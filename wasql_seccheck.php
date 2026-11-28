<?php
/*
	wasql_seccheck.php - command-line front end for the WaSQL page-code security scanner.
	The engine lives in php/admin/security_functions.php (also used by the backend menu
	"Security Scanner", /php/admin.php?_menu=security). This CLI scans PostEdit mirror files
	(postedit/postEditFiles/{alias}/{table}/{record}/...) or any folder/file, no database needed.

	Usage:
	  php wasql_seccheck.php                          every mirror alias (skips *_bak)
	  php wasql_seccheck.php byuward apptly           just these aliases
	  php wasql_seccheck.php --path=some/folder       any folder or single file
	  php wasql_seccheck.php byuward --min=medium --types=sqli,exec
	  php wasql_seccheck.php --format=json            (also: csv, text)
	  php wasql_seccheck.php --summary                per-alias counts only
	  php wasql_seccheck.php --include-bak            also scan *_bak folders
	  php wasql_seccheck.php --root=D:/other/mirror   mirror root (default postedit/postEditFiles)

	Exit codes: 0 = nothing at/above --min, 1 = findings reported, 2 = usage error.
	See seccheck.md.
*/
if(PHP_SAPI!=='cli'){echo "CLI only\n";exit(2);}
require_once(__DIR__.'/php/admin/security_functions.php');

$opts=array('root'=>__DIR__.'/postedit/postEditFiles','path'=>'','min'=>'low','types'=>'','format'=>'text','summary'=>false,'include-bak'=>false);
$aliases=array();
foreach(array_slice($argv,1) as $arg){
	if(preg_match('/^--([a-z\-]+)(?:=(.*))?$/',$arg,$m)){
		$k=$m[1];
		if(!array_key_exists($k,$opts)){fwrite(STDERR,"unknown option --{$k}\n");exit(2);}
		$opts[$k]=isset($m[2])?$m[2]:true;
	}
	elseif(in_array($arg,array('-h','--help','/?'))){
		echo preg_replace('/^.*?Usage:/s','Usage:',substr(file_get_contents(__FILE__),0,1800)),"\n";exit(0);
	}
	else{$aliases[]=$arg;}
}
$sevs=array('low'=>0,'medium'=>1,'high'=>2);
if(!isset($sevs[$opts['min']])){fwrite(STDERR,"--min must be low, medium or high\n");exit(2);}
$alltypes=array('sqli','xss','exec','include','redirect','mailheader','auth');
$types=$alltypes;
if(strlen($opts['types'])){
	$types=array_filter(array_map('trim',explode(',',$opts['types'])));
	$bad=array_diff($types,$alltypes);
	if(count($bad)){fwrite(STDERR,'unknown type(s): '.implode(', ',$bad)."\n");exit(2);}
}
if(!in_array($opts['format'],array('text','json','csv'))){fwrite(STDERR,"--format must be text, json or csv\n");exit(2);}

//targets: array(alias, base path, path that file labels are relative to)
$targets=array();
if(strlen($opts['path'])){
	$p=realpath($opts['path']);
	if($p===false){fwrite(STDERR,"no such path: {$opts['path']}\n");exit(2);}
	$targets[]=array(basename(rtrim($p,'\\/')),$p,dirname($p));
}
else{
	$root=realpath($opts['root']);
	if($root===false || !is_dir($root)){fwrite(STDERR,"mirror root not found: {$opts['root']}\n");exit(2);}
	$available=array();
	foreach(scandir($root) as $d){
		if($d[0]!='.' && is_dir("{$root}/{$d}")){$available[]=$d;}
	}
	if(count($aliases)){
		$missing=array_diff($aliases,$available);
		if(count($missing)){fwrite(STDERR,'unknown alias(es): '.implode(', ',$missing)."\n");exit(2);}
		$names=$aliases;
	}
	else{
		$names=array();
		foreach($available as $d){
			if($opts['include-bak'] || substr($d,-4)!='_bak'){$names[]=$d;}
		}
	}
	foreach($names as $a){$targets[]=array($a,"{$root}/{$a}",$root);}
}

/**
* @describe group scannable files under $base by folder (one folder = one record)
* @param base string - folder or single file
* @param relbase string - labels are relative to this path
* @return array - groups for securityScanGroups
* @usage $groups=seccheckCollect('D:/wasql/postedit/postEditFiles/byuward','D:/wasql/postedit/postEditFiles');
*/
function seccheckCollect($base,$relbase){
	$byDir=array();
	$relbase=rtrim(str_replace('\\','/',$relbase),'/').'/';
	$add=function($path) use (&$byDir,$relbase){
		if(!preg_match('/\.(php|html?)$/i',$path)){return;}
		$text=@file_get_contents($path);
		if($text===false || strpos($text,'<?')===false){return;}
		$norm=str_replace('\\','/',$path);
		$rel=strpos($norm,$relbase)===0?substr($norm,strlen($relbase)):$norm;
		$byDir[dirname($norm)][]=array('label'=>$rel,'text'=>$text,'meta'=>array());
	};
	if(is_file($base)){$add($base);}
	else{
		$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS));
		foreach($it as $f){
			if($f->isFile()){$add($f->getPathname());}
		}
	}
	ksort($byDir);
	$normbase=rtrim(str_replace('\\','/',is_file($base)?dirname($base):$base),'/').'/';
	$groups=array();
	foreach($byDir as $dir=>$files){
		$record=strpos($dir.'/',$normbase)===0?rtrim(substr($dir.'/',strlen($normbase)),'/'):$dir;
		$groups[]=array('record'=>strlen($record)?$record:'.','files'=>$files);
	}
	return $groups;
}

$all=array();
$byAlias=array();
foreach($targets as $t){
	list($alias,$base,$relbase)=$t;
	$byAlias[$alias]=array();
	foreach(securityScanGroups(seccheckCollect($base,$relbase)) as $f){
		if(!in_array($f['type'],$types) || $sevs[$f['severity']]<$sevs[$opts['min']]){continue;}
		$f=array('alias'=>$alias)+$f;
		$byAlias[$alias][]=$f;
		$all[]=$f;
	}
}

$fields=array('severity','type','alias','record','file','line','var','origin','snippet');
if($opts['format']=='json'){
	$out=array();
	foreach($all as $f){
		$row=array();
		foreach($fields as $k){$row[$k]=$f[$k];}
		$out[]=$row;
	}
	echo json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE),"\n";
}
elseif($opts['format']=='csv'){
	$fh=fopen('php://output','w');
	fputcsv($fh,$fields);
	foreach($all as $f){
		$row=array();
		foreach($fields as $k){$row[]=$f[$k];}
		fputcsv($fh,$row);
	}
	fclose($fh);
}
else{
	$tot=array('high'=>0,'medium'=>0,'low'=>0);
	foreach($byAlias as $alias=>$list){
		$c=array('high'=>0,'medium'=>0,'low'=>0);
		foreach($list as $f){$c[$f['severity']]++;$tot[$f['severity']]++;}
		if(!count($list) && $opts['summary']){continue;}
		printf("== %s: %d finding(s)  high=%d medium=%d low=%d ==\n",$alias,count($list),$c['high'],$c['medium'],$c['low']);
		if($opts['summary']){continue;}
		foreach($list as $f){
			$via=$f['origin']=='direct'?$f['var']:"{$f['var']}  <- {$f['origin']}";
			printf("%-6s %-8s %s:%d  %s\n       %s\n",strtoupper($f['severity']),$f['type'],$f['file'],$f['line'],$via,$f['snippet']);
		}
		echo "\n";
	}
	printf("TOTAL %d finding(s)  high=%d medium=%d low=%d  across %d target(s)\n",count($all),$tot['high'],$tot['medium'],$tot['low'],count($targets));
}
exit(count($all)?1:0);
