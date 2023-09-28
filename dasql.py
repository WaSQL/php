#! python
'''
    DaSQL: DOS Access to SQL -  command line way to query any database setup in WaSQL
'''
import sys
import os
import requests
import urllib3
import configparser
#get the script path
script_directory = os.path.dirname(os.path.abspath(sys.argv[0]))
#read dasql.ini for settings
inifile="{}/dasql.ini".format(script_directory)
config = configparser.ConfigParser()
config.read(inifile)

#set params to keys in global
params=dict(config.items('global'))

#set arg_query to blank
params['arg_query']=''

#check for section_name
section_name=sys.argv[1]
if(config.has_section(section_name)):
    #overide any params from section
    section=dict(config.items(section_name))
    for key in section:
        params[key]=section[key]
    #load the rest of args as the arg_query
    for arg in sys.argv[2:]:
        params['arg_query']+="{}  ".format(arg)
else:
    #load all the args as the arg_query
    for arg in sys.argv[1:]:
        params['arg_query']+="{}  ".format(arg)

params['arg_query']=params['arg_query'].strip()
#create a prepared request object
p = requests.models.PreparedRequest()
if(len(params['arg_query']) > 0):
    #check to see if they are passing in a file
    if os.path.isfile(params['arg_query']):
        #check for a section with this name
        file_name=os.path.splitext(os.path.basename(params['arg_query']))[0]
        if(config.has_section(file_name)):
            #reset from global
            #overide any params from section
            section=dict(config.items('global'))
            for key in section:
                params[key]=section[key]
            #overide any params from section
            section=dict(config.items(file_name))
            for key in section:
                params[key]=section[key]
        #set query to file contents
        file = open(params['arg_query'], mode='r')
        params['query'] = file.read()
        file.close()
    else:
        params['query']=params['arg_query']

#prepare the key/value pairs to pass to WaSQL base_url
data={
    '_auth': params['authkey'], 
    'db': params['db'],
    '_menu': 'sqlprompt',
    'func':'sql',
    'format':params['output_format'],
    '-nossl':1,
    'offset':0,
    'username':os.environ["USERNAME"].lower(),
    'computername':os.environ["COMPUTERNAME"],
    'AjaxRequestUniqueId':'dasql.py',
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

#prepare the url with the key/value pairs
p.prepare_url(url=params['base_url']+'/php/admin.php', params=data)

#disable ssl cert warnings since this is just an internal url anyway
urllib3.disable_warnings()

#call localhost to run the query
r = requests.get(p.url,verify=False)
print(r.content.decode('utf-8-sig'))

