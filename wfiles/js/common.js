/*common javascript routines*/
var agt=navigator.userAgent.toLowerCase();
var isFirefox=(agt.indexOf('firefox')!=-1);
var isIE=(agt.indexOf('msie')!=-1);
var isNetscape=(agt.indexOf('netscape')!=-1);
var isOpera = agt.indexOf("opera")!=-1;
var isIE = agt.indexOf("msie")!=-1 && !isOpera;
var isMoz = agt.indexOf("mozilla/5.") == 0 && !isOpera;

/* Define document.getElementById for Internet Explorer 4 */
if (typeof(document.getElementById) == "undefined")
	document.getElementById = function (id)
	{
		// Just return the corresponding index of all.
		return document.all[id];
	}
/* Code so that insertAdjacentHTML works in Mozilla Browsers*/
if(typeof HTMLElement!='undefined' && !HTMLElement.prototype.insertAdjacentElement){
	HTMLElement.prototype.insertAdjacentElement = function(where,parsedNode){
		switch (where){
			case 'beforeBegin':
				this.parentNode.insertBefore(parsedNode,this)
				break;
			case 'afterBegin':
				this.insertBefore(parsedNode,this.firstChild);
				break;
			case 'beforeEnd':
				this.appendChild(parsedNode);
				break;
			case 'afterEnd':
				if (this.nextSibling){
					this.parentNode.insertBefore(parsedNode,this.nextSibling);
					}
				else{this.parentNode.appendChild(parsedNode);}
				break;
			}
		}
	if(typeof Ext == 'undefined'){
		HTMLElement.prototype.insertAdjacentHTML = function(where,htmlStr){
			var r = this.ownerDocument.createRange();
			r.setStartBefore(this);
			var parsedHTML = r.createContextualFragment(htmlStr);
			this.insertAdjacentElement(where,parsedHTML)
			}
		}
	HTMLElement.prototype.insertAdjacentText = function(where,txtStr){
		var parsedText = document.createTextNode(txtStr)
		this.insertAdjacentElement(where,parsedText)
		}
	}
/*get all attributes*/
function getAllAttributes(obj){
	//info: get all attributes of a specific object or id
	var node=getObject(obj);
	var rv = {};
    for(var i=0; i<node.attributes.length; i++){
        if(node.attributes.item(i).specified){
            rv[node.attributes.item(i).nodeName]=node.attributes.item(i).nodeValue;
			}
		}
    return rv;
	}
/**
 * Generates a GUID string, according to RFC4122 standards.
 * @returns {String} The generated GUID.
 * @example af8a8416-6e18-a307-bd9c-f2c947bbb3aa
 * @author Slavik Meltser (slavik@meltser.info).
 * @link http://slavik.meltser.info/?p=142
 */
function guid() {
    function _p8(s) {
        var p = (Math.random().toString(16)+"000000000").substr(2,8);
        return s ? "-" + p.substr(0,4) + "-" + p.substr(4,4) : p ;
    }
    return _p8() + _p8(true) + _p8(true) + _p8();
}
/*scrollableTable*/
function scrollableTable (tableId, tableHeight, tableWidth) {
	//info:	Scrollable HTML table JavaScript code can be used to convert tables in ordinary HTML
	//info: into scrollable ones. No additional coding is necessary. All you need to do is put
	//info: header rows (if you need them) in THEAD section, table body rows in TBODY section,
	//info: footer rows (if you need them) in TFOOT section and give your table an ID field,
	//info: include the webtoolkit.scrollabletable.js file and create ScrollableTable() object after each table.
	//usage:	var t = new scrollableTable('myScrollTable', 100);
	var tableEl=getObject(tableId);
	if(undefined == tableHeight){tableHeight=400;}
	this.initIEengine = function () {
 
		this.containerEl.style.overflowY = 'auto';
		if (this.tableEl.parentElement.clientHeight - this.tableEl.offsetHeight < 0) {
			this.tableEl.style.width = this.newWidth - this.scrollWidth +'px';
		} else {
			this.containerEl.style.overflowY = 'hidden';
			this.tableEl.style.width = this.newWidth +'px';
		}
 
		if (this.thead) {
			var trs = this.thead.getElementsByTagName('tr');
			for (x=0; x<trs.length; x++) {
				trs[x].style.position ='relative';
				trs[x].style.setExpression("top",  "this.parentElement.parentElement.parentElement.scrollTop + 'px'");
			}
		}
 
		if (this.tfoot) {
			var trs = this.tfoot.getElementsByTagName('tr');
			for (x=0; x<trs.length; x++) {
				trs[x].style.position ='relative';
				trs[x].style.setExpression("bottom",  "(this.parentElement.parentElement.offsetHeight - this.parentElement.parentElement.parentElement.clientHeight - this.parentElement.parentElement.parentElement.scrollTop) + 'px'");
			}
		}
 
		eval("window.attachEvent('onresize', function () { document.getElementById('" + this.tableEl.id + "').style.visibility = 'hidden'; document.getElementById('" + this.tableEl.id + "').style.visibility = 'visible'; } )");
	};
 
 
	this.initFFengine = function () {
		this.containerEl.style.overflow = 'hidden';
		this.tableEl.style.width = this.newWidth + 'px';
 
		var headHeight = (this.thead) ? this.thead.clientHeight : 0;
		var footHeight = (this.tfoot) ? this.tfoot.clientHeight : 0;
		var bodyHeight = this.tbody.clientHeight;
		var trs = this.tbody.getElementsByTagName('tr');
		if (bodyHeight >= (this.newHeight - (headHeight + footHeight))) {
			this.tbody.style.overflow = '-moz-scrollbars-vertical';
			for (x=0; x<trs.length; x++) {
				var tds = trs[x].getElementsByTagName('td');
				tds[tds.length-1].style.paddingRight += this.scrollWidth + 'px';
			}
		} else {
			this.tbody.style.overflow = '-moz-scrollbars-none';
		}
 
		var cellSpacing = (this.tableEl.offsetHeight - (this.tbody.clientHeight + headHeight + footHeight)) / 4;
		this.tbody.style.height = (this.newHeight - (headHeight + cellSpacing * 2) - (footHeight + cellSpacing * 2)) + 'px';
 
	};
 
	this.tableEl = tableEl;
	this.scrollWidth = 16;
 
	this.originalHeight = this.tableEl.clientHeight;
	this.originalWidth = this.tableEl.clientWidth;
 
	this.newHeight = parseInt(tableHeight);
	this.newWidth = tableWidth ? parseInt(tableWidth) : this.originalWidth;

	this.tableEl.style.height = 'auto';
	this.tableEl.removeAttribute('height');
 
	this.containerEl = this.tableEl.parentNode.insertBefore(document.createElement('div'), this.tableEl);
	this.containerEl.appendChild(this.tableEl);
	this.containerEl.style.height = this.newHeight + 'px';
	this.containerEl.style.width = this.newWidth + 'px';
 
 
	var thead = this.tableEl.getElementsByTagName('thead');
	this.thead = (thead[0]) ? thead[0] : null;
 
	var tfoot = this.tableEl.getElementsByTagName('tfoot');
	this.tfoot = (tfoot[0]) ? tfoot[0] : null;
 
	var tbody = this.tableEl.getElementsByTagName('tbody');
	this.tbody = (tbody[0]) ? tbody[0] : null;
 
	if (!this.tbody) return;
 
	if (document.all && document.getElementById && !window.opera) this.initIEengine();
	if (!document.all && document.getElementById && !window.opera) this.initFFengine();
}
var loadJsCssFiles=new Array();
function loadJsFile(fname){
	return loadJsCss(fname, 'js');
	}
function loadCssFile(fname){
	return loadJsCss(fname, 'css');
	}
function loadJsCss(fname, filetype){
	if(undefined != loadJsCssFiles[fname]){
		//file already loaded
		return true;
		}
	if (filetype=="js"){ //if filename is a external JavaScript file
		var fileref=document.createElement('script');
		fileref.setAttribute("type","text/javascript");
		fileref.setAttribute("src", fname);
 		}
 	else if (filetype=="css"){ //if filename is an external CSS file
  		var fileref=document.createElement("link");
  		fileref.setAttribute("rel", "stylesheet");
  		fileref.setAttribute("type", "text/css");
  		fileref.setAttribute("href", fname);
 		}
	if (typeof fileref!="undefined"){
  		document.getElementsByTagName("head")[0].appendChild(fileref);
  		loadJsCssFiles[fname]+=1;
  		return true;
		}
	return false;
	}
/*Mobile Functions */
function mobileHideAddressBar(){
	window.scrollTo(0,1);
	}
/* showClock - shows current time based on offset in hrs */
function showClock(divid,offset){
	//info: creates a javascript clock at the specified id ofset by offset hours
    d = new Date();
   	// convert to msec
    // add local time zone offset
    // get UTC time in msec
    utc = d.getTime() + (d.getTimezoneOffset() * 60000);
	// create new Date object for different city using supplied offset
    nd = new Date(utc + (3600000*offset));
   	// return time as a string
    var t = nd.toLocaleString();
    setText(divid,t);
    setTimeout("showClock('"+divid+"',"+offset+")",1000);
	}
/* simulateEvent without actually having the event happen */
// adapted from http://stackoverflow.com/questions/6157929/how-to-simulate-mouse-click-using-javascript/6158050#6158050
function simulateEvent(element, eventName){
	//info: simulate an event without it actually happening
	//usage: simulateEvent(divid,'mouseover');
	element=getObject(element);
	var options = arguments[2] || {};
    var oEvent, eventType = null;
    var eventMatchers = {
    	'HTMLEvents': /^(?:load|unload|abort|error|select|change|submit|reset|focus|blur|resize|scroll)$/,
    	'MouseEvents': /^(?:click|dblclick|mouse(?:down|up|over|move|out))$/
	}
	var defaultOptions = {
	    pointerX: 0,
	    pointerY: 0,
	    button: 0,
	    ctrlKey: false,
	    altKey: false,
	    shiftKey: false,
	    metaKey: false,
	    bubbles: true,
	    cancelable: true
	}
	//set default option for any not passed in
	for (var property in defaultOptions){
		if(undefined == options[property]){options[property]=defaultOptions[property];}
	}
    for (var name in eventMatchers){
    	if (eventMatchers[name].test(eventName)) { eventType = name; break; }
    }
	if (!eventType){
    	console.log('simulateEvent Error: "'+eventName+'" is not a supported event. Only HTMLEvents and MouseEvents are supported');
		return false;
	}
    if (document.createEvent){
    	oEvent = document.createEvent(eventType);
    	if (eventType == 'HTMLEvents'){
        	oEvent.initEvent(eventName, options.bubbles, options.cancelable);
        }
        else{
            oEvent.initMouseEvent(eventName, options.bubbles, options.cancelable, document.defaultView,
          	options.button, options.pointerX, options.pointerY, options.pointerX, options.pointerY,
          	options.ctrlKey, options.altKey, options.shiftKey, options.metaKey, options.button, element);
        }
        element.dispatchEvent(oEvent);
    }
    else{
        options.clientX = options.pointerX;
        options.clientY = options.pointerY;
        var evt = document.createEventObject();
        oEvent = extend(evt, options);
        element.fireEvent('on' + eventName, oEvent);
    }
    return true;
}

/* isDST - returns true if Daylight Savings Time */
function isDST(){
	//info: returns true if Daylight Savings Time
	var today = new Date;
	var yr = today.getFullYear();
	var dst_start = new Date("March 14, "+yr+" 02:00:00"); // 2nd Sunday in March can't occur after the 14th
	var dst_end = new Date("November 07, "+yr+" 02:00:00"); // 1st Sunday in November can't occur after the 7th
	var day = dst_start.getDay(); // day of week of 14th
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
	}
//isFunction
function isFunction(functionToCheck) {
	var getType = {};
	return functionToCheck && getType.toString.call(functionToCheck) == '[object Function]';
}
//isNum validation
function isNum(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
}

/* abort - shows alert msg and returns false */
function abort(msg){
	//info: shows alert msg and returns false
	alert(msg);
	return false;
	}
//includeScript - a way to include a js file in a js file
function includeScript(url){
	document.write('<script type="text/javascript" src="'+ url + '"></script>');
	}

//apendText(fld,$val)
function appendText(obj,val,lf){
	var cObj=getObject(obj);
	if(undefined == cObj){return abort("undefined object passed to appendText");}
	var cval=trim(getText(obj));
	if(lf && cval.length > 0){
		cval += "\r\n";
		}
	var newval = cval + val;
	if(lf){
		newval += "\r\n";
		}
	setText(obj,newval);
	}
//Clone table row
function cloneTableRow(tid,opts){
	//info: use to clone a table row, and all elements in the row.  It will automatically incriment any names, ids, and tabindexes
	//usage: onclick="return cloneTableRow('mytableid');"
	var dTable = getObject(tid);
	if(undefined == dTable){alert('cloneTableRow Error: No table id found for '+tid);return false;}
	//set default opts
	var opt={
        copies:1,
        row:'last'
	}
	/* allow user to override default opt values */
	if(opts){
		for (var key in opts){
			opt[key]=opts[key];
		}
	}
	//get the table rows
	var rowCount = dTable.rows.length;
	//determine the row to clone. 'last' means the last row in the table
	var cloneRow = rowCount-1;
	if(isNum(opt.row)){cloneRow=opt.row;}
	//determine the next tabindex
	var lastindex=0;
	var focusobj=null;
	for (c=0;c<dTable.rows[cloneRow].cells.length;c++){
		var cellkids=dTable.rows[cloneRow].cells[c].getElementsByTagName('*');
		for(ci=0;ci<cellkids.length;ci++){
	    	if(undefined != cellkids[ci].tabIndex && isNum(cellkids[ci].tabIndex) && parseInt(cellkids[ci].tabIndex) > 0){
				lastindex=parseInt(cellkids[ci].tabIndex);
				focusobj=cellkids[ci];
			}
		}
	}
	tabx=lastindex+1;
	//loop through for each copy requested
	var xval=0;
	for(var x=0;x<opt.copies;x++){
		//create a new row
		xval++;
		var newRow =dTable.insertRow(rowCount);
		//loop through the cloneRow cells and clone each one.
		for (c=0;c<dTable.rows[cloneRow].cells.length;c++){
			//dTable.rows[cloneRow].cells[c].style.background='yellow';
            //get all elements in this cell, incrimenting the names and ids of each one
			var cellkids=dTable.rows[cloneRow].cells[c].getElementsByTagName('*');
			//insert a new cell
			var td=newRow.insertCell(c);
			//if there are no elements in this cell then just clone it
			if(cellkids.length ==0){
				var val=dTable.rows[cloneRow].cells[c].innerHTML;
				if(isNum(val)){
					val=parseInt(val)+xval;
				}
				td.innerHTML=val;
                continue;
			}
			//clone each element in the cell.
			var myregexp=/^(.+?)([0-9]+)$/;
			for(ci=0;ci<cellkids.length;ci++){
                var clone = cellkids[ci].cloneNode(true); // "deep" clone
                //change the id of this element
                if(undefined != clone.id && clone.id.length > 0){
                    var match = myregexp.exec(clone.id);
					if (match != null) {
						var newnum=parseInt(match[2])+1;
						clone.id=match[1]+newnum;
					}
					else{clone.id=clone.id+xval;}
				}
				//change the name of this element
                if(undefined != clone.name && clone.name.length > 0){
                    var match = myregexp.exec(clone.name);
					if (match != null) {
						var newnum=parseInt(match[2])+1;
						clone.name=match[1]+newnum;
					}
					else{clone.name=clone.name+xval;}
					if(undefined != clone.tagName){
						if(clone.tagName=='INPUT'){clone.value='';}
						if(clone.tagName=='TEXTAREA'){clone.innerHTML='';}
						if(clone.tagName=='SELECT'){clone.options[clone.selectedIndex].value = false;}

					}
				}
				//change the tabindex of this element
                if(undefined != clone.tabIndex && isNum(clone.tabIndex) && clone.tabIndex > 0){
                	clone.tabIndex=tabx;
                	tabx=tabx+1;
				}
				//add the clone to the table cell
                var newobj=td.appendChild(clone);
                //set the new focus element
			}
		}
		rowCount = dTable.rows.length;
	}
	//set the focus to the last element in the cloned row that has a tabindex so that when the tab action happens it goes to the next cell
	if(focusobj!=null){
		focusobj.focus();
	}
	return true;
}
function delTableRow(source,tid){
	//info: use to delete current table row
	//usage: <td><img src="/wfiles/iconsets/16/cancel.png" border="0" style="cursor:pointer" onclick="delTableRow(this,'mytableid');" /></td>
	var oRow = source.parentNode.parentNode;
    document.getElementById(tid).deleteRow(oRow.rowIndex);
}
//containsHtml - returns true if txt contains HTML tags
function containsHtml(txt){
	//info: returns true if txt contains HTML tags
	return (/[\<\>]/.test(txt));
	}
//containsHtml - returns true if txt contains HTML tags
function containsSpaces(txt){
	//info: returns true if txt contains spaces
	return (/[\ ]/.test(trim(txt)));
	}
//setActiveTab
function setActiveTab(t){
	var lid=t.parentNode.id;
	var ulobj=t.parentNode.parentNode;
	var lis=ulobj.getElementsByTagName("li");
	for(var i = 0; i < lis.length; i++) {
    	if(undefined != lis[i].id && lis[i].id==lid){setClassName(lis[i],'tab current');}
    	else{setClassName(lis[i],'tab');}
	}
}
//setOpacity
function setOpacity(obj,level) {
	//info: sets the transparency level of object or id specified. Level is in pcnt
	var cObj=getObject(obj);
	cObj.style.opacity = level;
	cObj.style.MozOpacity = level;
	cObj.style.KhtmlOpacity = level;
	cObj.style.filter = "alpha(opacity=" + (level * 100) + ");";

	}
function fadeIn(id){
	for (i = 0; i <= 1; i += (1 / 20)) {
		setTimeout("setOpacity('"+id+"'," + i + ")", i * 200);
  		}
	}

function fadeOut(id) {
	for (i = 0; i <= 1; i += (1 / 20)) {
		setTimeout("setOpacity('"+id+"'," + (1 - i) + ")", i * 200);
	}
}
function formatCurrency(num) {
    num = isNaN(num) || num === '' || num === null ? 0.00 : num;
    return parseFloat(num).toFixed(2);
}
//stripHtml - removes all html tags from string
function stripHtml(str){
	//info: removes all html tags from string
	var re = /(<([^>]+)>)/gi;
	newstr=str.replace(re, "");
	return newstr;
	}
//expand - used to create expanding divs - createExpandDiv
function expand(divid){
	var section=document.getElementById('expand_section_'+divid);
	var icon=document.getElementById('expand_icon_'+divid);
	if(section.style.display=='none'){
    	section.style.display='block';
        icon.innerHTML='<img src="/wfiles/minus.gif" border="0">';
        }
    else{
		section.style.display='none';
        icon.innerHTML='<img src="/wfiles/plus.gif" border="0">';
        }
    return false;
    }
//ajaxExpand - used to create expanding divs with ajax content - createExpandDiv
function ajaxExpand(divid,url,opts){
	var section=document.getElementById('expand_section_'+divid);
	var icon=document.getElementById('expand_icon_'+divid);
	var linkid=document.getElementById('expand_link_'+divid);
	if(section.style.display=='none'){
		var sectionTxt=getText('expand_section_'+divid);
		//alert(sectionTxt.length+', '+url);
		if(sectionTxt.length < 5){
			linkid.style.fontWeight='bold';
			ajaxGet(url,'expand_section_'+divid,opts);
		}
    	section.style.display='block';
        icon.innerHTML='<img src="/wfiles/minus.gif" border="0">';
        }
    else{
		section.style.display='none';
        icon.innerHTML='<img src="/wfiles/plus.gif" border="0">';
        }
    return false;
    }
//function goToAnchor
function scrollToDivId(pdiv,sdiv){
	//info:scroll to a div id inside of a scrollable div
	var t=document.getElementById(sdiv).offsetTop-10;
	if(t < 0){t=0;}
	document.getElementById(pdiv).scrollTop = t;
	return false;
	}
// fixE - Fix event object
function fixE(e){
	if (typeof e == 'undefined'){e = window.event;}
	if (typeof e.layerX == 'undefined'){e.layerX = e.offsetX;}
	if (typeof e.layerY == 'undefined'){e.layerY = e.offsetY;}
	return e;
	}
//getBrowserHeight
function getBrowserHeight() {
	//info: returns current browser height
	var myHeight = 0;
	if( typeof( window.innerWidth ) == 'number' ) {
		myHeight = window.innerHeight;
		} 
	else {
		if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
			//IE 6+ in 'standards compliant mode'
		    myHeight = document.documentElement.clientHeight;
		    } 
		else{
			if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
		        myHeight = document.body.clientHeight;
		      	}
			}
		}
	return myHeight;
	}
//get Cookie with said name
function getCookie(name){
	//info: get Cookie with said name
	name = trim(name);
	var cookies = document.cookie.split(";");
	var tmp;
	for (var i=0; i<cookies.length; i++){
		tmp = cookies[i].split("=");
		var cname=trim(tmp[0]);
		var cval=trim(tmp[1]);
		//alert('Looking for ['+name+']\nName: ['+cname+']\nValue: ['+cval+']');
		if (cname == name){return unescape(cval);}
		}
	return null;
	}
// parseUri 1.2.2
// (c) Steven Levithan <stevenlevithan.com>
// MIT License
function parseUri (str) {
	var parseUri = new Object;
	parseUri.options = {
		strictMode: false,
		key: ["source","protocol","authority","userInfo","user","password","host","port","relative","path","directory","file","query","anchor"],
		q:   {
			name:   "queryKey",
			parser: /(?:^|&)([^&=]*)=?([^&]*)/g
		},
		parser: {
			strict: /^(?:([^:\/?#]+):)?(?:\/\/((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?))?((((?:[^?#\/]*\/)*)([^?#]*))(?:\?([^#]*))?(?:#(.*))?)/,
			loose:  /^(?:(?![^:@]+:[^:@\/]*@)([^:\/?#.]+):)?(?:\/\/)?((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?)(((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[?#]|$)))*\/?)?([^?#\/]*))(?:\?([^#]*))?(?:#(.*))?)/
		}
	}
	var	o   = parseUri.options,
		m   = o.parser[o.strictMode ? "strict" : "loose"].exec(str),
		uri = {},
		i   = 14;
	while (i--){uri[o.key[i]] = m[i] || "";}
	uri[o.q.name] = {};
	uri[o.key[12]].replace(o.q.parser, function ($0, $1, $2) {
		if ($1) uri[o.q.name][$1] = $2;
	});
	return uri;
}
function preloadImages(images) {
	//info: Preload images passed in
	//usage: preloadImages('image1.jpg,image2.jpg,image3.jpg');
    if (document.images){
        var i = 0;
        var imageArray = new Array();
        imageArray = images.split(',');
        var imageObj = new Image();
        for(i=0; i<=imageArray.length-1; i++) {
            imageObj.src=images[i];
        	}
    	}
	}
function pxToInt(px){
	//info: returns the integer value of a CSS px string (width, height, etc);
	//usage: var pxi=pxToInt('454px'); or var pxi=pxToInt(this.style.width);
	// Set valid characters for numeric number.
    var validChars = "0123456789.";
    var convertedValue = 0;
	// Loop all characters of
    for (i = 0; i < px.length; i++) {
		// Stop search for valid numeric characters,  when a none numeric number is found.
        if (validChars.indexOf(px.charAt(i)) == -1) {
			// Start conversion if at least one character is valid.
            if (i > 0) {
                // Convert validnumbers to int and return result.
                convertedValue = parseInt(px.substring(0, i));
                return convertedValue;
                }
            }
        }

    return convertedValue;
    }
function anchorExists(aname){
	var list=GetElementsByAttribute('a', 'href', '#'+aname);
	if(list.count==1){return true;}
	return false;

}
function GetElementsByAttribute(tag, att, val){
	//info: GetElementsByAttribute - returns an array of tags that have an attribute of value.
    //usage: GetElementsByAttribute(tagname, attributename,stringtomatch)
        val=val.replace(/\[\]$/,"\\\[\\\]");
        var a, list, found = new Array(), re = new RegExp(val, 'i');
        //if(undefined != document.getElementsByTagName(tag)){return found;}
        list = document.getElementsByTagName(tag);
        //if(att=='name'){alert("Found "+list.length+" tags with name of "+tag+" ,checking for "+att+" with a val of "+val);}
        for (var i = 0; i < list.length; ++i) {
            a = list[i].getAttribute(att);
            if (undefined == a && undefined != list[i][att]){a = list[i][att];}
            if (undefined == a && att=='for' && undefined != list[i]["htmlFor"]){a = list[i]["htmlFor"];}
            if (undefined == a && att=='class' && undefined != list[i]["className"]){a = list[i]["className"];}
            if (typeof(a)=='string' && (val.length==0 || a.search(re) != -1)) {
               found[found.length] = list[i];
               }
            }
        return found;
        }
function hideElementsByAttribute(tag, att, val){
	//info: hides elements specified
    //usage: hideElementsByAttribute(tagname, attributename,stringtomatch)
	var list = GetElementsByAttribute(tag, att, val);
	for (var i = 0; i < list.length; ++i) {
		list[i].style.display='none';
    	}
    }
function highlightObj(id,c,hex){
	idObj=getObject(id);
	if(undefined == idObj){return false;}
	if(undefined == hex){hex='#fbd26c';}
	if(undefined == idObj.getAttribute('data-bgcolor')){
		var oldcolor=undefined != idObj.style.backgroundColor?idObj.style.backgroundColor:'';
    	idObj.setAttribute('data-bgcolor',oldcolor);
	}
	if(c){idObj.style.backgroundColor=hex;}
	else{idObj.style.backgroundColor=idObj.getAttribute('data-bgcolor');}
	return true;
}
function jsDocs(id){
        list = document.getElementsByTagName('script');
        //alert("Found "+list.length+" tags with name of "+tag+" ,checking for "+att+" with a val of "+val);
        for (var i = 0; i < list.length; ++i) {
			var cObj=list[i];
			var txt=getText(cObj);
			if(txt.length){}
			else{
				var vals;
				for(name in cObj){
					var type=typeof(cObj[name]);
					vals += name+' = '+type+'<br>\n';
                    if(type == 'function'){}
                    else if(type == 'string'){vals += name+' = ['+getText(cObj[name])+']<hr>\n';}
                    else if(type == 'object'){vals += name+' = '+cObj[name]+'<hr>\n';}
                	}
                if(id){setText(id,vals);}
                else{alert(vals);}
            	}
            }
        return;
        }
function getHeight(id){
	//info: getHeight - height of object. defaults to window object
	if(undefined == id){return document.body.clientHeight;}
	var idObj=getObject(id);
	if(undefined == idObj){return null;}
	if(undefined != idObj.offsetHeight && parseInt(idObj.offsetHeight) > 0){return idObj.offsetHeight;}
	if(undefined != idObj.style.height && parseInt(idObj.style.height) > 0){return idObj.style.height;}
	if(undefined != idObj.innerHeight){return idObj.innerHeight;}
	return idObj.offsetHeight;
	}
function getTextPixelWidthHeight(id){
	if(undefined == id){return 0;}
	var idObj=getObject(id);
	var d = document.createElement("div");
	d.style.position='absolute';
	d.style.float='left';
	d.innerHTML=getText(idObj);
	idObj.insertAdjacentElement('beforeEnd',d);
	var wh=getWidthHeight(d);
	removeId(d);
	return wh;
}
function getViewportHeight(){
	//info: get the viewport height
	if (typeof window.innerHeight != 'undefined'){return window.innerHeight;}
  	else if (typeof document.documentElement != 'undefined'
    	&& typeof document.documentElement.clientHeight !=
    	'undefined' && document.documentElement.clientHeight != 0){
       		return document.documentElement.clientHeight
 		}
 	else{return document.getElementsByTagName('body')[0].clientHeight;}
 	}
function getViewportWidth(){
	//info: get the viewport width
	if (typeof window.innerWidth != 'undefined'){return window.innerWidth;}
  	else if (typeof document.documentElement != 'undefined'
    	&& typeof document.documentElement.clientWidth !=
    	'undefined' && document.documentElement.clientWidth != 0){
       		return document.documentElement.clientWidth
 		}
 	else{return document.getElementsByTagName('body')[0].clientWidth;}
 	}
// methods for returning the scroll area width and height, that work on all browsers
function getScrollHeight(){
	//info: returns the scroll height of the browser
	var h = window.pageYOffset ||
           document.body.scrollTop ||
           document.documentElement.scrollTop;
	return h ? h : 0;
	}
function getScrollWidth(){
	//info: returns the scroll width of the browser
	var w = window.pageXOffset ||
           document.body.scrollLeft ||
           document.documentElement.scrollLeft;
	return w ? w : 0;
	}
function getScrollPercent(){
	var d = document.body || document.documentElement;
    var pcnt = Math.floor(d.scrollTop / (d.scrollHeight - d.clientHeight) * 100);
    return pcnt?pcnt:0;
}
//getText - returns object text
function getObject(obj){
	//info: returns the object identified by the object or id passed in
	if(typeof(obj)=='object'){return obj;}
    else if(typeof(obj)=='string'){
		if(undefined != document.getElementById(obj)){return document.getElementById(obj);}
		else if(undefined != document.getElementsByName(obj)){
			var els=document.getElementsByName(obj);
			if(els.length ==1){return els[0];}
        	}
		else if(undefined != document.all[obj]){return document.all[obj];}
    	}
    return null;
	}
/* getParent - gets parent object or its parent if parent is P or FORM */
function getParent(obj){
	//info: gets parent object
	var cObj=getObject(obj);
	if(undefined == cObj){return abort("undefined object passed to getParent");}
	if(undefined == cObj.parentNode){return cObj;}
	var pobj=cObj.parentNode;
	if(typeof(cObj.parentNode) == "object"){return cObj.parentNode;}
	else{return getParent(pobj);}
	}
/* find an objects parent by attribute. getParentNodeByAttribute('nodeName','FORM',this);*/
function getParentForm(obj) {
    var count = 1;
    while(count < 1000) {
        obj = obj.parentNode;
        if(!typeof(obj)){return null;}
        if(obj.nodeName == 'FORM'){
			return obj;
		}
        count++;
    }
	return null;
}
//getSelText - returns selected text on the page.
//<input type="button" value="Get selection" onmousedown="getSelText()">
function getSelText(fld){
	//info: returns selected text on the page
	var txt = '';
	if(undefined != fld){
		if(document.selection){
			fld.focus();
		    txt = document.selection.createRange();
	    	}
		else{
			var len = fld.value.length;
			var start = fld.selectionStart;
			var end = fld.selectionEnd;
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
	}
//getText - returns object text
function getText(obj){
	//info: returns the text of the specified object or id
	var cObj=getObject(obj);
	if(undefined == cObj){return '';}
	if(undefined != cObj.value){return cObj.value;}
    else if(undefined != cObj.innerHTML){return cObj.innerHTML;}
    else if(undefined != cObj.innerText){return cObj.innerText;}
    else{
		//alert('unable to getText on '+cObj);
    	}
    return '';
	}
// getWidth - width of object. defaults to window object
function getWidth(id){
	//info: returns the width of the specified object or id
	if(undefined == id){return document.body.clientWidth;}
	var idObj=getObject(id);
	if(undefined == idObj){return '?';}
	if(undefined != idObj.offsetWidth && parseInt(idObj.offsetWidth) > 0){return idObj.offsetWidth;}
	if(undefined != idObj.style.width && parseInt(idObj.style.width) > 0){return idObj.style.width;}
	if(undefined != idObj.innerWidth){return idObj.innerWidth;}
	return idObj.offsetWidth;
	}
function getWidthHeight(id){
	//info: returns array of the width and height of the specified object or id
	if(undefined == id){return [document.body.clientWidth,document.body.clientHeight];}
	var idObj=getObject(id);
	if(undefined == idObj){return undefined;}
	if(undefined != idObj.innerWidth){return [idObj.innerWidth,idObj.innerHeight];}
	return [idObj.offsetWidth,idObj.offsetHeight];
	}
//---------------
function getWindowScrollPosition(){
	return (window.pageYOffset !== undefined) ? window.pageYOffset :
			(document.documentElement ||
			document.body.parentNode ||
			document.body).scrollTop;
}
function getWordCount(id){
	var s=getText(id);
	//remove all extra spaces (double spaces etc) before counting
	s = s.replace(/(^\s*)|(\s*$)/gi,"");
	s = s.replace(/[ ]{2,}/gi," ");
	s = s.replace(/\n /,"\n");
	var wordcount = s.split(' ').length;
	return wordcount || 0;
}
function getXY(id){
	if(undefined == id){return [document.body.clientLeft,document.body.clientTop];}
	var idObj=getObject(id);
	if(undefined == idObj){return undefined;}
	if(undefined != idObj.offsetLeft){return [idObj.offsetLeft,idObj.offsetTop];}
	return undefined;
	}
function getPos(ctrl) {
	var pos = {x:0, y:0};
	if(undefined == ctrl){return pos;}
	if (ctrl.offsetParent){
	    while(ctrl){
	        pos.x += ctrl.offsetLeft;
	        pos.y += ctrl.offsetTop;
	        ctrl = ctrl.offsetParent;
	    	}
		}
	else if (ctrl.x && ctrl.y){
	    pos.x += ctrl.x;
	    pos.y += ctrl.y;
		}
	return pos;
	}
function findPos(obj){
	var element=getObject(obj);
    var body = document.body,
        win = document.defaultView,
        docElem = document.documentElement,
        box = document.createElement('div');
    box.style.paddingLeft = box.style.width = "1px";
    body.appendChild(box);
    var isBoxModel = box.offsetWidth == 2;
    body.removeChild(box);
    box = element.getBoundingClientRect();
    var clientTop  = docElem.clientTop  || body.clientTop  || 0,
        clientLeft = docElem.clientLeft || body.clientLeft || 0,
        scrollTop  = win.pageYOffset || isBoxModel && docElem.scrollTop  || body.scrollTop,
        scrollLeft = win.pageXOffset || isBoxModel && docElem.scrollLeft || body.scrollLeft;
    return {
        y : box.top  + scrollTop  - clientTop,
        x: box.left + scrollLeft - clientLeft};
}
/* fadeOut - if remove ==1, the id will be destroyed after fading away */
function fadeId(eid,remove){
	//info: fades, and removes if specified, specified id out
	var TimeToFade = 200.0;
  	var element=getObject(eid);
	if(undefined == element){return;}
	if(element.FadeState == null){
    	if(element.style.opacity == null || element.style.opacity == '' || element.style.opacity == '1'){
      		element.FadeState = 2;
    		}
		else{
      		element.FadeState = -2;
			}
  		}
   if(element.FadeState == 1 || element.FadeState == -1){
    	element.FadeState = element.FadeState == 1 ? -1 : 1;
    	element.FadeTimeLeft = TimeToFade - element.FadeTimeLeft;
  		}
	else{
    	element.FadeState = element.FadeState == 2 ? -1 : 1;
    	element.FadeTimeLeft = TimeToFade;
    	setTimeout("animateFade(" + new Date().getTime() + ",'" + eid + "','"+remove+"')", 33);
		}
	}
function animateFade(lastTick, eid, remove){
	var TimeToFade = 200.0;
	var curTick = new Date().getTime();
	var elapsedTicks = curTick - lastTick;
	var element=getObject(eid);
	if(undefined == element){return;}
	if(element.FadeTimeLeft <= elapsedTicks){
    	element.style.opacity = element.FadeState == 1 ? '1' : '0';
    	element.style.filter = 'alpha(opacity = ' + (element.FadeState == 1 ? '100' : '0') + ')';
    	element.FadeState = element.FadeState == 1 ? 2 : -2;
    	if(undefined != remove && remove==1){removeDiv(eid);}
    	return;
  		}
  	element.FadeTimeLeft -= elapsedTicks;
  	var newOpVal = element.FadeTimeLeft/TimeToFade;
  	if(element.FadeState == 1){newOpVal = 1 - newOpVal;}
	element.style.opacity = newOpVal;
	element.style.filter = 'alpha(opacity = ' + (newOpVal*100) + ')';
	setTimeout("animateFade(" + curTick + ",'" + eid + "','"+remove+"')", 33);
	}
var animateTimer='';
function animateGrow(divid, begin,end){
	clearTimeout(animateTimer);
	var element=getObject(divid);
	if(undefined == element){return;}
	var grow=element.getAttribute('animateGrow');
	if(grow==0){return;}
	if(begin > end){
		element.style.height = end+'px';
		element.setAttribute('animateGrow',0);
		return;
		}
	var h=begin+10;
	element.style.height = h+'px';
	var tstr="animateGrow('" + divid + "'," + h + ","+end+")";
	animateTimer = setTimeout(tstr, 5);
	}
function animateShrink(divid, begin,end){
	clearTimeout(animateTimer);
	var element=getObject(divid);
	if(undefined == element){return;}
	if(begin < end){
		element.style.height = end+'px';
		element.setAttribute('animateGrow',1);
		return;
		}
	var h=begin-10;
	element.style.height = h+'px';
	var tstr="animateShrink('" + divid + "'," + h + ","+end+")";
	animateTimer = setTimeout(tstr, 5);
	}
//Replace text in a
function replaceText(obj,s,r,i){
	var cObj=getObject(obj);
	if(undefined == obj){return abort("undefined object passed to replaceText");}
	var opt = 'ig';
	if(i){opt = 'g';}
	var regexp = new RegExp(s,opt);
	var txt=getText(obj);
	var newval = txt.replace(regexp,r);
	setText(obj,newval);
	}
//resizeIframe
function resizeIframe(id){
	var fobj=getObject(id);
	if(undefined == fobj){
		window.status='pageframe not found. resize failed';
		return false;

		}
	var h=getBrowserHeight();
	fobj.style.height=h+'px';
	return 1;
    }
//setText - returns object text
function setText(obj,txt){
	//info: sets the text of specified object or id to txt
	//usage: setText('div2','test');
	var cObj=getObject(obj);
    if(undefined == cObj){return null;}
    //alert(cObj+'\n'+txt);
    var previous_value=getText(cObj);
	if(undefined != cObj.innerHTML){cObj.innerHTML=txt;}
    else if(undefined != cObj.innerText){cObj.innerText=txt;}
    else if(undefined != cObj.value){cObj.value=txt;}
    else{
		return null;
    	}
    //check for onchange attribute and kick it off if the value changes
    if(undefined != cObj.onchange && previous_value != txt){
		cObj.onchange();
	}
}
//setText - returns object text
function setStyle(obj,s,v){
	//info: sets the specified style of object
	//usage: setStyle('tdiv','display','hidden');
	var cObj=getObject(obj);
    if(undefined == cObj){return false;}
    //if(v.length==0){return;}
    if(s.length==0){return;}
    var str="cObj.style."+s+"='"+v+"';";
    //alert(str);
    eval(str);
	}
//setClassName - returns object text
function setClassName(obj,v){
	//info: sets the classname of specified object
	//usage: setClassName('tdiv','current');
	var cObj=getObject(obj);
    if(undefined == cObj){return false;}
    var current=cObj.className;
    if(!v){return;}
    if(v.length==0){return;}
    if(current==v){return;}

    var str="cObj.className='"+v+"';";
    eval(str);
	}
function showContextId(id){
	showId(id,-5,-30);
	hideOnExit(id);
	return false;
	}
function showId(id,xoff,yoff){
    if(undefined == document.getElementById(id)){
		alert('Error in showId\n"'+id+'" is not defined as a valid object');
		return;
    	}
	var formObj=document.getElementById(id);
	formObj.style.position='absolute';
	if(undefined != xoff){
		var x=MouseX;
		x=Math.round(x+xoff);
		formObj.style.left=x+"px";
		}
	if(undefined != yoff){
		var y=MouseY;
		y=Math.round(y+yoff);
		formObj.style.top=y+"px";
		}
    formObj.style.display='inline';
    //
	//alert(formObj.style.display);
    return true;
    }
function showDropDown(id){
	var cObj=getObject(id);
    if(undefined == cObj){return abort("undefined object passed to showIdBlock:"+id);}
    if(cObj.style.display=='block'){
		cObj.style.display='none';
		return false;
		}
    cObj.style.display='block';
    hideOnExit(cObj);
    return false;
	}
function hideId(id){
	//info: hides specified object or id (does not destroy it)
	//usage: hideId('tdiv');
	var cObj=getObject(id);
    if(undefined == cObj){return abort("undefined object passed to showHide:"+id);}
    cObj.style.display='none';
	}
/* showDrop */
function showDrop(oid,h){
	var navEls = GetElementsByAttribute('div','id',oid);
	for (var i=0; i<navEls.length; i++) {
          if(undefined != h){
			if(h==1){navEls[i].style.display='none';}
			}
          else if(navEls[i].style.display=='block'){navEls[i].style.display='none';}
          else{navEls[i].style.display='block';}
	     }
     return false;
	}
/* showHide */
function showHide(id,scr){
	var cObj=getObject(id);
    if(undefined == cObj){return abort("undefined object passed to showHide:"+id);}
    if(cObj.style.display=='none'){
		cObj.style.display='block';
		}
    else{cObj.style.display='none';}
    if(undefined != scr && scr==1){showOnScreen(id);}
	}
function showHideMobile(divid){
	var s=document.getElementById(divid).style;
	if(s.display=='block'){s.display='none';}
	else{s.display='block';}
	}

// showProperties - shows the properties of any element
function showProperties(obj,id,v){
	var cObj=getObject(obj);
    if(undefined == cObj){return abort("undefined object passed to showProperties");}
	var str="Properties that have values for :" + cObj + "\n";
	var namestr='';
	for(var cname in cObj){
		if(cObj[cname]){
			//typeof returns "number" "string" "boolean" "function" "undefined" "object"
			var ctype=typeof(cObj[cname]);
			if(v || (ctype != "object" && ctype != "function")){
				var val=cObj[cname];
				str += "[" + cname + "]["+ctype+"]";
				if(v || ctype || val.length < 20 == 'number'){str += ' = '+ val;}
				if(!id){str += "\r\n";}
				else{str += "<br>\r\n";}
				}

			}
		namestr += cname + ", ";
		}
	if(!id){
		alert(str);
		}
	else{
		setText(id,str);
    	}
  	}
function str_replace(search, replace, str) { 
    var f = search, r = replace, s = str;
    var ra = r instanceof Array, sa = s instanceof Array, f = [].concat(f), r = [].concat(r), i = (s = [].concat(s)).length;
 
    while (j = 0, i--) {
        if (s[i]) {
            while (s[i] = s[i].split(f[j]).join(ra ? r[j] || "" : r[0]), ++j in f){};
        }
    };
 
    return sa ? s : s[0];
}
//trim - remove beginning and ending spaces, tabs, and line returns
function trim(str){
	//info: remove beginning and ending spaces, tabs, and line returns
	if (null != str && undefined != str && "" != str){
		var rval=str.replace(/^[\ \s\0\r\n\t]*/g,"");
		rval=rval.replace(/[\ \s\0\r\n\t]*$/g,"");
	    return rval;
		}
	else{return "";}
	}
// urlEncode
function urlEncode(str) {
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
	}
function getTableRowValues(tr,s) {
	//info: getTableRowValues returns a key/value array using the first row in the table as the keys.
	//info: if s=1, then returns as a URL string instead of an array
	//usage: alert(getTableRowValues(this,1));
	if(undefined == s){s=0;}
	ptable=tr.parentNode;
    var keys=new Array();
    var vals=new Array();
    for (var i = 0, row; row = ptable.rows[i]; i ++) {
	//iterate through rows
   		//rows would be accessed using the "row" variable assigned in the for loop
   		for (var j = 0, col; col = row.cells[j]; j ++) {
    		//iterate through columns
     		//columns would be accessed using the "col" variable assigned in the for loop
     		var cval=getText(col);
     		if(i==0){keys.push(cval);}
     		if(row==tr){vals.push(cval);}
   			}
   		}
	var str='';
	var row=new Array();
	for(var i=0;i<keys.length;i++){
		var key=strtolower(keys[i]);
		row[key]=vals[i];
		str+=key+'='+vals[i]+'&';
		}
	if(s==1){return str;}
	return row;
	}
function strtolower (str) {
    // info: Makes a string lowercase
    //source: http://phpjs.org/functions
    return (str + '').toLowerCase();
	}
function strtoupper (str) {
    // info: Makes a string uppercase
    //source: http://phpjs.org/functions
    return (str + '').toUpperCase();
	}
function ucfirst (str) {
    //info: Makes a string's first character uppercase
    //source: http://phpjs.org/functions
    var f = str.charAt(0).toUpperCase();
    return f + str.substr(1);
	}
function ucwords (str) {
    //info: Uppercase the first character of every word in a string
    //source: http://phpjs.org/functions
    return (str + '').replace(/^([a-z])|\s+([a-z])/g, function ($1) {
        return $1.toUpperCase();
    });
	}
function array_values (arr) {
    //info: Return just the values from an array
    //source: http://phpjs.org/functions
    var tmp_arr = [],
        cnt = 0;    var key = '';
 
    for (key in arr) {
        tmp_arr[cnt] = arr[key];
        cnt++;    }
 
    return tmp_arr;
	}
function array_keys (arr, search_value, argStrict) {
    //info: Return just the keys from an array, optionally only for the specified search_value
    //source: http://phpjs.org/functions
    var searchstr = typeof search_value !== 'undefined',
        tmp_arr = [],
        strict = !!argStrict,        include = true,
        key = '';
	//walk the array
    for (key in arr) {
        if (arr.hasOwnProperty(key)) {            
			include = true;
            if (searchstr) {
                if (strict && arr[key] !== search_value) {include = false;}                
				else if (arr[key] != search_value) {include = false;}
            	}
             if (include) {tmp_arr[tmp_arr.length] = key;}
        	}
    	}
    return tmp_arr;
	}
function in_array (needle, haystack, argStrict) {
    //info: Checks if the given value exists in the array
    //usage: var tf=in_array('van', ['Kevin', 'van', 'Zonneveld']); - returns true
    //usage: var tf=in_array('vlado', {0: 'Kevin', vlado: 'van', 1: 'Zonneveld'}); - returns false
    //usage: var tf=in_array(1, ['1', '2', '3'], true); - returns false since argStrict is used and there is a type mismatch
	//source: http://phpjs.org/functions
    var key = '', strict = !! argStrict;
    if (strict) {
        for (key in haystack) {
            if (haystack[key] === needle) {return true;}
        	}
    	} 
	else {
        for (key in haystack) {            
			if (haystack[key] == needle) {return true;}
        	}
    	}
    return false;
	}
function array_walk (array, funcname, userdata) {
    //usage: Apply a user function to every member of an array
    //usage: var tf=array_walk ({'a':'b'}, 'void', 'userdata');
    //source: http://phpjs.org/functions
    if (typeof array !== 'object' || array === null) {
        return false;
    	}
    for (key in array) {
        if (typeof(userdata) !== 'undefined') {
            eval(funcname + '( array [key] , key , userdata  )');
        	} 
		else{
			eval(funcname + '(  userdata ) ');
        	}
    	}
    return true;
	}
function nl2br (str, is_xhtml) {
    //info: Converts newlines to HTML line breaks
    //source: http://phpjs.org/functions
	var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '' : '<br>';
    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
	}
function implode (glue, pieces) {
    //info: Joins array elements placing glue string between items and return one string
    //source: http://phpjs.org/functions
    var i = '', retVal = '', tGlue = '';
    if (arguments.length === 1) {
        pieces = glue;
        glue = '';
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
	}