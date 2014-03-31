<?php
//error_reporting(E_STRICT | E_ALL);
/*
*	Debian Servers may need the following:
*	>apt-get install php5-imap
*	>apache2ctl restart
*/

//---------------------------------------
//mailbox Functions
function mailboxGetMessages($mbox,$basefilepath){
	if(!is_dir($basefilepath)){buildDir($basefilepath);}
	if(!is_dir($basefilepath)){
		$basefilepath=dirname(__FILE__) . "/temp";
    	}
	$emails=array();
	$list=mailboxList($mbox);
	if(is_array($list)){
		foreach($list as $email){
			$filepath=$basefilepath;
			if($email['deleted']==1){continue;}
			//set date_utime
	        if(isset($email['date'])){
				$email['date_utime']=strtotime($email['date']);
				$email['date_mysql']=date('Y-m-d H:i:s',$email['date_utime']);
				$year=date('Y',$email['date_utime']);
				$month=date('F',$email['date_utime']);
				$email['filepath']=$filepath;
				$email['year']=$year;
				$email['month']=$month;
				if(!is_dir("{$filepath}/{$year}/{$month}")){
					buildDir("{$filepath}/{$year}/{$month}");
				}
				if(is_dir("{$filepath}/{$year}/{$month}")){
                    $filepath="{$filepath}/{$year}/{$month}";
                    $email['filepath']=$filepath;
				}
            }
			//echo printValue($email);
			$msgparts=mailboxGetMessage($mbox,$email['msgno'],true);
			if(is_array($msgparts) && count($msgparts)){
				foreach($msgparts as $pid=>$msgpart){
					if(is_array($msgpart) && isset($msgpart['is_attachment'])){
						$email['attachment_count']+=1;
						$msgpart['sha']=sha1($email['message_id'].$email['msgno'].$msgpart['data']);
						$msgpart['ext']=getFileExtension($msgpart['filename']);
						//Save attachment
						$msgpart['path']=$filepath;
						$msgpart['tmpfile']="{$filepath}/{$msgpart['sha']}.{$msgpart['ext']}";
						if(!is_file($msgpart['tmpfile'])){
							$ok=setFileContents($msgpart['tmpfile'],$msgpart['data']);
							}
						$msgpart['tmpfile_size']=filesize($msgpart['tmpfile']);
						unset($msgpart['data']);
						if(isset($msgpart['creation-date'])){
							$msgpart['file_cdate']=$msgpart['creation-date'];
							unset($msgpart['creation-date']);
	                    	}
	                    if(isset($msgpart['modification-date'])){
							$msgpart['file_mdate']=$msgpart['modification-date'];
							unset($msgpart['modification-date']);
	                    	}
						foreach($msgpart as $key=>$val){
							if(preg_match('/date$/i',$key)){
								$msgpart[$key.'_utime']=strtotime($val);
								$msgpart[$key.'_mysql']=date('Y-m-d H:i:s',$msgpart[$key.'_utime']);
				            	}
	                    	}
	                    ksort($msgpart);
						$email['attachments'][]=$msgpart;
		            	}
		            elseif(isset($msgpart['parsed']) && is_array($msgpart['parsed']) && !isset($email['headers'])){
						$email['headers']=$msgpart['parsed'];
						if(isset($msgpart['encoding'])){$email['encoding']=$msgpart['encoding'];}
	                	}
		            else{
						if(isXML($msgpart['data'])){$email['html']=$msgpart['data'];}
						else{$email['text']=$msgpart['data'];}
						if(isset($msgpart['encoding'])){$email['encoding']=$msgpart['encoding'];}
						unset($msgpart['data']);
						//echo printValue($msgpart);
	                	}
		        	}
				}
			$email['body']=imap_body($mbox, $email['msgno']);
			if(isset($email['encoding'])){
				switch($email['encoding']){
					case 3:
						//base64
        				$email['body'] = base64_decode($email['body']);
        				break;
        			case 4:
        				//QUOTED-PRINTABLE
        				$email['body'] = quoted_printable_decode($email['body']);
        				break;
				}
			}
			if(!isset($email['text']) && !isset($email['html'])){
				$email['text']=trim(removeHtml($email['body']));
				$email['html']=$email['body'];
            }
            if(isset($email['html'])){
            	$email['html']=str_replace('&nbsp;',' ',$email['html']);
				$email['html']=str_replace('&quot;','"',$email['html']);
				$email['html']=str_replace('</div>',"</div>\r\n",$email['html']);
				$email['html']=str_replace('&#39;',"'",$email['html']);
				$email['html']=str_replace('<br>',"<br>\r\n",$email['html']);
				$email['html']=str_replace('<br />',"<br />\r\n",$email['html']);
			}
	        if(!isset($email['text']) && isset($email['html'])){
				$lines=preg_split('/[\r\n]+/',trim(removeHtml($email['html'])));
				$txt='';
				foreach($lines as $line){
					$line=preg_replace('/[^a-zA-Z0-9\-\'\"\.\,\;\:\-\=\+\[\]\{\}\!\@\#\$\%\&\*\(\)\_\?\/\~\'\t\ \s\r\n]+/','',$line);
					$line=trim($line);
					if(!strlen($line)){continue;}
					$txt .= "{$line}\r\n";
                }
				$email['text']=$txt;
			}
	        //parse from
	        $email['from_email']=mailboxParseEmail($email['from']);
	        //sha
            $shaparts=$email['message_id'].$email['msgno'].$email['attachment_count'].$email['date_utime'].$email['from'].$email['to'];
			$email['sha']=sha1($shaparts);
			unset($email['body']);
            ksort($email);
	        $emails[]=$email;
	    }
	}
	return $emails;
}
function mailboxParseEmail($str){
	//Steve Lloyd <slloyd@timequest.org>

	if(preg_match('/\<(.+?)\>/',$str,$pm) && isEmail($pm[1])){return $pm[1];}
	if(isEmail($str)){return $str;}
	return null;
	}
function mailboxConnect($host,$port,$user,$pass,$novalidate=1){
	$mbox=false;
	switch(strtolower((string)$port)){
		case '143':
		case 'imap':
			//IMAP server running on port 143 (Non Secure Mode)
			if($novalidate==1){
				//do not validate cert
				$mbox = "{".$host.":143/novalidate-cert}INBOX";
			}
			else{$mbox = "{".$host.":143}INBOX";}
			break;
		case '110':
		case 'pop3':
		case 'pop':
			//POP3 server running on port 110 (Non Secure Mode)
			$mbox = "{".$host.":110/pop3}INBOX";
			break;
		case '993':
		case 'imapssl':
			//IMAP server running on port 993 (Secure Mode)
			if($novalidate==1){
				//do not validate cert
				$mbox = "{".$host.":993/imap/ssl/novalidate-cert}INBOX";
			}
			else{$mbox = "{".$host.":993/imap/ssl}INBOX";}
			break;
		case '995':
		case 'pop3ssl':
		case 'pop3ssl':
			//POP3 server running on port 995 (Secure Mode)
			if($novalidate==1){
				//do not validate cert
				$mbox = "{".$host.":995/pop3/ssl/novalidate-cert}INBOX";
			}
			else{$mbox = "{".$host.":995/pop3/ssl}INBOX";}
			break;
		case '119':
		case 'nntp':
			//NNTP server running on port 119 (Secure Mode)
			if($novalidate==1){
				//do not validate cert
				$mbox = "{".$host.":993/imap/ssl/novalidate-cert}INBOX";
			}
			else{$mbox = "{".$host.":993/imap/ssl}INBOX";}
			$user='';
			$pass='';
			break;
    	}
    $connectopts=array($mbox,$user,$pass);
    //return $connectopts;
    $connection=imap_open($mbox, $user, $pass);
    if(is_resource($connection)){return $connection;}
    else{
		//failed - get error
		$error = error_get_last();
		return $error['message'] . printValue($connectopts);
	}
}
function mailboxDisconnect($mbox){
	return @imap_close($mbox);
}
function mailboxInfo($mbox){
    $check = imap_mailboxmsginfo($mbox);
    return ((array)$check);
	}
function mailboxList($mbox,$message=""){
    if ($message){
        $range=$message;
    	}
	else{
        $MC = imap_check($mbox);
        //echo printValue($mbox);
        $range = "1:".$MC->Nmsgs;
    	}
    $response = imap_fetch_overview($mbox,$range);
    $result=array();
    foreach ($response as $msg){
		$result[$msg->msgno]=(array)$msg;
		}
	return $result;
	}
function mailboxGetMessageHeader($mbox,$message){
    return(imap_fetchheader($mbox,$message,FT_PREFETCHTEXT));
	}
function mailboxDeleteMessage($mbox,$msgno){
    return(imap_delete($mbox,$msgno));
	}
function mailboxParseHeaders($headers){
	$lines=preg_split('/[\r\n]+/m',$headers);
	$hdr=array();
	foreach($lines as $line){
		$line=trim($line);
		if(preg_match('/(.+?)\:(.+)/',$line,$lmatch)){
			$key=strtolower($lmatch[1]);
			$hdr[$key]=$lmatch[2];
        	}
    	}
    return $hdr;
    $headers=preg_replace('/\r\n\s+/m', '',$headers);
    preg_match_all('/([^: ]+): (.+?(?:\r\n\s(?:.+?))*)?\r\n/m', $headers, $matches);
    foreach ($matches[1] as $key =>$value) $result[$value]=$matches[2][$key];
    return($result);
	}
function mailboxGetMessage($mbox,$message,$parse_headers=true){
    $mail = imap_fetchstructure($mbox,$message);
    //echo "imap_fetchstructure" . printValue($mail);
    $pmail = mailboxGetMessageParts($mbox,$message,$mail,0);

    //echo "mailboxGetMessageParts" . printValue($pmail);
    if($parse_headers){
		$pmail[0]['parsed']=mailboxParseHeaders($pmail[0]["data"]);
		}
    //echo printValue($pmail[0]["parsed"]);
    return($pmail);
	}
function mailboxGetMessageParts($imap,$mid,$part,$prefix){
    $attachments=array();
    $attachments[$prefix]=mailboxDecodeMessagePart($imap,$mid,$part,$prefix);
    // multipart ?
    if (isset($part->parts)){
        $prefix = ($prefix == "0")?"":"$prefix.";
        foreach ($part->parts as $number=>$subpart){
            $attachments=array_merge($attachments, mailboxGetMessageParts($imap,$mid,$subpart,$prefix.($number+1)));
			}
    	}
    return $attachments;
	}
function mailboxDecodeMessagePart($mbox,$message_number,$part,$prefix){
	//echo "mailboxDecodeMessagePart" . printValue($part);
    $attachment = array();
    if($part->ifdparameters) {
        foreach($part->dparameters as $object) {
            $attachment[strtolower($object->attribute)]=$object->value;
            if(strtolower($object->attribute) == 'filename') {
                $attachment['is_attachment'] = true;
                $attachment['filename'] = $object->value;
            	}
        	}
    	}
    if($part->ifparameters) {
        foreach($part->parameters as $object) {
            $attachment[strtolower($object->attribute)]=$object->value;
            if(strtolower($object->attribute) == 'name') {
                $attachment['is_attachment'] = true;
                $attachment['name'] = $object->value;
            	}
        	}
    	}
    $attachment['encoding']=$part->encoding;
    $attachment['data'] = imap_fetchbody($mbox, $message_number, $prefix);
    if($part->encoding == 3) { // 3 = BASE64
        $attachment['data'] = base64_decode($attachment['data']);
    	}
    elseif($part->encoding == 4) { // 4 = QUOTED-PRINTABLE
        $attachment['data'] = quoted_printable_decode($attachment['data']);
    	}
    //echo "attachment" . printValue($attachment);
    return($attachment);
	}

?>