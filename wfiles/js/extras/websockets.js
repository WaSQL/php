
/*!
 * websockets (ws)
 		all function names begin with ws are are camel cased
 *
 */
var wsSocket=null;
var wsDebug=false;
var wsMessages=null
var wsInput=null;
function wsConnect(host,port,debug) {
	if(undefined==host){host='127.0.0.1';}
	if(undefined==port){port=9000;}
	if(undefined==debug){debug=false;}
	wsQuit(debug);
	var host = "ws://"+host+':'+port;
	try {
		wsSocket = new WebSocket(host);
		wsSocket.debug=debug;
		if(debug){console.log('wsConnect: - status='+wsSocket.readyState);}
		wsSocket.onopen    = function(msg) {
			if(wsSocket.debug){console.log("wsConnect onopen: msg="+msg.data);}
		};
		wsSocket.onmessage = function(msg) {
			if(wsSocket.debug){console.log("wsConnect onmessage: msg= "+msg.data);}
		};
		wsSocket.onclose  = function(msg) {
			if(wsSocket.debug){console.log("wsConnect onclose - status "+this.readyState);}
		};
	}
	catch(ex){ 
		if(debug){console.log(ex);}
	}
}

function wsSend(obj,debug){
	var msobj=getObject(obj);
	if(undefined==msobj){
    	if(debug){console.log("wsSend error - undefined object. returning");}
		return;
	}
	if(!msg) {
		if(debug){console.log("wsSend error - Empty Message. returning");}
		return; 
	}
	var msg=getText(msobj);
	setText(msobj,'');
	msobj.focus();
	try {
		wsSocket.send(msg);
		if(debug){console.log('wsSend: msg= '+msg);}
	} catch(ex) { 
		if(debug){console.log(ex);}
	}
}
function wsQuit(debug){
	if (wsSocket != null) {
		if(debug){console.log("wsQuit");}
		wsSocket.close();
		wsSocket=null;
	}
}
function wsEnterKey(event,obj,debug){ if(event.keyCode==13){ wsSend(obj,debug); } }



