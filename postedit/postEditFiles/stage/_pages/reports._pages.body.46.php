<view:default>
<div class="row" style="padding:0px;">
	<nav class="navbar navbar-default" style="margin:4px 0 4px 0;padding:0px;">
    	<div class="navbar-header">
        	<button type="button" class="navbar-toggle collapsed" style="padding:0px;border:0px;" data-toggle="collapse" data-target="#topnavbar" aria-expanded="false" aria-controls="navbar">
            	<span class="icon-menu w_huge"></span>
          	</button>
          	<a class="navbar-brand w_nowrap visible-xs" href="/">Report Dashboard</a>
    	</div>
        <div id="topnavbar" class="navbar-collapse collapse" style="padding:0px;margin:0px;width:100%;">
        	<ul class="nav navbar-nav" id="usertabs">
        		<?=renderView('usertabs',$usertabs,'usertabs');?>
	    	</ul>
	    	<ul class="nav navbar-nav navbar-right">
		    	<li>
		    		<form method="post" action="/t/1/account/nav/tabform" style="padding:0px;margin:0px;width:220px;padding-top:8px;" name="tabform" onsubmit="return ajaxSubmitForm(this,'usertabs');" >
			  			<input type="hidden" name="tabid" value="" />
			  			<input type="hidden" name="func" value="save" />
			  			<input type="hidden" name="setprocessing" value="0" />
			  			<div class="w_right">
			  				<div class="text-right" style="margin-left:15px;"><a href="#" id="reportmanagelink" data-tab_id="<?=$_REQUEST['tab_id'];?>" onclick="return reportManage(this.getAttribute('data-tab_id'));"><span class="icon-gear w_grey" style="font-size:24px"></span></a></div>
							<div id="reportmanage" style="display:none;position:absolute;right:0px;"></div>
						</div>
			  			<div class="input-group">
							<input type="text" class="form-control" required="true" name="tabname" id="tabname" placeholder="tab name" />
							<span class="input-group-btn" id="navbuttons">
								<button type="submit" class="btn btn-default"><span class="icon-save" id="tabformsubmiticon"></span></button>
								<button type="button" class="btn btn-default" style="padding-left:2px;padding-right:2px;border-top-right-radius:4px;border-bottom-right-radius:4px;" onclick="return showHide('navoptions');"><span class="icon-dir-down"></span></button>
								<ul class="dropdown-menu" id="navoptions" style="display:none;position:absolute;right:0px;text-align:left;background:#FFF;z-index:99999;">
								    <li class="save"><a href="#" onclick="return reportTabFormAction('save');"><span class="icon-save"></span> ADD</a></li>
								    <li class="edit" style="display:none"><a href="#" onclick="return reportTabFormAction('edit');"><span class="icon-edit w_grey"></span> EDIT</a></li>
								    <li class="delete" style="display:none"><a href="#" onclick="return reportTabFormAction('delete');"><span class="icon-cancel w_danger"></span> DELETE</a></li>
								    <li class="clear"><a href="#" onclick="return reportTabFormAction('clear');"><span class="icon-erase w_warning"></span> CLEAR FORM</a></li>
								</ul>
							</span>
						</div>
					</form>
		    	</li>
            </ul>
        </div>
	</nav>
  	<div id="content">
		<?=renderView('tiles',$recs,'recs');?>
	</div>
	<div style="display:none"><div id="nulldiv"></div></div>
</div>
</view:default>

<view:reportmanage>
	<div class="well">
	<view:report>
		<div class="text-left"><div class="report btn btn-default" onclick="reportAddReportToTab(<?=$rec['_id'];?>);"><?=$rec['name'];?> <span class="icon-plus w_success"></span></div></div>
	</view:report>
	<?=renderEach('report',$recs,'rec');?>
	<div class="text-right"><a href="#" class="w_link w_block" onclick="return hideId('reportmanage');"><span class="icon-cancel w_danger w_small"></span></a></div>
	</div>
</view:reportmanage>

<view:login>
	<?=userLoginForm(array('-action'=>'/account'));?>
</view:login>

<view:tiles>
	<div id="tiles" class="tiles drop" data-tab_id="<?=$_REQUEST['tab_id'];?>">
	<view:tile>
	<div class="tile tile-double" draggable="true" data-report_name="<?=$rec['name'];?>" data-report_id="<?=$rec['_id'];?>" data-tab_id="<?=$_REQUEST['tab_id'];?>">
		<?=pageLoadReport($rec);?>
	</div>
	</view:tile>
	<?=renderEach('tile',$recs,'rec');?>
	<?if(!isMobileDevice()){echo buildOnLoad("reportInitTileSort();");}?>
	</div>
</view:tiles>

<view:usertabs>
	<view:tab>
	  	<li class="navtab <?=reportActiveTab($tab['_id']);?>" data-tab_id="<?=$tab['_id'];?>" style="padding-right:15px;"><a href="#" data-tab_id="<?=$tab['_id'];?>" class="drag" id="nav_<?=$tab['_id'];?>" onclick="return reportNavBar(<?=$tab['_id'];?>);"><?=$tab['name'];?></a></li>
	</view:tab>
	<?=renderEach('tab',$usertabs,'tab');?>
	<?=buildOnLoad("reportInitTabSort();");?>
</view:usertabs>

<view:todo>

mouseover tooltips
$ on axis for ones that are dollar values
% on axis for percents
month names instead of 1-12
transitions on pie and donuts

Contribution Margin Popup
	Click on Name of the report
		Contribution Margin Details
			market,category filters

DONE: Flip chart to back for table of data
	Product Mix has on back Product Mix by month line chart
	or table data
Tie dashboard to a full report

Totals orders by Month
	click a bar and have it flip to back with a pie chart - order source for that month

Click on Utah and see Utah sales by month on back
--------------------------------
onclick needs to pass in filters, report_id, tab_id
DONE: responsive menu


mouseover tooltips to see data (like on map)
DONE:remove bar on top of tile
number format on axis ($ or %)
stretch total orders by month to fill tile
month names on contribution margin x axis
DONE: category font to match sub-title font
stroke width set to 1 and stroke set to grey
	contribution margin
	monthly sales
DONE: remove year from main title
DONE: unbold country in sub-title
DONE:add top/left/right padding to Month over month
flip on monthly sales gets stuck
DONE:gap under top menu and gap on left needs to match gab between tiles
DONE:manage menu needs categories and changed to tree view and made uniform sizes
center tiles on page
Add NULL to monthly sales for current month so the line stops
Remove legend on donut
US Order by state - legend box spacing smaller



</view:todo>
