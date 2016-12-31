<?php
function getColors(){
	$recs=getDBRecords(array('-table'=>'colors','-order'=>'name'));
	return $recs;
}
function getColorByName($name){
	$rec=getDBRecord(array('-table'=>'colors','name'=>$name));
	if(isset($rec['_id'])){return $rec;}
	return null;
}
function getColorByHex($hex){
	$rec=getDBRecord(array('-table'=>'colors','hex'=>$hex));
	if(isset($rec['_id'])){return $rec;}
	return null;
}
function drawPicture(){
	/*
		convert -size 112x112 xc:white -stroke #91c5b8 -strokewidth 3 -draw "line 5,5 25,30" audible_art.png
		convert audible_art.jpg -stroke #91c5b8 -strokewidth 3 -draw "line 7,10 15,30"  audible_art.jpg
	*/
	$path='/var/www/vhosts/devmavin.com/skillsai.com/skills/audible_art';
	$recs=getDBRecords(array(
		'-table'	=> 'audible_art',
		'-where'	=> "YEARWEEK(_cdate)=YEARWEEK(NOW())",
		'-order'	=> '_id'
	));
	if(!is_array($recs)){
    	return;
	}
	$smallfile=date('YW')."_small.png";
	$asmallfile="{$path}/{$smallfile}";
	unlink($asmallfile);
	//setFileContents("{$path}/audible.log",'test');
	$largefile=date('YW')."_large.png";
	$alargefile="{$path}/{$largefile}";
	$alphamap=array_flip(range('A','Z'));
	alexaSetUserVar('audible_art',$asmallfile);
	$cmdlog='';
	foreach($recs as $rec){
		$x1key=strtoupper($rec['x1']);
		$x1=$alphamap[$x1key]*4;
		$y1=$rec['y1']*4;
		if(!strlen($rec['x2'])){
        	$x2=$x1;
        	$y2=$y1;
		}
		else{
        	$x2key=strtoupper($rec['x2']);
			$x2=$alphamap[$x2key]*4;
			$y2=$rec['y2']*4;
		}
		if(!is_file($asmallfile)){
        	$cmd="convert -size 112x112 xc:white -stroke '{$rec['color']}' -strokewidth 3 -draw 'line {$x1},{$y1} {$x2},{$y2}' '{$asmallfile}'";
			$ok=cmdResults($cmd);
			$cmdlog.=printValue($alphamap)."[{$x1key}] {$cmd}<br>\n";
			alexaSetUserVar('cmdlog',$cmdlog);
		}
		else{
        	$cmd="convert '{$asmallfile}' -stroke '{$rec['color']}' -strokewidth 3 -draw 'line {$x1},{$y1} {$x2},{$y2}' '{$asmallfile}'";
			$ok=cmdResults($cmd);
			$cmdlog.="{$cmd}<br>\n";
			alexaSetUserVar('cmdlog',$cmdlog);
		}
/* 		$ok=editDBRecord(array(
			'-table'=>'audible_art',
			'-where'=>"_id={$rec['_id']}",
			'drawn'=>1
		)); */

	}
	//make large file
	$cmd="convert -resize 512x512 '{$asmallfile}' '{$alargefile}'";
	$ok=cmdResults($cmd);
	//return stats
	return pageStats();
}
function pageStats(){
	$queries=array(
		'users'=>"select count(distinct(userid)) cnt from audible_art where YEARWEEK(_cdate)=YEARWEEK(NOW())",
		'lines'=>"select count(*) cnt from audible_art where length(x2) > 0 and YEARWEEK(_cdate)=YEARWEEK(NOW())",
		'dots'=>"select count(*) cnt from audible_art where length(x2) = 0 and YEARWEEK(_cdate)=YEARWEEK(NOW())",
		'colors'=>"select count(distinct(color)) cnt from audible_art where YEARWEEK(_cdate)=YEARWEEK(NOW())",
	);
	$stats=array();
	foreach($queries as $key=>$query){
    	$r=getDBRecord(array('-query'=>$query));
    	$stats[$key]=$r['cnt'];
	}
	return $stats;
}
function pageGrid(){
	$grid='';
	for($y=0;$y<512;$y+=8){
		for($x=0;$x<512;$x+=8){
			$x2=$x+8;
			$y2=$y+8;
			$grid .= '<area shape="rect" onclick="myHover(this);" onmouseout="myLeave();" coords="'."{$x},{$y},{$x2},{$y2}".'" href="#" alt="">'."\n";
		}
	}
	return $grid;
}
?>
