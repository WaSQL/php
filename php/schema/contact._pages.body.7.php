<view:default>
<div id="contact-details">
	<h3 class="title">Get In Touch</h3>
	<div class="post">
		<p>Please use the form on this page or contact us directly via email.</p>
	</div>

    <h3>Contact Details</h3>
    <h4>Email: <span>info@yourdomain.com</span></h4>
</div><!--end contact-details-->

<div id="contact-form-container">
	<?=pageContactForm();?>
</div>
</view:default>
<view:thankyou>
	<div class="w_centerpop_content">
		<h3>Thank You For Your Message!</h3>
		<?=buildOnLoad("document.addedit.reset();");?>
	</div>
</view:thankyou>
