function configAuthMethodChanged(){
	let el=document.querySelector('#config_auth_method');
	let auth=el.options[el.selectedIndex].value;
	el.dataset.func='config_users_'+auth;
	configNav(el);
	return true;
}
function configLogsViewFile(file){
	let params={setprocessing:0,'_menu':'config',func:'config_logs_view_file',file:file};
	return ajaxGet('/php/admin.php','centerpop',params);
}
function configNav(el,opts){
	if(undefined != el.dataset.confirm){
		if(!confirm(el.dataset.confirm)){return false;}
	}
	if(undefined == opts){opts={};}
	if(undefined != el.dataset.tab){
		wacss.setActiveTab(el);
	}
	let div=el.dataset.div || 'main_content';
	let nav=el.dataset.nav;
	let params={setprocessing:0,'_menu':'config'};
	if(undefined != el.dataset.title){
		params.title=el.dataset.title;
		params.cp_title=el.dataset.title;
	}
	for(k in el.dataset){
		if(k=='nav'){continue;}
		if(k=='div'){continue;}
		if(el.dataset[k].length==0){continue;}
		params[k]=el.dataset[k];
	}
	for(k in opts){
		params[k]=opts[k];
	}
	if(div.indexOf('reports_')==0){
		let txt=document.getElementById(div).innerText;
		if(txt.length > 10){
			setText(div,'');
			return false;
		}
	}
	return ajaxGet(nav,div,params);
}
function configUpdateSessionCookieLifetimeInputValue(el){
	// Make input for okta_simplesamlphp_config_session__cookie__lifetime the same value as the input this event is attached to (el) + 1 hour
	var val=parseInt(el.value);
	val+=(60*60);
	document.querySelector('[name="okta_simplesamlphp_config_session__cookie__lifetime_int"]').value=val;
}