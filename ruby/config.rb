# config.rb
# Reads the WaSQL config.xml and returns the connection settings for a named
# <database> node. Port of lua/config.lua and R/config.R. No external gems -
# config.xml is scanned with a regex the same way lua/config.lua does it.
#
# The evalRubyCode harness (php/common.php) defines wasqlConfigFile before this
# file is loaded; the guard below only matters for standalone use (unit tests).

unless respond_to?(:wasqlConfigFile, true)
	def wasqlConfigFile
		File.join(File.dirname(File.expand_path(__FILE__)), "..", "config.xml")
	end
end

#---------- begin function xml_unescape
# @describe expands the five predefined XML entities (and numeric refs) in an attribute value
# @param s string - the raw attribute text
# @return string - text with &amp; &lt; &gt; &quot; &apos; resolved
# @usage v = xml_unescape(raw_value)
def xml_unescape(s)
	s.to_s
		.gsub("&lt;", "<")
		.gsub("&gt;", ">")
		.gsub("&quot;", '"')
		.gsub("&apos;", "'")
		.gsub(/&#(\d+);/) { [Regexp.last_match(1).to_i].pack("U") }
		.gsub("&amp;", "&")
end

#---------- begin function config_parse
# @describe reads config.xml and returns the attributes of the <database name="..."> node matching db_name
# @param db_name string - the value of the database node's name attribute in config.xml
# @return Hash - every attribute of that node as name => value (dbtype, dbhost, dbuser, dbpass, dbname, dbport, dbschema, connect, ...); raises if the file or node is missing
# @usage cfg = config_parse("mydb")
def config_parse(db_name)
	config_file = wasqlConfigFile
	raise "Config file does not exist at: #{config_file}" unless File.exist?(config_file)
	xml = File.read(config_file)
	xml.scan(%r{<database\s+(.*?)/?>}m) do |blob|
		attrs = {}
		blob[0].scan(/([\w:-]+)\s*=\s*(['"])(.*?)\2/m) do |name, _q, value|
			attrs[name] = xml_unescape(value)
		end
		return attrs if attrs["name"] == db_name
	end
	raise "Database configuration not found for: #{db_name}"
end
