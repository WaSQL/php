<?php
function pageAddSignup($email){
	$rec=getDBRecord(array(
		'-table'=>'email_list',
		'email'	=> $email
	));
	if(isset($rec['_id'])){
		if($rec['active']==1){
			return array('error'=>'You Are Already Signed Up');
		}
		else{
			$ok=executeSQL("update email_list set active=1,confirm_date=NULL where _id={$rec['_id']}");
        	return $rec;
		}
	}
	$id=addDBRecord(array(
		'-table'=>'email_list',
		'email'	=> $email
	));
	if(isNum($id)){
    	return getDBRecordById('email_list',$id);
	}
	else return array('error'=>$id);
}
function pageConfirmSignup($email){
	$rec=getDBRecord(array(
		'-table'=>'email_list',
		'email'	=> $email
	));
	if(isset($rec['_id'])){
		if($rec['active']==1 && strlen($rec['confirm_date'])){
			return array('error'=>'You Are Already Confirmed');
		}
		$rec['confirm_date']=date('Y-m-d H:i:s');
    	$ok=executeSQL("update email_list set confirm_date='{$rec['confirm_date']}' where _id={$rec['_id']}");
    	return $rec;
	}
	else return array('error'=>'You Are Not Signed Up');
}
function pageUnsubscribeSignup($email){
	$rec=getDBRecord(array(
		'-table'=>'email_list',
		'email'	=> $email
	));
	if(isset($rec['_id'])){
		if($rec['active']==0){
			return array('error'=>'You Are Already Unsubscribed');
		}
		$rec['unsubscribe_date']=date('Y-m-d H:i:s');
    	$ok=executeSQL("update email_list set active=0,unsubscribe_date='{$rec['unsubscribe_date']}' where _id={$rec['_id']}");
    	return $rec;
	}
	else return array('error'=>'You Are Not Signed Up');
}
?>
