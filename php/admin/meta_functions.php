<?php
function metaShowList(){
	$opts=array(
		'-table'=>'_pages',
		'-action'=>'/php/admin.php',
		'_menu'=>'meta',
		'-listfields'=>'_id,name,_template,meta_image,title,meta_description,meta_robots,google_id',
		'-where'=>'_template != 1',
		'-order'=>'_template,name',
		'-tableclass'=>'table bordered is-bordered striped is-striped',
		'-editfields'=>'meta_image,title,meta_description,meta_robots,google_id',
		'-sorting'=>1,
		'title_displayname'=>'Title <span class="icon-info-circled" data-tooltip="id:title_help"></span> <div id="title_help" style="display:none">'.metaHelp('title').'</div>',
		'meta_description_displayname'=>'Meta Description <span class="icon-info-circled" data-tooltip="id:meta_description_help"></span> <div id="meta_description_help" style="display:none">'.metaHelp('meta_description').'</div>',
		'meta_robots_displayname'=>'Meta Robots <span class="icon-info-circled" data-tooltip="id:meta_robots_help"></span> <div id="meta_robots_help" style="display:none">'.metaHelp('meta_robots').'</div>',
		'meta_image_displayname'=>'Meta Image <span class="icon-info-circled" data-tooltip="id:meta_image_help"></span> <div id="meta_image_help" style="display:none">'.metaHelp('meta_image').'</div>',
		'google_id_displayname'=>'Google ID <span class="icon-info-circled" data-tooltip="id:google_id_help"></span> <div id="google_id_help" style="display:none">'.metaHelp('google_id').'</div>'
	);
	return databaseListRecords($opts);
}
function metaHelp($tag){
	switch(strtolower($tag)){
		case 'title':
			return <<< ENDOFTEXT
			<div class="w_big w_bold">Key Points to write a good Title tag:</div>
			<ul>
				<li>Add “modifiers” to your title tag (How to |The current year | Review |Best | Tips | Top |Find | Buy | Easy)</li>
				<li>Embed long tail keywords in title tags</li>
				<li>Add numbers to your title (9 Important HTML tags for your website to improve your SEO)</li>
				<li>Start your title tag with your main targeted keyword</li>
				<li>Don’t stuff your keywords</li>
				<li>Every page should have a unique title tag</li>
			</ul>
ENDOFTEXT;
		break;
		case 'meta_description':
			return <<< ENDOFTEXT
			<div class="w_big w_bold">Key Points to write a good Meta Description tag:</div>
			<ul>
				<li>Don’t put emphasis on the number of characters, as Google might pull Meta description text from your content based on a user’s query.</li>
				<li>Do not add duplicate Meta Descriptions</li>
				<li>Add clear Call-to-action (CTA) in your descriptions like Apply today, Check-out, Contact us today etc. See these CTA keywords for marketing campaigns</li>
				<li>Add your targeted keywords in descriptions</li>
				<li>Strategically provide solutions to a problem</li>
				<li>Write for your users and encourage them to click with specific and relevant content</li>
				<li>Add any discounts or offers you’ve going on</li>
				<li>Show empathy while writing your Meta Descriptions</li>
			</ul>
ENDOFTEXT;
		break;
		case 'meta_robots':
			return <<< ENDOFTEXT
			<div class="w_big w_bold">The Robots Meta tag has four main values for search engine crawlers:</div>
			<ul>
				<li>FOLLOW –The search engine crawler will follow all the links in that web page</li>
				<li>INDEX –The search engine crawler will index the whole web page</li>
				<li>NOFOLLOW – The search engine crawler will NOT follow the page and any links in that web page</li>
				<li>NOINDEX – The search engine crawler will NOT index that web page</li>
			</ul>
ENDOFTEXT;
		break;
		case 'meta_image':
			return <<< ENDOFTEXT
			<div class="w_big w_bold">Used For Social Media Meta Tags (Open Graph and Twitter Cards)</div>
ENDOFTEXT;
		break;
		case 'google_id':
			return <<< ENDOFTEXT
			<div class="w_big w_bold">Used in <span class="icon-size-google"></span> Google analyitcs</div>
ENDOFTEXT;
		break;
	}
}
?>