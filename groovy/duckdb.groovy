/**
Installation
	Download the DuckDB JDBC driver from: https://duckdb.org/docs/api/java
	Or use Maven/Gradle:
		implementation 'org.duckdb:duckdb_jdbc:1.1.0'

	Place duckdb_jdbc-x.x.x.jar in the groovy/lib/ directory.

	DuckDB is an embedded analytical database (no server required).
	Set dbname to a file path for a persistent database, or ':memory:' for in-memory.

References
	https://duckdb.org/docs/api/java
	https://duckdb.org/docs/sql/introduction
*/

import groovy.sql.Sql
import groovy.json.JsonGenerator
import java.sql.SQLException

//---------- begin function addIndex
/**
* @describe adds an index to a DuckDB table
* @param params params map
*	-table: table name (required)
*	-fields: field(s) to add to index, comma-separated (required)
*	-unique: if present, creates a unique index
*	-name: specific name for index (optional)
* @return boolean
*	true on success
* @usage
*	ok = duckdb.addIndex(['-table': 'events', '-fields': 'user_id'])
*/
def addIndex(Map params) {
	if (!params.containsKey('-table'))  return "duckdb.addIndex error: No Table Specified"
	if (!params.containsKey('-fields')) return "duckdb.addIndex error: No Fields Specified"

	def unique = params.containsKey('-unique') ? ' UNIQUE' : ''
	def prefix = params.containsKey('-unique') ? 'U' : ''

	if (!params.containsKey('-name')) {
		params['-name'] = "${prefix}_${params['-table']}_${params['-fields'].replace(',', '_')}"
	}

	def query = "CREATE${unique} INDEX IF NOT EXISTS ${params['-name']} ON ${params['-table']} (${params['-fields']})"
	return executeSQL(query, params)
}

//---------- begin function connect
/**
* @describe creates and returns a DuckDB connection
* @param params params map
*	dbname: path to .ddb file, or ':memory:' for in-memory (default: ':memory:')
* @return object
*	connection object
* @usage
*	sql = duckdb.connect(params)
*/
def connect(Map params) {
	def dbname = params.dbname ?: ':memory:'
	try {
		def url    = "jdbc:duckdb:${dbname == ':memory:' ? '' : dbname}"
		def driver = 'org.duckdb.DuckDBDriver'
		return Sql.newInstance(url, '', '', driver)
	} catch (Exception err) {
		throw err
	}
}

//---------- begin function executeSQL
/**
* @describe executes a SQL statement (INSERT, UPDATE, DELETE, CREATE, etc.)
* @param params query string, params map
* @return boolean
*	true on success
* @usage
*	ok = duckdb.executeSQL("CREATE TABLE t (id INTEGER)", params)
*/
def executeSQL(String query, Map params = [:]) {
	def sql = null
	try {
		sql = connect(params)
		sql.execute(query)
		return true
	} catch (Exception err) {
		throw err
	} finally {
		if (sql != null) sql.close()
	}
}

//---------- begin function executePS
/**
* @describe executes a parameterized statement
* @param params query string, args mixed, params map
* @return boolean
*	true on success
* @usage
*	duckdb.executePS("INSERT INTO t VALUES (?, ?)", [1, 'hello'], params)
*/
def executePS(String query, def args, Map params = [:]) {
	def sql = null
	try {
		sql = connect(params)
		def argList = args instanceof Map ? args.values().toList() : (args as List)
		sql.executeUpdate(query, argList)
		return true
	} catch (Exception err) {
		throw err
	} finally {
		if (sql != null) sql.close()
	}
}

//---------- begin function queryResults
/**
* @describe executes a SELECT query and returns results
* @param params query string, params map
*	filename:   write results to CSV file instead of returning data
*	format:     'json' (default) or 'list' for native Groovy list
*	skiperrors: skip problem rows and continue (default: false)
*	fetchsize:  rows to fetch per batch (default: 1000)
*	batchsize:  rows to buffer before writing CSV (default: 100)
*	notrim:     skip whitespace trimming (default: false)
* @return mixed
*	JSON string, List of Maps, or filename string (CSV mode)
* @usage
*	rows = duckdb.queryResults("SELECT * FROM events", params)
*	rows = duckdb.queryResults("SELECT * FROM events", params + [format: 'list'])
*/
def queryResults(String query, Map params = [:]) {
	def sql          = null
	def skipErrors   = params.getOrDefault('skiperrors', false)
	def fetchSize    = params.getOrDefault('fetchsize', 1000) as int
	def batchSize    = params.getOrDefault('batchsize', 100)  as int
	def noTrim       = params.getOrDefault('notrim', false)

	try {
		sql = connect(params)

		// ── CSV export mode ───────────────────────────────────────────────────
		if (params.containsKey('filename')) {
			def writer = null
			try {
				writer = new File(params.filename as String).newWriter('UTF-8')
				writer.write('﻿') // UTF-8 BOM for Excel

				def stmt = sql.connection.createStatement()
				if (fetchSize > 0) stmt.setFetchSize(fetchSize)
				stmt.setQueryTimeout(600)
				def rs = stmt.executeQuery(query)

				def errorCount       = 0
				def successCount     = 0
				def consecutiveErrors = 0
				final int maxConsecutive = 10

				try {
					def rsmd        = rs.getMetaData()
					def columnCount = rsmd.getColumnCount()
					def fieldNames  = (1..columnCount).collect { rsmd.getColumnName(it).toLowerCase() }

					// Header row
					writer.writeLine(fieldNames.collect { escapeCSV(it) }.join(','))

					def batchBuffer = new StringBuilder(batchSize * 200)
					def batchCount  = 0

					while (true) {
						try {
							if (!rs.next()) break
							def line = new StringBuilder(columnCount * 30)
							for (int i = 1; i <= columnCount; i++) {
								if (i > 1) line.append(',')
								try {
									def val = rs.getObject(i)
									if (val != null) {
										def s = val.toString()
										if (!noTrim) s = s.trim()
										line.append(escapeCSV(s))
									}
								} catch (Exception e) {
									if (skipErrors) {
										System.err.println("Warning: Error reading column '${fieldNames[i-1]}': ${e.message}")
									} else {
										throw e
									}
								}
							}
							batchBuffer.append(line).append('\n')
							batchCount++
							successCount++
							consecutiveErrors = 0

							if (batchCount >= batchSize) {
								writer.write(batchBuffer.toString())
								batchBuffer.setLength(0)
								batchCount = 0
							}
						} catch (SQLException e) {
							if (skipErrors) {
								errorCount++
								consecutiveErrors++
								System.err.println("Warning: Skipping row: ${e.message}")
								if (consecutiveErrors >= maxConsecutive) {
									System.err.println("Error: Aborting after ${maxConsecutive} consecutive errors.")
									break
								}
							} else {
								throw e
							}
						}
					}

					if (batchCount > 0) writer.write(batchBuffer.toString())

				} finally {
					rs.close()
					stmt.close()
				}

				if (skipErrors && errorCount > 0) {
					System.err.println("Warning: Skipped ${errorCount} rows. Processed ${successCount} successfully.")
				}

				return params.filename

			} finally {
				if (writer != null) writer.close()
			}
		}

		// ── In-memory result mode ─────────────────────────────────────────────
		def recs = []
		sql.eachRow(query) { row ->
			def rec = [:]
			row.toRowResult().each { k, v -> rec[k.toLowerCase()] = v }
			recs << rec
		}

		def format = params.getOrDefault('format', 'json')
		if (format == 'json') {
			return new JsonGenerator.Options().disableUnicodeEscaping().build().toJson(recs)
		}
		return recs

	} catch (Exception err) {
		throw err
	} finally {
		if (sql != null) sql.close()
	}
}

//---------- begin function escapeCSV
/**
* @describe escapes a value for CSV output
* @param params value string
*/
private def escapeCSV(String value) {
	if (value == null) return ''
	if (value.contains(',') || value.contains('"') || value.contains('\n') || value.contains('\r')) {
		return '"' + value.replace('"', '""') + '"'
	}
	return value
}

return this
