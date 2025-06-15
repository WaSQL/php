<?php
/*
	http://www.parsedown.org
	https://github.com/erusev/parsedown
	https://github.com/nickcernis/html-to-markdown
*/
$progpath=dirname(__FILE__);
//load the parsedown library
require_once("{$progpath}/markdown/parsedown.php");
require_once("{$progpath}/markdown/HTML_To_Markdown.php");
//---------- begin function markdown2Html--------------------
/**
* @describe converts markdown text to html
* @param text string - markdown string
* @return string - HTML
* @usage markdown2Html($txt));
* @author Brady Barten 1/20/2014
*/
function markdown2Html($txt){
	//return Parsedown::instance()->parse($txt);
	return Parsedown::instance()->text($txt);
}
//---------- begin function markdown2Email--------------------
/**
* @describe converts markdown text to email friendly HTML
* @param text string - markdown string
* @return string - HTML
* @usage markdown2Email($txt));
*/
function markdown2Email($txt) {
   $html = Parsedown::instance()->text($txt);
   
   // Email-friendly inline styles
   $emailStyles = array(
       '<h1>' => '<h1 style="font-family:Arial,sans-serif;font-size:24px;color:#333;margin:20px 0 10px 0;font-weight:bold;">',
       '<h2>' => '<h2 style="font-family:Arial,sans-serif;font-size:20px;color:#333;margin:18px 0 8px 0;font-weight:bold;">',
       '<h3>' => '<h3 style="font-family:Arial,sans-serif;font-size:18px;color:#333;margin:16px 0 6px 0;font-weight:bold;">',
       '<h4>' => '<h4 style="font-family:Arial,sans-serif;font-size:16px;color:#333;margin:14px 0 4px 0;font-weight:bold;">',
       '<h5>' => '<h5 style="font-family:Arial,sans-serif;font-size:14px;color:#333;margin:12px 0 4px 0;font-weight:bold;">',
       '<h6>' => '<h6 style="font-family:Arial,sans-serif;font-size:12px;color:#333;margin:10px 0 4px 0;font-weight:bold;">',
       '<p>' => '<p style="font-family:Arial,sans-serif;font-size:14px;line-height:1.6;margin:10px 0;color:#333;">',
       '<strong>' => '<strong style="font-weight:bold;">',
       '<em>' => '<em style="font-style:italic;">',
       '<ul>' => '<ul style="margin:10px 0;padding-left:20px;">',
       '<ol>' => '<ol style="margin:10px 0;padding-left:20px;">',
       '<li>' => '<li style="font-family:Arial,sans-serif;font-size:14px;line-height:1.6;margin:5px 0;">',
       '<blockquote>' => '<blockquote style="border-left:4px solid #ccc;margin:15px 0;padding-left:15px;font-style:italic;color:#666;">',
       '<code>' => '<code style="background-color:#f4f4f4;padding:2px 4px;border-radius:3px;font-family:monospace;font-size:13px;">',
       '<pre>' => '<pre style="background-color:#f4f4f4;padding:10px;border-radius:5px;overflow-x:auto;font-family:monospace;font-size:13px;margin:10px 0;">',
       '<a ' => '<a style="color:#0066cc;text-decoration:underline;" ',
       '<hr>' => '<hr style="border:none;border-top:1px solid #ccc;margin:20px 0;">',
   );
   
   return str_replace(array_keys($emailStyles), array_values($emailStyles), $html);
}
//---------- begin function markdownFromHtml--------------------
/**
* @link https://github.com/nickcernis/html-to-markdown
* @describe converts HTML text to markdown
* @param text string - HTML string
* @param params array
*	-strip_tags boolean - default is false - strip HTML tags that don't have a markdown equivalent (span, div)
*	-atx boolean - default is false - use ## for H1 tags instead of underscores
* @return string - markdown
* @usage markdownFromHtml($txt));
* @author Brady Barten 1/20/2014
*/
function markdownFromHtml($html,$params=array()){
	$markdown = new HTML_To_Markdown();
	if($params['-strip_tags']){$markdown->set_option('strip_tags', true);}
	if($params['-atx']){$markdown->set_option('header_style', 'atx');}
	$markdown->convert($html);
	return $markdown->output();
}
?>