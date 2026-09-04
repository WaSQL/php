# sqlitedb.rb
# SQLite driver for WaSQL embedded Ruby. Uses the sqlite3 gem.
#   gem install sqlite3
# Mirrors R/sqlitedb.R, lua/sqlitedb.lua and Tcl/sqlitedb.tcl.

require "sqlite3"

#---------- begin function sqlite_connect
# @describe opens a SQLite connection to the database file named in the config (falls back to default_database.sqlite), applying any configured pragmas
# @param cfg Hash - connection settings from config_parse (dbname = path to the .sqlite file; optional pragmas = { name => value })
# @return SQLite3::Database - an open handle (call .close when done); raises on failure
# @usage db = sqlite_connect(cfg)
def sqlite_connect(cfg)
	dbname = cfg["dbname"].to_s.empty? ? "default_database.sqlite" : cfg["dbname"]
	db = SQLite3::Database.new(dbname)
	if cfg["pragmas"].is_a?(Hash)
		cfg["pragmas"].each { |name, value| db.execute("PRAGMA #{name} = #{value};") }
	end
	db
end

#---------- begin function sqlite_query_results
# @describe connects, runs a query against SQLite, disconnects, and returns the rows
# @param cfg Hash - connection settings from config_parse
# @param query string - the SQL query to run
# @return Hash - { "columns" => [..], "rows" => [ {col=>val}, .. ], "count" => n }; raises on connect/query failure
# @usage res = sqlite_query_results(cfg, "SELECT * FROM users")
def sqlite_query_results(cfg, query)
	db = sqlite_connect(cfg)
	begin
		columns = []
		rows = []
		db.query(query) do |result|
			columns = result.columns
			result.each_hash { |h| rows << h }
		end
		{ "columns" => columns, "rows" => rows, "count" => rows.empty? ? db.changes : rows.length }
	ensure
		db.close
	end
end
