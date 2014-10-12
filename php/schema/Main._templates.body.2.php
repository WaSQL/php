<!DOCTYPE HTML>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Your title here</title>
	<link href="<?=minifyCssFile();?>" rel="stylesheet">
    <script type="text/javascript" src="<?=minifyJsFile();?>"></script>
</head>
<body>
<div id="container">
    <div id="header">
    	<div class="logo"><a href="/"><img src="/wfiles/iconsets/64/world.png" border="0" alt="logo" /></a></div>
    </div>
    <div class="mainMenu">
        <ul>
            <li><a href="/">home</a></li>
            <li><a href="/about">about</a></li>
            <li class="w_right"><a href="/contact">contact</a></li>
        </ul>
    </div>
    <div class="mainContent">
    	<?=pageValue('body');?>
	</div>
	<div class="footerMenu" align="center">
            <li><a href="/contact">contact</a></li>
        </ul>
    </div>
</div>
</body>
</html>
