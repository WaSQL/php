<view:default>
<div class="row w_padtop">
	<div class="col-sm-1"></div>
	<div class="col-sm-10">
		<div class="well" style="min-height:300px;">
			<div class="row">
				<div class="col-sm-6">
					<h1>Pico Fermi Bagel</h1>
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
				<div class="col-sm-6 text-center">
					<img src="/images/skills/PicoLogo512.png" onload="templateDrawChart('pico','line');" width="720" height="480" class="img img-responsive" alt="Pico Image" />
					<div align="center" class="w_padtop"><img src="/skills/pico/pico_example.png" class="img img-responsive" alt="Pico example" /></div>
					<div class="text-right w_padtop"><a class="btn btn-default btn-lg" target="alexa" href="//alexa.amazon.com/spa/index.html#skills/dp/B01F4XQ5NS/?ref=skill_dsk_skb_sr_0"><span class="icon-mic w_success w_bigger"></span> Enable this skill</a></div>
					<div class="w_bold w_big w_padtop">Pico Players (last 10 days)</div>
					<canvas id="pico_canvas"></canvas>
					<div id="pico_data" style="display:none"><?=commonGetSkillPlayerData('pico');?></div>
					<div id="pico_debug"></div>
				</div>
			</div>
		</div>
	</div>
</div>
</view:default>
