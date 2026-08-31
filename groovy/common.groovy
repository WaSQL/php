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

//---------- begin function abort
/**
* @describe aborts execution with an error message and a non-zero exit code
* @param params err Exception
* @return void
* @usage
*	common.abort(err)
*/
def abort(Exception err) {
    System.err.println("Error: ${err.message}")
    err.printStackTrace()
    System.exit(123)
}

//---------- begin function debug
/**
* @describe returns error debug string (message + stack trace) without aborting
* @param params err Exception
* @return string
* @usage
*	msg = common.debug(err)
*/
def debug(Exception err) {
    def sw = new StringWriter()
    err.printStackTrace(new PrintWriter(sw))
    return "Error: ${err.message}\n${sw.toString()}"
}

//---------- begin function arrayAverage
/**
* @describe returns the average of all elements in a list
* @param params lst list
* @return number
* @usage
*	avg = common.arrayAverage([12, 3, 4, 7])
*/
def arrayAverage(List lst) {
    if (!lst || lst.size() == 0) return 0
    return lst.sum() / lst.size()
}

//---------- begin function buildDir
/**
* @describe recursive folder creator
* @param params path string, mode integer
* @return boolean
* @usage
*	common.buildDir('/var/www/mystuff/temp/test')
*/
def buildDir(String path, int mode = 0777) {
    def dir = new File(path)
    return dir.mkdirs()
}

//---------- begin function calculateDistance
/**
* @describe calculates the distance between two longitude and latitude points
* @param params lat1 double, lon1 double, lat2 double, lon2 double, unit string
*	unit: K=kilometers, N=nautical miles, M=miles (default M)
* @return double
* @usage
*	dist = common.calculateDistance(lat1, lon1, lat2, lon2)
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

//---------- begin function coalesce
/**
* @describe returns the first non-null, non-blank value in the arguments
* @param params values mixed
* @return mixed
* @usage
*	privateToken = common.coalesce(params.token, vals.gitlab_token, '')
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

//---------- begin function decodeBase64
/**
* @describe decodes a base64 encoded string
* @param params str string
* @return string
* @usage
*	dec = common.decodeBase64(encoded_string)
*/
def decodeBase64(String str) {
    return new String(str.decodeBase64())
}

//---------- begin function decodeURL
/**
* @describe decodes a URL encoded string
* @param params str string
* @return string
* @usage
*	dec = common.decodeURL(str)
*/
def decodeURL(String str) {
    return URLDecoder.decode(URLDecoder.decode(str, 'UTF-8'), 'UTF-8')
}

//---------- begin function encodeBase64
/**
* @describe encodes a string to base64
* @param params str string
* @return string
* @usage
*	enc = common.encodeBase64(str)
*/
def encodeBase64(String str) {
    return str.bytes.encodeBase64().toString()
}

//---------- begin function encodeHtml
/**
* @describe encodes HTML special characters
* @param params str string
* @return string
* @usage
*	html = common.encodeHtml(str)
*/
def encodeHtml(String str = '') {
    if (!str || str.length() == 0) return str

    return str.replace('&', '&amp;')
              .replace('<', '&lt;')
              .replace('>', '&gt;')
              .replace('"', '&quot;')
              .replace("'", '&#39;')
}

//---------- begin function encodeURL
/**
* @describe encodes a URL string
* @param params str string
* @return string
* @usage
*	enc = common.encodeURL(str)
*/
def encodeURL(String str) {
    return URLEncoder.encode(str, 'UTF-8')
}

//---------- begin function encodeJson
/**
* @describe encodes an object to JSON
* @param params obj mixed
* @return string
* @usage
*	json = common.encodeJson(obj)
*/
def encodeJson(Object obj) {
    return new JsonBuilder(obj).toString()
}

//---------- begin function decodeJson
/**
* @describe decodes a JSON string
* @param params str string
* @return mixed
* @usage
*	obj = common.decodeJson(str)
*/
def decodeJson(String str) {
    return new JsonSlurper().parseText(str)
}

//---------- begin function echo
/**
* @describe prints output (wrapper for println) - appends <br /> when not on the CLI
* @param params str string
* @return void
* @usage
*	common.echo('hello')
*/
def echo(String str) {
    if (isCLI()) {
        println(str)
    } else {
        println("${str}<br />")
    }
}

//---------- begin function formatPhone
/**
* @describe formats a phone number
* @param params phone string
* @return string
* @usage
*	ph = common.formatPhone('8014584741')
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

//---------- begin function getFileContents
/**
* @describe gets the contents of a file
* @param params filename string
* @return string
* @usage
*	content = common.getFileContents('/var/tmp/abc.txt')
*/
def getFileContents(String filename) {
    return new File(filename).text
}

//---------- begin function setFileContents
/**
* @describe sets the contents of a file
* @param params filename string, data string, append boolean
*	append: set to true to append (defaults to false)
* @return void
* @usage
*	common.setFileContents(file, data)
*/
def setFileContents(String filename, String data, boolean append = false) {
    def file = new File(filename)
    if (append) {
        file.append(data)
    } else {
        file.text = data
    }
}

//---------- begin function getParentPath
/**
* @describe gets the parent path of a file or directory
* @param params path string
* @return string
* @usage
*	parent = common.getParentPath(path)
*/
def getParentPath(String path) {
    return new File(path).parentFile.absolutePath
}

//---------- begin function getRandomString
/**
* @describe returns a random string
* @param params size integer, chars string
*	size: length of string (default 6)
*	chars: characters to pick from (optional)
* @return string
* @usage
*	id = common.getRandomString(6)
*/
def getRandomString(int size = 6, String chars = null) {
    if (!chars) {
        chars = (('A'..'Z') + ('0'..'9')).join()
    }
    def random = new Random()
    return (1..size).collect { chars[random.nextInt(chars.length())] }.join()
}

//---------- begin function getCSVRecords
/**
* @describe reads a CSV file and returns records as a list of maps
* @param params afile string, params map
*	params.start: first data row index (default 1)
*	params.stop: last data row index (default end of file)
* @return list
* @usage
*	recs = common.getCSVRecords(afile)
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

//---------- begin function getWasqlPath
/**
* @describe returns the WaSQL root path, optionally with a subdirectory appended
* @param params str string
*	str: subdirectory to append (optional)
* @return string
* @usage
*	wpath = common.getWasqlPath('wfiles')
*/
def getWasqlPath(String str = '') {
    def wpath = getParentPath(scriptPath())
    if (str) {
        wpath += "/${str}"
    }
    return wpath
}

//---------- begin function hex2RGB
/**
* @describe converts a hex color string to an [r, g, b] list
* @param params hexvalue string
* @return list
* @usage
*	rgb = common.hex2RGB('#9495a3')
*/
def hex2RGB(String hexvalue) {
    def hex = hexvalue.replaceAll('#', '')
    def r = Integer.parseInt(hex[0..1], 16)
    def g = Integer.parseInt(hex[2..3], 16)
    def b = Integer.parseInt(hex[4..5], 16)
    return [r, g, b]
}

//---------- begin function rgb2HEX
/**
* @describe converts an [r, g, b] list to a hex color string
* @param params rgb list
* @return string
* @usage
*	hex = common.rgb2HEX([148, 149, 163])
*/
def rgb2HEX(List rgb) {
    return String.format('#%02x%02x%02x', rgb[0], rgb[1], rgb[2])
}

//---------- begin function isCLI
/**
* @describe returns true if the script is running from the command line
* @return boolean
* @usage
*	if (common.isCLI()) { ... }
*/
def isCLI() {
    return System.console() != null
}

//---------- begin function isDate
/**
* @describe returns true if the string is a valid date for the given format
* @param params string string, format string
*	format: date format (default yyyy-MM-dd)
* @return boolean
* @usage
*	if (common.isDate('2024-10-11')) { ... }
*/
def isDate(String string, String format = 'yyyy-MM-dd') {
    try {
        new SimpleDateFormat(format).parse(string)
        return true
    } catch (Exception e) {
        return false
    }
}

//---------- begin function isEmail
/**
* @describe returns true if the string is a valid email address
* @param params str string
* @return boolean
* @usage
*	if (common.isEmail(str)) { ... }
*/
def isEmail(String str) {
    def pattern = /^[\w\.\+\-]+\@[\w]+\.[a-z]{2,10}$/
    return str ==~ pattern
}

//---------- begin function isEven
/**
* @describe returns true if the number is even
* @param params num integer
* @return boolean
* @usage
*	if (common.isEven(num)) { ... }
*/
def isEven(int num) {
    return num % 2 == 0
}

//---------- begin function isJson
/**
* @describe returns true if the object is a valid JSON string
* @param params obj mixed
* @return boolean
* @usage
*	if (common.isJson(obj)) { ... }
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

//---------- begin function isWindows
/**
* @describe returns true if the script is running on a Windows platform
* @return boolean
* @usage
*	if (common.isWindows()) { ... }
*/
def isWindows() {
    def os = System.getProperty('os.name').toLowerCase()
    return os.contains('win')
}

//---------- begin function nl2BR
/**
* @describe converts new lines to <br /> tags in a string
* @param params string string
* @return string
* @usage
*	print(common.nl2BR(str))
*/
def nl2BR(String string) {
    return string.replaceAll('\n', '<br />\n')
}

//---------- begin function scriptPath
/**
* @describe returns the script directory, optionally with a subdirectory appended
* @param params d string
*	d: subdirectory to append (optional)
* @return string
* @usage
*	path = common.scriptPath()
*	path = common.scriptPath('/temp')
*/
def scriptPath(String d = '') {
    def scriptDir = new File(getClass().protectionDomain.codeSource.location.path).parent
    if (d) {
        return new File(scriptDir, d).absolutePath
    }
    return scriptDir
}

//---------- begin function sleep
/**
* @describe sleeps for x seconds
* @param params x integer
* @return void
* @usage
*	common.sleep(3)
*/
def sleep(int x) {
    Thread.sleep(x * 1000)
}

//---------- begin function stringContains
/**
* @describe returns true if str contains substr
* @param params str string, substr string
* @return boolean
* @usage
*	if (common.stringContains(str, val)) { ... }
*/
def stringContains(String str, String substr) {
    return str.contains(substr)
}

//---------- begin function stringEndsWith
/**
* @describe returns true if str ends with substr
* @param params str string, substr string
* @return boolean
* @usage
*	if (common.stringEndsWith(str, val)) { ... }
*/
def stringEndsWith(String str, String substr) {
    return str.endsWith(substr)
}

//---------- begin function stringBeginsWith
/**
* @describe returns true if str begins with substr
* @param params str string, substr string
* @return boolean
* @usage
*	if (common.stringBeginsWith(str, val)) { ... }
*/
def stringBeginsWith(String str, String substr) {
    return str.startsWith(substr)
}

//---------- begin function str_replace
/**
* @describe replaces str with str2 in str3
* @param params str string, str2 string, str3 string
* @return string
* @usage
*	newstr = common.str_replace('a', 'b', 'abb')
*/
def str_replace(String str, String str2, String str3) {
    return str3.replace(str, str2)
}

//---------- begin function time
/**
* @describe returns the current unix timestamp (seconds)
* @return long
* @usage
*	t = common.time()
*/
def time() {
    return System.currentTimeMillis() / 1000
}

//---------- begin function strtotime
/**
* @describe converts a string to a unix timestamp (mimics PHP's strtotime)
* @param params str string
* @return long
* @usage
*	ts = common.strtotime('2024-01-15')
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

//---------- begin function listFiles
/**
* @describe lists file names in a directory
* @param params adir string
* @return list
* @usage
*	files = common.listFiles(mypath)
*/
def listFiles(String adir) {
    def dir = new File(adir)
    if (!dir.exists() || !dir.isDirectory()) {
        return []
    }
    return dir.listFiles()*.name
}

//---------- begin function listFilesEx
/**
* @describe lists files in a directory with extended information (name, path, size, mtime, ...)
* @param params adir string
* @return list
* @usage
*	files = common.listFilesEx(mypath)
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

//---------- begin function printValue
/**
* @describe prints a value in a formatted <pre> block (universal debug helper)
* @param params obj mixed
* @return void
* @usage
*	common.printValue(recs)
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

//---------- begin function parseViews
/**
* @describe parses <view:name>...</view:name> blocks from an HTML string into the VIEWS map
* @param params str string
* @return boolean
* @usage
*	common.parseViews(PAGE.body)
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

//---------- begin function setView
/**
* @describe selects a parsed view for rendering, optionally clearing previously selected views
* @param params name string, clear integer
*	clear: pass 1 to clear other selected views first (default 0, cumulative)
* @return void
* @usage
*	common.setView('default')
*/
def setView(String name, int clear = 0) {
    if (clear == 1) {
        VIEW.clear()
    }
    if (VIEWS.containsKey(name)) {
        VIEW[name] = VIEWS[name]
    }
}

//---------- begin function cmdResults
/**
* @describe executes a shell command and returns its output
* @param params cmd string, args string
*	args: command arguments (optional)
* @return string
* @usage
*	out = common.cmdResults('ls', '-al')
*/
def cmdResults(String cmd, String args = '') {
    def command = args ? "${cmd} ${args}" : cmd
    def process = command.execute()
    process.waitFor()
    return process.text
}

//---------- begin function md5
/**
* @describe returns the MD5 hash of a string
* @param params str string
* @return string
* @usage
*	hash = common.md5(str)
*/
def md5(String str) {
    MessageDigest.getInstance("MD5").digest(str.bytes).encodeHex().toString()
}

// Export for use as module
return this
