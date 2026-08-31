package require Tcl

# Add MySQL lib directory to path
global env
append env(PATH) ";C:/Webserver/bin/mysql80/lib"
if {[info exists env(LIBPATH)]} {
    append env(LIBPATH) ";C:/Webserver/bin/mysql80/lib"
} else {
    set env(LIBPATH) "C:/Webserver/bin/mysql80/lib"
}

package require tdbc::mysql 1.1.5

#----------
# begin function mysqlQueryResults
# @describe connects to MySQL via tdbc::mysql using the given config, runs query, and returns all rows in the standard results-array form
# @param cfg list - "array get" config list; must supply dbhost, dbuser, dbpass, dbname
# @param query string - the SQL statement to execute
# @return list - results array ("array get" form): columns (list), rows (int), and <row>,<column> => value entries; raises an error on connect/query failure
# @usage array set res [mysqlQueryResults [array get cfg] {SELECT * FROM users}]
#----------
proc mysqlQueryResults {cfg query} {
    # Convert cfg list back to array
    array set cfgArray $cfg
    
    # Initialize the return array
    array set results {}
    
    # Connect to database using tdbc::mysql style connection
    if {[catch {
        set db [tdbc::mysql::connection create db \
            -host $cfgArray(dbhost) \
            -user $cfgArray(dbuser) \
            -password $cfgArray(dbpass) \
            -database $cfgArray(dbname)]
    } err]} {
        error "Failed to connect to MySQL: $err"
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