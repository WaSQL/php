<?php
loadExtras('system');
loadExtrasCss('wacss');
loadExtrasJs('wacss');
global $ADMINPAGE;
if(!isset($ADMINPAGE['page'])){
	$ADMINPAGE['page']='/php/admin.php';
}
if(!isset($ADMINPAGE['ajaxpage'])){
	$ADMINPAGE['ajaxpage']='/php/admin.php';
}
if(!isset($ADMINPAGE['ajaxdiv'])){
	$ADMINPAGE['ajaxdiv']='system_content';
}

// Validate tab parameter - whitelist only
$valid_tabs = array('info', 'uptime', 'os', 'drives', 'memory', 'network', 'processes', 'diskstats');

// If tab is not set or invalid, go to default case (initial page load)
if(!isset($_REQUEST['tab'])){
	$tab = '';
}
elseif(in_array(strtolower($_REQUEST['tab']), $valid_tabs)){
	$tab = strtolower($_REQUEST['tab']);
}
else{
	$tab = ''; // Invalid tab - go to default
}

switch($tab){
	case 'info':
		$info=getServerUptime();
		$recs=array();
		foreach($info as $k=>$v){
			$recs[]=array(
				'name'=>$k,
				'value'=>$v
			);
		}
		$listopts=array('tab'=>'info',);
		setView('list',1);
		return;
	break;
	case 'uptime':
		$recs=systemGetInfo();
		$listopts=array('tab'=>'uptime',);
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
			'use%_class'=>'align-left w_nowrap',
			'-results_eval'=>'systemGetDriveSpaceExtra'
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
			'free_pcnt'=>array(
				'class'=>'align-right',
				'eval'=>"return '%free_pcnt%'.'%';"
			),
			'used_options'=>array(
				'class'=>'align-right',
				'eval'=>"return verboseSize(%used%);"
			),
			'pcnt_used_options'=>array(
				'class'=>'align-right',
				'displayname'=>'% Used',
				'eval'=>"return '%pcnt_used%'.'%';"
			),
			'pcnt_free_options'=>array(
				'class'=>'align-right',
				'displayname'=>'% Free',
				'eval'=>"return '%pcnt_free%'.'%';"
			),
			'-listfields'=>'total,free,pcnt_free,used,pcnt_used'
		);
		if(isset($rec['available'])){
			$listopts['-listfields'].=',available,pcnt_available';
			$listopts['available_options']=array(
				'class'=>'align-right',
				'eval'=>"return verboseSize(%available%);"
			);
			$listopts['pcnt_available_options']=array(
				'class'=>'align-right',
				'displayname'=>'% Available',
				'eval'=>"return '%pcnt_available%'.'%';"
			);
		}
		if(isset($rec['buffers'])){
			$listopts['-listfields'].=',buffers,pcnt_buffers';
			$listopts['buffers_options']=array(
				'class'=>'align-right',
				'eval'=>"return verboseSize(%buffers%);"
			);
			$listopts['pcnt_buffers_options']=array(
				'class'=>'align-right',
				'displayname'=>'% Buffers',
				'eval'=>"return '%pcnt_buffers%'.'%';"
			);
		}
		if(isset($rec['cached'])){
			$listopts['-listfields'].=',cached,pcnt_cached';
			$listopts['cached_options']=array(
				'class'=>'align-right',
				'eval'=>"return verboseSize(%cached%);"
			);
			$listopts['pcnt_cached_options']=array(
				'class'=>'align-right',
				'displayname'=>'% Cached',
				'eval'=>"return '%pcnt_cached%'.'%';"
			);
		}
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
			'mac_address_options'=>array(
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
			'-results_eval'=>'systemGetProcessListExtra'
		);
		setView('list',1);
		return;
	break;
	case 'diskstats':
		if(isWindows()){
			// Windows: Use WMIC to get disk I/O statistics
			$out=cmdResults('wmic diskdrive get DeviceID,Model,Size,Status,BytesPerSector /format:csv');
			$lines=preg_split('/[\r\n]+/',$out['stdout']);
			$recs=array();
			$headers=array();
			foreach($lines as $idx=>$line){
				$line=trim($line);
				if(!strlen($line)){continue;}
				$parts=preg_split('/\,/',$line);
				if($idx==0){
					// First line is headers
					foreach($parts as $i=>$part){
						$headers[$i]=strtolower(trim($part));
					}
					continue;
				}
				if(count($parts)<2){continue;}
				$rec=array();
				foreach($parts as $i=>$part){
					if(isset($headers[$i])){
						$rec[$headers[$i]]=trim($part);
					}
				}
				if(isset($rec['deviceid']) && strlen($rec['deviceid'])){
					// Get additional I/O stats using PowerShell
					$deviceNum=preg_replace('/[^0-9]+/','',$rec['deviceid']);
					if(strlen($deviceNum)){
						$ps_cmd='powershell -Command "Get-Counter \'\PhysicalDisk('.$deviceNum.' *)\\*\' -ErrorAction SilentlyContinue | Select-Object -ExpandProperty CounterSamples | Select-Object Path,CookedValue | ConvertTo-Csv -NoTypeInformation"';
						$ps_out=cmdResults($ps_cmd);
						if(isset($ps_out['stdout']) && strlen($ps_out['stdout'])){
							$ps_lines=preg_split('/[\r\n]+/',$ps_out['stdout']);
							foreach($ps_lines as $ps_idx=>$ps_line){
								if($ps_idx==0){continue;} // Skip header
								$ps_line=trim($ps_line);
								if(!strlen($ps_line)){continue;}
								// Parse CSV: "Path","CookedValue"
								if(preg_match('/\"([^\"]+)\"\,\"([^\"]+)\"/',$ps_line,$matches)){
									$counter_path=$matches[1];
									$counter_value=$matches[2];
									if(preg_match('/\\\\([^\\\\]+)$/',$counter_path,$m)){
										$counter_name=strtolower(str_replace(' ','_',$m[1]));
										$rec[$counter_name]=round($counter_value,2);
									}
								}
							}
						}
					}

					// Calculate useful derived metrics
					// Activity percentage (inverse of idle)
					if(isset($rec['%_idle_time'])){
						$rec['activity_pcnt']=max(0,100-$rec['%_idle_time']);
					}
					// Read/Write ratio and IOPS
					if(isset($rec['disk_reads/sec'])){
						$rec['reads_per_sec']=$rec['disk_reads/sec'];
					}
					if(isset($rec['disk_writes/sec'])){
						$rec['writes_per_sec']=$rec['disk_writes/sec'];
					}
					if(isset($rec['disk_transfers/sec'])){
						$rec['total_iops']=$rec['disk_transfers/sec'];
					}
					if(isset($rec['reads_per_sec']) && isset($rec['writes_per_sec'])){
						$total_ops=$rec['reads_per_sec']+$rec['writes_per_sec'];
						if($total_ops>0){
							$rec['read_pcnt']=round(($rec['reads_per_sec']/$total_ops)*100,1);
							$rec['write_pcnt']=round(($rec['writes_per_sec']/$total_ops)*100,1);
						}
					}
					// Throughput in MB/s
					if(isset($rec['disk_read_bytes/sec'])){
						$rec['read_throughput']=$rec['disk_read_bytes/sec'];
					}
					if(isset($rec['disk_write_bytes/sec'])){
						$rec['write_throughput']=$rec['disk_write_bytes/sec'];
					}
					if(isset($rec['disk_bytes/sec'])){
						$rec['total_throughput']=$rec['disk_bytes/sec'];
					}
					// Latency indicators
					if(isset($rec['avg._disk_sec/read'])){
						$rec['read_latency_ms']=$rec['avg._disk_sec/read']*1000;
					}
					if(isset($rec['avg._disk_sec/write'])){
						$rec['write_latency_ms']=$rec['avg._disk_sec/write']*1000;
					}

					$recs[]=$rec;
				}
			}
		}
		else{
			$fieldstr='major_number,minor_number,device_name,reads_completed_successfully,reads_merged,sectors_read,time_spent,reading_ms,writes_completed,writes_merged,sectors_written,time_spent_writing_ms,ios_currently_in_progress,time_spent_doing_i/os_ms,weighted_time_spent_doing_ios_ms,discards_completed_successfully,discards_merged,sectors_discarded,time_spent_discarding,flush_requests_completed_successfully,time_spent_flushing';
			$fields=preg_split('/\,/is',$fieldstr);
			$out=cmdResults('cat --show-ends /proc/diskstats');
			$lines=preg_split('/\$/ism',$out['stdout']);
			$recs=array();
			foreach($lines as $line){
				$line=trim($line);
				$line=str_replace('$','',$line);
				$line=trim($line);
				if(!strlen($line)){continue;}
				$parts=preg_split('/[^a-zA-Z0-9\-]+/ism',$line);
				$rec=array();
				foreach($fields as $i=>$field){
					$rec[$field]=$parts[$i];
				}
				$recs[]=$rec;
			}
		}
		$listopts=array(
			'tab'=>'diskstats',
			// Basic disk info
			'model_class'=>'w_bold',
			'size_options'=>array(
				'class'=>'align-right',
				'eval'=>"return verboseSize(%size%);"
			),
			'status_class'=>'align-center',
			// Activity metrics
			'activity_pcnt_options'=>array(
				'class'=>'align-right',
				'displayname'=>'Activity',
				'eval'=>"return number_format(%activity_pcnt%,1).'%';"
			),
			// IOPS
			'reads_per_sec_options'=>array(
				'class'=>'align-right',
				'displayname'=>'Reads/s',
				'eval'=>"return number_format(%reads_per_sec%,2);"
			),
			'writes_per_sec_options'=>array(
				'class'=>'align-right',
				'displayname'=>'Writes/s',
				'eval'=>"return number_format(%writes_per_sec%,2);"
			),
			'total_iops_options'=>array(
				'class'=>'align-right',
				'displayname'=>'Total IOPS',
				'eval'=>"return number_format(%total_iops%,2);"
			),
			// Throughput
			'read_throughput_options'=>array(
				'class'=>'align-right',
				'displayname'=>'Read',
				'eval'=>"return verboseSize(%read_throughput%).'/s';"
			),
			'write_throughput_options'=>array(
				'class'=>'align-right',
				'displayname'=>'Write',
				'eval'=>"return verboseSize(%write_throughput%).'/s';"
			),
			'total_throughput_options'=>array(
				'class'=>'align-right',
				'displayname'=>'Total',
				'eval'=>"return verboseSize(%total_throughput%).'/s';"
			),
			// Latency
			'read_latency_ms_options'=>array(
				'class'=>'align-right',
				'displayname'=>'Read Latency',
				'eval'=>"return number_format(%read_latency_ms%,2).' ms';"
			),
			'write_latency_ms_options'=>array(
				'class'=>'align-right',
				'displayname'=>'Write Latency',
				'eval'=>"return number_format(%write_latency_ms%,2).' ms';"
			),
			// Queue depth
			'current_disk_queue_length_options'=>array(
				'class'=>'align-right',
				'displayname'=>'Queue Depth',
				'eval'=>"return %current_disk_queue_length%;"
			),
			// Only show the most useful fields by default
			'-listfields'=>'model,size,status,activity_pcnt,total_iops,total_throughput,read_latency_ms,write_latency_ms,current_disk_queue_length',
			'-results_eval'=>'systemGetDiskStatsExtra'
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
			'use%_class'=>'align-left w_nowrap',
			'-results_eval'=>'systemGetDriveSpaceExtra'
		);
		setView('default');
	break;
}
?>
