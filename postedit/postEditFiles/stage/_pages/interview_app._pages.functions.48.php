<?php
function pageGetQuestion($id){
	$rec=getDBRecord(array('-table'=>'interview_questions','_id'=>$id));
	if(isset($rec['_id'])){
		return "<speak>Ok, Take {$rec['_id']}! <break time=\"3s\" /> {$rec['question']}</speak>";
	}
	return '';
}

?>
