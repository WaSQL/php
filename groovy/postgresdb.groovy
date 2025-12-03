/**
Installation
	Download PostgreSQL JDBC Driver from:
	https://jdbc.postgresql.org/download/

	Or use Gradle/Maven dependency:
		implementation 'org.postgresql:postgresql:42.6.0'

	For standalone usage, place postgresql-x.x.x.jar in the classpath

References
	https://jdbc.postgresql.org/documentation/
	https://www.postgresql.org/docs/current/jdbc.html
*/

import groovy.sql.Sql
import java.sql.SQLException
import groovy.json.JsonOutput
import groovy.json.JsonGenerator

/**
 * Adds an index to a PostgreSQL table
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
 *   def ok = postgresdb.addIndex(params)
 */
def addIndex(Map params) {
	// Check required parameters
	if (!params.containsKey('-table')) {
		return "postgresdb.addIndex error: No Table Specified"
	}
	if (!params.containsKey('-fields')) {
		return "postgresdb.addIndex error: No Fields Specified"
	}

	// Check for unique and fulltext
	def unique = ''
	def prefix = ''

	if (params.containsKey('-unique')) {
		unique = ' UNIQUE'
		prefix += 'U'
	}
	if (params.containsKey('-fulltext')) {
		// PostgreSQL uses GIN or GiST indexes for fulltext
		prefix += 'F'
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
 *   dbhost: database host
 *   dbuser: database username
 *   dbpass: database password
 *   dbname: database name
 *   dbport: database port (default: 5432)
 *   dbkeepalives: keepalive setting (optional)
 *   dbkeepalives_idle: keepalive idle time (optional)
 *   dbkeepalives_interval: keepalive interval (optional)
 *   dbkeepalives_count: keepalive count (optional)
 * @return Sql connection object
 * @usage
 *   def sql = postgresdb.connect(params)
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
	def dbport = params.dbport ?: '5432'

	try {
		// Build connection URL with optional parameters
		def url = "jdbc:postgresql://${dbhost}:${dbport}/${dbname}"
		def props = [:]

		if (params.dbkeepalives) {
			props['tcpKeepAlive'] = params.dbkeepalives
		}

		def driver = 'org.postgresql.Driver'
		def sql = Sql.newInstance(url, dbuser, dbpass, driver)

		return sql
	} catch (Exception err) {
		System.err.println("PostgreSQL Connection Error: ${err.message}")
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
 *   def ok = postgresdb.executeSQL(query, params)
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
 *   def ok = postgresdb.executePS(query, ['John Doe', 'john@example.com'], params)
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
 * @return JSON string (default), List of Maps if format='list', filename string if filename provided, or error message on failure
 * @usage
 *   def json = postgresdb.queryResults(query, params)
 *   def recs = postgresdb.queryResults(query, params + [format: 'list'])
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
			def writer = null
			try {
				writer = csvFile.newWriter('UTF-8')

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
