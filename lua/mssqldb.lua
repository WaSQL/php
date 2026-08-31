-- mssqldb.lua
-- Microsoft SQL Server driver for WaSQL embedded Lua. Uses LuaSQL ODBC (luasql.odbc).
--   luarocks install luasql-odbc
-- Needs an ODBC driver installed ("ODBC Driver 17 for SQL Server").
-- Mirrors R/mssqldb.R and tcl/mssqldb.tcl.

local mssqldb = {}

----------------------------------------------------------------------
-- begin function cursorToResults
-- @describe drains a LuaSQL cursor into the standard WaSQL results table and closes it
-- @param cur userdata - an open LuaSQL cursor, or a number for non-SELECT statements
-- @return table - { columns = {..}, rows = { {col=val,..}, .. }, count = n }
-- @usage local res = cursorToResults(conn:execute(query))
----------------------------------------------------------------------
local function cursorToResults(cur)
	local results = { columns = {}, rows = {}, count = 0 }
	if type(cur) ~= "userdata" then
		results.count = tonumber(cur) or 0
		return results
	end
	results.columns = cur:getcolnames() or {}
	local row = cur:fetch({}, "a")
	while row do
		local copy = {}
		for k, v in pairs(row) do copy[k] = v end
		results.rows[#results.rows + 1] = copy
		results.count = results.count + 1
		row = cur:fetch({}, "a")
	end
	cur:close()
	return results
end
mssqldb.cursorToResults = cursorToResults

----------------------------------------------------------------------
-- begin function mssqlConnectionString
-- @describe builds an ODBC connection string for SQL Server from a parsed config table (defaults host to localhost, port to 1433)
-- @param cfg table - connection settings from configParse (connect, dbhost, dbport, dbuser, dbpass, dbname, dbdriver)
-- @return string - a "Driver={...};Server=host,port;Database=..;UID=..;PWD=.." string (or cfg.connect verbatim, with UID/PWD appended)
-- @usage local cs = mssqlConnectionString(cfg)
----------------------------------------------------------------------
local function mssqlConnectionString(cfg)
	local user = cfg.dbuser or ""
	local password = cfg.dbpass or ""
	if cfg.connect ~= nil and cfg.connect ~= "" then
		return cfg.connect .. ";UID=" .. user .. ";PWD=" .. password
	end
	local host = (cfg.dbhost ~= nil and cfg.dbhost ~= "") and cfg.dbhost or "localhost"
	local port = tonumber(cfg.dbport) or 1433
	local driver = (cfg.dbdriver ~= nil and cfg.dbdriver ~= "") and cfg.dbdriver or "ODBC Driver 17 for SQL Server"
	return table.concat({
		"Driver={" .. driver .. "}",
		"Server=" .. host .. "," .. port,
		"Database=" .. (cfg.dbname or ""),
		"UID=" .. user,
		"PWD=" .. password,
	}, ";")
end
mssqldb.mssqlConnectionString = mssqlConnectionString

----------------------------------------------------------------------
-- begin function mssqlConnect
-- @describe opens a Microsoft SQL Server connection via ODBC
-- @param cfg table - connection settings from configParse
-- @return userdata, userdata - the open connection and its LuaSQL environment (close both when done); raises an error on failure
-- @usage local conn, env = mssqlConnect(cfg)
----------------------------------------------------------------------
local function mssqlConnect(cfg)
	local luasql = require("luasql.odbc")
	local env = luasql.odbc()
	local conn, err = env:connect(mssqlConnectionString(cfg))
	if not conn then
		env:close()
		error("Failed to connect to SQL Server: " .. tostring(err))
	end
	return conn, env
end
mssqldb.mssqlConnect = mssqlConnect

----------------------------------------------------------------------
-- begin function mssqlQueryResults
-- @describe connects, runs a query against SQL Server, disconnects, and returns the rows
-- @param cfg table - connection settings from configParse
-- @param query string - the SQL query to run
-- @return table - { columns = {..}, rows = { {col=val,..}, .. }, count = n }; raises an error on connect/query failure
-- @usage local res = mssqlQueryResults(cfg, "SELECT * FROM Users")
----------------------------------------------------------------------
local function mssqlQueryResults(cfg, query)
	local conn, env = mssqlConnect(cfg)
	local cur, err = conn:execute(query)
	if cur == nil and err ~= nil then
		conn:close(); env:close()
		error("SQL Server query failed: " .. tostring(err))
	end
	local results = cursorToResults(cur)
	conn:close()
	env:close()
	return results
end
mssqldb.mssqlQueryResults = mssqlQueryResults

_G.mssqlConnect = mssqlConnect
_G.mssqlQueryResults = mssqlQueryResults

return mssqldb
