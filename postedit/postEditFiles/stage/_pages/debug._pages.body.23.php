<view:default>
<div class="row"><div class="col-sm-12"><h3>Debugger</h3></div></div>
<div class="row">
	<div class="col-sm-1"></div>
	<div class="col-sm-10">
		<form method="post" action="/t/1/debug/json" onsubmit="return ajaxSubmitForm(this,'result');">
		<textarea class="form-control" autofocus="true" name="json" placeholder="json string here">
		</textarea>
		<div><button class="btn btn-primary" type="submit">Decode Json</button></div>
		</form>
		<div id="result"></div>
	</div>
</div>
</view:default>
