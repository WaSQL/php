#! python
'''
    config.py parses config.xml and builds CONFIG and ALLCONFIG
    References:
        https://www.guru99.com/manipulating-xml-with-python.html
    Installs needed
        pip install xmltodict
'''
#imports
import os
import sys
try:
    import xmltodict
except Exception as err:
    exc_type, exc_obj, exc_tb = sys.exc_info()
    fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
    print("Import Error: {}. ExeptionType: {}, Filename: {}, Linenumber: {}".format(err,exc_type,fname,exc_tb.tb_lineno))
    sys.exit(3)


mypath = os.path.dirname(os.path.realpath(__file__))
parpath = os.path.abspath(os.path.join(mypath, os.pardir))
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
    if type(db) == str:
        db = ALLCONFIG['hosts']['database'][db]
    key = db['name']
    DATABASE[key] = db

#CONFIG 
CONFIG = {}
for chost in ALLCONFIG['hosts']['host']:
    if type(chost) == str:
        chost = ALLCONFIG['hosts']['host'][chost]
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

#---------- begin function config.value
# @describe returns a specific key value or the whole config dict
# @param [str] string - key
# @return mixed - value for key if key is passed in, else returns the config dict
# @usage v=config.value('name')
# @usage c=config.value()
def value(k=''):
    if(k in CONFIG):
        return CONFIG[k]
    return CONFIG

#---------- begin function config.database
# @describe returns a specific key value or the whole DATABASE dict
# @param [str] string - key
# @return mixed - value for key if key is passed in, else returns the DATABASE dict
# @usage v=config.database('name')
# @usage c=config.database()
def database(k='',sk=''):
    if(k in DATABASE):
        if(sk in DATABASE[k]):
            return DATABASE[k][sk]
        return DATABASE[k]
    return DATABASE