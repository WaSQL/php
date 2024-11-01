package require sqlite3

# Connect to SQLite
proc sqliteConnect {cfg} {
    set sqliteHandle [sqlite3::open $cfg(dbname)]
    return $sqliteHandle
}

# Execute query and return results
proc sqliteQueryResults {cfgData query} {
    array set cfg $cfgData

    set db_file $cfg(dbname)

    if {[catch {sqlite3 db $db_file} error_msg]} {
        puts "Error connecting to database: $error_msg"
        exit 1
    }
    
    # Initialize the return array
    array set results {}

    # Initialize counters and storage
    set row 0
    set columns {}
    set hasColumns 0
    
    # Execute query and process results
    db eval $query data {
        # Get column names from the first row
        if {!$hasColumns} {
            set columns $data(*)
            set hasColumns 1
            
            # Store column names in array
            set results(columns) $columns
        }
        
        # Store each field in the array
        foreach column $columns {
            set results($row,$column) $data($column)
        }
        
        incr row
    }
    
    # Store the number of rows
    set results(rows) $row
    
    # Close database connection
    db close
    
    # Return the results array
    return [array get results]

}


