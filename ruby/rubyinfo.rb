# rubyinfo.rb
# Emits the Ruby interpreter description and an HTML report of the installed
# gems. Parallel to R/rinfo.R and lua/luainfo.lua. Run standalone:
#   ruby rubyinfo.rb

#---------- begin function rubyinfo_html_escape
# @describe escapes HTML special characters (& < > ") so a value is safe to embed in markup
# @param text mixed - raw text (nil becomes "")
# @return string - the escaped text
# @usage safe = rubyinfo_html_escape(value)
def rubyinfo_html_escape(text)
	return "" if text.nil?
	text.to_s.gsub("&", "&amp;").gsub("<", "&lt;").gsub(">", "&gt;").gsub('"', "&quot;")
end

#---------- begin function rubyinfo_render
# @describe renders the full HTML report - a header with RUBY_DESCRIPTION, then one table row per installed gem
# @param (none)
# @return string - the complete HTML fragment
# @usage print rubyinfo_render
def rubyinfo_render
	out = []
	out << "<header>"
	out << '  <div style="background:linear-gradient(to right,#c2c4c7,#919198);padding:10px 20px;margin-bottom:20px;border:1px solid #919198;border-radius:4px;">'
	out << '    <div style="font-size:clamp(24px,3vw,48px);color:#00007d;">Ruby</div>'
	out << '    <div style="font-size:clamp(11px,2vw,18px);color:#00007d;">' + rubyinfo_html_escape(RUBY_DESCRIPTION) + "</div>"
	out << "  </div>"
	out << "</header>"

	gems = {}
	begin
		require "rubygems"
		Gem::Specification.each { |spec| gems[spec.name] ||= spec.version.to_s }
	rescue StandardError
		# rubygems not available - report just the interpreter
	end

	out << "<section>"
	out << %(  <h2><a name="module_gems">Installed gems (#{gems.size})</a></h2>)
	out << "  <table>"
	gems.keys.sort_by(&:downcase).each do |name|
		out << '<tr><td class="align-left w_small w_nowrap" style="width:300px;background:#c2c4c780;">' +
		       rubyinfo_html_escape(name) +
		       '</td><td class="align-left w_small" style="min-width:300px;background-color:#d5d7d9">' +
		       rubyinfo_html_escape(gems[name]) + "</td></tr>"
	end
	out << "  </table>"
	out << "</section>"
	out.join("\n")
end

if __FILE__ == $PROGRAM_NAME
	print rubyinfo_render
	print "\n"
end
