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

@Field int    PORT            = (System.getenv('WASQL_GROOVY_PORT') ?: '7070').toInteger()
@Field long   IDLE_TIMEOUT_MS = 10L * 60 * 1000
@Field String SCRIPT_DIR      = new File(getClass().protectionDomain.codeSource.location.path).parent
@Field File   PID_FILE        = new File(SCRIPT_DIR, 'server.pid')
@Field File   TOKEN_FILE      = new File(SCRIPT_DIR, 'server.token')
@Field String TOKEN           = System.getenv('WASQL_GROOVY_TOKEN') ?: UUID.randomUUID().toString()
@Field String PID             = ManagementFactory.getRuntimeMXBean().getName().split('@')[0]
@Field long   startedAt       = System.currentTimeMillis()
@Field def    lastActivity    = new AtomicLong(System.currentTimeMillis())
@Field def    modules         = new java.util.concurrent.ConcurrentHashMap<String, Object>()
@Field Map    DATABASE        = [:]
@Field def    serverRef       = null
@Field def    schedulerRef    = null

@Field def DT_FMT = java.time.format.DateTimeFormatter.ofPattern('yyyy-MM-dd HH:mm:ss')
@Field def D_FMT  = java.time.format.DateTimeFormatter.ofPattern('yyyy-MM-dd')
@Field def T_FMT  = java.time.format.DateTimeFormatter.ofPattern('HH:mm:ss')

@Field def JSON = {
    def opts = new JsonGenerator.Options()
        .disableUnicodeEscaping()
        // ── SQL legacy types ──────────────────────────────────────────────────
        .addConverter(java.sql.Timestamp)          { v -> v.toLocalDateTime().format(DT_FMT) }
        .addConverter(java.sql.Date)               { v -> v.toLocalDate().format(D_FMT) }
        .addConverter(java.sql.Time)               { v -> v.toLocalTime().format(T_FMT) }
        .addConverter(java.util.Date)              { v -> v.toInstant().atZone(java.time.ZoneId.systemDefault()).toLocalDateTime().format(DT_FMT) }
        // ── java.time types (specific before Temporal catch-all) ─────────────
        .addConverter(java.time.LocalDateTime)     { v -> v.format(DT_FMT) }
        .addConverter(java.time.LocalDate)         { v -> v.format(D_FMT) }
        .addConverter(java.time.LocalTime)         { v -> v.format(T_FMT) }
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

// ── Methods ───────────────────────────────────────────────────────────────────

void log(String msg) {
    System.err.println("[wasql-groovy] ${new Date().format('HH:mm:ss')} $msg")
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
    if (!dbconf) throw new IllegalArgumentException("Database '${dbname}' not found in config.xml")
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
    byte[] b = json.getBytes('UTF-8')
    ex.responseHeaders.set('Content-Type', 'application/json; charset=UTF-8')
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
    serverRef?.stop(2)
    schedulerRef?.shutdownNow()
    System.exit(0)
}

// ── Initialise ────────────────────────────────────────────────────────────────

def cfg = loadModule('config')
DATABASE = cfg.DATABASE as Map

DATABASE.each { dbname, dbconf ->
    def dbtype  = (dbconf.dbtype ?: '').toLowerCase()
    def modName = DRIVER_MAP.find { k, _ -> dbtype.startsWith(k) }?.value
    if (modName) {
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

def server    = HttpServer.create(new InetSocketAddress('127.0.0.1', PORT), 50)
def scheduler = Executors.newSingleThreadScheduledExecutor()

// Pin the script classloader on every handler thread so ServiceLoader (FastStringService etc.)
// finds Groovy services regardless of which child GroovyClassLoader module loading left behind.
def scriptCL   = getClass().classLoader
def threadCount = new java.util.concurrent.atomic.AtomicInteger(0)
server.setExecutor(Executors.newCachedThreadPool({ Runnable r ->
    def t = new Thread(r, "wasql-handler-${threadCount.incrementAndGet()}")
    t.contextClassLoader = scriptCL
    return t
} as java.util.concurrent.ThreadFactory))

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
        def dbname = pathParam(ex, '/query/')
        def query  = ex.requestBody.getText('UTF-8').trim()
        if (!dbname) throw new IllegalArgumentException("URL must be /query/{dbname}")
        if (!query)  throw new IllegalArgumentException("Request body must contain a SQL query")
        def drv    = resolveDriver(dbname)
        def result = drv.driver.queryResults(query, drv.params + [format: 'list'])
        respond(ex, 200, wrapOk(result))
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
        def dbname = pathParam(ex, '/execute/')
        def query  = ex.requestBody.getText('UTF-8').trim()
        if (!dbname) throw new IllegalArgumentException("URL must be /execute/{dbname}")
        if (!query)  throw new IllegalArgumentException("Request body must contain a SQL statement")
        def drv    = resolveDriver(dbname)
        def result = drv.driver.executeSQL(query, drv.params)
        respond(ex, 200, wrapOk(result))
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
        def req    = new JsonSlurper().parseText(ex.requestBody.getText('UTF-8'))
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
        def script = ex.requestBody.getText('UTF-8').trim()
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

// GET/POST /reload  — flush module cache; next request recompiles from disk
server.createContext('/reload') { HttpExchange ex ->
    if (!checkAuth(ex)) return
    lastActivity.set(System.currentTimeMillis())
    try {
        def cleared = modules.keySet().sort()
        modules.clear()
        log("Module cache cleared: ${cleared.join(', ') ?: '(none)'}")
        respond(ex, 200, JsonOutput.toJson([status: 'reloaded', cleared: cleared]))
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

// ── Start ─────────────────────────────────────────────────────────────────────
Runtime.runtime.addShutdownHook(new Thread({ PID_FILE.delete(); TOKEN_FILE.delete() }))
PID_FILE.text   = "${PID}\n"
TOKEN_FILE.text = "${TOKEN}\n"
server.start()
log("Listening on 127.0.0.1:${PORT}  PID ${PID}")
log("Token file: ${TOKEN_FILE.path}")
log("Auto-shutdown after ${IDLE_TIMEOUT_MS / 60000 as long} min idle")

// ── Idle watchdog ─────────────────────────────────────────────────────────────
scheduler.scheduleAtFixedRate({
    try {
        long idleMs = System.currentTimeMillis() - lastActivity.get()
        if (idleMs >= IDLE_TIMEOUT_MS) {
            log("Idle for ${idleMs / 60000 as long} min — shutting down")
            doShutdown('idle timeout')
        }
    } catch (Exception e) {
        log("Watchdog error: ${e.message}")
    }
}, 60, 60, TimeUnit.SECONDS)
