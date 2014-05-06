<?php
/*
	National Products Fulfillment API wrappers
*/
$progpath=dirname(__FILE__);
//load the nusoap library
require_once("{$progpath}/npf/lib/nusoap.php");
error_reporting(E_ALL & ~E_NOTICE);
//-----------------------
function npfTestOrderStatus(){
	$auth=array(
		'username'	=> "APINPFON",
		'password'	=> "APINPFON212",
		'clientcode'=> "APINPFON"
	);
	$salesOrderNumbers=array(
		'INT1235',
		'INT1234',
		'TST1236'
	);
	$result=npfOrderStatus($auth,$salesOrderNumbers);
	echo printValue($result);
	return $result;
}
//-----------------------
function npfTestServeOrder(){
	$auth=array(
		'username'	=> "APINPFON",
		'password'	=> "APINPFON212",
		'clientcode'=> "APINPFON"
	);
	$orders=array(
		'custid'	=> "TESTID",
		'orders'	=> array(
			array(
				'site_id'		=> 1,
				'ordernumber'	=> "TST1236",
				'custid'		=> "TESTID",
	            'createdate' 	=> "20120907",
	            'requiredshipdate' => "20120907",
	            'orderfilled' 	=> "O",
	            'carrier_service_code' => "USS 01",
	            'shipmethod'	=> "AP",
	            'carrier' 		=> "FDX",
	            'billtoname' 	=> "John Doe Company",
	            'billtocontact' => "John Doe",
	            'billtoaddress1'=> "35 Warrawee Street",
	            'billtocity' 	=> "Sapphire Beach",
	            'billtostate' 	=> "NSW",
	            'billtozipcode' => "2450",
	            'billtocountry' => "Australia",
	            'billtotelephone' => "2343234343",
	            'shiptoname' 	=> "John Doe Company",
	            'shiptocontact' => "John Doe",
	            'shiptoaddress1'=> "35 Warrawee Street",
	            'shiptocity' 	=> "Sapphire Beach",
	            'shiptostate' 	=> "NSW",
	            'shiptozipcode' => "2450",
	            'shiptocountry' => "Australia",
	            'shiptotelephone' => "2343234343",
	            'freightcode' 	=> 3,
	            'allow_short_ship' => "N",
	            'packlist_required' => "T",
	            'drop_ship' 	=> "R",
	            'customer_email_to' => "johndoe@hotmail.com",
	            'residential_flag' => "T",
	            'value'				=> 10.00,
	            'description'		=> "personal accessories",
	            'items' => array(
					array(
						'site_id'		=> 1,
						'ordernumber'	=> "INT1235",
		                'linenumber' 	=> 1,
		                'itemid' 		=> "001215",
		                'description'	=> "Nude By Night Foundation Medium 50ml",
		                'qtyordered' 	=> 3,
		                'retail_price'	=> 5.54
					),
					array(
						'site_id'		=> 1,
						'ordernumber'	=> "INT1235",
		                'linenumber' 	=> 2,
		                'itemid' 		=> "01-250",
		                'description'	=> "Goggles 7550",
		                'qtyordered' 	=> 1,
		                'retail_price'	=> 8.44
					)
				)
			)
		)
	);
	$test=npfServeOrder($auth,$orders);
	echo printValue($auth).printValue($test);
}
//-----------------------
function npfAuthXML($auth){
	return <<<ENDOFAUTH
	<Login>
		<Username>{$auth['username']}</Username>
    	<Password>{$auth['password']}</Password>
		<ClientCode>{$auth['clientcode']}</ClientCode>
	</Login>
ENDOFAUTH;
}
//-----------------------
function npfServeOrder($auth,$orders,$debug=0){
	$url='https://npfulfilmentapi.com/serverorder.php?wsdl';
	//for now NPF can only accept one order per request
	$responses=array();
	foreach($orders['orders'] as $order){
		$ordernumber=$order['ordernumber'];
  		$xml=xmlHeader();
		$xml .= '<OrderList>'."\n";
		$xml .= '	'.trim(npfAuthXML($auth))."\n";
		//default billto to shipto if not set
		if(strtoupper($order['shiptocountry'])=='AU'){
        	$order['shiptocountry']="Australia";
		}
		elseif(strtoupper($order['shiptocountry'])=='NZ'){
        	$order['shiptocountry']="New Zealand";
		}
		$billtofields=array('contact','name','address1','address2','city','state','zipcode','country','telephone','email');
		foreach($billtofields as $field){
			if(!isset($order["billto{$field}"])){$order["billto{$field}"]=$order["shipto{$field}"];}
		}
		if(!strlen($order['billtotelephone'])){$order['billtotelephone']='na';}
		list($billtofirstname,$billtolastname)=preg_split('/\ /',$order['billtocontact'],2);
		list($shiptofirstname,$shiptolastname)=preg_split('/\ /',$order['shiptocontact'],2);
		$xml .= '	<Order>'."\n";
		$xml .= '		<SalesOrderNo>'.$ordernumber.'</SalesOrderNo>'."\n";
		$xml .= '		<OrderDate>01-Jun-2012</OrderDate>'."\n";
	    $xml .= '		<CustomerNo>C001</CustomerNo>'."\n";
	    $xml .= '		<BillingAddress>'."\n";
	    $xml .= '			<FirstName>'.xmlEncode($billtofirstname).'</FirstName>'."\n";
	    $xml .= '			<LastName>'.xmlEncode($billtolastname).'</LastName>'."\n";
	    $xml .= '			<CompanyName>'.xmlEncode($order['billtoname']).'</CompanyName>'."\n";
	    $xml .= '			<Address1>'.xmlEncode($order['billtoaddress1']).'</Address1>'."\n";
	    $xml .= '			<Address2>'.xmlEncode($order['billtoaddress1']).'</Address2>'."\n";
	    $xml .= '			<City>'.xmlEncode($order['billtocity']).'</City>'."\n";
	    $xml .= '			<State>'.xmlEncode($order['billtostate']).'</State>'."\n";
	    $xml .= '			<PostCode>'.$order['billtozipcode'].'</PostCode>'."\n";
	    $xml .= '			<Country>'.$order['billtocountry'].'</Country>'."\n";
	    $xml .= '			<Phone>'.$order['billtotelephone'].'</Phone>'."\n";
	    $xml .= '			<Email>'.$order['billtoemail'].'</Email>'."\n";
	    $xml .= '		</BillingAddress>'."\n";
	    $xml .= '		<ShippingAddress>'."\n";
	    $xml .= '			<FirstName>'.xmlEncode($shiptofirstname).'</FirstName>'."\n";
	    $xml .= '			<LastName>'.xmlEncode($shiptolastname).'</LastName>'."\n";
	    $xml .= '			<CompanyName>'.xmlEncode($order['shiptoname']).'</CompanyName>'."\n";
	    $xml .= '			<Address1>'.xmlEncode($order['shiptoaddress1']).'</Address1>'."\n";
	    $xml .= '			<Address2>'.xmlEncode($order['shiptoaddress2']).'</Address2>'."\n";
	    $xml .= '			<City>'.xmlEncode($order['shiptocity']).'</City>'."\n";
	    $xml .= '			<State>'.xmlEncode($order['shiptostate']).'</State>'."\n";
	    $xml .= '			<PostCode>'.$order['shiptozipcode'].'</PostCode>'."\n";
	    $xml .= '			<Country>'.$order['shiptocountry'].'</Country>'."\n";
	    $xml .= '		</ShippingAddress>'."\n";
	    $xml .= '		<DespatchDetails>'."\n";
	    $xml .= '			<DispatchMethod>'.$order['shipmethod'].'</DispatchMethod>'."\n";
	    $xml .= '			<GiftWrappingRequired>'.$order['giftwrap'].'</GiftWrappingRequired>'."\n";
	    $message=xmlEncodeCDATA($order['customer_message']);
	    $message=str_replace('&amp;#039;',"'",$message);
	    //customer message is limited to 200 chars
	    $message=substr($message,0,200);
	    $xml .= '			<CustomerMessage>'.$message.'</CustomerMessage>'."\n";
	    //order value
	    if(!isset($order['value'])){
			$order['value']=0;
        	foreach($order['items'] as $item){
				if(!isset($item['quantity'])){$item['quantity']=$item['qtyordered'];}
				if(!isset($item['price'])){$item['price']=$item['retail_price'];}
				$subtotal=$item['quantity']*$item['price'];
				$order['value']+=$subtotal;
			}
			$order['value']=round($order['value'],2);
		}
	    $xml .= '			<ArticleValue>'.$order['value'].'</ArticleValue>'."\n";
	    $xml .= '			<ArticleDescription>'.xmlEncodeCDATA($order['description']).'</ArticleDescription>'."\n";
	    $xml .= '			<WebSiteCode>'.$order['url'].'</WebSiteCode>'."\n";
	    $xml .= '		</DespatchDetails>'."\n";
	    $xml .= '		<Products>'."\n";
	    $subtotal1=0;
	    foreach($order['items'] as $item){
			if(!isset($item['quantity'])){$item['quantity']=$item['qtyordered'];}
			if(!isset($item['price'])){$item['price']=$item['retail_price'];}
			if(!isset($item['gst'])){$item['gst']=round(($item['price']*.1),2);}
			if(!isset($item['gstprice'])){$item['gstprice']=$item['price']+$item['gst'];}
			if(!isset($item['amount'])){$item['amount']=$item['gstprice']*$item['quantity'];}
			if(!isset($item['upc'])){$item['upc']=$item['itemid'];}
			if(!strlen($item['price'])){$item['price']=0.00;}
			$subtotal1+=$item['amount'];
		    $xml .= '			<Product>'."\n";
		    $xml .= '				<Code>'.$item['itemid'].'</Code>'."\n";
		    $xml .= '				<Description>'.xmlEncodeCDATA($item['description']).'</Description>'."\n";
		    $xml .= '				<BarCode>'.$item['upc'].'</BarCode>'."\n";
		    $xml .= '				<Quantity>'.$item['quantity'].'</Quantity>'."\n";
		    $xml .= '				<SalesPrice>'.$item['price'].'</SalesPrice>'."\n";
		    $xml .= '				<GST>'.$item['gst'].'</GST>'."\n";
		    $xml .= '				<SalesPricesIncludesGST>'.$item['gstprice'].'</SalesPricesIncludesGST>'."\n";
		    $amount=$item['quantity']*$item['price'];
		    $xml .= '				<Amount>'.$item['amount'].'</Amount>'."\n";
		    $xml .= '			</Product>'."\n";
		}
		if(!isset($order['subtotal1'])){$order['subtotal1']=$subtotal1;}
		if(!isset($order['ph_cost'])){$order['ph_cost']=0.00;}
		if(!isset($order['ph_gst'])){$order['ph_gst']=round(($order['ph_cost']*.1),2);}
		if(!isset($order['ph_gstcost'])){$order['ph_gstcost']=$order['ph_cost']+$order['ph_gst'];}
		if(!isset($order['subtotal2'])){$order['subtotal2']=$order['subtotal1']+$order['ph_gstcost'];}
		if(!isset($order['discount_pcnt'])){$order['discount_pcnt']=0;}
		if($order['discount_pcnt'] > 1){$order['discount_pcnt']=round($order['discount_pcnt']/100,2);}
		if(!isset($order['discount_amt'])){round(($order['subtotal2']*$order['discount_pcnt']),2);}
		if(!isset($order['total'])){$order['total']=$order['subtotal2']-$order['discount_amt'];}
	    $xml .= '		</Products>'."\n";
	    $xml .= '		<Totals>'."\n";
	    $xml .= '			<SubTotal1>'.$order['subtotal1'].'</SubTotal1>'."\n";
	    $xml .= '			<P_H_Cost>'.$order['ph_cost'].'</P_H_Cost>'."\n";
	    $xml .= '			<P_H_GST>'.$order['ph_gst'].'</P_H_GST>'."\n";
	    $xml .= '			<P_H_IncludesGST>'.$order['ph_gstcost'].'</P_H_IncludesGST>'."\n";
	    $xml .= '			<SubTotal2>'.$order['subtotal2'].'</SubTotal2>'."\n";
	    $xml .= '			<DiscountPercentage>'.$order['discount_pcnt'].'</DiscountPercentage>'."\n";
	    if(!isset($order['discount_amt'])){$order['discount_amt']=0.00;}
	    $xml .= '			<DiscountAmount>'.$order['discount_amt'].'</DiscountAmount>'."\n";
	    $xml .= '			<Total>'.$order['total'].'</Total>'."\n";
	    $xml .= '		</Totals>'."\n";
	  	$xml .= '	</Order>'."\n";
    	$xml .= '</OrderList>'."\n";
    	$responses['raw_request'][$ordernumber]=$xml;
	  	if($debug==1){$responses[$ordernumber]=$xml;}
	  	else{
			$response=npfPostXML($url,'serverorder',$xml);
			if(stringBeginsWith($response,"ERROR")){
				$responses[$ordernumber] = $response;
			}
			else{
				$responses[$ordernumber]=xmL2Array($response);
			}
			$responses['raw'][$ordernumber]=$response;
		}
	}
	return $responses;
}
//---------------------
function npfOrderStatus($auth,$salesOrderNumbers=array()){
	if(!count($salesOrderNumbers)){return null;}
	$url='https://npfulfilmentapi.com/npforderstatus.php?wsdl';
	$xml=xmlHeader();
	$xml .= '<OrderList>'."\n";
	$xml .= '	'.trim(npfAuthXML($auth))."\n";
	$xml .= '	<Order>'."\n";
	$string=implode(",",$salesOrderNumbers);
    $xml .= '		<SalesOrderNo>'.$string.'</SalesOrderNo>'."\n";
  	$xml .= '	</Order>'."\n";
	$xml .= '</OrderList>'."\n";
/*	$method='importorderstatus';
 	$param = array('orders' => $xml);
	$client = new nusoap_client($url);
	$response = $client->call($method,$param);
	echo printValue($param).printValue($client).printValue($response) . $xml;exit; */
	$response=npfPostXML($url,'importorderstatus',$xml);
	if(stringBeginsWith($response,"ERROR")){
		return $response;
	}
	//hack to fix an ampersand to make it valid XML
	$rtn=array();
	$rtn['raw_request']=$xml;
	$rtn['raw_response']=$response;
	$response=str_replace('&type=','&amp;type=',$response);
	$rtn['orders']=xmL2Array($response);
	if(isset($rtn['orders']['OrderList']['Order'])){
		$orders=$rtn['orders']['OrderList']['Order'];
		if(isset($orders['ClientCode'])){$orders=array($orders);}
		$rtn['orders']=$orders;
	}
	//echo printValue($arr);
	return $rtn;
}
//---------------------
function npfStockOnHand($auth,$productcodes){
	$url='https://npfulfilmentapi.com/npfsoh.php?wsdl';
	//this API does not like large sets of productcodes - so break into groups of 150
	$groups=array_chunk($productcodes,150);
	$result=array('stock'=>array());
	foreach($groups as $group){
		$xml=xmlHeader();
		$xml .= '<ProductList>'."\n";
		$xml .= '	'.trim(npfAuthXML($auth))."\n";
		$xml .= '	<Product>'."\n";
		$string=implode(",",$group);
	    $xml .= '		<ProductCode>'.$string.'</ProductCode>'."\n";
	  	$xml .= '	</Product>'."\n";
		$xml .= '</ProductList>'."\n";
		//echo printValue($params) . $xml;exit;
		$response=npfPostXML($url,'importsoh',$xml);
		$result['requests'][]=$xml;
		$result['responses'][]=$response;
		if(stringContains($resonse,'no contents') || stringBeginsWith($response,"ERROR")){
			continue;
		}
		//echo $xml.printValue($response);
		$arr=xmL2Array($response);
		$result['responses_array'][]=$arr;
		if(isset($arr['ProductList']['Product']['ProductCode'])){
			$code=$arr['ProductList']['Product']['ProductCode'];
        	$result['stock'][$code]=$arr['ProductList']['Product']['StockonHand'];
		}
		elseif(isset($arr['ProductList']['Product'][0])){
			foreach($arr['ProductList']['Product'] as $product){
				$code=$product['ProductCode'];
	        	$result['stock'][$code]=$product['StockonHand'];
			}
		}
	}
	return $result;
}
//---------------------------
function npfPostXML($url,$method,$xml){
	$param = array('orders' => $xml);
	$client = new nusoap_client($url);
	$response = $client->call($method,$param);
	if($client->fault){
  		return "ERROR ".$client->faultcode.": ". $client->faultstring;
	}
	else{
		return $response;
	}
}
?>