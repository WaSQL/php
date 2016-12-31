#formfields{
	max-height: 0;
	overflow: hidden;
	-webkit-transition: max-height 0.8s;
	-moz-transition: max-height 0.8s;
	transition: max-height 0.8s;
}
#formfields.open{
	max-height:500px;
}

input[data-type="checkbox"][class*="package"] + label{
    border-radius: 6px;
    color: #1884c2;
    background: #fff none repeat scroll 0 0;
    font-weight: 900;
    padding: 10px;
    box-shadow: 2px 4px 8px rgba(92, 92, 92, .75);
}
input[data-type="checkbox"][class*="package"]:checked + label{
    background: #fff none repeat scroll 0 0;
    box-shadow: 2px 4px 8px rgba(92, 92, 92, .75);
}
input[data-type="checkbox"][class*="package"] + label  span[class*="icon-mark"]{
    color: #f9f9f9;
    font-size:1.2em;
    border:1px solid #e5e5e5;
    border-radius:4px;
}
input[data-type="checkbox"][class*="package"]:checked + label span[class*="icon-mark"]{
    color: #5cb85c;
}
#yearly, #monthly{
	font-size:1.5em;
	font-weight:900;
}
.pick_pricing{
	z-index:9999;
	transform:translateY(50%);
	left:30px;
	text-decoration:none;
	color:#1884c2;
}
.pick_pricing span{
	color:#1884c2;
	font-size:6em;
}
.pick_pricing:hover{
	text-decoration:none;
}
.btn {
	 box-shadow: 2px 4px 8px rgba(92, 92, 92, .75);
	 font-weight:bold;
	 font-size:1.2em;
	 width:60%;
	 color:#FFF;
	 max-width:200px;
}
.icon-arrow-left{
	color:rgba(238,238,238,.6);
}
.pick_pricing:hover .icon-arrow-left{
	color:rgba(238,238,238,.8);
}
img.plan{
	width:100%;
	max-width:100%;
}
.solo .btn {
    background-color: #FF9900;
    border-color: #8F7900;
}
.startup .btn{
	background-color: #1aaab3;
	border-color: #73887b;
}
.growing .btn{
	background-color: #CCCCCB;
	border-color: #9d9d9d;
}
.established .btn{
	background-color: #fcd905;
	border-color: #c89e52;
}
.package{
	background-color:#1884c2;
	padding:15px;
	border-radius:10px;
	font-size:.9em;
	color:#FFF;
	margin-bottom:15px;
}
.package .title{
	font-size:1.5Em;
	font-weight:700;
	line-height:95%;
}
.package ul{
	margin-top:5px;
	padding-left:15px;
}
