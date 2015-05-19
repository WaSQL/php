<?php
/* 
	iCalendar feeds -  parser routines

*/
$progpath=dirname(__FILE__);
//---------- begin function icalCreateICS ----------
/**
* @describe creates an ics file or pushes the file to the browser
* @param params array - options
* 	start  string - start date and time of the event - string or timestamp
* 	end  string - end date and time of the event - string or timestamp
* 	name string - name the event
* 	description string - description of the event
*	location string - location of the event
*	[-push] boolean - push event as a file
*	[-file] string - full file name and path to save file to server as -
* @return mixed - ics string or file or push to browser based on params
*/
function icalCreateICS($params=array()){
	$parts=array(
		"BEGIN:VCALENDAR",
		"VERSION:2.0",
		"METHOD:PUBLISH",
		"BEGIN:VEVENT",
		"DTSTART:".date("Ymd\THis\Z",strtotime($params['start'])),
		"DTEND:".date("Ymd\THis\Z",strtotime($params['end'])),
		"LOCATION:".$params['location'],
		"TRANSP: OPAQUE",
		"SEQUENCE:0",
		"UID:",
		"DTSTAMP:".date("Ymd\THis\Z"),
		"SUMMARY:".$params['name'],
		"DESCRIPTION:".$params['description'],
		"PRIORITY:1",
		"CLASS:PUBLIC",
		//alarm
		"BEGIN:VALARM",
		"TRIGGER:-PT10080M",
		"ACTION:DISPLAY",
		"DESCRIPTION: Reminder - ".$params['name'],
		"END:VALARM",

		"END:VEVENT",
		"END:VCALENDAR"
    );
    $ics=implode("\r\n",$parts);
    if($params['-push']){
    	header("Content-type:text/calendar");
        header('Content-Disposition: attachment; filename="'.$params['name'].'.ics"');
        Header('Content-Length: '.strlen($ics));
        Header('Connection: close');
        echo $ics;
        exit;
	}
	if($params['-file']){
    	file_put_contents($params['-file'],$ics);
	}
	return $ics;
}
//---------- begin function icalEvents ----------
/**
* @describe convert an ics iCal feed or file to an array of events
* @param file string - filename or URL to parse
* @param params array - additional options
* @return array - array of events
*/
function icalEvents($filename,$params=array()){
	$lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (stristr($lines[0], 'BEGIN:VCALENDAR') === false) {
        return false;
    }
	$events=array();
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
			$event['description']=removeHtml($event['description']);
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