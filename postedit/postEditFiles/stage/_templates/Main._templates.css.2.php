html { font-size: 100%; }
body { line-height: 1.6875;font-family:calibri, "open sans", arial;}
h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 {
	font-family: 'montserratregular';
}
h1,.h1 { font-size: 175%; }
h2,.h2 { font-size: 160%; }
h3,.h3 { font-size: 145%; }
h4,.h4 { font-size: 130%; }
h5,.h5 { font-size: 115%; }
h6,.h6 { font-size: 100%; }

.hline {
    position: relative;
    z-index: 1;
    overflow: hidden;
    text-align: center;
    color:#9a9a9a;
}
.hline:before, .hline:after {
    position: absolute;
    top: 51%;
    overflow: hidden;
    width: 50%;
    height: 1px;
    content: '\a0';
    background-color: #c0c0c0;
}
.hline:before {
    margin-left: -50%;
    text-align: right;
}
.hline span{
	padding:0 15px 0 15px;
}

.w_facebook{color:#3B5998;}
.w_facebookback{background-color:#3B5998;color:#FFF;}
.vertical-center {
  min-height: 100%;  /* Fallback for browsers do NOT support vh unit */
  min-height: 100vh; /* These two lines are counted as one :-)       */

  display: flex;
  align-items: center;
}


.text{font-size: 100%;}
@media (min-width: 200px) {
	body { font-size: 70%; }
	.text{font-size: 70%;}
	.w_biggest{font-size: 100%;}
	img.social{width:24px;height:24px;}
	.post_date, .comment_count{display:inline-block;margin-bottom:1px;margin-right:10px;}
	#topnavbar, #reportsnavbar{
		position:absolute;
		right:10px;
		z-index:99999;
		border:5px solid #CCC;
		background-color:#FFF;
		border-radius:4px;
		padding:15px;
		max-width:90%;
	}
}
@media (min-width: 480px) {
	body { font-size: 80%; }
	.text{font-size: 80%;}
	.w_biggest{font-size: 110%;}
	img.social{width:32px;height:32px;}
	.post_date, .comment_count{display:inline-block;margin-bottom:1px;margin-right:10px;}
	#topnavbar, #reportsnavbar{
		position:absolute;
		right:10px;
		z-index:99999;
		border:5px solid #CCC;
		background-color:#FFF;
		border-radius:4px;
		padding:15px;
		max-width:90%;
	}
}
@media (min-width: 768px) {
	body { font-size: 90%; }
	.text{font-size: 90%;}
	.w_biggest{font-size: 120%;}
	img.social{width:40px;height:40px;}
	.post_date, .comment_count{display:block;margin-bottom:10px;margin-right:5px;}
	#topnavbar, #reportsnavbar{
		position:relative;
		z-index:99999;
		border:0px solid #CCC;
		background-color:inherit;
		max-width:90%;
	}
}
@media (min-width: 992px) {
	body { font-size: 100%; }
	.text{font-size: 100%;}
	.w_biggest{font-size: 130%;}
	img.social{width:48px;height:48px;}
	.post_date, .comment_count{display:block;margin-bottom:10px;margin-right:5px;}
	#topnavbar, #reportsnavbar{
		position:relative;
		z-index:99999;
		border:0px solid #CCC;
		background-color:inherit;
		max-width:90%;
	}
}
.dropdown-menu{
	background-color:#FFF !important;
	text-align:left !important;
	z-index:9999;
}
.dropdown-menu a:hover{
	background-color:#ececec !important;
}
img.social:hover {
    -webkit-animation:spin 1s linear infinite;
    -moz-animation:spin 1s linear infinite;
    animation:spin 1s linear infinite;
}
@-moz-keyframes spin { 100% { -moz-transform: rotate(360deg); } }
@-webkit-keyframes spin { 100% { -webkit-transform: rotate(360deg); } }
@keyframes spin { 100% { -webkit-transform: rotate(360deg); transform:rotate(360deg); } }

.well.blog{
	min-height:100px;
	border:5px solid #CCC;
	background-color:#FFF;
	border-radius:4px;
	padding:15px;
	margin:0 auto;
	max-width:100%;
	position:relative;
}
.padlr{
	padding-left:10px;
	padding-right:10px;
}
.img-fit{
	width: 100%;
    height: 100%;
    object-fit: cover;
}
.vcenter {
    display: inline-block;
    top: 50%;
  	transform: translateY(-50%);
    float: none;
}
canvas{
    -moz-user-select: none;
    -webkit-user-select: none;
    -ms-user-select: none;
}
/* Fonts */
@font-face {
    font-family: 'montserratregular';
    src: url('/fonts/montserrat-regular-webfont.eot');
    src: url('/fonts/montserrat-regular-webfont.eot?#iefix') format('embedded-opentype'),
         url('/fonts/montserrat-regular-webfont.woff2') format('woff2'),
         url('/fonts/montserrat-regular-webfont.woff') format('woff'),
         url('/fonts/montserrat-regular-webfont.ttf') format('truetype'),
         url('/fonts/montserrat-regular-webfont.svg#montserratregular') format('svg');
    font-weight: normal;
    font-style: normal;
}

@font-face {
    font-family: 'montserratbold';
    src: url('/fonts/montserrat-bold-webfont.eot');
    src: url('/fonts/montserrat-bold-webfont.eot?#iefix') format('embedded-opentype'),
         url('/fonts/montserrat-bold-webfont.woff2') format('woff2'),
         url('/fonts/montserrat-bold-webfont.woff') format('woff'),
         url('/fonts/montserrat-bold-webfont.ttf') format('truetype'),
         url('/fonts/montserrat-bold-webfont.svg#montserratbold') format('svg');
    font-weight: normal;
    font-style: normal;

}

@font-face {
    font-family: 'kg_what_the_teacher_wantsRg';
    src: url('/fonts/kgwhattheteacherwants-webfont.eot');
    src: url('/fonts/kgwhattheteacherwants-webfont.eot?#iefix') format('embedded-opentype'),
         url('/fonts/kgwhattheteacherwants-webfont.woff2') format('woff2'),
         url('/fonts/kgwhattheteacherwants-webfont.woff') format('woff'),
         url('/fonts/kgwhattheteacherwants-webfont.ttf') format('truetype'),
         url('/fonts/kgwhattheteacherwants-webfont.svg#kgwhattheteacherwantsregular') format('svg');
    font-weight: normal;
    font-style: normal;
}
#centerpop{z-index:9999999 !important;}
/* Colors */
.orange{color: #ff931e;}
.blue{color: #25297b;}
.lightblue{color:#5b9bd5;}
.cream{color: #f8f8d7;}
.teal{color: #8CC3B5;}
.lightteal{color: #cae3dd;}

.orangeback, .btn-warning{background-color: #ff931e;}
.blueback{background-color: #25297b;}
.lightblueback{background-color:#5b9bd5;color:#FFF;}
.lightblueback:hover,.lightblueback a:hover{background-color:#3483c9;color:#FFF;}
.creamback{background-color: #f8f8d7;}
.tealback{background-color: #ffce85;}
.tealdarkback{background-color: #5c9993;}
.lighttealback{background-color: #cae3dd;}
.lightorange{color:#ffce85;}
.lightorangeback{background-color:#ffce85;}



.btn-primary {
    background-color: #25297b;
}
.btn-primary:hover {
    background-color: #353cb3;
}
.w_centerpop{
	border:none;
}
.w_centerpop .w_centerpop_title {
    background: #f3f3f3 none repeat scroll 0 0;
	color:#000;
}
.w_centerpop_close_bot{
	display:none;
}
.w_centerpop_close_top {
    position: absolute;
    right: 5px;
    top: 5px;
    font-size: 14px;
    font-family: arial;
    cursor: pointer;
    z-index: 9993;
    color: #d9534f;
}
/* index sections */
.section1{
    color:#f8f8d7;
    background-color:#01abb4;
}
.section1.gradient{
	background: rgba(212,228,239,1);
	background: -moz-radial-gradient(center, ellipse cover, rgba(212,228,239,1) 0%, rgba(1,171,180,1) 100%);
	background: -webkit-gradient(radial, center center, 0px, center center, 100%, color-stop(0%, rgba(212,228,239,1)), color-stop(100%, rgba(1,171,180,1)));
	background: -webkit-radial-gradient(center, ellipse cover, rgba(212,228,239,1) 0%, rgba(1,171,180,1) 100%);
	background: -o-radial-gradient(center, ellipse cover, rgba(212,228,239,1) 0%, rgba(1,171,180,1) 100%);
	background: -ms-radial-gradient(center, ellipse cover, rgba(212,228,239,1) 0%, rgba(1,171,180,1) 100%);
	background: radial-gradient(ellipse at center, rgba(212,228,239,1) 0%, rgba(1,171,180,1) 100%);
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#d4e4ef', endColorstr='#01abb4', GradientType=1 );
}
.section2{
	color: #f8f8d7;
	background-color:#f8f8d7;
}
.section3{
	color: #f8f8d7;
	background-color:#ff9900;
}

.counter{
	background-color:#25297b;
	color:#f8f8d7;
	padding:10px;
	margin-right:4px;
	font-size:40px;
	border-radius:4px;
	font-weight:700;
	font-family: 'montserratbold';
}
.mfont{font-family: 'montserratbold';}
.tfont{font-family: 'kg_what_the_teacher_wantsRg';}

.icon-site-facebook-squared, .icon-site-twitter{
	font-size:36px;
	padding:5px;
}
.xnavbar-nav > li > a{
	border-bottom-left-radius:7px;
	border-bottom-right-radius:7px;
}
.nav .open > a, .nav .open > a:hover, .nav .open > a:focus, .nav li > a:hover, .nav li > a:focus {
	background-color:inherit;
}
.dropdown-menu{
	background-color:inherit;
	border-top:0px;
}


.sidebar{
	display:inline-block;
	padding:10px !important;
	margin:0 auto;
}
hr{
	background-color:#CCC;
	height:4px;
	margin:10px 15px 10px 15px;
}
