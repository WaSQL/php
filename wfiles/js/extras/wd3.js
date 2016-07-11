/*
	d3.js addon functions for d3 at https://d3js.org/d3.v3.min.js
	wd3PieChart for pie and donut charts - label,value
	wd3LineChart for line and multiple lines chart - label,value1,value2,value3,.... each value is a new line
	wd3BarChart for bar chart - label,value1,value2,value3,... each value is a new bar

*/
//---------- begin function wd3LineChart--------------------
/**
* @describe creates[or updates] a line chart using d3
* @param p selector string - specifies the parent element to append the chart to
* @param params json
*	data - data
*	csv - url to csv data to load
*	tsv - url to tsv data to load
*	json - url to json data to load
*	label - defines the column to use as the x value
*	[width] - specifies width. If not specified, parent width is used
*	[height] - specifies height. If not specified, parent height is used
*	[padding] - defaults to 1
*	[duration] - defaults to 1000 milliseconds
* 	[onclick] - set to function name to call onclick. Passes in this, label,value,percent
*	[debug] - writes console.log messages for debugging purposes
* @return false
*/
function wd3LineChart(p,params){
	if(undefined == d3){
		alert('d3 library is not loaded');
		return false;
	}
	if(undefined==p){p='body';}
	var pObj=document.querySelector(p);
	if(undefined == pObj){
		console.log('wd3PieChart Error: undefined parent');
		return;
	}
	if(undefined==params){params={};}
	if(undefined==params.width){params.width=getWidth(pObj);}
	if(undefined==params.height){params.height=getHeight(pObj);}
	if(undefined==params.padding){params.padding=1;}
	if(undefined==params.label){params.label='label';}
	if(undefined==params.duration){params.padding=1000;}
	if(undefined!=params.debug){console.log(params);}
	//do not allow zero height or width
	if(params.height < 20){params.height=300;}
	if(params.width < 20){params.width=300;}
	var color = d3.scale.category20();
	//check to see if it already exists
	if(undefined == document.querySelector(p+' svg')){
		if(undefined!=params.debug){console.log('new chart - adding svg');}
		//title - place on bottom if pie, middle if donut
		d3.select(p).append("div")
	      		.attr("class", "title text-center")
	      		.text("");
		// draw and append the container
		var svg = d3.select(p).append("svg")
			.attr("width", params.width)
			.attr("height", params.height)
			.append("g");

		//add containers for slices, labels and lines
		svg.append("g")
			.attr("class", "slices");
		svg.append("g")
			.attr("class", "labels");
		svg.append("g")
			.attr("class", "lines");

		//set transform for svg
		svg.attr("transform", "translate(" + params.width / 2 + "," + params.height / 2 + ")");

	}
	else{
		if(undefined!=params.debug){console.log('existing chart - updating');}
		var svg = d3.select(p+' svg');
	}
	//scale x and y
	var	x = d3.scale.linear().range([0, params.width]);
	var	y = d3.scale.linear().range([params.height, 0]);
	//x axis ticks and optional format
	var	xAxis = d3.svg.axis().scale(x)
		.orient("bottom").ticks(params.xticks);
	if(undefined != xformat){
    	xAxis.tickFormat(d3.format(xformat));
	}
	//y axis ticks and optional format
	var	yAxis = d3.svg.axis().scale(y)
		.orient("left").ticks(params.yticks);
	if(undefined != yformat){
    	yAxis.tickFormat(d3.format(yformat));
	}

	var valueline=new Array();
	for(var z=0;z<10;z++){
		valueline[z] = d3.svg.line()
			.interpolate("basis")
			.x(function(d) { return x(d.xval); })
			.y(function(d,z) {var ykey=ykeys[z]; return y(d[ykey]); });
	}

	function loadline(data){
		var keys = Object.keys(data[0]);
		ykeys=new Array();
		for(i=0;i<keys.length;i++){
        	var ckey=keys[i];
        	if(ckey!=params.label){ykeys.push(ckey);}
		}
		data.forEach(function(d) {
			d.xval 	= +d[params.label];
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
			svg.append("path")
				.attr("class", "line")
				.style("stroke", colors.range(z))
				.attr("d", valueline[z](data));
		}
		// Add the X Axis
		svg.append("g")
			.attr("class", "x-axis kpi")
			.attr("transform", "translate(0," + height + ")")
			.call(xAxis);
		// Add the Y Axis
		svg.append("g")
			.attr("class", "y-axis kpi")
			.call(yAxis);

	 	// Add the legends
	 	for(z=0;z<ykeys.length;z++){
			svg.append("text")
				.attr("x", 50*z)
				.attr("y", height + margin.top+5)
				.attr("class", "legend")
				.style("fill", colors.range(z))
				.text(ykeys[z]);
		}
	}
	//pass in the data
	if(undefined != params.csv){
		if(undefined!=params.debug){console.log('loading csv');}
		d3.csv(params.csv, loadline);
	}
	else if(undefined != params.tsv){
		if(undefined!=params.debug){console.log('loading tsv');}
		d3.tsv(params.tsv, loadline);
	}
	else if(undefined != params.json){
		if(undefined!=params.debug){console.log('loading json');}
		d3.json(params.json, loadline);
	}
	else if(undefined != params.data){
		if(undefined!=params.debug){console.log('loading data');}
		loadline(params.data);
	}
	else{
		if(undefined!=params.debug){console.log('loading random data');}
    	loadline(wd3RandomLineData());
	}
	return false;
}

//---------- begin function wd3PieChart--------------------
/**
* @describe creates[or updates] a pie chart using d3
* @param p selector string - specifies the parent element to append the chart to
* @param params json
*	data - data
*	csv - url to csv data to load
*	tsv - url to tsv data to load
*	json - url to json data to load
*	[width] - specifies width. If not specified, parent width is used
*	[height] - specifies height. If not specified, parent height is used
*	[type] - set to donut for donut chart. Defaults to pie chart
*	[donut] - donut hole percent. defaults to .4
*	[padding] - defaults to 60. Need padding for labels
* 	[onclick] - set to function name to call onclick. Passes in this, label,value,percent
*	[debug] - writes console.log messages for debugging purposes
* @return false
*/
function wd3PieChart(p,params){
	if(undefined == d3){
		alert('d3 library is not loaded');
		return false;
	}
	if(undefined==p){p='body';}
	var pObj=document.querySelector(p);
	if(undefined == pObj){
		console.log('wd3PieChart Error: undefined parent');
		return;
	}
	if(undefined==params){params={};}
	if(undefined==params.width){params.width=getWidth(pObj);}
	if(undefined==params.height){params.height=getHeight(pObj);}
	if(undefined==params.padding){params.padding=60;}
	if(undefined!=params.debug){console.log(params);}
	//do not allow zero height or width
	if(params.height < 20){params.height=300;}
	if(params.width < 20){params.width=300;}
	var color = d3.scale.category20();
	//check to see if it already exists
	if(undefined == document.querySelector(p+' svg')){
		if(undefined!=params.debug){console.log('new chart - adding svg');}
		// draw and append the container
		var svg = d3.select(p).append("svg")
			.attr("width", params.width)
			.attr("height", params.height)
			.append("g");
		//title - place on bottom if pie, middle if donut
		if(undefined != params.type && params.type=='donut'){
			svg.append("text")
	      		.attr("class", "title")
	      		.attr("text-anchor", "middle")
	      		.attr("transform","translate(0,4)")
	      		.text("");
		}
		else{
			d3.select(p).append("div")
	      		.attr("class", "title text-center")
	      		.text("");
		}
		//add containers for slices, labels and lines
		svg.append("g")
			.attr("class", "slices");
		svg.append("g")
			.attr("class", "labels");
		svg.append("g")
			.attr("class", "lines");

		//set transform for svg
		svg.attr("transform", "translate(" + params.width / 2 + "," + params.height / 2 + ")");

	}
	else{
		if(undefined!=params.debug){console.log('existing chart - updating');}
		var svg = d3.select(p+' svg');
	}
	//calculate radius

	var radius = Math.round(Math.min(params.width, params.height) / 2)-params.padding;
	//define arc for chart
	if(undefined != params.type && params.type=='donut'){
		if(undefined == params.donut){params.donut=.4;}
		var arc = d3.svg.arc()
			.outerRadius(radius * 0.8)
			.innerRadius(radius * params.donut);
	}
	else{
    	var arc = d3.svg.arc()
			.outerRadius(radius * 0.85)
			.innerRadius(0);
	}
	//construct arc for labels
	var outerArc = d3.svg.arc()
    	.innerRadius(radius * 0.9)
		.outerRadius(radius * 0.9);

	//create key function since it will be used several places
	var key = function(d){ return d.data.label; };
	// construct default pie laoyut
	var pie = d3.layout.pie().value(function(d){ return d.value; }).sort(null);
	//build the function to load the data and draw the chart
	function loadpie(data){
		//convert value to numeric
		data.forEach(function(d) {
    		d.value = +d.value;
		});
		//get total to calculate percent
		var totals = d3.sum(data, function(d) {
            return d.value;
        });
        //calculate percent
        data.forEach(function(d) {
    		d.percent = Math.round((d.value/totals)*100,1);
		});
		//if(undefined!=params.debug){console.log(data);}
		/* ------- PIE SLICES -------*/
		var slice = svg.select(".slices").selectAll("path.slice")
			.data(pie(data), key);

		slice.enter()
			.insert("path")
			.style("fill", function(d,i) {
				if(undefined!=params.debug){console.log('new slice:i='+i+',color='+color.range()[i]+', label='+d.data.label);}
				return color.range()[i];
			})
			.attr("class", "slice");
		slice.attr("data-percent", function(d) { return d.data.percent; });
		slice.attr("data-value", function(d) { return d.data.value; });
		slice.attr("data-label", function(d) { return d.data.label; });
		//darken the slice on hover (mouseover) and restore the original color on mouseout
		slice.on("mouseover", function() {
				var fill=d3.select(this).style("fill");
				d3.select(this).attr("fill_ori",fill);
				d3.select(this).style("fill", d3.rgb(fill).darker(0.3));
				d3.select(p+' .title').text(d3.select(this).attr("data-percent")+'%');
				});
		slice.on("mouseout", function() {
				var fill=d3.select(this).attr("fill_ori");
  				d3.select(this).style("fill", fill);
				d3.select(p+' .title').text("");
				});
		//onclick?
		if(undefined != params.onclick){
			slice.on("click", function() {
				var args=new Array();
				args.push(d3.select(this).attr("data-label"));
				args.push(d3.select(this).attr("data-value"));
				args.push(d3.select(this).attr("data-percent"));
				window[params.onclick].apply(this,args);
			});
		}
		//transition
		slice.transition().duration(1000)
			.attrTween("d", function(d) {
				this._current = this._current || d;
				var interpolate = d3.interpolate(this._current, d);
				this._current = interpolate(0);
				return function(t) {
					return arc(interpolate(t));
				};
			});
		//exit point
		slice.exit().remove();
	
		/* ------- TEXT LABELS -------*/
		var text = svg.select(".labels").selectAll("text")
			.data(pie(data), key);
		text.enter()
			.append("text")
			.attr("dy", ".35em")
			.text(function(d) {
				return d.data.label;
			});
		function midAngle(d){
			return d.startAngle + (d.endAngle - d.startAngle)/2;
		}
	
		text.transition().duration(1000)
			.attrTween("transform", function(d) {
				this._current = this._current || d;
				var interpolate = d3.interpolate(this._current, d);
				this._current = interpolate(0);
				return function(t) {
					var d2 = interpolate(t);
					var pos = outerArc.centroid(d2);
					if(midAngle(d2) < Math.PI){pos[0]=radius-12;}
					else{pos[0]=(radius*-1)+12;}
					//pos[0] = radius * (midAngle(d2) < Math.PI ? 1 : -1)-10;
					return "translate("+ pos +")";
				};
			})
			.styleTween("text-anchor", function(d){
				this._current = this._current || d;
				var interpolate = d3.interpolate(this._current, d);
				this._current = interpolate(0);
				return function(t) {
					var d2 = interpolate(t);
					return midAngle(d2) < Math.PI ? "start":"end";
				};
			});
	
		text.exit().remove();
	
		/* ------- SLICE TO TEXT POLYLINES -------*/
	
		var polyline = svg.select(".lines").selectAll("polyline")
			.data(pie(data), key);
		
		polyline.enter()
			.append("polyline");

		polyline.transition().duration(1000)
			.attrTween("points", function(d){
				this._current = this._current || d;
				var interpolate = d3.interpolate(this._current, d);
				this._current = interpolate(0);
				return function(t) {
					var d2 = interpolate(t);
					var pos = outerArc.centroid(d2);
					pos[0] = radius * 0.91 * (midAngle(d2) < Math.PI ? 1 : -1);
					return [arc.centroid(d2), outerArc.centroid(d2), pos];
				};			
			});

		polyline.exit().remove();
	}
	//pass in the data
	if(undefined != params.csv){
		if(undefined!=params.debug){console.log('loading csv');}
		d3.csv(params.csv, loadpie);
	}
	else if(undefined != params.tsv){
		if(undefined!=params.debug){console.log('loading tsv');}
		d3.tsv(params.tsv, loadpie);
	}
	else if(undefined != params.json){
		if(undefined!=params.debug){console.log('loading json');}
		d3.json(params.json, loadpie);
	}
	else if(undefined != params.data){
		if(undefined!=params.debug){console.log('loading data');}
		loadpie(params.data);
	}
	else{
		if(undefined!=params.debug){console.log('loading random data');}
    	loadpie(wd3RandomPieData());
	}
	return false;
}
//---------- begin function wd3RandomPieData--------------------
/**
* @describe generates random pie chart data to be used in testing
* @return json  label,value
*/
function wd3RandomPieData(){
	var x = d3.scale.ordinal()
		.domain(["Lorem ipsum", "dolor sit", "amet", "consectetur", "adipisicing", "eiusmod", "tempor", "incididunt"]);
	var labels = x.domain();
	return labels.map(function(label){
		return { label: label, value: Math.random() }
	});
}
