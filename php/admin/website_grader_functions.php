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
		https://developers.facebook.com/tools/debug
		https://www.opengraph.xyz/
		The recommended resolution for an OG image is 1200 pixels x 627 pixels (1.91/1 ratio) but don't exceed the 5MB size limit.
	DONE: twitter meta tags
	Header tags
	DONE: viewport meta tag for responsive
	broken links
	DONE: images that are too large
	DONE: sitemap.xml
	DONE: robots.txt
	meta description actually describes page content
	short descriptive URLs (page names)
	one H1 tag on each page
	revelent external links
	descriptive alt tags of images
	loads fast
	check for duplicate content
	socail media links - instagram, facebook, youtube
	SSL
	enough content
	title tag has to be unique per page
*/
function websiteGraderMisc(){
	$recs=array();
	$baseurl=websiteGraderGetBaseURL();
	$files=array('robots.txt','sitemap.xml');
	foreach($files as $file){
		//check for robots.txt
		$info=websiteGraderGetURLHeader("{$baseurl}/{$file}");
		if($info['http_code']==404 || $info['download_content_length'] == -1){
			$recs[]=array(
				'source'=>"/",
				'element'=>"/{$file}",
				'suggestions'=>"{$file} is missing"
			);
		}
	}
	return $recs;
}
function websiteGraderPage(){
	$recs=array();
	$pages=websiteGraderActivePages();
	foreach($pages as $page){
		$body=websiteGraderGetPageBody($page['name']);
		if(!preg_match('/<head>(.*)<\/head>/si',$body,$m)){
			$recs[]=array(
				'page'=>websiteGraderPageEditLink($page['_id'],$page['name']),
				'element'=>"<xmp style=\"margin:0px;\"><head></head></xmp>",
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
				'page'=>websiteGraderPageEditLink($page['_id'],$page['name']),
				'element'=>"<xmp style=\"margin:0px;\"><title></title></xmp>",
				'suggestions'=>'Title is missing'
			);
		}
		else{
			$len=strlen($title);
			if($len > 80){
				$recs[]=array(
					'page'=>websiteGraderPageEditLink($page['_id'],$page['name']),
					'element'=>"<xmp style=\"margin:0px;\"><title>{$title}</title></xmp>",
					'suggestions'=>'Title is too long ({$len} chars. Best between 40 and 80)'
				);
			}
			elseif($len < 40){
				$recs[]=array(
					'page'=>websiteGraderPageEditLink($page['_id'],$page['name']),
					'element'=>"<xmp style=\"margin:0px;\"><title>{$title}</title></xmp>",
					'suggestions'=>"Title is too short ({$len} chars. Best between 40 and 80)"
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
				'page'=>websiteGraderPageEditLink($page['_id'],$page['name']),
				'element'=>'<xmp style="margin:0px;"><meta name="description" content="{your description here}" /></xmp>',
				'suggestions'=>'Meta description tag is missing'
			);
		}
		elseif(!isset($meta['description']['atts']['content'])){
			$recs[]=array(
				'page'=>websiteGraderPageEditLink($page['_id'],$page['name']),
				'element'=>"<xmp style=\"margin:0px;\">{$meta['description']['str']}</xmp>",
				'suggestions'=>'Meta description tag is missing content attribute'
			);
		}
		else{
			$len=strlen($meta['description']['atts']['content']);
			if($len > 160){
				$recs[]=array(
					'page'=>websiteGraderPageEditLink($page['_id'],$page['name']),
					'element'=>"<xmp style=\"margin:0px;\">{$meta['description']['str']}</xmp>",
					'suggestions'=>"Meta description is too long ({$len} chars. Best between 140 and 160)"
				);
			}
			elseif($len < 140){
				$recs[]=array(
					'page'=>websiteGraderPageEditLink($page['_id'],$page['name']),
					'element'=>"<xmp style=\"margin:0px;\">{$meta['description']['str']}</xmp>",
					'suggestions'=>"Meta description is too short ({$len} chars. Best between 140 and 160)"
				);
			}
		}
		//robots
		if(!isset($meta['robots'])){
			$recs[]=array(
				'page'=>websiteGraderPageEditLink($page['_id'],$page['name']),
				'element'=>'<xmp style="margin:0px;"><meta name="robots" content="index, follow" /></xmp>',
				'suggestions'=>'Meta robots tag is missing'
			);
		}
		elseif(!isset($meta['robots']['atts']['content'])){
			$recs[]=array(
				'page'=>websiteGraderPageEditLink($page['_id'],$page['name']),
				'element'=>"<xmp style=\"margin:0px;\">{$meta['robots']['str']}</xmp>",
				'suggestions'=>'Meta robots tag is missing content attribute'
			);
		}
		elseif(stringContains($meta['robots']['atts']['content'],'noindex')){
			$recs[]=array(
				'page'=>websiteGraderPageEditLink($page['_id'],$page['name']),
				'element'=>"<xmp style=\"margin:0px;\">{$meta['robots']['str']}</xmp>",
				'suggestions'=>'Meta robots tag specifies to NOT index this page'
			);
		}
		//viewport
		if(!isset($meta['viewport'])){
			$recs[]=array(
				'page'=>websiteGraderPageEditLink($page['_id'],$page['name']),
				'element'=>'<xmp style="margin:0px;"><meta name="viewport" content="width=device-width,initial-scale=1.0" /></xmp>',
				'suggestions'=>'Meta robots tag is missing'
			);
		}
		elseif(!isset($meta['viewport']['atts']['content'])){
			$recs[]=array(
				'page'=>websiteGraderPageEditLink($page['_id'],$page['name']),
				'element'=>"<xmp style=\"margin:0px;\">{$meta['viewport']['str']}</xmp>",
				'suggestions'=>'Meta viewport tag is missing content attribute'
			);
		}
		//open graph
		$check_fields=array('type','title','description','image','url','site_name');
		foreach($check_fields as $field){
			if(!isset($meta["og:{$field}"])){
				$recs[]=array(
					'page'=>websiteGraderPageEditLink($page['_id'],$page['name']),
					'element'=>'<xmp style="margin:0px;"><meta property="og:'.$field.'" content="{your content here}" /></xmp>',
					'suggestions'=>"Open Graph Meta {$field} is missing"
				);
			}
		}
		//twitter
		$check_fields=array('title','description','image:src','site','creator');
		foreach($check_fields as $field){
			if(!isset($meta["twitter:{$field}"])){
				$recs[]=array(
					'page'=>websiteGraderPageEditLink($page['_id'],$page['name']),
					'element'=>'<xmp style="margin:0px;"><meta name="twitter:'.$field.'" content="{your content here}" /></xmp>',
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
				'page'=>websiteGraderPageEditLink($page['_id'],$page['name']),
				'element'=>'<xmp style="margin:0px;"><link rel="canonical" href="{your href here}" /></xmp>',
				'suggestions'=>'Canonical link is missing'
			);
		}
		elseif(!isset($link['canonical']['atts']['href'])){
			$recs[]=array(
				'page'=>websiteGraderPageEditLink($page['_id'],$page['name']),
				'element'=>"<xmp style=\"margin:0px;\">{$link['canonical']['str']}</xmp>",
				'suggestions'=>'Canonical link is missing href attribute'
			);
		}
		elseif($page['name'] != 'index' && !stringEndsWith($link['canonical']['atts']['href'],"/{$page['name']}/")){
			$recs[]=array(
				'page'=>websiteGraderPageEditLink($page['_id'],$page['name']),
				'element'=>"<xmp style=\"margin:0px;\">{$link['canonical']['str']}</xmp>",
				'suggestions'=>'Canonical link is should include page name'
			);
		}
		/**** Check img tags ***/
		$img=array();
		preg_match_all('/<img[^>]*>/si', $body, $matches);
		foreach($matches[0] as $str){
			$atts=parseHtmlTagAttributes($str);
			$missing=array();
			if(!isset($atts['alt'])){
				$missing[]="missing alt attribute";
			}
			if(!isset($atts['src'])){
				$missing[]="missing src attribute";
			}
			else{
				$src=$atts['src'];
				if(stringBeginsWith($src,'//')){
					$src='https:'.$src;
				}
				elseif(stringBeginsWith($src,'/')){
					$src=websiteGraderGetBaseURL().$src;
				}
				$info=websiteGraderGetURLHeader($src);
				//max filesize of 300,000 bytes
				if($info['download_content_length'] > 300000){
					$missing[]="image size is too large (>300k)";
				}
			}
			///<a href="/php/admin.php?_menu=edit&_table_=_pages&_id=1
			if(count($missing)){
				$recs[]=array(
					'page'=>websiteGraderPageEditLink($page['_id'],$page['name']),
					'element'=>"<xmp style=\"margin:0px;\">{$str}</xmp>",
					'suggestions'=>implode('<br />'.PHP_EOL,$missing)
				);
			}
		}
		ksort($link);
	}
	//echo printValue($meta);
	return $recs;
}
function websiteGraderPageEditLink($id,$name){
	$link="<a target=\"_blank\" class=\"w_link\" href=\"/php/admin.php?_menu=edit&_table_=_pages&_id={$id}\">{$id} - {$name} <sup class=\"icon-edit w_smallest\"></sup></a>";
	return $link;
}
function websiteGraderGetURLHeader($url){
	$info=array();
	$curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FILETIME, true);
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    $header = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);
    return $info;
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
	$baseurl=websiteGraderGetBaseURL();
	$url="{$baseurl}/{$page['name']}";
	$post=postURL($url,array('-method'=>'GET'));
	$websiteGraderGetPageBodyCache[$name]=$post['body'];
	return $websiteGraderGetPageBodyCache[$name];
}
function websiteGraderGetBaseURL(){
	global $websiteGraderGetBaseURLCache;
	if(strlen($websiteGraderGetBaseURLCache)){
		return $websiteGraderGetBaseURLCache;
	}
	$prefix='http';
	if(isSSL()){$prefix='https';}
	elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){$prefix='https';}
	elseif(isset($_SERVER['HTTP_X_FORWARDED_SERVER'])){$prefix='https';}
	$websiteGraderGetBaseURLCache="{$prefix}://{$_SERVER['HTTP_HOST']}";
	return $websiteGraderGetBaseURLCache;
}
function websiteGraderList($recs,$listopts=array()){
	$opts=array(
		'-list'=>$recs,
		'-tableclass'=>'table bordered is-narrow condensed striped',
		'-hidesearch'=>1,
		'suggestions_class'=>'w_nowrap',
		'source_class'=>'w_nowrap',
		'page_class'=>'w_nowrap',
		'element_style'=>'max-width:70vw;text-overflow:ellipsis;overflow:hidden;'
	);
	foreach($listopts as $k=>$v){
		if(!strlen($v)){unset($opts[$k]);}
		else{$opts[$k]=$v;}
	}
	return databaseListRecords($opts);

}
?>