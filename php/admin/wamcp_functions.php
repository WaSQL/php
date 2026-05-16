<?php
/**
 * WaMCP Functions - MCP server for WaSQL databases
 */

// ── Database helpers ──────────────────────────────────────────────────────────

// Returns the db_id of the first wamcp-enabled database, or empty string if none.
function wamcpDefaultDatabase() {
    $list = wamcpListDatabases();
    return !empty($list) ? $list[0]['id'] : '';
}

// ── MCP Request Router ────────────────────────────────────────────────────────

function wamcpHandleMcpRequest($request, $db_id) {
    $method = isset($request['method']) ? $request['method'] : '';
    $id     = isset($request['id'])     ? $request['id']     : null;
    $params = isset($request['params']) ? $request['params'] : array();

    wamcpLog("MCP {$method} db={$db_id}");

    switch ($method) {
        case 'initialize':
            wamcpSend($id, array(
                'protocolVersion' => '2024-11-05',
                'capabilities'    => array('tools' => new stdClass()),
                'serverInfo'      => array('name' => 'wamcp', 'version' => '1.0.0')
            ));
            return;

        case 'notifications/initialized':
            echo '{}';
            return;

        case 'ping':
            wamcpSend($id, array());
            return;

        case 'tools/list':
            wamcpSend($id, array('tools' => wamcpGetToolsList()));
            return;

        case 'tools/call':
            $name   = isset($params['name'])      ? $params['name']      : '';
            $args   = isset($params['arguments']) ? $params['arguments'] : array();
            wamcpSend($id, wamcpDispatchTool($name, $args, $db_id));
            return;

        default:
            if ($id !== null) {
                wamcpSendError($id, -32601, "Method not found: {$method}");
            } else {
                echo '{}';
            }
            return;
    }
}

// ── Tool Registry ─────────────────────────────────────────────────────────────

function wamcpGetToolsList() {
    $none = new stdClass();
    $tools = array(
        array(
            'name'        => 'db',
            'description' => 'Display current database connection info (host, user, version, charset).',
            'inputSchema' => array('type' => 'object', 'properties' => $none)
        ),
        array(
            'name'        => 'ddl',
            'description' => 'Return the CREATE TABLE statement for a specified table.',
            'inputSchema' => array(
                'type'       => 'object',
                'properties' => array('tablename' => array('type' => 'string', 'description' => 'Table name')),
                'required'   => array('tablename')
            )
        ),
        array(
            'name'        => 'tables',
            'description' => 'List tables in the active database, optionally filtered by a substring.',
            'inputSchema' => array(
                'type'       => 'object',
                'properties' => array('filter' => array('type' => 'string', 'description' => 'Optional substring filter on table name'))
            )
        ),
        array(
            'name'        => 'fields',
            'description' => 'List columns for a table, optionally filtered by a substring. Alias: fld.',
            'inputSchema' => array(
                'type'       => 'object',
                'properties' => array(
                    'tablename' => array('type' => 'string'),
                    'filter'    => array('type' => 'string', 'description' => 'Optional substring filter on column name')
                ),
                'required' => array('tablename')
            )
        ),
        array(
            'name'        => 'fld',
            'description' => 'Alias for fields.',
            'inputSchema' => array(
                'type'       => 'object',
                'properties' => array(
                    'tablename' => array('type' => 'string'),
                    'filter'    => array('type' => 'string')
                ),
                'required' => array('tablename')
            )
        ),
        array(
            'name'        => 'idx',
            'description' => 'Return all indexes defined on a specified table.',
            'inputSchema' => array(
                'type'       => 'object',
                'properties' => array('tablename' => array('type' => 'string')),
                'required'   => array('tablename')
            )
        ),
        array(
            'name'        => 'running_queries',
            'description' => 'Show currently executing queries (excludes idle/Sleep connections).',
            'inputSchema' => array('type' => 'object', 'properties' => $none)
        ),
        array(
            'name'        => 'sessions',
            'description' => 'Show all active database sessions including idle ones.',
            'inputSchema' => array('type' => 'object', 'properties' => $none)
        ),
        array(
            'name'        => 'table_locks',
            'description' => 'Show tables currently held under a lock.',
            'inputSchema' => array('type' => 'object', 'properties' => $none)
        ),
        array(
            'name'        => 'views',
            'description' => 'List all views in the active database.',
            'inputSchema' => array('type' => 'object', 'properties' => $none)
        ),
        array(
            'name'        => 'indexes',
            'description' => 'List all indexes across every table in the active database.',
            'inputSchema' => array('type' => 'object', 'properties' => $none)
        ),
        array(
            'name'        => 'functions',
            'description' => 'List all stored functions in the active database.',
            'inputSchema' => array('type' => 'object', 'properties' => $none)
        ),
        array(
            'name'        => 'procedures',
            'description' => 'List all stored procedures in the active database.',
            'inputSchema' => array('type' => 'object', 'properties' => $none)
        ),
        array(
            'name'        => 'query',
            'description' => 'Execute an arbitrary SQL query and return the result set.',
            'inputSchema' => array(
                'type'       => 'object',
                'properties' => array('sql' => array('type' => 'string', 'description' => 'SQL statement to execute')),
                'required'   => array('sql')
            )
        ),
        array(
            'name'        => 'databases',
            'description' => 'List only the databases that are enabled for WaMCP (have the wamcp attribute set). Do NOT use SHOW DATABASES — always call this tool to list available databases.',
            'inputSchema' => array('type' => 'object', 'properties' => $none)
        )
    );

    $db_id_prop = array('type' => 'string', 'description' => 'Optional: target database ID. Call the databases tool to list available IDs.');
    foreach ($tools as &$tool) {
        if ($tool['name'] === 'databases') continue;
        $props = $tool['inputSchema']['properties'];
        if ($props instanceof stdClass) {
            $tool['inputSchema']['properties'] = array('db_id' => $db_id_prop);
        } else {
            $tool['inputSchema']['properties']['db_id'] = $db_id_prop;
        }
    }
    unset($tool);
    return $tools;
}

// ── Tool Dispatcher ───────────────────────────────────────────────────────────

function wamcpDispatchTool($name, $args, $db_id) {
    if ($name === 'databases') {
        return wamcpToolDatabases();
    }
    if (!empty($args['db_id'])) {
        $db_id = $args['db_id'];
    }
    if (!wamcpGetDatabase($db_id)) {
        return wamcpToolError("Database '{$db_id}' not found or not enabled for WaMCP.");
    }
    $tablename = isset($args['tablename']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $args['tablename']) : '';
    $filter    = isset($args['filter'])    ? $args['filter']    : '';
    $dbname    = wamcpGetDbName($db_id);

    switch ($name) {
        case 'db':
            return wamcpToolDb($db_id, $dbname);
        case 'ddl':
            if (!$tablename) return wamcpToolError('tablename is required');
            return wamcpToolDdl($db_id, $tablename);
        case 'tables':
            return wamcpToolTables($db_id, $dbname, $filter);
        case 'fields':
        case 'fld':
            if (!$tablename) return wamcpToolError('tablename is required');
            return wamcpToolFields($db_id, $dbname, $tablename, $filter);
        case 'idx':
            if (!$tablename) return wamcpToolError('tablename is required');
            return wamcpToolIdx($db_id, $tablename);
        case 'running_queries':
            return wamcpToolRunningQueries($db_id);
        case 'sessions':
            return wamcpToolSessions($db_id);
        case 'table_locks':
            return wamcpToolTableLocks($db_id);
        case 'views':
            return wamcpToolViews($db_id, $dbname);
        case 'indexes':
            return wamcpToolIndexes($db_id, $dbname);
        case 'functions':
            return wamcpToolRoutines($db_id, $dbname, 'FUNCTION');
        case 'procedures':
            return wamcpToolRoutines($db_id, $dbname, 'PROCEDURE');
        case 'query':
            $sql = isset($args['sql']) ? $args['sql'] : '';
            if (!$sql) return wamcpToolError('sql is required');
            return wamcpToolQuery($db_id, $sql);
        case 'databases':
            return wamcpToolDatabases();
        default:
            return wamcpToolError("Unknown tool: {$name}");
    }
}

// ── Tool Implementations ──────────────────────────────────────────────────────

function wamcpToolDb($db_id, $dbname) {
    $sql  = "SELECT DATABASE() AS db_name, USER() AS connected_user,
                    @@hostname AS host, @@version AS version,
                    @@character_set_server AS charset, @@collation_server AS collation";
    $rows = dbQueryResults($db_id, $sql);
    if (!is_array($rows)) return wamcpToolError('Could not retrieve connection info');
    return wamcpToolText(wamcpToMarkdownTable($rows));
}

function wamcpToolDdl($db_id, $tablename) {
    $rows = dbQueryResults($db_id, "SHOW CREATE TABLE `{$tablename}`");
    if (!is_array($rows) || empty($rows)) return wamcpToolError("Table '{$tablename}' not found");
    $row = $rows[0];
    $ddl = isset($row['Create Table']) ? $row['Create Table'] : (isset($row[1]) ? $row[1] : '');
    return wamcpToolText("```sql\n{$ddl}\n```");
}

function wamcpToolTables($db_id, $dbname, $filter) {
    $where = "TABLE_SCHEMA = " . wamcpQ($dbname) . " AND TABLE_TYPE = 'BASE TABLE'";
    if ($filter) $where .= " AND TABLE_NAME LIKE " . wamcpQ("%{$filter}%");
    $sql  = "SELECT TABLE_NAME, ENGINE, TABLE_ROWS,
                    ROUND(DATA_LENGTH/1024/1024,2) AS data_mb, CREATE_TIME
             FROM information_schema.TABLES
             WHERE {$where}
             ORDER BY TABLE_NAME";
    $rows = dbQueryResults($db_id, $sql);
    if (!is_array($rows)) return wamcpToolError('Could not retrieve tables');
    return wamcpToolText(wamcpToMarkdownTable($rows));
}

function wamcpToolFields($db_id, $dbname, $tablename, $filter) {
    $where = "TABLE_SCHEMA = " . wamcpQ($dbname) . " AND TABLE_NAME = " . wamcpQ($tablename);
    if ($filter) $where .= " AND COLUMN_NAME LIKE " . wamcpQ("%{$filter}%");
    $sql  = "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY,
                    COLUMN_DEFAULT, EXTRA, COLUMN_COMMENT
             FROM information_schema.COLUMNS
             WHERE {$where}
             ORDER BY ORDINAL_POSITION";
    $rows = dbQueryResults($db_id, $sql);
    if (!is_array($rows)) return wamcpToolError('Could not retrieve fields');
    return wamcpToolText(wamcpToMarkdownTable($rows));
}

function wamcpToolIdx($db_id, $tablename) {
    $rows = dbQueryResults($db_id, "SHOW INDEX FROM `{$tablename}`");
    if (!is_array($rows)) return wamcpToolError("Could not retrieve indexes for '{$tablename}'");
    return wamcpToolText(wamcpToMarkdownTable($rows));
}

function wamcpToolRunningQueries($db_id) {
    $sql  = "SELECT ID, USER, HOST, DB, COMMAND, TIME, STATE, LEFT(INFO,200) AS query_preview
             FROM information_schema.PROCESSLIST
             WHERE COMMAND != 'Sleep'
             ORDER BY TIME DESC";
    $rows = dbQueryResults($db_id, $sql);
    if (!is_array($rows)) return wamcpToolError('Could not retrieve running queries');
    return wamcpToolText(wamcpToMarkdownTable($rows));
}

function wamcpToolSessions($db_id) {
    $sql  = "SELECT ID, USER, HOST, DB, COMMAND, TIME, STATE, LEFT(INFO,100) AS query_preview
             FROM information_schema.PROCESSLIST
             ORDER BY TIME DESC";
    $rows = dbQueryResults($db_id, $sql);
    if (!is_array($rows)) return wamcpToolError('Could not retrieve sessions');
    return wamcpToolText(wamcpToMarkdownTable($rows));
}

function wamcpToolTableLocks($db_id) {
    $sql  = "SELECT r.trx_id AS waiting_trx, r.trx_mysql_thread_id AS waiting_thread,
                    LEFT(r.trx_query,100) AS waiting_query,
                    b.trx_id AS blocking_trx, b.trx_mysql_thread_id AS blocking_thread,
                    LEFT(b.trx_query,100) AS blocking_query
             FROM information_schema.INNODB_TRX b
             JOIN information_schema.INNODB_TRX r
               ON r.trx_wait_started IS NOT NULL AND b.trx_id != r.trx_id";
    $rows = dbQueryResults($db_id, $sql);
    if (!is_array($rows)) {
        $rows = dbQueryResults($db_id, "SHOW OPEN TABLES WHERE In_use > 0");
    }
    if (!is_array($rows)) return wamcpToolError('Could not retrieve table locks');
    if (empty($rows)) return wamcpToolText('No table locks detected.');
    return wamcpToolText(wamcpToMarkdownTable($rows));
}

function wamcpToolViews($db_id, $dbname) {
    $sql  = "SELECT TABLE_NAME, IS_UPDATABLE, DEFINER, SECURITY_TYPE,
                    LEFT(VIEW_DEFINITION,300) AS definition_preview
             FROM information_schema.VIEWS
             WHERE TABLE_SCHEMA = " . wamcpQ($dbname) . "
             ORDER BY TABLE_NAME";
    $rows = dbQueryResults($db_id, $sql);
    if (!is_array($rows)) return wamcpToolError('Could not retrieve views');
    return wamcpToolText(wamcpToMarkdownTable($rows));
}

function wamcpToolIndexes($db_id, $dbname) {
    $sql  = "SELECT TABLE_NAME, INDEX_NAME,
                    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columns,
                    NON_UNIQUE, INDEX_TYPE
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = " . wamcpQ($dbname) . "
             GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE, INDEX_TYPE
             ORDER BY TABLE_NAME, INDEX_NAME";
    $rows = dbQueryResults($db_id, $sql);
    if (!is_array($rows)) return wamcpToolError('Could not retrieve indexes');
    return wamcpToolText(wamcpToMarkdownTable($rows));
}

function wamcpToolRoutines($db_id, $dbname, $type) {
    $sql  = "SELECT ROUTINE_NAME, DATA_TYPE AS return_type, SECURITY_TYPE, DEFINER,
                    LEFT(ROUTINE_DEFINITION,300) AS body_preview
             FROM information_schema.ROUTINES
             WHERE ROUTINE_SCHEMA = " . wamcpQ($dbname) . "
               AND ROUTINE_TYPE = " . wamcpQ($type) . "
             ORDER BY ROUTINE_NAME";
    $rows = dbQueryResults($db_id, $sql);
    if (!is_array($rows)) return wamcpToolError("Could not retrieve {$type}s");
    return wamcpToolText(wamcpToMarkdownTable($rows));
}

function wamcpToolDatabases() {
    $list = wamcpListDatabases();
    if (empty($list)) return wamcpToolText('No WaMCP-enabled databases configured.');
    $rows = array();
    foreach ($list as $db) {
        $rows[] = array('id' => $db['id'], 'wamcp_name' => $db['name'], 'display_name' => $db['displayname']);
    }
    return wamcpToolText(wamcpToMarkdownTable($rows));
}

function wamcpToolQuery($db_id, $sql) {
    try {
        $rows = dbQueryResults($db_id, $sql);
        if (is_array($rows) && !empty($rows)) {
            return wamcpToolText(count($rows) . " rows returned.\n\n" . wamcpToMarkdownTable($rows));
        }
        return wamcpToolText('Query OK. No rows returned.');
    } catch (Exception $e) {
        return wamcpToolError($e->getMessage());
    }
}

// ── Response Helpers ──────────────────────────────────────────────────────────

function wamcpSend($id, $result) {
    header('Content-Type: application/json');
    echo json_encode(array('jsonrpc' => '2.0', 'id' => $id, 'result' => $result));
}

function wamcpSendError($id, $code, $msg) {
    header('Content-Type: application/json');
    echo json_encode(array('jsonrpc' => '2.0', 'id' => $id,
        'error' => array('code' => $code, 'message' => $msg)));
}

function wamcpToolText($text) {
    return array('content' => array(array('type' => 'text', 'text' => $text)), 'isError' => false);
}

function wamcpToolError($msg) {
    return array('content' => array(array('type' => 'text', 'text' => $msg)), 'isError' => true);
}

function wamcpToMarkdownTable($rows) {
    if (empty($rows)) return '_No results._';
    $headers = array_keys($rows[0]);
    $lines   = array(
        '| ' . implode(' | ', $headers) . ' |',
        '| ' . implode(' | ', array_fill(0, count($headers), '---')) . ' |'
    );
    foreach ($rows as $row) {
        $cells = array();
        foreach ($row as $val) {
            $cells[] = str_replace('|', '\\|', (string)$val);
        }
        $lines[] = '| ' . implode(' | ', $cells) . ' |';
    }
    return implode("\n", $lines);
}

// Safe single-quote escape for SQL string literals (used only for config-sourced values).
function wamcpQ($value) {
    return "'" . addslashes($value) . "'";
}

function wamcpGetDbName($db_id) {
    global $DATABASE;
    return isset($DATABASE[$db_id]['dbname']) ? $DATABASE[$db_id]['dbname'] : $db_id;
}

// ── Per-user state (stored in _users.wamcp JSON column) ──────────────────────

function wamcpGetUserState() {
    global $USER;
    if (empty($USER['_id'])) return array();
    //add wamcp field if it does not exist
    if(!isset($USER['wamcp'])){
        $ok=executeSQL("ALTER TABLE _users ADD COLUMN wamcp JSON NULL;");
        return array();
    }
    $state = json_decode($USER['wamcp'], true);
    return is_array($state) ? $state : array();
}

function wamcpSetUserState($key, $value) {
    global $USER;
    if (empty($USER['_id'])) return;
    $state = wamcpGetUserState();
    $state[$key] = $value;
    editDBRecord(array('-table' => '_users', '_id' => $USER['_id'], 'wamcp' => json_encode($state)));
}

function wamcpGetUserDb() {
    $state = wamcpGetUserState();
    if (!empty($state['db']) && wamcpGetDatabase($state['db'])) {
        return $state['db'];
    }
    return wamcpDefaultDatabase();
}

// ── Web UI support functions ──────────────────────────────────────────────────

function wamcpListDatabases() {
    global $DATABASE;
    $databases = array();
    foreach ($DATABASE as $key => $db) {
        if (isset($db['wamcp']) && strlen($db['wamcp'])) {
            $databases[] = array(
                'id'          => $key,
                'name'        => $db['wamcp'],
                'displayname' => isset($db['displayname']) ? $db['displayname'] : (isset($db['dbname']) ? $db['dbname'] : $key)
            );
        }
    }
    return $databases;
}

function wamcpGetDatabase($db_id) {
    global $DATABASE;
    if (isset($DATABASE[$db_id]) && isset($DATABASE[$db_id]['wamcp'])) {
        return $DATABASE[$db_id];
    }
    return null;
}

function wamcpUseDatabase($db_id) {
    $db = wamcpGetDatabase($db_id);
    if (!$db) {
        return array('success' => false, 'error' => "Database '{$db_id}' not found or not enabled for WaMCP.");
    }
    wamcpSetUserState('db', $db_id);
    return array('success' => true, 'message' => "Database set to '{$db['wamcp']}' ({$db_id})");
}

function wamcpQueryDatabase($db_id, $query) {
    $db = wamcpGetDatabase($db_id);
    if (!$db) {
        return array('success' => false, 'error' => "Database '{$db_id}' not found or not enabled for WaMCP.");
    }
    try {
        $recs = dbQueryResults($db_id, $query);
        if (is_array($recs)) {
            return array('success' => true, 'records' => $recs, 'count' => count($recs));
        }
        return array('success' => true, 'records' => array(), 'count' => 0, 'message' => 'Query executed successfully');
    } catch (Exception $e) {
        return array('success' => false, 'error' => $e->getMessage());
    }
}

function wamcpSeeRunningQueries($db_id) {
    $db = wamcpGetDatabase($db_id);
    if (!$db) {
        return array('success' => false, 'error' => "Database '{$db_id}' not found or not enabled for WaMCP.");
    }
    $dbtype = strtolower(isset($db['dbtype']) ? $db['dbtype'] : 'mysql');
    switch ($dbtype) {
        case 'mysql':
        case 'mysqli':
            $query = "SHOW FULL PROCESSLIST";
            break;
        case 'postgresql':
            $query = "SELECT * FROM pg_stat_activity WHERE state = 'active'";
            break;
        case 'mssql':
            $query = "SELECT r.session_id, r.status, r.start_time, r.command, t.text
                      FROM sys.dm_exec_requests r
                      CROSS APPLY sys.dm_exec_sql_text(r.sql_handle) t";
            break;
        default:
            return array('success' => false, 'error' => "Running queries not supported for dbtype '{$dbtype}'.");
    }
    return wamcpQueryDatabase($db_id, $query);
}

function wamcpLog($request, $payload = '[]') {
    $tpath   = getWaSQLPath('php/admin');
    $logfile = "{$tpath}/wamcp.log";
    appendFileContents($logfile, json_encode($request) . PHP_EOL);
    if (is_array($payload)) {
        appendFileContents($logfile, json_encode($payload) . PHP_EOL);
    }
    appendFileContents($logfile, '----------------------------' . PHP_EOL);
}
?>
