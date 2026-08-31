package require tdbc::mssql

#----------
# begin function mssqlQueryResults
# @describe connects to Microsoft SQL Server via tdbc::mssql with user/password auth, runs query, and returns all rows in the standard results-array form
# @param cfg list - "array get" config list; requires dbhost, dbname, dbuser, dbpass
# @param query string - the SQL statement to execute
# @return list - results array ("array get" form): columns (list), rows (int), and <row>,<column> => value entries; raises an error on connect/query failure
# @usage array set res [mssqlQueryResults [array get cfg] {SELECT * FROM Users}]
#----------
proc mssqlQueryResults {cfg query} {

    # Convert cfg list back to array
    array set cfgArray $results

    # Database connection parameters - modify as needed
    set server $cfg(dbhost)
    set database $cfg(dbname)
    set user $cfg(dbuser)
    set password $cfg(dbpass)
    
    # Initialize the return array
    array set results {}
    
    # Establish database connection
    if {[catch {
        # Construct connection string
        set connStr [format {
            -server %s 
            -database %s 
            -user %s 
            -password %s
        } $server $database $user $password]
        
        # Open connection
        set db [tdbc::mssql::connection new $connStr]
        
        # Prepare and execute statement
        set stmt [$db prepare $query]
        set resultSet [$stmt execute]
        
        # Get column names
        set columns [$resultSet columns]
        set results(columns) $columns
        
        # Process rows
        set row 0
        $resultSet foreach tuple {
            # Store each field in the array
            foreach column $columns {
                set results($row,$column) $tuple($column)
            }
            incr row
        }
        
        # Store the number of rows
        set results(rows) $row
        
        # Clean up resources
        $resultSet close
        $stmt close
        $db close
        
    } err]} {
        # Ensure resources are cleaned up in case of error
        catch {
            $resultSet close
            $stmt close
            $db close
        }
        error "SQL Server query failed: $err"
    }
    
    # Return the results array
    return [array get results]
}

# Example usage:
#
# try {
#     set query {
#         SELECT 
#             FirstName, 
#             LastName, 
#             EmailAddress, 
#             Age 
#         FROM 
#             Users 
#         WHERE 
#             Active = 1
#     }
#     
#     set results [mssqlQueryResults $query]
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
# } trap {MSSQL} err {
#     puts "Microsoft SQL Server Error: $err"
# }

#----------
# begin function mssqlQueryResultsWindowsAuth
# @describe same as mssqlQueryResults but connects to SQL Server with a trusted (Windows Authentication) connection - no user/password
# @param cfg list - "array get" config list; requires dbhost, dbname
# @param query string - the SQL statement to execute
# @return list - results array ("array get" form): columns (list), rows (int), and <row>,<column> => value entries; raises an error on connect/query failure
# @usage array set res [mssqlQueryResultsWindowsAuth [array get cfg] {SELECT * FROM Users}]
#----------
proc mssqlQueryResultsWindowsAuth {cfg query} {

    # Convert cfg list back to array
    array set cfgArray $results

    # Database connection parameters - modify as needed
    set server $cfg(dbhost)
    set database $cfg(dbname)
    
    # Initialize the return array
    array set results {}
    
    # Establish database connection
    if {[catch {
        # Construct connection string for Windows Authentication
        set connStr [format {
            -server %s 
            -database %s 
            -trusted_connection yes
        } $server $database]
        
        # Open connection
        set db [tdbc::mssql::connection new $connStr]
        
        # Prepare and execute statement
        set stmt [$db prepare $query]
        set resultSet [$stmt execute]
        
        # Get column names
        set columns [$resultSet columns]
        set results(columns) $columns
        
        # Process rows
        set row 0
        $resultSet foreach tuple {
            # Store each field in the array
            foreach column $columns {
                set results($row,$column) $tuple($column)
            }
            incr row
        }
        
        # Store the number of rows
        set results(rows) $row
        
        # Clean up resources
        $resultSet close
        $stmt close
        $db close
        
    } err]} {
        # Ensure resources are cleaned up in case of error
        catch {
            $resultSet close
            $stmt close
            $db close
        }
        error "SQL Server query failed: $err"
    }
    
    # Return the results array
    return [array get results]
}