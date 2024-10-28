# snowflakedb.R
# Rscript -e "install.packages(c('DBI', 'odbc'), repos='https://cran.r-project.org')"
suppressPackageStartupMessages(library(DBI, quietly = TRUE))
suppressPackageStartupMessages(library(odbc, quietly = TRUE))

# Function to connect to the Snowflake database
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

# Function to fetch data from a specified query
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
