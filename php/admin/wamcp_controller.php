<?php
// WaSQL_auth header is extracted into $_REQUEST['_auth'] by user.php bootstrap
// before this controller runs, so isAdmin() validates it for both MCP and web UI.
global $DATABASE;
global $USER;
global $PASSTHRU;
global $wamcp_result;
// Detect a JSON-RPC POST BEFORE the auth check so an auth failure can be
// reported in-protocol. A bare {success:false} body fails MCP client schema
// validation ("expected 2.0"), which hides the real cause from the user.
$mcp_data = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    if (strpos($contentType, 'application/json') !== false) {
        $input = file_get_contents('php://input');
        $data  = json_decode($input, true);
        if ($data && isset($data['jsonrpc']) && $data['jsonrpc'] === '2.0') {
            $mcp_data = $data;
        }
    }
}

// Web UI — same isAdmin() auth
if (!isAdmin()) {
    header('Content-Type: application/json');
    if (is_array($mcp_data)) {
        // Deliberately HTTP 200: a 401 makes MCP clients start an OAuth
        // discovery flow WaMCP does not implement (it authenticates via the
        // WaSQL_auth header), so the user sees an OAuth error instead of this.
        $msg = 'Authentication required - the WaSQL_auth header token is missing, invalid, or belongs to a user that does not exist in this database';
        if (isset($_REQUEST['_login_error']) && strlen($_REQUEST['_login_error'])) {
            $msg .= " ({$_REQUEST['_login_error']})";
        }
        echo json_encode(array('jsonrpc' => '2.0', 'id' => isset($mcp_data['id']) ? $mcp_data['id'] : null,
            'error' => array('code' => -32001, 'message' => $msg)));
        exit;
    }
    echo json_encode(array('success' => false, 'error' => 'Authentication required'));
    exit;
}

// MCP JSON-RPC over HTTP
if (is_array($mcp_data)) {
    header('Content-Type: application/json');
    $id = isset($mcp_data['id']) ? $mcp_data['id'] : null;
    // db_id from URL path segment, then user's saved db, then first enabled db
    $db_id = (isset($PASSTHRU[0]) && strlen($PASSTHRU[0])) ? $PASSTHRU[0] : wamcpGetUserDb();
    if (!$db_id) {
        echo json_encode(array('jsonrpc' => '2.0', 'id' => $id,
            'error' => array('code' => -32602, 'message' => 'No WaMCP-enabled database configured')));
        exit;
    }
    wamcpHandleMcpRequest($mcp_data, $db_id);
    exit;
}

$func = isset($_REQUEST['func']) ? strtolower(trim($_REQUEST['func'])) : '';
$wamcp_result = array();

switch ($func) {
    case 'list_databases':
        $wamcp_result = wamcpListDatabases();
        header('Content-Type: application/json');
        echo json_encode(array('success' => true, 'databases' => $wamcp_result));
        exit;
    break;
    case 'setdb':
        $db_id = isset($_REQUEST['db_id']) ? $_REQUEST['db_id'] : '';
        $wamcp_result = wamcpSetDatabase($db_id);
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
    case 'queries':
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
        loadExtras('markdown');
        $apath=getWaSQLPath('php/admin');
        $md = getFileContents("{$apath}/wamcp.md");
        $docs=markdown2Html($md);
        setView('default', 1);
    break;
}
?>
