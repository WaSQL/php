function chatlistTimer(){
	if(undefined != document.activeElement && document.activeElement.name=='msg'){return;}
	ajaxGet('/chat','chat_chatlist',{_template:1,func:'chatlist',nosetprocess:1});
}
function chatShowMyChatUsers(){
	var obj=getObject('chat_mychatusers');
	if(obj.style.display!='none'){
		obj.style.display='none';
		return;
	}
	return ajaxGet('/chat','chat_mychatusers',{_template:1,func:'mychatusers',nosetprocess:1});
}
function chatSetFocus(id){
	if(undefined == document.getElementById('chatform_'+id)){
		console.log('chatform_'+id+' does not exist to focus on');
		return;
	}
	document.getElementById('chatform_'+id).msg.focus();
}
function chatScrollToBottom(id){
	if(undefined == document.getElementById('chatbox_chats_'+id)){
		console.log('chatbox_chats_'+id+' does not exist to scroll to bottom of');
		return;
	}
	var element=document.getElementById('chatbox_chats_'+id);
	element.scrollTop = element.scrollHeight;
}

function chatSendMessage(msg_to,msg){
	hideId('chat_mychatusers');
	if(undefined == msg || !msg.length){msg='*';}
	document.chat_searchform.search.value='';
	chatSearchList();
	return ajaxGet('/chat','chat_chatlist',{_template:1,func:'sendmessage',msg_to:msg_to,msg:msg,nosetprocess:1});
}
/* autocomplete functionality for chat search field */
function chatSearchList(str){
	if(undefined==str){str='';}
	str=str.toLowerCase();
	var list=document.querySelectorAll('div[data-userlist]');
	var cnt=0;
	for(var i=0;i<list.length;i++){
		var val=list[i].getAttribute('data-userlist');
		if(!str.length || val.indexOf(str)==-1){
    		list[i].style.display='none';
		}
		else{
        	list[i].style.display='block';
        	cnt++;
		}
	}
	if(cnt>0){
    	document.querySelector('div#chat_userlist').style.display='block';
	}
	else{
    	document.querySelector('div#chat_userlist').style.display='none';
	}
	return false;
}
