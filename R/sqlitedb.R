# sqlitedb.R
# Rscript -e "install.packages('RSQLite', repos='https://cran.r-project.org')"
suppressPackageStartupMessages(library(RSQLite, quietly = TRUE))

# Function to connect to the SQLite database
sqliteConnect <- function(cfg) {
  # Use default database file if cfg$dbname is NULL or NA
  dbname <- ifelse(!is.null(cfg$dbname) && !is.na(cfg$dbname), cfg$dbname, "default_database.sqlite")
  
  dbConnect(SQLite(), dbname = dbname)
}

# Function to fetch data from a specified query
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
