<view:default>
<div class="row w_padtop default" data-pre="<?=isDBStage()?'stage':'www';?>">
	<div class="col-sm-2"></div>
	<div class="col-sm-8 text-center" >
		<div class="row">
			<div class="col-sm-6" style="padding-right:1px;">
			<?=renderView('login');?>
			</div>
			<div class="col-sm-1 text-center hidden-sm hidden-xs vertical-center" style="min-height:475px;">
				<img src="/images/site/vertical_or.png" onload="amazonInit();" alt="or" style="display:block;margin:auto;"  />
				<div id="amazon-root"></div>
				<view:isuser>
					<?=buildOnLoad("window.location='{$_SESSION['redirect']}';");?>
				</view:isuser>
				<?=renderViewIf(isUser(),'isuser');?>
			</div>
			<div class="col-sm-5" style="padding-left:0px;padding-right:0px;">
				<div class="well" style="min-height:475px;">
					<h4 class="text-left w_grey" id="formtitle">Don't have an account? Register!</h4>
					<div class="w_padtop">
						<a href="#" onclick="return ajaxGet('/t/1/login/register','centerpop');" class="btn btn-info btn-lg" style="width:100%;font-size:1.3em;"><span class="icon-mail" style="font-size:1.2em;float:left;padding:2px 4px 4px 4px;border-radius:3px;background:#FFF;color:#000;"></span> Sign up with Email</a>
					</div>
					<div class="w_padtop">
						<a href="#" id="amazon-register" class="btn btn-warning btn-lg" style="width:100%;font-size:1.3em;"><span class="icon-site-amazon" style="font-size:1.2em;float:left;padding:4px 3px 3px 4px;border-radius:3px;background:#FFF;color:#000;"></span> Sign up with Amazon</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</view:default>

<view:register>
<div class="w_centerpop_title">&nbsp;</div>
<div class="w_centerpop_content" style="min-width:400px;padding:0 40px 0 40px;">
	<h4 class="text-center" id="formtitle">Register via email</h4>
	<form id="loginform" name="loginform" method="POST" action="/login" onsubmit="return submitForm(this);">
		<input type="hidden" name="func" value="register">
		<label for="loginform_username">Username/Email Address</label>
		<input type="text" value="" id="loginform_username" autofocus="true" name="username" class="form-control input-lg" tabindex="1" _required="1" displayname="username" placeholder="username or email address" maxlength="255" />
		<div class="w_padtop">
			<label for="loginform_password">Password</label>
			<input type="password" value="" id="loginform_password" name="password" class="form-control input-lg" onfocus="this.select();" tabindex="2" _required="1" displayname="Password" placeholder="password" maxlength="25" />
		</div>
		<div class="w_padtop">
			<label for="loginform_password_repeat">Repeat Password</label>
			<input type="password" value="" id="loginform_password_repeat" name="password_repeat" class="form-control input-lg" onfocus="this.select();" tabindex="2" _required="1" displayname="Password" placeholder="repeat password" maxlength="25" />
		</div>
		<div class="w_padtop">
			<label for="registerform_name">Name</label>
			<input type="text" value="" id="registerform_name" name="name" class="form-control input-lg" tabindex="10" placeholder="your name" maxlength="255" />
			<label for="registerform_postal">Postal Code</label>
			<input type="text" value="" id="registerform_postal" name="postal_code" class="form-control input-lg" tabindex="12" placeholder="Postal/Zip code" maxlength="25" />
		</div>
		<div class="w_padtop text-right">
			<button type="submit" class="btn btn-info btn-lg" style="width:100%">Sign up</button>
		</div>
	</form>
</div>
</view:register>

<view:login>
<div class="well" style="min-height:475px;">
	<h4 class="text-left w_grey" id="formtitle">Log Into Your Account</h4>
	<form id="loginform" name="loginform" method="POST" action="/login/email" onsubmit="return submitForm(this);">
		<input type="hidden" name="func" value="login">
		<label for="loginform_username">Username/Email Address</label>
		<input type="text" value="" id="loginform_username" autofocus="true" name="username" class="form-control input-lg" tabindex="1" _required="1" displayname="username" placeholder="username or email address" maxlength="255" />
		<div class="w_padtop">
			<label for="loginform_password">Password</label>
			<input type="password" value="" id="loginform_password" name="password" class="form-control input-lg" onfocus="this.select();" tabindex="2" _required="1" displayname="Password" placeholder="password" maxlength="25" />
		</div>
		<div class="w_padtop text-right">
			<button type="submit" class="btn btn-info btn-lg" style="width:100%;"><span class="icon-mail" style="font-size:1.2em;float:left;padding:2px 4px 4px 4px;border-radius:3px;background:#FFF;color:#000;"></span> Log in With Email</button>
		</div>
		<div class="row w_padtop">
			<div class="col-sm-6 text-left">
				<input id="remember_me" style="display:none;" data-type="checkbox" name="register" value="1" type="checkbox" checked />
				<label class="icon-mark" for="remember_me"></label><label for="remember_me" class="w_grey" style="margin-left:10px;border:0px;font-weight:100;"> Remember Me</label>
			</div>
			<div class="col-sm-6 text-right">
				<a href="#" class="w_grey" onclick="remindMeForm(document.loginform.username.value);return false;">Forgot Password?</a>
			</div>
		</div>
	</form>
	<div class="hline text-center"><span>or</span></div>
	<div class="w_padtop">
		<a href="#" id="amazon-login" class="btn btn-warning btn-lg" style="width:100%;font-size:1.3em;"><span class="icon-site-amazon" style="font-size:1.2em;float:left;padding:4px 3px 3px 4px;border-radius:3px;background:#FFF;color:#000;"></span> Log in with Amazon</a>
	</div>
</div>
</view:login>
