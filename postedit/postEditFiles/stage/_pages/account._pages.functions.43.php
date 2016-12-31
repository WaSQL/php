<?php
function accountCSV2Recs($data){
	$lines=preg_split('/[\r\n]+/',$data);
    $keystr=array_shift($lines);
	$keys=csvParseLine($keystr);
	$cnt=count($lines);
	$trecs=array();
	foreach($lines as $line){
    	$parts=csvParseLine($line);
    	$datarec=array();
    	for($x=0;$x<count($parts);$x++){
			$datarec[$keys[$x]]=$parts[$x];
		}
		$trecs[]=$datarec;
	}
	return $trecs;
}
function pageAccountForm(){
	$client=accountGetClient();
	return addEditDBForm(array(
		'-table'	=> 'clients',
		'_id'		=> $client['_id'],
		'-fields'	=> 'name,url,address,city:province:postal_code,country,timezone',
		'-hide'		=> 'delete,clone'
	));
}
function accountGetStreamData(){
	$client=accountGetClient();
	$query=<<<ENDOFQUERY
		SELECT
			concat(u.firstname,' ',u.lastname) name
			,l.jstr
			,l.speech answer
			,l._cdate
			,(TRIM(BOTH '"' FROM json_extract(l.jstr, '$.report'))) report
		FROM skillsai_live._users u, skillsai_live.alexa_log l
		WHERE
			l.profile_id=u.profile_id
			and l.speech is not null
			and json_extract(l.jstr, '$.report') like '%'
			and json_extract(l.jstr, '$.client_id')='{$client['_id']}'
		ORDER BY l._id desc
		limit 100
ENDOFQUERY;
	$stream['audio']=array();
	$recs=getDBRecords($query);
	if(isset($recs[0])){
		$recs=array_reverse($recs);
    	foreach($recs as $rec){
			$rec['question']=$rec['report'];
        	$json=json_decode($rec['jstr'],true);
			if(isset($json['from_date'])){
				$rec['question'].=' from '.$json['from_date'];
			}
			if(isset($json['to_date'])){
				$rec['question'].=' to '.$json['to_date'];
			}
			//city and state
			if(isset($json['city']) && isset($json['city'])){
				$rec['question'].= " in {$json['city']},{$json['state']}";
			}
			elseif(isset($json['city'])){
				$rec['question'].= " in {$json['city']}";
			}
			elseif(isset($json['city'])){
				$rec['question'].= " in {$json['state']}";
			}
			$stream['audio'][]=$rec;
		}
	}
	//orders
	$query=<<<ENDOFQUERY
		SELECT
			concat(o.firstname,' ',o.lastname) name
			,o.cdate
			,o.amount
			,o.order_id
			,o.product_ids
			,o.product_names
			,o.product_qtys
			,o.billto_city
			,o.billto_state
			,o.billto_postcode
		FROM skillsai_live.clientdata_woo_orders o
		WHERE
			o.client_id='{$client['_id']}'
		ORDER BY o.cdate desc
		limit 100
ENDOFQUERY;
	$stream['orders']=array();
	$recs=getDBRecords($query);
	if(isset($recs[0])){
		$recs=array_reverse($recs);
    	foreach($recs as $rec){
			$product=array(
				'id'=>json_decode($rec['product_ids'],true),
				'name'=>json_decode($rec['product_names'],true),
				'qty'=>json_decode($rec['product_qtys'],true)
			);
			$rec['items']=array();
			foreach($product['id'] as $i=>$id){
				$rec['items'][]=array(
					'id'=>$id,
					'name'=>$product['name'][$i],
					'qty'=>$product['qty'][$i]
				);
			}
			$stream['orders'][]=$rec;
		}
	}
	return $stream;
}
function pageBuildQuickFilter($field,$rec){
	$defaults=json_decode($rec['defaults'],true);
	//return printValue($defaults);
	switch($field){
    	case 'default_date':
    		$opts=array(
    			'today'			=> 'Today',
					'yesterday'			=> 'Yesterday',
    			'this_week'		=> 'This Week',
				'this_month'	=> 'This Month',
				'this_quarter'	=> 'This Quarter',
				'this_year'		=> 'This Year',
				'last_week'		=> 'Last Week',
				'last_month'	=> 'Last Month',
				'last_3months'	=> 'Last 3 Months',
				'last_6months'	=> 'Last 6 Months',
				'last_quarter'	=> 'Last Quarter',
				'last_year'		=> 'Last Year',
				'last_2years'	=> 'Last 2 Years',
				'last_3years'	=> 'Last 3 Years',
				'last_4years'	=> 'Last 4 Years',
			);
			$params=array('message'=>'-- Default Date Range --','value'=>$defaults[$field]);
    		return buildFormSelect($field,$opts,$params);
    	break;
	}
	return 'INVALID QUICK FILTER:'.$field;
}
function accountAddTabForm(){
	global $USER;
	$client=accountGetClient();
	return addEditDBForm(array(
		'-table'	=> 'user_tabs',
		'user_id'	=> $USER['_id'],
		'-action'	=> '/t/1/account/reports',
		'setprocessing'=>0,
		'-fields'	=> 'name',
		'-hide'		=> 'delete,clone',
		'-onsubmit'	=> "return ajaxSubmitForm(this,'content');"
	));
}
function pageProfileForm(){
	global $USER;
	$client=accountGetClient();
	return addEditDBForm(array(
		'-table'	=> '_users',
		'_id'		=> $USER['_id'],
		'-fields'	=> 'username:password,firstname:lastname,city:state:zip,country',
		'-hide'		=> 'delete,clone'
	));
}

function pageSetClientAccountStatus($status=0){
	$client=accountGetClient();
	$opts=array(
		'-table'	=> 'clients',
		'-where'	=> "_id={$client['_id']}",
		'account_status' => $status
	);
	$ok=editDBRecord($opts);
	$client=accountGetClient(1);
}
function accountLiveStageSwitch(){
	$client=accountGetClient();
	$opts=array(
		'live'=>'Live',
		'test'=>'Test'
	);
	$name='account_status';
	$params=array(
		'value' =>$client[$name],
		'-onclick'=>'reportUpdateAccountStatus(this);',
		'-size'=>'small'
		);

	return buildFormToggleButton($name,$opts,$params);
}
function accountGetUsers(){
	global $USER;
	if(!isNum($USER['client_id'])){
		return array();
	}
	$recs=getDBRecords(array(
		'-table'=>'_users',
		'client_id'=>$USER['client_id'],
		'-fields'=>'_id,firstname,lastname,username,email,_adate,utype,active,title'
	));
	foreach($recs as $i=>$rec){
    	if($rec['active']==0){$recs[$i]['type']='disabled';}
    	else{
        	switch($rec['utype']){
            	case 0:
            	case 1:
					$recs[$i]['type']='admin';
				break;
				default:
					$recs[$i]['type']='user';
				break;
			}
		}
	}
	return $recs;
}
function accountAddEditUser($id=0){
	global $USER;
	if(!isNum($USER['client_id'])){
		return array();
	}
	if($USER['utype'] > 2){
    	return array();
	}
	$opts=array(
		'-table'=>'_users',
		'client_id'=>$USER['client_id'],
		'-fields'=>'active:utype:title,firstname:lastname,username:email',
		'-action'=>'/t/1/account/users',
		'-onsubmit'=>"return ajaxSubmitForm(this,'content');",
		'active_displayname'=>'Enable'
	);
	if(!isAdmin()){
    	$opts['utype_tvals']="1\r\n2";
    	$opts['utype_dvals']="Account Admin\r\nReport User";
	}
	if($id>0){$opts['_id']=$id;}
	else{
    	$opts['-fields']='pin:utype:title,firstname:lastname,username:email';
    	$opts['pin_required']=1;
	}
	return addEditDBForm($opts);
}
function accountIsAdmin(){
	global $USER;
	if(!isNum($USER['client_id'])){return false;}
	if($USER['utype']<2){return true;}
	return false;
}
function accountGetWooHooks($opts){
	//echo printValue($opts);exit;
	$hooks=woocommerceGetWebhooks($opts);
	//echo "HERE"
	$clienturl=accountBuildSkapiUrl('woo');
	//echo printValue($clienturl);exit;
	//check for two hooks: product.updated and order.updated
	$ourhooks=array();
	foreach($hooks as $i=>$hook){
		if(!stringContains($hook['delivery_url'],'skillsai')){
            unset($hooks[$i]);
            continue;
		}
		$ourhooks[$hook['topic']]=$hook;
	}
	$topic='order.updated';
	$hookurl="{$clienturl}/{$topic}";
	if(!isset($ourhooks[$topic])){
		$copts=$opts;
		$copts['delivery_url']=$hookurl;
		$copts['topic']=$topic;
		$copts['name']='Skillsai Webhook '. $topic;
		$copts['secret']='Skillsai Webhooks';
		$ok=woocommerceAddWebhook($copts);
		$hooks=woocommerceGetWebhooks($opts);
	}
	elseif($ourhooks[$topic]['delivery_url'] != $hookurl){
		$copts=$opts;
		$copts['id']=$hook['id'];
		$copts['delivery_url']=$hookurl;
		$ok=woocommerceEditWebhook($copts);
		$hooks[$i]['delivery_url']=$hookurl;
	}
	$topic='product.updated';
	$hookurl="{$clienturl}/{$topic}";
	if(!isset($ourhooks[$topic])){
		$copts=$opts;
		$copts['delivery_url']=$hookurl;
		$copts['topic']=$topic;
		$copts['name']='Skillsai Webhook '. $topic;
		$copts['secret']='Skillsai Webhooks';
		$ok=woocommerceAddWebhook($copts);
		$hooks=woocommerceGetWebhooks($opts);
	}
	elseif($ourhooks[$topic]['delivery_url'] != $hookurl){
		$copts=$opts;
		$copts['id']=$hook['id'];
		$copts['delivery_url']=$hookurl;
		$ok=woocommerceEditWebhook($copts);
		$hooks[$i]['delivery_url']=$hookurl;
	}
	return $hooks;
}
function accountFormField($field){
	$client=accountGetClient();
	//echo printValue($client);exit;
	switch($field){
    	case 'woo_consumer_key':
    	case 'woo_consumer_secret':
    	case 'woo_url':
			$xfield=str_replace('woo_','',$field);
    		return buildFormText($field,array(
				'id'			=> $field,
				'placeholder'	=> str_replace('_',' ',$xfield),
				'value'			=> $client['datasources']['woo'][$xfield]
			));
    	break;
    	case 'stripe_secret_key':
			$xfield=str_replace('stripe_','',$field);
    		return buildFormText($field,array(
				'id'			=> $field,
				'placeholder'	=> str_replace('_',' ',$xfield),
				'value'			=> $client['datasources']['stripe'][$xfield]
			));
    	break;
	}
}
function accountGetClient($dirty=0){
	return commonGetClient($dirty);
}
function accountGetDataSources(){
	$client=accountGetClient();
	return $client['datasources'];
}
function accountSetDatasource($source,$opts){
	global $accountGetClient;
	$accountGetClient=accountGetClient();
	if(!isNum($accountGetClient['_id'])){
    	echo "ERROR: Invalid Client";exit;
	}
	$accountGetClient['datasources'][$source]=$opts;
	$json=json_encode($accountGetClient['datasources']);
	$ok=editDBRecord(array(
		'-table'=>'clients',
		'-where'=>"_id={$accountGetClient['_id']}",
		'datasources'=>$json
	));
	return $ok;
}
function accountBuildSkapiUrl($source='woo'){
	$client=accountGetClient();
	if(isDBStage()){
    	return "http://stage.skillsai.com/skapi/{$source}/{$client['apikey']}";
	}
	return "http://www.skillsai.com/skapi/{$source}/{$client['apikey']}";
}


/*------------------------------*/
function reportMonthYear($cid){
	if(isset($_REQUEST['date']) && !stringContains($_REQUEST['date'],'undef')){return $_REQUEST['date'];}
	return date('Y M');
}
function reportSelectCountry($id){
	return 'COUNTRY';
	$opts=array();
	$query=<<<ENDOFQUERY
	SELECT
		count(*) cnt
		,ship_to_country
	FROM "BODSSCHEMA"."ORAODH"
	WHERE ship_to_country !=''
		and status in (2,3,4,5)
		and type in ('I','D','C')
	GROUP BY ship_to_country
	HAVING count(*) > 10
	ORDER BY ship_to_country
ENDOFQUERY;
	$recs=getDBRecords($query);
	foreach($recs as $rec){
		$opts[$rec['ship_to_country']]=$rec['ship_to_country'];
	}
	return buildFormSelect('country',$opts,array('message'=>'--All Countries--','id'=>"country_{$id}",'class'=>'form-control input-sm','onchange'=>"return  reportChangeFilters('{$id}');"));
}
function reportActiveTab($id){
	if($_REQUEST['tab_id']==$id){return ' active';}
	return '';
}
function reportGetUserTabs(){
	global $USER;
	$recs=getDBRecords(array(
		'-table'	=> 'user_tabs',
		'user_id'	=> $USER['_id'],
		'-order'	=> 'name',
		'-index'	=> 'name'
	));
	if(!is_array($recs) || !count($recs)){
		//none found - create default tabs and reports in each
		$projects=array('Sales','Orders','Products','Customers');
		$order=0;
		foreach($projects as $project){
			$order++;
			//create the tab
			$tab_id=addDBRecord(array(
				'-table'	=> 'user_tabs',
				'user_id'	=> $USER['_id'],
				'name'		=> $project,
				'list_order'=> $order
			));
			//get the reports that relate
			$trecs=getDBRecords(array(
				'-table'=>'reports',
				'project'=>$project,
				'active'=>1,
				'-fields'=>'_id',
				'-order'=>'name'
			));
			//echo $tab_id.printValue($recs);exit;
			if(is_array($trecs)){
            	foreach($trecs as $trec){
					$ok=reportAddUserTabReports($tab_id,$trec['_id']);
				}
			}
		}
		$recs=getDBRecords(array(
			'-table'	=> 'user_tabs',
			'user_id'	=> $USER['_id'],
			'-order'	=> 'list_order,name',
			'-index'	=> 'name'
		));
	}
	return $recs;
}
function reportAddUserTab($name){
	global $USER;
	$id=addDBRecord(array(
		'-table'	=> 'user_tabs',
		'user_id'	=> $USER['_id'],
		'name'		=> $name
	));
	return reportGetUserTabs();
}
function reportEditUserTab($id,$name){
	global $USER;
	$ok=editDBRecord(array(
		'-table'	=> 'user_tabs',
		'-where'	=> "user_id={$USER['_id']} and _id={$id}",
		'name'		=> $name
	));
	return reportGetUserTabs();
}
function reportDefaultUserTab($id){
	global $USER;
	$ok=editDBRecord(array(
		'-table'	=> 'user_tabs',
		'-where'	=> "user_id={$USER['_id']}",
		'default_tab'	=> 0
	));

	$ok=editDBRecord(array(
		'-table'	=> 'user_tabs',
		'-where'	=> "user_id={$USER['_id']} and _id={$id}",
		'default_tab'	=> 1
	));
	return reportGetUserTabs();
}
function reportDelUserTab($id){
	global $USER;
	$ok=delDBRecord(array(
		'-table'	=> 'user_tabs',
		'-where'	=> "user_id={$USER['_id']} and _id={$id}",
	));
	$id=delDBRecord(array(
		'-table'	=> 'report_tabs',
		'-where'	=> "user_tab_id={$id}",
	));
	return reportGetUserTabs();
}
function reportGetPostcodeEx($postcode,$country='US'){
	global $reportGetPostcodeEx;
	if(isset($reportGetPostcodeEx[$country][$postcode])){
    	return $reportGetPostcodeEx[$country][$postcode];
	}
	if(!is_array($reportGetPostcodeEx)){
    	$reportGetPostcodeEx=array();
	}
	if(!is_array($reportGetPostcodeEx[$country])){
    	$reportGetPostcodeEx[$country]=array();
	}
	$reportGetPostcodeEx[$country][$postcode]=getDBRecord(array(
		'-table'=>'cities',
		'country'=>$country,
		'zipcode'=>$postcode,
		'-fields'=>'city,state'
	));
	return $reportGetPostcodeEx[$country][$postcode];
}
function reportClearUserTab($id){
	global $USER;
	$id=delDBRecord(array(
		'-table'	=> 'report_tabs',
		'-where'	=> "user_tab_id={$id}",
	));
	return reportGetUserTabs();
}
function reportUpdateTabSort($str){
	global $USER;
	$ids=preg_split('/[\:\,]+/',$str);
	$sort=0;
	foreach($ids as $id){
		$id=editDBRecord(array(
			'-table'	=> 'user_tabs',
			'-where'	=> " _id={$id}",
			'list_order'=>$sort
		));
		$sort+=1;
	}
	return reportGetUserTabs();
}
function reportGetUserTabReports($tabid){
	if(!strlen($tabid)){return array();}
	$query=<<<ENDOFQUERY
	SELECT
		kt.defaults
		,kt.data_table
		,k.name
		,k.card_image
		,k.body
		,k.data
		,k.url
		,k.category
		,kt._id
		,kt.report_id
		,kt.user_tab_id
	FROM
		reports k, report_tabs kt
	WHERE
		k._id=kt.report_id
		and k.active=1
		and kt.user_tab_id={$tabid}
	ORDER BY kt.list_order,k.name
ENDOFQUERY;
	$recs=getDBRecords(array('-query'=>$query));
	return $recs;
}

function reportGetReportTabRec($id){
	$query=<<<ENDOFQUERY
	SELECT
		kt.defaults
		,kt.data_table
		,k.name
		,k.card_image
		,k.body
		,k.url
		,k.category
		,k.data
		,kt._id
		,kt.report_id
		,kt.user_tab_id
	FROM
		reports k, report_tabs kt
	WHERE
		k._id=kt.report_id
		and k.active=1
		and kt._id={$id}
ENDOFQUERY;
	$rec=getDBRecord(array('-query'=>$query));
	return $rec;
}

function reportGetTabName($id){
	global $USER;
	$query="select name from user_tabs where user_id={$USER['_id']} and _id={$id}";
	$rec=getDBRecord(array('-query'=>$query));
	return $rec['name'];
}

function reportAddUserTabReports($tab_id,$report_id){
	global $USER;
	$opts=array(
		'-table'	=> 'report_tabs',
		'report_id'	=> $report_id,
		'user_tab_id'=> $tab_id
	);
	$id=addDBRecord($opts);
	//echo printValue($id).printValue($opts);exit;
	return reportGetUserTabReports($tab_id);
}
function reportSortUserTabReports($str,$tab_id){
	global $USER;
	$ids=preg_split('/[\:\,]+/',$str);
	$sort=0;
	foreach($ids as $id){
		$id=editDBRecord(array(
			'-table'	=> 'report_tabs',
			'-where'	=> " _id={$id}",
			'list_order'=>$sort
		));
		$sort+=1;
	}
	return reportGetUserTabReports($tab_id);
}
function reportDelUserTabReports($tab_id,$report_id){
	global $USER;
	$opts=array(
		'-table'	=> 'report_tabs',
		'-where'	=> "_id={$report_id} and user_tab_id={$tab_id} and user_tab_id in (select _id from user_tabs where user_id={$USER['_id']})"
	);
	//echo printValue($opts);exit;
	$id=delDBRecord($opts);
	return reportGetUserTabReports($tab_id);
}



function pageGetUserReports($tabid){
	global $USER;
	$opts=array(
		'-table'	=> 'reports',
		'active'	=> 1
	);
	return getDBRecords($opts);
}
function pageGetReports($tabname=''){
	global $USER;
	$id=0;
	$opts=array(
		'-table'	=> 'reports',
		'active'	=> 1,
		'-order'	=> 'project,name',
		'-filter'	=> "project is not null"
	);
	if(strlen($tabname)){
		$precs=getDBRecords(array(
			'-query'=>"select distinct(project) as project from reports",
			'-index'=>'project'
		));
		$tabname=strtolower($tabname);
		if(in_array($tabname,array_keys($precs))){
    		$opts['-filter']= "project='{$tabname}'";
		}
	}
	$recs= getDBRecords($opts);
	return $recs;
	//echo printValue($opts).printValue($recs);exit;
}
function pageLoadReport($xrec){
	global $rec;
	$rec=$xrec;
	if(strlen($rec['defaults'])){
        $defaults=json_decode($rec['defaults'],true);
        foreach($defaults as $k=>$v){
			if(preg_match('/(from|to)_date/i',$k)){continue;}
			$rec[$k]=$v;
		}
	}
	$evalstr='<?global $'.'rec;?>'."\n".$rec['body'];
	return evalPHP($evalstr);
}

function pageLoadReportData($xrec){
	global $rec;
	$rec=$xrec;
	$evalstr='<'.'?global $'.'rec;?'.'>'."\n".$rec['data'];
	return evalPHP($evalstr);
}

function accountGetStateName($v){
	return commonGetStateName($v);
}
function accountGetStateCode($v){
	return commonGetStateCode($v);
}
function homeGetUserReports(){
	global $USER;
	$query=<<<ENDOFQUERY
SELECT
	k.report_id
	,p.permalink
	,p.title
	,k.display_order
	,p.report_group
FROM report_users k, _pages p
WHERE
	k.report_id=p._id
	and user_id={$USER['_id']}
ORDER BY k.display_order
ENDOFQUERY;
	$recs=getDBRecords(array(
		'-query'	=> $query
	));
	foreach($recs as $i=>$rec){
		$recs[$i]['classname']="p{$rec['report_id']}";
	}
	return $recs;
}
?>
