# mysqldb.R
#Rscript -e "install.packages('RMySQL', repos='https://cran.r-project.org')"
suppressPackageStartupMessages(library(RMySQL, quietly = TRUE))

# ---------- begin function mysqlConnect
# @describe opens a MySQL connection from a parsed config list (defaults host to localhost, port to 3306)
# @param cfg list - connection settings from configParse (dbhost, dbport, dbuser, dbpass, dbname)
# @return DBIConnection - an open RMySQL connection
# @usage dbh <- mysqlConnect(cfg)
mysqlConnect <- function(cfg) {
  # Use localhost cfg$dbhost is NULL or NA
  host <- ifelse(!is.null(cfg$dbhost) && !is.na(cfg$dbhost), cfg$dbhost, 'localhost')
  # Use default port 3306 if cfg$port is NULL or NA
  port <- ifelse(!is.null(cfg$dbport) && !is.na(cfg$dbport), cfg$dbport, 3306)
  dbConnect(
    MySQL(),
    host = host,
    port = port,
    user = cfg$dbuser,
    password = cfg$dbpass,
    dbname = cfg$dbname  
  )
}

# ---------- begin function mysqlQueryResults
# @describe connects, runs a query against MySQL, disconnects, and returns the rows
# @param cfg list - connection settings from configParse
# @param query string - the SQL query to run
# @return data.frame - the query result rows
# @usage rows <- mysqlQueryResults(cfg, 'SELECT * FROM users')
mysqlQueryResults <- function(cfg,query) {
  # Get the database connection using mysqlConnect
  dbh_r <- mysqlConnect(cfg)
  
  # Run the query
  result <- dbGetQuery(dbh_r, query)
  
  # Disconnect from the database
  invisible(dbDisconnect(dbh_r))
  
  # Return the results
  return(result)
}
