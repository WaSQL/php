// window.addEventListener('paste', ... or
function chatInitAcceptAttachments(){
	let els=document.querySelectorAll('[data-accept="attachments"]');
	for(let e=0;e<els.length;e++){
		els[e].onpaste = function (event) {
			event=event || window.event;
		  	// use event.originalEvent.clipboard for newer chrome versions
		  	let items = (event.clipboardData  || event.originalEvent.clipboardData).items;
		  	// find pasted image among pasted items
		  	let blob = null;
		  	for (var i = 0; i < items.length; i++) {
		    	if (items[i].type.indexOf("image") === 0) {
		      		blob = items[i].getAsFile();
		      		break;
		    	}
		  	}
		  	// load image if there is a pasted image
		  	if (blob !== null) {
		    	let reader = new FileReader();
		    	reader.el=this;
		    	reader.onload = function(event) {
		    		if(undefined != this.el.dataset.acceptTarget){
		    			let t=getObject(this.el.dataset.acceptTarget);
		    			if(undefined != t){
		    				let tlist=t.querySelectorAll('span');
		    				let fname=this.el.name+'_attachment_'+tlist.length;
		    				let span=document.createElement('span');
			  				span.className='icon-file-image w_bigger';
			  				span.dataset.src=fname;
		    				span.onclick=function(){
		    					return chatShowAttachment(this);
		    				}
		    				t.appendChild(span);
		    				let fld=document.createElement('textarea');
		    				fld.name=fname;
		    				fld.style.display='none';
		    				fld.innerText=event.target.result;
		    				t.appendChild(fld);
		    				//console.log(img);
		    			}
		    		}
		      		//console.log(event.target.result); // data url!
		    	};
		    	reader.readAsDataURL(blob);
		  	}
		};
		els[e].ondragenter = function(event){
			event=event || window.event;
			event.preventDefault();
			this.style.borderColor='#217346';
			this.style.backgroundColor='#bdecd2';
			this.style.cursor='pointer';
		};
		els[e].ondragleave = function(event){
			event=event || window.event;
			event.preventDefault();
			this.style.borderColor='#b5b5b5';
			this.style.backgroundColor='#FFFFFF';
			this.style.cursor='default';
		};
		els[e].ondrop = function (event) {
			event=event || window.event;
			event.preventDefault();
			this.style.borderColor='#b5b5b5';
			this.style.backgroundColor='#FFFFFF';
			this.style.cursor='default';
			let blob = null;
			let icon = '';
			let type = '';
			if (event.dataTransfer.items) {
	    		for (let i=0;i<event.dataTransfer.items.length;i++) {
	      			if (event.dataTransfer.items[i].kind === 'file') {
	      				type=event.dataTransfer.items[i].type;
	      				blob = event.dataTransfer.items[i].getAsFile();
	      				let reader = new FileReader();
				    	reader.el=this;
				    	reader.type=type;
				    	reader.onload = function(event) {
				    		if(undefined != this.el.dataset.acceptTarget){
				    			let t=getObject(this.el.dataset.acceptTarget);
				    			if(undefined != t){
				    				let tlist=t.querySelectorAll('span');
				    				let fname=this.el.name+'_attachment_'+tlist.length;
				    				let span=document.createElement('span');
					    			if(this.type.indexOf('audio')===0){
					    				span.className='icon-file-audio w_bigger';
					    			}
					    			else if(this.type.indexOf('image')===0){
					    				span.className='icon-file-image w_bigger';
					    				span.dataset.src=fname;
					    				span.onclick=function(){
					    					return chatShowAttachment(this);
					    				}
					    			}
					    			else if(this.type.indexOf('video')===0){
					    				span.className='icon-file-video w_bigger';
					    			}
					    			else if(this.type.indexOf('/pdf') != -1){
					    				span.className='icon-file-pdf w_bigger';
					    			}
					    			else if(this.type.indexOf('zip') != -1){
					    				span.className='icon-file-zip w_bigger';
					    			}
					    			else if(this.type.indexOf('text')===0){
					    				span.className='icon-file-txt w_bigger';
					    			}
				    				else{
				    					span.className='icon-file-doc w_bigger';
				    				}
				    				
				    				t.appendChild(span);

				    				let fld=document.createElement('textarea');
				    				fld.name=fname;
				    				fld.style.display='none';
				    				fld.innerText=event.target.result;
				    				t.appendChild(fld);
				    				//console.log(img);
				    			}
				    		}
				      		//console.log(event.target.result); // data url!
				    	};
				    	reader.readAsDataURL(blob);
	      			}
	    		}
  			} 
  			else {
    			for (let i=0;i<event.dataTransfer.files.length;i++) {
    				type=event.dataTransfer.files[i].type;
    				blob=event.dataTransfer.files[i];
    				let reader = new FileReader();
			    	reader.el=this;
			    	reader.type=type;
			    	reader.onload = function(event) {
			    		if(undefined != this.el.dataset.acceptTarget){
			    			let t=getObject(this.el.dataset.acceptTarget);
			    			if(undefined != t){
			    				let tlist=t.querySelectorAll('span');
			    				let fname=this.el.name+'_attachment_'+tlist.length;
			    				let span=document.createElement('span');
				    			if(this.type.indexOf('audio')===0){
				    				span.className='icon-file-audio w_bigger';
				    			}
				    			else if(this.type.indexOf('image')===0){
				    				span.className='icon-file-image w_bigger';
				    				span.dataset.src=fname;
				    				span.onclick=function(){
				    					return chatShowAttachment(this);
				    				}
				    			}
				    			else if(this.type.indexOf('video')===0){
				    				span.className='icon-file-video w_bigger';
				    			}
				    			else if(this.type.indexOf('/pdf') != -1){
				    				span.className='icon-file-pdf w_bigger';
				    			}
				    			else if(this.type.indexOf('zip') != -1){
				    				span.className='icon-file-zip w_bigger';
				    			}
				    			else if(this.type.indexOf('text')===0){
				    				span.className='icon-file-txt w_bigger';
				    			}
			    				else{
			    					span.className='icon-file-doc w_bigger';
			    				}
			    				
			    				t.appendChild(span);

			    				let fld=document.createElement('textarea');
			    				fld.name=fname;
			    				fld.style.display='none';
			    				fld.innerText=event.target.result;
			    				t.appendChild(fld);
			    				//console.log(img);
			    			}
			    		}
			      		//console.log(event.target.result); // data url!
			    	};
			    	reader.readAsDataURL(blob);
    			}
  			}
		};
	}
}
function chatShowAttachment(span){
	let src=document.app_chat_form[span.dataset.src].value;
	htm='<div style="padding:5px 8px;"><img src="'+src+'" alt="" style="max-width:800px;max-height:800px;" /></div>';
	wacss.modalPopup(htm);
	return false;
}
function chatExpandImage(img){
	htm='<div style="padding:5px 8px;"><img src="'+img.src+'" alt="" style="max-width:800px;max-height:800px;" /></div>';
	wacss.modalPopup(htm,'Image Viewer',{overlay:1});
	return false;
}
function chatMessagesLoaded(){
	let app=document.querySelector('div[data-app-settings="1"]');
	t=parseInt(app.dataset.timer)*1000;
	let msgs=document.querySelector('#chat_msgs');
	msgs.scrollTop = msgs.scrollHeight;
	window.setTimeout(function(){chatCheckForNewMessages();},t);
}
function chatMessagesClearInput(){
	let frm=document.app_chat_form;
	if(undefined == frm){return false;}
	if(undefined != frm.msg){
		frm.msg.value='';frm.msg.focus();
	}
	let attach=document.querySelector('#chat_msg_attachments');
	if(undefined != attach){
		attach.innerText='';
		console.log('cleared chat_msg_attachments');
	}
}
function chatNotify(){
	let notify=document.querySelector('#notify');
	notify.play();
}
function chatSetTimer(){
	let app=document.querySelector('div[data-app-settings="1"]');
	t=parseInt(app.dataset.timer)*1000;
	window.setTimeout(function(){chatCheckForNewMessages();},t);
}
function chatCheckForNewMessages(){
	let el=document.querySelector('div[data-last-message]');
	let app=document.querySelector('div[data-app-settings="1"]');
	ajaxGet(app.dataset.ajaxurl+'/app_chat_check_for_new_messages','chatnull',{last_message:el.dataset.lastMessage,setprocessing:0});
}
function chatGetNewMessages(){
	let app=document.querySelector('div[data-app-settings="1"]');
	ajaxGet(app.dataset.ajaxurl+'/app_chat_get_new_messages','chat_msgs',{setprocessing:0});
}
function chatConfig(){
	let app=document.querySelector('div[data-app-settings="1"]');
	return ajaxGet(app.dataset.ajaxurl+'/app_chat_config','modal',{setprocessing:0,title:'User Settings'});
}
function chatDelete(el){
	if(!confirm('Delete this entry?')){return false;}
	let app=document.querySelector('div[data-app-settings="1"]');
	let id=el.dataset.id;
	return ajaxGet(app.dataset.ajaxurl+'/app_chat_delete/'+id,'chat_msgs',{setprocessing:0});
}
function chatEdit(el){
	let app=document.querySelector('div[data-app-settings="1"]');
	let id=el.dataset.id;
	let div='chat_message_'+id;
	return ajaxGet(app.dataset.ajaxurl+'/app_chat_edit/'+id,div,{setprocessing:0});
}

