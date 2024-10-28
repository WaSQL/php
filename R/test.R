#Rscript -e "install.packages('tidyverse', repos='https://cran.r-project.org')"
suppressPackageStartupMessages(library(tidyverse))
#Rscript -e "install.packages('RMySQL', repos='https://cran.r-project.org')"
suppressPackageStartupMessages(library(jsonlite, quietly = TRUE))
#Rscript -e "install.packages('htmlTable', repos='https://cran.r-project.org')"
suppressPackageStartupMessages(library(htmlTable, quietly = TRUE))

# Load custom module(s)
source("mysqldb.R")

# Call the configParse function defined in config.R and store the result in a global variable
CONFIG <<- configParse("wasql_test_16")

# Execute the query and store results in a variable
result <- mysqlQueryResults("SELECT code, name, country FROM states LIMIT 10;")

#knitr::opts_chunk$set(connection = dbh_r, max.print = 20)

# Print the result
cat(result)

# Print the result in a pretty, aligned format
write.table(format(result, justify = "left"), row.names = FALSE, quote = FALSE)

# Output result in HTML table format
#cat(htmlTable(result, rnames = FALSE))

# Output result in CSV format to the command line
#write.csv(result, file = stdout(), row.names = FALSE)

# Output result in JSON format to the command line
#cat(toJSON(result, pretty = FALSE))

