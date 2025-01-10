# rinfo.R: Displays the R version and metadata of installed packages

# Load required library
if (!requireNamespace("utils", quietly = TRUE)) {
  stop("The 'utils' package is required but not installed.")
}

# Get R version
r_version <- R.version.string

# Get list of installed packages
installed_packages <- installed.packages()

# Function to generate package metadata
get_package_metadata <- function(package_name) {
  description_file <- file.path(installed.packages()[package_name, "LibPath"],
                                package_name, "DESCRIPTION")
  if (file.exists(description_file)) {
    description <- read.dcf(description_file)
    metadata <- paste0("<tr><td class=\"align-left w_small w_nowrap\" style=\"width:300px;background:#1d415e4D;\">", names(description), "</td><td class=\"align-left w_small\" style=\"min-width:300px;background-color:#CCCCCC80;\">", 
                       unlist(description), "</td></tr>", collapse = "\n")
    return(metadata)
  }
  return('<tr><td colspan="2">Metadata not available</td></tr>')
}
#Name</td>
# Generate HTML output
output <- paste0(
  "<header>
      <div style=\"background:#f0f0f0;padding:10px 20px;margin-bottom:20px;border:1px solid #276dc3;\">
          <div style=\"font-size:clamp(24px,3vw,48px);color:#276dc3;\"><span class=\"brand-r\"></span></div>
          <div style=\"font-size:clamp(11px,2vw,18px);color:#276dc3;\">", r_version, "</div>
      </div>
  </header>"
)

for (pkg in rownames(installed_packages)) {
  output <- paste0(output, "
  <section>
      <h2><a name=\"module_", pkg, "\">", pkg, "</a></h2>
      <table>", get_package_metadata(pkg), "</table>
  </section>\n")
}


# Write output to file
cat(output)
