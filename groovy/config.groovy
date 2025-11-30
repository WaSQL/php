/**
config.groovy parses config.xml and builds CONFIG and ALLCONFIG maps

References:
    https://groovy-lang.org/processing-xml.html

Installation:
    Groovy includes XmlSlurper by default - no additional dependencies needed
*/

import groovy.xml.XmlSlurper
import groovy.xml.slurpersupport.GPathResult

// Get paths
def scriptDir = new File(getClass().protectionDomain.codeSource.location.path).parent
def parentPath = new File(scriptDir).parentFile.absolutePath
def configFile = "${parentPath}${File.separator}config.xml"

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
def DATABASE = [:]
if (ALLCONFIG.hosts?.database) {
    def dbList = ALLCONFIG.hosts.database
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
def CONFIG = [:]
if (ALLCONFIG.hosts?.host) {
    def hostList = ALLCONFIG.hosts.host
    def hosts = hostList instanceof List ? hostList : [hostList]

    hosts.each { chost ->
        if (chost instanceof Map && chost.name == HTTP_HOST) {
            // Load allhost keys first
            if (ALLCONFIG.hosts?.allhost) {
                ALLCONFIG.hosts.allhost.each { k, v ->
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
