#! python
"""
Installation
    python3 -m pip install hdbcli
References
    https://pypi.org/project/hdbcli/
    https://developers.sap.com/tutorials/hana-clients-python.html
"""

#imports
try:
    import json
    import sys
    import os
    import hdbcli
    from hdbcli import dbapi
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
        return ("hanadb.addIndex error: No Table Specified")
    if '-fields' not in params:
        return ("hanadb.addIndex error: No Fields Specified")
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
            dbconfig['address'] = config.CONFIG['dbhost']

        if 'dbuser' in config.CONFIG:
            dbconfig['user'] = config.CONFIG['dbuser']

        if 'dbpass' in config.CONFIG:
            dbconfig['password'] = config.CONFIG['dbpass']

        if 'dbport' in config.CONFIG:
            dbconfig['port'] = config.CONFIG['dbport']
        #check params and override any that are passed in
        if 'dbhost' in params:
            dbconfig['address'] = params['dbhost']

        if 'dbuser' in params:
            dbconfig['user'] = params['dbuser']

        if 'dbpass' in params:
            dbconfig['password'] = params['dbpass']

        if 'dbport' in params:
            dbconfig['port'] = params['dbport']
        # Connect
        conn_hana = dbapi.connect(**dbconfig)
        cur_hana = conn_hana.cursor(dictionary=True,buffered=True)
            
        #need to return both cur and conn so conn stays around
        return cur_hana, conn_hana
        
    except hdbcli.Error as err:
        print("hanadb.connect error: {}".format(err))
        return false
###########################################
def executeSQL(query,params):
    try:
        #connect
        cur_hana, conn_hana =  connect(params)
        #now execute the query
        cur_hana.execute(query)
        return True
        
    except hdbcli.Error as err:
        return ("hanadb.executeSQL error: {}".format(err))
###########################################
#conversion function to convert objects in recordsets
def convertStr(o):
    return f"{o}"
###########################################
def queryResults(query,params):
    try:
        #connect
        cur_hana, conn_hana =  connect(params)
        #now execute the query
        cur_hana.execute(query)
        if 'filename' in params.keys():
            jsv_file=params['filename']
            #get column names
            fields = [field_md[0] for field_md in cur_hana.description]
            #write file
            f = open(jsv_file, "w")
            f.write(json.dumps(fields,sort_keys=False, ensure_ascii=False, default=str).lower())
            f.write("\n")
            #write records
            for rec in cur_hana.fetchall():
                f.write(json.dumps(rec,sort_keys=False, ensure_ascii=True, default=convertStr))
                f.write("\n")
            f.close()
            cur_hana.close()
            conn_hana.close()
            return params['filename']
        else:
            recs = cur_hana.fetchall()
            tname=type(recs).__name__
            if tname == 'tuple':
                recs=list(recs)
                cur_hana.close()
                conn_hana.close()
                return recs
            elif tname == 'list':
                cur_hana.close()
                conn_hana.close()
                return recs
            else:
                cur_hana.close()
                conn_hana.close()
                return []

    except Exception as err:
        exc_type, exc_obj, exc_tb = sys.exc_info()
        fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
        cur_hana.close()
        conn_hana.close()
        return (f"Error: {err}. ExeptionType: {exc_type}, Filename: {fname}, Linenumber: {exc_tb.tb_lineno}")
###########################################
