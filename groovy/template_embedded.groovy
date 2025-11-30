/**
 * Template for embedded Groovy in WaSQL pages
 *
 * Copy this template into your WaSQL page body field
 * and replace the "YOUR CODE HERE" section with your logic
 */

// === INITIALIZATION (Required) ===
def gdir = new File('D:/wasql/groovy')
def shell = new GroovyShell()
config = shell.parse(new File(gdir, 'config.groovy'))
common = shell.parse(new File(gdir, 'common.groovy'))
db = shell.parse(new File(gdir, 'db.groovy'))

// === YOUR CODE HERE ===
def recs = db.queryResults('wasql_test_17', 'select name from states limit 5', [:])
println(recs)

// Example: Pretty print results
recs.each { rec ->
    println "State: ${rec.name}"
}

// Example: Build HTML output
def html = new StringBuilder()
html.append('<ul>')
recs.each { rec ->
    html.append("<li>${rec.name}</li>")
}
html.append('</ul>')
println html.toString()
