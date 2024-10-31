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
    # Initialize an empty array to store the results
    set results {}

    # Use db eval to execute the query and populate the array
    if {[catch {
        db eval $query row {
            # Create a unique key based on the first column (ArtistId) and the row number
            set rowId [set row(ArtistId)]  ;# Assuming ArtistId is the first column

            # Create a unique row index
            set index [llength [array names results $rowId*]]

            # Store the row data using the rowId and index
            set results($rowId,$index) [dict create]

            # Populate the dictionary with column names and values
            foreach {columnName columnValue} [array get row] {
                set results($rowId,$index)($columnName) $columnValue
            }
        }
    } result_msg]} {
        puts "Error executing query: $result_msg"
        return
    }

    return results

}

# Example usage:
# set cfg [dict create dbname "d:/data/sqlite_chinook.db"]
# set results [dbQueryResult sqlite_chinook "SELECT * FROM artists LIMIT 5"]
# foreach row $results {
#     puts $row
# }
