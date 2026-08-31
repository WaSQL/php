# postgresdb.R
#Rscript -e "install.packages('RPostgres', repos='https://cran.r-project.org')"
suppressPackageStartupMessages(library(RPostgres, quietly = TRUE))

# ---------- begin function postgresConnect
# @describe opens a PostgreSQL connection from a parsed config list (defaults host to localhost, port to 5432)
# @param cfg list - connection settings from configParse (dbhost, dbport, dbuser, dbpass, dbname)
# @return DBIConnection - an open RPostgres connection
# @usage dbh <- postgresConnect(cfg)
postgresConnect <- function(cfg) {
  # Use localhost cfg$dbhost is NULL or NA
  host <- ifelse(!is.null(cfg$dbhost) && !is.na(cfg$dbhost), cfg$dbhost, 'localhost')
  # Use default port 5432 if cfg$port is NULL or NA
  port <- ifelse(!is.null(cfg$dbport) && !is.na(cfg$dbport), cfg$dbport, 5432)
  dbConnect(
    Postgres(),
    host = host,
    port = port,       # Default PostgreSQL port is 5432
    user = cfg$dbuser,
    password = cfg$dbpass,
    dbname = cfg$dbname
  )
}

# ---------- begin function postgresQueryResults
# @describe connects, runs a query against PostgreSQL, disconnects, and returns the rows
# @param cfg list - connection settings from configParse
# @param query string - the SQL query to run
# @return data.frame - the query result rows
# @usage rows <- postgresQueryResults(cfg, 'SELECT * FROM users')
postgresQueryResults <- function(cfg, query) {
  # Get the database connection using postgresConnect
  dbh_r <- postgresConnect(cfg)
  
  # Run the query
  result <- dbGetQuery(dbh_r, query)
  
  # Disconnect from the database
  invisible(dbDisconnect(dbh_r))
  
  # Return the results
  return(result)
}
