<?php
	$postdata = file_get_contents("php://input");
	skapiLog(array('postdata'=>$postdata,'request'=>printValue($_REQUEST)));
	//validate request or reject it
	$apikey=decodeBase64($_REQUEST['passthru'][1]);
    list($id,$apikey_raw)=preg_split('/\-/',$apikey,2);
    if(!isNum($id)){
        header('HTTP/1.1 400: BAD REQUEST', true, 400);
        echo "Not authorized";
        skapiLog(array('error'=>'invalid apikey'));
        exit;
	}
    $client=getDBRecord(array('-table'=>'clients','_id'=>$id,'-fields'=>'_id,apiseed,name,active'));
    if(!isset($client['_id'])){
        header('HTTP/1.1 400: BAD REQUEST', true, 400);
        echo "Not authorized";
        skapiLog(array('error'=>'invalid client'));
        exit;
	}
	skapiLog(array('client_id'=>$client['_id']));
    $decoded_str=decrypt($apikey_raw,$client['apiseed']);
    list($_id,$name)=preg_split('/\:/',$decoded_str,2);
    if($_id != $id || $name != $client['name'] || $client['active']==0){
        header('HTTP/1.1 400: BAD REQUEST', true, 400);
        echo "Not authorized";
        skapiLog(array('error'=>'invalid client seed'));
        exit;
	}
	$data=json_decode($postdata,1);
	//add to the appropriate table
	switch(strtolower($_REQUEST['passthru'][0])){
    	case 'wam':
			$data['client_id']=$client['_id'];
			$data['-table']='clientdata_wam_log';
			$id=addDBRecord($data);
    		echo printValue($id).printValue($data);
    	break;
    	case 'woo':
    		$postdata=json_decode($postdata,1);
    		//check for null posts - woo triggers a blank webhook if the webhook gets created.
    		if(count($postdata) == 0){
				echo "No Data";
				echo printValue($_REQUEST);
				skapiLog(array('error'=>'postdata is empty'.printValue($postdata)));
				break;
			}
    		//add webhook value to json string
    		$postdata['webhook']=$_REQUEST['passthru'][2];
    		//determine correct table
    		switch(strtolower($postdata['webhook'])){
            	case 'order.updated':$table='clientdata_woo_orders';break;
            	case 'product.created':$table='clientdata_woo_products';break;
            	default:
            		echo "invalid webhook";
            		skapiLog(array('error'=>'invalid webhook:'.printValue($postdata['webhook'])));
					exit;
				break;
			}
			//rebuild to a json string
			$postdata=json_encode($postdata);
			$opts=array(
				'client_id'	=> $client['_id'],
				'-table'	=> $table,
				'jdoc'		=>$postdata
			);
			$id=addDBRecord($opts);
			if(!isNum($id)){
				$opts=array(
					'client_id'	=> $client['_id'],
					'-table'	=> $table,
					'jdoc_failed'	=> $postdata,
					'jdoc_failed_err'=>$id
				);
				$id=addDBRecord($opts);
			}
    		echo printValue($id);
    		skapiLog(array('recid'=>$id,'tablename'=>$table));
    	break;
	}
?>
