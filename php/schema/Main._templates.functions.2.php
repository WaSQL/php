<?php
loadExtrasCss('bootstrap');
function templateSocialButtons(){
	return buildSocialButtons(array(
		'facebook'	=> "http://www.facebook.com/yourfacebookpage",
		'-size'		=> 'small',
		'-tooltip'=>true
	));
}
?>
