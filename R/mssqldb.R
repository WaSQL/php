# mssqlserverdb.R
# Rscript -e "install.packages('odbc', repos='https://cran.r-project.org')"
suppressPackageStartupMessages(library(odbc, quietly = TRUE))

# Function to connect to the Microsoft SQL Server database
mssqlConnect <- function(cfg) {
  # Use localhost if cfg$dbhost is NULL or NA
  host <- ifelse(!is.null(cfg$dbhost) && !is.na(cfg$dbhost), cfg$dbhost, 'localhost')
  # Use default port 1433 if cfg$dbport is NULL or NA
  port <- ifelse(!is.null(cfg$dbport) && !is.na(cfg$dbport), cfg$dbport, 1433)
  
  dbConnect(
    odbc::odbc(),
    Driver = "ODBC Driver 17 for SQL Server",  # Ensure the correct driver is installed
    Server = paste0(host, ",", port),
    Database = cfg$dbname,
    UID = cfg$dbuser,
    PWD = cfg$dbpass
  )
}

# Function to fetch data from a specified query
mssqlQueryResults <- function(cfg, query) {
  # Get the database connection using mssqlConnect
  dbh_r <- mssqlConnect(cfg)
  
  # Run the query
  result <- dbGetQuery(dbh_r, query)
  
  # Disconnect from the database
  invisible(dbDisconnect(dbh_r))
  
  # Return the results
  return(result)
}
