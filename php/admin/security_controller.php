<?php
/*
	Security Scanner controller (thin): route on $_REQUEST['func'].
	  - default (no func) : intro + Scan button (view: default)
	  - func=run          : scan this site's _pages/_templates code and render the
	                        AJAX partial with findings + "Fix with AI" (view: result)
	All the real work lives in security_functions.php.
*/
	switch(strtolower(isset($_REQUEST['func'])?$_REQUEST['func']:'')){
		case 'run':
			session_write_close();
			$scan=securityScanSite();
			setView('result',1);
		break;
		default:
			setView('default');
		break;
	}
?>
