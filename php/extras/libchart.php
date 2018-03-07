<?php
/* References:
	https://naku.dohcrew.com/libchart/pages/introduction/
	NOTE: work in progress...
*/
$progpath=dirname(__FILE__);
include_once "{$progpath}/libchart/classes/libchart.php";
function libchartPieChart($title='',$opts=array(),$params=array()){
	$chart = new PieChart();
	$dataSet = new XYDataSet();
	foreach($opts as $key=>$val){
		$dataSet->addPoint(new Point($key, $val));
	}
	$chart->setDataSet($dataSet);
	$chart->setTitle($title);
	header('Content-type: image/png');
	$chart->render();
}

?>