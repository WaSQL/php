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
 * @return JSON string (default), List of Maps if format='list', filename string if filename provided, or error message on failure
 * @usage
 *   def json = ctreedb.queryResults(query, params)
 *   def recs = ctreedb.queryResults(query, params + [format: 'list'])
 */
def queryResults(String query, Map params = [:]) {
	def sql = null
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

				// Execute query and process results
				def firstRow = true
				def fieldNames = []

				sql.eachRow(query) { row ->
					// Get field names from first row
					if (firstRow) {
						fieldNames = row.toRowResult().keySet().collect { it.toLowerCase() }
						// Write header row
						writer.writeLine(fieldNames.collect { escapeCSV(it) }.join(','))
						firstRow = false
					}

					// Write data row
					def values = fieldNames.collect { fieldName ->
						def value = row.toRowResult()[fieldName]
						escapeCSV(value?.toString() ?: '')
					}
					writer.writeLine(values.join(','))
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
