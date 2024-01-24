#! python
"""
Installation
	python3 -m pip install pyodbc
References
	https://developers.sap.com/tutorials/msaccess-clients-python.html
	https://code.google.com/archive/p/pyodbc/wikis/FAQs.wiki
"""

#imports
import os
import sys
try:
	import json
	import pyodbc
	import config
	import common
	import csv
except Exception as err:
	exc_type, exc_obj, exc_tb = sys.exc_info()
	fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
	print("Import Error: {}. ExeptionType: {}, Filename: {}, Linenumber: {}".format(err,exc_type,fname,exc_tb.tb_lineno))
	sys.exit(3)

#---------- begin function connect ----------
# @describe returns a database connection
# @param params tuple - parameters to override
# @return 
#	cur_mssql, conn_mssql array
# @usage 
#	cur_mssql, conn_mssql =  msaccessdb.connect(params)
def connect(params):
	dbconfig = {}

	#check params and override any that are passed in
	if 'dbname' in params:
		dbconfig['dbq'] = params['dbname']

	#build connection string list
	conn_list = ["Driver={Microsoft Access Driver (*.mdb, *.accdb)}","ExtendedAnsiSQL=1'","Uid=admin","Pwd=","Threads=4","MaxBufferSize=4096","PageTimeout=5"]
	conn_list.append("Dbq={}".format(dbconfig['dbq']))
	s=';'
	conn_str=s.join(conn_list)


	try:
		conn_msaccess = pyodbc.connect(conn_str, readonly=True, autocommit=True)
	except Exception as err:
		common.abort(sys.exc_info(),err)

	try:
		cur_msaccess = conn_msaccess.cursor()
	except Exception as err:
		common.abort(sys.exc_info(),err)

	return cur_msaccess, conn_msaccess

#---------- begin function executeSQL ----------
# @describe executes a query
# @param query str - SQL query to run
# @param params tuple - parameters to override
# @return 
#	boolean
# @usage 
#	recs =  msaccessdb.executeSQL(query,params)
def executeSQL(query,params):
	try:
		#connect
		cur_msaccess, conn_msaccess =  connect(params)
		#now execute the query
		cur_msaccess.execute(query)
		conn_msaccess.commit()
		return True
		
	except Exception as err:
		cur_msaccess.close()
		conn_msaccess.close()
		return common.debug(sys.exc_info(),err)

#---------- begin function queryResults ----------
# @describe executes a query and returns list of records
# @param query str - SQL query to run
# @param params tuple - parameters to override
# @return 
#   recordsets list
# @usage 
#   recs =  msaccessdb.queryResults(query,params)
def queryResults(query,params):
	try:
		#connect
		cur_msaccess, conn_msaccess =  connect(params)
		#now execute the query
		cur_msaccess.execute(query)
		#get column names - lowercase them for consistency
		fields = [field_md[0].lower for field_md in cur_msaccess.description]

		if 'filename' in params.keys():
			csv_file=params['filename']
			f = open(csv_file, 'w', newline='', encoding='utf-8')
			csvwriter = csv.writer(f, delimiter=',', quotechar='"', quoting=csv.QUOTE_MINIMAL)
			csvwriter.writerow(fields)
			#write records row, fetching them 1000 at a time
			csvwriter = csv.writer(f, delimiter=',', quotechar='"', quoting=csv.QUOTE_NONNUMERIC)
			#write records
			fetch_size = 1000
			while True:
			    rows = cur_msaccess.fetchmany(fetch_size)
			    if not rows:
			        break
			    else:
			        csvwriter.writerows(rows)
			f.close()
			cur_msaccess.close()
			conn_msaccess.close()
			return params['filename']
		else:
			recs = []
			for rec in cur_msaccess.fetchall():
				#convert to a dictionary
				rec=dict(zip(fields, rec))
				#call json.dumps to convert date objects to strings in results
				rec=json.loads(json.dumps(rec,sort_keys=False, ensure_ascii=True, default=db.convertStr))
				recs.append(rec)
			cur_msaccess.close()
			conn_msaccess.close()
			return recs

	except Exception as err:
		cur_msaccess.close()
		conn_msaccess.close()
		return common.debug(sys.exc_info(),err)
###########################################
