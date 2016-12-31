<view:default>
<div class="row w_padtop">
	<div class="col-sm-1"></div>
	<div class="col-sm-10">
		<div class="well" style="min-height:300px;">
			<div class="row">
				<div class="col-sm-12"><h1>Audible Art</h1></div>
			</div>
			<div class="row">
				<div class="col-sm-4">
					<h2>Colors</h2>
					<div id="colors" class="table-responsive" style="max-height:500px;overflow:auto;">
					<table>
					<view:color>
						<tr><td><?=$color['name'];?></td><td><div style="border:1px solid #7d7d7d;border-radius:6px;width:100px;height:20px;background-color:<?=$color['hex'];?>;"></div></td></tr>
					</view:color>
					<?=renderEach('color',$colors,'color');?>
					</table>
					</div>
				</div>
				<div class="col-sm-2">
					<div id="xy" style="font-size:30px;margin-top:50px;"></div>
				</div>
				<div class="col-sm-6">
					<h2>Current Canvas</h2>
					<div>
					<b class="pull-right">512</b>
					<b>0</b>
					</div>
					<div class="grid-div" id="current_canvas" data-behavior="ajax" data-timer="60" data-url="/audible_art">
						 <img src="/skills/audible_art/<?=$yearweek;?>_large.png?t=<?=time();?>" onload="showMapPoints();" id="gridimg" class="img-responsive" style="border:1px solid #000;" width="512" height="512" alt="grid" usemap="#gridmap">
					</div>
					<div>
					<b class="pull-right">512</b>
					<b>512</b>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</view:default>

<view:current_canvas>
	<img src="/skills/audible_art/<?=$yearweek;?>_large.png?t=<?=time();?>" onload="showMapPoints();" id="gridimg" class="img-responsive" style="border:1px solid #000;" width="512" height="512" alt="grid" usemap="#gridmap">
	<div id="stats_refresh" style="display:none;">

	</div>
</view:current_canvas>
