-- config.lua
-- Reads the WaSQL config.xml and returns the connection settings for a named database.
-- Mirrors R/config.R and tcl/config.tcl.
--
-- The evalLuaCode harness (php/common.php) defines the globals wasqlLuaPath() and
-- wasqlConfigFile() before this file is loaded. When run standalone (unit tests,
-- CLI) we fall back to locating config.xml relative to this file.

local config = {}

----------------------------------------------------------------------
-- begin function configScriptDir
-- @describe returns the directory this source file lives in (used for standalone fallbacks)
-- @param (none)
-- @return string - absolute or relative directory path, no trailing slash
-- @usage local dir = configScriptDir()
----------------------------------------------------------------------
local function configScriptDir()
	local src = debug.getinfo(1, "S").source
	if src:sub(1, 1) == "@" then src = src:sub(2) end
	return src:match("^(.*)[/\\]") or "."
end
config.configScriptDir = configScriptDir

----------------------------------------------------------------------
-- begin function wasqlLuaPath
-- @describe path to the WaSQL lua/ directory - honors a harness-provided global, else derives it from this file
-- @param (none)
-- @return string - directory path (no trailing slash)
-- @usage local dir = wasqlLuaPath()
----------------------------------------------------------------------
if type(_G.wasqlLuaPath) ~= "function" then
	function _G.wasqlLuaPath()
		return configScriptDir()
	end
end

----------------------------------------------------------------------
-- begin function wasqlConfigFile
-- @describe absolute path to config.xml - honors a harness-provided global, else <lua dir>/../config.xml
-- @param (none)
-- @return string - path to config.xml
-- @usage local f = wasqlConfigFile()
----------------------------------------------------------------------
if type(_G.wasqlConfigFile) ~= "function" then
	function _G.wasqlConfigFile()
		return configScriptDir() .. "/../config.xml"
	end
end

----------------------------------------------------------------------
-- begin function xmlUnescape
-- @describe expands the five predefined XML entities in an attribute value
-- @param s string - the raw attribute text
-- @return string - text with &amp; &lt; &gt; &quot; &apos; resolved
-- @usage local v = xmlUnescape(rawValue)
----------------------------------------------------------------------
local function xmlUnescape(s)
	s = s:gsub("&lt;", "<")
	s = s:gsub("&gt;", ">")
	s = s:gsub("&quot;", '"')
	s = s:gsub("&apos;", "'")
	s = s:gsub("&#(%d+);", function(d) return string.char(tonumber(d) % 256) end)
	s = s:gsub("&amp;", "&")
	return s
end
config.xmlUnescape = xmlUnescape

----------------------------------------------------------------------
-- begin function parseTagAttributes
-- @describe parses name="value" / name='value' pairs out of a raw XML tag-body fragment
-- @param blob string - the text between "<database" and the closing ">"
-- @return table - attribute name => unescaped value
-- @usage local attrs = parseTagAttributes(blob)
----------------------------------------------------------------------
local function parseTagAttributes(blob)
	local attrs = {}
	for name, q, value in blob:gmatch("([%w_:%-]+)%s*=%s*(['\"])(.-)%2") do
		attrs[name] = xmlUnescape(value)
	end
	return attrs
end
config.parseTagAttributes = parseTagAttributes

----------------------------------------------------------------------
-- begin function configParse
-- @describe reads config.xml and returns the attributes of the <database name="..."> node matching db_name
-- @param db_name string - the value of the database node's name attribute in config.xml
-- @return table - every attribute of that node as key => value (dbtype, dbhost, dbuser, dbpass, dbname, dbport, dbschema, connect, ...); raises an error if the file or node is missing
-- @usage local cfg = configParse("mydb")
----------------------------------------------------------------------
local function configParse(db_name)
	local config_file = wasqlConfigFile()
	local fh, ferr = io.open(config_file, "r")
	if not fh then
		error("Config file does not exist at: " .. tostring(config_file) .. (ferr and (" (" .. ferr .. ")") or ""))
	end
	local xml = fh:read("*a")
	fh:close()

	-- scan every <database ...> element (attributes may span multiple lines)
	for blob in xml:gmatch("<database%s+(.-)/?>") do
		local attrs = parseTagAttributes(blob)
		if attrs.name == db_name then
			return attrs
		end
	end

	error("Database configuration not found for: " .. tostring(db_name))
end
config.configParse = configParse

_G.configParse = configParse
_G.xmlUnescape = xmlUnescape

return config
