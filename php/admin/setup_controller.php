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
			if(!isset($_REQUEST['clone_username']) || !strlen($_REQUEST['clone_username'])){
				$message='<h2>Missing Username</h2>';
				break;
			}
			if(!isset($_REQUEST['clone_password']) || !strlen($_REQUEST['clone_password'])){
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
				'username'	=> addslashes($_REQUEST['clone_username']),
				'password'	=> addslashes($_REQUEST['clone_password']),
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
					//echo "<div>afile: {$afile}</div>\n";
					if(preg_match('/\.gz$/i',$afile)){
						$cmd="gunzip \"{$afile}\"";
						$out=shell_exec($cmd);
						//echo "<div>{$cmd}</div>\n";
                        $afile=preg_replace('/\.gz$/i','',$afile);
					}
					echo "<div>afile (sql): {$afile}</div>\n";
					if(is_file($afile) && preg_match('/\.sql$/i',$afile)){
						$cmds=array(
							"mysql -h {$CONFIG['dbhost']} -u {$CONFIG['dbuser']} -p\"{$CONFIG['dbpass']}\" --execute=\"DROP DATABASE {$CONFIG['dbname']}; CREATE DATABASE {$CONFIG['dbname']} CHARACTER SET utf8 COLLATE utf8_general_ci;\"",
							"mysql -h {$CONFIG['dbhost']} -u {$CONFIG['dbuser']} -p\"{$CONFIG['dbpass']}\" --max_allowed_packet=128M --default-character-set=utf8 {$CONFIG['dbname']} < \"{$afile}\""
						);
						foreach($cmds as $cmd){
							//echo "<div>{$cmd}</div>\n";
							$ok=shell_exec($cmd);
						}
					}
					else{
						$message="Failed: {$afile} not found";
						break;
					}
				}
			}
			//redirect to site
			header('Location: /');
			$message='<h1 class="w_success">Site Cloned</h1>';
		break;
		default:
			setView('default');
		break;
	}
?>
