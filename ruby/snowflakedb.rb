# snowflakedb.rb
# Snowflake driver for WaSQL embedded Ruby. Snowflake has no maintained native
# Ruby driver, so this goes through ODBC (the ruby-odbc gem) exactly like
# lua/snowflakedb.lua / R/snowflakedb.R.
#   gem install ruby-odbc
# Needs the Snowflake ODBC driver ("SnowflakeDSIIDriver") installed.

require "odbc"

#---------- begin function snowflake_connection_string
# @describe builds an ODBC connection string for Snowflake, using cfg["connect"] if present or assembling one from account/warehouse/schema
# @param cfg Hash - connection settings from config_parse (connect, account, dbuser, dbpass, dbname, dbschema, warehouse)
# @return string - the ODBC connection string
# @usage cs = snowflake_connection_string(cfg)
def snowflake_connection_string(cfg)
	user = cfg["dbuser"].to_s
	pass = cfg["dbpass"].to_s
	return "#{cfg['connect']};UID=#{user};PWD=#{pass}" unless cfg["connect"].to_s.empty?
	[
		"Driver={SnowflakeDSIIDriver}",
		"Server=#{cfg['account']}.snowflakecomputing.com",
		"Database=#{cfg['dbname']}",
		"Schema=#{cfg['dbschema']}",
		"Warehouse=#{cfg['warehouse']}",
		"UID=#{user}",
		"PWD=#{pass}"
	].join(";")
end

#---------- begin function snowflake_query_results
# @describe connects, runs a query against Snowflake over ODBC, disconnects, and returns the rows
# @param cfg Hash - connection settings from config_parse
# @param query string - the SQL query to run
# @return Hash - { "columns" => [..], "rows" => [ {col=>val}, .. ], "count" => n }; raises on connect/query failure
# @usage res = snowflake_query_results(cfg, "SELECT * FROM users")
def snowflake_query_results(cfg, query)
	dbc = ODBC::Database.new.drvconnect(snowflake_connection_string(cfg))
	begin
		stmt = dbc.run(query)
		cols = stmt.columns(true).map(&:name)
		rows = []
		while (row = stmt.fetch_hash)
			rows << row
		end
		stmt.drop
		{ "columns" => cols, "rows" => rows, "count" => rows.length }
	ensure
		dbc.disconnect if dbc
	end
end
