<?php
/* References:
	Server/System functions to get information about the server we are running on
	https://www.computerhope.com/wmic.htm
*/
//---------- begin function systemWinCim ----
/**
* @describe Windows only: queries WMI/CIM through PowerShell Get-CimInstance and returns the rows. Replaces the old
*	`wmic` calls - wmic.exe was removed from Windows 11 24H2+ / Server 2025 (and is deprecated everywhere).
* @param class string - CIM class name, e.g. 'Win32_OperatingSystem'
* @param props array - properties to select. A plain name, or a PowerShell calculated property such as
*	"@{n='bootdate';e={$_.LastBootUpTime.ToString('yyyy-MM-dd HH:mm:ss')}}" (use these for dates, which json badly)
* @param [filter] string - optional WQL filter, e.g. "DriveType=3"
* @return array - list of records with lowercase keys. Array-valued properties (IPAddress...) are joined with ';'.
*	Empty array on failure - check systemWinCimError() for the reason.
* @usage $rows=systemWinCim('Win32_Processor',array('Name','LoadPercentage'));
*/
function systemWinCim($class,$props,$filter=''){
	$ps="\$ErrorActionPreference='Stop';Get-CimInstance -ClassName {$class}";
	if(strlen($filter)){
		$ps.=" -Filter '".str_replace("'","''",$filter)."'";
	}
	$ps.=' | Select-Object '.implode(',',$props).' | ConvertTo-Json -Compress -Depth 3';
	return systemWinPs($ps);
}
//---------- begin function systemWinPs ----
/**
* @describe Windows only: runs a PowerShell script and returns its JSON output as rows. The script must end by piping
*	objects to `ConvertTo-Json -Compress`. Used by systemWinCim() and the network stats helpers.
* @param ps string - PowerShell source (any quoting is fine - it is sent base64 encoded)
* @return array - list of records with lowercase keys, array-valued properties joined with ';'. Empty array on failure
*	(see systemWinCimError()).
* @usage $rows=systemWinPs("Get-Process | Select-Object Name,Id | ConvertTo-Json -Compress");
*/
function systemWinPs($ps){
	global $systemWinCimError;
	$systemWinCimError='';
	$ps="\$ProgressPreference='SilentlyContinue';".$ps;
	//-EncodedCommand (UTF-16LE base64) sidesteps all quoting problems, cmdResults runs with bypass_shell on Windows
	$cmd='powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -EncodedCommand '.base64_encode(mb_convert_encoding($ps,'UTF-16LE','UTF-8'));
	$out=cmdResults($cmd);
	$json=isset($out['stdout'])?trim($out['stdout']):'';
	if(!strlen($json)){
		$systemWinCimError=trim(isset($out['stderr'])?$out['stderr']:'')?:'no output from PowerShell (is powershell.exe available?)';
		return array();
	}
	//strip a UTF-8 BOM if powershell emitted one
	$json=preg_replace('/^\xEF\xBB\xBF/','',$json);
	$data=json_decode($json,true);
	if(!is_array($data)){
		$systemWinCimError='invalid json from PowerShell: '.substr($json,0,120);
		return array();
	}
	//a single object comes back as an assoc array, not a list
	if(!isset($data[0])){$data=array($data);}
	$recs=array();
	foreach($data as $row){
		$rec=array();
		foreach($row as $k=>$v){
			if(is_array($v)){$v=implode(';',$v);}
			$rec[strtolower($k)]=$v;
		}
		$recs[]=$rec;
	}
	return $recs;
}
//---------- begin function systemWinCimError ----
/**
* @describe returns the error message from the last systemWinCim() call ('' if it worked)
* @return string
*/
function systemWinCimError(){
	global $systemWinCimError;
	return (string)$systemWinCimError;
}
function systemGetMemory(){
	global $systemGetMemoryCache;
	if(is_array($systemGetMemoryCache)){
		return $systemGetMemoryCache;
	}
	$systemGetMemoryCache=array();
	if(isWindows()){
		$rows=systemWinCim('Win32_OperatingSystem',array('TotalVisibleMemorySize','FreePhysicalMemory'));
		$os=isset($rows[0])?$rows[0]:array();
		$systemGetMemoryCache['total']=(int)(isset($os['totalvisiblememorysize'])?$os['totalvisiblememorysize']:0)*1024;
		$systemGetMemoryCache['free']=(int)(isset($os['freephysicalmemory'])?$os['freephysicalmemory']:0)*1024;
		$systemGetMemoryCache['used']=$systemGetMemoryCache['total']-$systemGetMemoryCache['free'];
		//total is 0 when the CIM query failed - report 0% instead of throwing DivisionByZeroError
		$systemGetMemoryCache['pcnt_used']=$systemGetMemoryCache['total']>0?(int)(($systemGetMemoryCache['used']/$systemGetMemoryCache['total'])*100):0;
		$systemGetMemoryCache['pcnt_free']=$systemGetMemoryCache['total']>0?100-$systemGetMemoryCache['pcnt_used']:0;
		//$systemGetMemoryCache['pcnt_used']=number_format($systemGetMemoryCache['pcnt_used'],2);
	}
	else{
		$meminfo = @getServerInfoFileData('/proc/meminfo');
		if(!empty($meminfo)){
			if(is_array($meminfo)){
				$info=array();
				foreach($meminfo as $minfo){
					$parts=preg_split('/\:/',trim($minfo),2);
					$k=trim(strtolower($parts[0]));
					$v=trim(strtolower($parts[1]));
					$info[$k]=$v;
				}
				$systemGetMemoryCache['total']=systemUnixMemsize($info['memtotal']);
				$systemGetMemoryCache['free']=systemUnixMemsize($info['memfree']);
				$systemGetMemoryCache['pcnt_free'] = (int)(($systemGetMemoryCache['free']/$systemGetMemoryCache['total'])*100);
				if(isset($info['memavailable'])){
					$systemGetMemoryCache['available']=systemUnixMemsize($info['memavailable']);
					$systemGetMemoryCache['pcnt_available'] = (int)(($systemGetMemoryCache['available']/$systemGetMemoryCache['total'])*100);
				}
				$systemGetMemoryCache['used'] = $systemGetMemoryCache['total'] - $systemGetMemoryCache['free'];
				$systemGetMemoryCache['pcnt_used']=100-$systemGetMemoryCache['pcnt_free'];
				if(isset($info['buffers'])){
					$systemGetMemoryCache['buffers']=systemUnixMemsize($info['buffers']);
					$systemGetMemoryCache['pcnt_buffers'] = (int)(($systemGetMemoryCache['buffers']/$systemGetMemoryCache['total'])*100);
				}
				if(isset($info['cached'])){
					$systemGetMemoryCache['cached']=systemUnixMemsize($info['cached']);
					$systemGetMemoryCache['pcnt_cached'] = (int)(($systemGetMemoryCache['cached']/$systemGetMemoryCache['total'])*100);
				}
			}
			elseif (preg_match('~:\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)~', $meminfo[1], $matches)){
				$systemGetMemoryCache['total'] = $matches[1] / 1024;
				$systemGetMemoryCache['used'] = $matches[2] / 1024;
				$systemGetMemoryCache['free'] = $matches[3] / 1024;
				$systemGetMemoryCache['free_pcnt'] = round(($systemGetMemoryCache['free']/$systemGetMemoryCache['total'])*100,0);
				/*$context['memory_usage']['shared'] = $matches[4] / 1024;
				$context['memory_usage']['buffers'] = $matches[5] / 1024;
				$context['memory_usage']['cached'] = $matches[6] / 1024;*/
				}
			else{
				$mem = implode('', $meminfo);
				if (preg_match('~memtotal:\s*(\d+ [kmgb])~i', $mem, $match) != 0){
					$systemGetMemoryCache['total'] = systemUnixMemsize($match[1]);
				}
				if (preg_match('~memfree:\s*(\d+ [kmgb])~i', $mem, $match) != 0){
					$systemGetMemoryCache['free'] = systemUnixMemsize($match[1]);
					$systemGetMemoryCache['free_pcnt'] = round(($systemGetMemoryCache['free']/$systemGetMemoryCache['total'])*100,0);
				}
				if (isset($systemGetMemoryCache['total'], $systemGetMemoryCache['free'])){
					$systemGetMemoryCache['used'] = $systemGetMemoryCache['total'] - $systemGetMemoryCache['free'];
				}
				if (preg_match('~swaptotal:\s*(\d+ [kmgb])~i', $mem, $match) != 0){
					$systemGetMemoryCache['swap_total'] = systemUnixMemsize($match[1]);
				}
				if (preg_match('~swapfree:\s*(\d+ [kmgb])~i', $mem, $match) != 0){
					$systemGetMemoryCache['swap_free'] = systemUnixMemsize($match[1]);
				}
				if (isset($context['memory_usage']['swap_total'], $systemGetMemoryCache['swap_free'])){
					$systemGetMemoryCache['swap_used'] = $systemGetMemoryCache['swap_total'] - $systemGetMemoryCache['swap_free'];
				}
			}
			if (preg_match('~:\s+(\d+)\s+(\d+)\s+(\d+)~', $meminfo[2], $matches) != 0){
				$systemGetMemoryCache['swap_total'] = $matches[1] / 1024;
				$systemGetMemoryCache['swap_used'] = $matches[2] / 1024;
				$systemGetMemoryCache['swap_free'] = $matches[3] / 1024;
			}
			$meminfo = false;
		}
	}
	return $systemGetMemoryCache;
}
function systemUnixMemsize($str){
	$str = strtr($str, array(',' => ''));
	list($size,$type)=preg_split('/\ /',$str,2);
	$types = array("b", "kb", "mb", "gb", "tb", "pb");
    if($key = array_search($type, $types)){
    	return $size * pow(1024, $key);
    }
    elseif (strtolower(substr($str, -1)) == 'g'){return $str * 1024 * 1024;}
	elseif (strtolower(substr($str, -1)) == 'm'){return $str * 1024;}
	elseif (strtolower(substr($str, -1)) == 'k'){return (int) $str;}
	else{return $str / 1024;}
}
function systemGetLoadAverage(){
	if(isWindows()){
		$rows=systemWinCim('Win32_Processor',array('LoadPercentage'));
		return isset($rows[0]['loadpercentage'])?trim((string)$rows[0]['loadpercentage']):'';
	}
	return $systemGetMemoryCache;
}
function systemGetDriveSpace($nohtml=0){
	$space=array();
	if(isWindows()){
		$rows=systemWinCim('Win32_LogicalDisk',array('Caption','Description','Size','FreeSpace','VolumeName','FileSystem'));
		$recs=array();
		foreach($rows as $rec){
			//"Windows-SSD (NTFS)" says more than the generic "Local Fixed Disk"
			$label=strlen(trim((string)$rec['volumename']))?trim($rec['volumename']):$rec['description'];
			if(strlen(trim((string)$rec['filesystem']))){$label.=' ('.trim($rec['filesystem']).')';}
			$recs[]=array(
				'filesystem'=>$label,
				'size'=>$rec['size'],
				'used'=>'',
				'available'=>$rec['freespace'],
				'used_pcnt'=>'',
				'mounted'=>$rec['caption']
			);
		}
	}
	else{
		$recs=systemMountsList();
	}
	//figure out used_pcnt with a bar graph
	foreach($recs as $i=>$rec){
		$rec['size']=(int)$rec['size'];
		if($rec['size'] == 0){
			unset($recs[$i]);
			continue;
		}
		$rec['available']=(int)$rec['available'];
		$rec['used']=$recs[$i]['used']=$rec['size']-$rec['available'];
		if($rec['size'] >0){
			$pcnt=round(($recs[$i]['used']/$rec['size'])*100,0);
			$pcntstr='<div class="w_right">'.$pcnt.'%</div>';
		}
		else{
			$pcnt=0;
			$pcntstr='';
		}
		if($nohtml==0){
			//add bar
			$bgcolor='#17a2b8';
			if($pcnt > 75){
				$bgcolor='#dc3545';
			}
			elseif($pcnt > 60){
				$bgcolor='#ffc107';
			}
			$recs[$i]['used_pcnt']='<div style="border:1px solid #ccd2d9;height:15px;width:150px;display:inline-block;">';
			$recs[$i]['used_pcnt'].='<div style="disply:inline-block;height:15px;width:'.$pcnt.'%;background-color:'.$bgcolor.';"></div>';
			$recs[$i]['used_pcnt'].='</div>'.$pcntstr;
		}
		else{
			$recs[$i]['used_pcnt']=$pcnt;
		}
	}
	return $recs;
}

/*

Array object:
{
    "description": "Realtek PCIe GbE Family Controller",
    "mac": "98:FA:9B:2F:BC:99",
    "mtu": 1500,
    "unicast": [
        {
            "flags": 197,
            "family": 2,
            "address": "169.254.147.254",
            "netmask": "255.255.0.0"
        }
    ],
    "up": false
}


 */
function systemGetNetworkAdapters(){
	$nics=net_get_interfaces();
	//echo printValue($nics);exit;
	if(is_array($nics)){
		$recs=array();
		ksort($nics);
		foreach($nics as $id=>$nic){
			$rec=array();
			//name
			if(isset($nic['name'])){$rec['name']=$nic['name'];}
			elseif(isset($nic['description'])){$rec['name']=$nic['description'];}
			else{$rec['name']=$id;}
			//enabled
			if(isset($nic['up']) && $nic['up']){$rec['enabled']=1;}
			else{$rec['enabled']=0;}
			//mac_address
			if(isset($nic['mac'])){$rec['mac_address']=$nic['mac'];}
			//ip_address(es)
			if(isset($nic['unicast'][0])){
				$rec['ip_address']=array();
				$rec['netmask']=array();
				foreach($nic['unicast'] as $ip){
					if(!isset($ip['address'])){continue;}
					$rec['ip_address'][]=$ip['address'];
					$rec['netmask'][]=$ip['netmask'];
				}
				$rec['ip_address']=implode('<br>'.PHP_EOL,$rec['ip_address']);
				$rec['netmask']=implode('<br>'.PHP_EOL,$rec['netmask']);
			}
			if(!isset($rec['ip_address']) || !strlen($rec['ip_address'])){
				continue;
			}
			$recs[]=$rec;
		}
		return $recs;
	}


	$space=array();
	if(isWindows()){
		$rows=systemWinCim('Win32_NetworkAdapterConfiguration',array('IPEnabled','IPAddress','MACAddress','Description','DHCPServer','IPSubnet'));
		$recs=array();
		foreach($rows as $rec){
			//if(!strlen($rec['adaptertype'])){continue;}
			$recs[]=array(
				'name'=>$rec['description'],
				'enabled'=>(isset($rec['ipenabled']) && ($rec['ipenabled']===true || strtolower((string)$rec['ipenabled'])=='true'))?1:0,
				'mac_address'=>(string)$rec['macaddress'],
				'ip_address'=>(string)$rec['ipaddress'],
				'ip_subnet'=>(string)$rec['ipsubnet'],
				'dhcp_server'=>(string)$rec['dhcpserver']
			);
		}
	}
	else{
		$cmd='ip -j addr show';
		$out=cmdResults($cmd);
		if($out['rtncode'] > 0 && !strlen($out['stdout'])){
			$cmd='sudo ip -j addr show';
			$out=cmdResults($cmd);
		}
		if($out['rtncode'] > 0 && !strlen($out['stdout'])){
			echo "Network command failed. May be a permissions issue.";
			echo printValue($out);
			exit;
		}
		$nics=json_decode($out['stdout'],true);
		if(!is_array($nics)){
			$cmd='ip addr show';
			$out=cmdResults($cmd);
			if($out['rtncode'] > 0 && !strlen($out['stdout'])){
				$cmd='sudo ip addr show';
				$out=cmdResults($cmd);
			}
			echo nl2br($out['stdout']);
			exit;
		}
		
		//echo printValue($nics);exit;
		$recs=array();
		foreach($nics as $nic){
			if($nic['link_type']=='loopback'){continue;}
			$rec=array(
				'name'=>$nic['ifname'],
				'type'=>$nic['link_type'],
				'mac_address'=>$nic['address'],
			);
			//ip addresses
			$addrs=array();
			foreach($nic['addr_info'] as $addr){
				if($addr['family']=='inet6'){
					$family='(v6) ';
				}
				else{
					$family='(v4) ';
				}
				$addrs['ip_address'][]="{$family} {$addr['local']}";
				if(isset($addr['broadcast'])){
					$addrs['ip_broadcast'][]="{$family} {$addr['broadcast']}";
				}
				
			}
			foreach($addrs as $k=>$v){
				$rec[$k]=implode('<br />'.PHP_EOL,$v);
			}
			$recs[]=$rec;
		}
	}
	return $recs;
}
//---------- begin function systemCpuInfo ----
/**
* @describe processor model and size
* @return array name, cores (physical), threads (logical processors), mhz
* @usage $cpu=systemCpuInfo();
*/
function systemCpuInfo(){
	global $systemCpuInfoCache;
	if(is_array($systemCpuInfoCache)){return $systemCpuInfoCache;}
	$info=array('name'=>'','cores'=>0,'threads'=>0,'mhz'=>0);
	if(isWindows()){
		foreach(systemWinCim('Win32_Processor',array('Name','NumberOfCores','NumberOfLogicalProcessors','MaxClockSpeed')) as $r){
			if(!strlen($info['name'])){$info['name']=trim($r['name']);}
			$info['cores']+=(int)$r['numberofcores'];
			$info['threads']+=(int)$r['numberoflogicalprocessors'];
			$info['mhz']=max($info['mhz'],(int)$r['maxclockspeed']);
		}
	}
	else{
		$ids=array();
		foreach((array)@file('/proc/cpuinfo',FILE_IGNORE_NEW_LINES) as $line){
			if(strpos($line,':')===false){continue;}
			list($k,$v)=array_map('trim',explode(':',$line,2));
			if($k=='model name' && !strlen($info['name'])){$info['name']=$v;}
			elseif($k=='processor'){$info['threads']++;}
			elseif($k=='cpu MHz'){$info['mhz']=max($info['mhz'],(int)$v);}
			elseif($k=='core id'){$ids[$v]=1;}
		}
		$info['cores']=count($ids)?count($ids):$info['threads'];
	}
	$systemCpuInfoCache=$info;
	return $info;
}
//---------- begin function systemCpuLoad ----
/**
* @describe current CPU utilisation, overall and per logical processor (a snapshot, not an average since boot)
*	Linux samples /proc/stat twice 0.5s apart; Windows reads the performance counters.
* @return array total (0-100), cores (list of 0-100, one per logical processor)
* @usage $load=systemCpuLoad();
*/
function systemCpuLoad(){
	$out=array('total'=>0,'cores'=>array());
	if(isWindows()){
		foreach(systemWinCim('Win32_PerfFormattedData_PerfOS_Processor',array('Name','PercentProcessorTime')) as $r){
			if($r['name']=='_Total'){$out['total']=(int)$r['percentprocessortime'];}
			elseif(is_numeric($r['name'])){$out['cores'][(int)$r['name']]=(int)$r['percentprocessortime'];}
		}
		ksort($out['cores']);
		$out['cores']=array_values($out['cores']);
		return $out;
	}
	$read=function(){
		$rows=array();
		foreach((array)@file('/proc/stat',FILE_IGNORE_NEW_LINES) as $line){
			if(!preg_match('/^(cpu\d*)\s+(.*)$/',$line,$m)){continue;}
			$v=array_map('floatval',preg_split('/\s+/',trim($m[2])));
			$idle=$v[3]+(isset($v[4])?$v[4]:0);
			$rows[$m[1]]=array(array_sum($v),$idle);
		}
		return $rows;
	};
	$a=$read();usleep(500000);$b=$read();
	foreach($b as $name=>$cur){
		if(!isset($a[$name])){continue;}
		$dt=$cur[0]-$a[$name][0];$di=$cur[1]-$a[$name][1];
		$pct=$dt>0?(int)round((1-$di/$dt)*100):0;
		if($name=='cpu'){$out['total']=$pct;}else{$out['cores'][]=$pct;}
	}
	return $out;
}
//---------- begin function systemUptimeInfo ----
/**
* @describe when the server booted and how long it has been up
* @return array booted (Y-m-d H:i:s), seconds, verbose ("4 days, 10 hours, 17 minutes"), time (server time), timezone
* @usage $up=systemUptimeInfo();
*/
function systemUptimeInfo(){
	$seconds=0;$booted='';
	if(isWindows()){
		$rows=systemWinCim('Win32_OperatingSystem',array("@{n='bootdate';e={\$_.LastBootUpTime.ToString('yyyy-MM-dd HH:mm:ss')}}"));
		if(isset($rows[0]['bootdate'])){
			$booted=$rows[0]['bootdate'];
			$seconds=max(0,time()-strtotime($booted));
		}
	}
	else{
		$raw=trim((string)@file_get_contents('/proc/uptime'));
		if(strlen($raw)){
			$seconds=(int)explode(' ',$raw)[0];
			$booted=date('Y-m-d H:i:s',time()-$seconds);
		}
	}
	$d=intdiv($seconds,86400);$h=intdiv($seconds%86400,3600);$m=intdiv($seconds%3600,60);
	$parts=array();
	if($d){$parts[]=$d.' day'.($d==1?'':'s');}
	if($h || $d){$parts[]=$h.' hour'.($h==1?'':'s');}
	$parts[]=$m.' minute'.($m==1?'':'s');
	return array(
		'booted'=>$booted,
		'seconds'=>$seconds,
		'verbose'=>implode(', ',$parts),
		'time'=>date('Y-m-d H:i:s'),
		'timezone'=>date_default_timezone_get()
	);
}
//---------- begin function systemWinProcessPerf ----
/**
* @describe Windows only: current per-process CPU and size counters keyed by pid. CPU is normalised to the whole machine
*	(100 = every logical processor fully busy) so the column adds up to real utilisation.
* @return array pid => array(cpu, private_bytes, threads, handles)
* @usage $perf=systemWinProcessPerf();
*/
function systemWinProcessPerf(){
	$threads=max(1,(int)systemCpuInfo()['threads']);
	$perf=array();
	foreach(systemWinCim('Win32_PerfFormattedData_PerfProc_Process',array('IDProcess','Name','PercentProcessorTime','WorkingSetPrivate','ThreadCount','HandleCount')) as $r){
		if($r['name']=='_Total'){continue;}
		$pid=(int)$r['idprocess'];
		//the Idle pseudo-process (pid 0) is "unused CPU", not usage
		$cpu=$pid==0?0:round((float)$r['percentprocessortime']/$threads,2);
		$perf[$pid]=array('cpu'=>$cpu,'private_bytes'=>(float)$r['workingsetprivate'],'threads'=>(int)$r['threadcount'],'handles'=>(int)$r['handlecount']);
	}
	return $perf;
}
//---------- begin function systemWinExtraInfo ----
/**
* @describe Windows only: graphics adapters, BIOS, page file and commit figures for the OS and Memory tabs
* @return array gpus (list of name+vram), bios, pagefile_total/pagefile_used (bytes), commit_total/commit_used (bytes)
* @usage $x=systemWinExtraInfo();
*/
function systemWinExtraInfo(){
	$x=array('gpus'=>array(),'bios'=>'','pagefile_total'=>0,'pagefile_used'=>0,'commit_total'=>0,'commit_used'=>0);
	foreach(systemWinCim('Win32_VideoController',array('Name','AdapterRAM')) as $r){
		$x['gpus'][]=trim($r['name']);
	}
	foreach(systemWinCim('Win32_BIOS',array('SMBIOSBIOSVersion','Manufacturer')) as $r){
		$x['bios']=trim($r['manufacturer'].' '.$r['smbiosbiosversion']);
	}
	foreach(systemWinCim('Win32_PageFileUsage',array('AllocatedBaseSize','CurrentUsage')) as $r){
		$x['pagefile_total']+=(float)$r['allocatedbasesize']*1048576;
		$x['pagefile_used']+=(float)$r['currentusage']*1048576;
	}
	foreach(systemWinCim('Win32_OperatingSystem',array('TotalVirtualMemorySize','FreeVirtualMemory')) as $r){
		$x['commit_total']=(float)$r['totalvirtualmemorysize']*1024;
		$x['commit_used']=$x['commit_total']-(float)$r['freevirtualmemory']*1024;
	}
	return $x;
}
//---------- begin function systemNetStats ----
/**
* @describe per-adapter network details plus live traffic: link status/speed, addresses, gateway, DNS, byte/packet/error
*	counters (since boot / adapter reset) and current rx/tx rate. The rate comes from sampling the counters twice
*	about one second apart, so this call takes ~1s.
* @return array - one record per adapter: name, description, status (up|down), speed (bits/s, 0 unknown), mac_address,
*	ipv4, ipv6 (global only), gateway, dns, rx_bytes, tx_bytes, rx_packets, tx_packets, rx_errors, tx_errors, rx_drops,
*	tx_drops, rx_rate, tx_rate (bytes/s). Sorted: up adapters first, busiest first.
* @usage $nics=systemNetStats();
*/
function systemNetStats(){
	$recs=array();
	if(isWindows()){
		$ps=<<<'PS'
$s0=@{}
Get-NetAdapterStatistics | ForEach-Object { $s0[$_.Name]=$_ }
$t0=[DateTime]::UtcNow
Start-Sleep -Milliseconds 1000
$s1=@{}
Get-NetAdapterStatistics | ForEach-Object { $s1[$_.Name]=$_ }
$secs=([DateTime]::UtcNow-$t0).TotalSeconds
$ips=@(Get-NetIPAddress)
$gws=@(Get-NetRoute -DestinationPrefix '0.0.0.0/0')
$dns=@(Get-DnsClientServerAddress -AddressFamily IPv4)
@(Get-NetAdapter | ForEach-Object {
	$n=$_.Name; $a=$s0[$n]; $b=$s1[$n]
	$mine=@($ips | Where-Object { $_.InterfaceAlias -eq $n })
	[pscustomobject][ordered]@{
		name=$n
		description=[string]$_.InterfaceDescription
		status=[string]$_.Status
		speed=[int64]$_.Speed
		mac_address=[string]$_.MacAddress
		ipv4=(@($mine | Where-Object { $_.AddressFamily -eq 'IPv4' } | ForEach-Object { "$($_.IPAddress)/$($_.PrefixLength)" }) -join ';')
		ipv6=(@($mine | Where-Object { $_.AddressFamily -eq 'IPv6' -and $_.IPAddress -notlike 'fe80*' } | ForEach-Object { $_.IPAddress }) -join ';')
		gateway=(@($gws | Where-Object { $_.InterfaceAlias -eq $n } | ForEach-Object { $_.NextHop }) -join ';')
		dns=(@($dns | Where-Object { $_.InterfaceAlias -eq $n } | ForEach-Object { $_.ServerAddresses }) -join ';')
		rx_bytes=[int64]$b.ReceivedBytes
		tx_bytes=[int64]$b.SentBytes
		rx_packets=[int64]$b.ReceivedUnicastPackets
		tx_packets=[int64]$b.SentUnicastPackets
		rx_errors=[int64]$b.ReceivedPacketErrors
		tx_errors=[int64]$b.OutboundPacketErrors
		rx_drops=[int64]$b.ReceivedDiscardedPackets
		tx_drops=[int64]$b.OutboundDiscardedPackets
		rx_bytes0=[int64]$a.ReceivedBytes
		tx_bytes0=[int64]$a.SentBytes
		secs=[double]$secs
	}
}) | ConvertTo-Json -Compress -Depth 3
PS;
		foreach(systemWinPs($ps) as $r){
			$secs=max(0.1,(float)$r['secs']);
			$r['status']=strtolower($r['status'])=='up'?'up':'down';
			$r['rx_rate']=(int)max(0,($r['rx_bytes']-$r['rx_bytes0'])/$secs);
			$r['tx_rate']=(int)max(0,($r['tx_bytes']-$r['tx_bytes0'])/$secs);
			unset($r['rx_bytes0'],$r['tx_bytes0'],$r['secs']);
			$recs[]=$r;
		}
	}
	else{
		//Linux: counters from /proc/net/dev sampled twice, details from net_get_interfaces() + /sys + /proc/net/route
		$read=function(){
			$out=array();
			foreach((array)@file('/proc/net/dev',FILE_IGNORE_NEW_LINES) as $line){
				if(strpos($line,':')===false){continue;}
				list($name,$vals)=explode(':',$line,2);
				$v=preg_split('/\s+/',trim($vals));
				if(count($v)<16){continue;}
				$out[trim($name)]=array(
					'rx_bytes'=>(float)$v[0],'rx_packets'=>(float)$v[1],'rx_errors'=>(float)$v[2],'rx_drops'=>(float)$v[3],
					'tx_bytes'=>(float)$v[8],'tx_packets'=>(float)$v[9],'tx_errors'=>(float)$v[10],'tx_drops'=>(float)$v[11]
				);
			}
			return $out;
		};
		$t0=microtime(true);
		$s0=$read();
		usleep(1000000);
		$s1=$read();
		$secs=max(0.1,microtime(true)-$t0);
		$gateways=array();
		foreach((array)@file('/proc/net/route',FILE_IGNORE_NEW_LINES) as $i=>$line){
			$f=preg_split('/\s+/',trim($line));
			if($i==0 || count($f)<3 || $f[1]!='00000000'){continue;}
			$gateways[$f[0]]=long2ip(hexdec(bin2hex(strrev(hex2bin(str_pad($f[2],8,'0',STR_PAD_LEFT))))));
		}
		$dns=array();
		foreach((array)@file('/etc/resolv.conf',FILE_IGNORE_NEW_LINES) as $line){
			if(preg_match('/^\s*nameserver\s+(\S+)/',$line,$m)){$dns[]=$m[1];}
		}
		$nics=function_exists('net_get_interfaces')?@net_get_interfaces():array();
		if(!is_array($nics)){$nics=array();}
		foreach($s1 as $name=>$c){
			if($name=='lo'){continue;}
			$nic=isset($nics[$name])?$nics[$name]:array();
			$ipv4=array();$ipv6=array();
			if(isset($nic['unicast']) && is_array($nic['unicast'])){
				foreach($nic['unicast'] as $u){
					if(!isset($u['address'])){continue;}
					if(strpos($u['address'],':')===false){
						$bits=isset($u['netmask'])?substr_count(decbin((int)ip2long($u['netmask'])),'1'):'';
						$ipv4[]=$u['address'].(strlen($bits)?'/'.$bits:'');
					}
					elseif(stripos($u['address'],'fe80')!==0){$ipv6[]=$u['address'];}
				}
			}
			$state=trim((string)@file_get_contents("/sys/class/net/{$name}/operstate"));
			$speed=(int)trim((string)@file_get_contents("/sys/class/net/{$name}/speed"));
			$p=isset($s0[$name])?$s0[$name]:$c;
			$recs[]=array(
				'name'=>$name,
				'description'=>$name,
				'status'=>($state=='up' || (isset($nic['up']) && $nic['up']))?'up':'down',
				'speed'=>$speed>0?$speed*1000000:0,
				'mac_address'=>isset($nic['mac'])?$nic['mac']:trim((string)@file_get_contents("/sys/class/net/{$name}/address")),
				'ipv4'=>implode(';',$ipv4),
				'ipv6'=>implode(';',$ipv6),
				'gateway'=>isset($gateways[$name])?$gateways[$name]:'',
				'dns'=>isset($gateways[$name])?implode(';',$dns):'',
				'rx_bytes'=>$c['rx_bytes'],'tx_bytes'=>$c['tx_bytes'],
				'rx_packets'=>$c['rx_packets'],'tx_packets'=>$c['tx_packets'],
				'rx_errors'=>$c['rx_errors'],'tx_errors'=>$c['tx_errors'],
				'rx_drops'=>$c['rx_drops'],'tx_drops'=>$c['tx_drops'],
				'rx_rate'=>(int)max(0,($c['rx_bytes']-$p['rx_bytes'])/$secs),
				'tx_rate'=>(int)max(0,($c['tx_bytes']-$p['tx_bytes'])/$secs)
			);
		}
	}
	//up adapters first, then busiest (total bytes) first
	usort($recs,function($a,$b){
		if($a['status']!=$b['status']){return $a['status']=='up'?-1:1;}
		return ($b['rx_bytes']+$b['tx_bytes'])<=>($a['rx_bytes']+$a['tx_bytes']);
	});
	return $recs;
}
//---------- begin function systemNetConnections ----
/**
* @describe lists TCP connections/listeners and UDP endpoints with the owning process.
*	Linux note: process names only show for sockets the web user owns unless it can run `ss -p` as root.
* @return array - records: proto (TCP|UDP), process, pid, local (addr:port), remote (addr:port, '' for listeners),
*	remote_address, state (Established|Listen|TimeWait|CloseWait|... or UDP)
* @usage $conns=systemNetConnections();
*/
function systemNetConnections(){
	$recs=array();
	if(isWindows()){
		$ps=<<<'PS'
$p=@{}
Get-Process | ForEach-Object { $p[[int]$_.Id]=$_.ProcessName }
$rows=@()
$rows+=@(Get-NetTCPConnection | ForEach-Object { [pscustomobject]@{ proto='TCP'; local_address=[string]$_.LocalAddress; local_port=[int]$_.LocalPort; remote_address=[string]$_.RemoteAddress; remote_port=[int]$_.RemotePort; state=[string]$_.State; pid=[int]$_.OwningProcess; process=[string]$p[[int]$_.OwningProcess] } })
$rows+=@(Get-NetUDPEndpoint | ForEach-Object { [pscustomobject]@{ proto='UDP'; local_address=[string]$_.LocalAddress; local_port=[int]$_.LocalPort; remote_address=''; remote_port=0; state='UDP'; pid=[int]$_.OwningProcess; process=[string]$p[[int]$_.OwningProcess] } })
$rows | ConvertTo-Json -Compress -Depth 3
PS;
		foreach(systemWinPs($ps) as $r){
			$recs[]=$r;
		}
	}
	else{
		$out=cmdResults('ss -H -tunap');
		if($out['rtncode'] > 0 && !strlen((string)$out['stdout'])){
			$out=cmdResults('ss -tunap');
		}
		$states=array('ESTAB'=>'Established','LISTEN'=>'Listen','TIME-WAIT'=>'TimeWait','CLOSE-WAIT'=>'CloseWait','SYN-SENT'=>'SynSent','SYN-RECV'=>'SynReceived','FIN-WAIT-1'=>'FinWait1','FIN-WAIT-2'=>'FinWait2','LAST-ACK'=>'LastAck','CLOSING'=>'Closing','UNCONN'=>'UDP');
		$splitaddr=function($a){
			$i=strrpos($a,':');
			if($i===false){return array($a,0);}
			//drop the scope suffix ss prints on link-local/loopback addresses (127.0.0.53%lo)
			return array(preg_replace('/%.*$/','',trim(substr($a,0,$i),'[]')),(int)substr($a,$i+1));
		};
		foreach(preg_split('/[\r\n]+/',(string)$out['stdout']) as $line){
			$f=preg_split('/\s+/',trim($line),7);
			if(count($f)<6 || !in_array($f[0],array('tcp','udp'))){continue;}
			list($la,$lp)=$splitaddr($f[4]);
			list($ra,$rp)=$splitaddr($f[5]);
			$proc='';$pid=0;
			if(isset($f[6]) && preg_match('/\(\("([^"]+)",pid=(\d+)/',$f[6],$m)){$proc=$m[1];$pid=(int)$m[2];}
			$st=isset($states[$f[1]])?$states[$f[1]]:$f[1];
			$recs[]=array('proto'=>strtoupper($f[0]),'local_address'=>$la,'local_port'=>$lp,'remote_address'=>($rp==0 || $ra=='*')?'':$ra,'remote_port'=>$rp,'state'=>$f[0]=='udp'?'UDP':$st,'pid'=>$pid,'process'=>$proc);
		}
	}
	foreach($recs as $i=>$r){
		$r['local']=$r['local_address'].':'.$r['local_port'];
		$r['remote']=(strlen($r['remote_address']) && $r['remote_port']>0 && !in_array($r['remote_address'],array('0.0.0.0','::','*')))?$r['remote_address'].':'.$r['remote_port']:'';
		if(!strlen($r['remote'])){$r['remote_address']='';}
		$recs[$i]=$r;
	}
	return $recs;
}
function systemGetOSInfo(){
	$space=array();
	if(isWindows()){
		//get cpu name
		$rows=systemWinCim('Win32_Processor',array('Name'));
		$cpu=isset($rows[0]['name'])?trim($rows[0]['name']):'';
		//model
		$rows=systemWinCim('Win32_ComputerSystem',array('Model','Manufacturer'));
		$model=isset($rows[0])?$rows[0]:array('manufacturer'=>'','model'=>'');
		$rows=systemWinCim('Win32_OperatingSystem',array(
			'Caption','FreePhysicalMemory','FreeSpaceInPagingFiles','FreeVirtualMemory',
			"@{n='installdate';e={\$_.InstallDate.ToString('yyyy-MM-dd HH:mm:ss')}}",
			"@{n='localdatetime';e={\$_.LocalDateTime.ToString('yyyy-MM-dd HH:mm:ss')}}",
			'OSArchitecture','RegisteredUser','TotalVirtualMemorySize','TotalVisibleMemorySize','Version','CSName'
		));
		$recs=array();
		foreach($rows as $rec){
			$cpuinfo=systemCpuInfo();
			$extra=systemWinExtraInfo();
			$xrec=array(
				'processor'=>$cpu,
				'cores_threads'=>$cpuinfo['cores'].' cores / '.$cpuinfo['threads'].' threads @ '.round($cpuinfo['mhz']/1000,2).' GHz',
				'graphics'=>count($extra['gpus'])?implode(', ',$extra['gpus']):'n/a',
				'bios'=>strlen($extra['bios'])?$extra['bios']:'n/a',
				'os_name'=>$rec['caption'],
				'os_version'=>$rec['version'],
				'os_architecture'=>$rec['osarchitecture'],
				'os_install_date'=>date('Y-m-d g:i a',strtotime($rec['installdate'])),
				'manufacturer'=>"{$model['manufacturer']} Model {$model['model']}",
				'physical_memory'=>verboseSize($rec['totalvisiblememorysize']*1000),
				'virtual_memory'=>verboseSize($rec['totalvirtualmemorysize']*1000),
				'current_date'=>date('Y-m-d g:i a',strtotime($rec['localdatetime'])),
				'computer_name'=>$rec['csname'],
				'registered_user'=>$rec['registereduser']
			);
			foreach($xrec as $k=>$v){
				$recs[]=array(
					'name'=>str_replace(array('Cores Threads','Bios'),array('Cores / Threads','BIOS'),preg_replace('/^Os /','OS ',ucwords(str_replace('_',' ',$k)))),
					'value'=>$v
				);
			}
		}
	}
	else{
		$recs=array();
		//cpuinfo
		$lines=getServerInfoFileData('/proc/cpuinfo');
		$cpuinfo=array();
		foreach($lines as $i=>$line){
			$lines[$i]=trim($line);
			list($k,$v)=preg_split('/\:/',$line,2);
			$k=strtolower(trim($k));
			$cpuinfo[$k]=trim($v);
		}
		//echo printValue($cpuinfo);exit;
		$recs[]=array(
			'name'=>'Processor',
			'value'=>$cpuinfo['model name']
		);
		
		//osrelease
		$tmp=cmdResults("cat /etc/os-release");
		$lines=preg_split('/[\r\n]+/',$tmp['stdout']);
		$osrelease=array();
		foreach($lines as $i=>$line){
			$lines[$i]=trim($line);
			list($k,$v)=preg_split('/[\:\=]/',$line,2);
			$k=strtolower(trim($k));
			$v=preg_replace('/^\"/','',$v);
			$v=preg_replace('/\"$/','',$v);
			$osrelease[$k]=trim($v);
		}
		$recs[]=array(
			'name'=>'OS Name',
			'value'=>$osrelease['name']
		);
		$recs[]=array(
			'name'=>'OS Version',
			'value'=>$osrelease['version']
		);
		//Architecture
		$tmp=cmdResults("uname -i");
		$recs[]=array(
			'name'=>'OS Architecture',
			'value'=>trim($tmp['stdout'])
		);
		//meminfo
		$lines=getServerInfoFileData('/proc/meminfo');
		$meminfo=array();
		foreach($lines as $i=>$line){
			$lines[$i]=trim($line);
			list($k,$v)=preg_split('/\:/',$line,2);
			$k=strtolower(trim($k));
			$meminfo[$k]=trim($v);
		}
		$recs[]=array(
			'name'=>'Physical Memory',
			'value'=>verboseSize(systemConvertToBytes($meminfo['memtotal']))
		);
		//hostname
		$tmp=cmdResults("hostname");
		$recs[]=array(
			'name'=>'Computer Name',
			'value'=>trim($tmp['stdout'])
		);
		//Current Date
		$tmp=cmdResults("date");
		$recs[]=array(
			'name'=>'Current Date',
			'value'=>trim($tmp['stdout'])
		);
	}
	return $recs;
}
function systemConvertToBytes($from){
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    $number = substr($from, 0, -2);
    $suffix = strtoupper(substr($from,-2));
    //B or no suffix
    if(is_numeric(substr($suffix, 0, 1))) {
        return preg_replace('/[^\d]/', '', $from);
    }
    $exponent = array_flip($units)[$suffix] ?? null;
    if($exponent === null) {
        return 0;
    }
    return $number * (1024 ** $exponent);
}

function systemGetProcessList(){
	if(isWindows()){
		$cmd="tasklist /v /fo csv";
		$out=cmdResults($cmd);
		$lines=preg_split('/[\r\n]+/',$out['stdout']);
		//remove the first line
		$fieldline=strtolower(array_shift($lines));
		$fieldline=str_replace(' ','_',$fieldline);
		$fieldline=str_replace('#','_num',$fieldline);
		$fieldline=str_replace('image_name','command',$fieldline);
		$fieldline=str_replace('user_name','user',$fieldline);
	    $fields=csvParseLine($fieldline);
		$recs=array();
		$totals=array('mem'=>0,'cpu'=>0);
		$mem=systemGetMemory();
		foreach($lines as $line){
			if(!strlen(trim($line))){continue;}
			$parts=csvParseLine($line);
			$rec=array();
			foreach($fields as $i=>$field){
				$rec[$field]=isset($parts[$i])?$parts[$i]:'';
			}
			//mem_usage is given in K with commas ("12,345 K", or "N/A") - keep digits only (PHP 8 warns on "12345 K")
			$rec['mem_usage']=(int)preg_replace('/[^0-9]+/','',$rec['mem_usage']);
			$rec['mem_usage']=$rec['mem_usage']*1000;
			$totals['mem']+=$rec['mem_usage'];
			//cpu_time is given in hh:mm:ss. Need to convert to %cpu.  (TotalProcessRuntime / CpuTime) / 100
			$parts=array_pad(preg_split('/\:/',$rec['cpu_time'],3),3,0);
			$rec['cpu_time']=(int)$parts[0]*3600+(int)$parts[1]*60+(int)$parts[2];
			$totals['cpu']+=$rec['cpu_time'];
			ksort($rec);
			$recs[]=$rec;
		}
		//pcpu = CURRENT cpu as a share of the whole machine, from the performance counters. (tasklist only knows cumulative
		//	cpu time since each process started, so a share of THAT made System Idle Process read 98% and the total ~100%.)
		//	If the counters are unavailable fall back to the cumulative share.
		$perf=systemWinProcessPerf();
		foreach($recs as $i=>$rec){
			$pid=(int)$rec['pid'];
			if(count($perf)){
				$recs[$i]['pcpu']=isset($perf[$pid])?$perf[$pid]['cpu']:0;
				$recs[$i]['threads']=isset($perf[$pid])?$perf[$pid]['threads']:'';
				$recs[$i]['handles']=isset($perf[$pid])?$perf[$pid]['handles']:'';
			}
			else{
				$recs[$i]['pcpu']=$totals['cpu']>0?round(($rec['cpu_time']/$totals['cpu'])*100,2):0;
			}
			$recs[$i]['pcpu']=number_format($recs[$i]['pcpu'],2);
			$recs[$i]['pmem']=$mem['total']>0?round(($rec['mem_usage']/$mem['total'])*100,2):0;
			$recs[$i]['pmem']=number_format($recs[$i]['pmem'],2);
		}
		return $recs;
	}
	//USER  PID %CPU %MEM VSZ  RSS TTY  STAT START   TIME COMMAND
	$cmd="ps aux --sort=-pcpu,-pmem,-vsz,-rss";
	$out=cmdResults($cmd);
	$lines=preg_split('/[\r\n]+/',$out['stdout']);
	//remove the first line
    $fields=preg_split('/[\t\s]+/',str_replace('%','p',strtolower(array_shift($lines))),11);
	$recs=array();
	foreach($lines as $line){
		$parts=preg_split('/[\t\s]+/',$line,11);
		$rec=array();
		foreach($fields as $i=>$field){
			$rec[$field]=$parts[$i];
		}
		//only show processes that are using cpu or memory
		if($rec['pcpu'] == 0 && $rec['pmem'] == 0 && $rec['vsz'] == 0 && $rec['rss'] == 0){
			break;
		}
		ksort($rec);
		$recs[]=$rec;
	}
	return $recs;
}
//---------- begin function systemGetPidInfo ----------
/**
* @describe returns process information about given process_ids
* @param pids mixed - array of processids or a string with comma separated process ids
* @return array
* @usage $irecs=systemGetPidInfo($pids);
*/
function systemGetPidInfo($pids=array()){
	if(!is_array($pids)){$pids=preg_split('/\,/',$pids);}
	if(isWindows()){
		$precs=array();
		foreach($pids as $pid){
			$cmd="tasklist /FI \"PID eq {$pid}\" /v /fo csv";
			$out=cmdResults($cmd);
			$recs=csv2Arrays($out['stdout'],array('-lowercase'=>1,'-nospaces'=>1,'-fieldmap'=>array('session#'=>'session_num')));
			$rec=$recs[0];
			//fix mem_usage
			if(isset($rec['mem_usage'])){
				$rec['mem_usage']=str_replace(',','',$rec['mem_usage']);
				if(preg_match('/\ k$/i',$rec['mem_usage'])){$rec['mem_usage']=(int)$rec['mem_usage']*1000;}
			}
			$precs[]=$rec;
		}
		return $precs;
	}
	else{
		/*
			USER       PID %CPU %MEM    VSZ   RSS TTY      STAT START   TIME COMMAND
			root       116  0.0  1.1  78664 45560 ?        Ss    2019  23:55 /lib/systemd/systemd-journald
		*/
		$pidstr=implode(',',$pids);
		$cmd="ps -fu -p {$pidstr}";
		$out=cmdResults($cmd);
		$out['stdout']=preg_replace('/\ +/',',',$out['stdout']);
		$recs=csv2Arrays($out['stdout'],array('-lowercase'=>1,'-nospaces'=>1,'-fieldmap'=>array('%cpu'=>'pcnt_cpu','%mem'=>'pcnt_mem')));
		return $recs;
	}
}
//---------- begin function getServerInfo
function getServerInfo(){
	//info: returns a array structure of load averages, cpu info, memory usage, os, and running processes
	if (strpos(strtolower(PHP_OS), 'win') === 0){return getServerInfoWindows();}
	else{return getServerInfoLinux();}
}
//---------- begin function getServerInfo
function getServerUptime(){
	//info: returns a array structure of load averages,
	if (strpos(strtolower(PHP_OS), 'win') === 0){
		$context=array();
		//current time 
		$context['current_time'] = strftime('%B %d, %Y, %I:%M:%S %p');
		//get uptime from net statistics workstation
		$rtn = @shell_exec('net statistics workstation');
		$lines=preg_split('/[\r\n]+/',trim($rtn));
		foreach($lines as $line){
			if(preg_match('/^Statistics\ since\ (.+)$/is',trim($line),$m)){
				$context['since']=trim($m[1]);
				$context['since_timestamp']=strtotime($context['since']);
				$context['since_date']=date('Y-m-d H:i:s',$context['since_timestamp']);
				$context['time']=time();
				$context['time_date']=date('Y-m-d H:i:s',$context['time']);
				$context['uptime']=$context['time']-$context['since_timestamp'];
				$context['uptime_verbose']=trim(verboseTime($context['uptime']));
				break;
			}
		}
	}
	else{
		$context=array();
		//current time
		$context['current_time'] = strftime('%B %d, %Y, %I:%M:%S %p');
		//load averages
		$context['load_averages'] = @implode('', @getServerInfoFileData('/proc/loadavg'));
		if (!empty($context['load_averages']) && preg_match('~^([^ ]+?) ([^ ]+?) ([^ ]+)~', $context['load_averages'], $matches) != 0){
			$context['load_averages'] = array($matches[1], $matches[2], $matches[3]);
		}
		elseif (($context['load_averages'] = @shell_exec('uptime 2>/dev/null')) != null && preg_match('~load average[s]?: (\d+\.\d+), (\d+\.\d+), (\d+\.\d+)~i', $context['load_averages'], $matches) != 0){
			$context['load_averages'] = array($matches[1], $matches[2], $matches[3]);
		}
		else{
			unset($context['load_averages']);
		}
		//uptime
		$uptime = @implode('', @getServerInfoFileData('/proc/uptime'));
		if (!empty($uptime)){
			list($uptime,$idletime)=preg_split('/\ /',$uptime);
			$uptime=(int)$uptime;
			$context['uptime']=$uptime;
			$context['uptime_verbose']=verboseTime($uptime);
		}
	}
	return $context;
}
//---------- begin function getServerInfoLinux
function getServerInfoLinux($show_process=0){
	$context=array();
	//current time
	$context['current_time'] = strftime('%B %d, %Y, %I:%M:%S %p');
	//load averages
	$context['load_averages'] = @implode('', @getServerInfoFileData('/proc/loadavg'));
	if (!empty($context['load_averages']) && preg_match('~^([^ ]+?) ([^ ]+?) ([^ ]+)~', $context['load_averages'], $matches) != 0){
		$context['load_averages'] = array($matches[1], $matches[2], $matches[3]);
		}
	elseif (($context['load_averages'] = @shell_exec('uptime 2>/dev/null')) != null && preg_match('~load average[s]?: (\d+\.\d+), (\d+\.\d+), (\d+\.\d+)~i', $context['load_averages'], $matches) != 0){
		$context['load_averages'] = array($matches[1], $matches[2], $matches[3]);
		}
	else{
		unset($context['load_averages']);
		}
	//uptime
	$uptime = @implode('', @getServerInfoFileData('/proc/uptime'));
	if (!empty($uptime)){
		list($uptime,$idletime)=preg_split('/\ /',$uptime);
		$uptime=(int)$uptime;
		$context['uptime']=$uptime;
		$context['uptime_verbose']=verboseTime($uptime);
		}
	//cpu info
	$context['cpu_info'] = array();
	$cpuinfo = @implode('', @getServerInfoFileData('/proc/cpuinfo'));
	if (!empty($cpuinfo)){
		// This only gets the first CPU!
		if (preg_match('~model name\s+:\s*([^\n]+)~i', $cpuinfo, $match) != 0){$context['cpu_info']['model'] = $match[1];}
		if (preg_match('~cpu mhz\s+:\s*([^\n]+)~i', $cpuinfo, $match) != 0){$context['cpu_info']['mhz'] = $match[1];}
		}
	else{
		// Solaris, perhaps?
		$cpuinfo = @shell_exec('psrinfo -pv 2>/dev/null');
		if (!empty($cpuinfo)){
			if (preg_match('~clock (\d+)~', $cpuinfo, $match) != 0){$context['cpu_info']['mhz'] = $match[1];}
			$cpuinfo = explode("\n", $cpuinfo);
			if (isset($cpuinfo[2])){$context['cpu_info']['model'] = trim($cpuinfo[2]);}
			}
		else{
			// BSD?
			$cpuinfo = @shell_exec('sysctl hw.model 2>/dev/null');
			if (preg_match('~hw\.model:(.+)~', $cpuinfo, $match) != 0){$context['cpu_info']['model'] = trim($match[1]);}
			$cpuinfo = @shell_exec('sysctl dev.cpu.0.freq 2>/dev/null');
			if (preg_match('~dev\.cpu\.0\.freq:(.+)~', $cpuinfo, $match) != 0){$context['cpu_info']['mhz'] = trim($match[1]);}
			}
		}
	//memory usage
	$context['memory_usage'] = array();
	function unix_memsize($str){
		$str = strtr($str, array(',' => ''));
		if (strtolower(substr($str, -1)) == 'g'){return $str * 1024 * 1024;}
		elseif (strtolower(substr($str, -1)) == 'm'){return $str * 1024;}
		elseif (strtolower(substr($str, -1)) == 'k'){return (int) $str;}
		else{return $str / 1024;}
		}
	$meminfo = @getServerInfoFileData('/proc/meminfo');
	if (!empty($meminfo)){
		if (preg_match('~:\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)~', $meminfo[1], $matches) != 0){
			$context['memory_usage']['total'] = $matches[1] / 1024;
			$context['memory_usage']['used'] = $matches[2] / 1024;
			$context['memory_usage']['free'] = $matches[3] / 1024;
			$context['memory_usage']['free_pcnt'] = round(($context['memory_usage']['free']/$context['memory_usage']['total'])*100,0);
			/*$context['memory_usage']['shared'] = $matches[4] / 1024;
			$context['memory_usage']['buffers'] = $matches[5] / 1024;
			$context['memory_usage']['cached'] = $matches[6] / 1024;*/
			}
		else{
			$mem = implode('', $meminfo);
			if (preg_match('~memtotal:\s*(\d+ [kmgb])~i', $mem, $match) != 0){
				$context['memory_usage']['total'] = unix_memsize($match[1]);
				}
			if (preg_match('~memfree:\s*(\d+ [kmgb])~i', $mem, $match) != 0){
				$context['memory_usage']['free'] = unix_memsize($match[1]);
				$context['memory_usage']['free_pcnt'] = round(($context['memory_usage']['free']/$context['memory_usage']['total'])*100,0);
				}
			if (isset($context['memory_usage']['total'], $context['memory_usage']['free'])){
				$context['memory_usage']['used'] = $context['memory_usage']['total'] - $context['memory_usage']['free'];
				}
			if (preg_match('~swaptotal:\s*(\d+ [kmgb])~i', $mem, $match) != 0){
				$context['memory_usage']['swap_total'] = unix_memsize($match[1]);
				}
			if (preg_match('~swapfree:\s*(\d+ [kmgb])~i', $mem, $match) != 0){
				$context['memory_usage']['swap_free'] = unix_memsize($match[1]);
				}
			if (isset($context['memory_usage']['swap_total'], $context['memory_usage']['swap_free'])){
				$context['memory_usage']['swap_used'] = $context['memory_usage']['swap_total'] - $context['memory_usage']['swap_free'];
				}

			}
		if (preg_match('~:\s+(\d+)\s+(\d+)\s+(\d+)~', $meminfo[2], $matches) != 0){
			$context['memory_usage']['swap_total'] = $matches[1] / 1024;
			$context['memory_usage']['swap_used'] = $matches[2] / 1024;
			$context['memory_usage']['swap_free'] = $matches[3] / 1024;
			}
		$meminfo = false;
		}
	// Maybe a generic free?
	elseif (empty($context['memory_usage'])){
		$meminfo = explode("\n", @shell_exec("free -k 2>/dev/null | awk '{ if (\$2 * 1 > 0) print \$2, \$3, \$4; }'"));
		if (!empty($meminfo[0])){
			$meminfo[0] = explode(' ', $meminfo[0]);
			$meminfo[1] = explode(' ', $meminfo[1]);
			$context['memory_usage']['total'] = $meminfo[0][0] / 1024;
			$context['memory_usage']['used'] = $meminfo[0][1] / 1024;
			$context['memory_usage']['free'] = $meminfo[0][2] / 1024;
			$context['memory_usage']['free_pcnt'] = round(($context['memory_usage']['free']/$context['memory_usage']['total'])*100,0);
			$context['memory_usage']['swap_total'] = $meminfo[1][0] / 1024;
			$context['memory_usage']['swap_used'] = $meminfo[1][1] / 1024;
			$context['memory_usage']['swap_free'] = $meminfo[1][2] / 1024;
			}
		}
	// Solaris, Mac OS X, or FreeBSD?
	if (empty($context['memory_usage'])){
		// Well, Solaris will have kstat.
		$meminfo = explode("\n", @shell_exec("kstat -p unix:0:system_pages:physmem unix:0:system_pages:freemem 2>/dev/null | awk '{ print \$2 }'"));
		if (!empty($meminfo[0])){
			$pagesize = shell_exec('/usr/bin/pagesize');
			$context['memory_usage']['total'] = unix_memsize($meminfo[0] * $pagesize);
			$context['memory_usage']['free'] = unix_memsize($meminfo[1] * $pagesize);
			$context['memory_usage']['free_pcnt'] = round(($context['memory_usage']['free']/$context['memory_usage']['total'])*100,0);
			$context['memory_usage']['used'] = $context['memory_usage']['total'] - $context['memory_usage']['free'];
			$meminfo = explode("\n", @shell_exec("swap -l 2>/dev/null | awk '{ if (\$4 * 1 > 0) print \$4, \$5; }'"));
			$context['memory_usage']['swap_total'] = 0;
			$context['memory_usage']['swap_free'] = 0;
			foreach ($meminfo as $memline){
				$memline = explode(' ', $memline);
				if (empty($memline[0])){continue;}
				$context['memory_usage']['swap_total'] += $memline[0];
				$context['memory_usage']['swap_free'] += $memline[1];
				}
			$context['memory_usage']['swap_used'] = $context['memory_usage']['swap_total'] - $context['memory_usage']['swap_free'];
			}
		}
	if (empty($context['memory_usage'])){
		// FreeBSD should have hw.physmem.
		$meminfo = @shell_exec('sysctl hw.physmem 2>/dev/null');
		if (!empty($meminfo) && preg_match('~hw\.physmem: (\d+)~i', $meminfo, $match) != 0){
			$context['memory_usage']['total'] = unix_memsize($match[1]);
			$meminfo = @shell_exec('sysctl hw.pagesize vm.stats.vm.v_free_count 2>/dev/null');
			if (!empty($meminfo) && preg_match('~hw\.pagesize: (\d+)~i', $meminfo, $match1) != 0 && preg_match('~vm\.stats\.vm\.v_free_count: (\d+)~i', $meminfo, $match2) != 0){
				$context['memory_usage']['free'] = $match1[1] * $match2[1] / 1024;
				$context['memory_usage']['free_pcnt'] = round(($context['memory_usage']['free']/$context['memory_usage']['total'])*100,0);
				$context['memory_usage']['used'] = $context['memory_usage']['total'] - $context['memory_usage']['free'];
				}
			$meminfo = @shell_exec("swapinfo 2>/dev/null | awk '{ print \$2, \$4; }'");
			if (preg_match('~(\d+) (\d+)~', $meminfo, $match) != 0){
				$context['memory_usage']['swap_total'] = $match[1];
				$context['memory_usage']['swap_free'] = $match[2];
				$context['memory_usage']['swap_used'] = $context['memory_usage']['swap_total'] - $context['memory_usage']['swap_free'];
				}
			}
		// Let's guess Mac OS X?
		else{
			$meminfo = @shell_exec('top -l1 2>/dev/null');
			if (!empty($meminfo) && preg_match('~PhysMem: (?:.+?) ([\d\.]+\w) used, ([\d\.]+\w) free~', $meminfo, $match) != 0){
				$context['memory_usage']['used'] = unix_memsize($match[1]);
				$context['memory_usage']['free'] = unix_memsize($match[2]);
				$context['memory_usage']['total'] = $context['memory_usage']['used'] + $context['memory_usage']['total'];
				$context['memory_usage']['free_pcnt'] = round(($context['memory_usage']['free']/$context['memory_usage']['total'])*100,0);
				}
			}
		$context['memory_usage']['used_pcnt']=100-$context['memory_usage']['free_pcnt'];
		}
	//operating system
	$context['operating_system']['type'] = 'unix';
	$check_release = array('centos', 'fedora', 'gentoo', 'redhat', 'slackware', 'yellowdog');
	foreach ($check_release as $os){
		if (@file_exists('/etc/' . $os . '-release')){
			$context['operating_system']['name'] = implode('', getServerInfoFileData('/etc/' . $os . '-release'));
			}
		}
	//get the os name and distribution info
	//$lines=cmdResults('lsb_release -a');
	if (isset($context['operating_system']['name'])){}
	elseif (@file_exists('/etc/debian_version')){
		$context['operating_system']['name'] = 'Debian ' . implode('', getServerInfoFileData('/etc/debian_version'));
		}
	elseif (@file_exists('/etc/SuSE-release')){
		$temp = getServerInfoFileData('/etc/SuSE-release');
		$context['operating_system']['name'] = trim($temp[0]);
		}
	elseif (@file_exists('/etc/release')){
		$temp = getServerInfoFileData('/etc/release');
		$context['operating_system']['name'] = trim($temp[0]);
		}
	else{
		$context['operating_system']['name'] = trim(@shell_exec('uname -s -r 2>/dev/null'));
		}
	if($show_process==0){return $context;}
	//running processes
	$context['running_processes'] = array();
	$processes = @shell_exec("ps auxc 2>/dev/null | awk '{ print \$2, \$3, \$4, \$8, \$11, \$12 }'");
	if (empty($processes)){
		$processes = @shell_exec("ps aux 2>/dev/null | awk '{ print \$2, \$3, \$4, \$8, \$11, \$12 }'");
		}
	// Maybe it's Solaris?
	if (empty($processes)){
		$processes = @shell_exec("ps -eo pid,pcpu,pmem,s,fname 2>/dev/null | awk '{ print \$1, \$2, \$3, \$4, \$5, \$6 }'");
		}
	// Okay, how about QNX?
	if (empty($processes)){
		$processes = @shell_exec("ps -eo pid,pcpu,comm 2>/dev/null | awk '{ print \$1, \$2, 0, \"\", \$5, \$6 }'");
		}
	if (!empty($processes)){
		$processes = explode("\n", $processes);
		$context['num_zombie_processes'] = 0;
		$context['num_sleeping_processes'] = 0;
		$context['num_running_processes'] = 0;
		$cnt=count($processes);
		for ($i = 1, $n = $cnt - 1; $i < $n; $i++){
			$proc = explode(' ', $processes[$i], 5);
			$additional = '';
			$statm=@getServerInfoFileData('/proc/' . $proc[0] . '/statm');
			if(is_array($statm)){$additional = @implode('', $statm);}
			if ($proc[4][0] != '[' && strpos($proc[4], ' ') !== false){
				$proc[4] = strtok($proc[4], ' ');
				}
			$context['running_processes'][$proc[0]] = array(
				'id' => $proc[0],
				'cpu' => $proc[1],
				'mem' => $proc[2],
				'title' => $proc[4],
				);

			if (strpos($proc[3], 'Z') !== false){$context['num_zombie_processes']++;}
			elseif (strpos($proc[3], 'S') !== false){$context['num_sleeping_processes']++;}
			else{$context['num_running_processes']++;}
			if (!empty($additional)){
				$additional = explode(' ', $additional);
				$context['running_processes'][$proc[0]]['mem_usage'] = $additional[0];
				}
			}
		$context['top_memory_usage'] = array('(other)' => array('name' => '(other)', 'percent' => 0, 'number' => 0));
		$context['top_cpu_usage'] = array('(other)' => array('name' => '(other)', 'percent' => 0, 'number' => 0));
		foreach ($context['running_processes'] as $proc){
			$id = basename($proc['title']);
			if (!isset($context['top_memory_usage'][$id])){
				$context['top_memory_usage'][$id] = array('name' => $id, 'percent' => $proc['mem'], 'number' => 1);
				}
			else{
				$context['top_memory_usage'][$id]['percent'] += $proc['mem'];
				$context['top_memory_usage'][$id]['number']++;
				}
			if (!isset($context['top_cpu_usage'][$id])){
				$context['top_cpu_usage'][$id] = array('name' => $id, 'percent' => $proc['cpu'], 'number' => 1);
				}
			else{
				$context['top_cpu_usage'][$id]['percent'] += $proc['cpu'];
				$context['top_cpu_usage'][$id]['number']++;
				}
			}

		// TODO: shared memory?
		foreach ($context['top_memory_usage'] as $proc){
			if ($proc['percent'] >= 1 || $proc['name'] == '(other)'){continue;}
			unset($context['top_memory_usage'][$proc['name']]);
			$context['top_memory_usage']['(other)']['percent'] += $proc['percent'];
			$context['top_memory_usage']['(other)']['number']++;
			}
		foreach ($context['top_cpu_usage'] as $proc){
			if ($proc['percent'] >= 0.6 || $proc['name'] == '(other)'){continue;}
			unset($context['top_cpu_usage'][$proc['name']]);
			$context['top_cpu_usage']['(other)']['percent'] += $proc['percent'];
			$context['top_cpu_usage']['(other)']['number']++;
			}
		}
	return $context;
	}
//---------- begin function getServerInfoWindows
function getServerInfoWindows(){
	$context=array();
	$context['current_time'] = strftime('%B %d, %Y, %I:%M:%S %p');
	//memory usage
	function windows_memsize($str){
		$str = strtr($str, array(',' => ''));
		if (strtolower(substr($str, -2)) == 'gb'){return $str * 1024 * 1024;}
		elseif (strtolower(substr($str, -2)) == 'mb'){return $str * 1024;}
		elseif (strtolower(substr($str, -2)) == 'kb'){return (int) $str;}
		elseif (strtolower(substr($str, -2)) == ' b'){return $str / 1024;}
		else{trigger_error('Unknown memory format \'' . $str . '\'', E_USER_NOTICE);}
		}
	$info=getSystemInfoWindows();
	if (is_array($info)){
		foreach($info as $key=>$val){
			$context[$key]=$val;
			}
		if(is_array($info['processors'])){
			$context['cpu_info'] = array();
			foreach($info['processors'] as $processor){
				if (preg_match('/(.+?) (\~?\d+) Mhz$/i', $processor, $match)){
					$context['cpu_info'][]=array(
						'model' => $match[1],
						'mhz' 	=> $match[2]
						);
					}
            	}
        	}
		//uptime
		$context['uptime'] = isset($info['system_up_time'])?$info['system_up_time']:'unknown';
		//memory
		$context['memory_usage'] = array();
		$context['memory_usage']['total'] = windows_memsize($info['total_physical_memory']);
		$context['memory_usage']['free'] = windows_memsize($info['available_physical_memory']);
		$context['memory_usage']['free_pcnt'] = round(($context['memory_usage']['free']/$context['memory_usage']['total'])*100,0);
		if (isset($context['memory_usage']['total'], $context['memory_usage']['free'])){
			$context['memory_usage']['used'] = $context['memory_usage']['total'] - $context['memory_usage']['free'];
			}
		$context['memory_usage']['swap_total'] = windows_memsize($info['virtual_memory_available']);
		$context['memory_usage']['swap_used'] = windows_memsize($info['virtual_memory_in_use']);
		if (isset($context['memory_usage']['swap_total'], $context['memory_usage']['swap_free'])){
			$context['memory_usage']['swap_free'] = $context['memory_usage']['swap_total'] - $context['memory_usage']['swap_used'];
			}
		}
	//operating system
	$context['operating_system']['type'] = 'windows';
	$context['operating_system']['name'] = shell_exec('ver');
	if (empty($context['operating_system']['name'])){$context['operating_system']['name'] = 'Microsoft Windows';}
	else{$context['operating_system']['name']=trim($context['operating_system']['name']);}
	//running processes
	$context['running_processes'] = array();
	//"Image Name","PID","Session Name","Session#","Mem Usage"
	return $context;
	}
//---------- begin function systemMountsList
/**
* @describe returns a list of all drives mounted on the server
* @usage
*	<?=systemMountsList();?>
*/
function systemMountsList(){
	//note: df is piped to a file then read to get around splitting issue on some platforms
	$path=getWasqlPath('php/temp');
	$outfile="{$path}/dfb1.txt";
	$cmd=<<<ENDOFCMD
df -B1 | awk '{print \$1","\$2","\$3","\$4","\$5","\$6","\$7","\$8}' >{$outfile}
ENDOFCMD;
	$cmd=trim($cmd);
	$out=shell_exec($cmd);
	if(is_file($outfile)){
		$lines=file($outfile);
		unlink($outfile);
		foreach($lines as $i=>$line){
			if($i==0){continue;}
			$line=trim($line);
			$line=preg_replace('/\,+$/','',$line);
			$parts=preg_split('/\,/',$line);
			if(count($parts) > 6){
				$c=count($parts) - 5;
				$xparts=array();
				for($x=0;$x<$c;$x++){
					$xparts[]=$parts[$x];
				}
				$nparts=array(implode(' ',$xparts));
				for($x=$c;$x<count($parts);$x++){
					$nparts[]=$parts[$x];
				}
				$lines[$i]=implode(',',$nparts);
			}
		}
	}
	$out=implode(PHP_EOL,$lines);
	$recs=CSV2Arrays($out);
	$recs=array_change_key_case($recs,CASE_LOWER);
	foreach($recs as $i=>$rec){
		$recs[$i]=array_change_key_case($recs[$i],CASE_LOWER);
		$recs[$i]['size']=$recs[$i]['1b-blocks'];
		unset($recs[$i]['1b-blocks']);	
	}
	return $recs;
	$opts=array(
		'-list'=>$recs,
		'-tableclass'=>'table table-responsive responsive bordered striped is-bordered is-striped is-fullwidth',
		'-hidesearch'=>1,
		'-listfields'=>'filesystem,1b-blocks,used,available,used_pcnt,mounted',
		'1b-blocks_displayname'=>'Size',
		'size_class'=>'align-right w_nowrap',
		'used_class'=>'align-right w_nowrap',
		'available_class'=>'align-right w_nowrap',
		'used_pcnt_class'=>'align-left w_nowrap'
	);
	return databaseListRecords($opts);
}
//---------- begin function nmap
function nmap($server,$ports){
	$cmd="nmap -r {$server}";
	if(strlen($ports)){$cmd="nmap -r -p {$ports} {$server}";}
	$tmp=cmdResults($cmd);
	$lines=preg_split('/[\r\n]+/',$tmp['stdout']);
	$info=array('name_in'=>$server,'nmap_cmd'=>$cmd);
	foreach($lines as $line){
		if(preg_match('/MAC Address: (.+?)/i',$line,$lm)){
			$info['mac_address']=preg_replace('/\(.+?\)/','',$lm[1]);
			}
		elseif(preg_match('/scanned in ([0-9\.]+)/',$line,$lm)){
			$info['scan_time']=$lm[1];
			}
		elseif(preg_match('/Host seems down/i',$line)){
			$info['error']=$line;
			}
		elseif(preg_match('/Interesting ports on (.+?)\(([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/i',$line,$lm)){
			$info['name_resolved']=trim($lm[1]);
			$info['ip_address']=$lm[2];
			}
		elseif(preg_match('/^[0-9]+/',$line)){
			$parts=preg_split('/[\s\t]+/',$line);
			$info['ports'][$parts[0]]=array(
				'type'=>preg_replace('/^([0-9]+?)\//','',$parts[0]),
				'state'=>$parts[1],
				'service'=>$parts[2]
				);
        	}
    	}
    ksort($info);
    $info['raw']=$tmp['stdout'];
    return $info;
	}
//---------- begin function getSystemInfoWindows
function getSystemInfoWindows(){
	//running systeminfo /fo csv crashes on xp pro sp3.. so we use list instead
	$systeminfo = @shell_exec('systeminfo /fo list');
	$lines=preg_split('/[\r\n]+/',trim($systeminfo));
	$lastkey='';
	$info=array();
	$ncard=array();
	foreach($lines as $l=>$line){
		$parts=preg_split('/\:/',trim($line),2);
		foreach($parts as $p=>$part){$parts[$p]=trim($part);}
		$oparts=$parts;
		$parts[0]=preg_replace('/\(s\)/i','',trim($parts[0]));
		if(preg_match('/(date|directory|time zone|file location)$/i',$parts[0])){
			$key=array_shift($parts);
			$val=implode(':',$parts);
		}
		else{
			$val=array_pop($parts);
			$key=implode('_',$parts);
        }
		$key=preg_replace('/\((s|es)\)/i','',trim($key));
		$key=preg_replace('/[^a-z0-9]+/i','_',trim($key));
		$key=preg_replace('/^\_/i','',trim($key));
		$key=preg_replace('/\_$/i','',trim($key));
		if(preg_match('/Hotfix/i',$key)){
			$lastkey='Hotfix';
			continue;
		}
		$line .= " [[{$key}]][[{$lastkey}]]";
		if(preg_match('/^[0-9]+$/',$key) && preg_match('/Hotfix/i',$lastkey)){continue;}
		$key=strtolower($key);
		$val=trim($val);
		if(!strlen($val)){continue;}
		if(preg_match('/Processor/i',$lastkey)){
			if(preg_match('/^([0-9]+)$/',$key)){
				$info['processors'][]=$val;
				continue;
				}
			else{$lastkey=$key;}
			}
		elseif(preg_match('/NetWork_Card/i',$lastkey)){
			if(preg_match('/^([0-9]+)$/',$key)){
				if(preg_match('/^([0-9\.]+)$/',$val)){$key='ip_address';}
				else{
					if(count($ncard)){
						$info['network_cards'][]=$ncard;
						$ncard=array();
						$key="name";
                        	}
					else{$key="name";}
					}
				}
			if(strlen($key)){$ncard[$key]=$val;}
			continue;
			}
		else{$info[$key]=$val;}
		$lastkey=$key;
        }
    if(count($ncard)){$info['network_cards'][]=$ncard;}
    unset($info['processor']);
    unset($info['network_card']);
    return $info;
	}
//---------- begin function getServerInfoFileData
function getServerInfoFileData($filename){
	if(!is_file($filename)){return false;}
	$data = @file($filename);
	if (is_array($data)){return $data;}
	if (strpos(strtolower(PHP_OS), 'win') !== false){
		@exec('type ' . preg_replace('~[^/a-zA-Z0-9\-_:]~', '', $filename), $data);
	}
	else{
		@exec('cat ' . preg_replace('~[^/a-zA-Z0-9\-_:]~', '', $filename) . ' 2>/dev/null', $data);
	}
	if (!is_array($data)){return false;}
	foreach ($data as $k => $dummy){
		$data[$k] .= "\n";
	}
	return $data;
}
//---------- begin function svnGetInfo
function svnGetInfo(){
	//svn status --verbose --show-updates --non-recursive index.php
	$wpath=getWasqlPath();
	$progpath=dirname(__FILE__);
	$progfile="/index.php";
	if(preg_match('/\\+/',$progpath)){$progfile="\\index.php";}
	$cmd="svn status --verbose --show-updates --non-recursive {$progpath}{$progfile}";
	$res = cmdResults($cmd);
	if(isset($res['stdout'])){
		$info=array();
		$info['cmd1']=$cmd;
		$info['out1']=$res['stdout'];
		$lines=preg_split('/[\r\n]+/',trim($res['stdout']));
		$first=$lines[0];
		$lastindex=count($lines)-1;
		$last=$lines[$lastindex];
		$firstparts=preg_split('/[\t\s]+/',trim($first));
		$lastparts=preg_split('/[\t\s]+/',trim($last));
		$info['local']=$firstparts[0];
		$lastindex=count($lastparts)-1;
		$info['head']=$lastparts[$lastindex];
		if($info['local'] != $info['head']){$info['update']=1;}
		else{$info['update']=0;}
		if($info['update']==1){
			//what files have changed?
			//svn merge --dry-run -r BASE:HEAD .
			$map=array(
				'A' => "Added",
				'D' => "Deleted",
				'U'	=> "Updated",
				'C' => "Conflict",
				'G'	=> "Merged"
				);
			$cmd="svn merge --dry-run -r BASE:HEAD \"{$wpath}\"";
			$res = cmdResults($cmd);
			//echo printValue($res);
			if(isset($res['stdout'])){
				$info['cmd2']=$cmd;
				$info['out2']=$res['stdout'];
				$lines=preg_split('/[\r\n]+/',trim($res['stdout']));
				$changes=array();
				foreach($lines as $line){
					$parts=preg_split('/[\t\s]+/',trim($line));
					if(isset($map[$parts[0]])){
						$changes[]=array(
							'code'	=> $parts[0],
							'file'	=> $parts[1],
							'code_ex'=>$map[$parts[0]]
							);
						}
                	}
                if(count($changes)){
					$info['changes']=$changes;
					}
				}
        	}
		return $info;
		}
	return null;
	}
//---------- begin function svnInfo
function svnInfo(){
	//info: get information about if this revision is up to date
	$info=array();
	//get local revision info
	$wpath=getWasqlPath();
	$cmd='svn info "'.$wpath.'"';
	$res = cmdResults($cmd);
	if(strlen($res['stdout'])){
		$lines=preg_split('/[\r\n]+/',trim($res['stdout']));
		foreach($lines as $line){
			list($key,$val)=preg_split('/\:/',trim($line),2);
			$key=str_replace(' ','_',strtolower(trim($key)));
			$info['local'][$key]=$val;
        	}
        ksort($info['local']);
    	}
    //get HEAD info
    $cmd='svn info http://www.wasql.com/svn_wasql';
	$res = cmdResults($cmd);
	if(strlen($res['stdout'])){
		$lines=preg_split('/[\r\n]+/',trim($res['stdout']));
		foreach($lines as $line){
			list($key,$val)=preg_split('/\:/',trim($line),2);
			$key=str_replace(' ','_',strtolower(trim($key)));
			$info['head'][$key]=$val;
        	}
        ksort($info['head']);
    	}
    //echo printValue($info);
	return $info;
	}
//---------- begin function mountsInfo
function mountsInfo(){
	//info: returns drive info for cifs mounts
	$cmd="df -PT --sync -t cifs";
	$tmp=cmdResults($cmd);
	$lines=preg_split('/[\r\n]+/',$tmp['stdout']);
	$mounts=array();
	foreach($lines as $line){
		if(!preg_match('/^\/\//',$line)){continue;}
		$cols=array('filesystem','type','bytes','bytes_used','bytes_free','pcnt_used','mount');
		$parts=preg_split('/[\s\t]+/',trim($line));
		$mount=array();
		$cnt=count($parts);
		for($x=0;$x<$cnt;$x++){
			$mount[$cols[$x]]=$parts[$x];
        }
        $tmp=preg_replace('/^\/\//','',$mount['filesystem']);
        $parts=preg_split('/\//',$tmp,2);
        $mount['server']=$parts[0];
        $mount['path']=$parts[1];
        $mount['pcnt_used']=(int)(str_replace('%','',$mount['pcnt_used']));
        $mount['bytes']=round(($mount['bytes']*1024),0);
        $mount['bytes_used']=round(($mount['bytes_used']*1024),0);
        $mount['bytes_free']=round(($mount['bytes_free']*1024),0);
        $mount['bytes_verbose']=verboseSize($mount['bytes']);
        $mount['bytes_used_verbose']=verboseSize($mount['bytes_used']);
        $mount['bytes_free_verbose']=verboseSize($mount['bytes_free']);
        $mount['pcnt_free']=100-$mount['pcnt_used'];
        ksort($mount);
        $mounts[]=$mount;
	}
    ksort($mounts);
    return $mounts;
}
//---------- begin function getProcessCount
function getProcessCount($process){
	if(isWindows()){
		// arg /C return the count only
    	$cmd="tasklist | find /I /C \"{$process}\"";
    	$results=cmdResults($cmd);
    	$lines=preg_split('/[\r\n]+/',$results['stdout']);
    	return $lines[0];
	}
	$cmd="ps aux|grep {$process}";
	$results=cmdResults($cmd);
	$lines=preg_split('/[\r\n]+/',$results['stdout']);
	$cnt=0;
	foreach($lines as $line){
    	if(preg_match('/grep/i',$line)){continue;}
		if(preg_match('/'.$process.'/i',$line)){$cnt++;}
	}
	return $cnt;
}
//---------- begin function getProcessList
function getProcessList($process){
	if(isWindows()){
    	$cmd="tasklist | find /I \"{$process}\"";
    	$results=cmdResults($cmd);
    	$lines=preg_split('/[\r\n]+/',$results['stdout']);
    	return $lines;
	}
	$cmd="ps aux|grep {$process}";
	$results=cmdResults($cmd);
	$xlines=preg_split('/[\r\n]+/',$results['stdout']);
	$cnt=0;
	$lines=array();
	foreach($xlines as $line){
    	if(preg_match('/grep/i',$line)){continue;}
		if(preg_match('/'.$process.'/i',$line)){$lines[]=$line;}
	}
	return $lines;
}
//---------- begin function getProcessCountXML
function getProcessCountXML($process){
	$rtn=xmlHeader();
	$rtn .= '<main>'."\n";
	if(strlen(trim($process))){
		$cnt=getProcessCount(trim($process));
		$rtn .= "	<{$process}>{$cnt}</{$process}>\n";
	}
	else{
    	$rtn .= '	<error>No Process Specified</error>'."\n";
	}
	$rtn .= '</main>'."\n";
	return $rtn;
}
?>
