package require mysqltcl

proc mysqlQueryResults {cfg query} {

    # Convert cfg list back to array
    array set cfgArray $results
    
    # Initialize the return array
    array set results {}
    
    # Connect to database
    if {[catch {
        set mysql [mysql::connect -host $cfg(dbhost) -user $cfg(dbuser) -password $cfg(dbpass) -db $cfg(dbname)]
    } err]} {
        error "Failed to connect to MySQL: $err"
    }
    
    # Execute query and get results
    if {[catch {
        # Execute the query
        mysql::use $mysql
        set queryResults [mysql::sel $mysql $query -list]
        
        # Get column names
        set columns [mysql::col $mysql names]
        set results(columns) $columns
        
        # Process each row
        set row 0
        foreach rowData $queryResults {
            # Store each field in the array
            for {set i 0} {$i < [llength $columns]} {incr i} {
                set column [lindex $columns $i]
                set value [lindex $rowData $i]
                set results($row,$column) $value
            }
            incr row
        }
        
        # Store the number of rows
        set results(rows) $row
        
    } err]} {
        # Close connection before throwing error
        catch {mysql::close $mysql}
        error "Query failed: $err"
    }
    
    # Close database connection
    mysql::close $mysql
    
    # Return the results array
    return [array get results]
}

# Example usage:
#
# try {
#     set query {
#         SELECT name, email, age 
#         FROM users 
#         WHERE active = 1
#     }
#     set results [mysqlQueryResults $query]
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
#     # Use with existing CSV or HTML formatters
#     puts [resultsAsCSV $results]
#     # or
#     puts [resultsAsTable $results]
#
# } trap {MYSQL} err {
#     puts "MySQL Error: $err"
# }
}

# Example usage:
# set results [mysqlQueryResult "my_local" "SELECT * FROM some_table"]
# foreach row $results {
#     puts $row
# }