function websocketNotify(file){
	if(undefined == file){file='notify';}
	var cid='websocketAudio_'+file.replace(/\//g,'');
	var obj=getObject(cid);
	if(undefined != obj){
    	obj.currentTime=0;
    	obj.play();
    	return;
	}
	var a=document.createElement('audio');
	a.id=cid;
	console.log(cid);
	var s=document.createElement('source');
	s.src=file+'.ogg';
	s.type='audio/ogg';
	a.appendChild(s);
	s=document.createElement('source');
	s.src=file+'.mp3';
	s.type='audio/mpeg';
	a.appendChild(s);
	s=document.createElement('source');
	s.src=file+'.wav';
	s.type='audio/wav';
	a.appendChild(s);
	document.body.appendChild(a);
	a.currentTime=0;
	a.play();
}
function websocketClient(obj,url,params){
	if(undefined == params){params={};}
	if(undefined == params.filter){params.filter='';}
	if(undefined == params.sound){params.sound='';}
	if(undefined == params.inp){params.inp=1;}
	var wsc = new websocketConnection(url);
	obj=getObject(obj);
	wsc.buildgui(obj,params.inp);
	wsc.params=params;
	if(params.inp==1){
		wsc.inputwindow.onkeypress = function(e){
			if (e.keyCode == 13 && this.value) {
				//send to logwindow as you
				wsc.logmessage('<span class="icon-user w_grey"></span> You: '+this.value);
				//send the message to others
				var msg={};
				msg.source='user';
				msg.message=this.value;
				msg.icon='icon-user';
				wsc.send('message',JSON.stringify(msg));
				//clear the input window
				this.value='';
			}
		};

		//if a user has focus on the logwindow and starts typing, switch to the input window
		wsc.logwindow.onkeypress = function(e){
			wsc.inputwindow.focus();
			wsc.inputwindow.value=e.key;
		};
	}
	//log connected
	wsc.bind('open', function() {
		wsc.logmessage('<span class="icon-mark w_success"></span> Connected');
		//loop through params and send to server
		for (var key in wsc.params){
			//exclude inp
			if(key=='inp'){continue;}
			if(key=='sound'){continue;}
			if(wsc.params[key].length==0){continue;}
			if(key=='filter'){
				var vals=wsc.params[key].split(',');
				for(var i=0;i<vals.length;i++){
					var msg={};
					msg.message='/'+key+' '+vals[i];
					wsc.send('message',JSON.stringify(msg));
				}
			}
			else{
				var msg={};
				msg.message='/'+key+' '+wsc.params[key];
				wsc.send('message',JSON.stringify(msg));
			}
		}
	});
	//log disconnected
	wsc.bind('close', function( data ) {
		wsc.logmessage('<span class="icon-close w_danger"></span> Disonnected');
	});
	//Log any messages sent from server
	wsc.bind('message', function( payload ) {
		wsc.logmessage( payload );
		if(wsc.params.sound.length){websocketNotify(wsc.params.sound);}
	});
	wsc.connect();
	return false;
}
var websocketConnection = function(url){
	var callbacks = {};
	var conn;
	var logwindow;
	this.bind = function(event_name, callback){
		callbacks[event_name] = callbacks[event_name] || [];
		callbacks[event_name].push(callback);
		return this;// chainable
	};

	this.send = function(event_name, event_data){
		this.conn.send( event_data );
		return this;
	};

	this.connect = function() {
		if ( typeof(MozWebSocket) == 'function' )
			this.conn = new MozWebSocket(url);
		else
			this.conn = new WebSocket(url);

		// dispatch to the right handlers
		this.conn.onmessage = function(evt){
			dispatch('message', evt.data);
		};

		this.conn.onclose = function(){dispatch('close',null)}
		this.conn.onopen = function(){dispatch('open',null)}
	};

	this.disconnect = function() {
		this.conn.close();
	};
	this.setfocus = function(){

	}
	this.logmessage = function(txt){
    	if(undefined == this.logwindow){return false;}
		var cline = document.createElement('div');
		cline.style.borderBottom='1px dashed #ccc';
		cline.style.marginBottom='3px';
		cline.innerHTML=txt;
		//send to logwindow as you
		this.logwindow.appendChild(cline);
		//set logwindow scroll
		this.logwindow.scrollTop = this.logwindow.scrollHeight - this.logwindow.clientHeight;
	};
	this.buildgui = function(obj,inp){
		if(undefined==inp){inp=1;}
		obj=getObject(obj);
		if(undefined == obj){obj=document.querySelector(obj);}
		if(undefined == obj){
        	console.log('websocketConnection gui error - gui object does not exist'+gui);
        	return false;
		}
		//create an log window
		this.logwindow = document.createElement('div');
		if(undefined != obj.getAttribute('data-height')){
			var h=obj.getAttribute('data-height');
			this.logwindow.style.height=h+'px';
		}
		else{
        	this.logwindow.style.height='300px';
		}
		this.logwindow.style.overflow='auto';
		this.logwindow.style.resize='both';
		this.logwindow.style.backgroundColor='#FFF';
		this.logwindow.style.border='1px solid #ccc';
		this.logwindow.style.padding='6px 12px';
		this.logwindow.style.borderRadius='4px';
		//this.logwindow.style.boxShadow='0 1px 1px rgba(0, 0, 0, 0.075) inset';
		obj.appendChild(this.logwindow);
		if(inp==1){
			//create an input window
			this.inputwindow = document.createElement('input');
			this.inputwindow.className='form-control';
			this.inputwindow.type='text';
			obj.appendChild(this.inputwindow);
			this.inputwindow.focus();
		}
	}
	var dispatch = function(event_name, message){
		var chain = callbacks[event_name];
		if(typeof chain == 'undefined') return; // no callbacks for this event
		for(var i = 0; i < chain.length; i++){
			chain[i]( message )
		}
	}
};