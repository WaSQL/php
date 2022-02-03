#! python
"""
Installation
    python -m pip install --upgrade snowflake-connector-python
       If it fails then go to https://visualstudio.microsoft.com/visual-cpp-build-tools/
           download build tools
           install c++ build tools
           reboot and try again
    python -m pip install snowflake-sqlalchemy

"""
#imports
try:
    import json
    import sys
    import os
    import snowflake.connector as sfc
    from sqlalchemy import create_engine
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


    dbconfig = {}
    #need account,user,password,database,schema,warehouse,role

    #check config.CONFIG
    if 'dbaccount' in config.CONFIG:
        dbconfig['account'] = config.CONFIG['dbaccount'].replace(".snowflakecomputing.com","",1)
    elif 'dbhost' in config.CONFIG:
        dbconfig['account'] = config.CONFIG['dbhost'].replace(".snowflakecomputing.com","",1)

    if 'dbuser' in config.CONFIG:
        dbconfig['user'] = config.CONFIG['dbuser']

    if 'dbpass' in config.CONFIG:
        dbconfig['password'] = config.CONFIG['dbpass']

    if 'dbname' in config.CONFIG:
        dbconfig['database'] = config.CONFIG['dbname']

    if 'dbschema' in config.CONFIG:
        dbconfig['schema'] = config.CONFIG['dbschema']

    if 'dbwarehouse' in config.CONFIG:
        dbconfig['warehouse'] = config.CONFIG['dbwarehouse']

    if 'dbrole' in config.CONFIG:
        dbconfig['role'] = config.CONFIG['dbrole']
    
    #check params and override any that are passed in
    if 'dbaccount' in params:
        dbconfig['account'] = params['dbaccount'].replace(".snowflakecomputing.com","",1)
    elif 'dbhost' in params:
        dbconfig['account'] = params['dbhost'].replace(".snowflakecomputing.com","",1)

    if 'dbuser' in params:
        dbconfig['user'] = params['dbuser']

    if 'dbpass' in params:
        dbconfig['password'] = params['dbpass']

    if 'dbname' in params:
        dbconfig['database'] = params['dbname']

    if 'dbschema' in params:
        dbconfig['schema'] = params['dbschema']

    if 'dbwarehouse' in params:
        dbconfig['warehouse'] = params['dbwarehouse']

    if 'dbrole' in params:
        dbconfig['role'] = params['dbrole']

    try:
        conn_snowflake = sfc.connect(**dbconfig)
    except Exception as err:
        conn_snowflake=None

    if conn_snowflake != None:
        try:
            cur_snowflake = conn_snowflake.cursor()
        except Exception as err:
            cur_snowflake=None

        if cur_snowflake != None:
            return cur_snowflake, conn_snowflake
        
    exc_type, exc_obj, exc_tb = sys.exc_info()
    fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
    print(f"Error: {err}\nFilename: {fname}\nLinenumber: {exc_tb.tb_lineno}")
    sys.exit()

###########################################
def executeSQL(query,params):
    try:
        #connect
        cur_snowflake, conn_snowflake =  connect(params)
        #now execute the query
        cur_snowflake.execute(query)
        return True
        
    except Exception as err:
        exc_type, exc_obj, exc_tb = sys.exc_info()
        fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
        return f"Error: {err}\nFilename: {fname}\nLinenumber: {exc_tb.tb_lineno}"

###########################################
#conversion function to convert objects in recordsets
def convertStr(o):
    return f"{o}"

###########################################
def queryResults(query,params):
    try:
        #connect
        cur_snowflake, conn_snowflake =  connect(params)

        #now execute the query
        cur_snowflake.execute(query)
        if 'filename' in params.keys():
            jsv_file=params['filename']
            #get column names
            fields = [field_md[0] for field_md in cur_snowflake.description]
            #write file
            f = open(jsv_file, "w")
            f.write(json.dumps(fields,sort_keys=False, ensure_ascii=True, default=convertStr).lower())
            f.write("\n")
            #write records
            for rec in cur_snowflake.fetchall():
                #convert to a dictionary manually since it is not built into the driver
                rec=dict(zip(fields, rec))
                f.write(json.dumps(rec,sort_keys=False, ensure_ascii=True, default=convertStr))
                f.write("\n")
            f.close()
            cur_snowflake.close()
            conn_snowflake.close()
            return params['filename']
        else:
            recs = cur_snowflake.fetchall()
            tname=type(recs).__name__
            if tname == 'tuple':
                recs=list(recs)
                cur_snowflake.close()
                conn_snowflake.close()
                return recs
            elif tname == 'list':
                cur_snowflake.close()
                conn_snowflake.close()
                return recs
            else:
                cur_snowflake.close()
                conn_snowflake.close()
                return []
        
    except Exception as err:
        exc_type, exc_obj, exc_tb = sys.exc_info()
        fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
        cur_snowflake.close()
        conn_snowflake.close()
        return (f"Error: {err}. ExeptionType: {exc_type}, Filename: {fname}, Linenumber: {exc_tb.tb_lineno}")
###########################################
