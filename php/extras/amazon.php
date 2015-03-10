<?php
$progpath=dirname(__FILE__);
//load the amazon common library
//require_once("$progpath/amazon/AmazonMerchantAtSoapClient.php");
/*
	References: http://www.amazonsellercommunity.com/forums/thread.jspa?threadID=173132&tstart=1
*/


function amazonSendMail($params=array()){
	if(!isset($params['-accesskey'])){return "No Access Key";}
	if(!isset($params['-secretkey'])){return "No Secret Key";}
	if(!isset($params['to'])){return "No To";}
	if(!isset($params['from'])){return "No From";}
	if(!isset($params['subject'])){return "No Subject";}
	if(!isset($params['message'])){return "No Message";}
	$progpath=dirname(__FILE__);
	require_once("$progpath/amazon/SimpleEmailService.php");
	require_once("$progpath/amazon/SimpleEmailServiceMessage.php");
	require_once("$progpath/amazon/SimpleEmailServiceRequest.php");
	$ses = new SimpleEmailService($params['-accesskey'], $params['-secretkey']);
	$m = new SimpleEmailServiceMessage();
	if(!is_array($params['to'])){$params['to']=preg_split('/[\,\;]+/',$params['to']);}
	$m->addTo($params['to']);
	if(isset($params['cc'])){
    	if(!is_array($params['cc'])){$params['cc']=preg_split('/[\,\;]+/',$params['cc']);}
		$m->addCC($params['cc']);
	}
	if(isset($params['bcc'])){
    	if(!is_array($params['bcc'])){$params['bcc']=preg_split('/[\,\;]+/',$params['bcc']);}
		$m->addBCC($params['bcc']);
	}
	if(isset($params['attach'])){
		if(!is_array($params['attach'])){$params['attach']=preg_split('/[\,\;]+/',$params['attach']);}
		foreach($params['attach'] as $afile){
        	$name=getFileName($afile);
        	$type=getFileMimeType($afile);
			$path=getFilePath($afile);
			$m->addAttachmentFromFile($name, $path, $type);
		}
	}
	$m->setFrom($params['from']);
	$m->setSubject($params['subject']);
	$m->setMessageFromString($params['message']);
	$rtn = $ses->sendEmail($m);
	if(isset($rtn['MessageId'])){return 1;}
	return printValue($rtn);

}
//---------- begin function
/**
* @exclude  - this function is not ready and thus excluded from the manual
*/
function amazonGetAllPendingDocumentInfo($params=array()){
	//info: returns an array of documents waiting to be retrieved
	error_reporting(E_ALL & ~E_NOTICE);
	if(!isset($params['-username'])){return "No Login ID";}
	if(!isset($params['-password'])){return "No password";}
	if(!isset($params['-token'])){return "No Merchant Token";}
	if(!isset($params['-name'])){return "No Merchant Name";}
	/*Load libraries and wsdls*/
	global $progpath;
	date_default_timezone_set('America/Denver');
	$path_to_wsdl = "$progpath/amazon/merchant-interface-mime.wsdl";
	$cache=isset($params['-cache'])?$params['-cache']:0;
	ini_set("soap.wsdl_cache_enabled", "{$cache}");
	$clientOpts = array(
		'login' => $params['-username'],
		'password' => $params['-password'],
		);
	$rtn=array();
	// Soap Request: Refer to http://us3.php.net/manual/en/ref.soap.php for more information
	$amazonClient = new SoapClient($path_to_wsdl,$clientOpts);
	$merchant = array(
		'merchantIdentifier'=> $params['-token'],
		'merchantName' 		=> $params['-name'],
		);
	try{
		$rtn['documents']=array();
		$result = $amazonClient->getAllPendingDocumentInfo($merchant,"_GET_FLAT_FILE_ORDERS_DATA_") ;
		foreach($result->MerchantDocumentInfo as $item){
			$crec=array();
			foreach($item as $citem=>$val){
				$key=(string)$citem;
				if(isNum((string)$val)){$crec[$key]=(real)$val;}
				else{$crec[$key]=removeCdata((string)$val);}
				}
			array_push($rtn['documents'],$crec);
			}
		if(!count($rtn['documents'])){
			$result = $amazonClient->getAllPendingDocumentInfo($merchant,"_GET_ORDERS_DATA_") ;
			foreach($result->MerchantDocumentInfo as $item){
				$crec=array();
				foreach($item as $citem=>$val){
					$key=(string)$citem;
					if(isNum((string)$val)){$crec[$key]=(real)$val;}
					else{$crec[$key]=removeCdata((string)$val);}
					}
				array_push($rtn['documents'],$crec);
				}
			array_reverse($rtn['documents']);
			}
		}
	catch (SOAPFault $fault) {
   		$elements = imap_mime_header_decode($amazonClient->__getLastResponse());
		for ($i=0; $i<count($elements); $i++) {
    		echo $elements[0]->text;
   			}
		$rtn['error'] = "SOAP FAILED<br>\n";
		$rtn['fault'] = printValue($fault); // here ex returns us that there "looks like we got no XML document"
		$rtn['response'] = $amazonClient->__getLastResponse();
		}
	$rtn['request'] = $amazonClient->__getLastRequest();

	return $rtn;
	}
//---------- begin function
/**
* @exclude  - this function is not ready and thus excluded from the manual
*/
function amazonGetDocument($docid,$params=array()){
	//info: returns an array of documents waiting to be retrieved
	error_reporting(E_ALL & ~E_NOTICE);
	if(!isset($params['-username'])){return "No Login ID";}
	if(!isset($params['-password'])){return "No password";}
	if(!isset($params['-token'])){return "No Merchant Token";}
	if(!isset($params['-name'])){return "No Merchant Name";}
	if(is_array($docid) && isset($docid['documentID'])){$docid=(string)$docid['documentID'];}
	else{$docid=(string)$docid;}
	if(!isNum($docid)){return "No DocumentID" . printValue($docid);}
	/*Load libraries and wsdls*/
	global $progpath;
	date_default_timezone_set('America/Denver');
	$path_to_wsdl = "$progpath/amazon/merchant-interface-mime.wsdl";
	$cache=isset($params['-cache'])?$params['-cache']:0;
	ini_set("soap.wsdl_cache_enabled", "{$cache}");
	$clientOpts = array(
		'login' => $params['-username'],
		'password' => $params['-password'],
		);
	$rtn=array();
	// Soap Request: Refer to http://us3.php.net/manual/en/ref.soap.php for more information
	$amazonClient = new SoapClient($path_to_wsdl,$clientOpts);
	$merchant = array(
		'merchantIdentifier'=> $params['-token'],
		'merchantName' 		=> $params['-name'],
		);
	try{
		$rtn['docid']=$docid;
		$rtn['result'] = $amazonClient->getDocument($merchant,array($docid)) ;
		return $rtn;
		}
	catch (SOAPFault $fault) {
   		$elements = imap_mime_header_decode($amazonClient->__getLastResponse());
		for ($i=0; $i<count($elements); $i++) {
    		echo $elements[0]->text;
   			}
		$rtn['error'] = "SOAP FAILED<br>\n";
		$rtn['fault'] = printValue($fault); // here ex returns us that there "looks like we got no XML document"
		$rtn['response'] = $amazonClient->__getLastResponse();
		}
	$rtn['request'] = $amazonClient->__getLastRequest();

	return $rtn;
	}
?>