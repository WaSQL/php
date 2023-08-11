#! python
#imports
import os
import sys
try:
    import config
    import common
except Exception as err:
    exc_type, exc_obj, exc_tb = sys.exc_info()
    fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
    print("Import Error: {}. ExeptionType: {}, Filename: {}, Linenumber: {}".format(err,exc_type,fname,exc_tb.tb_lineno))
    sys.exit(3)

#---------- begin function queryResults ----------
# @describe returns a dictionary of records returned from query
# @param dbname str - database name from database tag in config.xml
# @param query str - SQL query to run
# @return dictionary - recordsets
# @usage recs=db.queryResults('dbtest','select * from states')
def queryResults(dbname,query,params={}):
    if dbname in config.DATABASE:
        dbtype=config.DATABASE[dbname]['dbtype']
        #add DATABASE settings to params
        for k in config.DATABASE[dbname]:
            params[k] = config.DATABASE[dbname][k]
        #HANA
        if dbtype.startswith('hana'):
            try:
                import hanadb
            except Exception as err:
                common.abort(sys.exc_info(),err)
            
            return hanadb.queryResults(query,params)
        #MSSQL
        if dbtype.startswith('mssql'):
            try:
                import mssqldb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return mssqldb.queryResults(query,params)
        #Mysql
        if dbtype.startswith('mysql'):
            try:
                import mysqldb
            except Exception as err:
                common.abort(sys.exc_info(),err)
                
            return mysqldb.queryResults(query,params)
        #ORACLE
        if dbtype.startswith('oracle'):
            try:
                import oracledb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return oracledb.queryResults(query,params)
        #SNOWFLAKE
        if dbtype.startswith('snowflake'):
            try:
                import snowflakedb
            except Exception as err:
                common.abort(sys.exc_info(),err)
            
            return snowflakedb.queryResults(query,params)
        #SQLITE
        if dbtype.startswith('sqlite'):
            try:
                import sqlitedb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return sqlitedb.queryResults(query,params)
        #CTREE
        if dbtype.startswith('ctree'):
            try:
                import ctreedb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return ctreedb.queryResults(query,params)
        #POSTGRES
        if dbtype.startswith('postgre'):
            try:
                import postgresdb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return postgresdb.queryResults(query,params)
        #MSACCESS
        if dbtype.startswith('msaccess'):
            try:
                import msaccessdb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return msaccessdb.queryResults(query,params)
        #MSCSV
        if dbtype.startswith('mscsv'):
            try:
                import mscsvdb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return mscsvdb.queryResults(query,params)
        #MSEXCEL
        if dbtype.startswith('msexcel'):
            try:
                import msexceldb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return msexceldb.queryResults(query,params)

#---------- begin function executeSQL ----------
# @describe returns a dictionary of records returned from query
# @param dbname str - database name from database tag in config.xml
# @param query str - SQL query to run
# @return dictionary - recordsets
# @usage recs=db.executeSQL('dbtest','select * from states')
def executeSQL(dbname,query,params={}):
    if dbname in config.DATABASE:
        #add DATABASE settings to params
        for k in config.DATABASE[dbname]:
            params[k] = config.DATABASE[dbname][k]
        #HANA
        if config.DATABASE[dbname]['dbtype'].startswith('hana'):
            try:
                import hanadb
            except Exception as err:
                common.abort(sys.exc_info(),err)
            
            return hanadb.executeSQL(query,params)
        #MSSQL
        if config.DATABASE[dbname]['dbtype'].startswith('mssql'):
            try:
                import mssqldb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return mssqldb.executeSQL(query,params)
        #Mysql
        if config.DATABASE[dbname]['dbtype'].startswith('mysql'):
            try:
                import mysqldb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return mysqldb.executeSQL(query,params)
        #ORACLE
        if config.DATABASE[dbname]['dbtype'].startswith('oracle'):
            try:
                import oracledb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return oracledb.executeSQL(query,params)
        #SNOWFLAKE
        if config.DATABASE[dbname]['dbtype'].startswith('snowflake'):
            try:
                import snowflakedb
            except Exception as err:
                common.abort(sys.exc_info(),err)
            
            return snowflakedb.executeSQL(query,params)
        #SQLITE
        if config.DATABASE[dbname]['dbtype'].startswith('sqlite'):
            try:
                import sqlitedb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return sqlitedb.executeSQL(query,params)
        #CTREE
        if config.DATABASE[dbname]['dbtype'].startswith('ctree'):
            try:
                import ctreedb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return ctreedb.executeSQL(query,params)
        #POSTGRES
        if config.DATABASE[dbname]['dbtype'].startswith('postgre'):
            try:
                import postgresdb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return postgresdb.executeSQL(query,params)

#---------- begin function executePS ----------
# @describe returns a dictionary of records returned from query
# @param dbname str - database name from database tag in config.xml
# @param query str - SQL query to run
# @return dictionary - recordsets
# @usage recs=db.executePS('dbtest','select * from states')
def executePS(dbname,query,params={}):
    if dbname in config.DATABASE:
        #HANA
        if config.DATABASE[dbname]['dbtype'].startswith('hana'):
            try:
                import hanadb
            except Exception as err:
                common.abort(sys.exc_info(),err)
            
            return hanadb.executePS(query,params)
        #MSSQL
        if config.DATABASE[dbname]['dbtype'].startswith('mssql'):
            try:
                import mssqldb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return mssqldb.executePS(query,params)
        #Mysql
        if config.DATABASE[dbname]['dbtype'].startswith('mysql'):
            try:
                import mysqldb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return mysqldb.executePS(query,params)
        #ORACLE
        if config.DATABASE[dbname]['dbtype'].startswith('oracle'):
            try:
                import oracledb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return oracledb.executePS(query,params)
        #SNOWFLAKE
        if config.DATABASE[dbname]['dbtype'].startswith('snowflake'):
            try:
                import snowflakedb
            except Exception as err:
                common.abort(sys.exc_info(),err)
            
            return snowflakedb.executePS(query,params)
        #SQLITE
        if config.DATABASE[dbname]['dbtype'].startswith('sqlite'):
            try:
                import sqlitedb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return sqlitedb.executePS(query,params)
        #CTREE
        if config.DATABASE[dbname]['dbtype'].startswith('ctree'):
            try:
                import ctreedb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return ctreedb.executePS(query,params)
        #POSTGRES
        if config.DATABASE[dbname]['dbtype'].startswith('postgre'):
            try:
                import postgresdb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return postgresdb.executePS(query,params)

#---------- begin function connect ----------
# @describe returns an object with db connection and cursor
# @param dbname str - database name from database tag in config.xml
# @return object - connection details
# @usage cursor, conn, cursor=db.connect('dbtest')
def connect(dbname,params={}):
    if dbname in config.DATABASE:
        dbtype=config.DATABASE[dbname]['dbtype'].lower()
        #add DATABASE settings to params
        for k in config.DATABASE[dbname]:
            params[k] = config.DATABASE[dbname][k]
        #HANA
        if dbtype.startswith('hana'):
            try:
                import hanadb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return hanadb.connect(params)
        #MSSQL
        if dbtype.startswith('mssql'):
            try:
                import mssqldb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return mssqldb.connect(params)
        #Mysql
        if dbtype.startswith('mysql'):
            try:
                import mysqldb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return mysqldb.connect(params)
        #ORACLE
        if dbtype.startswith('oracle'):
            try:
                import oracledb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return oracledb.connect(params)
        #SNOWFLAKE
        if dbtype.startswith('snowflake'):
            try:
                import snowflakedb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return snowflakedb.connect(params)
        #SQLITE
        if dbtype.startswith('sqlite'):
            try:
                import sqlitedb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return sqlitedb.connect(params)
        #CTREE
        if dbtype.startswith('ctree'):
            try:
                import ctreedb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return ctreedb.connect(params)
        #POSTGRES
        if dbtype.startswith('postgre'):
            try:
                import postgresdb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return postgresdb.connect(params)
        #MSACCESS
        if dbtype.startswith('msaccess'):
            try:
                import msaccessdb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return msaccessdb.connect(params)
        #MSCSV
        if dbtype.startswith('mscsv'):
            try:
                import mscsvdb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return mscsvdb.connect(params)
        #MSEXCEL
        if dbtype.startswith('msexcel'):
            try:
                import msexceldb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return msexceldb.connect(params)

#---------- begin function convertStr ----------
# @describe returns a string from an object
# @param o object - object to convert
# @return str string 
# @usage db.convertStr(o)
def convertStr(o):
    return "{}".format(o)