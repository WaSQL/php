<?php
loadExtras('translate');
function configSave(){
	foreach($_REQUEST as $k=>$v){
		if(isWasqlField($k)){continue;}
		if(is_array($v)){$v=implode(':',$v);}
		if(!strlen(trim($v))){$v='NULL';}
		$ok=addDBRecord(array(
			'-table'=>'_config',
			'name'=>$k,
			'current_value'=>$v,
			'-upsert'=>'current_value'
		));
		if(!isNum($ok)){
			echo $ok;exit;
		}
	}
	return 1;
}
function configBuildFormField($field,$cparams=array()){
	global $CONFIG;
	if(!is_array($cparams)){$cparams=array();}
	switch(strtolower($field)){
		case 'auth_method':
			$opts=array(
				'wasql'=>'WaSQL',
				'ldap'=>'LDAP',
				'okta'=>'OKTA',
				'okta_ldap'=>'OKTA+LDAP'
			);
			$params=array(
				'id'=>'config_auth_method',
				'class'=>'select',
				'required'=>1,
				'onchange'=>"return configAuthMethodChanged(this);",
				'data-nav'=>"/php/admin.php",
				'data-div'=>'config_users',
				'value'=>$CONFIG[$field]
			);
			foreach($cparams as $k=>$v){
				if(isset($params[$k]) && !strlen($v)){unset($params[$k]);}
				else{$params[$k]=$v;}
			}
			return buildFormSelect($field,$opts,$params);
		break;
		case 'admin_color':
			//w_blue,w_gray,w_green,w_red,w_yellow,w_orange,w_teal,w_light,w_dark
			$opts=array(
				'w_blue'=>'Blue',
				'w_gray'=>'Gray',
				'w_green'=>'Green',
				'w_red'=>'Red',
				'w_yellow'=>'Yellow',
				'w_orange'=>'Orange',
				'w_teal'=>'Teal',
				'w_light'=>'Light',
				'w_dark'=>'Dark'
			);
			$params=array(
				'id'=>'config_auth_method',
				'class'=>'select',
				'value'=>$CONFIG[$field]
			);
			foreach($cparams as $k=>$v){
				if(isset($params[$k]) && !strlen($v)){unset($params[$k]);}
				else{$params[$k]=$v;}
			}
			return buildFormSelect($field,$opts,$params);
		break;
		case 'databases':
			global $DATABASE;
			$opts=array();
			$recs=sortArrayByKeys($DATABASE,array('dbtype'=>SORT_ASC,'name'=>SORT_ASC));
			foreach($recs as $db){
				$opts[$db['name']]="{$db['name']} ({$db['dbtype']})";
			}
			$params=array(
				'value'=>$CONFIG[$field],
				'-display'=>'column',
				'width'=>4
			);
			foreach($cparams as $k=>$v){
				if(isset($params[$k]) && !strlen($v)){unset($params[$k]);}
				else{$params[$k]=$v;}
			}
			return buildFormCheckbox($field,$opts,$params);
		break;
		default:
			$params=array(
				'class'=>'input',
				'required'=>1,
				'value'=>$CONFIG[$field]
			);
			foreach($cparams as $k=>$v){
				if(isset($params[$k]) && !strlen($v)){unset($params[$k]);}
				else{$params[$k]=$v;}
			}
			return buildFormText($field,$params);
		break;
		case 'ldap_password':
		case 'smtppass':
			$params=array(
				'class'=>'input',
				'required'=>1,
				'value'=>$CONFIG[$field]
			);
			foreach($cparams as $k=>$v){
				if(isset($params[$k]) && !strlen($v)){unset($params[$k]);}
				else{$params[$k]=$v;}
			}
			return buildFormPassword($field,$params);
		break;
		case 'smtpport':
			$params=array(
				'class'=>'input',
				'type'=>'number',
				'required'=>1,
				'value'=>$CONFIG[$field]
			);
			foreach($cparams as $k=>$v){
				if(isset($params[$k]) && !strlen($v)){unset($params[$k]);}
				else{$params[$k]=$v;}
			}
			return buildFormText($field,$params);
		break;
		case 'authldap_checkmemberof':
		case 'authldap_secure':
		case 'ldap_checkmemberof':
		case 'ldap_secure':
		case 'wasql_synchronize':
		case 'phpmailer':
		case 'cron':
		case 'userlog':
		case 'log_queries':
		case 'stage':
			$opts=array(
				'1'=>'Yes',
				'0'=>'No'
			);
			$params=array(
				'1_class'=>'btn w_green',
				'0_class'=>'btn w_red',
				'value'=>$CONFIG[$field]
			);
			foreach($cparams as $k=>$v){
				if(isset($params[$k]) && !strlen($v)){unset($params[$k]);}
				else{$params[$k]=$v;}
			}
			return buildFormButtonSelect($field,$opts,$params);
		break;
		case 'login_title':
			$params=array(
				'class'=>'input',
				'value'=>$CONFIG[$field]
			);
			foreach($cparams as $k=>$v){
				if(isset($params[$k]) && !strlen($v)){unset($params[$k]);}
				else{$params[$k]=$v;}
			}
			return buildFormText($field,$params);
		break;
	}
}
function configAddEdit($id){
	$opts=array(
		'-table'=>'_config',
		'-action'=>'/php/admin.php',
		'-fields'=>'name:category,current_value:default_value,description,possible_values',
		'-style_all'=>'width:100%',
		'-onsubmit'=>"return ajaxSubmitForm(this,'main_content');",
		'_menu'=>'config',
		'func'=>'showlist',
		'config_menu'=>1
	);
	if($id>0){$opts['_id']=$id;}
	return addEditDBForm($opts);
}
/**  --- function commonCronCheckSchema
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function configCheckSchema(){
	if(!isDBTable('_config')){
		createWasqlTable('_config');
	}
	//_config
	$finfo=getDBFieldInfo('_config');
	//category
	if(!isset($finfo['category'])){
		global $databaseCache;
		$query="ALTER TABLE _config ADD category ".databaseDataType('varchar(200)')." NULL";
		$ok=executeSQL($query);
		unset($databaseCache['getDBFieldInfo']);
		//echo $query.printValue($ok);
		//add the category to existing records
		$spath=getWasqlPath('php/schema');
		//echo $spath;
		if(file_exists("{$spath}/config.csv")){
			$csv=getCSVFileContents("{$spath}/config.csv");
			//echo printValue($csv);
			foreach($csv['items'] as $i=>$rec){
				$name=strtolower(trim($rec['name']));
				$opts=array(
					'-table'=>'_config',
					'-where'=>"name='{$name}'",
					'category'=>$rec['category']
				);
				$ok=editDBRecord($opts);
				//echo printValue($ok).printValue($opts);
			}
		}
		//set any extras
		$opts=array(
			'-table'=>'_config',
			'-where'=>"category is null and (name like 'aws%' or name like 'plivo%' or name like 'cart%' or name like 'google%' or name like 'paypal\\_%')",
			'category'=>'extras'
		);
		$ok=editDBRecord($opts);
		//remove any extras
		$opts=array(
			'-table'=>'_config',
			'-where'=>"name in ('AjaxRequestUniqueId')",
		);
		$ok=delDBRecord($opts);
		//echo printValue($ok).printValue($opts);exit;
	}
}
function configShowlist($category,$opts=array()){
	global $configShowDifferentListCenter;
	$listopts=array(
		'-table'=>'_config',
		'-nocache'=>1,
		'category'=>$category,
		'-tableclass'=>'table condensed striped bordered',
		'-listview'=>getView('config_item'),
		'-simplesearch'=>1,
		'-onsubmit'=>"return pagingSubmit(this,'main_content');",
		'setprocessing'=>0,
		'-action'=>'/php/admin.php?_menu=config&func=showlist_ajax',
		'-results_eval'=>'configShowlistExtra',
		'-pretable'=>'<div class="w_bigger w_bold">'.ucfirst($category).'</div><div style="display:flex;justify-content:flex-start;align-items:flex-start;flex-wrap:wrap;">',
		'-posttable'=>'</div>'
	);
	if($category=='misc' or !strlen($category)){
		unset($listopts['category']);
		$listopts['-where']="ifnull(category,'')=''";
	}
	foreach($opts as $k=>$v){
		if(!strlen($v)){unset($listopts[$k]);}
		else{
			$listopts[$k]=$v;
		}
	}
	// $debug=$listopts;
	// unset($debug['-listview']);unset($debug['-pretable']);
	// $listopts['-posttable'].=printValue($debug);
	return databaseListRecords($listopts);
}
function configShowlistExtra($recs){
	$elid=0;
	foreach($recs as $i=>$rec){
		$elid+=1;
		$recs[$i]['dname']=ucwords(str_replace('_',' ',$rec['name']));
		if(!strlen($rec['default_value'])){
			$recs[$i]['default_value']='no default';
		}
		$recs[$i]['edit']='<a class="w_link" href="#" onclick="return configNav(this)" data-div="centerpop" data-nav="/php/admin.php" data-_menu="config" data-func="addedit" data-id="'.$rec['_id'].'"><span class="icon-edit w_small w_gray"></span></a>';
		$recs[$i]['edit'].='<a class="w_link" style="margin-left:10px;" href="#" onclick="return configNav(this)" data-confirm="Delete this setting?" data-div="main_content" data-nav="/php/admin.php" data-_menu="config" data-func="delete" data-category="'.$rec['category'].'" data-id="'.$rec['_id'].'"><span class="icon-cancel w_small w_red"></span></a>';
		if(strlen($rec['possible_values'])){
			if(stringBeginsWith($rec['possible_values'],'&')){
				$efield='current_value';
				$_REQUEST[$efield]=$rec[$efield];
				$evalstr='<?='.preg_replace('/^\&/','',$rec['possible_values']).'?>';
				$recs[$i]['cvedit']=evalPHP($evalstr);
			}
			elseif($rec['possible_values']=='0=Off,1=On'){
				$cparams=array(
					'value'=>$rec['current_value'],
					'-formname'=>'editfieldform_'.$rec['_id'],
					'style'=>'width:100%;'
				);
				$recs[$i]['cvedit']=buildFormSelectOnOff('current_value',$cparams);
			}
			else{
				$cvals=preg_split('/\,/',$rec['possible_values']);
				$copts=array();
				foreach($cvals as $cval){
					$parts=preg_split('/\=/',$cval,2);
					if(count($parts)==2){
						$t=trim($parts[0]);
						$d=trim($parts[1]);
						$copts[$t]=$d;
					}
					else{
						$t=trim($parts[0]);
						$copts[$t]=$t;
					}
				}
				$cparams=array(
					'message'=>' --- ',
					'value'=>$rec['current_value'],
					'-formname'=>'editfieldform_'.$rec['_id'],
					'style'=>'width:100%;'
				);
				$recs[$i]['cvedit']=buildFormSelect('current_value',$copts,$cparams);
			}
		}
		else{
			$cparams=array(
				'value'=>$rec['current_value'],
				'style'=>'width:100%',
				'class'=>'input w_small'
			);
			$recs[$i]['cvedit']=buildFormText('current_value',$cparams);
		}
	}
	return $recs;
}
?>
