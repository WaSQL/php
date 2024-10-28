# config.R
#Rscript -e "install.packages('xml2', repos='https://cran.r-project.org')"
suppressPackageStartupMessages(library(xml2, quietly = TRUE))

configParse <- function(db_name) {
  # Construct the path to the config.xml in the parent directory
  config_file <- wasqlConfigFile()
  # Check if the config file exists
  if (!file.exists(config_file)) {
    stop("Config file does not exist at:", config_file)
  }

  # Read the XML configuration file
  config <- read_xml(config_file)

  # Find the database node matching the specified name
  db_node <- xml_find_first(config, paste0("//database[@name='", db_name, "']"))

  # Check if the database node was found
  if (is.null(db_node)) {
    stop("Database configuration not found for: ", db_name)
  }

  # Extract attributes from the database node
  list(
    dbtype = xml_attr(db_node, "dbtype"),
    dbhost = xml_attr(db_node, "dbhost"),
    dbuser = xml_attr(db_node, "dbuser"),
    dbpass = xml_attr(db_node, "dbpass"),
    dbname = xml_attr(db_node, "dbname"),
    dbschema = xml_attr(db_node, "dbschema") # Optional, depending on your needs
  )
}


