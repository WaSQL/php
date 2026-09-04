# mysqldb.rb
# MySQL / MariaDB driver for WaSQL embedded Ruby. Uses the mysql2 gem.
#   gem install mysql2
# Mirrors R/mysqldb.R, lua/mysqldb.lua and Tcl/mysqldb.tcl.

require "mysql2"

#---------- begin function mysql_connect
# @describe opens a mysql2 client from a parsed config Hash (defaults host to localhost, port to 3306)
# @param cfg Hash - connection settings from config_parse (dbhost, dbport, dbuser, dbpass, dbname)
# @return Mysql2::Client - an open client (call .close when done); raises on failure
# @usage client = mysql_connect(cfg)
def mysql_connect(cfg)
	host = cfg["dbhost"].to_s.empty? ? "localhost" : cfg["dbhost"]
	port = cfg["dbport"].to_s.empty? ? 3306 : cfg["dbport"].to_i
	Mysql2::Client.new(
		host: host,
		port: port,
		username: cfg["dbuser"],
		password: cfg["dbpass"],
		database: cfg["dbname"]
	)
end

#---------- begin function mysql_query_results
# @describe connects, runs a query against MySQL, disconnects, and returns the rows
# @param cfg Hash - connection settings from config_parse
# @param query string - the SQL query to run
# @return Hash - { "columns" => [..], "rows" => [ {col=>val}, .. ], "count" => n }; raises on connect/query failure
# @usage res = mysql_query_results(cfg, "SELECT * FROM users")
def mysql_query_results(cfg, query)
	client = mysql_connect(cfg)
	begin
		res = client.query(query, as: :hash, cast: true)
		return { "columns" => [], "rows" => [], "count" => client.affected_rows } if res.nil?
		rows = res.to_a
		{ "columns" => res.fields, "rows" => rows, "count" => rows.length }
	ensure
		client.close
	end
end
