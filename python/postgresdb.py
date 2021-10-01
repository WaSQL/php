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
    import psycopg2
    from psycopg2 import pool
    import psycopg2.extras
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
        #NOTE: columns names can be accessed by cur_postgres.column_names
        recs = cur_postgres.fetchall()
        #NOTE: get row count with cur_postgres.rowcount
        if type(recs) in (tuple, list):
            return recs
        else:
            return []
        
    except psycopg2.Error as err:
        return ("postgresdb.queryResults error: {}".format(err))
###########################################
