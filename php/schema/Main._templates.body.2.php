<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?=templateMetaTitle();?></title>
	<link rel="canonical" href="http://<?=$_SERVER['HTTP_HOST'];?>/" />
	<link rel="shortcut icon" href="/images/logo.png" type="image/png" />
	<link rel="apple-touch-icon" href="/images/logo.png" type="image/png" />
	<meta property="og:title" content="" />
	<meta property="og:type" content="website" />
	<meta property="og:url" content="http://<?=$_SERVER['HTTP_HOST'];?>/" />
	<meta property="og:site_name" content="<?=$_SERVER['HTTP_HOST'];?>" />
	<meta name="SKYPE_TOOLBAR" content="SKYPE_TOOLBAR_PARSER_COMPATIBLE" />
	<meta name="viewport" content="minimum-scale=0.25, maximum-scale=1.2" />
	<meta name="description" content="<?=templateMetaDescription();?>" />
	<meta name="keywords" content="<?=templateMetaKeywords();?>" />
	<link type="text/css" rel="stylesheet" href="<?=minifyCssFile();?>" />
  	<script type="text/javascript" src="<?=minifyJsFile();?>"></script>
</head>
<body>
	<div class="background-div">
		<div style="position: absolute;width:100%;top: 50%;transform: translateY(-50%);">
			<div class="w_white" style="font-size:3em;">Super Awesome Company</div>
			<div class="w_white" style="font-size:1.5em;">Our website isn't quite ready, but you can still...</div>
		</div>
	</div>
	<div style="position: absolute;width:100%;top: 50%;transform: translateY(-50%);min-height:200px;" align="center">
		<div style="width:70%;margin-left:auto;margin-right:auto;min-height:200px;" class="well container">
			<?=pageValue('body');?>
		</div>
	</div>
</body>
</html>
