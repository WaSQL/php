<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title><?=$_SERVER['HTTP_HOST'];?></title>
        <link href="<?=minifyCssFile();?>" rel="stylesheet">
        <script type="text/javascript" src="<?=minifyJsFile();?>"></script>
</head>
<body class="page">
	<div id="wrap">
        <div id="header">
        	<div style="position:absolute;top:50px;left:50px;">
				<img src="/wfiles/images/logo.png" border="0" alt="logo" class="w_marginright w_left" />
				<div style="margin-left:175px;margin-top:30px;font-family:arial;">
					<div style="font-size:50px;">COMING SOON!</div>
					<div style="font-size:20px;margin:5px 0 0 20px;">This site is under construction!</div>
				</div>
			</div>
			<br clear="both" />
			<div id="nav">
			<ul class="menu">
				<li class="<?=templateCurrentMenu('index');?>"><a href="/index">Home</a></li>
				<li class="<?=templateCurrentMenu('services');?>"><a href="/services">Services</a>
					<ul class="sub-menu">
						<li><a href="#lorem">Lorem</a></li>
						<li><a href="#ipsem">Ipsem</a></li>
						<li><a href="#adipisicing">Adipisicing </a></li>
						<li><a href="#dolore_magna">Dolore Magna</a></li>
					</ul>
				</li>
				<li class="<?=templateCurrentMenu('articles');?>"><a href="/articles">Articles</a></li>
				<li class="<?=templateCurrentMenu('contact');?>"><a href="/contact">Contact</a></li>
			</ul>
		</div><!--end nav-->
		</div><!--end header-->
        <div class="page-headline"><?=pageValue('title');?></div>
		<div id="main">
			<?=pageValue('body');?>
		</div><!--end main-->
		<div id="footer">
			<p class="copyright">
				Copyright &copy; <?=date('Y');?> / 
				<?=$_SERVER['HTTP_HOST'];?> / 
				All Rights Reserved /
				<a href="http://www.wasql.com" target="new">powered by WaSQL</a>
			</p>
		</div><!--end footer-->
	</div> <!--end wrap-->
</body>
<div class="cache-images">
	<img src="/wfiles/images/red-button-bg.png" width="0" height="0" alt="" />
	<img src="/wfiles/images/black-button-bg.png" width="0" height="0" alt="" />
</div><!--end cache-images-->
</html>
