<view:default>
<script src="//d3js.org/topojson.v1.min.js" type="text/javascript"></script>
<script src="//datamaps.github.io/scripts/datamaps.all.js" type="text/javascript"></script>
<div class="row well">
	<div class="col-xs-1" style="border-right:1px solid #cccccc;">
		<div class="hline"> Dashboard </div>
		<div class="sidebar-item"><a href="#reports" class="w_link blue w_block w_big" onclick="return setContent('reports')"><span class="icon-chart-bar"></span><span class="hidden-xs"> Reports</span></a></div>
		<view:account_admin>
		<div class="sidebar-item"><a href="#streams" class="w_link blue w_block w_big" onclick="return setContent('streams')"><span class="icon-rss"></span><span class="hidden-xs"> Streams</span></a></div>
		<!-- <div class="sidebar-item"><a href="#report_stats" class="w_link blue w_block w_big" onclick="return setContent('report_stats')"><span class="icon-chart-bar2"></span><span class="hidden-xs"> Usage</span></a></div> -->
		</view:account_admin>

		<div class="hline"> Settings </div>
		<div class="sidebar-item"><a href="#profile" class="w_link blue w_block w_big" onclick="return setContent('profile')"><span class="icon-user"></span><span class="hidden-xs"> Profile</span></a></div>
		<view:account_admin>
		<div class="sidebar-item"><a href="#users" class="w_link blue w_block w_big" onclick="return setContent('users')"><span class="icon-users"></span><span class="hidden-xs"> Users</span></a></div>
		<div class="sidebar-item"><a href="#sources" class="w_link blue w_block w_big" onclick="return setContent('sources')"><span class="icon-import"></span><span class="hidden-xs"> Sources</span></a></div>
		</view:account_admin>

		<view:account_admin>
		<div class="hline"> Billing </div>
		<div class="sidebar-item"><a href="#account" class="w_link blue w_block w_big" onclick="return setContent('account')"><span class="icon-briefcase"></span><span class="hidden-xs"> Company</span></a></div>
		</view:account_admin>

	</div>
	<div class="col-xs-10" id="content" style="min-height:500px;">
		<?=buildOnLoad("ajaxGet('/t/1/account/{$defaultmenu}','content');");?>
	</div>
</div>
</view:default>

<view:users>
	<div class="row">
		<div class="col-xs-12">
			<h3 class="blue" style="padding:0px;margin:0px;"><span class="icon-users"></span> User Manager</h3>
		</div>
	</div>
	<a href="#" class="w_link w_block w_right w_bigger" onclick="return ajaxGet('/t/1/account/users/add','centerpop');" title="click to add"><span class="icon-user-add"></span> add</a>
	<table class="table table-bordered table-striped table-hover">
		<tr>
			<th>Type</th>
			<th>Title</th>
			<th>Firstname</th>
			<th>Lastname</th>
			<th>Username</th>
			<th>Email</th>
			<th>Last Activity</th>
		</tr>
		<view:rec>
		<tr class="w_pointer" onclick="ajaxGet('/t/1/account/users/<?=$rec['_id'];?>','centerpop');" title="click to edit">
			<td><?=$rec['type'];?></td>
			<td><?=$rec['title'];?></td>
			<td><?=$rec['firstname'];?></td>
			<td><?=$rec['lastname'];?></td>
			<td><?=$rec['username'];?></td>
			<td><?=$rec['email'];?></td>
			<td><?=$rec['_adate'];?></td>
		</tr>
		</view:rec>
		<?=renderEach('rec',$recs,'rec');?>
	</table>
	<?=buildOnLoad("removeDiv('centerpop');");?>
</view:users>
<view:users_addedit>
	<div class="w_centerpop_title">User add/edit</div>
	<div class="w_centerpop_content">
		<?=accountAddEditUser($id);?>
	</div>
</view:users_addedit>

<view:sources>
	<div class="row">
		<div class="col-xs-12">
			<h3 class="blue" style="padding:0px;margin:0px;"><span class="icon-import"></span> Data Sources</h3>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-11" id="sources_woo" style="padding-left:40px;margin-bottom:40px;">
			<h3><img src="/images/site/woocommerce-logo.png" alt="WooCommerce" height="30" class="img" /> Webhooks</h3>
			<form method="post" name="wooform" action="/account/sources/woo" onsubmit="return submitForm(this);">
				<div class="row">
					<div class="col-sm-4">
						<?=buildFormText('woo_url',array('class'=>'form-control input-lg','placeholder'=>'Woo Website URL','requiredmsg'=>'Enter your Woo Commerce website URL','required'=>1));?>
					</div>
					<div class="col-sm-8 text-left">
						<view:register_woo>
							<button type="submit" class="btn btn-lg btn-info"><span class="icon-arrow-right"></span> Register Woo Account</button>
						</view:register_woo>
						<view:unregister_woo>
							<button type="submit" class="btn btn-lg btn-danger"><span class="icon-arrow-right"></span> Unregister Woo Account</button>
						</view:unregister_woo>
						<?=renderViewIfElse(isset($datasources['woo']),'unregister_woo','register_woo',$datasources['woo'],'datasource');?>
					</div>
				</div>
			</form>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-11" style="padding-left:40px;margin-bottom:40px;">
			<h3><img src="/images/site/stripe-logo.png" alt="Stripe Merchant" height="30" class="img" /></h3>
			<div class="row">
				<div class="col-sm-11">
					<view:register_stripe>
					<a href="https://connect.stripe.com/oauth/authorize?response_type=code&client_id=ca_9dwgZWyHahDX5EsEY19hC9s7Kv4g6UnY&scope=read_only" class="btn btn-lg btn-info"><span class="icon-arrow-right"></span> Register Stripe Account</a>
					</view:register_stripe>
					<view:unregister_stripe>
					<a href="/register?scope=<?=$datasource['scope'];?>&code=<?=$datasource['code'];?>" class="btn btn-lg btn-danger"><span class="icon-arrow-right"></span> Unregister Stripe Account</a>
					</view:unregister_stripe>
					<?=renderViewIfElse(isset($datasources['stripe']),'unregister_stripe','register_stripe',$datasources['stripe'],'datasource');?>
				</div>
			</div>
		</div>
	</div>
</view:sources>



<view:account>
	<div class="row">
		<div class="col-xs-12">
			<h3 class="blue" style="padding:0px;margin:0px;"><span class="icon-briefcase"></span> Account</h3>
		</div>
	</div>
	<?=pageAccountForm();?>
	<div style="display:none"><div id="nulldiv"></div></div>
</view:account>

<view:profile>
	<div class="row">
		<div class="col-xs-12">
			<h3 class="blue" style="padding:0px;margin:0px;"><span class="icon-user"></span> Profile</h3>
		</div>
	</div>
	<?=pageProfileForm();?>
	<div style="display:none"><div id="nulldiv"></div></div>
</view:profile>

<view:streams>
	<div class="row">
		<div class="col-xs-12">
			<h3 class="blue" style="padding:0px;margin:0px;"><span class="icon-rss"></span> Streams</h3>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-4">
			<h4><span class="icon-mic"></span> Audible Stream</h4>
			<div id="stream_audio" class="w_pad w_round w_whiteback w_shadow" style="min-height:200px;max-height:400px;overflow:auto;">
				<?=renderView('streamdata_audio',$stream,'stream');?>
				<?=buildOnLoad("scrollStream('audio');");?>
			</div>
		</div>
		<div class="col-sm-4">
			<h4><span class="icon-currency-dollar"></span> Orders Stream</h4>
			<div id="stream_orders" class="w_pad w_round w_whiteback w_shadow" style="min-height:200px;max-height:400px;overflow:auto;">
				<?=renderView('streamdata_orders',$stream,'stream');?>
				<?=buildOnLoad("scrollStream('orders');");?>
			</div>
		</div>
	</div>
	<div style="display:none"><div id="nulldiv" data-behavior="ajax" data-url="/t/1/account/streamdata" data-timer="30"></div></div>
</view:streams>

<view:streamdata_audio>
	<view:audio>
		<div class="stream_request">
			<div class="w_grey w_small"><span class="icon-user"></span> <?=$audio['name'];?> <?=$audio['_cdate'];?></div>
			<div><?=$audio['question'];?></div>
		</div>
		<div class="stream_response"><span class="icon-mic w_grey"></span> <?=$audio['answer'];?></div>
	</view:audio>
	<?=renderEach('audio',$stream['audio'],'audio');?>
</view:streamdata_audio>

<view:streamdata_orders>
	<view:order>
		<div class="stream_order">
			<div class="w_grey w_small"><span class="icon-package"></span> #<?=$order['order_id'];?> on <?=$order['cdate'];?></div>
			<div><?=$order['name'];?></div>
			<div><?="{$order['billto_city']}, {$order['billto_state']} {$order['billto_postcode']}";?></div>
			<div style="margin-left:15%;"><table class="table table-condensed">
			<view:item>
				<tr><td><?=$item['id'];?></td><td><?=$item['name'];?></td><td><?=$item['qty'];?></td></tr>
			</view:item>
			<?=renderEach('item',$order['items'],'item');?>
			</table></div>
			<div class="text-right"><b>$<?=$order['amount'];?></b></div>
		</div>
	</view:order>
	<?=renderEach('order',$stream['orders'],'order');?>
</view:streamdata_orders>

<view:streamdata>
	<div id="streamdata_audio">
		<?=renderView('streamdata_audio',$stream,'stream');?>
	</div>
	<div id="streamdata_orders">
		<?=renderView('streamdata_orders',$stream,'stream');?>
	</div>
	<?=buildOnLoad("updateStream('audio');updateStream('orders');");?>
</view:streamdata>

<view:report_stats>
	<h3 class="blue"><span class="icon-chart-bar2"></span> Usage</h3>

	<div style="display:none"><div id="nulldiv"></div></div>
</view:report_stats>

<view:reports>
	<div class="row">
		<div class="col-xs-12">
			<h3 class="blue" style="padding:0px;margin:0px;"><span class="icon-chart-bar"></span> Reports</h3>
		</div>
	</div>
	<div class="w_padtop">
		<nav >
			<ul class="nav navbar-nav" id="usertabs">
	        	<?=renderView('usertabs',$usertabs,'usertabs');?>
		    </ul>
		</nav><br clear="both">
	  	<div id="reports">
			<?=renderView('tiles',$recs,'recs');?>
		</div>
		<?=buildOnLoad("removeId('centerpop');");?>
		<div style="display:none"><div id="nulldiv"></div></div>
	</div>
</view:reports>

<view:addreportform>
	<div style="height:400px;overflow:auto;">
	Click on a report to add it
	<table class="table table-striped table-bordered table-hover">
		<tr>
			<th>Project</th>
			<th>Name</th>
			<th>Type</th>
		</tr>
	<view:report>
		<tr class="w_pointer" onclick="reportAddReportToTab(<?=$rec['_id'];?>,<?=$_REQUEST['tab_id'];?>);">
			<td><?=ucfirst($rec['project']);?>
				<view:ready><span class="icon-star w_gold"></span></view:ready>
				<?=renderViewIf($rec['ready']==1,'ready',$rec,'rec');?>
			</td>
			<td><?=$rec['name'];?></td>
			<td><?=$rec['graph_type'];?></td>
		</tr>
	</view:report>
	<?=renderEach('report',$recs,'rec');?>
	</table>
	</div>
</view:addreportform>



<view:login>
	<?=userLoginForm(array('-action'=>'/account'));?>
</view:login>

<view:reportmanage>
	<div class="well">
	<view:report>
		<div class="text-left"><div class="report btn btn-default" onclick="reportAddReportToTab(<?=$rec['_id'];?>);"><?=$rec['name'];?> <span class="icon-plus w_success"></span></div></div>
	</view:report>
	<?=renderEach('report',$recs,'rec');?>
	<div class="text-right"><a href="#" class="w_link w_block" onclick="return hideId('reportmanage');"><span class="icon-cancel w_danger w_small"></span></a></div>
	</div>
</view:reportmanage>




<view:tiles>
	<div id="tiles" class="tiles" data-tab_id="<?=$_REQUEST['tab_id'];?>">
	<view:tile>
	<div class="tile tile-double" data-report_name="<?=$rec['name'];?>" data-report_id="<?=$rec['_id'];?>" data-tab_id="<?=$_REQUEST['tab_id'];?>">
		<div id="report_<?=$rec['_id'];?>">
			<div class="row topbar">
				<div class="col-sm-12 text-right reportMenu" id="reportMenu_<?=$rec['_id'];?>" style="padding-right:25px;">
					<view:sql>
						<a href="#" class="w_link toggle sql" data-id="<?=$rec['_id'];?>" title="sql" onclick="return reportMenu(this);"><span class="icon-database"></span></a>
					</view:sql>
					<?=renderViewIf(isAdmin(),'sql',$rec,'rec');?>
					<a href="#" class="w_link toggle speech" data-id="<?=$rec['_id'];?>" title="speech" onclick="return reportMenu(this);"><span class="icon-mic w_warning"></span></a>
					<a href="#" class="w_link toggle report" data-id="<?=$rec['_id'];?>" title="report" onclick="return reportMenu(this);"><span class="icon-chart-bar w_primary"></span></a>
					<a href="#" class="w_link toggle list" data-id="<?=$rec['_id'];?>" title="list" onclick="return reportMenu(this);"><span class="icon-th-list w_info"></span></a>
					<a href="#" class="w_link toggle filter" data-id="<?=$rec['_id'];?>" title="filter" onclick="return reportMenu(this);"><span class="icon-filter w_warning"></span></a>
					<!-- <a href="#" class="w_link toggle notes" data-id="<?=$rec['_id'];?>" title="notes" onclick="return reportMenu(this);"><span class="icon-file-txt w_grey"></span></a> -->
					<a href="#" class="w_link toggle export" data-id="<?=$rec['_id'];?>" title="export" onclick="return reportMenu(this);"><span class="icon-file-excel w_success"></span></a>
					<view:remove><a href="#" class="w_link remove" data-id="<?=$rec['_id'];?>" data-tab_id="<?=$_REQUEST['tab_id'];?>" title="remove" onclick="return reportMenu(this);"><span class="icon-close w_danger"></span></a></view:remove>
					<?=renderViewIf(accountIsAdmin(),'remove',$rec,'rec');?>
				</div>
			</div>
			<div class="row w_padtop">
				<div class="col-sm-11 text-center report_group title">
					<?=$rec['name'];?>
					<div class="text-center report_group subtitle"></div>
				</div>

				<div class="col-sm-1 progresspcnt"></div>
			</div>
			<div class="reportData">
				<?=pageLoadReport($rec);?>
				<div class="data list text-left" id="datalist_<?=$rec['_id'];?>"  style="display:none;z-index:99996;">
					<h3>LIST HERE</h3>
				</div>
				<div class="data notes text-left" id="datalist_<?=$rec['_id'];?>"  style="display:none;">
					<div class="row">
						<div class="col-sm-1"></div>
						<div class="col-sm-10">
							<div><span class="icon-file-txt"></span> Notes</div>
							<form id="notesform_<?=$rec['_id'];?>" action="/t/1/account/addnote/<?=$rec['_id'];?>" onsubmit="return ajaxSubmitForm(this,'noteslist_<?=$rec['_id'];?>');">
								<textarea class="form-control" rows="2" placeholder="add note here" name="note"></textarea>
								<div class="text-right" style="margin-top:3px;"><button class="btn btn-default">Add Note</button></div>
							</form>
							<div id="noteslist_<?=$rec['_id'];?>" class="noteslist">

							</div>
						</div>
					</div>
				</div>
				<div class="data sql text-left w_pad" id="datasql_<?=$rec['_id'];?>"  style="overflow:auto;max-height:350px;display:none;z-index:99997;">
					<h3>SQL HERE</h3>
				</div>
				<div class="data speech text-left w_pad" id="dataspeech_<?=$rec['_id'];?>"  style="overflow:auto;max-height:350px;display:none;z-index:99998;">
					<h3>SPEECH HERE</h3>
				</div>
				<div class="data filter" style="overflow:auto;max-height:310px;display:none;z-index:99991;">
					<form id="filters_<?=$rec['_id'];?>" onsubmit="return reportUpdateChart(<?=$rec['_id'];?>);">
						<input type="hidden" value="filters" name="update" />
						<div class="row">
							<div class="col-sm-1"></div>
							<div class="col-sm-7 text-center">
								<?=pageBuildQuickFilter('default_date',$rec);?>
							</div>
							<div class="col-sm-3 text-center">
							</div>
						</div>

						<div class="row w_padtop">
							<div class="col-sm-1"></div>
							<div class="col-sm-5 text-center">
								<label for="from_date">Begin Date</label>
								<?=buildFormCalendar('from_date',array('id'=>'from_date'.$rec['_id']));?>
							</div>
							<div class="col-sm-5 text-center">
								<label for="to_date">End Date</label>
								<?=buildFormCalendar('to_date',array('id'=>'to_date'.$rec['_id']));?>
							</div>
						</div>
						<div class="row w_padtop">
							<div class="col-sm-1"></div>
							<div class="col-sm-5 text-center">
								<label for="city">City</label>
								<?=buildFormText('city');?>
							</div>
							<div class="col-sm-5 text-center">
								<label for="state">State</label>
								<?=buildFormText('state');?>
							</div>
						</div>
						<div class="row w_padtop">
							<div class="col-sm-1"></div>
							<div class="col-sm-5 text-center">
								<label for="postcode">Postcode</label>
								<?=buildFormText('postcode');?>
							</div>
							<div class="col-sm-5 text-center">
								<br />
								<button type="submit" class="btn btn-default btn-lg">SAVE</button>
							</div>
						</div>
						<div class="row w_padtop">
							<div class="col-sm-1"></div>
							<div class="col-sm-10 text-right">

							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
	</view:tile>
	<?=renderEach('tile',$recs,'rec');?>
	<?=renderViewIF(accountIsAdmin() && isNum($_REQUEST['tab_id']),'addtile');?>
	<?if(!isMobileDevice()){echo buildOnLoad("reportInitTileSort();");}?>
	</div>
</view:tiles>

<view:addtile>
	<div class="tile tile-double">
		<div>
			<div class="reportData w_padtop">
				<div class="text-center w_padtop" id="addtile_<?=$_REQUEST['tab_id'];?>"><a href="#" class="toggle cart" data-tab_id="<?=$_REQUEST['tab_id'];?>" onclick="return reportAddReportForm(this);" style="border-radius:20px;padding-bottom:15px">
					<span class="icon-plus" style="font-size:50px;color:#f0f0f0;"></span><br />
					<span style="font-size:25px;color:#d2d2d2;">Add New <?=reportGetTabName($_REQUEST['tab_id']);?> Report</span>
				</a></div>
				<div class="data addtile text-left" id="addreport_<?=$_REQUEST['tab_id'];?>"  style="display:none;z-index:99996;">
					<h3>please wait ...</h3>
				</div>
			</div>
		</div>
	</div>
</view:addtile>

<view:usertabs>
	<li id="navtab_toggle" class="navtab w_big w_bold" data-tab_id="new" style="padding-right:15px;">
		<?=accountLiveStageSwitch();?>
	</li>
	<view:tab>
	  	<li class="navtab w_big w_bold <?=reportActiveTab($tab['_id']);?>" id="navtab_<?=$tab['_id'];?>" data-tab_id="<?=$tab['_id'];?>" style="padding-right:15px;">
			<span class="input-group-btn" id="navbuttons" style="display:inline;">
				<a href="#" data-tab_id="<?=$tab['_id'];?>" class="btn btn-default" id="nav_<?=$tab['_id'];?>" onclick="return reportNavBar(<?=$tab['_id'];?>);"><?=$tab['name'];?></a>
				<div class="btn btn-default" data-behavior="menu" data-display="navoptions_<?=$tab['_id'];?>"><span class="icon-dir-down"></span>
				<ul class="dropdown-menu" id="navoptions_<?=$tab['_id'];?>" style="display:none;margin-top:-1px;min-width:95px;">
					<li class="delete"><a href="#" onclick="return reportTabFormAction('delete',<?=$tab['_id'];?>);"><span class="icon-cancel w_danger"></span> DELETE</a></li>
					<li class="clear"><a href="#" onclick="return reportTabFormAction('clear',<?=$tab['_id'];?>);"><span class="icon-erase w_primary"></span> CLEAR</a></li>
					<li class="default"><a href="#" onclick="return reportTabFormAction('default',<?=$tab['_id'];?>);"><span class="icon-star w_warning"></span> DEFAULT</a></li>
				</ul>
				</div>
			</span>
		</li>
	</view:tab>
	<?=renderEach('tab',$usertabs,'tab');?>
	<li id="navtab_new" class="navtab w_big w_bold" data-tab_id="new" style="padding-right:15px;">
		<span id="navbuttons" class="input-group-btn" style="display:inline;">
			<a id="nav_new" title="add new" class="btn btn-default" href="#" data-tab_id="new" onclick="return ajaxGet('/t/1/account/addtab','centerpop');"><span class="icon-plus w_grey"></span></a>
		</span>
	</li>
</view:usertabs>

<view:addtab>
	<div class="w_centerpop_title">New</div>
	<div class="w_centerpop_content">
		<?=accountAddTabForm();?>
	</div>
</view:addtab>
