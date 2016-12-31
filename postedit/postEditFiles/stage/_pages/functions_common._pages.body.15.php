<?php
function commonGetSubscription(){
	$client=commonGetClient();
	if(!isset($client['_id'])){return array();}
	$rec=getDBRecord(array(
		'-table'=>'clients_stripedata',
		'client_id'=>$client['_id'],
		'subscription_status'=>'active'
	));
	if(!isset($rec['_id'])){return array();}
	$packages=preg_split('/\:/',$rec['packages']);
	$rec['packages']=array();
	foreach($packages as $package){
    	$rec['packages_ex'][$package]=1;
	}
	return $rec;
}
function commonLocal2UTC($local='',$format='Y-m-d H:i:s'){
	if(!strlen($local)){$local=date($format);}
	//get settings
	$client=commonGetClient();
	if(!isset($client['timezone'])){
    	$client['timezone']='America/Denver';
	}
	$date = new DateTime($local, new DateTimeZone($client['timezone']));
	$date->setTimezone(new DateTimeZone('UTC'));
	return $date->format($format);
}
function commonUTC2Local($local='',$format='Y-m-d H:i:s'){
	if(!strlen($local)){$local=date($format);}
	//get settings
	$client=commonGetClient();
	if(!isset($client['timezone'])){
    	$client['timezone']='America/Denver';
	}
	$date = new DateTime($local, new DateTimeZone('UTC'));
	$date->setTimezone(new DateTimeZone($client['timezone']));
	return $date->format($format);
}
function commonGetClient($dirty=0){
	global $USER;
	global $alexa;
	$cid=0;
	if(isset($alexa['user']['client_id'])){$cid=$alexa['user']['client_id'];}
	elseif(isNum($USER['client_id'])){$cid=$USER['client_id'];}
	if($cid==0){
		return array();
	}
	global $commonGetClient;
	if(isset($commonGetClient['_id']) && $dirty==0){
    	return $commonGetClient;
	}
	$aopts=array(
		'-table'	=> 'clients',
		'_id'	=> $cid,
		'-fields'=>'_id,apikey,datasources,name,active,url,account_status'
	);
	$commonGetClient=getDBRecord($aopts);
	if(strlen($commonGetClient['datasources'])){
		$commonGetClient['datasources']=json_decode($accountGetClient['datasources'],true);
	}
	return $commonGetClient;
}
function commonGetStateName($v){
	global $commonGetStateName;
	if(isset($commonGetStateName[$v])){return $commonGetStateName[$v];}
	$rec=getDBRecord(array('-table'=>'states','code'=>$v,'-fields'=>'name'));
	$commonGetStateName[$v]=$rec['name'];
	return $commonGetStateName[$v];
}
function commonGetStateCode($v){
	global $commonGetStateCode;
	if(isset($commonGetStateCode[$v])){return $commonGetStateCode[$v];}
	$rec=getDBRecord(array('-table'=>'states','name'=>$v,'-fields'=>'code'));
	$commonGetStateCode[$v]=$rec['code'];
	return $commonGetStateCode[$v];
}
function commonBuildFilters($rec){
	global $USER;
	global $alexa;
	$client=commonGetClient();
	$client_id=$client['_id'];
	//switch to demo data if not in live mode
	if($client['account_status']!='live'){
    	$client_id=2;
	}
	$filters=array();
	$speech=array();

	//date range
	if(strlen($rec['filters']['from_date'])){
		$fdate=date('Y-m-d',strtotime($rec['filters']['from_date']));
    	$filters[]= " and date(cdate) >= '{$fdate}'";
    	$speech[]="Start Date: ".date('F jS Y',strtotime($fdate)).".";
	}
	if(isset($rec['filters']['to_date']) && strlen($rec['filters']['to_date'])){
		$fdate=date('Y-m-d',strtotime($rec['filters']['to_date']));
  	$filters[]= " and date(cdate) <= '{$fdate}'";
  	$speech[]="End Date: ".date('F jS Y',strtotime($fdate)).".";
	}
	if(!count($filters)){
		if(isset($alexa['swim']['group']) && preg_match('/^last/i',$alexa['swim']['group'])){
			$rec['filters']['default_date']=strtolower(str_replace(' ','_',$alexa['swim']['group']));
		}
		if(strlen($rec['filters']['default_date'])){
			switch($rec['filters']['default_date']){
				case 'Today':
				case 'today':
					$fdate = date('Y-m-d',strtotime('today'));
					$tdate = date('Y-m-d',strtotime('today'));
					$speech[]="Today";
        	break;
				case 'Yesterday':
				case 'yesterday':
					$tdate = date('Y-m-d', strtotime("-1 days"));
					$fdate = date('Y-m-d', strtotime("-1 days"));
					$speech[]="Yesterday";
					break;
				case 'this_week':
					$fdate = date('Y-m-d',strtotime('first day of this week'));
					$tdate = date('Y-m-d',strtotime('last day of this week'));
					$speech[]="This Week";
        	break;
      	case 'this_month':
					$fdate = date('Y-m-d',strtotime('first day of this month'));
					$tdate = date('Y-m-d',strtotime('last day of this month'));
					$speech[]="This Month";
      	break;
      	case 'this_quarter':
      		$current_quarter = ceil(date('n') / 3);
      		$year=date('Y');
      		switch($current_quarter){
          	case 1:
          		$fdate = date('Y-m-d',strtotime("first day of January {$year}"));
							$tdate = date('Y-m-d',strtotime("last day of March {$year}"));
							break;
						case 2:
          		$fdate = date('Y-m-d',strtotime("first day of April {$year}"));
							$tdate = date('Y-m-d',strtotime("last day of June {$year}"));
							break;
						case 3:
          		$fdate = date('Y-m-d',strtotime("first day of July {$year}"));
							$tdate = date('Y-m-d',strtotime("last day of September {$year}"));
							break;
						case 4:
          		$fdate = date('Y-m-d',strtotime("first day of October {$year}"));
							$tdate = date('Y-m-d',strtotime("last day of December {$year}"));
							break;
					}
					$speech[]="This Quarter";
        	break;
      	case 'q1':
      	case 'q2':
      	case 'q3':
      	case 'q4':
      		$quarter=strtolower($rec['filters']['default_date']);
      		$year=date('Y');
      		switch($current_quarter){
          	case 'q1':
          		$fdate = date('Y-m-d',strtotime("first day of January {$year}"));
							$tdate = date('Y-m-d',strtotime("last day of March {$year}"));
							break;
						case 'q2':
          		$fdate = date('Y-m-d',strtotime("first day of April {$year}"));
							$tdate = date('Y-m-d',strtotime("last day of June {$year}"));
							break;
						case 'q3':
          		$fdate = date('Y-m-d',strtotime("first day of July {$year}"));
							$tdate = date('Y-m-d',strtotime("last day of September {$year}"));
							break;
						case 'q4':
          		$fdate = date('Y-m-d',strtotime("first day of October {$year}"));
							$tdate = date('Y-m-d',strtotime("last day of December {$year}"));
							break;
					}
					$speech[]=$quarter;
        	break;
      	case 'this_year':
      		$t=strtotime('today');
      		$year=date('Y',$t);
      		$fdate = date('Y-m-d',strtotime("first day of January {$year}"));
					$tdate = date('Y-m-d',strtotime("last day of December {$year}"));
					$speech[]="Last Year";
      		break;
      	case 'last_week':
      		$today = strtotime('today 00:00:00');
					$this_week_start = strtotime('-1 week monday 00:00:00');
					$this_week_end = strtotime('sunday 23:59:59');
					$last_week_start = strtotime('-2 week monday 00:00:00');
					$last_week_end = strtotime('-1 week sunday 23:59:59');
					$fdate = date('Y-m-d',$last_week_start);
					$tdate = date('Y-m-d',$last_week_end);
					$speech[]="Last Week";
      		break;
      	case 'last_month':
      		$fdate = date('Y-m-d',strtotime('first day of last month'));
					$tdate = date('Y-m-d',strtotime('last day of last month'));
					$speech[]="Last Month";
      		break;
      	case 'last_3months':
      	case 'last_three_months':
      	case 'last_3_months':
					$fdate = date('Y-m-01',strtotime('- 3 Months'));
					$tdate	  =date('Y-m-d');
					$speech[]="Last 3 Months";
      		break;
      	case 'last_6months':
      	case 'last_6_months':
      	case 'last_six_months':
					$fdate = date('Y-m-01',strtotime('- 6 Months'));
					$tdate	  =date('Y-m-d');
					$speech[]="Last 6 Months";
        	break;
      	case 'last_quarter':
					$last_quarter = ceil(date('n') / 3)-1;
					$year=date('Y');
					if($last_quarter==0){
						$last_quarter=4;
						$year=$year-1;
					}
      		switch($last_quarter){
          	case 1:
          		$fdate = date('Y-m-d',strtotime("first day of January {$year}"));
							break;
						case 2:
          		$fdate = date('Y-m-d',strtotime("first day of April {$year}"));
							break;
						case 3:
          		$fdate = date('Y-m-d',strtotime("first day of July {$year}"));
							break;
						case 4:
          		$fdate = date('Y-m-d',strtotime("first day of October {$year}"));
							break;
					}
					$tdate = date('Y-m-d');
        	break;
      	case 'last_two_quarters':
      	case 'last 2\u20444':
      	case 'this quarter and last':
      		//this quarter and last
					$last_quarter = ceil(date('n') / 3)-1;
					$year=date('Y');
					if($last_quarter==0){
						$last_quarter=4;
						$year=$year-1;
					}
      		switch($last_quarter){
          	case 1:
          		$fdate = date('Y-m-d',strtotime("first day of January {$year}"));
							$tdate = date('Y-m-d',strtotime("last day of March {$year}"));
							break;
						case 2:
          		$fdate = date('Y-m-d',strtotime("first day of April {$year}"));
							$tdate = date('Y-m-d',strtotime("last day of June {$year}"));
							break;
						case 3:
          		$fdate = date('Y-m-d',strtotime("first day of July {$year}"));
							$tdate = date('Y-m-d',strtotime("last day of September {$year}"));
							break;
						case 4:
          		$fdate = date('Y-m-d',strtotime("first day of October {$year}"));
							$tdate = date('Y-m-d',strtotime("last day of December {$year}"));
							break;
					}
        	break;
      	case 'last_three_quarters':
      	case 'last 3\u20444':
      		//this quarter and last
					$last_quarter = ceil(date('n') / 3)-2;
					$year=date('Y');
					switch($last_quarter){
						case 0:
							$last_quarter=4;
							$year=$year-1;
							break;
						case -1:
							$last_quarter=3;
							$year=$year-1;
							break;
					}
      		switch($last_quarter){
          	case 1:
          		$fdate = date('Y-m-d',strtotime("first day of January {$year}"));
							$tdate = date('Y-m-d',strtotime("last day of March {$year}"));
							break;
						case 2:
          		$fdate = date('Y-m-d',strtotime("first day of April {$year}"));
							$tdate = date('Y-m-d',strtotime("last day of June {$year}"));
							break;
						case 3:
          		$fdate = date('Y-m-d',strtotime("first day of July {$year}"));
							$tdate = date('Y-m-d',strtotime("last day of September {$year}"));
							break;
						case 4:
          		$fdate = date('Y-m-d',strtotime("first day of October {$year}"));
							$tdate = date('Y-m-d',strtotime("last day of December {$year}"));
						break;
					}
        	break;
      	case 'last_year':
      		$t=strtotime('- 1 Years');
      		$year=date('Y',$t);
      		$fdate = date('Y-m-d',strtotime("first day of January {$year}"));
					$tdate = date('Y-m-d',strtotime("last day of December {$year}"));
					$speech[]="Last Year";
      		break;
      	case 'last_2years':
      	case 'last_2_years':
      	case 'last_two_years':
      		$t=strtotime('- 2 Years');
      		$year=date('Y',$t);
      		$fdate = date('Y-m-d',strtotime("first day of January {$year}"));
      		$t=strtotime('- 1 Years');
      		$year=date('Y',$t);
					$tdate = date('Y-m-d',strtotime("last day of December {$year}"));
					$speech[]="Last 2 Years";
      		break;
      	case 'last_3years':
      	case 'last_3_years':
      	case 'last_three_years':
					$t=strtotime('- 3 Years');
      		$year=date('Y',$t);
      		$fdate = date('Y-m-d',strtotime("first day of January {$year}"));
      		$t=strtotime('- 1 Years');
      		$year=date('Y',$t);
					$tdate = date('Y-m-d',strtotime("last day of December {$year}"));
					$speech[]="Last 3 Years";
        	break;
      	case 'last_4years':
      	case 'last_4_years':
      	case 'last_four_years':
					$t=strtotime('- 4 Years');
      		$year=date('Y',$t);
      		$fdate = date('Y-m-d',strtotime("first day of January {$year}"));
      		$t=strtotime('- 1 Years');
      		$year=date('Y',$t);
					$tdate = date('Y-m-d',strtotime("last day of December {$year}"));
					$speech[]="Last 4 years";
        	break;
			}
			$filters[]= " and date(cdate) between '{$fdate}' and '{$tdate}'";
		}
		else{
			//this year
			$fdate=date('Y-01-01');
			$rec['filters']['from_date']=$fdate;
			$filters[]= " and date(cdate) >= '{$rec['filters']['from_date']}'";
			$speech[]="Start Date: ".date('F jS Y',strtotime($fdate)).".";
			$speech[]="End Date: Today.";
		}
	}
	if(strlen($rec['filters']['city'])){
    	$filters[]= " and shipto_city='{$rec['filters']['city']}'";
    	$speech[]="Shipto City: {$rec['filters']['city']}.";
	}
	if(strlen($rec['filters']['state'])){
		$code=commonGetStateCode($rec['filters']['state']);
		$name=commonGetStateName($rec['filters']['state']);
		if(strlen($code)){
			$filters[]= " and shipto_state in ('{$rec['filters']['state']}','{$code}')";
		}
		elseif(strlen($name)){
			$filters[]= " and shipto_state in ('{$rec['filters']['state']}','{$name}')";
		}
		else{
			$filters[]= " and shipto_state in ('{$rec['filters']['state']}','{$code}')";
		}
    	$speech[]="Shipto State: {$rec['filters']['state']}.";
	}
	if(strlen($rec['filters']['country'])){
    	$filters[]= " and shipto_country='{$rec['filters']['country']}'";
    	$speech[]="Shipto Country: {$rec['filters']['country']}.";
	}
	if(strlen($rec['filters']['zip'])){
    	$filters[]= " and shipto_postcode='{$rec['filters']['zip']}'";
    	$speech[]="Shipto Postcode: {$rec['filters']['zip']}.";
	}
	elseif(strlen($rec['filters']['postcode'])){
    	$filters[]= " and shipto_postcode='{$rec['filters']['postcode']}'";
    	$speech[]="Shipto Postcode: {$rec['filters']['postcode']}.";
	}
	$rec['filterspeech']=implode("\r\n",$speech);
	//add client_id to the front of the filters array
	$rec['filterstr']="client_id={$client_id}";
	$rec['filterstr'].=implode(" ",$filters);
	//echo printValue($rec['filterstr']);exit;
	return $rec;
}
function commonGetSkillPlayerData($skillstr){
	$skills=preg_split('/\,/',$skillstr);
	$skillstr=implode("','",$skills);
$query=<<<ENDOFQUERY
SELECT
	count(distinct userid) yval
	,date(_cdate) xval
	,skill setval
FROM alexa_log
where skill in ('{$skillstr}')
and _cdate >= DATE_ADD(CURDATE(), INTERVAL -30 DAY)
and request != ''
GROUP BY
	skill
	,date(_cdate)
ORDER BY
	date(_cdate)
ENDOFQUERY;
$recs=getDBRecords(array('-query'=>$query));
	$hangman=hex2RGB('#cca9d0');
	$hangman[]=.5;
	$pico=hex2RGB('#f8991f');
	$pico[]=.5;
	$params=array(
		'hangman_backgroundColor'	=> "rgba({$hangman[0]},{$hangman[1]},{$hangman[2]},{$hangman[3]})",
		'pico_backgroundColor'	=> "rgba({$pico[0]},{$pico[1]},{$pico[2]},{$pico[3]})"
	);
	return buildChartJsData($recs,$params);
}
function commonGetSkillData($skill){
$query=<<<ENDOFQUERY
SELECT
	count(distinct userid) yval
	,count(distinct session_id) plays
	,date(_cdate) xval
	,'players' as setval
FROM alexa_log
where skill = '{$skill}'
and request != ''
and _cdate >= DATE_ADD(CURDATE(), INTERVAL -30 DAY)
and request != ''
GROUP BY
	date(_cdate)
ORDER BY
	date(_cdate)
ENDOFQUERY;
//return $query;
	$recs=getDBRecords(array('-query'=>$query));
	foreach($recs as $i=>$rec){
		$rec['setval']='plays';
		$rec['yval']=$rec['plays'];
		$recs[]=$rec;
	}
	$players=hex2RGB('#cca9d0');
	$players[]=.5;
	$plays=hex2RGB('#f8991f');
	$plays[]=.5;
	$params=array(
		'players_backgroundColor'	=> "rgba({$players[0]},{$players[1]},{$players[2]},{$players[3]})",
		'plays_backgroundColor'	=> "rgba({$plays[0]},{$plays[1]},{$plays[2]},{$plays[3]})"
	);
	return buildChartJsData($recs,$params);
}
function commonPageActive($name){
    	global $PAGE;
    	if($PAGE['name']==$name){return ' active';}
    	return '';
	}
function commonGreeting($type,$category){
	global $alexa;
	$opts=array(
		'-table'=>'common_greetings',
		'active'=>1,
		'type'=>$type,
		'category'=>$category,
		'-order'=>'RAND()',
		'-limit'=>1
	);
	//if it is in the morning and type=hello, then return morning greetings instead
	if($type=='hello'){
		//add rawOffset to timestamp to get the time in the users part of the world
		$h=date('G',$alexa['timestamp']+$alexa['profile']['rawOffset']);
		//alexaSetUserVar('debug',$h.printValue($alexa));
		if($h < 12){
	    	$opts['category']='morning';
		}
		elseif($h > 12 && $h < 17){
	    	$opts['category']='afternoon';
		}
		elseif($h >= 17){
	    	$opts['category']='evening';
		}
	}
	$recs=getDBRecords($opts);
	//echo printValue($opts).printValue($recs);
	return $recs[0]['greeting'];
}
function commonGetDictionaryWord($params){
	$params['-table']='dictionary';
	return getDBRecord($params);
}
function commonGetSoundfile($type){
	$sounds=array(
		'win'	=> array('cheer2','ywwowoo','applause8','applause4','clapping','crowdapplause','crowdcheering','happykids','kids_cheering','ta-da'),
		'lose'	=> array('aww','exhale','fail','gasp','moan','sad_trombone','sigh','toilet_flush','witchl'),
	);
	$i=array_rand($sounds[$type],1);
	return "https://www.skillsai.com/sounds/{$sounds[$type][$i]}.mp3";
}
function commonLoadReportData($xrec=array()){
	global $alexa;
	//if the report has a URL then it has been decoupled. Call it instead.
	if(1==1 || strlen($xrec['url'])){
		$report=commonBuildFilters($xrec);
		$opts=array();
		if(isset($xrec['return_type'])){
        	$report['return_type']=$xrec['return_type'];
		}
		elseif(isset($xrec['databack'])){
        	$report['return_type']=$xrec['databack'];
		}
		elseif(isset($_REQUEST['databack'])){
        	$report['return_type']=$_REQUEST['databack'];
		}
		$json=array(
			'report'=>strtolower($xrec['name']),
			'data'=>$report['return_type'],
			'where'=>$report['filterstr']
		);
		if(isset($alexa['swim'])){
			$json['alexa_log_id']=$alexa['logid'];
        	foreach($alexa['swim'] as $k=>$v){
            	$json[$k]=$v;
			}
			$alexa['swim']['filterstr']=$report['filterstr'];
		}
		elseif(isset($xrec['logid'])){
        	$json['alexa_log_id']=$xrec['logid'];
		}
		if(isset($alexa['user']['client_id'])){
			$json['client_id']=$alexa['user']['client_id'];
		}
		elseif(isset($xrec['client_id'])){
			$json['client_id']=$xrec['client_id'];
		}
		elseif($USER['client_id']){
			$json['client_id']=$USER['client_id'];
		}
		$json['db']=isDBStage()?'skillsai_stage':'skillsai_live';
		$jsonstr=json_encode($json);
		if(isset($alexa['logid'])){
			$ok=editDBRecord(array(
				'-table'=>'alexa_log',
				'-where'=>"_id={$alexa['logid']}",
				'jstr'	=> $jsonstr
			));
		}
		elseif(isset($xrec['logid'])){
			$ok=editDBRecord(array(
				'-table'=>'alexa_log',
				'-where'=>"_id={$xrec['logid']}",
				'jstr'	=> $jsonstr
			));
		}
		$url="http://localhost/reports";
		//echo $url.printValue($json);exit;
    	$post=postJSON($url,$jsonstr,array('-method'=>'POST','-contenttype'=>'Content-Type: application/json','-debug'=>1,'-port'=>5263));
    	//alexaSetUserVar('json',$jsonstr);
    	return $post['body'];
	}
	global $rec;
	$rec=$xrec;
	if(isset($xrec['return_type'])){
        $rec['return_type']=$xrec['return_type'];
	}
	elseif(isset($xrec['databack'])){
        $rec['return_type']=$xrec['databack'];
	}
	elseif(isset($_REQUEST['databack'])){
        $rec['return_type']=$_REQUEST['databack'];
	}
	$evalstr='<'.'?global $'.'rec;?'.'>'."\n".$rec['data'];
	//alexaSetUserVar('debug',$evalstr);
	return evalPHP($evalstr);
}

function commonMakeSecure(){
	//global $PAGE;
	if(!isSecure()){
		if(isDBStage()){
			header("Location: https://stage.skillsai.com{$_SERVER['REQUEST_URI']}");
		}
		else{
			header("Location: https://www.skillsai.com{$_SERVER['REQUEST_URI']}");
		}
			echo "redirecting to secure mode";
			exit;
	}
}
function commonMakeInSecure(){
	//global $PAGE;
	if(isAjax()){return;}
	if(isSecure()){
		if(isDBStage()){
			header("Location: http://stage.skillsai.com{$_SERVER['REQUEST_URI']}");
		}
		else{
			header("Location: http://www.skillsai.com{$_SERVER['REQUEST_URI']}");
		}
			echo "redirecting to non-secure mode";
			exit;
	}
}
function commonAdd2Cart($sku,$qty){
	$rec=getDBRecord(array(
		'-table'		=> 'orders_items',
		'ordernumber'	=> $_SERVER['GUID'],
		'status'		=> 'cart',
		'sku'			=> $sku
	));
	if(is_array($rec)){
    	$ok=editDBRecord(array(
			'-table'		=> 'orders_items',
			'-where'		=> "_id={$rec['_id']}",
			'quantity'		=> $rec['quantity']+$qty
		));
	}
	else{
		$rec=getDBRecord(array(
			'-table'		=> 'products',
			'sku'			=> $sku
		));
    	$ok=addDBRecord(array(
			'-table'		=> 'orders_items',
			'quantity'		=> $qty,
			'ordernumber'	=> $_SERVER['GUID'],
			'status'		=> 'cart',
			'sku'			=> $sku,
			'price'			=> $rec['price_retail'],
			'weight'		=> $rec['weight'],
			'name'			=> $rec['name'],
			'description'	=> "{$sku} - {$rec['name']}"
		));
	}
	return commonGetCart();
}
function commonApplyCoupon($coupon){
	unset($_REQUEST['coupon_error']);
	$ocr=editDBRecord(array(
		'-table'	=> "orders_coupons",
		'-where'	=> "ordernumber='{$_SERVER['GUID']}'",
		'active'	=> 0
	));
	$recopts=array(
		'-table'=>"coupon_codes",
		'coupon_code'	=> $coupon,
		'active'=>1
	);
	$rec=getDBRecord($recopts);
	#
	if(!is_array($rec)){
		$_REQUEST['coupon_error']="No coupon found that matches";
		return globalGetCart();
	}
	//does the coupon per_email based?
	if($rec['per_email']==1){
		if(!strlen($_REQUEST['email']) || !isEmail($_REQUEST['email'])){
			$_REQUEST['coupon_error']="That coupon requires you to enter your email address in the Credit Card Billing section below.  Enter your email and then re-apply the coupon.";
			return commonGetCart();
		}
		//has this email already used this coupon in a previous order
		$opts=array(
			'-table'=>"orders",
			'billtoemail'=>$_REQUEST['email'],
			'coupon'	=> $coupon
		);
		if(getDBCount($opts)){
        	$_REQUEST['coupon_error']="That coupon is a one-time use coupon.  You have already used it in a previous order.";
			return commonGetCart();
		}
	}
	//check for valid date range
	if(strlen($rec['date_end']) && strtotime($rec['date_end'])<time() && date('Ymd',strtotime($rec['date_end'])) != date('Ymd')){
		$_REQUEST['coupon_error']="That coupon has expired";
		return commonGetCart();
	}
	if(strlen($rec['date_begin']) && strtotime($rec['date_begin'])>time()){
		$_REQUEST['coupon_error']="That coupon is not valid";
		return commonGetCart();
	}
	//times allowed
	if(isNum($rec['times_allowed']) && $rec['times_allowed'] > 0 && $rec['times_allowed'] <= $rec['times_used']){
    	$_REQUEST['coupon_error']="That limited use coupon is no longer valid ({$rec['times_allowed']},{$rec['times_used']})";
		return commonGetCart();
	}
	unset($_REQUEST['coupon']);
	//add coupon to orders_coupons table
	$ok=addDBRecord(array(
		'-table'	=> "orders_coupons",
		'ordernumber'=> $_SERVER['GUID'],
		'coupon_code'=> $rec['coupon_code'],
		'coupon_name'=> $rec['coupon_name'],
		'active'	=> 1
	));

	return commonGetCart();
}
function commonApplyGiftcard($code){
	unset($_SESSION['cart_giftcard']);
	$giftcard=getDBRecord(array(
		'-table'=>"giftcards",
		'code'	=> $code,
		'active'=>1
	));
	if(!is_array($giftcard)){
		$_REQUEST['giftcard_error']="No giftcard found that matches {$code}";
		return commonGetCart();
	}
	$giftcard_cart=getDBRecord(array(
		'-table'=>"giftcards_cart",
		'code'	=> $code
	));
	if(is_array($giftcard_cart)){
		$_REQUEST['giftcard_error']="This giftcard has already been applied {$code}";
		return commonGetCart();
	}
	$id=addDBRecord(array(
		'-table'=>"giftcards_cart",
		'code'	=> $code,
		'ordernumber'=>$_SERVER['GUID']
	));
	return commonGetCart();
}

function commonCartCleanup(){
	$query="update orders_items set status='expired' where status = 'cart' and _cdate < (NOW() - INTERVAL 12 HOUR)";
	$ok=executeSQL($query);
	return;
}
function commonConvertCart2Order($orderid,$params=array(),$response=array()){
	global $PAGE;
	$cart=commonGetCart();
	$order_items=getDBRecords(array(
		'-table'=>"orders_items",
		'-where'=>"ordernumber='{$_SERVER['GUID']}' and status = 'cart'",
		'-index'=>'_id'
	));
	foreach ($cart['items'] as $key => $value) {
		if($value['price']!=$order_items[$value['_id']]['price']){
			$ok=editDBRecord(array(
				'-table'	=> 'orders_items',
				'-where'	=> "_id = {$value['_id']}",
				'price'		=> $value['price']
			));
		}
	}
	unset($_SESSION['shiptoemail']);
	unset($_SESSION['shiptostate']);
	if(!isset($cart['items'])){return 'ERROR: INVALID CART';}
	if(!isset($response['authorization_code'])){return 'ERROR: INVALID RESPONSE';}
	//orders
	$orderfields=getDBFields('orders');
	$params['-table']="orders";
	$params['-where']="_id={$orderid}";
	$params['cc_num']=str_replace(' ','',$params['cc_num']);
	$cclen=strlen($params['cc_num'])-4;
	$regex='/^[0-9]{'."{$cclen},{$cclen}".'}/';
	$params['cc_num']=preg_replace($regex,'************',$params['cc_num']);
	//check for coupon
	if(isset($cart['totals']['coupon'])){
    	$params['coupon']=$cart['totals']['coupon'];
    	unset($_SESSION['cart_coupon']);
	}
	foreach($orderfields as $field){
    	if(isset($cart['totals'][$field]) && !isset($params[$field])){
        	$params[$field]=$cart['totals'][$field];
		}
	}
	//ship_vendor (will call)
	if(isset($cart['totals']['ship_vendor'])){
    	$params['ship_vendor']=$cart['totals']['ship_vendor'];
	}
	//order total, ship_cost, items_total, items_quantity
    $params['order_total']=$cart['totals']['total'];
    $params['items_total']=$cart['totals']['subtotal'];
    $params['items_quantity']=$cart['totals']['quantity'];
    //authnet fields:
    $params['cc_authcode']=$response['authorization_code'];
    $params['cc_result']=$response['response_reason_text'];
    $params['payment_type']=$response['card_type'];
    //Status
    $params['status']='ready';
    //Session_id
    $params['session']=session_id();
	//prefix
	$year=date('y');
    $prefix="PS{$year}";
	$params['ordernumber']=$prefix.$orderid;
	$params['orderdate']=date("Y-m-d H:i:s");
	$params['source']='purestill';
	$params['warehouse']='USA1';

    //return printValue($params).printValue($response);
	$ok=editDBRecord($params);
	if(!isNum($ok)){return "ERROR: {$ok}";}
	//update affiliate database
	//echo printValue($_SESSION);exit;
	//update orders_coupons with the new ordernumber
	if(isset($cart['totals']['coupon'])){
    	$ok=editDBRecord(array(
			'-table'	=> "orders_coupons",
			'-where'	=> "active=1 and ordernumber='{$_SERVER['GUID']}'",
			'ordernumber'=> $params['ordernumber']
		));
	}
	//charge giftcards associated with this order

	//add items to this order
	$ids=array();
	$code_qty=0;
	foreach($cart['items'] as $item){
		//Check for e-ticket and e-giftcards and fulfill them immediately
		if(stringBeginsWith($item['itemid'],'eGiftcard')){
        	$egiftcards[]=$item;
        	$item['type']='giftcard';
        	$e_ids[]=$item['_id'];
        	$code_qty+=$item['quantity'];
        	continue;
		}
		$item['ordernumber']=$params['ordernumber'];
		$ids[]=$item['_id'];
	}
	if(count($ids)){
		$idstr=implode(',',$ids);
		$query="update orders_items set ordernumber='{$params['ordernumber']}',status='ready' where _id in ({$idstr})";

		$ok=executeSQL($query);
		//echo $ok.$query;exit;
	}
	//add purchases using giftcards and update balance
	if(is_array($cart['giftcards'])){
    	foreach($cart['giftcards'] as $giftcard){
			//remove from giftcards_cart
			$query="DELETE FROM giftcards_cart WHERE code='{$giftcard['code']}'";
			$ok=executeSQL($query);
			//add to giftcard_purchases
			$ok=addDBRecord(array(
				'-table'	=> 'giftcards_purchases',
				'location'	=> 'Online Store',
				'code'		=> $giftcard['code'],
				'amount'	=> $giftcard['used'],
				'ordernumber'=>$params['ordernumber']
			));
			//update balance in giftcards an incriment times used
			$query="UPDATE giftcards SET times_used=times_used+1,balance={$giftcard['balance']} WHERE active=1 AND code='{$giftcard['code']}'";
			$ok=executeSQL($query);
		}
	}
	return $params['ordernumber'];
}
function commonGetCart(){
	global $PAGE;
	commonCartCleanup();
	$cart=array(
		'country_code'=>strtoupper($_SESSION['country_code']),
		'totals'=>array(
			'shipping'	=> 0,
			'tax'		=> 0,
			'fee'		=> 0,
			'discount'	=> 0,
			'total'		=> 0,
			'quantity'	=> 0,
			'weight'	=> 0
		)
	);
	//items
	$recs=getDBRecords(array(
		'-table'=>"orders_items",
		'-where'=>"ordernumber='{$_SERVER['GUID']}' and status = 'cart'",
	));
	if(!is_array($recs)){return $cart;}
	//weight
	foreach($recs as $rec){
    	$cart['totals']['weight']+=$rec['weight'];
	}

	switch($cart['country_code']){
		//if in Canada - determine shipping from canadapost
    	case 'CA':
    		//$cart['CA_weight']=$cart['weight'];
    		$_REQUEST['shiptocountry']=$cart['order']['shiptocountry']='CA';
    		$cart['totals']['shipping_description']='Shipping & Handling (Canada Post)';
	    	$cart['totals']['shipping_description_short']='Shipping (CA):';
    		if(stringContains($_SERVER['REQUEST_URI'],'checkout') && $cart['weight'] > 0){
	    		if(strlen($cart['order']['shiptozipcode'])){
	    			$_SESSION['shipping']=$cart['totals']['shipping']=storeCanadaPost($cart['order']['shiptozipcode'],$cart['weight']);
				}
				elseif(strlen($_REQUEST['shiptozipcode'])){
	    			$_SESSION['shipping']=$cart['totals']['shipping']=storeCanadaPost($_REQUEST['shiptozipcode'],$cart['weight']);
				}
				elseif(strlen($_SESSION['shiptozipcode'])){
	    			$_SESSION['shipping']=$cart['totals']['shipping']=storeCanadaPost($_SESSION['shiptozipcode'],$cart['weight']);
				}
				else{
                	$cart['totals']['shipping']='TBD';
				}
			}
			elseif($_SESSION['shipping']){
            	$cart['totals']['shipping']=$_SESSION['shipping'];
			}
			else{
            	$cart['totals']['shipping']='TBD';
			}
			//echo printValue($cart);
    	break;
    	case 'UK':
    	case 'GB':
    		$cart['totals']['shipping']=0;
    		$cart['totals']['shipping_description']='Shipping & Handling';
			$cart['totals']['shipping_description_short']='Shipping:';
    		$_REQUEST['shiptocountry']=$cart['order']['shiptocountry']='GB';
    	break;
    	default:
    		$cart['totals']['shipping_title']='Shipping & Handling';
    		//$cart['debug'].='B';
    		$weight=ceil($cart['totals']['weight']/16);
    		//USPS will not ship over 70 lbs.
    		if($weight > 70){$weight=70;}
    		if($_REQUEST['shiptozipcode']){
			}
			else{
				$cart['totals']['shipping']='TBD';
			}

    		$_REQUEST['shiptocountry']=$cart['order']['shiptocountry']='US';
    	break;
	}
	$cart['items']=$recs;
	foreach($cart['items'] as $id=>$item){
		$cart['totals'][$item['sku']]+=$item['quantity'];
		//calculate row total
		$cart['items'][$id]['subtotal']=round(($item['quantity']*$item['price']),2);
		//calculate totals
		$cart['totals']['quantity']+=$item['quantity'];
		$cart['totals']['subtotal']+=$cart['items'][$id]['subtotal'];
	}
	//order
	$rec=getDBRecord(array(
		'-table'		=>"orders",
		'ordernumber'	=> $_SERVER['GUID']
	));

	if(is_array($rec)){
    	$cart['order']=$rec;
	}
	else{
    	$fields=getDBFields('orders');
    	foreach($fields as $field){
        	if(strlen($_REQUEST[$field])){
            	$cart['order'][$field]=$_REQUEST[$field];
			}
		}
	}
	$ocr=getDBRecord(array(
		'-table'		=> "orders_coupons",
		'ordernumber'	=> $_SERVER['GUID'],
		'active'		=> 1
	));
	unset($coupon);
	if(isset($ocr['coupon_code'])){
		$copts=array(
			'-table'	=> "coupon_codes",
			'coupon_code'=> $ocr['coupon_code']
		);
		switch(strtoupper($_SESSION['country_code'])){
	    	case 'CA':
	    		$copts['country_code']='CA';
	    	break;
	    	case 'GB':
	    		$copts['country_code']='GB';
	    	break;
	    	default:
	    		$copts['country_code']='US';
	    	break;
		}
		$coupon=getDBRecord($copts);
	}
	//coupon?
	if(isset($coupon['coupon_code'])){
		//is this coupon for a specific base_sku
		if(strlen($coupon['base_sku']) && !isset($cart['base_skus'][$coupon['base_sku']])){
			$_REQUEST['coupon_error']="Coupon code does not apply to items in your cart.";
		}
		if(strlen($coupon['series']) && strtolower($PAGE['name']) != strtolower($coupon['series'])){
			$_REQUEST['coupon_error']="Coupon code does not apply to this product line.";
		}
		//is this coupon for a specific country_code
		if(strlen($coupon['country_code']) && strtolower($coupon['country_code']) != strtolower($_SESSION['country_code'])){
			$_REQUEST['coupon_error']="Coupon code does not apply in your country.";
		}
		//echo printValue($cart).printValue($coupon);exit;
		//is this coupon for a specific category
		if(strlen($coupon['category'])){
			$found=0;
			foreach($cart['items'] as $item){
				if($item['category']==$coupon['category']){$found++;break;}
			}
			if($found==0){
				$_REQUEST['coupon_error']="Coupon code does not apply to items in your cart.";
			}
		}
		if(!isset($_REQUEST['coupon_error'])){
	        //coupon is valid.  Apply it.
        	$cart['totals']['coupon']=$coupon['coupon_code'];
			if(isNum($coupon['pcnt_off']) && $coupon['pcnt_off'] > 0){
				/* Percent Off */
				$discount=round($coupon['pcnt_off']/100,2);
				$off=0;
				if($coupon['max_qty'] > 0 && strlen($coupon['base_sku'])){
					$max_cnt=0;
                    	foreach($cart['items'] as $item){
						for($mx=0;$mx<$item['quantity'];$mx++){
							if($max_cnt < $coupon['max_qty'] && $item['base_sku']==$coupon['base_sku']){
								$max_cnt++;
								$off+=round($item['price']*$discount,2);
							}
						}
					}
					$cart['totals']['discount']=$off;
				}
				else{

            		$cart['totals']['discount']=round((($cart['totals']['subtotal']+$cart['totals']['fee'])*$discount),2);
				}
			}
			elseif(isNum($coupon['amt_off']) && $coupon['amt_off'] > 0){
				/* Amount Off */
				$off=0;
				if($coupon['max_qty'] > 0 && strlen($coupon['base_sku'])){
					$max_cnt=0;
                    	foreach($cart['items'] as $item){
						for($mx=0;$mx<$item['quantity'];$mx++){
							if($max_cnt < $coupon['max_qty'] && $item['base_sku']==$coupon['base_sku']){
                                $max_cnt++;
								$off+=round($coupon['amt_off'],2);
							}
						}
					}
					$cart['totals']['discount']=$off;
				}
				else{
            		$cart['totals']['discount']=round($coupon['amt_off'],2);
				}
			}
			elseif(isNum($coupon['amt_off']) && $coupon['amt_off'] == 0){
				/* Amount Off */
				//$discount=$cart['totals']['subtotal']*
            	$cart['totals']['discount']=0;
			}
			elseif(strlen($coupon['buyxy']) && strlen($coupon['base_sku']) && isset($cart['base_skus'][$coupon['base_sku']])){
				/* Buy X get Y free - must have a sku for it to be valid */
				list($x,$y)=preg_split('/\,/',$coupon['buyxy'],2);
				$discount=0;
				foreach($cart['items'] as $i=>$item){
					if($item['base_sku'] != $coupon['base_sku']){continue;}
					if($item['quantity'] < $x){continue;}
					$price=$item['price'];
					$qty=$item['quantity'];
					while($qty > $x){
                    	$discount += $price;
                    	$qty=$qty-$x-$y;
					}
				}
            	$cart['totals']['discount']=round($discount,2);
			}
			elseif(strlen($coupon['amount']) && strlen($coupon['base_sku']) && isset($cart['base_skus'][$coupon['base_sku']])){
				/*
					Amount - $25 on the 25th must have a sku for it to be valid
					Buy 5 tickets for $100 bucks
				*/
				//check for min_qty
     				if(isNum($coupon['min_qty']) && $coupon['min_qty']>0 && $cart['base_skus'][$coupon['base_sku']] <$coupon['min_qty']){
                    	//
				}
				else{
					foreach($cart['items'] as $i=>$item){
						if($item['base_sku'] != $coupon['base_sku']){continue;}
						if(strlen($coupon['itemid']) && strtolower($coupon['itemid']) != strtolower($item['itemid'])){continue;}
						$cart['items'][$i]['price']=$coupon['amount'];
						$cart['items'][$i]['description'].= " ***<b>{$coupon['code_code']}</b> - {$coupon['coupon_name']} ***";
					}
				}
				$cart['totals']['quantity']=0;
				$cart['totals']['subtotal']=0;
				foreach($cart['items'] as $id=>$item){
					$cart['totals']['quantity']+=$item['quantity'];
					$cart['totals']['subtotal']+=($item['quantity']*$item['price']);
				}
			}
			elseif(strlen($coupon['spendxy'])){
				/* Spend $x and get Y free */
				list($spend,$sku)=preg_split('/\,/',$coupon['spendxy'],2);
				$amount=0;
				foreach($cart['items'] as $i=>$item){
					if($item['base_sku']=='TICKET'){continue;}
					// if($item['base_sku']==$sku){continue;}
					if($item['base_sku']=='GIFTCARD'){continue;}
					$amount+=round(($item['price']*$item['quantity']),2);
				}
				if($amount < $spend){
					$_REQUEST['coupon_error']="You must spend at least $ {$spend} for this coupon to apply.";
				}
				else{
					$discount=0;
                    	$freeqty=floor($amount/$spend);
                    	foreach($cart['items'] as $i=>$item){
						if($item['base_sku']==$sku || $item['itemid']==$sku){
							$z=$item['quantity'];
							while($z > 0 && $freeqty > 0){
								$discount+=$item['price'];
								$freeqty--;
								$z--;
							}
						}
					}
					$cart['totals']['discount']=round($discount,2);
				}
			}
			elseif(strlen($coupon['skuforsku'])){
				/* Buy sku() and get sku() free */
				list($buy,$free)=preg_split('/\,/',$coupon['skuforsku'],2);
				foreach($cart['items'] as $i=>$item){
					if($item['base_sku']==$buy){$buyqty+=$item['quantity'];continue;}
					if($item['base_sku']==$free){$freeqty+=$item['quantity'];$freeprice[]=$item['price'];continue;}
				}
				while ($buyqty && $freeqty) {
					// use min of $freeprice array to make sure that kids tickets if in cart.
				    $discount+=round(min($freeprice),2);
				    $buyqty--;
				    $freeqty--;
				}
				$cart['totals']['discount']=round($discount,2);
			}
			//free shipping can be combined with others
			if($coupon['ship_free']==1){
				/* Free Shipping */
            	$cart['totals']['shipping']=0;
			}
			//free shipping can be combined with others
			if($coupon['employee_code']==1){
				/* Free Shipping */
            	$cart['totals']['ship_vendor']='WILLCALL';
			}
		}

	}

	$cart['totals']['total']=$cart['totals']['subtotal']+$cart['totals']['fee']+$cart['totals']['tax'];
	if(isNum($cart['totals']['shipping'])){
    	$cart['totals']['total']+=$cart['totals']['shipping'];
	}
	if($cart['totals']['discount'] > 0){
		$cart['totals']['discount_description'] = ($coupon['coupon_name']?$coupon['coupon_name']:$cart['totals']['discount_description']);
		//$cart['totals']['discount_description']=$coupon['description'];
    	if($cart['totals']['discount'] > $cart['totals']['total']){$cart['totals']['discount'] = $cart['totals']['total'];}
    	$cart['totals']['total'] = $cart['totals']['total'] - $cart['totals']['discount'];
	}
	//tax
	$taxable=$cart['totals']['subtotal']-$cart['totals']['discount'];
	if($taxable > 0){
		switch($cart['country_code']){
	    	case 'CA':
	    		//HST Tax for CA
				$cart['totals']['tax']=($taxable*.13);
        		$cart['totals']['tax_description']='Harmonized Sales Tax (HST)';
			break;
			case 'GB':
	    		//HST Tax for CA
				$cart['totals']['tax']=($taxable*.18);
        		$cart['totals']['tax_description']='VAT/Sales Tax';
			break;
			default:
				if(preg_match('/^UT|Utah$/i',$cart['order']['shiptostate'])){
					$cart['totals']['tax']=($taxable*.0685);
					$cart['totals']['tax_title']='Taxes';
					$cart['totals']['tax_description']='Utah Sales Tax';
				}
				elseif(preg_match('/^UT|Utah$/i',$_SESSION['shiptostate'])){
					$cart['totals']['tax']=($taxable*.0685);
					$cart['totals']['tax_title']='Taxes';
					$cart['totals']['tax_description']='Utah Sales Tax';
				}
			break;
		}
		$cart['totals']['total']+=$cart['totals']['tax'];
	}
	//echo printValue($cart);
	return $cart;
}
?>
