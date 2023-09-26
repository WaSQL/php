#! python
'''
    command line way to query any database setup in WaSQL
'''
import sys
import os
import requests
import urllib3
import configparser
#read query.ini for settings
config = configparser.ConfigParser()
config.read("query.ini")
authkey = ''
base_url = 'http://localhost'
output_format = 'json'
db=''
query=''

if(config.has_section('global')):
    if(config.has_option('global','authkey')):
        authkey=config.get('global', "authkey")
    if(config.has_option('global','base_url')):
        base_url=config.get('global', "base_url")
    if(config.has_option('global','output_format')):
        output_format=config.get('global', "output_format")
    if(config.has_option('global','db')):
        db=config.get('global', "db")
    if(config.has_option('global','query')):
        query=config.get('global', "query")

section=sys.argv[1]
if(config.has_section(section)):
    if(config.has_option(section,'authkey')):
        authkey=config.get(section, "authkey")
    if(config.has_option(section,'base_url')):
        base_url=config.get(section, "base_url")
    if(config.has_option(section,'output_format')):
        output_format=config.get(section, "output_format")
    if(config.has_option(section,'db')):
        db=config.get(section, "db")
    if(config.has_option(section,'query')):
        query=config.get(section, "query")

#create a prepared request object
p = requests.models.PreparedRequest()
if(len(query)==0):
    for arg in sys.argv[2:]:
        query+="{}  ".format(arg)      
#prepare the key/value pairs to pass to ctreepo
data={
    '_auth': authkey, 
    'db': db,
    '_menu': 'sqlprompt',
    'func':'sql',
    'format':output_format,
    '-nossl':1,
    'offset':0,
    'username':os.environ["USERNAME"].lower(),
    'computername':os.environ["COMPUTERNAME"],
    'AjaxRequestUniqueId':'query.py',
    'sql_full':query
}
#prepare the url with the key/value pairs
p.prepare_url(url=base_url+'/php/admin.php', params=data)
#disable ssl cert warnings since this is just an internal url anyway
urllib3.disable_warnings()
#call localhost to run the query
r = requests.get(p.url,verify=False)
print(r.content.decode('utf-8'))

