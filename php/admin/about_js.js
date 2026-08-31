//========================================================================
// About WaSQL dashboard - client-side helpers
//   Loaded once on full page load via wacss.loadScript(). The tab partials
//   are inserted by wacss.nav and only reference these globals.
//========================================================================

/**
* @exclude - internal admin helper
* filters key/value table rows by a plain substring match and updates the count.
*/
function aboutFilter(input,tableId){
	var q=(input.value||'').toLowerCase().trim();
	var rows=document.querySelectorAll('#'+tableId+' tbody tr');
	var shown=0;
	for(var i=0;i<rows.length;i++){
		var hay=(rows[i].textContent||'').toLowerCase();
		var match=(q==='' || hay.indexOf(q)!==-1);
		rows[i].style.display=match?'':'none';
		if(match){shown++;}
	}
	var c=document.getElementById(tableId+'_count');
	if(c){c.textContent=shown;}
	return true;
}

/**
* @exclude - internal admin helper
* filters a chip cloud (loaded PHP extensions) by substring.
*/
function aboutFilterChips(input,containerId){
	var q=(input.value||'').toLowerCase().trim();
	var chips=document.querySelectorAll('#'+containerId+' .about-chip');
	var shown=0;
	for(var i=0;i<chips.length;i++){
		var hay=(chips[i].textContent||'').toLowerCase();
		var match=(q==='' || hay.indexOf(q)!==-1);
		chips[i].style.display=match?'':'none';
		if(match){shown++;}
	}
	var c=document.getElementById(containerId+'_count');
	if(c){c.textContent=shown;}
	return true;
}

/**
* @exclude - internal admin helper
* copies a single value to the clipboard from a row's copy button.
*/
function aboutCopy(btn){
	var v=btn.getAttribute('data-copy')||'';
	return wacss.copy2Clipboard(v,'Copied');
}

/**
* @exclude - internal admin helper
* copies every currently-visible row as tab-separated name/value lines.
*/
function aboutCopyRows(tableId){
	var rows=document.querySelectorAll('#'+tableId+' tbody tr');
	var out=[];
	for(var i=0;i<rows.length;i++){
		if(rows[i].style.display==='none'){continue;}
		var name=rows[i].getAttribute('data-name')||'';
		var val=rows[i].getAttribute('data-value')||'';
		out.push(name+'\t'+val);
	}
	return wacss.copy2Clipboard(out.join('\n'),'Copied '+out.length+' row(s)');
}
