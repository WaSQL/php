#! python
"""
Installation
    python3 -m pip install fdb
References
    https://firebird-driver.readthedocs.io/en/latest/getting-started.html#quick-start-guide
    https://firebirdsql.org/file/documentation/html/en/firebirddocs/qsg3/firebird-3-quickstartguide.html

Notes:
    
"""


#imports
import os
import sys
try:
    import json
    import fdb
    import config
    import common
except Exception as err:
    exc_type, exc_obj, exc_tb = sys.exc_info()
    fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
    print("Import Error: {}. ExeptionType: {}, Filename: {}, Linenumber: {}".format(err,exc_type,fname,exc_tb.tb_lineno))
    sys.exit(3)

#---------- begin function connect ----------
# @describe returns a database connection
# @param params tuple - parameters to override
# @return 
#   cur_mssql, conn_mssql array
# @usage 
#   cur_mssql, conn_mssql =  firebirddb.connect(params)
def connect(params):
    dbconfig = {
        "charset":"UTF8"
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
        conn_firebird = fdb.connect(**dbconfig)
    except Exception as err:
        common.abort(sys.exc_info(),err)

    try:
        cur_firebird = conn_firebird.cursor()
    except Exception as err:
        common.abort(sys.exc_info(),err) 

    return cur_firebird, conn_firebird

#---------- begin function executeSQL ----------
# @describe executes a query
# @param query str - SQL query to run
# @param params tuple - parameters to override
# @return 
#   boolean
# @usage 
#   ok =  firebirddb.executeSQL(query,params)
def executeSQL(query,params):
    try:
        #connect
        cur_firebird, conn_firebird =  connect(params)
        #now execute the query
        cur_firebird.execute(query)
        return True
        
    except Exception as err:
        cur_firebird.close()
        conn_firebird.close()
        return common.debug(sys.exc_info(),err)

#---------- begin function convertStr ----------
# @describe convert objects in recordsets to string
# @param o object
# @return 
#   str string
# @usage 
#   str =  firebirddb.convertStr(o)
def convertStr(o):
    return "{}".format(o)

#---------- begin function queryResults ----------
# @describe executes a query and returns list of records
# @param query str - SQL query to run
# @param params tuple - parameters to override
# @return 
#   recordsets list
# @usage 
#   recs =  firebirddb.queryResults(query,params)
def queryResults(query,params):
    try:
        #connect
        cur_firebird, conn_firebird =  connect(params)
        #now execute the query
        cur_firebird.execute(query)
        
        if 'filename' in params.keys():
            jsv_file=params['filename']
            #get column names
            fields = [field_md[0] for field_md in cur_firebird.description]
            #write file
            f = open(jsv_file, "w")
            f.write(json.dumps(fields,sort_keys=False, ensure_ascii=True, default=convertStr).lower())
            f.write("\n")
            #write records
            for rec in cur_firebird.fetchall():
                #convert to a dictionary manually since it is not built into the driver
                rec=dict(zip(fields, rec))
                #lowercase key names
                rec = {k.lower(): v for k, v in rec.items()}
                f.write(json.dumps(rec,sort_keys=False, ensure_ascii=True, default=convertStr))
                f.write("\n")
            f.close()
            cur_firebird.close()
            conn_firebird.close()
            return params['filename']
        else:
            recs = cur_firebird.fetchall()
            tname=type(recs).__name__
            if tname == 'tuple':
                recs=list(recs)
                cur_firebird.close()
                conn_firebird.close()
                return recs
            elif tname == 'list':
                cur_firebird.close()
                conn_firebird.close()
                return recs
            else:
                cur_firebird.close()
                conn_firebird.close()
                return []

    except Exception as err:
        cur_firebird.close()
        conn_firebird.close()
        return common.debug(sys.exc_info(),err)
          
###########################################