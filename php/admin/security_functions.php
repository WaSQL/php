<?php
/*
	Security Scanner (admin menu "security").
	A read-only, heuristic static scan of THIS site's page code - the _pages (body, controller,
	functions) and _templates (body, functions, controller) records - for places where request
	data reaches a dangerous sink:
	  sqli      raw SQL text, or a -where/-filter/-order/-query/-fields/-group/-having/-limit/
	            -offset/-search value (dashless filter keys are escaped by core getDBWhere(), so
	            they are NOT flagged)
	  xss       <?= ... ?>, echo/print, or an HTML string built in PHP, without encodeHtml() etc.
	  exec      eval/include/require/system/exec/unserialize/file access/extract
	  include   includePage()/loadDBFunctions() with a request-controlled name
	  redirect  header('Location: ...') built from request data
	Sources: $_REQUEST $_GET $_POST $_COOKIE $_FILES $PASSTHRU and $_SERVER HTTP_*, REQUEST_URI,
	QUERY_STRING, PHP_SELF, PATH_INFO.

	Taint is tracked per record (all fields of one page together), flow- and scope-insensitive,
	through assignments, foreach over request data, and calls to functions defined in that record.
	Expect some false positives: "low" means "go look", not "safe".

	Layout:
	  engine  : securityScanGroups() and the security*Scan* helpers (pure - no DB, no output)
	  sources : securityLoadSiteSources() reads this site's _pages/_templates
	  UI      : securityRenderResults() + securityRenderAIPanel() / securityAIPrompt()
	The repo-root CLI wasql_seccheck.php reuses the engine against PostEdit mirror files.
*/

//---------- begin function securityPatterns
/**
* @describe compiled-once regex/word tables used by the scanner engine
* @return array
* @usage $p=securityPatterns();
*/
function securityPatterns(){
	static $p=null;
	if(is_array($p)){return $p;}
	//casts / functions whose result is safe in every context
	$common='isset|empty|isNum|is_numeric|ctype_digit|ctype_alnum|intval|floatval|boolval|abs|round|ceil|floor|'
		.'count|sizeof|strlen|commonStrlen|in_array|array_key_exists|is_array|isEmail|isDate|strtotime|'
		.'md5|sha1|crc32|hash|password_hash|password_verify|base64_encode|bin2hex|uniqid|date|time|mktime|'
		.'number_format|formatMoney|preg_match|filter_var|commonFormatPhone|getDBCount|dbGetCount';
	$sqlsan='databaseEscapeString|dbEscapeString|addslashes|mysqli_real_escape_string|real_escape_string|pg_escape_string|quote';
	$htmlnames='encodeHtml|htmlspecialchars|htmlentities|strip_tags|removeHtml|urlencode|rawurlencode|encodeURL|json_encode|encodeJson';
	$htmlsan=$htmlnames.'|nl2br\s*\(\s*encodeHtml';
	$cast='\(\s*(?:int|integer|float|double|bool|boolean)\s*\)\s*';
	$words=function($s){
		$r=array();
		foreach(preg_split('/\s+/',trim($s)) as $w){$r[$w]=1;}
		return $r;
	};
	$p=array(
		'cast'=>$cast,
		'cast_end'=>'~'.$cast.'$~i',
		'san_common_before'=>'~(?:\b(?:'.$common.')\s*\([^()]*|'.$cast.')$~i',
		'san_sql_before'=>'~(?:\b(?:'.$sqlsan.')\s*\([^()]*|'.$cast.')$~i',
		'san_html_before'=>'~(?:\b(?:'.$htmlsan.')\s*\([^()]*|'.$cast.')$~i',
		'san_common_any'=>'~\b(?:'.$common.')\s*\(|'.$cast.'~i',
		'sql_san_names'=>'~^(?:'.$sqlsan.')$~i',
		'html_san_names'=>'~^(?:'.$htmlnames.')$~i',
		'sql_sink_calls'=>$words('executesql dbexecutesql getdbrecord getdbrecords dbgetrecord dbgetrecords dbqueryresults
			getdbcount dbgetcount databasequery databasequeryresults mysqli_query query prepare databaselistrecords
			dblistrecords listdbrecords editdbrecord deldbrecord adddbrecord getdbrecordbyid editdbrecordbyid getdbfieldinfo'),
		'constructs'=>$words('if elseif while switch for foreach return echo print'),
		//calls whose result still carries the raw input (string shaping, decoding, array building)
		'passthrough'=>array(''=>1)+$words('array list trim ltrim rtrim strtolower strtoupper ucfirst ucwords lcfirst
			str_replace str_ireplace substr mb_substr preg_replace sprintf vsprintf implode join explode preg_split
			str_split urldecode rawurldecode base64_decode json_decode decodejson stripslashes html_entity_decode
			htmlspecialchars_decode nl2br str_pad strrev wordwrap array_values array_keys array_merge array_filter
			array_map array_unique array_slice array_reverse reset end current array_shift array_pop strval str_repeat
			truncatewords preg_quote utf8_decode utf8_encode mb_convert_encoding iconv'),
		'sql_strong'=>'~\bselect\b[\s\S]+?\bfrom\b|\bupdate\s+[`\w.]+\s+set\b|\binsert\s+(?:ignore\s+)?into\b|\bdelete\s+from\b|\breplace\s+into\b|\btruncate\s+(?:table\s+)?[`\w]~i',
		'sql_weak'=>'~\b(?:where|and|or|having|on)\s+\(?[`\w.()]+\s*(?:=|<>|!=|<=|>=|<|>|\blike\b|\bin\s*\(|\bis\b|\bbetween\b|\bregexp\b)|\border\s+by\b|\bgroup\s+by\b|\blimit\s+[\d{$]|[\w}\]`]\s*(?:=|<>|!=)\s*\'\{?\$|\blike\s*\'%?\{?\$~i',
		'sql_colassign'=>'~^\s*\(?\s*[`\w.]+\s*(?:=|<>|!=|\blike\b|\bin\s*\()\s*\'?%?$~i',
		'html_like'=>'~<[a-zA-Z/!]|\b(?:href|src|value|onclick|style|class)\s*=~i',
		'sql_keys'=>array('-where'=>1,'-filter'=>1,'-order'=>1,'-query'=>1,'-fields'=>1,'-group'=>1,'-having'=>1,'-limit'=>1,'-offset'=>1,'-search'=>1),
		'guards'=>array(
			'~\b(?:isNum|is_numeric|ctype_digit|ctype_alnum|in_array|isEmail|isDate|array_key_exists|intval|filter_var)\s*\(\s*\$([A-Za-z_]\w*)\s*[,)]~i',
			'~\bpreg_match\s*\([^,]+,\s*\$([A-Za-z_]\w*)\s*\)~i',
			'~\$([A-Za-z_]\w*)\s*=\s*'.$cast.'\$\1\b~i',
			'~\bswitch\s*\(\s*\$([A-Za-z_]\w*)\s*\)~i',
		),
		'exec'=>'~(?<![$\w>:])(?:eval|evalPHP|system|exec|shell_exec|passthru|popen|proc_open|unserialize|assert|create_function|file_put_contents|file_get_contents|fopen|readfile|unlink|move_uploaded_file|copy|rename|extract|parse_str|setFileContents|getFileContents)\s*\(|(?<![$\w>:])(?:include|include_once|require|require_once)\b~i',
		'include'=>'~(?<![$\w>:])(?:includePage|loadDBFunctions|includeDBOnce)\s*\(~i',
		'header'=>'~(?<![$\w>:])header\s*\(~i',
		'sql_exec_call'=>'~\b(?:executeSQL|dbExecuteSQL|dbQueryResults|dbGetRecords?|getDBRecords?|databaseQuery|mysqli_query|query)\s*\(~i',
		'sql_var_name'=>'~^(sql|query|q|qry|where|wheres|filter|order|orderby|sort)\w*$~i',
		'call'=>'~(?<![\w$>:])([A-Za-z_]\w*)\s*\(~',
		'funcdef'=>'~\bfunction\s+&?\s*([A-Za-z_]\w*)\s*\(([^)]*)\)~i',
		//built by concatenation so no literal php close tag appears in this file
		'island'=>'~<\?(php\b|=)?(.*?)(?:\?'.'>|\z)~si',
		'heredoc'=>'~\G<<<[ \t]*(["\']?)([A-Za-z_]\w*)\1[ \t]*\r?\n~',
		'assign'=>'~^\s*\$([A-Za-z_]\w*)\s*(\.=|=)(?![=>])~',
		'foreach'=>'~\bforeach\s*\(\s*(.+?)\s+as\s+\$(\w+)(?:\s*=>\s*\$(\w+))?~is',
		'echo'=>'~^\s*(?:echo|print)\b~i',
		'var'=>'~(?<![\\\\\w$])\$([A-Za-z_]\w*)~',
		'dqvar'=>'~(?<!\\\\)\{?\$([A-Za-z_]\w*)~',
		'server_key'=>'~^\s*\[\s*[\'"]?(HTTP_\w+|REQUEST_URI|QUERY_STRING|PHP_SELF|PATH_INFO|ORIG_PATH_INFO)[\'"]?\s*\]~',
		'sources'=>array('_REQUEST'=>1,'_GET'=>1,'_POST'=>1,'_COOKIE'=>1,'_FILES'=>1,'PASSTHRU'=>1),
		'skip_vars'=>array('this'=>1,'GLOBALS'=>1,'USER'=>1,'CONFIG'=>1,'PAGE'=>1,'TEMPLATE'=>1,'DATABASE'=>1),
		'severities'=>array('low'=>0,'medium'=>1,'high'=>2),
	);
	return $p;
}

//---------- begin function securityScanTokenize
/**
* @describe split PHP code into segments array(kind,text,offset); kind = code|sq|dq|bt|nowdoc
* @param code string - PHP code (inside one island)
* @param base int - byte offset of $code within its source text
* @return array
* @usage $segs=securityScanTokenize($code,0);
*/
function securityScanTokenize($code,$base){
	$p=securityPatterns();
	$segs=array();
	$n=strlen($code);
	$i=0;
	$cs=0;
	while($i<$n){
		if(!preg_match('~[\'"`#/<]~',$code,$m,PREG_OFFSET_CAPTURE,$i)){break;}
		$i=$m[0][1];
		$c=$code[$i];
		if($c=="'" || $c=='"' || $c=='`'){
			if($i>$cs){$segs[]=array('code',substr($code,$cs,$i-$cs),$base+$cs);}
			$j=$i+1;
			while($j<$n){
				if($code[$j]=='\\'){$j+=2;continue;}
				if($code[$j]==$c){break;}
				$j++;
			}
			$kind=$c=="'"?'sq':($c=='"'?'dq':'bt');
			$segs[]=array($kind,substr($code,$i+1,max(0,min($j,$n)-$i-1)),$base+$i+1);
			$i=$j+1;
			$cs=$i;
			continue;
		}
		if($c=='#' || substr($code,$i,2)=='//'){
			if($i>$cs){$segs[]=array('code',substr($code,$cs,$i-$cs),$base+$cs);}
			$j=strpos($code,"\n",$i);
			$i=$j===false?$n:$j;
			$cs=$i;
			continue;
		}
		if(substr($code,$i,2)=='/*'){
			if($i>$cs){$segs[]=array('code',substr($code,$cs,$i-$cs),$base+$cs);}
			$j=strpos($code,'*/',$i+2);
			$i=$j===false?$n:$j+2;
			$cs=$i;
			continue;
		}
		if(substr($code,$i,3)=='<<<' && preg_match($p['heredoc'],$code,$hm,0,$i)){
			if($i>$cs){$segs[]=array('code',substr($code,$cs,$i-$cs),$base+$cs);}
			$bodystart=$i+strlen($hm[0]);
			if(preg_match('~^[ \t]*'.preg_quote($hm[2],'~').'\b~m',$code,$em,PREG_OFFSET_CAPTURE,$bodystart)){
				$bodyend=$em[0][1];
				$next=$em[0][1]+strlen($em[0][0]);
			}
			else{
				$bodyend=$n;
				$next=$n;
			}
			$segs[]=array($hm[1]=="'"?'nowdoc':'dq',substr($code,$bodystart,$bodyend-$bodystart),$base+$bodystart);
			$i=$next;
			$cs=$i;
			continue;
		}
		$i++;
	}
	if($n>$cs){$segs[]=array('code',substr($code,$cs),$base+$cs);}
	return $segs;
}

//---------- begin function securityScanStatements
/**
* @describe group segments into statements, splitting code on ; { } and a line-ending : (case labels)
* @param segs array - from securityScanTokenize
* @return array - list of segment lists
* @usage $stmts=securityScanStatements($segs);
*/
function securityScanStatements($segs){
	$out=array();
	$cur=array();
	foreach($segs as $seg){
		if($seg[0]!='code'){$cur[]=$seg;continue;}
		$text=$seg[1];
		$pos=0;
		preg_match_all('~[;{}]|:(?=[ \t]*\r?\n)~',$text,$ms,PREG_OFFSET_CAPTURE);
		foreach($ms[0] as $mm){
			$piece=substr($text,$pos,$mm[1]-$pos);
			if(strlen(trim($piece))){$cur[]=array('code',$piece,$seg[2]+$pos);}
			if(count($cur)){$out[]=$cur;$cur=array();}
			$pos=$mm[1]+strlen($mm[0]);
		}
		$piece=substr($text,$pos);
		if(strlen(trim($piece))){$cur[]=array('code',$piece,$seg[2]+$pos);}
	}
	if(count($cur)){$out[]=$cur;}
	return $out;
}

//---------- begin function securityScanVars
/**
* @describe variables in a code fragment or (dq=true) interpolated in a double-quoted/heredoc string
* @param text string
* @param dq boolean
* @return array - list of array(name, offset, rest-of-text-after-name)
* @usage $vars=securityScanVars($text,false);
*/
function securityScanVars($text,$dq=false){
	$p=securityPatterns();
	$out=array();
	if(strpos($text,'$')===false){return $out;}
	preg_match_all($dq?$p['dqvar']:$p['var'],$text,$ms,PREG_OFFSET_CAPTURE);
	foreach($ms[1] as $k=>$mm){
		$end=$ms[0][$k][1]+strlen($ms[0][$k][0]);
		$out[]=array($mm[0],$ms[0][$k][1],(string)substr($text,$end,40));
	}
	return $out;
}

//---------- begin function securityScanIsSource
/**
* @describe true if $name (with following text $rest) is a request-data source
* @param name string - variable name without $
* @param rest string - text right after the name (for $_SERVER keys)
* @return boolean
* @usage if(securityScanIsSource('_REQUEST',"['x']")){...}
*/
function securityScanIsSource($name,$rest){
	$p=securityPatterns();
	if(isset($p['sources'][$name])){return true;}
	if($name=='_SERVER'){return preg_match($p['server_key'],$rest)==1;}
	return false;
}

//---------- begin function securityScanSourceLabel
/**
* @describe human label for a source, e.g. $_REQUEST['id']
* @param name string
* @param rest string
* @return string
* @usage $label=securityScanSourceLabel('_REQUEST',"['id']");
*/
function securityScanSourceLabel($name,$rest){
	if(preg_match('~^\s*\[\s*([^\]]{0,40})\]~',$rest,$m)){return '$'.$name.'['.$m[1].']';}
	return '$'.$name;
}

//---------- begin function securityScanFlatten
/**
* @describe one string for a list of segments: code verbatim, sq strings masked, dq strings reduced to their $vars
* @param segs array
* @return string
* @usage $flat=securityScanFlatten($segs);
*/
function securityScanFlatten($segs){
	$out='';
	foreach($segs as $s){
		if($s[0]=='code'){$out.=$s[1];}
		elseif($s[0]=='dq' || $s[0]=='bt'){
			$names=array();
			foreach(securityScanVars($s[1],true) as $v){$names[]='$'.$v[0];}
			$out.='"'.implode(' ',$names).'"';
		}
		else{$out.="'".preg_replace('~[,()\[\]{}$]~','_',$s[1])."'";}
	}
	return $out;
}

//---------- begin function securityScanEnclosingCalls
/**
* @describe lower-cased names of the calls still open at the end of $before ('' = bare parenthesis)
* @param before string
* @return array
* @usage $calls=securityScanEnclosingCalls('foo(bar(');
*/
function securityScanEnclosingCalls($before){
	$stack=array();
	if(strpos($before,'(')===false){return $stack;}
	preg_match_all('~([A-Za-z_]\w*)?\s*\(|\)~',$before,$ms,PREG_SET_ORDER);
	foreach($ms as $m){
		if($m[0]==')'){array_pop($stack);}
		else{$stack[]=strtolower(isset($m[1])?$m[1]:'');}
	}
	return $stack;
}

//---------- begin function securityScanSplitArgs
/**
* @describe top-level comma split of a call's argument list starting just after its (
* @param flat string
* @param pos int
* @return array
* @usage $args=securityScanSplitArgs($flat,$pos);
*/
function securityScanSplitArgs($flat,$pos){
	$depth=0;
	$args=array();
	$cur='';
	$n=strlen($flat);
	for($i=$pos;$i<$n;$i++){
		$ch=$flat[$i];
		if($ch=='(' || $ch=='[' || $ch=='{'){$depth++;}
		elseif($ch==')' || $ch==']' || $ch=='}'){
			if($depth==0){break;}
			$depth--;
		}
		elseif($ch==',' && $depth==0){$args[]=$cur;$cur='';continue;}
		$cur.=$ch;
	}
	$args[]=$cur;
	return $args;
}

//---------- begin function securityScanNewPage
/**
* @describe build the analysis state for one record (all its fields analysed together)
* @param record string - display name, e.g. _pages/portal
* @param files array - list of array('label'=>..,'text'=>..,'meta'=>array(..))
* @return array - page state
* @usage $pg=securityScanNewPage('_pages/portal',$files);
*/
function securityScanNewPage($record,$files){
	$p=securityPatterns();
	$pg=array('record'=>$record,'files'=>array(),'stmts'=>array(),'funcs'=>array(),'guarded'=>array(),
		'taint'=>array(),'mixed'=>array(),'findings'=>array());
	$raw=array();
	foreach($files as $fi=>$f){
		$pg['files'][$fi]=$f;
		$raw[]=$f['text'];
		if(strpos($f['text'],'<?')===false){continue;}
		preg_match_all($p['island'],$f['text'],$ms,PREG_SET_ORDER|PREG_OFFSET_CAPTURE);
		foreach($ms as $m){
			$tag=isset($m[1]) && $m[1][1]>=0?$m[1][0]:'';
			$body=$m[2][0];
			if(!strlen(trim($body))){continue;}
			$segs=securityScanTokenize($body,$m[2][1]);
			if($tag=='='){
				$pg['stmts'][]=array('segs'=>$segs,'file'=>$fi,'echo'=>true);
			}
			else{
				foreach(securityScanStatements($segs) as $s){
					$pg['stmts'][]=array('segs'=>$s,'file'=>$fi,'echo'=>false);
				}
			}
		}
	}
	$raw=implode("\n",$raw);
	preg_match_all($p['funcdef'],$raw,$ms,PREG_SET_ORDER);
	foreach($ms as $m){
		preg_match_all('~\$(\w+)~',$m[2],$pm);
		$pg['funcs'][strtolower($m[1])]=$pm[1];
	}
	foreach($p['guards'] as $rx){
		preg_match_all($rx,$raw,$gm);
		foreach($gm[1] as $g){$pg['guarded'][$g]=1;}
	}
	return $pg;
}

//---------- begin function securityScanLine
/**
* @describe 1-based line number of a byte offset in one of the page's files
* @param pg array
* @param fi int - file index
* @param offset int
* @return int
* @usage $line=securityScanLine($pg,0,120);
*/
function securityScanLine($pg,$fi,$offset){
	return substr_count($pg['files'][$fi]['text'],"\n",0,max(0,min($offset,strlen($pg['files'][$fi]['text']))))+1;
}

//---------- begin function securityScanSqlLiteral
/**
* @describe true if a string literal looks like SQL (and not like HTML)
* @param text string
* @return boolean
* @usage if(securityScanSqlLiteral($s)){...}
*/
function securityScanSqlLiteral($text){
	$p=securityPatterns();
	if(preg_match($p['html_like'],$text)){return false;}
	return preg_match($p['sql_strong'],$text) || preg_match($p['sql_weak'],$text) || preg_match($p['sql_colassign'],$text);
}

//---------- begin function securityScanSqlContext
/**
* @describe true if any string literal in the segments looks like SQL
* @param segs array
* @return boolean
* @usage if(securityScanSqlContext($segs)){...}
*/
function securityScanSqlContext($segs){
	foreach($segs as $s){
		if(($s[0]=='dq' || $s[0]=='sq') && securityScanSqlLiteral($s[1])){return true;}
	}
	return false;
}

//---------- begin function securityScanHtmlContext
/**
* @describe true if any string literal in the segments looks like HTML
* @param segs array
* @return boolean
* @usage if(securityScanHtmlContext($segs)){...}
*/
function securityScanHtmlContext($segs){
	$p=securityPatterns();
	foreach($segs as $s){
		if(($s[0]=='dq' || $s[0]=='sq') && preg_match($p['html_like'],$s[1])){return true;}
	}
	return false;
}

//---------- begin function securityScanOccurrences
/**
* @describe tainted occurrences in segments for a context. With $callfilter a value passed INTO a
*	function call is skipped (the call's result is not the raw input); with $concatonly a code
*	variable only counts when it is concatenated (.) into the surrounding string.
* @param pg array - page state
* @param segs array
* @param ctx string - sql|html|any
* @param callfilter boolean
* @param concatonly boolean
* @return array - list of array(offset, label, origin, level, reason)
* @usage $hits=securityScanOccurrences($pg,$segs,'sql');
*/
function securityScanOccurrences(&$pg,$segs,$ctx,$callfilter=true,$concatonly=false){
	$p=securityPatterns();
	$out=array();
	$cnt=count($segs);
	for($si=0;$si<$cnt;$si++){
		list($kind,$text,$start)=$segs[$si];
		if($kind=='sq' || $kind=='nowdoc' || strpos($text,'$')===false){continue;}
		$items=securityScanVars($text,$kind!='code');
		if($kind=='code'){
			//the tokenizer split quoted array keys into their own segments; stitch them back
			$follow='';
			for($k=$si+1;$k<min($cnt,$si+4);$k++){
				$follow.=$segs[$k][0]=='code'?$segs[$k][1]:'"'.$segs[$k][1].'"';
			}
			foreach($items as $x=>$it){
				$items[$x][2]=substr(substr($text,$it[1]+strlen($it[0])+1).$follow,0,60);
			}
		}
		foreach($items as $it){
			list($name,$idx,$rest)=$it;
			//a variable used only as an array subscript ($row[$k]) doesn't flow into the value
			if(preg_match('~\$\w+(?:\[[^\]]*\])*\[\s*$~',substr($text,max(0,$idx-60),min($idx,60)))){continue;}
			if($kind=='code'){
				$from=max(0,$idx-80);
				$before=substr($text,$from,$idx-$from);
				if(preg_match($p['san_common_before'],$before)){continue;}
				if($ctx=='sql' && preg_match($p['san_sql_before'],$before)){continue;}
				if($ctx=='html' && preg_match($p['san_html_before'],$before)){continue;}
				//assignment target
				if(preg_match('~^\s*(?:\.=|=)(?![=>])~',$rest)){continue;}
				//ternary condition: only decides which branch is used
				if(preg_match('~^(?:\[[^\]]*\]|->\w+)*\s*\?(?![?>-])~',$rest)){continue;}
				//not concatenated into the SQL/HTML string (e.g. a dashless 'col'=>$v)
				if($concatonly && !preg_match('~(?<!\.)\.\s*$~',substr($text,0,$idx)) && !preg_match('~^(?:\[[^\]]*\]|->\w+)*\s*\.(?!=)~',$rest)){continue;}
			}
			$src=securityScanIsSource($name,$rest);
			if(!$src && (!isset($pg['taint'][$name]) || isset($p['skip_vars'][$name]))){continue;}
			if($callfilter){
				$prefix=securityScanFlatten(array_slice($segs,0,$si)).($kind=='code'?substr($text,0,$idx):'"');
				$skip=false;
				foreach(securityScanEnclosingCalls($prefix) as $c){
					if(isset($p['passthrough'][$c]) || isset($p['constructs'][$c])){continue;}
					if($ctx=='sql' && isset($p['sql_sink_calls'][$c])){continue;}
					$skip=true;
					break;
				}
				if($skip){continue;}
			}
			if($src){
				$label=securityScanSourceLabel($name,$rest);
				$reason=preg_match('~HTTP_HOST|SERVER_NAME~',$label)?'Host header - normally only exploitable via cache poisoning':'';
				$out[]=array($start+$idx,$label,'direct','src',$reason);
				continue;
			}
			$t=$pg['taint'][$name];
			if($ctx=='sql' && !$t['sql']){continue;}
			if($ctx=='html' && !$t['html']){continue;}
			$reason='';
			if(isset($pg['guarded'][$name])){$reason='validated somewhere on this page - check it covers this path';}
			elseif(isset($pg['mixed'][$name])){$reason='name also holds non-request data elsewhere on this page';}
			$out[]=array($start+$idx,'$'.$name,$t['origin'],$t['level'],$reason);
		}
	}
	return $out;
}

//---------- begin function securityScanAdd
/**
* @describe record a finding (deduped per file/line/type/var, keeping the highest severity)
* @return void
* @usage securityScanAdd($pg,'high','sqli',$fi,$offset,$var,$origin,$reason);
*/
function securityScanAdd(&$pg,$severity,$type,$fi,$offset,$var,$origin,$reason=''){
	$p=securityPatterns();
	if(strlen($reason)){$origin="{$origin} [{$reason}]";}
	$line=securityScanLine($pg,$fi,$offset);
	$key="{$fi}|{$line}|{$type}|{$var}";
	if(isset($pg['findings'][$key]) && $p['severities'][$pg['findings'][$key]['severity']] >= $p['severities'][$severity]){return;}
	$lines=explode("\n",$pg['files'][$fi]['text']);
	$snippet=isset($lines[$line-1])?trim($lines[$line-1]):'';
	if(strlen($snippet)>200){$snippet=function_exists('mb_substr')?mb_substr($snippet,0,200):substr($snippet,0,200);}
	$f=array('severity'=>$severity,'type'=>$type,'record'=>$pg['record'],'file'=>$pg['files'][$fi]['label'],
		'line'=>$line,'var'=>$var,'origin'=>$origin,'snippet'=>$snippet);
	if(isset($pg['files'][$fi]['meta']) && is_array($pg['files'][$fi]['meta'])){
		foreach($pg['files'][$fi]['meta'] as $k=>$v){$f[$k]=$v;}
	}
	$pg['findings'][$key]=$f;
}

//---------- begin function securityScanSetTaint
/**
* @describe mark a variable tainted (or widen its taint); returns true if anything changed
* @return boolean
* @usage $changed=securityScanSetTaint($pg,'id',true,true,'src','$_REQUEST[id] line 3');
*/
function securityScanSetTaint(&$pg,$name,$sql,$html,$level,$origin){
	$p=securityPatterns();
	if(isset($p['skip_vars'][$name]) || isset($p['sources'][$name]) || !($sql || $html)){return false;}
	if(!isset($pg['taint'][$name])){
		$pg['taint'][$name]=array('sql'=>$sql,'html'=>$html,'level'=>$level,'origin'=>$origin);
		return true;
	}
	$t=&$pg['taint'][$name];
	$changed=false;
	if($sql && !$t['sql']){$t['sql']=true;$changed=true;}
	if($html && !$t['html']){$t['html']=true;$changed=true;}
	if($level=='src' && $t['level']!='src'){$t['level']='src';$t['origin']=$origin;$changed=true;}
	return $changed;
}

//---------- begin function securityScanRhs
/**
* @describe analyse `$lhs = rhs`. Returns null (not a plain assignment, or it builds SQL/HTML),
*	or array(lhs, best, sql, html) where best is null when the RHS carries no request data
* @return mixed
* @usage $r=securityScanRhs($pg,$stmt);
*/
function securityScanRhs(&$pg,$st){
	$p=securityPatterns();
	$first=$st['segs'][0];
	if($first[0]!='code' || !preg_match($p['assign'],$first[1],$m)){return null;}
	$lhs=$m[1];
	$rhs=array_merge(array(array('code',substr($first[1],strlen($m[0])),$first[2]+strlen($m[0]))),array_slice($st['segs'],1));
	//an SQL- or HTML-building string is reported where it is built; don't re-taint the result
	if(securityScanSqlContext($rhs) || securityScanHtmlContext($rhs)){return null;}
	$flat=securityScanFlatten($rhs);
	//isNum($x)?$x:0 style validated ternaries
	if(preg_match('~^\s*!?\s*(?:isNum|is_numeric|ctype_digit|ctype_alnum|in_array|preg_match|isEmail|isDate)\s*\([^?]*\?~i',$flat)){
		return array($lhs,null,false,false);
	}
	//plain ternary: the condition doesn't flow into the result
	$startat=0;
	$depth=0;
	$n=strlen($flat);
	for($i=0;$i<$n;$i++){
		$ch=$flat[$i];
		if($ch=='(' || $ch=='['){$depth++;}
		elseif($ch==')' || $ch==']'){$depth--;}
		elseif($ch=='?' && $depth==0){
			$nx=$i+1<$n?$flat[$i+1]:'';
			$pv=$i>0?$flat[$i-1]:'';
			if($nx!='?' && $nx!='-' && $nx!='>' && $pv!='?'){$startat=$i+1;break;}
		}
	}
	$best=null;
	$sqlany=false;
	$htmlany=false;
	foreach(securityScanVars($flat,false) as $v){
		list($name,$idx,$rest)=$v;
		if($idx<$startat){continue;}
		$src=securityScanIsSource($name,$rest);
		if($src){
			$level='src';
			$origin=securityScanSourceLabel($name,$rest).' line '.securityScanLine($pg,$st['file'],$rhs[0][2]);
		}
		elseif(isset($pg['taint'][$name]) && !isset($p['skip_vars'][$name])){
			$level=$pg['taint'][$name]['level'];
			$origin=$pg['taint'][$name]['origin'];
		}
		else{continue;}
		$before=substr($flat,0,$idx);
		if(preg_match($p['cast_end'],$before)){continue;}
		$calls=securityScanEnclosingCalls($before);
		$sql=true;
		$html=true;
		$wrapped=false;
		foreach($calls as $c){
			if(preg_match($p['sql_san_names'],$c)){$sql=false;continue;}
			if(preg_match($p['html_san_names'],$c)){$html=false;continue;}
			if(!isset($p['passthrough'][$c])){$wrapped=true;break;}
		}
		//wrapped in a call whose result isn't the raw input (DB lookup, isNum, count...)
		if($wrapped){continue;}
		if(!$src){
			$sql=$sql && $pg['taint'][$name]['sql'];
			$html=$html && $pg['taint'][$name]['html'];
		}
		if(!($sql || $html)){continue;}
		$sqlany=$sqlany || $sql;
		$htmlany=$htmlany || $html;
		if(is_null($best) || ($level=='src' && $best[0]!='src')){$best=array($level,$origin);}
	}
	return array($lhs,$best,$sqlany,$htmlany);
}

//---------- begin function securityScanForeachSubject
/**
* @describe parse a foreach statement; returns array(subject, keyvar, valvar) or null
* @return mixed
* @usage $fe=securityScanForeachSubject($stmt);
*/
function securityScanForeachSubject($st){
	$p=securityPatterns();
	$text='';
	foreach($st['segs'] as $s){$text.=$s[0]=='code'?$s[1]:'""';}
	if(stripos($text,'foreach')===false || !preg_match($p['foreach'],$text,$m)){return null;}
	return array($m[1],$m[2],isset($m[3])?$m[3]:'');
}

//---------- begin function securityScanPropagate
/**
* @describe spread taint through assignments, foreach loops and same-record function calls until stable
* @return void
* @usage securityScanPropagate($pg);
*/
function securityScanPropagate(&$pg){
	$p=securityPatterns();
	for($pass=0;$pass<6;$pass++){
		$changed=false;
		foreach($pg['stmts'] as $st){
			if($st['echo']){continue;}
			//assignment
			$r=securityScanRhs($pg,$st);
			if(is_array($r) && !is_null($r[1])){
				if(securityScanSetTaint($pg,$r[0],$r[2],$r[3],$r[1][0],$r[1][1])){$changed=true;}
			}
			//foreach over request data
			$fe=securityScanForeachSubject($st);
			if(is_array($fe)){
				$hit=null;
				foreach(securityScanVars($fe[0],false) as $v){
					if(securityScanIsSource($v[0],$v[2])){$hit=array('src',securityScanSourceLabel($v[0],$v[2]));break;}
					if(isset($pg['taint'][$v[0]])){$hit=array($pg['taint'][$v[0]]['level'],$pg['taint'][$v[0]]['origin']);break;}
				}
				if(!is_null($hit)){
					foreach(array($fe[1],$fe[2]) as $lv){
						if(strlen($lv) && securityScanSetTaint($pg,$lv,true,true,$hit[0],'foreach over '.$hit[1])){$changed=true;}
					}
				}
			}
			//calls to functions defined in this record: taint the parameter names
			if(!count($pg['funcs'])){continue;}
			$flat=securityScanFlatten($st['segs']);
			if(strpos($flat,'$')===false){continue;}
			preg_match_all($p['call'],$flat,$cms,PREG_SET_ORDER|PREG_OFFSET_CAPTURE);
			foreach($cms as $cm){
				$fname=strtolower($cm[1][0]);
				if(empty($pg['funcs'][$fname])){continue;}
				$params=$pg['funcs'][$fname];
				//skip the definition itself
				if(preg_match('~function\s+&?\s*$~i',substr($flat,0,$cm[1][1]))){continue;}
				$args=securityScanSplitArgs($flat,$cm[0][1]+strlen($cm[0][0]));
				foreach(array_slice($args,0,count($params)) as $ai=>$arg){
					if(preg_match($p['san_common_any'],$arg)){continue;}
					foreach(securityScanVars($arg,false) as $v){
						$src=securityScanIsSource($v[0],$v[2]);
						if($src || (isset($pg['taint'][$v[0]]) && $pg['taint'][$v[0]]['level']=='src')){
							$origin=$src?securityScanSourceLabel($v[0],$v[2]):$pg['taint'][$v[0]]['origin'];
							if(securityScanSetTaint($pg,$params[$ai],true,true,'param',"param of {$fname}() from {$origin}")){$changed=true;}
							break;
						}
					}
				}
			}
		}
		if(!$changed){break;}
	}
}

//---------- begin function securityScanFindMixed
/**
* @describe variables that are also (re)assigned from non-request data somewhere on the page
* @return void
* @usage securityScanFindMixed($pg);
*/
function securityScanFindMixed(&$pg){
	foreach($pg['stmts'] as $st){
		if($st['echo']){continue;}
		$first=$st['segs'][0];
		if($first[0]=='code' && preg_match('~^\s*\$\w+\s*=(?![=>])~',$first[1])){
			$r=securityScanRhs($pg,$st);
			if(is_array($r) && is_null($r[1])){$pg['mixed'][$r[0]]=1;}
		}
		$fe=securityScanForeachSubject($st);
		if(is_array($fe)){
			$tainted=false;
			foreach(securityScanVars($fe[0],false) as $v){
				if(securityScanIsSource($v[0],$v[2]) || isset($pg['taint'][$v[0]])){$tainted=true;break;}
			}
			if(!$tainted){
				foreach(array($fe[1],$fe[2]) as $lv){if(strlen($lv)){$pg['mixed'][$lv]=1;}}
			}
		}
	}
	foreach($pg['guarded'] as $g=>$x){unset($pg['mixed'][$g]);}
}

//---------- begin function securityScanSeverity
/**
* @describe final severity: guarded/mixed => low, via function parameter => one step down
* @return string
* @usage $sev=securityScanSeverity('high','param','',false);
*/
function securityScanSeverity($base,$level,$reason,$direct){
	if(strlen($reason)){return 'low';}
	if($direct){return $base;}
	if($level=='param'){
		$order=array('low','medium','high');
		$i=array_search($base,$order);
		return $order[max(0,$i-1)];
	}
	return $base;
}

//---------- begin function securityScanValueCut
/**
* @describe position of the first top-level , ) or ] in a code fragment, carrying bracket depth across calls
* @param text string
* @param depth int - by reference; start at 0
* @return int - offset, or -1 if the value continues past this fragment
* @usage $depth=0;$cut=securityScanValueCut($code,$depth);
*/
function securityScanValueCut($text,&$depth){
	$n=strlen($text);
	for($i=0;$i<$n;$i++){
		$ch=$text[$i];
		if($ch=='(' || $ch=='['){$depth++;}
		elseif($ch==')' || $ch==']'){
			if($depth==0){return $i;}
			$depth--;
		}
		elseif($ch==',' && $depth==0){return $i;}
	}
	return -1;
}

//---------- begin function securityScanSinks
/**
* @describe check one statement against the SQL, HTML and exec/include/redirect sinks
* @return void
* @usage securityScanSinks($pg,$stmt);
*/
function securityScanSinks(&$pg,$st){
	$p=securityPatterns();
	$segs=$st['segs'];
	$fi=$st['file'];
	$codetxt='';
	foreach($segs as $s){if($s[0]=='code'){$codetxt.=$s[1];}}
	$sqlctx=securityScanSqlContext($segs);
	//---- SQL: values of -where/-order/... keys
	$ranges=array();
	$cnt=count($segs);
	for($i=0;$i<$cnt;$i++){
		$k=$segs[$i][0];
		if(($k=='sq' || $k=='dq') && isset($p['sql_keys'][strtolower($segs[$i][1])]) && $i+1<$cnt
			&& $segs[$i+1][0]=='code' && preg_match('~^\s*=>~',$segs[$i+1][1],$am)){
			$arrow=$segs[$i+1];
			$tail=substr($arrow[1],strlen($am[0]));
			//the value runs to the first top-level , ) or ] (brackets inside it, like $_REQUEST['x'], are kept)
			$depth=0;
			$cut=securityScanValueCut($tail,$depth);
			$val=array(array('code',$cut<0?$tail:substr($tail,0,$cut),$arrow[2]+strlen($am[0])));
			for($j=$i+2;$cut<0 && $j<$cnt;$j++){
				$seg=$segs[$j];
				if($seg[0]=='code'){
					$cut=securityScanValueCut($seg[1],$depth);
					if($cut>=0){$val[]=array('code',substr($seg[1],0,$cut),$seg[2]);break;}
				}
				$val[]=$seg;
			}
			$ranges[]=array($val,false);
		}
	}
	//---- SQL: whole statement when it carries an SQL-looking literal (concatenated/interpolated vars only)
	if($sqlctx){$ranges[]=array($segs,true);}
	//---- SQL: executeSQL($sql) etc. with a tainted raw SQL-text variable
	if(!$sqlctx && preg_match($p['sql_exec_call'],$codetxt)){
		$codesegs=array();
		foreach($segs as $s){if($s[0]=='code'){$codesegs[]=$s;}}
		foreach(securityScanOccurrences($pg,$codesegs,'sql',false) as $o){
			//a superglobal passed whole as an opts array is fine (dashless keys)
			if(substr($o[1],0,2)=='$_' || substr($o[1],0,9)=='$PASSTHRU'){continue;}
			$name=substr($o[1],1);
			if(isset($pg['taint'][$name]) && preg_match($p['sql_var_name'],$name)){
				securityScanAdd($pg,securityScanSeverity('high',$o[3],$o[4],false),'sqli',$fi,$o[0],$o[1],$o[2],$o[4]);
			}
		}
	}
	$seen=array();
	foreach($ranges as $rg){
		foreach(securityScanOccurrences($pg,$rg[0],'sql',true,$rg[1]) as $o){
			if(isset($seen[$o[0]])){continue;}
			$seen[$o[0]]=1;
			securityScanAdd($pg,securityScanSeverity('high',$o[3],$o[4],$o[2]=='direct'),'sqli',$fi,$o[0],$o[1],$o[2],$o[4]);
		}
	}
	//---- HTML
	$firstcode='';
	foreach($segs as $s){if($s[0]=='code'){$firstcode=$s[1];break;}}
	$hrange=null;
	if($st['echo'] || preg_match($p['echo'],$firstcode)){$hrange=false;}
	elseif(!$sqlctx && securityScanHtmlContext($segs)){$hrange=true;}
	if(!is_null($hrange)){
		foreach(securityScanOccurrences($pg,$segs,'html',true,$hrange) as $o){
			$direct=$o[2]=='direct';
			securityScanAdd($pg,securityScanSeverity($direct?'high':'medium',$o[3],$o[4],$direct),'xss',$fi,$o[0],$o[1],$o[2],$o[4]);
		}
	}
	//---- exec / include / redirect
	$type='';
	$sev='';
	$hasbt=false;
	foreach($segs as $s){if($s[0]=='bt'){$hasbt=true;break;}}
	if($hasbt || preg_match($p['exec'],$codetxt)){$type='exec';$sev='high';}
	elseif(preg_match($p['include'],$codetxt)){$type='include';$sev='medium';}
	elseif(preg_match($p['header'],$codetxt)){
		foreach($segs as $s){
			if($s[0]!='code' && preg_match('~location\s*:~i',$s[1])){$type='redirect';$sev='low';break;}
		}
	}
	if(!strlen($type)){return;}
	foreach(securityScanOccurrences($pg,$segs,'any',false) as $o){
		securityScanAdd($pg,securityScanSeverity($sev,$o[3],$o[4],$o[2]=='direct'),$type,$fi,$o[0],$o[1],$o[2],$o[4]);
	}
}

//---------- begin function securityScanGroups
/**
* @describe run the scanner over groups of source texts. Each group is one record (page/template)
*	whose fields are analysed together.
* @param groups array - list of array('record'=>name,'files'=>array(array('label'=>,'text'=>,'meta'=>array())))
* @return array - findings sorted high->low, each array(severity,type,record,file,line,var,origin,snippet + meta keys)
* @usage $findings=securityScanGroups(securityLoadSiteSources());
*/
function securityScanGroups($groups){
	$p=securityPatterns();
	$findings=array();
	foreach($groups as $g){
		$pg=securityScanNewPage($g['record'],$g['files']);
		if(!count($pg['stmts'])){continue;}
		securityScanPropagate($pg);
		securityScanFindMixed($pg);
		foreach($pg['stmts'] as $st){securityScanSinks($pg,$st);}
		foreach($pg['findings'] as $f){$findings[]=$f;}
	}
	usort($findings,function($a,$b) use ($p){
		$s=$p['severities'][$b['severity']]-$p['severities'][$a['severity']];
		if($s!=0){return $s;}
		$c=strcmp($a['file'],$b['file']);
		if($c!=0){return $c;}
		return $a['line']-$b['line'];
	});
	return $findings;
}

//---------- begin function securityLoadSiteSources
/**
* @describe this site's page code from the database: _pages (body, controller, functions) and
*	_templates (body, controller, functions), one group per record
* @return array - groups for securityScanGroups
* @usage $groups=securityLoadSiteSources();
*/
function securityLoadSiteSources(){
	$groups=array();
	foreach(array('_pages','_templates') as $table){
		$fields=getDBFields($table);
		if(!is_array($fields)){continue;}
		$codefields=array_values(array_intersect(array('controller','functions','body'),$fields));
		if(!count($codefields)){continue;}
		$recs=getDBRecords(array(
			'-table'=>$table,
			'-fields'=>'_id,name,'.implode(',',$codefields),
			'-order'=>'name',
			'-nocache'=>1
		));
		if(!is_array($recs)){continue;}
		foreach($recs as $rec){
			$files=array();
			foreach($codefields as $field){
				if(!isset($rec[$field]) || !strlen($rec[$field]) || strpos($rec[$field],'<?')===false){continue;}
				$files[]=array(
					'label'=>"{$table}/{$rec['name']}.{$field}",
					'text'=>$rec[$field],
					'meta'=>array('table'=>$table,'_id'=>$rec['_id'],'name'=>$rec['name'],'field'=>$field)
				);
			}
			if(count($files)){$groups[]=array('record'=>"{$table}/{$rec['name']}",'files'=>$files);}
		}
	}
	return $groups;
}

//---------- begin function securityScanSite
/**
* @describe scan this site's _pages/_templates and return findings plus scan stats
* @return array - array('findings'=>..,'records'=>int,'seconds'=>float,'counts'=>array(high,medium,low))
* @usage $scan=securityScanSite();
*/
function securityScanSite(){
	$start=microtime(true);
	$groups=securityLoadSiteSources();
	$findings=securityScanGroups($groups);
	$counts=array('high'=>0,'medium'=>0,'low'=>0);
	foreach($findings as $f){$counts[$f['severity']]++;}
	return array(
		'findings'=>$findings,
		'records'=>count($groups),
		'seconds'=>round(microtime(true)-$start,2),
		'counts'=>$counts
	);
}

//---------- begin function securityTypeLabels
/**
* @describe display labels and one-line explanations for each finding type
* @return array
* @usage $labels=securityTypeLabels();
*/
function securityTypeLabels(){
	return array(
		'sqli'=>array('label'=>'SQL injection','desc'=>'Request data is built into SQL text, so a visitor can change the query.'),
		'xss'=>array('label'=>'Cross-site scripting','desc'=>'Request data is written into the page without escaping, so a crafted link can run script in a visitor\'s browser.'),
		'exec'=>array('label'=>'Code / file execution','desc'=>'Request data reaches eval, include, shell, file or unserialize calls.'),
		'include'=>array('label'=>'Page inclusion','desc'=>'Request data picks which page or function set is loaded.'),
		'redirect'=>array('label'=>'Open redirect','desc'=>'Request data controls a Location: redirect.'),
	);
}

//---------- begin function securitySeverityColor
/**
* @describe badge color for a severity
* @param severity string
* @return string
* @usage $color=securitySeverityColor('high');
*/
function securitySeverityColor($severity){
	switch($severity){
		case 'high':return '#c0392b';
		case 'medium':return '#d68910';
		default:return '#7f8c8d';
	}
}

//---------- begin function securityEditHref
/**
* @describe admin edit link for the record a finding is in
* @param f array - finding
* @return string
* @usage $href=securityEditHref($finding);
*/
function securityEditHref($f){
	if(empty($f['table']) || empty($f['_id'])){return '';}
	return '/php/admin.php?_menu=edit&_table_='.urlencode($f['table']).'&_id='.(int)$f['_id'];
}

//---------- begin function securityRenderResults
/**
* @describe the result panel: summary counts + tabs (High / Medium / Low / Fix with AI)
* @param scan array - from securityScanSite
* @return string HTML
* @usage <?=securityRenderResults($scan);?>
*/
function securityRenderResults($scan){
	$counts=$scan['counts'];
	$total=count($scan['findings']);
	$rtn='<style>'.PHP_EOL;
	$rtn.='.sec_results td{vertical-align:top;}'.PHP_EOL;
	$rtn.='.sec_tablewrap{overflow-x:auto;max-width:100%;}'.PHP_EOL;
	$rtn.='.sec_snip{display:block;margin:2px 0 0;white-space:pre-wrap;word-break:break-word;font-family:monospace;font-size:12px;background:#f7f8fa;border:1px solid #e6e8eb;border-radius:4px;padding:4px 6px;}'.PHP_EOL;
	$rtn.='.sec_badge{display:inline-block;color:#fff;border-radius:10px;padding:1px 9px;font-size:11px;font-weight:600;text-transform:uppercase;}'.PHP_EOL;
	$rtn.='.sec_stats{display:flex;gap:14px;flex-wrap:wrap;margin:8px 0 4px;}'.PHP_EOL;
	$rtn.='.sec_stat{border:1px solid #e6e8eb;border-radius:10px;padding:10px 18px;min-width:110px;background:#fff;}'.PHP_EOL;
	$rtn.='.sec_stat b{display:block;font-size:26px;line-height:1.1;}'.PHP_EOL;
	$rtn.='.sec_tabs{display:flex;flex-wrap:wrap;gap:4px;border-bottom:2px solid #e3e6ea;margin:16px 0 14px;}'.PHP_EOL;
	$rtn.='.sec_tabs a{padding:8px 14px;color:#4a5560;text-decoration:none;font-weight:600;cursor:pointer;white-space:nowrap;border-bottom:3px solid transparent;margin-bottom:-2px;}'.PHP_EOL;
	$rtn.='.sec_tabs a:hover{color:#1a5fb4;}'.PHP_EOL;
	$rtn.='.sec_tabs a.is-active,.sec_tabs a.active{color:#1a5fb4;border-bottom-color:#1a5fb4;}'.PHP_EOL;
	$rtn.='#security_ai_prompt{max-width:100%;max-height:460px;box-sizing:border-box;overflow-y:auto;white-space:pre-wrap;word-break:break-word;font-family:monospace;font-size:12px;line-height:1.5;border:1px solid #dbdbdb;border-radius:4px;padding:12px;background:#fff;color:#363636;}'.PHP_EOL;
	$rtn.='</style>'.PHP_EOL;
	$rtn.='<div class="sec_results">'.PHP_EOL;
	$rtn.='<div class="w_gray">Scanned '.(int)$scan['records'].' page/template record'.($scan['records']==1?'':'s').' in '.encodeHtml($scan['seconds']).'s.</div>'.PHP_EOL;
	$rtn.='<div class="sec_stats">'.PHP_EOL;
	foreach(array('high'=>'High','medium'=>'Medium','low'=>'Low (review)') as $sev=>$label){
		$rtn.='<div class="sec_stat"><b style="color:'.securitySeverityColor($sev).';">'.(int)$counts[$sev].'</b><span class="w_gray w_small">'.$label.'</span></div>'.PHP_EOL;
	}
	$rtn.='</div>'.PHP_EOL;
	if($total==0){
		$rtn.='<div class="w_success" style="padding:10px;"><span class="icon-mark"></span> No request data was found reaching SQL, HTML, or execution sinks in this site\'s page code.</div>'.PHP_EOL;
		$rtn.='</div>'.PHP_EOL;
		return $rtn;
	}
	$tabs=array(
		array('id'=>'sec_high','label'=>'High','sev'=>'high'),
		array('id'=>'sec_medium','label'=>'Medium','sev'=>'medium'),
		array('id'=>'sec_low','label'=>'Low','sev'=>'low'),
		array('id'=>'sec_ai','label'=>'Fix with AI','sev'=>''),
	);
	//open on the most severe non-empty tab
	$active='sec_ai';
	foreach($tabs as $t){
		if(strlen($t['sev']) && $counts[$t['sev']]>0){$active=$t['id'];break;}
	}
	$rtn.='<div class="sec_tabwrapper">'.PHP_EOL;
	$rtn.='<nav class="sec_tabs">'.PHP_EOL;
	foreach($tabs as $t){
		$pill='';
		if(strlen($t['sev'])){
			$pill=' <span class="sec_badge" style="background:'.securitySeverityColor($t['sev']).';">'.(int)$counts[$t['sev']].'</span>';
		}
		$onclick="var r=this.closest('.sec_tabwrapper');r.querySelectorAll('.sec_panel').forEach(function(p){p.style.display='none';});var t=r.querySelector('#".$t['id']."');if(t){t.style.display='';}return wacss.setActiveTab(this);";
		$rtn.='<a href="#" class="sec_tab'.($t['id']==$active?' is-active':'').'" onclick="'.$onclick.'">'.$t['label'].$pill.'</a>'.PHP_EOL;
	}
	$rtn.='</nav>'.PHP_EOL;
	foreach($tabs as $t){
		$style=$t['id']==$active?'':' style="display:none;"';
		$body=strlen($t['sev'])?securityRenderFindingsTable($scan['findings'],$t['sev']):securityRenderAIPanel($scan);
		$rtn.='<div class="sec_panel" id="'.$t['id'].'"'.$style.'>'.$body.'</div>'.PHP_EOL;
	}
	$rtn.='</div>'.PHP_EOL;
	$rtn.='</div>'.PHP_EOL;
	return $rtn;
}

//---------- begin function securityRenderFindingsTable
/**
* @describe table of findings for one severity
* @param findings array
* @param severity string
* @return string HTML
* @usage $html=securityRenderFindingsTable($findings,'high');
*/
function securityRenderFindingsTable($findings,$severity){
	$types=securityTypeLabels();
	$rows='';
	$n=0;
	foreach($findings as $f){
		if($f['severity']!=$severity){continue;}
		$n++;
		$href=securityEditHref($f);
		$where=encodeHtml($f['file']).':'.(int)$f['line'];
		if(strlen($href)){$where='<a class="w_link" href="'.encodeHtml($href).'" target="_blank" title="Edit this record">'.$where.'</a>';}
		$type=isset($types[$f['type']])?$types[$f['type']]:array('label'=>$f['type'],'desc'=>'');
		$origin=$f['origin']=='direct'?'used directly':'from '.$f['origin'];
		$rows.='<tr>';
		$rows.='<td style="white-space:nowrap;"><span title="'.encodeHtml($type['desc']).'">'.encodeHtml($type['label']).'</span></td>';
		$rows.='<td>'.$where.'<code class="sec_snip">'.encodeHtml($f['snippet']).'</code></td>';
		$rows.='<td><b>'.encodeHtml($f['var']).'</b><div class="w_gray w_small">'.encodeHtml($origin).'</div></td>';
		$rows.='</tr>'.PHP_EOL;
	}
	if($n==0){return '<div class="w_gray" style="padding:8px;">No '.encodeHtml($severity).' findings.</div>';}
	$intro=array(
		'high'=>'Request data reaches the sink directly or through a variable assigned straight from the request. Fix these first.',
		'medium'=>'Request data reaches the sink through a function parameter, or a request-derived variable is written into HTML.',
		'low'=>'Worth a look, not necessarily a bug: the variable is validated somewhere on the page, shares its name with non-request data, or the issue is minor (Host header, redirect). Confirm the validation covers the flagged line.',
	);
	$rtn='<div class="w_gray w_small" style="margin-bottom:6px;">'.$intro[$severity].'</div>'.PHP_EOL;
	$rtn.='<div class="sec_tablewrap"><table class="wacss_table is-striped is-sticky" style="width:100%;">'.PHP_EOL;
	$rtn.='<thead><tr><th>Issue</th><th>Where</th><th>Value</th></tr></thead><tbody>'.PHP_EOL.$rows.'</tbody></table></div>'.PHP_EOL;
	return $rtn;
}

//---------- begin function securityRenderAIPanel
/**
* @describe "Fix with AI" panel: copyable prompt for an AI assistant
* @param scan array
* @return string HTML
* @usage $html=securityRenderAIPanel($scan);
*/
function securityRenderAIPanel($scan){
	$prompt=securityAIPrompt($scan);
	$rtn='<div class="w_bigger w_bold w_gray w_padtop"><span class="icon-copy"></span> Fix with AI</div>'.PHP_EOL;
	$rtn.='<div class="w_small w_gray" style="margin-bottom:6px;">Copy this and paste it into Claude, ChatGPT, or any AI assistant. It lists every high and medium finding with its page, field, line and code, plus the WaSQL-specific way to fix each kind. Low findings are listed briefly for review.</div>'.PHP_EOL;
	$rtn.='<div style="position:relative;">'.PHP_EOL;
	$rtn.='	<button type="button" class="wacss_button is-small" style="position:absolute;top:6px;right:6px;z-index:2;" onclick="wacss.copy2Clipboard(document.getElementById(\'security_ai_prompt\').textContent,\'Copied &mdash; paste into your AI assistant\');return false;"><span class="icon-copy"></span> Copy</button>'.PHP_EOL;
	$rtn.='	<div id="security_ai_prompt">'.encodeHtml($prompt).'</div>'.PHP_EOL;
	$rtn.='</div>'.PHP_EOL;
	return $rtn;
}

//---------- begin function securityAIPrompt
/**
* @describe build the plain-text (markdown) AI prompt from scan findings
* @param scan array - from securityScanSite (findings + counts)
* @param site string - optional site name for the heading (defaults to this host)
* @return string
* @usage $prompt=securityAIPrompt($scan);
*/
function securityAIPrompt($scan,$site=''){
	if(!strlen($site)){$site=isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'this site';}
	$types=securityTypeLabels();
	$counts=$scan['counts'];
	$lines=array();
	$lines[]="# Security fixes for {$site} (WaSQL page code)";
	$lines[]='';
	$lines[]="A static scan of this WaSQL site's _pages and _templates code found {$counts['high']} high, {$counts['medium']} medium and {$counts['low']} low findings where request data (\$_REQUEST, \$_GET, \$_POST, \$_COOKIE, \$PASSTHRU, \$_SERVER headers) reaches SQL, HTML output, or code/file execution.";
	$lines[]='';
	$lines[]='Please fix every HIGH and MEDIUM finding below. For each one, show the corrected code for that record and field. Confirm the finding is real first: the scanner is heuristic, so say so if a finding is a false positive and explain why.';
	$lines[]='';
	$lines[]='## How to fix, WaSQL style';
	$lines[]='- WaSQL keeps page logic in the database: each _pages record has `controller`, `functions` and `body` (views) fields, and _templates records have `body`/`functions`. Change only those page fields. Never change WaSQL core files (php/*.php), which are shared by every site.';
	$lines[]='- SQL, numbers and ids: validate before use, e.g. `if(!isNum($id)){return;}`, or cast with `(int)`.';
	$lines[]='- SQL, strings: prefer dashless filter keys, which core escapes: `getDBRecords(["-table"=>"items","status"=>$status])`. When text must go into a -where or raw SQL, wrap it in `databaseEscapeString()` inside quotes.';
	$lines[]='- SQL, column/field names or sort order taken from the URL (e.g. `$PASSTHRU[3]` used as a field): allow-list them with `in_array($field, getDBFields($table))` or a fixed array. Escaping does not make an identifier safe.';
	$lines[]='- HTML output: wrap request-derived values in `encodeHtml()`, e.g. `<?=encodeHtml($_REQUEST[\'q\']);?'.'>`, including inside attributes (value="", data-*="", href="").';
	$lines[]='- include/eval/file functions: never pass request data. Map the input to an allow-listed value first.';
	$lines[]='- Redirects: only redirect to relative paths or an allow-listed host.';
	$lines[]='- Return errors with WaSQL conventions: a DB write returns the new id or an error string, so test it with `isNum()`.';
	$order=array('high'=>'HIGH','medium'=>'MEDIUM');
	foreach($order as $sev=>$label){
		$rows=array();
		foreach($scan['findings'] as $f){if($f['severity']==$sev){$rows[]=$f;}}
		if(!count($rows)){continue;}
		$lines[]='';
		$lines[]="## {$label} findings (".count($rows).')';
		$current='';
		foreach($rows as $f){
			if($f['record']!==$current){
				$current=$f['record'];
				$lines[]='';
				$lines[]="### {$current}";
			}
			$tlabel=isset($types[$f['type']])?$types[$f['type']]['label']:$f['type'];
			$from=$f['origin']=='direct'?'used directly':'from '.$f['origin'];
			$lines[]="- {$tlabel} - {$f['file']} line {$f['line']}: {$f['var']} ({$from})";
			$lines[]='  `'.str_replace('`',"'",$f['snippet']).'`';
		}
	}
	$low=array();
	foreach($scan['findings'] as $f){if($f['severity']=='low'){$low[]=$f;}}
	if(count($low)){
		$lines[]='';
		$lines[]='## LOW findings - review only ('.count($low).')';
		$lines[]='These usually have validation elsewhere on the page or are minor. Check that the validation really covers each line, and fix any that are not covered.';
		$max=60;
		foreach(array_slice($low,0,$max) as $f){
			$tlabel=isset($types[$f['type']])?$types[$f['type']]['label']:$f['type'];
			$lines[]="- {$tlabel} - {$f['file']} line {$f['line']}: {$f['var']}";
		}
		if(count($low)>$max){$lines[]='- ...and '.(count($low)-$max).' more (see the Low tab).';}
	}
	return implode("\n",$lines);
}
