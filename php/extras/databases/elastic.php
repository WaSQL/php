<?php

/*
	References
		https://www.elastic.co/guide/en/elasticsearch/reference/current/http-clients.html
		https://www.elastic.co/guide/en/elasticsearch/reference/current/sql-search-api.html
		https://www.elastic.co/guide/en/elasticsearch/reference/current/sql-syntax-show-tables.html
		https://www.elastic.co/guide/en/elasticsearch/reference/current/sql-syntax-show-columns.html
	Elasticsearch can be queried using SQL like a database.
	Usage:
		<database
	        name="elasticsearch_dev"
	        dbhost="https://logs.abccompany.com"
	        dbport="8200"
	        dbtype="elastic"
	        dbuser="{username}"
	        dbpass="{password}"
	    />


*/

//---------- begin function elasticGetTableDDL ----------
/**
* @describe returns create script for specified table
* @param table string - tablename
* @param [schema] string - schema. defaults to dbschema specified in config
* @return string
* @usage $createsql=elasticGetTableDDL('sample');
*/
function elasticGetTableDDL($table,$schema=''){
	$recs=array();
	return $recs;
}

function elasticGetDBIndexes($tablename=''){
	return elasticGetDBTableIndexes($tablename);
}

function elasticGetDBTableIndexes($tablename=''){
	$recs=array();
	return $recs;
}

//---------- begin function elasticIsDBTable ----------
/**
* @describe returns true if table exists
* @param $tablename string - table name
* @param $schema string - schema name
* @param $params array - These can also be set in the CONFIG file with dbname_elastic,dbuser_elastic, and dbpass_elastic
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return boolean returns true if table exists
* @usage if(elasticIsDBTable('abc')){...}
*/
function elasticIsDBTable($table,$params=array()){
	if(!strlen($table)){
		echo "elasticIsDBTable error: No table";
		return null;
	}
	$tables=elasticGetDBTables();
	if(in_array($table,$tables)){return true;}
	return false;
}

//---------- begin function elasticGetDBTables ----------
/**
* @describe returns an array of tables
* @param $params array - These can also be set in the CONFIG file with dbname_elastic,dbuser_elastic, and dbpass_elastic
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array returns array of table names
* @usage $tables=elasticGetDBTables();
*/
function elasticGetDBTables($params=array()){
	$recs=elasticQueryResults("Show Tables");
	return $recs;
}
//---------- begin function elasticGetDBFieldInfo ----------
/**
* @describe returns an array of field info. fieldname is the key, Each field returns name,type,scale, precision, length, num are
* @param $params array - These can also be set in the CONFIG file with dbname_elastic,dbuser_elastic, and dbpass_elastic
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
*	[-getmeta] - boolean - if true returns info in _fielddata table for these fields - defaults to false
*	[-field] - if this has a value return only this field - defaults to blank
*	[-force] - clear cache and refetch info
* @return boolean returns true on success
* @usage $fieldinfo=elasticGetDBFieldInfo('abcschema.abc');
*/
function elasticGetDBFieldInfo($tablename,$params=array()){
	$recs=elasticQueryResults("SHOW COLUMNS in {$tablename}");
	return $recs;
}

//---------- begin function elasticQueryResults ----------
/**
* @describe returns the records of a query
* @param $params array - These can also be set in the CONFIG file with dbname_elastic,dbuser_elastic, and dbpass_elastic
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* 	[-filename] - if you pass in a filename then it will write the results to the csv filename you passed in
* @return $recs array
* @usage $recs=elasticQueryResults('select top 50 * from abcschema.abc');
*/
function elasticQueryResults($query,$params=array()){
	global $DATABASE;
	global $CONFIG;
	$DATABASE['_lastquery']=array(
		'start'=>microtime(true),
		'stop'=>0,
		'time'=>0,
		'error'=>'',
		'query'=>$query,
		'function'=>'elasticQueryResults'
	);
	$db=$DATABASE[$CONFIG['db']];
	$url=$db['dbhost'];
	if(!stringBeginsWith($url,'http')){
		$url="https://{$url}";
	}
	$payload=array(
		'query'=>$query,
		'fetch_size'=>5000
	);
	$json=encodeJSON($payload);
	$post=postJSON($url,$json,array(
		'-authuser'=>$db['dbuser'],
		'-authpass'=>$db['dbpass'],
		'-port'=>$db['dbport'],
		'-nossl'=>1,
		'format'=>'json'
	));
	echo printValue($post);exit;
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
			$postopts['cursorId']=$nextcursorid;
			if(isset($params['-logfile'])){
		    	appendFileContents($params['-logfile'],time().", CURSORID SET,  POSTING: LOOP {$loop}".PHP_EOL);
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
			if(isset($params['-logfile'])){
		    	appendFileContents($params['-logfile'],time().", no resultCount:".PHP_EOL);
		    }
			break;
		}
		if(!isset($post['json_array']['results'][0])){
			if(isset($params['-logfile'])){
		    	appendFileContents($params['-logfile'],time().", results are empty:".PHP_EOL);
		    }
			break;
		}
		if(!isset($post['json_array']['statusCode']) || $post['json_array']['statusCode'] != 200){
			if(isset($params['-logfile'])){
		    	appendFileContents($params['-logfile'],time().", statusCode not 200:".PHP_EOL.decodeJson($post['json_array']).PHP_EOL);
		    }
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
			$post['json_array']['results']=elasticLowerKeys($post['json_array']['results']);
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
				if(is_array($custom_filters) && count($custom_filters)){
					$matches=0;
					foreach($custom_filters as $filter){
						$field=$filter['field'];
						if(!isset($rec[$field])){continue;}
						switch(strtolower($filter['oper'])){
							case 'like':
								if(stringContains($rec[$field],$filter['val'])){$matches+=1;}
							break;
							case 'not like':
								if(!stringContains($rec[$field],$filter['val'])){$matches+=1;}
							break;
						}
					}
					if(count($custom_filters)==$matches){
						$recs[]=$rec;
						$recs_count+=1;
					}
				}
				else{
					$recs[]=$rec;
					$recs_count+=1;
				}
				if($limit > 0 and $recs_count >= $limit){
					break;
				}
			}
			//echo $action.printValue($delete_url);exit;
			if($action=='delete' && strlen($delete_url)){
				foreach($recs as $r=>$rec){
					if(!isset($rec['uid'])){continue;}
					$json=array(
						'apiKey'=>$db['dbkey'],
						'userKey'=>$db['dbuser'],
						'secret'=>$db['dbpass'],
						'UID'=>$rec['uid'],
						'format'=>'json',
						'httpStatusCodes'=>true,
						'-json'=>1,
						'-method'=>'POST'
					);
					//$json=encodeJSON($json);
					$dparams=$params;
					$dparams['UID']=$rec['uid'];
					$dpost=postURL($delete_url,$json);
					if(isset($dpost['json_array']['statusReason'])){
						$recs[$r]['delete_status']=$dpost['json_array']['statusReason'];
					}
					else{
						$recs[$r]['delete_status']='failed';
					}
					if($recs[$r]['delete_status']=='Forbidden'){
						echo "DEBUG".printValue($dpost['json_array']);exit;
					}
					if(isset($dpost['json_array']['statusCode'])){
						$recs[$r]['delete_code']=$dpost['json_array']['statusCode'];
					}
					else{
						$recs[$r]['delete_code']='400';
					}
					//sleep for a 1/4 second to eliminate rate limit issues
					usleep(250);
					//echo printValue($dpost);exit;
				}
			}
			if(count($recs) && isset($params['-filename'])){
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

function elasticRandomUserAgent(){
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

function elasticLowerKeys($arr, $case = CASE_LOWER)
{
    return array_map(function($item) use($case) {
        if(is_array($item)){
            $item = elasticLowerKeys($item, $case);
        }
        return $item;
    },array_change_key_case($arr, $case));
}

//---------- begin function elasticGetDBRecords
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
*	elasticGetDBRecords(array('-table'=>'notes'));
*	elasticGetDBRecords("select * from myschema.mytable where ...");
*/
function elasticGetDBRecords($params){
	global $USER;
	global $CONFIG;
	//echo "elasticGetDBRecords".printValue($params);exit;
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
		$fields=elasticGetDBFieldInfo($params['-table']);
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
	$recs=elasticQueryResults($query,$params);
	//echo '<hr>'.$query.printValue($params).printValue($recs);
	return $recs;
}
//---------- begin function elasticGetDBRecordsCount ----------
/**
* @describe retrieves records from DB based on params
* @param $params array
* 	-table 	  - table to query
* 	[-dbname] - name of ODBC connection
* 	[-dbuser] - username
* 	[-dbpass] - password
* @return array recordsets
* @usage $cnt=elasticGetDBRecordsCount(array('-table'=>'tesl));
*/
function elasticGetDBRecordsCount($params=array()){
	$params['-fields']='count(*) cnt';
	if(isset($params['-order'])){unset($params['-order']);}
	if(isset($params['-limit'])){unset($params['-limit']);}
	if(isset($params['-offset'])){unset($params['-offset']);}
	$recs=elasticGetDBRecords($params);
	return $recs[0]['cnt'];
}
function elasticNamedQueryList(){
	return array();
}
//---------- begin function elasticNamedQuery ----------
/**
* @describe returns pre-build queries based on name
* @param name string
*	[running_queries]
*	[table_locks]
* @return query string
*/
function elasticNamedQuery($name){
	switch(strtolower($name)){
		case 'tables':
			return <<<ENDOFQUERY
ENDOFQUERY;
		break;
	}
}
