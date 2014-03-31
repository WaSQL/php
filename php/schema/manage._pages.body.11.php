<?
/*filetype:php*/
	if(!isAdmin()){
    	echo "You must be logged in to manage pages";
    	return;
	}
	$pageopts=array('-table'=>"_pages",'-formfields'=>"name:menu:description,body",'-action'=>"/",'body_width'=>700,'description_width'=>300);
	if(isNum($_REQUEST['eid'])){
		$rec=getDBRecord(array('-table'=>"_pages",'_id'=>$_REQUEST['eid']));
		if(is_array($rec)){
			$pageopts['_id']=$rec['_id'];
			$pageopts['-action']="/".$rec['name'];
			echo '<div class="w_bold w_lblue w_biggest">Edit Page: '.$rec['name'].'</div>'."\n";
		}
		else{
        	echo '<div class="w_bold w_lblue w_biggest">Add New Page</div>'."\n";
		}
	}
	else{
        	echo '<div class="w_bold w_lblue w_biggest">Add New Page</div>'."\n";
		}
	echo addEditDBForm($pageopts);
?>
