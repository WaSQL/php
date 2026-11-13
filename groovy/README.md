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

## Groovy Manager (admin page)

**WaSQL admin → Database → Groovy Manager** (`/php/admin.php?_menu=groovy`, admins only) monitors and controls the auto-started server. Its files are `php/admin/groovy_*`.

- **Status tab:** state, plus PID, uptime, threads busy/queued, requests served, JVM memory, versions, stuck threshold, log size and loaded modules. Below that, the **in-flight requests** (thread, endpoint, SQL, age, thread state, expandable stack; STUCK ones highlighted) and the databases the server can query. Each in-flight row has a **Cancel** button (see `/cancel`). Actions depend on the state:
  - **Start**, when stopped.
  - **Restart**, **Reload Modules** and **Stop**, when it is running.
  - **Force Kill**, when there is a pid file. Use it for a hung server that ignores `/shutdown`; it also removes the pid/token files.
- **Stats tab:** totals, error rate, average time and requests in the last 1/5/15/60 minutes; counters per database and per endpoint; the 10 slowest requests; the last 10 errors with their messages; **Reset Stats**. Every non-zero error count is a link that opens that endpoint's or database's errors in a popup (from `/errors`).
- **Log tab:** the tail of `logs/groovy-server.log` (100–5000 lines), filtered to all lines, STUCK reports with their stacks, errors and warnings, or requests only.
- **Auto-refresh** every 10 seconds (paused while a stack trace is open).

**Who ran it:** PHP's `dbGroovyHttp` sends `X-WaSQL-User` (the logged-in username, or `cli` for cron/command-line scripts) and `X-WaSQL-Host` (the site) with every request. The server reduces both to safe characters and records them. Every request log line ends `| user (host)`, and so do the error, STUCK and CANCEL lines. They also appear in the in-flight table, slowest/errors lists and the **Users** tile, and **By user** counts every distinct caller since start. `/errors` accepts `&user=`. Callers that don't send the headers show as `-`.

The page reads `/status` and `/stats` with a 3-second timeout, so a hung server shows as **Not responding** instead of hanging the page. An older `server.groovy` that only has `/ping` shows as **Running (older version)**; Restart loads the current one.

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
| `WASQL_GROOVY_THREADS` | `128` | Request handler threads. Generous on purpose: a request waiting for a connection slot holds one, so this must stay well above the connection caps. Idle threads exit after 60 s. When all are busy and 512 more requests are queued, extra requests run on one-off overflow threads (logged), never on the server's dispatcher thread |
| `WASQL_GROOVY_CTREE_MAX_CONNECTIONS` | `16` | Default connection cap for **c-tree** databases (see *Connection caps*). Set it within your FairCom licence; a `max_connections` attribute on a `<database>` tag overrides it |
| `WASQL_GROOVY_MAX_BODY_MB` | `16` | Max request body size in megabytes (protects against runaway SQL) |
| `WASQL_GROOVY_IDLE_MINUTES` | `60` | Minutes of inactivity before auto-shutdown; `0` disables auto-shutdown |
| `WASQL_GROOVY_TOKEN` | *(random UUID)* | Fix the auth token to a specific value (useful when a process supervisor restarts the server and PHP must not re-read a new token) |
| `WASQL_GROOVY_PID_FILE` | `groovy/server.pid` | Path where the server writes its PID |
| `WASQL_GROOVY_TOKEN_FILE` | `groovy/server.token` | Path where the server writes its auth token |
| `WASQL_GROOVY_STUCK_MINUTES` | `5` | A request still running after this many minutes is logged once as `STUCK`, with its thread's stack trace; `0` disables |
| `WASQL_GROOVY_LOG_FILE` | *(unset)* | The file stdout/stderr are redirected to. When set, the server trims it as a rolling log (Linux/macOS). WaSQL's auto-start sets it |
| `WASQL_GROOVY_LOG_MAX_MB` | `10` | Rolling-log cap for `WASQL_GROOVY_LOG_FILE`; `0` = no cap |

WaSQL's auto-start sets `WASQL_GROOVY_PID_FILE` and `WASQL_GROOVY_TOKEN_FILE` to `php/temp/wasql-groovy-server.{pid,token}` so the web server user (which typically cannot write to the `groovy/` source directory) can create them.

### Startup log

On start the server logs its active configuration:

```
[wasql-groovy] 11:56:21 Listening on 127.0.0.1:7070  PID 12345
[wasql-groovy] 11:56:21 Token file: /var/www/wasql/php/temp/wasql-groovy-server.token
[wasql-groovy] 11:56:21 Threads: 128  Queue: 512  Max body: 16 MB
[wasql-groovy] 11:56:21 Connection caps: ctree_remote_repl_groovy=16 (per caller 4)
[wasql-groovy] 11:56:21 Auto-shutdown: 60 min idle
[wasql-groovy] 11:56:21 Stuck-request report: after 5 min
[wasql-groovy] 11:56:21 Log cap: /var/www/wasql/logs/groovy-server.log rolls at 10 MB (keeps the newest half)
```

### Server log (auto-start)

WaSQL's auto-start writes stdout/stderr to **`logs/groovy-server.log`** and **appends** across restarts, so a restart no longer wipes the log of the run that hung. Each launch writes a `--- {date} starting Groovy server ---` marker.

The log is a **rolling log** capped by `<groovy_log_max_mb>` in `config.xml` (default `10`, `0` = no cap). Once it passes the cap, the oldest lines are dropped and the newest half is kept. That happens at launch (PHP) and once a minute while the server runs (Linux/macOS only; on Windows the trim happens only at launch).

### Stuck-request watchdog

Every request is tracked while it runs. Once a minute the watchdog logs any request older than `WASQL_GROOVY_STUCK_MINUTES` (default 5), **once per request**, with the endpoint, the database and the first 100 characters of the SQL, plus the handler thread's stack trace (top 25 frames, with Groovy dispatch plumbing filtered out). While anything is stuck it also logs a pool summary:

```
[wasql-groovy] 14:42:36 STUCK request on wasql-handler-3 for 6 min: /query/mydb | mydb: SELECT ... | state=RUNNABLE
        at ...the driver / socket frames it is blocked in...
[wasql-groovy] 14:42:36 STUCK summary: 1 request(s) past their stuck threshold | threads busy 1/32 | queued 0
```

When the caller sent its own timeout (`X-WaSQL-Timeout`, see below), the threshold for that request is the longer of the two, so a query that's expected to run 30 minutes isn't reported at 5. A request still running after its caller gave up is reported with `caller gave up after Ns - still running for nobody`.

If the server stops answering, `grep STUCK logs/groovy-server.log` shows what each thread was blocked on, with no thread dump needed. The watchdog only reports; it never kills a request.

### Query timeout (PHP ↔ server ↔ database)

`dbGroovyQueryResults()` waits **300 seconds** by default. Raise it per call with `-timeout`, or per connection with a `timeout` attribute on the `<database>` tag in `config.xml`. A per-call `-timeout` wins:

```php
// Default — 300 seconds (or the connection's timeout="N")
$recs = dbGroovyQueryResults('mydb', $sql);

// Long report — 30 minutes
$recs = dbGroovyQueryResults('mydb', $sql, ['-timeout' => 1800]);
```
```xml
<database name="ctree_reports" dbtype="ctree" groovy="1" timeout="1800" ... />
```

PHP sends that number to the server as the `X-WaSQL-Timeout` header. The server then sets the JDBC statement's `querytimeout` a little lower (10%, between 1 and 10 s), so the database cancels a query the caller is no longer waiting for, rather than it running on. A `querytimeout` attribute on the `<database>` tag overrides this. Without the header (older PHP cores), the drivers keep their old behavior: ctree 600 s, CSV exports 600 s, other drivers' list queries unlimited.

The server never shuts itself down for idleness while a query is running. If the caller hangs up before the reply is sent, the log says `client disconnected before the response was sent - request ran Ns, caller timeout was Ms`, and `/stats` counts it as a 499.

A web page should not wait half an hour, because Apache/PHP-FPM proxy timeouts and browsers give up first. Run long queries from cron, or stream them to disk with `-filename`.

### Streamed results (`X-WaSQL-Stream: 1`)

A `/query` request carrying `X-WaSQL-Stream: 1` (which `dbGroovyQueryResults` sends) gets its rows streamed while they're read, rather than collected into one JSON document first. The server holds one row at a time, so a million-row result costs it no more memory than a five-row one.

```
HTTP/1.1 200 OK
Content-Type: application/x-ndjson; charset=UTF-8
Transfer-Encoding: chunked

{"tbl":"sys_chk_constrs","col":"owner","coltype":"nvarchar"}
{"tbl":"sys_chk_constrs","col":"tblname","coltype":"nvarchar"}
["end",2]
```

- One JSON object per line per row, using the same converters as a normal response (dates, NULLs, LOBs are unchanged).
- Exactly one control line at the end, always a JSON **array**: `["end", rowCount]`, or `["error", "message"]` if the query failed after rows had already been sent. The caller must discard those rows. **No control line = the stream was cut off and is incomplete.**
- An error before the first row is an ordinary `{"success":false,"error":"..."}` reply with a 4xx/5xx status.
- If the caller hangs up, the next row write fails and the query stops right there. The log shows `client disconnected after N rows were sent`.
- Without the header (curl by hand, older PHP cores) you get the classic `{"success":true,"data":[...]}` reply.

On the PHP side, the rows from a stream are collected into the usual array, **except**:
- `'-process'=>'functionName'`: each row is passed to `functionName($row)` as it arrives and nothing is kept, so any number of rows fits in memory. The return value is the row count, as on the native c-tree path. If the query fails part-way, rows already processed stay processed and an error string is returned.
- Without `-process`/`-filename`, a result that brings the PHP request to 85% of `memory_limit` is stopped. The transfer is aborted, the server sees the hang-up and stops the query, and an error string names both options, instead of the page dying with *Allowed memory size exhausted*.

### Connection caps

FairCom licenses concurrent c-tree sessions, and every query opens one. Going over the licence fails **everyone's** queries with *Maximum users exceeded*. So the server allows only so many concurrent requests per database (`/query`, `/queryfile`, `/execute`, `/executeps`, `/meta`; `/eval` is not capped):

```xml
<database name="ctree_remote_repl_groovy" dbtype="ctree" groovy="1" max_connections="16" max_user_connections="4" ... />
```

| attribute | default | meaning |
|---|---|---|
| `max_connections` | c-tree: `WASQL_GROOVY_CTREE_MAX_CONNECTIONS` (16); other types: `0` = no cap | concurrent connections to this database |
| `max_user_connections` | a quarter of `max_connections`, at least 1 | one caller's share, keyed by `X-WaSQL-User` + `X-WaSQL-Host`. **All cron/CLI scripts share the user `cli`.** |

A request first waits for its caller's share, then for a free connection, both in arrival order. While it waits, `/status` shows it with a `phase` such as *waiting for a free connection to X (16 max, 3 waiting)*, and `slots` lists in-use/waiting per database. If no slot frees up before the caller's own timeout (or 300 s if it sent none), it gets **HTTP 503** with a plain message such as *Busy: all 16 connections to 'X' are in use (5 more requests waiting) - waited 290s*. Any wait over 5 s is logged. The time spent waiting comes out of the statement's `querytimeout`. Startup logs every cap (`Connection caps: ...`) so a wrong number shows up immediately.

Drivers take part through `params.rowsink`: their list path starts with `def recs = params.rowsink != null ? params.rowsink : []`, so `recs << rec` sends the row on. A driver without that line still works: the server streams the list it returns, but it gets no memory saving.

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

### `GET /status`

Monitoring snapshot used by the Groovy Manager page. Unlike `/ping`, it does **not** count as activity, so a monitoring page left open never keeps an idle server alive past `WASQL_GROOVY_IDLE_MINUTES`.

Returns `pid`, `started`, `uptime_ms`, `idle_ms`, plus:
- `versions`: groovy, java, java_vm, java_home, os
- `config`: port, threads, queue_cap, max_body_mb, idle_minutes, stuck_minutes, log_file, log_max_mb, script_dir
- `pool`: active (excluding this request), max, largest, queued, completed
- `memory`: used_mb, total_mb, max_mb
- `modules`
- `inflight`: one entry per running request, longest first: thread, path, detail (db + first 100 chars of the SQL), age_ms, state, stuck, stack (filtered, top 25 frames)

---

### `GET /stats[?reset=1]`

Request statistics since server start, or since the last `?reset=1`. These don't count as activity for the idle timeout either.

- **`totals`:** requests, errors, avg_ms, and last_1m / last_5m / last_15m / last_60m. Only the work endpoints count here: `/query`, `/queryfile`, `/execute`, `/executeps`, `/meta`, `/eval`.
- **`by_endpoint`** and **`by_db`:** name, count, errors, avg_ms, max_ms, total_ms, last_at; busiest first. Monitoring endpoints like `/ping` show up in `by_endpoint` only. `/status` and `/stats` aren't counted.
- **`slowest`:** the 10 slowest requests (at, endpoint, db, ms, code, detail).
- **`errors`:** the last 10 failures, newest first, with the error message. A failure is any HTTP 400+ response, or a handler that died without responding.

---

### `GET /errors[?endpoint=/query][&db=name]`

The error log: the last 200 failed requests across every counted endpoint, newest first, optionally filtered by endpoint and/or database. Each entry has at, endpoint, db, ms, code, detail and error. Groovy Manager's clickable error counts use it.

---

### `GET /cancel?thread=wasql-handler-N`

Cancels an in-flight request (thread names come from `/status` → `inflight`).

1. **Aborts the request's JDBC connections** with `Connection.abort()`, falling back to `close()`. Each connection gets a 5-second limit on a separate daemon thread, so a driver that blocks on close can't hang `/cancel` itself.
2. **Interrupts the request's thread**, which ends sleeps and waits (for example in `/eval`).
3. **Labels the failure.** The request then fails with `Cancelled from Groovy Manager (...)`, and a `CANCEL requested` line goes to the log.

The response lists the outcome for each connection, for example `aborted+closed`, or `driver did not release the connection within 5s`.

**What actually stops a query:** every driver module's row loop checks `wasqlCancelled` between rows and throws `request cancelled` once its request has been flagged. That's the dependable part. The c-tree driver, for example, reports `aborted+closed` but keeps fetching, and the row check stops it within a row. Abort/close covers the other case: a query still blocked in `executeQuery` before its first row. Whether that ends depends on the driver. If it doesn't, the request stays in flight; Restart or Force Kill the server. A cancelled `/queryfile` deletes its partial CSV.

**How connections are tracked:** `loadModule` evaluates each driver module with a `wasqlTrackConnection` binding variable, and every driver's `connect()` calls it with its JDBC connection. A module run outside the server (the CLI `evalPHP` path) doesn't have the variable, so the call does nothing. **A new driver module should include the same two lines before `return sql` in its `connect()`**:

```groovy
// Groovy server only: register the connection so Groovy Manager can cancel this request (no-op from the CLI)
if (binding.hasVariable('wasqlTrackConnection')) { binding.getVariable('wasqlTrackConnection').call(sql.connection) }
```

Its row loops (`while (rs.next())`, `while (true) { if (!rs.next()) break ...`, `sql.eachRow { row -> ...`) should start with the cancel check. Put it **outside** any per-row `try` that swallows errors (`skiperrors`):

```groovy
// Groovy Manager cancel: stop between rows - some drivers (c-tree) ignore Connection.abort, so this is what stops them
if (binding.hasVariable('wasqlCancelled') && binding.getVariable('wasqlCancelled').call()) throw new java.sql.SQLException('request cancelled')
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
