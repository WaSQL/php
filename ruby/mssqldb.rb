# mssqldb.rb
# Microsoft SQL Server driver for WaSQL embedded Ruby. Uses the tiny_tds gem
# (which links FreeTDS).
#   gem install tiny_tds
# Mirrors R/mssqldb.R, lua/mssqldb.lua and Tcl/mssqldb.tcl.

require "tiny_tds"

#---------- begin function mssql_connect
# @describe opens a TinyTds client for SQL Server from a parsed config Hash (defaults host to localhost, port to 1433)
# @param cfg Hash - connection settings from config_parse (dbhost, dbport, dbuser, dbpass, dbname)
# @return TinyTds::Client - an open client (call .close when done); raises on failure
# @usage client = mssql_connect(cfg)
def mssql_connect(cfg)
	host = cfg["dbhost"].to_s.empty? ? "localhost" : cfg["dbhost"]
	port = cfg["dbport"].to_s.empty? ? 1433 : cfg["dbport"].to_i
	TinyTds::Client.new(
		host: host,
		port: port,
		username: cfg["dbuser"],
		password: cfg["dbpass"],
		database: cfg["dbname"]
	)
end

#---------- begin function mssql_query_results
# @describe connects, runs a query against SQL Server, disconnects, and returns the rows
# @param cfg Hash - connection settings from config_parse
# @param query string - the SQL query to run
# @return Hash - { "columns" => [..], "rows" => [ {col=>val}, .. ], "count" => n }; raises on connect/query failure
# @usage res = mssql_query_results(cfg, "SELECT * FROM Users")
def mssql_query_results(cfg, query)
	client = mssql_connect(cfg)
	begin
		result = client.execute(query)
		rows = result.each(as: :hash).to_a
		cols = (result.fields if result.respond_to?(:fields)) || (rows.first ? rows.first.keys : [])
		{ "columns" => cols, "rows" => rows, "count" => rows.empty? ? result.affected_rows : rows.length }
	ensure
		client.close
	end
end
