/**
 * server.groovy — Persistent WaSQL Groovy daemon
 *
 * Start:  groovy -cp "groovy/lib/*" groovy/server.groovy
 *
 * Endpoints
 *   GET  /ping               — health check
 *   POST /query/{dbname}     — body = raw SQL        → queryResults → JSON
 *   POST /execute/{dbname}   — body = raw SQL        → executeSQL   → JSON
 *   POST /executeps/{dbname} — body = JSON { "query":"...", "args":{} }
 *   POST /eval               — body = raw Groovy script
 *   GET  /reload             — flush module cache (recompile on next request)
 *   GET  /exit  | /shutdown  — graceful shutdown
 *
 * All responses: { "success": true,  "data": ... }
 *                { "success": false, "error": "..." }
 *
 * PID file: groovy/server.pid — written on start, deleted on stop.
 */

import com.sun.net.httpserver.HttpServer
import com.sun.net.httpserver.HttpExchange
import groovy.json.JsonGenerator
import groovy.json.JsonOutput
import groovy.json.JsonSlurper
import groovy.transform.Field
import java.lang.management.ManagementFactory
import java.util.concurrent.Executors
import java.util.concurrent.TimeUnit
import java.util.concurrent.atomic.AtomicLong

// ── Shared state (@Field = Script instance field, visible from methods + threads) ──

@Field int    PORT            = (System.getenv('WASQL_GROOVY_PORT')    ?: '7070').toInteger()
@Field int    THREADS         = (System.getenv('WASQL_GROOVY_THREADS') ?: '32').toInteger()
@Field int    MAX_BODY_MB     = (System.getenv('WASQL_GROOVY_MAX_BODY_MB') ?: '16').toInteger()
@Field long   IDLE_TIMEOUT_MS = { def m = System.getenv('WASQL_GROOVY_IDLE_MINUTES'); m ? m.toLong() * 60_000L : 60L * 60_000L }()
@Field String SCRIPT_DIR      = new File(getClass().protectionDomain.codeSource.location.path).parent
@Field File   PID_FILE        = new File(System.getenv('WASQL_GROOVY_PID_FILE')   ?: (SCRIPT_DIR + '/server.pid'))
@Field File   TOKEN_FILE      = new File(System.getenv('WASQL_GROOVY_TOKEN_FILE') ?: (SCRIPT_DIR + '/server.token'))
@Field String TOKEN           = System.getenv('WASQL_GROOVY_TOKEN') ?: UUID.randomUUID().toString()
@Field String PID             = ManagementFactory.getRuntimeMXBean().getName().split('@')[0]
@Field long   startedAt       = System.currentTimeMillis()
@Field def    lastActivity    = new AtomicLong(System.currentTimeMillis())
@Field def    modules         = new java.util.concurrent.ConcurrentHashMap<String, Object>()
@Field volatile Map DATABASE   = [:]
@Field def    serverRef       = null
@Field def    schedulerRef    = null

@Field def DT_FMT   = java.time.format.DateTimeFormatter.ofPattern('yyyy-MM-dd HH:mm:ss')
@Field def DATE_FMT = java.time.format.DateTimeFormatter.ofPattern('yyyy-MM-dd')
@Field def TIME_FMT = java.time.format.DateTimeFormatter.ofPattern('HH:mm:ss')

@Field def JSON = {
    def opts = new JsonGenerator.Options()
        .disableUnicodeEscaping()
        // ── SQL legacy types ──────────────────────────────────────────────────
        .addConverter(java.sql.Timestamp)          { v -> v.toLocalDateTime().format(DT_FMT) }
        .addConverter(java.sql.Date)               { v -> v.toLocalDate().format(DATE_FMT) }
        .addConverter(java.sql.Time)               { v -> v.toLocalTime().format(TIME_FMT) }
        .addConverter(java.util.Date)              { v -> v.toInstant().atZone(java.time.ZoneId.systemDefault()).toLocalDateTime().format(DT_FMT) }
        // ── java.time types (specific before Temporal catch-all) ─────────────
        .addConverter(java.time.LocalDateTime)     { v -> v.format(DT_FMT) }
        .addConverter(java.time.LocalDate)         { v -> v.format(DATE_FMT) }
        .addConverter(java.time.LocalTime)         { v -> v.format(TIME_FMT) }
        .addConverter(java.time.OffsetDateTime)    { v -> v.toLocalDateTime().format(DT_FMT) }
        .addConverter(java.time.ZonedDateTime)     { v -> v.toLocalDateTime().format(DT_FMT) }
        .addConverter(java.time.temporal.Temporal) { v -> v.toString() }
        // ── Binary / LOB ─────────────────────────────────────────────────────
        .addConverter(java.sql.Clob)               { v -> v.getSubString(1L, (int) v.length()) }
        .addConverter(java.sql.Blob)               { v -> v.getBytes(1L, (int) v.length()).encodeBase64().toString() }
        // ── PostgreSQL / other arrays ─────────────────────────────────────────
        .addConverter(java.sql.Array)              { v -> v.getArray().toList() }
    // ── PostgreSQL JSON/JSONB (PGobject) — only if pg driver is on classpath ─
    try {
        opts.addConverter(Class.forName('org.postgresql.util.PGobject')) { v ->
            def val = v.getValue()
            if (val == null) return null
            (v.type in ['json', 'jsonb']) ? new JsonSlurper().parseText(val) : val
        }
    } catch (ClassNotFoundException ignored) {}
    opts.build()
}()

@Field Map DRIVER_MAP = [
    hana      : 'hanadb',
    mssql     : 'mssqldb',
    mysql     : 'mysqldb',
    oracle    : 'oracledb',
    snowflake : 'snowflakedb',
    sqlite    : 'sqlitedb',
    ctree     : 'ctreedb',
    postgre   : 'postgresdb',
    msaccess  : 'msaccessdb',
    mscsv     : 'mscsvdb',
    msexcel   : 'msexceldb',
    firebird  : 'firebirddb',
    duckdb    : 'duckdb',
]

// JDBC driver class for each module — used to detect missing lib jars at startup.
@Field Map DRIVER_CLASS_MAP = [
    ctreedb    : 'ctree.jdbc.ctreeDriver',
    duckdb     : 'org.duckdb.DuckDBDriver',
    hanadb     : 'com.sap.db.jdbc.Driver',
    msaccessdb : 'net.ucanaccess.jdbc.UcanaccessDriver',
    mssqldb    : 'com.microsoft.sqlserver.jdbc.SQLServerDriver',
    mysqldb    : 'com.mysql.cj.jdbc.Driver',
    oracledb   : 'oracle.jdbc.OracleDriver',
    postgresdb : 'org.postgresql.Driver',
    snowflakedb: 'net.snowflake.client.jdbc.SnowflakeDriver',
    sqlitedb   : 'org.sqlite.JDBC',
]

// ── Methods ───────────────────────────────────────────────────────────────────

void log(String msg) {
    System.err.println("[wasql-groovy] ${new Date().format('HH:mm:ss')} $msg")
}

String readBody(HttpExchange ex) {
    long max = MAX_BODY_MB * 1024L * 1024L
    def len = ex.requestHeaders.getFirst('Content-Length')
    if (len && len.toLong() > max)
        throw new IllegalArgumentException("Request body exceeds ${MAX_BODY_MB} MB limit")
    def bytes = ex.requestBody.readNBytes((int) Math.min(max + 1, Integer.MAX_VALUE))
    if (bytes.length > max)
        throw new IllegalArgumentException("Request body exceeds ${MAX_BODY_MB} MB limit")
    return new String(bytes, 'UTF-8').trim()
}

Object loadModule(String name) {
    def f = new File(SCRIPT_DIR, "${name}.groovy")
    if (!f.exists()) throw new RuntimeException("${name}.groovy not found in ${SCRIPT_DIR}")
    def parentCL = getClass().classLoader
    modules.computeIfAbsent(name) { n ->
        log("Compiling ${n}.groovy")
        def savedCL = Thread.currentThread().contextClassLoader
        try {
            new GroovyShell(parentCL).evaluate(new File(SCRIPT_DIR, "${n}.groovy"))
        } finally {
            Thread.currentThread().contextClassLoader = savedCL
        }
    }
}

Map resolveDriver(String dbname) {
    def dbconf = DATABASE[dbname]
    if (!dbconf) throw new IllegalArgumentException("Database '${dbname}' not found in config.xml. Available: ${DATABASE.keySet().sort().join(', ')}")
    def dbtype  = (dbconf.dbtype ?: '').toLowerCase()
    def modName = DRIVER_MAP.find { k, _ -> dbtype.startsWith(k) }?.value
    if (!modName) throw new IllegalArgumentException("Unsupported database type: '${dbtype}'")
    return [driver: loadModule(modName), params: [:] + dbconf]
}

// Extracts the path segment after a known prefix, URL-decoded.
// e.g. pathParam(ex, '/query/') on '/query/my_db' → 'my_db'
String pathParam(HttpExchange ex, String prefix) {
    URLDecoder.decode(ex.requestURI.path.substring(prefix.length()), 'UTF-8').trim()
}

void respond(HttpExchange ex, int code, String json) {
    respondAs(ex, code, 'application/json; charset=UTF-8', json)
}

void respondAs(HttpExchange ex, int code, String contentType, String body) {
    byte[] b = body.getBytes('UTF-8')
    ex.responseHeaders.set('Content-Type', contentType)
    ex.sendResponseHeaders(code, b.length)
    def os = ex.responseBody
    try { os.write(b) } finally { os.close() }
}

// If the driver already returned a JSON string embed it raw; otherwise serialize.
String wrapOk(Object result) {
    if (result instanceof String) {
        def t = result.trim()
        if (t.startsWith('[') || t.startsWith('{')) {
            return "{\"success\":true,\"data\":${t}}"
        }
    }
    return JSON.toJson([success: true, data: result])
}

String wrapErr(String msg) {
    return JsonOutput.toJson([success: false, error: msg])
}

// 400 for caller errors (bad SQL, missing params), 500 for server/driver errors.
int errorCode(Exception e) {
    (e instanceof IllegalArgumentException
  || e instanceof java.sql.SQLSyntaxErrorException
  || e instanceof UnsupportedOperationException) ? 400 : 500
}

boolean checkAuth(HttpExchange ex) {
    if (ex.requestHeaders.getFirst('X-WaSQL-Token') == TOKEN) return true
    respond(ex, 401, wrapErr('Unauthorized'))
    return false
}

void doShutdown(String reason) {
    log("Shutting down: ${reason}")
    PID_FILE.delete()
    TOKEN_FILE.delete()
    serverRef?.stop(5)
    schedulerRef?.shutdown()
    schedulerRef?.awaitTermination(5, TimeUnit.SECONDS)
    System.exit(0)
}

// ── Initialise ────────────────────────────────────────────────────────────────

def cfg = loadModule('config')
DATABASE = cfg.DATABASE as Map

def jarAvailableCache = [:] as HashMap  // modName → Boolean
def missingJarDrivers = [] as LinkedHashSet

DATABASE.each { dbname, dbconf ->
    def dbtype  = (dbconf.dbtype ?: '').toLowerCase()
    def modName = DRIVER_MAP.find { k, _ -> dbtype.startsWith(k) }?.value
    if (modName) {
        boolean jarOk = jarAvailableCache.computeIfAbsent(modName) { n ->
            def cls = DRIVER_CLASS_MAP[n]
            if (!cls) return true
            try { Class.forName(cls); return true }
            catch (ClassNotFoundException ignored) { return false }
        }
        if (!jarOk) {
            missingJarDrivers << modName
            return
        }
        try {
            loadModule(modName)
            log("Pre-loaded '${modName}' for database '${dbname}'")
        } catch (Exception e) {
            log("Warning: driver '${modName}' not available — skipping database '${dbname}'")
        }
    } else {
        log("Warning: no driver mapped for dbtype='${dbtype}' (database '${dbname}')")
    }
}
if (missingJarDrivers) log("Skipped (jar not found): ${missingJarDrivers.sort().join(', ')}")

try { loadModule('common'); log("Pre-loaded 'common'") } catch (Exception e) { log("Warning: could not pre-load common — ${e.message}") }
try { loadModule('db');     log("Pre-loaded 'db'")     } catch (Exception e) { log("Warning: could not pre-load db — ${e.message}") }

log("Ready: ${modules.size()} module(s) — ${modules.keySet().sort().join(', ')}")

// Suppress groovy.sql WARNING logs — errors are returned as JSON; stack traces are noise.
// Must run AFTER modules are loaded so groovy.sql.Sql's static LOG field holds a strong
// reference to the logger, preventing it from being GC'd and recreated with default settings.
['groovy.sql', 'groovy.sql.Sql'].each { name ->
    java.util.logging.Logger.getLogger(name).with {
        level             = java.util.logging.Level.OFF
        useParentHandlers = false
    }
}

// ── HTTP server ────────────────────────────────────────────────────────────────

def server    = HttpServer.create(new InetSocketAddress('127.0.0.1', PORT), 256)
def scheduler = Executors.newSingleThreadScheduledExecutor()

// Bounded thread pool — prevents unbounded thread growth under sustained load.
// CallerRunsPolicy applies backpressure when the queue is full rather than dropping requests.
def scriptCL    = getClass().classLoader
def threadCount = new java.util.concurrent.atomic.AtomicInteger(0)
def threadFactory = { Runnable r ->
    def t = new Thread(r, "wasql-handler-${threadCount.incrementAndGet()}")
    t.contextClassLoader = scriptCL
    return t
} as java.util.concurrent.ThreadFactory
server.setExecutor(new java.util.concurrent.ThreadPoolExecutor(
    THREADS, THREADS, 60L, TimeUnit.SECONDS,
    new java.util.concurrent.LinkedBlockingQueue<Runnable>(512),
    threadFactory,
    new java.util.concurrent.ThreadPoolExecutor.CallerRunsPolicy()
))

serverRef    = server
schedulerRef = scheduler

// ── Endpoints ─────────────────────────────────────────────────────────────────

// GET /ping
server.createContext('/ping') { HttpExchange ex ->
    if (!checkAuth(ex)) return
    lastActivity.set(System.currentTimeMillis())
    try {
        respond(ex, 200, JsonOutput.toJson([
            status : 'ok',
            pid    : PID,
            uptime : System.currentTimeMillis() - startedAt,
            modules: modules.keySet().sort()
        ]))
    } catch (Exception e) {
        log("/ping error: ${e.message}")
        e.printStackTrace(System.err)
    }
}

// POST /query/{dbname}  — body is raw SQL, returns queryResults as JSON
server.createContext('/query/') { HttpExchange ex ->
    if (!checkAuth(ex)) return
    lastActivity.set(System.currentTimeMillis())
    try {
        def t0     = System.currentTimeMillis()
        def dbname = pathParam(ex, '/query/')
        def query  = readBody(ex)
        if (!dbname) throw new IllegalArgumentException("URL must be /query/{dbname}")
        if (!query)  throw new IllegalArgumentException("Request body must contain a SQL query")
        def drv    = resolveDriver(dbname)
        def result = drv.driver.queryResults(query, drv.params + [format: 'list'])
        respond(ex, 200, wrapOk(result))
        log("/query ${dbname} — ${result instanceof List ? result.size() + ' rows' : ''} ${System.currentTimeMillis()-t0}ms")
    } catch (Exception e) {
        log("/query error: ${e.message}")
        try { respond(ex, errorCode(e), wrapErr(e.message)) } catch (Exception ignored) {}
    }
}

// POST /execute/{dbname}  — body is raw SQL, returns executeSQL as JSON
server.createContext('/execute/') { HttpExchange ex ->
    if (!checkAuth(ex)) return
    lastActivity.set(System.currentTimeMillis())
    try {
        def t0     = System.currentTimeMillis()
        def dbname = pathParam(ex, '/execute/')
        def query  = readBody(ex)
        if (!dbname) throw new IllegalArgumentException("URL must be /execute/{dbname}")
        if (!query)  throw new IllegalArgumentException("Request body must contain a SQL statement")
        def drv    = resolveDriver(dbname)
        def result = drv.driver.executeSQL(query, drv.params)
        respond(ex, 200, wrapOk(result))
        log("/execute ${dbname} — ${System.currentTimeMillis()-t0}ms")
    } catch (Exception e) {
        log("/execute error: ${e.message}")
        try { respond(ex, errorCode(e), wrapErr(e.message)) } catch (Exception ignored) {}
    }
}

// POST /executeps/{dbname}  — body is JSON { "query": "...", "args": {} }
server.createContext('/executeps/') { HttpExchange ex ->
    if (!checkAuth(ex)) return
    lastActivity.set(System.currentTimeMillis())
    try {
        def dbname = pathParam(ex, '/executeps/')
        def req    = new JsonSlurper().parseText(readBody(ex))
        if (!dbname)    throw new IllegalArgumentException("URL must be /executeps/{dbname}")
        if (!req.query) throw new IllegalArgumentException("Body must include 'query'")
        def drv    = resolveDriver(dbname)
        def args   = req.args instanceof Map ? req.args : [:]
        def result
        try {
            result = drv.driver.executePS(req.query as String, args, drv.params)
        } catch (MissingMethodException ignored) {
            throw new UnsupportedOperationException("executePS not implemented for this driver")
        }
        respond(ex, 200, wrapOk(result))
    } catch (Exception e) {
        log("/executeps error: ${e.message}")
        try { respond(ex, errorCode(e), wrapErr(e.message)) } catch (Exception ignored) {}
    }
}

// POST /eval  — body is raw Groovy script
// Runs arbitrary Groovy with db, common, config modules in binding.
// "output" = stdout captured via binding 'out' (PrintWriter per request — fully concurrent).
// "result" = return value of the script.
server.createContext('/eval') { HttpExchange ex ->
    if (!checkAuth(ex)) return
    lastActivity.set(System.currentTimeMillis())
    try {
        def script = readBody(ex)
        if (!script) throw new IllegalArgumentException("Request body must contain a Groovy script")

        def baos    = new ByteArrayOutputStream()
        def capture = new PrintWriter(new OutputStreamWriter(baos, 'UTF-8'), true)

        def binding = new Binding()
        ['db', 'common', 'config'].each { name ->
            def mod = modules[name]
            if (mod) binding.setVariable(name, mod)
        }
        binding.setVariable('DATABASE',   DATABASE)
        binding.setVariable('SCRIPT_DIR', SCRIPT_DIR)
        binding.setVariable('out',        capture)  // println routes here, not System.out

        def result
        def evalErr = null
        try {
            result = new GroovyShell(getClass().classLoader, binding).evaluate(script)
        } catch (Exception e) {
            evalErr = e
        } finally {
            capture.flush()
        }

        def output = baos.toString('UTF-8')

        if (evalErr) {
            log("/eval error: ${evalErr.message}")
            respond(ex, 500, JsonOutput.toJson([success: false, error: evalErr.message, output: output]))
        } else {
            def resultJson
            try   { resultJson = JSON.toJson(result) }
            catch (Exception ignored) { resultJson = JSON.toJson(result?.toString()) }
            respond(ex, 200, "{\"success\":true,\"output\":${JsonOutput.toJson(output)},\"result\":${resultJson}}")
        }
    } catch (Exception e) {
        log("/eval error: ${e.message}")
        try { respond(ex, errorCode(e), wrapErr(e.message)) } catch (Exception ignored) {}
    }
}

// GET /databases  — list all configured databases grouped by dbtype
server.createContext('/databases') { HttpExchange ex ->
    if (!checkAuth(ex)) return
    lastActivity.set(System.currentTimeMillis())
    try {
        def grouped = [:].withDefault { [] }
        DATABASE.each { dbname, dbconf ->
            def dbtype  = (dbconf.dbtype ?: '').toLowerCase()
            def modName = DRIVER_MAP.find { mk, _ -> dbtype.startsWith(mk) }?.value
            if (modName && modules.containsKey(modName)) {
                grouped[dbtype] << dbname
            }
        }
        def result = grouped.collectEntries { type, names -> [type, names.sort()] }.sort()
        respond(ex, 200, wrapOk(result))
    } catch (Exception e) {
        log("/databases error: ${e.message}")
        try { respond(ex, 500, wrapErr(e.message)) } catch (Exception ignored) {}
    }
}

// GET/POST /reload  — flush module cache; next request recompiles from disk
server.createContext('/reload') { HttpExchange ex ->
    if (!checkAuth(ex)) return
    lastActivity.set(System.currentTimeMillis())
    try {
        def cleared = modules.keySet().sort()
        modules.clear()
        cfg = loadModule('config')
        DATABASE = cfg.DATABASE as Map
        log("Module cache cleared and config reloaded: ${cleared.join(', ') ?: '(none)'}")
        respond(ex, 200, JsonOutput.toJson([status: 'reloaded', cleared: cleared, databases: DATABASE.keySet().sort()]))
    } catch (Exception e) {
        log("/reload error: ${e.message}")
        try { respond(ex, 500, wrapErr(e.message)) } catch (Exception ignored) {}
    }
}

// POST /shutdown  (or GET — accepts any method)
// POST /exit      — alias
def shutdownHandler = { HttpExchange ex ->
    if (!checkAuth(ex)) return
    try { respond(ex, 200, JsonOutput.toJson([status: 'shutting down', pid: PID])) } catch (Exception ignored) {}
    Thread.start { Thread.sleep(200); doShutdown("HTTP ${ex.requestURI.path} request") }
}
server.createContext('/shutdown', shutdownHandler)
server.createContext('/exit',     shutdownHandler)

// GET /openapi — OpenAPI 3.0 JSON spec (unauthenticated)
Closure buildSpec = {
    def dbNames = DATABASE.keySet().sort()
    def dbNameEnum = dbNames ?: ['mydb']
    return [
        openapi: '3.0.3',
        info: [
            title  : 'WaSQL Groovy Server',
            version: '1.0.0',
            description: "Persistent Groovy/SQL daemon. Authenticate via the **X-WaSQL-Token** header (value is in groovy/server.token). Port: ${PORT}."
        ],
        servers: [[ url: "http://127.0.0.1:${PORT}" ]],
        components: [
            securitySchemes: [
                TokenAuth: [ type: 'apiKey', in: 'header', name: 'X-WaSQL-Token' ]
            ]
        ],
        security: [[ TokenAuth: [] ]],
        paths: [
            '/databases': [
                get: [
                    summary    : 'List all configured databases grouped by type',
                    operationId: 'databases',
                    responses  : [
                        '200': [ description: 'Databases by type', content: [ 'application/json': [ schema: [ type: 'object', properties: [ success: [type:'boolean'], data: [type:'object', additionalProperties: [type:'array', items:[type:'string']], example: [mysql:['mydb','cms'], postgresql:['analytics']]] ] ] ] ] ]
                    ]
                ]
            ],
            '/ping': [
                get: [
                    summary    : 'Health check',
                    operationId: 'ping',
                    responses  : [
                        '200': [ description: 'Server status', content: [ 'application/json': [ schema: [ type: 'object', properties: [ status: [type:'string'], pid: [type:'string'], uptime: [type:'integer'], modules: [type:'array', items:[type:'string']] ] ] ] ] ]
                    ]
                ]
            ],
            '/query/{dbname}': [
                post: [
                    summary    : 'Run a SELECT query, returns rows as JSON array',
                    operationId: 'query',
                    parameters : [[ name: 'dbname', in: 'path', required: true, schema: [type:'string', enum: dbNameEnum] ]],
                    requestBody: [ required: true, content: [ 'text/plain': [ schema: [type:'string', example:'SELECT * FROM users LIMIT 10'] ] ] ],
                    responses  : [
                        '200': [ description: 'Query results', content: [ 'application/json': [ schema: [ type:'object', properties: [ success:[type:'boolean'], data:[type:'array'] ] ] ] ] ],
                        '400': [ description: 'Bad SQL or missing params' ],
                        '500': [ description: 'Driver / server error' ]
                    ]
                ]
            ],
            '/execute/{dbname}': [
                post: [
                    summary    : 'Execute a non-SELECT statement (INSERT/UPDATE/DELETE/DDL)',
                    operationId: 'execute',
                    parameters : [[ name: 'dbname', in: 'path', required: true, schema: [type:'string', enum: dbNameEnum] ]],
                    requestBody: [ required: true, content: [ 'text/plain': [ schema: [type:'string', example:'DELETE FROM sessions WHERE expired = 1'] ] ] ],
                    responses  : [
                        '200': [ description: 'Rows affected', content: [ 'application/json': [ schema: [ type:'object', properties: [ success:[type:'boolean'], data:[type:'integer'] ] ] ] ] ],
                        '400': [ description: 'Bad SQL or missing params' ],
                        '500': [ description: 'Driver / server error' ]
                    ]
                ]
            ],
            '/executeps/{dbname}': [
                post: [
                    summary    : 'Execute a parameterised statement',
                    operationId: 'executeps',
                    parameters : [[ name: 'dbname', in: 'path', required: true, schema: [type:'string', enum: dbNameEnum] ]],
                    requestBody: [ required: true, content: [ 'application/json': [ schema: [ type:'object', required:['query'], properties: [ query:[type:'string', example:'UPDATE users SET name=:name WHERE id=:id'], args:[type:'object', example:[name:'Alice', id:1]] ] ] ] ] ],
                    responses  : [
                        '200': [ description: 'Result', content: [ 'application/json': [ schema: [ type:'object', properties: [ success:[type:'boolean'], data:[type:'integer'] ] ] ] ] ],
                        '400': [ description: 'Bad SQL or missing params' ],
                        '500': [ description: 'Driver / server error' ]
                    ]
                ]
            ],
            '/eval': [
                post: [
                    summary    : 'Evaluate arbitrary Groovy (db, common, config modules in binding)',
                    operationId: 'eval',
                    requestBody: [ required: true, content: [ 'text/plain': [ schema: [type:'string', example:"db.queryResults('SELECT 1', [:])"] ] ] ],
                    responses  : [
                        '200': [ description: 'Script result', content: [ 'application/json': [ schema: [ type:'object', properties: [ success:[type:'boolean'], output:[type:'string'], result:[description:'Return value of the script'] ] ] ] ] ],
                        '500': [ description: 'Script threw an exception' ]
                    ]
                ]
            ],
            '/reload': [
                get: [
                    summary    : 'Flush module cache — next request recompiles .groovy files from disk',
                    operationId: 'reload',
                    responses  : [
                        '200': [ description: 'Cache cleared', content: [ 'application/json': [ schema: [ type:'object', properties: [ status:[type:'string'], cleared:[type:'array', items:[type:'string']] ] ] ] ] ]
                    ]
                ]
            ],
            '/shutdown': [
                post: [
                    summary    : 'Graceful shutdown',
                    operationId: 'shutdown',
                    responses  : [ '200': [ description: 'Shutting down' ] ]
                ]
            ],
            '/exit': [
                post: [
                    summary    : 'Alias for /shutdown',
                    operationId: 'exit',
                    responses  : [ '200': [ description: 'Shutting down' ] ]
                ]
            ]
        ]
    ]
}

server.createContext('/openapi') { HttpExchange ex ->
    lastActivity.set(System.currentTimeMillis())
    respond(ex, 200, JSON.toJson(buildSpec()))
}

// GET /  — RapidDoc UI (unauthenticated)
server.createContext('/') { HttpExchange ex ->
    if (ex.requestURI.path != '/') { respond(ex, 404, wrapErr('Not found')); return }
    lastActivity.set(System.currentTimeMillis())
    def html = """\
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>WaSQL Groovy Server — API Docs</title>
  <script type="module" src="https://unpkg.com/rapidoc/dist/rapidoc-min.js"></script>
  <style>
    html, body { height: 100%; margin: 0; }
    rapi-doc img { max-height: 50px; padding: 8px 12px; }
  </style>
</head>
<body>
<rapi-doc
  spec-url="/openapi"
  heading-text="WaSQL Groovy Server"
  show-info="true"
  allow-try="true"
  allow-search="true"
  persist-auth="true"
  show-curl-before-try="true"
  api-key-name="X-WaSQL-Token"
  api-key-location="header"
  api-key-value="${TOKEN}"
  theme="light"
  bg-color="#ffffff"
  text-color="#111827"
  header-color="#0056b3"
  primary-color="#f89723"
  nav-bg-color="#0d1b35"
  nav-text-color="#a0c4ff"
  nav-hover-bg-color="#0086ff"
  nav-hover-text-color="#ffffff"
  nav-accent-color="#f89723"
>
  <img slot="logo" src="https://www.wasql.com/images/wasql_logo.png" />
  <style slot="rapidoc-style">
    input { background-color: #ffffff !important; color: #111827 !important; }
  </style>
</rapi-doc>
</body>
</html>"""
    respondAs(ex, 200, 'text/html; charset=UTF-8', html)
}

// ── Start ─────────────────────────────────────────────────────────────────────
Runtime.runtime.addShutdownHook(new Thread({ PID_FILE.delete(); TOKEN_FILE.delete() }))
PID_FILE.text   = "${PID}\n"
TOKEN_FILE.text = "${TOKEN}\n"
server.start()
log("Listening on 127.0.0.1:${PORT}  PID ${PID}")
log("Token file: ${TOKEN_FILE.path}")
log("Threads: ${THREADS}  Queue: 512  Max body: ${MAX_BODY_MB} MB")
log("Auto-shutdown: ${IDLE_TIMEOUT_MS > 0 ? "${IDLE_TIMEOUT_MS / 60000 as long} min idle" : 'disabled'}")

// ── Idle watchdog ─────────────────────────────────────────────────────────────
scheduler.scheduleAtFixedRate({
    try {
        if (IDLE_TIMEOUT_MS > 0) {
            long idleMs = System.currentTimeMillis() - lastActivity.get()
            if (idleMs >= IDLE_TIMEOUT_MS) {
                log("Idle for ${idleMs / 60000 as long} min — shutting down")
                doShutdown('idle timeout')
            }
        }
    } catch (Exception e) {
        log("Watchdog error: ${e.message}")
    }
}, 60, 60, TimeUnit.SECONDS)
