# sqlitedb.R
# Rscript -e "install.packages('RSQLite', repos='https://cran.r-project.org')"
suppressPackageStartupMessages(library(RSQLite, quietly = TRUE))

# ---------- begin function sqliteConnect
# @describe opens a SQLite connection to the database file named in the config (falls back to default_database.sqlite)
# @param cfg list - connection settings from configParse (dbname = path to the .sqlite file)
# @return DBIConnection - an open RSQLite connection
# @usage dbh <- sqliteConnect(cfg)
sqliteConnect <- function(cfg) {
  # Use default database file if cfg$dbname is NULL or NA
  dbname <- ifelse(!is.null(cfg$dbname) && !is.na(cfg$dbname), cfg$dbname, "default_database.sqlite")
  
  dbConnect(SQLite(), dbname = dbname)
}

# ---------- begin function sqliteQueryResults
# @describe connects, runs a query against SQLite, disconnects, and returns the rows
# @param cfg list - connection settings from configParse
# @param query string - the SQL query to run
# @return data.frame - the query result rows
# @usage rows <- sqliteQueryResults(cfg, 'SELECT * FROM users')
sqliteQueryResults <- function(cfg, query) {
  # Get the database connection using sqliteConnect
  dbh_r <- sqliteConnect(cfg)
  
  # Run the query
  result <- dbGetQuery(dbh_r, query)
  
  # Disconnect from the database
  invisible(dbDisconnect(dbh_r))
  
  # Return the results
  return(result)
}
