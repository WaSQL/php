<div class="row" style="min-height:60px !important;">
	<div class="col-sm-12 text-center">
    	<div class="navbar-header w_nowrap">
        	<button type="button" class="navbar-toggle collapsed" style="padding:0px;border:0px;" data-toggle="collapse" data-target="#topnavbar" aria-expanded="false" aria-controls="topnavbar">
            	<span class="icon-menu blue" style="font-size:20px;"></span>
          	</button>
          	<div>
          	<a href="/" class="hidden-lg hidden-md" onclick="window.location='/';return false;">
				<img src="/images/site/SkillsaiLogoTagline.png" class="img img-responsive" style="max-height:30px;margin-top:5px;" height="30" alt="skillsai logo" />
			</a>
			</div>

    	</div>
        <div id="topnavbar" class="navbar-collapse collapse">
        	<a href="/" class="w_left hidden-xs hidden-sm" onclick="window.location='/';return false;">
				<img src="/images/site/SkillsaiLogoTagline.png" class="img img-responsive" style="max-height:50px;margin-bottom:15px;" height="50" alt="skillsai logo" />
			</a>
        	<ul class="nav navbar-nav navbar-right">
		        <li class="<?=commonPageActive('products');?>"><a class="blue w_big w_bold" href="/products/"> Products</a></li>
		        <li class="<?=commonPageActive('pricing');?>"><a class="blue w_big w_bold" href="/pricing/"> Pricing</a></li>
		        <li class="<?=commonPageActive('support');?>"><a class="blue w_big w_bold" href="/support/"> Support</a></li>
		        <!-- <li class="<?=commonPageActive('soundbytes');?>"><a class="blue w_big w_bold" href="/soundbytes/"> Blog</a></li> -->
		        <li class="<?=commonPageActive('about');?>"><a class="blue w_big w_bold" href="/about/"> About</a></li>
		        <view:login>
					<li class="<?=commonPageActive('login');?>"><a class="blue w_big w_bold" href="/login/"> Login/Register</a></li>
				</view:login>
		        <view:account>
					<li class="<?=commonPageActive('account');?>"><a class="orange w_big w_bold" href="/account/"> Account</a></li>
					<li class="<?=commonPageActive('login');?>"><a class="orange w_big w_bold" href="/login?_logout=1/"> Logoff</a></li>
				</view:account>
				<?=renderViewIfElse(isUser(),'account','login');?>
            </ul>
        </div><!--/.nav-collapse -->
    </div>
</div>
