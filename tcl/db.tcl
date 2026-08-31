# db.tcl

#----------
# begin function dbQueryResults
# @describe generic query dispatcher - resolves db_name via configParse, then sources and calls the driver-specific *QueryResults proc based on dbtype (mysql/mysqli, postgres, sqlite, mssql, snowflake)
# @param db_name string - the config.xml database connection name
# @param query string - the SQL statement to execute
# @return list - the driver's results array in "array get" form (keys: columns, rows, <row>,<column>); raises an error for an unsupported dbtype
# @usage array set res [dbQueryResults mydb {SELECT * FROM users}]
#----------
proc dbQueryResults {db_name query} {
  set db_config [configParse $db_name]
  array set cfg $db_config
  set dbtype [string tolower $cfg(dbtype)]
  set cfgData [array get cfg]
  switch $dbtype {
    "mysql" -
    "mysqli" {
      set sourcefile [file join [wasqlTclPath] "mysqldb.tcl"]
      source $sourcefile
      return [mysqlQueryResults $cfgData $query]
    }
    "postgres" {
      set sourcefile [file join [wasqlTclPath] "postgresdb.tcl"]
      source $sourcefile
      return [postgresQueryResults $cfgData $query]
    }
    "sqlite" {
      set sourcefile [file join [wasqlTclPath] "sqlitedb.tcl"]
      source $sourcefile
      return [sqliteQueryResults $cfgData $query]
    }
    "mssql" {
      set sourcefile [file join [wasqlTclPath] "mssqldb.tcl"]
      source $sourcefile
      return [mssqlQueryResults $cfgData $query]
    }
    "snowflake" {
      set sourcefile [file join [wasqlTclPath] "snowflakedb.tcl"]
      source $sourcefile
      return [snowflakeQueryResults $cfgData $query]
    }
    default {
      error "Unsupported database type: $dbtype"
    }
  }
}