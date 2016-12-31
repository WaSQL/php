<view:default>
<div class="row w_padtop">
	<div class="col-sm-2"></div>                        
	<div class="col-sm-8 text-center">
		<div class="row">
			<div class="col-sm-3 text-center">
				<div class="well lightblueback <?=pageNavClass('products');?>">
					<view:boxes><div class="w_padtop"><a href="/products/salestalk"><img src="/images/site/bi2.png" alt="Products" width="225" class="img img-responsive img-thumbnail" /></a></div></view:boxes>
					<h5 class="w_white"><a href="/products/salestalk" class="w_white">Products</a></h5>
				</div>
			</div>
			<div class="col-sm-3 text-center">
				<div class="well lightblueback <?=pageNavClass('platforms');?>">
					<view:boxes><div class="w_padtop" align="center"><a href="/products/platforms"><img src="/images/site/supportedplatforms.png" alt="supported platforms" width="225" class="img img-responsive img-thumbnail" /></a></div></view:boxes>
					<h5 class="w_white"><a href="/products/platforms" class="w_white">Supported Platforms</a></h5>
				</div>
			</div>
			<div class="col-sm-3 text-center">
				<div class="well lightblueback <?=pageNavClass('releases');?>">
					<view:boxes><div class="w_padtop" align="center"><a href="/products/releases"><img src="/images/site/releasenotes.png" alt="Release Notes" width="225" class="img img-responsive img-thumbnail" /></a></div></view:boxes>
					<h5 class="w_white"><a href="/products/releases" class="w_white">Release Notes</a></h5>
				</div>
			</div>

			<div class="col-sm-3 text-center">
				<div class="well lightblueback <?=pageNavClass('fun');?>">
					<view:boxes><div class="w_padtop" align="center"><a href="/products/fun"><img src="/images/site/play.png" alt="Fun stuff" width="225" class="img img-responsive img-thumbnail" /></a></div></view:boxes>
					<h5 class="w_white"><a href="/products/fun" class="w_white">Fun Stuff</a></h5>
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

<view:salestalk>
	<div class="row w_padtop">
		<div class="col-sm-2"></div>
		<div class="col-sm-8">
			<div class="row">
				<div class="col-sm-12">
					<h2>SalesTalk</h2>
					<p>
						The Internet of Things (IoT) isn't just for your home or car anymore.  
						Now you can extend IoT access to your business, with audible and visual Business Intelligence products from Skillsai.  
						Currently limited to invited participants only, please let us know if you are interested in joining our beta program.
						Selected users who meet the project and environment requirements (using WooCommerce or the Stripe Payment Gateway) will receive a free 2-year subscription to SalesTalk.
						<div class="text-right" style="display:none;"><a href="#" class="btn btn-default lightblueback btn-lg">Invite Me!</a></div>
					</p>
					<h4>Product Features</h4>
					<p>
						Ask any business person.
						There is always more to do then there are hours in the day to get work done. 
						Often you're so busy doing your business, you don't really know how your business is doing. Are you tracking your daily WooCommerce store sales? 
						Then you're aware of the time it takes to log in, and click through to get to the data.
						Perhaps you track your sales in Excel and create your own charts - a process that takes even more time away from what you do best!
					</p><p>
						With Salestalk, all you need to do is register and login to <a href="account">connect your datasource</a> to get instant insight into the data critical to your business. 
						This efficient, hands-free solution is based on the way we human beings function best - with our voices!
					</p>
					<h4>Key Elements</h4>
					<p>
						SalesTalk is a secure cloud solution that stores your data in a scalable AWS server. 
						Currently, we've integrated with the Amazon Voice Service (AVS) to process your business questions and hand those queries to the SalesTalk controller. 
						It retrieves the result from our datastore and returns those results back to you from your Amazon or Google voice-enabled device.<br>  
						<br>						
						The key elements of our solution include:
					</p>
					<div class="row">
						<div class="col-xs-1" style="padding:0 0 30px 0;"><img src="/images/site/featurecircle-datasource.png" class="img img-responsive" alt="" /></div>
						<div class="col-xs-5">
							We link directly to your business account on platforms like WooCommerce or Payment Gateways such as Stripe using Oauth 2.0.
							All you need to do after registering with Salestalk is to click the button for your data source and login to that account. 
							
						</div>
						<div class="col-xs-1" style="padding:0 0 30px 0;"><img src="/images/site/featurecircle-datawarehouse.png" class="img img-responsive" alt="" /></div>
						<div class="col-xs-5">
							With a data store that is separate from your day-to-day online transaction processing, it's optimized for queries by business needs rather than operational or computer processes.  
							As we add support for new data sources, you'll get additional insights from the aggregated data.
		
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-1" style="padding:0 0 30px 0;"><img src="/images/site/featurecircle-etl.png" class="img img-responsive" alt="" /></div>
				<div class="col-xs-5">
					We utilze published APIs, SDKs and Webhooks from your platform's provider to extract the available sales data. 
					We then transform the relevant information to match our voice-optimized schema and store it.
				</div>
				<div class="col-xs-1" style="padding:0 0 30px 0;"><img src="/images/site/featurecircle-audiblereporting.png" class="img img-responsive" alt="" /></div>
				<div class="col-xs-5">
					Using the Amazon Voice Service or similar VUI services, we capture your business question, query the data store for the answer and return the reponse in seconds-all in a conversational interaction that is the natural way humans communicate!
				</div>
			</div>
			<div class="row">
				<div class="col-xs-1" style="padding:0 0 30px 0;"><img src="/images/site/featurecircle-aws.png" class="img img-responsive" alt="" /></div>
				<div class="col-xs-5">
					Utilizing Amazon Web Services, your data is stored securely in the Cloud. 
					You benefit from the industry-leading platform that expands on demand which provides massive economies of scale.  
					That, in turn, allows us to contain costs and offer a robust Business Intelligence solution that is highly cost-effective.

				</div>
				<div class="col-xs-1" style="padding:0 0 30px 0;"><img src="/images/site/featurecircle-dashboard.png" class="img img-responsive" alt="" /></div>
				<div class="col-xs-5">
					Real time questions and answers are highly efficient and require no context switch to implement. 
					But for those times when you can spend dedicated brain cylcles on analysis, a visual interface is best.
					Customize your reports to uniquely fit your business market.

				</div>
			</div>
			<div class="row">
				<div class="col-xs-3"></div>
				<div class="col-xs-1" style="padding:0 0 30px 0;"><img src="/images/site/featurecircle-decisionmaking.png" class="img img-responsive" alt="" /></div>
				<div class="col-xs-5">
					With both audible and visual Business Intelligence, you can take immediate action to address any issues you uncover and enhance your sales with the insights SalesTalk provides.
				</div>
			</div>
			<h4>What Kind of Questions Can I Ask?</h4>
			<p>
				Depending on the <a href="/pricing">Report Packages</a> purchased, you can ask Alexa things like:
			</p><p>
				What were my sales yesterday?<br />
				How many units of camping stoves did I sell last month?<br />
				What day of the week is my best sales day?<br />
				What are my top selling locations?<br />
			</p><p>
			Just try asking a logical question, and Alexa will let you know if she can answer it. 
			And if she can't, please submit your phrase request to our <a href="/support/feedback">Feedback</a> page, and we will do our best to help her learn it.
			</p>
			<h4>How to Get Started</h4>
			<p>
			It's fast and easy to get started with no install necessary. 
			Just sign up for an <a href="/account">account</a>, and ask any <a href="/products/platforms">Alexa-enabled device</a> to "Open SalesTalk."
			You will immediately see the kinds of reports and configuration available for all four report packages in the Dashboard, using our demo camping supply store.  
			Ask Alexa questions, and she'll respond with answers based on our demo store's sales. 
			Stay in test mode as long as you like!
			</p><p>
			When you're ready to start getting insights into your own sales data, just choose your <a href="/pricing">Pricing Plan</a>.
			Connect your business platform datasource in your Dashboard. 
			Then you'll be able to ask Alexa a wide breadth of important questions. 
			Get real-time answers to your company's critical queries without the need to switch context away from your current task.
			</p><p>
			Do you have additional questions? 
			Check out the <a href="/support/faq">product FAQ</a>  or contact our <a href="/support/contact">sales team</a> today!
		</div>
	</div>
</view:salestalk>

<view:platforms>
	<div class="row w_padtop">
		<div class="col-sm-2"></div>
		<div class="col-sm-8">
			<div class="row"><div class="col-sm-12"><h2>Amazon Alexa-enabled Devices</h2></div></div>
			<div class="row border">
				<div class="col-sm-3 text-center" style="padding:0px;">
					<div><a target="afflink" href="https://www.amazon.com/gp/product/B00X4WHP5E/ref=as_li_qf_sp_asin_il_tl?ie=UTF8&tag=wwwskillsaico-20&camp=1789&creative=9325&linkCode=as2&creativeASIN=B00X4WHP5E&linkId=bd8e9d7720334413a0c957afeb6e3f36">
						<img src="/images/site/sp-amazon.png" alt="" class="img img-responsive" />
						<caption>Amazon Echo&trade; Tap&trade; and Dot&trade;</caption>
					</a></div>
				</div>
				<div class="col-sm-3 text-center" style="padding:0px;">
					<div><a target="afflink" href="https://www.amazon.com/gp/product/B00ZV9RDKK/ref=as_li_qf_sp_asin_il_tl?ie=UTF8&tag=wwwskillsaico-20&camp=1789&creative=9325&linkCode=as2&creativeASIN=B00ZV9RDKK&linkId=fd7cd1235dea4d1c7745494a05f37508">
						<img src="/images/site/sp-firestick.png" alt="" class="img img-responsive" />
						<caption>Amazon Firestick&trade;</caption>
					</a></div>
				</div>
				<div class="col-sm-3 text-center" style="padding:0px;">
					<div><a target="afflink" href="https://www.amazon.com/gp/product/B00U3FPN4U/ref=as_li_qf_sp_asin_il_tl?ie=UTF8&tag=wwwskillsaico-20&camp=1789&creative=9325&linkCode=as2&creativeASIN=B00U3FPN4U&linkId=c002b10fad41c902b436454b820f4445">
						<img src="/images/site/sp-firetv.png" alt="" class="img img-responsive" />
						<caption>Amazon Fire TV&trade;</caption>
					</a></div>
				</div>
				<div class="col-sm-3 text-center" style="padding:0px;">
					<div><a target="afflink" href="#" onclick="alert('Coming Nov 2016');return false;">
						<img src="/images/site/sp-jam.png" alt="" class="img img-responsive" />
						<caption>Jam Audio Voice&trade;</caption>
					</a></div>
				</div>
			</div>
			<div class="row border">
				<div class="col-sm-3 text-center" style="padding:0px;">
					<div><a target="afflink" href="#" onclick="alert('Coming Jan 2017');return false;">
						<img src="/images/site/sp-pebblecore.png" alt="" class="img img-responsive" />
						<caption>Pebble Core&trade;</caption>
					</a></div>
				</div>
				<div class="col-sm-3 text-center" style="padding:0px;">
					<div><a target="afflink" href="#" onclick="alert('Coming Nov 2016');return false;">
						<img src="/images/site/sp-omaterise.png" alt="" class="img img-responsive" />
						<caption>Omate Rise&trade;</caption>
					</a></div>
				</div>
				<div class="col-sm-3 text-center" style="padding:0px;">
					<div><a target="afflink" href="https://www.amazon.com/gp/product/B01H5YX9HO/ref=as_li_qf_sp_asin_il_tl?ie=UTF8&tag=wwwskillsaico-20&camp=1789&creative=9325&linkCode=as2&creativeASIN=B01H5YX9HO&linkId=385bb5964835a3893c4b63387f94487d">
						<img src="/images/site/sp-cowatch.png" alt="" class="img img-responsive" />
						<caption>iMCO CoWatch&trade;</caption>
					</a></div>
				</div>
				<div class="col-sm-3 text-center" style="padding:0px;">
					<div><a target="afflink" href="https://www.amazon.com/gp/product/B017WL4N84/ref=as_li_qf_sp_asin_il_tl?ie=UTF8&tag=wwwskillsaico-20&camp=1789&creative=9325&linkCode=as2&creativeASIN=B017WL4N84&linkId=8a3b72442dbe6afd3e83034e59e807f7">
						<img src="/images/site/sp-triby.png" alt="" class="img img-responsive" />
						<caption>Inoxia Traby&trade;</caption>
					</a></div>
				</div>
			</div>
			<div class="row"><div class="col-sm-12"><h2>eCommerce Platforms</h2></div></div>
			<div class="row border">
				<div class="col-sm-3 text-center" style="padding:0px;">
					<div><a target="afflink" href="">
						<img src="/images/site/sp-woocommerce.png" alt="" class="img img-responsive" />
					</a></div>
				</div>
			</div>
			<div class="row"><div class="col-sm-12"><h2>Payment Gateways</h2></div></div>
			<div class="row border">
				<div class="col-sm-3 text-center" style="padding:0px;">
					<div><a target="afflink" href="">
						<img src="/images/site/sp-stripe.png" alt="" class="img img-responsive" />
					</a></div>
				</div>
			</div>
		</div>
	</div>
</view:platforms>

<view:fun>
<div class="row w_padtop">
	<div class="col-sm-2"></div>
	<div class="col-sm-8">
		<div class="row">
			<div class="col-sm-6">
				<div class="w_big well" style="padding-top:0px;">
					<div class="w_right w_padtop">
						<img onload="pageDrawChart();" src="/skills/hangman/hangman_logo.png" alt="hangman" width="108" height="108" class="img img-responsive img-thumbnail" />
					</div>
					<h3 style="margin-bottom:0px;">Hangman</h3>
					<div class="w_small w_grey">Submitted - April 16, 2016</div>
					<div class="w_small w_grey">Live - April 29, 2016</div>
					<div class="w_pad text">
						The old school spelling game played with paper and pencil has moved into the 21st century with our new hands-free audio version.
						Using the Amazon Echo&trade; family of devices, just ask Alexa to "Start Hangman" and prepare to be educated and entertained.
						<div><a href="#" onclick="return showHide('more_hangman');" class="w_link w_grey">Read more...</a></div>
						<div class="w_padtop"></div>
						<div class="text-right"><a class="btn btn-default btn-sm" target="alexa" href="//alexa.amazon.com/spa/index.html#skills/dp/B01ED658W6/?ref=skill_dsk_skb_sr_1"><span class="icon-mic w_success"></span> Enable this skill</a></div>
					</div>
				</div>
			</div>
			<div class="col-sm-6">
				<h1 class="text-center w_bold">S K I _ _ S A I</h1>
				<div class="w_bold w_big w_padtop">Hangman Activity (last 30 days)</div>
				<canvas id="hangman_canvas"></canvas>
				<div id="hangman_data" style="display:none"><?=commonGetSkillData('hangman');?></div>
				<div id="hangman_debug"></div>
				<div class="text-left w_padtop">
					Help us make Hangman the highest rated skill in the Alexa app store.
					Submit your review <a target="alexa" href="//alexa.amazon.com/spa/index.html#skills/dp/B01ED658W6/create-review/?ref-suffix=war_dp">here!</a>
				</div>
			</div>
		</div>
		<div class="row" id="more_hangman" style="display:none;">
			<div class="col-sm-12">
				<h1>Hangman</h1>
				<p>First mentioned in a book by Alice Bertha Gromme in 1894, no one knows exactly where the game of Hangman originated.  But this old-school spelling game played with paper and pencil has moved into the 21st century with our new hands-free audio version.
					Using any of the Amazon Echo&trade; family of devices, just ask Alexa to "Start Hangman" and prepare to be educated and entertained.
				</p>
				<p>First, she'll ask whether you would like to choose the word length (from 3 to 31 letters) or to pick from a category of words. 
				</p>
				<p>The categories you can select from include:
				</p>
					
				<p>
					<span class="w_bold">Parts of Speech:</span> Adjectives, Adverbs, Nouns or Verbs<br />
					<span class="w_bold">The Natural World:</span> Anatomy, Fruits and Vegetables, Periodic Elements, Trees or Wildlife<br />
					<span class="w_bold">The Kitchen Sink:</span> Colors, Cooking Terms, Musical Instruments, Occupations, or Transportation
				</p>
				<p>Alexa will help you keep track of your guesses along the way. If you forget and ask for a letter you've already guessed, she'll let you know and it won't count as a miss. You get 6 misses total. 
					If you think you know the secret word, you can guess it anytime Alexa asks you to pick a letter. If you need help visualizing the word, you can always play along with the Alexa app on your phone or computer at
					<a href="http://alexa.amazon.com/" target="alexa">http://alexa.amazon.com/</a> where the word you're playing is listed along with the guesses you've made and how many tries you have left.
				</p>
				<p> Hangman uses The WordNet database under the following Copyright:
					<p class="w_smaller w_padleft">
					This software and database is being provided to you, the LICENSEE, by
					Princeton University under the following license.  By obtaining, using  
					and/or copying this software and database, you agree that you have  
					read, understood, and will comply with these terms and conditions.:
					</p>
				  	<p class="w_smaller w_padleft">
					Permission to use, copy, modify and distribute this software and
					database and its documentation for any purpose and without fee or
					royalty is hereby granted, provided that you agree to comply with  
					the following copyright notice and statements, including the disclaimer,  
					and that the same appear on ALL copies of the software, database and  
					documentation, including modifications that you make for internal  
					use or for distribution.  
				  	<p class="w_smaller w_padleft">
					WordNet 1.6 Copyright 1997 by Princeton University.  All rights reserved.  
					</p>
				  	<p class="w_smaller w_padleft">
					THIS SOFTWARE AND DATABASE IS PROVIDED "AS IS" AND PRINCETON  
					UNIVERSITY MAKES NO REPRESENTATIONS OR WARRANTIES, EXPRESS OR  
					IMPLIED.  BY WAY OF EXAMPLE, BUT NOT LIMITATION, PRINCETON  
					UNIVERSITY MAKES NO REPRESENTATIONS OR WARRANTIES OF MERCHANT-  
					ABILITY OR FITNESS FOR ANY PARTICULAR PURPOSE OR THAT THE USE  
					OF THE LICENSED SOFTWARE, DATABASE OR DOCUMENTATION WILL NOT  
					INFRINGE ANY THIRD PARTY PATENTS, COPYRIGHTS, TRADEMARKS OR  
					OTHER RIGHTS.
					</p>
				  	<p class="w_smaller w_padleft">
					The name of Princeton University or Princeton may not be used in  
					advertising or publicity pertaining to distribution of the software  
					and/or database.  Title to copyright in this software, database and  
					any associated documentation shall at all times remain with  
					Princeton University and LICENSEE agrees to preserve same.  
					</p>
				</p>
				<hr size="1">
			</div>
		</div>
		<div class="row">
			<div class="col-sm-6">
				<div class="w_big well" style="padding-top:0px;">
					<div class="w_right w_padtop">
						<img src="/skills/pico/PicoLogo108x108.png" alt="pico fermi bagel" width="108" height="108" class="img img-responsive img-thumbnail" />
					</div>
					<h3 style="margin-bottom:0px;">Pico Fermi Bagel</h3>
					<div class="w_small w_grey">Submitted - May 4, 2016</div>
					<div class="w_small w_grey">Live - May 9, 2016</div>
					<div class="w_pad text">
							Pico Fermi Bagel is a number guessing game for the Amazon Echo&trade; Alexa will pick a random number and you try to guess it. 
							Each time you guess Alexa will respond with the number of Picos, Fermis, and Bagels.
							Just ask Alexa to "Open Pico" and prepare to strain your math brain.
						<div><a href="#" onclick="return showHide('more_pico');" class="w_link w_grey">Read more...</a></div>
						<div class="w_padtop"></div>
						<div class="text-right"><a class="btn btn-default btn-sm" target="alexa" href="//alexa.amazon.com/spa/index.html#skills/dp/B01F4XQ5NS/?ref=skill_dsk_skb_sr_1"><span class="icon-mic w_success"></span> Enable this skill</a></div>
					</div>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="w_bold w_big w_padtop">Pico Activity (last 30 days)</div>
				<canvas id="pico_canvas"></canvas>
				<div id="pico_data" style="display:none"><?=commonGetSkillData('pico');?></div>
				<div id="pico_debug"></div>
				<div class="text-left w_padtop">
					Help us make Pico Fermi Bagel the highest rated numbers game in the Alexa app store.
					Submit your review <a target="alexa" href="//alexa.amazon.com/spa/index.html#skills/dp/B01F4XQ5NS/create-review/?ref-suffix=war_dp">here!</a>
				</div>
			</div>
		</div>
		<div class="row" id="more_pico" style="display:none;">
			<div class="col-sm-12">
				<h1>Pico Fermi Bagel</h1>
				<div class="w_right w_padleft"><img src="/skills/pico/pico_example.png" class="img img-responsive" alt="Pico example" /></div>
				<p>Pico Fermi Bagel is a number guessing game for the Amazon Echo&trade;.
				Alexa will pick a secret number and you try to guess it.
				Each time you guess, Alexa will respond with these three items, based on your guess.
				<ol>
					<li><span class="w_bold">Pico.</span> Pico means a digit is in the secret number but it's in the wrong place value or location.</li>
					<li><span class="w_bold">Fermi.</span> Fermi means a digit is in the secret number AND it's in the correct place value or location.</li>
					<li><span class="w_bold">Bagels.</span> Bagel means the digit is not in the secret number.
				</ol>
				You can choose to play with 2, 3, 4, or 5 digit numbers.  
				<ul>
					<li>2 digit numbers get 6 guesses.
					<li>3 digit numbers get 8 guesses.
					<li>4 digit numbers get 9 guesses.
					<li>5 digit numbers get 10 guesses.
				</ul>
				<p>Here is how an example game might be played out:
				<div class="list-group list-group-sm w_small">
					<div class="list-group-item list-group-item-warning user">{YOU} Alexa, Open Pico.</div>
					<div class="list-group-item alexa">{ALEXA} Would you like to play the easy game or the standard version?</div>
					<div class="list-group-item list-group-item-warning user">{YOU} standard version</div>
					<div class="list-group-item alexa">{ALEXA} How many digits? 2, 3, 4 or 5?</div>
					<div class="list-group-item list-group-item-warning user">{YOU} Three.</div>
					<div class="list-group-item alexa">{ALEXA} Ok, I have chosen a 3 digit number. Your turn to guess. Pick a 3 digit number.</div>
					<div class="list-group-item list-group-item-warning user">{YOU} 849</div>
					<div class="list-group-item alexa">{ALEXA} 1 Fermi, 2 Bagels</div>
					<div class="list-group-item list-group-item-warning user">{YOU} 812</div>
					<div class="list-group-item alexa">{ALEXA} 3 Bagels</div>
					<div class="list-group-item list-group-item-warning user">{YOU} 342</div>
					<div class="list-group-item alexa">{ALEXA} 2 Fermi, 1 Bagel</div>
					<div class="list-group-item list-group-item-warning user">{YOU} 346</div>
					<div class="list-group-item alexa">{ALEXA} 2 Fermi, 1 Bagel</div>
					<div class="list-group-item list-group-item-warning user">{YOU} 347</div>
					<div class="list-group-item alexa">{ALEXA} 2 Fermi, 1 Bagel</div>
					<div class="list-group-item list-group-item-warning user">{YOU} 345</div>
					<div class="list-group-item alexa">{ALEXA} You Win!</div>
				</div>
			</div>
		</div>
	</div>
</div>
</view:fun>

<view:releases>
<div class="row w_padtop">
	<div class="col-sm-2"></div>
	<div class="col-sm-8">
		<h3>SalesTalk</h3>
		<table>
			<tr valign="top"><td>Initial Release:</td><td>2016-12-18</td></tr>
			<tr valign="top"><td>IOT Platforms Supported:</td><td>Amazon Echo Version 4540</td></tr>
			<tr valign="top"><td>Data Sources Supported:</td><td>
				WooCommerce 2.7<br>
				Stripe 2016-07-06
			</td></tr>
			<tr valign="top"><td>Reports Available:</td><td>Sales (or Total Sales), Unit Sales, Best/Worst Selling (Date, Day, Week, etc.)</td></tr>
		</table>
		<b>Feature Summary:</b>
		<ol>
			<li>Repeat any query with the phrases : Repeat, Repeat that
			<li>Help Summary added to list reports and available filters
			<li>Filters include: city, state, zip code, date, date range, named date period, historic (all sales ever recorded)
			<li>Support for "named" time periods including: yesterday, last week, last month, last quarter, first second third and fourth quarter, this week, this month, this year, quarter, last month by name
			<li>Greetings based on user's profile name and time of day locally
			<li>Duplicate city support provided with a secondary query to clarify which state the city is in
			<li>Ability to ask Alexa for the date or time while remaining within the SalesTalk Skill
			<li>Sales reports available via dashboard
		</ol>
	</div>
</div>
</view:releases>
