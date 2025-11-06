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

    subprocess.Popen([browser_exe, html_file], shell=True)

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
            body {{ font-family: Arial, sans-serif; padding: 2em; max-width: 800px; margin: auto; }}
            pre {{ background: #f4f4f4; padding: 1em; overflow: auto; }}
            code {{ background: #f4f4f4; padding: 0.2em 0.4em; }}

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

    subprocess.Popen([browser_exe, html_file], shell=True)

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
        '.htm':'html'
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