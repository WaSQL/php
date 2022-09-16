#! python
"""
Installation
	python3 -m pip install hdbcli
References
	https://pypi.org/project/hdbcli/
	https://developers.sap.com/tutorials/hana-clients-python.html
"""

#imports
import os
import sys
try:
	import json
	import hdbcli
	from hdbcli import dbapi
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
# ok=hanadb.addIndex(**params)
def addIndex(params):
	#check required
	if '-table' not in params:
		return ("hanadb.addIndex error: No Table Specified")
	if '-fields' not in params:
		return ("hanadb.addIndex error: No Fields Specified")
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
#For HANA tenant databases, you can use the port number 3NN13 (where NN is the SAP instance number).
#For HANA single-tenant databases, the port number is 3NN15.

#---------- begin function connect ----------
# @describe returns a database connection
# @param params tuple - parameters to override
# @return 
#	cur_mssql, conn_mssql array
# @usage 
#	cur_mssql, conn_mssql =  hanadb.connect(params)
def connect(params):
	dbconfig = {}
	#check params and override any that are passed in
	if 'dbhost' in params:
		dbconfig['address'] = params['dbhost']
	else:
		print("Missing dbhost attribute in database tag named '{}'".format(params['name']))
		sys.exit(123)

	if 'dbuser' in params:
		dbconfig['user'] = params['dbuser']

	if 'dbpass' in params:
		dbconfig['password'] = params['dbpass']

	if 'dbport' in params:
		dbconfig['port'] = params['dbport']

	try:
		conn_hana = dbapi.connect(**dbconfig)
	except Exception as err:
		common.abort(sys.exc_info(),err)

	try:
		cur_hana = conn_hana.cursor()
	except Exception as err:
		common.abort(sys.exc_info(),err)

	return cur_hana, conn_hana
		
#---------- begin function executeSQL ----------
# @describe executes a query
# @param query str - SQL query to run
# @param params tuple - parameters to override
# @return 
#	boolean
# @usage 
#	ok =  hanadb.executeSQL(query,params)
def executeSQL(query,params):
	try:
		#connect
		cur_hana, conn_hana =  connect(params)
		#now execute the query
		cur_hana.execute(query)
		conn_hana.commit()
		return True
		
	except Exception as err:
		cur_hana.close()
		conn_hana.close()
		return common.debug(sys.exc_info(),err)

#---------- begin function convertStr ----------
# @describe convert objects in recordsets to string
# @param o object
# @return 
#   str string
# @usage 
#   str =  hanadb.convertStr(o)
def convertStr(o):
	return "{}".format(o)

#---------- begin function queryResults ----------
# @describe executes a query and returns list of records
# @param query str - SQL query to run
# @param params tuple - parameters to override
# @return 
#   recordsets list
# @usage 
#   recs =  hanadb.queryResults(query,params)
def queryResults(query,params):
	try:
		#connect
		cur_hana, conn_hana =  connect(params)
		#now execute the query
		cur_hana.execute(query)
		#get column names - lowercase them for consistency
		fields = [field_md[0].lower() for field_md in cur_hana.description]
		if 'filename' in params.keys():
			jsv_file=params['filename']
			#write file
			f = open(jsv_file, "w")
			f.write(json.dumps(fields,sort_keys=False, ensure_ascii=True, default=convertStr).lower())
			f.write("\n")
			#write records
			for rec in cur_hana.fetchall():
				#convert to a dictionary manually since it is not built into the driver
				rec=dict(zip(fields, rec))
				#call json.dumps to convert date objects to strings in results
				f.write(json.dumps(rec,sort_keys=False, ensure_ascii=True, default=convertStr))
				f.write("\n")
			f.close()
			cur_hana.close()
			conn_hana.close()
			return params['filename']
		else:
			recs = []
			for rec in cur_hana.fetchall():
				#convert to a dictionary manually since it is not built into the driver
				rec=dict(zip(fields, rec))
				#call json.dumps to convert date objects to strings in results
				rec=json.dumps(rec,sort_keys=False, ensure_ascii=True, default=convertStr)
				recs.append(rec)
			cur_hana.close()
			conn_hana.close()
			return recs

	except Exception as err:
		cur_hana.close()
		conn_hana.close()
		return common.debug(sys.exc_info(),err)
###########################################
