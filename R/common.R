#Rscript -e "install.packages('RMySQL', repos='https://cran.r-project.org')"
suppressPackageStartupMessages(library(jsonlite, quietly = TRUE))

convertExtendedCharacters <- function(string) {
  # Define the character mapping
  normalizeChars <- c(
    'Å' = 'A', 'Æ' = 'A', 'À' = 'A', 'Á' = 'A', 'Â' = 'A', 'Ã' = 'A', 'Ä' = 'A', 'Ă' = 'A', 'Ā' = 'A', 'Ą' = 'A',
    'Ç' = 'C', 'Ć' = 'C', 'Ĉ' = 'C', 'Ċ' = 'C', 'Č' = 'C',
    'È' = 'E', 'É' = 'E', 'Ê' = 'E', 'Ë' = 'E', 'Ð' = 'E', 'Ē' = 'E', 'Ĕ' = 'E', 'Ė' = 'E', 'Ę' = 'E', 'Ě' = 'E',
    'Ƒ' = 'F',
    'Ğ' = 'G', 'Ġ' = 'G', 'Ģ' = 'G',
    'Ĥ' = 'H', 'Ħ' = 'H',
    'Ì' = 'I', 'Í' = 'I', 'Î' = 'I', 'Ï' = 'I', 'Ĩ' = 'I', 'Ī' = 'I', 'Ĭ' = 'I', 'Į' = 'I', 'İ' = 'I', 'Ĳ' = 'I',
    'Ĵ' = 'J',
    'Ķ' = 'K', 'ĸ' = 'K',
    'Ĺ' = 'L', 'Ļ' = 'L', 'Ľ' = 'L', 'Ŀ' = 'L', 'Ł' = 'L',
    'Ñ' = 'N', 'Ń' = 'N', 'Ņ' = 'N', 'Ň' = 'N', 'ʼN' = 'N', 'Ŋ' = 'N', 
    'Ò' = 'O', 'Ó' = 'O', 'Ô' = 'O', 'Õ' = 'O', 'Ö' = 'O', 'Ø' = 'O', 'Ŏ' = 'O', 'Ő' = 'O', 'Œ' = 'O',
    'Þ' = 'P',
    'Ŕ' = 'R', 'Ŗ' = 'R', 'Ř' = 'R',
    'Š' = 'S', 'Ș' = 'S', 'Ś' = 'S', 'Ŝ' = 'S', 'Ş' = 'S', 'ſ' = 'S',
    'Ț' = 'T', 'Ţ' = 'T', 'Ť' = 'T', 'Ŧ' = 'T',
    'Ù' = 'U', 'Ú' = 'U', 'Û' = 'U', 'Ü' = 'U', 'Ũ' = 'U', 'Ū' = 'U', 'Ŭ' = 'U', 'Ů' = 'U', 'Ű' = 'U', 'Ų' = 'U',
    'Ŵ' = 'W',
    'Ý' = 'Y', 'Ÿ' = 'Y', 'Ŷ' = 'Y',
    'Ž' = 'Z', 'Ź' = 'Z', 'Ż' = 'Z',

    'å' = 'a', 'æ' = 'a', 'à' = 'a', 'á' = 'a', 'â' = 'a', 'ã' = 'a', 'ä' = 'a', 'ă' = 'a', 'ā' = 'a', 'ą' = 'a',
    'ç' = 'c', 'ć' = 'c', 'ĉ' = 'c', 'ċ' = 'c', 'č' = 'c',
    'è' = 'e', 'é' = 'e', 'ê' = 'e', 'ë' = 'e', 'ð' = 'e', 'ē' = 'e', 'ĕ' = 'e', 'ė' = 'e', 'ę' = 'e', 'ě' = 'e',
    'ƒ' = 'f',
    'ğ' = 'g', 'ġ' = 'g', 'ģ' = 'g',
    'ĥ' = 'h', 'ħ' = 'h',
    'ì' = 'i', 'í' = 'i', 'î' = 'i', 'ï' = 'i', 'ĩ' = 'i', 'ī' = 'i', 'ĭ' = 'i', 'į' = 'i', 'i̇' = 'i', 'ĳ' = 'i',
    'ĵ' = 'j',
    'ķ' = 'k', 'ĸ' = 'k',
    'ĺ' = 'l', 'ļ' = 'l', 'ľ' = 'l', 'ŀ' = 'l', 'ł' = 'l',
    'ñ' = 'n', 'ń' = 'n', 'ņ' = 'n', 'ň' = 'n', 'ŉ' = 'n', 'ŋ' = 'n', 
    'ò' = 'o', 'ó' = 'o', 'ô' = 'o', 'õ' = 'o', 'ö' = 'o', 'ø' = 'o', 'ŏ' = 'o', 'ő' = 'o', 'œ' = 'o',
    'þ' = 'p',
    'ŕ' = 'r', 'ŗ' = 'r', 'ř' = 'r',
    'š' = 's', 'ș' = 's', 'ß' = 'ss', 'ś' = 's', 'ŝ' = 's', 'ş' = 's', 'ſ' = 's',
    'ț' = 't', 'ţ' = 't', 'ť' = 't', 'ŧ' = 't',
    'ù' = 'u', 'ú' = 'u', 'û' = 'u', 'ü' = 'u', 'ũ' = 'u', 'ū' = 'u', 'ŭ' = 'u', 'ů' = 'u', 'ű' = 'u', 'ų' = 'u',
    'ŵ' = 'w',
    'ý' = 'y', 'ÿ' = 'y', 'ŷ' = 'y',
    'ž' = 'z', 'ź' = 'z', 'ż' = 'z'
  )

  # Replace each extended character in the string
  for (char in names(normalizeChars)) {
    string <- gsub(char, normalizeChars[char], string, fixed = TRUE)
  }
  
  return(string)
}
# ---------- begin function commonStrlen
# @describe wrapper for strlen function to handle arrays, objects, etc.
# @param params str mixed
# @return integer
# @usage if(commonStrlen($x)){...}
commonStrlen <- function(s) {
  if (is.null(s)) { return(0) }
  
  if (is.character(s) || is.numeric(s)) {
    return(nchar(as.character(s)))
  }
  
  if (is.list(s) || is.object(s)) {
    s <- jsonlite::toJSON(s, auto_unbox = TRUE)
  }
  
  return(nchar(s))
}

# ---------- begin function commonFormatPhone
# @describe formats a phone number
# @param string phone number
# @return string - formatted phone number (xxx) xxx-xxxx
# @usage commonFormatPhone('8014584741')
# 
commonFormatPhone <- function(phone) {
  # Making sure we have something
  if (nchar(phone) < 4) {  return('') }
  
  # Strip out everything but numbers
  phone <- gsub("[^0-9]", "", phone)
  length <- nchar(phone)
  
  switch(length,
     `7` = gsub("([0-9]{3})([0-9]{4})", "\\1-\\2", phone),
     `10` = gsub("([0-9]{3})([0-9]{3})([0-9]{4})", "($1) $2-$3", phone),
     `11` = gsub("([0-9]{1})([0-9]{3})([0-9]{3})([0-9]{4})", "\\1($2) $3-$4", phone),
     phone # default case, return the unformatted phone
  )
}

# ---------- begin function parseAttributes ----------
# @describe parses an html tag attributes
# @param txt html tag string
# @return array key/value pairs for each attribute found in the html tag
# @usage attrs=parseHtmlTagAttributes($tag)
parseHtmlTagAttributes <- function(text) {
  attributes <- list()
  
  # Define the regex pattern to match HTML tag attributes
  pattern <- "(?:(?<name>[a-zA-Z][a-zA-Z0-9\\-:_]*)(?:(=)(?:(?:(\"[^\"]+\")|('[^']+')|([^\\s>]+))))?)"
  
  # Use gregexpr to find all matches of the pattern in the text
  matches <- gregexpr(pattern, text, perl = TRUE)
  match_data <- regmatches(text, matches)
  
  # Iterate through matches
  for (match in match_data) {
    for (m in match) {
      # Extract name and value
      name_match <- regmatches(m, regexec("(?<name>[a-zA-Z][a-zA-Z0-9\\-:_]*)(?:=(?<value>(?:\"[^\"]+\"|'[^']+'|[^\\s>]+)))?", m, perl = TRUE))
      if (length(name_match) > 0) {
        name <- tolower(name_match[[1]][2])  # Extract the name and convert to lowercase
        value <- ifelse(length(name_match[[1]]) > 3, trimws(gsub("^['\"]|['\"]$", "", name_match[[1]][4])), NULL)
        attributes[[name]] <- value
      }
    }
  }
  
  return(attributes)
}


