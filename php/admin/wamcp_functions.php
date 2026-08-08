<?php
/**
 * WaMCP Functions - MCP server for WaSQL databases
 */

// ── daSQL remote connections (dasql/dasql.ini) ────────────────────────────────
// A dasql.ini [section] is exposed as a wamcp database ONLY when it defines both
// base_url and wamcp=true. No [global] merge — each section must stand alone.
// This lets wamcp query remote WaSQL hosts without a local driver, and without a
// matching <database> entry in config.xml.
function wamcpLoadDasqlSections() {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = array();
    $inifile = getWaSQLPath('dasql') . '/dasql.ini';
    if (!is_file($inifile)) return $cache;
    $data = commonParseIni($inifile);
    if (!is_array($data)) return $cache;
    foreach ($data as $name => $vals) {
        if (!is_array($vals)) continue;
        if ($name === 'global') continue;               // reserved
        if (strpos($name, ':') !== false) continue;     // shortcut sections, e.g. [name:shortcut]
        $base = isset($vals['base_url']) ? trim($vals['base_url']) : '';
        if ($base === '') continue;                      // must define its own base_url (no [global] merge)
        $wamcp = isset($vals['wamcp']) ? strtolower(trim($vals['wamcp'])) : '';
        if (!in_array($wamcp, array('true', '1', 'yes', 'on'))) continue;  // must opt in
        $base = rtrim($base, '/');
        $db   = isset($vals['db']) ? trim($vals['db']) : '';
        $cache[$name] = array(
            'id'          => $name,
            'dbtype'      => 'dasql',
            'base_url'    => $base,
            'dbhost'      => $base,
            'db'          => $db,
            'dbname'      => $db !== '' ? $db : $name,
            'authkey'     => isset($vals['authkey'])  ? trim($vals['authkey'])  : '',
            'apikey'      => isset($vals['apikey'])   ? trim($vals['apikey'])   : '',
            'tauthkey'    => isset($vals['tauthkey']) ? trim($vals['tauthkey']) : '',
            'username'    => isset($vals['username']) ? trim($vals['username']) : '',
            'password'    => isset($vals['password']) ? trim($vals['password']) : '',
            'displayname' => isset($vals['displayname']) ? trim($vals['displayname']) : $name,
        );
    }
    return $cache;
}

// Returns the dasql section config for a db_id, or null. Section names are
// lowercased by commonParseIni, so match case-insensitively.
function wamcpGetDasqlSection($db_id) {
    $sections = wamcpLoadDasqlSections();
    $lk = strtolower($db_id);
    return isset($sections[$lk]) ? $sections[$lk] : null;
}

// Runs SQL against a remote WaSQL host the same way dasql.py does: POST to
// {base_url}/php/admin.php with func=sql. $format='json' returns an array of
// row objects; any other format returns the raw response body (used for ddl).
function wamcpRemoteSql($conf, $sql, $format = 'json') {
    $url = rtrim($conf['base_url'], '/') . '/php/admin.php';
    $params = array(
        'db'                  => $conf['db'],
        'func'                => 'sql',
        'format'              => $format,
        'offset'              => 0,
        '_menu'               => 'sqlprompt',
        'AjaxRequestUniqueId' => 'wamcp',
        'sql_full'            => $sql,
        '-nossl'              => 1,
        '-follow'             => 1,
        '-timeout'            => 120,
    );
    // Auth — same precedence dasql.py uses (dasql.py:410-429).
    if (!empty($conf['apikey']) && !empty($conf['username'])) {
        $params['apikey']   = $conf['apikey'];
        $params['username'] = $conf['username'];   // postURL turns these into WaSQL headers
    } elseif (!empty($conf['authkey'])) {
        $params['_auth'] = $conf['authkey'];        // postURL sends WaSQL-Auth header
    } elseif (!empty($conf['tauthkey'])) {
        $params['_tauth'] = $conf['tauthkey'];
    } elseif (!empty($conf['username']) && !empty($conf['password'])) {
        $params['_login']   = 1;
        $params['username'] = $conf['username'];
        $params['password'] = $conf['password'];
    }
    $res  = postURL($url, $params);
    if (!empty($res['error'])) {
        throw new Exception("daSQL remote error: {$res['error']}");
    }
    $body = isset($res['body']) ? trim($res['body']) : '';
    $body = preg_replace('/^\xEF\xBB\xBF/', '', $body);   // strip BOM if present
    if ($format !== 'json') {
        return $body;
    }
    $data = json_decode($body, true);
    // Success returns an array of rows. "No results" returns a JSON-encoded
    // string wrapper ("{\"result\":\"no results\"}"), which decodes to a
    // non-list — treat anything that isn't a list of rows as empty.
    if (is_array($data) && (empty($data) || isset($data[0]))) {
        return $data;
    }
    return array();
}

// Central query path for every wamcp tool: route dasql databases to the remote
// WaSQL host, everything else through the local WaSQL DB layer.
function wamcpQueryRows($db_id, $sql) {
    $db = wamcpGetDatabase($db_id);
    if ($db && isset($db['dbtype']) && strtolower($db['dbtype']) === 'dasql') {
        return wamcpRemoteSql($db, $sql, 'json');
    }
    return dbQueryResults($db_id, $sql);
}

// ── MCP Request Router ────────────────────────────────────────────────────────

function wamcpHandleMcpRequest($request, $db_id) {
    $method = isset($request['method']) ? $request['method'] : '';
    $id     = isset($request['id'])     ? $request['id']     : null;
    $params = isset($request['params']) ? $request['params'] : array();

    // tools/call logs its own detailed entry below (tool name + arg/result
    // sizes) — logging it here too would just duplicate the line.
    if ($method !== 'tools/call') {
        wamcpLog("MCP {$method} db={$db_id}");
    }

    switch ($method) {
        case 'initialize':
            wamcpSend($id, array(
                'protocolVersion' => '2024-11-05',
                'capabilities'    => array('tools' => new stdClass()),
                'serverInfo'      => array('name' => 'wamcp', 'version' => '1.0.0'),
                'instructions'    => wamcpInstructions(),
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
            $result = wamcpDispatchTool($name, $args, $db_id);
            wamcpLog("MCP tools/call db={$db_id} tool={$name}", array(
                'args_chars'   => commonStrlen(json_encode($args)),
                'result_chars' => commonStrlen(json_encode($result)),
                'is_error'     => !empty($result['isError']),
            ));
            wamcpSend($id, $result);
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

// Sent once, in the MCP `initialize` response — most clients (incl. Claude Code)
// surface this to the model as standing guidance before any tool is called.
function wamcpInstructions() {
    return "db_id is required on every tool call that accepts it (query, schema, "
         . "pagesrc, tables, fields, ddl, indexes, getdb) — call `databases` once "
         . "to look up ids, then pass db_id on every subsequent call. There is no "
         . "server-side 'active database'; omitting db_id returns an error rather "
         . "than guessing which database you meant.";
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
            'description' => 'List available databases grouped by type, one compact line per type. Do NOT use SHOW DATABASES — always call this tool. Pass an optional dbtype and/or filter to narrow the list.',
            'inputSchema' => array(
                'type'       => 'object',
                'properties' => array(
                    'dbtype' => array('type' => 'string', 'description' => 'Optional: filter by database type, e.g. mysql, postgresql, mssql'),
                    'filter' => array('type' => 'string', 'description' => 'Optional: substring filter on database id/name')
                )
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
            'description' => 'Execute a read-only SQL query (SELECT, SHOW, EXPLAIN, DESCRIBE, WITH) and return the result set. Output is capped by default (50 rows, 4000 chars, 2000 chars/cell) to control token usage — a single row/column result is returned as a raw value, not a table. Raise maxrows/maxchars/maxcell, or pass all:true, to get the full result.',
            'inputSchema' => array(
                'type'       => 'object',
                'properties' => array(
                    'sql'      => array('type' => 'string',  'description' => 'SQL statement to execute'),
                    'maxrows'  => array('type' => 'integer', 'description' => 'Max rows to return (default 50)'),
                    'maxchars' => array('type' => 'integer', 'description' => 'Max output characters (default 4000)'),
                    'maxcell'  => array('type' => 'integer', 'description' => 'Max characters per cell before truncation (default 2000)'),
                    'all'      => array('type' => 'boolean', 'description' => 'Return the full result, ignoring the caps above (default false)')
                ),
                'required'   => array('sql')
            )
        ),
        array(
            'name'        => 'schema',
            'description' => 'Compact schema overview — "table: col, col, …" (or "col type, …" with detail:true) for every table matching an optional filter. Cheaper than hand-written information_schema/pg_catalog/DESCRIBE queries for a broad look at table shapes.',
            'inputSchema' => array(
                'type'       => 'object',
                'properties' => array(
                    'filter'    => array('type' => 'string',  'description' => 'Optional substring filter on table name'),
                    'detail'    => array('type' => 'boolean', 'description' => 'Include column types (default false = names only)'),
                    'maxtables' => array('type' => 'integer', 'description' => 'Max tables to describe (default 30)'),
                    'all'       => array('type' => 'boolean', 'description' => 'Describe every matching table, ignoring maxtables (default false)')
                )
            )
        ),
        array(
            'name'        => 'pagesrc',
            'description' => 'Fetch one field (name, body, functions, controller, js, css, meta) of a single _pages record by id or name. Use grep or lines to pull just a section instead of the whole field — far cheaper than SELECT field FROM _pages via the query tool for large pages.',
            'inputSchema' => array(
                'type'       => 'object',
                'properties' => array(
                    'page'     => array('type' => 'string',  'description' => '_pages._id or name'),
                    'field'    => array('type' => 'string',  'description' => 'One of: name, body, functions, controller, js, css, meta'),
                    'grep'     => array('type' => 'string',  'description' => 'Optional substring — return only matching lines, with line numbers'),
                    'lines'    => array('type' => 'string',  'description' => 'Optional line range, e.g. "1-50", or a single number for the first N lines'),
                    'maxchars' => array('type' => 'integer', 'description' => 'Max output characters (default 4000)'),
                    'all'      => array('type' => 'boolean', 'description' => 'Return the full field, ignoring maxchars (default false)')
                ),
                'required'   => array('page', 'field')
            )
        ),
        array(
            'name'        => 'website_grade',
            'description' => 'Crawl a live website and run the SEO / AI-Optimization (AIO) grader (same engine as the Website Checker admin page), returning its "Fix with AI" prompt: overall grade plus every failed check (SEO, Open Graph/Twitter, AIO, Misc) with the affected page, example element, and suggested fix. Grades any public URL — not tied to a WaSQL database, so db_id is not used.',
            'inputSchema' => array(
                'type'       => 'object',
                'properties' => array(
                    'url'      => array('type' => 'string',  'description' => 'Website URL to grade, e.g. https://example.com'),
                    'maxpages' => array('type' => 'integer', 'description' => 'Max same-host pages to crawl (default 20, max 50)')
                ),
                'required'   => array('url')
            )
        ),
    );

    $db_id_prop = array('type' => 'string', 'description' => 'Required: target database ID. Call the databases tool to list available IDs.');
    foreach ($tools as &$tool) {
        if (in_array($tool['name'], array('databases', 'help', 'getuser', 'website_grade'))) continue;
        $props = $tool['inputSchema']['properties'];
        if ($props instanceof stdClass) {
            $tool['inputSchema']['properties'] = array('db_id' => $db_id_prop);
        } else {
            $tool['inputSchema']['properties']['db_id'] = $db_id_prop;
        }
        $required = isset($tool['inputSchema']['required']) ? $tool['inputSchema']['required'] : array();
        $required[] = 'db_id';
        $tool['inputSchema']['required'] = $required;
    }
    unset($tool);
    return $tools;
}

// ── Tool Dispatcher ───────────────────────────────────────────────────────────

function wamcpDispatchTool($name, $args, $db_id) {
    if ($name === 'databases') {
        return wamcpToolDatabases(
            isset($args['dbtype']) ? $args['dbtype'] : '',
            isset($args['filter']) ? $args['filter'] : ''
        );
    }
    if ($name === 'help') {
        return wamcpToolHelp();
    }
    if ($name === 'getuser') {
        return wamcpToolGetUser();
    }
    if ($name === 'website_grade') {
        return wamcpToolWebsiteGrade(
            isset($args['url'])      ? $args['url']      : '',
            isset($args['maxpages']) ? (int)$args['maxpages'] : 20
        );
    }
    if (!empty($args['db_id'])) {
        $db_id = $args['db_id'];
    }
    if (empty($db_id)) {
        return wamcpToolError('db_id is required. Call the databases tool to list available ids, then pass db_id on this call.');
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
            return wamcpToolQuery(
                $db_id, $sql,
                isset($args['maxrows'])  ? (int)$args['maxrows']  : 50,
                isset($args['maxchars']) ? (int)$args['maxchars'] : 4000,
                isset($args['maxcell'])  ? (int)$args['maxcell']  : 2000,
                !empty($args['all'])
            );
        case 'schema':
            return wamcpToolSchema(
                $db_id, $filter, !empty($args['detail']),
                isset($args['maxtables']) ? (int)$args['maxtables'] : 30,
                !empty($args['all'])
            );
        case 'pagesrc':
            $page  = isset($args['page'])  ? $args['page']  : '';
            $field = isset($args['field']) ? $args['field'] : '';
            if ($page === '' || $field === '') return wamcpToolError('page and field are required');
            return wamcpToolPagesrc(
                $db_id, $page, $field,
                isset($args['grep'])     ? $args['grep']     : '',
                isset($args['lines'])    ? $args['lines']    : '',
                isset($args['maxchars']) ? (int)$args['maxchars'] : 4000,
                !empty($args['all'])
            );
        default:
            return wamcpToolError("Unknown tool: {$name}");
    }
}

// ── Tool Implementations ──────────────────────────────────────────────────────

function wamcpToolDb($db_id, $dbname) {
    $db = wamcpGetDatabase($db_id);
    if (!$db) return wamcpToolError("Database '{$db_id}' not found or is excluded from WaMCP.");
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
    $db = wamcpGetDatabase($db_id);
    $dbtype = ($db && isset($db['dbtype'])) ? strtolower($db['dbtype']) : 'mysql';
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
    $db = wamcpGetDatabase($db_id);
    if ($db && isset($db['dbtype']) && strtolower($db['dbtype']) === 'dasql') {
        $ddl = trim((string)wamcpRemoteSql($db, "ddl {$tablename}", 'dos'));
    } else {
        $ddl = dbGetTableDDL($db_id, $tablename);
    }
    return wamcpToolText("```sql\n{$ddl}\n```");
}

function wamcpToolTables($db_id, $filter = '') {
    $rows = wamcpQueryRows($db_id, "tables");
    if (!is_array($rows)) return wamcpToolError('Could not retrieve tables');
    if ($filter) {
        $rows = array_filter($rows, function($row) use ($filter) {
            return stripos($row['name'], $filter) !== false;
        });
    }
    return wamcpToolText(wamcpToMarkdownTable(array_values($rows)));
}

function wamcpToolFields($db_id, $tablename, $filter) {
    $rows = wamcpQueryRows($db_id, "fld {$tablename}");
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
    $rows = wamcpQueryRows($db_id, "idx {$tablename}");
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

function wamcpToolDatabases($dbtype = '', $filter = '') {
    $list = wamcpListDatabases();
    if ($dbtype) {
        $dbtype = strtolower($dbtype);
        $list = array_filter($list, function($db) use ($dbtype) {
            return strtolower($db['dbtype']) === $dbtype;
        });
    }
    if ($filter) {
        $list = array_filter($list, function($db) use ($filter) {
            return stripos($db['id'], $filter) !== false || stripos($db['name'], $filter) !== false;
        });
    }
    if (empty($list)) {
        $msg = $dbtype ? "No {$dbtype} databases available." : 'No databases available.';
        if ($filter) $msg = "No databases match filter '{$filter}'" . ($dbtype ? " (type {$dbtype})." : '.');
        return wamcpToolText($msg);
    }
    // One compact line per dbtype: "id (name)" only when name adds information
    // beyond id — in practice name equals id for the vast majority of entries,
    // so a per-group markdown table was mostly duplicating the same string twice.
    $groups = array();
    foreach ($list as $db) {
        $label = ($db['name'] !== '' && $db['name'] !== $db['id']) ? "{$db['id']} ({$db['name']})" : $db['id'];
        $groups[$db['dbtype']][] = $label;
    }
    ksort($groups);
    $lines = array();
    foreach ($groups as $type => $names) {
        $lines[] = "**{$type}:** " . implode(', ', $names);
    }
    return wamcpToolText(implode("\n", $lines));
}

function wamcpToolGetUser() {
    global $USER;
    if (empty($USER)) return wamcpToolError('No authenticated user found.');
    $fields = array('_id', 'firstname', 'lastname', 'username', 'email');
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

function wamcpToolQuery($db_id, $sql, $maxrows = 50, $maxchars = 4000, $maxcell = 2000, $all = false) {
    $keyword = strtoupper(preg_match('/^\s*(\w+)/', $sql, $m) ? $m[1] : '');
    $allowed = array('SELECT', 'SHOW', 'EXPLAIN', 'WITH', 'DESCRIBE', 'DESC');
    if (!in_array($keyword, $allowed)) {
        return wamcpToolError('Only read-only queries are permitted (SELECT, SHOW, EXPLAIN, DESCRIBE, WITH).');
    }
    if ($maxrows <= 0)  $maxrows  = 50;
    if ($maxchars <= 0) $maxchars = 4000;
    if ($maxcell <= 0)  $maxcell  = 2000;

    try {
        $rows = wamcpQueryRows($db_id, $sql);
        if (!is_array($rows) || empty($rows)) {
            return wamcpToolText('Query OK. No rows returned.');
        }
        $total = count($rows);

        // Single row, single column: the cell IS the whole payload — return the
        // raw value with no table scaffolding (no header, no "| --- |", no
        // pipe-escaping). Still subject to the char cap so a giant blob column
        // can't blow past it.
        if ($total === 1 && count($rows[0]) === 1) {
            $val = (string) reset($rows[0]);
            if (!$all && commonStrlen($val) > $maxchars) {
                $fullLen = commonStrlen($val);
                return wamcpToolText(substr($val, 0, $maxchars)
                    . "\n\n_Truncated: {$fullLen} chars total. Re-run with maxchars raised, or all:true, for the rest._");
            }
            return wamcpToolText($val);
        }

        $shown   = $all ? $rows : array_slice($rows, 0, $maxrows);
        $cellCap = $all ? null : $maxcell;
        $out     = $total . " rows returned.\n\n" . wamcpToMarkdownTable($shown, $cellCap);

        if (!$all) {
            $rowsCut  = $total > count($shown);
            $charsCut = commonStrlen($out) > $maxchars;
            if ($charsCut) { $out = substr($out, 0, $maxchars); }
            if ($rowsCut || $charsCut) {
                $out .= "\n\n_Truncated: showing " . count($shown) . " of {$total} rows."
                     .  " Re-run with maxrows/maxchars raised, or all:true, for the rest._";
            }
        }
        return wamcpToolText($out);
    } catch (Exception $e) {
        return wamcpToolError($e->getMessage());
    }
}

// Compact multi-table schema overview. Reuses the dialect-agnostic "tables"/"fld"
// shortcuts in dbQueryResults() (same ones wamcpToolTables/wamcpToolFields use)
// instead of hand-written information_schema/pg_catalog SQL, so it works the same
// across every dbtype wamcp supports without per-dialect branches.
function wamcpToolSchema($db_id, $filter = '', $detail = false, $maxtables = 30, $all = false) {
    if ($maxtables <= 0) $maxtables = 30;
    $tableRows = wamcpQueryRows($db_id, 'tables');
    if (!is_array($tableRows)) return wamcpToolError('Could not retrieve tables');

    $names = array();
    foreach ($tableRows as $row) {
        $n = isset($row['name']) ? $row['name'] : '';
        if ($n === '') continue;
        if ($filter && stripos($n, $filter) === false) continue;
        $names[] = $n;
    }
    if (empty($names)) {
        return wamcpToolText($filter ? "No tables match filter '{$filter}'." : 'No tables found.');
    }
    sort($names);
    $total = count($names);
    $shown = ($all || $maxtables <= 0) ? $names : array_slice($names, 0, $maxtables);

    $lines = array();
    foreach ($shown as $table) {
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        if ($safe === '') continue;
        $cols = wamcpQueryRows($db_id, "fld {$safe}");
        if (!is_array($cols)) continue;
        $parts = array();
        foreach ($cols as $c) {
            $cname = isset($c['name']) ? $c['name'] : '';
            if ($cname === '') continue;
            $ctype = isset($c['type']) ? $c['type'] : '';
            $parts[] = ($detail && $ctype !== '') ? "{$cname} {$ctype}" : $cname;
        }
        $lines[] = "**{$safe}**: " . implode(', ', $parts);
    }
    $out = implode("\n", $lines);
    if (!$all && $total > count($shown)) {
        $out .= "\n\n_Truncated: showing " . count($shown) . " of {$total} tables."
             .  " Narrow with filter, or raise maxtables/all:true._";
    }
    return wamcpToolText($out);
}

// One field of one _pages record — the direct fix for "SELECT body/functions/css
// FROM _pages WHERE _id=N" being the single largest source of query bytes (see
// ai_cleanup.md). field is allow-listed (never interpolated from an open set),
// page is either cast to int or SQL-escaped via wamcpQ() — never concatenated raw.
function wamcpToolPagesrc($db_id, $page, $field, $grep = '', $lines = '', $maxchars = 4000, $all = false) {
    $allowedFields = array('name', 'body', 'functions', 'controller', 'js', 'css', 'meta');
    $field = strtolower(trim($field));
    if (!in_array($field, $allowedFields)) {
        return wamcpToolError('field must be one of: ' . implode(', ', $allowedFields));
    }
    if ($maxchars <= 0) $maxchars = 4000;

    $where = is_numeric($page) ? ('_id=' . (int)$page) : ('name=' . wamcpQ($page));
    $sql   = "SELECT {$field} FROM _pages WHERE {$where}";
    try {
        $rows = wamcpQueryRows($db_id, $sql);
    } catch (Exception $e) {
        return wamcpToolError($e->getMessage());
    }
    if (!is_array($rows) || empty($rows)) {
        return wamcpToolText("No _pages record found for '{$page}'.");
    }
    $val = (string) reset($rows[0]);
    if ($val === '') {
        return wamcpToolText("_pages.{$field} is empty for '{$page}'.");
    }

    if ($grep !== '') {
        $srcLines = preg_split('/\r\n|\r|\n/', $val);
        $matched  = array();
        foreach ($srcLines as $i => $l) {
            if (stripos($l, $grep) !== false) { $matched[] = ($i + 1) . ': ' . $l; }
        }
        if (empty($matched)) {
            return wamcpToolText("No lines matching '{$grep}' in _pages.{$field} for '{$page}'.");
        }
        $out = implode("\n", $matched);
    } elseif ($lines !== '') {
        $srcLines = preg_split('/\r\n|\r|\n/', $val);
        $total    = count($srcLines);
        if (preg_match('/^(\d+)\s*-\s*(\d+)$/', trim($lines), $m)) {
            $start = max(1, (int)$m[1]);
            $end   = min($total, (int)$m[2]);
        } else {
            $start = 1;
            $end   = min($total, max(1, (int)$lines));
        }
        $slice = array_slice($srcLines, $start - 1, max(0, $end - $start + 1));
        $out   = "lines {$start}-{$end} of {$total}:\n\n" . implode("\n", $slice);
    } else {
        $out = $val;
    }

    if (!$all && commonStrlen($out) > $maxchars) {
        $fullLen = commonStrlen($out);
        $out = substr($out, 0, $maxchars)
             . "\n\n_Truncated: {$fullLen} chars total. Use grep/lines to target a section, raise maxchars, or pass all:true._";
    }
    return wamcpToolText($out);
}

// Crawls a live URL and returns the same "Fix with AI" prompt shown on the
// Website Checker admin page's "Fix with AI" tab (websiteGraderRenderAIPanel).
// Not tied to a WaSQL database — website_grader_functions.php is loaded on
// demand since wamcp's own page load only auto-includes wamcp_functions.php.
function wamcpToolWebsiteGrade($url, $maxpages = 20) {
    if (!function_exists('websiteGraderCrawl')) {
        $fpath = getWaSQLPath('php/admin') . '/website_grader_functions.php';
        if (!is_file($fpath)) return wamcpToolError('website_grader_functions.php not found.');
        include_once($fpath);
    }
    if (!strlen(trim((string)$url))) return wamcpToolError('url is required.');
    $starturl = websiteGraderNormalizeURL($url);
    if (!filter_var($starturl, FILTER_VALIDATE_URL)) {
        return wamcpToolError('Please provide a valid website URL (e.g. https://example.com).');
    }
    $parts = parse_url($starturl);
    if (!isset($parts['scheme']) || !in_array(strtolower($parts['scheme']), array('http', 'https'))) {
        return wamcpToolError('URL must use http or https.');
    }
    if ($maxpages < 1)  $maxpages = 20;
    if ($maxpages > 50) $maxpages = 50;

    $crawl = websiteGraderCrawl($starturl, $maxpages);
    if (isset($crawl['error'])) {
        return wamcpToolError($crawl['error']);
    }
    $baseurl = $crawl['baseurl'];
    $pages   = $crawl['pages'];
    $checks  = websiteGraderRunChecks($baseurl, $pages, $crawl['robots']);
    $grade   = websiteGraderGrade($checks);
    $social  = count($pages) ? websiteGraderSocialData($pages[0], $baseurl) : array();

    return wamcpToolText(websiteGraderAIPrompt($baseurl, $pages, $checks, $grade, $social));
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

function wamcpToMarkdownTable($rows, $maxcell = null) {
    if (empty($rows)) return '_No results._';
    $headers = array_keys($rows[0]);
    $lines   = array(
        '| ' . implode(' | ', $headers) . ' |',
        '| ' . implode(' | ', array_fill(0, count($headers), '---')) . ' |'
    );
    foreach ($rows as $row) {
        $cells = array();
        foreach ($row as $val) {
            $cell = (string)$val;
            if ($maxcell && commonStrlen($cell) > $maxcell) {
                $cell = substr($cell, 0, $maxcell) . '…[truncated]';
            }
            $cells[] = str_replace('|', '\\|', $cell);
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
    if (isset($DATABASE[$db_id]['dbname'])) return $DATABASE[$db_id]['dbname'];
    $sec = wamcpGetDasqlSection($db_id);
    if ($sec) return $sec['dbname'];
    return $db_id;
}

// ── Web UI support functions ──────────────────────────────────────────────────

function wamcpListDatabases() {
    global $DATABASE;
    $databases = array();
    $seen = array();
    foreach ($DATABASE as $key => $db) {
        if (isset($db['wamcp']) && $db['wamcp'] === 'false') continue;
        $seen[strtolower($key)] = true;
        $databases[] = array(
            'id'          => $key,
            'name'        => isset($db['wamcp']) ? $db['wamcp'] : $key,
            'displayname' => isset($db['displayname']) ? $db['displayname'] : (isset($db['dbname']) ? $db['dbname'] : $key),
            'dbtype'      => isset($db['dbtype']) ? $db['dbtype'] : 'mysql'
        );
    }
    // Add dasql.ini remote sections not already defined in config.xml.
    foreach (wamcpLoadDasqlSections() as $key => $sec) {
        if (isset($seen[strtolower($key)])) continue;   // config.xml wins
        $databases[] = array(
            'id'          => $key,
            'name'        => $key,
            'displayname' => $sec['displayname'],
            'dbtype'      => 'dasql'
        );
    }
    return $databases;
}

function wamcpGetDatabase($db_id) {
    global $DATABASE;
    // config.xml is primary and wins on name collisions.
    if (isset($DATABASE[$db_id])) {
        if (isset($DATABASE[$db_id]['wamcp']) && $DATABASE[$db_id]['wamcp'] === 'false') return null;
        return $DATABASE[$db_id];
    }
    // Not in config.xml — fall back to a dasql.ini remote section.
    return wamcpGetDasqlSection($db_id);
}

function wamcpQueryDatabase($db_id, $query) {
    $db = wamcpGetDatabase($db_id);
    if (!$db) {
        return array('success' => false, 'error' => "Database '{$db_id}' not found or is excluded from WaMCP.");
    }
    try {
        $recs = wamcpQueryRows($db_id, $query);
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
