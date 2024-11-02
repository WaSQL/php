package require Tcl
package require tdbc::postgres

proc postgresQueryResults {cfg query} {
    # Convert cfg list back to array
    array set cfgArray $cfg
    
    # Initialize the return array
    array set results {}
    
    # Construct connection string
    set connStr [list \
        -host $cfgArray(dbhost) \
        -user $cfgArray(dbuser) \
        -password $cfgArray(dbpass) \
        -db $cfgArray(dbname) \
    ]
    
    # Add port if specified
    if {[info exists cfgArray(dbport)]} {
        lappend connStr -port $cfgArray(dbport)
    }
    
    # Connect to database
    if {[catch {
        set db [tdbc::postgres::connection create db {*}$connStr]
    } err]} {
        error "Failed to connect to PostgreSQL: $err"
    }
    
    try {
        # Execute query
        set stmt [$db prepare $query]
        set rs [$stmt execute]
        
        # Get column names
        set columns [$rs columns]
        set results(columns) $columns
        
        # Process each row
        set row 0
        while {[$rs nextrow -as lists rowData]} {
            # Store each field in the array
            for {set i 0} {$i < [llength $columns]} {incr i} {
                set column [lindex $columns $i]
                set value [lindex $rowData $i]
                # Handle null values
                if {$value eq "{}"} {
                    set value ""
                }
                set results($row,$column) $value
            }
            incr row
        }
        
        # Store the number of rows
        set results(rows) $row
        
        # Clean up
        $rs close
        $stmt close
        
    } on error {err opts} {
        # Clean up on error
        catch {$rs close}
        catch {$stmt close}
        catch {$db close}
        error "Query failed: $err"
    } finally {
        catch {$db close}
    }
    
    # Return the results array
    return [array get results]
}

# Example usage:
#
# set cfg [list \
#     dbhost "localhost" \
#     dbuser "postgres" \
#     dbpass "yourpassword" \
#     dbname "yourdatabase" \
#     dbport "5432" \
# ]
#
# if {[catch {
#     set query {
#         SELECT name, email, age 
#         FROM users 
#         WHERE active = true
#     }
#     set results [pgsqlQueryResults $cfg $query]
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
# } err]} {
#     puts "Database Error: $err"
# }