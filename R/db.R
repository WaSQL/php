# db.R

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