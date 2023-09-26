<?php
loadExtras('translate');
function configSave(){
	foreach($_REQUEST as $k=>$v){
		if(isWasqlField($k)){continue;}
		if(is_array($v)){$v=implode(':',$v);}
		if(!strlen(trim($v))){$v='NULL';}
		$addopts=array(
			'-table'=>'_config',
			'name'=>$k,
			'current_value'=>$v,
			'-upsert'=>'current_value'
		);
		$ok=addDBRecord($addopts);
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
		case 'okta_auth_method':
			$opts=array(
				'oauth2'=>'OAuth 2.0',
				'saml'=>'SAML',
			);
			$params=array(
				'id'=>'config_okta_auth_method',
				'class'=>'select',
				'required'=>1,
				// 'onchange'=>"return configOktaAuthMethodChanged(this);",
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
		case 'wasql_synchronize_slave':
			global $ALLCONFIG;
			//return printValue($ALLCONFIG);
			$opts=array();
			foreach($ALLCONFIG as $host=>$info){
				$opts[$host]="{$info['name']} ({$info['database']})";
			}
			$params=array(
				'id'=>'wasql_synchronize_slave',
				'message'=>' -- Target Host --',
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
		//********************************
		// OKTA FORM INPUT BUILDERS
		//********************************
		// Misc. inputs requiredif Okta OAuth 2.0
		case 'okta_client_id':
		case 'okta_client_secret':
			$params=array(
				'class'=>'input',
				'requiredif'=>'okta_auth_method:oauth2',
				'value'=>$CONFIG[$field],
			);
			return buildFormText($field,$params);
			break;
		// Custom params for specific Okta OAuth 2.0 inputs
		/* Intentionally blank */
		// Misc. inputs requiredif Okta SAML
		case 'okta_simplesamlphp_service_provider_id':
		case 'okta_simplesamlphp_config_auth__adminpassword':
		case 'okta_simplesamlphp_config_technicalcontact_name':
		case 'okta_simplesamlphp_config_technicalcontact_email':
			$params=array(
				'class'=>'input',
				'requiredif'=>'okta_auth_method:saml',
				'value'=>$CONFIG[$field],
				'id'=>strtolower($field)
			);
			return buildFormText($field,$params);
			break;
		// Custom params for specific Okta SAML inputs
		case 'okta_simplesamlphp_config_session__duration_int':
			$params=array(
				'class'=>'input',
				'requiredif'=>'okta_auth_method:saml',
				'value'=>$CONFIG[$field],
				'onchange'=>'configUpdateSessionCookieLifetimeInputValue(this);',
			);
			return buildFormText($field,$params);
			break;
		// Custom params for hidden OKTA SAML SimpleSAMLphp configuration inputs
		case 'okta_simplesamlphp_config_session__cookie__domain':
			$params=array(
				'value'=>'.'.$_SERVER['UNIQUE_HOST'],
			);
			foreach($cparams as $k=>$v){
				if(isset($params[$k]) && !strlen($v)){unset($params[$k]);}
				else{$params[$k]=$v;}
			}
			return buildFormHidden($field,$params);
			break;
		case 'okta_simplesamlphp_config_session__cookie__lifetime_int':
			$params=array(
				'value'=>$CONFIG[$field],
			);
			foreach($cparams as $k=>$v){
				if(isset($params[$k]) && !strlen($v)){unset($params[$k]);}
				else{$params[$k]=$v;}
			}
			return buildFormHidden($field,$params);
			break;
		case 'okta_simplesamlphp_config_store__sql__dsn':
			$simplesamlphp_dir=getWasqlPath('php/extras/simplesamlphp');
			$sqlite_file='sqlite:'.$simplesamlphp_dir.'/simplesaml.sq3';
			$params=array(
				'value'=>$sqlite_file,
			);
			foreach($cparams as $k=>$v){
				if(isset($params[$k]) && !strlen($v)){unset($params[$k]);}
				else{$params[$k]=$v;}
			}
			return buildFormHidden($field,$params);
		//********************************
		// DEFAULT FORM INPUT BUILDER
		//********************************
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
		//********************************
		// END DEFAULT FORM INPUT BUILDER
		//********************************
		// More special form inputs builders
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
		case 'wasql_crons':
		case 'userlog':
		case 'log_queries':
		case 'wasql_queries':
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
		case 'wasql_queries_user':
		case 'log_queries_user':
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
		case 'wasql_queries_time':
		case 'wasql_queries_days':
		case 'log_queries_time':
		case 'log_queries_days':
			$params=array(
				'class'=>'input',
				'value'=>$CONFIG[$field],
				'inputtype'=>'number'
			);
			foreach($cparams as $k=>$v){
				if(isset($params[$k]) && !strlen($v)){unset($params[$k]);}
				else{$params[$k]=$v;}
			}
			return buildFormText($field,$params);
		break;
		case 'mysql_error_log':
		case 'mysql_slow_query_log':
		case 'apache_error_log':
		case 'apache_access_log':
		case 'php_error_log':
		case 'custom_log_1':
		case 'custom_log_2':
		case 'custom_log_3':
			$params=array(
				'class'=>'input',
				'value'=>$CONFIG[$field],
			);
			foreach($cparams as $k=>$v){
				if(isset($params[$k]) && !strlen($v)){unset($params[$k]);}
				else{$params[$k]=$v;}
			}
			return buildFormText($field,$params);
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
	if(stringContains($finfo['name']['_dbtype_ex'],'varchar(50)')){
		$ok=executeSQL("alter table _config modify name varchar(200) NOT NULL UNIQUE");
	}
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
		if(is_file("{$spath}/config.csv")){
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
function configGetOktaSAMLACSURL() {
	global $CONFIG;
	$tmpl="https://%s%s/simplesaml/module.php/saml/sp/saml2-acs.php/<span id=\"okta_simplesamlphp_service_provider_id_acs\">%s</span>";
	$subdomain=$_SERVER['SUBDOMAIN']?$_SERVER['SUBDOMAIN'].'.':'';
	// $sp=$CONFIG['okta_simplesamlphp_service_provider_id'];
	// $url=sprintf($tmpl, $subdomain, $_SERVER['UNIQUE_HOST'], $sp);
	$url=sprintf($tmpl, $subdomain, $_SERVER['UNIQUE_HOST'], '');
	echo $url;
}
function configGetOktaSAMLSPEntityID() {
	global $CONFIG;
	$tmpl="https://%s%s/simplesaml/module.php/saml/sp/metadata.php/%s";
	$subdomain=$_SERVER['SUBDOMAIN']?$_SERVER['SUBDOMAIN'].'.':'';
	// $sp=$CONFIG['okta_simplesamlphp_service_provider_id'];
	// $url=sprintf($tmpl, $subdomain, $_SERVER['UNIQUE_HOST'], $sp);
	$url=sprintf($tmpl, $subdomain, $_SERVER['UNIQUE_HOST'], '');
	echo $url;
}
function configGetSimpleSAMLphpVirtualHostDirectives() {
	// Output dynamically-generated virtual host directive(s) that match the following example:
	/*
	SetEnv SIMPLESAMLPHP_CONFIG_DIR /var/www/wasql_stage/php/extras/simplesamlphp/config
	Alias /simplesaml /var/www/wasql_stage/php/extras/simplesamlphp/www
	<Directory /var/www/wasql_stage/php/extras/simplesamlphp>
	    Require all granted
	</Directory>
	*/
	$simplesamlphp_dir=getWasqlPath('php/extras/simplesamlphp');
	$simplesamlphp_config_dir=getWasqlPath('php/extras/simplesamlphp/config');
	$simplesamlphp_alias_dir=getWasqlPath('php/extras/simplesamlphp/www');
	$directives=<<<EOF
SetEnv SIMPLESAMLPHP_CONFIG_DIR {$simplesamlphp_config_dir}<br>Alias /simplesaml {$simplesamlphp_alias_dir}<br>&lt;Directory {$simplesamlphp_dir}&gt;<br>    Require all granted<br>&lt;/Directory&gt;
EOF;
	echo $directives;
}
function configOktaSAMLWriteConfig(){
	loadExtras('okta');
	// Put Okta SAML config values from configuration form inputs in array
	// All input names with the prefix "okta_simplesamlphp_config_" are SimpleSAMLphp configuration $config array properties
	$config=array();
	foreach($_REQUEST as $key=>$value){
		if(strpos($key, 'okta_simplesamlphp_config_')===0){
			$k=str_replace('okta_simplesamlphp_config_', '', $key); // Remove the prefix
			$k=str_replace('__', '.', $k);
			// If key ends in "_int", convert the value to an integer
			if(strpos($k, '_int', -4)===(strlen($k)-4)){
				$k=substr($k, 0, -4); // Remove the data type suffix
				$config[$k]=intval($value);
			}
			else{
				$config[$k]=$value;
			}
		}
	}
	$result=Okta::writeSAMLConfig($config);
	if ($result !== true) {
		// TODO: Set view with error message
		echo "<p style=\"margin:6px 0 0 0; color:red;\"><span class=\"icon-close\"></span> {$result} SimpleSAMLphp config.php file not written.</p>";
		commonLogMessage('user', "Okta SAML configuration was".($ok ? "" : " not")." written. {$result}");
	}
}
?>
