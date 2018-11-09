/* event based javascript routines*/
/* - Required dependancies: common.js 			 */
/*----------------------------------------------*/

/* Capture mouse movement and set MouseX and MouseY to its x,y corordinates */
var MouseX=0;
var MouseY=0;
var cursor = {x:0, y:0};
if(document.onmousemove){
	document.onmousemove = mouseMove;
	}
else if(document.addEventListener){
	/* Firefox model */
	document.addEventListener("mousedown",mouseMove,false);
    document.addEventListener("mouseup",mouseMove,false);
    document.addEventListener("mousemove",mouseMove,false);
	}
else if(document.attachEvent){
	/* IE model */
	document.attachEvent("onmousedown",mouseMove);
    document.attachEvent("onmouseup",mouseMove);
    document.attachEvent("onmousemove",mouseMove);
	}
else if(document.captureEvents){document.captureEvents(Event.MOUSEDOWN | Event.MOUSEMOVE | Event.MOUSEUP);}
/**
* @describe loads the contents of the text file into the element it is dropped on
* @param element object or id  - the element
* @return false
* @usage loadTextFileInit('sql_area');
*/
function loadTextFileInit(el){
	// Setup the dnd listeners.
	var loadTextFile = getObject(el);
	if(undefined == el){
		console.log('Error: invalid element',el);
		return;
	}
	loadTextFile.origBgcolor=loadTextFile.style.backgroundColor;
	addEventHandler(loadTextFile,"dragover",function(evt){
		evt.stopPropagation();
		evt.preventDefault();
		//evt.dataTransfer.dropEffect = 'copy';
	});
	addEventHandler(loadTextFile,"dragenter",function(evt){
		evt.stopPropagation();
		evt.preventDefault();
		//change bg color
		this.style.backgroundColor='#f0f0f0';
	});
	addEventHandler(loadTextFile,"dragexit",function(evt){
		evt.stopPropagation();
		evt.preventDefault();
		//change bg color back
		this.style.backgroundColor=this.origBgcolor;
	});
	addEventHandler(loadTextFile,"drop",function(evt){
		evt.stopPropagation();
		evt.preventDefault();
		this.style.backgroundColor=this.origBgcolor;
		// FileList object.
		var files = evt.dataTransfer.files;
		var obj=this;
		// files is a FileList of File objects.
		for (var i=0;i<files.length;i++){
			var f=files[i];
			// Only process text files.
			if (!f.type.match('text.*')) {
				setText(this,'ERROR! '+f.name+' is not a text file');
				continue;
			}
			var reader = new FileReader();
			reader.targetObj=this;
			// Closure to capture the file information.
			reader.onload = (function(theFile) {
				return function(e) {
					setText(this.targetObj,e.target.result);
				};
			})(f);
			// Read in the file as text.
			reader.readAsText(f);
			loadTextFileInit(this);
			break;
		}
	});
}
function eventInitSticky(){
	//set table headers  to sticky
	var tlist=document.querySelectorAll('table.w_sticky');
	for(var t=0;t<tlist.length;t++){
		//get the top position of this table
		var tabletop=tlist[t].offsetTop;
		if(undefined != tlist[t].getAttribute('data-top')){
			tabletop=tlist[t].getAttribute('data-top');
		}
		//get the table thead th objects and set them to sticky
		var list=tlist[t].querySelectorAll('thead th');	
		for(var i=0;i<list.length;i++){
			list[i].style.position='sticky';
			list[i].style.top=tabletop+'px';
		}
	}
	
	//set anything that has w_sticky as a class
	list=document.querySelectorAll('.w_sticky');
	for(i=0;i<list.length;i++){
		//skip tables since we have already taken care of them above
		if(undefined != tlist[i].nodeName && tlist[i].nodeName=='TABLE'){
			continue;
		}
		else if(undefined != tlist[i].tagName && tlist[i].tagName=='TABLE'){
			continue;
		}
		t=list[i].offsetTop;
		list[i].style.position='sticky';
		list[i].style.top=t+'px';
	}
}
function marquee(id){
	//info: turns text in specified object or id into a scrolling marquee
	var mobj=getObject(id);
	if(undefined==mobj){return false;}
	var mid=mobj.id;
	clearTimeout(TimoutArray[id]);
	//get the attributes
	var attr=getAllAttributes(mobj);
	if(undefined == attr['m']){
		mobj.setAttribute('m',0);
		attr['m']=0;
	}
	//pause scrolling is mouse if over the area
	if (typeof(mobj.onmouseover) != 'undefined'){
		mobj.onmouseover=function(){
			this.setAttribute('m',1);
		};
		mobj.onmouseout=function(e){
			if(undefined == e){e = fixE(e);}
			if(undefined != e){
				if(checkMouseLeave(this,e)){
					this.setAttribute('m',0);
				}
			}
		};
	}
	if(undefined == attr['m'] || attr['m']===0){
		//get the text and determine its length
		var pxwh=getTextPixelWidthHeight(mobj);
		var mwh=getWidthHeight(mobj);
		//set timer default to 20 and allow override
		var timer=20;
		if(undefined != attr['timer']){speed=attr['timer'];}
		//set direction default to left and allow override
		var direction='left';
		if(undefined != attr['direction']){direction=attr['direction'];}
		//change position based on direction
		switch(direction.toLowerCase()){
			case 'left':
				//scroll right to left
				mobj.style.textAlign='right';
				mobj.style.whiteSpace='nowrap';
				mobj.style.paddingLeft='0px';
				if(undefined != mobj.style.paddingRight){
					x=mobj.style.paddingRight;
					if(x.length){
						x=x.replace('px','');
						x=parseInt(x,10);
					}
					else{
	                    	x=0;
					}
				}
				if(x < mwh[0]-pxwh[0]-2){
					mobj.style.paddingRight=parseInt(x+2,10)+'px';
				}
				else{
					mobj.style.paddingRight='0px';
				}
				break;
			case 'right':
				//scroll left to right
				mobj.style.textAlign='left';
				mobj.style.whiteSpace='nowrap';
				mobj.style.paddingRight='0px';
				if(undefined != mobj.style.paddingLeft){
					x=mobj.style.paddingLeft;
					if(x.length){
						x=x.replace('px','');
						x= parseInt(x,10);
					}
					else{
	                    	x=0;
					}
				}
				if(x < mwh[0]-pxwh[0]-2){
					mobj.style.paddingLeft=parseInt(x+2,10)+'px';
				}
				else{
					mobj.style.paddingLeft='0px';
				}
				break;
			default:
				return false;
				break;
		}
	}
	//set timeout to call it again in speed miliseconds
	TimoutArray[id] = setTimeout("marquee('"+id+"')",timer);
	return false;
}
function mouseMove(e) {
	if (!e){e = window.event;}
	if (e.pageX || e.pageY){
		cursor.x = e.pageX;
		cursor.y = e.pageY;
		}
	else if (e.clientX || e.clientY){
		if(document.body){
			if(document.documentElement){
				cursor.x = e.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;
				cursor.y = e.clientY + document.body.scrollTop + document.documentElement.scrollTop;
				}
			}
		}
	/* set MouseX and MouseY for backward compatibility*/
	MouseY=cursor.y;
    MouseX=cursor.x;
    //window.status=MouseX+','+MouseY;
	}
function mp3Player(mp3,id,as){
	//info: creates flash object tags for mp3 player using niftyplayer.swf
	if(undefined == as){as=0;}
	var htm='';
	htm += '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" width="165" height="38" id="niftyPlayer1" align="">'+"\n";
	htm += '<param name=movie value="/wfiles/niftyplayer.swf?file='+mp3+'&as='+as+'">'+"\n";
	htm += '<param name=quality value=high>'+"\n";
	htm += '<param name=bgcolor value=#FFFFFF>'+"\n";
	htm += '<embed src="/wfiles/niftyplayer.swf?file='+mp3+'&as='+as+'" quality=high bgcolor=#FFFFFF width="165" height="38" name="niftyPlayer1" align="" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer">'+"\n";
	htm += '</embed>'+"\n";
	htm += '</object>'+"\n";
	setText(id,htm);
	}
function embedFlash(swf,param){
	if(undefined == param['width']){param['width']=400;}
	if(undefined == param['height']){param['height']=300;}
	if(undefined == param['bgcolor']){param['bgcolor']='#FFFFFF';}
	if(undefined == param['name']){param['name']='flashobj';}
	var htm='';
	htm += '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" width="'+param['width']+'" height="'+param['height']+'" id="'+param['name']+'" align="">'+"\n";
	htm += '<param name=movie value="'+swf+'">'+"\n";
	htm += '<param name="quality" value="high">'+"\n";
	htm += '<param name="wmode" value="transparent">'+"\n";
	htm += '<param name="bgcolor" value="'+param['bgcolor']+'">'+"\n";
	htm += '<embed src="'+swf+'" wmode="transparent" quality="high" bgcolor="'+param['bgcolor']+'" width="'+param['width']+'" height="'+param['height']+'" name="'+param['name']+'" align="" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer">'+"\n";
	htm += '</embed>'+"\n";
	htm += '</object>'+"\n";
	if(undefined != param['debug']){alert(htm);}
	if(undefined != param['popup'] && param['popup']==1){popUpDiv(htm,param);}
	else{
		setText(param['id'],htm);
		}
	}
/* make a div float */
function makeDivFloat(id, sx, sy){setFloatDiv(id, sx, sy).floatIt();}
var d=document;
var ns = (navigator.appName.indexOf("Netscape") != -1);
function setFloatDiv(id, sx, sy){
	var el=d.getElementById?d.getElementById(id):d.all?d.all[id]:d.layers[id];
	var px = document.layers ? "" : "px";
	window[id + "_obj"] = el;
	if(d.layers){el.style=el;}
	el.cx = el.sx = sx;el.cy = el.sy = sy;
	el.sP=function(x,y){this.style.left=x+px;this.style.top=y+px;};
	el.floatIt=function(){
		var pX, pY;
		pX = (this.sx >= 0) ? 0 : ns ? innerWidth :
		document.documentElement && document.documentElement.clientWidth?document.documentElement.clientWidth:document.body.clientWidth;
		pY = ns?pageYOffset : document.documentElement && document.documentElement.scrollTop?document.documentElement.scrollTop : document.body.scrollTop;
		if(this.sy<0){
			pY += ns ? innerHeight : document.documentElement && document.documentElement.clientHeight?document.documentElement.clientHeight : document.body.clientHeight;
			}
		this.cx += (pX + this.sx - this.cx)/8;this.cy += (pY + this.sy - this.cy)/8;
		this.sP(this.cx, this.cy);
		setTimeout(this.id + "_obj.floatIt()", 40);
		};
	return el;
	}
var changeState = new Array();
var changeValue = new Array();
var nicEditors = new Array();
var OnLoad = "";
//Schedule a Page Refresh
var schedulePageID='';
function schedulePageRefresh(page,div,opts,ms){
	var id='schedulePageRefresh';
	clearTimeout(TimoutArray[id]);
	//info: schedules a page refresh
	if(undefined == ms){ms=60000;}
	TimoutArray[id] = setTimeout('pageRefresh("'+page+'","'+div+'","'+opts+'")',ms);
    }
function scheduleAjaxGet(id,page,div,opts,ms,nosetprocess){
	//info: schedules a page refresh
	clearTimeout(TimoutArray[id]);
	if(undefined == ms){ms=60000;}
	if(undefined == nosetprocess){nosetprocess=0;}
	TimoutArray[id] = setTimeout('pageRefresh("'+page+'","'+div+'","'+opts+'",'+nosetprocess+')',ms);
    }
function pageRefresh(page,div,opts,nosetprocess){
	if(undefined == document.getElementById(div)){return false;}
	ajaxGet('/'+page,div,opts,'',60000,nosetprocess);
	return false;
}
// -- processMultiComboBox
function processMultiComboBox(tid,cid,tcnt,cm,showvalues){
     var tbox=document.getElementById(tid);
	var cbox=document.getElementById(cid);
	if(cm && cbox){
	     if(cbox.checked){cbox.checked = false;}
		else{cbox.checked = true;}
	}
	var list = GetElementsByAttribute('input','name',tid);
	var cnt=0;
	var val='';
	for(var i=0;i<list.length;i++){
		if(list[i].checked){
			if(showvalues){val += list[i].value+",";}
			cnt++;
		}
	}
	if(showvalues){tbox.value=val;}
	else{tbox.value=cnt+"/"+tcnt+" selected";}
}

//Determine if an objects value has changed
function setChangeState(tid){
     var el=document.getElementById(tid);
     changeState[tid]=el.value;
     }
function setChangeValue(tid,val){
     changeValue[tid]=val;
     }
function evalChange(tid){
	eval(changeValue[tid]);
	}
function hasChanged(tid){
     var el=document.getElementById(tid);
     var changed=0;
     var val=el.value;
     var old=changeState[tid];
     if(val != old){changed++;}
     if(changed){return true;}
     return false;
     }
function iframePopup(url,opts){
	if(undefined == opts){opts=new Object;}
	var htm='';
	if(undefined == opts['iwidth']){opts['iwidth']=500;}
	if(undefined == opts['iheight']){opts['iheight']=300;}
	if(undefined == opts['iscrolling']){opts['iscrolling']='auto';}
	htm += '<div class="w_centerpop_title">'+opts['title']+'</div>'+"\n";
	htm += '<div class="w_centerpop_content">'+"\n";
	htm += '<iframe seamless="1" src="'+url+'" width="'+opts['iwidth']+'" height="'+opts['iheight']+'" frameborder="0" marginwidth="0" marginheight="0" scrolling="'+opts['iscrolling']+'" align="center">Your browser does not support iframes.</iframe>';
	htm += '</div>'+"\n";
	centerpopDiv(htm,null,0);
	return false;
	}
function w_shareButton(url,t){
	window.open(url,'_blank','scrollbars=no, location=no, width=600, height=400, status=no, toolbar=no, menubar=no',false);
	return false;
}
function ajaxPopup(url,params,useropts){
	/* set default opt values */
	var pid='ajaxPopupDiv';
	var opt={
        id: pid,
        drag:1
		};
	/* allow user to override default opt values */
	if(useropts){
		var key;
		for (key in opt){
			if(undefined != useropts[key]){opt[key]=useropts[key];}
			}
		/* add additonal user settings to opt Object */
		for (key in useropts){
			if(undefined == opt[key]){opt[key]=useropts[key];}
			}
		}
	popUpDiv('<div class="w_bold w_lblue w_big"><img src="/wfiles/loading_blu.gif"> loading...please wait.</div>',opt);
	ajaxGet(url,opt['id']+'_Body',params);
	}
/* cancelBubble - stop propigation of events to that onclick events of the parent elements do not fire. */
function cancelBubble(e) {
 	var evt = e ? e:window.event;
 	if (evt.preventDefault){evt.preventDefault();}
	if (evt.stopPropagation){evt.stopPropagation();}
 	if (evt.cancelBubble !== null){evt.cancelBubble = true;}
}
/* centerpopDiv*/
function centerpopDiv(txt,rtimer,x){
	if(undefined == x){x='';}
	var divid='centerpop'+x;
	if(undefined != document.getElementById(txt)){txt=getText(txt);}
	var params={id:divid,drag:1,notop:1,nobot:1,noborder:1,nobackground:1,bodystyle:"padding:0px;border:0px;background:none;"};
	if(undefined != rtimer && rtimer > 0){
		params.showtime=rtimer;
    }
	popUpDiv('',params);
	setCenterPopText(divid,txt,{drag:true,close_bot:false});
	centerObject(divid);
	setStyle(divid,'zIndex','99999');
	return false;
}
/* tooltipDiv */
var tooltipDivObj='';
function tooltipDiv(obj,rtimer){
	if(undefined != commonFadeIdTimeouts['w_tooltip']){clearTimeout(commonFadeIdTimeouts['w_tooltip']);}
	obj=getObject(obj);
	if(tooltipDivObj==obj){return false;}
	tooltipDivObj=obj;
	var txt=obj.getAttribute('data-tooltip');
	var position=obj.getAttribute('data-tooltip_position') || '';
	var ajax='';
	if(txt.indexOf('id:')===0){
		//get tooltip text from an external div
    	var divid=str_replace('id:','',txt);
    	txt=getText(divid) || '';
	}
	else if(txt.indexOf('js:')===0){
		//call a function
    	var f=str_replace('js:','',txt);
    	txt=eval(f);
	}
	else if(txt.indexOf('ajax:')===0){
		//call a function
    	ajax=str_replace('ajax:','',txt);
    	txt='';
	}
	else if(txt.indexOf('att:')===0){
		//get tooltip from another attribute - att:alt for example
    	var att=str_replace('att:','',txt);
    	txt=obj.getAttribute(att) || '';
	}
	if(txt.length === 0 || txt==='false' || !txt){txt=' ';}
	var cObj=getObject('w_tooltip');
	if(undefined != cObj){
		tooltipDivObj='';
		removeId(cObj);
	}
	var tipdiv = document.createElement("div");
	tipdiv.setAttribute("id",'w_tooltip');
	tipdiv.style.zIndex='698999';
	tipdiv.style.position='absolute';
	//tipdiv.innerHTML=obj.nodeName+':'+txt;
	tipdiv.innerHTML=txt;
	var pos=findPos(obj);
	var x=y=h=w=th=0;
	h=getHeight(obj);
	//default image position to bottom
	if(position==='' && obj.nodeName.toLowerCase()==='img'){
		position='bottom';
	}
	if(position=='bottom'){
    	h=getHeight(obj);
    	w=getWidth(obj);
    	y=pos.y+h+6;
    	x=pos.x;
	}
	else{
		//default to tip on right of image
        tipdiv.setAttribute("class",'left');
    	w=getWidth(obj);
    	y=pos.y;
    	x=pos.x+w+6;
	}
	tipdiv.style.top=y+"px";
    tipdiv.style.left=x+"px";
 	document.body.appendChild(tipdiv);
 	if(ajax.length){
		ajaxGet(ajax,'w_tooltip');
	}
	return false;
}
/* popUpDiv */
function popUpDiv(content,param){
	//showProperties(param,'debug',1);
	/* set default opt values */
	var s="position:absolute;top:200px;left:200px;margin:5px;z-index:99999;";
	var bs="padding:5px;border:1px solid #d6dee7;background:#FFF;";
	clearTimeout('popupdiv_timeout');
	if(undefined != param['width']){
		s+='width:'+param['width']+'px;';
		}
	if(undefined != param['height']){bs+='height:'+param['height']+'px;overflow:auto;';}
	var opt={
        id: 'w' + new Date().getTime(),
        style: s,
        title: "",
        closestyle:"cursor:pointer;",
        close: '<span class="icon-cancel w_danger"></span>',
        bodystyle: bs,
        titleleft: 20,
        body: content
		};
	/* allow user to override default opt values */
	if(param){
		var key;
		for (key in opt){
			if(undefined != param[key]){opt[key]=param[key];}
			}
		/* add additonal user settings to opt Object */
		for (key in param){
			if(undefined == opt[key]){opt[key]=param[key];}
			}
		}
	//alert('opt:'+opt.timeout+', param:'+param.timeout);
	var masterdiv;
	if(undefined != document.getElementById(opt.id)){removeDiv(opt.id);}
	if(undefined != document.getElementById(opt.id)){
		masterdiv=document.getElementById(opt.id);
		//show if hidden
		var bodyid=opt.id+'_Body';
		masterdiv.style.display='block';
		if(undefined != document.getElementById(bodyid)){
			setText(bodyid,opt.body);
			}
		}
	else{
		masterdiv = document.createElement("div");
		masterdiv.setAttribute("id",opt['id']);
		masterdiv.style.zIndex='9999';
		masterdiv.style.position='absolute';
		var t  = document.createElement("table");
		t.border=0;
		t.align="left";
		t.cellPadding=0;
		t.cellSpacing=0;
		if(undefined != opt['width']){
			t.style.width=opt['width']+'px';
			}
		//bgcolor
	    var bgcolor='#49495a';
	    if(opt.titlebgcolor){bgcolor=opt.titlebgcolor;}
	    else if(param.titlebgcolor){bgcolor=param.titlebgcolor;}
	    //Table border
	    if(undefined == param['noborder']){
			t.style.border='1px solid '+bgcolor;
			}
		else{
			t.style.border='0px solid '+bgcolor;
        	}
		//body - begin
	    var tb = document.createElement("tbody");
	    if(undefined == param['notop']){
		    //title row - begin
		    var toprow = document.createElement("div");
		    //titlecell
		    var titlecell = document.createElement("td");
		 	//title
		    titlecell.noWrap = true;
		    titlecell.align='right';
		    titlecell.style.fontFamily='arial';
		    titlecell.style.fontSize='11px';
			titlecell.style.backgroundColor=bgcolor;
			//color
			if(opt.titlecolor){
				titlecell.style.color=opt.titlecolor;
				}
			else{titlecell.style.color='#FFFFFF';}
			var titlediv = document.createElement("div");
		    var titletxt='<div id="'+opt['id']+'_Title'+'" style="float:left;margin-left:10px;margin-top:1px">'+opt['title']+'</div>';
		    //add close div
		    titletxt += '<a href="#" style="font-weight:bold;font-size:12px;font-family:arial;color:#970000;text-decoration:none;padding:0 3px 0 0;" onclick="ajaxAbort(\''+opt['id']+'\',1);return false;">X</a>';
		    titlediv.innerHTML=titletxt;
		    titlecell.appendChild(titlediv);
		    toprow.appendChild(titlecell);
			tb.appendChild(toprow);
			}
		//top row - end

		//Body row - begin
	    var bodyrow = document.createElement("tr");
	    bodyrow.height='100%';
	    var bodycell = document.createElement("td");
	    if(undefined == param['nobackground']){
	    	bodycell.style.backgroundColor='#FFFFFF';
			}
	    var bodydiv = document.createElement("div");
	    var bodycontent = '<div id="'+opt['id']+'_Body'+'">'+opt.body;
		bodycontent += '</div>';
	    bodydiv.innerHTML=bodycontent;
	    bodycell.appendChild(bodydiv);
	    bodyrow.appendChild(bodycell);
		tb.appendChild(bodyrow);
		//body row - end
		if(undefined == param['nobot']){
			//bottom close row
		    var botrow = document.createElement("tr");
		    var botcell = document.createElement("td");
		    botcell.noWrap = true;
		    botcell.align='right';
		    botcell.style.fontFamily='arial';
		    botcell.style.fontSize='11px';
		    bgcolor='#FFFFFF';
	    	if(opt.botbgcolor){bgcolor=opt.botbgcolor;}
	    	else if(param.botbgcolor){bgcolor=param.botbgcolor;}
		    botcell.style.backgroundColor=bgcolor;
			var botdiv = document.createElement("div");
		    //add close div
		    var bottxt = '<a href="#" class="w_red w_bold w_link"" onclick="ajaxAbort(\''+opt['id']+'\',1);return false;">Close</a>';
		    botdiv.innerHTML=bottxt;
		    botcell.appendChild(botdiv);
		    botrow.appendChild(botcell);
			tb.appendChild(botrow);
			}

		//allow body to be resized
		//addDragToTextarea(opt['id']+'_Body');

		//body -end
	    t.appendChild(tb);

		masterdiv.style.display='block';
	    //append table to masterdiv
	    masterdiv.appendChild(t);
	    if(opt.drag && undefined == param['notop']){
			Drag.init(titlediv,masterdiv);
			titlediv.style.cursor='move';
	        }
	    //append to document body
	    document.body.appendChild(masterdiv);
    	}

    /* check for center option */
    masterdiv.style.display='block';
    if(opt.center){
		var xy=centerObject(masterdiv);
		var x=0;
		var y=0;
		var cvalue=opt.center+'';
		if(cvalue.indexOf('x') != -1){
			//only center x - make y MouseY
			x=xy[0];
        	}
        else if(cvalue.indexOf('y') != -1){
			//only center y - make x MouseX
			y=xy[1];
        	}
        else{
			x=xy[0];
			y=xy[1];
        	}
        //check for x and y
		if(undefined != opt.x){
			//if x begins with a + or -, then add it
			xvalue=opt.x+'';
			if(xvalue.indexOf('+') != -1){x=Math.round(MouseX+parseInt(xvalue,10));}
			else if(xvalue.indexOf('-') != -1){x=Math.round(MouseX-Math.abs(parseInt(xvalue,10)));}
			else{x=Math.round(opt.x);}
			if(x < 0){x=0;}
			}
		if(undefined != opt.y){
			//if y begins with a + or -, then add it
			yvalue=opt.y+'';
			if(yvalue.indexOf('+') != -1){y=Math.round(MouseY+parseInt(yvalue,10));}
			else if(yvalue.indexOf('-') != -1){y=Math.round(MouseY-Math.abs(parseInt(yvalue,10)));}
			else{y=Math.round(opt.y);}
			if(y < 0){y=0;}
			}
		if(x < 0){x=0;}
		if(y < 0){y=0;}
		//alert(x+','+y);
		masterdiv.style.position='absolute';
		masterdiv.style.left=x+"px";
		masterdiv.style.top=y+"px";
		}
	/* check for botright option */
	else if(param.topright){
		masterdiv.style.position='absolute';
		masterdiv.style.top=param.topright+"px";
		masterdiv.style.right=param.topright+"px";
		}
	/* check for botleft option */
	else if(param.topleft){
		masterdiv.style.position='absolute';
		masterdiv.style.top=param.topleft+"px";
		masterdiv.style.left=param.topleft+"px";
		}
	/* check for botright option */
	else if(param.botright){
		masterdiv.style.position='absolute';
		masterdiv.style.bottom=param.botright+"px";
		masterdiv.style.right=param.botright+"px";
		}
	/* check for botleft option */
	else if(param.botleft){
		masterdiv.style.position='absolute';
		masterdiv.style.bottom=param.botleft+"px";
		masterdiv.style.left=param.botleft+"px";
		}
	else if(opt.screen){
		showOnScreen(masterdiv);
     	}
    else if(opt.mouse){
        x=0;
		y=0;
		cvalue=opt.mouse+'';
		if(cvalue.indexOf('x') != -1){
			//only center x - make y MouseY
			x=MouseX;
        	}
        else if(cvalue.indexOf('y') != -1){
			//only center y - make x MouseX
			y=MouseY;
        	}
		//check for x and y
		if(undefined != opt.x){
			//if x begins with a + or -, then add it
			xvalue=opt.x+'';
			if(xvalue.indexOf('+') != -1){x=Math.round(MouseX+parseInt(xvalue,10));}
			else if(xvalue.indexOf('-') != -1){x=Math.round(MouseX-Math.abs(parseInt(xvalue,10)));}
			else{x=MouseX;}
			}
		if(undefined != opt.y){
			//if y begins with a + or -, then add it
			yvalue=opt.y+'';
			if(yvalue.indexOf('+') != -1){y=Math.round(MouseY+parseInt(yvalue,10));}
			else if(yvalue.indexOf('-') != -1){y=Math.round(MouseY-Math.abs(parseInt(yvalue,10)));}
			else{y=MouseY;}
			}
		//window.status=x+','+y;
		if(x < 0){x=0;}
		if(y < 0){y=0;}
    	masterdiv.style.top=y+"px";
    	masterdiv.style.left=x+"px";
    	}
    else{
		if(undefined != opt.x){masterdiv.style.left=opt.x+"px";}
		if(undefined != opt.y){masterdiv.style.top=opt.y+"px";}
    	}
    if(opt.showtime){
		//remove the div if mouse is not in the div,
		//	otherwise until after they have moved mouse out and timeout has expired.
		t=Math.round(opt.showtime*1000);
		popupdiv_timeout=setTimeout("removeDivOnExit('"+opt.id+"',1)",t);
    	}
    else if(opt.fade){
		masterdiv.onmouseout=function(e){
			if(undefined == e){e = fixE(e);}
			if(undefined != e){
				if(checkMouseLeave(this,e)){
					//alert('mouse left - 1');
					fadeId(this.id,1);
					}
				}
			//else{fadeId(this.id,1);}
			};
    	}
}
function createTable(){
	var t  = document.createElement("table");
    tb = document.createElement("tbody");
    t.setAttribute("border","1");
    var tr = document.createElement("tr");
    var td ;
    var d;
	d = document.createElement("div");
    d.style.backgroundColor = "red";
    d.style.minHeight = "20px";
    d.style.width = "50px";
    td = document.createElement("td");
    td.appendChild(d);

    tr.appendChild(td);
	d = document.createElement("div");
    d.style.backgroundColor = "green";
    d.style.minHeight = "20px";
    d.style.width = "50px";
    td = document.createElement("td");
    td.appendChild(d);
    tr.appendChild(td);
	tb.appendChild(tr);
    t.appendChild(tb);
    //alert(t);
    document.getElementById("ajaxstatus").appendChild(t);

 }
function removeDiv(divid){
	//info: removes specified id
	return removeId(divid);
	}
function removeId(divid){
	//info: removes specified id
	var obj=getObject(divid);
	if(undefined == obj){return false;}
	try{
		obj.remove();
		if(undefined == obj){return true;}
	}
	catch(e){}
	try{
		if(undefined != obj.parentNode){
			obj.parentNode.removeChild(obj);
			if(undefined == obj){return true;}
		}
	}
	catch(e){}
	try{
		document.body.removeChild(obj);
    	if(undefined == obj){return true;}
	}
	catch(e){}
	try{
		document.getElementsByTagName('BODY')[0].removeChild(obj);
    	if(undefined == obj){return true;}
	}
	catch(e){}
	try{
    	obj.parentNode.removeChild(obj);
    	if(undefined == obj){return true;}
	}
	catch(e){}
    return false;
}
function removeDivOnExit(divid,fade){
	//info: removes specified id when the mouse cursor exits the area
	var obj=getObject(divid);
	if(undefined == obj){return;}
	if(!isMouseOver(divid)){
		//alert('mouse left - 2:'+divid);
		if(undefined != fade && fade==1){
			fadeId(divid,1);
			}
		else{removeDiv(divid);}
		return;
		}
	if(undefined != fade && fade==1){
		obj.onmouseout=function(e){
			if(undefined == e){e = fixE(e);}
			if(undefined != e){
				if(checkMouseLeave(this,e)){
					//alert('mouse left - 3');
					fadeId(this.id,1);
					}
				}
			//else{fadeId(this.id,1);}
			};
		}
	else{
		obj.onmouseout=function(e){
			if(undefined == e){e = fixE(e);}
			if(undefined != e){
				if(checkMouseLeave(this,e)){
					removeDiv(this.id);
					}
				}
			//else{removeDiv(this.id);}
			}
		};
	}
/* isMouseOver - returns true the mouse if over this object*/
function isMouseOver(id){
	//info: returns true the mouse if over this object
	var exy = getXY(id);
	if(undefined == exy){return true;}
	var ewh = getWidthHeight(id);
	//alert(MouseX+','+MouseY);
	//showProperties(exy);
	//showProperties(ewh);
	if (MouseX >= exy[0] && MouseX <= exy[0]+ewh[0] && MouseY >= exy[1] && MouseY <= exy[1]+ewh[1]){return true;}
	return false;
	}
function getChildById(obj, id) {
	if (obj.id == id){return obj;}
	if (obj.hasChildNodes()) {
		for (var i=0; i<obj.childNodes.length; i++) {
			var child = getChildById(obj.childNodes[i], id);
			if (child !== null){return child;}
			}
		}
	return null;
	}
/* centerObject */
function setObjectPos(obj,x,y){
	var sObj=getObject(obj);
	if(undefined == sObj){return false;}
	if(undefined == x){x=MouseX || 10;}
	if(undefined == y){y=MouseY || 10;}
  	sObj.style.position='absolute';
  	sObj.style.left=x+'px';
  	sObj.style.top=y+'px';
  	return new Array(x,y);
	}
/* centerObject */
function centerObject(obj,fade){
	//info: centers specified object or id
	if(undefined == fade){fade=0;}
	var sObj=getObject(obj);
	if(undefined == sObj){return false;}
	var w=getWidth(sObj);
	var h=getHeight(sObj);
	window.status=obj+':'+w+','+h;
	//var whx=getWidthHeight(sObj);
	//window width and height
	var ww=getViewportWidth();
	var wh=getViewportHeight();
	//scroll width and height
	var sw=getScrollWidth();
	var sh=getScrollHeight();
	var x = Math.round((ww / 2) - (w / 2)) + sw;
  	var y = Math.round((wh / 2) - (h / 2)) + sh;
  	//window.status='centerObject: '+sObj.id+' w,h:'+w+','+h+' window:'+ww+','+wh+',scroll:'+sw+','+sh+','+x+','+y;
  	sObj.style.position='absolute';
  	sObj.style.left=x+'px';
  	if(undefined == y){y=10;}
	if(y < 10){y=10;}
  	sObj.style.top=y+'px';
  	if(fade==1){
    	sObj.onmouseout=function(e){
			if(undefined == e){e = fixE(e);}
			if(undefined != e){
				if(checkMouseLeave(this,e)){fadeId(this.id,1);}
			}
		};
	}
  	return new Array(x,y);
	}
/* hideOnExit */
function hideOnExit(obj){
	//info: hides specified id when the mouse cursor exits the area
	var sObj=getObject(obj);
	if(undefined == sObj){return false;}
	sObj.onmouseout=function(e){
		if(undefined == e){e = fixE(e);}
		if(undefined != e){
			if(checkMouseLeave(this,e)){
				this.style.display='none';
			}
		}
	};
	return false;
}
/* showOnScreen */
function showOnScreen(obj){
	//info: forces placement of object on screen
	var sObj=getObject(obj);
	if(undefined == sObj){return false;}
	//if(sObj.style.display=='block'){return true;}
	//if the object is set to display:none it will have a 0 width and height - visibility lets us capture w and h
	sObj.style.position='absolute';
	sObj.style.visibility='hidden';
	sObj.style.display='block';
	//get object's width and height
	var w=getWidth(sObj);
	var h=getHeight(sObj);
	//get screen width and height
	var screen=getViewPort();
	var sw=getWidth();
	var sh=getHeight();
	//get cursor position
	var x=cursor.x;
	var y=cursor.y;
	/* set x */
	if(x+w+20 > sw){
		var z=x-w;
		while(z < 0){z++;}
		x = z;
		}
	/* set y */
	if(y+h+20 > sh){
		z=y-h;
		while(z < 0){z++;}
		y = z;
		}
	//set object's new position
	sObj.style.left=x+'px';
  	sObj.style.top=y+'px';
  	sObj.style.visibility='visible';
  	return new Array(x,y);
   	}
/*getViewPort - Space within the browser window is known as the 'viewport' */
function getViewPort(){
	var viewport={};
	if (typeof window.innerWidth != 'undefined')
	 {
	      viewport.w = window.innerWidth;
	      viewport.h = window.innerHeight;
	 }

	// IE6 in standards compliant mode (i.e. with a valid doctype as the first line in the document)

	 else if (typeof document.documentElement != 'undefined' 
	     && typeof document.documentElement.clientWidth !=
	     'undefined' && document.documentElement.clientWidth != 0)
	 {
	       viewport.w = document.documentElement.clientWidth;
	       viewport.h = document.documentElement.clientHeight;
	 }

	 // older versions of IE

	 else
	 {
	       viewport.w = document.getElementsByTagName('body')[0].clientWidth;
	       viewport.h = document.getElementsByTagName('body')[0].clientHeight;
	 }
  return viewport;
  }
// Menu function to assign hover to li and hide w_select select tags on hover
sfHover = function() {
	//assign hover to li and hide w_select select tags on hover
	var navEls = GetElementsByAttribute('ul', 'id', 'w_nav');
	for (var n=0; n<navEls.length; n++){
		var sfEls = navEls[n].getElementsByTagName("LI");
		for (var i=0; i<sfEls.length; i++){
			sfEls[i].onmouseover=function(){
				this.className="sfhover";
				};
			sfEls[i].onmouseout=function(){
				this.className="";
				};
			}
		}
	};
// Add sfHover to the onLoad queue
addEventHandler(window,'load',sfHover);
var calledStickyMenus=0;
/* initPin - function to assign hover to dom objects that have data-behavior="pin" so they hide onMouseOut */
function initBehaviors(ajaxdiv){
	//info: initializes special data-behavior atrributes
	//assign hover to li and hide w_select select tags on hover
	//info: 	_behavior="clock" id="clockid"
	//usage:	<div data-behavior="menu" display="menuid">MouseOve</div><br><div id="menuid">This is the menu that is displayed</div>
	//	<div data-behavior="@math(one+(two*three))"></div>
	//	<div data-behavior="@sum(one:two:three)"></div>
	//	<div data-behavior="@raid(raidid)"></div><input type="text" name="raidid" value="123">
	//replace title attributes with ours
	try{f_tcalInit();}catch(e){}
	//bootstrap toggles
	var buttons=document.querySelectorAll('[data-toggle="buttons"] .btn');
	for(var i=0;i<buttons.length;i++){
		buttons[i].onclick=function(){
			//get all children of the parentNode
			var kids=this.parentNode.childNodes;
			for(var k=0;k<kids.length;k++){
            	removeClass(kids[k],'active');
			}
			addClass(this,'active');
		};
	}
	if(!window.jQuery){
		//look for data-toggle modal
		var navbars=document.querySelectorAll('[data-toggle="modal"]');
		for (var n=0; n<navbars.length; n++){
	    	navbars[n].onclick=function(e){
				cancel(e);
	        	var t=this.getAttribute('data-target');
	        	var tdiv=getObject(t);
	        	var state=tdiv.getAttribute('aria-hidden');
	        	switch(state.toLowerCase()){
	            	case 'f':
	            	case 'false':
	            	case 0:
	            		tdiv.setAttribute('aria-hidden','true');
	            		removeClass(tdiv,'in');
	            	break;
	            	default:
	            		tdiv.setAttribute('aria-hidden','false');
	            		addClass(tdiv,'in');
	            	break;
				}
				return false;
			};
		}
		//look for data-toggle modal
		navbars=document.querySelectorAll('[data-toggle="dropdown"]');
		for (n=0; n<navbars.length; n++){
	    	navbars[n].onclick=function(e){
				cancel(e);
	        	var p=getParent(this);
	        	if(undefined != p){
	            	if(p.className.indexOf('open')==-1){
	                	addClass(p,'open');
					}
					else{
						removeClass(p,'open');
					}
				}
				return false;
			};
		}
		//look for bootstrap navbars with a collapse toggle attribute and hook the onclick
		navbars=document.querySelectorAll('[data-toggle="collapse"]');
		for (n=0; n<navbars.length; n++){
	    	navbars[n].onclick=function(){
	        	var t=this.getAttribute('data-target');
	        	if(undefined == t){return false;}
	        	var tdiv=document.querySelector(t);
	        	if(undefined == tdiv){return false;}
	        	var state=this.getAttribute('aria-expanded');
	        	if(undefined == 'state'){state='';}
	        	if(state===null){state='';}
	        	switch(state.toLowerCase()){
	            	case 'f':
	            	case 'false':
	            	case '0':
	            	case '':
	            		this.setAttribute('aria-expanded','true');
	            		addClass(tdiv,'in');
	            	break;
	            	default:
	            		this.setAttribute('aria-expanded','false');
	            		removeClass(tdiv,'in');
	            	break;
				}
				return false;
			};
		}
	}
	//
	eventInitSticky();

	var navEls = GetElementsByAttribute('*', 'data-behavior', '.+');
	var navEls2 = GetElementsByAttribute('*', '_behavior', '.+');
	//backwords compatibility - get _behavior attributes also
	for (n=0; n<navEls2.length; n++){
		navEls2[n].setAttribute('data-behavior',navEls2[n].getAttribute('_behavior'));
		navEls2[n].removeAttribute('_behavior');
    	navEls[navEls.length]=navEls2[n];
	}
	//alert(navEls.length+' objects have behaviors');
	for (n=0; n<navEls.length; n++){
		var str=navEls[n].getAttribute('data-behavior').toLowerCase();
		var behaviors=str.split(/[\ \;\,\:]+/);
		if(in_array("ajax",behaviors)){
			/* AJAX - Updates div with ajax call every refresh seconds. data-behavior="ajax" url="" id="mytest" timer="20" */
  			var attr=getAllAttributes(navEls[n]);
  			if(undefined != attr['id'] && (undefined != attr['url'] || undefined != attr['data-url'] || undefined != attr['data-function']) && (undefined != attr['data-timer'] || undefined != attr['timer'])){
				ajaxTimer(attr['id']);
			}
		}
		if(in_array("animate",behaviors)){
			/* ANIMATE - get id and head */
            addEventHandler(navEls[n],'mouseover',function(e){
				animateGrow(this.id,parseInt(this.getAttribute('min'),10),parseInt(this.getAttribute('max'),10));
			});
			addEventHandler(navEls[n],'mouseout',function(e){
				if(undefined == e){e = fixE(e);}
				if(undefined != e){
					if(checkMouseLeave(this,e)){
						animateShrink(this.id,parseInt(this.getAttribute('max'),10),parseInt(this.getAttribute('min'),10));
					}
				}
			});
        }
        if(in_array("autogrow",behaviors)){
			/* AUTOGROW - textarea will auto based on content */
			navEls[n].setAttribute('data-autogrow',navEls[n].style.height);
            addEventHandler(navEls[n],'focus',function(e){
				autoGrow(this);
			});
			addEventHandler(navEls[n],'keypress',function(e){
				autoGrow(this);
			});
			addEventHandler(navEls[n],'blur',function(e){
				var h=this.getAttribute('data-autogrow');
				this.style.height=h;
			});
        }
        if(in_array("chart",behaviors)){
			/* Chart using Chart.js */
			var chart_type=navEls[n].getAttribute('data-type') || 'bar';
			chart_type=chart_type.toLowerCase();
			//labels
			var chart_labels=navEls[n].getAttribute('data-labels') || '';
			chart_labels=chart_labels.split(',');
			//showProperties(chart_labels,'debug',1);return;
			//datasets
			var chart_datasets=new Array();
			var chart_id=navEls[n].getAttribute('data-datasets') || '';
			var chart_ids=GetElementsByAttribute('div', 'id', chart_id);
			//showProperties(chart_ids,'debug',1);
			for (i=0; i<chart_ids.length; i++) {
				var txt=getText(chart_ids[i]);
				var dataset=parseJSONString(txt);
				chart_datasets.push(dataset);
			}
			//showProperties(chart_datasets,'debug',1);
			var chart_data={
				labels:chart_labels,
				datasets:chart_datasets
			};
			var chart_options=navEls[n].getAttribute('data-options') || '';
			if(undefined != document.getElementById(chart_options)){
            	chart_options=getText(chart_options);
			}
			chart_options=parseJSONString(chart_options);
			var myLine;
			switch(chart_type){
				case 'bar':
					myLine = new Chart(navEls[n].getContext("2d")).Bar(chart_data,chart_options);
				break;
				case 'line':
					myLine = new Chart(navEls[n].getContext("2d")).Line(chart_data,chart_options);
				break;
				case 'pie':
					myLine = new Chart(navEls[n].getContext("2d")).Pie(chart_data,chart_options);
				break;
				default:
					myLine='';
				break;
			}
			//save as an image if requested
			var chart_image=navEls[n].getAttribute('data-image') || '';
			if(chart_image.length){
				var img=navEls[n].toDataURL();
            	setText(chart_image,img);
			}
		}
        if(in_array("clock",behaviors)){
			/*CLOCK - */
  			var id=navEls[n].getAttribute('id');
			if(id){startClock(id,1);}
		}
		if(in_array("clone",behaviors)){
			/*CLONE DIV - */
			cloneDiv(navEls[n]);
		}
		if(in_array("utcclock",behaviors)){
			/*UTC CLOCK - */
  			var id=navEls[n].getAttribute('id');
			if(id){startUTCClock(id,1);}
		}
		else if(in_array("countdown",behaviors)){
			/* COUNTDOWN */
  			var id=navEls[n].getAttribute('id');
			if(id){countDown(id);}
		}
		else if(in_array("countdowndate",behaviors)){
			/* COUNTDOWNDATE */
  			var id=navEls[n].getAttribute('id');
			if(id){
				var yr=navEls[n].getAttribute('data-year');
				if(undefined != yr){yr=navEls[n].getAttribute('year');}
				var mon=navEls[n].getAttribute('data-month');
				if(undefined != mon){mon=navEls[n].getAttribute('month');}
				var day=navEls[n].getAttribute('data-day');
				if(undefined != day){day=navEls[n].getAttribute('day');}
				var hr=navEls[n].getAttribute('data-hour');
				if(undefined != hr){hr=navEls[n].getAttribute('hour');}
				var m=navEls[n].getAttribute('minute');
				if(undefined != m){m=navEls[n].getAttribute('minute');}
				var t=navEls[n].getAttribute('tz');
				if(undefined != t){t=navEls[n].getAttribute('tz');}
				countDownDate(id,yr,mon,day,hr,m,t);
			}
		}
		if(in_array("drag",behaviors)){
			/* DRAG - Make object draggable */
			var head=navEls[n].getAttribute('data-head');
			if(undefined == head){head=navEls[n].getAttribute('head');}
			var headobj=getObject(head);
			navEls[n].style.position='relative';
            if(undefined == headobj){
				//alert('drag behavior error. no head defined: '+navEls[n].getAttribute('id'));
				Drag.init(navEls[n]);
			}
			else{
            	Drag.init(headobj,navEls[n]);
   			}
        }
        if(in_array("email-link",behaviors)){
			/* email link:  /info/wasql/com/  */
			var email=getText(navEls[n]);
			email=email.replace(/^[\/]+/,'',email);
			email=email.replace(/[\/]+$/,'',email);
			email=email.replace(/[\/]/,'@',email);
			email=email.replace(/[\/]/,'.',email);
			navEls[n].href='mailto:'+email;
			setText(navEls[n],email);
			navEls[n].setAttribute('data-behavior','processed');
		}
		if(in_array("loadtextfile",behaviors)){
			loadTextFileInit(navEls[n]);
		}
		if(in_array("markers",behaviors)){
			wasqlMarkerInit(navEls[n]);
		}
        if(in_array("signature",behaviors)){
			/* Signature */
			var pencolor=navEls[n].getAttribute('data-color') || '#000';
			signaturePad = new SignaturePad(navEls[n],{penColor:pencolor});
			//console.log(navEls[n]);

		}
		if(in_array("csseditor",behaviors)){
			/* EDITOR: CSSEDITOR */
			codemirrorTextEditor(navEls[n],'text/css','csseditor');
		}
		else if(in_array("diffeditor",behaviors)){
			/* EDITOR: DIFF Mode */
			codemirrorTextEditor(navEls[n],"text/x-diff",'diffeditor');
		}
		else if(in_array("txteditor",behaviors)){
			/* EDITOR: TXTEDITOR */
			codemirrorTextEditor(navEls[n],'text/plain','txteditor');
		}
		else if(in_array("javascript",behaviors) || in_array("jseditor",behaviors)){
			/* EDITOR: JSEditor */
			codemirrorTextEditor(navEls[n],'text/javascript','jseditor');
		}
		else if(in_array("perleditor",behaviors)){
			/* EDITOR: PERLEditor */
			codemirrorTextEditor(navEls[n],"text/x-perl",'perleditor');
		}
		else if(in_array("phpeditor",behaviors)){
			/* EDITOR: PHPEditor */
			codemirrorTextEditor(navEls[n],"application/x-httpd-php",'phpeditor');
		}
		else if(in_array("rubyeditor",behaviors)){
			/* EDITOR: RUBYEditor */
			codemirrorTextEditor(navEls[n],"text/x-ruby",'rubyeditor');
		}
		else if(in_array("sqleditor",behaviors)){
			/* EDITOR: SQLEditor */
			codemirrorTextEditor(navEls[n],"text/x-mysql",'sqleditor');
		}
		else if(in_array("vbscript",behaviors) || in_array("vbeditor",behaviors)){
			/* EDITOR: VBEditor  */
			codemirrorTextEditor(navEls[n],"text/vbscript",'vbeditor');
		}
		else if(in_array("richtext",behaviors) || in_array("wysiwyg",behaviors) || in_array("nicedit",behaviors) || in_array("tinymce",behaviors)){
			//EDITOR: WSIWYG or NICEDIT or TINYMCE
			var id=navEls[n].getAttribute('id');
			var h=getHeight(navEls[n]);
			//set max height so that you can still see the panel
			var m=500;
			if(undefined != h && h < m){m=h;}
			if(undefined != nicEditors[id]){
				nicEditors[id].removeInstance(id);
			}
			nicEditors[id] = new nicEditor({fullPanel : true, maxHeight:m}).panelInstance(id,{hasPanel : true, maxHeight:m});
		}
		else if(in_array("xmleditor",behaviors)){
			/* EDITOR: XMLEditor */
			codemirrorTextEditor(navEls[n],{name: "xml", alignCDATA: true},'xmleditor');
		}
		if(in_array("tab_enable",behaviors)){
			/* Enable tabs - */
			navEls[n].onkeydown = function(e){
				if(e.keyCode==9 || e.which==9){
					e.preventDefault();
					var s = this.selectionStart;
					this.value = this.value.substring(0,this.selectionStart) + "\t" + this.value.substring(this.selectionEnd);
					this.selectionEnd = s+1; 
				}
			}
		}
		if(in_array("zoom",behaviors)){
			/*zoom - optional attributes:  data-zoomsrc, id, data-zoomalwaysshow */
			if(undefined == navEls[n].getAttribute('id')){
				navEls[n].setAttribute('id',guid());
			}
			if(undefined == navEls[n].getAttribute('data-zoomsrc')){
				/*set the zoom image to the src image */
				navEls[n].setAttribute('data-zoomsrc',navEls[n].src);
			}
    		MojoZoom.makeZoomable(navEls[n], navEls[n].getAttribute('data-zoomsrc'), document.getElementById(navEls[n].getAttribute("id") + "_zoom"), null, null, navEls[n].getAttribute("data-zoomalwaysshow")=="true");
		}
        if(in_array("fileupload",behaviors)){
			/*FILEUPLOAD - HTMl5 only - drag files here to upload */
			if (window.File && window.FileReader && window.FileList && window.Blob) {
				//save original background color
				navEls[n].setAttribute('_bgcolor',navEls[n].style.backgroundColor || '');
  				//add event listeners
  				addEventHandler(navEls[n],"dragover",function(evt){
					cancelBubble(evt);
					}
				);
				addEventHandler(navEls[n],"dragexit", function(evt){
					cancelBubble(evt);
						var bgcolor='';
						if(undefined != this.getAttribute('_dragcolor_out')){bgcolor=this.getAttribute('_dragcolor_out');}
						this.style.backgroundColor=bgcolor;
					}
				);
				addEventHandler(navEls[n],"dragenter", function(evt){
					cancelBubble(evt);
					//change background of div when files are dragged over it to signify it accepts files
					this.style.backgroundColor=this.getAttribute('data-color-over') || 'green';
					}, false);
				addEventHandler(navEls[n],"drop", function(evt){
					// get window.event if e argument missing (in IE)
					evt = evt || window.event;
					// stops the browser from redirecting off to the image.
		            cancelBubble(evt);
					this.style.backgroundColor=this.getAttribute('_bgcolor');
					fileUploadBehavior(evt,this);
					},false);
			}
			else{
				setText(navEls[n],'Fileupload via dragdrop is not supported in your browser.');
            }
        }
        if(in_array("float",behaviors)){
			/* FLOAT */
			var id=navEls[n].getAttribute('id');
			if(id){
				var t=navEls[n].getAttribute('data-top');
				if(undefined != t){t=navEls[n].getAttribute('top');}
				floatDiv(id, t);
            }
		}
		if(in_array("marquee",behaviors)){
			/* MARQUEE - turns text into a scrolling marquee. data-behavior="marquee" timer="2" */
  			var attr=getAllAttributes(navEls[n]);
  			if(undefined != attr['id']){
               	marquee(attr['id']);
			}
		}
		if(in_array("pin",behaviors)){
			/*PIN - */
			addEventHandler(navEls[n],'mouseout',function(e){
				if(undefined == e){e = fixE(e);}
				if(undefined != e){
					if(checkMouseLeave(this,e)){
						this.style.display='none';
						var onhide=this.getAttribute('onhide');
						if(onhide){eval(onhide);}
					}
				}
			});
        }
		else if(in_array("dropdown",behaviors)){
			/* Dropdown MENU - */
  			var dname=navEls[n].getAttribute('data-display');
  			if(undefined == dname){dname=navEls[n].getAttribute('display');}
			if(dname){
				navEls[n].style.position='relative';
				addEventHandler(navEls[n],'click',function(e){
					var dname=this.getAttribute('data-display');
					if(undefined == dname){dname=this.getAttribute('display');}
					dObj=getObject(dname);
					if(dObj){
						if(dObj.style.display == 'block'){return true;}
						dObj.style.display='block';
						dObj.style.position='absolute';
				    	dObj.style.bottom="-199px";
				    	dObj.style.height="200px";
				    	dObj.style.left="0px";
				    	dObj.style.zIndex='999999';
				    	dObj.style.backgroundColor='#FFF';
					}
                });
                addEventHandler(navEls[n],'mouseout',function(e){
					if(undefined == e){e = fixE(e);}
					if(undefined != e){
						if(checkMouseLeave(this,e)){
							var dname=this.getAttribute('data-display');
							if(undefined == dname){dname=this.getAttribute('display');}
							dObj=getObject(dname);
							if(dObj){
								var hide=0;
								if(undefined != dObj.className){
									if(dObj.className.indexOf("current") == -1){hide++;}
                                }
								else{
									var cclass=dObj.getAttribute('class');
									if(undefined == cclass){hide++;}
									else{
										if(cclass.indexOf("current") == -1){hide++;}
	                                }
								}
                                if(hide){dObj.style.display='none';}
							}
							var onhide=this.getAttribute('onhide');
							if(onhide){eval(onhide);}
						}
					}
				});
            }
		}
		else if(in_array("menu",behaviors)){
			/* MENU - */
  			var dname=navEls[n].getAttribute('data-display');
  			if(undefined == dname){dname=navEls[n].getAttribute('display');}
			if(dname){
				addEventHandler(navEls[n],'click',function(e){
					var dname=this.getAttribute('data-display');
					if(undefined == dname){dname=this.getAttribute('display');}
					dObj=getObject(dname);
					if(dObj){
						if(dObj.style.display == 'block'){return true;}
						dObj.style.display='block';
					}
					var dmouse=this.getAttribute('mouse');
					var dx=this.getAttribute('x');
					var dy=this.getAttribute('y');
					if(undefined != dmouse){
						//position
						var x=0;
						var y=0;
						if(dmouse.indexOf('x') != -1){
							//only center x - make y MouseY
							x=MouseX;
				        }
				        else if(dmouse.indexOf('y') != -1){
							//only center y - make x MouseX
							y=MouseY;
				        }
				        else{
                            x=MouseX;
                            y=MouseY;
                        }
						//check for x and y
						if(undefined != dx){
							//if x begins with a + or -, then add it
							xvalue=dx+'';
							if(xvalue.indexOf('+') != -1){x=Math.round(MouseX+parseInt(xvalue));}
							else if(xvalue.indexOf('-') != -1){x=Math.round(MouseX-Math.abs(parseInt(xvalue)));}
							else{x=Math.round(Math.abs(parseInt(xvalue)));}
						}
						if(undefined != dy){
							//if y begins with a + or -, then add it
							yvalue=dy+'';
							if(yvalue.indexOf('+') != -1){y=Math.round(MouseY+parseInt(yvalue));}
							else if(yvalue.indexOf('-') != -1){y=Math.round(MouseY-Math.abs(parseInt(yvalue)));}
							else{y=Math.round(Math.abs(parseInt(yvalue)));}
						}
                        dObj.style.position='absolute';
				    	dObj.style.top=y+"px";
				    	dObj.style.left=x+"px";
				    	//window.status="Set menu postion to "+x+','+y;
                    }
                });
                addEventHandler(navEls[n],'mouseover',function(e){
					var dname=this.getAttribute('data-display');
					if(undefined == dname){dname=this.getAttribute('display');}
					dObj=getObject(dname);
					if(dObj){
						if(dObj.style.display == 'block'){return true;}
						dObj.style.display='block';
					}
					var dmouse=this.getAttribute('mouse');
					var dx=this.getAttribute('x');
					var dy=this.getAttribute('y');
					if(undefined != dmouse){
						//position
						var x=0;
						var y=0;
						if(dmouse.indexOf('x') != -1){
							//only center x - make y MouseY
							x=MouseX;
				        }
				        else if(dmouse.indexOf('y') != -1){
							//only center y - make x MouseX
							y=MouseY;
				        }
				        else{
                            x=MouseX;
                            y=MouseY;
                        }
						//check for x and y
						if(undefined != dx){
							//if x begins with a + or -, then add it
							xvalue=dx+'';
							if(xvalue.indexOf('+') != -1){x=Math.round(MouseX+parseInt(xvalue));}
							else if(xvalue.indexOf('-') != -1){x=Math.round(MouseX-Math.abs(parseInt(xvalue)));}
							else{x=Math.round(Math.abs(parseInt(xvalue)));}
						}
						if(undefined != dy){
							//if y begins with a + or -, then add it
							yvalue=dy+'';
							if(yvalue.indexOf('+') != -1){y=Math.round(MouseY+parseInt(yvalue));}
							else if(yvalue.indexOf('-') != -1){y=Math.round(MouseY-Math.abs(parseInt(yvalue)));}
							else{y=Math.round(Math.abs(parseInt(yvalue)));}
						}
                        dObj.style.position='absolute';
				    	dObj.style.top=y+"px";
				    	dObj.style.left=x+"px";
				    	//window.status="Set menu postion to "+x+','+y;
                    }
                });
                addEventHandler(navEls[n],'mouseout',function(e){
					if(undefined == e){e = fixE(e);}
					if(undefined != e){
						if(checkMouseLeave(this,e)){
							var dname=this.getAttribute('data-display');
							if(undefined == dname){dname=this.getAttribute('display');}
							dObj=getObject(dname);
							if(dObj){
								var hide=0;
								if(undefined != dObj.className){
									if(dObj.className.indexOf("current") == -1){hide++;}
                                }
								else{
									var cclass=dObj.getAttribute('class');
									if(undefined == cclass){hide++;}
									else{
										if(cclass.indexOf("current") == -1){hide++;}
	                                }
								}
                                if(hide){dObj.style.display='none';}
							}
							var onhide=this.getAttribute('onhide');
							if(onhide){eval(onhide);}
						}
					}
				});
            }
		}
		if(in_array("scrolltable",behaviors)){
			/* SCROLLTABLE  */
			var id=navEls[n].getAttribute('id');
  			var h=navEls[n].getAttribute('data-scrollheight');
  			if(undefined == h){h=navEls[n].getAttribute('scrollheight');}
  			var w=navEls[n].getAttribute('data-scrollwidth');
  			if(undefined == w){w=navEls[n].getAttribute('scrollwidth');}
			if(id){scrollableTable(navEls[n],h,w);}
		}
		if(in_array("slideshow",behaviors)){
			/* SLIDESHOW */
			var id=navEls[n].getAttribute('id');
			if(id){
				addClass(navEls[n],'w_slideshow');
				var t=navEls[n].getAttribute('data-timer');
				if(undefined == t){t=navEls[n].getAttribute('timer');}
				if(undefined == t){t=15;}
				//add navigation
				var navobj=getObject(id+'_nav');
				if(undefined != navobj){
					var tag=navEls[n].getAttribute('data-tag');
					if(undefined == tag){tag='img';}
					var objs=navEls[n].getElementsByTagName(tag);
					if(objs.length!=0){
                    	var txt='';
                    	for(var n=0;n<objs.length;n++){
							var navtitle=objs[n].getAttribute('title');
							if(undefined == navtitle){navtitle='';}
							if(t){
                        		txt+='<div id="'+id+'_nav_'+n+'" data-tooltip="'+navtitle+'" data-tooltip_position="bottom" class="" onclick="slideShow(\''+id+'\','+n+','+t+');"></div>';
							}
							else{
								txt+='<div id="'+id+'_nav_'+n+'" data-tooltip="'+navtitle+'" data-tooltip_position="bottom" class=""  onclick="slideShow(\''+id+'\','+n+');"></div>';
							}
						}
						setText(navobj,txt);
					}
				}
				slideShow(id,0,t);
			}
		}
		if(in_array("sticky",behaviors)){
			/* STICKY - makes menu sticky even when scrolling past it. data-behavior="sticky" */
  			var pos=getPos(navEls[n]);
  			var wh=getWidthHeight(navEls[n]);
  			navEls[n].setAttribute('sticky_y',pos.y+wh[1]);
			navEls[n].setAttribute('sticky_x',pos.x+wh[0]);
			if(undefined != navEls[n].style.zIndex){
				navEls[n].setAttribute('sticky_z',navEls[n].style.zIndex);
			}
			if(calledStickyMenus==0){
				addEventHandler(window,'scroll',stickyMenus);
				calledStickyMenus=1;
			}
		}
		if(in_array("stopwatch",behaviors)){
			/* STOPWATCH */
  			var id=navEls[n].getAttribute('id');
			if(id){stopWatch(id,0);}
		}
		else if(in_array("timer_verbose",behaviors)){
			/* Timer Verbose */
  			var id=navEls[n].getAttribute('id');
			if(undefined != id){
				var t=parseInt(getText(navEls[n]),10);
				if(!isNaN(t)){
					navEls[n].setAttribute('timer_verbose',t);
					timerVerbose(id,1);
				}	
			}
		}
		else if(in_array("time_verbose",behaviors)){
			/* Time Verbose */
			var s=getText(navEls[n]);
			if(!isNaN(s)){
				var secs=parseInt(s,10);
				if(!isNaN(secs)){
					var t=verboseTime(secs);
					if(t.length){
						setText(navEls[n],t);
					}
				}
			}
		}
		else if(in_array("time",behaviors)){
			/* TIME */
  			var id=navEls[n].getAttribute('id');
			if(id){startClock(id,0);}
		}
		if(in_array("tab",behaviors)){
			/* Enable tab in box */
			addEventHandler(navEls[n],'keydown',function(e){
		        if(e.keyCode==9 || e.which==9){
		            e.preventDefault();
		            var s = this.selectionStart;
		            this.value = this.value.substring(0,this.selectionStart) + "\t" + this.value.substring(this.selectionEnd);
		            this.selectionEnd = s+1;
		        }
		    });
		}
		/*	Check for @math(..)  @sum(..)
			@sum(one:two:three)
			@math(one+two+three)
			str.replace(/microsoft/, "W3Schools")
		*/
		for(b in behaviors){
			var behavior=behaviors[b];
			//alert(behavior);
			var id=navEls[n].getAttribute('id');
			var re = new RegExp('\@([a-z]+)[(](.+)[)]', 'igm');
        	var res = re.exec(behavior);
	        if (res && res.length > 0){
				var func=res[1].toLowerCase();
				var str=res[2].toLowerCase();
				switch (func){
					case 'sum':
						var result=0;
						var sids=str.split(/[,:\s]+/);
						for (var s=0; s<sids.length; s++) {
							result += Math.round(getText(sids[s]));
	                    	}
	                    setText(navEls[n],result);
						break
					case 'math':
						doMath(id);
						break
					case 'raid':
						var cObj=getObject(str);
						if(typeof(cObj)=='object'){
							setText(cObj,getText(navEls[n]));
							navEls[n].setAttribute('raidid',str);
							addEventHandler(navEls[n],'keyup',function(){
								var raidid = this.getAttribute('raidid');
								setText(raidid,getText(this));
							});
							addEventHandler(navEls[n],'change',function(){
								var raidid = this.getAttribute('raidid');
								setText(raidid,getText(this));
							});
							addEventHandler(navEls[n],'blur',function(){
								var raidid = this.getAttribute('raidid');
								setText(raidid,getText(this));
							});
      					}
						break
					}
	        	}
			}
       }
    //data tooltips
	var tobs=GetElementsByAttribute('*', 'data-tooltip','.+');
	for(var i=0;i<tobs.length;i++){
		addEventHandler(tobs[i],'mouseover', function(event){
			tooltipDiv(this);
			event.stopPropagation();
    		event.preventDefault();
		});
		addEventHandler(tobs[i],'focus', function(event){
			tooltipDiv(this);
			event.stopPropagation();
    		event.preventDefault();
		});
		addEventHandler(tobs[i],'mouseout', function(event){
			tooltipDivObj='';
			fadeId('w_tooltip',1,1);
			event.stopPropagation();
    		event.preventDefault();
		});
	}
}
function cancel(e) {
      if (e.preventDefault) { e.preventDefault(); }
      return false;
    }
function stickyMenus(){
	var list = GetElementsByAttribute('*','data-behavior','sticky');
	var scrollPosition=getWindowScrollPosition();
	//console.log('stickyMenus found '+list.length+' matches');
	for(var i=0;i<list.length;i++){
		var sticky_start=list[i].getAttribute('sticky_y');
		if(scrollPosition >= sticky_start && list[i].style.position != "fixed"){
			list[i].style.position = "fixed";
			list[i].style.top = 0;
			list[i].style.width = '100%';
			list[i].style.zIndex = 99999;
			if(undefined != list[i].id){
				fadeIn(list[i].id);
			}
		}
		if(scrollPosition < sticky_start && list[i].style.position != "relative"){
			list[i].style.position = "relative";
			list[i].style.top = "";
			list[i].style.width = "";
			if(undefined != list[i].getAttribute('sticky_z')){
				list[i].style.zIndex = list[i].getAttribute('sticky_z');
			}
			else{
            	list[i].style.zIndex='';
			}
		}
	}
}
//remove tinymce
function removeTinyMCE(id){
	if (tinyMCE.getInstanceById(id)){
		tinyMCE.execCommand('mceRemoveControl', false, id);
	}
}
// Add initBehaviors to the onLoad queue
addEventHandler(window,'load',initBehaviors);


function addOnLoadEvent(f){
	addEventHandler(window,'load',f);
}
//generic addEvent for all browsers - depreciated use addEventHandler instead
function addEvent(elem, evnt, func){
	return addEventHandler(elem, evnt, func);
}
function addEventHandler(elem,evnt, func){
	if (elem.addEventListener){
		// W3C DOM
		elem.addEventListener(evnt,func,false);
	}
	else if (elem.attachEvent){
   		// IE DOM
   		elem.attachEvent("on"+evnt, func);
   }
   else {
   		// Not IE or W3C - try generic
		elem['on'+evnt] = func;
   }
}
//generic remove Event handler for all browsers - depreciated use removeEventHandler instead
function removeEvent(elem, evnt, func){
	return removeEventHandler(elem, evnt, func);
}
function removeEventHandler(elem,evnt, func){
	if (elem.removeEventListener){
		// W3C DOM
		elem.removeEventListener(evnt,func,false);
	}
	else if (elem.detachEvent){
   		// IE DOM
   		elem.detachEvent("on"+evnt, func);
   }
   else {
   		// Not IE or W3C - try generic
		elem['on'+evnt] = {};
   }
}
/* Codemirror helper funcitons */
function codemirrorTextEditor(obj,mode,behavior){
	obj=getObject(obj);
	//don't process the same object again - fixes ajax where it shows up twice
	if(undefined != obj.getAttribute('codeeditor_processed')){return false;}
	var readonly=false;
	if(undefined != obj.getAttribute('readonly')){readonly=true;}
	var extrakeys={};
	if(undefined == obj.getAttribute('data-nokeys')){
		extrakeys={
			"F1": function(cm){
				/* F1 - Show Help */
				codemirrorHelp(cm);
			},
			"F5": function(cm) {
				/* F5 - Preview window  */
				var obj=cm.getTextArea();
				var ajaxid='centerpop';
				if(undefined != obj.getAttribute('ajaxid')){
					ajaxid=obj.getAttribute('ajaxid');
				}
				else if(undefined != obj.getAttribute('data-ajaxid')){
					ajaxid=obj.getAttribute('data-ajaxid');
				}
				if(undefined != obj.getAttribute('preview')){
					cm.save();
					var cmForm=getElementForm(cm.getWrapperElement());
					if(undefined != cmForm._action){
						cmForm._action.value='EDIT';
					}
					if(undefined != cmForm._preview && undefined != cmForm.name){
						cmForm._preview.value=cmForm.name.value;
					}
					ajaxSubmitForm(cmForm,ajaxid);
				}
				else{
					var opts={ajaxid:ajaxid,_template:1};
					var url='/php/index.php';
					if(undefined != obj.getAttribute('data-url')){
						url=obj.getAttribute('data-url');
					}
					if(undefined != obj.getAttribute('data-func')){
						opts['func']=obj.getAttribute('data-func');
					}
					if(undefined != obj.getAttribute('data-query')){
						var cname=obj.getAttribute('data-query');
						opts[cname]=cm.getValue();
					}
					else{opts['_sqlpreview_']=cm.getValue();}
                	ajaxGet(url,ajaxid,opts);
				}
			},
			"F6": function(cm) {
				/* CTRL-F6 - Begin $rtn string - mode sensitive  */
				var cmode=cm.getOption('mode');
				switch(cmode){
                	case 'application/x-httpd-php':
                	case 'application/x-perl':
                		cm.replaceRange('$rtn .= \'',cm.getCursor(true),cm.getCursor(false));
                		break;
                	case 'application/x-ruby':
                		cm.replaceRange('rtn .= \'',cm.getCursor(true),cm.getCursor(false));
                		break;
                	case 'text/javascript':
                		cm.replaceRange('rtn += \'',cm.getCursor(true),cm.getCursor(false));
                		break;
				}
			},
			"F7": function(cm){
				/* CTRL-F6 - End $rtn string  - mode sensitive */
				var cmode=cm.getOption('mode');
				switch(cmode){
                	case 'application/x-httpd-php':
                	case 'application/x-perl':
                	case 'application/x-ruby':
                		cm.replaceRange('\'."\\r\\n";',cm.getCursor(true),cm.getCursor(false));
                		break;
                	case 'text/javascript':
                		cm.replaceRange('\'+"\\r\\n";',cm.getCursor(true),cm.getCursor(false));
                		break;
				}

			},
			"F11": function(cm){
				/* F11 - Full Screen Toggle */
		        var scrollerElement = cm.getScrollerElement();
		        var editorElement=cm.getWrapperElement();
		        var isfullscreen=editorElement.getAttribute('isfullscreen');
		        if (undefined==isfullscreen || isfullscreen != 1) {
					editorElement.setAttribute('isfullscreen',1);
					editorElement.style.position='absolute';
					editorElement.style.top=0;
					editorElement.style.left=0;
		            	editorElement.style.height = "100%";
		            	editorElement.style.width = "100%";
					editorElement.style.zIndex = 99999;
		            	editorElement.style.backgroundColor = '#FFFFFF';
					scrollerElement.style.position='absolute';
					scrollerElement.style.top=0;
					scrollerElement.style.left=0;
					scrollerElement.style.zIndex = 99999;
		            	scrollerElement.style.height = "100%";
		            	scrollerElement.style.width = "100%";
		            	cm.refresh();
		        }
				else{
					editorElement.setAttribute('isfullscreen',0);
		            	editorElement.style.position='relative';
		            	scrollerElement.style.position='relative';
		            	editorElement.style.zIndex = 10;
		            	scrollerElement.style.zIndex = 10;
		            	scrollerElement.style.height = editorElement.getAttribute('setheight');
		            	scrollerElement.style.width = editorElement.getAttribute('setwidth');
		            	editorElement.style.height = editorElement.getAttribute('setheight');
		            	editorElement.style.width = editorElement.getAttribute('setwidth');
		            	cm.refresh();
		        }
		    },
			"Ctrl-Q": function(cm){
				/* CTRL-Q - Fold All Toggle */
				var lcnt=cm.lineCount();
				for(var i=0;i<lcnt;i++){
					foldFunc(cm, i);
				}
			}
		};
	}
	//function keys overrides
	var fkeys=new Array('F1','F2','F3','F4','F5','F6','F7','F8','F9','F10','F11','F12');
	for(var f=0;f<fkeys.length;f++){
		var key=fkeys[f];
		var key2=key.toLowerCase();
		if(undefined != obj.getAttribute('data-'+key)){
			var fname=obj.getAttribute('data-'+key);
	    	extrakeys[key]=function(cm){cm.save();window[fname]();};
		}
		else if(undefined != obj.getAttribute('data-'+key2)){
	    	var fname=obj.getAttribute('data-'+key2);
	    	extrakeys[key]=function(cm){cm.save();window[fname]();};
		}
	}
	var params={
		mode: mode,
		behavior: behavior,
		viewportMargin: "Infinity",
		indentUnit: 4,
        indentWithTabs: true,
        enterMode: "keep",
        tabMode: "shift",
		matchBrackets: true,
		electricChars: false,
		readOnly:readonly,
  		textWrapping: false,
  		lineWrapping:true,
  		onBlur: function(cm){cm.save()},
    	extraKeys: extrakeys
	};
	//gutter or not?
	if(undefined == obj.getAttribute('data-gutter') || obj.getAttribute('data-gutter') == 'true'){
  		var foldFunc = CodeMirror.newFoldFunction(CodeMirror.braceRangeFinder);
  		params["gutter"]=true;
  		params["fixedGutter"]=true;
  		params["onGutterClick"]=foldFunc;
  		params["lineNumbers"]= true;
	}
	else{
    	params["gutter"]=false;
    	params["fixedGutter"]=false;
    	params["lineNumbers"]= false;
	}
	var editor = CodeMirror.fromTextArea(obj,params);
	//var hlLine = editor.setLineClass(0, "activeline");
	/* set width to match the texteditor width */
	var editorElement=editor.getWrapperElement();
	obj.setAttribute('codeeditor_processed',1);
	editorElement.setAttribute('setheight',obj.style.height);
	editorElement.setAttribute('setwidth',obj.style.width);
	editorElement.style.width=obj.style.width;
	editorElement.style.height=obj.style.height;
	var scroller = editor.getScrollerElement();
	scroller.style.height=obj.style.height;
	scroller.style.width=obj.style.width;
	/* Focus */
	if(undefined != obj.getAttribute('focus')){
		editor.focus();
		var xy=obj.getAttribute('focus').split(',',2);
		xy[0]=xy[0]-1;
		if(xy.length==2){
			editor.setCursor(xy[0],xy[1]);
		}
		else if(xy > 0){
			editor.setCursor(xy[0]);
		}
	}
	return;
}

function executeFunctionByName(functionName, context /*, args */) {
	if(undefined == context){context='window';}
	var args = [].slice.call(arguments).splice(2);
	var namespaces = functionName.split(".");
	var func = namespaces.pop();
	for(var i = 0; i < namespaces.length; i++) {
		context = context[namespaces[i]];
	}
	return context[func].apply(this, args);
}

function codemirrorHelp(cm){
	if(undefined != document.getElementById('codemirrorpop')){
    		removeDiv('codemirrorpop');
    		return;
	}
	//get the original textarea
	var obj=cm.getTextArea();
	var title=obj.getAttribute('data-behavior').toString().toUpperCase()
	var htm='';
	htm += '<div class="w_lblue w_bold w_big w_middle"><b style="color:red;">{ }</b> '+title+' Help</div>'+"\n";
	htm += '<div>Navigation Keys</div>'+"\n";
	htm += '<table cellspacing="0" cellpadding="2" border="1" class="w_table">'+"\n";
	htm += '  <tr><th>Function Keys</th><th>Description</th></tr>'+"\n";
	htm += '  <tr><td>F1</td><td>Help Screen Toggle</td></tr>'+"\n";
	if(undefined != obj.getAttribute('preview')){
		var preview=obj.getAttribute('preview');
		if(preview.length > 1){
        		htm += '  <tr><td>F5</td><td>'+preview+'</td></tr>'+"\n";
		}
		else if(undefined != obj.getAttribute('ajaxid')){
			htm += '  <tr><td>F5</td><td>Preview</td></tr>'+"\n";
		}
		else{
        		htm += '  <tr><td>F5</td><td>Save and Preview Page</td></tr>'+"\n";
		}
	}
	else if(undefined != obj.getAttribute('data-ajaxid')){
		htm += '  <tr><td>F5</td><td>Execute SQL</td></tr>'+"\n";
	}
	htm += '  <tr><td>F6</td><td>Begin $rtn string (mode sensitive)</td></tr>'+"\n";
	htm += '  <tr><td>F7</td><td>End $rtn string (mode sensitive)</td></tr>'+"\n";
	htm += '  <tr><td>F11</td><td>Full Screen Toggle</td></tr>'+"\n";
	htm += '  <tr><th>Search and Replace</th><th>Description</th></tr>'+"\n";
    htm += '  <tr><td>Ctrl-F</td><td>Start searching</td></tr>'+"\n";
    htm += '  <tr><td>Ctrl-G</td><td>Find next</td></tr>'+"\n";
    htm += '  <tr><td>Shift-Ctrl-G</td><td>Find previous</td></tr>'+"\n";
    htm += '  <tr><td>Shift-Ctrl-F</td><td>Replace</td></tr>'+"\n";
    htm += '  <tr><td>Shift-Ctrl-R</td><td>Replace all</td></tr>'+"\n";
    htm += '  <tr><th>Misc Keys</th><th>Description</th></tr>'+"\n";
    htm += '  <tr><td>Ctrl-Q</td><td>Fold all code groups toggle.</td></tr>'+"\n";
    htm += '</table>'+"\n";
    htm += '<div align="right"></div>'+"\n";
    codemirrorPopup(htm);
    return true;
}
/* codemirrorpopup*/
function codemirrorPopup(txt){
	if(undefined != document.getElementById(txt)){txt=getText(txt);}
	popUpDiv('',{id:'codemirrorpop',drag:1,notop:1,nobot:1,noborder:1,nobackground:1,bodystyle:"padding:0px;border:0px;background:none;"});
	setText('codemirrorpop',txt);
	centerObject('codemirrorpop',1);
}
var tinymceInitialized=0;
function tinymceInitialize(txtid,px){
	//initialize the tinyMCE editor instance so we can add editor instances.
	//reference: http://blog.mirthlab.com/2008/11/13/dynamically-adding-and-removing-tinymce-instances-to-a-page/
	var bar1="bold,italic,underline,|,forecolor,backcolor,|,justifyleft,justifycenter,justifyright,|,bullist,numlist,outdent,indent,|,charmap,image,media,link,unlink,formatselect,code,fullscreen,fontsizeselect";
	var bar2="";
	var bar3="";
	var ok=tinyMCE.init({
		// General options
		mode : "none",
    	//elements : txtid,
		theme : "advanced",
		plugins : "table,advhr,inlinepopups,contextmenu,media,paste,fullscreen,visualchars,nonbreaking,xhtmlxtras,template,advlist,advimage",
		submit_patch : false,
		paste_auto_cleanup_on_paste : true,
		paste_text_replacements : [
			[/\u2026/g, "..."],
			[/[\x93\x94\u201c\u201d]/g, '"'],
			[/[\x60\x91\x92\u2018\u2019]/g, "'"],
			[ /.*((https?|ssh|ftp|file):\/\/\S+).*/gi, '<a href="$1">$1</a>' ],
        	[ /.*((https?|ssh|ftp|file):\/\/\S+((\.)bmp|gif|jpe?g|png|psd|tif?f)).*/gi, '<img src="$1" alt="image" />' ]
		],

		// Theme options
		theme_advanced_buttons1 : bar1,
		theme_advanced_buttons2 : bar2,
		theme_advanced_buttons3 : bar3,
		theme_advanced_buttons4 : "",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_resizing : true
		});
	}
function fullScreenToggle(id){
	//info:toggle a div to full screen and back
	var obj=getObject(id);
	if(undefined==obj){return false;}
	var set=obj.getAttribute('fullscreen');
	if(undefined == set || set==0){
		var wh=getWidthHeight(obj);
		obj.setAttribute('fullscreen',1);
		obj.setAttribute('width_ori',wh[0]);
		obj.setAttribute('height_ori',wh[1]);
		obj.style.width=document.body.clientWidth+'px';
		obj.style.height=document.body.clientHeight+'px';
	}
	else{
		obj.setAttribute('fullscreen',0);
		obj.style.width=obj.getAttribute('width_ori')+'px';
		obj.style.height=obj.getAttribute('height_ori')+'px';
	}
}
function floatDiv(id,t,b){
	//info: makes specified object or id float at t,b
	var obj=getObject(id);
	var top=0;
	if(undefined != obj.parentNode && parseInt(obj.parentNode.scrollTop) > 0){top=parseInt(obj.parentNode.scrollTop);}
	else if(undefined != document.documentElement && parseInt(document.documentElement.scrollTop) > 0){top=parseInt(document.documentElement.scrollTop);}
	else if(undefined != document.body && parseInt(document.body.scrollTop) > 0){top=parseInt(document.body.scrollTop);}
	else{return false;}
	var stay=parseInt(t);
	var newtop=Math.round(top+stay);
	obj.style.top=newtop+'px';
    setTimeout("floatDiv('"+id+"','"+t+"')",250);
	}

function addDragToTextarea(sid){
	//info: makes specified textarea resizable by dragging bottom right corner
	var obj = document.getElementById(sid);
	//get select object width.
	var w=Math.round(obj.offsetWidth+10);
	var dragarea=obj.id+'_dragarea';
	var dragcheckbox=obj.id+'_dragcheckbox';
	var cx=findPosX(obj);
	var cy=findPosY(obj);
	var xpos=Math.round(cx+obj.offsetWidth-6);
	var ypos=Math.round(cy+obj.offsetHeight-12);
	var html = '<span parentid="'+sid+'" textareadrag="1" id="'+dragarea+'" style="position:absolute;left:'+xpos+'px;top:'+ypos+'px;cursor:crosshair;color:#7F9DB9;font-size:13pt;font-family:times;" title="Drag to adjust size">&#9688;</span>';
	var pobj=getParent(obj);
   	pobj.insertAdjacentHTML('beforeEnd',html);
   	var dragobj=document.getElementById(dragarea);
	Drag.init(dragobj);
	//var valcnt=obj.length;
	//var w=Math.round(obj.offsetWidth-6);
	dragobj.onDrag = function(x, y) {
		var pid = this.getAttribute('parentid');
		var obj = document.getElementById(pid);
		var w=Math.round(x-cx+6);
		var h=Math.round(y-cy+12);
		if(w > 0){obj.style.width = w+'px';}
		if(h > 0){obj.style.height = h+'px';}
		/*Look for any other dragable items and reset their position*/
  		var cid=this.id;
		var dragObjs = GetElementsByAttribute('span', 'textareadrag', '1');
  		for (var n=0; n<dragObjs.length; n++) {
	   		if(dragObjs[n].id != cid){
				var parentid = dragObjs[n].getAttribute('parentid');
				//window.status=cid+","+dragObjs[n].id+","+parentid;
    				if(undefined != parentid){
					var cpobj = document.getElementById(parentid);
					var px=findPosX(cpobj);
					var py=findPosY(cpobj);
					var cxpos=Math.round(px+cpobj.offsetWidth-6);
					var cypos=Math.round(py+cpobj.offsetHeight-12);
					dragObjs[n].lastMouseX=cxpos;
					dragObjs[n].lastMouseY=cypos;
					dragObjs[n].style.left=cxpos+'px';
					dragObjs[n].style.top=cypos+'px';
		              }
		 		}
		 	}
		}
   	}
// Remember the current position.
function storeCaret(text)
{
	// Only bother if it will be useful.
	if (typeof(text.createTextRange) != 'undefined'){
		text.caretPos = document.selection.createRange().duplicate();
		}
}

// Replaces the currently selected text with the passed text.
function replaceText(text, textarea)
{
	// Attempt to create a text range (IE).
	if (typeof(textarea.caretPos) != "undefined" && textarea.createTextRange)
	{
		var caretPos = textarea.caretPos;

		caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? text + ' ' : text;
		caretPos.select();
	}
	// Mozilla text range replace.
	else if (typeof(textarea.selectionStart) != "undefined")
	{
		var begin = textarea.value.substr(0, textarea.selectionStart);
		var end = textarea.value.substr(textarea.selectionEnd);
		var scrollPos = textarea.scrollTop;

		textarea.value = begin + text + end;

		if (textarea.setSelectionRange)
		{
			textarea.focus();
			textarea.setSelectionRange(begin.length + text.length, begin.length + text.length);
		}
		textarea.scrollTop = scrollPos;
	}
	// Just put it on the end.
	else
	{
		textarea.value += text;
		textarea.focus(textarea.value.length - 1);
	}
}

// Surrounds the selected text with text1 and text2.
function surroundText(text1, text2, textarea){
	//info: Surrounds the selected text with text1 and text2
	// Can a text range be created?
	if (typeof(textarea.caretPos) != "undefined" && textarea.createTextRange)
	{
		var caretPos = textarea.caretPos;

		caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? text1 + caretPos.text + text2 + ' ' : text1 + caretPos.text + text2;
		caretPos.select();
	}
	// Mozilla text range wrap.
	else if (typeof(textarea.selectionStart) != "undefined")
	{
		var begin = textarea.value.substr(0, textarea.selectionStart);
		var selection = textarea.value.substr(textarea.selectionStart, textarea.selectionEnd - textarea.selectionStart);
		var end = textarea.value.substr(textarea.selectionEnd);
		var newCursorPos = textarea.selectionStart;
		var scrollPos = textarea.scrollTop;

		textarea.value = begin + text1 + selection + text2 + end;

		if (textarea.setSelectionRange)
		{
			if (selection.length == 0)
				textarea.setSelectionRange(newCursorPos + text1.length, newCursorPos + text1.length);
			else
				textarea.setSelectionRange(newCursorPos, newCursorPos + text1.length + selection.length + text2.length);
			textarea.focus();
		}
		textarea.scrollTop = scrollPos;
	}
	// Just put them on the end, then.
	else
	{
		textarea.value += text1 + text2;
		textarea.focus(textarea.value.length - 1);
	}
}

// Checks if the passed input's value is nothing.
function isEmptyText(theField){
	// Copy the value so changes can be made..
	var theValue = theField.value;

	// Strip whitespace off the left side.
	while (theValue.length > 0 && (theValue.charAt(0) == ' ' || theValue.charAt(0) == '\t'))
		theValue = theValue.substring(1, theValue.length);
	// Strip whitespace off the right side.
	while (theValue.length > 0 && (theValue.charAt(theValue.length - 1) == ' ' || theValue.charAt(theValue.length - 1) == '\t'))
		theValue = theValue.substring(0, theValue.length - 1);

	if (theValue == ''){return true;}
	else{return false;}
}
/*http://www.quirksmode.org/js/findpos.html*/
function findPosX(xobj){
	var curleft = 0;
	if (xobj.offsetParent){
		while (xobj.offsetParent){
			curleft += xobj.offsetLeft;
			xobj = xobj.offsetParent;
			}
		}
	else if (xobj.x){curleft += xobj.x;}
	return curleft;
	}

function findPosY(yobj){
	var curtop = 0;
	if (yobj.offsetParent){
		while (yobj.offsetParent){
			curtop += yobj.offsetTop;
			yobj = yobj.offsetParent;
			}
		}
	else if (yobj.y){curtop += yobj.y;}
	return curtop;
	}
/* timeClock - */
var TimoutArray=new Array();
function slideShow(divid,idx,s){
	//info: creates a slideshow using image tags found in divid
	if(undefined == s){s=15;}
	var ms=Math.round(s*1000);
	idx=Math.round(idx+0)
	var divobj=getObject(divid);
	var transition=divobj.getAttribute('data-transition');
	id='slideshow'+divid;
	clearTimeout(TimoutArray[id]);
	if(isMouseOver(divid)){
		TimoutArray[id]=setTimeout("slideShow('"+divid+"',"+idx+","+s+")",ms);
		return;
	}
	var tag=divobj.getAttribute('data-tag');
	if(undefined == tag){tag='img';}
	var objs=divobj.getElementsByTagName(tag);
	if(objs.length==0){
		alert('SlideShow Error: - No '+tag+' elements found');
		return false;
		}
	if(idx == objs.length){idx=0;}
	for (var i=0; i<objs.length; i++) {
		var caption=objs[i].getAttribute('data-caption');
		if(undefined != transition && undefined == objs[i].getAttribute('data-transition')){
			objs[i].setAttribute('data-transition',transition);
		}
		objs[i].id='w_slide';
		setStyle(objs[i],'display','block');
		if(undefined == caption){caption='';}
		var navobj=getObject(divid+'_nav');
		if(i==idx){
			addClass(objs[i],'opaque');
			//check for data-function
			if(undefined != objs[i].getAttribute('data-function')){
				var functionName=objs[i].getAttribute('data-function');
				window[functionName](objs[i]);
			}
			if(undefined != navobj){
            	var navdiv=getObject(divid+'_nav_'+i);
            	addClass(navdiv,'active');
			}
		}
		else{
			removeClass(objs[i],'opaque');
			if(undefined != navobj){
            	var navdiv=getObject(divid+'_nav_'+i);
            	removeClass(navdiv,'active');
			}
			caption='';
		}
		setText(divid+'_caption',caption);
	}
	idx=Math.round(idx+1);
    TimoutArray[id]=setTimeout("slideShow('"+divid+"',"+idx+","+s+")",ms);
	}
function stopWatch(id){
	clearTimeout(TimoutArray[id]);
	var obj=getObject(id);
	obj.onfocus=function(){
		this.setAttribute('hasfocus',1);
     	}
     obj.onblur=function(){
		this.setAttribute('hasfocus',0);
     	}
	var f=obj.getAttribute('hasfocus');
	if(undefined != f && f==1){return false;}
	//Get the start time from the value of id.  HH:MM:SS
	var stime=getText(id);
	var hour=0;
	var min=0;
	var sec=0;
	if(stime.length){
		var parts=stime.split(':');
		hour=parseInt(parts[0]);
		min=parseInt(parts[1]);
		sec=parseInt(parts[2]);
    	}
    sec++;
    //window.status=stime+'-'+hour+','+min+','+sec;
    if (sec == 60) {sec = 0; min++;}
  	if (min == 60){min = 0; hour++;}
	//if (hour<=9) { hour = "0" + hour; }
	//if (min<=9) { min = "0" + min; }
	//if (sec<=9) { sec = "0" + sec; }
   	var newtext = hour + ":" + min + ":" + sec;
	setText(id,newtext);
    //set the timer
    TimoutArray[id]=setTimeout("stopWatch('"+id+"')",1000);
	}
function ajaxTimer(id){
	//info: used by ajax behavior
	//info: create an object with skip_ajax_timer as the id to skip
	clearTimeout(TimoutArray[id]);

	var obj=getObject(id);
	if(undefined == obj){return;}
	var attr=getAllAttributes(obj);
	var number;
	if(undefined != attr['data-countdown']){number=parseInt(attr['data-countdown']);}
	else if(undefined != attr['countdown']){number=parseInt(attr['countdown']);}
	else if(undefined != attr['data-timer']){number=parseInt(attr['data-timer']);}
	else if(undefined != attr['timer']){number=parseInt(attr['timer']);}

	else{
		//default to 75 seconds
    	number=75;
	}
	number--;
	obj.setAttribute('data-countdown',number);
	var timer;
	if(undefined != attr['data-timer']){timer=parseInt(attr['data-timer']);}
	else if(undefined != attr['timer']){timer=parseInt(attr['timer']);}
	else{
		//default to 75 seconds
    	timer=75;
	}
	//check for skip attribute on page
	var skip=0;
	if(undefined != document.activeElement && undefined != document.activeElement.type && document.activeElement.type.indexOf('text') != -1){
		skip=1;
	}
	else if(undefined != getObject('skip_ajax_timer')){skip=1;}
	obj.setAttribute('data-skip',skip);
	if(skip==0 && number <= 0){
		if(undefined != attr['url'] || undefined != attr['data-url']){
			var url;
			if(undefined != attr['url']){url=attr['url'];}
			else{url=attr['data-url'];}
    		//call ajax and reset the countdown timer
			var parts=url.split('?');
			var params={};
			if(undefined != parts[1]){params=JSON.parse('{"' + decodeURI(parts[1].replace(/&/g, "\",\"").replace(/=/g,"\":\"")) + '"}');}
			params['showprocessing']=false;
			params['timer']=timer;
			ajaxGet(parts[0],attr['id'],params);
		}
		else if(undefined != attr['function'] || undefined != attr['data-function']){
        	//run a function instead
        	var func;
			if(undefined != attr['function']){func=attr['function'];}
			else{func=attr['data-function'];}
			eval(func);
		}
		//reset the timer
		obj.setAttribute('data-countdown',timer);
	}
	TimoutArray[id]=setTimeout("ajaxTimer('"+id+"')",1000);
}
function countDown(id){
	//info: used by countdown behavior
	clearTimeout(TimoutArray[id]);
	var obj=getObject(id);
	if(undefined == obj){return;}
	//Get the start time from the value of id.  HH:MM:SS
	var number=parseInt(getText(id));
	number--;
	setText(id,number);
    var cb=obj.getAttribute('callback');
    if(cb){
    	var func=cb+"('"+id+"','"+number+"')";
    	eval(func);
		}
	TimoutArray[id]=setTimeout("countDown('"+id+"')",1000);
	}
function countDownDate(divid,yr,m,d,hr,min,tz){
	//info: used by countdowndate behavior
	if(undefined == tz){tz='';}
	var divobj=getObject(divid);
	if(undefined==divobj){
		return;
	}
	theyear=yr;themonth=m;theday=d;thehour=hr;theminute=min;
	var montharray=new Array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
	var today=new Date();
	var todayy=today.getYear();
	if (todayy < 1000) {todayy+=1900;}
	var todaym=today.getMonth();
	var todayd=today.getDate();
	var todayh=today.getHours();
	var todaymin=today.getMinutes();
	var todaysec=today.getSeconds();
	//if (todayh<=9) { todayh = "0" + todayh;}
	//if (todaymin<=9) { todaymin = "0" + todaymin;}
	//if (todaysec<=9) { todaysec = "0" + todaysec;}
	var todaystring1=montharray[todaym]+" "+todayd+", "+todayy+" "+todayh+":"+todaymin+":"+todaysec;
	//adjust for timezone if passed in
	if(tz.length==0){
		var todaystring=Date.parse(todaystring1)+(tz*1000*60*60);
	}
	else{
		var todaystring=Date.parse(todaystring1);
	}
	var futurestring1=(montharray[m-1]+" "+d+", "+yr+" "+hr+":"+min);
	var futurestring=Date.parse(futurestring1)-(today.getTimezoneOffset()*(1000*60));
	var dd=futurestring-todaystring;
	var dday=Math.floor(dd/(60*60*1000*24)*1);
	var dhour=Math.floor((dd%(60*60*1000*24))/(60*60*1000)*1);
	var dmin=Math.floor(((dd%(60*60*1000*24))%(60*60*1000))/(60*1000)*1);
	var dsec=Math.floor((((dd%(60*60*1000*24))%(60*60*1000))%(60*1000))/1000*1);
	if(dday<=0&&dhour<=0&&dmin<=0&&dsec<=0){
		divobj.style.display='none';
		return;
	}
	else {
		if (dhour<=9) { dhour = "0" + dhour; }
		if (dmin<=9) { dmin = "0" + dmin; }
		if (dsec<=9) { dsec = "0" + dsec; }
		var rtn='<table  class="w_countdown">'+"\r\n";
		rtn+='<tr align="center"><th>'+dday+'</th><th>'+dhour+'</th><th>'+dmin+'</th><th>'+dsec+'</th></tr>'+"\r\n";
		rtn+='<tr align="center"><td>Day</td><td>Hour</td><td>Min</td><td>Sec</td></tr>'+"\r\n";
		rtn+='</table>'+"\r\n";
		setText(divobj,rtn);
		if(tz.length==0){
			setTimeout("countDownDate('"+divid+"',theyear,themonth,theday,thehour,theminute)",1000);
		}
		else{
        	setTimeout("countDownDate('"+divid+"',theyear,themonth,theday,thehour,theminute,tz)",1000);
		}
	}
}
function startClock(id,live){
	clearTimeout(TimoutArray[id]);
    var dt = new Date();
    var h=dt.getHours();
    var m=dt.getMinutes();
    var s=dt.getSeconds();
    var p=" am";
    if(h > 12){h=h-12;p=" pm";}
    if(h==12){p=" pm";}
    var timestr='';
    if(h<10){timestr +="0";}
    timestr += h;
    timestr += ":";
    if(m<10){timestr +="0";}
    timestr += m;
	timestr += ":";
    if(s<10){timestr +="0";}
	timestr += s;
    timestr +=p;
    setText(id,timestr);
    if(live){
    	TimoutArray[id]=setTimeout("startClock('"+id+"',"+live+")",1000);
    }
}
function timerVerbose(id,live){
	var obj=getObject(id);
	if(undefined == obj){return false;}
	var s=parseInt(obj.getAttribute('timer_verbose'));
	setText(obj,verboseTime(s));
	s=s+1;
	obj.setAttribute('timer_verbose',s);
	clearTimeout(TimoutArray[id]);
    if(live){
    	TimoutArray[id]=setTimeout("timerVerbose('"+id+"',"+live+")",1000);
    }
}
function startUTCClock(id,live){
	if(undefined == live){live=false;}
	clearTimeout(TimoutArray[id]);
	obj=getObject(id);
	var offset=Math.round(obj.getAttribute('data-offset'));
    var dt = new Date();
    if(dt.isDST()){
		offset=Math.round(obj.getAttribute('data-offset-dst'));
		if(undefined == offset){
        	offset=Math.round(obj.getAttribute('data-offset'));
		}
	}

	var h=dt.getUTCHours();
	if(dt.getDate()!=dt.getUTCDate()){
	    h+=24;
	}
	var h=h+offset;
    var m=dt.getUTCMinutes();
    var s=dt.getUTCSeconds();
    var p=" am";
    if(h > 24){h=h-24;p='am';}
    else if(h > 12){h=h-12;p='pm';}
    else if(h==12){p='pm';}
    var format=obj.getAttribute('data-format');
    if(undefined == format){format='h:m:s p';}
    //set the timer based on if they need seconds or not
    var t=1000;
    if(format.indexOf('s') == -1){
		//no seconds to show  so only run it 4 times a minute
		t=15000;
		}
    var parts=new Array();
    //hour
    if(format.indexOf('h') != -1){
	    format=format.replace('h',h);
	}
    //minute
    if(format.indexOf('m') != -1){
    	if(m<10){m ='0'+m;}
    	format=format.replace('m',m);
    }
    //seconds
    if(format.indexOf('s') != -1){
    	if(s<10){s ='0'+s;}
    	format=format.replace('s',s);
    }
    //am pm
    if(format.indexOf('p') != -1){
    	format=format.replace('p',p);
    }
    //set the new clock time
    setText(id,format);
    //if live, set a timer.
    if(live){
    	TimoutArray[id]=setTimeout("startUTCClock('"+id+"',"+live+")",t);
    	}
	}
function startRaid(id,raidid){
	clearTimeout(TimoutArray[id]);
    setText(id,getText(raidid));
    TimoutArray[id]=setTimeout("startRaid('"+id+"','"+raidid+"')",250);
	}
function startSum(id,sumid){
	clearTimeout(TimoutArray[id]);
	var sumIds = GetElementsByAttribute('*', 'id', sumid);
	var sum=0;
	for (var s=0; s<sumIds.length; s++) {
		var cval=getText(sumIds[s]);
		/*alert(cval);*/
    	var val=Math.round(cval);
    	sum=sum+val;
 		}
	setText(id,sum);
    TimoutArray[id]=setTimeout("startSum('"+id+"','"+sumid+"')",250);
	}
function detectLeftButton(evt) {
    evt = evt || window.event;
    var button = evt.which || evt.button;
    return button == 1;
}
function doMath(id){
	/*
		@math(one+two+three)  @math(one+(two*3))             one+(two*3)
	*/
	var behavior=document.getElementById(id).getAttribute('data-behavior').toLowerCase();
	var re = new RegExp('^\@([a-z]+)[(](.+)[)]$', 'igm');
	var str;
	var res=re.exec(behavior);
	if (res && res.length > 0){
		var func=res[1].toLowerCase();
		var str=res[2].toLowerCase();
		var result=0;
		var mre = new RegExp('([a-z0-9\_]+)', 'igm');
		while(mres=mre.exec(str)){
			if (mres && mres.length > 0){
				var mname=mres[1];
				var txt=getText(mres[1]);
				//alert('replace '+mname+' with '+txt);
				var evalstr='str.replace(/'+mname+'/,\''+txt+'\')';
				str=eval(evalstr);
				}
			}
		//window.status=str;
		try{
			str=eval(str);
			setText(id,str);
			}
		catch(err){
			setText(id,err);
        	}
	}
}
function stopTimeout(id){
	clearTimeout(TimoutArray[id]);
}

//CheckMouseEnter  - returns true if the mouse is over the element
function checkMouseEnter (element, evt) {
	if (element.contains && evt.fromElement) {
	     return !element.contains(evt.fromElement);
	}
	else if (evt.relatedTarget) {
	   	return !containsDOM(element, evt.relatedTarget);
	}
}

// checkMouseLeave - returns true if the mouse is no longer over the element
function checkMouseLeave (element, evt) {
	   //window.status=evt;
	   //return;
	   if (element.contains && undefined != evt.toElement) {
	        return !element.contains(evt.toElement);
		   }
	   else if (evt.relatedTarget) {
		   return !containsDOM(element, evt.relatedTarget);
		   }
	   }

//containsDOM - does container have containee
function containsDOM (container, containee) {
	var isParent = false;
	do {
	     if ((isParent = container == containee)){break;}
		containee = containee.parentNode;
	}
 	while (containee != null);
	return isParent;
}
function isOver(dragId,containerId){
	var dragPos=getPos(dragId);
	var dw=getWidth(dragId);
	var dx=dragPos.x+parseInt(dw/2);
	var contPos=getPos(containerId);
	var w=getWidth(containerId);
	var h=getHeight(containerId);
	var lft=contPos.x+w;
	var h=contPos.y+h;
	if(dx > contPos.x && dx < lft && dragPos.y > contPos.y && dragPos.y < h){return true;}
	return false;
}
//Drag library from http://www.aaronboodman.com/
//		https://github.com/aboodman/dom-drag
//	Note: modifications made to handle evaluating funcions when a div is dropped on
var Drag = {
	obj : null,
	init : function(o, oRoot, minX, maxX, minY, maxY, bSwapHorzRef, bSwapVertRef, fXMapper, fYMapper)
	{
		o=getObject(o);
		o.onmousedown	= Drag.start;
		o.hmode			= bSwapHorzRef ? false : true ;
		o.vmode			= bSwapVertRef ? false : true ;

		o.root = oRoot && oRoot != null ? oRoot : o ;

		if (o.hmode  && isNaN(parseInt(o.root.style.left  ))){o.root.style.left   = "0px";}
		if (o.vmode  && isNaN(parseInt(o.root.style.top   ))){o.root.style.top    = "0px";}
		if (!o.hmode && isNaN(parseInt(o.root.style.right ))){o.root.style.right  = "0px";}
		if (!o.vmode && isNaN(parseInt(o.root.style.bottom))){o.root.style.bottom = "0px";}

		var y = parseInt(o.vmode ? o.root.style.top  : o.root.style.bottom);
		var x = parseInt(o.hmode ? o.root.style.left : o.root.style.right );
		o.startX=x;
		o.startY=y;

		o.minX	= typeof minX != 'undefined' ? minX : null;
		o.minY	= typeof minY != 'undefined' ? minY : null;
		o.maxX	= typeof maxX != 'undefined' ? maxX : null;
		o.maxY	= typeof maxY != 'undefined' ? maxY : null;

		o.xMapper = fXMapper ? fXMapper : null;
		o.yMapper = fYMapper ? fYMapper : null;

		o.onmouseover= function(){this.style.cursor='move';}

		o.root.onDragStart	= new Function();
		o.root.onDragEnd	= new Function();
		o.root.onDrag		= new Function();
	},

	start : function(e)
	{
		var o = Drag.obj = this;
		e = Drag.fixE(e);
		var y = parseInt(o.vmode ? o.root.style.top  : o.root.style.bottom);
		var x = parseInt(o.hmode ? o.root.style.left : o.root.style.right );
		o.root.onDragStart(x, y);
		Drag.obj.lastX=x;
		Drag.obj.lastY=y;
		o.lastMouseX	= e.clientX;
		o.lastMouseY	= e.clientY;

		if (o.hmode) {
			if (o.minX != null)	o.minMouseX	= e.clientX - x + o.minX;
			if (o.maxX != null)	o.maxMouseX	= o.minMouseX + o.maxX - o.minX;
		} else {
			if (o.minX != null) o.maxMouseX = -o.minX + e.clientX + x;
			if (o.maxX != null) o.minMouseX = -o.maxX + e.clientX + x;
		}

		if (o.vmode) {
			if (o.minY != null)	o.minMouseY	= e.clientY - y + o.minY;
			if (o.maxY != null)	o.maxMouseY	= o.minMouseY + o.maxY - o.minY;
		} else {
			if (o.minY != null) o.maxMouseY = -o.minY + e.clientY + y;
			if (o.maxY != null) o.minMouseY = -o.maxY + e.clientY + y;
		}

		document.onmousemove	= Drag.drag;
		document.onmouseup		= Drag.end;

		return false;
	},

	drag : function(e)
	{
		e = Drag.fixE(e);
		var o = Drag.obj;

		var ey	= e.clientY;
		var ex	= e.clientX;
		var y = parseInt(o.vmode ? o.root.style.top  : o.root.style.bottom);
		var x = parseInt(o.hmode ? o.root.style.left : o.root.style.right );
		var nx, ny;

		if (o.minX != null) ex = o.hmode ? Math.max(ex, o.minMouseX) : Math.min(ex, o.maxMouseX);
		if (o.maxX != null) ex = o.hmode ? Math.min(ex, o.maxMouseX) : Math.max(ex, o.minMouseX);
		if (o.minY != null) ey = o.vmode ? Math.max(ey, o.minMouseY) : Math.min(ey, o.maxMouseY);
		if (o.maxY != null) ey = o.vmode ? Math.min(ey, o.maxMouseY) : Math.max(ey, o.minMouseY);

		nx = x + ((ex - o.lastMouseX) * (o.hmode ? 1 : -1));
		ny = y + ((ey - o.lastMouseY) * (o.vmode ? 1 : -1));

		if (o.xMapper)		nx = o.xMapper(y)
		else if (o.yMapper)	ny = o.yMapper(x)
		Drag.obj.root.style[o.hmode ? "left" : "right"] = nx + "px";
		Drag.obj.root.style[o.vmode ? "top" : "bottom"] = ny + "px";
		Drag.obj.lastMouseX	= ex;
		Drag.obj.lastMouseY	= ey;
		Drag.obj.root.onDrag(nx, ny);
		//Am I over any object with an _ondragover attribute?  _ondragover="functionName", this function get get the attributes of targetdiv and dropdiv
		var navEls = GetElementsByAttribute('*', '_ondragover', '.+');
		for (var n=0; n<navEls.length; n++){
			if(isOver(Drag.obj,navEls[n])){
				var dofunc=navEls[n].getAttribute('_ondragover');
				navEls[n].setAttribute('_dragover',1);
				//gather attributes of both elements
				var targetdiv=new Object();
				for(var a=0;a<navEls[n].attributes.length;a++){
					var attrib=navEls[n].attributes[a];
					targetdiv[attrib.name]=attrib.value;
                	}
                var dropdiv=new Object();
                for(var a=0;a<Drag.obj.root.attributes.length;a++){
					var attrib=Drag.obj.root.attributes[a];
					dropdiv[attrib.name]=attrib.value;
                	}
                dropdiv.startX=Drag.obj.startX;
                dropdiv.startY=Drag.obj.startY;
				window[dofunc](targetdiv,dropdiv);
            	}
            else{
				//handle _ondragout
				var dragover=navEls[n].getAttribute('_dragover');
				if(undefined != dragover && dragover==1 && undefined != navEls[n].getAttribute('_ondragout')){
					navEls[n].setAttribute('_dragover',0);
					var dofunc=navEls[n].getAttribute('_ondragout');
					//gather attributes of both elements
					var targetdiv=new Object();
					for(var a=0;a<navEls[n].attributes.length;a++){
						var attrib=navEls[n].attributes[a];
						targetdiv[attrib.name]=attrib.value;
	                	}
	                var dropdiv=new Object();
	                for(var a=0;a<Drag.obj.root.attributes.length;a++){
						var attrib=Drag.obj.root.attributes[a];
						dropdiv[attrib.name]=attrib.value;
	                	}
	                dropdiv.startX=Drag.obj.startX;
	                dropdiv.startY=Drag.obj.startY;
					window[dofunc](targetdiv,dropdiv);
					}
            	}
        	}
		return false;
	},

	end : function()
	{
		document.onmousemove = null;
		document.onmouseup   = null;
		Drag.obj.root.onDragEnd(	parseInt(Drag.obj.root.style[Drag.obj.hmode ? "left" : "right"]),
									parseInt(Drag.obj.root.style[Drag.obj.vmode ? "top" : "bottom"]));
		// look for a _ondragend attribute
		var dragendfunc=Drag.obj.getAttribute('data-ondragend');
		if(undefined == dragendfunc){dragendfunc=Drag.obj.getAttribute('_ondragend');}
		if(undefined != dragendfunc){
        	var dropdiv=new Object();
            for(var a=0;a<Drag.obj.attributes.length;a++){
				var attrib=Drag.obj.attributes[a];
				dropdiv[attrib.name]=attrib.value;
            }
            //get the new position of the object that was dragged
            dropdiv.drag_x=parseInt(Drag.obj.root.style[Drag.obj.hmode ? "left" : "right"]);
            dropdiv.drag_y=parseInt(Drag.obj.root.style[Drag.obj.vmode ? "top" : "bottom"]);
            //showProperties(dropdiv);
			window[dragendfunc](dropdiv);
		}
		//Am I over any object with an ondrop attribute?  _ondrop="functionName", this function get get the attributes of targetdiv and dropdiv
		var navEls = GetElementsByAttribute('*', '_ondrop', '.+');
		for (var n=0; n<navEls.length; n++){
			if(isOver(Drag.obj,navEls[n])){
				var dropfunc=navEls[n].getAttribute('_ondrop');
				//gather attributes of both elements
				var targetdiv=new Object();
				for(var a=0;a<navEls[n].attributes.length;a++){
					var attrib=navEls[n].attributes[a];
					targetdiv[attrib.name]=attrib.value;
                	}
                var dropdiv=new Object();
                for(var a=0;a<Drag.obj.root.attributes.length;a++){
					var attrib=Drag.obj.root.attributes[a];
					dropdiv[attrib.name]=attrib.value;
                	}
                dropdiv.startX=Drag.obj.startX;
                dropdiv.startY=Drag.obj.startY;
				window[dropfunc](targetdiv,dropdiv);
            	}
        	}
		Drag.obj = null;
	},

	fixE : function(e)
	{
		if (typeof e == 'undefined') e = window.event;
		if (typeof e.layerX == 'undefined') e.layerX = e.offsetX;
		if (typeof e.layerY == 'undefined') e.layerY = e.offsetY;
		return e;
	}
}
/* initDrop */
function initDrop(tagname,tagatt,attval){
	//make w_dropdown fields hide display on mouse out
	if(undefined == tagname){tagname='div';}
	if(undefined == tagatt){tagatt='data-behavior';}
	if(undefined == attval){attval='dropdown';}
	var navEls = GetElementsByAttribute(tagname,tagatt,attval);
	//alert(navEls.length+" "+tagname+" "+tagatt+" "+attval);
	for (var n=0; n<navEls.length; n++) {
		addEventHandler(navEls[n],'mouseout',function(e) {
			if(undefined == e){e = fixE(e);}
			if(undefined != e){
				if(checkMouseLeave(this,e)){
					this.style.display='none';
					/*Check for onhide attribute*/
					var onhide=this.getAttribute('onhide');
					//window.status="onhide="+onhide;
					if(onhide){eval(onhide);}
				}
			}
		});
	}
}
/*_marker functions */
function wasqlMarkerForm(){
	var centerpop=getObject('centerpop');
	if(undefined != centerpop){return;}
    ajaxGet('/php/index.php','centerpop',{_template:4,mousex:MouseX,mousey:MouseY,_marker:'click'});
}
var wasqlmarkers={};
var wasqlmarker={};
function wasqlMarkerTagsJson(id){
	var jsondata=getText(id);
	wasqlmarkers=parseJSONString(jsondata);
	for(var i in wasqlmarkers){
		wasqlMarkerTag(wasqlmarkers[i].x,wasqlmarkers[i].y,wasqlmarkers[i].page,wasqlmarkers[i].priority);
	}
}
function wasqlMarkerTag(x,y,page,priority){
	var _markernulldiv=getObject('_markernulldiv');
	if(undefined == _markernulldiv){return 'no _markernulldiv div';}
	var guid='_marker_'+x+'_'+y+'_'+page;
	var hex='#337ab7';
    switch(priority){
		case 0:hex='#bc1717';break;
		case 1:hex='#cf312c';break;
		case 2:hex='#337ab7';break;
		case 3:hex='#bcbcbc';break;
	}
	var icon=page==0?'icon-tags':'icon-tag';
	var txt='<span class="'+icon+' w_pointer" style="font-size:2rem;color:'+hex+';"></span>';
	//console.log(txt);
	var tag=getObject(guid);
	if(undefined != tag){
		setText(tag,txt);
		return guid;
	}
	tag=document.createElement('div');
	tag.id=guid;
	tag.style.position='absolute';
	tag.style.display='inline';
	tag.style.zIndex=600;
    tag.style.top=y+'px';
    tag.style.left=x+'px';

    setText(tag,txt);
    document.body.appendChild(tag);
	return new Array(x,y,page,priority,guid,hex,txt);
}
function wasqlMarkerLoad(){
	var _markernulldiv=getObject('_markernulldiv');
	if(undefined == _markernulldiv){
	    //create a hidden div for the form to be posted to
	    var div_outer = document.createElement('div');
	    div_outer.style.display='none';
	    document.body.appendChild(div_outer);
	    var div_inner = document.createElement('div');
	    div_inner.id='_markernulldiv';
	    div_outer.appendChild(div_inner);
	    ajaxGet('/php/index.php','_markernulldiv',{_template:4,_marker:'load'});
	}
}
/**
 * initialize wasql markers
 * @param element mixed - object or id of the element to enable wasql markers on - defauts to document.body
 * @param icon string - classname from wasql_icons to use as the cursor - defaults to icon-tags
 * @usage wasqlMarkerInit(document.body,'icon-tags');
 */
function wasqlMarkerInit(el,cl){
	if(undefined == el){el=document.body;}
	if(undefined == cl){cl='icon-tags';}
	el=getObject(el);
	/* wasql markers - used to markeup a page for design purposes */
	wasqlMarkerLoad();
	//navEls[n].style.cursor='help';
	fontIconCursor(el,cl);
	addEventHandler(el,'click',function(e){
		//cancel parent onclicks so that the click stops here
		cancelBubble(e);
		//if the left button is clicked then
		if(e.altKey && e.ctrlKey){wasqlMarkerForm();return false;}
		return false;
	});
}
function resizeSignatureWidthHeight(){
	var list=document.querySelectorAll('.w_signature');
	for(var i=0;i<list.length;i++){
		var pwh=getWidthHeight(list[i]);
		list[i].width=pwh[0];
		list[i].height=pwh[1];
	}
}
/*
 * add event handler to resize any signature canvas
 * */
 //console.log('adding resize');
addEventHandler(window,'resize',function(){resizeSignatureWidthHeight();});
