/**
Installation
	Download the Snowflake JDBC driver (snowflake-jdbc-x.x.x.jar) from Maven Central:
		https://repo1.maven.org/maven2/net/snowflake/snowflake-jdbc/
	and place it in groovy/lib/. (Already present: snowflake-jdbc-3.27.1.jar)

database tag attributes (same names the PHP snowflake driver reads from config.xml)
	dbaccount:   account identifier, e.g. xy12345.us-east-1 (or give dbhost instead)
	dbhost:      <account>[.snowflakecomputing.com] - used when dbaccount is not set
	dbuser:      user name
	dbpass:      password (not needed when dbcert is set)
	dbcert:      path to the private key file for key-pair (JWT) authentication
	dbcertpass:  passphrase of that private key (optional)
	dbname:      database
	dbschema:    schema (optional)
	dbwarehouse: warehouse (optional; without it the user needs a DEFAULT_WAREHOUSE)
	dbrole:      role (optional)
	querytimeout: statement timeout in seconds (the Groovy server sets it from the caller's timeout)

References
	https://docs.snowflake.com/en/developer-guide/jdbc/jdbc-configure
	https://groovy-lang.org/databases.html
*/

import groovy.sql.Sql
import groovy.json.JsonGenerator
import java.sql.SQLException

//---------- begin function connect
/**
* @describe creates and returns a Snowflake connection. Password auth by default; key-pair (JWT) auth when dbcert is set.
*	Throws IllegalArgumentException when no account/host or user is configured.
* @param params params map
*	dbaccount|dbhost, dbuser, dbpass, dbcert, dbcertpass, dbname, dbschema, dbwarehouse, dbrole
* @return object
*	groovy.sql.Sql connection object
* @usage
*	sql = snowflakedb.connect(params)
*/
def connect(Map params) {
	def account = params.dbaccount ?: ''
	if (!account && params.dbhost) {
		account = params.dbhost.toString().replaceAll(/(?i)\.snowflakecomputing\.com.*/, '')
	}
	if (!account) {
		// throw, never System.exit: inside the Groovy server an exit would kill the daemon and every in-flight query
		throw new IllegalArgumentException("Missing dbaccount or dbhost attribute in database tag named '${params.name}'")
	}
	if (!params.dbuser) {
		throw new IllegalArgumentException("Missing dbuser attribute in database tag named '${params.name}'")
	}

	// a full host name (e.g. xy12345.privatelink.snowflakecomputing.com) is used as given
	def host = account.toString().toLowerCase().contains('snowflakecomputing.com') ? account : "${account}.snowflakecomputing.com"
	def url = "jdbc:snowflake://${host}/"

	def props = new java.util.Properties()
	props.setProperty('user', params.dbuser.toString())
	if (params.dbcert) {
		def keyFile = new File(params.dbcert.toString())
		if (!keyFile.isFile()) {
			throw new IllegalArgumentException("dbcert private key file not found: ${keyFile.path}")
		}
		props.setProperty('private_key_file', keyFile.path)
		if (params.dbcertpass) props.setProperty('private_key_file_pwd', params.dbcertpass.toString())
	} else {
		props.setProperty('password', (params.dbpass ?: '').toString())
	}
	if (params.dbname)      props.setProperty('db',        params.dbname.toString())
	if (params.dbschema)    props.setProperty('schema',    params.dbschema.toString())
	if (params.dbwarehouse) props.setProperty('warehouse', params.dbwarehouse.toString())
	if (params.dbrole)      props.setProperty('role',      params.dbrole.toString())
	// The default Arrow result format needs --add-opens=java.base/java.nio=ALL-UNNAMED on Java 16+ and otherwise fails with
	// "Failed to initialize MemoryUtil". JSON works on every JVM; override with the snowflake_result_format attribute.
	props.setProperty('JDBC_QUERY_RESULT_FORMAT', (params.snowflake_result_format ?: 'JSON').toString())
	// give up connecting instead of hanging a handler thread
	props.setProperty('loginTimeout', '30')

	def sql = binding.hasVariable('wasqlOpenSql') ? binding.getVariable('wasqlOpenSql').call(url, props, 'net.snowflake.client.jdbc.SnowflakeDriver', params) : Sql.newInstance(url, props, 'net.snowflake.client.jdbc.SnowflakeDriver')

	// Groovy server only: register the connection so Groovy Manager can cancel this request (no-op from the CLI)
	if (binding.hasVariable('wasqlTrackConnection')) { binding.getVariable('wasqlTrackConnection').call(sql.connection) }
	return sql
}

//---------- begin function executeSQL
/**
* @describe executes a SQL statement (INSERT, UPDATE, DELETE, DDL, etc.)
* @param params query string, params map
* @return boolean
*	true on success (throws on failure)
* @usage
*	ok = snowflakedb.executeSQL(query, params)
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
* @describe executes a prepared statement with positional "?" parameters
* @param params query string, args list, params map
* @return boolean
*	true on success (throws on failure)
* @usage
*	ok = snowflakedb.executePS("INSERT INTO users (name, email) VALUES (?, ?)", ['John Doe', 'john@example.com'], params)
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
*	skiperrors: if true, a column/row read error is logged and skipped (default: false)
*	batchsize: number of rows to buffer before writing to file (default: 100)
*	notrim: if true, skips trimming whitespace from values (default: false)
*	querytimeout: statement timeout in seconds (CSV mode defaults to 600, list mode to none)
*	fetchsize: accepted for compatibility - the Snowflake driver streams result chunks itself
* @return mixed
*	JSON string (default), List of Maps if format='list', filename string if filename provided
* @usage
*	json = snowflakedb.queryResults(query, params)
*	recs = snowflakedb.queryResults(query, params + [format: 'list'])
*	csv  = snowflakedb.queryResults(query, params + [filename: 'output.csv'])
*/
def queryResults(String query, Map params = [:]) {
	def sql = null
	def skipErrors = params.getOrDefault('skiperrors', false)
	def batchSize = (params.getOrDefault('batchsize', 100) as int)
	def noTrim = params.getOrDefault('notrim', false)

	try {
		sql = connect(params)
		// Statement timeout in seconds. The Groovy server sets it just under the caller's own timeout (X-WaSQL-Timeout).
		def qtRaw = params.querytimeout?.toString()?.trim()
		int queryTimeout = qtRaw?.isInteger() ? (qtRaw as int) : (params.containsKey('filename') ? 600 : 0)
		if (queryTimeout > 0) sql.withStatement { st -> st.queryTimeout = queryTimeout }

		def cancelled = binding.hasVariable('wasqlCancelled') ? binding.getVariable('wasqlCancelled') : null

		if (params.containsKey('filename')) {
			def writer = null
			try {
				writer = new File(params.filename.toString()).newWriter('UTF-8')
				writer.write('﻿')   // UTF-8 BOM so Excel recognises the encoding

				def stmt = sql.connection.createStatement()
				try {
					if (queryTimeout > 0) stmt.setQueryTimeout(queryTimeout)
					def rs = stmt.executeQuery(query)
					try {
						def rsmd = rs.getMetaData()
						int columnCount = rsmd.getColumnCount()
						def fieldNames = (1..columnCount).collect { rsmd.getColumnName(it).toLowerCase() }
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
		def keys = null    // lower-cased column names, worked out once from the first row's metadata
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
