<?php
	global $CONFIG;
	global $DATABASE;
	global $USER;
	global $DEBUG;
	global $setactive;
	$DEBUG=array();
	if(!isDBTable('sync_source')){
		$fields=array(
			'table_name'=>'varchar(125) NOT NULL',
			'table_id'=>'int NOT NULL',
			'source_domain'=>'varchar(255)',
			'source_id'=>'int NOT NULL',
			'last_sync'=>'date'
		);
		$ok=createDBTable('sync_source',$fields);
	}
	switch(strtolower($_REQUEST['func'])){
		case 'list':
			setView('sync_source_content',1);
			return;
		break;
		case 'compare':
			$_SESSION['sync_source']=array();
			$source=$_REQUEST['source'];
			$target=$_REQUEST['target'];
			$diffs=isNum($_REQUEST['diffs'])?$_REQUEST['diffs']:0;
			setView('compare',1);
			return;
		break;
		case 'addedit':
			$id=(integer)$_REQUEST['id'];
			setView('sync_source_addedit',1);
			return;
		break;
		case 'sync':
			$id=(integer)$_REQUEST['id'];
			$rec=getDBRecordById('sync_source',$id);
			$url="https://{$rec['source_domain']}/php/admin.php";
			$srec=sync_sourcePost($url,$_SESSION['sync_source_postopts']);
			foreach($srec as $k=>$v){
				if(strlen(trim($v))){
					$srec[$k]=base64_decode($v);
				}
			}
			$ok=editDBRecordById($rec['table_name'],$rec['table_id'],$srec);
			$ok=editDBRecordById('sync_source',$id,array(
				'last_sync'=>date('Y-m-d')
			));
			unset($_SESSION['sync_source_rec']);
			setView('sync_source_content',1);
			return;
		break;
		case 'check':
			if(!isset($_REQUEST['user']) || !isset($_REQUEST['pass'])){
				setView('authorize',1);
				return;
			}
			$rec=getDBRecordById('sync_source',(integer)$_REQUEST['id']);
			$url="https://{$rec['source_domain']}/php/admin.php";
			$load=array(
				'func'		=> 'auth',
				'username'	=> $_REQUEST['user'],
				'password'	=> $_REQUEST['pass'],
				'_login'	=> 1,
				'_pwe'		=> 1
			);
			$postopts=$load;
			$postopts['_menu']='synchronize';
			$postopts['load']=base64_encode(json_encode($load));
			$postopts['-follow']=1;
			$postopts['-nossl']=1;
			$postopts['_noguid']=1;
			$json=sync_sourcePost($url,$postopts);
			if(isset($json['auth']) && strlen($json['auth'])){
				$lrec=getDBRecordById($rec['table_name'],$rec['table_id']);
				$finfo=getDBFieldInfo($rec['table_name']);
				//echo printValue($finfo);exit;
				$fields=array();
				foreach($lrec as $k=>$v){
					if(isWasqlField($k)){continue;}
					if(in_array($finfo[$k]['_dbtype'],array('int','tinyint','smallint'))){
						continue;
					}
					$fields[]=$k;
				}
				$load=array(
					'func'		=> 'get_record',
					'table'		=> $rec['table_name'],
					'id'		=> $rec['source_id'],
					'fields'	=> implode(',',$fields)
				);
				$postopts=array(
					'_menu'		=> 'synchronize',
					'load'		=> base64_encode(json_encode($load)),
					'_auth'		=> encodeURL($json['auth']),
					'_noguid'	=> 1,
					'-follow'	=> 1,
					'-nossl'	=> 1
				);
				$srec=sync_sourcePost($url,$postopts);
				foreach($srec as $k=>$v){
					if(strlen(trim($v))){
						$srec[$k]=base64_decode($v);
					}
				}
				//check for differences
				$diff_fields=array();
				foreach($fields as $field){
					if(sha1($lrec[$field]) != sha1($srec[$field])){
						$diff_fields[]=$field;
					}
				}
				if(count($diff_fields)){
					$_SESSION['sync_source_postopts']=$postopts;
					$diffs=array();
					foreach($diff_fields as $field){
						$arr_source=preg_split('/[\r\n]+/', trim($lrec[$field]));
						$arr_target=preg_split('/[\r\n]+/', trim($srec[$field]));
						$diff = diffText($arr_source,$arr_target, $field,'',300);
						if(!strlen($diff)){continue;}
						if(preg_match('/No differences found/i',$diff)){continue;}
						$diffs[$field]=$diff;
					}
					$title="Table {$rec['table_name']}, Local: {$lrec['_id']}-{$lrec['name']}, Source: {$srec['_id']}-{$srec['name']}";
					setView('sync_diffs',1);
					return;
				}
				else{
					//no differences found
					unset($_SESSION['sync_source_rec']);
					setView('sync_no_diffs',1);
					return;
				}
			}
			else{
				setView(array('authorize','failed'),1);
				return;
			}
		break;
		case 'diff_details':
			$field=addslashes($_REQUEST['field']);
			setView('switch_field',1);
			return;
		break;
		case 'redraw_table_id':
			$recs=getDBRecords(array(
				'-table'=>$_REQUEST['value'],
				'-fields'=>'_id,name'
			));
			$opts=array();
			foreach($recs as $rec){
				$opts[$rec['_id']]="{$rec['_id']} - {$rec['name']}";
			}
			$params=array('message'=>'-- --');
			echo buildFormSelect('table_id',$opts,$params);
			exit;
		break;
		default:
			setView('default',1);
		break;
	}
?>
