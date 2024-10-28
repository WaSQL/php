# mysqldb.R
#Rscript -e "install.packages('RMySQL', repos='https://cran.r-project.org')"
suppressPackageStartupMessages(library(RMySQL, quietly = TRUE))

# Function to connect to the database
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

# Function to fetch data from a specified query
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
