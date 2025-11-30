/**
Installation
	Download MySQL Connector/J from: https://dev.mysql.com/downloads/connector/j/
	Or use Gradle/Maven dependency:
		implementation 'mysql:mysql-connector-java:8.0.33'

	For standalone usage, place mysql-connector-java-x.x.xx.jar in the classpath

References
	https://docs.oracle.com/javase/8/docs/api/java/sql/package-summary.html
	https://groovy-lang.org/databases.html
*/

import groovy.sql.Sql
import groovy.json.JsonOutput
import groovy.json.JsonSlurper
import java.sql.SQLException

/**
 * Adds an index to a MySQL table
 * @param params Map containing:
 *   -table: table name (required)
 *   -fields: field(s) to add to index, comma-separated (required)
 *   -unique: if present, creates unique index
 *   -fulltext: if present, creates fulltext index
 *   -name: specific name for index (optional)
 * @return boolean true on success, error message string on failure
 * @usage
 *   def params = [
 *     '-table': 'states',
 *     '-fields': 'code'
 *   ]
 *   def ok = mysqldb.addIndex(params)
 */
def addIndex(Map params) {
	// Check required parameters
	if (!params.containsKey('-table')) {
		return "mysqldb.addIndex error: No Table Specified"
	}
	if (!params.containsKey('-fields')) {
		return "mysqldb.addIndex error: No Fields Specified"
	}

	// Check for unique and fulltext
	def fulltext = ''
	def unique = ''
	def prefix = ''

	if (params.containsKey('-unique')) {
		unique = ' UNIQUE'
		prefix += 'U'
	}
	if (params.containsKey('-fulltext')) {
		fulltext = ' FULLTEXT'
		prefix += 'F'
	}

	// Build index name if not passed in
	if (!params.containsKey('-name')) {
		params['-name'] = "${prefix}_${params['-table']}_"
	}

	// Create query
	def fieldstr = params['-fields'].replace(',', '_')
	def query = "CREATE ${unique}${fulltext} INDEX IF NOT EXISTS ${params['-name']} ON ${params['-table']} (${params['-fields']})"

	// Execute query
	return executeSQL(query, params)
}

/**
 * Creates and returns a database connection
 * @param params Map containing connection parameters:
 *   dbhost: database host (default: localhost)
 *   dbuser: database username
 *   dbpass: database password
 *   dbname: database name
 * @return Sql connection object
 * @usage
 *   def sql = mysqldb.connect(params)
 */
def connect(Map params) {
	def dbhost = params.dbhost ?: 'localhost'
	def dbuser = params.dbuser ?: ''
	def dbpass = params.dbpass ?: ''
	def dbname = params.dbname ?: ''
	def dbport = params.dbport ?: '3306'

	try {
		def url = "jdbc:mysql://${dbhost}:${dbport}/${dbname}?useSSL=false&allowPublicKeyRetrieval=true&serverTimezone=UTC"
		def driver = 'com.mysql.cj.jdbc.Driver'

		def sql = Sql.newInstance(url, dbuser, dbpass, driver)
		return sql
	} catch (Exception err) {
		System.err.println("MySQL Connection Error: ${err.message}")
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
 *   def ok = mysqldb.executeSQL(query, params)
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
 * Executes a query and returns list of records as maps
 * @param query String SQL query to execute
 * @param params Map containing connection parameters and optional:
 *   filename: if provided, writes results to CSV file instead of returning list
 * @return List of Maps (records) or filename string if filename provided, or error message on failure
 * @usage
 *   def recs = mysqldb.queryResults(query, params)
 */
def queryResults(String query, Map params = [:]) {
	def sql = null
	try {
		// Connect
		sql = connect(params)
		if (sql == null) {
			return "Failed to connect to database"
		}

		// Check if we should write to CSV file
		if (params.containsKey('filename')) {
			def csvFile = new File(params.filename)
			def writer = csvFile.newWriter('UTF-8')

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

			writer.close()
			return params.filename

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

			return recs
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

/**
 * Helper function to convert objects to JSON-compatible strings
 * Useful for date/time objects and other special types
 * @param obj Object to convert
 * @return String representation
 */
def convertStr(Object obj) {
	if (obj == null) {
		return null
	}

	if (obj instanceof java.sql.Date || obj instanceof java.sql.Timestamp || obj instanceof java.util.Date) {
		return obj.toString()
	}

	return obj
}

// Export functions for use as a module
return this
