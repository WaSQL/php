<?php
loadExtras('translate');

//Input validation functions
function dbsyncValidateDatabaseName($name){
	//Validate database name format (alphanumeric, underscore, hyphen)
	if(!isset($name) || !strlen($name)){
		return false;
	}
	//Only allow alphanumeric, underscore, and hyphen
	if(!preg_match('/^[a-zA-Z0-9\_\-]+$/',$name)){
		return false;
	}
	return true;
}

function dbsyncValidateTableName($table){
	//Only allow alphanumeric, underscore, hyphen, and dot (for schema.table)
	if(!preg_match('/^[a-zA-Z0-9\_\-\.]+$/',$table)){
		return false;
	}
	return true;
}

function dbsyncValidateProcedureName($name){
	//Only allow alphanumeric, underscore, and hyphen
	if(!preg_match('/^[a-zA-Z0-9\_\-]+$/',$name)){
		return false;
	}
	return true;
}

function dbsyncValidateProcedureType($type){
	//Only allow specific procedure types
	$allowed=array('function','procedure','trigger','view','package');
	return in_array(strtolower($type),$allowed);
}

function dbsyncValidateViewName($view){
	//Only the tab names actually reachable via the user-controlled 'tab' request param.
	//Other views (view_diff, view_sync, ddl, ...) are only ever selected internally via setView() and never come from user input here.
	$allowed=array('compare','compare_tables_indexes','compare_functions_procedures');
	return in_array(strtolower($view),$allowed);
}

/**
 * @describe Returns the lowercased dbtype configured in config.xml for a database name (mysql is the
 *           implicit default, matching dbFunctionCall()'s own switch default in database.php).
 * @param string $dbname database name as configured in config.xml
 * @return string lowercased dbtype, e.g. 'mysql','postgresql','oracle'
 * @usage $type=dbsyncEngineType('mydb');
 */
function dbsyncEngineType($dbname){
	global $DATABASE;
	if(isset($DATABASE[$dbname]['dbtype']) && strlen($DATABASE[$dbname]['dbtype'])){
		return strtolower($DATABASE[$dbname]['dbtype']);
	}
	return 'mysql';
}
/**
 * @describe True only when both databases run an engine that implements getAllTableConstraints()/getProcedureText()/
 *           getAllProcedures() (currently oracle and postgresql only - see php/extras/databases/*.php). Calling these
 *           functions for an unsupported engine hits WaSQL's "function does not exist" path, which calls debugValue()
 *           and - in debug/staging mode - echoes directly into the output buffer, tripping evalPHP's
 *           "return value and echo value both found" check. Always check this BEFORE calling those functions.
 * @param string $source source database name
 * @param string $target target database name
 * @return bool
 * @usage if(dbsyncEngineSupportsConstraintsAndProcedures($source,$target)){ ... }
 */
function dbsyncEngineSupportsConstraintsAndProcedures($source,$target){
	$supported=array('oracle','postgresql','postgres');
	return in_array(dbsyncEngineType($source),$supported) && in_array(dbsyncEngineType($target),$supported);
}
/**
 * @describe Calls a function while discarding any output it (or something it calls, e.g. a core debugValue()
 *           notice) echoes directly - these data-fetch functions should never legitimately produce output, and
 *           a stray echo landing in the same buffer evalPHP() inspects can trip its "return value and echo
 *           value both found" check when the caller is a <?=...?> short-echo tag. dbsync already surfaces
 *           failures through each function's own return value / $_SESSION['debugValue_lastm'], so the raw
 *           echoed notice is redundant noise here, not lost information.
 * @param callable $func function name to call
 * @param array $args positional arguments to pass
 * @return mixed whatever $func returns
 * @usage $recs=dbsyncSuppressedCall('dbGetAllTableConstraints',array($source));
 */
function dbsyncSuppressedCall($func,$args){
	ob_start();
	$result=call_user_func_array($func,$args);
	ob_end_clean();
	return $result;
}
/**
 * @describe Unpacks the return value of dbAddIndex()/dbDropIndex() into [ok,query]. Both functions return
 *           either an error string, or an array shaped ['query'=>...,'result'=>...] on success - never a
 *           plain [ok,query] tuple, so list()-destructuring the raw return silently yields nulls.
 * @param mixed $result the raw return value of dbAddIndex()/dbDropIndex()
 * @return array [$ok,$query]
 * @usage list($ok,$query)=dbsyncUnpackIndexResult(dbAddIndex($db,$params));
 */
function dbsyncUnpackIndexResult($result){
	if(is_array($result)){
		return array(isset($result['result'])?$result['result']:$result,isset($result['query'])?$result['query']:'');
	}
	return array($result,'');
}
function dbsyncSyncIndexes($sync){
	$recs=array();
	//source
	$source=array();
	foreach($sync['source']['indexes'] as $rec){
		$key=strtolower($rec['index_name']);
		$source[$key]=$rec;
	}
	//target
	$target=array();
	foreach($sync['target']['indexes'] as $rec){
		$key=strtolower($rec['index_name']);
		$target[$key]=$rec;
	}
	//adds
	$adds=array();
	foreach($source as $key=>$rec){
		if(!isset($target[$key])){
			$adds[$key]=$rec;
		}
	}
	//drops
	$drops=array();
	foreach($target as $key=>$rec){
		if(!isset($source[$key])){
			$drops[$key]=$rec;
		}
	}
	//echo "Adds".printValue($adds)."Drops".printValue($drops);exit;
	if(count($drops)){
		foreach($drops as $name=>$rec){
			$_SESSION['debugValue_lastm']='';
			list($ok,$query)=dbsyncUnpackIndexResult(dbDropIndex($sync['target']['name'],$name,$sync['table']));
			if(strlen($_SESSION['debugValue_lastm'])){
				$ok="<pre><xmp>{$_SESSION['debugValue_lastm']}</xmp></pre>";
			}
			$recs[]=array(
				'action'=>"Drop index {$name}",
				'query'=>$query,
				'params'=>'',
				'result'=>printValue($ok)
			);
		}
	}
	if(count($adds)){
		foreach($adds as $name=>$rec){
			$params=array(
				'-table'=>$sync['table'],
				'-name'=>$name,
				'-fields'=>json_decode($rec['index_keys'],true)
			);
			if(isset($rec['is_unique']) && $rec['is_unique']==1){
				$params['-unique']=true;
			}
			if(isset($rec['is_fulltext']) && $rec['is_fulltext']==1){
				$params['-fulltext']=true;
			}
			$_SESSION['debugValue_lastm']='';
			list($ok,$query)=dbsyncUnpackIndexResult(dbAddIndex($sync['target']['name'],$params));
			if(strlen($_SESSION['debugValue_lastm'])){
				$ok="<pre><xmp>{$_SESSION['debugValue_lastm']}</xmp></pre>";
			}
			$recs[]=array(
				'action'=>"Add index {$name} ",
				'params'=>nl2br(json_encode($params,JSON_PRETTY_PRINT)),
				'query'=>$query,
				'result'=>printValue($ok)
			);
		}
	}
	
	return $recs;
}
function dbsyncSyncFields($sync){
	$rtn=array();
	if($sync['schema']=='new'){
		$ddl=dbGetTableDDL($sync['source']['name'],$sync['table']);
		$ddl=trim($ddl);
		//echo $ddl;exit;
		if(!stringBeginsWith($ddl,'create')){
			return $ddl;
		}
		$rtn['query']="<pre><xmp>{$ddl}</xmp></pre>";
		$_SESSION['debugValue_lastm']='';
		$rtn['result']=dbExecuteSQL($sync['target']['name'],$ddl);
		if(strlen($_SESSION['debugValue_lastm'])){
			$rtn['result']="<pre><xmp>{$_SESSION['debugValue_lastm']}</xmp></pre>";
		}
		//echo printValue($rtn);exit;
	}
	elseif($sync['schema']=='different'){
		$fields=array();
		foreach($sync['source']['fields'] as $rec){
			$fields[$rec['field_name']]=$rec['type_name'];
		}
		$rtn['fields']=nl2br(json_encode($fields,JSON_PRETTY_PRINT));
		$_SESSION['debugValue_lastm']='';
		$ok=dbAlterTable($sync['target']['name'],$sync['table'],$fields);
		if(strlen($_SESSION['debugValue_lastm'])){
			$rtn['result']="<pre><xmp>{$_SESSION['debugValue_lastm']}</xmp></pre>";
		}
		else{
			$rtn['result']=printValue($ok);
		}
	}
	return $rtn;
}
function dbsyncCompareFunctionsAndProcedures($source,$target,$diffs=0){
	if(!dbsyncEngineSupportsConstraintsAndProcedures($source,$target)){
		return '<div class="w_bold w_danger">Comparing functions/procedures isn\'t supported for one of these database engines (requires Oracle or PostgreSQL).</div>';
	}
	$procedures=array(
		'source'=>dbsyncSuppressedCall('dbGetAllProcedures',array($source)),
		'target'=>dbsyncSuppressedCall('dbGetAllProcedures',array($target)),
	);
	if(!is_array($procedures['source']) || !is_array($procedures['target'])){
		return '<div class="w_bold w_danger">Failed to retrieve functions/procedures from one of these databases.</div>';
	}
	if(!count($procedures['source'])){
		return "Failed to get source functions from [{$source}]";
	}
	elseif(!count($procedures['target'])){
		return "Failed to get target functions from [{$target}]";
	}
	$recs=array();
	foreach($procedures['source'] as $procs){
		foreach($procs as $proc){
			$key=$proc['object_name'].$proc['object_type'].$proc['overload'];
			$recs[$key]=$proc;
			//schema
			if(!isset($procedures['target'][$key])){
				$recs[$key]['diff']='new';
			}
		}	
	}
	foreach($procedures['target'] as $procs){
		foreach($procs as $proc){
			$key=$proc['object_name'].$proc['object_type'].$proc['overload'];
			if(!isset($recs[$key])){
				$recs[$key]=array(
					'object_name'=>$proc['object_name'],
					'object_type'=>$proc['object_type'],
					'overload'=>$proc['overload'],
					'args'=>$proc['args'],
					'diff'=>'missing',
				);
			}
			elseif(sha1($recs[$key]['args']) != sha1($proc['args'])){
				$proc['diff']='args';
				$recs[$key]=$proc;
			}
			elseif($recs[$key]['hash'] != $proc['hash']){
				$proc['diff']='content';
				$recs[$key]=$proc;
			}
			else{
				$proc['diff']='same';
				$recs[$key]=$proc;
			}
		}
	}
	foreach($recs as $key=>$rec){
		$recs[$key]['status']='';
		$cols=array();
		$cols[]='<button type="button" class="wacss_button is-mobile-responsive" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="'.$recs[$key]['diff'].'" data-func="view_procedure" data-name="'.$recs[$key]['object_name'].'" data-type="'.$recs[$key]['object_type'].'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';
		switch(strtolower($recs[$key]['diff'])){
			case 'new':
				$recs[$key]['status'].='<div class="align-left w_gray"><span class="icon-plus" style="margin-right:5px;"></span><translate>New</translate></div>';
			break;
			case 'missing':
				$recs[$key]['status'].='<div class="align-left w_gray"><span class="icon-warning" style="margin-right:5px;"></span><translate>Missing in source</translate></div>';
			break;
			case 'args':
				$recs[$key]['status'].='<div class="align-left w_gray"><span class="icon-gear" style="margin-right:5px;"></span><translate>Arguments are different</translate></div>';
			break;
			case 'content':
				$recs[$key]['status'].='<div class="align-left w_gray"><span class="icon-file-txt" style="margin-right:5px;"></span><translate>Content is different</translate></div>';
			break;
			case 'same':
				$recs[$key]['status'].='<div class="align-left w_gray"><span class="icon-mark w_success" style="margin-right:5px;"></span><translate>Same</translate></div>';
			break;
		}
		
		if(count($cols)==1){
			//echo "Key:{$key}  ".printValue($recs[$key]);exit;
			$recs[$key]['status'].=$cols[0];
		}
		else{
			$recs[$key]['status'].='<div style="display:flex;flex-direction:row;flex-wrap:no-wrap; align-items:flex-end;justify-content:space-between;">';
			$recs[$key]['status'].='<div>'.array_shift($cols).'</div>';
			$recs[$key]['status'].='<div style="margin-left:10px;">'.implode(' ',$cols).'</div>';
			$recs[$key]['status'].='</div>';
		}
	}
	
	$counts=array('new'=>0,'missing'=>0,'args'=>0,'content'=>0,'same'=>0);
	foreach($recs as $rec){
		if(isset($counts[$rec['diff']])){
			$counts[$rec['diff']]++;
		}
	}
	$summary='<div class="dbsync-summary">'.count($recs).' object'.(count($recs)==1?'':'s')
		.' &middot; '.$counts['new'].' new'
		.' &middot; '.$counts['missing'].' missing'
		.' &middot; '.$counts['args'].' args different'
		.' &middot; '.$counts['content'].' content different'
		.' &middot; '.$counts['same'].' same</div>';

	if($diffs==1){
		foreach($recs as $key=>$rec){
			if($recs[$key]['diff']=='same'){
				unset($recs[$key]);
			}
		}
	}
	//return $diffs.printValue($recs);
	$xrecs=array();
	foreach($recs as $rec){$xrecs[]=$rec;}
	$listopts=array(
		'-list'=>$xrecs,
		'-listfields'=>'object_name,object_type,overload,status',
		'-tableclass'=>'wacss_table bordered striped is-sticky',
		'-hidesearch'=>1
	);

	return $summary.dbsyncSuppressedCall('databaseListRecords',array($listopts));
}
function dbsyncCompareTablesAndIndexes($source,$target,$diffs=0){
	$tableindexes=array(
		'source'=>dbsyncSuppressedCall('dbGetAllTableIndexes',array($source)),
		'target'=>dbsyncSuppressedCall('dbGetAllTableIndexes',array($target)),
	);
	if(!is_array($tableindexes['source'])){$tableindexes['source']=array();}
	if(!is_array($tableindexes['target'])){$tableindexes['target']=array();}
	//remove any indexes that are auto-generated (not every engine's index record includes a 'generated' column - mysql's doesn't)
	foreach($tableindexes['source'] as $name=>$indexes){
		foreach($indexes as $i=>$index){
			if(isset($index['generated']) && in_array($index['generated'],array('Y',1))){
				unset($tableindexes['source'][$name][$i]);
			}
		}
	}
	foreach($tableindexes['target'] as $name=>$indexes){
		foreach($indexes as $i=>$index){
			if(isset($index['generated']) && in_array($index['generated'],array('Y',1))){
				unset($tableindexes['target'][$name][$i]);
			}
		}
	}
	$tablefields=array(
		'source'=>dbsyncSuppressedCall('dbGetAllTableFields',array($source)),
		'target'=>dbsyncSuppressedCall('dbGetAllTableFields',array($target)),
	);
	if(!is_array($tablefields['source']) || !count($tablefields['source'])){
		return "Failed to get source tables from [{$source}]";
	}
	elseif(!is_array($tablefields['target']) || !count($tablefields['target'])){
		return "Failed to get target tables from [{$target}]";
	}
	//constraints - not every database engine has an implementation (currently only oracle/postgresql); check
	//engine support up front so we never call the missing function (which would echo a core debug notice).
	$constraintsSupported=dbsyncEngineSupportsConstraintsAndProcedures($source,$target);
	if($constraintsSupported){
		$tableconstraints=array(
			'source'=>dbsyncSuppressedCall('dbGetAllTableConstraints',array($source)),
			'target'=>dbsyncSuppressedCall('dbGetAllTableConstraints',array($target)),
		);
		$constraintsSupported=is_array($tableconstraints['source']) && is_array($tableconstraints['target']);
	}
	if(!$constraintsSupported){
		$tableconstraints=array('source'=>array(),'target'=>array());
	}

	$recs=array();
	foreach($tablefields['source'] as $table=>$fields){
		$recs[$table]=array(
			'table'=>$table,
			'schema'=>'',
			'indexes'=>'',
			'constraints'=>''
		);
		//schema
		if(!isset($tablefields['target'][$table])){
			$recs[$table]['schema']='new';
		}
		else{

		}
	}
	foreach($tablefields['target'] as $table=>$fields){
		if(!isset($tablefields['source'][$table])){
			$recs[$table]=array(
				'table'=>$table,
				'schema'=>'missing',
				'indexes'=>'',
				'constraints'=>''
			);
		}
	}
	//check schema fields for each table that is not new or missing
	foreach($recs as $table=>$rec){
		$recs[$table]['source']=array(
			'name'=>$source,
			'fields'=>isset($tablefields['source'][$table])?$tablefields['source'][$table]:array(),
			'indexes'=>isset($tableindexes['source'][$table])?$tableindexes['source'][$table]:array(),
			'constraints'=>isset($tableconstraints['source'][$table])?$tableconstraints['source'][$table]:array()
		);
		$recs[$table]['target']=array(
			'name'=>$target,
			'fields'=>isset($tablefields['target'][$table])?$tablefields['target'][$table]:array(),
			'indexes'=>isset($tableindexes['target'][$table])?$tableindexes['target'][$table]:array(),
			'constraints'=>isset($tableconstraints['target'][$table])?$tableconstraints['target'][$table]:array()
		);
		if(strlen($rec['schema'])){continue;}
		//check for field differences
		if(count($tablefields['target'][$table]) != count($tablefields['source'][$table])){
			$recs[$table]['schema']='different';
		}
		elseif(sha1(json_encode($tablefields['target'][$table])) != sha1(json_encode($tablefields['source'][$table]))){
			$recs[$table]['schema']='different';
		}
		if(!strlen($recs[$table]['schema'])){
			$recs[$table]['schema']='same';
		}
		//check indexes
		if(isset($tableindexes['source'][$table])){
			if(!isset($tableindexes['target'][$table])){
				$recs[$table]['indexes']='new';
			}
			elseif(sha1(json_encode($tableindexes['target'][$table])) != sha1(json_encode($tableindexes['source'][$table]))){
				$recs[$table]['indexes']='different';
			}
			else{
				$recs[$table]['indexes']='same';
			}
		}
		elseif(isset($tableindexes['target'][$table])){
			$recs[$table]['indexes']='missing';
		}
		else{
			$recs[$table]['indexes']='none';
		}
		//check constraints
		if(isset($tableconstraints['source'][$table])){
			if(!isset($tableconstraints['target'][$table])){
				$recs[$table]['constraints']='new';
			}
			elseif(sha1(json_encode($tableconstraints['target'][$table])) != sha1(json_encode($tableconstraints['source'][$table]))){
				$recs[$table]['constraints']='different';
			}
			else{
				$recs[$table]['constraints']='same';
			}
		}
		elseif(isset($tableconstraints['target'][$table])){
			$recs[$table]['constraints']='missing';
		}
		else{
			$recs[$table]['constraints']='none';
		}
		if(!$constraintsSupported){
			$recs[$table]['constraints']='unsupported';
		}
	}
	if($diffs==1){
		foreach($recs as $table=>$rec){	
			$diff=0;
			if($recs[$table]['schema']!='same'){$diff+=1;} 
			if(!in_array($recs[$table]['indexes'],array('same','none'))){$diff+=1;}
			if(!in_array($recs[$table]['constraints'],array('same','none','unsupported'))){
				$diff+=1;
			}
			if($diff==0){
				unset($recs[$table]);
			}
		}
	}
	//echo printValue($recs);exit;
	$_SESSION['dbsync']=$recs;
	//summary counts, computed before the raw status strings below get overwritten with display HTML
	$schemaCounts=array('new'=>0,'missing'=>0,'different'=>0,'same'=>0);
	foreach($recs as $rec){
		if(isset($schemaCounts[$rec['schema']])){
			$schemaCounts[$rec['schema']]++;
		}
	}
	$summary='<div class="dbsync-summary">'.count($recs).' table'.(count($recs)==1?'':'s')
		.' &middot; '.$schemaCounts['new'].' new'
		.' &middot; '.$schemaCounts['missing'].' missing'
		.' &middot; '.$schemaCounts['different'].' different'
		.' &middot; '.$schemaCounts['same'].' same</div>';
	//now to pretty up the messages
	foreach($recs as $table=>$rec){
		//schema
		$lines=array();
		$cols=array();
		switch(strtolower($recs[$table]['schema'])){
			case 'same':
				$fieldcount=count($tablefields['source'][$table]);
				$lines[]='<span class="icon-mark w_success"></span> Table exists in Both';
				$lines[]='<span class="icon-mark w_success"></span> Same '.$fieldcount.' Fields in Both';
				$cols[]=implode('<br />',$lines);
				//view
				$cols[]='<button type="button" class="wacss_button is-mobile-responsive" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="same" data-func="view_fields" data-table="'.$table.'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';
			break;
			case 'new':
				$fieldcount=count($tablefields['source'][$table]);
				$lines[]='<span class="icon-block w_danger"></span> Table ONLY exists in Source DB ('.$fieldcount.' fields)';
				$cols[]=implode('<br />',$lines);
				//push to target
				$cols[]='<button type="button" class="wacss_button is-mobile-responsive" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="new" data-func="view_fields" data-table="'.$table.'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';
				
			break;
			case 'different':
				$sfieldcount=count($tablefields['source'][$table]);
				$tfieldcount=count($tablefields['target'][$table]);
				$lines[]='<span class="icon-mark w_success"></span> Table exists in Both';
				if($sfieldcount != $tfieldcount){
					$msg=" Source has {$sfieldcount} fields, Target has {$tfieldcount} fields";
				}
				else{
					$msg=" Same number of fields({$sfieldcount}) in both but they are different";
				}
				$lines[]='<span class="icon-block w_danger"></span> '.$msg;
				$cols[]=implode('<br />',$lines);
				//push to target
				$cols[]='<button type="button" class="wacss_button is-mobile-responsive" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="different" data-func="view_fields" data-table="'.$table.'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';
			break;
			case 'missing':
				$fieldcount=count($tablefields['target'][$table]);
				$lines[]='<span class="icon-warning w_danger"></span> Table ONLY exists in Target DB ('.$fieldcount.' fields)';
				$cols[]=implode('<br />',$lines);
				//pull from target
				$cols[]='<button type="button" class="wacss_button is-mobile-responsive" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="missing" data-func="view_fields" data-table="'.$table.'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';
			break;
		}
		$recs[$table]['schema']='';
		if(count($cols)==1){
			$recs[$table]['schema']=$cols[0];
		}
		else{
			$recs[$table]['schema']='<div style="display:flex;flex-direction:row;flex-wrap:no-wrap; align-items:flex-end;justify-content:space-between;">';
			$recs[$table]['schema'].='<div>'.array_shift($cols).'</div>';
			$recs[$table]['schema'].='<div style="margin-left:10px;">'.implode(' ',$cols).'</div>';
			$recs[$table]['schema'].='</div>';
		}
		//indexes
		$lines=array();
		$cols=array();
		switch(strtolower($recs[$table]['indexes'])){
			case 'same':
				$fieldcount=count($tableindexes['source'][$table]);
				$lines[]='<span class="icon-mark w_success"></span> Indexes exists in Both';
				$lines[]='<span class="icon-mark w_success"></span> Same '.$fieldcount.' indexes in Both';
				$cols[]=implode('<br />',$lines);
				//view
				$cols[]='<button type="button" class="wacss_button is-mobile-responsive" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="same" data-func="view_indexes" data-table="'.$table.'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';
				
			break;
			case 'new':
				$fieldcount=count($tableindexes['source'][$table]);
				$lines[]='<span class="icon-block w_danger"></span> Indexes ONLY exists in Source DB ('.$fieldcount.' fields)';
				$cols[]=implode('<br />',$lines);
				//push to target
				$cols[]='<button type="button" class="wacss_button is-mobile-responsive" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="new" data-func="view_indexes" data-table="'.$table.'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';			
			break;
			case 'different':
				$sfieldcount=count($tableindexes['source'][$table]);
				$tfieldcount=count($tableindexes['target'][$table]);
				$lines[]='<span class="icon-mark w_success"></span> Indexes exists in Both';
				if($sfieldcount != $tfieldcount){
					$msg=" Source has {$sfieldcount} indexes, Target has {$tfieldcount} indexes";
				}
				else{
					$msg=" Same number of indexes({$sfieldcount}) in both but they are different";
				}
				$lines[]='<span class="icon-block w_danger"></span> '.$msg;
				$cols[]=implode('<br />',$lines);
				//push to target
				$cols[]='<button type="button" class="wacss_button is-mobile-responsive" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="different" data-func="view_indexes" data-table="'.$table.'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';
			break;
			case 'missing':
				$fieldcount=count($tableindexes['target'][$table]);
				$lines[]='<span class="icon-warning w_danger"></span> Indexes ONLY exists in Target DB ('.$fieldcount.' fields)';
				$cols[]=implode('<br />',$lines);
				//pull from target
				$cols[]='<button type="button" class="wacss_button is-mobile-responsive" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="missing" data-func="view_indexes" data-table="'.$table.'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';
			break;
		}
		$recs[$table]['indexes']='';
		if(count($cols)==1){
			$recs[$table]['indexes']=$cols[0];
		}
		else{
			$recs[$table]['indexes']='<div style="display:flex;flex-direction:row;flex-wrap:no-wrap; align-items:flex-end;justify-content:space-between;">';
			$recs[$table]['indexes'].='<div>'.array_shift($cols).'</div>';
			$recs[$table]['indexes'].='<div style="margin-left:10px;">'.implode(' ',$cols).'</div>';
			$recs[$table]['indexes'].='</div>';
		}
		//constraints
		$lines=array();
		$cols=array();
		switch(strtolower($recs[$table]['constraints'])){
			case 'same':
				$fieldcount=count($tableconstraints['source'][$table]);
				$lines[]='<span class="icon-mark w_success"></span> constraints exists in Both';
				$lines[]='<span class="icon-mark w_success"></span> Same '.$fieldcount.' constraints in Both';
				$cols[]=implode('<br />',$lines);
				//view
				$cols[]='<button type="button" class="wacss_button is-mobile-responsive" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="same" data-func="view_constraints" data-table="'.$table.'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';
				
			break;
			case 'new':
				$fieldcount=count($tableconstraints['source'][$table]);
				$lines[]='<span class="icon-block w_danger"></span> constraints ONLY exists in Source DB ('.$fieldcount.' fields)';
				$cols[]=implode('<br />',$lines);
				//push to target
				$cols[]='<button type="button" class="wacss_button is-mobile-responsive" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="new" data-func="view_constraints" data-table="'.$table.'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';			
			break;
			case 'different':
				$sfieldcount=count($tableconstraints['source'][$table]);
				$tfieldcount=count($tableconstraints['target'][$table]);
				$lines[]='<span class="icon-mark w_success"></span> constraints exists in Both';
				if($sfieldcount != $tfieldcount){
					$msg=" Source has {$sfieldcount} constraints, Target has {$tfieldcount} constraints";
				}
				else{
					$msg=" Same number of constraints({$sfieldcount}) in both but they are different";
				}
				$lines[]='<span class="icon-block w_danger"></span> '.$msg;
				$cols[]=implode('<br />',$lines);
				//push to target
				$cols[]='<button type="button" class="wacss_button is-mobile-responsive" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="different" data-func="view_constraints" data-table="'.$table.'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';
			break;
			case 'missing':
				$fieldcount=count($tableconstraints['target'][$table]);
				$lines[]='<span class="icon-warning w_danger"></span> constraints ONLY exists in Target DB ('.$fieldcount.' fields)';
				$cols[]=implode('<br />',$lines);
				//pull from target
				$cols[]='<button type="button" class="wacss_button is-mobile-responsive" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="missing" data-func="view_constraints" data-table="'.$table.'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';
			break;
			case 'unsupported':
				$cols[]='<span class="icon-minus w_gray"></span> <span class="w_gray">Not supported by this database engine</span>';
			break;
		}
		$recs[$table]['constraints']='';
		if(count($cols)==1){
			$recs[$table]['constraints']=$cols[0];
		}
		else{
			$recs[$table]['constraints']='<div style="display:flex;flex-direction:row;flex-wrap:no-wrap; align-items:flex-end;justify-content:space-between;">';
			$recs[$table]['constraints'].='<div>'.array_shift($cols).'</div>';
			$recs[$table]['constraints'].='<div style="margin-left:10px;">'.implode(' ',$cols).'</div>';
			$recs[$table]['constraints'].='</div>';
		}
	}
	$xrecs=array();
	foreach($recs as $rec){$xrecs[]=$rec;}
	$listopts=array(
		'-list'=>$xrecs,
		'-listfields'=>'table,schema,indexes,constraints',
		'-tableclass'=>'wacss_table bordered striped is-sticky',
		'-hidesearch'=>1
	);

	return $summary.dbsyncSuppressedCall('databaseListRecords',array($listopts));
}
function dbsyncDiff($srecs,$trecs){
	if(!is_array($srecs)){$srecs=array();}
	if(!is_array($trecs)){$trecs=array();}
	$diffs=array();
	if(is_array($srecs)){
		foreach($srecs as $srec){
			unset($srec['table_name']);
			$key=sha1(json_encode($srec));
			$diffs[$key]['source']=$srec;
		}
	}
	if(is_array($trecs)){
		foreach($trecs as $trec){
			unset($trec['table_name']);
			$key=sha1(json_encode($trec));
			$diffs[$key]['target']=$trec;
		}
	}
	if(!count($diffs)){
		return '<div class="w_gray">No records to compare.</div>';
	}
	foreach($diffs as $key=>$diff){
		if(isset($diff['source'])){
			$fields=array_keys($diff['source']);
		}
		else{
			$fields=array_keys($diff['target']);
		}
		break;
	}
	//echo printValue($diffs).printValue($srecs).printValue($trecs);exit;
	$blank=array();
	foreach($fields as $field){
		$blank[$field]='<div class="align-center"><span class="icon-block w_smaller w_danger"></span></div>';
	}
	$recs=array();
	foreach($diffs as $key=>$diff){
		if(isset($diff['source']) && isset($diff['target'])){
			//same
			$recs['source'][]=$diff['source'];
			$recs['target'][]=$diff['target'];
		}
		elseif(isset($diff['source'])){
			//missing target
			$recs['source'][]=$diff['source'];
			$recs['target'][]=$blank;
		}
		elseif(isset($diff['target'])){
			//missing source
			$recs['source'][]=$blank;
			$recs['target'][]=$diff['target'];
		}
	}
	//echo printValue($blank).printValue($recs);exit;
	return dbsyncShowDifferent($recs['source'],$recs['target']);
}

function dbsyncShowDifferent($source,$target){
	$rtn='<div style="max-height:70vh;overflow:auto;"><table class="wacss_table">';
	$rtn.='<thead><tr><th>Source</th><th>Target</th></tr></thead>';
	$rtn.='<tbody><tr><td style="padding-right:10px;">'.dbsyncShowDifferentList($source).'</td><td style="padding-left:10px;">'.dbsyncShowDifferentList($target).'</td></tr></tbody>';
	$rtn.='</table></div>';
	return $rtn;
}
function dbsyncShowDifferentList($recs){
	global $dbsyncShowDifferentListCenter;
	$listopts=array(
		'-list'=>$recs,
		'-tableclass'=>'wacss_table condensed striped bordered',
		'-hidesearch'=>1,
		'is_unique_checkmark'=>1
	);
	if($dbsyncShowDifferentListCenter != 1){
		$dbsyncShowDifferentListCenter=1;
		$listopts['-posttable']=buildOnLoad("centerObject('wacss_modal');");
	}
	return dbsyncSuppressedCall('databaseListRecords',array($listopts));
}
function dbsyncFormField($field){
	global $DATABASE;
	$params=array();
	switch(strtolower($field)){
		case 'source':
		case 'target':
			return buildFormSelectDatabase($field);
		break;
	}
}
?>
