-- mysqldb.lua
-- MySQL / MariaDB driver for WaSQL embedded Lua. Uses LuaSQL (luasql.mysql).
--   luarocks install luasql-mysql
-- Mirrors R/mysqldb.R and tcl/mysqldb.tcl.

local mysqldb = {}

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
		-- non-SELECT: cur is the affected-row count (or nil)
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
mysqldb.cursorToResults = cursorToResults

----------------------------------------------------------------------
-- begin function mysqlConnect
-- @describe opens a MySQL connection from a parsed config table (defaults host to localhost, port to 3306)
-- @param cfg table - connection settings from configParse (dbhost, dbport, dbuser, dbpass, dbname)
-- @return userdata, userdata - the open connection and its LuaSQL environment (close both when done); raises an error on failure
-- @usage local conn, env = mysqlConnect(cfg)
----------------------------------------------------------------------
local function mysqlConnect(cfg)
	local luasql = require("luasql.mysql")
	local env = luasql.mysql()
	local host = (cfg.dbhost ~= nil and cfg.dbhost ~= "") and cfg.dbhost or "localhost"
	local port = tonumber(cfg.dbport) or 3306
	local conn, err = env:connect(cfg.dbname, cfg.dbuser, cfg.dbpass, host, port)
	if not conn then
		env:close()
		error("Failed to connect to MySQL: " .. tostring(err))
	end
	return conn, env
end
mysqldb.mysqlConnect = mysqlConnect

----------------------------------------------------------------------
-- begin function mysqlQueryResults
-- @describe connects, runs a query against MySQL, disconnects, and returns the rows
-- @param cfg table - connection settings from configParse
-- @param query string - the SQL query to run
-- @return table - { columns = {..}, rows = { {col=val,..}, .. }, count = n }; raises an error on connect/query failure
-- @usage local res = mysqlQueryResults(cfg, "SELECT * FROM users")
----------------------------------------------------------------------
local function mysqlQueryResults(cfg, query)
	local conn, env = mysqlConnect(cfg)
	local cur, err = conn:execute(query)
	if cur == nil and err ~= nil then
		conn:close(); env:close()
		error("MySQL query failed: " .. tostring(err))
	end
	local results = cursorToResults(cur)
	conn:close()
	env:close()
	return results
end
mysqldb.mysqlQueryResults = mysqlQueryResults

_G.mysqlConnect = mysqlConnect
_G.mysqlQueryResults = mysqlQueryResults

return mysqldb
