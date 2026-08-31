/**
 * wasql.groovy - WaSQL Module Loader and Initializer
 *
 * This file provides a simple way to load WaSQL modules in embedded Groovy scripts.
 * Include this at the start of your embedded Groovy code.
 *
 * Usage:
 * <?evalgroovy
 * load('groovy/wasql.groovy')
 * recs = db.queryResults('wasql_test_17', 'select name from states limit 5', [:])
 * println(recs)
 * ?>
 */

//---------- begin function findWasqlRoot
/**
* @describe finds the WaSQL root directory by locating the folder that contains groovy/db.groovy (current dir, then parents, then common install paths)
* @return string
*	absolute path to the WaSQL root, or null if it cannot be found
* @usage
*	root = findWasqlRoot()
*/
def findWasqlRoot() {
    def currentDir = new File('.').absoluteFile

    // Try current directory first
    def groovyDir = new File(currentDir, 'groovy')
    if (groovyDir.exists() && new File(groovyDir, 'db.groovy').exists()) {
        return currentDir.absolutePath
    }

    // Try parent directories
    currentDir = currentDir.parentFile
    while (currentDir != null) {
        groovyDir = new File(currentDir, 'groovy')
        if (groovyDir.exists() && new File(groovyDir, 'db.groovy').exists()) {
            return currentDir.absolutePath
        }
        currentDir = currentDir.parentFile
    }

    // Fallback to trying common paths
    def possiblePaths = [
        'D:/wasql',
        'C:/wasql',
        '/var/www/wasql',
        System.getProperty('user.dir')
    ]

    for (path in possiblePaths) {
        def testDir = new File(path, 'groovy')
        if (testDir.exists() && new File(testDir, 'db.groovy').exists()) {
            return path
        }
    }

    return null
}

def wasqlRoot = findWasqlRoot()
if (!wasqlRoot) {
    throw new Exception("Could not find WaSQL root directory with groovy modules")
}

def groovyPath = new File(wasqlRoot, 'groovy').absolutePath

//---------- begin function loadModule
/**
* @describe parses a WaSQL Groovy module file and returns the evaluated script object
* @param params moduleName string
*	moduleName: module name without the .groovy extension (e.g. 'db')
* @return object
* @usage
*	db = loadModule('db')
*/
def loadModule(moduleName) {
    def moduleFile = new File(groovyPath, "${moduleName}.groovy")
    if (!moduleFile.exists()) {
        throw new FileNotFoundException("Module not found: ${moduleFile.absolutePath}")
    }
    return new GroovyShell(this.class.classLoader, new Binding(this.binding.variables)).parse(moduleFile)
}

// Load core modules and make them globally available
this.binding.config = loadModule('config')
this.binding.common = loadModule('common')
this.binding.db = loadModule('db')

// Also create shorthand variables for convenience
config = this.binding.config
common = this.binding.common
db = this.binding.db

// Return this script object for chaining
return this
