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
	if(undefined==p){p='body';}
	var pObj=document.querySelector(p);
	if(undefined == pObj){
		console.log('wd3LineChart Error: undefined parent');
		return;
	}
	if(undefined==params){params={};}
	if(undefined==params.top){params.top=20;}
	if(undefined==params.right){params.right=20;}
	if(undefined==params.bottom){params.bottom=30;}
	if(undefined==params.left){params.left=50;}
	if(undefined==params.width){params.width=getWidth(pObj)-params.left-params.right;}
	if(undefined==params.height){params.height=getHeight(pObj)-params.top-params.bottom;}
	if(undefined==params.padding){params.padding=1;}
	if(undefined==params.yticks){params.yticks=Math.round(params.height/30);}
	if(undefined==params.xticks){params.xticks=Math.round(params.width/30);}
	if(undefined==params.xtype){params.xtype='date';}
	if(undefined==params.label){params.label='label';}
	if(undefined==params.duration){params.padding=10;}
	//do not allow zero height or width
	if(params.height < 20){params.height=300;}
	if(params.width < 20){params.width=300;}
	if(undefined!=params.debug){console.log('Params:',params);}
	//load colors
	var color = d3.scale.category20();

    var yAxisLabelOffset = 40;

    var innerWidth  = params.width  - params.left - params.right;
    var innerHeight = params.height - params.top  - params.bottom;
    if(undefined!=params.debug){console.log('innerWidth:',innerWidth);}
    if(undefined!=params.debug){console.log('innerHeight:',innerHeight);}
	//console.log(p,innerWidth,innerHeight,params);
	if(undefined == document.querySelector(p+' svg')){
	    var svg = d3.select(p).append("svg")
	    	.attr("width", params.width)
	        .attr("height", params.height);
	    var g = svg.append("g")
        	.attr("transform", "translate(" + params.left + "," + params.top + ")")
			.attr("data-g","one");
		var legend = d3.select(p).append("div")
			.attr("class","legend")
			.style("margin-left",params.left+'px')
			.style("margin-right",params.right+'px');

      	var xAxisG = g.append("g")
        	.style("stroke",'grey')
			.style("fill","none")
			.style("stroke-width","1px")
			.attr("class","x-axis")
        	.attr("transform", "translate(0," + innerHeight + ")")

      	var yAxisG = g.append("g")
        	.attr("class", "y-axis")
			.style("stroke",'grey')
			.style("fill","none")
			.style("stroke-width","1px");
      	var yAxisLabel = yAxisG.append("text")
        	.style("text-anchor", "middle")
        	.attr("transform", "translate(-" + yAxisLabelOffset + "," + (innerHeight / 2) + ") rotate(-90)")
        	.attr("class", "label");
		}
	else{
			var svg = d3.select(p+' svg');
			var legend = d3.select(p+' div.legend');
			var g = d3.select(p+' svg g[data-g=one]');
			var path = d3.select(p+' svg g[data-g=one] path');
			var xAxisG = d3.select(p+' svg g.x-axis');
			var xAxisLabel = d3.select(p+' svg g.x-axis text');
			var yAxisG = d3.select(p+' svg g.y-axis');
			var yAxisLabel = d3.select(p+' svg g.y-axis text');

	}
	//x and y axis scale
	switch(params.xtype.toLowerCase()){
		case 'num':
			var xScale = d3.scale.linear().range([0, innerWidth]);
		break;
		default:
			var xScale = d3.time.scale().range([0, innerWidth]);
		break;
	}
	var yScale = d3.scale.linear().range([innerHeight, 0]);
	
	var xAxis = d3.svg.axis().scale(xScale).orient("bottom")
        .ticks(5)
        .outerTickSize(0);
    if(undefined != params.xformat){
    	xAxis.tickFormat(d3.format(params.xformat));
	}
    var yAxis = d3.svg.axis().scale(yScale).orient("left")
        .ticks(5)
        .outerTickSize(0);
    if(undefined != params.yformat){
    	yAxis.tickFormat(d3.format(params.yformat));
	}
  	//render function
	function loadline(data){
		var keys=Object.keys(data[0]);
		var ykeys=new Array();
		var ykeysClass={};
		for(var z=0;z<keys.length;z++){
			var ckey=keys[z];
			if(keys[z] != params.label){
				ykeys.push(keys[z]);
				ykeysClass[keys[z]]=keys[z].replace(' ','');
			}
		}
		data.forEach(function(d) {
			switch(params.xtype.toLowerCase()){
				case 'num':
					d[params.label]=+d[params.label];
				break;
				case 'date':
					d[params.label] = new Date(d[params.label]);
				break;
			}

			for(var z=0;z<ykeys.length;z++){
				var ykey=ykeys[z];
				d[ykey]=+d[ykey];
			}
		});
		if(undefined!=params.debug){
			console.log('data:',data);
			console.log('keys:',keys);
			console.log('ykeys:',ykeys);
			console.log('label:',params.label);
		}
		var lines={};
	  	for(var z=0;z<ykeys.length;z++){
			var ykey=ykeys[z];
    		lines[ykey] = d3.svg.line()
    			.interpolate("basis")
        		.x(function(d) { return xScale(d[params.label]); })
        		.y(function(d) { return yScale(+d[ykey]); });
	  	}
		if(undefined!=params.debug){console.log('lines: ',lines);}
		//extent gets the min and max from the data
		xScale.domain(d3.extent(data, function (d){ return d[params.label]; }));
        yScale.domain([
			d3.min(data, function(d) {
				var ydata=new Array();
				for(var z=0;z<ykeys.length;z++){
					var ykey=ykeys[z];
					ydata.push(+d[ykey]);
				}
				return Math.min.apply(null,ydata);
				})+-1,
			d3.max(data, function(d) {
				var ydata=new Array();
				for(var z=0;z<ykeys.length;z++){
					var ykey=ykeys[z];
					ydata.push(+d[ykey]);
				}
				return Math.max.apply(null,ydata);
				})+1
		]);

        xAxisG.call(xAxis);
        yAxisG.call(yAxis);
        //get all existing lines so we can remove the ones no longer in the data
        var plines=g.selectAll("path.line")[0];
        if(undefined!=params.debug){console.log('plines: ',plines);}
        legend.selectAll("span").remove();
        for(var z=0;z<ykeys.length;z++){
			var ykey=ykeys[z];
			//add legend
			var clegend=legend.append("span");
			clegend.append("span")
				.attr("class","icon-blank")
				.style("color",color.range()[z]);
			clegend.append("span")
					.text(' '+ykey+' ');
			var linegraph = g.selectAll("path.line."+ykeysClass[ykey]);
			if(undefined!=params.debug){console.log('linegraph: ',linegraph);}
			//remove this line from the plines array that we will remove below
			for(var b=0;b<plines.length;b++){
				if(undefined == plines[b].getAttribute('class')){continue;}
				var val=plines[b].getAttribute('class');
				if(undefined == val){continue;}
				var s=val.indexOf(ykeysClass[ykey]);
            	if(s != -1){plines.splice(b,1);}
			}
			//add lines that do not exist yet
			if(linegraph.empty()){
				linegraph = g.append("path")
					.attr("class", "line "+ykeysClass[ykey])
					.style("stroke",color.range()[z])
					.style("fill","none")
					.style("stroke-width","2px");
			}
			//transition the lines with the new data
 			linegraph
		        .transition()
		        .ease("linear")
		        .duration(500)
		        .style("stroke",color.range()[z])
		        .attr("d", lines[ykey](data));
		}
		if(undefined!=params.debug){console.log('remove plines: ',plines);}
		//remove lines no longer in the data
		for(var b=0;b<plines.length;b++){
        	plines[b].remove();
		}
		params={};
		lines={};
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

//---------- begin function wd3MapChart--------------------
/**
* @describe creates[or updates] a map chart using d3
* @param p selector string - specifies the parent element to append the chart to
* @param params json
*	data - data
*	csv - url to csv data to load
*	tsv - url to tsv data to load
*	json - url to json data to load
*	[width] - specifies width. If not specified, parent width is used
*	[height] - specifies height. If not specified, parent height is used
* 	[onclick] - set to function name to call onclick. Passes in this, label,value,percent
*	[debug] - writes console.log messages for debugging purposes
* @return false
*/
var wd3MapChartMaps=new Array();
function wd3MapChart(p,params){
	if(undefined == d3){
		alert('d3 library is not loaded');
		return false;
	}
	if(undefined==p){p='body';}
	var pObj=document.querySelector(p);
	if(undefined == pObj){
		console.log('wd3MapChart Error: undefined parent');
		return;
	}
	if(undefined==params){params={};}
	if(undefined==params.width){params.width=getWidth(pObj);}
	if(undefined==params.height){params.height=getHeight(pObj);}
	if(undefined!=params.debug){console.log(params);}
	//do not allow zero height or width
	if(params.height < 20){params.height=250;}
	if(params.width < 20){params.width=350;}
	var fills={
			"1st": "#3399ff",
			"2nd": "#805393",
			"3rd": "#9c71ae",
			"4th": "#ae8cbd",
			"5th": "#c8afd2",
			"6th": "#d9c8e0",
			"defaultFill": "#e5e5e5"
		};
	if(undefined == document.querySelector(p+' .datamap')){
		var chartParams = {
			"width":    params.width,
			"height":    params.height,
			"scope": "usa",
			"class": "haha",
			"fills": fills,
			"element":document.querySelector(p),
	    	data: {},
	    	"legend": false,
			"labels": true,
			"id": "map",
			"geographyConfig": {

	 			"popupTemplate":  function(geography, data){
	    			return '<div class=hoverinfo><strong>' + geography.properties.name +': ' + wd3NumberWithCommas(data.value) + '</strong></div>';
	  			}
			}
		}
	  	wd3MapChartMaps[p] = new Datamap(chartParams);
	  	//add legend
	  	if(chartParams.legend){wd3MapChartMaps[p].legend();}
	  	//add labels
	  	if(chartParams.labels){wd3MapChartMaps[p].labels();}
	  	if(undefined != params.onclick){
			d3.selectAll(p+' .datamaps-subunit').on('click', function(geography) {
				if(undefined == window[params.onclick]){
                	console.log('wd3MapChart onclick error: "'+params.onclick+'" is not a valid function');
                	return false;
				}
				var args=new Array();
				args.push(d3.select(this).attr("data-label"));
				args.push(d3.select(this).attr("data-value"));
				window[params.onclick].apply(this,args);
			});
		}
	  	//add legend
	  	d3.select(p).append("div").attr("class", "legend").text("");
	    d3.select(p+' .legend').append("span").text('Most');
	  	for (var key in fills) {
  			d3.select(p+' .legend').append("span")
	      		.attr("class", "icon-blank")
	      		.attr("style", "color:"+fills[key]);
		}
		d3.select(p+' .legend').append("span").text('Least');
 	}
	//pass in the data
	if(undefined != params.csv){
		if(undefined!=params.debug){console.log('loading csv');}
		d3.csv(params.csv, function(error, data) {
			var cdata = data;
			for (var i=0;i<data.length;i++){
                cdata[ cdata[i].id] = cdata[i] ;
                delete  cdata[i].id;
                delete  cdata[i] ;
            }
			wd3MapChartMaps[p].updateChoropleth(cdata);
			//add data-id and data-value to path attribute so we can access it via this
			if(undefined != params.onclick){
				for (var key in cdata) {
					d3.select(p+' .datamaps-subunit.'+key)
						.attr("data-value",+cdata[key].value)
						.attr("data-label",+key);
				}
			}
		});
	}
	else if(undefined != params.tsv){
		if(undefined!=params.debug){console.log('loading tsv');}
		d3.tsv(params.tsv, function(error, data) {
			var cdata = data;
			for (var i=0;i<data.length;i++){
                cdata[ cdata[i].id] = cdata[i] ;
                delete  cdata[i].id;
                delete  cdata[i] ;
            }
			wd3MapChartMaps[p].updateChoropleth(cdata);
			//add data-id and data-value to path attribute so we can access it via this
			if(undefined != params.onclick){
				for (var key in cdata) {
					d3.select(p+' .datamaps-subunit.'+key)
						.attr("data-value",+cdata[key].value)
						.attr("data-label",+key);
				}
			}
		});
	}
	else if(undefined != params.json){
		if(undefined!=params.debug){console.log('loading json');}
		d3.json(params.json, function(error, data) {
			var cdata = data;
			for (var i=0;i<data.length;i++){
                cdata[ cdata[i].id] = cdata[i] ;
                delete  cdata[i].id;
                delete  cdata[i] ;
            }
			wd3MapChartMaps[p].updateChoropleth(cdata);
			//add data-id and data-value to path attribute so we can access it via this
			if(undefined != params.onclick){
				for (var key in cdata) {
					d3.select(p+' .datamaps-subunit.'+key)
						.attr("data-value",+cdata[key].value)
						.attr("data-label",+key);
				}
			}
		});
	}
	else if(undefined != params.data){
		if(undefined!=params.debug){console.log('loading data');}
		wd3MapChartMaps[p].updateChoropleth(params.data);
	}
	return false;

}
function wd3NumberWithCommas(x) {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
//---------- begin function wd3BarChart--------------------
/**
* @describe creates[or updates] a bar chart using d3
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
function wd3BarChart(p,params){
	if(undefined == d3){
		alert('d3 library is not loaded');
		return false;
	}
	if(undefined==p){p='body';}
	var pObj=document.querySelector(p);
	if(undefined == pObj){
		console.log('wd3BarChart Error: undefined parent');
		return;
	}
	if(undefined==params){params={};}
	if(undefined==params.width){params.width=getWidth(pObj);}
	if(undefined==params.height){params.height=getHeight(pObj);}
	if(undefined==params.top){params.top=30;}
	if(undefined==params.right){params.right=55;}
	if(undefined==params.bottom){params.bottom=50;}
	if(undefined==params.left){params.left=80;}
	if(undefined==params.padding){params.padding=1;}
	if(undefined==params.yticks){params.yticks=10;}
	if(undefined==params.label){params.label='label';}
	if(undefined==params.duration){params.duration=300;}
	if(undefined!=params.debug){console.log(params);}
	//do not allow zero height or width
	if(params.height < 20){params.height=300;}
	if(params.width < 20){params.width=300;}
	params.width =params.width - params.left - params.right,
	params.height = params.height - params.top - params.bottom;
	var color = d3.scale.category20();
	//scale x and y
	var	x = d3.scale.ordinal().rangeRoundBands([0, params.width], .1);
	var	y = d3.scale.linear().range([params.height, 0]);
	//x axis ticks and optional format
	var	xAxis = d3.svg.axis().scale(x)
		.orient("bottom")
		.ticks(params.xticks);
	if(undefined != params.xformat){
    	xAxis.tickFormat(d3.format(params.xformat));
	}
	//y axis ticks and optional format
	var	yAxis = d3.svg.axis().scale(y)
		.orient("left").ticks(params.yticks);
	if(undefined != params.yformat){
    	yAxis.tickFormat(d3.format(params.yformat));
	}

	if(undefined != params.rightvalue){
		var linecolor=color.range()[2];
		var	rightline = d3.svg.line()
			.x(function(d) { return x(d.label)+10; })
			.y(function(d) { return yLine(d.linevalue); });
			
		var	yLine = d3.scale.linear().range([params.height, 0]);

		if(undefined==params.rightticks){params.rightticks=10;}
		var	yAxisLine = d3.svg.axis().scale(yLine)
			.orient("right").ticks(params.rightticks);
		if(undefined != params.rightformat){
	    	yAxisLine.tickFormat(d3.format(params.rightformat));
		}
	}


	if(undefined == document.querySelector(p+' svg')){
		if(undefined!=params.debug){console.log('new bar chart - adding svg');}
		//title - place on bottom if pie, middle if donut
		d3.select(p).append("div")
	      		.attr("class", "title text-center")
	      		.text("");
		// draw and append the container
		var svg = d3.select(p).append("svg")
    		.attr("width", params.width + params.left + params.right)
    		.attr("height", params.height + params.top + params.bottom)
  			.append("g")
    			.attr("transform", "translate(" + params.left + "," + params.top + ")");

		svg.append("g")
    		.attr("class", "x axis")
    		.attr("transform", "translate(0," + params.height + ")");

		svg.append("g")
    		.attr("class", "y axis")
  			.append("text") // just for the title (ticks are automatic)
    			.attr("transform", "rotate(-90)") // rotate the text!
    			.attr("y", 6)
    			.attr("dy", ".71em")
    			.style("text-anchor", "end");

    	if(undefined != params.rightvalue){
			svg.append("g")
    		.attr("class", "y axisline")
    		.attr("transform", "translate(" + params.width + " ,0)")
  			.append("text") // just for the title (ticks are automatic)
    			.attr("transform", "rotate(-90)") // rotate the text!
    			.attr("y", 6)
    			.attr("dy", ".71em")
    			.style("text-anchor", "start");
		}
	}
	else{
		if(undefined!=params.debug){console.log('existing chart - updating');}
		var svg = d3.select(p+' svg');
	}
	//load the chart
	function loadbar(data){
		data.forEach(function(d) {
			d.value 	= +d.value;
			if(undefined != params.rightvalue){
				d.linevalue = +d[params.rightvalue];
			}
		});
		//console.log(data);
		// measure the domain (for x, unique letters) (for y [0,maxFrequency])
  		// now the scales are finished and usable
  		x.domain(data.map(function(d) { return d.label; }));
  		y.domain([0, d3.max(data, function(d) { return d.value; })]);
  		if(undefined != params.rightvalue){
			yLine.domain([0, d3.max(data, function(d) { return d.linevalue; })]);
		}

		// another g element, this time to move the origin to the bottom of the svg element
  		// someSelection.call(thing) is roughly equivalent to thing(someSelection[i])
  		//   for everything in the selection\
  		// the end result is g populated with text and lines!
  		svg.select('.x.axis').transition().duration(300).call(xAxis)
		  .selectAll("text") /* rotate x axis labels */
    			.attr("y", 0)
    			.attr("x", 9)
    			.attr("dy", ".35em")
    			.attr("transform", "rotate(90)")
    			.style("text-anchor", "start");

  		// same for yAxis but with more transform and a title
  		svg.select(".y.axis").transition().duration(300).call(yAxis);

  		if(undefined != params.rightvalue){
  			svg.select(".y.axisline").transition().duration(300).call(yAxisLine);
		}

  		// THIS IS THE ACTUAL WORK!
  		var bars = svg.selectAll("rect").data(data, function(d) { return d.label; }); // (data) is an array/iterable thing, second argument is an ID generator function
		//exit
  		bars.exit()
    		.transition()
      			.duration(300)
    		.attr("y", y(0))
    		.attr("height", params.height - y(0))
    		.style('fill-opacity', 1e-6)
    		.remove();

  		// data that needs DOM = enter() (a set/selection, not an event!)
  		bars.enter().append("rect")
    		.attr("fill", color.range()[0])
    		.attr("y", y(0))
    		.attr("height", params.height - y(0))
    		.attr("data-value", function(d) { return d.value; })
			.attr("data-label", function(d) { return d.label; })
			.on("mouseover", function(){
				var fill=d3.select(this).style("fill");
				d3.select(this).attr("fill_ori",fill);
				d3.select(this).style("fill", d3.rgb(fill).darker(0.3));
				})
			.on("mouseout", function() {
				var fill=d3.select(this).attr("fill_ori");
		  			d3.select(this).style("fill", fill);
				})
			.on("click", function() {
				if(undefined != params.onclick){
					if(undefined == window[params.onclick]){
	                	console.log('wd3MapChart onclick error: "'+params.onclick+'" is not a valid function');
	                	return false;
					}
					var args=new Array();
					args.push(d3.select(this).attr("data-label"));
					args.push(d3.select(this).attr("data-value"));
					window[params.onclick].apply(this,args);
				}
				else{
                	return false;
				}
			});;
    	//darken the slice on hover (mouseover) and restore the original color on mouseout

  		// the "UPDATE" set:
  		bars.transition().duration(300)
		  	.attr("x", function(d) { return x(d.label); }) // (d) is one item from the data array, x is the scale object from above
    		.attr("width", x.rangeBand()) // constant, so no callback function(d) here
    		.attr("y", function(d) { return y(d.value); })
    		.attr("height", function(d) { return params.height - y(d.value); }); // flip the height, because y's domain is bottom up, but SVG renders top down
	
		if(undefined != params.rightvalue){
        var line= svg.selectAll("path.line").data(data, function(d) { return d.label; });
        line.exit()
    		.transition()
      			.duration(300)
    		.remove();

  		// data that needs DOM = enter() (a set/selection, not an event!)
  		line.enter().append("path")
			.attr("class", "line")
			.style("fill","none")
			.style("stroke",linecolor)
			.style("stroke-width","3")
			.style("z-index","999")
			.attr("d", rightline(data));

  		// the "UPDATE" set:
  		line.transition().duration(300)
            .attr("d", rightline(data));

		}
	}
	//pass in the data
	if(undefined != params.csv){
		if(undefined!=params.debug){console.log('loading csv');}
		d3.csv(params.csv, loadbar);
	}
	else if(undefined != params.tsv){
		if(undefined!=params.debug){console.log('loading tsv');}
		d3.tsv(params.tsv, loadbar);
	}
	else if(undefined != params.json){
		if(undefined!=params.debug){console.log('loading json');}
		d3.json(params.json, loadbar);
	}
	else if(undefined != params.data){
		if(undefined!=params.debug){console.log('loading data');}
		loadbar(params.data);
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
	if(undefined==params.padding){params.padding=20;}
	if(undefined==params.labels){params.labels=1;}
	if(undefined==params.legend){params.legend=1;}
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
		//labels?
		if(params.labels==1){
			svg.append("g")
				.attr("class", "labels");
			svg.append("g")
				.attr("class", "lines");
		}
		//legend?
		if(params.legend==1){
			d3.select(p).append("div")
				.attr("class", "legend")
				.text("");
		}
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
	if(params.labels==1){
		//construct arc for labels
		var outerArc = d3.svg.arc()
	    	.innerRadius(radius * 0.9)
			.outerRadius(radius * 0.9);
	}

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
		/* ------- PIE SLICES -------*/
		var slice = svg.select(".slices").selectAll("path.slice")
			.data(pie(data), key);

		slice.enter()
			.insert("path")
			.style("fill", function(d,i) {
				if(undefined!=params.debug){console.log('new slice:i='+i+',color='+color.range()[i]+', label='+d.data.label);}
				return color.range()[i];
			})
			.style("stroke","#FFF")
			.style("stroke-width","1px")
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
				if(undefined == window[params.onclick]){
                	console.log('wd3MapChart onclick error: "'+params.onclick+'" is not a valid function');
                	return false;
				}
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
		if(params.labels==1){
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
		if(params.legend==1){
			/* ------- TEXT legend -------*/
			var legend = d3.select(p+" div.legend").selectAll("span.item")
				.data(pie(data), key).enter()
					.append("span")
					.attr("class", "item")
					.style("min-width","150px")
					.attr("data-percent", function(d) { return d.data.percent; })
					.attr("data-value", function(d) { return d.data.value; })
					.attr("data-label", function(d) { return d.data.label; });
			legend.append("span")
					.attr("class", "icon-blank")
					.style("margin-left","3px")
					.style("color",function(d,i) {return color.range()[i];});
			legend.insert("span")
					.text(function(d) {return ' '+d.data.label;});
			legend.on("mouseover", function() {
					d3.select(p+' .title').text(d3.select(this).attr("data-percent")+'%');
					});
			legend.on("mouseout", function() {
					d3.select(p+' .title').text("");
					});
			d3.select(p+" div.legend").selectAll("span.item")
				.data(pie(data), key).exit().remove();
		}
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
