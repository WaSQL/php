/* form-based, get, post, ajax javascript routines*/
/* - Required dependancies: common.js 			 */
/*----------------------------------------------*/
/** attachDropFiles **/
/** https://thiscouldbebetter.wordpress.com/2013/07/03/converting-a-file-to-a-base64-dataurl-in-javascript/ **/
function attachDropFiles(fld){
	var filesSelected = fld.files;
	if (filesSelected.length == 0){return;}
	//get the parent form
	var parentForm=getParentForm(fld);
	if(undefined == parentForm){return;}
	var pname=parentForm.getAttribute('name');
	if(undefined == pname){pname=parentForm.getAttribute('id');}
	if(undefined == pname){pname='noname';}
	//check to see if we should allow multiple files
	var allow_multiple=false;
	if(undefined != fld.getAttribute('multiple')){
		allow_multiple=true;
	}
	for(var i=0;i<filesSelected.length;i++){
		var fileToLoad = filesSelected[i];
		var fileReader = new FileReader();
		if(allow_multiple){fileReader.cname=fld.name+'_base64[]';}
		else{fileReader.cname=fld.name+'_base64';}
		fileReader.cid=pname+'_'+fld.name+'_base64';
		fileReader.allow_multiple=allow_multiple;
		fileReader.fname=fileToLoad.name;
		fileReader.fid=pname+'_'+fld.name+'_base64_filename';
		fileReader.onload = function(fileLoadedEvent){
			if(this.allow_multiple){
				var txtarea = document.createElement("textarea");
				txtarea.name=this.cname;
				txtarea.id=this.cid;
				txtarea.setAttribute('data-filename',this.fname);
				txtarea.innerHTML = 'filename:'+this.fname+';'+fileLoadedEvent.target.result;
				txtarea.style.display='none';
				parentForm.appendChild(txtarea);
			}
			else{
				var txtobj=getObject(this.cid);
				if(undefined == txtobj){
                	txtarea = document.createElement("textarea");
                	txtarea.name=this.cname;
					txtarea.id=this.cid;
					txtarea.style.display='none';
					parentForm.appendChild(txtarea);
				}
				txtarea.setAttribute('data-filename',this.fname)
				txtarea.innerHTML = 'filename:'+this.fname+';'+fileLoadedEvent.target.result;
			}
		};
		fileReader.readAsDataURL(fileToLoad);
	}
}
/*----------------------------------------------*/
function autoFill(theForm,answerid,ro){
	if(undefined == ro){
		//default to not read only
		ro=0;
        }
    //get the answers - 
	var answers=new Array();
	var slist=GetElementsByAttribute('div', 'id', answerid);
	for(i=0;i<slist.length;i++){
		var ename=slist[i].getAttribute('name');
		if(ename!='null'){
			var val=getText(slist[i]);
			answers[ename]=val;
			}
        }
    for(var i=0;i<theForm.length;i++){
		var etype=theForm[i].type;
		var ename=theForm[i].getAttribute('name');
		var aname=ename.replace(/\[/,"");
		aname=aname.replace(/\]/,"");
		var answer=answers[aname];
		if(ro){
			//set to read only mode
			theForm[i].style.backgroundColor='#FFFFFF';
			theForm[i].style.color='#000000';
			theForm[i].style.border='1px solid #7f9db9';
			if(etype == 'textarea'){
				theForm[i].readOnly=true;
				}
			else if(etype == 'text'){
				theForm[i].readOnly=true;
				}
			else{theForm[i].disabled=true;}
			}
		//alert('form:name='+ename+', aname='+aname+', answer='+answers[aname]);
		if(undefined != answers[aname]){
			if(etype == 'checkbox'){
				var vals=answers[aname].split(':');
				var evalue=theForm[i].getAttribute('value');
				for(s=0;s<vals.length;s++){
					if(evalue == vals[s]){theForm[i].checked=true;}
                    }
                }
			else{
				theForm[i].value=answers[aname];
				}
			}
		}
    }
//--------------------------
function ajaxAddEditForm(table,id,flds,userparams){
	var params='_action=editform&-table='+table;
	var xtitle='Add New Record';
	if(undefined != id && id > 0){
		params += '&_id='+id;
		xtitle='Edit Record';
		}
	if(undefined != flds){params += '&-fields='+flds;}
	if(undefined != userparams){params += '&'+userparams;}
	ajaxPopup('/php/index.php',params,{id:'centerpop'});
	return false;
	}
//--------------------------
function autoGrow(box,maxheight) {
	//info: allows a textbox to grow as a person types
	//usage: <textarea onkeypress="autoGrow();"><textarea>
	if(undefined==maxheight){maxheight=400;}
	if (box.scrollHeight < maxheight && box.scrollHeight > box.clientHeight && !window.opera){box.style.height=box.scrollHeight+'px';}
	}
//--------------------------
function comboCompleteMatch (sText, arrValues) {
	sText=sText.toLowerCase();
	for (var i=0; i < arrValues.length; i++) {
		aval=arrValues[i].toLowerCase();
		if (aval.indexOf(sText) == 0) {
    		return i;
			}
   		}
	return -1;
	}
function comboComplete(oTextbox, oEvent, vid) {
	var comboSelectionText=getText(vid);
	//alert(comboSelectionText);
	var selectionLines=comboSelectionText.split("\n");
	var arrayValues=new Array();
	for(var i=0;i<selectionLines.length;i++){
		if(undefined == selectionLines[i]){continue;}
		var cline=trim(selectionLines[i]);
		if(cline.length ==0){continue;}
		var matches = cline.match(/tval=\"(.+?)\"/ig);
		//showProperties(matches,null,1);
		if(undefined == matches){continue;}
		for(m in matches){
			var cmatch=matches[m]+'';
			if(cmatch.indexOf('tval="') != -1){
				var val=cmatch.replace('tval="','');
				val=val.replace(/\"$/,'');
				arrayValues.push(val);
				}
			}
    	}
	//showProperties(arrayValues,null,1);
	var code=parseInt(oEvent.keyCode)
	switch (code) {
		case 38: //up arrow
			if(undefined != oTextbox.getAttribute('last_index')){
				var mi=parseInt(oTextbox.getAttribute('last_index'));
				if(mi > 0){
					mi=mi-1;
					if(undefined != arrayValues[mi]){
						oTextbox.value = arrayValues[mi].replace('&amp;','&');;
						oTextbox.setAttribute('last_index',mi);
    					textboxSelect(oTextbox, iLen, oTextbox.value.length);
    					//window.status='M1:'+arrayValues.length+','+mi+','+oEvent.keyCode;
    					return false;

						}
					}
            	}
            break;
		case 40: //down arrow
		//window.status='M2-A:'+arrayValues.length+','+mi+','+oEvent.keyCode;
		if(undefined != oTextbox.getAttribute('last_index')){
				var mi=parseInt(oTextbox.getAttribute('last_index'));
				//window.status='M2-B:'+arrayValues.length+','+mi+','+oEvent.keyCode;
				if(mi < arrayValues.length){
					mi=mi+1;
					if(undefined != arrayValues[mi]){
						oTextbox.value = arrayValues[mi].replace('&amp;','&');;
						oTextbox.setAttribute('last_index',mi);
    					textboxSelect(oTextbox, iLen, oTextbox.value.length);
    					//window.status='M2:'+arrayValues.length+','+mi+','+oEvent.keyCode;
    					return false;
    					break;
						}
					}
            	}
            break;
		case 37: //left arrow
		case 39: //right arrow
		case 33: //page up
		case 34: //page down
		case 36: //home
		case 35: //end
		case 13: //enter
		case 9: //tab
		case 27: //esc
		case 16: //shift
		case 17: //ctrl
		case 18: //alt
		case 20: //caps lock
		case 8: //backspace
		case 46: //delete
			//window.status='Keycode:'+arrayValues.length+','+oEvent.keyCode;
			return true;
			break;
		default:
			textboxReplaceSelect(oTextbox, String.fromCharCode(isIE?oEvent.keyCode:oEvent.charCode));
			var iLen = oTextbox.value.length;
			var mi = comboCompleteMatch(oTextbox.value, arrayValues);
			if (mi > -1) {
				oTextbox.value = arrayValues[mi].replace('&amp;','&');;
				oTextbox.setAttribute('last_index',mi);
    			textboxSelect(oTextbox, iLen, oTextbox.value.length);
    			//window.status='M4:'+arrayValues.length+','+mi+','+oEvent.keyCode;
				}
			return false;
   		}
	}
//--------------------------
function formSetMultiSelectStatus(fld){
	var group=fld.getAttribute('data-group');
	var list=GetElementsByAttribute('input','data-group',group);
	var spans=GetElementsByAttribute('span','data-group',group);
	var vals=new Array();
	for(var i=0;i<list.length;i++){
		//skip if no value
		if(undefined == list[i].value){continue;}
		if(list[i].value==''){continue;}
    	if(list[i].checked){vals.push(list[i].value);}
	}
	var cname=vals.length?'icon-checkbox':'icon-checkbox-empty';
	for(var i=0;i<spans.length;i++){
		spans[i].className=cname;
	}
	if(fld.checked){fld.checked=true;}
	return true;
}
//--------------------------
//fielddataChange(this);
function fielddataChange(fld){
	var parentObj=getParentForm(fld);
	var pname=parentObj.name;
	var fname=fld.name;
	var val=fld.value;
	var requiredObj=parentObj["required"];
	switch(fname){
    	case 'inputtype':
    		switch(val){
            	case 'slider':
            		setText(pname+'_height_dname','Min');
            		setText(pname+'_inputmax_dname','Max');
            		setText(pname+'_required_dname','Step');
            		requiredObj.type='text';
            		requiredObj.style.width='25px';
            	break;
            	default:
            		setText(pname+'_height_dname','Height');
            		setText(pname+'_inputmax_dname','Inputmax');
            		setText(pname+'_required_dname','Required');
            		requiredObj.type='checkbox';
            		requiredObj.style.width='';
            	break;
			}
    	break;
	}
	//showProperties(parentObj);
}
//--------------------------
function filemanagerEdit(id,page,param){
	//build an html for for changing the name and description of file
	var obj=getObject(id);
	var fname=obj.getAttribute('filename');
	var desc=getText(obj);
	var htm='';
	htm += '<form class="w_form" method="post" action="/'+page+'" onSubmit="return submitForm(this);">'+"\n";
	htm += '<table class="w_table w_nopad">'+"\n";
	htm += '<tr><th>Name</th><td><input type="text" name="file_name" value="'+fname+'" style="width:300px;"></td></tr>'+"\n";
	htm += '<tr><th>Desc</th><td><textarea name="file_desc" style="width:300px;height:60;" onkeypress="autoGrow(this,200);">'+desc+'</textarea></td></tr>'+"\n";
	htm += '<tr><td align="right" colspan="2"><input type="submit" value="Save Changes"></td></tr>'+"\n";
	htm += '</table>'+"\n";
	if(param){
		htm += '<div style="display:none" id="params">'+"\n";
		for (var key in param){
			htm += '<textarea name="'+key+'">'+param[key]+'</textarea>'+"\n";
			}
		htm += '</div>'+"\n";
		}
	htm += '</form><br />'+"\n";
	//alert(htm);
	popUpDiv(htm,{title:'filemanager Edit',drag:1,center:'x',y:'-100',width:350});
	return false;
	}
//--------------------------
function formatPhone(phone){
 	var newphone=phone;
	newphone=newphone.replace(/\(/g, '');
	newphone=newphone.replace(/\)/g, '');
	newphone=newphone.replace(/\-/g, '');
	newphone=newphone.replace(/[a-zA-z]/g, '');
	newphone=newphone.replace(/\s+/g, '')
	var one=newphone.substr(0, 3);
	var two=newphone.substr(3, 3);
	var three=newphone.substr(6, 4);
	newphone=one;
	if(two.length > 0){newphone += '-'+two;}
	if(three.length > 0){newphone += '-'+three;}
	return newphone;
}
//--------------------------
function formatCalendar(str){
 	var newstr=str;
	newstr=newstr.replace(/\(/g, '');
	newstr=newstr.replace(/\)/g, '');
	newstr=newstr.replace(/\-/g, '');
	newstr=newstr.replace(/[a-zA-z]/g, '');
	newstr=newstr.replace(/\s+/g, '')
	var one=newstr.substr(0, 2);
	var two=newstr.substr(2, 2);
	var three=newstr.substr(4, 4);
	newstr=one;
	if(two.length > 0){newstr += '-'+two;}
	if(three.length > 0){newstr += '-'+three;}
	return newstr;
}
//--------------------------
function getElementForm(what){
	//info: returns the parent form of a form element
	//usage: var frm=getElementForm(this);
	while(what && undefined != what.nodeName && what.nodeName!='FORM'){what=what.parentNode;}
	return what;
}
//--------------------------
function dynamicSelect(fld,v,p){
	//info: allows a user to enter "other" values in a select box
	//usage: <select name="color" onChange="dynamicSelect(this,'other')>
	if(undefined == fld){return false;}
	if(undefined == v){v='other';}
	var dname=fld.name;
	if(fld.getAttribute('data-displayname')){dname=fld.getAttribute('data-displayname');}
	else if(fld.getAttribute('displayname')){dname=fld.getAttribute('displayname');}
	if(undefined == p){p='Enter '+v+' "'+dname+'" below and click OK.';}
	if(fld.value.toLowerCase()==v.toLowerCase()){
		var other=prompt(p);
		if(undefined != other && other.length > 0){
			for(var i=0;i<fld.options.length;i++){
				if(fld.options[i].value == other){
					fld.value=fld.options[i].value;
					return false;
                	}
            	}
			fld.options[fld.length]=new Option(other.toLowerCase(),other);
			fld.value=other;
        	}
    	}
    return false;
	}
//--------------------------
var timer_heartbeat='';
function scheduleHeartbeat(div,t){
	//info: schedules a heartbeat every t seconds (600 default)
	//info: this keeps sessions from timing out
	if(undefined == div){div='null';}
	if(undefined == t){t=600;}
	var tmo=parseInt(t*1000);
	clearTimeout(timer_heartbeat);
	timer_heartbeat = setTimeout("heartbeat('"+div+"',"+t+")",tmo);
	}
function heartbeat(div,t){
	//alert(div+','+t);
	//return false;
	if(undefined == div){div='null';}
	if(undefined == t){t=60;}
	ajaxGet('/php/index.php',div,'_heartbeat=1&t='+t+'&div='+div);
	}

//--------------------------
var timer_session_errors='';
function scheduleSessionErrors(div,t){
	//info: schedules a heartbeat every t seconds (600 default)
	//info: this keeps sessions from timing out
	if(undefined == div){div='null';}
	if(undefined == t){t=600;}
	var tmo=parseInt(t*1000);
	clearTimeout(timer_heartbeat);
	timer_session_errors = setTimeout("sessionErrors('"+div+"',"+t+")",tmo);
	}
function sessionErrors(div,t){
	//alert(div+','+t);
	//return false;
	if(undefined == div){div='null';}
	if(undefined == t){t=60;}
	ajaxGet('/php/admin.php',div,'_menu=session_errors&t='+t+'&div='+div);
	}
//--------------------------
function redrawField(fieldname,srcObj){
	//usage: redrawField('states',this);
	var theForm=getElementForm(srcObj);
	var formname=theForm.getAttribute('name');
	var divid=formname+'_'+fieldname+'_content';
	var tablename=theForm["_table"].value;
	var redraw=tablename+':'+fieldname;
	var params={_redraw:redraw,opt_0:srcObj.name,val_0:srcObj.value};
	//add attributes of the field we are redrawing
	var node=theForm[fieldname];
	for(var i=0; i<node.attributes.length; i++){
        if(node.attributes.item(i).specified){
            params['att_'+node.attributes.item(i).nodeName]=node.attributes.item(i).value;
		}
	}
	//_redraw=_users:states&opt_0=country&val_0=CA
	ajaxGet('/php/index.php',divid,params);
	return false;
}
//--------------------------
function changeModelType(srcObj){
	var theForm=getElementForm(srcObj);
	var content='';
	if(undefined != theForm["_id"]){
    	//edit mode - do nothing if it is not blank
    	content=getText(theForm["functions"]);
    	if(content.length > 0){return;}
	}
	var tablename=theForm["name"].value;
	content='';
	if(srcObj.value==0){
    	//stub out class
	}
	else{
    	//stub out functions
    	content +='<?php'+"\r\n";
    	//Documentation
		content +='/* Available functions: Each function should return the array that was passed in.'+"\r\n";
		content +=''+"\r\n";
		content +='	//addDBRecord triggers'+"\r\n";
		content +='	function '+tablename+'AddBefore($params=array()){}'+"\r\n";
		content +='	function '+tablename+'AddSuccess($params=array()){}'+"\r\n";
		content +='	function '+tablename+'AddFailure($params=array()){}'+"\r\n";
		content +=''+"\r\n";
		content +='	//editDBRecord triggers'+"\r\n";
		content +='	function '+tablename+'EditBefore($params=array()){}'+"\r\n";
		content +='	function '+tablename+'EditSuccess($params=array()){}'+"\r\n";
		content +='	function '+tablename+'EditFailure($params=array()){}'+"\r\n";
		content +=''+"\r\n";
		content +='	//delDBRecord triggers'+"\r\n";
		content +='	function '+tablename+'DeleteBefore($params=array()){}'+"\r\n";
		content +='	function '+tablename+'DeleteSuccess($params=array()){}'+"\r\n";
		content +='	function '+tablename+'DeleteFailure($params=array()){}'+"\r\n";
		content +=''+"\r\n";
		content +='	//getDBRecord, listDBRecords, and getDBRecords trigger'+"\r\n";
		content +='	function '+tablename+'GetRecord($rec=array()){}'+"\r\n";
		content +='*/'+"\r\n\r\n";
		content +='?>'+"\r\n";
	}
	setText(theForm["functions"],content);
	//var cm = CodeMirror.fromTextArea(theForm["functions"]);
	//cm.setValue(content);

}
//--------------------------
function remindMeForm(){
	var dname="remindMePopup";
	var txt='';
	txt +=  '	<div class="w_centerpop_content" id="remindmediv" style="width:300px;">'+"\n";
	txt +=	'		<form method="POST" name="remindMe" class="w_form" action="/php/index.php" onSubmit="ajaxSubmitForm(this,\'remindmediv\');return false;">'+"\n";
	txt +=  '			<input type="hidden" name="_remind" value="1">'+"\n";
	txt +=  '			<input type="hidden" name="tname" value="remind me">'+"\n";
	txt +=	'			<div class="w_gray"> Enter the email address tied to your account profile to have your username and password emailed to you.</div>'+"\n";
	txt +=	'			<div><span class="icon-mail"></span> <b>Email Address</b></div>'+"\n";
	txt +=	' 			<div><input type="text" maxlength="255" name="email" placeholder="email address" pattern=".+@.+..{2,6}" data-pattern-msg="Invalid Email Address" data-required="1" data-requiredmsg="Enter the email address you registered with." value="" onFocus="this.select();" class="form-control"></div>'+"\n";
	txt +=	'			<div align="right" style="margin-right:2px;margin-top:5px;"><button type="submit" class="btn btn-primary w_formsubmit">Remind Me</button></div>'+"\n";
	txt +=  '		</form>';
	txt +=	'	</div>'+"\n";
	var rtitle='<span class="icon-info"></span> Remind Me Form';
	popUpDiv('',{id:dname,width:300,height:50,drag:1,notop:1,nobot:1,noborder:1,nobackground:1,bodystyle:"padding:0px;border:0px;background:none;"});
	setCenterPopText(dname,txt,{title:rtitle});
	document.remindMe.email.focus();
	return false;
	}
//--------------------------
function setProcessing(id,msg,cancel){
	if(undefined == cancel){cancel=1;}
	if(undefined == msg){msg='Processing ...';}
	var str=getProcessingDiv(id,msg,cancel);
	setText(id,str);
	return;
	}
//--------------------------
function getProcessingDiv(id,msg,cancel){
	if(undefined == cancel){cancel=1;}
	if(undefined == msg){msg='Processing. Please Wait ...';}
	var str='<div style="display:table-cell;">';
	str+='<div><img src="/wfiles/loading_blu.gif" alt="loading" /> '+msg+'</div>';
	if(cancel==1){
    	str+='<div align="right" style="padding:5px 0 0 0;"><a href="#cancel" onclick="return ajaxAbort(\''+id+'\');" class="btn btn-default btn-sm" style="display:table-cell;padding:0 2px 0 2px;font-size:14px;">Cancel <span class="icon-cancel-circled" style="font-size:18px;"></span></a></div>';
	}

	str+='</div>';
	return str;
	}
//--------------------------
function submitRemindMeForm(frm){
	setProcessing('reminderMessage');
	ajaxSubmitForm(frm,'remindMeForm_Body');
	return false;
	}
//--------------------------
function setDateTimeBox(id,d,h,m,ap){
	if(undefined == d || d.length==0){
		var cdate=new Date();
		d=cdate.getMonth()+'-'+cdate.getDate()+'-'+cdate.getFullYear();
		}
	var dparts=d.split('-',3);
	h=parseInt(h*1);
	if(ap=='PM'){h=h+12;}
	if(h < 10){h='0'+h;}
	if(m.length==1){m='0'+m;}
	var t=dparts[2]+'-'+dparts[0]+'-'+dparts[1]+' '+h+':'+m+':00';
	setText(id,t);
	return 1;
	}
//--------------------------
function setSliderText(fld){
	var val=fld.value;
	var attr=getAllAttributes(fld);
	if(undefined == attr["data-label"]){return;}
	if(undefined != attr['data-labelmap']){
		attr['data-labelmap']=str_replace("'",'"',attr['data-labelmap']);
    	var map=JSON.parse(attr['data-labelmap']);
    	if(undefined != map[val]){
			setText(attr['data-label'],map[val]);
			return;
		}
	}
	setText(attr['data-label'],val);
}
//--------------------------
function setTimeBox(id,h,m,ap){
	h=parseInt(h*1);
	if(ap=='PM'){h=h+12;}
	if(h.length==1){h='0'+h;}
	if(m.length==1){m='0'+m;}
	var t=h+':'+m+':00';
	setText(id,t);
	return 1;
	}
//--------------------------
function showColors(color,hexbox,colorbox){
	window.status=color+","+hexbox+","+colorbox;
	var setEl = document.getElementById(hexbox);
	setEl.innerHTML=color;
	setEl = document.getElementById(colorbox);
	setEl.style.backgroundColor=color;
	}
//--------------------------
function showColor(color){
	showColors(color,'show_hex','show_color');
	}
//--------------------------
var setHexObj;
var setHexDiv;
var setHexImg;
function selectColor(divid,frmObj,imgid){
	setHexObj=frmObj;
	setHexDiv=divid;
	setHexImg=imgid;
	ajaxGet('/wfiles/colortable.html',divid);
	}
//--------------------------
function colorSelector(id){
	var obj=getObject(id);
	if(undefined == obj){alert(id+' does not exist');return false;}
	var wobj=getObject(id+'_wrapper');
	if(undefined != wobj){
    	//already open - close it.
    	removeId(wobj);
    	return false;
	}
	obj.style.position='relative';
	this.iconid=id+'_icon';
	var iconobj=getObject(this.iconid);
	var h=getHeight(obj);
	//wrapper
	this.wrapper=document.createElement('div');
	this.wrapper.style.width='115px';
	this.wrapper.style.height='134px';
	this.wrapper.id=id+'_wrapper';
	this.wrapper.style.position='absolute';
	this.wrapper.style.top=h+'px';
	this.wrapper.style.left='0px';
	this.wrapper.style.zIndex='99999';
	this.wrapper.style.border='1px solid #C0C0C0';
	this.wrapper.style.backgroundColor='#FFFFFF';
	this.wrapper.style.borderBottom='10px solid #C0C0C0';
	iconobj.insertAdjacentElement('afterEnd',this.wrapper);
	//box in wrapper
	this.colorbox=document.createElement('div');
	this.colorbox.style.width='80px';
	this.colorbox.style.height='100px';
	this.colorbox.style.position='absolute';
	this.colorbox.style.top='0px';
	this.colorbox.style.left='0px';
	this.colorbox.style.cursor='crosshair';
	this.colorbox.id=id+'_colorbox';
	this.wrapper.insertAdjacentElement('afterBegin',this.colorbox);
	//slider in wrapper
	this.slider=document.createElement('div');
	this.slider.style.width='35px';
	this.slider.style.height='100px';
	this.slider.style.position='absolute';
	this.slider.style.top='0px';
	this.slider.style.right='0px';
	this.slider.style.cursor='crosshair';
	this.slider.id=id+'_slider';
	this.wrapper.insertAdjacentElement('afterBegin',this.slider);
	//value in wrapper
	this.valuebox=document.createElement('div');
	this.valuebox.style.width='115px';
	this.valuebox.style.height='24px';
	this.valuebox.style.position='absolute';
	this.valuebox.style.top='100px';
	this.valuebox.style.left='0px';
	this.valuebox.id=id+'_valuebox';
	this.wrapper.insertAdjacentElement('afterBegin',this.valuebox);
	this.setid=id+'_set';
	this.btnid=id+'_btn';
	this.lineid=id+'_line';
	var valuetext='<div style="font-size:14px;height:30px;width:115px;font-family:arial;position:relative;">';
	valuetext+='<div style="position:absolute;top:0px;right:3px;cursor:pointer;" onclick="colorSelectorSet(\''+id+'\')" title="Set Color"><span id="'+this.btnid+'" class="icon-checkbox" style="font-size:18px;cursor:pointer;color:none;"></span></div>';
	valuetext+='<div style="position:absolute;top:0px;left:5px;" id="'+this.setid+'"></div>';
	valuetext+='</div>';
	setText(this.valuebox,valuetext);
	//call ColorPicker
	this.picker=ColorPicker(this.slider,this.colorbox,function(hex, hsv, rgb) {
		var btnid=this.control_id+'_btn';
		var setid=this.control_id+'_set';
		var lineid=this.control_id+'_wrapper';
		hex=hex.toUpperCase();
		setText(setid,hex);
		setStyle(btnid,'color',hex);
		setStyle(lineid,'borderBottom','10px solid '+hex);
	});
	this.picker.control_id=id;
	this.picker.id=this.wrapper.id;
	if(obj.value.length){
		this.picker.setHex(obj.value);
	}
	return false;
}
//--------------------------
function colorSelectorSet(id){
	var setid=id+'_set';
	var wrapper=id+'_wrapper';
	var iconid=id+'_icon';
	var hex=getText(setid);
	setText(id,hex);
	setStyle(iconid,'color',hex);
	removeId(wrapper);
	return false;
}
//--------------------------
function setHex(hex){
	setText(setHexObj,hex);
	setText(setHexDiv,'');
	setStyle(setHexImg,'backgroundColor',hex);
    }
//--------------------------
function showToolbar(field,cat){
	var list=GetElementsByAttribute('div','id','toolbar');
	for(var i=0;i<list.length;i++){
		var cfield = list[i].getAttribute('field');
		var ccat = list[i].getAttribute('category');
		if(cfield == field && ccat == cat){
			list[i].style.display='block';
			}
		else {
			list[i].style.display='none';
			}
		}
	}
//--------------------------
function setRadioLabel(rname){
	var rtags=GetElementsByAttribute('input','name','^'+rname+'$');
	for(var x=0;x<rtags.length;x++){
    	var rtagid=rtags[x].id;
    	var cust=GetElementsByAttribute('label','for','^'+rtagid+'$');
    	for(var i=0;i<cust.length;i++){
			if(rtags[x].checked){setClassName(cust[i],'w_checklist_checked');}
			else{setClassName(cust[i],'w_checklist');}
	    }
	}
}
//--------------------------
function setLabelChecked(att,val,ck,classn,classc){
    //info:decorate labels associated with a checkbox value that have an attribute of value
    //info: returns number of items checked
    var cust=GetElementsByAttribute('label',att,'^'+val+'$');
    var cnt = 0;
    if(undefined!=classn && undefined!=classc){
	    for(var i=0;i<cust.length;i++){
			if(ck){setClassName(cust[i],classc);}
			else{setClassName(cust[i],classn);}
	    	}
	    }
	else{
		for(var i=0;i<cust.length;i++){
			if(ck){setClassName(cust[i],'w_checklist_checked');}
			else{setClassName(cust[i],'w_checklist');}
	    	}
	    }
	return true;
    }
//--------------------------
function checkAllElements(att,val,ck){
    //info:check/toggle all checkboxes that have an attribute of value
    //info: returns number of items checked
    //usage: <input type="checkbox" onclick="checkAllElements('cid','mylist', this.checked);">
    var cust=GetElementsByAttribute('input',att,'^'+val+'$');
    var cnt = 0;
    for(var i=0;i<cust.length;i++){
		if(cust[i].type=='checkbox'){cust[i].checked=ck;cnt++;}
    	}
    if (ck){return cnt;}
	return 0;
    }
//--------------------------
function setTimeField(frmname,fldname){
	var dt = new Date();
	//getMonth starts at 0, so add one
	var mon=dt.getMonth()+1;
	if(mon.length==1){mon='0'+mon;}
	var day=dt.getDate();
	if(day.length==1){day='0'+day;}
	var d=mon+'-'+day+'-'+dt.getFullYear();
    var h=dt.getHours();
    var m=dt.getMinutes();
    //get the m that is closest
    if(m<10){m="0"+m;}
    var p="am";
    if(h == 0){h=12;p="am";}
    else if(h > 12){h=h-12;p="pm";}
    if(h<10){h="0"+h;}
    var obj=getObject(frmname+'_'+fldname+'_date');
    if(undefined != obj){obj.value=d;}
    var obj=getObject(frmname+'_'+fldname+'_hour');
    if(undefined != obj){obj.value=h;}
    var obj=getObject(frmname+'_'+fldname+'_minute');
    if(undefined != obj){obj.value=m;}
    var obj=getObject(frmname+'_'+fldname+'_ampm');
    if(undefined != obj){obj.value=p;}
	}
//--------------------------
function insertAtCursor(myField, myValue) {
	var obj=getObject(myField);
	//usage: insertAtCursor(document.formName.fieldName, 'this value');
	//IE support
	if (document.selection) {
		obj.focus();
		sel = document.selection.createRange();
		sel.text = myValue;
		}
	//MOZILLA/NETSCAPE support
	else if (obj.selectionStart || obj.selectionStart == '0') {
		var startPos = obj.selectionStart;
		var endPos = obj.selectionEnd;
		obj.value = obj.value.substring(0, startPos) + myValue + obj.value.substring(endPos, obj.value.length);
		}
	else {
		obj.value += myValue;
		}
	}
//--------------------------
function isCreditCardNumber(ccNumb) {
	//info: returns true if string passed in a valid credit card number format
	var valid = "0123456789"  // Valid digits in a credit card number
	var len = ccNumb.length;  // The length of the submitted cc number
	var iCCN = parseInt(ccNumb);  // integer of ccNumb
	var sCCN = ccNumb.toString();  // string of ccNumb
	sCCN = sCCN.replace (/^\s+|\s+$/g,'');  // strip spaces
	var iTotal = 0;  // integer total set at zero
	var bNum = true;  // by default assume it is a number
	var temp;  // temp variable for parsing string
	var calc;  // used for calculation of each digit
	// Determine if the ccNumb is in fact all numbers
	for (var j=0; j<len; j++) {
		temp = "" + sCCN.substring(j, j+1);
  		if (valid.indexOf(temp) == "-1"){return false;}
		}
	// ccNumb is a number and the proper length - let's see if it is a valid card number
  	if(len >= 15 && len <= 20){
	// 15 or 16 for Amex or V/MC
    	for(var i=len;i>0;i--){  // LOOP throught the digits of the card
      		calc = parseInt(iCCN) % 10;  // right most digit
     		calc = parseInt(calc);  // assure it is an integer
      		iTotal += calc;  // running total of the card number as we loop - Do Nothing to first digit
      		i--;  // decrement the count - move to the next digit in the card
      		iCCN = iCCN / 10;                               // subtracts right most digit from ccNumb
      		calc = parseInt(iCCN) % 10 ;    // NEXT right most digit
      		calc = calc *2;                                 // multiply the digit by two
      		// Instead of some screwy method of converting 16 to a string and then parsing 1 and 6 and then adding them to make 7,
      		// I use a simple switch statement to change the value of calc2 to 7 if 16 is the multiple.
      		switch(calc){
        		case 10: calc = 1; break;       //5*2=10 & 1+0 = 1
        		case 12: calc = 3; break;       //6*2=12 & 1+2 = 3
        		case 14: calc = 5; break;       //7*2=14 & 1+4 = 5
        		case 16: calc = 7; break;       //8*2=16 & 1+6 = 7
        		case 18: calc = 9; break;       //9*2=18 & 1+8 = 9
        		default: calc = calc;           //4*2= 8 &   8 = 8  -same for all lower numbers
      			}
    		iCCN = iCCN / 10;  // subtracts right most digit from ccNum
    		iTotal += calc;  // running total of the card number as we loop
  			}
  		// check to see if the sum Mod 10 is zero
  		var m=iTotal%10;
  		if (m==0){
    		return true;  // This IS (or could be) a valid credit card number.
  			}
		else {
    		return false;  // This could NOT be a valid credit card number
    		}
  		}
  	return false;
	}
//--------------------------
function textboxSelect (oTextbox, iStart, iEnd) {
	switch(arguments.length) {
		case 1:
			oTextbox.select();
			break;
		case 2:
			iEnd = oTextbox.value.length;
			/* falls through */
		case 3:
			if (isIE) {
               	var oRange = oTextbox.createTextRange();
               	oRange.moveStart("character", iStart);
               	oRange.moveEnd("character", -oTextbox.value.length + iEnd);
               	oRange.select();
           		} 
			else if (isMoz){
            	oTextbox.setSelectionRange(iStart, iEnd);
           		}
           	break;
   		}
	oTextbox.focus();
	}
function textboxReplaceSelect (oTextbox, sText) {
	if (isIE) {
		var oRange = document.selection.createRange();
		oRange.text = sText;
		oRange.collapse(true);
		oRange.select();
		} 
	else if (isMoz) {
		var iStart = oTextbox.selectionStart;
		oTextbox.value = oTextbox.value.substring(0, iStart) + sText + oTextbox.value.substring(oTextbox.selectionEnd, oTextbox.value.length);
		oTextbox.setSelectionRange(iStart + sText.length, iStart + sText.length);
		}
   	oTextbox.focus();
	}
//--------------------------
function submitForm(theForm,popup,debug,ajax){
	//info: submitForm -- parses through theForm and validates input based in additonal field attributes
	//info:  Possible attributes are: data-required="1" data-requiredmsg="" mask="^[0-9]" data-pattern-msg="Age must begin with a number" maxlength="23"

	if(undefined == debug){debug==0;}
	if(undefined == ajax){ajax==0;}
	if(undefined == theForm){
		alert("No form object passed to submitForm");
		return false;
		}
	/*If action is delete then just return true*/
	if(undefined != theForm._action){
		if(theForm._action.value == 'Delete'){return true;}
    	}
    popup=1;
    //Define some quickmasks
    var quickmask=new Array();
	quickmask['alpha'] = '^[a-zA-Z_\\-\\?\\ \\\']+$';
	quickmask['alphanumeric'] = '^[0-9a-zA-Z_\\-\\.\\?\\ \\\']+$';
	quickmask['calendar'] = '^[0-9]{1,2}\\-[0-9]{1,2}\\-[0-9]{1,4}$';
	quickmask['email'] = '.+@.+\\..{2,6}';
	quickmask['integer'] = '^[0-9]+$';
	quickmask['hexcolor'] = '^#[abcdef0-9]{6,6}$';
	quickmask['decimal'] = '^[0-9]*\\.[0-9]+$';
	quickmask['number'] = '(^[0-9]+$)|(^\\.[0-9]+$)|(^[0-9]+\\.[0-9]+$)';
	quickmask['phone'] = '^([0-9]{3,3}[\\-\\.][0-9]{3,3}[\\-\\.][0-9]{4,4}|\\([0-9]{3,3}\\)\\ [0-9]{3,3}[\\-][0-9]{4,4})$';
	quickmask['time'] = '^[0-9]{1,2}\\:[0-9]{2}$';
	quickmask['ssn'] = '^[0-9]{3,3}\\-[0-9]{2,2}\\-[0-9]{4,4}$';
	quickmask['zipcode'] = '^[0-9]{5,5}(\\\-[0-9]{4,4})*$';
	//alert("theForm type="+typeof(theForm));
	if(theForm.length ==0){return false;}
	if(debug==1){alert("Form length: "+theForm.length);}
	var formfields=new Array();
	for(var i=0;i<theForm.length;i++){
		if(debug==1){alert("Checking "+theForm[i].name+" of type "+theForm[i].type);}
		/* add this form name to the list of formfields */
		if(!in_array(theForm[i].name,formfields) && theForm[i].name != '_formfields'){
			formfields[formfields.length]=theForm[i].name;
		}
	  	/* Password confirm */
	  	if(theForm[i].name == 'password'  && undefined != theForm.password_confirm){
	  		if(theForm[i].value.length == 0 || theForm.password_confirm.value.length == 0){
				submitFormAlert('Password is required',popup,5);
                theForm[i].focus();
                return false;
            }
            if(theForm[i].value != theForm.password_confirm.value){
				submitFormAlert('Passwords do not match.  Please retype password.',popup,5);
                theForm[i].focus();
                return false;
            }
		}
		/* email confirm */
	  	if(theForm[i].name == 'email'  && undefined != theForm.email_confirm){
	  		if(theForm[i].value.length == 0 || theForm.email_confirm.value.length == 0){
				submitFormAlert('Email is required',popup,3);
                theForm[i].focus();
                return false;
            }
            if(theForm[i].value != theForm.email_confirm.value){
				submitFormAlert('Emails do not match.',popup,3);
                theForm[i].focus();
                return false;
            }
		}
		var dname=theForm[i].name;
		if(theForm[i].getAttribute('data-displayname')){dname=theForm[i].getAttribute('data-displayname');}
		else if(theForm[i].getAttribute('displayname')){dname=theForm[i].getAttribute('displayname');}

        //check for required attribute
        var required=0;
		if(undefined != theForm[i].getAttribute('_required')){required=theForm[i].getAttribute('_required');}
		else if(undefined != theForm[i].getAttribute('data-required')){required=theForm[i].getAttribute('data-required');}
		else if(undefined != theForm[i].getAttribute('required')){required=theForm[i].getAttribute('required');}
        if(required == 1){
			var requiredmsg=theForm[i].getAttribute('data-requiredmsg');
			if(undefined == requiredmsg){requiredmsg=theForm[i].getAttribute('requiredmsg');}
			//checkboxes
			if(theForm[i].type=='checkbox'){
				var checkname='name';
				if(theForm[i].getAttribute('checkname')){checkname=theForm[i].getAttribute('checkname');}
				var checkval=theForm[i].getAttribute(checkname);
				//alert(checkname+'='+checkval);
				var checkboxlist=GetElementsByAttribute('input', checkname, checkval);
				//alert(checkboxlist.length+' elements found with a '+checkname+' of '+checkval);
				var isChecked=0;
				for(var c=0;c<checkboxlist.length;c++){
					if(checkboxlist[c].type=='checkbox' && checkboxlist[c].checked){isChecked++;}
					else if(checkboxlist[c].type=='text' && checkboxlist[c].value != ''){isChecked++;}
                	}
                if(isChecked==0){
					var msg=dname+" is required";
		            if(undefined != requiredmsg){msg=requiredmsg;}
				 	submitFormAlert(msg,popup,5);
		            theForm[i].focus();
		            return false;
                	}
            	}
            else if(theForm[i].type=='radio'){
				var checkboxlist=GetElementsByAttribute('input', 'name', theForm[i].name);
				var isChecked=0;
				for(var c=0;c<checkboxlist.length;c++){
					if(checkboxlist[c].type=='radio' && checkboxlist[c].checked){isChecked++;}
					else if(checkboxlist[c].type=='text' && checkboxlist[c].value != ''){isChecked++;}
                	}
                if(isChecked==0){
					var msg=dname+" is required";
		            if(undefined != requiredmsg){msg=requiredmsg;}
				 	submitFormAlert(msg,popup,5);
		            theForm[i].focus();
		            return false;
                	}
            	}
            else if(theForm[i].type=='textarea'){
            	var cval=trim(getText(theForm[i]));
            	if(cval.length==0){
                	var msg=dname+" is required";
		            if(undefined != requiredmsg){msg=requiredmsg;}
				 	submitFormAlert(msg,popup,5);
		            theForm[i].focus();
		            return false;
				}
			}
			else if(theForm[i].value == ''){
	            var msg=dname+" is required";
	            if(undefined != requiredmsg){msg=requiredmsg;}
			 	submitFormAlert(msg,popup,5);
	            theForm[i].focus();
	            return false;
				}
            }
        //check for mask attribute - a filter to test input against
        var mask=theForm[i].getAttribute('pattern');
        if(undefined == mask){mask=theForm[i].getAttribute('mask');}
        if(undefined != mask && mask != '' && theForm[i].value != ''){
			var fldmsg=theForm[i].getAttribute('data-pattern-msg');
			if(undefined == fldmsg){fldmsg=theForm[i].getAttribute('maskmsg');}
            if(mask == 'ccnumber'){
				//credit card number
				if(isCreditCardNumber(theForm[i].value) == false){
					//invalid card number
                    var msg = dname+" must be of valid credit card number ";
                    if(undefined != fldmsg){msg=fldmsg;}
                    submitFormAlert(msg,popup,5);
                    theForm[i].focus();
                    return false;
                    }
                }
            else if(mask == 'intlphone'){
				//international phone check
				if(checkInternationalPhone(theForm[i].value) == false){
					//invalid card number
                    var msg = dname+" must be a valid phone number";
                    if(undefined != fldmsg){msg=fldmsg;}
                    submitFormAlert(msg,popup,5);
                    theForm[i].focus();
                    return false;
                    }
                }
            else if(mask != 'searchandreplace'){
				if(mask == 'phone'){
                	theForm[i].value=formatPhone(theForm[i].value);
				}
				else if(mask == 'calendar'){
                	theForm[i].value=formatCalendar(theForm[i].value);
				}
				var rmask=mask;
                if(undefined != quickmask[mask]){rmask=quickmask[mask]+'';}
                //console.log('rmask:'+rmask);
                var re = new RegExp(rmask, 'i');
                if(re.test(theForm[i].value) == false){
                    var msg = dname+" must be of type "+mask;
                    if(undefined != fldmsg){msg=fldmsg;}
                    submitFormAlert(msg,popup,5);
                    theForm[i].focus();
                    return false;
                    }
           		}
            }
        //check for length attribute on textarea fields
        if(theForm[i].type == 'textarea' && theForm[i].getAttribute('maxlength')){
            var len=theForm[i].value.length;
            var max=Math.abs(theForm[i].getAttribute('maxlength'));
            if(len > max){
                var msg = dname+" must be less than "+max+" characters\nYou entered "+len+" characters.";
                if(theForm[i].getAttribute('maxlengthmsg')){msg=theForm[i].getAttribute('maxlengthmsg');}
                submitFormAlert(msg,popup,5);
                theForm[i].focus();
                return false;
            }
        }
        //min length
        if(theForm[i].getAttribute('minlength')){
            var len=theForm[i].value.length;
            var minlength=Math.abs(theForm[i].getAttribute('minlength'));
            if(len < minlength){
                var msg = dname+" must be at least "+minlength+" characters.\nYou entered "+len+" characters.";
                if(theForm[i].getAttribute('minlengthmsg')){msg=theForm[i].getAttribute('minlengthmsg');}
                submitFormAlert(msg,popup,5);
                theForm[i].focus();
                return false;
            }
        }
        //minwords
        if(theForm[i].getAttribute('minwords')){
			var cnt=getWordCount(theForm[i]);
            var minwords=Math.abs(theForm[i].getAttribute('minwords'));
            if(len < minwords){
                var msg = dname+" must be at least "+minwords+" words in length.\nYou entered "+cnt+" words.";
                if(theForm[i].getAttribute('minwordsmsg')){msg=theForm[i].getAttribute('minwordsmsg');}
                submitFormAlert(msg,popup,5);
                theForm[i].focus();
                return false;
            }
        }
        //maxwords
        if(theForm[i].getAttribute('maxwords')){
			var cnt=getWordCount(theForm[i]);
            var maxwords=Math.abs(theForm[i].getAttribute('maxwords'));
            if(len < maxwords){
                var msg = dname+" must be less than "+maxwords+" words in length.\nYou entered "+cnt+" words.";
                if(theForm[i].getAttribute('maxwordsmsg')){msg=theForm[i].getAttribute('maxwordsmsg');}
                submitFormAlert(msg,popup,5);
                theForm[i].focus();
                return false;
            }
        }
        //check for allow for file types
        if(theForm[i].type == 'file' && theForm[i].getAttribute('accept') && theForm[i].value.length){
			var allow=theForm[i].getAttribute('accept');
			if(debug==1){
				alert(" -- File type allowed exts:"+allow);
            	}
			var exts=allow.split(',');
			var valid=0;
			for(s=0;s<exts.length;s++){
				if(theForm[i].value.lastIndexOf(exts[s])!=-1){valid++;}
                }
            if(valid==0){
            	var msg = dname+" must be of valid file type:  "+allow;
                if(theForm[i].getAttribute('acceptmsg')){msg=theForm[i].getAttribute('acceptmsg');}
                submitFormAlert(msg,popup,5);
                theForm[i].focus();
                return false;
                }
            }
        }
    //nicEdit save
    for(var i=0;i<theForm.length;i++){
		var behavior=theForm[i].getAttribute('data-behavior');
		var id=theForm[i].getAttribute('id');
		if(undefined != behavior && (behavior=='wysiwyg' || behavior=='richtext' || behavior=='tinymce' || behavior=='nicedit') && undefined != nicEditors[id]){
			if(ajax==1){
            	//ajaxSubmitForm needs us to manually call saveContent()
            	nicEditors.findEditor(id).saveContent();
			}
			nicEditors[id].removeInstance(id);
		}
	}
    //add a hidden field to this form before submitting with a list of all the fields in the form
    var formfieldstr=implode(',',formfields);
    var ffound=0;
    for(var i=0;i<theForm.length;i++){
		if(theForm[i].name=='_formfields'){
			theForm[i].value=formfieldstr;
			ffound=1;
			break;
		}
	}
    if(ffound==0){
		var ffield = document.createElement("input");
		ffield.type='hidden';
		ffield.name='_formfields';
		ffield.value=formfieldstr;
		theForm.appendChild(ffield);
		}
    return true;
	}
//--------------------------
function imposeMaxlength(obj, max){
	return (obj.value.length <= max);
	}
//--------------------------
//submitSurveyForm appends additional info to the form 
//  like question, section, etc
//	usage: onSubmit="return submitSurveyForm(this,{question:1,section:1,group:1,order:1});"
function submitSurveyForm(theForm,opts){
	if(undefined == theForm){
		alert('submitSurveyForm Error: No Form');
		return false;
    	}
	//validate first
	if(!submitForm(theForm)){return false;}
	//lets make an array of form element names
	var enames=new Array();
	for(var i=0;i<theForm.length;i++){
		var fname=theForm[i].name;
		enames[fname]=1;
		}
	for(var i=0;i<theForm.length;i++){
		if(undefined == theForm[i].name){continue;}
		var fname=theForm[i].name;
		if(fname.length==0){continue;}
		//check for opts
		if(undefined != opts){
			for (var optkey in opts){
				if(opts[optkey]!=1){continue;}
				if(theForm[i].getAttribute(optkey)){
					var optval=theForm[i].getAttribute(optkey);
					if(optval.length){
						var fieldname=optkey+'_'+fname;
						if(undefined == enames[fieldname]){
							enames[fieldname]=1;
							var addfield=document.createElement("textarea");
							addfield.setAttribute('NAME',fieldname);
							addfield.style.display='none';
							addfield.value=optval;
							theForm.appendChild(addfield);
							}
		   				}
		  			}
	            }
	        }
        }
    return true;
    }
//--------------------------
function checkInternationalPhone(strPhone){
	// Declaring required variables
	var digits = "0123456789";
	// non-digit characters which are allowed in phone numbers
	var phoneNumberDelimiters = ".()- ";
	// characters which are allowed in international phone numbers
	// (a leading + is OK)
	var validWorldPhoneChars = phoneNumberDelimiters + "+";
	// Minimum no of digits in an international phone no.
	var minDigitsInIPhoneNumber = 10;
	var bracket=3;
	strPhone=trim(strPhone);
	if(strPhone.indexOf("+")>1) return false;
	if(strPhone.indexOf("-")!=-1){bracket=bracket+1;}
	if(strPhone.indexOf("(")!=-1 && strPhone.indexOf("(")>bracket){return false;}
	var brchr=strPhone.indexOf("(");
	if(strPhone.indexOf("(")!=-1 && strPhone.charAt(brchr+2)!=")"){return false;}
	if(strPhone.indexOf("(")==-1 && strPhone.indexOf(")")!=-1){return false;}
	s=stripCharsInBag(strPhone,validWorldPhoneChars);
	return (isInteger(s) && s.length >= minDigitsInIPhoneNumber);
	}
//--------------------------
function isInteger(s){ 
	//info: returns true if s is an integer
	var i;
    for (i = 0; i < s.length; i++){
        // Check that current character is number.
        var c = s.charAt(i);
        if (((c < "0") || (c > "9"))) return false;
    	}
    // All characters are numbers.
    return true;
	}
//--------------------------
function trim(str){
	if (null != str && undefined != str && "" != str){
		var rval=str.replace(/^[\ \s\0\r\n\t]*/g,"");
		rval=rval.replace(/[\ \s\0\r\n\t]*$/g,"");
	    return rval;
		}
	else{return "";}
	}
//--------------------------
function stripCharsInBag(s, bag){   
	var i;
    var returnString = "";
    // Search through string's characters one by one.
    // If character is not in bag, append to returnString.
    for (i = 0; i < s.length; i++){
        // Check that current character isn't whitespace.
        var c = s.charAt(i);
        if (bag.indexOf(c) == -1){ returnString += c;}
    	}
    return returnString;
	}
//--------------------------
function submitFormAlert(msg,popup,timer){
	if(undefined == popup){popup=0;}
	if(popup){
		html='';
		html+='<div class="w_centerpop_title" style="background:#d50000;"><img src="/wfiles/iconsets/16/alert.png" alt="errors" class="w_middle"> Error Processing Request</div>'+"\n";
		html+= '<div class="w_centerpop_content">'+"\n";
		html+= '	<div class="w_big w_dblue"> - '+msg+'</div>'+"\n";
		html+= '	<div class="w_smallest w_right w_lblue" style="margin-right:20px;" id="centerpop2_countdown">4</div>'+"\n";
		html+= '</div>'+"\n";
		centerpopDiv(html,3,2);
		countDown('centerpop2_countdown');
    	}
    else{alert(msg);}
    return false;
	}
//--------------------------
function showFormData(theForm,id){
	if(undefined == theForm){
		alert("No form object passed to formData");
		return false;
		}
	//alert("theForm type="+typeof(theForm));
	if(theForm.length ==0){return false;}
	var str='';
	for(var i=0;i<theForm.length;i++){
		var dname=theForm[i].name;
		var type=typeof(theForm[i]);
		//alert(theForm[i]);
		//if(type == 'object'){showProperties(theForm[i],'info',1);}
		str += 'Name: '+ dname + "<br>\r\n";
		str += 'Type: '+ type + "<br>\r\n";
		}
	if(!id){
		alert(str);
		}
	else{
		setText(id,str);
    	}
	}
//--------------------------
function checkPerl(field,value){
	if(undefined == document._perlcheck){
		alert('document._perlcheck does not exist.');
		return false;
		}
	var pfield=field+"_perlcheck";
	if(undefined == value){
		alert('Nothing to check.');
		return false;
		}
	if(value.length ==0){
		alert('Nothing to check.');
		return false;
		}
	document.getElementById(pfield).innerHTML ='<img src="/wfiles/busy.gif" title="checking Perl syntax" width="12" height="12" alt="checking Perl syntax" />';
	document._perlcheck.perlcheck.value=value;
	ajaxSubmitForm(document._perlcheck,pfield,5);
	return false;
	}
//--------------------------
/*

*/
function cloneObj(c){
	//info: cloneObj - clones a row, div, ect.  Use for adding another file input type or another item in a form
	//info: if a input field is a button and is named _clone then it will hide that field on cloned objects
	//info: incriments field names for input, select, and textarea fields cloned . name, name_1, name_2, ...
	var obj=getObject(c);
	var inc=obj.getAttribute('inc');
	if(undefined == inc){inc=1;}
  	else{
		inc=parseFloat(inc);
		inc++;
	  	}
	obj.setAttribute('inc',inc);
	/* clone the object */
	var cloneObj = obj.cloneNode(true);
	/*Gen any input elements in the cloned object*/
	if(undefined != cloneObj.id){
		cloneObj.setAttribute('id',cloneObj.id+'_'+inc);
    	}
    if(undefined != cloneObj.style.display && cloneObj.style.display=='none'){
    	cloneObj.style.display='block';
		}
	var clonedInputs = cloneObj.getElementsByTagName('input');
	var clonedImgs = cloneObj.getElementsByTagName('img');
	/*Incriment the names of any inputs found*/
	for(var i=0;i<clonedInputs.length;i++){
		var cname=clonedInputs[i].getAttribute('name');
		var cid=clonedInputs[i].getAttribute('id');
		if(undefined == cid || cid.length==0){cid=cname;}
		/* skip inputs without a name attribute*/
		if(undefined == cname){continue;}
		/*get input type*/
		var ctype=clonedInputs[i].getAttribute('type');
		/* hide buttons named _clone */
		if(cname == '_clone' && ctype.toLowerCase() == 'button'){
			clonedInputs[i].style.display='none';
			}
		//alert(cname+','+ctype);
		/* set the value to zero unless it is a button or hidden */
		if(ctype.toLowerCase() != 'button' && ctype.toLowerCase() != 'hidden'){clonedInputs[i].value='';}
		/* get and set the newname */
		var newname=cname+'_'+inc;
		var newid=cid+'_'+inc;
		//do not incriment the name if it ends in []
		if(newname.indexOf('[]')>1){newname=cname;}
		/* treat _path field differently since they need to match their corresponding field */
		if(cname.indexOf('_path') != -1){
			newname=cname.replace(/path/, "");
			newname+=inc+'_path';
        	}
        if(cname.indexOf('_autonumber') != -1){
			newname=cname.replace(/autonumber/, "");
			newname+=inc+'_autonumber';
        	}
		clonedInputs[i].setAttribute('name',newname);
		if(undefined != cid && cid.indexOf('_datecontrol') != -1){newid=newid+'_datecontrol';}
		clonedInputs[i].setAttribute('id',newid);

		/*Incriment the names of any imgs found*/
		if(undefined != cid){
			for(var k=0;k<clonedImgs.length;k++){
				if(undefined != clonedImgs[k].getAttribute('onclick')){
					var onclickval =clonedImgs[k].getAttribute('onclick');
					if(typeof(onclickval.replace) == "function"){
						onclickval=onclickval.replace(cid, newid);
						}
					clonedImgs[k].setAttribute('onclick',onclickval);
					}
				}
			}
		}
	/*Gen any select elements in the cloned object*/
	var clonedSelects = cloneObj.getElementsByTagName('select');
	/*Incriment the names of any selects found*/
	for(var i=0;i<clonedSelects.length;i++){
		var cname=clonedSelects[i].getAttribute('name');
		/* skip inputs without a name attribute*/
		if(undefined == cname){continue;}
		/* get and set the newname */
		var newname=cname+'_'+inc;
		clonedSelects[i].setAttribute('name',newname);
		clonedSelects[i].setAttribute('id',newname);
		}
	/*Gen any textarea elements in the cloned object*/
	var clonedTextareas = cloneObj.getElementsByTagName('textarea');
	/*Incriment the names of any textareas found*/
	for(var i=0;i<clonedTextareas.length;i++){
		var cname=clonedTextareas[i].getAttribute('name');
		/* skip inputs without a name attribute*/
		if(undefined == cname){continue;}
		/* set the value to zero */
		clonedTextareas[i].value='';
		/* get and set the newname */
		var newname=cname+'_'+inc;
		//alert(newname);
		clonedTextareas[i].setAttribute('name',newname);
		clonedTextareas[i].setAttribute('id',newname)
		}
	obj.parentNode.appendChild(cloneObj);
	return inc;
	}
//--------------------------
function ajaxSubmitForm(theform,sid,tmeout,callback,returnreq,abort_callback){
	//info: submits a form via AJAX and returns the requested page contents to sid.  File inputs are not allowed via ajax
	return ajaxPost(theform,sid,tmeout,callback,returnreq,abort_callback);
	}
//--------------------------
function ajaxPopup(url,params,useropts){
	/* set default opt values */
	var pid='ajaxPopupDiv';
	var opt={
        id: pid,
        drag:1
		}
	/* allow user to override default opt values */
	if(useropts){
		for (var key in opt){
			if(undefined != useropts[key]){opt[key]=useropts[key];}
			}
		/* add additonal user settings to opt Object */
		for (var key in useropts){
			if(undefined == opt[key]){opt[key]=useropts[key];}
			}
		}
	popUpDiv('<div class="w_bold w_lblue w_big"><img src="/wfiles/loading_blu.gif" alt="loading" /> loading...please wait.</div>',opt);
	ajaxGet(url,'centerpop',params);
	}
//--------------------------
//--Submit form using ajax
function ajaxPost(theform,sid,tmeout,callback,returnreq,abort_callback) {
	//verify that they passed in the form object
	if(undefined == theform){
		alert("No form object passed to ajaxPost");
		return false;
		}
	//Pass form through validation before calling ajax
	var ok=submitForm(theform,1,0,1);
	if(!ok){return false;}
	//verify that the sid exists
	if(undefined == callback){
		//check hidden fields in the form
		if(undefined != theform.callback && theform.callback.type=='hidden' && theform.callback.value.length){
        	callback=theform.callback.value;
		}
		else{callback='';}
	}
	if(undefined == returnreq){returnreq=false;}
	if(undefined == abort_callback){
		//check hidden fields in the form
		if(undefined != theform.abort_callback && theform.abort_callback.type=='hidden' && theform.abort_callback.value.length){
        	abort_callback=theform.abort_callback.value;
		}
		else{abort_callback='';}
	}
	//default timeout to 10 minutes with a 3 minute minimum
	if(undefined == tmeout){tmeout=600000;}
	if(tmeout < 180000){tmeout=180000;}
	var lcsid=sid.toLowerCase();
	var cb=callback.toLowerCase();
	if(undefined == document.getElementById(sid) && cb.indexOf('popupdiv') == -1 && cb.indexOf('centerpop') == -1 && lcsid.indexOf('popupdiv') == -1 && lcsid.indexOf('centerpop') == -1){
		alert('Error in ajaxPost\n'+sid+' is not defined as a valid object id');
		return false;
    	}
    if(typeof(AjaxRequest.ActiveAjaxGroupRequests[sid]) != 'undefined'){
		ajaxAbort(sid);
	}
	//show processing?
	var showprocessing=true;
	if(undefined != theform.showprocessing){
		if(theform.setprocessing.value.toLowerCase()=='false'){showprocessing=false;}
		if(theform.setprocessing.value.toLowerCase()=='0'){showprocessing=false;}
	}
	else if(undefined != theform.noprocessing){
		if(theform.noprocessing.value.toLowerCase()=='true'){showprocessing=false;}
		if(theform.noprocessing.value.toLowerCase()=='1'){showprocessing=false;}
	}
	//show processing div
	var showprocessingdiv=sid;
	if(undefined != theform.showprocessingdiv){
		showprocessingdiv=theform.showprocessingdiv.value;
	}
	else if(undefined != theform.setprocessing){
		showprocessingdiv=theform.setprocessing.value;
	}

    //Set the ajax ID
	var AJUid=new Date().getTime() + "";
	//add AjaxRequestUniqueId as a hidden value to the form
	var h=document.createElement('input');
	h.type='hidden';
	h.name='AjaxRequestUniqueId';
	h.value=AJUid;
	theform.appendChild(h);
	//submit the form via ajax
	var getReq = AjaxRequest.submit(
		theform,{
			'groupName':sid
			,'timeout':tmeout
			,'callback':callback
			,'abort_callback':abort_callback
			,'showprocessing':showprocessing
			,'showprocessingdiv':showprocessingdiv
			,'AjaxRequestUniqueId':AJUid
			,'onGroupBegin':function(req){
				var dname=this.groupName;
				var lname=dname.toLowerCase();
				var cb=this.callback;
				cb=cb.toLowerCase();
				if(cb.indexOf('centerpop') != -1 || lname.indexOf('centerpop') != -1){
					var txt=getProcessingDiv(sid);
					popUpDiv('',{id:dname,width:300,height:50,notop:1,nobot:1,noborder:1,nobackground:1,bodystyle:"padding:0px;border:0px;background:none;"});
					var atitle='Processing Request';
					setCenterPopText(dname,txt,{title:atitle,drag:false,close_bot:false});
					}
				else if(this.showprocessing){
					setProcessing(this.showprocessingdiv);
					}
				}
          	,'onGroupEnd':function(req){
				//setText('ajaxstatus',' ');
				}
          	,'onTimeout':function(req){
				var dname = this.groupName;
				var cb=this.callback;
				cb=cb.toLowerCase();
				var val="<b style=\"color:red\">ajaxPost Timed Out Error</b>";
				if(cb.indexOf('centerpop') != -1 || dname.indexOf('centerpop') != -1){
					setCenterPopText(dname,val);
                    }
				else{setText(dname,val);}
				if(undefined != theform.setprocessing){
					setText(theform.setprocessing.value,'');
					}
				}
			,'onError':function(req){
				var dname = this.groupName;
				var cb=this.callback;
				cb=cb.toLowerCase();
				var val='<div style="display:none" id="ajaxOnError">'+req.responseText+'</div>';
				if(cb.indexOf('centerpop') != -1 || dname.indexOf('centerpop') != -1){
					setCenterPopText(dname,val);
                    }
				else{setText(dname,val);}
				if(undefined != theform.setprocessing){
					setText(theform.setprocessing.value,'');
					}
				}
			,'onSuccess':function(req){
				var dname=this.groupName;
				var lname=dname.toLowerCase();
				var cb=this.callback.toLowerCase();
				var val=req.responseText;
				ajaxids = GetElementsByAttribute('textarea', 'ajaxdiv',dname)
				for(i=0;i<ajaxids.length;i++){
					var cid=ajaxids[i].id;
					tinyMCE.execCommand('mceRemoveControl', false, cid);
					//console.log('ajaxPost - removed tinyMCE for '+cid+' found in '+dname);
				}
				if(undefined != theform.setprocessing){
					setText(theform.setprocessing.value,'');
					}
				if(cb != ''){
					if(cb.indexOf('popupdiv') != -1){
						popUpDiv(val,{id:dname,center:1,drag:1});
						centerObject(dname);
                    	}
                    else if(cb.indexOf('centerpop') != -1){
						setCenterPopText(dname,val);
                    	}
					else{
						var str=this.callback+'(req);';
						eval(str);
						}
                	}
				else{
					if(lname.indexOf('popupdiv') != -1){
						popUpDiv(val,{id:dname,center:1,drag:1});
						centerObject(dname);
                    	}
                    else if(lname.indexOf('centerpop') != -1){
						setCenterPopText(dname,val);
                    	}
					else if(document.getElementById(dname)){
						if(undefined == document.getElementById(dname).style.display || document.getElementById(dname).style.display=='none'){
							document.getElementById(dname).style.display='inline';
						}
						//check to see if this element is a nicedit behavior
						var obj=getObject(dname);
						var behavior=obj.getAttribute('data-behavior');
						var id=obj.getAttribute('id');
						if(undefined != behavior && (behavior=='wysiwyg' || behavior=='richtext' || behavior=='tinymce' || behavior=='nicedit') && undefined != nicEditors[id]){
            				nicEditors.findEditor(id).setContent(val);
						}
						setText(dname,val);
						}
					else{
						//alert('ajaxPost could not find object id:'+dname);
						}
					}
				if(isFunction('f_tcalInit')){f_tcalInit();}
				initBehaviors(dname);
				}
  			}
	  	);
	if(returnreq){return getReq;}
	return false;
	}
//--------------------------
function setCenterPopText(cpid,cptext,params){
	//usage: setCenterPopText('','hello there',{title:nice title});
	//params: title(text), drag(boolean), close_top(boolean),close_bot(boolean),center(boolean)
	if(undefined == params){params={};}
	if(undefined == params.title){params.title='';}
	if(undefined == params.drag){params.drag=true;}
	if(undefined == params.close_top){params.close_top=true;}
	if(undefined == params.close_bot){params.close_bot=true;}
	if(undefined == params.center){params.center=true;}
	var txt='';
	txt += '<div class="w_centerpop">'+"\n";
	if(params.title.length){
		txt += '	<div class="w_centerpop_title">'+params.title+'</div>'+"\n";
	}
	if(params.close_top){
		txt += '	<div class="w_centerpop_close_top" title="Click to close" onclick="ajaxAbort(\''+cpid+'\');"><img src="/wfiles/icons/Xbutton24.png" width="24" height="24" alt="close" /></div>'+"\n";
	}
	if(params.drag){
		txt += '	<div class="w_centerpop_drag" id="'+cpid+'_move" title="Click to drag"><img id="'+cpid+'_drag" src="/wfiles/icons/Xmove24.png" width="24" height="24" alt="move" /></div>'+"\n";
	}
	//txt += '	<div class="w_centerpop_content">'+"\n";
	txt += '		'+cptext+"\n";
	//txt += '	</div>'+"\n";

	txt += '	<img src="/wfiles/clear.gif" width="1" height="1" style="position:absolute;top:0px;right:5px;" onload="centerObject(\''+cpid+'\');" alt="" />'+"\n";
	if(params.close_bot){
		txt += '	<div class="w_centerpop_close_bot" title="Click to close" onclick="ajaxAbort(\''+cpid+'\');"><img src="/wfiles/icons/Xbutton24.png" width="24" height="24" alt="close" /></div>'+"\n";
	}
	txt += '</div>'+"\n";
	//set the text in cpid
	setText(cpid,txt);
	//center the object
	if(params.center){
		centerObject(cpid);
	}
	//make the object draggable
	if(params.drag){
		var dObj=getObject(cpid);
		var dObjMove=getObject(cpid+'_move');
		Drag.init(dObjMove,dObj);
		dObjMove.style.position='absolute';
		dObj.style.position='absolute';
	}
}
//--------------------------
function callWaSQL(id,name,params){
	var url='/cgi-bin/wasql.pl';
	ajaxGet(url,name,'&_view='+id+'&'+params);
	}
//--------------------------
function ajaxAbort(sid){
	if(typeof(AjaxRequest.ActiveAjaxGroupRequests[sid]) != 'undefined'){
		var req=AjaxRequest.ActiveAjaxGroupRequests[sid];
		//check for abort_callback
		if(undefined != req.abort_callback && req.abort_callback.length){
			req.status='aborted';
			var str=req.abort_callback+'(req);';
			eval(str);
        }
		req.xmlHttpRequest.abort();
		delete(req);
		if(sid.indexOf('centerpop') != -1){removeId(sid);}
		else{setText(sid,req.prevValue);}
		return false;
	}
	if(sid.indexOf('centerpop') != -1){removeId(sid);}
	else{setText(sid,'');}
	return false;
}
//--------------------------
function ajaxGet(url,sid,xparams,callback,tmeout,nosetprocess,returnreq,newtitle,newurl,abort_callback){
	//info: makes an AJAX request and returns request page contents to sid
	//get GUID cookie and pass it in
	//if params is a json string, use it instead of the other params...
	var params='';
	var cp_title='';
	//show processing div
	var showprocessingdiv=sid;
	//show processing
	var showprocessing=true;
	if(typeof(xparams) == 'object'){
    	if(undefined != xparams.callback){callback=xparams.callback;}
		if(undefined != xparams.abort_callback){abort_callback=xparams.abort_callback;}
    	if(undefined != xparams.timeout){tmeout=xparams.timeout;}
    	if(undefined != xparams.nosetprocess){
			if(xparams.nosetprocess){showprocessing=false;}
		}
		else if(undefined != xparams.showprocessing){
			if(!xparams.showprocessing){showprocessing=false;}
		}
		if(undefined != xparams.setprocessing){
			showprocessingdiv=xparams.setprocessing;
		}
		else if(undefined != xparams.showprocessingdiv){
			showprocessingdiv=xparams.showprocessingdiv;
		}

    	if(undefined != xparams.cp_title){cp_title=xparams.cp_title;}
    	//build a new params string
		for(var key in xparams){
			//skip keys that start with a dash - these are configuration settings
			if(key.indexOf("-")==0){continue;}
			if(key == 'callback'){continue;}
			if(key == 'abort_callback'){continue;}
			if(key == 'timeout'){continue;}
			if(key == 'nosetprocess'){continue;}
			if(key == 'showprocessing'){continue;}
			if(key == 'setprocessing'){continue;}
			if(key == 'showprocessingdiv'){continue;}
			if(key == 'cp_title'){continue;}
        	params=params+key+'='+xparams[key]+'&';
		}
	}
	else{params=xparams;}
	if(undefined == newtitle){
		if(undefined != xparams && undefined != xparams["-newtitle"]){newtitle=xparams["-newtitle"];}
		else{newtitle='';}
	}
	if(undefined == newurl){
		if(undefined != xparams && undefined != xparams["-newurl"]){newurl=xparams["-newurl"];}
		else{newurl='';}
	}
	if(undefined == callback){callback='';}
	if(undefined == abort_callback){abort_callback='';}
	if(undefined == returnreq){returnreq=false;}
	var guid=getCookie('GUID');
	//default timeout to 10 minutes with a 3 minute minimum
	if(undefined == tmeout){tmeout=600000;}
	if(tmeout < 180000){tmeout=180000;}

	if(undefined == nosetprocess){
    	if(nosetprocess){showprocessing=false;}
	}
	var lcsid=sid.toLowerCase();
	var cb=callback.toLowerCase();
	if(undefined == document.getElementById(sid) && cb.indexOf('popupdiv') == -1 && cb.indexOf('centerpop') == -1 && lcsid.indexOf('popupdiv') == -1 && lcsid.indexOf('centerpop') == -1){
		alert('Error in ajaxGet\n'+sid+' is not defined as a valid object id');
		return false;
    }
    if(typeof(AjaxRequest.ActiveAjaxGroupRequests[sid]) != 'undefined'){
		ajaxAbort(sid);
	}
	var getReq=AjaxRequest.get(
		{
    		'url':url+'?'+params,
    		'callback':callback,
			'abort_callback':abort_callback,
			'showprocessing':showprocessing,
			'showprocessingdiv':showprocessingdiv,
    		'timeout':tmeout,
    		'var2':cp_title,
    		'var3':newtitle,
    		'var4':newurl,
			'groupName':sid,
			'prevValue':getText(sid),
			'onGroupBegin':function(req){
				var dname=this.groupName;
				var lname=dname.toLowerCase();
				var cb=this.callback.toLowerCase();
				if(cb.indexOf('centerpop') != -1 || lname.indexOf('centerpop') != -1){
					var txt=getProcessingDiv(sid);
					popUpDiv('',{id:dname,width:300,height:50,notop:1,nobot:1,noborder:1,nobackground:1,bodystyle:"padding:10px;border:0px;background:#FFF;"});
					var atitle='Processing Request';
					setCenterPopText(dname,txt,{title:atitle,drag:false,close_bot:false});
					}
				else if(this.showprocessing){
					setProcessing(this.showprocessingdiv);
					}
				},
			'onTimeout':function(req){
				var dname = this.groupName;
				var lname=dname.toLowerCase();
				var cb=this.callback.toLowerCase();
				var val="<b style=\"color:red\">ajaxGet Timed Out Error</b>";
				if(cb.indexOf('centerpop') != -1 || lname.indexOf('centerpop') != -1){
					setCenterPopText(dname,val);
                    }
				else{setText(dname,val);}
				},
			'onError':function(req){
				var dname = this.groupName;
				var lname=dname.toLowerCase();
				var cb=this.callback.toLowerCase();
				var val='<div style="display:none" id="ajaxOnError">'+req.responseText+'</div>';
				if(cb.indexOf('centerpop') != -1 || lname.indexOf('centerpop') != -1){
					setCenterPopText(dname,val);
                    }
				else{setText(dname,val);}
				},
    		'onSuccess':function(req){
				var dname=this.groupName;
				var lname=dname.toLowerCase();
				var cb=this.callback.toLowerCase();
				var val=req.responseText;
				ajaxids = GetElementsByAttribute('textarea', 'ajaxdiv',dname)
				for(i=0;i<ajaxids.length;i++){
					var cid=ajaxids[i].id;
					tinyMCE.execCommand('mceRemoveControl', false, cid);
					//console.log('ajaxPost - removed tinyMCE for '+cid+' found in '+dname);
				}
				//update the url and history if requested
				//http://stackoverflow.com/questions/11932869/how-to-dynamically-change-url-without-reloading
				if(this.var3.length > 0 && this.var4.length > 0){
                	history.pushState(req.responseText, this.var3, this.var4);
				}
				if(cb != ''){
					if(cb.indexOf('popupdiv') != -1){
						var val=req.responseText;
						popUpDiv(val,{id:dname,center:1,drag:1});
						centerObject(dname);
                    }
                    else if(cb.indexOf('centerpop') != -1){
						if(undefined != this.var2 && this.var2.length > 0){
							setCenterPopText(dname,req.responseText,{title:this.var2});
						}
						else{
                        	setCenterPopText(dname,req.responseText);
						}
                    }
					else{
						var str=this.callback+'(req);';
						eval(str);
						}
                	}
				else{
					if(lname.indexOf('popupdiv') != -1){
						var val=req.responseText;
						popUpDiv(val,{id:dname,center:1,drag:1});
						centerObject(dname);
                    }
                    else if(lname.indexOf('centerpop') != -1){
						if(undefined != this.var2 && this.var2.length > 0){
							setCenterPopText(dname,req.responseText,{title:this.var2});
						}
						else{
                        	setCenterPopText(dname,req.responseText);
						}
                    }
					else if(document.getElementById(dname)){
						if(undefined == document.getElementById(dname).style.display || document.getElementById(dname).style.display=='none'){
							document.getElementById(dname).style.display='inline';
						}
						//check to see if this element is a nicedit behavior
						var obj=getObject(dname);
						var behavior=obj.getAttribute('data-behavior');
						var id=obj.getAttribute('id');
						if(undefined != behavior && (behavior=='wysiwyg' || behavior=='richtext' || behavior=='tinymce' || behavior=='nicedit') && undefined != nicEditors[id]){
            				nicEditors.findEditor(id).setContent(val);
						}
						setText(dname,val);
						//center object if onshow="center"
						if(undefined != document.getElementById(dname).getAttribute('onshow') && document.getElementById(dname).getAttribute('onshow')=='center'){
							document.getElementById(dname).style.position='absolute';
							centerObject(dname);
							}
						}
					else{
						//alert('ajaxGet could not find object id:'+dname);
						}
					}
				initBehaviors(dname);
				}
  			}
		);
	if(returnreq){return getReq;}
	return false;
	}
//--------------------------
function newXmlHttpRequest(){
	if (window.XMLHttpRequest) {return new XMLHttpRequest();}
	else if (window.ActiveXObject) {
		// Based on http://jibbering.com/2002/4/httprequest.html
		/*@cc_on @*/
		/*@if (@_jscript_version >= 5)
		try {
			return new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			try {
				return new ActiveXObject("Microsoft.XMLHTTP");
			} catch (E) {
				return null;
			}
		}
		@end @*/
	}
	else {
		return null;
	}
}
function wpassInput(v){
	if(!isNum(v)){return true;}
	var obj=getObject('wpass_search');
	if(undefined == obj){return;}
	//blank out the search form
	obj.value='';
	ajaxGet('/php/index.php','wpass_info',{_wpass:v});
	return false;
}
//--------------------------
// Parts Taken from  http://www.AjaxToolbox.com/
function AjaxRequest() {
	var req = new Object();
	
	// -------------------
	// Instance properties
	// -------------------

	/**
	 * Timeout period (in ms) until an async request will be aborted, and
	 * the onTimeout function will be called
	 */
	req.timeout = null;
	
	/**
	 *	Since some browsers cache GET requests via XMLHttpRequest, an
	 * additional parameter called AjaxRequestUniqueId will be added to
	 * the request URI with a unique numeric value appended so that the requested
	 * URL will not be cached.
	 */
	req.generateUniqueUrl = true;
	
	/**
	 * The url that the request will be made to, which defaults to the current 
	 * url of the window
	 */
	req.url = window.location.href;
	
	/**
	 * The method of the request, either GET (default), POST, or HEAD
	 */
	req.method = "GET";
	
	/**
	 * Whether or not the request will be asynchronous. In general, synchronous 
	 * requests should not be used so this should rarely be changed from true
	 */
	req.async = true;
	
	/**
	 * The username used to access the URL
	 */
	req.username = null;
	
	/**
	 * The password used to access the URL
	 */
	req.password = null;
	
	/**
	 * Generic fields for user to pass through data
	 */
	req.xAttribute = null;
	req.xValue = null;
	req.xName = null;
	req.xAction = null;
	req.xId = null;
	req.prevValue = null;

	/**
	 * The parameters is an object holding name/value pairs which will be 
	 * added to the url for a GET request or the request content for a POST request
	 */
	req.parameters = new Object();
	
	/**
	 * The sequential index number of this request, updated internally
	 */
	req.requestIndex = AjaxRequest.numAjaxRequests++;
	
	/**
	 * Indicates whether a response has been received yet from the server
	 */
	req.responseReceived = false;
	
	/**
	 * Indicates whether to show processing message
	 */
	req.showprocessing = true;
	
	/**
	 * div to show processing in if showprocessing is true
	 */
	req.showprocessingdiv = null;

	/**
	 * The name of the group that this request belongs to, for activity 
	 * monitoring purposes
	 */
	req.groupName = null;
	req.callback = null;
	req.abort_callback = null;
	req.var1 = null;
	req.var2 = null;
	req.var3 = null;
	req.var4 = null;
	req.var5 = null;
	req.AjaxRequestUniqueId = null;
	
	/**
	 * The query string to be added to the end of a GET request, in proper 
	 * URIEncoded format
	 */
	req.queryString = "";
	
	/**
	 * After a response has been received, this will hold the text contents of 
	 * the response - even in case of error
	 */
	req.responseText = null;
	
	/**
	 * After a response has been received, this will hold the XML content
	 */
	req.responseXML = null;
	
	/**
	 * After a response has been received, this will hold the status code of 
	 * the response as returned by the server.
	 */
	req.status = null;
	
	/**
	 * After a response has been received, this will hold the text description 
	 * of the response code
	 */
	req.statusText = null;

	/**
	 * An internal flag to indicate whether the request has been aborted
	 */
	req.aborted = false;
	
	/**
	 * The XMLHttpRequest object used internally
	 */
	req.xmlHttpRequest = null;

	// --------------
	// Event handlers
	// --------------
	
	/**
	 * If a timeout period is set, and it is reached before a response is 
	 * received, a function reference assigned to onTimeout will be called
	 */
	req.onTimeout = null; 
	
	/**
	 * A function reference assigned will be called when readyState=1
	 */
	req.onLoading = null;

	/**
	 * A function reference assigned will be called when readyState=2
	 */
	req.onLoaded = null;

	/**
	 * A function reference assigned will be called when readyState=3
	 */
	req.onInteractive = null;

	/**
	 * A function reference assigned will be called when readyState=4
	 */
	req.onComplete = null;

	/**
	 * A function reference assigned will be called after onComplete, if
	 * the statusCode=200
	 */
	req.onSuccess = null;
	

	/**
	 * A function reference assigned will be called after onComplete, if 
	 * the statusCode != 200
	 */
	req.onError = null;
	
	/**
	 * If this request has a group name, this function reference will be called 
	 * and passed the group name if this is the first request in the group to 
	 * become active
	 */
	req.onGroupBegin = null;

	/**
	 * If this request has a group name, and this request is the last request 
	 * in the group to complete, this function reference will be called
	 */
	req.onGroupEnd = null;

	// Get the XMLHttpRequest object itself
	req.xmlHttpRequest = AjaxRequest.getXmlHttpRequest();
	if (req.xmlHttpRequest==null) { return null; }
	
	// -------------------------------------------------------
	// Attach the event handlers for the XMLHttpRequest object
	// -------------------------------------------------------
	req.xmlHttpRequest.onreadystatechange = 
	function() {
		if (req==null || req.xmlHttpRequest==null) { return; }
		if (req.xmlHttpRequest.readyState==1) { req.onLoadingInternal(req); }
		if (req.xmlHttpRequest.readyState==2) { req.onLoadedInternal(req); }
		if (req.xmlHttpRequest.readyState==3) { req.onInteractiveInternal(req); }
		if (req.xmlHttpRequest.readyState==4) { req.onCompleteInternal(req); }
	};
	
	// ---------------------------------------------------------------------------
	// Internal event handlers that fire, and in turn fire the user event handlers
	// ---------------------------------------------------------------------------
	// Flags to keep track if each event has been handled, in case of 
	// multiple calls (some browsers may call the onreadystatechange 
	// multiple times for the same state)
	req.onLoadingInternalHandled = false;
	req.onLoadedInternalHandled = false;
	req.onInteractiveInternalHandled = false;
	req.onCompleteInternalHandled = false;
	req.onLoadingInternal = 
		function() {
			if (req.onLoadingInternalHandled) { return; }
			AjaxRequest.numActiveAjaxRequests++;
			if (AjaxRequest.numActiveAjaxRequests==1 && typeof(window['AjaxRequestBegin'])=="function") {
				AjaxRequestBegin();
				//AjaxRequest.ActiveAjaxRequests=new Array();
			}
			if (req.groupName!=null) {
				if (typeof(AjaxRequest.numActiveAjaxGroupRequests[req.groupName])=="undefined") {
					AjaxRequest.numActiveAjaxGroupRequests[req.groupName] = 0;
					AjaxRequest.ActiveAjaxGroupRequests[req.groupName]=req;
				}
				else{
                	AjaxRequest.ActiveAjaxGroupRequests[req.groupName]=req;
				}
				AjaxRequest.numActiveAjaxGroupRequests[req.groupName]++;
				if (AjaxRequest.numActiveAjaxGroupRequests[req.groupName]==1 && typeof(req.onGroupBegin)=="function") {
					req.onGroupBegin(req.groupName);
				}
			}
			if (typeof(req.onLoading)=="function") {
				req.onLoading(req);
			}
			req.onLoadingInternalHandled = true;
		};
	req.onLoadedInternal = 
		function() {
			if (req.onLoadedInternalHandled) { return; }
			if (typeof(req.onLoaded)=="function") {
				req.onLoaded(req);
			}
			req.onLoadedInternalHandled = true;
		};
	req.onInteractiveInternal = 
		function() {
			if (req.onInteractiveInternalHandled) { return; }
			if (typeof(req.onInteractive)=="function") {
				req.onInteractive(req);
			}
			req.onInteractiveInternalHandled = true;
		};
	req.onCompleteInternal = 
		function() {
			if (req.onCompleteInternalHandled || req.aborted) { 
				return;
			}
			req.onCompleteInternalHandled = true;
			AjaxRequest.numActiveAjaxRequests--;
			if (AjaxRequest.numActiveAjaxRequests==0 && typeof(window['AjaxRequestEnd'])=="function") {
				AjaxRequestEnd(req.groupName);
			}
			if (req.groupName!=null) {
				AjaxRequest.numActiveAjaxGroupRequests[req.groupName]--;
				delete(AjaxRequest.ActiveAjaxGroupRequests[req.groupName]);
				if (AjaxRequest.numActiveAjaxGroupRequests[req.groupName]==0 && typeof(req.onGroupEnd)=="function") {
					req.onGroupEnd(req.groupName);
				}
			}
			req.responseReceived = true;
			req.status = req.xmlHttpRequest.status;
			req.statusText = req.xmlHttpRequest.statusText;
			req.responseText = req.xmlHttpRequest.responseText;
			req.responseXML = req.xmlHttpRequest.responseXML;
			if (typeof(req.onComplete)=="function") {
				req.onComplete(req);
			}
			if (req.xmlHttpRequest.status==200 && typeof(req.onSuccess)=="function") {
				req.onSuccess(req);
			}
			else if (typeof(req.onError)=="function") {
				req.onError(req);
			}

			// Clean up so IE doesn't leak memory
			delete req.xmlHttpRequest['onreadystatechange'];
			req.xmlHttpRequest = null;
		};
	req.onTimeoutInternal = 
		function() {
			if (req!=null && req.xmlHttpRequest!=null && !req.onCompleteInternalHandled) {
				req.aborted = true;
				req.xmlHttpRequest.abort();
				AjaxRequest.numActiveAjaxRequests--;
				if (AjaxRequest.numActiveAjaxRequests==0 && typeof(window['AjaxRequestEnd'])=="function") {
					AjaxRequestEnd(req.groupName);
				}
				if (req.groupName!=null) {
					AjaxRequest.numActiveAjaxGroupRequests[req.groupName]--;
					delete(AjaxRequest.ActiveAjaxGroupRequests[req.groupName]);
					if (AjaxRequest.numActiveAjaxGroupRequests[req.groupName]==0 && typeof(req.onGroupEnd)=="function") {
						req.onGroupEnd(req.groupName);
					}
				}
				if (typeof(req.onTimeout)=="function") {
					req.onTimeout(req);
				}
			// Opera won't fire onreadystatechange after abort, but other browsers do. 
			// So we can't rely on the onreadystate function getting called. Clean up here!
			delete req.xmlHttpRequest['onreadystatechange'];
			req.xmlHttpRequest = null;
			}
		};

	// ----------------
	// Instance methods
	// ----------------
	/**
	 * The process method is called to actually make the request. It builds the
	 * querystring for GET requests (the content for POST requests), sets the
	 * appropriate headers if necessary, and calls the 
	 * XMLHttpRequest.send() method
	*/
	req.process = 
		function() {
			if (req.xmlHttpRequest!=null) {
				// Some logic to get the real request URL
				if (req.generateUniqueUrl && req.method=="GET") {
					req.parameters["AjaxRequestUniqueId"] = new Date().getTime() + "" + req.requestIndex;
				}
				var content = null; // For POST requests, to hold query string
				for (var i in req.parameters) {
					if (req.queryString.length>0) { req.queryString += "&"; }
					req.queryString += encodeURIComponent(i) + "=" + encodeURIComponent(req.parameters[i]);
				}
				if (req.method=="GET") {
					if (req.queryString.length>0) {
						req.url += ((req.url.indexOf("?")>-1)?"&":"?") + req.queryString;
					}
				}
				req.xmlHttpRequest.open(req.method,req.url,req.async,req.username,req.password);
				if (req.method=="POST") {
					if (typeof(req.xmlHttpRequest.setRequestHeader)!="undefined") {
						req.xmlHttpRequest.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
					}
					content = req.queryString;
				}
				if (req.timeout>0) {
					setTimeout(req.onTimeoutInternal,req.timeout);
				}
				req.xmlHttpRequest.send(content);
			}
		};

	/**
	 * An internal function to handle an Object argument, which may contain
	 * either AjaxRequest field values or parameter name/values
	 */
	req.handleArguments = 
		function(args) {
			for (var i in args) {
				// If the AjaxRequest object doesn't have a property which was passed, treat it as a url parameter
				if (typeof(req[i])=="undefined") {
					req.parameters[i] = args[i];
				}
				else {
					req[i] = args[i];
				}
			}
		};

	/**
	 * Returns the results of XMLHttpRequest.getAllResponseHeaders().
	 * Only available after a response has been returned
	 */
	req.getAllResponseHeaders =
		function() {
			if (req.xmlHttpRequest!=null) {
				if (req.responseReceived) {
					return req.xmlHttpRequest.getAllResponseHeaders();
				}
				alert("Cannot getAllResponseHeaders because a response has not yet been received");
			}
		};

	/**
	 * Returns the the value of a response header as returned by 
	 * XMLHttpRequest,getResponseHeader().
	 * Only available after a response has been returned
	 */
	req.getResponseHeader =
		function(headerName) {
			if (req.xmlHttpRequest!=null) {
				if (req.responseReceived) {
					return req.xmlHttpRequest.getResponseHeader(headerName);
				}
				alert("Cannot getResponseHeader because a response has not yet been received");
			}
		};

	return req;
}

// ---------------------------------------
// Static methods of the AjaxRequest class
// ---------------------------------------

/**
 * Returns an XMLHttpRequest object, either as a core object or an ActiveX 
 * implementation. If an object cannot be instantiated, it will return null;
 */
AjaxRequest.getXmlHttpRequest = function() {
	if (window.XMLHttpRequest) {
		return new XMLHttpRequest();
	}
	else if (window.ActiveXObject) {
		// Based on http://jibbering.com/2002/4/httprequest.html
		/*@cc_on @*/
		/*@if (@_jscript_version >= 5)
		try {
			return new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			try {
				return new ActiveXObject("Microsoft.XMLHTTP");
			} catch (E) {
				return null;
			}
		}
		@end @*/
	}
	else {
		return null;
	}
};

/**
 * See if any request is active in the background
 */
AjaxRequest.isActive = function() {
	return (AjaxRequest.numActiveAjaxRequests>0);
};

/**
 * Make a GET request. Pass an object containing parameters and arguments as 
 * the second argument.
 * These areguments may be either AjaxRequest properties to set on the request 
 * object or name/values to set in the request querystring.
 */
AjaxRequest.get = function(args) {
	AjaxRequest.doRequest("GET",args);
};

/**
 * Make a POST request. Pass an object containing parameters and arguments as 
 * the second argument.
 * These arguments may be either AjaxRequest properties to set on the request 
 * object or name/values to set in the request querystring.
 */
AjaxRequest.post = function(args) {
	AjaxRequest.doRequest("POST",args);
};

/**
 * The internal method used by the .get() and .post() methods
 */
AjaxRequest.doRequest = function(method,args) {
	if (typeof(args)!="undefined" && args!=null) {
		var myRequest = new AjaxRequest();
		myRequest.method = method;
		myRequest.handleArguments(args);
		myRequest.process();
	}
}	;

/**
 * Submit a form. The requested URL will be the form's ACTION, and the request 
 * method will be the form's METHOD.
 * Returns true if the submittal was handled successfully, else false so it 
 * can easily be used with an onSubmit event for a form, and fallback to 
 * submitting the form normally.
 */
AjaxRequest.submit = function(theform, args) {
	var myRequest = new AjaxRequest();
	if (myRequest==null) { return false; }
	var serializedForm = AjaxRequest.serializeForm(theform);
	myRequest.method = theform.method.toUpperCase();
	myRequest.url = theform.action;
	myRequest.handleArguments(args);
	myRequest.queryString = serializedForm;
	myRequest.process();
	return true;
};

/**
 * Serialize a form into a format which can be sent as a GET string or a POST 
 * content.It correctly ignores disabled fields, maintains order of the fields 
 * as in the elements[] array. The 'file' input type is not supported, as 
 * its content is not available to javascript. This method is used internally
 * by the submit class method.
 */
AjaxRequest.serializeForm = function(theform) {
	var els = theform.elements;
	if(undefined==els){alert('Ajax Request serializeForm failed');return false;}
	var len = els.length;
	var queryString = "";
	this.addField = 
		function(name,value) { 
			if (queryString.length>0) { 
				queryString += "&";
			}
			queryString += encodeURIComponent(name) + "=" + encodeURIComponent(value);
		};
	for (var i=0; i<len; i++) {
		var el = els[i];
		if (!el.disabled) {
			switch(el.type) {
				//new HTML5 input types
				case 'color':
				case 'date':
				case 'datetime':
				case 'datetime-local':
				case 'email':
				case 'month':
				case 'number':
				case 'range':
				case 'search':
				case 'tel':
				case 'time':
				case 'url':
				case 'week':
				//Standard HTML input types
				case 'text':
				case 'password': 
				case 'submit': 
				case 'hidden': 
				case 'textarea':
					this.addField(el.name,el.value);
					break;
				case 'select-one':
					if (el.selectedIndex>=0) {
						this.addField(el.name,el.options[el.selectedIndex].value);
					}
					break;
				case 'select-multiple':
					for (var j=0; j<el.options.length; j++) {
						if (el.options[j].selected) {
							this.addField(el.name,el.options[j].value);
						}
					}
					break;
				case 'checkbox': 
				case 'radio':
					if (el.checked) {
						this.addField(el.name,el.value);
					}
					break;
			}
		}
	}
	return queryString;
};

// -----------------------
// Static Class variables
// -----------------------

/**
 * The number of total AjaxRequest objects currently active and running
 */
AjaxRequest.numActiveAjaxRequests = 0;

/**
 * An object holding the number of active requests for each group
 */
AjaxRequest.numActiveAjaxGroupRequests = new Object();

/**
 * An object holding the  active requests for each group
 */
AjaxRequest.ActiveAjaxGroupRequests = new Object();

/**
 * The total number of AjaxRequest objects instantiated
 */
AjaxRequest.numAjaxRequests = 0;

/*
html5slider - a JS implementation of <input type=range> for Firefox 16 and up
https://github.com/fryn/html5slider

Copyright (c) 2010-2012 Frank Yan, <http://frankyan.com>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

(function() {
	// test for native support
	var test = document.createElement('input');
	try {
  		test.type = 'range';
  		if (test.type == 'range'){
			//already supported - break out
			return;
		}
	} 
	catch (e) {
		//showProperties(e);
		return;
		}
	// test for required property support
	test.style.background = 'linear-gradient(red, red)';
	if (!test.style.backgroundImage || !('MozAppearance' in test.style) || !document.mozSetImageElement || !this.MutationObserver){
		return;
	}
	var scale;
	var isMac = navigator.platform == 'MacIntel';
	var thumb = {
  		radius: isMac ? 9 : 6,
  		width: isMac ? 22 : 12,
  		height: isMac ? 16 : 20
	};
	var track = 'linear-gradient(transparent ' + (isMac ?
  		'6px, #999 6px, #999 7px, #ccc 8px, #bbb 9px, #bbb 10px, transparent 10px' :
  		'9px, #999 9px, #bbb 10px, #fff 11px, transparent 11px') +
  		', transparent)';
	var styles = {
  		'min-width': thumb.width + 'px',
  		'min-height': thumb.height + 'px',
  		'max-height': thumb.height + 'px',
  		padding: '0 0 ' + (isMac ? '2px' : '1px'),
  		border: 0,
  		'border-radius': 0,
  		cursor: 'default',
  		'text-indent': '-999999px' // -moz-user-select: none; breaks mouse capture
	};
	var options = {
		attributes: true,
		attributeFilter: ['min', 'max', 'step', 'value']
	};
	var forEach = Array.prototype.forEach;
	var onChange = document.createEvent('HTMLEvents');
	onChange.initEvent('change', true, false);
	if (document.readyState == 'loading'){
  		document.addEventListener('DOMContentLoaded', initialize, true);
	}
	else{
  		initialize();
	}
	//------
	function initialize() {
	  // create initial sliders
	  forEach.call(document.querySelectorAll('input[type=range]'), transform);
	  // create sliders on-the-fly
	  new MutationObserver(function(mutations) {
	    mutations.forEach(function(mutation) {
	      if (mutation.addedNodes)
	        forEach.call(mutation.addedNodes, function(node) {
	          check(node);
	          if (node.childElementCount)
	            forEach.call(node.querySelectorAll('input'), check);
	        });
	    });
	  }).observe(document, { childList: true, subtree: true });
	}
	//--------
	function check(input) {
	  if (input.localName == 'input' && input.type != 'range' &&
	      input.getAttribute('type') == 'range')
	    transform(input);
	}
	//------
	function transform(slider) {
	  var isValueSet, areAttrsSet, isChanged, isClick, prevValue, rawValue, prevX;
	  var min, max, step, range, value = slider.value;
	  // lazily create shared slider affordance
	  if (!scale) {
	    scale = document.body.appendChild(document.createElement('hr'));
	    style(scale, {
	      '-moz-appearance': isMac ? 'scale-horizontal' : 'scalethumb-horizontal',
	      display: 'block',
	      visibility: 'visible',
	      opacity: 1,
	      position: 'fixed',
	      top: '-999999px'
	    });
	    document.mozSetImageElement('__sliderthumb__', scale);
	  }
	
	  // reimplement value and type properties
	  var getValue = function() { return '' + value; };
	  var setValue = function setValue(val) {
	    value = '' + val;
	    isValueSet = true;
	    draw();
	    delete slider.value;
	    slider.value = value;
	    slider.__defineGetter__('value', getValue);
	    slider.__defineSetter__('value', setValue);
	  };
	  slider.__defineGetter__('value', getValue);
	  slider.__defineSetter__('value', setValue);
	  slider.__defineGetter__('type', function() { return 'range'; });
	
	  // sync properties with attributes
	  ['min', 'max', 'step'].forEach(function(prop) {
	    if (slider.hasAttribute(prop))
	      areAttrsSet = true;
	    slider.__defineGetter__(prop, function() {
	      return this.hasAttribute(prop) ? this.getAttribute(prop) : '';
	    });
	    slider.__defineSetter__(prop, function(val) {
	      val === null ? this.removeAttribute(prop) : this.setAttribute(prop, val);
	    });
	  });
	
	  // initialize slider
	  slider.readOnly = true;
	  style(slider, styles);
	  update();
	
	  new MutationObserver(function(mutations) {
	    mutations.forEach(function(mutation) {
	      if (mutation.attributeName != 'value') {
	        update();
	        areAttrsSet = true;
	      }
	      // note that value attribute only sets initial value
	      else if (!isValueSet) {
	        value = slider.getAttribute('value');
	        draw();
	      }
	    });
	  }).observe(slider, options);
	
	  slider.addEventListener('mousedown', onDragStart, true);
	  slider.addEventListener('keydown', onKeyDown, true);
	  slider.addEventListener('focus', onFocus, true);
	  slider.addEventListener('blur', onBlur, true);
	
	  function onDragStart(e) {
	    isClick = true;
	    setTimeout(function() { isClick = false; }, 0);
	    if (e.button || !range)
	      return;
	    var width = parseFloat(getComputedStyle(this, 0).width);
	    var multiplier = (width - thumb.width) / range;
	    if (!multiplier)
	      return;
	    // distance between click and center of thumb
	    var dev = e.clientX - this.getBoundingClientRect().left - thumb.width / 2 -
	              (value - min) * multiplier;
	    // if click was not on thumb, move thumb to click location
	    if (Math.abs(dev) > thumb.radius) {
	      isChanged = true;
	      this.value -= -dev / multiplier;
	    }
	    rawValue = value;
	    prevX = e.clientX;
	    this.addEventListener('mousemove', onDrag, true);
	    this.addEventListener('mouseup', onDragEnd, true);
	  }
	
	  function onDrag(e) {
	    var width = parseFloat(getComputedStyle(this, 0).width);
	    var multiplier = (width - thumb.width) / range;
	    if (!multiplier)
	      return;
	    rawValue += (e.clientX - prevX) / multiplier;
	    prevX = e.clientX;
	    isChanged = true;
	    this.value = rawValue;
	  }
	
	  function onDragEnd() {
	    this.removeEventListener('mousemove', onDrag, true);
	    this.removeEventListener('mouseup', onDragEnd, true);
	  }
	
	  function onKeyDown(e) {
	    if (e.keyCode > 36 && e.keyCode < 41) { // 37-40: left, up, right, down
	      onFocus.call(this);
	      isChanged = true;
	      this.value = value + (e.keyCode == 38 || e.keyCode == 39 ? step : -step);
	    }
	  }
	
	  function onFocus() {
	    if (!isClick)
	      this.style.boxShadow = !isMac ? '0 0 0 2px #fb0' :
	        'inset 0 0 20px rgba(0,127,255,.1), 0 0 1px rgba(0,127,255,.4)';
	  }
	
	  function onBlur() {
	    this.style.boxShadow = '';
	  }
	
	  // determines whether value is valid number in attribute form
	  function isAttrNum(value) {
	    return !isNaN(value) && +value == parseFloat(value);
	  }
	
	  // validates min, max, and step attributes and redraws
	  function update() {
	    min = isAttrNum(slider.min) ? +slider.min : 0;
	    max = isAttrNum(slider.max) ? +slider.max : 100;
	    if (max < min)
	      max = min > 100 ? min : 100;
	    step = isAttrNum(slider.step) && slider.step > 0 ? +slider.step : 1;
	    range = max - min;
	    draw(true);
	  }
	
	  // recalculates value property
	  function calc() {
	    if (!isValueSet && !areAttrsSet)
	      value = slider.getAttribute('value');
	    if (!isAttrNum(value))
	      value = (min + max) / 2;;
	    // snap to step intervals (WebKit sometimes does not - bug?)
	    value = Math.round((value - min) / step) * step + min;
	    if (value < min)
	      value = min;
	    else if (value > max)
	      value = min + ~~(range / step) * step;
	  }
	
	  // renders slider using CSS background ;)
	  function draw(attrsModified) {
	    calc();
	    if (isChanged && value != prevValue)
	      slider.dispatchEvent(onChange);
	    isChanged = false;
	    if (!attrsModified && value == prevValue)
	      return;
	    prevValue = value;
	    var position = range ? (value - min) / range * 100 : 0;
	    var bg = '-moz-element(#__sliderthumb__) ' + position + '% no-repeat, ';
	    style(slider, { background: bg + track });
	  }
	
	}
	//-----
	function style(element, styles) {
	  for (var prop in styles)
	    element.style.setProperty(prop, styles[prop], 'important');
	}
})();


