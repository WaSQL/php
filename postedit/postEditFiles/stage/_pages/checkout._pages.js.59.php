function templateDeleteCartItem(sku){
	ajaxGet('/checkout','cart_contents',{_template:1,func:'delete',sku:sku});
	return false;
}
function pageApplyCoupon(){
	var coupon=trim(getText('coupon_code'));
	if(coupon.length==0){return false;}
	if(undefined!=document.cartform.billtoemail){email=document.cartform.billtoemail.value;}
	else{email=document.cartform.shiptoemail.value;}
	return ajaxGet(document.location.href,'cart_table','_template=1&func=applycoupon&coupon='+coupon+'&email='+email);
}
function pageApplyGiftcard(code){
	var code=trim(getText('giftcard_code'));
	if(code.length==0){return false;}
	var email='';
	if(undefined!=document.cartform.billtoemail){email=document.cartform.billtoemail.value;}
	else{email=document.cartform.shiptoemail.value;}
	return ajaxGet(document.location.href,'cart_table','_template=1&func=applygiftcard&giftcard='+code+'&email='+email);
}
function shiptoStateChanged(){
	if(undefined == document.cartform.shiptostate){return false;}
	if(document.cartform.shiptostate.value.length==0){return false;}
	return ajaxGet(document.location.href,'cart_table','_template=1&func=shiptostate&shiptostate='+document.cartform.shiptostate.value+'&shiptozipcode='+document.cartform.shiptozipcode.value);
}
function pageUpdateCart(){
	ajaxGet('/products','cart',{_template:1,func:'cartview'});
	return false;
}
function pageSameAsShipping(ck){
	var theForm=document.cartform;
	for(var i=0;i<theForm.length;i++){
    	if(theForm[i].name.indexOf('shipto')==0){
        	var billto=theForm[i].name.replace('shipto','billto');
        	var cobj=getObject(billto);
        	if(undefined != cobj){
				if(ck){
					var cval=getText(theForm[i]);
					cobj.value=cval;
				}
				else{
                	cobj.value='';
				}
			}
		}
	}
	return true;
}
function useUSPSAddress(){
	document.cartform.usps_verified.value=1;
	document.cartform.shiptoaddress1.value=getText('usps_Address2');
	document.cartform.shiptoaddress2.value=getText('usps_Address1');
	document.cartform.shiptocity.value=getText('usps_City');
	document.cartform.shiptostate.value=getText('usps_State');
	document.cartform.shiptozipcode.value=getText('usps_Zip5');
	ajaxSubmitForm(document.cartform, 'centerpop');
	return false;
}
