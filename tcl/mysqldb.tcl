package require mysqltcl

proc mysqlConnect {cfg} {
    # Connect to MySQL
    set mysqlHandle [mysql::connect \
        -host $cfg(dbhost) \
        -user $cfg(dbuser) \
        -password $cfg(dbpass) \
        -db $cfg(dbname)]
}

proc mysqlQueryResult {cfg query} {
    # Connect to MySQL
    set tcl_h [mysqlConnect cfg]
    
    # Execute query
    set result {}
    try {
        # Execute the query
        mysql::use $tcl_h
        set queryHandle [mysql::query $query]
        
        # Fetch all rows
        while {[set row [mysql::fetch $queryHandle]] != ""} {
            lappend result $row
        }
        
        mysql::endquery $queryHandle
        
    } finally {
        # Always close the connection
        mysql::close $mysqlHandle
    }
    
    return $result
}

# Example usage:
# set results [mysqlQueryResult "my_local" "SELECT * FROM some_table"]
# foreach row $results {
#     puts $row
# }