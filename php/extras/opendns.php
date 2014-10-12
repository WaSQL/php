<?php
/*
	Functions to get stats from an OpenDNS.com account

	inspired by OpenDNS Stats Fetcher - https://github.com/rcrowley/opendns-fetchstats
	Blocked Domains  - this is one we use because it returns actual data
		https://dashboard.opendns.com/stats/org-5127/topdomains/2014-02-15/blocked.csv
	Total Requests - not really any data returned
		https://dashboard.opendns.com/stats/org-5127/totalrequests/2014-02-15.csv
	Unique Domains - not really any data returned
		https://dashboard.opendns.com/stats/org-5127/uniquedomains/2014-02-15.csv
	Domains - this is one we use because it returns actual data
		https://dashboard.opendns.com/stats/org-5127/topdomains/2014-02-15.csv
		https://dashboard.opendns.com/stats/org-5127/topdomains/2014-02-01to2014-02-15.csv

*/

//---------- begin function
/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function opendnsGetBlockedDomains($params=array()){
	if(!isset($params['-date'])){$params['-date']=array(date('Y-m-d'));}
	if(!is_array($params['-date'])){$params['-date']=array($params['-date']);}
	$daterange=implode('to',$params['-date']);
	$url="https://dashboard.opendns.com";
	$progpath=dirname(__FILE__);
	$cookiefile="{$progpath}/opendns_{$_SERVER['GUID']}.txt";
	unlink($cookiefile);
	//first get the formtoken from the form itself
	$post=postURL("{$url}/signin",array(
		'-method'	=> "GET",
		'-ssl'		=> false,
		'-cookiefile'=>$cookiefile,
	));
	//$post['body']=preg_replace('/[\r\n]+/',' ',$post['body']);
	if(!preg_match('/name="formtoken" value="([0-9a-fA-F]+)"/s',$post['body'],$m)){return "No formtoken";}
	$formtoken=$m[1];
	//log in
	$post=postURL("{$url}/signin",array(
		'formtoken'=>$formtoken,
		'username'	=> $params['-user'],
		'password'	=> $params['-pass'],
		'-ssl'		=> false,
		'-cookiefile'=>$cookiefile,
		'sign_in_submit'=>"foo"
	));
	//if the location header was set to dashboard we are in.
	if(!stringContains($post['headers']['location'],'/dashboard')){return "Auth Failed";}
	//now get the network_id  from the location header
	$post=postURL("{$url}/stats/all/start",array(
		'-method'	=> "GET",
		'-ssl'		=> false,
		'-cookiefile'=>$cookiefile
	));
	if(!preg_match('/\/stats\/(.+?)\/start/',$post['headers']['location'],$m)){return "No network_id";}
	$network_id=$m[1];
	//now get the blocked domains csv file
	$post=postURL("{$url}/stats/{$network_id}/topdomains/{$daterange}/blocked.csv",array(
		'-method'	=> "GET",
		'-ssl'		=> false,
		'-cookiefile'=>$cookiefile,
	));
	$csvfile="{$progpath}/opendns_{$_SERVER['GUID']}.csv";
	$ok=setFileContents($csvfile,$post['body']);
	$csv=getCSVFileContents($csvfile);
	unlink($csvfile);
	if(!is_array($csv['items'])){return null;}
	return $csv['items'];
}
//---------- begin function opendnsGetDomains--------------------
/**
* @describe returns domains list from an opendns account based on date range
* @param params array
*	-user string - OpenDNS username - probably an email
*	-pass string - OpenDNS password
*	[-date] mixed - Date in YYYY-MM-DD format or an array of dates for from and to dates. Defaults to current date
*	[-filter] string - blocked|unique - blocked will only return blocked domains, unique will group subdomins together
* @return array - array of domains with the following keys:
*	id - the rank
*	domain - unique domain if unique filter, or subdomain of site
*	visits - number of visits to this domain in the specified time period
*	[categories] string - comma separated list of categories. blank if no categories
*	[blocks] string - comma separated list of reasons it was blocked. Blank if not blocked
* @usage $domains=opendnsGetDomains(array('-user'=>'my@email.com','-pass'=>'mypass'));
*/
function opendnsGetDomains($params=array()){
	if(!isset($params['-date'])){$params['-date']=array(date('Y-m-d'));}
	if(!is_array($params['-date'])){$params['-date']=array($params['-date']);}
	if(count($params['-date']) > 2){return 'opendnsGetDomains Error: Only two dates are allowed';}
	$daterange=implode('to',$params['-date']);
	$url="https://login.opendns.com";
	$progpath=dirname(__FILE__);
	$cookiefile="{$progpath}/opendns_{$_SERVER['GUID']}.txt";
	unlink($cookiefile);
	//first get the formtoken from the form itself
	$post=postURL("{$url}",array(
		'-method'	=> "GET",
		'return_to'	=> 'https://dashboard.opendns.com/',
		'-ssl'		=> false,
		'-follow'	=> 1,
		'-cookiefile'=>$cookiefile,
	));
	//echo "opendnsGetDomains:".$formtoken.printValue($post['body']);exit;
	//$post['body']=preg_replace('/[\r\n]+/',' ',$post['body']);
	if(!preg_match('/name="formtoken" value="([0-9a-fA-F]+)"/s',$post['body'],$m)){return "No formtoken";}
	$formtoken=$m[1];
	//echo "opendnsGetDomains:".$formtoken.printValue($post[' o);exit;
	//log in
	$post=postURL("{$url}",array(
		'formtoken'=>$formtoken,
		'username'	=> $params['-user'],
		'password'	=> $params['-pass'],
		'-ssl'		=> false,
		'-follow'	=> 1,
		'return_to'	=> 'https://dashboard.opendns.com/',
		'-cookiefile'=>$cookiefile,
		'sign-in'=>"Sign in"
	));
	//echo printValue($post);exit;
	//if the location header was set to dashboard we are in.
	if(!stringContains($post['headers']['location'],'/dashboard')){return "Auth Failed";}
	$url="https://dashboard.opendns.com";
	//now get the network_id  from the location header
	$post=postURL("{$url}/stats/all/start",array(
		'-method'	=> "GET",
		'-ssl'		=> false,
		'-cookiefile'=>$cookiefile
	));
	unset($post['body']);
	if(!preg_match('/\/stats\/(.+?)\/start/',$post['headers']['location'],$m)){return "No network_id";}
	$network_id=$m[1];
	//now get the items

	$items=array();
	if(isset($params['-filter']) && strtolower($params['-filter'])=='blocked'){
    	$items=opendnsGetBlockedDomains($params);
	}
	else{
		$page=1;
		while($page < 5){
			$post=postURL("{$url}/stats/{$network_id}/topdomains/{$daterange}/page{$page}.csv",array(
				'-method'	=> "GET",
				'-ssl'		=> false,
				'-cookiefile'=>$cookiefile,
			));
			if(!isset($post['headers']['content-type']) || $post['headers']['content-type']!='text/csv'){
				break;
			}
			$csvfile="{$progpath}/opendns_{$_SERVER['GUID']}.csv";
			$ok=setFileContents($csvfile,$post['body']);
			$csv=getCSVFileContents($csvfile);
			unlink($csvfile);
			if(!is_array($csv['items']) || count($csv['items'])==0){break;}
			$items=array_merge($items,$csv['items']);
			$page+=1;
		}
	}
	unlink($cookiefile);
	//echo printValue($items);exit;
	//domain visits blocks categories
	$domains=array();
	foreach($items as $item){
		$root=getUniqueHost($item['domain']);
    	$rec=array(
    		'domain'=> $item['domain'],
			'visits'	=> $item['total'],
			'domain_root'=> $root,
			'_id'	=> $item['rank']
		);
		foreach($item as $key=>$val){
        	if(in_array($key,array('domain','rank','total'))){continue;}
        	switch(strtolower($key)){
            	case 'blacklisted':
            	case 'blocked_by_category':
            	case 'blocked_as_botnet':
            	case 'blocked_as_malware':
            	case 'blocked_as_phishing':
					if(isNum($val) && $val > 0){
						$rec['blocks_arr'][]=$key;
					}
					break;
				case 'resolved_by_smartcache':
				break;
				default:
					if($val==1){
						$rec['categories_arr'][]=$key;
					}
				break;
			}
		}
		switch(strtolower($params['-filter'])){
        	case 'blocked':
        		if(isset($rec['blocks_arr']) && count($rec['blocks_arr'])){
                	$domains[]=$rec;
				}
        	break;
        	case 'unique':
        		if(!isset($domains[$root])){
					$rec['domain']=$root;
					$domains[$root]=$rec;
					}
        		else{
                	$domains[$root]['visits']+=$rec['visits'];
                	if(isset($rec['categories_arr'])){
                    	foreach($rec['categories_arr'] as $cat){
						 if(!in_array($cat,$domains[$root]['categories_arr'])){
                         	$domains[$root]['categories_arr'][]=$cat;
						 }
						}
					}
					if(isset($rec['blocks_arr'])){
                    	foreach($rec['blocks_arr'] as $cat){
						 if(!in_array($cat,$domains[$root]['blocks_arr'])){
                         	$domains[$root]['blocks_arr'][]=$cat;
						 }
						}
					}
				}
			break;
        	default:
        		$domains[]=$rec;
        	break;
		}
	}
	$xdomains=array();
	foreach($domains as $rec){
		if(isset($rec['categories_arr'])){
        	$rec['categories']=implode(', ',$rec['categories_arr']);
        	//unset($rec['categories_arr']);
		}
		else{
        	$rec['categories']='';
		}
		if(isset($rec['blocks_arr'])){
			$rec['blocks']=implode(', ',$rec['blocks_arr']);
			//unset($rec['blocks_arr']);
		}
		else{
        	$rec['blocks']='';
		}
		unset($rec['blocked']);
		//unset($rec['domain_root']);
		$xdomains[]=$rec;
	}
	return $xdomains;
}
//---------- begin function opendnsIsValidAuth--------------------
/**
* @describe returns true if the user and password authenticate successfully on openDNS.com
* @param user string - OpenDNS username - probably an email
* @param pass string - OpenDNS password
* @return boolean -true if the user and password authenticate successfully on openDNS.com
* @usage if(opendnsIsValidAuth($user,$pass)){...}
*/
function opendnsIsValidAuth($user,$pass){
	$url="https://dashboard.opendns.com";
	$progpath=dirname(__FILE__);
	$cookiefile="{$progpath}/opendns_{$_SERVER['GUID']}.txt";
	unlink($cookiefile);
	//first get the formtoken from the form itself
	$post=postURL("{$url}/signin",array(
		'-method'	=> "GET",
		'-ssl'		=> false,
		'-cookiefile'=>$cookiefile,
	));
	if(!preg_match('/name="formtoken" value="([0-9a-fA-F]+)"/s',$post['body'],$m)){return "No formtoken";}
	$formtoken=$m[1];
	//log in
	$post=postURL("{$url}/signin",array(
		'formtoken'=>$formtoken,
		'username'	=> $user,
		'password'	=> $pass,
		'-ssl'		=> false,
		'-cookiefile'=>$cookiefile,
		'sign_in_submit'=>"foo"
	));
	unlink($cookiefile);
	//if the location header was set to dashboard we are in.
	if(!stringContains($post['headers']['location'],'/dashboard')){return false;}
	return true;
}
?>

