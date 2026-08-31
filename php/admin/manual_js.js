function manualNav(el) {
	let div='results';
	if(undefined != el.dataset.div){
		div=el.dataset.div;
	}
	if(div != 'results'){
		let content=getText(div);
		if(content.length > 5){
			setText(div,'');
			return false;
		}
	}
	let params={setprocessing:0};
	for(key in el.dataset){
		// Check hasOwnProperty to prevent prototype pollution
		if(el.dataset.hasOwnProperty(key)){
			params[key]=el.dataset[key];
		}
	}
	params['_menu']='manual';
	return wacss.ajaxGet('/php/admin.php',div,params);
}

// Rebuild the documentation index (re-parses every framework code file). The
// server response (view:flash) fires manualRebuildDone() via buildOnLoad.
function manualRebuild(el) {
	if(el.getAttribute('data-busy')=='1'){return false;}
	if(!confirm('Rebuild the documentation index?\n\nThis re-parses every framework code file and can take a minute or two.')){return false;}
	el.setAttribute('data-busy','1');
	el.setAttribute('data-label',el.innerHTML);
	el.setAttribute('disabled','disabled');
	el.innerHTML='<span class="icon-spin4 w_spin"></span> Rebuilding…';
	// safety net: re-enable the button if the response never lands
	window.manualRebuildTimer=setTimeout(function(){manualRebuildReset(el);},180000);
	return wacss.ajaxGet('/php/admin.php','results',{_menu:'manual',func:'rebuild',confirm:'yes'});
}

function manualRebuildReset(el) {
	if(undefined==el){el=document.getElementById('manual_rebuild_btn');}
	if(undefined==el||null==el){return;}
	if(el.getAttribute('data-label')){el.innerHTML=el.getAttribute('data-label');}
	el.removeAttribute('disabled');
	el.removeAttribute('data-busy');
}

function manualRebuildDone() {
	if(undefined != window.manualRebuildTimer){clearTimeout(window.manualRebuildTimer);}
	manualRebuildReset();
	if(undefined != wacss.toast){wacss.toast('Documentation index rebuilt');}
	setTimeout(function(){window.location.reload();},1100);
}
