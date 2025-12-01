"""
Installation
    MySQL.jl package for MySQL database connectivity
    https://github.com/JuliaDatabases/MySQL.jl

    Automatically installed by WaSQL using Julia's package manager:
        using Pkg; Pkg.add("MySQL")

References
    https://juliadatabases.org/MySQL.jl/
    https://github.com/JuliaDatabases/DBInterface.jl
"""

using MySQL
using DBInterface
using DataFrames
using JSON3
using CSV
using Logging
using Dates

# Global connection pool for reuse
const CONNECTION_POOL = Dict{String, Tuple{Any, DateTime}}()
const POOL_LOCK = ReentrantLock()
const CONNECTION_TIMEOUT = 300  # 5 minutes before connection expires
const MAX_RETRIES = 3
const RETRY_DELAY = 1.0  # seconds

"""
    get_connection_key(params::Dict)

Generates a unique key for connection pooling based on connection parameters.
"""
function get_connection_key(params::Dict)
    dbhost = get(params, "dbhost", "localhost")
    dbport = get(params, "dbport", 3306)
    dbuser = get(params, "dbuser", "")
    dbname = get(params, "dbname", "")
    return "$dbhost:$dbport:$dbuser:$dbname"
end

"""
    is_connection_valid(conn)

Checks if a database connection is still valid.
"""
function is_connection_valid(conn)
    try
        # Simple query to test connection
        DBInterface.execute(conn, "SELECT 1")
        return true
    catch
        return false
    end
end

"""
    validate_params(params::Dict)

Validates connection parameters for security and completeness.
Returns (is_valid, error_message).
"""
function validate_params(params::Dict)
    # Check required parameters
    if !haskey(params, "dbhost") || isempty(get(params, "dbhost", ""))
        return (false, "Database host is required")
    end

    if !haskey(params, "dbuser") || isempty(get(params, "dbuser", ""))
        return (false, "Database username is required")
    end

    if !haskey(params, "dbname") || isempty(get(params, "dbname", ""))
        return (false, "Database name is required")
    end

    # Validate port is numeric and in valid range
    dbport = get(params, "dbport", 3306)
    if isa(dbport, String)
        try
            dbport = parse(Int, dbport)
        catch
            return (false, "Invalid port number format")
        end
    end

    if dbport < 1 || dbport > 65535
        return (false, "Port number must be between 1 and 65535")
    end

    return (true, "")
end

"""
    sanitize_error_message(err)

Sanitizes error messages to prevent information disclosure in production.
"""
function sanitize_error_message(err)
    err_str = string(err)
    # Remove potential sensitive information from error messages
    # Keep error type but sanitize details
    if occursin("password", lowercase(err_str)) || occursin("credential", lowercase(err_str))
        return "Authentication error"
    elseif occursin("access denied", lowercase(err_str))
        return "Access denied"
    elseif occursin("unknown database", lowercase(err_str))
        return "Database not found"
    elseif occursin("can't connect", lowercase(err_str)) || occursin("connection refused", lowercase(err_str))
        return "Connection refused"
    else
        # Return the error but log full details
        return "Database error: Check logs for details"
    end
end

"""
    connect(params::Dict)

Creates and returns a database connection to MySQL with connection pooling.

# Arguments
- `params::Dict`: Connection parameters
  - `dbhost`: Database host (required)
  - `dbport`: Database port (default: 3306)
  - `dbuser`: Database username (required)
  - `dbpass`: Database password (required)
  - `dbname`: Database name (required)
  - `use_ssl`: Use SSL/TLS connection (default: false)
  - `connect_timeout`: Connection timeout in seconds (default: 10)
  - `read_timeout`: Read timeout in seconds (default: 30)
  - `pool`: Use connection pooling (default: true)

# Returns
- MySQL connection object or nothing on failure

# Example
```julia
params = Dict("dbhost" => "localhost", "dbuser" => "root", "dbpass" => "password", "dbname" => "test")
conn = mysqldb.connect(params)
```
"""
function connect(params::Dict)
    # Validate parameters
    (is_valid, error_msg) = validate_params(params)
    if !is_valid
        @error "Connection validation failed" error=error_msg
        return nothing
    end

    # Check if connection pooling is enabled (default: true)
    use_pool = get(params, "pool", true)

    if use_pool
        conn_key = get_connection_key(params)

        # Try to get connection from pool
        lock(POOL_LOCK) do
            if haskey(CONNECTION_POOL, conn_key)
                (conn, timestamp) = CONNECTION_POOL[conn_key]

                # Check if connection is still valid and not expired
                age = (now() - timestamp).value / 1000  # Convert to seconds
                if age < CONNECTION_TIMEOUT && is_connection_valid(conn)
                    # Update timestamp and return existing connection
                    CONNECTION_POOL[conn_key] = (conn, now())
                    return conn
                else
                    # Connection expired or invalid, remove from pool
                    try
                        DBInterface.close!(conn)
                    catch
                        # Connection already closed
                    end
                    delete!(CONNECTION_POOL, conn_key)
                end
            end
        end
    end

    # Create new connection
    dbhost = get(params, "dbhost", "localhost")
    dbport = get(params, "dbport", 3306)
    dbuser = get(params, "dbuser", "")
    dbpass = get(params, "dbpass", "")
    dbname = get(params, "dbname", "")

    try
        # Parse port if it's a string
        if isa(dbport, String)
            dbport = parse(Int, dbport)
        end

        # Build connection options
        conn_opts = Dict{Symbol, Any}(
            :db => dbname,
            :port => dbport
        )

        # Add optional parameters
        if haskey(params, "connect_timeout")
            conn_opts[:connect_timeout] = params["connect_timeout"]
        end

        if haskey(params, "read_timeout")
            conn_opts[:read_timeout] = params["read_timeout"]
        end

        # Connect to MySQL with retry logic
        conn = nothing
        last_error = nothing

        for attempt in 1:MAX_RETRIES
            try
                conn = DBInterface.connect(
                    MySQL.Connection,
                    dbhost,
                    dbuser,
                    dbpass;
                    conn_opts...
                )
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
            @error "MySQL Connection Error after $MAX_RETRIES attempts" error=string(last_error) sanitized=sanitized_error
            return nothing
        end

        # Add to connection pool if pooling is enabled
        if use_pool
            lock(POOL_LOCK) do
                conn_key = get_connection_key(params)
                CONNECTION_POOL[conn_key] = (conn, now())
            end
        end

        return conn
    catch err
        sanitized_error = sanitize_error_message(err)
        @error "MySQL Connection Error" error=string(err) sanitized=sanitized_error
        return nothing
    end
end

"""
    executeSQL(query::String, params::Dict=Dict())

Executes a SQL query (INSERT, UPDATE, DELETE, etc.)

⚠️  SECURITY WARNING: This function executes raw SQL queries and is vulnerable to SQL injection.
Use executePS() with prepared statements for user input. This function should only be used
for static queries or administrative tasks.

# Arguments
- `query::String`: SQL query to execute (should not contain user input)
- `params::Dict`: Connection parameters

# Returns
- `true` on success, error message string on failure

# Example
```julia
# Safe - static query
ok = mysqldb.executeSQL("TRUNCATE TABLE temp_table", params)

# UNSAFE - use executePS instead!
# bad_query = "INSERT INTO users (name) VALUES ('" * user_input * "')"
# ok = mysqldb.executeSQL(bad_query, params)  # DON'T DO THIS!
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

    # Warn if query looks like it might contain user input (basic check)
    if occursin(r"['\"].*\$", query)
        @warn "executeSQL query may contain interpolated variables - use executePS for security"
    end

    use_pool = get(params, "pool", true)
    conn = nothing

    try
        # Connect
        conn = connect(params)
        if conn === nothing
            return "Failed to connect to MySQL database"
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
        # Only close connection if pooling is disabled
        if !use_pool && conn !== nothing
            try
                DBInterface.close!(conn)
            catch err
                @warn "Error closing connection" error=string(err)
            end
        end
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
ok = mysqldb.executePS(query, ["John Doe", "john@example.com"], params)

# Update example
query = "UPDATE users SET status = ? WHERE id = ?"
ok = mysqldb.executePS(query, ["active", 123], params)
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

    use_pool = get(params, "pool", true)
    conn = nothing
    stmt = nothing

    try
        # Connect
        conn = connect(params)
        if conn === nothing
            return "Failed to connect to MySQL database"
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
        # Close statement if it was created
        if stmt !== nothing
            try
                DBInterface.close!(stmt)
            catch err
                @debug "Error closing statement" error=string(err)
            end
        end

        # Only close connection if pooling is disabled
        if !use_pool && conn !== nothing
            try
                DBInterface.close!(conn)
            catch err
                @warn "Error closing connection" error=string(err)
            end
        end
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
json = mysqldb.queryResults("SELECT * FROM users", params)
df = mysqldb.queryResults("SELECT * FROM users", merge(params, Dict("format" => "dataframe")))
file = mysqldb.queryResults("SELECT * FROM users", merge(params, Dict("filename" => "output.csv")))
limited = mysqldb.queryResults("SELECT * FROM logs", merge(params, Dict("max_rows" => 1000)))
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

    use_pool = get(params, "pool", true)
    conn = nothing

    try
        # Connect
        conn = connect(params)
        if conn === nothing
            return "Failed to connect to MySQL database"
        end

        # Execute query and get DataFrame
        @debug "Executing query" query_preview=first(query, min(100, length(query)))
        result = DBInterface.execute(conn, query)
        df = DataFrame(result)

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
        # Only close connection if pooling is disabled
        if !use_pool && conn !== nothing
            try
                DBInterface.close!(conn)
            catch err
                @warn "Error closing connection" error=string(err)
            end
        end
    end
end

"""
    close_pooled_connections()

Closes all connections in the connection pool.
Useful for cleanup during application shutdown or testing.

# Example
```julia
mysqldb.close_pooled_connections()
```
"""
function close_pooled_connections()
    lock(POOL_LOCK) do
        closed_count = 0
        for (key, (conn, timestamp)) in CONNECTION_POOL
            try
                DBInterface.close!(conn)
                closed_count += 1
            catch err
                @warn "Error closing pooled connection" key=key error=string(err)
            end
        end
        empty!(CONNECTION_POOL)
        @info "Closed pooled connections" count=closed_count
        return closed_count
    end
end

"""
    clean_expired_connections()

Removes expired connections from the pool.
Automatically called by connect(), but can be called manually for cleanup.

# Example
```julia
mysqldb.clean_expired_connections()
```
"""
function clean_expired_connections()
    lock(POOL_LOCK) do
        cleaned_count = 0
        keys_to_remove = String[]

        for (key, (conn, timestamp)) in CONNECTION_POOL
            age = (now() - timestamp).value / 1000  # Convert to seconds
            if age >= CONNECTION_TIMEOUT || !is_connection_valid(conn)
                try
                    DBInterface.close!(conn)
                catch
                    # Connection already closed
                end
                push!(keys_to_remove, key)
                cleaned_count += 1
            end
        end

        for key in keys_to_remove
            delete!(CONNECTION_POOL, key)
        end

        if cleaned_count > 0
            @info "Cleaned expired connections" count=cleaned_count
        end

        return cleaned_count
    end
end

"""
    get_pool_stats()

Returns statistics about the connection pool.

# Returns
- Dict with keys: total_connections, connection_details

# Example
```julia
stats = mysqldb.get_pool_stats()
println("Active connections: ", stats["total_connections"])
```
"""
function get_pool_stats()
    lock(POOL_LOCK) do
        details = []
        for (key, (conn, timestamp)) in CONNECTION_POOL
            age = (now() - timestamp).value / 1000  # seconds
            is_valid = is_connection_valid(conn)
            push!(details, Dict(
                "key" => key,
                "age_seconds" => age,
                "is_valid" => is_valid,
                "created_at" => timestamp
            ))
        end

        return Dict(
            "total_connections" => length(CONNECTION_POOL),
            "connection_details" => details,
            "timeout_seconds" => CONNECTION_TIMEOUT
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
(success, message) = mysqldb.test_connection(params)
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

    # Try to connect without pooling
    test_params = copy(params)
    test_params["pool"] = false

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
        if conn !== nothing
            try
                DBInterface.close!(conn)
            catch
                # Ignore close errors
            end
        end
    end
end

# Functions defined: connect, executeSQL, executePS, queryResults,
# close_pooled_connections, clean_expired_connections, get_pool_stats, test_connection
