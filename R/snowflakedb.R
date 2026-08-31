# snowflakedb.R
# Rscript -e "install.packages(c('DBI', 'odbc'), repos='https://cran.r-project.org')"
suppressPackageStartupMessages(library(DBI, quietly = TRUE))
suppressPackageStartupMessages(library(odbc, quietly = TRUE))

# ---------- begin function snowflakeConnect
# @describe opens a Snowflake connection via ODBC, using cfg$connect if present or building a connection string from account/warehouse/schema
# @param cfg list - connection settings from configParse (connect, account, dbuser, dbpass, dbname, dbschema, warehouse)
# @return DBIConnection - an open odbc connection (needs the SnowflakeDSIIDriver)
# @usage dbh <- snowflakeConnect(cfg)
snowflakeConnect <- function(cfg) {
  # Use default values if not provided
  account <- ifelse(!is.null(cfg$account) && !is.na(cfg$account), cfg$account, '')
  user <- ifelse(!is.null(cfg$dbuser) && !is.na(cfg$dbuser), cfg$dbuser, '')
  password <- ifelse(!is.null(cfg$dbpass) && !is.na(cfg$dbpass), cfg$dbpass, '')
  database <- ifelse(!is.null(cfg$dbname) && !is.na(cfg$dbname), cfg$dbname, '')
  schema <- ifelse(!is.null(cfg$dbschema) && !is.na(cfg$dbschema), cfg$dbschema, '')
  warehouse <- ifelse(!is.null(cfg$warehouse) && !is.na(cfg$warehouse), cfg$warehouse, '')
  if(!is.null(cfg$connect) && !is.na(cfg$connect)){
    cstring <- paste0(cfg$connect,";","UID=",user, ";","PWD=", password)
  }
  else{
    cstring <- paste0("Driver={SnowflakeDSIIDriver};",
                                 "Server=", account, ".snowflakecomputing.com;",
                                 "Database=", database, ";",
                                 "Schema=", schema, ";",
                                 "Warehouse=", warehouse, ";",
                                 "UID=", user, ";",
                                 "PWD=", password)
  }
  #print(cstring)
  # Establish the connection
  con <- dbConnect(
    odbc::odbc(),
    .connection_string = cstring
  )
  
  return(con)
}

# ---------- begin function snowflakeQueryResults
# @describe connects, runs a query against Snowflake, disconnects, and returns the rows
# @param cfg list - connection settings from configParse
# @param query string - the SQL query to run
# @return data.frame - the query result rows
# @usage rows <- snowflakeQueryResults(cfg, 'SELECT * FROM users')
snowflakeQueryResults <- function(cfg, query) {
  # Get the database connection using snowflakeConnect
  #print("connecting")
  dbh_r <- snowflakeConnect(cfg)
  #stop("Connected")
  # Run the query
  result <- dbGetQuery(dbh_r, query)
  
  # Disconnect from the database
  dbDisconnect(dbh_r)
  
  # Return the results
  return(result)
}
