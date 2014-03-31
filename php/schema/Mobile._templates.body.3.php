<!DOCTYPE html PUBLIC
  "-//WAPFORUM//DTD XHTML Mobile 1.2//EN"
  "http://www.openmobilealliance.org/tech/DTD/xhtml-mobile12.dtd">
<html>
<head>
	<META NAME="keywords" CONTENT="">
  	<META NAME="description" content="">
  	<META name="abstract" content="">
  	<META name="robots" content="index,follow">
  	<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
	<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=4.0; user-scalable=1;" />
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<link rel="apple-touch-icon" sizes="64x64" href="/wfiles/iconsets/64/globe.png" />
	<link rel="apple-touch-startup-image" href="/wfiles/mobile_loading.png" />
	<link type="text/css" rel="stylesheet" href="<?=minifyCssFile();?>" />
	<script type="text/javascript" src="<?=minifyJsFile();?>"></script>
	<title><?return $_SERVER['HTTP_HOST'];?> - Mobile</title>
</head>
<body onload="mobileHideAddressBar();" class="w_mobilebody">
<div class="w_mobilemenu">
	<table cellspacing="0" cellpadding="3" border="0" width="100%">
		<tr valign="middle">
			<td><a href="#" onclick="showHideMobile('sideMenu');document.sform.searchtxt.focus();return false;"><img src="/wfiles/iconsets/32/menu.png" border="0" width="32" height="32" alt="Menu" title="Menu"></a></td>
			<td width="100%" align="center"><div>Mobile Site Demo</div></td>
			<td><a href="#" onclick="showHideMobile('searchForm');document.sform.searchtxt.focus();return false;"><img src="/wfiles/iconsets/32/search.png" border="0" width="32" height="32" alt="Search" title="Search"></a></td>
			<td><img src="/wfiles/clear.gif" height="38" width="5"></td>
		</tr>
	</table>
</div>
<div class="w_mobilemenu" id="sideMenu" style="display:none;position:absolute;left:0px;background:#FFF;border:1px solid #6d84a2;padding:10px;z-index:900;" align="left">
	<div><a href="#"><img src="/wfiles/iconsets/32/home.png" border="0" width="32" height="32" alt="Home" title="Home"> Home</a></div>
	<div><a href="#"><img src="/wfiles/iconsets/32/email.png" border="0" width="32" height="32" alt="Contact" title="Contact"> Contact</a></div>
	<div><a href="#"><img src="/wfiles/iconsets/32/gear.png" border="0" width="32" height="32" alt="Settings" title="Settings"> Settings</a></div>
</div>
<div id="searchForm" style="display:none;position:absolute;right:10px;background:#FFF;border:1px solid #6d84a2;padding:10px;z-index:850;" align="right">
	<form name="sform" action="/" method="POST" onsubmit="return ajaxSubmitForm(this,'mobilecontent');">
		<input type="hidden" name="_template" value="blank">
		<input type="text" name="searchtxt" value="" />
   	<input type="submit" value="Search" />
	</form>
</div>
<div id="mobilecontent" class="w_pad">
<?
echo pageValue('body');
?>
</div>
</body>
</html>
