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
			if($maxpages < 1){$maxpages=20;}
			if($maxpages > 50){$maxpages=50;}
			//crawl the live site
			$crawl=websiteGraderCrawl($starturl,$maxpages);
			if(isset($crawl['error'])){
				$grader_error=$crawl['error'];
				setView('result',1);
				break;
			}
			$baseurl=$crawl['baseurl'];
			$pages=$crawl['pages'];
			//run all checks, grade them, and gather the social preview data
			$checks=websiteGraderRunChecks($baseurl,$pages,$crawl['robots']);
			$grade=websiteGraderGrade($checks);
			$social=count($pages)?websiteGraderSocialData($pages[0],$baseurl):array();
			setView('result',1);
		break;
		default:
			setView('default');
		break;
	}
?>
