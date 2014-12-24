<view:default>
	<div class="background-div">
		<div style="position: absolute;width:100%;top: 50%;transform: translateY(-50%);">
			<div class="w_white" style="font-size:3em;">Super Awesome Company</div>
			<div class="w_white" style="font-size:1.5em;">Our website isn't quite ready, but you can still...</div>
		</div>
	</div>
	<div style="position: absolute;width:100%;top: 50%;transform: translateY(-50%);min-height:100px;" align="center">
		<div style="width:60%;margin-left:auto;margin-right:auto;min-height:100px;" class="well container">
			<div class="row">
				<div class="col-xs-4">
					<div class="mybox round">
						<h2>Contact us</h2>
						<div><a href="tel:123.123.1234" class="w_big"><span class="icon-phone"></span> 123.123.1234</a></div>
						<div><a href="mailto:info@<?=strtolower($_SERVER['UNIQUE_HOST']);?>" class="w_big"><span class="icon-mail"></span> info@<?=strtolower($_SERVER['UNIQUE_HOST']);?></a></div>
					</div>
				</div>
			    <div class="col-xs-4">
			    	<div class="mybox round">
						<h2><span class="icon-mail"></span> Subscribe</h2>
						<div>You will be notified when we are live</div>
						<div>
							<form class="form-inline" onsubmit="return ajaxSubmitForm(this,'centerpop');">
								<input type="hidden" name="_template" value="1" />
								<input type="hidden" name="func" value="signup" />
								<input type="email" required="1" class="form-control" name="email" />
								<button type="submit" class="btn btn-primary">Go</button>
							</form>
						</div>
			   		</div>
				</div>
				<div class="col-xs-4">
					<div class="mybox round">
						<h2>Follow us</h2>
						<a href="http://www.facebook.com" class="w_huge"><span class="icon-site-facebook-squared"></span></a>
						<a href="http://www.twitter.com" class="w_huge"><span class="icon-site-twitter-bird"></span></a>
					</div>
				</div>
			</div>
		</div>
	</div>
</view:default>
<view:thankyou>
	<div class="w_centerpop_title"><span class="icon-info"></span> Information Window</div>
	<div class="w_centerpop_content">
		<h3 class="padtop">Thank You Signing up for updates!</h3>
		Please check your email and confirm your request.
		<?=buildOnLoad("document.signup.reset();");?>
	</div>
	<view:_confirm>
		<html>
			<head>
				<style>
					body {
						font-family: serif;
						background:#102932;
						padding:30px;
						font-size:1.2em;
					}
					.roundtops{
						-webkit-border-top-left-radius: 5px;
						-khtml-border-radius-topleft: 5px;
						border-top-left-radius: 5px;
						-webkit-border-top-right-radius: 5px;
						-khtml-border-radius-topright: 5px;
						border-top-right-radius: 5px;
					}
					.roundbottoms{
						-webkit-border-bottom-left-radius: 5px;
						-khtml-border-radius-bottomleft: 5px;
						border-bottom-left-radius: 5px;
						-webkit-border-bottom-right-radius: 5px;
						-khtml-border-radius-bottomright: 5px;
						border-bottom-right-radius: 5px;
					}
					.whiteback{background-color:#ffffff;}
					.blue{color:#58c7fe;}
					.uppercase{text-transform: uppercase;}
					.pad15{padding:15px;}
					.padtop{padding-top:15px;}
					.smalltext{font-size:.6em;}
				</style>
			</head>
			<body>

				<div align="center"><h3 class="blue uppercase">Subscribe Confirmation</h3></div>
				<div class="whiteback pad15 roundbottoms">
					Thank you for subscribing.  Please click on the link below to confirm your request.
					<br><br>
					<a href="http://<?=strtolower($_SERVER['HTTP_HOST']);?>/index/confirm/<?=urlEncode($_REQUEST['email']);?>">http://<?=strtolower($_SERVER['HTTP_HOST']);?>/index/confirm/<?=urlEncode($_REQUEST['email']);?></a>
					<br><br>
					Thanks again!
					<br><br>
					<a href="http://<?=strtolower($_SERVER['HTTP_HOST']);?>">http://<?=strtolower($_SERVER['HTTP_HOST']);?></a>
					<br><br><br><br>
					<div class="smalltext">
						Note: You may unsubscribe at any time with this link: <a href="http://<?=strtolower($_SERVER['HTTP_HOST']);?>/index/unsubscribe/<?=urlEncode($_REQUEST['email']);?>">http://<?=strtolower($_SERVER['HTTP_HOST']);?>/index/unsubscribe/<?=urlEncode($_REQUEST['email']);?></a>
					</div>
				</div>
			</body>
		</html>
	</view:_confirm>
	<?=renderView('_confirm',$rec,$confirm);?>

	<view:_sendmail>
		<?=printValue($_REQUEST);?>
	</view:_sendmail>
	<?=renderView('_sendmail',$rec,$sendopts);?>
</view:thankyou>

<view:signup_error>
	<div class="w_centerpop_title"><span class="icon-info"></span> Information Window</div>
	<div class="w_centerpop_content">
		<h3 class="padtop">Notice!</h3>
		The email you entered in already signed up.
	</div>
</view:signup_error>

<view:confirm>
	<div id="confirm" style="display:none">
		<div class="w_centerpop_title"><span class="icon-info"></span> Information Window</div>
		<div class="w_centerpop_content">
			<h3 class="padtop">Thank You For Confirming!</h3>
			You are all set to receive updates.
		</div>
	</div>
	<?=buildOnLoad("centerpopDiv(getText('confirm'));");?>
</view:confirm>
<view:confirm_error>
	<div id="confirm_error" style="display:none">
		<div class="w_centerpop_title"><span class="icon-info"></span> Information Window</div>
		<div class="w_centerpop_content">
			<h3 class="padtop">Notice!</h3>
			<?=$rec['error'];?>.
		</div>
	</div>
	<?=buildOnLoad("centerpopDiv(getText('confirm_error'));");?>
</view:confirm_error>

<view:unsubscribe>
	<div id="confirm" style="display:none">
		<div class="w_centerpop_title "><span class="icon-info"></span> Information Window</div>
		<div class="w_centerpop_content">
			<h3 class="padtop">Sorry to see you leave!</h3>
			You have been unsubscribed from receiving updates from <?=strtolower($_SERVER['HTTP_HOST']);?>.
		</div>
	</div>
	<?=buildOnLoad("centerpopDiv(getText('confirm'));");?>
</view:unsubscribe>
<view:unsubscribe_error>
	<div id="confirm_error" style="display:none">
		<div class="w_centerpop_title "><span class="icon-info"></span> Information Window</div>
		<div class="w_centerpop_content">
			<h3 class="padtop">Notice!</h3>
			<?=$rec['error'];?>.
		</div>
	</div>
	<?=buildOnLoad("centerpopDiv(getText('confirm_error'));");?>
</view:unsubscribe_error>
