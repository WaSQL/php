#http://tdom.org/downloads/
package require tdom

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
