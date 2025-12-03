/**
db.groovy - Database abstraction layer for WaSQL Groovy
Routes database operations to appropriate database-specific drivers

References:
    Dynamically loads database drivers based on dbtype from config.xml
*/

import groovy.sql.Sql
import groovy.transform.Field

// Use WASQL variables passed from PHP (if available)
// Otherwise try to load config module for standalone usage
// @Field makes these accessible to functions in this script
@Field DATABASE = null
@Field CONFIG = null
@Field common = null

try {
    // First try to use PHP-provided variables (when running embedded)
    // These are set in the main script by common.php's evalGroovyCode function
    DATABASE = WASQL_DATABASE
    CONFIG = WASQL_CONFIG
} catch (MissingPropertyException e) {
    // Not running embedded, WASQL variables not available - will load modules below
} catch (Exception e) {
    // Other error - will load modules below
}

// If not available, load config module (standalone mode)
if (DATABASE == null || CONFIG == null) {
    System.err.println("[db.groovy] DATABASE is null, attempting to load config.groovy...")
    def attemptedConfigPaths = []

    try {
        // Try multiple methods to find config.groovy
        def configScript = null

        // Method 0: Check environment variable
        def configPath = System.getenv('WASQL_CONFIG_PATH')
        if (configPath) {
            def envPath = new File(configPath, 'config.groovy')
            attemptedConfigPaths << envPath.absolutePath
            configScript = envPath
            if (!configScript.exists()) {
                configScript = null
            }
        }

        // Method 1: Try using script location
        if (configScript == null || !configScript.exists()) {
            try {
                def scriptDir = new File(getClass().protectionDomain.codeSource.location.path).parent
                def method1Path = new File(scriptDir, 'config.groovy')
                attemptedConfigPaths << method1Path.absolutePath
                configScript = method1Path
                if (!configScript.exists()) {
                    configScript = null
                }
            } catch (Exception e1) {
                attemptedConfigPaths << "Method 1 failed: ${e1.message}"
            }
        }

        // Method 2: Try current directory
        if (configScript == null || !configScript.exists()) {
            def method2Path = new File('config.groovy')
            attemptedConfigPaths << method2Path.absolutePath
            configScript = method2Path
            if (!configScript.exists()) {
                configScript = null
            }
        }

        // Method 3: Try script's directory using different approach
        if (configScript == null || !configScript.exists()) {
            try {
                def thisFile = new File(getClass().protectionDomain.codeSource.location.toURI())
                def method3Path = thisFile.isFile() ? new File(thisFile.parentFile, 'config.groovy') : new File(thisFile, 'config.groovy')
                attemptedConfigPaths << method3Path.absolutePath
                configScript = method3Path
                if (!configScript.exists()) {
                    configScript = null
                }
            } catch (Exception e3) {
                attemptedConfigPaths << "Method 3 failed: ${e3.message}"
            }
        }

        if (configScript != null && configScript.exists()) {
            System.err.println("[db.groovy] Found config.groovy at: ${configScript.absolutePath}")
            System.err.println("[db.groovy] Evaluating config.groovy...")
            def configModule = new GroovyShell(this.binding).evaluate(configScript)
            if (DATABASE == null) DATABASE = configModule.DATABASE
            if (CONFIG == null) CONFIG = configModule.CONFIG
            System.err.println("[db.groovy] Config loaded. DATABASE has ${DATABASE?.size() ?: 0} entries")
        } else {
            System.err.println("[db.groovy] ERROR: Could not find config.groovy!")
            System.err.println("[db.groovy] Searched in the following locations:")
            attemptedConfigPaths.eachWithIndex { path, idx ->
                System.err.println("  ${idx + 1}. ${path}")
            }
            System.err.println("[db.groovy] Current working directory: ${new File('.').absolutePath}")
            System.err.println("[db.groovy] Hint: Set WASQL_CONFIG_PATH environment variable to the directory containing config.groovy")
            DATABASE = [:]
            CONFIG = [:]
        }
    } catch (Exception e) {
        System.err.println("[db.groovy] ERROR loading config module: ${e.message}")
        e.printStackTrace()
        System.err.println("[db.groovy] Searched in the following locations:")
        attemptedConfigPaths.eachWithIndex { path, idx ->
            System.err.println("  ${idx + 1}. ${path}")
        }
        DATABASE = [:]
        CONFIG = [:]
    }
} else {
    System.err.println("[db.groovy] DATABASE already loaded with ${DATABASE?.size() ?: 0} entries: ${DATABASE?.keySet()}")
}

// Load common module for error handling
try {
    def commonScript = new File(new File(getClass().protectionDomain.codeSource.location.path).parent, 'common.groovy')
    common = new GroovyShell(this.binding).parse(commonScript)
} catch (Exception e) {
    // Define minimal error handling if common not available
    common = [
        debug: { Exception err -> "Error: ${err.message}" },
        abort: { Exception err -> System.err.println("Error: ${err.message}"); System.exit(1) }
    ]
}

/**
 * Returns the results of a query
 * @param dbname String database name from database tag in config.xml
 * @param query String SQL query to run
 * @param params Map parameters to override
 * @return List of Maps (recordsets)
 * @usage recs = db.queryResults('dbtest', 'select * from states')
 */
def queryResults(String dbname, String query, Map params = [:]) {
    // Ensure DATABASE is initialized
    if (DATABASE == null) {
        DATABASE = [:]
    }

    // Ensure params is not null (can happen with trailing commas on some platforms)
    if (params == null) {
        params = [:]
    }

    if (!DATABASE.containsKey(dbname)) {
        def available = DATABASE.keySet().size() > 0 ? DATABASE.keySet().join(', ') : 'none'
        return "Database '${dbname}' not found in config.xml. Available databases: ${available}"
    }

    def dbtype = DATABASE[dbname].dbtype?.toLowerCase()

    // Add DATABASE settings to params
    DATABASE[dbname].each { k, v ->
        params[k] = v
    }

    try {
        // Route to appropriate database driver
        if (dbtype?.startsWith('hana')) {
            def hanadb = loadModule('hanadb')
            return hanadb.queryResults(query, params)
        }
        else if (dbtype?.startsWith('mssql')) {
            def mssqldb = loadModule('mssqldb')
            return mssqldb.queryResults(query, params)
        }
        else if (dbtype?.startsWith('mysql')) {
            def mysqldb = loadModule('mysqldb')
            return mysqldb.queryResults(query, params)
        }
        else if (dbtype?.startsWith('oracle')) {
            def oracledb = loadModule('oracledb')
            return oracledb.queryResults(query, params)
        }
        else if (dbtype?.startsWith('snowflake')) {
            def snowflakedb = loadModule('snowflakedb')
            return snowflakedb.queryResults(query, params)
        }
        else if (dbtype?.startsWith('sqlite')) {
            def sqlitedb = loadModule('sqlitedb')
            return sqlitedb.queryResults(query, params)
        }
        else if (dbtype?.startsWith('ctree')) {
            def ctreedb = loadModule('ctreedb')
            return ctreedb.queryResults(query, params)
        }
        else if (dbtype?.startsWith('postgre')) {
            def postgresdb = loadModule('postgresdb')
            return postgresdb.queryResults(query, params)
        }
        else if (dbtype?.startsWith('msaccess')) {
            def msaccessdb = loadModule('msaccessdb')
            return msaccessdb.queryResults(query, params)
        }
        else if (dbtype?.startsWith('mscsv')) {
            def mscsvdb = loadModule('mscsvdb')
            return mscsvdb.queryResults(query, params)
        }
        else if (dbtype?.startsWith('msexcel')) {
            def msexceldb = loadModule('msexceldb')
            return msexceldb.queryResults(query, params)
        }
        else if (dbtype?.startswith('firebird')) {
            def firebirddb = loadModule('firebirddb')
            return firebirddb.queryResults(query, params)
        }
        else {
            return "Unsupported database type: ${dbtype}"
        }
    } catch (Exception err) {
        // Robust error handling that doesn't depend on common module
        def errorMsg = "Database error: ${err.message}"
        System.err.println(errorMsg)
        err.printStackTrace()
        return errorMsg
    }
}

/**
 * Executes a SQL query (INSERT, UPDATE, DELETE, etc.)
 * @param dbname String database name from database tag in config.xml
 * @param query String SQL query to run
 * @param params Map parameters to override
 * @return boolean true on success, error message on failure
 * @usage ok = db.executeSQL('dbtest', 'INSERT INTO users...')
 */
def executeSQL(String dbname, String query, Map params = [:]) {
    // Ensure DATABASE is initialized
    if (DATABASE == null) {
        DATABASE = [:]
    }
    if (params == null) {
        params = [:]
    }

    if (!DATABASE.containsKey(dbname)) {
        def available = DATABASE.keySet().size() > 0 ? DATABASE.keySet().join(', ') : 'none'
        return "Database '${dbname}' not found in config.xml. Available databases: ${available}"
    }

    def dbtype = DATABASE[dbname].dbtype?.toLowerCase()

    // Add DATABASE settings to params
    DATABASE[dbname].each { k, v ->
        params[k] = v
    }

    try {
        // Route to appropriate database driver
        if (dbtype?.startsWith('hana')) {
            def hanadb = loadModule('hanadb')
            return hanadb.executeSQL(query, params)
        }
        else if (dbtype?.startsWith('mssql')) {
            def mssqldb = loadModule('mssqldb')
            return mssqldb.executeSQL(query, params)
        }
        else if (dbtype?.startsWith('mysql')) {
            def mysqldb = loadModule('mysqldb')
            return mysqldb.executeSQL(query, params)
        }
        else if (dbtype?.startsWith('oracle')) {
            def oracledb = loadModule('oracledb')
            return oracledb.executeSQL(query, params)
        }
        else if (dbtype?.startsWith('snowflake')) {
            def snowflakedb = loadModule('snowflakedb')
            return snowflakedb.executeSQL(query, params)
        }
        else if (dbtype?.startsWith('sqlite')) {
            def sqlitedb = loadModule('sqlitedb')
            return sqlitedb.executeSQL(query, params)
        }
        else if (dbtype?.startsWith('ctree')) {
            def ctreedb = loadModule('ctreedb')
            return ctreedb.executeSQL(query, params)
        }
        else if (dbtype?.startsWith('postgre')) {
            def postgresdb = loadModule('postgresdb')
            return postgresdb.executeSQL(query, params)
        }
        else if (dbtype?.startswith('firebird')) {
            def firebirddb = loadModule('firebirddb')
            return firebirddb.executeSQL(query, params)
        }
        else {
            return "Unsupported database type: ${dbtype}"
        }
    } catch (Exception err) {
        // Robust error handling that doesn't depend on common module
        def errorMsg = "Database error: ${err.message}"
        System.err.println(errorMsg)
        err.printStackTrace()
        return errorMsg
    }
}

/**
 * Executes a prepared statement with parameters
 * @param dbname String database name from database tag in config.xml
 * @param query String SQL query to run
 * @param args Map query arguments
 * @param params Map connection parameters to override
 * @return boolean true on success, error message on failure
 * @usage ok = db.executePS('dbtest', 'INSERT INTO users VALUES (?, ?)', [name: 'John', email: 'john@example.com'])
 */
def executePS(String dbname, String query, Map args = [:], Map params = [:]) {
    // Ensure DATABASE is initialized
    if (DATABASE == null) {
        DATABASE = [:]
    }
    if (params == null) {
        params = [:]
    }
    if (args == null) {
        args = [:]
    }

    if (!DATABASE.containsKey(dbname)) {
        def available = DATABASE.keySet().size() > 0 ? DATABASE.keySet().join(', ') : 'none'
        return "Database '${dbname}' not found in config.xml. Available databases: ${available}"
    }

    def dbtype = DATABASE[dbname].dbtype?.toLowerCase()

    // Add DATABASE settings to params
    DATABASE[dbname].each { k, v ->
        params[k] = v
    }

    try {
        // Route to appropriate database driver
        if (dbtype?.startsWith('hana')) {
            def hanadb = loadModule('hanadb')
            return hanadb.executePS(query, args, params)
        }
        else if (dbtype?.startsWith('mssql')) {
            def mssqldb = loadModule('mssqldb')
            return mssqldb.executePS(query, args, params)
        }
        else if (dbtype?.startsWith('mysql')) {
            def mysqldb = loadModule('mysqldb')
            return mysqldb.executePS(query, args, params)
        }
        else if (dbtype?.startsWith('oracle')) {
            def oracledb = loadModule('oracledb')
            return oracledb.executePS(query, args, params)
        }
        else if (dbtype?.startsWith('snowflake')) {
            def snowflakedb = loadModule('snowflakedb')
            return snowflakedb.executePS(query, args, params)
        }
        else if (dbtype?.startsWith('sqlite')) {
            def sqlitedb = loadModule('sqlitedb')
            return sqlitedb.executePS(query, args, params)
        }
        else if (dbtype?.startsWith('ctree')) {
            def ctreedb = loadModule('ctreedb')
            return ctreedb.executePS(query, args, params)
        }
        else if (dbtype?.startsWith('postgre')) {
            def postgresdb = loadModule('postgresdb')
            return postgresdb.executePS(query, args, params)
        }
        else {
            return "Unsupported database type: ${dbtype}"
        }
    } catch (Exception err) {
        // Robust error handling that doesn't depend on common module
        def errorMsg = "Database error: ${err.message}"
        System.err.println(errorMsg)
        err.printStackTrace()
        return errorMsg
    }
}

/**
 * Returns a database connection
 * @param dbname String database name from database tag in config.xml
 * @param params Map parameters to override
 * @return Sql connection object
 * @usage sql = db.connect('dbtest')
 */
def connect(String dbname, Map params = [:]) {
    // Ensure DATABASE is initialized
    if (DATABASE == null) {
        DATABASE = [:]
    }
    if (params == null) {
        params = [:]
    }

    if (!DATABASE.containsKey(dbname)) {
        return null
    }

    def dbtype = DATABASE[dbname].dbtype?.toLowerCase()

    // Add DATABASE settings to params
    DATABASE[dbname].each { k, v ->
        params[k] = v
    }

    try {
        // Route to appropriate database driver
        if (dbtype?.startsWith('hana')) {
            def hanadb = loadModule('hanadb')
            return hanadb.connect(params)
        }
        else if (dbtype?.startsWith('mssql')) {
            def mssqldb = loadModule('mssqldb')
            return mssqldb.connect(params)
        }
        else if (dbtype?.startsWith('mysql')) {
            def mysqldb = loadModule('mysqldb')
            return mysqldb.connect(params)
        }
        else if (dbtype?.startsWith('oracle')) {
            def oracledb = loadModule('oracledb')
            return oracledb.connect(params)
        }
        else if (dbtype?.startsWith('snowflake')) {
            def snowflakedb = loadModule('snowflakedb')
            return snowflakedb.connect(params)
        }
        else if (dbtype?.startsWith('sqlite')) {
            def sqlitedb = loadModule('sqlitedb')
            return sqlitedb.connect(params)
        }
        else if (dbtype?.startsWith('ctree')) {
            def ctreedb = loadModule('ctreedb')
            return ctreedb.connect(params)
        }
        else if (dbtype?.startsWith('postgre')) {
            def postgresdb = loadModule('postgresdb')
            return postgresdb.connect(params)
        }
        else if (dbtype?.startsWith('msaccess')) {
            def msaccessdb = loadModule('msaccessdb')
            return msaccessdb.connect(params)
        }
        else if (dbtype?.startsWith('mscsv')) {
            def mscsvdb = loadModule('mscsvdb')
            return mscsvdb.connect(params)
        }
        else if (dbtype?.startsWith('msexcel')) {
            def msexceldb = loadModule('msexceldb')
            return msexceldb.connect(params)
        }
        else if (dbtype?.startswith('firebird')) {
            def firebirddb = loadModule('firebirddb')
            return firebirddb.connect(params)
        }
        else {
            return null
        }
    } catch (Exception err) {
        common.abort(err)
        return null
    }
}

/**
 * Converts an object to string (helper for JSON serialization)
 * @param o Object to convert
 * @return String representation
 * @usage db.convertStr(o)
 */
def convertStr(Object o) {
    if (o == null) return null
    return o.toString()
}

/**
 * Loads a database module dynamically
 * @param moduleName String name of module file (without .groovy extension)
 * @return Module object
 */
private def loadModule(String moduleName) {
    def moduleFile = new File(new File(getClass().protectionDomain.codeSource.location.path).parent, "${moduleName}.groovy")
    if (moduleFile.exists()) {
        return new GroovyShell().parse(moduleFile)
    } else {
        throw new FileNotFoundException("Module not found: ${moduleName}.groovy")
    }
}

// Export for use as module
return this
