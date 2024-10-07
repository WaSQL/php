<?php
/* 
	https://docs.gitlab.com/ee/api/api_resources.html

*/
$progpath=dirname(__FILE__);
function gitlabAddUpdate($params=array()){
	global $USER;
	global $CONFIG;
	$privateToken=commonCoalesce($params['privateToken'],$params['token'],$CONFIG['gitlab_token'],'');
	if(!strlen($privateToken)){return 'Error: not gitlab_token';}
    // GitLab API URL
    $baseUrl = commonCoalesce($params['baseUrl'],$params['baseurl'],$CONFIG['gitlab_baseurl'],'');
    if(!strlen($baseUrl)){return 'Error: no gitlab_baseUrl';}
    //projectid
    $projectId = commonCoalesce($params['projectId'],$params['projectid'],$CONFIG['gitlab_projectid'],'');
    if(!strlen($projectId)){return 'Error: no projectid';}
    //branch
    $branch = commonCoalesce($params['branch'],$CONFIG['gitlab_branch'],'main');
    if(!strlen($projectId)){return 'Error: no branch';}
    //afile
    $afile=commonCoalesce($params['afile'],$params['file'],'');
    if(!strlen($afile)){return 'Error: no file specified';}
    //commitMessage
    $commitMessage=commonCoalesce($params['commitMessage'],$params['message'],$CONFIG['gitlab_message'],'');
	if(!strlen($commitMessage)){return 'Error: no message';}
	//content
	$content=commonCoalesce($params['content'],$params['fileContent'],'');
	//if content is a file, get the contents of the file
	if(file_exists($content)){
		$content=file_get_contents($content);
	}
    // First, check if file exists. If so use PUT to update instead of POST to add
    $existingFile = false;
    $url = sprintf(
        '%s/projects/%s/repository/files/%s?ref=%s',
        $baseUrl,
        $projectId,
        urlencode($afile),
        $branch
    );
    $headers=array(
    	'PRIVATE-TOKEN:' . $privateToken,
        'Content-Type: application/json'
    );
    $params=array('-method'=>'GET','-json'=>1,'-headers'=>$headers);
    $post=postURL($url,$params);
    if (isset($post['json_array']['blob_id'])) {
    	//file Exists - change method to PUT
    	$params['-method']='PUT';
        $existingFile = true;
    }
    else{
    	$params['-method']='POST';
    }
    // Prepare the data for creating/updating file
    $data = [
        'branch' => $branch,
        'content' => $content,
        'commit_message' => "Username: {$USER['username']}, Host: {$_SERVER['HTTP_HOST']},  Message: ".$commitMessage,
    ];
    $json=encodeJSON($data);
    $post=postJSON($url,$json,$params);
    if(isset($post['json_array'])){return $post['json_array'];}
    return $post;
}

?>