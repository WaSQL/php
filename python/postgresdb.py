#! python
# -*- coding: utf-8 -*-
"""
Installation
    python3 -m pip install psycopg2-binary
       If it errors try these first
           python -m pip install -U setuptools
           python -m pip install -U wheel
           then try again
References
    https://www.psycopg.org/docs/
    https://pynative.com/psycopg2-python-postgresql-connection-pooling/
"""


#imports
try:
    import json
    import sys
    import os
    import psycopg2
    import psycopg2.extras
    import config
    import common
except Exception as err:
    exc_type, exc_obj, exc_tb = sys.exc_info()
    fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
    print(f"Error: {err}\nFilename: {fname}\nLinenumber: {exc_tb.tb_lineno}")
    sys.exit()
###########################################
def addIndex(params):
    #check required
    if '-table' not in params:
        return ("postgresdb.addIndex error: No Table Specified")
    if '-fields' not in params:
        return ("postgresdb.addIndex error: No Fields Specified")
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
    dbconfig = {}
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
        conn_postgres = psycopg2.connect(**dbconfig)
    except Exception as err:
        common.abort(sys.exc_info(),err)

    try:
        cur_postgres = conn_postgres.cursor(cursor_factory=psycopg2.extras.DictCursor)
    except Exception as err:
        common.abort(sys.exc_info(),err)

    return cur_postgres, conn_postgres
       
###########################################
def executeSQL(query,params):
    try:
        #connect
        cur_postgres, conn_postgres =  connect(params)
        #now execute the query
        cur_postgres.execute(query)
        return True
    except Exception as err:
        exc_type, exc_obj, exc_tb = sys.exc_info()
        cur_postgres.close()
        conn_postgres.close()
        return common.debug(sys.exc_info(),err)

###########################################
#conversion function to convert objects in recordsets
def convertStr(o):
    return f"{o}"

###########################################
def queryResults(query,params):
    try:
        #connect
        cur_postgres, conn_postgres =  connect(params)

        #now execute the query
        cur_postgres.execute(query)
        if 'filename' in params.keys():
            jsv_file=params['filename']
            #get column names
            fields = [field_md[0] for field_md in cur_postgres.description]
            #write file
            f = open(jsv_file, "w")
            f.write(json.dumps(fields,sort_keys=False, ensure_ascii=True, default=convertStr).lower())
            f.write("\n")
            #write records
            for rec in cur_postgres.fetchall():
                f.write(json.dumps(rec,sort_keys=False, ensure_ascii=True, default=convertStr))
                f.write("\n")
            f.close()
            cur_postgres.close()
            conn_postgres.close()
            return params['filename']
        else:
            recs = cur_postgres.fetchall()
            tname=type(recs).__name__
            if tname == 'tuple':
                recs=list(recs)
                cur_postgres.close()
                conn_postgres.close()
                return recs
            elif tname == 'list':
                cur_postgres.close()
                conn_postgres.close()
                return recs
            else:
                cur_postgres.close()
                conn_postgres.close()
                return []
        
    except Exception as err:
        cur_postgres.close()
        conn_postgres.close()
        return common.debug(sys.exc_info(),err)
###########################################
