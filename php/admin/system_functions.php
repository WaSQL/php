<?php
function systemGetDiskStatsExtra($recs){
	global $ADMINPAGE;
	foreach($recs as $i=>$rec){
		// Add visual indicators for performance issues
		// Note: Values are already formatted by eval, we just add color coding

		// Activity indicator with color coding
		if(isset($rec['activity_pcnt'])){
			$value=$rec['activity_pcnt'];
			// Extract numeric value from already-formatted string if needed
			$activity=is_numeric($value)?$value:floatval($value);
			$color='';
			if($activity>90){
				$color='w_red w_bold'; // Very high activity - potential bottleneck
			}
			elseif($activity>70){
				$color='w_orange'; // High activity
			}
			elseif($activity>30){
				$color='w_bold'; // Moderate activity
			}
			else{
				$color='w_muted'; // Low activity
			}
			if($color){
				$recs[$i]['activity_pcnt']='<span class="'.$color.'">'.$value.'</span>';
			}
		}

		// Latency indicators with color coding
		if(isset($rec['read_latency_ms'])){
			$value=$rec['read_latency_ms'];
			$latency=is_numeric($value)?$value:floatval($value);
			$color='';
			if($latency>100){
				$color='w_red w_bold'; // Very slow
			}
			elseif($latency>50){
				$color='w_orange'; // Slow
			}
			elseif($latency>20){
				$color='w_bold'; // Moderate
			}
			if($color){
				$recs[$i]['read_latency_ms']='<span class="'.$color.'">'.$value.'</span>';
			}
		}

		if(isset($rec['write_latency_ms'])){
			$value=$rec['write_latency_ms'];
			$latency=is_numeric($value)?$value:floatval($value);
			$color='';
			if($latency>100){
				$color='w_red w_bold';
			}
			elseif($latency>50){
				$color='w_orange';
			}
			elseif($latency>20){
				$color='w_bold';
			}
			if($color){
				$recs[$i]['write_latency_ms']='<span class="'.$color.'">'.$value.'</span>';
			}
		}

		// Queue depth indicator
		if(isset($rec['current_disk_queue_length'])){
			$value=$rec['current_disk_queue_length'];
			$queue=is_numeric($value)?$value:floatval($value);
			$color='';
			if($queue>10){
				$color='w_red w_bold'; // Very high queue - serious bottleneck
			}
			elseif($queue>5){
				$color='w_orange'; // High queue
			}
			elseif($queue>2){
				$color='w_bold'; // Moderate queue
			}
			if($color){
				$recs[$i]['current_disk_queue_length']='<span class="'.$color.'">'.$value.'</span>';
			}
		}

		// Status indicator with icon
		if(isset($rec['status'])){
			$status=strtolower(trim($rec['status']));
			if($status=='ok'){
				$recs[$i]['status']='<span class="icon-check w_green" title="OK"></span>';
			}
			elseif($status=='error' || $status=='degraded' || $status=='failed'){
				$recs[$i]['status']='<span class="icon-warning w_red w_bold" title="'.encodeHtml(ucfirst($status)).'"></span>';
			}
			else{
				$recs[$i]['status']='<span class="icon-help w_orange" title="'.encodeHtml(ucfirst($status)).'"></span>';
			}
		}
	}
	return $recs;
}
/**
* systemProcessTiles - summary tiles for the Processes tab (process count, CPU/memory totals, top consumers, load)
* @param array $recs rows from systemGetProcessList (only processes using cpu/memory on Linux)
* @return array tiles for systemStatTiles
*/
function systemProcessTiles($recs){
	$tiles=array();
	$pcpu=0;$pmem=0;$topcpu=array('name'=>'','v'=>-1);$topmem=array('name'=>'','v'=>-1);
	foreach($recs as $rec){
		$cpu=(float)$rec['pcpu'];$mem=(float)$rec['pmem'];
		$pcpu+=$cpu;$pmem+=$mem;
		$cmd=basename(strtok((string)$rec['command'],' '));
		if($cpu>$topcpu['v']){$topcpu=array('name'=>$cmd,'v'=>$cpu);}
		if($mem>$topmem['v']){$topmem=array('name'=>$cmd,'v'=>$mem);}
	}
	$total=count($recs);$zombies=0;
	if(!isWindows()){
		$out=cmdResults('ps -eo stat --no-headers');
		$stats=preg_split('/[\r\n]+/',trim((string)$out['stdout']));
		$total=count($stats);
		foreach($stats as $st){if(strlen($st) && $st[0]=='Z'){$zombies++;}}
	}
	$tiles[]=array('label'=>'Processes','value'=>number_format($total),'note'=>isWindows()?'running':'running on this server');
	$tiles[]=array('label'=>'CPU In Use','value'=>round($pcpu,1).'%','note'=>'sum of the processes listed (100% = one full core)');
	$tiles[]=array('label'=>'Memory In Use','value'=>round($pmem,1).'%','note'=>'sum of the processes listed');
	$tiles[]=array('label'=>'Top CPU','value'=>$topcpu['v']>=0?$topcpu['name']:'n/a','note'=>$topcpu['v']>=0?$topcpu['v'].'% of CPU':'');
	$tiles[]=array('label'=>'Top Memory','value'=>$topmem['v']>=0?$topmem['name']:'n/a','note'=>$topmem['v']>=0?$topmem['v'].'% of memory':'');
	if(!isWindows()){
		$tiles[]=array('label'=>'Zombies','value'=>number_format($zombies),'note'=>'finished but not cleaned up (should be 0)');
		$load=function_exists('sys_getloadavg')?sys_getloadavg():false;
		if(is_array($load)){
			$tiles[]=array('label'=>'Load Average','value'=>round($load[0],2).' / '.round($load[1],2).' / '.round($load[2],2),'note'=>'1 / 5 / 15 min (compare to your CPU core count)');
		}
	}
	return $tiles;
}
function systemGetProcessListExtra($recs){
	global $ADMINPAGE;
	$states=array('R'=>'Running','S'=>'Sleeping','D'=>'Disk wait','T'=>'Stopped','Z'=>'Zombie','I'=>'Idle','t'=>'Traced','X'=>'Dead');
	foreach($recs as $i=>$rec){
		if(!isWindows() && isset($rec['stat']) && strlen($rec['stat'])){
			$word=isset($states[$rec['stat'][0]])?$states[$rec['stat'][0]]:$rec['stat'];
			$recs[$i]['stat']='<span title="'.encodeHtml($rec['stat']).'">'.encodeHtml($word).'</span>';
		}
		$recs[$i]['command']='<div style="max-width:200px;text-overflow: ellipsis;overflow:hidden;white-space:nowrap;" title="'.encodeHtml($rec['command']).'">'.encodeHtml($rec['command']).'</div>';
	}
	return $recs;
}
function systemGetDriveSpaceExtra($recs){
	global $ADMINPAGE;
	foreach($recs as $i=>$rec){
		if(isset($ADMINPAGE["mounted_{$rec['mounted']}_hide"])){
			unset($recs[$i]);
			continue;
		}
		elseif(isset($ADMINPAGE["filesystem_{$rec['filesystem']}_hide"])){
			unset($recs[$i]);
			continue;
		}
		if(isset($ADMINPAGE["mounted_{$rec['mounted']}_icon"])){
			$recs[$i]['filesystem']='<span class="'.encodeHtml($ADMINPAGE["mounted_{$rec['mounted']}_icon"]).'"></span> '.encodeHtml($rec['filesystem']);
		}

	}
	return $recs;
}
/**
* systemGetLinuxDiskStats - parses /proc/diskstats into human-friendly records plus summary tiles
* @return array [records, tiles] - one record per device (raw numeric values), tiles = array of label/value/note
*/
function systemGetLinuxDiskStats(){
	$raw=@file_get_contents('/proc/diskstats');
	$up=@file_get_contents('/proc/uptime');
	$uptime=strlen($up)?floatval(strtok($up,' ')):0;
	$recs=array();
	$tot=array('read_bytes'=>0,'written_bytes'=>0,'reads'=>0,'writes'=>0,'read_ms'=>0,'write_ms'=>0,'in_flight'=>0);
	$busiest=array('name'=>'','pcnt'=>0);
	foreach(preg_split('/[\r\n]+/',(string)$raw) as $line){
		$p=preg_split('/\s+/',trim($line));
		if(count($p)<14){continue;}
		$name=$p[2];
		// kernel order: reads, reads merged, sectors read, ms reading, writes, writes merged, sectors written, ms writing, in flight, ms doing io, weighted ms
		$reads=(float)$p[3];$sread=(float)$p[5];$msread=(float)$p[6];
		$writes=(float)$p[7];$swritten=(float)$p[9];$mswrite=(float)$p[10];
		$inflight=(float)$p[11];$msio=(float)$p[12];
		if(preg_match('/^(loop|ram|zram)/',$name)){$type='Loop/RAM';}
		elseif(@is_dir("/sys/block/{$name}/device")){$type='Disk';}
		elseif(preg_match('/^(dm-|md)/',$name)){$type='Virtual (LVM/RAID)';}
		elseif(@is_dir("/sys/block/{$name}")){$type='Virtual';}
		else{$type='Partition';}
		$busy=$uptime>0?round(($msio/1000)/$uptime*100,2):0;
		$rec=array(
			'device'=>$name,
			'type'=>$type,
			'read_bytes'=>$sread*512,
			'written_bytes'=>$swritten*512,
			'reads'=>$reads,
			'writes'=>$writes,
			'avg_read_ms'=>$reads>0?round($msread/$reads,2):0,
			'avg_write_ms'=>$writes>0?round($mswrite/$writes,2):0,
			'busy_pcnt'=>$busy,
			'in_flight'=>$inflight
		);
		$recs[]=$rec;
		// totals only from physical disks so partitions/LVM/loops are not double counted
		if($type=='Disk'){
			$tot['read_bytes']+=$rec['read_bytes'];$tot['written_bytes']+=$rec['written_bytes'];
			$tot['reads']+=$reads;$tot['writes']+=$writes;
			$tot['read_ms']+=$msread;$tot['write_ms']+=$mswrite;$tot['in_flight']+=$inflight;
			if($busy>=$busiest['pcnt']){$busiest=array('name'=>$name,'pcnt'=>$busy);}
		}
	}
	$avgr=$tot['reads']>0?round($tot['read_ms']/$tot['reads'],2):0;
	$avgw=$tot['writes']>0?round($tot['write_ms']/$tot['writes'],2):0;
	$tiles=array(
		array('label'=>'Data Read','value'=>verboseSize($tot['read_bytes']),'note'=>number_format($tot['reads']).' read requests since boot'),
		array('label'=>'Data Written','value'=>verboseSize($tot['written_bytes']),'note'=>number_format($tot['writes']).' write requests since boot'),
		array('label'=>'Avg Read Latency','value'=>$avgr.' ms','note'=>'time per read (SSD under 1, spinning disk 5-15)'),
		array('label'=>'Avg Write Latency','value'=>$avgw.' ms','note'=>'time per write'),
		array('label'=>'Busiest Disk','value'=>strlen($busiest['name'])?$busiest['name'].' '.$busiest['pcnt'].'%':'n/a','note'=>'share of uptime spent doing I/O'),
		array('label'=>'I/O In Flight','value'=>number_format($tot['in_flight']),'note'=>'requests waiting right now (should be near 0)'),
		array('label'=>'Uptime','value'=>$uptime>0?round($uptime/86400,1).' days':'n/a','note'=>'all counters reset at boot')
	);
	return array($recs,$tiles);
}
/**
* systemStatTiles - renders a row of summary stat tiles
* @param array $tiles list of label/value/note arrays
* @return string html
*/
function systemStatTiles($tiles){
	if(!is_array($tiles) || !count($tiles)){return '';}
	$html='<div style="display:flex;flex-wrap:wrap;gap:12px;margin:0 0 16px 0;">';
	foreach($tiles as $t){
		$html.='<div style="flex:1 1 170px;border:1px solid #ccc;border-radius:6px;padding:10px 14px;min-width:150px;">'
			.'<div style="font-size:12px;text-transform:uppercase;opacity:.7;">'.encodeHtml($t['label']).'</div>'
			.'<div style="font-size:24px;font-weight:bold;margin:2px 0;">'.encodeHtml($t['value']).'</div>'
			.'<div style="font-size:11px;opacity:.7;">'.encodeHtml($t['note']).'</div></div>';
	}
	return $html.'</div>';
}
/**
* systemMemoryBreakdown - splits memory into apps / cache / free (and swap on Linux)
* @param array $rec memory record from systemGetMemory()
* @return array total, apps, cache, free, available, swap_total, swap_used (bytes)
*/
function systemMemoryBreakdown($rec){
	$b=array('total'=>(float)$rec['total'],'apps'=>0,'cache'=>0,'free'=>(float)$rec['free'],'available'=>isset($rec['available'])?(float)$rec['available']:(float)$rec['free'],'swap_total'=>0,'swap_used'=>0);
	$info=array();
	if(!isWindows() && is_readable('/proc/meminfo')){
		foreach(preg_split('/[\r\n]+/',(string)file_get_contents('/proc/meminfo')) as $line){
			if(preg_match('/^(\w+):\s+(\d+)/',$line,$m)){$info[strtolower($m[1])]=(float)$m[2]*1024;}
		}
	}
	if(isset($info['memtotal'])){
		$cache=(isset($info['buffers'])?$info['buffers']:0)+(isset($info['cached'])?$info['cached']:0)+(isset($info['sreclaimable'])?$info['sreclaimable']:0);
		$b['cache']=$cache;
		$b['apps']=max(0,$info['memtotal']-$info['memfree']-$cache);
		if(isset($info['swaptotal'])){
			$b['swap_total']=$info['swaptotal'];
			$b['swap_used']=$info['swaptotal']-(isset($info['swapfree'])?$info['swapfree']:0);
		}
	}
	else{
		$b['apps']=max(0,$b['total']-$b['free']);
	}
	return $b;
}
/**
* systemMemoryTiles - summary tiles for the Memory tab
* @param array $b result of systemMemoryBreakdown()
* @return array tiles for systemStatTiles
*/
function systemMemoryTiles($b){
	$pc=function($v) use ($b){return $b['total']>0?round($v/$b['total']*100,1).'%':'';};
	$tiles=array(
		array('label'=>'Total Memory','value'=>verboseSize($b['total']),'note'=>'installed RAM'),
		array('label'=>'Used By Apps','value'=>verboseSize($b['apps']),'note'=>$pc($b['apps']).' of RAM held by programs'),
		array('label'=>'Available','value'=>verboseSize($b['available']),'note'=>$pc($b['available']).' usable without swapping (this is the number to watch)')
	);
	if($b['cache']>0){
		$tiles[]=array('label'=>'Cache & Buffers','value'=>verboseSize($b['cache']),'note'=>'file cache, released automatically when apps need it');
	}
	if($b['swap_total']>0){
		$tiles[]=array('label'=>'Swap Used','value'=>verboseSize($b['swap_used']),'note'=>'of '.verboseSize($b['swap_total']).' swap (heavy use means RAM is short)');
	}
	return $tiles;
}
/**
* systemMemoryCharts - pie charts for the Memory tab: RAM breakdown, swap, and top memory processes
* @param array $b result of systemMemoryBreakdown()
* @return string html (chartjs tags)
*/
function systemMemoryCharts($b){
	$gb=function($v){return round($v/1073741824,2);};
	$boxes=array();
	$boxes[]=array('id'=>'sysmem_ram','title'=>'RAM breakdown',
		'labels'=>array('Apps ('.verboseSize($b['apps']).')','Cache & buffers ('.verboseSize($b['cache']).')','Free ('.verboseSize($b['free']).')'),
		'data'=>array($gb($b['apps']),$gb($b['cache']),$gb($b['free'])),
		'colors'=>array('#f14668','#3e8ed0','#48c774'));
	if($b['swap_total']>0){
		$boxes[]=array('id'=>'sysmem_swap','title'=>'Swap',
			'labels'=>array('Used ('.verboseSize($b['swap_used']).')','Free ('.verboseSize($b['swap_total']-$b['swap_used']).')'),
			'data'=>array($gb($b['swap_used']),$gb($b['swap_total']-$b['swap_used'])),
			'colors'=>array('#ffe08a','#48c774'));
	}
	// top memory consumers, grouped by program name
	$procs=systemGetProcessList();
	$byname=array();$sumpmem=0;
	foreach($procs as $p){
		$name=basename(strtok((string)$p['command'],' '));
		if(!strlen($name)){continue;}
		$pm=(float)$p['pmem'];
		$byname[$name]=(isset($byname[$name])?$byname[$name]:0)+$pm;
		$sumpmem+=$pm;
	}
	arsort($byname);
	if(count($byname)){
		$labels=array();$data=array();$shown=0;
		foreach(array_slice($byname,0,7,true) as $name=>$pm){
			$labels[]=$name.' ('.round($pm,1).'%)';$data[]=round($pm,2);$shown+=$pm;
		}
		$other=round(100-$shown,2);
		if($other>0){$labels[]='Everything else ('.$other.'%)';$data[]=$other;}
		$boxes[]=array('id'=>'sysmem_procs','title'=>'Memory by program','labels'=>$labels,'data'=>$data,
			'colors'=>array('#f14668','#ff9f40','#ffe08a','#48c774','#3e8ed0','#7957d5','#00d1b2','#b5b5b5'));
	}
	$html='<div style="display:flex;flex-wrap:wrap;gap:16px;margin:0 0 16px 0;">';
	foreach($boxes as $c){
		$html.='<div style="flex:1 1 300px;border:1px solid #ccc;border-radius:6px;padding:10px 14px;min-width:280px;">'
			.'<div style="font-size:12px;text-transform:uppercase;opacity:.7;margin-bottom:6px;">'.encodeHtml($c['title']).'</div>'
			.'<chartjs data-type="doughnut" data-id="'.$c['id'].'" style="height:260px;">'
			.'<dataset data-label="'.encodeHtml($c['title']).'" data-borderwidth="2">'.json_encode($c['data']).'</dataset>'
			.'<labels>'.json_encode($c['labels']).'</labels>'
			.'<colors>'.json_encode($c['colors']).'</colors>'
			.'<bcolors>'.json_encode(array_fill(0,count($c['colors']),'#ffffff')).'</bcolors>'
			.'<options>{"responsive":true,"maintainAspectRatio":false,"legend":{"position":"bottom"},"tooltips":{"enabled":true}}</options>'
			.'</chartjs></div>';
	}
	return '<div data-onload="wacss.initChartJs();">'.$html.'</div>';
}
function systemShowList($recs,$listopts=array()){
	$opts=array(
		'-list'=>$recs,
		'-navonly'=>1,
		'setprocessing'=>0,
		'-sorting'=>1,
		'_menu'=>'system',
		'-onsubmit'=>"return pagingSubmit(this,'system_content');",
		'-formname'=>'systemlist',
		'-tableclass'=>'wacss_table bordered striped fullwidth',
	);
	foreach($listopts as $k=>$v){
		if(is_array($v)){$opts[$k]=$v;}
		elseif(!strlen($v)){unset($opts[$k]);}
		else{$opts[$k]=$v;}
	}
	if(isset($_REQUEST['filter_order']) && strlen($_REQUEST['filter_order'])){
		// Sanitize field name - only allow alphanumeric, underscore, dash, and space
		$fld = preg_replace('/[^a-z0-9_\-\s]/i', '', $_REQUEST['filter_order']);
		$opts['-order']=$fld;
		if(stringEndsWith($fld,' desc')){
			$fld=str_replace(' desc','',$fld);
			$sort=SORT_DESC;
		}
		else{
			$sort=SORT_ASC;
		}
		// Only sort if the field actually exists in the data
		if(isset($recs[0][$fld])){
			$opts['-list']=sortArrayByKey($recs,$fld,$sort);
		}
	}
	$html=databaseListRecords($opts);
	// wacss_table.sticky nests two scroll containers (table + tbody) = double horizontal scrollbar, so use ONE wrapper
	// that scrolls both ways and pin the header row with position:sticky instead
	$html='<style>.system_scroll{overflow:auto;max-height:70vh;}.system_scroll thead th{position:sticky;top:0;z-index:2;background:#f2f2f2;}</style>'
		.'<div class="system_scroll">'.$html.'</div>';
	return $html;
}
?>