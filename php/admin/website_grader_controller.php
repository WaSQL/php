<?php
/*
	https://github.com/rkonstadinos/seo-techniques-tool/tree/master/seo-techniques
	
	https://www.semrush.com/blog/seo-checklist/?kw=&cmp=US_SRCH_DSA_Blog_SEO_New_EN&label=dsa_pagefeed&Network=g&Device=c&utm_content=460045215351&kwid=dsa-944982275753&cmpid=8012574163&agpid=106525566694&gclid=CjwKCAjw0On8BRAgEiwAincsHM810xCs2_X0yut7ZbkYG5hrSO8-X8tCE1z2RHwypJy3z1tV3UgeJhoCXEUQAvD_BwE

	robots.txt
	sitemap.xml
	ssl/https
	domain-authority
	meta tags on page match content
	title on links
	responsive

	ads.txt
	site.webmanifest
	asset-manifest.json

*/
	global $CONFIG;
	$stats=array();
	//check if all images have alt tags
	$recs=array();
	//misc
	$recs['misc']=websiteGraderMisc();
	if(!count($recs['misc'])){
		$recs['misc_grade']='<span class="icon-mark w_success"></span>';
	}
	//head
	$recs['page']=websiteGraderPage();
	if(!count($recs['page'])){
		$recs['page_grade']='<span class="icon-mark w_success"></span>';
	}

	setView('default');
?>