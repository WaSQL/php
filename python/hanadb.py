#! python
"""
Installation
    python -m pip install pyhdb
References
    https://github.com/SAP/PyHDB
"""

#imports
try:
    import pyhdb
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
        if 'dbhost' in config.CONFIG:
            dbconfig['host'] = config.CONFIG['dbhost']

        if 'dbuser' in config.CONFIG:
            dbconfig['user'] = config.CONFIG['dbuser']

        if 'dbpass' in config.CONFIG:
            dbconfig['password'] = config.CONFIG['dbpass']

        if 'dbport' in config.CONFIG:
            dbconfig['port'] = config.CONFIG['dbport']
        #check params and override any that are passed in
        if 'dbhost' in params:
            dbconfig['host'] = params['dbhost']

        if 'dbuser' in params:
            dbconfig['user'] = params['dbuser']

        if 'dbpass' in params:
            dbconfig['password'] = params['dbpass']

        if 'dbport' in params:
            dbconfig['port'] = params['dbport']
        # Connect
        conn_hana = pyhdb.connect(**dbconfig)
        cur_hana = conn_hana.cursor(dictionary=True)
            
        #need to return both cur and conn so conn stays around
        return cur_hana, conn_hana
        
    except pyhdb.Error as err:
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
        
    except pyhdb.Error as err:
        return ("hanadb.executeSQL error: {}".format(err))
###########################################
def queryResults(query,params):
    try:
        #connect
        cur_hana, conn_hana =  connect(params)
        #now execute the query
        cur_hana.execute(query)
        #NOTE: columns names can be accessed by cur_hana.column_names
        recs = cur_hana.fetchall()
        #NOTE: get row count with cur_hana.rowcount
        if type(recs) in (tuple, list):
            return recs
        else:
            return []
        
    except hana.Error as err:
        return ("hanadb.queryResults error: {}".format(err))
###########################################
