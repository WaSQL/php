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
				$recs[$i]['status']='<span class="icon-check w_green" title="OK"></span> OK';
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
	$tiles[]=array('label'=>'CPU In Use','value'=>round($pcpu,1).'%','note'=>isWindows()?'right now, share of the whole machine':'sum of the processes listed (100% = one full core)');
	$tiles[]=array('label'=>'Memory In Use','value'=>round($pmem,1).'%','note'=>'sum of the processes listed');
	$tiles[]=array('label'=>'Top CPU Process','value'=>$topcpu['v']>=0?$topcpu['name']:'n/a','note'=>$topcpu['v']>=0?$topcpu['v'].'% of CPU':'');
	$tiles[]=array('label'=>'Top Memory Process','value'=>$topmem['v']>=0?$topmem['name']:'n/a','note'=>$topmem['v']>=0?$topmem['v'].'% of memory':'');
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
		$html.='<div style="flex:1 1 170px;border:1px solid #ccc;border-radius:6px;padding:10px 14px;min-width:150px;overflow:hidden;">'
			.'<div style="font-size:12px;text-transform:uppercase;opacity:.7;">'.encodeHtml($t['label']).'</div>'
			.'<div title="'.encodeHtml($t['value']).'" style="'.(commonStrlen($t['value'])>16?'font-size:16px;line-height:1.25;padding:5px 0;word-break:break-word;':'font-size:24px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;').'font-weight:bold;margin:2px 0;">'.encodeHtml($t['value']).'</div>'
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
		'labels'=>$b['cache']>0?array('Apps ('.verboseSize($b['apps']).')','Cache & buffers ('.verboseSize($b['cache']).')','Free ('.verboseSize($b['free']).')'):array('Apps ('.verboseSize($b['apps']).')','Free ('.verboseSize($b['free']).')'),
		'data'=>$b['cache']>0?array($gb($b['apps']),$gb($b['cache']),$gb($b['free'])):array($gb($b['apps']),$gb($b['free'])),
		'colors'=>$b['cache']>0?array('#f14668','#3e8ed0','#48c774'):array('#f14668','#48c774'));
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
/**
* systemFormatSpeed - bits per second as a link speed, e.g. 866700000 -> "867 Mbps"
* @param mixed $bps bits per second (0/empty = unknown)
* @return string
*/
function systemFormatSpeed($bps){
	$bps=(float)$bps;
	if($bps<=0){return 'n/a';}
	if($bps>=1000000000){return round($bps/1000000000,1).' Gbps';}
	if($bps>=1000000){return round($bps/1000000).' Mbps';}
	return round($bps/1000).' Kbps';
}
/**
* systemChartBoxes - renders a row of chartjs boxes in the same style as the Memory tab
* @param array $boxes each: id, title, labels[], data[], colors[], [type] doughnut|bar (default doughnut)
* @return string html (chartjs tags) wrapped so wacss builds them after the AJAX load
*/
function systemChartBoxes($boxes){
	$html='<div style="display:flex;flex-wrap:wrap;gap:16px;margin:0 0 16px 0;">';
	foreach($boxes as $c){
		$type=isset($c['type'])?$c['type']:'doughnut';
		$height=isset($c['height'])?(int)$c['height']:260;
		$minw=isset($c['minwidth'])?(int)$c['minwidth']:280;
		$multi=isset($c['datasets']) && is_array($c['datasets']);
		$datasets='';
		if($multi){
			//several series (e.g. received + sent): each dataset carries its own colour
			foreach($c['datasets'] as $ds){
				$datasets.='<dataset data-label="'.encodeHtml($ds['label']).'" data-backgroundcolor="'.encodeHtml($ds['color']).'" data-bordercolor="'.encodeHtml($ds['color']).'" data-borderwidth="1">'.json_encode($ds['data']).'</dataset>';
			}
			$colorhtml='';
		}
		else{
			//one colour per data point - cycle the list if there are more points than colours
			$cols=array();
			foreach(array_keys($c['data']) as $i=>$k){$cols[]=$c['colors'][$i % count($c['colors'])];}
			$datasets='<dataset data-label="'.encodeHtml($c['title']).'" data-borderwidth="2">'.json_encode($c['data']).'</dataset>';
			$colorhtml='<colors>'.json_encode($cols).'</colors><bcolors>'.json_encode(array_fill(0,count($cols),'#ffffff')).'</bcolors>';
		}
		if($type=='bar'){
			$ticks='{"beginAtZero":true'.(isset($c['max'])?',"max":'.(float)$c['max']:'').'}';
			$legend=$multi?'{"position":"bottom"}':'{"display":false}';
			$options='{"responsive":true,"maintainAspectRatio":false,"legend":'.$legend.',"tooltips":{"enabled":true},"scales":{"yAxes":[{"ticks":'.$ticks.'}]}}';
		}
		else{
			$options='{"responsive":true,"maintainAspectRatio":false,"legend":{"position":"bottom"},"tooltips":{"enabled":true}}';
		}
		$html.='<div style="flex:'.(isset($c['grow'])?(int)$c['grow']:1).' 1 300px;border:1px solid #ccc;border-radius:6px;padding:10px 14px;min-width:'.$minw.'px;">'
			.'<div style="font-size:12px;text-transform:uppercase;opacity:.7;margin-bottom:6px;">'.encodeHtml($c['title']).'</div>'
			.'<chartjs data-type="'.$type.'" data-id="'.$c['id'].'" style="height:'.$height.'px;">'
			.$datasets
			.'<labels>'.json_encode($c['labels']).'</labels>'
			.$colorhtml
			.'<options>'.$options.'</options>'
			.'</chartjs></div>';
	}
	return '<div data-onload="wacss.initChartJs();">'.$html.'</div>';
}
/**
* systemNetworkRows - turns systemNetStats() records into display rows for the Network tab list
* @param array $nics result of systemNetStats()
* @return array rows (raw numbers kept so column sorting works; ';' lists become <br> separated)
*/
function systemNetworkRows($nics){
	$br=function($v){return str_replace(';','<br>',(string)$v);};
	//Windows/Linux keep a pile of temporary IPv6 privacy addresses - show the first and a count of the rest
	$first=function($v){
		$a=array_values(array_filter(explode(';',(string)$v),'strlen'));
		if(count($a)<2){return implode('',$a);}
		return $a[0].'<br><span style="opacity:.6;">+'.(count($a)-1).' more</span>';
	};
	$rows=array();
	foreach($nics as $n){
		$rows[]=array(
			'name'=>strlen($n['description']) && $n['description']!=$n['name']?$n['name'].' - '.$n['description']:$n['name'],
			'status'=>$n['status']=='up'?'Up':'Down',
			'speed'=>$n['speed'],
			'ipv4'=>$br($n['ipv4']),
			'ipv6'=>$first($n['ipv6']),
			'gateway'=>$br($n['gateway']),
			'dns'=>$br($n['dns']),
			'mac_address'=>$n['mac_address'],
			'rx_bytes'=>$n['rx_bytes'],
			'tx_bytes'=>$n['tx_bytes'],
			'rx_rate'=>$n['rx_rate'],
			'tx_rate'=>$n['tx_rate'],
			'errors'=>$n['rx_errors']+$n['tx_errors'],
			'drops'=>$n['rx_drops']+$n['tx_drops']
		);
	}
	return $rows;
}
/**
* systemNetworkTiles - stat tiles for the Network tab
* @param array $nics result of systemNetStats()
* @return array tiles (label,value,note)
*/
function systemNetworkTiles($nics){
	$up=0;$rx=0;$tx=0;$rxrate=0;$txrate=0;$err=0;$drop=0;$gw='';$dns='';$speed=0;
	foreach($nics as $n){
		if($n['status']!='up'){continue;}
		$up++;
		$rx+=$n['rx_bytes'];$tx+=$n['tx_bytes'];
		$rxrate+=$n['rx_rate'];$txrate+=$n['tx_rate'];
		$err+=$n['rx_errors']+$n['tx_errors'];
		$drop+=$n['rx_drops']+$n['tx_drops'];
		if(!strlen($gw) && strlen($n['gateway'])){$gw=$n['gateway'];$dns=$n['dns'];$speed=$n['speed'];}
	}
	$tiles=array(
		array('label'=>'Adapters Up','value'=>$up.' of '.count($nics),'note'=>'adapters with a live link'),
		array('label'=>'Download Now','value'=>verboseSize($rxrate).'/s','note'=>'sampled over ~1 second just now'),
		array('label'=>'Upload Now','value'=>verboseSize($txrate).'/s','note'=>'sampled over ~1 second just now'),
		array('label'=>'Total Received','value'=>verboseSize($rx),'note'=>'on live adapters since boot / adapter reset'),
		array('label'=>'Total Sent','value'=>verboseSize($tx),'note'=>'on live adapters since boot / adapter reset'),
		array('label'=>'Errors / Drops','value'=>number_format($err).' / '.number_format($drop),'note'=>($err+$drop)?'packets lost - a bad cable, driver or congested link':'no packet loss recorded')
	);
	if(strlen($gw)){
		$tiles[]=array('label'=>'Default Gateway','value'=>$gw,'note'=>'link speed '.systemFormatSpeed($speed));
		if(strlen($dns)){$tiles[]=array('label'=>'DNS Servers','value'=>str_replace(';',', ',$dns),'note'=>'used for name lookups');}
	}
	return $tiles;
}
/**
* systemNetworkCharts - charts for the Network tab: received and sent by adapter, plus current throughput
* @param array $nics result of systemNetStats()
* @return string html (chartjs tags), '' when there is nothing to plot
*/
function systemNetworkCharts($nics){
	//short label: "Wi-Fi - Intel(R) ..." / "vEthernet (WSL (Hyper-V firewall))" -> keep the part before " (" or " - "
	$short=function($n){return trim(preg_split('/\s+-\s+|\s+\(/',$n['name'])[0]);};
	$labels=array();$rx=array();$tx=array();$rxr=array();$txr=array();
	foreach($nics as $n){
		if($n['rx_bytes']<=0 && $n['tx_bytes']<=0){continue;}
		$labels[]=$short($n);
		$rx[]=round($n['rx_bytes']/1048576,1);$tx[]=round($n['tx_bytes']/1048576,1);
		$rxr[]=round($n['rx_rate']/1024,2);$txr[]=round($n['tx_rate']/1024,2);
	}
	if(!count($labels)){return '';}
	return systemChartBoxes(array(
		array('id'=>'sysnet_total','title'=>'Data transferred since boot (MB)','type'=>'bar','labels'=>$labels,'datasets'=>array(
			array('label'=>'Received','data'=>$rx,'color'=>'#3e8ed0'),
			array('label'=>'Sent','data'=>$tx,'color'=>'#48c774')
		),'height'=>240),
		array('id'=>'sysnet_rate','title'=>'Throughput right now (KB/s)','type'=>'bar','labels'=>$labels,'datasets'=>array(
			array('label'=>'Download','data'=>$rxr,'color'=>'#3e8ed0'),
			array('label'=>'Upload','data'=>$txr,'color'=>'#48c774')
		),'height'=>240)
	));
}
/**
* systemConnectionRows - display rows for the Connections tab, listeners/established first
* @param array $conns result of systemNetConnections()
* @return array rows: proto, process, pid, local, remote, state
*/
function systemConnectionRows($conns){
	$order=array('Established'=>0,'Listen'=>1,'CloseWait'=>2,'TimeWait'=>3);
	usort($conns,function($a,$b) use ($order){
		$oa=isset($order[$a['state']])?$order[$a['state']]:9;
		$ob=isset($order[$b['state']])?$order[$b['state']]:9;
		if($oa!=$ob){return $oa<=>$ob;}
		return strcasecmp((string)$a['process'],(string)$b['process']);
	});
	$rows=array();
	foreach($conns as $c){
		$rows[]=array('proto'=>$c['proto'],'process'=>$c['process'],'pid'=>$c['pid'],'local'=>$c['local'],'remote'=>$c['remote'],'state'=>$c['state']);
	}
	return $rows;
}
/**
* systemConnectionTiles - stat tiles for the Connections tab
* @param array $conns result of systemNetConnections()
* @return array tiles (label,value,note)
*/
function systemConnectionTiles($conns){
	$by=array();$remotes=array();
	foreach($conns as $c){
		$by[$c['state']]=(isset($by[$c['state']])?$by[$c['state']]:0)+1;
		if($c['state']=='Established' && strlen($c['remote_address'])){$remotes[$c['remote_address']]=1;}
	}
	$g=function($k) use ($by){return isset($by[$k])?$by[$k]:0;};
	return array(
		array('label'=>'Established','value'=>number_format($g('Established')),'note'=>'open TCP conversations right now'),
		array('label'=>'Listening','value'=>number_format($g('Listen')),'note'=>'TCP ports waiting for connections'),
		array('label'=>'Unique Remote Hosts','value'=>number_format(count($remotes)),'note'=>'distinct addresses connected to'),
		array('label'=>'Time Wait','value'=>number_format($g('TimeWait')),'note'=>'recently closed, cleaning up (normal)'),
		array('label'=>'Close Wait','value'=>number_format($g('CloseWait')),'note'=>$g('CloseWait')>50?'many - a program is not closing sockets':'peer closed, program has not yet (a few is normal)'),
		array('label'=>'UDP Endpoints','value'=>number_format($g('UDP')),'note'=>'connectionless sockets in use')
	);
}
/**
* systemConnectionCharts - charts for the Connections tab: TCP state mix and established connections by program
* @param array $conns result of systemNetConnections()
* @return string html (chartjs tags), '' when there is nothing to plot
*/
function systemConnectionCharts($conns){
	$palette=array('#3e8ed0','#48c774','#ffe08a','#f14668','#7957d5','#00d1b2','#ff9f40','#b5b5b5');
	$states=array();$procs=array();
	foreach($conns as $c){
		if($c['proto']=='TCP'){$states[$c['state']]=(isset($states[$c['state']])?$states[$c['state']]:0)+1;}
		if($c['state']=='Established'){
			$p=strlen($c['process'])?$c['process']:'(unknown)';
			$procs[$p]=(isset($procs[$p])?$procs[$p]:0)+1;
		}
	}
	arsort($states);arsort($procs);
	$boxes=array();
	if(count($states)){
		$l=array();$d=array();
		foreach($states as $k=>$v){$l[]=$k.' ('.$v.')';$d[]=$v;}
		$boxes[]=array('id'=>'sysconn_states','title'=>'TCP connection states','labels'=>$l,'data'=>$d,'colors'=>array_slice($palette,0,count($d)));
	}
	if(count($procs)){
		$l=array();$d=array();$shown=0;$total=array_sum($procs);
		foreach(array_slice($procs,0,7,true) as $k=>$v){$l[]=$k.' ('.$v.')';$d[]=$v;$shown+=$v;}
		if($total>$shown){$l[]='Everything else ('.($total-$shown).')';$d[]=$total-$shown;}
		$boxes[]=array('id'=>'sysconn_procs','title'=>'Established connections by program','labels'=>$l,'data'=>$d,'colors'=>array_slice($palette,0,count($d)));
	}
	return count($boxes)?systemChartBoxes($boxes):'';
}
/**
* systemLoadColor - traffic-light colour for a 0-100 utilisation figure
* @param float $pct percent
* @return string hex colour
*/
function systemLoadColor($pct){
	return $pct>=85?'#f14668':($pct>=60?'#ffe08a':'#48c774');
}
/**
* systemUptimeTiles - stat tiles for the Uptime/Load tab
* @param array $up result of systemUptimeInfo()
* @param array $load result of systemCpuLoad()
* @param array $cpu result of systemCpuInfo()
* @return array tiles (label,value,note)
*/
function systemUptimeTiles($up,$load,$cpu){
	$tiles=array(
		array('label'=>'Uptime','value'=>strlen($up['booted'])?$up['verbose']:'n/a','note'=>strlen($up['booted'])?'up since '.date('M j, Y g:i a',strtotime($up['booted'])):''),
		array('label'=>'CPU Load Now','value'=>$load['total'].'%','note'=>'across '.count($load['cores']).' logical processors'),
		array('label'=>'Processor','value'=>$cpu['cores'].' cores / '.$cpu['threads'].' threads','note'=>strlen($cpu['name'])?$cpu['name']:''),
		array('label'=>'Server Time','value'=>date('g:i:s a'),'note'=>date('l, M j Y').' - '.$up['timezone'])
	);
	if(!isWindows() && function_exists('sys_getloadavg')){
		$avg=sys_getloadavg();
		if(is_array($avg)){
			$tiles[]=array('label'=>'Load Average','value'=>round($avg[0],2).' / '.round($avg[1],2).' / '.round($avg[2],2),'note'=>'1 / 5 / 15 min - compare to '.$cpu['threads'].' threads');
		}
	}
	return $tiles;
}
/**
* systemUptimeCharts - per-logical-processor CPU load bar chart
* @param array $load result of systemCpuLoad()
* @return string html (chartjs tags), '' when no per-core data
*/
function systemUptimeCharts($load){
	if(!count($load['cores'])){return '';}
	$labels=array();$colors=array();
	foreach($load['cores'] as $i=>$pct){$labels[]='CPU '.$i;$colors[]=systemLoadColor($pct);}
	return systemChartBoxes(array(
		array('id'=>'sysup_cores','title'=>'CPU load by logical processor (% busy right now)','type'=>'bar','labels'=>$labels,'data'=>$load['cores'],'colors'=>$colors,'max'=>100,'grow'=>3,'minwidth'=>360),
		array('id'=>'sysup_total','title'=>'Overall CPU','labels'=>array('Busy ('.$load['total'].'%)','Idle ('.(100-$load['total']).'%)'),'data'=>array($load['total'],100-$load['total']),'colors'=>array(systemLoadColor($load['total']),'#e8e8e8'))
	));
}
/**
* systemDriveTiles - stat tiles for the Drives tab
* @param array $recs result of systemGetDriveSpace()
* @return array tiles (label,value,note)
*/
function systemDriveTiles($recs){
	$size=0;$used=0;$avail=0;$full=array('pcnt'=>-1,'name'=>'');
	foreach($recs as $r){
		$size+=(float)$r['size'];$used+=(float)$r['used'];$avail+=(float)$r['available'];
		$pcnt=(float)$r['size']>0?round($r['used']/$r['size']*100):0;
		if($pcnt>$full['pcnt']){$full=array('pcnt'=>$pcnt,'name'=>$r['mounted']);}
	}
	if(!$size){return array();}
	return array(
		array('label'=>'Total Capacity','value'=>verboseSize($size),'note'=>count($recs).' volumes'),
		array('label'=>'Used','value'=>verboseSize($used),'note'=>round($used/$size*100).'% of all space'),
		array('label'=>'Free','value'=>verboseSize($avail),'note'=>round($avail/$size*100).'% still available'),
		array('label'=>'Fullest Volume','value'=>$full['name'],'note'=>$full['pcnt'].'% used'.($full['pcnt']>=85?' - running low':''))
	);
}
/**
* systemDriveCharts - one used/free doughnut per volume (up to 6)
* @param array $recs result of systemGetDriveSpace()
* @return string html (chartjs tags)
*/
function systemDriveCharts($recs){
	$boxes=array();
	foreach(array_slice(array_values($recs),0,6) as $i=>$r){
		if((float)$r['size']<=0){continue;}
		$pcnt=round($r['used']/$r['size']*100);
		$boxes[]=array('id'=>'sysdrv_'.$i,'title'=>trim($r['mounted'].' '.strip_tags($r['filesystem'])),
			'labels'=>array('Used ('.verboseSize($r['used']).')','Free ('.verboseSize($r['available']).')'),
			'data'=>array(round($r['used']/1073741824,2),round($r['available']/1073741824,2)),
			'colors'=>array($pcnt>75?'#dc3545':($pcnt>60?'#ffc107':'#17a2b8'),'#e8e8e8'),
			'height'=>220,'minwidth'=>240);
	}
	return count($boxes)?systemChartBoxes($boxes):'';
}
/**
* systemOSTiles - headline tiles for the Operating System tab, picked out of the details rows
* @param array $recs result of systemGetOSInfo() (name/value rows)
* @return array tiles (label,value,note)
*/
function systemOSTiles($recs){
	$m=array();
	foreach($recs as $r){$m[strtolower($r['name'])]=$r['value'];}
	$g=function($k) use ($m){return isset($m[$k])?$m[$k]:'';};
	$tiles=array();
	if(strlen($g('os name'))){$tiles[]=array('label'=>'Operating System','value'=>preg_replace('/^Microsoft\s+/','',$g('os name')),'note'=>trim('version '.$g('os version').' - '.$g('os architecture'),' -'));}
	if(strlen($g('processor'))){$tiles[]=array('label'=>'Processor','value'=>$g('cores / threads')?:$g('processor'),'note'=>$g('processor'));}
	if(strlen($g('physical memory'))){$tiles[]=array('label'=>'Memory','value'=>$g('physical memory'),'note'=>strlen($g('virtual memory'))?$g('virtual memory').' virtual':'');}
	if(strlen($g('graphics')) && $g('graphics')!='n/a'){$tiles[]=array('label'=>'Graphics','value'=>explode(', ',$g('graphics'))[0],'note'=>count(explode(', ',$g('graphics')))>1?'+ '.(count(explode(', ',$g('graphics')))-1).' more adapter(s)':'');}
	if(strlen($g('computer name'))){$tiles[]=array('label'=>'Computer','value'=>$g('computer name'),'note'=>$g('manufacturer'));}
	return $tiles;
}
/**
* systemMemoryExtraTiles - Windows commit charge and page file tiles for the Memory tab
* @return array tiles (empty off Windows)
*/
function systemMemoryExtraTiles(){
	if(!isWindows()){return array();}
	$x=systemWinExtraInfo();
	$tiles=array();
	if($x['commit_total']>0){
		$tiles[]=array('label'=>'Committed','value'=>verboseSize($x['commit_used']),'note'=>'of '.verboseSize($x['commit_total']).' limit (RAM + page file) promised to programs');
	}
	if($x['pagefile_total']>0){
		$tiles[]=array('label'=>'Page File','value'=>verboseSize($x['pagefile_used']),'note'=>'of '.verboseSize($x['pagefile_total']).' - overflow space on disk');
	}
	return $tiles;
}
/**
* systemProcessCharts - top programs by CPU and by memory (processes of the same program are summed)
* @param array $recs result of systemGetProcessList()
* @return string html (chartjs tags)
*/
function systemProcessCharts($recs){
	$cpu=array();$mem=array();
	foreach($recs as $r){
		$name=basename(strtok((string)$r['command'],' '));
		if(!strlen($name)){continue;}
		$cpu[$name]=(isset($cpu[$name])?$cpu[$name]:0)+(float)$r['pcpu'];
		$mem[$name]=(isset($mem[$name])?$mem[$name]:0)+(float)$r['pmem'];
	}
	arsort($cpu);arsort($mem);
	$boxes=array();
	$cpu=array_filter(array_slice($cpu,0,8,true),function($v){return $v>0;});
	if(count($cpu)){
		$boxes[]=array('id'=>'sysproc_cpu','title'=>'Top programs by CPU (% of machine, right now)','type'=>'bar','labels'=>array_keys($cpu),'data'=>array_map(function($v){return round($v,2);},array_values($cpu)),'colors'=>array('#3e8ed0'),'height'=>240);
	}
	$mem=array_slice($mem,0,8,true);
	if(count($mem)){
		$boxes[]=array('id'=>'sysproc_mem','title'=>'Top programs by memory (% of RAM)','type'=>'bar','labels'=>array_keys($mem),'data'=>array_map(function($v){return round($v,2);},array_values($mem)),'colors'=>array('#48c774'),'height'=>240);
	}
	return count($boxes)?systemChartBoxes($boxes):'';
}
/**
* systemDiskStatTiles - stat tiles for the Windows Disk Stats tab
* @param array $recs disk records (activity_pcnt, total_iops, total_throughput, read_/write_latency_ms)
* @return array tiles (label,value,note)
*/
function systemDiskStatTiles($recs){
	$busy=array('v'=>-1,'name'=>'');$iops=0;$tput=0;$lat=0;$latname='';
	foreach($recs as $r){
		$a=isset($r['activity_pcnt'])?(float)$r['activity_pcnt']:0;
		if($a>$busy['v']){$busy=array('v'=>$a,'name'=>$r['model']);}
		$iops+=isset($r['total_iops'])?(float)$r['total_iops']:0;
		$tput+=isset($r['total_throughput'])?(float)$r['total_throughput']:0;
		foreach(array('read_latency_ms','write_latency_ms') as $k){
			if(isset($r[$k]) && (float)$r[$k]>$lat){$lat=(float)$r[$k];$latname=$r['model'];}
		}
	}
	if($busy['v']<0){return array();}
	$tiles=array(
		array('label'=>'Busiest Disk','value'=>round($busy['v'],1).'%','note'=>$busy['name']),
		array('label'=>'Total Throughput','value'=>verboseSize($tput).'/s','note'=>'read + write, all disks'),
		array('label'=>'Total IOPS','value'=>number_format($iops,0),'note'=>'operations per second, all disks')
	);
	//some systems report 0 for every latency counter - a "0.00 ms" tile would be noise
	if($lat>0){$tiles[]=array('label'=>'Slowest Response','value'=>number_format($lat,2).' ms','note'=>$latname);}
	return $tiles;
}
/**
* systemDiskStatCharts - activity and read/write throughput per disk (Windows)
* @param array $recs disk records
* @return string html (chartjs tags)
*/
function systemDiskStatCharts($recs){
	$labels=array();$act=array();$actc=array();$rd=array();$wr=array();
	foreach($recs as $r){
		$labels[]=$r['model'];
		$a=isset($r['activity_pcnt'])?round((float)$r['activity_pcnt'],1):0;
		$act[]=$a;$actc[]=systemLoadColor($a);
		$rd[]=round((isset($r['read_throughput'])?(float)$r['read_throughput']:0)/1048576,2);
		$wr[]=round((isset($r['write_throughput'])?(float)$r['write_throughput']:0)/1048576,2);
	}
	if(!count($labels)){return '';}
	return systemChartBoxes(array(
		array('id'=>'sysdisk_act','title'=>'Disk activity (% busy)','type'=>'bar','labels'=>$labels,'data'=>$act,'colors'=>$actc,'max'=>100,'height'=>240),
		array('id'=>'sysdisk_tput','title'=>'Read vs write throughput (MB/s)','type'=>'bar','labels'=>$labels,'datasets'=>array(
			array('label'=>'Read','data'=>$rd,'color'=>'#3e8ed0'),
			array('label'=>'Write','data'=>$wr,'color'=>'#f14668')
		),'height'=>240)
	));
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