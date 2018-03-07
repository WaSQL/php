<?php
/* References:
	https://naku.dohcrew.com/libchart/pages/introduction/
	NOTE: work in progress...  need to build PieChart, BarChart, LineChart
		2018-03-07: libchartPieChart is finished
*/
$progpath=dirname(__FILE__);
include_once "{$progpath}/libchart/classes/libchart.php";

//---------- begin function libchartPieChart ----------
/**
* @describe creates a pie chart
* @reference https://naku.dohcrew.com/libchart/pages/documentation/classPieChart.html
* @param title string - title of chart
* @param datasets array - key=>value pairs to plot 
* @param params array - additional settings
*		[width] integer - render width. default is 600
*		[height] integer - render height. default is 250
*		[colors] array - specify colors to render in order of datasets
*		[-filename] string - create and return filename instead of rendering inline
* @return mixed - returns the image unless -filename then returns file generated.
* @usage loadExtras('libchart'); libchartPieChart('test widgets',$datasets,$params);
*/
function libchartPieChart($title='',$opts=array(),$params=array()){
	if(!isset($params['width'])){$params['width']=600;}
	if(!isset($params['height'])){$params['height']=250;}
	$chart = new PieChart($params['width'],$params['height']);
	if(isset($params['colors']) && is_array($params['colors'])){
		$colors=array();
		foreach($params['colors'] as $color){
			if(!is_array($color)){$color=hex2RGB($color);}
			$colors[]=new Color($color[0],$color[1],$color[2]);
		}
		$chart->getPlot()->getPalette()->setPieColor($colors);	
	}
	$dataSet = new XYDataSet();
	foreach($opts as $key=>$val){
		$dataSet->addPoint(new Point($key, $val));
	}
	$chart->setDataSet($dataSet);
	$chart->setTitle($title);
	//draw percent?
	if(isset($params['-percent']) && $params['-percent']){
		$chart->drawPercent();
	}
	//create an image?
	if(isset($params['-filename'])){
		$chart->render($params['-filename']);
		if(file_exists($params['-filename'])){
			return $params['-filename'];
		}
		return null;
	}
	header('Content-type: image/png');
	$chart->render();
}

?>