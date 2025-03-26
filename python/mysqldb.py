#! python
"""
Installation
	python3 -m pip install mysql.connector-python
	If that fails, try upgrading pip:
		python3 -m pip install --upgrade pip
		then try again
	if it still fails try
   		python -m pip install -U setuptools
   		python -m pip install -U wheel
   		then try again
References
	https://dev.mysql.com/doc/connector-python/en/connector-python-cext-reference.html
"""


#imports
import os
import sys
try:
	import json
	import mysql.connector
	import config
	import common
	import db
	import csv
except Exception as err:
	exc_type, exc_obj, exc_tb = sys.exc_info()
	fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
	print("Import Error: {}. ExeptionType: {}, Filename: {}, Linenumber: {}".format(err,exc_type,fname,exc_tb.tb_lineno))
	sys.exit(3)
	
#---------- begin function addIndex ----------
# @describe returns a dictionary of records returned from query
# @param params dictionary - params
# -table table
# -fields field(s) to add to index
# [-unique]
# [-fulltext]
# [-name] str - specific name for index
# @return 
#	boolean
# @usage
# params={
# 	'-table':'states',
# 	'-fields':'code'   
# } 
# ok=mysqldb.addIndex(**params)
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
		params['-name']="{}_{}_".format(prefix,params['-table'])
	#create query
	fieldstr = params['-fields'].replace(',','_')
	query="CREATE {} INDEX IF NOT EXISTS {} on {} ({})".format(unique,params['-name'],params['-table'],fieldstr)
	#execute query
	return executeSQL(query)  
	  
#---------- begin function connect ----------
# @describe returns a database connection
# @param params tuple - parameters to override
# @return 
# cur_mssql, conn_mssql array
# @usage 
# cur_mssql, conn_mssql =  mysqldb.connect(params)
def connect(params):
	dbconfig = {
		'auth_plugin':'mysql_native_password'
	}
	#check params and override any that are passed in
	if 'dbhost' in params:
		dbconfig['host'] = params['dbhost']
	else:
		dbconfig['host'] = 'localhost'

	if 'dbuser' in params:
		dbconfig['user'] = params['dbuser']

	if 'dbpass' in params:
		dbconfig['password'] = params['dbpass']

	if 'dbname' in params:
		dbconfig['database'] = params['dbname']

	try:
		conn_mysql = mysql.connector.connect(**dbconfig)
	except Exception as err:
		common.abort(sys.exc_info(),err)

	try:
		cur_mysql = conn_mysql.cursor(dictionary=False,buffered=True)
	except Exception as err:
		common.abort(sys.exc_info(),err) 

	return cur_mysql, conn_mysql

#---------- begin function executeSQL ----------
# @describe executes a query
# @param query str - SQL query to run
# @param params tuple - parameters to override
# @return 
#	boolean
# @usage 
#	ok =  mysqldb.executeSQL(query,params)
def executeSQL(query,params):
	try:
		#connect
		cur_mysql, conn_mysql =  connect(params)
		#now execute the query
		cur_mysql.execute(query)
		conn_mysql.commit()
		return True
		
	except Exception as err:
		cur_mysql.close()
		conn_mysql.close()
		return common.debug(sys.exc_info(),err)


#---------- begin function queryResults ----------
# @describe executes a query and returns list of records
# @param query str - SQL query to run
# @param params tuple - parameters to override
# @return 
#   recordsets list
# @usage 
#   recs =  mysqldb.queryResults(query,params)
def queryResults(query,params):
	try:
		#connect
		cur_mysql, conn_mysql =  connect(params)
		#now execute the query
		cur_mysql.execute(query)
		#get column names - lowercase them for consistency
		fields = [field_md[0].lower() for field_md in cur_mysql.description]
		if 'filename' in params.keys():
			csv_file=params['filename']	
			#write the fields row
			f = open(csv_file, 'w', newline='', encoding='utf-8')
			csvwriter = csv.writer(f, delimiter=',', quotechar='"', quoting=csv.QUOTE_MINIMAL)
			csvwriter.writerow(fields)
			#write records row, fetching them 1000 at a time
			csvwriter = csv.writer(f, delimiter=',', quotechar='"', quoting=csv.QUOTE_NONNUMERIC)
			#write records
			fetch_size = 1000
			while True:
			    rows = cur_mysql.fetchmany(fetch_size)
			    if not rows:
			        break
			    else:
			        csvwriter.writerows(rows)
			f.close()
			cur_mysql.close()
			conn_mysql.close()
			return params['filename']
		else:
			recs = []
			for rec in cur_mysql.fetchall():
				#convert to a dictionary manually since it is not built into the driver
				rec=dict(zip(fields, rec))
				#call json.dumps to convert date objects to strings in results
				rec=json.loads(json.dumps(rec,sort_keys=False, ensure_ascii=True, default=db.convertStr))
				recs.append(rec)
			cur_mysql.close()
			conn_mysql.close()
			return recs

	except Exception as err:
		cur_mysql.close()
		conn_mysql.close()
		return common.debug(sys.exc_info(),err)
		  
###########################################