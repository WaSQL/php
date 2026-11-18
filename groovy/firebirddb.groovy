/**
Installation
	Download Jaybird (the Firebird JDBC driver, jaybird-x.x.x.jar) from Maven Central:
		https://repo1.maven.org/maven2/org/firebirdsql/jdbc/jaybird/
	and place it in groovy/lib/. (Jaybird 6 needs Java 17+; use Jaybird 5.x on older JVMs.)

database tag attributes
	dbhost:      Firebird server host (default: localhost)
	dbport:      server port (default: 3050)
	dbname:      database path or alias on the server, e.g. d:/data/examples.fdb
	dbuser:      user name (default: SYSDBA)
	dbpass:      password
	dbrole:      SQL role (optional)
	dbcharset:   connection character set (default: UTF8)
	querytimeout: statement timeout in seconds (the Groovy server sets it from the caller's timeout)

References
	https://firebirdsql.org/file/documentation/drivers_documentation/java/faq.html
	https://groovy-lang.org/databases.html
*/

import groovy.sql.Sql
import groovy.json.JsonGenerator
import java.sql.SQLException

//---------- begin function connect
/**
* @describe creates and returns a Firebird connection. Throws IllegalArgumentException when dbname is missing.
* @param params params map
*	dbhost, dbport, dbname, dbuser, dbpass, dbrole, dbcharset
* @return object
*	groovy.sql.Sql connection object
* @usage
*	sql = firebirddb.connect(params)
*/
def connect(Map params) {
	if (!params.dbname) {
		// throw, never System.exit: inside the Groovy server an exit would kill the daemon and every in-flight query
		throw new IllegalArgumentException("Missing dbname attribute in database tag named '${params.name}'")
	}
	def dbhost = params.dbhost ?: 'localhost'
	def dbport = params.dbport ?: '3050'
	def dbname = params.dbname.toString().replace('\\', '/')

	def url = "jdbc:firebirdsql://${dbhost}:${dbport}/${dbname}"
	def props = new java.util.Properties()
	props.setProperty('user', (params.dbuser ?: 'SYSDBA').toString())
	props.setProperty('password', (params.dbpass ?: '').toString())
	props.setProperty('encoding', (params.dbcharset ?: 'UTF8').toString())
	if (params.dbrole) props.setProperty('roleName', params.dbrole.toString())
	// give up connecting instead of hanging a handler thread
	props.setProperty('connectTimeout', '30')

	def sql = binding.hasVariable('wasqlOpenSql') ? binding.getVariable('wasqlOpenSql').call(url, props, 'org.firebirdsql.jdbc.FBDriver', params) : Sql.newInstance(url, props, 'org.firebirdsql.jdbc.FBDriver')

	// Groovy server only: register the connection so Groovy Manager can cancel this request (no-op from the CLI)
	if (binding.hasVariable('wasqlTrackConnection')) { binding.getVariable('wasqlTrackConnection').call(sql.connection) }
	return sql
}

//---------- begin function executeSQL
/**
* @describe executes a SQL statement (INSERT, UPDATE, DELETE, DDL, etc.) and commits it
* @param params query string, params map
* @return boolean
*	true on success (throws on failure)
* @usage
*	ok = firebirddb.executeSQL(query, params)
*/
def executeSQL(String query, Map params = [:]) {
	def sql = null
	try {
		sql = connect(params)
		def qt = params.querytimeout?.toString()?.trim()
		if (qt?.isInteger() && (qt as int) > 0) sql.withStatement { st -> st.queryTimeout = (qt as int) }
		sql.execute(query)
		if (!sql.connection.autoCommit) sql.commit()
		return true
	} finally {
		if (sql != null) sql.close()
	}
}

//---------- begin function executePS
/**
* @describe executes a prepared statement with positional "?" parameters and commits it
* @param params query string, args list, params map
* @return boolean
*	true on success (throws on failure)
* @usage
*	ok = firebirddb.executePS("INSERT INTO users (name, email) VALUES (?, ?)", ['John Doe', 'john@example.com'], params)
*/
def executePS(String query, List args, Map params = [:]) {
	def sql = null
	try {
		sql = connect(params)
		def qt = params.querytimeout?.toString()?.trim()
		if (qt?.isInteger() && (qt as int) > 0) sql.withStatement { st -> st.queryTimeout = (qt as int) }
		sql.executeUpdate(query, args)
		if (!sql.connection.autoCommit) sql.commit()
		return true
	} finally {
		if (sql != null) sql.close()
	}
}

//---------- begin function queryResults
/**
* @describe executes a query and returns the records as maps with lower-case keys
* @param params query string, params map
*	filename: if provided, writes results to a UTF-8 CSV file (with BOM) instead of returning rows
*	format: 'json' (default) or 'list' for a native Groovy list
*	rowsink: Groovy server streaming - each row is appended with rowsink << rec instead of being collected
*	fetchsize: rows fetched per round trip (default 1000, 0 = driver default)
*	skiperrors: if true, a column read error is logged and the value left blank (default: false)
*	batchsize: number of rows to buffer before writing to file (default: 100)
*	notrim: if true, skips trimming whitespace from values (default: false) - note CHAR columns are blank-padded
*	querytimeout: statement timeout in seconds (CSV mode defaults to 600, list mode to none)
* @return mixed
*	JSON string (default), List of Maps if format='list', filename string if filename provided
* @usage
*	json = firebirddb.queryResults(query, params)
*	recs = firebirddb.queryResults(query, params + [format: 'list'])
*	csv  = firebirddb.queryResults(query, params + [filename: 'output.csv'])
*/
def queryResults(String query, Map params = [:]) {
	def sql = null
	def skipErrors = params.getOrDefault('skiperrors', false)
	int fetchRows = (params.getOrDefault('fetchsize', 1000) as int)
	def batchSize = (params.getOrDefault('batchsize', 100) as int)
	def noTrim = params.getOrDefault('notrim', false)

	try {
		sql = connect(params)
		// Statement timeout in seconds. The Groovy server sets it just under the caller's own timeout (X-WaSQL-Timeout).
		def qtRaw = params.querytimeout?.toString()?.trim()
		int queryTimeout = qtRaw?.isInteger() ? (qtRaw as int) : (params.containsKey('filename') ? 600 : 0)
		if (queryTimeout > 0 || fetchRows > 0) {
			sql.withStatement { st ->
				if (queryTimeout > 0) st.queryTimeout = queryTimeout
				if (fetchRows > 0) st.fetchSize = fetchRows
			}
		}

		def cancelled = binding.hasVariable('wasqlCancelled') ? binding.getVariable('wasqlCancelled') : null

		if (params.containsKey('filename')) {
			def writer = null
			try {
				writer = new File(params.filename.toString()).newWriter('UTF-8')
				writer.write('\uFEFF')   // UTF-8 BOM so Excel recognises the encoding

				def stmt = sql.connection.createStatement()
				try {
					if (fetchRows > 0) stmt.setFetchSize(fetchRows)
					if (queryTimeout > 0) stmt.setQueryTimeout(queryTimeout)
					def rs = stmt.executeQuery(query)
					try {
						def rsmd = rs.getMetaData()
						int columnCount = rsmd.getColumnCount()
						def fieldNames = (1..columnCount).collect { rsmd.getColumnLabel(it).toLowerCase() }
						writer.writeLine(fieldNames.collect { escapeCSV(it) }.join(','))

						def batch = new StringBuilder(batchSize * 200)
						int batchCount = 0
						int errorCount = 0
						while (true) {
							if (cancelled != null && cancelled.call()) throw new SQLException('request cancelled')
							if (!rs.next()) break
							def line = new StringBuilder(columnCount * 30)
							for (int i = 1; i <= columnCount; i++) {
								if (i > 1) line.append(',')
								try {
									def value = rs.getObject(i)
									if (value != null) {
										def str = value.toString()
										line.append(escapeCSV(noTrim ? str : str.trim()))
									}
								} catch (Exception e) {
									if (!skipErrors) throw e
									errorCount++
									System.err.println("Warning: Error reading column '${fieldNames[i - 1]}': ${e.message}")
								}
							}
							batch.append(line).append('\n')
							if (++batchCount >= batchSize) {
								writer.write(batch.toString())
								batch.setLength(0)
								batchCount = 0
							}
						}
						if (batchCount > 0) writer.write(batch.toString())
						if (errorCount > 0) System.err.println("Warning: ${errorCount} column value(s) could not be read and were left blank.")
					} finally {
						rs.close()
					}
				} finally {
					stmt.close()
				}
				return params.filename
			} finally {
				if (writer != null) writer.close()
			}
		}

		// list / json mode. params.rowsink (Groovy server) receives each row as it is read, so nothing accumulates.
		def recs = params.rowsink != null ? params.rowsink : []
		def keys = null    // lower-cased column names, worked out once from the result's metadata
		sql.eachRow(query, { meta ->
			int n = meta.columnCount
			keys = new String[n]
			for (int i = 0; i < n; i++) keys[i] = meta.getColumnLabel(i + 1).toLowerCase()
		}, { row ->
			if (cancelled != null && cancelled.call()) throw new SQLException('request cancelled')
			def rec = new LinkedHashMap()
			for (int i = 0; i < keys.length; i++) rec[keys[i]] = row[i]
			recs << rec
		})

		if (params.getOrDefault('format', 'json') == 'json') {
			return new JsonGenerator.Options().disableUnicodeEscaping().build().toJson(recs)
		}
		return recs

	} finally {
		if (sql != null) sql.close()
	}
}

//---------- begin function escapeCSV
/**
* @describe helper function to escape CSV values
* @param params value string
* @return string
*	escaped value
*/
private def escapeCSV(String value) {
	if (value == null) return ''
	if (value.contains(',') || value.contains('"') || value.contains('\n') || value.contains('\r')) {
		return '"' + value.replace('"', '""') + '"'
	}
	return value
}

// Export functions for use as a module
return this
