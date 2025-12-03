/**
Installation
	FairCom c-treeACE JDBC driver can be downloaded from:
	https://www.faircom.com/products/faircom-db

	Required JAR files (should be in lib folder):
		- ctreeJDBC.jar

	For standalone usage, place ctreeJDBC.jar in the classpath

References
	https://docs.faircom.com/doc/ctreeACE/JDBC_Developer_Guide.pdf
	https://www.faircom.com/products/faircom-db
*/

import groovy.sql.Sql
import java.sql.SQLException
import groovy.json.JsonOutput
import groovy.json.JsonGenerator

/**
 * Creates and returns a database connection to FairCom c-treeACE
 * @param params Map containing connection parameters:
 *   dbhost: c-treeACE server hostname or IP (default: localhost)
 *   dbport: c-treeACE server port (default: 6597)
 *   dbuser: database username
 *   dbpass: database password
 *   dbname: database name (optional)
 *   connect: full JDBC URL if provided (overrides other params)
 * @return Sql connection object
 * @usage
 *   def sql = ctreedb.connect(params)
 */
def connect(Map params) {
	def dbuser = params.dbuser ?: ''
	def dbpass = params.dbpass ?: ''

	try {
		def url

		// Check if custom connect string is provided
		if (params.connect) {
			def connectStr = params.connect

			// Check if it's an ODBC-style connection string (contains semicolons and key=value pairs)
			if (connectStr.contains(';') && connectStr.contains('=')) {
				// Parse ODBC connection string: DRIVER={...};Server=...;Port=...;Database=...
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
				def server = odbcParams['server'] ?: odbcParams['host'] ?: 'localhost'
				def port = odbcParams['port'] ?: '6597'
				def database = odbcParams['database'] ?: odbcParams['db'] ?: ''

				url = "jdbc:ctree://${server}:${port}"
				if (database) {
					url += "/${database}"
				}
			}
			// Otherwise assume it's a JDBC URL
			else if (connectStr.startsWith('jdbc:ctree://')) {
				url = connectStr
			}
			// Assume it's just the host part
			else {
				url = "jdbc:ctree://${connectStr}:6597"
			}
		} else {
			// Build c-treeACE JDBC URL from parameters
			def dbhost = params.dbhost ?: 'localhost'
			def dbport = params.dbport ?: '6597'
			def dbname = params.dbname ?: ''

			url = "jdbc:ctree://${dbhost}:${dbport}"
			if (dbname) {
				url += "/${dbname}"
			}
		}

		def driver = 'ctree.jdbc.ctreeDriver'
		def sql = Sql.newInstance(url, dbuser, dbpass, driver)

		return sql
	} catch (Exception err) {
		System.err.println("c-treeACE Connection Error: ${err.message}")
		err.printStackTrace()
		return null
	}
}

/**
 * Executes a SQL query (INSERT, UPDATE, DELETE, etc.)
 * @param query String SQL query to execute
 * @param params Map containing connection parameters
 * @return boolean true on success, error message string on failure
 * @usage
 *   def ok = ctreedb.executeSQL(query, params)
 */
def executeSQL(String query, Map params = [:]) {
	def sql = null
	try {
		// Connect
		sql = connect(params)
		if (sql == null) {
			return "Failed to connect to c-treeACE"
		}

		// Execute the query
		sql.execute(query)
		sql.commit()
		return true

	} catch (SQLException err) {
		System.err.println("SQL Error: ${err.message}")
		err.printStackTrace()
		return "SQL Error: ${err.message}"
	} catch (Exception err) {
		System.err.println("Error: ${err.message}")
		err.printStackTrace()
		return "Error: ${err.message}"
	} finally {
		if (sql != null) {
			sql.close()
		}
	}
}

/**
 * Executes a prepared statement with parameters
 * @param query String SQL query with ? placeholders
 * @param args List of parameters for prepared statement
 * @param params Map containing connection parameters
 * @return boolean true on success, error message string on failure
 * @usage
 *   def query = "INSERT INTO users (name, email) VALUES (?, ?)"
 *   def ok = ctreedb.executePS(query, ['John Doe', 'john@example.com'], params)
 */
def executePS(String query, List args, Map params = [:]) {
	def sql = null
	try {
		// Connect
		sql = connect(params)
		if (sql == null) {
			return "Failed to connect to c-treeACE"
		}

		// Execute the prepared statement
		sql.executeUpdate(query, args)
		sql.commit()
		return true

	} catch (SQLException err) {
		System.err.println("SQL Error: ${err.message}")
		err.printStackTrace()
		return "SQL Error: ${err.message}"
	} catch (Exception err) {
		System.err.println("Error: ${err.message}")
		err.printStackTrace()
		return "Error: ${err.message}"
	} finally {
		if (sql != null) {
			sql.close()
		}
	}
}

/**
 * Executes a query and returns list of records as maps
 * @param query String SQL query to execute
 * @param params Map containing connection parameters and optional:
 *   filename: if provided, writes results to CSV file instead of returning list
 *   format: 'json' (default) or 'list' for native Groovy list format
 *   skiperrors: if true, skips problematic rows and continues processing (default: false)
 *   fetchsize: number of rows to fetch at once from database (default: 1000, 0 for driver default)
 *   batchsize: number of rows to buffer before writing to file (default: 100)
 *   maxerrors: max consecutive errors before aborting (default: 100, only applies when skiperrors=true)
 *   notrim: if true, skips trimming whitespace from values (faster, default: false)
 * @return JSON string (default), List of Maps if format='list', filename string if filename provided, or error message on failure
 * @usage
 *   def json = ctreedb.queryResults(query, params)
 *   def recs = ctreedb.queryResults(query, params + [format: 'list'])
 *   def csv = ctreedb.queryResults(query, params + [filename: 'output.csv', skiperrors: true, fetchsize: 5000, batchsize: 500])
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
		if (sql == null) {
			return "Failed to connect to c-treeACE"
		}

		// Check if we should write to CSV file
		if (params.containsKey('filename')) {
			def csvFile = new File(params.filename)
			def writer = null
			try {
				writer = csvFile.newWriter('UTF-8')
				// Write UTF-8 BOM (Byte Order Mark) for proper Excel/app recognition
				writer.write('\uFEFF')

				// Use optimized manual ResultSet iteration for CSV output (always fastest)
				def stmt = sql.connection.createStatement()

				// Set fetch size for optimal performance (small size = more resilient)
				if (fetchSize > 0) {
					stmt.setFetchSize(fetchSize)
				}

				// Set query timeout to prevent hanging (60 seconds)
				stmt.setQueryTimeout(60)

				def rs = stmt.executeQuery(query)
				def errorCount = 0
				def successCount = 0
				def consecutiveErrors = 0
				def maxConsecutiveErrors = params.getOrDefault('maxerrors', 100) // Allow override, default 100

				try {
					def rsmd = rs.getMetaData()
					def columnCount = rsmd.getColumnCount()

					// Get field names from metadata (only once)
					def fieldNames = (1..columnCount).collect { rsmd.getColumnName(it).toLowerCase() }

					// Write header row
					def headerLine = new StringBuilder()
					fieldNames.eachWithIndex { name, idx ->
						if (idx > 0) headerLine.append(',')
						headerLine.append(escapeCSV(name))
					}
					writer.writeLine(headerLine.toString())

					// Batch buffer for writing
					def batchBuffer = new StringBuilder(batchSize * 200) // Estimate 200 chars per line
					def batchCount = 0

					// Process each row with optimized string building
					while (true) {
						try {
							if (!rs.next()) break

							// Build CSV line using StringBuilder (much faster than collect + join)
							def line = new StringBuilder(columnCount * 30) // Estimate 30 chars per field

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
										// Leave empty field
									} else {
										throw e
									}
								}
							}

							// Add to batch buffer
							batchBuffer.append(line).append('\n')
							batchCount++
							successCount++
							consecutiveErrors = 0 // Reset on success

							// Flush batch when it reaches batchSize
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

								// Show progress every 10 errors
								if (errorCount % 10 == 0) {
									System.err.println("Progress: ${successCount} successful, ${errorCount} errors so far...")
								}

								// Only abort if we hit max consecutive errors
								if (consecutiveErrors >= maxConsecutiveErrors) {
									System.err.println("Error: Aborting after ${maxConsecutiveErrors} consecutive errors. Successfully processed ${successCount} rows so far.")
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
			def errorCount = 0
			def consecutiveErrors = 0
			def maxConsecutiveErrors = params.getOrDefault('maxerrors', 100) // Allow override, default 100

			if (skipErrors) {
				// Use manual ResultSet iteration for better error handling
				def stmt = sql.connection.createStatement()

				// Set fetch size for optimal performance
				if (fetchSize > 0) {
					stmt.setFetchSize(fetchSize)
				}

				// Set query timeout to prevent hanging (60 seconds)
				stmt.setQueryTimeout(60)

				def rs = stmt.executeQuery(query)

				try {
					def rsmd = rs.getMetaData()
					def columnCount = rsmd.getColumnCount()

					// Get field names from metadata
					def fieldNames = (1..columnCount).collect { rsmd.getColumnName(it).toLowerCase() }

					// Process each row with error handling
					while (true) {
						try {
							if (!rs.next()) break

							def rec = [:]
							fieldNames.withIndex().each { fieldName, idx ->
								try {
									rec[fieldName] = rs.getObject(idx + 1)
								} catch (Exception e) {
									System.err.println("Warning: Error reading column '${fieldName}': ${e.message}")
									rec[fieldName] = null
								}
							}
							recs << rec
							consecutiveErrors = 0 // Reset on success

						} catch (SQLException e) {
							errorCount++
							consecutiveErrors++
							def errorMsg = e.message?.toLowerCase() ?: ''
							System.err.println("Warning: Skipping row due to error: ${e.message}")

							// Check for fatal cursor errors - stop immediately
							if (errorMsg.contains('cursor not opened') || errorMsg.contains('cursor') && errorMsg.contains('closed')) {
								System.err.println("Error: Fatal cursor error detected. Cursor is broken and cannot continue.")
								System.err.println("Info: Successfully processed ${recs.size()} rows before cursor failure.")
								break
							}

							// Show progress every 10 errors
							if (errorCount % 10 == 0) {
								System.err.println("Progress: ${recs.size()} successful, ${errorCount} errors so far...")
							}

							// Only abort if we hit max consecutive errors
							if (consecutiveErrors >= maxConsecutiveErrors) {
								System.err.println("Error: Aborting after ${maxConsecutiveErrors} consecutive errors. Successfully processed ${recs.size()} rows so far.")
								break
							}
							// Try to continue to next row
							continue
						}
					}
				} finally {
					rs.close()
					stmt.close()
				}

				if (errorCount > 0) {
					System.err.println("Warning: Skipped ${errorCount} rows due to errors. Successfully processed ${recs.size()} rows.")
				}

			} else {
				// Original behavior - fail on first error
				sql.eachRow(query) { row ->
					def rec = [:]
					def rowResult = row.toRowResult()

					// Convert to lowercase field names for consistency
					rowResult.each { key, value ->
						rec[key.toLowerCase()] = value
					}

					recs << rec
				}
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

	} catch (SQLException err) {
		System.err.println("SQL Error: ${err.message}")
		err.printStackTrace()
		return "SQL Error: ${err.message}"
	} catch (Exception err) {
		System.err.println("Error: ${err.message}")
		err.printStackTrace()
		return "Error: ${err.message}"
	} finally {
		if (sql != null) {
			sql.close()
		}
	}
}

/**
 * Helper function to escape CSV values
 * @param value String to escape
 * @return String escaped value
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
