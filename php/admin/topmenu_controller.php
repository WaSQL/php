<?php
	global $CONFIG;
	if(!isset($CONFIG['admin_menu_color'])){
		$CONFIG['admin_menu_color']='gray';
	}
	setView('default');
	if(isAdmin()){
		global $USER;
		//get wasql_tables
		$alltables=getDBTables();
		//build a meta map
		$meta=getDBRecords(array('-table'=>'_tabledata','-index'=>'tablename','-fields'=>'tablename,tablegroup,synchronize,tabledesc'));
		foreach($alltables as $table){
			if(preg_match('/^\_/',$table)){
				$key='WaSQL';
			}
			else{
				if(isset($meta[$table]['tablegroup']) && strlen($meta[$table]['tablegroup'])){
					$key=$meta[$table]['tablegroup'];
				}
				else{$key='Ungrouped';}
			}
			$tables[$key][]=$table;
		}
		ksort($tables);
		//get the last 15 viewed pages
		$pages=getDBRecords(array('-table'=>'_pages','-fields','_id,name','-order'=>'_edate desc,_adate desc','-limit'=>15));
		//get the first 15 templates
		$templates=getDBRecords(array('-table'=>'_templates','-fields','_id,name','-order'=>'name','-limit'=>15));
		//get the most active 15 users
		$users=getDBRecords(array('-table'=>'_users','-fields','_id,type,username','-order'=>'_edate desc,_adate desc','-limit'=>15));
		setView('admin');
	}

?>