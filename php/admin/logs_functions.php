<?php
function logsGetLogs($includes=array(),$excludes=array()){
	global $CONFIG;
	$logs=array();
	$rowcount=isset($CONFIG['logs_rowcount'])?(integer)$CONFIG['logs_rowcount']:100;
	$tempdir=getWasqlPath('php/temp');
	foreach($CONFIG as $k=>$v){
		$lk=strtolower($k);
		if(strtolower($k)=='logs_rowcount'){continue;}
		if(strtolower($k)=='logs_refresh'){continue;}

		if(preg_match('/^logs\_(.+)$/is',$k,$m)){
			if(!file_exists($v)){
				echo "Logs File error for {$k}  - no such file or no access: {$v}<br>";
				continue;
			}
			$fname=getFileName($v);
			$aname="{$tempdir}/{$fname}";
			if(!file_exists($aname)){
				echo "Logs File error for {$k} - cron must not be running: {$aname}<br>";
				continue;
			}
			$results=getFileContents($aname);
			$lines=preg_split('/[\r\n]+/',$results);
			$lines=array_reverse($lines);
			foreach($lines as $i=>$line){
				if(count($includes)){
					$found=0;
					foreach($includes as $include){
						if(stringContains($line,$include)){
							$found+=1;
						}
					}
					if($found==0){
						unset($lines[$i]);
						continue;
					}
				}
				if(count($excludes)){
					$found=0;
					foreach($excludes as $exclude){
						if(stringContains($line,$exclude)){
							$found+=1;
						}
					}
					if($found!=0){
						unset($lines[$i]);
						continue;
					}
				}
				$flag='';
				if(stringContains($line,'fatal errror:') || stringContains($line,':fatal')){
					$flag='<span class="icon-circle w_danger w_blink"></span> ';
				}
				elseif(stringContains($line,'warning:') || stringContains($line,':warn')){
					$flag='<span class="icon-circle w_warning"></span> ';
				}
				elseif(stringContains($line,'error:') || stringContains($line,':error')){
					$flag='<span class="icon-circle w_danger"></span> ';
				}
				elseif(stringContains($line,'notice:') || stringContains($line,':notice')){
					$flag='<span class="icon-circle w_info"></span> ';
				}
				$lines[$i]="<div style=\"padding:2px;margin-bottom:3px;\">{$flag}{$line}</div>";
			}
			//echo $v.printValue($out);exit;
			$logs[$lk]=array(
				'name'=>$m[1],
				'file'=>$aname,
				'mtime'=>filemtime($aname),
				'key'=>$lk,
				'tail'=>implode(PHP_EOL,$lines)
			);
			$logs[$lk]['age']=time()-$logs[$lk]['mtime'];
			$logs[$lk]['age_verbose']=verboseTime($logs[$lk]['age']);
		}
	}
	return $logs;
}
?>