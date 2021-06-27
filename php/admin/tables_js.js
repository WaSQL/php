function tablesNav(el){
	if(undefined != el.dataset.confirm && !confirm(el.dataset.confirm)){
		return false;
	}		
	let params=el.dataset;
	params['_menu']='tables';
	if(undefined==params.div){
		params.div='tables_content';
	}
	if(undefined==params.setprocessing){
		params.setprocessing=el;
	}
	//console.log(params);
	return ajaxGet('/php/admin.php',params.div,params)
}