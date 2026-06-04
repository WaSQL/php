# WaSQL Groovy Bridge

The `groovy/` folder is WaSQL's bridge between PHP and the Java ecosystem. Any WaSQL page can embed Groovy code directly — giving PHP access to JDBC database drivers, Java libraries, and the full JVM — without leaving the WaSQL page model.

## Two Ways to Run Groovy

### 1. Inline via `evalPHP` (simple, no setup)

Use `<?groovy ... ?>` tags anywhere in a WaSQL page body or function. WaSQL's `evalPHP` function detects the tag and hands the block off to Groovy:

```php
// In a WaSQL controller or function:
$result = evalPHP(<<<'GROOVY'
<?groovy
import groovy.sql.Sql
def db = Sql.newInstance("jdbc:mysql://localhost/mydb", "user", "pass", "com.mysql.cj.jdbc.Driver")
return db.rows("SELECT * FROM products WHERE active = 1")
?>
GROOVY);
```

This works out of the box — no server required. The downside is that every call spawns a fresh Groovy process, which means **2–5 seconds of JVM startup overhead** on each request.

### 2. Persistent server via `server.groovy` (fast, recommended for production)

`server.groovy` is a long-running HTTP daemon that keeps the JVM and all compiled modules warm between requests. WaSQL automatically routes `dbGroovyQueryResults()` calls through the server, starting it automatically if it is not already running.

```php
$recs = dbGroovyQueryResults('mydb', 'SELECT * FROM users WHERE active = 1');
```

With the server running, calls complete in **under 100ms** because the JVM, JDBC drivers, and database connections are already loaded.

## How the Server Works

On first start the server:
1. Parses `config.xml` to build the `DATABASE` map
2. Pre-compiles every DB driver module needed by the configured databases
3. Pre-loads the `common` and `db` modules
4. Starts an HTTP listener on `127.0.0.1:7070`
5. Writes its PID and auth token to the WaSQL temp directory

Subsequent requests hit the already-warm JVM with compiled modules cached in memory.

The server shuts itself down automatically after **60 minutes of idle** by default. It can be stopped manually via the `/shutdown` endpoint.

## Starting the Server

WaSQL starts the server automatically on the first `dbGroovyQueryResults()` call. To start it manually:

```bash
# Linux
WASQL_GROOVY_PID_FILE=/var/www/wasql/php/temp/wasql-groovy-server.pid \
WASQL_GROOVY_TOKEN_FILE=/var/www/wasql/php/temp/wasql-groovy-server.token \
groovy -cp "groovy/lib/jar1.jar:groovy/lib/jar2.jar" groovy/server.groovy

# Windows
set WASQL_GROOVY_PID_FILE=C:\wasql\php\temp\wasql-groovy-server.pid
set WASQL_GROOVY_TOKEN_FILE=C:\wasql\php\temp\wasql-groovy-server.token
groovy -cp "groovy\lib\jar1.jar;groovy\lib\jar2.jar" groovy\server.groovy
```

> **Note:** List JDBC JARs explicitly — Groovy's `-cp` handler does not expand the `*` wildcard. WaSQL's auto-start does this automatically via `glob()`.

## Stopping the Server

```bash
# Via the shutdown endpoint (cleanest — flushes connections, deletes pid/token files)
curl -s -X POST http://127.0.0.1:7070/shutdown \
  -H "X-WaSQL-Token: $(cat /var/www/wasql/php/temp/wasql-groovy-server.token)"

# Via the PID file
kill $(cat /var/www/wasql/php/temp/wasql-groovy-server.pid)

# By process name
pkill -f server.groovy        # Linux
taskkill /F /IM java.exe      # Windows (kills all java processes)
```

---

## Configuration

All settings are controlled via environment variables. None are required — the defaults are production-safe.

### Full environment variable reference

| Variable | Default | Description |
|---|---|---|
| `WASQL_GROOVY_PORT` | `7070` | HTTP port the server listens on |
| `WASQL_GROOVY_THREADS` | `32` | Max concurrent request handler threads |
| `WASQL_GROOVY_MAX_BODY_MB` | `16` | Max request body size in megabytes (protects against runaway SQL) |
| `WASQL_GROOVY_IDLE_MINUTES` | `60` | Minutes of inactivity before auto-shutdown; `0` disables auto-shutdown |
| `WASQL_GROOVY_TOKEN` | *(random UUID)* | Fix the auth token to a specific value (useful when a process supervisor restarts the server and PHP must not re-read a new token) |
| `WASQL_GROOVY_PID_FILE` | `groovy/server.pid` | Path where the server writes its PID |
| `WASQL_GROOVY_TOKEN_FILE` | `groovy/server.token` | Path where the server writes its auth token |

WaSQL's auto-start sets `WASQL_GROOVY_PID_FILE` and `WASQL_GROOVY_TOKEN_FILE` to `php/temp/wasql-groovy-server.{pid,token}` so the web server user (which typically cannot write to the `groovy/` source directory) can create them.

### Startup log

On start the server logs its active configuration:

```
[wasql-groovy] 11:56:21 Listening on 127.0.0.1:7070  PID 12345
[wasql-groovy] 11:56:21 Token file: /var/www/wasql/php/temp/wasql-groovy-server.token
[wasql-groovy] 11:56:21 Threads: 32  Queue: 512  Max body: 16 MB
[wasql-groovy] 11:56:21 Auto-shutdown: 60 min idle
```

### PHP query timeout

The PHP helper `dbGroovyQueryResults()` defaults to a **300-second** HTTP timeout. Override per-call with `-timeout`:

```php
// Default — 300 seconds
$recs = dbGroovyQueryResults('mydb', $sql);

// Large export — 600 seconds
$recs = dbGroovyQueryResults('mydb', $sql, ['-timeout' => 600]);
```

---

## Keeping the Server Running

By default the server shuts itself down after **60 minutes of idle** to conserve resources. There are two approaches depending on how you want to manage it.

### Option A — Disable auto-shutdown and use a process manager (recommended for production)

Set `WASQL_GROOVY_IDLE_MINUTES=0` to disable the idle watchdog entirely, then let the OS restart the process if it ever crashes.

**Linux — systemd service**

Create `/etc/systemd/system/wasql-groovy.service`:

```ini
[Unit]
Description=WaSQL Groovy Bridge
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/wasql
Environment=WASQL_GROOVY_IDLE_MINUTES=0
Environment=WASQL_GROOVY_PID_FILE=/var/www/wasql/php/temp/wasql-groovy-server.pid
Environment=WASQL_GROOVY_TOKEN_FILE=/var/www/wasql/php/temp/wasql-groovy-server.token
ExecStart=/usr/bin/groovy -cp "groovy/lib/ctreeJDBC.jar" groovy/server.groovy
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

> Replace the `-cp` value with the actual JARs in your `groovy/lib/` directory.

```bash
sudo systemctl daemon-reload
sudo systemctl enable wasql-groovy
sudo systemctl start wasql-groovy
```

**Windows — Task Scheduler (on boot)**

Run once in an elevated PowerShell session:

```powershell
$action  = New-ScheduledTaskAction -Execute 'groovy' `
               -Argument '-cp "groovy\lib\ctreeJDBC.jar" groovy\server.groovy' `
               -WorkingDirectory 'C:\wasql'
$trigger = New-ScheduledTaskTrigger -AtStartup
$settings = New-ScheduledTaskSettingsSet -ExecutionTimeLimit (New-TimeSpan -Hours 0)
$principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -RunLevel Highest
Register-ScheduledTask -TaskName 'WaSQL Groovy Bridge' `
    -Action $action -Trigger $trigger -Settings $settings -Principal $principal
```

Set these in system environment variables so the task picks them up:
- `WASQL_GROOVY_IDLE_MINUTES=0`
- `WASQL_GROOVY_PID_FILE=C:\wasql\php\temp\wasql-groovy-server.pid`
- `WASQL_GROOVY_TOKEN_FILE=C:\wasql\php\temp\wasql-groovy-server.token`

---

### Option B — Let it idle-shutdown and restart automatically (lightweight)

Keep the default idle timeout and use a scheduled job that checks the PID file periodically.

**Linux — crontab**

Create `/var/www/wasql/groovy/start-if-stopped.sh`:

```bash
#!/bin/bash
WASQL_DIR="/var/www/wasql"
PID_FILE="$WASQL_DIR/php/temp/wasql-groovy-server.pid"

if [ -f "$PID_FILE" ] && kill -0 "$(cat $PID_FILE)" 2>/dev/null; then
    exit 0  # already running
fi

export WASQL_GROOVY_PID_FILE="$WASQL_DIR/php/temp/wasql-groovy-server.pid"
export WASQL_GROOVY_TOKEN_FILE="$WASQL_DIR/php/temp/wasql-groovy-server.token"

# List JARs explicitly — Groovy does not expand wildcards in -cp
JARS=$(find "$WASQL_DIR/groovy/lib" -name '*.jar' | paste -sd ':')

cd "$WASQL_DIR"
setsid nohup groovy -cp "$JARS" groovy/server.groovy \
    >"$WASQL_DIR/php/temp/wasql-groovy-server-start.log" 2>&1 &
```

```bash
chmod +x /var/www/wasql/groovy/start-if-stopped.sh
crontab -e
# Add (checks every 55 minutes — just under the 60-minute idle timeout):
*/55 * * * * /var/www/wasql/groovy/start-if-stopped.sh
```

**Windows — Task Scheduler (every 55 minutes)**

Create `C:\wasql\groovy\start-if-stopped.ps1`:

```powershell
$pidFile = 'C:\wasql\php\temp\wasql-groovy-server.pid'
if (Test-Path $pidFile) {
    $id = (Get-Content $pidFile).Trim()
    if (Get-Process -Id $id -ErrorAction SilentlyContinue) { exit }
}
$env:WASQL_GROOVY_PID_FILE   = 'C:\wasql\php\temp\wasql-groovy-server.pid'
$env:WASQL_GROOVY_TOKEN_FILE = 'C:\wasql\php\temp\wasql-groovy-server.token'
$jars = (Get-ChildItem 'C:\wasql\groovy\lib\*.jar').FullName -join ';'
Start-Process groovy `
    -ArgumentList "-cp `"$jars`" groovy\server.groovy" `
    -WorkingDirectory 'C:\wasql' `
    -WindowStyle Hidden
```

Register the task:

```powershell
$action  = New-ScheduledTaskAction -Execute 'powershell.exe' `
               -Argument '-NonInteractive -File C:\wasql\groovy\start-if-stopped.ps1'
$trigger = New-ScheduledTaskTrigger -RepetitionInterval (New-TimeSpan -Minutes 55) `
               -Once -At (Get-Date)
Register-ScheduledTask -TaskName 'WaSQL Groovy Watch' `
    -Action $action -Trigger $trigger -RunLevel Highest -Force
```

---

## Calling the Groovy Server

Every request to `http://127.0.0.1:7070` must include the `X-WaSQL-Token` header. Read the token from the token file and include it with each call.

### PHP helper

```php
function groovyRequest($path, $body = null, $contentType = 'text/plain') {
    $tokenFile = dirname(__DIR__) . '/php/temp/wasql-groovy-server.token';
    $token = trim(file_get_contents($tokenFile));
    $opts  = [
        'http' => [
            'method'  => $body === null ? 'GET' : 'POST',
            'header'  => "X-WaSQL-Token: $token\r\nContent-Type: $contentType",
            'content' => $body,
        ]
    ];
    $json = file_get_contents("http://127.0.0.1:7070$path", false, stream_context_create($opts));
    return json_decode($json, true);
}
```

### Python helper

```python
import json, urllib.request, pathlib

TOKEN_FILE = pathlib.Path('/var/www/wasql/php/temp/wasql-groovy-server.token')
BASE_URL   = 'http://127.0.0.1:7070'

def groovy_request(path, body=None, content_type='text/plain'):
    token = TOKEN_FILE.read_text().strip()
    data  = body.encode() if isinstance(body, str) else body
    req   = urllib.request.Request(
        BASE_URL + path,
        data    = data,
        headers = {'X-WaSQL-Token': token, 'Content-Type': content_type},
        method  = 'POST' if data else 'GET',
    )
    with urllib.request.urlopen(req) as r:
        return json.loads(r.read())
```

---

### Health check

**PHP**
```php
$status = groovyRequest('/ping');
// ["status" => "ok", "pid" => "1234", "uptime" => 43200000, "modules" => [...]]
```
**Python**
```python
status = groovy_request('/ping')
# {'status': 'ok', 'pid': '1234', 'uptime': 43200000, 'modules': [...]}
```

---

### Run a SELECT query

**PHP**
```php
$result = groovyRequest('/query/mydb', 'SELECT * FROM users WHERE active = 1');
// $result['data'] → array of rows
```
**Python**
```python
result = groovy_request('/query/mydb', 'SELECT * FROM users WHERE active = 1')
# result['data'] → list of row dicts
```

---

### Run an INSERT / UPDATE / DELETE

**PHP**
```php
$result = groovyRequest('/execute/mydb', "UPDATE users SET active = 0 WHERE id = 5");
// $result['data'] → affected row count
```
**Python**
```python
result = groovy_request('/execute/mydb', "UPDATE users SET active = 0 WHERE id = 5")
# result['data'] → affected row count
```

---

### Run a parameterized query

**PHP**
```php
$payload = json_encode([
    'query' => 'SELECT * FROM users WHERE id = :id AND active = :active',
    'args'  => ['id' => 5, 'active' => 1],
]);
$result = groovyRequest('/executeps/mydb', $payload, 'application/json');
// $result['data'] → array of rows
```
**Python**
```python
payload = json.dumps({
    'query': 'SELECT * FROM users WHERE id = :id AND active = :active',
    'args':  {'id': 5, 'active': 1},
})
result = groovy_request('/executeps/mydb', payload, 'application/json')
# result['data'] → list of row dicts
```

---

### Eval — run arbitrary Groovy

**PHP**
```php
$script = 'return db.queryResults("SELECT COUNT(*) AS total FROM users", DATABASE.mydb)';
$result = groovyRequest('/eval', $script);
// $result['result'] → return value  /  $result['output'] → printed output
```
**Python**
```python
script = 'return db.queryResults("SELECT COUNT(*) AS total FROM users", DATABASE.mydb)'
result = groovy_request('/eval', script)
# result['result'] → return value  /  result['output'] → printed output
```

---

### Reload modules and config (after editing config.xml or a .groovy file)

Re-reads `config.xml` and recompiles all modules from disk without restarting the server.

**PHP**
```php
$result = groovyRequest('/reload');
// $result['databases'] → list of all databases now loaded
```
**Python**
```python
result = groovy_request('/reload')
# result['databases'] → list of all databases now loaded
```

---

### Stop the server

**PHP**
```php
groovyRequest('/shutdown');
```
**Python**
```python
groovy_request('/shutdown')
```

---

## Security

The server uses two layers of protection:

1. **Localhost-only binding** — The HTTP listener binds to `127.0.0.1` only. It is unreachable from any other machine on the network, so no firewall rule or TLS certificate is required.

2. **Token authentication** — Every request must include a secret token in the `X-WaSQL-Token` header. Requests without a valid token receive `401 Unauthorized`.

### Token lifecycle

On startup the server generates a random UUID token and writes it to the token file (`php/temp/wasql-groovy-server.token` when auto-started by PHP):

```
X-WaSQL-Token: 550e8400-e29b-41d4-a716-446655440000
```

On shutdown (idle timeout, `/shutdown`, or process termination) the token file is deleted automatically. The file is listed in `.gitignore` so it is never committed.

### Using a fixed token

Set `WASQL_GROOVY_TOKEN` before starting the server to pin a specific value:

```bash
export WASQL_GROOVY_TOKEN=my-secret-token
groovy -cp "..." groovy/server.groovy
```

This is useful when a process supervisor restarts the server and PHP cannot re-read a newly generated token in time.

---

## JDBC Drivers

The server loads JDBC drivers from `groovy/lib/`. **No drivers are bundled** — you must download the JAR(s) for each database type you plan to use and place them in that directory.

| Database | `config.xml` `dbtype` prefix | JAR(s) needed | Download |
|---|---|---|---|
| MySQL / MariaDB | `mysql` | `mysql-connector-j-*.jar` | [dev.mysql.com](https://dev.mysql.com/downloads/connector/j/) · [Maven Central](https://central.sonatype.com/artifact/com.mysql/mysql-connector-j) |
| PostgreSQL | `postgre` | `postgresql-*.jar` | [jdbc.postgresql.org](https://jdbc.postgresql.org/download/) · [Maven Central](https://central.sonatype.com/artifact/org.postgresql/postgresql) |
| Microsoft SQL Server | `mssql` | `mssql-jdbc-*.jre11.jar` | [Microsoft Learn](https://learn.microsoft.com/en-us/sql/connect/jdbc/download-microsoft-jdbc-driver-for-sql-server) · [Maven Central](https://central.sonatype.com/artifact/com.microsoft.sqlserver/mssql-jdbc) |
| Oracle | `oracle` | `ojdbc11-*.jar` | [Oracle](https://www.oracle.com/database/technologies/maven-central-guide.html) · [Maven Central](https://central.sonatype.com/artifact/com.oracle.database.jdbc/ojdbc11) |
| SQLite | `sqlite` | `sqlite-jdbc-*.jar` | [GitHub Releases](https://github.com/xerial/sqlite-jdbc/releases) · [Maven Central](https://central.sonatype.com/artifact/org.xerial/sqlite-jdbc) |
| DuckDB | `duckdb` | `duckdb_jdbc-*.jar` | [GitHub Releases](https://github.com/duckdb/duckdb-java/releases) · [Maven Central](https://central.sonatype.com/artifact/org.duckdb/duckdb_jdbc) |
| Snowflake | `snowflake` | `snowflake-jdbc-*.jar` | [Snowflake Docs](https://docs.snowflake.com/en/developer-guide/jdbc/jdbc-download) · [Maven Central](https://central.sonatype.com/artifact/net.snowflake/snowflake-jdbc) |
| SAP HANA | `hana` | `ngdbc.jar` | [SAP Tools](https://tools.hana.ondemand.com/#hanatools) (requires SAP account) |
| Faircom c-tree | `ctree` | `ctreeJDBC.jar` | [Faircom](https://www.faircom.com) (requires Faircom license) |
| MS Access / CSV / Excel | `msaccess` / `mscsv` / `msexcel` | see below | [SourceForge](https://sourceforge.net/projects/ucanaccess/) · [Maven Central](https://central.sonatype.com/artifact/net.sf.ucanaccess/ucanaccess) |
| Firebird | `firebird` | `jaybird-full-*.jar` | [firebirdsql.org](https://firebirdsql.org/en/jdbc-driver/) · [Maven Central](https://central.sonatype.com/artifact/org.firebirdsql.jdbc/jaybird) |

### MS Access / CSV / Excel dependencies

UCanAccess (used for `msaccess`, `mscsv`, and `msexcel`) requires five JARs. Download them all and place them in `groovy/lib/`:

| JAR | Maven Central |
|---|---|
| `ucanaccess-*.jar` | [net.sf.ucanaccess:ucanaccess](https://central.sonatype.com/artifact/net.sf.ucanaccess/ucanaccess) |
| `jackcess-*.jar` | [com.healthmarketscience.jackcess:jackcess](https://central.sonatype.com/artifact/com.healthmarketscience.jackcess/jackcess) |
| `commons-lang3-*.jar` | [org.apache.commons:commons-lang3](https://central.sonatype.com/artifact/org.apache.commons/commons-lang3) |
| `commons-logging-*.jar` | [commons-logging:commons-logging](https://central.sonatype.com/artifact/commons-logging/commons-logging) |
| `hsqldb-*.jar` | [org.hsqldb:hsqldb](https://central.sonatype.com/artifact/org.hsqldb/hsqldb) |

The UCanAccess download from SourceForge includes all five JARs as a pre-bundled zip.

---

## Endpoints

All endpoints listen on `127.0.0.1:7070` (localhost only).

### `GET /ping`

Health check. Returns server status, PID, uptime in milliseconds, and loaded module names.

```json
{
  "status": "ok",
  "pid": "12345",
  "uptime": 43200000,
  "modules": ["common", "config", "db", "mysqldb"]
}
```

---

### `POST /query/{dbname}`

Run a SELECT query against a named database. The request body is raw SQL.

**Request:**
```
POST /query/mydb
Content-Type: text/plain

SELECT * FROM users WHERE active = 1
```

**Response:**
```json
{
  "success": true,
  "data": [
    { "id": 1, "name": "Alice", "created": "2024-03-15 10:30:00" }
  ]
}
```

The `{dbname}` must match a `name` attribute in `config.xml`. Date/time columns are automatically formatted as `YYYY-MM-DD HH:MM:SS`.

---

### `POST /execute/{dbname}`

Run an INSERT, UPDATE, DELETE, or DDL statement. The request body is raw SQL.

**Request:**
```
POST /execute/mydb
Content-Type: text/plain

UPDATE users SET active = 0 WHERE last_login < '2023-01-01'
```

**Response:**
```json
{
  "success": true,
  "data": 42
}
```

`data` is the affected row count.

---

### `POST /executeps/{dbname}`

Run a parameterized query. The request body is JSON with `query` (required) and `args` (optional map of named parameters).

**Request:**
```json
{
  "query": "SELECT * FROM users WHERE id = :id AND active = :active",
  "args": { "id": 5, "active": 1 }
}
```

**Response:**
```json
{
  "success": true,
  "data": [{ "id": 5, "name": "Alice" }]
}
```

---

### `POST /eval`

Execute an arbitrary Groovy script. The request body is the raw Groovy code.

The following variables are pre-bound in the script's scope:

| Variable | Description |
|---|---|
| `db` | The `db` module (database helpers) |
| `common` | The `common` module |
| `config` | The `config` module |
| `DATABASE` | Map of all configured databases from `config.xml` |
| `SCRIPT_DIR` | Absolute path to the `groovy/` directory |

**Request:**
```
POST /eval
Content-Type: text/plain

def rows = db.queryResults("SELECT COUNT(*) AS total FROM users", DATABASE.mydb)
return rows
```

**Response:**
```json
{
  "success": true,
  "output": "",
  "result": [{ "total": 123 }]
}
```

- **`result`** — the return value of the script (last expression or explicit `return`)
- **`output`** — anything printed to stdout (`println`, `print`) during execution

---

### `GET /reload`

Re-reads `config.xml` and flushes the in-memory module cache. Modules are recompiled from disk on the next request. Use this after editing `config.xml` (to add/remove a database) or after editing a `.groovy` module file, without restarting the server.

**Response:**
```json
{
  "status": "reloaded",
  "cleared": ["common", "config", "ctreedb", "db"],
  "databases": ["ctree_dev", "ctree_prod", "mydb"]
}
```

---

### `GET /databases`

Lists all configured databases that have a loaded driver, grouped by type.

**Response:**
```json
{
  "success": true,
  "data": {
    "ctree": ["ctree_dev", "ctree_prod"],
    "mysql": ["mydb"]
  }
}
```

---

### `GET /exit` or `POST /shutdown`

Gracefully stops the server. Deletes the PID and token files, waits up to 5 seconds for in-flight requests to finish, then exits.

**Response:**
```json
{
  "status": "shutting down",
  "pid": "12345"
}
```

---

## Error Responses

All endpoints return a consistent error shape on failure:

```json
{
  "success": false,
  "error": "Database 'foo' not found in config.xml. Available: bar, baz"
}
```

`/eval` also includes any captured stdout in error responses:

```json
{
  "success": false,
  "error": "No such property: xyz",
  "output": "got here\n"
}
```

## Data Type Handling

The server normalizes JDBC types to JSON-friendly values automatically:

| JDBC / Java Type | JSON Output |
|---|---|
| `TIMESTAMP`, `DATETIME` | `"2024-03-15 10:30:00"` |
| `DATE` | `"2024-03-15"` |
| `TIME` | `"10:30:00"` |
| `BLOB` / `byte[]` | Base64-encoded string |
| `CLOB` | Plain string |
| `ARRAY` | JSON array |
| PostgreSQL `json` / `jsonb` | Embedded JSON object |
| Numbers, booleans, nulls | Native JSON |

## PID and Token Files

When auto-started by PHP, the server writes its PID and auth token to the WaSQL temp directory:

```
php/temp/wasql-groovy-server.pid
php/temp/wasql-groovy-server.token
```

Both files are deleted automatically on shutdown. Override the locations via `WASQL_GROOVY_PID_FILE` and `WASQL_GROOVY_TOKEN_FILE`.

## PowerShell Examples

```powershell
# Read token from file
$token = (Get-Content 'php\temp\wasql-groovy-server.token').Trim()
$headers = @{ 'X-WaSQL-Token' = $token }

# Health check
Invoke-RestMethod -Uri 'http://127.0.0.1:7070/ping' -Headers $headers

# Query
Invoke-RestMethod -Uri 'http://127.0.0.1:7070/query/mydb' -Method Post `
    -Headers $headers -ContentType 'text/plain' -Body 'SELECT * FROM users LIMIT 5'

# Execute
Invoke-RestMethod -Uri 'http://127.0.0.1:7070/execute/mydb' -Method Post `
    -Headers $headers -ContentType 'text/plain' `
    -Body "UPDATE users SET active = 1 WHERE id = 5"

# Eval
Invoke-RestMethod -Uri 'http://127.0.0.1:7070/eval' -Method Post `
    -Headers $headers -ContentType 'text/plain' -Body 'return 1 + 1'

# Reload config and modules
Invoke-RestMethod -Uri 'http://127.0.0.1:7070/reload' -Headers $headers

# Stop server
Invoke-RestMethod -Uri 'http://127.0.0.1:7070/shutdown' -Method Post -Headers $headers
```
