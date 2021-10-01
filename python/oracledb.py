#! python
"""
Installation
    python -m pip install cx_Oracle
References
    https://cx-oracle.readthedocs.io/en/latest/user_guide/connection_handling.html#connpool
    https://cx-oracle.readthedocs.io/en/latest/user_guide/connection_handling.html
"""


#imports
try:
    import sys
    import cx_Oracle
    from cx_Oracle import Error
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
        pconfig = {
            'min':2,
            'max':10,
            'increment':1,
            'encoding':'UTF-8'
        }
        dbconfig = {}
        dbconfig['port'] = 1521
        dbconfig['host'] = 'localhost'
        #check config.CONFIG
        if 'dbconnect' in config.CONFIG:
            dbconfig['connect'] = config.CONFIG['dbconnect']
        elif 'connect' in config.CONFIG:
            dbconfig['connect'] = config.CONFIG['connect']

        if 'dbport' in config.CONFIG:
            dbconfig['port'] = config.CONFIG['dbport']
        elif 'port' in config.CONFIG:
            dbconfig['port'] = config.CONFIG['port']

        if 'dbhost' in config.CONFIG:
            dbconfig['host'] = config.CONFIG['dbhost']

        if 'dbuser' in config.CONFIG:
            dbconfig['user'] = config.CONFIG['dbuser']

        if 'dbpass' in config.CONFIG:
            dbconfig['password'] = config.CONFIG['dbpass']

        if 'dbname' in config.CONFIG:
            dbconfig['database'] = config.CONFIG['dbname']

        if 'sid' in config.CONFIG:
            dbconfig['service_name'] = config.CONFIG['sid']
        elif 'service_name' in config.CONFIG:
            dbconfig['service_name'] = config.CONFIG['service_name']

        if 'dbserver' in config.CONFIG:
            dbconfig['server'] = config.CONFIG['dbserver']
        elif 'server' in config.CONFIG:
            dbconfig['server'] = config.CONFIG['server']

        #check params and override any that are passed in
        if 'dbconnect' in params:
            dbconfig['connect'] = params['dbconnect']
        elif 'connect' in params:
            dbconfig['connect'] = params['connect']

        if 'dbport' in params:
            dbconfig['port'] = params['dbport']
        elif 'port' in params:
            dbconfig['port'] = params['port']

        if 'dbhost' in params:
            dbconfig['host'] = params['dbhost']

        if 'dbuser' in params:
            dbconfig['user'] = params['dbuser']

        if 'dbpass' in params:
            dbconfig['password'] = params['dbpass']

        if 'dbname' in params:
            dbconfig['database'] = params['dbname']

        if 'sid' in params:
            dbconfig['service_name'] = params['sid']
        elif 'service_name' in params:
            dbconfig['service_name'] = params['service_name']

        if 'dbserver' in params:
            dbconfig['server'] = params['dbserver']
        elif 'server' in params:
            dbconfig['server'] = params['server']

        #create a dsn object
        dsnconfig={}
        if 'service_name' in dbconfig:
            dsnconfig['service_name'] = dbconfig['service_name']

        dsn = cx_Oracle.makedsn(dbconfig['host'],dbconfig['port'],**dsnconfig)
        #setup connection config
        cconfig = {}
        if 'user' in dbconfig:
            cconfig['user'] = dbconfig['user']
        if 'password' in dbconfig:
            cconfig['password'] = dbconfig['password']
        cconfig['dsn'] = dsn
        #setup the connection pool
        if 'user' in dbconfig:
            pconfig['user'] = dbconfig['user']
        if 'password' in dbconfig:
            pconfig['password'] = dbconfig['password']
        pconfig['dsn'] = dsn
        pool_oracle = cx_Oracle.SessionPool(**pconfig)

        # Get connection object from a pool if possible, otherwise just connect
        conn_oracle = pool_oracle.acquire()
        if conn_oracle:
            cur_oracle = conn_oracle.cursor()
        else:
            conn_oracle = cx_Oracle.connect(**cconfig)
            cur_oracle = conn_oracle.cursor()
        cur_oracle.rowfactory = dictFactory
        #need to return both cur and conn so conn stays around
        return cur_oracle, conn_oracle
        
    except cx_Oracle.Error as err:
        print("oracledb.connect error: {}".format(err))
        return False
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
        cur_oracle, conn_oracle =  connect(params)
        #now execute the query
        cur_oracle.execute(query)
        return True
        
    except cx_Oracle.Error as err:
        return ("oracledb.executeSQL error: {}".format(err))
###########################################
def queryResults(query,params):
    try:
        #connect
        cur_oracle, conn_oracle =  connect(params)
        #now execute the query
        cur_oracle.execute(query)
        #NOTE: columns names can be accessed by cur_oracle.column_names
        recs = cur_oracle.fetchall()
        #NOTE: get row count with cur_oracle.rowcount
        if type(recs) in (tuple, list):
            return recs
        else:
            return []
        
    except cx_Oracle.Error as err:
        return ("oracledb.queryResults error: {}".format(err))
###########################################
