# snowflakedb.R
# Rscript -e "install.packages(c('DBI', 'odbc'), repos='https://cran.r-project.org')"
suppressPackageStartupMessages(library(DBI, quietly = TRUE))
suppressPackageStartupMessages(library(odbc, quietly = TRUE))

# Function to connect to the Snowflake database
snowflakeConnect <- function(cfg) {
  # Use default values if not provided
  account <- ifelse(!is.null(cfg$account) && !is.na(cfg$account), cfg$account, 'your_account')
  user <- ifelse(!is.null(cfg$user) && !is.na(cfg$user), cfg$user, 'your_user')
  password <- ifelse(!is.null(cfg$dbpass) && !is.na(cfg$dbpass), cfg$dbpass, 'your_password')
  database <- ifelse(!is.null(cfg$dbname) && !is.na(cfg$dbname), cfg$dbname, 'your_database')
  schema <- ifelse(!is.null(cfg$dbschema) && !is.na(cfg$dbschema), cfg$dbschema, 'your_schema')
  warehouse <- ifelse(!is.null(cfg$warehouse) && !is.na(cfg$warehouse), cfg$warehouse, 'your_warehouse')

  # Establish the connection
  con <- dbConnect(
    odbc::odbc(),
    .connection_string = paste0("Driver={SnowflakeDSIIDriver};",
                                 "Server=", account, ".snowflakecomputing.com;",
                                 "Database=", database, ";",
                                 "Schema=", schema, ";",
                                 "Warehouse=", warehouse, ";",
                                 "UID=", user, ";",
                                 "PWD=", password)
  )
  
  return(con)
}

# Function to fetch data from a specified query
snowflakeQueryResults <- function(cfg, query) {
  # Get the database connection using snowflakeConnect
  dbh_r <- snowflakeConnect(cfg)
  
  # Run the query
  result <- dbGetQuery(dbh_r, query)
  
  # Disconnect from the database
  dbDisconnect(dbh_r)
  
  # Return the results
  return(result)
}
