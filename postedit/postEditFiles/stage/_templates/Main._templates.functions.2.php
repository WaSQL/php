<?php
loadExtrasCss('bootstrap');
loadExtrasJs('imagemap');
loadDBFunctions('functions_common');
global $PAGE;
//make secure
if(!isSSL()){
	header("Location: https://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}");
	echo "redirecting to secure mode";
	exit;
}
function templateActiveMenu($name){
	global $PAGE;
	if($PAGE['name']==$name){return ' active';}
	return '';
}
function templateCounter(){
$query=<<<ENDOFQUERY
SELECT
	count(*) cnt 
FROM alexa_log
WHERE 
	skill in ('salestalk_alexa','salestalk_googlehome')
	and profile_id not in (select profile_id from _users where client_id=2)
ENDOFQUERY;
	$rec=getDBRecord($query);
	return $rec['cnt'];
}
function templateMetaEtag(){
	global $PAGE;
	global $TEMPLATE;
	//get latest edit date and latest create date and sha1 encode them
	return sha1($PAGE['_cdate'].$PAGE['_edate'].$TEMPLATE['_cdate'].$TEMPLATE['_edate']);
}
function templateMetaTitle(){
	global $PAGE;
	if(strtolower($PAGE['name'])=='soundbytes' && isset($_REQUEST['passthru'][0])){
    	$rec=getDBRecord(array(
			'-table'=>'blogs',
			'permalink'=>$_REQUEST['passthru'][0],
			'-fields'=>'title'
		));
		if(isset($rec['title'])){return $rec['title'];}
	}

	if(strlen($PAGE['title'])){return $PAGE['title'];}
	return '';
}
function templateOGMeta($type){
	global $PAGE;
	if(strtolower($PAGE['name'])=='soundbytes'){
		//blog page
		$ok=executeSQL("update blogs set date_begin=_cdate where date_begin is null");
		$opts=array(
			'-table'=>'blogs',
			'-where'=>"date(date_begin) <= date(now()) and (date_end is null or date(date_end) >= date(now()))",
			'-order'=>'date_begin desc',
			'-limit'=>1
		);
		if(isDBStage()){
	    	$opts['-where'].=" and status in (1,2)";
		}
		else{
	    	$opts['-where'].=" and status=2";
		}
		if(isset($_REQUEST['passthru'][0])){
	    	$opts['-where'].=" and permalink='{$_REQUEST['passthru'][0]}'";
		}
    	$rec=getDBRecord($opts);
		//echo $rec['body'];exit;
		$rec['og_url']="http://stage.skillsai.com/soundbytes/{$rec['permalink']}";
		$rec['og_type']='article';
		$rec['og_title']=$rec['title'];
		if(strlen($rec['share'])){
			$rec['og_description']=$rec['share'];
		}
		else{
			$rec['og_description']=truncateWords(removeHtml($rec['body']),150,1);
		}
		$rec['og_description']=preg_replace('/[\r\n]+/',' ',$rec['og_description']);
		if(preg_match('/\<img(.*?)\ src\=\"(.+?)\"/mis',$rec['body'],$m)){
			if(stringContains($m[2],'http')){
				$rec['og_image']=$m[2];
			}
			else{
        		$rec['og_image']="http://{$_SERVER['SERVER_NAME']}/".$m[2];
			}
		}
	}
	else{
		$rec=array();
    	$rec['og_url']="http://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}";
		$rec['og_type']='website';
		$rec['og_title']=$PAGE['title'];
		if(strlen($PAGE['meta_description'])){
			$rec['og_description']=$PAGE['meta_description'];
		}
		else{
			$rec['og_description']='Skillsai - intelligent, hands-free, communication with the Internet of Things.  We extend your access to the Internet of Things with our business productivity, education and entertainment apps built for Amazon Echo&trade; devices.';
		}
		$rec['og_description']=preg_replace('/[\r\n]+/',' ',$rec['og_description']);
		if(preg_match('/\<img(.*?)\ src\=\"(.+?)(png|jpg|jpeg)\"/mis',$PAGE['body'],$m)){
			if(stringContains($m[2],'http')){
				$rec['og_image']=$m[2].$m[3];
			}
			else{
        		$rec['og_image']="http://{$_SERVER['SERVER_NAME']}/".$m[2].$m[3];
			}
		}
	}
	return $rec[$type];
}
function templateMetaDescription(){
	global $PAGE;
	if(strtolower($PAGE['name'])=='soundbytes' && isset($_REQUEST['passthru'][0])){
    	$rec=getDBRecord(array(
			'-table'=>'blogs',
			'permalink'=>$_REQUEST['passthru'][0],
			'-fields'=>'meta_desc'
		));
		if(isset($rec['meta_desc'])){return $rec['meta_desc'];}
	}
	if(strlen($PAGE['meta_description'])){return $PAGE['meta_description'];}
	return 'Skillsai - intelligent, hands-free, communication with the Internet of Things.  We extend your access to the Internet of Things with our business productivity, education and entertainment apps built for Amazon Echo&trade; devices.';
}
function templateMetaKeywords(){
	global $PAGE;
	if(strtolower($PAGE['name'])=='soundbytes' && isset($_REQUEST['passthru'][0])){
    	$rec=getDBRecord(array(
			'-table'=>'blogs',
			'permalink'=>$_REQUEST['passthru'][0],
			'-fields'=>'meta_keywords'
		));
		if(isset($rec['meta_keywords'])){return $rec['meta_keywords'];}
	}
	if(strlen($PAGE['meta_keywords'])){return $PAGE['meta_keywords'];}
	return 'audio applications, hangman, pico, pico fermi bagel, amazon echo, internet of things, audible reports, echo apps, busines tools, reporting, games, echo games, sound bytes, ecommerce store';
}

?>
