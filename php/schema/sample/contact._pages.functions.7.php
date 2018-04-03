<?php
function pageContactForm(){
	return addeditDBForm(array(
		'-table'=>"contact_form",
		'-fields'=>"name,email,subject,message",
		'-id'=> "contact-form",
		'func'=>'thankyou',
		'_template'=>1,
		'-onsubmit'=>"ajaxSubmitForm(this,'centerpop');return false;"
	));
}
?>

