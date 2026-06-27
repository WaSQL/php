# WaSQL MCP Server Specification (wamcp)

An HTTP-based MCP server that exposes MySQL/MariaDB database introspection and query execution tools to AI assistants like Claude.

---

## Configuration (`mcpServers` definition)

The client authenticates entirely via `url`, `type`, and `headers`. No tool arguments carry credentials.

```json
{
  "mcpServers": {
    "wamcp": {
      "type": "http",
      "url": "https://yourhost.com/mcp/{dbname}",
      "headers": {
        "Authorization": "Bearer YOUR_API_TOKEN",
        "Content-Type": "application/json"
      }
    }
  }
}
```

| Field | Purpose |
|-------|---------|
| `url` | Base endpoint; `{dbname}` in the path sets the active database for every request |
| `type` | Always `"http"` for this server |
| `headers.Authorization` | Bearer token validated server-side on every request |

The server extracts `{dbname}` from the URL path segment and uses it as the active database for the lifetime of the session.

---

## HTTP Transport

Every MCP message is a **POST** to the configured URL.

```
POST /mcp/{dbname}
Authorization: Bearer <token>
Content-Type: application/json

{ "jsonrpc": "2.0", "id": 1, "method": "...", "params": { ... } }
```

The server responds with `200 OK` and a JSON body for every request (no SSE needed for this server since none of the tools produce streaming output).

```
HTTP/1.1 200 OK
Content-Type: application/json

{ "jsonrpc": "2.0", "id": 1, "result": { ... } }
```

**Authentication failure** returns a JSON-RPC error, not an HTTP 401:
```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "error": { "code": -32001, "message": "Unauthorized" }
}
```

---

## Initialization Handshake

### `initialize` — client → server

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "initialize",
  "params": {
    "protocolVersion": "2024-11-05",
    "capabilities": {},
    "clientInfo": { "name": "claude-code", "version": "1.0.0" }
  }
}
```

### Response — server → client

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "result": {
    "protocolVersion": "2024-11-05",
    "capabilities": { "tools": {} },
    "serverInfo": { "name": "wamcp", "version": "1.0.0" }
  }
}
```

### `notifications/initialized` — client → server

```json
{ "jsonrpc": "2.0", "method": "notifications/initialized" }
```

Server responds with `200 OK` and an empty body (`{}`).

---

## `tools/list`

### Request
```json
{ "jsonrpc": "2.0", "id": 2, "method": "tools/list", "params": {} }
```

### Response
```json
{
  "jsonrpc": "2.0",
  "id": 2,
  "result": {
    "tools": [
      {
        "name": "db",
        "description": "Display current database connection info (host, user, version, database name, data directory).",
        "inputSchema": { "type": "object", "properties": {} }
      },
      {
        "name": "ddl",
        "description": "Return the CREATE TABLE statement (DDL) for a specified table.",
        "inputSchema": {
          "type": "object",
          "properties": {
            "tablename": { "type": "string", "description": "Table name to describe" }
          },
          "required": ["tablename"]
        }
      },
      {
        "name": "tables",
        "description": "List tables in the active database, optionally filtered by a substring match.",
        "inputSchema": {
          "type": "object",
          "properties": {
            "filter": { "type": "string", "description": "Optional substring filter on table name" }
          }
        }
      },
      {
        "name": "fields",
        "description": "List columns for a table, optionally filtered by a substring match. Alias: fld.",
        "inputSchema": {
          "type": "object",
          "properties": {
            "tablename": { "type": "string", "description": "Table to inspect" },
            "filter": { "type": "string", "description": "Optional substring filter on column name" }
          },
          "required": ["tablename"]
        }
      },
      {
        "name": "fld",
        "description": "Alias for fields. List columns for a table, optionally filtered.",
        "inputSchema": {
          "type": "object",
          "properties": {
            "tablename": { "type": "string" },
            "filter": { "type": "string" }
          },
          "required": ["tablename"]
        }
      },
      {
        "name": "idx",
        "description": "Return all indexes defined on a specified table.",
        "inputSchema": {
          "type": "object",
          "properties": {
            "tablename": { "type": "string" }
          },
          "required": ["tablename"]
        }
      },
      {
        "name": "running_queries",
        "description": "Show currently executing queries (excludes idle/Sleep connections).",
        "inputSchema": { "type": "object", "properties": {} }
      },
      {
        "name": "sessions",
        "description": "Show all active database sessions including idle ones.",
        "inputSchema": { "type": "object", "properties": {} }
      },
      {
        "name": "table_locks",
        "description": "Show tables currently held under a lock.",
        "inputSchema": { "type": "object", "properties": {} }
      },
      {
        "name": "views",
        "description": "List all views in the active database.",
        "inputSchema": { "type": "object", "properties": {} }
      },
      {
        "name": "indexes",
        "description": "List all indexes across every table in the active database.",
        "inputSchema": { "type": "object", "properties": {} }
      },
      {
        "name": "functions",
        "description": "List all stored functions in the active database.",
        "inputSchema": { "type": "object", "properties": {} }
      },
      {
        "name": "procedures",
        "description": "List all stored procedures in the active database.",
        "inputSchema": { "type": "object", "properties": {} }
      },
      {
        "name": "query",
        "description": "Execute an arbitrary SQL query and return the result set. SELECT only unless the token has write permission.",
        "inputSchema": {
          "type": "object",
          "properties": {
            "sql": { "type": "string", "description": "SQL statement to execute" }
          },
          "required": ["sql"]
        }
      }
    ]
  }
}
```

---

## `tools/call` — Per-Tool Specification

All calls share this outer envelope:

```json
{
  "jsonrpc": "2.0",
  "id": <n>,
  "method": "tools/call",
  "params": {
    "name": "<tool_name>",
    "arguments": { ... }
  }
}
```

All responses share this outer envelope:

```json
{
  "jsonrpc": "2.0",
  "id": <n>,
  "result": {
    "content": [{ "type": "text", "text": "<markdown or plain text>" }],
    "isError": false
  }
}
```

---

### `db` — Connection Info

**SQL executed**
```sql
SELECT
  DATABASE()        AS db_name,
  USER()            AS connected_user,
  @@hostname        AS host,
  @@version         AS version,
  @@version_comment AS server_type,
  @@datadir         AS data_dir,
  @@character_set_server AS charset,
  @@collation_server AS collation;
```

**Request**
```json
{
  "jsonrpc": "2.0", "id": 3,
  "method": "tools/call",
  "params": { "name": "db", "arguments": {} }
}
```

**Response**
```json
{
  "jsonrpc": "2.0", "id": 3,
  "result": {
    "content": [{
      "type": "text",
      "text": "| Field | Value |\n|-------|-------|\n| Database | myapp |\n| User | dbuser@localhost |\n| Host | db01.example.com |\n| Version | 8.0.33 |\n| Server | MySQL Community Server |\n| Data Dir | /var/lib/mysql/ |\n| Charset | utf8mb4 |\n| Collation | utf8mb4_unicode_ci |"
    }],
    "isError": false
  }
}
```

---

### `ddl` — Create Statement

**SQL executed**
```sql
SHOW CREATE TABLE `{tablename}`;
```

**Request**
```json
{
  "jsonrpc": "2.0", "id": 4,
  "method": "tools/call",
  "params": { "name": "ddl", "arguments": { "tablename": "users" } }
}
```

**Response**
```json
{
  "jsonrpc": "2.0", "id": 4,
  "result": {
    "content": [{
      "type": "text",
      "text": "```sql\nCREATE TABLE `users` (\n  `id` int NOT NULL AUTO_INCREMENT,\n  `name` varchar(100) NOT NULL,\n  `email` varchar(255) NOT NULL,\n  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,\n  PRIMARY KEY (`id`),\n  UNIQUE KEY `email` (`email`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n```"
    }],
    "isError": false
  }
}
```

---

### `tables` — List Tables

**SQL executed**
```sql
SELECT
  TABLE_NAME,
  ENGINE,
  TABLE_ROWS,
  ROUND(DATA_LENGTH / 1024 / 1024, 2) AS data_mb,
  TABLE_COMMENT,
  CREATE_TIME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = '{dbname}'
  AND TABLE_TYPE = 'BASE TABLE'
  [AND TABLE_NAME LIKE '%{filter}%']
ORDER BY TABLE_NAME;
```

**Request (filtered)**
```json
{
  "jsonrpc": "2.0", "id": 5,
  "method": "tools/call",
  "params": { "name": "tables", "arguments": { "filter": "order" } }
}
```

**Response**
```json
{
  "jsonrpc": "2.0", "id": 5,
  "result": {
    "content": [{
      "type": "text",
      "text": "| Table | Engine | Rows | Size (MB) | Created |\n|-------|--------|------|-----------|--------|\n| order_items | InnoDB | 42381 | 8.25 | 2023-01-15 |\n| orders | InnoDB | 9812 | 3.10 | 2023-01-15 |"
    }],
    "isError": false
  }
}
```

---

### `fields` / `fld` — List Columns

**SQL executed**
```sql
SELECT
  COLUMN_NAME,
  COLUMN_TYPE,
  IS_NULLABLE,
  COLUMN_KEY,
  COLUMN_DEFAULT,
  EXTRA,
  COLUMN_COMMENT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = '{dbname}'
  AND TABLE_NAME = '{tablename}'
  [AND COLUMN_NAME LIKE '%{filter}%']
ORDER BY ORDINAL_POSITION;
```

**Request**
```json
{
  "jsonrpc": "2.0", "id": 6,
  "method": "tools/call",
  "params": { "name": "fields", "arguments": { "tablename": "users", "filter": "email" } }
}
```

**Response**
```json
{
  "jsonrpc": "2.0", "id": 6,
  "result": {
    "content": [{
      "type": "text",
      "text": "| Column | Type | Nullable | Key | Default | Extra |\n|--------|------|----------|-----|---------|-------|\n| email | varchar(255) | NO | UNI | NULL | |"
    }],
    "isError": false
  }
}
```

---

### `idx` — Table Indexes

**SQL executed**
```sql
SHOW INDEX FROM `{tablename}`;
```

**Request**
```json
{
  "jsonrpc": "2.0", "id": 7,
  "method": "tools/call",
  "params": { "name": "idx", "arguments": { "tablename": "orders" } }
}
```

**Response**
```json
{
  "jsonrpc": "2.0", "id": 7,
  "result": {
    "content": [{
      "type": "text",
      "text": "| Index | Type | Column | Unique | Cardinality |\n|-------|------|--------|--------|-------------|\n| PRIMARY | BTREE | id | YES | 9812 |\n| idx_user_id | BTREE | user_id | NO | 2450 |\n| idx_status | BTREE | status | NO | 5 |"
    }],
    "isError": false
  }
}
```

---

### `running_queries` — Active Queries

**SQL executed**
```sql
SELECT
  ID, USER, HOST, DB, COMMAND, TIME, STATE,
  LEFT(INFO, 200) AS query_preview
FROM information_schema.PROCESSLIST
WHERE COMMAND != 'Sleep'
ORDER BY TIME DESC;
```

**Request**
```json
{
  "jsonrpc": "2.0", "id": 8,
  "method": "tools/call",
  "params": { "name": "running_queries", "arguments": {} }
}
```

---

### `sessions` — All Sessions

**SQL executed**
```sql
SELECT
  ID, USER, HOST, DB, COMMAND, TIME, STATE,
  LEFT(INFO, 100) AS query_preview
FROM information_schema.PROCESSLIST
ORDER BY TIME DESC;
```

---

### `table_locks` — Locked Tables

**SQL executed**
```sql
SELECT
  r.trx_id              AS waiting_trx,
  r.trx_mysql_thread_id AS waiting_thread,
  r.trx_query           AS waiting_query,
  b.trx_id              AS blocking_trx,
  b.trx_mysql_thread_id AS blocking_thread,
  b.trx_query           AS blocking_query
FROM information_schema.INNODB_TRX b
JOIN information_schema.INNODB_TRX r
  ON r.trx_wait_started IS NOT NULL
  AND b.trx_id != r.trx_id;
```

Fallback if InnoDB views are unavailable:
```sql
SHOW OPEN TABLES WHERE In_use > 0;
```

---

### `views` — List Views

**SQL executed**
```sql
SELECT
  TABLE_NAME     AS view_name,
  IS_UPDATABLE,
  DEFINER,
  SECURITY_TYPE,
  LEFT(VIEW_DEFINITION, 300) AS definition_preview
FROM information_schema.VIEWS
WHERE TABLE_SCHEMA = '{dbname}'
ORDER BY TABLE_NAME;
```

---

### `indexes` — All Indexes (Database-wide)

**SQL executed**
```sql
SELECT
  TABLE_NAME,
  INDEX_NAME,
  GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columns,
  NON_UNIQUE,
  INDEX_TYPE
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = '{dbname}'
GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE, INDEX_TYPE
ORDER BY TABLE_NAME, INDEX_NAME;
```

---

### `functions` — Stored Functions

**SQL executed**
```sql
SELECT
  ROUTINE_NAME,
  DATA_TYPE          AS return_type,
  SECURITY_TYPE,
  DEFINER,
  LEFT(ROUTINE_DEFINITION, 300) AS body_preview
FROM information_schema.ROUTINES
WHERE ROUTINE_SCHEMA = '{dbname}'
  AND ROUTINE_TYPE = 'FUNCTION'
ORDER BY ROUTINE_NAME;
```

---

### `procedures` — Stored Procedures

**SQL executed**
```sql
SELECT
  ROUTINE_NAME,
  SECURITY_TYPE,
  DEFINER,
  LEFT(ROUTINE_DEFINITION, 300) AS body_preview
FROM information_schema.ROUTINES
WHERE ROUTINE_SCHEMA = '{dbname}'
  AND ROUTINE_TYPE = 'PROCEDURE'
ORDER BY ROUTINE_NAME;
```

---

### `query` — Execute SQL

**Request**
```json
{
  "jsonrpc": "2.0", "id": 9,
  "method": "tools/call",
  "params": {
    "name": "query",
    "arguments": {
      "sql": "SELECT id, name, email FROM users WHERE created_at > '2024-01-01' LIMIT 10"
    }
  }
}
```

**Response (result set)**
```json
{
  "jsonrpc": "2.0", "id": 9,
  "result": {
    "content": [{
      "type": "text",
      "text": "3 rows returned.\n\n| id | name | email |\n|----|------|-------|\n| 42 | Alice | alice@example.com |\n| 43 | Bob | bob@example.com |\n| 44 | Carol | carol@example.com |"
    }],
    "isError": false
  }
}
```

**Response (write statement result)**
```json
{
  "jsonrpc": "2.0", "id": 9,
  "result": {
    "content": [{
      "type": "text",
      "text": "Query OK. Rows affected: 1. Last insert ID: 45."
    }],
    "isError": false
  }
}
```

**Response (SQL error)**
```json
{
  "jsonrpc": "2.0", "id": 9,
  "result": {
    "content": [{
      "type": "text",
      "text": "SQL Error [1146]: Table 'myapp.nonexistent' doesn't exist"
    }],
    "isError": true
  }
}
```

---

## Server Implementation (PHP)

```php
<?php
// wamcp_controller.php — receives POST to /mcp/{dbname}

header('Content-Type: application/json');

// --- Auth ---
$token = getBearerToken();
if (!validateToken($token)) {
    echo json_encode(['jsonrpc'=>'2.0','id'=>null,
        'error'=>['code'=>-32001,'message'=>'Unauthorized']]);
    exit;
}

// --- Parse dbname from URL ---
$dbname = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbname)) {
    echo json_encode(['jsonrpc'=>'2.0','id'=>null,
        'error'=>['code'=>-32602,'message'=>'Invalid database name']]);
    exit;
}

// --- Connect ---
$pdo = new PDO("mysql:host=localhost;dbname=$dbname;charset=utf8mb4",
               DB_USER, DB_PASS,
               [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// --- Route message ---
$msg = json_decode(file_get_contents('php://input'), true);
$method = $msg['method'] ?? '';
$id     = $msg['id'] ?? null;
$params = $msg['params'] ?? [];

switch ($method) {
    case 'initialize':
        send($id, [
            'protocolVersion' => '2024-11-05',
            'capabilities'    => ['tools' => []],
            'serverInfo'      => ['name' => 'wamcp', 'version' => '1.0.0']
        ]);
        break;

    case 'notifications/initialized':
        echo '{}';
        break;

    case 'ping':
        send($id, []);
        break;

    case 'tools/list':
        send($id, ['tools' => getToolsList()]);
        break;

    case 'tools/call':
        $name = $params['name'] ?? '';
        $args = $params['arguments'] ?? [];
        send($id, dispatchTool($name, $args, $pdo, $dbname));
        break;

    default:
        if ($id !== null) {
            sendError($id, -32601, "Method not found: $method");
        } else {
            echo '{}';
        }
}

// --- Helpers ---

function dispatchTool(string $name, array $args, PDO $pdo, string $dbname): array {
    try {
        switch ($name) {
            case 'db':            return toolDb($pdo);
            case 'ddl':           return toolDdl($pdo, $args['tablename']);
            case 'tables':        return toolTables($pdo, $dbname, $args['filter'] ?? '');
            case 'fields':
            case 'fld':           return toolFields($pdo, $dbname, $args['tablename'], $args['filter'] ?? '');
            case 'idx':           return toolIdx($pdo, $args['tablename']);
            case 'running_queries': return toolRunningQueries($pdo);
            case 'sessions':      return toolSessions($pdo);
            case 'table_locks':   return toolTableLocks($pdo);
            case 'views':         return toolViews($pdo, $dbname);
            case 'indexes':       return toolIndexes($pdo, $dbname);
            case 'functions':     return toolRoutines($pdo, $dbname, 'FUNCTION');
            case 'procedures':    return toolRoutines($pdo, $dbname, 'PROCEDURE');
            case 'query':         return toolQuery($pdo, $args['sql']);
            default:
                return toolError("Unknown tool: $name");
        }
    } catch (Exception $e) {
        return toolError($e->getMessage());
    }
}

function toolDb(PDO $pdo): array {
    $row = $pdo->query("SELECT DATABASE() AS db, USER() AS user, @@hostname AS host,
                        @@version AS ver, @@datadir AS datadir,
                        @@character_set_server AS charset,
                        @@collation_server AS collation")->fetch(PDO::FETCH_ASSOC);
    return toolText(toMarkdownTable([$row]));
}

function toolDdl(PDO $pdo, string $table): array {
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $row   = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
    return toolText("```sql\n" . $row[1] . "\n```");
}

function toolTables(PDO $pdo, string $db, string $filter): array {
    $sql = "SELECT TABLE_NAME, ENGINE, TABLE_ROWS,
                   ROUND(DATA_LENGTH/1024/1024,2) AS data_mb, CREATE_TIME
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'";
    $params = [$db];
    if ($filter) { $sql .= " AND TABLE_NAME LIKE ?"; $params[] = "%$filter%"; }
    $sql .= " ORDER BY TABLE_NAME";
    $rows = $pdo->prepare($sql);
    $rows->execute($params);
    return toolText(toMarkdownTable($rows->fetchAll(PDO::FETCH_ASSOC)));
}

function toolFields(PDO $pdo, string $db, string $table, string $filter): array {
    $sql = "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY,
                   COLUMN_DEFAULT, EXTRA, COLUMN_COMMENT
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
    $params = [$db, $table];
    if ($filter) { $sql .= " AND COLUMN_NAME LIKE ?"; $params[] = "%$filter%"; }
    $sql .= " ORDER BY ORDINAL_POSITION";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return toolText(toMarkdownTable($stmt->fetchAll(PDO::FETCH_ASSOC)));
}

function toolIdx(PDO $pdo, string $table): array {
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $rows  = $pdo->query("SHOW INDEX FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    return toolText(toMarkdownTable($rows));
}

function toolRunningQueries(PDO $pdo): array {
    $rows = $pdo->query("SELECT ID,USER,HOST,DB,COMMAND,TIME,STATE,LEFT(INFO,200) AS query_preview
                         FROM information_schema.PROCESSLIST WHERE COMMAND!='Sleep'
                         ORDER BY TIME DESC")->fetchAll(PDO::FETCH_ASSOC);
    return toolText(toMarkdownTable($rows));
}

function toolSessions(PDO $pdo): array {
    $rows = $pdo->query("SELECT ID,USER,HOST,DB,COMMAND,TIME,STATE,LEFT(INFO,100) AS query_preview
                         FROM information_schema.PROCESSLIST
                         ORDER BY TIME DESC")->fetchAll(PDO::FETCH_ASSOC);
    return toolText(toMarkdownTable($rows));
}

function toolTableLocks(PDO $pdo): array {
    try {
        $rows = $pdo->query("SELECT r.trx_id AS waiting_trx, r.trx_mysql_thread_id AS waiting_thread,
                                    LEFT(r.trx_query,100) AS waiting_query,
                                    b.trx_id AS blocking_trx, b.trx_mysql_thread_id AS blocking_thread,
                                    LEFT(b.trx_query,100) AS blocking_query
                             FROM information_schema.INNODB_TRX b
                             JOIN information_schema.INNODB_TRX r
                               ON r.trx_wait_started IS NOT NULL AND b.trx_id != r.trx_id")
                    ->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $rows = $pdo->query("SHOW OPEN TABLES WHERE In_use > 0")->fetchAll(PDO::FETCH_ASSOC);
    }
    return toolText(empty($rows) ? 'No table locks detected.' : toMarkdownTable($rows));
}

function toolViews(PDO $pdo, string $db): array {
    $stmt = $pdo->prepare("SELECT TABLE_NAME,IS_UPDATABLE,DEFINER,SECURITY_TYPE,
                                  LEFT(VIEW_DEFINITION,300) AS definition_preview
                           FROM information_schema.VIEWS WHERE TABLE_SCHEMA=?
                           ORDER BY TABLE_NAME");
    $stmt->execute([$db]);
    return toolText(toMarkdownTable($stmt->fetchAll(PDO::FETCH_ASSOC)));
}

function toolIndexes(PDO $pdo, string $db): array {
    $stmt = $pdo->prepare("SELECT TABLE_NAME, INDEX_NAME,
                                  GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columns,
                                  NON_UNIQUE, INDEX_TYPE
                           FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=?
                           GROUP BY TABLE_NAME,INDEX_NAME,NON_UNIQUE,INDEX_TYPE
                           ORDER BY TABLE_NAME,INDEX_NAME");
    $stmt->execute([$db]);
    return toolText(toMarkdownTable($stmt->fetchAll(PDO::FETCH_ASSOC)));
}

function toolRoutines(PDO $pdo, string $db, string $type): array {
    $stmt = $pdo->prepare("SELECT ROUTINE_NAME, DATA_TYPE AS return_type, SECURITY_TYPE, DEFINER,
                                  LEFT(ROUTINE_DEFINITION,300) AS body_preview
                           FROM information_schema.ROUTINES
                           WHERE ROUTINE_SCHEMA=? AND ROUTINE_TYPE=?
                           ORDER BY ROUTINE_NAME");
    $stmt->execute([$db, $type]);
    return toolText(toMarkdownTable($stmt->fetchAll(PDO::FETCH_ASSOC)));
}

function toolQuery(PDO $pdo, string $sql): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    if ($stmt->columnCount() > 0) {
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return toolText(count($rows) . " rows returned.\n\n" . toMarkdownTable($rows));
    }
    $affected = $stmt->rowCount();
    $lastId   = $pdo->lastInsertId();
    $msg      = "Query OK. Rows affected: $affected.";
    if ($lastId) $msg .= " Last insert ID: $lastId.";
    return toolText($msg);
}

function toMarkdownTable(array $rows): string {
    if (empty($rows)) return '_No results._';
    $headers = array_keys($rows[0]);
    $lines   = ['| ' . implode(' | ', $headers) . ' |',
                '| ' . implode(' | ', array_fill(0, count($headers), '---')) . ' |'];
    foreach ($rows as $row) {
        $lines[] = '| ' . implode(' | ', array_map(fn($v) => str_replace('|', '\\|', (string)$v), $row)) . ' |';
    }
    return implode("\n", $lines);
}

function toolText(string $text): array {
    return ['content' => [['type' => 'text', 'text' => $text]], 'isError' => false];
}

function toolError(string $msg): array {
    return ['content' => [['type' => 'text', 'text' => $msg]], 'isError' => true];
}

function send(mixed $id, array $result): void {
    echo json_encode(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]);
}

function sendError(mixed $id, int $code, string $msg): void {
    echo json_encode(['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $msg]]);
}

function getBearerToken(): string {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $header, $m)) return $m[1];
    return '';
}

function validateToken(string $token): bool {
    return hash_equals(MCP_SECRET, $token);
}
```

---

## Security Notes

- **Sanitize table/column names** before interpolating into SQL — use `preg_replace('/[^a-zA-Z0-9_]/', '', $name)`.
- **Parameterize all user values** — use PDO prepared statements for filter strings and dbname lookups.
- **Token validation** — use `hash_equals()` to prevent timing attacks.
- **Read-only token vs write token** — optionally issue two token tiers; the `query` tool checks which tier is active before allowing DML.
- **Allowlist databases** — validate `{dbname}` against a configured list so the bearer token cannot pivot to other databases on the same host.
