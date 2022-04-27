#! python
"""
Installation
	python3 -m pip install pyodbc
References

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
	print("Import Error: {}. ExeptionType: {}, Filename: {}, Linenumber: {}".format(err,exc_type,fname,exc_tb.tb_lineno))
	sys.exit(3)

#---------- begin function connect ----------
# @describe returns a database connection
# @param params tuple - parameters to override
# @return 
#	cur_mssql, conn_mssql array
# @usage 
#	cur_mssql, conn_mssql =  mscsvdb.connect(params)
def connect(params):
	dbconfig = {}

	#check params and override any that are passed in
	if 'dbname' in params:
		dbconfig['dbq'] = params['dbname']

	#build connection string list
	conn_list = [
		"Driver={Microsoft Access Text Driver (*.txt, *.csv)}",
		'FIL=text',
		'DriverId=27',
		'Extensions=asc,csv,tab,txt',
		'ImportMixedTypes=Text',
		'ReadOnly=false',
		'IMEX=1',
		"DelimitedBy=|",
		'MaxScanRows=2',
		'Extended Properties="Mode=ReadWrite;ReadOnly=false;MaxScanRows=2;HDR=YES"'
		]
	conn_list.append("Dbq={}".format(dbconfig['dbq']))
	s=';'
	conn_str=s.join(conn_list)

	try:
		conn_mscsv = pyodbc.connect(conn_str)
	except Exception as err:
		common.abort(sys.exc_info(),err)

	try:
		cur_mscsv = conn_mscsv.cursor()
	except Exception as err:
		common.abort(sys.exc_info(),err)

	return cur_mscsv, conn_mscsv

#---------- begin function executeSQL ----------
# @describe executes a query
# @param query str - SQL query to run
# @param params tuple - parameters to override
# @return 
#	boolean
# @usage 
#	recs =  mscsvdb.executeSQL(query,params)
def executeSQL(query,params):
	try:
		#connect
		cur_mscsv, conn_mscsv =  connect(params)
		#now execute the query
		cur_mscsv.execute(query)
		return True
		
	except Exception as err:
		cur_mscsv.close()
		conn_mscsv.close()
		return common.debug(sys.exc_info(),err)

#---------- begin function convertStr ----------
# @describe convert objects in recordsets to string
# @param o object
# @return 
#   str string
# @usage 
#   str =  mscsvdb.convertStr(o)
def convertStr(o):
	return "{}".format(o)

#---------- begin function queryResults ----------
# @describe executes a query and returns list of records
# @param query str - SQL query to run
# @param params tuple - parameters to override
# @return 
#   recordsets list
# @usage 
#   recs =  mscsvdb.queryResults(query,params)
def queryResults(query,params):
	try:
		#connect
		cur_mscsv, conn_mscsv =  connect(params)
		#now execute the query
		cur_mscsv.execute(query)

		if 'filename' in params.keys():
			jsv_file=params['filename']
			#get column names
			fields = [field_md[0] for field_md in cur_mscsv.description]
			#write file
			f = open(jsv_file, "w")
			f.write(json.dumps(fields,sort_keys=False, ensure_ascii=True, default=convertStr).lower())
			f.write("\n")
			#write records
			for rec in cur_mscsv.fetchall():
				#convert to a dictionary manually since it is not built into the driver
				rec=dict(zip(fields, rec))
				f.write(json.dumps(rec,sort_keys=False, ensure_ascii=True, default=convertStr))
				f.write("\n")
			f.close()
			cur_mscsv.close()
			conn_mscsv.close()
			return params['filename']
		else:
			recs = cur_mscsv.fetchall()
			tname=type(recs).__name__
			if tname == 'tuple':
				recs=list(recs)
				cur_mscsv.close()
				conn_mscsv.close()
				return recs
			elif tname == 'list':
				cur_mscsv.close()
				conn_mscsv.close()
				return recs
			else:
				cur_mscsv.close()
				conn_mscsv.close()
				return []

	except Exception as err:
		cur_mscsv.close()
		conn_mscsv.close()
		return common.debug(sys.exc_info(),err)
###########################################
