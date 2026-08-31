package require Tcl
package require tdbc::sqlite3

#----------
# begin function sqliteQueryResults
# @describe opens the SQLite database file via tdbc::sqlite3, applies any configured pragmas, runs query, and returns all rows in the standard results-array form (NULLs normalized to "")
# @param cfg list - "array get" config list; requires dbname (path to the .db file); optional pragmas (name/value pairs)
# @param query string - the SQL statement to execute
# @return list - results array ("array get" form): columns (list), rows (int), and <row>,<column> => value entries; raises an error on connect/query failure
# @usage array set res [sqliteQueryResults [array get cfg] {SELECT * FROM users}]
#----------
proc sqliteQueryResults {cfg query} {
    # Convert cfg list back to array
    array set cfgArray $cfg
    
    # Initialize the return array
    array set results {}
    
    # Ensure database file path exists in config
    if {![info exists cfgArray(dbname)]} {
        error "Database file path not specified in configuration"
    }
    
    # Connect to database
    if {[catch {
        set db [tdbc::sqlite3::connection create db $cfgArray(dbname)]
        
        # Set pragmas if specified in config
        if {[info exists cfgArray(pragmas)]} {
            foreach {pragma value} $cfgArray(pragmas) {
                $db foreachrow "PRAGMA $pragma = $value;" {}
            }
        }
    } err]} {
        error "Failed to connect to SQLite database: $err"
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
#     dbfile "path/to/your/database.db" \
#     pragmas { \
#         foreign_keys 1 \
#         journal_mode WAL \
#         synchronous NORMAL \
#     } \
# ]
#
# if {[catch {
#     set query {
#         SELECT name, email, age 
#         FROM users 
#         WHERE active = 1
#     }
#     set results [sqliteQueryResults $cfg $query]
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