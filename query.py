import sys
import os
import requests
import urllib3
import configparser
#read query.ini for settings
user=os.environ["USERNAME"].lower()
config = configparser.ConfigParser()
config.read("query.ini")
authkey = config.get(user, "authkey")
base_url = config.get(user, "base_url")
output_format = config.get(user, "output_format")
#create a prepared request object
p = requests.models.PreparedRequest()
#build the SQL query from the args
sql = '';  
for arg in sys.argv[2:]:
    sql+="{}  ".format(arg)      
#prepare the key/value pairs to pass to ctreepo
data={
    '_auth': authkey, 
    'db': sys.argv[1],
    '_menu': 'sqlprompt',
    'func':'sql',
    'format':output_format,
    '-nossl':1,
    'offset':0,
    'username':os.environ["USERNAME"],
    'computername':os.environ["COMPUTERNAME"],
    'AjaxRequestUniqueId':'query.py',
    'sql_full':sql
}
#prepare the url with the key/value pairs
p.prepare_url(url=base_url+'/php/admin.php', params=data)
#disable ssl cert warnings since this is just an internal url anyway
urllib3.disable_warnings()
#call localhost to run the query
r = requests.get(p.url,verify=False)
print(r.content.decode('utf-8'))

