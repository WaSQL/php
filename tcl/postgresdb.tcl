package require Pgtcl

proc postgresQueryResults {cfg query} {

    # Convert cfg list back to array
    array set cfgArray $results
    
    # Initialize the return array
    array set results {}
    
    # Build connection string
    set conninfo "host=$cfg(dbhost) port=$cfg(dbport) dbname=$cfg(dbname) user=$cfg(dbuser) password=$cfg(dbpass)"
    
    # Connect to database
    if {[catch {
        set conn [pg_connect $conninfo]
    } err]} {
        error "Failed to connect to PostgreSQL: $err"
    }
    
    # Execute query and get results
    if {[catch {
        # Execute the query
        set pgResult [pg_exec $conn $query]
        
        # Check for errors
        set resultStatus [pg_result $pgResult -status]
        if {$resultStatus != "PGRES_TUPLES_OK" && $resultStatus != "PGRES_COMMAND_OK"} {
            error [pg_result $pgResult -error]
        }
        
        # Get number of rows and columns
        set numRows [pg_result $pgResult -numTuples]
        set numCols [pg_result $pgResult -numAttrs]
        
        # Get column names
        set columns {}
        for {set i 0} {$i < $numCols} {incr i} {
            lappend columns [pg_result $pgResult -attname $i]
        }
        set results(columns) $columns
        
        # Process each row
        for {set row 0} {$row < $numRows} {incr row} {
            foreach column $columns {
                set colNum [lsearch $columns $column]
                set results($row,$column) [pg_result $pgResult -getTuple $row $colNum]
            }
        }
        
        # Store the number of rows
        set results(rows) $numRows
        
        # Free the result
        pg_result $pgResult -clear
        
    } err]} {
        # Free result and close connection before throwing error
        catch {pg_result $pgResult -clear}
        catch {pg_disconnect $conn}
        error "Query failed: $err"
    }
    
    # Close database connection
    pg_disconnect $conn
    
    # Return the results array
    return [array get results]
}

# Example usage:
#
# try {
#     set query {
#         SELECT name, email, age 
#         FROM users 
#         WHERE active = true
#     }
#     set results [postgresQueryResults $query]
#     
#     # Convert results back to array
#     array set resultsArray $results
#     
#     # Access the data
#     puts "Number of rows: $resultsArray(rows)"
#     puts "Columns: $resultsArray(columns)"
#     
#     # Print all results
#     for {set i 0} {$i < $resultsArray(rows)} {incr i} {
#         foreach column $resultsArray(columns) {
#             puts "$column: $resultsArray($i,$column)"
#         }
#         puts "---"
#     }
#     
#     # Use with existing formatters
#     puts [resultsAsCSV $results]
#     # or
#     puts [resultsAsTable $results]
#
# } trap {POSTGRES} err {
#     puts "PostgreSQL Error: $err"
# }