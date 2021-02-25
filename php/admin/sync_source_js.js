function sync_sourceCheck(id){
	let params={id:id};
	params['_menu']='sync_source';
	params['func']='check';
	return ajaxGet('/php/admin.php','centerpop',params);
}
function sync_sourceAddEdit(id){
	let params={id:id,setprocessing:0};
	params['_menu']='sync_source';
	params['func']='addedit';
	return ajaxGet('/php/admin.php','sync_source_addedit',params);
}
function sync_sourceSync(id){
	if(!confirm('Update your copy to match the source record? THIS CANNOT BE UNDONE.')){
		return false;
	}
	let params={id:id};
	params['_menu']='sync_source';
	params['func']='sync';
	return ajaxGet('/php/admin.php','sync_source_content',params);
}
function sync_sourceFunc(el){
	if(undefined != el.dataset.confirm && !confirm(el.dataset.confirm)){
		return false;
	}		
	let params=el.dataset;
	params['_menu']='sync_source';
	if(undefined!=el.value){params.value=el.value;}
	else if(undefined != el.options[el.selectedIndex].value){
		params.value=el.options[el.selectedIndex].value;
	}
	if(undefined==params.div){
		if(undefined != el.id){
			params.div=el.id;
		}
		else{
			params.div='sync_source_content';
		}
	}
	if(undefined==params.setprocessing){
		params.setprocessing=el;
	}
	//console.log(params);
	return ajaxGet('/php/admin.php',params.div,params);
}
