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
        #add DATABASE settings to params
        for k in config.DATABASE[dbname]:
            params[k] = config.DATABASE[dbname][k]
        #HANA
        if config.DATABASE[dbname]['dbtype'].startswith('hana'):
            try:
                import hanadb
            except Exception as err:
                common.abort(sys.exc_info(),err)
            
            return hanadb.queryResults(query,params)
        #MSSQL
        if config.DATABASE[dbname]['dbtype'].startswith('mssql'):
            try:
                import mssqldb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return mssqldb.queryResults(query,params)
        #Mysql
        if config.DATABASE[dbname]['dbtype'].startswith('mysql'):
            try:
                import mysqldb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return mysqldb.queryResults(query,params)
        #ORACLE
        if config.DATABASE[dbname]['dbtype'].startswith('oracle'):
            try:
                import oracledb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return oracledb.queryResults(query,params)
        #SNOWFLAKE
        if config.DATABASE[dbname]['dbtype'].startswith('snowflake'):
            try:
                import snowflakedb
            except Exception as err:
                common.abort(sys.exc_info(),err)
            
            return snowflakedb.queryResults(query,params)
        #SQLITE
        if config.DATABASE[dbname]['dbtype'].startswith('sqlite'):
            try:
                import sqlitedb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return sqlitedb.queryResults(query,params)
        #POSTGRES
        if config.DATABASE[dbname]['dbtype'].startswith('postgre'):
            try:
                import postgresdb
            except Exception as err:
                common.abort(sys.exc_info(),err)

            return postgresdb.queryResults(query,params)