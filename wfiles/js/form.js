/* form-based, get, post, ajax javascript routines*/
/* - Required dependancies: common.js 			 */
/*----------------------------------------------*/
function formChanged(frm){
	let els=frm.querySelectorAll('[data-displayif]');
	for(let i=0;i<els.length;i++){
		let ifel=frm.querySelectorAll('[name="'+els[i].dataset.displayif+'"]');
		if(undefined == ifel){continue;}
		//console.log(els[i].dataset.displayif+' = '+ifel.length);
		if(ifel.length > 0){
			let display=0;
			for(let f=0;f<ifel.length;f++){
				let cval='';
				switch(ifel[f].type.toLowerCase()){
					case 'select-one':
            			cval=ifel[f].options[ifel[f].selectedIndex].value;
            			if(cval.toLowerCase() == 1 ){display=1;}
            			if(cval.toLowerCase() == 'y' ){display=1;}
            			if(cval.toLowerCase() == 'yes' ){display=1;}
					break;
					case 'radio':
					case 'checkbox':
						if(ifel[f].checked){cval=ifel[f].value||1;}
						if(cval.toLowerCase() == 1 ){display=1;}
            			if(cval.toLowerCase() == 'y' ){display=1;}
            			if(cval.toLowerCase() == 'yes' ){display=1;}
					break;
					case 'textarea':
						if(trim(ifel.innerText).length){display=1;}
					break;
					default:
						cval=ifel[f].value;
						if(cval.toLowerCase() == 1 ){display=1;}
            			if(cval.toLowerCase() == 'y' ){display=1;}
            			if(cval.toLowerCase() == 'yes' ){display=1;}
					break;
				}
			}
			//console.log(display);
			//console.log(els[i]);
			if(display==1){
				if(undefined != els[i].dataset.display){
					els[i].style.display=els[i].dataset.display;	
				}
				else{els[i].style.display='block';}
			}
			else{
				els[i].style.display='none';
			}
		}
		else{
			els[i].display='none';
		}
	}
}
function setInputFileName(fld){
	//console.log(fld.files);
	let multiple=0;
	if(fld.multiple){multiple=1;}
	let label=document.querySelector('label[for='+fld.id+']');
	let labeltxt=document.querySelector('label[for='+fld.id+'] span.input_file_text');
	if(undefined == labeltxt.dataset.text){
		labeltxt.dataset.text=getText(labeltxt);
	}
	if(undefined == fld.files || fld.files.length==0){
		setText(labeltxt,labeltxt.dataset.text);
		return;
	}
	if(multiple==0){
		setText(labeltxt,'');
	}
	for(let f=0;f<fld.files.length;f++){
		let reader = new FileReader();
		reader.label=label;
		reader.labeltxt=labeltxt;
		reader.fld=fld;
		reader.cfile=fld.files[f];
		reader.filename=fld.files[f].name;
		reader.f=f;
		reader.fmax=fld.files.length-1;
		reader.onload = function (e) {
			let ext=this.filename.split('.').pop().toLowerCase();
			if(this.result.indexOf('data:image') == 0){
				let img=document.createElement('img');
				img.style.display='inline';
				img.style.height='24px';
				img.src=e.target.result;
				img.title=this.filename;
				this.labeltxt.appendChild(img);
			}
			else if(this.result.indexOf('data:audio') == 0){
				let span=document.createElement('span');
				span.className='w_gray icon-file-audio';
				span.style.fontSize='26px';
				span.title=this.filename;
				this.labeltxt.appendChild(span);
			}
			else if(this.result.indexOf('data:video') == 0){
				let span=document.createElement('span');
				span.className='w_gray icon-file-video';
				span.style.fontSize='26px';
				span.title=this.filename;
				this.labeltxt.appendChild(span);
			}
			else{
				let cname='w_gray ';
				switch(ext){
					case 'pdf':cname=cname+'icon-file-pdf2';break;
					case 'xls':
					case 'xlsx':
						cname=cname+'icon-file-excel';
					break;
					case 'doc':
					case 'docx':
						cname=cname+'icon-file-word';
					break;
					case 'zip':
					case 'gz':
						cname=cname+'icon-file-zip';
					break;
					case 'txt':
					case 'csv':
						cname=cname+'icon-file-txt';
					break;
					default:
						cname=cname+'icon-file-doc';
					break;
				}
				let span=document.createElement('span');
				span.className=cname;
				span.style.fontSize='26px';
				span.title=this.filename;
				this.labeltxt.appendChild(span);
			}
			if(this.f==this.fmax && undefined == labeltxt.querySelector('span.icon-erase')){
				let span=document.createElement('span');
				span.className='w_danger icon-erase';
				span.style.fontSize='16px';
				span.style.marginLeft='10px';
				span.title='Clear';
				span.fld=fld;
				span.labeltxt=labeltxt;
				span.onclick=function(e){
					cancelBubble(e);
					this.fld.value='';
					setText(this.labeltxt,this.labeltxt.dataset.text);
				}
				labeltxt.appendChild(span);
			}
		}
		reader.readAsDataURL(fld.files[f]);
	}
}
function formClearFileField(id){
	
}
function formSendPhoneAuth(el){
	let phone_id=el.getAttribute('data-username_id');
	let phone=getText(phone_id);
	if(phone.length < 10){
		alert('Enter Valid Phone Number to authorize');
		return false;
	}
	return ajaxGet('/php/index.php',el.id,{send_phone_auth:1,phone:phone});
}
function formShowPassword(id,sh){
	let obj=getObject(id);
	if(undefined == id){return false;}
	if(undefined == sh){return false;}
	if(undefined == obj){return false;}
	if(sh==1){
		obj.setAttribute('type','text');
	}
	else{
		obj.setAttribute('type','password');
	}
	return false;
}
/** attachDropFiles **/
/** https://thiscouldbebetter.wordpress.com/2013/07/03/converting-a-file-to-a-base64-dataurl-in-javascript/ **/
function attachDropFiles(fld){
	var filesSelected = fld.files;
	if (filesSelected.length === 0){return;}
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
				txtarea.setAttribute('data-filename',this.fname);
				txtarea.innerHTML = 'filename:'+this.fname+';'+fileLoadedEvent.target.result;
			}
		};
		fileReader.readAsDataURL(fileToLoad);
	}
}
function formSetFrequency(fid,v){
	let container=document.querySelector('#'+fid+'_container');
	if(undefined==container){return false;}
	let minutes=container.querySelectorAll('input.frequency_minute');
	let hours=container.querySelectorAll('input.frequency_hour');
	let months=container.querySelectorAll('input.frequency_month');
	let days=container.querySelectorAll('input.frequency_day');
	let field=container.querySelector('#'+fid);
	let nv={minute:[],hour:[],month:[],day:[]};
	//check for every minute 
	if(undefined != v){
		if(typeof(v)=='number'){
			switch(v){
				case 1:v={minute:[-1],hour:[-1],month:[-1],day:[-1]};break;
				case 5:v={minute:[0,5,10,15,20,25,30,35,40,45,50,55],hour:[-1],month:[-1],day:[-1]};break;
				case 10:v={minute:[0,10,20,30,40,50],hour:[-1],month:[-1],day:[-1]};break;
				case 15:v={minute:[0,15,30,45],hour:[-1],month:[-1],day:[-1]};break;
				case 20:v={minute:[0,20,40],hour:[-1],month:[-1],day:[-1]};break;
				case 30:v={minute:[0,30],hour:[-1],month:[-1],day:[-1]};break;
				case 60:v={minute:[0],hour:[-1],month:[-1],day:[-1]};break;
				case 1440:v={minute:[0],hour:[0],month:[-1],day:[-1]};break;
				case 720:v={minute:[0],hour:[0,12],month:[-1],day:[-1]};break;
				case 10080:v={minute:[0],hour:[0],month:[-1],day:[1,8,15,22]};break;
				case 43829:v={minute:[0],hour:[0],month:[-1],day:[1]};break;
			}
			//console.log('number');
			//console.log(v);
		}
		if(typeof(v)=='string'){
			if(v.length > 0){v=JSON.parse(v);}
			else{v={minute:[],hour:[],month:[],day:[]};}
		}
		if(v.reset){
			for(let y=0;y<v.reset.length;y++){
				switch(v.reset[y]){
					case 'minute':
						for(let x=0;x<minutes.length;x++){minutes[x].checked=false;}
						let mclear=container.querySelector('span[title="clear minutes"]');
						if(undefined != mclear){mclear.style.color='#6c757d';}
					break;
					case 'hour':
						for(let x=0;x<hours.length;x++){hours[x].checked=false;}
						let hclear=container.querySelector('span[title="clear hours"]');
						if(undefined != hclear){hclear.style.color='#6c757d';}
					break;
					case 'day':
						for(let x=0;x<days.length;x++){days[x].checked=false;}
						let dclear=container.querySelector('span[title="clear days"]');
						if(undefined != dclear){dclear.style.color='#6c757d';}
					break;
					case 'month':
						for(let x=0;x<months.length;x++){months[x].checked=false;}
						let moclear=container.querySelector('span[title="clear months"]');
						if(undefined != moclear){moclear.style.color='#6c757d';}
					break;
				}
			}
			formSetFrequency(field.id);
			return false;
		}
		for(let x=0;x<minutes.length;x++){
			let hval=parseInt(minutes[x].value);
			if(undefined != v.minute && undefined != v.minute[0] && v.minute[0]==-1){
				minutes[x].checked=true;
				nv.minute=[-1];
			}
			else if(in_array(hval,v.minute)){
				minutes[x].checked=true;
				nv.minute.push(hval);
			}
			else{
				minutes[x].checked=false;
			}
		}
		for(let x=0;x<hours.length;x++){
			let hval=parseInt(hours[x].value);
			if(undefined != v.hour && undefined != v.hour[0] && v.hour[0]==-1){
				hours[x].checked=true;
				nv.hour=[-1];
			}
			else if(in_array(hval,v.hour)){
				hours[x].checked=true;
				nv.hour.push(hval);
			}
			else{
				hours[x].checked=false;
			}
		}
		for(let x=0;x<months.length;x++){
			let hval=parseInt(months[x].value);
			if(undefined != v.month && undefined != v.month[0] && v.month[0]==-1){
				months[x].checked=true;
				nv.month=[-1];
			}
			else if(in_array(hval,v.month)){
				months[x].checked=true;
				nv.month.push(hval)	
			}
			else{months[x].checked=false;}
		}
		for(let x=0;x<days.length;x++){
			let hval=parseInt(days[x].value);
			if(undefined != v.day && undefined != v.day[0] && v.day[0]==-1){
				days[x].checked=true;
				nv.day=[-1];
			}
			else if(in_array(hval,v.day)){
				days[x].checked=true;
				nv.day.push(hval);	
			}
			else{days[x].checked=false;}
		}
		setText(field,JSON.stringify(nv));
	}
	else{
		for(let x=0;x<minutes.length;x++){
			let hval=parseInt(minutes[x].value);
			if(minutes[x].checked){
				nv.minute.push(hval);
			}
		}
		if(minutes.length == nv.minute.length){
			nv.minute=[-1];
		}
		else if(nv.minute.length==0){
			nv.minute=[0];
		}
		for(let x=0;x<hours.length;x++){
			let hval=parseInt(hours[x].value);
			if(hours[x].checked){
				nv.hour.push(hval);
			}
		}
		if(hours.length == nv.hour.length){
			nv.hour=[-1];
		}
		else if(nv.hour.length==0){
			nv.hour=[0];
		}
		for(let x=0;x<months.length;x++){
			let hval=parseInt(months[x].value);
			if(months[x].checked){
				nv.month.push(hval);
			}
		}
		if(months.length == nv.month.length){
			nv.month=[-1];
		}
		else if(nv.month.length == 0){
			nv.month=[-1]
		}
		for(let x=0;x<days.length;x++){
			let hval=parseInt(days[x].value);
			if(days[x].checked){
				nv.day.push(hval);
			}
		}
		if(days.length == nv.day.length){
			nv.day=[-1];
		}
		else if(nv.day.length==0){
			nv.day=[1];
		}
		setText(field,JSON.stringify(nv));
		formSetFrequency(field.id,field.value);
	}
	//set clear icons
	if(nv.minute.length > 0){
		container.querySelector('span[title="clear minutes"]').style.color='#c51017';	
	}
	if(nv.hour.length > 0){
		container.querySelector('span[title="clear hours"]').style.color='#c51017';	
	}
	if(nv.day.length > 0){
		container.querySelector('span[title="clear days"]').style.color='#c51017';	
	}
	if(nv.month.length > 0){
		container.querySelector('span[title="clear months"]').style.color='#c51017';	
	}
	
}
function formSetFrequencyDisplay(fid,s){
	let ev=this.event || window.event;
	ev.stopPropagation();
	let fidstr='#'+fid+'_wizard';
	let wizard=document.querySelector(fidstr);
	if(undefined==wizard){
		//console.log(fidstr+' not found');
		return false;
	}
	if(s){
		wizard.style.display='block';
		commonOpenClose(wizard,'display','none');
		//console.log('formSetFrequencyDisplay:'+wizard);
	}
	else{
		wizard.style.display='none';
	}
	return false;
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
		var en=slist[i].getAttribute('name');
		if(en != 'null'){
			var val=getText(slist[i]);
			answers[en]=val;
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
function ajaxEditField(table,id,fld,params){
	if(undefined==params){params={};}
	params['_action']='EDITFIELD';
	params.table=table;
	params.id=id;
	params.field=fld;
	if(undefined == params.div){params.div='centerpop';}
	return ajaxGet('/php/index.php',params.div,params);
}
/**
* @describe enables speech recognition for an input field
* @param inp mixed - id or element for input field
* @param [ico] mixed - id or element for icon to blink while speech is on
* @params[frm] mixed - form element to submit or function to call
* @params[continuous] boolean - set to true for continuous listening
* @return false
* @usage onclick="return formDictate('inputid','iconid');"
*/
function formDictate(inp,ico,frm,continuous) {
  	inp=getObject(inp);
  	if(undefined == inp){
  		console.log('formDictate error: undefined input '+inp);
  		return false;
  	}
  	ico=getObject(ico);
    if (window.hasOwnProperty('webkitSpeechRecognition')) {
		var recognition = new webkitSpeechRecognition();
      	recognition.continuous = continuous||false;
      	recognition.interimResults = false;
      	recognition.lang = "en-US";
      	if(undefined != ico){
	      	ico.classList.add('w_blink');
	      	ico.classList.add('w_success');
	      	recognition.ico=ico;
	    }
	    if(undefined != frm){
	    	recognition.frm=frm;
	    }
      	recognition.inp=inp;
      	recognition.start();
      	recognition.onresult = function(e) {
      		if(undefined != this.frm && undefined != window[this.frm]){
        		try {
        			window[this.frm](e.results[0][0].transcript);
  					this.stop();
					this.start();
				}
				catch (e) {}
        	}
        	this.inp.value = e.results[0][0].transcript;
        	if(undefined != this.ico){
	        	this.ico.classList.remove('w_blink');
	        	this.ico.classList.remove('w_success');
	        }
        	try {
  					this.stop();
				}
			catch (e) {}
        	if(undefined != this.frm){
        		simulateEvent(this.frm,'submit');
        	}
		};
		recognition.onend = function(e){
			if(this.continuous){
				try {
					this.start();
				}
				catch (e) {}
			}
			else{
      			try {
  					this.stop();
				}
				catch (e) {}
      			if(undefined != this.ico){
		      		this.ico.classList.remove('w_blink');
		      		this.ico.classList.remove('w_success');
		      	}
      		}
		}
      	recognition.onerror = function(e) {
      		if(this.continuous){
      			if(undefined != e.error && e.error=='no-speech'){
      				try {
						this.restart();
					}
					catch (e) {}
      			}
      			else{
      				console.log(e);
      			}
			}
      		else{
      			try {
  					this.stop();
				}
				catch (e) {}
      			if(undefined != this.ico){
		      		this.ico.classList.remove('w_blink');
		      		this.ico.classList.remove('w_success');
		      	}
      		} 
      	};
    }
    return false;
}
//--------------------------
function autoGrow(box,maxheight) {
	//info: allows a textbox to grow as a person types
	//usage: <textarea onkeypress="autoGrow();"><textarea>
	if(undefined==maxheight){maxheight=400;}
	if (box.scrollHeight < maxheight && box.scrollHeight > box.clientHeight && !window.opera){box.style.height=box.scrollHeight+'px';}
}

//---------- begin function initPikadayCalendar--------------------
/**
* @describe initializes pikaday calendar module
* @param fieldname string - name of the input field to create
* @param params array - parameters
*	[-parent] string - parent object or id to append control to
* @return object
* @usage buildFormCalendar('fdate',{'-parent':'myform'});
*/
function initPikadayCalendar(field,params){
	field=getObject(field);
	let opts={
        field: field,
        position:'bottom right',
        format: 'YYYY-MM-DD',
	    toString(date, format) {
	        // you should do formatting based on the passed format,
	        // but we will just return 'D/M/YYYY' for simplicity
	        let day = date.getDate();
	        let month = date.getMonth() + 1;
	        let year = date.getFullYear();
	        if(day < 10){day='0'+day;}
	        if(month < 10){month='0'+month;}
	        return year+'-'+month+'-'+day;
	    },
	    parse(dateString, format) {
	        const parts = dateString.split('/');
	        const day = parseInt(parts[0], 10);
	        const month = parseInt(parts[1], 10) - 1;
	        const year = parseInt(parts[2], 10);
	        return new Date(year, month, day);
	    }
	};
	//check for custom attributes
	let attrs=getAllAttributes(field);
	//maxDate
	if(undefined != attrs['data-maxdate']){
		if(attrs['data-maxdate'].indexOf('-') != -1){
			let dateparts=attrs['data-maxdate'].split("-");
			if(dateparts.length==3){
				dateparts[1]=parseInt(dateparts[1])-1;
				opts.maxDate=new Date(dateparts[0],dateparts[1],dateparts[2])
			}
			else{
				let n=parseInt(attrs['data-maxdate'])*-1;
				let d=new Date();
				d.setDate(d.getDate() - n);
				opts.maxDate=d;
			}
		}
		else{
			switch(attrs['data-maxdate'].toLowerCase()){
				case 'now':
				case 'today':
					opts.maxDate=new Date();
				break;
				default:
					opts.maxDate=new Date(attrs['data-maxdate']);
				break;
			}
		}
	}
	//minDate  - YYYY-mm-dd
	if(undefined != attrs['data-mindate']){
		if(attrs['data-mindate'].indexOf('-') != -1){
			let dateparts=attrs['data-mindate'].split("-");
			if(dateparts.length==3){
				dateparts[1]=parseInt(dateparts[1])-1;
				opts.minDate=new Date(dateparts[0],dateparts[1],dateparts[2])
			}
			else{
				let n=parseInt(attrs['data-mindate'])*-1;
				let d=new Date();
				d.setDate(d.getDate() - n);
				opts.minDate=d;
			}
		}
		else{
			switch(attrs['data-mindate'].toLowerCase()){
				case 'now':
				case 'today':
					opts.minDate=new Date();
				break;
				default:
					opts.minDate=new Date(attrs['data-mindate']);
				break;
			}
		}
	}
	//trigger
	if(undefined != attrs['data-trigger']){
		let tobj=getObject(attrs['data-trigger']);
		if(undefined != tobj){
			opts.trigger=tobj;
		}
	}
	//firstDay
	if(undefined != attrs['data-firstday']){
		opts.firstDay=parseInt(attrs['data-firstday']);
	}
	//showWeekNumber
	if(undefined != attrs['data-showweeknumber']){
		opts.showWeekNumber=true;
	}
	//disableWeekends
	if(undefined != attrs['data-disableweekends']){
		opts.disableWeekends=true;
	}
	//showDaysInNextAndPreviousMonths
	if(undefined != attrs['data-showalldays']){
		opts.showDaysInNextAndPreviousMonths=true;
		opts.enableSelectionDaysInNextAndPreviousMonths=true;
	}
	//numberOfMonths
	if(undefined != attrs['data-numberofmonths']){
		opts.numberOfMonths=attrs['data-numberofmonths'];
		if(undefined != attrs['data-maincalendar']){
			opts.mainCalendar=attrs['data-maincalendar'];
		}
	}
	//pickWholeWeek
	if(undefined != attrs['data-pickwholeweek']){
		opts.pickWholeWeek=true;
	}
	//theme
	if(undefined != attrs['data-theme']){
		opts.theme=attrs['data-theme'];
	}
	//yearRange
	if(undefined != attrs['data-yearrange']){
		let r=attrs['data-yearrange'].split(/\,/);
		if(r.length==1){
			let n=parseInt(attrs['data-yearrange']);
			opts.yearRange=n;	
		}
		else{
			r[0]=parseInt(r[0]);
			r[1]=parseInt(r[1]);
			opts.yearRange=r;
		}
		
	}
	if(undefined != attrs['data-debug']){
		//log debug in console
		console.log(opts);
	}
	if(undefined != attrs['data-debug']){
		console.log('Pikaday calendar opts');
		console.log(opts);
	}
	let p=new Pikaday(opts);
	//show the calendar if they click on the field icon
	let icon=getObject(field.id+'_icon');
	if(undefined != icon){
		icon.p=p;
		icon.onclick=function(){
			this.p.show();
		};
	}
}
//---------- begin function buildFormCalendar--------------------
/**
* @describe creates an HTML calendar control
* @param fieldname string - name of the input field to create
* @param params array - parameters
*	[-parent] string - parent object or id to append control to
* @return object
* @usage buildFormCalendar('fdate',{'-parent':'myform'});
*/
function buildFormCalendar(fieldname,params){
	return buildFormDate(fieldname,params);
}
//---------- begin function buildFormCheckAll--------------------
/**
* @describe creates a checkbox element that checks other checkboxes
* @param att string
* @param attval string
* @param params array - parameters
*	[-parent] string - parent object or id to append control to
*	[onclick] string - additional function to run when control is clicked
*	[label] string - override label - defaults to Check All
*	[id] string - specify specific id attribute - generates a uniquie guid value by default
* @return element object
* @usage var cb=buildFormCheckAll('id','users');
*/
function buildFormCheckAll(att,attval,params){
	var tagdiv = document.createElement("div");
	if(undefined == params){params={};}
	if(undefined == params.id){params.id='c'+guid();}
	var tag = document.createElement("input");
	tag.type='checkbox';
	tag.id=params.id;
	var onclick='';
	if(undefined != params['onchange']){
		onclick=params['onchange'];
	}
	else if(undefined != params['onclick']){
		onclick=params['onclick'];
	}
	onclick="checkAllElements('"+att+"','"+attval+"',this.checked);"+onclick+";";
	tag.setAttribute('onclick',onclick);
	tagdiv.appendChild(tag);
	//now the label
	if(undefined == params.label){params.label='Check All';}
	var taglabel = document.createElement("label");
	taglabel.setAttribute('for',tag.id);
	taglabel.innerHTML='&nbsp;'+params.label;
	tagdiv.appendChild(taglabel);
	if(undefined != params['-parent']){
		var pobj=getObject(params['-parent']);
		if(undefined != pobj){
			pobj.appendChild(tagdiv);
		}
		else{console.log(params['-parent']+' does not exist');}
	}
	return tagdiv;
}
//---------- begin function buildFormCheckbox--------------------------------------
/**
* @describe creates an HTML Form checkbox
* @param name string
*	The name of the checkbox
* @param opts array tval/dval pairs to display
* @param params array - parameters
*	[-parent] string - parent object or id to append control to
*	[-formname] string - specify the form name - defaults to addedit
*	[id] string - specify the field id - defaults to formname_fieldname
*	[group] string - groupname. defaults to formname_fieldname_group
*	[-values] mixed - specify values that are checked. Can be an array or a colon separated string
*	[required] boolean - make it a required field - defaults to addedit false
*	[width] how many to show in a row - default to 6
*	[-checkall] boolean - show checkall control  - defaults to false
*	[-radio]	boolean - return radio button control instead - defaults to false
* @return element object
*	HTML Form checkbox for each pair passed in
*/
function buildFormCheckbox(fieldname, opts, params){
	if(undefined == fieldname || !fieldname.length){alert('buildFormCheckbox Error: no name');return undefined;}
	fieldname=fieldname.replace('/[\[\]]+$/','');
	if(undefined == params){params={};}
	if(undefined == opts){alert('buildFormCheckbox Error: no opts');return undefined;}
	if(undefined == params['-formname']){params['-formname']='addedit';}
	if(undefined == params['id']){params['id']=params['-formname']+'_'+fieldname;}
	if(undefined == params['group']){params['group']=params['-formname']+'_'+fieldname+'_group';}
	if(undefined == params['width']){params['width']=4;}
	if(undefined == params['-values']){params['-values']={};}
	if(undefined != params['value']){
		var vtype=typeof(params['value']);
		//parse the value
    	if(vtype.toLowerCase() == 'string'){
        	var vals=params['value'].split(':');
        	for(v=0;v<vals.length;v++){
            	params['-values'][vals[v]]=true;
			}
      	}
      	else{params['-values']=params['value'];}
      	params['value']='';
    }
    if(undefined != params['-values']){
		var pvtype=typeof(params['-values']);
		//parse the value
    	if(pvtype.toLowerCase() == 'string'){
    		var pvals=params['-values'].split(':');
    		params['-values']={};
    		for(v=0;v<pvals.length;v++){
            	params['-values'][pvals[v]]=true;
			}
    	}
	}
    var rowdiv = document.createElement("div");
	rowdiv.className='row';
	if(undefined != params['-checkall'] && undefined == params['-radio']){
    	var checkalldiv=buildFormCheckAll('data-group',params['group'],{'-parent':rowdiv});
	}
	//divide the opts into columns
	var col_opts={};
	var x=0;
	var tval;
	var dval;
	for(tval in opts){
		if(undefined == col_opts[x]){col_opts[x]={};}
		dval=opts[tval];
		col_opts[x][tval]=dval;
		x=x+1;
		if(x==params.width){x=0;}
	}
	var col_width=Math.floor(12/params.width);
	for(x=0;x<params.width;x++){
		var coldiv = document.createElement("div");
		coldiv.className='col-xs-'+col_width;
		for(tval in col_opts[x]){
			dval=col_opts[x][tval];
			var ctagdiv = document.createElement("div");
			var cid=params['-formname']+'_'+fieldname+'_'+tval;
			var ctag = document.createElement("input");
			if(undefined != params['-radio']){
				ctag.type='radio';
				ctag.name=fieldname;
			}
			else{
				ctag.type='checkbox';
				ctag.name=fieldname+'[]';
			}

			ctag.value=tval;
			if(undefined != params['-values'][tval]){ctag.checked=true;}
			if(params.required){ctag.setAttribute('_required',params.required);}
			ctag.id=cid;
			ctag.setAttribute('data-group',params.group);
			ctag.setAttribute('data-type','checkbox');
			ctagdiv.appendChild(ctag);
			var ctaglabel = document.createElement("label");
			ctaglabel.setAttribute('for',cid);
			ctaglabel.innerHTML='&nbsp;'+dval;
			ctagdiv.appendChild(ctaglabel);
			coldiv.appendChild(ctagdiv);
		}
		rowdiv.appendChild(coldiv);
	}
	if(undefined != params['-parent']){
		var pobj=getObject(params['-parent']);
		if(undefined != pobj){
			pobj.appendChild(rowdiv);
		}
		else{console.log(params['-parent']+' does not exist');}
	}
	return rowdiv;
}
//---------- begin function buildFormColor-------------------
/**
* @describe creates an HTML color control
* @param name string - field name
* @param params array - parameters
*	[-parent] string - parent object or id to append control to
*	[-formname] string - specify the form name - defaults to addedit
*	[value] string - specify the current value
*	[class] string - class attribute value - defaults to form-control
*	[required] boolean - make it a required field - defaults to addedit false
*	[id] string - specify the field id - defaults to formname_fieldname
*	[placeholder] string - placeholder attribute value - defaults to #HEXVAL
* @return element object
* @usage echo buildFormColor('color');
*/
function buildFormColor(fieldname,params){
	if(undefined == params){params={};}
	if(undefined == params['-formname']){params['-formname']='addedit';}
	if(undefined == params.id){params.id=params['-formname']+'_'+fieldname;}
	var iconid=params.id+'_icon';
	//force witdh
	params.width=115;
	var iconcolor='#c0c0c0';
	if(undefined != params.value){iconcolor=params.value;}
	if(undefined == params.placeholder){params.placeholder='#HEXVAL';}
	if(undefined == params.classname){params.classname='form-control input';}
	params['maxlength']=7;
	var tagdiv = document.createElement("div");
	tagdiv.className="input-group";
	tagdiv.style.width=params.width+'px';
	var tag = document.createElement("input");
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
	var tagspan = document.createElement("span");
	tagspan.id=iconid;
	tagspan.setAttribute('onclick',"return colorSelector('"+params.id+"');");
	tagspan.className="icon-color-adjust w_bigger w_pointer input-group-addon";
	tagspan.style.color=iconcolor+';padding-left:3px !important;padding-right:6px !important;';
	tagspan.title='Color Selector';
	tagdiv.appendChild(tagspan);
	if(undefined != params['-parent']){
		var pobj=getObject(params['-parent']);
		if(undefined != pobj){
			pobj.appendChild(tagdiv);
		}
		else{console.log(params['-parent']+' does not exist');}
	}
	return tagdiv;
}

//---------- begin function buildFormCombo--------------------
/**
* @describe creates an HTML combo field
* @param name string
* @param opts array
* @param params array - parameters
*	[-parent] string - parent object or id to append control to
*	[-formname] string - specify the form name - defaults to addedit
*	[class] string - class attribute value - defaults to form-control
*	[required] boolean - make it a required field - defaults to addedit false
*	[id] string - specify the field id - defaults to formname_fieldname
* @return element object
* @usage echo buildFormCombo('mydate',$opts,$params);
*/
function buildFormCombo(fieldname,opts,params){
	if(undefined == fieldname){alert('buildFormCombo requires fieldname');return undefined;}
	if(undefined == opts){alert('buildFormCombo requires opts');return undefined;}
	if(undefined == params){params={};}
	if(undefined == params['-formname']){params['-formname']='addedit';}
	if(undefined == params.id){params.id=params['-formname']+'_'+fieldname;}
	if(undefined == params.classname){params.classname='form-control input';}
	var datalist_id=params.id+'_datalist';
	var tagdiv = document.createElement("div");
	var tag = document.createElement("input");
	tag.className=params.classname;
	if(params.required){tag.setAttribute('required',params.required);}
	if(undefined != params.value){
    	tag.setAttribute('value',params.value);
	}
	else{tag.setAttribute('value','');}
	tag.name=fieldname;
	tag.id=params.id;
	tag.setAttribute('list',datalist_id);
	tagdiv.appendChild(tag);
	//datalist
	var taglist = document.createElement("datalist");
	taglist.id=datalist_id;
	for(var tval in opts){
		var coption = document.createElement("OPTION");
		coption.value=tval;
		coption.innerHTML=opts[tval];
		taglist.appendChild(coption);
	}
	tagdiv.appendChild(taglist);
	if(undefined != params['-parent']){
		var pobj=getObject(params['-parent']);
		if(undefined != pobj){
			pobj.appendChild(tagdiv);
		}
		else{console.log(params['-parent']+' does not exist');}
	}
	return tagdiv;
}
//---------- begin function buildFormDate-------------------
/**
* @describe creates an HTML date control
* @param name string - field name
* @param params array - parameters
*	[-parent] string - parent object or id to append control to
*	[-formname] string - specify the form name - defaults to addedit
*	[class] string - class attribute value - defaults to form-control
*	[required] boolean - make it a required field - defaults to addedit false
*	[id] string - specify the field id - defaults to formname_fieldname
*	[value] string - current value - no default
*	[title] string - title attribute - defaults to Date Control
*	[-control] string - control type: date,datetime,time - defaults to date
* @return element object
* @usage echo buildFormDate('fdate');
*/
function buildFormDate(fieldname,params){
	if(undefined == params){params={};}
	if(undefined == params['-formname']){params['-formname']='addedit';}
	if(undefined == params.id){params.id=params['-formname']+'_'+fieldname;}
	if(undefined == params.classname){params.classname='form-control input';}
	if(undefined == params['-control']){params['-control']='date';}
	var spanclass='';
	switch(params['-control']){
    	case 'date':
			if(undefined == params.title){params.title='Date Control';}
			spanclass="icon-calendar w_pointer input-group-addon";
			params.width=155;
			if(undefined == params.placeholder){params.placeholder='YYYY-MM-DD';}
		break;
    	case 'datetime':
			if(undefined == params.title){params.title='Date and Time Control';}
			spanclass="icon-calendar w_pointer input-group-addon";
			params.width=220;
			if(undefined == params.placeholder){params.placeholder='YYYY-MM-DD MH:MM:SS';}
		break;
    	case 'time':
			if(undefined == params.title){params.title='Time Control';}
			spanclass="icon-clock w_pointer input-group-addon";
			params.width=115;
			if(undefined == params.placeholder){params.placeholder='MH:MM:SS';}
		break;
		default:
		break;
	}
	var iconid=params.id+'_icon';
	var iconcolor='#c0c0c0';
	var tagdiv = document.createElement("div");
	tagdiv.className="input-group";
	tagdiv.style.width=params.width+'px';
	var tag = document.createElement("input");
	tag.type='text';
	tag.className=params.classname;
	tag.style.fontSize='12px';
	tag.style.fontFamily='arial';
	tag.name=fieldname;
	tag.id=params.id;
	if(params.required){tag.setAttribute('required',params.required);}
	if(undefined != params.value && params.value.length){
		tag.setAttribute('value',params.value);
	}
	else{tag.setAttribute('value','');}
	tag.classname=params.classname;
	tag.setAttribute('data-type',params['-control']);
	tag.placeholder=params.placeholder;
	tagdiv.appendChild(tag);
	var tagspan = document.createElement("span");
	tagspan.id=iconid;
	//tagspan.setAttribute('onclick',"return Calendar('"+params.id+"');");
	tagspan.className=spanclass;
	tagspan.style.color=iconcolor+';padding-left:3px !important;padding-right:6px !important;';
	tagspan.title=params.title;
	if(params['-control']=='datetime'){
    	tagspan.innerHTML='<span class="icon-clock"></span>';
	}
	tagdiv.appendChild(tagspan);
	
	if(undefined != params['-parent']){
		var pobj=getObject(params['-parent']);
		if(undefined != pobj){
			pobj.appendChild(tagdiv);
		}
		else{console.log(params['-parent']+' does not exist');}
	}
	if(params['-control']=='date'){
    	//initPikadayCalendar(tag.id,tagspan.id);
	}
	tagdiv.setAttribute('data-tagid',tag.id);
	tagdiv.setAttribute('data-spanid',tagspan.id);
	return tagdiv;
}
//---------- begin function buildFormDateTime-------------------
/**
* @describe creates an HTML color control
* @param name string - field name
* @param params array - parameters
*	[-parent] string - parent object or id to append control to
*	[-formname] string - specify the form name - defaults to addedit
*	[class] string - class attribute value - defaults to form-control
*	[required] boolean - make it a required field - defaults to addedit false
*	[id] string - specify the field id - defaults to formname_fieldname
*	[value] string - current value - no default
*	[title] string - title attribute - defaults to Date and Time Control
* @return element object
* @usage echo buildFormDateTime('color');
*/
function buildFormDateTime(fieldname,params){
	if(undefined == params){params={};}
	params['-control']='datetime';
	return buildFormDate(fieldname,params);
}
//---------- begin function buildFormHidden-------------------
/**
* @describe creates hidden form field
* @param name string - field name
* @param params array - parameters
*	[-parent] string - parent object or id to append control to
*	[-formname] string - specify the form name - defaults to addedit
*	[value] string - specify the current value
* @return element object
* @usage echo buildFormDateTime('color');
*/
function buildFormHidden(fieldname,params){
	if(undefined == params){params={};}
	if(undefined == params['-formname']){params['-formname']='addedit';}
	if(undefined == params.id){params.id=params['-formname']+'_'+fieldname;}
	var tag = document.createElement("input");
	tag.type='hidden';
	tag.name=fieldname;
	if(undefined != params.value){
		tag.setAttribute('value',params.value);
	}
	else{tag.setAttribute('value','');}
	tag.id=params.id;
	if(undefined != params['-parent']){
		var pobj=getObject(params['-parent']);
		if(undefined != pobj){
			pobj.appendChild(tag);
		}
		else{console.log(params['-parent']+' does not exist');}
	}
	return tag;
}
//---------- begin function buildFormPassword-------------------
/**
* @describe creates hidden form field
* @param name string - field name
* @param params array - parameters
*	[-parent] string - parent object or id to append control to
*	[-formname] string - specify the form name - defaults to addedit
*	[value] string - specify the current value
* @return element object
* @usage echo buildFormPassword('color');
*/
function buildFormPassword(fieldname,params){
	if(undefined == params){params={};}
	if(undefined == params['-formname']){params['-formname']='addedit';}
	if(undefined == params.id){params.id=params['-formname']+'_'+fieldname;}
	if(undefined == params.classname){params.classname='form-control input';}
	var tag = document.createElement("input");
	tag.type='password';
	tag.name=fieldname;
	tag.value=params.value;
	if(params.required){tag.setAttribute('required',params.required);}
	tag.id=params.id;
	tag.className=params.classname;
	if(undefined != params.value){
		tag.setAttribute('value',params.value);
	}
	else{tag.setAttribute('value','');}
	if(undefined != params['-parent']){
		var pobj=getObject(params['-parent']);
		if(undefined != pobj){
			pobj.appendChild(tag);
		}
		else{console.log(params['-parent']+' does not exist');}
	}
	return tag;
}
//---------- begin function buildFormRadio--------------------------------------
/**
* @describe creates an HTML Form checkbox
* @param name string
*	The name of the checkbox
* @param opts array tval/dval pairs to display
* @param params array - parameters
*	[-parent] string - parent object or id to append control to
*	[-formname] string - specify the form name - defaults to addedit
*	[id] string - specify the field id - defaults to formname_fieldname
*	[group] string - groupname. defaults to formname_fieldname_group
*	[-values] mixed - specify values that are checked. Can be an array or a colon separated string
*	[required] boolean - make it a required field - defaults to addedit false
*	[width] how many to show in a row - default to 6
* @return element object
*	HTML Form checkbox for each pair passed in
*/
function buildFormRadio(fieldname, opts, params){
	if(undefined == params){params={};}
	params['-radio']=true;
	return buildFormCheckbox(fieldname, opts, params);
}
//---------- begin function buildFormText--------------------
/**
* @describe creates an HTML combo field
* @param name string
* @param opts array
* @param params array - parameters
*	[-parent] string - parent object or id to append control to
* @return element object
* @usage echo buildFormText('mydate',$opts,$params);
*/
function buildFormText(fieldname,params){
	if(undefined == fieldname){alert('buildFormText requires fieldname');return undefined;}
	if(undefined == params){params={};}
	if(undefined == params['-formname']){params['-formname']='addedit';}
	if(undefined == params.id){params.id=params['-formname']+'_'+fieldname;}
	if(undefined == params.classname){params.classname='form-control input';}
	var tag = document.createElement("input");
	tag.className=params.classname;
	if(params.required){tag.setAttribute('required',params.required);}
	if(undefined != params.value){
    	tag.setAttribute('value',params.value);
	}
	else{tag.setAttribute('value','');}
	tag.name=fieldname;
	tag.id=params.id;
	if(undefined != params['-parent']){
		var pobj=getObject(params['-parent']);
		if(undefined != pobj){
			pobj.appendChild(tag);
		}
		else{console.log(params['-parent']+' does not exist');}
	}
	return tag;
}
//---------- begin function buildFormTextarea--------------------
/**
* @describe creates an HTML textarea field
* @param name string
* @param opts array
* @param params array - parameters
*	[-parent] string - parent object or id to append control to
* @return element object
* @usage echo buildFormTextarea('mydate',$opts,$params);
*/
function buildFormTextarea(fieldname,params){
	if(undefined == fieldname){alert('buildFormTextarea requires fieldname');return undefined;}
	if(undefined == params){params={};}
	if(undefined == params['-formname']){params['-formname']='addedit';}
	if(undefined == params.id){params.id=params['-formname']+'_'+fieldname;}
	if(undefined == params.classname){params.classname='form-control input';}
	if(undefined == params.height){params.height=50;}
	if(undefined != params.behavior){params['data-behavior']=params.behavior;}
	var tag = document.createElement("textarea");
	tag.className=params.classname;
	if(params.required){tag.setAttribute('required',params.required);}
	if(undefined != params.value){
    	tag.innerHTML=params.value;
	}
	else{tag.innerHTML='';}
	tag.name=fieldname;
	tag.id=params.id;
	tag.style.height=params.height+'px';
	//look for behaviors
	if(undefined != params['data-behavior']){
    	switch(params['data-behavior'].toLowerCase()){
        	case 'editor':
			case 'tinymce':
			case 'wysiwyg':
			case 'nicedit':
				tag.setAttribute('data-behavior','nicedit');
			break;
			default:
				tag.setAttribute('data-behavior',params['data-behavior']);
			break;
		}
	}
	if(undefined != params['-parent']){
		var pobj=getObject(params['-parent']);
		if(undefined != pobj){
			pobj.appendChild(tag);
		}
		else{console.log(params['-parent']+' does not exist');}
	}
	return tag;
}
//---------- begin function buildFormTime-------------------
/**
* @describe creates an HTML time control
* @param name string - field name
* @param params array - parameters
*	[-parent] string - parent object or id to append control to
*	[-formname] string - specify the form name - defaults to addedit
*	[class] string - class attribute value - defaults to form-control
*	[required] boolean - make it a required field - defaults to addedit false
*	[id] string - specify the field id - defaults to formname_fieldname
*	[value] string - current value - no default
*	[title] string - title attribute - defaults to Time Control
* @return element object
* @usage  buildFormDateTime('ftime');
*/
function buildFormTime(fieldname,params){
	if(undefined == params){params={};}
	params['-control']='time';
	return buildFormDate(fieldname,params);
}
//---------- begin function buildFormSelect--------------------------------------
/**
* @describe creates an HTML Form checkbox
* @param name string
*	The name of the checkbox
* @param opts array tval/dval pairs to display
* @param params array - parameters
*	[-parent] string - parent object or id to append control to
*	[-formname] string - specify the form name - defaults to addedit
*	[id] string - specify the field id - defaults to formname_fieldname
*	[-values] mixed - specify values that are checked. Can be an array or a colon separated string
*	[required] boolean - make it a required field - defaults to addedit false
* @return element object
*	HTML Form checkbox for each pair passed in
*/
function buildFormSelect(fieldname, opts, params){
	if(undefined == fieldname || !fieldname.length){alert('buildFormSelect Error: no name');return undefined;}
	fieldname=fieldname.replace('/[\[\]]+$/','');
	if(undefined == params){params={};}
	if(undefined == opts){alert('buildFormSelect Error: no opts');return undefined;}
	if(undefined == params['-formname']){params['-formname']='addedit';}
	if(undefined == params['id']){params['id']=params['-formname']+'_'+fieldname;}
	if(undefined == params.classname){params.classname='form-control input';}
    var tag = document.createElement("select");
	tag.className=params.classname;
	if(params.required){tag.setAttribute('required',params.required);}
	tag.name=fieldname;
	tag.id=params.id;
	for(var tval in opts){
		var coption = document.createElement("OPTION");
		coption.value=tval;
		coption.innerHTML=opts[tval];
		if(undefined != params.value && tval==params.value){coption.setAttribute('selected',true);}
		tag.appendChild(coption);
	}
	if(undefined != params['-parent']){
		var pobj=getObject(params['-parent']);
		if(undefined != pobj){
			pobj.appendChild(tag);
		}
		else{console.log(params['-parent']+' does not exist');}
	}
	return tag;
}



//--------------------------
function comboCompleteMatch (sText, arrValues) {
	sText=sText.toLowerCase();
	for (var i=0; i < arrValues.length; i++) {
		aval=arrValues[i].toLowerCase();
		if (aval.indexOf(sText) === 0) {
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
		if(cline.length ===0){continue;}
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
	var code=parseInt(oEvent.keyCode);
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
	if(undefined == fld.getAttribute('data-id')){return false;}
	let id=fld.getAttribute('data-id');
	let oid=id+'_options';
	let options=getObject(oid);
	if(undefined == options){return false;}
	let list=options.querySelectorAll('input');
	let vals=new Array();
	for(let i=0;i<list.length;i++){
		//skip if no value
		if(undefined == list[i].value){continue;}
		if(list[i].value==''){continue;}
		if(undefined == list[i].id){continue;}
		if(list[i].id==''){continue;}
    	if(list[i].checked){
    		let label=document.querySelector('label[for="'+list[i].id+'"]');
    		vals.push(label.innerText);
    	}
	}
	
	let btn_id=id+'_button';
	let bobj=getObject(btn_id);
	if(vals.length==0){
		bobj.innerText=bobj.getAttribute('data-dname');
	}
	else{
		let str='('+vals.length+') '+implode(', ',vals);
		bobj.innerText=str;
	}
	return false;
}
//--------------------------
//fielddataChange(this);
function fielddataChange(fld){
	var parentObj=getParentForm(fld);
	return false;
}
//--------------------------
function filemanagerEdit(id,formaction,param){
	//build an html for for changing the name and description of file
	var obj=getObject(id);
	if(undefined==obj){return;}
	var fname=obj.getAttribute('filename');
	var desc=getText(obj);
	var htm='';
	htm += '<div class="w_centerpop_title">File Manager File Edit</div>'+"\n";
	htm += '<div  style="padding:0 25px 0 25px;">'+"\n";
	htm += '	<form method="post" action="'+formaction+'" onSubmit="return submitForm(this);">'+"\n";
	htm += '		<div class="row">'+"\n";
	htm += '			<label for="file_name">Name</label><input type="text" id="file_name" name="file_name" value="'+fname+'" class="form-control">'+"\n";
	htm += '		</div>'+"\n";
	htm += '		<div class="row">'+"\n";
	htm += '			<label for="file_desc">Desc</label><textarea name="file_desc" class="form-control" onkeypress="autoGrow(this,200);">'+desc+'</textarea>'+"\n";
	htm += '		</div>'+"\n";
	htm += '		<div class="row text-right" style="margin-top:20px;"><button type="submit" class="btn btn-primary">Save Changes</button></div>'+"\n";
	if(param){
		htm += '<div style="display:none" id="params">'+"\n";
		for (var key in param){
			htm += '<textarea name="'+key+'">'+param[key]+'</textarea>'+"\n";
			}
		htm += '</div>'+"\n";
		}
	htm += '</form>'+"\n";
	htm += '</div>'+"\n";
	//alert(htm);
	centerpopDiv(htm);
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
	txt +=  '	<div class="w_centerpop_content" id="remindmediv" style="width:350px;padding:0 30px 0 30px;">'+"\n";
	txt +=	'		<form method="POST" name="remindMe" class="w_form" action="/php/index.php" onSubmit="ajaxSubmitForm(this,\'remindmediv\');return false;">'+"\n";
	txt +=  '			<input type="hidden" name="_remind" value="1">'+"\n";
	txt +=  '			<input type="hidden" name="tname" value="remind me">'+"\n";
	txt +=	'			<label for="remind_me_email" class="w_bigger w_grey" style="font-weight:300;"><span class="icon-mail"></span> Enter your email address.</label>'+"\n";
	txt +=	' 			<div><input type="email" maxlength="255" id="remind_me_email" name="email" placeholder="your email address" pattern=".+@.+..{2,6}" data-pattern-msg="Invalid Email Address" required="1" data-requiredmsg="Enter the email address you registered with." value="" onFocus="this.select();" class="form-control input-lg"></div>'+"\n";
	txt +=	'			<div class="text-right w_padtop"><button type="submit" class="btn btn-default w_formsubmit btn-lg">Remind Me</button></div>'+"\n";
	txt +=  '		</form>';
	txt +=	'	</div>'+"\n";
	var rtitle='Remind Me';
	popUpDiv('',{id:dname,width:300,height:50,drag:1,notop:1,nobot:1,noborder:1,nobackground:1,bodystyle:"padding:0px;border:0px;background:none;"});
	setCenterPopText(dname,txt,{title:rtitle});
	document.remindMe.email.focus();
	return false;
	}

//--------------------------
function setProcessing(id,msg,cancel){
	if(undefined == cancel){cancel=1;}
	if(undefined == msg){msg='';}
	var str=getProcessingDiv(id,msg,cancel);
	setText(id,str);
	return;
	}
//--------------------------
function getProcessingDiv(id,msg,cancel){
	if(undefined == cancel){cancel=1;}
	if(undefined == msg){msg='';}
	//check for setprocessing_custom
	let pdiv=document.querySelector('#setprocessing_custom');
	if(undefined != pdiv){
		return pdiv.innerHTML;
	}
	var str='';
	str += '<span id="processing_div">';
	str += '<span class="icon-spin7 w_spin"></span>';
	str += ' <span class="w_grey processing_message"> '+msg+'</span>';
	if(cancel==1){
		str += ' <span class="icon-cancel-circled w_danger w_pointer" onclick="return ajaxAbort(\''+id+'\');"></span>';
	}
	str += '</span>';
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
    		//list=GetElementsByAttribute('input',att,'^'+val+'$');
    	break;
    }
    for(var i=0;i<list.length;i++){
		//process any onclick attribute
		if(undefined != list[i].dataset.onclick){
			simulateEvent(list[i], 'click');
		}
		list[i].checked=ck;
    }
	return false;
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
	quickmask['number'] = '(^\-{0,1}[0-9]+$)|(^\-{0,1}\\.[0-9]+$)|(^\-{0,1}[0-9]+\\.[0-9]+$)';
	quickmask['phone'] = '^([0-9]{3,3}[\\-\\.][0-9]{3,3}[\\-\\.][0-9]{4,4}|\\([0-9]{3,3}\\)\\ [0-9]{3,3}[\\-][0-9]{4,4})$';
	quickmask['time'] = '^[0-9]{1,2}\\:[0-9]{2}$';
	quickmask['ssn'] = '^[0-9]{3,3}\\-[0-9]{2,2}\\-[0-9]{4,4}$';
	quickmask['zipcode'] = '^[0-9]{5,5}(\\\-[0-9]{4,4})*$';
	//alert("theForm type="+typeof(theForm));
	if(theForm.length ==0){return false;}
	if(debug==1){console.log("submitForm Debug Begin. Form length: "+theForm.length);}
	var formfields=new Array();
	for(var i=0;i<theForm.length;i++){
		if(debug==1){console.log(" - Checking "+theForm[i].name+" of type "+theForm[i].type);}
		let atts=getAllAttributes(theForm[i]);
		if(undefined != atts.disabled){continue;}
		if(undefined != atts.readonly){continue;}
		if(theForm[i].type == 'hidden'){continue;}
		/* add this form name to the list of formfields */
		if(!in_array(theForm[i].name,formfields) && theForm[i].name != '_formfields'){
			formfields[formfields.length]=theForm[i].name;
		}
	  	/* Password confirm */
	  	if(theForm[i].name == 'password'  && undefined != theForm.password_confirm){
	  		if(theForm[i].value.length == 0 || theForm.password_confirm.value.length == 0){
	  			if(debug==1){console.log(theForm[i].name+' is required');}
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
	  			if(debug==1){console.log(theForm[i].name+' is required');}
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
		//check for requiredif attribute
        var requiredif='';
		if(undefined != theForm[i].getAttribute('_requiredif')){requiredif=theForm[i].getAttribute('_requiredif');}
		else if(undefined != theForm[i].getAttribute('data-requiredif')){requiredif=theForm[i].getAttribute('data-requiredif');}
		else if(undefined != theForm[i].getAttribute('requiredif')){requiredif=theForm[i].getAttribute('requiredif');}
        if(requiredif.length > 0){
			if(formFieldHasValue(requiredif)){
				//console.log(requiredif+' is checked');
				required=1;
			}
		}
        if(required == 1 || required == 'required'){
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
		            if(typeof wacss.blink === "function" && undefined != theForm[i].getAttribute('data-blink')){
		            	if(typeof wacss.toast === "function"){
				 			wacss.toast(msg);
				 		}
		            	wacss.blink(theForm[i].getAttribute('data-blink'));
		            }
		            else{
		            	submitFormAlert(msg,popup,5);	
		            }
				 	if(debug==1){console.log(msg);}
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
				 	if(typeof wacss.blink === "function" && undefined != theForm[i].getAttribute('data-blink')){
				 		if(typeof wacss.toast === "function"){
				 			wacss.toast(msg);
				 		}
		            	wacss.blink(theForm[i].getAttribute('data-blink'));
		            }
		            else{
		            	submitFormAlert(msg,popup,5);	
		            }
		            if(debug==1){console.log(msg);}
		            theForm[i].focus();
		            return false;
                }
            }
            else if(theForm[i].type=='textarea'){
            	var cval=trim(getText(theForm[i]));
            	if(cval.length==0){
                	var msg=dname+" is required";
		            if(undefined != requiredmsg){msg=requiredmsg;}
				 	if(typeof wacss.blink === "function" && undefined != theForm[i].getAttribute('data-blink')){
				 		if(typeof wacss.toast === "function"){
				 			wacss.toast(msg);
				 		}
		            	wacss.blink(theForm[i].getAttribute('data-blink'));
		            }
		            else{
		            	submitFormAlert(msg,popup,5);	
		            }
		            if(debug==1){console.log(msg);}
		            theForm[i].focus();
		            return false;
				}
			}
			else if(theForm[i].type=='select-one'){
            	var cval=theForm[i].options[theForm[i].selectedIndex].value;
            	if(cval.length==0){
                	var msg=dname+" is required";
		            if(undefined != requiredmsg){msg=requiredmsg;}
				 	if(typeof wacss.blink === "function" && undefined != theForm[i].getAttribute('data-blink')){
				 		if(typeof wacss.toast === "function"){
				 			wacss.toast(msg);
				 		}
		            	wacss.blink(theForm[i].getAttribute('data-blink'));
		            }
		            else{
		            	submitFormAlert(msg,popup,5);	
		            }
		            if(debug==1){console.log(msg);}
		            theForm[i].focus();
		            return false;
				}
			}
			else if(theForm[i].value == ''){
	            var msg=dname+" is required";
	            if(undefined != requiredmsg){msg=requiredmsg;}
			 	if(typeof wacss.blink === "function" && undefined != theForm[i].getAttribute('data-blink')){
			 			if(typeof wacss.toast === "function"){
				 			wacss.toast(msg);
				 		}
		            	wacss.blink(theForm[i].getAttribute('data-blink'));
		            }
		            else{
		            	submitFormAlert(msg,popup,5);	
		            }
		        if(debug==1){console.log(msg);}
	            theForm[i].focus();
	            return false;
			}
        }
        //check for mask attribute - a filter to test input against
        var mask=theForm[i].getAttribute('pattern');
        if(undefined == mask){mask=theForm[i].getAttribute('mask');}
        if(undefined == mask){mask=theForm[i].getAttribute('data-mask');}
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
                    if(debug==1){console.log(msg);}
                    theForm[i].focus();
                    return false;
                }
            }
            else if(mask == 'date'){
				//date
				if(isDate(theForm[i].value) == false){
					//invalid date
                    var msg = dname+" must be a valid date ";
                    if(undefined != fldmsg){msg=fldmsg;}
                    submitFormAlert(msg,popup,5);
                    if(debug==1){console.log(msg);}
                    theForm[i].focus();
                    return false;
                }
            }
            else if(mask == 'futuredate'){
				//future date
				if(isFutureDate(theForm[i].value) == false){
					//invalid date
                    var msg = dname+" must be of valid date in the future ";
                    if(undefined != fldmsg){msg=fldmsg;}
                    submitFormAlert(msg,popup,5);
                    if(debug==1){console.log(msg);}
                    theForm[i].focus();
                    return false;
                }
            }
            else if(mask == 'pastdate'){
				//past date
				if(isPastDate(theForm[i].value) == false){
					//invalid date
                    var msg = dname+" must be of valid date in the past ";
                    if(undefined != fldmsg){msg=fldmsg;}
                    submitFormAlert(msg,popup,5);
                    if(debug==1){console.log(msg);}
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
                    if(debug==1){console.log(msg);}
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
                    if(debug==1){console.log(msg);}
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
                if(debug==1){console.log(msg);}
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
                if(debug==1){console.log(msg);}
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
                if(debug==1){console.log(msg);}
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
                if(debug==1){console.log(msg);}
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
                if(debug==1){console.log(msg);}
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
		formAddField(theForm,'_formfields',formfieldstr);
	}
	//check for show_upload_progress
	if(undefined != theForm.show_upload_progress){
		formShowUploadProgress();
		//setTimeout('formShowUploadProgress(\''+theForm.show_upload_progress.value+'\')',2000);
	}
	else{
		//disable buttons with class w_disable_on_submit
		let dlist=document.querySelectorAll('.w_disable_on_submit');
		//console.log('w_disable_on_submit');
		//console.log(dlist);
		for(let d=0;d<dlist.length;d++){
			dlist[d].setAttribute('disabled','disabled');
		}
		//hide buttons with class w_hide_on_submit
		let hlist=document.querySelectorAll('.w_hide_on_submit');
		//console.log('w_hide_on_submit');
		//console.log(hlist);
		for(let h=0;h<hlist.length;h++){
			hlist[h].style.display='none';
		}
	}
    return true;
}
function formShowUploadProgress(){
	//disable buttons with class w_disable_on_submit
	let dlist=document.querySelectorAll('.w_disable_on_submit');
	//console.log('w_disable_on_submit');
	//console.log(dlist);
	for(let d=0;d<dlist.length;d++){
		dlist[d].setAttribute('disabled','disabled');
	}
	//hide buttons with class w_hide_on_submit
	let hlist=document.querySelectorAll('.w_hide_on_submit');
	//console.log('w_hide_on_submit');
	//console.log(hlist);
	for(let h=0;h<hlist.length;h++){
		hlist[h].style.display='none';
	}
	//uploading
	html='';
	html+='<div class="w_centerpop_title">Uploading...</div>'+"\n";
	html+= '<div class="w_centerpop_content">'+"\n";
	html+= '	<div class="w_padtop"><span class="icon-spin4 w_rotate w_spin"></span> Uploading ...</div>'+"\n";
	html+= '</div>'+"\n";
	centerpopDiv(html,0);

	//ajaxGet('/php/index.php','centerpop99',{get_upload_progress_json:1,name:name})
}
function formAddField(frm,fld,val){
	var input = document.createElement("input");
    input.type = "hidden";
    input.name = fld;
	input.value=val;
    frm.appendChild(input);
}
function formFieldHasValue(fld){
	fld=getObject(fld);
	if(undefined == fld){return false;}
	if(undefined == fld.type){
		return false;
	}
	if(fld.type=='checkbox' || fld.type=='radio'){
		if(fld.checked){return true;}
	}
    else if(fld.type=='textarea'){
        var cval=trim(getText(fld));
        if(cval.length){return true;}
	}
	else if(fld.value != ''){
		return true;
	}
	return false;
}
//--------------------------
function imposeMaxlength(obj, max){
	return (obj.value.length <= max);
	}
//--------------------------
function pagingSetProcessing(obj){ 
	let s=obj.querySelector('span');
	if(undefined != s){
		s.className='icon-spin7 w_spin';
	}
	else{
		setText(obj,'<span class="icon-spin7 w_spin"></span>');
	}
}
function pagingSubmit(frm,div){
	//console.log('pagingSubmit',div)
	pagingAddFilter(frm);
	pagingSetFilters(frm);
	if(undefined != div){
		//console.log('pagingSubmit - ajax');
		frm.setAttribute('pagingSetFilters',0);
		return ajaxSubmitForm(frm,div);
	}
	//console.log('pagingSubmit - NOT ajax');
	//console.log(frm.filter_export.value);
	frm.submit();
	return false;
}
function pagingSetOffset(frm,v){
	frm.filter_offset.value=v;
	return frm.onsubmit();
}
function pagingSetOrder(frm,v){
	if(frm.filter_order.value==v && frm.filter_order.value.indexOf('desc')==-1){
		v=v+' desc';
	}
	frm.filter_order.value=v;
	return frm.onsubmit();
}
function pagingBulkEdit(frm){
	if(frm.filter_field.value.length==0 || frm.filter_field.value=='*'){alert('select a field to edit');return false;}
	var editval='';
	if(frm.filter_value.value.length==0){editval='NULL';}
	else{editval="'"+frm.filter_value.value+"'";}
	if(!confirm('Are you sure you want to update the current dataset?'+"\r\n\r\n"+'Mass Update \''+frm.filter_field.value+'\' field to '+editval+'?'+"\r\n\r\n"+'Click OK to confirm.  THIS IS NOT REVERSABLE.')){return false;}
	frm.bulkedit=1;
	frm.filter_bulkedit.value='1';
	//return false;
	return frm.onsubmit();
}
function pagingExport(frm){
	let div=frm._export_formname.value+'_exportbutton';
	let obj=getObject(div);
	if(undefined==div){return false;}
	obj.innerHTML='<span class="icon-spin7 w_spin" style="margin-top:5px;"></span>';
	obj.style.display='inline-block';
	let exportForm=document.createElement('FORM');
	exportForm.method='POST';
	exportForm.action='/php/index.php';
	let inp=document.createElement('INPUT');
	inp.type='hidden';
	inp.name='setprocessing';
	inp.value='0';
	exportForm.appendChild(inp);
	let inp1=document.createElement('INPUT');
	inp1.type='hidden';
	inp1.name='_pushexport';
	inp1.value='1';
	exportForm.appendChild(inp1);
	let inp2=document.createElement('TEXTAREA');
	inp2.name='_pushparams';
	inp2.innerText=frm._export_params_.innerText;
	exportForm.appendChild(inp2);
	document.body.appendChild(exportForm);
	ajaxSubmitForm(exportForm,div);
	exportForm.parentNode.removeChild(exportForm);
	return false;
	//pagingAddFilter(frm);
	//pagingSetFilters(frm);
	//frm.filter_export.value='1';
	//return frm.onsubmit();
}
function pagingAddFilter(frm){
	if(undefined != frm.bulkedit){return false;}
	if(frm.filter_field.value.length==0){alert('select a filter field');return false;}
	if(frm.filter_operator.value.length==0){alert('select a filter operator');return false;}

	if(frm.filter_field.value == '*' && (frm.filter_operator.value == 'ib' || frm.filter_operator.value == 'nb')){
		alert('select a field to check for null values on');
		frm.filter_field.focus();
		return false;
	}
	else if(frm.filter_field.value != '*' && (frm.filter_operator.value == 'ib' || frm.filter_operator.value == 'nb')){
	}
	else if(frm.filter_value.value.length==0 && frm.filter_operator.value != 'null'){
		//alert('select a filter value');
		frm.filter_value.focus();
		return false;
	}
	let str=frm.filter_field.value+' '+frm.filter_operator.value+' '+frm.filter_value.value;
	pagingAddFilters(frm,str);
	frm.filter_value.value='';
	frm.filter_value.focus();
}
function pagingAddFilters(frm,filters,clear){
	//console.log(frm);
	//console.log(filters);
	if(undefined != clear){
		pagingClearFilters(frm);
	}
	let sets=filters.split(";");
	//console.log(sets);
	for(let s=0;s<sets.length;s++){
		let fltrs=sets[s].split(" ");
		let id=fltrs[0]+fltrs[1];
		if(undefined != fltrs[2]){
			id=id+fltrs[2].replace(/\,/g,"");
		}
		let obj=frm.querySelector('#'+id);
		let filters=new Array();
		if(undefined != obj){
			obj.style.display='inline-block';
		}
		else{
			let d=document.createElement('div');
			d.className='w_pagingfilter';
			d.setAttribute('data-field',fltrs[0]);
			d.setAttribute('data-operator',fltrs[1]);
			d.setAttribute('data-value',fltrs[2]);
			d.id=id;
			let dfield=fltrs[0];
			if(dfield=='*'){dfield='Any Field';}
			let doper=fltrs[1];
			let dval='\''+fltrs[2]+'\'';
			switch(doper){
	        	case 'ct': 	doper='Contains';break;
	        	case 'nct': doper='Not Contains';break;
	        	case 'ca': 	doper='Contains Any of These';break;
	        	case 'nca': doper='Not Contain Any of These';break;
				case 'eq': 	doper='Equals';break;
				case 'neq': doper='Not Equals';break;
				case 'ea': 	doper='Equals Any of These';break;
				case 'nea': doper='Not Equals Any of These';break;
				case 'gt': doper='Greater Than';break;
				case 'lt': doper='Less Than';break;
				case 'egt': doper='Equals or Greater than';break;
				case 'elt': doper='Less than or Equals';break;
				case 'ib': doper='Is Blank';dval='';break;
				case 'nb': doper='Is Not Blank';dval='';break;
			}
			d.innerHTML='<span class="icon-filter w_grey"></span> '+dfield+' '+doper+' '+dval+' <span class="icon-cancel w_danger w_pointer" onclick="removeId(\''+id+'\');"></span>';
			let p=frm.querySelector('#send_to_filters');
			p.appendChild(d);
		}
		let f=frm.querySelectorAll('.w_pagingfilter');
		let filter_count=0;
		for(let i=0;i<f.length;i++){
	    	if(f[i].style.display=='none'){continue;}
	    	if(undefined == f[i].getAttribute('data-field') || f[i].getAttribute('data-field')=='null'){continue;}
	  		filter_count=filter_count+1;
		}
		if(filter_count > 0){
			//Clear Filters button
			let obj=frm.querySelector('#paging_clear_filters');
			if(undefined != obj){removeId(obj);}
			let d=document.createElement('div');
			d.className='w_pagingfilter icon-erase w_big w_danger';
			d.id='paging_clear_filters';
			d.setAttribute('title','Clear All Filters');
			d.onclick=function(){
				pagingClearFilters(getParent(this,'form'));
			};
			let p=frm.querySelector('#send_to_filters');
			p.appendChild(d);
		}
		frm.filter_value.value='';
		frm.filter_value.focus();
	}
	simulateEvent(frm,'submit');
}
function pagingSetFilters(frm){
	var f=frm.querySelectorAll('.w_pagingfilter');
	var filters=new Array();
	for(var i=0;i<f.length;i++){
    	if(f[i].style.display=='none'){continue;}
    	if(undefined == f[i].getAttribute('data-field') || f[i].getAttribute('data-field')=='null'){continue;}
    	var fval=f[i].getAttribute('data-field')+'-'+f[i].getAttribute('data-operator')+'-'+f[i].getAttribute('data-value');
    	filters.push(fval);
	}
	//update filters field
	if(undefined != frm.bulkedit){return;}
	frm._filters.value=implode("\r\n",filters);
	if(undefined != frm.getAttribute('pagingSetFilters')){
		let check=frm.getAttribute('pagingSetFilters');
		if(check==1 || check=='1'){return false;}
	}
	frm.setAttribute('pagingSetFilters',1);

	//clear bulk edit if it exists
	if(undefined != frm.filter_bulkedit){frm.filter_bulkedit.value='';}
	//clear export if it exists
	if(undefined != frm.filter_export){frm.filter_export.value='';}
}
function pagingClearFilters(frm){
	var f=frm.querySelectorAll('.w_pagingfilter');
	if(undefined == f){return false;}
	for(var i=0;i<f.length;i++){
		removeId(f[i]);
	}
	//clear filters field
	var f=GetElementsByAttribute('textarea','name','_filters');
	for(var i=0;i<f.length;i++){
    	setText(f[i],'');
	}
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
		html+='<div class="w_centerpop_title"><span class="icon-warning w_danger"></span> Error Processing Request</div>'+"\n";
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
function ajaxSubmitMultipartForm(theform,sid,params){
	//verify that they passed in the form object
	if(undefined == theform){
		alert("No form object passed to ajaxSubmitMultipartForm");
		return false;
	}
	if(undefined=='sid'){sid='centerpop';}
	if(undefined == params){params={};}
	let url=theform.getAttribute('action');
	//console.log(theform);return false;
	//validate form fields
	let ok=submitForm(theform,1,0,1);
	if(!ok){return false;}
	let data = new FormData();
	//AjaxRequestUniqueId
	data.append('AjaxRequestUniqueId',Math.random().toString(36).substr(2, 9));
	let request = new XMLHttpRequest();
	request.formdata=data;
	//attach files
	let j=0;
	for(let i=0;i<theform.length;i++){
		if(undefined == theform[i].name){continue;}
		if(theform[i].name.length==0){continue;}
		if(theform[i].name == 'setprocessing'){
			params.setprocessing=theform[i].value;
		}
		switch(theform[i].type.toLowerCase()){
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
				data.append(theform[i].name,theform[i].value);
			break;
			case 'select-one':
				if (theform[i].selectedIndex>=0) {
					data.append(theform[i].name,theform[i].options[theform[i].selectedIndex].value);
				}
				break;
			case 'select-multiple':
				for (j=0; j<theform[i].options.length; j++) {
					if (theform[i].options[j].selected) {
						data.append(theform[i].name,theform[i].options[j].value);
					}
				}
			break;
			case 'checkbox':
			case 'radio':
				if (theform[i].checked) {
					data.append(theform[i].name,theform[i].value);
				}
			break;
			case 'file':
				if(theform[i].files.length==1){
					data.append(theform[i].name,theform[i].files[0]);
				}
				else{
					for (j=0; j<theform[i].files.length; j++) {
						data.append(theform[i].name+'_'+j,theform[i].files[j]);
					}
				}
			break;
		}
	}
	//return false;
	request.sid=sid;
	request.params=params;
	//event: loadstart event is fired when a request has started to load data.
	request.addEventListener('loadstart', function(e) {
		if(undefined == this.params.setprocessing){
			if(this.params.setprocessing == 0){return false;}
		}
		//console.log('Load: '+this.sid);
		// request.response will hold the response from the server
		var txt=getProcessingDiv(this.sid);
		if(undefined == this.params.setprocessing){
			setText(this.params.setprocessing,txt);
			return false;
		}
        if(this.sid.indexOf('centerpop') != -1){
        	//console.log('setting a centerpop');
			popUpDiv('',{id:this.sid,width:300,height:50,notop:1,nobot:1,noborder:1,nobackground:1,bodystyle:"padding:0px;border:0px;background:none;"});
			var atitle='Processing Request';
			setCenterPopText(this.sid,txt,{title:atitle,drag:false,close_bot:false});
        }
        else if(this.sid=='modal'){
        	let title='';
            if(undefined != this.params.title){title=this.params.title;}
            else{title='Processing Request';}
            //console.log('setting a modal');
			let modal=wacss.modalPopup(txt,title,{overlay:1});
		}
		else{
			//console.log('setting a custom');
			this.prevtxt=getText(this.sid);
			setText(this.sid,txt);
		}
	});
	// Upload progress on request.upload
	request.upload.sid=sid;
	request.upload.params=params;
	request.upload.xhr=request;
	request.upload.addEventListener('progress', function(e) {
		let percent_complete = parseInt((e.loaded / e.total)*100);
		//console.log('Percent Complete: '+percent_complete+'%');
		//console.log(this.sid);
		let pobj=document.querySelector('#processing_div .processing_message');
		if(undefined != pobj){
			setText(pobj,percent_complete+'%');
			return false;
		}
		let txt=getProcessingDiv(this.sid,percent_complete+'%');
		if(this.sid.indexOf('centerpop') != -1){
			//console.log(percent_complete+'% for centerpop x');
			let sidobj=getObject(this.sid);
			if(undefined == sidobj){
				this.xhr.abort();
				return false;
			}
			
			updateCenterPopText(this.sid,txt);
        }
        else if(this.sid=='modal'){
        	let sidobj=getObject('wacss_modal');
			if(undefined == sidobj){
				this.xhr.abort();
				return false;
			}
        	//console.log(percent_complete+'% for model');
			let modal=wacss.modalPopup(txt);
		}
		else{
			//console.log(percent_complete+'% for custom sid');
			setText(this.sid,txt);
		}
	});
	// event: load - fired when an XMLHttpRequest transaction completes successfully.
	request.addEventListener('load', function(e) {
		console.log('Load: '+this.sid);
		// request.response will hold the response from the server
        if(this.sid.indexOf('centerpop') != -1){
			//setCenterPopText(this.sid,txt,{title:atitle,drag:false,close_bot:false});
			updateCenterPopText(this.sid,request.response);
        }
        else if(this.sid=='modal'){
        	let title='';
            if(undefined != this.params.success_title){title=this.params.success_title;}
            else{title='Success';}
            
			let modal=wacss.modalPopup(request.response,title);
		}
		else{
			setText(this.sid,request.response);
		}
	});
	// event: error event is fired when the request encountered an error.
	request.addEventListener('error', function(e) {
		// request.response will hold the response from the server
        if(this.sid.indexOf('centerpop') != -1){
			//setCenterPopText(this.sid,txt,{title:atitle,drag:false,close_bot:false});
			updateCenterPopText(this.sid,request.response,'Error');
        }
        else if(this.sid=='modal'){
        	let title='';
            if(undefined != this.params.error_title){title=this.params.error_title;}
            else{title='Error';}
			let modal=wacss.modalPopup(request.response,title,{overlay:1});
		}
		else{
			setText(this.sid,request.response);
		}
	});
	// event: abort - fired when a request has been aborted
	request.addEventListener('abort', function(e) {
		//console.log('abort listener called');
		// request.response will hold the response from the server
        if(this.sid.indexOf('centerpop') != -1){
			removeId(this.sid);
        }
        else if(this.sid=='modal'){
        	wacss.modalClose();
		}
		else{
			setText(this.sid,this.prevtxt);
		}
	});
	

	// If server is sending a JSON response then set JSON response type
	//request.responseType = 'json';

	// Send POST request to the server side script
	//console.log('Posting Multipart Form...');
	request.open('post', url); 
	request.send(data);
	return false;

}
//--------------------------
//--Submit form using ajax
function ajaxPost(theform,sid,tmeout,callback,returnreq,abort_callback) {
	//check for file fields
	let fields=theform.querySelectorAll('input[type="file"]');
	for(let i=0;i<fields.length;i++){
		if(undefined == fields[i].id){continue;}
		let img=theform.querySelector('label[for="'+fields[i].id+'"] img');
		if(undefined != img){
			let name=fields[i].value.split('\\').pop().split('/').pop();
			if(undefined != theform[fields[i].name]){
				fields[i].parentNode.removeChild(fields[i]);
			}
			let h=document.createElement('input');
			h.type='hidden';
			h.name=fields[i].name;
			h.value=name;
			theform.appendChild(h);
			h=document.createElement('textarea');
			h.style.display='none';
			h.name=fields[i].name+'_base64';
			h.value=name+';'+name+';'+img.src;
			theform.appendChild(h);
		}
	}
	//verify that they passed in the form object
	if(undefined == theform){
		alert("No form object passed to ajaxPost");
		return false;
		}
	//Pass form through validation before calling ajax
	if(undefined != tmeout && tmeout=='novalidation'){
		//default to 60 minutes
		tmeout=3600000;
	}
	else{
		var ok=submitForm(theform,1,0,1);
		if(!ok){return false;}
	}
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
	//default timeout to 60 minutes with a 3 minute minimum
	if(undefined == tmeout){tmeout=3600000;}
	if(tmeout < 180000){tmeout=180000;}
	var lcsid=sid.toLowerCase();
	var cb=callback.toLowerCase();
	if(undefined == document.getElementById(sid) && 
		lcsid != 'pop' && 
		lcsid != 'null' &&
		lcsid != 'modal' && 
		cb.indexOf('popupdiv') == -1 && 
		cb.indexOf('centerpop') == -1 && 
		lcsid.indexOf('popupdiv') == -1 && 
		lcsid.indexOf('centerpop') == -1){
		alert('Error1 in ajaxPost\n'+sid+' is not defined as a valid object id');
		return false;
    	}
    if(typeof(AjaxRequest.ActiveAjaxGroupRequests[sid]) != 'undefined'){
		ajaxAbort(sid);
	}
	//show processing?
	var showprocessing=true;
	if(undefined != theform.setprocessing){
		if(theform.setprocessing.value.toLowerCase()=='false'){showprocessing=false;}
		if(theform.setprocessing.value.toLowerCase()=='0'){showprocessing=false;}
	}
	else if(undefined != theform.showprocessing){
		if(theform.showprocessing.value.toLowerCase()=='false'){showprocessing=false;}
		if(theform.showprocessing.value.toLowerCase()=='0'){showprocessing=false;}
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
	//cp_title
	var cp_title='';
	if(undefined != theform.cp_title){
		cp_title=theform.cp_title.value;
	}
	else if(undefined != theform.centerpop_title){
		cp_title=theform.centerpop_title.value;
	}
	var title='';
	if(undefined != theform.title){
		title=theform.title.value;
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
			,'var2':cp_title
			,'var3':title
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
				else if(lname=='modal'){
					let modal=wacss.modalPopup('<span class="icon-spin7 w_spin"></span>','Processing Request',{overlay:1});
				}
				else if(lname == 'pop'){
					this.popNumber=popWindow(getProcessingDiv(sid),'processing request...');
					}
				else if(lname == 'null'){
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
				var lname=dname.toLowerCase();
				var cb=this.callback;
				cb=cb.toLowerCase();
				var val="<b style=\"color:red\">ajaxPost Timed Out Error</b>";
				if(cb.indexOf('centerpop') != -1 || dname.indexOf('centerpop') != -1){
					setCenterPopText(dname,val);
                }
                else if(lname=='modal'){
					let modal=wacss.modalPopup(val,'Timeout Error',{overlay:1});
				}
                else if(lname == 'pop'){
					popText(this.popNumber,val,'timed out...');
				}
				else if(lname == 'null'){
					removeId('centerpop');                           
				}
				else{setText(dname,val);}
				if(undefined != theform.setprocessing){
					setText(theform.setprocessing.value,'');
				}
			}
			,'onError':function(req){
				var dname = this.groupName;
				var lname=dname.toLowerCase();
				var cb=this.callback;
				cb=cb.toLowerCase();
				var val='<div style="display:none" id="ajaxOnError">'+req.responseText+'</div>';
				if(cb.indexOf('centerpop') != -1 || dname.indexOf('centerpop') != -1){
					setCenterPopText(dname,val);
                }
                else if(lname=='modal'){
					let modal=wacss.modalPopup(val,'ajaxPost Error',{overlay:1});
				}
                else if(lname == 'pop'){
					popText(this.popNumber,val,'error');
				}
				else if(lname == 'null'){
					removeId('centerpop');
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
                    else if(lname == 'pop'){
						popText(this.popNumber,val,this.var2);
					}
					else if(lname == 'null'){
						removeId('centerpop');
					}
                    else if(cb.indexOf('centerpop') != -1){
						setCenterPopText(dname,val);
                    }
                    else if(cb=='modal'){
                    	let title='';
                    	if(this.var2.length){title=this.var2;}
                    	else if (this.var3.length){title=this.var3;}
                    	else{title='Success';}
						let modal=wacss.modalPopup(val,title,{overlay:1});
					}
					else{
						window[this.callback](req,dname);
						//var str=this.callback+'(req);';
						//eval(str);
						}
                	}
				else{
					if(lname.indexOf('popupdiv') != -1){
						popUpDiv(val,{id:dname,center:1,drag:1});
						centerObject(dname);
                    	}
                    else if(lname == 'pop'){
						popText(this.popNumber,val,this.var2);
					}
					else if(lname == 'null'){
						removeId('centerpop');
					}
                    else if(lname.indexOf('centerpop') != -1){
						setCenterPopText(dname,val);
                    }
                    else if(lname=='modal'){
                    	let title='';
                    	if(undefined != this.var2 && this.var2.length){title=this.var2;}
                    	else if (undefined != this.var3 && this.var3.length){title=this.var3;}
                    	else{title='Success';}
						let modal=wacss.modalPopup(val,title,{overlay:1});
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
//-------------------------
function updateCenterPopText(cpid,cptext,cptitle){
	let cp=getObject(cpid);
	if(undefined == cp){
		console.log('updateCenterPopText Error: No cp');
		return false;
	}
	if(undefined == cptext){cptext='';}
	if(undefined == cptitle){cptitle='';}
	console.log(cp);
	if(cptext.length){
		let cpt=cp.querySelector('.w_centerpop_content');
		if(undefined != cpt){
			console.log('updating centerpop content to'+cptext);
			setText(cpt,cptext);
		}
		else{
			console.log('updateCenterPopText Error: No cpt');
		}
	}
	if(cptitle.length){
		let cptt=cp.querySelector('.w_centerpop_title');
		if(undefined != cptt){
			setText(cptt,cptitle);
		}
		else{
			console.log('updateCenterPopText Error: No cptt');
		}
	}
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
	//console.log(cptext);
	if(cptext.includes('w_centerpop_content')){
		txt=cptext;
	}
	else{
		//console.log('NOT found');
		txt += '<div class="w_centerpop">'+"\n";
		if(params.title.length){
			txt += '	<div class="w_centerpop_title">'+params.title+'</div>'+"\n";
		}
		if(params.close_top){
			txt += '	<div class="w_centerpop_close_top icon-cancel" title="Click to close" onclick="ajaxAbort(\''+cpid+'\');"></div>'+"\n";
		}
		txt += '	<div class="w_centerpop_content">'+"\n";
		txt += '		'+cptext+"\n";
		txt += '	</div>'+"\n";

		txt += '	<img src="/wfiles/clear.gif" width="1" height="1" style="position:absolute;top:0px;right:5px;" onload="centerObject(\''+cpid+'\');" alt="" />'+"\n";
		if(params.close_bot){
			txt += '	<div class="w_centerpop_close_bot icon-cancel" style="font-size:18px;" title="Click to close" onclick="ajaxAbort(\''+cpid+'\');"></div>'+"\n";
		}
		txt += '</div>'+"\n";
		cptext=txt;
	}
	//set the text in cpid
	//console.log(txt);
	setText(cpid,txt);
	if(!cptext.includes('w_centerpop_close_top') && cptext.includes('w_centerpop_title')){
		let cpobj=getObject(cpid);
		if(undefined != cpobj){
			let tobj=cpobj.querySelector('div.w_centerpop_title');
			if(undefined != tobj){
				let div = document.createElement("div");
				div.setAttribute("class",'w_centerpop_close_top icon-cancel');
				div.setAttribute("title",'Click to close');
				div.setAttribute("onclick",'ajaxAbort(\''+cpid+'\');');
				tobj.appendChild(div);
			}

		}
	}
	//center the object
	if(params.center){
		centerObject(cpid);
	}
	//make the object draggable
	if(params.drag){
		var dObj=getObject(cpid);
		var dObjMove=document.querySelector('[class="w_centerpop_title"]');
		if(undefined != dObjMove){
			Drag.init(dObjMove,dObj);
			dObjMove.style.position='relative';
			dObj.style.position='absolute';
		}
	}
}
//--------------------------
function callWaSQL(id,name,params){
	var url='/cgi-bin/wasql.pl';
	ajaxGet(url,name,'&_view='+id+'&'+params);
	}
//--------------------------
function ajaxAbort(sid){
	console.log('ajaxAbort'+sid);
	if(sid=='modal'){
		wacss.modalClose();
		return false;
	}
	if(typeof(AjaxRequest.ActiveAjaxGroupRequests[sid]) != 'undefined'){
		var req=AjaxRequest.ActiveAjaxGroupRequests[sid];
		//check for abort_callback
		if(undefined != req.abort_callback && req.abort_callback.length){
			req.status='aborted';
			window[req.abort_callback](req);
			//var str=req.abort_callback+'(req);';
			//eval(str);
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
function ajaxGet(url,sid,xparams,callback,tmeout,nosetprocess,returnreq,newtitle,newurl,abort_callback,append){
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
    	if(undefined != xparams.url){newurl=xparams.url;}
    	if(undefined != xparams.title){newtitle=xparams.title;}
    	if(undefined != xparams.append){append=xparams.append;}
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
			if(key == 'append'){continue;}
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
	//default timeout to 60 minutes with a 3 minute minimum
	if(undefined == tmeout){tmeout=3600000;}
	if(tmeout < 180000){tmeout=180000;}

	if(undefined == nosetprocess){
    	if(nosetprocess){showprocessing=false;}
	}
	var lcsid=sid.toLowerCase();
	var cb=callback.toLowerCase();
	if(undefined == document.getElementById(sid) && 
		lcsid != 'pop' && 
		lcsid != 'modal' && 
		cb.indexOf('popupdiv') == -1 && 
		cb.indexOf('centerpop') == -1 && 
		lcsid.indexOf('popupdiv') == -1 &&
		lcsid.indexOf('centerpop') == -1){
		alert('Error3 in ajaxGet\n'+sid+' is not defined as a valid object id in the DOM');
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
    		'var5':append,
			'groupName':sid,
			'prevValue':getText(sid),
			'onGroupBegin':function(req){
				var dname=this.groupName;
				var lname=dname.toLowerCase();
				var cb=this.callback.toLowerCase();
				if(cb.indexOf('centerpop') != -1 || lname.indexOf('centerpop') != -1){
					var sidObj=getObject(sid);
					if(undefined == sidObj){
						var txt=getProcessingDiv(sid);
						popUpDiv('',{id:dname,width:300,height:50,notop:1,nobot:1,noborder:1,nobackground:1,bodystyle:"padding:10px;border:0px;background:#FFF;"});
						var atitle='Processing Request';
						setCenterPopText(dname,txt,{title:atitle,drag:false,close_bot:false});
					}
					if(this.showprocessing){
						setProcessing(this.showprocessingdiv);
					}
				}
				else if(lname=='modal'){
					let modal=wacss.modalPopup('<span class="icon-spin7 w_spin"></span>','Processing Request',{overlay:1});
				}
				else if(lname == 'pop'){
					this.popNumber=popWindow(getProcessingDiv(sid),'processing request...');
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
                else if(lname=='modal'){
					let modal=wacss.modalPopup('ajaxGet Timed Out','Timed out Processing Request',{overlay:1});
				}
                else if(lname == 'pop'){
					popText(this.popNumber,val,'timed out...');
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
                else if(lname=='modal'){
					let modal=wacss.modalPopup(req.responseText,'Error Processing Request',{overlay:1});
				}
                else if(lname == 'pop'){
					popText(this.popNumber,val,'error');
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
                    else if(lname == 'pop'){
						popText(this.popNumber,req.responseText,this.var2);
					}
                    else if(cb.indexOf('centerpop') != -1){
						if(undefined != this.var2 && this.var2.length > 0){
							setCenterPopText(dname,req.responseText,{title:this.var2});
						}
						else{
                        	setCenterPopText(dname,req.responseText);
						}
                    }
                    else if(lname=='modal'){
                    	let title='';
                    	if(this.var2.length){title=this.var2;}
                    	else if (this.var3.length){title=this.var3;}
                    	else{title='Success';}
						let modal=wacss.modalPopup(req.responseText,title,{overlay:1});
					}
					else{
						window[this.callback](req,dname);
						//var str=this.callback+'(req);';
						//eval(str);
						}
                	}
				else{
					var val=req.responseText;
					if(lname.indexOf('popupdiv') != -1){
						popUpDiv(val,{id:dname,center:1,drag:1});
						centerObject(dname);
                    }
                    else if(lname == 'pop'){
						popText(this.popNumber,req.responseText,this.var2);
					}
                    else if(lname.indexOf('centerpop') != -1){
						if(undefined != this.var2 && this.var2.length > 0){
							setCenterPopText(dname,req.responseText,{title:this.var2});
						}
						else{
                        	setCenterPopText(dname,req.responseText);
						}
                    }
                    else if(lname=='modal'){
                    	let title='';
                    	if(this.var2.length){title=this.var2;}
                    	else if (this.var3.length){title=this.var3;}
                    	else{title='Success';}
						let modal=wacss.modalPopup(req.responseText,title,{overlay:1});
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
						//append?
						if(this.var5){
                        	val=getText(dname)+val;
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
	if (typeof(args)!="undefined" && args !== null) {
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
	if (myRequest===null) { return false; }
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
				default:
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
  		document.addEventListener('DOMContentLoaded', initialize, commonPassiveEventListener(true));
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

	  slider.addEventListener('mousedown', onDragStart, commonPassiveEventListener(true));
	  slider.addEventListener('keydown', onKeyDown, commonPassiveEventListener(true));
	  slider.addEventListener('focus', onFocus, commonPassiveEventListener(true));
	  slider.addEventListener('blur', onBlur, commonPassiveEventListener(true));

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
	    this.addEventListener('mousemove', onDrag, commonPassiveEventListener(true));
	    this.addEventListener('mouseup', onDragEnd, commonPassiveEventListener(true));
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
	function style(element, styles) {
		for (var prop in styles){
	    	element.style.setProperty(prop, styles[prop], 'important');
		}
	}
})();