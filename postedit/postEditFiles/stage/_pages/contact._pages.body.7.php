<view:default>
<div class="row">
	<div class="col-sm-11">
		<h1 class="title">Contact Us</h1>
		<p>Please use the form on this page or contact us directly via email.</p>

		<div>Email: <a href="" data-behavior="email-link">/info/skillsai/com</a></div>
		<form name="addedit" class="w_form" method="POST" action="/t/1/contact/send"  id="contact-form" enctype="application/x-www-form-urlencoded" accept-charset="UTF-8" onsubmit="ajaxSubmitForm(this,'centerpop');return false;">
			<input type="hidden" name="setprocessing" value="0">
			<input type="hidden" name="_template" value="1">
			<input type="hidden" name="_table" value="contact_form">
			<input type="hidden" name="_formname" value="addedit">
			<input type="hidden" name="_enctype" value="application/x-www-form-urlencoded">
			<input type="hidden" name="_action" value="ADD">
			<label class="control-label" for="addedit_name">Name</label>
			<input type="text" name="name" id="name" class="form-control"  required autofocus />
			<label class="control-label" for="addedit_email">Email</label>
			<input type="email" name="email" id="email" class="form-control"  required />
			<label class="control-label" for="addedit_subject">Subject</label>
			<input type="text" name="subject" id="sibject" class="form-control"  required />
			<label class="control-label" for="addedit_message">Message</label>
			<textarea id="message" class="form-control" rows="5" name="message" required></textarea>
			<div class="text-right w_padtop"><div id="results" style="display:inline-block;padding-right:25px;" class="w_red w_bold w_big"></div><button type="submit" class="btn btn-primary">Send Message</button></div>
		</form>
	</div>
</div>
</view:default>

<view:thankyou>
	Thank You For Your Message!
	<?=buildOnLoad("document.addedit.reset();");?>
	<view:sendmail>
		<?=nl2br($_REQUEST['message']);?>
	</view:sendmail>
	<?=renderView('sendmail',$rec,$sendopts);?>
</view:thankyou>
