-- common.lua
-- WaSQL shared helper functions for embedded Lua scripts.
-- Mirrors R/common.R and tcl/common.tcl. Loaded by the evalLuaCode harness
-- (see php/common.php) alongside wasql_<md5>.lua, config.lua and db.lua.
--
-- Every function is registered both as a field on the returned module table
-- and as a global, so page code can call e.g. commonFormatPhone(x) directly
-- (matching the flat namespace R and Tcl expose).

local common = {}

-- optional pretty-JSON encoder (php/extras/json.lua -> copied to temp as json.lua)
local ok_json, json = pcall(require, "json")
if not ok_json then json = nil end

----------------------------------------------------------------------
-- begin function escapeLuaPattern
-- @describe escapes the Lua-pattern magic characters in a string so it can be used as a literal match
-- @param s string - the raw text
-- @return string - the text with ( ) . % + - * ? [ ] ^ $ each prefixed by %
-- @usage local safe = escapeLuaPattern(userInput)
----------------------------------------------------------------------
local function escapeLuaPattern(s)
	return (s:gsub("([%(%)%.%%%+%-%*%?%[%]%^%$])", "%%%1"))
end
common.escapeLuaPattern = escapeLuaPattern

-- extended-Latin -> ASCII fold table, built once at module load and consumed
-- by convertExtendedCharacters() below. Kept above that function's doc block so
-- the block stays adjacent to the function (the manual parser reads it there).
local NORMALIZE_PAIRS = [[
Å A Æ A À A Á A Â A Ã A Ä A Ă A Ā A Ą A
Ç C Ć C Ĉ C Ċ C Č C
È E É E Ê E Ë E Ð E Ē E Ĕ E Ė E Ę E Ě E
Ƒ F
Ğ G Ġ G Ģ G
Ĥ H Ħ H
Ì I Í I Î I Ï I Ĩ I Ī I Ĭ I Į I İ I Ĳ I
Ĵ J
Ķ K ĸ K
Ĺ L Ļ L Ľ L Ŀ L Ł L
Ñ N Ń N Ņ N Ň N Ŋ N
Ò O Ó O Ô O Õ O Ö O Ø O Ŏ O Ő O Œ O
Þ P
Ŕ R Ŗ R Ř R
Š S Ș S Ś S Ŝ S Ş S ſ S
Ț T Ţ T Ť T Ŧ T
Ù U Ú U Û U Ü U Ũ U Ū U Ŭ U Ů U Ű U Ų U
Ŵ W
Ý Y Ÿ Y Ŷ Y
Ž Z Ź Z Ż Z
å a æ a à a á a â a ã a ä a ă a ā a ą a
ç c ć c ĉ c ċ c č c
è e é e ê e ë e ð e ē e ĕ e ė e ę e ě e
ƒ f
ğ g ġ g ģ g
ĥ h ħ h
ì i í i î i ï i ĩ i ī i ĭ i į i ĳ i
ĵ j
ķ k ĸ k
ĺ l ļ l ľ l ŀ l ł l
ñ n ń n ņ n ň n ŉ n ŋ n
ò o ó o ô o õ o ö o ø o ŏ o ő o œ o
þ p
ŕ r ŗ r ř r
š s ș s ß ss ś s ŝ s ş s
ț t ţ t ť t ŧ t
ù u ú u û u ü u ũ u ū u ŭ u ů u ű u ų u
ŵ w
ý y ÿ y ŷ y
ž z ź z ż z
]]

local NORMALIZE_MAP = {}
for from, to in NORMALIZE_PAIRS:gmatch("(%S+)%s+(%S+)") do
	NORMALIZE_MAP[from] = to
end

----------------------------------------------------------------------
-- begin function convertExtendedCharacters
-- @describe normalizes accented / extended Latin characters to their plain ASCII equivalents (accents stripped, ligatures expanded, e.g. "e"->"e", "ss"->"ss")
-- @param str string - the text to normalize (assumed UTF-8)
-- @return string - the text with extended Latin characters folded to ASCII
-- @usage local clean = convertExtendedCharacters(name)
----------------------------------------------------------------------
local function convertExtendedCharacters(str)
	if str == nil then return "" end
	str = tostring(str)
	for from, to in pairs(NORMALIZE_MAP) do
		str = str:gsub(escapeLuaPattern(from), to)
	end
	return str
end
common.convertExtendedCharacters = convertExtendedCharacters

----------------------------------------------------------------------
-- begin function commonStrlen
-- @describe wrapper for string length that also handles nil, numbers, and tables (JSON-encoded first)
-- @param s mixed - a string, number, table, or nil
-- @return integer - the character length (0 for nil or an empty string)
-- @usage if commonStrlen(x) > 0 then ... end
----------------------------------------------------------------------
local function commonStrlen(s)
	if s == nil then return 0 end
	local t = type(s)
	if t == "string" then return #s end
	if t == "number" or t == "boolean" then return #tostring(s) end
	if t == "table" then
		if json then
			return #json.encode(s)
		end
		local n = 0
		for _ in pairs(s) do n = n + 1 end
		return n
	end
	return #tostring(s)
end
common.commonStrlen = commonStrlen

----------------------------------------------------------------------
-- begin function commonFormatPhone
-- @describe strips non-digits from a phone number and formats it by length (7 -> nnn-nnnn, 10 -> (nnn) nnn-nnnn, 11 -> n(nnn) nnn-nnnn)
-- @param phone string - a raw phone number in any format
-- @return string - the formatted number, "" if shorter than 4 chars, or the digit string unchanged for other lengths
-- @usage local pretty = commonFormatPhone("8014584741")
----------------------------------------------------------------------
local function commonFormatPhone(phone)
	if phone == nil then return "" end
	phone = tostring(phone)
	-- Making sure we have something
	if #phone < 4 then return "" end
	-- Strip out everything but numbers
	phone = phone:gsub("[^0-9]", "")
	local length = #phone
	if length == 7 then
		return (phone:gsub("(%d%d%d)(%d%d%d%d)", "%1-%2"))
	elseif length == 10 then
		return (phone:gsub("(%d%d%d)(%d%d%d)(%d%d%d%d)", "(%1) %2-%3"))
	elseif length == 11 then
		return (phone:gsub("(%d)(%d%d%d)(%d%d%d)(%d%d%d%d)", "%1(%2) %3-%4"))
	end
	return phone
end
common.commonFormatPhone = commonFormatPhone

----------------------------------------------------------------------
-- begin function parseHtmlTagAttributes
-- @describe extracts name/value attribute pairs from an HTML tag string, lowercasing names and stripping surrounding quotes from values
-- @param text string - an HTML tag or attribute fragment
-- @return table - attribute name => value (value "" for valueless attributes)
-- @usage local attrs = parseHtmlTagAttributes('<a href="/x" target="_blank">')
----------------------------------------------------------------------
local function parseHtmlTagAttributes(text)
	local attributes = {}
	if text == nil then return attributes end
	text = tostring(text)
	-- drop the leading "<tagname" and any trailing ">" so we only scan attributes
	text = text:gsub("^%s*<%s*[%a][%w:_%-]*", "")
	text = text:gsub("/?%s*>%s*$", "")

	local i, n = 1, #text
	while i <= n do
		-- skip whitespace
		local ws = text:match("^%s*", i)
		i = i + #ws
		if i > n then break end
		local name = text:match("^([%a][%w:_%-]*)", i)
		if not name then
			i = i + 1
		else
			i = i + #name
			local key = name:lower()
			local eq = text:match("^%s*=%s*", i)
			if eq then
				i = i + #eq
				local q = text:sub(i, i)
				local value
				if q == '"' or q == "'" then
					value = text:match("^" .. q .. "([^" .. q .. "]*)" .. q, i)
					if value == nil then
						value = text:sub(i + 1)
						i = n + 1
					else
						i = i + #value + 2
					end
				else
					value = text:match("^([^%s>]+)", i) or ""
					i = i + #value
				end
				attributes[key] = value
			else
				attributes[key] = ""
			end
		end
	end
	return attributes
end
common.parseHtmlTagAttributes = parseHtmlTagAttributes

----------------------------------------------------------------------
-- begin function htmlEscape
-- @describe escapes HTML special characters (& < > " ') in a string so it is safe to embed in markup
-- @param text mixed - raw text (nil becomes "")
-- @return string - the escaped text
-- @usage html = html .. "<td>" .. htmlEscape(value) .. "</td>"
----------------------------------------------------------------------
local function htmlEscape(text)
	if text == nil then return "" end
	text = tostring(text)
	text = text:gsub("&", "&amp;")
	text = text:gsub("<", "&lt;")
	text = text:gsub(">", "&gt;")
	text = text:gsub('"', "&quot;")
	text = text:gsub("'", "&#39;")
	return text
end
common.htmlEscape = htmlEscape

----------------------------------------------------------------------
-- csvField - internal helper for resultsAsCSV()
-- @exclude - private helper, not part of the public API
----------------------------------------------------------------------
local function csvField(v)
	v = (v == nil) and "" or tostring(v)
	if v:find('[",\r\n]') then
		v = '"' .. v:gsub('"', '""') .. '"'
	end
	return v
end

----------------------------------------------------------------------
-- begin function resultsAsCSV
-- @describe converts a query-results table (as returned by dbQueryResults / *QueryResults) into a CSV string, quoting fields that contain a comma, quote or newline
-- @param results table - { columns = {..}, rows = { {col=val,..}, .. }, count = n }
-- @return string - CSV text with a header row, or "No results found" when there are no rows
-- @usage print(resultsAsCSV(dbQueryResults("mydb", "SELECT * FROM users")))
----------------------------------------------------------------------
local function resultsAsCSV(results)
	if type(results) ~= "table" or not results.columns or (results.count or #(results.rows or {})) == 0 then
		return "No results found"
	end
	local out = {}
	local header = {}
	for _, c in ipairs(results.columns) do header[#header + 1] = csvField(c) end
	out[#out + 1] = table.concat(header, ",")
	for _, row in ipairs(results.rows) do
		local line = {}
		for _, c in ipairs(results.columns) do line[#line + 1] = csvField(row[c]) end
		out[#out + 1] = table.concat(line, ",")
	end
	return table.concat(out, "\n") .. "\n"
end
common.resultsAsCSV = resultsAsCSV

----------------------------------------------------------------------
-- begin function resultsAsTable
-- @describe converts a query-results table into an HTML <table> (class "table bordered striped"), HTML-escaping every cell
-- @param results table - { columns = {..}, rows = { {col=val,..}, .. }, count = n }
-- @return string - HTML markup, or "<p>No results found</p>" when there are no rows
-- @usage print(resultsAsTable(dbQueryResults("mydb", "SELECT * FROM users")))
----------------------------------------------------------------------
local function resultsAsTable(results)
	if type(results) ~= "table" or not results.columns or (results.count or #(results.rows or {})) == 0 then
		return "<p>No results found</p>"
	end
	local html = { '<table class="table bordered striped">', "<thead><tr>" }
	for _, c in ipairs(results.columns) do
		html[#html + 1] = "<th>" .. htmlEscape(c) .. "</th>"
	end
	html[#html + 1] = "</tr></thead>"
	html[#html + 1] = "<tbody>"
	for _, row in ipairs(results.rows) do
		html[#html + 1] = "<tr>"
		for _, c in ipairs(results.columns) do
			html[#html + 1] = "<td>" .. htmlEscape(row[c]) .. "</td>"
		end
		html[#html + 1] = "</tr>"
	end
	html[#html + 1] = "</tbody>"
	html[#html + 1] = "</table>"
	return table.concat(html, "\n")
end
common.resultsAsTable = resultsAsTable

----------------------------------------------------------------------
-- begin function luaVersion
-- @describe returns the interpreter version string (_VERSION plus the LuaJIT banner when running under LuaJIT)
-- @param (none)
-- @return string - e.g. "Lua 5.4" or "Lua 5.1 / LuaJIT 2.1.0"
-- @usage local v = luaVersion()
----------------------------------------------------------------------
local function luaVersion()
	local v = _VERSION or "Lua"
	if jit and jit.version then
		v = v .. " / " .. jit.version
	end
	return v
end
common.luaVersion = luaVersion

-- expose every helper as a global for flat-namespace parity with R / Tcl
for k, v in pairs(common) do
	_G[k] = v
end

return common
