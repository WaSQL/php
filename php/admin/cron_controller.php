<?php
global $CONFIG;
if(isset($CONFIG['paging']) && $CONFIG['paging'] < 100){
	$CONFIG['paging']=100;
}
if(!isset($CONFIG['admin_form_url'])){
	$CONFIG['admin_form_url']='/php/admin.php';
}
switch(strtolower($_REQUEST['func'])){
	case 'run':
		$id=(integer)$_REQUEST['id'];
		$ok=editDBRecordById('_cron',$id,array('run_now'=>1));
		$cron=getDBRecordById('_cron',$id);
		setView('details',1);
		return;
	break;
	case 'kill':
		$id=(integer)$_REQUEST['id'];
		$out=cmdResults("kill {$id}");
		usleep(250);
		$id=(integer)$_REQUEST['id'];
		$cron=getDBRecordById('_cron',$id);
		setView('details',1);
		return;
	break;
	case 'add':
		//echo "add";exit;
		$id=0;
		setView('addedit',1);
		return;
	break;
	case 'edit':
		//echo "edit";exit;
		$id=(integer)$_REQUEST['id'];
		setView('addedit',1);
		return;
	break;
	case 'pid':
		loadExtras('system');
		$pid=(integer)$_REQUEST['id'];
		$recs=systemGetProcessList();
		foreach($recs as $rec){
			if($rec['pid']==$pid){
				setView('pid',1);
				return;
			}
		}
		$ok=editDBRecord(array(
			'-table'=>'_cron',
			'-where'=>"cron_pid={$pid}",
			'running'=>0,
			'cron_pid'=>0
		));
		setView('nopid',1);
		return;
	break;
	case 'pause':
		$idstr=$_REQUEST['ids'];
		$ok=editDBRecordById('_cron',$idstr,array('paused'=>1));
		setView('list',1);
		return;
	break;
	case 'unpause':
		$idstr=$_REQUEST['ids'];
		$ok=editDBRecordById('_cron',$idstr,array('paused'=>0));
		setView('list',1);
		return;
	break;
	case 'details':
		//echo "details";exit;
		$id=(integer)$_REQUEST['id'];
		$cron=getDBRecordById('_cron',$id);
		setView('details',1);
		return;
	break;
	case 'details_log':
		//echo "details";exit;
		$id=(integer)$_REQUEST['id'];
		$field='log';
		setView('details_log',1);
		return;
	break;
	case 'details_body':
		//echo "details";exit;
		$id=(integer)$_REQUEST['id'];
		$log=getDBRecord(array(
			'-table'=>'_cron_log',
			'_id'=>$id,
		));
		$json=decodeJson($log['body']);
		echo printValue($json);exit;
		if(isset($json['headers'])){
			//this is a url
			setView('details_body_url',1);
		}
		elseif(isset($json['stdout'])){
			//this is a url command
			//echo printValue($json);exit;
			setView('details_body_cmd',1);
		}
		elseif(isset($json['output'])){
			//this is a eval
			//echo printValue($json);exit;
			setView('details_body_eval',1);
		}
		elseif(isset($json['code'])){
			//this is a url command
			//echo printValue($json);exit;
			setView('details_body_cmd',1);
		}
		else{
			echo "UNKNOWN: ".printValue($json).printValue($log);exit;
			setView('details_body_eval',1);
		}
		return;
	break;
	case 'details_body_body':
		//echo "details";exit;
		$id=(integer)$_REQUEST['id'];
		$field='body';
		setView('details_body_body',1);
		return;
	break;
	case 'details_body_params':
		//echo "details";exit;
		$id=(integer)$_REQUEST['id'];
		$field='body';
		setView('details_body_params',1);
		return;
	break;
	case 'set_field_value':
		$cron_id=(integer)$_REQUEST['cron_id'];
		$fld=$_REQUEST['fld'];
		$val=$_REQUEST['val'];
		$ok=editDBRecordById('_cron',$cron_id,array($fld=>$val));
		echo printValue($ok);exit;
	break;
	case 'set_multiple':
		$ids=$_REQUEST['ids'];
		$field=$_REQUEST['field'];
		if(is_array($ids)){$ids=implode(',',$ids);}
		$val=$_REQUEST['value'];
		$opts=array(
			'-table'=>'_cron',
			'-where'=>"_id in ({$ids})",
			$field	=> $val
		);
		$ok=editDBRecord($opts);
		echo printValue($ok).printValue($opts);exit;
	break;
	case 'cron_result':
		//echo "cron_result";exit;
		$id=(integer)$_REQUEST['id'];
		if($id==0){
			$id=(integer)$_REQUEST['cron_id'];
			$path=getWaSQLPath('php/temp');
			$commonCronLogFile="{$path}/{$CONFIG['name']}_cronlog_{$id}.txt";
			$log=array('run_error'=>'');
			if(is_file($commonCronLogFile)){
				$t=time()-filectime($commonCronLogFile);
				$run_length=verboseTime($t);
				$log['bottom']="{$id},0,'{$run_length}'";
				$log['_id']=0;
				$log['cron_id']=$id;
				$log['run_result']=getFileContents($commonCronLogFile);
			}
			else{
				$log['run_result']='No longer running';
			}
			setView('cron_result',1);
			return;
		}
		$log=getDBRecordById('_cronlog',$id);
		setView('cron_result',1);
	break;
	case 'list':
		//echo "list";exit;
		setView('list',1);
		return;
	break;
	default:
		//echo "default";exit;
		$ok=commonCronCheckSchema();
		$ok=commonCronLogCheckSchema();
		$ok=commonCronCleanup();
		setView('default');
	break;
}
?>