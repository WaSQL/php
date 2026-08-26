<?php
/*
	Website Checker (SEO + AIO grader)

	Given ANY website URL, this crawls the live site (it does NOT rely on the local
	_pages table), fetches a bounded set of same-host HTML pages, and grades them for
	SEO and AI Optimization (AIO). Output comes in TWO forms:
		1. a report card - EVERY check shown with a Pass/Fail status, category
		   sub-scores, and an overall percentage grade + description
		   (websiteGraderRenderResults)
		2. a copy/paste AI prompt so an assistant can generate the fixes
		   (websiteGraderAIPrompt)
	Plus a visual social / link-share preview (websiteGraderRenderSocialPreview).

	SEO references:
		https://moz.com/blog/the-ultimate-guide-to-seo-meta-tags
	AIO = optimizing to be found, understood, and cited by AI answer engines
	(ChatGPT/OpenAI, Claude, Perplexity, Google AI Overviews, Copilot, etc.).
*/

//---------- URL / crawl helpers ----------

/**
 * @describe normalize a user-entered URL: trim and ensure an http(s) scheme.
 * @param url string
 * @return string
 */
function websiteGraderNormalizeURL($url){
	$url=trim($url);
	if(!strlen($url)){return '';}
	if(!preg_match('#^https?://#i',$url)){
		$url='https://'.$url;
	}
	return $url;
}

/**
 * @describe fetch a URL and return body + status info (follows redirects, ignores SSL/cert issues).
 * @param url string
 * @return array [body, http_code, content_type, final_url, error]
 */
function websiteGraderFetch($url){
	$post=postURL($url,array('-method'=>'GET','-follow'=>1,'-nossl'=>1,'-timeout'=>25,'-timeout_connect'=>10));
	$info=isset($post['curl_info']) && is_array($post['curl_info'])?$post['curl_info']:array();
	return array(
		'body'=>isset($post['body'])?$post['body']:'',
		'http_code'=>isset($info['http_code'])?$info['http_code']:0,
		'content_type'=>isset($info['content_type'])?$info['content_type']:'',
		'final_url'=>isset($info['url']) && strlen($info['url'])?$info['url']:$url,
		'headers'=>isset($post['headers']) && is_array($post['headers'])?$post['headers']:array(),
		'error'=>isset($post['error'])?$post['error']:''
	);
}

/**
 * @describe resolve an href found in a page to an absolute URL.
 * @param href string, pageurl string, baseurl string
 * @return string absolute url, or '' if it should be skipped
 */
function websiteGraderAbsoluteURL($href,$pageurl,$baseurl){
	$href=trim($href);
	if(!strlen($href)){return '';}
	if(stringBeginsWith($href,'#')){return '';}
	if(preg_match('#^https?://#i',$href)){return $href;}
	//any OTHER URI scheme (mailto:, tel:, sms:, javascript:, data:, ftp:, whatsapp:, facetime:,
	//geo:, ...) is an absolute, non-crawlable URI per RFC 3986 - skip it rather than falling
	//through to relative-path resolution below (which would wrongly turn "sms:5551234567" into
	//"{current-dir}/sms:5551234567" and queue a fake page for every such link on the page).
	if(preg_match('/^[a-zA-Z][a-zA-Z0-9+.\-]*:/',$href)){return '';}
	if(stringBeginsWith($href,'//')){
		$scheme=parse_url($baseurl,PHP_URL_SCHEME);
		return $scheme.':'.$href;
	}
	if(stringBeginsWith($href,'/')){
		return rtrim($baseurl,'/').$href;
	}
	$path=parse_url($pageurl,PHP_URL_PATH);
	if(!strlen($path)){$path='/';}
	$dir=preg_replace('#/[^/]*$#','/',$path);
	if(!strlen($dir)){$dir='/';}
	return rtrim($baseurl,'/').$dir.$href;
}

/**
 * @describe canonicalize a URL for crawl de-duplication: lowercase scheme/host, drop the
 *   default port and fragment, collapse repeated slashes, drop a trailing "/index"|"/default"
 *   (with optional .html/.htm/.php/.aspx), and strip the trailing slash (except root). This is
 *   what makes "/", "/index", "/index.html", and "/shop/" all resolve to one page.
 * @param url string
 * @return string
 */
function websiteGraderCanonicalURL($url){
	$p=parse_url($url);
	if($p===false || !isset($p['host'])){return $url;}
	$scheme=isset($p['scheme'])?strtolower($p['scheme']):'http';
	$host=strtolower($p['host']);
	$port='';
	if(isset($p['port']) && !(($scheme=='http' && $p['port']==80) || ($scheme=='https' && $p['port']==443))){
		$port=':'.$p['port'];
	}
	$path=isset($p['path'])?$p['path']:'/';
	$path=preg_replace('#/+#','/',$path);
	$path=preg_replace('#/(index|default)(\.(html?|php|aspx?))?$#i','/',$path);
	if(strlen($path) > 1){
		$path=rtrim($path,'/');
		if(!strlen($path)){$path='/';}
	}
	$query=isset($p['query']) && strlen($p['query'])?'?'.$p['query']:'';
	return "{$scheme}://{$host}{$port}{$path}{$query}";
}

/**
 * @describe extract same-host, crawlable page links from an HTML body (canonicalized + deduped).
 * @param body string, pageurl string, baseurl string, host string
 * @return array of absolute URLs (deduped)
 */
function websiteGraderExtractLinks($body,$pageurl,$baseurl,$host){
	$links=array();
	if(!preg_match_all('/<a\b[^>]*\bhref\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/si',$body,$m)){
		return array();
	}
	foreach($m[1] as $href){
		$href=trim($href," \t\n\r\0\x0B\"'");
		$abs=websiteGraderAbsoluteURL($href,$pageurl,$baseurl);
		if(!strlen($abs)){continue;}
		$abs=preg_replace('/#.*$/','',$abs);
		if(!strlen($abs)){continue;}
		$parts=parse_url($abs);
		if(!isset($parts['host']) || strtolower($parts['host'])!=strtolower($host)){continue;}
		if(preg_match('/\.(jpe?g|png|gif|svg|webp|ico|css|js|pdf|zip|gz|mp4|mp3|avi|mov|woff2?|ttf|eot|xml|json|csv|xlsx?|docx?)(\?|$)/i',$abs)){continue;}
		$abs=websiteGraderCanonicalURL($abs);
		$links[$abs]=1;
	}
	return array_keys($links);
}

/**
 * @describe filter a list of same-host URLs down to the ones robots.txt allows crawling (user-agent
 *   "*"). Used to keep the crawler itself from ever fetching a Disallow'd path - the per-page
 *   exclusion in websiteGraderRunChecks only stops a fetched page from being GRADED, it can't undo
 *   the fact that the crawl already spent a page slot fetching something a real crawler never would.
 * @param links array of absolute URLs, robots string
 * @return array of absolute URLs (same order, disallowed ones removed)
 */
function websiteGraderCrawlableLinks($links,$robots){
	if(!strlen(trim($robots))){return $links;}
	$out=array();
	foreach($links as $l){
		$path=(string)parse_url($l,PHP_URL_PATH);
		if(!strlen($path)){$path='/';}
		$query=(string)parse_url($l,PHP_URL_QUERY);
		if(strlen($query)){$path.='?'.$query;}
		if(websiteGraderRobotsDisallowsPath($robots,$path)){continue;}
		$out[]=$l;
	}
	return $out;
}

/**
 * @describe detect whether a fetched body is actually an HTML page rather than the plain-text/XML
 *   file it was requested as. Needed because many sites/frameworks (WaSQL included, via a
 *   configured "missing page" fallback - see php/index.php) return HTTP 200 with the site's
 *   normal HTML for ANY unmatched path, so /robots.txt, /sitemap.xml, /llms.txt can all resolve
 *   to a 200 that is really just the homepage or a "page not found" page.
 * @param body string
 * @return bool
 */
function websiteGraderLooksLikeHtmlPage($body){
	return (bool)preg_match('/<html[\s>]|<!doctype\s+html/i',trim((string)$body));
}

/**
 * @describe verify a "well-known" file (robots.txt, sitemap.xml, llms.txt) is genuinely present -
 *   not just "the URL didn't 404". Requires a 2xx status, a non-empty body that is NOT an HTML
 *   page (see websiteGraderLooksLikeHtmlPage), and optionally a pattern the body must match
 *   (e.g. sitemap.xml must contain <urlset>/<sitemapindex>).
 * @param baseurl string, path string (leading slash), requirepattern string|null regex
 * @return array [ok bool, reason string (why it failed, blank when ok)]
 */
function websiteGraderCheckWellKnownFile($baseurl,$path,$requirepattern=null){
	$res=websiteGraderFetch(rtrim($baseurl,'/').$path);
	if(!isNum($res['http_code']) || $res['http_code'] < 200 || $res['http_code'] >= 300){
		return array('ok'=>false,'reason'=>"HTTP {$res['http_code']}");
	}
	$body=trim($res['body']);
	if(!strlen($body)){
		return array('ok'=>false,'reason'=>'the response body was empty');
	}
	if(websiteGraderLooksLikeHtmlPage($body)){
		return array('ok'=>false,'reason'=>'the URL returned an HTML page (likely a catch-all/"page not found" fallback), not a real file');
	}
	if($requirepattern!==null && !preg_match($requirepattern,$body)){
		return array('ok'=>false,'reason'=>"the response did not look like a valid {$path}");
	}
	return array('ok'=>true,'reason'=>'');
}

/**
 * @describe human-readable byte size (e.g. 412 KB, 1.4 MB).
 * @param bytes number
 * @return string
 */
function websiteGraderFormatBytes($bytes){
	$bytes=(float)$bytes;
	if($bytes >= 1048576){return round($bytes/1048576,1).' MB';}
	if($bytes >= 1024){return round($bytes/1024).' KB';}
	return round($bytes).' B';
}

/**
 * @describe truncate a display string to $max chars (adding an ellipsis) - guards against a single
 *   pathologically long, whitespace-free token (e.g. a base64 data: URI image src) blowing up
 *   browser text-layout cost wherever this value later gets rendered (report card or AI prompt).
 * @param s string, max int
 * @return string
 */
function websiteGraderTruncate($s,$max=200){
	$s=(string)$s;
	if(commonStrlen($s) <= $max){return $s;}
	return substr($s,0,$max).'…';
}

/**
 * @describe detect whether a fetched response is a bot-verification challenge page
 *   (Cloudflare "Just a moment...", etc.) rather than the site's real content - these come
 *   back as HTTP 403/503 to automated requests (curl has no JS engine to solve the challenge)
 *   even though a real visitor's browser loads the page fine.
 * @param res array (a websiteGraderFetch() result)
 * @return bool
 */
function websiteGraderIsBotChallenge($res){
	$headers=isset($res['headers']) && is_array($res['headers'])?$res['headers']:array();
	if(isset($headers['cf-mitigated'])){return true;}
	if(isset($headers['server']) && preg_match('/cloudflare/i',$headers['server']) && $res['http_code']>=400 && preg_match('/just a moment|challenges\.cloudflare\.com/i',$res['body'])){
		return true;
	}
	if(preg_match('/<title>\s*(just a moment|attention required|are you a human|access denied)/i',$res['body'])){
		return true;
	}
	return false;
}

/**
 * @describe crawl a website starting at $starturl, following same-host links up to $maxpages.
 * @param starturl string, maxpages int
 * @return array [baseurl, scheme, host, pages(array of [url,body]), robots] OR [error]
 */
function websiteGraderCrawl($starturl,$maxpages){
	if($maxpages < 1){$maxpages=1;}
	if($maxpages > 300){$maxpages=300;}
	$start=websiteGraderFetch($starturl);
	if(strlen($start['error'])){
		return array('error'=>"Could not reach {$starturl}: {$start['error']}");
	}
	if(!isNum($start['http_code']) || $start['http_code']==0){
		return array('error'=>"Could not connect to {$starturl}. Check the URL and try again.");
	}
	if($start['http_code'] >= 400 && websiteGraderIsBotChallenge($start)){
		return array('error'=>"{$starturl} is protected by a bot-verification challenge (e.g. Cloudflare) that blocks automated page grading. This isn't a broken page - real visitors and browsers load it fine, but the grader can't get past the anti-bot check to fetch it.");
	}
	if($start['http_code'] >= 400){
		return array('error'=>"{$starturl} returned HTTP {$start['http_code']}. Cannot grade a page that does not load.");
	}
	if(!preg_match('/html/i',$start['content_type']) && !preg_match('/<html/i',$start['body'])){
		return array('error'=>"{$starturl} did not return an HTML page (content-type: ".htmlspecialchars($start['content_type']).").");
	}
	$finalurl=$start['final_url'];
	$parts=parse_url($finalurl);
	$scheme=isset($parts['scheme'])?strtolower($parts['scheme']):'https';
	$host=isset($parts['host'])?$parts['host']:'';
	$port=isset($parts['port'])?':'.$parts['port']:'';
	$baseurl="{$scheme}://{$host}{$port}";
	//fetch robots.txt BEFORE crawling (not after) so Disallow rules can actually stop the crawler
	//from fetching those paths in the first place - a real search/AI crawler consults robots.txt
	//before requesting a URL, not after. The explicit start URL is still fetched regardless (the
	//user asked to check that exact page); only links discovered while spidering are filtered.
	$rres=websiteGraderFetch("{$baseurl}/robots.txt");
	$robots=($rres['http_code'] >= 200 && $rres['http_code'] < 300 && strlen(trim($rres['body'])) && !websiteGraderLooksLikeHtmlPage($rres['body']))?$rres['body']:'';
	$finalurl=websiteGraderCanonicalURL($finalurl);
	$pages=array();
	$visited=array();
	$pages[$finalurl]=array('url'=>$finalurl,'body'=>$start['body'],'headers'=>$start['headers']);
	$visited[$finalurl]=1;
	$queue=websiteGraderCrawlableLinks(websiteGraderExtractLinks($start['body'],$finalurl,$baseurl,$host),$robots);
	//hard wall-clock cap - each fetch below can take up to ~25s, and a slow/misbehaving site can
	//queue far more links than $maxpages ever accepts, so bound total crawl time regardless of
	//page count (session lock is released around this call, but the request/worker thread is
	//still tied up for as long as this runs).
	$deadline=microtime(true)+180;
	while(count($pages) < $maxpages && count($queue) && microtime(true) < $deadline){
		$url=array_shift($queue);
		if(isset($visited[$url])){continue;}
		$visited[$url]=1;
		$res=websiteGraderFetch($url);
		if($res['http_code'] < 200 || $res['http_code'] >= 400){continue;}
		if(!preg_match('/html/i',$res['content_type']) && !preg_match('/<html/i',$res['body'])){continue;}
		$pages[$url]=array('url'=>$url,'body'=>$res['body'],'headers'=>$res['headers']);
		if(count($pages) >= $maxpages){break;}
		$newlinks=websiteGraderCrawlableLinks(websiteGraderExtractLinks($res['body'],$url,$baseurl,$host),$robots);
		foreach($newlinks as $l){
			if(!isset($visited[$l]) && !in_array($l,$queue)){$queue[]=$l;}
		}
	}
	return array(
		'baseurl'=>$baseurl,
		'scheme'=>$scheme,
		'host'=>$host,
		'pages'=>array_values($pages),
		'robots'=>$robots
	);
}

/**
 * @describe HEAD request for a URL - returns curl_getinfo (http_code, download_content_length, ...).
 * @param url string
 * @return array
 */
function websiteGraderGetURLHeader($url){
	$curl=curl_init();
	curl_setopt($curl,CURLOPT_URL,$url);
	curl_setopt($curl,CURLOPT_FILETIME,true);
	curl_setopt($curl,CURLOPT_NOBODY,true);
	curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($curl,CURLOPT_HEADER,true);
	curl_setopt($curl,CURLOPT_FOLLOWLOCATION,true);
	curl_setopt($curl,CURLOPT_MAXREDIRS,10);
	curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,false);
	curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,false);
	curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,10);
	curl_setopt($curl,CURLOPT_TIMEOUT,20);
	curl_setopt($curl,CURLOPT_USERAGENT,'Mozilla/5.0 (compatible; WaSQL-WebsiteChecker/1.0)');
	$header=curl_exec($curl);
	$info=curl_getinfo($curl);
	curl_close($curl);
	return $info;
}

/**
 * @describe render an external link to a crawled page (opens in a new tab).
 * @param url string
 * @return string
 */
function websiteGraderPageLink($url){
	$safe=htmlspecialchars($url,ENT_QUOTES);
	return "<a target=\"_blank\" rel=\"noopener\" class=\"w_link\" href=\"{$safe}\">{$safe} <sup class=\"icon-link-ext w_smallest\"></sup></a>";
}

/**
 * @describe bound the number of remote image size checks per request so a large crawl stays responsive.
 * @return bool
 */
function websiteGraderImgCheckAllowed(){
	global $websiteGraderImgChecks;
	if(!isNum($websiteGraderImgChecks)){$websiteGraderImgChecks=0;}
	if($websiteGraderImgChecks >= 60){return false;}
	$websiteGraderImgChecks++;
	return true;
}

//---------- checks engine ----------

/**
 * @describe accumulate one instance of a check. Each check tracks pass/total across
 *   pages so we can show a Pass/Fail status and compute a grade.
 * @param checks array (by ref), key string, label string, category string, ok bool, fail array|null
 * @return void
 */
function websiteGraderAddCheck(&$checks,$key,$label,$category,$ok,$fail=null){
	if(!isset($checks[$key])){
		$checks[$key]=array('key'=>$key,'label'=>$label,'category'=>$category,'pass'=>0,'total'=>0,'fails'=>array());
	}
	$checks[$key]['total']++;
	if($ok){$checks[$key]['pass']++;}
	elseif($fail!==null){$checks[$key]['fails'][]=$fail;}
	return;
}

/**
 * @describe display order + labels for check categories.
 * @return array key => label
 */
function websiteGraderCategories(){
	return array(
		'SEO'=>'SEO &mdash; Page Checks',
		'Social'=>'Social / Open Graph',
		'AIO'=>'AI Optimization (AIO)',
		'Misc'=>'Misc / Technical'
	);
}

/**
 * @describe plain-language glossary for the jargon used in check labels (Open Graph, canonical,
 *   JSON-LD, etc.), in display order. Keyed by the same check 'key' used in websiteGraderAddCheck,
 *   so the email only shows definitions for terms that actually appear in that report.
 * @return array key => ['term'=>string,'def'=>string]
 */
function websiteGraderTermsGlossary(){
	return array(
		'ssl'=>array('term'=>'HTTPS / SSL','def'=>"The padlock icon shown in browsers. It encrypts traffic between your site and its visitors, and search engines favor secure sites over insecure ones."),
		'title'=>array('term'=>'Title tag','def'=>"The clickable headline shown in search results and browser tabs for a page."),
		'description'=>array('term'=>'Meta description','def'=>"The short summary shown under a page's title in search results — often what convinces someone to click through."),
		'metarobots'=>array('term'=>'Meta robots tag','def'=>"A setting that tells search engines whether to index a page and follow its links."),
		'viewport'=>array('term'=>'Responsive viewport','def'=>"A setting that makes a page resize properly on phones and tablets instead of just looking like a shrunk desktop page."),
		'canonical'=>array('term'=>'Canonical link','def'=>"A tag that tells search engines which version of a page is the official one, avoiding confusion when the same content can be reached at more than one URL."),
		'images'=>array('term'=>'Alt text','def'=>"A short written description of an image. It's read aloud by screen readers and used by search engines, since they can't 'see' pictures."),
		'opengraph'=>array('term'=>'Open Graph','def'=>"A set of tags that control how a link looks when shared on Facebook, LinkedIn, iMessage, Slack, and similar apps — the preview image, title, and description."),
		'twitter'=>array('term'=>'Twitter/X card','def'=>"Like Open Graph, but for how a link looks when shared on X (formerly Twitter)."),
		'htmllang'=>array('term'=>'<html lang>','def'=>"A tag declaring what language a page is written in, helping browsers, screen readers, and search engines interpret it correctly."),
		'jsonld'=>array('term'=>'JSON-LD / structured data','def'=>"Hidden, machine-readable notes on a page describing what it is — an article, product, FAQ, and so on. Search engines and AI tools use it to better understand and display your content. Also called Schema.org markup."),
		'h1'=>array('term'=>'H1 heading','def'=>"The main heading of a page. Having exactly one clear H1 helps readers and search engines understand what the page is about."),
		'authorship'=>array('term'=>'Authorship / E-E-A-T','def'=>"Short for Experience, Expertise, Authoritativeness, and Trust — signals, like a named author, that help search engines and AI judge how credible your content is."),
		'robots'=>array('term'=>'robots.txt','def'=>"A small text file that tells search engines and AI crawlers which parts of your site they're allowed to read."),
		'sitemap'=>array('term'=>'sitemap.xml','def'=>"A file listing every page on your site, so search engines can find and index all of them."),
		'llms'=>array('term'=>'llms.txt','def'=>"A newer file, similar to robots.txt, aimed at AI systems — it describes your site's content so AI assistants can find and understand it."),
		'aiaccess'=>array('term'=>'AI crawlers','def'=>"Automated visitors from AI systems (like the ones behind ChatGPT or Claude) that read your pages so they can reference or cite your content in answers."),
		'robotssitemap'=>array('term'=>'Sitemap in robots.txt','def'=>"A line in robots.txt pointing crawlers straight at your sitemap.xml, so they don't have to guess where it lives."),
		'favicon'=>array('term'=>'Favicon','def'=>"The small icon shown in browser tabs, bookmarks, and some search results — a basic sign of a real, maintained site."),
		'richschema'=>array('term'=>'Breadcrumb / FAQ schema','def'=>"Structured data marking up a page's breadcrumb trail or Q&A content — the two schema types AI answer engines rely on most when citing a source."),
		'headinghierarchy'=>array('term'=>'Heading hierarchy','def'=>"The nesting order of a page's headings (H1, H2, H3...). Skipping a level (H1 straight to H3) makes it harder for search/AI engines to tell how the content is organized."),
		'dupcontent'=>array('term'=>'Duplicate titles/descriptions','def'=>"Two or more pages using the exact same title or meta description, which makes it harder for search engines to tell the pages apart."),
		'httpsredirect'=>array('term'=>'HTTP&rarr;HTTPS redirect','def'=>"Whether the plain http:// version of your site automatically forwards visitors to the secure https:// version, instead of serving the same content at both."),
		'wwwcanonical'=>array('term'=>'www / non-www canonicalization','def'=>"Whether the www and non-www versions of your domain both work but one properly redirects to the other, instead of serving the same content at two separate addresses.")
	);
}

/**
 * @describe run every SEO/AIO/Social/Misc check against the crawl and return the results. Pages
 *   the site deliberately excludes from indexing (robots.txt Disallow, or a noindex meta robots
 *   tag) are NOT run through the on-page checks - a real search/AI crawler will never evaluate
 *   them either, so doing so only produces false-positive failures. They're reported separately
 *   via the optional &$excluded out-param instead of being mixed into the pass/fail counts.
 * @param baseurl string, pages array of [url,body], robots string, excluded array (by ref, out)
 * @return array of check results (key => [label,category,pass,total,fails])
 */
function websiteGraderRunChecks($baseurl,$pages,$robots,&$excluded=array()){
	$checks=array();
	$excluded=array();
	//===== site-wide (Misc / AIO) =====
	//HTTPS / SSL
	$ssl_ok=stringBeginsWith(strtolower($baseurl),'https://');
	websiteGraderAddCheck($checks,'ssl','HTTPS / SSL','Misc',$ssl_ok,$ssl_ok?null:array(
		'suggestion'=>'Site is served over plain HTTP. Serve it over HTTPS (SSL) &mdash; a ranking signal and required for trust.'
	));
	//robots.txt - $robots was already fetched+validated (non-empty, not an HTML fallback page) by websiteGraderCrawl()
	$robots_ok=strlen(trim($robots)) > 0;
	websiteGraderAddCheck($checks,'robots','robots.txt present','Misc',$robots_ok,$robots_ok?null:array(
		'element'=>'<xmp style="margin:0px;">/robots.txt</xmp>','suggestion'=>'robots.txt is missing (the URL either 404s or falls back to a normal HTML page). Add a real robots.txt to guide search/AI crawlers.'
	));
	//sitemap.xml - must be reachable AND actually contain a <urlset>/<sitemapindex>, not just "not a 404"
	$sitemapcheck=websiteGraderCheckWellKnownFile($baseurl,'/sitemap.xml','#<urlset\b|<sitemapindex\b#i');
	websiteGraderAddCheck($checks,'sitemap','sitemap.xml present','Misc',$sitemapcheck['ok'],$sitemapcheck['ok']?null:array(
		'element'=>'<xmp style="margin:0px;">/sitemap.xml</xmp>','suggestion'=>'sitemap.xml is missing or invalid ('.$sitemapcheck['reason'].'). Add a real XML sitemap so crawlers can find all your pages.'
	));
	//llms.txt (AIO) - must be reachable and NOT just the site's normal HTML page
	$llmscheck=websiteGraderCheckWellKnownFile($baseurl,'/llms.txt');
	websiteGraderAddCheck($checks,'llms','llms.txt present','AIO',$llmscheck['ok'],$llmscheck['ok']?null:array(
		'element'=>'<xmp style="margin:0px;">/llms.txt</xmp>','suggestion'=>'llms.txt is missing or invalid ('.$llmscheck['reason'].'). This emerging standard lets AI assistants (ChatGPT, Claude, Perplexity, ...) discover your key content. Add a markdown /llms.txt linking to your important pages.'
	));
	//AI crawler access (AIO)
	$aibots=array('GPTBot','OAI-SearchBot','ChatGPT-User','ClaudeBot','anthropic-ai','Claude-Web','Claude-SearchBot','Claude-User','PerplexityBot','Perplexity-User','Google-Extended','Google-CloudVertexBot','Applebot','Applebot-Extended','CCBot','Amazonbot','Bytespider','Meta-ExternalAgent','Meta-ExternalFetcher','cohere-ai','MistralAI-User','DuckAssistBot','YouBot');
	$blocked=strlen(trim($robots))?websiteGraderRobotsBlockedBots($robots,$aibots):array();
	$ai_ok=count($blocked)==0;
	websiteGraderAddCheck($checks,'aiaccess','AI crawlers allowed','AIO',$ai_ok,$ai_ok?null:array(
		'element'=>'<xmp style="margin:0px;">robots.txt</xmp>',
		'suggestion'=>'robots.txt blocks these AI crawlers, so your content will NOT be read or cited by them: '.encodeHtml(implode(', ',$blocked)).'. Remove the Disallow rules for any AI engine you want to appear in.'
	));
	//sitemap referenced in robots.txt - only meaningful when robots.txt itself is present (already
	//flagged separately above if not), so a crawler that reads robots.txt can discover the sitemap
	//without having to guess/probe for it.
	if($robots_ok){
		$robots_sitemap_ok=(bool)preg_match('/^\s*Sitemap\s*:\s*\S+/mi',$robots);
		websiteGraderAddCheck($checks,'robotssitemap','Sitemap listed in robots.txt','Misc',$robots_sitemap_ok,$robots_sitemap_ok?null:array(
			'element'=>'<xmp style="margin:0px;">Sitemap: '.encodeHtml(rtrim($baseurl,'/')).'/sitemap.xml</xmp>','suggestion'=>'robots.txt has no "Sitemap:" line. Add one pointing at your sitemap.xml so crawlers can discover it without having to guess the URL.'
		));
	}
	//favicon - check the homepage's <link rel="icon"> first, then fall back to /favicon.ico
	$homebody=(count($pages) && isset($pages[0]['body']))?$pages[0]['body']:'';
	$favicon_ok=(bool)preg_match('/<link[^>]*\brel\s*=\s*["\'](?:shortcut icon|icon|apple-touch-icon)["\'][^>]*>/si',$homebody);
	if(!$favicon_ok){
		$favres=websiteGraderFetch(rtrim($baseurl,'/').'/favicon.ico');
		$favicon_ok=(isNum($favres['http_code']) && $favres['http_code']>=200 && $favres['http_code']<300 && strlen($favres['body']));
	}
	websiteGraderAddCheck($checks,'favicon','Favicon present','Misc',$favicon_ok,$favicon_ok?null:array(
		'element'=>'<xmp style="margin:0px;"><link rel="icon" href="/favicon.ico" /></xmp>','suggestion'=>'No favicon found (neither a &lt;link rel="icon"&gt; tag nor a working /favicon.ico). Add one - it shows in browser tabs, bookmarks, and some search/AI result listings.'
	));
	//rich schema types (AIO) - BreadcrumbList/FAQPage are the schema types that most directly drive
	//AI Overview / rich-result citation, so check for them site-wide rather than per-page (most
	//individual pages have no reason to carry either type).
	$richschema_ok=false;
	foreach($pages as $rp){
		if(preg_match('/"@type"\s*:\s*"(BreadcrumbList|FAQPage)"/si',isset($rp['body'])?$rp['body']:'')){$richschema_ok=true;break;}
	}
	websiteGraderAddCheck($checks,'richschema','Breadcrumb/FAQ schema present','AIO',$richschema_ok,$richschema_ok?null:array(
		'element'=>'<xmp style="margin:0px;">{ "@type": "BreadcrumbList", ... }</xmp>','suggestion'=>'No BreadcrumbList or FAQPage structured data found on any crawled page. These are the schema types AI answer engines rely on most for rich-result citation - add BreadcrumbList to category/product pages and FAQPage anywhere you answer common questions.'
	));
	$host=(string)parse_url($baseurl,PHP_URL_HOST);
	$scheme=(string)parse_url($baseurl,PHP_URL_SCHEME);
	//HTTP -> HTTPS redirect - only meaningful when the site supports https at all (already flagged
	//separately above if not); confirms the plain-http origin doesn't ALSO serve duplicate content.
	if($ssl_ok && strlen($host)){
		$httpres=websiteGraderFetch('http://'.$host.'/');
		$httpsredirect_ok=stringBeginsWith(strtolower($httpres['final_url']),'https://');
		websiteGraderAddCheck($checks,'httpsredirect','HTTP redirects to HTTPS','Misc',$httpsredirect_ok,$httpsredirect_ok?null:array(
			'suggestion'=>'http://'.encodeHtml($host).'/ does not redirect to https. Add a 301 redirect from http to https so the two don\'t serve duplicate content under separate URLs.'
		));
	}
	//www / non-www canonicalization - fetch the "other" host variant and confirm it either doesn't
	//resolve (no risk) or 301s to the canonical host, rather than serving duplicate content itself.
	if(strlen($host)){
		$althost=stringBeginsWith(strtolower($host),'www.')?substr($host,4):('www.'.$host);
		$altres=websiteGraderFetch($scheme.'://'.$althost.'/');
		$altfinalhost=strtolower((string)parse_url($altres['final_url'],PHP_URL_HOST));
		$wwwcanonical_ok=(!isNum($altres['http_code']) || $altres['http_code']==0 || strlen($altres['error']) || $altfinalhost===strtolower($host));
		websiteGraderAddCheck($checks,'wwwcanonical','www/non-www canonicalization','Misc',$wwwcanonical_ok,$wwwcanonical_ok?null:array(
			'suggestion'=>$scheme.'://'.encodeHtml($althost).'/ serves content without redirecting to '.encodeHtml($host).'. Add a redirect from one host to the other so the same content isn\'t reachable at two separate URLs.'
		));
	}

	//===== per-page =====
	$titlemap=array();$descmap=array();
	foreach($pages as $gpage){
		$url=$gpage['url'];
		$body=$gpage['body'];
		$parsed=websiteGraderParseMeta($body);
		$meta=$parsed['meta'];
		//intentionally-excluded pages (robots.txt Disallow and/or meta robots noindex) get
		//bucketed here and skip every on-page check below - see the function doc comment.
		$rob=isset($meta['robots'])?$meta['robots']:null;
		$noindex=($rob!==null && stringContains($rob,'noindex'));
		$path=(string)parse_url($url,PHP_URL_PATH);
		if(!strlen($path)){$path='/';}
		$query=(string)parse_url($url,PHP_URL_QUERY);
		if(strlen($query)){$path.='?'.$query;}
		$robots_blocked=strlen(trim($robots))?websiteGraderRobotsDisallowsPath($robots,$path):false;
		if($noindex || $robots_blocked){
			$why=array();
			if($robots_blocked){$why[]='blocked by robots.txt';}
			if($noindex){$why[]='meta robots noindex';}
			$excluded[]=array('url'=>$url,'reason'=>implode(' & ',$why));
			continue;
		}
		$link=websiteGraderPageLink($url);
		$title=$parsed['title'];
		$head=$body;
		if(preg_match('/<head[^>]*>(.*)<\/head>/si',$body,$m)){$head=$m[1];}

		//--- SEO ---
		//head present
		$head_ok=(bool)preg_match('/<head[\s>]/si',$body);
		websiteGraderAddCheck($checks,'head','Has &lt;head&gt; section','SEO',$head_ok,$head_ok?null:array(
			'page'=>$link,'element'=>'<xmp style="margin:0px;"><head></head></xmp>','suggestion'=>'Head tag is missing entirely.'
		));
		//title
		$tlen=commonStrlen($title);
		$title_ok=false;$t_sugg='';
		if(!strlen(trim($title))){$t_sugg='Title is missing.';}
		elseif($tlen > 80){$t_sugg="Title is too long ({$tlen} chars; best 40-80).";}
		elseif($tlen < 40){$t_sugg="Title is too short ({$tlen} chars; best 40-80).";}
		else{$title_ok=true;}
		websiteGraderAddCheck($checks,'title','Title tag (40-80 chars)','SEO',$title_ok,$title_ok?null:array(
			'page'=>$link,'element'=>"<xmp style=\"margin:0px;\"><title>{$title}</title></xmp>",'suggestion'=>$t_sugg
		));
		//meta description
		$desc=isset($meta['description'])?$meta['description']:null;
		$desc_ok=false;$d_sugg='';
		if($desc===null){$d_sugg='Meta description is missing.';}
		else{
			$dlen=commonStrlen($desc);
			if($dlen > 160){$d_sugg="Meta description is too long ({$dlen} chars; best 140-160).";}
			elseif($dlen < 140){$d_sugg="Meta description is too short ({$dlen} chars; best 140-160).";}
			else{$desc_ok=true;}
		}
		websiteGraderAddCheck($checks,'description','Meta description (140-160 chars)','SEO',$desc_ok,$desc_ok?null:array(
			'page'=>$link,'element'=>'<xmp style="margin:0px;"><meta name="description" content="'.encodeHtml((string)$desc).'" /></xmp>','suggestion'=>$d_sugg
		));
		//track titles/descriptions across pages for the site-wide duplicate-content check below
		$normtitle=strtolower(trim((string)$title));
		if(strlen($normtitle)){$titlemap[$normtitle][]=$url;}
		if($desc!==null){
			$normdesc=strtolower(trim($desc));
			if(strlen($normdesc)){$descmap[$normdesc][]=$url;}
		}
		//meta robots - a noindex page was already bucketed into $excluded above and never
		//reaches here, so the only remaining failure case is the tag being missing entirely.
		$rob_ok=($rob!==null);
		websiteGraderAddCheck($checks,'metarobots','Meta robots tag','SEO',$rob_ok,$rob_ok?null:array(
			'page'=>$link,'element'=>'<xmp style="margin:0px;"><meta name="robots" content="index, follow" /></xmp>','suggestion'=>'Meta robots tag is missing (add content="index, follow").'
		));
		//viewport
		$vp=isset($meta['viewport'])?trim($meta['viewport']):'';
		$vp_ok=strlen($vp)?true:false;
		websiteGraderAddCheck($checks,'viewport','Responsive viewport meta','SEO',$vp_ok,$vp_ok?null:array(
			'page'=>$link,'element'=>'<xmp style="margin:0px;"><meta name="viewport" content="width=device-width,initial-scale=1.0" /></xmp>','suggestion'=>'Viewport meta tag is missing (needed for mobile/responsive rendering).'
		));
		//canonical
		$canonical_ok=false;
		if(preg_match_all('/<link[^>]*>/si',$head,$lm)){
			foreach($lm[0] as $ls){
				$la=parseHtmlTagAttributes($ls);
				if(isset($la['rel']) && strtolower($la['rel'])=='canonical'){
					if(isset($la['href']) && strlen(trim($la['href']))){$canonical_ok=true;}
					break;
				}
			}
		}
		websiteGraderAddCheck($checks,'canonical','Canonical link','SEO',$canonical_ok,$canonical_ok?null:array(
			'page'=>$link,'element'=>'<xmp style="margin:0px;"><link rel="canonical" href="{page url}" /></xmp>','suggestion'=>'Canonical link is missing (helps avoid duplicate-content issues).'
		));
		//images alt + size
		$imgitems=array();
		preg_match_all('/<img[^>]*>/si',$body,$im);
		foreach($im[0] as $is){
			$ia=parseHtmlTagAttributes($is);
			$probs=array();
			if(!isset($ia['alt'])){$probs[]='missing alt';}
			if(!isset($ia['src'])){$probs[]='missing src';}
			else{
				$src=$ia['src'];
				if(stringBeginsWith($src,'//')){$src='https:'.$src;}
				elseif(stringBeginsWith($src,'/')){
					$pp=parse_url($url);
					$src="{$pp['scheme']}://{$pp['host']}".(isset($pp['port'])?":{$pp['port']}":'').$src;
				}
				//skip the size fetch for a same-host image robots.txt disallows - a real crawler
				//would never fetch it either, so flagging its size would be misleading.
				$img_disallowed=false;
				if(preg_match('#^https?://#i',$src) && strlen(trim($robots)) && strtolower((string)parse_url($src,PHP_URL_HOST))===strtolower((string)parse_url($baseurl,PHP_URL_HOST))){
					$imgpath=(string)parse_url($src,PHP_URL_PATH);
					if(!strlen($imgpath)){$imgpath='/';}
					$imgquery=(string)parse_url($src,PHP_URL_QUERY);
					if(strlen($imgquery)){$imgpath.='?'.$imgquery;}
					$img_disallowed=websiteGraderRobotsDisallowsPath($robots,$imgpath);
				}
				if(!$img_disallowed && preg_match('#^https?://#i',$src) && websiteGraderImgCheckAllowed()){
					$hinfo=websiteGraderGetURLHeader($src);
					if(isset($hinfo['download_content_length']) && $hinfo['download_content_length'] > 300000){
						$probs[]=websiteGraderFormatBytes($hinfo['download_content_length']).' &mdash; too large (keep under ~300 KB)';
					}
				}
			}
			if(count($probs)){
				$label_src=encodeHtml(websiteGraderTruncate(isset($ia['src'])?$ia['src']:'(no src)',150));
				$imgitems[]=$label_src.': '.implode(', ',$probs);
			}
		}
		$img_ok=count($imgitems)==0;
		websiteGraderAddCheck($checks,'images','Images have alt &amp; reasonable size','SEO',$img_ok,$img_ok?null:array(
			'page'=>$link,'suggestion'=>count($imgitems).' image(s) need attention (missing alt text or oversized).','items'=>$imgitems
		));

		//--- Social (Open Graph + Twitter) ---
		$ogfields=array('type','title','description','image','url','site_name');
		$ogmissing=array();
		foreach($ogfields as $f){if(!isset($meta["og:{$f}"])){$ogmissing[]="og:{$f}";}}
		$og_ok=count($ogmissing)==0;
		websiteGraderAddCheck($checks,'opengraph','Open Graph tags complete','Social',$og_ok,$og_ok?null:array(
			'page'=>$link,'element'=>'<xmp style="margin:0px;"><meta property="og:image" content="..." /></xmp>','suggestion'=>'Missing Open Graph tags: '.implode(', ',$ogmissing).'. These control how the link looks when shared to Facebook, LinkedIn, iMessage, Slack, etc.'
		));
		$twfields=array('card','title','description','image');
		$twmissing=array();
		foreach($twfields as $f){if(!isset($meta["twitter:{$f}"])){$twmissing[]="twitter:{$f}";}}
		$tw_ok=count($twmissing)==0;
		websiteGraderAddCheck($checks,'twitter','Twitter/X card tags complete','Social',$tw_ok,$tw_ok?null:array(
			'page'=>$link,'element'=>'<xmp style="margin:0px;"><meta name="twitter:card" content="summary_large_image" /></xmp>','suggestion'=>'Missing Twitter/X card tags: '.implode(', ',$twmissing).'. These control how the link looks when shared to X.'
		));

		//--- AIO ---
		$lang_ok=(bool)preg_match('/<html[^>]*\blang\s*=\s*["\'][a-z]/si',$body);
		websiteGraderAddCheck($checks,'htmllang','&lt;html lang&gt; set','AIO',$lang_ok,$lang_ok?null:array(
			'page'=>$link,'element'=>'<xmp style="margin:0px;"><html lang="en"></xmp>','suggestion'=>'The &lt;html&gt; tag has no lang attribute. AI models and screen readers use it to detect the content language.'
		));
		$jsonld_ok=(bool)preg_match('/<script[^>]*type\s*=\s*["\']application\/ld\+json["\']/si',$body);
		websiteGraderAddCheck($checks,'jsonld','JSON-LD structured data','AIO',$jsonld_ok,$jsonld_ok?null:array(
			'page'=>$link,'element'=>'<xmp style="margin:0px;"><script type="application/ld+json">{ ... }</script></xmp>','suggestion'=>'No JSON-LD structured data (Schema.org) found. AI answer engines rely on it to understand entities (Organization, Article, Product, FAQ, Breadcrumb).'
		));
		$h1cnt=preg_match_all('/<h1[\s>]/si',$body,$hm);
		$h1_ok=($h1cnt==1);
		$h1_sugg=($h1cnt==0)?'No H1 heading found. A single, clear H1 tells search and AI crawlers the main topic.':"Found {$h1cnt} H1 tags. Use exactly one H1 per page.";
		websiteGraderAddCheck($checks,'h1','Exactly one H1','AIO',$h1_ok,$h1_ok?null:array(
			'page'=>$link,'element'=>'<xmp style="margin:0px;"><h1>...</h1></xmp>','suggestion'=>$h1_sugg
		));
		$author_ok=(preg_match('/<meta[^>]*name\s*=\s*["\']author["\']/si',$body) || preg_match('/"@type"\s*:\s*"(Person|Organization)"/si',$body))?true:false;
		websiteGraderAddCheck($checks,'authorship','Authorship / E-E-A-T signal','AIO',$author_ok,$author_ok?null:array(
			'page'=>$link,'element'=>'<xmp style="margin:0px;"><meta name="author" content="..." /></xmp>','suggestion'=>'No authorship signal (meta author or Person/Organization schema). AI engines weigh authorship &amp; authority (E-E-A-T) when choosing sources to cite.'
		));
		//heading hierarchy - flag a skipped level (e.g. H1 straight to H3, or a page that starts at
		//H2+ with no H1 at all); AI engines chunk pages by heading structure when extracting
		//answers, so a broken hierarchy makes that harder.
		preg_match_all('/<h([1-6])[\s>]/si',$body,$hlm);
		$hlevels=array_map('intval',$hlm[1]);
		$hier_ok=true;$hier_sugg='';
		if(count($hlevels)){
			if($hlevels[0]!=1){
				$hier_ok=false;$hier_sugg="The first heading on the page is H{$hlevels[0]}, not H1.";
			}
			else{
				$hprev=$hlevels[0];
				foreach(array_slice($hlevels,1) as $hlvl){
					if($hlvl > $hprev+1){
						$hier_ok=false;$hier_sugg="Heading level jumps from H{$hprev} to H{$hlvl}, skipping a level in between.";break;
					}
					$hprev=$hlvl;
				}
			}
		}
		websiteGraderAddCheck($checks,'headinghierarchy','Heading hierarchy (no skipped levels)','AIO',$hier_ok,$hier_ok?null:array(
			'page'=>$link,'element'=>'<xmp style="margin:0px;"><h1>...</h1><h2>...</h2></xmp>','suggestion'=>$hier_sugg
		));
	}
	//duplicate titles/descriptions across pages - a real issue only when more than one page shares
	//the exact same title or description (each row in $titlemap/$descmap collected during the loop
	//above lists every URL that shares one normalized value).
	$dupfails=array();
	foreach($titlemap as $t=>$urls){
		if(count($urls) > 1){
			$dupfails[]='Title "'.encodeHtml(websiteGraderTruncate($t,80)).'" is used on '.count($urls).' pages: '.implode(', ',array_map('websiteGraderPageLink',$urls));
		}
	}
	foreach($descmap as $d=>$urls){
		if(count($urls) > 1){
			$dupfails[]='Description "'.encodeHtml(websiteGraderTruncate($d,80)).'" is used on '.count($urls).' pages: '.implode(', ',array_map('websiteGraderPageLink',$urls));
		}
	}
	$dupcontent_ok=count($dupfails)==0;
	websiteGraderAddCheck($checks,'dupcontent','No duplicate titles/descriptions','SEO',$dupcontent_ok,$dupcontent_ok?null:array(
		'suggestion'=>count($dupfails).' duplicate title/description issue(s) found across crawled pages. Each page should have a unique title and description.','items'=>$dupfails
	));
	return $checks;
}

/**
 * @describe map a percentage to a color used for badges/rings.
 * @param pct number
 * @return string hex color
 */
function websiteGraderGradeColor($pct){
	if($pct >= 90){return '#1f9d55';}
	if($pct >= 80){return '#3aa757';}
	if($pct >= 70){return '#c98a00';}
	if($pct >= 50){return '#d97706';}
	return '#d64545';
}

/**
 * @describe compute the overall grade (percent + description) from all checks. Graded per
 *   CHECK TYPE, not per instance: a check counts as passed only if it passed on every instance
 *   (every page, for a per-page check) - the same pass==total test each check's own row uses to
 *   show Pass/Fail (see websiteGraderRenderChecksTable). Grading by instance instead would let a
 *   check that runs once per crawled page (e.g. heading hierarchy) outweigh a check that only
 *   ever runs once site-wide (e.g. sitemap.xml present) in direct proportion to page count,
 *   which has nothing to do with how important either check is.
 * @param checks array
 * @return array [percent, pass, total, label, letter, color] - pass/total count check TYPES, e.g.
 *   "18 of 25 checks passed" (not check instances)
 */
function websiteGraderGrade($checks){
	$pass=0;$total=0;
	foreach($checks as $c){
		if($c['total']==0){continue;}
		$total++;
		if($c['pass']==$c['total'] && !count($c['fails'])){$pass++;}
	}
	$pct=$total?(int)round($pass/$total*100):0;
	if($pct >= 90){$label='Excellent &mdash; well optimized';$letter='A';}
	elseif($pct >= 80){$label='Good but could use some tweaks';$letter='B';}
	elseif($pct >= 70){$label='Fair &mdash; several improvements needed';$letter='C';}
	elseif($pct >= 50){$label='Needs work';$letter='D';}
	else{$label='Poor &mdash; significant issues';$letter='F';}
	return array('percent'=>$pct,'pass'=>$pass,'total'=>$total,'label'=>$label,'letter'=>$letter,'color'=>websiteGraderGradeColor($pct));
}

/**
 * @describe grade for a single category. Same per-check-type semantics as websiteGraderGrade().
 * @param checks array, cat string
 * @return array [percent, pass, total] - pass/total count check TYPES in this category
 */
function websiteGraderCategoryGrade($checks,$cat){
	$pass=0;$total=0;
	foreach($checks as $c){
		if($c['category']!=$cat || $c['total']==0){continue;}
		$total++;
		if($c['pass']==$c['total'] && !count($c['fails'])){$pass++;}
	}
	$pct=$total?(int)round($pass/$total*100):100;
	return array('percent'=>$pct,'pass'=>$pass,'total'=>$total);
}

//---------- technology detection ----------

/**
 * @describe signature list used to fingerprint the technology stack of a crawled site
 *   (CMS, e-commerce, JS framework, analytics, CDN, server, language, etc.) - a lightweight,
 *   built-in equivalent of what BuiltWith/Wappalyzer report, based only on the page bodies
 *   and response headers already gathered during the crawl (no external API calls).
 * @return array of [category,name,type(body|header),header,pattern]
 */
function websiteGraderTechSignatures(){
	return array(
		//--- CMS / Platform ---
		//WaSQL listed FIRST: websiteGraderDetectPrimaryPlatform() returns the first tech['CMS'] name
		//it finds notes for, so a genuine WaSQL hit always wins over any other CMS pattern that
		//happens to also match (e.g. via an outbound citation link in article body text - see
		//websiteGraderDetectTech()'s foreign-host stripping, which is the other half of this fix).
		array('category'=>'CMS','name'=>'WaSQL','type'=>'body','pattern'=>'#/w_min/minify_[0-9a-f]{8,}_[0-9a-f]{8,}\.(?:css|js)|\bwacss\.(?:nav|ajaxGet|ajaxPost|centerpopClose|toast)\s*\(#i'),
		array('category'=>'CMS','name'=>'WordPress','type'=>'body','pattern'=>'#/wp-content/|/wp-includes/|<meta[^>]+name=["\']generator["\'][^>]+content=["\']WordPress#i'),
		array('category'=>'CMS','name'=>'Drupal','type'=>'body','pattern'=>'#Drupal\.settings|/sites/default/files/|<meta[^>]+name=["\']generator["\'][^>]+content=["\']Drupal#i'),
		array('category'=>'CMS','name'=>'Joomla','type'=>'body','pattern'=>'#/media/jui/|<meta[^>]+name=["\']generator["\'][^>]+content=["\']Joomla#i'),
		array('category'=>'CMS','name'=>'Wix','type'=>'body','pattern'=>'#static\.wixstatic\.com|wix\.com/service-pages#i'),
		array('category'=>'CMS','name'=>'Squarespace','type'=>'body','pattern'=>'#squarespace\.com|static1\.squarespace\.com#i'),
		array('category'=>'CMS','name'=>'Webflow','type'=>'body','pattern'=>'#assets-global\.website-files\.com|data-wf-site=|data-wf-page=#i'),
		array('category'=>'CMS','name'=>'Ghost','type'=>'body','pattern'=>'#<meta[^>]+name=["\']generator["\'][^>]+content=["\']Ghost#i'),
		array('category'=>'CMS','name'=>'HubSpot CMS','type'=>'body','pattern'=>'#hs-scripts\.com|hs-banner\.com|hubspot\.net/cta/#i'),
		array('category'=>'CMS','name'=>'Contentful','type'=>'body','pattern'=>'#images\.ctfassets\.net#i'),
		//--- E-commerce ---
		array('category'=>'Ecommerce','name'=>'Shopify','type'=>'body','pattern'=>'#cdn\.shopify\.com|Shopify\.theme|shopify-section#i'),
		array('category'=>'Ecommerce','name'=>'WooCommerce','type'=>'body','pattern'=>'#/plugins/woocommerce/|woocommerce-#i'),
		array('category'=>'Ecommerce','name'=>'Magento','type'=>'body','pattern'=>'#Mage\.Cookies|/skin/frontend/|Magento_#i'),
		array('category'=>'Ecommerce','name'=>'BigCommerce','type'=>'body','pattern'=>'#cdn\d*\.bigcommerce\.com#i'),
		//--- JS Framework ---
		array('category'=>'JS Framework','name'=>'Next.js','type'=>'body','pattern'=>'#__NEXT_DATA__|/_next/static/#i'),
		array('category'=>'JS Framework','name'=>'Nuxt.js','type'=>'body','pattern'=>'#__NUXT__|/_nuxt/#i'),
		array('category'=>'JS Framework','name'=>'React','type'=>'body','pattern'=>'#data-reactroot|react-dom(\.production|\.min)?\.js#i'),
		array('category'=>'JS Framework','name'=>'Vue.js','type'=>'body','pattern'=>'#\bvue(\.runtime)?(\.min)?\.js\b|data-v-[0-9a-f]{6,}#i'),
		array('category'=>'JS Framework','name'=>'Angular','type'=>'body','pattern'=>'#\bng-version\s*=|\bangular(\.min)?\.js\b#i'),
		array('category'=>'JS Framework','name'=>'Svelte','type'=>'body','pattern'=>'#__svelte|svelte-[a-z0-9]{6,}#i'),
		//--- JS Library ---
		array('category'=>'JS Library','name'=>'jQuery','type'=>'body','pattern'=>'#jquery(\-[\d\.]+)?(\.min)?\.js#i'),
		array('category'=>'JS Library','name'=>'Alpine.js','type'=>'body','pattern'=>'#alpinejs|\bx-data\s*=#i'),
		array('category'=>'JS Library','name'=>'GSAP','type'=>'body','pattern'=>'#gsap(\.min)?\.js#i'),
		//--- CSS Framework ---
		array('category'=>'CSS Framework','name'=>'Bootstrap','type'=>'body','pattern'=>'#bootstrap(\.min)?\.css|bootstrap(\.bundle)?(\.min)?\.js#i'),
		array('category'=>'CSS Framework','name'=>'Tailwind CSS','type'=>'body','pattern'=>'#tailwind(\.min)?\.css|tailwindcss#i'),
		array('category'=>'CSS Framework','name'=>'Bulma','type'=>'body','pattern'=>'#bulma(\.min)?\.css#i'),
		array('category'=>'CSS Framework','name'=>'Foundation','type'=>'body','pattern'=>'#foundation(\.min)?\.css#i'),
		//--- Analytics & Tracking ---
		array('category'=>'Analytics','name'=>'Google Analytics (GA4)','type'=>'body','pattern'=>'#googletagmanager\.com/gtag/js|gtag\(\s*[\'"]config[\'"]#i'),
		array('category'=>'Analytics','name'=>'Google Universal Analytics','type'=>'body','pattern'=>'#google-analytics\.com/analytics\.js|ga\(\s*[\'"]create[\'"]#i'),
		array('category'=>'Analytics','name'=>'Meta / Facebook Pixel','type'=>'body','pattern'=>'#connect\.facebook\.net/[^"\']*/fbevents\.js#i'),
		array('category'=>'Analytics','name'=>'Hotjar','type'=>'body','pattern'=>'#static\.hotjar\.com#i'),
		array('category'=>'Analytics','name'=>'Segment','type'=>'body','pattern'=>'#cdn\.segment\.com#i'),
		array('category'=>'Analytics','name'=>'Mixpanel','type'=>'body','pattern'=>'#cdn\.mxpnl\.com#i'),
		array('category'=>'Analytics','name'=>'Microsoft Clarity','type'=>'body','pattern'=>'#clarity\.ms/tag#i'),
		//--- Tag Manager ---
		array('category'=>'Tag Manager','name'=>'Google Tag Manager','type'=>'body','pattern'=>'#googletagmanager\.com/gtm\.js#i'),
		//--- CDN ---
		array('category'=>'CDN','name'=>'Cloudflare','type'=>'header','header'=>'server','pattern'=>'#cloudflare#i'),
		array('category'=>'CDN','name'=>'Cloudflare','type'=>'header','header'=>'cf-ray','pattern'=>'#.#'),
		array('category'=>'CDN','name'=>'Fastly','type'=>'header','header'=>'x-served-by','pattern'=>'#fastly#i'),
		array('category'=>'CDN','name'=>'Amazon CloudFront','type'=>'header','header'=>'via','pattern'=>'#cloudfront#i'),
		array('category'=>'CDN','name'=>'Akamai','type'=>'header','header'=>'server','pattern'=>'#akamaighost#i'),
		//--- Hosting / PaaS ---
		array('category'=>'Hosting','name'=>'Vercel','type'=>'header','header'=>'server','pattern'=>'#vercel#i'),
		array('category'=>'Hosting','name'=>'Vercel','type'=>'header','header'=>'x-vercel-id','pattern'=>'#.#'),
		array('category'=>'Hosting','name'=>'Netlify','type'=>'header','header'=>'server','pattern'=>'#netlify#i'),
		array('category'=>'Hosting','name'=>'GitHub Pages','type'=>'header','header'=>'server','pattern'=>'#github\.com#i'),
		array('category'=>'Hosting','name'=>'WP Engine','type'=>'header','header'=>'x-powered-by','pattern'=>'#wp\s*engine#i'),
		//--- Web Server ---
		array('category'=>'Web Server','name'=>'Nginx','type'=>'header','header'=>'server','pattern'=>'#nginx#i'),
		array('category'=>'Web Server','name'=>'Apache','type'=>'header','header'=>'server','pattern'=>'#apache#i'),
		array('category'=>'Web Server','name'=>'Microsoft-IIS','type'=>'header','header'=>'server','pattern'=>'#microsoft-iis#i'),
		array('category'=>'Web Server','name'=>'LiteSpeed','type'=>'header','header'=>'server','pattern'=>'#litespeed#i'),
		//--- Programming Language ---
		array('category'=>'Language','name'=>'PHP','type'=>'header','header'=>'x-powered-by','pattern'=>'#php#i'),
		array('category'=>'Language','name'=>'PHP','type'=>'header','header'=>'set-cookie','pattern'=>'#PHPSESSID#i'),
		array('category'=>'Language','name'=>'ASP.NET','type'=>'header','header'=>'x-powered-by','pattern'=>'#asp\.net#i'),
		array('category'=>'Language','name'=>'ASP.NET','type'=>'header','header'=>'x-aspnet-version','pattern'=>'#.#'),
		array('category'=>'Language','name'=>'ASP.NET','type'=>'header','header'=>'set-cookie','pattern'=>'#ASP\.NET_SessionId#i'),
		array('category'=>'Language','name'=>'Express (Node.js)','type'=>'header','header'=>'x-powered-by','pattern'=>'#express#i'),
		//--- Fonts ---
		array('category'=>'Font','name'=>'Google Fonts','type'=>'body','pattern'=>'#fonts\.googleapis\.com|fonts\.gstatic\.com#i'),
		array('category'=>'Font','name'=>'Font Awesome','type'=>'body','pattern'=>'#font-?awesome#i'),
		array('category'=>'Font','name'=>'Adobe Fonts (Typekit)','type'=>'body','pattern'=>'#use\.typekit\.net#i'),
		//--- Chat / Support Widget ---
		array('category'=>'Widget','name'=>'Intercom','type'=>'body','pattern'=>'#widget\.intercom\.io#i'),
		array('category'=>'Widget','name'=>'Drift','type'=>'body','pattern'=>'#js\.driftt\.com#i'),
		array('category'=>'Widget','name'=>'Zendesk Chat','type'=>'body','pattern'=>'#static\.zdassets\.com#i'),
		array('category'=>'Widget','name'=>'Crisp','type'=>'body','pattern'=>'#client\.crisp\.chat#i'),
		array('category'=>'Widget','name'=>'Tawk.to','type'=>'body','pattern'=>'#embed\.tawk\.to#i'),
		//--- Payment ---
		array('category'=>'Payment','name'=>'Stripe','type'=>'body','pattern'=>'#js\.stripe\.com#i'),
		array('category'=>'Payment','name'=>'PayPal','type'=>'body','pattern'=>'#paypal\.com/sdk/js#i')
	);
}

/**
 * @describe fingerprint the technology stack of the crawled site (CMS, e-commerce, JS
 *   framework/library, CSS framework, analytics, tag manager, CDN, hosting, web server,
 *   language, fonts, chat widgets, payment) by matching page bodies + response headers
 *   against websiteGraderTechSignatures(). No external API is called.
 * @param pages array of [url,body,headers], robots string, host string - the crawled site's own
 *   hostname (from parse_url($baseurl,PHP_URL_HOST)), used to keep outbound links from false-firing
 *   a signature - see the foreign-host stripping below
 * @return array category => array of technology names (deduped, unsorted)
 */
function websiteGraderDetectTech($pages,$robots,$host=''){
	$body='';
	$headerlist=array();
	foreach($pages as $p){
		if(isset($p['body'])){$body.="\n".$p['body'];}
		if(isset($p['headers']) && is_array($p['headers'])){$headerlist[]=$p['headers'];}
	}
	$body.="\n".$robots;
	//neutralize href/src values that point at a DIFFERENT host before signature matching - a page's
	//own outbound link/citation to another site (e.g. an article citing a Drupal-powered .gov PDF)
	//must never make a body-type signature fire for THIS site. Only same-host/relative asset paths
	//(untouched here, since they don't match the http(s):// form) are real platform signal.
	if(strlen($host)){
		$body=preg_replace_callback('#(href|src)(\s*=\s*)(["\'])\s*(https?://[^"\']+)\3#i',function($m) use ($host){
			$urlhost=(string)parse_url($m[4],PHP_URL_HOST);
			return (strcasecmp($urlhost,$host)===0)?$m[0]:($m[1].$m[2].$m[3].$m[3]);
		},$body);
	}
	$found=array();
	foreach(websiteGraderTechSignatures() as $sig){
		$hit=false;
		if($sig['type']=='body'){
			if(preg_match($sig['pattern'],$body)){$hit=true;}
		}
		else{
			foreach($headerlist as $headers){
				if(!isset($headers[$sig['header']])){continue;}
				$val=$headers[$sig['header']];
				if(is_array($val)){$val=implode(' ',$val);}
				if(preg_match($sig['pattern'],$val)){$hit=true;break;}
			}
		}
		if(!$hit){continue;}
		if(!isset($found[$sig['category']])){$found[$sig['category']]=array();}
		if(!in_array($sig['name'],$found[$sig['category']])){$found[$sig['category']][]=$sig['name'];}
	}
	return $found;
}

/**
 * @describe display order + labels for technology categories.
 * @return array key => label
 */
function websiteGraderTechCategories(){
	return array(
		'CMS'=>'CMS / Platform',
		'Ecommerce'=>'E-commerce',
		'JS Framework'=>'JavaScript Framework',
		'JS Library'=>'JavaScript Library',
		'CSS Framework'=>'CSS Framework',
		'Analytics'=>'Analytics &amp; Tracking',
		'Tag Manager'=>'Tag Manager',
		'CDN'=>'CDN',
		'Hosting'=>'Hosting / PaaS',
		'Web Server'=>'Web Server',
		'Language'=>'Programming Language',
		'Font'=>'Fonts',
		'Widget'=>'Chat / Support Widget',
		'Payment'=>'Payment'
	);
}

/**
 * @describe render the detected-technology table (BuiltWith-style): one row per category
 *   with the technology name(s) found, ordered by websiteGraderTechCategories().
 * @param tech array category => array of names (from websiteGraderDetectTech)
 * @return string HTML
 */
function websiteGraderRenderTechTable($tech){
	$rtn='<div class="w_bigger w_bold w_gray w_padtop"><span class="icon-globe"></span> Technology Detected</div>'.PHP_EOL;
	$rtn.='<div class="w_small w_gray" style="margin-bottom:8px;">Based on the crawled pages\' HTML, scripts, and response headers &mdash; a lightweight, built-in equivalent of BuiltWith/Wappalyzer (may miss things only a live JS-execution scanner would catch).</div>'.PHP_EOL;
	$any=false;
	foreach(websiteGraderTechCategories() as $cat=>$label){if(isset($tech[$cat]) && count($tech[$cat])){$any=true;break;}}
	if(!$any){
		return $rtn.'<div class="w_gray" style="padding:8px 0;">No specific technology signatures were detected on this site.</div>';
	}
	$rtn.='<div class="wg_tablewrap"><table class="wacss_table is-bordered is-striped is-narrow" style="width:100%;">'.PHP_EOL;
	$rtn.='<thead><tr><th style="width:26%;">Category</th><th>Technology</th></tr></thead><tbody>'.PHP_EOL;
	foreach(websiteGraderTechCategories() as $cat=>$label){
		if(!isset($tech[$cat]) || !count($tech[$cat])){continue;}
		$names=$tech[$cat];
		sort($names);
		$rtn.='<tr><td>'.$label.'</td><td>'.encodeHtml(implode(', ',$names)).'</td></tr>'.PHP_EOL;
	}
	$rtn.='</tbody></table></div>'.PHP_EOL;
	return $rtn;
}

/**
 * @describe total count of distinct technologies detected across all categories (used for
 *   the Technology tab's pill badge).
 * @param tech array category => array of names
 * @return int
 */
function websiteGraderTechCount($tech){
	$n=0;
	if(is_array($tech)){foreach($tech as $names){$n+=count($names);}}
	return $n;
}

/**
 * @describe format a duration in seconds as a short human string ("42.3s" or "1m 12s").
 * @param seconds float
 * @return string
 */
function websiteGraderFormatSeconds($seconds){
	$seconds=(float)$seconds;
	if($seconds < 60){return round($seconds,1).'s';}
	$m=floor($seconds/60);
	$s=round($seconds-($m*60));
	return $m.'m '.$s.'s';
}

//---------- output builders ----------

/**
 * @describe FORM 1 (report card): grade hero + social preview + all checks (Pass/Fail) + technology + AI prompt panel.
 * @param grade array, checks array, social array, baseurl string, pages array, tech array, error string,
 *   excluded array of [url,reason] - pages skipped from on-page checks (robots.txt/noindex), crawlseconds float
 * @return string HTML
 */
function websiteGraderRenderResults($grade,$checks,$social,$baseurl,$pages,$tech=array(),$error='',$excluded=array(),$crawlseconds=0){
	if(strlen($error)){
		return '<div class="w_danger" style="padding:10px;"><span class="icon-warning"></span> '.encodeHtml($error).'</div>';
	}
	$cnt=count($pages);
	$rtn='';
	//scoped, responsive styles (mobile / tablet / desktop)
	$rtn.='<style>'.PHP_EOL;
	$rtn.='.wg_results td{vertical-align:top;}'.PHP_EOL;
	$rtn.='.wg_results table{max-width:100%;}'.PHP_EOL;
	$rtn.='.wg_tablewrap{overflow-x:auto;-webkit-overflow-scrolling:touch;max-width:100%;}'.PHP_EOL;
	$rtn.='.wg_results td xmp{display:block;margin:0;white-space:pre-wrap;word-break:break-word;max-width:52vw;font-size:12px;}'.PHP_EOL;
	$rtn.='.wg_cards{display:flex;gap:24px;flex-wrap:wrap;align-items:flex-start;}'.PHP_EOL;
	$rtn.='.wg_cards>div{flex:1 1 340px;min-width:0;max-width:100%;}'.PHP_EOL;
	$rtn.='.wg_hero{display:flex;align-items:center;gap:22px;flex-wrap:wrap;background:#f7f8fa;border:1px solid #e6e8eb;border-radius:12px;padding:18px 20px;margin:4px 0 6px;}'.PHP_EOL;
	$rtn.='.wg_pill{display:inline-block;color:#fff;border-radius:11px;padding:2px 10px;font-size:12px;font-weight:600;margin:2px 6px 2px 0;white-space:nowrap;}'.PHP_EOL;
	$rtn.='.wg_tabs{display:flex;flex-wrap:wrap;gap:4px;border-bottom:2px solid #e3e6ea;margin:16px 0 14px;}'.PHP_EOL;
	$rtn.='.wg_tabs a{padding:8px 14px;color:#4a5560;text-decoration:none;font-weight:600;cursor:pointer;white-space:nowrap;border-bottom:3px solid transparent;margin-bottom:-2px;}'.PHP_EOL;
	$rtn.='.wg_tabs a:hover{color:#1a5fb4;}'.PHP_EOL;
	$rtn.='.wg_tabs a.is-active,.wg_tabs a.active{color:#1a5fb4;border-bottom-color:#1a5fb4;}'.PHP_EOL;
	$rtn.='.wg_tabpill{font-size:11px;padding:1px 6px;border-radius:9px;color:#fff;}'.PHP_EOL;
	$rtn.='#grader_ai_prompt{max-width:100%;max-height:420px;box-sizing:border-box;overflow-y:auto;white-space:pre-wrap;word-break:break-word;font-family:monospace;font-size:12px;line-height:1.5;border:1px solid #dbdbdb;border-radius:4px;padding:12px;background:#fff;color:#363636;}'.PHP_EOL;
	$rtn.='@media (max-width:820px){.wg_results td xmp{max-width:56vw;font-size:11px;}}'.PHP_EOL;
	$rtn.='@media (max-width:560px){.wg_tablewrap table{min-width:600px;}.wg_results td xmp{max-width:320px;font-size:11px;}.wg_cards{gap:16px;}.wg_hero{padding:14px;}.wg_tabs{flex-wrap:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch;}}'.PHP_EOL;
	$rtn.='</style>'.PHP_EOL;
	$rtn.='<div class="wg_results">'.PHP_EOL;
	//scan summary + share/email action (always visible)
	$rtn.='<div style="display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap;">'.PHP_EOL;
	$rtn.='<div style="flex:1 1 auto;min-width:0;">'.PHP_EOL;
	$rtn.='<div class="w_bigger w_bold w_gray">Scanned <span class="w_dblue">'.htmlspecialchars($baseurl).'</span></div>'.PHP_EOL;
	$rtn.='<div class="w_gray" style="margin-bottom:8px;">Crawled '.$cnt.' page'.($cnt==1?'':'s').' from the live site'.($crawlseconds>0?(' in '.websiteGraderFormatSeconds($crawlseconds)):'').'.</div>'.PHP_EOL;
	$rtn.='</div>'.PHP_EOL;
	$rtn.='<div style="flex:0 0 auto;display:flex;gap:6px;flex-wrap:wrap;">'.websiteGraderEmailButton().websiteGraderDownloadButton().'</div>'.PHP_EOL;
	$rtn.='</div>'.PHP_EOL;
	$rtn.='<details style="margin-bottom:10px;"><summary class="w_link w_pointer">Pages crawled ('.$cnt.')</summary><div class="w_small" style="padding:6px 0 0 12px;">';
	foreach($pages as $p){$rtn.=websiteGraderPageLink($p['url']).'<br />'.PHP_EOL;}
	$rtn.='</div></details>'.PHP_EOL;
	if(is_array($excluded) && count($excluded)){
		$rtn.='<details style="margin-bottom:10px;"><summary class="w_link w_pointer">Excluded from checks &mdash; robots.txt / noindex ('.count($excluded).')</summary><div class="w_small" style="padding:6px 0 0 12px;">';
		$rtn.='<div class="w_gray" style="margin-bottom:4px;">These pages are already excluded from indexing by the site itself, so on-page checks (title, H1, canonical, etc.) were skipped for them &mdash; a real search/AI crawler would skip them too.</div>'.PHP_EOL;
		foreach($excluded as $ex){$rtn.=websiteGraderPageLink($ex['url']).' <span class="w_gray">&mdash; '.encodeHtml($ex['reason']).'</span><br />'.PHP_EOL;}
		$rtn.='</div></details>'.PHP_EOL;
	}
	//grade hero (always visible)
	$rtn.=websiteGraderRenderGradeHero($grade,$checks);
	//tabbed sections
	$rtn.='<div class="wg_tabwrapper">'.PHP_EOL;
	$rtn.=websiteGraderRenderTabNav($checks,$tech);
	$rtn.='<div class="wg_panel" id="wg_seo">'.websiteGraderRenderChecksTable($checks,'SEO','SEO &mdash; Page Checks').'</div>'.PHP_EOL;
	$rtn.='<div class="wg_panel" id="wg_social" style="display:none;">'.websiteGraderRenderChecksTable($checks,'Social','Social / Open Graph').'</div>'.PHP_EOL;
	$rtn.='<div class="wg_panel" id="wg_aio" style="display:none;">'.websiteGraderRenderChecksTable($checks,'AIO','AI Optimization (AIO)').'</div>'.PHP_EOL;
	$rtn.='<div class="wg_panel" id="wg_misc" style="display:none;">'.websiteGraderRenderChecksTable($checks,'Misc','Misc / Technical').'</div>'.PHP_EOL;
	$rtn.='<div class="wg_panel" id="wg_tech" style="display:none;">'.websiteGraderRenderTechTable($tech).'</div>'.PHP_EOL;
	$rtn.='<div class="wg_panel" id="wg_preview" style="display:none;">'.websiteGraderRenderSocialPreview($social).'</div>'.PHP_EOL;
	$rtn.='<div class="wg_panel" id="wg_ai" style="display:none;">'.websiteGraderRenderAIPanel($baseurl,$pages,$checks,$grade,$social,$tech).'</div>'.PHP_EOL;
	$rtn.='</div>'.PHP_EOL;//.wg_tabwrapper
	$rtn.='</div>'.PHP_EOL;//.wg_results
	return $rtn;
}

/**
 * @describe render the tab bar. Each tab shows/hides its panel client-side (no re-crawl) and
 *   uses wacss.setActiveTab for the active-class handling.
 * @param checks array, tech array
 * @return string HTML
 */
function websiteGraderRenderTabNav($checks,$tech=array()){
	$tabs=array(
		array('id'=>'wg_seo','label'=>'SEO','cat'=>'SEO'),
		array('id'=>'wg_social','label'=>'Open Graph','cat'=>'Social'),
		array('id'=>'wg_aio','label'=>'AIO','cat'=>'AIO'),
		array('id'=>'wg_misc','label'=>'Technical','cat'=>'Misc'),
		array('id'=>'wg_tech','label'=>'Technology','cat'=>''),
		array('id'=>'wg_preview','label'=>'Preview','cat'=>''),
		array('id'=>'wg_ai','label'=>'Fix with AI','cat'=>'')
	);
	$rtn='<nav class="wg_tabs">'.PHP_EOL;
	$first=true;
	foreach($tabs as $t){
		$cls=$first?' is-active':'';
		$pill='';
		if(strlen($t['cat'])){
			$cg=websiteGraderCategoryGrade($checks,$t['cat']);
			if($cg['total'] > 0){
				$pill=' <span class="wg_tabpill" style="background:'.websiteGraderGradeColor($cg['percent']).';">'.$cg['percent'].'%</span>';
			}
		}
		elseif($t['id']=='wg_tech'){
			$tcount=websiteGraderTechCount($tech);
			if($tcount > 0){
				$pill=' <span class="wg_tabpill" style="background:#5a6472;">'.$tcount.'</span>';
			}
		}
		$onclick="var r=this.closest('.wg_tabwrapper');r.querySelectorAll('.wg_panel').forEach(function(p){p.style.display='none';});var t=r.querySelector('#".$t['id']."');if(t){t.style.display='';}return wacss.setActiveTab(this);";
		$rtn.='<a href="#" class="wg_tab'.$cls.'" onclick="'.$onclick.'">'.$t['label'].$pill.'</a>'.PHP_EOL;
		$first=false;
	}
	$rtn.='</nav>'.PHP_EOL;
	return $rtn;
}

/**
 * @describe render the overall-grade hero (percent ring + description + per-category pills).
 * @param grade array, checks array
 * @return string HTML
 */
function websiteGraderRenderGradeHero($grade,$checks){
	$pct=$grade['percent'];
	$color=$grade['color'];
	$ring='background:conic-gradient('.$color.' '.$pct.'%, #e3e6ea 0);';
	$rtn='<div class="wg_hero">'.PHP_EOL;
	$rtn.='<div style="width:118px;height:118px;border-radius:50%;'.$ring.'display:flex;align-items:center;justify-content:center;flex:0 0 auto;">'.PHP_EOL;
	$rtn.='	<div style="width:90px;height:90px;border-radius:50%;background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;">'.PHP_EOL;
	$rtn.='		<div style="font-size:30px;font-weight:700;line-height:1;color:'.$color.';">'.$pct.'%</div>'.PHP_EOL;
	$rtn.='		<div style="font-size:11px;color:#8a9099;letter-spacing:.5px;">GRADE '.$grade['letter'].'</div>'.PHP_EOL;
	$rtn.='	</div>'.PHP_EOL;
	$rtn.='</div>'.PHP_EOL;
	$rtn.='<div style="flex:1 1 240px;min-width:0;">'.PHP_EOL;
	$rtn.='	<div style="font-size:20px;font-weight:700;color:'.$color.';line-height:1.2;">'.$grade['label'].'</div>'.PHP_EOL;
	$rtn.='	<div class="w_gray" style="margin:3px 0 8px;">'.$grade['pass'].' of '.$grade['total'].' checks passed</div>'.PHP_EOL;
	$rtn.='	<div>'.PHP_EOL;
	foreach(websiteGraderCategories() as $cat=>$label){
		$cg=websiteGraderCategoryGrade($checks,$cat);
		if($cg['total']==0){continue;}
		$plain=trim(preg_replace('/&mdash;.*$/','',str_replace('&amp;','&',$label)));
		$rtn.='<span class="wg_pill" style="background:'.websiteGraderGradeColor($cg['percent']).';">'.encodeHtml($plain).' '.$cg['percent'].'%</span>'.PHP_EOL;
	}
	$rtn.='	</div>'.PHP_EOL;
	$rtn.='</div>'.PHP_EOL;
	$rtn.='</div>'.PHP_EOL;
	return $rtn;
}

/**
 * @describe render all checks in a category as a table with a Pass/Fail Status column.
 * @param checks array, cat string, label string
 * @return string HTML
 */
function websiteGraderRenderChecksTable($checks,$cat,$label){
	$rows=array();
	foreach($checks as $c){if($c['category']==$cat){$rows[]=$c;}}
	if(!count($rows)){return '';}
	$cg=websiteGraderCategoryGrade($checks,$cat);
	$pill='<span class="wg_pill" style="background:'.websiteGraderGradeColor($cg['percent']).';">'.$cg['percent'].'%</span>';
	$rtn='<div class="w_bigger w_bold w_gray w_padtop">'.$label.' '.$pill.'</div>'.PHP_EOL;
	$rtn.='<div class="wg_tablewrap"><table class="wacss_table is-bordered is-striped is-narrow" style="width:100%;">'.PHP_EOL;
	$rtn.='<thead><tr><th style="width:86px;">Status</th><th style="width:22%;">Check</th><th>Details</th></tr></thead><tbody>'.PHP_EOL;
	foreach($rows as $c){
		$ok=($c['pass']==$c['total'] && count($c['fails'])==0);
		if($ok){$status='<span class="w_success w_bold w_nowrap"><span class="icon-mark"></span> Pass</span>';}
		else{$status='<span class="w_danger w_bold w_nowrap"><span class="icon-warning"></span> Fail</span>';}
		$rtn.='<tr><td>'.$status.'</td><td>'.$c['label'].'</td><td>'.websiteGraderCheckDetails($c,$ok).'</td></tr>'.PHP_EOL;
	}
	$rtn.='</tbody></table></div>'.PHP_EOL;
	return $rtn;
}

/**
 * @describe render the Details cell for a check (pass summary, or the failing pages + fixes).
 * @param c array (a single check), ok bool
 * @return string HTML
 */
function websiteGraderCheckDetails($c,$ok){
	if($ok){
		if($c['total'] > 1){return '<span class="w_gray">All '.$c['total'].' pages OK</span>';}
		return '<span class="w_gray">OK</span>';
	}
	$fails=$c['fails'];
	$failcount=count($fails);
	$out='<div class="w_bold w_danger" style="margin-bottom:4px;">'.$failcount.' of '.$c['total'].' failed</div>'.PHP_EOL;
	$cap=6;$more=0;
	if($failcount > $cap){$more=$failcount-$cap;$fails=array_slice($fails,0,$cap);}
	$out.='<div class="w_small">';
	foreach($fails as $f){
		$out.='<div style="margin-bottom:6px;">';
		if(isset($f['page']) && strlen($f['page'])){$out.=$f['page'].'<br />';}
		if(isset($f['suggestion']) && strlen($f['suggestion'])){$out.='<span class="w_gray">'.$f['suggestion'].'</span>';}
		if(isset($f['element']) && strlen($f['element'])){$out.=$f['element'];}
		if(isset($f['items']) && is_array($f['items']) && count($f['items'])){
			$out.='<ul style="margin:3px 0 0 16px;padding:0;">';
			foreach($f['items'] as $it){$out.='<li>'.$it.'</li>';}
			$out.='</ul>';
		}
		$out.='</div>';
	}
	if($more > 0){$out.='<div class="w_gray">&hellip;and '.$more.' more page'.($more==1?'':'s').'</div>';}
	$out.='</div>';
	return $out;
}

/**
 * @describe render a set of issue recs as an HTML grid (legacy helper; kept for reuse).
 * @param recs array, listopts array
 * @return string
 */
function websiteGraderList($recs,$listopts=array()){
	if(!is_array($recs) || !count($recs)){
		return '<div class="w_success" style="padding:8px 2px 4px;"><span class="icon-mark"></span> All checks passed &mdash; no issues found.</div>';
	}
	$opts=array(
		'-list'=>$recs,
		'-tableclass'=>'wacss_table is-bordered is-striped is-narrow',
		'-hidesearch'=>1
	);
	foreach($listopts as $k=>$v){
		if(!strlen($v)){unset($opts[$k]);}
		else{$opts[$k]=$v;}
	}
	return '<div class="wg_tablewrap">'.databaseListRecords($opts).'</div>';
}

//---------- Fix with AI (FORM 2) ----------

/**
 * @describe per-platform notes for the "Fix with AI" prompt: how a site owner on that platform
 *   actually makes changes (theme editor vs. code injection vs. no template access, etc.), so the
 *   AI assistant's fixes are things the reader can actually apply rather than generic raw-HTML edits.
 *   Keyed by the tech-signature 'name' from websiteGraderTechSignatures() (CMS + Ecommerce categories).
 * @return array name => note string
 */
function websiteGraderPlatformNotes(){
	return array(
		'WaSQL'=>"This site runs on WaSQL, a database-driven PHP framework - page logic (body/controller/functions/css/js) lives in _pages table records, not template files, and site-wide chrome/meta lives in the active _templates record's own functions field. Give fixes as: the exact PHP/HTML to add to the relevant page's controller or functions field (e.g. a title/meta-description helper), or - for something that should apply site-wide (Open Graph tags, JSON-LD, robots meta) - the template's functions field (the function that builds <head>, commonly named like templateMeta*/templateJsonLd). Also mention robots.txt/sitemap.xml if relevant, since on WaSQL those are typically their own _pages records rather than framework-generated files. Avoid assuming static template files on disk (.twig/.liquid/etc.) - WaSQL sites are developer-maintained through the database.",
		'WordPress'=>"This site runs on WordPress. Give fixes as: (a) exact field values to enter into the active SEO plugin if one is detected among the technologies (Yoast SEO, Rank Math, All in One SEO) — title/meta description/OG fields, or (b) if no SEO plugin is evident, the exact PHP/HTML to add to the active theme's header.php or functions.php (wp_head hook), or (c) plain text/HTML for anything edited directly in the block editor. Avoid assuming shell/FTP access to core files unless the fix requires editing the active theme.",
		'Squarespace'=>"This site runs on Squarespace, a hosted builder with NO access to server files or arbitrary templates. Give fixes only as things doable inside the Squarespace editor: per-page SEO fields (Page Settings > SEO tab: title, description, social image), site-wide settings (Settings > Marketing > SEO), and Settings > Advanced > Code Injection (Header/Footer) for meta tags, JSON-LD structured data, or analytics/verification scripts. Do not suggest editing template files, .htaccess, robots.txt directly, or server config — Squarespace manages those (robots.txt/sitemap.xml are auto-generated; note if a fix isn't achievable on Squarespace at all).",
		'Wix'=>"This site runs on Wix, a hosted builder with NO access to server files or template source. Give fixes as: per-page SEO panel entries (Wix SEO settings: title tag, meta description, URL slug, social share image), the site-wide SEO Settings, and Wix's Custom Code feature (Settings > Custom Code, or Velo dev mode) for injecting meta tags/JSON-LD into <head> or <body>. Do not suggest editing server files, robots.txt, or .htaccess directly — Wix manages robots.txt/sitemap.xml itself (note if a fix isn't achievable on Wix at all).",
		'Webflow'=>"This site runs on Webflow. Give fixes as: per-page Settings > SEO panel fields (title, description, OG image), the Custom Code embed (Page Settings > Custom Code, or Project Settings > Custom Code for site-wide head/body code) for meta tags and JSON-LD, and note that Webflow auto-generates robots.txt/sitemap.xml under Project Settings > SEO unless a custom robots.txt is set there.",
		'Shopify'=>"This site runs on Shopify. Give fixes as: per-product/page/collection SEO fields in the Shopify admin (Search engine listing preview: title/description), theme.liquid or the relevant section/snippet's Liquid code (Online Store > Themes > Edit code) for meta tags/JSON-LD not covered by admin fields, and note that robots.txt is editable via a robots.txt.liquid template in newer Shopify themes.",
		'WooCommerce'=>"This site runs on WordPress + WooCommerce. Give fixes as SEO-plugin field values (Yoast/Rank Math product SEO tab) where possible, otherwise WooCommerce template overrides or functions.php hooks (woocommerce_ hooks) — avoid suggesting core WooCommerce file edits.",
		'Ghost'=>"This site runs on Ghost. Give fixes as: per-post/page Settings > Meta Data fields (title/description/OG/Twitter card), the site-wide Settings > Code injection (Header/Footer) for meta tags and JSON-LD, and note Ghost auto-generates robots.txt/sitemap.xml.",
		'HubSpot CMS'=>"This site runs on HubSpot CMS. Give fixes as: the page's Settings > Advanced Options SEO fields (meta description, page title), and the page's Head HTML field for custom meta tags/JSON-LD. Avoid suggesting raw server file edits.",
		'Drupal'=>"This site runs on Drupal. Give fixes as: field values for the Metatag/Yoast SEO for Drupal module if evident, or the specific .html.twig template / hook_preprocess to edit, since Drupal sites are typically developer-maintained.",
		'Joomla'=>"This site runs on Joomla. Give fixes as: the Global Configuration / article Metadata tab fields where possible, or the specific template override (templates/<template>/...) to edit for anything not covered by admin fields.",
		'BigCommerce'=>"This site runs on BigCommerce. Give fixes as: per-product/page Search Engine Optimization fields in the BigCommerce admin, and Stencil theme template edits (Storefront > My Themes > Advanced > Edit Theme Files) for anything not covered by admin fields."
	);
}

/**
 * @describe pick the single most relevant detected platform for AI-prompt guidance: prefer a CMS
 *   hit (WordPress, Wix, Squarespace, ...), falling back to an Ecommerce platform (Shopify, ...)
 *   when no CMS was detected (e.g. a Shopify store with no separate CMS signature).
 * @param tech array category => array of names (from websiteGraderDetectTech)
 * @return string platform name, or '' if none of the known platforms were detected
 */
function websiteGraderDetectPrimaryPlatform($tech){
	$notes=websiteGraderPlatformNotes();
	foreach(array('CMS','Ecommerce') as $cat){
		if(!isset($tech[$cat])){continue;}
		foreach($tech[$cat] as $name){
			if(isset($notes[$name])){return $name;}
		}
	}
	return '';
}

/**
 * @describe render the copy/paste AI prompt inside a read-only textarea with a copy button.
 * @param baseurl string, pages array, checks array, grade array, social array, tech array
 * @return string HTML
 */
function websiteGraderRenderAIPanel($baseurl,$pages,$checks,$grade,$social=array(),$tech=array()){
	$prompt=websiteGraderAIPrompt($baseurl,$pages,$checks,$grade,$social,$tech);
	$platform=websiteGraderDetectPrimaryPlatform($tech);
	$rtn='';
	$rtn.='<div class="w_bigger w_bold w_gray w_padtop"><span class="icon-copy"></span> Fix with AI</div>'.PHP_EOL;
	$rtn.='<div class="w_small w_gray" style="margin-bottom:6px;">Copy this summary and paste it into Claude, ChatGPT, or any AI assistant to get the exact fixes for every failed check above'.(strlen($platform)?' &mdash; tailored to <b>'.encodeHtml($platform).'</b>, the platform detected for this site':'').'.</div>'.PHP_EOL;
	$rtn.='<div style="position:relative;">'.PHP_EOL;
	$rtn.='	<button type="button" class="wacss_button is-small" style="position:absolute;top:6px;right:6px;z-index:2;" onclick="wacss.copy2Clipboard(document.getElementById(\'grader_ai_prompt\').textContent,\'Copied &mdash; paste into your AI assistant\');return false;"><span class="icon-copy"></span> Copy</button>'.PHP_EOL;
	$rtn.='	<div id="grader_ai_prompt">'.encodeHtml($prompt).'</div>'.PHP_EOL;
	$rtn.='</div>'.PHP_EOL;
	return $rtn;
}

/**
 * @describe build the plain-text AI prompt: overall grade + social summary + every FAILED check.
 * @param baseurl string, pages array, checks array, grade array, social array, tech array
 * @return string plain text (markdown)
 */
function websiteGraderAIPrompt($baseurl,$pages,$checks,$grade,$social=array(),$tech=array()){
	$lines=array();
	$lines[]="# SEO & AI Optimization (AIO) audit for {$baseurl}";
	$lines[]="";
	$gradeplain=trim(html_entity_decode(preg_replace('/&mdash;/','-',$grade['label']),ENT_QUOTES));
	$lines[]="Overall grade: {$grade['percent']}% (".$grade['letter'].") - {$gradeplain}. {$grade['pass']} of {$grade['total']} checks passed across ".count($pages)." crawled page(s).";
	$lines[]="";
	$lines[]="Please fix the FAILED checks listed below. For each, I give the check name, the affected page(s), an example element, and the problem. Provide the exact HTML, meta tags, JSON-LD structured data, robots.txt rules, or file contents to add or change. Where an issue repeats across pages, give one reusable solution.";
	$platform=websiteGraderDetectPrimaryPlatform($tech);
	if(strlen($platform)){
		$notes=websiteGraderPlatformNotes();
		$lines[]="";
		$lines[]="## Platform: {$platform}";
		$lines[]=$notes[$platform];
	}
	//social summary
	$soclines=websiteGraderSocialPromptLines($social);
	if(count($soclines)){
		$lines[]="";
		$lines[]="## Social / link-share preview (how the home page looks when pasted into Facebook, X/Twitter, iMessage, Slack, etc.)";
		foreach($soclines as $sl){$lines[]=$sl;}
	}
	//failed checks by category
	$anyfail=false;
	foreach(websiteGraderCategories() as $cat=>$label){
		$catplain=trim(html_entity_decode(preg_replace('/&mdash;.*$/','',$label),ENT_QUOTES));
		foreach($checks as $c){
			if($c['category']!=$cat){continue;}
			if($c['pass']==$c['total'] && !count($c['fails'])){continue;}
			$anyfail=true;
			$clabel=trim(html_entity_decode($c['label'],ENT_QUOTES));
			$lines[]="";
			$lines[]="## [{$catplain}] {$clabel} — ".count($c['fails'])." of {$c['total']} failing";
			$fails=$c['fails'];
			if(count($fails) > 25){$fails=array_slice($fails,0,25);}
			foreach($fails as $f){
				$loc='';
				if(isset($f['page']) && preg_match('/href="([^"]+)"/i',$f['page'],$mm)){$loc=html_entity_decode($mm[1],ENT_QUOTES);}
				$sugg=isset($f['suggestion'])?websiteGraderPlainText($f['suggestion']):'';
				$line='- ';
				if(strlen($loc)){$line.="[{$loc}] ";}
				$line.=$sugg;
				$lines[]=$line;
				if(isset($f['element']) && strlen($f['element'])){
					$lines[]="    element: ".websiteGraderPlainElement($f['element']);
				}
				if(isset($f['items']) && is_array($f['items'])){
					foreach($f['items'] as $it){$lines[]="    - ".websiteGraderPlainText($it);}
				}
			}
		}
	}
	if(!$anyfail){
		$lines[]="";
		$lines[]="No failed checks — the site passed every SEO/AIO/Social check. Suggest any advanced, optional improvements (including social share-card polish) you would still recommend.";
	}
	return implode("\n",$lines);
}

/**
 * @describe strip HTML from a suggestion string for the plain-text AI prompt.
 * @param html string
 * @return string
 */
function websiteGraderPlainText($html){
	$s=preg_replace('#<xmp[^>]*>(.*?)</xmp>#is','$1',$html);
	$s=preg_replace('#<br\s*/?>#i',' ',$s);
	$s=strip_tags($s);
	$s=html_entity_decode($s,ENT_QUOTES);
	$s=preg_replace('/\s+/',' ',$s);
	return trim($s);
}

/**
 * @describe unwrap an <xmp> example element to its literal text (keeps the example tag intact) for the AI prompt.
 * @param html string
 * @return string
 */
function websiteGraderPlainElement($html){
	$s=preg_replace('#<xmp[^>]*>(.*?)</xmp>#is','$1',$html);
	$s=preg_replace('#<br\s*/?>#i',' ',$s);
	$s=html_entity_decode($s,ENT_QUOTES);
	$s=preg_replace('/[\r\n]+/',' ',$s);
	$s=preg_replace('/\s+/',' ',$s);
	return trim($s);
}

//---------- social / link-share preview ----------

/**
 * @describe parse a page's <title> and meta tags (name/property => content) for social/link previews.
 * @param body string
 * @return array [title, meta(assoc)]
 */
function websiteGraderParseMeta($body){
	$title='';
	if(preg_match('/<title>(.+?)<\/title>/si',$body,$m)){
		$title=trim(html_entity_decode($m[1],ENT_QUOTES));
	}
	$head=$body;
	if(preg_match('/<head[^>]*>(.*)<\/head>/si',$body,$m)){$head=$m[1];}
	$meta=array();
	preg_match_all('/<meta[^>]*>/si',$head,$matches);
	foreach($matches[0] as $str){
		$atts=parseHtmlTagAttributes($str);
		$key='';
		if(isset($atts['property'])){$key=strtolower($atts['property']);}
		elseif(isset($atts['name'])){$key=strtolower($atts['name']);}
		if(!strlen($key) || !isset($atts['content'])){continue;}
		$meta[$key]=html_entity_decode($atts['content'],ENT_QUOTES);
	}
	return array('title'=>$title,'meta'=>$meta);
}

/**
 * @describe resolve the Open Graph / Twitter values a share card would use for a page (with fallbacks).
 * @param page array [url,body], baseurl string
 * @return array of resolved fields + image status
 */
function websiteGraderSocialData($page,$baseurl){
	$url=$page['url'];
	$parsed=websiteGraderParseMeta($page['body']);
	$title=$parsed['title'];
	$meta=$parsed['meta'];
	$get=function($k) use ($meta){return isset($meta[$k])?trim($meta[$k]):'';};
	$og_title=strlen($get('og:title'))?$get('og:title'):$title;
	$og_desc=strlen($get('og:description'))?$get('og:description'):$get('description');
	$og_image=$get('og:image');
	$og_site=strlen($get('og:site_name'))?$get('og:site_name'):parse_url($baseurl,PHP_URL_HOST);
	$og_url=strlen($get('og:url'))?$get('og:url'):$url;
	$tw_card=strtolower($get('twitter:card'));
	$tw_title=strlen($get('twitter:title'))?$get('twitter:title'):$og_title;
	$tw_desc=strlen($get('twitter:description'))?$get('twitter:description'):$og_desc;
	$tw_image=strlen($get('twitter:image'))?$get('twitter:image'):$og_image;
	if(strlen($og_image)){$og_image=websiteGraderAbsoluteURL($og_image,$url,$baseurl);}
	if(strlen($tw_image)){$tw_image=websiteGraderAbsoluteURL($tw_image,$url,$baseurl);}
	$img_ok=false;$img_note='';
	if(strlen($og_image)){
		$info=websiteGraderGetURLHeader($og_image);
		$code=isset($info['http_code'])?$info['http_code']:0;
		if($code>=200 && $code<400){$img_ok=true;}
		else{$img_note="og:image URL does not load (HTTP {$code}).";}
	}
	return array(
		'url'=>$url,
		'host'=>parse_url($url,PHP_URL_HOST),
		'title'=>$title,
		'og_title'=>$og_title,'og_desc'=>$og_desc,'og_image'=>$og_image,'og_site'=>$og_site,'og_url'=>$og_url,
		'tw_card'=>$tw_card,'tw_title'=>$tw_title,'tw_desc'=>$tw_desc,'tw_image'=>$tw_image,
		'img_ok'=>$img_ok,'img_note'=>$img_note
	);
}

/**
 * @describe clip a string to a representative preview length (multibyte-aware), with a fallback when empty.
 * @param str string, max int, fallback string
 * @return string
 */
function websiteGraderClip($str,$max,$fallback=''){
	$str=trim($str);
	if(!strlen($str)){return $fallback;}
	if(commonStrlen($str)<=$max){return $str;}
	return rtrim(mb_substr($str,0,$max)).'…';
}

/**
 * @describe append a cache-busting query param to a preview image URL so the browser re-fetches it on every grade run instead of serving a stale cached copy of an og:image the site owner just replaced at the same filename.
 * @param url string
 * @return string
 */
function websiteGraderCacheBustURL($url){
	if(!strlen($url)){return $url;}
	$sep=(strpos($url,'?')===false)?'?':'&';
	return $url.$sep.'_wgcb='.time();
}

/**
 * @describe placeholder box shown in a card when there is no share image.
 * @return string HTML
 */
function websiteGraderNoImageBox(){
	return '<div style="height:200px;background:#e9ebee;display:flex;align-items:center;justify-content:center;color:#90949c;font-size:13px;">No share image (og:image)</div>';
}

/**
 * @describe render the visual link-share preview cards (Open Graph + X/Twitter) plus preview-specific warnings.
 * @param social array from websiteGraderSocialData
 * @return string HTML
 */
function websiteGraderRenderSocialPreview($social){
	if(!is_array($social) || !isset($social['url'])){return '';}
	$hostlc=encodeHtml($social['host']);
	//Open Graph card (Facebook, LinkedIn, iMessage, WhatsApp, Slack, Discord)
	$og_title=encodeHtml(websiteGraderClip($social['og_title'],88,'(no title)'));
	$og_desc=encodeHtml(websiteGraderClip($social['og_desc'],200,'(no description)'));
	if($social['img_ok']){
		$ogimg='<div style="height:260px;overflow:hidden;background:#e9ebee;"><img src="'.htmlspecialchars(websiteGraderCacheBustURL($social['og_image']),ENT_QUOTES).'" style="width:100%;height:100%;object-fit:cover;display:block;" alt="og image" /></div>';
	}
	else{$ogimg=websiteGraderNoImageBox();}
	$ogcard='<div style="width:500px;max-width:100%;border:1px solid #dadde1;border-radius:8px;overflow:hidden;background:#fff;font-family:Helvetica,Arial,sans-serif;box-shadow:0 1px 2px rgba(0,0,0,.1);">';
	$ogcard.=$ogimg;
	$ogcard.='<div style="padding:10px 12px;background:#f2f3f5;">';
	$ogcard.='<div style="text-transform:uppercase;color:#606770;font-size:12px;letter-spacing:.3px;">'.$hostlc.'</div>';
	$ogcard.='<div style="font-weight:600;font-size:16px;color:#1d2129;line-height:1.3;margin:2px 0;">'.$og_title.'</div>';
	$ogcard.='<div style="color:#606770;font-size:14px;line-height:1.3;">'.$og_desc.'</div>';
	$ogcard.='</div></div>';
	//X / Twitter card
	$tw_title=encodeHtml(websiteGraderClip($social['tw_title'],70,'(no title)'));
	$tw_desc=encodeHtml(websiteGraderClip($social['tw_desc'],140,'(no description)'));
	$is_large=($social['tw_card']=='summary_large_image' || $social['tw_card']=='');
	$twimg_url=strlen($social['tw_image'])?$social['tw_image']:$social['og_image'];
	$tw_has_img=strlen($twimg_url) && $social['img_ok'];
	$twcard='<div style="width:500px;max-width:100%;border:1px solid #cfd9de;border-radius:16px;overflow:hidden;background:#fff;font-family:Helvetica,Arial,sans-serif;">';
	if($is_large){
		if($tw_has_img){
			$twcard.='<div style="height:250px;overflow:hidden;background:#e9ebee;position:relative;"><img src="'.htmlspecialchars(websiteGraderCacheBustURL($twimg_url),ENT_QUOTES).'" style="width:100%;height:100%;object-fit:cover;display:block;" alt="twitter image" /><span style="position:absolute;bottom:10px;left:10px;background:rgba(0,0,0,.75);color:#fff;font-size:13px;padding:2px 6px;border-radius:4px;">'.$tw_title.'</span></div>';
		}
		else{$twcard.=websiteGraderNoImageBox();}
		$twcard.='<div style="padding:8px 12px;color:#536471;font-size:13px;">'.$hostlc.'</div>';
	}
	else{
		$twcard.='<div style="display:flex;align-items:stretch;">';
		if($tw_has_img){
			$twcard.='<div style="width:130px;flex:0 0 130px;overflow:hidden;background:#e9ebee;"><img src="'.htmlspecialchars(websiteGraderCacheBustURL($twimg_url),ENT_QUOTES).'" style="width:100%;height:100%;object-fit:cover;display:block;" alt="twitter image" /></div>';
		}
		$twcard.='<div style="padding:10px 12px;">';
		$twcard.='<div style="color:#536471;font-size:13px;">'.$hostlc.'</div>';
		$twcard.='<div style="font-size:15px;color:#0f1419;font-weight:600;line-height:1.3;">'.$tw_title.'</div>';
		$twcard.='<div style="color:#536471;font-size:14px;line-height:1.3;">'.$tw_desc.'</div>';
		$twcard.='</div></div>';
	}
	$twcard.='</div>';
	//preview-specific warnings
	$warns=array();
	if(!strlen(trim($social['og_image']))){
		$warns[]='<b>No og:image</b> &mdash; links shared to Facebook, LinkedIn, iMessage, Slack, etc. will show <b>no thumbnail</b>. Add <code>&lt;meta property="og:image" content="..." /&gt;</code> (recommended 1200&times;630).';
	}
	elseif(!$social['img_ok']){
		$warns[]='<b>og:image problem</b> &mdash; '.encodeHtml($social['img_note']).' Fix the URL so the share thumbnail loads.';
	}
	if(!strlen(trim($social['og_title']))){$warns[]='<b>No title</b> for the share card (missing both &lt;title&gt; and og:title).';}
	if(!strlen(trim($social['og_desc']))){$warns[]='<b>No description</b> &mdash; the share card will have little/no descriptive text.';}
	if(!strlen(trim($social['tw_card']))){$warns[]='<b>twitter:card not set</b> &mdash; X may render only a plain link. Add <code>&lt;meta name="twitter:card" content="summary_large_image" /&gt;</code>.';}
	$warnhtml='';
	if(count($warns)){
		$warnhtml.='<ul style="margin:8px 0 0 0;padding-left:18px;color:#a15c00;">';
		foreach($warns as $w){$warnhtml.='<li style="margin-bottom:4px;">'.$w.'</li>';}
		$warnhtml.='</ul>';
	}
	else{$warnhtml='<div class="w_success" style="padding:6px 0;"><span class="icon-mark"></span> Share card looks complete (title, description, and image all present).</div>';}
	//assemble
	$rtn='<div class="w_bigger w_bold w_gray w_padtop"><span class="icon-share"></span> Social / Link Preview</div>'.PHP_EOL;
	$rtn.='<div class="w_small w_gray" style="margin-bottom:10px;">How <span class="w_dblue">'.encodeHtml($social['url']).'</span> looks when pasted into Facebook, LinkedIn, iMessage, WhatsApp, Slack, Discord (Open Graph) and X / Twitter.</div>'.PHP_EOL;
	$rtn.='<div class="wg_cards">'.PHP_EOL;
	$rtn.='<div><div class="w_small w_bold w_gray" style="margin-bottom:6px;">Facebook / LinkedIn / Messaging</div>'.$ogcard.'</div>'.PHP_EOL;
	$rtn.='<div><div class="w_small w_bold w_gray" style="margin-bottom:6px;">X / Twitter'.(strlen($social['tw_card'])?' ('.encodeHtml($social['tw_card']).')':' (no twitter:card)').'</div>'.$twcard.'</div>'.PHP_EOL;
	$rtn.='</div>'.PHP_EOL;
	$rtn.=$warnhtml;
	return $rtn;
}

/**
 * @describe plain-text lines summarizing the social share card for the AI prompt.
 * @param social array from websiteGraderSocialData
 * @return array of lines (empty if no social data)
 */
function websiteGraderSocialPromptLines($social){
	if(!is_array($social) || !isset($social['url'])){return array();}
	$lines=array();
	$lines[]="Home page previewed: {$social['url']}";
	$lines[]="- og:title: ".(strlen(trim($social['og_title']))?$social['og_title']:'(missing)');
	$lines[]="- og:description: ".(strlen(trim($social['og_desc']))?$social['og_desc']:'(missing)');
	$lines[]="- og:image: ".(strlen(trim($social['og_image']))?($social['og_image'].($social['img_ok']?' (loads OK)':' (DOES NOT LOAD)')):'(missing)');
	$lines[]="- og:site_name: ".(strlen(trim($social['og_site']))?$social['og_site']:'(missing)');
	$lines[]="- twitter:card: ".(strlen(trim($social['tw_card']))?$social['tw_card']:'(missing — X may not show a rich card)');
	$lines[]="- twitter:image: ".(strlen(trim($social['tw_image']))?$social['tw_image']:'(missing)');
	$lines[]="Please recommend the exact Open Graph and Twitter meta tags (including an og:image at 1200x630) to make the share/link preview look great across Facebook, X/Twitter, and messaging apps.";
	return $lines;
}

/**
 * @describe parse robots.txt into Allow/Disallow rules for one user-agent group, falling back to
 *   the "*" (default) group when there is no group specifically named for $agent.
 * @param robots string, agent string
 * @return array of ['type'=>'allow'|'disallow','path'=>string]
 */
function websiteGraderRobotsGroupRules($robots,$agent='*'){
	$lines=preg_split('/\r\n|\r|\n/',$robots);
	$groups=array();
	$curagents=array();
	$sawrule=false;
	foreach($lines as $line){
		$line=trim(preg_replace('/#.*$/','',$line));
		if(!strlen($line)){continue;}
		if(preg_match('/^user-agent\s*:\s*(.+)$/i',$line,$m)){
			if($sawrule){$curagents=array();$sawrule=false;}
			$a=strtolower(trim($m[1]));
			$curagents[]=$a;
			if(!isset($groups[$a])){$groups[$a]=array();}
		}
		elseif(preg_match('/^(disallow|allow)\s*:\s*(.*)$/i',$line,$m)){
			$sawrule=true;
			$type=strtolower($m[1]);
			$path=trim($m[2]);
			foreach($curagents as $a){$groups[$a][]=array('type'=>$type,'path'=>$path);}
		}
	}
	$key=strtolower($agent);
	if(isset($groups[$key])){return $groups[$key];}
	return isset($groups['*'])?$groups['*']:array();
}

/**
 * @describe does a single robots.txt Allow/Disallow path pattern match $path? Supports the "*"
 *   wildcard (matches anything) and a trailing "$" (anchors the match to the end of $path).
 * @param pattern string, path string
 * @return bool
 */
function websiteGraderRobotsPatternMatches($pattern,$path){
	if(!strlen($pattern)){return false;}
	$endanchor=false;
	if(substr($pattern,-1)=='$'){$endanchor=true;$pattern=substr($pattern,0,-1);}
	$re=preg_quote($pattern,'#');
	$re=str_replace('\*','.*',$re);
	$re='#^'.$re.($endanchor?'$':'').'#';
	return (bool)@preg_match($re,$path);
}

/**
 * @describe does robots.txt disallow crawling $path for $agent? Uses the standard
 *   longest-matching-rule-wins algorithm (an Allow can override a shorter Disallow).
 * @param robots string, path string, agent string
 * @return bool
 */
function websiteGraderRobotsDisallowsPath($robots,$path,$agent='*'){
	if(!strlen(trim($robots))){return false;}
	$rules=websiteGraderRobotsGroupRules($robots,$agent);
	$best=null;$bestlen=-1;
	foreach($rules as $r){
		if(!strlen($r['path']) || !websiteGraderRobotsPatternMatches($r['path'],$path)){continue;}
		$len=commonStrlen($r['path']);
		if($len > $bestlen){$bestlen=$len;$best=$r['type'];}
	}
	return $best=='disallow';
}

/**
 * @describe parse robots.txt and return which of $bots are Disallowed from the site root.
 * @param robots string, bots array
 * @return array of blocked bot names
 */
function websiteGraderRobotsBlockedBots($robots,$bots){
	$blocked=array();
	foreach($bots as $bot){
		if(websiteGraderRobotsDisallowsPath($robots,'/',$bot)){$blocked[]=$bot;}
	}
	return $blocked;
}

//---------- share / email the report ----------

/**
 * @describe render the "Email Report" button shown in the results header. It opens the email
 *   form in the shared centerpop modal (self-creating), which POSTs back to func=email.
 * @return string HTML
 */
function websiteGraderEmailButton(){
	return '<a href="#" class="wacss_button is-small" data-nav="/php/admin.php?_menu=website_grader&func=emailform" data-div="centerpop" data-title="Email SEO &amp; AIO Report" onclick="return wacss.nav(this);" title="Email this report to someone"><span class="icon-mail"></span> Email Report</a>';
}

/**
 * @describe render the "Download Report" button shown next to Email Report. It links straight
 *   to func=download - a plain navigation (not AJAX), so the browser's native file-download
 *   flow (Content-Disposition: attachment, via pushFile) handles the save.
 * @return string HTML
 */
function websiteGraderDownloadButton(){
	return '<a href="/php/admin.php?_menu=website_grader&func=download" class="wacss_button is-small" title="Download the full report as a zip file"><span class="icon-download"></span> Download Report</a>';
}

/**
 * @describe build the full report (report.html, plus fixes.md when there are failing checks)
 *   from the session-stashed result, zip it, and push the zip to the browser as a download.
 *   Named so it identifies the site and the date the report was generated. Exits via pushFile.
 * @return void (exits)
 */
function websiteGraderDownloadReport(){
	if(!isset($_SESSION['websiteGraderReport']['baseurl'])){
		header('Content-type: text/plain');
		echo 'No report is loaded. Run a site check first, then download the report.';
		exit;
	}
	$rep=$_SESSION['websiteGraderReport'];
	$host=parse_url($rep['baseurl'],PHP_URL_HOST);
	$safehost=preg_replace('/[^a-z0-9\.\-]+/i','-',(string)$host);
	$safehost=trim($safehost,'-');
	if(!strlen($safehost)){$safehost='site';}
	$zipname="{$safehost}-seo-aio-report-".date('Y-m-d').'.zip';
	$zippath=rtrim(sys_get_temp_dir(),'/\\').DIRECTORY_SEPARATOR.'seo-aio-report-'.$safehost.'-'.getmypid().'-'.mt_rand(1000,9999).'.zip';
	$zip=new ZipArchive();
	if($zip->open($zippath,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true){
		header('Content-type: text/plain');
		echo 'Could not build the report zip.';
		exit;
	}
	$reporthtml=websiteGraderEmailHTML($rep);
	$reportdoc='<!doctype html>'.PHP_EOL.'<html><head><meta charset="utf-8" /><title>SEO &amp; AIO Report: '.encodeHtml((string)$host).'</title></head><body style="margin:0;padding:20px;background:#fff;">'.PHP_EOL.$reporthtml.'</body></html>';
	$zip->addFromString('report.html',$reportdoc);
	if(websiteGraderHasFailures($rep['checks'])){
		$prompt=websiteGraderAIPrompt($rep['baseurl'],$rep['pages'],$rep['checks'],$rep['grade'],$rep['social'],isset($rep['tech'])?$rep['tech']:array());
		$zip->addFromString('fixes.md',$prompt);
	}
	$zip->close();
	pushFile($zippath,array('-filename'=>$zipname,'-ctype'=>'application/zip','-destroy'=>1));
	exit;
}

/**
 * @describe stash the just-computed report in the session so the email/download steps can
 *   rebuild it without re-crawling (guarantees the emailed/downloaded report matches what is
 *   on screen).
 * @param baseurl string, checks array, grade array, social array, pages array of [url,body], tech array,
 *   excluded array of [url,reason], crawlseconds float
 * @return void
 */
function websiteGraderStoreResult($baseurl,$checks,$grade,$social,$pages,$tech=array(),$excluded=array(),$crawlseconds=0){
	$urls=array();
	foreach($pages as $p){if(isset($p['url'])){$urls[]=$p['url'];}}
	$_SESSION['websiteGraderReport']=array(
		'baseurl'=>$baseurl,
		'checks'=>$checks,
		'grade'=>$grade,
		'social'=>$social,
		'pages'=>$urls,
		'tech'=>$tech,
		'excluded'=>$excluded,
		'crawlseconds'=>$crawlseconds,
		'when'=>date('M j, Y g:i a')
	);
	return;
}

/**
 * @describe render the email form (loaded into the centerpop modal). Recipient + optional
 *   note; from/reply-to default to the logged-in admin. Submits to func=email via ajaxPost.
 * @return string HTML
 */
function websiteGraderEmailForm(){
	global $USER;
	if(!isset($_SESSION['websiteGraderReport']['baseurl'])){
		return '<div class="w_centerpop_title"><span class="icon-mail"></span> Email Report</div>'
			.'<div class="w_centerpop_content"><div class="w_danger" style="padding:10px;"><span class="icon-warning"></span> No report is loaded. Run a site check first, then email the results.</div></div>';
	}
	$rep=$_SESSION['websiteGraderReport'];
	$host=parse_url($rep['baseurl'],PHP_URL_HOST);
	$myemail=(isset($USER['email']) && isEmail($USER['email']))?$USER['email']:'';
	$myname=trim((isset($USER['firstname'])?$USER['firstname']:'').' '.(isset($USER['lastname'])?$USER['lastname']:''));
	if(!strlen($myname) && isset($USER['username'])){$myname=$USER['username'];}
	//$rtn='<div class="w_centerpop_title"><span class="icon-mail"></span> Email SEO &amp; AIO Report</div>'.PHP_EOL;
	//$rtn.='<div class="w_centerpop_content" style="min-width:300px;max-width:460px;">'.PHP_EOL;
	$rtn.='<div class="w_small w_gray" style="margin-bottom:10px;">Send the '.encodeHtml($rep['grade']['percent']).'% report for <span class="w_dblue">'.encodeHtml($host).'</span> ('.count($rep['pages']).' page'.(count($rep['pages'])==1?'':'s').' crawled) as a formatted email.</div>'.PHP_EOL;
	$rtn.='<form method="post" action="/php/admin.php" data-setprocessing="grader_email_status" onsubmit="return wacss.ajaxPost(this,\'grader_email_status\');">'.PHP_EOL;
	$rtn.='	<input type="hidden" name="_menu" value="website_grader" />'.PHP_EOL;
	$rtn.='	<input type="hidden" name="func" value="email" />'.PHP_EOL;
	$rtn.='	<div style="margin-bottom:8px;display:flex;gap:8px;">'.PHP_EOL;
	$rtn.='		<div style="flex:1;"><label class="w_bold">Send to (email)</label>'.PHP_EOL;
	$rtn.='			<input type="email" class="wacss_input" name="to" required="required" placeholder="name@example.com" style="width:100%;box-sizing:border-box;" /></div>'.PHP_EOL;
	$rtn.='		<div style="flex:1;"><label class="w_bold">Recipient name <span class="w_gray w_small">(optional)</span></label>'.PHP_EOL;
	$rtn.='			<input type="text" class="wacss_input" name="toname" style="width:100%;box-sizing:border-box;" /></div>'.PHP_EOL;
	$rtn.='	</div>'.PHP_EOL;
	$rtn.='	<div style="margin-bottom:8px;display:flex;gap:8px;">'.PHP_EOL;
	$rtn.='		<div style="flex:1;"><label class="w_bold">Your name <span class="w_gray w_small">(optional)</span></label>'.PHP_EOL;
	$rtn.='			<input type="text" class="wacss_input" name="fromname" value="'.encodeHtml($myname).'" style="width:100%;box-sizing:border-box;" /></div>'.PHP_EOL;
	$rtn.='		<div style="flex:1;"><label class="w_bold">Reply-to <span class="w_gray w_small">(optional)</span></label>'.PHP_EOL;
	$rtn.='			<input type="email" class="wacss_input" name="replyto" value="'.encodeHtml($myemail).'" placeholder="you@example.com" style="width:100%;box-sizing:border-box;" /></div>'.PHP_EOL;
	$rtn.='	</div>'.PHP_EOL;
	$rtn.='	<div style="margin-bottom:8px;display:flex;gap:8px;">'.PHP_EOL;
	$rtn.='		<div style="flex:1;"><label class="w_bold">Cc <span class="w_gray w_small">(optional)</span></label>'.PHP_EOL;
	$rtn.='			<input type="text" class="wacss_input" name="cc" placeholder="name@example.com, name2@example.com" style="width:100%;box-sizing:border-box;" /></div>'.PHP_EOL;
	$rtn.='		<div style="flex:1;"><label class="w_bold">Bcc <span class="w_gray w_small">(optional)</span></label>'.PHP_EOL;
	$rtn.='			<input type="text" class="wacss_input" name="bcc" placeholder="name@example.com, name2@example.com" style="width:100%;box-sizing:border-box;" /></div>'.PHP_EOL;
	$rtn.='	</div>'.PHP_EOL;
	$rtn.='	<div style="margin-bottom:10px;"><label class="w_bold">Note <span class="w_gray w_small">(optional)</span></label>'.PHP_EOL;
	$rtn.='		<textarea class="wacss_textarea" name="note" rows="3" placeholder="Add a short message&hellip;" style="width:100%;box-sizing:border-box;"></textarea></div>'.PHP_EOL;
	$rtn.='	<div style="display:flex;gap:8px;justify-content:flex-end;">'.PHP_EOL;
	$rtn.='		<button type="button" class="wacss_button" onclick="wacss.centerpopClose();return false;">Cancel</button>'.PHP_EOL;
	$rtn.='		<button type="submit" class="wacss_button '.configValue('admin_color').'"><span class="icon-mail"></span> Send Report</button>'.PHP_EOL;
	$rtn.='	</div>'.PHP_EOL;
	$rtn.='	<div id="grader_email_status" style="margin-top:10px;"></div>'.PHP_EOL;
	$rtn.='</form>'.PHP_EOL;
	//$rtn.='</div>'.PHP_EOL;
	return $rtn;
}

/**
 * @describe does the report have any failing checks (i.e. is an AI fix file "necessary")?
 * @param checks array
 * @return bool
 */
function websiteGraderHasFailures($checks){
	foreach($checks as $c){
		if($c['pass']==$c['total'] && !count($c['fails'])){continue;}
		return true;
	}
	return false;
}

/**
 * @describe write the AI fix prompt to a temp .md file for email attachment, return its path ('' on failure).
 * @param host string, prompt string
 * @return string full path or ''
 */
function websiteGraderWriteFixFile($host,$prompt){
	$safe=preg_replace('/[^a-z0-9\.\-]/i','_',$host);
	if(!strlen($safe)){$safe='site';}
	$file=rtrim(sys_get_temp_dir(),'/\\').DIRECTORY_SEPARATOR.'seo-aio-fixes-'.$safe.'-'.getmypid().'.md';
	$ok=setFileContents($file,$prompt);
	return is_file($file)?$file:'';
}

/**
 * @describe validate the recipient, build the report email from the session, and send it.
 *   When the report has failing checks, an AI-ready fix .md file is attached. Returns a
 *   status message (AJAX partial) to drop into grader_email_status.
 * @return string HTML status
 */
function websiteGraderSendReport(){
	global $USER;
	global $CONFIG;
	$to=trim(isset($_REQUEST['to'])?$_REQUEST['to']:'');
	if(!isEmail($to)){
		return '<div class="w_danger" style="padding:8px 0;"><span class="icon-warning"></span> Please enter a valid recipient email address.</div>';
	}
	if(!isset($_SESSION['websiteGraderReport']['baseurl'])){
		return '<div class="w_danger" style="padding:8px 0;"><span class="icon-warning"></span> The report expired. Close this, re-run the check, and try again.</div>';
	}
	$rep=$_SESSION['websiteGraderReport'];
	$note=trim(isset($_REQUEST['note'])?$_REQUEST['note']:'');
	$toname=trim(isset($_REQUEST['toname'])?$_REQUEST['toname']:'');
	$fromname=trim(isset($_REQUEST['fromname'])?$_REQUEST['fromname']:'');
	$replyto=trim(isset($_REQUEST['replyto'])?$_REQUEST['replyto']:'');
	$cc=trim(isset($_REQUEST['cc'])?$_REQUEST['cc']:'');
	$bcc=trim(isset($_REQUEST['bcc'])?$_REQUEST['bcc']:'');
	$host=parse_url($rep['baseurl'],PHP_URL_HOST);
	//from address: config email_from is the deliverable sender; reply-to routes replies to the admin
	$from=isset($CONFIG['email_from'])?$CONFIG['email_from']:(isset($USER['email'])?$USER['email']:'');
	if(!isEmail($from)){
		return '<div class="w_danger" style="padding:8px 0;"><span class="icon-warning"></span> No valid sender address is configured (config.xml <b>email_from</b>). Cannot send mail from this site.</div>';
	}
	$fromheader=strlen($fromname)?($fromname.' <'.$from.'>'):$from;
	$subject='SEO & AIO Report: '.$host.' — '.$rep['grade']['percent'].'% (Grade '.$rep['grade']['letter'].')';
	$message=websiteGraderEmailHTML($rep,$note,$fromname,$toname);
	$mailopts=array('to'=>$to,'from'=>$fromheader,'subject'=>$subject,'message'=>$message);
	if(isEmail($replyto)){$mailopts['reply-to']=$replyto;}
	if(strlen($cc)){$mailopts['cc']=$cc;}
	if(strlen($bcc)){$mailopts['bcc']=$bcc;}
	//attach the AI-ready fix file only when there are issues to fix
	$fixfile='';
	if(websiteGraderHasFailures($rep['checks'])){
		$prompt=websiteGraderAIPrompt($rep['baseurl'],$rep['pages'],$rep['checks'],$rep['grade'],$rep['social'],isset($rep['tech'])?$rep['tech']:array());
		$fixfile=websiteGraderWriteFixFile($host,$prompt);
		if(strlen($fixfile)){$mailopts['attach']=array($fixfile);}
	}
	$err=sendMail($mailopts);
	//clean up the temp attachment
	if(strlen($fixfile) && is_file($fixfile)){@unlink($fixfile);}
	//sendMail returns null (native mail) OR 1 (phpmailer/SMTP path) on success; an error STRING on failure
	$sent=($err===null || $err===1 || $err==='1' || $err===true);
	if($sent){
		return '<div class="w_success" style="padding:8px 0;"><span class="icon-mark"></span> Report sent to <b>'.encodeHtml($to).'</b>.</div>'
			.buildOnLoad("setTimeout(function(){wacss.centerpopClose();wacss.toast('Report emailed to ".addslashes($to)."','is-success');},900);");
	}
	return '<div class="w_danger" style="padding:8px 0;"><span class="icon-warning"></span> Could not send the email:<br />'.encodeHtml(removeHtml((string)$err)).'</div>';
}

/**
 * @describe build the HTML email body for the report: warm greeting, grade hero, per-category
 *   scores, failed checks grouped by category, the social share summary, a plain-language terms
 *   glossary, and a friendly sign-off. Inline styles + tables so it renders in email clients
 *   (Gmail, Outlook, Apple Mail).
 * @param rep array (the stored report), note string, fromname string, toname string
 * @return string HTML (xml so sendMail sends it as multipart/html)
 */
function websiteGraderEmailHTML($rep,$note='',$fromname='',$toname=''){
	$baseurl=$rep['baseurl'];
	$host=parse_url($baseurl,PHP_URL_HOST);
	$grade=$rep['grade'];
	$checks=$rep['checks'];
	$color=$grade['color'];
	$gradeplain=trim(html_entity_decode(preg_replace('/&mdash;/','-',$grade['label']),ENT_QUOTES));
	$pagecnt=count($rep['pages']);
	$font='font-family:Helvetica,Arial,sans-serif;';
	$h='<div style="'.$font.'max-width:640px;margin:0 auto;color:#1d2129;font-size:14px;line-height:1.5;">'.PHP_EOL;
	//header
	$h.='<div style="border-bottom:3px solid '.$color.';padding-bottom:10px;margin-bottom:16px;">';
	$h.='<div style="font-size:12px;letter-spacing:.5px;color:#8a9099;text-transform:uppercase;">SEO &amp; AI Optimization Report</div>';
	$h.='<div style="font-size:22px;font-weight:700;color:#1d2129;">'.encodeHtml($host).'</div>';
	$excluded=isset($rep['excluded']) && is_array($rep['excluded'])?$rep['excluded']:array();
	$crawlseconds=isset($rep['crawlseconds'])?(float)$rep['crawlseconds']:0;
	$h.='<div style="font-size:12px;color:#8a9099;">'.encodeHtml($baseurl).' &nbsp;&middot;&nbsp; '.$pagecnt.' page'.($pagecnt==1?'':'s').' crawled'.($crawlseconds>0?(' in '.websiteGraderFormatSeconds($crawlseconds)):'').(count($excluded)?(' ('.count($excluded).' excluded via robots.txt/noindex)'):'').' &nbsp;&middot;&nbsp; '.encodeHtml($rep['when']).'</div>';
	$h.='</div>'.PHP_EOL;
	//warm, personal greeting
	$h.='<div style="margin-bottom:14px;">Hi '.(strlen($toname)?encodeHtml($toname):'there').',</div>'.PHP_EOL;
	$h.='<div style="margin-bottom:16px;">Here'."'".'s a look at how <b>'.encodeHtml($host).'</b> is doing for search engines and AI visibility'.(strlen($fromname)?(', put together by '.encodeHtml($fromname)):'').'. It scored <b>'.$grade['percent'].'%</b> ('.$grade['pass'].' of '.$grade['total'].' checks passed) &mdash; details are below, along with plain-language explanations for any terms that might be new to you.</div>'.PHP_EOL;
	//optional note
	if(strlen($note)){
		$h.='<div style="background:#f7f8fa;border:1px solid #e6e8eb;border-radius:8px;padding:12px 14px;margin-bottom:16px;">';
		if(strlen($fromname)){$h.='<div style="font-size:12px;color:#8a9099;margin-bottom:4px;">Note from '.encodeHtml($fromname).':</div>';}
		$h.='<div style="white-space:pre-wrap;">'.nl2br(encodeHtml($note)).'</div></div>'.PHP_EOL;
	}
	//grade hero
	$h.='<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;background:#f7f8fa;border:1px solid #e6e8eb;border-radius:12px;margin-bottom:18px;"><tr>';
	$h.='<td style="padding:16px 18px;vertical-align:middle;width:110px;text-align:center;">';
	$h.='<div style="display:inline-block;width:88px;height:88px;border-radius:50%;background:'.$color.';color:#fff;text-align:center;">';
	$h.='<div style="font-size:26px;font-weight:700;padding-top:20px;line-height:1;">'.$grade['percent'].'%</div>';
	$h.='<div style="font-size:11px;letter-spacing:.5px;opacity:.9;">GRADE '.$grade['letter'].'</div></div></td>';
	$h.='<td style="padding:16px 18px 16px 4px;vertical-align:middle;">';
	$h.='<div style="font-size:18px;font-weight:700;color:'.$color.';">'.$gradeplain.'</div>';
	$h.='<div style="color:#606770;font-size:13px;margin-top:2px;">'.$grade['pass'].' of '.$grade['total'].' checks passed</div></td>';
	$h.='</tr></table>'.PHP_EOL;
	//category scores
	$h.='<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin-bottom:18px;">';
	foreach(websiteGraderCategories() as $cat=>$label){
		$cg=websiteGraderCategoryGrade($checks,$cat);
		if($cg['total']==0){continue;}
		$plain=trim(preg_replace('/&mdash;.*$/','',str_replace('&amp;','&',$label)));
		$ccolor=websiteGraderGradeColor($cg['percent']);
		$h.='<tr><td style="padding:6px 0;border-bottom:1px solid #eef0f2;font-weight:600;">'.encodeHtml($plain).'</td>';
		$h.='<td style="padding:6px 0;border-bottom:1px solid #eef0f2;text-align:right;color:#606770;font-size:12px;">'.$cg['pass'].'/'.$cg['total'].'</td>';
		$h.='<td style="padding:6px 0 6px 10px;border-bottom:1px solid #eef0f2;text-align:right;width:52px;"><span style="display:inline-block;background:'.$ccolor.';color:#fff;border-radius:10px;padding:2px 8px;font-size:12px;font-weight:600;">'.$cg['percent'].'%</span></td></tr>';
	}
	$h.='</table>'.PHP_EOL;
	//failed checks by category
	$anyfail=false;
	$fh='';
	foreach(websiteGraderCategories() as $cat=>$label){
		$catplain=trim(preg_replace('/&mdash;.*$/','',str_replace('&amp;','&',$label)));
		$catrows='';
		foreach($checks as $c){
			if($c['category']!=$cat){continue;}
			if($c['pass']==$c['total'] && !count($c['fails'])){continue;}
			$anyfail=true;
			$clabel=trim(html_entity_decode($c['label'],ENT_QUOTES));
			$catrows.='<div style="margin:10px 0 4px;font-weight:700;color:#1d2129;">'.encodeHtml($clabel).' <span style="color:#d64545;font-weight:600;font-size:12px;">('.count($c['fails']).' of '.$c['total'].' failing)</span></div>';
			$fails=$c['fails'];
			$cap=8;$more=0;
			if(count($fails) > $cap){$more=count($fails)-$cap;$fails=array_slice($fails,0,$cap);}
			$catrows.='<ul style="margin:0 0 0 18px;padding:0;color:#4a5560;font-size:13px;">';
			foreach($fails as $f){
				$loc='';
				if(isset($f['page']) && preg_match('/href="([^"]+)"/i',$f['page'],$mm)){$loc=html_entity_decode($mm[1],ENT_QUOTES);}
				$sugg=isset($f['suggestion'])?websiteGraderPlainText($f['suggestion']):'';
				$catrows.='<li style="margin-bottom:4px;">';
				if(strlen($loc)){$catrows.='<a href="'.htmlspecialchars($loc,ENT_QUOTES).'" style="color:#1a5fb4;">'.encodeHtml($loc).'</a> — ';}
				$catrows.=encodeHtml($sugg).'</li>';
			}
			if($more > 0){$catrows.='<li style="color:#8a9099;">…and '.$more.' more page'.($more==1?'':'s').'</li>';}
			$catrows.='</ul>';
		}
		if(strlen($catrows)){
			$fh.='<div style="margin-bottom:14px;"><div style="font-size:13px;font-weight:700;color:#8a9099;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid #e6e8eb;padding-bottom:4px;margin-bottom:4px;">'.encodeHtml($catplain).'</div>'.$catrows.'</div>';
		}
	}
	if($anyfail){
		$h.='<div style="font-size:16px;font-weight:700;margin-bottom:6px;">Issues to fix</div>'.PHP_EOL;
		$h.='<div style="background:#fff8e6;border:1px solid #f0e0b0;border-radius:8px;padding:10px 12px;margin-bottom:12px;color:#8a6d00;font-size:13px;">📎 An AI-ready fix file (<b>seo-aio-fixes-'.encodeHtml($host).'.md</b>) is attached. Paste its contents into Claude, ChatGPT, or any AI assistant to generate the exact HTML, meta tags, and structured data to fix every item below.</div>'.PHP_EOL;
		$h.=$fh;
	}
	else{
		$h.='<div style="background:#eafbea;border:1px solid #bfe6bf;border-radius:8px;padding:12px 14px;color:#1f7a1f;margin-bottom:14px;">✓ Every SEO, Social, AIO, and technical check passed — no issues found.</div>'.PHP_EOL;
	}
	//pages excluded from checks (robots.txt / noindex) - reported separately, not as failures
	if(count($excluded)){
		$h.='<div style="font-size:16px;font-weight:700;margin:18px 0 6px;">Excluded from checks ('.count($excluded).')</div>';
		$h.='<div style="color:#606770;font-size:13px;margin-bottom:6px;">These pages are already excluded from indexing by the site itself, so on-page checks were skipped for them:</div>';
		$h.='<ul style="margin:0 0 0 18px;padding:0;color:#4a5560;font-size:13px;">';
		foreach($excluded as $ex){
			$h.='<li style="margin-bottom:3px;"><a href="'.htmlspecialchars($ex['url'],ENT_QUOTES).'" style="color:#1a5fb4;">'.encodeHtml($ex['url']).'</a> &mdash; '.encodeHtml($ex['reason']).'</li>';
		}
		$h.='</ul>'.PHP_EOL;
	}
	//social summary
	$soclines=websiteGraderSocialPromptLines($rep['social']);
	if(count($soclines)){
		$h.='<div style="font-size:16px;font-weight:700;margin:18px 0 6px;">Social / link-share preview</div>';
		$h.='<ul style="margin:0 0 0 18px;padding:0;color:#4a5560;font-size:13px;">';
		//skip the first (page url) and the trailing instruction line; show the field lines
		foreach($soclines as $i=>$sl){
			if($i==0 || $i==count($soclines)-1){continue;}
			$h.='<li style="margin-bottom:3px;">'.encodeHtml($sl).'</li>';
		}
		$h.='</ul>'.PHP_EOL;
	}
	//technology detected
	$tech=isset($rep['tech']) && is_array($rep['tech'])?$rep['tech']:array();
	$techrows='';
	foreach(websiteGraderTechCategories() as $cat=>$catlabel){
		if(!isset($tech[$cat]) || !count($tech[$cat])){continue;}
		$names=$tech[$cat];
		sort($names);
		$plaincat=trim(html_entity_decode(preg_replace('/&amp;/','&',$catlabel),ENT_QUOTES));
		$techrows.='<tr><td style="padding:4px 8px 4px 0;font-weight:600;color:#1d2129;border-bottom:1px solid #eef0f2;">'.encodeHtml($plaincat).'</td><td style="padding:4px 0;color:#4a5560;border-bottom:1px solid #eef0f2;">'.encodeHtml(implode(', ',$names)).'</td></tr>';
	}
	if(strlen($techrows)){
		$h.='<div style="font-size:16px;font-weight:700;margin:18px 0 6px;">Technology detected</div>'.PHP_EOL;
		$h.='<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;font-size:13px;">'.$techrows.'</table>'.PHP_EOL;
	}
	//terms glossary - only the terms actually used by checks in this report, so it explains
	//jargon (Open Graph, canonical, JSON-LD, etc.) without padding the email with unused entries
	$glossaryrows='';
	foreach(websiteGraderTermsGlossary() as $gkey=>$g){
		if(!isset($checks[$gkey])){continue;}
		$glossaryrows.='<div style="margin-bottom:8px;"><span style="font-weight:700;color:#1d2129;">'.encodeHtml($g['term']).'</span> &mdash; <span style="color:#4a5560;">'.encodeHtml($g['def']).'</span></div>'.PHP_EOL;
	}
	if(strlen($glossaryrows)){
		$h.='<div style="font-size:16px;font-weight:700;margin:18px 0 6px;">Terms explained</div>'.PHP_EOL;
		$h.='<div style="background:#f7f8fa;border:1px solid #e6e8eb;border-radius:8px;padding:14px 16px;font-size:13px;">'.$glossaryrows.'</div>'.PHP_EOL;
	}
	$h.='</div>'.PHP_EOL;
	return $h;
}
?>
