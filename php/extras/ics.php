<?php
/* 
	iCal parser routines
	see https://code.google.com/p/ics-parser/source/browse/trunk/iCalReader.php?r=2

*/
$progpath=dirname(__FILE__);

//---------- begin function icsEvents ----------
/**
* @describe convert an ics file to an array of events
* @param file string - filename or URL to parse
* @param params array - additional options
* @return array - array of events
*/
function icsEvents($filename,$params=array()){
	$lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (stristr($lines[0], 'BEGIN:VCALENDAR') === false) {
        return false;
    }
	$ics=array();
	$read=0;
	$key='';
	//echo printValue($lines);
	foreach($lines as $line){
    	if(stringContains($line,'BEGIN:VEVENT')){
        	$read=1;
        	$event=array();
		}
		if(stringContains($line,'END:VEVENT')){
        	$read=0;
        	ksort($event);
        	foreach($event as $key=>$val){
            	$event[$key]=fixMicrosoft(str_replace('[n]',"\n",$val));
			}
        	$events[]=$event;
		}
		if($read==0){continue;}
		//echo "Line:{$line}<br>\n";
		if(preg_match('/^([a-z\-]+?)\:(.+)$/i',ltrim($line),$m)){
        	$key=strtolower($m[1]);
        	$val=$m[2];
		}
		elseif(preg_match('/^(dtstart|dtend)\;(.+?)\:(.+)$/i',ltrim($line),$m)){
        	$key=strtolower($m[1]);
        	$val=$m[3];
		}
		elseif(preg_match('/^([a-z]+?)\;(.+)$/i',ltrim($line),$m)){
        	$key=strtolower($m[1]);
        	$val=$m[2];
		}
		else{
			if(stringBeginsWith($line,'  ')){
				$val=' '.trim($line);
			}
			else{
            	$val=trim($line);
			}
		}
		if(!strlen($key)){continue;}
		$val=str_replace("\\n",'[n]',$val);
		$val=stripslashes($val);
		//echo "Key:{$key},  Val:{$val}<br>\n";
		switch(strtolower($key)){
	        case 'dtstart':
	            $ts=strtotime($val);
				$event['date_start']=date('Y-m-d',$ts);
				$event['time_start']=date('H:i',$ts);
	        break;
	        case 'dtend':
	            $ts=strtotime($val);
				$event['date_stop']=date('Y-m-d',$ts);
				$event['time_stop']=date('H:i',$ts);
	        break;
	        case 'summary':
	        case 'title':
	        	$event['title'].=$val;
	        break;
	        case 'description':
	        case 'location':
	        case 'geo':
	        case 'url':
	        case 'uid':
	        case 'class':
	        case 'status':
	            $key=strtolower($key);
	            $event[$key].=$val;
	        break;
	        case 'created':
	            $ts=strtotime($val);
	            $event['_cdate']=date('Y-m-d H:i:s',$ts);
	        break;
	        case 'last-modified':
	            $ts=strtotime($val);
	            $event['_edate']=date('Y-m-d H:i:s',$ts);
	        break;
		}
	}
	return $events;
}

?>