#! python
# -*- coding: utf-8 -*-
"""
Installation
	python3 -m pip install "psycopg[binary]"
		NOTE: you MUST put quotes around the name
References
	https://www.psycopg.org/psycopg3/docs/basic/usage.html

"""


#imports
import os
import sys
try:
	import json
	import psycopg
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
# ok=postgresdb.addIndex(**params)
def addIndex(params):
	#check required
	if '-table' not in params:
		return ("postgresdb.addIndex error: No Table Specified")
	if '-fields' not in params:
		return ("postgresdb.addIndex error: No Fields Specified")
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
#	cur_mssql, conn_mssql array
# @usage 
#	cur_mssql, conn_mssql =  postgresdb.connect(params)
def connect(params):
	dbconfig = {}
	#check params and override any that are passed in
	if 'dbhost' in params:
		dbconfig['host'] = params['dbhost']
	else:
		print("Missing dbhost attribute in database tag named '{}'".format(params['name']))
		sys.exit(123)
		
	if 'dbuser' in params:
		dbconfig['user'] = params['dbuser']

	if 'dbpass' in params:
		dbconfig['password'] = params['dbpass']

	if 'dbname' in params:
		dbconfig['dbname'] = params['dbname']

	if 'dbport' in params:
		dbconfig['port'] = params['dbport']

	if 'dbkeepalives' in params:
		dbconfig['keepalives'] = params['dbkeepalives']

	if 'dbkeepalives_idle' in params:
		dbconfig['keepalives_idle'] = params['dbkeepalives_idle']

	if 'dbkeepalives_interval' in params:
		dbconfig['keepalives_interval'] = params['dbkeepalives_interval']

	if 'dbkeepalives_count' in params:
		dbconfig['keepalives_count'] = params['dbkeepalives_count']

	try:
		conn_postgres = psycopg.connect(**dbconfig)
	except Exception as err:
		common.abort(sys.exc_info(),err)

	try:
		cur_postgres = conn_postgres.cursor()
	except Exception as err:
		common.abort(sys.exc_info(),err)

	return cur_postgres, conn_postgres
	   
#---------- begin function executeSQL ----------
# @describe executes a query
# @param query str - SQL query to run
# @return 
#	boolean
# @usage 
#	ok =  postgresdb.executeSQL(query)
def executeSQL(query,params={}):
	try:
		#connect
		cur_postgres, conn_postgres =  connect(params)
		#now execute the query
		cur_postgres.execute(query)
		conn_postgres.commit()
		return True
	except Exception as err:
		exc_type, exc_obj, exc_tb = sys.exc_info()
		cur_postgres.close()
		conn_postgres.close()
		return common.debug(sys.exc_info(),err)

#---------- begin function executePS ----------
# @describe executes a prepared statement and passes in params
# @param query str - SQL query to run
# @param params tuple - parameters for prepared statement
# @note When parameters are used, in order to include a literal % in the query you can use the %% string:
# @return 
#	boolean
# @usage 
# 	query = "INSERT INTO some_table (id, last_name) VALUES (%(id)s,  %(name)s);"
# 	params =  {'name': "O'Reilly",'id': 10}
#	ok =  postgresdb.executePS(query,params)
def executePS(query,args,params={}):
	try:
		#connect
		cur_postgres, conn_postgres =  connect(params)
		#now execute the query
		cur_postgres.execute(query,args)
		conn_postgres.commit()
		return True
	except Exception as err:
		exc_type, exc_obj, exc_tb = sys.exc_info()
		cur_postgres.close()
		conn_postgres.close()
		return common.debug(sys.exc_info(),err)

#---------- begin function queryResults ----------
# @describe executes a query and returns list of records
# @param query str - SQL query to run
# @param params tuple - parameters to override
# @return 
#   recordsets list
# @usage 
#   recs =  postgresdb.queryResults(query,params)
def queryResults(query,params={}):
	try:
		#connect
		cur_postgres, conn_postgres =  connect(params)

		#now execute the query
		cur_postgres.execute(query)
		#get column names - lowercase them for consistency
		fields = [field_md[0].lower() for field_md in cur_postgres.description]
		if 'filename' in params.keys():
			csv_file=params['filename']
			#write file
			f = open(csv_file, 'w', newline='', encoding='utf-8')
			csvwriter = csv.writer(f, delimiter=',', quotechar='"', quoting=csv.QUOTE_MINIMAL)
			csvwriter.writerow(fields)
			#write records row, fetching them 1000 at a time
			csvwriter = csv.writer(f, delimiter=',', quotechar='"', quoting=csv.QUOTE_NONNUMERIC)
			#write records
			fetch_size = 1000
			while True:
			    rows = cur_postgres.fetchmany(fetch_size)
			    if not rows:
			        break
			    else:
			        csvwriter.writerows(rows)
			f.close()
			cur_postgres.close()
			conn_postgres.close()
			return params['filename']
		else:
			recs = []
			for rec in cur_postgres.fetchall():
				#convert to a dictionary manually since it is not built into the driver
				rec=dict(zip(fields, rec))
				#call json.dumps to convert date objects to strings in results
				rec=json.loads(json.dumps(rec,sort_keys=False, ensure_ascii=True, default=db.convertStr))
				recs.append(rec)
			cur_postgres.close()
			conn_postgres.close()
			return recs
		
	except Exception as err:
		cur_postgres.close()
		conn_postgres.close()
		return common.debug(sys.exc_info(),err)
###########################################
