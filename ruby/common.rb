# common.rb
# WaSQL shared helper functions for embedded Ruby scripts.
# Port of R/common.R, lua/common.lua and Tcl/common.tcl. Loaded by the
# evalRubyCode harness (php/common.php) alongside wasql_<md5>.rb, config.rb and
# db.rb. Every helper is a top-level method, matching the flat namespace the
# R / Tcl / Lua ports expose.

require "json"

# extended-Latin -> ASCII fold table, consumed by convert_extended_characters
NORMALIZE_PAIRS = <<~PAIRS
  Å A Æ A À A Á A Â A Ã A Ä A Ă A Ā A Ą A
  Ç C Ć C Ĉ C Ċ C Č C
  È E É E Ê E Ë E Ð E Ē E Ĕ E Ė E Ę E Ě E
  Ğ G Ġ G Ģ G
  Ĥ H Ħ H
  Ì I Í I Î I Ï I Ĩ I Ī I Ĭ I Į I İ I
  Ĵ J
  Ķ K
  Ĺ L Ļ L Ľ L Ŀ L Ł L
  Ñ N Ń N Ņ N Ň N Ŋ N
  Ò O Ó O Ô O Õ O Ö O Ø O Ŏ O Ő O Œ O
  Þ P
  Ŕ R Ŗ R Ř R
  Š S Ș S Ś S Ŝ S Ş S
  Ț T Ţ T Ť T Ŧ T
  Ù U Ú U Û U Ü U Ũ U Ū U Ŭ U Ů U Ű U Ų U
  Ŵ W
  Ý Y Ÿ Y Ŷ Y
  Ž Z Ź Z Ż Z
  å a æ a à a á a â a ã a ä a ă a ā a ą a
  ç c ć c ĉ c ċ c č c
  è e é e ê e ë e ð e ē e ĕ e ė e ę e ě e
  ğ g ġ g ģ g
  ĥ h ħ h
  ì i í i î i ï i ĩ i ī i ĭ i į i
  ĵ j
  ķ k
  ĺ l ļ l ľ l ŀ l ł l
  ñ n ń n ņ n ň n ŋ n
  ò o ó o ô o õ o ö o ø o ŏ o ő o œ o
  þ p
  ŕ r ŗ r ř r
  š s ș s ß ss ś s ŝ s ş s
  ț t ţ t ť t ŧ t
  ù u ú u û u ü u ũ u ū u ŭ u ů u ű u ų u
  ŵ w
  ý y ÿ y ŷ y
  ž z ź z ż z
PAIRS

NORMALIZE_MAP = {}
NORMALIZE_PAIRS.split(/\s+/).each_slice(2) do |from, to|
	NORMALIZE_MAP[from] = to if from && to
end
NORMALIZE_MAP.freeze

#---------- begin function convert_extended_characters
# @describe normalizes accented / extended Latin characters to their plain ASCII equivalents (accents stripped, ligatures expanded, e.g. "e"->"e", "ss"->"ss")
# @param str string - the text to normalize (assumed UTF-8)
# @return string - the text with extended Latin characters folded to ASCII
# @usage clean = convert_extended_characters(name)
def convert_extended_characters(str)
	return "" if str.nil?
	out = str.to_s
	NORMALIZE_MAP.each { |from, to| out = out.gsub(from, to) }
	out
end

#---------- begin function html_escape
# @describe escapes HTML special characters (& < > " ') in a string so it is safe to embed in markup
# @param text mixed - raw text (nil becomes "")
# @return string - the escaped text
# @usage html << "<td>" + html_escape(value) + "</td>"
def html_escape(text)
	return "" if text.nil?
	text.to_s
		.gsub("&", "&amp;")
		.gsub("<", "&lt;")
		.gsub(">", "&gt;")
		.gsub('"', "&quot;")
		.gsub("'", "&#39;")
end

#---------- begin function common_strlen
# @describe string-length wrapper that also copes with nil, numbers and Hash/Array (JSON-encoded first)
# @param s mixed - a string, number, Hash, Array or nil
# @return integer - the character length (0 for nil)
# @usage do_thing if common_strlen(x) > 0
def common_strlen(s)
	return 0 if s.nil?
	case s
	when String then s.length
	when Numeric, TrueClass, FalseClass then s.to_s.length
	when Hash, Array then JSON.generate(s).length
	else s.to_s.length
	end
end

#---------- begin function common_format_phone
# @describe strips non-digits from a phone number and formats it by length (7 -> nnn-nnnn, 10 -> (nnn) nnn-nnnn, 11 -> n(nnn) nnn-nnnn)
# @param phone string - a raw phone number in any format
# @return string - the formatted number, "" if shorter than 4 digits, or the digit string unchanged for other lengths
# @usage pretty = common_format_phone("8014584741")
def common_format_phone(phone)
	return "" if phone.nil?
	digits = phone.to_s.gsub(/[^0-9]/, "")
	return "" if digits.length < 4
	case digits.length
	when 7  then digits.sub(/(\d{3})(\d{4})/, '\1-\2')
	when 10 then digits.sub(/(\d{3})(\d{3})(\d{4})/, '(\1) \2-\3')
	when 11 then digits.sub(/(\d)(\d{3})(\d{3})(\d{4})/, '\1(\2) \3-\4')
	else digits
	end
end

#---------- begin function parse_html_tag_attributes
# @describe extracts name/value attribute pairs from an HTML tag string, lowercasing names and stripping surrounding quotes
# @param text string - an HTML tag or attribute fragment
# @return Hash - attribute name => value ("" for valueless attributes)
# @usage attrs = parse_html_tag_attributes('<a href="/x" target="_blank">')
def parse_html_tag_attributes(text)
	attrs = {}
	return attrs if text.nil?
	body = text.to_s.sub(/\A\s*<\s*[A-Za-z][\w:-]*/, "").sub(%r{/?\s*>\s*\z}, "")
	body.scan(/([A-Za-z][\w:-]*)\s*(?:=\s*("[^"]*"|'[^']*'|[^\s>]+))?/) do |name, value|
		next if name.nil?
		attrs[name.downcase] = value.nil? ? "" : value.gsub(/\A["']|["']\z/, "")
	end
	attrs
end

#---------- begin function results_as_csv
# @describe converts a query-results Hash (as returned by db_query_results) into a CSV string, quoting fields that contain a comma, quote or newline
# @param results Hash - { "columns" => [..], "rows" => [ {col=>val}, .. ], "count" => n }
# @return string - CSV text with a header row, or "No results found" when there are no rows
# @usage puts results_as_csv(db_query_results("mydb", "SELECT * FROM users"))
def results_as_csv(results)
	cols = results.is_a?(Hash) ? results["columns"] : nil
	rows = results.is_a?(Hash) ? (results["rows"] || []) : []
	return "No results found" if cols.nil? || rows.empty?
	esc = lambda do |v|
		s = v.nil? ? "" : v.to_s
		s =~ /[",\r\n]/ ? %Q("#{s.gsub('"', '""')}") : s
	end
	out = [cols.map { |c| esc.call(c) }.join(",")]
	rows.each { |row| out << cols.map { |c| esc.call(row[c]) }.join(",") }
	out.join("\n") + "\n"
end

#---------- begin function results_as_table
# @describe converts a query-results Hash into an HTML <table> (class "table bordered striped"), HTML-escaping every cell
# @param results Hash - { "columns" => [..], "rows" => [ {col=>val}, .. ], "count" => n }
# @return string - HTML markup, or "<p>No results found</p>" when there are no rows
# @usage puts results_as_table(db_query_results("mydb", "SELECT * FROM users"))
def results_as_table(results)
	cols = results.is_a?(Hash) ? results["columns"] : nil
	rows = results.is_a?(Hash) ? (results["rows"] || []) : []
	return "<p>No results found</p>" if cols.nil? || rows.empty?
	html = ['<table class="table bordered striped">', "<thead><tr>"]
	cols.each { |c| html << "<th>#{html_escape(c)}</th>" }
	html << "</tr></thead>" << "<tbody>"
	rows.each do |row|
		html << "<tr>"
		cols.each { |c| html << "<td>#{html_escape(row[c])}</td>" }
		html << "</tr>"
	end
	html << "</tbody>" << "</table>"
	html.join("\n")
end

#---------- begin function ruby_version
# @describe returns the interpreter description string (RUBY_DESCRIPTION)
# @param (none)
# @return string - e.g. "ruby 3.4.7 (2025-10-08 revision ...) [x64-mingw-ucrt]"
# @usage v = ruby_version
def ruby_version
	RUBY_DESCRIPTION
end
