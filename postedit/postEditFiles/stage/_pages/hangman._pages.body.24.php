<view:default>
<div class="row w_padtop">
	<div class="col-sm-1"></div>
	<div class="col-sm-10">
		<div class="well" style="min-height:300px;">
			<div class="row">
				<div class="col-sm-6">
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
						<p class="w_smaller">
						This software and database is being provided to you, the LICENSEE, by
						Princeton University under the following license.  By obtaining, using  
						and/or copying this software and database, you agree that you have  
						read, understood, and will comply with these terms and conditions.:
						</p>
					  	<p class="w_smaller">
						Permission to use, copy, modify and distribute this software and
						database and its documentation for any purpose and without fee or
						royalty is hereby granted, provided that you agree to comply with  
						the following copyright notice and statements, including the disclaimer,  
						and that the same appear on ALL copies of the software, database and  
						documentation, including modifications that you make for internal  
						use or for distribution.  
					  	<p class="w_smaller">
						WordNet 1.6 Copyright 1997 by Princeton University.  All rights reserved.  
						</p>
					  	<p class="w_smaller">
						THIS SOFTWARE AND DATABASE IS PROVIDED "AS IS" AND PRINCETON  
						UNIVERSITY MAKES NO REPRESENTATIONS OR WARRANTIES, EXPRESS OR  
						IMPLIED.  BY WAY OF EXAMPLE, BUT NOT LIMITATION, PRINCETON  
						UNIVERSITY MAKES NO REPRESENTATIONS OR WARRANTIES OF MERCHANT-  
						ABILITY OR FITNESS FOR ANY PARTICULAR PURPOSE OR THAT THE USE  
						OF THE LICENSED SOFTWARE, DATABASE OR DOCUMENTATION WILL NOT  
						INFRINGE ANY THIRD PARTY PATENTS, COPYRIGHTS, TRADEMARKS OR  
						OTHER RIGHTS.
						</p>
					  	<p class="w_smaller">
						The name of Princeton University or Princeton may not be used in  
						advertising or publicity pertaining to distribution of the software  
						and/or database.  Title to copyright in this software, database and  
						any associated documentation shall at all times remain with  
						Princeton University and LICENSEE agrees to preserve same.  
						</p>
				</p>
				</div>
				<div class="col-sm-6">
					<h1 class="text-center w_bold">S K I _ _ S A I</h1>
					<img src="https://www.skillsai.com/cors/skills/hangman/miss3-small.png" onload="templateDrawChart('hangman','line');" width="720" height="480" class="img img-responsive" alt="Hangman Image" />
					<div class="text-left w_padtop">
						Help us make Hangman the highest rated skill in the Alexa app store.
						Submit your review <a target="alexa" href="//alexa.amazon.com/spa/index.html#skills/dp/B01ED658W6/create-review/?ref-suffix=war_dp">here!</a>
					</div>
					<div class="text-right w_padtop">
						<a class="btn btn-default btn-lg" target="alexa" href="//alexa.amazon.com/spa/index.html#skills/dp/B01ED658W6/?ref=skill_dsk_skb_sr_1"><span class="icon-mic w_success w_bigger"></span> Enable this skill</a>
					</div>
					<div class="w_bold w_big w_padtop">Hangman Players (last 10 days)</div>
					<canvas id="hangman_canvas"></canvas>
					<div id="hangman_data" style="display:none"><?=commonGetSkillPlayerData('hangman');?></div>
					<div id="hangman_debug"></div>
				</div>
			</div>
		</div>
	</div>
</div>
</view:default>
