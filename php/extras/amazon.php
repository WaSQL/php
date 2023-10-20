<?php
$progpath=dirname(__FILE__);
//load the amazon common library
//require_once("$progpath/amazon/AmazonMerchantAtSoapClient.php");
/*
	References: http://www.amazonsellercommunity.com/forums/thread.jspa?threadID=173132&tstart=1
	https://github.com/daniel-zahariev/php-aws-ses
	https://console.aws.amazon.com/ses/home?region=us-east-1#verified-senders-email:
	http://docs.aws.amazon.com/ses/latest/DeveloperGuide/regions.html

	$afile="/path/to/myfile.jpg";
	$ok=amazonUploadFileS3(array(
		'file'=>$afile,
		'folder'=>'pub'
	));
*/
//confirm/add aws settings to _config table
// $crecs=getDBRecords(array(
// 	'-table'=>'_config',
// 	'-where'=>"name like aws_%",
// 	'-index'=>'name'
// ));
// if(!isset($crecs['aws_accesskey'])){
// 	$ok=addDBRecord(array('-table'=>'_config','name'=>'aws_accesskey','category'=>'extras'));
// }
// if(!isset($crecs['aws_secretkey'])){
// 	$ok=addDBRecord(array('-table'=>'_config','name'=>'aws_secretkey','category'=>'extras'));
// }
// if(!isset($crecs['aws_acl'])){
// 	$ok=addDBRecord(array('-table'=>'_config','name'=>'aws_acl','category'=>'extras'));
// }
// if(!isset($crecs['aws_bucket'])){
// 	$ok=addDBRecord(array('-table'=>'_config','name'=>'aws_bucket','category'=>'extras'));
// }
// if(!isset($crecs['aws_folder'])){
// 	$ok=addDBRecord(array('-table'=>'_config','name'=>'aws_folder','category'=>'extras'));
// }
// if(!isset($crecs['aws_region'])){
// 	$ok=addDBRecord(array('-table'=>'_config','name'=>'aws_region','category'=>'extras'));
// }

function amazonConvertCheckTable(){
	if(!isDBTable('aws_convert_files')){
		$fields=array(
			'processed'=>'tinyint(1) NOT NULL Default 0',
			'record_id'=>'int NOT NULL',
			'process_id'=>'int NOT NULL Default 0',
			'status'=>'varchar(25) NULL',
			'results'=>'varchar(3000)',
			'tablename'=>'varchar(100) NOT NULL',
			'fieldname'=>'varchar(50) NOT NULL'
		);
		$ok = createDBTable('aws_convert_files',$fields,'InnoDB');
		$ok=addDBIndex(array('-table'=>'aws_convert_files','-fields'=>"processed"));
		$ok=addDBIndex(array('-table'=>'aws_convert_files','-unique'=>1,'-fields'=>"tablename,fieldname,record_id"));
	}
}
function amazonConvertFile($tablename,$fieldname,$record_id){
	//make sure aws_convert_files table exists
	$ok=amazonConvertCheckTable();
	//add the record
	$ok=addDBRecord(array(
		'-table'=>'aws_convert_files',
		'tablename'=>$tablename,
		'fieldname'=>$fieldname,
		'record_id'=>$record_id,
		'processed'=>0,
		'process_id'=>0,
		'status'=>'queued',
		'-upsert'=>'processed,process_id,status'
	));
	return $ok;
}
function amazonConvertFilesS3($params=array()){
	global $CONFIG;
	global $databaseCache;
	//require -accesskey
	if(!isset($params['-accesskey']) && isset($CONFIG['aws_accesskey'])){
		$params['-accesskey']=$CONFIG['aws_accesskey'];
	}
	if(!isset($params['-accesskey'])){
		return "amazonConvertFilesS3 Error: missing -accesskey";
	}
	//require -secretkey
	if(!isset($params['-secretkey']) && isset($CONFIG['aws_secretkey'])){
		$params['-secretkey']=$CONFIG['aws_secretkey'];
	}
	if(!isset($params['-secretkey'])){
		return "amazonConvertFilesS3 Error: missing -secretkey";
	}
	//require bucket
	if(!isset($params['bucket']) && isset($CONFIG['aws_bucket'])){
		$params['bucket']=$CONFIG['aws_bucket'];
	}
	if(!isset($params['bucket'])){
		return "amazonConvertFilesS3 Error: missing bucket param";
	}
	//requre region - us-west-2, us-east-1, etc
	if(!isset($params['region']) && isset($CONFIG['aws_region'])){
		$params['region']=$CONFIG['aws_region'];
	}
	if(!isset($params['region'])){
		return "amazonConvertFilesS3 Error: missing region param";
	}
	//acl - public, public-read, private
	if(!isset($params['acl']) && isset($CONFIG['aws_acl'])){
		$params['acl']=$CONFIG['aws_acl'];
	}
	if(!isset($params['acl'])){
		$params['acl']='x-amz-acl:public-read';
	}
	//make sure aws_convert_files table exists
	$ok=amazonConvertCheckTable();
	//get a record that has not been processed
	$rec=getDBRecord(array(
		'-table'=>'aws_convert_files',
		'processed'=>0
	));
	if(!isset($rec['_id'])){
		return "amazonConvertFilesS3: nothing to do";
	}
	$process_id=getmypid();
	//try to claim it my assiging our process_id to it
	$ok=editDBRecord(array(
		'-table'=>'aws_convert_files',
		'-where'=>"_id={$rec['_id']} and process_id=0",
		'process_id'=>$process_id
	));
	//make sure we claimed it
	$databaseCache=array();
	$rec=getDBRecordById('aws_convert_files',$rec['_id']);
	if(!isset($rec['_id'])){return 0;}
	if($rec['process_id'] != $process_id){return "failed to claim record {$rec['_id']}";}
	//if we are here we have claimed the record to process
	$ok=editDBRecordById('aws_convert_files',$rec['_id'],array('processed'=>1));
	//get the record from the table specified
	$crec=getDBRecordById($rec['tablename'],$rec['record_id'],"_id,{$rec['fieldname']}");
	if(!isset($crec['_id'])){
		$ok=editDBRecordById('aws_convert_files',$rec['_id'],array(
			'status'=>'failed',
			'results'=>'No such record'
		));
		return $rec['_id'];
	}
	$field=$rec['fieldname'];
	//has this record already been converted?
	if(stringContains($crec[$field],'amazonaws')){
		$ok=editDBRecordById('aws_convert_files',$rec['_id'],array(
			'status'=>'failed',
			'results'=>'already on amazon'
		));
		return $rec['_id'];
	}
	//has this record already been converted?
	if(stringBeginsWith($crec[$field],'http')){
		$ok=editDBRecordById('aws_convert_files',$rec['_id'],array(
			'status'=>'failed',
			'results'=>'already hosted elsewhere'
		));
		return $rec['_id'];
	}
	//get absolute path to file and confirm it exists
	$afile=$_SERVER['DOCUMENT_ROOT'].$crec[$field];
	if(!is_file($afile)){
		$ok=editDBRecordById('aws_convert_files',$rec['_id'],array(
			'status'=>'failed',
			'results'=>"No such file: {$afile}"
		));
		return $rec['_id'];
	}
	$ext=getFileExtension($afile);
	$fname=getFileName($afile);
	$uid=$crec['_cuser'];
	$editrec=array();
	switch(strtolower($ext)){
		case 'heic':
			//iphone image file format - apt install libheif1 libheif-examples
			$nfile=preg_replace('/\.'.$ext.'$/i','',$nfile);
			$nfile=preg_replace('/[^a-z0-9\.\/\_\-]+/i','',$afile).'_'.time()."_u{$crec['_cuser']}r{$crec['_id']}.png";
			$cmd="heif-convert \"{$afile}\"  \"{$nfile}\"";
			if(is_file($nfile)){unlink($nfile);}
			$results=cmdResults($cmd);
			$editrec['results']=json_encode($results);
		break;
		case 'mov':
		case 'avi':
			$nfile=preg_replace('/\.'.$ext.'$/i','',$afile);
			$nfile=preg_replace('/[^a-z0-9\.\/\_\-]+/i','',$nfile).'_'.time()."_u{$crec['_cuser']}r{$crec['_id']}.mp4";
			$cmd="/usr/bin/ffmpeg -y -hide_banner -i \"{$afile}\" -vcodec copy -acodec copy  \"{$nfile}\"";
			if(is_file($nfile)){unlink($nfile);}
			$results=cmdResults($cmd);
			$editrec['results']=json_encode($results);
		break;
		default:
			//just upload the rest to AWS
			$nfile=preg_replace('/\.'.$ext.'$/i','',$afile);
			$nfile=preg_replace('/[^a-z0-9\.\:\/\_\-]+/i','',$nfile).'_'.time()."_u{$crec['_cuser']}r{$crec['_id']}.{$ext}";
			$ok=copyFile($afile,$nfile);
			if(!is_file($nfile)){
				$ok=copy($afile,$nfile);
			}
			if(!is_file($nfile)){
				$cmd="copy {$afile} {$nfile}";
				$ok=cmdResults($cmd);
			}
			if(!is_file($nfile)){
				$cmd="cp {$afile} {$nfile}";
				$ok=cmdResults($cmd);
			}
			$results=array('cmd'=>'copyFile','from'=>$afile,'to'=>$nfile,'result'=>$ok);
			$editrec['results']=json_encode($results,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
		break;
	}
	if(is_file($nfile) && filesize($nfile) > 0){
		//upload to amazon
		$opts=array(
			'file'=>$nfile
		);
		if(isset($CONFIG['aws_folder'])){
			$folder=$CONFIG['aws_folder'];
			//replace any field values in folder
			foreach($crec as $k=>$v){
				$folder=str_replace("%{$k}%",$v,$folder);
			}
			$opts['folder']=$folder;
		}
		//upload to Amazon S3
		$upload=amazonUploadFileS3($opts);
		if(isNum($upload)){
			//success
			$fname=getFileName($opts['file']);
			$url="https://{$CONFIG['aws_bucket']}.s3-{$CONFIG['aws_region']}.amazonaws.com/";
			if(isset($opts['folder'])){
				$url .= "{$opts['folder']}/";
			}
			$url.=$fname;
			//replace the field with the new url
			$ok=editDBRecordById($rec['tablename'],$crec['_id'],array(
				$rec['fieldname']=>$url
			));
			//remove the local files
			unlink($opts['file']);
			if(is_file($afile)){unlink($afile);}
			//set status
			$editrec['status']='success';
		}
		else{
			//failed to upload
			$editrec['status']='failure: '.$upload.printValue($opts);
		}
	}
	else{
		//failed to create file
		$editrec['status']='failure';
	}
	if(count($editrec)){
		$ok=editDBRecordById('aws_convert_files',$rec['_id'],$editrec);
	}
	return $rec['_id'];
}
function amazonUploadFileS3($params=array()){
	global $CONFIG;
	$rtn=array();
	$rtn['input_params']=$params;
	//require -accesskey
	if(!isset($params['-accesskey']) && isset($CONFIG['aws_accesskey'])){
		$params['-accesskey']=$CONFIG['aws_accesskey'];
	}
	if(!isset($params['-accesskey'])){
		return "amazonUploadFileS3 Error: missing -accesskey";
	}
	//require -secretkey
	if(!isset($params['-secretkey']) && isset($CONFIG['aws_secretkey'])){
		$params['-secretkey']=$CONFIG['aws_secretkey'];
	}
	if(!isset($params['-secretkey'])){
		return "amazonUploadFileS3 Error: missing -secretkey";
	}
	//require bucket
	if(!isset($params['bucket']) && isset($CONFIG['aws_bucket'])){
		$params['bucket']=$CONFIG['aws_bucket'];
	}
	if(!isset($params['bucket'])){
		return "amazonUploadFileS3 Error: missing bucket param";
	}
	//requre region - us-west-2, us-east-1, etc
	if(!isset($params['region']) && isset($CONFIG['aws_region'])){
		$params['region']=$CONFIG['aws_region'];
	}
	if(!isset($params['region'])){
		return "amazonUploadFileS3 Error: missing region param";
	}
	//acl - public, public-read, private
	if(!isset($params['acl']) && isset($CONFIG['aws_acl'])){
		$params['acl']=$CONFIG['aws_acl'];
	}
	if(!isset($params['acl'])){
		$params['acl']='x-amz-acl:public-read';
	}
	//require file
	if(!isset($params['file'])){
		return "amazonUploadFileS3 Error: missing file param";
	}
	if(!is_file($params['file'])){
		return "amazonUploadFileS3 Error: file does not exist - {$params['file']}";
	}
	if(!isset($params['folder'])){
		$params['folder']='';
	}
	elseif(!stringEndsWith($params['folder'],'/')){
		$params['folder']="{$params['folder']}/";
	}
	// USER OPTIONS
	// Replace these values with ones appropriate to you.
	$accessKeyId = $params['-accesskey'];
	$secretKey = $params['-secretkey'];
	$bucket = $params['bucket'];
	$region = $params['region'];
	$acl = $params['acl'];
	$filePath = getFilePath($params['file']);
	$fileName = getFileName($params['file']);
	$fileType = getFileMimeType($params['file']);

	// VARIABLES
	// These are used throughout the request.
	$longDate = gmdate('Ymd\THis\Z');
	$shortDate = gmdate('Ymd');
	$credential = $accessKeyId.'/'.$shortDate.'/'.$region.'/s3/aws4_request';

	// POST POLICY
	// Amazon requires a base64-encoded POST policy written in JSON.
	// This tells Amazon what is acceptable for this request. For
	// simplicity, we set the expiration date to always be 24H in 
	// the future. The two "starts-with" fields are used to restrict
	// the content of "key" and "Content-Type", which are specified
	// later in the POST fields. Again for simplicity, we use blank
	// values ('') to not put any restrictions on those two fields.
	$policy = base64_encode(json_encode([
	    'expiration' => gmdate('Y-m-d\TH:i:s\Z', time() + 86400),
	    'conditions' => [
	        //['acl' => $acl],
	        ['bucket' => $bucket],
	        ['starts-with', '$Content-Type', ''],
	        ['starts-with', '$key', ''],
	        ['x-amz-algorithm' => 'AWS4-HMAC-SHA256'],
	        ['x-amz-credential' => $credential],
	        ['x-amz-date' => $longDate]
	    ]
	]));

	// SIGNATURE
	// A base64-encoded HMAC hashed signature with your secret key.
	// This is used so Amazon can verify your request, and will be
	// passed along in a POST field later.
	$signingKey = hash_hmac('sha256', $shortDate, 'AWS4' . $secretKey, true);
	$signingKey = hash_hmac('sha256', $region, $signingKey, true);
	$signingKey = hash_hmac('sha256', 's3', $signingKey, true);
	$signingKey = hash_hmac('sha256', 'aws4_request', $signingKey, true);
	$signature = hash_hmac('sha256', $policy, $signingKey);

	// CURL
	// The cURL request. Passes in the full URL to your Amazon bucket.
	// Sets RETURNTRANSFER and HEADER to true to see the full response from
	// Amazon, including body and head. Sets POST fields for cURL.
	// Then executes the cURL request.
	$url='https://' . $bucket . '.s3.' . $region . '.amazonaws.com';
	//echo $url;
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	//turn off ssl to test
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	// curl_setopt($ch, CURLOPT_POST, 0);
	// curl_setopt($ch, CURLOPT_CUSTOMREQUEST,'PUT');
	$postfields=array(
		'Content-Type' =>  $fileType,
	    //'acl' => $acl,
	    'key' => "{$params['folder']}{$fileName}",
	    'policy' =>  $policy,
	    'x-amz-algorithm' => 'AWS4-HMAC-SHA256',
	    'x-amz-credential' => $credential,
	    'x-amz-date' => $longDate,
	    'x-amz-signature' => $signature,
	    'file' => file_get_contents($params['file'])
	);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	$response = curl_exec($ch);
	$rtn['response']=$response;
	$rtn['headers_out']=preg_split('/[\r\n]+/',curl_getinfo($ch,CURLINFO_HEADER_OUT));
	$rtn['curl_info']=curl_getinfo($ch);
	if ( curl_errno($ch) ) {
		$rtn['error_number'] = curl_errno($ch);
		$rtn['error'] = curl_error($ch);
		}
	else{
		//break it up into header and body
		$parts=preg_split('/\r\n\r\n/',trim($return),2);
		$rtn['header']=trim($parts[0]);
		$rtn['body']=trim($parts[1]);
		//check for redirect cases with two headers
		if(preg_match('/^HTTP\//is',$rtn['body'])){
			$parts=preg_split('/\r\n\r\n/',trim($rtn['body']),2);
			$rtn['header']=trim($parts[0]);
			$rtn['body']=trim($parts[1]);
		}
		//parse the header into an array
		$parts=preg_split('/[\r\n]+/',trim($rtn['header']));
		$headers=array();
		foreach($parts as $part){
			if(!preg_match('/\:/',$part)){continue;}
			list($key,$val)=preg_split('/\:/',trim($part),2);
			$key=strtolower(trim($key));
			$headers[$key][]=trim($val);
        }
        foreach($headers as $k=>$v){
        	if(count($v)==1){$rtn['headers'][$k]=$v[0];}
        	else{$rtn['headers'][$k]=$v;}
		}
    }
    curl_close($ch);
	$rtn['url']=$url;
	$rtn['postfields_file_length']=strlen($postfields['file']);
	unset($postfields['file']);
	$rtn['postfields']=$postfields;
	//echo printValue($rtn).printValue($postfields);
	// RESPONSE
	// If Amazon returns a response code of 204, the request was
	// successful and the file should be sitting in your Amazon S3
	// bucket. If a code other than 204 is returned, there will be an
	// XML-formatted error code in the body. For simplicity, we use
	// substr to extract the error code and output it.
	if ($rtn['curl_info']['http_code'] == 204) {
	    return 1;
	} 
	else {
	    return 'amazonUploadFileS3 Error: '.printValue($rtn);
	}
}
//---------- begin function amazonSendMail---------------------------------------
/**
* @describe sends an email using Amazon SES and returns null on success or the error string
* @param params array
*	-accesskey - amazon accesskey
*	-secretkey - amazon secretkey
*	to - the email address to send the email to.  For multiple recepients, separate emails by commas or semi-colons
*	from - the email address to send the email from
*	[cc] - optional email address to carbon copy the email to. For multiple recepients, separate emails by commas or semi-colons
*	[bcc] - optional email address to blind carbon copy the email to. For multiple recepients, separate emails by commas or semi-colons
*	[reply-to] - the email address to set as the reply-to address
*	subject - the subject of the email
*	message - the body of the email
*	[attach] - an array of files (full path required) to attach to the message
* @return str value
*	returns the error message or null on success
* @usage
*	$errmsg=amazonSendMail(array(
*		'to'		=> 'john@doe.com',
*		'from'		=> 'jane@doe.com',
*		'subject'	=> 'When will you be home?',
*		'message'	=> 'Here is the document you requested',
*		'attach'	=> array('/var/www/doument.doc')
*	));
*/
function amazonSendMail($params=array()){
	//require -accesskey
	if(!isset($params['-accesskey']) && isset($CONFIG['aws_accesskey'])){
		$params['-accesskey']=$CONFIG['aws_accesskey'];
	}
	if(!isset($params['-accesskey'])){
		return "amazonSendMail Error: missing -accesskey";
	}
	//require -secretkey
	if(!isset($params['-secretkey']) && isset($CONFIG['aws_secretkey'])){
		$params['-secretkey']=$CONFIG['aws_secretkey'];
	}
	if(!isset($params['-secretkey'])){
		return "amazonSendMail Error: missing -secretkey";
	}
	//require to, from, subject, message
	if(!isset($params['to'])){return "amazonSendMail Error: missing To";}
	if(!isset($params['from'])){return "amazonSendMail Error: missing From";}
	if(!isset($params['subject'])){return "amazonSendMail Error: missing Subject";}
	if(!isset($params['message'])){return "amazonSendMail Error: missing Message";}
	//load amazon SES SDK
	$progpath=dirname(__FILE__);
	require_once("$progpath/amazon/SimpleEmailService.php");
	require_once("$progpath/amazon/SimpleEmailServiceMessage.php");
	require_once("$progpath/amazon/SimpleEmailServiceRequest.php");
	//send
	$ses = new SimpleEmailService($params['-accesskey'], $params['-secretkey']);
	$ses->verifyPeer(0);
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
			$m->addAttachmentFromFile($name, $afile, $type);
		}
	}
	$m->setFrom($params['from']);
	$m->setSubject($params['subject']);
	$m->setMessageFromString(strip_tags($params['message']),$params['message']);
	$rtn = $ses->sendEmail($m);
	if(isset($rtn['MessageId'])){return 1;}
	return printValue(array('failed',$rtn));

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
				if(isNum((string)$val)){$crec[$key]=(float)$val;}
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
					if(isNum((string)$val)){$crec[$key]=(float)$val;}
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