#! python
"""
Converts a database results to jsv (json separated value)
    ["_id", "_cdate", "_cuser", "_edate", "_euser", "code", "name", "country", "longitude", "latitude"]
    {"_id": 4892, "_cdate": null, "_cuser": 0, "_edate": null, "_euser": null, "code": "MI", "name": "Midlands Province", "country": "ZW", "longitude": "29.60354950", "latitude": "-19.05520090"}

Usage:
    python3 db2jsv.py dbname sqlfile

"""
#imports
import os
import sys
try:
    import json
    import sys
    import os
    import config
    import common
except Exception as err:
    exc_type, exc_obj, exc_tb = sys.exc_info()
    fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
    print(f"Import Error: {err}. ExeptionType: {exc_type}, Filename: {fname}, Linenumber: {exc_tb.tb_lineno}")
    sys.exit(3)

#get query_file from command line arg
if(len(sys.argv) < 3):
    print("db2jsv usage: python db2jsv dbname sqlfile")
    exit(2)

sys.stdout.flush()
sys.stderr.flush()

dbname=sys.argv[1]

#make sure the file exists
if(os.path.exists(sys.argv[2]) == False):
    print('file does not exists')
    print(sys.argv[1])
    exit()

#get query from file
sql_file=sys.argv[2]
f = open(sql_file, "r")
#read whole file to a string
query = f.read()
#close file
f.close()
params={}
params['filename']=sql_file.replace('.sql','.jsv')
outfile=''
try:
    if dbname in config.DATABASE:
        #add DATABASE settings to params
        for k in config.DATABASE[dbname]:
            params[k] = config.DATABASE[dbname][k]
        #HANA
        if config.DATABASE[dbname]['dbtype'].startswith('hana'):
            import hanadb
            outfile=hanadb.queryResults(query,params)
        #MSSQL
        if config.DATABASE[dbname]['dbtype'].startswith('mssql'):
            import mssqldb
            outfile=mssqldb.queryResults(query,params)
        #Mysql
        if config.DATABASE[dbname]['dbtype'].startswith('mysql'):
            import mysqldb
            outfile=mysqldb.queryResults(query,params)
        #ORACLE
        if config.DATABASE[dbname]['dbtype'].startswith('oracle'):
            import oracledb
            outfile=oracledb.queryResults(query,params)
        #SNOWFLAKE
        if config.DATABASE[dbname]['dbtype'].startswith('snowflake'):
            import snowflakedb
            outfile=snowflakedb.queryResults(query,params)
        #SQLITE
        if config.DATABASE[dbname]['dbtype'].startswith('sqlite'):
            import sqlitedb
            outfile=sqlitedb.queryResults(query,params)
        #POSTGRES
        if config.DATABASE[dbname]['dbtype'].startswith('postgre'):
            import postgresdb
            outfile=postgresdb.queryResults(query,params)
        #MSACCESS
        if config.DATABASE[dbname]['dbtype'].startswith('msaccess'):
            import msaccessdb
            outfile=msaccessdb.queryResults(query,params)
        #MSCSV
        if config.DATABASE[dbname]['dbtype'].startswith('mscsv'):
            import mscsvdb
            outfile=mscsvdb.queryResults(query,params)
        print(outfile)
    else:
        print(f"Error: invalid database: {dbname}")
except Exception as err:
    sys.stdout.flush()
    sys.stderr.flush()
    common.abort(sys.exc_info(),err)
