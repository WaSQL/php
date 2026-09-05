function dbsyncFunc(el){
	if(undefined != el.dataset.confirm && !confirm(el.dataset.confirm)){
		return false;
	}		
	let params=el.dataset;
	params['_menu']='dbsync';
	if(undefined==params.div){
		if(undefined != el.id){
			params.div=el.id;
		}
		else{
			params.div='dbsync_content';
		}
	}
	if(undefined==params.setprocessing){
		params.setprocessing=el;
	}
	//console.log(params);
	return wacss.ajaxGet('/php/admin.php',params.div,params)
}
function dbsyncCompare(el){
	wacss.setActiveTab(el);
	let tab=el.dataset.tab;
	document.dbsync_form.tab.value=tab;
	//the form's own data-setprocessing targets dbsync_content (correct for the initial full-page Compare
	//submit), but this tab-switch only replaces the nested compare_results div - pointing the spinner at
	//the wrong (parent) div would wipe out compare_results before the response can land in it, leaving a
	//permanent spinner with nowhere to go. Point it at the actual target for this call only.
	document.dbsync_form.dataset.setprocessing='compare_results';
	return wacss.ajaxPost(document.dbsync_form,'compare_results');
}