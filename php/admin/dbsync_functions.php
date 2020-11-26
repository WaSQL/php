<?php
loadExtras('translate');
function dbsyncCompare($source,$target,$diffs=0){
	$tableindexes=array(
		'source'=>dbGetAllTableIndexes($source),
		'target'=>dbGetAllTableIndexes($target),
	);
	$tablefields=array(
		'source'=>dbGetAllTableFields($source),
		'target'=>dbGetAllTableFields($target),
	);
	if(!count($tablefields['source'])){
		return "Failed to get source tables from [{$source}]";
	}
	elseif(!count($tablefields['target'])){
		return "Failed to get target tables from [{$targer}]";
	}
	
	$recs=array();
	foreach($tablefields['source'] as $table=>$fields){
		$recs[$table]=array(
			'table'=>$table,
			'schema'=>'',
			'indexes'=>'',
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
			);
		}
	}
	//check schema fields for each table that is not new or missing
	foreach($recs as $table=>$rec){
		$recs[$table]['source']=array(
			'fields'=>$tablefields['source'][$table],
			'indexes'=>$tableindexes['source'][$table]
		);
		$recs[$table]['target']=array(
			'fields'=>$tablefields['target'][$table],
			'indexes'=>$tableindexes['target'][$table]
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
	}
	if($diffs==1){
		foreach($recs as $table=>$rec){	
			if($recs[$table]['schema']=='same' && $recs[$table]['indexes']=='same'){
				unset($recs[$table]);
			}
		}
	}
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
	}
	$xrecs=array();
	foreach($recs as $rec){$xrecs[]=$rec;}
	$listopts=array(
		'-list'=>$xrecs,
		'-listfields'=>'table,schema,indexes',
		//'-pretable'=>'<hr size="1" style="margin:0px;" />',
		'-tableclass'=>'table bordered striped is-sticky',
		'-tableheight'=>'80vh',
		'-hidesearch'=>1
	);

	return databaseListRecords($listopts);
}
function dbsyncDiff($srecs,$trecs){
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
			$opts=array();
			//group by dbtype
			$dbtypes=array();
			foreach($DATABASE as $dbkey=>$db){
				$dbtypes[$db['dbtype']][]=$db;
			}
			ksort($dbtypes);
			$tag='<select required="required" name="'.$field.'" class="select">'.PHP_EOL;
			$tag.='	<option value="">-- '.ucfirst($field).' --</option>'.PHP_EOL;
			foreach($dbtypes as $dbtype=>$dbs){
				$tag.='	<optgroup label="'.ucfirst($dbtype).'">'.PHP_EOL;
				foreach($dbs as $db){
					$dval=$db['displayname'];
					if(strlen($db['dbschema'])){
						$dval.= " ({$db['dbschema']})";
					}
					$tval=$db['name'];
					$tag.='		<option value="'.$tval.'">'.$dval.'</option>'.PHP_EOL;
				}
				$tag.='	</optgroup>';
			}
			$tag.='</select>';
			return $tag;
		break;
	}
}
?>
