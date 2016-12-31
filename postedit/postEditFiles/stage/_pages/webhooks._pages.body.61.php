<?php
/*
	/webhooks/woo/sha1(client_id) with json string in the post
*/
$jsonstr = file_get_contents("php://input");
if(!strlen($jsonstr)){$jsonstr='{}';}
$json = json_decode($jsonstr);
if(isset($json->order->created_at)){
	$json->order->created_at=date('Y-m-d H:i:s',strtotime($json->order->created_at));
}
$jsonstr=json_encode($json);
switch(strtolower($_REQUEST['passthru'][0])){
	case 'woo':
		$json_type = $json->type;
		$type = strtok($json_type, '.');
		$type='order';
		if(isset($_REQUEST['passthru'][2])){
        	$type=$_REQUEST['passthru'][2];
		}
		$module='woo';
		$table = "clientdata_{$module}_{$type}s";
	break;
	case 'stripe':
		$json_type = $json->type;
		$type = strtok($json_type, '.');
		$module='stripe';
		$table = "clientdata_{$module}_{$type}s";
		$jsonstr = json_encode($json->data->object);
	break;
}
$id = '';
if(!isDBTable($table)){
	createDBTable($table,array(
		'client_id' => 'int(11) NOT NULL Default 0',
		'jdoc'		=> 'json NULL',
		'jdoc_failed' => 'mediumtext',
		'jdoc_failed_err' => 'varchar(800)'
	));
}
echo "Table:{$table}<br>\n";
echo "Module:{$module}<br>\n";
if(isDBTable($table)){
	$addopts=array(
		'-table'=>$table,
		'jdoc'=>$jsonstr
	);
	//add client_id if passed in as second path param
	if(isset($_REQUEST['passthru'][1])){
    	$client=getDBRecord(array(
    		'-table'=>'clients',
    		'-where'=>"sha1(_id)='{$_REQUEST['passthru'][1]}'"
    	));
    	if(isset($client['_id'])){
        	$addopts['client_id']=$client['_id'];
		}
	}
	$id=addDBRecord($addopts);
	echo $id;
}
if(!isNum($id)){
	$addopts=array(
		'-table'=>"clientdata_{$module}_events",
		'jdoc'=>$jsonstr,
		'jdoc_failed'=>$id
	);
	//add client_id if passed in as second path param
	if(isset($_REQUEST['passthru'][1])){
    	$client=getDBRecord(array(
    		'-table'=>'clients',
    		'-where'=>"sha1(_id)='{$_REQUEST['passthru'][1]}'"
    	));
    	if(isset($client['_id'])){
        	$addopts['client_id']=$client['_id'];
		}
	}
	$id=addDBRecord($addopts);
}
?>
