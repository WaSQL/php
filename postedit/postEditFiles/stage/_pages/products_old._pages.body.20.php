<div class="row">
	<div class="col-sm-1"></div>
	<div class="col-sm-10">
		<h1>Products</h1>
		<div class="row">
			<div class="col-sm-6">
				<div class="w_big well" style="padding-top:0px;">
					<div class="w_right w_padtop">
						<a href="/hangman"><img src="/skills/hangman/hangman_logo.png" alt="hangman" width="108" height="108" class="img img-responsive img-thumbnail" /></a>
					</div>
					<a href="/hangman"><h3 style="margin-bottom:0px;">Hangman</h3></a>
					<div class="w_small w_grey">Submitted - April 16, 2016</div>
					<div class="w_small w_grey">Live - April 29, 2016</div>
					<div class="w_pad text">
						The old school spelling game played with paper and pencil has moved into the 21st century with our new hands-free audio version.
						Using the Amazon Echo&trade; family of devices, just ask Alexa to "Start Hangman" and prepare to be educated and entertained.
						<div><a href="/hangman" class="w_link w_grey">Read more...</a></div>
						<div class="w_padtop"></div>
						<div class="text-right"><a class="btn btn-default btn-sm" target="alexa" href="//alexa.amazon.com/spa/index.html#skills/dp/B01ED658W6/?ref=skill_dsk_skb_sr_1"><span class="icon-mic w_success"></span> Enable this skill</a></div>
					</div>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="w_big well" style="padding-top:0px;">
					<div class="w_right w_padtop">
						<a href="/pico"><img src="/skills/pico/PicoLogo108x108.png" alt="pico fermi bagel" width="108" height="108" class="img img-responsive img-thumbnail" /></a>
					</div>
					<a href="/pico"><h3 style="margin-bottom:0px;">Pico Fermi Bagel</h3></a>
					<div class="w_small w_grey">Submitted - May 4, 2016</div>
					<div class="w_small w_grey">Live - May 9, 2016</div>
					<div class="w_pad text">
							Pico Fermi Bagel is a number guessing game for the Amazon Echo&trade; Alexa will pick a random number and you try to guess it. 
							Each time you guess Alexa will respond with the number of Picos, Fermis, and Bagels.
							Just ask Alexa to "Open Pico" and prepare to strain your math brain.
						<div><a href="/pico" class="w_link w_grey">Read more...</a></div>
						<div class="w_padtop"></div>
						<div class="text-right"><a class="btn btn-default btn-sm" target="alexa" href="//alexa.amazon.com/spa/index.html#skills/dp/B01F4XQ5NS/?ref=skill_dsk_skb_sr_0"><span class="icon-mic w_success"></span> Enable this skill</a></div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12">
				<div class="w_bold w_big w_padtop">Hangman Activity (last 30 days)</div>
				<canvas id="hangman_canvas"></canvas>
				<div id="hangman_data" style="display:none"><?=commonGetSkillData('hangman');?></div>
				<div id="hangman_debug"></div>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12">
				<div class="w_bold w_big w_padtop">Pico Activity (last 30 days)</div>
				<canvas id="pico_canvas"></canvas>
				<div id="pico_data" style="display:none"><?=commonGetSkillData('pico');?></div>
				<div id="pico_debug"></div>
			</div>
		</div>
	</div>
</div>
