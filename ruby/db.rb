# db.rb
# Generic query dispatcher for WaSQL embedded Ruby. Resolves a named connection
# via config_parse, then loads and calls the driver-specific *_query_results
# method based on dbtype. Port of R/db.R and lua/db.lua.
#
# Drivers are required on demand, so a page that never touches the database
# needs none of the database gems installed.

require "json"

DB_DRIVER_MAP = {
	"mysql"      => "mysqldb",
	"mysqli"     => "mysqldb",
	"postgres"   => "postgresdb",
	"postgresql" => "postgresdb",
	"sqlite"     => "sqlitedb",
	"sqlite3"    => "sqlitedb",
	"mssql"      => "mssqldb",
	"sqlsrv"     => "mssqldb",
	"snowflake"  => "snowflakedb",
}.freeze

unless respond_to?(:wasqlRubyPath, true)
	def wasqlRubyPath
		File.dirname(File.expand_path(__FILE__))
	end
end

#---------- begin function db_query_results
# @describe runs a query against a named connection in config.xml, dispatching to the driver for its dbtype
# @param db_name string - the database name/key as defined in config.xml
# @param query string - the SQL query to run
# @return Hash - { "columns" => [..], "rows" => [ {col=>val}, .. ], "count" => n }; supports mysql/mysqli, postgres, sqlite, mssql, snowflake; raises for an unsupported dbtype
# @usage res = db_query_results("mydb", "SELECT * FROM users")
def db_query_results(db_name, query)
	cfg = config_parse(db_name)
	dbtype = cfg["dbtype"].to_s.downcase
	basename = DB_DRIVER_MAP[dbtype]
	raise "Unsupported database type: #{dbtype}" if basename.nil?
	require File.join(wasqlRubyPath, "#{basename}.rb")
	send("#{basename.sub(/db\z/, '')}_query_results", cfg, query)
end

#---------- begin function dbQueryResults
# @describe camelCase alias of db_query_results, for parity with the other WaSQL language ports
# @param db_name string - the database name/key as defined in config.xml
# @param query string - the SQL query to run
# @return Hash - see db_query_results
# @usage res = dbQueryResults("mydb", "SELECT * FROM users")
def dbQueryResults(db_name, query)
	db_query_results(db_name, query)
end
