<?php
/*
	Reference:
			https://sellercentral.amazon.com/gp/mws/index.html
			https://mws.amazonservices.com/scratchpad/index.html
			http://stackoverflow.com/questions/25735271/amazon-mws-php-mark-order-as-shipped-submit-order-fulfillment-with-shipping-wi
			http://stackoverflow.com/questions/5934953/amazon-api-marking-orders-as-shipped

*/
//GetOrders is by ordernumbers
function sellercentralGetOrders($params=array()){
	//maximum request quota of six and a restore rate of one request every minute.
	if(!isset($params['access_key_id'])){return 'missing access_key_id';}
	if(!isset($params['seller_id'])){return 'missing seller_id';}
	if(!isset($params['secret_key'])){return 'missing secret_key';}
	if(!isset($params['orderid'])){return 'missing orderid';}
	if(!is_array($params['orderid'])){$params['orderid']=array($params['orderid']);}
	$serviceUrl = "https://mws.amazonservices.com/Orders/2013-09-01";
	$opts=array(
		'AWSAccessKeyId'	=> $params['access_key_id'],
		'Action'			=> 'GetOrder',
		'SellerId'			=> $params['seller_id'],
		'SignatureMethod'	=> 'HmacSHA256',
		'SignatureVersion'	=> 2,
		'Timestamp'			=> gmdate("Y-m-d\TH:i:s\Z", time()),
		'Version'			=> '2013-09-01'
	);
	//add orderids to request
	//Note: Sending more than 8 at a times seems to break their API. Lets break this up.
	$orderidgroups=array_chunk($params['orderid'],8);
	$orders=array();
	foreach($orderidgroups as $orderidgroup){
		for($x=0;$x<count($orderidgroup);$x++){
			$num=$x+1;
			$opts["AmazonOrderId.Id.{$num}"]=$orderidgroup[$x];
		}
		$response=sellerCentralSendRequest('/Orders/2013-09-01',$opts,$params['secret_key']);
		if(isset($response['GetOrderResponse']['GetOrderResult']['Orders']['Order'][0])){
	    	$gorders=$response['GetOrderResponse']['GetOrderResult']['Orders']['Order'];
		}
		elseif(isset($response['GetOrderResponse']['GetOrderResult']['Orders']['Order'])){
	    	$gorders=array($response['GetOrderResponse']['GetOrderResult']['Orders']['Order']);
		}
		else{
			if(isset($response['ErrorResponse']['Error']['Code']) && $response['ErrorResponse']['Error']['Code']=='RequestThrottled'){
				echo '<div><span class="icon-warning w_warning"></span> <b>RequestThrottled:</b> Wait for a few minutes before checking Amazon again.  This Amazon API has a maximum request quota of six and a restore rate of one request every minute.</div>';
            	break;
			}
			else{
				echo printValue($response);
			}
			$gorders=array();
		}
		$orders=array_merge($orders,$gorders);
	}
	if(count($orders) && !isset($params['-noitems'])){
		//get the details
		foreach($orders as $i=>$order){
			$params['order_id']=$order['AmazonOrderId'];
	    	$orders[$i]['items']=sellercentralListOrderItems($params);
	    	if(!count($orders[$i]['items'])){
				//probably got throttled
				unset($orders[$i]);
	        	//echo "Here".printValue($params);exit;
			}
		}
	}
	//set the array key to the ordernumber for easier lookup
	$korders=array();
	foreach($orders as $order){
    	$korders[$order['AmazonOrderId']]=$order;
	}

	return $korders;
}
//ListOrders is by date range
function sellercentralListOrders($params=array()){
	//maximum request quota of six and a restore rate of one request every minute.
	if(!isset($params['access_key_id'])){return 'missing access_key_id';}
	if(!isset($params['seller_id'])){return 'missing seller_id';}
	if(!isset($params['marketplace_id'])){return 'missing marketplace_id';}
	if(!isset($params['secret_key'])){return 'missing secret_key';}
	if(!isset($params['status'])){$params['status']=array('Unshipped','PartiallyShipped');}
	$serviceUrl = "https://mws.amazonservices.com/Orders/2013-09-01";
	$opts=array(
		'AWSAccessKeyId'	=> $params['access_key_id'],
		'SellerId'			=> $params['seller_id'],
		'SignatureMethod'	=> 'HmacSHA256',
		'SignatureVersion'	=> 2,
		'Timestamp'			=> gmdate("Y-m-d\TH:i:s\Z", time()),
		'Version'			=> '2013-09-01',

	);
	if(isset($params['nexttoken'])){
    	$opts['Action']='ListOrdersByNextToken';
    	$opts['NextToken']=$params['nexttoken'];
	}
	else{
		//default dates
		if(!isset($params['created_after'])){$params['created_after']=gmdate("Y-m-d\TH:i:s\Z",strtotime('-1 days'));}
		else{$params['created_after']=gmdate("Y-m-d\TH:i:s\Z",strtotime($params['created_after']));}
		if(!isset($params['created_before'])){$params['created_before']=gmdate("Y-m-d\TH:i:s\Z",strtotime('-5 minutes'));}
		else{$params['created_before']=gmdate("Y-m-d\TH:i:s\Z",strtotime($params['created_before']));}
		//options
		$opts['Action']='ListOrders';
		$opts['CreatedAfter']		= $params['created_after'];
		$opts['CreatedBefore']		= $params['created_before'];
		$opts['MarketplaceId.Id.1']	= $params['marketplace_id'];
		$opts['MaxResultsPerPage']	= 100;
		//filter by status?
		if(isset($params['status'])){
			if(!is_array($params['status'])){$params['status']=array($params['status']);}
			$x=0;
			foreach($params['status'] as $status){
		    	$x+=1;
		    	$opts["OrderStatus.Status.{$x}"]=$status;
			}
		}
	}
	ksort($opts);
	$response=sellerCentralSendRequest('/Orders/2013-09-01',$opts,$params['secret_key']);
	//echo printValue($opts).printValue($response);
	$orders=array();
	if(isset($opts['NextToken'])){
		//echo "NEXT".printValue($response);exit;
    	if(isset($response['ListOrdersByNextTokenResponse']['ListOrdersByNextTokenResult']['Orders']['Order'][0])){
	    	$orders=$response['ListOrdersByNextTokenResponse']['ListOrdersByNextTokenResult']['Orders']['Order'];
		}
		elseif(isset($response['ListOrdersByNextTokenResponse']['ListOrdersByNextTokenResult']['Orders']['Order'])){
	    	$orders=array($response['ListOrdersByNextTokenResponse']['ListOrdersByNextTokenResult']['Orders']['Order']);
		}
		if(isset($response['ListOrdersByNextTokenResponse']['ListOrdersByNextTokenResult']['NextToken']) && strlen($response['ListOrdersResponse']['ListOrdersResult']['NextToken'])){
			$params['nexttoken']=$response['ListOrdersByNextTokenResponse']['ListOrdersByNextTokenResult']['NextToken'];
			$nextorders=sellercentralListOrders($params);
			//echo "NextOrders:".count($orders).printValue($nextorders);exit;
			$orders=array_merge($orders,$nextorders);
		}
	}
	elseif(isset($response['ListOrdersResponse'])){
    	if(isset($response['ListOrdersResponse']['ListOrdersResult']['Orders']['Order'][0])){
	    	$orders=$response['ListOrdersResponse']['ListOrdersResult']['Orders']['Order'];
		}
		elseif(isset($response['ListOrdersResponse']['ListOrdersResult']['Orders']['Order'])){
	    	$orders=array($response['ListOrdersResponse']['ListOrdersResult']['Orders']['Order']);
		}
		if(isset($response['ListOrdersResponse']['ListOrdersResult']['NextToken']) && strlen($response['ListOrdersResponse']['ListOrdersResult']['NextToken'])){
			$params['nexttoken']=$response['ListOrdersResponse']['ListOrdersResult']['NextToken'];
			$nextorders=sellercentralListOrders($params);
			//echo "NextOrders:".count($orders).printValue($nextorders);exit;
			$orders=array_merge($orders,$nextorders);
		}
	}


	//return $orders;
	//get the details
	foreach($orders as $i=>$order){
		$params['order_id']=$order['AmazonOrderId'];
    	$orders[$i]['items']=sellercentralListOrderItems($params);
    	if(!count($orders[$i]['items'])){
			//probably got throttled
			unset($orders[$i]);
        	//echo "Here".printValue($params);exit;
		}
	}
	return $orders;
}
function sellercentralListOrderItems($params=array()){
	if(!isset($params['access_key_id'])){return 'missing access_key_id';}
	if(!isset($params['seller_id'])){return 'missing seller_id';}
	if(!isset($params['marketplace_id'])){return 'missing marketplace_id';}
	if(!isset($params['secret_key'])){return 'missing secret_key';}
	if(!isset($params['order_id'])){return 'missing order_id';}
	$serviceUrl = "https://mws.amazonservices.com/Orders/2013-09-01";
	$opts=array(
		'AWSAccessKeyId'	=> $params['access_key_id'],
		'Action'			=> 'ListOrderItems',
		'SellerId'			=> $params['seller_id'],
		'SignatureMethod'	=> 'HmacSHA256',
		'SignatureVersion'	=> 2,
		'Timestamp'			=> gmdate("Y-m-d\TH:i:s\Z", time()),
		'Version'			=> '2013-09-01',
		'AmazonOrderId'		=> $params['order_id'],

	);
	$response=sellerCentralSendRequest('/Orders/2013-09-01',$opts,$params['secret_key']);
	$items=array();
	if(isset($response['ListOrderItemsResponse']['ListOrderItemsResult']['OrderItems']['OrderItem'][0])){
    	$items=$response['ListOrderItemsResponse']['ListOrderItemsResult']['OrderItems']['OrderItem'];
	}
	elseif(isset($response['ListOrderItemsResponse']['ListOrderItemsResult']['OrderItems']['OrderItem'])){
    	$items=array($response['ListOrderItemsResponse']['ListOrderItemsResult']['OrderItems']['OrderItem']);
	}
	//if(!count($items)){echo "NO ITEMS".printValue($params).printValue($items).printValue($response);exit;}
	return $items;
}
function sellerCentralSendRequest($action_url,$opts,$secret_key){
	$url_parts = array();
	foreach(array_keys($opts) as $key){
        $url_parts[] = $key . "=" . str_replace('%7E', '~', rawurlencode($opts[$key]));
	}
	sort($url_parts);
	// Construct the string to sign
	$url_string = implode("&", $url_parts);
	$string_to_sign = "GET\nmws.amazonservices.com\n{$action_url}\n" . $url_string;
	// Sign the request
	$signature = hash_hmac("sha256", $string_to_sign, $secret_key, TRUE);
	// Base64 encode the signature and make it URL safe
	$signature = urlencode(base64_encode($signature));

	$url = "https://mws.amazonservices.com{$action_url}" . '?' . $url_string . "&Signature=" . $signature;
	//echo $url;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $response = curl_exec($ch);
	$response=trim($response);
    $array = XML2Array::createArray($response);
    return $array;
}
function sellerCentralPostOrderFulfillmentData($params=array()){
	if(!isset($params['access_key_id'])){return 'missing access_key_id';}
	if(!isset($params['seller_id'])){return 'missing seller_id';}
	if(!isset($params['marketplace_id'])){return 'missing marketplace_id';}
	if(!isset($params['secret_key'])){return 'missing secret_key';}
	if(!isset($params['ordernumber'])){return 'missing ordernumber';}
	if(!isset($params['carrier'])){return 'missing carrier';}
	if(!isset($params['ship_method'])){return 'missing ship_method';}
	if(!isset($params['tracking_num'])){return 'missing tracking_num';}
	$param = array();
    $param['AWSAccessKeyId']   = $params['access_key_id'];
    $param['Action']           = 'SubmitFeed';
    $param['Merchant']         = $params['seller_id'];
    $param['FeedType']         = '_POST_ORDER_FULFILLMENT_DATA_';
    $param['SignatureMethod']  = 'HmacSHA256';
    $param['SignatureVersion'] = '2';
    $param['Timestamp']        = gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", time());
    $param['Version']          = '2009-01-01';
    $param['MarketplaceId.Id.1']    = $params['marketplace_id'];
    $param['PurgeAndReplace']    = 'false';
    $secret = $params['secret_key'];
	$url = array();
    foreach ($param as $key => $val) {
        $key = str_replace("%7E", "~", rawurlencode($key));
        $val = str_replace("%7E", "~", rawurlencode($val));
        $url[] = "{$key}={$val}";
    }
    $amazon_feed=<<<ENDOFXML
<?xml version="1.0" encoding="UTF-8"?>
<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <Header>
        <DocumentVersion>1.01</DocumentVersion>
        <MerchantIdentifier>{$params['seller_id']}</MerchantIdentifier>
    </Header>
    <MessageType>OrderFulfillment</MessageType>
    <Message>
        <MessageID>1</MessageID>
        <OperationType>Update</OperationType>
        <OrderFulfillment>
            <AmazonOrderID>{$params['ordernumber']}</AmazonOrderID>
            <FulfillmentDate>{$param['Timestamp']}</FulfillmentDate>
            <FulfillmentData>
                <CarrierName>{$params['carrier']}</CarrierName>
                <ShippingMethod>{$params['ship_method']}</ShippingMethod>
                <ShipperTrackingNumber>{$params['tracking_num']}</ShipperTrackingNumber>
            </FulfillmentData>
        </OrderFulfillment>
    </Message>
</AmazonEnvelope>
ENDOFXML;
    sort($url);
    $arr   = implode('&', $url);
    $sign  = 'POST' . "\n";
    $sign .= 'mws.amazonservices.com' . "\n";
    $sign .= '/Feeds/'.$param['Version'].'' . "\n";
    $sign .= $arr;
    $signature = hash_hmac("sha256", $sign, $secret, true);
    $httpHeader     =   array();
    $httpHeader[]   =   'Transfer-Encoding: chunked';
    $httpHeader[]   =   'Content-Type: application/xml';
    $httpHeader[]   =   'Content-MD5: ' . base64_encode(md5($amazon_feed, true));
    $httpHeader[]   =   'Expect:';
    $httpHeader[]   =   'Accept:';
    $signature = urlencode(base64_encode($signature));
    $link  = "https://mws.amazonservices.com/Feeds/".$param['Version']."?";
    $link .= $arr . "&Signature=" . $signature;
	//return $link; //for debugging
    $ch = curl_init($link);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $amazon_feed);
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    /*
		Normal output:
		<?xml version="1.0"?><SubmitFeedResponse xmlns="http://mws.amazonaws.com/doc/2009-01-01/">SubmitFeedResult>FeedSubmissionInfo>FeedSubmissionId>XXXXXXXXXXXXXXXXXXX</FeedSubmissionId><FeedType>_POST_ORDER_FULFILLMENT_DATA_</FeedType><SubmittedDate>2014-09-09T00:36:29+00:00</SubmittedDate><FeedProcessingStatus>_SUBMITTED_</FeedProcessingStatus></FeedSubmissionInfo>/SubmitFeedResult>ResponseMetadata>RequestId>XXXXXXXXXXXXXXXXXXX</RequestId></ResponseMetadata></SubmitFeedResponse><?xml version="1.0"?><SubmitFeedResponse xmlns="http://mws.amazonaws.com/doc/2009-01-01/"><SubmitFeedResult><FeedSubmissionInfo><FeedSubmissionId>XXXXXXXXXXXXXXXXXXX</FeedSubmissionId>FeedType>_POST_ORDER_FULFILLMENT_DATA_</FeedType><SubmittedDate>2014-09-09T00:36:29+00:00</SubmittedDate>FeedProcessingStatus>_SUBMITTED_</FeedProcessingStatus></FeedSubmissionInfo>/SubmitFeedResult>ResponseMetadata>RequestId>XXXXXXXXXXXXXXXXXXX</RequestId></ResponseMetadata></SubmitFeedResponse
	*/
	$response=trim($response);
    $array = XML2Array::createArray($response);
    return $array;
}
?>