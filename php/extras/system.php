<?php
/* References:
	Server/System functions to get information about the server we are running on
*/
//---------- begin function getServerInfo
function getServerInfo(){
	//info: returns a array structure of load averages, cpu info, memory usage, os, and running processes
	if (strpos(strtolower(PHP_OS), 'win') === 0){return getServerInfoWindows();}
	else{return getServerInfoLinux();}
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
	elseif (($context['load_averages'] = @`uptime 2>/dev/null`) != null && preg_match('~load average[s]?: (\d+\.\d+), (\d+\.\d+), (\d+\.\d+)~i', $context['load_averages'], $matches) != 0){
		$context['load_averages'] = array($matches[1], $matches[2], $matches[3]);
		}
	else{
		unset($context['load_averages']);
		}
	//uptime
	$uptime = @implode('', @getServerInfoFileData('/proc/uptime'));
	if (!empty($uptime)){
		list($uptime,$idletime)=preg_split('/\ /',$uptime);
		$uptime=(integer)$uptime;
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
		$cpuinfo = @`psrinfo -pv 2>/dev/null`;
		if (!empty($cpuinfo)){
			if (preg_match('~clock (\d+)~', $cpuinfo, $match) != 0){$context['cpu_info']['mhz'] = $match[1];}
			$cpuinfo = explode("\n", $cpuinfo);
			if (isset($cpuinfo[2])){$context['cpu_info']['model'] = trim($cpuinfo[2]);}
			}
		else{
			// BSD?
			$cpuinfo = @`sysctl hw.model 2>/dev/null`;
			if (preg_match('~hw\.model:(.+)~', $cpuinfo, $match) != 0){$context['cpu_info']['model'] = trim($match[1]);}
			$cpuinfo = @`sysctl dev.cpu.0.freq 2>/dev/null`;
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
		$meminfo = explode("\n", @`free -k 2>/dev/null | awk '{ if ($2 * 1 > 0) print $2, $3, $4; }'`);
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
		$meminfo = explode("\n", @`kstat -p unix:0:system_pages:physmem unix:0:system_pages:freemem 2>/dev/null | awk '{ print $2 }'`);
		if (!empty($meminfo[0])){
			$pagesize = `/usr/bin/pagesize`;
			$context['memory_usage']['total'] = unix_memsize($meminfo[0] * $pagesize);
			$context['memory_usage']['free'] = unix_memsize($meminfo[1] * $pagesize);
			$context['memory_usage']['free_pcnt'] = round(($context['memory_usage']['free']/$context['memory_usage']['total'])*100,0);
			$context['memory_usage']['used'] = $context['memory_usage']['total'] - $context['memory_usage']['free'];
			$meminfo = explode("\n", @`swap -l 2>/dev/null | awk '{ if ($4 * 1 > 0) print $4, $5; }'`);
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
		$meminfo = @`sysctl hw.physmem 2>/dev/null`;
		if (!empty($meminfo) && preg_match('~hw\.physmem: (\d+)~i', $meminfo, $match) != 0){
			$context['memory_usage']['total'] = unix_memsize($match[1]);
			$meminfo = @`sysctl hw.pagesize vm.stats.vm.v_free_count 2>/dev/null`;
			if (!empty($meminfo) && preg_match('~hw\.pagesize: (\d+)~i', $meminfo, $match1) != 0 && preg_match('~vm\.stats\.vm\.v_free_count: (\d+)~i', $meminfo, $match2) != 0){
				$context['memory_usage']['free'] = $match1[1] * $match2[1] / 1024;
				$context['memory_usage']['free_pcnt'] = round(($context['memory_usage']['free']/$context['memory_usage']['total'])*100,0);
				$context['memory_usage']['used'] = $context['memory_usage']['total'] - $context['memory_usage']['free'];
				}
			$meminfo = @`swapinfo 2>/dev/null | awk '{ print $2, $4; }'`;
			if (preg_match('~(\d+) (\d+)~', $meminfo, $match) != 0){
				$context['memory_usage']['swap_total'] = $match[1];
				$context['memory_usage']['swap_free'] = $match[2];
				$context['memory_usage']['swap_used'] = $context['memory_usage']['swap_total'] - $context['memory_usage']['swap_free'];
				}
			}
		// Let's guess Mac OS X?
		else{
			$meminfo = @`top -l1 2>/dev/null`;
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
		$context['operating_system']['name'] = trim(@`uname -s -r 2>/dev/null`);
		}
	if($show_process==0){return $context;}
	//running processes
	$context['running_processes'] = array();
	$processes = @`ps auxc 2>/dev/null | awk '{ print $2, $3, $4, $8, $11, $12 }'`;
	if (empty($processes)){
		$processes = @`ps aux 2>/dev/null | awk '{ print $2, $3, $4, $8, $11, $12 }'`;
		}
	// Maybe it's Solaris?
	if (empty($processes)){
		$processes = @`ps -eo pid,pcpu,pmem,s,fname 2>/dev/null | awk '{ print $1, $2, $3, $4, $5, $6 }'`;
		}
	// Okay, how about QNX?
	if (empty($processes)){
		$processes = @`ps -eo pid,pcpu,comm 2>/dev/null | awk '{ print $1, $2, 0, "", $5, $6 }'`;
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
			if ($proc[4]{0} != '[' && strpos($proc[4], ' ') !== false){
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
		$context['uptime'] = $info['system_up_time'];
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
	$context['operating_system']['name'] = `ver`;
	if (empty($context['operating_system']['name'])){$context['operating_system']['name'] = 'Microsoft Windows';}
	else{$context['operating_system']['name']=trim($context['operating_system']['name']);}
	//running processes
	$context['running_processes'] = array();
	$processes = @`tasklist /fo csv /v /nh`;
	if (!empty($processes)){
		$processes = explode("\n", $processes);
		$total_mem = 0;
		$total_cpu = 0;
		$context['num_zombie_processes'] = 0;
		$context['num_sleeping_processes'] = 0;
		$context['num_running_processes'] = 0;
		foreach ($processes as $proc){
			if (empty($proc)){continue;}
			$proc = explode('","', substr($proc, 1, -1));
			$proc[7] = explode(':', $proc[7]);
			$proc[7] = ($proc[7][0] * 3600) + ($proc[7][1] * 60) + $proc[7][2];
			if (substr($proc[4], -1) == 'K'){$proc[4] = (int) $proc[4];}
			elseif (substr($proc[4], -1) == 'M'){$proc[4] = $proc[4] * 1024;}
			elseif (substr($proc[4], -1) == 'G'){$proc[4] = $proc[4] * 1024 * 1024;}
			else{$proc[4] = $proc[4] / 1024;}
			$context['running_processes'][$proc[1]] = array(
				'id' => $proc[1],
				'cpu_time' => $proc[7],
				'mem_usage' => $proc[4],
				'title' => $proc[0],
				);
			if (strpos($proc[5], 'Not') !== false){$context['num_zombie_processes']++;}
			else{$context['num_running_processes']++;}
			$total_mem += $proc[4];
			$total_cpu += $proc[7];
			}
		foreach ($context['running_processes'] as $proc){
			$context['running_processes'][$proc['id']]['cpu'] = ($proc['cpu_time'] * 100) / $total_cpu;
			$context['running_processes'][$proc['id']]['mem'] = ($proc['mem_usage'] * 100) / $total_mem;
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
	$systeminfo = @`systeminfo /fo list`;
	$lines=preg_split('/[\r\n]+/',trim($systeminfo));
	$lastkey='';
	$info=array();
	$ncard=array();
	foreach($lines as &$line){
		$parts=preg_split('/\:/',trim($line));
		foreach($parts as &$part){$part=trim($part);}
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
        $mount['pcnt_used']=(integer)(str_replace('%','',$mount['pcnt_used']));
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