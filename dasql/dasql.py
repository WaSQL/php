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
import codecs
from requests.packages import urllib3
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
#import the daSQL functions so we can call them as df.{function_name}
import dasql_functions as df


def postToWaSQL(params, sql_full):
    """POST a sql_full string to WaSQL's admin SQL prompt and return the decoded response.
    Shared by the SQL, .cli (cmd>), and remote php>/py>/lua> paths so auth + transport
    live in one place. Exits the process on connection-level errors, just like the
    original inline handlers did."""
    data={
        'db': params.get('db',''),
        'func':'sql',
        'format':params.get('output_format','dos'),
        '-nossl':1,
        'offset':0,
        'username':os.environ.get("USERNAME", os.environ.get("USER", "")).lower(),
        'AjaxRequestUniqueId':'dasql.py',
        '_auth': params.get('authkey',''),
        '_menu': 'sqlprompt',
        'sql_full':sql_full
    }
    #WaSQL supports multiple authentication methods: set auth method based on params
    if 'apikey' in params:
        data['apikey']=params['apikey']
        data['username']=params['username']
        data['_auth']=1
    elif 'authkey' in params:
        data['_auth']=params['authkey']
    elif 'tauthkey' in params:
        data['_tauth']=params['tauthkey']
    elif 'username' in params:
        data['_login']=1
        data['username']=params['username']
        data['password']=params['password']
    elif 'email' in params:
        data['_login']=1
        data['email']=params['email']
        data['password']=params['password']
    elif 'phone' in params:
        data['_login']=1
        data['phone']=params['phone']
        data['password']=params['password']
    #post to the WaSQL admin endpoint (ssl cert warnings disabled - internal url)
    url=params['base_url'].rstrip('/')+'/php/admin.php'
    urllib3.disable_warnings()
    try:
        r = requests.post(url,data,verify=False)
    except requests.exceptions.Timeout:
        print('DaSQL: Timeout error')
        sys.exit(1)
    except requests.exceptions.TooManyRedirects:
        print('DaSQL: TooManyRedirects error')
        sys.exit(2)
    except requests.exceptions.HTTPError:
        print('DaSQL: Http Error:')
        sys.exit(3)
    except requests.exceptions.ConnectionError:
        print('DaSQL: ConnectionError trying to connect to {}'.format(params.get('base_url','')))
        sys.exit(4)
    except requests.exceptions.RequestException as e:
        raise SystemExit(e)
    #decode as ISO-8859-1 to avoid invalid continuation byte errors
    return r.content.decode('ISO-8859-1')


# Check if any arguments were passed
if len(sys.argv) < 3:
    print("No arguments provided. Please provide at least one argument.")
    sys.exit(1)

# Set stdout to use UTF-8 encoding
sys.stdout = codecs.getwriter("utf-8")(sys.stdout.detach())

# Get the script directory and construct the INI file path
script_directory = os.path.dirname(os.path.abspath(sys.argv[0]))
inifile = os.path.join(script_directory, "dasql.ini")

# Read the file content without BOM
if not os.path.isfile(inifile):
    print("DaSQL: dasql.ini not found. Copy dasql.ini.sample to dasql.ini and configure it.")
    sys.exit(1)
file_content = df.readFileWithoutBOM(inifile)

# Use configparser to parse the content
config = configparser.ConfigParser()
config.read_string(file_content)  # Read from the string instead of the file
# #get the script path
# script_directory = os.path.dirname(os.path.abspath(sys.argv[0]))
# #read dasql.ini for settings
# inifile="{}/dasql.ini".format(script_directory)
# config = configparser.ConfigParser()
# config.read(inifile)
section_name=''
#set params to keys in global
params=dict(config.items('global'))
params['query']=''
#check to see if the args are a filename
params['arg_query']=''
for arg in sys.argv[1:]:
    params['arg_query']+="{}  ".format(arg)
params['arg_query']=params['arg_query'].strip()
#decide whether this file should run on the REMOTE WaSQL server:
# - a .cli file                                  -> shell command   (cmd>)
# - a .php/.py/.lua file living in the DaSQL dir -> server-side code (php>/py>/lua>)
#everything else falls through to the local interpreter / SQL handling below.
remote_prefix=None
remote_ext=os.path.splitext(sys.argv[1].lower())[1]
if sys.argv[1].lower().endswith('.cli'):
    remote_prefix='cmd>'
elif remote_ext in ('.php','.py','.lua'):
    #a .php/.py/.lua file runs on the server only when it lives in the DaSQL directory AND
    #its name matches a configured section. The section-match requirement targets the right
    #server and keeps DaSQL's own files (dasql.py, *_installer.py, ...) running locally.
    remote_base=os.path.splitext(os.path.basename(sys.argv[1]))[0]
    if os.path.isfile(os.path.join(script_directory, os.path.basename(sys.argv[1]))) and config.has_section(remote_base):
        remote_prefix={'.php':'php>','.py':'py>','.lua':'lua>'}[remote_ext]

interpreter = df.getInterpreter(sys.argv[1])
if remote_prefix is None and interpreter:
    #print("interpreter:{}".format(interpreter))
    if interpreter == 'markdown':
        df.previewMarkdown(sys.argv[1])
    elif interpreter == 'html':
        df.previewHTML(sys.argv[1])
    else:
        rtn = df.runScript(sys.argv[1])
        print(rtn)
    params['arg_query']=''
else:
    if remote_prefix is not None:
        #remote execution: run on the server the same way a .cli does.
        #load the matching ini section (by filename, then by directory) so we target
        #that section's server/db/auth instead of falling back to [global] only.
        remote_section=os.path.splitext(os.path.basename(sys.argv[1]))[0]
        remote_dir=os.path.splitext(os.path.basename(sys.argv[2]))[0] if len(sys.argv) > 2 else ''
        if config.has_section(remote_section):
            section_name=remote_section
            for _k,_v in config.items(remote_section):
                params[_k]=_v
        elif remote_dir and config.has_section(remote_dir):
            section_name=remote_dir
            for _k,_v in config.items(remote_dir):
                params[_k]=_v
        if remote_prefix == 'cmd>':
            #.cli: run the selected command line (read a temp/file path if one was passed)
            remote_query = sys.argv[3].strip() if len(sys.argv) > 3 else ''
            if os.path.isfile(remote_query):
                with open(remote_query, 'r', encoding='utf-8') as _f:
                    remote_query = _f.read().strip()
            if not len(remote_query):
                print('DaSQL: no command selected')
                sys.exit(0)
        else:
            #.php/.py/.lua: run the ENTIRE file on the server. This mirrors how a local
            #script runs the whole file - just on the remote host instead of locally.
            script_path = sys.argv[1] if os.path.isfile(sys.argv[1]) else os.path.join(script_directory, os.path.basename(sys.argv[1]))
            if not os.path.isfile(script_path):
                print('DaSQL: could not find {} in the DaSQL directory'.format(os.path.basename(sys.argv[1])))
                sys.exit(0)
            with open(script_path, 'r', encoding='utf-8') as _f:
                remote_query = _f.read().strip()
            if not len(remote_query):
                print('DaSQL: nothing to run')
                sys.exit(0)
            #the server wraps php>/py>/lua> code in <?lang ?>, so strip a surrounding
            #<?php ?> wrapper from .php files to avoid a double-wrapped (broken) block.
            if remote_prefix == 'php>':
                _c = remote_query
                if _c.lower().startswith('<?php'):
                    _c = _c[5:]
                elif _c.startswith('<?'):
                    _c = _c[2:]
                if _c.rstrip().endswith('?>'):
                    _c = _c.rstrip()[:-2]
                remote_query = _c.strip()
        #double backslashes so the server's stripslashes() doesn't eat them
        remote_query = remote_query.replace('\\', '\\\\')
        output = postToWaSQL(params, remote_prefix+remote_query).replace('\r\n', '\n').replace('\r', '\n')
        if remote_prefix == 'cmd>':
            # reformat the single-line header "CMD: x, DIR: y, RUNTIME: z, RTNCODE: n" onto separate lines
            lines = output.splitlines()
            result = []
            rtncode = None
            for line in lines:
                if line.startswith('CMD:') and ', DIR:' in line:
                    for part in re.split(r',\s*(?=CMD:|DIR:|RUNTIME:|RTNCODE:)', line):
                        part = part.strip()
                        if part.startswith('DIR:'):
                            continue
                        result.append(part)
                        if part.startswith('RTNCODE:'):
                            rtncode = part.split(':', 1)[1].strip()
                elif line.startswith('DIR:'):
                    pass
                elif line.startswith('RTNCODE:'):
                    result.append(line.strip())
                    rtncode = line.split(':', 1)[1].strip()
                else:
                    result.append(line)
            # insert STATUS after RTNCODE
            rtncode_idx = next((i for i, l in enumerate(result) if l.startswith('RTNCODE:')), None)
            if rtncode is not None and rtncode_idx is not None:
                status = 'Success' if rtncode == '0' else 'Failure'
                result.insert(rtncode_idx + 1, 'STATUS: {}'.format(status))
            print('\n'.join(result))
        else:
            #code output from the server - print as-is
            print(output.rstrip())
        sys.exit(0)
    dir_name=os.path.splitext(os.path.basename(sys.argv[2]))[0]

    if len(params['arg_query']) > 0 and os.path.isfile(params['arg_query']):
        #check for a section with this name
        file_name=os.path.splitext(os.path.basename(params['arg_query']))[0]
        if(config.has_section(file_name)):
            section_name=file_name
            #overide any params from section
            section=dict(config.items(file_name))
            for key in section:
                params[key]=section[key]
            #set query to file contents
            file = open(params['arg_query'], mode='r', encoding='utf-8')
            params['query'] = file.read()
            file.close()
            if(params['arg_query'].endswith('_deleteme')):
                os.remove(params['arg_query'])
            params['tempfile']=params['arg_query']
        elif(config.has_section(dir_name)):
            section_name=dir_name
            #overide any params from section
            section=dict(config.items(dir_name))
            for key in section:
                params[key]=section[key]
            #set query to file contents
            file = open(params['arg_query'], mode='r', encoding='utf-8')
            params['query'] = file.read()
            file.close()
            if(params['arg_query'].endswith('_deleteme')):
                os.remove(params['arg_query'])
            params['tempfile']=params['arg_query']

        params['arg_query']=''
    else:
        #check for section_name
        #section_name=sys.argv[1]
        section_name=os.path.splitext(os.path.basename(sys.argv[1]))[0]
        if(config.has_section(section_name)):
            #overide any params from section
            section=dict(config.items(section_name))
            for key in section:
                params[key]=section[key]
            #load the rest of args as the arg_query
            params['arg_query']=''
            for arg in sys.argv[3:]:
                params['arg_query']+="{}  ".format(arg)
            params['arg_query']=params['arg_query'].strip()
            #if the line starts with two dashes, remove them.
            if params['query'].startswith('-- '):
                params['query']=params['query'][3:].strip()
            elif params['query'].startswith('--'):
                params['query']=params['query'][2:].strip()
            elif params['query'].startswith('#'):
                params['query']=params['query'][1:].strip()
            #check to see if the args are a filename
            if len(params['arg_query']) > 0 and os.path.isfile(params['arg_query']):
                #check for a section with this name
                file_name=os.path.splitext(os.path.basename(params['arg_query']))[0]
                if(config.has_section(file_name)):
                    #overide any params from section
                    section=dict(config.items(file_name))
                    for key in section:
                        params[key]=section[key]
                    #set query to file contents
                    file = open(params['arg_query'], mode='r', encoding='utf-8')
                    params['query'] = file.read()
                    file.close()
                    if(params['arg_query'].endswith('_deleteme')):
                        os.remove(params['arg_query'])
                    params['tempfile']=params['arg_query']

                params['arg_query']=''
        elif(config.has_section(dir_name)):
            #overide any params from section
            section=dict(config.items(dir_name))
            for key in section:
                params[key]=section[key]
            #load the rest of args as the arg_query
            params['arg_query']=''
            for arg in sys.argv[3:]:
                params['arg_query']+="{}  ".format(arg)
            params['arg_query']=params['arg_query'].strip()
            #if the line starts with two dashes, remove them.
            if params['query'].startswith('-- '):
                params['query']=params['query'][3:].strip()
            elif params['query'].startswith('--'):
                params['query']=params['query'][2:].strip()
            elif params['query'].startswith('#'):
                params['query']=params['query'][1:].strip()
            #check to see if the args are a filename
            if len(params['arg_query']) > 0 and os.path.isfile(params['arg_query']):
                #check for a section with this name
                file_name=os.path.splitext(os.path.basename(params['arg_query']))[0]
                if(config.has_section(file_name)):
                    #overide any params from section
                    section=dict(config.items(file_name))
                    for key in section:
                        params[key]=section[key]
                    #set query to file contents
                    file = open(params['arg_query'], mode='r', encoding='utf-8')
                    params['query'] = file.read()
                    file.close()
                    if(params['arg_query'].endswith('_deleteme')):
                        os.remove(params['arg_query'])
                    params['tempfile']=params['arg_query']
                elif(config.has_section(dir_name)):
                    #overide any params from section
                    section=dict(config.items(dir_name))
                    for key in section:
                        params[key]=section[key]
                    #set query to file contents
                    file = open(params['arg_query'], mode='r', encoding='utf-8')
                    params['query'] = file.read()
                    file.close()
                    if(params['arg_query'].endswith('_deleteme')):
                        os.remove(params['arg_query'])
                    params['tempfile']=params['arg_query']

                params['arg_query']=''
        elif os.path.isfile(sys.argv[2]):
            file = open(sys.argv[2], mode='r', encoding='utf-8')
            params['query'] = file.read()
            file.close()
            if(sys.argv[2].endswith('_deleteme')):
                os.remove(sys.argv[2])
            if params['query'].startswith('-- '):
                params['query']=params['query'][3:].strip()
            elif params['query'].startswith('--'):
                params['query']=params['query'][2:].strip()
            elif params['query'].startswith('#'):
                params['query']=params['query'][1:].strip()
            params['tempfile']=sys.argv[2]
            params['arg_query']=''
        else:
            #load the rest of args as the arg_query
            params['arg_query']=''
            for arg in sys.argv[1:]:
                params['arg_query']+="{}  ".format(arg)

    params['arg_query']=params['arg_query'].strip()
    if len(params['arg_query']) > 0:
        params['query']=params['arg_query']
    params['query']=params['query'].strip()
    #stdin fallback: editors that pipe the selection to stdin instead of passing it
    #as an argument (e.g. Geany, gedit, and other Unix filters) leave the query empty,
    #so read it from stdin when we still have nothing. isatty() guards against blocking
    #on an interactive terminal, where nothing is piped in.
    if len(params['query']) == 0 and not sys.stdin.isatty():
        try:
            stdin_query=sys.stdin.read()
            if stdin_query:
                params['query']=stdin_query.strip()
        except Exception:
            pass
    #if the line starts with two dashes, remove them.
    if params['query'].startswith('-- '):
        params['query']=params['query'][3:].strip()
    elif params['query'].startswith('--'):
        params['query']=params['query'][2:].strip()
    elif params['query'].startswith('#'):
        params['query']=params['query'][1:].strip()
    #check for shortcuts in ini file - first section then global
    global_shortcut="global:{}".format(params['query'])
    shortcut="{}:{}".format(section_name,params['query'])
    if(config.has_section(shortcut)):
        #overide any params from section
        shortcut_section=dict(config.items(shortcut))
        if 'query' in shortcut_section:
            params['query']=shortcut_section['query']
    elif(config.has_section(global_shortcut)):
        #overide any params from section
        shortcut_section=dict(config.items(global_shortcut))
        if 'query' in shortcut_section:
            params['query']=shortcut_section['query']

    #check for shell command requests
    #c:\windows>dir
    output = re.search('^([a-z]?):(.*?)>(.+)$', params['query'], flags=re.IGNORECASE)
    if output is not None:
        #run a windows command and show output
        wdir="{}:{}".format(output.group(1),output.group(2))
        csvlist=[]
        csvparts=list(csv.reader(output.group(3), delimiter=' ', quotechar='"', quoting=csv.QUOTE_MINIMAL))
        cp=''
        for p in csvparts:
            if len(p) == 1 and len(p[0]) == 1:
                cp=cp+p[0]
            if len(p) == 2 and len(p[0]) == 0:
                csvlist.append(cp)
                cp=''
            if len(p) == 1 and len(p[0]) > 1:
                csvlist.append(p[0])
        if len(cp):
            csvlist.append(cp)
        #print(csvlist)
        result = subprocess.run(csvlist, cwd=wdir, stdout=subprocess.PIPE, stderr = subprocess.STDOUT)
        for line in result.stdout.decode('utf-8-sig').splitlines():
            line=line.strip()
            if len(line):
                print(line)
    elif len(params['query']) > 0 and params['query'].lower().startswith('math>'):
        #Run a python command
        params['query']=params['query'][5:].strip()
        print(eval(params['query']))
    elif len(params['query']) > 0 and params['query'].lower().startswith('calc>'):
        #Run a python command
        params['query']=params['query'][5:].strip()
        print(eval(params['query']))
    elif len(params['query']) > 0 and params['query'].lower().startswith('cmd>'):
        #run a command  cmd>ls -al d:\wasql
        params['query']=params['query'][4:].strip()
        csvlist=[]
        csvparts=list(csv.reader(params['query'], delimiter=' ', quotechar='"', quoting=csv.QUOTE_MINIMAL))
        cp=''
        for p in csvparts:
            if len(p) == 1 and len(p[0]) == 1:
                cp=cp+p[0]
            if len(p) == 2 and len(p[0]) == 0:
                csvlist.append(cp)
                cp=''
            if len(p) == 1 and len(p[0]) > 1:
                csvlist.append(p[0])
        if len(cp):
            csvlist.append(cp)
        #print(csvlist)
        result = subprocess.run(csvlist, stdout=subprocess.PIPE, stderr = subprocess.STDOUT)
        for line in result.stdout.decode('utf-8-sig').splitlines():
            line=line.strip()
            if len(line):
                print(line)
    elif len(params['query']) > 0 and params['query'].lower().startswith('http'):
        #launch a URL
        #Reference: https://stackoverflow.com/questions/6375149/how-to-open-a-url-with-get-query-parameters-using-the-command-line-in-windows
        url=params['query'].replace('&','^&')
        os.system("start {}".format(url))
    elif len(params['query']) > 0 and ((params['query'].startswith('{') and params['query'].endswith('}')) or (params['query'].startswith('[') and params['query'].endswith(']'))):
        #pretty print a JSON string
        code='<?php\r\n$jsonstr=<<<ENDOFSTR\r\n{}\r\nENDOFSTR;$json=json_decode($jsonstr);$str=json_encode($json,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);\r\n$str=str_replace("\t","     ",$str);echo $str;\r\n?>\r\n'.format(params['query'])
        df.evalCode('php','php',code)
    elif len(params['query']) > 0 and params['query'].lower().startswith('<?php'):
        #Run a PHP command
        df.evalCode('php','php',params['query'])
    elif len(params['query']) > 0 and params['query'].lower().startswith('<?py'):
        #Run a python command
        params['query']=params['query'][4:]
        if params['query'].endswith('?>'):
            params['query']=params['query'][:len(params['query'])-2]
        df.evalCode('python','py',params['query'])
    elif len(params['query']) > 0 and params['query'].lower().startswith('<?lua'):
        #Run a lua command
        params['query']=params['query'][6:]
        if params['query'].endswith('?>'):
            params['query']=params['query'][:len(params['query'])-2]
        df.evalCode('lua','lua',params['query'])
    elif len(params['query']) > 0 and params['query'].lower().startswith(('php>','py>','lua>')):
        #run PHP/python/lua code on the REMOTE WaSQL server (admins only), the same way a
        #.cli's cmd> runs a shell command there - the server evals the code server-side.
        #normalise "lang> code" -> "lang>code" so a space after > doesn't become a leading
        #indent (which breaks python/lua). double backslashes so stripslashes() keeps escapes.
        _m = re.match(r'(?is)^(php|py|lua)\>\s*(.*)$', params['query'])
        remote_code = (_m.group(1).lower()+'>'+_m.group(2)).replace('\\', '\\\\')
        output = postToWaSQL(params, remote_code).replace('\r\n', '\n').replace('\r', '\n')
        print(output.rstrip())
    elif len(params['query']) > 0 and params['query'].lower().startswith(("running","fld","idx","help","commands","history","db","versions","grade","ddl","tables","fields","cal ","running_queries","sessions","views","indexes","kill ","uptime","memory","server","processes","df","top","mem","os","ps","explain","select","insert","update","delete","with","create","alter","drop","truncate","grant","revoke","comment","explain","analyze","describe","desc","show","use","set","reset","call","execute","exec","do","declare","fetch","copy","load","import","export","merge","lock","unload","begin","start transaction","start","commit","rollback","savepoint","release","end","reindex","pragma","vacuum","refresh","checkpoint")):
        #run the query on the WaSQL server and print the results
        for line in postToWaSQL(params, params['query']).splitlines():
            line=line.strip()
            if len(line):
                print(line)
    else:
        print('DaSQL: not sure what to do with this')
        print(sys.argv)
        print(params['query'])

