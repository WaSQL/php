#! python
"""
Installation
	python3 -m pip install sqlite3
References
	https://www.sqlitetutorial.net/sqlite-python/create-tables/
"""

#imports
import os
import sys
try:
	import json
	import sqlite3
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
# @return boolean
# @usage 
# params={
# 	'-table':'states',
# 	'-fields':'code'   
# } 
# @usage ok=sqlitedb.addIndex(**params)
def addIndex(params):
	#check required
	if '-table' not in params:
		return ("sqlitedb.addIndex error: No Table Specified")
	if '-fields' not in params:
		return ("sqlitedb.addIndex error: No Fields Specified")
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

# ---------- begin function sqliteAddDBRecords--------------------

# @describe add multiple records into a table
# @param table string - tablename
# @param params array - 
#	[-recs] array - array of records to insert into specified table
#	[-csv] array - csv file of records to insert into specified table
# @return count int
# @usage ok=sqlite.addRecords('comments',{'-csv'=>afile})
# @usage ok=sqlite.addRecords('comments',{'-recs'=>recs})
def addRecords(table='',params={}):
	if len(table)==0:
		return "sqliteAddDBRecords Error: No Table"
	
	if '-chunk' not in params:
		params['-chunk']=1000
	
	params['-table']=table
	#require either -recs or -csv
	if '-recs' not in params and '-csv' not in params:
		return "sqliteAddDBRecords Error: either -csv or -recs is required"

	#csv
	if '-csv' in params:
		if os.path.isfile(params['-csv']) is not True:
			return "sqliteAddDBRecords Error: no such file: {$params['-csv']}"
		
		return processCSVLines(params['-csv'],'sqliteAddDBRecordsProcess',params)
	elif '-recs' in params:
		if type(params['-recs']) is not List:
			return "sqliteAddDBRecords Error: no recs"
		elif len(params['-recs']) == 0:
			return "sqliteAddDBRecords Error: no recs"

		return sqliteAddDBRecordsProcess(params['-recs'],params)

def sqliteAddDBRecordsProcess(recs={},params={}):
	if '-table' not in params:
		return "sqliteAddDBRecordsProcess Error: no table"

	if type(recs) is not List:
		return "sqliteAddDBRecords Error: no recs"
	elif len(recs) == 0:
		return "sqliteAddDBRecords Error: no recs"

	table=params['-table'];
	fieldinfo=sqliteGetDBFieldInfo(table,1);
	#indexes must be normal - fix if not
	if(!isset($recs[0])){
		$xrecs=array();
		foreach($recs as $rec){$xrecs[]=$rec;}
		$recs=$xrecs;
		unset($xrecs);
	}
	//if -map then remap specified fields
	if(isset($params['-map'])){
		foreach($recs as $i=>$rec){
			foreach($rec as $k=>$v){
				if(isset($params['-map'][$k])){
					unset($recs[$i][$k]);
					$k=$params['-map'][$k];
					$recs[$i][$k]=$v;
				}
			}
		}
	}
	//if -map2json then map the whole record to this field
	if(isset($params['-map2json'])){
		$jsonkey=$params['-map2json'];
		foreach($recs as $i=>$rec){
			$recs[$i]=array($jsonkey=>$rec);
		}
	}
	//fields
	$fields=array();
	foreach($recs as $i=>$first_rec){
		foreach($first_rec as $k=>$v){
			if(!isset($fieldinfo[$k])){
				unset($recs[$i][$k]);
				continue;
			}
			if(!in_array($k,$fields)){$fields[]=$k;}
		}
		break;
	}
	if(!count($fields)){
		debugValue(array(
			'function'=>'sqliteAddDBRecordsProcess',
			'message'=>'No fields in first_rec that match fieldinfo',
			'first_rec'=>$first_rec,
			'fieldinfo_keys'=>array_keys($fieldinfo)
		));
		return 0;
	}
	$fieldstr=implode(',',$fields);
	//if possible use the JSON way so we can insert more efficiently
	$jsonstr=encodeJSON($recs,JSON_UNESCAPED_UNICODE);
	if(strlen($jsonstr)){
		
		$extracts=array();
		foreach($fields as $fld){
			$extracts[]="JSON_EXTRACT(value,'\$.{$fld}') as {$fld}";
		}
		$extractstr=implode(','.PHP_EOL,$extracts);
		$query=<<<ENDOFQUERY
			INSERT OR REPLACE INTO {$table} ($fieldstr)
			  SELECT
			    {$extractstr}
			  FROM JSON_EACH(?)
			RETURNING *;
ENDOFQUERY;
		$dbh_sqlite=sqliteDBConnect($params);
		if(!$dbh_sqlite){
			$err=array(
				'msg'=>"sqliteAddDBRecordsProcess error",
				'error'	=> $dbh_sqlite->lastErrorMsg(),
				'query'	=> $query
			);
	    	debugValue(array("sqliteAddDBRecord Connect Error",$err));
	    	return 0;
		}
		//enable exceptions
		$dbh_sqlite->enableExceptions(true);
		try{
			$stmt=$dbh_sqlite->prepare($query);
			//bind the jsonstring to the prepared statement
			$stmt->bindParam(1,$jsonstr,SQLITE3_TEXT);
			$results=$stmt->execute();
			$recs=sqliteEnumQueryResults($results,$params);
			return count($recs);
		}
		catch (Exception $e) {
			$msg=$e->getMessage();
			debugValue(array(
				'function'=>'sqliteAddDBRecordsProcess',
				'message'=>'query failed',
				'error'=>$msg,
				'query'=>$query,
				'params'=>$params
			));
			return 0;
		}
	}
	//JSON method did not work, try standard prepared statement method
	$query="INSERT INTO {$table} ({$fieldstr}) VALUES ".PHP_EOL;
	$values=array();
	foreach($recs as $i=>$rec){
		foreach($rec as $k=>$v){
			if(!in_array($k,$fields)){
				unset($rec[$k]);
				continue;
			}
			if(!strlen($v)){
				$rec[$k]='NULL';
			}
			else{
				$v=sqliteEscapeString($v);
				$rec[$k]="'{$v}'";
			}
		}
		$values[]='('.implode(',',array_values($rec)).')';
	}
	$query.=implode(','.PHP_EOL,$values);
	if(isset($params['-upsert']) && isset($params['-upserton'])){
		if(!is_array($params['-upsert'])){
			$params['-upsert']=preg_split('/\,/',$params['-upsert']);
		}
		/*
			ON CONFLICT (id) DO UPDATE SET 
			  id=excluded.id, username=excluded.username,
			  password=excluded.password, level=excluded.level,email=excluded.email
		*/
		if(strtolower($params['-upsert'][0])=='ignore'){
			$query.=PHP_EOL."ON CONFLICT ({$params['-upserton']}) DO NOTHING";
		}
		else{
			$query.=PHP_EOL."ON CONFLICT ({$params['-upserton']}) DO UPDATE SET";
			$flds=array();
			foreach($params['-upsert'] as $fld){
				$flds[]="{$fld}=excluded.{$fld}";
			}
			$query.=PHP_EOL.implode(', ',$flds);
			if(isset($params['-upsertwhere'])){
				$query.=" WHERE {$params['-upsertwhere']}";
			}
		}
	}
	$ok=sqliteExecuteSQL($query);
	return count($values);
}


#---------- begin function connect ----------
# @describe returns a database connection
# @param params tuple - parameters to override
# @return 
#	cur_mssql, conn_mssql array
# @usage 
#	cur_mssql, conn_mssql =  sqlitedb.connect(params)
def connect(params):
	dbconfig = {}
	#check config.CONFIG
	if 'dbname' in config.CONFIG:
		dbconfig['database'] = config.CONFIG['dbname']

	#check params and override any that are passed in
	if 'dbname' in params:
		dbconfig['database'] = params['dbname']

	try:
		conn_sqlite = sqlite3.connect(dbconfig['database'])
	except Exception as err:
		common.abort(sys.exc_info(),err)

	try:
		cur_sqlite=conn_sqlite.cursor()
	except Exception as err:
		common.abort(sys.exc_info(),err)

	return cur_sqlite, conn_sqlite

#---------- begin function executeSQL ----------
# @describe executes a query
# @param query str - SQL query to run
# @param params tuple - parameters to override
# @return 
#	boolean
# @usage 
#	ok =  sqlitedb.executeSQL(query,params)
def executeSQL(query,params):
	try:
		#connect
		cur_sqlite, conn_sqlite =  connect(params)
		#now execute the query
		cur_sqlite.execute(query)
		conn_sqlite.commit()
		return True
		
	except Exception as err:
		cur_sqlite.close()
		conn_sqlite.close()
		return common.debug(sys.exc_info(),err)
	
#---------- begin function queryResults ----------
# @describe executes a query and returns list of records
# @param query str - SQL query to run
# @param params tuple - parameters to override
# @return 
#   recordsets list
# @usage 
#   recs =  sqlitedb.queryResults(query,params)
def queryResults(query,params):
	try:
		#connect
		cur_sqlite, conn_sqlite =  connect(params)
		#now execute the query
		cur_sqlite.execute(query)
		#get column names - lowercase them for consistency
		fields = [field_md[0].lower() for field_md in cur_sqlite.description]

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
			    rows = cur_sqlite.fetchmany(fetch_size)
			    if not rows:
			        break
			    else:
			        csvwriter.writerows(rows)
			f.close()
			cur_sqlite.close()
			conn_sqlite.close()
			return params['filename']
		else:
			recs = []
			for rec in cur_sqlite.fetchall():
				#convert to a dictionary manually since it is not built into the driver
				rec=dict(zip(fields, rec))
				#call json.dumps to convert date objects to strings in results
				rec=json.loads(json.dumps(rec,sort_keys=False, ensure_ascii=True, default=db.convertStr))
				recs.append(rec)
			cur_sqlite.close()
			conn_sqlite.close()
			return recs
		
	except Exception as err:
		cur_sqlite.close()
		conn_sqlite.close()
		return common.debug(sys.exc_info(),err)
###########################################
