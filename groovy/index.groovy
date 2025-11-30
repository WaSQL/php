/**
index.groovy - Main entry point for WaSQL Groovy pages

This script processes WaSQL pages stored in the database, executing
controller logic, functions, and rendering views.

References:
    https://groovy-lang.org/
*/

import groovy.json.JsonSlurper

// Load modules
def scriptDir = new File(getClass().protectionDomain.codeSource.location.path).parent

def configScript = new File(scriptDir, 'config.groovy')
def config = new GroovyShell().parse(configScript)

def commonScript = new File(scriptDir, 'common.groovy')
def common = new GroovyShell().parse(commonScript)

def dbScript = new File(scriptDir, 'db.groovy')
def db = new GroovyShell().parse(dbScript)

// Check if running from CLI
if (!common.isCLI()) {
    println("Content-type: text/html; charset=UTF-8;\n\n")
}

// HTTP_HOST
def HTTP_HOST = System.getenv('HTTP_HOST') ?: 'localhost'

// URL parsing
def url = "//${HTTP_HOST}"
if (System.getenv('REDIRECT_URL')) {
    url += System.getenv('REDIRECT_URL')
    if (System.getenv('QUERY_STRING')) {
        url += '?' + System.getenv('QUERY_STRING')
    }
} else if (System.getenv('REQUEST_URI')) {
    url += System.getenv('REQUEST_URI')
}

// Parse query string into REQUEST map
def REQUEST = [:]
if (url.contains('?')) {
    def queryString = url.split('\\?', 2)[1]
    queryString.split('&').each { param ->
        def parts = param.split('=', 2)
        if (parts.length == 2) {
            REQUEST[URLDecoder.decode(parts[0], 'UTF-8')] = URLDecoder.decode(parts[1], 'UTF-8')
        }
    }
}

// Initialize global variables
def PAGE = [:]
def TEMPLATE = [:]

if (!REQUEST._view) {
    REQUEST._view = 'index'
}

// View a page
if (REQUEST._view) {
    def view = REQUEST._view

    // Build query to get page from database
    def query = "SELECT * FROM _pages WHERE name='${view}' OR permalink='${view}'"

    try {
        def recs = db.queryResults(config.CONFIG.database, query, [:])

        if (recs instanceof List && recs.size() > 0) {
            def rec = recs[0]

            // Set PAGE
            rec.each { k, v ->
                if (v instanceof String) {
                    PAGE[k] = v.trim()
                } else {
                    PAGE[k] = v
                }
            }

            // Parse views from body
            common.parseViews(PAGE.body ?: '')
            def body = PAGE.body ?: ''

            // Execute functions if present
            if (PAGE.functions && PAGE.functions.length() > 0) {
                try {
                    def shell = new GroovyShell()
                    shell.evaluate(PAGE.functions)
                } catch (Exception e) {
                    System.err.println("Error executing functions: ${e.message}")
                    e.printStackTrace()
                }
            }

            // Execute controller if present
            if (PAGE.controller && PAGE.controller.length() > 0) {
                try {
                    def shell = new GroovyShell()
                    shell.evaluate(PAGE.controller)
                } catch (Exception e) {
                    System.err.println("Error executing controller: ${e.message}")
                    e.printStackTrace()
                }
            }

            // Process views
            if (!common.VIEW || common.VIEW.size() == 0) {
                common.setView('default', 0)
                common.VIEW['default'] = body
            }

            // Process each view
            common.VIEW.each { viewname, viewbody ->
                def processed = viewbody // Process code blocks if needed
                def repstr = "<view:${viewname}>${viewbody}</view:${viewname}>"
                body = body.replace(repstr, processed)
            }

            // Remove unprocessed views
            def viewPattern = ~/<view:(.*?)>(.*?)<\/view:\1>/
            body = body.replaceAll(viewPattern, '')

            // Output
            println(body)

        } else {
            println("<!-- Query: ${query} -->")
            println("<!-- No results found -->")
            println("<h1>Page Not Found</h1>")
            println("<p>The requested page '${view}' could not be found.</p>")
        }

    } catch (Exception e) {
        println("<!-- Error: ${e.message} -->")
        System.err.println("Error processing page: ${e.message}")
        e.printStackTrace()
        println("<h1>Error</h1>")
        println("<pre>${e.message}</pre>")
    }

} else {
    // No view specified
    println("<pre>")
    println("REQUEST:")
    REQUEST.each { k, v ->
        println("  ${k}: ${v}")
    }
    println("</pre>")
}
