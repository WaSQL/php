var wacss = {
	/**
	* @exclude  - this function is for internal use only and thus excluded from the manual
	*/
	version: '2024.0124',
	/**
	* @exclude  - this function is for internal use only and thus excluded from the manual
	*/
	author: 'WaSQL.com',
	/**
	* @exclude  - this function is for internal use only and thus excluded from the manual
	*/
	chartjs:{},
	/**
	* @exclude  - this function is for internal use only and thus excluded from the manual
	*/
	EOL: '\n',
	/**
	* @exclude  - this function is for internal use only and thus excluded from the manual
	*/
	CRLF: '\r\n',
	/**
	* @exclude  - this function is for internal use only and thus excluded from the manual
	*/
	processing: '<div style="display:flex;"><span class="icon-spin4 w_spin" style="align-self:center"></span><span id="processing_timer" data-timer="3" style="margin-left:10px;align-self:center;font-size:0.7rem;"></span></div>',
	processing_timeout:undefined,
	/**
	* @exclude  - this function is for internal use only and thus excluded from the manual
	*/
	hoverDiv:'',
	/**
	* @name wacss.addClass
	* @describe adds a class to an element
	* @param mixed element object or id
	* @param string class name
	* @return boolean true
	* @usage wacss.addClass(el,'active');
	*/
	addClass: function(element, classToAdd) {
		element=wacss.getObject(element);
		if(undefined == element){return false;}
		if(undefined==element.className){
			element.className=classToAdd;
			return true;
		}
	    let currentClassValue = element.className;

	    if (currentClassValue.indexOf(classToAdd) == -1) {
	        if ((currentClassValue == null) || (currentClassValue === "")) {
	            element.className = classToAdd;
	        } else {
	            element.className += " " + classToAdd;
	        }
	    }
	    return true;
	},
	/**
	* @name wacss.ajaxGet
	* @describe calls url in an httpd AJAX request and sends results to div
	* @param string url 
	* @param string div
	* @param obj key/value pairs to pass through
	* @return boolean false
	* @usage wacss.ajaxGet('/t/1/index/show,'mydiv',{color:"red",age:35});
	*/
	ajaxGet: function(url,div,params) {
	    let xmlhttp = new XMLHttpRequest();
	    xmlhttp.recenter='';
	    xmlhttp.div=div;
	    let cp={};
	    if(typeof(div)==='string'){
	    	switch(div.toLowerCase()){
	    		case 'centerpop':
	    		case 'wacss_centerpop':
	    			xmlhttp.div=wacss.getObject('wacss_centerpop');
	    			if(undefined==xmlhttp.div){xmlhttp.recenter='wacss_centerpop';}
	    			cp=wacss.createCenterpop(params.title);
	    			xmlhttp.div=cp.querySelector('.wacss_centerpop_content');
	    		break;
	    		case 'centerpop1':
	    		case 'wacss_centerpop1':
	    			xmlhttp.div=wacss.getObject('wacss_centerpop1');
	    			if(undefined==xmlhttp.div){xmlhttp.recenter='wacss_centerpop1';}
	    			cp=wacss.createCenterpop(params.title,1);
	    			xmlhttp.div=cp.querySelector('.wacss_centerpop_content');
	    		break;
	    		case 'centerpop2':
	    		case 'wacss_centerpop2':
	    			xmlhttp.div=wacss.getObject('wacss_centerpop2');
	    			if(undefined==xmlhttp.div){xmlhttp.recenter='wacss_centerpop2';}
	    			cp=wacss.createCenterpop(params.title,2);
	    			xmlhttp.div=cp.querySelector('.wacss_centerpop_content');
	    		break;
	    		case 'centerpop3':
	    		case 'wacss_centerpop3':
	    			xmlhttp.div=wacss.getObject('wacss_centerpop3');
	    			if(undefined==xmlhttp.div){xmlhttp.recenter='wacss_centerpop2';}
	    			cp=wacss.createCenterpop(params.title,3);
	    			xmlhttp.div=cp.querySelector('.wacss_centerpop_content');
	    		break;
	    		case 'modal':
	    			params.overlay=1;
	    			let modal=wacss.modalPopup(wacss.setprocessing,params.title,params);
	    			xmlhttp.div=modal.querySelector('#wacss_modal_content');
	    		break;
	    	}
	    }
		//set processing
		if(undefined==params.setprocessing){
			xmlhttp.div.innerHTML=wacss.processing;
			if(undefined != xmlhttp.recenter && xmlhttp.recenter.length > 0){
        		wacss.centerObject(xmlhttp.recenter);
        	}
        	setTimeout(wacss.setProcessingTimer, 3000);
		}
		else if (params.setprocessing.toString()!='0'){
			switch(params.setprocessing.toString().toLowerCase()){
				case 'centerpop_processing':
					params.setprocessing='wacss_centerpop_processing';
				break;
				case 'centerpop1_processing':
					params.setprocessing='wacss_centerpop1_processing';
				break;
				case 'centerpop2_processing':
					params.setprocessing='wacss_centerpop2_processing';
				break;
				case 'centerpop3_processing':
					params.setprocessing='wacss_centerpop3_processing';
				break;
			}
			pdiv=wacss.getObject(params.setprocessing);
			if(undefined != pdiv){
				pdiv.previous=pdiv.innerHTML;
				xmlhttp.processing=pdiv;
				pdiv.innerHTML=wacss.processing;
				setTimeout(wacss.setProcessingTimer, 3000);
			}
		}
		//get base URL if needed
	    if(url.indexOf('http')==-1){
	    	url=window.location.origin+url;
	    }
		let aurl = new URL(url);
		let aparams = aurl.searchParams;
		params.AjaxRequestUniqueId=wacss.ajaxUniqueID();
		for(k in params){
	    	if(undefined==typeof(params[k]) || params[k]===null || params[k].length==0){continue;}
			aparams.append(k, params[k]);
		}
		//load
	    xmlhttp.onload = function(){
	    	//console.log('load');
	    	this.ajaxevent='load';
	    	this.ajaxtext=this.responseText;
	    	wacss.ajaxProcessResponse(this);
	    };
	    //error
	    xmlhttp.onerror = function(){
	    	//console.log('error');
	    	this.ajaxevent='error';
	    	this.ajaxtext='&#9888; ajax request error';
	    	wacss.ajaxProcessResponse(this);
	    };
	    xmlhttp.ontimeout = function(){
	    	//console.log('timeout');
	    	this.ajaxevent='timeout';
	    	this.ajaxtext='&#128359; ajax request timed out';
	    	wacss.ajaxProcessResponse(this);
	    };
	    xmlhttp.onabort = function(){
	    	//console.log('abort');
	    	this.ajaxevent='abort';
	    	this.ajaxtext='&#x2718; ajax request aborted';
	    	wacss.ajaxProcessResponse(this);
	    };
		//make the request
	    xmlhttp.open("GET", aurl.toString(), true);
	    //timeout
	    if(undefined != params.timeout){xmlhttp.timeout=parseInt(params.timeout);}
	    else{
	    	let divobj=wacss.getObject(div);
	    	if(undefined != divobj && undefined != divobj.dataset.timeout){
	    		xmlhttp.timeout=parseInt(divobj.dataset.timeout);
	    	}
	    }
	    xmlhttp.send();
	    //always return false
	    return false;
	},
	ajaxPost: function(frm,div) {
	    let xmlhttp = new XMLHttpRequest();
	    let url=frm.getAttribute('action');
	    xmlhttp.div=div;
	    let cp={};
	    let params={};
	    params.title=frm.title.value||frm.dataset.title||'Information';
	    if(typeof(div)==='string'){
	    	switch(div.toLowerCase()){
	    		case 'centerpop':
	    		case 'wacss_centerpop':
	    			xmlhttp.div=wacss.getObject('wacss_centerpop');
	    			if(undefined==xmlhttp.div){xmlhttp.recenter='wacss_centerpop';}
	    			cp=wacss.createCenterpop(params.title);
	    			xmlhttp.div=cp.querySelector('.wacss_centerpop_content');
	    		break;
	    		case 'centerpop1':
	    		case 'wacss_centerpop1':
	    			xmlhttp.div=wacss.getObject('wacss_centerpop1');
	    			if(undefined==xmlhttp.div){xmlhttp.recenter='wacss_centerpop1';}
	    			cp=wacss.createCenterpop(params.title,1);
	    			xmlhttp.div=cp.querySelector('.wacss_centerpop_content');
	    		break;
	    		case 'centerpop2':
	    		case 'wacss_centerpop2':
	    			xmlhttp.div=wacss.getObject('wacss_centerpop2');
	    			if(undefined==xmlhttp.div){xmlhttp.recenter='wacss_centerpop2';}
	    			cp=wacss.createCenterpop(params.title,2);
	    			xmlhttp.div=cp.querySelector('.wacss_centerpop_content');
	    		break;
	    		case 'centerpop3':
	    		case 'wacss_centerpop3':
	    			xmlhttp.div=wacss.getObject('wacss_centerpop3');
	    			if(undefined==xmlhttp.div){xmlhttp.recenter='wacss_centerpop2';}
	    			cp=wacss.createCenterpop(params.title,3);
	    			xmlhttp.div=cp.querySelector('.wacss_centerpop_content');
	    		break;
	    		case 'modal':
	    			params.overlay=1;
	    			let modal=wacss.modalPopup(wacss.setprocessing,params.title,params);
	    			xmlhttp.div=modal.querySelector('#wacss_modal_content');
	    		break;
	    	}
	    }
	    //load
	    xmlhttp.onload = function(){
	    	//console.log('load');
	    	this.ajaxevent='load';
	    	this.ajaxtext=this.responseText;
	    	wacss.ajaxProcessResponse(this);
	    };
	    //error
	    xmlhttp.onerror = function(){
	    	//console.log('error');
	    	this.ajaxevent='error';
	    	this.ajaxtext='&#9888; ajax request error';
	    	wacss.ajaxProcessResponse(this);
	    };
	    xmlhttp.ontimeout = function(){
	    	//console.log('timeout');
	    	this.ajaxevent='timeout';
	    	this.ajaxtext='&#128359; ajax request timed out';
	    	wacss.ajaxProcessResponse(this);
	    };
	    xmlhttp.onabort = function(){
	    	//console.log('abort');
	    	this.ajaxevent='abort';
	    	this.ajaxtext='&#x2718; ajax request aborted';
	    	wacss.ajaxProcessResponse(this);
	    };
	    //set processing
	    let processing=0;
	    if(undefined != frm.dataset.setprocessing){
	    	processing=frm.dataset.setprocessing;
	    }
	    else if(undefined != frm['setprocessing']){
	    	processing=frm['setprocessing'].value;
	    }
		if (processing.toString() != '0'){
			switch(processing.toString().toLowerCase()){
				case 'centerpop_processing':
					processing='wacss_centerpop_processing';
				break;
				case 'centerpop1_processing':
					processing='wacss_centerpop1_processing';
				break;
				case 'centerpop2_processing':
					processing='wacss_centerpop2_processing';
				break;
				case 'centerpop3_processing':
					processing='wacss_centerpop3_processing';
				break;
			}
			pdiv=wacss.getObject(processing);
			if(undefined != pdiv){
				pdiv.previous=pdiv.innerHTML;
				xmlhttp.processing=pdiv;
				pdiv.innerHTML=wacss.processing;
				setTimeout(wacss.setProcessingTimer, 3000);
			}
		}
	    let data = new FormData(frm);
	    //add AjaxRequestUniqueId
	    data.append('AjaxRequestUniqueId',wacss.ajaxUniqueID());
	    xmlhttp.open("POST", url, true);
	    //timeout
	    if(undefined != frm.dataset.timeout){xmlhttp.timeout=parseInt(frm.dataset.timeout);}
	    else{
	    	let divobj=wacss.getObject(div);
	    	if(undefined != divobj && undefined != divobj.dataset.timeout){
	    		xmlhttp.timeout=parseInt(divobj.dataset.timeout);
	    	}
	    }
	    xmlhttp.send(data);
	    return false;
	},
	ajaxProcessResponse: function(obj){
		let div=wacss.getObject(obj.div);
		let txt=obj.ajaxtext || obj.responseText;
		if(undefined == div){
			switch(obj.div.toLowerCase()){
				case 'toast':
					wacss.toast(txt);
					if(undefined != obj.processing){
		        		obj.processing.innerHTML=obj.processing.previous;
		        	}
				break;
				default:
					console.log('dom object does not exist');
    				//console.log(txt);
				break;
			}
    	}
        else{
        	if(undefined != obj.processing){
        		obj.processing.innerHTML=obj.processing.previous;
        	}
        	div.innerHTML = txt;
        	if(undefined != obj.recenter && obj.recenter.length > 0){
        		wacss.centerObject(obj.recenter);
        	}
        }
        if(undefined != div && undefined != div.id){
        	let mp=wacss.getParent(div,'div','wacss_modal')
        	let cp=wacss.getParent(div,'div','wacss_centerpop')
        	if(undefined == mp && div.id.indexOf('_modal_') == -1){
        		wacss.modalClose();	
        	}
        	else if(div.id.indexOf('centerpop') == -1){
        		wacss.removeId('centerpop');	
        	}
        } 
        wacss.initOnloads();
	},
	ajaxUniqueID: function(){
		return "10000000000000000000".replace(/[018]/g, c => (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16));
	},
	/**
	* @name wacss.blink
	* @describe makes an element blink
	* @param mixed el DOM object or ID of a DOM object
	* @return boolean true
	* @usage wacss.blink('myel');
	*/
	blink: function(el){
		el=wacss.getObject(el);
		if(undefined == el){return;}
		let blink=0;
		if(undefined == el.getAttribute('data-blink')){
			el.setAttribute('data-blink',1);
			el.setAttribute('data-boxshadow',el.style.boxShadow);
		}
		else{
			blink=parseInt(el.getAttribute('data-blink'),10);
		}
		let n=blink+1;
		el.setAttribute('data-blink',n);
		switch(blink){
			case 0:
			case 2:
			case 4:
				el.style.boxShadow='0 8px 12px 0 rgba(0,0,0,0.7),0 10px 24px 0 rgba(0,0,0,0.69)';
				setTimeout(function(){wacss.blink(el);},150);
			break;
			case 1:
			case 3:
			case 5:
				el.style.boxShadow='0 8px 12px 0 rgba(0,0,0,0.2),0 10px 24px 0 rgba(0,0,0,0.19)';
				setTimeout(function(){wacss.blink(el);},150);
			break;
			default:
				el.style.boxShadow=el.getAttribute('data-boxshadow');
				el.setAttribute('data-blink',0);
				//wacss.removeClass(el,'tooltip');
				//wacss.removeClass(el,'top');
			break;
		}
	},
	/**
	* @name wacss.buildFormColor
	* @describe creates an HTML color input field - mimicks the WaSQL PHP function
	* @param fieldname string
	* @param params - JSON object
	* @return HTML element
	* @usage let el=wacss.buildFormColor('color');
	* @usage myform.appendChild(el);
	*/
	buildFormColor: function(fieldname,params){
		if(undefined == params){params={};}
		if(undefined == params['-formname']){params['-formname']='addedit';}
		if(undefined == params.id){params.id=params['-formname']+'_'+fieldname;}
		let iconid=params.id+'_icon';
		//force witdh
		params.width=115;
		let iconcolor='#c0c0c0';
		if(undefined != params.value){iconcolor=params.value;}
		if(undefined == params.placeholder){params.placeholder='#HEXVAL';}
		if(undefined == params.classname){params.classname='form-control input';}
		params['maxlength']=7;
		let tagdiv = document.createElement("div");
		tagdiv.className="input-group";
		tagdiv.style.width=params.width+'px';
		let tag = document.createElement("input");
		tag.type='text';
		tag.maxlength=7;
		tag.className=params.classname;
		tag.style.fontSize='11px';
		tag.style.fontFamily='arial';
		tag.name=fieldname;
		tag.id=params.id;
		if(params.required){tag.setAttribute('required',params.required);}
		if(undefined != params.value){
			tag.setAttribute('value',params.value);
		}
		else{tag.setAttribute('value','');}
		tag.classname=params.classname;
		tag.placeholder=params.placeholder;
		tagdiv.appendChild(tag);
		let tagspan = document.createElement("span");
		tagspan.id=iconid;
		tagspan.setAttribute('onclick',"return colorSelector('"+params.id+"');");
		tagspan.className="icon-color-adjust w_bigger w_pointer input-group-addon";
		tagspan.style.color=iconcolor+';padding-left:3px !important;padding-right:6px !important;';
		tagspan.title='Color Selector';
		tagdiv.appendChild(tagspan);
		if(undefined != params['-parent']){
			let pobj=wacss.getObject(params['-parent']);
			if(undefined != pobj){
				pobj.appendChild(tagdiv);
			}
			else{console.log(params['-parent']+' does not exist');}
		}
		return tagdiv;
	},
	/**
	* @name wacss.buildFormSelect
	* @describe creates an HTML select field - mimicks the WaSQL PHP function
	* @param fieldname string
	* @param params - JSON object
	* @return HTML element
	* @usage let el=wacss.buildFormSelect('age_range',{10:'under 10',20:'10 to 20',30:'over 20'});
	* @usage myform.appendChild(el);
	*/
	buildFormSelect: function(fieldname, opts, params){
		if(undefined == fieldname || !fieldname.length){alert('buildFormSelect Error: no name');return undefined;}
		fieldname=fieldname.replace('/[\[\]]+$/','');
		if(undefined == params){params={};}
		if(undefined == opts){alert('buildFormSelect Error: no opts');return undefined;}
		if(undefined == params['-formname']){params['-formname']='addedit';}
		if(undefined == params['id']){params['id']=params['-formname']+'_'+fieldname;}
	    let tag = document.createElement("select");
		if(undefined != params.required){tag.setAttribute('required',params.required);}
		if(undefined != params.class){tag.setAttribute('class',params.class);}
		if(undefined != params.style){tag.setAttribute('style',params.style);}
		tag.name=fieldname;
		tag.id=params.id;
		for(let tval in opts){
			let coption = document.createElement("OPTION");
			coption.value=tval;
			coption.innerHTML=opts[tval];
			if(undefined != params.value && tval==params.value){coption.setAttribute('selected',true);}
			tag.appendChild(coption);
		}
		if(undefined != params['-parent']){
			let pobj=wacss.getObject(params['-parent']);
			if(undefined != pobj){
				pobj.appendChild(tag);
			}
			else{console.log(params['-parent']+' does not exist');}
		}
		return tag;
	},
	/**
	* @name wacss.buildFormText
	* @describe creates an HTML input field - mimicks the WaSQL PHP function
	* @param fieldname string
	* @param params - JSON object
	* @return HTML element
	* @usage let el=wacss.buildFormText('age_range');
	* @usage myform.appendChild(el);
	*/
	buildFormText:function (fieldname,params){
		if(undefined == fieldname){alert('buildFormText requires fieldname');return undefined;}
		if(undefined == params){params={};}
		if(undefined == params['-formname']){params['-formname']='addedit';}
		if(undefined == params.id){params.id=params['-formname']+'_'+fieldname;}
		if(undefined == params.classname){params.classname='form-control input';}
		let tag = document.createElement("input");
		tag.className=params.classname;
		if(undefined != params.required){tag.setAttribute('required',params.required);}
		if(undefined != params.class){tag.setAttribute('class',params.class);}
		if(undefined != params.style){tag.setAttribute('style',params.style);}
		if(undefined != params.value){
	    	tag.setAttribute('value',params.value);
		}
		else{tag.setAttribute('value','');}
		tag.name=fieldname;
		tag.id=params.id;
		if(undefined != params['-parent']){
			let pobj=wacss.getObject(params['-parent']);
			if(undefined != pobj){
				pobj.appendChild(tag);
			}
			else{console.log(params['-parent']+' does not exist');}
		}
		return tag;
	},
	callFunc: function(params){
		if(undefined == params){return false;}
		if(undefined == params.func){return false;}
		let func=params.func;
		if(undefined != params.args){
			return window[func](params.args);	
		}
		return window[func]();
	},
	/**
	* @name wacss.centerObject
	* @describe centers specified object or id
	* @param mixed object, qs string, or id
	* @return false
	* @usage wacss.centerObject('centerpop')
	*/
	centerObject: function(obj){
		//info: centers specified object or id
		let sObj=wacss.getObject(obj);
		if(undefined == sObj){return false;}
		sObj.style.position=sObj.dataset.position || 'fixed';
		let w=sObj.offsetWidth || sObj.innerWidth || getWidth(sObj) || 100;
		let h=sObj.offsetHeight || sObj.innerHeight || getHeight(sObj) || 100;
		let vp=wacss.getViewportSize();
		let x = Math.round((vp.w / 2) - (w / 2));
	  	let y = Math.round((vp.h / 2) - (h / 2));
	  	sObj.style.left=x+'px';
	  	if(undefined == y){y=10;}
		if(y < 10){y=10;}
	  	sObj.style.top=y+'px';
	  	return new Array(x,y);
	},
	/**
	* @name wacss.centerpopCenter
	* @describe closes the centerpop window
	* @return boolean
	* @usage wacss.centerpopCenter();
	*/
	centerpopCenter: function(){
		if(undefined != document.getElementById('wacss_centerpop')){
			return wacss.centerObject('wacss_centerpop');
		}
		else if(undefined != document.getElementById('centerpop')){
			return wacss.centerObject('centerpop');
		}
	},
	/**
	* @name wacss.centerpopClose
	* @describe closes the centerpop window
	* @return boolean
	* @usage wacss.centerpopClose();
	*/
	centerpopClose: function(){
		if(undefined != document.getElementById('wacss_centerpop')){
			return wacss.removeObj(document.getElementById('wacss_centerpop'));
		}
		else if(undefined != document.getElementById('centerpop')){
			return wacss.removeObj(document.getElementById('centerpop'));
		}
	},
	chartjsDrawTotals: function(chart){
		let width = chart.chart.width,
	    height = chart.chart.height,
	    ctx = chart.chart.ctx;
	 
	    ctx.restore();
	    let fontSize = (height / 60).toFixed(2);
	    ctx.font = fontSize + "em sans-serif";
	    ctx.textBaseline = "middle";
	 
	    let text = chart.config.centerText.text,
	    textX = Math.round((width - ctx.measureText(text).width) / 2),
	    textY = height-20;
	 
	    ctx.fillText(text, textX, textY);
	    ctx.save();
	},
	/**
	* @name wacss.checkAllElements
	* @describe check/toggle all checkboxes that have an attribute of value
	* @param att string
	* @param val - string
	* @param ck - 1 or 0 - check or uncheck
	* @return false
	* @usage <input type="checkbox" onclick="wacss.checkAllElements('cid','mylist', this.checked);">
	*/
	checkAllElements: function(att,val,ck){
    	let list;
    	switch(att.toLowerCase()){
    		case 'class':
    			list=document.querySelectorAll('input[type="checkbox"].'+val);
    		break;
    		case 'id':
    			list=document.querySelectorAll('input[type="checkbox"]#'+val);
    		break;
    		default:
    			list=document.querySelectorAll('input[type="checkbox"]['+att+'="'+val+'"]');
    		break;
    	}
    	for(let i=0;i<list.length;i++){
			//process any onclick attribute
			if(undefined != list[i].dataset.onclick){
				wacss.simulateEvent(list[i], 'click');
			}
			list[i].checked=ck;
    	}
		return false;
   	},
   	/**
	* @name wacss.checkMouseLeave
	* @describe checks to see if mouse is still over element
	* @param mixed  orject or selector or id
	* @param event 
	* @return boolean
	* @usage if(wacss.checkMouseLeave(this,e)){...}
	*/
   	checkMouseLeave: function(element, evt){
   		element=wacss.getObject(element);
   		if(undefined==element){return false;}
		if(element.contains && undefined != evt.toElement){
			return !element.contains(evt.toElement);
		}
		else if(evt.relatedTarget){
			return !containsDOM(element, evt.relatedTarget);
		}
	},
	/**
	* @name wacss.containsHTML
	* @describe returns true if string contains HTML
	* @param str string
	* @return boolean
	* @usage if(wacss.containsHTML(str)){...}
	*/
	containsHTML: function(str){
		return (/[\<\>]/.test(str));
	},
	/**
	* @name wacss.containsSpaces
	* @describe returns true if string contains spaces
	* @param str string
	* @return boolean
	* @usage if(wacss.containsSpaces(str)){...}
	*/
	containsSpaces: function(str){
		return (/[\ ]/.test(trim(str)));
	},
   	/**
	* @name wacss.copy2Clipboard
	* @describe copies str to the clipboard and displays message
	* @param str string
	* @param msg - message to display as a toast - defaults to "Copy Successful"
	* @return false
	* @usage <button type="button" class="button" onclick="return wacss.copy2Clipboard(document.querySelect('#stuff').innerText);">Copy</button>
	*/
	copy2Clipboard: function(str,msg){
		if(undefined==msg){msg='Copy Successful';}
		const el = document.createElement('textarea');
	  	el.value = str;
	  	document.body.appendChild(el);
	  	el.select();
	  	document.execCommand('copy');
	 	document.body.removeChild(el);
	 	wacss.toast(msg);
	 	return false;
	},
	/**
	* @exclude  - this function is for internal use only and thus excluded from the manual
	*/
	color: function(){
		if(undefined != document.getElementById('admin_menu')){
				return document.getElementById('admin_menu').getAttribute('data-color');
			}
			else if(undefined != document.getElementById('admin_color')){
				return document.getElementById('admin_color').innerText;
			}
			else if(undefined != document.getElementById('wacss_color')){
				return document.getElementById('wacss_color').innerText;
			}
			else{return 'w_gray';}
	},
	/**
	* @exclude  - this function is for internal use only and thus excluded from the manual
	*/
	colorboxSelect: function(el){
		let p=wacss.getParent(el,'div');
		if(undefined==p){return false;}
		let v=el.options[el.selectedIndex].value;
		p.querySelector('input[type="text"]').value=v;
		p.querySelector('label[for]').style.backgroundColor=v;
		p.querySelector('input[type="checkbox"]').checked=false;
	},
	/**
	* @exclude  - this function is for internal use only and thus excluded from the manual
	*/
	colorboxSet: function(el){
		let p=wacss.getParent(el,'div');
		if(undefined==p){return false;}
		p.querySelector('input[type="text"]').value=el.dataset.color;
		p.querySelector('label[for]').style.backgroundColor=el.dataset.color;
		p.querySelector('input[type="checkbox"]').checked=false;
	},
	/**
	* @exclude  - this function is for internal use only and thus excluded from the manual
	*/
	colorwheelSet: function(el){
		let p=wacss.getParent(el,'div');
		if(undefined==p){return false;}
		p.querySelector('input[type="text"]').value=el.dataset.color;
		p.querySelector('label[for]').style.backgroundColor=el.dataset.color;
		p.querySelector('input[type="checkbox"]').checked=false;
	},
	/**
	* @exclude  - this function is for internal use only and thus excluded from the manual
	*/
	colorwheelClose: function(el){
		let p=wacss.getParent(el,'div');
		if(undefined==p){return false;}
		p.querySelector('input[type="checkbox"]').checked=false;
	},
	/**
	* @name wacss.createCenterpop
	* @describe creates an html centerpop object and returns the content element
	* @param title string
	* @param x number  either blank or 1,2,or 3
	* @return object
	* @usage let cp=wacss.createCenterpop;cp.innerHTML='<h1>Hello</h1>';
	*/
	createCenterpop: function(title,x){
		if(undefined==x){x='';}
		let cp=wacss.getObject('wacss_centerpop'+x);
		if(undefined != cp){
			let cpt_txt=cp.querySelector('.wacss_centerpop_title_text');
			if(undefined != cpt_txt){cpt_txt.innerHTML=title;}
			return cp;
		}
		//centerpop
		cp=document.createElement('div');
		cp.id='wacss_centerpop'+x;
		cp.className='wacss_centerpop';
		cp.setAttribute('data-movable','true');
		//title
		let cpt=document.createElement('div');
		cpt.className='wacss_centerpop_title';
		//title text
		let cpt_text=document.createElement('div');
		cpt_text.style.flex=1;
		cpt_text.className='wacss_centerpop_title_text';
		cpt_text.innerHTML=title;
		cpt.appendChild(cpt_text);
		//title processing
		let cpt_processing=document.createElement('div');
		cpt_processing.id=cp.id+'_processing';
		cpt_processing.className='wacss_centerpop_processing';
		cpt_processing.innerText='*';
		cpt.appendChild(cpt_processing);
		//title close
		let cpt_close=document.createElement('div');
		cpt_close.className='icon-close wacss_centerpop_close';
		cpt_close.title='Close';
		cpt_close.closeid=cp.id;
		cpt_close.onclick=function(){
			wacss.removeId(this.closeid);
		}
		cpt.appendChild(cpt_close);
		//content
		let cpc=document.createElement('div');
		cpc.id='centerpop'+x+'_content';
		cpc.className='wacss_centerpop_content';
		cpc.style.maxHeight='80vh';
		cpc.style.overflow='auto';
		cpc.innerHTML='<div class="align-center">......</div>';
		//appends
		cp.appendChild(cpt);
		cp.appendChild(cpc);
		document.body.appendChild(cp);
		//center
		wacss.centerObject(cp);
		//make movable
		wacss.makeMovable(cp,cpt);
		return cp;
	},
	dismiss: function(el){
		/* if the user is hovering over it, do not close.*/
		if(el.parentElement.querySelector(':hover') == el){
			let wtimer=parseInt(el.timer)*3;
			setTimeout(function(){
				wacss.dismiss(el);
				},wtimer
			);
			return;
		}
		el.classList.add='dismiss';
		setTimeout(function(){
			wacss.removeObj(el);
		},1000);
	},
	/**
	* @name wacss.documentHeight
	* @describe returns the full height of the document.
	* @return integer
	* @usage let h=wacss.documentHeight();
	*/
	documentHeight: function(){
		return Math.max( document.body.scrollHeight, document.body.offsetHeight, 
                       document.documentElement.clientHeight, document.documentElement.scrollHeight, document.documentElement.offsetHeight);
	},
	/**
	* @name wacss.emulateEvent
	* @describe emulates event on object/element.  change, click, etc.
	* @param el mixed  - object or id
	* @param ev string  - event to emulate - change,click, etc
	* @return false
	* @usage wacss.EmulateEvent(el,'change');
	*/
	emulateEvent: function(el,ev){
		el=wacss.getObject(el);
		if(undefined == el){return false;}
		if ("createEvent" in document) {
		    let evt = document.createEvent("HTMLEvents");
		    evt.initEvent(ev, false, true);
		    el.dispatchEvent(evt);
		}
		else{
		    el.fireEvent("on"+ev);
		}
		return false;
	},
	filemanagerReorder:function(dragel,dropel){
		let action=dragel.dataset.action || dropel.dataset.action;
		let dir=dragel.dataset.dir || dropel.dataset.dir;
		let params={_menu:'files',_dir:dir,_reorder:1,_dragname:dragel.dataset.filename,_dropname:dropel.dataset.filename};
		let ok=wacss.post(action,params);
		return false;
	},
	formatAlpha: function(str){
  		let cleaned = ('' + str).replace(/[^a-zA-Z]+/g, '');
  		return cleaned;
	},
	formatAlphanumeric: function(str){
  		let cleaned = ('' + str).replace(/[^a-zA-Z0-9]+/g, '');
  		return cleaned;
	},
	formatInteger: function(str){
  		let cleaned = ('' + str).replace(/[^0-9]+/g, '');
  		return cleaned;
	},
	formatHexcolor: function(str){
  		let cleaned = ('' + str).replace(/[^abcdef0-9]+/g, '');
  		cleaned=cleaned.substr(0,8);
  		let hex='#';
  		switch(cleaned.length){
  			case 3:return hex+cleaned+cleaned;break;
  			case 6:return hex+cleaned;break;
  			case 8:return hex+cleaned;break;
  		}
  		return '';
	},
	formatJSON: function(str){
		let jsonobj=JSON.parse(str);
		return JSON.stringify(jsonobj,null,'\t');
	},
	formatPhone: function(str){
  		//Filter only numbers from the input
  		let cleaned = ('' + str).replace(/[^0-9]+/g, '');
  		cleaned=cleaned.substr(0,11);
  		//Check if the input is of correct
  		let match = cleaned.match(/^(\d{1})?(\d{3})(\d{3})(\d{4})$/);
  		if (match) {
    		//Remove the matched extension code
    		//Change this to format for any country code.
    		let intlCode = (match[1] ? '+'+match[1]+' ' : '')
    		return [intlCode, '(', match[2], ') ', match[3], '-', match[4]].join('')
  		}
  		return cleaned;
	},
	formChanged: function(frm,debug){
		if(undefined == debug || debug != 1){debug=0;}
		if(debug==1){console.log('formChanged');}
		//data-classif="w_red:age:4"
		//data-requiredif, data-displayif, data-hideif, data-blankif, data-readonlyif
		//data-displayif
		let els=frm.querySelectorAll('[data-displayif]');
		for(let i=0;i<els.length;i++){
			if(wacss.formIsIfTrue(frm,els[i].dataset.displayif)){
				if(undefined != els[i].dataset.display){
					if(debug==1){console.log('displayif to display:'+els[i]);}
					els[i].style.display=els[i].dataset.display;
				}
				else{
					if(debug==1){console.log('displayif to initial:'+els[i]);}
					els[i].style.display='initial';
				}
			}
			else{
				if(debug==1){console.log('displayif to none:'+els[i]);}
				els[i].style.display='none';
			}
		}
		//data-hideif
		els=frm.querySelectorAll('[data-hideif]');
		for(let i=0;i<els.length;i++){
			if(wacss.formIsIfTrue(frm,els[i].dataset.hideif)){
				if(debug==1){console.log('hideif to none:'+els[i]);}
				els[i].style.display='none';
			}
			else{
				if(undefined != els[i].dataset.display){
					if(debug==1){console.log('hideif to display:'+els[i]);}
					els[i].style.display=els[i].dataset.display;
				}
				else{
					if(debug==1){console.log('hideif to initial:'+els[i]);}
					els[i].style.display='initial';
				}
			}
		}
		//data-readonlyif
		els=frm.querySelectorAll('[data-readonlyif]');
		for(let i=0;i<els.length;i++){
			if(wacss.formIsIfTrue(frm,els[i].dataset.readonlyif)){
				if(!els[i].hasAttribute('onclick')){
					els[i].setAttribute('onclick','return false');
					els[i].setAttribute('onclickx','1');
				}
				if(debug==1){console.log('readonly set:'+els[i]);}
				els[i].setAttribute('readonly','readonly');
			}
			else{
				if(debug==1){console.log('readonly unset:'+els[i]);}
				els[i].removeAttribute('readonly');
				if(els[i].hasAttribute('onclickx')){
					els[i].removeAttribute('onclick');
					els[i].removeAttribute('onclickx');
				}
			}
		}
		//data-requiredif
		els=frm.querySelectorAll('[data-requiredif]');
		for(let i=0;i<els.length;i++){
			if(wacss.formIsIfTrue(frm,els[i].dataset.requiredif)){
				els[i].setAttribute('required','required');
				if(debug==1){console.log('requiredif set:'+els[i]);}
			}
			else{
				if(debug==1){console.log('requiredif unset:'+els[i]);}
				els[i].removeAttribute('required');
				if(els[i].hasAttribute('data-required')){
					els[i].removeAttribute('data-required');
				}
			}
		}
		//data-blankif
		els=frm.querySelectorAll('[data-blankif]');
		for(let i=0;i<els.length;i++){
			if(wacss.formIsIfTrue(frm,els[i].dataset.blankif)){
				if(debug==1){console.log('blankif set:'+els[i]);}
				switch(els[i].type.toLowerCase()){
					case 'radio':
					case 'checkbox':
						//store all checked values into blankx
						//els[i].dataset.blankx=new Array();
						console.error('blankif does not support checkbox and radio inputs');
					break;
					case 'textarea':
						els[i].dataset.blankx=trim(els[i].innerHTML);
						//is this textarea a codemirror
						if(undefined != els[i].codemirror){
							els[i].codemirror.getDoc().setValue('');
						}
						els[i].innerHTML='';
					break;
					default:
						els[i].dataset.blankx=els[i].value;
						els[i].value='';
					break;
				}
			}
			else{
				if(debug==1){console.log('blankif unset:'+els[i]);}
				if(undefined != els[i].dataset.blankx){
					switch(els[i].type.toLowerCase()){
						case 'radio':
						case 'checkbox':
							//not supported
							console.error('blankif does not support checkbox and radio inputs');
						break;
						case 'textarea':
							//is this textarea a codemirror
							if(undefined != els[i].codemirror){
								els[i].codemirror.getDoc().setValue(els[i].dataset.blankx);
							}
							els[i].innerHTML='';
							els[i].innerHTML=els[i].dataset.blankx;
						break;
						default:
							els[i].value=els[i].dataset.blankx;
						break;
					}
				}
			}
		}
		//data-classif="w_bold:age:12"  data-classif="w_bold:age:12,3 and color:red"
		els=frm.querySelectorAll('[data-classif]');
		for(let i=0;i<els.length;i++){
			let parts=els[i].dataset.classif.split(':');
			let eclass=parts.shift();
			let ifstr=parts.join(':');
			if(wacss.formIsIfTrue(frm,ifstr)){
				if(debug==1){console.log('classif added:'+els[i]);}
				els[i].classList.add(eclass);
			}
			else{
				if(debug==1){console.log('classif removed:'+els[i]);}
				els[i].classList.remove(eclass);
			}
		}
		//data-format
		els=frm.querySelectorAll('input[data-format]');
		if(els.length){
			for(let i=0;i<els.length;i++){
				switch(els[i].dataset.format.toLowerCase()){
					case 'alpha':els[i].value=wacss.formatAlpha(els[i].value);break;
					case 'alphanumeric':els[i].value=wacss.formatAlphanumeric(els[i].value);break;
					case 'integer':els[i].value=wacss.formatInteger(els[i].value);break;
					case 'hexcolor':
						els[i].value=wacss.formatHexcolor(els[i].value);
						if(undefined == els[i].bl_orig){
							els[i].bl_orig=els[i].style.borderLeft;
						}
						if(els[i].value.length > 0){
							els[i].style.borderLeft='6px solid '+els[i].value;
						}
						else{
							els[i].style.borderLeft=els[i].bl_orig;
						}
					break;
					case 'phone':els[i].value=wacss.formatPhone(els[i].value);break;	
				}	
			}
		}
		return;
	},
	formFileUploadInit: function(){
		let els=document.querySelectorAll('input[type="file"].fileupload');
		if(els.length==0){return false;}
		for(let i=0;i<els.length;i++){
			let el=els[i];
			if(undefined != el.dataset.initialized){continue;}
			el.dataset.initialized=1;
			let div=el.nextElementSibling;
			if(!div.classList.contains('fileupload')){continue;}
			let hover=div.nextElementSibling;
			if(!hover.classList.contains('fileupload_hover')){continue;}
			let label=div.querySelector('label');
			if(undefined == label){continue;}
			let erase=div.querySelector('div.icon-erase');
			if(undefined == erase){continue;}
			erase.style.display='none';
			let rbox=div.querySelector('input[type="checkbox"]');
			if(undefined == rbox){continue;}
			let code=div.querySelector('code');
			if(undefined == code){continue;}
			//if it changes then uncheck the remove checkbox
			el.rbox=rbox;
			el.addEventListener("change", function(e) {
				el.rbox.checked=false;
			});
			let files=new Array();
			if(code.innerText.length){
				files= JSON.parse(code.innerText) || new Array();
			}
			let fcnt=files.length;
			if(fcnt > 0){
				erase.style.display='block';
				let tsize=0;
				let htm='<table class="table condensed striped bordered">';
				htm=htm+'<tr><th>Name</th><th>Size</th></tr>';
				for(let f=0;f<fcnt;f++){
					tsize=tsize+files[f].size;
					htm=htm+'<tr><td><a style="max-width:250px;overflow:hidden;text-overflow: ellipsis;white-space:nowrap;" href="'+files[f].name+'" title="'+files[f].name+'" class="w_link" download><span class="icon-download"></span> '+files[f].name+'</a></td><td class="w_nowrap align-right">'+wacss.verboseSize(files[f].size)+'</td></tr>';
				}
				htm=htm+'</table>';
				hover.innerHTML=htm;
				htm='';
				if(fcnt==1){
					htm=files[0].name+' - '+wacss.verboseSize(tsize);
				}
				else{
					htm=fcnt+' files'+' - '+wacss.verboseSize(tsize);			
				}
				label.innerHTML=htm;
				
			}
			else{
				label.innerHTML=label.dataset.text || 'Upload';
				hover.innerHTML='';
			}
			erase.file_element=el;
			erase.rbox=rbox;
			erase.onclick=function() {
				this.file_element.value='';
				this.style.display='none';
				label.innerHTML='Upload';
				rbox.checked=true;
				return false;
			};
			
		}
	},
	formFileUpload: function(el){
		if(undefined==el || undefined==el.files){
			return true;
		}
		let files=el.files || new Array();
		let fcnt=files.length;
		if(fcnt==0){
			let div=el.nextElementSibling;
			if(undefined == div){return true;}
			let label=div.querySelector('label');
			if(undefined != label){label.innerHTML='Upload';}
			let hover=div.nextElementSibling;
			if(undefined != hover){hover.innerHTML='';}
			return true;
		}
		//process data-onfile
		if(undefined != el.dataset.onfile){
			let cfunc=new Function(el.dataset.onfile);
			cfunc();
		}
		let div=el.nextElementSibling;
		if(!div.classList.contains('fileupload')){return true;}
		let hover=div.nextElementSibling;
		if(!hover.classList.contains('fileupload_hover')){return true;}
		let label=div.querySelector('label');
		if(undefined == label){return true;}
		let erase=div.querySelector('div.icon-erase');
		if(undefined == erase){return true;}
		erase.style.display='block';
		let rbox=div.querySelector('input[type="checkbox"]');
		if(undefined == rbox){return true;}
		let code=div.querySelector('code');
		if(undefined == code){return true;}
		erase.style.display='block';
		let tsize=0;
		let htm='<table class="table condensed striped bordered">';
		htm=htm+'<tr><th>Name</th><th>Size</th></tr>';
		for(let f=0;f<fcnt;f++){
			tsize=tsize+files[f].size;
			htm=htm+'<tr><td>'+files[f].name+'</td><td class="w_nowrap align-right">'+wacss.verboseSize(files[f].size)+'</td></tr>';
		}
		htm=htm+'</table>';
		hover.innerHTML=htm;
		htm='';
		if(fcnt==1){
			htm=files[0].name+' - '+wacss.verboseSize(tsize);
		}
		else{
			htm=fcnt+' files'+' - '+wacss.verboseSize(tsize);			
		}
		label.innerHTML=htm;
		erase.file_element=el;
		erase.rbox=rbox;
		erase.onclick=function() {
			this.file_element.value='';
			this.style.display='none';
			label.innerHTML='Upload';
			rbox.checked=true;
			return false;
		};
		return true;
	},
	formIsIfTrue: function(frm,ifstr){
		//age:5
		//age:5,12
		//age:5 and color:red
		//age:5 or color:red
		//age:5,12 and color:red,green
		//Step 1. split ifstr into sets
		if(undefined==ifstr || ifstr.length==0){
			//console.error('formIsIfTrue Error - ifstr not defined');
			return false;
		}
		ifstr=trim(ifstr);
		if(ifstr.length==0){
			//console.error('formIsIfTrue Error - ifstr is empty');
			return false;
		}
		let oper='';
		let sets=ifstr.split(' and ');
		if(sets.length){oper='and';}
		if(oper.length==0){
			sets=ifstr.split(' && ');
			if(sets.length){oper='and';}
		}
		if(oper.length==0){
			sets=ifstr.split(' or ');
			if(sets.length){oper='or';}
		}
		if(oper.length==0){
			sets=ifstr.split(' || ');
			if(sets.length){oper='or';}
		}
		if(sets.length==0){
			sets.push(ifstr);
		}
		let tvals=new Array();
		let fvals=new Array();
		for(let i=0;i<sets.length;i++){
			let parts=sets[i].split(':');
			let fld=parts[0];
			//get fvals for this field - may be one or many
			let formels=frm.querySelectorAll('[name="'+fld+'"],[name="'+fld+'[]"],[id="'+fld+'"]');
			if(formels.length==0){continue;}
			if(formels.length==1 && undefined==formels[0].type){continue;}
			fvals=new Array();
			for(let f=0;f<formels.length;f++){
				if(undefined==formels[f].type){continue;}
				let fel=formels[f];
				switch(fel.type.toLowerCase()){
					case 'select-one':
		    			fvals.push(fel.options[fel.selectedIndex].value);
					break;
					case 'radio':
					case 'checkbox':
						if(fel.checked){
							let fv=fel.value || 1;
							fvals.push(fv);
						}
					break;
					case 'textarea':
						fvals.push(trim(fel.innerText));
					break;
					default:
						fvals.push(fel.value);
					break;
				}
			}
			if(undefined != parts[1]){
				let vals=parts[1].split(',');
				for(let v=0;v<vals.length;v++){
					for(let f=0;f<fvals.length;f++){
						if(fvals[f]==vals[v]){
							tvals.push(1);
							break;	
						}
					}
				}
			}
			else{
				for(let f=0;f<fvals.length;f++){
					if(fvals[f].length){
						tvals.push(1);
					}
				}
			}
		}
		if(oper=='or'){
			if(tvals.length > 0){
				//console.log(ifstr+' = true (or)');
				return true;
			}
			//console.log(ifstr+' = false (or)');
			return false;
		}
		if(tvals.length==sets.length){
			//console.log(ifstr+' = true (and)');
			return true;
		}
		//console.log(ifstr+' = false (and)');
		return false;
	},
	/**
	* @name wacss.formValidate
	* @describe validates form with all the special attributes. i.e. data-requiredif
	* @param frm object - form
	* @return boolean
	* @usage if(!wacss.formValidate(frm)){return false;}
	*/
	formValidate: function(frm){   
    	
	},
	/**
	* @name wacss.function_exists
	* @describe returns true if the javascript function exists
	* @param name string  - name of function to check for
	* @return boolean
	* @usage if(wacss.function_exists('abc')){...}
	*/
	function_exists: function(function_name){   
    	if (typeof function_name == 'string'){  
        	return (typeof window[function_name] == 'function');  
    	} else{  
        	return (function_name instanceof Function);  
    	}
	},
	/**
	* @name wacss.getAllAttributes
	* @describe get all attributes of a specific object or id
	* @param el mixed  - object or id of element
	* @return json object 
	* @usage let atts=wacss.getAllAttributes(el); let x=atts.name;.....
	*/
	getAllAttributes: function(obj){
		let node=wacss.getObject(obj);
		let rv = {};
	    for(let i=0; i<node.attributes.length; i++){
	        if(node.attributes.item(i).specified){
	            rv[node.attributes.item(i).nodeName]=node.attributes.item(i).nodeValue;
				}
			}
	    return rv;
	},
	/**
	* @name wacss.getViewportSize
	* @describe return width and height of viewport
	* @param [w] object defaults to window
	* @return object {w:12,h:332}
	* @usage let vp=wacss.getViewportSize();
	*/
	getViewportSize: function(w) {
	    // Use the specified window or the current window if no argument
	    w = w || window;
	    // This works for all browsers except IE8 and before
	    if (w.innerWidth != null) return { w: w.innerWidth, h: w.innerHeight };
	    // For IE (or any browser) in Standards mode
	    let d = w.document;
	    if (document.compatMode == "CSS1Compat")
	        return { w: d.documentElement.clientWidth,
	           h: d.documentElement.clientHeight };
	    // For browsers in Quirks mode
	    return { w: d.body.clientWidth, h: d.body.clientHeight };
	},
	/**
	* @exclude  - this function is for internal use only and thus excluded from the manual
	*/
	geoLocation: function(fld,opts){
		//fld can be a function: (lat,long) or an input field to set value to: [lat,long] 
		fldObj=wacss.getObject(fld);
		if(undefined==fldObj){
			if(!wacss.function_exists(fld)){
				console.log("wacss.getGeoLocation error: "+fld+' is undefined');
				return false;
			}
		}
  		if(navigator.geolocation) {
    		let options = {
      			enableHighAccuracy: true,
      			timeout: 10000,
      			maximumAge: 5000
    		};
    		//allow options to be set
    		if(undefined != opts){
    			for(let k in opts){
    				if(k == 'onerror'){
    					navigator.geoSetFldFailed=opts[k];
    				}
    				else{
    					options[k]=opts[k];
    				}
    			}
    		}
    		navigator.geoSetFld=fld;
    		navigator.geoOptions=options;
    		navigator.geolocation.getCurrentPosition(
    			function(position){
    				//console.log(navigator.geoSetFld);
    				//console.log(wacss.function_exists(navigator.geoSetFld));
    				if (wacss.function_exists(navigator.geoSetFld)){
    					window[navigator.geoSetFld](position.coords.latitude,position.coords.longitude,navigator.geoOptions);
    				}
    				else{
    					//check for showmap option
    					if(undefined!=navigator.geoOptions.showmap && navigator.geoOptions.showmap==1){
    							navigator.geoOptions.input=navigator.geoSetFld;
    							wacss.geoLocationMap(position.coords.latitude,position.coords.longitude,navigator.geoOptions);
    					}
    					else{
    						fldObj=wacss.getObject(navigator.geoSetFld);
    						fldObj.value='['+position.coords.latitude+','+position.coords.longitude+']';	
    					}
    				}
    				
    				return false; 
    			},
    			function(err){
    				//err returns err.code and err.message
    				//err.code: 1=permission denied, 2=position unavailable, 3=timeout
    				navigator.geoOptions.code=err.code;
    						navigator.geoOptions.message=err.message;
    				if(undefined != navigator.geoSetFldFailed){
    					if (wacss.function_exists(navigator.geoSetFldFailed)){
	    					window[navigator.geoSetFldFailed](navigator.geoOptions);
	    				}
	    				else{
	    					if(undefined==navigator.geoOptions.showmap && navigator.geoOptions.showmap==1){
	    						alert(err.message);
	    					}
	    					else{
		    					let errfld=document.querySelector(navigator.geoSetFldFailed);
		    					if(undefined != errfld){
		    						setText(wacss.getObject(errfld),err.message);
		    					}
		    					else{
		    						console.log('wacss.getGeoLocation error. Invalid onerror value');
		    						console.log(navigator.geoSetFldFailed);
		    						console.log(err.message);
		    					}
		    				}
	    				}
    				}
    				else{
    					if(undefined==navigator.geoOptions.showmap && navigator.geoOptions.showmap==1){
    						alert(err.message);
    					}
    					else{
    						console.log('wacss.getGeoLocation error. No onerror set.');
    						console.log(navigator.geoOptions);
    					}
    				}
    				return false;
    			},
    			options
    		);
  		} 
		return false;
	},
	/**
	* @exclude  - this function is for internal use only and thus excluded from the manual
	*/
	geoLocationMap: function(lat,long,params){
		//console.log('geoLocationMap');
		lat=parseFloat(lat);
		long=parseFloat(long);
		//console.log(lat);
		//console.log(long);
		//console.log(params);
		if(undefined == params){params={};}
		if(undefined == params.displayname){params.displayname='Click on map to select';}
		params.lat=lat;
		params.long=long;
		if(undefined != document.getElementById('geolocationmap_content')){
			params.div='geolocationmap_content';
			wacss.geoLocationMapContent(params);
			wacss.centerObject('geolocationmap');
			return 1;
		}
		let popup=document.createElement('div');
		popup.id='geolocationmap_popup';
		popup.style='z-index:99999999;background:#FFF;width:80vw;height:80vh;position:absolute;display:flex;flex-direction:column;justify-content:flex-start;border-radius:5px;box-shadow: 0 4px 8px 0 rgb(0 0 0 / 20%), 0 6px 20px 0 rgb(0 0 0 / 19%);';
		let popup_title=document.createElement('div');
		popup_title.style="display:flex;justify-content:center;align-items:center;width:100%;";
		popup_title.id='geolocationmap_title';
		let popup_title_img=document.createElement('img');
		popup_title_img.src='/wfiles/svg/google-maps.svg';
		popup_title_img.style='height:32px;width:auto;';
		popup_title.appendChild(popup_title_img);
		let popup_title_text=document.createElement('div');
		popup_title_text.style='padding-top:5px;flex:1;height:100%;line-height:1.2rem;color:#FFF;background:#b5b5b5;font-size:1.2rem;text-align:center;';
		popup_title_text.innerHTML=params.displayname;
		popup_title.appendChild(popup_title_text);
		let popup_title_close=document.createElement('span');
		popup_title_close.className='icon-close w_red';
		popup_title_close.style='background:#b5b5b5;padding-right:10px;height:100%;padding-top:5px;cursor:pointer;';
		popup_title_close.onclick=function(){
			wacss.removeId('geolocationmap_popup');
		}
		popup_title.appendChild(popup_title_close);
		popup.appendChild(popup_title);
		let popup_content=document.createElement('div');
		popup_content.id='geolocationmap_content';
		popup_content.style='flex:1;';
		popup.appendChild(popup_content);
		params.div=popup_content;
		wacss.geoLocationMapContent(params);
		document.body.appendChild(popup);
		wacss.centerObject(popup);
	},
	/**
	* @exclude  - this function is for internal use only and thus excluded from the manual
	*/
	geoLocationMapContent:function(params){
		//console.log('geoLocationMapContent');
		//console.log(params);
		if(undefined == params){params={};}
		params.zoom=params.zoom||13;
		//return;
		let myLatlng={ lat: params.lat, lng: params.long };
		let map_params={
			center: myLatlng,
			streetViewControl: false,
			mapTypeId: 'roadmap',
			zoom: params.zoom,

			styles: []
		};
		if(undefined != params['hide'] && params['hide'].toLowerCase().indexOf('poi') != -1){
			let poi={ featureType: 'poi', elementType: 'labels', stylers: [{ visibility: 'off' }]};
			map_params.styles.push(poi);
		}
		
		let map = new google.maps.Map(params.div, map_params);
		map.addListener('click', function (event) {
			// If the event is a POI
		  	if (event.placeId) {
				// Call event.stop() on the event to prevent the default info window from showing.
		    	event.stop();
		  	}
		});
		//add markers
		/* markers MUST have position - json latlong - {lat:, lng:} */
		/* markers CAN have title - hello world */
		/* markers CAN have label - B or 2 -- single letter or number */
		/* markers CAN have icon - https://some_url_to_png */
		if(undefined != params['markers']){
			if(!Array.isArray(params['markers'])){
				params['markers']=new Array(params['markers']);
			}
			for(let m=0;m<params['markers'].length;m++){
				let marker=params['markers'][m];
				marker.map=map;
				let mark=new google.maps.Marker(marker);
			}
		}
		// Create the initial InfoWindow.
		if(undefined == params.hideinfo){
			let infoWindow = new google.maps.InfoWindow({
			  content: params.displayname,
			  position: myLatlng,
			});
			infoWindow.open(map);
			let mylatlonval='['+myLatlng.lat+','+myLatlng.lng+']';
			let htm='';
			htm='<div class="align-center w_smallest w_gray">'+mylatlonval+'</div><div class="align-center w_padtop"><span class="icon-map-marker w_red"></span> Location</div>';
			infoWindow.setContent(htm);
			if(undefined == params.readonly){			
				map.params=params;
				// Configure the click listener.
				map.addListener("click", (mapsMouseEvent) => {
					//console.log(map.params.input);
					let latlon=mapsMouseEvent.latLng.toJSON();
					let latlonval='['+latlon.lat+','+latlon.lng+']';
				  	// Close the current InfoWindow.
				  	infoWindow.close();
				  	// Create a new InfoWindow.
				  	infoWindow = new google.maps.InfoWindow({
				    	position: mapsMouseEvent.latLng,
				  	});
				  	let chtm='<div class="align-center w_smallest w_gray">'+latlonval+'</div><div class="align-center w_padtop"><span class="icon-map-marker w_red"></span> <span data-lat="'+latlon.lat+'" data-lon="'+latlon.lng+'" data-latlon="'+latlonval+'" data-input="'+map.params.input+'" class="w_pointer" onclick="return wacss.geoLocationMapSetValue(this);"><span class="w_bigger w_gray icon-save w_pointer"></span> Save</span></div>';
				  	infoWindow.setContent(chtm);
				  	infoWindow.open(map);
				});
			}
		}
	},
	/**
	* @exclude  - this function is for internal use only and thus excluded from the manual
	*/
	geoLocationMapSetValue: function(el){
		let inp=wacss.getObject(el.dataset.input);
		inp.value=el.dataset.latlon;
		let clickdiv=wacss.getObject(el.dataset.input+'_clickdiv');
		if(undefined != clickdiv){
			clickdiv.dataset.lat=el.dataset.lat;
			clickdiv.dataset.lon=el.dataset.lon;
		}
		wacss.removeId('geolocationmap_popup');
		return false;
	},
	/**
	* @name wacss.getObject
	* @describe returns element with that selector or id
	* @param str mixed - id or query selector or object
	* @return element or null
	* @usage let obj=wacss.getObject('myid')
	*/
	getObject: function(obj){
		//info: returns the object identified by the object or id passed in
		if(typeof(obj)=='object'){return obj;}
	    else if(typeof(obj)=='string'){
	    	//try querySelector
	    	try{
	    		let qso=document.querySelector('#'+obj);
	    		if(typeof(qso)=='object'){return qso;}
	    	}
			catch(e){}
			try{
	    		qso=document.querySelector(obj);
	    		if(typeof(qso)=='object'){return qso;}
	    	}
			catch(e){}
	    	//try getElementById
			if(undefined != document.getElementById(obj)){return document.getElementById(obj);}
			else if(undefined != document.getElementsByName(obj)){
				let els=document.getElementsByName(obj);
				if(els.length ==1){return els[0];}
	        	}
			else if(undefined != document.all[obj]){return document.all[obj];}
	    }
	    return null;
	},
	/**
	* @name wacss.getParent
	* @describe walks up the DOM and returns the first parent with that name and optional class
	* @param el obj - starting element
	* @param name string - name of parent element (table, tr, td, div, etc...)
	* @params [classname] string - optional classname to match
	* @return element or null
	* @usage let tbl=wacss.getParent(tdEl,'table');
	*/
	getParent: function(obj,name,classname){
		if(undefined == obj){return null;}
		if(undefined == name){name='';}
		if(undefined == classname){classname='';}
		if(name.length > 0 || classname.length > 0){
			let count = 1;
			while(count < 1000) {
				if(undefined == obj.parentNode){return null;}
				obj = obj.parentNode;
				if(!typeof(obj)){return null;}
				if(classname.length && name.length){
					if(
						obj.nodeName.toLowerCase() == name.toLowerCase()
						&& obj.classList.contains(classname)
						){
						return obj;
					}
				}
				else if(classname.length){
					if(obj.classList.contains(classname)){
						return obj;
					}
				}
				else if(name.length){
					if(obj.nodeName.toLowerCase() == name.toLowerCase()){
						return obj;
					}
				}
				count++;
			}
			return null;	
		}
		let cObj=wacss.getObject(obj);
		if(undefined == cObj){return abort("undefined object passed to getParent");}
		if(undefined == cObj.parentNode){return cObj;}
		let pobj=cObj.parentNode;
		if(typeof(cObj.parentNode) == "object"){return cObj.parentNode;}
		else{return wacss.getParent(pobj,name,classname);}
	},
	getParentByAtt: function(obj,att,val){
		if(undefined == obj){return null;}
		if(undefined != att){
			let count = 1;
			while(count < 1000) {
				if(undefined == obj.parentNode){return null;}
				obj = obj.parentNode;
				if(!typeof(obj)){return null;}
				let natt=obj.getAttribute(att);
				if(undefined != natt && (undefined==val || val.length==0 || natt.toLowerCase() == val.toLowerCase())){
					return obj;
				}
				count++;
			}
			return null;	
		}
		return null;
	},
	/**
	* @name wacss.getSelectedText
	* @describe returns selected text in the document or field if specified
	* @param el obj - fld
	* @return array
	* @usage let seltext=wacss.getSelectedText();
	*/
	getSelectedText: function(fld){
		//info: returns selected text on the page
		let txt = '';
		if(undefined != fld){
			fld=wacss.getObject(fld);
			if(undefined == fld){return '';}
			if(document.selection){
				fld.focus();
			    txt = document.selection.createRange();
		    	}
			else{
				let len = fld.value.length;
				let start = fld.selectionStart;
				let end = fld.selectionEnd;
				txt = fld.value.substring(start, end);
		    	}
			}
		else{
		    if (window.getSelection){
		        txt = window.getSelection();
		    }
		    else if (document.getSelection){
		        txt = document.getSelection();
			}
		    else if (document.selection){
		        txt = document.selection.createRange().text;
		    }
		}
		return txt;
	},
	/**
	* @name wacss.getTableRowValues
	* @describe returns a key/value array using the first row in the table as the keys
	* @param el obj - element
	* @return mixed
	* @usage let params=wacss.getTableRowValues(el);
	*/
	getTableRowValues: function(el,s) {
	   //info: getTableRowValues .
	   //info: if s=1, then returns as a URL string instead of an array
	   //usage: alert(getTableRowValues(this,1));
	   if(undefined == s){s=0;}
	   ptable=wacss.getParent(el,'table');
	   let str='';
	   let row=new Array();
	   if(undefined == ptable){
	       //no parent table - log and return
	       console.log('wacss.getTableRowValues Error - no parent table');
	       if(s==1){return str;}
	       return row; 
	   }
	   let keys=new Array();
	   let vals=new Array();
	   let tr=wacss.getParent(el,'tr'); // assuming you have getParent method
	   
	   //iterate through rows
	   for (let i = 0, tableRow; tableRow = ptable.rows[i]; i ++) {
	       //rows would be accessed using the "tableRow" variable assigned in the for loop
	       for (let j = 0, col; col = tableRow.cells[j]; j ++) {
	           //iterate through columns
	           //columns would be accessed using the "col" variable assigned in the for loop
	           var cval;
	           if(i==0){
	               cval=wacss.getText(col);
	               keys.push(cval);
	           }
	           if(tableRow==tr){
	               // Check for input/select elements first
	               let input = col.querySelector('input, select');
	               if(input) {
	                   cval = input.value;
	               } else {
	                   cval = wacss.getText(col);
	               }
	               vals.push(cval);
	           }
	       }
	   }
	   for(let i=0;i<keys.length;i++){
	       let key=strtolower(keys[i]);
	       row[key]=vals[i];
	       str+=key+'='+vals[i]+'&';
	   }
	   if(s==1){return str;}
	   return row;
	},
	/**
	* @name wacss.getText
	* @describe returns text in any object
	* @param el obj - element
	* @return string
	* @usage let txt=wacss.getText(el);
	*/
	getText: function(obj){
		let cObj=wacss.getObject(obj);
		if(undefined == cObj){return '';}
		if(undefined != cObj.value){return cObj.value;}
	    else if(undefined != cObj.innerHTML){return cObj.innerHTML;}
	    else if(undefined != cObj.innerText){return cObj.innerText;}
	    else{
			//alert('unable to getText on '+cObj);
	    	}
	    return '';
	},
	/**
	* @name wacss.getSiblings
	* @describe returns an array of sibling elements in the DOM
	* @param el obj - starting element
	* @return array
	* @usage let els=wacss.getSiblings(tdEl);
	*/
	getSiblings: function (elem) {
		// Setup siblings array and get the first sibling
		let siblings = [];
		let sibling = elem.parentNode.firstChild;

		// Loop through each sibling and push to the array
		while (sibling) {
			if (sibling.nodeType === 1 && sibling !== elem) {
				siblings.push(sibling);
			}
			sibling = sibling.nextSibling
		}

		return siblings;

	},
	guid: function () {
	    function _p8(s) {
	        let p = (Math.random().toString(16)+"000000000").substr(2,8);
	        return s ? "-" + p.substr(0,4) + "-" + p.substr(4,4) : p ;
	    }
	    return _p8() + _p8(true) + _p8(true) + _p8();
	},
	hexToRgb: function(hex) {
		if(undefined==hex){
			return {
				r:255,
				g:255,
				b:255
			};
		}
		//check for rgb(r,g,b) string;
		let rgb_regex=/rgb\(([0-9]+?),\ ([0-9]+?),\ ([0-9]+?)\)/;
		let rgb_match=hex.toString().match(rgb_regex);
		if(undefined != rgb_match && undefined != rgb_match[1]){
			return {r:rgb_match[1],g:rgb_match[2],b:rgb_match[3]};
		}
		// Expand shorthand form (e.g. "03F") to full form (e.g. "0033FF")
		let shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
		hex = hex.replace(shorthandRegex, function (m, r, g, b) {
			return r + r + g + g + b + b;
		});

		let result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
		return result ? {
			r: parseInt(result[1], 16),
			g: parseInt(result[2], 16),
			b: parseInt(result[3], 16)
		} : {
			r:255,
			g:255,
			b:255
		}
		;
    },
    /**
	* @name wacss.loadJsText
	* @describe loads a text string as a javascript function
	* @param string function name
	* @param string function body
	* @return boolean true
	* @usage wacss.loadJsText('myfunc',str); myfunc();
	*/
	loadJsText:function(name,body){
		let code = 'this.f = function ' + name + '() {'+body+'}';
		eval(code);
		return true;
	},
	/**
	* @name wacss.implode
	* @describe Joins array elements placing glue string between items and return one string
	* @param string glue
	* @param array 
	* @return string
	* @usage let list=wacss.implode(', ',items);
	* @source https://phpjs.org/functions
	*/
    implode:function(glue,pieces){
	    let i='',retVal='',tGlue='';
	    if (arguments.length === 1) {
	        pieces=glue;
	        glue='';
	    }
		if (typeof(pieces) === 'object') {
	        if (pieces instanceof Array) {
				return pieces.join(glue);
	    	}
			else {
	            for (i in pieces) {
					retVal += tGlue + pieces[i];
	                tGlue = glue;
	            }
	            return retVal;
	        }
		}
		else {
	        return pieces;
	    }
	},
	/**
	* @name wacss.in_array
	* @describe emulates PHP function
	* @param needle string
	* @param haystack array
	* @return boolean
	* @usage let arr=new Array(1,3,6);
	* @usage if(wacss.in_array(3,arr)){....}
	* @sourece https://phpjs.org/functions
	*/
	in_array: function(needle, haystack) {
	    let length = haystack.length;
	    for(let i = 0; i < length; i++) {
	    	//console.log('in_array',haystack[i],needle);
	        if(haystack[i] == needle){return true;}
	    }
	    return false;
	},
	/**
	* @name wacss.init
	* @describe initializer for all other wacss init functions
	* @return false
	* @usage wacss.init();
	*/
	init: function(){
		/*wacssedit*/
		try{wacss.initOnloads();}catch(e){console.log('wacss.initOnloads failed');}
		try{wacss.initWacssEdit();}catch(e){console.log('wacss.initWacssEdit failed');}
		try{wacss.initChartJs();}catch(e){console.log('wacss.initChartJs failed');}
		try{wacss.initTabs();}catch(e){console.log('wacss.initTabs failed');}
		try{wacss.initCodeMirror();}catch(e){console.log('wacss.initCodeMirror failed');}
		try{wacss.initDropdowns();}catch(e){console.log('wacss.initDropdowns failed');}
		try{wacss.initEditor();}catch(e){console.log('wacss.initEditor failed');}
		try{wacss.initWhiteboard();}catch(e){console.log('wacss.initWhiteboard failed');}
		try{wacss.initSignaturePad();}catch(e){console.log('wacss.initSignaturePad failed');}
		try{wacss.initDrag();}catch(e){console.log('wacss.initDrag failed');}
		try{wacss.initHovers();}catch(e){console.log('wacss.initHovers failed');}
		try{wacss.leftMenu('init');}catch(e){console.log('wacss.leftMenu failed');}
		try{wacss.initDatePicker();}catch(e){console.log('wacss.initDatePicker failed');}
		return false;
	},

	/**
	* @name wacss.leftMenu
	* @describe leftMenu functions: init, open, close, width
	* @describe <div class="wacss_leftmenu w_white" data-state="open" data-onload="wacss.leftMenu('width');>
	* @describe 	<div class="wacss_leftmenu-menu">
	* @describe 		your menu goes here
	* @describe 	</div>
	* @describe </div>
	* @describe <div class="wacss_leftmenu-content">
	* @describe  	main content goes here
	* @describe </div>
	* @param action string - init, open, close, width
	* @return boolean
	* @usage wacss.leftMenu('init');
	* @usage wacss.leftMenu('width');
	* @usage wacss.leftMenu('open');
	* @usage wacss.leftMenu('close');
	*/
	leftMenu: function(action){
		let leftmenu=document.querySelector('.wacss_leftmenu');
		if(undefined == leftmenu){return false;}
		switch(action.toLowerCase()){
			case 'init':
				if(undefined != leftmenu.dataset.initialized){
					return false;
				}
				leftmenu.dataset.initialized=1;
				//verify that a checkbox with an id of wacss_leftmenu-toggle exist
				let ckobj=document.getElementById('wacss_leftmenu-toggle');
				if(undefined==ckobj){
					let ck=document.createElement('input');
					ck.type='checkbox';
					ck.id='wacss_leftmenu-toggle';
					ck.addEventListener('click', function(){
						wacss.leftMenu('width');
					});
					if(undefined != leftmenu.dataset.state && leftmenu.dataset.state=='open'){
						ck.checked=true;
						ck.dataset.state='open';
					}
					leftmenu.parentNode.insertBefore(ck, leftmenu);
					let lab=document.createElement('label');
					lab.setAttribute('for','wacss_leftmenu-toggle');
					lab.className='wacss_leftmenu-tab';
					leftmenu.insertBefore(lab, leftmenu.firstChild);
				}		
				// Initial width calculation
				document.documentElement.style.setProperty('--wacss_leftmenu-width', leftmenu.offsetWidth + 'px');
				// Update left menu width on window resize
				window.addEventListener('resize', function(){
					wacss.leftMenu('width');
				});
				// Update left menu width with a treeview expands
			  	document.querySelectorAll('.wacss_treeview details').forEach(detail => {
					detail.addEventListener('toggle', () => {
						// Small delay to allow animation to complete
						setTimeout(function(){
							wacss.leftMenu('width');
						}, 10);
					});
				});
				wacss.registerTouchEvent(function(d){
					switch(d.toLowerCase()){
						case 'left':
							document.getElementById('wacss_leftmenu-toggle').checked=false;
						break;
						case 'right':
							document.getElementById('wacss_leftmenu-toggle').checked=true;
						break;
					}
				});
				return true;
			break;
			case 'open':
				document.getElementById('wacss_leftmenu-toggle').checked=true;
				return true;
			break;
			case 'close':
				document.getElementById('wacss_leftmenu-toggle').checked=false;
				return true;
			break;
			case 'width':
		    	document.documentElement.style.setProperty('--wacss_leftmenu-width', leftmenu.offsetWidth + 'px');
		    	return true;
			break;
		}
		return false;
	},
	/**
	* @name wacss.initDrag
	* @describe initializes draggable="true" elements and elements with data-ondrop set
	* @describe example drag element:  <div draggable="true" data-dropzones="div.drop1">...</div>
	* @describe example drop element:  <div data-ondrop="myDropFunc">...</div>
	* @describe example drop function: function myDropFunc(dragEl,dropEl){...}
	* @describe Note: During drag the class "dragging" is set on the dragging element
	* @return false
	* @usage wacss.initDrag();
	*/
	initDrag: function(){
		/* <div draggable="true" dropzones="div.drop1">...</div> */
		let draggables=document.querySelectorAll('[draggable="true"]');;
		for(let i=0;i<draggables.length;i++){
			//console.log(draggables[i]);
			//check to see if we have already initialized this one
			if(undefined != draggables[i].dataset.draggable_initialized){
				continue;
			}
			draggables[i].dataset.draggable_initialized=1;
			//generate an id if it does not already have one
			if(undefined==draggables[i].id || draggables[i].id.length==0){
				draggables[i].id='id_'+Math.random().toString(36).slice(2);
			}
			//set dragstart
			draggables[i].ondragstart=function(ev){
				ev.dataTransfer.clearData();
				//get the element that has a draggable
				let el;
				if(undefined == this.draggable){
					el=wacss.getParentByAtt(this,'draggable','true');
				}
				else{el=this;}
				if(undefined==el){
					console.log('wacss.initDrag ondrag start error: no el');
				}
				//console.log('drag start '+el.id);
				ev.dataTransfer.setData("Text", el.id);
				el.classList.add('dragging');
			}
			//set dragend
			draggables[i].ondragend=function(ev){
				//get the element that has a draggable
				let el;
				if(undefined == this.draggable){
					el=wacss.getParentByAtt(this,'draggable','true');
				}
				else{el=this;}
				if(undefined==el){
					console.log('wacss.initDrag ondrag start error: no el');
				}
				//console.log('drag end '+el.id);
				ev.dataTransfer.clearData("Text");
				el.classList.remove('dragging');
			}
		}
		//dropzones
		let dropzones=document.querySelectorAll('[data-ondrop]');
		for(let d=0;d<dropzones.length;d++){
			//check to see if we have already initialized this one
			if(undefined!=dropzones[d].dataset.ondrop_initialized){
				continue;
			}
			dropzones[d].dataset.ondrop_initialized=1;
			//generate an id if it does not already have one
			if(undefined==dropzones[d].id || dropzones[d].id.length==0){
				dropzones[d].id='id_'+Math.random().toString(36).slice(2);
			}
			//set dragover
			dropzones[d].ondragover=function(ev){
				ev.preventDefault();
			}
			//set dragenter
			dropzones[d].ondragenter=function(ev){
				ev.target.classList.add("dropping");
			}
			//set dragleave
			dropzones[d].ondragleave=function(ev){
				if (ev.target.classList.contains("dropping")) {
				    ev.target.classList.remove("dropping");
				}
			}
			//set drop
			dropzones[d].ondrop=function(ev){
				ev.stopPropagation();
				let dragid = ev.dataTransfer.getData("Text");
				let dragel=document.getElementById(dragid);
				if(undefined==dragel){
					console.log('wacss.initDrag ondrop error: no dragel');
					return false;
				}
				let tel=ev.target;
				if(undefined == tel.dataset.ondrop){
					console.log('getting parent');
					tel=wacss.getParentByAtt(tel,'data-ondrop');
				}
				if(undefined==tel){
					console.log('wacss.initDrag ondrop error: no tel');
					return false;
				}
				tel.classList.remove('dropping');
				if(undefined==tel.dataset.ondrop){
					console.log('wacss.initDrag ondrop error: no ondrop in tel');
					return false;
				}
				if(tel.dataset.ondrop.length==0){
					console.log('wacss.initDrag ondrop error: ondrop is blank in tel');
					return false;
				}
				if(tel.dataset.ondrop.indexOf('wacss.') !=-1){
					tel.dataset.ondrop=tel.dataset.ondrop.replace('wacss.','');
					wacss[tel.dataset.ondrop](dragel,tel);
				}
				else{window[tel.dataset.ondrop](dragel,tel);}
			}
		}
		return false;
	},
	initChartJsBehavior: function(chartid){
		let list=document.querySelectorAll('[data-behavior="chartjs"]');
		if(undefined != chartid){
			list=document.querySelectorAll('#'+chartid);
		}
		if(list.length==0){return false;}
		//load Chart is it is not already loaded
		if(undefined == Chart){
			//console.log('loading Chartjs, etc');
			wacss.loadScript('/wfiles/js/extras/chart.min.js');
			wacss.loadScript('/wfiles/js/extras/chartjs-plugin-datalabels.min.js');
			wacss.loadScript('/wfiles/js/extras/chartjs-plugin-doughnutlabel.min.js');
		}
		if(undefined == Chart){
			console.log('Error in initChartJsBehavior: Chartjs is not defined');
			return false;
		}
		let gcolors = new Array(
	        'rgba(255,159,64,0.4)',
	        'rgba(75,192,192,0.4)',
	        'rgba(255,99,132,0.4)',
	        'rgba(54,162,235,0.4)',
	        'rgba(153,102,255,0.4)',
	        'rgba(218,165,32,0.4)',
	        'rgba(233,150,122,0.4)',
	        'rgba(189,183,107,0.4)',
	        'rgba(154,205,50,0.4)',
	        'rgba(255,228,196,0.4)',
	        'rgba(244,164,96,0.4)',
	        'rgba(176,196,222,0.4)',
	        'rgba(188,143,143,0.4)',
	        'rgba(255,228,225,0.4)',
	        'rgba(201,203,207,0.4)'
	    );
	    let gbcolors = new Array(
	        'rgb(255,159,64)',
	        'rgb(75,192,192)',
	        'rgb(255,99,132)',
	        'rgb(54,162,235)',
	        'rgb(153,102,255)',
	        'rgb(218,165,32)',
	        'rgb(233,150,122)',
	        'rgb(189,183,107)',
	        'rgb(154,205,50)',
	        'rgb(255,228,196)',
	        'rgb(244,164,96)',
	        'rgb(176,196,222)',
	        'rgb(188,143,143)',
	        'rgb(255,228,225)',
	        'rgb(201,203,202)'
	    );
	    let colors=new Array();
	    let bcolors=new Array();
		for(let i=0;i<list.length;i++){
			if(undefined==list[i].id){
				console.log('Error in initChartJsBehavior: missing id attribute');
				console.log(list[i]);
				continue;
			}
			if(undefined==list[i].dataset.type){
				console.log('Error in initChartJsBehavior: missing data-type attribute');
				console.log(list[i]);
				continue;
			}
			if(undefined==chartid && undefined!=list[i].dataset.initialized){continue;}
			list[i].dataset.initialized=1;
			let datadiv=document.querySelector('#'+list[i].id+'_data'); 
			if(undefined==datadiv){
				console.log('Error in initChartJsBehavior: missing data div attribute');
				console.log(list[i]);
				continue;
			}
			//setup the config: type, data, options
			let lconfig = {
				type:list[i].dataset.type,
				data:{
					labels:[],
					datasets:[]
				}
			};
			//options
			let optionsdiv=datadiv.querySelector('options');
			if(undefined != optionsdiv){
				let optionsjson=wacss.trim(optionsdiv.innerText);
				//lconfig.options=JSON.parse(optionsjson);
				lconfig.options=JSON.parse(optionsjson);
			}
			else{
				lconfig.options={
					responsive: true,
            		events: false,
            		animation: {animateScale:false,animateRotate:true},
            		title:{display:false},
            		tooltips: {enabled:false,intersect: false,mode:'index'},
            		plugins:{
            			labels:{
            				fontColor:function (data) {
								let rgb = {};
								rgb=wacss.hexToRgb(data.dataset.backgroundColor[data.index]);
								if(undefined == rgb.r){return '#FFF';}
								let threshold = 140;
								let luminance = 0.299 * rgb.r + 0.587 * rgb.g + 0.114 * rgb.b;
								return luminance > threshold ? 'black' : 'white';
								},
							precision:0,
							showActualPercentages:true
            			}
            		}
				};
			}
			//labels
			let labelsdiv=datadiv.querySelector('labels');
			if(undefined != labelsdiv){
				let labelsjson=wacss.trim(labelsdiv.innerText);
				lconfig.data.labels=JSON.parse(labelsjson);
			}
			//colors
			let colorsdiv=datadiv.querySelector('colors');
			if(undefined != colorsdiv){
				let colorsjson=wacss.trim(colorsdiv.innerText);
				colors=JSON.parse(colorsjson);
			}
			else{
				colors=gcolors;
			}
			//bcolors
			let bcolorsdiv=datadiv.querySelector('bcolors');
			if(undefined != bcolorsdiv){
				let bcolorsjson=wacss.trim(bcolorsdiv.innerText);
				bcolors=JSON.parse(bcolorsjson);
			}
			else{
				bcolors=gbcolors;
			}
			//datasets
			let datasets=datadiv.querySelectorAll('dataset');
			for(let d=0;d<datasets.length;d++){
				let datasetjson=wacss.trim(datasets[d].innerText);
				let json=JSON.parse(datasetjson);  		
				let dataset={
					label:datasets[d].dataset.label || datasets[d].dataset.title || '',
					borderWidth:datasets[d].dataset.borderwidth || list[i].dataset.borderwidth || 1,
                    //type:datasets[d].dataset.type || lconfig.type,
					data: json
				};
				switch(list[i].dataset.type.toLowerCase()){
					case 'pie':
					case 'doughnut':
						//set colors to the set for pies and doughnuts
						dataset.hoverOffset=4;
						dataset.backgroundColor=colors || null;
						dataset.borderColor=bcolors || null;
					break;
					default:
						dataset.backgroundColor = datasets[d].dataset.backgroundcolor || colors[d] || null;
						dataset.borderColor = datasets[d].dataset.bordercolor || bcolors[d] || null;
						dataset.borderRadius = 3;
					break;
				}
				/* --  fill  -- */
				if(undefined != datasets[d].dataset.fill && 
					(datasets[d].dataset.fill.toLowerCase()=='0' || datasets[d].dataset.fill.toLowerCase()=='false')
					){
					dataset.fill=false;
				}
				else if(undefined != list[i].dataset.fill && 
					(list[i].dataset.fill.toLowerCase()=='0' || list[i].dataset.fill.toLowerCase()=='false')
					){
					dataset.fill=false;
				}
				/* --  borderDash  -- */
				if(undefined != datasets[d].dataset.borderdash){
					dataset.borderDash=datasets[d].dataset.borderdash;
				}
				else if(undefined != datasets[d].dataset.borderDash){
					dataset.borderDash=datasets[d].dataset.borderDash;
				}
				/* --  yAxisID  -- */
				if(undefined != datasets[d].dataset.yaxis){
					dataset.yAxisID=datasets[d].dataset.yaxis;
				}
				else if(undefined != datasets[d].dataset.yAxisID){
					dataset.yAxisID=datasets[d].dataset.yAxisID;
				}
				/* --  showLine  -- */
				if(undefined != datasets[d].dataset.showline && 
					(datasets[d].dataset.showline =='0' || datasets[d].dataset.showline.toLowerCase()=='false')
					){
					dataset.showLine=false;
				}
				else if(undefined != datasets[d].dataset.showLine && 
					(datasets[d].dataset.showLine =='0' || datasets[d].dataset.showLine.toLowerCase()=='false')
					){
					dataset.showLine=false;
				}
				/* --  pointHoverBackgroundColor  -- */
				if(undefined != datasets[d].dataset.pointhoverbackgroundcolor){
					dataset.pointHoverBackgroundColor=datasets[d].dataset.pointhoverbackgroundcolor;
				}
				else if(undefined != datasets[d].dataset.pointHoverBackgroundColor){
					dataset.pointHoverBackgroundColor=datasets[d].dataset.pointHoverBackgroundColor;
				}
				/* --  pointHoverBorderColor  -- */
				if(undefined != datasets[d].dataset.pointhoverbordercolor){
					dataset.pointHoverBorderColor=datasets[d].dataset.pointhoverbordercolor;
				}
				else if(undefined != datasets[d].dataset.pointHoverBorderColor){
					dataset.pointHoverBorderColor=datasets[d].dataset.pointHoverBorderColor;
				}
				lconfig.data.datasets.push(dataset);
			}
			//options - responsive
			if(undefined != list[i].dataset.responsive && 
				(list[i].dataset.responsive=='0' || list[i].dataset.responsive.toLowerCase()=='false')
				){
				lconfig.options.responsive=false;
			}
			//options - title
			if(undefined != list[i].dataset.title){
				lconfig.options.title={display:true,text:list[i].dataset.title};
			}
			//options - scales - x,y - stacked
			if(undefined != list[i].dataset.stacked && (list[i].dataset.stacked.toLowerCase()==1 || list[i].dataset.stacked.toLowerCase()=='true')){
				lconfig.options.scales={
					xAxes:[{stacked:true}],
					yAxes:[{stacked:true}]
				};
			}
			if(undefined != list[i].dataset.beginatzero && (list[i].dataset.beginatzero==1 || list[i].dataset.beginatzero.toLowerCase()=='true')){
				if(undefined == lconfig.options.scales){
					lconfig.options.scales={
						yAxes:[{ticks:{beginAtZero:true}}]
					};
				}
				if(undefined == lconfig.options.scales.yAxes){
					lconfig.options.scales.yAxis=[{ticks:{beginAtZero:true}}];
				}
			}
			//options - plugins - legend - display
			if(undefined != list[i].dataset.legenddisplay && 
				(list[i].dataset.legenddisplay=='0'|| list[i].dataset.legenddisplay.toLowerCase()=='false')
				){
				lconfig.options.legend={display:false};
			}
			//options - plugins - labels - render
			if(undefined != list[i].dataset.render){
				lconfig.options.plugins.labels.render=list[i].dataset.render;
			}
			//options - plugins - labels - fontColor
			if(undefined != list[i].dataset.fontcolor){
				lconfig.options.plugins.labels.fontColor=list[i].dataset.fontcolor;
			}
			//options - plugins - labels - precision
			if(undefined != list[i].dataset.precision){
				lconfig.options.plugins.labels.precision=list[i].dataset.precision;
			}
			//options - plugins - labels - position
			if(undefined != list[i].dataset.position){
				lconfig.options.plugins.labels.position=list[i].dataset.position;
			}
			//options - plugins - labels - outsidePadding
			if(undefined != list[i].dataset.outsidepadding){
				lconfig.options.plugins.labels.outsidePadding=list[i].dataset.outsidepadding;
			}
			//options - plugins - labels - textMargin
			if(undefined != list[i].dataset.textmargin){
				lconfig.options.plugins.labels.textMargin=list[i].dataset.textmargin;
			}
			//options - plugins - labels - textMargin
			if(undefined != list[i].dataset.centertext){
				lconfig.options.plugins.doughnutlabel={
					color:list[i].dataset.centertextcolor || '#000',
					labels:[{
						text: list[i].dataset.centertext,
						font:{size:list[i].dataset.centertextfontsize || 30}
					}]
				};
			}
			let lcanvas=document.createElement('canvas');
			list[i].appendChild(lcanvas);
			let lctx = lcanvas.getContext('2d');
			if(undefined != list[i].dataset.debug){
				console.log(JSON.stringify(lconfig,null,2));
			}
			wacss.chartjs[list[i].id] = new Chart(lctx, lconfig);
			//onclick
			if(undefined != list[i].dataset.onclick){
				lcanvas.parentobj=list[i];
				lcanvas.chartobj=wacss.chartjs[list[i].id];
				lcanvas.onclick_func=list[i].dataset.onclick;
				lcanvas.clicked=0;
				lcanvas.onclick = function(evt){
					if(this.clicked==0){
						this.clicked=1;
				        //set clicked back to 0 in 250 ms (this prevents duplicate click events)
				        this.timeout=setTimeout(function(obj){obj.clicked=0;}, 250,this);
				        //get the active point
						let activePoints = this.chartobj.getElementsAtEventForMode(evt, 'point', this.chartobj.options);
				        if(activePoints.length > 0){
				        	let params={};
							params.parent_id=this.parentobj.id;
							params.type=this.parentobj.getAttribute('data-type');
							params.onclick=this.parentobj.getAttribute('data-onclick');
					        let firstPoint = activePoints[0];			        					        
					        params.xvalue=this.chartobj.data.labels[firstPoint._index];
					        params.yvalue=this.chartobj.data.datasets[firstPoint._datasetIndex].data[firstPoint._index];
					        params.dataset=firstPoint._view.datasetLabel || firstPoint._view.label || this.chartobj.data.datasets[firstPoint._datasetIndex].label;
					        params.color=firstPoint._view.backgroundColor;
					        params.bcolor=firstPoint._view.borderColor;
					        params.width=firstPoint._view.width;
					        params.x=firstPoint._view.x;
					        params.y=firstPoint._view.y;
					        window[this.onclick_func](params);
					    }
					    
				    }
				};
			}
		}
	},
	initChartJs: function(initid){
		wacss.initChartJsBehavior();
		let list=document.querySelectorAll('div.chartjs,div[data-behavior="chartjs"]');
		if(undefined==list || list.length==0){return false;}
		//load Chart is it is not already loaded
		if(undefined == Chart){
			console.log('loading Chartjs, etc');
			wacss.loadScript('/wfiles/js/extras/chart.min.js');
			wacss.loadScript('/wfiles/js/extras/chartjs-plugin-datalabels.min.js');
			wacss.loadScript('/wfiles/js/extras/chartjs-plugin-doughnutlabel.min.js');
		}
		let gcolors = new Array(
	        'rgba(255,159,64,0.4)',
	        'rgba(75,192,192,0.4)',
	        'rgba(255,99,132,0.4)',
	        'rgba(54,162,235,0.4)',
	        'rgba(153,102,255,0.4)',
	        'rgba(218,165,32,0.4)',
	        'rgba(233,150,122,0.4)',
	        'rgba(189,183,107,0.4)',
	        'rgba(154,205,50,0.4)',
	        'rgba(255,228,196,0.4)',
	        'rgba(244,164,96,0.4)',
	        'rgba(176,196,222,0.4)',
	        'rgba(188,143,143,0.4)',
	        'rgba(255,228,225,0.4)',
	        'rgba(201,203,207,0.4)'
	    );
	    let gbcolors = new Array(
	        'rgb(255,159,64)',
	        'rgb(75,192,192)',
	        'rgb(255,99,132)',
	        'rgb(54,162,235)',
	        'rgb(153,102,255)',
	        'rgb(218,165,32)',
	        'rgb(233,150,122)',
	        'rgb(189,183,107)',
	        'rgb(154,205,50)',
	        'rgb(255,228,196)',
	        'rgb(244,164,96)',
	        'rgb(176,196,222)',
	        'rgb(188,143,143)',
	        'rgb(255,228,225)',
	        'rgb(201,203,202)'
	    );
	    let colors=new Array();
	    let bcolors=new Array();
		//console.log(list);
		for(let i=0;i<list.length;i++){
			if(undefined == list[i].id){
				console.log('missing id',list[i]);
				continue;
			}
			if(undefined != initid && list[i].id != initid){
				continue;
			}
			if(undefined == list[i].getAttribute('data-type')){
				console.log('missing data-type',list[i]);
				continue;
			}
			//check for data element
			//console.log('initChartJs: '+list[i].id);
			if(undefined == document.getElementById(list[i].id+'_data')){
				console.log('missing data div',list[i]);
				continue;
			}
			list[i].setAttribute('data-initialized',1);
			let type=list[i].dataset.type.toLowerCase();
			let datadiv=wacss.getObject(list[i].id+'_data');
			//colors
			let colorsdiv=datadiv.querySelector('colors');
			if(undefined != colorsdiv){
				let colorsjson=wacss.trim(colorsdiv.innerText);
				colors=JSON.parse(colorsjson);
			}
			else{
				colors=gcolors;
			}
			//bcolors
			let bcolorsdiv=datadiv.querySelector('bcolors');
			if(undefined != bcolorsdiv){
				let bcolorsjson=wacss.trim(bcolorsdiv.innerText);
				bcolors=JSON.parse(bcolorsjson);
			}
			else{
				bcolors=gbcolors;
			}
			//labels
			let labels=new Array();
			let labelsdiv=datadiv.querySelector('labels');
			if(undefined != labelsdiv){
				let labelsjson=wacss.trim(labelsdiv.innerText);
				//lconfig.data.labels=JSON.parse(labelsjson);
				labels=JSON.parse(labelsjson);
			}
			//options
			let optionsdiv=datadiv.querySelector('options');
			if(undefined != optionsdiv){
				let optionsjson=wacss.trim(optionsdiv.innerText);
				//lconfig.options=JSON.parse(optionsjson);
				options=JSON.parse(optionsjson);
			}
			else{
				options={
					responsive:true
				};
			}
			let foundchart=0;
			switch(type){
				case 'guage':
					if(undefined != wacss.chartjs[list[i].id]){
						//check for canvas
						let ck=list[i].querySelector('canvas');
						if(undefined != ck){
							//update existing chart
							let gv=list[i].dataset.value || datadiv.innerText;
							gv=parseInt(gv);
							let max=list[i].dataset.max||180;
							let gv1=parseInt(max*(gv/100));
							if(gv1 > max){gv1=max;}
							let gv2=max-gv1;
							wacss.chartjs[list[i].id].config.centerText.text=gv1;
							wacss.chartjs[list[i].id].config.data.datasets[0].data=[gv1,gv2];
	        				wacss.chartjs[list[i].id].update();
	        				foundchart=1;
		        		}
					}
					if(foundchart==0){
						let gv=list[i].dataset.value || datadiv.innerText;
						gv=parseInt(gv);
						let max=list[i].dataset.max||180;
						let gv1=parseInt(max*(gv/100));
						if(gv1 > max){gv1=max;}
						let gv2=max-gv1;
						let color=list[i].dataset.color || '#009300';
	        			
						//console.log(type);
						let gconfig = {
							type:'doughnut',
							data: {
								datasets: [{
									data: [gv1,gv2],
	                        		backgroundColor: colors,
	                        		borderWidth: 0
	                    		}]
	            			},
	            			options: {
	            				title:{display:false},
	                			circumference: Math.PI,
	                			rotation: -1 * Math.PI,
	                			responsive: true,
	                			plugins:{
	                				labels: {
										render: list[i].dataset.render || 'label', //label,percentage,value
										fontColor: list[i].dataset.fontcolor || function (data) {
											let rgb = {};
											rgb=wacss.hexToRgb(data.dataset.backgroundColor[data.index]);
											if(undefined == rgb.r){
												return 'white';
											}
											let threshold = 140;
											let luminance = 0.299 * rgb.r + 0.587 * rgb.g + 0.114 * rgb.b;
											return luminance > threshold ? 'black' : 'white';
											},
										precision: list[i].dataset.precision || 2,
										position: list[i].dataset.position || 'outside',
										outsidePadding: list[i].dataset.outsidepadding || 4,
										textMargin: list[i].dataset.textmargin || 4
									}
	                			},
	                			legend:{display:false},
	                    		animation: {animateScale:false,animateRotate:true}
	            			},
	            			centerText:{
	            				display:true,
	            				text: gv1
	            			}
	        			};
	        			if(undefined != list[i].dataset.labels && 
	        				(list[i].dataset.labels=='0' || list[i].dataset.labels.toLowerCase()=='false')
	        				){
	        				gconfig.options.plugins.datalabels.display=false;
	        			}
	        			if(undefined != list[i].dataset.title){
	        				gconfig.options.title={display:true,padding:0,position:'bottom',text:list[i].dataset.title};
	        			}
	        			if(undefined != list[i].dataset.titlePosition){
	        				gconfig.options.title.position=list[i].dataset.titlePosition;
	        			}
	        			let gcanvas=document.createElement('canvas');
	        			list[i].appendChild(gcanvas);
	        			let gctx = gcanvas.getContext('2d');
						wacss.chartjs[list[i].id]  = new Chart(gctx, gconfig);
						Chart.pluginService.register({
							/**
							* @exclude  - this function is for internal use only and thus excluded from the manual
							*/
						    afterDraw: function(chart) {
						    	if(undefined != chart.config.centerText){
						        	if ( undefined != chart.config.centerText.display){
						        		//console.log(chart);
						        		wacss.chartjsDrawTotals(chart);	
						        	} 
						        }
						    }
						});
						

						/* check for data-onclick */
						if(undefined != list[i].getAttribute('data-onclick')){
							gcanvas.parentobj=list[i];
							gcanvas.chartobj=wacss.chartjs[list[i].id];
							gcanvas.onclick_func=list[i].getAttribute('data-onclick');
							gcanvas.clicked=0;
							gcanvas.onclick = function(evt){
								if(this.clicked==0){
									this.clicked=1;
							        //set clicked back to 0 in 250 ms (this prevents duplicate click events)
							        this.timeout=setTimeout(function(obj){obj.clicked=0;}, 250,this);
									let activePoints = this.chartobj.getElementsAtEventForMode(evt, 'point', this.chartobj.options);
							       	if(activePoints.length > 0){
								        let firstPoint = activePoints[0];
								        let params={};
								        params.parent=this.parentobj;
								        params.chart=this.chartobj;
								        params.type=this.parentobj.getAttribute('data-type');
								        params.label = this.chartobj.data.labels[firstPoint._index] || this.chartobj.data.datasets[firstPoint._datasetIndex].label;
								        params.value = this.chartobj.data.datasets[firstPoint._datasetIndex].data[firstPoint._index];
								        window[this.onclick_func](params);
								    }
							    }
							};
						}
					}
				break;
				case 'line':
				case 'bar':
				case 'horizontalbar':
				case 'doughnut':
					//console.log('barline');
					if(undefined != wacss.chartjs[list[i].id]){
						//check for canvas
						let ck=list[i].querySelector('canvas');
						if(undefined != ck){	
							if(undefined != labels && labels.length > 0){
								wacss.chartjs[list[i].id].config.data.labels=labels;
							}
							let udatasets=datadiv.querySelectorAll('dataset');
							let datasetLabels=new Array();
		        			for(let ud=0;ud<udatasets.length;ud++){
		        				//require data-label
		        				let json=JSON.parse(udatasets[ud].innerText);  			
								let udataset={
									backgroundColor: udatasets[ud].getAttribute('data-backgroundColor') || colors[ud],
		                            type:udatasets[ud].getAttribute('data-type') || list[i].getAttribute('data-type'),
									data: json,
									fill:false,
									pointBackgroundColor:[],
									pointBorderColor: []
								};
								if(undefined != udatasets[ud].getAttribute('data-showLine') && 
									(udatasets[ud].getAttribute('data-showLine')=='0' || udatasets[ud].getAttribute('data-showLine').toLowerCase()=='false')
									){
									udataset.showLine=false;
								}
								if(undefined != udatasets[ud].getAttribute('data-yaxis')){
									udataset.yAxisID=udatasets[ud].getAttribute('data-yaxis');
								}
								if(undefined != udatasets[ud].getAttribute('data-label')){
									udataset.label=udatasets[ud].getAttribute('data-label');
									let dlabel=udatasets[ud].getAttribute('data-label');
		        					datasetLabels.push(dlabel); 
								}
								//check for fillColor in dataset itself
								for(let ds=0;ds<udataset.data.length;ds++){
									if(undefined != udataset.data[ds].pointBackgroundColor){
										udataset.pointBackgroundColor[ds]=udataset.data[ds].pointBackgroundColor;
									}
									if(undefined != udataset.data[ds].pointBorderColor){
										udataset.pointBorderColor[ds]=udataset.data[ds].pointBorderColor;
									}
									if(undefined != udataset.data[ds].backgroundColor){
										udataset.backgroundColor[ds]=udataset.data[ds].backgroundColor;
									}
									if(undefined != udataset.data[ds].borderColor){
										udataset.borderColor[ds]=udataset.data[ds].borderColor;
									}
								}
								wacss.chartjs[list[i].id].config.data.datasets[ud] = udataset;
		        			}
		        			wacss.chartjs[list[i].id].config.options=options;
		        			if((undefined == labels || labels.length==0) && undefined != datasetLabels && datasetLabels.length > 0){
								wacss.chartjs[list[i].id].config.data.labels=datasetLabels;
							}
							if(undefined != list[i].getAttribute('data-stacked') && 
								(list[i].getAttribute('data-stacked')==1 || list[i].getAttribute('data-stacked').toLowerCase()=='true')
								){
								if(undefined != wacss.chartjs[list[i].id].config.options.scales.yAxes[0]){
									wacss.chartjs[list[i].id].config.options.scales.yAxes[0].stacked=true;
								}
								if(undefined != wacss.chartjs[list[i].id].config.options.scales.xAxes[0]){
									wacss.chartjs[list[i].id].config.options.scales.xAxes[0].stacked=true;
								}
							}
							if(undefined != list[i].getAttribute('data-beginatzero') && 
								(list[i].getAttribute('data-beginatzero')==1 || list[i].getAttribute('data-beginatzero').toLowerCase()=='true')
								){
								if(undefined == lconfig.options.scales){
									lconfig.options.scales.yAxes[0].ticks.beginAtZero=true;	
								}
								if(undefined == lconfig.options.scales.yAxes[0]){
									lconfig.options.scales.yAxes[0].ticks.beginAtZero=true;	
								}
								if(undefined == lconfig.options.scales.yAxes[0].ticks){
									lconfig.options.scales.yAxes[0].ticks.beginAtZero=true;	
								}
								if(undefined == lconfig.options.scales.yAxes[0].ticks.beginAtZero){
									lconfig.options.scales.yAxes[0].ticks.beginAtZero=true;	
								}
							}
		        			wacss.chartjs[list[i].id].update();
		        			foundchart=1;
		        		}
					}
					if(foundchart==0){
						let lconfig = {
							type:list[i].getAttribute('data-type'),
							data:{
								labels:labels,
								datasets:[]
							},
							options:options
						};
						//stacked?
						if(undefined != list[i].getAttribute('data-stacked') && 
							(list[i].getAttribute('data-stacked')==1 || list[i].getAttribute('data-stacked').toLowerCase()=='true')
							){
							if(undefined != lconfig.options.scales){
								lconfig.options.scales.yAxes[0].stacked=true;
								lconfig.options.scales.xAxes[0].stacked=true;	
							}
							if(undefined != lconfig.options.scales.yAxes[0]){
								lconfig.options.scales.yAxes[0].stacked=true;	
							}
							if(undefined != lconfig.options.scales.xAxes[0]){
								lconfig.options.scales.xAxes[0].stacked=true;
							}

						}
						//console.log('list: '+i);
						//console.log(list[i]);
						//beginatzero
						if(undefined != list[i].getAttribute('data-beginatzero') && 
							(list[i].getAttribute('data-beginatzero')==1 || list[i].getAttribute('data-beginatzero').toLowerCase()=='true')
							){
							if(undefined != lconfig.options.scales){
								lconfig.options.scales.yAxes[0].ticks.beginAtZero=true;	
							}
							if(undefined != lconfig.options.scales.yAxes[0]){
								lconfig.options.scales.yAxes[0].ticks.beginAtZero=true;	
							}
							if(undefined != lconfig.options.scales.yAxes[0].ticks){
								lconfig.options.scales.yAxes[0].ticks.beginAtZero=true;	
							}
							if(undefined != lconfig.options.scales.yAxes[0].ticks.beginAtZero){
								lconfig.options.scales.yAxes[0].ticks.beginAtZero=true;	
							}
						}
	        			//look for datasets;
	        			//console.log(colors);
	        			let datasets=datadiv.querySelectorAll('dataset');
	        			let datasetLabels=new Array();
	        			for(let d=0;d<datasets.length;d++){
	        				//require data-label
	        				let json=JSON.parse(datasets[d].innerText);   
	        				let fill=datasets[d].dataset.fill || list[i].dataset.fill || '';
							if(fill.length){
								if(fill.indexOf('true') != -1){fill=true;}
								else if(fill == '1'){fill=true;}
								else{fill=false;}
							}   
							else{fill=false;}				
							let dataset={
								backgroundColor: datasets[d].dataset.backgroundcolor || datasets[d].dataset.backgroundColor || colors[d],
								borderColor: datasets[d].dataset.bordercolor || datasets[d].dataset.borderColor || bcolors[d],
	                            borderWidth:1,
	                            borderRadius:3,
	                            type:datasets[d].dataset.type || list[i].dataset.type || 'line',
								data: json,
								fill:fill,
								pointBackgroundColor:datasets[d].dataset.pointbackgroundcolor || datasets[d].dataset.pointBackgroundColor || [],
								pointBorderColor: datasets[d].dataset.pointbordercolor || datasets[d].dataset.pointBorderColor || []
							};
							/* --  borderDash  -- */
							if(undefined != datasets[d].dataset.borderdash){
								dataset.borderDash=datasets[d].dataset.borderdash;
							}
							else if(undefined != datasets[d].dataset.borderDash){
								dataset.borderDash=datasets[d].dataset.borderDash;
							}
							/* --  yAxisID  -- */
							if(undefined != datasets[d].dataset.yaxis){
								dataset.yAxisID=datasets[d].dataset.yaxis;
							}
							else if(undefined != datasets[d].dataset.yAxisID){
								dataset.yAxisID=datasets[d].dataset.yAxisID;
							}
							/* --  showLine  -- */
							if(undefined != datasets[d].dataset.showline && 
								(datasets[d].dataset.showline =='0' || datasets[d].dataset.showline.toLowerCase()=='false')
								){
								dataset.showLine=false;
							}
							else if(undefined != datasets[d].dataset.showLine && 
								(datasets[d].dataset.showLine =='0' || datasets[d].dataset.showLine.toLowerCase()=='false')
								){
								dataset.showLine=false;
							}
							/* --  pointHoverBackgroundColor  -- */
							if(undefined != datasets[d].dataset.pointhoverbackgroundcolor){
								dataset.pointHoverBackgroundColor=datasets[d].dataset.pointhoverbackgroundcolor;
							}
							else if(undefined != datasets[d].dataset.pointHoverBackgroundColor){
								dataset.pointHoverBackgroundColor=datasets[d].dataset.pointHoverBackgroundColor;
							}
							/* --  pointHoverBorderColor  -- */
							if(undefined != datasets[d].dataset.pointhoverbordercolor){
								dataset.pointHoverBorderColor=datasets[d].dataset.pointhoverbordercolor;
							}
							else if(undefined != datasets[d].dataset.pointHoverBorderColor){
								dataset.pointHoverBorderColor=datasets[d].dataset.pointHoverBorderColor;
							}
							/* --  label  -- */
							if(undefined != datasets[d].dataset.label){
								dataset.label=datasets[d].dataset.label;
								let dlabel=datasets[d].dataset.label;
	        					datasetLabels.push(dlabel); 
							}
							//check for fillColor in dataset itself
							for(let ds=0;ds<dataset.data.length;ds++){
								if(undefined != dataset.data[ds].pointbackgroundcolor){
									dataset.pointBackgroundColor[ds]=dataset.data[ds].pointbackgroundcolor;
								}
								if(undefined != dataset.data[ds].pointbordercolor){
									dataset.pointBorderBolor[ds]=dataset.data[ds].pointbordercolor;
								}
								if(undefined != dataset.data[ds].backgroundcolor){
									dataset.backgroundColor[ds]=dataset.data[ds].backgroundcolor;
								}
								if(undefined != dataset.data[ds].bordercolor){
									dataset.borderColor[ds]=dataset.data[ds].bordercolor;
								}
							}
							lconfig.data.datasets.push(dataset);
	        			}
	        			if((undefined == labels || labels.length==0) && undefined != datasetLabels && datasetLabels.length > 0){
	        				lconfig.data.labels=datasetLabels;	
	        			}
	        			//
	        			let lcanvas=document.createElement('canvas');
	        			list[i].appendChild(lcanvas);
	        			let lctx = lcanvas.getContext('2d');
						wacss.chartjs[list[i].id]  = new Chart(lctx, lconfig);
						/* check for data-onclick */
						if(undefined != list[i].dataset.onclick){
							lcanvas.parentobj=list[i];
							lcanvas.chartobj=wacss.chartjs[list[i].id];
							lcanvas.onclick_func=list[i].dataset.onclick;
							lcanvas.clicked=0;
							lcanvas.onclick = function(evt){
								if(this.clicked==0){
									this.clicked=1;
							        //set clicked back to 0 in 250 ms (this prevents duplicate click events)
							        this.timeout=setTimeout(function(obj){obj.clicked=0;}, 250,this);
							        //get exact element you clicked on
							        let activePoint = this.chartobj.getElementAtEvent(evt);
								    if (activePoint.length > 0) {
								    	let firstPoint = activePoint[0];
								       	let clickedDatasetIndex = firstPoint._datasetIndex;
								       	let clickedElementIndex = firstPoint._index;
								       	let clickedDatasetPoint = this.chartobj.data.datasets[clickedDatasetIndex];
								       	let label = clickedDatasetPoint.label;
								       	let value = clickedDatasetPoint.data[clickedElementIndex]["y"];  
								       	let params={};
								       	params.parent=this.parentobj;
								        params.chart=this.chartobj;
								       	params.type=this.parentobj.dataset.type;
								       	params.label = this.chartobj.data.labels[firstPoint._index] || clickedDatasetPoint.label;
								       	params.value = clickedDatasetPoint.data[firstPoint._index] || clickedDatasetPoint.data[clickedElementIndex]["y"];
								       	params.axis = clickedDatasetPoint.yAxisID || 'default';
								       	window[this.onclick_func](params);   
								    }
								    else{
										let activePoints = this.chartobj.getElementsAtEventForMode(evt, 'point', this.chartobj.options);
										if(activePoints.length > 0){
									        let firstPoint = activePoints[0];
									        let params={};
									        params.parent=this.parentobj;
								        	params.chart=this.chartobj;
									        params.type=this.parentobj.dataset.type;
									        params.label = this.chartobj.data.labels[firstPoint._index] || this.chartobj.data.datasets[firstPoint._datasetIndex].label;
									        params.value = this.chartobj.data.datasets[firstPoint._datasetIndex].data[firstPoint._index];
									        params.axis = this.chartobj.data.datasets[firstPoint._datasetIndex].yAxisID || 'default';
									        window[this.onclick_func](params);
									    }
									}
							    }
							};
						}
					}
				break;
				case 'pie':
					if(undefined != wacss.chartjs[list[i].id]){
						//check for canvas
						let ck=list[i].querySelector('canvas');
						if(undefined != ck){
							//update existing pie chart
							let pielabels=[];
		        			let data=[];
		        			let datasets=datadiv.querySelectorAll('dataset');
		        			let json=JSON.parse(datasets[0].innerText); 
		        			for(let tval in json){
		        				pielabels.push(tval);
		        				data.push(json[tval]);
		        			}
		        			wacss.chartjs[list[i].id].config.data.datasets[0].data=data;
		        			if(undefined != labels && labels.length > 0){
								wacss.chartjs[list[i].id].config.data.labels=labels;
							}
		        			else{
		        				wacss.chartjs[list[i].id].config.data.labels=pielabels;
		        			}
		        			//console.log(wacss.chartjs[list[i].id].config);
	        				wacss.chartjs[list[i].id].update();
	        				foundchart=1;
		        		}
					}
					if(foundchart==0){
						//look for datasets;
	        			let pielabels=[];
	        			let data=[];
	        			let datasets=datadiv.querySelectorAll('dataset');
	        			let json=JSON.parse(datasets[0].innerText); 
	        			for(let tval in json){
	        				pielabels.push(tval);
	        				data.push(json[tval]);
	        			}
	        			let pconfig={
	        				type: 'pie',
	        				data: {
	        					labels: labels,
	        					datasets:[{
	        						backgroundColor: colors,
	        						fill: true,
	        						data: data
	        					}]
	        				},
	        				options: {
	        					responsive: true,
	                    		events: false,
	                    		animation: {animateScale:false,animateRotate:true},
	        					title:{
	        						display: list[i].dataset.label?true:false,
	        						text: list[i].dataset.label || ''
	        					},
	        					rotation: -0.7 * Math.PI,
	        					plugins: {
	        						labels: {
										render: list[i].dataset.render || 'label', //label,percentage,value
										fontColor: list[i].dataset.fontcolor || function (data) {
											let rgb = {};
											rgb=wacss.hexToRgb(data.dataset.backgroundColor[data.index]);
											if(undefined == rgb.r){
												return 'white';
											}
											let threshold = 140;
											let luminance = 0.299 * rgb.r + 0.587 * rgb.g + 0.114 * rgb.b;
											return luminance > threshold ? 'black' : 'white';
											},
										precision: list[i].dataset.precision || 0,
										position: list[i].dataset.position || 'outside',
										outsidePadding: list[i].dataset.outsidepadding || 4,
										textMargin: list[i].dataset.textmargin || 4,
										showActualPercentages: true
									}
							    }
	        				}
	        			};
	        			//console.log(pconfig.options.plugins);
	        			if(undefined != labels && labels.length > 0){
							pconfig.data.labels=labels;
						}
	        			let pcanvas=document.createElement('canvas');
	        			list[i].appendChild(pcanvas);
	        			let pctx = pcanvas.getContext('2d');
						wacss.chartjs[list[i].id]  = new Chart(pctx, pconfig);
						//console.log(pconfig);
						/* check for data-onclick */
						if(undefined != list[i].dataset.onclick){
							pcanvas.parentobj=list[i];
							pcanvas.chartobj=wacss.chartjs[list[i].id];
							pcanvas.onclick_func=list[i].dataset.onclick;
							pcanvas.clicked=0;
							pcanvas.onclick = function(evt){
								if(this.clicked==0){
									this.clicked=1;
							        //set clicked back to 0 in 250 ms (this prevents duplicate click events)
							        this.timeout=setTimeout(function(obj){obj.clicked=0;}, 250,this);
									let activePoints = this.chartobj.getElementsAtEventForMode(evt, 'point', this.chartobj.options);
							        if(activePoints.length > 0){
								        let firstPoint = activePoints[0];
								        let params={};
								        params.parent=this.parentobj;
								        params.chart=this.chartobj;
								        params.type=this.parentobj.dataset.type;
								        params.label = this.chartobj.data.labels[firstPoint._index] || this.chartobj.data.datasets[firstPoint._datasetIndex].label;
								        params.value = this.chartobj.data.datasets[firstPoint._datasetIndex].data[firstPoint._index];
								        window[this.onclick_func](params);
								    }
							    }
							};
						}
					}
				break;
			}
		}
		return true;
	},
	initDatePicker: function(){
		let els=document.querySelectorAll('input[data-behavior="flatpickr"],input[data-behavior="date"],input[data-behavior="datepicker"]');
		if(!els.length){return false;}
		let nlang=navigator.language || 'en-US';
		nlang=nlang.split('-')[0];
		for(let i=0;i<els.length;i++){
			if(undefined != els[i].dataset.initflatpickr){continue;}
			if(undefined != els[i].dataset.initdatepicker){continue;}
			els[i].dataset.initdatepicker=1;
			let lang=els[i].dataset.lang || nlang;
			let config={
				errorHandler: function (err) {
					alert(err);
		          	return false;
		        	}
			};
			config.prevArrow = "<span class='icon-arrow-left'></span>";
			config.nextArrow = "<span class='icon-arrow-right'></span>";
			for(k in els[i].dataset){
				let v=els[i].dataset[k];
				switch(k.toLowerCase()){
					case 'altformat':k='altFormat';break;
					case 'altinput':k='altInput';break;
					case 'altinputclass':k='altInputClass';break;
					case 'allowinput':k='allowInput';break;
					case 'allowinvalidpreload':k='allowInvalidPreload';break;
					case 'appendto':
						k='appendTo';
						v=document.querySelector(v)||getObject(v);
					break;
					case 'ariadateformat':k='ariaDateFormat';break;
					case 'conjunction':k='conjunction';break;
					case 'clickopens':k='clickOpens';break;
					case 'dateformat':k='dateFormat';break;
					case 'defaultdate':
						if(undefined != els[i].value && els[i].value.length){k='';}
						else{k='defaultDate';}
					break;
					case 'defaulthour':k='defaultHour';break;
					case 'defaultminute':k='defaultMinute';break;
					case 'disablemobile':k='disableMobile';break;
					case 'enabletime':k='enableTime';break;
					case 'enableseconds':k='enableSeconds';break;
					case 'firstdayofweek':
					case 'firstday':
						if(undefined == config.locale){
							config.locale={};
						}
						config.locale.firstDayOfWeek=v;
						continue;
						//k='firstDayOfWeek';
					break;
					case 'hourincrement':k='hourIncrement';break;
					case 'maxdate':
						k='maxDate';
						if(!isNaN(v)){
							if(v < 0){
								v=Math.abs(v);
								v = new Date(new Date().setDate(new Date().getDate() - v)).toLocaleDateString('en-CA');	
							}
							else if(v > 0){
								v=Math.abs(v);
								v = new Date(new Date().setDate(new Date().getDate() + v)).toLocaleDateString('en-CA');	
							}	
						}
						else if(v.toLowerCase()=='today'){
							v=new Date().toLocaleDateString('en-CA');
						}
						els[i].dataset.maxdate_value=v;
					break;
					case 'mindate':
						k='minDate';
						if(!isNaN(v)){
							if(v < 0){
								v=Math.abs(v);
								v = new Date(new Date().setDate(new Date().getDate() - v)).toLocaleDateString('en-CA');	
							}
							else if(v > 0){
								v=Math.abs(v);
								v = new Date(new Date().setDate(new Date().getDate() + v)).toLocaleDateString('en-CA');	
							}	
						}
						else if(v.toLowerCase()=='today'){
							v=new Date().toLocaleDateString('en-CA');
						}
						els[i].dataset.mindate_value=v;
					break;
					case 'minuteincrement':k='minuteIncrement';break;
					case 'nextarrow':k='nextArrow';break;
					case 'nocalendar':k='noCalendar';break;
					case 'prevarrow':k='prevArrow';break;
					case 'shorthandcurrentmonth':k='shorthandCurrentMonth';break;
					case 'showmonths':k='showMonths';break;
					case 'weeknumbers':
					case 'showweeknumber':
						k='weekNumbers';
					break;
					case 'monthselectortype':k='monthSelectorType';break;
					default:continue;break;
				}
				switch(v.toLowerCase()){
					case 'true':
					case '1':
						v=true;
					break;
					case 'false':
					case '0':
						v=false;
					break;
				}
				if(k != ''){config[k]=v;}
			}
			switch(lang.toLowerCase()){
				case 'es':
					//spanish
					config.locale={};
					config.locale.months={};
					config.locale.months.longhand = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
					config.locale.months.shorthand = ['enero', 'feb.', 'marzo', 'abr.', 'mayo', 'jun.', 'jul.', 'agosto', 'sept.', 'oct.', 'nov.', 'dic.'];
					config.locale.weekdays={};
					config.locale.weekdays.longhand = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
					config.locale.weekdays.shorthand = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
					config.locale.firstDayOfWeek = 1;
					config.locale.rangeSeparator=" a ";
					config.locale.time_24hr=true;
					config.locale.ordinal=function(){return "º";};
				break;
				case 'de':
					//german
					config.locale={};
					config.locale.months={};
					config.locale.months.longhand = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
					config.locale.months.shorthand = ['Jan.', 'Feb.', 'Marz', 'Apr.', 'Mai', 'Juni.', 'Juli', 'Aug.', 'Sept.', 'Okt.', 'Nov.', 'Dez.'];
					config.locale.weekdays={};
					config.locale.weekdays.longhand = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
					config.locale.weekdays.shorthand = ['Son', 'Mon', 'Die', 'Mit', 'Don', 'Fre', 'Sam'];
					//in Germany the first day of the week is Monday
					config.locale.firstDayOfWeek = 1;
					config.locale.weekAbbreviation = "KW";
	      			config.locale.rangeSeparator = " bis ";
	      			config.locale.scrollTitle = "Zum Ändern scrollen";
	      			config.locale.toggleTitle = "Zum Umschalten klicken";
	      			config.locale.time_24hr = true;
				break;
				case 'fr':
					//french
					config.locale={};
					config.locale.months={};
					config.locale.months.longhand = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
					config.locale.months.shorthand = ['janv.', 'févr.', 'mars', 'avril.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
					config.locale.weekdays={};
					config.locale.weekdays.longhand = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
					config.locale.weekdays.shorthand = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
					config.locale.ordinal = function (nth) {
				          if (nth > 1){return "";}
				          return "er";
				     };
				     rangeSeparator = " au ";
				     weekAbbreviation = "Sem";
				     scrollTitle = "Défiler pour augmenter la valeur";
				     toggleTitle = "Cliquer pour basculer";
				     time_24hr = true;
				break;
				case 'it':
					//italian
					config.locale={};
					config.locale.months={};
					config.locale.months.longhand = ['gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno', 'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre'];
					config.locale.months.shorthand = ['genn.', 'febbr.', 'mar.', 'abr.', 'magg.', 'giugno', 'luglio', 'ag.', 'sett.', 'ott.', 'nov.', 'dic.'];
					config.locale.weekdays={};
					config.locale.weekdays.longhand = ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];
					config.locale.weekdays.shorthand = ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'];
					//in Italy the first day of the week is Monday
					config.locale.firstDayOfWeek = 1;
					config.locale.ordinal = function () { return "°"; };
	      			config.locale.rangeSeparator = " al ";
	      			config.locale.weekAbbreviation = "Se";
	      			config.locale.scrollTitle = "Scrolla per aumentare";
	      			config.locale.toggleTitle = "Clicca per cambiare";
	      			config.locale.time_24hr = true;
				break;
				case 'ja':
					//japanese
					config.locale={};
					config.locale.months={};
					config.locale.months.longhand = ["1月","2月","3月","4月","5月","6月","7月","8月","9月","10月","11月","12月"];
					config.locale.months.shorthand = ["1月","2月","3月","4月","5月","6月","7月","8月","9月","10月","11月","12月"];
					config.locale.weekdays={};
					config.locale.weekdays.longhand = ["日曜日","月曜日","火曜日","水曜日","木曜日","金曜日","土曜日"];
					config.locale.weekdays.shorthand = ["日", "月", "火", "水", "木", "金", "土"];
					config.locale.time_24hr=true;
					config.locale.rangeSeparator = " から ";
				     config.locale.monthAriaLabel = "月";
				     config.locale.amPM = ["午前", "午後"];
				     config.locale.yearAriaLabel = "年";
				     config.locale.hourAriaLabel = "時間";
				     config.locale.minuteAriaLabel = "分";
				break;
				case 'ko':
					//korean
					config.locale={};
					config.locale.months={};
					config.locale.months.longhand = ["1월","2월","3월","4월","5월","6월","7월","8월","9월","10월","11월","12월"];
					config.locale.months.shorthand = ["1월","2월","3월","4월","5월","6월","7월","8월","9월","10월","11월","12월"];
					config.locale.weekdays={};
					config.locale.weekdays.longhand = ["일요일","월요일","화요일","수요일","목요일","금요일","토요일"];
					config.locale.weekdays.shorthand = ["일", "월", "화", "수", "목", "금", "토"];
					config.locale.rangeSeparator = " ~ ";
				     config.locale.ordinal = function(){return "일";};
				break;
				case 'pt':
					//portuguese
					config.locale={};
					config.locale.months={};
					config.locale.months.longhand = ["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"];
					config.locale.months.shorthand = ["Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"];
					config.locale.weekdays = {};
					config.locale.weekdays.longhand = [ "Domingo","Segunda-feira","Terça-feira","Quarta-feira","Quinta-feira","Sexta-feira","Sábado"];
					config.locale.weekdays.shorthand = ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"];
					config.locale.rangeSeparator = " até ";
				     config.locale.time_24hr = true;
				break;
				case 'zh':
					//chinese
					config.locale={};
					config.locale.months = {};
					config.locale.months.longhand = ["一月","二月","三月","四月","五月","六月","七月","八月","九月","十月","十一月","十二月"];
					config.locale.months.shorthand = ["一月","二月","三月","四月","五月","六月","七月","八月","九月","十月","十一月","十二月"];
					config.locale.weekdays={};
					config.locale.weekdays.longhand = ["星期日","星期一","星期二","星期三","星期四","星期五","星期六"];
					config.locale.weekdays.shorthand = ["周日", "周一", "周二", "周三", "周四", "周五", "周六"];
					config.locale.rangeSeparator = " 至 ";
	      			config.locale.weekAbbreviation = "周";
	      			config.locale.scrollTitle = "滚动切换";
	      			config.locale.toggleTitle = "点击切换 12/24 小时时制";
				break;
			}
			//console.log(config);
			flatpickr(els[i],config);
		}
		return false;
	},
	/**
	* @name wacss.initHovers
	* @describe initializes elements with data-hover="string..." - similiar to tooltips
	* @describe if string starts with id: followed by the id of another element, the tip will be the contents of that element
	* @describe if string starts with js: it will execute the following javascript on hover
	* @describe tooltip position is set by data-position. Values are above,below,right, left
	* @describe example1:  <div data-hover="this is a test">...</div>
	* @describe example2:  <div data-hover="id:myhiddendiv" data-position="right">...</div>
	* @return false
	* @usage wacss.initHovers();
	*/
	initHovers: function(){
		let hoverEls=document.querySelectorAll('[data-hover]');
		if(hoverEls.length > 0){
			if(wacss.hoverDiv == ''){
				wacss.hoverDiv=document.createElement('div');
				let d=document.createElement('div');
				d.className='hover_content';
				d.innerHTML='wacss hover default text';
				wacss.hoverDiv.appendChild(d);
				wacss.hoverDiv.className='wacss_hover';
				wacss.hoverDiv.dataset.position='above';
				//wacss.hoverDiv.style.display='none';
				document.body.appendChild(wacss.hoverDiv);
				wacss.hoverDiv.addEventListener('mouseout',function(){
					if(undefined != this.hoverParent && this.hoverParent.matches(':hover')){
						return false;
					}
					if(this.matches(':hover')){return false;}
					this.querySelector('.hover_content').innerHTML='';
					this.style.display='none';
				},false);
			}
		}
		for(let i=0;i<hoverEls.length;i++){
			if(undefined != hoverEls[i].dataset.initialized){continue;}
			hoverEls[i].dataset.initialized=1;
			hoverEls[i].addEventListener('mouseover',function(){
				//populate wacss.hoverDiv with 
				let txt='';
				wacss.hoverDiv.style.display='initial';
				if(hoverEls[i].dataset.hover.indexOf('id:')===0){
					//console.log("hover id");
					//get content from a different id
					let txtid=trim(str_replace('id:','',hoverEls[i].dataset.hover));
					let txtel=document.querySelector('#'+txtid);
					if(undefined != txtel){
						txt=txtel.innerHTML;		
					}
				}
				else if(hoverEls[i].dataset.hover.indexOf('js:')===0){
					//call a function
					//console.log("hover js");
					let f=trim(str_replace('js:','',hoverEls[i].dataset.hover));
					let jsfunc=new Function(f);
					txt=jsfunc();
				}
				else{
					//console.log("hover");
					txt=hoverEls[i].dataset.hover;
				}
				wacss.hoverDiv.style.width='initial';
				wacss.hoverDiv.style.height='initial';
				wacss.hoverDiv.querySelector('.hover_content').innerHTML=txt;
				wacss.hoverDiv.hoverParent=this;
				let drect=this.getBoundingClientRect();
				let hrect=wacss.hoverDiv.getBoundingClientRect();
				//console.log(drect);
				//console.log(hrect);
				
				let vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
				let vh = Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0);
				switch(this.dataset.position.toLowerCase()){
					case 'above':
						wacss.hoverDiv.dataset.position='above';
						wacss.hoverDiv.style.top=parseInt(drect.top-hrect.height-2)+'px';
						wacss.hoverDiv.style.left=parseInt(drect.left)+'px';
						if(!wacss.inViewport(wacss.hoverDiv)){
							//console.log('not in viewport')
							wacss.hoverDiv.dataset.position='below';
							wacss.hoverDiv.style.top=parseInt(drect.top+drect.height+2)+'px';
							wacss.hoverDiv.style.left=parseInt(drect.left)+'px';
						}
					break;
					case 'below':
					default:
						wacss.hoverDiv.dataset.position='below';
						wacss.hoverDiv.style.top=parseInt(drect.top+drect.height+2)+'px';
						wacss.hoverDiv.style.left=parseInt(drect.left)+'px';
						if(!wacss.inViewport(wacss.hoverDiv)){
							//console.log('not in viewport')
							wacss.hoverDiv.dataset.position='above';
							wacss.hoverDiv.style.top=parseInt(drect.top-hrect.height-2)+'px';
							wacss.hoverDiv.style.left=parseInt(drect.left)+'px';
						}
					break;
					case 'right':
						wacss.hoverDiv.dataset.position='right';
						wacss.hoverDiv.style.top=parseInt(drect.top+2)+'px';
						wacss.hoverDiv.style.left=parseInt(drect.left+drect.width+2)+'px';
						if(!wacss.inViewport(wacss.hoverDiv)){
							//console.log('not in viewport');
							wacss.hoverDiv.style.width=parseInt(vw-drect.right-5)+'px';
							wacss.hoverDiv.style.left=parseInt(drect.right)+'px';
						}
						if(!wacss.inViewport(wacss.hoverDiv)){
							wacss.hoverDiv.dataset.position='left';
							wacss.hoverDiv.style.top=parseInt(drect.top+2)+'px';
							wacss.hoverDiv.style.left=parseInt(drect.left-hrect.width-2)+'px';
							if(!wacss.inViewport(wacss.hoverDiv)){
								wacss.hoverDiv.style.width=parseInt(drect.left-5)+'px';
								wacss.hoverDiv.style.left='5px';
							}
						}
					break;
					case 'left':
						wacss.hoverDiv.dataset.position='left';
						wacss.hoverDiv.style.top=parseInt(drect.top+2)+'px';
						wacss.hoverDiv.style.left=parseInt(drect.left-hrect.width-2)+'px';
						if(!wacss.inViewport(wacss.hoverDiv)){
							wacss.hoverDiv.style.width=parseInt(drect.left-5)+'px';
							wacss.hoverDiv.style.left='5px';
						}
						if(!wacss.inViewport(wacss.hoverDiv)){
							wacss.hoverDiv.dataset.position='right';
							wacss.hoverDiv.style.top=parseInt(drect.top+2)+'px';
							wacss.hoverDiv.style.left=parseInt(drect.left+drect.width+2)+'px';
							if(!wacss.inViewport(wacss.hoverDiv)){
								//console.log('not in viewport');
								wacss.hoverDiv.style.width=parseInt(vw-drect.right-5)+'px';
								wacss.hoverDiv.style.left=parseInt(drect.right)+'px';
							}
						}
					break;

				}
				
				//console.log(wacss.hoverDiv.innerHTML);
				
			},false);
			hoverEls[i].addEventListener('mouseout',function(){
				//remove wacss.hoverDiv content
				if(wacss.hoverDiv.matches(':hover') || wacss.hoverDiv.querySelector('.hover_content:hover')){
					return false;
				}
				wacss.hoverDiv.querySelector('.hover_content').innerHTML='';
				wacss.hoverDiv.style.display='none';
			},false);
		}
	},
	initCodeMirror: function(){
		/*convert texteara to codemirror */
		let list=document.querySelectorAll('textarea.code[data-mode]');
		if(undefined == list || list.length==0){return false;}
		//set some defaults
		let defaults={
	    	mode:'text/x-sql',
		    lineNumbers: true,
		    viewportMargin: Infinity,
		    extraKeys: {
		    	/**
				* @exclude  - this function is for internal use only and thus excluded from the manual
				*/
		    	"Ctrl-Space": "autocomplete",
		    	/**
				* @exclude  - this function is for internal use only and thus excluded from the manual
				*/
		    	"F11": function(cm) {
		        	cm.setOption("fullScreen", !cm.getOption("fullScreen"));
		        },
		        /**
				* @exclude  - this function is for internal use only and thus excluded from the manual
				*/
		        "Esc": function(cm) {
		        	if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
		        }
		    }
	  	};
	  	//set a cm object
		if(undefined==this.codemirror){
			this.codemirror={};
		}
		for(let i=0;i<list.length;i++){
			//check to see if we have already initialized this element
			if(undefined != list[i].codemirror){continue;}
			//go through dataset to get params
			let params={};
			let curr_defaults=defaults;
			for(k in list[i].dataset){
				if(k=='debug'){continue;}
				let v=list[i].dataset[k];
				//look for custom keys
				if(k.startsWith('ctrl_')){
					k=k.toUpperCase().replace('_','-').replace('CTRL','Ctrl').replace('ENTER','Enter');
					let key=k;
					curr_defaults.extraKeys[k]=function(cm){
						let p={args:[key,cm],func:v};
						wacss.callFunc(p);
					};
					continue;
				}
				else if(k.startsWith('shift_')){
					k=k.toUpperCase().replace('_','-').replace('SHIFT','Shift').replace('ENTER','Enter');
					let key=k;
					curr_defaults.extraKeys[k]=function(cm){
						let p={args:[key,cm],func:v};
						wacss.callFunc(p);
					};
					continue;
				}
				else if(k.startsWith('f_') && k.length < 4){
					k=k.toUpperCase().replace('_','');
					let key=k;
					curr_defaults.extraKeys[k]=function(cm){
						let p={args:[key,cm],func:v};
						wacss.callFunc(p);
					};
					continue;
				}
				if (typeof v === 'string' || v instanceof String){
					switch(v){
						case 'true':
		  					v=true;
		  				break;
		  				case 'false':
		  					v=false;
		  				break;
					}
				}
				params[k]=v;
			}
			//fix modes
			switch(params.mode.toLowerCase()){
				case 'css':
				case 'text/css':
					params.mode='text/css';
				break;
				case 'html':
				case 'text/html':
					params.mode='text/html';
					curr_defaults.htmlMode=true;
				break;
				case 'ini':
				case 'text/x-ini':
					params.mode='text/x-ini';
				break;
				case 'javascript':
				case 'text/javascript':
					params.mode='text/javascript';
					curr_defaults.continueComments='Enter';
					curr_defaults.extraKeys["Ctrl-Q"]='toggleComment';
				break;
				case 'json':
				case 'application/x-json':
					params.mode='application/x-json';
					curr_defaults.autoCloseBrackets=true;
					curr_defaults.matchBrackets=true;
					curr_defaults.lineWrapping=true;
				break;
				case 'lua':
				case 'text/x-lua':
					params.mode='text/x-lua';
				break;
				case 'perl':
				case 'text/x-perl':
					params.mode='text/x-perl';
				break;
				case 'php':
				case 'application/x-httpd-php':
					params.mode='application/x-httpd-php';
				break;
				case 'python':
					params.mode={name:'python',version:3,singleLineStringErrors:false};
				break;
				case 'sql':
				case 'text/x-sql':
					params.mode='text/x-sql';
				break;
				case 'vbscript':
				case 'text/vbscript':
					params.mode='text/vbscript';
				break;
				case 'xml':
				case 'application/xml':
					params.mode='application/xml';
				break;
			}
			for(k in curr_defaults){
	  			if(undefined == params[k]){
	  				params[k]=curr_defaults[k];
	  			}
	  		}
	  		if(undefined != list[i].dataset.debug){
	  			console.log(list[i]);
	  			console.log(params);
	  		}
			let cm = CodeMirror.fromTextArea(list[i], params);
			//save the codemirror object to the textarea so we can find it easier
			list[i].codemirror=cm;
			//save changes to textarea
	  		cm.on('change', function(cm){cm.save();});
	  	}
	},
	inViewport: function(elem) {
		elem=wacss.getObject(elem);
	    let bounding = elem.getBoundingClientRect();
	    return (
	        bounding.top >= 0 &&
	        bounding.left >= 0 &&
	        bounding.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
	        bounding.right <= (window.innerWidth || document.documentElement.clientWidth)
	    );
	},
	initDropdowns: function(){
		let els=document.querySelectorAll('[data-dropdown]:not([data-dropdown_initialized="1"])');
		if(els.length==0){return false;}
		for(e=0;e<els.length;e++){
			//set dropdown_initialized
			els[e].dataset.dropdown_initialized=1;
			//get the menu obj
			let menuid=els[e].dataset.dropdown;
			let menuobj=wacss.getObject(menuid);
			if(undefined == menuobj){
				els[e].dataset.dropdown_error='dropdown element not found';
				continue;
			}
			//assign the menu obj
			els[e].menuobj=menuobj;
			//listen for mouse enter
			els[e].addEventListener('mouseenter',function(evt){
				wacss.preventDefault(evt);
				if(undefined != this.dropdown){return false;}
				if(undefined == this.menuobj){return false;}
				let dropdown = document.createElement('div');
				dropdown.innerHTML = this.menuobj.innerHTML;
				rect = this.getBoundingClientRect();
				// Check if dropdown would go off right edge of screen
				let dropdownWidth = 300; // estimated width, you can measure after creating
				let windowWidth = window.innerWidth;
				// Get background color from element
				let computedStyle = window.getComputedStyle(this);
				let borderColor = computedStyle.borderColor;
				let bgColor = computedStyle.backgroundColor;
				let shadowColor = 'rgba(0, 0, 0, 0.16)';

				// determine the shadowcolor
				if(undefined != this.menuobj.dataset.border){
					let borderColor = this.menuobj.dataset.border;
					// If it's a hex color
					if(borderColor.startsWith('#')) {
						let hex = borderColor.slice(1);
						let r, g, b;
						// Handle 3-digit hex (#000)
						if(hex.length === 3) {
							r = parseInt(hex[0] + hex[0], 16);
							g = parseInt(hex[1] + hex[1], 16);
							b = parseInt(hex[2] + hex[2], 16);
						}
						// Handle 6-digit hex (#000000)
						else if(hex.length === 6) {
							r = parseInt(hex.slice(0, 2), 16);
							g = parseInt(hex.slice(2, 4), 16);
							b = parseInt(hex.slice(4, 6), 16);
						}
						if(r !== undefined && g !== undefined && b !== undefined) {
							shadowColor = `rgba(${r}, ${g}, ${b}, 0.4)`;
						}
					}
					// If it's a named color or rgb() value, create a temporary element to get computed color
					else {
						let tempEl = document.createElement('div');
						tempEl.style.color = borderColor;
						document.body.appendChild(tempEl);
						let computedColor = window.getComputedStyle(tempEl).color;
						document.body.removeChild(tempEl);

						let rgbMatch = computedColor.match(/\d+/g);
						if(rgbMatch && rgbMatch.length >= 3) {
							shadowColor = `rgba(${rgbMatch[0]}, ${rgbMatch[1]}, ${rgbMatch[2]}, 0.4)`;
						}
					}
				}
				else if(borderColor && borderColor !== 'rgba(0, 0, 0, 0)' && borderColor !== 'transparent') {
					let rgbMatch = borderColor.match(/\d+/g);
					if(rgbMatch && rgbMatch.length >= 3) {
						shadowColor = `rgba(${rgbMatch[0]}, ${rgbMatch[1]}, ${rgbMatch[2]}, 0.4)`;
					}
				}
				// Fall back to background color if no border color
				else if(bgColor && bgColor !== 'rgba(0, 0, 0, 0)' && bgColor !== 'transparent') {
					let rgbMatch = bgColor.match(/\d+/g);
					if(rgbMatch && rgbMatch.length >= 3) {
						shadowColor = `rgba(${rgbMatch[0]}, ${rgbMatch[1]}, ${rgbMatch[2]}, 0.4)`;
					}
				}
				//style the dropdown
				Object.assign(dropdown.style,{
					display:'block',
					position:'absolute',
					top:(rect.bottom + window.scrollY)-1 + 'px',
					padding:'13px',
					zIndex:99999,
					backgroundColor:'#FFFFFF',
					boxShadow: `${shadowColor} 0px 2px 6px`,
					overflow:'hidden',
					maxHeight:'1px',
					transition:'max-height 0.1s ease-out',
					opacity:'0',
					borderRadius:'0 0 3px 3px'
				});
				//add dropdown to the dom
				document.body.appendChild(dropdown);
				// If dropdown would extend beyond right edge, position it to the right of element
				let dropdownRect = dropdown.getBoundingClientRect();
				if (rect.left + dropdownRect.width > windowWidth) {
					dropdown.style.left = (rect.right-(dropdownRect.width)) + 'px';
					dropdown.style.borderTopLeftRadius='10px';
				}
				else{
					dropdown.style.left = rect.left + 'px';
					dropdown.style.borderTopRightRadius='10px';
				}
				// Get the full height after adding to DOM
				let fullHeight = dropdown.scrollHeight;
				// Trigger animation
				requestAnimationFrame(() => {
					dropdown.style.maxHeight = fullHeight + 'px';
					dropdown.style.opacity = '1';
				});
				//assign to each other for easy access later
				this.dropdown=dropdown;
				dropdown.pel=this;
				// Remove dropdown when mouse leaves it and is over the original element
				dropdown.addEventListener('mouseleave', function(evt) {
				if(undefined == this.pel){return false;}
				let originalRect = this.pel.getBoundingClientRect();
				if(
					evt.clientX >= originalRect.left 
					&& evt.clientX <= originalRect.right 
					&& evt.clientY >= originalRect.top 
					&& evt.clientY <= originalRect.bottom
					) {
					return false;
				}
				// Remove dropdown when mouse leaves it
				if(this.parentNode) {
					this.parentNode.removeChild(this);
				}
				// Clear reference from original element
				this.pel.dropdown = undefined;
				});
				return false;
			},false);
			//listen for mouse leave
			els[e].addEventListener('mouseleave',function(evt){
				wacss.preventDefault(evt);
				if(undefined == this.dropdown){return false;}
				// Check if mouse is over the dropdown
				let dropdownRect = this.dropdown.getBoundingClientRect();
				if(
					evt.clientX >= dropdownRect.left 
					&& evt.clientX <= dropdownRect.right 
					&& evt.clientY >= dropdownRect.top 
					&& evt.clientY <= dropdownRect.bottom
					) {
					return false;
				}
				//remove the node
				this.dropdown.parentNode.removeChild(this.dropdown);
				// Clear the reference
				this.dropdown = undefined; 
				return false;
			},false);
			
		}
	},
	initEditor: function(){
		let els=document.querySelectorAll('textarea[data-behavior="editor"]');
		if(els.length==0){return false;}
		for(e=0;e<els.length;e++){
			if(undefined != els[e].dataset.initialized){continue;}
			els[e].setAttribute('data-initialized',1);
			let mh=els[e].style.minHeight || '200px';
			els[e].style.display='none';
			let editor=document.createElement('div');
			editor.setAttribute('contenteditable','true');
			editor.style.minHeight=mh;
			editor.saveto=els[e];
			els[e].editor=editor;
			//enable special keys
			editor.onkeydown=function(e){
				let evt = e || window.event;
			    let keyCode = evt.charCode || evt.keyCode;
			    //console.log(evt.ctrlKey)
			    //console.log(keyCode);
			    //get the selected text, if any
			    let sel = '';
			    if (window.getSelection) {
			        sel = window.getSelection().toString();
			    } else if (document.selection && document.selection.type != "Control") {
			        sel = document.selection.createRange().text;
			    }
			    switch(keyCode){
			    	case 9:
			    		//tab
			    		evt.preventDefault();
			    		document.execCommand('insertHTML', false, '\u0009');
			    	break;
			    	case 85:
			    		if(evt.altKey && sel.length){
			    			//Alt+U => uppercase
			    			evt.preventDefault();
			    			document.execCommand('insertHTML', false, sel.toUpperCase());
			    		}
			    	break;
			    	case 76:
			    		if(evt.altKey && sel.length){
			    			//Alt+l => lowercase
			    			evt.preventDefault();
			    			document.execCommand('insertHTML', false, sel.toLowerCase());
			    		}
			    	break;
			    	case 72:
			    		if(evt.altKey){
			    			//Alt+h => help
			    			evt.preventDefault();
			    			let help='EDITOR COMMAND REFERENCE:'+wacss.CRLF+wacss.CRLF;
			    			help+='Alt+u => uppercase selection'+wacss.CRLF;
			    			help+='Alt+l => lowercase selection'+wacss.CRLF;
			    			help+='Ctrl+b => bold selection'+wacss.CRLF;
			    			help+='Ctrl+i => italic selection'+wacss.CRLF;
			    			help+='Ctrl+u => underline selection'+wacss.CRLF;
			    			help+='Alt+c => clear all formatting';
			    			alert(help);
			    		}
			    	break;
			    	case 67:
			    		if(evt.altKey){
			    			//Alt+c => clear formatting
			    			if(confirm('Clear all formatting?')){
			    				evt.preventDefault();
			    				this.textContent=this.innerText;
			    			}
			    		}
			    	break;
			    }
			}
			//paste text as plain text
			editor.addEventListener("paste", function(e) {
				e.preventDefault();
  				let text = e.clipboardData.getData('text/plain');
  				document.execCommand("insertHTML", false, text);
			});
			//update the textarea anytime it changes
			editor.addEventListener("input", function(ie) {
				//console.log(this.innerHTML);
				//console.log(this.innerText);
				//console.log(this.textContent);
				this.saveto.innerHTML=this.innerText;
			});
			editor.save=function() {
				this.saveto.innerHTML=this.innerText;
			};
			//set initial value the same as textarea
			editor.textContent=els[e].textContent;
			//setEditorMarkup(editor);
			els[e].parentNode.insertBefore(editor, els[e].nextSibling);
		}
	},
	initOnloads: function(){
		let onloads=document.querySelectorAll('[data-onload],[data-wacss_onload]');
		let initcnt=0;
		for(let i=0;i<onloads.length;i++){
			if(undefined != onloads[i].getAttribute('data-onload-ex')){continue;}
			let exval=wacss.guid().replaceAll('-','');
			onloads[i].setAttribute('data-onload-ex',exval);
			let funcstr=onloads[i].dataset.wacss_onload||onloads[i].dataset.onload||'';
			if(funcstr==''){continue;}
			initcnt=initcnt+1;
			let debug=onloads[i].dataset.debug||0;
			if(debug==1){
				console.log(funcstr);
			}
			let funcstrs=funcstr.split(';');
			let thisstr="document.querySelector('[data-onload-ex=\""+exval+"\"]')";
			for(let f=0;f<funcstrs.length;f++){
				funcstr=funcstrs[f];
				funcstr=funcstr.replaceAll('this',thisstr);
				if(debug==1){
					console.log(funcstr);
				}
				let cfunc=new Function(funcstr);

				if(debug==1){
					console.log(cfunc.toString());
				}
				cfunc();
			}
		}
		return initcnt;
	},
	/**
	* @name wacss.initTabs
	* @describe if a textarea has a class of w_tabs then enable the tab key in that textarea
	* @return false
	* @usage wacss.initTabs();
	*/
	initTabs: function(){
		/* if a textarea has a class of w_tabs then enable tabs */
		let list=document.querySelectorAll('textarea.w_tabs');
		for(let i=0;i<list.length;i++){
			list[i].onkeydown=function(event){
				if(event.keyCode===9){
					let v=this.value,s=this.selectionStart,e=this.selectionEnd;
					this.value=v.substring(0, s)+'\t'+v.substring(e);
					this.selectionStart=this.selectionEnd=s+1;
					return false;
				}
			}
		}
		return false;
	},
	/**
	* @name wacss.initWacssEdit
	* @describe convert texteara with a wacssedit class to contenteditable div
	* @usage wacss.initWacssEdit());
	*/
	initWacssEdit: function(){
		let list=document.querySelectorAll('textarea.wacssedit');
		for(let i=0;i<list.length;i++){
			if(undefined == list[i].id){continue;}
			//check to see if we have already initialized this element
			if(undefined != list[i].dataset.initialized){continue;}
			list[i].setAttribute('data-initialized',1);
			let editor_id=list[i].id+'_wacsseditor';
			//does it already exist?
			let eobj=wacss.getObject(editor_id);
			if(undefined != eobj){continue;}
			//create a contenteditable div
			let attrs=wacss.getAllAttributes(list[i]);
			let d = document.createElement('div');
			d.id=editor_id;
			list[i].setAttribute('data-editor',d.id);
			d.setAttribute('data-editor',list[i].id);
			for(k in attrs){
				if(k=='id' || k=='data-editor'){continue;}
				d.setAttribute(k,attrs[k]);
			}
			d.addEventListener('input', function() {
				let eid=this.dataset.editor;
				let tobj=wacss.getObject(eid);
				if(undefined == tobj){
					console.log('textarea update failed: no eid: '+eid);
					return false;
				}
				tobj.innerHTML=this.innerHTML.replace(/</g,'&lt;').replace(/>/g,'&gt;');
			});
			d.setAttribute('contenteditable','true');
			d.innerHTML = list[i].value;
			d.style.display='inherit';
			d.style.borderTopRightRadius=0;
			d.style.borderTopLeftRadius=0;
			list[i].original = list[i].value;
			//hide the textarea and show the contenteditable div in its place
			list[i].style.display='none';
			//wacssedit_bar
			let nav = document.createElement('nav');
			nav.className='nav w_white';
			nav.style.borderLeftColor='#a6a6a6';
			nav.style.borderTopColor='#a6a6a6';
			nav.style.borderRightColor='#a6a6a6';
			nav.style.borderTopRightRadius='4px';
			nav.style.borderTopLeftRadius='4px';
			if(undefined != list[i].getAttribute('data-bar-color')){
				nav.className='nav '+list[i].getAttribute('data-bar-color');
			}
			let ul = document.createElement('ul');
		
			//title,cmd,arg,icon,accesskey
			let buttons={
				'Reset':['reset','','icon-reset','r'],
				'Bold':['bold','','icon-bold','b'],
				'Italic':['italic','','icon-italic','i'],
				'Underline':['underline','','icon-underline','u'],
				'Delete':['delete','','icon-delete',''],
				'Cut':['cut','','icon-scissors','t'],
				'Copy':['copy','','icon-copy','c'],
				'Quote':['formatBlock','blockquote','icon-code','q'],
				'Heading':['heading','','',''],
				'Font':['fontName','','',''],
				'Size':['fontSize','','',''],
				'Color':['','','icon-color-adjust',''],
				'Link':['link','','icon-link','l'],
				'Media':['','','icon-image',''],
				'Justify':['justify','','',''],
				'Form':['form','','',''],
				'Unordered List':['insertUnorderedList','','icon-list-ul','u'],
				'Ordered List':['insertOrderedList','','icon-list-ol','o'],
				'Redo':['redo','','icon-redo','y'],
				'Undo':['undo','','icon-undo','z'],
				'Remove':['removeFormat','','icon-erase','-'],
				'Print':['print','','icon-print','p'],
				'Htmlcode':['code','','icon-file-code','h']
				
			}
			/*
				Features to add:
					fontName
			*/
			let databar=new Array();
			if(undefined != list[i].getAttribute('data-bar')){
				let barstr=list[i].getAttribute('data-bar');
				let btns=barstr.split(',');
				for(let db=0;db<btns.length;db++){
					databar.push(wacss.ucwords(btns[db]));
				}
			}
			//console.log('databar',databar);
			for(name in buttons){
				//console.log(name);
				if(databar.length > 0 && !wacss.in_array(name,databar)){
					continue;
				}
				let li=document.createElement('li');
				let parts;
				let a;
				let icon;
				switch(name.toLowerCase()){
					case 'media':
						a=document.createElement('button');
						a.className='wacssedit dropdown';
						a.title='Multimedia';
						a.li=li;
						a.onclick=function(){
							let list=wacss.getSiblings(this.li);
							for(let s=0;s<list.length;s++){
								wacss.removeClass(list[s],'open');
							}
							wacss.toggleClass(this.li,'open');
							return false;
						};
						let micon=document.createElement('span');
						micon.className='icon-image';
						a.appendChild(micon);
						li.appendChild(a);
						let mediabox=document.createElement('div');
						/* URL */
						let murl=document.createElement('input');
						murl.type='text';
						murl.name='url';
						murl.placeholder='URL to media';
						murl.setAttribute('value','');
						mediabox.appendChild(murl);
						/* drag n drop or click to upload */
						let mfilebox=document.createElement('div');
						mfilebox.style.marginTop='10px';
						let mfile=document.createElement('input');
						mfile.type='file';
						mfile.accept='audio/*,video/*,image/*';
						mfile.id='mediafile';
						mfile.name='mediafile';
						mfile.setAttribute('multiple','multiple');
						mfile.style.position='fixed';
						mfile.style.top='-1000px';
						mfile.filebox=mfilebox;
						mfile.onchange=function(evt){
							wacss.preventDefault(evt);
							wacss.wacsseditHandleFiles(this);
							return false;
						}
						mediabox.appendChild(mfile);
						let mdrop=document.createElement('label');
						mdrop.filebox=mfilebox;
						mdrop.addEventListener('dragenter',function(evt){
							wacss.preventDefault(evt);
							this.style.border='1px dashed #28a745';
							this.style.color='#28a745';
							return false;
						},false);
						mdrop.addEventListener('dragleave',function(evt){
							wacss.preventDefault(evt);
							this.style.border='1px dashed #ccc';
							this.style.color='#999999';
							return false;
						},false);
						mdrop.addEventListener('dragover',function(evt){
							wacss.preventDefault(evt);
						 	return false;
						},false);
						mdrop.addEventListener('drop',function(evt){
							wacss.preventDefault(evt);
  							this.files = evt.dataTransfer.files;
  							wacss.wacsseditHandleFiles(this);
							return false;
						},false);
						mdrop.setAttribute('for','mediafile');
						mdrop.style.display='block';
						mdrop.style.border='1px dashed #ccc';
						mdrop.style.borderRadius='5px';
						mdrop.style.marginTop='10px';
						mdrop.style.color='#999999';
						mdrop.style.padding='15px';
						mdrop.innerHTML='Drag n Drop<br />(or click to browse)';
						mdrop.style.textAlign='center';
						mdrop.style.backgroundColor='#FFF';
						mediabox.appendChild(mdrop);
						mediabox.appendChild(mfilebox);
						/* max width and max height */
						let mmaxbox=document.createElement('div');
						mmaxbox.style.display='flex';
						mmaxbox.style.flexDirection='row';
						mmaxbox.style.marginTop='10px';
						/* max width */
						let mwidth=document.createElement('input');
						mwidth.type='text';
						mwidth.name='width';
						mwidth.placeholder='Width';
						mwidth.title="Max Width - defaults to 200px";
						mwidth.style.flex='1 1 auto';
						mwidth.style.marginRight='5px';
						mwidth.pattern='[0-9px\%]+';
						mwidth.oninput=function(){this.reportValidity();};
						mmaxbox.appendChild(mwidth);
						/* max height */
						let mheight=document.createElement('input');
						mheight.type='text';
						mheight.name='height';
						mheight.placeholder='Height';
						mheight.title='Max Height - defaults to 200px';
						mheight.style.flex='1 1 auto';
						mheight.style.marginLeft='5px';
						mheight.pattern='[0-9px\%]+';
						mheight.oninput=function(){this.reportValidity();};
						mmaxbox.appendChild(mheight);

						mediabox.appendChild(mmaxbox);

						/* align and border */
						let mabbox=document.createElement('div');
						mabbox.style.display='flex';
						mabbox.style.flexDirection='row';
						mabbox.style.marginTop='10px';
						/* align */
						let malign=document.createElement('select');
						malign.style.flex='1 1 auto';
						malign.title='Align';
						malign.name="align";
						malign.style.marginRight='5px';
						let malign_opts=new Array('left','center','right');
						for(let opt in malign_opts){
							let malign_opt=document.createElement('option');
							malign_opt.value=malign_opts[opt];
							malign_opt.innerText=malign_opts[opt];
							malign.appendChild(malign_opt);
						}
						mabbox.appendChild(malign);
						/* border */
						let mborder=document.createElement('input');
						mborder.type='checkbox';
						mborder.name="border";
						mborder.style.flex='1 1 auto';
						mborder.title='Border';
						mborder.style.marginLeft='5px';
						mabbox.appendChild(mborder);

						mediabox.appendChild(mabbox);

						/* save and reset  */
						let msrbox=document.createElement('div');
						msrbox.style.display='flex';
						msrbox.style.flexDirection='row';
						msrbox.style.marginTop='10px';
						/* reset */
						let mreset=document.createElement('button');
						mreset.style.flex='1 1 auto';
						mreset.title='Reset';
						mreset.type='button';
						mreset.innerText='Reset';
						mreset.className='btn w_red';
						mreset.elems=new Array(murl,mfilebox,mwidth,mheight,mborder);
						mreset.onclick=function(){
							for(let x in this.elems){
								switch(this.elems[x].tagName.toLowerCase()){
									case 'div':
										this.elems[x].innerHTML='';	
									break;
									case 'input':
										switch(this.elems[x].type.toLowerCase()){
											case 'text':
												this.elems[x].value='';
											break;
											case 'checkbox':
												this.elems[x].checked=false;
											break;
										}	
									break;
								}
								
							}
						}
						mreset.style.marginRight='5px';
						msrbox.appendChild(mreset);
						/* insert */
						let msave=document.createElement('button');
						msave.style.flex='1 1 auto';
						msave.title='Insert at cursor';
						msave.type='button';
						msave.innerText='Insert';
						msave.className='btn w_green';
						msave.style.marginRight='5px';
						msave.mwidth=mwidth;
						msave.mheight=mheight;
						msave.malign=malign;
						msave.mborder=mborder;
						msave.murl=murl;
						msave.mfiles=mfilebox;
						msave.li=li;
						msave.elems=new Array(murl,mfilebox,mwidth,mheight,mborder);
						msave.onclick=function(){
							let width='300px';
							if(undefined != this.mwidth.value && this.mwidth.value.length){width=this.mwidth.value;}
							if(width.indexOf('px')==-1 && width.indexOf('%')==-1){
								width=width+'px';
							}
							let height='200px';
							if(undefined != this.mheight.value && this.mheight.value.length){height=this.mheight.value;}
							if(height.indexOf('px')==-1 && height.indexOf('%')==-1){
								height=height+'px';
							}
							let align=msave.malign.value;
							let style="max-width:"+width+";max-height:"+height+";";
							if(undefined != this.mborder && this.mborder.checked){style=style+'border:1px outset #000;';}
							document.execCommand('removeFormat',false);
							/* image */
							let list=this.mfiles.querySelectorAll('img');	
							for(let y=0;y<list.length;y++){
								let htm='<div style="float:'+align+';"><img src="'+list[y].src+'" style="'+style+'" /></div>';
							 	document.execCommand("insertHTML", false, htm);
							}
							/* audio */
							list=this.mfiles.querySelectorAll('audio');	
							for(let y=0;y<list.length;y++){
								let htm='<div style="float:'+align+';"><audio src="'+list[y].src+'" style="'+style+'" controls="controls" /></div>';
							 	document.execCommand("insertHTML", false, htm);
							}
							/* video */
							list=this.mfiles.querySelectorAll('video');	
							for(let y=0;y<list.length;y++){
								let htm='<div style="float:'+align+';"><video src="'+list[y].src+'" style="'+style+'" controls="controls" /></div>';
							 	document.execCommand("insertHTML", false, htm);
							}
							/* url */
							if(this.murl.value.length){
								let ext=this.murl.value.split('.').pop();
								let style='';
								let htm='';
								switch(ext.toLowerCase()){
									case 'png':
									case 'jpg':
									case 'jpeg':
									case 'gif':
									case 'svg':
										htm='<div style="float:'+align+';"><img src="'+this.murl.value+'" style="'+style+'" /></div>';
									 	document.execCommand("insertHTML", false, htm);
									break;
									case 'mp3':
										htm='<div style="float:'+align+';"><audio src="'+this.murl.value+'" style="'+style+'" controls="controls" /></div>';
									 	document.execCommand("insertHTML", false, htm);
									break;
									case 'mp4':
										htm='<div style="float:'+align+';"><video src="'+this.murl.value+'" style="'+style+'" controls="controls" /></div>';
									 	document.execCommand("insertHTML", false, htm);
									break;
									default:
										//check for youtube
										if(this.murl.value.indexOf('youtube.com') != -1){
											/* https://www.youtube.com/watch?v=_DmM_6pa-TI replaced with  https://www.youtube.com/embed/_DmM_6pa-TI */
											let src=this.murl.value.replace('watch?v=','embed/');
											htm='<div style="float:'+align+';"><iframe src="'+src+'" style="'+style+'" ></iframe></div>';
										}
										else{
											htm='<div style="float:'+align+';"><embed src="'+this.murl.value+'" style="'+style+'" controls="controls" ></embed></div>';	
										}
										
									 	document.execCommand("insertHTML", false, htm);
									break;
								}
							}
							wacss.initWacssEditElements();
							//return false;
							/* reset the form */
							for(let x in this.elems){
								switch(this.elems[x].tagName.toLowerCase()){
									case 'div':
										this.elems[x].innerHTML='';	
									break;
									case 'input':
										switch(this.elems[x].type.toLowerCase()){
											case 'text':
												this.elems[x].value='';
											break;
											case 'checkbox':
												this.elems[x].checked=false;
											break;
										}	
									break;
								}
								
							}
							wacss.removeClass(this.li,'open');
							return false;
						};
						msrbox.appendChild(msave);

						mediabox.appendChild(msrbox);


						let mul=document.createElement('ul');
						mul.style.padding='10px';
						mul.appendChild(mediabox);
						li.appendChild(mul)
						break;

					break;
					case 'color':
						a=document.createElement('button');
						a.className='wacssedit dropdown';
						a.title='Text Color';
						a.li=li;
						a.onclick=function(){
							let list=wacss.getSiblings(this.li);
							for(let s=0;s<list.length;s++){
								wacss.removeClass(list[s],'open');
							}
							wacss.toggleClass(this.li,'open');
							return false;
						};
						let cicon=document.createElement('span');
						cicon.className='icon-textcolor';
						a.appendChild(cicon);
						li.appendChild(a);
						let colors={
							r1:['#000000','#444444','#666666','#999999','#cccccc','#eeeeee','#f3f3f3','#ffffff'],
							r2:['#ff0000','#ff9900','#ffff00','#00ff00','#00ffff','#0000ff','#9900ff','#ff00ff'],
							r3:['#f4cccc','#fce5cd','#fff2cc','#d9ead3','#d0e0e3','#cfe2f3','#d9d2e9','#ead1dc'],
							r4:['#ea9999','#f9cb9c','#ffe599','#b6d7a8','#a2c4c9','#9fc5e8','#b4a7d6','#d5a6bd'],
							r5:['#e06666','#ebaa66','#fad564','#8ab976','#729fa9','#6aa1d2','#8776b9','#bd789b'],
							r6:['#bc0000','#dc8b36','#e1b52f','#659f4b','#427b88','#3a7ebc','#5c4594','#9f4a74'],
							r7:['#8d0000','#a45705','#b88b00','#36721c','#124a57','#0b508f','#321a6e','#6e1a43'],
							r8:['#5f0000','#6f3a04','#775a00','#254b12','#0b313a','#07335c','#1e1149','#48102e']
						};
						let colorflexbox=document.createElement('div');
						colorflexbox.style.display='flex';
						/* Background Color */
						let bgbox=document.createElement('div');
						bgbox.style.flex='1 1 0';
						bgbox.style.borderRight='10px solid transparent';
						let title=document.createElement('div');
						title.style.textAlign='left';
						title.style.color='#000000';
						title.innerText='Background Color';
						title.style.padding='5px 0 5px 0';
						bgbox.appendChild(title);
						let table=document.createElement('table');
						table.style.width='98%';
						table.style.borderCollapse='separate';
						table.style.borderSpacing='2px';
						for(let k in colors){
							let tr=document.createElement('tr');
							for(t=0;t<colors[k].length;t++){
								let td=document.createElement('td');
								td.style.padding='0px';
								let b=document.createElement('button');
								b.className='wacssedit';
								b.style.backgroundColor=colors[k][t];
								b.style.width='16px';
								b.style.height='16px';
								b.style.border='1px solid transparent';
								b.setAttribute('data-cmd','backColor');
								b.setAttribute('data-arg',colors[k][t]);
								b.setAttribute('title',colors[k][t]);
								b.setAttribute('data-txt',list[i].id);
								b.onmouseover=function(){
									this.style.border='1px solid #000';
								};
								b.onmouseout=function(){
									this.style.border='1px solid transparent';
								};
								td.appendChild(b);
								tr.appendChild(td);
							}
							table.appendChild(tr);
						}
						bgbox.appendChild(table);
						/* Text Color */
						let tbox=document.createElement('div');
						tbox.style.flex='1 1 0';
						title=document.createElement('div');
						title.style.textAlign='left';
						title.style.color='#000000';
						title.innerText='Text Color';
						title.style.padding='5px 0 5px 0';
						tbox.appendChild(title);
						table=document.createElement('table');
						table.style.width='98%';
						table.style.borderCollapse='separate';
						table.style.borderSpacing='2px';
						for(let k in colors){
							let tr=document.createElement('tr');
							for(t=0;t<colors[k].length;t++){
								let td=document.createElement('td');
								td.style.padding='0px';
								let b=document.createElement('button');
								b.className='wacssedit';
								b.style.backgroundColor=colors[k][t];
								b.style.width='16px';
								b.style.height='16px';
								b.style.border='1px solid transparent';
								b.setAttribute('data-cmd','foreColor');
								b.setAttribute('data-arg',colors[k][t]);
								b.setAttribute('title',colors[k][t]);
								b.setAttribute('data-txt',list[i].id);
								b.onmouseover=function(){
									this.style.border='1px solid #000';
								};
								b.onmouseout=function(){
									this.style.border='1px solid transparent';
								};
								td.appendChild(b);
								tr.appendChild(td);
							}
							table.appendChild(tr);
						}
						tbox.appendChild(table);
						colorflexbox.appendChild(bgbox);
						colorflexbox.appendChild(tbox);
						let cul=document.createElement('ul');
						cul.style.padding='10px';
						cul.appendChild(colorflexbox);
						li.appendChild(cul)
						break;

					break;
					case 'heading':
						//headings H1-6
						a=document.createElement('button');
						a.className='wacssedit dropdown';
						a.title=name;
						a.li=li;
						a.onclick=function(){
							let list=wacss.getSiblings(this.li);
							for(let s=0;s<list.length;s++){
								wacss.removeClass(list[s],'open');
							}
							wacss.toggleClass(this.li,'open');
							return false;
						};
						a.innerHTML=name;
						li.appendChild(a);
						let hul=document.createElement('ul');
						hul.style.maxHeight='175px';
						hul.style.overflow='auto';
						for(let h=1;h<7;h++){
							let hname='H'+h;
							let hli=document.createElement('li');
							hul.appendChild(hli);
							ha=document.createElement('button');
							ha.className='wacssedit';
							let hh=document.createElement(hname);
							hh.innerHTML=hname;
							ha.appendChild(hh);
							ha.setAttribute('data-cmd','formatBlock');
							ha.setAttribute('data-arg','H'+h);
							ha.setAttribute('data-txt',list[i].id);
							hli.appendChild(ha);
						}
						
						li.appendChild(hul);

					break;
					case 'font':
						//justify full,left,center,right
						a=document.createElement('button');
						a.className='wacssedit dropdown';
						a.title=name;
						a.li=li;
						a.onclick=function(){
							let list=wacss.getSiblings(this.li);
							for(let s=0;s<list.length;s++){
								wacss.removeClass(list[s],'open');
							}
							wacss.toggleClass(this.li,'open');
							return false;
						};
						a.innerHTML=name;
						li.appendChild(a);
						let fnul=document.createElement('ul');
						fnul.style.maxHeight='175px';
						fnul.style.overflow='auto';
						let fonts=new Array('Arial','Helvetica','Times New Roman','Times','Courier New','Courier','Verdana','Georgia','Palatino','Garamond','Bookman','Comic Sans MS','Trebuchet MS','Arial Black','Impact');
						for(let fn=0;fn<fonts.length;fn++){
							let fnli=document.createElement('li');
							fnul.appendChild(fnli);
							fna=document.createElement('button');
							fna.className='wacssedit';
							fna.setAttribute('data-cmd','fontName');
							fna.setAttribute('data-arg',fonts[fn]);
							fna.setAttribute('data-txt',list[i].id);
							fna.style.fontFamily=fonts[fn];
							fna.innerHTML=fonts[fn];
							fnli.appendChild(fna);
						}
						li.appendChild(fnul);
					break;
					case 'size':
						//headings H1-6
						a=document.createElement('button');
						a.className='wacssedit dropdown';
						a.title='Text Size';
						a.li=li;
						a.onclick=function(){
							let list=wacss.getSiblings(this.li);
							for(let s=0;s<list.length;s++){
								wacss.removeClass(list[s],'open');
							}
							wacss.toggleClass(this.li,'open');
							return false;
						};
						let sicon=document.createElement('span');
						sicon.className='icon-textsize';
						a.appendChild(sicon);
						li.appendChild(a);
						let fsul=document.createElement('ul');
						fsul.style.maxHeight='175px';
						fsul.style.overflow='auto';
						for(let fs=1;fs<7;fs++){
							let fsname='Size '+fs;
							let fsli=document.createElement('li');
							fsul.appendChild(fsli);
							let fsa=document.createElement('button');
							fsa.className='wacssedit';
							let fsf=document.createElement('font');
							fsf.setAttribute('size',fs);
							fsf.innerHTML=fsname;
							fsa.appendChild(fsf);
							fsa.setAttribute('data-cmd','fontSize');
							fsa.setAttribute('data-arg',fs);
							fsa.setAttribute('data-txt',list[i].id);
							fsli.appendChild(fsa);
						}
						li.appendChild(fsul);
					break;
					case 'justify':
						//justify full,left,center,right
						a=document.createElement('button');
						a.className='wacssedit dropdown';
						a.title=name;
						a.li=li;
						a.onclick=function(){
							let list=wacss.getSiblings(this.li);
							for(let s=0;s<list.length;s++){
								wacss.removeClass(list[s],'open');
							}
							wacss.toggleClass(this.li,'open');
							return false;
						};
						a.innerHTML=name;
						li.appendChild(a);
						let jul=document.createElement('ul');
						jul.style.maxHeight='175px';
						jul.style.overflow='auto';
						let jopts=new Array('indent','outdent','full','left','center','right',);
						for(let j=0;j<jopts.length;j++){
							let jname=wacss.ucwords(jopts[j]);
							let jli=document.createElement('li');
							jul.appendChild(jli);
							ja=document.createElement('button');
							ja.className='wacssedit';
							ja.setAttribute('data-txt',list[i].id);
							let jicon=document.createElement('span');
							switch(jopts[j]){
								case 'indent':
								case 'outdent':
									ja.setAttribute('data-cmd',jopts[j]);
									jicon.className='icon-'+jopts[j];
								break;
								default:
									ja.setAttribute('data-cmd','justify'+jname);
									jicon.className='icon-justify-'+jopts[j];
								break;	
							}
							ja.appendChild(jicon);
							let jtxt=document.createElement('span');
							jtxt.innerHTML=' '+jname;
							ja.appendChild(jtxt);
							jli.appendChild(ja);
						}
						li.appendChild(jul);
					break;
					case 'form':
						//justify full,left,center,right
						//Multi-media insert:  https://www.froala.com/wysiwyg-editor/examples/custom-image-button
						a=document.createElement('button');
						a.className='wacssedit dropdown';
						a.title=name;
						a.li=li;
						a.onclick=function(){
							let list=wacss.getSiblings(this.li);
							for(let s=0;s<list.length;s++){
								wacss.removeClass(list[s],'open');
							}
							wacss.toggleClass(this.li,'open');
							return false;
						};
						a.innerHTML=name;
						a.style.color='#3d7a7a';
						li.appendChild(a);
						let sul=document.createElement('ul');
						sul.style.maxHeight='325px';
						sul.style.overflow='auto';
						let types={
							date:'Date Picker <span class="icon-calendar"></span>',
							raten5:'Rating Number 1 - 5 <span class="icon-radio-button w_smaller"></span>',
							raten10:'Rating Number 1 - 10 <span class="icon-radio-button w_smaller"></span>',
							rates5:'Rating Stars 1 -5 <span class="icon-star-empty"></span>',
							rates10:'Rating Stars 1 -10 <span class="icon-star-empty"></span>',
							one:'Select One <span class="icon-checkbox"></span>',
							many:'Select Multiple <span class="icon-checkbox"></span> <span class="icon-checkbox"></span>',
							hideonview:'Hide On View <span class="icon-moon-quarter"></span>',
							section:'Section Marker <span class="icon-bookmark"></span>',
							signature:'Signature <span class="icon-signature"></span>',
							text:'Text One <span class="icon-text"></span>',
							textarea:'Text Multiple <span class="icon-textarea"></span>',
							customcode:'Insert Custom Code {}'
						};
						for(let type in types){
							let sli=document.createElement('li');
							sul.appendChild(sli);
							sna=document.createElement('button');
							sna.className='wacssedit';
							sna.style.color='#3d7a7a';
							sna.setAttribute('data-cmd','form');
							sna.setAttribute('data-arg',type);
							sna.setAttribute('data-txt',list[i].id);
							sna.innerHTML=types[type];
							sli.appendChild(sna);
						}
						li.appendChild(sul);
					break;
					default:
						parts=buttons[name];
						a=document.createElement('button');
						a.className='wacssedit';
						a.title=name;
						a.onclick=function(){return false;};
						a.setAttribute('data-txt',list[i].id);
						if(parts[3].length){
							a.setAttribute('accesskey',parts[3]);
							a.title=a.title+' (ALT-'+parts[3]+')';
						}
						a.setAttribute('data-cmd',parts[0]);
						if(parts[1].length){
							a.setAttribute('data-arg',parts[1]);
						}
						if(parts[2].length){
							//icon
							icon=document.createElement('span');
							icon.className=parts[2];
							a.appendChild(icon);
						}
						li.appendChild(a);
					break;
				}
				ul.appendChild(li);
			}
			nav.appendChild(ul);
			
			list[i].parentNode.insertAdjacentElement('afterBegin',d);
			list[i].parentNode.insertAdjacentElement('afterBegin',nav);
			
			//list[i].parentNode.replaceChild(d, list[i]);
		}
		if(list.length){
			document.execCommand('styleWithCSS',true,null);
		}
		list=document.querySelectorAll('button.wacssedit');
		for(i=0;i<list.length;i++){
			let cmd=list[i].getAttribute('data-cmd');
			if(undefined == cmd){continue;}
			list[i].setAttribute('data-wacssedit-cmd',cmd);
			list[i].onclick=function(event){
				event.preventDefault();
				let cmd=this.getAttribute('data-cmd');
				//console.log('onclick',cmd);
				let tid=this.getAttribute('data-txt');
				let tobj=wacss.getObject(tid);
				if(undefined == tobj){
					console.log('wacssedit code error: no tobj');
					return false;
				}
				let dobj=wacss.getObject(tid+'_wacsseditor');
				if(undefined == dobj){
					console.log('wacssedit code error: no dobj');
					wacss.initWacssEditElements();
					return false;
				}
				switch(cmd){
					case 'form':
						let arg=this.getAttribute('data-arg');
						document.execCommand('removeFormat',false);
					 	document.execCommand("insertHTML", false, "<span class='wacssform_"+arg+"'>"+ document.getSelection()+'</span>');
					 	wacss.initWacssEditElements();
					 	return false;
					break;
					case 'link':
						let sel=document.getSelection();
						if(sel.type.toLowerCase()!='range'){
							alert('Select text first');
							return false;
						}
						let lurl=prompt('ENTER URL','https://');
						if(undefined != lurl && lurl.length){
							let target=prompt('ENTER TARGET (optional)');
							if(undefined != target && target.length){
								document.execCommand("insertHTML", false, '<a href=\"'+lurl+'\" target=\"'+target+'\">'+sel+'</a>');	
							}
							else{
								document.execCommand("insertHTML", false, '<a href=\"'+lurl+'\">'+sel+'</a>');
							}
						}
					 	wacss.initWacssEditElements();
					 	return false;
					break;
					case 'reset':
						if(confirm('Reset back to original?'+dobj.original)){
							dobj.innerHTML=tobj.original;
						}
						wacss.initWacssEditElements();
						return false;
					break;
					case 'print':
						let oPrntWin = window.open("","_blank","width=450,height=470,left=400,top=100,menubar=yes,toolbar=no,location=no,scrollbars=yes");
						oPrntWin.document.open();
						oPrntWin.document.write("<!doctype html><html><head><title>Print<\/title><\/head><body onload=\"print();\">" + dobj.innerHTML + "<\/body><\/html>");
						oPrntWin.document.close();
						wacss.initWacssEditElements();
						return false;
					break;
					case 'code':
						if(tobj.style.display=='none'){
							//switch to textarea edit mode
							dobj.removeEventListener('input', function() {
								let eid=this.getAttribute('data-editor');
								let tobj=wacss.getObject(eid);
								if(undefined == tobj){
									console.log('textarea update failed: no eid: '+eid);
									wacss.initWacssEditElements();
									return false;
								}
								tobj.innerHTML=this.innerHTML.replace(/</g,'&lt;').replace(/>/g,'&gt;');
							});
							dobj.style.display='none';
							tobj.style.display='block';
							tobj.focus();
							tobj.addEventListener('input', function() {
								let eid=this.getAttribute('data-editor');
								let tobj=wacss.getObject(eid);
								if(undefined == tobj){
									console.log('textarea update failed: no eid: '+eid);
									wacss.initWacssEditElements();
									return false;
								}
								tobj.innerHTML=this.value;
							});
						}
						else{
							//switch to wysiwyg edit mode 
							tobj.removeEventListener('input', function() {
								let eid=this.getAttribute('data-editor');
								let tobj=wacss.getObject(eid);
								if(undefined == tobj){
									console.log('textarea update failed: no eid: '+eid);
									wacss.initWacssEditElements();
									return false;
								}
								tobj.innerHTML=this.value;
							});
							tobj.style.display='none';
							dobj.style.display='block';
							dobj.focus();
							dobj.addEventListener('input', function() {
								let eid=this.getAttribute('data-editor');
								let tobj=wacss.getObject(eid);
								if(undefined == tobj){
									console.log('textarea update failed: no eid: '+eid);
									wacss.initWacssEditElements();
									return false;
								}
								tobj.value=this.innerHTML;
							});
						}
						wacss.initWacssEditElements();
						return false;
					break;
					default:
						if(undefined == this.getAttribute('data-arg')){
							//console.log(cmd);
							document.execCommand(cmd,false,null);
						}
						else{
							let arg=this.getAttribute('data-arg');
							//console.log(cmd,arg);
							document.execCommand(cmd,false,arg);
						}
						tobj.innerHTML=dobj.innerHTML;
						wacss.initWacssEditElements();
						return false;
					break;
				}
				wacss.initWacssEditElements();
			};
		}
		wacss.initWacssEditElements();
	},
	initWacssEditElements: function(){
		let list=document.querySelectorAll('[contenteditable] .wacssform_one');
		for(let i=0;i<list.length;i++){
			let p=wacss.getParent(list[i],'div');
			if(undefined == p || undefined == p.nextSibling){continue;}
			let lis=p.nextSibling.querySelectorAll('ul li');
			for(let x=0;x<lis.length;x++){
				lis[x].className='wacssform_one';
			}
		}
		list=document.querySelectorAll('[contenteditable] .wacssform_many');
		for(let i=0;i<list.length;i++){
			let p=wacss.getParent(list[i],'div');
			if(undefined == p || undefined == p.nextSibling){continue;}
			let lis=p.nextSibling.querySelectorAll('ul li');
			for(let x=0;x<lis.length;x++){
				lis[x].className='wacssform_many';
			}
		}
	},
	initSignaturePad:function(){
		let list=document.querySelectorAll('textarea[data-behavior="signature_pad"]');
		//console.log("initSignaturePad found "+list.length);
		for(let i=0;i<list.length;i++){
			//require id
			if(undefined == list[i].id){
				console.log("wacss.initSignaturePad Error - No ID");
				console.log(list[i]);
				continue;
			}
			//require name
			if(undefined == list[i].name){
				console.log("wacss.initSignaturePad Error - No name");
				console.log(list[i]);
				continue;
			}
			//initialize this object so we only build it once
			if(undefined != list[i].dataset.initialized){continue;}
			list[i].dataset.initialized=1;
			//hide the textarea
			list[i].style.display='none';
			//wrapper
			let wrapper = document.createElement('div');
			wrapper.style.width = list[i].style.width;
			wrapper.style.height = list[i].style.height;
			wrapper.style.display='block';
			wrapper.style.position='relative';
			wrapper.style.border='1px solid #ccc';
			wrapper.style.borderRadius='3px';
			wrapper.id=list[i].id+'_wrapper';
			wrapper.style.backgroundColor=list[i].dataset.fill||'#fff';
			//put wrapper in the DOM
			list[i].parentNode.insertBefore(wrapper, list[i].nextSibling);
			//make an _inline hidden field to tell WaSQL to process this as an inline image
			wrapper.inline = document.createElement('input');
			wrapper.inline.type='hidden';
			wrapper.inline.name=list[i].name+'_inline';
			wrapper.inline.value=1;
			wrapper.appendChild(wrapper.inline);
			//build a canvas
			wrapper.canvas = document.createElement('canvas');
			wrapper.canvas.style.zIndex=5000;
			wrapper.canvas.style.position='absolute';
			wrapper.canvas.style.top='0px';
			wrapper.canvas.style.left='0px';
			wrapper.canvas.id=list[i].id+'_canvas';
			wrapper.canvas.width='500';
			wrapper.canvas.height='200';
			wrapper.appendChild(wrapper.canvas);
			//build a resize observer to make it responsive
			wrapper.ro = new ResizeObserver(entries => {
				for (let entry of entries) {
					if(undefined != entry.target.canvas && undefined!=entry.target.clientWidth){
						entry.target.canvas.setAttribute('width',parseInt(entry.target.clientWidth));
						entry.target.canvas.setAttribute('height',parseInt(entry.target.clientHeight)-30);
					}
			 	}
			});
			wrapper.ro.observe(wrapper);
			// call signature_pad
			wrapper.pad=new SignaturePad(wrapper.canvas);
			if(undefined == wrapper.pad){
				console.log("wacss.initSignaturePad Error - failed to create SignaturePad object");
				console.log(wrapper.canvas);
				continue;
			}
			//load image?
			if(list[i].innerHTML.length){
				wrapper.hide_undo=true;
		        wrapper.pad.fromDataURL(list[i].innerHTML,{ ratio: 1, width: wrapper.clientWidth, height: wrapper.clientHeight, xOffset: 0, yOffset: 0 });
			}
			//assign the textarea to the wrapper
			wrapper.pad.txtarea=list[i];
			//save to the textarea so we can upload it as an inline field
			wrapper.pad.addEventListener('endStroke', function(){
  				this.txtarea.innerText=this.toDataURL('image/png');
			});
			//build toolbar
			wrapper.toolbar = document.createElement('div');
			wrapper.toolbar.style.position='absolute';
			wrapper.toolbar.style.bottom='0px';
			wrapper.toolbar.style.left='0px';
			wrapper.toolbar.style.display='flex';
			wrapper.toolbar.style.justifyContent='flex-end';
			wrapper.toolbar.style.alignItems='center';
			wrapper.toolbar.style.width='100%';
			wrapper.toolbar.style.height='30px';
			wrapper.toolbar.style.borderTop='1px solid #ccc';
			wrapper.appendChild(wrapper.toolbar);
			//toolbar.text
			params={style:'flex:1;color:#999;font-size:0.8rem;height:16px;',class:'input w_small'};
			wrapper.toolbar.txt=wacss.buildFormText('txt',params);
			wrapper.toolbar.txt.onkeyup=function(){
				return this.parentNode.parentNode.typeText();
			};
			wrapper.toolbar.txt.onfocus=function(){
				this.style.outline='none';
			}
			wrapper.typeText=function(){
				let pad=this.pad;
				let txt=wacss.trim(this.toolbar.txt.value);
				if(txt.length==0){return true;}
				pad.clear();
				let fontname=this.dataset.font;
				let ctx=pad._ctx;
				let w=pad.canvas.width;
                let h=pad.canvas.height;
                let px=parseInt(h/2)+5;
                pad.clear();
                pad.penColor=this.dataset.pencolor;
                ctx.font = px+'px '+fontname;
				ctx.textAlign='start';
				let x=15;
                let y=parseInt(h/2)+10;
                let m=w-20;
				ctx.fillText(txt,x, y, m);
				pad.txtarea.innerText=pad.toDataURL('image/png');
				return true;
			};
			wrapper.toolbar.txt.title="Signature";
			wrapper.toolbar.txt.style.borderColor='transparent';
			wrapper.toolbar.txt.style.borderRight='1px solid #ccc';
			wrapper.toolbar.appendChild(wrapper.toolbar.txt);
			//toolbar.font - default to andragogy
			wrapper.dataset.font=list[i].dataset.font || 'andragogy';
			wrapper.dataset.font=wrapper.dataset.font.replace('_','');
			let fonts={
				'arial':'Arial',
				'andragogy':'Andragogy',
				'high_summit':'High Summit',
				'julialauren':'Julia Lauren',
				'katrineholland':'Katrine Holland',
				'sandrabelhock':'Sandra Belhock',
				'yasminerothem':'Yasmine Rothem',
			};
			//load the fonts so they are available when selected.
			Object.keys(fonts).forEach(function(fontname) {
			  let el=document.createElement('div');
			  el.style.float='left';
			  el.style.fontSize='1px';
			  el.style.fontFamily=fontname;
			  el.innerText='.';
			  wrapper.toolbar.appendChild(el);
			});
			params={
				style:'border-color:transparent;color:#999;margin-left:2px;width:80px;font-size:0.8rem;padding:3px;',
				value:wrapper.dataset.font
			};
			wrapper.toolbar.font=wacss.buildFormSelect('font',fonts,params);
			wrapper.toolbar.font.onchange=function(){
				let fontname=this.options[this.selectedIndex].value;
				this.parentNode.parentNode.dataset.font=fontname;
				return this.parentNode.parentNode.typeText();
			};
			wrapper.toolbar.font.onfocus=function(){
				this.style.outline='none';
			}
			wrapper.toolbar.font.title="Signature Font";
			wrapper.toolbar.font.style.borderColor='#ccc';
			wrapper.toolbar.appendChild(wrapper.toolbar.font);
			//toolbar.pencolor - default to black
			wrapper.dataset.pencolor=list[i].dataset.pencolor || list[i].dataset.color || '#000000';
			let pencolors={
				'#000000':'Black',
				'#002B59':'Cyan Blue',
				'#545AA7':'Purple',
				'#EC1C24':'Verizon Red',
				'#91A3B0':'Cadet Grey'
			};
			params={
				style:'background-color:#000;color:#fff;margin-left:2px;width:70px;font-size:0.8rem;padding:3px;',
				value:list[i].dataset.pencolor
			};
			wrapper.toolbar.pencolor=wacss.buildFormSelect('pencolor',pencolors,params);
			wrapper.toolbar.pencolor.onchange=function(){
				let pencolor=this.options[this.selectedIndex].value;
				this.parentNode.parentNode.pad.penColor=pencolor;
				this.parentNode.parentNode.dataset.pencolor=pencolor;
				this.style.borderColor=pencolor;
				this.style.backgroundColor=pencolor;
				return this.parentNode.parentNode.typeText();
			};
			wrapper.toolbar.pencolor.onfocus=function(){
				this.style.outline='none';
			}
			wrapper.toolbar.pencolor.title="Pen Color";
			wrapper.toolbar.pencolor.style.borderColor='#000000';
			wrapper.toolbar.appendChild(wrapper.toolbar.pencolor);
			//toolbar.undo
			wrapper.toolbar.undo=document.createElement('span');
			wrapper.toolbar.undo.className='w_biggest w_pointer w_blue icon-undo';
			wrapper.toolbar.undo.title="Undo";
			wrapper.toolbar.undo.style.marginLeft='2px';
			wrapper.toolbar.undo.onclick=function(){
				let data = this.parentNode.parentNode.pad.toData();
				if (data) {
					data.pop(); // remove the last dot or line
					this.parentNode.parentNode.pad.fromData(data);
				}
				//reset the pencolor
				this.parentNode.parentNode.pad.penColor=this.parentNode.parentNode.dataset.pencolor;
			}
			if(undefined!=wrapper.hide_undo){
				wrapper.toolbar.undo.style.display='none';
			}
			wrapper.toolbar.appendChild(wrapper.toolbar.undo);

			//toolbar.clear
			wrapper.toolbar.clear=document.createElement('span');
			wrapper.toolbar.clear.className='w_biggest w_pointer w_red icon-erase';
			wrapper.toolbar.clear.title='Clear';
			wrapper.toolbar.clear.style.marginLeft='2px';
			wrapper.toolbar.clear.onclick=function(){
				this.parentNode.txt.value='';
				this.parentNode.parentNode.pad.clear();
				this.parentNode.undo.style.display='inline-block';
				//reset the pencolor
				this.parentNode.parentNode.pad.penColor=this.parentNode.parentNode.dataset.pencolor;
			}
			wrapper.toolbar.appendChild(wrapper.toolbar.clear);
		}
	},
	initWhiteboard:function(){
		//@reference https://stackoverflow.com/questions/3008635/html5-canvas-element-multiple-layers
		let list=document.querySelectorAll('textarea[data-behavior="whiteboard"]');
		for(let i=0;i<list.length;i++){
			if(undefined != list[i].dataset.initialized){
				continue;
			}
			list[i].dataset.initialized+=1;
			list[i].style.display='none';
			//wrapper
			let wrapper = document.createElement('div');
			wrapper.style.width = list[i].style.width;
			wrapper.style.height = list[i].style.height;
			wrapper.style.display='block';
			wrapper.style.position='relative';
			wrapper.style.boxShadow='rgba(0, 0, 0, 0.16) 0px 3px 6px, rgba(0, 0, 0, 0.23) 0px 3px 6px';
			wrapper.id='whiteboard_wrapper_1';
			wrapper.style.backgroundColor=list[i].dataset.fill||'#fff';
			wrapper.shapesBottom=new Array();
			wrapper.shapesTop=new Array();
			wrapper.shape={};
			wrapper.drawShapesBottom=function(){
				//clear canvas
				this.bottom_canvas.ctx.clearRect(0, 0, this.bottom_canvas.width, this.bottom_canvas.height);
		    	this.txtarea.innerText='';
		    	let dshapes=this.shapesBottom;
		    	//draw shapes
		    	let simg=undefined;
				for(let i=0;i<dshapes.length;i++){
					let shape=dshapes[i];
					if(undefined == shape){continue;}
					if(undefined == shape.shape){continue;}
					switch(shape.shape){
						case 'image':
							simg = new Image();
					        //drawing of the test image - img1
					        simg.bottom_canvas=this.bottom_canvas;
					        simg.txtarea=this.txtarea;
					        simg.crossOrigin = 'anonymous';
					        simg.src = shape.src;
						break;
						case 'circle':
							//circle fields: shape,x,y,radius,fillcolor
							this.bottom_canvas.ctx.beginPath();
				            this.bottom_canvas.ctx.arc(shape.x,shape.y,shape.radius,0,Math.PI*2);
				       		
				            if(shape.fillcolor.length){
				            	this.bottom_canvas.ctx.fillStyle=shape.fillcolor;
				            	this.bottom_canvas.ctx.lineWidth=shape.size;
				            	this.bottom_canvas.ctx.fill();	
				            }
				            else{
				            	//no fill
				            	this.bottom_canvas.ctx.lineWidth=shape.size;
				            	this.bottom_canvas.ctx.strokeStyle=shape.pencolor;
				            	this.bottom_canvas.ctx.stroke();
				            }
						break;
						case 'rectangle':
							//rectangle fields: shape,x,y,width,fillcolor
							if(shape.fillcolor.length){
				            	this.bottom_canvas.ctx.fillStyle=shape.fillcolor;
				            	this.bottom_canvas.ctx.lineWidth=shape.size;
	            				this.bottom_canvas.ctx.fillRect(shape.x,shape.y,shape.width,shape.height);	
				            }
				            else{
				            	//no fill
				            	this.bottom_canvas.ctx.strokeStyle=shape.pencolor;
				            	this.bottom_canvas.ctx.lineWidth=shape.size;
				            	this.bottom_canvas.ctx.strokeRect(shape.x,shape.y,shape.width,shape.height);
				            }
						break;
						case 'line':
							//pencil fields: shape,x,y,x2,y2,pencolor
							this.bottom_canvas.ctx.beginPath();
							this.bottom_canvas.ctx.moveTo(shape.x, shape.y);
							this.bottom_canvas.ctx.strokeStyle=shape.pencolor;
			            	this.bottom_canvas.ctx.lineTo(shape.x2, shape.y2);
			            	this.bottom_canvas.ctx.lineWidth=shape.size;
							this.bottom_canvas.ctx.stroke();
							this.bottom_canvas.ctx.closePath();
						break;
						default: //pencil is the default
							//pencil fields: shape,x,y,x2,y2,pencolor
							//console.log(shape);
							this.bottom_canvas.ctx.beginPath();
							this.bottom_canvas.ctx.moveTo(shape.x, shape.y);
							this.bottom_canvas.ctx.strokeStyle=shape.pencolor;
							this.bottom_canvas.ctx.lineWidth=shape.size;
			            	this.bottom_canvas.ctx.lineTo(shape.x2, shape.y2);
			            	
							this.bottom_canvas.ctx.stroke();
							this.bottom_canvas.ctx.closePath();
						break;
					}
				}
				dshapes=[];
				
				if (typeof this.bottom_canvas.toDataURL === 'function') {
					if(simg){
						simg.onload = function () {
				            //draw background image
				            this.bottom_canvas.ctx.drawImage(this, 0, 0);
				            this.txtarea.innerText=this.bottom_canvas.toDataURL('image/png');
				            //console.log('saved 1');
				        };
					}
					else{
						this.txtarea.innerText=this.bottom_canvas.toDataURL('image/png');
						//console.log('saved 2');
					}
				}
				else{
					//console.log('NOT saved');
				}
			}
			wrapper.drawShapesTop=function(){
				//clear canvas
				//this.top_canvas.ctx.clearRect(0, 0, this.bottom_canvas.width, this.bottom_canvas.height);
		    	let dshapes=new Array();
		    	dshapes.push(this.shape);
		    	//console.log(dshapes);
		    	//draw shapes
		    	let simg=undefined;
				for(let i=0;i<dshapes.length;i++){
					let shape=dshapes[i];
					if(undefined == shape){continue;}
					if(undefined == shape.shape){continue;}
					switch(shape.shape){
						case 'image':
							simg = new Image();
					        //drawing of the test image - img1
					        simg.top_canvas=this.top_canvas;
					        simg.txtarea=this.txtarea;
					        simg.crossOrigin = 'anonymous';
					        simg.src = shape.src;
						break;
						case 'circle':
							//circle fields: shape,x,y,radius,fillcolor
							this.top_canvas.ctx.beginPath();
				            this.top_canvas.ctx.arc(shape.x,shape.y,shape.radius,0,Math.PI*2);
				       		
				            if(shape.fillcolor.length){
				            	this.top_canvas.ctx.fillStyle=shape.fillcolor;
				            	this.top_canvas.ctx.lineWidth=shape.size;
				            	this.top_canvas.ctx.fill();	
				            }
				            else{
				            	//no fill
				            	this.top_canvas.ctx.lineWidth=shape.size;
				            	this.top_canvas.ctx.strokeStyle=shape.pencolor;
				            	this.top_canvas.ctx.stroke();
				            }
						break;
						case 'rectangle':
							//rectangle fields: shape,x,y,width,fillcolor
							if(shape.fillcolor.length){
				            	this.top_canvas.ctx.fillStyle=shape.fillcolor;
				            	this.top_canvas.ctx.lineWidth=shape.size;
	            				this.top_canvas.ctx.fillRect(shape.x,shape.y,shape.width,shape.height);	
				            }
				            else{
				            	//no fill
				            	this.top_canvas.ctx.strokeStyle=shape.pencolor;
				            	this.top_canvas.ctx.lineWidth=shape.size;
				            	this.top_canvas.ctx.strokeRect(shape.x,shape.y,shape.width,shape.height);
				            }
						break;
						case 'line':
							//pencil fields: shape,x,y,x2,y2,pencolor
							this.top_canvas.ctx.beginPath();
							this.top_canvas.ctx.moveTo(shape.x, shape.y);
							this.top_canvas.ctx.strokeStyle=shape.pencolor;
			            	this.top_canvas.ctx.lineTo(shape.x2, shape.y2);
			            	this.top_canvas.ctx.lineWidth=shape.size;
							this.top_canvas.ctx.stroke();
							this.top_canvas.ctx.closePath();
						break;
						default: //pencil is the default
							//pencil fields: shape,x,y,x2,y2,pencolor
							this.top_canvas.ctx.beginPath();
							this.top_canvas.ctx.moveTo(shape.x, shape.y);
							this.top_canvas.ctx.strokeStyle=shape.pencolor;
			            	this.top_canvas.ctx.lineTo(shape.x2, shape.y2);
			            	this.top_canvas.ctx.lineWidth=shape.size;
							this.top_canvas.ctx.stroke();
							this.top_canvas.ctx.closePath();
						break;
					}
				}
				dshapes=[];
				
				if (typeof this.top_canvas.toDataURL === 'function') {
					if(simg){
						simg.onload = function () {
				            //draw background image
				            this.top_canvastop_canvas.ctx.drawImage(this, 0, 0);
				            this.txtarea.innerText=this.top_canvas.toDataURL('image/png');
				            //console.log('saved 1');
				        };
					}
					else{
						this.txtarea.innerText=this.top_canvas.toDataURL('image/png');
						//console.log('saved 2');
					}
				}
				else{
					//console.log('NOT saved');
				}
			}
			//put wrapper in the DOM
			list[i].parentNode.insertBefore(wrapper, list[i].nextSibling);
			//bottom_canvas for the base
			wrapper.bottom_canvas = document.createElement('canvas');
			wrapper.bottom_canvas.style.zIndex=5000;
			//top_canvas for the top (temp)
			wrapper.top_canvas = document.createElement('canvas');
			wrapper.top_canvas.style.zIndex=6000;
			//toolbar
			let params={};
			let toolbar = document.createElement('div');
			toolbar.style='position:absolute;bottom:0px;left:0px;display:flex;justify-content:flex-end;align-items:center;width:100%;background:#f0f0f0;height:34px;box-shadow: rgba(17, 17, 26, 0.05) 0px 1px 0px, rgba(17, 17, 26, 0.1) 0px 0px 8px;';
			//toolbar.shape - pencil,line,circle,rectangle - default to pencil
			wrapper.dataset.shape='pencil';
			let shapes={
				'pencil':'Pencil',
				'line':'Line',
				'circle':'Circle',
				'rectangle':'Rectangle',
			};
			params={style:'margin-left:10px;width:100px;padding:3px;'}
			toolbar.shape=wacss.buildFormSelect('shape',shapes,params);
			toolbar.shape.onchange=function(){
				let shape=this.options[this.selectedIndex].value;
				this.parentNode.parentNode.dataset.shape=shape;
			};
			toolbar.shape.title="Shape";
			toolbar.appendChild(toolbar.shape);
			//toolbar.size - default to 1
			wrapper.dataset.size='1';
			let sizes={
				'1':'1px',
				'3':'3px',
				'5':'5px',
				'7':'7px',
				'9':'9px',
				'11':'11px',
				'13':'13px',
				'15':'15px',
				'17':'17px',
				'19':'19px'
			};
			params={style:'margin-left:10px;width:100px;padding:3px;'}
			toolbar.size=wacss.buildFormSelect('size',sizes,params);
			toolbar.size.onchange=function(){
				let size=this.options[this.selectedIndex].value;
				this.parentNode.parentNode.dataset.size=size;
			};
			toolbar.size.title="Pen Size";
			toolbar.appendChild(toolbar.size);	
			//toolbar.pencolor - default to black
			wrapper.dataset.pencolor='#000';
			let pencolors={
				'#000':'Black',
				'#213a9a':'Blue',
				'#05abff':'Light Blue',
				'#05a04d':'Green',
				'#66d81f':'Light Green',
				'#ff0081':'Pink',
				'#f16115':'Orange',
				'#f43940':'Red',
				'#fee213':'Yellow'
			};
			params={style:'margin-left:10px;width:100px;padding:3px;'}
			toolbar.pencolor=wacss.buildFormSelect('pencolor',pencolors,params);
			toolbar.pencolor.onchange=function(){
				let pencolor=this.options[this.selectedIndex].value;
				this.parentNode.parentNode.dataset.pencolor=pencolor;
			};
			toolbar.pencolor.title="Pen Color";
			toolbar.appendChild(toolbar.pencolor);
			//toolbar.fillcolor - default to none
			let fillcolors={
				'':'None',
				'#000':'Black',
				'#213a9a':'Blue',
				'#05abff':'Light Blue',
				'#05a04d':'Green',
				'#66d81f':'Light Green',
				'#ff0081':'Pink',
				'#f16115':'Orange',
				'#f43940':'Red',
				'#fee213':'Yellow'
			};
			wrapper.dataset.fillcolor='';
			params={style:'margin-left:10px;width:100px;padding:3px;'}
			toolbar.fillcolor=wacss.buildFormSelect('fillcolor',fillcolors,params);
			toolbar.fillcolor.onchange=function(){
				let fillcolor=this.options[this.selectedIndex].value;
				this.parentNode.parentNode.dataset.fillcolor=fillcolor;
			};
			toolbar.fillcolor.title="Fill Color";
			toolbar.appendChild(toolbar.fillcolor);
			//toolbar.clear
			toolbar.clear=document.createElement('span');
			toolbar.clear.className='icon-erase w_pointer';
			toolbar.clear.setAttribute('style','margin-left:10px;margin-right:10px;');
			toolbar.clear.title='Erase Whiteboard';
			toolbar.appendChild(toolbar.clear);
			toolbar.clear.onclick=function(e){
				if(!confirm('Erase Whiteboard?')){return false;}
				this.parentNode.parentNode.bottom_canvas.ctx.clearRect(0, 0, this.parentNode.bottom_canvas.width, this.parentNode.bottom_canvas.height);
		    	this.parentNode.parentNode.txtarea.innerText='';
		    	this.parentNode.parentNode.shapes=new Array();
			};
			wrapper.txtarea=list[i];
			wrapper.ro = new ResizeObserver(entries => {
				for (let entry of entries) {
					if(undefined != entry.target.bottom_canvas && undefined!=entry.target.clientWidth){
						entry.target.bottom_canvas.setAttribute('width',entry.target.clientWidth);
						entry.target.bottom_canvas.setAttribute('height',parseInt(entry.target.clientHeight)-30);
						entry.target.top_canvas.setAttribute('width',entry.target.clientWidth);
						entry.target.top_canvas.setAttribute('height',parseInt(entry.target.clientHeight)-30);
					}
			 	}
			});
			wrapper.ro.observe(wrapper);
		    // Fill Window Width and Height
			wrapper.bottom_canvas.style.position='absolute';
			wrapper.top_canvas.style.position='absolute';
			wrapper.top_canvas.style.cursor='crosshair';
			

			//console.log(wcanvas);
			// context (ctx)
			wrapper.bottom_canvas.ctx = wrapper.bottom_canvas.getContext("2d");
			wrapper.top_canvas.ctx = wrapper.top_canvas.getContext("2d");
			//load image?
			if(list[i].innerHTML.length){
		        let wshape={
		        	shape:'image',
		        	src:list[i].innerHTML
		        }
		        wrapper.shapesBottom.push(wshape);
		        wrapper.drawShapesBottom();
			}
			
		    // Mouse Event Handlers
			wrapper.isDown = false;
			wrapper.top_canvas.onmousedown = function(e){
				e = e || window.event;
				let rect = e.target.getBoundingClientRect();
			    this.x = parseInt(e.pageX - rect.left); //x position within the element.
			    this.y = parseInt(e.pageY - rect.top);  //y position within the element.
				this.parentNode.isDown = true;
				switch(this.parentNode.dataset.shape){
					default: //pencil is the default
						this.parentNode.shape={
							shape:'pencil',
							x:this.x,
							y:this.y,
							pencolor:this.parentNode.dataset.pencolor,
							size:this.parentNode.dataset.size
						};
					break;
					case 'line':
						this.parentNode.shape={
							shape:'line',
							x:this.x,
							y:this.y,
							pencolor:this.parentNode.dataset.pencolor,
							size:this.parentNode.dataset.size
						};
					break;
					case 'circle':
						//circle
						//circle fields: shape,x,y,radius,fillcolor
						this.parentNode.shape={
							shape:'circle',
							x:this.x,
							y:this.y,
							pencolor:this.parentNode.dataset.pencolor,
							size:this.parentNode.dataset.size
						};
			            if(this.parentNode.dataset.fillcolor.length){
			            	this.parentNode.shape.fillcolor=this.parentNode.dataset.fillcolor;	
			            }
			            else{
			            	//no fill
			            	this.parentNode.shape.fillcolor='';
			            }
					break;
					case 'rectangle':
						this.parentNode.shape={
							shape:'rectangle',
							x:this.x,
							y:this.y,
							pencolor:this.parentNode.dataset.pencolor,
							size:this.parentNode.dataset.size
						};
			            if(this.parentNode.dataset.fillcolor.length){
			            	this.parentNode.shape.fillcolor=this.parentNode.dataset.fillcolor;	
			            }
			            else{
			            	//no fill
			            	this.parentNode.shape.fillcolor='';
			            }
					break;
				}
				//console.log(this.shape);
			};
			wrapper.top_canvas.onmousemove=function(e){
				e = e || window.event;
				if(this.parentNode.isDown !== false) {
					let rect = e.target.getBoundingClientRect();
					let x = parseInt(e.pageX - rect.left); //x position within the element.
				    let y = parseInt(e.pageY - rect.top);  //y position within the element.

				    switch(this.parentNode.dataset.shape){
						default: //pencil is the default
							this.parentNode.shape.x2=x;
							this.parentNode.shape.y2=y;
				    		this.parentNode.shapesTop.push(this.parentNode.shape);
				    		this.parentNode.shape={
								shape:'pencil',
								x:x,
								y:y,
								pencolor:this.parentNode.dataset.pencolor
							};
							//console.log(this.parentNode.shapes);
						break;
						case 'line':
							this.parentNode.shape.x2=x;
				    		this.parentNode.shape.y2=y;
						break;
						case 'circle':
							let r1=Math.abs(x-this.parentNode.shape.x);
							let r2=Math.abs(y-this.parentNode.shape.y);
							if(r1 > r2){this.parentNode.shape.radius=r1;}
							else{this.parentNode.shape.radius=r2;}
						break;
						case 'rectangle':
							this.parentNode.shape.width=Math.abs(x-this.parentNode.shape.x);
							this.parentNode.shape.height=Math.abs(y-this.parentNode.shape.y);
						break;
					}
					this.parentNode.drawShapesTop();
				}
				
			};
			wrapper.top_canvas.onmouseup=function(e){
				e = e || window.event;
				this.parentNode.isDown = false;
				switch(this.parentNode.dataset.shape){
					default: //pencil is the default
						this.parentNode.shapesTop.push(this.shape);
					break;
					case 'line':
					break;
					case 'circle':
						this.parentNode.shapesTop.push(this.shape);
					break;
					case 'rectangle':
						this.parentNode.shapesTop.push(this.shape);
					break;
				}
				this.parentNode.drawShapesTop();
			};
			wrapper.top_canvas.onmouseout=function(e){
				e = e || window.event;
				this.parentNode.isDown = false;
				switch(this.parentNode.dataset.shape){
					default: //pencil is the default
						
					break;
					case 'line':
					break;
					case 'circle':
					break;
					case 'rectangle':
					break;
				}
			}
			// Disable Page Move
			document.body.addEventListener('touchmove',function(e){
				e = e || window.event;
				e.preventDefault();
			},false);
			wrapper.appendChild(wrapper.bottom_canvas);
			wrapper.appendChild(wrapper.top_canvas);
			wrapper.appendChild(toolbar);
		}
	},
	/**
	* @name wacss.isMobile
	* @describe return true if device is a mobile device
	* @return boolean
	* @usage if(wacss.isMobile()){...}
	*/
	isMobile: function() {
		let check = false;
  		(function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))) check = true;})(navigator.userAgent||navigator.vendor||window.opera);
  		return check;
	},
	/**
	* @name wacss.idDate
	* @describe returns true if string is a date
	* @param str string  - the date string to check
	* @return boolean
	* @usage if(isDate(str)){...}
	*/
	isDate: function(str){
		let d = new Date(str.replace(/-/g, "/"));
		if ( Object.prototype.toString.call(d) === "[object Date]" ) {
	  		// it is a date
	  		if ( isNaN( d.getTime() ) ) {  // d.valueOf() could also work
	    		// date is not valid
	    		return false;
	  		}
	  		else {
	    		// date is valid
	    		return true;
	  		}
		}
		else {
	  		// not a date
	  		return false;
		}
	},
	/**
	* @name wacss.isFutureDate
	* @describe returns true if string is a date and is in the future
	* @param str string  - the date string to check
	* @return boolean
	* @usage if(isFutureDate(str)){...}
	*/
	isFutureDate: function(str){
		let d = new Date(str.replace(/-/g, "/"));
		if ( Object.prototype.toString.call(d) === "[object Date]" ) {
	  		// it is a date
	  		if ( isNaN( d.getTime() ) ) {  // d.valueOf() could also work
	    		// date is not valid
	    		return false;
	  		}
	  		else {
	    		// date is valid
	    		let today = new Date();
    			if((today-d)<0){return true;}
    			return false;
	  		}
		}
		else {
	  		// not a date
	  		return false;
		}
	},
	/**
	* @name wacss.isDST
	* @describe return true if Daylight Savings Time
	* @return boolean
	* @usage if(wacss.isDST()){...}
	*/
	isDST: function(){
		let today = new Date;
		let yr = today.getFullYear();
		// 2nd Sunday in March can't occur after the 14th
		let dst_start = new Date("March 14, "+yr+" 02:00:00");
		// 1st Sunday in November can't occur after the 7th
		let dst_end = new Date("November 07, "+yr+" 02:00:00");
		let day = dst_start.getDay(); // day of week of 14th
		dst_start.setDate(14-day); // Calculate 2nd Sunday in March of this year
		day = dst_end.getDay(); // day of the week of 7th
		dst_end.setDate(7-day); // Calculate first Sunday in November of this year
		if (today >= dst_start && today < dst_end){
			//does today fall inside of DST period?
			return true; //if so then return true
		}
		else{
			return false; //if not then return false
		}
	},
	/**
	* @name wacss.isPastDate
	* @describe returns true if string is a date and is in the past
	* @param str string  - the date string to check
	* @return boolean
	* @usage if(isPastDate(str)){...}
	*/
	isPastDate: function(str){
		let d = new Date(str.replace(/-/g, "/"));
		if ( Object.prototype.toString.call(d) === "[object Date]" ) {
	  		// it is a date
	  		if ( isNaN( d.getTime() ) ) {  // d.valueOf() could also work
	    		// date is not valid
	    		return false;
	  		}
	  		else {
	    		// date is valid
	    		let today = new Date();
    			if((today-d)>0){return true;}
    			return false;
	  		}
		}
		else {
	  		// not a date
	  		return false;
		}
	},
	/**
	* @name wacss.isFirefox
	* @describe return true if browser is Firefox
	* @return boolean
	* @usage if(wacss.isFirefox()){...}
	*/
	isFirefox: function(){
		let agt=navigator.userAgent.toLowerCase();
		return (agt.indexOf('firefox')!=-1);
	},
	/**
	* @name wacss.isOpera
	* @describe return true if browser is Opera
	* @return boolean
	* @usage if(wacss.isOpera()){...}
	*/
	isOpera: function(){
		let agt=navigator.userAgent.toLowerCase();
		return agt.indexOf("opera")!=-1;
	},
	/**
	* @name wacss.isIE
	* @describe return true if browser is Internet Explorer
	* @return boolean
	* @usage if(wacss.isIE()){...}
	*/
	isIE: function(){
		let agt=navigator.userAgent.toLowerCase();
		return agt.indexOf("msie")!=-1 && !commonIsOpera;
	},
	/**
	* @name wacss.isMobileOrTablet
	* @describe return true if device is a mobile or a tablet device
	* @return boolean
	* @usage if(wacss.isMobileOrTablet()){...}
	*/
	isMobileOrTablet: function() {
		let check = false;
  		(function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino|android|ipad|playbook|silk/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))) check = true;})(navigator.userAgent||navigator.vendor||window.opera);
  		return check;
	},
	/**
	* @name wacss.isNum
	* @describe return true if n is a number
	* @params n mixed
	* @return boolean
	* @usage if(wacss.isNum(x)){...}
	*/
	isNum: function(n) {
	  return !isNaN(parseFloat(n)) && isFinite(n);
	},
	/**
	* @name wacss.latlon
	* @describe returns [latitude,longitude,accuracy,trycount] into input
	* @params n mixed
	* @return boolean
	* @usage if(wacss.isNum(x)){...}
	*/
	latlon: function(inp,ico){
		inp=wacss.getObject(inp);
		ico=wacss.getObject(ico);
		if(undefined==inp || undefined==ico){return false;}
		ico.class_orig=ico.className;
	    inp.color_orig=inp.style.borderColor;
	    inp.style.borderColor='red';
	    ico.className='icon-spin5 w_spin';
	    if (navigator.geolocation) {
	        const latlonId=navigator.geolocation.watchPosition(
	        function(position) {
	            if(undefined==inp.dataset.counter){inp.dataset.counter=0;}
	            inp.dataset.counter=parseInt(inp.dataset.counter)+1;
	            //console.log('Try: '+inp.dataset.counter);
	            //console.log(inp.dataset);
	            let latitude = position.coords.latitude;
	            let longitude = position.coords.longitude;
	            let accuracy = position.coords.accuracy;
	            inp.value='['+position.coords.latitude+','+position.coords.longitude+','+position.coords.accuracy+','+inp.dataset.counter+']';
	            ico.title='try: '+inp.dataset.counter+', Accuracy: '+position.coords.accuracy;
	            //console.log('isMobile:');
	            //console.log(!wacss.isMobile());
	            if(!wacss.isMobile() || parseInt(position.coords.accuracy) <= 10 || inp.dataset.counter > 5){
	              	ico.className=ico.class_orig;
	              	inp.style.borderColor=inp.color_orig;
	              	window.navigator.geolocation.clearWatch(latlonId);
	              	//console.log('latlon set');
	              	//console.log(position.coords);
	              	return false;
	            }
	        },
	        function error(msg){
	          	ico.className=ico.class_orig;
	          	inp.style.borderColor=inp.color_orig;
	          	window.navigator.geolocation.clearWatch(latlonId);
	          	ico.title=ico.title+' - ERROR: '+msg;
	          	//console.log('latlon error');
	          	//console.log(this);
	          	return false;
	        },
	        {maximumAge:500, timeout:10000, enableHighAccuracy: true});
	    } else {
	    	ico.className=ico.class_orig;
	        inp.style.borderColor=inp.color_orig;
	        ico.title=ico.title+' - ERROR: '+"Geolocation API is not supported in your browser. Unable to set LatLon";
	        console.log(ico.title);
	    }
	    return false;
	},
	listen: function(evnt, elem, func) {
	    if (elem.addEventListener){ 
	    	// W3C DOM
	    	elem.addEventListener(evnt,func,false);
	    }  
	    else if (elem.attachEvent) { 
	    	// IE DOM
	         let r = elem.attachEvent("on"+evnt, func);
	         return r;
	    }
	    else{
	    	console.log('wacss.listen failed. Browser does not support event listeners');
	    }
	},
	loadCSS: function(file,notify) {
	    let link = document.createElement('link');
	    if(undefined != notify && notify==1){
	    	link.onload = function () {
		    //do stuff with the script
		    wacss.toast(this.getAttribute('href')+' loaded successfully');
			};
	    }
	    link.setAttribute("rel", "stylesheet");
  		link.setAttribute("type", "text/css");
  		link.setAttribute("href", file);
		document.head.appendChild(link);
		return true;
	},
	loadJs: function(file,notify) {
		return wacss.loadScript(file,notify);
	},
	loadScript: function(file,func) {
	    let script = document.createElement('script');
	    script.src = file;
	    //for backwards compatibility, check to see if func=1, if so toast 
	    if(undefined != func){
	    	script.func=func;
	    	script.onload = function () {
	    		if(parseInt(this.func)==1){
		    		wacss.toast(this.src+' loaded successfully');
				}
				else if(wacss.function_exists(func)){
					window[func](script.src);
				}
			}
	    }
		document.head.appendChild(script);
		return true;
	},
	log: function(m){
		if (typeof console != 'undefined' && typeof console.log != 'undefined'){
			console.log(m);
		}
	},
	makeMovable: function(obj,hdr) {
		obj=wacss.getObject(obj);
		hdr=wacss.getObject(hdr);
		obj.pos1 = 0;
		obj.pos2 = 0;
		obj.pos3 = 0;
		obj.pos4 = 0;
		if (undefined != hdr) {
			//the header is where you move the DIV from:
			hdr.onmousedown = makeMovableMouseDown;
			hdr.style.cursor='move';
		} else {
		// otherwise, move the DIV from anywhere inside the DIV:
			obj.onmousedown = makeMovableMouseDown;
			obj.style.cursor='move';
		}
  		function makeMovableMouseDown(e) {
			e = e || window.event;
			e.preventDefault();
			// get the mouse cursor position at startup:
			obj.pos3 = e.clientX;
			obj.pos4 = e.clientY;
			document.onmouseup = makeMovableStop;
			// call a function whenever the cursor moves:
			document.onmousemove = makeMovableDrag;
  		}
		function makeMovableDrag(e) {
			e = e || window.event;
			e.preventDefault();
			// calculate the new cursor position:
			obj.pos1 = obj.pos3 - e.clientX;
			obj.pos2 = obj.pos4 - e.clientY;
			obj.pos3 = e.clientX;
			obj.pos4 = e.clientY;
			// set the element's new position:
			obj.style.top = (obj.offsetTop - obj.pos2) + "px";
			obj.style.left = (obj.offsetLeft - obj.pos1) + "px";
		}
		function makeMovableStop() {
			// stop moving when mouse button is released:
			document.onmouseup = null;
			document.onmousemove = null;
		}
	},
	/**
	* @name wacss.mobileHideAddressBar
	* @describe hides the address bar so the page looks like an app
	* @return boolean
	* @usage if(x){wacss.mobileHideAddressBar();}
	*/
	mobileHideAddressBar: function(){
		return window.scrollto(0,1);
	},
	/**
	* @name wacss.modalClose
	* @describe closes the modal window generated by an ajax call
	* @return boolean
	* @usage if(x){wacss.modalClose();}
	*/
	modalClose: function(){
		if(undefined != document.getElementById('wacss_modal_overlay')){
			return wacss.removeObj(document.getElementById('wacss_modal_overlay'));
		}
		else if(undefined != document.getElementById('wacss_modal')){
			return wacss.removeObj(document.getElementById('wacss_modal'));
		}
	},
	/**
	* @name wacss.modalClose
	* @describe sets modal title
	* @return boolean
	* @usage wacss.modalTitle('test');
	*/
	modalTitle: function(title){
		if(undefined != document.getElementById('wacss_modal')){
			let m=document.getElementById('wacss_modal');
			let mt=m.querySelector('.wacss_modal_title_text');
			if(undefined != mt){
				mt.innerHTML=title;
			}
			wacss.centerObject(m);
			return m;
		}
	},
	modalPopupId: function(id,title,params){
		let htm='';
		if(undefined != document.querySelector(id)){
			htm=document.querySelector(id).innerHTML;
		}
		else if(undefined != document.querySelector('#'+id)){
			htm=document.querySelector('#'+id).innerHTML;
		}
		return wacss.modalPopup(htm,title,params);
	},
	modalPopup: function(htm,title,params){
		if(undefined == params){params={overlay:1};}
		if(undefined == title){title='&nbsp;';}
		if(undefined == htm){htm='';}
		//check for id: or qs:
		if(htm.indexOf('id:') == 0 && htm.length < 100){
			let cid=htm.slice(3);
			let cidobj=document.querySelector('#'+cid);
			if(undefined != cidobj){
				htm=cidobj.innerHTML;
			}
		}
		else if(htm.indexOf('qs:') == 0 && htm.length < 100){
			let cid=htm.slice(3);
			let cidobj=document.querySelector(cid);
			if(undefined != cidobj){
				htm=cidobj.innerHTML;
			}
		}
		if(undefined != document.getElementById('wacss_modal')){
			let m=document.getElementById('wacss_modal');
			let mel=m.querySelector('.wacss_modal_content');
			if(undefined != mel){
				mel.innerHTML=htm;
			}
			if(title.length > 0){
				let mt=m.querySelector('.wacss_modal_title_text');
				if(undefined != mt){
					mt.innerHTML=title;
				}
			}
			wacss.centerObject(m);
			return m;
		}
		if(undefined != document.getElementById('wacss_modal_overlay_close')){
			params.overlay_close=1;
		}

		if(undefined == params.color){
			params.color=wacss.color();
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
				wacss.removeObj(this.pnode);
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
		modal_content.id='wacss_modal_content';
		modal_content.innerHTML=htm;
		modal.appendChild(modal_content);
		if(undefined != params.overlay){
			let modal_overlay=document.createElement('div');
			modal_overlay.id='wacss_modal_overlay';
			modal_overlay.className='wacss_modal_overlay '+params.color;
			//set the height to the full height
			//modal_overlay.style.height=wacss.documentHeight()+'px';
			modal_overlay.style.height='100%';
			modal_overlay.appendChild(modal);
			modal_close.pnode=modal_overlay;
			if(undefined != params.overlay_close){
				modal_overlay.onclick = function(){
					//get the element where the click happened using hover
					let elements = document.querySelectorAll(':hover');
					let i=elements.length-1;
					if(this == elements[i]){
						wacss.removeObj(this);	
					}
				};
			}
			else{
				modal_overlay.onclick = function(){
					//get the element where the click happened using hover
					let elements = document.querySelectorAll(':hover');
					let i=elements.length-1;
					if(this == elements[i]){
						wacss.blink('wacss_modal');	
					}
				};
			}
			document.body.appendChild(modal_overlay);
		}
		else{
			modal_close.pnode=modal;
			document.body.appendChild(modal);
		}
		modal.setAttribute('data-position','initial');
		wacss.centerObject(modal);
		return modal;
	},
	/**
	* @name wacss.nav
	* @describe navigation based on data-nav and data-div
	* @param el element (this)
	* @param opts object additional instructions not in dataset
	* @return string placed in element specified by data-div
	* @usage <a href="#" data-nav="/t/1/index/test" data-div="centerpop" onclick="return wacss.nav(this);">test</a>
	*/
	nav: function(el,opts){
		//check to make sure that el has data-nav
		let elobj=wacss.getObject(el);
		if(undefined == elobj){
			console.log('invalid object passed to wacss.nav');
			console.log(el);
			return false;
		}
		//stop propigation
		if(window.event && undefined != el.dataset.nav){
			window.event.stopImmediatePropagation();
		}
		//get parent
		let ptr=wacss.getParent(elobj,'tr');
		if(undefined==ptr || undefined==ptr.dataset){
			ptr=new Object;
			ptr.dataset={};
			ptr.dataset.div=null;
			ptr.dataset.confirm=null;
			ptr.dataset.nav=null;
			ptr.dataset.sp=null;
			ptr.dataset.title=null;
		}
		let ptd=wacss.getParent(elobj,'td');
		if(undefined==ptd || undefined==ptd.dataset){
			ptd=new Object;
			ptd.dataset={};
			ptd.dataset.div=null;
			ptd.dataset.confirm=null;
			ptd.dataset.nav=null;
			ptd.dataset.sp=null;
			ptd.dataset.title=null;
		}
		let pli=wacss.getParent(elobj,'li');
		if(undefined==pli || undefined==pli.dataset){
			pli=new Object;
			pli.dataset={};
			pli.dataset.div=null;
			pli.dataset.confirm=null;
			pli.dataset.nav=null;
			pli.dataset.sp=null;
			pli.dataset.title=null;
		}
		let pul=wacss.getParent(elobj,'ul');
		if(undefined==pul || undefined==pul.dataset){
			pul=new Object;
			pul.dataset={};
			pul.dataset.div=null;
			pul.dataset.confirm=null;
			pul.dataset.nav=null;
			pul.dataset.sp=null;
			pul.dataset.title=null;
		}
		if(undefined==opts){opts={};}
		//find the div 
		let div=opts.div || elobj.dataset.div || pli.dataset.div || pul.dataset.div || ptd.dataset.div || ptr.dataset.div || 'main_content';
		//console.log('wacss.nav - div: '+div);
		//confirm?
		let has_confirm=opts.confirm || elobj.dataset.confirm || pli.dataset.confirm || pul.dataset.confirm || ptd.dataset.confirm || ptr.dataset.confirm;
		if(undefined != has_confirm && has_confirm.length > 0){
			let txt=has_confirm.replace(/\[newline\]/g,wacss.EOL);
			if(txt.indexOf('id:') == 0){
				let cid=txt.replace('id:','');
				let cidobj=document.querySelector('#'+cid);
				if(undefined != cidobj){
					txt=cidobj.innerText;
				}
			}

			if(!confirm(txt)){return false;}
		}
		if(undefined == opts){opts={};}
		if(undefined != elobj.dataset.tab){
			wacss.setActiveTab(elobj);
		}
		if(undefined != elobj.dataset.collapse){
			let div_content=div.innerHTML;
			if(div_content.length > 10){
				div.innerHTML='';
				return false;
			}
		}
		let params={};
		//checkbox
		let checkbox=elobj.dataset.checkbox || pli.dataset.checkbox || pul.dataset.checkbox || ptd.dataset.checkbox || ptr.dataset.checkbox;
		if(undefined != checkbox){
			let elsearch='input[type="checkbox"][name="'+checkbox+'[]"]:checked';
			let checkboxes=document.querySelectorAll(elsearch);
			params.checkboxes=new Array();
			for(let c=0;c<checkboxes.length;c++){
				params.checkboxes.push(checkboxes[c].value);
			}
		}
		if(elobj.type=='checkbox' && undefined != elobj.name){
			if(elobj.checked){params[elobj.name]=elobj.value;}
			else{params[elobj.name]=0;}
		}
		//load key/values from a form?
		let frm=elobj.dataset.form || pli.dataset.form || pul.dataset.form || ptd.dataset.form || ptr.dataset.form;
		if(undefined != frm){
			frm=wacss.getObject(frm);
			if(undefined != frm){
				let els=frm.querySelectorAll('input[name],select[name]');
				for(let i=0;i<els.length;i++){
					switch(els[i].type){
						case 'checkbox':
							if(els[i].checked){
								if(undefined == params[els[i].name]){
									params[els[i].name]=new Array();
								}
								params[els[i].name].push(els[i].value);
							}
						break;
						case 'radio':
							if(els[i].checked){
								params[els[i].name]=els[i].value;
							}
						break;
						default:
							params[els[i].name]=els[i].value;
						break;
					}
				}
			}
		}
		if(undefined != document.searchfiltersform){
			let filters=document.searchfiltersform._filters.innerHTML;
			if(undefined != filters && filters.length){
				params.searchfilters=filters.replace(/\r?\n/g, ";");
			}
			if(undefined != document.searchfiltersform.filter_order && document.searchfiltersform.filter_order.value.length){
				params.searchsort=document.searchfiltersform.filter_order.value;
			}
			if(undefined != document.searchfiltersform.filter_offset && document.searchfiltersform.filter_offset.value.length && !isNaN(document.searchfiltersform.filter_offset.value)){
				params.searchoffset=document.searchfiltersform.filter_offset.value;
			}
		}
		if(undefined != document.tailform){
			let els=document.tailform.querySelectorAll('input[name],select[name]');
			for(let i=0;i<els.length;i++){
				params[els[i].name]=els[i].value;
			}
		}
		if(undefined != ptr.dataset){
			for(k in ptr.dataset){
				params[k]=ptr.dataset[k];
			}
		}
		if(undefined != ptd.dataset){
			for(k in ptd.dataset){
				params[k]=ptd.dataset[k];
			}
		}
		//nav
		let nav=opts.nav || elobj.dataset.nav || pli.dataset.nav || pul.dataset.nav || ptd.dataset.nav || ptr.dataset.nav || elobj.getAttribute('href');
		//console.log('wacss.nav - nav: '+nav);
		//scrollto
		let scrollto=opts.scrollto || elobj.dataset.scrollto || pli.dataset.scrollto || pul.dataset.scrollto || ptd.dataset.scrollto || ptr.dataset.scrollto || '';
		//handle data-nav id: for cases where we are not doing ajax
		if(nav.indexOf('id:') == 0){
			let cid=nav.replace('id:','');
			let cidobj=document.querySelector('#'+cid);
			if(undefined != cidobj){
				let content=cidobj.value || cidobj.innerHTML || cidobj.innerText || '';
				document.querySelector('#'+div).innerHTML=content;
				if(scrollto=='bottom'){
					console.log('scrolling to bottom');
					document.querySelector('#'+div).scrollTop=document.querySelector('#'+div).scrollHeight;
				}
				return false;
			}
		}
		if(nav=='id' && undefined != elobj.dataset.parent){
			let target=wacss.getObject(div);
			let ptarget=wacss.getObject(elobj.dataset.parent);
			for(let c=0;c<ptarget.childNodes.length;c++){
				if(undefined==ptarget.childNodes[c].id){continue;}
				ptarget.childNodes[c].style.display='none';
			}
			target.style.display=target.dataset.display || 'block';
			return false;
		}
		//sp - setprocessing
		let sp=opts.sp || elobj.dataset.sp || pli.dataset.sp || pul.dataset.sp || ptd.dataset.sp || ptr.dataset.sp || document.querySelector('#setprocessing');
		if(undefined != sp){
			params.setprocessing=sp;
		}
		//prompt
		let pmpt=opts.prompt || elobj.dataset.prompt || pli.dataset.prompt || pul.dataset.prompt || ptd.dataset.prompt || ptr.dataset.prompt || '';
		if(undefined != pmpt && pmpt.length > 0){
			let pmpt_default=opts.prompt_default || elobj.dataset.prompt_default || pli.dataset.prompt_default || pul.dataset.prompt_default || ptd.dataset.prompt_default || ptr.dataset.prompt_default || '';
			params.prompt=prompt(pmpt,pmpt_default);
			if(undefined==params.prompt || params.prompt.length==0){
				return false;
			}
		}
		let title=opts.title || elobj.dataset.title || pli.dataset.title || pul.dataset.title || ptd.dataset.title || ptr.dataset.title;
		if(undefined != title){
			params.title=title;
			params.cp_title=title;
		}
		for(k in elobj.dataset){
			if(k=='nav'){continue;}
			if(k=='confirm'){continue;}
			if(k=='div'){continue;}
			if(k=='sp'){continue;}
			if(k=='title'){continue;}
			if(k=='prompt'){continue;}
			if(elobj.dataset[k].indexOf('id:') == 0){
				let cid=elobj.dataset[k].replace('id:','qs:#');
				elobj.dataset[k]=cid;
			}
			if(elobj.dataset[k].indexOf('qs:') == 0){
				let cid=elobj.dataset[k].replace('qs:','');
				let cidobjs=document.querySelectorAll(cid);
				let cidvals=new Array();
				let cidv='';
				for(let c=0;c<cidobjs.length;c++){
					let cidobj=cidobjs[c];
					let type='';
					if(undefined != cidobj.nodeName){
						switch(cidobj.nodeName.toLowerCase()){
							case 'input':
								type=cidobj.getAttribute('type');		
							break;
							case 'select':
								type='select';
							break;
							case 'textarea':
								type='textarea';
							break;
						}	
					}
					switch(type){
						case 'checkbox':
						case 'radio':
							//only included checked values for radio and checkboxes
							if(cidobj.checked){
								cidv=cidobj.value || 1;
								cidvals.push(cidv);
							}
						break;
						case 'select':
							//for select lists support multi-select
							for (let j=0; j<cidobj.options.length; j++) {
								if (cidobj.options[j].selected) {
									cidv=cidobj.options[cidobj.selectedIndex].value;
									cidvals.push(cidv);
								}
							}
							
						break;
						case 'textarea':
							//get the innerHTML of textareas
							cidv=cidobj.innerText;
							cidvals.push(cidv);
						break;
						case 'text':
						case 'hidden':
							//get the value of text and hidden inputs
							cidv=cidobj.value;
							cidvals.push(cidv);
						break;
						default:
							cidv=cidobj.value || cidobj.innerHTML || cidobj.innerText;
							cidvals.push(cidv);
						break;
					}
				}
				params[k]=wacss.implode(',',cidvals);
			}
			else{
				params[k]=elobj.dataset[k];
			}
		}
		//override params with opts if passed in
		for(k in opts){
			if(opts[k].length==0){continue;}
			params[k]=opts[k];
		}
		
		let url=opts.url || elobj.dataset.url || pli.dataset.url || ptd.dataset.url || ptr.dataset.url;
		if(url){
			params.url=url;
			params.title=title;
			document.title=params.title;
		}
		//if div is "window", pop up a new window.
		if(div=='window'){
			if(undefined==title || title.length==0){
				title='Print';
			}
			let w=params.width||600;
			let h=params.height||400;
			// Fixes dual-screen position                             Most browsers      Firefox
		    let dualScreenLeft = window.screenLeft !==  undefined ? window.screenLeft : window.screenX;
		    let dualScreenTop = window.screenTop !==  undefined   ? window.screenTop  : window.screenY;

		    let width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
		    let height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

		    let systemZoom = width / window.screen.availWidth;
		    let left = (width - w) / 2 / systemZoom + dualScreenLeft
		    let top = (height - h) / 2 / systemZoom + dualScreenTop
		    
			let winWidth=parseInt(w/systemZoom);
			let winHeight=parseInt(h/systemZoom);

			let pairs=new Array();
			for(k in params){
				if(k=='nav'){continue;}
				if(k=='confirm'){continue;}
				if(k=='div'){continue;}
				if(k=='sp'){continue;}
				if(k=='title'){continue;}
				if(k=='prompt'){continue;}
				pairs.push(k+'='+params[k]);
			}
			if(pairs.length){
				let pairstr=pairs.join('&');
				nav=nav+'?'+pairstr;	
			}
			
			let win = window.open(nav,title,'width='+winWidth+',height='+winHeight+',left='+left+',top='+top+',menubar=no,toolbar=no,location=no,scrollbars=yes');
			return false;
		}
		return wacss.ajaxGet(nav,div,params);

	},
	navMobileToggle: function(el){
		let navs=document.querySelectorAll('.nav');
		for(let n=0;n<navs.length;n++){
			let lis=navs[n].querySelectorAll('li');
			for(let l=0;l<lis.length;l++){
				if(lis[l]==el){
					/* this  is the right nav */
					if(navs[n].className.indexOf('leftmenu') != -1){
						wacss.removeClass(navs[n],'leftmenu');	
					}
					else{
						wacss.addClass(navs[n],'leftmenu');
					}
				}
			}
		}
		return false;
	},
	/**
	* @name wacss.post
	* @describe sends a request to the specified url from a form with params as inputs
	* @param string form action
	* @param json key/value pairs
	* @return boolean false
	* @usage wacss.post('/yourpage',{age:15,color:'blue'});
	*/
	post:function(path, params, method='post') {
		// The rest of this code assumes you are not using a library.
		// It can be made less verbose if you use one.
		let form = document.createElement('form');
		form.method = method;
		form.action = path;

		for (let key in params) {
			if (params.hasOwnProperty(key)) {
				let hiddenField = document.createElement('input');
				hiddenField.type = 'hidden';
				hiddenField.name = key;
				hiddenField.value = params[key];
				form.appendChild(hiddenField);
			}
		}
		document.body.appendChild(form);
		form.submit();
		form.remove();
		return false;
	},
	preventDefault: function(evt){
		evt = evt || window.event;
		if (evt.preventDefault){evt.preventDefault();}
		if (evt.stopPropagation){evt.stopPropagation();}
	 	if (evt.cancelBubble !== null){evt.cancelBubble = true;}
	},
	/**
	* @name wacss.registerTouchEvent
	* @describe called specified function(callback) on touch(swipe) event
	* @describe callback will receive a string left/right/up/down/click/diagonal and the startEvent and endEvent
	* @param callback function
	* @param [element] defaults to document
	* @return boolean false
	* @usage wacss.registerTouchEvent(mySwipeEvent);
	*/
	registerTouchEvent: function(callback,element) {
		if(undefined==element){element=document;}
		let startEvent;
		element.addEventListener('touchstart', ev => startEvent = ev);
		element.addEventListener('touchend', endEvent => {
			if (!startEvent.changedTouches || !endEvent.changedTouches){return;}
			let THRESHOLD = 50
			let start = startEvent.changedTouches[0];
			let end = endEvent.changedTouches[0];
			if (!start || !end) return;

			let horizontalDifference = start.screenX - end.screenX;
			let verticalDifference = start.screenY - end.screenY;
			let horizontal = Math.abs(horizontalDifference) > Math.abs(verticalDifference) && Math.abs(verticalDifference) < THRESHOLD;
			let vertical = !horizontal && Math.abs(horizontalDifference) < THRESHOLD;

			let direction = 'diagonal';
			if (horizontal){direction = horizontalDifference >= THRESHOLD ? 'left' : (horizontalDifference <= -THRESHOLD ? 'right' : 'click');}
			if (vertical){direction = verticalDifference >= THRESHOLD ? 'up' : (verticalDifference <= -THRESHOLD ? 'down' : 'click');}

			callback(direction, startEvent, endEvent);
		});
		return true;
	},
	removeClass: function(element, classToRemove) {
		element=wacss.getObject(element);
		if(undefined == element.className){return;}
	    let currentClassValue = element.className;

	    // removing a class value when there is more than one class value present
	    // and the class you want to remove is not the first one
	    if (currentClassValue.indexOf(" " + classToRemove) != -1) {
	        element.className = element.className.replace(" " + classToRemove, "");
	        return;
	    }

	    // removing the first class value when there is more than one class value present
	    if (currentClassValue.indexOf(classToRemove + " ") != -1) {
	        element.className = element.className.replace(classToRemove + " ", "");
	        return;
	    }

	    // removing the first class value when there is only one class value present
	    if (currentClassValue.indexOf(classToRemove) != -1) {
	        element.className = element.className.replace(classToRemove, "");
	        return;
	    }
	},
	/**
	* @name wacss.removeId
	* @describe removes specified object from the DOM
	* @param string divid
	* @return boolean
	* @usage wacss.removeId('centerpop');
	*/
	removeId: function(divid){
		//info: removes specified id
		let obj=wacss.getObject(divid);
		return wacss.removeObj(obj);
	},
	/**
	* @name wacss.removeObj
	* @describe removes specified object from the DOM
	* @param object DOM element to remove
	* @return boolean
	* @usage wacss.removeObj(document.querySelector('#centerpop'));
	*/
	removeObj: function(obj){
		//info: removes specified id
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
	},
	/**
	* @name wacss.rgbToHex
	* @describe converts rgb values string to a hex value
	* @param string rgb
	* @return string HEX value
	* @usage let hex=wacss.rgbToHex(255,255,255);
	*/
	rgbToHex:function(rgb) {
		let result = rgb.match(/\d+/g);
		function hex(x) {
			let digits = new Array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "A", "B", "C", "D", "E", "F");
			return isNaN(x) ? "00" : digits[(x - x % 16 ) / 16] + digits[x % 16];
		}
		return "#" + hex(result[0]) + hex(result[1]) + hex(result[2]);
	},
	/**
	* @name wacss.scrollIntoView
	* @describe scrolls the page so that specified element is in view
	* @param mixed DOM element or id of element
	* @param object params defaults to {behavior: 'smooth', block: 'center', inline: 'center'}
	*        behavior Defines the transition animation -  auto or smooth
	*        block Defines vertical alignment - start, center, end, or nearest
	*        inline Defines horizontal alignment - start, center, end or nearest
	* @return boolean false
	* @usage wacss.scrollIntoView('#myelement');
	* @usage wacss.scrollIntoView(document.querySelector('#myelement'));
	*/
	scrollIntoView:function(el,p){
		el=wacss.getObject(el);
		if(undefined == el){return false;}
		let params={behavior: 'smooth', block: 'center', inline: 'center'};
		//allow override
		if(undefined==p){
			for(k in p){
				params[k]=p[k];
			}
		}
		el.scrollIntoView(params);
		let iw=document.getElementById('image_wrapper');
		return false;
	},
	/**
	* @name wacss.scrollToBottom
	* @describe converts rgb values string to a hex value
	* @param mixed DOM element or id of element
	* @return boolean false
	* @usage wacss.scrollToBottom('#myelement');
	* @usage wacss.scrollToBottom(document.querySelector('#myelement'));
	*/
	scrollToBottom: function(el){
		el=wacss.getObject(el);
		if(undefined == el){return false;}
		if(undefined == el.scrollHeight){return false;}
		if(undefined != el.clientHeight){
			el.scrollTop = el.scrollHeight - el.clientHeight;
		}
		else{
			el.scrollTop = el.scrollHeight;
		}
		return false;
	},
	/**
	* @name wacss.setActiveTab
	* @describe sets the current <ul>  <li> element to class active. Used with w_tabs
	* @param mixed DOM element or id of element
	* @return boolean false
	* @usage onclick="return wacss.setActiveTab(this);"
	*/
	setActiveTab: function(el){
		el=wacss.getObject(el);
		//get parent ul - can be either nav-tabs or nav-list
	    let p=wacss.getParent(el,'ul','nav-tabs');
	    if(p === null){
	    	p=wacss.getParent(el,'ul','nav-list')
	    }
	    if(p === null){
	    	return false;
	    }
	    //get parents li tags and unset any active class
	    let list=p.querySelectorAll('li');
	    for(let i=0;i<list.length;i++){
	        wacss.removeClass(list[i],'active');
	    }
	    //add active class to the li
	    if(el.nodeName.toLowerCase()=='li'){
	    	wacss.addClass(el,'active');	
	    }
	    else{
			let li=wacss.getParent(el,'li');
			let lip=wacss.getParent(li,'ul');
			if(!lip.classList.contains('nav-tabs')){
				wacss.addClass(li,'active');
				li=wacss.getParent(lip,'li');
			}
	    	wacss.addClass(li,'active');
	    }
	    return false;
	},
	setProcessingTimer: function(){
		//console.log('setProcessingTimer');
		if(undefined != wacss.processing_timeout){
			clearTimeout(wacss.processing_timeout);
		}
		let t=document.getElementById('processing_timer');
		if(undefined==t){return false;}
		if(undefined==t.dataset.timer){
			t.dataset.timer=0;
		}
		let seconds=parseInt(t.dataset.timer);
		const hrs = Math.floor(seconds / 3600);
	    const mins = Math.floor((seconds % 3600) / 60);
	    const secs = seconds % 60;
	    t.innerText = [hrs, mins, secs]
	        .map(v => String(v).padStart(2, '0'))
	        .join(':');
	    //console.log(t.innerText);
	    t.dataset.timer=seconds+1;
		wacss.processing_timeout=setTimeout(wacss.setProcessingTimer, 1000);
	},
	/**
	* @name wacss.setText
	* @describe sets the value of an element to specified value
	* @param el object or id
	* @params str value to set it to
	* @return boolean
	* @usage wacss.setText(divid,'');
	*/
	setText: function(obj,txt){
		let cObj=wacss.getObject(obj);
	    if(undefined == cObj){return false;}
	    let previous_value=wacss.getText(cObj);
	    let setflag=0;
	    if(undefined != cObj.tagName){
			switch(cObj.tagName.toUpperCase()){
				case 'INPUT':
					cObj.value=txt;
					setflag=1;
				break;
				case 'TEXTAREA':
					if(undefined != cObj.innerHTML){
						cObj.innerHTML=txt;
						setflag=1;	
					}
					else{
						cObj.innerText=txt;
						setflag=1;
					}
				break;
			}
		}
	    //if the object has a value attribute, set it
	    else if(undefined != cObj.getAttribute('value')){
			cObj.value=txt;
			setflag=1;
		}
		//otherwise try a few others
		if(setflag==0){
			try{
				cObj.innerHTML=txt;
				let check=wacss.getText(cObj);
				setflag=1;
			}
			catch(e){}
		}
		if(setflag==0){
			try{
				cObj.innerText=txt;
				let check=wacss.getText(cObj);
				setflag=1;
			}
			catch(e){}
		}
	    if(setflag==0){
			try{
				cObj.value=txt;
				let check=wacss.getText(cObj);
				setflag=1;
			}
			catch(e){}
		}
		if(setflag==0){return false;}
	    //check for onchange attribute and kick it off if the value changes
	    if(undefined != cObj.onchange && previous_value != txt){
			cObj.onchange();
		}
		return true;
	},
	/**
	* @name wacss.setStarRating
	* @exclude  - this function is for internal use only and thus excluded from the manual
	*/
	setStarRating:function(el){
		let p=wacss.getParent(el,'div');
		//return false if in readonly mode
		if(undefined==p){
			console.log('setStarRating is missing a div parent');
			return false;
		}
		if(undefined!=p.dataset.readonly){
			console.log('setStarRating is set to readonly');
			return false;
		}
	    let n=parseInt(el.dataset.value);
		let stars=p.querySelectorAll('span');
		//toggle first star if it is the only one marked so the user can select none
		if(n==1 && stars[0].className.indexOf('empty') == -1 && stars[1].className.indexOf('empty') != -1){
			n=0;
		}
		//set field value
		//console.log('setStarRating set to '+n);
		//console.log(stars);
		p.querySelector('input').value=n;
		for(let i=0;i<stars.length;i++){
			console.log(stars[i].classList);
			let s=parseInt(stars[i].dataset.value);
			if(s <= n){
				stars[i].classList.remove('icon-star-half-empty');
				stars[i].classList.remove('icon-star-empty');
				stars[i].classList.add('icon-star');
				//console.log(s+' star');
			}
			else{
				stars[i].classList.remove('icon-star-half-empty');
				stars[i].classList.remove('icon-star');
	            stars[i].classList.add('icon-star-empty');
				//console.log(s+' star-empty');
			}
		}
		return true;
	},
	/**
	* @name wacss.showAudio
	* @describe creates a DOM element to show audio in
	* @param mixed DOM element or id of element
	* @param number z-index defaults to 10020
	* @param string title optional title to show above audio
	* @return object DOM object that is created
	* @usage let el=wacss.showAudio('#myimg',2323)
	*/
	showAudio: function(el,z,title){
		el=wacss.getObject(el);
		if(undefined == el){return false;}
		z=z||999980;
		let d=document.createElement('div');
		d.id="modal1";
		//d.className='modal open';
		d.tabindex=0;
		d.style.zIndex=z;
		d.style.display='block';
		d.style.background='#FFF';
		d.style.padding='15px';
		d.style.border='1px outset #747392';
		d.style.borderRadius='3px';
		d.style.position='absolute';
		d.style.textAlign='center';
		d.style.maxWidth='60%';
		d.style.maxHeight='800px';
		d.style.transform='scaleX(1) scaleY(1)';
		if(undefined != title && title.length){
			let t=document.createElement('div');
			t.className='w_big w_bold align-center';
			t.innerHTML=title;
			d.appendChild(t);
		}
		let aud=document.createElement('audio');
		aud.src=el.getAttribute('src')  || el.dataset.src || el.getAttribute('href');
		aud.setAttribute('controls','');
		aud.setAttribute('autoplay','');
		aud.setAttribute('playsinline','');
		aud.style.maxWidth='100%';
		aud.style.maxHeight='770px';
		aud.style.width='200px';
		aud.style.height='50px';
		aud.d=d;
		aud.oncanplay=function(){
			wacss.centerObject(this.d);
		}
		d.appendChild(aud);
		document.body.appendChild(d);
		z=z-2;
		// Build modal-overlay.
		let v=document.createElement('div');
		v.style.zIndex=z;
		v.style.display='block';
		v.style.width='100vw';
		v.style.height=wacss.documentHeight()+'px';
		v.style.position='absolute';
		v.style.top='0px';
		v.style.left='0px';
		v.style.background='rgba(0,0,0,0.5)';
		v.id=d.id+'_overlay';
		v.setAttribute('data-target',d.id);
		v.onclick=function(){
			removeDiv(this.getAttribute('data-target'));
			removeDiv(this.id);
		};
		document.body.appendChild(v);
		wacss.centerObject(d);
		return v;
	},
	/**
	* @name wacss.showImage
	* @describe creates a DOM element to show image in
	* @param mixed DOM element or id of element
	* @param number z-index defaults to 10020
	* @param string title optional title to show above image
	* @return object DOM object that is created
	* @usage let el=wacss.showImage('#myimg',2323)
	*/
	showImage: function(el,z,title){
		el=wacss.getObject(el);
		if(undefined == el){return false;}
		z=z||999980;
		let d=document.createElement('div');
		d.id="modal1";
		//d.className='modal open';
		d.tabindex=0;
		d.style.zIndex=z;
		d.style.display='block';
		d.style.border='1px outset #747392';
		d.style.borderRadius='3px';
		d.style.position='absolute';
		d.style.textAlign='center';
		d.style.width=el.dataset.width || '50vw';
		d.style.height=el.dataset.height || '60vh';
		d.style.transform='scaleX(1) scaleY(1)';
		let imgsrc=el.getAttribute('src') || el.dataset.src || el.getAttribute('href');
		d.style.backgroundImage="URL('"+imgsrc+"')";
		d.style.backgroundRepeat='no-repeat';
		d.style.backgroundSize='contain';
		d.style.backgroundPosition='center';
		d.style.backgroundColor='#000';
		title=title||el.dataset.header||el.dataset.title||el.title||'';
		if(undefined != title && title.length){
			let t=document.createElement('div');
			t.className='w_blackback w_white w_big w_bold align-center';
			t.style.top='0px';
			t.style.display='block';
			t.style.padding='10px 15px;';
			t.style.width=el.dataset.width || '50vw';
			t.style.position='absolute';
			t.innerHTML=title;
			d.appendChild(t);
		}
		let i=document.createElement('img');
		i.src='/wfiles/clear.gif';
		i.style.maxWidth='100%';
		i.style.maxHeight='100%';
		i.d=d;
		i.onload=function(){
			wacss.centerObject(this.d);
		}
		d.appendChild(i);
		if(undefined != el.dataset.footer && el.dataset.footer.length){
			let f=document.createElement('div');
			f.className='w_blackback w_white w_big w_bold align-center';
			f.style.bottom='0px';
			f.style.display='block';
			f.style.padding='10px 15px;';
			f.style.width=el.dataset.width || '50vw';
			f.style.position='absolute';
			f.innerHTML=el.dataset.footer;
			d.appendChild(t);
		}
		document.body.appendChild(d);
		z=z-2;
		// Build modal-overlay.
		let v=document.createElement('div');
		v.style.zIndex=z;
		v.style.display='block';
		v.style.width='100vw';
		v.style.height=wacss.documentHeight()+'px';
		v.style.position='absolute';
		v.style.top='0px';
		v.style.left='0px';
		v.style.background='rgba(0,0,0,0.5)';
		v.id=d.id+'_overlay';
		v.setAttribute('data-target',d.id);
		v.onclick=function(){
			removeDiv(this.getAttribute('data-target'));
			removeDiv(this.id);
		};
		document.body.appendChild(v);
		wacss.centerObject(d);
		return v;
	},
	/**
	* @name wacss.showVideo
	* @describe creates a DOM element to show video in
	* @param mixed DOM element or id of element
	* @param number z-index defaults to 10020
	* @param string title optional title to show above video
	* @return object DOM object that is created
	* @usage let el=wacss.showImage('#myimg',2323)
	*/
	showVideo: function(el,z,title){
		el=wacss.getObject(el);
		if(undefined == el){return false;}
		z=z||999980;
		let d=document.createElement('div');
		d.id="modal1";
		//d.className='modal open';
		d.tabindex=0;
		d.style.zIndex=z;
		d.style.display='block';
		d.style.background='#FFF';
		d.style.padding='15px';
		d.style.border='1px outset #747392';
		d.style.borderRadius='3px';
		d.style.position='absolute';
		d.style.textAlign='center';
		d.style.maxWidth='60%';
		d.style.maxHeight='800px';
		d.style.transform='scaleX(1) scaleY(1)';
		if(undefined != title && title.length){
			let t=document.createElement('div');
			t.className='w_big w_bold align-center';
			t.innerHTML=title;
			d.appendChild(t);
		}
		//if video is youtube we have to add an iframe instead of a video element
		let video_src=el.getAttribute('src')  || el.dataset.src || el.getAttribute('href');
		let vid={};
		if(
			video_src.toLowerCase().indexOf('youtube') != -1 
			|| video_src.toLowerCase().indexOf('youtu.be') != -1
			){
			let code=video_src.substring(video_src.lastIndexOf('/') + 1);
			video_src='https://www.youtube.com/embed/'+code;
			vid=document.createElement('iframe');
			vid.style.maxWidth='100%';
			vid.style.maxHeight='770px';
			vid.style.width='auto';
			vid.style.height='auto';
			vid.src=video_src;
			vid.title='';
			vid.frameborder=0;
			vid.allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share";
			vid.referrerpolicy="strict-origin-when-cross-origin";
			vid.setAttribute('allowfullscreen','allowfullscreen');
		}
		else{
			vid=document.createElement('video');
			vid.src=video_src;
			vid.setAttribute('controls','');
			vid.setAttribute('autoplay','');
			vid.setAttribute('playsinline','');
			vid.style.maxWidth='100%';
			vid.style.maxHeight='770px';
			vid.style.width='auto';
			vid.style.height='auto';
			vid.d=d;
			vid.oncanplay=function(){
				wacss.centerObject(this.d);
			}
		}
		d.appendChild(vid);
		document.body.appendChild(d);
		z=z-2;
		// Build modal-overlay.
		let v=document.createElement('div');
		v.style.zIndex=z;
		v.style.display='block';
		v.style.width='100vw';
		v.style.height=wacss.documentHeight()+'px';
		v.style.position='absolute';
		v.style.top='0px';
		v.style.left='0px';
		v.style.background='rgba(0,0,0,0.5)';
		v.id=d.id+'_overlay';
		v.setAttribute('data-target',d.id);
		v.onclick=function(){
			removeDiv(this.getAttribute('data-target'));
			removeDiv(this.id);
		};
		document.body.appendChild(v);
		wacss.centerObject(d);
		return v;
	},
	/**
	* @name wacss.simulateEvent
	* @describe creates a DOM element to show image in
	* @param mixed DOM element or id of element
	* @param string event name - click,hover,mouseover,mouseout,etc
	* @return boolean
	* @usage let el=wacss.simulateEvent('#mybutton','click')
	*/
	simulateEvent: function(element, eventName){
		element = getObject(element);
	    if (element === undefined) { return false; }
	    if (eventName === 'submit' && element.tagName === 'FORM') {
	        // Check if requestSubmit is available (modern browsers)
	        if (typeof element.requestSubmit === 'function') {
	            element.requestSubmit();
	        } else {
	            // Fallback for older browsers
	            element.submit();
	        }
	    } else {
	        // For other events
	        let evObj;
	        if (document.createEvent) {
	            evObj = document.createEvent('Event');
	            evObj.initEvent(eventName, true, false);
	        } else {
	            // IE fallback
	            evObj = document.createEventObject();
	            evObj.eventType = eventName;
	        }
	        if (element.dispatchEvent) {
	            element.dispatchEvent(evObj);
	        } else {
	            // IE fallback
	            element.fireEvent('on' + eventName, evObj);
	        }
	    }
	    return true;
	},
	/**
	* @name wacss.speak
	* @param sentence string
	* @param params - json object. volume,name,lang,rate,pitch,gender
	* @return false
	* @usage wacss.speak('hello bob');
	* @usage wacss.speak('hello bob',{volume:0.3,name:'Sally',lang:'en-US'});
	* @usage wacss.speak('hello bob',{volume:0.3,gender:'male'});
	*/
	speakStop: function(vol){
		window.speechSynthesis.cancel();
		return true;
	},
	speakPause: function(vol){
		window.speechSynthesis.pause();
		return true;
	},
	speakResume: function(r){
		window.speechSynthesis.resume();
		return true;
	},
	speak: function(txt,params){
		if(undefined == params){params={};}
		params.txt=txt;
		if ('speechSynthesis' in window) {	
			/* cancel any speach already playing */
			window.speechSynthesis.cancel();
			/* check to see if voices are loaded already */
			let voices = window.speechSynthesis.getVoices();
			if(voices.length == 0){
				/* no voices loaded. Setup a promise and then call wacss.speak */
				window.speechSynthesis.params=params;
				window.speechSynthesis.onvoiceschanged = function(){
					let params=window.speechSynthesis.params;
					wacss.speak(params.txt,params);
				};
			}
			else{
				if(undefined != document.getElementById('voices_list_debug')){
					const voicesData = voices.map(voice => ({
				        name: voice.name,
				        lang: voice.lang,
				        default: voice.default,
				        localService: voice.localService,
				        voiceURI: voice.voiceURI
				    }));
				    wacss.setText('voices_list_debug', JSON.stringify(voicesData, null, 2));
				}
				let msg = new SpeechSynthesisUtterance();
				/* if params.name then pick a voice with that name */
				if(undefined != params.name){
					for(let i=0;i<voices.length;i++){
						if(voices[i].name.toLowerCase().indexOf(params.name.toLowerCase()) != -1){
							msg.voice=voices[i];
							break;
						}
					}
				}
				else if(undefined != params.gender){
					/* if params.gender then pick a voice with that gender */
					let gender=params.gender.toLowerCase();
					for(let i=0;i<voices.length;i++){
						let name = voices[i].name.toLowerCase();
						if (gender === 'male' && (
						   name.includes('male') ||
						   name.includes('daniel') ||
						   name.includes('david') ||
						   name.includes('alex') ||
						   name.includes('mark') ||
						   name.includes('tom') ||
						   name.includes('fred') ||
						   name.includes('paul') ||
						   name.includes('john') ||
						   name.includes('bob') ||
						   name.includes('man') ||
						   name.includes('masculine')
						)) {
						   msg.voice = voices[i];
						   break;
						} 
						else if (gender === 'female' && (
						   name.includes('female') ||
						   name.includes('samantha') ||
						   name.includes('victoria') ||
						   name.includes('karen') ||
						   name.includes('susan') ||
						   name.includes('mary') ||
						   name.includes('helen') ||
						   name.includes('sarah') ||
						   name.includes('anna') ||
						   name.includes('woman') ||
						   name.includes('feminine')
						)) {
						   msg.voice = voices[i];
						   break;
						}
					}
				}
				//check for lang
				if(undefined != params.lang){
					msg.lang=params.lang;
				}
				//volume - between 0 and 1
				if(undefined != params.volume){
					msg.volume=params.volume;
				}
				//rate (speed) - works best between 0.1 and 2
				if(undefined != params.rate){
					msg.rate=params.rate;
				}
				//pitch - works best between 0.1 and 2
				if(undefined != params.pitch){
					msg.pitch=params.pitch;
				}
				//event: boundary
				if(undefined != params.onboundary){
					msg.func_boundary=params.onboundary;
					msg.func_event='boundary';
					msg.addEventListener('boundary',function(){
						window[this.func_boundary](event);
					});
				}
				//to fix a bug with long texts call resume every 5 seconds
				msg.addEventListener('start',function(event){
					this.speak_timer=setInterval(wacss.speakResume, 5000);
				});
				msg.addEventListener('resume',function(event){
					this.speak_timer=setInterval(wacss.speakResume, 5000);
				});
				msg.addEventListener('end',function(event){
					clearTimeout(this.speak_timer);
				});
				msg.addEventListener('error',function(event){
					clearTimeout(this.speak_timer);
				});
				msg.addEventListener('pause',function(event){
					clearTimeout(this.speak_timer);
				});
				//event: start
				if(undefined != params.onstart){
					msg.func_start=params.onstart;
					msg.addEventListener('start',function(event){
						window[this.func_start](event.utterance);
					});
				}
				//event: end
				if(undefined != params.onend){
					msg.func_end=params.onend;
					msg.addEventListener('end',function(){
						window[this.func_end](event);
					});
				}
				//event: error
				if(undefined != params.onerror){
					msg.func_error=params.onerror;
					msg.addEventListener('error',function(){
						window[this.func_error](event.utterance);
					});
				}
				//event: mark
				if(undefined != params.onmark){
					msg.func_mark=params.onmark;
					msg.addEventListener('mark',function(){
						window[this.func_mark](event.utterance);
					});
				}
				//event: pause
				if(undefined != params.onpause){
					msg.func_pause=params.onpause;
					msg.addEventListener('pause',function(){
						window[this.func_pause](event.utterance);
					});
				}
				//event: resume
				if(undefined != params.onresume){
					msg.func_resume=params.onresume;
					msg.addEventListener('resume',function(){
						window[this.func_resume](event.utterance);
					});
				}
				
				if(params.txt.indexOf('<') != -1){
					console.log('ssml');
					msg.input={ssml:params.txt};
					window.speechSynthesis.speak(msg);
					return true;
				}
				else{
					msg.text=params.txt;
					window.speechSynthesis.speak(msg);
					return true;
				}
			}
		}
		else{
			console.log('wacss.speak error: speechSynthesis is not supported in your browser or OS');
		}
		return false;
	},
	/**
	* @name wacss.str_replace
	* @describe emulates PHP function
	* @param search string
	* @param replace string
	* @param str string
	* @return string
	* @usage let newstr=wacss.str_replace(search,replace,str);
	* @sourece https://phpjs.org/functions
	*/
	str_replace: function(search, replace, str) {
	    let f = search;
	    let r = replace;
	    let s = str;
	    let ra = r instanceof Array;
	    let sa = s instanceof Array;
	    f = [].concat(f);
	    r = [].concat(r);
	    let i = (s = [].concat(s)).length;

	    while (j = 0, i--) {
	        if (s[i]) {
	            while (s[i] = s[i].split(f[j]).join(ra ? r[j] || "" : r[0]), ++j in f){};
	        }
	    };

	    return sa ? s : s[0];
	},
	/**
	* @name wacss.strtolower
	* @describe lowercases a string - emulates PHP function
	* @param str string
	* @return string
	* @usage let uc=wacss.strtolower(str);
	* @sourece https://phpjs.org/functions
	*/
	strtolower: function(str) {
	    // info: Makes a string lowercase
	    //source: http://phpjs.org/functions
	    return (str + '').toLowerCase();
	},
	/**
	* @name wacss.strtoupper
	* @describe uppercases a string - emulates PHP trim function
	* @param str string
	* @return string
	* @usage let uc=wacss.strtoupper(str);
	* @sourece https://phpjs.org/functions
	*/
	strtoupper: function(str) {
	    // info: Makes a string uppercase
	    //source: http://phpjs.org/functions
	    return (str + '').toUpperCase();
	},
	/**
	* @name wacss.toast
	* @describe displays a toast message for 3 seconds
	* @param str string
	* @param params - json obj
	* @return false
	* @usage wacss.toast('copy successful');
	* @usage wacss.toast('copy successful',{color:'w_blue',timer:5});
	*/
	toast: function(msg,params){
		//check for qs: and id: custom message redirectors
		if(msg.indexOf('qs:')==0){
			let mid=msg.replace('qs:','');
			let midobj=document.querySelector(mid);
			if(undefined!=midobj){
				msg=midobj.innerHTML;
			}
		}
		else if(msg.indexOf('id:')==0){
			let mid=msg.replace('id:','');
			let midobj=document.querySelector('#'+mid);
			if(undefined!=midobj){
				msg=midobj.innerHTML;
			}
		}
		if(undefined == params){
			params={color:'w_green',timer:3};
		}
		if(undefined == params.color){
			params.color=wacss.color();
		}
		if(undefined == params.timer){params.timer=3000;}
		else{params.timer=parseInt(params.timer)*1000;}
		if(undefined == document.getElementById('wacss_toasts')){
			let ts = document.createElement('div');	
			ts.id='wacss_toasts';
			document.body.appendChild(ts);
		}
		
		let t = document.createElement('div');
		t.className='toast '+params.color;
		t.setAttribute('role','alert');
		t.innerHTML=msg;
		t.style.position='relative';
		t.timer=params.timer;
		//close button
		let c = document.createElement('span');
		c.className='icon-close';
		c.pnode=t;
		c.title='Close';
		c.onclick=function(){
			wacss.removeObj(this.pnode);
		};
		t.appendChild(c);
		document.getElementById('wacss_toasts').appendChild(t);
		//console.log('timer',params);
		setTimeout(function(){
			wacss.dismiss(t);
			},params.timer
		);
		return false;
	},
	toggleClass: function(id,class1,class2,myid,myclass1,myclass2){
		let obj=wacss.getObject(id);
		if(undefined == obj){return;}
		if(obj.className.indexOf(class1) != -1){
	    	wacss.removeClass(obj,class1);
	    	wacss.addClass(obj,class2);
		}
		else if(obj.className.indexOf(class2) != -1){
	    	wacss.removeClass(obj,class2);
	    	wacss.addClass(obj,class1);
		}
		else{wacss.addClass(obj,class1);}
		//a second set may be set to also modify the caller
		if(undefined != myid){
			obj=wacss.getObject(myid);
			if(undefined == obj){return;}
			if(obj.className.indexOf(myclass1) != -1){
		    	wacss.removeClass(obj,myclass1);
		    	wacss.addClass(obj,myclass2);
			}
			else if(obj.className.indexOf(myclass2) != -1){
		    	wacss.removeClass(obj,myclass2);
		    	wacss.addClass(obj,myclass1);
			}
			else{wacss.addClass(obj,myclass1);}
		}
	},
	
	/**
	* @name wacss.trim
	* @describe trims a string - emulates PHP function
	* @param str string
	* @return string
	* @usage let ucf=wacss.trim(str);
	* @sourece https://phpjs.org/functions
	*/
	trim: function(str){
		if (null != str && undefined != str && "" != str){
			let rval=str.replace(/^[\ \s\0\r\n\t]*/g,"");
			rval=rval.replace(/[\ \s\0\r\n\t]*$/g,"");
		    return rval;
			}
		else{return "";}
	},
	/**
	* @name wacss.ucfirst
	* @describe upper cases the first char of a string - emulates PHP function
	* @param str string
	* @return string
	* @usage let ucf=wacss.ucfirst(str);
	* @sourece https://phpjs.org/functions
	*/
	ucfirst: function(str) {
	    let f = str.charAt(0).toUpperCase();
	    return f + str.substr(1);
	},
	/**
	* @name wacss.ucwords
	* @describe upper cases a string - emulates PHP function
	* @param str string
	* @return string
	* @usage let uc=wacss.ucwords(str);
	*/
	ucwords: function(str){
		str = str.toLowerCase().replace(/\b[a-z]/g, function(letter) {
		    return letter.toUpperCase();
		});
		return str;
	},
	/**
	* @name wacss.urlEncode
	* @describe URL encodes a string
	* @param str string
	* @return string
	* @usage let encstr=wacss.urlEncode(str);
	*/
	urlEncode: function(str) {
		//info: URL encode string
		//usage: $encoded=urlEncode('address=122 east way');
		str=str+'';
		str=str.replace(/\//g,"%2F");
		str=str.replace(/\?/g,"%3F");
		str=str.replace(/\</g,"%3C");
		str=str.replace(/\>/g,"%3E");
		str=str.replace(/\"/g,"%22");
		str=str.replace(/=/g,"%3D");
		str=str.replace(/&/g,"%26");
		str=str.replace(/\#/g,"%23");
		//str=str.replace(/\s/g,"+");
	    return str;
	},
	verboseSize: function(bytes) {
	  var i = -1;
	  var byteUnits = [' kB', ' MB', ' GB', ' TB', 'PB', 'EB', 'ZB', 'YB'];
	  do {
	    bytes /= 1024;
	    i++;
	  } while (bytes > 1024);

	  return Math.max(bytes, 0.1).toFixed(1) + byteUnits[i];
	},
	/**
	* @name wacss.verboseTime
	* @describe converts a number representing seconds into a string that describes how long
	* @param s number
	* @return string
	* @usage console.log(wacss.verboseTime(12321));
	*/
	verboseTime: function(s){
		if(isNaN(s)){return s;}
		let secs = parseInt(s, 10);
		let years = Math.floor(secs / (3600*24*365));
		secs  -= years*3600*24*365;
		let months = Math.floor(secs / (3600*24*30));
		secs  -= months*3600*24*30;
		let days = Math.floor(secs / (3600*24));
		secs  -= days*3600*24;
		let hrs   = Math.floor(secs / 3600);
		secs  -= hrs*3600;
		let mins = Math.floor(secs / 60);
		secs  -= mins*60;
		let parts=new Array();
		if(years > 0){parts.push(years+' years');}
		if(months > 0){parts.push(months+' months');}
		if(days > 0){parts.push(days+' days');}
		if(hrs > 0){parts.push(hrs+' hrs');}
		if(mins > 0){parts.push(mins+' mins');}
		parts.push(secs+' secs');
		return wacss.implode(' ',parts);
	},
	/**
	* @exclude  - this function is for internal use only and thus excluded from the manual
	*/
	wacsseditHandleFiles(el){
		for(let f=0;f<el.files.length;f++){
			let reader = new FileReader();
			reader.filebox=el.filebox;
			reader.filename=el.files[f].name;
			reader.filesize=el.files[f].size;
			reader.filetype=el.files[f].type;
		    reader.onload = function(){
		    	if(this.filetype.toLowerCase().indexOf('image') == 0){
		    		let dataURL = this.result;
			      	let img = document.createElement('img');
			      	img.src = dataURL;
			      	img.style.width='32px';
			      	img.style.height='32px';
			      	img.style.margin='5px';
			      	img.title=this.filename;
			      	this.filebox.appendChild(img);	
		    	}
		    	else if(this.filetype.toLowerCase().indexOf('audio') == 0){
		    		let dataURL = this.result;
			      	let aud = document.createElement('audio');
			      	aud.src = dataURL;
			      	aud.controls = true;
			      	aud.title=this.filename;
			      	aud.style.maxHeight='100px';
			      	aud.style.maxWidth='150px';
			      	this.filebox.appendChild(aud);	
		    	}
		    	else if(this.filetype.toLowerCase().indexOf('video') == 0){
		    		let dataURL = this.result;
			      	let vid = document.createElement('video');
			      	vid.src = dataURL;
			      	vid.controls = true;
			      	vid.title=this.filename;
			      	vid.style.maxHeight='100px';
			      	vid.style.maxWidth='150px';
			      	this.filebox.appendChild(vid);	
		    	}
		    	
		    };
		    reader.readAsDataURL(el.files[f]);
		}
	}
}
wacss.listen('load',window,function(){wacss.init();});