<?php
/* References:
	https://naku.dohcrew.com/libchart/pages/introduction/
	http://colorhunt.co/
	https://coolors.co/a2c5ac-9db5b2-e6ccbe-878e99-7f6a93
	http://www.color-hex.com/color-palettes/
	
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
	}
	else{
		$colors=libchartColorThemes();
	}
	$chart->getPlot()->getPalette()->setPieColor($colors);
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
function libchartColorThemes($theme=''){
	switch(strtolower($theme)){
		case 'neon':
			$hexcolors=array('#9900ff','#99ff00','#ff9900','#00ff99','#00eeee','#f00000','#fd1bff','#3ccdec','#2fff00','#e8ff29','#e3247a');
		break;
		default:
			$hexcolors=array('#a6cee3','#1f78b4','#b2df8a','#33a02c','#fb9a99','#e31a1c','#fdbf6f','#ff7f00','#cab2d6','#6a3d9a','#ffff99');
		break;
	}
	$colors=array();
	foreach($hexcolors as $color){
		$color=hex2RGB($color);
		$colors[]=new Color($color[0],$color[1],$color[2]);
	}
	return $colors;
}
?>