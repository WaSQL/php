<?php
loadExtras('system');
switch(strtolower($_REQUEST['tab'])){
	case 'info':
		$recs=systemGetInfo();
		$listopts=array('tab'=>'info',);
		setView('list',1);
		return;
	break;
	case 'os':
		$info=getServerInfo();
		unset($info['network_cards']);
		unset($info['memory_usage']);
		unset($info['cpu_info']);
		unset($info['operating_system']);
		unset($info['running_processes']);
		if(is_array($info['processors'])){
			$info['processors']=implode('<br>',$info['processors']);
		}
		ksort($info);
		$recs=array();
		foreach($info as $k=>$v){
			$recs[]=array(
				'name'=>ucwords(str_replace('_',' ',$k)),
				'value'=>$v
			);
		}
		$listopts=array('tab'=>'os',);
		setView('list',1);
		return;
	break;
	case 'drives':
		$recs=systemGetDriveSpace();
		$listopts=array(
			'tab'=>'drives',
			'freespace_options'=>array(
				'class'=>'align-right',
				'eval'=>"return verboseSize(%freespace%);"
			),
			'size_options'=>array(
				'class'=>'align-right',
				'eval'=>"return verboseSize(%size%);"
			),
		);
		setView('list',1);
		return;
	break;
	case 'memory':
		$rec=systemGetMemory();
		$recs=array($rec);
		$listopts=array(
			'tab'=>'memory',
			'total_options'=>array(
				'class'=>'align-right',
				'eval'=>"return verboseSize(%total%);"
			),
			'free_options'=>array(
				'class'=>'align-right',
				'eval'=>"return verboseSize(%free%);"
			),
			'used_options'=>array(
				'class'=>'align-right',
				'eval'=>"return verboseSize(%used%);"
			),
			'pcnt_used_options'=>array(
				'class'=>'align-right'
			)
		);
		setView('list',1);
		return;
	break;
	case 'network':
		$info=getServerInfo();
		$recs=$info['network_cards'];
		$listopts=array(
			'tab'=>'network',
		);
		setView('list',1);
		return;
	break;
	case 'processes':
		$recs=systemGetProcessList();
		$listopts=array(
			'tab'=>'processes',
			//'-pretable'=>json_encode($_REQUEST),
			'pid_class'=>'align-right',
			'mem_usage_options'=>array(
				'class'=>'align-right w_nowrap',
				'eval'=>"return verboseSize(%mem_usage%);"
			),
			'cpu_time_class'=>'align-right w_nowrap',
			'pcpu_options'=>array(
				'class'=>'align-right w_nowrap',
				'displayname'=>'CPU %'
			),
			'pmem_options'=>array(
				'class'=>'align-right w_nowrap',
				'displayname'=>'Mem %'
			),
		);
		setView('list',1);
		return;
	break;
	default:
		$recs=systemGetDriveSpace();
		$listopts=array(
			'tab'=>'drives',
			'freespace_options'=>array(
				'class'=>'align-right',
				'eval'=>"return verboseSize(%freespace%);"
			),
			'size_options'=>array(
				'class'=>'align-right',
				'eval'=>"return verboseSize(%size%);"
			),
		);
		setView('default');
	break;
}
?>
