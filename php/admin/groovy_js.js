/* Groovy Manager (php/admin/groovy_*) - refresh, auto-refresh and log scrolling */

/**
* @describe reloads the active tab (Status, Stats or Log). The Log tab keeps its filter and line-count choices
* @param {boolean} showProcessing - true to show the processing spinner (manual refresh); auto-refresh passes false so the panel does not flicker
* @return {boolean} false, so it can be used directly in onclick/onchange
*/
function groovyRefresh(showProcessing){
	let content=document.getElementById('groovy_content');
	if(!content){return false;}
	let active=document.querySelector('#groovy_tabs li.is-active a');
	let func=(active && active.dataset.func) ? active.dataset.func : 'status';
	let params={_menu:'groovy',func:func};
	if(!showProcessing){params.setprocessing=0;}
	if(func=='log'){
		let filter=document.getElementById('groovy_log_filter');
		let lines=document.getElementById('groovy_log_lines');
		if(filter){params.filter=filter.value;}
		if(lines){params.lines=lines.value;}
	}
	wacss.ajaxGet('/php/admin.php','groovy_content',params);
	return false;
}

/**
* @describe turns the 10-second auto-refresh on or off. The timer stops itself once the page is left
* @param {object} cb - the auto-refresh checkbox
* @return {boolean} true
*/
function groovyAutoRefresh(cb){
	if(window.groovyRefreshTimer){
		clearInterval(window.groovyRefreshTimer);
		window.groovyRefreshTimer=null;
	}
	if(!cb.checked){return true;}
	window.groovyRefreshTimer=setInterval(function(){
		//stop when the Groovy Manager page is gone (navigated elsewhere in the admin)
		if(!document.getElementById('groovy_content')){
			clearInterval(window.groovyRefreshTimer);
			window.groovyRefreshTimer=null;
			return;
		}
		//do not refresh while a stack trace is open - it would collapse under the reader
		if(document.querySelector('#groovy_content details[open]')){return;}
		groovyRefresh(false);
	},10000);
	return true;
}

/**
* @describe scrolls the log view to the newest line (runs from the log panel's data-onload)
* @return {boolean} true
*/
function groovyLogScroll(){
	let pre=document.getElementById('groovy_log');
	if(pre){pre.scrollTop=pre.scrollHeight;}
	return true;
}
