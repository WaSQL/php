package require sqlite3
source "config.tcl"  ;# Update this path to the location of config.tcl

proc wasqlConfigFile {} {
    return "d:/wasql/config.xml"
}

# Fetch configuration for a specific database
set db_config [configParse "sqlite_chinook"]  ;# Replace "my_database" with the correct dbname from your config

# Extract the database file path from the config
array set cfg $db_config
set db_file $cfg(dbname)

if {[catch {sqlite3 db $db_file} error_msg]} {
    puts "Error connecting to database: $error_msg"
    exit 1
}

if {[catch {db eval {SELECT ArtistId, Name FROM artists LIMIT 5}} result_msg]} {
    puts "Error executing query: $result_msg"
    db close
    exit 1
}

db eval {SELECT ArtistId, Name FROM artists LIMIT 5} row {
    puts "$row(ArtistId) $row(Name)"
}

db close
