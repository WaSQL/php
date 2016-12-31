<?php
loadExtrasJs('nicedit');
if(isset($_REQUEST['passthru'][0])){$_REQUEST['func']=$_REQUEST['passthru'][0];}
switch(strtolower($_REQUEST['func'])){
	case 'addeditblog':
		setView('addeditblog',1);
		return;
	break;
	case 'comment':
		//add comment to the wordpress database
		$postid=addslashes($_REQUEST['passthru'][1]);
		if(strlen($_REQUEST['firstname']) || strlen($_REQUEST['lastname'])){
        	//spambot
        	echo '<h1>DENIED - GO AWAY!</h1>';
        	exit;
		}
		$id=blogAddComment($postid,$_REQUEST);
		setView('comment_posted',1);
		return;
	break;
	case 'byte':
		//add comment to the wordpress database

		//echo printValue($posts);exit;
		setView('default');
		return;
	break;
	case 'comments':
		$postid=addslashes($_REQUEST['passthru'][1]);
		$comments=blogComments($postid);
		setView('comments',1);
		return;
	break;
	case 'rss':
		$posts=blogPosts();
		$recs=array();
		foreach($posts as $post){
            $recs[]=array(
				'title'=>$post['title'],
				'link'=>"http://www.skillsai.com/soundbytes/{$post['permalink']}",
				'description'=>removeHtml($post['body']),
				'pubdate'=>$post['date_begin'],
				);
		}
		$params=array(
			'title'=>"Sound Bytes by Skillsai",
			'link'=>"http://www.skillsai.com/soundbytes",
			'description'=>"Sound Bytes is where you'll find posts on a wide variety of topics. From news and info on the Amazon Echo&trade; to other areas we're interested in including Small Business, Education, Technology and Artificial Intelligence, Sound Bytes is an eclectic mix of content. We'll discuss our latest product releases, share our technical and business expertise, and tons of other cool stuff like our monthly contest to win your own Amazon Echo! We hope you'll subscribe and share your thoughts with us as well. ",
			'pubdate'=>$posts[0]['date_begin'],
		);
		$rss=arrays2RSS($recs,$params);
        echo $rss;
        exit;
	break;
	default:
  		if(strlen($_REQUEST['func'])){
			$post_name=addslashes($_REQUEST['func']);
			$posts=blogPosts($post_name);
		}
		else{
			$posts=blogPosts();
		}
		//echo printValue($posts);exit;
		setView('default');
	break;
}
?>
