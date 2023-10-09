function configAuthMethodChanged(){
	let el=document.querySelector('#config_auth_method');
	let auth=el.options[el.selectedIndex].value;
	el.dataset.func='config_users_'+auth;
	el.dataset._menu='config';
	el.dataset.nav='/php/admin.php';
	wacss.nav(el);
	return true;
}
function configLogsViewFile(file){
	let params={setprocessing:0,'_menu':'config',func:'config_logs_view_file',file:file};
	return ajaxGet('/php/admin.php','centerpop',params);
}
function configUpdateSessionCookieLifetimeInputValue(el){
	// Make input for okta_simplesamlphp_config_session__cookie__lifetime the same value as the input this event is attached to (el) + 1 hour
	var val=parseInt(el.value);
	val+=(60*60);
	document.querySelector('[name="okta_simplesamlphp_config_session__cookie__lifetime_int"]').value=val;
}