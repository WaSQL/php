/**
Installation
	Download CsvJdbc (csvjdbc-x.x.x.jar) from Maven Central:
		https://repo1.maven.org/maven2/net/sourceforge/csvjdbc/csvjdbc/
	and place it in groovy/lib/.

How it works
	dbname is a DIRECTORY. Every file in it with the configured extension is a table named after the file
	(sales.csv -> SELECT * FROM sales). The first line of each file holds the column names.
	CsvJdbc is READ-ONLY: SELECT works, INSERT/UPDATE/DELETE/DDL are not supported (executeSQL/executePS throw).

database tag attributes
	dbname:         directory holding the CSV files (required)
	dbseparator:    field separator (default: ,)
	dbextension:    file extension of the table files (default: .csv)
	dbcharset:      file character set (default: UTF-8)
	dbnoheader:     set to 1 when the files have no header line (columns are then named COLUMN1, COLUMN2, ...)
	querytimeout:   accepted for compatibility (CsvJdbc reads local files and has no statement timeout)

References
	http://csvjdbc.sourceforge.net/doc.html
	https://groovy-lang.org/databases.html
*/

import groovy.sql.Sql
import groovy.json.JsonGenerator
import java.sql.SQLException

//---------- begin function connect
/**
* @describe creates and returns a CsvJdbc connection on a directory of CSV files. Throws IllegalArgumentException when dbname is missing or is not a directory.
* @param params params map
*	dbname, dbseparator, dbextension, dbcharset, dbnoheader
* @return object
*	groovy.sql.Sql connection object
* @usage
*	sql = mscsvdb.connect(params)
*/
def connect(Map params) {
	if (!params.dbname) {
		// throw, never System.exit: inside the Groovy server an exit would kill the daemon and every in-flight query
		throw new IllegalArgumentException("Missing dbname attribute (the CSV directory) in database tag named '${params.name}'")
	}
	def dir = new File(params.dbname.toString())
	if (!dir.isDirectory()) {
		throw new IllegalArgumentException("CSV directory does not exist: ${dir.path}")
	}

	def props = new java.util.Properties()
	props.setProperty('separator', (params.dbseparator ?: ',').toString())
	props.setProperty('fileExtension', (params.dbextension ?: '.csv').toString())
	props.setProperty('charset', (params.dbcharset ?: 'UTF-8').toString())
	if (params.dbnoheader?.toString() in ['1', 'true']) props.setProperty('suppressHeaders', 'true')

	def url = 'jdbc:relique:csv:' + dir.absolutePath.replace('\\', '/')
	def sql = binding.hasVariable('wasqlOpenSql') ? binding.getVariable('wasqlOpenSql').call(url, props, 'org.relique.jdbc.csv.CsvDriver', params) : Sql.newInstance(url, props, 'org.relique.jdbc.csv.CsvDriver')

	// Groovy server only: register the connection so Groovy Manager can cancel this request (no-op from the CLI)
	if (binding.hasVariable('wasqlTrackConnection')) { binding.getVariable('wasqlTrackConnection').call(sql.connection) }
	return sql
}

//---------- begin function executeSQL
/**
* @describe not supported - CSV files are read-only through CsvJdbc
* @param params query string, params map
* @return boolean
*	never returns - always throws UnsupportedOperationException (HTTP 400 from the server)
*/
def executeSQL(String query, Map params = [:]) {
	throw new UnsupportedOperationException("mscsv databases are read-only (CsvJdbc supports SELECT only)")
}

//---------- begin function executePS
/**
* @describe not supported - CSV files are read-only through CsvJdbc
* @param params query string, args list, params map
* @return boolean
*	never returns - always throws UnsupportedOperationException (HTTP 400 from the server)
*/
def executePS(String query, List args, Map params = [:]) {
	throw new UnsupportedOperationException("mscsv databases are read-only (CsvJdbc supports SELECT only)")
}

//---------- begin function queryResults
/**
* @describe executes a SELECT against the CSV files and returns the records as maps with lower-case keys. Values are strings
*	unless the query casts them (CsvJdbc has no column types of its own here).
* @param params query string, params map
*	filename: if provided, writes results to a UTF-8 CSV file (with BOM) instead of returning rows
*	format: 'json' (default) or 'list' for a native Groovy list
*	rowsink: Groovy server streaming - each row is appended with rowsink << rec instead of being collected
*	batchsize: number of rows to buffer before writing to file (default: 100)
*	notrim: if true, skips trimming whitespace from values (default: false)
* @return mixed
*	JSON string (default), List of Maps if format='list', filename string if filename provided
* @usage
*	json = mscsvdb.queryResults('SELECT * FROM sales', params)
*	recs = mscsvdb.queryResults('SELECT * FROM sales', params + [format: 'list'])
*/
def queryResults(String query, Map params = [:]) {
	def sql = null
	def batchSize = (params.getOrDefault('batchsize', 100) as int)
	def noTrim = params.getOrDefault('notrim', false)

	try {
		sql = connect(params)
		def cancelled = binding.hasVariable('wasqlCancelled') ? binding.getVariable('wasqlCancelled') : null

		if (params.containsKey('filename')) {
			def writer = null
			try {
				writer = new File(params.filename.toString()).newWriter('UTF-8')
				writer.write('﻿')   // UTF-8 BOM so Excel recognises the encoding

				def stmt = sql.connection.createStatement()
				try {
					def rs = stmt.executeQuery(query)
					try {
						def rsmd = rs.getMetaData()
						int columnCount = rsmd.getColumnCount()
						def fieldNames = (1..columnCount).collect { rsmd.getColumnLabel(it).toLowerCase() }
						writer.writeLine(fieldNames.collect { escapeCSV(it) }.join(','))

						def batch = new StringBuilder(batchSize * 200)
						int batchCount = 0
						while (true) {
							if (cancelled != null && cancelled.call()) throw new SQLException('request cancelled')
							if (!rs.next()) break
							def line = new StringBuilder(columnCount * 30)
							for (int i = 1; i <= columnCount; i++) {
								if (i > 1) line.append(',')
								def value = rs.getObject(i)
								if (value != null) {
									def str = value.toString()
									line.append(escapeCSV(noTrim ? str : str.trim()))
								}
							}
							batch.append(line).append('\n')
							if (++batchCount >= batchSize) {
								writer.write(batch.toString())
								batch.setLength(0)
								batchCount = 0
							}
						}
						if (batchCount > 0) writer.write(batch.toString())
					} finally {
						rs.close()
					}
				} finally {
					stmt.close()
				}
				return params.filename
			} finally {
				if (writer != null) writer.close()
			}
		}

		// list / json mode. params.rowsink (Groovy server) receives each row as it is read, so nothing accumulates.
		def recs = params.rowsink != null ? params.rowsink : []
		def keys = null    // lower-cased column names, worked out once from the result's metadata
		sql.eachRow(query, { meta ->
			int n = meta.columnCount
			keys = new String[n]
			for (int i = 0; i < n; i++) keys[i] = meta.getColumnLabel(i + 1).toLowerCase()
		}, { row ->
			if (cancelled != null && cancelled.call()) throw new SQLException('request cancelled')
			def rec = new LinkedHashMap()
			for (int i = 0; i < keys.length; i++) rec[keys[i]] = row[i]
			recs << rec
		})

		if (params.getOrDefault('format', 'json') == 'json') {
			return new JsonGenerator.Options().disableUnicodeEscaping().build().toJson(recs)
		}
		return recs

	} finally {
		if (sql != null) sql.close()
	}
}

//---------- begin function escapeCSV
/**
* @describe helper function to escape CSV values
* @param params value string
* @return string
*	escaped value
*/
private def escapeCSV(String value) {
	if (value == null) return ''
	if (value.contains(',') || value.contains('"') || value.contains('\n') || value.contains('\r')) {
		return '"' + value.replace('"', '""') + '"'
	}
	return value
}

// Export functions for use as a module
return this
