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
			$pages=array();
			$checks=array();
			$grade=array('percent'=>0,'pass'=>0,'total'=>0,'label'=>'','letter'=>'','color'=>'#888');
			$social=array();
			$tech=array();
			$excludedpages=array();
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
			//how many pages to crawl
			$maxpages=(int)$_REQUEST['maxpages'];
			if($maxpages < 1){$maxpages=50;}
			if($maxpages > 300){$maxpages=300;}
			//this crawl is synchronous and can legitimately run for minutes (many page fetches +
			//image HEAD checks). session.save_path is file-based here (database_sessions=0 in
			//config.xml), which locks the session file for the life of the script - release that
			//lock now so it doesn't block every other tab/request in this same browser session
			//while we crawl. Reacquired below, right before we need to write to $_SESSION again.
			session_write_close();
			//crawl the live site
			$crawlstart=microtime(true);
			$crawl=websiteGraderCrawl($starturl,$maxpages);
			$crawlseconds=round(microtime(true)-$crawlstart,1);
			if(isset($crawl['error'])){
				session_start();
				$grader_error=$crawl['error'];
				setView('result',1);
				break;
			}
			$baseurl=$crawl['baseurl'];
			$pages=$crawl['pages'];
			//run all checks, grade them, gather the social preview data, and fingerprint the tech stack
			$checks=websiteGraderRunChecks($baseurl,$pages,$crawl['robots'],$excludedpages);
			$grade=websiteGraderGrade($checks);
			$social=count($pages)?websiteGraderSocialData($pages[0],$baseurl):array();
			$tech=websiteGraderDetectTech($pages,$crawl['robots'],(string)parse_url($baseurl,PHP_URL_HOST));
			//reacquire the session so we can stash the report for the email/download steps
			session_start();
			websiteGraderStoreResult($baseurl,$checks,$grade,$social,$pages,$tech,$excludedpages,$crawlseconds);
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
