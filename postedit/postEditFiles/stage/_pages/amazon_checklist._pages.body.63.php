<view:default>
<div class="row">
	<div class="col-sm-10 col-sm-offset-1">
		<h3 class="blue" style="padding:0px;margin:0px;"><span class="icon-mark w_grey"></span> Checklist for Amazon Echo Family Skills</h3>
	</div>
</div>
<div class="row">
	<div class="col-sm-6 col-sm-offset-1">
		<form method="post" action="/t/1/amazon_checklist/test" onsubmit="return ajaxSubmitForm(this,'test_results');">
			<div><textarea name="schema" class="form-control" rows="5" required="1" placeholder="paste schema here"></textarea></div>
			<div><textarea name="utterances" class="form-control" rows="5" required="1" placeholder="paste sample utterances here"></textarea></div>
			<button class="btn btn-default" type="submit">Run Tests</button>
		</form>
  		<div>
			<input data-group="addedit_checklist_group" id="schema_test1" style="display:none;" data-type="checkbox" type="checkbox" name="checklist[]" value="1">
			<label for="schema_test1" class="icon-mark "></label>
			<label for="schema_test1" style="padding-left:10px;"> All intents must have sample utterances</label>

		</div>
		<div>
			<input data-group="addedit_checklist_group" id="utterance_test1" style="display:none;" data-type="checkbox" type="checkbox" name="checklist[]" value="1">
			<label for="utterance_test1" class="icon-mark "></label>
			<label for="utterance_test1" style="padding-left:10px;"> Each slot should be used only once within a sample utterance</label>
		</div>
	</div>
	<div class="col-sm-4">
		<div class="w_bold w_big">Test Results:</div>
		<div id="test_results" style="max-height:300px;overflow:auto;">
		</div>
	</div>
</div>
</view:default>

<view:tests>
	<view:schema_test1_pass>
		<div class="w_bold w_success w_bigger">Schema Test Passed</div>
		<?=buildOnLoad("document.getElementById('schema_test1').checked=true;");?>
	</view:schema_test1_pass>
	<view:schema_test1_fail>
		<div class="w_bold w_danger w_bigger">Schema Test Failed</div>
		<view:error><div><?=$error;?></div></view:error>
		<?=renderEach('error',$errors,'error');?>
	</view:schema_test1_fail>
	<?=renderViewIfElse(count($errors['schema_test1']),'schema_test1_fail','schema_test1_pass',$errors['schema_test1'],'errors');?>


	<view:utterance_test1_pass>
		<div class="w_bold w_success w_bigger">Utterance Test Passed</div>
		<?=buildOnLoad("document.getElementById('utterance_test1').checked=true;");?>
	</view:utterance_test1_pass>
	<view:utterance_test1_fail>
		<div class="w_bold w_danger w_bigger">Utterance Test Failed</div>
		<view:error><div><?=$error;?></div></view:error>
		<?=renderEach('error',$errors,'error');?>
	</view:utterance_test1_fail>
	<?=renderViewIfElse(count($errors['utterance_test1']),'utterance_test1_fail','utterance_test1_pass',$errors['utterance_test1'],'errors');?>


</view:tests>
