#!/usr/bin/env groovy
/**
 * ctreesql.groovy - Interactive SQL prompt for cTree (FairCOM) databases
 * Usage: groovy ctreesql.groovy <db_name>
 *   <db_name> must match a 'name' attribute in ../config.xml with dbtype="ctree"
 *
 * Requirements:
 *   ctreeJDBC.jar — place in ./lib/ next to this script (auto-loaded),
 *                   or pass explicitly: groovy -cp lib/ctreeJDBC.jar ctreesql.groovy <db_name>
 */

import groovy.sql.Sql
import groovy.xml.XmlSlurper
import java.lang.management.ManagementFactory
import java.sql.DatabaseMetaData
import java.sql.ResultSet

// ── Suppress Java 17+ restricted-native-access warnings ──────────────────────
// ctreeJDBC calls System.loadLibrary internally; --enable-native-access is a
// startup flag that cannot be set once the JVM is running.  If it is absent,
// re-exec this process with the flag injected before anything else loads.
def jvmFlags = ManagementFactory.getRuntimeMXBean().inputArguments
if (!jvmFlags.any { it.contains('enable-native-access') }) {
    def info = ProcessHandle.current().info()
    def cmd  = info.command().orElse(null)
    def argv = info.arguments().map { it as List }.orElse(null)
    if (cmd && argv != null) {
        System.exit(new ProcessBuilder([cmd, '--enable-native-access=ALL-UNNAMED'] + argv)
            .inheritIO().start().waitFor())
    }
}

// ── Auto-load JDBC driver from ./lib if not already on classpath ───────────────

def loadCtreeDriver = {
    // Already on classpath — nothing to do
    try { Class.forName('ctree.jdbc.ctreeDriver'); return } catch (ClassNotFoundException ignored) {}

    // Search for any ctree*.jar — prefer ./lib (script dir) then fallbacks
    def searchDirs = [
        new File('./lib'),       // groovy/lib/  (canonical location)
        new File('../lib'),      // wasql/lib/
        new File('.'),           // groovy/ itself
    ]

    def jar = searchDirs.findResult { dir ->
        if (!dir.exists() || !dir.isDirectory()) return null
        dir.listFiles()?.find { f ->
            f.name.toLowerCase().contains('ctree') && f.name.endsWith('.jar')
        }
    }

    if (!jar) {
        System.err.println "Error: ctreeJDBC.jar not found."
        System.err.println "  Searched: ${searchDirs.collect { it.canonicalPath }.join(', ')}"
        System.err.println "  Place ctreeJDBC.jar in ./lib/ or run:"
        System.err.println "    groovy -cp lib/ctreeJDBC.jar ctreesql.groovy <db_name>"
        System.exit(1)
    }

    try {
        this.class.classLoader.rootLoader.addURL(jar.toURI().toURL())
        Class.forName('ctree.jdbc.ctreeDriver')   // verify it loaded
        println "Driver loaded from: ${jar.canonicalPath}"
    } catch (Exception e) {
        System.err.println "Error: Failed to load driver from ${jar.canonicalPath}: ${e.message}"
        System.exit(1)
    }
}

loadCtreeDriver()

// ── Constants ─────────────────────────────────────────────────────────────────

final int MAX_COL_WIDTH = 80

// ── Session state ─────────────────────────────────────────────────────────────

def state = [
    outputFmt:  'dos',     // 'dos' (bordered table) or 'csv'
    sql:        null,
    dbParams:   [:],
    promptName: ''
]

// ── Input reader ──────────────────────────────────────────────────────────────

def stdinReader = new BufferedReader(new InputStreamReader(System.in))

def readLine = { String prompt ->
    System.out.print(prompt)
    System.out.flush()
    stdinReader.readLine()
}

// ── Config loading ─────────────────────────────────────────────────────────────

def findConfigFile = {
    [new File('../config.xml'), new File('c:/wasql/config.xml')]
        .find { it.canonicalFile.exists() }
        ?.canonicalFile
}

def parseDbNode = { node ->
    [
        name:        node.@name.text(),
        displayname: node.@displayname.text(),
        dbhost:      node.@dbhost.text(),
        dbuser:      node.@dbuser.text(),
        dbpass:      node.@dbpass.text(),
        dbname:      node.@dbname.text(),
        dbport:      node.@dbport.text() ?: '6597',
        connect:     node.@connect.text() ?: null,
        dbtype:      node.@dbtype.text()
    ]
}

def loadConfig = { String dbName ->
    def configFile = findConfigFile()
    if (!configFile) { println "Error: config.xml not found"; return null }
    def xml = new XmlSlurper().parse(configFile)
    def found = null
    xml.database.each { db -> if (db.@name.text() == dbName) found = parseDbNode(db) }
    found
}

def loadAllCtreeDbs = {
    def configFile = findConfigFile()
    if (!configFile) return []
    def xml = new XmlSlurper().parse(configFile)
    def result = []
    xml.database.each { db -> if (db.@dbtype.text() == 'ctree') result << parseDbNode(db) }
    result
}

// ── Connection ─────────────────────────────────────────────────────────────────

def buildJdbcUrl = { Map params ->
    def conn = params.connect
    if (conn) {
        // Parse ODBC-style connection string into JDBC URL
        if (conn.contains(';') && conn.contains('=')) {
            def o = [:]
            conn.split(';').each { part ->
                def kv = part.split('=', 2)
                if (kv.size() == 2)
                    o[kv[0].trim().toLowerCase()] = kv[1].trim().replaceAll(/^\{|\}$/, '')
            }
            def server = o['host'] ?: o['server'] ?: 'localhost'
            def port   = o['port'] ?: '6597'
            def db     = o['database'] ?: ''
            return "jdbc:ctree://${server}:${port}" + (db ? "/${db}" : '')
        }
        if (conn.startsWith('jdbc:ctree://')) return conn
        return "jdbc:ctree://${conn}:6597"
    }
    def host = params.dbhost ?: 'localhost'
    def port = params.dbport ?: '6597'
    def db   = params.dbname ?: ''
    "jdbc:ctree://${host}:${port}" + (db ? "/${db}" : '')
}

def connectDb = { Map params ->
    def url  = buildJdbcUrl(params)
    def user = params.dbuser ?: ''
    def pass = params.dbpass ?: ''
    try {
        return Sql.newInstance(url, user, pass, 'ctree.jdbc.ctreeDriver')
    } catch (Exception e) {
        println "Connection error: ${e.message}"
        return null
    }
}

// ── Output formatting ─────────────────────────────────────────────────────────

def escapeCSV = { String v ->
    if (!v) return ''
    (v.contains(',') || v.contains('"') || v.contains('\n') || v.contains('\r'))
        ? '"' + v.replace('"', '""') + '"'
        : v
}

def cleanRows = { List columns, List rows ->
    def numeric = ([true] * columns.size()) as List<Boolean>
    def strRows = rows.collect { row ->
        row.withIndex().collect { val, i ->
            String s
            if (val == null) {
                s = ''; numeric[i] = false
            } else if (val instanceof Number) {
                s = val.toString()
            } else {
                s = val.toString().trim(); numeric[i] = false
            }
            s.length() > MAX_COL_WIDTH ? s[0..(MAX_COL_WIDTH - 4)] + '...' : s
        }
    }
    [strRows, numeric]
}

def printTable = { List columns, List rows, Map st ->
    if (!columns) return
    def (strRows, numeric) = cleanRows(columns, rows)

    if (st.outputFmt == 'csv') {
        def esc = escapeCSV
        println columns.collect { esc(it.toString()) }.join(',')
        strRows.each { row -> println row.collect { esc(it) }.join(',') }
        return
    }

    // DOS bordered table
    def widths = columns.collect { it.toString().length() }
    strRows.each { row ->
        row.eachWithIndex { s, i -> widths[i] = Math.max(widths[i], s.length()) }
    }
    def sep    = '+' + widths.collect { '-' * (it + 2) }.join('+') + '+'
    def header = '|' + columns.withIndex().collect { c, i ->
        " ${c.toString().padRight(widths[i])} "
    }.join('|') + '|'

    println sep
    println header
    println sep
    strRows.each { row ->
        println '|' + row.withIndex().collect { v, i ->
            numeric[i] ? " ${v.padLeft(widths[i])} " : " ${v.padRight(widths[i])} "
        }.join('|') + '|'
    }
    println sep
}

// ── Query execution ───────────────────────────────────────────────────────────

def executeQuery = { Sql sql, String query, Map st ->
    query = query?.trim()
    if (!query) return
    def stmt = null
    try {
        stmt = sql.connection.createStatement()
        boolean hasResults = stmt.execute(query)
        if (hasResults) {
            def rs = stmt.resultSet
            def cols, rows = []
            try {
                def meta = rs.metaData
                cols = (1..meta.columnCount).collect { meta.getColumnName(it) }
                while (rs.next()) rows << (1..meta.columnCount).collect { rs.getObject(it) }
            } finally {
                rs.close()
            }
            printTable(cols, rows, st)
            println "(${rows.size()} row${rows.size() == 1 ? '' : 's'})"
        } else {
            def count = stmt.updateCount
            println count < 0 ? "OK" : "${count} row(s) affected"
        }
    } catch (Exception e) {
        println "Error: ${e.message}"
    } finally {
        stmt?.close()
    }
}

// ── Backslash command handlers ────────────────────────────────────────────────

def formatBytes = { long n ->
    if (n >= 1024L ** 3) return String.format('%.2f GB', n / (1024.0 ** 3))
    if (n >= 1024L ** 2) return String.format('%.1f MB', n / (1024.0 ** 2))
    if (n >= 1024L)      return String.format('%.1f KB', n / 1024.0)
    return "${n} B"
}

def cmdStats = { Sql sql, String proc, Map st ->
    def stmt = null
    try {
        stmt = sql.connection.createStatement()
        if (!stmt.execute("call ${proc}()")) {
            println "No results returned."; return
        }
        def rows = []
        def rs = stmt.resultSet
        try {
            while (rs.next()) {
                def desc = rs.getObject(1)?.toString()?.trim() ?: ''
                def raw  = rs.getObject(2)
                String valStr, pretty = ''
                try {
                    long n = Long.parseLong(raw?.toString()?.trim() ?: '0')
                    valStr = String.format('%,d', n)
                    if (n >= 1024L * 1024L) pretty = formatBytes(n)
                } catch (ignored) {
                    valStr = raw?.toString()?.trim() ?: ''
                }
                rows << [desc, valStr, pretty]
            }
        } finally {
            rs.close()
        }
        if (!rows) { println "No results returned."; return }
        printTable(['Description', 'Value', 'Size'], rows, st)
    } catch (Exception e) {
        println "Error: ${e.message}"
    } finally {
        stmt?.close()
    }
}

def cmdListTables = { Sql sql, String schema, Map st ->
    try {
        def meta = sql.connection.metaData
        def rs   = meta.getTables(null, schema ?: null, '%', ['TABLE'] as String[])
        def rows = []
        try {
            while (rs.next())
                rows << [rs.getString('TABLE_SCHEM'), rs.getString('TABLE_NAME'), rs.getString('TABLE_TYPE')]
        } finally {
            rs.close()
        }
        if (!rows) { println "No tables found."; return }
        printTable(['schema', 'table', 'type'], rows, st)
        println "(${rows.size()} row${rows.size() == 1 ? '' : 's'})"
    } catch (Exception e) {
        println "Error: ${e.message}"
    }
}

def cmdDescribe = { Sql sql, String tableArg, Map st ->
    if (!tableArg?.trim()) { println "Usage: \\d [schema.]<table_name>"; return }
    def parts  = tableArg.trim().split('\\.', 2)
    def schema = parts.size() > 1 ? parts[0] : null
    def table  = parts.size() > 1 ? parts[1] : parts[0]
    try {
        def meta = sql.connection.metaData
        def rs   = meta.getColumns(null, schema, table, '%')
        def rows = []
        try {
            while (rs.next()) {
                rows << [
                    rs.getString('COLUMN_NAME'),
                    rs.getString('TYPE_NAME'),
                    rs.getInt('COLUMN_SIZE').toString(),
                    rs.getInt('NULLABLE') == DatabaseMetaData.columnNullable ? 'YES' : 'NO',
                    rs.getString('COLUMN_DEF') ?: ''
                ]
            }
        } finally {
            rs.close()
        }
        if (!rows) { println "Table not found or no columns returned."; return }
        printTable(['column', 'type', 'size', 'nullable', 'default'], rows, st)
        println "(${rows.size()} column${rows.size() == 1 ? '' : 's'})"
    } catch (Exception e) {
        println "Error: ${e.message}"
    }
}

def cmdExecFile = { Sql sql, String filepath, Map st ->
    filepath = filepath.trim().replaceAll(/^['"]|['"]$/, '')
    def f = new File(filepath)
    if (!f.exists()) { println "File not found: ${filepath}"; return }
    def stmts = f.getText('UTF-8').split(';').collect { it.trim() }.findAll { it }
    stmts.each { stmt ->
        println "-- ${stmt.take(80)}"
        executeQuery(sql, stmt, st)
    }
}

def cmdNames = { Map st ->
    def dbs = loadAllCtreeDbs()
    if (!dbs) { println "No cTree databases found in config.xml."; return }
    def rows = dbs.collect { [it.name, it.displayname, it.dbhost, it.dbuser, it.dbport] }
    printTable(['name', 'displayname', 'dbhost', 'dbuser', 'dbport'], rows, st)
    println "(${rows.size()} row${rows.size() == 1 ? '' : 's'})"
}

// ── Help text ─────────────────────────────────────────────────────────────────

def HELP_TEXT = """
Backslash commands:
  \\q, \\quit, exit    Quit ctreesql
  \\dt [schema]        List tables (optional schema filter)
  \\d  [schema.]table  Describe table columns
  \\i  <file>          Execute SQL from a file
  \\c                  Show connection info
  \\g                  Execute buffer (no trailing semicolon needed)

  -- Session --
  \\name=<db>          Reconnect to a different database from config.xml
  \\output=dos|csv     Set output format (default: dos)

  -- Server info --
  \\v                  Server version    (fc_get_server_version)
  \\u                  User list         (fc_get_userlist)
  \\db                 Database list     (fc_get_dblist)
  \\procs              Built-in proc list (fc_get_fcproclist)
  \\names              cTree entries in config.xml

  -- Stats (numeric values auto-formatted as KB/MB/GB) --
  \\s                  Connection stats  (fc_get_connstats)
  \\m                  Memory stats      (fc_get_memstats)
  \\io                 I/O stats         (fc_get_iostats)
  \\ca                 Cache stats       (fc_get_cachestats)
  \\lk                 Lock stats        (fc_get_lockstats)
  \\tx                 Transaction stats (fc_get_transtats)
  \\sq                 SQL perf stats    (fc_get_sqlstats)
  \\is                 ISAM engine stats (fc_get_isamstats)
  \\rp                 Replication stats (fc_get_replstats)

  \\?  or \\h           Show this help

SQL is executed when the statement ends with a semicolon (;).
Use \\g on its own line to execute without a trailing semicolon.
"""

// Hard exit — skips JDBC finalizers that hang on stale idle connections.
// Runtime.halt() is the JVM equivalent of os._exit(): no shutdown hooks,
// no GC, no JDBC driver disconnect attempt.
def quitClean = {
    Runtime.runtime.halt(0)
}

// ── Meta command handler ──────────────────────────────────────────────────────
// Returns null normally, or a Map {sql, dbParams, promptName} on reconnect.

def handleMetaCommand = { String line, Map st ->
    line = line.trim()
    if (!line.startsWith('\\')) return null

    def rest  = line.substring(1)
    def parts = rest.split(/\s+/, 2)
    def cmd   = parts[0].toLowerCase()
    def arg   = parts.size() > 1 ? parts[1] : ''

    // Support \cmd=value syntax
    if (cmd.contains('=')) {
        def idx = cmd.indexOf('=')
        arg = cmd.substring(idx + 1)
        cmd = cmd.substring(0, idx)
    }

    // ── Session settings ──────────────────────────────────────────────────────
    if (cmd == 'name') {
        def newName = arg.trim()
        if (!newName) {
            println "Usage: \\name=<db_name>  (see \\names for available connections)"
            return null
        }
        def newParams = loadConfig(newName)
        if (!newParams) { println "Error: '${newName}' not found in config.xml"; return null }
        // Skip closing old connection — it may be stale and close() would hang
        def newSql = connectDb(newParams)
        if (!newSql) return null
        println "Connected to '${newName}' (${newParams.dbhost})"
        return [sql: newSql, dbParams: newParams, promptName: newParams.dbname ?: newName]
    }

    if (cmd == 'output') {
        def fmt = arg.trim().toLowerCase()
        if (!['dos', 'csv'].contains(fmt)) {
            println "Usage: \\output=dos|csv  (current: ${st.outputFmt})"
            return null
        }
        st.outputFmt = fmt
        println "Output format set to '${fmt}'."
        return null
    }

    switch (cmd) {
        case ['q', 'quit']:
            println "Bye"; quitClean(); break
        case 'dt':
            cmdListTables(st.sql, arg.trim() ?: null, st); break
        case 'd':
            cmdDescribe(st.sql, arg, st); break
        case 'i':
            cmdExecFile(st.sql, arg, st); break
        case 'c':
            println "Database : ${st.dbParams.dbname ?: ''}"
            println "Host     : ${st.dbParams.dbhost ?: ''}"
            println "Port     : ${st.dbParams.dbport ?: ''}"
            println "User     : ${st.dbParams.dbuser ?: ''}"
            println "Name     : ${st.dbParams.name ?: ''}"
            break
        // Stats (Description/Value with byte formatting)
        case 's':     cmdStats(st.sql, 'fc_get_connstats',  st); break
        case 'm':     cmdStats(st.sql, 'fc_get_memstats',   st); break
        case 'io':    cmdStats(st.sql, 'fc_get_iostats',    st); break
        case 'ca':    cmdStats(st.sql, 'fc_get_cachestats', st); break
        case 'lk':    cmdStats(st.sql, 'fc_get_lockstats',  st); break
        case 'tx':    cmdStats(st.sql, 'fc_get_transtats',  st); break
        case 'sq':    cmdStats(st.sql, 'fc_get_sqlstats',   st); break
        case 'is':    cmdStats(st.sql, 'fc_get_isamstats',  st); break
        case 'rp':    cmdStats(st.sql, 'fc_get_replstats',  st); break
        // Admin / info
        case 'names': cmdNames(st); break
        case 'v':     executeQuery(st.sql, 'call fc_get_server_version()', st); break
        case 'u':     executeQuery(st.sql, 'call fc_get_userlist()',        st); break
        case 'db':    executeQuery(st.sql, 'call fc_get_dblist()',          st); break
        case 'procs': executeQuery(st.sql, 'call fc_get_fcproclist()',      st); break
        case ['?', 'h']: println HELP_TEXT; break
        default: println "Unknown command: \\${cmd}  (try \\?)"
    }
    return null
}

// ── REPL ──────────────────────────────────────────────────────────────────────

def repl = { Map st ->
    def buffer = []

    while (true) {
        def promptMain = "${st.promptName}=# "
        def promptCont = ' ' * st.promptName.length() + '-> '
        def prompt = buffer.isEmpty() ? promptMain : promptCont

        def line
        try {
            line = readLine(prompt)
        } catch (Exception e) {
            println "\nBye"; quitClean()
        }
        if (line == null) { println "\nBye"; quitClean() }

        def stripped = line.trim()

        // Exit shortcuts (no backslash)
        if (buffer.isEmpty() && stripped.toLowerCase() in ['exit', 'quit', '\\q']) {
            println "Bye"; quitClean()
        }

        // \g: execute buffer without trailing semicolon (must check before general \ handler)
        if (stripped == '\\g') {
            if (!buffer.isEmpty()) {
                executeQuery(st.sql, buffer.join(' '), st)
                buffer.clear()
            }
            continue
        }

        // All other backslash commands
        if (stripped.startsWith('\\')) {
            if (!buffer.isEmpty()) {
                println "Warning: discarding pending buffer: ${buffer.join(' ').take(60)}"
                buffer.clear()
            }
            def result = handleMetaCommand(stripped, st)
            if (result instanceof Map) {
                // Reconnect: swap in new connection, update prompts on next iteration
                st.sql        = result.sql
                st.dbParams   = result.dbParams
                st.promptName = result.promptName
            }
            continue
        }

        // Accumulate SQL lines
        buffer << line
        def combined = buffer.join(' ').stripTrailing()
        if (combined.endsWith(';')) {
            def sqlStr = combined[0..(combined.length() - 2)].trim()
            if (sqlStr) executeQuery(st.sql, sqlStr, st)
            buffer.clear()
        }
    }
}

// ── Entry point ───────────────────────────────────────────────────────────────

def usage = {
    println "Usage: groovy ctreesql.groovy <db_name>"
    println ""
    println "  <db_name>  Name attribute from ../config.xml (dbtype must be 'ctree')"
    println ""
    println "Available cTree databases in config.xml:"
    loadAllCtreeDbs().each { db ->
        println "  ${db.name.padRight(40)}  ${db.displayname}"
    }
    System.exit(1)
}

if (!args || args[0] in ['-h', '--help']) { usage() }

def dbName   = args[0]
def dbParams = loadConfig(dbName)

if (!dbParams) {
    println "Error: '${dbName}' not found in config.xml"
    println ""
    usage()
}

if (dbParams.dbtype != 'ctree') {
    println "Warning: '${dbName}' has dbtype='${dbParams.dbtype}', not 'ctree'. Proceeding anyway."
}

println "ctreesql - connecting to '${dbName}' (${dbParams.displayname ?: dbParams.dbhost})"

def sqlConn = connectDb(dbParams)
if (!sqlConn) System.exit(1)

state.sql        = sqlConn
state.dbParams   = dbParams
state.promptName = dbParams.dbname ?: dbName

println "Connected. Type \\? for help, \\q to quit."
println ""

repl(state)
