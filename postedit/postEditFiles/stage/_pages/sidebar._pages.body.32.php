<view:default>
<div class="row sidebar">
	<div class="col-sm-12">
		<h5 class="orange text-center"><a class="orange" href="/blog">Subscribe to Sound Bytes</a></h5>
		<form method="post" name="subscribeform" action="/t/1/sidebar/subscribe" onsubmit="return ajaxSubmitForm(this,'subscribe');">
			<div class="input-group">
				<input type="email" class="form-control" placeholder="email address" name="email" required />
				<span class="input-group-btn"><button type="submit" class="btn btn-warning">Subscribe</button></span>
			</div>
		</form>
		<div id="subscribe"></div>
		<div class="text w_pad">
			<a class="orange" href="/blog">Sound Bytes</a> is where you'll find posts on a wide variety of topics. From news and info on the Amazon Echo&trade; to other areas we're interested in including Small Business, Education, Technology and Artificial Intelligence, Sound Bytes is an eclectic mix of content. We'll discuss our latest product releases, share our technical and business expertise, and tons of other cool stuff like our monthly contest to win your own Amazon Echo! We hope you'll subscribe and share your thoughts with us as well.
		</div>
		<hr>
		<h5 class="orange text-center">Don't own an Echo?</h5>
		<div class="text w_pad">
			The greatest thing since sliced bread, we love our Amazon Echo&trade;, Tap&trade; and Dot&trade;.
			If you're ready to get your own virtual assistant, we'd really appreciate you buying yours through our affiliate links below.
			It's a great way to help us continue to create the best skills in the market!
		</div>
		<div class="text-center">
			<img src="/images/site/amazon-echo-tap-dot.png" alt="Amazon Echo, Tap, Dot" class="img-responsive" usemap="#Amazon_Devices" />
			<map name="Amazon_Devices" id="Amazon_Devices">
			    <area alt="Amazon Echo" title="Echo" target="amazon" href="//www.amazon.com/Amazon-SK705DI-Echo/dp/B00X4WHP5E/ref=as_sl_pc_qf_sp_asin_til?tag=wwwskillsaico-20&linkCode=w00&linkId=EIA6K6DYCWYCJKR5&creativeASIN=B00X4WHP5E" shape="poly" coords="47,15,46,224,122,224,121,16" />
			    <area alt="Amazon Tap" title="Tap" target="amazon" href="//www.amazon.com/gp/product/B00VXS8E8S/ref=as_li_tl?ie=UTF8&camp=1789&creative=9325&creativeASIN=B00VXS8E8S&linkCode=as2&tag=wwwskillsaico-20&linkId=RS6O4572KNZ4FNEL" shape="poly" coords="161,55,161,226,229,227,231,56" />
			    <area alt="Amazon Dot" title="Dot" target="amazon" href="//www.amazon.com/Amazon-S04WQR-Echo-Dot/dp/B00VKTZFB4/ref=as_sl_pc_tf_til?tag=wwwskillsaico-20&linkCode=w00&linkId=KZ7PLDAO6UBFHSWM&creativeASIN=B00VKTZFB4" shape="poly" coords="273,175,273,227,347,225,347,177" />
			</map>
		</div>
	</div>
</div>
</view:default>

<view:invalid_email>
	<div class="w_red"><span class="icon-warning"></span> Invalid Email Address</div>
</view:invalid_email>

<view:already_subscribed>
	<div class="blue"><span class="icon-info-circled blue"></span> Your are already subscribed</div>
</view:already_subscribed>

<view:subscription_success>
	<div class="w_success"><span class="icon-mark w_success"></span> Subscription Successful!</div>
	<?=buildOnLoad("document.subscribeform.reset();");?>
</view:subscription_success>

<view:suggest_success>
	<div class="w_success"><span class="icon-mark w_success"></span> Thank you so much!</div>
	<?=buildOnLoad("document.suggestform.reset();");?>
</view:suggest_success>
