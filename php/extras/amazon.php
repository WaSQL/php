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
//---------- begin function amazonUploadFileS3---------------------------------------
/**
* @describe Uploads a file to Amazon S3 using AWS Signature Version 4
* @param array $params Parameters array
*   Required parameters:
*   - file: Full path to the local file to upload
*   
*   Optional parameters (will use global CONFIG values if not provided):
*   - -accesskey: AWS Access Key ID
*   - -secretkey: AWS Secret Access Key  
*   - bucket: S3 bucket name
*   - region: AWS region (e.g., 'us-east-1', 'us-west-2')
*   - acl: Access control list (default: 'x-amz-acl:public-read')
*   - folder: S3 folder/prefix to upload to (default: root)
* @return mixed Returns 1 on success, or error message string on failure
* @usage
*   // Upload a file to S3
*   $result = amazonUploadFileS3(array(
*       'file' => '/path/to/local/file.jpg',
*       'folder' => 'uploads/images',
*       'bucket' => 'my-bucket',
*       'region' => 'us-east-1'
*   ));
*   if($result === 1) {
*       echo "File uploaded successfully";
*   } else {
*       echo "Upload failed: " . $result;
*   }
*/
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
		$parts=preg_split('/\r\n\r\n/',trim($response),2);
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
//----------------------------------------------------------

/**
 * Sends SMS message using AWS SNS via cURL with Signature Version 4
 * 
 * @param string $message SMS message content
 * @param array $awsConfig AWS configuration array with keys: accessKey, secretKey, region, phoneNumber
 * @return string AWS SNS response
 * @throws Exception If SMS sending fails
 */
function amazonSMSSendSMSWithCurl($message, $awsConfig) {
    $service = 'sns';
    $region = $awsConfig['region'];
    $host = $service . '.' . $region . '.amazonaws.com';
    $endpoint = 'https://' . $host . '/';
    
    // Request body for SNS Publish
    $requestBody = http_build_query([
        'Action' => 'Publish',
        'Message' => $message,
        'PhoneNumber' => $awsConfig['phoneNumber'],
        'Version' => '2010-03-31'
    ]);
    
    // Create AWS Signature Version 4
    $signature = amazonSMSCreateAWSSignatureV4($requestBody, $awsConfig, $host, $service);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Host: ' . $host,
        'X-Amz-Date: ' . $signature['amzDate'],
        'Authorization: ' . $signature['authHeader']
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('SMS send failed: ' . $httpCode . ' - ' . $response);
    }
    
    echo "SMS sent successfully!\n";
    return $response;
}

/**
 * Creates AWS Signature Version 4 for SNS API requests
 * 
 * @param string $requestBody The HTTP request body
 * @param array $awsConfig AWS configuration array with keys: accessKey, secretKey, region
 * @param string $host AWS service host
 * @param string $service AWS service name (sns)
 * @return array Array with authHeader and amzDate keys
 */
function amazonSMSCreateAWSSignatureV4($requestBody, $awsConfig, $host, $service) {
    $accessKey = $awsConfig['accessKey'];
    $secretKey = $awsConfig['secretKey'];
    $region = $awsConfig['region'];
    
    // Create timestamp
    $timestamp = gmdate('Ymd\THis\Z');
    $dateStamp = gmdate('Ymd');
    
    // Step 1: Create canonical request
    $method = 'POST';
    $canonicalUri = '/';
    $canonicalQueryString = '';
    
    // Canonical headers
    $canonicalHeaders = "content-type:application/x-www-form-urlencoded\n";
    $canonicalHeaders .= "host:" . $host . "\n";
    $canonicalHeaders .= "x-amz-date:" . $timestamp . "\n";
    
    $signedHeaders = 'content-type;host;x-amz-date';
    
    // Create payload hash
    $payloadHash = hash('sha256', $requestBody);
    
    // Create canonical request
    $canonicalRequest = $method . "\n" . $canonicalUri . "\n" . $canonicalQueryString . "\n" . 
                       $canonicalHeaders . "\n" . $signedHeaders . "\n" . $payloadHash;
    
    // Step 2: Create string to sign
    $algorithm = 'AWS4-HMAC-SHA256';
    $credentialScope = $dateStamp . '/' . $region . '/' . $service . '/aws4_request';
    $stringToSign = $algorithm . "\n" . $timestamp . "\n" . $credentialScope . "\n" . 
                   hash('sha256', $canonicalRequest);
    
    // Step 3: Calculate signature
    $signingKey = amazonSMSGetSignatureKey($secretKey, $dateStamp, $region, $service);
    $signature = hash_hmac('sha256', $stringToSign, $signingKey);
    
    // Step 4: Create authorization header
    $authorizationHeader = $algorithm . ' ' . 'Credential=' . $accessKey . '/' . $credentialScope . ', ' .
                          'SignedHeaders=' . $signedHeaders . ', ' . 'Signature=' . $signature;
    
    return [
        'authHeader' => $authorizationHeader,
        'amzDate' => $timestamp
    ];
}

/**
 * Derives AWS signing key for Signature Version 4
 * 
 * @param string $key AWS secret access key
 * @param string $dateStamp Date in YYYYMMDD format
 * @param string $regionName AWS region name
 * @param string $serviceName AWS service name
 * @return string Binary signing key
 */
function amazonSMSGetSignatureKey($key, $dateStamp, $regionName, $serviceName) {
    $kDate = hash_hmac('sha256', $dateStamp, 'AWS4' . $key, true);
    $kRegion = hash_hmac('sha256', $regionName, $kDate, true);
    $kService = hash_hmac('sha256', $serviceName, $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
    return $kSigning;
}

function amazonSendTextMsg($phone, $message) {
    global $CONFIG;
    $service = 'sns';
    $host = "sns.{$region}.amazonaws.com";

    // Trim credentials to remove hidden characters
    $accessKey = trim($CONFIG['aws_accesskey']);
    $secretKey = trim($CONFIG['aws_secretkey']);
    $region=commonCoalesce($CONFIG['aws_region'],'us-east-1');

    $method = 'POST';
    $uri = '/';
    $date = gmdate('Ymd\THis\Z');
    $shortDate = gmdate('Ymd');

    // Build payload with strict encoding
    $payload = "Action=Publish&Message=" . rawurlencode($message) . "&PhoneNumber=" . rawurlencode($phone);
    $hashedPayload = hash('sha256', $payload);

    // Canonical request
    $contentType = 'application/x-www-form-urlencoded; charset=utf-8';
    $canonicalQuery = '';
    $canonicalHeaders = "content-type:{$contentType}\n" .
                       "host:{$host}\n" .
                       "x-amz-date:{$date}\n";
    $signedHeaders = 'content-type;host;x-amz-date';
    $canonicalRequest = "{$method}\n{$uri}\n{$canonicalQuery}\n{$canonicalHeaders}\n{$signedHeaders}\n{$hashedPayload}";

    // String to sign
    $algorithm = 'AWS4-HMAC-SHA256';
    $credentialScope = "{$shortDate}/{$region}/{$service}/aws4_request";
    $stringToSign = "{$algorithm}\n{$date}\n{$credentialScope}\n" . hash('sha256', $canonicalRequest);

    // Signing key
    $kDate = hash_hmac('sha256', $shortDate, "AWS4{$secretKey}", true);
    $kRegion = hash_hmac('sha256', $region, $kDate, true);
    $kService = hash_hmac('sha256', $service, $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
    $signature = bin2hex(hash_hmac('sha256', $stringToSign, $kSigning, true));

    // Authorization header
    $authorizationHeader = "{$algorithm} Credential={$accessKey}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";

    // cURL headers
    $headers = [
        "Content-Type: {$contentType}",
        "Host: {$host}",
        "X-Amz-Date: {$date}",
        "Authorization: {$authorizationHeader}"
    ];

    // cURL setup
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://{$host}{$uri}");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Not recommended for production
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $verbose = fopen('curl_debug.log', 'a+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    fclose($verbose);
    curl_close($ch);

    if ($httpCode == 200) {
        $xml = simplexml_load_string($response);
        $result = [
            'status' => 'SUCCESS',
            'phone_number' => $phone,
            '_cdate' => date('Y-m-d H:i:s'),
            'message_id' => (string)$xml->PublishResult->MessageId,
            'request_id' => (string)$xml->ResponseMetadata->RequestId
        ];
        return $result;
    } else {
        $result = [
            'status' => 'FAILED',
            'phone_number' => $phone,
            '_cdate' => date('Y-m-d H:i:s'),
            'response' => $response,
            'http_code' => $httpCode,
            'curl_error' => $curlError,
            'curl_errno' => $curlErrno
        ];
        return $result;
    }
}

//---------- begin function amazonListFilesS3---------------------------------------
/**
* @describe Lists files in an Amazon S3 bucket using AWS Signature Version 4
* @param array $params Parameters array
*   Optional parameters (will use global CONFIG values if not provided):
*   - -accesskey: AWS Access Key ID
*   - -secretkey: AWS Secret Access Key  
*   - bucket: S3 bucket name
*   - region: AWS region (e.g., 'us-east-1', 'us-west-2')
*   - prefix: Filter files by prefix/folder path (default: '')
*   - max-keys: Maximum number of files to return (default: 1000, max: 1000)
*   - delimiter: Character to group keys by (default: none)
*   - continuation-token: Token for pagination (from previous response)
*   - newer-than: ISO 8601 date string - only return files modified after this date
*   - older-than: ISO 8601 date string - only return files modified before this date
*   - stop-on-old: Boolean - stop fetching when encountering files older than newer-than (default: true)
* @return mixed Returns array with file list on success, or error message string on failure
*   Success response contains:
*   - files: Array of file objects with keys: key, last_modified, size, storage_class
*   - count: Number of files returned
*   - is_truncated: Boolean indicating if more results available
*   - next_continuation_token: Token for next page (if is_truncated is true)
* @usage
*   // List all files in bucket
*   $result = amazonListFilesS3(array(
*       'bucket' => 'my-bucket',
*       'region' => 'us-east-1'
*   ));
*   
*   // List files with prefix filter
*   $result = amazonListFilesS3(array(
*       'prefix' => 'uploads/images/',
*       'max-keys' => 50
*   ));
*   
*   // List only new files from last 24 hours (optimized)
*   $result = amazonListFilesS3(array(
*       'newer-than' => date('c', strtotime('-1 day')),
*       'stop-on-old' => true // Stops fetching when old files found
*   ));
*   
*   // List files in date range
*   $result = amazonListFilesS3(array(
*       'newer-than' => '2024-01-01T00:00:00Z',
*       'older-than' => '2024-12-31T23:59:59Z',
*       'stop-on-old' => false // Continue through all pages
*   ));
*   
*   if(is_array($result)) {
*       echo "Found " . $result['count'] . " files";
*       foreach($result['files'] as $file) {
*           echo $file['key'] . " (" . $file['size'] . " bytes)";
*       }
*   } else {
*       echo "Error: " . $result;
*   }
*/
function amazonListFilesS3($params=array()){
	global $CONFIG;
	$rtn=array();
	$rtn['input_params']=$params;
	//require -accesskey
	if(!isset($params['-accesskey']) && isset($CONFIG['aws_accesskey'])){
		$params['-accesskey']=$CONFIG['aws_accesskey'];
	}
	if(!isset($params['-accesskey'])){
		return "amazonListFilesS3 Error: missing -accesskey";
	}
	//require -secretkey
	if(!isset($params['-secretkey']) && isset($CONFIG['aws_secretkey'])){
		$params['-secretkey']=$CONFIG['aws_secretkey'];
	}
	if(!isset($params['-secretkey'])){
		return "amazonListFilesS3 Error: missing -secretkey";
	}
	//require bucket
	if(!isset($params['bucket']) && isset($CONFIG['aws_bucket'])){
		$params['bucket']=$CONFIG['aws_bucket'];
	}
	if(!isset($params['bucket'])){
		return "amazonListFilesS3 Error: missing bucket param";
	}
	//require region - us-west-2, us-east-1, etc
	if(!isset($params['region']) && isset($CONFIG['aws_region'])){
		$params['region']=$CONFIG['aws_region'];
	}
	if(!isset($params['region'])){
		return "amazonListFilesS3 Error: missing region param";
	}
	
	// Optional parameters with validation
	if(!isset($params['prefix'])){
		$params['prefix']='';
	}
	if(!isset($params['max-keys'])){
		$params['max-keys']=1000;
	}
	// Validate max-keys range
	$params['max-keys'] = max(1, min(1000, intval($params['max-keys'])));
	
	if(!isset($params['delimiter'])){
		$params['delimiter']='';
	}
	if(!isset($params['continuation-token'])){
		$params['continuation-token']='';
	}
	
	// Date filtering parameters
	$newerThan = isset($params['newer-than']) ? $params['newer-than'] : '';
	$olderThan = isset($params['older-than']) ? $params['older-than'] : '';
	$stopOnOld = isset($params['stop-on-old']) ? $params['stop-on-old'] : true;
	
	// Validate date formats if provided
	if($newerThan !== ''){
		$newerThanTime = strtotime($newerThan);
		if($newerThanTime === false){
			return "amazonListFilesS3 Error: Invalid newer-than date format. Use ISO 8601 format.";
		}
	}
	if($olderThan !== ''){
		$olderThanTime = strtotime($olderThan);
		if($olderThanTime === false){
			return "amazonListFilesS3 Error: Invalid older-than date format. Use ISO 8601 format.";
		}
	}
	
	// USER OPTIONS
	$accessKeyId = $params['-accesskey'];
	$secretKey = $params['-secretkey'];
	$bucket = $params['bucket'];
	$region = $params['region'];
	$prefix = $params['prefix'];
	$maxKeys = $params['max-keys'];
	$delimiter = $params['delimiter'];
	$continuationToken = $params['continuation-token'];

	// VARIABLES
	$longDate = gmdate('Ymd\THis\Z');
	$shortDate = gmdate('Ymd');
	$credential = $accessKeyId.'/'.$shortDate.'/'.$region.'/s3/aws4_request';

	// Build query string
	$queryString = 'list-type=2';
	if($prefix != ''){
		$queryString .= '&prefix='.rawurlencode($prefix);
	}
	$queryString .= '&max-keys='.$maxKeys;
	if($delimiter != ''){
		$queryString .= '&delimiter='.rawurlencode($delimiter);
	}
	if($continuationToken != ''){
		$queryString .= '&continuation-token='.rawurlencode($continuationToken);
	}

	// CANONICAL REQUEST
	$canonicalRequest = "GET\n/\n{$queryString}\nhost:{$bucket}.s3.{$region}.amazonaws.com\nx-amz-date:{$longDate}\n\nhost;x-amz-date\n".hash('sha256', '');

	// STRING TO SIGN
	$stringToSign = "AWS4-HMAC-SHA256\n{$longDate}\n{$shortDate}/{$region}/s3/aws4_request\n".hash('sha256', $canonicalRequest);

	// SIGNATURE
	$signingKey = hash_hmac('sha256', $shortDate, 'AWS4' . $secretKey, true);
	$signingKey = hash_hmac('sha256', $region, $signingKey, true);
	$signingKey = hash_hmac('sha256', 's3', $signingKey, true);
	$signingKey = hash_hmac('sha256', 'aws4_request', $signingKey, true);
	$signature = hash_hmac('sha256', $stringToSign, $signingKey);

	// CURL
	$url = "https://{$bucket}.s3.{$region}.amazonaws.com/?{$queryString}";
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_HTTPGET, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	
	$headers = array(
		'Host: '.$bucket.'.s3.'.$region.'.amazonaws.com',
		'X-Amz-Date: '.$longDate,
		'Authorization: AWS4-HMAC-SHA256 Credential='.$credential.', SignedHeaders=host;x-amz-date, Signature='.$signature
	);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	
	$response = curl_exec($ch);
	$rtn['response']=$response;
	$rtn['headers_out']=preg_split('/[\r\n]+/',curl_getinfo($ch,CURLINFO_HEADER_OUT));
	$rtn['curl_info']=curl_getinfo($ch);
	
	if(curl_errno($ch)){
		$rtn['error_number'] = curl_errno($ch);
		$rtn['error'] = curl_error($ch);
	}
	else{
		//break it up into header and body
		$parts=preg_split('/\r\n\r\n/',trim($response),2);
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

	// RESPONSE PROCESSING WITH DATE FILTERING
	if($rtn['curl_info']['http_code'] == 200){
		// Parse XML response to extract file list
		$xml = simplexml_load_string($rtn['body']);
		if($xml === false){
			return 'amazonListFilesS3 Error: Invalid XML response';
		}
		
		// Process files with date filtering
		$allFiles = array();
		$filteredFiles = array();
		$shouldStopFetching = false;
		$oldFileEncountered = false;
		
		if(isset($xml->Contents)){
			foreach($xml->Contents as $content){
				$file = array(
					'key' => (string)$content->Key,
					'last_modified' => (string)$content->LastModified,
					'size' => (int)$content->Size,
					'storage_class' => (string)$content->StorageClass,
					'etag' => trim((string)$content->ETag, '"')
				);
				
				$allFiles[] = $file;
				$fileTime = strtotime($file['last_modified']);
				$includeFile = true;
				
				// Apply date filters
				if($newerThan !== '' && $fileTime <= $newerThanTime){
					$includeFile = false;
					$oldFileEncountered = true;
					
					// If stop-on-old is enabled and we're filtering by newer-than, stop here
					if($stopOnOld && $newerThan !== ''){
						$shouldStopFetching = true;
					}
				}
				
				if($olderThan !== '' && $fileTime >= $olderThanTime){
					$includeFile = false;
				}
				
				if($includeFile){
					$filteredFiles[] = $file;
				}
				
				// If we should stop fetching, break the loop
				if($shouldStopFetching){
					break;
				}
			}
		}
		
		// Parse pagination information
		$isTruncated = isset($xml->IsTruncated) ? ((string)$xml->IsTruncated === 'true') : false;
		
		// If we encountered old files and should stop, override truncation
		if($shouldStopFetching){
			$isTruncated = false; // Don't continue pagination
		}
		
		$result = array(
			'files' => $filteredFiles,
			'count' => count($filteredFiles),
			'is_truncated' => $isTruncated,
			'max_keys' => isset($xml->MaxKeys) ? (int)$xml->MaxKeys : $maxKeys,
			'key_count' => isset($xml->KeyCount) ? (int)$xml->KeyCount : count($allFiles),
			'total_files_examined' => count($allFiles),
			'files_filtered_out' => count($allFiles) - count($filteredFiles)
		);
		
		// Add filter information to response
		if($newerThan !== '' || $olderThan !== ''){
			$result['date_filters_applied'] = array();
			if($newerThan !== '') $result['date_filters_applied']['newer_than'] = $newerThan;
			if($olderThan !== '') $result['date_filters_applied']['older_than'] = $olderThan;
			$result['date_filters_applied']['stop_on_old'] = $stopOnOld;
			$result['stopped_early'] = $shouldStopFetching;
		}
		
		if(isset($xml->NextContinuationToken) && !$shouldStopFetching){
			$result['next_continuation_token'] = (string)$xml->NextContinuationToken;
		}
		
		// Parse common prefixes (folders) if delimiter was used
		if(isset($xml->CommonPrefixes)){
			$result['common_prefixes'] = array();
			foreach($xml->CommonPrefixes as $commonPrefix){
				$result['common_prefixes'][] = (string)$commonPrefix->Prefix;
			}
		}
		
		return $result;
	} 
	else {
		return 'amazonListFilesS3 Error: HTTP '.$rtn['curl_info']['http_code'].' - '.printValue($rtn);
	}
}

//---------- begin function amazonListAllNewFilesS3---------------------------------------
/**
* @describe Lists all new files from S3 with automatic pagination until date limit reached
* @param array $params Same parameters as amazonListFilesS3, plus:
*   - target-count: Stop after finding this many matching files (default: unlimited)
*   - max-pages: Maximum number of pages to fetch (default: 10, prevents runaway)
* @return mixed Returns array with all matching files, or error message string on failure
* @usage
*   // Get ALL files from last 24 hours (may span multiple pages)
*   $result = amazonListAllNewFilesS3(array(
*       'newer-than' => date('c', strtotime('-1 day')),
*       'max-pages' => 20
*   ));
*/
function amazonListAllNewFilesS3($params = array()) {
    $targetCount = isset($params['target-count']) ? intval($params['target-count']) : 0;
    $maxPages = isset($params['max-pages']) ? intval($params['max-pages']) : 10;
    
    // Remove helper params from S3 request
    unset($params['target-count']);
    unset($params['max-pages']);
    
    $allFiles = array();
    $totalExamined = 0;
    $totalFilteredOut = 0;
    $pageCount = 0;
    $continuationToken = '';
    
    do {
        $pageCount++;
        
        // Set continuation token for pagination
        if($continuationToken !== '') {
            $params['continuation-token'] = $continuationToken;
        }
        
        $result = amazonListFilesS3($params);
        
        if(!is_array($result)) {
            return $result; // Return error
        }
        
        // Add files from this page
        $allFiles = array_merge($allFiles, $result['files']);
        $totalExamined += $result['total_files_examined'];
        $totalFilteredOut += $result['files_filtered_out'];
        
        // Check if we should continue
        $shouldContinue = false;
        
        // Continue if we haven't reached target count
        if($targetCount > 0 && count($allFiles) < $targetCount) {
            $shouldContinue = true;
        } else if($targetCount === 0) {
            $shouldContinue = true;
        }
        
        // Continue if there are more pages and we didn't stop early
        if($shouldContinue && isset($result['next_continuation_token']) && !$result['stopped_early']) {
            $continuationToken = $result['next_continuation_token'];
            $shouldContinue = true;
        } else {
            $shouldContinue = false;
        }
        
        // Don't exceed max pages
        if($pageCount >= $maxPages) {
            $shouldContinue = false;
        }
        
    } while($shouldContinue);
    
    // Trim results to target count if specified
    if($targetCount > 0 && count($allFiles) > $targetCount) {
        $allFiles = array_slice($allFiles, 0, $targetCount);
    }
    
    return array(
        'files' => $allFiles,
        'count' => count($allFiles),
        'pages_fetched' => $pageCount,
        'total_files_examined' => $totalExamined,
        'files_filtered_out' => $totalFilteredOut,
        'date_filters_applied' => isset($result['date_filters_applied']) ? $result['date_filters_applied'] : null,
        'stopped_at_max_pages' => $pageCount >= $maxPages,
        'target_count_reached' => $targetCount > 0 && count($allFiles) >= $targetCount
    );
}

//---------- begin function amazonGetFileS3---------------------------------------
/**
* @describe Downloads a file from Amazon S3 using AWS Signature Version 4
* @param array $params Parameters array
*   Required parameters:
*   - key: S3 object key/path to download
*   
*   Optional parameters (will use global CONFIG values if not provided):
*   - -accesskey: AWS Access Key ID
*   - -secretkey: AWS Secret Access Key  
*   - bucket: S3 bucket name
*   - region: AWS region (e.g., 'us-east-1', 'us-west-2')
*   - save_to: Local file path to save downloaded content (optional)
*   - range: HTTP Range header value for partial download (e.g., 'bytes=0-1023')
*   - if_modified_since: Only download if modified since this date (RFC 2822 format)
*   - if_none_match: Only download if ETag doesn't match this value
* @return mixed Returns array with file content on success, or error message string on failure
*   Success response contains:
*   - content: Raw file content (if save_to not specified)
*   - content_length: Size of downloaded content in bytes
*   - content_type: MIME type of the file
*   - etag: ETag of the file
*   - last_modified: Last modification date
*   - saved_to: Local file path (if save_to was specified)
*   - bytes_written: Number of bytes written to local file (if save_to was specified)
* @usage
*   // Download file to memory
*   $result = amazonGetFileS3(array(
*       'key' => 'uploads/document.pdf',
*       'bucket' => 'my-bucket',
*       'region' => 'us-east-1'
*   ));
*   if(is_array($result)) {
*       file_put_contents('local_file.pdf', $result['content']);
*   }
*   
*   // Download file directly to disk
*   $result = amazonGetFileS3(array(
*       'key' => 'uploads/large-file.zip',
*       'save_to' => '/local/path/large-file.zip'
*   ));
*   
*   // Download partial file (first 1KB)
*   $result = amazonGetFileS3(array(
*       'key' => 'uploads/video.mp4',
*       'range' => 'bytes=0-1023'
*   ));
*/
function amazonGetFileS3($params=array()){
	global $CONFIG;
	$rtn=array();
	$rtn['input_params']=$params;
	//require -accesskey
	if(!isset($params['-accesskey']) && isset($CONFIG['aws_accesskey'])){
		$params['-accesskey']=$CONFIG['aws_accesskey'];
	}
	if(!isset($params['-accesskey'])){
		return "amazonGetFileS3 Error: missing -accesskey";
	}
	//require -secretkey
	if(!isset($params['-secretkey']) && isset($CONFIG['aws_secretkey'])){
		$params['-secretkey']=$CONFIG['aws_secretkey'];
	}
	if(!isset($params['-secretkey'])){
		return "amazonGetFileS3 Error: missing -secretkey";
	}
	//require bucket
	if(!isset($params['bucket']) && isset($CONFIG['aws_bucket'])){
		$params['bucket']=$CONFIG['aws_bucket'];
	}
	if(!isset($params['bucket'])){
		return "amazonGetFileS3 Error: missing bucket param";
	}
	//require region - us-west-2, us-east-1, etc
	if(!isset($params['region']) && isset($CONFIG['aws_region'])){
		$params['region']=$CONFIG['aws_region'];
	}
	if(!isset($params['region'])){
		return "amazonGetFileS3 Error: missing region param";
	}
	//require key (file path in S3)
	if(!isset($params['key']) || trim($params['key']) === ''){
		return "amazonGetFileS3 Error: missing or empty key param";
	}
	
	// Optional parameters
	$saveToFile = isset($params['save_to']) ? $params['save_to'] : '';
	$range = isset($params['range']) ? $params['range'] : '';
	$ifModifiedSince = isset($params['if_modified_since']) ? $params['if_modified_since'] : '';
	$ifNoneMatch = isset($params['if_none_match']) ? $params['if_none_match'] : '';
	
	// Validate save_to directory exists if specified
	if($saveToFile !== ''){
		$saveDir = dirname($saveToFile);
		if(!is_dir($saveDir)){
			return "amazonGetFileS3 Error: Directory does not exist: {$saveDir}";
		}
		if(!is_writable($saveDir)){
			return "amazonGetFileS3 Error: Directory not writable: {$saveDir}";
		}
	}

	// USER OPTIONS
	$accessKeyId = $params['-accesskey'];
	$secretKey = $params['-secretkey'];
	$bucket = $params['bucket'];
	$region = $params['region'];
	$key = trim($params['key']);

	// VARIABLES
	$longDate = gmdate('Ymd\THis\Z');
	$shortDate = gmdate('Ymd');
	$credential = $accessKeyId.'/'.$shortDate.'/'.$region.'/s3/aws4_request';

	// CANONICAL REQUEST
	$canonicalRequest = "GET\n/".rawurlencode($key)."\n\nhost:{$bucket}.s3.{$region}.amazonaws.com\nx-amz-date:{$longDate}\n\nhost;x-amz-date\n".hash('sha256', '');

	// STRING TO SIGN
	$stringToSign = "AWS4-HMAC-SHA256\n{$longDate}\n{$shortDate}/{$region}/s3/aws4_request\n".hash('sha256', $canonicalRequest);

	// SIGNATURE
	$signingKey = hash_hmac('sha256', $shortDate, 'AWS4' . $secretKey, true);
	$signingKey = hash_hmac('sha256', $region, $signingKey, true);
	$signingKey = hash_hmac('sha256', 's3', $signingKey, true);
	$signingKey = hash_hmac('sha256', 'aws4_request', $signingKey, true);
	$signature = hash_hmac('sha256', $stringToSign, $signingKey);

	// CURL
	$url = "https://{$bucket}.s3.{$region}.amazonaws.com/".rawurlencode($key);
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_HTTPGET, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	
	$headers = array(
		'Host: '.$bucket.'.s3.'.$region.'.amazonaws.com',
		'X-Amz-Date: '.$longDate,
		'Authorization: AWS4-HMAC-SHA256 Credential='.$credential.', SignedHeaders=host;x-amz-date, Signature='.$signature
	);
	
	// Add optional headers
	if($range !== ''){
		$headers[] = 'Range: '.$range;
	}
	if($ifModifiedSince !== ''){
		$headers[] = 'If-Modified-Since: '.$ifModifiedSince;
	}
	if($ifNoneMatch !== ''){
		$headers[] = 'If-None-Match: '.$ifNoneMatch;
	}
	
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	
	$response = curl_exec($ch);
	$rtn['response']=$response;
	$rtn['headers_out']=preg_split('/[\r\n]+/',curl_getinfo($ch,CURLINFO_HEADER_OUT));
	$rtn['curl_info']=curl_getinfo($ch);
	
	if(curl_errno($ch)){
		$rtn['error_number'] = curl_errno($ch);
		$rtn['error'] = curl_error($ch);
	}
	else{
		//break it up into header and body
		$parts=preg_split('/\r\n\r\n/',trim($response),2);
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

	// RESPONSE
	$httpCode = $rtn['curl_info']['http_code'];
	
	if($httpCode == 200 || $httpCode == 206){  // 206 = Partial Content for range requests
		// Extract metadata from response headers
		$result = array(
			'content' => $rtn['body'],
			'content_length' => strlen($rtn['body']),
			'http_code' => $httpCode
		);
		
		// Parse response headers for metadata
		if(isset($rtn['headers'])){
			if(isset($rtn['headers']['content-type'])){
				$result['content_type'] = $rtn['headers']['content-type'];
			}
			if(isset($rtn['headers']['etag'])){
				$result['etag'] = trim($rtn['headers']['etag'], '"');
			}
			if(isset($rtn['headers']['last-modified'])){
				$result['last_modified'] = $rtn['headers']['last-modified'];
			}
			if(isset($rtn['headers']['content-range'])){
				$result['content_range'] = $rtn['headers']['content-range'];
			}
			if(isset($rtn['headers']['accept-ranges'])){
				$result['accept_ranges'] = $rtn['headers']['accept-ranges'];
			}
		}
		
		// For successful download, save to file if specified
		if($saveToFile !== ''){
			$bytesWritten = file_put_contents($saveToFile, $rtn['body']);
			if($bytesWritten !== false){
				$result['saved_to'] = $saveToFile;
				$result['bytes_written'] = $bytesWritten;
				// Don't include content in memory if saved to file (saves memory)
				unset($result['content']);
			}
			else{
				$result['save_error'] = 'Failed to save file to '.$saveToFile;
			}
		}
		
		return $result;
	}
	else if($httpCode == 304){
		// Not Modified - file hasn't changed
		return array(
			'not_modified' => true,
			'http_code' => $httpCode,
			'message' => 'File not modified since last request'
		);
	}
	else if($httpCode == 404){
		return 'amazonGetFileS3 Error: File not found - '.$key;
	}
	else if($httpCode == 403){
		return 'amazonGetFileS3 Error: Access denied - check permissions for '.$key;
	}
	else {
		return 'amazonGetFileS3 Error: HTTP '.$httpCode.' - '.printValue($rtn);
	}
}
?>