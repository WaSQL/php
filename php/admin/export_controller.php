<?php
	if(!isAdmin()){
		setView('not_admin',1);
		return;
	}
	switch(strtolower($_REQUEST['func'])){
		case 'db':
			$path=getWasqlPath('sh/backups');
			buildDir($path);
			$ok=cleanupDirectory($path,2,'mon','gz');
			$dump=dumpDB();
			if(isset($dump['error']) && strlen($dump['error'])){echo printValue($dump);exit;}
			pushFile("{$dump['path']}/{$dump['file']}");
		break;
		case 'export':
			setView('export',1);
			//export_schema, export_meta, export_data
			//export_pages, export_templates
			$filename=addslashes($_REQUEST['name']);
			$schema=isset($_REQUEST['export_schema']) && is_array($_REQUEST['export_schema']) && count($_REQUEST['export_schema'])?$_REQUEST['export_schema']:array();
			$meta=isset($_REQUEST['export_meta']) && is_array($_REQUEST['export_meta']) && count($_REQUEST['export_meta'])?$_REQUEST['export_meta']:array();
			$data=isset($_REQUEST['export_data']) && is_array($_REQUEST['export_data']) && count($_REQUEST['export_data'])?$_REQUEST['export_data']:array();
			$pages=isset($_REQUEST['export_pages']) && is_array($_REQUEST['export_pages']) && count($_REQUEST['export_pages'])?$_REQUEST['export_pages']:array();
			$templates=isset($_REQUEST['export_templates']) && is_array($_REQUEST['export_templates']) && count($_REQUEST['export_templates'])?$_REQUEST['export_templates']:array();
			$ok=pageBuildExport($filename,$schema,$meta,$data,$pages,$templates);
			return;
		break;
		default:
			$tables=pageGetTables();
			$epages=pageGetPages();
			$etemplates=pageGetTemplates();
			setView('default',1);
		break;
	}
	setView('default',1);
?>
