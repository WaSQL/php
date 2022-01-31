#! python
"""
Installation
    python -m pip install pymssql
References
    https://docs.microsoft.com/en-us/sql/connect/python/pymssql/step-3-proof-of-concept-connecting-to-sql-using-pymssql?view=sql-server-ver15
    https://pythonhosted.org/pymssql/pymssql_examples.html


"""


#imports
try:
    import pymssql
    import config
    import common
except ImportError as err:
    sys.exit(err)
###########################################
def addIndex(params):
    #check required
    if '-table' not in params:
        return ("mssqldb.addIndex error: No Table Specified")
    if '-fields' not in params:
        return ("mssqldb.addIndex error: No Fields Specified")
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
            dbconfig['server'] = config.CONFIG['dbhost']

        if 'dbuser' in config.CONFIG:
            dbconfig['user'] = config.CONFIG['dbuser']

        if 'dbpass' in config.CONFIG:
            dbconfig['password'] = config.CONFIG['dbpass']

        if 'dbname' in config.CONFIG:
            dbconfig['database'] = config.CONFIG['dbname']

        #check params and override any that are passed in
        if 'dbhost' in params:
            dbconfig['server'] = params['dbhost']

        if 'dbuser' in params:
            dbconfig['user'] = params['dbuser']

        if 'dbpass' in params:
            dbconfig['password'] = params['dbpass']

        if 'dbname' in params:
            dbconfig['database'] = params['dbname']

        # Connect
        conn_mssql = pymssql.connect(**dbconfig)
        cur_mssql = conn_mssql.cursor(as_dict=True)
            
        #need to return both cur and conn so conn stays around
        return cur_mssql, conn_mssql
        
    except pymssql.Error as err:
        print("mssqldb.connect error: {}".format(err))
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
        cur_mssql, conn_mssql =  connect(params)
        #now execute the query
        cur_mssql.execute(query)
        return True
        
    except pymssql.Error as err:
        return ("mssqldb.executeSQL error: {}".format(err))
###########################################
def queryResults(query,params):
    try:
        #connect
        cur_mssql, conn_mssql =  connect(params)
        #now execute the query
        cur_mssql.execute(query)
        if 'filename' in params.keys():
            jsv_file=params['filename']
            #get column names
            fields = [field_md[0] for field_md in cur_mssql.description]
            #write file
            f = open(jsv_file, "w")
            f.write(json.dumps(fields,sort_keys=False, ensure_ascii=False, default=str).lower())
            #write records
            for rec in cur_mssql.fetchall():
                f.write(json.dumps(rec,sort_keys=False, ensure_ascii=True, default=convertStr))
            f.close()
            cur_mssql.close()
            conn_mssql.close()
            return params['filename']
        else:
            recs = cur_mssql.fetchall()
            tname=type(recs).__name__
            if tname == 'tuple':
                recs=list(recs)
                cur_mssql.close()
                conn_mssql.close()
                return recs
            elif tname == 'list':
                cur_mssql.close()
                conn_mssql.close()
                return recs
            else:
                cur_mssql.close()
                conn_mssql.close()
                return []

        
    except Exception as err:
        exc_type, exc_obj, exc_tb = sys.exc_info()
        fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
        cur_mssql.close()
        conn_mssql.close()
        return (f"Error: {err}. ExeptionType: {exc_type}, Filename: {fname}, Linenumber: {exc_tb.tb_lineno}")
###########################################
