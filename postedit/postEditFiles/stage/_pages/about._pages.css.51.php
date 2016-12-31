input[data-type="checkbox"]:checked + label[class*="icon-mark"], input[data-type="radio"] + label[class*="icon-mark"] {
    color: #ff9900;
    border: 3px solid #ff9900;
}
input[data-type="checkbox"]:checked + label, input[data-type="radio"]:checked + label {
    background: none;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.05) inset, 0 0 2px rgba(162, 162, 162, 0.6);
}

input[data-type="checkbox"] + label[class*="icon-mark"], input[data-type="radio"] + label[class*="icon-mark"] {
    border: 3px solid #f3f3f3;
    border-radius: 5px;
    color: #fff;
    font-weight: 900;
    padding: 2px;
    background:#f3f3f3;
}
.votecount{
	background:rgba(0,0,0,.15);
	border-radius:5px;
	padding:5px;
	font-size:1.2em;
	font-weight:bold;
	font-family:arial;
}
.project{
	background-color: #fff;
    border-radius: 3px;
    box-shadow: 0 1px 3px 1px rgba(0, 0, 0, 0.26), 0 0 0 1px rgba(0, 0, 0, 0.16);
    transition: box-shadow 200ms cubic-bezier(0.4, 0, 0.2, 1) 0s;
    VERTICAL-ALIGN: MIDDLE;
    padding:15px;
    margin-left:4px;
}
.w_indent{
	text-indent: 30px;
}
.well.lightorangeback{
	padding:5px;
}
.well.lightorangeback a:hover, .well.lightorangeback.active a{
	color:#2f2b85;
}
.donorchoose_title{
	font-size:1.3em;
	color:#20a1d4;
	margin-bottom:10px;
	font-weight:bold;
}
.donorchoose_trailer{
	font-size:0.9em;
	color:#303030;
}
.donorchoose_teacher{
	font-size:0.8em;
	color:#303030;
	font-weight:bold;
}
.donorchoose_school{
	font-size:0.7em;
	color:#606060;
}
.donorchoose_donors{
	font-size:1.1em;
	color:#606060;
}
.donorchoose_needed{
	font-size:1.2em;
	color:#606060;
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
