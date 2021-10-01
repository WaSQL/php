#! python
"""
Installation
    python -m pip install sqlite3
References
    https://www.sqlitetutorial.net/sqlite-python/create-tables/
"""

#imports
try:
    import sqlite3
    from sqlite3 import Error
    import config
    import common
except ImportError as err:
    sys.exit(err)
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
        cur_sqlite = conn_sqlite.cursor()
            
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
def queryResults(query,params):
    try:
        #connect
        cur_sqlite, conn_sqlite =  connect(params)
        #now execute the query
        cur_sqlite.execute(query)
        #NOTE: columns names can be accessed by cur_sqlite.column_names
        recs = cur_sqlite.fetchall()
        #NOTE: get row count with cur_sqlite.rowcount
        if type(recs) in (tuple, list):
            return recs
        else:
            return []
        
    except Error as err:
        return ("sqlitedb.queryResults error: {}".format(err))
###########################################
