<view:default>
<div class="row">
	<div class="col-sm-12 w_lgreyback w_roundsmall">
		<div class="row">
			<div class="col-sm-2 w_pad">
				<div class="w_big w_bold">Reports</div>
				<div><a href="#" onclick="return pageShowReport('wam');" class="text"><span class="icon-website"></span> Website Availability</a></div>
			</div>
			<div class="col-sm-10 w_pad" id="data" style="border-left:1px solid #cae3dd;">
			</div>
		</div>
	</div>
</div>
</view:default>

<view:filters>
	<form name="filterform" method="post" class="form-inline" action="/t/1/dashboard/report/<?=$report['name'];?>" onsubmit="return ajaxSubmitForm(this,'data');">
		<input type="hidden" name="drilldown" value="" />
		<div class="form-group">
			<label for="date_begin" class="sr-only">Begin</label>
			<?=buildFormDate('date_begin',array('placeholder'=>'Begin Date','id'=>'date_begin'));?>
		</div>
		<div class="form-group">
			<label for="date_end" class="sr-only">End</label>
			<?=buildFormDate('date_end',array('placeholder'=>'End Date','id'=>'date_end'));?>
		</div>
		<div class="form-group">
			<label for="type" class="sr-only">Hour</label>
			<?=buildFormSelect('hour',range(0,23),array('message'=>'--hour--'));?>
		</div>
		<div class="form-group">
			<label for="type" class="sr-only">Type</label>
			<?=buildFormSelect('type',array('time'=>'Time','date'=>'Date','minute'=>'Minute'));?>
		</div>
		<button type="submit" class="btn btn-primary" onclick="document.filterform.drilldown.value='';">Go</button>
	</form>
</view:filters>
<view:report_wam>
	<?=renderView('filters',array('name'=>'wam'),'report');?>
	<div class="w_bold w_big w_padtop">Website Availability Monitor (WAM)</div>
	<canvas id="wam_canvas"></canvas>
	<div id="wam_data" style="display:none"><?=$json_data;?></div>
	<?=buildOnLoad("pageDrawChart('wam');");?>
	<div id="debug"><?=printValue($_REQUEST);?></div>
</view:report_wam>
