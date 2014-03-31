//note: The following scripts must be loaded prior to loading this script
//	- https://www.google.com/jsapi
//	- /wfiles/js/json2.js

// Load the google Visualization API.
google.load('visualization', '1', {packages:['gauge','corechart']});

//wrapper function for google pie chart
function googlePieChart(div,title,opts){
	if(undefined == title){title='';}
	if(undefined == opts){return false;}
    //set default options
	google.setOnLoadCallback(function(){
		var datastr='{"cols":[{"id":"","label":"Label","pattern":"","type":"string"},{"id":"","label":"Value","pattern":"","type":"number"}],"rows":[';
		var parts=new Array();
		for (var key in opts){
			parts[parts.length]= '{"c":[{"v":"'+key+'","f":null},{"v":'+opts[key]+',"f":null}]}';
		}
		var partstr=googleImplode(',',parts);
		datastr += partstr+'],"p":null}';
      		new google.visualization.PieChart(document.getElementById(div)).draw(new google.visualization.DataTable(datastr), opts);
		});
	return false;
}
function googleBarChart(div,title,opts){
	if(undefined == title){title='';}
	if(undefined == opts){return false;}
    //set default options
	google.setOnLoadCallback(function(){
		var datastr='{"cols":[{"id":"","label":"Label","pattern":"","type":"string"},{"id":"","label":"Value","pattern":"","type":"number"}],"rows":[';
		var parts=new Array();
		for (var key in opts){
			parts[parts.length]= '{"c":[{"v":"'+key+'","f":null},{"v":'+opts[key]+',"f":null}]}';
		}
		var partstr=googleImplode(',',parts);
		datastr += partstr+'],"p":null}';
      		new google.visualization.BarChart(document.getElementById(div)).draw(new google.visualization.DataTable(datastr), opts);
		});
	return false;
}
function googleGuageChart(val,div,title,opts){
	//usage: googleGuageChart(value,divid,[title,opts]);
	if(undefined == title){title='';}
	if(undefined == opts){opts={};}
    //set default options
    if(undefined == opts['minorTicks']){opts['minorTicks']=5;}
    if(undefined != opts['red']){
		var tstr=''+opts['red'];
        var tmp=tstr.split(/[\-,:]+/);
        opts['redTo']=tmp[1];
        opts['redFrom']=tmp[0];
        opts=googleRemoveKey(opts,'red');
	}
	if(undefined != opts['green']){
        var tstr=''+opts['green'];
        var tmp=tstr.split(/[\-,:]+/);
        opts['greenTo']=tmp[1];
        opts['greenFrom']=tmp[0];
        opts=googleRemoveKey(opts,'green');
	}
	if(undefined != opts['yellow']){
    	var tstr=''+opts['yellow'];
        var tmp=tstr.split(/[\-,:]+/);
        opts['yellowTo']=tmp[1];
        opts['yellowFrom']=tmp[0];
        opts=googleRemoveKey(opts,'yellow');
	}
	google.setOnLoadCallback(function(){
		var datastr='{"cols":[{"id":"","label":"Label","pattern":"","type":"string"},{"id":"","label":"Value","pattern":"","type":"number"}],"rows":[{"c":[{"v":"'+title+'","f":null},{"v":'+val+',"f":null}]}],"p":null}';
      		new google.visualization.Gauge(document.getElementById(div)).draw(new google.visualization.DataTable(datastr), opts);
		});
	return false;
}
function googleImplode(glue, pieces){
    // Joins array elements placing glue string between items and return one string
    var i = '', retVal = '',        tGlue = '';
    if (arguments.length === 1) {
        pieces = glue;
        glue = '';
    }
	if (typeof(pieces) === 'object') {
        if (Object.prototype.toString.call(pieces) === '[object Array]') {
            return pieces.join(glue);
        }
        for (i in pieces) {            retVal += tGlue + pieces[i];
            tGlue = glue;
        }
        return retVal;
    }
	return pieces;
}
function googleRemoveKey(arrayName,key){
	var x;
	var tmpArray = new Array();
	for(x in arrayName){
  		if(x!=key) { tmpArray[x] = arrayName[x]; }
 	}
 	return tmpArray;
}

