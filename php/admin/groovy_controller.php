<?php
//Access control check
if(!isAdmin()){
	echo '<div class="w_bold w_danger">Access Denied: Admin privileges required</div>';
	exit;
}
$func=isset($_REQUEST['func']) ? strtolower($_REQUEST['func']) : '';
switch($func){
	case 'status':
		$status=groovyAdminStatus();
		setView('status_panel',1);
		return;
	break;
	case 'stats':
	case 'reset_stats':
		$stats=groovyAdminStats($func=='reset_stats');
		setView('stats_panel',1);
		return;
	break;
	case 'errors':
		$errors=groovyAdminErrors(isset($_REQUEST['endpoint']) ? $_REQUEST['endpoint'] : '',isset($_REQUEST['db']) ? $_REQUEST['db'] : '',isset($_REQUEST['user']) ? $_REQUEST['user'] : '');
		setView('errors_popup',1);
		return;
	break;
	case 'help':
		$help=groovyAdminHelp(isset($_REQUEST['topic']) ? $_REQUEST['topic'] : '');
		if(!count($help)){
			echo '<div class="w_bold w_danger">Unknown help topic</div>';
			exit;
		}
		setView('help_popup',1);
		return;
	break;
	case 'cancel':
		$result=groovyAdminCancel(isset($_REQUEST['thread']) ? $_REQUEST['thread'] : '');
		$status=groovyAdminStatus();
		$status['message']=$result['message'];
		$status['message_class']=$result['class'];
		setView('status_panel',1);
		return;
	break;
	case 'log':
	case 'clear_log':
		$message=array('message'=>'','class'=>'');
		if($func=='clear_log'){
			$err=groovyAdminClearLog();
			$message=strlen($err) ? array('message'=>$err,'class'=>'is-danger') : array('message'=>'Log cleared - showing only what happens from now on. The previous log was saved alongside it with a .prev extension.','class'=>'is-success');
		}
		$log=groovyAdminLog(isset($_REQUEST['lines']) ? $_REQUEST['lines'] : 300,isset($_REQUEST['filter']) ? $_REQUEST['filter'] : 'all');
		$log['message']=$message['message'];
		$log['message_class']=$message['class'];
		setView('log_panel',1);
		return;
	break;
	case 'start_fresh':
		//clear the log AND reset stats/errors, then redraw whichever tab the button was on
		$result=groovyAdminStartFresh();
		if(isset($_REQUEST['panel']) && $_REQUEST['panel']=='log'){
			$log=groovyAdminLog(isset($_REQUEST['lines']) ? $_REQUEST['lines'] : 300,isset($_REQUEST['filter']) ? $_REQUEST['filter'] : 'all');
			$log['message']=$result['message'];
			$log['message_class']=$result['class'];
			setView('log_panel',1);
			return;
		}
		$stats=groovyAdminStats(false);
		$stats['message']=$result['message'];
		$stats['message_class']=$result['class'];
		setView('stats_panel',1);
		return;
	break;
	case 'start':
	case 'stop':
	case 'restart':
	case 'reload':
	case 'kill':
		$err='';
		switch($func){
			case 'start':   $err=groovyAdminStart(); $done='Groovy server started.'; break;
			case 'stop':    $err=groovyAdminStop(); $done='Groovy server stopped.'; break;
			case 'reload':  $err=groovyAdminReload(); $done='Config and modules reloaded - db modules recompile on their next request.'; break;
			case 'kill':    $err=groovyAdminKill(); $done='Groovy server process killed.'; break;
			case 'restart':
				$err=groovyAdminStop();
				if(!strlen($err)){$err=groovyAdminStart();}
				$done='Groovy server restarted.';
			break;
		}
		$status=groovyAdminStatus();
		//the status panel view reads everything from $status (it is also rendered via renderView, where only params are in scope)
		$status['message']=strlen($err) ? $err : $done;
		$status['message_class']=strlen($err) ? 'is-danger' : 'is-success';
		setView('status_panel',1);
		return;
	break;
	default:
		//page shell + the Status tab; Stats and Log tabs load over AJAX
		$status=groovyAdminStatus();
		setView('default');
	break;
}
?>
