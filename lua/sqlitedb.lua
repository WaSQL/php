-- sqlitedb.lua
-- SQLite driver for WaSQL embedded Lua. Uses LuaSQL (luasql.sqlite3).
--   luarocks install luasql-sqlite3
-- Mirrors R/sqlitedb.R and tcl/sqlitedb.tcl.

local sqlitedb = {}

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
sqlitedb.cursorToResults = cursorToResults

----------------------------------------------------------------------
-- begin function sqliteConnect
-- @describe opens a SQLite connection to the database file named in the config (falls back to default_database.sqlite), applying any configured pragmas
-- @param cfg table - connection settings from configParse (dbname = path to the .sqlite file; optional pragmas = { name = value, ... })
-- @return userdata, userdata - the open connection and its LuaSQL environment (close both when done); raises an error on failure
-- @usage local conn, env = sqliteConnect(cfg)
----------------------------------------------------------------------
local function sqliteConnect(cfg)
	local luasql = require("luasql.sqlite3")
	local env = luasql.sqlite3()
	local dbname = (cfg.dbname ~= nil and cfg.dbname ~= "") and cfg.dbname or "default_database.sqlite"
	local conn, err = env:connect(dbname)
	if not conn then
		env:close()
		error("Failed to connect to SQLite database: " .. tostring(err))
	end
	if type(cfg.pragmas) == "table" then
		for pragma, value in pairs(cfg.pragmas) do
			conn:execute("PRAGMA " .. pragma .. " = " .. tostring(value) .. ";")
		end
	end
	return conn, env
end
sqlitedb.sqliteConnect = sqliteConnect

----------------------------------------------------------------------
-- begin function sqliteQueryResults
-- @describe connects, runs a query against SQLite, disconnects, and returns the rows
-- @param cfg table - connection settings from configParse
-- @param query string - the SQL query to run
-- @return table - { columns = {..}, rows = { {col=val,..}, .. }, count = n }; raises an error on connect/query failure
-- @usage local res = sqliteQueryResults(cfg, "SELECT * FROM users")
----------------------------------------------------------------------
local function sqliteQueryResults(cfg, query)
	local conn, env = sqliteConnect(cfg)
	local cur, err = conn:execute(query)
	if cur == nil and err ~= nil then
		conn:close(); env:close()
		error("SQLite query failed: " .. tostring(err))
	end
	local results = cursorToResults(cur)
	conn:close()
	env:close()
	return results
end
sqlitedb.sqliteQueryResults = sqliteQueryResults

_G.sqliteConnect = sqliteConnect
_G.sqliteQueryResults = sqliteQueryResults

return sqlitedb
