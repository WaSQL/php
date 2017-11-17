<?php
	//get global settings
	global $ALLCONFIG;
	global $CONFIG;
	$configvalues=array();
	foreach($CONFIG as $k=>$v){
		if(isWasqlField($k)){continue;}
		if(preg_match('/pass$/i',$k)){$v=preg_replace('/./','*',$v);}
		$configvalues[$k]=$v;
	}
	ksort($configvalues);
	$recs=settingsGetValues();
	switch(strtolower($_REQUEST['func'])){
		case 'process':
			//add any new
			$dirty=false;
			foreach($_REQUEST as $key=>$value){
				if(!isset($recs[$key])){
					$dirty=true;
					$ok=addDBRecord(array(
						'-table'=>'_settings',
						'user_id'=>0,
						'key_name'=>$key,
						'key_value'=>$value,
					));
				}
			}
			if($dirty){
				$recs=settingsGetValues();
			}
			//update any others
			foreach($recs as $key=>$rec){
				if(!isset($_REQUEST[$key])){$v='';}
				else{$v=$_REQUEST[$key];}
				$ok=editDBRecord(array('-table'=>'_settings','-where'=>"_id={$rec['_id']}",'key_value'=>$v));
			}
			foreach($_SESSION as $k=>$v){
				if(preg_match('/^(sync\_|git\_)/i',$k)){
					unset($_SESSION[$k]);
				}
			}
			setView('processed',1);
			return;
		break;
		default:
			setView('default',1);
		break;
	}
?>
