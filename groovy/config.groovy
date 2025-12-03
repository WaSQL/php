/**
config.groovy parses config.xml and builds CONFIG and ALLCONFIG maps

References:
    https://groovy-lang.org/processing-xml.html

Installation:
    Groovy includes XmlSlurper by default - no additional dependencies needed
*/

import groovy.xml.XmlSlurper
import groovy.xml.slurpersupport.GPathResult

// Get paths - try multiple methods for cross-platform compatibility
def scriptDir = null
def parentPath = null
def configFile = null

try {
    // Method 1: Using codeSource location
    scriptDir = new File(getClass().protectionDomain.codeSource.location.path).parent
    parentPath = new File(scriptDir).parentFile.absolutePath
    configFile = "${parentPath}${File.separator}config.xml"

    // Check if config exists, if not try other methods
    if (!new File(configFile).exists()) {
        throw new Exception("Config not found at ${configFile}")
    }
} catch (Exception e1) {
    try {
        // Method 2: Using URI to handle URL encoding properly
        def thisFile = new File(getClass().protectionDomain.codeSource.location.toURI())
        scriptDir = thisFile.isFile() ? thisFile.parentFile.absolutePath : thisFile.absolutePath
        parentPath = new File(scriptDir).parentFile.absolutePath
        configFile = "${parentPath}${File.separator}config.xml"

        if (!new File(configFile).exists()) {
            throw new Exception("Config not found at ${configFile}")
        }
    } catch (Exception e2) {
        // Method 3: Try current directory's parent
        scriptDir = new File('.').absolutePath
        parentPath = new File(scriptDir).parentFile.absolutePath
        configFile = "${parentPath}${File.separator}config.xml"

        if (!new File(configFile).exists()) {
            // Method 4: Try ../config.xml relative
            configFile = "../config.xml"
        }
    }
}

// HTTP_HOST - default to localhost for command line stuff
// Remove 'def' to make these script binding variables accessible to other scripts
HTTP_HOST = System.getenv('HTTP_HOST') ?: 'localhost'

// ALLCONFIG
ALLCONFIG = [:]
try {
    def xmlFile = new File(configFile)
    if (xmlFile.exists()) {
        def xmlText = xmlFile.text
        def xml = new XmlSlurper().parseText(xmlText)

        // Convert XML to Map recursively
        ALLCONFIG = xmlToMap(xml)
    } else {
        System.err.println("Config file not found: ${configFile}")
    }
} catch (Exception e) {
    System.err.println("Error parsing config.xml: ${e.message}")
    e.printStackTrace()
}

// DATABASE
DATABASE = [:]
if (ALLCONFIG.database) {
    def dbList = ALLCONFIG.database
    if (dbList instanceof Map) {
        // Single database
        def db = dbList
        if (db.name) {
            DATABASE[db.name] = db
        }
    } else if (dbList instanceof List) {
        // Multiple databases
        dbList.each { db ->
            if (db instanceof Map && db.name) {
                DATABASE[db.name] = db
            }
        }
    }
}

// CONFIG
CONFIG = [:]
if (ALLCONFIG.host) {
    def hostList = ALLCONFIG.host
    def hosts = hostList instanceof List ? hostList : [hostList]

    hosts.each { chost ->
        if (chost instanceof Map && chost.name == HTTP_HOST) {
            // Load allhost keys first
            if (ALLCONFIG.allhost) {
                ALLCONFIG.allhost.each { k, v ->
                    CONFIG[k] = v
                }
            }

            // Check for sameas
            if (chost.sameas) {
                hosts.each { shost ->
                    if (shost.name == chost.sameas) {
                        shost.each { k, v ->
                            CONFIG[k] = v
                        }
                    }
                }
            }

            // Load the host keys (override)
            chost.each { k, v ->
                CONFIG[k] = v
            }
        }
    }
}

/**
 * Helper function to recursively convert XML GPathResult to Map
 * @param node GPathResult node
 * @return Map representation of XML
 */
def xmlToMap(node) {
    def map = [:]

    if (node.children().size() == 0) {
        // Leaf node - return text value
        return node.text()
    }

    // Get attributes
    node.attributes().each { k, v ->
        map[k.toString()] = v
    }

    // Group children by name
    def childGroups = node.children().groupBy { it.name() }

    childGroups.each { name, children ->
        if (children.size() == 1) {
            // Single child
            def child = children[0]
            if (child.children().size() == 0 && child.attributes().size() == 0) {
                // Simple text node
                map[name] = child.text()
            } else {
                // Complex node
                map[name] = xmlToMap(child)
            }
        } else {
            // Multiple children with same name - create list
            map[name] = children.collect { xmlToMap(it) }
        }
    }

    return map
}

/**
 * Returns a specific key value or the whole config map
 * @param k String key (optional)
 * @return Mixed - value for key if key is passed in, else returns the config map
 * @usage v = config.value('name')
 * @usage c = config.value()
 */
def value(k = '') {
    if (k && CONFIG.containsKey(k)) {
        return CONFIG[k]
    }
    return CONFIG
}

/**
 * Returns a specific key value or the whole DATABASE map
 * @param k String key (optional)
 * @param sk String sub-key (optional)
 * @return Mixed - value for key if key is passed in, else returns the DATABASE map
 * @usage v = config.database('name')
 * @usage c = config.database()
 * @usage v = config.database('dbname', 'dbuser')
 */
def database(k = '', sk = '') {
    if (k && DATABASE.containsKey(k)) {
        if (sk && DATABASE[k] instanceof Map && DATABASE[k].containsKey(sk)) {
            return DATABASE[k][sk]
        }
        return DATABASE[k]
    }
    return DATABASE
}

// Export for use as module
return this
