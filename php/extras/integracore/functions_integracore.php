<?php
/*Functions for processing orders with IntegraCore, LLC - a logistics company */
global $import_rules;
$import_rules=integracoreImportRules();
//Expected Receipts Functions
//---------------------------
function integracoreGetExpectedReceipts($params=array()){
	$url='http://www.integracoreb2b.com/API/ExpectedReceipts.asmx';
	$xmlpost=xmlHeader(array('encoding'=>'utf-8'));
	$xmlpost .= '<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">'."\n";
	$xmlpost .= integracoreSOAPHeader($params);
	$xmlpost .= '  <soap12:Body>'."\n";
	$xmlpost .= '    <getExpectedReceipts xmlns="http://www.integracoreb2b.com/API/" />'."\n";
	$xmlpost .= '  </soap12:Body>'."\n";
	$xmlpost .= '</soap12:Envelope>'."\n";
	$post=postXML($url,$xmlpost,array('-ssl'=>false));
	//return $post;
	$rtn=$post['xml_array']['soapBody']['getExpectedReceiptsResponse']['getExpectedReceiptsResult'];
	return $rtn;
}
//---------------------------
function integracoreGetShipMethods($params=array()){
	$url='http://api.integracore.net/api';
	$xmlpost=xmlHeader(array('encoding'=>'utf-8'));
	$xmlpost .= '<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">'."\n";
	$xmlpost .= integracoreSOAPHeader($params);
	$xmlpost .= '  <soap12:Body>'."\n";
	$xmlpost .= '    <getShipMethods xmlns="http://www.integracoreb2b.com/API/" />'."\n";
	$xmlpost .= '  </soap12:Body>'."\n";
	$xmlpost .= '</soap12:Envelope>'."\n";
	$post=postXML($url,$xmlpost,array('-ssl'=>false));
	$rtn=$post['xml_array']['getShipMethods']['Methods']['Method'];
	return $rtn;
}
//---------------------------
function integracoreAddExpectedReceipts($params=array()){
	$url='http://www.integracoreb2b.com/API/ExpectedReceipts.asmx';
	$map=array(
		'ponumber'				=> 'PONumber',
		'site_id'				=> 'Site_ID',
		'ship_contact'			=> 'Ship_Contact',
		'comments'				=> 'comments',
		'ship_telephone'		=> 'Ship_Telephone',
		'carriername'			=> 'CarrierName',
		'expectedreceiptdate'	=> 'ExpectedReceiptDate',
		'create_userid'			=> 'Create_UserID'
	);
	$dmap=array(
		'itemid'				=> 'ItemID',
		'costeach'				=> 'CostEach',
		'quantity'				=> 'Quantity',
		'expectedreceiptdate'	=> 'ExpectedReceiptDate',
	);
	$xmlpost=xmlHeader(array('encoding'=>'utf-8'));
	$xmlpost .= '<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">'."\n";
	$xmlpost .= integracoreSOAPHeader($params);
	$xmlpost .= '<soap12:Body>'."\n";
	$xmlpost .= '<addExpectedReceipts xmlns="http://www.integracoreb2b.com/API/">'."\n";
    $xmlpost .= '	<ExpectedReceipts>'."\n";
    foreach($params['receipts'] as $receipt){
		$xmlpost .= '		<ExpectedReceipt>'."\n";
		//echo printValue($receipt);
		foreach($receipt as $key=>$val){
			if(is_array($val)){continue;}
			if(isset($map[$key])){
				$val=xmlEncodeCDATA($val);
				$key=$map[$key];
				$xmlpost .= "			<{$key}>{$val}</{$key}>\n";
            }
		}
        $xmlpost .= '			<Details>'."\n";
        foreach($receipt['details'] as $detail){
			$xmlpost .= '				<Detail>'."\n";
			//echo printValue($detail);
			foreach($detail as $dkey=>$dval){
				if(isset($dmap[$dkey])){
					$dval=xmlEncodeCDATA($dval);
					$dkey=$dmap[$dkey];
					$xmlpost .= "					<{$dkey}>{$dval}</{$dkey}>\n";
	            }
			}
	        $xmlpost .= '				</Detail>'."\n";
		}
		$xmlpost .= '			</Details>'."\n";
		$xmlpost .= '		</ExpectedReceipt>'."\n";
	}
    $xmlpost .= '	</ExpectedReceipts>'."\n";
    $xmlpost .= '</addExpectedReceipts>'."\n";
	$xmlpost .= '</soap12:Body>'."\n";
	$xmlpost .= '</soap12:Envelope>'."\n";
	$post=postXML($url,$xmlpost,array('-ssl'=>false));
	//return $post;
	$rtn=$post['xml_array']['soapBody']['addExpectedReceiptsResponse']['addExpectedReceiptsResult'];
	return $rtn;
}
//ShipConfirm Functions
//---------------------------
function integracoreGetShipConfirms($params=array()){
	$url='http://www.integracoreb2b.com/API/ShipConfirm.asmx';
	$xmlpost=xmlHeader(array('encoding'=>'utf-8'));
	$xmlpost .= '<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">'."\n";
	$xmlpost .= integracoreSOAPHeader($params);
	$xmlpost .= '  <soap12:Body>'."\n";
	$xmlpost .= '    <getShipConfirms xmlns="http://www.integracoreb2b.com/API/" />'."\n";
	$xmlpost .= '  </soap12:Body>'."\n";
	$xmlpost .= '</soap12:Envelope>'."\n";
	$post=postXML($url,$xmlpost,array('-ssl'=>false));
	$orders=$post['xml_array']['soapBody']['getShipConfirmsResponse']['getShipConfirmsResult']['OrderStatus'];
	return $orders;
}
//---------------------------
function integracoreGetMultipleOrderStatus($params=array()){
	$url='http://www.integracoreb2b.com/API/ShipConfirm.asmx';
	$xmlpost=xmlHeader(array('encoding'=>'utf-8'));
	$xmlpost .= '<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">'."\n";
	$xmlpost .= integracoreSOAPHeader($params);
	$xmlpost .= '  <soap12:Body>'."\n";
	$xmlpost .= '<getMultipleOrderStatus xmlns="http://www.integracoreb2b.com/API/">'."\n";
    $xmlpost .= '  <OrderNumbers>'."\n";
    foreach($params['ordernumbers'] as $ordernumber){
    	$xmlpost .= '    <string>'.$ordernumber.'</string>'."\n";
	}
    $xmlpost .= '  </OrderNumbers>'."\n";
    $xmlpost .= '</getMultipleOrderStatus>'."\n";
	$xmlpost .= '  </soap12:Body>'."\n";
	$xmlpost .= '</soap12:Envelope>'."\n";
	$post=postXML($url,$xmlpost,array('-ssl'=>false));
	//echo printValue($post);
	if(!isset($post['xml_array']['soapBody']['getMultipleOrderStatusResponse']['getMultipleOrderStatusResult'])){
		return $post;
	}
	$result=$post['xml_array']['soapBody']['getMultipleOrderStatusResponse']['getMultipleOrderStatusResult'];
	if(isset($result['Order']) && is_array($result['Order'])){
		if(isset($result['Order']['OrderNumber'])){$result['Order'] = array($result['Order']);}
		//remove all the crap
		$list=array();
		foreach($result['Order'] as $order){
			$corder=array();
        	foreach($order as $key=>$val){
            	if(is_array($val) && (!count($val) || isset($val['xsinil']))){continue;}
            	if($key=='Items'){continue;}
            	$corder[$key]=$val;
			}
			if(is_array($order['Items']['Item'])){
				foreach($order['Items']['Item'] as $item){
	            	$citem=array();
	            	foreach($item as $key=>$val){
						if(is_array($val) && (!count($val) || isset($val['xsinil']))){continue;}
						$citem[$key]=$val;
					}
					$corder['Items']['Item'][]=$citem;
				}
			}
			$list[]=$corder;
		}
		return $list;
		}
	return $post;
}
//---------------------------
function integracoreGetMultipleOrderStatusNew($params=array()){
	$url='http://api.integracore.net/api';
	$xmlpost=xmlHeader(array('encoding'=>'utf-8'));
	$xmlpost .= '<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">'."\n";
	$xmlpost .= integracoreSOAPHeader($params);
	$xmlpost .= '  <soap12:Body>'."\n";
	$xmlpost .= '<getMultipleOrderStatus xmlns="http://www.integracoreb2b.com/API/">'."\n";
    $xmlpost .= '  <OrderNumbers>'."\n";
    foreach($params['ordernumbers'] as $ordernumber){
    	$xmlpost .= '    <string>'.$ordernumber.'</string>'."\n";
	}
    $xmlpost .= '  </OrderNumbers>'."\n";
    $xmlpost .= '</getMultipleOrderStatus>'."\n";
	$xmlpost .= '  </soap12:Body>'."\n";
	$xmlpost .= '</soap12:Envelope>'."\n";
	$post=postXML($url,$xmlpost,array('-ssl'=>false));
	if(!isset($post['xml_array']['getMultipleOrderStatus']['Orders'])){
		return $post;
	}
	$result=$post['xml_array']['getMultipleOrderStatus']['Orders'];
	if(isset($result['Order']) && is_array($result['Order'])){
		if(isset($result['Order']['OrderNumber'])){$result['Order'] = array($result['Order']);}
		return $result['Order'];
	}
	return $post;
}
//---------------------------
function integracoreGetMultipleOrderStatusWhere($params=array(),$testxml=0){
	$url='http://api.integracore.net/api';
	$xmlpost=xmlHeader(array('encoding'=>'utf-8'));
	$xmlpost .= '<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">'."\n";
	$xmlpost .= integracoreSOAPHeader($params);
	$xmlpost .= '  <soap12:Body>'."\n";
	$xmlpost .= '<getMultipleOrderStatusWhere xmlns="http://www.integracoreb2b.com/API/">'."\n";
	if(isset($params['pagenumber'])){
		$xmlpost .= '  <Pagenumber>'.$params['pagenumber'].'</Pagenumber>'."\n";
	}
	if(isset($params['tracking'])){
		$xmlpost .= '  <IncludeTracking>'.$params['tracking'].'</IncludeTracking>'."\n";
	}
    $xmlpost .= '  <Wheres>'."\n";
    foreach($params['wheres'] as $where){
		$xmlpost .= '  	<Where>'."\n";
    	$xmlpost .= "    	<Field>{$where[0]}</Field>\n";
    	$xmlpost .= "    	<Operator>{$where[1]}</Operator>\n";
    	$xmlpost .= "    	<Value>{$where[2]}</Value>\n";
    	$xmlpost .= '  	</Where>'."\n";
	}
    $xmlpost .= '  </Wheres>'."\n";
    $xmlpost .= '</getMultipleOrderStatusWhere>'."\n";
	$xmlpost .= '  </soap12:Body>'."\n";
	$xmlpost .= '</soap12:Envelope>'."\n";
	if($testxml==1){return $xmlpost;}
	$post=postXML($url,$xmlpost,array('-ssl'=>false));
	if(!isset($post['xml_array']['getMultipleOrderStatus']['Orders'])){
		return $post;
	}
	$result=$post['xml_array']['getMultipleOrderStatus']['Orders'];
	if(isset($result['Order']) && is_array($result['Order'])){
		if(isset($result['Order']['OrderNumber'])){$result['Order'] = array($result['Order']);}
		return $result['Order'];
	}
	return $post;
}
//---------------------------
function integracoreResetOrderStatus($params=array()){
	$url='http://www.integracoreb2b.com/API/ShipConfirm.asmx';
	$xmlpost=xmlHeader(array('encoding'=>'utf-8'));
	$xmlpost .= '<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">'."\n";
	$xmlpost .= integracoreSOAPHeader($params);
	$xmlpost .= '  <soap12:Body>'."\n";
	$xmlpost .= '<resetOrderNumbers xmlns="http://www.integracoreb2b.com/API/">'."\n";
    $xmlpost .= '  <OrderNumbers>'."\n";
    foreach($params['ordernumbers'] as $ordernumber){
    	$xmlpost .= '    <string>'.$ordernumber.'</string>'."\n";
	}
    $xmlpost .= '  </OrderNumbers>'."\n";
    $xmlpost .= '</resetOrderNumbers>'."\n";
	$xmlpost .= '  </soap12:Body>'."\n";
	$xmlpost .= '</soap12:Envelope>'."\n";
	$post=postXML($url,$xmlpost,array('-ssl'=>false));
	//return $post;
	$orders=$post['xml_array']['soapBody']['resetOrderNumbersResponse']['resetOrderNumbersResult']['OrderStatus'];
	return $orders;
}
//--ItemMaster Functions - http://www.integracoreb2b.com/API/ItemMaster.asmx
//---------------------------
function integracoreGetItems($params=array()){
	$url='http://www.integracoreb2b.com/API/ItemMaster.asmx';
	$xmlpost=xmlHeader(array('encoding'=>'utf-8'));
	$xmlpost .= '<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">'."\n";
	$xmlpost .= integracoreSOAPHeader($params);
	$xmlpost .= '  <soap12:Body>'."\n";
	$xmlpost .= '<getItems xmlns="http://www.integracoreb2b.com/API/">'."\n";
    $xmlpost .= '  <Items>'."\n";
    foreach($params['itemids'] as $itemid){
		$xmlpost .= '  	<Item>'."\n";
    	$xmlpost .= '    		<ItemID>'.$itemid.'</ItemID>'."\n";
    	$xmlpost .= '  	</Item>'."\n";
	}
    $xmlpost .= '  </Items>'."\n";
    $xmlpost .= '</getItems>'."\n";
	$xmlpost .= '  </soap12:Body>'."\n";
	$xmlpost .= '</soap12:Envelope>'."\n";
	$post=postXML($url,$xmlpost,array('-ssl'=>false));
	//return $post;
	$items=$post['xml_array']['soapBody']['getItemsResponse']['getItemsResult']['Item'];
	return $items;
}
//---------------------------
function integracoreAddItems($params=array()){
	$url='http://www.integracoreb2b.com/API/ItemMaster.asmx';
	$map=array(
		'description'		=> 'Description',
		'description2'		=> 'Description2',
		'item_status'		=> 'Item_Status',
		'revision'			=> 'Revision',
		'upccode'			=> 'UPCCode',
		'hts_num'			=> 'HTS_Num',
		'standardcost'		=> 'StandardCost',
		'saleprice'			=> 'SalePrice',
		'retailprice'		=> 'RetailPrice',
		'country_of_origin'	=> 'Country_Of_Origin',
		'item_width'		=> 'ItemWidth',
		'item_length'		=> 'ItemLength',
		'item_height'		=> 'ItemHeight',
		'item_weight'		=> 'ItemWeight'
	);
	$xmlpost=xmlHeader(array('encoding'=>'utf-8'));
	$xmlpost .= '<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">'."\n";
	$xmlpost .= integracoreSOAPHeader($params);
	$xmlpost .= '<soap12:Body>'."\n";
	$xmlpost .= '<addItems xmlns="http://www.integracoreb2b.com/API/">'."\n";
    $xmlpost .= '	<Items>'."\n";
    foreach($params['items'] as $item){
		$xmlpost .= '		<Item>'."\n";
    	$xmlpost .= '			<ItemID>'.$item['itemid'].'</ItemID>'."\n";
    	$xmlpost .= '			<SiteID>'.$item['siteid'].'</SiteID>'."\n";
    	$xmlpost .= '			<ItemInfo>'."\n";
    	//optional fields
    	foreach($item as $key=>$val){
			if(isset($map[$key])){
				$val=xmlEncodeCDATA($val);
				$key=$map[$key];
				$xmlpost .= "				<{$key}>{$val}</{$key}>\n";
            	}
		}
		$xmlpost .= '			</ItemInfo>'."\n";
		$xmlpost .= '		</Item>'."\n";
	}
    $xmlpost .= '	</Items>'."\n";
    $xmlpost .= '</addItems>'."\n";
	$xmlpost .= '</soap12:Body>'."\n";
	$xmlpost .= '</soap12:Envelope>'."\n";
	$post=postXML($url,$xmlpost,array('-ssl'=>false));
	//return $post;
	$items=$post['xml_array']['soapBody']['addItemsResponse']['addItemsResult']['Item'];
	return $items;
}
//---------------------------
function integracoreSetItems($params=array()){
	$url='https://www.integracoreb2b.com/API/ItemMaster.asmx';
	$map=array(
		'description'		=> 'Description',
		'description2'		=> 'Description2',
		'item_status'		=> 'Item_Status',
		'revision'			=> 'Revision',
		'upccode'			=> 'UPCCode',
		'hts_num'			=> 'HTS_Num',
		'standardcost'		=> 'StandardCost',
		'saleprice'			=> 'SalePrice',
		'retailprice'		=> 'RetailPrice',
		'country_of_origin'	=> 'Country_Of_Origin',
		'item_width'		=> 'ItemWidth',
		'item_length'		=> 'ItemLength',
		'item_height'		=> 'ItemHeight',
		'item_weight'		=> 'ItemWeight'
	);
	$xmlpost=xmlHeader(array('encoding'=>'utf-8'));
	$xmlpost .= '<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">'."\n";
	$xmlpost .= integracoreSOAPHeader($params);
	$xmlpost .= '<soap12:Body>'."\n";
	$xmlpost .= '<setItems xmlns="http://www.integracoreb2b.com/API/">'."\n";
    $xmlpost .= '	<Items>'."\n";
    foreach($params['items'] as $item){
		$xmlpost .= '		<Item>'."\n";
    	$xmlpost .= '			<ItemID>'.$item['itemid'].'</ItemID>'."\n";
    	$xmlpost .= '			<SiteID>'.$item['site_id'].'</SiteID>'."\n";
    	$xmlpost .= '			<ItemInfo>'."\n";
    	//optional fields
    	foreach($item as $key=>$val){
			if(isset($map[$key])){
				$val=xmlEncodeCDATA($val);
				$key=$map[$key];
				$xmlpost .= "				<{$key}>{$val}</{$key}>\n";
            	}
		}
		$xmlpost .= '			</ItemInfo>'."\n";
		$xmlpost .= '		</Item>'."\n";
	}
    $xmlpost .= '	</Items>'."\n";
    $xmlpost .= '</setItems>'."\n";
	$xmlpost .= '</soap12:Body>'."\n";
	$xmlpost .= '</soap12:Envelope>'."\n";
	$post=postXML($url,$xmlpost,array('-ssl'=>false));
	//return $post;
	$items=$post['xml_array']['soapBody']['setItemsResponse']['setItemsResult']['Item'];
	return $items;
}
//---------------------------
function integracoreSplitNote($str){
	//returns an array of notes no longer than 80 chars each without cutting words
	$str=preg_replace('/[\t\r\n]+/',' ',$str);
	$notes=preg_split('/<BR>/',wordwrap($str,80,'<BR>'));
	return $notes;
}
//---------------------------
function integracoreFTPData($data,$params=array()){
	$ftp_server='ftp.integracoreb2b.com';
	// set up basic connection
	$conn_id = ftp_connect($ftp_server);
	$rtn=1;
	if(is_resource($conn_id)){
		// login with username and password
		if(ftp_login($conn_id, $params['user'], $params['pass'])){
			//create a temp file to transfer
			if (ftp_chdir($conn_id, "distorders")) {
				$file=$params['custid'].'_'.date("Ymd_His").'.txt';
				$afile="/home/basgetti/syncingship.com/clients/snag/orderfiles/{$file}";
				$ok=setFileContents($afile,$data);
				// upload a file
				if (ftp_put($conn_id, $file, $afile, FTP_ASCII)) {
			 		$rtn=1;
					}
				else {
			 		$rtn = "There was a problem while uploading $file\n";
					}
				}
			}
		else{$rtn="Unable to login to FTP Server: {$ftp_server}, User: {$params['user']}";}
		// close the connection
		ftp_close($conn_id);
		}
	else{
		$rtn="Unable to connect to FTP Server: {$ftp_server}";
    	}
	return $rtn;
	}
//---------------------------
function integracoreSOAPHeader($params=array()){
	$xmlpost = '  <soap12:Header>'."\n";
	$xmlpost .= '    <Authenticate xmlns="http://www.integracoreb2b.com/API/">'."\n";
	$val=xmlEncodeCDATA($params['user']);
	$xmlpost .= '      <UserName>'.$val.'</UserName>'."\n";
	$val=xmlEncodeCDATA($params['pass']);
	$xmlpost .= '      <Password>'.$val.'</Password>'."\n";
	if(isset($params['test'])){
		$xmlpost .= '      <Test>'.$params['test'].'</Test>'."\n";
	}
	else{
    	$xmlpost .= '      <Test>false</Test>'."\n";
	}
	$xmlpost .= '    </Authenticate>'."\n";
	$xmlpost .= '  </soap12:Header>'."\n";
	return $xmlpost;
}
//-------------------------------
function integracoreGetInventory($params=array()){
	//info: returns all the inventory for the client.  if $params[sku] is passed in, it returns only that item
	//info: first call to this function requires user and pass.
	//require user and pass
	$xmlpost=xmlHeader(array('encoding'=>'utf-8'));
	$xmlpost .= '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'."\n";
	$xmlpost .= '  <soap:Header>'."\n";
	$xmlpost .= '    <AuthHeader xmlns="http://www.integracoreb2b.com/">'."\n";
	$xmlpost .= '      <Username>'.$params['user'].'</Username>'."\n";
	$xmlpost .= '      <Password>'.$params['pass'].'</Password>'."\n";
	//$xmlpost .= '      <Test>boolean</Test>'."\n";
	$xmlpost .= '    </AuthHeader>'."\n";
	$xmlpost .= '  </soap:Header>'."\n";
	$xmlpost .= '  <soap:Body>'."\n";
	$xmlpost .= '    <GetCompleteInventory xmlns="http://www.integracoreb2b.com/" />'."\n";
	$xmlpost .= '  </soap:Body>'."\n";
	$xmlpost .= '</soap:Envelope>'."\n";
	$url='https://www.integracoreb2b.com/IntCore/Inventory.asmx';
	$post=postXML($url,$xmlpost,array('-ssl'=>false));
	if(!isset($post['xml_array']['soapBody']['GetCompleteInventoryResponse']['GetCompleteInventoryResult']['InventoryList']['ItemInventory'])){
    	echo "ERROR" . printValue($post['xml_array']);
    	exit;
	}
	$info=array('items'=>array());
	$fields=array();
	foreach($post['xml_array']['soapBody']['GetCompleteInventoryResponse']['GetCompleteInventoryResult']['InventoryList']['ItemInventory'] as $item){
		$sku=$item['PartNumber'];
		$crec=array();
		foreach($item as $key=>$val){
			if(!preg_match('/^(SiteId|PartNumber)$/i',$key)){$fields[$key]+=1;}
			$info['items'][$sku][$key]=removeCdata($val);
			}
    	}
    if(!count($info['items'])){return $post;}
	$info['fields']=array_keys($fields);
	sort($info['fields']);
	$_SERVER['_cache_']['integracoreGetInventory']=$info;
    return $info;
	}
//-------------------------------
function integracoreGetOrderStatus($params=array()){
	//info: returns the order status for ordernumbers
	//info: this function requires user and pass and an array of ordernumbers.
	//usage: $status=integracoreGetOrderStatus(array('user'=>"username",'pass'=>"password",'ordernumbers'=>array('ON12343','ON34323','ON752345')));
	//require user and pass
	$xmlpost=xmlHeader(array('encoding'=>'utf-8'));
	$xmlpost .= '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'."\n";
	$xmlpost .= '	<soap:Header>'."\n";
	$xmlpost .= '		<AuthHeader xmlns="http://www.integracoreb2b.com/">'."\n";
	$xmlpost .= '			<Username>'.$params['user'].'</Username>'."\n";
	$xmlpost .= '      		<Password>'.$params['pass'].'</Password>'."\n";
	$xmlpost .= '    	</AuthHeader>'."\n";
	$xmlpost .= '  	</soap:Header>'."\n";
	$xmlpost .= '	<soap:Body>'."\n";
	$xmlpost .= ' 		<GetMultipleOrderStatus xmlns="http://www.integracoreb2b.com/">'."\n";
    $xmlpost .= '  			<Orders>'."\n";
    foreach($params['orders'] as $on){
    	$xmlpost .= '  				<string>'.$on.'</string>'."\n";
		}
    $xmlpost .= '  			</Orders>'."\n";
    $xmlpost .= '  		</GetMultipleOrderStatus>'."\n";
	$xmlpost .= '	</soap:Body>'."\n";
	$xmlpost .= '</soap:Envelope>'."\n";
	$url='https://www.integracoreb2b.com/IntCore/Inventory.asmx';
	$post=postXML($url,$xmlpost,array('-ssl'=>false,'-soap'=>1));
	//echo "integracoreGetOrderStatus:" . printValue($post);
 	$arr=$post['xml_array'];
 	$orders=$arr['soapBody']['GetMultipleOrderStatusResponse']['GetMultipleOrderStatusResult']['OrderStatusList']['OrderStatus'];
	$info=array('orders'=>array());
	$fields=array();
	foreach($orders as $order){
		$ordernumber=$order['Order'];
		foreach($order as $key=>$val){
			$key=strtolower($key);
			$fields[$key]+=1;
			switch($key){
				case 'trackingnumbers':
					if(is_array($val['string'])){$info['orders'][$ordernumber][$key]=$val['string'];}
					else{$info['orders'][$ordernumber][$key][]=$val['string'];}
					break;
            	case 'carrierservicecode':
					$tmp=preg_split('/\ /',$val);
					$info['orders'][$ordernumber][$key]=$val;
					$info['orders'][$ordernumber]['carrier']=$tmp[0];
					break;
            	case 'status':
					$info['orders'][$ordernumber][$key]=integracoreStatusString($val);
					$info['orders'][$ordernumber]["{$key}_code"]=$val;
            		break;
            	case 'shipdate':
					$utime=strtotime($val);
					$info['orders'][$ordernumber][$key]=date("Y-m-d H:i:s",$utime);
					$info['orders'][$ordernumber]["{$key}_utime"]=$utime;
					break;
				case 'details':
					$items=array();
					if(is_array($val['ItemInformation'][0])){$items=$val['ItemInformation'];}
					else{$items[]=$val['ItemInformation'];}
					foreach($items as &$item){
						$item=array_change_key_case($item);
                    	}
                    $info['orders'][$ordernumber]['items']=$items;
					break;
				case 'order':
					$key='ordernumber';
				default:
					$info['orders'][$ordernumber][$key]=$val;
					break;
				}
			}
    	}
    if(!count($info['orders'])){return $post;}
    return $info['orders'];
	$info['fields']=array_keys($fields);
	sort($info['fields']);
    return $info;
	}
//-------------------------------
function integracorePostOrders($orders=array(),$testxml=0){
	//info: returns the order status for ordernumbers
	//info: this function requires user and pass and an array of ordernumbers.
	//usage: $status=integracoreGetOrderStatus(array('user'=>"username",'pass'=>"password",'ordernumbers'=>array('ON12343','ON34323','ON752345')));
	//https://www.integracoreb2b.com/IntCore/IncomingOrders.asmx
	global $import_rules;
	$xmlpost=xmlHeader(array('encoding'=>'utf-8')) . "\r\n";
	$xmlpost .= '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'."\r\n";
	$xmlpost .= '	<soap:Header>'."\r\n";
	$xmlpost .= '		<AuthHeader xmlns="http://www.integracoreb2b.com/">'."\r\n";
	$xmlpost .= '			<Username>'.$orders['user'].'</Username>'."\r\n";
	$xmlpost .= '      		<Password>'.$orders['pass'].'</Password>'."\r\n";
	$xmlpost .= '    	</AuthHeader>'."\r\n";
	$xmlpost .= '  	</soap:Header>'."\r\n";
	$xmlpost .= '<soap:Body>'."\r\n";
	$xmlpost .= '	<OrderImport xmlns="http://www.integracoreb2b.com/">'."\r\n";
	$xmlpost .= '		<Orders>'."\r\n";
	$rules=$import_rules['xml'];
	foreach($orders['orders'] as $order){
		$xmlpost .= '			<Order>'."\n";
		//add header values
		$used['H']=array();
		foreach($rules['H'] as $rule){
			$dbfield=$rule['db_field'];
			$soapfield=$rule['field_soap'];
			if(isset($used['H'][$soapfield])){continue;}
			$used['H'][$soapfield]=1;
			if(!strlen($soapfield)){continue;}
			$val=$order[$dbfield];
			if(is_array($val)){continue;}
			if(!strlen($val) && strlen($rule['value_default'])){
				$evalstr=preg_replace('/\&amp\;/','&',trim($rule['value_default']));
				$evalstr=preg_replace('/\&quot\;/','"',$evalstr);
				if(stringBeginsWith($evalstr,'&')){
					$evalstr=preg_replace('/^\&/','return ',$evalstr);
					$evalstr.=';';
					$val=eval($evalstr);
				}
				//echo "EVAL:{$dbfield} [{$evalstr}] = [{$val}]<br>\n";
			}
			if(strlen($val)){
				$val=xmlEncodeCDATA($val);
				$xmlpost .= "				<{$soapfield}>{$val}</{$soapfield}>\r\n";
			}
			//else{$xmlpost .= "				<{$soapfield} />\r\n";}
        }
        //Order Details
        $xmlpost .= '				<OrderDetails>'."\r\n";
        foreach($order['items'] as $item){
			$xmlpost .= '					<OrderDetail>'."\r\n";
			$used['D']=array();
	        foreach($rules['D'] as $rule){
				$dbfield=$rule['db_field'];
				$soapfield=$rule['field_soap'];
				if(isset($used['D'][$soapfield])){continue;}
				$used['D'][$soapfield]=1;
				if(!strlen($soapfield)){continue;}
				$val=$item[$dbfield];
				if(!strlen($val) && strlen($rule['value_default'])){$val=evalPHP($rule['value_default']);}
				if(strlen($val)){
					$val=xmlEncodeCDATA($val);
					$xmlpost .= "						<{$soapfield}>{$val}</{$soapfield}>\r\n";
				}
				//else{$xmlpost .= "					<{$soapfield} />\r\n";}
	        }
	        $xmlpost .= '					</OrderDetail>'."\r\n";
		}
        $xmlpost .= '				</OrderDetails>'."\r\n";
        //Packing Slip?
        if(isset($order['packingslip'])){
			//echo printValue($rules['P']);
        	//Order Details
        	$xmlpost .= '				<PackingSlip>'."\r\n";
        	$used['P']=array();
        	foreach($rules['P'] as $rule){
				$dbfield=$rule['db_field'];
				$soapfield=$rule['field_soap'];
				if(!strlen($soapfield)){continue;}
				if(isset($used['P'][$soapfield])){continue;}
				$used['P'][$soapfield]=1;
				$val=$order['packingslip'][$dbfield];
				if(!strlen($val) && strlen($rule['value_default'])){$val=evalPHP($rule['value_default']);}
				if(strlen($val)){
					$val=xmlEncodeCDATA($val);
					$xmlpost .= "					<{$soapfield}>{$val}</{$soapfield}>\r\n";
				}
	        }
	        //PackingSlipChildren
			$xmlpost .= '					<PackingSlipChildren>'."\r\n";
			foreach($order['packingslip']['items'] as $item){
				$xmlpost .= '						<PackingSlipChild>'."\r\n";
				$used['S']=array();
	        	foreach($rules['S'] as $rule){
					$dbfield=$rule['db_field'];
					$soapfield=$rule['field_soap'];
					if(!strlen($soapfield)){continue;}
					if(isset($used['S'][$soapfield])){continue;}
					$used['S'][$soapfield]=1;
					$val=$item[$dbfield];
					if(!strlen($val) && strlen($rule['value_default'])){$val=evalPHP($rule['value_default']);}
					if(strlen($val)){
						$val=xmlEncodeCDATA($val);
						$xmlpost .= "							<{$soapfield}>{$val}</{$soapfield}>\r\n";
					}
		        }

				$xmlpost .= '						</PackingSlipChild>'."\r\n";
			}
			$xmlpost .= '					</PackingSlipChildren>'."\r\n";
        	$xmlpost .= '				</PackingSlip>'."\r\n";
		}

		//Order Notes
		if(isset($order['notes']) && is_array($order['notes']) && count($order['notes'])){
			$xmlpost .= '				<OrderNotes>'."\r\n";
	        foreach($order['notes'] as $note){
				$xmlpost .= '					<OrderNote>'."\r\n";
				$used['N']=array();
		        foreach($rules['N'] as $rule){
					$dbfield=$rule['db_field'];
					$soapfield=$rule['field_soap'];
					if(isset($used['N'][$soapfield])){continue;}
					$used['N'][$soapfield]=1;
					if(!strlen($soapfield)){continue;}
					$val=$note[$dbfield];
					if(!strlen($val) && strlen($rule['value_default'])){$val=evalPHP($rule['value_default']);}
					if(strlen($val)){
						$val=xmlEncodeCDATA($val);
						$xmlpost .= "					<{$soapfield}>{$val}</{$soapfield}>\r\n";
					}
					//else{$xmlpost .= "					<{$soapfield} />\r\n";}
		        }
		        $xmlpost .= '					</OrderNote>'."\r\n";
			}
	        $xmlpost .= '				</OrderNotes>'."\r\n";
	    }
        //End Order
		$xmlpost .= '			</Order>'."\r\n";
    }
	$xmlpost .= '			</Orders>'."\r\n";
	$xmlpost .= '		</OrderImport>'."\r\n";
	$xmlpost .= '	</soap:Body>'."\r\n";
	$xmlpost .= '</soap:Envelope>'."\r\n";
	//echo $xmlpost;exit;
	if($testxml==1){return $xmlpost;}
	//echo "XML:<br>\n" . $xmlpost;
	//return;
	$url='https://www.integracoreb2b.com/IntCore/IncomingOrders.asmx';
	if(isset($_REQUEST['-test'])){
    	$url='https://www.integracoreb2b.com/IntCore/Test/IncomingOrders.asmx';
	}
	$results=array(
		'xml_in'=>$xmlpost,
		'url'=>$url
	);
	$post=postXML($url,$xmlpost,array('-ssl'=>false,'-soap'=>1));
 	$arr=$post['xml_array'];
	$results['xml_array']=$arr;
 	$recs=$arr['soapBody']['OrderImportResponse']['OrderImportResult']['OrderMessage']['OrderResult'];
 	if(!is_array($recs)){
		$results['_status']='FAILED';
		$results['_errors'][]=$arr['soapBody']['soapFault']['faultstring'];
    	$results['xpost']=$post;
    	ksort($results);
    	return $results;
	}
	$results['_status']='SUCCESS';
	$results['xpost']=$post;
	if(isset($recs['orderNumber'])){$results['orders'][]=$recs;}
	else{$results['orders']=$recs;}

	 //echo "integracorePostOrders Recs:" . printValue($recs);
 	//echo printValue($recs);
 	$failed=array();
 	foreach($results['orders'] as $rec){
		if(!strlen($rec['orderNumber'])){
			$results['_errors'][]=$rec;
			continue;
		}
		unset($id);unset($m);
		if(preg_match('/([0-9]+)$/',$rec['orderNumber'],$m)){$id=$m[1];}
		if(!isset($id)){
			$results['_errors'][]=$rec;
			continue;
		}
		$results['orderids'][]=$id;
	}
	ksort($results);
	return $results;
}
//---------------------------
function integracoreStatusString($code=''){
	switch(strtoupper($code)){
		case 'N':
			return 'Not Scheduled';
			break;
		case 'S':
			return 'Scheduled';
			break;
		case 'B':
			return 'Being Picked';
			break;
		case 'P': 
			return 'Partial Pick';
			break;
		case 'D':
			return 'Being Loaded';
			break;
		case 'L':
			return 'Loading Dock';
			break;
		case 'G':
			return 'On the truck';
			break;
		case 'T':
			return 'On the Truck';
			break;
		case 'I': 
			return 'Inventory Hold';
			break;
		case 'O':
			return 'Pick Complete in Transit';
			break;
		case 'H': 
			return 'Credit Hold';
			break;
		case 'K':
			return 'Pick Hold';
			break;
		case 'C': 
			return 'Confirm Shipped';
			break;
		case 'X':
			return 'Canceled';
			break;
		case 'W':
			return 'Web Pending';
			break;
		case 'U':
			return 'Unlocked';
			break;
        }
    return "Unknown";
	}
//---------------------------
function integracoreImportRules(){
  	//Load in Integracore import rules.
	$progpath=dirname(__FILE__);
	$file="{$progpath}/integracore_import_rules.sdf";
	$serdata=getFileContents($file);
	$import_rules=unserialize($serdata);
	if(!isset($import_rules['flat']['H'])){
		echo "ERROR: invalid rules!";
		exit;
	}
    return $import_rules;
  	}
//---------------------------
function integracoreFixOrderErrors($orders,$debug=0){
	global $import_rules;
	if(!is_array($orders) || !isset($orders['orders'])){return array('integracoreFixOrderErrors failed ',$orders);}
	global $USER;
	//if($USER['username']=='slloyd'){echo "orders" . printValue($orders);}
	//$orders['timing']['fix_start']=time();
	$orders['count_warnings']=0;
  	$orders['count_errors']=0;
  	$orders['count_defaults']=0;
  	$orders['count_orders_noerrors']=0;
  	unset($orders['orders']['_errors']);
  	unset($orders['orders']['_warnings']);
  	unset($orders['orders']['_defaults']);
  	//check for _format_error
  	if(isset($orders['_format_error']) && strlen($orders['_format_error'])){
    	$orders['_errors'][]=$orders['_format_error'];
    	$orders['count_errors']+=1;
      	}
    $orders['custid']=strtoupper($orders['custid']);
  	//verify order information
  	for($x=0;$x<$orders['count_orders'];$x++){
    	//$orders['timing']['order'][$orders['orders'][$x]['ordernumber']]=time();
    	$order_errors=0;
    	//verify at least one item is attached to this order
     	if(!isset($orders['orders'][$x]['items'])){
      		$orders['orders'][$x]['_errors'][]="No order items attached to this order";
      		$orders['count_errors']+=1;
      		$order_errors++;
        	}
    	//Check for any missing required fields
    	foreach($import_rules['flat']['H'] as $index=>$rule){
      		$field=$rule['db_field'];
      		if(strlen($field) && $rule['required']==1 && !isset($orders['orders'][$x][$field])){$orders['orders'][$x][$field]='';}
      		}
	    if(strlen($orders['orders'][$x]['freight_bill_acct_num']) && strlen($orders['orders'][$x]['freight_bill_acct_num']) < 3){
	    	$orders['orders'][$x]['freight_bill_acct_num']='';
	    	$orders['orders'][$x]['_warnings'][]="freight_bill_acct_num was under 3 chars - invalid account - removed";
	        $orders['count_warnings']+=1;
	    	}
        //Default Site ID
        if(!isset($orders['orders'][$x]['site_id']) || !strlen($orders['orders'][$x]['site_id'])){$orders['orders'][$x]['site_id']=1;}
      	}
    if($debug==1){echo time().",[HeaderRulesStart] {$x} {$orders['orders'][$x]['ordernumber']}<br>\n";}
    //Header Rules Check for Orders
	foreach($orders['orders'][$x] as $key=>$val){
      	if($key=='items' || is_array($val)){continue;}
      	$val=trim($val);
      	if(preg_match('/^\_/',$key)){continue;}
      	if(preg_match('/^\"(.+?)\"$/',$val,$qmatch)){$val=$qmatch[1];}
      	$uckey=strtoupper($key);
      	$rule=$import_rules['xml']['H'][$uckey];
        //enum value
        if(!strlen($val) && strlen($rule['value_enum'])){
        	$opts=explode(',',$rule['value_enum']);
          	$val=integracoreParseValue($opts[0]);
          	$orders['orders'][$x][$key]=$val;
          	$orders['orders'][$x]['_defaults'][]="Missing '{$key}': set to first enum value '{$val}'";
          	$orders['count_defaults']+=1;
          	}
        //set ShipToName to ShipToContact if blank
        if(!strlen($val) && $rule['db_field']=='shiptoname'){
          	$fields=array(
	            $import_rules['xml']['H']['SHIPTOCONTACT']['db_field'],
	            $import_rules['xml']['H']['SHIPTOCONTACT']['field_import'],
	            $import_rules['xml']['H']['SHIPTOCONTACT']['field_import2'],
	            $import_rules['xml']['H']['SHIPTOCONTACT']['field_soap']
	            );
          	foreach($fields as $field){
            	if(strlen($orders['orders'][$x][$field])){$val=$orders['orders'][$x][$field];break;}
            	}
          	if(strlen($val)){
            	$orders['orders'][$x][$key]=$val;
            	$orders['orders'][$x]['_defaults'][]="Missing '{$key}': set to ShipToContact value";
            	$orders['count_defaults']+=1;
            	}
          	}
        //set ShipToContact to ShipToName if blank
        if(!strlen($val) && $rule['db_field']=='shiptocontact'){
          	$fields=array(
	            $import_rules['xml']['H']['SHIPTONAME']['db_field'],
	            $import_rules['xml']['H']['SHIPTONAME']['field_import'],
	            $import_rules['xml']['H']['SHIPTONAME']['field_import2']
	            );
          	foreach($fields as $field){
            	if(strlen($orders['orders'][$x][$field])){$val=$orders['orders'][$x][$field];break;}
            	}
          	if(strlen($val)){
	            $orders['orders'][$x][$key]=$val;
	            $orders['orders'][$x]['_defaults'][]="Missing '{$key}': set to ShipToName value";
	            $orders['count_defaults']+=1;
	            }
          	}
        //Throw an error if still blank
        if(!strlen($val)){
          	if(preg_match('/zipcode$/i',$key) && !preg_match('/(USA|United States|America)/i',$orders['orders'][$x]['shiptocountry'])){}
          	elseif(isset($rule['required']) && $rule['required']==1){
	            $orders['orders'][$x]['_errors'][]="Missing Field '{$key}'";
	            $orders['count_errors']+=1;
	            $order_errors++;
	            }
            }
    	//Check for valid database type based on the database
    	if(strlen($val)){
	    	switch($rule['db_type']){
	          	case 'int':
	            	if(!preg_match('/^[0-9]+$/',$val)){
	              		$orders['orders'][$x]['_errors'][]="Value of '{$val}' is a Data type({$rule['db_type']}) mismatch: '{$key}' must be of type {$rule['db_type']}";
	              		$orders['count_errors']+=1;
	              		$order_errors++;
	                    }
	            	break;
	          	case 'real':
	          	case 'money':
	          	case 'numeric':
	            	if(preg_match('/^[0-9]+\.[0-9]+$/',$val)){
	              		$newval=number_format($val,2,'.','');
	              		if((string)$newval !== (string)$val){
	                		$orders['orders'][$x]['_defaults'][]="Value of '{$val}' is a Data type({$rule['db_type']}) mismatch: '{$key}' changed to '{$newval}'";
	                		$orders['count_defaults']+=1;
	                		$val=$newval;
	                		$orders['orders'][$x][$key]=$val;
	                		}
	              		}
	            	if(!preg_match('/^[0-9\.]+$/',$val)){
		              	$orders['orders'][$x]['_errors'][]="Value of '{$val}' is a Data type({$rule['db_type']}) mismatch: '{$key}' must be of type {$rule['db_type']}";
		              	$orders['count_errors']+=1;
		              	$order_errors++;
	                    }
	            	break;
	    		case 'datetime':
	            	$t=strtotime($val);
	            	$d=date("Y",$t);
	            	if($d==1969){$isValidType=0;}
	            	else{
	              		$newval=date("c",$t);
	              		if((string)$newval !== (string)$val){
	                		$orders['orders'][$x]['_defaults'][]="Value of '{$val}' is a Data type({$rule['db_type']}) mismatch '{$key}' changed to '{$newval}'";
	                		$orders['count_defaults']+=1;
	                		$val=$newval;
	                		$orders['orders'][$x][$key]=$val;
	                		}
	              		}
	            	break;
	    		case 'char':
	            	//remove any slashes
	            	$val=stripslashes(stripslashes(stripslashes(stripslashes(stripslashes($val)))));
	            	//remove begin and end quotes
	            	if(preg_match('/^[\"\']+(.+)[\"\']+$/',$val,$vm)){
	              		$orders['orders'][$x]['_warnings'][]="Removed quotes from '{$key}'. changed from '{$val}' to '{$vm[1]}'";
	              		$orders['count_warnings']+=1;
	              		$val=$vm[1];
	              		}
	            	//truncate if close
	            	if(isNum($rule['db_len'])){
	              		$newval=substr($val,0,$rule['db_len']);
	              		if((string)$newval !== (string)$val){
	                		$val_len=strlen($val);
	                		$orders['orders'][$x]['_warnings'][]="'{$key}' too long. {$val_len} of {$rule['db_len']} char limit. Truncated from '{$val}' to '{$newval}'.";
	                		$orders['count_warnings']+=1;
	                		$val=$newval;
	                		$orders['orders'][$x][$key]=$val;
	                		}
	              		}
	            	break;
	            }
	    	//Check for limit constraints
	        if(isNum($rule['db_len']) && strlen($val) > $rule['db_len']){
	        	if(preg_match('/^(billto|shiptoname|shiptocontact|shiptoaddress|shiptotelephone)/i',$key) || ($key=='itemid' && preg_match('/^(SIGN)$/i',$orders['custid']))){
	            	$orders['orders'][$x]['_warnings'][]="Field limit exceeded: '{$key}' length is limited to {$rule['db_len']}. '{$val}' is too long.";
	            	$orders['count_warnings']+=1;
	            	}
	          	else{
	            	$orders['orders'][$x]['_errors'][]="Field limit exceeded: '{$key}' length is limited to {$rule['db_len']}. '{$val}' is too long.";
	            	$orders['count_errors']+=1;
	            	$order_errors++;
	            	}
	            }
	        }
		//end of orders check
	    }
	if($debug==1){echo time().",[DetailRulesStart] {$x} {$orders['orders'][$x]['ordernumber']}<br>\n";}
	//Notes
    if(is_array($orders['orders'][$x]['notes'])){
      	$linenumbers=array();
    	for($i=0;$i<count($orders['orders'][$x]['notes']);$i++){
        	//make sure linenumb is zero
        	$orders['orders'][$x]['notes'][$i]['linenum']=0;
        	//default site id
        	if(!isset($orders['orders'][$x]['notes'][$i]['site_id']) || !strlen($orders['orders'][$x]['notes'][$i]['site_id'])){
          		$orders['orders'][$x]['notes'][$i]['site_id']=$orders['orders'][$x]['site_id'];
          		}
        	//make sure detail site_id matches header site_id
        	if($orders['orders'][$x]['notes'][$i]['site_id'] != $orders['orders'][$x]['site_id']){
	          	$orders['orders'][$x]['notes'][$i]['_warnings'][]="Detail site_id {$orders['orders'][$x]['items'][$i]['site_id']} does not match Header site_id {$orders['orders'][$x]['site_id']}.";
	          	$orders['count_warnings']+=1;
	          	$order_warnings++;
	          	$orders['orders'][$x]['notes'][$i]['site_id']=$orders['orders'][$x]['site_id'];
	          	}
        	}
      	}
    //Detail Rules Check for order items
    $orders['count_order_items']+=count($orders['orders'][$x]['items']);
    $linenumbers=array();
    for($i=0;$i<count($orders['orders'][$x]['items']);$i++){
    	//make sure linenumber is incrimental and uinque
      	if(!isNum($orders['orders'][$x]['items'][$i]['linenumber'])){
        	$orders['orders'][$x]['items'][$i]['linenumber']=1;
        	}
      	if(!in_array($orders['orders'][$x]['items'][$i]['linenumber'],$linenumbers)){
        	$orders['orders'][$x]['items'][$i]['linenumber']=count($linenumbers)+1;
        	$linenumbers[]=$orders['orders'][$x]['items'][$i]['linenumber'];
        	}
      	//default site id
      	if(!isset($orders['orders'][$x]['items'][$i]['site_id']) || !strlen($orders['orders'][$x]['items'][$i]['site_id'])){
        	$orders['orders'][$x]['items'][$i]['site_id']=$orders['orders'][$x]['site_id'];
        	}
      	//make sure detail site_id matches header site_id
      	if($orders['orders'][$x]['items'][$i]['site_id'] != $orders['orders'][$x]['site_id']){
        	$orders['orders'][$x]['items'][$i]['_warnings'][]="Detail site_id {$orders['orders'][$x]['items'][$i]['site_id']} does not match Header site_id {$orders['orders'][$x]['site_id']}.";
        	$orders['count_warnings']+=1;
        	$order_warnings++;
        	$orders['orders'][$x]['items'][$i]['site_id']=$orders['orders'][$x]['site_id'];
        	}
      	//add any missing required fields to this item
      	foreach($import_rules['flat']['D'] as $index=>$rule){
        	if(strlen($rule['db_field']) && $rule['required']==1 && !isset($orders['orders'][$x]['items'][$i][$rule['db_field']])){$orders['orders'][$x]['items'][$i][$rule['db_field']]='';}
        	}
      	foreach($orders['orders'][$x]['items'][$i] as $key=>$val){
        	$val=trim($val);
        	$uckey=strtoupper($key);
        	$rule=$import_rules['xml']['D'][$uckey];
        	if($orders['_format']=='xml' && !strlen($val)){
          		switch($key){
            		case 'ordernumber': $val=$orders['orders'][$x]['ordernumber'];break;
            		case 'linenumber':$val=$i+1;
            		}
          		$orders['orders'][$x]['items'][$i][$key]=$val;
          		}
        	//required?
        	if($rule['required']==1){
          		//default value?
          		if(!strlen($val) && strlen($rule['value_default'])){
            		$val=integracoreParseValue($rule['value_default']);
            		$orders['orders'][$x]['items'][$i][$key]=$val;
            		if($key != 'orderfilled'){
              			$orders['orders'][$x]['items'][$i]['_defaults'][]="Missing '{$key}': set to default value '{$val}'";
              			$orders['count_defaults']+=1;
              			}
            		}
          		//enum value
          		if(!strlen($val) && strlen($rule['value_enum'])){
            		$opts=explode(',',$rule['value_enum']);
            		$val=integracoreParseValue($opts[0]);
            		$orders['orders'][$x]['items'][$i][$key]=$val;
            		$orders['orders'][$x]['items'][$i]['_defaults'][]="Missing '{$key}': set to first enum value '{$val}'";
            		$orders['count_defaults']+=1;
            		}
          		//Throw an error if still blank
          		if(!strlen($val)){
            		$orders['orders'][$x]['items'][$i]['_errors'][]="Missing '{$key}'";
            		$orders['count_errors']+=1;
            		$order_errors++;
                    }
            	}
            if(strlen($val)){
            	//Check for valid database type based on the database
            	switch($rule['db_type']){
            		case 'int':
              			if(!preg_match('/^[0-9]+$/',$val)){
                			$orders['orders'][$x]['items'][$i]['_errors'][]="Value of '{$val}' is a Data type({$rule['db_type']}) mismatch '{$key}' must be of type {$rule['db_type']}";
                			$orders['count_errors']+=1;
                			$order_errors++;
                          	}
              			break;
            		case 'real':
            		case 'money':
            		case 'numeric':
              			if(preg_match('/^[0-9]+\.[0-9]+$/',$val)){
                			$newval=number_format($val,2,'.','');
                			if((string)$newval !== (string)$val){
                  				$orders['orders'][$x]['items'][$i]['_defaults'][]="Value of '{$val}' is a Data type({$rule['db_type']}) mismatch: '{$key}' changed to '{$newval}'";
                  				$orders['count_defaults']+=1;
                  				$val=$newval;
                  				$orders['orders'][$x]['items'][$i][$key]=$val;
                  				}
                			}
              			if(!preg_match('/^[0-9\.]+$/',$val)){
                			$orders['orders'][$x]['items'][$i]['_errors'][]="Value of '{$val}' is a Data type({$rule['db_type']}) mismatch '{$key}' must be of type {$rule['db_type']}";
                			$orders['count_errors']+=1;
                			$order_errors++;
                          	}
              			break;
            		case 'datetime':
              			$t=strtotime($val);
              			$d=date("Y",$t);
              			if($d==1969){$isValidType=0;}
              			else{
                			$newval=date("c",$t);
                			if((string)$newval !== (string)$val){
                  				$orders['orders'][$x]['items'][$i]['_warnings'][]="Value of '{$val}' is a Data type({$rule['db_type']}) mismatch '{$key}' changed to  {$newval}";
                  				$orders['count_warnings']+=1;
                  				$val=$newval;
                  				$orders['orders'][$x]['items'][$i][$key]=$val;
                  				}
                			}
              			break;
            		case 'char':
              			//remove any slashes
              			$val=stripslashes(stripslashes(stripslashes(stripslashes(stripslashes($val)))));
              			//remove begin and end quotes
              			if(preg_match('/^[\"\']+(.+)[\"\']+$/',$val,$vm)){
                			$orders['orders'][$x]['items'][$i]['_warnings'][]="Removed quotes from '{$key}'. changed from '{$val}' to '{$vm[1]}'";
                			$orders['count_warnings']+=1;
                			$val=$vm[1];
                			}
              			//truncate if close
              			if(isNum($rule['db_len'])){
                			$newval=substr($val,0,$rule['db_len']);
                			if((string)$newval !== (string)$val){
                  				$orders['orders'][$x]['items'][$i]['_warnings'][]="Data type({$rule['db_type']}) mismatch '{$key}' value truncated from '{$val}' to '{$newval}'";
                  				$orders['count_warnings']+=1;
                  				$val=$newval;
                  				$orders['orders'][$x]['items'][$i][$key]=$val;
                  				}
                			}
              			break;
                	}
          		//Check for limit constraints
          		if(isNum($rule['db_len']) && strlen($val) > $rule['db_len']){
            		if(preg_match('/^(billto|shiptoname|shiptocontact|shiptoaddress)/i',$key)  || ($key=='itemid' && preg_match('/^(SIGN)$/i',$orders['custid']))){
              			$orders['orders'][$x]['items'][$i]['_warnings'][]="Field limit exceeded: '{$key}' length is limited to {$rule['db_len']}. '{$val}' is too long.";
              			$orders['count_warnings']+=1;
                        }
                    else{
              			$orders['orders'][$x]['items'][$i]['_errors'][]="Field limit exceeded: '{$key}' length is limited to {$rule['db_len']}. '{$val}' is too long.";
              			$orders['count_errors']+=1;
              			$order_errors++;
              			}
                  	}
          		}
        	//end of details check
        	}
        }
    if($order_errors==0){$orders['count_orders_noerrors']++;}
    if($orders['count_order_items']==0){
		$orders['_errors'][]="No Order Items found";
      	}
    //organize any errors
	foreach($orders['orders'] as &$order){
		$order['_errors_cnt']=0;
		$ordernumber=strtoupper(trim($order['ordernumber']));
	  	if(isset($order['_errors']) && is_array($order['_errors'])){
	    	foreach($order['_errors'] as $error){
	        	$orders['_orders_with_errors'][$ordernumber]['header'][] = $error;
	        	$order['_errors_cnt']++;
	            }
	        }
		foreach($order['items'] as $item){
	    	if(isset($item['_errors']) && is_array($item['_errors'])){
	        	foreach($item['_errors'] as $error){
					$orders['_orders_with_errors'][$ordernumber]['detail'][]=$error;
					$order['_errors_cnt']++;
					}
	            }
	      	}
	    if(isset($orders['_orders_with_errors'][$ordernumber])){
			$orders['_orders_with_errors'][$ordernumber]['name']=$order['shiptoname'];
            }
		}
    ksort($orders);
    return $orders;
	}
//---------------------------
function integracoreParseValue($str='',$val=''){
  //process logic
  //echo "integracoreParseValue({$str},{$val})<br>\n";
  if(preg_match('/^\&(.+)/',$str,$vmatch)){
    $evalstr='<? return ' . str_replace('%val%',$val,$vmatch[1]) . ';?>';
    $val = evalPHP($evalstr);
    //echo "integracoreParseValue EvalStr1:{$evalstr} === {$val}<br>\n";
    return $val;
      }
    else if(preg_match('/^\<\?(.+?)\?\>$/',$str)){
    $evalstr=str_replace('%val%',$val,$str);
    $val = evalPHP($evalstr);
    //echo "integracoreParseValue EvalStr1:{$evalstr} === {$val}<br>\n";
    return $val;
      }
    return $str;
  }
?>