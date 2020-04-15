<?php
function importProcessXML($params){
	$items=exportFile2Array($params['file_abspath']);
	unlink($params['file_abspath']);
	return importXmlData($items,$params);
}
function importProcessCSV($params){
	if(!strlen($params['csvtable'])){
		return "Select a table for CSV import";
	}
	if($params['csvtable']=='Create NEW Table'){
	 	if(!strlen($params['csvtable_name'])){
			return "Choose a table name if you want to create a new table";
		}
		if(isDBTable($params['csvtable_name'])){
			return "Table '{$params['csvtable_name']}'' already exists";
		}
	}
	$lines = getCSVFileContents($params['file_abspath']);
	if($params['csvtable']=='Create NEW Table'){
		$fields=array(
			'_id'	=> databasePrimaryKeyFieldString(),
			'_cdate'=> databaseDataType('datetime').databaseDateTimeNow(),
			'_cuser'=> "int NOT NULL",
			'_edate'=> databaseDataType('datetime')." NULL",
			'_euser'=> "int NULL",
			);
		$fieldtype=array();
		$maxlen=array();
		foreach($lines['items'] as $item){
			foreach($item as $key=>$val){
				//maxlength
				if(!isset($maxlen[$key]) || strlen($val) > $maxlen[$key]){
					$maxlen[$key]=strlen($val);
				}
				//type
				if(isNum($val)){
					if(!isset($fieldtype[$key])){$fieldtype[$key]='int';}
                }
                elseif(isDateTime($val)){
					if(!isset($fieldtype[$key])){$fieldtype[$key]='datetime';}
                }
                elseif(isDate($val)){
					if(!isset($fieldtype[$key])){$fieldtype[$key]='date';}
                }
                else{
					$fieldtype[$key]='varchar';
                }
            }
        }
        foreach($fieldtype as $key=>$type){
			$fld=preg_replace('/[^a-z0-9]+/i','_',$key);
			switch($type){
				case 'int':
					if($maxlen[$key] > 11){
						$fields[$fld]="bigint NULL";	
					}
					else{
						$fields[$fld]="int NULL";
					}
				break;
				case 'datetime':
					$fields[$fld]=databaseDataType('datetime')." NULL";
				break;
				case 'date':
					$fields[$fld]="date NULL";
				break;
				case 'varchar':
					$max=$maxlen[$key];
					//round max up to nearest 5
					$max=(round($max)%5 === 0) ? round($max) : round(($max+5/2)/5)*5;
					if($max > 2000){$fields[$fld]="text NULL";}
					else{$fields[$fld]="varchar({$max}) NULL";}	
				break;
            }
        }
        //create the table
        $params['csvtable']=$params['csvtable_name'];
        $ok = createDBTable($params['csvtable'],$fields);
		if(!isNum($ok)){
			return array("Create Table Error: ",$ok);
		}
	}
	//populate the table
	$info=getDBFieldInfo($params['csvtable'],1);
	$results=array();
	$importcnt=0;
	foreach($lines['items'] as $item){
		$row++;
		$opts=array();
		foreach($item as $key=>$val){
			if(!isset($info[$key])){continue;}
			if(!strlen($val)){continue;}
			$opts[$key]=$val;
        	}
        if(count($opts) > 0){
			$opts['-table']=$params['csvtable'];
			$id=addDBRecord($opts);
			if(isNum($id)){
				$importcnt+=1;
			}
			else{
				$results[]="addDBRecord Error on row {$row}";
				$results[]=$id;
				$results[]="Opts";
				$results[]=$opts;
				$results[]="Maxlen";
				$results[]=$maxlen;
				$results[]="Fields";
				$results[]=$fields;
				return $results;
            }
        }
    }
    $results[]="Import Record Count: {$importcnt}";
    return $results;
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
