<?php
/* 
	Eventbrite API functions
	see http://developer.eventbrite.com/doc/

*/
$progpath=dirname(__FILE__);

//---------- begin function eventbriteGetAttendeeList ----------
/**
* @url http://developer.eventbrite.com/doc/users/event_list_attendees/
* @describe returns list of attendees for said event
* @param array 
*	app_key  Eventbrite app_key
*	user_key  Eventbrite user_key
*	event_id  Eventbrite event_id captured by calling eventbriteGetEventList
*	additional parameters act to filter out the list
* @return array
*	returns list of attendees for said event
* @usage 
*	$attendees=eventbriteGetAttendeeList(array(
*		'app_key'	=> 'YOUREVENTBRITEAPPKEY',
*		'user_key'	=> 'YOURAPPKEY',
*		'event_id'		=> $event['id']
*	));
*/
function eventbriteGetAttendeeList($params=array()){
	//auth tokens are required
	$required=array('app_key','user_key','event_id');
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "eventbriteGetAttendees Error: Missing required param '{$key}'";
		}
	}
	$postopts=array(
		'app_key'	=> $params['app_key'],
		'user_key'	=> $params['user_key'],
		'id'		=> $params['event_id'],
		'-method'	=> 'GET',
		'-ssl'		=> 0,
		'-json'		=> 1
	);
	unset($params['app_key']);
	unset($params['user_key']);
	unset($params['event_id']);
	$url='https://www.eventbrite.com/json/event_list_attendees';
	$post=postURL($url,$postopts);
	if(!isset($post['json_array']['attendees'][0])){
		//failed
    	return array();
	}
	$attendees=array();
	foreach($post['json_array']['attendees'] as $attendee){
    	if(isset($attendee['attendee']['id'])){$attendee=$attendee['attendee'];}
    	unset($attendee['tickets']);
    	unset($attendee['description']);
    	unset($attendee['organizer']);
    	unset($attendee['logo_ssl']);
    	unset($attendee['logo']);
    	foreach($attendee as $key=>$val){
			if(is_array($val)){
            	foreach($val As $vkey=>$vval){
                	$newkey="{$key}_{$vkey}";
                	$attendee[$newkey]=$vval;
				}
				unset($attendee[$key]);
			}
		}
		$skip=0;
		foreach($params as $key=>$val){
        	if(isset($attendee[$key]) && !stringContains($attendee[$key],$val)){$skip=1;break;}
		}
		if(!$skip){
			ksort($attendee);
    		$attendees[]=$attendee;
		}
	}
	return $attendees;
}
//---------- begin function eventbriteGetEventList ----------
/**  
* @url http://developer.eventbrite.com/doc/users/user_list_events/
* @describe returns list of events
* @param array 
*	app_key  Eventbrite app_key
*	user_key  Eventbrite user_key
*	additional parameters act to filter out the list
* @return array
*	returns list of events
* @usage 
*	$events=eventbriteGetEventList(array(
*		'app_key'	=> 'YOUREVENTBRITEAPPKEY',
*		'user_key'	=> 'YOURAPPKEY'
*	));
*/
function eventbriteGetEventList($params=array()){
	//auth tokens are required
	$required=array('app_key','user_key');
	foreach($required as $key){
    	if(!isset($params[$key]) || !strlen($params[$key])){
        	return "eventbriteGetAttendees Error: Missing required param '{$key}'";
		}
	}
	$postopts=array(
		'app_key'	=> $params['app_key'],
		'user_key'	=> $params['user_key'],
		'do_not_display'=>'logo,logo_ssl,description,organizer,tickets',
		'-method'	=> 'GET',
		'-ssl'		=> 0,
		'-json'		=> 1
	);
	unset($params['app_key']);
	unset($params['user_key']);
	unset($params['do_not_display']);
	$url='https://www.eventbrite.com/json/user_list_events';
	$post=postURL($url,$postopts);
	if(!isset($post['json_array']['events'][0])){
		//failed
    	return array();
	}
	$events=array();
	foreach($post['json_array']['events'] as $event){
    	if(isset($event['event']['id'])){$event=$event['event'];}
    	foreach($event as $key=>$val){
        	if(stringContains($key,'_color')){
            	unset($event[$key]);
			}
			if(is_array($val)){
            	foreach($val as $vkey=>$vval){
                	$newkey="{$key}_{$vkey}";
                	$event[$newkey]=$vval;
				}
				unset($event[$key]);
			}
		}
		$skip=0;
		foreach($params as $key=>$val){
        	if(isset($event[$key]) && !stringContains($event[$key],$val)){$skip=1;break;}
		}
		if(!$skip){
			ksort($event);
    		$events[]=$event;
		}
	}
	return $events;
}
?>