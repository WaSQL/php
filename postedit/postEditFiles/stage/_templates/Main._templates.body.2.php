<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Skillsai - <?=templateMetaTitle();?></title>
	<link rel="canonical" href="http://<?=$_SERVER['HTTP_HOST'];?>/<?=pageValue('name');?>/" />
	<link rel="icon" type="image/ico" href="/favicon.ico">
	<link rel="shortcut icon" href="/images/site/SkillsaiLogo-150x75.png" type="image/png" />
	<link rel="apple-touch-icon" href="/images/site/SkillsaiLogo-150x75.png" type="image/png" />
	<meta name="google-site-verification" content="jeG1tBq7iJYZjCku5_Qv2PvwVLI5GqfxZIyyzKI8xcM" />
	<meta property="og:url" content="<?=templateOGMeta('og_url');?>" />
	<meta property="og:type" content="<?=templateOGMeta('og_type');?>" />
	<meta property="og:title" content="<?=templateOGMeta('og_title');?>" />
	<meta property="og:description" content="<?=templateOGMeta('og_description');?>" />
	<meta property="og:image" content="<?=templateOGMeta('og_image');?>" />


	<meta name="SKYPE_TOOLBAR" content="SKYPE_TOOLBAR_PARSER_COMPATIBLE" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="description" content="<?=templateMetaDescription();?>" />
	<meta name="keywords" content="<?=templateMetaKeywords();?>" />
	<meta property="fb:admins" content="100011207742246" />
	<meta name="p:domain_verify" content="80e69c7efe13b79ae96259eb6b01f544"/>
	<link type="text/css" rel="stylesheet" href="<?=minifyCssFile();?>" />
	<script type="text/javascript" src="<?=minifyJsFile();?>"></script>
	<view:piwik>
		<!-- Piwik -->
<script type="text/javascript">
  var _paq = _paq || [];
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u="//analytics.skillsai.com/";
    _paq.push(['setTrackerUrl', u+'piwik.php']);
    _paq.push(['setSiteId', '1']);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'piwik.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<noscript><p><img src="//analytics.skillsai.com/piwik.php?idsite=1" style="border:0;" alt="" /></p></noscript>
</view:piwik>
</head>
<body>
	<div class="container-fluid" style="padding:0px;">
		<div class="row">
			<div class="col-sm-12">
				<?=includePage('topmenu');?>
				<view:sidebar>
				<div class="row">
					<div class="col-sm-9">
						<?=pageValue('body');?>
					</div>
					<div class="col-sm-3" style="padding:0px;">
						<?=includePage('sidebar');?>
					</div>
				</div>
				</view:sidebar>
				<view:blanksidebar>
				<div class="row">
					<div class="col-sm-9">
						<?=pageValue('body');?>
					</div>
					<div class="col-sm-3">

					</div>
				</div>
				</view:blanksidebar>
				<view:nosidebar>
				<div class="row">
					<div class="col-sm-12" style="margin-top:-20px;">
						<?=pageValue('body');?>
					</div>
				</div>
				</view:nosidebar>
				<?switch(pageValue('sidebar')){case 1:return renderView('sidebar');break;case 2:return renderView('blanksidebar');break;default:return renderView('nosidebar');break;};?>
				<div class="row">
					<div class="col-sm-12">
						<?=includePage('botmenu');?>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<view:google_analytics>
	<script>
	(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  		})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
  	ga('create', 'UA-76764366-1', 'auto');
  	ga('send', 'pageview');
	</script>
	<!-- Start Alexa Certify Javascript -->
	<script type="text/javascript">
	_atrk_opts = { atrk_acct:"fy8Mn1QolK10uW", domain:"skillsai.com",dynamic: true};
	(function() { var as = document.createElement('script'); as.type = 'text/javascript'; as.async = true; as.src = "https://d31qbv1cthcecs.cloudfront.net/atrk.js"; 
		var s = document.getElementsByTagName('script')[0];s.parentNode.insertBefore(as, s); })();
	</script>		
	<noscript><img src="https://d5nxst8fruw4z.cloudfront.net/atrk.gif?account=fy8Mn1QolK10uW" style="display:none" height="1" width="1" alt="" /></noscript>
	<!-- End Alexa Certify Javascript --> 
	</view:google_analytics>
	<?=renderViewIf(!isDBStage(),'google_analytics');?>

	<view:freshchat>
	<!-- Start Fresh Chat Javascript -->
	<script type='text/javascript'>
		var fc_JS=document.createElement('script');
		fc_JS.type='text/javascript';
		fc_JS.src='https://assets1.freshchat.io/production/assets/widget.js?t='+Date.now();
		(document.body?document.body:document.getElementsByTagName('head')[0]).appendChild(fc_JS); 
		window._fcWidgetCode='85PJWD3OQJ';
		window._fcURL='https://skillsai.freshchat.io';
	</script>
	<!-- End Fresh Chat Javascript -->
	</view:freshchat>
	<?=renderViewIf(!isDBStage(),'freshchat');?>

	<view:async_hubspot>
	<!-- Start of Async HubSpot Analytics Code -->
	  <script type="text/javascript">
		(function(d,s,i,r) {
		  if (d.getElementById(i)){return;}
		  var n=d.createElement(s),e=d.getElementsByTagName(s)[0];
		  n.id=i;n.src='//js.hs-analytics.net/analytics/'+(Math.ceil(new Date()/r)*r)+'/2747079.js';
		  e.parentNode.insertBefore(n, e);
		})(document,"script","hs-analytics",300000);
	  </script>
	<!-- End of Async HubSpot Analytics Code -->
	</view:async_hubspot>
	<?=renderViewIf(!isDBStage(),'async_hubspot');?>
</body>
</html>
