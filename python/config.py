#! python
'''
    config.py parses config.xml and builds CONFIG and ALLCONFIG
    References:
        https://www.guru99.com/manipulating-xml-with-python.html
    Installs needed
        pip install xmltodict
        php install pprint
'''

import os
import common
import sys
import xmltodict

mypath = os.path.dirname(os.path.realpath(__file__))
parpath = common.getParentPath(mypath)
configfile = parpath+os.path.sep+"config.xml"
#HTTP_HOST - default to localhost for command line stuff
HTTP_HOST = 'localhost'
if 'HTTP_HOST' in os.environ:
    HTTP_HOST = os.environ['HTTP_HOST']
#ALLCONFIG
ALLCONFIG = {}
with open(configfile) as fd:
    ALLCONFIG = xmltodict.parse(fd.read(),attr_prefix='',dict_constructor=dict)
#DATABASE
DATABASE = {}
for db in ALLCONFIG['hosts']['database']:
    key = db['name']
    DATABASE[key] = db

#CONFIG 
CONFIG = {}
for chost in ALLCONFIG['hosts']['host']:
    if 'name' in chost:
        if chost['name'] == HTTP_HOST:
            #load the allhost keys 
            if 'allhost' in ALLCONFIG['hosts']:
                for k in ALLCONFIG['hosts']['allhost']:
                    CONFIG[k] = ALLCONFIG['hosts']['allhost'][k]
            #check for sameas
            if 'sameas' in chost:
                for shost in ALLCONFIG['hosts']['host']:
                    if shost['name'] == chost['sameas']:
                        for k in shost:
                            CONFIG[k] = shost[k]
            #load the host keys
            for k in chost:
                CONFIG[k] = chost[k]
            #load the database keys if specified
            if 'database' in CONFIG:
                dbkey = CONFIG['database']
                if dbkey in DATABASE:
                    for k in DATABASE[dbkey]:
                        CONFIG[k] = DATABASE[dbkey][k]
