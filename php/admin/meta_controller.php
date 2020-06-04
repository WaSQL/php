<?php
	global $CONFIG;
	global $SETTINGS;
	if(!isset($CONFIG['admin_menu_color'])){
		$CONFIG['admin_menu_color']='gray';
	}
	setView('default');
	$finfo=getDBFieldInfo('_pages');
	$mfields=array('meta_image','meta_title','meta_description','meta_keywords');
	if(!isset($finfo['meta_image'])){
		$sql="alter table _pages add meta_image varchar(255) NULL";
		$ok=executeSQL($sql);
		$adir="{$_SERVER['DOCUMENT_ROOT']}/images";
		if(!is_dir($adir)){
			mkdir($adir,0777,1);
		}
		$id=addDBRecord(array('-table'=>"_fielddata",
			'tablename'		=> '_pages',
			'fieldname'		=> 'meta_image',
			'inputtype'		=> 'file',
			'width'			=> 400,
			'defaultval'	=> 'images',
			'required'		=> 0
		));
	}
	if(!isset($finfo['meta_title'])){
		$sql="alter table _pages add meta_title varchar(255) NULL";
		$ok=executeSQL($sql);
		$id=addDBRecord(array('-table'=>"_fielddata",
			'tablename'		=> '_pages',
			'fieldname'		=> 'meta_title',
			'inputtype'		=> 'text',
			'width'			=> 400,
			'max'			=> 255,
			'required'		=> 0
		));
	}
	if(!isset($finfo['meta_description'])){
		$sql="alter table _pages add meta_description varchar(160) NULL";
		$ok=executeSQL($sql);
		$id=addDBRecord(array('-table'=>"_fielddata",
			'tablename'		=> '_pages',
			'fieldname'		=> 'meta_description',
			'inputtype'		=> 'textarea',
			'width'			=> 400,
			'height'		=> 100,
			'max'			=> 160,
			'min'			=> 50,
			'required'		=> 0
		));
	}
	if(!isset($finfo['meta_keywords'])){
		$sql="alter table _pages add meta_keywords varchar(255) NULL";
		$ok=executeSQL($sql);
		$id=addDBRecord(array('-table'=>"_fielddata",
			'tablename'		=> '_pages',
			'fieldname'		=> 'meta_keywords',
			'inputtype'		=> 'textarea',
			'width'			=> 400,
			'height'		=> 100,
			'max'			=> 255,
			'required'		=> 0
		));
	}
?>