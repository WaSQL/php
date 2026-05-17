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
            'name'        => 'help',
            'description' => 'List all available WaMCP tools with a description of each.',
            'inputSchema' => array('type' => 'object', 'properties' => $none)
        ),
        array(
            'name'        => 'databases',
            'description' => 'List available databases grouped by type. Do NOT use SHOW DATABASES — always call this tool. Pass an optional dbtype to filter (e.g. "mysql", "postgresql").',
            'inputSchema' => array(
                'type'       => 'object',
                'properties' => array('dbtype' => array('type' => 'string', 'description' => 'Optional: filter by database type, e.g. mysql, postgresql, mssql'))
            )
        ),
        array(
            'name'        => 'setdb',
            'description' => 'Set the active database for this session by name/ID. If the name is unknown, call the databases tool to list available names.',
            'inputSchema' => array(
                'type'       => 'object',
                'properties' => array('dbname' => array('type' => 'string', 'description' => 'Database name/ID to activate')),
                'required'   => array('dbname')
            )
        ),
        array(
            'name'        => 'getdb',
            'description' => 'Display current database connection info (host, user, version, charset).',
            'inputSchema' => array('type' => 'object', 'properties' => $none)
        ),
        array(
            'name'        => 'getuser',
            'description' => 'Display info about the currently authenticated user.',
            'inputSchema' => array('type' => 'object', 'properties' => $none)
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
            'description' => 'List columns for a table, optionally filtered by a substring.',
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
            'name'        => 'ddl',
            'description' => 'Return the CREATE TABLE statement for a specified table.',
            'inputSchema' => array(
                'type'       => 'object',
                'properties' => array('tablename' => array('type' => 'string', 'description' => 'Table name')),
                'required'   => array('tablename')
            )
        ),
        array(
            'name'        => 'indexes',
            'description' => 'Return all indexes defined on a specified table, optionally filtered by column name.',
            'inputSchema' => array(
                'type'       => 'object',
                'properties' => array(
                    'tablename' => array('type' => 'string', 'description' => 'Table name'),
                    'filter'    => array('type' => 'string', 'description' => 'Optional substring filter on column_name')
                ),
                'required'   => array('tablename')
            )
        ),
        array(
            'name'        => 'query',
            'description' => 'Execute a read-only SQL query (SELECT, SHOW, EXPLAIN, DESCRIBE, WITH) and return the result set.',
            'inputSchema' => array(
                'type'       => 'object',
                'properties' => array('sql' => array('type' => 'string', 'description' => 'SQL statement to execute')),
                'required'   => array('sql')
            )
        ),
    );

    $db_id_prop = array('type' => 'string', 'description' => 'Optional: target database ID. Call the databases tool to list available IDs.');
    foreach ($tools as &$tool) {
        if (in_array($tool['name'], array('databases', 'setdb', 'help', 'getuser'))) continue;
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
        return wamcpToolDatabases(isset($args['dbtype']) ? $args['dbtype'] : '');
    }
    if ($name === 'help') {
        return wamcpToolHelp();
    }
    if ($name === 'getuser') {
        return wamcpToolGetUser();
    }
    if ($name === 'setdb') {
        $target = isset($args['dbname']) ? $args['dbname'] : '';
        if (!$target) return wamcpToolError('dbname is required');
        $result = wamcpSetDatabase($target);
        return $result['success']
            ? wamcpToolText($result['message'])
            : wamcpToolError($result['error']);
    }
    if (!empty($args['db_id'])) {
        $db_id = $args['db_id'];
    }
    if (!wamcpGetDatabase($db_id)) {
        return wamcpToolError("Database '{$db_id}' not found or is excluded from WaMCP.");
    }
    $tablename = isset($args['tablename']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $args['tablename']) : '';
    $filter    = isset($args['filter'])    ? $args['filter']    : '';
    $dbname    = wamcpGetDbName($db_id);

    switch ($name) {
        case 'getdb':
            return wamcpToolDb($db_id, $dbname);
        case 'ddl':
            if (!$tablename) return wamcpToolError('tablename is required');
            return wamcpToolDdl($db_id, $tablename);
        case 'tables':
            return wamcpToolTables($db_id, $filter);
        case 'fields':
            if (!$tablename) return wamcpToolError('tablename is required');
            return wamcpToolFields($db_id, $tablename, $filter);
        case 'indexes':
            if (!$tablename) return wamcpToolError('tablename is required');
            return wamcpToolIndexes($db_id, $tablename, $filter);
        case 'query':
            $sql = isset($args['sql']) ? $args['sql'] : '';
            if (!$sql) return wamcpToolError('sql is required');
            return wamcpToolQuery($db_id, $sql);
        default:
            return wamcpToolError("Unknown tool: {$name}");
    }
}

// ── Tool Implementations ──────────────────────────────────────────────────────

function wamcpToolDb($db_id, $dbname) {
    global $DATABASE;
    $db = $DATABASE[$db_id];
    $row = array(
        'id'     => $db_id,
        'name'   => $dbname,
        'dbtype' => isset($db['dbtype'])   ? $db['dbtype']   : 'mysql',
        'host'   => isset($db['dbhost'])   ? $db['dbhost']   : '',
        'port'   => isset($db['dbport'])   ? $db['dbport']   : '',
        'user'   => isset($db['dbuser'])   ? $db['dbuser']   : '',
        'file'   => isset($db['dbfile'])   ? $db['dbfile']   : '',
    );
    // remove empty fields so the table stays clean
    $row = array_filter($row, function($v) { return $v !== ''; });
    $out = wamcpToMarkdownTable(array($row));
    $instructions = wamcpDbtypeInstructions($db_id);
    if ($instructions) $out .= "\n\n" . $instructions;
    return wamcpToolText($out);
}

function wamcpDbtypeInstructions($db_id) {
    global $DATABASE;
    $dbtype = isset($DATABASE[$db_id]['dbtype']) ? strtolower($DATABASE[$db_id]['dbtype']) : 'mysql';
    switch ($dbtype) {
        case 'ctree':
        case 'ctreeace':
            return <<<INST
**FairCom c-treeACE SQL Query Notes**
- Pagination: use `FIRST n` and `SKIP n` — NOT `LIMIT` / `OFFSET`
  ```sql
  SELECT FIRST 25 SKIP 50 * FROM mytable;
  ```
- No `SHOW TABLES` — use the `tables` tool instead
- No `SHOW CREATE TABLE` — use the `ddl` tool instead
- String concatenation: use `||` not `CONCAT()`
- Use `TOP n` as an alternative to `FIRST n` for single-page fetches
- Date literals: `DATE '2024-01-15'` format
- Boolean: use `1`/`0` — no native BOOLEAN type
INST;

        case 'mssql':
        case 'sqlsrv':
            return <<<INST
**Microsoft SQL Server Query Notes**
- Pagination: use `OFFSET / FETCH NEXT` (requires `ORDER BY`)
  ```sql
  SELECT * FROM mytable ORDER BY id OFFSET 50 ROWS FETCH NEXT 25 ROWS ONLY;
  ```
- Alternatively use `TOP n` for simple row limits: `SELECT TOP 25 * FROM mytable`
- String concatenation: use `+` or `CONCAT()`
- Use `GETDATE()` for current timestamp, `GETUTCDATE()` for UTC
- Wrap identifiers in `[square brackets]` if they conflict with reserved words
INST;

        case 'postgresql':
        case 'pgsql':
            return <<<INST
**PostgreSQL Query Notes**
- Pagination: `LIMIT n OFFSET n` (standard)
- String concatenation: use `||` or `CONCAT()`
- Use `NOW()` or `CURRENT_TIMESTAMP` for current time
- Identifiers are case-sensitive when quoted; unquoted identifiers are lowercased
- Use `ILIKE` for case-insensitive pattern matching instead of `LIKE`
- Use `::type` cast syntax: `SELECT '2024-01-15'::date`
INST;

        case 'oracle':
            return <<<INST
**Oracle Query Notes**
- Pagination: use `FETCH FIRST n ROWS ONLY` / `OFFSET n ROWS` (Oracle 12c+)
  ```sql
  SELECT * FROM mytable ORDER BY id OFFSET 50 ROWS FETCH NEXT 25 ROWS ONLY;
  ```
- For older Oracle: use `ROWNUM` in a subquery
- No `AUTO_INCREMENT` — use sequences or `GENERATED AS IDENTITY`
- Use `SYSDATE` for current date/time, `SYSTIMESTAMP` for full precision
- `NULL` handling: use `NVL(col, default)` instead of `COALESCE()` where needed
- String concatenation: use `||`
INST;

        case 'sqlite':
            return <<<INST
**SQLite Query Notes**
- Pagination: `LIMIT n OFFSET n` (standard)
- No stored procedures or functions
- Loosely typed — column types are advisory only
- No `RIGHT JOIN` or `FULL OUTER JOIN` support
- Use `strftime()` for date/time formatting
INST;

        default:
            return '';
    }
}

function wamcpToolDdl($db_id, $tablename) {
    $ddl=dbGetTableDDL($db_id,$tablename);
    return wamcpToolText("```sql\n{$ddl}\n```");
}

function wamcpToolTables($db_id, $filter = '') {
    $rows = dbQueryResults($db_id, "tables");
    if (!is_array($rows)) return wamcpToolError('Could not retrieve tables');
    if ($filter) {
        $rows = array_filter($rows, function($row) use ($filter) {
            return stripos($row['name'], $filter) !== false;
        });
    }
    return wamcpToolText(wamcpToMarkdownTable(array_values($rows)));
}

function wamcpToolFields($db_id, $tablename, $filter) {
    $rows = dbQueryResults($db_id, "fld {$tablename}");
    if (!is_array($rows)) return wamcpToolError("Could not retrieve fields for '{$tablename}'");
    if ($filter) {
        $rows = array_filter($rows, function($row) use ($filter) {
            return stripos($row['name'], $filter) !== false;
        });
    }
    return wamcpToolText(wamcpToMarkdownTable(array_values($rows)));
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

function wamcpToolViews($db_id, $dbname, $filter = '') {
    $filterClause = $filter ? " AND TABLE_NAME LIKE " . wamcpQ('%' . $filter . '%') : '';
    $sql  = "SELECT TABLE_NAME, IS_UPDATABLE, DEFINER, SECURITY_TYPE,
                    LEFT(VIEW_DEFINITION,300) AS definition_preview
             FROM information_schema.VIEWS
             WHERE TABLE_SCHEMA = " . wamcpQ($dbname) . $filterClause . "
             ORDER BY TABLE_NAME";
    $rows = dbQueryResults($db_id, $sql);
    if (!is_array($rows)) return wamcpToolError('Could not retrieve views');
    return wamcpToolText(wamcpToMarkdownTable($rows));
}

function wamcpToolIndexes($db_id, $tablename, $filter = '') {
    $rows = dbQueryResults($db_id, "idx {$tablename}");
    if (!is_array($rows)) return wamcpToolError("Could not retrieve indexes for '{$tablename}'");
    if ($filter) {
        $rows = array_filter($rows, function($row) use ($filter) {
            return stripos($row['column_name'], $filter) !== false;
        });
    }
    return wamcpToolText(wamcpToMarkdownTable(array_values($rows)));
}

function wamcpToolRoutines($db_id, $dbname, $type, $filter = '') {
    $filterClause = $filter ? " AND ROUTINE_NAME LIKE " . wamcpQ('%' . $filter . '%') : '';
    $sql  = "SELECT ROUTINE_NAME, DATA_TYPE AS return_type, SECURITY_TYPE, DEFINER,
                    LEFT(ROUTINE_DEFINITION,300) AS body_preview
             FROM information_schema.ROUTINES
             WHERE ROUTINE_SCHEMA = " . wamcpQ($dbname) . "
               AND ROUTINE_TYPE = " . wamcpQ($type) . $filterClause . "
             ORDER BY ROUTINE_NAME";
    $rows = dbQueryResults($db_id, $sql);
    if (!is_array($rows)) return wamcpToolError("Could not retrieve {$type}s");
    return wamcpToolText(wamcpToMarkdownTable($rows));
}

function wamcpToolDatabases($dbtype = '') {
    $list = wamcpListDatabases();
    if ($dbtype) {
        $dbtype = strtolower($dbtype);
        $list = array_filter($list, function($db) use ($dbtype) {
            return strtolower($db['dbtype']) === $dbtype;
        });
    }
    if (empty($list)) {
        $msg = $dbtype ? "No {$dbtype} databases available." : 'No databases available.';
        return wamcpToolText($msg);
    }
    $groups = array();
    foreach ($list as $db) {
        $groups[$db['dbtype']][] = array('id' => $db['id'], 'name' => $db['name']);
    }
    ksort($groups);
    $lines = array();
    foreach ($groups as $type => $dbs) {
        $lines[] = "### {$type}";
        $lines[] = wamcpToMarkdownTable($dbs);
    }
    return wamcpToolText(implode("\n\n", $lines));
}

function wamcpToolGetUser() {
    global $USER;
    if (empty($USER)) return wamcpToolError('No authenticated user found.');
    $fields = array('_id', 'firstname', 'lastname', 'username', 'email', 'wamcp');
    $row = array();
    foreach ($fields as $f) {
        $row[$f] = isset($USER[$f]) ? $USER[$f] : '';
    }
    return wamcpToolText(wamcpToMarkdownTable(array($row)));
}

function wamcpToolHelp() {
    $tools = wamcpGetToolsList();
    $lines = array('| Tool | Description |', '| --- | --- |');
    foreach ($tools as $tool) {
        $lines[] = '| ' . $tool['name'] . ' | ' . $tool['description'] . ' |';
    }
    return wamcpToolText(implode("\n", $lines));
}

function wamcpToolQuery($db_id, $sql) {
    $keyword = strtoupper(preg_match('/^\s*(\w+)/', $sql, $m) ? $m[1] : '');
    $allowed = array('SELECT', 'SHOW', 'EXPLAIN', 'WITH', 'DESCRIBE', 'DESC');
    if (!in_array($keyword, $allowed)) {
        return wamcpToolError('Only read-only queries are permitted (SELECT, SHOW, EXPLAIN, DESCRIBE, WITH).');
    }
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
    $rec = getDBRecord(array('-table' => '_users', '_id' => $USER['_id'], '-nocache' => 1));
    if (!$rec) return array();
    if (!array_key_exists('wamcp', $rec)) {
        executeSQL("ALTER TABLE _users ADD COLUMN wamcp JSON NULL;");
        return array();
    }
    $state = json_decode($rec['wamcp'], true);
    return is_array($state) ? $state : array();
}

function wamcpSetUserState($key, $value) {
    global $USER;
    if (empty($USER['_id'])) return;
    $state = wamcpGetUserState();
    $state[$key] = $value;
    editDBRecordById('_users',$USER['_id'],array('wamcp'=>$state));
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
        if (isset($db['wamcp']) && $db['wamcp'] === 'false') continue;
        $databases[] = array(
            'id'          => $key,
            'name'        => isset($db['wamcp']) ? $db['wamcp'] : $key,
            'displayname' => isset($db['displayname']) ? $db['displayname'] : (isset($db['dbname']) ? $db['dbname'] : $key),
            'dbtype'      => isset($db['dbtype']) ? $db['dbtype'] : 'mysql'
        );
    }
    return $databases;
}

function wamcpGetDatabase($db_id) {
    global $DATABASE;
    if (isset($DATABASE[$db_id]) && !(isset($DATABASE[$db_id]['wamcp']) && $DATABASE[$db_id]['wamcp'] === 'false')) {
        return $DATABASE[$db_id];
    }
    return null;
}

function wamcpSetDatabase($db_id) {
    global $USER;
    $db = wamcpGetDatabase($db_id);
    if (!$db) {
        return array('success' => false, 'error' => "Database '{$db_id}' not found or is excluded from WaMCP.");
    }
    wamcpSetUserState('db', $db_id);
    $msg = "Database set to '{$db_id}' for user {$USER['_id']}.";
    $instructions = wamcpDbtypeInstructions($db_id);
    if ($instructions) $msg .= "\n\n" . $instructions;
    return array('success' => true, 'message' => $msg);
}

function wamcpQueryDatabase($db_id, $query) {
    $db = wamcpGetDatabase($db_id);
    if (!$db) {
        return array('success' => false, 'error' => "Database '{$db_id}' not found or is excluded from WaMCP.");
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
        return array('success' => false, 'error' => "Database '{$db_id}' not found or is excluded from WaMCP.");
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
