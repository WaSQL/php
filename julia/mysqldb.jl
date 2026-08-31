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

#---------- begin function get_connection_key
# @describe Generates a unique key for connection pooling based on connection parameters
# @param params Dict Connection parameters (dbhost, dbport, dbuser, dbname)
# @return String pool key of the form "dbhost:dbport:dbuser:dbname"
# @usage
#   key = get_connection_key(params)
function get_connection_key(params::Dict)
    dbhost = get(params, "dbhost", "localhost")
    dbport = get(params, "dbport", 3306)
    dbuser = get(params, "dbuser", "")
    dbname = get(params, "dbname", "")
    return "$dbhost:$dbport:$dbuser:$dbname"
end

#---------- begin function is_connection_valid
# @describe Checks if a database connection is still alive by running a trivial SELECT
# @param conn MySQL.Connection connection object to test
# @return Bool true if the connection responds, false otherwise
# @usage
#   if is_connection_valid(conn) ... end
function is_connection_valid(conn)
    try
        # Simple query to test connection
        DBInterface.execute(conn, "SELECT 1")
        return true
    catch
        return false
    end
end

#---------- begin function validate_params
# @describe Validates connection parameters for security and completeness (host, user, name, port range)
# @param params Dict Connection parameters to validate
# @return Tuple{Bool,String} (is_valid, error_message); error_message is "" when valid
# @usage
#   (ok, err) = validate_params(params)
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

#---------- begin function sanitize_error_message
# @describe Sanitizes an error into a generic message to prevent information disclosure in production
# @param err Any error or exception (anything stringifiable)
# @return String safe, generalized error message
# @usage
#   msg = sanitize_error_message(err)
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

#---------- begin function connect
# @describe Creates and returns a pooled database connection to MySQL, reusing a live pooled connection when available
# @param params Dict Connection parameters:
#   dbhost          - database host (required)
#   dbport          - database port (default: 3306)
#   dbuser          - database username (required)
#   dbpass          - database password (required)
#   dbname          - database name (required)
#   use_ssl         - use SSL/TLS connection (default: false)
#   connect_timeout - connection timeout in seconds (default: 10)
#   read_timeout    - read timeout in seconds (default: 30)
#   pool            - use connection pooling (default: true)
# @return MySQL.Connection on success, or nothing on failure
# @usage
#   params = Dict("dbhost" => "localhost", "dbuser" => "root", "dbpass" => "password", "dbname" => "test")
#   conn = mysqldb.connect(params)
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

#---------- begin function executeSQL
# @describe Executes a raw SQL query (INSERT, UPDATE, DELETE, etc.) against MySQL
# @warning Vulnerable to SQL injection - use executePS() for anything containing user input; reserve this for static or administrative queries
# @param query String SQL query to execute (should not contain user input)
# @param params Dict Connection parameters (see connect)
# @return true on success, an error message String on failure
# @usage
#   # Safe - static query
#   ok = mysqldb.executeSQL("TRUNCATE TABLE temp_table", params)
#   # UNSAFE - use executePS instead:
#   # ok = mysqldb.executeSQL("INSERT INTO users (name) VALUES ('" * user_input * "')", params)
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

#---------- begin function executePS
# @describe Executes a prepared statement with bound parameters against MySQL (SQL-injection safe); the recommended way to run queries with user input
# @param query String SQL query with ? placeholders
# @param args Vector Parameters bound to the placeholders (count must match)
# @param params Dict Connection parameters (see connect)
# @return true on success, an error message String on failure
# @usage
#   ok = mysqldb.executePS("INSERT INTO users (name, email) VALUES (?, ?)", ["John Doe", "john@example.com"], params)
#   ok = mysqldb.executePS("UPDATE users SET status = ? WHERE id = ?", ["active", 123], params)
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

#---------- begin function queryResults
# @describe Executes a SELECT query against MySQL and returns the rows as JSON, a DataFrame, or a written CSV file
# @param query String SQL query to execute
# @param params Dict Connection parameters (see connect) plus optional:
#   filename          - if provided, writes results to this CSV file and returns the path
#   format            - "json" (default) or "dataframe" for a native DataFrame
#   max_rows          - maximum rows to return (default: 10000, 0 for unlimited)
#   lowercase_columns - convert column names to lowercase (default: true)
# @return String JSON (default), a DataFrame when format="dataframe", the filename when filename is given, or an error message String
# @usage
#   json = mysqldb.queryResults("SELECT * FROM users", params)
#   df = mysqldb.queryResults("SELECT * FROM users", merge(params, Dict("format" => "dataframe")))
#   file = mysqldb.queryResults("SELECT * FROM users", merge(params, Dict("filename" => "output.csv")))
#   limited = mysqldb.queryResults("SELECT * FROM logs", merge(params, Dict("max_rows" => 1000)))
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

#---------- begin function close_pooled_connections
# @describe Closes every connection in the pool and empties it; useful for shutdown or testing
# @return Integer count of connections that were closed
# @usage
#   mysqldb.close_pooled_connections()
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

#---------- begin function clean_expired_connections
# @describe Removes expired or invalid connections from the pool; also callable manually for cleanup
# @return Integer count of connections that were cleaned
# @usage
#   mysqldb.clean_expired_connections()
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

#---------- begin function get_pool_stats
# @describe Returns statistics about the current connection pool
# @return Dict with keys total_connections, connection_details, timeout_seconds
# @usage
#   stats = mysqldb.get_pool_stats()
#   println("Active connections: ", stats["total_connections"])
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

#---------- begin function test_connection
# @describe Tests connection parameters by connecting (without pooling) and running SELECT 1; useful for validating config before use
# @param params Dict Connection parameters to test (see connect)
# @return Tuple{Bool,String} (true, "Connection successful") on success, or (false, error_message) on failure
# @usage
#   (success, message) = mysqldb.test_connection(params)
#   success ? println("Connection OK: ", message) : println("Connection failed: ", message)
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
