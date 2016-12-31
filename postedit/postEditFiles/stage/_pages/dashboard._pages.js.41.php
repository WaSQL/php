function pageShowReport(rpt){
	return ajaxGet('/t/1/dashboard/report/'+rpt,'data');
}
function pageDrawChart(chartid,charttype,chartoptions){
	if(undefined == charttype){charttype='line';}
	if(undefined == chartoptions){
		chartoptions={
			onClick:function(evt){
				pageChartClick(evt,this);
			}
		};
	}
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
function pageChartClick(evt,ch){
	var el=ch.getElementAtEvent(evt);
	//console.log(el[0]._index);
	document.filterform.drilldown.value=el[0]._index;
	ajaxSubmitForm(document.filterform,'data');
}
