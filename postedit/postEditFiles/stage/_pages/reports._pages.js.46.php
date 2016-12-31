function reportMenu(obj){
	var report_id=obj.getAttribute('data-id');
	var title=obj.getAttribute('title');
	if(title.toLowerCase()=='remove'){
		var tab_id=document.getElementById('reportmanagelink').getAttribute('data-tab_id');
		reportRemoveReport(report_id,tab_id);
    	return false;
	}
	//show all but this one
	d3.selectAll("#reportMenu_"+report_id+" a.toggle").style("display","inline");
	obj.style.display='none';
	d3.selectAll("#report_"+report_id+" .reportData .data").style("display","none");
	d3.selectAll("#report_"+report_id+" .reportData .data."+title).style("display","block");
	return false;
}
function reportInitTabSort(){
	var tabs = document.querySelector("#usertabs");
	//console.log('reportInitTabSort',tabs);
	var tabsort = Sortable.create(tabs, {
	  animation: 150, // ms, animation speed moving items when sorting, `0` - without animation
	  //handle: ".tile__title", // Restricts sort start click/touch to the specified element
	  draggable: ".navtab", // Specifies which items inside the element should be sortable
	  onUpdate: function (evt/**Event*/){
	     var item = evt.item; // the current dragged HTMLElement
	     reportUpdateTabSort(item.getAttribute('data-tab_id'));
	  }
	});
}
function reportTabFormAction(func){
	hideId('navoptions');
	document.tabform.func.value=func;
	switch(func.toLowerCase()){
		case 'clear':
			reportClearTabForm();
    	case 'add':
    		document.querySelector("#tabformsubmiticon").className='icon-save';
    	break;
    	case 'edit':
    		document.querySelector("#tabformsubmiticon").className='icon-edit w_grey';
    	break;
    	case 'delete':
    		document.querySelector("#tabformsubmiticon").className='icon-cancel w_danger';
    	break;
	}
	ajaxSubmitForm(document.tabform,'usertabs');
	document.querySelector("#tabformsubmiticon").className='icon-save';
	document.tabform.func.value='add';
}
function reportAddReportToTab(report_id){
	var tab_id=document.getElementById('reportmanagelink').getAttribute('data-tab_id');
    return ajaxGet('/t/1/reports/addreport','content',{tab_id:tab_id,report_id:report_id,nosetprocess:1});
}
function reportUpdateTabSort(i){
	var obj = document.querySelectorAll("#usertabs li.navtab");
	var opts={};
	var tabsort=new Array();
	for(var i=0;i<obj.length;i++){
		var t=obj[i].getAttribute('data-tab_id');
		tabsort.push(t);
	}
	//console.log('reportUpdateTabSort',i,tabsort);{tab_id:tab_id,report_id:report_id,nosetprocess:1}
	tabsort=implode(':',tabsort);
	return ajaxGet('/t/1/reports/nav/sort','nulldiv',{tabsort:tabsort});
}

function reportInitTileSort(){
	var tiles = document.querySelector("#tiles");
	//console.log('reportInitTileSort',tiles);
	var tilesort = Sortable.create(tiles, {
	  animation: 150, // ms, animation speed moving items when sorting, `0` - without animation
	  //handle: ".tile__title", // Restricts sort start click/touch to the specified element
	  draggable: ".tile", // Specifies which items inside the element should be sortable
	  onUpdate: function (evt/**Event*/){
	     var item = evt.item; // the current dragged HTMLElement
	     reportUpdateTileSort(item.getAttribute('data-report_id'));
	  }
	});
}
function reportUpdateTileSort(tid){
	var obj = document.querySelectorAll("#tiles div.tile");
	var opts={};
	var tilesort=new Array();
	var c=0;
	for(var i=0;i<obj.length;i++){
		var t=obj[i].getAttribute('data-report_id');
		if(t==tid){c=i;}
		tilesort.push(t);
	}
	var params={tilesort:tilesort,tab_id:obj[c].getAttribute('data-tab_id'),report_id:obj[c].getAttribute('data-report_id'),nosetprocess:1};
	tilesort=implode(':',tilesort);
	console.log('reportUpdateTileSort',params);
	return ajaxGet('/t/1/reports/sortreport','nulldiv',params);
}


function reportLoadChart(cid){
	var chart=d3.select('div #report_'+cid).attr('data-chart');
	switch(chart.toLowerCase()){
    	case 'line':reportLoadLineChart(cid);break;
    	default:reportLoadLineChart(cid);break;
	}
	//check to see if this chart has a back that needs to be updated
	var obj=document.querySelector('div #report_'+cid+' #reportback_'+cid);
	if(undefined != obj){
		var url="/t/1/reports/data/"+cid;
		var databack=d3.select('div #report_'+cid).attr('data-back');
    	ajaxGet(url,'reportback_'+cid,{databack:databack});
	}
}
function reportUpdateChart(cid,url){
	var chart=d3.select('div #report_'+cid).attr('data-chart');
	switch(chart.toLowerCase()){
    	case 'line':reportUpdateLineChart(cid,url);break;
    	case 'bar':wd3BarChart('#report_'+cid+' #chart',{csv:url});break;
    	case 'barline':wd3BarChart('#report_'+cid+' #chart',{csv:url,rightvalue:'aos',right:65,left:70,rightformat:'$.2f'});break;
    	case 'map':wd3MapChart('#report_'+cid+' #chart',{csv:url,height:300});break;
    	case 'pie':wd3PieChart('#report_'+cid+' #chart',{csv:url});break;
    	case 'donut':wd3PieChart('#report_'+cid+' #chart',{csv:url,type:'donut',legend:1,labels:1,padding:10,height:275});break;
    	case 'custom':
    		var xobj = new XMLHttpRequest();
		    xobj.overrideMimeType("application/json");
		    xobj.open('GET', url, true);
		    xobj.cid='#report_'+cid+' #chart';
		    xobj.onreadystatechange = function () {
		          if (xobj.readyState == 4 && xobj.status == "200") {
		            d3.select(this.cid).html(xobj.responseText);
		          }
		    };
		    xobj.send(null);
		break;
    	default:reportUpdateLineChart(cid,url);break;
	}
	//check to see if this chart has a back that needs to be updated
	var obj=document.querySelector('div #report_'+cid+' #reportback_'+cid);
	if(undefined != obj){
		var databack=d3.select('div #report_'+cid).attr('data-back');
    	ajaxGet(url,'reportback_'+cid,{databack:databack});
	}
}
function reportChangeFilters(cid){
	var url=reportGetFilterUrl(cid);
	reportUpdateChart(cid,url);
	return false;
}


function reportPrevMonthYear(cid){
	var str=d3.select('#report_'+cid+' #report_monthyear').text();
	//console.log(str);
	str=str.replace(' ','/01/');
	//console.log(str);
	var x=Date.parse(str);
	//console.log(x);
	var d=new Date(x);
	//previous month
	if (d.getMonth() == 0) {
    	d = new Date(d.getFullYear() - 1, 11, 1);
	} else {
    	d = new Date(d.getFullYear(), d.getMonth() - 1, 1);
	}
	var year=d.getFullYear();
	var mon=reportMonthName(d.getMonth());
	//do not allow before 2008
	if(year < 2008){year=2008;}
	d3.selectAll('#report_'+cid+' #report_monthyear').text(mon+' '+year);
	reportChangeFilters(cid);
	return false;
}
function reportNextMonthYear(cid){
	var str=d3.select('#report_'+cid+' #report_monthyear').text();
	str=str.replace(' ','/01/');
	var x=Date.parse(str);
	var d=new Date(x);
	//next month
	if (d.getMonth() == 11) {
    	d = new Date(d.getFullYear() + 1, 0, 1);
	} else {
    	d = new Date(d.getFullYear(), d.getMonth() + 1, 1);
	}
	var year=d.getFullYear();
	var mon=reportMonthName(d.getMonth());
	//do not allow future years
	var c=new Date();
	if(year > c.getFullYear()){
		year=cyear;
	}
	if(year == c.getFullYear() && d.getMonth() > c.getMonth()){
		mon=reportMonthName(c.getMonth());
	}
	d3.selectAll('#report_'+cid+' #report_monthyear').text(mon+' '+year);
	reportChangeFilters(cid);
	return false;
}
function reportSetMonthYear(cid){
	var obj=document.querySelector('#report_'+cid+' #report_monthyear');
	var country=obj.options[obj.selectedIndex].value;
	if(!country.length){country='All Countries';}
	d3.selectAll('div #report_'+cid+' #countrytop').text(country);
	return false;
}
function reportMonthName(m){
	var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
	return monthNames[m];
}
function reportExportData(cid){
	var url=reportGetFilterUrl(cid);
	window.location=url;
	return false;
}
function reportGetFilterUrl(cid){
	var url='/t/1/reports/data/'+cid;
	//country?
	var obj=document.querySelector('div #report_'+cid+' #country_'+cid);
	if(undefined != obj){
		var country=obj.options[obj.selectedIndex].value;
		if(!country.length){country='ALL';}
		url=url+'/country/'+country;
		//update countrytop
		if(country=='ALL'){country='All Countries';}
		d3.selectAll('div #report_'+cid+' #countrytop').text(country);
	}
	//date?
	obj=document.querySelector('#report_'+cid+' #report_monthyear');
	if(undefined != obj){
		var str=d3.select('#report_'+cid+' #report_monthyear').text();
		if(undefined != str){
			str=str.replace(' ','+');
			url=url+'/date/'+str;
		}
	}
	return url;
}

function reportSetCountry(cid){
	return false;
	var obj=document.querySelector('div #report_'+cid+' #country_'+cid);
	var country=obj.options[obj.selectedIndex].value;
	if(!country.length || country=='ALL'){country='All Countries';}
	d3.selectAll('div #report_'+cid+' #countrytop').text(country);
	return false;
}

function reportManage(tab){
	var obj=document.querySelector('#reportmanage');
	if(undefined == obj){return false;}
	if(obj.style.display=='inline'){
		obj.style.display='none';
		return false;
	}
	var txt=getText(obj);
	if(txt.toLowerCase().indexOf('report btn') != -1){
    	obj.style.display='inline';
		return false;
	}
	return ajaxGet('/t/1/reports/reportmanage/'+tab,'reportmanage',{nosetprocess:1});
}
function reportFlip(id){
	var obj=getObject('reportflip_'+id);
	obj.classList.toggle('hover');
	return false;
}
function reportNavBar(id){
	d3.selectAll(".navtab").attr('class','navtab');
	var obj=getObject('nav_'+id);
	document.getElementById('reportmanagelink').setAttribute('data-tab_id',id);
	obj.parentNode.className='active navtab';
	document.tabform.tabid.value=id;
	document.tabform.tabname.value=obj.innerHTML;
	document.tabform.func.value='edit';
	document.querySelector("#tabformsubmiticon").className='icon-edit w_grey';
	document.querySelector("#navoptions .edit").style='display:inline';
	document.querySelector("#navoptions .delete").style='display:inline';

	return ajaxGet('/t/1/reports/nav/'+id,'content',{nosetprocess:1});
}
function reportClearTabForm(){
	document.tabform.tabid.value='';
	document.tabform.tabname.value='';
	document.tabform.tabname.focus();
	document.querySelector("#navoptions .edit").style='display:none';
	document.querySelector("#navoptions .delete").style='display:none';
}
function reportCancel(e) {
  if (e.preventDefault){e.preventDefault();} // required by FF + Safari
  e.dataTransfer.dropEffect = 'copy'; // tells the browser what drop effect is allowed here
  return false; // required by IE
}
function reportRemoveReport(report_id,tab_id){
	ajaxGet('/t/1/reports/delreport','content',{tab_id:tab_id,report_id:report_id,nosetprocess:1});
}
function reportPrevYear(cid){
	var year=parseInt(d3.select('div #report_'+cid+' #year').text());
	year=year-1;
	//console.log(year);
	d3.selectAllselect('div #report_'+cid+' #year').text(year);
	reportUpdateChart(cid,'/t/1/reports/data/'+cid+'/year/'+year);
	return false;
}
function reportNextYear(cid){
	var year=parseInt(d3.select('div #report_'+cid+' #year').text());
	year=year+1;
	//do not allow future years
	var cyear=new Date().getFullYear();
	if(year > cyear){year=cyear;}
	//console.log(year);
	d3.selectAllselect('div #report_'+cid+' #year').text(year);
	reportUpdateChart(cid,'/t/1/reports/data/'+cid+'/year/'+year);
	return false;
}
function reportNumberWithCommas(x) {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function reportLoadLineChart(cid) {
	//console.log('reportLoadLineChart:'+cid);
    var	margin = {top: 30, right: 55, bottom: 50, left: 45};
	var leftmargin=document.querySelector('div #report_'+cid).getAttribute('data-leftmargin');
	if(undefined != leftmargin && leftmargin.length){
    	margin.left=leftmargin;
	}
	var width = 435 - margin.left - margin.right;
	var height = 330 - margin.top - margin.bottom;

	var	x = d3.scale.linear().range([0, width]);
	var	y = d3.scale.linear().range([height, 0]);

	var xticks=d3.select('div #report_'+cid).attr('data-xticks');
	var	xAxis = d3.svg.axis().scale(x)
		.orient("bottom").ticks(xticks);

	var yformat=d3.select('div #report_'+cid).attr('data-yformat');
	var	yAxis = d3.svg.axis().scale(y)
		.orient("left").ticks(10);
	if(undefined != yformat){
    	yAxis.tickFormat(d3.format(yformat));
	}
	var valueline=new Array();
	valueline[0] = d3.svg.line()
		.interpolate("basis")
		.x(function(d) { return x(d.xval); })
		.y(function(d,i) {var ykey=ykeys[0]; return y(d[ykey]); });

	valueline[1] = d3.svg.line()
		.interpolate("basis")
		.x(function(d) { return x(d.xval); })
		.y(function(d,i) {var ykey=ykeys[1]; return y(d[ykey]); });

	valueline[2] = d3.svg.line()
		.interpolate("basis")
		.x(function(d) { return x(d.xval); })
		.y(function(d,i) {var ykey=ykeys[2]; return y(d[ykey]); });

	valueline[3] = d3.svg.line()
		.interpolate("basis")
		.x(function(d) { return x(d.xval); })
		.y(function(d,i) {var ykey=ykeys[3]; return y(d[ykey]); });

	valueline[4] = d3.svg.line()
		.interpolate("basis")
		.x(function(d) { return x(d.xval); })
		.y(function(d,i) {var ykey=ykeys[4]; return y(d[ykey]); });

	valueline[5] = d3.svg.line()
		.interpolate("basis")
		.x(function(d) { return x(d.xval); })
		.y(function(d,i) {var ykey=ykeys[5]; return y(d[ykey]); });

	var	svg = d3.select('div #report_'+cid+' #chart')
		.append("svg")
			.attr("width", width + margin.left + margin.right)
			.attr("height", height + margin.top + margin.bottom)
		.append("g")
			.attr("transform", "translate(" + margin.left + "," + margin.top + ")");
	var colors = d3.scale.category10();
	var ykeys=new Array();
	//var focus = svg.append("g").style("display", "none");
	// Get the data
	var xkey=d3.select('div #report_'+cid).attr('data-xkey');
	d3.csv("/t/1/reports/data/"+cid, function(error, data) {
		var keys = Object.keys(data[0]);
		ykeys=new Array();
		for(i=0;i<keys.length;i++){
        	var ckey=keys[i];
        	if(ckey!=xkey){ykeys.push(ckey);}
		}
		data.forEach(function(d) {
			d.xval 	= +d[xkey];
			for(i=0;i<ykeys.length;i++){
				var ykey=ykeys[i];
				d[ykey]=+d[ykey];
			}
		});
		// Scale the range of the data
		x.domain(d3.extent(data, function(d) { return d.xval; }));
		y.domain([
			d3.min(data, function(d) {
				var ydata=new Array();
				for(i=0;i<ykeys.length;i++){
					var ykey=ykeys[i];
					ydata.push(+d[ykey]);
				}
				return Math.min.apply(null,ydata); })+-1,
			d3.max(data, function(d) {
				var ydata=new Array();
				for(i=0;i<ykeys.length;i++){
					var ykey=ykeys[i];
					ydata.push(+d[ykey]);
				}
				return Math.max.apply(null,ydata);
				 })+1
		]);
		// Add the valueline for each ykey
		for(z=0;z<ykeys.length;z++){
			var r=i+1;
			svg.append("path")
				.attr("class", "line")
				.attr("id", "reportline"+z)
				.style("stroke", colors(z))
				.attr("d", valueline[z](data));
		}
		// Add the X Axis
		svg.append("g")
			.attr("class", "x-axis report")
			.attr("transform", "translate(0," + height + ")")
			.call(xAxis);
		// Add the Y Axis
		svg.append("g")
			.attr("class", "y-axis report")
			.call(yAxis);

	 	// Add the legends
	 	for(z=0;z<ykeys.length;z++){
			svg.append("text")
				.attr("x", 50*z)
				.attr("y", height + margin.top+5)
				.attr("class", "legend")
				.style("fill", colors(z))
				.text(ykeys[z]);
		}

	}).on("progress", function(event){
        		//update progress bar
        		if (d3.event.lengthComputable) {
          			var percentComplete = Math.round(d3.event.loaded * 100 / d3.event.total);
          			//d3.select('div #report_'+cid+' .progresspcnt').text(percentComplete);
       				}
    			});
	return;
}
