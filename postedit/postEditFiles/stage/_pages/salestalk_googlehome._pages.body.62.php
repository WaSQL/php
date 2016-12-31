<view:register>
<div class="row">
	<div class="col-sm-1"></div>
	<div class="col-sm-10">
		<h1>Welcome to <?=pageValue('title');?></h1>
		<div class="well">
			<h4>Pair your Alexa device with the StoreTalk service.</h4>
			<form method="post" action="/t/1/<?=pageValue('name');?>/register/<?=$logid;?>/check" onsubmit="return ajaxSubmitForm(this,'result');">
				<div class="row">
					<div class="col-sm-8">
						<span class="icon-1"></span> Install the StoreTalk plugin on your website.<br />  
						<span class="icon-2"></span> Once installed enter the pairing key (in settings) below.
					</div>
				</div>
				<div class="row">
					<div class="col-sm-6">
						<label for="url">Website address</label>
						<input type="text" autofocus="true" required="1" class="form-control" name="url" placeholder="your website address" value="" />
					</div>
					<div class="col-sm-2">
						<label for="url">Pairing Key</label>
						<input type="text" class="form-control" required="1" name="pairkey" placeholder="" value="" />
					</div>
				</div>
				<div class="row" id="account">
					<div class="col-sm-3">
						<label for="uname">Skillsai Username</label>
						<input type="text" id="uname" name="username" placeholder="username" class="form-control" />
					</div>
					<div class="col-sm-3">
						<label for="upass">Password</label>
						<input type="password" id="upass" name="password" placeholder="password" class="form-control" />
					</div>
					<div class="col-sm-2">
						<label>No account?</label>
						<a href="#" onclick="return ajaxGet('/t/1/<?=pageValue('name');?>/register/<?=$logid;?>/account','account');" class="btn btn-warning">Create Account</a>
					</div>
				</div>
				<div class="w_padtop" id="result"><button class="btn btn-primary"><span class="icon-arrow-right"></span> Pair Device</button></div>
			</form>
		</div>
	</div>
</div>
</view:register>

<view:pair_success>
	<div><span class="icon-mark w_successback w_round w_white" style="padding:3px"></span><span class="w_success w_bold"> Pairing Successful!</span></div>
	<div>You can now use your Alexa device to access StoreTalk</div>
</view:pair_success>

<view:pair_failed>
	<div><span class="icon-cancel w_danger" style="padding:3px"></span><span class="w_danger w_bold"> Pairing Failed!</span></div>
	<div class="w_padtop">
		Check to make sure your website address and pair key are correct. Then try again.
	</div>
	<div class="w_padtop">
		<button class="btn btn-primary btn"><span class="icon-arrow-right"></span> Try Again</button>
	</div>
</view:pair_failed>

<view:ping_failed>
	<div><span class="icon-cancel w_danger" style="padding:3px"></span><span class="w_danger w_bold"> Plugin Not Found!</span></div>
	<div class="w_padtop">
		Check to make sure your website address is correct and make sure you have installed the StoreTalk plugin on that site.
	</div>
	<div class="w_padtop">
		<button class="btn btn-primary btn"><span class="icon-arrow-right"></span> Try Again</button>
	</div>
</view:ping_failed>

<view:login>
<div class="col-sm-12">
	<div class="row">
		<div class="col-sm-3">
			<label for="uname">Skillsai Username</label>
			<input type="text" id="uname" name="username" placeholder="username" class="form-control" />
		</div>
		<div class="col-sm-3">
			<label for="upass">Password</label>
			<input type="password" id="upass" name="password" placeholder="password" class="form-control" />
		</div>
		<div class="col-sm-2">
			<label>No account?</label>
			<a href="#" onclick="return ajaxGet('/t/1/<?=pageValue('name');?>/register/<?=$logid;?>/account','account');" class="btn btn-warning">Create Account</a>
		</div>
	</div>
</div>
</view:login>

<view:login_failed>
	<div><span class="icon-cancel w_danger" style="padding:3px"></span><span class="w_danger w_bold"> Login Failed!</span></div>
	<div class="w_padtop">
		Check to make sure your username and password are correct. Then try again.
	</div>
	<div class="w_padtop">
		<button class="btn btn-primary btn"><span class="icon-arrow-right"></span> Try Again</button>
	</div>
</view:login_failed>

<view:account>
<div class="col-sm-12">
	<div class="row">
		<div class="col-sm-4">
			<label for="fname">Firstname</label>
			<input type="text" id="fname" name="firstname" placeholder="first name" required="1" class="form-control" />
		</div>
		<div class="col-sm-4">
			<label for="lname">Lastname</label>
			<input type="text" id="lname" name="lastname" placeholder="last name" required="1" class="form-control" />
		</div>
	</div>
	<div class="row">
		<div class="col-sm-6">
			<label for="fname">Email (Your login credentials will be sent to this address)</label>
			<input type="email" id="email" name="email" placeholder="email address" required="1" class="form-control" />
		</div>
		<div class="col-sm-2">
			<label>Have an account?</label>
			<a href="#" onclick="return ajaxGet('/t/1/<?=pageValue('name');?>/register/<?=$logid;?>/login','account');" class="btn btn-warning">Login</a>
		</div>
	</div>
</div>
</view:account>

<view:account_success>
	<div><span class="icon-mark w_successback w_round w_white" style="padding:3px"></span><span class="w_success w_bold"> Pairing Successful!</span></div>
	<div>You can now use your Alexa device to access StoreTalk.  We have also created an account for you and emailed you the login credentials.</div>
	<view:sendmail>
		Thank you for signing up for a Skillsai account.  Here is how you login to your account:
		<ol>
			<li>Go to <a href="http://www.skillsai.com">http://www.skillsai.com</a></li>
			<li>Click on the LOGIN link in the menu (to right)</li>
			<li>Your username is this email address</li>
			<li>Your password is <?=$rec['password'];?></li>
			<li>Click the Sign In button</li>
		</ol>
	</view:sendmail>
	<?=renderView('sendmail',$rec,$sendopts);?>
</view:account_success>

<view:account_failed>
	<div><span class="icon-cancel w_danger" style="padding:3px"></span><span class="w_danger w_bold"> Account Creation Failed!</span></div>
	<div class="w_padtop">
		We are unable to create your account at this time.
	</div>
	<div class="w_padtop">
		<button class="btn btn-primary btn"><span class="icon-arrow-right"></span> Try Again</button>
	</div>
</view:account_failed>

<view:invalid>
<div class="row">
	<div class="col-sm-1"></div>
	<div class="col-sm-10 w_padtop">
		<div class="well">
			<div><span class="icon-warning w_danger" style="padding:3px"></span><span class="w_danger w_bold"> Invalid Registration Request!</span></div>
			<div class="w_padtop">
				We have logged this and will look into the cause.
			</div>
		</div>
	</div>
</div>
</view:invalid>
