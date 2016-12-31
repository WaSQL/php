<view:_receipt>
<!DOCTYPE HTML>
<html lang="en">
<head>
<style>
	table {
		border-collapse:collapse;
		margin-bottom:15px;
	}
	.full{width:100%;}
	.items tr td {
		empty-cells:show;
		padding:5px;
	}
	.items tr:nth-child(even){
		background:#f8f8f8;
	}
	img{border:0px;}
	.bold{font-weight:bold;}
	.big{font-size:1.3em;}
	.red{color:red;}
	.footer{font-size:.8em;}
	body{
		background-color:#FFF;
		font-family:arial;
	}
</style>
</head>
<body>
	<table class="full"><tr valign="top">
		<td><img src="https://stage.skillsai.com/images/site/SkillsaiLogo-150x75.png" height="150" alt="logo" /></td>
		<td style="padding-left:50px;">
			<div class="bold">Skillsai.com</div>
			<div>2325 N 600 W</div>
			<div>Pleasant Grove, UT 84062</div>
			<div>http://www.skillsai.com</div>
			<div class="bold">385-414-1728</div>
		</td>
		<td class="full">
			<div class="big bold" align="right">RECEIPT / INVOICE</div>
			<div align="right">
			<table>
				<tr><td class="bold" nowrap>Order Number: </td><td nowrap><?=$order['ordernumber'];?></td></tr>
				<tr><td class="bold" nowrap>Order Date: </td><td nowrap><?=$order['orderdate'];?></td></tr>
				<tr><td class="bold" nowrap>Current Status: </td><td nowrap><?=$order['status'];?></td></tr>
			</table>
			</div>
		</td>
	</tr></table>
	<table class="full">
		<tr valign="top"><td class="big bold">Bill To</td><td class="big bold">Ship To</td></tr>
		<tr valign="top">
			<td>
				<div><?=$order['billtoname'];?></div>
				<div><?=$order['billtoemail'];?></div>
				<div><?=$order['billtotelephone'];?></div>
				<div>Paid with <?=$order['payment_type'];?></div>
			</td>
			<td>
				<div><?=$order['shiptoname'];?></div>
				<div><?=$order['shiptoaddress1'];?></div>
				<div><?=$order['shiptoaddress2'];?></div>
				<div><?=$order['shiptocity'];?> <?=$order['shiptostate'];?> <?=$order['shiptozipcode'];?></div>
			</td>
		</tr>
	</table>
	<table class="items full">
		<tr>
			<th>SKU</th>
			<th>Description</th>
			<th>Qty</th>
			<th>Price</th>
			<th>Subtotal</th>
		</tr>
	<view:_citem>
		<tr>
			<td><?=$citem['sku'];?></td>
			<td><?=$citem['description'];?></td>
			<td align="right"><?=$citem['quantity'];?></td>
			<td align="right">$ <?=formatMoney($citem['price']);?></td>
			<td align="right">$ <?=formatMoney($citem['subtotal']);?></td>
		</tr>
	</view:_citem>
	<?=renderEach('_citem',$order['items'],array('-alias'=>'citem'));?>
		<tr>
			<td colspan="2" align="right" class="bold">Totals</td>
			<td align="right"><?=$order['items_quantity'];?></td>
			<td  align="right"></td>
			<td  align="right"><?=$order['items_total'];?></td>
		</tr>
		<view:_receipt_discount>
		<tr>
			<td colspan="2" align="right" class="bold">Discount</td>
			<td colspan="2" align="right"><?=$order['discount_description'];?></td>
			<td  align="right" class="red">- $ <?=formatMoney($order['discount']);?></td>
		</tr>
		</view:_receipt_discount>
		<?=renderViewIf($order['discount']>0,'_receipt_discount',$order,array('-alias'=>'order'));?>
		<view:_receipt_tax>
		<tr>
			<td colspan="2" align="right" class="bold"><?=$order['tax_title'];?></td>
			<td colspan="2" align="right"><?=$order['tax_description'];?></td>
			<td  align="right">$ <?=formatMoney($order['tax']);?></td>
		</tr>
		</view:_receipt_tax>
		<?=renderViewIf($order['tax']>0,'_receipt_tax',$order,array('-alias'=>'order'));?>
		<view:_receipt_shipping>
		<tr>
			<td colspan="2" align="right" class="bold"><?=$order['shipping_title'];?></td>
			<td colspan="2" align="right"><?=$order['shipping_description'];?></td>
			<td  align="right">$ <?=formatMoney($order['shipping']);?></td>
		</tr>
		</view:_receipt_shipping>
		<?=renderViewIf($order['shipping']>0,'_receipt_shipping',$order,array('-alias'=>'order'));?>
		<tr>
			<td colspan="4" align="right" class="bold">Order Total</td>
			<td  align="right" class="big bold"><?=$order['order_total'];?></td>
		</tr>
	</table>
	<div class="footer">
		<div style="padding:25px;text-align:left">
			Thank you for your order!
			This transaction will appear on your billing statement as "DEVMAVIN".
			If you have any questions, please feel free to contact us at info@skillsai.com.
		</div>
		<div style="padding:10px;text-align:left">
		This communication is for the exclusive use of the addressee and may contain proprietary, confidential or privileged information.
		If you are not the intended recipient any use, copying, disclosure, dissemination or distribution is strictly prohibited.
		</div>
		<div style="padding:10px;text-align:center">
		&copy; Skillsai All Rights Reserved
		</div>
	</div>
</body>
</html>
</view:_receipt>

<view:_coupon>
	<div class="row">
		<div class="col-xs-12 col-sm-6">
			<div class="row">
				<div class="col-sm-3">
					<div class="text-muted">Coupons</div>
				</div>
				<div class="col-sm-5">
					<input class="form-control" type="text" name="coupon" id="coupon_code" value="<?=$_REQUEST['coupon'];?>">
				</div>
				<div class="col-sm-4">
					<button class="btn btn-primary" type="button" onclick="pageApplyCoupon();"><span class="glyphicon glyphicon-tag"></span> Apply Coupon</button>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-12  text-center w_red">
					<view:_coupon_error>
						<span class="glyphicon glyphicon-warning-sign"></span> <?=$_REQUEST['coupon_error'];?>
					</view:_coupon_error>
					<?=renderViewIf(isset($_REQUEST['coupon_error']),'_coupon_error');?>
				</div>
			</div>
		</div>
		<div class="col-xs-12 col-sm-6">
			<div class="row">
				<div class="col-sm-3">
					<div class="text-muted">Gift Cards</div>
				</div>
				<div class="col-sm-5">
					<input class="form-control" type="text" id="giftcard" name="giftcard_code" value="<?=$_REQUEST['giftcard'];?>">
				</div>
				<div class="col-sm-4">
					<button class="btn btn-primary" type="button" onclick="pageApplyGiftcard();"><span class="glyphicon glyphicon-credit-card"></span> Apply Giftcard</button>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-12 text-center w_red">
					<view:_giftcard_error>
						<span class="glyphicon glyphicon-warning-sign"></span> <?=$_REQUEST['giftcard_error'];?>
					</view:_giftcard_error>
					<?=renderViewIf(isset($_REQUEST['giftcard_error']),'_giftcard_error');?>
				</div>
			</div>
		</div>
	</div>
</view:_coupon>
<view:_creditcardfields>
			<div class="w_bigger w_dblue w_pad">
				<div class="w_right" style="margin:4px 4px 0 0;">
					<input type="checkbox" id="saship" onclick="return pageSameAsShipping(this.checked);" /> <label for="saship" class="w_small"> Same as Ship To</label>
				</div>
				<span class="glyphicon glyphicon-credit-card"></span>
				Credit Card
			</div>

			<table class="formtable">
				<tr><td>Name</td><td><?=buildFormField('orders','billtoname',array('requiredmsg'=>"Bill To Name is required"));?></td></tr>
				<tr><td>ZipCode</td><td><?=buildFormField('orders','billtozipcode',array('style'=>'width:100px;','requiredmsg'=>"Bill To Zipcode is required"));?></td></tr>
				<tr><td>Phone</td><td><?=buildFormField('orders','billtotelephone',array('requiredmsg'=>"Bill To Telephone is required"));?></td></tr>
				<tr><td>Email</td><td><?=buildFormField('orders','billtoemail',array('requiredmsg'=>"Bill To Email is required", '_required'=>1));?></td></tr>
				<tr><td>Credit Card #</td><td><?=buildFormField('orders','cc_num',array('requiredmsg'=>"Credit Card number is required"));?></td></tr>
				<tr>
					<td>Expire Date</td>
					<td nowrap>
						<?=buildFormField('orders','cc_month',array('message'=>'--month--','requiredmsg'=>"Credit Card Expire Month is required"));?>
						<?=buildFormField('orders','cc_year',array('message'=>'--year--','requiredmsg'=>"Credit Card Expire Year is required"));?>
					</td>
				</tr>
				<tr><td>
					Verification Code</td><td><?=buildFormField('orders','cc_cvv2',array('style'=>"width:75px;",'requiredmsg'=>"Credit Card Verification Code (back of card) is required"));?>
					<a href="#" onclick="showHide('cvvhelp');return false;" class="w_link w_lblue">what's this?</a>
					<div id="cvvhelp" class="w_pointer" title="click to close" style="display:none;position:absolute;background:#FFF;z-index:999;" onclick="hideId(this.id);">
						<img src="/wfiles/icons/pymt/cvv2.gif" border="0" alt="cvv code help"/>
					</div>
				</td></tr>
			</table>

			<div align="right" style="margin:10px 10px 0 0;">
				<table border="0" cellspacing="2" cellpadding="2"><tr>
					<td><img src="/wfiles/iconsets/48/visa.png" alt="We accept Visa" border="0" height="48" /></td>
					<td><img src="/wfiles/iconsets/48/mastercard.png" alt="We accept Mastercard" border="0" height="48" /></td>
					<td><img src="/wfiles/iconsets/48/amex.png" alt="We accept American Express" border="0" height="48" /></td>
					<td><img src="/wfiles/iconsets/48/discover.png" alt="We accept Discover" border="0" height="48" /></td>
					<td><img src="/wfiles/iconsets/48/secure.png" border="0" alt="This page is secure" height="48" /></td>
				</tr></table>
			</div>
</view:_creditcardfields>


<view:_cart>
	<div class="w_bigger w_bold"><span class="glyphicon glyphicon-shopping-cart"></span> Cart Contents</div>
	<form class="form form-inline" id="cartform" name="cartform" onsubmit="return ajaxSubmitForm(this,'cart_contents');">
		<input type="hidden" name="func" value="" />
		<input type="hidden" name="_template" value="1" />
		<input type="hidden" name="usps_verified" value="0" />
	<div id="cart_table">
		<?=renderView('_cart_table',$cart,array('-alias'=>'cart'));?>
	</div>
	<hr size="1" style="margin-top:15px;" class="clearfix" />
	<?=renderView('_coupon',$cart,array('-alias'=>'cart'));?>
	<hr size="1" style="margin-top:15px;" class="clearfix" />
    <div class="row">
		<div class="col-xs-12 col-sm-6">
			<div class="w_bigger w_dblue w_pad"><span class="glyphicon glyphicon-user"></span> Ship To</div>
			<table class="formtable">
				<tr><td>Name</td><td><?=buildFormField('orders','shiptoname',array('requiredmsg'=>"Ship To Name is required"));?></td></tr>
				<tr><td>Address1</td><td><?=buildFormField('orders','shiptoaddress1',array('requiredmsg'=>"Ship To Address1 is required"));?></td></tr>
				<tr><td>Address2</td><td><?=buildFormField('orders','shiptoaddress2');?></td></tr>
				<tr><td>City</td><td><?=buildFormField('orders','shiptocity',array('requiredmsg'=>"Ship To City is required"));?></td></tr>
				<tr><td>State</td><td><div id="addedit_shiptostate_content"><?=buildFormField('orders','shiptostate',array('style'=>'width:300px','requiredmsg'=>"Ship To State is required",'onchange'=>"shiptoStateChanged();"));?></div></td></tr>
				<tr><td>ZipCode</td><td><?=buildFormField('orders','shiptozipcode',array('style'=>'width:100px;','requiredmsg'=>"Ship To Zipcode is required",'onblur'=>"shiptoStateChanged();"));?></td></tr>
				<tr><td>Phone</td><td><?=buildFormField('orders','shiptotelephone',array('requiredmsg'=>"Ship To Telephone is required"));?></td></tr>
				<tr><td>Email</td><td><?=buildFormField('orders','shiptoemail',array('requiredmsg'=>"Ship To Email is required"));?></td></tr>
			</table>
		</div>
		<div id="creditcardfields" class="col-xs-12 col-sm-6">
			<?=renderViewIf($cart['totals']['total'] > 0,'_creditcardfields',$cart,array('-alias'=>'cart'));?>
		</div>
		<br clear="both">
		<div align="right" style="margin:10px 10px 0 0;">
			<div onclick="ajaxSubmitForm(document.addedit,'centerpop',300,'storePopupOpen');return false;" class="process_order"></div>
			<div class="w_pad w_bigger">Order Amount: $ <span id="bill_amount"><?=formatMoney($cart['totals']['total']);?></span></div>
		</div>
	</div>
	<div class="row">
		<div class="col-xs-12 text-right">
			<button class="btn btn-lg btn-success" type="button" onclick="document.cartform.func.value='process_order';ajaxSubmitForm(document.cartform,'centerpop');">
				<span class="glyphicon glyphicon-tint"></span>
				Process Order
			</button>
		</div>
	</div>
	</form>
</view:_cart>

<view:_cart_table>
	<table class="table table-condensed table-hover">
	<tr>
		<th colspan="3"></td>
		<th class="text-center">Quantity</th>
		<th class="text-center">Each</th>
		<th class="text-center">Total</th>
	</tr>
	<view:_cartitem>
		<tr>
			<td class="text-center">
				<a href="#" onclick="return templateDeleteCartItem('<?=$item['sku'];?>');" data-tooltip="Remove from cart" data-tooltip_position="bottom">
					<span class="glyphicon glyphicon-remove"></span>
				</a>
			</td>
			<td><?=$item['sku'];?></td>
			<td><?=$item['description'];?></td>
			<td class="text-right"><input name="qty_<?=$item['sku'];?>" id="qty_<?=$item['sku'];?>" class="form-control" style="width:80px;text-align:right;" value="<?=$item['quantity'];?>" onchange="pageUpdateCart(this.id,this.value);" /></td>
			<td class="text-right">$ <?=formatMoney($item['price']);?></td>
			<td class="text-right">$ <?=formatMoney($item['subtotal']);?></td>
		</tr>
	</view:_cartitem>
	<?=renderEach('_cartitem',$cart['items'],array('-alias'=>'item'));?>
	<tr>
		<th colspan="3" align="right">Totals</th>
		<th class="text-right"></th>
		<th></th>
		<th class="text-right">$ <?=formatMoney($cart['totals']['subtotal']);?></th>
	</tr>
	<view:_discount>
	<tr>
		<td colspan="3" class="text-right w_bold">Discount</th>
		<td colspan="2" class="text-right"><?=$cart['totals']['discount_description'];?></th>
		<td class="text-right red">- $ <?=formatMoney($cart['totals']['discount']);?></th>
	</tr>
	</view:_discount>
	<?=renderViewIf($cart['totals']['discount']>0,'_discount',$cart,array('-alias'=>"cart"));?>
	<view:_tax>
		<tr>
			<td colspan="3" class="text-right w_bold"><?=$cart['totals']['tax_title'];?></th>
			<td colspan="2" class="text-right"><?=$cart['totals']['tax_description'];?></th>
			<td class="text-right">$ <?=formatMoney($cart['totals']['tax']);?></th>
		</tr>
	</view:_tax>
	<?=renderViewIf($cart['totals']['tax']>0,'_tax',$cart,array('-alias'=>"cart"));?>
	<view:_shipping>
	<tr>
		<td colspan="3" class="text-right w_bold"><?=$cart['totals']['shipping_title'];?></th>
		<td colspan="2" class="text-right"><?=$cart['totals']['shipping_description'];?></th>
		<td class="text-right">$ <?=formatMoney($cart['totals']['shipping']);?></th>
	</tr>
	</view:_shipping>
	<?=renderViewIf($cart['totals']['shipping']>0,'_shipping',$cart,array('-alias'=>"cart"));?>
	<!-- TOTAL -->
	<tr>
		<th colspan="3" align="right">Amount To Charge</th>
		<th class="text-right"></th>
		<th></th>
		<th class="text-right">$ <?=formatMoney($cart['totals']['total']);?></th>
	</tr>
	</table>
	<?=buildOnLoad("setText('bill_amount','".formatMoney($cart['totals']['total'])."');pageUpdateCart();");?>
</view:_cart_table>


<view:_empty>
	Your Cart is currently empty.
	<?=buildOnLoad("pageUpdateCart();");?>
</view:_empty>

<view:default>
<div class="row whiteback" style="padding-left:0px;padding-right:0px;">
	<div class="col-xs-12">
		<div class="padtop">
			<div class="text-left pad15 clearfix">
				<h1>Checkout</h1>
				<div id="cart_contents" style="padding:15px 0 15px 0;">
				<?=renderViewIfElse($cart['totals']['quantity']>0,'_cart','_empty',$cart,array('-alias'=>'cart'));?>
				</div>
			</div>
		</div>
	</div>
</div>
<div style="display:none;"><div id="nulldiv"></div></div>
</view:default>

<view:address_not_found>
	<div class="w_big w_bold"><img src="/wfiles/iconsets/32/alert.png" border="0" class="w_middle" /> USPS Address Verification</div>
	<div class="w_red w_bold" align="center" class="w_pad"><?=$verify['address'][0]['out']['err'];?></div>
	<div style="border-top:1px dashed #ccc;margin:15px;padding-top:10px;">We are unable to verify your address so your order has not been processed. Please correct your address and press "process order" again.</div>
</view:address_not_found>

<view:address_diff>
	<div class="w_big w_bold" style="border-bottom:1px dashed #ccc;margin-bottom:10px;padding-bottom:10px;"><img src="/wfiles/iconsets/32/alert.png" border="0" class="w_middle" /> USPS Address Verification</div>
	<div class="w_dblue w_bold" class="w_pad">Did you mean...?</div>
	<div class="w_dblue">Please select the correct address below.</div>
	<div style="border-top:1px dashed #ccc;margin:15px;padding-top:10px;">
		<div class="col-xs-12 col-sm-6">
			<div class=" w_bold">Your Entry<hr size="1"></div>
			<?
				foreach($verify['address'][0]['in'] as $key=>$val){
					if(strtolower($key)=='zip4'){continue;}
					$your_address .= '			<div>'.$val.'</div>'."\n";
				}
				echo $your_address;
			?>
			<button onclick="return useMyAddress();" class="w_white btn btn-block" style="padding:3px;">Use This Address</button>
		</div>
		<div class="visible-xs col-xs-12 text-center w_bold w_padding w_margin">OR</div>
		<div class="col-xs-12 col-sm-6">
			<div class="w_bold w_dblue">USPS Entry<hr size="1"></div>
			<?
				foreach($verify['address'][0]['out'] as $key=>$val){
					if(strtolower($key)=='zip4'){continue;}
					$class='';
					if(in_array($key,$verify['address'][0]['diff'])){$class=' class="w_bold w_red"';}
					$usps_address .= '			<div id="usps_'.$key.'"  '.$class.'>'.$val.'</div>'."\n";
				}
				echo $usps_address;
			?>
			<button onclick="return useUSPSAddress();" class="w_bold w_white btn btn-block" style="background-color:#3a9cee;padding:3px;">Use This Address</button>
		</div>
		<br clear="both">
	</div>
</view:address_diff>

<view:process_order_success>
	<div class="w_centerpop_content">
		<b><span class="icon-emo-happy"></span> Thank you for your order!</b>  We have emailed you a receipt for your records.
		<div class="pad15"><b>Note:</b> This transaction will appear on your billing statement as "DEVMAVIN".</div>
	</div>
	<?=renderView('_receipt',$order,$orderopts);?>
	<?=renderView('_receipt',$order,$notifyopts);?>
</view:process_order_success>

<view:process_order_failed>
	<div class="w_centerpop_content">
		<div class="w_bold"><span class="icon-emo-unhappy"></span> Processing your card failed!</div>
		<div class="red pad15"><?=$order['cc_result'];?></div>
		<div class="pad15">Please check your card and try again.</div>
	</div>
</view:process_order_failed>
