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
		$temppath=getWasqlPath('php/temp');
		$afile="{$temppath}/{$CONFIG['name']}_runnow.txt";
		$id=(integer)$_REQUEST['id'];
		$ok=setFileContents($afile,$id);
		usleep(250);
		$cron=cronDetails($id);
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
		$cron=cronDetails($id);
		setView('details',1);
		if($cron['_id']==0){
			setView('bottom');
		}
		return;
	break;
	case 'cron_result':
		//echo "cron_result";exit;
		$id=(integer)$_REQUEST['id'];
		if($id==0){
			$id=(integer)$_REQUEST['cron_id'];
			$path=getWaSQLPath('php/temp');
			$commonCronLogFile="{$path}/cronlog_{$id}.txt";
			$log=array('run_error'=>'');
			if(file_exists($commonCronLogFile)){
				$log['run_result']=getFileContents($commonCronLogFile);
			}
			else{
				$log['run_result']='No longer running';
			}
			setView('cron_result',1);
			setView('bottom');
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
		$ok=cronCheckSchema();
		setView('default');
	break;
}
?>