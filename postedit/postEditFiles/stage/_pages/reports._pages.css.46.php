/* tile css */
.tile {
  display: block;
  background-color:#FFF;
  position:relative;
  -webkit-perspective: 0;
  -webkit-transform-style: preserve-3d;
  transition: all .25s ease-in-out;
  -moz-transition: all .25s ease-in-out;
  -webkit-transition: all .25s ease-in-out;
  padding:0 0 5px 0;
  float: left;
  margin:0 5px 5px 0;
  width: 400px;
  height: 450px;
  text-align: center;
  opacity: 1;
  z-index: 1;
  border: 1px #ccc solid;
  color: #000;
  border-radius:4px;
  overflow:hidden;
}
.tile .topbar{
	text-align:right;
}
.tile .botbar{
	text-align:left;
	position:absolute;
	width:100%;
	left:0px;
	bottom:0px;
	height:40px;
	padding:0px;
	color:#FFF;
	background-color:rgba(107,99,110,0.60);
}
.tile .botbar a{
	color:#FFF;
}
.tile .info{
	margin:20px 0 0 30px;
}
.tile .botbar .content{
	vertical-align:middle;
	padding:5px 10px 0 15px;
	color:#fff;
	height:40px;
}
.tile .botbar .content .text{
	margin-top:5px;
}
.tile a{
	color:#93939b;
}
.tile a:hover {
  text-decoration: underline;

}
.tile .name{
	font-size:20px;
	text-align:center;
}
.tile .menu{
	text-align:center;
	font-size:.8em;
}
.tiles{
	min-height:900px;
}
.tile img {
  border: 0;
}
.tile:hover {
  opacity: 1;
}
.report.btn{
min-width:175px;
margin-bottom:2px;
}

/* us states map */
.states {
	fill: #aaa;
    stroke: #fff;
    stroke-width: 0.75px;
    color:#FFF;
}
.states.UT{
	fill:#c1c113;
}
.states.GA{
	fill:#13c113;
}
.rChart {
      display: block;
      margin-left: auto;
      margin-right: auto;
      width: 800px;
      height: 400px;
    }  
    .container {
      margin-top: 20px;
    }
.datamaps {
  position: relative;
}
.datamaps-legend{
	margin-left:20px;
}
.popup{
	position:absolute;
	top:5px;
	right:5px;
	background-color:#FFF;
	padding:4px;
	border-radius:5px;
	color:#000;
}
.report_group{margin-left:15px;}
.report_config{margin-right:15px;}

.config .report{
	border:1px solid #ccc;
	border-radius:5px;
	background:#FFF;
	padding:4px;
	margin-top:10px;
}

[draggable] {
  -moz-user-select: none;
  -khtml-user-select: none;
  -webkit-user-select: none;
  user-select: none;
  /* Required to make elements draggable in old WebKit */
  -khtml-user-drag: element;
  -webkit-user-drag: element;
}
.drag{
	cursor:drag;
}
.drop{
	-moz-user-select: none;
  -khtml-user-select: none;
  -webkit-user-select: none;
  user-select: none;
}


#chart{
	font: 12px Arial;
	height:332px;
	width:398px;
}
#chart path {
	stroke: steelblue;
	stroke-width: 2;
	fill: none;
}

#chart .axis path,
#chart .axisline path,
#chart .axis line, 
#chart .axisline line {
	fill: none;
	stroke: grey;
	stroke-width: 1;
	shape-rendering: crispEdges;
}
#chart .legend {
	font-size: 12px;
	text-anchor: start;
	font-weight:bold;
}

.title{
	font-size:14pt;
	font-weight:normal;
}
.subtitle{
	font-size:10pt;
	font-weight:normal;
}

#chart .grid {
	opacity: 0.2;
}

.config{
	position:absolute;
	right:0px;
	top:22px;
	border-left:1px solid #ccc;
	border-bottom:1px solid #ccc;
	border-bottom-left-radius:8px;
	padding:3px 15px 5px 5px;
	z-index:9999;
	background-color:#fcfcfc;

}
.topbar{
	border-bottom:0px solid #ccc;
	border-bottom-left-radius:8px;
	border-bottom-right-radius:8px;
	background-color:#fcfcfc;
	padding:5px 0 0 0;
}


/*===================*/
/* simple */
.flip-container {
	-webkit-perspective: 1000;
	-moz-perspective: 1000;
	-ms-perspective: 1000;
	perspective: 1000;
	-ms-transform: perspective(1000px);
	-moz-transform: perspective(1000px);
   	-moz-transform-style: preserve-3d;
    -ms-transform-style: preserve-3d;
	margin:0 15px 15px 0;
	float:left;
}
/* START: Accommodating for IE */
.flip-container.hover .back {
    -webkit-transform: rotateY(0deg);
    -moz-transform: rotateY(0deg);
    -o-transform: rotateY(0deg);
    -ms-transform: rotateY(0deg);
    transform: rotateY(0deg);
    z-index:999;
}
.flip-container.hover .front {
    -webkit-transform: rotateY(180deg);
    -moz-transform: rotateY(180deg);
    -o-transform: rotateY(180deg);
    transform: rotateY(180deg);
    z-index:999;
}
/* END: Accommodating for IE */

.flip-container, .front, .back {
	width: 400px;
  	height: 380px;
}
.flipper {
	-webkit-transition: 0.6s;
	-webkit-transform-style: preserve-3d;
	-ms-transition: 0.6s;
	-moz-transition: 0.6s;
	-moz-transform: perspective(1000px);
	-moz-transform-style: preserve-3d;
	-ms-transform-style: preserve-3d;
	transition: 0.6s;
	transform-style: preserve-3d;
	position: relative;
}
.front, .back {
	-webkit-backface-visibility: hidden;
	-moz-backface-visibility: hidden;
	-ms-backface-visibility: hidden;
	backface-visibility: hidden;
	-webkit-transition: 0.6s;
	-webkit-transform-style: preserve-3d;
	-webkit-transform: rotateY(0deg);
	-moz-transition: 0.6s;
	-moz-transform-style: preserve-3d;
	-moz-transform: rotateY(0deg);
	-o-transition: 0.6s;
	-o-transform-style: preserve-3d;
	-o-transform: rotateY(0deg);
	-ms-transition: 0.6s;
	-ms-transform-style: preserve-3d;
	-ms-transform: rotateY(0deg);
	transition: 0.6s;
	transform-style: preserve-3d;
	transform: rotateY(0deg);
	position: absolute;
	top: 0;
	left: 0;
}
.front {
	-webkit-transform: rotateY(0deg);
	-ms-transform: rotateY(0deg);
	background: #ffffff;
	z-index: 2;
}
.back {
	background: #ffffff;
	-webkit-transform: rotateY(-180deg);
	-moz-transform: rotateY(-180deg);
	-o-transform: rotateY(-180deg);
	-ms-transform: rotateY(-180deg);
	transform: rotateY(-180deg);
}
