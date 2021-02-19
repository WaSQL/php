function dbsyncFunc(el){
	if(undefined != el.dataset.confirm && !confirm(el.dataset.confirm)){return false;
}		let params=el.dataset;
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
	return ajaxGet('/php/admin.php',params.div,params)
}