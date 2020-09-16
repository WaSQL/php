<?php
/*
	backup script to automate backing up database and files
	Use:
		crontab entry example
			#Database Backups every day at 9:01am
			1 9 * * * php /var/www/wasql_live/php/backup.php  2>&1
		CONFIG SETTINGS:
			backup="1" - REQUIRED - will skip if not set to 1 or true
			backup_dir="/var/www/bkup" - directory to backup files to (defaults to sh/backup)
			backup_retain_days="10" - keeps backup for 10 days (defaults to 5 days)
			backup_folder[_x]="/var/www/shared" - folder to backup. change x to a number or comma separate folders. No default
			backup_email="you@yourdomain.com" - if set, send backup files to this email also
			backup_email_days="1,15" - if set only send backup files to email on these days
			backup_email_subject="backup for mysite" - defaults to backup for {sitename}
*/
ini_set('max_execution_time', 72000);
set_time_limit(72000);
error_reporting(E_ALL & ~E_NOTICE);
$starttime=microtime(true);
$progpath=dirname(__FILE__);
global $logfile;
global $CONFIG;
global $ALLCONFIG;
global $backupfile;
$_SERVER['HTTP_HOST']='localhost';
$scriptname=basename(__FILE__, '.php');
$logfile="{$progpath}/{$scriptname}.log";
$backupfile="{$progpath}/{$scriptname}.last";
if(file_exists($backupfile)){
	unlink($backupfile);
}
include_once("{$progpath}/common.php");
include_once("{$progpath}/config.php");
$cdate=date('Ymd_his');
foreach($ALLCONFIG as $site=>$host){
	$sendopts=array();
	$filenames=array();
	if(!isset($host['backup'])){continue;}
	if(!in_array($host['backup'],array(1,'true'))){continue;}
	$ok=backupMessage("*** Begin backup for {$site} ***");
	if(!isset($host['backup_dir'])){
		$host['backup_dir']=getWasqlPath('sh/backup');
	}
	if(!is_dir($host['backup_dir'])){
		$ok=buildDir($host['backup_dir'],0777,true);
	}
	$filename="{$host['dbname']}_{$cdate}.sql.gz";
	$outfile="{$host['backup_dir']}/{$filename}";
	$cmd="mysqldump --user={$host['dbuser']} --host={$host['dbhost']} --password={$host['dbpass']} --max_allowed_packet=128M {$host['dbname']} | gzip -9 > {$outfile}";
	$ok=backupMessage($cmd);
	$out=cmdResults($cmd);
	if(file_exists($outfile)){
		appendFileContents($backupfile,$filename.PHP_EOL);
		if(isset($host['backup_email']) && isEmail($host['backup_email'])){
			$sendopts['attach']=array($outfile);
			$filenames[]=$filename;
		}
	}
	//folders?
	$folders=array();
	foreach($host as $k=>$v){
		if(stringBeginsWith($k,'backup_folder')){
			$cdirs=preg_split('/[\,\;]+/',$v);
			foreach($cdirs as $cdir){
				if(is_dir($cdir) && !in_array($cdir,$folders)){
					$folders[]=$cdir;
				}
			}
		}
	}
	foreach($folders as $folder){
		$filename=str_replace('/',' ',$folder);
		$filename=trim($filename);
		$filename=str_replace(' ','_',$filename);
		$filename.="_{$cdate}.tar.gz";
		$outfile="{$host['backup_dir']}/{$filename}";
		$cmd="tar -cvzf {$outfile} {$folder}/";
		$ok=backupMessage($cmd);
		$out=cmdResults($cmd);
		if(file_exists($outfile)){
			appendFileContents($backupfile,$filename.PHP_EOL);
			if(isset($host['backup_email']) && isEmail($host['backup_email'])){
				$sendopts['attach'][]=$outfile;
			}
		}
	}
	//cleanup
	if(!isset($host['backup_retain_days'])){$host['backup_retain_days']=5;}
	$days=(integer)$host['backup_retain_days'];
	if($days < 1){$days=1;}
	$ok=cleanupDirectory($host['backup_dir'],$days);
	//email
	if(isset($host['backup_email']) && isEmail($host['backup_email'])){
		$send=1;
		$sendopts['to']=$host['backup_email'];
		if(isset($host['backup_days'])){
			$cday=date('j');
			$hdays=preg_split('/\,/',$host['backup_days']);
			if(!in_array($cday,$hdays)){$send=0;}
		}
		if($send==1){
			if(!isset($host['backup_email_subject'])){
				$host['backup_email_subject']="Backup for {$site}";
			}
			$sendopts['subject']=$host['backup_email_subject'];
			$sendopts['message']="Files Attached:".PHP_EOL.implode(PHP_EOL,$filenames);
			$ok=sendMail($sendopts);
			$ok=backupMessage("sendmail - {$ok}");
		}
	}
	$ok=backupMessage("*** End backup for {$site} ***");
}

/**
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function backupMessage($msg){
	global $logfile;
	$ctime=time();
	$cdate=date('Y-m-d h:i:s',$ctime);
	$msg="{$cdate},{$ctime},{$msg}".PHP_EOL;
	echo $msg;
	if(!file_exists($logfile) || filesize($logfile) > 1000000 ){
        setFileContents($logfile,$msg);
    }
    else{
        appendFileContents($logfile,$msg);
    }
	return;
}
?>