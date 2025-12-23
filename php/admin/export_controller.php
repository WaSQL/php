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
			//Validate filename - prevent path traversal
			if(!isset($_REQUEST['name']) || !strlen(trim($_REQUEST['name']))){
				echo '<div class="w_bold w_danger">Filename is required</div>';
				return;
			}
			$filename=trim($_REQUEST['name']);
			if(!exportValidateFilename($filename)){
				echo '<div class="w_bold w_danger">Invalid filename. Only alphanumeric characters, underscores, hyphens, and dots are allowed.</div>';
				return;
			}

			//Get and validate arrays
			$schema=isset($_REQUEST['export_schema']) && is_array($_REQUEST['export_schema'])?$_REQUEST['export_schema']:array();
			$meta=isset($_REQUEST['export_meta']) && is_array($_REQUEST['export_meta'])?$_REQUEST['export_meta']:array();
			$data=isset($_REQUEST['export_data']) && is_array($_REQUEST['export_data'])?$_REQUEST['export_data']:array();
			$pages=isset($_REQUEST['export_pages']) && is_array($_REQUEST['export_pages'])?$_REQUEST['export_pages']:array();
			$templates=isset($_REQUEST['export_templates']) && is_array($_REQUEST['export_templates'])?$_REQUEST['export_templates']:array();

			//Validate table names
			$validTables=pageGetTables();
			$schema=exportValidateTableArray($schema,$validTables);
			$meta=exportValidateTableArray($meta,$validTables);
			$data=exportValidateTableArray($data,$validTables);

			//Validate page and template IDs
			$pages=exportValidateIdArray($pages);
			$templates=exportValidateIdArray($templates);

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
