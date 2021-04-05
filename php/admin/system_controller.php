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
		$recs=systemGetOSInfo();
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
			'used_options'=>array(
				'class'=>'align-right',
				'eval'=>"return verboseSize(%used%);"
			),
			'available_options'=>array(
				'class'=>'align-right',
				'eval'=>"return verboseSize(%available%);"
			),
			'use%_class'=>'align-left w_nowrap'
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
		$recs=systemGetNetworkAdapters();
		$listopts=array(
			'tab'=>'network',
			'speed_options'=>array(
				'class'=>'align-right'
			),
			'enabled_checkmark'=>1,
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
			'used_options'=>array(
				'class'=>'align-right',
				'eval'=>"return verboseSize(%used%);"
			),
			'available_options'=>array(
				'class'=>'align-right',
				'eval'=>"return verboseSize(%available%);"
			),
			'use%_class'=>'align-left w_nowrap'
		);
		setView('default');
	break;
}
?>
