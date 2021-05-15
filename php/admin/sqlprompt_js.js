function sqlpromptCheckKey(e){
	e = e || window.event;
	//console.log(e.keyCode);
    if (e.keyCode == 119) {
		return sqlpromptSubmit(document.sqlprompt);
    }
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
	var pos=getCursorPos(frm.sql_full);
	if(undefined != pos.start){
		frm.cursor_pos.value=pos.start;
	}
	return ajaxSubmitForm(frm,'sqlprompt_results');
}
function sqlpromptExport(){
	document.sqlprompt.func.value='export';
	document.sqlprompt.submit();
	document.sqlprompt.func.value='sql';
	return false;
}
document.onkeydown = sqlpromptCheckKey;