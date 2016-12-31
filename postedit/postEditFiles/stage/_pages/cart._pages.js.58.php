function cartUpdatePrices(){
	var plan=document.querySelector('#plan');
	var planm=parseInt(plan.getAttribute('data-m'));
	var plany=parseInt(plan.getAttribute('data-y'));
	var pkgs=document.querySelectorAll('[data-group="cart_package_group"]:checked');
	var mp=planm*pkgs.length;
	var yp=plany*pkgs.length;
	var sp=(mp*12)-yp;
	setText('yearly',yp+'/year');
	setText('monthly',mp+'/month');
	setText('savings','Two Months Free-Save $'+sp+' per year');
}
function cartRedrawState(obj){
	var country=obj.value;
	return ajaxGet('/t/1/cart/redraw/state','statediv',{country:country,setprocessing:0});
}
function cartCheckout(){
	document.getElementById('formfields').className="col-sm-12 open";
	document.getElementById('cartCheckout').style.display='none';
	return false;
}
