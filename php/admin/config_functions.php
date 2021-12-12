<?php
loadExtras('translate');
/**  --- function commonCronCheckSchema
* @exclude  - this function is for internal use only and thus excluded from the manual
*/
function configCheckSchema(){
	if(!isDBTable('_config')){
		createWasqlTable('_config');
	}
	//_config
	$finfo=getDBFieldInfo('_config');
	//category
	if(!isset($finfo['category'])){
		$query="ALTER TABLE _config ADD category ".databaseDataType('varchar(200)')." NULL";
		$ok=executeSQL($query);
	}
}
function configShowlist($category,$opts=array()){
	global $configShowDifferentListCenter;
	$listopts=array(
		'-table'=>'_config',
		'category'=>$category,
		'-tableclass'=>'table condensed striped bordered',
		'-listview'=>getView('config_item'),
		'-simplesearch'=>1,
		'-onsubmit'=>"return pagingSubmit(this,'main_content');",
		'setprocessing'=>0,
		'-action'=>'/php/admin.php?_menu=config&func=showlist_ajax',
		'-results_eval'=>'configShowlistExtra',
		'-pretable'=>'<div class="w_bigger w_bold">'.ucfirst($category).'</div><div style="display:flex;justify-content:flex-start;align-items:flex-start;flex-wrap:wrap;">',
		'-posttable'=>'</div>'
	);
	if($category=='misc' or !strlen($category)){
		unset($listopts['category']);
		$listopts['-where']="ifnull(category,'')=''";
	}
	foreach($opts as $k=>$v){
		if(!strlen($v)){unset($listopts[$k]);}
		else{
			$listopts[$k]=$v;
		}
	}
	// $debug=$listopts;
	// unset($debug['-listview']);unset($debug['-pretable']);
	// $listopts['-posttable'].=printValue($debug);
	return databaseListRecords($listopts);
}
function configShowlistExtra($recs){
	$elid=0;
	foreach($recs as $i=>$rec){
		$elid+=1;
		$recs[$i]['dname']=ucwords(str_replace('_',' ',$rec['name']));
		if(!strlen($rec['default_value'])){
			$recs[$i]['default_value']='no default';
		}
		$recs[$i]['edit']='<a class="w_link" href="/php/admin.php?_table_=_config&_menu=edit&_id='.$rec['_id'].'"><span class="icon-edit w_small w_gray"></span></a>';
		if(strlen($rec['possible_values'])){
			if(stringBeginsWith($rec['possible_values'],'&')){
				$efield='current_value';
				$_REQUEST[$efield]=$rec[$efield];
				$evalstr='<?='.preg_replace('/^\&/','',$rec['possible_values']).'?>';
				$recs[$i]['cvedit']=evalPHP($evalstr);
			}
			elseif($rec['possible_values']=='0=Off,1=On'){
				$cparams=array(
					'value'=>$rec['current_value'],
					'-formname'=>'editfieldform_'.$rec['_id'],
					'style'=>'width:100%;'
				);
				$recs[$i]['cvedit']=buildFormSelectOnOff('current_value',$cparams);
			}
			else{
				$cvals=preg_split('/\,/',$rec['possible_values']);
				$copts=array();
				foreach($cvals as $cval){
					$parts=preg_split('/\=/',$cval,2);
					if(count($parts)==2){
						$t=trim($parts[0]);
						$d=trim($parts[1]);
						$copts[$t]=$d;
					}
					else{
						$t=trim($parts[0]);
						$copts[$t]=$t;
					}
				}
				$cparams=array(
					'message'=>' --- ',
					'value'=>$rec['current_value'],
					'-formname'=>'editfieldform_'.$rec['_id'],
					'style'=>'width:100%;'
				);
				$recs[$i]['cvedit']=buildFormSelect('current_value',$copts,$cparams);
			}
		}
		else{
			$cparams=array(
				'value'=>$rec['current_value'],
				'style'=>'width:100%',
				'class'=>'input w_small'
			);
			$recs[$i]['cvedit']=buildFormText('current_value',$cparams);
		}
	}
	return $recs;
}
?>
