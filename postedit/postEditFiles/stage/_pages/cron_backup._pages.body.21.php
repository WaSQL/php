<?php
     $path=getWasqlPath('sh/backups');
     buildDir($path);
     //keep daily backups for 30 days
     $ok=cleanupDirectory($path,30,'days','gz');
     $dump=dumpDB();
     //Every Sunday, move the file to weekly backups
     if(date('N')==7){
	 	$destpath=="{$dump['path']}/weekly";
     	if(!is_dir($destpath)){buildDir($destpath);}
	 	$destFile="{$destpath}/{$dump['file']}";
     	$b=copyFile($dump['afile'],$destFile);
     	//keep weekly backups for 1 year
     	$ok=cleanupDirectory($destpath,1,'year','gz');
	 }
     //every 1st of the month move the file to monthly backups
     if(date('j')==1){
	 	$destpath=="{$dump['path']}/monthly";
     	if(!is_dir($destpath)){buildDir($destpath);}
	 	$destFile="{$destpath}/{$dump['file']}";
     	$b=copyFile($dump['afile'],$destFile);
     	//keep monthly backups for 4 years
     	$ok=cleanupDirectory($destpath,4,'years','gz');
	 }
     //every Jan 1st move the file to yearly backups
     if(date('z')==0){
	 	$destpath=="{$dump['path']}/yearly";
     	if(!is_dir($destpath)){buildDir($destpath);}
	 	$destFile="{$destpath}/{$dump['file']}";
     	$b=copyFile($dump['afile'],$destFile);
     	//keep yearly backups for 4 years
     	$ok=cleanupDirectory($destpath,14,'years','gz');
	 }
     echo printValue($dump);
?>
