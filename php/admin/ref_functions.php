<?php

function refShowList($tab){
	$listopts=array(
		'-hidesearch'=>1,
		'-tableclass'=>'wacss_table striped bordered condensed sticky',
		'-tdstyle'=>"font-family:monospace",
		'-tableheight'=>'80vh',
		'-anchormap'=>'name'
	);
	$mypath=getWasqlPath('php/admin');
	switch(strtolower($tab)){
		case 'php_functions':
			$recs=getCSVRecords("{$mypath}/ref_{$tab}.csv");
			$listopts['-anchormap']='category';
			$listopts['-anchormap_full']=1;
			//fix tags
			foreach($recs as $i=>$rec){
				$recs[$i]['category']=preg_replace('/[^a-z0-9\-]+/i','',$rec['category']);
				$recs[$i]['function']='<pre style="margin:0px;white-space:inherit;">'.encodeHtml($rec['function']).'</pre>';
				$recs[$i]['description']='<pre style="margin:0px;white-space:inherit;">'.encodeHtml($rec['description']).'</pre>';
			}
		break;
		case 'html_tags':
			$xrecs=getCSVRecords("{$mypath}/ref_{$tab}.csv");
			//fix tags
			$recs=array();
			foreach($xrecs as $i=>$rec){
				$recs[]=array(
					'name'=>preg_replace('/[^a-z0-9\-]+/i','',$rec['tag']),
					'tag'=>'<pre style="margin:0px;white-space:inherit;">'.encodeHtml($rec['tag']).'</pre>',
					'description'=>'<pre style="margin:0px;white-space:inherit;">'.encodeHtml($rec['description']).'</pre>'
				);
			}
		break;
		case 'html_attributes':
			$recs=getCSVRecords("{$mypath}/ref_{$tab}.csv");
			$listopts['-anchormap']='attribute';
			//fix tags
			foreach($recs as $i=>$rec){
				$recs[$i]['attribute']=preg_replace('/[^a-z0-9\-]+/i','',$rec['attribute']);
				$recs[$i]['belongs_to']='<pre style="margin:0px;white-space:inherit;">'.encodeHtml($rec['belongs_to']).'</pre>';
				$recs[$i]['description']='<pre style="margin:0px;white-space:inherit;">'.encodeHtml($rec['description']).'</pre>';
			}
		break;
		case 'html_events':
			$recs=getCSVRecords("{$mypath}/ref_{$tab}.csv");
			unset($listopts['-anchormap']);
			//fix tags
			foreach($recs as $i=>$rec){
				$recs[$i]['attribute']=preg_replace('/[^a-z0-9\-]+/i','',$rec['attribute']);
				$recs[$i]['description']='<pre style="margin:0px;white-space:inherit;">'.encodeHtml($rec['description']).'</pre>';
			}
		break;
		case 'css_styles':
			$recs=getCSVRecords("{$mypath}/ref_{$tab}.csv");
			$listopts['-anchormap']='attribute';
			//fix tags
			foreach($recs as $i=>$rec){
				$recs[$i]['attribute']=preg_replace('/[^a-z0-9\-]+/i','',$rec['attribute']);
				$recs[$i]['value']='<pre style="margin:0px;white-space:inherit;">'.encodeHtml($rec['value']).'</pre>';
				$recs[$i]['description']='<pre style="margin:0px;white-space:inherit;">'.encodeHtml($rec['description']).'</pre>';
			}
		break;
		case 'sql_reference':
			$recs=getCSVRecords("{$mypath}/ref_{$tab}.csv");
			$listopts['mysql_options']=array(
				'checkmark'=>1,
				'checkmark_icon'=>'icon-mark w_green',
				'displayname'=>'<span class="icon-database-mysql right5"></span> Mysql'
			);
			$listopts['postgresql_options']=array(
				'checkmark'=>1,
				'checkmark_icon'=>'icon-mark w_green',
				'displayname'=>'<span class="icon-database-postgresql right5"></span> Postgresql'
			);
			$listopts['mssql_options']=array(
				'checkmark'=>1,
				'checkmark_icon'=>'icon-mark w_green',
				'displayname'=>'<span class="icon-database-mssql right5"></span> MS SQL'
			);
			$listopts['oracle_options']=array(
				'checkmark'=>1,
				'checkmark_icon'=>'icon-mark w_green',
				'displayname'=>'<span class="icon-database-oracle right5"></span> Oracle'
			);
			$listopts['snowflake_options']=array(
				'checkmark'=>1,
				'checkmark_icon'=>'icon-mark w_green',
				'displayname'=>'<span class="icon-database-snowflake right5"></span> SnowFlake'
			);
			$listopts['hana_options']=array(
				'checkmark'=>1,
				'checkmark_icon'=>'icon-mark w_green',
				'displayname'=>'<span class="icon-database-hana right5"></span> HANA'
			);
			$listopts['sqlite_options']=array(
				'checkmark'=>1,
				'checkmark_icon'=>'icon-mark w_green',
				'displayname'=>'<span class="icon-database-sqlite right5"></span> Sqlite'
			);
			$listopts['ctree_options']=array(
				'checkmark'=>1,
				'checkmark_icon'=>'icon-mark w_green',
				'displayname'=>'<span class="icon-database-faircom right5"></span> cTree'
			);
			$listopts['msaccess_options']=array(
				'checkmark'=>1,
				'checkmark_icon'=>'icon-mark w_green',
				'displayname'=>'<span class="icon-database-msaccess right5"></span> MS Access'
			);
		break;
		default:
			$recs=getCSVRecords("{$mypath}/ref_{$tab}.csv");
			unset($listopts['-anchormap']);
			//fix tags
			foreach($recs as $i=>$rec){
				foreach($rec as $k=>$v){
					$recs[$i][$k]='<pre style="margin:0px;white-space:inherit;">'.encodeHtml($v).'</pre>';
				}
			}
		break;
	}

	$listopts['-list']=$recs;
	return databaseListRecords($listopts);
}
?>