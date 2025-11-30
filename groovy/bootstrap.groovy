/**
 * bootstrap.groovy - Auto-loads core WaSQL modules for embedded Groovy execution
 *
 * This file should be included at the start of embedded Groovy scripts
 * to provide access to db, config, and common modules.
 *
 * Usage in embedded Groovy:
 * <?evalgroovy
 * evaluate(new File('groovy/bootstrap.groovy'))
 * def recs = db.queryResults('wasql_test_17', 'select name from states limit 5', [:])
 * println(recs)
 * ?>
 */

import groovy.transform.Field

// Determine the WaSQL root path
def scriptFile = new File(getClass().protectionDomain.codeSource.location.toURI().path)
def scriptDir = scriptFile.parentFile.absolutePath
def wasqlRoot = scriptDir

// Check if we're in a temp directory (common for embedded execution)
if (scriptDir.contains('temp') || scriptDir.contains('tmp')) {
    // Go up to find wasql root
    def currentDir = scriptFile.parentFile
    while (currentDir != null) {
        def groovyDir = new File(currentDir, 'groovy')
        if (groovyDir.exists() && groovyDir.isDirectory()) {
            wasqlRoot = currentDir.absolutePath
            break
        }
        currentDir = currentDir.parentFile
    }
}

// Build paths to modules
def groovyDir = new File(wasqlRoot, 'groovy').absolutePath

// Load modules with proper binding
def binding = new Binding()

// Load config
def configFile = new File(groovyDir, 'config.groovy')
if (configFile.exists()) {
    binding.setVariable('config', new GroovyShell(binding).parse(configFile))
}

// Load common
def commonFile = new File(groovyDir, 'common.groovy')
if (commonFile.exists()) {
    binding.setVariable('common', new GroovyShell(binding).parse(commonFile))
}

// Load db (depends on config and common)
def dbFile = new File(groovyDir, 'db.groovy')
if (dbFile.exists()) {
    binding.setVariable('db', new GroovyShell(binding).parse(dbFile))
}

// Make variables available to calling script
if (binding.hasVariable('config')) {
    this.binding.setVariable('config', binding.getVariable('config'))
}
if (binding.hasVariable('common')) {
    this.binding.setVariable('common', binding.getVariable('common'))
}
if (binding.hasVariable('db')) {
    this.binding.setVariable('db', binding.getVariable('db'))
}

// Return true to indicate successful bootstrap
return true
