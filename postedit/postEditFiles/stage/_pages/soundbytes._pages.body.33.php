<view:post>
<div class="row post">
	<div class="col-sm-1 text-right post_info">
		<div class="post_date"><?=date('M\<\b\r \/\>j',strtotime($post['date_begin']));?></div>
		<div class="comment_count w_pointer" title="comments" onclick="pageComments(<?=$post['_id'];?>);"><div class="icon-comment w_white"></div><?=$post['comments_count'];?></div>
	</div>
	<div class="col-sm-11 post_data">
		<div class="text-right">
		<a onclick="return w_shareButton(this.href);" title="Share on Facebook" href="http://www.facebook.com/sharer.php?u=http://<?=$_SERVER['SERVER_NAME'];?>/soundbytes/<?=$post['permalink'];?>"><span class="icon-site-facebook sharepost"></span></a>
		<a onclick="return w_shareButton(this.href);" title="Share on Twitter" href="http://twitter.com/share?url=http://<?=$_SERVER['SERVER_NAME'];?>/soundbytes/<?=$post['permalink'];?>"><span class="icon-site-twitter sharepost"></span></a>
		<a onclick="return w_shareButton(this.href);" title="Share on LinkedIn" href="http://www.linkedin.com/shareArticle?mini-true&url=http://<?=$_SERVER['SERVER_NAME'];?>/soundbytes/<?=$post['permalink'];?>"><span class="icon-site-linkedin sharepost"></span></a>
		<view:edit_post>
			<a href="#" onclick="return ajaxGet('/t/1/soundbytes/addeditblog','centerpop',{id:<?=$post['_id'];?>});" class="w_link"><span class="icon-edit sharepost"></span></a>
		</view:edit_post>
		<?=renderViewIf(isAdmin(),'edit_post',$post,'post');?>
		</div>
		<h2 class="post_title"><a href="/soundbytes/<?=$post['permalink'];?>"><?=$post['title'];?></a></h2>
		<div class="post_content">
			<view:content>
				<?=$post['body'];?>
			</view:content>
			<view:summary>
			<?=$post['summary'];?>
			<view:more>
				<div id="postmore<?=$post['_id'];?>" class="teal w_link w_pointer s_small" onclick="postExpand(<?=$post['_id'];?>);">Read More ...</div>
				<span id="postmore_body<?=$post['_id'];?>" style="display:none">
					<?=$post['more'];?>
					<span id="postcollapse<?=$post['_id'];?>" class="teal w_link w_pointer s_small" style="display:block;"  onclick="postCollapse(<?=$post['_id'];?>);"><span class="icon-dir-up"></span> collapse</span>
				</span>
			</view:more>
			<?=renderViewIf(strlen($post['more']),'more',$post,'post');?>
			</view:summary>
			<?=renderViewIfElse(isset($post['single']),'content','summary',$post,'post');?>
			<a name="comments<?=$post['_id'];?>"></a>
			<div id="comments<?=$post['_id'];?>"></div>
		</div>
	</div>
</div>
</view:post>

<view:addeditblog>
	<div class="w_centerpop_title">Blog post</div>
	<div class="w_centerpop_content" style="max-width:900px;">
		<?=pageAddEditBlogPost($_REQUEST['id']);?>
	</div>
</view:addeditblog>

<view:comment>
<div class="comment">
	<div class="comment_info">
		<div class="icon-user"></div>
		<div class="comment_author"></div>
		<div class="comment_date"><?=date('F j, Y \a\t g:i a',strtotime($comment['_cdate']));?></div>
	</div>
	<div class="comment_data">
		<div class="comment_content">
			<?=$comment['summary'];?>
			<view:comment_more>
				<div id="commentmore<?=$comment['_id'];?>" class="teal w_link w_pointer s_small" onclick="commentExpand(<?=$comment['_id'];?>);">Read More ...</div>
				<span id="commentmore_body<?=$comment['_id'];?>" style="display:none">
					<?=$comment['more'];?>
					<span id="commentcollapse<?=$comment['_id'];?>" style="display:block;" class="teal w_link w_pointer s_small" onclick="commentCollapse(<?=$comment['_id'];?>);"><span class="icon-dir-up"></span> collapse</span>
				</span>
			</view:comment_more>
			<?=renderViewIf(strlen($comment['more']),'comment_more',$comment,'comment');?>
		</div>
	</div>
</div>
</view:comment>

<view:comments>
<br clear="both" /><hr>
<?=renderEach('comment',$comments,'comment');?>
<h3>Leave a comment</h3>
<?=blogCommentForm($postid);?>
</view:comments>

<view:default>
	<div class="row">
		<div class="col-sm-12">
			<view:add_new>
				<a href="#" onclick="return ajaxGet('/t/1/soundbytes/addeditblog','centerpop');" class="w_link w_right"><span class="icon-plus sharepost"></span></a>
			</view:add_new>
			<?=renderViewIf(isAdmin(),'add_new');?>
			<h1 class="text-center blue"><a href="/soundbytes">Sound Bytes</a></h1>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<div class="posts">
				<?=renderEach('post',$posts,'post');?>
			</div>
		</div>
	</div>
</view:default>

<view:comment_posted>
<div class="w_centerpop_title">Comment Posted</div>
<div class="w_centerpop_content">
	<h3>Thank you for your comment!</h3>
	<?=buildOnLoad("document.commentform.reset();");?>
</div>
</view:comment_posted>
