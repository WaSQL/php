var wacss = {
	version: '1.1',
	author: 'WaSQL.com',
	chartjs:{},
	addClass: function(element, classToAdd) {
		element=wacss.getObject(element);
		if(undefined == element){return false;}
		if(undefined==element.className){
			element.className=classToAdd;
			return true;
		}
	    var currentClassValue = element.className;

	    if (currentClassValue.indexOf(classToAdd) == -1) {
	        if ((currentClassValue == null) || (currentClassValue === "")) {
	            element.className = classToAdd;
	        } else {
	            element.className += " " + classToAdd;
	        }
	    }
	},
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
	copy2Clipboard: function(str){
		const el = document.createElement('textarea');
	  	el.value = str;
	  	document.body.appendChild(el);
	  	el.select();
	  	document.execCommand('copy');
	 	document.body.removeChild(el);
	 	wacss.toast('Copy Successful');
	 	return true;
	},
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
		el.className='toast dismiss';
		setTimeout(function(){
			wacss.removeObj(el);
		},1000);
	},
	getAllAttributes: function(obj){
		//info: get all attributes of a specific object or id
		var node=wacss.getObject(obj);
		var rv = {};
	    for(var i=0; i<node.attributes.length; i++){
	        if(node.attributes.item(i).specified){
	            rv[node.attributes.item(i).nodeName]=node.attributes.item(i).nodeValue;
				}
			}
	    return rv;
	},
	getObject: function(obj){
		//info: returns the object identified by the object or id passed in
		if(typeof(obj)=='object'){return obj;}
	    else if(typeof(obj)=='string'){
	    	//try getElementById
			if(undefined != document.getElementById(obj)){return document.getElementById(obj);}
			else if(undefined != document.getElementsByName(obj)){
				var els=document.getElementsByName(obj);
				if(els.length ==1){return els[0];}
	        	}
			else if(undefined != document.all[obj]){return document.all[obj];}
	    	//try querySelector
	    	let qso=document.querySelector(obj);
	    	if(typeof(qso)=='object'){return qso;}
	    }
	    return null;
	},
	getParent: function(obj,name){
		if(undefined == obj){return null;}
		if(undefined != name){
			var count = 1;
			while(count < 1000) {
				if(undefined == obj.parentNode){return null;}
				obj = obj.parentNode;
				if(!typeof(obj)){return null;}
				if(obj.nodeName.toLowerCase() == name.toLowerCase()){
					return obj;
				}
				count++;
			}
			return null;	
		}
		var cObj=wacss.getObject(obj);
		if(undefined == cObj){return abort("undefined object passed to getParent");}
		if(undefined == cObj.parentNode){return cObj;}
		var pobj=cObj.parentNode;
		if(typeof(cObj.parentNode) == "object"){return cObj.parentNode;}
		else{return wacss.getParent(pobj);}
	},
	getSiblings: function (elem) {
		// Setup siblings array and get the first sibling
		var siblings = [];
		var sibling = elem.parentNode.firstChild;

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
	        var p = (Math.random().toString(16)+"000000000").substr(2,8);
	        return s ? "-" + p.substr(0,4) + "-" + p.substr(4,4) : p ;
	    }
	    return _p8() + _p8(true) + _p8(true) + _p8();
	},
	in_array: function(needle, haystack) {
	    let length = haystack.length;
	    for(let i = 0; i < length; i++) {
	    	//console.log('in_array',haystack[i],needle);
	        if(haystack[i] == needle){return true;}
	    }
	    return false;
	},
	init: function(){
		/*wacssedit*/
		wacss.initWacssEdit();
		wacss.initChartJs();
		wacss.initTruncate();
	},
	initChartJs: function(initid){
		let list=document.querySelectorAll('div.chartjs');
		let colors = new Array(
	        'rgb(255, 159, 64)',
	        'rgb(75, 192, 192)',
	        'rgb(255, 99, 132)',
	        'rgb(54, 162, 235)',
	        'rgb(153, 102, 255)',
	        'rgb(201, 203, 207)'
	    );
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
			let type=list[i].getAttribute('data-type').toLowerCase();
			let datadiv=wacss.getObject(list[i].id+'_data');
			let foundchart=0;
			switch(type){
				case 'guage':
					if(undefined != wacss.chartjs[list[i].id]){
						//check for canvas
						let ck=list[i].querySelector('canvas');
						if(undefined != ck){
							//update existing chart
							let gv=parseInt(datadiv.innerText);
							let gv1=parseInt(180*(gv/100));
							if(gv1 > 180){gv1=180;}
							let gv2=180-gv1;
							wacss.chartjs[list[i].id].config.data.datasets[0].data=[gv1,gv2];
	        				wacss.chartjs[list[i].id].update();
	        				foundchart=1;
		        		}
					}
					if(foundchart==0){
						let gv=parseInt(datadiv.innerText);
						let gv1=parseInt(180*(gv/100));
						if(gv1 > 180){gv1=180;}
						let gv2=180-gv1;
						let color='#009300';
						if(undefined != list[i].getAttribute('data-color')){
	        				color=list[i].getAttribute('data-color');
	        			}
	        			
						//console.log(type);
						let gconfig = {
							type:'doughnut',
							data: {
								datasets: [{
									data: [gv1,gv2],
	                        		backgroundColor: [color,'#e0e0e0'],
	                        		borderWidth: 0
	                    		}]
	            			},
	            			options: {
	            				title:{display:false},
	                			circumference: Math.PI,
	                			rotation: -1 * Math.PI,
	                			responsive: true,
	                    		animation: {animateScale:false,animateRotate:true}
	            			}
	        			};
	        			if(undefined != list[i].getAttribute('data-title')){
	        				let title=list[i].getAttribute('data-title');
	        				gconfig.options.title={display:true,padding:0,position:'bottom',text:title};
	        			}
	        			if(undefined != list[i].getAttribute('data-title-position')){
	        				gconfig.options.title.position=list[i].getAttribute('data-title-position');
	        			}
	        			let gcanvas=document.createElement('canvas');
	        			list[i].appendChild(gcanvas);
	        			let gctx = gcanvas.getContext('2d');
						wacss.chartjs[list[i].id]  = new Chart(gctx, gconfig);
					}
				break;
				case 'line':
				case 'bar':
					//console.log('barline');
					if(undefined != wacss.chartjs[list[i].id]){
						//check for canvas
						let ck=list[i].querySelector('canvas');
						if(undefined != ck){	
							let udatasets=datadiv.querySelectorAll('dataset');
		        			for(let ud=0;ud<udatasets.length;ud++){
		        				//require data-label
								if(undefined == udatasets[ud].getAttribute('data-label')){continue;}
		        				let json=JSON.parse(udatasets[ud].innerText);      				
								let udataset={
									label:udatasets[ud].getAttribute('data-label'),
									backgroundColor: colors[ud],
		                            borderColor: colors[ud],
		                            pointColor: colors[ud],
		                            type:'line',
		                            pointRadius:0,
									data: json,
									fill:false,
									lineTension:0,
									borderWidth: 2
								};
								wacss.chartjs[list[i].id].config.data.datasets[ud] = udataset;
		        			}
		        			wacss.chartjs[list[i].id].update();
		        			foundchart=1;
		        		}
					}
					if(foundchart==0){
						if(undefined == list[i].getAttribute('data-x')){continue;}
						if(undefined == list[i].getAttribute('data-y')){continue;}
						let xkey=list[i].getAttribute('data-x');
						let ykey=list[i].getAttribute('data-y');
						let lconfig = {
							type:type,
							data:{
								labels:[],
								datasets:[]
							}
						};
						lconfig.options={
							responsive: true,
							scales: {
								xAxes: [{
									type: 'time',
									distribution: 'series',
									ticks: {
										source: 'data',
										autoSkip: true
									}
								}],
								yAxes: [{
									scaleLabel: {
										display: true,
										labelString: list[i].getAttribute('data-ylabel')
									}
								}]
							},
							tooltips: {
								intersect: false,
								mode: 'index',
								callbacks: {
									label: function(tooltipItem, myData) {
										var label = myData.datasets[tooltipItem.datasetIndex].label || '';
										if (label) {
											label += ': ';
										}
										label += parseFloat(tooltipItem.value).toFixed(2);
										return label;
									}
								}
							}
						};
						if(undefined != list[i].getAttribute('data-ysteps')){
							if(undefined == lconfig.options.scales.yAxes.ticks){lconfig.options.scales.yAxes.ticks={};}
							lconfig.options.scales.yAxes.ticks.steps=parseInt(list[i].getAttribute('data-ysteps'));
						}
						if(undefined != list[i].getAttribute('data-ystepvalue')){
							if(undefined == lconfig.options.scales.yAxes.ticks){lconfig.options.scales.yAxes.ticks={};}
							lconfig.options.scales.yAxes.ticks.stepValue=parseInt(list[i].getAttribute('data-ystepvalue'));
						}
						if(undefined != list[i].getAttribute('data-ystepsize')){
							if(undefined == lconfig.options.scales.yAxes.ticks){lconfig.options.scales.yAxes.ticks={};}
							lconfig.options.scales.yAxes.ticks.stepSize=parseInt(list[i].getAttribute('data-ystepsize'));
						}
						if(undefined != list[i].getAttribute('data-ymin')){
							if(undefined == lconfig.options.scales.yAxes.ticks){lconfig.options.scales.yAxes.ticks={};}
							lconfig.options.scales.yAxes.ticks.min=parseInt(list[i].getAttribute('data-ymin'));
						}
						if(undefined != list[i].getAttribute('data-ymax')){
							if(undefined == lconfig.options.scales.yAxes.ticks){lconfig.options.scales.yAxes.ticks={};}
							lconfig.options.scales.yAxes.ticks.max=parseInt(list[i].getAttribute('data-ymax'));
						}
						if(undefined != list[i].getAttribute('data-ybeginatzero')){
							if(undefined == lconfig.options.scales.yAxes.ticks){lconfig.options.scales.yAxes.ticks={};}
							lconfig.options.scales.yAxes.ticks.beginAtZero=true;
						}
						console.log(lconfig.options);
	        			//look for datasets;
	        			let labels=[];
	        			let datasets=datadiv.querySelectorAll('dataset');
	        			for(let d=0;d<datasets.length;d++){
	        				//require data-label
							if(undefined == datasets[d].getAttribute('data-label')){continue;}
	        				let json=JSON.parse(datasets[d].innerText);       				
							let dataset={
								label:datasets[d].getAttribute('data-label'),
								backgroundColor: colors[d],
	                            borderColor: colors[d],
	                            pointColor: colors[d],
	                            type:'line',
	                            pointRadius:0,
								data: json,
								fill:false,
								lineTension:0,
								borderWidth: 2
							};
							lconfig.data.datasets.push(dataset);
	        			}
	    				/* set options */
	        			
						//console.log(lconfig);
	        			let lcanvas=document.createElement('canvas');
	        			list[i].appendChild(lcanvas);
	        			let lctx = lcanvas.getContext('2d');
						wacss.chartjs[list[i].id]  = new Chart(lctx, lconfig);
					}
				break;
				case 'pie':
					if(undefined != wacss.chartjs[list[i].id]){
						//check for canvas
						let ck=list[i].querySelector('canvas');
						if(undefined != ck){
							//update existing pie chart
							let labels=[];
		        			let data=[];
		        			let datasets=datadiv.querySelectorAll('dataset');
		        			let json=JSON.parse(datasets[0].innerText); 
		        			for(let label in json){
		        				labels.push(label);
		        				data.push(json[label]);
		        			}
		        			wacss.chartjs[list[i].id].config.data.datasets[0].data=data;
		        			wacss.chartjs[list[i].id].config.data.labels=labels;
		        			//console.log(wacss.chartjs[list[i].id].config);
	        				wacss.chartjs[list[i].id].update();
	        				foundchart=1;
		        		}
					}
					if(foundchart==0){
						//look for datasets;
	        			let labels=[];
	        			let data=[];
	        			let datasets=datadiv.querySelectorAll('dataset');
	        			let json=JSON.parse(datasets[0].innerText); 
	        			for(let label in json){
	        				labels.push(label);
	        				data.push(json[label]);
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
	        						display: false
	        					},
	        					rotation: -0.7 * Math.PI
	        				}
	        			};

	        			let pcanvas=document.createElement('canvas');
	        			list[i].appendChild(pcanvas);
	        			let pctx = pcanvas.getContext('2d');
						wacss.chartjs[list[i].id]  = new Chart(pctx, pconfig);
						//console.log(wacss.chartjs[list[i].id].config);
					}
				break;
			}
		}
		return true;
	},
	initTruncate: function(){
		/*convert texteara to contenteditable div*/
		let list=document.querySelectorAll('.truncate');
		for(let i=0;i<list.length;i++){
			if(list[i].innerText.length==0){continue;}
			//check to see if we have already initialized this element
			if(undefined != list[i].getAttribute('data-initialized')){continue;}
			list[i].setAttribute('data-initialized',1);
			list[i].setAttribute('data-tooltip',list[i].innerHTML);
			list[i].setAttribute('data-tooltip_position','bottom');
		}
	},
	initWacssEdit: function(){
		/*convert texteara to contenteditable div*/
		let list=document.querySelectorAll('textarea.wacssedit');
		for(let i=0;i<list.length;i++){
			if(undefined == list[i].id){continue;}
			//check to see if we have already initialized this element
			if(undefined != list[i].getAttribute('data-initialized')){continue;}
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
				let eid=this.getAttribute('data-editor');
				let tobj=wacss.getObject(eid);
				if(undefined == tobj){
					console.log('textarea update failed: no eid: '+eid);
					return false;
				}
				tobj.innerHTML=this.innerHTML.replace(/</g,'&lt;').replace(/>/g,'&gt;');
			});
			d.setAttribute('contenteditable','true');
			d.innerHTML = list[i].value;
			list[i].original = list[i].value;
			//hide the textarea and show the contenteditable div in its place
			list[i].style.display='none';
			//wacssedit_bar
			let nav = document.createElement('nav');
			nav.className='nav w_white';
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
				'Cut':['cut','','icon-scissors',''],
				'Copy':['copy','','icon-copy','c'],
				'Quote':['formatBlock','blockquote','icon-code','q'],
				'Heading':['heading','','',''],
				'Font':['fontName','','',''],
				'Size':['fontSize','','',''],
				'Color':['','','icon-color-adjust',''],
				'Media':['','','icon-image',''],
				'Justify':['justify','','',''],
				'Form':['form','','',''],
				'Unordered List':['insertUnorderedList','','icon-list-ul',''],
				'Ordered List':['insertOrderedList','','icon-list-ol',''],
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
						/* save */
						let msave=document.createElement('button');
						msave.style.flex='1 1 auto';
						msave.title='Reset';
						msave.type='button';
						msave.innerText='Save';
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
								let htm='<div style="text-align:'+align+';"><img src="'+list[y].src+'" style="'+style+'" /></div>';
							 	document.execCommand("insertHTML", false, htm);
							}
							/* audio */
							list=this.mfiles.querySelectorAll('audio');	
							for(let y=0;y<list.length;y++){
								let htm='<div style="text-align:'+align+';"><audio src="'+list[y].src+'" style="'+style+'" controls="controls" /></div>';
							 	document.execCommand("insertHTML", false, htm);
							}
							/* video */
							list=this.mfiles.querySelectorAll('video');	
							for(let y=0;y<list.length;y++){
								let htm='<div style="text-align:'+align+';"><video src="'+list[y].src+'" style="'+style+'" controls="controls" /></div>';
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
										htm='<div style="text-align:'+align+';"><img src="'+this.murl.value+'" style="'+style+'" /></div>';
									 	document.execCommand("insertHTML", false, htm);
									break;
									case 'mp3':
										htm='<div style="text-align:'+align+';"><audio src="'+this.murl.value+'" style="'+style+'" controls="controls" /></div>';
									 	document.execCommand("insertHTML", false, htm);
									break;
									case 'mp4':
										htm='<div style="text-align:'+align+';"><video src="'+this.murl.value+'" style="'+style+'" controls="controls" /></div>';
									 	document.execCommand("insertHTML", false, htm);
									break;
									default:
										//check for youtube
										if(this.murl.value.indexOf('youtube.com') != -1){
											/* https://www.youtube.com/watch?v=_DmM_6pa-TI replaced with  https://www.youtube.com/embed/_DmM_6pa-TI */
											let src=this.murl.value.replace('watch?v=','embed/');
											htm='<div style="text-align:'+align+';"><iframe src="'+src+'" style="'+style+'" ></iframe></div>';
										}
										else{
											htm='<div style="text-align:'+align+';"><embed src="'+this.murl.value+'" style="'+style+'" controls="controls" ></embed></div>';	
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
				let dobj=getObject(tid+'_wacsseditor');
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
					case 'reset':
						if(confirm('Reset back to original?'+dobj.original)){
							dobj.innerHTML=tobj.original;
						}
						wacss.initWacssEditElements();
						return false;
					break;
					case 'print':
						var oPrntWin = window.open("","_blank","width=450,height=470,left=400,top=100,menubar=yes,toolbar=no,location=no,scrollbars=yes");
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
								let tobj=getObject(eid);
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
								let tobj=getObject(eid);
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
	listen: function(evnt, elem, func) {
	    if (elem.addEventListener){ 
	    	// W3C DOM
	    	elem.addEventListener(evnt,func,false);
	    }  
	    else if (elem.attachEvent) { 
	    	// IE DOM
	         var r = elem.attachEvent("on"+evnt, func);
	         return r;
	    }
	    else{
	    	console.log('wacss.listen failed. Browser does not support event listeners');
	    }
	},
	modalClose: function(){
		if(undefined != document.getElementById('wacss_modal_overlay')){
			return wacss.removeObj(document.getElementById('wacss_modal_overlay'));
		}
		else if(undefined != document.getElementById('wacss_modal')){
			return wacss.removeObj(document.getElementById('wacss_modal'));
		}
	},
	modalTitle: function(title){
		if(undefined != document.getElementById('wacss_modal')){
			let m=document.getElementById('wacss_modal');
			let mt=m.querySelector('.wacss_modal_title_text');
			if(undefined != mt){
				mt.innerHTML=title;
			}
			return m;
		}
	},
	modalPopup: function(htm,title,params){
		if(undefined == params){params={};}
		if(undefined == title){title='';}
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
			return m;
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
		modal_content.innerHTML=htm;
		modal.appendChild(modal_content);
		if(undefined != params.overlay){
			let modal_overlay=document.createElement('div');
			modal_overlay.id='wacss_modal_overlay';
			modal_overlay.className='wacss_modal_overlay '+params.color;
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
	preventDefault: function(evt){
		evt = evt || window.event;
		if (evt.preventDefault){evt.preventDefault();}
		if (evt.stopPropagation){evt.stopPropagation();}
	 	if (evt.cancelBubble !== null){evt.cancelBubble = true;}
	},
	removeClass: function(element, classToRemove) {
		element=wacss.getObject(element);
		if(undefined == element.className){return;}
	    var currentClassValue = element.className;

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
	setActiveTab: function(el){
	    let p=wacss.getParent(el,'ul');
	    if(p === null){return false;}
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
	    	wacss.addClass(li,'active');
	    }
	    return false;;
	},
	toast: function(msg,params){
		if(undefined == params){
			params={color:'w_red',timer:3};
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
	},
	toggleClass: function(id,class1,class2,myid,myclass1,myclass2){
		var obj=wacss.getObject(id);
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
			var obj=wacss.getObject(myid);
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
	ucwords: function(str){
		str = str.toLowerCase().replace(/\b[a-z]/g, function(letter) {
		    return letter.toUpperCase();
		});
		return str;
	},
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