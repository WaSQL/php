#! python
"""
Installation
    python3 -m pip install mysql.connector-python
       If it errors try these first
           python -m pip install -U setuptools
           python -m pip install -U wheel
References
    https://dev.mysql.com/doc/connector-python/en/connector-python-cext-reference.html
"""


#imports
import os
import sys
try:
    import json
    import mysql.connector
    import config
    import common
except Exception as err:
    exc_type, exc_obj, exc_tb = sys.exc_info()
    fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
    print(f"Error: {err}\nFilename: {fname}\nLinenumber: {exc_tb.tb_lineno}")
    sys.exit(3)
###########################################
def addIndex(params):
    #check required
    if '-table' not in params:
        return ("mysqldb.addIndex error: No Table Specified")
    if '-fields' not in params:
        return ("mysqldb.addIndex error: No Fields Specified")
    #check for unique and fulltext
    fulltext = ''
    unique = ''
    prefix = ''
    if '-unique' in params:
        unique =' UNIQUE'
        prefix += 'U'
    if '-fulltext' in params:
        fulltext =' FULLTEXT'
        prefix += 'F'
    #build index name if not passed in
    if '-name' not in params:
        params['-name']="{}_{}_".format(prefix,params['-table']);
    #create query
    fieldstr = params['-fields'].replace(',','_')
    query="CREATE {} INDEX IF NOT EXISTS {} on {} ({})".format(unique,params['-name'],params['-table'],fieldstr);
    #execute query
    return executeSQL(query)    
###########################################
#Python’s default arguments are evaluated once when the function is defined, not each time the function is called.
def connect(params):
    dbconfig = {
        'auth_plugin':'mysql_native_password'
    }
    #check config.CONFIG
    if 'dbhost' in config.CONFIG:
        dbconfig['host'] = config.CONFIG['dbhost']

    if 'dbuser' in config.CONFIG:
        dbconfig['user'] = config.CONFIG['dbuser']

    if 'dbpass' in config.CONFIG:
        dbconfig['password'] = config.CONFIG['dbpass']

    if 'dbname' in config.CONFIG:
        dbconfig['database'] = config.CONFIG['dbname']
    #check params and override any that are passed in
    if 'dbhost' in params:
        dbconfig['host'] = params['dbhost']

    if 'dbuser' in params:
        dbconfig['user'] = params['dbuser']

    if 'dbpass' in params:
        dbconfig['password'] = params['dbpass']

    if 'dbname' in params:
        dbconfig['database'] = params['dbname']

    try:
        conn_mysql = mysql.connector.connect(**dbconfig)
    except Exception as err:
        common.abort(sys.exc_info(),err)

    try:
        cur_mysql = conn_mysql.cursor(dictionary=True,buffered=True)
    except Exception as err:
        common.abort(sys.exc_info(),err) 

    return cur_mysql, conn_mysql

###########################################
def executeSQL(query,params):
    try:
        #connect
        cur_mysql, conn_mysql =  connect(params)
        #now execute the query
        cur_mysql.execute(query)
        return True
        
    except Exception as err:
        cur_mysql.close()
        conn_mysql.close()
        return common.debug(sys.exc_info(),err)

###########################################
#conversion function to convert objects in recordsets
def convertStr(o):
    return f"{o}"
###########################################
def queryResults(query,params):
    try:
        #connect
        cur_mysql, conn_mysql =  connect(params)
        #now execute the query
        cur_mysql.execute(query)
        
        if 'filename' in params.keys():
            jsv_file=params['filename']
            #get column names
            fields = [field_md[0] for field_md in cur_mysql.description]
            #write file
            f = open(jsv_file, "w")
            f.write(json.dumps(fields,sort_keys=False, ensure_ascii=True, default=convertStr).lower())
            f.write("\n")
            #write records
            for rec in cur_mysql.fetchall():
                f.write(json.dumps(rec,sort_keys=False, ensure_ascii=True, default=convertStr))
                f.write("\n")
            f.close()
            cur_mysql.close()
            conn_mysql.close()
            return params['filename']
        else:
            recs = cur_mysql.fetchall()
            tname=type(recs).__name__
            if tname == 'tuple':
                recs=list(recs)
                cur_mysql.close()
                conn_mysql.close()
                return recs
            elif tname == 'list':
                cur_mysql.close()
                conn_mysql.close()
                return recs
            else:
                cur_mysql.close()
                conn_mysql.close()
                return []

    except Exception as err:
        cur_mysql.close()
        conn_mysql.close()
        return common.debug(sys.exc_info(),err)
          
###########################################