# postgresdb.rb
# PostgreSQL driver for WaSQL embedded Ruby. Uses the pg gem.
#   gem install pg
# Mirrors R/postgresdb.R, lua/postgresdb.lua and Tcl/postgresdb.tcl.

require "pg"

#---------- begin function postgres_connect
# @describe opens a pg connection from a parsed config Hash (defaults host to localhost, port to 5432; applies dbschema as search_path when set)
# @param cfg Hash - connection settings from config_parse (dbhost, dbport, dbuser, dbpass, dbname, dbschema)
# @return PG::Connection - an open connection (call .close when done); raises on failure
# @usage conn = postgres_connect(cfg)
def postgres_connect(cfg)
	host = cfg["dbhost"].to_s.empty? ? "localhost" : cfg["dbhost"]
	port = cfg["dbport"].to_s.empty? ? 5432 : cfg["dbport"].to_i
	conn = PG.connect(
		host: host,
		port: port,
		user: cfg["dbuser"],
		password: cfg["dbpass"],
		dbname: cfg["dbname"]
	)
	unless cfg["dbschema"].to_s.empty?
		conn.exec("SET search_path TO #{conn.escape_identifier(cfg['dbschema'])}")
	end
	conn
end

#---------- begin function postgres_query_results
# @describe connects, runs a query against PostgreSQL, disconnects, and returns the rows
# @param cfg Hash - connection settings from config_parse
# @param query string - the SQL query to run
# @return Hash - { "columns" => [..], "rows" => [ {col=>val}, .. ], "count" => n }; raises on connect/query failure
# @usage res = postgres_query_results(cfg, "SELECT * FROM users")
def postgres_query_results(cfg, query)
	conn = postgres_connect(cfg)
	begin
		res = conn.exec(query)
		cols = res.fields
		rows = res.to_a
		count = res.result_status == PG::PGRES_TUPLES_OK ? res.ntuples : res.cmd_tuples
		{ "columns" => cols, "rows" => rows, "count" => count }
	ensure
		conn.close
	end
end
