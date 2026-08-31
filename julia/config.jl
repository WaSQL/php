"""
config.jl - WaSQL config.xml reader for Julia

Julia port of R/config.R and tcl/config.tcl. Provides configParse(dbname), which
returns the connection settings for a named <database> node in config.xml.

Loaded automatically by db.jl. No external packages required - config.xml is a
flat list of self-closing <database .../> elements, parsed here without an XML
library (mirroring the lightweight parsing used elsewhere in the framework).
"""

#---------- begin function wasqlConfigFile
# @describe Locates the WaSQL config.xml; walks up from this directory looking for a sibling config.xml, then tries a few common install paths
# @return String absolute path to config.xml (the best guess even if the file is absent)
# @usage
#   path = wasqlConfigFile()
function wasqlConfigFile()
    dir = @__DIR__
    for _ in 1:6
        candidate = joinpath(dir, "config.xml")
        isfile(candidate) && return candidate
        parent = dirname(dir)
        parent == dir && break
        dir = parent
    end
    for base in ("D:/wasql", "C:/wasql", "/var/www/wasql", "/opt/wasql")
        candidate = joinpath(base, "config.xml")
        isfile(candidate) && return candidate
    end
    return joinpath(dirname(@__DIR__), "config.xml")
end

#---------- begin function configParseAttributes
# @describe Parses key="value" / key='value' attribute pairs out of an XML element's attribute text
# @param attrtext AbstractString the text between the tag name and the closing >
# @return Dict{String,String} attribute name (lowercased) => unquoted value
# @usage
#   attrs = configParseAttributes(raw)
function configParseAttributes(attrtext::AbstractString)
    attrs = Dict{String,String}()
    pattern = r"([a-zA-Z_][a-zA-Z0-9_\-:]*)\s*=\s*(\"[^\"]*\"|'[^']*'|[^\s>]+)"
    for m in eachmatch(pattern, attrtext)
        attrs[lowercase(m.captures[1])] = strip(m.captures[2], ('"', '\''))
    end
    return attrs
end

#---------- begin function configParse
# @describe Reads config.xml and returns the connection settings for the <database> node whose name attribute matches dbname
# @param dbname AbstractString the database connection name as it appears in config.xml
# @param config_file AbstractString path to config.xml (defaults to wasqlConfigFile())
# @return Dict{String,String} every attribute of the matching node (dbtype, dbhost, dbuser, dbpass, dbname, dbschema, dbport, ...); throws when the file or the node is missing
# @usage
#   cfg = configParse("mydb")
#   cfg["dbtype"]
function configParse(dbname::AbstractString, config_file::AbstractString = wasqlConfigFile())
    isfile(config_file) || error("Config file does not exist at: $config_file")
    xml = read(config_file, String)
    for m in eachmatch(r"<database\b([^>]*?)/?>"s, xml)
        attrs = configParseAttributes(m.captures[1])
        if get(attrs, "name", "") == dbname
            return attrs
        end
    end
    error("Database configuration not found for: $dbname")
end
