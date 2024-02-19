#! python
'''
    DaSQL: DOS Access to SQL -  command line way to query any database setup in WaSQL
'''
import sys
import os
import requests
import urllib3
import configparser
import subprocess
import tempfile
import json
import re
import csv
from requests.packages import urllib3
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
#get the script path
script_directory = os.path.dirname(os.path.abspath(sys.argv[0]))
#read dasql.ini for settings
inifile="{}/dasql.ini".format(script_directory)
config = configparser.ConfigParser()
config.read(inifile)



#set params to keys in global
params=dict(config.items('global'))
params['query']=''
#check to see if the args are a filename
params['arg_query']='';
for arg in sys.argv[1:]:
    params['arg_query']+="{}  ".format(arg)
params['arg_query']=params['arg_query'].strip()
if len(params['arg_query']) > 0 and os.path.isfile(params['arg_query']):
    #check for a section with this name
    file_name=os.path.splitext(os.path.basename(params['arg_query']))[0]
    if(config.has_section(file_name)):
        #overide any params from section
        section=dict(config.items(file_name))
        for key in section:
            params[key]=section[key]
        #set query to file contents
        file = open(params['arg_query'], mode='r')
        params['query'] = file.read()
        file.close()
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
        params['arg_query']='';
        for arg in sys.argv[2:]:
            params['arg_query']+="{}  ".format(arg)
        params['arg_query']=params['arg_query'].strip()
        #if the line starts with two dashes, remove them.
        if params['arg_query'].startswith('--'):
            params['arg_query']=params['arg_query'][3:]
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
                file = open(params['arg_query'], mode='r')
                params['query'] = file.read()
                file.close()
                params['tempfile']=params['arg_query']

            params['arg_query']=''
    else:
        #load the rest of args as the arg_query
        params['arg_query']='';
        for arg in sys.argv[1:]:
            params['arg_query']+="{}  ".format(arg)

params['arg_query']=params['arg_query'].strip()
if len(params['arg_query']) > 0:
    params['query']=params['arg_query']
params['query']=params['query'].strip()
#if the line starts with two dashes, remove them.
if params['query'].startswith('--'):
    params['query']=params['query'][3:]
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
    print(csvlist)
    result = subprocess.run(csvlist, cwd=wdir, stdout=subprocess.PIPE)
    for line in result.stdout.decode('utf-8-sig').splitlines():
        line=line.strip()
        if len(line):
            print(line)
elif len(params['query']) > 0 and params['query'].startswith('http'):
    #launch a URL
    #Reference: https://stackoverflow.com/questions/6375149/how-to-open-a-url-with-get-query-parameters-using-the-command-line-in-windows
    url=params['query'].replace('&','^&');
    os.system("start {}".format(url))
elif len(params['query']) > 0 and params['query'].startswith('{') and params['query'].endswith('}'):
    #pretty print a json string
    jstr= re.sub('\s', '', params['query'])
    jstr = re.sub('(\w+)', '"\g<1>"', jstr)
    parsed = json.loads(jstr)
    print(json.dumps(parsed, indent=4))
elif len(params['query']) > 0 and params['query'].startswith('[') and params['query'].endswith(']'):
    #pretty print a json string
    jstr= re.sub('\s', '', params['query'])
    jstr = re.sub('(\w+)', '"\g<1>"', jstr)
    parsed = json.loads(jstr)
    print(json.dumps(parsed, indent=4))
elif len(params['query']) > 0 and params['query'].startswith('<?php'):
    #Run a PHP command
    handle, name = tempfile.mkstemp(suffix=".php",prefix="dasql_",text=True)
    handle = os.fdopen(handle, mode="wt",encoding="utf-8")
    handle.write(params['query'])
    handle.close()
    result = subprocess.run(['php', name], stdout=subprocess.PIPE)
    for line in result.stdout.decode('utf-8-sig').splitlines():
        line=line.strip()
        if len(line):
            print(line)
elif len(params['query']) > 0 and params['query'].startswith('<?py'):
    #Run a lua command
    params['query']=params['query'][4:]
    if params['query'].endswith('?>'):
        params['query']=params['query'][:len(params['query'])-2]
    handle, name = tempfile.mkstemp(suffix=".py",prefix="dasql_",text=True)
    handle = os.fdopen(handle, mode="wt",encoding="utf-8")
    handle.write(params['query'])
    handle.close()
    result = subprocess.run(['python', name], stdout=subprocess.PIPE)
    for line in result.stdout.decode('utf-8-sig').splitlines():
        line=line.strip()
        if len(line):
            print(line)
elif len(params['query']) > 0 and params['query'].startswith('<?lua'):
    #Run a lua command
    params['query']=params['query'][6:]
    if params['query'].endswith('?>'):
        params['query']=params['query'][:len(params['query'])-2]
    handle, name = tempfile.mkstemp(suffix=".lua",prefix="dasql_",text=True)
    handle = os.fdopen(handle, mode="wt",encoding="utf-8")
    handle.write(params['query'])
    handle.close()
    result = subprocess.run(['lua', name], stdout=subprocess.PIPE)
    for line in result.stdout.decode('utf-8-sig').splitlines():
        line=line.strip()
        if len(line):
            print(line)
elif len(params['query']) > 0:
    #prepare the key/value pairs to pass to WaSQL base_url
    data={
        'db': params['db'],
        'func':'sql',
        'format':params['output_format'],
        '-nossl':1,
        'offset':0,
        'username':os.environ["USERNAME"].lower(),
        'AjaxRequestUniqueId':'dasql.py',
        '_auth': params['authkey'],
        '_menu': 'sqlprompt',
        'sql_full':params['query']
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

    #set the url to post to
    url=params['base_url']+'/php/admin.php'
    #disable ssl cert warnings since this is just an internal url anyway
    urllib3.disable_warnings()

    #call localhost to run the query
    try:
        r = requests.post(url,data,verify=False)
    except requests.exceptions.Timeout:
        # Maybe set up for a retry, or continue in a retry loop
        print('DaSQL: Timeout error')
        sys.exit(1)
    except requests.exceptions.TooManyRedirects:
        # Tell the user their URL was bad and try a different one
        print('DaSQL: TooManyRedirects error')
        sys.exit(2)
    except requests.exceptions.HTTPError as errh:
        print ("DaSQL: Http Error:")
        sys.exit(3)
    except requests.exceptions.ConnectionError as errc:
        print ("DaSQL: ConnectionError trying to connect to {}".format(params['base_url']))
        sys.exit(4)
    except requests.exceptions.RequestException as e:  # This is the correct syntax
        raise SystemExit(e)
    for line in r.content.decode('utf-8-sig').splitlines():
        line=line.strip()
        if len(line):
            print(line)
else:
    print('DaSQL: No Query to run')
    print(sys.argv)

