<?php
loadDBFunctions(array('functions_common'));
loadExtrasJs('chart');
setView('default');
$viewname='';
if(isset($_REQUEST['passthru'][0])){
	$viewname=strtolower($_REQUEST['passthru'][0]);
	switch(strtolower($viewname)){
    	case 'new':
    		setView('addedit',1);
    		return;
    	break;
    	case 'faq':
    		if(isset($_REQUEST['category'])){
				$recs=pageGetFAQs($_REQUEST['category'],$_REQUEST['subcategory']);
            	setView('faq_questions',1);
            	return;
			}
    	break;
    	case 'search':
    		$tickets=pageSearchResults(addslashes($_REQUEST['search']));
    		setView('search_results',1);
    		return;
    	break;
    	case 'submit':
    		setView('thankyou',1);
    		return;
    	break;
    	case 'send':
			setView('thankyou2',1);
			$from=addslashes($_REQUEST['name']).' <'.addslashes($_REQUEST['email']).'>';
			$subject='Skillsai Website Contact Form: '.nl2br(addslashes(trim($_REQUEST['subject'])));
			$sendopts=array(
				'-format'	=>'email',
				'to'		=>isDBStage()?'steve.lloyd@gmail.com':'team@skillsai.com',
				'from'		=> $from,
				'subject'	=> $subject
			);
			return;
		break;
	}
}
else{setView('boxes');}

?>
