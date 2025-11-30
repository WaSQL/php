/**
common.groovy - Common utility functions for WaSQL Groovy
Provides utility functions for dates, strings, files, encoding, and more

References:
    https://groovy-lang.org/groovy-dev-kit.html
*/

import groovy.json.JsonBuilder
import groovy.json.JsonSlurper
import groovy.xml.MarkupBuilder
import java.text.SimpleDateFormat
import java.util.regex.Pattern
import java.net.URLEncoder
import java.net.URLDecoder
import java.nio.file.Files
import java.nio.file.Paths
import java.security.MessageDigest

// Global variables
def VIEWS = [:]
def VIEW = [:]
def DEBUG = []

/**
 * Aborts execution with error message
 * @param err Exception object
 * @usage common.abort(err)
 */
def abort(Exception err) {
    System.err.println("Error: ${err.message}")
    err.printStackTrace()
    System.exit(123)
}

/**
 * Returns error debug string without aborting
 * @param err Exception object
 * @return String error message
 * @usage msg = common.debug(err)
 */
def debug(Exception err) {
    def sw = new StringWriter()
    err.printStackTrace(new PrintWriter(sw))
    return "Error: ${err.message}\n${sw.toString()}"
}

/**
 * Returns average of all elements in a list
 * @param lst List
 * @return Number average
 * @usage avg = common.arrayAverage([12, 3, 4, 7])
 */
def arrayAverage(List lst) {
    if (!lst || lst.size() == 0) return 0
    return lst.sum() / lst.size()
}

/**
 * Recursive folder creator
 * @param path String path to create
 * @param mode Integer create mode (optional)
 * @return boolean
 * @usage common.buildDir('/var/www/mystuff/temp/test')
 */
def buildDir(String path, int mode = 0777) {
    def dir = new File(path)
    return dir.mkdirs()
}

/**
 * Calculates distance between two longitude & latitude points
 * @param lat1 Double first latitude
 * @param lon1 Double first longitude
 * @param lat2 Double second latitude
 * @param lon2 Double second longitude
 * @param unit Char unit of measure - K=kilometers, N=nautical miles, M=miles
 * @return Double distance
 * @usage dist = common.calculateDistance(lat1, lon1, lat2, lon2)
 */
def calculateDistance(double lat1, double lon1, double lat2, double lon2, String unit = 'M') {
    // Approximate radius of earth in km
    def R = 6373.0

    def lat1Rad = Math.toRadians(Math.abs(lat1))
    def lon1Rad = Math.toRadians(Math.abs(lon1))
    def lat2Rad = Math.toRadians(Math.abs(lat2))
    def lon2Rad = Math.toRadians(Math.abs(lon2))

    def dlon = lon2Rad - lon1Rad
    def dlat = lat2Rad - lat1Rad

    def a = Math.sin(dlat / 2)**2 + Math.cos(lat1Rad) * Math.cos(lat2Rad) * Math.sin(dlon / 2)**2
    def c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))

    def distance = R * c

    // Convert to miles if requested
    if (unit == 'M') {
        return distance * 0.621371
    }
    return distance
}

/**
 * Returns the first non-null, non-blank value in arguments
 * @param values Variable arguments
 * @return Mixed first non-empty value
 * @usage privateToken = common.coalesce(params.token, vals.gitlab_token, '')
 */
def coalesce(Object... values) {
    for (v in values) {
        if (v != null) {
            if (v instanceof String && v.length() > 0) return v
            else if (v instanceof Number) return v
            else if (v instanceof Collection && v.size() > 0) return v
            else if (v instanceof Map && v.size() > 0) return v
        }
    }
    return ''
}

/**
 * Decodes a base64 encoded string
 * @param str String base64 string to decode
 * @return String decoded string
 * @usage dec = common.decodeBase64(encoded_string)
 */
def decodeBase64(String str) {
    return new String(str.decodeBase64())
}

/**
 * Decodes a URL encoded string
 * @param str String to decode
 * @return String decoded string
 * @usage dec = common.decodeURL(str)
 */
def decodeURL(String str) {
    return URLDecoder.decode(URLDecoder.decode(str, 'UTF-8'), 'UTF-8')
}

/**
 * Encodes a string to base64
 * @param str String to encode
 * @return String base64 encoded string
 * @usage enc = common.encodeBase64(str)
 */
def encodeBase64(String str) {
    return str.bytes.encodeBase64().toString()
}

/**
 * Encodes HTML special characters
 * @param str String to encode
 * @return String encoded string
 * @usage html = common.encodeHtml(str)
 */
def encodeHtml(String str = '') {
    if (!str || str.length() == 0) return str

    return str.replace('&', '&amp;')
              .replace('<', '&lt;')
              .replace('>', '&gt;')
              .replace('"', '&quot;')
              .replace("'", '&#39;')
}

/**
 * Encodes a URL string
 * @param str String to encode
 * @return String encoded string
 * @usage enc = common.encodeURL(str)
 */
def encodeURL(String str) {
    return URLEncoder.encode(str, 'UTF-8')
}

/**
 * Encodes an object to JSON
 * @param obj Object to encode
 * @return String JSON encoded string
 * @usage json = common.encodeJson(obj)
 */
def encodeJson(Object obj) {
    return new JsonBuilder(obj).toString()
}

/**
 * Decodes a JSON string
 * @param str String JSON string to decode
 * @return Object decoded object
 * @usage obj = common.decodeJson(str)
 */
def decodeJson(String str) {
    return new JsonSlurper().parseText(str)
}

/**
 * Prints output (wrapper for println)
 * @param str String to print
 * @usage common.echo('hello')
 */
def echo(String str) {
    if (isCLI()) {
        println(str)
    } else {
        println("${str}<br />")
    }
}

/**
 * Formats a phone number
 * @param phone String phone number
 * @return String formatted phone number
 * @usage ph = common.formatPhone('8014584741')
 */
def formatPhone(String phone) {
    def clean = phone.replaceAll('[^0-9]', '')
    if (clean.length() == 10) {
        return "(${clean[0..2]}) ${clean[3..5]}-${clean[6..9]}"
    } else if (clean.length() == 11) {
        return "+${clean[0]} (${clean[1..3]}) ${clean[4..6]}-${clean[7..10]}"
    }
    return phone
}

/**
 * Gets the contents of a file
 * @param filename String full path to file
 * @return String file contents
 * @usage content = common.getFileContents('/var/tmp/abc.txt')
 */
def getFileContents(String filename) {
    return new File(filename).text
}

/**
 * Sets the contents of a file
 * @param filename String full path to file
 * @param data String data to write
 * @param append Boolean set to true to append (defaults to false)
 * @usage common.setFileContents(file, data)
 */
def setFileContents(String filename, String data, boolean append = false) {
    def file = new File(filename)
    if (append) {
        file.append(data)
    } else {
        file.text = data
    }
}

/**
 * Gets the parent path
 * @param path String path
 * @return String parent path
 * @usage parent = common.getParentPath(path)
 */
def getParentPath(String path) {
    return new File(path).parentFile.absolutePath
}

/**
 * Returns a random string
 * @param size Integer size of string (default 6)
 * @param chars String characters to use (optional)
 * @return String random string
 * @usage id = common.getRandomString(6)
 */
def getRandomString(int size = 6, String chars = null) {
    if (!chars) {
        chars = (('A'..'Z') + ('0'..'9')).join()
    }
    def random = new Random()
    return (1..size).collect { chars[random.nextInt(chars.length())] }.join()
}

/**
 * Reads CSV file and returns records as list of maps
 * @param afile String full path to CSV file
 * @param params Map parameters (optional)
 * @return List of Maps (records)
 * @usage recs = common.getCSVRecords(afile)
 */
def getCSVRecords(String afile, Map params = [:]) {
    def recs = []
    def file = new File(afile)

    if (!file.exists()) {
        return recs
    }

    def lines = file.readLines('UTF-8')
    if (lines.size() == 0) return recs

    // First line is header
    def fields = lines[0].split(',').collect { it.trim().replaceAll('"', '') }

    // Process data rows
    def startRow = params.start ?: 1
    def stopRow = params.stop ?: lines.size()

    for (int i = startRow; i < Math.min(stopRow, lines.size()); i++) {
        def values = lines[i].split(',(?=(?:[^"]*"[^"]*")*[^"]*$)') // Split on comma, but not inside quotes
        def rec = [:]

        fields.eachWithIndex { field, idx ->
            if (idx < values.size()) {
                rec[field] = values[idx].trim().replaceAll('^"|"$', '')
            }
        }

        recs << rec
    }

    return recs
}

/**
 * Returns WaSQL path
 * @param str String subdirectory (optional)
 * @return String path
 * @usage wpath = common.getWasqlPath('wfiles')
 */
def getWasqlPath(String str = '') {
    def wpath = getParentPath(scriptPath())
    if (str) {
        wpath += "/${str}"
    }
    return wpath
}

/**
 * Converts hex string to RGB tuple
 * @param hexvalue String hex color value
 * @return List [r, g, b]
 * @usage rgb = common.hex2RGB('#9495a3')
 */
def hex2RGB(String hexvalue) {
    def hex = hexvalue.replaceAll('#', '')
    def r = Integer.parseInt(hex[0..1], 16)
    def g = Integer.parseInt(hex[2..3], 16)
    def b = Integer.parseInt(hex[4..5], 16)
    return [r, g, b]
}

/**
 * Converts RGB tuple to hex string
 * @param rgb List [r, g, b]
 * @return String hex color value
 * @usage hex = common.rgb2HEX([148, 149, 163])
 */
def rgb2HEX(List rgb) {
    return String.format('#%02x%02x%02x', rgb[0], rgb[1], rgb[2])
}

/**
 * Returns true if script is running from command line
 * @return boolean
 * @usage if (common.isCLI())
 */
def isCLI() {
    return System.console() != null
}

/**
 * Returns true if string is a valid date
 * @param string String to check
 * @param format String date format (default: yyyy-MM-dd)
 * @return boolean
 * @usage if (common.isDate('2024-10-11'))
 */
def isDate(String string, String format = 'yyyy-MM-dd') {
    try {
        new SimpleDateFormat(format).parse(string)
        return true
    } catch (Exception e) {
        return false
    }
}

/**
 * Returns true if specified string is a valid email address
 * @param str String to check
 * @return boolean
 * @usage if (common.isEmail(str))
 */
def isEmail(String str) {
    def pattern = /^[\w\.\+\-]+\@[\w]+\.[a-z]{2,10}$/
    return str ==~ pattern
}

/**
 * Returns true if specified number is an even number
 * @param num Number to check
 * @return boolean
 * @usage if (common.isEven(num))
 */
def isEven(int num) {
    return num % 2 == 0
}

/**
 * Returns true if specified object is JSON
 * @param obj Object to check
 * @return boolean
 * @usage if (common.isJson(obj))
 */
def isJson(Object obj) {
    if (obj instanceof String) {
        try {
            new JsonSlurper().parseText(obj)
            return true
        } catch (Exception e) {
            return false
        }
    }
    return false
}

/**
 * Returns true if script is running on Windows platform
 * @return boolean
 * @usage if (common.isWindows())
 */
def isWindows() {
    def os = System.getProperty('os.name').toLowerCase()
    return os.contains('win')
}

/**
 * Converts new lines to <br /> tags in string
 * @param string String to convert
 * @return String converted string
 * @usage print(common.nl2br(str))
 */
def nl2BR(String string) {
    return string.replaceAll('\n', '<br />\n')
}

/**
 * Returns script path
 * @param d String subdirectory (optional)
 * @return String path
 * @usage path = common.scriptPath()
 * @usage path = common.scriptPath('/temp')
 */
def scriptPath(String d = '') {
    def scriptDir = new File(getClass().protectionDomain.codeSource.location.path).parent
    if (d) {
        return new File(scriptDir, d).absolutePath
    }
    return scriptDir
}

/**
 * Sleeps for x seconds
 * @param x Number seconds
 * @usage common.sleep(3)
 */
def sleep(int x) {
    Thread.sleep(x * 1000)
}

/**
 * Returns true if string contains substr
 * @param str String to check
 * @param substr String substring to find
 * @return boolean
 * @usage if (common.stringContains(str, val))
 */
def stringContains(String str, String substr) {
    return str.contains(substr)
}

/**
 * Returns true if string ends with substr
 * @param str String to check
 * @param substr String substring to find
 * @return boolean
 * @usage if (common.stringEndsWith(str, val))
 */
def stringEndsWith(String str, String substr) {
    return str.endsWith(substr)
}

/**
 * Returns true if string begins with substr
 * @param str String to check
 * @param substr String substring to find
 * @return boolean
 * @usage if (common.stringBeginsWith(str, val))
 */
def stringBeginsWith(String str, String substr) {
    return str.startsWith(substr)
}

/**
 * Replaces str with str2 in str3
 * @param str String to find
 * @param str2 String replacement
 * @param str3 String source
 * @return String result
 * @usage newstr = common.str_replace('a', 'b', 'abb')
 */
def str_replace(String str, String str2, String str3) {
    return str3.replace(str, str2)
}

/**
 * Returns unix timestamp
 * @return Long timestamp
 * @usage t = common.time()
 */
def time() {
    return System.currentTimeMillis() / 1000
}

/**
 * Converts a string to unix timestamp (mimics PHP's strtotime)
 * @param str String to parse
 * @return Long unix timestamp
 * @usage ts = common.strtotime('2024-01-15')
 */
def strtotime(String str) {
    try {
        def date = Date.parse('yyyy-MM-dd HH:mm:ss', str)
        return date.time / 1000
    } catch (Exception e1) {
        try {
            def date = Date.parse('yyyy-MM-dd', str)
            return date.time / 1000
        } catch (Exception e2) {
            return null
        }
    }
}

/**
 * Lists files in a directory
 * @param adir String directory path
 * @return List of filenames
 * @usage files = common.listFiles(mypath)
 */
def listFiles(String adir) {
    def dir = new File(adir)
    if (!dir.exists() || !dir.isDirectory()) {
        return []
    }
    return dir.listFiles()*.name
}

/**
 * Lists files in a directory with extended information
 * @param adir String directory path
 * @return List of Maps with file information
 * @usage files = common.listFilesEx(mypath)
 */
def listFilesEx(String adir) {
    def dir = new File(adir)
    if (!dir.exists() || !dir.isDirectory()) {
        return []
    }

    return dir.listFiles().collect { file ->
        [
            name: file.name,
            path: adir,
            afile: file.absolutePath,
            ext: file.name.substring(file.name.lastIndexOf('.') + 1),
            size: file.size(),
            mtime: file.lastModified(),
            mdate: new Date(file.lastModified()).format('yyyy-MM-dd')
        ]
    }
}

/**
 * Prints value in a formatted way
 * @param obj Object to print
 * @usage common.printValue(recs)
 */
def printValue(Object obj) {
    if (isJson(obj)) {
        println('<pre class="printvalue" type="JSON">')
        println(new JsonBuilder(obj).toPrettyString())
        println('</pre>')
    } else {
        println('<pre class="printvalue" type="' + obj.getClass().simpleName + '">')
        println(obj.inspect())
        println('</pre>')
    }
}

/**
 * Parses views from HTML string
 * @param str String containing view tags
 * @return boolean
 */
def parseViews(String str) {
    VIEWS.clear()
    def pattern = ~/<view:(.*?)>(.+?)<\/view:\1>/
    def matcher = pattern.matcher(str)

    while (matcher.find()) {
        def viewname = matcher.group(1)
        def viewbody = matcher.group(2)
        VIEWS[viewname] = viewbody
    }

    return true
}

/**
 * Sets a view for rendering
 * @param name String view name
 * @param clear Integer clear existing views (default 0)
 */
def setView(String name, int clear = 0) {
    if (clear == 1) {
        VIEW.clear()
    }
    if (VIEWS.containsKey(name)) {
        VIEW[name] = VIEWS[name]
    }
}

/**
 * Executes a command and returns results
 * @param cmd String command to execute
 * @param args String arguments (optional)
 * @return String command output
 * @usage out = common.cmdResults('ls', '-al')
 */
def cmdResults(String cmd, String args = '') {
    def command = args ? "${cmd} ${args}" : cmd
    def process = command.execute()
    process.waitFor()
    return process.text
}

/**
 * Converts MD5 hash of string
 * @param str String to hash
 * @return String MD5 hash
 * @usage hash = common.md5(str)
 */
def md5(String str) {
    MessageDigest.getInstance("MD5").digest(str.bytes).encodeHex().toString()
}

// Export for use as module
return this
