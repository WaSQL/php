import groovy.json.JsonSlurper
import groovy.json.JsonOutput
import groovy.transform.Field
import java.sql.*

// ── Config from environment ──────────────────────────────────────────────────
@Field String jdbcUrl   = System.getenv('CTREE_JDBC_URL')            ?: 'jdbc:ctree://localhost:6597/liveSQL'
@Field String jdbcUser  = System.getenv('CTREE_USER')                ?: ''
@Field String jdbcPass  = System.getenv('CTREE_PASSWORD')            ?: ''
@Field int    maxRows   = (System.getenv('CTREE_MAX_ROWS') ?: '1000').toInteger()
@Field String instrFile = System.getenv('CTREE_INSTRUCTIONS_FILE')

@Field String instructions = ''
if (instrFile) {
    def f = new File(instrFile)
    if (f.exists()) instructions = f.text
}

// ── JDBC connection ──────────────────────────────────────────────────────────
Class.forName('ctree.jdbc.ctreeDriver')
@Field Connection conn = null

Connection getConn() {
    try {
        if (conn != null && !conn.isClosed()) return conn
    } catch (ignored) {}
    conn = DriverManager.getConnection(jdbcUrl, jdbcUser, jdbcPass)
    return conn
}

def schemaTable(String name) {
    if (name?.contains('.')) {
        def parts = name.split('\\.', 2)
        return [parts[0], parts[1]]
    }
    return [null, name]
}

// ── Tool implementations ─────────────────────────────────────────────────────
def toolTestConnection() {
    def c = getConn()
    def m = c.getMetaData()
    """\
Connection successful

| Property | Value |
| --- | --- |
| dbms_name | ${m.getDatabaseProductName()} |
| dbms_version | ${m.getDatabaseProductVersion()} |
| driver_name | ${m.getDriverName()} |
| driver_version | ${m.getDriverVersion()} |
| url | ${jdbcUrl} |"""
}

def toolListConnections() {
    "Connections:\n- ctree (default)\n  URL: ${jdbcUrl}\n  User: ${jdbcUser}\n  Max rows: ${maxRows}"
}

def toolListDsns() {
    "JDBC URL in use: ${jdbcUrl}"
}

def toolListTables(String schema) {
    def c = getConn()
    def rs = c.getMetaData().getTables(null, schema ?: null, '%', ['TABLE'] as String[])
    def tables = []
    while (rs.next()) {
        def s = rs.getString('TABLE_SCHEM')
        def t = rs.getString('TABLE_NAME')
        tables << (s ? "${s}.${t}" : t)
    }
    rs.close()
    tables.isEmpty() ? 'No tables found' : "Tables (${tables.size()}):\n" + tables.sort().join('\n')
}

def toolDescribeTable(String tableName) {
    def (schema, table) = schemaTable(tableName)
    def c = getConn()
    def rs = c.getMetaData().getColumns(null, schema, table, '%')
    def rows = []
    while (rs.next()) {
        rows << [
            name    : rs.getString('COLUMN_NAME'),
            type    : rs.getString('TYPE_NAME'),
            size    : rs.getInt('COLUMN_SIZE'),
            nullable: rs.getString('IS_NULLABLE')
        ]
    }
    rs.close()
    if (rows.isEmpty()) return "Table '${tableName}' not found or has no columns"
    def sb = new StringBuilder("Columns for ${tableName}:\n\n")
    sb.append(String.format("%-35s %-15s %-8s %s%n", 'Column', 'Type', 'Size', 'Nullable'))
    sb.append('-' * 70 + '\n')
    rows.each { r -> sb.append(String.format("%-35s %-15s %-8d %s%n", r.name, r.type, r.size, r.nullable)) }
    sb.toString()
}

def toolGetPrimaryKeys(String tableName) {
    def (schema, table) = schemaTable(tableName)
    def c = getConn()
    def rs = c.getMetaData().getPrimaryKeys(null, schema, table)
    def keys = []
    while (rs.next()) keys << rs.getString('COLUMN_NAME')
    rs.close()
    keys.isEmpty() ? "No primary keys found for ${tableName}" : "Primary keys for ${tableName}:\n" + keys.join('\n')
}

def toolGetForeignKeys(String tableName) {
    def (schema, table) = schemaTable(tableName)
    def c = getConn()
    def rs = c.getMetaData().getImportedKeys(null, schema, table)
    def keys = []
    while (rs.next()) {
        keys << "${rs.getString('FKCOLUMN_NAME')} -> ${rs.getString('PKTABLE_SCHEM')}.${rs.getString('PKTABLE_NAME')}.${rs.getString('PKCOLUMN_NAME')}"
    }
    rs.close()
    keys.isEmpty() ? "No foreign keys found for ${tableName}" : "Foreign keys for ${tableName}:\n" + keys.join('\n')
}

def toolExecuteQuery(String query, int qMax) {
    def trimmed = query?.trim()?.toUpperCase() ?: ''
    if (!trimmed.startsWith('SELECT') && !trimmed.startsWith('WITH')) {
        return 'Error: Only SELECT (and WITH) queries are allowed'
    }
    def c = getConn()
    def stmt = c.createStatement()
    stmt.setMaxRows(qMax)
    def rs = stmt.executeQuery(query)
    def meta = rs.getMetaData()
    int colCount = meta.getColumnCount()
    def cols = (1..colCount).collect { meta.getColumnName(it) }

    def rows = []
    while (rs.next()) {
        def row = [:]
        cols.eachWithIndex { col, i -> row[col] = rs.getObject(i + 1)?.toString()?.trim() ?: '' }
        rows << row
    }
    rs.close()
    stmt.close()

    if (rows.isEmpty()) return 'Query returned 0 rows'

    // Markdown table
    def sb = new StringBuilder()
    sb.append("| ${cols.join(' | ')} |\n")
    sb.append("| ${cols.collect { '-' * [it.length(), 3].max() }.join(' | ')} |\n")
    rows.each { row -> sb.append("| ${cols.collect { (row[it] ?: '').replace('|', '\\|') }.join(' | ')} |\n") }
    sb.append("\n_${rows.size()} row(s) returned_")
    sb.toString()
}

def handleToolCall(String name, Map args) {
    switch (name) {
        case 'execute_query':
            int qMax = args.max_rows ? Math.min(args.max_rows.toInteger(), maxRows) : maxRows
            return toolExecuteQuery(args.query as String, qMax)
        case 'list_tables':
            return toolListTables(args.schema as String)
        case 'describe_table':
            return toolDescribeTable(args.table_name as String)
        case 'get_primary_keys':
            return toolGetPrimaryKeys(args.table_name as String)
        case 'get_foreign_keys':
            return toolGetForeignKeys(args.table_name as String)
        case 'test_connection':
            return toolTestConnection()
        case 'list_connections':
            return toolListConnections()
        case 'list_dsns':
            return toolListDsns()
        default:
            throw new Exception("Unknown tool: ${name}")
    }
}

// ── Tool schema definitions ──────────────────────────────────────────────────
def tools = [
    [name: 'execute_query',
     description: 'Execute a read-only SQL SELECT query against the FairCom cTree database via JDBC. Only SELECT and WITH (CTE) are allowed.',
     inputSchema: [type: 'object',
        properties: [
            query:    [type: 'string',  description: 'SQL SELECT query to execute'],
            max_rows: [type: 'integer', description: "Max rows to return (default ${maxRows}, hard cap ${maxRows})"]
        ], required: ['query']]],
    [name: 'list_tables',
     description: 'List tables in the cTree database',
     inputSchema: [type: 'object',
        properties: [schema: [type: 'string', description: 'Schema to filter by (e.g. admin)']]]],
    [name: 'describe_table',
     description: 'Describe the columns of a table',
     inputSchema: [type: 'object',
        properties: [table_name: [type: 'string', description: 'Table name, optionally schema-qualified (e.g. admin.dstdb)']],
        required: ['table_name']]],
    [name: 'get_primary_keys',
     description: 'Get primary key columns for a table',
     inputSchema: [type: 'object',
        properties: [table_name: [type: 'string', description: 'Table name (optionally schema-qualified)']],
        required: ['table_name']]],
    [name: 'get_foreign_keys',
     description: 'Get foreign key relationships for a table',
     inputSchema: [type: 'object',
        properties: [table_name: [type: 'string', description: 'Table name (optionally schema-qualified)']],
        required: ['table_name']]],
    [name: 'test_connection',
     description: 'Test the JDBC connection to the cTree server',
     inputSchema: [type: 'object', properties: [:]]],
    [name: 'list_connections',
     description: 'List configured JDBC connections',
     inputSchema: [type: 'object', properties: [:]]],
    [name: 'list_dsns',
     description: 'Show the JDBC URL in use',
     inputSchema: [type: 'object', properties: [:]]]
]

// ── MCP stdio protocol loop ──────────────────────────────────────────────────
def slurper  = new JsonSlurper()
def stdout   = new PrintStream(System.out, true, 'UTF-8')

def send = { Map msg ->
    stdout.println(JsonOutput.toJson(msg))
    stdout.flush()
}

System.err.println('[ctree-jdbc] MCP server started. URL: ' + jdbcUrl)

System.in.withReader('UTF-8') { reader ->
    reader.eachLine { line ->
        line = line.trim()
        if (!line) return
        try {
            def msg    = slurper.parseText(line)
            def id     = msg.id
            def method = msg.method as String

            switch (method) {
                case 'initialize':
                    send([jsonrpc: '2.0', id: id, result: [
                        protocolVersion: '2024-11-05',
                        capabilities: [tools: [:]],
                        serverInfo: [name: 'ctree-jdbc', version: '1.0.0'],
                        instructions: instructions ?: null
                    ]])
                    break

                case 'notifications/initialized':
                case 'initialized':
                    break  // notification — no response

                case 'tools/list':
                    send([jsonrpc: '2.0', id: id, result: [tools: tools]])
                    break

                case 'tools/call':
                    def toolName = msg.params?.name as String
                    def toolArgs = (msg.params?.arguments ?: [:]) as Map
                    try {
                        def result = handleToolCall(toolName, toolArgs)
                        send([jsonrpc: '2.0', id: id, result: [
                            content: [[type: 'text', text: result]]
                        ]])
                    } catch (e) {
                        send([jsonrpc: '2.0', id: id, result: [
                            content: [[type: 'text', text: "Error: ${e.message}"]],
                            isError: true
                        ]])
                    }
                    break

                case 'ping':
                    send([jsonrpc: '2.0', id: id, result: [:]])
                    break

                default:
                    if (id != null) {
                        send([jsonrpc: '2.0', id: id, error: [code: -32601, message: "Method not found: ${method}"]])
                    }
            }
        } catch (e) {
            System.err.println('[ctree-jdbc] Error: ' + e.message)
        }
    }
}
