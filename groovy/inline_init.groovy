/**
 * inline_init.groovy - Inline initialization for embedded Groovy in WaSQL
 *
 * Copy and paste this code at the start of your embedded Groovy scripts
 * to get access to db, config, and common modules.
 */

// Find groovy directory
def findGroovyDir() {
    // Try from current working directory
    def dirs = [
        new File('groovy'),
        new File('../groovy'),
        new File('../../groovy'),
        new File('D:/wasql/groovy'),
        new File('C:/wasql/groovy')
    ]

    for (dir in dirs) {
        if (dir.exists() && new File(dir, 'db.groovy').exists()) {
            return dir.absolutePath
        }
    }

    return null
}

def gdir = findGroovyDir()
if (gdir) {
    // Load modules
    def shell = new GroovyShell()
    config = shell.parse(new File(gdir, 'config.groovy'))
    common = shell.parse(new File(gdir, 'common.groovy'))
    db = shell.parse(new File(gdir, 'db.groovy'))
}
