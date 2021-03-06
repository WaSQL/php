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
			'sync_fields'=>'varchar(255)',
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
			$fields=preg_split('/\,+/',$rec['sync_fields']);
			foreach($fields as $i=>$field){
				$fields[$i]=trim($field);
			}
			$url="https://{$rec['source_domain']}/php/admin.php";
			$srec=sync_sourcePost($url,$_SESSION['sync_source_postopts']);
			foreach($srec as $k=>$v){
				if(strlen(trim($v))){
					$srec[$k]=base64_decode($v);
				}
			}
			$lrec=getDBRecordById($rec['table_name'],$rec['table_id']);
			$editopts=array();
			foreach($fields as $field){
				if(sha1($lrec[$field]) != sha1($srec[$field])){
					$editopts[$field]=$srec[$field];
				}
			}
			//echo printValue($fields).printValue(array_keys($editopts));exit;
			$ok=editDBRecordById($rec['table_name'],$rec['table_id'],$editopts);
			$ok=editDBRecordById('sync_source',$id,array(
				'last_sync'=>date('Y-m-d')
			));
			unset($_SESSION['sync_source_rec']);
			setView('sync_source_content',1);
			return;
		break;
		case 'check':
			$rec=getDBRecordById('sync_source',(integer)$_REQUEST['id']);
			$url="https://{$rec['source_domain']}/php/admin.php";
			$fields=preg_split('/\,+/',$rec['sync_fields']);
			foreach($fields as $i=>$field){
				$fields[$i]=trim($field);
			}
			if(isset($_SESSION['sync_check_postopts'])){
				$postopts=$_SESSION['sync_check_postopts'];
			}
			elseif(!isset($_REQUEST['user']) || !isset($_REQUEST['pass'])){
				setView('authorize',1);
				return;
			}
			else{
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
				$_SESSION['sync_check_postopts']=$postopts;
			}
			$json=sync_sourcePost($url,$postopts);
			if(isset($json['auth']) && strlen($json['auth'])){
				$lrec=getDBRecordById($rec['table_name'],$rec['table_id']);
				//echo "LREC".printValue($lrec).printValue($rec);exit;
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
						$local=preg_split('/[\r\n]+/', trim($lrec[$field]));
						$source=preg_split('/[\r\n]+/', trim($srec[$field]));
						//echo "{$field}: LOCAL:<xmp>{$lrec[$field]}</xmp>";
						//echo "source:<xmp>{$srec[$field]}</xmp>";exit;
						$diff = diffText($source,$local, $field,'',300);
						if(!strlen($diff)){continue;}
						if(preg_match('/No differences found/i',$diff)){continue;}
						$diffs[$field]=$diff;
					}
					$title="{$rec['table_name']}, Local: {$lrec['_id']}-{$lrec['name']}, Source: {$srec['_id']}-{$srec['name']}";
					setView('sync_diffs',1);
					return;
				}
				else{
					//no differences found
					$title="{$rec['table_name']}, Local: {$lrec['_id']}-{$lrec['name']}, Source: {$srec['_id']}-{$srec['name']}";
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
