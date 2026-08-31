"""
Installation
    SQLite.jl package for SQLite database connectivity
    https://github.com/JuliaDatabases/SQLite.jl

    Automatically installed by WaSQL using Julia's package manager:
        using Pkg; Pkg.add("SQLite")

References
    https://juliadatabases.org/SQLite.jl/stable/
    https://www.sqlite.org/docs.html
"""

using SQLite
using DBInterface
using DataFrames
using JSON3
using CSV
using Logging

# Connection cache for SQLite (file-based, so we can safely reuse)
const CONNECTION_CACHE = Dict{String, Any}()
const CACHE_LOCK = ReentrantLock()
const MAX_RETRIES = 3
const RETRY_DELAY = 0.5  # seconds

#---------- begin function validate_params
# @describe Validates connection parameters for SQLite (requires a dbname file path)
# @param params Dict Connection parameters to validate
# @return Tuple{Bool,String} (is_valid, error_message); error_message is "" when valid
# @usage
#   (ok, err) = validate_params(params)
function validate_params(params::Dict)
    if !haskey(params, "dbname") || isempty(get(params, "dbname", ""))
        return (false, "Database file path (dbname) is required")
    end
    return (true, "")
end

#---------- begin function sanitize_error_message
# @describe Sanitizes an error into a generic message to prevent information disclosure in production
# @param err Any error or exception (anything stringifiable)
# @return String safe, generalized error message
# @usage
#   msg = sanitize_error_message(err)
function sanitize_error_message(err)
    err_str = string(err)
    if occursin("no such table", lowercase(err_str))
        return "Table not found"
    elseif occursin("no such column", lowercase(err_str))
        return "Column not found"
    elseif occursin("syntax error", lowercase(err_str))
        return "SQL syntax error"
    elseif occursin("locked", lowercase(err_str))
        return "Database is locked"
    elseif occursin("unable to open", lowercase(err_str))
        return "Unable to open database file"
    else
        return "Database error: Check logs for details"
    end
end

#---------- begin function connect
# @describe Creates and returns a cached database connection to SQLite, reusing a cached connection for the same file when available
# @param params Dict Connection parameters:
#   dbname   - path to the SQLite database file (required)
#   readonly - open the database in read-only mode (default: false)
#   cache    - use connection caching (default: true)
# @return SQLite.DB on success, or nothing on failure
# @usage
#   params = Dict("dbname" => "d:/data/mydb.sqlite")
#   conn = sqlitedb.connect(params)
function connect(params::Dict)
    # Validate parameters
    (is_valid, error_msg) = validate_params(params)
    if !is_valid
        @error "Connection validation failed" error=error_msg
        return nothing
    end

    dbname = get(params, "dbname", "")
    readonly = get(params, "readonly", false)
    use_cache = get(params, "cache", true)

    # Check cache if enabled
    if use_cache
        cache_key = dbname
        lock(CACHE_LOCK) do
            if haskey(CONNECTION_CACHE, cache_key)
                return CONNECTION_CACHE[cache_key]
            end
        end
    end

    # Connect with retry logic
    conn = nothing
    last_error = nothing

    for attempt in 1:MAX_RETRIES
        try
            conn = SQLite.DB(dbname)
            break
        catch err
            last_error = err
            if attempt < MAX_RETRIES
                @warn "Connection attempt $attempt failed, retrying..." error=string(err)
                sleep(RETRY_DELAY)
            end
        end
    end

    if conn === nothing
        sanitized_error = sanitize_error_message(last_error)
        @error "SQLite Connection Error after $MAX_RETRIES attempts" error=string(last_error) sanitized=sanitized_error
        return nothing
    end

    # Add to cache if enabled
    if use_cache
        lock(CACHE_LOCK) do
            CONNECTION_CACHE[dbname] = conn
        end
    end

    return conn
end

#---------- begin function executeSQL
# @describe Executes a raw SQL query (INSERT, UPDATE, DELETE, etc.) against SQLite
# @warning Vulnerable to SQL injection - use executePS() for anything containing user input; reserve this for static or administrative queries
# @param query String SQL query to execute (should not contain user input)
# @param params Dict Connection parameters (see connect)
# @return true on success, an error message String on failure
# @usage
#   # Safe - static query
#   ok = sqlitedb.executeSQL("DELETE FROM temp_table", params)
#   # UNSAFE - use executePS instead:
#   # ok = sqlitedb.executeSQL("INSERT INTO users (name) VALUES ('" * user_input * "')", params)
function executeSQL(query::String, params::Dict=Dict())
    # Validate query is not empty
    if isempty(strip(query))
        @error "Empty query provided to executeSQL"
        return "Query cannot be empty"
    end

    # Validate parameters
    (is_valid, error_msg) = validate_params(params)
    if !is_valid
        @error "Invalid parameters" error=error_msg
        return error_msg
    end

    conn = nothing
    try
        # Connect
        conn = connect(params)
        if conn === nothing
            return "Failed to connect to SQLite database"
        end

        # Execute the query
        @debug "Executing SQL" query_preview=first(query, min(100, length(query)))
        DBInterface.execute(conn, query)

        return true
    catch err
        sanitized_error = sanitize_error_message(err)
        @error "SQL Error" error=string(err) sanitized=sanitized_error
        return sanitized_error
    finally
        # SQLite.jl connections don't need explicit closing
    end
end

#---------- begin function executePS
# @describe Executes a prepared statement with bound parameters against SQLite (SQL-injection safe); the recommended way to run queries with user input
# @param query String SQL query with ? placeholders
# @param args Vector Parameters bound to the placeholders (count must match)
# @param params Dict Connection parameters (see connect)
# @return true on success, an error message String on failure
# @usage
#   ok = sqlitedb.executePS("INSERT INTO users (name, email) VALUES (?, ?)", ["John Doe", "john@example.com"], params)
#   ok = sqlitedb.executePS("UPDATE users SET status = ? WHERE id = ?", ["active", 123], params)
function executePS(query::String, args::Vector, params::Dict=Dict())
    # Validate query is not empty
    if isempty(strip(query))
        @error "Empty query provided to executePS"
        return "Query cannot be empty"
    end

    # Validate parameters
    (is_valid, error_msg) = validate_params(params)
    if !is_valid
        @error "Invalid parameters" error=error_msg
        return error_msg
    end

    # Validate that number of ? matches number of args
    placeholder_count = count(c -> c == '?', query)
    if placeholder_count != length(args)
        error_msg = "Parameter mismatch: query has $placeholder_count placeholders but $(length(args)) arguments provided"
        @error error_msg
        return error_msg
    end

    conn = nothing
    stmt = nothing
    try
        # Connect
        conn = connect(params)
        if conn === nothing
            return "Failed to connect to SQLite database"
        end

        # Execute prepared statement
        @debug "Executing prepared statement" query_preview=first(query, min(100, length(query))) arg_count=length(args)
        stmt = DBInterface.prepare(conn, query)
        DBInterface.execute(stmt, args)

        return true
    catch err
        sanitized_error = sanitize_error_message(err)
        @error "SQL Error in prepared statement" error=string(err) sanitized=sanitized_error
        return sanitized_error
    finally
        # SQLite.jl connections don't need explicit closing
    end
end

#---------- begin function queryResults
# @describe Executes a SELECT query against SQLite and returns the rows as JSON, a DataFrame, or a written CSV file
# @param query String SQL query to execute
# @param params Dict Connection parameters (see connect) plus optional:
#   filename          - if provided, writes results to this CSV file and returns the path
#   format            - "json" (default) or "dataframe" for a native DataFrame
#   max_rows          - maximum rows to return (default: 10000, 0 for unlimited)
#   lowercase_columns - convert column names to lowercase (default: true)
# @return String JSON (default), a DataFrame when format="dataframe", the filename when filename is given, or an error message String
# @usage
#   json = sqlitedb.queryResults("SELECT * FROM users", params)
#   df = sqlitedb.queryResults("SELECT * FROM users", merge(params, Dict("format" => "dataframe")))
#   file = sqlitedb.queryResults("SELECT * FROM users", merge(params, Dict("filename" => "output.csv")))
#   limited = sqlitedb.queryResults("SELECT * FROM logs", merge(params, Dict("max_rows" => 1000)))
function queryResults(query::String, params::Dict=Dict())
    # Validate query is not empty
    if isempty(strip(query))
        @error "Empty query provided to queryResults"
        return "Query cannot be empty"
    end

    # Validate parameters
    (is_valid, error_msg) = validate_params(params)
    if !is_valid
        @error "Invalid parameters" error=error_msg
        return error_msg
    end

    # Get max rows limit (default 10000, 0 means unlimited)
    max_rows = get(params, "max_rows", 10000)
    lowercase_columns = get(params, "lowercase_columns", true)

    conn = nothing
    try
        # Connect
        conn = connect(params)
        if conn === nothing
            return "Failed to connect to SQLite database"
        end

        # Execute query and get DataFrame
        @debug "Executing query" query_preview=first(query, min(100, length(query)))
        df = DBInterface.execute(conn, query) |> DataFrame

        # Check row count and warn if limit is reached
        row_count = nrow(df)
        if max_rows > 0 && row_count > max_rows
            @warn "Result set truncated" total_rows=row_count max_rows=max_rows
            df = first(df, max_rows)
        end

        # Convert column names to lowercase for consistency if requested
        if lowercase_columns && !isempty(names(df))
            try
                # Create mapping of original names to lowercase names
                name_map = Dict(name => lowercase(string(name)) for name in names(df))
                rename!(df, name_map)
            catch err
                # If rename fails (e.g., duplicate column names after lowercasing), keep original
                @warn "Could not lowercase column names, keeping originals" error=string(err)
            end
        end

        # Check if we should write to CSV file
        if haskey(params, "filename")
            filename = params["filename"]

            # Validate filename
            if isempty(strip(filename))
                @error "Empty filename provided"
                return "Filename cannot be empty"
            end

            io = nothing
            try
                @debug "Writing results to CSV" filename=filename rows=nrow(df)
                io = open(filename, "w")
                CSV.write(io, df)
                @info "Query results written to file" filename=filename rows=nrow(df)
                return filename
            catch err
                @error "Error writing CSV file" filename=filename error=string(err)
                return "Error writing to file: $(sanitize_error_message(err))"
            finally
                if io !== nothing
                    close(io)
                end
            end
        else
            # Return JSON by default, or DataFrame if requested
            format = get(params, "format", "json")
            if format == "json"
                # Convert DataFrame to array of dicts for standard row-based JSON
                if isempty(names(df))
                    return "[]"
                end

                try
                    rows = [Dict(names(df)[i] => row[i] for i in 1:length(names(df))) for row in eachrow(df)]
                    return JSON3.write(rows)
                catch err
                    @error "Error converting to JSON" error=string(err)
                    return "Error converting results to JSON: $(sanitize_error_message(err))"
                end
            elseif format == "dataframe"
                return df
            else
                @warn "Unknown format requested, returning DataFrame" format=format
                return df
            end
        end
    catch err
        sanitized_error = sanitize_error_message(err)
        @error "SQL Error in queryResults" error=string(err) sanitized=sanitized_error
        return sanitized_error
    finally
        # SQLite.jl connections don't need explicit closing
    end
end

#---------- begin function clear_connection_cache
# @describe Clears every cached SQLite connection; useful for shutdown or testing
# @return Integer count of cached connections that were cleared
# @usage
#   sqlitedb.clear_connection_cache()
function clear_connection_cache()
    lock(CACHE_LOCK) do
        cleared_count = length(CONNECTION_CACHE)
        empty!(CONNECTION_CACHE)
        @info "Cleared connection cache" count=cleared_count
        return cleared_count
    end
end

#---------- begin function get_cache_stats
# @describe Returns statistics about the current SQLite connection cache
# @return Dict with keys total_connections, database_files
# @usage
#   stats = sqlitedb.get_cache_stats()
#   println("Cached connections: ", stats["total_connections"])
function get_cache_stats()
    lock(CACHE_LOCK) do
        return Dict(
            "total_connections" => length(CONNECTION_CACHE),
            "database_files" => collect(keys(CONNECTION_CACHE))
        )
    end
end

#---------- begin function test_connection
# @describe Tests connection parameters by connecting (without caching) and running SELECT 1; useful for validating config before use
# @param params Dict Connection parameters to test (see connect)
# @return Tuple{Bool,String} (true, "Connection successful") on success, or (false, error_message) on failure
# @usage
#   (success, message) = sqlitedb.test_connection(params)
#   success ? println("Connection OK: ", message) : println("Connection failed: ", message)
function test_connection(params::Dict)
    # Validate parameters first
    (is_valid, error_msg) = validate_params(params)
    if !is_valid
        return (false, error_msg)
    end

    # Try to connect without caching
    test_params = copy(params)
    test_params["cache"] = false

    conn = nothing
    try
        conn = connect(test_params)
        if conn === nothing
            return (false, "Failed to establish connection")
        end

        # Test the connection with a simple query
        DBInterface.execute(conn, "SELECT 1")
        return (true, "Connection successful")
    catch err
        sanitized_error = sanitize_error_message(err)
        return (false, sanitized_error)
    finally
        # SQLite connections don't need explicit closing
    end
end

# Functions defined: connect, executeSQL, executePS, queryResults,
# clear_connection_cache, get_cache_stats, test_connection
