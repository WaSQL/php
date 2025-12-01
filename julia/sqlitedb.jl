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

"""
    validate_params(params::Dict)

Validates connection parameters for SQLite.
Returns (is_valid, error_message).
"""
function validate_params(params::Dict)
    if !haskey(params, "dbname") || isempty(get(params, "dbname", ""))
        return (false, "Database file path (dbname) is required")
    end
    return (true, "")
end

"""
    sanitize_error_message(err)

Sanitizes error messages to prevent information disclosure in production.
"""
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

"""
    connect(params::Dict)

Creates and returns a database connection to SQLite with connection caching.

# Arguments
- `params::Dict`: Connection parameters
  - `dbname`: Path to SQLite database file (required)
  - `readonly`: Open database in read-only mode (default: false)
  - `cache`: Use connection caching (default: true)

# Returns
- SQLite connection object or nothing on failure

# Example
```julia
params = Dict("dbname" => "d:/data/mydb.sqlite")
conn = sqlitedb.connect(params)
```
"""
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

"""
    executeSQL(query::String, params::Dict=Dict())

Executes a SQL query (INSERT, UPDATE, DELETE, etc.)

⚠️  SECURITY WARNING: This function executes raw SQL queries and is vulnerable to SQL injection.
Use executePS() with prepared statements for user input.

# Arguments
- `query::String`: SQL query to execute (should not contain user input)
- `params::Dict`: Connection parameters

# Returns
- `true` on success, error message string on failure

# Example
```julia
# Safe - static query
ok = sqlitedb.executeSQL("DELETE FROM temp_table", params)

# UNSAFE - use executePS instead!
# bad_query = "INSERT INTO users (name) VALUES ('" * user_input * "')"
# ok = sqlitedb.executeSQL(bad_query, params)  # DON'T DO THIS!
```
"""
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

"""
    executePS(query::String, args::Vector, params::Dict=Dict())

Executes a prepared statement with parameters (SQL injection safe).

This is the recommended way to execute queries with user input.
Uses parameterized queries to prevent SQL injection attacks.

# Arguments
- `query::String`: SQL query with ? placeholders
- `args::Vector`: Parameters for prepared statement
- `params::Dict`: Connection parameters

# Returns
- `true` on success, error message string on failure

# Example
```julia
query = "INSERT INTO users (name, email) VALUES (?, ?)"
ok = sqlitedb.executePS(query, ["John Doe", "john@example.com"], params)

# Update example
query = "UPDATE users SET status = ? WHERE id = ?"
ok = sqlitedb.executePS(query, ["active", 123], params)
```
"""
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

"""
    queryResults(query::String, params::Dict=Dict())

Executes a query and returns results

# Arguments
- `query::String`: SQL query to execute
- `params::Dict`: Connection parameters and optional:
  - `filename`: If provided, writes results to CSV file
  - `format`: "json" (default) or "dataframe" for native DataFrame
  - `max_rows`: Maximum number of rows to return (default: 10000, 0 for unlimited)
  - `lowercase_columns`: Convert column names to lowercase (default: true)

# Returns
- JSON string (default), DataFrame if format="dataframe", filename if filename provided, or error message

# Example
```julia
json = sqlitedb.queryResults("SELECT * FROM users", params)
df = sqlitedb.queryResults("SELECT * FROM users", merge(params, Dict("format" => "dataframe")))
file = sqlitedb.queryResults("SELECT * FROM users", merge(params, Dict("filename" => "output.csv")))
limited = sqlitedb.queryResults("SELECT * FROM logs", merge(params, Dict("max_rows" => 1000)))
```
"""
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

"""
    clear_connection_cache()

Clears all cached SQLite connections.
Useful for cleanup during application shutdown or testing.

# Example
```julia
sqlitedb.clear_connection_cache()
```
"""
function clear_connection_cache()
    lock(CACHE_LOCK) do
        cleared_count = length(CONNECTION_CACHE)
        empty!(CONNECTION_CACHE)
        @info "Cleared connection cache" count=cleared_count
        return cleared_count
    end
end

"""
    get_cache_stats()

Returns statistics about the connection cache.

# Returns
- Dict with keys: total_connections, database_files

# Example
```julia
stats = sqlitedb.get_cache_stats()
println("Cached connections: ", stats["total_connections"])
```
"""
function get_cache_stats()
    lock(CACHE_LOCK) do
        return Dict(
            "total_connections" => length(CONNECTION_CACHE),
            "database_files" => collect(keys(CONNECTION_CACHE))
        )
    end
end

"""
    test_connection(params::Dict)

Tests database connection parameters without executing any queries.
Useful for validating configuration before use.

# Arguments
- `params::Dict`: Connection parameters to test

# Returns
- `(true, "Connected successfully")` on success
- `(false, error_message)` on failure

# Example
```julia
(success, message) = sqlitedb.test_connection(params)
if success
    println("Connection OK: ", message)
else
    println("Connection failed: ", message)
end
```
"""
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
