-- snowflakedb.lua
-- Snowflake driver for WaSQL embedded Lua. Uses LuaSQL ODBC (luasql.odbc).
--   luarocks install luasql-odbc
-- Needs the Snowflake ODBC driver (SnowflakeDSIIDriver) installed.
-- Mirrors R/snowflakedb.R.

local snowflakedb = {}

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
snowflakedb.cursorToResults = cursorToResults

----------------------------------------------------------------------
-- begin function snowflakeConnectionString
-- @describe builds an ODBC connection string for Snowflake, using cfg.connect if present or assembling one from account/warehouse/schema
-- @param cfg table - connection settings from configParse (connect, account, dbuser, dbpass, dbname, dbschema, warehouse)
-- @return string - the ODBC connection string
-- @usage local cs = snowflakeConnectionString(cfg)
----------------------------------------------------------------------
local function snowflakeConnectionString(cfg)
	local user = cfg.dbuser or ""
	local password = cfg.dbpass or ""
	if cfg.connect ~= nil and cfg.connect ~= "" then
		return cfg.connect .. ";UID=" .. user .. ";PWD=" .. password
	end
	return table.concat({
		"Driver={SnowflakeDSIIDriver}",
		"Server=" .. (cfg.account or "") .. ".snowflakecomputing.com",
		"Database=" .. (cfg.dbname or ""),
		"Schema=" .. (cfg.dbschema or ""),
		"Warehouse=" .. (cfg.warehouse or ""),
		"UID=" .. user,
		"PWD=" .. password,
	}, ";")
end
snowflakedb.snowflakeConnectionString = snowflakeConnectionString

----------------------------------------------------------------------
-- begin function snowflakeConnect
-- @describe opens a Snowflake connection via ODBC
-- @param cfg table - connection settings from configParse
-- @return userdata, userdata - the open connection and its LuaSQL environment (close both when done); raises an error on failure
-- @usage local conn, env = snowflakeConnect(cfg)
----------------------------------------------------------------------
local function snowflakeConnect(cfg)
	local luasql = require("luasql.odbc")
	local env = luasql.odbc()
	local conn, err = env:connect(snowflakeConnectionString(cfg))
	if not conn then
		env:close()
		error("Failed to connect to Snowflake: " .. tostring(err))
	end
	return conn, env
end
snowflakedb.snowflakeConnect = snowflakeConnect

----------------------------------------------------------------------
-- begin function snowflakeQueryResults
-- @describe connects, runs a query against Snowflake, disconnects, and returns the rows
-- @param cfg table - connection settings from configParse
-- @param query string - the SQL query to run
-- @return table - { columns = {..}, rows = { {col=val,..}, .. }, count = n }; raises an error on connect/query failure
-- @usage local res = snowflakeQueryResults(cfg, "SELECT * FROM users")
----------------------------------------------------------------------
local function snowflakeQueryResults(cfg, query)
	local conn, env = snowflakeConnect(cfg)
	local cur, err = conn:execute(query)
	if cur == nil and err ~= nil then
		conn:close(); env:close()
		error("Snowflake query failed: " .. tostring(err))
	end
	local results = cursorToResults(cur)
	conn:close()
	env:close()
	return results
end
snowflakedb.snowflakeQueryResults = snowflakeQueryResults

_G.snowflakeConnect = snowflakeConnect
_G.snowflakeQueryResults = snowflakeQueryResults

return snowflakedb
