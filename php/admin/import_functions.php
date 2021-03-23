<?php
function importProcessXML($params){
	$items=exportFile2Array($params['file_abspath']);
	unlink($params['file_abspath']);
	return importXmlData($items,$params);
}
function importProcessCSV($params){
	global $results;
	global $fieldinfo;
	global $importrecs_total;
	if(!strlen($params['csvtable'])){
		return "Select a table for CSV import";
	}
	if($params['csvtable']=='Create NEW Table'){
	 	if(!strlen($params['csvtable_name'])){
			return "Choose a table name if you want to create a new table";
		}
		if(isDBTable($params['csvtable_name'])){
			return "Table '{$params['csvtable_name']}' already exists";
		}
	}
	if($params['csvtable']=='Create NEW Table'){
		$fields=getCSVSchema($params['file_abspath']);
        //create the table
        $params['csvtable']=$_REQUEST['csvtable']=$params['csvtable_name'];
        $ok = createDBTable($params['csvtable'],$fields);
		if(!isNum($ok)){
			return array("Create Table Error: ",$ok,$fields);
		}
	}
	$stime=microtime(true);
	$fieldinfo=getDBFieldInfo($params['csvtable'],1);
	$ok=processCSVLines($params['file_abspath'],'importProcessCSVLine');
	$ok=importProcessCSVRecs();
	$importrecs_total=number_format($importrecs_total,0);
	$etime=round((microtime(true)-$stime),4);
	array_unshift($results,count($results)." import calls as follows:");
	array_unshift($results,"Total imported time: {$etime} seconds");
	array_unshift($results,"Total imported record count: {$importrecs_total}");
	array_unshift($results,"Tablename: {$params['csvtable']}");
    return $results;
}
function importProcessCSVLine($line){
	global $importrecs;
	global $results;
	
	//make sure this is not a blank row
	$vcnt=0;
	foreach($line['line'] as $k=>$v){
		if(strlen($v)){
			$vcnt+=1;
			break;
		}
	}
	if($vcnt==0){return;}
	$importrecs[]=$line['line'];
	if(count($importrecs) >= 1000){
		$ok=importProcessCSVRecs();
	}
}
function importProcessCSVRecs($recs=array()){
	global $importrecs;
	global $importrecs_total;
	global $results;
	global $fieldinfo;
	$stime=microtime(true);
	//insert into table
	$importrecs_count=count($importrecs);
	if($importrecs_count==0){return;}
	$importrecs_total+=$importrecs_count;
	$table=$_REQUEST['csvtable'];
	$fields=array();
	foreach($importrecs as $i=>$rec){
		if(isset($fieldinfo['_cdate']) && !isset($rec['_cdate'])){
			$importrecs[$i]['_cdate']=$rec['_cdate']=date('Y-m-d H:i:s');
		}
		if(isset($fieldinfo['_cuser']) && !isset($rec['_cuser'])){
			$importrecs[$i]['_cuser']=$rec['_cuser']=0;
		}
		foreach($rec as $k=>$v){
			if(!isset($fieldinfo[$k])){continue;}
			if(!in_array($k,$fields)){$fields[]=$k;}
		}
	}
	$fieldstr=implode(',',$fields);
	$query="INSERT INTO {$table} ({$fieldstr}) VALUES ".PHP_EOL;
	$values=array();
	foreach($importrecs as $i=>$rec){
		foreach($rec as $k=>$v){
			if(!in_array($k,$fields)){
				unset($rec[$k]);
				continue;
			}
			if(!strlen($v)){
				$rec[$k]='NULL';
			}
			else{
				switch($fieldinfo[$k]['_dbtype']){
					case 'datetime':
						$v=date('Y-m-d H:i:s',strtotime($v));
					break;
					case 'date':
						$v=date('Y-m-d',strtotime($v));
					break;
					case 'time':
						$v=date('H:i:s',strtotime($v));
					break;
				}
				$v=databaseEscapeString($v);
				$rec[$k]="'{$v}'";
			}
		}
		$values[]='('.implode(',',array_values($rec)).')';
	}
	$query.=implode(','.PHP_EOL,$values);
	//echo $query;exit;
	$ok=executeSQL($query);
	$importrecs=array();
	$etime=round((microtime(true)-$stime),4);
	$results[]="imported {$importrecs_count} records in {$etime} seconds ";
}
function importProcessAPPS($params){
	
}
function importBuildFormField($name){
	switch(strtolower($name)){
		case 'file';
			$params=array(
				'accept'=>'.xml,.csv',
				'path'=>'wasql_temp_path',
				'autonumber'=>1,
				'acceptmsg'=>'Only valid xml or csv files are allowed',
				'required'=>1
			);
			return buildFormFile('file',$params);
		break;
		case 'xmltypes':
			$opts=array('xmlschema'=>'Schema','xmlmeta'=>'Meta','xmldata'=>'Data');
			$params=array('width'=>3);
			return buildFormCheckbox('xmltypes',$opts,$params);
		break;
		case 'xmloptions':
			$opts=array('drop'=>'Drop Existing Tables with same names','truncate'=>'Truncate Existing Records','ids'=>'Import IDs');
			$params=array('width'=>1);
			return buildFormCheckbox('xmloptions',$opts,$params);
		break;
		case 'xmlmerge':
			$params=array('height'=>100);
			return buildFormTextarea('xmlmerge',$params);
		break;
		case 'csvtable':
			$tables=getDBTables();
			array_unshift($tables,'Create NEW Table');
			$opts=array();
			foreach($tables as $table){
				$opts[$table]=$table;
			}
			$params=array(
				'onchange' => "if(this.value=='Create NEW Table'){showId('newtable');hideId('picktable');}else{hideId('newtable');showId('picktable');}",
				'message'=>'-- Table to Import Into --'
			);
			return buildFormSelect('csvtable',$opts,$params);
		break;
		case 'csvtable_name':
			$params=array('placeholder'=>'new table name');
			return buildFormText('csvtable_name',$params);
		break;
	}
}
?>
