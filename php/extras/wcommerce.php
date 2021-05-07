<?php
/*
	wcommerce - WaSQL functions to support an e-commerce site
	References:
		https://ps.w.org/woocommerce/assets/screenshot-1.jpg?rev=2366418

*/
//$progpath=dirname(__FILE__);
$ok=wcommerceInit();
//create wcommerce_files on document root if it does not exist
$wcommerce_files_path=$_SERVER['DOCUMENT_ROOT'].'/wcommerce_files';
buildDir($wcommerce_files_path);
if(!is_dir($wcommerce_files_path)){
	echo "wcommerce ERROR: unable to create {$wcommerce_files_path}.  Manually create it.";
	exit;
}
function wcommerceBuyersList(){
	global $PAGE;
	$opts=array(
		'-table'=>'_users',
		'-tableclass'=>"table striped bordered responsive",
		'-filter'=>"_id in (select user_id from wcommerce_orders)",
		'-sorting'=>1,
		'-listfields'=>'_id,firstname,lastname,city,state,zip,country,order_count,last_order,note',
		'-edit'=>'note'
	);
	return databaseListRecords($opts);
}
function wcommerceOrdersList(){
	global $PAGE;
	$opts=array(
		'-table'=>'wcommerce_orders',
		'-tableclass'=>"table striped bordered responsive",
		'-edit'=>1,
		'-sorting'=>1,
		'-listfields'=>'_id,user_id,date_ordered,date_shipped,tracking_number,items_count,order_total,shipto_city,shipto_state,shipto_country',
		'-order'=>'date_shipped desc'
	);
	return databaseListRecords($opts);
}
function wcommerceProductsList(){
	global $PAGE;
	$opts=array(
		'-table'=>'wcommerce_products',
		'-tableclass'=>"table striped bordered responsive",
		'-listfields'=>'_id,active,name,quantity,price,sale_price,sku,size,color,material,weight,photo_1,photo_2',
		'photo_1_image'=>1,
		'photo_2_image'=>1,
		'-edit'=>1,
		'-sorting'=>1,
		'-order'=>'active desc,name',
		'quantity_class'=>'align-right',
		'price_class'=>'align-right',
		'sale_price_class'=>'align-right',
		'weight_options'=>array(
			'class'=>'align-right',
			'displayname'=>'Weight (oz)'
		),
		'active_options'=>array(
			'onclick'=>"return wcommerceManageSetActive(this);",
			'data-id'=>"%_id%",
			'data-table'=>"wcommerce_products",
			'data-value'=>"%active%",
			'checkmark'=>1,
			'checkmark_icon'=>'icon-mark w_success',
			'icon_0'=>'icon-block w_danger'
		),
		'name_options'=>array(
			'onclick'=>"return wcommerceNav(getParent(this,'td'));",
			'data-href'=>"/t/1/{$PAGE['name']}/manage_products/addedit/%_id%",
			'data-div'=>'centerpop'
		),
		'_id_options'=>array(
			'onclick'=>"return wcommerceNav(getParent(this,'td'));",
			'data-href'=>"/t/1/{$PAGE['name']}/manage_products/addedit/%_id%",
			'data-div'=>'centerpop'
		),
	);
	$opts['-pretable']=<<<ENDOFPRETABLE
<div style="display:flex;justify-content:flex-end;align-items:center">
	<button type="button" class="button is-small btn btn-small is-success w_success" onclick="wcommerceNav(this);" data-href="/t/1/{$PAGE['name']}/manage_products/addedit/0" data-div="centerpop"><span class="icon-plus"></span> <translate>Add</translate></button>
</div>
ENDOFPRETABLE;
	return databaseListRecords($opts);
}
function wcommerceProductsAddedit($id=0){
	global $PAGE;
	$opts=array(
		'-table'=>'wcommerce_products',
		'-enctype'=>'multipart/form-data',
		'-action'=>"/t/1/{$PAGE['name']}/manage_products/list",
		'-onsubmit'=>"return ajaxSubmitForm(this,'wcommerce_products_content')",
		'-fields'=>getView('manage_products_addedit_fields'),
		'-style_all'=>'width:100%',
		'name_options'=>array(
			'inputtype'=>'text',
		),
		'price_options'=>array(
			'inputtype'=>'number',
			'step'=>'any',
			'min'=>0
		),
		'sale_price_options'=>array(
			'inputtype'=>'number',
			'step'=>'any',
			'min'=>0
		),
		'details_options'=>array(
			'inputtype'=>'textarea',
			'height'=>'150'
		),
		'related_products_options'=>array(
			'inputtype'=>'textarea',
			'height'=>'150'
		)
	);
	//set 10 photo field options
	for($x=1;$x<11;$x++){
		$opts["photo_{$x}_options"]=array(
			'inputtype'=>'file',
			'autonumber'=>1,
			'path'=>'wcommerce_files'
		);
	}
	if($id > 0){
		$opts['_id']=$id;
	}
	else{
		$opts['active']=1;
	}
	return addEditDBForm($opts);
}
function wcommerceSettingsList(){
	global $PAGE;
	$opts=array(
		'-table'=>'wcommerce_settings',
		'-tableclass'=>"table striped bordered responsive",
		'-edit'=>1,
		'-sorting'=>1,
		'-order'=>'name',
		'-navonly'=>1,
		'name_options'=>array(
			'onclick'=>"return wcommerceNav(getParent(this,'td'));",
			'data-href'=>"/t/1/{$PAGE['name']}/manage_settings/addedit/%_id%",
			'data-div'=>'centerpop'
		)
	);
	$opts['-pretable']=<<<ENDOFPRETABLE
<div style="display:flex;justify-content:flex-end;align-items:center">
	<button type="button" class="button is-small btn btn-small is-success w_success" onclick="wcommerceNav(this);" data-href="/t/1/{$PAGE['name']}/manage_settings/addedit/0" data-div="centerpop"><span class="icon-plus"></span> <translate>Add</translate></button>
</div>
ENDOFPRETABLE;
	return databaseListRecords($opts);
}
function wcommerceSettingsAddedit($id=0){
	global $PAGE;
	$opts=array(
		'-table'=>'wcommerce_settings',
		'-action'=>"/t/1/{$PAGE['name']}/manage_settings",
		'-onsubmit'=>"return ajaxSubmitForm(this,'wcommerce_content')",
		'name_options'=>array(
			'inputtype'=>'text',
			'class'=>'input',
			'width'=>400
		),
		'value_options'=>array(
			'inputtype'=>'textarea',
			'class'=>'textarea',
			'width'=>400,
			'height'=>100
		)
	);
	if($id > 0){
		$opts['_id']=$id;
	}
	return addEditDBForm($opts);
}
function wcommerceInit($force=0){
	//check for wcommerce page
	$rec=getDBRecord(array('-table'=>'_pages','-where'=>"name='wcommerce' or permalink='wcommerce'",'-fields'=>'_id,name,permalink'));
	if($force==1 || !isset($rec['_id'])){
		//create a wcommerce page
		$ok=addDBRecord(array(
			'-table'=>'_pages',
			'name'=>'wcommerce',
			'permalink'=>'wcommerce',
			'body'=>wcommercePageBody(),
			'js'=>wcommercePageJs(),
			'controller'=>wcommercePageController(),
			'description'=>'wcommerce management page',
			'_template'=>2,
			'-upsert'=>'body,controller,description,js'
		));
	}
	//check for schema
	if($force==1 || !isDBTable('wcommerce_products')){
		$ok=databaseAddMultipleTables(wcommerceSchema());
	}
}
function wcommercePageBody(){
	return <<<ENDOFBODY
<view:manage_portal>
<div class="w_bold w_biggest w_success">
	<span class="icon-handshake w_success"></span> wCOMMERCE
</div>
<ul class="nav-tabs w_black">
	<!-- Orders -->
	<li class="active"><a href="#" onclick="return wcommerceNavTab(this);" data-href="/t/1/<?=pageValue('name');?>/manage_orders"><span class="icon-package"></span> <translate>Orders</translate></a></li>
	<!-- Products -->
	<li><a href="#" onclick="return wcommerceNavTab(this);" data-href="/t/1/<?=pageValue('name');?>/manage_products"><span class="icon-tag"></span> <translate>Products</translate></a></li>
	<!-- Settings -->
	<li><a href="#" onclick="return wcommerceNavTab(this);" data-href="/t/1/<?=pageValue('name');?>/manage_settings"><span class="icon-gear"></span> <translate>Settings</translate></a></li>
	<!-- Buyers -->
	<li><a href="#" onclick="return wcommerceNavTab(this);" data-href="/t/1/<?=pageValue('name');?>/manage_buyers"><span class="icon-users"></span> <translate>Buyers</translate></a></li>
	<!-- User menu -->
	<li><a href="#" onclick="return false" class="dropdown"><span class="icon-user"></span> <?=\$USER['username'];?></a>
		<div>
			<ul class="nav-list">
				<!-- Profile -->
				<li><a href="#" onclick="return wcommerceNavTab(this);" data-href="/t/1/<?=pageValue('name');?>/user_profile"><span class="icon-user"></span> <translate>Profile</translate></a></li>
				<!-- Logoff -->
				<li class="right"><a href="/<?=pageValue('name');?>?_logoff=1"><span class="icon-logout"></span> <translate>Logoff</translate></a></li>
			</ul>
		</div>
	</li>
</ul>
<div id="wcommerce_content">
	<?=renderView('manage_orders',\$orders,'orders');?>
</div>
<div style="display:none"><div id="wcommerce_nulldiv"></div></div>
</view:manage_portal>

<view:manage_orders>
<div class="w_bold w_bigger">
	<span class="icon-package w_success"></span> <translate>Orders</translate>
</div>
<div id="wcommerce_orders_content">
	<?=wcommerceOrdersList();?>
</div>
</view:manage_orders>

<view:manage_products>
<div class="w_bold w_bigger">
	<span class="icon-tag w_success"></span> <translate>Products</translate>
</div>
<div id="wcommerce_products_content">
	<?=wcommerceProductsList();?>
</div>
</view:manage_products>

<view:manage_products_list>
	<?=wcommerceProductsList();?>
	<?=buildOnLoad("removeId('centerpop');");?>
</view:manage_products_list>

<view:manage_products_addedit>
<div class="w_centerpop_title">Products AddEdit</div>
<div class="w_centerpop_content" style="width:70vw;">
	<?=wcommerceProductsAddedit(\$id);?>
	<?=buildOnLoad("centerObject('centerpop');");?>
</div>
</view:manage_products_addedit>

<view:manage_products_addedit_fields>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;"><label><translate>Active</translate></label>[active]</div>
	<div style="margin:5px;flex-grow:1;"><label><translate>Name</translate></label>[name]</div>
</div>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;"><label><translate>SKU</translate></label>[sku]</div>
	<div style="margin:5px;"><label><translate>Price</translate></label>[price]</div>
	<div style="margin:5px;"><label><translate>Sale Price</translate></label>[sale_price]</div>
	<div style="margin:5px;"><label><translate>Quantity</translate></label>[quantity]</div>
</div>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;"><label><translate>Weight  (Ounces)</translate></label>[weight]</div>
	<div style="margin:5px;"><label><translate>Size (Optional)</translate></label>[size]</div>
	<div style="margin:5px;"><label><translate>Color (Optional)</translate></label>[color]</div>
	<div style="margin:5px;"><label><translate>Material (Optional)</translate></label>[material]</div>
</div>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;"><label><translate>Photo</translate></label>[photo_1]</div>
	<div style="margin:5px;"><label><translate>Photo</translate></label>[photo_2]</div>
	<div style="margin:5px;"><label><translate>Photo</translate></label>[photo_3]</div>
	<div style="margin:5px;"><label><translate>Photo</translate></label>[photo_4]</div>
	<div style="margin:5px;"><label><translate>Photo</translate></label>[photo_5]</div>
</div>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;"><label><translate>Photo</translate></label>[photo_6]</div>
	<div style="margin:5px;"><label><translate>Photo</translate></label>[photo_7]</div>
	<div style="margin:5px;"><label><translate>Photo</translate></label>[photo_8]</div>
	<div style="margin:5px;"><label><translate>Photo</translate></label>[photo_9]</div>
	<div style="margin:5px;"><label><translate>Photo</translate></label>[photo_10]</div>
</div>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;flex-grow:1;"><label><translate>Details</translate></label>[details]</div>
	<div style="margin:5px;flex-grow:1;"><label><translate>Related Products</translate></label>[related_products]</div>
</div>

</view:manage_products_addedit_fields>

<view:manage_buyers>
<div class="w_bold w_bigger">
	<span class="icon-users w_success"></span> <translate>Buyers</translate>
</div>
<div id="wcommerce_buyers_content">
	<?=wcommerceBuyersList();?>
</div>
</view:manage_buyers>

<view:manage_settings>
<div class="w_bold w_bigger">
	<span class="icon-gear w_success"></span> <translate>Settings</translate>
</div>
<div id="wcommerce_settings_content">
	<?=wcommerceSettingsList();?>
</div>
</view:manage_settings>

<view:manage_settings_addedit>
<div class="w_centerpop_title">Settings AddEdit</div>
<div class="w_centerpop_content">
	<?=wcommerceSettingsAddedit(\$id);?>
	<?=buildOnLoad("centerObject('centerpop');");?>
</div>
</view:manage_settings_addedit>

<view:customer_portal>
<div class="w_bold w_biggest w_success">
	<translate>Customer Portal</translate>
</div>
<ul class="nav-tabs">
	<!-- Orders -->
	<li class="active"><a href="#" onclick="return wcommerceNavTab(this);" data-href="/t/1/<?=pageValue('name');?>/customer_orders"><span class="icon-package"></span> <translate>Orders</translate></a></li>
	<!-- User menu -->
	<li style="margin-left: auto;"><a href="#" onclick="return false" class="dropdown"><span class="icon-user"></span> <?=\$USER['username'];?></a>
		<div>
			<ul class="nav-list">
				<!-- Profile -->
				<li><a href="#" onclick="return wcommerceNavTab(this);" data-href="/t/1/<?=pageValue('name');?>/user_profile"><span class="icon-user"></span> <translate>Profile</translate></a></li>
				<!-- Logoff -->
				<li class="right"><a href="/<?=pageValue('name');?>?_logoff=1"><span class="icon-logout"></span> <translate>Logoff</translate></a></li>
			</ul>
		</div>
	</li>
</ul>
</view:customer_portal>

<view:login>
<?=userLoginForm(array('-action'=>'/'.pageValue('name')));?>
</view:login>
ENDOFBODY;
}
function wcommercePageJs(){
	return <<<ENDOFJS
function wcommerceNavTab(el){
	wacss.setActiveTab(el);
	return wcommerceNav(el);
}
function wcommerceNav(el){
	let p=el.dataset;
	p.setprocessing=0;
	let div=el.dataset.div||'wcommerce_content';
	let href=el.dataset.href;
	return ajaxGet(href,div,p);
}
function wcommerceManageSetActive(el){
	let p=getParent(el,'td');
	let v=parseInt(p.dataset.value);
	let s=el.querySelector('span');
	if(v==1){
		p.dataset.value=0;
		s.className='icon-block w_danger';
	}
	else{
		p.dataset.value=1;
		s.className='icon-mark w_success';
	}
	let url='/t/1/wcommerce/manage_setactive/'+p.dataset.table+'/'+p.dataset.id+'/'+p.dataset.value;
	return ajaxGet(url,'wcommerce_nulldiv',{setprocessing:0});
}
ENDOFJS;
}
function wcommercePageController(){
	return <<<ENDOFCONTROLLER
<?php
//require user
if(!isUser()){
	setView('login',1);
	return;
}
global \$USER;
global \$PASSTHRU;
loadExtras('wcommerce');
switch(strtolower(\$PASSTHRU[0])){
	case 'user_profile':
		setView(\$PASSTHRU[0],1);
	break;
	case 'manage_setactive':
		\$table=\$PASSTHRU[1];
		\$id=(integer)\$PASSTHRU[2];
		\$v=(integer)\$PASSTHRU[3];
		\$ok=editDBRecordById(\$table,\$id,array('active'=>\$v));
		echo "Table:{\$table}, id:{\$id}, Value: {\$v}, Result: ".printValue(\$ok);exit;
	break;
	case 'manage_develop':
		\$ok=wcommerceInit(1);
		setView('manage_portal');
	break;
	case 'manage_orders':
		setView(\$PASSTHRU[0],1);
	break;
	case 'manage_products':
		switch(strtolower(\$PASSTHRU[1])){
			case 'list':
				setView('manage_products_list',1);
				return;
			break;
			case 'addedit':
				\$id=\$PASSTHRU[2];
				setView('manage_products_addedit',1);
				return;
			break;
		}
		setView(\$PASSTHRU[0],1);
	break;
	case 'manage_settings':
		switch(strtolower(\$PASSTHRU[1])){
			case 'addedit':
				\$id=\$PASSTHRU[2];
				setView('manage_settings_addedit',1);
				return;
			break;
		}
		setView(\$PASSTHRU[0],1);
	break;
	case 'manage_buyers':
		setView(\$PASSTHRU[0],1);
	break;
	case 'customer_orders':
		setView(\$PASSTHRU[0],1);
	break;
	case 'customer_profile':
		setView(\$PASSTHRU[0],1);
	break;
	default:
		if(isAdmin()){
			setView('manage_portal');
		}
		else{
			setView('customer_portal');
		}
	break;
}
?>
ENDOFCONTROLLER;
}

function wcommerceSchema(){
	return <<<ENDOFSCHEMA
wcommerce_products
	name varchar(150) NOT NULL
	sku varchar(25)
	category varchar(50)
	size varchar(25)
	color varchar(25)
	material varchar(25)
	quantity int NOT NULL Default 1
	price float(12,2)
	sale_price float(12,2)
	active tinyint(1) NOT NULL Default 0
	onsale tinyint(1) NOT NULL Default 0
	featured tinyint(1) NOT NULL Default 0
	related_products JSON
	details text
	photo_1 varchar(255)
	photo_2 varchar(255)
	photo_3 varchar(255)
	photo_4 varchar(255)
	photo_5 varchar(255)
	photo_6 varchar(255)
	photo_7 varchar(255)
	photo_8 varchar(255)
	photo_9 varchar(255)
	photo_10 varchar(255)
	weight int
wcommerce_products_reviews
	product_id int NOT NULL
	email varchar(255)
	comment varchar(500)
	review int
	active tinyint(1) NOT NULL Default 0
wcommerce_orders
	user_id int
	shipto_firstname varchar(150)
	shipto_lastname varchar(150)
	shipto_company varchar(150)
	shipto_address varchar(255)
	shipto_city varchar(30)
	shipto_state varchar(20)
	shipto_zip varchar(20)
	shipto_country varchar(5)
	shipto_phone varchar(30)
	shipto_email varchar(255)
	date_ordered datetime
	date_shipped datetime
	date_delivered datetime
	shipmethod_code varchar(25)
	shipmethod_price float(12,2)
	coupon varchar(25)
	items_count int
	items_total int
	discount float(12,2)
	tax float(12,2)
	order_total float(12,2)
	payment_description varchar(255)
	payment_name varchar(150)
	payment_response varchar(255)
	payment_status varchar(25)
	payment_code varchar(150)
	tracking_number varchar(40)
wcommerce_orders_items
	order_id int NOT NULL Default 0
	product_id int NOT NULL
	guid varchar(150) NOT NULL
	name varchar(150) NOT NULL
	sku varchar(25)
	size varchar(25)
	color varchar(25)
	material varchar(25)
	quantity int NOT NULL Default 1
	price float(12,2)
	sale_price float(12,2)
	active tinyint(1) NOT NULL Default 0
	related_products JSON
	details text
	photo_1 varchar(255)
wcommerce_coupons
	coupon varchar(25) NOT NULL UNIQUE
	description varchar(255)
	discount float(12,2)
	expire_date date
	times_used int NOT NULL Default 0
	times_valid int NOT NULL Default 1
	active tinyint(1) NOT NULL Default 0
	product_ids json
wcommerce_settings
	name varchar(100) NOT NULL UNIQUE
	value json
ENDOFSCHEMA;
}
?>