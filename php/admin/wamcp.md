# WaMCP — WaSQL Model Context Protocol Server

WaMCP exposes your WaSQL databases as MCP tools so AI assistants (Claude, Cursor, Windsurf, etc.) can query and inspect them directly during a conversation.

---

## How It Works

WaMCP is a PHP page (`wamcp`) that speaks the [MCP JSON-RPC 2.0 protocol](https://modelcontextprotocol.io) over HTTP. When an AI tool calls one of the WaMCP tools, it sends a POST request to your WaSQL instance. Authentication is handled via the `WaSQL_auth` request header — the same token the WaSQL admin UI uses.

### Available Tools

| Tool | Parameters | Description |
|---|---|---|
| `help` | | List all available tools with descriptions |
| `databases` | `[dbtype]` | List WaMCP-enabled databases, optionally filtered by type (mysql, postgresql, mssql, etc.) |
| `setdb` | `{dbname}` | Set the active database for this session |
| `getdb` | | Show connection info for the active database |
| `getuser` | | Show info about the authenticated user |
| `tables` | `[filter]` | List tables, optionally filtered by name substring |
| `fields` | `{tablename} [filter]` | List columns for a table, optionally filtered by name substring |
| `ddl` | `{tablename}` | Show the `CREATE TABLE` statement for a table |
| `indexes` | `{tablename} [filter]` | Show indexes on a table, optionally filtered by column name |
| `query` | `{sql}` | Execute a read-only SQL query (SELECT, SHOW, EXPLAIN, DESCRIBE, WITH) |

All tools except `databases`, `setdb`, `help`, and `getuser` accept an optional `db_id` argument to target a specific database per-call.

### Database Targeting

The active database is resolved in this order:
1. `db_id` argument passed directly to the tool call
2. `db_id` path segment in the MCP URL (e.g. `?_menu=wamcp/mydb`)
3. The database saved in the user's profile (`_users.wamcp` JSON column)
4. The first WaMCP-enabled database in the server config

User database preference persists across sessions — selecting a database once saves it to `_users.wamcp` for that user.

---

## Enabling / Disabling Databases

All databases in your WaSQL server config are available to WaMCP by default. To **exclude** a database, set `wamcp=false` in its config block:

```xml
<database
    name="internal_db"
    dbtype="mysqli"
    dbhost="localhost"
    dbname="internal_db"
    dbuser="myuser"
    dbpass="mypassword"
    wamcp="false" />
```

You can also set `wamcp` to a friendly display name — that name will appear in the `databases` tool output:

```xml
<database
    name="mydb"
    dbtype="mysqli"
    dbhost="localhost"
    dbname="mydb"
    dbuser="myuser"
    dbpass="mypassword"
    wamcp="My Database" />
```

Access also requires the user to be a **WaSQL admin** (`isAdmin()`). Standard user accounts cannot connect.

---

## Finding Your Auth Token

1. Log into the WaSQL admin UI
2. Go to your **User Profile** (top-right menu → your name)
3. Copy the **Auth Token** (`_auth`) value shown on that page

This token is unique per user and grants the same access level as your WaSQL login. Keep it secret — treat it like a password.

---

## Setup: Claude Code (CLI)

Claude Code reads MCP servers from `C:\Users\<you>\.claude.json` (Windows) or `~/.claude.json` (Mac/Linux). Add a `mcpServers` entry at the root of that file:

```json
{
  "mcpServers": {
    "wamcp": {
      "type": "http",
      "url": "http://your-wasql-host/php/admin.php?_menu=wamcp",
      "headers": {
        "WaSQL_auth": "YOUR_AUTH_TOKEN_HERE"
      }
    }
  }
}
```

To target a specific database by default, append its `db_id` to the URL:

```
"url": "http://your-wasql-host/php/admin.php?_menu=wamcp/mydb"
```

After saving, restart Claude Code. The `wamcp` tools will appear automatically.

---

## Setup: Cursor / Windsurf / Zed / Continue

These editors support MCP via a config file — typically `.cursor/mcp.json`, `.windsurf/mcp.json`, or the editor's settings UI. Use the same HTTP transport format:

```json
{
  "mcpServers": {
    "wamcp": {
      "type": "http",
      "url": "http://your-wasql-host/php/admin.php?_menu=wamcp",
      "headers": {
        "WaSQL_auth": "YOUR_AUTH_TOKEN_HERE"
      }
    }
  }
}
```

Refer to your editor's MCP documentation for the exact config file location.

---

## Setup: ChatGPT

Add WaMCP as a remote MCP server in ChatGPT Settings → Connectors → Add MCP Server. Use the same HTTP endpoint and pass the `WaSQL_auth` token as a custom header.

---

## Setup: Other LLMs / Generic MCP Clients

Any MCP client that supports **HTTP Streamable transport** (MCP protocol version `2024-11-05`) can connect:

- **Endpoint:** `POST http://your-wasql-host/php/admin.php?_menu=wamcp`
- **Protocol:** JSON-RPC 2.0, MCP `2024-11-05`
- **Auth header:** `WaSQL_auth: YOUR_AUTH_TOKEN`
- **Content-Type:** `application/json`

The server responds to `initialize`, `tools/list`, and `tools/call` in standard MCP format.

---

## Security Notes

- The `WaSQL_auth` token authenticates as a specific WaSQL user — that user's permissions apply to all queries.
- All databases are exposed by default; set `wamcp="false"` on any database you want to hide from WaMCP.
- The `query` tool only permits read-only statements (SELECT, SHOW, EXPLAIN, DESCRIBE, WITH) — write operations are rejected.
- For production, run WaSQL behind HTTPS so the auth token is not transmitted in plaintext.
- Each user has their own token. Revoke access by changing the user's password or disabling their account in the WaSQL admin.
