"""
common.jl - Shared utility helpers for WaSQL Julia

Julia ports of the small php/common.php helpers, mirroring R/common.R and
tcl/common.tcl. Loaded automatically by db.jl (which includes this file), so the
functions are reachable as db.commonStrlen(...), db.commonFormatPhone(...), etc.

No external packages are required.
"""

# Accented / extended Latin -> plain ASCII folding table (shared with common.R / common.tcl).
# Multi-codepoint source glyphs from the R/Tcl tables (e.g. a bare combining dot) are omitted.
const _EXTENDED_CHAR_MAP = Dict{String,String}(
    "Å"=>"A", "Æ"=>"A", "À"=>"A", "Á"=>"A", "Â"=>"A", "Ã"=>"A", "Ä"=>"A", "Ă"=>"A", "Ā"=>"A", "Ą"=>"A",
    "Ç"=>"C", "Ć"=>"C", "Ĉ"=>"C", "Ċ"=>"C", "Č"=>"C",
    "È"=>"E", "É"=>"E", "Ê"=>"E", "Ë"=>"E", "Ð"=>"E", "Ē"=>"E", "Ĕ"=>"E", "Ė"=>"E", "Ę"=>"E", "Ě"=>"E",
    "Ƒ"=>"F",
    "Ğ"=>"G", "Ġ"=>"G", "Ģ"=>"G",
    "Ĥ"=>"H", "Ħ"=>"H",
    "Ì"=>"I", "Í"=>"I", "Î"=>"I", "Ï"=>"I", "Ĩ"=>"I", "Ī"=>"I", "Ĭ"=>"I", "Į"=>"I", "İ"=>"I", "Ĳ"=>"I",
    "Ĵ"=>"J",
    "Ķ"=>"K", "ĸ"=>"K",
    "Ĺ"=>"L", "Ļ"=>"L", "Ľ"=>"L", "Ŀ"=>"L", "Ł"=>"L",
    "Ñ"=>"N", "Ń"=>"N", "Ņ"=>"N", "Ň"=>"N", "Ŋ"=>"N",
    "Ò"=>"O", "Ó"=>"O", "Ô"=>"O", "Õ"=>"O", "Ö"=>"O", "Ø"=>"O", "Ŏ"=>"O", "Ő"=>"O", "Œ"=>"O",
    "Þ"=>"P",
    "Ŕ"=>"R", "Ŗ"=>"R", "Ř"=>"R",
    "Š"=>"S", "Ș"=>"S", "Ś"=>"S", "Ŝ"=>"S", "Ş"=>"S",
    "Ț"=>"T", "Ţ"=>"T", "Ť"=>"T", "Ŧ"=>"T",
    "Ù"=>"U", "Ú"=>"U", "Û"=>"U", "Ü"=>"U", "Ũ"=>"U", "Ū"=>"U", "Ŭ"=>"U", "Ů"=>"U", "Ű"=>"U", "Ų"=>"U",
    "Ŵ"=>"W",
    "Ý"=>"Y", "Ÿ"=>"Y", "Ŷ"=>"Y",
    "Ž"=>"Z", "Ź"=>"Z", "Ż"=>"Z",
    "å"=>"a", "æ"=>"a", "à"=>"a", "á"=>"a", "â"=>"a", "ã"=>"a", "ä"=>"a", "ă"=>"a", "ā"=>"a", "ą"=>"a",
    "ç"=>"c", "ć"=>"c", "ĉ"=>"c", "ċ"=>"c", "č"=>"c",
    "è"=>"e", "é"=>"e", "ê"=>"e", "ë"=>"e", "ð"=>"e", "ē"=>"e", "ĕ"=>"e", "ė"=>"e", "ę"=>"e", "ě"=>"e",
    "ƒ"=>"f",
    "ğ"=>"g", "ġ"=>"g", "ģ"=>"g",
    "ĥ"=>"h", "ħ"=>"h",
    "ì"=>"i", "í"=>"i", "î"=>"i", "ï"=>"i", "ĩ"=>"i", "ī"=>"i", "ĭ"=>"i", "į"=>"i", "ĳ"=>"i",
    "ĵ"=>"j",
    "ķ"=>"k",
    "ĺ"=>"l", "ļ"=>"l", "ľ"=>"l", "ŀ"=>"l", "ł"=>"l",
    "ñ"=>"n", "ń"=>"n", "ņ"=>"n", "ň"=>"n", "ŉ"=>"n", "ŋ"=>"n",
    "ò"=>"o", "ó"=>"o", "ô"=>"o", "õ"=>"o", "ö"=>"o", "ø"=>"o", "ŏ"=>"o", "ő"=>"o", "œ"=>"o",
    "þ"=>"p",
    "ŕ"=>"r", "ŗ"=>"r", "ř"=>"r",
    "š"=>"s", "ș"=>"s", "ß"=>"ss", "ś"=>"s", "ŝ"=>"s", "ş"=>"s", "ſ"=>"s",
    "ț"=>"t", "ţ"=>"t", "ť"=>"t", "ŧ"=>"t",
    "ù"=>"u", "ú"=>"u", "û"=>"u", "ü"=>"u", "ũ"=>"u", "ū"=>"u", "ŭ"=>"u", "ů"=>"u", "ű"=>"u", "ų"=>"u",
    "ŵ"=>"w",
    "ý"=>"y", "ÿ"=>"y", "ŷ"=>"y",
    "ž"=>"z", "ź"=>"z", "ż"=>"z",
)

#---------- begin function convertExtendedCharacters
# @describe Normalizes accented / extended Latin characters to their plain ASCII equivalents (accents stripped, ligatures expanded)
# @param s AbstractString the text to normalize
# @return String the text with extended Latin characters folded to ASCII
# @usage
#   clean = convertExtendedCharacters(name)
function convertExtendedCharacters(s::AbstractString)
    return replace(s, _EXTENDED_CHAR_MAP...)
end
convertExtendedCharacters(s) = convertExtendedCharacters(string(s))

#---------- begin function commonStrlen
# @describe Wrapper for string length that also handles nothing / numbers / collections (collections are JSON-encoded first, matching php/common.php)
# @param s Any a string, number, nothing, or a collection (Dict/Vector/Tuple/NamedTuple)
# @return Int the character length (0 for nothing or an empty value)
# @usage
#   if commonStrlen(x) > 0 ... end
function commonStrlen(s)
    s === nothing && return 0
    s isa AbstractString && return length(s)
    s isa Number && return length(string(s))
    if s isa AbstractDict || s isa AbstractVector || s isa Tuple || s isa NamedTuple
        return length(_commonJson(s))
    end
    return length(string(s))
end

# Internal: JSON-encode a value when JSON3 is available, else fall back to string()
function _commonJson(x)
    if isdefined(Main, :JSON3)
        try
            return Base.invokelatest(getfield(Main, :JSON3).write, x)
        catch
        end
    end
    return string(x)
end

#---------- begin function commonFormatPhone
# @describe Strips non-digits from a phone number and formats it by length (7 -> nnn-nnnn, 10 -> (nnn) nnn-nnnn, 11 -> n(nnn) nnn-nnnn)
# @param phone AbstractString a raw phone number in any format
# @return String the formatted number; "" when fewer than 4 characters; the digit string unchanged for other lengths
# @usage
#   pretty = commonFormatPhone("8014584741")
function commonFormatPhone(phone::AbstractString)
    length(phone) < 4 && return ""
    digits = replace(phone, r"[^0-9]" => "")
    n = length(digits)
    if n == 7
        return string(digits[1:3], "-", digits[4:7])
    elseif n == 10
        return string("(", digits[1:3], ") ", digits[4:6], "-", digits[7:10])
    elseif n == 11
        return string(digits[1:1], "(", digits[2:4], ") ", digits[5:7], "-", digits[8:11])
    end
    return digits
end
commonFormatPhone(phone) = commonFormatPhone(string(phone))

#---------- begin function parseHtmlTagAttributes
# @describe Parses the attributes out of an HTML tag string, lowercasing names and stripping surrounding quotes from values
# @param text AbstractString the HTML tag or attribute fragment
# @return Dict{String,String} attribute name => value ("" for valueless attributes; like common.R/common.tcl a leading tag name is also captured)
# @usage
#   attrs = parseHtmlTagAttributes("<a href=\"/x\" target=\"_blank\">")
function parseHtmlTagAttributes(text::AbstractString)
    attributes = Dict{String,String}()
    pattern = r"([a-zA-Z][a-zA-Z0-9\-:_]*)(?:=(\"[^\"]*\"|'[^']*'|[^\s>]+))?"
    for m in eachmatch(pattern, text)
        name = lowercase(m.captures[1])
        raw = m.captures[2]
        attributes[name] = raw === nothing ? "" : strip(raw, ('"', '\''))
    end
    return attributes
end
