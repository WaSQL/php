-- db.lua
-- Generic query dispatcher. Resolves a named connection via configParse, then
-- loads and calls the driver-specific *QueryResults function based on dbtype.
-- Mirrors R/db.R and tcl/db.tcl.

local ok_config, config = pcall(require, "config")
if not ok_config then
	-- standalone fallback: load config.lua sitting next to this file
	local src = debug.getinfo(1, "S").source
	if src:sub(1, 1) == "@" then src = src:sub(2) end
	local dir = src:match("^(.*)[/\\]") or "."
	config = assert(loadfile(dir .. "/config.lua"))()
end

local db = {}

-- dbtype (lowercased) -> driver module basename
local DRIVER_MAP = {
	mysql     = "mysqldb",
	mysqli    = "mysqldb",
	postgres  = "postgresdb",
	postgresql = "postgresdb",
	sqlite    = "sqlitedb",
	sqlite3   = "sqlitedb",
	mssql     = "mssqldb",
	sqlsrv    = "mssqldb",
	snowflake = "snowflakedb",
}

----------------------------------------------------------------------
-- begin function loadDriver
-- @describe loads a driver module (mysqldb, postgresdb, ...) from the WaSQL lua/ directory
-- @param basename string - the driver file name without extension
-- @return table - the driver module (exports a <name>QueryResults function); raises an error when the file is missing
-- @usage local drv = loadDriver("mysqldb")
----------------------------------------------------------------------
local function loadDriver(basename)
	local ok, mod = pcall(require, basename)
	if ok and type(mod) == "table" then
		return mod
	end
	local dir = wasqlLuaPath()
	local path = dir .. "/" .. basename .. ".lua"
	local chunk, lerr = loadfile(path)
	if not chunk then
		error("Cannot load database driver '" .. basename .. "': " .. tostring(lerr))
	end
	return chunk()
end
db.loadDriver = loadDriver

----------------------------------------------------------------------
-- begin function dbQueryResults
-- @describe runs a query against a named connection in config.xml, dispatching to the driver for its dbtype
-- @param db_name string - the database name/key as defined in config.xml
-- @param query string - the SQL query to run
-- @return table - { columns = {..}, rows = { {col=val,..}, .. }, count = n }; supports mysql/mysqli, postgres, sqlite, mssql, snowflake; raises an error for an unsupported dbtype
-- @usage local res = dbQueryResults("mydb", "SELECT * FROM users")
----------------------------------------------------------------------
local function dbQueryResults(db_name, query)
	local cfg = config.configParse(db_name)
	local dbtype = tostring(cfg.dbtype or ""):lower()
	local basename = DRIVER_MAP[dbtype]
	if not basename then
		error("Unsupported database type: " .. dbtype)
	end
	local driver = loadDriver(basename)
	local fnName = basename:gsub("db$", "") .. "QueryResults"
	local fn = driver[fnName] or _G[fnName]
	if type(fn) ~= "function" then
		error("Driver '" .. basename .. "' does not export " .. fnName)
	end
	return fn(cfg, query)
end
db.dbQueryResults = dbQueryResults

_G.dbQueryResults = dbQueryResults

return db
