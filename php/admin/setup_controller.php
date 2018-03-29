<?php
	$progpath=dirname(__FILE__);
	global $CONFIG;
	switch(strtolower($_REQUEST['starttype'])){
		case 'blank':
			include_once("$progpath/user.php");
			include_once("$progpath/schema.php");
			$CONFIG['starttype']='blank';
			$ok=createWasqlTables();
			header('Location: /');
			$message='<h1 class="w_success">Blank Site Created.</h1>';
		break;
		case 'sample':
			include_once("$progpath/user.php");
			include_once("$progpath/schema.php");
			$CONFIG['starttype']='sample';
			$ok=createWasqlTables();
			header('Location: /');
			$message='<h1 class="w_success">Sample Site Created</h1>';
		break;
		case 'clone':
			//make sure url, user, and pass are there
			if(!isset($_REQUEST['clone_url']) || !strlen($_REQUEST['clone_url'])){
				$message='<h2>Missing URL</h2>';
				break;
			}
			if(!isset($_REQUEST['clone_un']) || !strlen($_REQUEST['clone_un'])){
				$message='<h2>Missing Username</h2>';
				break;
			}
			if(!isset($_REQUEST['clone_pw']) || !strlen($_REQUEST['clone_pw'])){
				$message='<h2>Missing Password</h2>';
				break;
			}
			//request a full dump file from URL
			$url=$_REQUEST['clone_url'];
			if(!preg_match('/^http/',$url)){
				$url="https://{$url}";
			}
			//request the dump file
			//build the load
			$params=array(
				'func'		=> 'backup',
				'_menu'		=> 'backups',
				'push'		=> 'filename',
				'username'	=> addslashes($_REQUEST['clone_un']),
				'password'	=> addslashes($_REQUEST['clone_pw']),
				'_login'	=> 1,
				'_pwe'		=> 1
			);
			//import dump file
			//echo "{$url}/php/admin.php".printValue($params);
			$post=postURL("{$url}/php/admin.php",$params);
			//echo $post['body'];exit;
			if(preg_match('/\<backup\>(.+?)\<\/backup\>/is',$post['body'],$m)){
				$body=trim($m[1]);
				if(preg_match('/^error/is',$body)){
					$message=$body;
					break;
				}
				else{
					//download the file
					$url="{$url}/php/index.php?_pushfile={$body}";
					//echo "<div>{$url}</div>".PHP_EOL;
					$rfile=base64_decode($body);
					//check to see if it is local
					if(file_exists($rfile)){$afile=$rfile;}
					else{
						//echo "<div>{$rfile}</div>".PHP_EOL;exit;
						$filename=getFileName($rfile);
						$ext=getFileExtension($filename);					
						$afile="{$progpath}/temp/{$filename}";
						$fp = fopen ($afile, 'w+');
						$ch = curl_init();
						curl_setopt( $ch, CURLOPT_URL, $url );
						curl_setopt( $ch, CURLOPT_BINARYTRANSFER, true );
						curl_setopt( $ch, CURLOPT_RETURNTRANSFER, false );
						curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
						curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 300 );
						curl_setopt( $ch, CURLOPT_FILE, $fp );
						curl_exec( $ch );
						curl_close( $ch );
						fclose( $fp );
						$afile=realpath($afile);
					}
					//echo "<div>afile: {$afile}</div>\n";
					if(preg_match('/\.gz$/i',$afile)){
						$cmd="gunzip \"{$afile}\"";
						$out=shell_exec($cmd);
						//echo "<div>{$cmd}</div>\n";
                        $afile=preg_replace('/\.gz$/i','',$afile);
					}
					//echo "<div>afile (sql): {$afile}</div>\n";
					if(is_file($afile) && preg_match('/\.sql$/i',$afile)){
						$message='';
						//$afile=str_replace("\\","\\\\",$afile);
						//$message=$afile;break;
						$mysql_cmd=isset($CONFIG['mysql_command'])?$CONFIG['mysql_command']:'mysql';
						$cmds=array(
							"{$$mysql_cmd} -h {$CONFIG['dbhost']} -u {$CONFIG['dbuser']} -p\"{$CONFIG['dbpass']}\" --execute=\"DROP DATABASE {$CONFIG['dbname']}; CREATE DATABASE {$CONFIG['dbname']} CHARACTER SET utf8 COLLATE utf8_general_ci;\"",
							"{$$mysql_cmd} -h {$CONFIG['dbhost']} -u {$CONFIG['dbuser']} -p\"{$CONFIG['dbpass']}\" --max_allowed_packet=128M --default-character-set=utf8 {$CONFIG['dbname']} < \"{$afile}\" 2>&1"
						);
						foreach($cmds as $cmd){
							//echo "<div>{$cmd}</div>\n";
							$out=shell_exec($cmd);
							$message.="<div>{$cmd}</div>";
							$message.="<div>{$out}</div>";
							// $ok.PHP_EOL;
						}
					}
					else{
						$message="Failed: {$afile} not found";
					}
				}
			}
		break;
		default:
			setView('default');
		break;
	}
?>
