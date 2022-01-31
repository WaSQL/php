#! python
"""
Installation
    python3 -m pip install sqlite3
References
    https://www.sqlitetutorial.net/sqlite-python/create-tables/
"""

#imports
try:
    import json
    import sys
    import os
    import sqlite3
    from sqlite3 import Error
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
        return ("sqlitedb.addIndex error: No Table Specified")
    if '-fields' not in params:
        return ("sqlitedb.addIndex error: No Fields Specified")
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
        if 'dbname' in config.CONFIG:
            dbconfig['database'] = config.CONFIG['dbname']

        #check params and override any that are passed in
        if 'dbname' in params:
            dbconfig['database'] = params['dbname']

        # Connect
        conn_sqlite = sqlite3.connect(dbconfig['database'])
        conn_sqlite.row_factory = dictFactory
        conn_sqlite.text_factory = sqlite3.OptimizedUnicode
        cur_sqlite = conn_sqlite.cursor(buffered=True)
            
        #need to return both cur and conn so conn stays around
        return cur_sqlite, conn_sqlite
        
    except sqlite3.Error as err:
        print("sqlitedb.connect error: {}".format(err))
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
        cur_sqlite, conn_sqlite =  connect(params)
        #now execute the query
        cur_sqlite.execute(query)
        return True
        
    except Error as err:
        return ("sqlitedb.executeSQL error: {}".format(err))
###########################################
#conversion function to convert objects in recordsets
def convertStr(o):
    return f"{o}"
###########################################
def queryResults(query,params):
    try:
        #connect
        cur_sqlite, conn_sqlite =  connect(params)
        #now execute the query
        cur_sqlite.execute(query)
        if 'filename' in params.keys():
            jsv_file=params['filename']
            #get column names
            fields = [field_md[0] for field_md in cur_sqlite.description]
            #write file
            f = open(jsv_file, "w")
            f.write(json.dumps(fields,sort_keys=False, ensure_ascii=False, default=str).lower())
            f.write("\n")
            #write records
            for rec in cur_sqlite.fetchall():
                f.write(json.dumps(rec,sort_keys=False, ensure_ascii=True, default=convertStr))
                f.write("\n")
            f.close()
            cur_sqlite.close()
            conn_sqlite.close()
            return params['filename']
        else:
            recs = cur_sqlite.fetchall()
            tname=type(recs).__name__
            if tname == 'tuple':
                recs=list(recs)
                cur_sqlite.close()
                conn_sqlite.close()
                return recs
            elif tname == 'list':
                cur_sqlite.close()
                conn_sqlite.close()
                return recs
            else:
                cur_sqlite.close()
                conn_sqlite.close()
                return []
        
    except Exception as err:
        exc_type, exc_obj, exc_tb = sys.exc_info()
        fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
        cur_sqlite.close()
        conn_sqlite.close()
        return (f"Error: {err}. ExeptionType: {exc_type}, Filename: {fname}, Linenumber: {exc_tb.tb_lineno}")
###########################################
