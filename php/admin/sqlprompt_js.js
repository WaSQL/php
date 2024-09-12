function sqlpromptShowLinks(offset,limit,total,qtime){
	if(undefined==offset || isNaN(offset) || offset.length==0){
		setText('results_count','');
		return false;
	}
	//console.log(new Array(offset,limit,total,qtime));
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
	// console.log(e.keyCode);
	// console.log(e.ctrlKey)
	//keycodes: F8=119, CTRL-ENTER=10
    if (e.keyCode == 119) {
		return sqlpromptSubmit(document.sqlprompt);
    }
    else if (e.ctrlKey && e.keyCode === 13) {
    	//CTRL+ENTER
    	return sqlpromptSubmit(document.sqlprompt);
    }
    else if (e.ctrlKey && e.keyCode === 69) {
    	//CTRL+e
    	return sqlpromptSubmit(document.sqlprompt);
    }
    else if (e.ctrlKey && e.keyCode === 88) {
    	//CTRL+x - generate an explain plan
    	return sqlpromptExplainPlan();
    }
    else{
    	//console.log('Keycode:'+e.keyCode);
    }
}
function sqlpromptExplainPlan(){
	document.sqlprompt.func.value='explain';
	sqlpromptSubmit(document.sqlprompt);
	document.sqlprompt.func.value='sql';
	return false;
}
function sqlpromptSetSha(sha,cnt){
	document.sqlprompt.sql_sha.value=sha;
	document.sqlprompt.sql_cnt.value=cnt;
}
function sqlpromptSetDB(db,schema){
	document.sqlprompt.db.value=db;
	let params={_menu:'sqlprompt',func:'setdb',db:db};
	if(undefined != schema && schema.length > 0){
		document.sqlprompt.schema.value=schema;
		params.schema=schema;
	}
	else{document.sqlprompt.schema.value='';}
	return ajaxGet('/php/admin.php','table_fields',params)
}
function sqlpromptSetValue(v){
	let el=document.getElementById('sql_full');
	if(undefined != el.codemirror){
		console.log('setValue');
		el.codemirror.setValue(v);
		return false;
	}
	//console.log(v);
	document.sqlprompt.sql_select.value='';
	let sql=v;
	let doc = new DOMParser().parseFromString(sql, "text/html");
	sql=doc.documentElement.innerText;
	let obj=getObject('sql_full');
	if(undefined != obj.editor){
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
function sqlpromptLoadPrompt(){
	let db=document.sqlprompt.db.value;
	let schema=document.sqlprompt.schema.value;
	let params={_menu:'sqlprompt',func:'load_prompt',db:db,setprocessing:0};
	if(schema.length){
		params.schema=document.sqlprompt.schema.value;
	}
	return ajaxGet('/php/admin.php','nulldiv',params)
}
function sqlpromptMonitor(type){
	let db=document.sqlprompt.db.value;
	let schema=document.sqlprompt.schema.value;
	document.sqlprompt.reset();
	document.sqlprompt.db.value=db;
	let div='nulldiv';
	let params={_menu:'sqlprompt',func:'monitor',db:db,type:type,setprocessing:0};
	if(schema.length){
		params.schema=document.sqlprompt.schema.value;
		document.sqlprompt.schema.value=schema;
	}
	if(type.toLowerCase()=='optimizations'){
		div='sqlprompt_results';
		params.setprocessing=div;
	}
	return ajaxGet('/php/admin.php',div,params)
}
function sqlpromptMonitorSQL(norun){
	if(undefined==norun){norun=0;}
	let sql=getText('monitor_sql_query');
	let doc = new DOMParser().parseFromString(sql, "text/html");
	sql=doc.documentElement.innerText;
	document.sqlprompt.sql_select.value='';
	let obj=document.getElementById('sql_full');
	if(undefined != obj.codemirror){
		obj.codemirror.setValue(sql);
		obj.codemirror.save();
		if(norun==0){
			sqlpromptSubmit(document.sqlprompt);
		}
		
	}
	else if(undefined != obj.editor){
		setText(obj.editor,'');
		setText(obj.editor,sql);
		obj.editor.save();
		if(norun==0){
			sqlpromptSubmit(document.sqlprompt);
		}
	}
	else{
		setText('sql_full','');
		setText('sql_full',sql);
		if(norun==0){
			sqlpromptSubmit(document.sqlprompt);
		}
	}
	return false;
}
function sqlpromptLastRecords(table){
	let db=document.sqlprompt.db.value;
	let schema=document.sqlprompt.schema.value;
	document.sqlprompt.reset();
	document.sqlprompt.db.value=db;
	let params={_menu:'sqlprompt',func:'last_records',db:db,table:table,setprocessing:0}
	if(schema.length){
		params.schema=document.sqlprompt.schema.value;
		document.sqlprompt.schema.value=schema;
	}
	return ajaxGet('/php/admin.php','nulldiv',params)
}
function sqlpromptListRecords(table){
	let db=document.sqlprompt.db.value;
	let schema=document.sqlprompt.schema.value;
	document.sqlprompt.reset();
	document.sqlprompt.db.value=db;
	let params={_menu:'sqlprompt',func:'list_records',db:db,table:table,setprocessing:0};
	if(schema.length){
		params.schema=document.sqlprompt.schema.value;
		document.sqlprompt.schema.value=schema;
	}
	return ajaxGet('/php/admin.php','sqlprompt_results',params)
}
function sqlpromptCountRecords(table){
	let db=document.sqlprompt.db.value;
	let schema=document.sqlprompt.schema.value;
	document.sqlprompt.reset();
	document.sqlprompt.db.value=db;
	let params={_menu:'sqlprompt',func:'count_records',db:db,table:table,setprocessing:0};
	if(schema.length){
		params.schema=document.sqlprompt.schema.value;
		document.sqlprompt.schema.value=schema;
	}
	return ajaxGet('/php/admin.php','nulldiv',params)
}
function sqlpromptDDL(table){
	let db=document.sqlprompt.db.value;
	let schema=document.sqlprompt.schema.value;
	document.sqlprompt.reset();
	document.sqlprompt.db.value=db;
	let params={_menu:'sqlprompt',func:'ddl',db:db,table:table,setprocessing:0};
	if(schema.length){
		params.schema=document.sqlprompt.schema.value;
		document.sqlprompt.schema.value=schema;
	}
	return ajaxGet('/php/admin.php','nulldiv',params)
}
function sqlpromptFields(table){
	let icon=getObject(table+'_icon');
	let t=getText(table+'_fields');
	if(t.length){
		icon.className='icon-square-plus';
		setText(table+'_fields','');
		return;
	}
	let params={_menu:'sqlprompt',func:'fields',table:table,db:document.sqlprompt.db.value};
	if(document.sqlprompt.schema.value.length){
		params.schema=document.sqlprompt.schema.value;
	}
	icon.className='icon-square-minus';
	return ajaxGet('/php/admin.php',table+'_fields',params)
}
function sqlpromptExecute(args){
	return sqlpromptSubmit(document.sqlprompt);
}
function sqlpromptSubmit(frm){
	let obj=getObject('sql_full');
	if(undefined != obj.codemirror){
		obj.codemirror.save();
		let str=obj.codemirror.getSelection();
		if(str.length){
			//console.log('section selected: length:'+str.length);
			//console.log(str);
			frm.sql_select.value=str;
			return ajaxSubmitForm(frm,'sqlprompt_results');
		}
		frm.sql_select.value='';
		return ajaxSubmitForm(frm,'sqlprompt_results');
	}
	else if(undefined != obj.editor){
		//store editor_content
		frm.editor_content.value=obj.editor.innerHTML;
		//console.log(frm.sql_full);
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
			//console.log('section selected: length:'+str.length);
			//console.log(str);
			frm.sql_select.value=str;
			return ajaxSubmitForm(frm,'sqlprompt_results');
		}
		frm.sql_select.value='';
		return ajaxSubmitForm(frm,'sqlprompt_results');
	}
	return false;
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
document.onkeydown=sqlpromptCheckKey;
