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
function wcommerceAdd2Cart($id,$params=array()){
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name],$id,$params);
	}
	$product=getDBRecordById('wcommerce_products',$id);
	if(!isset($product['_id'])){
		return array('status'=>'failed','error'=>'no product with that id');
	}
	$rtn=$product;
	$fields=getDBFields('wcommerce_orders_items');
	$guid=wcommerceCartGuid();
	$opts=array(
		'-table'=>'wcommerce_orders_items',
		'product_id'=>$product['_id'],
		'guid'=>$guid
	);
	$rec=getDBRecord($opts);
	if(isset($settings['wcommerceAdd2Cart_GroupItems']) && $settings['wcommerceAdd2Cart_GroupItems']==0){
		unset($rec);
	}
	if(isset($rec['_id'])){
		$qty=$rec['quantity']+1;
		$rtn['quantity']=$qty;
		$ok=editDBRecordById('wcommerce_orders_items',$rec['_id'],array('quantity'=>$qty));
		$rtn['status']='success';
		$rtn['cart_id']=$rec['_id'];
	}
	else{
		$rtn['quantity']=$opts['quantity']=(integer)$_REQUEST['qty'];
		//get photo
		$rtn['photos']=$product['photos']=wcommerceProductImages($product);
		if(count($product['photos'])){
			$opts['photo']=$product['photos'][0]['src'];
		}
		foreach($fields as $field){
			if(isWaSQLField($field)){continue;}
			if(isset($params['usepoints']) && $params['usepoints']==1 && $field=='price'){
				$rtn['points_order']=1;
				continue;
			}
			if((!isset($params['usepoints']) || $params['usepoints']==0) && $field=='points'){
				$rtn['price_order']=1;
				continue;
			}
			if(!isset($opts[$field])){
				if(isset($_REQUEST[$field]) && strlen($_REQUEST[$field])){
					$opts[$field]=$_REQUEST[$field];
				}
				elseif(isset($product[$field])){
					$opts[$field]=$product[$field];
				}
			}
		}
		$id=addDBRecord($opts);
		if(isNum($id)){
			$rtn['status']='success';
			$rtn['cart_id']=$id;
		}
		else{
			$rtn['status']='failed';
			$rtn['error']=$id;
		}
	}
	return $rtn;
}
function wcommerceCartGuid(){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name]);
	}
	if(isset($_COOKIE['wCommerceGUID'])){
		return $_COOKIE['wCommerceGUID'];
	}
	//expire in a year
	$guid=session_id().'_'.time();
	$expire=time()+(3600*24*365);
	$ok=commonSetCookie("wCommerceGUID", $guid, $expire);
	$_SERVER['wCommerceGUID']=$guid;
	return $guid;
}
function wcommerceBuyersList(){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name]);
	}
	global $PAGE;
	$opts=array(
		'-table'=>'_users',
		'-tableclass'=>"table striped bordered sticky",
		'-filter'=>"_id in (select user_id from wcommerce_orders)",
		'-sorting'=>1,
		'-listfields'=>'_id,firstname,lastname,city,state,zip,country,order_count,last_order,note',
		'-edit'=>'note'
	);
	return databaseListRecords($opts);
}
function wcommerceGetOrder($id){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name],$id);
	}
	$order=getDBRecordById('wcommerce_orders',$id);
	$order['items']=getDBRecords(array('-table'=>'wcommerce_orders_items','order_id'=>$order['_id']));
	return $order;
}
function wcommerceOrdersItemsList($order_id){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name],$order_id);
	}
	$order=getDBRecordById('wcommerce_orders',$order_id);
	$opts=array(
		'-table'=>'wcommerce_orders_items',
		'order_id'=>$order_id,
		'-relate'=>array('shipped_by'=>'_users'),
		'-tableclass'=>'table striped bordered',
		'-tablestyle'=>'background-color:inherit;',
		'-listfields'=>'label,date_shipped,shipped_by,date_delivered,category,name,photo,size,color,quantity,price,subtotal',
		'subtotal_class'=>'align-right',
		'quantity_class'=>'align-right',
		'date_shipped_displayname'=>'Shipped',
		'date_delivered_displayname'=>'Delivered',
		'price_class'=>'align-right',
		'size_class'=>'align-center',
		'-hidesearch'=>1,
		'-sumfields'=>'subtotal',
		'-results_eval'=>'wcommerceOrdersItemsListExtra'
	);
	if(!strlen($order['date_shipped'])){
		$opts['-posttable']=<<<ENDOFPOST
		<div class="align-right" style="margin-top:10px;">
		<button class="btn w_blue" type="button" data-id="{$order_id}" onclick="wcommerceOrdersShip(this);">Ship This Order</button>
		</div>
ENDOFPOST;
	}
	elseif(!strlen($order['date_delivered'])){
		$opts['-posttable']=<<<ENDOFPOST
		<div class="align-right" style="margin-top:10px;">
		<button class="btn w_yellow" type="button" data-id="{$order_id}" onclick="wcommerceOrdersDeliver(this);">Deliver This Order</button>
		</div>
ENDOFPOST;
	}
	return databaseListRecords($opts);
}
function wcommerceOrdersItemsListExtra($recs){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name],$recs);
	}
	global $USER;
	$settings=wcommerceGetSettings();
	foreach($recs as $i=>$rec){
		$recs[$i]['subtotal']=number_format($rec['price']*$rec['quantity'],2);
		$recs[$i]['photo']='<img src="'.$rec['photo'].'" style="height:32px;width:auto;" onclick="wacss.showImage(this);" />';
		if(isset($rec['shipped_by_ex']['firstname'])){
			$recs[$i]['shipped_by']="{$rec['shipped_by_ex']['firstname']} {$rec['shipped_by_ex']['lastname']}";
		}
		if(strlen($rec['date_ordered'])){
			$recs[$i]['date_ordered']='<div title="'.$rec['date_ordered'].'"><span class="icon-calendar w_blue"></span> '.date('D M jS',strtotime($rec['date_ordered'])).'</div>';
		}
		if(strlen($rec['date_shipped'])){
			$recs[$i]['date_shipped']='<div title="'.$rec['date_shipped'].'"><span class="icon-package w_green"></span> '.date('D M jS',strtotime($rec['date_shipped'])).'</div>';
		}
		else{
			$recs[$i]['date_shipped']='<button class="btn w_blue" style="padding:.175rem .25rem;" type="button" data-id="'.$rec['_id'].'" onclick="wcommerceOrdersShipItem(this);">Ship</button>';
		}
		if(strlen($rec['date_delivered'])){
			$recs[$i]['date_delivered']='<div title="'.$rec['date_delivered'].'"><span class="icon-calendar-check w_gray"></span> '.date('D M jS',strtotime($rec['date_delivered'])).'</div>';
		}
		elseif(strlen($rec['date_shipped'])){
			$recs[$i]['date_delivered']='<button style="padding:.175rem .25rem;" class="btn w_yellow" type="button" data-id="'.$rec['_id'].'" onclick="wcommerceOrdersDeliverItem(this);">Deliver</button>';
		}
	}
	return $recs;
}
function wcommerceOrdersList(){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name]);
	}
	global $PAGE;
	$opts=array(
		'-table'=>'wcommerce_orders',
		'-relate'=>array('shipped_by'=>'_users'),
		'-action'=>"/t/1/{$PAGE['name']}/manage_orders/list",
		'-onsubmit'=>"return pagingSubmit(this,'wcommerce_orders_content');",
		'-tableclass'=>"table striped bordered sticky",
		'-tableheight'=>'55vh',
		'-export'=>'1',
		'-sorting'=>1,
		'-listfields'=>'_id,date_ordered,date_shipped,date_delivered,shipped_by,tracking_number,shipto_firstname,shipto_lastname,shipto_email,points_total,price_total',
		'points_total_class'=>'align-right',
		'price_total_class'=>'align-right',
		'-order'=>'date_delivered,date_shipped,date_ordered desc',
		'-results_eval'=>'wcommerceOrdersListExtra',
		'_id_options'=>array(
			'displayname'=>'OrderID',
			'class'=>'w_nowrap'
		),
		'shipto_firstname_options'=>array(
			'displayname'=>'Firstname'
		),
		'shipto_lastname_options'=>array(
			'displayname'=>'Lastname'
		),
		'shipto_email_options'=>array(
			'displayname'=>'Email'
		),
		'date_ordered_options'=>array(
			'class'=>'w_nowrap',
			'displayname'=>'Ordered'
		),
		'date_shipped_options'=>array(
			'class'=>'w_nowrap',
			'displayname'=>'Shipped'
		),
		'date_delivered_options'=>array(
			'class'=>'w_nowrap',
			'displayname'=>'Delivered'
		),
		'tracking_number_options'=>array(
			'class'=>'w_nowrap',
			'displayname'=>'Track'
		),
	);
	$opts['-quickfilters_class']='btn w_blue';
	$opts['-quickfilters']=array(
		'Add'=>array(
			'icon'=>'icon-plus',
			'onclick'=>'wcommerceNav(this);',
			'data-href'=>"/t/1/{$PAGE['name']}/manage_orders/addedit/0",
			'data-div'=>"centerpop",
			'class'=>"btn w_white"
			),
		'Open'=>array(
			'icon'=>'icon-mark',
			'filter'=>'date_shipped ib',
			'class'=>"btn w_blue"
			),
		'Shipped'=>array(
			'icon'=>'icon-package',
			'filter'=>'date_shipped nb',
			'class'=>"btn w_green"
			),
		'In Transit'=>array(
			'icon'=>'icon-spin8',
			'filter'=>'date_shipped nb;date_delivered ib',
			'class'=>"btn w_yellow"
			),
		'Delivered'=>array(
			'icon'=>'icon-spin8',
			'filter'=>'date_shipped nb;date_delivered nb',
			'class'=>"btn w_gray"
			),
	);
	return databaseListRecords($opts);
}
function wcommerceOrdersListExtra($recs){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name],$recs);
	}
	foreach($recs as $i=>$rec){
		$recs[$i]['_id']='<div style="display:flex;justify-content:space-between;align-items:center;"><div>'.$rec['_id'].'</div><button type="button" style="padding:.175rem .25rem;" class="btn w_blue" data-id="'.$rec['_id'].'" onclick="wcommerceOrdersView(this);">View</button></div>';
		if(isset($rec['shipped_by_ex']['firstname'])){
			$recs[$i]['shipped_by']="{$rec['shipped_by_ex']['firstname']} {$rec['shipped_by_ex']['lastname']}";
		}
		if(strlen($rec['date_ordered'])){
			$recs[$i]['date_ordered']='<div title="'.$rec['date_ordered'].'"><span class="icon-calendar w_blue"></span> '.date('D M jS',strtotime($rec['date_ordered'])).'</div>';
		}
		if(strlen($rec['date_shipped'])){
			$recs[$i]['date_shipped']='<div title="'.$rec['date_shipped'].'"><span class="icon-package w_green"></span> '.date('D M jS',strtotime($rec['date_shipped'])).'</div>';
		}
		else{
			$recs[$i]['date_shipped']='<button class="btn w_blue" style="padding:.175rem .25rem;" type="button" data-id="'.$rec['_id'].'" onclick="wcommerceOrdersShip(this);">Ship</button>';
		}
		if(strlen($rec['date_delivered'])){
			$recs[$i]['date_delivered']='<div title="'.$rec['date_delivered'].'"><span class="icon-calendar-check w_gray"></span> '.date('D M jS',strtotime($rec['date_delivered'])).'</div>';
		}
		else{
			$recs[$i]['date_delivered']='<button style="padding:.175rem .25rem;" class="btn w_yellow" type="button" data-id="'.$rec['_id'].'" onclick="wcommerceOrdersDeliver(this);">Deliver</button>';
		}
	}
	return $recs;
}
function wcommerceOrdersAddedit($id=0){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name],$id);
	}
	global $PAGE;
	$opts=array(
		'-table'=>'wcommerce_orders',
		'-enctype'=>'multipart/form-data',
		'-action'=>"/t/1/{$PAGE['name']}/manage_orders/list",
		'-onsubmit'=>"return ajaxSubmitForm(this,'wcommerce_orders_content')",
		'-fields'=>getView('manage_orders_addedit_fields'),
		'-style_all'=>'width:100%',
		'name_options'=>array(
			'inputtype'=>'text',
		),
		'shipto_country_options'=>array(
			'inputtype'=>'select',
			'onchange'=>"wcommerceNav(this);",
			'data-href'=>"/t/1/{$PAGE['name']}/manage_redraw",
			'data-field'=>'shipto_state',
			'data-div'=>'shipto_state_content',
			'tvals'=>wasqlGetCountries(),
			'dvals'=>wasqlGetCountries(1)
		),
		'shipto_state_options'=>array(
			'inputtype'=>'select',
			'tvals'=>wasqlGetStates(),
			'dvals'=>wasqlGetStates(1)
		),
		'shipto_address_options'=>array(
			'inputtype'=>'textarea',
			'height'=>'50'
		),
		'related_products_options'=>array(
			'inputtype'=>'textarea',
			'height'=>'150'
		)
	);
	if($id > 0){
		$opts['_id']=$id;
	}
	else{
		$opts['shipto_country']='US';
	}
	return addEditDBForm($opts);
}
function wcommerceOrdersShipped($id){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name],$id);
	}
	global $PAGE;
	global $USER;
	$opts=array(
		'-table'=>'wcommerce_orders',
		'-formname'=>'ordersshippedform',
		'-action'=>"/t/1/{$PAGE['name']}/manage_orders/list",
		'-onsubmit'=>"return ajaxSubmitForm(this,'wcommerce_orders_content')",
		'-fields'=>getView('manage_orders_shipped_fields'),
		'-editfields'=>'shipped_by,tracking_number,date_shipped',
		'-style_all'=>'width:100%',
		'_id'=>$id,
		'date_shipped'=>date('Y-m-d H:i:s'),
		'-hide'=>'delete,clone,reset',
		'-save_class'=>'btn w_green',
		'shipped_by'=>$USER['_id']
	);
	return addEditDBForm($opts);
}
function wcommerceOrdersDelivered($id){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name],$id);
	}
	global $PAGE;
	global $USER;
	$opts=array(
		'-table'=>'wcommerce_orders',
		'-formname'=>'ordersdeliveredform',
		'-action'=>"/t/1/{$PAGE['name']}/manage_orders/list",
		'-onsubmit'=>"return ajaxSubmitForm(this,'wcommerce_orders_content')",
		'-fields'=>getView('manage_orders_delivered_fields'),
		'-style_all'=>'width:100%',
		'_id'=>$id,
		'date_delivered'=>date('Y-m-d H:i:s'),
		'-hide'=>'delete,clone,reset',
		'-save_class'=>'btn w_warning'
	);
	return addEditDBForm($opts);
}
function wcommerceOrdersItemShipped($id){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name],$id);
	}
	global $PAGE;
	global $USER;
	$item=getDBRecordById('wcommerce_orders_items',$id);
	$opts=array(
		'-table'=>'wcommerce_orders_items',
		'-formname'=>'ordersitemsshippedform',
		'-action'=>"/t/1/{$PAGE['name']}/manage_orders/view/{$item['order_id']}",
		'-onsubmit'=>"return ajaxSubmitForm(this,'centerpop')",
		'-fields'=>getView('manage_orders_items_shipped_fields'),
		'-editfields'=>'shipped_by,tracking_number,date_shipped',
		'-style_all'=>'width:100%',
		'_id'=>$id,
		'date_shipped'=>date('Y-m-d H:i:s'),
		'-hide'=>'delete,clone,reset',
		'-save_class'=>'btn w_green',
		'shipped_by'=>$USER['_id']
	);
	return addEditDBForm($opts);
}
function wcommerceOrdersItemDelivered($id){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name],$id);
	}
	global $PAGE;
	global $USER;
	$item=getDBRecordById('wcommerce_orders_items',$id);
	$opts=array(
		'-table'=>'wcommerce_orders_items',
		'-formname'=>'ordersitemsdeliveredform',
		'-action'=>"/t/1/{$PAGE['name']}/manage_orders/view/{$item['order_id']}",
		'-onsubmit'=>"return ajaxSubmitForm(this,'centerpop')",
		'-fields'=>getView('manage_orders_items_delivered_fields'),
		'-style_all'=>'width:100%',
		'_id'=>$id,
		'date_delivered'=>date('Y-m-d H:i:s'),
		'-hide'=>'delete,clone,reset',
		'-save_class'=>'btn w_warning'
	);
	//check for override
	$settings=wcommerceGetSettings();
	if(!empty($settings['wcommerceOrdersItemDelivered']) && function_exists($settings['wcommerceOrdersItemDelivered'])){
		$opts=call_user_func($settings['wcommerceOrdersItemDelivered'],$opts);
	}
	return addEditDBForm($opts);
}
function wcommerceBuildField($field,$rec=array(),$val2=''){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name],$field,$rec,$val2);
	}
	$params=array();
	if(isset($rec[$field])){$params['value']=$params['-value']=$rec[$field];}
	elseif(isset($_REQUEST[$field])){$params['value']=$params['-value']=$_REQUEST[$field];}
	switch(strtolower($field)){
		case 'shipto_state':
			$opts=wasqlGetStates(2,$val2);
			$params['message']=' ----- ';
			return buildFormSelect($field,$opts,$params);
		break;
		case 'size':			
			$params['onclick']="wcommerceChangeProductAttribute(this);";
			$params['data-product_guid']=$rec['guid'];
			$params['data-product_name']=$rec['name'];
			$params['data-product_attr']=1;
			$params['class']='small';
			$name="size_{$rec['_id']}";
			$params['name']=$name;
			if(isset($_REQUEST[$name])){$params['value']=$params['-value']=$_REQUEST[$name];}
			$opts=array();
			if(is_array($rec['sizes'])){
				if(count($rec['sizes'])==1){
					return $rec['sizes'][0]['name'];
				}
				foreach($rec['sizes'] as $size){
					$opts[$size['name']]="{$size['name']}";
				}
				return buildFormButtonSelect('size',$opts,$params);
			}
			return $rec['size'];
		break;
		case 'color':
			$params['onclick']="wcommerceChangeProductAttribute(this);";
			$params['data-product_guid']=$rec['guid'];
			$params['data-product_name']=$rec['name'];
			$params['data-product_attr']=1;
			$params['class']='small';
			$name="color_{$rec['_id']}";
			$params['name']=$name;
			if(isset($_REQUEST[$name])){$params['value']=$params['-value']=$_REQUEST[$name];}
			$opts=array();
			if(is_array($rec['colors'])){
				if(count($rec['colors'])==1){
					return $rec['colors'][0]['name'];
				}
				foreach($rec['colors'] as $color){
					$opts[$color['name']]="{$color['name']}";
				}
				return buildFormButtonSelect('color',$opts,$params);
			}
			return $rec['color'];
		break;
		case 'material':
			$params['onclick']="wcommerceChangeProductAttribute(this);";
			$params['data-product_guid']=$rec['guid'];
			$params['data-product_name']=$rec['name'];
			$params['data-product_attr']=1;
			$params['class']='small';
			$name="material_{$rec['_id']}";
			$params['name']=$name;
			if(isset($_REQUEST[$name])){$params['value']=$params['-value']=$_REQUEST[$name];}
			$opts=array();
			if(is_array($rec['materials'])){
				if(count($rec['materials'])==1){
					return $rec['materials'][0]['name'];
				}
				foreach($rec['materials'] as $material){
					$opts[$material['name']]="{$material['name']}";
				}
				return buildFormButtonSelect('material',$opts,$params);
			}
			return $rec['material'];
		break;
			if(is_array($val2)){
				if(count($val2)==1){
					return $val2[0]['name'];
				}
				$opts=array();
				foreach($val2 as $val){
					$opts[$val['name']]="{$val['name']}";
				}
				return buildFormButtonSelect('material',$opts,$params);
			}
			return $val1;
		break;
	}
}
function wcommerceGetSettings(){
	global $wcommerceGetSettingsCache;
	if(is_array($wcommerceGetSettingsCache)){
		return $wcommerceGetSettingsCache;
	}
	$params['-table']='wcommerce_settings';
	$params['active']=1;
	$recs=getDBRecords($params);
	$wcommerceGetSettingsCache=array();
	foreach($recs as $rec){
		$key=trim($rec['name']);
		$value=trim($rec['value']);
		$wcommerceGetSettingsCache[$key]=$value;
	}
	return $wcommerceGetSettingsCache;
}
function wcommerceProducts($params=array()){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name],$params);
	}
	$params['-table']='wcommerce_products';
	//default active to 1
	if(!isset($params['active'])){
		$params['active']=1;
	}
	//default order
	if(!isset($params['-order'])){
		$params['-order']='sort_group,name';
	}
	$recs=getDBRecords($params);
	//group by size,color,material
	$products=array();
	foreach($recs as $rec){
		$key=strtolower(trim($rec['name']));
		$products[$key][]=$rec;
	}
	$recs=array();
	foreach($products as $key=>$precs){
		//pick the one with default=1 if it exists
		$index=0;
		foreach($precs as $p=>$prec){
			if($prec['selected']==1){
				$index=$p;
				break;
			}
		}
		if(isset($params['-index']) && isset($precs[$params['-index']])){$index=$params['-index'];}
		$rec=$precs[$index];
		//what colors, sizes, and materials does this one product have
		$atts=wcommerceGetProductAttributes($rec['name']);
		foreach($atts as $k=>$v){
			$rec[$k]=$v;
		}
		$rec['guid']=md5($rec['name']);
		$rec['photos']=wcommerceProductImages($rec);
		if(count($rec['photos']) > 1){
		}
		elseif(count($rec['photos'])==1){
			$rec['photo']=$rec['photos'][0]['src'];
		}
		else{
			$rec['photo']='/wfiles/clear.gif';
		}
		$recs[]=$rec;
	}
	return $recs;
}
function wcommerceProductImages($rec){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name],$rec);
	}
	$photos=array();
	for($p=1;$p<11;$p++){
		if(!strlen($rec["photo_{$p}"])){continue;}
		$prec=array(
			'src'=>$rec["photo_{$p}"],
			'guid'=>$rec['guid'],
			'border'=>'1px solid #fff'
		);
		if($p==1){
			$prec['border']='1px solid #ddd';
		}
		$photos[]=$prec;
	}
	return $photos;
}
function wcommerceGetProductAttributes($name){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name],$name);
	}
	global $wcommerceGetProductAttributesCache;
	if(isset($wcommerceGetProductAttributesCache[$name])){
		return $wcommerceGetProductAttributesCache[$name];
	}
	$q=<<<ENDOFQ
	select 
		name,
		group_concat(distinct color ORDER BY sort_color SEPARATOR ';') as colors,
		group_concat(distinct size ORDER BY sort_size SEPARATOR ';') as sizes,
		group_concat(distinct material ORDER BY sort_material SEPARATOR ';') as materials
	from wcommerce_products
	group by name
ENDOFQ;
	$recs=getDBRecords($q);
	//echo printValue($recs);exit;
	$wcommerceGetProductAttributesCache=array();
	foreach($recs as $rec){
		$cname=$rec['name'];
		if(strlen($rec['colors'])){
			$vals=preg_split('/\;/',$rec['colors']);
			foreach($vals as $val){
				$wcommerceGetProductAttributesCache[$cname]['colors'][]=array(
					'name'=>$val
				);
			}
		}
		if(strlen($rec['sizes'])){
			$vals=preg_split('/\;/',$rec['sizes']);
			foreach($vals as $val){
				$wcommerceGetProductAttributesCache[$cname]['sizes'][]=array(
					'name'=>$val
				);
			}
		}
		if(strlen($rec['materials'])){
			$vals=preg_split('/\;/',$rec['materials']);
			foreach($vals as $val){
				$wcommerceGetProductAttributesCache[$cname]['materials'][]=array(
					'name'=>$val
				);
			}
		}
	}
	//echo printValue($wcommerceGetProductAttributesCache);exit;
	return $wcommerceGetProductAttributesCache[$name];
}
function wcommerceProductsList(){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name]);
	}
	global $PAGE;
	$opts=array(
		'-table'=>'wcommerce_products',
		'-action'=>"/t/1/{$PAGE['name']}/manage_products/list",
		'-onsubmit'=>"return pagingSubmit(this,'wcommerce_products_content');",
		'-tableclass'=>"table striped bordered sticky",
		'-tableheight'=>'55vh',
		'-order'=>'active desc,sort_group,name',
		'setprocessing'=>0,
		'-results_eval'=>'wcommerceProductsListExtra',
		'-listfields'=>'_id,active,featured,onsale,selected,name,category,sort_group,quantity,price,sale_price,points,sale_points,sku,size,sort_size,color,sort_color,material,sort_material,photo_1,photo_2',
		'photo_1_image'=>1,
		'photo_2_image'=>1,
		'-editfields'=>'name,quantity,category,sort_group,price,sale_price,points,sale_points,sku,size,sort_size,color,sort_color,material,sort_material',
		'-sorting'=>1,
		'-export'=>1,
		'-bulkedit'=>1,
		'quantity_class'=>'align-right',
		'price_class'=>'align-right',
		'name_class'=>'w_nowrap',
		'sale_price_class'=>'align-right',
		'weight_options'=>array(
			'class'=>'align-right',
			'displayname'=>'Weight (oz)'
		),
		'active_options'=>array(
			'onclick'=>"return wcommerceManageSetValue(this);",
			'data-id'=>"%_id%",
			'data-table'=>"wcommerce_products",
			'data-field'=>'active',
			'data-value'=>"%active%",
			'data-one'=>'icon-mark w_success',
			'data-zero'=>'icon-mark w_lgray',
			'checkmark'=>1,
			'checkmark_icon'=>'icon-mark w_success',
			'icon_0'=>'icon-mark w_lgray',
			'displayname'=>'<span class="icon-mark w_success" title="Active"></span>'
		),
		'onsale_options'=>array(
			'onclick'=>"return wcommerceManageSetValue(this);",
			'data-id'=>"%_id%",
			'data-table'=>"wcommerce_products",
			'data-field'=>'onsale',
			'data-value'=>"%onsale%",
			'data-one'=>'icon-tag w_danger',
			'data-zero'=>'icon-tag w_lgray',
			'checkmark'=>1,
			'checkmark_icon'=>'icon-tag w_danger',
			'icon_0'=>'icon-tag w_lgray',
			'displayname'=>'<span class="icon-tag w_danger" title="On Sale"></span>'
		),
		'featured_options'=>array(
			'onclick'=>"return wcommerceManageSetValue(this);",
			'data-id'=>"%_id%",
			'data-table'=>"wcommerce_products",
			'data-field'=>'featured',
			'data-value'=>"%featured%",
			'data-one'=>'icon-optimize w_warning',
			'data-zero'=>'icon-optimize w_lgray',
			'checkmark'=>1,
			'checkmark_icon'=>'icon-optimize w_warning',
			'icon_0'=>'icon-optimize w_lgray',
			'displayname'=>'<span class="icon-optimize w_warning" title="Featured"></span>'
		),
		'selected_options'=>array(
			'onclick'=>"return wcommerceManageSetValue(this);",
			'data-id'=>"%_id%",
			'data-table'=>"wcommerce_products",
			'data-field'=>'selected',
			'data-value'=>"%selected%",
			'data-group'=>'%group%',
			'data-one'=>'selected icon-checkbox w_blue',
			'data-zero'=>'selected icon-checkbox-empty w_lgray',
			'checkmark'=>1,
			'checkmark_icon'=>'selected icon-checkbox w_blue',
			'icon_0'=>'selected icon-checkbox-empty w_lgray',
			'displayname'=>'<span class="icon-checkbox w_blue" title="Default Selection"></span>'
		),
		'_id_options'=>array(
			'onclick'=>"return wcommerceNav(getParent(this,'td'));",
			'data-href'=>"/t/1/{$PAGE['name']}/manage_products/addedit/%_id%",
			'data-div'=>'centerpop'
		),
	);
	$opts['-quickfilters']=array(
		'Add'=>array(
			'icon'=>'icon-plus',
			'onclick'=>'wcommerceNav(this);',
			'data-href'=>"/t/1/{$PAGE['name']}/manage_products/addedit/0",
			'data-div'=>"centerpop",
			'class'=>"btn w_white"
			),
		'Featured'=>array(
			'icon'=>'icon-optimize',
			'filter'=>'featured eq 1',
			'class'=>"btn w_yellow"
			),
		'On Sale'=>array(
			'icon'=>'icon-tag',
			'filter'=>'onsale eq 1',
			'class'=>"btn w_green"
			),
		'Selected'=>array(
			'icon'=>'icon-checkbox',
			'filter'=>'selected eq 1',
			'class'=>"btn w_blue"
			),
		'Inactive'=>array(
			'icon'=>'icon-mark',
			'filter'=>'active eq 0',
			'class'=>"btn w_gray"
			)
	);
	$opts['-pretable']=<<<ENDOFPRETABLE
<div style="display:flex;justify-content:space-between;align-items:center">
	<div class="w_small w_gray">Click on Id to edit entire record. Click on <span class="icon-edit"></span> to edit a single field.</div>
</div>
ENDOFPRETABLE;
	return databaseListRecords($opts);
}
function wcommerceProductsListExtra($recs){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name],$recs);
	}
	foreach($recs as $i=>$rec){
		$recs[$i]['guid']=md5($rec['name']);
		$recs[$i]['group']=strtolower(preg_replace('/[^a-z0-9]+/i','',$rec['name']));
	}
	return $recs;
}
function wcommerceProductsAddedit($id=0){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name],$id);
	}
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
		'category_options'=>array(
			'inputtype'=>'text',
			'tvals'=>"select distinct(category) from wcommerce_products order by category",
			'autocomplete'=>'off'
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
		'size_options'=>array(
			'inputtype'=>'text',
			'tvals'=>"select distinct(size) from wcommerce_products order by size",
			'autocomplete'=>'off'
		),
		'color_options'=>array(
			'inputtype'=>'text',
			'tvals'=>"select distinct(color) from wcommerce_products order by color",
			'autocomplete'=>'off'
		),
		'material_options'=>array(
			'inputtype'=>'text',
			'tvals'=>"select distinct(material) from wcommerce_products order by material",
			'autocomplete'=>'off'
		),
		'details_options'=>array(
			'inputtype'=>'textarea',
			'height'=>'150'
		),
		'related_products_options'=>array(
			'inputtype'=>'checkbox',
			'tvals'=>"select _id from wcommerce_products order by name",
			'dvals'=>"select _id,'. ',name from wcommerce_products order by name",
			'width'=>'3'
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
		$opts['related_products_options']=array(
			'inputtype'=>'checkbox',
			'tvals'=>"select _id from wcommerce_products where _id <> {$id} order by name",
			'dvals'=>"select _id,'. ',name from wcommerce_products where _id <> {$id} order by name",
			'width'=>'3'
		);
	}
	else{
		$opts['active']=1;
	}
	return addEditDBForm($opts);
}
function wcommerceSettingsList(){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name]);
	}
	global $PAGE;
	$opts=array(
		'-table'=>'wcommerce_settings',
		'-tableclass'=>"table striped bordered sticky",
		'-tableheight'=>'55vh',
		'-action'=>"/t/1/{$PAGE['name']}/manage_settings/list",
		'-onsubmit'=>"return pagingSubmit(this,'wcommerce_settings_content');",
		'-searchfields'=>'name,value',
		'-searchopers'=>'ct,eq,ib,nb',
		'-editfields'=>'value',
		'-export'=>1,
		'-sorting'=>1,
		'-order'=>'name',
		//'-navonly'=>1,
		'name_options'=>array(
			'onclick'=>"return wcommerceNav(getParent(this,'td'));",
			'data-href'=>"/t/1/{$PAGE['name']}/manage_settings/addedit/%_id%",
			'data-div'=>'centerpop'
		)
	);
	$opts['-quickfilters_class']='btn w_blue';
	$opts['-quickfilters']=array(
		'Not Blank'=>array(
			'icon'=>'icon-package',
			'filter'=>'value nb',
			'class'=>"btn"
			),
		'Orders'=>array(
			'icon'=>'icon-package',
			'filter'=>'name ct wcommerceOrders',
			'class'=>"btn"
			),
		'Products'=>array(
			'icon'=>'icon-tag',
			'filter'=>'name ct wcommerceProducts',
			'class'=>"btn"
			),
		'Buyers'=>array(
			'icon'=>'icon-users',
			'filter'=>'name ct wcommerceBuyers',
			'class'=>"btn"
			),
	);
	$opts['-pretable']=<<<ENDOFPRETABLE
<div style="display:flex;justify-content:flex-end;align-items:center">
	<button type="button" class="button is-small btn btn-small is-success w_success" onclick="wcommerceNav(this);" data-href="/t/1/{$PAGE['name']}/manage_settings/addedit/0" data-div="centerpop"><span class="icon-plus"></span> <translate>Add</translate></button>
</div>
ENDOFPRETABLE;
	return databaseListRecords($opts);
}
function wcommerceSettingsAddedit($id=0){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name],$id);
	}
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
	$rtn='';
	$rec=getDBRecord(array('-table'=>'_pages','-where'=>"name='wcommerce' or permalink='wcommerce'",'-fields'=>'_id,name,permalink'));
	if($force==1 || !isset($rec['_id'])){
		//create a wcommerce page
		$opts=array(
			'-table'=>'_pages',
			'name'=>'wcommerce',
			'title'=>'wcommerce',
			'permalink'=>'wcommerce',
			'body'=>wcommercePageBody(),
			'js'=>wcommercePageJs(),
			'controller'=>wcommercePageController(),
			'description'=>'wcommerce management page',
			'-upsert'=>'body,controller,js',
			'_template'=>2
		);
		$ok=addDBRecord($opts);
		if(isNum($ok)){
			$rtn.="updated wcommerce page. <br />".PHP_EOL;
		}
		else{
			$rtn .= "ERROR updating wcommerce page:".printValue($ok);
		}
	}
	//check for schema
	if($force==1 || !isDBTable('wcommerce_products')){
		$ok=databaseAddMultipleTables(wcommerceSchema());
		$rtn.="updated wcommerce schema. <br />".PHP_EOL;
	}
	//settings
	$settings=wcommerceGetSettings();
	if($force==1 || !count($settings)){
		$progpath=dirname(__FILE__);
		$afile="{$progpath}/wcommerce.php";
		$lines=file($afile);
		$recs=array();
		$jsfunc=0;
		foreach($lines as $line){
			$line=trim($line);
			if(preg_match('/return \<\<\<ENDOFJS$/',$line)){
				$jsfunc=1;
				continue;
			}
			elseif(preg_match('/^ENDOFJS\;$/',$line)){
				$jsfunc=0;
				continue;
			}
			if($jsfunc==1){continue;}
			if(preg_match('/^function\ (wcommerce)(.+?)\(/is',$line,$m)){
				$fname=$m[1].$m[2];
				//echo "[{$fname}]<br>".PHP_EOL;
				$opts=array(
					'-table'=>'wcommerce_settings',
					'name'=>$fname,
					'-upsert'=>'ignore'
				);
				$id=addDBRecord($opts);
				//echo $id.printValue($opts).PHP_EOL;
			}
		}
		//add other settings
		$fnames=array('wcommerceAdd2Cart_GroupItems');
		foreach($fnames as $fname){
			$opts=array(
				'-table'=>'wcommerce_settings',
				'name'=>$fname,
				'-upsert'=>'ignore'
			);
			$id=addDBRecord($opts);
		}
		$rtn.="updated wcommerce settings. <br />".PHP_EOL;
	}
	return $rtn;
}
function wcommercePageBody(){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name]);
	}
	return <<<ENDOFBODY
<view:manage_portal>
<style type="text/css">

.product{
	position:relative;
}
.product .popup{
	max-height:0px;
	overflow:hidden;
	transition:* 0.5s ease-out;
}
.product:hover .popup{
	max-height:600px;
	transition: * 0.5s ease-in;;
}
</style>
<div class="w_bold w_biggest w_success" id="wcommerce" data-page="<?=pageValue('name');?>">
	<span class="icon-handshake w_success"></span> wCOMMERCE
</div>
<ul class="nav-tabs w_black">
	<!-- Orders -->
	<li class="active"><a href="#" onclick="return wcommerceNavTab(this);" data-href="/t/1/<?=pageValue('name');?>/manage_orders"><span class="icon-package"></span> <translate>Orders</translate></a></li>
	<!-- Products -->
	<li><a href="#" onclick="return wcommerceNavTab(this);" data-href="/t/1/<?=pageValue('name');?>/manage_products"><span class="icon-tag"></span> <translate>Products</translate></a></li>
	<li><a href="#" onclick="return wcommerceNavTab(this);" data-href="/t/1/<?=pageValue('name');?>/manage_products_preview"><span class="icon-eye"></span> <translate>Products Preview</translate></a></li>
	<!-- Settings -->
	<li><a href="#" onclick="return wcommerceNavTab(this);" data-href="/t/1/<?=pageValue('name');?>/manage_settings"><span class="icon-gear"></span> <translate>Settings</translate></a></li>
	<!-- Buyers -->
	<li><a href="#" onclick="return wcommerceNavTab(this);" data-href="/t/1/<?=pageValue('name');?>/manage_buyers"><span class="icon-users"></span> <translate>Buyers</translate></a></li>
	<!-- User menu -->
	<li><a href="#" onclick="return false" class="dropdown"><span class="icon-user"></span> <?=\$USER['username'];?></a>
		<div>
			<ul class="nav-list">
				<!-- Logoff -->
				<li class="right"><a href="/<?=pageValue('name');?>?_logoff=1"><span class="icon-logout"></span> <translate>Logoff</translate></a></li>
				<!-- Update -->
				<li style="margin-top:10px;border-top:1px solid #ccc;padding-top:10px;"><a href="#" onclick="return wcommerceNavTab(this);" data-href="/t/1/<?=pageValue('name');?>/manage_init" data-confirm="This will update the wcommerce module. Are you sure?"><span class="icon-refresh w_danger"></span> <translate>Update</translate></a></li>
			</ul>
		</div>
	</li>
</ul>
<div id="wcommerce_content">
	<?=renderView('manage_orders',\$orders,'orders');?>
</div>
<div style="display:none"><div id="wcommerce_nulldiv"></div></div>
</view:manage_portal>

<view:manage_redraw><?=wcommerceBuildField(\$field,'',\$value);?></view:manage_redraw>
<view:manage_init>
	<div class="w_bold w_big">wCommerce has been Updated</div>
	<div class="w_small w_gray">Restart postedit and clear your cache</div>
	<div><?=\$rtn;?></div>
</view:manage_init>

<view:manage_orders>
<div id="wcommerce_orders_content" style="margin-top:10px;">
	<?=wcommerceOrdersList();?>
</div>
</view:manage_orders>

<view:manage_orders_list>
	<?=wcommerceOrdersList();?>
	<?=buildOnLoad("removeId('centerpop');");?>
</view:manage_orders_list>

<view:manage_orders_addedit>
<div class="w_centerpop_title">Orders AddEdit</div>
<div class="w_centerpop_content" style="width:70vw;">
	<?=wcommerceOrdersAddedit(\$id);?>
	<?=buildOnLoad("centerObject('centerpop');");?>
</div>
</view:manage_orders_addedit>

<view:manage_orders_addedit_fields>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;"><label><translate>User_ID</translate></label>[user_id]</div>
	<div style="margin:5px;"><label><translate>Date Ordered</translate></label>[date_ordered]</div>
	<div style="margin:5px;"><label><translate>Coupon</translate></label>[coupon]</div>
	<div style="margin:5px;"><label><translate>Discount</translate></label>[discount]</div>
</div>
<div style="font-size:1.2rem;background:#eee;padding:0 10px;margin:5px 0;font-weight: bold;color:#999;"><span class="icon-user2"></span> SHIP TO</div>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;"><label><translate>Firstname</translate></label>[shipto_firstname]</div>
	<div style="margin:5px;"><label><translate>Lastname</translate></label>[shipto_lastname]</div>
	<div style="margin:5px;"><label><translate>Email</translate></label>[shipto_email]</div>
	<div style="margin:5px;"><label><translate>Phone</translate></label>[shipto_phone]</div>
</div>
<div style="display:flex;padding:1px;">
	<div style="flex:1;margin:5px;"><label><translate>Address</translate></label>[shipto_address]</div>
</div>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;"><label><translate>Country</translate></label>[shipto_country]</div>
	<div style="margin:5px;"><label><translate>State</translate></label><div id="shipto_state_content">[shipto_state]</div></div>
	<div style="margin:5px;"><label><translate>City</translate></label>[shipto_city]</div>
	<div style="margin:5px;"><label><translate>Postal Code</translate></label>[shipto_zip]</div>
	
</div>
<div style="font-size:1.2rem;background:#eee;padding:0 10px;margin:5px 0;font-weight: bold;color:#999;"><span class="icon-package"></span> SHIPPING</div>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;"><label><translate>Code</translate></label>[shipmethod_code]</div>
	<div style="margin:5px;"><label><translate>Price</translate></label>[shipmethod_price]</div>
	<div style="margin:5px;"><label><translate>Tracking Number</translate></label>[tracking_number]</div>
	<div style="margin:5px;"><label><translate>Date Shipped</translate></label>[date_shipped]</div>
	<div style="margin:5px;"><label><translate>Date Delivered</translate></label>[date_delivered]</div>
</div>
<div style="font-size:1.2rem;background:#eee;padding:0 10px;margin:5px 0;font-weight: bold;color:#999;"><span class="icon-cc"></span> PAYMENT</div>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;"><label><translate>Method</translate></label>[payment_name]</div>
	<div style="margin:5px;"><label><translate>Code</translate></label>[payment_code]</div>
	<div style="margin:5px;"><label><translate>Description</translate></label>[payment_description]</div>
	<div style="margin:5px;"><label><translate>Status</translate></label>[payment_status]</div>
	<div style="margin:5px;"><label><translate>Response</translate></label>[payment_response]</div>
</div>
</view:manage_orders_addedit_fields>

<view:manage_orders_view>
<div class="w_centerpop_title">View Order #<?=\$id;?> </div>
<div class="w_centerpop_content">
	<table class="table condensed bordered">
		<tr><th>OrderID</th><th>Firstname</th><th>Lastname</th>
			<th><span class="icon-calendar w_blue"></span> Ordered</th>
			<th><span class="icon-package w_green"></span> Shipped</th>
			<th><span class="icon-calendar-check w_gray"></span> Delivered</th></tr>
		<tr>
			<td><?=\$order['_id'];?></td>
			<td><?=\$order['shipto_firstname'];?></td>
			<td><?=\$order['shipto_lastname'];?></td>
			<td><?=strlen(\$order['date_ordered'])?date('D M jS',strtotime(\$order['date_ordered'])):'n/a';?></td>
			<td><?=strlen(\$order['date_shipped'])?date('D M jS',strtotime(\$order['date_shipped'])):'n/a';?></td>
			<td><?=strlen(\$order['date_delivered'])?date('D M jS',strtotime(\$order['date_delivered'])):'n/a';?></td>
		</tr>
	</table>
	<?=wcommerceOrdersItemsList(\$id);?>
	<?=buildOnLoad("document.ordersshippedform.tracking_number.focus();centerObject('centerpop');");?>
</div>
</view:manage_orders_view>

<view:manage_orders_shipped>
<div class="w_centerpop_title">Ship Order #<?=\$id;?></div>
<div class="w_centerpop_content">
	<table class="table condensed bordered">
		<tr><th>OrderID</th><th>Firstname</th><th>Lastname</th><td>Shipped By</td></tr>
		<tr>
			<td><?=\$order['_id'];?></td>
			<td><?=\$order['shipto_firstname'];?></td>
			<td><?=\$order['shipto_lastname'];?></td>
			<td class="w_gray"><?="{\$USER['firstname']} {\$USER['lastname']}";?></td>
		</tr>
	</table>
	<?=wcommerceOrdersShipped(\$id);?>
	<?=buildOnLoad("document.ordersshippedform.tracking_number.focus();centerObject('centerpop');");?>
</div>
</view:manage_orders_shipped>

<view:manage_orders_shipped_fields>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;"><label><translate>Tracking Number</translate></label>[tracking_number]</div>
	<div style="margin:5px;"><label><translate>Date Shipped</translate></label>[date_shipped]</div>
</div>
</view:manage_orders_shipped_fields>

<view:manage_orders_delivered>
<div class="w_centerpop_title">Deliver Order <?=\$id;?> <?=\$order['shipto_firstname'];?></div>
<div class="w_centerpop_content">
	<table class="table condensed bordered">
		<tr><th>OrderID</th><th>Firstname</th><th>Lastname</th><td>Ship Date</td></tr>
		<tr>
			<td><?=\$order['_id'];?></td>
			<td><?=\$order['shipto_firstname'];?></td>
			<td><?=\$order['shipto_lastname'];?></td>
			<td class="w_gray"><?=\$order['date_shipped'];?></td>
		</tr>
	</table>
	<?=wcommerceOrdersDelivered(\$id);?>
	<?=buildOnLoad("centerObject('centerpop');");?>
</div>
</view:manage_orders_delivered>

<view:manage_orders_delivered_fields>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;"><label><translate>Date delivered</translate></label>[date_delivered]</div>
</div>
</view:manage_orders_delivered_fields>

<view:manage_orders_items_shipped>
<div class="w_centerpop_title">Ship Order Item #<?=\$id;?></div>
<div class="w_centerpop_content">
	<table class="table condensed bordered">
		<tr><th>OrderID</th><th>Firstname</th><th>Lastname</th><td>Shipped By</td></tr>
		<tr>
			<td><?=\$order['_id'];?></td>
			<td><?=\$order['shipto_firstname'];?></td>
			<td><?=\$order['shipto_lastname'];?></td>
			<td class="w_gray"><?="{\$USER['firstname']} {\$USER['lastname']}";?></td>
		</tr>
	</table>
	<?=wcommerceOrdersItemShipped(\$id);?>
	<?=buildOnLoad("document.ordersshippedform.tracking_number.focus();centerObject('centerpop');");?>
</div>
</view:manage_orders_items_shipped>

<view:manage_orders_items_shipped_fields>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;"><label><translate>Tracking Number</translate></label>[tracking_number]</div>
	<div style="margin:5px;"><label><translate>Date Shipped</translate></label>[date_shipped]</div>
</div>
</view:manage_orders_items_shipped_fields>

<view:manage_orders_items_delivered>
<div class="w_centerpop_title">Deliver Order <?=\$id;?> <?=\$order['shipto_firstname'];?></div>
<div class="w_centerpop_content">
	<table class="table condensed bordered">
		<tr><th>OrderID</th><th>Firstname</th><th>Lastname</th><td>Ship Date</td></tr>
		<tr>
			<td><?=\$order['_id'];?></td>
			<td><?=\$order['shipto_firstname'];?></td>
			<td><?=\$order['shipto_lastname'];?></td>
			<td class="w_gray"><?=\$order['date_shipped'];?></td>
		</tr>
	</table>
	<?=wcommerceOrdersItemDelivered(\$id);?>
	<?=buildOnLoad("centerObject('centerpop');");?>
</div>
</view:manage_orders_items_delivered>

<view:manage_orders_items_delivered_fields>
<div style="display:flex;padding:1px;">
	<div style="margin:5px;"><label><translate>Date delivered</translate></label>[date_delivered]</div>
</div>
</view:manage_orders_items_delivered_fields>



<view:manage_products>
<div id="wcommerce_products_content" style="margin-top:10px;">
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

<view:manage_products_preview>
<div style="display:flex;justify-content: center;flex-wrap: wrap;align-items: flex-start;padding:1px;">
	<?=renderEach('product',\$products,'product');?>
</div>
</view:manage_products_preview>

<view:product>
<div class="w_shadow" id="product_<?=\$product['guid'];?>" style="margin:15px;padding:10px 15px;border-radius: 8px;display:flex;justify-content: flex-start;align-items: flex-end;flex-direction: column;">
	<?=renderView('product_body',\$product,'product');?>
</div>
</view:product>

<view:product_body>
	<div style="display:flex;justify-content: flex-end;align-items: center;width:100%;">
		<div style="flex:1;" class="align-center mint"><?=\$product['category'];?></div>
		<div class="w_lgray">p<?=\$product['_id'];?></div>
	</div>
	<div style="display:flex;justify-content: flex-start;align-items: flex-start;width:100%;">
		<view:photos>
		<div style="display:flex;flex-direction: column;justify-content: flex-start;margin-right:5px;">
			<view:photo>
			<img data-guid="<?=\$photo['guid'];?>" src="<?=\$photo['src'];?>" onmouseover="wcommerceSetPhoto(this);"  style="border:<?=\$photo['border'];?>;width:32px;padding:2px;height:auto;border-radius: 4px" />
			</view:photo>
			<?=renderEach('photo',\$product['photos'],'photo');?>
		</div>
		</view:photos>
		<?=renderViewIf(isset(\$product['photos']),'photos',\$product,'product');?>

		<div style="flex:1;" class="align-center">
			<figure style="width:250px;height:250px;"><img id="photo_<?=\$product['guid'];?>" src="<?=\$product['photo_1'];?>" onclick="wacss.showImage(this);"  style="cursor:pointer;width:100%;height:auto;" /></figure>
		</div>
	</div>
	<view:sizes>
	<div class="align-left" style="margin:5px;width:100%;"><?=wcommerceBuildField('size',\$product);?></div>
	</view:sizes>
	<?=renderViewIf(isset(\$product['sizes'][0]),'sizes',\$product,'product');?>
	<view:colors>
		<div class="align-left" style="margin:5px;width:100%;"><?=wcommerceBuildField('color',\$product);?></div>
	</view:colors>
	<?=renderViewIf(isset(\$product['colors'][0]),'colors',\$product,'product');?>
	<view:materials>
	<div class="align-left" style="margin:5px;width:100%;"><?=wcommerceBuildField('material',\$product);?></div>
	</view:materials>
	<?=renderViewIf(isset(\$product['materials'][0]),'materials',\$product,'product');?>
	
	<div class="w_biggest align-center"><?=\$product['name'];?></div>
	
	<div style="display:flex;justify-content: space-between;width:100%;align-items: center;">
		<div class="w_bigger w_bold mint">\$ <?=\$product['price'];?></div>
		<button class="button is-info" type="button" onclick="wcommerceAdd2Cart(this);" data-product_id="<?=\$product['_id'];?>" data-points="0" data-price="<?=\$product['price'];?>" style="margin-top:5px;display:flex;align-self: center;justify-content: center;"><span>ADD TO CART </span><span class="icon-heart" style="margin-left:5px;"></span></button>
	</div>
</view:product_body>

<view:add2cart>
<div id="add2cart_message"><?=\$message;?></div>
<?=buildOnLoad("wacss.toast(getText('add2cart_message'));");?>
</view:add2cart>

<view:manage_buyers>
<div id="wcommerce_buyers_content" style="margin-top:10px;">
	<?=wcommerceBuyersList();?>
</div>
</view:manage_buyers>

<view:manage_settings>
<div id="wcommerce_settings_content" style="margin-top:10px;">
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

<view:login>
<?=userLoginForm(array('-action'=>'/'.pageValue('name')));?>
</view:login>
ENDOFBODY;
}
function wcommercePageJs(){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name]);
	}
	return <<<ENDOFJS
function wcommerceChangeProductAttribute(el){
	let page=wcommercePageName();
	let div='product_'+el.dataset.product_guid;
	let url='/t/1/'+page+'/manage_change_product_attribute';
	let attrs=document.querySelectorAll('#product_'+el.dataset.product_guid+' input[data-product_attr]');
	let params={setprocessing:0,field:el.name,value:el.value,name:el.dataset.product_name};
	for(let i=0;i<attrs.length;i++){
		if(!attrs[i].checked){continue;}
		params[attrs[i].name]=attrs[i].value;
	}
	return ajaxGet(url,div,params);
}
function wcommerceSetPhoto(el){
	let setobj=document.querySelector('#photo_'+el.dataset.guid);
	if(undefined == setobj){return false;}
	let pobj=document.querySelector('#product_'+el.dataset.guid);
	if(undefined == pobj){return false;}
	if(setobj.src==el.src){
		el.style.border='1px solid #ddd';
		return false;
	}
	let photos=pobj.querySelectorAll('img[data-guid="'+el.dataset.guid+'"]');
	for(let i=0;i<photos.length;i++){
		photos[i].style.border='1px solid #fff';
	}
	setobj.src=el.src;
	el.style.border='1px solid #ddd';
	return;

}
function wcommerceAdd2Cart(el){
	let pdiv=getParent(el,'div');
	let qty=1;
	let page=wcommercePageName();
	let div='wcommerce_nulldiv';
	let url='/t/1/'+page+'/add2cart';
	let params={setprocessing:0,id:el.dataset.product_id,qty:qty,name:el.dataset.product_name};
	let price=parseFloat(el.dataset.price);
	let points=parseInt(el.dataset.points);
	if(undefined != points && points > 0){params.usepoints=1;}
	return ajaxGet(url,div,params);
}
function wcommerceOrdersView(el){
	let page=wcommercePageName();
	let div='centerpop';
	let url='/t/1/'+page+'/manage_orders/view/'+el.dataset.id;
	let params={setprocessing:0};
	return ajaxGet(url,div,params);
}
function wcommerceOrdersShip(el){
	let page=wcommercePageName();
	let div='centerpop2';
	let url='/t/1/'+page+'/manage_orders/ship/'+el.dataset.id;
	let params={setprocessing:0};
	return ajaxGet(url,div,params);
}
function wcommerceOrdersDeliver(el){
	let page=wcommercePageName();
	let div='centerpop2';
	let url='/t/1/'+page+'/manage_orders/deliver/'+el.dataset.id;
	let params={setprocessing:0};
	return ajaxGet(url,div,params);
}
function wcommerceOrdersShipItem(el){
	let page=wcommercePageName();
	let div='centerpop2';
	let url='/t/1/'+page+'/manage_orders/ship_item/'+el.dataset.id;
	let params={setprocessing:0};
	return ajaxGet(url,div,params);
}
function wcommerceOrdersDeliverItem(el){
	let page=wcommercePageName();
	let div='centerpop2';
	let url='/t/1/'+page+'/manage_orders/deliver_item/'+el.dataset.id;
	let params={setprocessing:0};
	return ajaxGet(url,div,params);
}
function wcommerceNavTab(el){
	wacss.setActiveTab(el);
	return wcommerceNav(el);
}
function wcommerceNav(el){
	if(undefined != el.dataset.confirm && !confirm(el.dataset.confirm)){
		return false;
	}
	let p=el.dataset;
	p.setprocessing=0;
	if(undefined != el.value){
		p.value=el.value;
	}
	let div=el.dataset.div||'wcommerce_content';
	let href=el.dataset.href;
	return ajaxGet(href,div,p);
}
function wcommerceManageSetValue(el){
	let p=getParent(el,'td');
	let v=parseInt(p.dataset.value);
	let s=el.querySelector('span');
	//unset others for certain fields
	if(p.dataset.table=='wcommerce_products' && p.dataset.field=='selected'){
		let pels=document.querySelectorAll('td[data-group="'+p.dataset.group+'"]');
		for(let x=0;x<pels.length;x++){
			let sels=pels[x].querySelectorAll('span.selected');
			for(let i=0;i<sels.length;i++){
				if(sels[i]==s){continue;}
				pels[x].dataset.value=0;
				sels[i].className=p.dataset.zero;
			}
		}
	}
	//set the value
	console.log(v);
	if(v==1){
		p.dataset.value=0;
		s.className=p.dataset.zero;
	}
	else{
		p.dataset.value=1;
		s.className=p.dataset.one;
	}
	let page=wcommercePageName();
	let url='/t/1/'+page+'/manage_setvalue/'+p.dataset.table+'/'+p.dataset.field+'/'+p.dataset.id+'/'+p.dataset.value;
	return ajaxGet(url,'wcommerce_nulldiv',{setprocessing:0});
}
function wcommercePageName(){
	let el=document.querySelector('#wcommerce[data-page]');
	if(undefined==el){return 'wcommerce';}
	return el.dataset.page;
}
ENDOFJS;
}
function wcommercePageController(){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name]);
	}
	return <<<ENDOFCONTROLLER
<?php
//require user
if(!isAdmin()){
	setView('login',1);
	return;
}
global \$USER;
global \$PASSTHRU;
global \$PAGE;
loadExtras('wcommerce');
switch(strtolower(\$PASSTHRU[0])){
	case 'user_profile':
		setView(\$PASSTHRU[0],1);
	break;
	case 'add2cart':
		\$id=(integer)\$_REQUEST['id'];
		\$rtn=wcommerceAdd2Cart(\$id,\$_REQUEST);
		if(isset(\$rtn['error'])){
			\$message="ERROR: {\$rtn['error']}";
		}
		else{
			\$message="{\$rtn['_id']} - {\$rtn['name']}: {\$rtn['quantity']} in cart";
		}
		setView('add2cart',1);
		return;
	break;
	case 'manage_change_product_attribute':
		\$filters=array(
			'-table'=>'wcommerce_products',
			'name'=>\$_REQUEST['name'],
			'active'=>1
		);
		foreach(\$_REQUEST as \$k=>\$v){
			if(preg_match('/^(size|color|material)\_([0-9]+)\$/',\$k,\$m)){
				\$_REQUEST[\$m[1]]=\$v;
			}
		}
		if(isset(\$_REQUEST['size']) && strlen(\$_REQUEST['size'])){
			\$filters['size']=\$_REQUEST['size'];
		}
		if(isset(\$_REQUEST['color']) && strlen(\$_REQUEST['color'])){
			//\$filters['color']=\$_REQUEST['color'];
		}
		if(isset(\$_REQUEST['material']) && strlen(\$_REQUEST['material'])){
			//\$filters['material']=\$_REQUEST['material'];
		}
		\$product=getDBRecord(\$filters);
		if(isset(\$product['name'])){
			\$atts=wcommerceGetProductAttributes(\$product['name']);
			foreach(\$atts as \$k=>\$v){
				\$product[\$k]=\$v;
			}
			\$product['guid']=md5(\$product['name']);
			\$product['photos']=wcommerceProductImages(\$product);
			if(count(\$product['photos']) > 1){
			}
			elseif(count(\$product['photos'])==1){
				\$product['photo']=\$product['photos'][0]['src'];
			}
			else{
				\$product['photo']='/wfiles/clear.gif';
			}
		}
		//echo printValue(\$filters).printValue(\$product);exit;
		setView('product_body',1);
		return;
	break;
	case 'manage_setvalue':
		\$table=\$PASSTHRU[1];
		\$field=\$PASSTHRU[2];
		\$id=(integer)\$PASSTHRU[3];
		\$value=(integer)\$PASSTHRU[4];
		if(\$field=='selected' && \$table=='wcommerce_products'){
			\$rec=getDBRecordById(\$table,\$id);
			\$name=str_replace("'","''",\$rec['name']);
			\$opts=array(
				'-table'=>\$table,
				'-where'=>"name='{\$name}'",
				\$field=>0
			);
			\$ok=editDBRecord(\$opts);
			echo printValue(\$ok).printValue(\$opts);
		}
		\$ok=editDBRecordById(\$table,\$id,array(\$field=>\$value));
		echo "Table:{\$table}, Field:{\$field}, Id:{\$id}, Value: {\$value}, Result: ".printValue(\$ok);exit;
	break;
	case 'manage_redraw':
		\$field=\$_REQUEST['field'];
		\$value=\$_REQUEST['value'];
		setView(\$PASSTHRU[0],1);
		return;
	break;
	case 'manage_init':
		\$rtn=wcommerceInit(1);
		setView(\$PASSTHRU[0],1);
		return;
	break;
	case 'manage_orders':
		switch(strtolower(\$PASSTHRU[1])){
			case 'list':
				setView('manage_orders_list',1);
				return;
			break;
			case 'addedit':
				\$id=(integer)\$PASSTHRU[2];
				setView('manage_orders_addedit',1);
				return;
			break;
			case 'view':
				\$id=(integer)\$PASSTHRU[2];
				\$order=wcommerceGetOrder(\$id);
				setView('manage_orders_view',1);
				return;
			break;
			case 'ship':
				\$id=(integer)\$PASSTHRU[2];
				\$order=getDBRecordById('wcommerce_orders',\$id);
				setView('manage_orders_shipped',1);
				return;
			break;
			case 'deliver':
				\$id=(integer)\$PASSTHRU[2];
				\$order=getDBRecordById('wcommerce_orders',\$id);
				setView('manage_orders_delivered',1);
				return;
			break;
			case 'ship_item':
				\$id=(integer)\$PASSTHRU[2];
				\$item=getDBRecordById('wcommerce_orders_items',\$id);
				setView('manage_orders_items_shipped',1);
				return;
			break;
			case 'deliver_item':
				\$id=(integer)\$PASSTHRU[2];
				\$order=getDBRecordById('wcommerce_orders_items',\$id);
				setView('manage_orders_items_delivered',1);
				return;
			break;
		}
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
	case 'manage_products_preview':
		\$products=wcommerceProducts(array('-random'=>10));
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
	default:
		setView('manage_portal');
	break;
}
?>
ENDOFCONTROLLER;
}

function wcommerceSchema(){
	//check for override
	$settings=wcommerceGetSettings();
	$function_name=__FUNCTION__;
	if(!empty($settings[$function_name]) && function_exists($settings[$function_name])){
		return call_user_func($settings[$function_name]);
	}
	return <<<ENDOFSCHEMA
wcommerce_products
	name varchar(150) NOT NULL
	sku varchar(25)
	category varchar(50)
	size varchar(25)
	color varchar(25)
	material varchar(25)
	quantity int NOT NULL Default 1
	sort_size INT NOT NULL Default 0
	sort_color INT NOT NULL Default 0
	sort_material INT NOT NULL Default 0
	sort_group varchar(25)
	price float(12,2)
	sale_price float(12,2)
	points int
	sale_points int
	active tinyint(1) NOT NULL Default 0
	onsale tinyint(1) NOT NULL Default 0
	selected tinyint(1) NOT NULL Default 0
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
	shipped_by int
	shipmethod_code varchar(25)
	shipmethod_price float(12,2)
	coupon varchar(25)
	quantity_total int NOT NULL Default 1
	price_total float(12,2)
	points_total int
	note varchar(255)
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
	category varchar(50)
	size varchar(25)
	color varchar(25)
	material varchar(25)
	quantity int NOT NULL Default 1
	price float(12,2)
	points int
	onsale tinyint(1) NOT NULL Default 0
	featured tinyint(1) NOT NULL Default 0
	weight int
	photo varchar(255)
	date_shipped datetime
	date_delivered datetime
	shipped_by int
	note varchar(255)
	shipmethod_code varchar(25)
	label varchar(50)
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
	value text
ENDOFSCHEMA;
}
?>