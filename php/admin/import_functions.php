<?php
//Input validation functions
function importValidateDatabaseName($name){
	//Only allow alphanumeric, underscore, and hyphen
	if(!preg_match('/^[a-zA-Z0-9\_\-]+$/',$name)){
		return false;
	}
	return true;
}

function importValidateTableName($name){
	//Allow schema.table format with alphanumeric, underscore, hyphen, and dot
	if(!preg_match('/^[a-zA-Z0-9\_\-\.]+$/',$name)){
		return false;
	}
	return true;
}

function importValidateFieldName($name){
	//Only allow alphanumeric, underscore, and comma (for lists)
	if(!preg_match('/^[a-zA-Z0-9\_\,]+$/',$name)){
		return false;
	}
	return true;
}

function importValidateFilePath($filepath){
	//Ensure file is in temp directory
	$tempPath=getWasqlPath('/php/temp');
	$realFilePath=realpath($filepath);
	$realTempPath=realpath($tempPath);

	if(!$realFilePath || !$realTempPath){
		return false;
	}

	//Check if file is within temp directory
	if(strpos($realFilePath,$realTempPath)!==0){
		return false;
	}

	return true;
}

function importProcessXML($params){
	//Validate file path before processing
	if(!isset($params['file_abspath']) || !importValidateFilePath($params['file_abspath'])){
		return "Invalid file path";
	}
	if(!file_exists($params['file_abspath'])){
		return "File does not exist";
	}

	$items=exportFile2Array($params['file_abspath']);

	//Safely delete the uploaded file
	if(file_exists($params['file_abspath']) && importValidateFilePath($params['file_abspath'])){
		unlink($params['file_abspath']);
	}

	return importXmlData($items,$params);
}
function importProcessCSV($params){
	global $results;
	global $fieldinfo;
	global $importrecs_total;

	//Validate file path
	if(!isset($params['file_abspath']) || !importValidateFilePath($params['file_abspath'])){
		return "Invalid file path";
	}
	if(!is_file($params['file_abspath'])){
		return "Select a csv file to upload. File does not exist.";
	}

	//Validate database name
	if(!isset($params['csvtable_db']) || !strlen($params['csvtable_db'])){
		return "Select a database for CSV import";
	}
	if(!importValidateDatabaseName($params['csvtable_db'])){
		return "Invalid database name. Only alphanumeric characters, underscores, and hyphens are allowed.";
	}

	//Validate table name
	if(!isset($params['csvtable_name']) || !strlen($params['csvtable_name'])){
		return "Select a table name for CSV import";
	}
	if(!importValidateTableName($params['csvtable_name'])){
		return "Invalid table name. Only alphanumeric characters, underscores, hyphens, and dots are allowed.";
	}
	if(!dbIsTable($params['csvtable_db'],$params['csvtable_name'])){
		$fields=getCSVSchema($params['file_abspath']);
		//check for upserton and make that field unique
		if(isset($params['csvtable_upserton']) && strlen($params['csvtable_upserton'])){
			//Validate field name
			if(!importValidateFieldName($params['csvtable_upserton'])){
				return "Invalid upserton field name. Only alphanumeric characters and underscores are allowed.";
			}
			if(isset($fields[$params['csvtable_upserton']])){
				$fields[$params['csvtable_upserton']].= ' NOT NULL UNIQUE';
			}
		}
        //create the table
        $ok = dbCreateTable($params['csvtable_db'],$params['csvtable_name'],$fields);
		if(!isNum($ok)){
			return array("Create Table Error: ",$ok,$fields);
		}
	}
	$cparams=array();
	$cparams['-csv']=$params['file_abspath'];

	//upsert - validate field names
	if(isset($params['csvtable_upsert']) && strlen(trim($params['csvtable_upsert']))){
		if(!importValidateFieldName($params['csvtable_upsert'])){
			return "Invalid upsert field name(s). Only alphanumeric characters, underscores, and commas are allowed.";
		}
		$cparams['-upsert']=$params['csvtable_upsert'];
	}

	//upserton - validate field name
	if(isset($params['csvtable_upserton']) && strlen(trim($params['csvtable_upserton']))){
		if(!importValidateFieldName($params['csvtable_upserton'])){
			return "Invalid upserton field name. Only alphanumeric characters and underscores are allowed.";
		}
		$cparams['-upserton']=$params['csvtable_upserton'];
	}

	//upsertwhere - passed through to dbAddRecords which should handle SQL safely
	if(isset($params['csvtable_where']) && strlen(trim($params['csvtable_where']))){
		$cparams['-upsertwhere']=$params['csvtable_where'];
	}

	//chunk - validate as integer
	if(isset($params['csvtable_chunk']) && strlen(trim($params['csvtable_chunk']))){
		$chunk=(integer)$params['csvtable_chunk'];
		if($chunk > 0){
			$cparams['-chunk']=$chunk;
		}
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
