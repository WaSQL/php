#! python
'''
    DaSQL: DOS Access to SQL -  command line way to query any database setup in WaSQL
    NOTE: you may need to install the following:
        python3 -m pip install requests
        python3 -m pip install markdown
'''
import sys
import os
import requests
import urllib3
import configparser
from chardet import detect  # For encoding detection
import subprocess
import tempfile
import json
import re
import csv
import markdown
from requests.packages import urllib3
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

#---------- function preview_html
# @describe Opens an HTML file in a web browser. 
# @param html_file: Path to the HTML file to open
# @type html_file: str
# @param browser_path: Custom path to browser executable (optional) 
# @type browser_path: str or None
# @return: None
def previewHTML(html_file, browser_path=None):

    # Detect or use browser
    if browser_path:
        browser_exe = browser_path
    else:
        # Try known Windows paths
        browser_exe = None
        possible_paths = [
            r"C:\Program Files\Google\Chrome\Application\chrome.exe",
            r"C:\Program Files (x86)\Google\Chrome\Application\chrome.exe",
            r"C:\Program Files\Mozilla Firefox\firefox.exe",
            r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe",
        ]
        for path in possible_paths:
            if os.path.exists(path):
                browser_exe = path
                break

    if not browser_exe:
        print("⚠️ Could not find a known browser. Please pass `browser_path='path/to/browser.exe'`.")
        sys.exit(1)

    subprocess.Popen([browser_exe, html_file], shell=False)

#---------- function previewMarkdown
# @description Renders a Markdown file to HTML and opens it in a browser.  
# @param markdown_file: Path to the Markdown file to preview
# @type markdown_file: str
# @param browser_path: Custom path to browser executable (optional)
# @type browser_path: str or None  
# @return: None
def previewMarkdown(markdown_file, browser_path=None):
    """
    Renders Markdown and opens it in a specific browser via subprocess.
    If browser_path is None, it tries known defaults.
    """
    with open(markdown_file, 'r', encoding='utf-8') as f:
        md_content = f.read()

    # Available markdown extensions:
    # 'abbr' - Abbreviation support (e.g., *[HTML]: HyperText Markup Language)
    # 'admonition' - Warning/note/tip boxes (!!! note, !!! warning, etc.)
    # 'attr_list' - Add CSS classes and IDs to elements {: .class #id}
    # 'codehilite' - Syntax highlighting for code blocks
    # 'def_list' - Definition lists support
    # 'fenced_code' - GitHub-style ``` code blocks
    # 'footnotes' - Footnote support with [^1] syntax
    # 'md_in_html' - Process markdown inside HTML blocks
    # 'meta' - Document metadata support
    # 'nl2br' - Convert single newlines to <br> tags
    # 'sane_lists' - Better list handling and nesting
    # 'smarty' - Smart quotes, dashes, and ellipses
    # 'tables' - Table support with | syntax
    # 'toc' - Table of contents generation
    # 'wikilinks' - Wiki-style [[links]]
    html = markdown.markdown(md_content, extensions=['fenced_code', 'codehilite', 'tables', 'toc', 'footnotes', 'admonition','sane_lists','footnotes','attr_list'])

    html_content = f"""
    <html>
    <head>
        <meta charset="utf-8">
        <title>Markdown Preview</title>
        <style>
            body {{ font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; line-height: 1.6; padding: 2em; max-width: 860px; margin: auto; }}
            pre {{ background: #f4f4f4; padding: 0.6em 0.8em 0.75em; overflow: auto; line-height: 1.5; }}
            code {{ font-family: 'Consolas', 'Cascadia Code', 'Menlo', 'Monaco', monospace; font-size: 0.9em; background: #f4f4f4; padding: 0.2em 0.4em; }}
            pre code {{ background: none; padding: 0; font-size: 0.875em; }}

            /* Tables */
            table {{ border-collapse: collapse; width: 100%; margin: 1em 0; }}
            th, td {{ border: 1px solid #ddd; padding: 8px 12px; text-align: left; }}
            th {{ background-color: #f2f2f2; font-weight: bold; }}
            tr:nth-child(even) {{ background-color: #f9f9f9; }}
            
            /* Table of Contents */
            .toc {{ background: #f8f9fa; border: 1px solid #e9ecef; padding: 1em; margin: 1em 0; border-radius: 4px; }}
            .toc ul {{ margin: 0.5em 0; }}
            
            /* Admonitions (note, warning, etc.) */
            .admonition {{ margin: 1em 0; padding: 1em; border-left: 4px solid #007bff; background: #f8f9fa; }}
            .admonition-title {{ font-weight: bold; margin-bottom: 0.5em; }}
            .admonition.warning {{ border-left-color: #ffc107; background: #fff3cd; }}
            .admonition.danger {{ border-left-color: #dc3545; background: #f8d7da; }}
            
            /* Footnotes */
            .footnote {{ font-size: 0.8em; }}
            .footnote-ref {{ vertical-align: super; font-size: 0.7em; }}
            
            /* Definition Lists */
            dt {{ font-weight: bold; margin-top: 1em; }}
            dd {{ margin-left: 2em; margin-bottom: 0.5em; }}
            
            /* Abbreviations */
            abbr {{ cursor: help; border-bottom: 1px dotted; }}
        </style>
    </head>
    <body>{html}</body>
    </html>
    """

    temp_dir = tempfile.gettempdir()
    html_file = os.path.join(temp_dir, 'dasql_md_preview.html')

    with open(html_file, 'w', encoding='utf-8') as tmp:
        tmp.write(html_content)

    # Detect or use browser
    if browser_path:
        browser_exe = browser_path
    else:
        # Try known Windows paths
        browser_exe = None
        possible_paths = [
            "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe",
            "C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe",
            "C:\\Program Files\\Mozilla Firefox\\firefox.exe",
            "C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe",
        ]
        for path in possible_paths:
            if os.path.exists(path):
                browser_exe = path
                break

    if not browser_exe:
        print("⚠️ Unable to view markdown. Could not find a known browser.")
        sys.exit(1)

    subprocess.Popen([browser_exe, html_file], shell=False)

#---------- function previewCSV
# @description Renders a CSV/TSV file as a Bulma-styled HTML table and opens it in a browser.
#              The header row stays fixed while the body scrolls, and a search box filters
#              rows as you type (every whitespace-separated term must match somewhere in the row).
#              Clicking a column header sorts by it (click again to reverse); numeric columns
#              sort by value. The delimiter is sniffed, so comma, tab, semicolon and pipe files
#              all work. Files over max_mb or max_rows are refused, since the browser would choke.
# @param csv_file: Path to the CSV file to preview
# @type csv_file: str
# @param browser_path: Custom path to browser executable (optional)
# @type browser_path: str or None
# @param max_mb: Largest file size, in MB, that will be rendered
# @type max_mb: int
# @param max_rows: Most data rows that will be rendered
# @type max_rows: int
# @return: None
def previewCSV(csv_file, browser_path=None, max_mb=25, max_rows=50000):
    import html as htmllib

    file_mb = os.path.getsize(csv_file) / 1048576.0
    if file_mb > max_mb:
        print("⚠️ {} is too large to preview: {:,.1f} MB (limit is {:,} MB).".format(os.path.basename(csv_file), file_mb, max_mb))
        sys.exit(1)

    with open(csv_file, 'rb') as f:
        raw_data = f.read()
    encoding = 'UTF-8'
    try:
        content = raw_data.decode('utf-8-sig')
    except UnicodeDecodeError:
        # Excel exports are often cp1252; let chardet guess, and never die on a stray byte
        encoding = detect(raw_data)['encoding'] or 'cp1252'
        content = raw_data.decode(encoding, errors='replace')

    try:
        dialect = csv.Sniffer().sniff(content[:65536], delimiters=',\t;|')
    except csv.Error:
        dialect = csv.excel
    rows = list(csv.reader(content.splitlines(), dialect))
    rows = [r for r in rows if any(c.strip() for c in r)]
    if len(rows) - 1 > max_rows:
        print("⚠️ {} is too large to preview: {:,} rows (limit is {:,} rows).".format(os.path.basename(csv_file), len(rows) - 1, max_rows))
        sys.exit(1)

    header = rows[0] if rows else []
    data = rows[1:]
    colcount = max([len(header)] + [len(r) for r in data]) if rows else 0
    header = header + [''] * (colcount - len(header))

    # Right-align columns whose non-empty values are all numeric
    numeric_re = re.compile(r'^[\s$€£-]*[\d,]*\.?\d+%?\s*$')
    numeric = []
    for i in range(colcount):
        values = [r[i] for r in data if i < len(r) and r[i].strip()]
        numeric.append(bool(values) and all(numeric_re.match(v) for v in values))

    # Stat tiles
    cells = len(data) * colcount
    blanks = sum(1 for r in data for i in range(colcount) if i >= len(r) or not r[i].strip())
    size = len(raw_data)
    for unit in ('B', 'KB', 'MB', 'GB'):
        if size < 1024 or unit == 'GB':
            size_label = ('{:,.0f} {}' if unit == 'B' else '{:,.1f} {}').format(size, unit)
            break
        size /= 1024.0
    delimiter_names = {',': 'Comma', '\t': 'Tab', ';': 'Semicolon', '|': 'Pipe'}
    stats = [
        ('Rows', '{:,}'.format(len(data))),
        ('Showing', '<span id="shown">{:,}</span>'.format(len(data))),
        ('Columns', '{:,}'.format(colcount)),
        ('Numeric cols', '{:,}'.format(sum(numeric))),
        ('Blank cells', '{:.1f}%'.format(100.0 * blanks / cells) if cells else '0%'),
        ('Size', size_label),
        ('Delimiter', delimiter_names.get(dialect.delimiter, repr(dialect.delimiter))),
        ('Encoding', encoding.upper()),
    ]

    esc = htmllib.escape
    tiles = ''.join(
        '<div class="stat"><p class="heading">{}</p><p class="value">{}</p></div>'.format(label, value)
        for label, value in stats
    )
    thead = ''.join(
        '<th data-col="{}"{}{}>{}<span class="sort"></span></th>'.format(
            i, ' data-numeric="1"' if numeric[i] else '', ' class="has-text-right"' if numeric[i] else '', esc(h))
        for i, h in enumerate(header)
    )
    tbody = []
    for r in data:
        r = r + [''] * (colcount - len(r))
        tbody.append('<tr>' + ''.join(
            '<td{}>{}</td>'.format(' class="has-text-right"' if numeric[i] else '', esc(c))
            for i, c in enumerate(r)
        ) + '</tr>')

    html_content = """<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>__TITLE__</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.4/css/bulma.min.css">
<style>
    html, body { height: 100%; overflow: hidden; }
    body { display: flex; flex-direction: column; }
    .toolbar { padding: 0.75rem 1rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; }
    .toolbar .control { flex: 1 1 20rem; }
    .toolbar .file-name { font-weight: 600; }
    .stats { display: flex; flex-wrap: wrap; gap: 0.5rem; padding: 0.75rem 1rem 0; }
    .stat { flex: 1 1 7rem; padding: 0.4rem 0.75rem; border: 1px solid var(--bulma-border-weak); border-radius: 4px; background: var(--bulma-scheme-main-bis); }
    .stat .heading { margin: 0; font-size: 0.65rem; letter-spacing: 0.05em; text-transform: uppercase; color: var(--bulma-text-weak); }
    .stat .value { font-size: 1.15rem; font-weight: 600; color: var(--bulma-text-strong); white-space: nowrap; }
    .table-wrap { flex: 1 1 auto; overflow: auto; margin: 0 1rem 1rem; border: 1px solid var(--bulma-border-weak); border-radius: 4px; }
    .table-wrap table { margin: 0; }
    .table-wrap thead th { position: sticky; top: 0; z-index: 1; background: var(--bulma-scheme-main-ter); box-shadow: inset 0 -2px 0 var(--bulma-border); white-space: nowrap; cursor: pointer; user-select: none; }
    .table-wrap thead th:hover { background: var(--bulma-scheme-main-bis); }
    .table-wrap thead th .sort { display: inline-block; width: 1em; margin-left: 0.25em; color: var(--bulma-link); }
    .table-wrap td { white-space: nowrap; }
    .table-wrap tbody tr.stripe { background: var(--bulma-scheme-main-bis); }
    .table-wrap tbody tr.is-hidden { display: none; }
</style>
</head>
<body>
<div class="stats">__TILES__</div>
<div class="toolbar">
    <span class="file-name">__TITLE__</span>
    <div class="control">
        <input id="search" class="input is-small" type="search" placeholder="Filter rows... (Esc clears)" autofocus>
    </div>
</div>
<div class="table-wrap">
    <table class="table is-narrow is-hoverable is-fullwidth">
        <thead><tr>__THEAD__</tr></thead>
        <tbody id="rows">__TBODY__</tbody>
    </table>
</div>
<script>
(function(){
    var input = document.getElementById('search');
    var count = document.getElementById('shown');
    var tbody = document.getElementById('rows');
    var items = Array.prototype.slice.call(tbody.rows).map(function(r, i){
        return { row: r, text: r.textContent.toLowerCase(), order: i };
    });
    var total = items.length;
    var timer = null;
    var sortCol = -1, sortDir = 1;
    var collator = new Intl.Collator(undefined, { numeric: true, sensitivity: 'base' });

    function apply(){
        var terms = input.value.toLowerCase().split(/\\s+/).filter(Boolean);
        var shown = 0;
        for (var i = 0; i < total; i++) {
            var it = items[i];
            var ok = terms.every(function(t){ return it.text.indexOf(t) !== -1; });
            it.row.classList.toggle('is-hidden', !ok);
            if (ok) { it.row.classList.toggle('stripe', shown % 2 === 1); shown++; }
        }
        count.textContent = shown.toLocaleString();
    }

    // Click a header: sort ascending, click again: descending. Blanks always sort last.
    function sortBy(th){
        var col = +th.getAttribute('data-col');
        var isNum = th.hasAttribute('data-numeric');
        sortDir = (col === sortCol) ? -sortDir : 1;
        sortCol = col;
        items.forEach(function(it){
            var v = it.row.cells[col].textContent.trim();
            it.key = v === '' ? null : (isNum ? parseFloat(v.replace(/[^0-9.\\-]/g, '')) : v);
        });
        items.sort(function(a, b){
            if (a.key === null || b.key === null) {
                return (a.key === null) - (b.key === null) || a.order - b.order;
            }
            var c = isNum ? a.key - b.key : collator.compare(a.key, b.key);
            return c * sortDir || a.order - b.order;
        });
        var frag = document.createDocumentFragment();
        items.forEach(function(it){ frag.appendChild(it.row); });
        tbody.appendChild(frag);
        document.querySelectorAll('thead th .sort').forEach(function(s){ s.textContent = ''; });
        th.querySelector('.sort').textContent = sortDir === 1 ? '\\u25B2' : '\\u25BC';
        apply();
    }
    document.querySelectorAll('thead th').forEach(function(th){
        th.addEventListener('click', function(){ sortBy(th); });
    });
    input.addEventListener('input', function(){ clearTimeout(timer); timer = setTimeout(apply, total > 5000 ? 150 : 0); });
    input.addEventListener('keydown', function(e){ if (e.key === 'Escape') { input.value = ''; apply(); } });
    apply();
})();
</script>
</body>
</html>
"""
    html_content = (html_content
        .replace('__TITLE__', esc(os.path.basename(csv_file)))
        .replace('__TILES__', tiles)
        .replace('__THEAD__', thead)
        .replace('__TBODY__', '\n'.join(tbody)))

    html_file = os.path.join(tempfile.gettempdir(), 'dasql_csv_preview.html')
    with open(html_file, 'w', encoding='utf-8') as tmp:
        tmp.write(html_content)

    previewHTML(html_file, browser_path)

#---------- function markdownToText
# @description Converts a Markdown file into clean, speech-friendly plain text.
#              Renders the Markdown to HTML, then strips tags and drops fenced
#              code blocks so a TTS engine reads prose rather than syntax noise.
# @param markdown_file: Path to the Markdown file to convert
# @type markdown_file: str
# @return: Clean spoken-word text
# @rtype: str
def markdownToText(markdown_file):
    from html.parser import HTMLParser
    from html import unescape

    with open(markdown_file, 'r', encoding='utf-8') as f:
        md_content = f.read()

    html = markdown.markdown(md_content, extensions=['fenced_code', 'tables', 'sane_lists'])

    #block tags that should force a line/paragraph break in the spoken output
    block_tags = {'p','div','br','li','tr','h1','h2','h3','h4','h5','h6','blockquote','pre'}
    #tags whose text content we skip entirely (code is unpleasant to listen to)
    skip_tags = {'pre','code'}

    class SpeechExtractor(HTMLParser):
        def __init__(self):
            super().__init__()
            self.parts = []
            self.skip_depth = 0
        def handle_starttag(self, tag, attrs):
            if tag in skip_tags:
                self.skip_depth += 1
            if tag in block_tags:
                self.parts.append('\n')
        def handle_endtag(self, tag):
            if tag in skip_tags and self.skip_depth > 0:
                self.skip_depth -= 1
            if tag in block_tags:
                self.parts.append('\n')
        def handle_data(self, data):
            if self.skip_depth == 0:
                self.parts.append(data)

    parser = SpeechExtractor()
    parser.feed(html)
    text = unescape(''.join(parser.parts))

    #collapse runs of blank lines and trailing spaces into tidy paragraphs
    text = re.sub(r'[ \t]+', ' ', text)
    lines = [ln.strip() for ln in text.splitlines()]
    text = '\n'.join(ln for ln in lines if ln)
    return text.strip()

#---------- function markdownToMp3
# @description Converts a Markdown file to an MP3 audio file using Microsoft
#              Edge neural text-to-speech (edge-tts), then opens it in the
#              default media player. Great for listening to docs on the go.
# @param markdown_file: Path to the Markdown file to convert
# @type markdown_file: str
# @param voice: edge-tts neural voice name (optional)
# @type voice: str
# @param play: Whether to auto-play the resulting MP3 (default True)
# @type play: bool
# @return: Path to the generated MP3 file
# @rtype: str
def markdownToMp3(markdown_file, voice='en-US-AndrewNeural', play=True):
    try:
        import edge_tts
    except ImportError:
        print("⚠️ edge-tts is not installed. Run: python -m pip install edge-tts")
        sys.exit(1)
    import asyncio

    text = markdownToText(markdown_file)
    if not text:
        print("⚠️ Nothing to read - the Markdown produced no spoken text.")
        sys.exit(1)

    mp3_file = os.path.splitext(markdown_file)[0] + '.mp3'

    async def _synth():
        communicate = edge_tts.Communicate(text, voice)
        await communicate.save(mp3_file)

    print("🔊 Converting to speech ({} chars) using {} ...".format(len(text), voice))
    #edge-tts occasionally returns NoAudioReceived transiently - retry a few times
    attempts = 3
    for attempt in range(1, attempts + 1):
        try:
            asyncio.run(_synth())
            if os.path.getsize(mp3_file) > 0:
                break
            raise RuntimeError("empty audio file")
        except Exception as e:
            if attempt == attempts:
                print("⚠️ Text-to-speech failed after {} attempts: {}".format(attempts, e))
                sys.exit(1)
            print("   attempt {} failed ({}), retrying ...".format(attempt, type(e).__name__))
    print("✅ Saved {}".format(mp3_file))

    if play:
        try:
            os.startfile(mp3_file)  # Windows: open in default media player
        except AttributeError:
            #non-Windows fallback
            opener = 'open' if sys.platform == 'darwin' else 'xdg-open'
            subprocess.Popen([opener, mp3_file])
    return mp3_file

def evalCode(lang,ext,code):
    handle, name = tempfile.mkstemp(suffix=".{}".format(ext),prefix="dasql_",text=True)
    handle = os.fdopen(handle, mode="wt",encoding="utf-8")
    handle.write(code)
    handle.close()
    result = subprocess.run([lang, name], stdout=subprocess.PIPE)
    for line in result.stdout.decode('utf-8-sig').splitlines():
        line=line.strip()
        if len(line):
            print(line)
    os.remove(name)

# Function to detect file encoding and remove BOM
def readFileWithoutBOM(file_path):
    with open(file_path, 'rb') as f:
        raw_data = f.read()

    # Detect encoding
    encoding = detect(raw_data)['encoding']
    if not encoding:
        encoding = 'utf-8'  # Fallback to UTF-8 if detection fails

    # Decode the file content and remove BOM if present
    content = raw_data.decode(encoding)
    if content.startswith('\ufeff'):  # Check for UTF-8 BOM
        content = content.lstrip('\ufeff')

    return content

def getInterpreter(filename):
    """
    Determines the appropriate interpreter for the given script file.
    Checks file extension and shebang line.
    Returns the interpreter command as a string or None if not found.
    """
    # Map file extensions to interpreters
    interpreters = {
        '.php': 'php',
        '.py': 'python',
        '.pl': 'perl',
        '.rb': 'ruby',
        '.js': 'node',
        '.lua': 'lua',
        '.r': 'Rscript',
        '.sh': 'bash',
        '.md': 'markdown',
        '.markdown':'markdown',
        '.html':'html',
        '.htm':'html',
        '.csv':'csv',
        '.tsv':'csv'
    }

    _, ext = os.path.splitext(filename.lower())
    interpreter = interpreters.get(ext)

    if not interpreter:
        try:
            with open(filename, 'r', encoding='utf-8') as f:
                first_line = f.readline().strip()
                if first_line.startswith('#!'):
                    parts = first_line[2:].strip().split()
                    interpreter = os.path.basename(parts[0])
        except Exception:
            pass

    return interpreter


def runScript(filename):
    """
    Executes the given script file using its appropriate interpreter.
    Returns stdout on success or stderr on failure.
    """
    interpreter = getInterpreter(filename)

    if not interpreter:
        return "Unable to determine interpreter for file: {}".format(filename)

    try:
        result = subprocess.run(
            [interpreter, filename],
            capture_output=True,
            text=True,
            shell=False
        )
        return result.stdout if result.returncode == 0 else result.stderr
    except Exception as e:
        return "Error executing script: {}".format(e)