function manualNav(el) {
	let div='results';
	if(undefined != el.dataset.div){
		div=el.dataset.div;
	}
	if(div != 'results'){
		let content=getText(div);
		if(content.length > 5){
			setText(div,'');
			return false;
		}
	}
	let params={setprocessing:0};
	for(key in el.dataset){
		// Check hasOwnProperty to prevent prototype pollution
		if(el.dataset.hasOwnProperty(key)){
			params[key]=el.dataset[key];
		}
	}
	params['_menu']='manual';
	return wacss.ajaxGet('/php/admin.php',div,params);
}

