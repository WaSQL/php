#http://tdom.org/downloads/
package require tdom

#----------
# begin function configParse
# @describe reads the WaSQL config XML (wasqlConfigFile) and returns the attributes of the <database name="..."> node matching dbname
# @param dbname string - the database connection name as it appears in config.xml
# @return list - flat attr/value pairs (suitable for "array set"); raises an error if the file or database node is missing
# @usage array set cfg [configParse $db_name]
#----------
proc configParse {dbname} {
    set config_file [wasqlConfigFile]
    # Initialize the return array
    set result []
    
    # Read the XML file
    if {[catch {open $config_file r} f]} {
        error "Cannot open config file: $config_file"
    }
    set xmlContent [read $f]
    close $f
    
    # Parse XML content
    set doc [dom parse $xmlContent]
    set root [$doc documentElement]
    
    # Find the specific database element matching the name
    set dbNode [$root selectNodes "//database\[@name='$dbname'\]"]
    
    # Check if we found the database
    if {$dbNode eq ""} {
        error "Database '$dbname' not found in config"
    }
    
    # Get all attributes for this database
    foreach attr [$dbNode attributes] {
        set value [$dbNode getAttribute $attr]
        lappend result $attr $value
    }
    
    # Clean up DOM tree
    $doc delete
    
    return $result
}
