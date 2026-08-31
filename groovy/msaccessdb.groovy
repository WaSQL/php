/**
Installation
	UCanAccess is a pure Java JDBC driver for MS Access databases
	https://github.com/spannm/ucanaccess

	Required JAR files (should be in lib folder):
		- ucanaccess-x.x.x.jar
		- commons-lang3-x.x.x.jar
		- commons-logging-x.x.jar
		- hsqldb-x.x.x.jar
		- jackcess-x.x.x.jar

	Maven/Gradle dependency:
		implementation 'net.sf.ucanaccess:ucanaccess:5.0.1'

References
	https://github.com/spannm/ucanaccess
	http://ucanaccess.sourceforge.net/site.html
*/

import groovy.sql.Sql
import java.sql.SQLException
import groovy.json.JsonOutput
import groovy.json.JsonGenerator

//---------- begin function connect
/**
* @describe creates and returns a database connection to MS Access
* @param params params map
*	dbname: path to .mdb or .accdb file
* @return object
*	connection object
* @usage
*	sql = msaccessdb.connect(params)
*/
def connect(Map params) {
	def dbname = params.dbname

	if (!dbname) {
		System.err.println("MS Access Connection Error: No database file specified")
		return null
	}

	try {
		// Basic connection - with HSQLDB 2.5.1, no special properties needed
		// If you need memory mode for small databases, add: ;memory=true
		def url = "jdbc:ucanaccess://${dbname}"
		def driver = 'net.ucanaccess.jdbc.UcanaccessDriver'

		def sql = Sql.newInstance(url, null, null, driver)
		return sql
	} catch (Exception err) {
		throw err
	}
}

//---------- begin function executeSQL
/**
* @describe executes a SQL query (INSERT, UPDATE, DELETE, etc.)
* @param params query string, params map
* @return boolean
*	true on success, error message string on failure
* @usage
*	ok = msaccessdb.executeSQL(query, params)
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
* @describe executes a prepared statement with parameters
* @param params query string, args list, params map
* @return boolean
*	true on success, error message string on failure
* @usage
*	query = "INSERT INTO users (name, email) VALUES (?, ?)"
*	ok = msaccessdb.executePS(query, ['John Doe', 'john@example.com'], params)
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
* @describe executes a query and returns list of records as maps
* @param params query string, params map
*	filename: if provided, writes results to CSV file instead of returning list
*	format: 'json' (default) or 'list' for native Groovy list format
*	skiperrors: if true, skips problematic rows and continues processing (default: false)
*	fetchsize: number of rows to fetch at once from database (default: 1000, 0 for driver default)
*	batchsize: number of rows to buffer before writing to file (default: 100)
*	notrim: if true, skips trimming whitespace from values (faster, default: false)
* @return mixed
*	JSON string (default), List of Maps if format='list', filename string if filename provided, or error message on failure
* @usage
*	json = msaccessdb.queryResults(query, params)
*	recs = msaccessdb.queryResults(query, params + [format: 'list'])
*	csv = msaccessdb.queryResults(query, params + [filename: 'output.csv', fetchsize: 5000])
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
* @describe helper function to escape CSV values
* @param params value string
* @return string
*	escaped value
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
