<?php
/*
	Blocked IPs Firewall - backend admin page (Model layer)
	=======================================================
	Manages the per-server SQLite firewall database that php/common.php's
	commonBlockedIps* functions read on every request. The db is NOT tied to any
	one site / domain - it lives at getWasqlPath('blocked_ips.db') and every
	site's backend admin on this server edits the same file. All access here goes
	through commonBlockedIpsDb() so there is exactly one code path to the file.

	Route: /php/admin.php?_menu=firewall  (see the two fallthrough switches in
	admin.php). Sub-actions arrive as $_REQUEST['func'].
*/

//---------- begin function firewallDb ----
/**
* @exclude
* @describe shared PDO handle to blocked_ips.db, or null (fail-soft - the page
*	then shows an "unavailable" notice instead of erroring).
*/
function firewallDb(){
	if(!function_exists('commonBlockedIpsDb')){return null;}
	return commonBlockedIpsDb();
}
//---------- begin function firewallDbInfo ----
/**
* @exclude
* @describe file-level facts for the status header: path, whether it exists yet,
*	size, last-modified age, and whether the firewall is switched on in config.
* @return array
*/
function firewallDbInfo(){
	$path=function_exists('commonBlockedIpsPath')?commonBlockedIpsPath():getWasqlPath('blocked_ips.db');
	$exists=is_file($path);
	return array(
		'path'         => $path,
		'exists'       => $exists,
		'size'         => $exists?filesize($path):0,
		'mtime'        => $exists?filemtime($path):0,
		'enabled'      => function_exists('commonBlockedIpsEnabled')?commonBlockedIpsEnabled():false,
		'writable'     => $exists?is_writable($path):null,
		'dir_writable' => is_writable(dirname($path)),
		'error'        => function_exists('commonBlockedIpsLastError')?commonBlockedIpsLastError():'',
	);
}
//---------- begin function firewallStats ----
/**
* @exclude
* @describe headline numbers for the dashboard cards.
* @return array
*/
function firewallStats(){
	$out=array(
		'total'=>0,'active'=>0,'inactive'=>0,'sources'=>0,
		'hits_24h'=>0,'hits_7d'=>0,'hits_30d'=>0,'events_total'=>0,
		'top_source'=>'','last_hit'=>0,
	);
	$db=firewallDb();
	if($db===null){return $out;}
	try{
		$r=$db->query("SELECT
			COUNT(*) total,
			SUM(CASE WHEN active=1 THEN 1 ELSE 0 END) active,
			SUM(CASE WHEN active=0 THEN 1 ELSE 0 END) inactive,
			COUNT(DISTINCT source) sources
			FROM blocked_ips")->fetch(PDO::FETCH_ASSOC);
		if(is_array($r)){
			$out['total']=(int)$r['total']; $out['active']=(int)$r['active'];
			$out['inactive']=(int)$r['inactive']; $out['sources']=(int)$r['sources'];
		}
		$now=time();
		$out['hits_24h']=(int)$db->query("SELECT COUNT(*) FROM blocked_history WHERE created_at >= ".($now-86400))->fetchColumn();
		$out['hits_7d'] =(int)$db->query("SELECT COUNT(*) FROM blocked_history WHERE created_at >= ".($now-604800))->fetchColumn();
		$out['hits_30d']=(int)$db->query("SELECT COUNT(*) FROM blocked_history WHERE created_at >= ".($now-2592000))->fetchColumn();
		$out['events_total']=(int)$db->query("SELECT COUNT(*) FROM blocked_history")->fetchColumn();
		$out['last_hit']=(int)$db->query("SELECT COALESCE(MAX(created_at),0) FROM blocked_history")->fetchColumn();
		$ts=$db->query("SELECT source, COUNT(*) c FROM blocked_history WHERE source<>'' GROUP BY source ORDER BY c DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
		if(is_array($ts)){$out['top_source']=$ts['source'].' ('.$ts['c'].')';}
	}
	catch(\Throwable $e){error_log('firewallStats: '.$e->getMessage());}
	return $out;
}
//---------- begin function firewallTimelineData ----
/**
* @exclude
* @describe daily blocked_history counts for the last $days days, split into
*	flagged (new detections) vs blocked (known IPs turned away). Days with no
*	activity are filled with 0 so the axis is continuous.
* @param days int
* @return array  array('labels'=>[], 'flagged'=>[], 'blocked'=>[])
*/
function firewallTimelineData($days=30){
	$days=max(7,min(180,(int)$days));
	$labels=array(); $flagged=array(); $blocked=array(); $idx=array();
	for($i=$days-1;$i>=0;$i--){
		$d=date('Y-m-d',strtotime("-{$i} days"));
		$labels[]=$d; $flagged[]=0; $blocked[]=0; $idx[$d]=count($labels)-1;
	}
	$db=firewallDb();
	if($db===null){return array('labels'=>$labels,'flagged'=>$flagged,'blocked'=>$blocked);}
	try{
		$since=strtotime(date('Y-m-d',strtotime('-'.($days-1).' days')).' 00:00:00');
		$q=$db->prepare("SELECT date(created_at,'unixepoch','localtime') d, event, COUNT(*) c
			FROM blocked_history WHERE created_at >= :since GROUP BY d, event");
		$q->execute(array(':since'=>$since));
		foreach($q as $row){
			if(!isset($idx[$row['d']])){continue;}
			$i=$idx[$row['d']];
			if($row['event']==='blocked'){$blocked[$i]=(int)$row['c'];}
			else{$flagged[$i]=(int)$row['c'];}
		}
	}
	catch(\Throwable $e){error_log('firewallTimelineData: '.$e->getMessage());}
	return array('labels'=>$labels,'flagged'=>$flagged,'blocked'=>$blocked);
}
//---------- begin function firewallTopPatternsData ----
/**
* @exclude
* @describe the most-hit probe patterns across blocked_history (what attackers
*	are fishing for), for the doughnut chart.
* @param limit int
* @return array  array('labels'=>[], 'values'=>[])
*/
function firewallTopPatternsData($limit=8){
	$limit=max(3,min(20,(int)$limit));
	$labels=array(); $values=array();
	$db=firewallDb();
	if($db===null){return array('labels'=>$labels,'values'=>$values);}
	try{
		$q=$db->query("SELECT CASE WHEN pattern='' THEN '(none)' ELSE pattern END p, COUNT(*) c
			FROM blocked_history GROUP BY p ORDER BY c DESC LIMIT {$limit}");
		foreach($q as $row){$labels[]=$row['p']; $values[]=(int)$row['c'];}
	}
	catch(\Throwable $e){error_log('firewallTopPatternsData: '.$e->getMessage());}
	return array('labels'=>$labels,'values'=>$values);
}
//---------- begin function firewallTimelineChart ----
/**
* @exclude
* @describe <chartjs> JSON-form tag (see wasql_reference "Chart.js") for the
*	daily activity bar chart. Data is computed here, not left as SQL in the tag.
* @param days int
* @return string
*/
function firewallTimelineChart($days=30){
	$d=firewallTimelineData($days);
	if(!array_sum($d['flagged']) && !array_sum($d['blocked'])){
		return '<div class="w_gray" style="padding:30px;text-align:center;">No blocked activity in the last '.(int)$days.' days.</div>';
	}
	$labels=json_encode($d['labels']);
	$flagged=json_encode($d['flagged']);
	$blocked=json_encode($d['blocked']);
	return <<<HTML
<chartjs data-type="bar" data-id="fw_timeline" class="fw-chart-box">
	<dataset data-label="New detections">{$flagged}</dataset>
	<dataset data-label="Repeat blocks">{$blocked}</dataset>
	<labels>{$labels}</labels>
	<colors>["rgba(255,159,64,0.55)","rgba(75,192,192,0.55)"]</colors>
	<bcolors>["rgb(255,159,64)","rgb(75,192,192)"]</bcolors>
	<options>{"responsive":true,"maintainAspectRatio":false,"scales":{"xAxes":[{"stacked":true}],"yAxes":[{"stacked":true,"ticks":{"beginAtZero":true,"precision":0}}]}}</options>
</chartjs>
HTML;
}
//---------- begin function firewallPatternChart ----
/**
* @exclude
* @describe <chartjs> doughnut tag for the top probe patterns.
* @return string
*/
function firewallPatternChart(){
	$d=firewallTopPatternsData(8);
	if(!count($d['labels'])){
		return '<div class="w_gray" style="padding:30px;text-align:center;">Nothing recorded yet.</div>';
	}
	$labels=json_encode($d['labels']);
	$values=json_encode($d['values']);
	return <<<HTML
<chartjs data-type="doughnut" data-id="fw_patterns" class="fw-chart-box">
	<dataset data-label="Hits">{$values}</dataset>
	<labels>{$labels}</labels>
	<options>{"responsive":true,"maintainAspectRatio":false,"legend":{"position":"right"}}</options>
</chartjs>
HTML;
}
//---------- begin function firewallListParams ----
/**
* @exclude
* @describe reads + sanitises the grid controls from $_REQUEST (search, status,
*	sort, page). Shared by the controller and the grid renderer.
* @return array
*/
function firewallListParams(){
	$sortcols=array(
		'last_seen'=>'last_seen DESC','first_seen'=>'first_seen DESC','hits'=>'hit_count DESC',
		'ip'=>'ip_addr ASC','pattern'=>'pattern ASC','source'=>'source ASC',
	);
	$sort=isset($_REQUEST['fw_sort']) && isset($sortcols[$_REQUEST['fw_sort']])?$_REQUEST['fw_sort']:'last_seen';
	$status=isset($_REQUEST['fw_status']) && in_array($_REQUEST['fw_status'],array('active','inactive'))?$_REQUEST['fw_status']:'all';
	$page=isset($_REQUEST['fw_page']) && isNum($_REQUEST['fw_page'])?max(1,(int)$_REQUEST['fw_page']):1;
	return array(
		'search'  => isset($_REQUEST['fw_search'])?trim((string)$_REQUEST['fw_search']):'',
		'status'  => $status,
		'sort'    => $sort,
		'orderby' => $sortcols[$sort],
		'page'    => $page,
		'perpage' => 40,
	);
}
//---------- begin function firewallListGrid ----
/**
* @exclude
* @describe the blocked_ips table: filtered, sorted, paged, with per-row
*	block/unblock + delete and a checkbox column for bulk actions.
* @return string HTML
*/
function firewallListGrid(){
	$db=firewallDb();
	if($db===null){return firewallUnavailableNotice();}
	$p=firewallListParams();
	$where=array(); $args=array();
	if($p['status']==='active'){$where[]='active=1';}
	elseif($p['status']==='inactive'){$where[]='active=0';}
	if(strlen($p['search'])){
		$where[]='(ip_addr LIKE :s OR pattern LIKE :s OR source LIKE :s OR reason LIKE :s OR notes LIKE :s)';
		$args[':s']='%'.$p['search'].'%';
	}
	$wsql=count($where)?(' WHERE '.implode(' AND ',$where)):'';
	try{
		$cq=$db->prepare("SELECT COUNT(*) FROM blocked_ips{$wsql}");
		$cq->execute($args);
		$count=(int)$cq->fetchColumn();
		$pages=max(1,(int)ceil($count/$p['perpage']));
		$page=min($p['page'],$pages);
		$offset=($page-1)*$p['perpage'];
		$q=$db->prepare("SELECT * FROM blocked_ips{$wsql} ORDER BY {$p['orderby']} LIMIT {$p['perpage']} OFFSET {$offset}");
		$q->execute($args);
		$rows=$q->fetchAll(PDO::FETCH_ASSOC);
	}
	catch(\Throwable $e){
		error_log('firewallListGrid: '.$e->getMessage());
		return '<div class="w_bold w_danger">Query failed - see the error log.</div>';
	}

	$h='<form name="firewall_grid_form" onsubmit="return false;">';
	$h.='<div class="w_small w_gray" style="margin-bottom:6px;">'.number_format($count).' address'.($count==1?'':'es').' match'.($count==1?'es':'').
		' &middot; page '.$page.' of '.$pages.'</div>';
	$h.='<div style="overflow-x:auto;"><table class="wacss_table is-striped is-fullwidth is-sticky" style="min-width:900px;">';
	$h.='<thead><tr>'
		.'<th style="width:26px;"><input type="checkbox" onclick="firewallCheckAll(this);"></th>'
		.firewallSortTh('ip','IP address',$p)
		.'<th>Reason</th>'
		.firewallSortTh('pattern','Pattern',$p)
		.firewallSortTh('source','First seen at',$p)
		.firewallSortTh('hits','Hits',$p)
		.firewallSortTh('last_seen','Last hit',$p)
		.'<th>Status</th><th>Notes</th><th style="width:120px;">Actions</th>'
		.'</tr></thead><tbody>';
	if(!count($rows)){
		$h.='<tr><td colspan="10" class="w_gray" style="text-align:center;padding:24px;">No matching addresses.</td></tr>';
	}
	foreach($rows as $r){
		$id=(int)$r['id'];
		$active=(int)$r['active']===1;
		$h.='<tr>';
		$h.='<td><input type="checkbox" class="fw-check" value="'.$id.'"></td>';
		$h.='<td style="white-space:nowrap;"><code>'.encodeHtml($r['ip_addr']).'</code></td>';
		$h.='<td>'.encodeHtml($r['reason']).'</td>';
		$h.='<td>'.($r['pattern']!==''?'<span class="wacss_tag is-light">'.encodeHtml($r['pattern']).'</span>':'').'</td>';
		$h.='<td class="w_small" style="white-space:nowrap;">'.encodeHtml($r['source']!==''?$r['source']:'-').
			($r['last_source']!=='' && $r['last_source']!==$r['source']?'<br><span class="w_gray">last: '.encodeHtml($r['last_source']).'</span>':'').'</td>';
		$h.='<td style="text-align:right;">'.number_format((int)$r['hit_count']).'</td>';
		$h.='<td class="w_small" style="white-space:nowrap;" title="'.encodeHtml(firewallWhen($r['last_seen'])).'">'.encodeHtml(firewallAgo($r['last_seen'])).'</td>';
		$h.='<td>'.($active
			?'<span class="wacss_tag is-danger">blocking</span>'
			:'<span class="wacss_tag is-light">allowed</span>').'</td>';
		$h.='<td style="max-width:220px;"><span class="fw-note" style="cursor:pointer;" title="click to edit" onclick="firewallEditNote('.$id.',this);">'
			.($r['notes']!==''?encodeHtml($r['notes']):'<span class="w_gray">+ note</span>').'</span></td>';
		$h.='<td style="white-space:nowrap;">'
			.'<a href="#toggle" title="'.($active?'stop blocking (keep the record)':'block again').'" onclick="return firewallRow(\'toggle\','.$id.');">'
				.'<span class="'.($active?'icon-eye w_success':'icon-lock w_danger').'"></span></a> '
			.'<a href="#del" title="delete this record" onclick="return firewallRow(\'delete\','.$id.');"><span class="icon-erase w_danger"></span></a>'
			.'</td>';
		$h.='</tr>';
	}
	$h.='</tbody></table></div>';
	$h.=firewallPager($page,$pages);
	$h.='</form>';
	return $h;
}
//---------- begin function firewallSortTh ----
/** @exclude */
function firewallSortTh($key,$label,$p){
	$arrow=$p['sort']===$key?' <span class="w_gray">&#9660;</span>':'';
	return '<th style="cursor:pointer;white-space:nowrap;" onclick="firewallSort(\''.$key.'\');">'.encodeHtml($label).$arrow.'</th>';
}
//---------- begin function firewallPager ----
/** @exclude */
function firewallPager($page,$pages){
	if($pages<=1){return '';}
	$h='<div style="margin-top:10px;display:flex;gap:5px;flex-wrap:wrap;align-items:center;">';
	$mk=function($n,$label,$disabled=false) {
		if($disabled){return '<span class="wacss_button is-small is-static">'.$label.'</span>';}
		return '<a href="#p'.$n.'" class="wacss_button is-small" onclick="firewallSet(\'fw_page\','.$n.');firewallReloadGrid();">'.$label.'</a>';
	};
	$h.=$mk($page-1,'&laquo; Prev',$page<=1);
	$h.='<span class="w_small w_gray" style="padding:0 6px;">'.$page.' / '.$pages.'</span>';
	$h.=$mk($page+1,'Next &raquo;',$page>=$pages);
	$h.='</div>';
	return $h;
}
//---------- begin function firewallHistoryParams ----
/** @exclude */
function firewallHistoryParams(){
	$page=isset($_REQUEST['fw_hpage']) && isNum($_REQUEST['fw_hpage'])?max(1,(int)$_REQUEST['fw_hpage']):1;
	$event=isset($_REQUEST['fw_hevent']) && in_array($_REQUEST['fw_hevent'],array('flagged','blocked'))?$_REQUEST['fw_hevent']:'all';
	return array(
		'search'=>isset($_REQUEST['fw_hsearch'])?trim((string)$_REQUEST['fw_hsearch']):'',
		'event'=>$event,'page'=>$page,'perpage'=>50,
	);
}
//---------- begin function firewallHistoryGrid ----
/**
* @exclude
* @describe recent blocked_history events, filtered + paged. Read-only.
* @return string HTML
*/
function firewallHistoryGrid(){
	$db=firewallDb();
	if($db===null){return firewallUnavailableNotice();}
	$p=firewallHistoryParams();
	$where=array(); $args=array();
	if($p['event']!=='all'){$where[]='event=:e'; $args[':e']=$p['event'];}
	if(strlen($p['search'])){
		$where[]='(ip_addr LIKE :s OR pattern LIKE :s OR source LIKE :s OR request_uri LIKE :s)';
		$args[':s']='%'.$p['search'].'%';
	}
	$wsql=count($where)?(' WHERE '.implode(' AND ',$where)):'';
	try{
		$cq=$db->prepare("SELECT COUNT(*) FROM blocked_history{$wsql}");
		$cq->execute($args);
		$count=(int)$cq->fetchColumn();
		$pages=max(1,(int)ceil($count/$p['perpage']));
		$page=min($p['page'],$pages);
		$offset=($page-1)*$p['perpage'];
		$q=$db->prepare("SELECT * FROM blocked_history{$wsql} ORDER BY created_at DESC, id DESC LIMIT {$p['perpage']} OFFSET {$offset}");
		$q->execute($args);
		$rows=$q->fetchAll(PDO::FETCH_ASSOC);
	}
	catch(\Throwable $e){
		error_log('firewallHistoryGrid: '.$e->getMessage());
		return '<div class="w_bold w_danger">Query failed - see the error log.</div>';
	}
	$h='<div class="w_small w_gray" style="margin-bottom:6px;">'.number_format($count).' events &middot; page '.$page.' of '.$pages.'</div>';
	$h.='<div style="overflow-x:auto;"><table class="wacss_table is-striped is-fullwidth is-sticky" style="min-width:900px;">';
	$h.='<thead><tr><th>When</th><th>Event</th><th>IP address</th><th>Pattern</th><th>Site</th><th>Request URI</th></tr></thead><tbody>';
	if(!count($rows)){
		$h.='<tr><td colspan="6" class="w_gray" style="text-align:center;padding:24px;">No matching events.</td></tr>';
	}
	foreach($rows as $r){
		$h.='<tr>';
		$h.='<td class="w_small" style="white-space:nowrap;" title="'.encodeHtml(firewallWhen($r['created_at'])).'">'.encodeHtml(firewallAgo($r['created_at'])).'</td>';
		$h.='<td>'.($r['event']==='blocked'
			?'<span class="wacss_tag is-info">repeat</span>'
			:'<span class="wacss_tag is-warning">new</span>').'</td>';
		$h.='<td style="white-space:nowrap;"><code>'.encodeHtml($r['ip_addr']).'</code></td>';
		$h.='<td>'.($r['pattern']!==''?'<span class="wacss_tag is-light">'.encodeHtml($r['pattern']).'</span>':'').'</td>';
		$h.='<td class="w_small">'.encodeHtml($r['source']!==''?$r['source']:'-').'</td>';
		$h.='<td class="w_small" style="max-width:340px;word-break:break-all;">'.encodeHtml($r['request_uri']).'</td>';
		$h.='</tr>';
	}
	$h.='</tbody></table></div>';
	$h.=str_replace(array('fw_page','firewallReloadGrid'),array('fw_hpage','firewallReloadHistory'),firewallPager($page,$pages));
	return $h;
}
//---------- begin function firewallStatCards ----
/**
* @exclude
* @describe the row of headline stat cards for the dashboard. Built as a string
*	(no per-card island) per the "each island is eval'd separately" rule.
* @param stats array - from firewallStats()
* @param info array - from firewallDbInfo()
* @return string HTML
*/
function firewallStatCards($stats,$info){
	$cards=array(
		array('Currently blocking', number_format($stats['active']), 'icon-lock', 'is-danger'),
		array('Not blocking (kept)', number_format($stats['inactive']), 'icon-eye', ''),
		array('Hits, last 24h', number_format($stats['hits_24h']), 'icon-clock', 'is-warning'),
		array('Hits, last 7d', number_format($stats['hits_7d']), 'icon-history', ''),
		array('Contributing sites', number_format($stats['sources']), 'icon-website', ''),
		array('Events logged', number_format($stats['events_total']), 'icon-list', ''),
	);
	$h='<div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:16px;">';
	foreach($cards as $c){
		$h.='<div class="wadmin-card" style="flex:1 1 150px;margin:0;padding:12px 14px;">'
			.'<div class="w_small w_gray" style="text-transform:uppercase;letter-spacing:.03em;"><span class="'.$c[2].' '.($c[3]?:'w_gray').'"></span> '.encodeHtml($c[0]).'</div>'
			.'<div style="font-size:1.6rem;font-weight:700;line-height:1.3;">'.$c[1].'</div>'
			.'</div>';
	}
	$h.='</div>';
	$state=$info['enabled']
		?'<span class="wacss_tag is-success">ON</span>'
		:'<span class="wacss_tag is-danger">OFF</span> <span class="w_small w_gray">($CONFIG[\'blocked_ips\']=0)</span>';
	$file=$info['exists']
		?('<code>'.encodeHtml($info['path']).'</code> &middot; '.firewallBytes($info['size']).' &middot; updated '.encodeHtml(firewallAgo($info['mtime'])))
		:('<code>'.encodeHtml($info['path']).'</code> &middot; <span class="w_warning">not created yet</span>');
	$h.='<div class="w_small w_gray" style="margin-bottom:14px;">Firewall: '.$state.' &nbsp;|&nbsp; '.$file
		.($stats['top_source']!==''?(' &nbsp;|&nbsp; most active site: '.encodeHtml($stats['top_source'])):'').'</div>';
	return $h;
}
//---------- begin function firewallBytes ----
/** @exclude */
function firewallBytes($n){
	$n=(int)$n;
	if($n<1024){return $n.' B';}
	if($n<1048576){return number_format($n/1024,1).' KB';}
	return number_format($n/1048576,1).' MB';
}
//---------- begin function firewallUnavailableNotice ----
/** @exclude */
function firewallUnavailableNotice(){
	//ensure a connection attempt has run so commonBlockedIpsLastError() is populated
	if(function_exists('commonBlockedIpsDb')){commonBlockedIpsDb();}
	$info=firewallDbInfo();
	$out='<div class="wadmin-card"><div class="w_bold w_warning"><span class="icon-warning"></span> The firewall database is not available.</div>'
		.'<div class="w_small w_gray" style="margin-top:6px;">Expected at <code>'.encodeHtml($info['path']).'</code>. '
		.'It is created automatically on the first non-allowlisted request once PHP can write to the WaSQL root, '
		.'or SFTP a prebuilt <code>blocked_ips.db</code> into place.';
	if(!class_exists('PDO')){$out.=' PDO is not loaded on this server.';}
	$out.='</div>';
	if(strlen($info['error'])){
		$out.='<div class="w_small" style="margin-top:8px;"><span class="w_bold">PDO error:</span> '
			.'<code style="white-space:normal;">'.encodeHtml($info['error']).'</code></div>';
	}
	$out.='<div class="w_small w_gray" style="margin-top:6px;">'
		.'File exists: '.($info['exists']?'yes':'no')
		.(($info['exists'] && $info['writable']!==null)?' &nbsp;|&nbsp; File writable by PHP: '.($info['writable']?'yes':'<span class="w_warning">no</span>'):'')
		.' &nbsp;|&nbsp; Parent dir writable by PHP: '.($info['dir_writable']?'yes':'<span class="w_warning">no</span> ('.encodeHtml(dirname($info['path'])).')')
		.'</div>';
	$out.='</div>';
	return $out;
}
//---------- begin function firewallWhen ----
/** @exclude */
function firewallWhen($ts){$ts=(int)$ts; return $ts>0?date('Y-m-d H:i:s',$ts):'-';}
//---------- begin function firewallAgo ----
/** @exclude */
function firewallAgo($ts){
	$ts=(int)$ts; if($ts<=0){return '-';}
	$s=time()-$ts;
	if($s<60){return $s.'s ago';}
	if($s<3600){return floor($s/60).'m ago';}
	if($s<86400){return floor($s/3600).'h ago';}
	if($s<2592000){return floor($s/86400).'d ago';}
	return date('Y-m-d',$ts);
}

/* ============================ write actions ============================ */

//---------- begin function firewallActionToggle ----
/**
* @exclude
* @describe flips active 1<->0 for one row (unblock without losing the record).
* @param id int
* @return bool
*/
function firewallActionToggle($id){
	$db=firewallDb(); if($db===null || !isNum($id)){return false;}
	try{
		$db->prepare("UPDATE blocked_ips SET active = CASE WHEN active=1 THEN 0 ELSE 1 END WHERE id=:id")
			->execute(array(':id'=>(int)$id));
		commonBlockedIpsFlush();
		return true;
	}catch(\Throwable $e){error_log('firewallActionToggle: '.$e->getMessage()); return false;}
}
//---------- begin function firewallActionSetActive ----
/**
* @exclude
* @describe set active=1/0 for one or more rows (bulk unblock / re-block).
* @param ids array
* @param active int - 1 or 0
* @return int rows changed
*/
function firewallActionSetActive($ids,$active){
	$db=firewallDb(); if($db===null){return 0;}
	$ids=array_values(array_filter(array_map('intval',(array)$ids),function($n){return $n>0;}));
	if(!count($ids)){return 0;}
	$in=implode(',',$ids); $a=(int)$active?1:0;
	try{
		$n=$db->exec("UPDATE blocked_ips SET active={$a} WHERE id IN ({$in})");
		commonBlockedIpsFlush();
		return (int)$n;
	}catch(\Throwable $e){error_log('firewallActionSetActive: '.$e->getMessage()); return 0;}
}
//---------- begin function firewallActionDelete ----
/**
* @exclude
* @describe permanently removes one or more blocked_ips rows (history is kept).
* @param ids array - list of ids
* @return int rows removed
*/
function firewallActionDelete($ids){
	$db=firewallDb(); if($db===null){return 0;}
	$ids=array_values(array_filter(array_map('intval',(array)$ids),function($n){return $n>0;}));
	if(!count($ids)){return 0;}
	$in=implode(',',$ids);
	try{
		$n=$db->exec("DELETE FROM blocked_ips WHERE id IN ({$in})");
		commonBlockedIpsFlush();
		return (int)$n;
	}catch(\Throwable $e){error_log('firewallActionDelete: '.$e->getMessage()); return 0;}
}
//---------- begin function firewallActionAdd ----
/**
* @exclude
* @describe manually add (or re-activate) a blocked IP.
* @param ip string
* @param reason string
* @return string ''=ok, else an error message
*/
function firewallActionAdd($ip,$reason=''){
	$db=firewallDb(); if($db===null){return 'Firewall database unavailable.';}
	$ip=trim((string)$ip);
	if(!filter_var($ip,FILTER_VALIDATE_IP)){return 'That is not a valid IP address.';}
	$reason=trim((string)$reason); if($reason===''){$reason='Added manually';}
	$now=time();
	global $USER;
	$src=isset($USER['username'])?('admin:'.$USER['username']):'admin';
	try{
		$up=$db->prepare("UPDATE blocked_ips SET active=1, reason=:reason, last_seen=:now, last_source=:src WHERE ip_addr=:ip");
		$up->execute(array(':reason'=>$reason,':now'=>$now,':src'=>$src,':ip'=>$ip));
		if($up->rowCount()==0){
			$db->prepare("INSERT INTO blocked_ips (ip_addr,reason,pattern,source,last_source,hit_count,active,first_seen,last_seen)
				VALUES (:ip,:reason,'manual',:src,:src,0,1,:now,:now)")
				->execute(array(':ip'=>$ip,':reason'=>$reason,':src'=>$src,':now'=>$now));
		}
		$db->prepare("INSERT INTO blocked_history (ip_addr,source,event,reason,pattern,request_uri,user_agent,created_at)
			VALUES (:ip,:src,'flagged',:reason,'manual','','',:now)")
			->execute(array(':ip'=>$ip,':src'=>$src,':reason'=>$reason,':now'=>$now));
		commonBlockedIpsFlush();
		return '';
	}catch(\Throwable $e){error_log('firewallActionAdd: '.$e->getMessage()); return 'Write failed - see the error log.';}
}
//---------- begin function firewallActionNotes ----
/**
* @exclude
* @describe set the free-text note on a row.
* @param id int
* @param notes string
* @return bool
*/
function firewallActionNotes($id,$notes){
	$db=firewallDb(); if($db===null || !isNum($id)){return false;}
	try{
		$db->prepare("UPDATE blocked_ips SET notes=:n WHERE id=:id")
			->execute(array(':n'=>substr(trim((string)$notes),0,500),':id'=>(int)$id));
		return true;
	}catch(\Throwable $e){error_log('firewallActionNotes: '.$e->getMessage()); return false;}
}
//---------- begin function firewallActionPurgeInactive ----
/**
* @exclude
* @describe delete every row that is currently not blocking (active=0).
* @return int rows removed
*/
function firewallActionPurgeInactive(){
	$db=firewallDb(); if($db===null){return 0;}
	try{
		$n=$db->exec("DELETE FROM blocked_ips WHERE active=0");
		commonBlockedIpsFlush();
		return (int)$n;
	}catch(\Throwable $e){error_log('firewallActionPurgeInactive: '.$e->getMessage()); return 0;}
}
//---------- begin function firewallActionPruneHistory ----
/**
* @exclude
* @describe trim blocked_history to the last $days days (keeps the charts fast).
* @param days int
* @return int rows removed
*/
function firewallActionPruneHistory($days){
	$db=firewallDb(); if($db===null || !isNum($days)){return 0;}
	$days=max(1,(int)$days);
	$cut=time()-($days*86400);
	try{
		$n=$db->exec("DELETE FROM blocked_history WHERE created_at < {$cut}");
		return (int)$n;
	}catch(\Throwable $e){error_log('firewallActionPruneHistory: '.$e->getMessage()); return 0;}
}
?>
