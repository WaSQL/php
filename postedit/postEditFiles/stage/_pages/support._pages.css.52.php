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

.w_indent{
	text-indent: 30px;
}
.well.tealback{
	padding:5px;
	background-color:#5c9993;
}
.well.tealback a:hover, .well.tealback.active a{
	color:#2f2b85;
}
/*faq*/
.faq_qna{
	max-height:1px;
	overflow:hidden;
	padding-left:40px;
	-webkit-transition: max-height 1s; 
  	-moz-transition: max-height 1s;
  	-ms-transition: max-height 1s;
  	-o-transition: max-height 1s;
  	transition: max-height 1s;
}
.faq_qna .question{
	font-weight:600;
}
.faq_qna .answer{
	padding-left:20px;
	font-size:0.9em;
}
.faq_category{
}
.faq_subcategory{
	padding:0 5px 3px 10px;
}
.faq_category_title{
	background:#d4d4d4;
	color:#000;
	font-weight:bold;
	padding:10px 0 10px 20px;
	text-align:left;
	font-size:1.2em;
}
.faq_subcategory_title{
	font-weight:bold;
	padding:3px 0 3px 20px;
}
.faq{
	margin-bottom:15px;
}

.faq .question{
	font-size:1.3em;

}

.faq .answer{
	margin:10px 0 0 30px;
}
