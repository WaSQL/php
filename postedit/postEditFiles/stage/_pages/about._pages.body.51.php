<view:default>
<div class="row w_padtop">
	<div class="col-sm-2"></div>
	<div class="col-sm-8 text-center">
		<div class="row">
			<div class="col-sm-3 text-center">
				<div class="well lightorangeback <?=pageNavClass('about_us');?>">
					<view:boxes><div class="w_padtop"><a href="/about/about_us"><img src="/images/site/saltlakecity.jpg" alt="About Us" width="225" class="img img-responsive img-thumbnail" /></a></div></view:boxes>
					<h5 class="w_white"><a href="/about/about_us" class="w_white">About Us</a></h5>
				</div>
			</div>
			<div class="col-sm-3 text-center">
				<div class="well lightorangeback <?=pageNavClass('core_values');?>">
					<view:boxes><div class="w_padtop"><a href="/about/core_values"><img src="/images/site/Compass.jpg" alt="vote donors choose" width="225" class="img img-responsive img-thumbnail" /></a></div></view:boxes>
					<h5 class="w_white"><a href="/about/core_values" class="w_white">Core Values</a></h5>
				</div>
			</div>
			<div class="col-sm-3 text-center">
				<div class="well lightorangeback <?=pageNavClass('donors_choose');?>">
					<view:boxes><div class="w_padtop"><a href="/about/donors_choose"><img src="/images/site/Education.jpg" alt="In support of education" width="225" class="img img-responsive img-thumbnail" /></a></div></view:boxes>
					<h5 class="w_white"><a href="/about/donors_choose" class="w_white">In Support of Education</a></h5>
				</div>
			</div>

			<div class="col-sm-3 text-center">
				<div class="well lightorangeback <?=pageNavClass('donation_results');?>">
					<view:boxes><div class="w_padtop"><a href="/about/donation_results"><img src="/images/site/donationresults.jpg" alt="Donation Results" width="225" class="img img-responsive img-thumbnail" /></a></div></view:boxes>
					<h5 class="w_white"><a href="/about/donation_results" class="w_white">Donation Results</a></h5>
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

<view:about_us>
	<h2>About Us</h2>
	<p>Based in Salt Lake City, our founders have more than 65 years of business and technology experience between them.
	Over the years, we've all worked for enterprise companies with global reach like Intel to innovative startups, oftentimes as coworkers.
	Individually, we've run our own businesses, striving to reach financial success alone.
	We have nothing but admiration for the many solopreneurs and startups working hard every day to achieve their dreams. 
	In fact, one of our biggest goals as a company is to help those business owners by sharing our technical knowledge and business experience, what we've seen work and where we've failed in the past, while providing quality software that supports those efforts.</p>
	<p>
	While our primary focus is to provide premium business productivity apps at an affordable price, we also want to have fun along the way.
	That's why you'll see a mix of offerings from us including free games and entertaining apps that have little to do with work and the business of making money. 
	Our voice games are things we have a lot of fun making and using ourselves.</p>
	<p>
	We sincerely hope you enjoy what we're creating at Skillsai, and that you'll join us in working AND playing hard.
	Please let us hear from you about what you're doing in your own business, what new skills or actions you would like us to build, and any other ways we can help.
	Life is not a zero-sum game--we're going for the win-win!</p><br>

	<h2>Our Team</h2>
	<div class="row w_padtop">
		<div class="col-sm-3">
			<div><img src="/images/site/laurie_nylund.jpg" class="img img-responsive" /></div>
			<div class="w_bold"><br>CEO and Co-Founder</div>
			<ul>				
				<li>Education Junkie
				<li>Gadget Freak
				<li>Wannabe Chef
			</ul>
		</div>
		<div class="col-sm-9">
			<h4>Laurie Nylund</h4>
			<p>
			An inveterate organizer, Laurie has attained leadership positions in every role she has held, from Sales to Engineering to Operations.
			After a ten-year career in retail, rising from a part-time salesclerk position while in college at UW-Madison to Store Manager at regional discount chain Shopko, Laurie went back to school
			for a degree in Computer Science. Less than two years later, she was hired as a build intern for the LANDesk Systems Management division at Intel. While at Intel, she moved
			from Build Engineer to Engineering Manager in less than seven years.</p>
			<p>
			Part of the core management team instrumental in spinning out the LANDesk division,she then led a number of product lines and teams as Director of Engineering. 
			Those responsibilities included the design and launch of new products like the innovative Instant Support Suite for IT help desks and the datacenter-focused Server Manager
			web solution&mdash;one of the key decision factors in the company being acquired by Avocent a few years later.</p>
			<p>
			As the Director of Engineering and Operations at Celio Corp, a mobile startup founded in 2006 (before mobile was even a thing!), she experienced firsthand the roller coaster ride
			that is a tech startup, wearing multiple hats and gaining foundational experience in what it's like to be part of a nascent technology swing. 
			She strongly believes that IoT and VUI is the next major shift, and is excited to once again be part of an unfolding technology story.</p>				
		</div>			
	</div>
	<hr size="1" />
	<div class="row w_padtop">
		<div class="col-sm-3">
			<div><img src="/images/site/steven_lloyd.jpg" class="img img-responsive" /></div>
			<div class="w_bold"><br>CTO and Co-Founder</div>
			<ul>
				<li>Eternal Optimist
				<li>Flyfisher Extraordinaire
				<li>King of Dad Jokes
			</ul>
		</div>
		<div class="col-sm-9">
			<h4>Steve Lloyd</h4>
			<p>Steve is the consummate inventor and entrepreneur with more business and product ideas than any one army could implement in a lifetime.
			His pervasive "can do" attitude led him to start his first business while still in college.
			He went on to contribute to more than two dozen companies throughout his career, several of which were small businesses he founded, and his bonafides include two patents as well.</p>
			<p>
			From QA Manager at WordPerfect to Automation Engineer at LANDesk to Business Intelligence Manager at DoTerra, data and analytics have been the backbone of his technical expertise.
			He'll happily geek out for hours on the pros and cons of various development languages or on how cool the new JSON data type is in MySQL.</p>
			<p>
			As a husband and a father of four girls (his two favorite roles), he is well-versed in handling any challenging situation he comes across with aplomb and grace. 
			In fact, he is so patient and kind that everyone who knows him describes him as the nicest guy you would ever want to meet. 
			He didn't want us to include that part, but it's who he is. Truly.</p>
		</div>
	</div>
	<hr size="1" />
	<div class="row w_padtop">
		<div class="col-sm-3">
			<div><img src="/images/site/jake_nylund.jpg" class="img img-responsive" /></div>
			<div class="w_bold"><br>CIO and Co-Founder</div>
			<ul>
				<li>Drone Expert
				<li>Avid Gamer
				<li>Lover of four-legged, furry beasts
			</ul>
		</div>
		<div class="col-sm-9">
			<h4>Jake Nylund</h4>
			<p>As our Chief Innovation Officer, Jake has been around technology all of his life.
			It began with learning the alphabet from a Donald Duck 5.25" floppy disk that he had to boot to from DOS to load, before he was even three years old. 
			He started programming in 9th grade, and continued to embrace technology when he started the first Robotics team at his high school, a group that continues competing at FIRST today.</p>
			<p>
			With both hardware and software experience, he landed his first job at Intel before even graduating from the University of Portland in 2014, where he received his degree in Electrical Engineering with a minor in Computer Science.  
			He later joined a drone management company, Skyward.io, an exciting startup at the leading edge of that developing sector. 
			While at Skyward, he contributed to two patents and received the company's Viking award for his ability to successfully journey forward in the face of the unknown.  
			At Nike, he was part of the team who created a new React mobile app for product personalization.</p>
			<p>
			Fascinated by Artificial Intelligence and Machine Learning, Jake is exploring where those technologies can take our BI products in the future.
			At Skillsai, we're excited to see where those explorations lead us.</p>
		</div>
	</div>
</view:about_us>

<view:core_values>
	<h2>Our Guiding Principles</h2>
	<img src="/images/site/CoreValues.png" alt="Skillsai Core Values" class="img img-responsive img-thumbnail" />
</view:core_values>

<view:donors_choose>
	<? $recs=pageDonorsChooseList();?>
	<h2>In Support of Education</h2>
	<img src="/images/site/charlesbest.png" alt="vote donors choose" width="400" style="margin:0 0 20px 20px;" class="w_right img img-responsive img-thumbnail" />
	At Skillsai, we believe education is the fundamental solution to ALL of the world's problems.
	That's why we donate 2% of our total revenue to <a href="https://www.donorschoose.org" target="donors_choose">DonorsChoose.org</a>, an amazing national charity that funds classrooms in need.
	Founded in 2000 by Bronx public school teacher, Charles Best, their dymanic team of 80 people vet and fulfill classroom project requests from teachers in every state.
	With an amazing score of 96.66 on <a href="https://www.charitynavigator.org/index.cfm?bay=search.summary&orgid=9284" target="donors_choose">Charity Navigator</a>, this group spends nearly 94% of the money they receive on the programs and services they deliver.
	<p><p>
	Every month, we'll pick three projects from their site and ask you to vote on the one that resonates most with you.
	We'll share when, where and how much we contribute - with your support - because, of course, we're all about data and cool charts.
	Coincidently, it seems that the folks at DonorChoose geek out on data visualization, too - check out their nice infographic page <a href="https://www.donorschoose.org/about/impact.html" target="donors_choose">here</a>.
	It will be fun to watch the donation number grow as we grow, and rewarding to know that together we are doing a little bit to make a difference in the world.
	<h3>This Month's Classroom Projects - Vote Now!</h3>
	<form method="post" action="/t/1/about/vote" onsubmit="return ajaxSubmitForm(this,'centerpop');">
	<div class="row">
		<div class="col-sm-12">
   			<view:drec>
			<div class="row">
				<div class="col-sm-1 w_padtop text-right">
     				<view:canvote>
					<input id="project_<?=$rec['id'];?>" type="radio" value="<?=$rec['id'];?>" data-required="1" data-requiredmsg="Pick one to vote for to place your vote." name="vote[]" data-type="checkbox" style="display:none;" data-group="vote_group">
					<label class="icon-mark w_warning" style="font-size:30pt;" for="project_<?=$rec['id'];?>"></label>
					</view:canvote>
					<?=renderViewIf($rec['percentFunded'] < 100,'canvote',$rec,'rec');?>
					<div class="text-center w_right">
						<div class="votecount"><?=$rec['votes'];?></div>
						<div>votes</div>
					</div><br clear="both" />
     				<view:award>
					<div class="text-center w_right">
						<div style="font-size:2.5em;"><span class="icon-award-filled w_gold"></span></div>
						<div class="w_bold w_big w_success">$<?=$rec['award'];?></div>
						<div class="w_grey">Award</div>
					</div>
					<br clear="both" />
					</view:award>
					<?=renderViewIf($rec['award'] > 0,'award',$rec,'rec');?>
				</div>
				<div class="col-sm-8">
     				<div class="project w_pad" style="font-weight:normal;margin-top:15px;">
      				<div class="row">
							<div class="col-xs-3 hidden-sm hidden-xs">
								<img src="<?=$rec['imageURL'];?>" alt="" class="img img-responsive" />
							</div>
							<div class="col-xs-6">
								<div class="donorchoose_title"><?=$rec['title'];?></div>
								<div class="donorchoose_trailer">"<?=$rec['fulfillmentTrailer'];?>"</div>
								<div class="donorchoose_teacher"><?=$rec['teacherName'];?></div>
								<div class="donorchoose_school"><?=$rec['schoolName'];?> - <?=$rec['city'];?> <?=$rec['state'];?></div>
							</div>
							<div class="col-xs-3">
								<div class="progress">
									<div class="progress-bar progress-bar-<?=$rec['progressColor'];?>" role="progressbar" aria-valuenow="<?=$rec['percentFunded'];?>"
								  		aria-valuemin="0" aria-valuemax="100" style="width:<?=$rec['percentFunded'];?>%">
								    	<?=$rec['percentFunded'];?>%
								  	</div>
								</div>
								<div class="donorchoose_donors"><b><?=$rec['numDonors'];?></b> Donors so far</div>
								<div class="donorchoose_needed"><b>$<?=$rec['costToComplete'];?></b> still needed</div>
								<div class="text-right w_padtop"><a href="<?=$rec['proposalURL'];?>" target="donorschoose" class="btn btn-default">More Info</a></div>
							</div>
						</div>
					</div>
				</div>
			</div>
			</view:drec>
			<?=renderEach('drec',$recs,'rec');?>
		</div>
	</div>

	<div class="row w_padtop">
		<div class="col-sm-4">
			<input type="email" value="" placeholder="Enter Email Address" name="email" required="1" class="form-control" />
		</div>
	</div>
	<div class="row w_padtop">
		<div class="col-sm-4">
			<input id="opt_in" type="checkbox" value="1" name="opt_in" data-type="checkbox" style="display:none;" data-group="optin_group" checked>
			<label class="icon-mark w_grey" style="font-size:18pt;" for="opt_in"></label>
			Sign me up for the newsletter
		</div>
	</div>
	<div class="row w_padtop">
		<div class="col-sm-4">
			<button type="submit" class="btn btn-warning btn-lg">Place Vote</button>
		</div>
	</div>
	</form>
</view:donors_choose>

<view:donation_results>
	<h2>Donation Results</h2>
	At Skillsai, education is one of our passions.
	That's why we support <a href="https://www.donorschoose.org" target="donors_choose">DonorsChoose.Org</a>, a charity dedicated to helping teachers in the classroom get the resources they need to educate our next generation of learners.
	We've pledged to donate 5% of our profits every month, and share the results of that donation with you here.
	<p>
	Of course, as a budding new business, we're starting out small.  
	Remember, you don't have to be great to start, but you have to start to be great.
	Every little bit we contribute (thanks to your support!) will make a difference.
	<p>
	<div class="w_bold">Total Contributions</div>
	<p>
		<div id="chart" class="contribution" style="padding:0px;"></div>
		<?=buildOnLoad("wd3BarChart('#chart.contribution',{csv:'/t/1/about/contribution_chart',yformat:',.2f',ylabel:'Contributions (USD)',yticks:10,xticks:12});");?>
	<p>
	<? $recs=pageProjectsAwarded();?>
	<div class="w_bold">Projects Awarded</div>
	<table class="table table-striped table-bordered">
		<tr class="orangeback">
			<th>Date</th>
			<th>Amount</th>
			<th>Classroom Project</th>
			<th>City</th>
			<th>State</th>
		</tr>
		<view:rec>
		<tr>
			<td><?=date('Y-m-t',strtotime($rec['project_date']));?></td>
			<td><?=$rec['award'];?></td>
			<td><a href="<?=$rec['proposalURL'];?>" target="donorschoose"><?=$rec['title'];?></a></td>
			<td><?=$rec['city'];?></td>
			<td><?=$rec['state'];?></td>
		</tr>
		</view:rec>
		<?=renderEach('rec',$recs,'rec');?>
	</table>
</view:donation_results>

<view:contribution_chart>
<?=pageContributionChartCsv();?>
</view:contribution_chart>

<view:vote>
	<div class="w_centerpop_title"></div>
	<div class="w_centerpop_content">
		<h2 style="font-weight:normal;">Thanks for Voting!</h2>
		Please bookmark this page, and visit us next month to vote again for your favorite  Classroom Project.
	</div>
</view:vote>

<view:voted>
	<div class="w_centerpop_title"></div>
	<div class="w_centerpop_content">
		<h2>You have already voted this month.</h2>
		You only get one vote per month. Come back next month and vote again!
	</div>
</view:voted>
