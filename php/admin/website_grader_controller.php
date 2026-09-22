<?php
/*
	Website Checker controller (thin): route on $_REQUEST['func'].
	  - default (no func) : show the URL entry form (view: default)
	  - func=run          : crawl the entered URL, run SEO/AIO/misc checks,
	                         build results, and render the AJAX partial (view: result)
	All the real work lives in website_grader_functions.php.
*/
	global $CONFIG;
	switch(strtolower($_REQUEST['func'])){
		case 'run':
			//defaults so the result view can always render safely
			$grader_error='';
			$baseurl='';
			$redirectnotice='';
			$pages=array();
			$checks=array();
			$grade=array('percent'=>0,'pass'=>0,'total'=>0,'label'=>'','letter'=>'','color'=>'#888');
			$social=array();
			$tech=array();
			$excludedpages=array();
			$fromlocalfile=false;
			//validate URL
			$starturl=websiteGraderNormalizeURL($_REQUEST['url']);
			if(!filter_var($starturl,FILTER_VALIDATE_URL)){
				$grader_error='Please enter a valid website URL (e.g. https://example.com).';
				setView('result',1);
				break;
			}
			$parts=parse_url($starturl);
			if(!isset($parts['scheme']) || !in_array(strtolower($parts['scheme']),array('http','https'))){
				$grader_error='URL must use http or https.';
				setView('result',1);
				break;
			}
			//optional escape hatch for sites behind a bot-verification challenge (Cloudflare, etc.)
			//that blocks curl but not a real browser: the developer saves the page locally (browser
			//"Save Page As") and types the full local path here instead of us fetching it live. A
			//plain path field (rather than a <input type=file> upload) is deliberate - this admin.php
			//menu is already admin-only/localhost, and a path lets the developer point at anything on
			//disk (the saved html, or - later - a sibling "_files" folder of downloaded images/assets)
			//without a copy round-tripping through PHP's upload handling.
			//Explorer's "Copy as path" wraps the value in literal double quotes - strip them (and
			//any stray surrounding single quotes) along with whitespace before treating it as a path.
			$localabspath=trim(trim((string)$_REQUEST['localpath']),"\"'");
			if(strlen($localabspath)){
				if(!is_file($localabspath) || !is_readable($localabspath)){
					$grader_error="Could not read local file: {$localabspath}";
					setView('result',1);
					break;
				}
			}
			//how many pages to crawl (ignored when grading a single local page)
			$maxpages=(int)$_REQUEST['maxpages'];
			if($maxpages < 1){$maxpages=50;}
			if($maxpages > 300){$maxpages=300;}
			//this crawl is synchronous and can legitimately run for minutes (many page fetches +
			//image HEAD checks). session.save_path is file-based here (database_sessions=0 in
			//config.xml), which locks the session file for the life of the script - release that
			//lock now so it doesn't block every other tab/request in this same browser session
			//while we crawl. Reacquired below, right before we need to write to $_SESSION again.
			session_write_close();
			$crawlstart=microtime(true);
			if(strlen($localabspath)){
				$crawl=websiteGraderCrawlFromLocalFile($localabspath,$starturl);
				$fromlocalfile=true;
			}
			else{
				//crawl the live site
				$crawl=websiteGraderCrawl($starturl,$maxpages);
			}
			$crawlseconds=round(microtime(true)-$crawlstart,1);
			if(isset($crawl['error'])){
				session_start();
				$grader_error=$crawl['error'];
				setView('result',1);
				break;
			}
			$baseurl=$crawl['baseurl'];
			$pages=$crawl['pages'];
			//the start URL can redirect to a different host (e.g. a "dev" subdomain that bounces
			//anonymous visitors to the production domain) - -follow is on in websiteGraderFetch,
			//so the crawl silently ends up grading whatever host it landed on. Flag that clearly
			//rather than showing a report labeled with the site the caller actually typed in.
			//(not applicable when grading from a local file - baseurl is derived straight from
			//the URL the developer typed in, so it can never mismatch.)
			$requestedhost=(string)parse_url($starturl,PHP_URL_HOST);
			$finalhost=(string)parse_url($baseurl,PHP_URL_HOST);
			if(!$fromlocalfile && strlen($requestedhost) && strlen($finalhost) && strcasecmp($requestedhost,$finalhost)!==0){
				$redirectnotice="You requested {$starturl} — it redirected to a different host. These results are for {$baseurl}, not {$requestedhost}.";
			}
			if($fromlocalfile){
				$redirectnotice="Graded from a local copy of {$starturl} ({$localabspath}) — that site blocks automated crawling, so only that saved page could be checked. Site-wide checks (robots.txt, sitemap.xml, llms.txt, HTTPS/www redirects, favicon) were still tested by contacting the live site directly, and may fail here too if the whole domain blocks automated requests.";
			}
			//run all checks, grade them, gather the social preview data, and fingerprint the tech stack
			$checks=websiteGraderRunChecks($baseurl,$pages,$crawl['robots'],$excludedpages);
			$grade=websiteGraderGrade($checks);
			$social=count($pages)?websiteGraderSocialData($pages[0],$baseurl):array();
			$tech=websiteGraderDetectTech($pages,$crawl['robots'],(string)parse_url($baseurl,PHP_URL_HOST));
			//reacquire the session so we can stash the report for the email/download steps
			session_start();
			websiteGraderStoreResult($baseurl,$checks,$grade,$social,$pages,$tech,$excludedpages,$crawlseconds,$redirectnotice,$fromlocalfile);
			setView('result',1);
		break;
		case 'emailform':
			//show the "email this report" form inside the centerpop modal
			$email_form=websiteGraderEmailForm();
			setView('emailform',1);
		break;
		case 'email':
			//validate + build the report email from the session and send it
			$email_status=websiteGraderSendReport();
			setView('email_result',1);
		break;
		case 'download':
			//streams the zip and exits - never reaches setView
			websiteGraderDownloadReport();
		break;
		default:
			setView('default');
		break;
	}
?>
