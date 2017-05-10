<?php
function pageGetTables(){
	$tables=getDBTables();
	foreach($tables as $i=>$table){
		if(isWasqlTable($table)){unset($tables[$i]);}
	}
	return $tables;
}
function pageGetPages(){
	return getDBRecords(array(
		'-table'=>'_pages',
		'-fields'=>'_id,name',
		'-order'=>'name'
	));
}
function pageGetTemplates(){
	return getDBRecords(array(
		'-table'=>'_templates',
		'-fields'=>'_id,name',
		'-order'=>'name'
	));
}
function pageBuildExport($filename,$schema=array(),$meta=array(),$data=array(),$pages=array(),$templates=array()){
	if(!preg_match('/\.xml$/i',$filename)){$filename.='.xml';}
	global $CONFIG;
	$xmldata=xmlHeader();
	$xmldata.='<export dbname="'.$CONFIG['dbname'].'" timestamp="'.time().'">'."".PHP_EOL;
	$tables=pageGetTables();
	foreach($tables as $table){
		if(in_array($table,$schema)){
			//export Schema
			$fields=getDBSchema(array($table));
			$xmldata .= '<xmlschema name="'.$table.'">'.PHP_EOL;
			$fields=sortArrayByKey($fields,'field');
			foreach($fields as $field){
				$type=$field['type'];
				if($field['null']=='NO'){$type .= ' NOT NULL';}
				else{$type .= ' NULL';}
				if($field['key']=='PRI'){$type .= ' Primary Key';}
				elseif($field['key']=='UNI'){$type .= ' UNIQUE';}
				if(strlen($field['default'])){$type .= ' Default '.$field['default'];}
				if(strlen($field['extra'])){$type .= ' '.$field['extra'];}
				$type=xmlEncodeCDATA($type);
				$xmldata .= '	<field name="'.$field['field'].'" type="'.$type.'" />'.PHP_EOL;
                }
            $xmldata .= '</xmlschema>'.PHP_EOL;
            }
        if(in_array($table,$meta)){
			//export Meta data from _tabledata and _fielddata tables
			$mtables=array('_tabledata','_fielddata');
			foreach($mtables as $mtable){
				$recs=getDBRecords(array('-table'=>$mtable,'tablename'=>$table));
				if(is_array($recs)){
					$fields=getDBFields($mtable,1);
					foreach($recs as $rec){
						$xmldata .= '<xmlmeta name="'.$mtable.'">'.PHP_EOL;
						foreach($fields as $field){
							if(!strlen($rec[$field])){continue;}
							if($isapp && stringBeginsWith($field,'_')){continue;}
							$xmldata .= "	<{$field}>".xmlEncodeCDATA($rec[$field])."</{$field}>".PHP_EOL;
	                        }
						$xmldata .= '</xmlmeta>'.PHP_EOL;
	                    }
					}
				}
            }
        if(in_array($table,$data)){
			//export table record
			$recs=getDBRecords(array('-table'=>$table,'-order'=>'_id'));
			if(is_array($recs)){
				$fields=getDBFields($table,1);
				foreach($recs as $rec){
					$xmldata .= '<xmldata name="'.$table.'">'.PHP_EOL;
					foreach($fields as $field){
						if(!strlen($rec[$field])){continue;}
						if($isapp && stringBeginsWith($field,'_')){continue;}
						$xmldata .= "	<{$field}>".xmlEncodeCDATA($rec[$field])."</{$field}>".PHP_EOL;
		                }
					$xmldata .= '</xmldata>'.PHP_EOL;
		            }
				}
	        }
        $xmldata .= PHP_EOL.PHP_EOL;
        }
    //pages?
    $fields=getDBFields('_pages',1);
    foreach($pages as $id){
		$rec=getDBRecord(array('-table'=>'_pages','_id'=>$id));
		$xmldata .= '<xmldata name="_pages">'.PHP_EOL;
		foreach($fields as $field){
			if(!strlen($rec[$field])){continue;}
			if($isapp && stringBeginsWith($field,'_')){continue;}
			$xmldata .= "	<{$field}>".xmlEncodeCDATA($rec[$field])."</{$field}>".PHP_EOL;
		}
		$xmldata .= '</xmldata>'.PHP_EOL;
	}
	//templates?
    $fields=getDBFields('_templates',1);
    foreach($pages as $id){
		$rec=getDBRecord(array('-table'=>'_templates','_id'=>$id));
		$xmldata .= '<xmldata name="_templates">'.PHP_EOL;
		foreach($fields as $field){
			if(!strlen($rec[$field])){continue;}
			if($isapp && stringBeginsWith($field,'_')){continue;}
			$xmldata .= "	<{$field}>".xmlEncodeCDATA($rec[$field])."</{$field}>".PHP_EOL;
		}
		$xmldata .= '</xmldata>'.PHP_EOL;
	}
    $xmldata.='</export>'.PHP_EOL;
    pushData($xmldata,'xml',$filename);
    exit;
}
?>
