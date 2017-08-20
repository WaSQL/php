<?php
function datasyncGetTarget(){
	$rec=getDBRecord(array(
		'-table'=>'_settings',
		'user_id'=>0,
		'key_name'=>'wasql_synchronize_slave',
		'-fields'=>'_id,key_name,key_value'
	));
	return $rec['key_value'];
}
function datasyncGetAuth($user,$pass){
	//build the load
	$load=array(
		'func'		=> 'auth',
		'username'	=> $user,
		'password'	=> $pass,
		'_login'	=> 1,
		'_pwe'		=> 1
	);
	return datasyncPost($load,1);
}
function datasyncGetSourceTables(){
	global $CONFIG;
	//tables and record counts
	$query=<<<ENDOFQUERY
	SELECT
		table_name tablename
		,table_rows records
  	FROM information_schema.tables
	WHERE
		table_schema='{$CONFIG['dbname']}'
	ORDER BY table_name
ENDOFQUERY;
	$recs=getDBRecords(array('-query'=>$query,'-index'=>'tablename'));
	foreach($recs as $table=>$rec){
		$frecs=getDBFieldInfo($table);
		foreach($frecs as $field=>$frec){
			$recs[$table]['fields'][$field]=$frec['_dbtype_ex'];
		}
	}
	return $recs;
}
function datasyncGetTargetTables(){
	//build the load
	$load=array(
		'func'		=> 'get_tables',
	);
	return datasyncPost($load,0);
}
function datasyncFromTarget($table){
	//copy all records here to Target in chunks of
	$limit=500;
	$offset=0;
	//set limit to 100 for tables with large data values
	$fields=getDBFieldInfo($table);
	foreach($fields as $field=>$info){
		switch(strtolower($info['_dbtype'])){
			case 'binary':
			case 'blob':
			case 'text':
			case 'mediumtext':
			case 'largetext':
				$limit=100;
			break;
		}
	}
	//truncate the table
	$ok=truncateDBTable($table);
	$cnt=0;
	while(1){
		$load=array(
			'func'		=> 'get_records',
			'table'		=> $table,
			'limit'		=> $limit,
			'offset'	=> $offset
		);
		$recs=datasyncPost($load,0);
		foreach($recs as $rec){
			$opts=array();
			foreach($rec as $k=>$v){
				if(!strlen($v)){continue;}
				$opts[$k]=$v;
			}
			$opts['-table']=$table;
			$id=addDBRecord($opts);
			if(!isNum($id)){return printValue(array($id,$opts));}
			$cnt++;
		}
		if(!is_array($recs) || !count($recs)){break;}
		$offset+=$limit;
	}
	return "{$cnt} records from target to here";
}
function datasyncToTarget($table){
	//copy all records here to Target in chunks of
	$limit=500;
	$offset=0;
	//set limit to 100 for tables with large data values
	$fields=getDBFieldInfo($table);
	foreach($fields as $field=>$info){
		switch(strtolower($info['_dbtype'])){
			case 'binary':
			case 'blob':
			case 'text':
			case 'mediumtext':
			case 'largetext':
				$limit=100;
			break;
		}
	}
	$results=array();
	$cnt=0;
	while(1){
		$recs=getDBRecords(array('-table'=>$table,'-limit'=>$limit,'-offset'=>$offset,'-order'=>'_id'));
		if(!is_array($recs)){break;}
		$load=array(
			'func'		=> 'datasync_records',
			'table'		=> $table,
			'records'	=> $recs,
			'offset'	=> $offset
		);
		$results=datasyncPost($load,0);
		if(!isset($results['count'])){return printValue($results);}
		$offset+=$limit;
		$cnt+=$results['count'];
	}
	return "{$cnt} records from here to target";
}
function datasyncPost($load,$plain=0){
	global $USER;
	if(!isset($_SESSION['sync_target_url'])){
		global $ALLCONFIG;
		$target=$_SESSION['sync_target'];
		if(!isset($ALLCONFIG[$target])){
			return json_encode(array('error'=>'invalid target'));
		}
		if(isset($ALLCONFIG[$target]['admin_secure']) && $ALLCONFIG[$target]['admin_secure']==1){
			$_SESSION['sync_target_url']="https://{$ALLCONFIG[$target]['name']}/php/admin.php";
		}
		else{
			$_SESSION['sync_target_url']="http://{$ALLCONFIG[$target]['name']}/php/admin.php";
		}
	}
	if($plain==1){
		$postopts=$load;
		$postopts['_menu']='datasync';
		$postopts['load']=base64_encode(json_encode($load));
	}
	else{
		$postopts=array(
			'_menu'		=> 'datasync',
			'load'		=> base64_encode(json_encode($load)),
			'_auth'		=> $_SESSION['sync_target_auth']
		);
	}
	$postopts['-follow']=1;
	$postopts['-nossl']=1;
	$postopts['_noguid']=1;
	$post=postURL($_SESSION['sync_target_url'],$postopts);
	if(isset($post['error'])){
		return array('error'=>$post['error']);
	}
	elseif(!strlen($post['body'])){
		return array('error'=>printValue($post));
	}
	else{
		$json=json_decode(base64_decode($post['body']),true);
		if(!is_array($json)){
			$json=json_decode($post['body'],true);
			if(!is_array($json)){
				return array('error'=>$post['body']);
			}
		}
		return $json;
	}
	return array('error'=>json_encode($post));
}

?>
