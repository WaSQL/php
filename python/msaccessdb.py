#! python
"""
Installation
    python3 -m pip install pyodbc
References
    https://pypi.org/project/hdbcli/
    https://developers.sap.com/tutorials/msaccess-clients-python.html
"""

#imports
import os
import sys
try:
    import json
    sys.path.append('c:/users/slloy/appdata/roaming/python/python38/site-packages')
    import pyodbc
    import config
    import common

except Exception as err:
    exc_type, exc_obj, exc_tb = sys.exc_info()
    fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
    print(f"Error: {err}\nFilename: {fname}\nLinenumber: {exc_tb.tb_lineno}")
    sys.exit(3)

###########################################
#Python’s default arguments are evaluated once when the function is defined, not each time the function is called.
#For msaccess tenant databases, you can use the port number 3NN13 (where NN is the SAP instance number).
#For msaccess single-tenant databases, the port number is 3NN15.
def connect(params):
    dbconfig = {}
    #check config.CONFIG
    if 'dbname' in config.CONFIG:
        dbconfig['dbq'] = config.CONFIG['dbname']

    #check params and override any that are passed in
    if 'dbname' in params:
        dbconfig['dbq'] = params['dbname']

    #build connection string list
    conn_list = ["Driver={Microsoft Access Driver (*.mdb, *.accdb)}","ExtendedAnsiSQL=1'","Uid=admin","Pwd=","Threads=4","MaxBufferSize=4096","PageTimeout=5"]
    conn_list.append(f"Dbq={dbconfig['dbq']}")
    s=';'
    conn_str=s.join(conn_list)


    try:
        conn_msaccess = pyodbc.connect(conn_str)
    except Exception as err:
        common.abort(sys.exc_info(),err)

    try:
        cur_msaccess = conn_msaccess.cursor()
    except Exception as err:
        common.abort(sys.exc_info(),err)

    return cur_msaccess, conn_msaccess

###########################################
def executeSQL(query,params):
    try:
        #connect
        cur_msaccess, conn_msaccess =  connect(params)
        #now execute the query
        cur_msaccess.execute(query)
        return True
        
    except Exception as err:
        cur_msaccess.close()
        conn_msaccess.close()
        return common.debug(sys.exc_info(),err)

###########################################
#conversion function to convert objects in recordsets
def convertStr(o):
    return f"{o}"

###########################################
def queryResults(query,params):
    try:
        #connect
        cur_msaccess, conn_msaccess =  connect(params)
        #now execute the query
        cur_msaccess.execute(query)

        if 'filename' in params.keys():
            jsv_file=params['filename']
            #get column names
            fields = [field_md[0] for field_md in cur_msaccess.description]
            #write file
            f = open(jsv_file, "w")
            f.write(json.dumps(fields,sort_keys=False, ensure_ascii=True, default=convertStr).lower())
            f.write("\n")
            #write records
            for rec in cur_msaccess.fetchall():
                #convert to a dictionary manually since it is not built into the driver
                rec=dict(zip(fields, rec))
                f.write(json.dumps(rec,sort_keys=False, ensure_ascii=True, default=convertStr))
                f.write("\n")
            f.close()
            cur_msaccess.close()
            conn_msaccess.close()
            return params['filename']
        else:
            recs = cur_msaccess.fetchall()
            tname=type(recs).__name__
            if tname == 'tuple':
                recs=list(recs)
                cur_msaccess.close()
                conn_msaccess.close()
                return recs
            elif tname == 'list':
                cur_msaccess.close()
                conn_msaccess.close()
                return recs
            else:
                cur_msaccess.close()
                conn_msaccess.close()
                return []

    except Exception as err:
        cur_msaccess.close()
        conn_msaccess.close()
        return common.debug(sys.exc_info(),err)
###########################################
