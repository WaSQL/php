<?php
/*
	https://moz.com/blog/the-ultimate-guide-to-seo-meta-tags
	https://clutch.co/seo-firms/resources/meta-tags-that-improve-seo

	DONE: Title tag
	DONE: Meta description
	DONE: Canonical tag
	DONE: Alt and title attributes in images
	DONE: Robots meta tag
	DONE: Open graph meta tags for facebook
	DONE: twitter meta tags
	Header tags
	DONE: viewport meta tag for responsive
*/
function websiteGraderHead(){
	$recs=array();
	$pages=websiteGraderActivePages();
	foreach($pages as $page){
		$body=websiteGraderGetPageBody($page['name']);
		if(!preg_match('/<head>(.*)<\/head>/si',$body,$m)){
			$recs[]=array(
				'source'=>"{$page['_id']} - {$page['name']}",
				'element'=>"<xmp><head></head></xmp>",
				'suggestions'=>'Head tag is missing all together'
			);
			continue;
		}
		$head=$m[1];
		/**** Check for Title tag ***/
		$title='';
		if(preg_match('/<title>(.+?)<\/title>/si',$head,$m)){
			$title=$m[1];
		}
		//title should be between 40 - 80 chars in length
		if(!strlen($title)){
			$recs[]=array(
				'source'=>"{$page['_id']} - {$page['name']}",
				'element'=>"<xmp><title></title></xmp>",
				'suggestions'=>'Title is missing'
			);
		}
		else{
			if(strlen($title) > 80){
				$recs[]=array(
					'source'=>"{$page['_id']} - {$page['name']}",
					'element'=>"<xmp><title>{$title}</title></xmp>",
					'suggestions'=>'Title is too long (> 80 chars)'
				);
			}
			elseif(strlen($title) < 40){
				$recs[]=array(
					'source'=>"{$page['_id']} - {$page['name']}",
					'element'=>"<xmp><title>{$title}</title></xmp>",
					'suggestions'=>'Title is too short (< 40 chars)'
				);
			}
		}
		/**** Check meta tags ***/
		$meta=array();
		preg_match_all('/<meta[^>]*>/si', $head, $matches);
		foreach($matches[0] as $str){
			$atts=parseHtmlTagAttributes($str);
			if(isset($atts['name'])){
				$key=strtolower($atts['name']);
				$meta[$key]=array(
					'atts'=>$atts,
					'str'=>$str
				);
			}
			elseif(isset($atts['property'])){
				$key=strtolower($atts['property']);
				$meta[$key]=array(
					'atts'=>$atts,
					'str'=>$str
				);
			}
			
		}
		ksort($meta);
		//description should be between 150 - 160 chars in length
		if(!isset($meta['description'])){
			$recs[]=array(
				'source'=>"{$page['_id']} - {$page['name']}",
				'element'=>'<xmp><meta name="description" content="{your description here}" /></xmp>',
				'suggestions'=>'Meta description tag is missing'
			);
		}
		elseif(!isset($meta['description']['atts']['content'])){
			$recs[]=array(
				'source'=>"{$page['_id']} - {$page['name']}",
				'element'=>"<xmp>{$meta['description']['str']}</xmp>",
				'suggestions'=>'Meta description tag is missing content attribute'
			);
		}
		else{
			if(strlen($meta['description']['atts']['content']) > 160){
				$recs[]=array(
					'source'=>"{$page['_id']} - {$page['name']}",
					'element'=>"<xmp>{$meta['description']['str']}</xmp>",
					'suggestions'=>'Meta description is too long (> 160 chars)'
				);
			}
			elseif(strlen($meta['description']['atts']['content']) < 140){
				$recs[]=array(
					'source'=>"{$page['_id']} - {$page['name']}",
					'element'=>"<xmp>{$meta['description']['str']}</xmp>",
					'suggestions'=>'Meta description is too short (< 140 chars)'
				);
			}
		}
		//robots
		if(!isset($meta['robots'])){
			$recs[]=array(
				'source'=>"{$page['_id']} - {$page['name']}",
				'element'=>'<xmp><meta name="robots" content="index, follow" /></xmp>',
				'suggestions'=>'Meta robots tag is missing'
			);
		}
		elseif(!isset($meta['robots']['atts']['content'])){
			$recs[]=array(
				'source'=>"{$page['_id']} - {$page['name']}",
				'element'=>"<xmp>{$meta['robots']['str']}</xmp>",
				'suggestions'=>'Meta robots tag is missing content attribute'
			);
		}
		elseif(stringContains($meta['robots']['atts']['content'],'noindex')){
			$recs[]=array(
				'source'=>"{$page['_id']} - {$page['name']}",
				'element'=>"<xmp>{$meta['robots']['str']}</xmp>",
				'suggestions'=>'Meta robots tag specifies to NOT index this page'
			);
		}
		//viewport
		if(!isset($meta['viewport'])){
			$recs[]=array(
				'source'=>"{$page['_id']} - {$page['name']}",
				'element'=>'<xmp><meta name="viewport" content="width=device-width,initial-scale=1.0" /></xmp>',
				'suggestions'=>'Meta robots tag is missing'
			);
		}
		elseif(!isset($meta['viewport']['atts']['content'])){
			$recs[]=array(
				'source'=>"{$page['_id']} - {$page['name']}",
				'element'=>"<xmp>{$meta['viewport']['str']}</xmp>",
				'suggestions'=>'Meta viewport tag is missing content attribute'
			);
		}
		//open graph
		$check_fields=array('type','title','description','image','url','site_name');
		foreach($check_fields as $field){
			if(!isset($meta["og:{$field}"])){
				$recs[]=array(
					'source'=>"{$page['_id']} - {$page['name']}",
					'element'=>'<xmp><meta property="og:'.$field.'" content="{your content here}" /></xmp>',
					'suggestions'=>"Open Graph Meta {$field} is missing"
				);
			}
		}
		//twitter
		$check_fields=array('title','description','image','site','creator');
		foreach($check_fields as $field){
			if(!isset($meta["twitter:{$field}"])){
				$recs[]=array(
					'source'=>"{$page['_id']} - {$page['name']}",
					'element'=>'<xmp><meta name="twitter:'.$field.'" content="{your content here}" /></xmp>',
					'suggestions'=>"Twitter Meta {$field} is missing"
				);
			}
		}
		/**** Check link tags ***/
		$link=array();
		preg_match_all('/<link[^>]*>/si', $head, $matches);
		foreach($matches[0] as $str){
			$atts=parseHtmlTagAttributes($str);
			if(!isset($atts['rel'])){continue;}
			$key=strtolower($atts['rel']);
			$link[$key]=array(
				'atts'=>$atts,
				'str'=>$str
			);
		}
		ksort($link);
		//canonical tag check
		if(!isset($link['canonical'])){
			$recs[]=array(
				'source'=>"{$page['_id']} - {$page['name']}",
				'element'=>'<xmp><link rel="canonical" href="{your href here}" /></xmp>',
				'suggestions'=>'Canonical link is missing'
			);
		}
		elseif(!isset($link['canonical']['atts']['href'])){
			$recs[]=array(
				'source'=>"{$page['_id']} - {$page['name']}",
				'element'=>"<xmp>{$link['canonical']['str']}</xmp>",
				'suggestions'=>'Canonical link is missing href attribute'
			);
		}
		elseif(!stringContains($link['canonical']['atts']['href'],$page['name'])){
			$recs[]=array(
				'source'=>"{$page['_id']} - {$page['name']}",
				'element'=>"<xmp>{$link['canonical']['str']}</xmp>",
				'suggestions'=>'Canonical link is should include page name'
			);
		}
	}
	//echo printValue($meta);
	return $recs;
}
//check if all images have alt tags
function websiteGraderImages(){
	$recs=array();
	$pages=websiteGraderActivePages();
	$pattern = '/<img[^>]* src=\"([^\"]*)\"[^>]*>/si';
	foreach($pages as $page){
		$body=websiteGraderGetPageBody($page['name']);
		preg_match_all($pattern, $body, $matches);
		foreach($matches[0] as $str){
			//confirm it has an alt and title attribute
			$atts=parseHtmlTagAttributes($str);
			$missing=array();
			if(!isset($atts['alt'])){
				$missing[]="missing alt attribute";
			}
			if(!isset($atts['title'])){
				$missing[]="missing title attribute";
			}
			if(count($missing)){
				$recs[]=array(
					'source'=>"{$page['_id']} - {$page['name']}",
					'element'=>"<xmp>{$str}</xmp>",
					'suggestions'=>implode('<br />'.PHP_EOL,$missing)
				);
			}
		}
	}
	return $recs;
}
function websiteGraderActivePages(){
	global $websiteGraderActivePagesCache;
	if(isset($websiteGraderActivePagesCache[0])){
		return $websiteGraderActivePagesCache;
	}
	$template=websiteGraderActiveTemplate();
	$opts=array(
		'-table'=>'_pages',
		'_template'=>$template['_template'],
		'-fields'=>'_id,name,permalink'
	);
	//echo printValue($template).printValue($opts);
	$websiteGraderActivePagesCache=getDBRecords($opts);
	return $websiteGraderActivePagesCache;
}
function websiteGraderActiveTemplate(){
	global $websiteGraderActiveTemplateCache;
	if(isNum($websiteGraderActiveTemplateCache)){
		return $websiteGraderActiveTemplateCache;
	}
	$rec=getDBRecord(array(
		'-table'=>'_pages',
		'-where'=>"name='index' or permalink='index'",
		'-fields'=>'_id,_template,name'
	));
	$websiteGraderActiveTemplateCache=$rec['_template'];
	return $websiteGraderActiveTemplateCache;
}
function websiteGraderGetPageBody($name){
	global $websiteGraderGetPageBodyCache;
	if(isset($websiteGraderGetPageBodyCache[$name])){
		return $websiteGraderGetPageBodyCache[$name];
	}
	$prefix=isSSL()?'https':'http';
	$url="{$prefix}://{$_SERVER['HTTP_HOST']}/{$page['name']}";
	$post=postURL($url,array('-method'=>'GET'));
	$websiteGraderGetPageBodyCache[$name]=$post['body'];
	return $websiteGraderGetPageBodyCache[$name];
}
function websiteGraderList($recs,$listopts=array()){
	$opts=array(
		'-list'=>$recs,
		'-tableclass'=>'table bordered is-narrow condensed striped',
		'-hidesearch'=>1,
		'suggestions_class'=>'w_nowrap',
		'source_class'=>'w_nowrap',
		'element_style'=>'max-width:70vw;text-overflow:ellipsis;overflow:hidden;'
	);
	foreach($listopts as $k=>$v){
		if(!strlen($v)){unset($opts[$k]);}
		else{$opts[$k]=$v;}
	}
	return databaseListRecords($opts);

}
?>