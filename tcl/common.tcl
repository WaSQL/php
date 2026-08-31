#----------
# begin function tclVersion
# @describe returns the full Tcl patchlevel string for the running interpreter
# @param (none)
# @return string - e.g. "8.6.13"
# @usage set v [tclVersion]
#----------
proc tclVersion {} {
    return [info patchlevel]
}

#----------
# begin function listBuiltinNamespaces
# @describe inspects known built-in Tcl namespaces and collects their variables/values into a dict
# @param (none)
# @return dict - keyed by namespace name (::tcl_platform, ::env, ::errorCode, ::errorInfo, ::auto_path)
# @usage set info [listBuiltinNamespaces]
#----------
proc listBuiltinNamespaces {} {
    # Known built-in Tcl namespaces
    set builtinNamespaces {
        ::tcl_platform
        ::env
        ::errorCode
        ::errorInfo
        ::auto_path
    }
    
    # Results dictionary to store findings
    set results [dict create]
    
    # Explore each namespace
    foreach ns $builtinNamespaces {
        # Skip if namespace doesn't exist
        if {![namespace exists $ns]} continue
        
        # Create a list to store variables
        set nsVars {}
        
        # Special handling for some namespaces
        switch $ns {
            "::tcl_platform" {
                # Predefined platform information
                dict set results $ns [array get ::tcl_platform]
            }
            "::env" {
                # Environment variables
                dict set results $ns [array get ::env]
            }
            "::errorCode" {
                # Last error code
                dict set results $ns [list value $::errorCode]
            }
            "::errorInfo" {
                # Last error info
                dict set results $ns [list value $::errorInfo]
            }
            "::auto_path" {
                # Library search paths
                dict set results $ns $::auto_path
            }
        }
    }
    
    return $results
}

#----------
# begin function exploreAutoPath
# @describe prints the contents of ::auto_path plus the sub-directories found under each existing path
# @param (none)
# @return void - writes a report to stdout via puts
# @usage exploreAutoPath
#----------
proc exploreAutoPath {} {
    puts "Auto Path Details:"
    puts "----------------"
    puts "Total Paths: [llength $::auto_path]"
    puts "\nPaths:"
    foreach path $::auto_path {
        puts "- $path"
        if {[file exists $path]} {
            puts "  [glob -nocomplain -type d $path/*]"
        }
    }
}

#----------
# begin function exploreEnvVars
# @describe prints every environment variable (::env) as an aligned "KEY : VALUE" list
# @param (none)
# @return void - writes to stdout via puts
# @usage exploreEnvVars
#----------
proc exploreEnvVars {} {
    puts "Environment Variables:"
    puts "--------------------"
    foreach {key value} [array get ::env] {
        puts "[format "%-20s : %s" $key $value]"
    }
}

#----------
# begin function tclVariableInfo
# @describe gathers a snapshot of interpreter state - global vars, loaded packages, platform config and cwd
# @param (none)
# @return dict - keys: global_vars, loaded_packages, config, pwd
# @usage set snapshot [tclVariableInfo]
#----------
proc tclVariableInfo {} {
    set info [dict create]
    
    # Global variables and their values
    dict set info global_vars [info globals]
    
    # Loaded packages
    dict set info loaded_packages [package names]
    
    # Tcl configuration information
    dict set info config [array get ::tcl_platform]
    
    # Current working directory
    dict set info pwd [pwd]
    
    return $info
}

#----------
# begin function demonstrateTclVariables
# @describe demo/diagnostic routine that prints platform info, the first 10 env vars, auto_path and error vars
# @param (none)
# @return void - writes to stdout via puts/parray
# @usage demonstrateTclVariables
#----------
proc demonstrateTclVariables {} {
    puts "===== Tcl Built-in Namespaces and Variables ====="
    
    puts "\n1. Platform Information:"
    parray ::tcl_platform
    
    puts "\n2. Environment Variables (first 10):"
    set count 0
    foreach {key value} [array get ::env] {
        puts "[format "%-20s : %s" $key $value]"
        incr count
        if {$count >= 10} break
    }
    
    puts "\n3. Auto Path:"
    puts $::auto_path
    
    puts "\n4. Error Handling Variables:"
    puts "Error Code: \$::errorCode"
    puts "Error Info: \$::errorInfo"
}
#----------
# begin function resultsAsCSV
# @describe converts a query-results array (as returned by *QueryResults) into a CSV string, quoting fields containing commas
# @param results list - the "array get" list form of a results array (keys: columns, rows, <row>,<column>)
# @return string - CSV text with a header row, or "No results found" when rows == 0
# @usage puts [resultsAsCSV [sqliteQueryResults $cfg $query]]
#----------
proc resultsAsCSV {results} {
    # Convert results list back to array
    array set resultsArray $results
    
    # If no results, return early
    if {$resultsArray(rows) == 0} {
        return "No results found"
    }
    
    # Build CSV string starting with headers
    set csv ""
    append csv [join $resultsArray(columns) ","]
    append csv "\n"
    
    # Add each row of data
    for {set i 0} {$i < $resultsArray(rows)} {incr i} {
        set row {}
        foreach column $resultsArray(columns) {
            # Handle fields that might contain commas by quoting them
            set value $resultsArray($i,$column)
            if {[string first "," $value] != -1} {
                set value "\"$value\""
            }
            lappend row $value
        }
        append csv [join $row ","]
        append csv "\n"
    }
    
    return $csv
}

# Example usage:
#
# # First get results from database
# set query {SELECT name, email, age FROM users}
# set results [sqliteQueryResults $query]
#
# # Convert to CSV
# puts [resultsAsCSV $results]
#
# # Or save to file:
# set csv [resultsAsCSV $results]
# set fileId [open "output.csv" w]
# puts $fileId $csv
# close $fileId
# 
#----------
# begin function resultsAsTable
# @describe converts a query-results array into an HTML <table> (class "table bordered striped"), HTML-escaping every cell
# @param results list - the "array get" list form of a results array (keys: columns, rows, <row>,<column>)
# @return string - HTML markup, or "<p>No results found</p>" when rows == 0
# @usage puts [resultsAsTable [sqliteQueryResults $cfg $query]]
#----------
proc resultsAsTable {results} {
    # Convert results list back to array
    array set resultsArray $results
    
    # If no results, return early
    if {$resultsArray(rows) == 0} {
        return "<p>No results found</p>"
    }
    
    # Start building HTML with some basic styling
    set html {
    }
    
    # Start table
    append html "<table class=\"table bordered striped\">\n"
    
    # Add header row
    append html "<thead><tr>\n"
    foreach column $resultsArray(columns) {
        append html "<th>[htmlEscape $column]</th>\n"
    }
    append html "</tr></thead>\n"
    
    # Add data rows
    append html "<tbody>\n"
    for {set i 0} {$i < $resultsArray(rows)} {incr i} {
        append html "<tr>\n"
        foreach column $resultsArray(columns) {
            append html "<td>[htmlEscape $resultsArray($i,$column)]</td>\n"
        }
        append html "</tr>\n"
    }
    append html "</tbody>\n"
    
    # Close table
    append html "</table>"
    
    return $html
}

#----------
# begin function htmlEscape
# @describe escapes HTML special characters (& < > " ') in a string so it is safe to embed in markup
# @param text string - raw text
# @return string - the escaped text
# @usage append html "<td>[htmlEscape $value]</td>"
#----------
proc htmlEscape {text} {
    set escapes {
        & &amp;
        < &lt;
        > &gt;
        \" &quot;
        ' &#39;
    }
    
    set result $text
    foreach {from to} $escapes {
        regsub -all $from $result $to result
    }
    return $result
}

# Example usage:
#
# # Get data from database
# set query {SELECT name, email, age FROM users}
# set results [sqliteQueryResults $query]
#
# # Convert to HTML table
# set html [resultsAsTable $results]
#
# # Save to file if needed
# set fileId [open "results.html" w]
# puts $fileId $html
# close $fileId
#----------
# begin function convertExtendedCharacters
# @describe transliterates accented / extended Latin characters to their plain ASCII equivalents (e.g. "e" -> "e", "ss" -> "ss")
# @param string string - text that may contain accented characters
# @return string - the normalized ASCII text
# @usage set clean [convertExtendedCharacters $name]
#----------
proc convertExtendedCharacters {string} {
    # Define the character mapping
    set normalizeChars {
        "Å" "A" "Æ" "A" "À" "A" "Á" "A" "Â" "A" "Ã" "A" "Ä" "A" "Ă" "A" "Ā" "A" "Ą" "A"
        "Ç" "C" "Ć" "C" "Ĉ" "C" "Ċ" "C" "Č" "C"
        "È" "E" "É" "E" "Ê" "E" "Ë" "E" "Ð" "E" "Ē" "E" "Ĕ" "E" "Ė" "E" "Ę" "E" "Ě" "E"
        "Ƒ" "F"
        "Ğ" "G" "Ġ" "G" "Ģ" "G"
        "Ĥ" "H" "Ħ" "H"
        "Ì" "I" "Í" "I" "Î" "I" "Ï" "I" "Ĩ" "I" "Ī" "I" "Ĭ" "I" "Į" "I" "İ" "I" "Ĳ" "I"
        "Ĵ" "J"
        "Ķ" "K" "ĸ" "K"
        "Ĺ" "L" "Ļ" "L" "Ľ" "L" "Ŀ" "L" "Ł" "L"
        "Ñ" "N" "Ń" "N" "Ņ" "N" "Ň" "N" "ʼN" "N" "Ŋ" "N"
        "Ò" "O" "Ó" "O" "Ô" "O" "Õ" "O" "Ö" "O" "Ø" "O" "Ŏ" "O" "Ő" "O" "Œ" "O"
        "Þ" "P"
        "Ŕ" "R" "Ŗ" "R" "Ř" "R"
        "Š" "S" "Ș" "S" "Ś" "S" "Ŝ" "S" "Ş" "S" "ſ" "S"
        "Ț" "T" "Ţ" "T" "Ť" "T" "Ŧ" "T"
        "Ù" "U" "Ú" "U" "Û" "U" "Ü" "U" "Ũ" "U" "Ū" "U" "Ŭ" "U" "Ů" "U" "Ű" "U" "Ų" "U"
        "Ŵ" "W"
        "Ý" "Y" "Ÿ" "Y" "Ŷ" "Y"
        "Ž" "Z" "Ź" "Z" "Ż" "Z"
        "å" "a" "æ" "a" "à" "a" "á" "a" "â" "a" "ã" "a" "ä" "a" "ă" "a" "ā" "a" "ą" "a"
        "ç" "c" "ć" "c" "ĉ" "c" "ċ" "c" "č" "c"
        "è" "e" "é" "e" "ê" "e" "ë" "e" "ð" "e" "ē" "e" "ĕ" "e" "ė" "e" "ę" "e" "ě" "e"
        "ƒ" "f"
        "ğ" "g" "ġ" "g" "ģ" "g"
        "ĥ" "h" "ħ" "h"
        "ì" "i" "í" "i" "î" "i" "ï" "i" "ĩ" "i" "ī" "i" "ĭ" "i" "į" "i" "i̇" "i" "ĳ" "i"
        "ĵ" "j"
        "ķ" "k" "ĸ" "k"
        "ĺ" "l" "ļ" "l" "ľ" "l" "ŀ" "l" "ł" "l"
        "ñ" "n" "ń" "n" "ņ" "n" "ň" "n" "ŉ" "n" "ŋ" "n"
        "ò" "o" "ó" "o" "ô" "o" "õ" "o" "ö" "o" "ø" "o" "ŏ" "o" "ő" "o" "œ" "o"
        "þ" "p"
        "ŕ" "r" "ŗ" "r" "ř" "r"
        "š" "s" "ș" "s" "ß" "ss" "ś" "s" "ŝ" "s" "ş" "s" "ſ" "s"
        "ț" "t" "ţ" "t" "ť" "t" "ŧ" "t"
        "ù" "u" "ú" "u" "û" "u" "ü" "u" "ũ" "u" "ū" "u" "ŭ" "u" "ů" "u" "ű" "u" "ų" "u"
        "ŵ" "w"
        "ý" "y" "ÿ" "y" "ŷ" "y"
        "ž" "z" "ź" "z" "ż" "z"
    }

    # Replace each extended character in the string
    foreach {char newChar} [dict get normalizeChars] {
        set string [regsub $char $string $newChar]
    }

    return $string
}

#----------
# begin function commonStrlen
# @describe wrapper for string length that also handles list/object-ish input by flattening it first
# @param s mixed - a string, or a space-separated list
# @return integer - the character length (0 for an empty string)
# @usage if {[commonStrlen $x]} {...}
#----------
proc commonStrlen {s} {
    if {$s eq ""} {
        return 0
    }
    
    # Check if the input is a list or an object
    if {[llength [split $s " "]] > 1} {
        # Convert list or object to JSON string (using built-in features)
        set s [join [split $s " "] ","]
    }
    
    return [string length $s]
}

#----------
# begin function commonFormatPhone
# @describe strips non-digits from a phone number and formats it by length (7 -> nnn-nnnn, 10 -> (nnn) nnn-nnnn, 11 -> n(nnn) nnn-nnnn)
# @param phone string - a raw phone number in any format
# @return string - the formatted number, "" if shorter than 4 chars, or the digit string unchanged for other lengths
# @usage set pretty [commonFormatPhone $row(phone)]
#----------
proc commonFormatPhone {phone} {
    # Making sure we have something
    if {[string length $phone] < 4} {
        return ""
    }
    
    # Strip out everything but numbers
    set phone [regsub "[^0-9]" $phone ""]
    set length [string length $phone]

    switch $length {
        7 {
            return [regsub {([0-9]{3})([0-9]{4})} $phone {\1-\2}]
        }
        10 {
            return [regsub {([0-9]{3})([0-9]{3})([0-9]{4})} $phone {(\1) \2-\3}]
        }
        11 {
            return [regsub {([0-9]{1})([0-9]{3})([0-9]{3})([0-9]{4})} $phone {\1(\2) \3-\4}]
        }
        default {
            return $phone ; # Return the unformatted phone
        }
    }
}

#----------
# begin function parseHtmlTagAttributes
# @describe extracts name/value attribute pairs from an HTML tag string, lowercasing names and stripping surrounding quotes from values
# @param text string - an HTML tag or attribute fragment
# @return dict - attribute name => value (value "" for valueless attributes)
# @usage set attrs [parseHtmlTagAttributes {<a href="/x" target="_blank">}]
#----------
proc parseHtmlTagAttributes {text} {
    set attributes [dict create]
    
    # Define the regex pattern to match HTML tag attributes
    set pattern "(?:(?<name>[a-zA-Z][a-zA-Z0-9\\-:_]*)(?:(=)(?:(?:(\"[^\"]+\")|('[^']+')|([^\\s>]+))))?)"
    
    # Use regexp to find all matches of the pattern in the text
    set matches [regexp -all -inline $pattern $text]
    
    # Iterate through matches
    foreach match $matches {
        set name [string tolower [lindex $match 1]]
        set value ""

        if {[llength $match] > 3} {
            set value [lindex $match 3]
            set value [regsub {^['"]|['"]$} $value ""]
        }
        
        # Store in attributes dictionary
        dict set attributes $name $value
    }
    
    return $attributes
}
