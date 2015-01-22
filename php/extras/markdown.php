<?php
/*
	http://www.parsedown.org
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
* @usage <?=markdown2Html($txt));?>
* @author Brady Barten 1/20/2014
*/
function markdown2Html($txt){
	return Parsedown::instance()->parse($txt);
}
//---------- begin function markdownFromHtml--------------------
/**
* @link https://github.com/nickcernis/html-to-markdown
* @describe converts HTML text to markdown
* @param text string - HTML string
* @param params array
*	-strip_tags boolean - default is false - strip HTML tags that don't have a markdown equivalent (span, div)
* @return string - markdown
* @usage <?=markdownFromHtml($txt));?>
* @author Brady Barten 1/20/2014
*/
function markdownFromHtml($html,$params=array()){
	$markdown = new HTML_To_Markdown();
	if($params['-strip_tags']){$markdown->set_option('strip_tags', true);}
	$markdown->convert($html);
	return $markdown->output();
}
?>