-- postgresdb.lua
-- PostgreSQL driver for WaSQL embedded Lua. Uses LuaSQL (luasql.postgres).
--   luarocks install luasql-postgres
-- Mirrors R/postgresdb.R and tcl/postgresdb.tcl.

local postgresdb = {}

----------------------------------------------------------------------
-- begin function cursorToResults
-- @describe drains a LuaSQL cursor into the standard WaSQL results table and closes it (NULLs surface as nil)
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
postgresdb.cursorToResults = cursorToResults

----------------------------------------------------------------------
-- begin function postgresConnect
-- @describe opens a PostgreSQL connection from a parsed config table (defaults host to localhost, port to 5432)
-- @param cfg table - connection settings from configParse (dbhost, dbport, dbuser, dbpass, dbname)
-- @return userdata, userdata - the open connection and its LuaSQL environment (close both when done); raises an error on failure
-- @usage local conn, env = postgresConnect(cfg)
----------------------------------------------------------------------
local function postgresConnect(cfg)
	local luasql = require("luasql.postgres")
	local env = luasql.postgres()
	local host = (cfg.dbhost ~= nil and cfg.dbhost ~= "") and cfg.dbhost or "localhost"
	local port = tonumber(cfg.dbport) or 5432
	local conn, err = env:connect(cfg.dbname, cfg.dbuser, cfg.dbpass, host, port)
	if not conn then
		env:close()
		error("Failed to connect to PostgreSQL: " .. tostring(err))
	end
	if cfg.dbschema ~= nil and cfg.dbschema ~= "" then
		conn:execute("SET search_path TO " .. cfg.dbschema)
	end
	return conn, env
end
postgresdb.postgresConnect = postgresConnect

----------------------------------------------------------------------
-- begin function postgresQueryResults
-- @describe connects, runs a query against PostgreSQL, disconnects, and returns the rows
-- @param cfg table - connection settings from configParse
-- @param query string - the SQL query to run
-- @return table - { columns = {..}, rows = { {col=val,..}, .. }, count = n }; raises an error on connect/query failure
-- @usage local res = postgresQueryResults(cfg, "SELECT * FROM users")
----------------------------------------------------------------------
local function postgresQueryResults(cfg, query)
	local conn, env = postgresConnect(cfg)
	local cur, err = conn:execute(query)
	if cur == nil and err ~= nil then
		conn:close(); env:close()
		error("PostgreSQL query failed: " .. tostring(err))
	end
	local results = cursorToResults(cur)
	conn:close()
	env:close()
	return results
end
postgresdb.postgresQueryResults = postgresQueryResults

_G.postgresConnect = postgresConnect
_G.postgresQueryResults = postgresQueryResults

return postgresdb
