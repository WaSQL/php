<view:default>
<div class="row w_padtop">
	<div class="col-sm-2"></div>
	<div class="col-sm-8 text-center">
		<div class="row">
			<div class="col-sm-3 text-center">
				<div class="well tealback <?=pageNavClass('faq');?>">
					<view:boxes><div class="w_padtop" align="center"><a href="/support/faq"><img src="/images/site/faq.png" alt="FAQ" width="128" class="img img-responsive" /></a></div></view:boxes>
					<h5 class="w_white"><a href="/support/faq" class="w_white">FAQ</a></h5>
				</div>
			</div>
			<div class="col-sm-3 text-center">
				<div class="well tealback <?=pageNavClass('feedback');?>">
					<view:boxes><div class="w_padtop" align="center"><a href="/support/feedback"><img src="/images/site/feedback.png" alt="feedback" width="128" class="img img-responsive" /></a></div></view:boxes>
					<h5 class="w_white"><a href="/support/feedback" class="w_white">Feedback</a></h5>
				</div>
			</div>
			<div class="col-sm-3 text-center">
				<div class="well tealback <?=pageNavClass('issues');?>">
					<view:boxes><div class="w_padtop" align="center"><a href="/support/issues"><img src="/images/site/report_issue.png" alt="Report Issues" width="128" class="img img-responsive" /></a></div></view:boxes>
					<h5 class="w_white"><a href="/support/issues" class="w_white">Report Issues</a></h5>
				</div>
			</div>

			<div class="col-sm-3 text-center">
				<div class="well tealback <?=pageNavClass('contact');?>">
					<view:boxes><div class="w_padtop" align="center"><a href="/support/contact"><img src="/images/site/contact_us.png" alt="Contact Us" width="128" class="img img-responsive" /></a></div></view:boxes>
					<h5 class="w_white"><a href="/support/contact" class="w_white">Contact Us</a></h5>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="row w_padtop">
	<div class="col-sm-2"></div>
	<div class="col-sm-8" id="about_content">
		<?=renderView($viewname);?>
	</div>
</div>
</view:default>

<view:faq>
	<? $faq_categories=pageGetFAQGroups();?>
	<div class="row">
		<div class="col-sm-6 col-sm-offset-3 text-center">
			<input type="text" class="form-control input-lg input-round" placeholder="search FAQ" autocomplete="off" onkeyup="faqSearchList(this.value);" />
		</div>
	</div>
	<div class="row w_padtop">
		<div class="col-sm-8">
			<view:faq_category>
			<div class="faq_category">
				<div class="faq_category_title w_pointer" onclick="faqSearchList('_<?=$key;?>');"><?=$key;?></div>
				<div class="faq_subcategory">
					<view:faq_subcategory>
						<div class="faq_subcategory_title w_pointer" onclick="faqSearchList('_<?=$key;?>');"><?=$key;?></div>
						<view:question>
							<div class="faq_qna" data-category="<?=$rec['category'];?>" data-subcategory="<?=$rec['subcategory'];?>">
								<div class="question"><?=$rec['question'];?></div>
								<div class="answer"><?=$rec['answer'];?></div>
							</div>
						</view:question>
						<?=renderEach('question',$questions,'rec');?>
					</view:faq_subcategory>
					<?=renderEach('faq_subcategory',$subcatetories,'questions');?>
				</div>
			</div>
			</view:faq_category>
			<?=renderEach('faq_category',$faq_categories,'subcatetories');?>
		</div>
		<div class="col-sm-4">
			If you cannot find an answer in our FAQ then ask:
			<?=renderView('contactform');?>
		</div>
	</div>
</view:faq>

<view:faq_questions>
	<view:faq_question>
		<div><?=$rec['question'];?></div>
	</view:faq_question>
	<?=renderEach('faq_question',$recs,'rec');?>
</view:faq_questions>

<view:feedback>
	<div class="row w_padtop">
		<div class="col-sm-2"></div>
		<div class="col-sm-8">
			<h1>Feedback</h1>
			<div class="well" style="min-height:300px;">
				<h5 class="orange text-center">Want something new?</h5>
				<form method="post" name="suggestform" action="/t/1/sidebar/suggest" onsubmit="return ajaxSubmitForm(this,'suggest');">
						<input type="email" class="form-control" placeholder="email address" name="email" required />
						<textarea class="form-control" style="margin-top:5px;" placeholder="your suggestion for a new skill" name="suggestion" required></textarea>
						<div class="text-right"><button type="submit" style="margin-top:5px;" class="btn btn-primary">Suggest</button></div>
				</form>
				<div id="suggest"></div>
				<div class="text w_pad">
					What do you wish your Amazon Echo&trade; could do for you? Suggest a new product idea and get entered into a drawing to win a free echo!
				</div>
			</div>
		</div>
	</div>

</view:feedback>

<view:issues>
	<div class="row w_padtop">
		<div class="col-sm-2"></div>
		<div class="col-sm-8">
			<div class="well" style="min-height:300px;">
				<div class="w_right w_danger w_padtop w_pointer w_link" onclick="pageNewTicket();"><span class="icon-ticket w_danger"></span> new ticket</div>
				<h1>Product Support</h1>
				<form method="post" action="/t/1/issues/search" onsubmit="return ajaxSubmitForm(this,'results');">
					<div class="input-group">
						<input type="text" name="search" placeholder="ticket number or email" class="form-control" required autofocus />
						<span class="input-group-btn"><button type="submit" class="btn btn-primary"><span class="icon-search"></span> Lookup Ticket(s)</button></span>
					</div>
				</form>
				<div id="results" class="w_pad">
					Welcome to the Skillsai support page.  If you are having an issue with any of of products/skills please submit a ticket.
					You can come back to this page to lookup any tickets you have submitted to get a status of the ticket.
					<div><?=$_REQUEST['func'];?></div>
				</div>
			</div>
		</div>
	</div>
</view:issues>

<view:contact>
	<div class="row">
		<div class="col-sm-11">
			<h1 class="title">Contact Us</h1>
			<p>Please use the form on this page or contact us directly via email.</p>
	
			<div>Email: <a href="" data-behavior="email-link">/info/skillsai/com</a></div>
			<?=renderView('contactform');?>
		</div>
	</div>
</view:contact>
<view:contactform>
	<form name="addedit" class="w_form" method="POST" action="/t/1/support/send"  id="contact-form" enctype="application/x-www-form-urlencoded" accept-charset="UTF-8" onsubmit="ajaxSubmitForm(this,'centerpop');return false;">
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
</view:contactform>

<view:addedit>
	<?=pageAddEdit($id);?>
</view:addedit>

<view:search_results>
	<?=count($tickets);?> results found
	<table class="table table-striped table-hover table-bordered">
		<tr>
			<th>Date</th>
			<th>Status</th>
			<th>Issue</th>
			<th>Resolution</th>
		</tr>
		<view:ticket>
		<tr>
			<td class="text"><?=date('m/d/Y',strtotime($ticket['_cdate']));?></td>
			<td class="text"><?=$ticket['status_ex'];?></td>
			<td class="text"><?=$ticket['description'];?></td>
			<td class="text"><?=$ticket['resolution'];?></td>
		</tr>
		</view:ticket>
		<?=renderEach('ticket',$tickets,'ticket');?>
	</table>
</view:search_results>

<view:thankyou>
	<h1>Thank you for letting us know!</h1>
	<h5>We will get to work on a resolution as soon as possible</h3>
</view:thankyou>

<view:thankyou2>
	Thank You For Your Message!
	<?=buildOnLoad("document.addedit.reset();");?>
	<view:sendmail>
		<?=nl2br($_REQUEST['message']);?>
	</view:sendmail>
	<?=renderView('sendmail',$rec,$sendopts);?>
</view:thankyou2>
