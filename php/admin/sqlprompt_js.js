function sqlpromptShowLinks(offset,limit,total,qtime){
	offset=parseInt(offset);
	limit=parseInt(limit);
	total=parseInt(total);
	let a=offset+1;
	let b=a+limit-1;
	let c=total;
	if(b > c){b=c;}
	let abc=a+' to '+b+' of '+c;
	
	document.getElementById('export_link').style.display='inline-block';
	//prev
	let prev=document.getElementById('prev_link');
	let prev_offset=offset-limit;
	if(prev_offset < 0){prev_offset=0;}
	prev.dataset.offset=prev_offset;
	if(offset > 0){
		prev.style.display='inline-block';	
	}
	else{
		prev.style.display='none';
	}
	//next
	let next=document.getElementById('next_link');
	let next_offset=offset+limit;
	if(next_offset > total){next_offset=total;}
	next.dataset.offset=next_offset;
	if(next_offset < total){
		next.style.display='inline-block';	
	}
	else{
		next.style.display='none';
	}
	setText('results_count',abc+'<br> Took: '+qtime);
}
function sqlpromptCheckKey(e){
	e = e || window.event;
	//console.log(e.keyCode);
	//keycodes: F8=119, CTRL-ENTER=10
    if (e.keyCode == 119) {
		return sqlpromptSubmit(document.sqlprompt);
    }
    else if (undefined != this.previousCode && e.keyCode == 13 && this.previousCode==17) {
    	//CTRL+ENTER
    	return sqlpromptSubmit(document.sqlprompt);
    }
    else if (undefined != this.previousCode && e.keyCode == 69 && this.previousCode==17) {
    	//CTRL+e
    	return sqlpromptSubmit(document.sqlprompt);
    }
    else{
    	//console.log('Keycode:'+e.keyCode);
    }
    this.previousCode=e.keyCode;
}
function sqlpromptSetDB(db){
	document.sqlprompt.db.value=db;
	return ajaxGet('/php/admin.php','table_fields',{_menu:'sqlprompt',func:'setdb',db:db})
}
function sqlpromptMonitor(type){
	var db=document.sqlprompt.db.value;
	document.sqlprompt.reset();
	document.sqlprompt.db.value=db;
	let div='nulldiv';
	let params={_menu:'sqlprompt',func:'monitor',db:db,type:type,setprocessing:0};
	if(type.toLowerCase()=='optimizations'){
		div='sqlprompt_results';
		params.setprocessing=div;
	}
	return ajaxGet('/php/admin.php',div,params)
}
function sqlpromptLastRecords(table){
	var db=document.sqlprompt.db.value;
	document.sqlprompt.reset();
	document.sqlprompt.db.value=db;
	return ajaxGet('/php/admin.php','nulldiv',{_menu:'sqlprompt',func:'last_records',db:db,table:table,setprocessing:0})
}
function sqlpromptListRecords(table){
	var db=document.sqlprompt.db.value;
	document.sqlprompt.reset();
	document.sqlprompt.db.value=db;
	return ajaxGet('/php/admin.php','sqlprompt_results',{_menu:'sqlprompt',func:'list_records',db:db,table:table,setprocessing:0})
}
function sqlpromptCountRecords(table){
	var db=document.sqlprompt.db.value;
	document.sqlprompt.reset();
	document.sqlprompt.db.value=db;
	return ajaxGet('/php/admin.php','nulldiv',{_menu:'sqlprompt',func:'count_records',db:db,table:table,setprocessing:0})
}
function sqlpromptDDL(table){
	var db=document.sqlprompt.db.value;
	document.sqlprompt.reset();
	document.sqlprompt.db.value=db;
	return ajaxGet('/php/admin.php','centerpop',{_menu:'sqlprompt',func:'ddl',db:db,table:table,setprocessing:0})
}
function sqlpromptFields(table){
	let icon=getObject(table+'_icon');
	let t=getText(table+'_fields');
	if(t.length){
		icon.className='icon-square-plus';
		setText(table+'_fields','');
		return;
	}
	var db=document.sqlprompt.db.value;
	icon.className='icon-square-minus';
	return ajaxGet('/php/admin.php',table+'_fields',{_menu:'sqlprompt',func:'fields',table:table,db:db})
}
function sqlpromptSubmit(frm){
	frm.sql_select.value=getSelText(frm.sql_full);
	frm.offset.value=0;
	var pos=getCursorPos(frm.sql_full);
	if(undefined != pos.start){
		frm.cursor_pos.value=pos.start;
	}
	return ajaxSubmitForm(frm,'sqlprompt_results');
}
function sqlpromptExport(){
	document.sqlprompt.func.value='export';
	document.sqlprompt.offset.value=0;
	document.sqlprompt.submit();
	document.sqlprompt.func.value='sql';
	return false;
}
function sqlpromptPaginate(offset){
	document.sqlprompt.func.value='paginate';
	document.sqlprompt.offset.value=offset;
	ajaxSubmitForm(document.sqlprompt,'sqlprompt_results');
	document.sqlprompt.func.value='sql';
	document.sqlprompt.offset.value=0;
	return false;
}
document.onkeydown = sqlpromptCheckKey;