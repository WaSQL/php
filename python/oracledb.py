#! python
"""
Installation
	python3 -m pip install cx_Oracle
References
	https://cx-oracle.readthedocs.io/en/latest/user_guide/connection_handling.html#connpool
	https://cx-oracle.readthedocs.io/en/latest/user_guide/connection_handling.html
"""


#imports
import os
import sys
try:
	import json
	import cx_Oracle
	from cx_Oracle import Error
	import config
	import common
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
# ok=oracledb.addIndex(**params)
def addIndex(params):
	#check required
	if '-table' not in params:
		return ("oracledb.addIndex error: No Table Specified")
	if '-fields' not in params:
		return ("oracledb.addIndex error: No Fields Specified")
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
	
#---------- begin function connect ----------
# @describe returns a database connection
# @param params tuple - parameters to override
# @return 
# 	cur_mssql, conn_mssql array
# @usage 
# 	cur_mssql, conn_mssql =  oracledb.connect(params)
def connect(params):
	pconfig = {
		'min':2,
		'max':10,
		'increment':1,
		'encoding':'UTF-8'
	}
	dbconfig = {}
	dbconfig['port'] = 1521
	dbconfig['host'] = 'localhost'

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

	try:
		conn_oracle = cx_Oracle.connect(**cconfig)
	except Exception as err:
		common.abort(sys.exc_info(),err)

	try:
		cur_oracle = conn_oracle.cursor()
	except Exception as err:
		common.abort(sys.exc_info(),err)

	cur_oracle.rowfactory = dictFactory
	return cur_oracle, conn_oracle
		
#---------- begin function dictFactory ----------
# @describe function to generate dictionary from recordset
# @param c cursor
# @return 
#   d dict
# @usage 
#   cur_oracle.rowfactory = dictFactory
def dictFactory(cursor, row):
	d = {}
	for idx, col in enumerate(cursor.description):
		d[col[0]] = row[idx]
	return d

#---------- begin function executeSQL ----------
# @describe executes a query
# @param query str - SQL query to run
# @param params tuple - parameters to override
# @return 
#	boolean
# @usage 
#	ok =  oracledb.executeSQL(query,params)
def executeSQL(query,params):
	try:
		#connect
		cur_oracle, conn_oracle =  connect(params)
		#now execute the query
		cur_oracle.execute(query)
		return True
		
	except Exception as err:
		return common.debug(sys.exc_info(),err)

#---------- begin function convertStr ----------
# @describe convert objects in recordsets to string
# @param o object
# @return 
#   str string
# @usage 
#   str =  oracledb.convertStr(o)
def convertStr(o):
	return "{}".format(o)

#---------- begin function queryResults ----------
# @describe executes a query and returns list of records
# @param query str - SQL query to run
# @param params tuple - parameters to override
# @return 
#   recordsets list
# @usage 
#   recs =  oracledb.queryResults(query,params)
def queryResults(query,params):
	try:
		#connect
		cur_oracle, conn_oracle =  connect(params)
		#now execute the query
		cur_oracle.execute(query)       
		if 'filename' in params.keys():
			jsv_file=params['filename']
			#get column names
			fields = [field_md[0] for field_md in cur_oracle.description]
			#write file
			f = open(jsv_file, "w")
			f.write(json.dumps(fields,sort_keys=False, ensure_ascii=False, default=str).lower())
			f.write("\n")
			#write records
			for rec in cur_oracle.fetchall():
				f.write(json.dumps(rec,sort_keys=False, ensure_ascii=True, default=convertStr))
				f.write("\n")
			f.close()
			cur_oracle.close()
			conn_oracle.close()
			return params['filename']
		else:
			recs = cur_oracle.fetchall()
			tname=type(recs).__name__
			if tname == 'tuple':
				recs=list(recs)
				cur_oracle.close()
				conn_oracle.close()
				return recs
			elif tname == 'list':
				cur_oracle.close()
				conn_oracle.close()
				return recs
			else:
				cur_oracle.close()
				conn_oracle.close()
				return []

		
	except Exception as err:
		cur_oracle.close()
		conn_oracle.close()
		return common.debug(sys.exc_info(),err)
###########################################
