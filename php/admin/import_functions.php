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
	if(!isset($params['file_abspath']) || !is_file($params['file_abspath'])){
		return "Select a csv file to upload. {$params['file_abspath']} does not exist.";
	}
	if(!strlen($params['csvtable_db'])){
		return "Select a database for CSV import";
	}
	if(!strlen($params['csvtable_name'])){
		return "Select a table name for CSV import";
	}
	if(!dbIsTable($params['csvtable_db'],$params['csvtable_name'])){
		$fields=getCSVSchema($params['file_abspath']);
		//check for upserton and make that field unique
		if(isset($params['csvtable_upserton']) && isset($fields[$params['csvtable_upserton']])){
			$fields[$params['csvtable_upserton']].= ' NOT NULL UNIQUE';
		}
        //create the table
        $ok = dbCreateTable($params['csvtable_db'],$params['csvtable_name'],$fields);
		if(!isNum($ok)){
			return array("Create Table Error: ",$ok,$fields);
		}
	}
	$cparams=array();
	$cparams['-csv']=$params['file_abspath'];
	//upsert?
	if(isset($_REQUEST['csvtable_upsert']) && strlen(trim($_REQUEST['csvtable_upsert']))){
		$cparams['-upsert']=$_REQUEST['csvtable_upsert'];
	}
	//upserton?
	if(isset($_REQUEST['csvtable_upserton']) && strlen(trim($_REQUEST['csvtable_upserton']))){
		$cparams['-upserton']=$_REQUEST['csvtable_upserton'];
	}
	//upsertwhere?
	if(isset($_REQUEST['csvtable_where']) && strlen(trim($_REQUEST['csvtable_where']))){
		$cparams['-upsertwhere']=$_REQUEST['csvtable_where'];
	}
	//chunk?
	if(isset($_REQUEST['csvtable_chunk']) && strlen(trim($_REQUEST['csvtable_chunk']))){
		$cparams['-chunk']=$_REQUEST['csvtable_chunk'];
	}
	//import
	$stime=microtime(true);
	//echo $params['csvtable_name'].printValue($cparams);exit;
	$importrecs_total=dbAddRecords($params['csvtable_db'],$params['csvtable_name'],$cparams);
	$etime=round((microtime(true)-$stime),4);
	array_unshift($results,"Total imported time: {$etime} seconds");
	array_unshift($results,"Total imported record count: {$importrecs_total}");
	array_unshift($results,"Database: {$params['csvtable_db']}");
	array_unshift($results,"Tablename: {$params['csvtable_name']}");
    return $results;
}
function importProcessAPPS($params){
	
}
function importBuildFormField($name){
	global $CONFIG;
	switch(strtolower($name)){
		case 'file';
			$params=array(
				'accept'=>'.xml,.csv',
				'path'=>'wasql_temp_path',
				'text'=>'CSV or XML file to import',
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
			$params=array('class'=>'wacss_textarea is-mobile-responsive','height'=>100);
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
				'class'=>'wacss_select is-mobile-responsive',
				'required'=>'required',
				'onchange' => "if(this.value=='Create NEW Table'){showId('newtable');hideId('picktable');}else{hideId('newtable');showId('picktable');}",
				'message'=>'-- Table to Import Into --'
			);
			return buildFormSelect('csvtable',$opts,$params);
		break;
		case 'csvtable_db':
			$params=array('class'=>'wacss_select is-mobile-responsive','value'=>isset($_REQUEST['csvtable_db'])?$_REQUEST['csvtable_db']:$CONFIG['database']);
			return buildFormSelectDatabase('csvtable_db',$params);
		break;
		case 'csvtable_name':
			$params=array('class'=>'wacss_input is-mobile-responsive','placeholder'=>'[schema_name.]table_name');
			return buildFormText('csvtable_name',$params);
		break;
		case 'csvtable_chunk':
			$params=array('class'=>'wacss_input is-mobile-responsive','value'=>isset($_REQUEST['csvtable_chunk'])?$_REQUEST['csvtable_chunk']:1000);
			return buildFormText('csvtable_chunk',$params);
		break;
		case 'csvtable_upsert':
			$params=array('class'=>'wacss_input is-mobile-responsive',
				'placeholder'=>'field1,field2,...','value'=>isset($_REQUEST['csvtable_upsert'])?$_REQUEST['csvtable_upsert']:'');
			return buildFormText('csvtable_upsert',$params);
		break;
		case 'csvtable_upserton':
			$params=array('class'=>'wacss_input is-mobile-responsive','placeholder'=>'pkey','value'=>isset($_REQUEST['csvtable_upserton'])?$_REQUEST['csvtable_upserton']:'');
			return buildFormText('csvtable_upserton',$params);
		break;
		case 'csvtable_where':
			$params=array('class'=>'wacss_textarea is-mobile-responsive','placeholder'=>'where clause','value'=>isset($_REQUEST['csvtable_where'])?$_REQUEST['csvtable_where']:'');
			return buildFormTextarea('csvtable_where',$params);
		break;
	}
}
?>
