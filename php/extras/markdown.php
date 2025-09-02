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
* @param string $txt     - markdown string
* @param array  $params  - e.g. ['target' => '_blank', 'rel' => 'noopener noreferrer']
* @return string - HTML
* @usage echo markdown2Email($txt, ['target' => '_blank']);
*/
function markdown2Email($txt, $params = array()) {
    $html = Parsedown::instance()->text($txt);

    // 1) Tweak <a> tags reliably via DOM (handles any spacing/newlines/attribute order)
    $linkStyle = 'color:#0066cc;text-decoration:underline;';
    $linkTarget = isset($params['target']) ? $params['target'] : null;
    $linkRel    = isset($params['rel']) ? $params['rel'] : null;

    if (class_exists('DOMDocument')) {
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        // Ensure UTF-8 and avoid HTML wrapper side-effects
        $dom->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">'.$html);
        libxml_clear_errors();

        foreach ($dom->getElementsByTagName('a') as $a) {
            // Style: append to any existing style
            $existingStyle = $a->getAttribute('style');
            $a->setAttribute('style', trim($existingStyle.' '.$linkStyle));

            if (!empty($linkTarget)) {
                $a->setAttribute('target', $linkTarget);
            }
            if (!empty($linkRel)) {
                // Append rel tokens if rel already exists
                $existingRel = trim($a->getAttribute('rel'));
                $newRel = trim($existingRel.' '.$linkRel);
                // Deduplicate rel tokens
                if ($newRel !== '') {
                    $relParts = preg_split('/\s+/', $newRel);
                    $a->setAttribute('rel', implode(' ', array_unique($relParts)));
                }
            }
        }

        // Extract body innerHTML
        $body = $dom->getElementsByTagName('body')->item(0);
        $newHtml = '';
        foreach ($body->childNodes as $child) {
            $newHtml .= $dom->saveHTML($child);
        }
        $html = $newHtml;
    } else {
        // 1b) Fallback: regex approach if DOM extension isn’t available
        $t = $linkTarget;
        $r = $linkRel;
        $s = $linkStyle;
        $html = preg_replace_callback('/<a\b([^>]*)>/i', function ($m) use ($t, $r, $s) {
            $attrs = $m[1];

            // style
            if (preg_match('/\bstyle="/i', $attrs)) {
                $attrs = preg_replace('/\bstyle="([^"]*)"/i', 'style="$1 '.$s.'"', $attrs);
            } else {
                $attrs .= ' style="'.$s.'"';
            }

            // target
            if (!empty($t)) {
                if (preg_match('/\btarget="/i', $attrs)) {
                    $attrs = preg_replace('/\btarget="[^"]*"/i', 'target="'.htmlspecialchars($t, ENT_QUOTES).'"', $attrs);
                } else {
                    $attrs .= ' target="'.htmlspecialchars($t, ENT_QUOTES).'"';
                }
            }

            // rel (append + dedupe basic)
            if (!empty($r)) {
                if (preg_match('/\brel="([^"]*)"/i', $attrs, $rm)) {
                    $existing = trim($rm[1]);
                    $merged = trim($existing.' '.$r);
                    $parts = preg_split('/\s+/', $merged);
                    $merged = implode(' ', array_unique($parts));
                    $attrs = preg_replace('/\brel="[^"]*"/i', 'rel="'.$merged.'"', $attrs);
                } else {
                    $attrs .= ' rel="'.$r.'"';
                }
            }

            return '<a'.$attrs.'>';
        }, $html);
    }

    // 2) Your email-friendly inline styles for other tags
    $emailStyles = array(
        '<h1>' => '<h1 style="font-family:Arial,sans-serif;font-size:24px;color:#333;margin:20px 0 10px 0;font-weight:bold;">',
        '<h2>' => '<h2 style="font-family:Arial,sans-serif;font-size:20px;color:#333;margin:18px 0 8px 0;font-weight:bold;">',
        '<h3>' => '<h3 style="font-family:Arial,sans-serif;font-size:18px;color:#333;margin:16px 0 6px 0;font-weight:bold;">',
        '<h4>' => '<h4 style="font-family:Arial,sans-serif;font-size:16px;color:#333;margin:14px 0 4px 0;font-weight:bold;">',
        '<h5>' => '<h5 style="font-family:Arial,sans-serif;font-size:14px;color:#333;margin:12px 0 4px 0;font-weight:bold;">',
        '<h6>' => '<h6 style="font-family:Arial,sans-serif;font-size:12px;color:#333;margin:10px 0 4px 0;font-weight:bold;">',
        '<p>'  => '<p style="font-family:Arial,sans-serif;font-size:14px;line-height:1.6;margin:10px 0;color:#333;">',
        '<strong>' => '<strong style="font-weight:bold;">',
        '<em>' => '<em style="font-style:italic;">',
        '<ul>' => '<ul style="margin:10px 0;padding-left:20px;">',
        '<ol>' => '<ol style="margin:10px 0;padding-left:20px;">',
        '<li>' => '<li style="font-family:Arial,sans-serif;font-size:14px;line-height:1.6;margin:5px 0;">',
        '<blockquote>' => '<blockquote style="border-left:4px solid #ccc;margin:15px 0;padding-left:15px;font-style:italic;color:#666;">',
        '<code>' => '<code style="background-color:#f4f4f4;padding:2px 4px;border-radius:3px;font-family:monospace;font-size:13px;">',
        '<pre>' => '<pre style="background-color:#f4f4f4;padding:10px;border-radius:5px;overflow-x:auto;font-family:monospace;font-size:13px;margin:10px 0;">',
        // NOTE: we no longer touch '<a ' here; handled above
        '<hr>' => '<hr style="border:none;border-top:1px solid #ccc;margin:20px 0;">',
    );

    $html = str_replace(array_keys($emailStyles), array_values($emailStyles), $html);

    return $html;
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