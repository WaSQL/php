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
			if(!preg_match('/^/',$url)){
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
			$post=postURL("{$url}/php/admin.php",$params);
			if(preg_match('/\<backup\>(.+?)\<\/backup\>/is',$post['body'],$m)){
				$body=trim($m[1]);
				if(preg_match('/^error/is',$body)){
					$message=$body;
					break;
				}
				else{
					//download the file
					$url="{$url}/php/index.php?_pushfile={$body}";
					$filename=getFileName($url);
					$ext=getFileExtension($filename);
					$data=file($url);
					$afile="{$progpath}/temp/{$filename}";
					$ok=setFileContents($afile,$data);
					if(preg_match('/\.gz$/i',$afile)){
                        $ok=cmdResults("gunzip '{$afile}'");
                        //echo printValue($ok);
                        $afile=preg_replace('/\.gz$/i','',$file);
					}
					if(is_file($afile) && preg_match('/\.sql$/i',$afile)){
						$cmds=array(
							"mysql -h {$CONFIG['dbhost']} --user='{$CONFIG['dbuser']}' --password='{$CONFIG['dbpass']}' --execute=\"DROP DATABASE {$CONFIG['dbname']}; CREATE DATABASE {$CONFIG['dbname']} CHARACTER SET utf8 COLLATE utf8_general_ci;\"",
							"mysql -h {$CONFIG['dbhost']} --user='{$CONFIG['dbuser']}' --password='{$CONFIG['dbpass']}' --max_allowed_packet=128M --default-character-set=utf8 {$CONFIG['dbname']} < \"{$afile}\""
						);
						foreach($cmds as $cmd){
							//echo "<div>{$cmd}</div>\n";
							$ok=cmdResults($cmd);
							if(isset($ok['rtncode']) && $ok['rtncode'] != 0){
								$message= printValue($ok);
								break;
							}
						}
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
