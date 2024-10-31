# db.tcl

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