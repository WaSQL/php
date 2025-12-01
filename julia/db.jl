"""
db.jl - Database abstraction layer for WaSQL Julia
Routes database operations to appropriate database-specific drivers

Automatically installs required Julia packages on first use
"""

module WaSQLDb

using Pkg

# Global variables for WaSQL integration (set by evalJuliaCode)
# These will be defined by the calling script
# WASQL_DATABASE, WASQL_CONFIG, etc.

"""
    ensure_packages_installed(dbtypes::Vector{String})

Checks if required Julia packages are installed and returns installation instructions if needed

# Arguments
- `dbtypes::Vector{String}`: List of database types to check packages for

# Returns
- `nothing` if all packages are installed, or error message with installation instructions
"""
function ensure_packages_installed(dbtypes::Vector{String})
    # Map database types to required Julia packages
    package_map = Dict(
        "mysql" => ["MySQL", "DBInterface"],
        "mysqli" => ["MySQL", "DBInterface"],
        "postgres" => ["LibPQ"],
        "postgresql" => ["LibPQ"],
        "sqlite" => ["SQLite", "DBInterface"],
        "sqlite3" => ["SQLite", "DBInterface"],
        "oracle" => ["ODBC"],
        "mssql" => ["ODBC"],
        "sqlsrv" => ["ODBC"],
        "snowflake" => ["ODBC"],
        "msaccess" => ["ODBC"]
    )

    # Common packages needed by all database modules
    common_packages = ["DataFrames", "JSON3", "CSV"]

    # Collect all required packages
    required_packages = Set{String}(common_packages)

    for dbtype in dbtypes
        dbtype_lower = lowercase(dbtype)
        if haskey(package_map, dbtype_lower)
            union!(required_packages, package_map[dbtype_lower])
        end
    end

    # Check which packages need to be installed
    installed_packages = Set([pkg.name for pkg in values(Pkg.dependencies())])
    packages_to_install = setdiff(required_packages, installed_packages)

    # Return installation instructions if packages are missing
    if !isempty(packages_to_install)
        pkg_list = join(sort(collect(packages_to_install)), "\", \"")

        instructions = """
<pre>
Missing Julia packages detected!

Required packages: $pkg_list

EASIEST METHOD - Run Julia REPL and paste these commands:

  julia
  using Pkg
  Pkg.add(["$pkg_list"])
  exit()

Or install individually from command line:
"""

        # Add individual package install commands
        for pkg in sort(collect(packages_to_install))
            if Sys.iswindows()
                instructions *= "  julia -e \"using Pkg; Pkg.add(\\\"$pkg\\\")\"\n"
            else
                instructions *= "  julia -e 'using Pkg; Pkg.add(\"$pkg\")'\n"
            end
        end

        # Add ODBC driver note if ODBC package is required
        if "ODBC" in packages_to_install
            instructions *= "\nNote: ODBC package requires database-specific ODBC drivers:\n"
            instructions *= "  - Oracle: Install Oracle Instant Client with ODBC\n"
            instructions *= "  - MS SQL Server: Install Microsoft ODBC Driver 17 for SQL Server\n"
            instructions *= "  - Snowflake: Install Snowflake ODBC Driver\n"
            instructions *= "  - MS Access: Install Microsoft Access Database Engine\n"
        end

        instructions *= "</pre>"
        return instructions
    end

    return nothing
end

"""
    load_module(dbtype::String)

Loads the appropriate database driver functions for the given database type

# Arguments
- `dbtype::String`: Database type (mysql, postgres, oracle, etc.)

# Returns
- NamedTuple with database functions (queryResults, executeSQL, executePS) or nothing
"""
# Cache for loaded modules - stores which files have been included
const LOADED_DB_FILES = Set{String}()

function load_module(dbtype::String)
    dbtype_lower = lowercase(dbtype)

    # Map database types to their file names
    module_map = Dict(
        "mysql" => "mysqldb",
        "mysqli" => "mysqldb",
        "postgres" => "postgresdb",
        "postgresql" => "postgresdb",
        "sqlite" => "sqlitedb",
        "sqlite3" => "sqlitedb",
        "oracle" => "oracledb",
        "mssql" => "mssqldb",
        "sqlsrv" => "mssqldb",
        "snowflake" => "snowflakedb",
        "msaccess" => "msaccessdb"
    )

    if haskey(module_map, dbtype_lower)
        module_file = module_map[dbtype_lower]
        module_path = joinpath(@__DIR__, module_file * ".jl")

        if isfile(module_path)
            # Include the file if not already loaded (defines functions in current scope)
            if !(module_file in LOADED_DB_FILES)
                include(module_path)
                push!(LOADED_DB_FILES, module_file)
            end

            # Return a NamedTuple with function references
            # The functions are now available in the current module scope
            return (
                queryResults = queryResults,
                executeSQL = executeSQL,
                executePS = executePS
            )
        else
            println(stderr, "Database module not found: $module_file.jl")
            return nothing
        end
    else
        println(stderr, "Unsupported database type: $dbtype")
        return nothing
    end
end

"""
    queryResults(dbname::String, query::String, params::Dict=Dict())

Executes a query and returns results

# Arguments
- `dbname::String`: Database name from config
- `query::String`: SQL query to execute
- `params::Dict`: Optional parameters to override config

# Returns
- JSON string (default) or other format based on params

# Example
```julia
json = db.queryResults("mydb", "SELECT * FROM users")
df = db.queryResults("mydb", "SELECT * FROM users", Dict("format" => "dataframe"))
```
"""
function queryResults(dbname::String, query::String, params::Dict=Dict())
    # Check if database exists in configuration
    if !isdefined(Main, :WASQL_DATABASE) || !haskey(Main.WASQL_DATABASE, dbname)
        return "Database '$dbname' not found in configuration"
    end

    dbconfig = Main.WASQL_DATABASE[dbname]
    dbtype = lowercase(get(dbconfig, "dbtype", ""))

    if isempty(dbtype)
        return "Database type not specified for '$dbname'"
    end

    # Check if required packages are installed
    install_msg = ensure_packages_installed([dbtype])
    if install_msg !== nothing
        return install_msg
    end

    # Merge database config with params and convert symbol keys to strings
    merged_params = merge(dbconfig, params)
    # Convert symbol keys to string keys for compatibility with database drivers
    string_params = Dict{String, Any}()
    for (k, v) in merged_params
        key_str = k isa Symbol ? string(k) : string(k)
        string_params[key_str] = v
    end
    merged_params = string_params

    # Load appropriate database module
    load_result = load_module(dbtype)
    if load_result === nothing
        return "Failed to load database module for type: $dbtype"
    end

    # Execute query using invokelatest to call the database-specific queryResults function
    # invokelatest ensures we use the method that was just compiled from the included file
    try
        return Base.invokelatest(load_result.queryResults, query, merged_params)
    catch err
        println(stderr, "Database query error: ", err)
        Base.showerror(stderr, err, catch_backtrace())
        println(stderr)
        return "Database error: $err"
    end
end

"""
    executeSQL(dbname::String, query::String, params::Dict=Dict())

Executes a SQL query (INSERT, UPDATE, DELETE, etc.)

# Arguments
- `dbname::String`: Database name from config
- `query::String`: SQL query to execute
- `params::Dict`: Optional parameters to override config

# Returns
- `true` on success, error message on failure

# Example
```julia
ok = db.executeSQL("mydb", "INSERT INTO users (name) VALUES ('John')")
```
"""
function executeSQL(dbname::String, query::String, params::Dict=Dict())
    # Check if database exists in configuration
    if !isdefined(Main, :WASQL_DATABASE) || !haskey(Main.WASQL_DATABASE, dbname)
        return "Database '$dbname' not found in configuration"
    end

    dbconfig = Main.WASQL_DATABASE[dbname]
    dbtype = lowercase(get(dbconfig, "dbtype", ""))

    if isempty(dbtype)
        return "Database type not specified for '$dbname'"
    end

    # Check if required packages are installed
    install_msg = ensure_packages_installed([dbtype])
    if install_msg !== nothing
        return install_msg
    end

    # Merge database config with params and convert symbol keys to strings
    merged_params = merge(dbconfig, params)
    # Convert symbol keys to string keys for compatibility with database drivers
    string_params = Dict{String, Any}()
    for (k, v) in merged_params
        key_str = k isa Symbol ? string(k) : string(k)
        string_params[key_str] = v
    end
    merged_params = string_params

    # Load appropriate database module
    dbmodule = load_module(dbtype)
    if dbmodule === nothing
        return "Failed to load database module for type: $dbtype"
    end

    # Execute query
    try
        return dbmodule.executeSQL(query, merged_params)
    catch err
        println(stderr, "Database execution error: ", err)
        return "Database error: $err"
    end
end

"""
    executePS(dbname::String, query::String, args::Vector, params::Dict=Dict())

Executes a prepared statement with parameters

# Arguments
- `dbname::String`: Database name from config
- `query::String`: SQL query with placeholders
- `args::Vector`: Query parameters
- `params::Dict`: Optional parameters to override config

# Returns
- `true` on success, error message on failure

# Example
```julia
ok = db.executePS("mydb", "INSERT INTO users (name, email) VALUES (?, ?)", ["John", "john@example.com"])
```
"""
function executePS(dbname::String, query::String, args::Vector, params::Dict=Dict())
    # Check if database exists in configuration
    if !isdefined(Main, :WASQL_DATABASE) || !haskey(Main.WASQL_DATABASE, dbname)
        return "Database '$dbname' not found in configuration"
    end

    dbconfig = Main.WASQL_DATABASE[dbname]
    dbtype = lowercase(get(dbconfig, "dbtype", ""))

    if isempty(dbtype)
        return "Database type not specified for '$dbname'"
    end

    # Check if required packages are installed
    install_msg = ensure_packages_installed([dbtype])
    if install_msg !== nothing
        return install_msg
    end

    # Merge database config with params and convert symbol keys to strings
    merged_params = merge(dbconfig, params)
    # Convert symbol keys to string keys for compatibility with database drivers
    string_params = Dict{String, Any}()
    for (k, v) in merged_params
        key_str = k isa Symbol ? string(k) : string(k)
        string_params[key_str] = v
    end
    merged_params = string_params

    # Load appropriate database module
    dbmodule = load_module(dbtype)
    if dbmodule === nothing
        return "Failed to load database module for type: $dbtype"
    end

    # Execute prepared statement
    try
        return dbmodule.executePS(query, args, merged_params)
    catch err
        println(stderr, "Database execution error: ", err)
        return "Database error: $err"
    end
end

# Module exports
export queryResults, executeSQL, executePS, ensure_packages_installed

end # module WaSQLDb
