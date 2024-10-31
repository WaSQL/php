# mysqldb.tcl

# Load the necessary package
package require tclodbc

# Function to connect to the Microsoft SQL Server database
proc mssqlConnect {cfg} {
    # Use localhost if cfg(dbhost) is not set
    set host [dict get $cfg dbhost]
    if {![info exists host] || $host eq ""} {
        set host "localhost"
    }

    # Use default port 1433 if cfg(dbport) is not set
    set port [dict get $cfg dbport]
    if {![info exists port] || $port eq ""} {
        set port 1433
    }

    # Create the connection string
    set connString "Driver={ODBC Driver 17 for SQL Server};Server=$host,$port;Database=[dict get $cfg dbname];UID=[dict get $cfg dbuser];PWD=[dict get $cfg dbpass];"

    # Connect to the database
    return [odbc::connect $connString]
}

# Function to fetch data from a specified query
proc mssqlQueryResults {cfg query} {
    # Get the database connection using mssqlConnect
    set dbh_r [mssqlConnect $cfg]

    # Run the query
    set result [odbc::exec $dbh_r $query]

    # Fetch and print the results in CSV format
    set column_names [odbc::fetch $result]
    puts [join $column_names ","]  ; # Print column headers

    while {[set row [odbc::fetch $result]] ne ""} {
        puts [join $row ","]
    }

    # Clean up
    odbc::free_result $result
    odbc::disconnect $dbh_r
}

# Example usage
set config [dict create dbhost "your_host" dbport 1433 dbname "your_database" dbuser "your_username" dbpass "your_password"]
set query "SELECT * FROM your_table;"
mssqlQueryResults $config $query
