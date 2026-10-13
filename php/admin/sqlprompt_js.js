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
    else if (e.ctrlKey && e.keyCode === 73) {
    	//CTRL+i - generate an explain plan
    	return sqlpromptExplainPlan();
    }
    else if (e.keyCode == 120) {
    	//F9 - generate an explain plan
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
	let params={_menu:'sqlprompt',func:'setdb',db:db,setprocessing:'tables_fields_processing'};
	if(undefined != schema && schema.length > 0){
		document.sqlprompt.schema.value=schema;
		params.schema=schema;
	}
	else{document.sqlprompt.schema.value='';}
	return wacss.ajaxGet('/php/admin.php','table_fields',params)
}
function sqlpromptShowHistory(){
	let params={_menu:'sqlprompt',func:'show_history',db:document.sqlprompt.db.value,schema:document.sqlprompt.schema.value};
	params.title='User History: '+params.db;
	return wacss.ajaxGet('/php/admin.php','centerpop',params)
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
	//v is PLAIN text: assign it as text, never as innerHTML. setText() on the
	//contenteditable used innerHTML, so a "<b>" inside a string literal was parsed
	//as markup and silently dropped from the query
	if(undefined != el.editor){
		el.editor.textContent=v;
		el.editor.save();
	}
	else{
		el.value=v;
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
	return wacss.ajaxGet('/php/admin.php','nulldiv',params)
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
	return wacss.ajaxGet('/php/admin.php',div,params)
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
	return wacss.ajaxGet('/php/admin.php','nulldiv',params)
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
	return wacss.ajaxGet('/php/admin.php','sqlprompt_results',params)
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
	return wacss.ajaxGet('/php/admin.php','nulldiv',params)
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
	return wacss.ajaxGet('/php/admin.php','nulldiv',params)
}
function sqlpromptDescTable(table){
	let db=document.sqlprompt.db.value;
	let schema=document.sqlprompt.schema.value;
	document.sqlprompt.reset();
	document.sqlprompt.db.value=db;
	let params={_menu:'sqlprompt',func:'desc',db:db,table:table,setprocessing:0};
	if(schema.length){
		params.schema=document.sqlprompt.schema.value;
		document.sqlprompt.schema.value=schema;
	}
	return wacss.ajaxGet('/php/admin.php','nulldiv',params)
}
function sqlpromptFields(table){
	let icon=getObject(table+'_icon');
	let div=table.replace(/[^0-9a-z\_]/gi, '')+'_fields';
	let t=getText(div);
	if(t.length){
		icon.className='icon-square-plus';
		setText(div,'');
		return;
	}
	let params={_menu:'sqlprompt',func:'fields',table:table,db:document.sqlprompt.db.value};
	if(document.sqlprompt.schema.value.length){
		params.schema=document.sqlprompt.schema.value;
	}
	icon.className='icon-square-minus';
	
	return wacss.ajaxGet('/php/admin.php',div,params)
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
			return wacss.ajaxPost(frm,'sqlprompt_results');
		}
		frm.sql_select.value='';
		return wacss.ajaxPost(frm,'sqlprompt_results');
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
			return wacss.ajaxPost(frm,'sqlprompt_results');
		}
		frm.sql_select.value='';
		return wacss.ajaxPost(frm,'sqlprompt_results');
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
	wacss.ajaxPost(document.sqlprompt,'sqlprompt_results');
	document.sqlprompt.func.value='sql';
	document.sqlprompt.offset.value=0;
	return false;
}
/**
* @name sqlpromptFormatSQL
* @describe reformats the SQL in the editor: indents clauses, uppercases keywords/functions, lowercases identifiers
* @return boolean false
* @usage onclick="return sqlpromptFormatSQL();"
*/
var sqlpromptFormatSQL=(function(){
	var KEYWORDS=new Set((
		'SELECT FROM WHERE GROUP BY ORDER HAVING LIMIT OFFSET '+
		'INSERT INTO VALUES UPDATE SET DELETE REPLACE '+
		'JOIN INNER LEFT RIGHT FULL OUTER CROSS ON USING '+
		'AND OR NOT IN IS NULL LIKE BETWEEN EXISTS ALL ANY SOME '+
		'UNION EXCEPT INTERSECT DISTINCT AS '+
		'CASE WHEN THEN ELSE END '+
		'CREATE TABLE VIEW INDEX DROP ALTER ADD COLUMN PRIMARY KEY FOREIGN REFERENCES '+
		'DEFAULT UNIQUE CONSTRAINT CASCADE '+
		'ASC DESC '+
		'BEGIN COMMIT ROLLBACK TRANSACTION '+
		'WITH RECURSIVE '+
		'TRUE FALSE'
	).split(/\s+/));
	var FUNCTION_HINT=new Set((
		'COUNT SUM AVG MIN MAX NOW DATE_FORMAT CONCAT CONCAT_WS IFNULL COALESCE '+
		'CAST CONVERT SUBSTRING SUBSTR LENGTH TRIM LTRIM RTRIM UPPER LOWER '+
		'ROUND FLOOR CEIL CEILING ABS IF GROUP_CONCAT DATEDIFF DATE_ADD DATE_SUB '+
		'CURDATE CURTIME UNIX_TIMESTAMP FROM_UNIXTIME YEAR MONTH DAY HOUR MINUTE SECOND '+
		'REPLACE LOCATE INSTR LEFT RIGHT REVERSE REPEAT LPAD RPAD FORMAT'
	).split(/\s+/));
	var CLAUSE_START=new Set(['SELECT','FROM','WHERE','GROUP BY','ORDER BY','HAVING','LIMIT',
		'INSERT INTO','VALUES','UPDATE','SET','DELETE FROM','UNION','UNION ALL','EXCEPT','INTERSECT',
		'JOIN','INNER JOIN','LEFT JOIN','RIGHT JOIN','FULL JOIN','FULL OUTER JOIN','LEFT OUTER JOIN',
		'RIGHT OUTER JOIN','CROSS JOIN']);
	//clauses whose comma-separated items each get their own indented line. a single-item
	//clause has no top-level comma to break on, so "GROUP BY 1" stays on one line while
	//"GROUP BY a, b" breaks
	var BREAK_LIST_CLAUSES=new Set(['SELECT','SET','VALUES','INSERT INTO','GROUP BY','ORDER BY']);
	//...except these two, which are pulled back onto one line when the whole clause fits
	var SOFT_BREAK_CLAUSES=/^(GROUP BY|ORDER BY)\b/;
	var MAX_INLINE_CLAUSE=100;
	//a word right after one of these names a table, never a function - "insert into t(a,b)"
	var TABLE_CONTEXT=new Set(['FROM','INTO','JOIN','UPDATE','TABLE','VIEW']);
	//operators that hug what is on either side of them
	var TIGHT_OPERATORS=new Set(['.','::','->','->>']);

	//strips the leading/trailing spaces and tabs off every line so pasted indentation
	//(and the &nbsp; the contenteditable editor leaves behind) can never leak into the output
	function stripIndent(sql){
		return sql.replace(/\u00a0/g,' ').replace(/\r\n?/g,'\n').split('\n').map(function(line){
			return line.replace(/^[ \t]+/,'').replace(/[ \t]+$/,'');
		}).join('\n').trim();
	}

	//pulls a broken-up GROUP BY / ORDER BY back onto a single line when the joined clause
	//stays under MAX_INLINE_CLAUSE characters, so "ORDER BY 1, 2" does not get split
	function rejoinShortClauses(text){
		var lines=text.split('\n');
		var kept=[];
		for(var i=0;i<lines.length;i++){
			if(!SOFT_BREAK_CLAUSES.test(lines[i])){kept.push(lines[i]);continue;}
			//the clause's items are the indented lines that follow, each hanging off a comma
			var joined=lines[i];
			var last=i;
			while(last+1<lines.length && /,$/.test(lines[last]) && /^\t/.test(lines[last+1])){
				last++;
				joined+=' '+lines[last].replace(/^\t+/,'');
			}
			if(last>i && joined.length<MAX_INLINE_CLAUSE){
				kept.push(joined);
				i=last;
			}
			else{kept.push(lines[i]);}
		}
		return kept.join('\n');
	}

	function tokenize(sql){
		//NOTE: strings allow both doubled ('') and backslash escapes; multi-char operators
		//(!=, <>, >=, ||, ::, ->>) must match as ONE token or they get split by a space
		var re=/(--[^\n]*)|(\/\*[\s\S]*?\*\/)|('(?:''|\\.|[^'\\])*')|("(?:""|\\.|[^"\\])*")|(`(?:``|[^`])*`)|([A-Za-z_][A-Za-z0-9_$]*)|([0-9]+\.?[0-9]*)|(<=>|<>|!=|!~\*|!~|<=|>=|\|\||&&|::|->>|->|:=|=)|(\s+)|(.)/g;
		var tokens=[],m;
		while((m=re.exec(sql))!==null){
			if(m[1]!==undefined){tokens.push({type:'comment',v:m[1]});}
			else if(m[2]!==undefined){tokens.push({type:'comment',v:m[2]});}
			else if(m[3]!==undefined){tokens.push({type:'string',v:m[3]});}
			else if(m[4]!==undefined){tokens.push({type:'string',v:m[4]});}
			else if(m[5]!==undefined){tokens.push({type:'ident_q',v:m[5]});}
			else if(m[6]!==undefined){tokens.push({type:'word',v:m[6]});}
			else if(m[7]!==undefined){tokens.push({type:'number',v:m[7]});}
			else if(m[8]!==undefined){tokens.push({type:'punct',v:m[8]});}
			else if(m[9]!==undefined){tokens.push({type:'space',v:m[9]});}
			else{tokens.push({type:'punct',v:m[10]});}
		}
		return tokens;
	}

	function formatSQL(sql){
		sql=stripIndent(sql);
		if(!sql.length){return sql;}
		//drop the whitespace tokens, but remember where they were: "count(*)" is a function
		//call, "insert into t (a,b)" is a table followed by a column list
		var tokens=[];
		tokenize(sql).forEach(function(t){
			if(t.type==='space'){
				if(tokens.length){tokens[tokens.length-1].spaceAfter=true;}
				return;
			}
			tokens.push(t);
		});

		//pass 1: case-normalize words (keywords upper, functions upper, ALL-CAPS identifiers lower)
		for(var i=0;i<tokens.length;i++){
			var t=tokens[i];
			if(t.type!=='word'){continue;}
			var upper=t.v.toUpperCase();
			//a word glued directly to "(" is a function call - covers DATE, CONVERT_TZ, DATE_TRUNC
			//and every other db-specific function that will never be in the hint list
			var nxt=tokens[i+1];
			var prev=tokens[i-1];
			var isCall=!!(nxt && nxt.type==='punct' && nxt.v==='(' && !t.spaceAfter
				&& !(prev && prev.isKeyword && TABLE_CONTEXT.has(prev.v)));
			if(KEYWORDS.has(upper) && !(isCall && FUNCTION_HINT.has(upper))){
				//keyword wins over call syntax (IN (...), VALUES (...), USING (...))
				//unless it is also a known function - LEFT(, RIGHT(, REPLACE(, IF(
				t.v=upper;
				t.isKeyword=true;
			}
			else if(isCall || FUNCTION_HINT.has(upper)){
				t.v=upper;
				t.isFunc=true;
			}
			else if(t.v===upper){
				//only fold identifiers that were typed in caps. lowercasing a mixed-case
				//identifier (orderDate, MyTable) breaks case-sensitive databases
				t.v=t.v.toLowerCase();
			}
		}

		//pass 2: merge multi-word keyword phrases (GROUP BY, LEFT OUTER JOIN, etc.) into single logical tokens
		var merged=[];
		for(var i2=0;i2<tokens.length;i2++){
			var t2=tokens[i2];
			if(t2.isKeyword){
				var phrase=t2.v;
				var j=i2;
				while(true){
					var n1=tokens[j+1];
					if(n1 && n1.isKeyword){
						var candidate=phrase+' '+n1.v;
						if(CLAUSE_START.has(candidate)
							|| (phrase==='GROUP' && n1.v==='BY')
							|| (phrase==='ORDER' && n1.v==='BY')
							|| (phrase==='INSERT' && n1.v==='INTO')
							|| (phrase==='DELETE' && n1.v==='FROM')
							|| (['UNION','EXCEPT','INTERSECT'].indexOf(phrase)>-1 && n1.v==='ALL')
							|| (['LEFT','RIGHT','FULL','INNER','CROSS'].indexOf(phrase)>-1 && (n1.v==='JOIN'||n1.v==='OUTER'))
							|| (phrase.slice(-5)==='OUTER' && n1.v==='JOIN')
						){
							phrase=candidate;
							j++;
							continue;
						}
					}
					break;
				}
				merged.push({type:'word',v:phrase,isKeyword:true,isClauseStart:CLAUSE_START.has(phrase)});
				i2=j;
			}
			else{
				merged.push(t2);
			}
		}

		//pass 3: render with line breaks + indentation
		var IND='\t';
		var out='';
		var depth=0;
		var currentClause=null; //top-level clause we're currently inside (at depth 0)
		var noSpaceBefore=false;

		function trimTrail(){
			if(/\n[ \t]*$/.test(out)){return;} //preserve indentation just written by newline()
			out=out.replace(/[ \t]+$/,'');
		}
		function newline(level){
			out=out.replace(/[ \t\n]+$/,''); //collapse any trailing whitespace/blank lines to none
			out+='\n'+IND.repeat(Math.max(0,level));
			noSpaceBefore=true;
		}

		for(var k=0;k<merged.length;k++){
			var tk=merged[k];

			if(tk.type==='punct' && tk.v==='('){
				//only a function name, another "(" or a tight operator hugs the paren.
				//keywords and table names keep their space: IN (1,2), insert into t (a,b)
				var pv=merged[k-1];
				var hug=!!(pv && (pv.isFunc || (pv.type==='punct' && (pv.v==='(' || TIGHT_OPERATORS.has(pv.v)))));
				if(hug || noSpaceBefore){trimTrail();}
				else if(out.length){out+=' ';}
				out+='(';
				depth++;
				noSpaceBefore=true;
				continue;
			}
			if(tk.type==='punct' && tk.v===')'){
				depth=Math.max(0,depth-1);
				trimTrail();
				out+=')';
				noSpaceBefore=false;
				continue;
			}
			if(tk.type==='comment'){
				if(!noSpaceBefore && out.length){out+=' ';}
				out+=tk.v;
				//a line comment (--) eats to end of line, so anything after it must start fresh
				newline(0);
				currentClause=null;
				continue;
			}
			if(tk.type==='punct' && tk.v===';'){
				trimTrail();
				out+=';';
				newline(0);
				currentClause=null;
				continue;
			}
			if(tk.type==='punct' && TIGHT_OPERATORS.has(tk.v)){
				trimTrail();
				out+=tk.v;
				noSpaceBefore=true;
				continue;
			}
			if(tk.type==='punct' && tk.v===','){
				trimTrail();
				out+=',';
				if(depth===0 && BREAK_LIST_CLAUSES.has(currentClause)){
					newline(1);
				}
				else{
					out+=' ';
					noSpaceBefore=true;
				}
				continue;
			}
			if(tk.isKeyword && tk.isClauseStart && depth===0){
				currentClause=tk.v;
				if(out.length){newline(0);}
				else{trimTrail();}
				out+=tk.v;
				noSpaceBefore=false;
				continue;
			}
			if((tk.v==='AND'||tk.v==='OR') && depth===0){
				newline(1);
				out+=tk.v;
				noSpaceBefore=false;
				continue;
			}

			if(!noSpaceBefore && out.length){out+=' ';}
			out+=tk.v;
			noSpaceBefore=false;
		}
		return rejoinShortClauses(out.trim());
	}

	//the button-facing entry point: reads the current editor content (whichever mode is active), formats it, writes it back
	return function sqlpromptFormatSQL(){
		let obj=getObject('sql_full');
		if(undefined != obj.codemirror){
			obj.codemirror.save();
		}
		else if(undefined != obj.editor){
			obj.editor.save();
		}
		let sql=getText('sql_full');
		if(!sql || !sql.replace(/\s+/g,'').length){return false;}
		sqlpromptSetValue(formatSQL(sql));
		return false;
	};
})();
document.onkeydown=sqlpromptCheckKey;
