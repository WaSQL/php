-- luainfo.lua: emits the Lua interpreter version and an HTML report of loaded /
-- discoverable modules. Parallel to R/rinfo.R. Run standalone:  lua luainfo.lua

local luainfo = {}

----------------------------------------------------------------------
-- begin function htmlEscape
-- @describe escapes HTML special characters (& < > ") so a value is safe to embed in markup
-- @param text mixed - raw text (nil becomes "")
-- @return string - the escaped text
-- @usage local safe = htmlEscape(value)
----------------------------------------------------------------------
local function htmlEscape(text)
	if text == nil then return "" end
	text = tostring(text)
	text = text:gsub("&", "&amp;"):gsub("<", "&lt;"):gsub(">", "&gt;"):gsub('"', "&quot;")
	return text
end
luainfo.htmlEscape = htmlEscape

----------------------------------------------------------------------
-- begin function moduleMetadata
-- @describe builds an HTML table-row fragment describing one loaded module (type of each top-level field, plus _VERSION if present)
-- @param name string - the module key as it appears in package.loaded
-- @param mod any - the module value
-- @return string - HTML <tr> rows, one per field, or a "no introspectable fields" row
-- @usage local html = moduleMetadata("json", package.loaded.json)
----------------------------------------------------------------------
local function moduleMetadata(name, mod)
	local rows = {}
	if type(mod) == "table" then
		local keys = {}
		for k in pairs(mod) do keys[#keys + 1] = tostring(k) end
		table.sort(keys)
		for _, k in ipairs(keys) do
			local v = mod[k]
			local desc = type(v)
			if type(v) == "string" or type(v) == "number" or type(v) == "boolean" then
				desc = desc .. " = " .. tostring(v)
			end
			rows[#rows + 1] = table.concat({
				'<tr><td class="align-left w_small w_nowrap" style="width:300px;background:#c2c4c780;">',
				htmlEscape(k),
				'</td><td class="align-left w_small" style="min-width:300px;background-color:#d5d7d9"><div style="max-height:200px;overflow:auto;">',
				htmlEscape(desc),
				"</div></td></tr>",
			})
		end
	else
		rows[#rows + 1] = '<tr><td colspan="2">' .. htmlEscape(type(mod)) .. " (no introspectable fields)</td></tr>"
	end
	if #rows == 0 then
		return '<tr><td colspan="2">Metadata not available</td></tr>'
	end
	return table.concat(rows, "\n")
end
luainfo.moduleMetadata = moduleMetadata

----------------------------------------------------------------------
-- begin function render
-- @describe renders the full HTML report - a header with the Lua version, then one <section> per loaded module
-- @param (none)
-- @return string - the complete HTML fragment
-- @usage io.write(render())
----------------------------------------------------------------------
local function render(self)
	local version = _VERSION or "Lua"
	if jit and jit.version then version = version .. " / " .. jit.version end

	local out = {
		"<header>",
		'  <div style="background: linear-gradient(to right, #c2c4c7, #919198);padding:10px 20px;margin-bottom:20px;border:1px solid #919198;border-radius:4px;">',
		'    <div style="font-size:clamp(24px,3vw,48px);color:#00007d;">Lua</div>',
		'    <div style="font-size:clamp(11px,2vw,18px);color:#00007d;">' .. htmlEscape(version) .. "</div>",
		"  </div>",
		"</header>",
	}

	local names = {}
	for k in pairs(package.loaded) do names[#names + 1] = k end
	table.sort(names)

	for _, name in ipairs(names) do
		out[#out + 1] = "<section>"
		out[#out + 1] = '  <h2><a name="module_' .. htmlEscape(name) .. '">' .. htmlEscape(name) .. "</a></h2>"
		out[#out + 1] = "  <table>" .. moduleMetadata(name, package.loaded[name]) .. "</table>"
		out[#out + 1] = "</section>"
	end

	return table.concat(out, "\n")
end
luainfo.render = render

_G.luainfoRender = render

-- when executed directly (not require'd), print the report
if not pcall(debug.getlocal, 4, 1) then
	io.write(render())
	io.write("\n")
end

return luainfo
