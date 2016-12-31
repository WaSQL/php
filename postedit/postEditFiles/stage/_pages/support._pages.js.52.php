function pageNewTicket(){
	return ajaxGet('/t/1/support/new','results');
}
function faqSearchList(str){
	if(undefined==str){str='';}
	str=str.toLowerCase();
	var list=document.querySelectorAll('div.faq_qna');
	var cnt=0;
	for(var i=0;i<list.length;i++){
		if(!str.length){
    		list[i].style.maxHeight='1px';
    		list[i].style.overflow='hidden';
    		continue;
		}
		var show=0;
		var val=list[i].getAttribute('data-category');
		val=val.toLowerCase();
		if('_'+val == str){show=1;}
		val=list[i].getAttribute('data-subcategory');
		val=val.toLowerCase();
		if('_'+val ==str){show=1;}
		val=list[i].querySelector('div.question').innerText;
		val=val.toLowerCase();
		if(val.indexOf(str)!=-1){show=1;}
		val=list[i].querySelector('div.answer').innerText;
		val=val.toLowerCase();
		if(val.indexOf(str)!=-1){show=1;}
		if(show==1){
        	list[i].style.maxHeight='inherit';
        	list[i].style.overflow='auto';
		}
		else{
			list[i].style.maxHeight='1px';
    		list[i].style.overflow='hidden';
		}
	}
	return false;
}
