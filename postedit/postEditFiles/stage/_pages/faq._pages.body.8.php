<view:faq>
<div class="faq">
	<div class="question"><?=$faq['category'];?>: <?=$faq['question'];?></div>
	<div class="answer"><?=$faq['answer'];?></div>
</div>
</view:faq>

<view:default>
<h1>FAQ</h1>
This is a short list of our most frequently asked questions. 
For more information about Skillsai, or if you need support, please <a href="/contact">contact</a> us.
<?=renderEach('faq',$faqs,'faq');?>
</view:default>
