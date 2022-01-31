#! python
"""
Installation
    python -m pip install psycopg2-binary
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
    from psycopg2 import pool
    import psycopg2.extras
    import config
    import common
except Exception as err:
    exc_type, exc_obj, exc_tb = sys.exc_info()
    fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
    print(f"Import Error: {err}. ExeptionType: {exc_type}, Filename: {fname}, Linenumber: {exc_tb.tb_lineno}")
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
    try:
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
        #setup the connection pool
        pool_postgres = psycopg2.pool.SimpleConnectionPool(1,20,**dbconfig)

        # Get connection object from a pool if possible, otherwise just connect
        conn_postgres = pool_postgres.getconn()
        if conn_postgres:
            cur_postgres = conn_postgres.cursor(cursor_factory=dictFactory)
        else:
            conn_postgres = psycopg2.connect(**dbconfig)
            cur_postgres = conn_postgres.cursor(cursor_factory=dictFactory)
        #need to return both cur and conn so conn stays around
        return cur_postgres, conn_postgres
        
    except psycopg2.Error as err:
        print("postgresdb.connect error: {}".format(err))
        return false
###########################################
def dictFactory(cursor, row):
    d = {}
    for idx, col in enumerate(cursor.description):
        d[col[0]] = row[idx]
    return d
###########################################
def executeSQL(query,params):
    try:
        #connect
        cur_postgres, conn_postgres =  connect(params)
        #now execute the query
        cur_postgres.execute(query)
        return True
        
    except psycopg2.Error as err:
        return ("postgresdb.executeSQL error: {}".format(err))
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
            f.write(json.dumps(fields,sort_keys=False, ensure_ascii=False, default=str).lower())
            #write records
            for rec in cur_postgres.fetchall():
                f.write(json.dumps(rec,sort_keys=False, ensure_ascii=True, default=convertStr))
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
        exc_type, exc_obj, exc_tb = sys.exc_info()
        fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
        cur_postgres.close()
        conn_postgres.close()
        return (f"Error: {err}. ExeptionType: {exc_type}, Filename: {fname}, Linenumber: {exc_tb.tb_lineno}")
###########################################
