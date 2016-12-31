<?
/*
	Skillsai URL: https://skillsai.freshdesk.com
	API Key: pOliO4ZfQiaJCOEIq7j
	API Reference: http://developer.freshdesk.com/api/
*/
function freshdeskAPIKey(){
	return 'pOliO4ZfQiaJCOEIq7j';
}

function freshdeskGetTickets($params=array()){
	$opts=array(
		'-authuser'=>freshdeskAPIKey(),
		'-authpass'=>'Xd4494$!',
		'-method'=>'GET',
		'-json'=>1
	);
	foreach($params as $k=>$v){
		$opts[$k]=$v;
	}
	$url='https://skillsai.freshdesk.com/api/v2/tickets';
	$post=postURL($url,$opts);
	if(isset($post['json_array'])){
    	$tickets=$post['json_array'];
    	foreach($tickets as $i=>$ticket){
        	foreach($ticket as $k=>$v){
            	if(is_array($v)){$tickets[$i][$k]=implode(':',$v);}
			}
		}
		return $tickets;
	}
	//much have got an error
	echo printValue($post);exit;
}
function freshdeskCreateTicket($params=array()){
	$opts=array(
		'-authuser'=>freshdeskAPIKey(),
		'-authpass'=>'Xd4494$!',
		'-method'=>'POST',
		'-json'=>1
	);
	$url='https://skillsai.freshdesk.com/api/v2/tickets';
	$jsn=json_encode($params,JSON_NUMERIC_CHECK);
	//echo $jsn;exit;
	$post=postJSON($url,$jsn,$opts);
	if(isset($post['json_array'])){
		//return $post['json_array']['id'];
    	$ticket=$post['json_array'];
        foreach($ticket as $k=>$v){
            if(is_array($v)){$ticket[$k]=implode(':',$v);}
		}
		return $ticket;
	}
	//much have got an error
	return $post;
}
?>
