# db.R

# ---------- begin function dbQueryResults
# @describe runs a query against a named connection in config.xml, dispatching to the driver for its dbtype
# @param db_name string - the database name/key as defined in config.xml
# @param query string - the SQL query to run
# @return data.frame - the query result rows (supports mysql, mysqli, postgres, sqlite, mssql, snowflake)
# @usage rows <- dbQueryResults('mydb', 'SELECT * FROM users')
dbQueryResults <- function(db_name,query) {
  cfg <- configParse(db_name)
  if(tolower(cfg$dbtype)=='mysql' || tolower(cfg$dbtype)=='mysqli' ){
      source(file.path(wasqlRPath(), ".", "mysqldb.R"))
      return(mysqlQueryResults(cfg,query))
  }
  else if(tolower(cfg$dbtype)=='postgres' ){
      source(file.path(wasqlRPath(), ".", "postgresdb.R"))
      return(postgresQueryResults(cfg,query))
  }
  else if(tolower(cfg$dbtype)=='sqlite' ){
      source(file.path(wasqlRPath(), ".", "sqlitedb.R"))
      return(sqliteQueryResults(cfg,query))
  }
  else if(tolower(cfg$dbtype)=='mssql' ){
      source(file.path(wasqlRPath(), ".", "mssqldb.R"))
      return(mssqlQueryResults(cfg,query))
  }
  else if(tolower(cfg$dbtype)=='snowflake' ){
      source(file.path(wasqlRPath(), ".", "snowflakedb.R"))
      return(snowflakeQueryResults(cfg,query))
  }
}