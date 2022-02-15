#! python
"""
References
    https://www.php2python.com/

"""
import os
import sys
#import common.py
try:
    import common
    import requests
    from urllib.parse import urlparse, parse_qs, parse_qsl
    import config
    import db
    import re
    from importlib import import_module
    #common.echo("common imported")
except Exception as err:
    exc_type, exc_obj, exc_tb = sys.exc_info()
    fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
    print("Content-type: text/plain; charset=UTF-8;\n\n")
    print(f"Import Error: {err}. ExeptionType: {exc_type}, Filename: {fname}, Linenumber: {exc_tb.tb_lineno}")
    sys.exit(3)

#header
if not common.isCLI():
    print("Content-type: text/html; charset=UTF-8;\n\n")

#HTTP_HOST
if 'HTTP_HOST' in os.environ:
    HTTP_HOST = os.environ['HTTP_HOST']
else:
    HTTP_HOST = 'localhost'

#url
url = '//'+HTTP_HOST
if 'REDIRECT_URL' in os.environ:
    url+=os.environ['REDIRECT_URL']
    if 'QUERY_STRING' in os.environ:
        url+='?'+os.environ['QUERY_STRING']
else:
    url+=os.environ['REQUEST_URI']

# REQUEST setup
parsed_url = urlparse(url)
REQUEST = dict(parse_qsl(parsed_url.query))
#initial some global variables
PAGE = {}
TEMPLATE = {}
if '_view' not in REQUEST:
    REQUEST['_view']='index.py'

#view a page
if '_view' in REQUEST:
    view = REQUEST['_view']
    #build query using python3+ f strings
    query="select * from _pages where name='{}' or permalink='{}'".format(view,view);
    recs = db.queryResults(config.CONFIG['database'],query,{})
    if type(recs) in (tuple, list):
        for rec in recs:
            #SET PAGE
            for rk in rec:
                if isinstance(rec[rk],str):
                    PAGE[rk]=rec[rk].strip()
                else:
                    PAGE[rk]=rec[rk]
            #set common.VIEWS
            common.parseViews(rec['body'])
            body = rec['body']
            #check for functions - TODO: determine how to call these
            if 'functions' in rec and len(rec['functions']) > 0:
                compileString=''
                compileString = rec['functions'] + os.linesep + os.linesep
                #compile(source, filename, mode, flags=0, dont_inherit=False, optimize=-1)
                compiledCodeBlock = compile(compileString, '<string>', 'exec')
                eval(compiledCodeBlock)
            #check for controller
            if 'controller' in rec and len(rec['controller']) > 0:
                compileString=''
                compileString = rec['controller'] + os.linesep + os.linesep
                compiledCodeBlock = compile(compileString, '<string>', 'exec')
                eval(compiledCodeBlock)
            #process page views set
            if not bool(common.VIEW.keys):
                common.createView('default',rec['body'])

            for viewname in common.VIEW:
                rtn = common.parseCodeBlocks(common.VIEW[viewname]).strip()
                repstr="<view:{}>{}</view:{}>".format(viewname,common.VIEW[viewname],viewname)
                body=common.str_replace(repstr,rtn,body)
            #remove other views
            views = common.parseViewsOnly(body)
            for viewname in views:
                repstr="<view:{}>{}</view:{}>".format(viewname,views[viewname],viewname)
                body=common.str_replace(repstr,'',body)
            print(body)
            print(common.debugValues())
            break
    else:
        print(query)
else:
    print(REQUEST)