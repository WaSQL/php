<?php

/*
	HANA System Tables
		http://sapbw.optimieren.de/hana/hana/html/monitor_views.html

		Sequences:
		--SELECT * FROM sequences WHERE sequence_oid LIKE '%1157363%'
		--SELECT * FROM sequences WHERE sequence_name LIKE '%1157363%'
		--SELECT table_name,column_name, column_id FROM table_columns WHERE table_name ='SAP_FLASH_CARDS' AND column_name='ID'
		SELECT BICOMMON."_SYS_SEQUENCE_1157363_#0_#".CURRVAL FROM DUMMY;

*/
//---------- begin function hanaAddDBRecords--------------------
/**
* @describe add multiple records into a table
* @param table string - tablename
* @param params array - 
*	[-recs] array - array of records to insert into specified table
*	[-csv] array - csv file of records to insert into specified table
*	[-use_json] - 0|1 - set -use_json=0 to not use the JSON_TABLE method
* @return count int
* @usage $ok=hanaAddDBRecords('comments',array('-csv'=>$afile);
* @usage $ok=hanaAddDBRecords('comments',array('-recs'=>$recs);
* 
* $conn = odbc_connect("CData ODBC SAPHANA Source","user","password");
* $query = odbc_prepare($conn, "SELECT * FROM buckets WHERE Name = ?,?,?");
* $success = odbc_execute($query, array('TestBucket'));
*
*/
function hanaAddDBRecords($table='',$params=array()){
	if(!strlen($table)){
		debugValue(array(
			'function'=>'hanaAddDBRecords',
			'message'=>'No Table specified',
			'params'=>$params
		));
		return 0;
	}
	if(!isset($params['-chunk'])){$params['-chunk']=1000;}
	//set chunk max to 50,000
	if((integer)$params['-chunk'] > 50000){$params['-chunk']=50000;}
	$params['-chunk']=(integer)$params['-chunk'];
	$params['-table']=$table;
	//require either -recs or -csv
	if(!isset($params['-recs']) && !isset($params['-csv'])){
		debugValue(array(
			'function'=>'hanaAddDBRecords',
			'message'=>'either -csv or -recs is required',
			'params'=>$params
		));
		return 0;
	}
	if(isset($params['-csv'])){
		if(!is_file($params['-csv'])){
				debugValue(array(
				'function'=>'hanaAddDBRecords',
				'message'=>'invalid csv file',
				'params'=>$params
			));
			return 0;
		}
		return processCSVLines($params['-csv'],'hanaAddDBRecordsProcess',$params);
	}
	elseif(isset($params['-recs'])){
		if(!is_array($params['-recs'])){
			debugValue(array(
				'function'=>'hanaAddDBRecords',
				'message'=>'-recs is not an array',
				'params'=>$params
			));
			return 0;
		}
		elseif(!count($params['-recs'])){
			debugValue(array(
				'function'=>'hanaAddDBRecords',
				'message'=>'-recs is empty',
				'params'=>$params
			));
			return 0;
		}
		return hanaAddDBRecordsProcess($params['-recs'],$params);
	}
}
function hanaAddDBRecordsProcess($recs,$params=array()){
	global $dbh_hana;
	if(!isset($params['-table'])){
		debugValue(array(
			'function'=>'hanaAddDBRecordsProcess',
			'message'=>'No table defined'
		));
		return 0; 
	}
	if(!is_array($recs) || !count($recs)){
		debugValue(array(
			'function'=>'hanaAddDBRecordsProcess',
			'message'=>'no recs',
		));
		return 0; 
	}
	$table=$params['-table'];
	if(isset($params['-fieldinfo'])){
		$fieldinfo=$params['-fieldinfo'];
	}
	else{
		$fieldinfo=hanaGetDBFieldInfo($table,1);
	}
	if(!is_array($fieldinfo) || !count($fieldinfo)){
		debugValue(array(
			'function'=>'hanaAddDBRecordsProcess',
			'message'=>'no fieldinfo',
		));
		return 0; 
	}
	//indexes must be normal - fix if not
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
			if(stringContains($k,"/")){
				$orik=$k;
				$k='"'.strtoupper($k).'"';
				$fieldinfo[$k]=$fieldinfo[$orik];
			}
			if(!in_array($k,$fields)){$fields[]=$k;}
		}
		break;
	}
	if(!count($fields)){
		debugValue(array(
			'function'=>'hanaAddDBRecordsProcess',
			'message'=>'No fields in first_rec that match fieldinfo',
			'first_rec'=>$first_rec,
			'fieldinfo_keys'=>array_keys($fieldinfo)
		));
		return 0;
	}
	$fieldstr=implode(',',$fields);
	//if possible use the JSON way so we can insert more efficiently
	if(!isset($params['-use_json'])){$params['-use_json']=1;}
	if($params['-use_json']==1){
		$jsonstr=encodeJSON($recs,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
	}
	else{$jsonstr='';}
	if(strlen($jsonstr)){
		//make sure we can connect
		if(!is_resource($dbh_hana)){
			$dbh_hana=hanaDBConnect($params);
		}
		if(!$dbh_hana){
			debugValue(array(
				'function'=>'hanaAddDBRecordsProcess',
				'message'=>'hanaDBConnect error',
				'error'=>odbc_errormsg(),
				'query'=>$query,
				'params'=>$params
			));
			return 0;
		}
		//store JSON in a temp file to allow larger datasets
		$pvalues=array($jsonstr);
		//define field_defs
		//Acceptable datatypes for regular column of JSON table are VARCHAR(n), NVARCHAR(n), INT, BIGINT, DOUBLE, DECIMAL, SMALLDECIMAL, TIMESTAMP, SECONDDATE, DATE and TIME
		$field_defs=array();
		$jfields=array();
		foreach($fields as $field){
			switch(strtolower($fieldinfo[$field]['_dbtype'])){
				case 'char':
				case 'nchar':
					$type=str_replace('char','varchar',$fieldinfo[$field]['_dbtype_ex']);
					$pfield=str_replace("\"",'',$field);
					$field_defs[]="		{$field} {$type} PATH '\$.{$pfield}'";
					$jfields[]="IFNULL({$field},'') as {$field}";
				break;
				case 'varchar':
				case 'nvarchar':
					$type=$fieldinfo[$field]['_dbtype_ex'];
					$pfield=str_replace("\"",'',$field);
					$field_defs[]="		{$field} {$type} PATH '\$.{$pfield}'";
					$jfields[]="IFNULL({$field},'') as {$field}";
				break;
				case 'tinyint':
				case 'smallint':
				case 'integer':
					$type='int';
					$pfield=str_replace("\"",'',$field);
					$field_defs[]="		{$field} {$type} PATH '\$.{$pfield}'";
					$jfields[]="IFNULL({$field},0) as {$field}";
				break;
				case 'bigint':
					$type='bigint';
					$pfield=str_replace("\"",'',$field);
					$field_defs[]="		{$field} {$type} PATH '\$.{$pfield}'";
					$jfields[]="IFNULL({$field},0) as {$field}";
				break;
				case 'varbinary':
					$type='nvarchar';
					$pfield=str_replace("\"",'',$field);
					$field_defs[]="		{$field} {$type} PATH '\$.{$pfield}'";
					$jfields[]="COALESCE(BINTOSTR({$field}),'') as {$field}";
				break;
				case 'decimal':
				case 'smalldecimal':
					$type=$fieldinfo[$field]['_dbtype'];
					$pfield=str_replace("\"",'',$field);
					$field_defs[]="		{$field} {$type} PATH '\$.{$pfield}'";
					$jfields[]="IFNULL({$field},0) as {$field}";
				break;
				case 'real':
				case 'double':
					$type='double';
					$pfield=str_replace("\"",'',$field);
					$field_defs[]="		{$field} {$type} PATH '\$.{$pfield}'";
					$jfields[]="IFNULL({$field},0) as {$field}";
				break;
				default:
					//echo $field.printValue($fieldinfo[$field]);exit;
					$type=$fieldinfo[$field]['_dbtype'];
					$pfield=str_replace("\"",'',$field);
					$field_defs[]="		{$field} {$type} PATH '\$.{$pfield}'";
					$jfields[]="IFNULL({$field},'') as {$field}";
				break;
			}
		}
		//build and test selectquery
		$jfieldstr=implode(','.PHP_EOL,$jfields);
		$selectquery="SELECT {$jfieldstr}".PHP_EOL;
		$selectquery.=" FROM JSON_TABLE(".PHP_EOL;
		$selectquery.="?,".PHP_EOL;
		$selectquery.="'\$[*]' COLUMNS (".PHP_EOL;
		//insert field_defs into query 
    	$selectquery.=implode(','.PHP_EOL,$field_defs); 
    	$selectquery.="		)".PHP_EOL; 
		$selectquery.="	) jt".PHP_EOL;
		//echo $selectquery.PHP_EOL.$jsonstr.PHP_EOL;exit;
		
		//echo "JSON".count($recs).printValue($params);exit;
		if(isset($params['-upsert']) && isset($params['-upserton'])){
			if(!is_array($params['-upserton'])){
				$params['-upserton']=preg_split('/\,/',$params['-upserton']);
			}
			if(!is_array($params['-upsert'])){
				if($params['-upsert']=='*'){
					$params['-upsert']=$fields;
					foreach($params['-upsert'] as $p=>$ufld){
						if(in_array($ufld,$params['-upserton'])){
							unset($params['-upsert'][$p]);
						}
					}
				}
				else{$params['-upsert']=preg_split('/\,/',$params['-upsert']);}
			}
			
			$query="MERGE INTO {$table} T1 USING ( ".PHP_EOL;
			$query.=$selectquery;
			$query.=') T2'.PHP_EOL.'ON  ';
			$onflds=array();
			foreach($params['-upserton'] as $fld){
				$fld=trim($fld);
				$onflds[]="T1.{$fld}=T2.{$fld}";
			}
			$query .= implode(' AND ',$onflds);
			$condition='';
			if(isset($params['-upsertwhere'])){
				$condition=" AND {$params['-upsertwhere']}";
			}
			$query .= ' '.PHP_EOL."WHEN MATCHED {$condition} THEN UPDATE SET ";
			$flds=array();
			foreach($params['-upsert'] as $fld){
				$fld=trim($fld);
				if(!in_array($fld,$fields)){continue;}
				if(!isset($fieldinfo[$fld])){continue;}
				$flds[]="T1.{$fld}=T2.{$fld}";
			}
			$query.=implode(', ',$flds);
			
			$query .= PHP_EOL."WHEN NOT MATCHED THEN INSERT  ";
			$query .= PHP_EOL."({$fieldstr})";
			$query .= PHP_EOL."VALUES ( ";
			$flds=array();
			foreach($fields as $fld){
				$fld=trim($fld);
				$flds[]="T2.{$fld}";
			}
			$query.=PHP_EOL.implode(', ',$flds);
			$query .=PHP_EOL. ')';
		}
		else{
			$query="INSERT INTO {$table} ({$fieldstr}) ( ".PHP_EOL;
			$query.=$selectquery;
			$query.=')';
		}
		//echo nl2br($query).printValue($pvalues).printValue($params);exit;
		if($resource = odbc_prepare($dbh_hana, $query)){
			if(odbc_execute($resource, $pvalues)){
				//odbc_num_rows holds the number of rows affected
				$rcnt=odbc_num_rows($resource);
				//echo "yo: ".printValue($rcnt).printValue($params).count($recs);exit;
				if(file_exists($atfile)){unlink($atfile);}
				if(is_resource($resource) && get_resource_type($resource)=='odbc result'){
					odbc_free_result($resource);
				}
				$resource=null;
				if(is_resource($dbh_hana) && stringBeginsWith(get_resource_type($dbh_hana),'odbc link')){
					odbc_close($dbh_hana);
				}
				$dbh_hana=null;
				return count($recs);
			}
			else{
				if(file_exists($atfile)){unlink($atfile);}
				$drec=array();
				foreach($recs as $drec){
					break;
				}
				debugValue(array(
					'function'=>'hanaAddDBRecordsProcess',
					'message'=>'odbc execute error',
					'error'=>odbc_errormsg(),
					'query'=>$query,
					'first_record'=>$drec
				));
				if(is_resource($dbh_hana) && stringBeginsWith(get_resource_type($dbh_hana),'odbc link')){
					odbc_close($dbh_hana);
				}
				$dbh_hana=null;
				return 0;
			}
		}
		else{
			if(file_exists($atfile)){unlink($atfile);}
			$drec=array();
			foreach($recs as $drec){
				break;
			}
			debugValue(array(
				'function'=>'hanaAddDBRecordsProcess',
				'message'=>'odbc prepare error',
				'error'=>odbc_errormsg(),
				'query'=>$query,
				'first_record'=>$drec
			));
			return 0;
		}
	}
	//JSON method did not work, try standard prepared statement method
	//keep prepared statement markers under 20000
	$fieldcount=count($fields);
	$maxchunksize=ceil(20000/$fieldcount);
	if(!isset($params['-chunk'])){
		$params['-chunk']=$maxchunksize;
	}
	if($params['-chunk'] > $maxchunksize){$params['-chunk']=$maxchunksize;}
	$chunks=array_chunk($recs,$params['-chunk']);
	$chunk_size=count($chunks[0]);
	$total_count=0;
	foreach($chunks as $c=>$recs){
		//check for -timeout
		$pvalues=array();
		$values=array();
		foreach($recs as $i=>$rec){
			$pvals=array();
			foreach($rec as $k=>$v){
				if(!in_array($k,$fields)){continue;}
				if(!strlen($v)){
					$pvals[]='? as '.$k;
					$pvalues[]=null;
				}
				else{
					if(isset($params['-iconv'])){
						$v=iconv("ISO-8859-1", "UTF-8//TRANSLIT", $v);
					}
					$pvals[]='? as '.$k;
					$pvalues[]=$v;
				}
			}
			$recstr=implode(',',$pvals);
			$values[]="SELECT {$recstr} FROM dummy";
		}
		if($c > 0 && count($recs)==$chunk_size){
			$ok = odbc_execute($stmt, $pvalues);
			if (odbc_error()){
				$drecs=array();
				$xchunks=array_chunk($pvalues,count($fields));
				foreach($xchunks as $xchunk){
					$rec=array();
					foreach($fields as $i=>$fld){
						//if($fld != 'dist_id'){continue;}
						$fld="{$fld} ({$fieldinfo[$fld]['_dbtype']})";
						$drecs[$fld][$xchunk[$i]]+=1;
					}
					break;
				}
				debugValue(array(
					'function'=>'hanaAddDBRecordsProcess',
					'message'=>'odbc execute error',
					'error'=>odbc_errormsg($stmt),
					'query'=>$query,
					'params'=>$params,
					'first_record'=>$drecs
				));
				return $total_count;
			}
			$total_count+=count($recs);
			continue;
		}
		if(isset($params['-upsert']) && isset($params['-upserton'])){
			if(!is_array($params['-upsert'])){
				$params['-upsert']=preg_split('/\,/',$params['-upsert']);
			}
			if(!is_array($params['-upserton'])){
				$params['-upserton']=preg_split('/\,/',$params['-upserton']);
			}
			$query="MERGE INTO {$table} T1 USING ( ".PHP_EOL;
			$query.=implode(PHP_EOL.'UNION ALL'.PHP_EOL,$values);
			$query.=') T2'.PHP_EOL.'ON  ';
			$onflds=array();
			foreach($params['-upserton'] as $fld){
				$fld=trim($fld);
				$onflds[]="T1.{$fld}=T2.{$fld}";
			}
			$query .= implode(' AND ',$onflds);
			$condition='';
			if(isset($params['-upsertwhere'])){
				$condition=" AND {$params['-upsertwhere']}";
			}
			$query .= ' '.PHP_EOL."WHEN MATCHED {$condition} THEN UPDATE SET ";
			$flds=array();
			foreach($params['-upsert'] as $fld){
				$fld=trim($fld);
				if(!in_array($fld,$fields)){continue;}
				if(!isset($fieldinfo[$fld])){continue;}
				$flds[]="T1.{$fld}=T2.{$fld}";
			}
			$query.=implode(', ',$flds);
			
			$query .= PHP_EOL."WHEN NOT MATCHED THEN INSERT  ";
			$query .= PHP_EOL."({$fieldstr})";
			$query .= PHP_EOL."VALUES ( ";
			$flds=array();
			foreach($fields as $fld){
				$fld=trim($fld);
				$flds[]="T2.{$fld}";
			}
			$query.=PHP_EOL.implode(', ',$flds);
			$query .=PHP_EOL. ')';
		}
		else{
			$query="INSERT INTO {$table} ({$fieldstr}) ( ".PHP_EOL;
			$query.=implode(PHP_EOL.'UNION ALL'.PHP_EOL,$values);
			$query.=')';
		}
		if(isset($params['-debug'])){
			$drecs=array();
			$xchunks=array_chunk($pvalues,count($fields));
			$errors=array();
			foreach($xchunks as $xchunk){
				$rec=array();
				foreach($fields as $i=>$fld){
					if(strlen($xchunk[$i]) > $fieldinfo[$fld]['_dblength']){
						$errors[]="{$fld} value in record {$i} is too long";
					}
					if($fieldinfo[$fld]['nullable'] !=1 && !strlen($xchunk[$i])){
						$errors[]="{$fld} value in record {$i} cannot be null";
					}
					$rec[$fld]=$xchunk[$i];
				}
				$drecs[]=$rec;
			}
			return "Fields:".printValue($fields).PHP_EOL."fieldinfo:".printValue($fieldinfo).PHP_EOL."Query:{$query}".PHP_EOL."errors".printValue($errors).PHP_EOL.'drecs'.printValue($drecs);
		}

		if(!is_resource($dbh_hana)){
			$dbh_hana=hanaDBConnect($params);
		}
		if(!$dbh_hana){
			debugValue(array(
				'function'=>'hanaAddDBRecordsProcess',
				'message'=>'hanaDBConnect error',
				'error'=>odbc_errormsg(),
				'query'=>$query,
				'params'=>$params
			));
			return 0;
		}
		if($resource = odbc_prepare($dbh_hana, $query)){
			if(odbc_execute($resource, $pvalues)){
				$total_count+=count($recs);
				if(is_resource($resource)){odbc_free_result($resource);}
				$resource=null;
				if(is_resource($dbh_hana) || is_object($dbh_hana)){odbc_close($dbh_hana);}
				$dbh_hana=null;
			}
			else{
				$drecs=array();
				$xchunks=array_chunk($pvalues,count($fields));
				foreach($xchunks as $xchunk){
					$rec=array();
					foreach($fields as $i=>$fld){
						//if($fld != 'dist_id'){continue;}
						$fld="{$fld} ({$fieldinfo[$fld]['_dbtype']})";
						$drecs[$fld][$xchunk[$i]]+=1;
					}
					break;
				}
				if(is_resource($resource)){$error=odbc_errormsg($resource);}
				elseif(is_resource($dbh_hana)){$error=odbc_errormsg($dbh_hana);}
				else{$error=odbc_errormsg();}
				debugValue(array(
					'function'=>'hanaAddDBRecordsProcess',
					'message'=>'odbc execute error',
					'error'=>$error,
					'query'=>$query,
					'params'=>$params,
					'first_record'=>$drecs
				));
				return 0;
			}
		}
		else{
			debugValue(array(
				'function'=>'hanaAddDBRecordsProcess',
				'message'=>'odbc prepare error',
				'error'=>odbc_errormsg($dbh_hana),
				'query'=>$query,
				'params'=>$params
			));
			return 0;
		}
	}
	if(isset($params['-debug'])){
		return printValue($ok).$query;
	}
	return $total_count;
}
//---------- begin function hanaAddDBFields--------------------
/**
* @describe adds fields to given table
* @param table string - name of table to alter
* @param params array - list of field/attributes to edit
* @return array - name,type,query,result for each field set
* @usage
*	$ok=hanaAddDBFields('comments',array('comment'=>"varchar(1000) NULL"));
*/
function hanaAddDBFields($table,$fields=array(),$maintain_order=1){
	$recs=array();
	foreach($fields as $name=>$type){
		$crec=array('name'=>$name,'type'=>$type);
		$fieldstr="{$name} {$type}";
		$crec['query']="ALTER TABLE {$table} ADD ({$fieldstr})";
		$crec['result']=hanaExecuteSQL($crec['query']);
		$recs[]=$crec;
	}
	return $recs;
}
//---------- begin function hanaDropDBFields--------------------
/**
* @describe drops fields to given table
* @param table string - name of table to alter
* @param params array - list of fields
* @return array - name,query,result for each field
* @usage
*	$ok=hanaDropDBFields('comments',array('comment','age'));
*/
function hanaDropDBFields($table,$fields=array()){
	$recs=array();
	foreach($fields as $name){
		$crec=array('name'=>$name);
		$crec['query']="ALTER TABLE {$table} DROP ({$name})";
		$crec['result']=hanaExecuteSQL($crec['query']);
		$recs[]=$crec;
	}
	return $recs;
}
//---------- begin function hanaAlterDBTable--------------------
/**
* @describe alters fields in given table
* @param table string - name of table to alter
* @param params array - list of field/attributes to edit
* @param [maintain_order] boolean - try to maintain field order - defaults to 1
* @return mixed - 1 on success, error string on failure
* @usage
*	$ok=hanaAlterDBTable('comments',array('comment'=>"varchar(1000) NULL"));
*/
function hanaAlterDBTable($table,$fields=array(),$maintain_order=1){
	$info=hanaGetDBFieldInfo($table);
	if(!is_array($info) || !count($info)){
		debugValue("hanaAlterDBTable - {$table} is missing or has no fields".printValue($table));
		return false;
	}
	$rtn=array();
	//$rtn[]=$info;
	$addfields=array();
	foreach($fields as $name=>$type){
		$lname=strtolower($name);
		$uname=strtoupper($name);
		if(isset($info[$name]) || isset($info[$lname]) || isset($info[$uname])){continue;}
		$addfields[]="{$name} {$type}";
	}
	$dropfields=array();
	foreach($info as $name=>$finfo){
		$lname=strtolower($name);
		$uname=strtoupper($name);
		if(!isset($fields[$name]) && !isset($fields[$lname]) && !isset($fields[$uname])){
			$dropfields[]=$name;
		}
	}
	if(count($dropfields)){
		$fieldstr=implode(', ',$dropfields);
		$query="ALTER TABLE {$table} DROP ({$fieldstr})";
		$ok=hanaExecuteSQL($query);
		$rtn[]=$query;
		$rtn[]=$ok;
	}
	if(count($addfields)){
		$fieldstr=implode(', ',$addfields);
		$query="ALTER TABLE {$table} ADD ({$fieldstr})";
		$ok=hanaExecuteSQL($query);
		$rtn[]=$query;
		$rtn[]=$ok;
	}
	return $rtn;
}
//---------- begin function hanaCreateDBTable--------------------
/**
* @describe creates hana table with specified fields
* @param table string - name of table to alter
* @param params array - list of field/attributes to add
* @return mixed - 1 on success, error string on failure
* @usage
*	$ok=hanaCreateDBTable($table,array($field=>"varchar(255) NULL",$field2=>"int NOT NULL"));
*/
function hanaCreateDBTable($table='',$fields=array()){
	$function='createDBTable';
	if(strlen($table)==0){return "hanaCreateDBTable error: No table";}
	if(count($fields)==0){return "hanaCreateDBTable error: No fields";}
	//check for schema name
	$schema=hanaGetDBSchema();
	if(!stringContains($table,'.')){
		if(strlen($schema)){
			$table="{$schema}.{$table}";
		}
	}
	if(hanaIsDBTable($table)){return 0;}
	global $CONFIG;	
	//lowercase the tablename and replace spaces with underscores
	$table=strtolower(trim($table));
	$table=str_replace(' ','_',$table);
	$ori_table=$table;
	if(strlen($schema) && !stringContains($table,$schema)){
		$table="{$schema}.{$table}";
	}
	$query="create table {$table} (".PHP_EOL;
	$lines=array();
	foreach($fields as $field=>$attributes){
		//datatype conversions
		$attributes=str_replace('tinyint','smallint',$attributes);
		$attributes=str_replace('mediumint','integer',$attributes);
		$attributes=str_replace('datetime','timestamp',$attributes);
		$attributes=str_replace('float','real',$attributes);
		//lowercase the fieldname and replace spaces with underscores
		$field=strtolower(trim($field));
		$field=str_replace(' ','_',$field);
		$lines[]= "	{$field} {$attributes}";
   	}
    $query .= implode(','.PHP_EOL,$lines).PHP_EOL;
    $query .= ")".PHP_EOL;
    return hanaExecuteSQL($query);
}
function hanaEscapeString($str){
	$str = str_replace("'","''",$str);
	return $str;
}
//---------- begin function hanaGetTableDDL ----------
/**
* @describe returns create script for specified table
* @param table string - tablename
* @param [schema] string - schema. defaults to dbschema specified in config
* @return string
* @usage $createsql=hanaGetTableDDL('sample');
* @link https://stackoverflow.com/questions/26237174/show-create-table-equivalent-in-sap-hana
*/
function hanaGetTableDDL($table,$schema=''){
	if(!strlen($schema)){
		$schema=hanaGetDBSchema();
	}
	$parts=preg_split('/\./',$table,2);
	if(count($parts)==2){
		$schema=$parts[0];
		$table=$parts[1];
	}
	if(!strlen($schema)){
		debugValue('hanaGetTableDDL error: schema is not defined in config.xml');
		return null;
	}
	$schema=strtoupper($schema);
	$table=strtoupper($table);
	$query=<<<ENDOFQUERY
		call get_object_definition('{$schema}','{$table}')
ENDOFQUERY;
	$recs=hanaQueryResults($query);
	if(isset($recs[0]['object_creation_statement'])){
		return $recs[0]['object_creation_statement'];
	}
	if(isset($recs[0]['ddl'])){
		return $recs[0]['ddl'];
	}
	return printValue($recs);
}
//---------- begin function hanaGetAllTableFields ----------
/**
* @describe returns fields of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allfields=hanaGetAllTableFields();
*/
function hanaGetAllTableFields($schema=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'hanaGetAllTableFields');
	if(isset($databaseCache[$cachekey])){
		return $databaseCache[$cachekey];
	}
	if(!strlen($schema)){
		$schema=hanaGetDBSchema();
	}
	if(!strlen($schema)){
		debugValue('hanaGetAllTableFields error: schema is not defined in config.xml');
		return null;
	}
	$query=<<<ENDOFQUERY
		SELECT
			table_name as table_name,
			column_name as field_name,
			data_type_name as type_name
		FROM table_columns
		WHERE
			schema_name='{$schema}'
		ORDER BY 1,2
ENDOFQUERY;
	$recs=hanaQueryResults($query);
	$databaseCache[$cachekey]=array();
	foreach($recs as $rec){
		$table=strtolower($rec['table_name']);
		$field=strtolower($rec['field_name']);
		$type=strtolower($rec['type_name']);
		$databaseCache[$cachekey][$table][]=$rec;
	}
	return $databaseCache[$cachekey];
}
//---------- begin function hanaGetAllTableIndexes ----------
/**
* @describe returns indexes of all tables with the table name as the index
* @param [$schema] string - schema. defaults to dbschema specified in config
* @return array
* @usage $allindexes=hanaGetAllTableIndexes();
*/
function hanaGetAllTableIndexes($schema=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'hanaGetAllTableIndexes');
	if(isset($databaseCache[$cachekey])){
		return $databaseCache[$cachekey];
	}
	if(!strlen($schema)){
		$schema=hanaGetDBSchema();
	}
	if(!strlen($schema)){
		debugValue('hanaGetAllTableIndexes error: schema is not defined in config.xml');
		return null;
	}
	//key_name,column_name,seq_in_index,non_unique
	$query=<<<ENDOFQUERY
	SELECT
		si.table_name as table_name,
		si.index_name as index_name,
		string_agg(sic.column_name,',') as index_keys,
		case index_type when 'CPBTREE UNIQUE' then 1 else 0 end as is_unique
	FROM sys.indexes si, sys.index_columns sic
	WHERE 
		si.index_name = sic.index_name
		and schema_name='{$schema}'
	GROUP BY 
		si.table_name,
		si.index_name,
		case index_type when 'CPBTREE UNIQUE' then 1 else 0 end
ENDOFQUERY;
	$recs=hanaQueryResults($query);
	//echo "{$CONFIG['db']}--{$schema}".$query.'<hr>'.printValue($recs);exit;
	$databaseCache[$cachekey]=array();
	foreach($recs as $rec){
		$table=strtolower($rec['table_name']);
		$table=str_replace("{$schema}.",'',$table);
		$index_keys=preg_split('/\,+/',$rec['index_keys']);
		sort($index_keys);
		$rec['index_keys']=implode(', ',$index_keys);
		$databaseCache[$cachekey][$table][]=$rec;
	}
	return $databaseCache[$cachekey];
}
function hanaGetDBSchema(){
	global $CONFIG;
	global $DATABASE;
	$params=hanaParseConnectParams();
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']]['dbschema'])){
		return $DATABASE[$CONFIG['db']]['dbschema'];
	}
	elseif(isset($CONFIG['dbschema'])){return $CONFIG['dbschema'];}
	elseif(isset($CONFIG['-dbschema'])){return $CONFIG['-dbschema'];}
	elseif(isset($CONFIG['schema'])){return $CONFIG['schema'];}
	elseif(isset($CONFIG['-schema'])){return $CONFIG['-schema'];}
	elseif(isset($CONFIG['hana_dbschema'])){return $CONFIG['hana_dbschema'];}
	elseif(isset($CONFIG['hana_schema'])){return $CONFIG['hana_schema'];}
	return '';
}
function hanaGetDBIndexes($tablename=''){
	return hanaGetDBTableIndexes($tablename);
}
function hanaGetDBTableIndexes($table=''){
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'hanaGetDBTableIndexes'.$table);
	if(isset($databaseCache[$cachekey])){
		return $databaseCache[$cachekey];
	}
	$parts=preg_split('/\./',$table,2);
	if(count($parts)==2){
		$schema=$parts[0];
		$table=$parts[1];
	}
	else{
		$schema=hanaGetDBSchema();
	}
	if(!strlen($schema)){
		debugValue('hanaGetDBTableIndexes error: schema is not defined in config.xml');
		return array('hanaGetDBTableIndexes ERROR');
	}
	$schema=strtoupper($schema);
	$table=strtoupper($table);
	//key_name,column_name,is_primary,is_unique,seq_in_index
	$query=<<<ENDOFQUERY
	SELECT
		lower(constraint_name) as key_name,
		lower(column_name) as column_name,
		is_primary_key as is_primary,
		is_unique_key as is_unique,
		position as seq_in_index
	FROM sys.constraints
	WHERE
		schema_name='{$schema}'
		and table_name='{$table}'
	ORDER BY 
		constraint_name,
		position
ENDOFQUERY;
	$recs=hanaQueryResults($query);
	foreach($recs as $i=>$rec){
		switch(strtolower($rec['is_primary'])){
			case 't':
			case 'true':
			case 1:
				$recs[$i]['is_primary']=1;
			break;
		}
		switch(strtolower($rec['is_unique'])){
			case 't':
			case 'true':
			case 1:
				$recs[$i]['is_unique']=1;
			break;
		}
	}
	$databaseCache[$cachekey]=$recs;
	return $databaseCache[$cachekey];
}
//---------- begin function hanaGetDBRecordById--------------------
/**
* @describe returns a single multi-dimensional record with said id in said table
* @param table string - tablename
* @param id integer - record ID of record
* @param relate boolean - defaults to true
* @param fields string - defaults to blank
* @return array
* @usage $rec=hanaGetDBRecordById('comments',7);
*/
function hanaGetDBRecordById($table='',$id=0,$relate=1,$fields=""){
	if(!strlen($table)){return "hanaGetDBRecordById Error: No Table";}
	if($id == 0){return "hanaGetDBRecordById Error: No ID";}
	$recopts=array('-table'=>$table,'_id'=>$id);
	if($relate){$recopts['-relate']=1;}
	if(strlen($fields)){$recopts['-fields']=$fields;}
	$rec=hanaGetDBRecord($recopts);
	return $rec;
}
//---------- begin function hanaEditDBRecordById--------------------
/**
* @describe edits a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @param params array - field=>value pairs to edit in this record
* @return boolean
* @usage $ok=hanaEditDBRecordById('comments',7,array('name'=>'bob'));
*/
function hanaEditDBRecordById($table='',$id=0,$params=array()){
	if(!strlen($table)){
		return debugValue("hanaEditDBRecordById Error: No Table");
	}
	//allow id to be a number or a set of numbers
	$ids=array();
	if(is_array($id)){
		foreach($id as $i){
			if(isNum($i) && !in_array($i,$ids)){$ids[]=$i;}
		}
	}
	else{
		$id=preg_split('/[\,\:]+/',$id);
		foreach($id as $i){
			if(isNum($i) && !in_array($i,$ids)){$ids[]=$i;}
		}
	}
	if(!count($ids)){return debugValue("hanaEditDBRecordById Error: invalid ID(s)");}
	if(!is_array($params) || !count($params)){return debugValue("hanaEditDBRecordById Error: No params");}
	if(isset($params[0])){return debugValue("hanaEditDBRecordById Error: invalid params");}
	$idstr=implode(',',$ids);
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return hanaEditDBRecord($params);
}
//---------- begin function hanaDelDBRecord ----------
/**
* @describe deletes records in table that match -where clause
* @param params array
*	-table string - name of table
*	-where string - where clause to filter what records are deleted
* @return boolean
* @usage $id=hanaDelDBRecord(array('-table'=> '_tabledata','-where'=> "_id=4"));
*/
function hanaDelDBRecord($params=array()){
	global $USER;
	if(!isset($params['-table'])){return 'hanaDelDBRecord error: No table specified.';}
	if(!isset($params['-where'])){return 'hanaDelDBRecord Error: No where';}
	//check for schema name
	if(!stringContains($params['-table'],'.')){
		$schema=hanaGetDBSchema();
		if(strlen($schema)){
			$params['-table']="{$schema}.{$params['-table']}";
		}
	}
	$query="delete from {$params['-table']} where " . $params['-where'];
	return hanaExecuteSQL($query);
}
//---------- begin function hanaDelDBRecordById--------------------
/**
* @describe deletes a record with said id in said table
* @param table string - tablename
* @param id mixed - record ID of record or a comma separated list of ids
* @return boolean
* @usage $ok=hanaDelDBRecordById('comments',7,array('name'=>'bob'));
*/
function hanaDelDBRecordById($table='',$id=0){
	if(!strlen($table)){
		return debugValue("hanaDelDBRecordById Error: No Table");
	}
	//allow id to be a number or a set of numbers
	$ids=array();
	if(is_array($id)){
		foreach($id as $i){
			if(isNum($i) && !in_array($i,$ids)){$ids[]=$i;}
		}
	}
	else{
		$id=preg_split('/[\,\:]+/',$id);
		foreach($id as $i){
			if(isNum($i) && !in_array($i,$ids)){$ids[]=$i;}
		}
	}
	if(!count($ids)){return debugValue("hanaDelDBRecordById Error: invalid ID(s)");}
	$idstr=implode(',',$ids);
	$params=array();
	$params['-table']=$table;
	$params['-where']="_id in ({$idstr})";
	$recopts=array('-table'=>$table,'_id'=>$id);
	return hanaDelDBRecord($params);
}
//---------- begin function hanaListRecords
/**
* @describe returns an html table of records from a hana database. refer to databaseListRecords
*/
function hanaListRecords($params=array()){
	$params['-database']='hana';
	return databaseListRecords($params);
}

//---------- begin function hanaAddDBRecordsFromCSV ----------
/**
* @describe imports data from csv into a HANA table
* @param $table string - table to import into
* @param $csv - csvfile to import (path visible by the HANA server)
* @param $params array - These can also be set in the CONFIG file with dbname_hana,dbuser_hana, and dbpass_hana
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	[-localfile] - path to localfile if different than csvfile that DB can see.
* 	[-delim] - delimiter. Default is ,
* 	[-enclose] - enclosed by. Default is "
* 	[-eol] - end of line char.  Default is \n
* 	[-threads] - number of threads. Default is 10
* 	[-batch] - number of records to commit in batch. Default is 10,000
* 	[-skip] - number of top rows to skip if any
* 	[-columns] - comma separated list of column name list. Defaults to use first row as column name list
* 	[-date] - format of date columns in csv file. Y=year, MM=month, MON=name of month, DD=day
* 	[-time] - format of time columns in csv file. HH24=hour, MI=minute, SS=second
* 	[-timestamp] format of timestamp columns in csv file. Y=year, MM=month, MON=name of month, DD=day, HH24=hour, MI=minute, SS=second
* 	[-nofail] - continue instead of failing on errors. Defaults to failing on errors.
* 	[-checktype] - check data types on insert. Defaults to not check.
* @return $errors array
* @usage 
*	loadExtras('hana');
*	$ok=hanaAddDBRecordsFromCSV('stgschema.testtable','/mnt/dtxhana/test.csv');
*/
function hanaAddDBRecordsFromCSV($table,$csvfile,$params=array()){
	//error log with same name as csvfile in same path so HANA can write to it.
	$error_log= preg_replace('/\.csv$/i', '.errors', $csvfile);
	if(!isset($params['-localfile'])){$params['-localfile']=$csvfile;}
	$local_error_log= preg_replace('/\.csv$/i', '.errors', $params['-localfile']);
	/*
	 * References
	 * 	https://help.sap.com/viewer/4fe29514fd584807ac9f2a04f6754767/2.0.00/en-US/20f712e175191014907393741fadcb97.html
	 * 	https://blogs.sap.com/2013/04/08/best-practices-for-sap-hana-data-loads/
	 * 	https://blogs.sap.com/2014/02/12/8-tips-on-pre-processing-flat-files-with-sap-hana/
	 *
	 * THREADS and BATCH provide high loading performance by enabling parallel loading and also by committing many records at once.
	 * In general, for column tables, a good setting to use is 10 parallel loading threads, with a commit frequency of 10,000 records or greater
	 *
	THREADS <number_of_threads>
	* 	Specifies the number of threads that can be used for concurrent import. The default value is 1 and maximum allowed is 256
	BATCH <number_of_records_of_each_commit>
	* 	Specifies the number of records to be inserted in each commit
	TABLE LOCK
	* 	Provides faster data loading for column store tables. Use this option carefully as it incurs table locks in exclusive mode as well as explicit hard merges and save points after data loading is finished.
	NO TYPE CHECK
	* 	Specifies that the records are inserted without checking the type of each field.
	SKIP FIRST <number_of_rows_to_skip>
	* 	Skips the specified number of rows in the import file.
	COLUMN LIST IN FIRST ROW [<with_schema_flexibility>]
	* 	Indicates that the column list is stored in the first row of the CSV import file.
	* 	WITH SCHEMA FLEXIBILITY creates missing columns in flexible tables during CSV imports, as specified in the header (first row) of the CSV file or column list.
	* 	By default, missing columns in flexible tables are not created automatically during data imports.
	COLUMN LIST ( <column_name_list> ) [<with_schema_flexibility>]
	* 	Specifies the column list for the data being imported.
	* 	WITH SCHEMA FLEXIBILITY creates missing columns in flexible tables during CSV imports, as specified in the header (first row) of the CSV file or column list.
	RECORD DELIMITED BY <string_for_record_delimiter>
	* 	Specifies the record delimiter used in the CSV file being imported.
	FIELD DELIMITED BY <string_for_field_delimiter>
	* 	Specifies the field delimiter of the CSV file.
	OPTIONALLY ENCLOSED BY <character_for_optional_enclosure>
	* 	Specifies the optional enclosure character used to delimit field data.
	DATE FORMAT <string_for_date_format>
	* 	Specifies the format that date strings are encoded with in the import data.
	* 	Y : year, MM : month, MON : name of month, DD : day
	TIME FORMAT <string_for_time_format>
	* 	Specifies the format that time strings are encoded with in the import data.
	* 	HH24 : hour, MI : minute, SS : second
	TIMESTAMP FORMAT <string_for_timestamp_format>
	* 	Specifies the format that timestamp strings are encoded with in the import data.  (YYYY-MM-DD HH24:MI:SS)
	ERROR LOG <file_path_of_error_log>
	* 	Specifies that a log file of errors generated is stored in this file. Ensure the file path you use is writable by the database.
	FAIL ON INVALID DATA
	* 	Specifies that the IMPORT FROM command fails unless all the entries import successfully.
	 * */
	if(!isset($params['-delim'])){$params['-delim']=',';}
	if(!isset($params['-enclose'])){$params['-enclose']='"';}
	if(!isset($params['-eol'])){$params['-eol']='\\n';}
	if(!isset($params['-threads'])){$params['-threads']=10;}
	if(!isset($params['-batch'])){$params['-batch']=10000;}
	$with='';
	if(isset($params['-skip'])){
		$with.= "SKIP FIRST ({$params['-skip']}) ".PHP_EOL;
	}
	if(isset($params['-columns'])){
		$with.= "COLUMN LIST '{$params['-columns']}' WITH SCHEMA FLEXIBILITY ".PHP_EOL;
	}
	else{
		$with.= "COLUMN LIST IN FIRST ROW WITH SCHEMA FLEXIBILITY ". PHP_EOL;
	}
	if(isset($params['-date'])){
		$with.= "DATE FORMAT ({$params['-date']}) ".PHP_EOL;
	}
	if(isset($params['-time'])){
		$with.= "TIME FORMAT '{$params['-time']}' ".PHP_EOL;
	}
	if(isset($params['-timestamp'])){
		$with.= "TIMESTAMP FORMAT '{$params['-timestamp']}' ".PHP_EOL;
	}
	if(!isset($params['-nofail'])){
		$with.= "FAIL ON INVALID DATA ".PHP_EOL;
	}
	if(!isset($params['-checktype'])){
		$with.= "NO TYPE CHECK ".PHP_EOL;
	}
	$query=<<<ENDOFQUERY
	IMPORT FROM CSV FILE '{$csvfile}'
	INTO {$table}
	WITH
		RECORD DELIMITED BY '{$params['-eol']}'
		FIELD DELIMITED BY '{$params['-delim']}'
		OPTIONALLY ENCLOSED BY '{$params['-enclose']}'
		{$with}
	THREADS {$params['-threads']}
	BATCH {$params['-batch']}
	ERROR LOG '{$error_log}'
ENDOFQUERY;
	setFileContents($error_log,'');
	//set to a single so we can turn off autocommit
	$params['-single']=1;
	$conn=hanaDBConnect($params);
	odbc_autocommit($conn, FALSE);

	odbc_exec($conn, $query);

	if (!odbc_error()){
		odbc_commit($conn);
	}
	else{
		odbc_rollback($conn);
	}
	odbc_close($conn);
	return file($local_error_log);

}
//---------- begin function hanaParseConnectParams ----------
/**
* @describe parses the params array and checks in the CONFIG if missing
* @param $params array - These can also be set in the CONFIG file with dbname_hana,dbuser_hana, and dbpass_hana
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @exclude  - this function is for internal use and thus excluded from the manual
* @return $params array
* @usage 
*	loadExtras('hana');
*	$params=hanaParseConnectParams($params);
*/
function hanaParseConnectParams($params=array()){
	if(!is_array($params)){$params=array();}
	global $CONFIG;
	global $DATABASE;
	global $USER;
	if(isset($CONFIG['db']) && isset($DATABASE[$CONFIG['db']])){
		foreach($CONFIG as $k=>$v){
			if(preg_match('/^hana/i',$k)){unset($CONFIG[$k]);}
		}
		foreach($DATABASE[$CONFIG['db']] as $k=>$v){
			if(strtolower($k)=='cursor'){
				switch(strtoupper($v)){
					case 'SQL_CUR_USE_ODBC':$params['-cursor']=SQL_CUR_USE_ODBC;break;
					case 'SQL_CUR_USE_IF_NEEDED':$params['-cursor']=SQL_CUR_USE_IF_NEEDED;break;
					case 'SQL_CUR_USE_DRIVER':$params['-cursor']=SQL_CUR_USE_DRIVER;break;
				}
			}
			else{
				$params["-{$k}"]=$v;
			}
			
		}
	}
	//check for user specific
	if(isUser() && strlen($USER['username'])){
		foreach($params as $k=>$v){
			if(stringEndsWith($k,"_{$USER['username']}")){
				$nk=str_replace("_{$USER['username']}",'',$k);
				unset($params[$k]);
				$params[$nk]=$v;
			}
		}
	}
	if(!isset($params['-dbname'])){
		if(isset($CONFIG['hana_connect'])){
			$params['-dbname']=$CONFIG['hana_connect'];
			$params['-dbname_source']="CONFIG hana_connect";
		}
		elseif(isset($CONFIG['dbname_hana'])){
			$params['-dbname']=$CONFIG['dbname_hana'];
			$params['-dbname_source']="CONFIG dbname_hana";
		}
		elseif(isset($CONFIG['hana_dbname'])){
			$params['-dbname']=$CONFIG['hana_dbname'];
			$params['-dbname_source']="CONFIG hana_dbname";
		}
		else{return 'hanaParseConnectParams Error: No dbname set';}
	}
	else{
		$params['-dbname_source']="passed in";
	}
	if(!isset($params['-dbuser'])){
		if(isset($CONFIG['dbuser_hana'])){
			$params['-dbuser']=$CONFIG['dbuser_hana'];
			$params['-dbuser_source']="CONFIG dbuser_hana";
		}
		elseif(isset($CONFIG['hana_dbuser'])){
			$params['-dbuser']=$CONFIG['hana_dbuser'];
			$params['-dbuser_source']="CONFIG hana_dbuser";
		}
		else{return 'hanaParseConnectParams Error: No dbuser set';}
	}
	else{
		$params['-dbuser_source']="passed in";
	}
	if(!isset($params['-dbpass'])){
		if(isset($CONFIG['dbpass_hana'])){
			$params['-dbpass']=$CONFIG['dbpass_hana'];
			$params['-dbpass_source']="CONFIG dbpass_hana";
		}
		elseif(isset($CONFIG['hana_dbpass'])){
			$params['-dbpass']=$CONFIG['hana_dbpass'];
			$params['-dbpass_source']="CONFIG hana_dbpass";
		}
		else{return 'hanaParseConnectParams Error: No dbpass set';}
	}
	else{
		$params['-dbpass_source']="passed in";
	}
	if(isset($CONFIG['hana_cursor'])){
		switch(strtoupper($CONFIG['hana_cursor'])){
			case 'SQL_CUR_USE_ODBC':$params['-cursor']=SQL_CUR_USE_ODBC;break;
			case 'SQL_CUR_USE_IF_NEEDED':$params['-cursor']=SQL_CUR_USE_IF_NEEDED;break;
			case 'SQL_CUR_USE_DRIVER':$params['-cursor']=SQL_CUR_USE_DRIVER;break;
		}
	}
	return $params;
}
//---------- begin function hanaDBConnect ----------
/**
* @describe connects to a HANA database via odbc and returns the odbc resource
* @param $param array - These can also be set in the CONFIG file with dbname_hana,dbuser_hana, and dbpass_hana
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
*   [-single] - if you pass in -single it will connect using odbc_connect instead of odbc_pconnect and return the connection
* @return $dbh_hana resource - returns the odbc connection resource
* @exclude  - this function is for internal use and thus excluded from the manual
* @usage 
*	loadExtras('hana');
*	$dbh_hana=hanaDBConnect($params);
*/
function hanaDBConnect($params=array()){
	if(!is_array($params) && $params=='single'){$params=array('-single'=>1);}
	$params=hanaParseConnectParams($params);
	if(isset($params['-connect'])){
		$connect_name=$params['-connect'];
	}
	elseif(isset($params['-dbname'])){
		$connect_name=$params['-dbname'];
	}
	else{
		echo "hanaDBConnect error: no dbname or connect param".printValue($params);
		exit;
	}
	//echo printValue($params);exit;
	if(isset($params['-single'])){
		if(isset($params['-cursor'])){
			$dbh_hana_single = odbc_connect($connect_name,$params['-dbuser'],$params['-dbpass'],$params['-cursor'] );
		}
		else{
			$dbh_hana_single = odbc_connect($connect_name,$params['-dbuser'],$params['-dbpass'] );
		}
		if(!is_resource($dbh_hana_single)){
			$err=odbc_errormsg();
			$params['-dbpass']=preg_replace('/[a-z0-9]/i','*',$params['-dbpass']);
			echo "hanaDBConnect single connect error:{$err}".printValue($params);
			exit;
		}
		return $dbh_hana_single;
	}
	global $dbh_hana;
	if(is_resource($dbh_hana)){return $dbh_hana;}

	try{
		if(isset($params['-cursor'])){
			$dbh_hana = @odbc_pconnect($connect_name,$params['-dbuser'],$params['-dbpass'],$params['-cursor'] );
		}
		else{
			$dbh_hana = @odbc_pconnect($connect_name,$params['-dbuser'],$params['-dbpass']);
		}
		if(!is_resource($dbh_hana)){
			//wait a few seconds and try again
			sleep(2);
			if(isset($params['-cursor'])){
				$dbh_hana = @odbc_pconnect($connect_name,$params['-dbuser'],$params['-dbpass'],$params['-cursor'] );
			}
			else{
				$dbh_hana = @odbc_pconnect($connect_name,$params['-dbuser'],$params['-dbpass'] );
			}
			if(!is_resource($dbh_hana)){
				$err=odbc_errormsg();
				$params['-dbpass']=preg_replace('/[a-z0-9]/i','*',$params['-dbpass']);
				echo "hanaDBConnect error:{$err}".printValue($params);
				exit;
			}
		}
		return $dbh_hana;
	}
	catch (Exception $e) {
		echo "hanaDBConnect exception" . printValue($e);
		exit;

	}
}
//---------- begin function hanaIsDBTable ----------
/**
* @describe returns true if table exists
* @param $tablename string - table name
* @param $schema string - schema name
* @param $params array - These can also be set in the CONFIG file with dbname_hana,dbuser_hana, and dbpass_hana
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if table exists
* @usage 
*	loadExtras('hana');
*	if(hanaIsDBTable('abc','abcschema')){...}
*/
function hanaIsDBTable($table,$force=0){
	$table=strtolower($table);
	global $databaseCache;
	global $CONFIG;
	$cachekey=sha1(json_encode($CONFIG).'hanaIsDBTable'.$table);
	if($force==0 && isset($databaseCache[$cachekey])){
		return $databaseCache[$cachekey];
	}
	$parts=preg_split('/\./',$table,2);
	if(count($parts)==2){
		$schema=$parts[0];
		$table=$parts[1];
	}
	else{
		$schema=hanaGetDBSchema();
	}
	if(!strlen($schema)){
		debugValue("hanaIsDBTable error: no schema");
		return false;
	}
	$table=strtoupper($table);
	$schema=strtoupper($schema);
	$query=<<<ENDOFQUERY
		SELECT table_name
		FROM sys.tables
		WHERE 
			schema_name='{$schema}' 
			AND table_name='{$table}'
ENDOFQUERY;
	$recs=hanaQueryResults($query);
	if(isset($recs[0]['table_name'])){
		$databaseCache[$cachekey]=true;
	}
	else{
		$databaseCache[$cachekey]=false;
	}
	return $databaseCache[$cachekey];
}
//---------- begin function hanaClearConnection ----------
/**
* @describe clears the hana database connection
* @return boolean returns true if query succeeded
* @usage 
*	loadExtras('hana');
*	$ok=hanaClearConnection();
*/
function hanaClearConnection(){
	global $dbh_hana;
	$dbh_hana='';
	return true;
}
//---------- begin function hanaExecuteSQL ----------
/**
* @describe executes a query and returns without parsing the results
* @param $query string - query to execute
* @param $params array - These can also be set in the CONFIG file with dbname_hana,dbuser_hana, and dbpass_hana
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if query succeeded
* @usage 
*	loadExtras('hana');
*	$ok=hanaExecuteSQL("truncate table abc");
*/
function hanaExecuteSQL($query,$params=array()){
	global $DATABASE;
	$DATABASE['_lastquery']=array(
		'start'=>microtime(true),
		'stop'=>0,
		'time'=>0,
		'error'=>'',
		'query'=>$query,
		'function'=>'hanaExecuteSQL',
		'params'=>$params
	);
	global $dbh_hana;
	if(!is_resource($dbh_hana)){
		$dbh_hana=hanaDBConnect($params);
	}
	if(!$dbh_hana){
		$DATABASE['_lastquery']['error']='connect failed: '.odbc_errormsg();
		debugValue($DATABASE['_lastquery']);
    	return 0;
	}
	try{
		//check for -timeout
		if(isset($params['-timeout']) && isNum($params['-timeout'])){
			//sets the query to timeout after X seconds
			$result = odbc_prepare($dbh_hana,$query);
			odbc_setoption($result, 2, 0, $params['-timeout']);
			odbc_execute($result);
		}
		else{
			$result=odbc_exec($dbh_hana,$query);	
		}
		if (odbc_error()){
			$errstr=odbc_errormsg($dbh_hana);
			if(!strlen($errstr)){return array();}
			if(stringContains($errstr,'session not connected')){
				//lets retry
				odbc_close($dbh_hana);
				sleep(1);
				$dbh_hana='';
				$dbh_hana=hanaDBConnect($params);
				if(isset($params['-timeout']) && isNum($params['-timeout'])){
					//sets the query to timeout after X seconds
					$result = odbc_prepare($dbh_hana,$query);
					odbc_setoption($result, 2, 0, $params['-timeout']);
					odbc_execute($result);
				}
				else{
					$result=odbc_exec($dbh_hana,$query);	
				}
				if(!$result){
					$errstr=odbc_errormsg($dbh_hana);
					if(!strlen($errstr)){return array();}
					$DATABASE['_lastquery']['error']='connect failed: '.$errstr;
					debugValue($DATABASE['_lastquery']);
			    	return 0;
				}
			}
			else{
				$DATABASE['_lastquery']['error']='connect failed: '.$errstr;
				debugValue($DATABASE['_lastquery']);
    			return 0;
			}
		}
	}
	catch (Exception $e) {
		$DATABASE['_lastquery']['error']=$e->errorInfo;
		debugValue($DATABASE['_lastquery']);
		return 0;
	}
	$DATABASE['_lastquery']['stop']=microtime(true);
	$DATABASE['_lastquery']['time']=$DATABASE['_lastquery']['stop']-$DATABASE['_lastquery']['start'];
	return 1;
}
//---------- begin function hanaAddDBRecord ----------
/**
* @describe adds a records from params passed in.
*  if cdate, and cuser exists as fields then they are populated with the create date and create username
* @param $params array - These can also be set in the CONFIG file with dbname_hana,dbuser_hana, and dbpass_hana
*   -table - name of the table to add to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to add to the record
* @return integer returns the autoincriment key
* @usage 
*	loadExtras('hana');
*	$id=hanaAddDBRecord(array('-table'=>'abc','name'=>'bob','age'=>25));
*/
function hanaAddDBRecord($params){
	global $hanaAddDBRecordCache;
	global $USER;
	if(!isset($params['-table'])){return 'hanaAddDBRecord error: No table.';}
	$fields=hanaGetDBFieldInfo($params['-table'],$params);
	$opts=array();
	if(isset($fields['cdate'])){
		$params['cdate']=strtoupper(date('d-M-Y  H:i:s'));
	}
	elseif(isset($fields['_cdate'])){
		$params['_cdate']=strtoupper(date('d-M-Y  H:i:s'));
	}
	if(isset($fields['cuser'])){
		$params['cuser']=$USER['username'];
	}
	elseif(isset($fields['_cuser'])){
		$params['_cuser']=$USER['username'];
	}
	$valstr='';
	foreach($params as $k=>$v){
		$k=strtolower($k);
		if(!strlen(trim($v))){continue;}
		if(!isset($fields[$k])){continue;}
		//fix array values
		if(is_array($v)){$v=implode(':',$v);}
		switch(strtolower($fields[$k]['type'])){
        	case 'date':
				if($k=='cdate' || $k=='_cdate'){
					$v=date('Y-m-d',strtotime($v));
				}
        	break;
			case 'nvarchar':
			case 'nchar':
				$v=hanaConvert2UTF8($v);
        	break;
		}
		//support for nextval
		if(preg_match('/\.nextval$/',$v)){
			$opts['bindings'][]=$v;
        	$opts['fields'][]=trim(strtoupper($k));
		}
		else{
			$opts['values'][]=$v;
			$opts['bindings'][]='?';
        	$opts['fields'][]=trim(strtoupper($k));
		}
	}
	$fieldstr=implode('","',$opts['fields']);
	$bindstr=implode(',',$opts['bindings']);
    $query=<<<ENDOFQUERY
		INSERT INTO {$params['-table']}
			("{$fieldstr}")
		VALUES
			({$bindstr})
ENDOFQUERY;
	$dbh_hana=hanaDBConnect($params);
	if(!$dbh_hana){
    	$e=odbc_errormsg();
    	debugValue(array("hanaAddDBRecord Connect Error",$e));
    	return "hanaAddDBRecord Connect Error".printValue($e);
	}
	try{
		if(!isset($hanaAddDBRecordCache[$params['-table']]['stmt'])){
			$hanaAddDBRecordCache[$params['-table']]['stmt']    = odbc_prepare($dbh_hana, $query);
			if(!is_resource($hanaAddDBRecordCache[$params['-table']]['stmt'])){
				$e=odbc_errormsg();
				$err=array("hanaAddDBRecord prepare Error",$e,$query);
				debugValue($err);
				return printValue($err);
			}
		}
		
		$success = odbc_execute($hanaAddDBRecordCache[$params['-table']]['stmt'],$opts['values']);
		if (odbc_error()){
			$e=odbc_errormsg();
			debugValue(array("hanaAddDBRecord Execute Error",$e,$opts));
    		return "hanaAddDBRecord Execute Error".printValue($e);
		}
		if(isset($params['-noidentity'])){return $success;}
		$result2=odbc_exec($dbh_hana,"SELECT TOP 1 IFNULL(CURRENT_IDENTITY_VALUE(),0) AS cval FROM {$params['-table']};");
		$row=odbc_fetch_array($result2,0);
		odbc_free_result($result2);
		$row=array_change_key_case($row);
		if(isset($row['cval'])){return $row['cval'];}
		return "hanaAddDBRecord Identity Error".printValue($row).printValue($opts);
		//echo "result2:".printValue($row);
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		$err['query']=$query;
		$recs=array($err);
		debugValue(array("hanaAddDBRecord Try Error",$e));
		return "hanaAddDBRecord Try Error".printValue($err);
	}
	return 0;
}
//---------- begin function hanaEditDBRecord ----------
/**
* @describe edits a record from params passed in based on where.
*  if edate, and euser exists as fields then they are populated with the edit date and edit username
* @param $params array - These can also be set in the CONFIG file with dbname_hana,dbuser_hana, and dbpass_hana
*   -table - name of the table to add to
*   -where - filter criteria
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to edit
* @return boolean returns true on success
* @usage 
*	loadExtras('hana');
*	$id=hanaEditDBRecord(array('-table'=>'abc','-where'=>"id=3",'name'=>'bob','age'=>25));
*/
function hanaEditDBRecord($params,$id=0,$opts=array()){
	mb_internal_encoding("UTF-8");
	//check for function overload: editDBRecord(table,id,opts());
	if(!is_array($params) && strlen($params) && isNum($id) && $id > 0 && is_array($opts) && count($opts)){
		$opts['-table']=$params;
		$opts['-where']="_id={$id}";
		$params=$opts;
	}
	if(!isset($params['-table'])){return 'hanaEditDBRecord error: No table specified.';}
	if(!isset($params['-where'])){return 'hanaEditDBRecord error: No where specified.';}
	global $USER;
	$fields=hanaGetDBFieldInfo($params['-table'],$params);
	$opts=array();
	if(isset($fields['edate'])){
		$params['edate']=strtoupper(date('Y-M-d H:i:s'));
	}
	elseif(isset($fields['_edate'])){
		$params['_edate']=strtoupper(date('Y-M-d H:i:s'));
	}
	if(isset($fields['euser'])){
		$params['euser']=$USER['username'];
	}
	elseif(isset($fields['_euser'])){
		$params['_euser']=$USER['username'];
	}
	$vals=array();
	foreach($params as $k=>$v){
		$k=strtolower($k);
		if(!isset($fields[$k])){continue;}
		//fix array values
		if(is_array($v)){$v=implode(':',$v);}
		switch(strtolower($fields[$k]['type'])){
			case 'nvarchar':
			case 'nchar':
				$v=hanaConvert2UTF8($v);
			break;
        	case 'date':
				if($k=='edate' || $k=='_edate'){
					$v=date('Y-m-d',strtotime($v));
				}
        	break;
		}
		$updates[]="{$k}=?";
		$vals[]=$v;
	}
	$updatestr=implode(', ',$updates);
    $query=<<<ENDOFQUERY
		UPDATE {$params['-table']}
		SET {$updatestr}
		WHERE {$params['-where']}
ENDOFQUERY;
	global $dbh_hana;
	if(!is_resource($dbh_hana)){
		$dbh_hana=hanaDBConnect($params);
	}
	if(!$dbh_hana){
    	$e=odbc_errormsg();
    	debugValue(array("hanaEditDBRecord2 Connect Error",$e));
    	return;
	}
	try{
		$hana_stmt    = odbc_prepare($dbh_hana, $query);
		if(!is_resource($hana_stmt)){
			$e=odbc_errormsg();
			debugValue(array("hanaEditDBRecord2 prepare Error",$e));
    		return 1;
		}
		$success = odbc_execute($hana_stmt,$vals);
		if (odbc_error()){
			debugValue(array(
				'function'=>'hanaEditDBRecord',
				'message'=>'odbc execute error',
				'error'=>odbc_errormsg($hana_stmt),
				'query'=>$query,
				'params'=>$params
			));
		}
		//echo $vals[5].$query.printValue($success).printValue($vals);
	}
	catch (Exception $e) {
		debugValue(array("hanaEditDBRecord2 try Error",$e));
    	return;
	}
	return 0;
}
//---------- begin function hanaReplaceDBRecord ----------
/**
* @describe updates or adds a record from params passed in.
*  if cdate, and cuser exists as fields then they are populated with the create date and create username
* @param $params array - These can also be set in the CONFIG file with dbname_hana,dbuser_hana, and dbpass_hana
*   -table - name of the table to add to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	other field=>value pairs to add/edit to the record
* @return integer returns the autoincriment key
* @usage 
*	loadExtras('hana');
*	$id=hanaReplaceDBRecord(array('-table'=>'abc','name'=>'bob','age'=>25));
*/
function hanaReplaceDBRecord($params){
	global $USER;
	if(!isset($params['-table'])){return 'hanaAddRecord error: No table specified.';}
	$fields=hanaGetDBFieldInfo($params['-table'],$params);
	$opts=array();
	if(isset($fields['cdate'])){
		$opts['fields'][]='CDATE';
		$opts['values'][]=strtoupper(date('d-M-Y  H:i:s'));
	}
	if(isset($fields['cuser'])){
		$opts['fields'][]='CUSER';
		$opts['values'][]=$USER['username'];
	}
	$valstr='';
	foreach($params as $k=>$v){
		$k=strtolower($k);
		if(!strlen(trim($v))){continue;}
		if(!isset($fields[$k])){continue;}
		//skip cuser and cdate - already set
		if($k=='cuser' || $k=='cdate'){continue;}
		//fix array values
		if(is_array($v)){$v=implode(':',$v);}
		//take care of single quotes in value
		$v=str_replace("'","''",$v);
		switch(strtolower($fields[$k]['type'])){
        	case 'integer':
        	case 'number':
        		$opts['values'][]=$v;
        	break;
			case 'nvarchar':
			case 'nchar':
				//add N before the value to handle utf8 inserts
				$opts['values'][]="N'{$v}'";
			break;
        	default:
        		$opts['values'][]="'{$v}'";
        	break;
		}
        $opts['fields'][]=trim(strtoupper($k));
	}
	$fieldstr=implode('","',$opts['fields']);
	$valstr=implode(',',$opts['values']);
    $query=<<<ENDOFQUERY
		REPLACE {$params['-table']}
		("{$fieldstr}")
		values({$valstr})
		WITH PRIMARY KEY
ENDOFQUERY;
	$dbh_hana=hanaDBConnect($params);
	if(!$dbh_hana){
    	$e=odbc_errormsg();
    	debugValue(array("hanaReplaceDBRecord Connect Error",$e));
    	return;
	}
	try{
		$result=odbc_exec($dbh_hana,$query);
		if(!$result){
        	$err=array(
        		'error'	=> odbc_errormsg($dbh_hana),
				'query'	=> $query
			);
			debugValue($err);
			return "hanaReplaceDBRecord Error".printValue($err).$query;
		}
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		$err['query']=$query;
		odbc_free_result($result);
		debugValue(array("hanaReplaceDBRecord Connect Error",$e));
		return "hanaReplaceDBRecord Error".printValue($err);
	}
	odbc_free_result($result);
	return true;
}
//---------- begin function hanaManageDBSessions ----------
/**
* @describe cleans up idle sessions since HANA does not clean up for you.
* @param $user string - defaults to current username.  Set to "all" to clean up all users (requires session admin privileges)
* @param $idle integer - number of milliseconds session has to be idle for
* @param $params array - These can also be set in the CONFIG file with dbname_hana,dbuser_hana, and dbpass_hana
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage 
*	loadExtras('hana');
*	$cnt=hanaManageDBSessions('all');
*/
function hanaManageDBSessions($username='',$idle=1800000,$params=array()){
	global $USER;
	if(!strlen($username)){$username=$USER['username'];}
	if(strtolower($username)=='all'){$username='user_name';}
	else{$username="'{$username}'";}
	//ALTER SYSTEM DISCONNECT SESSION '<connection_id>'
	$query=<<<ENDOFQUERY
SELECT connection_id
FROM M_CONNECTIONS
WHERE
	user_name={$username}
	AND connection_status = 'IDLE'
	AND connection_type = 'Remote'
	AND idle_time > {$idle}
ORDER BY idle_time DESC
ENDOFQUERY;
	$recs=hanaQueryResults($query,$params);
	foreach($recs as $rec){
    	$query="ALTER SYSTEM DISCONNECT SESSION '{$rec['connection_id']}'";
    	$ok=hanaQueryResults($query,$params);
	}
	return count($recs);
}
//---------- begin function hanaGetDBRecords
/**
* @describe returns and array of records
* @param params array - requires either -table or a raw query instead of params
*	[-table] string - table name.  Use this with other field/value params to filter the results
*	[-limit] mixed - query record limit.  Defaults to CONFIG['paging'] if set in config.xml otherwise 25
*	[-offset] mixed - query offset limit
*	[-fields] mixed - fields to return
*	[-where] string - string to add to where clause
*	[-filter] string - string to add to where clause
*	[-host] - server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array - set of records
* @usage
*	loadExtras('hana');
*	$recs=hanaGetDBRecords(array('-table'=>'notes','name'=>'bob'));
*	$recs=hanaGetDBRecords("SELECT * FROM notes WHERE name='bob'");
*/
function hanaGetDBRecords($params){
	global $USER;
	global $CONFIG;
	if(empty($params['-table']) && !is_array($params)){
		$params=trim($params);
		if(preg_match('/^(select|exec|with|explain|returning|show|call)[\t\s\ \r\n]/i',$params)){
			//they just entered a query
			$query=$params;
			$params=array();
		}
		else{
			$ok=hanaExecuteSQL($params);
			return $ok;
		}
	}
	elseif(isset($params['-query'])){
		$query=$params['-query'];
		unset($params['-query']);
	}
	else{
		//determine fields to return
		if(!empty($params['-fields'])){
			if(!is_array($params['-fields'])){;
				$params['-fields']=preg_split('/\,/',$params['-fields']);
				foreach($params['-fields'] as $i=>$field){
					$params['-fields'][$i]=trim($field);
				}
			}
			$params['-fields']=implode(',',$params['-fields']);
		}
		if(empty($params['-fields'])){$params['-fields']='*';}
		$fields=hanaGetDBFieldInfo($params['-table'],$params);
		$ands=array();
		foreach($params as $k=>$v){
			$k=strtolower($k);
			if(!isset($fields[$k])){continue;}
			if(is_array($params[$k])){
	            $params[$k]=implode(':',$params[$k]);
			}
			elseif(!strlen(trim($params[$k]))){continue;}
	        $params[$k]=str_replace("'","''",$params[$k]);
	        $v=strtoupper($params[$k]);
	        $ands[]="upper({$k})='{$v}'";
		}
		//check for -where
		if(!empty($params['-where'])){
			$ands[]= "({$params['-where']})";
		}
		if(isset($params['-filter'])){
			$ands[]= "({$params['-filter']})";
		}
		$wherestr='';
		if(count($ands)){
			$wherestr='WHERE '.implode(' and ',$ands);
		}
	    $query="SELECT {$params['-fields']} FROM {$params['-table']} {$wherestr}";
	    if(isset($params['-order'])){
    		$query .= " ORDER BY {$params['-order']}";
    	}
    	//offset and limit
    	if(!isset($params['-nolimit'])){
	    	$offset=isset($params['-offset'])?$params['-offset']:0;
	    	$limit=25;
	    	if(!empty($params['-limit'])){$limit=$params['-limit'];}
	    	elseif(!empty($CONFIG['paging'])){$limit=$CONFIG['paging'];}
	    	$query .= " LIMIT {$limit} OFFSET {$offset}";
	    }
	}
	if(isset($params['-debug'])){return $query;}
	if(isset($params['-queryonly'])){return $query;}
	return hanaQueryResults($query,$params);
}
//---------- begin function hanaGetDBSystemTables ----------
/**
* @describe returns an array of system tables
* @param $params array - These can also be set in the CONFIG file with dbname_hana,dbuser_hana, and dbpass_hana
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage 
*	loadExtras('hana');
*	$systemtables=hanaGetDBSystemTables();
*/
function hanaGetDBSystemTables($params=array()){
	$params['-table_schema']='S';
	return hanaGetDBTables($params);
}
//---------- begin function hanaGetDBSchemas ----------
/**
* @describe returns an array of system tables
* @param $params array - These can also be set in the CONFIG file with dbname_hana,dbuser_hana, and dbpass_hana
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true on success
* @usage
*	loadExtras('hana'); 
*	$schemas=hanaGetDBSchemas();
*/
function hanaGetDBSchemas($has_privileges=1){
	$query=<<<ENDOFQUERY
		SELECT 
			schema_name
		FROM sys.schemas 
		WHERE 
			schema_owner NOT IN ('SYS','UIS','SYSTEM','SYS_REPL')
			AND schema_owner NOT LIKE '_SYS_%'
			AND schema_owner NOT LIKE 'SAP_%'
			AND schema_owner NOT LIKE 'HANA_%'
			AND schema_owner NOT LIKE '%_AUTO_USER_%'
ENDOFQUERY;
	if($has_privileges==1){
		$query.= "			AND has_privileges='TRUE'".PHP_EOL;
	}
	$query.= "ORDER BY schema_name";
	$recs=hanaQueryResults($query);
	$schemas=array();
	foreach($recs as $rec){
		$schemas[]=$rec['schema_name'];
	}
	return $schemas;
}
//---------- begin function hanaGetDBTables ----------
/**
* @describe returns an array of tables

* @return boolean returns true on success
* @usage 
*	loadExtras('hana');
*	$tables=hanaGetDBTables();
*/
function hanaGetDBTables($schema=''){
	$schemas=hanaGetDBSchemas(1);
	if(!count($schemas)){return array();}
	$schemastr=implode("','",$schemas);
	$query=<<<ENDOFQUERY
		SELECT
			schema_name,
			table_name
		FROM public.tables
		WHERE schema_name in ('{$schemastr}')
		ORDER BY table_name
ENDOFQUERY;
	$recs=hanaQueryResults($query);
	$tables=array();
	foreach($recs as $rec){
		$tables[]=strtolower($rec['schema_name']).'.'.strtolower($rec['table_name']);
	}
	return $tables;
}
//---------- begin function hanaGetDBFieldInfo ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name,type,scale, precision, length, num are
* @return boolean returns true on success
* @usage
*	loadExtras('hana'); 
*	$fieldinfo=hanaGetDBFieldInfo('abcschema.abc');
*/
function hanaGetDBFieldInfo($table,$params=array()){
	$parts=preg_split('/\./',$table,2);
	if(count($parts)==2){
		$schema=$parts[0];
		$table=$parts[1];
	}
	else{
		$schema=hanaGetDBSchema();
	}
	if(!strlen($schema)){
		debugValue('hanaGetDBFieldInfo error: schema is not defined in config.xml');
		return array('hanaGetDBTables ERROR');
	}
	$schema=strtoupper($schema);
	$table=strtoupper($table);
	$query=<<<ENDOFQUERYNEW
	SELECT
		tc.schema_name as table_schema, 
		tc.table_name,
		tc.column_name,
		tc.position as ordinal_position,
		tc.default_value column_default,
		tc.is_nullable,
		tc.data_type_name as data_type,
		tc.length as character_maximum_length,
		tc.scale as numeric_precision,
		c.is_primary_key,
		c.is_unique_key
	FROM
		sys.table_columns tc
		LEFT OUTER JOIN sys.constraints c on c.schema_name=tc.schema_name and c.table_name=tc.table_name and c.column_name=tc.column_name
	WHERE
		tc.schema_name='{$schema}'
		and tc.table_name='{$table}'
ENDOFQUERYNEW;
	$recs=hanaQueryResults($query);
	$fields=array();
	foreach($recs as $rec){
		$field=array(
			'_dbtable'	=> $rec['table_name'],
		 	'_dbfield'	=> strtolower($rec['column_name']),
		 	'_dbtype'	=> strtolower($rec['data_type']),
		 	'_dblength' => $rec['character_maximum_length'],
		 	'table'		=> $rec['table_name'],
		 	'name'		=> strtolower($rec['column_name']),
		 	'type'		=> strtolower($rec['data_type']),
			'length'	=> $rec['character_maximum_length'],
			'num'		=> $rec['numeric_precision'],
			'size'		=> $rec['numeric_precision_radix'],
			'identity'	=> strtolower($rec['is_identity'])=='true'?1:0,
			'primary'	=> strtolower($rec['is_primary_key'])=='true'?1:0,
			'unique'	=> strtolower($rec['is_unique_key'])=='true'?1:0,
		);
		//nullable
		switch(strtolower($rec['not_null'])){
			case 't':
				$field['nullable']=0;
			break;
			default:
				$field['nullable']=1;
			break;

		}
		//_dbtype_ex
		switch(strtolower($field['_dbtype'])){
			case 'bigint':
			case 'integer':
			case 'timestamp':
				$field['_dbtype_ex']=$field['_dbtype'];
			break;
			default:
				if(strlen($field['_dblength']) && $field['_dblength'] != '-1'){
					$field['_dbtype_ex']="{$field['_dbtype']}({$field['_dblength']})";
				}
				else{
					$field['_dbtype_ex']=$field['_dbtype'];
				}
			break;
		}
		
		//default
		if(strlen($rec['column_default'])){
			$field['_dbdef']=$field['default']=$rec['column_default'];
		}
		$fields[$field['_dbfield']]=$field;
	}
	//echo $table.printValue($fields).'<hr>'.PHP_EOL;
	return $fields;
}
//---------- begin function hanaGetDBCount--------------------
/**
* @describe returns a record count based on params
* @param params array - requires either -list or -table or a raw query instead of params
*	-table string - table name.  Use this with other field/value params to filter the results
* @return array
* @usage
*	loadExtras('hana'); 
*	$cnt=hanaGetDBCount(array('-table'=>'states','-where'=>"code like 'M%'"));
*/
function hanaGetDBCount($params=array()){
	global $CONFIG;
	global $DATABASE;
	if(!isset($params['-table'])){return null;}
	$parts=preg_split('/\./',$params['-table']);
	if(count($parts)==2){
		$dbschema=strtolower($parts[0]);
		$table=strtolower($parts[1]);
	}
	else{
		$dbschema=strtolower($DATABASE[$CONFIG['db']]['dbschema']);
		$table=strtolower($params['-table']);
	}
	$params['-fields']="COUNT(*) AS cnt";
	unset($params['-order']);
	unset($params['-limit']);
	unset($params['-offset']);
	$params['-queryonly']=1;
	$query=hanaGetDBRecords($params);
	if(!stringContains($query,'where') && strlen($dbschema)){
	 	$query="SELECT schema_name,table_name,record_count AS cnt FROM sys.m_tables WHERE LOWER(schema_name)='{$dbschema}' AND LOWER(table_name)='{$table}'";
	 	$recs=hanaQueryResults($query);
	 	//echo $query.printValue($recs);exit;
	 	if(isset($recs[0]['cnt']) && isNum($recs[0]['cnt'])){
	 		return (integer)$recs[0]['cnt'];
	 	}
	}
	$recs=hanaQueryResults($query);
	//if($params['-table']=='states'){echo $query.printValue($recs);exit;}
	if(!isset($recs[0]['cnt'])){
		debugValue($recs);
		return 0;
	}
	return $recs[0]['cnt'];
}
//---------- begin function hanaQueryHeader ----------
/**
* @describe returns a single row array with the column names
* @param $params array - These can also be set in the CONFIG file with dbname_hana,dbuser_hana, and dbpass_hana
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array a single row array with the column names
* @usage
*	loadExtras('hana'); 
*	$recs=hanaQueryHeader($query);
*/
function hanaQueryHeader($query,$params=array()){
	$dbh_hana=hanaDBConnect($params);
	if(!$dbh_hana){
    	$e=odbc_errormsg();
    	debugValue(array("hanaQueryHeader Connect Error",$e));
    	return;
	}
	if(!preg_match('/limit\ /is',$query)){
		$query .= " limit 0";
	}
	try{
		//check for -timeout
		if(isset($params['-timeout']) && isNum($params['-timeout'])){
			//sets the query to timeout after X seconds
			$result = odbc_prepare($dbh_hana,$query);
			odbc_setoption($result, 2, 0, $params['-timeout']);
			odbc_execute($result);
		}
		else{
			$result=odbc_exec($dbh_hana,$query);	
		}
		if (odbc_error()){
			debugValue(array(
				'function'=>'hanaEditDBRecord',
				'message'=>'odbc execute error',
				'error'=>odbc_errormsg($dbh_hana),
				'query'=>$query,
				'params'=>$params
			));
			return array();
		}
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		debugValue(array(
			'function'=>'hanaEditDBRecord',
			'message'=>'try catch Exception',
			'error'=>$err,
			'query'=>$query,
			'params'=>$params
		));
		return array();
	}
	$fields=array();
	for($i=1;$i<=odbc_num_fields($result);$i++){
		$field=strtolower(odbc_field_name($result,$i));
        $fields[]=$field;
    }
    odbc_free_result($result);
    $rec=array();
    foreach($fields as $field){
		$rec[$field]='';
	}
    $recs=array($rec);
	return $recs;
}
//---------- begin function hanaQueryResults ----------
/**
* @describe returns the records of a query
* @param $params array - These can also be set in the CONFIG file with dbname_hana,dbuser_hana, and dbpass_hana
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	[-filename] - if you pass in a filename then it will write the results to the csv filename you passed in
* 	[-filename_partitions] - number of files you want to create. Appends number to each one. This requires you to set a row_count field in each record returned. (use a CTE - with as)
* 	[-filename_maxsize] - max filesize. Appends number to each file created
* 	[-filename_maxrows] - max rows. Appends number to each file created
*   [-process] - function name to call for each record
* 	[-logfile] - logfile to write to
* @return $recs array
* @usage
*	loadExtras('hana'); 
*	$recs=hanaQueryResults('SELECT TOP 50 * FROM abcschema.abc');
*/
function hanaQueryResults($query,$params=array()){
	global $DATABASE;
	$DATABASE['_lastquery']=array(
		'start'=>microtime(true),
		'stop'=>0,
		'time'=>0,
		'error'=>'',
		'query'=>$query,
		'function'=>'hanaQueryResults',
		'params'=>$params
	);
	global $hanaStopProcess;
	global $dbh_hana;
	$starttime=microtime(true);
	if(!is_resource($dbh_hana)){
		$dbh_hana=hanaDBConnect($params);
	}
	$dbh_hana_result='';
	if(!$dbh_hana){
		$DATABASE['_lastquery']['error']='connect failed: '.odbc_errormsg();
		debugValue($DATABASE['_lastquery']);
    	return array();
	}
	$ok=odbc_errormsg($dbh_hana);
	if(!isset($params['-longreadlen'])){
		$params['-longreadlen']=131027;
	}
	try{
		//odbc_exec($dbh_hana, "SET NAMES 'UTF8'");
		//odbc_exec($dbh_hana, "SET client_encoding='UTF-8'");
		//check for -timeout
		if(isset($params['-timeout']) && isNum($params['-timeout'])){
			//sets the query to timeout after X seconds
			$dbh_hana_result = odbc_prepare($dbh_hana,$query);
			odbc_setoption($dbh_hana_result, 2, 0, $params['-timeout']);
			odbc_execute($dbh_hana_result);
		}
		else{
			$dbh_hana_result=odbc_exec($dbh_hana,$query);	
		}
		if (odbc_error()){
			debugValue(array(
				'function'=>'hanaQueryResults',
				'message'=>'odbc execute error',
				'error'=>odbc_errormsg($dbh_hana),
				'query'=>$query,
				'params'=>$params
			));
			return array();
		}
	}
	catch (Exception $e) {
		$err=$e->errorInfo;
		$DATABASE['_lastquery']['error']='connect failed: '.$e->errorInfo;
		debugValue($DATABASE['_lastquery']);
    	return array();
	}
	$rowcount=odbc_num_rows($dbh_hana_result);
	if($rowcount==0 && isset($params['-forceheader'])){
		$fields=array();
		for($i=1;$i<=odbc_num_fields($dbh_hana_result);$i++){
			$field=strtolower(odbc_field_name($dbh_hana_result,$i));
			$fields[]=$field;
		}
		odbc_free_result($dbh_hana_result);
		$rec=array();
		foreach($fields as $field){
			$rec[$field]='';
		}
		$recs=array($rec);
		return $recs;
	}
	if(isset($params['-count'])){
		odbc_free_result($dbh_hana_result);
    	return $rowcount;
	}
	$header=0;
	unset($fh);
	//write to file or return a recordset?
	//-filename=>'/var/www/temp/myfilename.csv'
	$maxrows=0;
	if(isset($params['-filename_partitions']) && $rowcount > 0){
		$maxrows=ceil($rowcount/$params['-filename_partitions']);
	}
	elseif(isset($params['-filename_maxrows'])){
		$maxrows=$params['-filename_maxrows'];
	}
	if($maxrows > 0 && isset($params['-filename'])){
		//rename the file 
		$ext=getFileExtension($params['-filename']);
		$filename=getFileName($params['-filename'],1);
		$path=getFilePath($params['-filename']);
		$file_counter=1;
		$params['-filename']="{$path}/{$filename}_{$file_counter}.{$ext}";
	}
	if(isset($params['-filename'])){
		if(isset($params['-append'])){
			//append
    		$fh = fopen($params['-filename'],"ab");
		}
		else{
			if(file_exists($params['-filename'])){unlink($params['-filename']);}
    		$fh = fopen($params['-filename'],"wb");
		}
		
    	if(!isset($fh) || !is_resource($fh)){
			odbc_free_result($dbh_hana_result);
			$DATABASE['_lastquery']['error']='failed to open file: '.$params['-filename'];
			debugValue($DATABASE['_lastquery']);
	    	return array();
		}
		if(isset($params['-logfile'])){
			setFileContents($params['-logfile'],"Rowcount:".$rowcount.PHP_EOL.$query.PHP_EOL.PHP_EOL);
		}
		
	}
	else{$recs=array();}
	if(isset($params['-binmode'])){
		odbc_binmode($dbh_hana_result, $params['-binmode']);
	}
	if(isset($params['-longreadlen'])){
		odbc_longreadlen($dbh_hana_result,$params['-longreadlen']);
	}
	$i=0;

	$writefile=0;
	if(isset($fh) && is_resource($fh)){
		$writefile=1;
	}
	while($rec=odbc_fetch_array($dbh_hana_result)){
		//lowercase the field names
		$rec=array_change_key_case($rec);
		if(isset($params['-filename']) && $maxrows==0 && isset($params['-filename_partitions']) && isset($rec['row_count'])){
			$rowcount=$rec['row_count'];
			$maxrows=ceil($rowcount/$params['-filename_partitions']);
			if($maxrows > 0){
				unlink($params['-filename']);
				//rename the file 
				$ext=getFileExtension($params['-filename']);
				$filename=getFileName($params['-filename'],1);
				$path=getFilePath($params['-filename']);
				$file_counter=1;
				$params['-filename']="{$path}/{$filename}_{$file_counter}.{$ext}";
			}
			if(isset($params['-filename'])){
				if(isset($params['-append'])){
					//append
		    		$fh = fopen($params['-filename'],"ab");
				}
				else{
					if(file_exists($params['-filename'])){unlink($params['-filename']);}
		    		$fh = fopen($params['-filename'],"wb");
				}
				
		    	if(!isset($fh) || !is_resource($fh)){
					odbc_free_result($dbh_hana_result);
					$DATABASE['_lastquery']['error']='failed to open file: '.$params['-filename'];
					debugValue($DATABASE['_lastquery']);
			    	return array();
				}
				if(isset($params['-logfile'])){
					setFileContents($params['-logfile'],"Rowcount:".$rowcount.PHP_EOL.$query.PHP_EOL.PHP_EOL);
				}
			}
		}
		if(isset($params['-filename']) && isset($params['-filename_partitions']) && isset($rec['row_count'])){
			//remove row_count this from the result set
			unset($rec['row_count']);
		}
		//check for hanaStopProcess request
		if(isset($hanaStopProcess) && $hanaStopProcess==1){
			break;
		}
		
	    if($writefile==1){
        	if($header==0){
            	$csv=arrays2CSV(array($rec));
            	$header=1;
            	//add UTF-8 byte order mark to the beginning of the csv
				$csv="\xEF\xBB\xBF".$csv;
			}
			else{
            	$csv=arrays2CSV(array($rec),array('-noheader'=>1));
			}
			$csv=preg_replace('/[\r\n]+$/','',$csv);
			fwrite($fh,$csv."\r\n");
			$i+=1;
			if(isset($params['-logfile']) && file_exists($params['-logfile']) && $i % 5000 == 0){
				appendFileContents($params['-logfile'],$i.PHP_EOL);
			}
			if(isset($params['-process'])){
				$ok=call_user_func($params['-process'],$rec);
			}
			//check to see if we need to increment the filename based on maxrows
			if($maxrows > 0 && $i % $maxrows==0){
				@fclose($fh);
				//time to open a new file
				$header=0;
				$file_counter+=1;
				$params['-filename']="{$path}/{$filename}_{$file_counter}.{$ext}";
				if(file_exists($params['-filename'])){unlink($params['-filename']);}
		    	$fh = fopen($params['-filename'],"wb");
				
		    	if(!isset($fh) || !is_resource($fh)){
					odbc_free_result($dbh_hana_result);
					$DATABASE['_lastquery']['error']='failed to open file: '.$params['-filename'];
					debugValue($DATABASE['_lastquery']);
			    	return array();
				}
				if(isset($params['-logfile'])){
					setFileContents($params['-logfile'],"New File:".$params['-filename'].PHP_EOL);
				}
			}
			continue;
		}
		elseif(isset($params['-process'])){
			$ok=call_user_func($params['-process'],$rec);
			$x++;
			continue;
		}
		elseif(isset($params['-index']) && isset($rec[$params['-index']])){
			$recs[$rec[$params['-index']]]=$rec;
		}
		else{
			$recs[]=$rec;
		}
	}
	odbc_free_result($dbh_hana_result);
	if($writefile==1){
		@fclose($fh);
		if(isset($params['-logfile']) && file_exists($params['-logfile'])){
			$elapsed=microtime(true)-$starttime;
			appendFileContents($params['-logfile'],"Line count:{$i}, Execute Time: ".verboseTime($elapsed).PHP_EOL);
		}
		if(file_exists($params['-filename']) && filesize($params['-filename'])==0){
			unlink($params['-filename']);
		}
		return $i;
	}
	$DATABASE['_lastquery']['stop']=microtime(true);
	$DATABASE['_lastquery']['time']=$DATABASE['_lastquery']['stop']-$DATABASE['_lastquery']['start'];
	return $recs;
}
//---------- begin function hanaGetDBTablePrimaryKeys ----------
/**
* @describe returns an array of primary key fields for the specified table
* @param [$params] array - These can also be set in the CONFIG file with dbname_hana,dbuser_hana, and dbpass_hana
*	[-host] - hana server to connect to
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array returns array of primary key fields
* @usage
*	loadExtras('hana'); 
*	$fields=hanaGetDBTablePrimaryKeys();
*/
function hanaGetDBTablePrimaryKeys($table,$params=array()){
	$parts=preg_split('/\./',strtoupper($table),2);
	$where='';
	if(count($parts)==2){
		$query = "SELECT column_name FROM constraints WHERE SCHEMA_NAME = '{$parts[0]}' AND table_name='{$parts[1]}'";
	}
	else{
		$query = "SELECT column_name FROM constraints WHERE table_name='{$parts[1]}'";
	}
	$tmp = hanaQueryResults($query,$params);
	$keys=array();
	foreach($tmp as $rec){
		$keys[]=$rec['column_name'];
    }
	return $keys;
}
//---------- begin function hanaBuildPreparedInsertStatement ----------
/**
* @describe creates the query needed for a prepared Insert Statement
* @param $table string - tablename
* @param $fieldinfo array - field info obtained from hanaGetDBFieldInfo function
* @return $query string
* @usage
*	loadExtras('hana'); 
*	$query=hanaBuildPreparedInsertStatement($table,$fieldinfo,$primary_keys);
*/
function hanaBuildPreparedInsertStatement($table,$fieldinfo=array(),$params=array()){
	if(!is_array($fieldinfo)){
		$fieldinfo=hanaGetDBFieldInfo($table,$params);
	}
	$fields=array();
	$binds=array();
	foreach($fieldinfo as $field=>$info){
		//handle special fields
		switch(strtolower($field)){
			case 'limit':$field='"'.strtoupper($field).'"';break;
		}
		$fields[]=$field;
		$binds[]='?';
	}
	$fieldstr=implode(', ',$fields);
	$bindstr=implode(', ',$binds);
	$query="INSERT INTO {$table} ({$fieldstr}) VALUES ({$bindstr})";
	return $query;
}
//---------- begin function hanaBuildPreparedReplaceStatement ----------
/**
* @describe creates the query needed for a prepared Upsert Statement
* @param $table string - tablename
* @param $fieldinfo array - field info obtained from hanaGetDBFieldInfo function
* @param $primary_keys array - array of primary keys
* @return $query string
* @usage 
*	loadExtras('hana');
*	$query=hanaBuildPreparedReplaceStatement($table,$fieldinfo,$primary_keys);
*/
function hanaBuildPreparedReplaceStatement($table,$fieldinfo=array(),$keys=array(),$params=array()){
	if(!is_array($keys) || !count($keys)){
		debugValue(array(
			'function'=>'hanaBuildPreparedReplaceStatement',
			'message'=>'missing keys',
		));
		return '';
	}
	if(!is_array($fieldinfo)){
		$fieldinfo=hanaGetDBFieldInfo($table,$params);
	}
	$sets=array();
	$wheres=array();
	foreach($fieldinfo as $field=>$info){
		$bind='?';
		//handle special fields
		switch(strtolower($field)){
			case 'limit':$field='"'.strtoupper($field).'"';break;
		}
		if(in_array($field,$keys)){
			$wheres[]="{$field}={$bind}";
		}
		$fields[]=$field;
		$binds[]='?';
	}
	$fieldstr=implode(', ',$fields);
	$bindstr=implode(', ',$binds);
	$wherestr=implode(' and ',$wheres);
	$query="REPLACE {$table} ({$fieldstr}) VALUES ({$bindstr}) WHERE {$wherestr}";
	return $query;
}
//---------- begin function hanaBuildPreparedUpdateStatement ----------
/**
* @describe creates the query needed for a prepared Update Statement
* @param $table string - tablename
* @param $fieldinfo array - field info obtained from hanaGetDBFieldInfo function
* @param $primary_keys array - array of primary keys
* @return $query string
* @usage 
*	loadExtras('hana');
*	$query=hanaBuildPreparedUpdateStatement($table,$fieldinfo,$primary_keys);
*/
function hanaBuildPreparedUpdateStatement($table,$fieldinfo=array(),$keys=array(),$params=array()){
	if(!is_array($keys) || !count($keys)){
		debugValue(array(
			'function'=>'hanaBuildPreparedUpdateStatement',
			'message'=>'missing keys',
		));
		return '';
	}
	if(!is_array($fieldinfo)){
		$fieldinfo=hanaGetDBFieldInfo($table,$params);
	}
	$sets=array();
	$wheres=array();
	foreach($fieldinfo as $field=>$info){
		$bind='?';
		//handle special fields
		switch(strtolower($field)){
			case 'limit':$field='"'.strtoupper($field).'"';break;
		}
		if(in_array($field,$keys)){
			$wheres[]="{$field}={$bind}";
		}
		else{
			$sets[]="{$field}={$bind}";
		}
	}
	$setstr=implode(', ',$sets);
	$wherestr=implode(' and ',$wheres);
	$query="UPDATE {$table} SET {$setstr} WHERE {$wherestr}";
	return $query;
}
//---------- begin function hanaBuildPreparedDeleteStatement ----------
/**
* @describe creates the query needed for a prepared Delete Statement
* @param $table string - tablename
* @param $fieldinfo array - field info obtained from hanaGetDBFieldInfo function
* @param $primary_keys array - array of primary keys
* @return $query string
* @usage 
*	loadExtras('hana');
*	$query=hanaBuildPreparedDeleteStatement($table,$fieldinfo,$primary_keys);
*/
function hanaBuildPreparedDeleteStatement($table,$fieldinfo=array(),$keys=array(),$params=array()){
	if(!is_array($keys) || !count($keys)){
		debugValue(array(
			'function'=>'hanaBuildPreparedDeleteStatement',
			'message'=>'missing keys',
		));
		return '';
	}
	if(!is_array($fieldinfo)){
		$fieldinfo=hanaGetDBFieldInfo($table,$params);
	}
	$wheres=array();
	foreach($fieldinfo as $field=>$info){
		$bind='?';
		//handle special fields
		switch(strtolower($field)){
			case 'limit':$field='"'.strtoupper($field).'"';break;
		}
		if(in_array($field,$keys)){
			$wheres[]="{$field}={$bind}";
		}
	}
	$wherestr=implode(' and ',$wheres);
	$query="DELETE FROM {$table} WHERE {$wherestr}";
	return $query;
}
function hanaConvert2UTF8($content) { 
    if(!mb_check_encoding($content, 'UTF-8') 
        OR !($content === mb_convert_encoding(mb_convert_encoding($content, 'UTF-32', 'UTF-8' ), 'UTF-8', 'UTF-32'))) {

        $content = mb_convert_encoding($content, 'UTF-8'); 

        if (mb_check_encoding($content, 'UTF-8')) { 
            // log('Converted to UTF-8'); 
        } else { 
            // log('Could not converted to UTF-8'); 
        } 
    } 
    return $content; 
}
function hanaNamedQueryList(){
	return array(
		array(
			'code'=>'running_queries',
			'icon'=>'icon-spin4',
			'name'=>'Running Queries'
		),
		array(
			'code'=>'server_resources',
			'icon'=>'icon-server',
			'name'=>'Server Resources'
		),
		array(
			'code'=>'disk_space',
			'icon'=>'icon-save',
			'name'=>'Disk Space'
		),
		array(
			'code'=>'processors',
			'icon'=>'icon-hardware-cpu',
			'name'=>'CPU Usage'
		),
		array(
			'code'=>'disk_usage',
			'icon'=>'icon-hardware-drive',
			'name'=>'Disk Usage'
		),
		array(
			'code'=>'network_usage',
			'icon'=>'icon-network',
			'name'=>'Network Usage'
		),
		array(
			'code'=>'table_locks',
			'icon'=>'icon-lock',
			'name'=>'Table Locks'
		),
		array(
			'code'=>'sessions',
			'icon'=>'icon-spin8',
			'name'=>'Sessions'
		),
		array(
			'code'=>'tables',
			'icon'=>'icon-table',
			'name'=>'Tables'
		),
		array(
			'code'=>'views',
			'icon'=>'icon-table',
			'name'=>'Views'
		),
		array(
			'code'=>'indexes',
			'icon'=>'icon-marker',
			'name'=>'Indexes'
		),
	);
}
//---------- begin function hanaNamedQuery ----------
/**
* @describe returns pre-build queries based on name
* @param name string
*	[running_queries]
*	[table_locks]
* @return query string
*/
function hanaNamedQuery($name,$str=''){
	switch(strtolower($name)){
		case 'kill':
			return "ALTER SYSTEM CANCEL SESSION '{$str}'";
		break;
		case 'running':
		case 'queries':
		case 'running_queries':
			return <<<ENDOFQUERY
SELECT
	c.connection_id,
	c.host, 
	c.user_name, 
	c.connection_status, 
	c.transaction_id, 
	s.last_executed_time,
	round(s.allocated_memory_size/1024/1024/1024,2) as allocated_memory_gb,
	round(s.used_memory_size/1024/1024/1024,2) as used_mem_gb, s.statement_string
FROM
	m_connections c, m_prepared_statements s
WHERE
	s.connection_id = c.connection_id 
	and c.connection_status != 'IDLE'
ORDER BY
	s.allocated_memory_size desc
ENDOFQUERY;
		break;
		case 'sessions':
			return <<<ENDOFQUERY
SELECT 
	host, 
	port, 
	connection_id, 
	connection_status, 
	connection_type,
	transaction_id, 
	idle_time, 
	auto_commit,
	client_host,
	user_name,
	current_schema_name,
	fetched_record_count
FROM 
	m_connections 
ORDER BY connection_status
ENDOFQUERY;
		break;
		case 'tables':
			return <<<ENDOFQUERY
SELECT
	schema_name,
	table_name,
	comments,
	create_time
FROM public.tables
WHERE 
	is_system_table = 'FALSE'
	AND schema_name NOT IN ('SYS','UIS','SYSTEM','SYS_REPL')
	AND schema_name NOT LIKE '_SYS_%'
	AND schema_name NOT LIKE 'SAP_%'
	AND schema_name NOT LIKE 'HANA_%'
ORDER BY 1,2
ENDOFQUERY;
		break;
		case 'views':
			return <<<ENDOFQUERY
SELECT
	schema_name,
	view_name,
	comments,
	definition,
	create_time
FROM public.views
WHERE 
	is_valid = 'TRUE'
	AND schema_name NOT IN ('SYS','UIS','SYSTEM','SYS_REPL')
	AND schema_name NOT LIKE '_SYS_%'
	AND schema_name NOT LIKE 'SAP_%'
	AND schema_name NOT LIKE 'HANA_%'
ORDER BY 1,2
ENDOFQUERY;
		break;
		case 'indexes':
			return <<<ENDOFQUERY
SELECT
	si.schema_name,
	si.table_name,
	si.index_name AS name,
	STRING_AGG(sic.column_name,',') AS keys,
	CASE index_type WHEN 'CPBTREE UNIQUE' THEN 1 ELSE 0 END AS is_unique
FROM sys.indexes si, sys.index_columns sic
WHERE 
	si.table_name = sic.table_name
	AND si.schema_name NOT IN ('SYS','UIS','SYSTEM','SYS_REPL')
	AND si.schema_name NOT LIKE '_SYS_%'
	AND si.schema_name NOT LIKE 'SAP_%'
	AND si.schema_name NOT LIKE 'HANA_%'
GROUP BY 
	si.schema_name,
	si.table_name,
	si.index_name,
	CASE index_type WHEN 'CPBTREE UNIQUE' THEN 1 ELSE 0 END
ORDER BY 1,2,3
ENDOFQUERY;
		break;
		case 'table_locks':
			return <<<ENDOFQUERY
SELECT * FROM m_table_locks
ENDOFQUERY;
		break;
		case 'disk_space':
			return <<<ENDOFQUERY
-- listopts:size_gb_options={"class":"align-right","number_format":"2"}
-- listopts:pcnt_used_options={"class":"align-right","number_format":"2"}
-- listopts:pcnt_avail_options={"class":"align-right","number_format":"2"}
WITH fs_sizes AS (
    SELECT host,caption,value,unit,measured_element_name
    FROM M_HOST_AGENT_METRICS
    WHERE definition_id = 'FS.Size'
)
,fs_avail AS (
    SELECT value,measured_element_name
    FROM M_HOST_AGENT_METRICS
    WHERE definition_id = 'FS.AvailableSpace'
)
SELECT
    fs.host,
    fs.measured_element_name as fs_mount,
    TO_DECIMAL((fs.value/1024/1024/1024),10,2) as size_gb,
    TO_DECIMAL(100-((fa.value/fs.value)*100),3,2) as pcnt_used,
    TO_DECIMAL(((fa.value/fs.value)*100),3,2) as pcnt_avail
FROM fs_sizes fs
    INNER JOIN fs_avail fa on fs.measured_element_name=fa.measured_element_name
ENDOFQUERY;
		break;
		case 'disk_usage':
			return <<<ENDOFQUERY
-- listopts:avg_queue_length_options={"class":"align-right","number_format":"2"}
-- listopts:avg_service_time_options={"class":"align-right","number_format":"2"}
-- listopts:avg_wait_time_options={"class":"align-right","number_format":"2"}
-- listopts:io_rate_options={"class":"align-right","number_format":"2"}
-- listopts:total_throughput_options={"class":"align-right","number_format":"2"}
-- listopts:util_options={"class":"align-right","number_format":"2"}
WITH avg_queue_length as (
       SELECT definition_id,measured_element_name,value
       FROM M_HOST_AGENT_METRICS
       WHERE measured_element_type='Disk' and definition_id='Disk.AverageQueueLength'
),avg_service_time AS (
       SELECT measured_element_name,value
       FROM M_HOST_AGENT_METRICS
       WHERE measured_element_type='Disk' and definition_id='Disk.AverageServiceTime'
),avg_wait_time AS (
       SELECT measured_element_name,value
       FROM M_HOST_AGENT_METRICS
       WHERE measured_element_type='Disk' and definition_id='Disk.AverageWaitTime'
),io_rate AS (
       SELECT measured_element_name,value
       FROM M_HOST_AGENT_METRICS
       WHERE measured_element_type='Disk' and definition_id='Disk.IORate'
),total_throughput AS (
       SELECT measured_element_name,value
       FROM M_HOST_AGENT_METRICS
       WHERE measured_element_type='Disk' and definition_id='Disk.TotalThroughput'
),util AS (
       SELECT measured_element_name,value
       FROM M_HOST_AGENT_METRICS
       WHERE measured_element_type='Disk' and definition_id='Disk.Util'
)
SELECT
       aql.measured_element_name as name,
       aql.value as avg_queue_length,
       ast.value as avg_service_time,
       awt.value as avg_wait_time,
       ir.value as io_rate,
       tt.value as total_throughput,
       u.value as util
FROM avg_queue_length aql
INNER JOIN avg_service_time ast ON ast.measured_element_name=aql.measured_element_name
INNER JOIN avg_wait_time awt ON awt.measured_element_name=aql.measured_element_name
INNER JOIN io_rate ir ON ir.measured_element_name=aql.measured_element_name
INNER JOIN total_throughput tt ON tt.measured_element_name=aql.measured_element_name
INNER JOIN util u ON u.measured_element_name=aql.measured_element_name
ORDER BY aql.measured_element_name
ENDOFQUERY;
		case 'processors':
			return <<<ENDOFQUERY
-- listopts:idle_options={"class":"align-right","number_format":"2"}
-- listopts:system_options={"class":"align-right","number_format":"2"}
-- listopts:user_options={"class":"align-right","number_format":"2"}
-- listopts:wait_options={"class":"align-right","number_format":"2"}
WITH idle_cpus as (
       SELECT definition_id,measured_element_name,value
       FROM M_HOST_AGENT_METRICS
       WHERE measured_element_type='Processor' and definition_id='Proc.IdleTimePercentage'
),system_cpus AS (
       SELECT measured_element_name,value
       FROM M_HOST_AGENT_METRICS
       WHERE measured_element_type='Processor' and definition_id='Proc.SystemTimePercentage'
),user_cpus AS (
       SELECT measured_element_name,value
       FROM M_HOST_AGENT_METRICS
       WHERE measured_element_type='Processor' and definition_id='Proc.UserTimePercentage'
),wait_cpus AS (
       SELECT measured_element_name,value
       FROM M_HOST_AGENT_METRICS
       WHERE measured_element_type='Processor' and definition_id='Proc.WaitTimePercentage'
)
SELECT
       ic.measured_element_name as cpu,
       sc.value as system,
       uc.value as user,
       wc.value as wait,
       ic.value as idle
FROM idle_cpus ic
INNER JOIN system_cpus sc ON sc.measured_element_name=ic.measured_element_name
INNER JOIN user_cpus uc ON uc.measured_element_name=ic.measured_element_name
INNER JOIN wait_cpus wc ON wc.measured_element_name=ic.measured_element_name
ORDER BY CAST(ic.measured_element_name as numeric)
ENDOFQUERY;
		break;
		case 'network_usage':
			return <<<ENDOFQUERY
-- listopts:kbyte_receive_rate_options={"class":"align-right","number_format":"2"}
-- listopts:kbyte_transmit_rate_options={"class":"align-right","number_format":"2"}
-- listopts:transmit_error_rate_options={"class":"align-right","number_format":"2"}
-- listopts:receive_error_rate_options={"class":"align-right","number_format":"2"}
-- listopts:packet_receive_rate_options={"class":"align-right","number_format":"2"}
-- listopts:packet_transmit_rate_options={"class":"align-right","number_format":"2"}
WITH CollisionRate as (
       SELECT definition_id,measured_element_name,value
       FROM M_HOST_AGENT_METRICS
       WHERE measured_element_type='NetworkPort' and definition_id='Net.CollisionRate'
),KByteReceiveRate AS (
       SELECT measured_element_name,value
       FROM M_HOST_AGENT_METRICS
       WHERE measured_element_type='NetworkPort' and definition_id='Net.KByteReceiveRate'
),KByteTransmitRate AS (
       SELECT measured_element_name,value
       FROM M_HOST_AGENT_METRICS
       WHERE measured_element_type='NetworkPort' and definition_id='Net.KByteTransmitRate'
),TransmitErrorRate AS (
       SELECT measured_element_name,value
       FROM M_HOST_AGENT_METRICS
       WHERE measured_element_type='NetworkPort' and definition_id='Net.TransmitErrorRate'
),ReceiveErrorRate AS (
       SELECT measured_element_name,value
       FROM M_HOST_AGENT_METRICS
       WHERE measured_element_type='NetworkPort' and definition_id='Net.ReceiveErrorRate'
),PacketReceiveRate AS (
       SELECT measured_element_name,value
       FROM M_HOST_AGENT_METRICS
       WHERE measured_element_type='NetworkPort' and definition_id='Net.PacketReceiveRate'
),PacketTransmitRate AS (
       SELECT measured_element_name,value
       FROM M_HOST_AGENT_METRICS
       WHERE measured_element_type='NetworkPort' and definition_id='Net.PacketTransmitRate'
)
SELECT
       cr.measured_element_name as device,
       cr.value as collission_rate,
       krr.value as kbyte_receive_rate,
       ktr.value as kbyte_transmit_rate,
       ter.value as transmit_error_rate,
       rer.value as receive_error_rate,
       prr.value as packet_receive_rate,
       ptr.value as packet_transmit_rate
FROM CollisionRate cr
INNER JOIN KByteReceiveRate krr ON krr.measured_element_name=cr.measured_element_name
INNER JOIN KByteTransmitRate ktr ON ktr.measured_element_name=cr.measured_element_name
INNER JOIN TransmitErrorRate ter ON ter.measured_element_name=cr.measured_element_name
INNER JOIN ReceiveErrorRate rer ON rer.measured_element_name=cr.measured_element_name
INNER JOIN PacketReceiveRate prr ON prr.measured_element_name=cr.measured_element_name
INNER JOIN PacketTransmitRate ptr ON ptr.measured_element_name=cr.measured_element_name
ORDER BY cr.measured_element_name
ENDOFQUERY;
		case 'server_resources':
			return <<<ENDOFQUERY
-- listopts:mem_tot_gb_options={"class":"align-right","number_format":"2"}
-- listopts:mem_free_gb_options={"class":"align-right","number_format":"2"}
-- listopts:loadavg_1min_options={"class":"align-right","number_format":"2"}
-- listopts:loadavg_5min_options={"class":"align-right","number_format":"2"}
-- listopts:loadavg_15min_options={"class":"align-right","number_format":"2"}
-- listopts:steal_pct_options={"class":"align-right","number_format":"2"}
-- listopts:ctx_switches_per_s_options={"class":"align-right","number_format":"2"}
-- listopts:interrupts_per_s_options={"class":"align-right","number_format":"2"}
SELECT
  SUBSTR(MAX(timestamp),0,19) timestamp,
  HOST,
  TO_DECIMAL((MAX(MAP(caption, 'Available Physical Memory', TO_NUMBER(value), 0)) / 1024 / 1024),10,2) mem_tot_gb,
  TO_DECIMAL((MAX(MAP(caption, 'Free Physical Memory', TO_NUMBER(value), 0)) / 1024 / 1024),10,2) mem_free_gb,
  MAX(MAP(caption, 'Load Average 1 Minute', TO_NUMBER(value), 0)) loadavg_1min,
  MAX(MAP(caption, 'Load Average 5 Minutes', TO_NUMBER(value), 0)) loadavg_5min,
  MAX(MAP(caption, 'Load Average 15 Minutes', TO_NUMBER(value), 0)) loadavg_15min,
  MAX(MAP(caption, 'Steal Time', TO_NUMBER(value), 0)) steal_pct,
  MAX(MAP(caption, 'Context Switch Rate', TO_NUMBER(value), 0)) ctx_switches_per_s,
  MAX(MAP(caption, 'Interrupt Rate', TO_NUMBER(value), 0)) interrupts_per_s
FROM
  M_HOST_AGENT_METRICS
WHERE
  measured_element_type = 'OperatingSystem'
GROUP BY
  HOST
ENDOFQUERY;
		break;
	}
}