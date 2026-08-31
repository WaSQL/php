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

//---------- begin function JSON
/**
* @describe immediately-invoked builder for the shared JsonGenerator - registers converters for SQL/java.time temporal types, CLOB/BLOB, arrays and PostgreSQL JSON so result sets serialize to readable JSON
* @return object
*	the configured, reusable JsonGenerator (assigned to JSON)
* @usage
*	respond(ex, 200, JSON.toJson(result))
*/
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

//---------- begin function log
/**
* @describe writes a timestamped line to stderr
* @param params msg string
* @return void
* @usage
*	log("Listening on port ${PORT}")
*/
void log(String msg) {
    System.err.println("[wasql-groovy] ${new Date().format('HH:mm:ss')} $msg")
}

//---------- begin function readBody
/**
* @describe reads the request body as a trimmed UTF-8 string, enforcing the MAX_BODY_MB size limit (throws IllegalArgumentException if exceeded)
* @param params ex HttpExchange
* @return string
* @usage
*	query = readBody(ex)
*/
String readBody(HttpExchange ex) {
    long max = MAX_BODY_MB * 1024L * 1024L
    def len = ex.requestHeaders.getFirst('Content-Length')
    if (len && len.toLong() > max)
        throw new IllegalArgumentException("Request body exceeds ${MAX_BODY_MB} MB limit")
    // Read at most max+1 bytes so we can still detect an over-limit body when no
    // Content-Length is sent. InputStream.readNBytes(int) is Java 9+, so do it by
    // hand to keep the server compatible with Java 8.
    def bytes = readUpTo(ex.requestBody, (int) Math.min(max + 1, Integer.MAX_VALUE))
    if (bytes.length > max)
        throw new IllegalArgumentException("Request body exceeds ${MAX_BODY_MB} MB limit")
    return new String(bytes, 'UTF-8').trim()
}

//---------- begin function readUpTo
/**
* @describe reads up to cap bytes from a stream (Java 8 safe - no readNBytes/readAllBytes)
* @param params is InputStream, cap integer
* @return mixed
*	byte array of the bytes read (may be shorter than cap if the stream ends first)
* @usage
*	bytes = readUpTo(ex.requestBody, 1024)
*/
byte[] readUpTo(InputStream is, int cap) {
    def buf = new ByteArrayOutputStream()
    byte[] chunk = new byte[8192]
    int total = 0
    while (total < cap) {
        int want = Math.min(chunk.length, cap - total)
        int n = is.read(chunk, 0, want)
        if (n == -1) break
        buf.write(chunk, 0, n)
        total += n
    }
    return buf.toByteArray()
}

//---------- begin function loadModule
/**
* @describe compiles and evaluates a .groovy module from SCRIPT_DIR, caching the result so each module compiles only once (until /reload clears the cache)
* @param params name string
*	name: module name without the .groovy extension (e.g. 'mysqldb')
* @return object
* @usage
*	driver = loadModule('mysqldb')
*/
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

//---------- begin function resolveDriver
/**
* @describe resolves a configured database name to its driver module and connection params (throws IllegalArgumentException if unknown or unsupported dbtype)
* @param params dbname string
*	dbname: database name from a database tag in config.xml
* @return map
*	[driver: module object, params: connection param map]
* @usage
*	drv = resolveDriver('mydb'); drv.driver.queryResults(sql, drv.params)
*/
Map resolveDriver(String dbname) {
    def dbconf = DATABASE[dbname]
    if (!dbconf) throw new IllegalArgumentException("Database '${dbname}' not found in config.xml. Available: ${DATABASE.keySet().sort().join(', ')}")
    def dbtype  = (dbconf.dbtype ?: '').toLowerCase()
    def modName = DRIVER_MAP.find { k, _ -> dbtype.startsWith(k) }?.value
    if (!modName) throw new IllegalArgumentException("Unsupported database type: '${dbtype}'")
    return [driver: loadModule(modName), params: [:] + dbconf]
}

//---------- begin function pathParam
/**
* @describe extracts the URL-decoded path segment after a known prefix, e.g. pathParam(ex, '/query/') on '/query/my_db' returns 'my_db'
* @param params ex HttpExchange, prefix string
* @return string
* @usage
*	dbname = pathParam(ex, '/query/')
*/
String pathParam(HttpExchange ex, String prefix) {
    URLDecoder.decode(ex.requestURI.path.substring(prefix.length()), 'UTF-8').trim()
}

//---------- begin function queryParams
/**
* @describe parses the request's query string into a URL-decoded map (values default to '' when absent)
* @param params ex HttpExchange
* @return map
* @usage
*	q = queryParams(ex); op = q.op
*/
Map queryParams(HttpExchange ex) {
    def out = [:]
    def raw = ex.requestURI.rawQuery
    if (raw) raw.split('&').each { pair ->
        def kv = pair.split('=', 2)
        if (kv.length && kv[0]) out[URLDecoder.decode(kv[0], 'UTF-8')] = kv.length > 1 ? URLDecoder.decode(kv[1], 'UTF-8') : ''
    }
    return out
}

// ── JDBC DatabaseMetaData helpers (ODBC-free schema introspection) ─────────────
// A blank/absent schema means "all schemas" (null pattern).

//---------- begin function metaTables
/**
* @describe lists table names via JDBC DatabaseMetaData (blank/null schema = all schemas)
* @param params md object, schema string
*	md: DatabaseMetaData from the JDBC connection
* @return list
*	table names, sorted
* @usage
*	tables = metaTables(md, 'dbo')
*/
List metaTables(md, String schema) {
    def rs = md.getTables(null, (schema ?: null), '%', ['TABLE'] as String[])
    def out = []
    try { while (rs.next()) out << rs.getString('TABLE_NAME') } finally { rs.close() }
    out.sort()
}

//---------- begin function metaColumns
/**
* @describe introspects a table's columns via the ResultSetMetaData of "SELECT * FROM t WHERE 1=0" - far faster than DatabaseMetaData.getColumns() on some drivers and works for every JDBC driver, but column DEFAULT values are not available
* @param params conn object, md object, schema string, table string
*	conn: the JDBC connection
*	md: DatabaseMetaData from the connection (used to resolve the schema)
*	schema: schema name, or blank/null to auto-resolve
*	table: table name (required)
* @return list
*	list of maps [name, type, size, scale, nullable, default, position]
* @usage
*	cols = metaColumns(sql.connection, md, null, 'users')
*/
List metaColumns(conn, md, String schema, String table) {
    if (!table) throw new IllegalArgumentException("meta columns: 'table' is required")
    // resolve the schema (needed to qualify the table) when the caller didn't supply one
    def eff = schema
    if (!eff) {
        def rt = md.getTables(null, null, table, ['TABLE'] as String[])
        try { if (rt.next()) eff = rt.getString('TABLE_SCHEM') } finally { rt.close() }
    }
    // guard: only simple identifiers may be interpolated into the introspection SQL
    def ok = { s -> s == null || s ==~ /[A-Za-z0-9_$#]+/ }
    if (!ok(eff) || !ok(table)) throw new IllegalArgumentException("invalid table/schema name")
    def ref = eff ? "${eff}.${table}" : table
    def st = conn.createStatement()
    def out = []
    try {
        def rs = st.executeQuery("SELECT * FROM ${ref} WHERE 1=0")
        try {
            def m  = rs.getMetaData()
            int cc = m.getColumnCount()
            for (int i = 1; i <= cc; i++) {
                out << [
                    name    : m.getColumnName(i),
                    type    : m.getColumnTypeName(i),
                    size    : m.getPrecision(i),
                    scale   : m.getScale(i),
                    nullable: (m.isNullable(i) == java.sql.ResultSetMetaData.columnNoNulls) ? 0 : 1,
                    'default': null,
                    position: i
                ]
            }
        } finally { rs.close() }
    } finally { st.close() }
    out
}

//---------- begin function metaPrimaryKey
/**
* @describe returns a table's primary-key name and columns via JDBC DatabaseMetaData
* @param params md object, schema string, table string
*	md: DatabaseMetaData from the JDBC connection
*	schema: schema to filter by, or blank/null for all schemas
* @return map
*	[name: pk name or null, cols: ordered set of column names]
* @usage
*	pk = metaPrimaryKey(md, null, 'users')
*/
Map metaPrimaryKey(md, String schema, String table) {
    def rs = md.getPrimaryKeys(null, (schema ?: null), table)
    def name = null
    def cols = [] as LinkedHashSet
    try { while (rs.next()) { name = rs.getString('PK_NAME'); cols << rs.getString('COLUMN_NAME') } } finally { rs.close() }
    [name: name, cols: cols]
}

//---------- begin function metaIndexes
/**
* @describe lists a table's indexes via JDBC DatabaseMetaData, flagging primary and unique keys (throws IllegalArgumentException if table is missing)
* @param params md object, schema string, table string
*	md: DatabaseMetaData from the JDBC connection
*	schema: schema to filter by, or blank/null for all schemas
*	table: table name (required)
* @return list
*	list of maps [key_name, column_name, seq_in_index, is_unique, is_primary, index_type]
* @usage
*	idx = metaIndexes(md, null, 'users')
*/
List metaIndexes(md, String schema, String table) {
    if (!table) throw new IllegalArgumentException("meta indexes: 'table' is required")
    def pk = metaPrimaryKey(md, schema, table)
    def rs = md.getIndexInfo(null, (schema ?: null), table, false, false)
    def out = []
    try {
        while (rs.next()) {
            def iname = rs.getString('INDEX_NAME')
            if (iname == null) continue   // tableIndexStatistic row has a null index name
            out << [
                key_name    : iname,
                column_name : rs.getString('COLUMN_NAME'),
                seq_in_index: rs.getInt('ORDINAL_POSITION'),
                is_unique   : rs.getBoolean('NON_UNIQUE') ? 0 : 1,
                is_primary  : (iname == pk.name) ? 1 : 0,
                index_type  : rs.getInt('TYPE')
            ]
        }
    } finally { rs.close() }
    out
}

//---------- begin function metaDDL
/**
* @describe builds a best-effort CREATE TABLE statement from column and primary-key metadata (column DEFAULTs are unavailable - see metaColumns)
* @param params conn object, md object, schema string, table string
*	conn: the JDBC connection
*	md: DatabaseMetaData from the connection
*	schema: schema name, or blank/null
* @return string
*	the CREATE TABLE DDL, or a "-- table not found" comment
* @usage
*	ddl = metaDDL(sql.connection, md, null, 'users')
*/
String metaDDL(conn, md, String schema, String table) {
    def cols = metaColumns(conn, md, schema, table)
    if (!cols) return "-- table not found: ${schema ? schema + '.' : ''}${table}"
    def pk = metaPrimaryKey(md, schema, table)
    def lines = cols.collect { col ->
        def t = col.type
        def lt = (col.type ?: '').toLowerCase()
        if (col.scale && col.scale > 0)      t = "${col.type}(${col.size},${col.scale})"
        else if (col.size && col.size > 0 && (lt.contains('char') || lt in ['numeric', 'decimal'])) t = "${col.type}(${col.size})"
        def line = "\t${col.name} ${t}"
        if (col.nullable == 0) line += ' NOT NULL'
        if (col.'default' != null && col.'default'.toString().trim()) line += " DEFAULT ${col.'default'}"
        line
    }
    if (pk.cols) lines << "\tPRIMARY KEY (${pk.cols.join(', ')})"
    def name = schema ? "${schema}.${table}" : table
    "CREATE TABLE ${name} (\n" + lines.join(',\n') + "\n)"
}

//---------- begin function respond
/**
* @describe sends a JSON response with the given HTTP status code
* @param params ex HttpExchange, code integer, json string
* @return void
* @usage
*	respond(ex, 200, wrapOk(result))
*/
void respond(HttpExchange ex, int code, String json) {
    respondAs(ex, code, 'application/json; charset=UTF-8', json)
}

//---------- begin function respondAs
/**
* @describe sends a response with an explicit content type and HTTP status code
* @param params ex HttpExchange, code integer, contentType string, body string
* @return void
* @usage
*	respondAs(ex, 200, 'text/html; charset=UTF-8', html)
*/
void respondAs(HttpExchange ex, int code, String contentType, String body) {
    byte[] b = body.getBytes('UTF-8')
    ex.responseHeaders.set('Content-Type', contentType)
    ex.sendResponseHeaders(code, b.length)
    def os = ex.responseBody
    try { os.write(b) } finally { os.close() }
}

//---------- begin function wrapOk
/**
* @describe wraps a successful result in the { "success": true, "data": ... } envelope (raw JSON strings are embedded as-is, everything else is serialized)
* @param params result mixed
*	result: the driver result (JSON string, list, map, number, etc.)
* @return string
* @usage
*	respond(ex, 200, wrapOk(rows))
*/
String wrapOk(Object result) {
    if (result instanceof String) {
        def t = result.trim()
        if (t.startsWith('[') || t.startsWith('{')) {
            return "{\"success\":true,\"data\":${t}}"
        }
    }
    return JSON.toJson([success: true, data: result])
}

//---------- begin function wrapErr
/**
* @describe wraps an error message in the { "success": false, "error": ... } envelope
* @param params msg string
* @return string
* @usage
*	respond(ex, 500, wrapErr(e.message))
*/
String wrapErr(String msg) {
    return JsonOutput.toJson([success: false, error: msg])
}

//---------- begin function errorCode
/**
* @describe maps an exception to an HTTP status code - 400 for caller errors (bad SQL, missing params), 500 for server/driver errors
* @param params e Exception
* @return integer
* @usage
*	respond(ex, errorCode(e), wrapErr(e.message))
*/
int errorCode(Exception e) {
    (e instanceof IllegalArgumentException
  || e instanceof java.sql.SQLSyntaxErrorException
  || e instanceof UnsupportedOperationException) ? 400 : 500
}

//---------- begin function checkAuth
/**
* @describe checks the X-WaSQL-Token request header against the server token - on failure it has already written a 401 response
* @param params ex HttpExchange
* @return boolean
*	true if authorized, false if a 401 was sent
* @usage
*	if (!checkAuth(ex)) return
*/
boolean checkAuth(HttpExchange ex) {
    if (ex.requestHeaders.getFirst('X-WaSQL-Token') == TOKEN) return true
    respond(ex, 401, wrapErr('Unauthorized'))
    return false
}

//---------- begin function doShutdown
/**
* @describe gracefully shuts the daemon down - deletes the pid/token files, stops the HTTP server and scheduler, then exits the JVM
* @param params reason string
*	reason: reason for shutdown, written to the log
* @return void
* @usage
*	doShutdown('idle timeout')
*/
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

// Write the pid file and register cleanup BEFORE the (slow) module preload so the
// launching process can detect that the JVM is alive during startup. If we waited
// until just before server.start(), a caller polling for liveness would see no pid
// file for the entire compile phase and wrongly conclude the process had crashed.
Runtime.runtime.addShutdownHook(new Thread({ PID_FILE.delete(); TOKEN_FILE.delete() }))
PID_FILE.text = "${PID}\n"

// Log the Groovy and Java versions first thing — makes version-mismatch bugs
// (e.g. a Java 9+ API called on a Java 8 JVM) obvious straight from the log.
log("Groovy ${GroovySystem.version} | Java ${System.getProperty('java.version')} " +
    "(${System.getProperty('java.vm.name')} ${System.getProperty('java.vm.version')}) | " +
    "${System.getProperty('java.home')}")

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
//---------- begin function threadFactory
/**
* @describe thread factory for the request thread pool - names each thread and pins the script's class loader as its context class loader
* @param params r Runnable
* @return object
*	a configured, unstarted handler thread
*/
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

// POST /queryfile/{dbname}  — body is JSON { "query":"...", "filename":"...", optional tuning }
// Streams the result set straight to a CSV file on disk (driver-side) instead of
// returning rows in the response — use for large result sets. The driver's
// queryResults(...) writes the file and returns its path; we echo that back as
// { filename: "..." }. PHP counts the rows from the file it now owns.
server.createContext('/queryfile/') { HttpExchange ex ->
    if (!checkAuth(ex)) return
    lastActivity.set(System.currentTimeMillis())
    try {
        def t0     = System.currentTimeMillis()
        def dbname = pathParam(ex, '/queryfile/')
        def req    = new JsonSlurper().parseText(readBody(ex))
        if (!dbname)       throw new IllegalArgumentException("URL must be /queryfile/{dbname}")
        if (!req.query)    throw new IllegalArgumentException("Body must include 'query'")
        if (!req.filename) throw new IllegalArgumentException("Body must include 'filename'")
        def drv   = resolveDriver(dbname)
        def extra = [filename: req.filename as String]
        if (req.fetchsize  != null) extra.fetchsize  = req.fetchsize as int
        if (req.batchsize  != null) extra.batchsize  = req.batchsize as int
        if (req.skiperrors != null) extra.skiperrors = req.skiperrors as boolean
        def result = drv.driver.queryResults(req.query as String, drv.params + extra)
        respond(ex, 200, wrapOk([filename: result]))
        log("/queryfile ${dbname} → ${result} ${System.currentTimeMillis()-t0}ms")
    } catch (Exception e) {
        log("/queryfile error: ${e.message}")
        try { respond(ex, errorCode(e), wrapErr(e.message)) } catch (Exception ignored) {}
    }
}

// GET /meta/{dbname}?op=tables|columns|indexes|ddl[&table=..&schema=..]
// ODBC-free schema introspection via JDBC DatabaseMetaData. Blank schema = all schemas.
server.createContext('/meta/') { HttpExchange ex ->
    if (!checkAuth(ex)) return
    lastActivity.set(System.currentTimeMillis())
    def sql = null
    try {
        def t0     = System.currentTimeMillis()
        def dbname = pathParam(ex, '/meta/')
        if (!dbname) throw new IllegalArgumentException("URL must be /meta/{dbname}")
        def q      = queryParams(ex)
        def op     = (q.op ?: 'tables').toLowerCase()
        def table  = q.table
        def schema = (q.schema != null && q.schema != '') ? q.schema : null
        def drv    = resolveDriver(dbname)
        sql        = drv.driver.connect(drv.params)
        def md     = sql.connection.metaData
        def result
        switch (op) {
            case 'tables':  result = metaTables(md, schema);                      break
            case 'columns': result = metaColumns(sql.connection, md, schema, table); break
            case 'indexes': result = metaIndexes(md, schema, table);              break
            case 'ddl':     result = metaDDL(sql.connection, md, schema, table);  break
            default: throw new IllegalArgumentException("Unknown meta op: '${op}' (use tables|columns|indexes|ddl)")
        }
        respond(ex, 200, wrapOk(result))
        log("/meta ${dbname} op=${op}${table ? ' table=' + table : ''} — ${System.currentTimeMillis()-t0}ms")
    } catch (Exception e) {
        log("/meta error: ${e.message}")
        try { respond(ex, errorCode(e), wrapErr(e.message)) } catch (Exception ignored) {}
    } finally {
        if (sql != null) try { sql.close() } catch (Exception ignored) {}
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

//---------- begin function shutdownHandler
/**
* @describe HTTP handler for POST/GET /shutdown and its /exit alias - acknowledges the request, then triggers a graceful shutdown on a short delay
* @param params ex HttpExchange
* @return void
*/
def shutdownHandler = { HttpExchange ex ->
    if (!checkAuth(ex)) return
    try { respond(ex, 200, JsonOutput.toJson([status: 'shutting down', pid: PID])) } catch (Exception ignored) {}
    Thread.start { Thread.sleep(200); doShutdown("HTTP ${ex.requestURI.path} request") }
}
server.createContext('/shutdown', shutdownHandler)
server.createContext('/exit',     shutdownHandler)

//---------- begin function buildSpec
/**
* @describe builds the OpenAPI 3.0 specification for the server's endpoints, with the configured database names filled into the dbname path-parameter enums
* @return map
*	the OpenAPI spec, ready to serialize to JSON
* @usage
*	respond(ex, 200, JSON.toJson(buildSpec()))
*/
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
// pid file + shutdown hook were written early (see Initialise). Write the token
// only now, once startup succeeded, so a caller never authenticates against a
// half-initialised server.
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
