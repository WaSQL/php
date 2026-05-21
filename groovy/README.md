# WaSQL Groovy Server

A persistent Groovy HTTP daemon that provides fast JDBC database access and Groovy script evaluation for WaSQL. Eliminates JVM cold-start overhead by keeping the JVM and compiled modules alive between requests.

## How It Works

On first start the server:
1. Parses `config.xml` to build the `DATABASE` map
2. Pre-compiles every DB driver module needed by the configured databases
3. Pre-loads the `common` and `db` modules
4. Starts an HTTP listener on `127.0.0.1:7070`
5. Writes its PID to `groovy/server.pid`

Subsequent requests hit an already-warm JVM with compiled modules cached in memory — typically sub-100ms vs several seconds for a cold start.

The server shuts itself down automatically after **10 minutes of idle** (no requests). It can also be stopped manually via the `/exit` endpoint. The PID file is deleted on shutdown.

## Starting the Server

```bash
groovy -cp "groovy/lib/*" groovy/server.groovy
```

Override the default port with an environment variable:

```bash
set WASQL_GROOVY_PORT=8080
groovy -cp "groovy/lib/*" groovy/server.groovy
```

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

Both can be populated at the same time:
```groovy
println "fetching users..."
return db.queryResults("SELECT * FROM users", DATABASE.mydb)
```
```json
{
  "success": true,
  "output": "fetching users...\n",
  "result": [{ "id": 1, "name": "Alice" }]
}
```

---

### `GET /reload`

Flushes the in-memory module cache. The next request to any endpoint will recompile modules from disk. Use this during development after editing a `.groovy` module file without restarting the server.

**Response:**
```json
{
  "status": "reloaded",
  "cleared": ["common", "config", "db", "mysqldb"]
}
```

---

### `GET /exit` or `GET /shutdown`

Gracefully stops the server. Deletes the PID file and exits.

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
  "error": "Database 'foo' not found in config.xml"
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

## PID File

The server writes its process ID to `groovy/server.pid` on startup and deletes it on shutdown. PHP can use this file to check whether the server is running before attempting a connection, and to start it if not.

## PowerShell Examples

```powershell
# Health check
Invoke-RestMethod -Uri 'http://127.0.0.1:7070/ping'

# Query
Invoke-RestMethod -Uri 'http://127.0.0.1:7070/query/mydb' -Method Post -ContentType 'text/plain' -Body 'SELECT * FROM users LIMIT 5'

# Execute
Invoke-RestMethod -Uri 'http://127.0.0.1:7070/execute/mydb' -Method Post -ContentType 'text/plain' -Body "UPDATE users SET active = 1 WHERE id = 5"

# Eval
Invoke-RestMethod -Uri 'http://127.0.0.1:7070/eval' -Method Post -ContentType 'text/plain' -Body 'return 1 + 1'

# Reload modules
Invoke-RestMethod -Uri 'http://127.0.0.1:7070/reload'

# Stop server
Invoke-RestMethod -Uri 'http://127.0.0.1:7070/exit'
```
