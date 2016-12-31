<?php
function pageAddEditBlogPost($id=0){
	if(!isNum($id)){$id=0;}
	$opts=array(
		'-table'=>'blogs',
		'body_behavior'=>'richtext'
	);
	if($id > 0){$opts['_id']=$id;}
	return addEditDBForm($opts);
}

function blogAddComment($postid,$params){
	foreach($params as $key=>$val){
    	$params[$key]=str_replace("'","''",addslashes($val));
	}
	$cdate=date('Y-m-d H:i:s');
	$gdate=gmdate('Y-m-d H:i:s');
	$query=<<<ENDOFQUERY
INSERT INTO skillsai_blog_live.DatK2I_comments (
	comment_post_ID
	,comment_author
	,comment_author_email
	,comment_author_url
	,comment_author_ip
	,comment_date
	,comment_date_gmt
	,comment_content
	,comment_agent
	,comment_approved
	)
values (
	{$postid}
	,'{$params['comment_author']}'
	,'{$params['comment_author_email']}'
	,'{$params['comment_author_url']}'
	,'{$_SERVER['REMOTE_ADDR']}'
	,'{$cdate}'
	,'{$gdate}'
	,'{$params['comment_content']}'
	,'{$_SERVER['HTTP_USER_AGENT']}'
	,0
)
ENDOFQUERY;
//echo $query;exit;
	$ok=executeSQL($query);
	return;
}
function blogDatabaseName(){
	return 'skillsai_blog_live';
}
function blogTablePrefix(){
	return 'DatK2I';
}

function blogPosts($post_name=''){
	$ok=executeSQL("update blogs set date_begin=_cdate where date_begin is null");
	$opts=array(
		'-table'=>'blogs',
		'-where'=>"date(date_begin) <= date(now()) and (date_end is null or date(date_end) >= date(now()))",
		'-order'=>'date_begin desc',
		'-limit'=>20
	);
	if(isDBStage()){
    	$opts['-where'].=" and status in (1,2)";
	}
	else{
    	$opts['-where'].=" and status=2";
	}
	if(strlen($post_name)){
    	$opts['-where'].=" and permalink='{$post_name}'";
	}
	//echo printValue($opts);exit;
	$recs=getDBRecords($opts);
	if(!is_array($recs)){return array();}
	//echo printValue($recs);exit;
	foreach($recs as $i=>$rec){
		$recs[$i]['comments_count']=getDBCount(array('-table'=>'blogs_comments','blog_id'=>$rec['_id']));
		$recs[$i]['title']=trim($recs[$i]['title']);
		$recs[$i]['title']=fixMicrosoft($recs[$i]['title']);
		$recs[$i]['body']=fixMicrosoft($recs[$i]['body']);
		//remove tags
		$tags=array();
		preg_match_all('/\<(a|table)(.*?)\>(.+?)\<\/\1\>/ism',$recs[$i]['body'],$tag,PREG_PATTERN_ORDER);
		$tagcnt=count($tag[1]);
        for($x=0;$x<$tagcnt;++$x){
			$tags[$x] = $tag[0][$x];
			$replace_str="[[tag{$x}]]";
			$recs[$i]['body']=str_replace($tags[$x],$replace_str,$recs[$i]['body']);
        }
		//fix non-secure urls
		$recs[$i]['body']=str_replace('src="http://www.skillsai.com','src="',$recs[$i]['body']);
		$recs[$i]['body']=preg_replace('/[\r\n]+/','<p>',$recs[$i]['body']);
		//look for read more...
		list($recs[$i]['summary'],$recs[$i]['more'])=preg_split('/read more\.\.\./i',$recs[$i]['body'],2);
		if(strlen($post_name)){$recs[$i]['single']=1;}
		foreach($tags as $x=>$tag){
			$replace_str="[[tag{$x}]]";
			$recs[$i]['body']=str_replace($replace_str,$tags[$x],$recs[$i]['body']);
			$recs[$i]['summary']=str_replace($replace_str,$tags[$x],$recs[$i]['summary']);
			$recs[$i]['more']=str_replace($replace_str,$tags[$x],$recs[$i]['more']);
        }
	}
 	//echo printValue($recs);exit;
	return $recs;
}
function blogCommentForm($blog_id,$parent_id=0){
	$fields=<<<ENDOFFIELDS
<label for="">Comment</label>
<div>[body]</div>
<label for="">Email</label>
<div>[email]</div>
<label for="">Website</label>
<div>[website]</div>
ENDOFFIELDS;
	$opts=array(
		'-table'	=> 'blogs_comments',
		'-fields'	=> $fields,
		'blog_id'	=> $blog_id,
		'parent_id'	=> $parent_id,
		'-honeypot' => 'firstname',
		'email_style'=>'width:100%',
		'body_style'=>'width:100%',
		'website_style'=>'width:100%',
		'-save'	=> 'Submit',
		'-save_align'=>'right',
		'-onsubmit'	=> "return ajaxSubmitForm(this,'centerpop');",
		'-action'	=> "/t/1/soundbytes/comment/{$blog_id}",
		'-name'		=> 'commentsform'.$blog_id,
		'-focus'	=> 'body'
	);
	//return printValue($opts);
	return addEditDBForm($opts);
}
function blogComments($id){
	$opts=array(
		'-table'=>'blogs_comments',
		'blog_id'=>$id,
		'-order'=>'_cdate desc',
		'-limit'=>50
	);
	$recs=getDBRecords($opts);
	if(!is_array($recs)){return array();}
	//echo printValue($recs);exit;
	foreach($recs as $i=>$rec){
		$content=fixMicrosoft($recs[$i]['body']);

		//remmove images
		$imgs=array();
		preg_match_all('/\<img(.+?)\>/ism',$content,$img,PREG_PATTERN_ORDER);
		$cnt=count($img[1]);
		//save views so they can be used by renderEach and renderView;
        for($x=0;$x<$cnt;++$x){
			$imgs[$x] = $img[0][$x];
			$replace_str="[[img{$x}]]";
			$content=str_replace($imgs[$x],$replace_str,$content);
        }
        //remove tags
		$tags=array();
		preg_match_all('/\<(a)(.+?)\>(.+?)\<\/a\>/ism',$content,$tag,PREG_PATTERN_ORDER);
		$tagcnt=count($tag[1]);
		//save views so they can be used by renderEach and renderView;
        for($x=0;$x<$cnt;++$x){
			$tags[$x] = $tag[0][$x];
			$replace_str="[[tag{$x}]]";
			$content=str_replace($tags[$x],$replace_str,$content);
        }
        $content=removeHtml($content);
		$recs[$i]['summary']=truncateWords($content,250,0);
		if(sha1($recs[$i]['summary']) != sha1($content)){
        	$recs[$i]['more']=str_ireplace($recs[$i]['summary'],'',$content);
		}
		else{
        	$recs[$i]['more']='';
		}
		$recs[$i]['summary']=nl2br($recs[$i]['summary']);
		$recs[$i]['more']=nl2br($recs[$i]['more']);
		foreach($imgs as $x=>$img){
			$replace_str="[[img{$x}]]";
			$recs[$i]['summary']=str_replace($replace_str,$imgs[$x],$recs[$i]['summary']);
			$recs[$i]['more']=str_replace($replace_str,$imgs[$x],$recs[$i]['more']);
        }
        foreach($tags as $x=>$tag){
			$replace_str="[[tag{$x}]]";
			$recs[$i]['summary']=str_replace($replace_str,$tags[$x],$recs[$i]['summary']);
			$recs[$i]['more']=str_replace($replace_str,$tags[$x],$recs[$i]['more']);
        }
	}
	//echo printValue($recs);exit;
	return $recs;
}
?>
