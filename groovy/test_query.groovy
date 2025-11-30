#!/usr/bin/env groovy
/**
 * Test query script - Groovy equivalent of Python code
 * Queries wasql_test_17 database and prints results
 */

// Get script directory
def scriptDir = new File(getClass().protectionDomain.codeSource.location.path).parent

// Load required modules
def configFile = new File(scriptDir, 'config.groovy')
def config = new GroovyShell().parse(configFile)

def commonFile = new File(scriptDir, 'common.groovy')
def common = new GroovyShell().parse(commonFile)

def dbFile = new File(scriptDir, 'db.groovy')
def db = new GroovyShell().parse(dbFile)

// Execute query
def recs = db.queryResults('wasql_test_17', 'select name from states limit 5', [:])

// Print results
println(recs)
