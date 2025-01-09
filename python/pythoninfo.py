#! python
"""
Installation
    python3 -m pip install setuptools
        if pip is not installed run the following first to install pip
            python3 -m ensurepip --upgrade
"""
import pkg_resources
import sys
import io
import codecs

# Set up UTF-8 encoding for stdout
if sys.stdout.encoding != 'UTF-8':
    try:
        # Try the newer reconfigure method first (Python 3.7+)
        sys.stdout.reconfigure(encoding='utf-8')
    except (AttributeError, Exception):
        # Fall back to setting up a UTF-8 writer for older Python versions
        sys.stdout = codecs.getwriter('utf-8')(sys.stdout.buffer)

# Do the same for stdin if needed
if sys.stdin.encoding != 'UTF-8':
    try:
        sys.stdin.reconfigure(encoding='utf-8')
    except (AttributeError, Exception):
        sys.stdin = codecs.getreader('utf-8')(sys.stdin.buffer)

prows = ''
for p in pkg_resources.working_set:
    prow = '''
        <section>
        <h2><a name="module_{}">{}</a></h2>
        <table>
        '''.format(p.project_name, p.project_name)
    info = ''
    try:
        info = pkg_resources.get_distribution(p.project_name).get_metadata('PKG-INFO')
    except Exception as err:
        info = pkg_resources.get_distribution(p.project_name).get_metadata('METADATA')

    for line in info.splitlines():
        parts = line.split(':', 1)
        partscount = len(parts)
        if partscount != 2:
            continue

        if len(parts[0]) == 0:
            break

        if "<" in parts[0] or "|" in parts[0] or "." in parts[0] or \
           "[" in parts[0] or " " in parts[0] or "(" in parts[0]:
            break

        if parts[0] in ['Metadata-Version', 'Description-Content-Type', 'Classifier']:
            continue

        if parts[0] == 'Home-page':
            parts[1] = '<a href="{}" class="w_link" target="_blank">{}</a>'.format(
                parts[1].strip(), parts[1].strip())

        if 'email' in parts[0]:
            parts[1] = '<a href="mailto:{}" class="w_link">{}</a>'.format(
                parts[1].strip(), parts[1].strip())

        key = parts[0]
        val = parts[1].strip()
        trow = '    <tr><td class="align-left w_small w_nowrap" style="width:300px;background:#1d415e4D;">{}</td><td class="align-left w_small" style="min-width:300px;background-color:#CCCCCC80;">{}</td></tr>'.format(key, val)
        trow = trow + "\n"
        prow = prow + trow

    prow = prow + "\n</table>\n</section>\n\n"
    prows = prows + prow

output = """
<header>
    <div style="background:#1d415e;padding:10px 20px;margin-bottom:20px;border:1px solid #000;">
        <div style="font-size:clamp(24px,3vw,48px);color:#FFF;"><span class="brand-python"></span> Python</div>
        <div style="font-size:clamp(11px,2vw,18px);color:#FFF;">Version {}</div>
    </div>
</header>
{}
"""

# Ensure the output is properly encoded
try:
    print(output.format(sys.version, prows))
except UnicodeEncodeError:
    # If we still have encoding issues, try to encode and decode properly
    encoded_output = output.format(sys.version, prows).encode('utf-8', errors='replace')
    print(encoded_output.decode('utf-8'))