#! python
"""
Installation
	python3 -m pip install --upgrade snowflake-connector-python
	   If it fails then go to https://visualstudio.microsoft.com/visual-cpp-build-tools/
		   download build tools
		   install c++ build tools
		   reboot and try again
	python3 -m pip install snowflake-sqlalchemy

"""

#imports
import os
import sys
try:
	import json
	import snowflake.connector as sfc
	from sqlalchemy import create_engine
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
# ok=snowflakedb.addIndex(**params)
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

#---------- begin function certFileCheck ----------
# @describe verifies a private key / cert file exists and can be read by the current user
# @param path str - path to the key or cert file
# @return
#	str - empty string if the file is usable, otherwise the reason it is not
# @usage
#	err = snowflakedb.certFileCheck('/etc/wasql/certs/snowflake.p8')
#	if len(err): print(err)
def certFileCheck(path):
	if path == None or not len(str(path).strip()):
		return "snowflake dbcert error: no path set"
	path = str(path).strip()
	if not os.path.isfile(path):
		return "snowflake dbcert error: file not found - {}".format(path)
	if not os.access(path,os.R_OK):
		return "snowflake dbcert error: file is not readable by this user - {}".format(path)
	return ""

#---------- begin function maskConnectParams ----------
# @describe masks passwords/keys in a connect params dict so it is safe to log
# @param params dict
# @return
#	dict - the params with every secret replaced by asterisks
# @usage print(snowflakedb.maskConnectParams(params))
def maskConnectParams(params):
	if not isinstance(params,dict):
		return params
	masked = dict(params)
	for k in ('dbpass','dbcertpass','password','private_key_file_pwd'):
		if k in masked and masked[k] != None and len(str(masked[k])):
			masked[k] = '*' * len(str(masked[k]))
	if 'private_key' in masked:
		masked['private_key'] = '****'
	return masked

#---------- begin function certValue ----------
# @describe resolves dbcert/dbcertpass from the params, falling back to config.CONFIG
#	using the same key names the php extra uses: <key>_snowflake then snowflake_<key>
# @param params dict - the database tag attributes
# @param key str - dbcert or dbcertpass
# @return
#	str - the value, or an empty string if it is not set anywhere
# @usage dbcert = snowflakedb.certValue(params,'dbcert')
def certValue(params,key):
	if isinstance(params,dict) and key in params and params[key] != None and len(str(params[key]).strip()):
		return str(params[key]).strip()
	try:
		CONFIG = config.CONFIG
	except Exception:
		CONFIG = {}
	for ckey in ("{}_snowflake".format(key),"snowflake_{}".format(key)):
		if ckey in CONFIG and CONFIG[ckey] != None and len(str(CONFIG[ckey]).strip()):
			return str(CONFIG[ckey]).strip()
	return ''

#---------- begin function loadPrivateKey ----------
# @describe reads a PKCS#8 private key (rsa_key.p8) and returns it as the DER bytes
#	that snowflake.connector wants for key-pair (JWT) auth.
#	the matching public key must be registered on the snowflake user:
#	ALTER USER x SET RSA_PUBLIC_KEY=<public key>
# @param path str - path to the private key file
# @param [passphrase] str - passphrase protecting the key. omit for an unencrypted key
# @return
#	(bytes,str) - the DER encoded key and an empty error, or (None,error)
# @usage der,err = snowflakedb.loadPrivateKey('/etc/wasql/certs/snowflake.p8','')
def loadPrivateKey(path,passphrase=''):
	try:
		from cryptography.hazmat.backends import default_backend
		from cryptography.hazmat.primitives import serialization
	except Exception as err:
		return None,"snowflake dbcert error: the cryptography module is required to load the private key - {}".format(err)
	try:
		with open(path,'rb') as fh:
			keydata = fh.read()
	except Exception as err:
		return None,"snowflake dbcert error: unable to read {} - {}".format(path,err)
	pwd = None
	if passphrase != None and len(str(passphrase)):
		pwd = str(passphrase).encode()
	pkey = None
	try:
		pkey = serialization.load_pem_private_key(keydata,password=pwd,backend=default_backend())
	except Exception as err:
		#not PEM - try DER before giving up
		try:
			pkey = serialization.load_der_private_key(keydata,password=pwd,backend=default_backend())
		except Exception:
			return None,"snowflake dbcert error: unable to load private key {} - {}".format(path,err)
	try:
		der = pkey.private_bytes(
			encoding=serialization.Encoding.DER,
			format=serialization.PrivateFormat.PKCS8,
			encryption_algorithm=serialization.NoEncryption()
		)
	except Exception as err:
		return None,"snowflake dbcert error: unable to encode private key {} - {}".format(path,err)
	return der,''

#---------- begin function connect ----------
# @describe returns a database connection
# @param params tuple - parameters to override
#	[dbcert] - path to the PKCS#8 private key used for key-pair (JWT) auth. replaces dbpass
#	[dbcertpass] - passphrase protecting that key. omit for an unencrypted key
#	[dbauth] - authenticator. defaults to SNOWFLAKE_JWT whenever a dbcert is in play
# @return
#	cur_mssql, conn_mssql array
# @usage
#	cur_mssql, conn_mssql =  snowflakedb.connect(params)
def connect(params):
	dbconfig = {}
	#need account,user,password,database,schema,warehouse,role

	#check config.CONFIG

	#check params and override any that are passed in
	if 'dbaccount' in params:
		dbconfig['account'] = params['dbaccount'].replace(".snowflakecomputing.com","",1)
	elif 'dbhost' in params:
		dbconfig['account'] = params['dbhost'].replace(".snowflakecomputing.com","",1)
	else:
		try:
			dbconfig['account'] = params['name']
		except:
			print("Missing dbhost or dbaccount attribute in database tag named '{}'".format(params['name']))
			sys.exit(123)
	if 'dbuser' in params:
		dbconfig['user'] = params['dbuser']

	if 'dbpass' in params:
		dbconfig['password'] = params['dbpass']

	if 'dbname' in params:
		dbconfig['database'] = params['dbname']

	if 'dbschema' in params:
		dbconfig['schema'] = params['dbschema']

	if 'dbwarehouse' in params:
		dbconfig['warehouse'] = params['dbwarehouse']

	if 'dbrole' in params:
		dbconfig['role'] = params['dbrole']

	#dbcert - path to the PKCS#8 private key (rsa_key.p8) used for snowflake key-pair (JWT) auth.
	#	key-pair auth replaces the password entirely
	dbcert = certValue(params,'dbcert')
	dbcertpass = certValue(params,'dbcertpass')
	usecert = 0
	if len(dbcert):
		certerr = certFileCheck(dbcert)
		if len(certerr):
			print(certerr)
			sys.exit(123)
		der,keyerr = loadPrivateKey(dbcert,dbcertpass)
		if der != None:
			dbconfig['private_key'] = der
		elif keyerr.find('cryptography module') > -1:
			#no cryptography module - let the connector read the key file itself (needs connector 3.6+)
			dbconfig['private_key_file'] = dbcert
			if len(dbcertpass):
				dbconfig['private_key_file_pwd'] = dbcertpass
		else:
			print(keyerr)
			sys.exit(123)
		if 'dbauth' in params and params['dbauth'] != None and len(str(params['dbauth'])):
			dbconfig['authenticator'] = params['dbauth']
		else:
			dbconfig['authenticator'] = 'SNOWFLAKE_JWT'
		#a password alongside a key confuses the connector - drop it
		dbconfig.pop('password',None)
		usecert = 1

	try:
		if usecert == 1:
			conn_snowflake = sfc.connect(**dbconfig)
		else:
			conn_snowflake = sfc.connect(account=params['account'], user=params['user'],
               password=params['password'], database=params['database'],
               schema=params['schema'], warehouse=params['warehouse'], role=params['role'])
	except Exception as err:
		try:
			conn_snowflake = sfc.connect(**dbconfig)
		except Exception as err:
			common.abort(sys.exc_info(),err)

	try:
		cur_snowflake = conn_snowflake.cursor()
	except Exception as err:
		common.abort(sys.exc_info(),err)

	return cur_snowflake, conn_snowflake

#---------- begin function executeSQL ----------
# @describe executes a query
# @param query str - SQL query to run
# @param params tuple - parameters to override
# @return 
#	boolean
# @usage 
#	ok =  snowflakedb.executeSQL(query,params)
def executeSQL(query,params):
	try:
		#connect
		cur_snowflake, conn_snowflake =  connect(params)
		#now execute the query
		cur_snowflake.execute(query)
		conn_snowflake.commit()
		return True
		
	except Exception as err:
		cur_snowflake.close()
		conn_snowflake.close()
		return common.debug(sys.exc_info(),err)

#---------- begin function queryResults ----------
# @describe executes a query and returns list of records
# @param query str - SQL query to run
# @param params tuple - parameters to override
# @return 
#   recordsets list
# @usage 
#   recs =  snowflakedb.queryResults(query,params)
def queryResults(query,params):
	try:
		#connect
		cur_snowflake, conn_snowflake =  connect(params)

		#now execute the query
		cur_snowflake.execute(query)
		#get column names - lowercase them for consistency
		fields = [field_md[0].lower() for field_md in cur_snowflake.description]
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
			    rows = cur_snowflake.fetchmany(fetch_size)
			    if not rows:
			        break
			    else:
			        csvwriter.writerows(rows)
			f.close()
			cur_snowflake.close()
			conn_snowflake.close()
			return params['filename']
		else:
			recs = []
			for rec in cur_snowflake.fetchall():
				#convert to a dictionary manually since it is not built into the driver
				rec=dict(zip(fields, rec))
				#call json.dumps to convert date objects to strings in results
				rec=json.loads(json.dumps(rec,sort_keys=False, ensure_ascii=True, default=db.convertStr))
				recs.append(rec)
			cur_snowflake.close()
			conn_snowflake.close()
			return recs
			
	except Exception as err:
		cur_snowflake.close()
		conn_snowflake.close()
		return common.debug(sys.exc_info(),err)
###########################################
