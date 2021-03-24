<?php
loadExtras('translate');
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
			list($ok,$query)=dbDropIndex($sync['target']['name'],$name,$sync['table']);
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
			if($rec['is_unique']==1){
				$params['-unique']=true;
			}
			if($rec['is_fulltext']==1){
				$params['-fulltext']=true;
			}
			$_SESSION['debugValue_lastm']='';
			list($ok,$query)=dbAddIndex($sync['target']['name'],$params);
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
	//echo printValue($sync);exit;
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
		$ok=dbAlterTable($sync['target']['name'],$sync['table'],$fields);
		$rtn['result']=printValue($ok);	
	}
	return $rtn;
}
function dbsyncCompareFunctionsAndProcedures($source,$target,$diffs=0){
	$procedures=array(
		'source'=>dbGetAllProcedures($source),
		'target'=>dbGetAllProcedures($target),
	);
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
			$cols=array();
			$cols[]='<button type="button" class="btn button" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="'.$recs[$key]['diff'].'" data-func="view_procedure" data-name="'.$recs[$key]['object_name'].'" data-type="'.$recs[$key]['object_type'].'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';
			$recs[$key]['status']='';
			switch(strtolower($recs[$key]['diff'])){
				case 'new':
					$recs[$key]['status'].='<div class="align-left w_gray"><span class="icon-plus" style="margin-right:5px;"></span><translate>New</translate></div>';
				break;
				case 'missing':
					$recs[$key]['status'].='<div class="align-left w_gray"><span class="icon-warning" style="margin-right:5px;"></span><translate>Missing in source</translate></div>';
				break;
				case 'args':
					$recs[$key]['status'].='<div class="align-left w_gray"><span class="icon-gear" style="margin-right:5px;"></span><translate>Arguements are different</translate></div>';
				break;
				case 'content':
					$recs[$key]['status'].='<div class="align-left w_gray"><span class="icon-file-txt" style="margin-right:5px;"></span><translate>Content is different</translate></div>';
				break;
				case 'same':
					$recs[$key]['status'].='<div class="align-left w_gray"><span class="icon-mark w_success" style="margin-right:5px;"></span><translate>Same</translate></div>';
				break;
			}
			
			if(count($cols)==1){
				//echo $key.printValue($recs[$key]);exit;
				$recs[$key]['status'].=$cols[0];
			}
			else{
				$recs[$key]['status'].='<div style="display:flex;flex-direction:row;flex-wrap:no-wrap; align-items:flex-end;justify-content:space-between;">';
				$recs[$key]['status'].='<div>'.array_shift($cols).'</div>';
				$recs[$key]['status'].='<div style="margin-left:10px;">'.implode(' ',$cols).'</div>';
				$recs[$key]['status'].='</div>';
			}
		}
	}
	
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
		'-tableclass'=>'table bordered striped is-sticky',
		'-hidesearch'=>1
	);

	return databaseListRecords($listopts);
}
function dbsyncCompareTablesAndIndexes($source,$target,$diffs=0){
	$tableindexes=array(
		'source'=>dbGetAllTableIndexes($source),
		'target'=>dbGetAllTableIndexes($target),
	);
	//remove any indexes that are auto-generated
	foreach($tableindexes['source'] as $name=>$indexes){
		foreach($indexes as $i=>$index){
			if(in_array($index['generated'],array('Y',1))){
				unset($tableindexes['source'][$name][$i]);
			}
		}
	}
	foreach($tableindexes['target'] as $name=>$indexes){
		foreach($indexes as $i=>$index){
			if(in_array($index['generated'],array('Y',1))){
				unset($tableindexes['target'][$name][$i]);
			}
		}
	}
	$tablefields=array(
		'source'=>dbGetAllTableFields($source),
		'target'=>dbGetAllTableFields($target),
	);
	if(!count($tablefields['source'])){
		return "Failed to get source tables from [{$source}]";
	}
	elseif(!count($tablefields['target'])){
		return "Failed to get target tables from [{$target}]";
	}
	//constraints
	$tableconstraints=array(
		'source'=>dbGetAllTableConstraints($source),
		'target'=>dbGetAllTableConstraints($target),
	);
	if(!count($tableconstraints['source'])){
		return "Failed to get source table constraints from [{$source}]";
	}
	elseif(!count($tableconstraints['target'])){
		return "Failed to get target table constraints from [{$target}]";
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
			'fields'=>$tablefields['source'][$table],
			'indexes'=>$tableindexes['source'][$table],
			'constraints'=>$tableconstraints['source'][$table]
		);
		$recs[$table]['target']=array(
			'name'=>$target,
			'fields'=>$tablefields['target'][$table],
			'indexes'=>$tableindexes['target'][$table],
			'constraints'=>$tableconstraints['target'][$table]
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
	}
	if($diffs==1){
		foreach($recs as $table=>$rec){	
			$diff=0;
			if($recs[$table]['schema']!='same'){$diff+=1;} 
			if(!in_array($recs[$table]['indexes'],array('same','none'))){$diff+=1;}
			if(!in_array($recs[$table]['constraints'],array('same','none'))){
				$diff+=1;
			}
			if($diff==0){
				unset($recs[$table]);
			}
		}
	}
	//echo printValue($recs);exit;
	$_SESSION['dbsync']=$recs;
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
				$cols[]='<button type="button" class="btn button" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="same" data-func="view_fields" data-table="'.$table.'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';
			break;
			case 'new':
				$fieldcount=count($tablefields['source'][$table]);
				$lines[]='<span class="icon-block w_danger"></span> Table ONLY exists in Source DB ('.$fieldcount.' fields)';
				$cols[]=implode('<br />',$lines);
				//push to target
				$cols[]='<button type="button" class="btn button" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="new" data-func="view_fields" data-table="'.$table.'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';
				
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
				$cols[]='<button type="button" class="btn button" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="different" data-func="view_fields" data-table="'.$table.'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';
			break;
			case 'missing':
				$fieldcount=count($tablefields['target'][$table]);
				$lines[]='<span class="icon-warning w_danger"></span> Table ONLY exists in Target DB ('.$fieldcount.' fields)';
				$cols[]=implode('<br />',$lines);
				//pull from target
				$cols[]='<button type="button" class="btn button" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="missing" data-func="view_fields" data-table="'.$table.'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';
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
				$cols[]='<button type="button" class="btn button" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="same" data-func="view_indexes" data-table="'.$table.'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';
				
			break;
			case 'new':
				$fieldcount=count($tableindexes['source'][$table]);
				$lines[]='<span class="icon-block w_danger"></span> Indexes ONLY exists in Source DB ('.$fieldcount.' fields)';
				$cols[]=implode('<br />',$lines);
				//push to target
				$cols[]='<button type="button" class="btn button" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="new" data-func="view_indexes" data-table="'.$table.'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';			
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
				$cols[]='<button type="button" class="btn button" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="different" data-func="view_indexes" data-table="'.$table.'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';
			break;
			case 'missing':
				$fieldcount=count($tableindexes['target'][$table]);
				$lines[]='<span class="icon-warning w_danger"></span> Indexes ONLY exists in Target DB ('.$fieldcount.' fields)';
				$cols[]=implode('<br />',$lines);
				//pull from target
				$cols[]='<button type="button" class="btn button" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="missing" data-func="view_indexes" data-table="'.$table.'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';
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
				$cols[]='<button type="button" class="btn button" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="same" data-func="view_constraints" data-table="'.$table.'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';
				
			break;
			case 'new':
				$fieldcount=count($tableconstraints['source'][$table]);
				$lines[]='<span class="icon-block w_danger"></span> constraints ONLY exists in Source DB ('.$fieldcount.' fields)';
				$cols[]=implode('<br />',$lines);
				//push to target
				$cols[]='<button type="button" class="btn button" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="new" data-func="view_constraints" data-table="'.$table.'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';			
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
				$cols[]='<button type="button" class="btn button" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="different" data-func="view_constraints" data-table="'.$table.'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';
			break;
			case 'missing':
				$fieldcount=count($tableconstraints['target'][$table]);
				$lines[]='<span class="icon-warning w_danger"></span> constraints ONLY exists in Target DB ('.$fieldcount.' fields)';
				$cols[]=implode('<br />',$lines);
				//pull from target
				$cols[]='<button type="button" class="btn button" onclick="dbsyncFunc(this);"  data-div="centerpop" data-status="missing" data-func="view_constraints" data-table="'.$table.'" data-source="'.$source.'" data-target="'.$target.'"><span class="icon-eye"></span> View</button>';
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
		//'-pretable'=>'<hr size="1" style="margin:0px;" />',
		'-tableclass'=>'table bordered striped is-sticky',
		'-hidesearch'=>1
	);

	return databaseListRecords($listopts);
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
		return 'Error';
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
		$blank[$field]='<div class="align-center"><span class="icon-block w_smaller w_danger"></span></';
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
	$rtn='<div style="max-height:70vh;overflow:auto;"><table>';
	$rtn.='<thead><tr><th>Source</th><th>Target</th></tr></thead>';
	$rtn.='<tbody><tr><td style="padding-right:10px;">'.dbsyncShowDifferentList($source).'</td><td style="padding-left:10px;">'.dbsyncShowDifferentList($target).'</th></tr></tbody>';
	$rtn.='</table></';
	return $rtn;
}
function dbsyncShowDifferentList($recs){
	global $dbsyncShowDifferentListCenter;
	$listopts=array(
		'-list'=>$recs,
		'-tableclass'=>'table condensed striped bordered',
		'-hidesearch'=>1,
		'is_unique_checkmark'=>1
	);
	if($dbsyncShowDifferentListCenter != 1){
		$dbsyncShowDifferentListCenter=1;
		$listopts['-posttable']=buildOnLoad("centerObject('wacss_modal');");
	}
	return databaseListRecords($listopts);
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
