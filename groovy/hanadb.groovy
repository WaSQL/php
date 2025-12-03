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

/**
 * Creates and returns a database connection to SAP HANA
 * @param params Map containing connection parameters:
 *   dbhost: SAP HANA server hostname or IP
 *   dbport: SAP HANA server port (default: 30015)
 *   dbuser: database username
 *   dbpass: database password
 *   dbname: database name (optional)
 *   connect: full JDBC URL if provided (overrides other params)
 * @return Sql connection object
 * @usage
 *   def sql = hanadb.connect(params)
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
		System.err.println("SAP HANA Connection Error: ${err.message}")
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
 *   def ok = hanadb.executeSQL(query, params)
 */
def executeSQL(String query, Map params = [:]) {
	def sql = null
	try {
		// Connect
		sql = connect(params)
		if (sql == null) {
			return "Failed to connect to SAP HANA"
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
 *   def ok = hanadb.executePS(query, ['John Doe', 'john@example.com'], params)
 */
def executePS(String query, List args, Map params = [:]) {
	def sql = null
	try {
		// Connect
		sql = connect(params)
		if (sql == null) {
			return "Failed to connect to SAP HANA"
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
 *   def json = hanadb.queryResults(query, params)
 *   def recs = hanadb.queryResults(query, params + [format: 'list'])
 */
def queryResults(String query, Map params = [:]) {
	def sql = null
	try {
		// Connect
		sql = connect(params)
		if (sql == null) {
			return "Failed to connect to SAP HANA"
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
