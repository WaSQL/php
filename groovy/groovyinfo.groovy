/**
groovyinfo.groovy - Displays Groovy version and system information

This script displays information about the Groovy environment,
JVM properties, and system configuration.

Installation:
    Groovy is self-contained - no additional dependencies needed

Usage:
    groovy groovyinfo.groovy
*/

import java.lang.management.ManagementFactory
import java.lang.management.MemoryMXBean
import java.lang.management.RuntimeMXBean

// Get system information
def runtimeMXBean = ManagementFactory.getRuntimeMXBean()
def memoryMXBean = ManagementFactory.getMemoryMXBean()
def heapMemory = memoryMXBean.heapMemoryUsage
def nonHeapMemory = memoryMXBean.nonHeapMemoryUsage

// Build output HTML
def prows = new StringBuilder()

// Groovy Version Section
prows.append('''
<section>
<h2><a name="groovy_version">Groovy Version</a></h2>
<table>
''')

prows.append(createRow('Groovy Version', GroovySystem.version))
prows.append(createRow('JVM Version', System.getProperty('java.version')))
prows.append(createRow('JVM Vendor', System.getProperty('java.vendor')))
prows.append(createRow('JVM Home', System.getProperty('java.home')))

prows.append('</table>\n</section>\n\n')

// Runtime Information Section
prows.append('''
<section>
<h2><a name="runtime_info">Runtime Information</a></h2>
<table>
''')

prows.append(createRow('Name', runtimeMXBean.name))
prows.append(createRow('VM Name', runtimeMXBean.vmName))
prows.append(createRow('VM Vendor', runtimeMXBean.vmVendor))
prows.append(createRow('VM Version', runtimeMXBean.vmVersion))
prows.append(createRow('Spec Name', runtimeMXBean.specName))
prows.append(createRow('Spec Vendor', runtimeMXBean.specVendor))
prows.append(createRow('Spec Version', runtimeMXBean.specVersion))
prows.append(createRow('Management Spec Version', runtimeMXBean.managementSpecVersion))
prows.append(createRow('Uptime (ms)', runtimeMXBean.uptime.toString()))
prows.append(createRow('Start Time', new Date(runtimeMXBean.startTime).toString()))

prows.append('</table>\n</section>\n\n')

// Memory Information Section
prows.append('''
<section>
<h2><a name="memory_info">Memory Information</a></h2>
<table>
''')

prows.append(createRow('Heap Memory Init (MB)', String.format('%.2f', heapMemory.init / (1024 * 1024))))
prows.append(createRow('Heap Memory Used (MB)', String.format('%.2f', heapMemory.used / (1024 * 1024))))
prows.append(createRow('Heap Memory Committed (MB)', String.format('%.2f', heapMemory.committed / (1024 * 1024))))
prows.append(createRow('Heap Memory Max (MB)', String.format('%.2f', heapMemory.max / (1024 * 1024))))
prows.append(createRow('Non-Heap Memory Init (MB)', String.format('%.2f', nonHeapMemory.init / (1024 * 1024))))
prows.append(createRow('Non-Heap Memory Used (MB)', String.format('%.2f', nonHeapMemory.used / (1024 * 1024))))
prows.append(createRow('Non-Heap Memory Committed (MB)', String.format('%.2f', nonHeapMemory.committed / (1024 * 1024))))

prows.append('</table>\n</section>\n\n')

// System Properties Section
prows.append('''
<section>
<h2><a name="system_properties">System Properties</a></h2>
<table>
''')

// Sort system properties for easier reading
def sortedProps = System.properties.sort { a, b -> a.key <=> b.key }

sortedProps.each { key, value ->
    // Skip some verbose properties
    if (key in ['line.separator', 'path.separator']) {
        return
    }

    // Escape HTML special characters
    def escapedValue = value.toString()
        .replace('&', '&amp;')
        .replace('<', '&lt;')
        .replace('>', '&gt;')
        .replace('"', '&quot;')

    prows.append(createRow(key.toString(), escapedValue))
}

prows.append('</table>\n</section>\n\n')

// Environment Variables Section
prows.append('''
<section>
<h2><a name="environment_variables">Environment Variables</a></h2>
<table>
''')

// Sort environment variables
def sortedEnv = System.getenv().sort { a, b -> a.key <=> b.key }

sortedEnv.each { key, value ->
    // Escape HTML special characters
    def escapedValue = value.toString()
        .replace('&', '&amp;')
        .replace('<', '&lt;')
        .replace('>', '&gt;')
        .replace('"', '&quot;')

    prows.append(createRow(key, escapedValue))
}

prows.append('</table>\n</section>\n\n')

// Class Path Section
prows.append('''
<section>
<h2><a name="classpath">Class Path</a></h2>
<table>
''')

def classPath = System.getProperty('java.class.path')
classPath.split(File.pathSeparator).eachWithIndex { path, index ->
    prows.append(createRow("Entry ${index + 1}", path))
}

prows.append('</table>\n</section>\n\n')

// JVM Arguments Section
if (runtimeMXBean.inputArguments) {
    prows.append('''
<section>
<h2><a name="jvm_arguments">JVM Arguments</a></h2>
<table>
''')

    runtimeMXBean.inputArguments.eachWithIndex { arg, index ->
        prows.append(createRow("Argument ${index + 1}", arg))
    }

    prows.append('</table>\n</section>\n\n')
}

// Build final output
def output = """
<header>
    <div style="background:#18b5aa;padding:10px 20px;margin-bottom:20px;border:1px solid #000;">
        <div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="brand-groovy"></span> Groovy</div>
        <div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Version ${GroovySystem.version}</div>
    </div>
</header>
${prows.toString()}
"""

// Print output
println(output)

/**
 * Helper function to create a table row
 * @param key String table cell key
 * @param value String table cell value
 * @return String HTML table row
 */
def createRow(String key, String value) {
    return """    <tr><td class="align-left w_small w_nowrap" style="width:300px;background:#18b5aa4D;">${key}</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">${value}</td></tr>\n"""
}
