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
$valid_tabs = array('info', 'uptime', 'os', 'drives', 'memory', 'network', 'connections', 'processes', 'diskstats');

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

// Tab requests are AJAX partials loaded into #system_content. If a tab's code below throws, evalPHP prints the error
// and abandons the rest of this controller - setView() never runs and the DEFAULT view (the whole System page, menu
// and tabs) renders nested inside the tab div, duplicating ids so every tab breaks until a refresh.
// Pre-select an empty 'list' view so a failing tab shows its error + an empty list instead.
if(strlen($tab)){
	$recs=array();
	$listopts=array('tab'=>$tab);
	setView('list',1);
}
switch($tab){
	case 'info':
		// tiles + per-core chart say it all; the old since/since_timestamp/since_date/time/time_date dump was redundant
		$cpuinfo=systemCpuInfo();
		$cpuload=systemCpuLoad();
		$tiles=systemUptimeTiles(systemUptimeInfo(),$cpuload,$cpuinfo);
		$charts=systemUptimeCharts($cpuload);
		$recs=array();
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
		$tiles=systemOSTiles($recs);
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
		$tiles=systemDriveTiles($recs);
		$charts=systemDriveCharts($recs);
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
		$membreak=systemMemoryBreakdown($rec);
		$tiles=array_merge(systemMemoryTiles($membreak),systemMemoryExtraTiles());
		$charts=systemMemoryCharts($membreak);
		$recs=array();	// the tiles + charts above say everything the one-row table did
		setView('list',1);
		return;
	break;
	case 'network':
		// systemNetStats() samples the counters twice ~1s apart for the live rates, so this tab takes a few seconds
		$nics=systemNetStats();
		$recs=systemNetworkRows($nics);
		$tiles=systemNetworkTiles($nics);
		$charts=systemNetworkCharts($nics);
		$listopts=array(
			'tab'=>'network',
			'-listfields'=>'name,status,speed,ipv4,ipv6,gateway,dns,mac_address,rx_bytes,tx_bytes,rx_rate,tx_rate,errors,drops',
			'name_class'=>'w_bold',
			'status_class'=>'align-center',
			'speed_options'=>array('class'=>'align-right w_nowrap','eval'=>"return systemFormatSpeed(%speed%);"),
			'ipv4_options'=>array('displayname'=>'IPv4'),
			'ipv6_options'=>array('displayname'=>'IPv6 (global)'),
			'dns_options'=>array('displayname'=>'DNS'),
			'mac_address_options'=>array('class'=>'w_nowrap'),
			'rx_bytes_options'=>array('class'=>'align-right w_nowrap','displayname'=>'Received','eval'=>"return verboseSize(%rx_bytes%);"),
			'tx_bytes_options'=>array('class'=>'align-right w_nowrap','displayname'=>'Sent','eval'=>"return verboseSize(%tx_bytes%);"),
			'rx_rate_options'=>array('class'=>'align-right w_nowrap','displayname'=>'Down Now','eval'=>"return verboseSize(%rx_rate%).'/s';"),
			'tx_rate_options'=>array('class'=>'align-right w_nowrap','displayname'=>'Up Now','eval'=>"return verboseSize(%tx_rate%).'/s';"),
			'errors_options'=>array('class'=>'align-right'),
			'drops_options'=>array('class'=>'align-right'),
		);
		setView('list',1);
		return;
	break;
	case 'connections':
		$conns=systemNetConnections();
		$recs=systemConnectionRows($conns);
		$tiles=systemConnectionTiles($conns);
		$charts=systemConnectionCharts($conns);
		$listopts=array(
			'tab'=>'connections',
			'-listfields'=>'proto,process,pid,local,remote,state',
			'process_class'=>'w_bold',
			'pid_class'=>'align-right',
			'local_options'=>array('class'=>'w_nowrap'),
			'remote_options'=>array('class'=>'w_nowrap'),
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
		$tiles=systemProcessTiles($recs);
		$charts=systemProcessCharts($recs);
		if(isWindows()){
			// session_name/session_num/status/window_title are the same value on nearly every row
			$listopts['-listfields']='command,pid,user,pcpu,mem_usage,threads,handles,cpu_time';
			$listopts['threads_options']=array('class'=>'align-right');
			$listopts['handles_options']=array('class'=>'align-right');
			$listopts['cpu_time_options']=array('class'=>'align-right w_nowrap','displayname'=>'CPU Time','eval'=>"return verboseTime(%cpu_time%);");
		}
		if(!isWindows()){
			// ps columns: vsz/rss are KB; stat is decoded to words in systemGetProcessListExtra
			$listopts['rss_options']=array('class'=>'align-right w_nowrap','displayname'=>'Memory (RSS)','eval'=>"return verboseSize(%rss%*1024);");
			$listopts['vsz_options']=array('class'=>'align-right w_nowrap','displayname'=>'Virtual (VSZ)','eval'=>"return verboseSize(%vsz%*1024);");
			$listopts['stat_options']=array('displayname'=>'State');
			$listopts['-listfields']='user,pid,pcpu,pmem,rss,vsz,stat,start,time,command';
		}
		setView('list',1);
		return;
	break;
	case 'diskstats':
		if(isWindows()){
			// Windows: Get-CimInstance Win32_DiskDrive (wmic.exe no longer ships with Windows 11 24H2+)
			$cimrows=systemWinCim('Win32_DiskDrive',array('DeviceID','Model','Size','Status','BytesPerSector'));
			$recs=array();
			foreach($cimrows as $rec){
				foreach($rec as $rk=>$rv){$rec[$rk]=trim((string)$rv);}
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
			list($recs,$tiles)=systemGetLinuxDiskStats();
		}
		if(isWindows()){
			$tiles=systemDiskStatTiles($recs);
			$charts=systemDiskStatCharts($recs);
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
		if(!isWindows()){
			// Linux: /proc/diskstats records (see systemGetLinuxDiskStats); Windows options above don't apply
			$listopts=array(
				'tab'=>'diskstats',
				'device_options'=>array('class'=>'w_bold'),
				'read_bytes_options'=>array('class'=>'align-right','displayname'=>'Data Read','eval'=>"return verboseSize(%read_bytes%);"),
				'written_bytes_options'=>array('class'=>'align-right','displayname'=>'Data Written','eval'=>"return verboseSize(%written_bytes%);"),
				'reads_options'=>array('class'=>'align-right','displayname'=>'Read Requests','eval'=>"return number_format(%reads%);"),
				'writes_options'=>array('class'=>'align-right','displayname'=>'Write Requests','eval'=>"return number_format(%writes%);"),
				'avg_read_ms_options'=>array('class'=>'align-right','displayname'=>'Avg Read (ms)'),
				'avg_write_ms_options'=>array('class'=>'align-right','displayname'=>'Avg Write (ms)'),
				'busy_pcnt_options'=>array('class'=>'align-right','displayname'=>'Busy %'),
				'in_flight_options'=>array('class'=>'align-right','displayname'=>'In Flight'),
				'-listfields'=>'device,type,read_bytes,written_bytes,reads,writes,avg_read_ms,avg_write_ms,busy_pcnt,in_flight'
			);
		}
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
		$tiles=systemDriveTiles($recs);
		$charts=systemDriveCharts($recs);
		setView('default');
	break;
}
?>
