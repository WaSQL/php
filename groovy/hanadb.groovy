/**
Installation
	SAP HANA JDBC driver can be downloaded from:
	https://tools.hana.ondemand.com/#hanatools

	Required JAR files (should be in lib folder):
		- ngdbc.jar (or hanaJDBC.jar)

	Maven/Gradle dependency:
		implementation 'com.sap.cloud.db.jdbc:ngdbc:2.16.14'

	For standalone usage, place ngdbc.jar in the classpath

References
	https://help.sap.com/docs/SAP_HANA_PLATFORM/0eec0d68141541d1b07893a39944924e/ff15928cf5594d78b841fbbe649f04b4.html
	https://help.sap.com/docs/SAP_HANA_CLIENT/f1b440ded6144a54ada97ff95dac7adf/434e2962074540e18c802fd478de86d6.html
*/

import groovy.sql.Sql
import java.sql.SQLException
import groovy.json.JsonOutput
import groovy.json.JsonGenerator

//---------- begin function connect
/**
* @describe creates and returns a database connection to SAP HANA
* @param params params map
*	dbhost: SAP HANA server hostname or IP
*	dbport: SAP HANA server port (default 30015)
*	dbuser: database username
*	dbpass: database password
*	dbname: database name (optional)
*	connect: full JDBC URL or ODBC-style connection string (overrides other params)
* @return object
*	Sql connection object
* @usage
*	sql = hanadb.connect(params)
*/
def connect(Map params) {
	def dbuser = params.dbuser ?: ''
	def dbpass = params.dbpass ?: ''

	try {
		def url
		def databaseName = null

		// Check if custom connect string is provided
		if (params.connect) {
			def connectStr = params.connect

			// Check if it's an ODBC-style connection string (contains semicolons and key=value pairs)
			if (connectStr.contains(';') && connectStr.contains('=')) {
				// Parse ODBC connection string: DRIVER={...};SERVERNODE=...;DATABASE=...
				def odbcParams = [:]
				connectStr.split(';').each { param ->
					def parts = param.split('=', 2)
					if (parts.size() == 2) {
						def key = parts[0].trim().toLowerCase()
						def value = parts[1].trim().replaceAll(/^\{|\}$/, '') // Remove curly braces
						odbcParams[key] = value
					}
				}

				// Build JDBC URL from ODBC parameters
				def servernode = odbcParams['servernode'] ?: odbcParams['host']
				databaseName = odbcParams['database'] ?: odbcParams['db']

				// Extract user and password from connect string if present
				if (odbcParams['uid']) {
					dbuser = odbcParams['uid']
				}
				if (odbcParams['pwd']) {
					dbpass = odbcParams['pwd']
				}

				if (!servernode) {
					System.err.println("Missing SERVERNODE in ODBC connection string")
					return null
				}

				// For HANA, remove trailing slash - some versions don't like it
				url = "jdbc:sap://${servernode}"
			}
			// Otherwise assume it's a JDBC URL
			else if (connectStr.startsWith('jdbc:sap://')) {
				url = connectStr
			}
			// Assume it's just the host:port part
			else {
				url = "jdbc:sap://${connectStr}/"
			}
		} else {
			// Build SAP HANA JDBC URL from parameters
			def dbhost = params.dbhost
			if (!dbhost) {
				System.err.println("Missing dbhost attribute in database tag named '${params.name}'")
				return null
			}

			def dbport = params.dbport ?: '30015'
			// For HANA, remove trailing slash - some versions don't like it
			url = "jdbc:sap://${dbhost}:${dbport}"
		}

		// Fallback to params.dbname if not set from connect string
		if (!databaseName) {
			databaseName = params.dbname
		}

		// Build connection properties for SAP HANA
		def props = new Properties()
		props.setProperty('user', dbuser)
		props.setProperty('password', dbpass)

		// For multi-tenant HANA, the port routes to the tenant database
		// Don't specify databaseName - let the port handle routing
		// Only use databaseName if explicitly connecting to SYSTEMDB
		// if (databaseName) {
		// 	props.setProperty('databaseName', databaseName)
		// }

		// Don't set currentschema in connection properties - it causes errors if schema doesn't exist
		// Instead, set it after connection if needed
		// if (params.dbschema) {
		// 	props.setProperty('currentschema', params.dbschema)
		// }

		// Enable UTF-8 support
		props.setProperty('characterEncoding', 'UTF-8')

		// Enable reconnect similar to ODBC settings
		props.setProperty('reconnect', 'true')

		def driver = 'com.sap.db.jdbc.Driver'

		// Load the driver and create connection with properties
		Class.forName(driver)
		def connection = java.sql.DriverManager.getConnection(url, props)
		def sql = new Sql(connection)

		return sql
	} catch (Exception err) {
		throw err
	}
}

//---------- begin function executeSQL
/**
* @describe executes a non-SELECT SQL statement (INSERT, UPDATE, DELETE, DDL, etc.)
* @param params query string, params map
*	params: connection parameters (see connect)
* @return boolean
*	true on success, or an error is thrown on failure
* @usage
*	ok = hanadb.executeSQL(query, params)
*/
def executeSQL(String query, Map params = [:]) {
	def sql = null
	try {
		// Connect
		sql = connect(params)

		// Execute the query
		sql.execute(query)
		sql.commit()
		return true

	} catch (Exception err) {
		throw err
	} finally {
		if (sql != null) {
			sql.close()
		}
	}
}

//---------- begin function executePS
/**
* @describe executes a prepared statement with positional parameters
* @param params query string, args list, params map
*	query: SQL with ? placeholders
*	args: values to bind to the placeholders
*	params: connection parameters (see connect)
* @return boolean
*	true on success, or an error is thrown on failure
* @usage
*	ok = hanadb.executePS("INSERT INTO users (name, email) VALUES (?, ?)", ['John Doe', 'john@example.com'], params)
*/
def executePS(String query, List args, Map params = [:]) {
	def sql = null
	try {
		// Connect
		sql = connect(params)

		// Execute the prepared statement
		sql.executeUpdate(query, args)
		sql.commit()
		return true

	} catch (Exception err) {
		throw err
	} finally {
		if (sql != null) {
			sql.close()
		}
	}
}

//---------- begin function queryResults
/**
* @describe executes a SELECT query and returns the records (as JSON, a native list, or streamed to a CSV file)
* @param params query string, params map
*	filename: if provided, writes results to this CSV file instead of returning a list
*	format: 'json' (default) or 'list' for a native Groovy list
*	skiperrors: if true, skips problematic rows and continues (default false)
*	fetchsize: rows to fetch at once from the database (default 1000, 0 for driver default)
*	batchsize: rows to buffer before writing to file (default 100)
*	notrim: if true, skips trimming whitespace from values (faster, default false)
* @return mixed
*	JSON string (default), list of maps if format='list', the filename if filename was provided, or an error is thrown on failure
* @usage
*	json = hanadb.queryResults(query, params)
*	recs = hanadb.queryResults(query, params + [format: 'list'])
*	csv = hanadb.queryResults(query, params + [filename: 'output.csv', fetchsize: 5000])
*/
def queryResults(String query, Map params = [:]) {
	def sql = null
	def skipErrors = params.getOrDefault('skiperrors', false)
	def fetchSize = params.getOrDefault('fetchsize', 1000)
	def batchSize = params.getOrDefault('batchsize', 100)
	def noTrim = params.getOrDefault('notrim', false)

	try {
		// Connect
		sql = connect(params)

		// Check if we should write to CSV file
		if (params.containsKey('filename')) {
			def csvFile = new File(params.filename)
			def writer = null
			try {
				writer = csvFile.newWriter('UTF-8')
				// Write UTF-8 BOM (Byte Order Mark) for proper Excel/app recognition
				writer.write('\uFEFF')

				// Use optimized manual ResultSet iteration for CSV output
				def stmt = sql.connection.createStatement()

				// Set fetch size for optimal performance
				if (fetchSize > 0) {
					stmt.setFetchSize(fetchSize)
				}

				// Set query timeout to prevent hanging (300 seconds)
				stmt.setQueryTimeout(600)

				def rs = stmt.executeQuery(query)
				def errorCount = 0
				def successCount = 0
				def consecutiveErrors = 0
				def maxConsecutiveErrors = 10 // Break out if we get stuck

				try {
					def rsmd = rs.getMetaData()
					def columnCount = rsmd.getColumnCount()

					// Get field names from metadata
					def fieldNames = (1..columnCount).collect { rsmd.getColumnName(it).toLowerCase() }

					// Write header row
					def headerLine = new StringBuilder()
					fieldNames.eachWithIndex { name, idx ->
						if (idx > 0) headerLine.append(',')
						headerLine.append(escapeCSV(name))
					}
					writer.writeLine(headerLine.toString())

					// Batch buffer for writing
					def batchBuffer = new StringBuilder(batchSize * 200)
					def batchCount = 0

					// Process each row with optimized string building
					while (true) {
						try {
							if (!rs.next()) break

							def line = new StringBuilder(columnCount * 30)

							for (int i = 1; i <= columnCount; i++) {
								if (i > 1) line.append(',')

								try {
									def value = rs.getObject(i)
									if (value != null) {
										def strValue = value.toString()
										if (!noTrim) {
											strValue = strValue.trim()
										}
										line.append(escapeCSV(strValue))
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
							consecutiveErrors = 0 // Reset on success

							if (batchCount >= batchSize) {
								writer.write(batchBuffer.toString())
								batchBuffer.setLength(0)
								batchCount = 0
							}

						} catch (SQLException e) {
							if (skipErrors) {
								errorCount++
								consecutiveErrors++
								def errorMsg = e.message?.toLowerCase() ?: ''
								System.err.println("Warning: Skipping row due to error: ${e.message}")

								// Check for fatal cursor errors - stop immediately
								if (errorMsg.contains('cursor not opened') || errorMsg.contains('cursor') && errorMsg.contains('closed')) {
									System.err.println("Error: Fatal cursor error detected. Cursor is broken and cannot continue.")
									System.err.println("Info: Successfully processed ${successCount} rows before cursor failure.")
									break
								}

								// Prevent infinite loops on persistent errors
								if (consecutiveErrors >= maxConsecutiveErrors) {
									System.err.println("Error: Aborting after ${maxConsecutiveErrors} consecutive errors. Possible connection issue or corrupted data.")
									break
								}
								continue
							} else {
								throw e
							}
						}
					}

					// Write remaining buffered lines
					if (batchCount > 0) {
						writer.write(batchBuffer.toString())
					}

				} finally {
					rs.close()
					stmt.close()
				}

				if (skipErrors && errorCount > 0) {
					System.err.println("Warning: Skipped ${errorCount} rows due to errors. Successfully processed ${successCount} rows.")
				}

				return params.filename
			} finally {
				if (writer != null) {
					writer.close()
				}
			}

		} else {
			// Return list of maps
			def recs = []

			sql.eachRow(query) { row ->
				def rec = [:]
				def rowResult = row.toRowResult()

				// Convert to lowercase field names for consistency
				rowResult.each { key, value ->
					rec[key.toLowerCase()] = value
				}

				recs << rec
			}

			// Return JSON by default, or native format if requested
			def format = params.getOrDefault('format', 'json')
			if (format == 'json') {
				// Use JsonGenerator to output actual UTF-8 characters instead of Unicode escape sequences
				def generator = new JsonGenerator.Options()
					.disableUnicodeEscaping()
					.build()
				return generator.toJson(recs)
			} else {
				return recs
			}
		}

	} catch (Exception err) {
		throw err
	} finally {
		if (sql != null) {
			sql.close()
		}
	}
}

//---------- begin function escapeCSV
/**
* @describe escapes a value for CSV output (quotes it if it contains a comma, quote, or newline)
* @param params value string
* @return string
* @usage
*	line.append(escapeCSV(strValue))
*/
private def escapeCSV(String value) {
	if (value == null) {
		return ''
	}

	// If value contains comma, quote, or newline, wrap in quotes and escape internal quotes
	if (value.contains(',') || value.contains('"') || value.contains('\n') || value.contains('\r')) {
		return '"' + value.replace('"', '""') + '"'
	}

	return value
}

// Export for use as module
return this
