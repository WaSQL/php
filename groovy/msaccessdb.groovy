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

/**
 * Creates and returns a database connection to MS Access
 * @param params Map containing connection parameters:
 *   dbname: path to .mdb or .accdb file
 * @return Sql connection object
 * @usage
 *   def sql = msaccessdb.connect(params)
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
		System.err.println("MS Access Connection Error: ${err.message}")
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
 *   def ok = msaccessdb.executeSQL(query, params)
 */
def executeSQL(String query, Map params = [:]) {
	def sql = null
	try {
		// Connect
		sql = connect(params)
		if (sql == null) {
			return "Failed to connect to MS Access database"
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
 *   def ok = msaccessdb.executePS(query, ['John Doe', 'john@example.com'], params)
 */
def executePS(String query, List args, Map params = [:]) {
	def sql = null
	try {
		// Connect
		sql = connect(params)
		if (sql == null) {
			return "Failed to connect to MS Access database"
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
 *   def json = msaccessdb.queryResults(query, params)
 *   def recs = msaccessdb.queryResults(query, params + [format: 'list'])
 */
def queryResults(String query, Map params = [:]) {
	def sql = null
	try {
		// Connect
		sql = connect(params)
		if (sql == null) {
			return "Failed to connect to MS Access database"
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
