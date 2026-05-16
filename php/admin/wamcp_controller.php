<?php
// WaSQL_auth header is extracted into $_REQUEST['_auth'] by user.php bootstrap
// before this controller runs, so isAdmin() validates it for both MCP and web UI.
global $DATABASE;
global $USER;
global $PASSTHRU;
global $wamcp_result;
//log the last request
$input = file_get_contents('php://input');
$data  = json_decode($input, true);
$logfile=getWaSQLTempPath().'/wamcp.log';
setFileContents($logfile,printValue(array("REQUEST",$_REQUEST,"PAYLOAD",$data,"SERVER",$_SERVER)));
// MCP JSON-RPC over HTTP
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    if (strpos($contentType, 'application/json') !== false) {
        if ($data && isset($data['jsonrpc']) && $data['jsonrpc'] === '2.0') {
            header('Content-Type: application/json');
            $id = isset($data['id']) ? $data['id'] : null;
            if (!isAdmin()) {
                echo json_encode(array('jsonrpc' => '2.0', 'id' => $id,
                    'error' => array('code' => -32001, 'message' => 'Unauthorized')));
                exit;
            }
            // db_id from URL path segment, then user's saved db, then first enabled db
            $db_id = (isset($PASSTHRU[0]) && strlen($PASSTHRU[0])) ? $PASSTHRU[0] : wamcpGetUserDb();
            if (!$db_id) {
                echo json_encode(array('jsonrpc' => '2.0', 'id' => $id,
                    'error' => array('code' => -32602, 'message' => 'No WaMCP-enabled database configured')));
                exit;
            }
            wamcpHandleMcpRequest($data, $db_id);
            exit;
        }
    }
}

// Web UI — same isAdmin() auth
if (!isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'error' => 'Authentication required'));
    exit;
}

$func = isset($_REQUEST['func']) ? strtolower(trim($_REQUEST['func'])) : 'list_databases';
$wamcp_result = array();

switch ($func) {
    case 'list_databases':
        $wamcp_result = wamcpListDatabases();
        header('Content-Type: application/json');
        echo json_encode(array('success' => true, 'databases' => $wamcp_result));
        exit;
    break;
    case 'use_database':
        $db_id = isset($_REQUEST['db_id']) ? $_REQUEST['db_id'] : '';
        $wamcp_result = wamcpUseDatabase($db_id);
        header('Content-Type: application/json');
        echo json_encode($wamcp_result);
        exit;
    break;
    case 'query':
        $db_id = isset($_REQUEST['db_id']) ? $_REQUEST['db_id'] : wamcpGetUserDb();
        $query = isset($_REQUEST['query']) ? $_REQUEST['query'] : '';
        if (empty($db_id)) {
            $wamcp_result = array('success' => false, 'error' => 'No database selected');
        } else {
            $wamcp_result = wamcpQueryDatabase($db_id, $query);
        }
        header('Content-Type: application/json');
        echo json_encode($wamcp_result);
        exit;
    break;
    case 'running_queries':
        $db_id = isset($_REQUEST['db_id']) ? $_REQUEST['db_id'] : wamcpGetUserDb();
        if (empty($db_id)) {
            $wamcp_result = array('success' => false, 'error' => 'No database selected');
        } else {
            $wamcp_result = wamcpSeeRunningQueries($db_id);
        }
        header('Content-Type: application/json');
        echo json_encode($wamcp_result);
        exit;
    break;
    default:
        $wamcp_result = array(
            'databases'  => wamcpListDatabases(),
            'current_db' => wamcpGetUserDb()
        );
        setView('default', 1);
        break;
}
?>
