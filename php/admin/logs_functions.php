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
			$results='';
			if(isWindows()){
				$cmd="tail -n 30 \"{$v}\"";
				$out=cmdResults($cmd);
				//$results=$cmd.PHP_EOL;
				if(strlen($out['stdout'])){
					$results.=$out['stdout'];
				}
				if(strlen($out['stderr'])){
					$results.=PHP_EOL.$out['stderr'];
				}
			}
			else{
				setFileContents("{$tempdir}/wasql.tail",$v);
				//wait for the update.log file
				$startwait=microtime(true);
				while(microtime(true)-$startwait < 30){
					if(file_exists("{$tempdir}/wasql.tail.log")){
						$results=getFileContents("{$tempdir}/wasql.tail.log");
						unlink("{$tempdir}/wasql.tail.log");
						break;
					}
					sleep(1);
				}
			}
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
				'file'=>$v,
				'key'=>$lk,
				'tail'=>implode(PHP_EOL,$lines)
			);
		}
	}
	return $logs;
}
?>