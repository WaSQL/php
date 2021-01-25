<?php

function refShowList($tab){
	$listopts=array(
		'-hidesearch'=>1,
		'-tableclass'=>'table striped bordered condensed is-sticky',
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
				$recs[$i]['function']='<xmp style="margin:0px;white-space:inherit;">'.$rec['function'].'</xmp>';
				$recs[$i]['description']='<xmp style="margin:0px;white-space:inherit;">'.$rec['description'].'</xmp>';
			}
		break;
		case 'html_tags':
			$xrecs=getCSVRecords("{$mypath}/ref_{$tab}.csv");
			//fix tags
			$recs=array();
			foreach($xrecs as $i=>$rec){
				$recs[]=array(
					'name'=>preg_replace('/[^a-z0-9\-]+/i','',$rec['tag']),
					'tag'=>'<xmp style="margin:0px;white-space:inherit;">'.$rec['tag'].'</xmp>',
					'description'=>'<xmp style="margin:0px;white-space:inherit;">'.$rec['description'].'</xmp>'
				);
			}
		break;
		case 'html_attributes':
			$recs=getCSVRecords("{$mypath}/ref_{$tab}.csv");
			$listopts['-anchormap']='attribute';
			//fix tags
			foreach($recs as $i=>$rec){
				$recs[$i]['attribute']=preg_replace('/[^a-z0-9\-]+/i','',$rec['attribute']);
				$recs[$i]['belongs_to']='<xmp style="margin:0px;white-space:inherit;">'.$rec['belongs_to'].'</xmp>';
				$recs[$i]['description']='<xmp style="margin:0px;white-space:inherit;">'.$rec['description'].'</xmp>';
			}
		break;
		case 'html_events':
			$recs=getCSVRecords("{$mypath}/ref_{$tab}.csv");
			unset($listopts['-anchormap']);
			//fix tags
			foreach($recs as $i=>$rec){
				$recs[$i]['attribute']=preg_replace('/[^a-z0-9\-]+/i','',$rec['attribute']);
				$recs[$i]['description']='<xmp style="margin:0px;white-space:inherit;">'.$rec['description'].'</xmp>';
			}
		break;
		case 'css_styles':
			$recs=getCSVRecords("{$mypath}/ref_{$tab}.csv");
			$listopts['-anchormap']='attribute';
			//fix tags
			foreach($recs as $i=>$rec){
				$recs[$i]['attribute']=preg_replace('/[^a-z0-9\-]+/i','',$rec['attribute']);
				$recs[$i]['value']='<xmp style="margin:0px;white-space:inherit;">'.$rec['value'].'</xmp>';
				$recs[$i]['description']='<xmp style="margin:0px;white-space:inherit;">'.$rec['description'].'</xmp>';
			}
		break;
		default:
			$recs=getCSVRecords("{$mypath}/ref_{$tab}.csv");
			unset($listopts['-anchormap']);
			//fix tags
			foreach($recs as $i=>$rec){
				foreach($rec as $k=>$v){
					$recs[$i][$k]="<xmp style=\"margin:0px;white-space:inherit;\">{$v}</xmp>";
				}
			}
		break;
	}

	$listopts['-list']=$recs;
	return databaseListRecords($listopts);
}
?>