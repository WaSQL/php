/**
Installation
	SQLite JDBC driver is typically included with Groovy or can be added via:
	https://github.com/xerial/sqlite-jdbc

	Maven/Gradle dependency:
		implementation 'org.xerial:sqlite-jdbc:3.41.2.2'

	For standalone usage, place sqlite-jdbc-x.x.x.x.jar in the classpath

References
	https://github.com/xerial/sqlite-jdbc
	https://www.sqlite.org/docs.html
*/

import groovy.sql.Sql
import java.sql.SQLException

/**
 * Adds an index to a SQLite table
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
 *   def ok = sqlitedb.addIndex(params)
 */
def addIndex(Map params) {
	// Check required parameters
	if (!params.containsKey('-table')) {
		return "sqlitedb.addIndex error: No Table Specified"
	}
	if (!params.containsKey('-fields')) {
		return "sqlitedb.addIndex error: No Fields Specified"
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
		params['-name'] = "${prefix}_${params['-table']}_${params['-fields'].replace(',', '_')}"
	}

	// Create query
	def query = "CREATE ${unique} INDEX IF NOT EXISTS ${params['-name']} ON ${params['-table']} (${params['-fields']})"

	// Execute query
	return executeSQL(query, params)
}

/**
 * Creates and returns a database connection
 * @param params Map containing connection parameters:
 *   dbname: database file path
 * @return Sql connection object
 * @usage
 *   def sql = sqlitedb.connect(params)
 */
def connect(Map params) {
	def dbname = params.dbname ?: ':memory:'

	try {
		def url = "jdbc:sqlite:${dbname}"
		def driver = 'org.sqlite.JDBC'

		def sql = Sql.newInstance(url, null, null, driver)
		return sql
	} catch (Exception err) {
		System.err.println("SQLite Connection Error: ${err.message}")
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
 *   def ok = sqlitedb.executeSQL(query, params)
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
 *   def ok = sqlitedb.executePS(query, ['John Doe', 'john@example.com'], params)
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
 * @return List of Maps (records) or filename string if filename provided, or error message on failure
 * @usage
 *   def recs = sqlitedb.queryResults(query, params)
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

// Export for use as module
return this
