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
function sqlpromptSetValue(v){
	console.log(v);
	document.sqlprompt.sql_select.value='';
	let sql=v;
	let doc = new DOMParser().parseFromString(sql, "text/html");
	sql=doc.documentElement.textContent;
	let obj=getObject('sql_full');
	if(undefined != obj.codemirror){
		obj.codemirror.getDoc().setValue(sql);
		obj.codemirror.save();
	}
	else if(undefined != obj.editor){
		setText(obj.editor,'');
		setText(obj.editor,v);
		obj.editor.save();
	}
	else{
		setText('sql_full','');
		setText('sql_full',sql);
	}
	return false;
}
function sqlpromptLoadPrompt(db){
	document.sqlprompt.db.value=db;
	return ajaxGet('/php/admin.php','nulldiv',{_menu:'sqlprompt',func:'load_prompt',db:db,setprocessing:0})
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
function sqlpromptMonitorSQL(){
	document.sqlprompt.sql_select.value='';
	let sql=getText('monitor_sql_query');
	let doc = new DOMParser().parseFromString(sql, "text/html");
	sql=doc.documentElement.textContent;
	let obj=getObject('sql_full');
	if(undefined != obj.codemirror){
		obj.codemirror.getDoc().setValue(sql);
		obj.codemirror.save();
		sqlpromptSubmit(document.sqlprompt);
	}
	else if(undefined != obj.editor){
		setText(obj.editor,'');
		setText(obj.editor,sql);
		obj.editor.save();
		sqlpromptSubmit(document.sqlprompt);
	}
	else{
		setText('sql_full','');
		setText('sql_full',sql);
		sqlpromptSubmit(document.sqlprompt);
	}
	return false;
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
	let obj=getObject('sql_full');
	if(undefined != obj.codemirror){
		//if the user has selected a section, run just the selection
		let str=obj.codemirror.getSelection();
		if(str.length){
			frm.sql_select.value=str;
			return ajaxSubmitForm(frm,'sqlprompt_results');
		}
		frm.sql_select.value='';
		return ajaxSubmitForm(frm,'sqlprompt_results');
	}
	else if(undefined != obj.editor){
		//store editor_content
		frm.editor_content.value=obj.editor.innerHTML;
		
		//if the user has selected a section, run just the selection
		let str='';
		if (window.getSelection) {
	        str = window.getSelection().toString();
	    } else if (document.getSelection) {
	        str = document.getSelection().toString();
	    } else if (document.selection) {
	        str = document.selection.createRange().text;
	    }
		if(str.length){
			frm.sql_select.value=str;
			return ajaxSubmitForm(frm,'sqlprompt_results');
		}
		frm.sql_select.value='';
		return ajaxSubmitForm(frm,'sqlprompt_results');
	}
	//no longer used now that we are using codemirror
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
let db=document.querySelector('#nulldiv[data-db]').dataset.db;
sqlpromptLoadPrompt(db);