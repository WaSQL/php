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
		if($info['http_code']==404){
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
	$gpages=websiteGraderActivePages();
	foreach($gpages as $gpage){
		$body=websiteGraderGetPageBody($gpage['name']);
		if(!preg_match('/<head>(.*)<\/head>/si',$body,$m)){
			$recs[]=array(
				'page'=>websiteGraderPageEditLink($gpage['_id'],$gpage['name']),
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
				'page'=>websiteGraderPageEditLink($gpage['_id'],$gpage['name']),
				'element'=>"<xmp style=\"margin:0px;\"><title></title></xmp>",
				'suggestions'=>'Title is missing'
			);
		}
		else{
			$len=strlen($title);
			if($len > 80){
				$recs[]=array(
					'page'=>websiteGraderPageEditLink($gpage['_id'],$gpage['name']),
					'element'=>"<xmp style=\"margin:0px;\"><title>{$title}</title></xmp>",
					'suggestions'=>"Title is too long ({$len} chars. Best between 40 and 80)"
				);
			}
			elseif($len < 40){
				$recs[]=array(
					'page'=>websiteGraderPageEditLink($gpage['_id'],$gpage['name']),
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
				'page'=>websiteGraderPageEditLink($gpage['_id'],$gpage['name']),
				'element'=>'<xmp style="margin:0px;"><meta name="description" content="{your description here}" /></xmp>',
				'suggestions'=>'Meta description tag is missing'
			);
		}
		elseif(!isset($meta['description']['atts']['content'])){
			$recs[]=array(
				'page'=>websiteGraderPageEditLink($gpage['_id'],$gpage['name']),
				'element'=>"<xmp style=\"margin:0px;\">{$meta['description']['str']}</xmp>",
				'suggestions'=>'Meta description tag is missing content attribute'
			);
		}
		else{
			$len=strlen($meta['description']['atts']['content']);
			if($len > 160){
				$recs[]=array(
					'page'=>websiteGraderPageEditLink($gpage['_id'],$gpage['name']),
					'element'=>"<xmp style=\"margin:0px;\">{$meta['description']['str']}</xmp>",
					'suggestions'=>"Meta description is too long ({$len} chars. Best between 140 and 160)"
				);
			}
			elseif($len < 140){
				$recs[]=array(
					'page'=>websiteGraderPageEditLink($gpage['_id'],$gpage['name']),
					'element'=>"<xmp style=\"margin:0px;\">{$meta['description']['str']}</xmp>",
					'suggestions'=>"Meta description is too short ({$len} chars. Best between 140 and 160)"
				);
			}
		}
		//robots
		if(!isset($meta['robots'])){
			$recs[]=array(
				'page'=>websiteGraderPageEditLink($gpage['_id'],$gpage['name']),
				'element'=>'<xmp style="margin:0px;"><meta name="robots" content="index, follow" /></xmp>',
				'suggestions'=>'Meta robots tag is missing'
			);
		}
		elseif(!isset($meta['robots']['atts']['content'])){
			$recs[]=array(
				'page'=>websiteGraderPageEditLink($gpage['_id'],$gpage['name']),
				'element'=>"<xmp style=\"margin:0px;\">{$meta['robots']['str']}</xmp>",
				'suggestions'=>'Meta robots tag is missing content attribute'
			);
		}
		elseif(stringContains($meta['robots']['atts']['content'],'noindex')){
			$recs[]=array(
				'page'=>websiteGraderPageEditLink($gpage['_id'],$gpage['name']),
				'element'=>"<xmp style=\"margin:0px;\">{$meta['robots']['str']}</xmp>",
				'suggestions'=>'Meta robots tag specifies to NOT index this page'
			);
		}
		//viewport
		if(!isset($meta['viewport'])){
			$recs[]=array(
				'page'=>websiteGraderPageEditLink($gpage['_id'],$gpage['name']),
				'element'=>'<xmp style="margin:0px;"><meta name="viewport" content="width=device-width,initial-scale=1.0" /></xmp>',
				'suggestions'=>'Meta robots tag is missing'
			);
		}
		elseif(!isset($meta['viewport']['atts']['content'])){
			$recs[]=array(
				'page'=>websiteGraderPageEditLink($gpage['_id'],$gpage['name']),
				'element'=>"<xmp style=\"margin:0px;\">{$meta['viewport']['str']}</xmp>",
				'suggestions'=>'Meta viewport tag is missing content attribute'
			);
		}
		//open graph
		$check_fields=array('type','title','description','image','url','site_name');
		foreach($check_fields as $field){
			if(!isset($meta["og:{$field}"])){
				$recs[]=array(
					'page'=>websiteGraderPageEditLink($gpage['_id'],$gpage['name']),
					'element'=>'<xmp style="margin:0px;"><meta property="og:'.$field.'" content="{your content here}" /></xmp>',
					'suggestions'=>"Open Graph Meta {$field} is missing"
				);
			}
		}
		//twitter (X) cards - card type + image are required for a card to render; image:src is deprecated in favor of image
		$check_fields=array('card','title','description','image');
		foreach($check_fields as $field){
			if(!isset($meta["twitter:{$field}"])){
				$recs[]=array(
					'page'=>websiteGraderPageEditLink($gpage['_id'],$gpage['name']),
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
				'page'=>websiteGraderPageEditLink($gpage['_id'],$gpage['name']),
				'element'=>'<xmp style="margin:0px;"><link rel="canonical" href="{your href here}" /></xmp>',
				'suggestions'=>'Canonical link is missing'
			);
		}
		elseif(!isset($link['canonical']['atts']['href'])){
			$recs[]=array(
				'page'=>websiteGraderPageEditLink($gpage['_id'],$gpage['name']),
				'element'=>"<xmp style=\"margin:0px;\">{$link['canonical']['str']}</xmp>",
				'suggestions'=>'Canonical link is missing href attribute'
			);
		}
		elseif($gpage['name'] != 'index' && !stringContains($link['canonical']['atts']['href'],$gpage['name'])){
			$recs[]=array(
				'page'=>websiteGraderPageEditLink($gpage['_id'],$gpage['name']),
				'element'=>"<xmp style=\"margin:0px;\">{$link['canonical']['str']}</xmp>",
				'suggestions'=>"Canonical link is should include page name "
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
					'page'=>websiteGraderPageEditLink($gpage['_id'],$gpage['name']),
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
	$template_id=websiteGraderActiveTemplate();
	//echo "DEBUG".printValue($template);exit;
	$opts=array(
		'-table'=>'_pages',
		'_template'=>$template_id,
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
	$url="{$baseurl}/{$name}";
	$post=postURL($url,array('-method'=>'GET','-nossl'=>1));
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
	//no issues found - show a clear pass message instead of an empty table
	if(!is_array($recs) || !count($recs)){
		return '<div class="w_success" style="padding:8px 2px 4px;"><span class="icon-mark"></span> All checks passed &mdash; no issues found.</div>';
	}
	$opts=array(
		'-list'=>$recs,
		'-tableclass'=>'wacss_table bordered condensed striped',
		'-hidesearch'=>1,
		'source_class'=>'w_nowrap',
		'page_class'=>'w_nowrap',
		'element_style'=>'max-width:60vw;text-overflow:ellipsis;overflow:hidden;'
	);
	foreach($listopts as $k=>$v){
		if(!strlen($v)){unset($opts[$k]);}
		else{$opts[$k]=$v;}
	}
	return databaseListRecords($opts);

}
//---------- begin AI Optimization (AIO) checks ----------
/*
	AIO = optimizing to be found, understood, and cited by AI answer engines
	(ChatGPT/OpenAI, Claude, Perplexity, Google AI Overviews, Copilot, etc.).
	Signals checked:
		- llms.txt (emerging standard guiding AI crawlers to key content)
		- AI crawler access in robots.txt (are the major AI bots allowed?)
		- JSON-LD structured data (Schema.org) so AI can extract entities
		- a single, clear H1 per page
		- <html lang> so AI/readers detect the content language
		- an authorship signal (meta author or Person/Organization schema) for E-E-A-T
*/
function websiteGraderAIO(){
	$recs=array();
	$baseurl=websiteGraderGetBaseURL();
	$rootlink='<span class="w_nowrap">/ (site root)</span>';
	//---- llms.txt ----
	$info=websiteGraderGetURLHeader("{$baseurl}/llms.txt");
	if(isset($info['http_code']) && $info['http_code']==404){
		$recs[]=array(
			'page'=>$rootlink,
			'element'=>'<xmp style="margin:0px;">/llms.txt</xmp>',
			'suggestions'=>'llms.txt is missing. This emerging standard lets AI assistants (ChatGPT, Claude, Perplexity, ...) discover your most important content. Add a markdown /llms.txt that links to your key pages and docs.'
		);
	}
	//---- AI crawler access in robots.txt ----
	$aibots=array('GPTBot','OAI-SearchBot','ChatGPT-User','ClaudeBot','anthropic-ai','Claude-Web','PerplexityBot','Perplexity-User','Google-Extended','Applebot-Extended','CCBot','Amazonbot','Bytespider','Meta-ExternalAgent','cohere-ai');
	$robots=websiteGraderGetURLBody("{$baseurl}/robots.txt");
	if(strlen(trim($robots))){
		$blocked=websiteGraderRobotsBlockedBots($robots,$aibots);
		if(count($blocked)){
			$recs[]=array(
				'page'=>$rootlink,
				'element'=>'<xmp style="margin:0px;">robots.txt</xmp>',
				'suggestions'=>'robots.txt blocks these AI crawlers, so your content will NOT be read or cited by them: <b>'.encodeHtml(implode(', ',$blocked)).'</b>. Remove the Disallow rules for any AI engine you want to appear in.'
			);
		}
	}
	//---- per-page AIO checks ----
	$gpages=websiteGraderActivePages();
	foreach($gpages as $gpage){
		$body=websiteGraderGetPageBody($gpage['name']);
		$link=websiteGraderPageEditLink($gpage['_id'],$gpage['name']);
		//<html lang>
		if(!preg_match('/<html[^>]*\blang\s*=\s*["\'][a-z]/si',$body)){
			$recs[]=array(
				'page'=>$link,
				'element'=>'<xmp style="margin:0px;"><html lang="en"></xmp>',
				'suggestions'=>'The &lt;html&gt; tag has no lang attribute. AI models and screen readers use it to detect the content language.'
			);
		}
		//JSON-LD structured data (Schema.org)
		if(!preg_match('/<script[^>]*type\s*=\s*["\']application\/ld\+json["\']/si',$body)){
			$recs[]=array(
				'page'=>$link,
				'element'=>'<xmp style="margin:0px;"><script type="application/ld+json">{ ... }</script></xmp>',
				'suggestions'=>'No JSON-LD structured data (Schema.org) found. AI answer engines rely on it to understand entities (Organization, Article, Product, FAQ, Breadcrumb). Add the relevant schema.'
			);
		}
		//exactly one H1
		$h1cnt=preg_match_all('/<h1[\s>]/si',$body,$m);
		if($h1cnt==0){
			$recs[]=array(
				'page'=>$link,
				'element'=>'<xmp style="margin:0px;"><h1>...</h1></xmp>',
				'suggestions'=>'No H1 heading found. A single, clear H1 tells search and AI crawlers the main topic of the page.'
			);
		}
		elseif($h1cnt > 1){
			$recs[]=array(
				'page'=>$link,
				'element'=>"<xmp style=\"margin:0px;\">{$h1cnt} <h1> tags</xmp>",
				'suggestions'=>"Found {$h1cnt} H1 tags. Use exactly one H1 per page so crawlers can identify the primary topic."
			);
		}
		//authorship / authority signal (E-E-A-T)
		if(!preg_match('/<meta[^>]*name\s*=\s*["\']author["\']/si',$body) && !preg_match('/"@type"\s*:\s*"(Person|Organization)"/si',$body)){
			$recs[]=array(
				'page'=>$link,
				'element'=>'<xmp style="margin:0px;"><meta name="author" content="..." /></xmp>',
				'suggestions'=>'No authorship signal (meta author or Person/Organization schema). AI engines weigh authorship &amp; authority (E-E-A-T) when choosing which sources to cite.'
			);
		}
	}
	return $recs;
}
function websiteGraderGetURLBody($url){
	$post=postURL($url,array('-method'=>'GET','-nossl'=>1));
	return isset($post['body'])?$post['body']:'';
}
//parse robots.txt and return which of $bots are Disallowed from the site root
function websiteGraderRobotsBlockedBots($robots,$bots){
	$lines=preg_split('/\r\n|\r|\n/',$robots);
	$groups=array();//agent(lowercased) => array of disallow paths
	$curagents=array();
	$sawrule=false;
	foreach($lines as $line){
		$line=trim(preg_replace('/#.*$/','',$line));
		if(!strlen($line)){continue;}
		if(preg_match('/^user-agent\s*:\s*(.+)$/i',$line,$m)){
			//a User-agent line following rule lines begins a new group
			if($sawrule){$curagents=array();$sawrule=false;}
			$agent=strtolower(trim($m[1]));
			$curagents[]=$agent;
			if(!isset($groups[$agent])){$groups[$agent]=array();}
		}
		elseif(preg_match('/^disallow\s*:\s*(.*)$/i',$line,$m)){
			$sawrule=true;
			$path=trim($m[1]);
			foreach($curagents as $a){$groups[$a][]=$path;}
		}
		elseif(preg_match('/^allow\s*:/i',$line)){
			$sawrule=true;
		}
	}
	$blocked=array();
	foreach($bots as $bot){
		$key=strtolower($bot);
		//most-specific group wins; fall back to * only if no specific group
		$dis=isset($groups[$key])?$groups[$key]:(isset($groups['*'])?$groups['*']:array());
		foreach($dis as $d){
			if($d==='/'){$blocked[]=$bot;break;}
		}
	}
	return $blocked;
}
?>