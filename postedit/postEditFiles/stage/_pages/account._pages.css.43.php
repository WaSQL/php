.board,
.board-sidebar,
.board-title,
#board-main {
  background: #FFF;
  border-color: #000;
  border-width: 3px;
  border-style: solid;
  height: 1000px;
}
input.w_toggle-round + label {
  padding: 2px;
  width: 80px;
  height: 32px;
  background-color: #dddddd;
  -webkit-border-radius: 32px;
  -moz-border-radius: 32px;
  -ms-border-radius: 32px;
  -o-border-radius: 32px;
  border-radius: 32px;
}
input.w_toggle-round:checked + label:before {
  background-color: #5cb85c;
  content: attr(data-on);
  text-align: left;
  padding-left:6px;
}
input.w_toggle-round:checked + label:after {
  margin-left: 50px;
}
.board-title {
  height: 75px;
}

.board-sidebar {
  height: 700px;
}

.board-main {
  height: 700px;
}

.dropdown-menu{
	position:absolute;
	right:0px;
	text-align:left;
	background:#FFF;
	z-index:99999;
}
.reportMenu a{
    background-color: #fff;
    border-radius: 2px;
    box-shadow: 0 1px 3px 1px rgba(0, 0, 0, 0.16), 0 0 0 1px rgba(0, 0, 0, 0.06);
	font-size:20px;
    transition: box-shadow 200ms cubic-bezier(0.4, 0, 0.2, 1) 0s;
    vertical-align: middle;
    padding:3px 3px 1px 3px;
    margin-left:4px;
}
.reportMenu a:hover{
	text-decoration:none;
	background-color: #f8f8f8;
}

/* tile css */
.stream_request{
	position:relative;
	padding:5px;
	border-radius:5px;
	background-color:#deecf9;
	margin-right:30%;
	margin-bottom:5px;
}
.stream_request:after {
	content: '';
	position: absolute;
	border-style: solid;
	border-width: 9px 9px 9px 0;
	border-color: transparent #deecf9;
	display: block;
	width: 0;
	z-index: 1;
	left: -9px;
	top: 5px;
}
.stream_response{
	position:relative;
	padding:5px;
	border-radius:5px;
	background-color:#fdeed9;
	margin-left:30%;
	margin-bottom:5px;
}
.stream_response:after {
	content: '';
	position: absolute;
	border-style: solid;
	border-width: 9px 0 9px 9px;
	border-color: transparent #fdeed9;
	display: block;
	width: 0;
	z-index: 1;
	right: -9px;
	top: 5px;
}

.stream_order{
	position:relative;
	padding:5px;
	border-radius:5px;
	background-color:#d5ffd5;
	margin-bottom:5px;
}
.stream_order:after {
	content: '';
	position: absolute;
	border-style: solid;
	border-width: 9px 9px 9px 0;
	border-color: transparent #d5ffd5;
	display: block;
	width: 0;
	z-index: 1;
	left: -9px;
	top: 5px;
}

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
  width: 360px;
  height: 400px;
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
.data.list{
	overflow:auto;
	max-height:350px;
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
      width: 700px;
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
  /* Required to make elements draggable in old WebKit */
  -khtml-user-drag: element;
  -webkit-user-drag: element;
}
.drag{
	cursor:drag;
}
.drop{

}


#chart{
	font: 11px Arial;
	height:332px;
	width:348px;
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
	font-size: 11px;
	text-anchor: start;
	font-weight:bold;
}

.title{
	font-size:13pt;
	font-weight:600;
}
.subtitle{
	font-size:10pt;
	font-weight:400;
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
	width: 350px;
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
