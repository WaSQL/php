function chatMessagesLoaded(){
	let frm=document.app_chat_form;
	let app=document.querySelector('div[data-app-settings="1"]');
	if(undefined == frm){return false;}
	if(undefined != frm.msg){frm.msg.value='';frm.msg.focus();}
	t=parseInt(app.dataset.timer)*1000;
	let msgs=document.querySelector('#chat_msgs');
	msgs.scrollTop = msgs.scrollHeight;
	window.setTimeout(function(){chatCheckForNewMessages();},t);
}
function chatNotify(){
	let notify=document.querySelector('#notify');
	console.log(notify);
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
	ajaxGet(app.dataset.ajaxurl+'/check_for_new_messages','chatnull',{last_message:el.dataset.lastMessage,setprocessing:0});
}
function chatGetNewMessages(){
	let app=document.querySelector('div[data-app-settings="1"]');
	ajaxGet(app.dataset.ajaxurl+'/get_new_messages','chat_msgs',{setprocessing:0});
}
function chatConfig(){
	let app=document.querySelector('div[data-app-settings="1"]');
	return ajaxGet(app.dataset.ajaxurl+'/config','modal',{setprocessing:0,title:'User Settings'});
}

