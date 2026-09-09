<?php
/*
	Blocked IPs Firewall - backend admin page (Controller)
	Thin: auth, route on $_REQUEST['func'], pick a view. Work lives in
	firewall_functions.php. Controller + body share scope here (adminViewPage
	evals them together), so vars set below are read directly in the views.
	No PHP header('Location') redirects - admin.php has already sent output, so
	maintenance actions run here and re-render the dashboard (tempfiles pattern).
*/
if(!isAdmin()){
	echo '<div class="w_bold w_danger">Access Denied: Admin privileges required</div>';
	exit;
}

$adderror='';
$func=strtolower(isset($_REQUEST['func'])?$_REQUEST['func']:'');

//--- maintenance actions arrive as a full-page nav: act, flash, show dashboard
if($func==='purge_inactive'){
	$n=firewallActionPurgeInactive();
	$_SESSION['firewall_message']="<div class='w_bold w_success'><span class='icon-success'></span> Removed {$n} non-blocking record(s).</div>";
	$func='';
}
elseif($func==='prune_history'){
	$days=isNum($_REQUEST['days'])?(int)$_REQUEST['days']:180;
	$n=firewallActionPruneHistory($days);
	$_SESSION['firewall_message']="<div class='w_bold w_success'><span class='icon-success'></span> Removed {$n} history event(s) older than {$days} day(s).</div>";
	$func='';
}

switch($func){

	//--- AJAX partials -----------------------------------------------------
	case 'list':
		$grid=firewallListGrid();
		setView('grid',1);
		return;

	case 'history':
		$grid=firewallHistoryGrid();
		setView('history',1);
		return;

	//--- row / bulk actions: act, then return the refreshed grid ----------
	case 'toggle':
		firewallActionToggle($_REQUEST['id']);
		$grid=firewallListGrid();
		setView('grid',1);
		return;

	case 'delete':
		$ids=isset($_REQUEST['ids'])?preg_split('/[\s,;]+/',trim((string)$_REQUEST['ids'])):array(isset($_REQUEST['id'])?$_REQUEST['id']:0);
		firewallActionDelete($ids);
		$grid=firewallListGrid();
		setView('grid',1);
		return;

	case 'unblock':
		$ids=isset($_REQUEST['ids'])?preg_split('/[\s,;]+/',trim((string)$_REQUEST['ids'])):array(isset($_REQUEST['id'])?$_REQUEST['id']:0);
		firewallActionSetActive($ids,0);
		$grid=firewallListGrid();
		setView('grid',1);
		return;

	case 'note':
		firewallActionNotes($_REQUEST['id'],isset($_REQUEST['notes'])?$_REQUEST['notes']:'');
		$grid=firewallListGrid();
		setView('grid',1);
		return;

	case 'add':
		$adderror=firewallActionAdd(isset($_REQUEST['ip'])?$_REQUEST['ip']:'',isset($_REQUEST['reason'])?$_REQUEST['reason']:'');
		$grid=firewallListGrid();
		if(strlen($adderror)){
			$grid='<div class="w_bold w_danger" style="padding:6px 0;"><span class="icon-warning"></span> '.encodeHtml($adderror).'</div>'.$grid;
		}
		setView('grid',1);
		return;

	//--- full dashboard -------------------------------------------------
	default:
		$info     = firewallDbInfo();
		$stats    = firewallStats();
		$timeline = firewallTimelineChart(30);
		$patterns = firewallPatternChart();
		$grid     = firewallListGrid();
		setView('default');
	break;
}
?>
