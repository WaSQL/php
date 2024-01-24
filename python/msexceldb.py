#! python
"""
Installation
	python3 -m pip install pyodbc
References
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
	import db
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
#	cur_mssql, conn_mssql =  msexceldb.connect(params)
def connect(params):
	dbconfig = {}

	#check params and override any that are passed in
	if 'dbname' in params:
		dbconfig['dbq'] = params['dbname']

	#build connection string list
	conn_list = [
		"Driver=Microsoft Excel Driver (*.xls, *.xlsx, *.xlsm, *.xlsb)",
		'FIL=Excel 12.0',
		'DriverId=1046',
		'ImportMixedTypes=Text',
		'ReadOnly=false',
		'IMEX=1',
		'MaxScanRows=2',
		'Extended Properties="Mode=ReadWrite;ReadOnly=false;MaxScanRows=2;HDR=YES"',
		]
	conn_list.append("Dbq={}".format(dbconfig['dbq']))
	s=';'
	conn_str=s.join(conn_list)

	try:
		conn_msexcel = pyodbc.connect(conn_str, readonly=True, autocommit=True)
	except Exception as err:
		common.abort(sys.exc_info(),err)

	try:
		cur_msexcel = conn_msexcel.cursor()
	except Exception as err:
		common.abort(sys.exc_info(),err)

	return cur_msexcel, conn_msexcel

#---------- begin function executeSQL ----------
# @describe executes a query
# @param query str - SQL query to run
# @param params tuple - parameters to override
# @return 
#	boolean
# @usage 
#	recs =  msexceldb.executeSQL(query,params)
def executeSQL(query,params):
	try:
		#connect
		cur_msexcel, conn_msexcel =  connect(params)
		#now execute the query
		cur_msexcel.execute(query)
		conn_msexcel.commit()
		return True
		
	except Exception as err:
		cur_msexcel.close()
		conn_msexcel.close()
		return common.debug(sys.exc_info(),err)

#---------- begin function queryResults ----------
# @describe executes a query and returns list of records
# @param query str - SQL query to run
# @param params tuple - parameters to override
# @return 
#   recordsets list
# @usage 
#   recs =  msexceldb.queryResults(query,params)
def queryResults(query,params):
	try:
		#connect
		cur_msexcel, conn_msexcel =  connect(params)
		#now execute the query
		cur_msexcel.execute(query)
		#get column names - lowercase them for consistency
		fields = [field_md[0].lower() for field_md in cur_msexcel.description]

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
			    rows = cur_msexcel.fetchmany(fetch_size)
			    if not rows:
			        break
			    else:
			        csvwriter.writerows(rows)
			f.close()
			cur_msexcel.close()
			conn_msexcel.close()
			return params['filename']
		else:
			recs = []
			for rec in cur_msexcel.fetchall():
				#convert to a dictionary manually since it is not built into the driver
				rec=dict(zip(fields, rec))
				#call json.dumps to convert date objects to strings in results
				rec=json.loads(json.dumps(rec,sort_keys=False, ensure_ascii=True, default=db.convertStr))
				recs.append(rec)
			cur_msexcel.close()
			conn_msexcel.close()
			return recs

	except Exception as err:
		cur_msexcel.close()
		conn_msexcel.close()
		return common.debug(sys.exc_info(),err)
###########################################
