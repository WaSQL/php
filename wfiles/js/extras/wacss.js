var wacss = {
	version: '1.1',
	author: 'WaSQL.com',
	modalPopup: function(htm,title,params){
		if(undefined == params){params={};}
		if(undefined != document.getElementById('wacss_modal')){
			let m=document.getElementById('wacss_modal');
			let mel=m.querySelector('.wacss_modal_content');
			if(undefined != mel){
				mel.innerHTML=htm;
			}
			let mt=m.querySelector('.wacss_modal_title_text');
			if(undefined != mt){
				mt.innerHTML=title;
			}
			return m;
		}
		if(undefined == params.color){
			if(undefined != document.getElementById('admin_menu')){
				params.color=document.getElementById('admin_menu').getAttribute('data-color');
			}
			else{params.color='w_black';}
		}
		let modal=document.createElement('div');
		modal.id='wacss_modal';
		let modal_close=document.createElement('span');
		modal.className='wacss_modal';
		if(undefined!=title && title.length > 0){
			//default titlebar color to light if not specified in params
			let modal_title=document.createElement('div');
			modal_title.className='wacss_modal_title '+params.color;
			modal_close.className='wacss_modal_close icon-close';
			modal_close.title="Close";
			modal_close.onclick=function(){
				removeId(this.pnode);
			}
			modal_title.appendChild(modal_close);
			let modal_title_text=document.createElement('div');
			modal_title_text.className='wacss_modal_title_text';
			modal_title_text.innerHTML=title;
			modal_title.appendChild(modal_title_text);
			modal.appendChild(modal_title);

		}
		let modal_content=document.createElement('div');
		modal_content.className='wacss_modal_content';
		modal_content.innerHTML=htm;
		modal.appendChild(modal_content);
		if(undefined != params.overlay){
			let modal_overlay=document.createElement('div');
			modal_overlay.id='wacss_modal_overlay';
			modal_overlay.className='wacss_modal_overlay '+params.color;
			modal_overlay.appendChild(modal);
			modal_close.pnode=modal_overlay;
			modal_overlay.onclick = function(){
				//get the element where the click happened using hover
				let elements = document.querySelectorAll(':hover');
				let i=elements.length-1;
				if(this == elements[i]){
					removeId(this);	
				}
			}
			document.body.appendChild(modal_overlay);
		}
		else{
			modal_close.pnode=modal;
			document.body.appendChild(modal);
		}
		return modal;
	},
	navMobileToggle: function(el){
		let navs=document.querySelectorAll('.nav');
		for(let n=0;n<navs.length;n++){
			let lis=navs[n].querySelectorAll('li');
			for(let l=0;l<lis.length;l++){
				if(lis[l]==el){
					/* this  is the right nav */
					if(navs[n].className.indexOf('leftmenu') != -1){
						removeClass(navs[n],'leftmenu');	
					}
					else{
						addClass(navs[n],'leftmenu');
					}
				}
			}
		}
		return false;
	}
}