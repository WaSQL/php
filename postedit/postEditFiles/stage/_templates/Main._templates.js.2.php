function templateDrawChart(chartid,charttype,chartoptions){
	if(undefined == charttype){charttype='line';}
	var ctx = document.getElementById(chartid+'_canvas').getContext("2d");
	var jsonstr=document.getElementById(chartid+'_data').innerText;
	var jsondata=parseJSONString(jsonstr);
	var config = {
        type: charttype,
        data: jsondata,
        options: chartoptions
	};
	var chart = new Chart(ctx,config);
    return false;
}
