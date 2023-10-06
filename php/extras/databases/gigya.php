<?php

/*
	
	gigya can be queried using SQL like a database.
	Usage:
		<database
	        name="gigya"
	        dbhost="{data-center}"
	        dbtype="gigya"
	        dbuser="{userKey}"
	        dbpass="{secretKey}"
	        dbkey="{apiKey}"
	    />
Auditlog:
	https://help.sap.com/docs/SAP_CUSTOMER_DATA_CLOUD/8b8d6fffe113457094a17701f63e3d6a/4143815a70b21014bbc5a10ce4041860.html

	SELECT
		uid as dist_id,
		params.loginid as loginid,
		timestamp,
		err_code,
		err_details,
		err_message,
		endpoint,
		http_req.country as country,
		ip,
		params.loginID as login_id,
		params.pageURL as url,
		user_agent.os as os,
		user_agent.browser as browser,
		user_agent.platform as platform,
		auth_type
	FROM auditLog 
	WHERE 
		endpoint = "accounts.login" 
	ORDER BY @timestamp 
	limit 10

Accounts:
	https://help.sap.com/docs/SAP_CUSTOMER_DATA_CLOUD/8b8d6fffe113457094a17701f63e3d6a/b32ce0918af44c3ebd7e96650fa6cc1d.html

	SELECT
		uid as dist_id,
		profile.firstname as firstname,
		profile.lastname as lastname,
		profile.email as email,
		profile.username as username,
		is_verified,
		is_registered,
		is_active,
		last_login,
		lastUpdatedTimestamp as last_updated_timestamp
	FROM accounts
	ORDER BY lastUpdatedTimestamp desc
	limit 10

https://help.sap.com/docs/SAP_CUSTOMER_DATA_CLOUD/8b8d6fffe113457094a17701f63e3d6a/415f681b70b21014bbc5a10ce4041860.html

Script:
	SELECT
		id,
		description,
		input,
		output,
		create_time,
		update_time,
		script
	FROM script
	LIMIT 10

idx_job_status:
	SELECT
		site_id,
		host_name,
		dataflow_name,
		start_time,
		end_time,
		frequency_type,
		success_email_notification,
		failure_email_notification,
		processed_records,
		attempt_number,
		status
	FROM idx_job_status
	ORDER BY updateTime
	LIMIT 10

*/

//---------- begin function gigyaGetTableDDL ----------
/**
* @describe returns create script for specified table
* @param table string - tablename
* @param [schema] string - schema. defaults to dbschema specified in config
* @return string
* @usage $createsql=gigyaGetTableDDL('sample');
*/
function gigyaGetTableDDL($table,$schema=''){
	$recs=array();
	return $recs;
}

function gigyaGetDBIndexes($tablename=''){
	return gigyaGetDBTableIndexes($tablename);
}

function gigyaGetDBTableIndexes($tablename=''){
	$recs=array();
	return $recs;
}

//---------- begin function gigyaIsDBTable ----------
/**
* @describe returns true if table exists
* @param $tablename string - table name
* @param $schema string - schema name
* @param $params array - These can also be set in the CONFIG file with dbname_gigya,dbuser_gigya, and dbpass_gigya
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if table exists
* @usage if(gigyaIsDBTable('abc')){...}
*/
function gigyaIsDBTable($table,$params=array()){
	if(!strlen($table)){
		echo "gigyaIsDBTable error: No table";
		return null;
	}
	$tables=gigyaGetDBTables();
	if(in_array($table,$tables)){return true;}
	return false;
}

//---------- begin function gigyaGetDBTables ----------
/**
* @describe returns an array of tables
* @param $params array - These can also be set in the CONFIG file with dbname_gigya,dbuser_gigya, and dbpass_gigya
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array returns array of table names
* @usage $tables=gigyaGetDBTables();
*/
function gigyaGetDBTables($params=array()){
	$tables=array(
		'accounts',
		'auditlog',
		'datastore',
		'dataflow',
		'dataflow_draft',
		'dataflow_draft_version',
		'dataflow_draft_version',
		'scheduling',
		'idx_job_status',
		'script'
	);
	return $tables;
}
//---------- begin function gigyaGetDBFieldInfo ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name,type,scale, precision, length, num are
* @param $params array - These can also be set in the CONFIG file with dbname_gigya,dbuser_gigya, and dbpass_gigya
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
*	[-getmeta] - boolean - if true returns info in _fielddata table for these fields - defaults to false
*	[-field] - if this has a value return only this field - defaults to blank
*	[-force] - clear cache and refetch info
* @return boolean returns true on success
* @usage $fieldinfo=gigyaGetDBFieldInfo('abcschema.abc');
*/
function gigyaGetDBFieldInfo($tablename,$params=array()){
	$fieldinfo=gigyaQueryResults("SELECT * FROM {$tablename} limit 1",array('-fieldinfo'=>1));
	//echo printValue($fieldinfo);exit;
	$recs=array();
	foreach($fieldinfo as $k=>$rec){
		$recs[]=$rec;
	}
	return $recs;
}

//---------- begin function gigyaQueryResults ----------
/**
* @describe returns the records of a query
* @param $params array - These can also be set in the CONFIG file with dbname_gigya,dbuser_gigya, and dbpass_gigya
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	[-filename] - if you pass in a filename then it will write the results to the csv filename you passed in
* @return $recs array
* @usage $recs=gigyaQueryResults('select top 50 * from abcschema.abc');
*/
function gigyaQueryResults($query,$params=array()){
	global $DATABASE;
	global $CONFIG;

	$DATABASE['_lastquery']=array(
		'start'=>microtime(true),
		'stop'=>0,
		'time'=>0,
		'error'=>'',
		'query'=>$query,
		'function'=>'gigyaQueryResults'
	);
	$db=$DATABASE[$CONFIG['db']];
	$table='';
	if(preg_match('/([\s\r\n]+)from([\s\r\n]+)([a-z\_]+)/is',$query,$m)){
		switch(strtolower(trim($m[3]))){
			case 'accounts':
				$table='accounts';
			break;
			case 'audit':
			case 'auditlog':
				$table='audit';
			break;
			case 'ds':
			case 'data':
			case 'datastore':
				$table='ds';
			break;
			case 'dataflow':
			case 'dataflow_draft':
			case 'dataflow_version':
			case 'dataflow_draft_version':
			case 'scheduling':
			case 'idx_job_status':
			case 'script':
				$table='idx';
			break;
		}
	}
	if(!strlen($table)){return "Invalid Table name: ".printValue($m);}
	//get fields to return
	$fields=array();
	if(preg_match('/SELECT(.+?)FROM/is',$query,$m)){
		//echo printValue($m);exit;
		$rtag=$m[0];
		if(!stringContains(trim($m[1]),'count(') && trim($m[1])!='*'){
			$xfields=preg_split('/\,/',trim($m[1]));
			foreach($xfields as $f=>$field){
				$fields[]=strtolower(trim($field));
			}
			$query=str_replace($rtag,'SELECT * FROM',$query);
		}
	}
	//echo "gigyaQueryResults: ".$query.printValue($fields);exit;
	$url="https://{$table}.us1.gigya.com/{$table}.search";
	/*
		"totalCount": 10109796,
        "statusCode": 200,
        "errorCode": 0,
        "statusReason": "OK",
        "callId": "05a743768f25416d8f12835ff4f8dd3c",
        "time": "2023-09-09T14:17:58.294Z",
        "objectsCount": 300
        "results":[{},{},....]

        //to paginate use START and LIMIT
        	select * from auditlog START 10000 limit 10000

	*/
    //set START and LIMIT to paginate the results
    $offset=0;
    $limit=0;
    if(preg_match('/([\s\r\n]+)limit([\s\r\n]+)(.+)$/is',$query,$m)){
    	$limit=(integer)trim($m[3]);
    	$query=preg_replace('/([\s\r\n]+)limit([\s\r\n]+)(.+)$/is','',$query);
    }
    if(preg_match('/([\s\r\n]+)start([\s\r\n]+)(.+)$/is',$query,$m)){
    	$offset=(integer)trim($m[3]);
    	$query=preg_replace('/([\s\r\n]+)start([\s\r\n]+)(.+)$/is','',$query);
    }
    $poffset=0;
    $plimit=5000;
    if(isset($CONFIG['gigya_limit'])){
    	$plimit=(integer)$CONFIG['gigya_limit'];
    }
    //set a maxloops as a safety precaution - this will limit the total number of records to 50M rows
    $maxloops=5000;
    if(isset($CONFIG['gigya_maxloops'])){
    	$maxloops=(integer)$CONFIG['gigya_maxloops'];
    }
    if($limit > 0 && $limit < $plimit){
    	$plimit=$limit;
    }
    $recs_count=0;
    $recs=array();
    if(isset($params['-filename']) && file_exists($params['-filename'])){
    	unlink($params['-filename']);
    }
    $loop=0;
    $fieldinfo=array();
    if(isset($params['-logfile'])){
    	setFileContents($params['-logfile'],printValue($params));
    }
    if(isset($params['-logfile'])){
    	appendFileContents($params['-logfile'],time().", PLIMIT: {$plimit}, MAXLOOPS: {$maxloops}".PHP_EOL);
    }
    $nextcursorid='';
    //echo "<pre>{$query}</pre>";exit;
    while($loop < $maxloops){
    	$loop+=1;
    	//$cquery=$query." START {$poffset} LIMIT {$plimit}";
    	$cquery=$query." LIMIT {$plimit}";
    	$postopts=array(
			'-method'=>'POST',
			'apiKey'=>$db['dbkey'],
			'userKey'=>$db['dbuser'],
			'secret'=>$db['dbpass'],
			'format'=>'json',
			'-json'=>1,
		);
		if(!strlen($nextcursorid)){
			$postopts['openCursor']='true';
			$postopts['query']=$cquery;
			if(isset($params['-logfile'])){
		    	appendFileContents($params['-logfile'],time().",openCURSOR: true,  POSTING: LOOP {$loop}".PHP_EOL);
		    }
		}
		else{
			$postopts['cusrorId']=$nextcursorid;
			if(isset($params['-logfile'])){
		    	appendFileContents($params['-logfile'],time().", CURSORID SET,  POSTING: LOOP {$loop}".PHP_EOL.$nextcursorid.PHP_EOL);
		    }
		}
		//echo printValue($postopts);exit;
		$post=postURL($url,$postopts);
		if(isset($post['json_array']['nextCursorId'])){
			$nextcursorid=$post['json_array']['nextCursorId'];
		}

		if(isset($params['-logfile'])){
	    	appendFileContents($params['-logfile'],time().", RETURNED:".PHP_EOL);
	    }
		//echo printValue($postopts).printValue($post);exit;
		if(isset($post['json_array']['errorMessage'])){
			if($post['json_array']['errorCode']=='500001'){
				if(isset($params['-logfile'])){
			    	appendFileContents($params['-logfile'],time().", 500001 error: sleeping for 5 seconds".PHP_EOL);
			    }
				sleep(10);
				if(isset($params['-logfile'])){
			    	appendFileContents($params['-logfile'],time().", POSTING after 500001 error: LOOP {$loop}".PHP_EOL);
			    }
				$post=postURL($url,$postopts);
				if(isset($params['-logfile'])){
			    	appendFileContents($params['-logfile'],time().", RETURNED:".PHP_EOL);
			    }
			}
		}
		if(isset($post['json_array']['errorMessage'])){
			if(isset($params['-logfile'])){
		    	appendFileContents($params['-logfile'],"{$post['json_array']['errorCode']} - {$post['json_array']['errorMessage']}");
		    	appendFileContents($params['-logfile'],printValue($post));
		    }
			return "<div class=\"w_red\">{$post['json_array']['errorCode']} - {$post['json_array']['errorMessage']}</div>".printValue($post);
		}
		if(!isset($post['json_array']['results']) && isset($post['json_array']['result'])){
			$post['json_array']['results']=$post['json_array']['result'];
		}
		if(isset($post['json_array']['resultCount']) && $post['json_array']['resultCount']==0){
			break;
		}
		if(!isset($post['json_array']['results'][0])){
			break;
		}
		if(!isset($post['json_array']['statusCode']) || $post['json_array']['statusCode'] != 200){
			echo printValue($post);exit;
			break;
		}
		if(isset($post['json_array']['results'])){
			foreach($post['json_array']['results'] as $result){
				//fix fields
				foreach($result as $k=>$v){
					$xk=$k;
					switch(strtolower($xk)){
						case 'count':
						case 'count(*)':
							$xk='count';
						break;
						case 'serverip':$xk='server_ip';break;
						case '@timestamp':$xk='timestamp';break;
						case 'uid':$xk='uid';break;
					}
					$xk=str_replace('ID','Id',$xk);
					$xk = strtolower(preg_replace('/([A-Z])/', '_$1', $xk));
					if(!isset($fieldinfo[$xk])){
						$fieldinfo[$k]=array(
							'field'=>$xk,
							'_dbfield'=>$k
						);
						$jv=decodeJson($v);
						if(is_array($jv)){
							if(!isset($jv[0])){
								$jfields=array_keys($jv);
								$fieldinfo[$k]['_dbtype_ex']=implode(',<br>',$jfields);
							}
							else{
								$fieldinfo[$k]['_dbtype_ex']='JSON';
							}
						}
						elseif(isDate($v)){
							$fieldinfo[$k]['_dbtype_ex']='date';	
						}
						elseif($v=='1' || $v=='0'){
							$fieldinfo[$k]['_dbtype_ex']='int';	
						}
						elseif(preg_match('/^[0-9]+$/',$v)){
							$fieldinfo[$k]['_dbtype_ex']='bigint';
						}
						else{
							$fieldinfo[$k]['_dbtype_ex']='string';
						}
					}
				}
			}
			$post['json_array']['results']=gigyaLowerKeys($post['json_array']['results']);
			foreach($post['json_array']['results'] as $result){
				//fix fields
				$xrec=array();
				foreach($result as $k=>$v){
					$xk=$k;
					switch(strtolower($xk)){
						case 'count':
						case 'count(*)':
							$xk='count';
						break;
						case 'serverip':$xk='server_ip';break;
						case '@timestamp':$xk='timestamp';break;
						case 'uid':$xk='uid';break;
					}
					$xk=str_replace('ID','Id',$xk);
					$xk = strtolower(preg_replace('/([A-Z])/', '_$1', $xk));
					$k=strtolower($k);
					$xrec[$k]=$xrec[$xk]=$v;
				}
				$rec=array();
				if(count($fields)){
					foreach($fields as $fieldstr){
						$aname='';
						if(preg_match('/([\s\r\n]+)AS([\s\r\n]+)(.+)$/is',$fieldstr,$m)){
							$aname=$m[3];
							$fieldstr=str_replace($m[0],'',$fieldstr);
						}
						$parts=preg_split('/\./',$fieldstr,2);
						$field=$parts[0];
						//echo $field.printValue($xrec[$field]);exit;
						if(isset($xrec[$field])){
							if(count($parts)==1){
								$val=$xrec[$field];
							}
							else{
								$subfield=$parts[1];
								$val=$xrec[$field][$subfield];
							}
							if(is_array($val)){$val=encodeJson($val);}
							if(strlen($aname)){
								$rec[$aname]=$val;
							}
							else{
								$field=implode('_',$parts);
								$rec[$field]=$val;
							}
						}
						else{
							if(strlen($aname)){
								$rec[$aname]='';
							}
							else{
								$rec[$field]='';
							}
						}
					}
				}
				else{
					foreach($xrec as $k=>$v){
						if(is_array($v)){$v=encodeJson($v);}
						$rec[$k]=$v;
					}
				}
				$recs[]=$rec;
				$recs_count+=1;
				if($limit > 0 and $recs_count >= $limit){
					break;
				}
			}
			if(isset($params['-filename'])){
				if(file_exists($params['-filename'])){
					$csv=arrays2CSV($recs,array('-noheader'=>1)).PHP_EOL;
					setFileContents($params['-filename'],$csv,1);
				}
				else{
					//new file - set utf8 bit and include header row
					$csv="\xEF\xBB\xBF".arrays2CSV($recs).PHP_EOL;
					setFileContents($params['-filename'],$csv);
				}
				$recs=array();
			}
		}
		if(!isset($post['json_array']['nextCursorId'])){
			if(isset($params['-logfile'])){
		    	appendFileContents($params['-logfile'],time().", No nextCursorId - DONE".PHP_EOL);
		    }
			break;
		}
		if(stringContains($query,'count(*)')){
			if(isset($params['-logfile'])){
		    	appendFileContents($params['-logfile'],time().", Count detected - DONE".PHP_EOL);
		    }
			break;
		}
		if(isset($post['json_array']['objectsCount']) && $post['json_array']['objectsCount'] < $plimit){
			if(isset($params['-logfile'])){
		    	appendFileContents($params['-logfile'],time().", ObjectsCount is less - DONE".PHP_EOL);
		    }
			break;
		}
		if($limit > 0 and $recs_count >= $limit){
			if(isset($params['-logfile'])){
		    	appendFileContents($params['-logfile'],time().", hit limit - DONE".PHP_EOL);
		    }
			break;
		}
		$poffset+=$plimit;
	}
	if(isset($params['-filename'])){
		return $recs_count;
	}
	elseif(isset($params['-fieldinfo'])){
		return $fieldinfo;
	}
	return $recs;
}

function gigyaRandomUserAgent(){
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/604.3.5 (KHTML, like Gecko) Version/11.0.1 Safari/604.3.5";
	$userAgentArray[] = "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:57.0) Gecko/20100101 Firefox/57.0";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.89 Safari/537.36 OPR/49.0.2725.47";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_2) AppleWebKit/604.4.7 (KHTML, like Gecko) Version/11.0.2 Safari/604.4.7";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:57.0) Gecko/20100101 Firefox/57.0";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.108 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64; rv:57.0) Gecko/20100101 Firefox/57.0";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36 Edge/15.15063";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:57.0) Gecko/20100101 Firefox/57.0";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36 Edge/16.16299";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/604.4.7 (KHTML, like Gecko) Version/11.0.2 Safari/604.4.7";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/604.3.5 (KHTML, like Gecko) Version/11.0.1 Safari/604.3.5";
	$userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64; rv:52.0) Gecko/20100101 Firefox/52.0";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 6.3; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.108 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:52.0) Gecko/20100101 Firefox/52.0";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36 OPR/49.0.2725.64";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.108 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; rv:57.0) Gecko/20100101 Firefox/57.0";
	$userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.106 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/604.4.7 (KHTML, like Gecko) Version/11.0.2 Safari/604.4.7";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.11; rv:57.0) Gecko/20100101 Firefox/57.0";
	$userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/62.0.3202.94 Chrome/62.0.3202.94 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; WOW64; rv:56.0) Gecko/20100101 Firefox/56.0";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:58.0) Gecko/20100101 Firefox/58.0";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; Trident/7.0; rv:11.0) like Gecko";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:52.0) Gecko/20100101 Firefox/52.0";
	$userAgentArray[] = "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0;  Trident/5.0)";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; rv:52.0) Gecko/20100101 Firefox/52.0";
	$userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/63.0.3239.84 Chrome/63.0.3239.84 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (X11; Fedora; Linux x86_64; rv:57.0) Gecko/20100101 Firefox/57.0";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:56.0) Gecko/20100101 Firefox/56.0";
	$userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.108 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.89 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/5.0;  Trident/5.0)";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/603.3.8 (KHTML, like Gecko) Version/10.1.2 Safari/603.3.8";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:57.0) Gecko/20100101 Firefox/57.0";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/604.3.5 (KHTML, like Gecko) Version/11.0.1 Safari/604.3.5";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/603.3.8 (KHTML, like Gecko) Version/10.1.2 Safari/603.3.8";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; WOW64; rv:57.0) Gecko/20100101 Firefox/57.0";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.79 Safari/537.36 Edge/14.14393";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:56.0) Gecko/20100101 Firefox/56.0";
	$userAgentArray[] = "Mozilla/5.0 (iPad; CPU OS 11_1_2 like Mac OS X) AppleWebKit/604.3.5 (KHTML, like Gecko) Version/11.0 Mobile/15B202 Safari/604.1";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; Touch; rv:11.0) like Gecko";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:58.0) Gecko/20100101 Firefox/58.0";
	$userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Safari/604.1.38";
	$userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
	$userAgentArray[] = "Mozilla/5.0 (X11; CrOS x86_64 9901.77.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.97 Safari/537.36";
	
	$getArrayKey = array_rand($userAgentArray);
	return $userAgentArray[$getArrayKey];
}

function gigyaLowerKeys($arr, $case = CASE_LOWER)
{
    return array_map(function($item) use($case) {
        if(is_array($item)){
            $item = gigyaLowerKeys($item, $case);
        }
        return $item;
    },array_change_key_case($arr, $case));
}

//---------- begin function gigyaGetDBRecords
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
*	gigyaGetDBRecords(array('-table'=>'notes'));
*	gigyaGetDBRecords("select * from myschema.mytable where ...");
*/
function gigyaGetDBRecords($params){
	global $USER;
	global $CONFIG;
	//echo "gigyaGetDBRecords".printValue($params);exit;
	if(empty($params['-table']) && !is_array($params) && preg_match('/^(with|select|pragma)\ /is',trim($params))){
		//they just entered a query
		$query=$params;
		$params=array();
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
		//echo printValue($params);
		$fields=gigyaGetDBFieldInfo($params['-table']);
		$ands=array();
		foreach($params as $k=>$v){
			$k=strtolower($k);
			if(!strlen(trim($v))){continue;}
			if(!isset($fields[$k])){continue;}
			if(is_array($params[$k])){
	            $params[$k]=implode(':',$params[$k]);
			}
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
	$recs=gigyaQueryResults($query,$params);
	//echo '<hr>'.$query.printValue($params).printValue($recs);
	return $recs;
}
//---------- begin function gigyaGetDBRecordsCount ----------
/**
* @describe retrieves records from DB based on params
* @param $params array
* 	-table 	  - table to query
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array recordsets
* @usage $cnt=gigyaGetDBRecordsCount(array('-table'=>'tesl));
*/
function gigyaGetDBRecordsCount($params=array()){
	$params['-fields']='count(*) cnt';
	if(isset($params['-order'])){unset($params['-order']);}
	if(isset($params['-limit'])){unset($params['-limit']);}
	if(isset($params['-offset'])){unset($params['-offset']);}
	$recs=gigyaGetDBRecords($params);
	return $recs[0]['cnt'];
}
function gigyaNamedQueryList(){
	return array();
}
//---------- begin function gigyaNamedQuery ----------
/**
* @describe returns pre-build queries based on name
* @param name string
*	[running_queries]
*	[table_locks]
* @return query string
*/
function gigyaNamedQuery($name){
	switch(strtolower($name)){
		case 'tables':
			return <<<ENDOFQUERY
ENDOFQUERY;
		break;
	}
}
