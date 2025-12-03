/**
Installation
	Download Oracle JDBC Driver from:
	https://www.oracle.com/database/technologies/appdev/jdbc-downloads.html

	Or use Gradle/Maven dependency:
		implementation 'com.oracle.database.jdbc:ojdbc11:23.2.0.0'

	For standalone usage, place ojdbc11.jar in the classpath

References
	https://docs.oracle.com/en/database/oracle/oracle-database/23/jjdbc/
	https://www.oracle.com/database/technologies/appdev/jdbc.html
*/

import groovy.sql.Sql
import java.sql.SQLException
import groovy.json.JsonOutput
import groovy.json.JsonGenerator

/**
 * Adds an index to an Oracle table
 * @param params Map containing:
 *   -table: table name (required)
 *   -fields: field(s) to add to index, comma-separated (required)
 *   -unique: if present, creates unique index
 *   -name: specific name for index (optional)
 * @return boolean true on success, error message string on failure
 * @usage
 *   def params = [
 *     '-table': 'states',
 *     '-fields': 'code'
 *   ]
 *   def ok = oracledb.addIndex(params)
 */
def addIndex(Map params) {
	// Check required parameters
	if (!params.containsKey('-table')) {
		return "oracledb.addIndex error: No Table Specified"
	}
	if (!params.containsKey('-fields')) {
		return "oracledb.addIndex error: No Fields Specified"
	}

	// Check for unique
	def unique = ''
	def prefix = ''

	if (params.containsKey('-unique')) {
		unique = ' UNIQUE'
		prefix += 'U'
	}

	// Build index name if not passed in
	if (!params.containsKey('-name')) {
		def fieldstr = params['-fields'].replace(',', '_')
		params['-name'] = "${prefix}_${params['-table']}_${fieldstr}".take(30) // Oracle has 30 char limit
	}

	// Oracle doesn't support IF NOT EXISTS for indexes directly, need to check first
	def checkQuery = """
		SELECT COUNT(*) as cnt FROM user_indexes WHERE index_name = '${params['-name'].toUpperCase()}'
	"""

	def sql = connect(params)
	if (sql == null) {
		return "Failed to connect to database"
	}

	try {
		def row = sql.firstRow(checkQuery)
		if (row.cnt == 0) {
			// Index doesn't exist, create it
			def query = "CREATE ${unique} INDEX ${params['-name']} ON ${params['-table']} (${params['-fields']})"
			sql.execute(query)
			sql.commit()
		}
		sql.close()
		return true
	} catch (Exception err) {
		if (sql) sql.close()
		return "Error: ${err.message}"
	}
}

/**
 * Creates and returns a database connection
 * @param params Map containing connection parameters:
 *   dbhost: database host
 *   dbuser: database username
 *   dbpass: database password
 *   dbname: database service name or SID
 *   dbport: database port (default: 1521)
 *   dbsid: use SID instead of service name (optional)
 * @return Sql connection object
 * @usage
 *   def sql = oracledb.connect(params)
 */
def connect(Map params) {
	if (!params.dbhost) {
		System.err.println("Missing dbhost attribute in database tag named '${params.name}'")
		System.exit(123)
	}

	def dbhost = params.dbhost
	def dbuser = params.dbuser ?: ''
	def dbpass = params.dbpass ?: ''
	def dbname = params.dbname ?: ''
	def dbport = params.dbport ?: '1521'

	// Handle PHP-style "host, port" format (e.g., "10.144.243.105, 1521")
	if (dbhost.contains(',')) {
		def parts = dbhost.split(',')
		dbhost = parts[0].trim()
		if (parts.size() > 1 && parts[1].trim().isNumber()) {
			dbport = parts[1].trim()
		}
	}

	try {
		def url

		// Check if custom connect descriptor is provided (TNS format)
		if (params.connect) {
			// Use full TNS descriptor from connect parameter
			// Example: (DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(Host=...)(Port=...))(CONNECT_DATA=(SERVICE_NAME=...)))
			url = "jdbc:oracle:thin:@${params.connect}"
		}
		// Choose connection format based on whether dbsid is specified
		else if (params.dbsid) {
			// SID format
			url = "jdbc:oracle:thin:@${dbhost}:${dbport}:${dbname}"
		} else {
			// Service name format (default)
			url = "jdbc:oracle:thin:@//${dbhost}:${dbport}/${dbname}"
		}

		def driver = 'oracle.jdbc.OracleDriver'
		def sql = Sql.newInstance(url, dbuser, dbpass, driver)

		return sql
	} catch (Exception err) {
		System.err.println("Oracle Connection Error: ${err.message}")
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
 *   def ok = oracledb.executeSQL(query, params)
 */
def executeSQL(String query, Map params = [:]) {
	def sql = null
	try {
		// Connect
		sql = connect(params)
		if (sql == null) {
			return "Failed to connect to database"
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
 *   def ok = oracledb.executePS(query, ['John Doe', 'john@example.com'], params)
 */
def executePS(String query, List args, Map params = [:]) {
	def sql = null
	try {
		// Connect
		sql = connect(params)
		if (sql == null) {
			return "Failed to connect to database"
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
 *   notrim: if true, skips trimming whitespace from values (faster, default: false)
 * @return JSON string (default), List of Maps if format='list', filename string if filename provided, or error message on failure
 * @usage
 *   def json = oracledb.queryResults(query, params)
 *   def recs = oracledb.queryResults(query, params + [format: 'list'])
 *   def csv = oracledb.queryResults(query, params + [filename: 'output.csv', fetchsize: 5000])
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
			return "Failed to connect to database"
		}

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
