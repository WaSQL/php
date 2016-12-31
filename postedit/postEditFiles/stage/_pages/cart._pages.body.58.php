<view:default>
<div class="row w_padtop">
	<div class="col-sm-1"></div>
	<div class="col-sm-10">
		<form name="cartform" method="post" action="/t/1/cart/subscribe" onsubmit="return ajaxSubmitForm(this,'centerpop');">
			<input type="hidden" name="term" value="year" />
		<div class="row w_padtop text-center" style="position:relative;">
			<div class="w_padtop hidden-xs hidden-sm" style="position:absolute;bottom:5px;left:20px;"><a href="/pricing" class="pick_pricing" title="Select a different package"><span class="icon-arrow-left w_primary"></span><br>&nbsp;&nbsp;&nbsp;Select a different package</a></div>
			<div class="col-sm-3" style="padding:0 7px 0 7px;height:100%;">
				<view:solo>
				<input type="hidden" name="plan" id="plan" value="solo" data-m="5" data-y="50" />
				<div class="solo">
					<div><button class="btn btn-lg">Solo</button></div>
					<div align="center">
						<img src="/images/site/fivedollarpricing-box.png" alt="five dollar pricing" class="img img-responsive plan" style="max-width:286px;" onload="cartUpdatePrices();" />
					</div>
				</div>
				</view:solo>
				<view:startup>
				<input type="hidden" name="plan" id="plan" value="startup" data-m="10" data-y="100" />
				<div class="startup">
					<div><button class="btn btn-lg">Startup</button></div>
					<div class="relative" align="center">
						<img src="/images/site/tendollarpricing-box.png" alt="ten dollar pricing" class="img img-responsive plan" style="max-width:286px;" onload="cartUpdatePrices();" />
					</div>
				</div>
				</view:startup>
				<view:growing>
				<input type="hidden" name="plan" id="plan" value="growing" data-m="25" data-y="250" />
				<div class="growing">
					<div><button class="btn btn-lg">Growing</button></div>
					<div class="relative" align="center">
						<img src="/images/site/twentyfivedollarpricing-box.png" alt="twentyfive dollar pricing" class="img img-responsive plan" style="max-width:286px;" onload="cartUpdatePrices();" />
					</div>
				</div>
				</view:growing>
				<view:established>
				<input type="hidden" name="plan" id="plan" value="established" data-m="50" data-y="500" />
				<div class="established">
					<div><button class="btn btn-lg">Established</button></div>
					<div class="relative" align="center">
						<img src="/images/site/fiftydollarpricing-box.png" alt="fifty dollar pricing" class="img img-responsive plan" style="max-width:286px;" onload="cartUpdatePrices();" />
					</div>
				</div>
				</view:established>
			</div>
			<div class="col-sm-9">
				<div class="row">
					<div class="col-sm-12">
						<h2>Select Report Packages</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-12 text-left w_bigger">
						These packages contain dozens of reports that can be further configured with multiple filters in your dashboard resulting in hundreds of possible options.
						If you later choose to downgrade one of the optional packages, previously configured reports from those packages will be removed from your dashboard.<br>
						<br>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-6">
						<div class="package">
							<div class="title">
								<input data-group="cart_package_group" class="package" id="cart_package_sales" style="display:none;" data-type="checkbox" type="checkbox" name="package[]" value="sales" checked>
								<label><span class="icon-mark"></span> Sales Package</label>
							</div>
							<div class="w_padtop text-center">
								<div>Totals (Dollars), Totals (Units), Sales Goals.</div>
								<div>Configurable by time, date, city, state, and zip code</div>
							</div>
						</div>
					</div>
					<div class="col-sm-6">
						<div class="package">
							<div class="title">
								<input data-group="cart_package_group" class="package"  id="cart_package_orders" style="display:none;" data-type="checkbox" onclick="cartUpdatePrices();" type="checkbox" name="package[]" value="orders">
								<label for="cart_package_orders"><span class="icon-mark"></span> Orders Package</label>
							</div>
							<div class="w_padtop text-center">
								<div>Conversion Rates, Average Order Size, Price Discounts</div>
								<div>Configurable by time, date, city, state, and zip code</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-6">
						<div class="package">
							<div class="title">
								<input data-group="cart_package_group" class="package"  id="cart_package_products" style="display:none;" data-type="checkbox" onclick="cartUpdatePrices();" type="checkbox" name="package[]" value="products">
								<label for="cart_package_products"><span class="icon-mark"></span> Products Package</label>
							</div>
							<div class="w_padtop text-center">
								<div>BestSellers, Products with No sales, Product Refunds</div>
								<div>Configurable by time, date, city, state, and zip code</div>
							</div>
						</div>
					</div>
					<div class="col-sm-6">
						<div class="package">
							<div class="title">
								<input data-group="cart_package_group" class="package"  id="cart_package_customers" style="display:none;" data-type="checkbox" onclick="cartUpdatePrices();" type="checkbox" name="package[]" value="customers">
								<label for="cart_package_customers"><span class="icon-mark"></span> Customers Package</label>
							</div>
							<div class="w_padtop text-center">
								<div>Customer Details, Customer Counts, Customer Refunds</div>
								<div>Configurable by time, date, city, state, and zip code</div>
								</ul>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div id="<?=isUser()?'formfieldsx':'formfields';?>" class="col-sm-12">
						<div class="row">
							<div class="col-sm-6">
								<h2>Customer Information</h2>
								<div class="row w_padtop">
									<div class="col-sm-12">
										<?=buildFormText('company',array('placeholder'=>'company name','required'=>1));?>
									</div>
								</div>
								<div class="row w_padtop">
									<div class="col-sm-12">
										<?=buildFormText('url',array('placeholder'=>'website/url','required'=>1));?>
									</div>
								</div>
								<div class="row w_padtop">
									<div class="col-sm-12">
										<?=buildFormSelectTimezone('timezone',array('message'=>'-- Timezone --','required'=>1));?>
									</div>
								</div>
								<div class="row w_padtop">
									<div class="col-sm-6">
										<?=buildFormSelectCountry('country',array('value'=>'US','message'=>'-- Country --','onchange'=>"cartRedrawState(this);",'required'=>1));?>
									</div>
									<div class="col-sm-6" id="statediv">
										<?=buildFormSelectState('state','US',array('message'=>'-- State --','required'=>1));?>
									</div>
								</div>
								<div class="row w_padtop">
									<div class="col-sm-8">
										<?=buildFormText('city',array('placeholder'=>'city','required'=>1));?>
									</div>
									<div class="col-sm-4">
										<?=buildFormText('postcode',array('placeholder'=>'postcode','required'=>1));?>
									</div>
								</div>
								<div class="row w_padtop">
									<div class="col-sm-6">
										<?=buildFormText('name',array('placeholder'=>'contact name','required'=>1));?>
									</div>
									<div class="col-sm-6">
										<?=buildFormText('email',array('-type'=>'email','placeholder'=>'contact email address','required'=>1));?>
									</div>
								</div>
							</div>
							<div class="col-sm-1"></div>
							<div class="col-sm-5">
								<h2>Payment Information</h2>
								<div class="row w_padtop">
									<div class="col-sm-12">
										<?=buildFormText('cc_name',array('placeholder'=>'name on card','required'=>1));?>
									</div>
								</div>
								<div class="row w_padtop">
									<div class="col-sm-12">
										<?=buildFormText('cc_num',array('placeholder'=>'credit card number','required'=>1));?>
									</div>
								</div>
								<div class="row w_padtop">
									<div class="col-sm-4">
										<?=buildFormSelectMonth('cc_month',array('message'=>'-- Month --','required'=>1));?>
									</div>
									<div class="col-sm-4">
										<?=buildFormSelectYear('cc_year',array('message'=>'-- Year --','required'=>1));?>
									</div>
									<div class="col-sm-4">
										<?=buildFormText('cc_cvv',array('placeholder'=>'cvv','required'=>1));?>
										<a href="#" onclick="showHide('cvvhelp');return false;" class="w_link w_lblue">what's this?</a>
										<div id="cvvhelp" class="w_pointer" title="click to close" style="display:none;position:absolute;top:40px;left:-250px;background:#FFF;z-index:999;" onclick="hideId(this.id);">
											<div class="well" style="padding-bottom:1px;">
												<div><img src="/wfiles/icons/pymt/cvv2.gif" border="0" alt="cvv code help" /></div>
												<div class="text-right w_padtop"><span class="icon-close w_danger"></span></div>
											</div>
										</div>
									</div>
								</div>
								<div align="right" style="margin:10px 10px 0 0;">
									<table border="0" cellspacing="2" cellpadding="2"><tr>
										<td><img src="/wfiles/iconsets/48/visa.png" title="visa" alt="We accept Visa" border="0" height="48" /></td>
										<td><img src="/wfiles/iconsets/48/mastercard.png" title="mastercard" alt="We accept Mastercard" border="0" height="48" /></td>
										<td><img src="/wfiles/iconsets/48/amex.png" title="american express" alt="We accept American Express" border="0" height="48" /></td>
										<td><img src="/wfiles/iconsets/48/discover.png" title="discover" alt="We accept Discover" border="0" height="48" /></td>
										<td><img src="/wfiles/iconsets/48/secure.png" title="Site is Secure" border="0" alt="This page is secure" height="48" /></td>
									</tr></table>
								</div>
							</div>
						</div>
						<div class="row w_padtop">
							<div class="col-sm-6 text-center">
								<h2>Yearly Subscription</h2>
								<div class="row">
									<div class="col-sm-6 text-right">
										<div id="yearly" class="icon-currency-dollar w_bold"></div>
									</div>
									<div class="col-sm-6 text-left">
										<button type="submit" onclick="document.cartform.term.value='year';" class="btn btn-success btn-lg">Subscribe</button>
									</div>
								</div>
								<div class="row w_padtop">
									<div class="col-sm-12 text-center w_danger w_bigger" id="savings"></div>
								</div>
							</div>
							<div class="col-sm-6 text-center">
								<h2>Monthly Subscription</h2>
								<div class="row">
									<div class="col-sm-6 text-right">
										<div id="monthly" class="icon-currency-dollar w_bold"></div>
									</div>
									<div class="col-sm-6 text-left">
										<button type="submit" onclick="document.cartform.term.value='month';" class="btn btn-warning btn-lg">Subscribe</button>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<view:guest>
				<div class="row">
					<div id="cartCheckout" class="row w_padtop text-center">
						<a href="/login?redirect=<?=encodeURL($_SERVER['REQUEST_URI']);?>" class="btn btn-success btn-lg"><span class="icon-arrow-right"></span> Next</a>
					</div>
				</div>
				</view:guest>
				<?=renderViewIf(!isUser(),'guest');?>
			</div>
		</form>
		</div>
	</div>
</div>
</view:default>

<view:country>
<?=buildFormSelectState('state',$country);?>
</view:country>

<view:subscribe>
<div class="w_centerpop_title">Subscriptions</div>
<div class="w_centerpop_body" style="padding:20px;">
	<h2 class="text-center"><span class="icon-mark w_success"></span> You are Subscribed!</h2>
	<h4 class="text-center"><?=$stripe['plan']['name'];?></h4>
</div>
</view:subscribe>
