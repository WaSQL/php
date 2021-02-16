<?php
function zipcodesListCountries(){
	if(!isDBTable('countries')){
		$progpath=getWasqlPath('php/schema');
		$crecs=getCSVRecords("{$progpath}/all_countries.csv");
	}
	else{
		$crecs=getDBRecords(array('-table'=>'countries','-fields'=>'name,code'));
	}
	$cmap=array();
	foreach($crecs as $crec){
		$cmap[$crec['code']]=$crec['name'];
	}
	$zrecs=array();
	if(isDBTable('zipcodes')){
		$query="select count(*) cnt,country_code from zipcodes group by country_code";
		$zrecs=getDBRecords(array('-query'=>$query,'-index'=>'country_code'));
	}
	$post=postURL('http://download.geonames.org/export/zip/',array('-method'=>'GET'));
	$ok=preg_match_all('/href\=\"([a-z]{2,2})\.zip\"/ism',$post['body'],$m);
	$recs=array();
	foreach($m[1] as $code){
		$lcode=strtolower($code);
		$rec=array(
			'code'=>$code,
			'name'=>$cmap[$code]
		);
		if(isset($zrecs[$code])){
			$rec['icon']='icon-mark';
			$rec['zipcodes']=$zrecs[$code]['cnt'];
			$rec['exists']=1;
		}
		elseif(isset($zrecs[$lcode])){
			$rec['icon']='';
			$rec['zipcodes']=$zrecs[$lcode]['cnt'];
			$rec['exists']=1;
		}
		else{
			$rec['checked']='';
			$rec['zipcodes']='';
			$rec['exists']=0;
		}
		$recs[]=$rec;
	}
	$recs=sortArrayByKeys($recs, array('exists'=>SORT_DESC, 'name'=>SORT_ASC));
	$opts=array(
		'-tableclass'=>'table striped narrow bordered sticky',
		'-listfields'=>'id,name,code,zipcodes',
		'-list'=>$recs,
		'-hidesearch'=>1,
		'id_displayname'=>buildFormCheckAll('class','country',array('-label'=>'&nbsp;')),
		'code_displayname'=>'Country Code',
		'name_displayname'=>'Country',
		'zipcodes_class'=>'align-right',
		'-results_eval'=>'zipcodesListCountriesExtra',
		'-sumfields'=>'zipcodes',
		'-tableheight'=>'80vh'

	);
	return databaseListRecords($opts);
}
function zipcodesListCountriesExtra($recs){
	foreach($recs as $i=>$rec){
		if($rec['exists']==1){
			$recs[$i]['name']='<span class="w_right icon-mark w_success"></span>'.$rec['name'];
			$recs[$i]['id']='<input type="checkbox" class="checkbox w_red" data-type="checkbox" name="country_codes[]" value="'.$rec['code'].'" />';
		}
		else{
			$recs[$i]['id']='<input type="checkbox" class="country checkbox w_green" data-type="checkbox" name="country_codes[]" value="'.$rec['code'].'" />';
		}
		
	}
	return $recs;
}
?>