# DaSQL — DOS Access to SQL

Run SQL queries against any [WaSQL](https://wasql.com)-connected database directly from **Notepad++**, **Sublime Text**, **VS Code**, **JetBrains IDEs**, or **Vim/Neovim** — and from the command line. Select a query, press **F8**, and see results in a panel — no separate database client required.

---

## Table of Contents

- [What is DaSQL?](#what-is-dasql)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
  - [Authentication methods](#authentication-methods)
  - [Output formats](#output-formats)
  - [Per-section settings](#per-section-settings)
  - [Shortcuts](#shortcuts)
- [Running queries](#running-queries)
  - [From the command line](#from-the-command-line)
  - [From an editor](#from-an-editor)
- [Special commands](#special-commands)
- [Running scripts](#running-scripts)
- [CLI mode](#cli-mode)
- [Editor setup](#editor-setup)
  - [Notepad++](#notepad)
  - [Sublime Text](#sublime-text)
  - [Visual Studio Code](#visual-studio-code)
  - [JetBrains IDEs](#jetbrains-ides)
  - [Vim / Neovim](#vim--neovim)
- [Troubleshooting](#troubleshooting)

---

## What is DaSQL?

DaSQL is a lightweight Python script that acts as a bridge between your text editor and any database connected to a WaSQL server. It works by sending the selected text (or current line) to WaSQL's SQL engine via HTTP and printing the results back to the editor's output panel.

**Key benefits:**

- Query any database your WaSQL server has access to — MySQL, PostgreSQL, SQL Server, Oracle, SAP HANA, cTree, and more — all from the same file
- Switch databases by changing which `.sql` file you are editing; the filename drives the connection
- Works in every major editor through a single Python script and a config file
- Supports running PHP, Python, Lua, and other scripts from the same interface
- No VPN or direct database credentials needed on the client — authentication goes through WaSQL

---

## Requirements

- **Python 3.8+**
- **WaSQL server** with at least one database configured
- A WaSQL auth key (found under the profile menu in the WaSQL admin portal)

Install Python dependencies:

```
pip install requests chardet markdown
```

---

## Installation

1. Place the DaSQL folder somewhere permanent (e.g. `C:\wasql\dasql`).
2. Copy `dasql.ini.sample` to `dasql.ini`.
3. Edit `dasql.ini` and set your `base_url` and `authkey` in the `[global]` section.
4. Run the installer for your editor (see [Editor setup](#editor-setup)).

---

## Configuration

`dasql.ini` is a standard INI file. The `[global]` section sets defaults; each named section defines a database connection.

```ini
[global]
base_url     = https://your-wasql-server.example.com
authkey      = YOUR_AUTH_KEY
output_format = csv
db           =

[my_database]
db           = my_database
output_format = dos
```

Open a file named `my_database.sql` in your editor and press F8 — DaSQL automatically uses the `[my_database]` section.

### Authentication methods

DaSQL supports all six WaSQL authentication methods. Add whichever one applies to your section (or `[global]`):

| Method | Keys required |
|--------|--------------|
| Auth key | `authkey = ...` |
| Temporary auth key | `tauthkey = ...` |
| API key | `apikey = ...` and `username = ...` |
| Username + password | `username = ...` and `password = ...` |
| Email + password | `email = ...` and `password = ...` |
| Phone + password | `phone = ...` and `password = ...` |

### Output formats

| Format | Description |
|--------|-------------|
| `dos` | Fixed-width tabular — best for editors |
| `csv` | Comma-separated values |
| `json` | JSON array of objects |
| `xml` | XML |
| `html` | HTML table |
| `table` | Markdown-style table |

### Per-section settings

Any key in `[global]` can be overridden in a named section:

```ini
[reporting]
db            = reporting_db
base_url      = https://reporting.example.com
authkey       = REPORTING_AUTH_KEY
output_format = dos
```

A section can also store a default query:

```ini
[slow_queries]
db    = production
query = SELECT pid, query, state FROM pg_stat_activity WHERE state != 'idle' ORDER BY query_start
```

Pressing F8 on any line of `slow_queries.sql` with nothing selected runs that default query.

### Shortcuts

Define reusable query aliases inside `dasql.ini`. A shortcut specific to a section overrides a global one with the same name.

```ini
[global:tables]
query = SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'

[mydb:tables]
query = SELECT relname, n_live_tup FROM pg_stat_user_tables ORDER BY n_live_tup DESC
```

Type `tables` on any line and press F8. When editing `mydb.sql`, the section-specific version runs; all other files use the global version.

---

## Running queries

### From the command line

```
python3 dasql.py <section> <dirname> <query>
```

The most common patterns:

```bash
# Run a query against a named section
python3 dasql.py mydb . "select count(*) from orders"

# Run a .sql file (filename must match an ini section)
python3 dasql.py mydb.sql . mydb.sql

# Run a saved shortcut
python3 dasql.py mydb . tables
```

### From an editor

Open a `.sql` file whose name matches a section in `dasql.ini` (e.g. `mydb.sql` for the `[mydb]` section). Select the SQL you want to run and press **F8**. With nothing selected, the current line is executed.

---

## Special commands

These work on any line regardless of which `.sql` file is open:

| Prefix | What it does | Example |
|--------|-------------|---------|
| `math>` or `calc>` | Evaluates a Python expression | `math> 1024 * 1024 * 512` |
| `cmd>` | Runs a shell command | `cmd> dir C:\data` |
| `C:\path>command` | Runs a shell command in a specific directory | `C:\projects>git status` |
| `http...` | Opens a URL in the default browser | `https://example.com` |
| `{...}` or `[...]` | Pretty-prints a JSON string | `{"key":"value"}` |
| `<?php ... ?>` | Executes a PHP snippet | `<?php echo date('Y-m-d'); ?>` |
| `<?py ... ?>` | Executes a Python snippet | `<?py print(2**32) ?>` |
| `<?lua ... ?>` | Executes a Lua snippet | `<?lua print(os.time()) ?>` |

---

## Running scripts

If you open a script file directly (not a `.sql` file), DaSQL runs it through the appropriate interpreter based on the file extension or shebang line:

| Extension / shebang | Interpreter |
|--------------------|------------|
| `.php` | `php` |
| `.py` or `#! python` | `python` |
| `.pl` | `perl` |
| `.rb` | `ruby` |
| `.js` | `node` |
| `.lua` | `lua` |
| `.sh` | `bash` |
| `.md` / `.markdown` | Rendered to HTML and opened in browser |
| `.html` / `.htm` | Opened in browser |

---

## CLI mode

Files with a `.cli` extension let you run shell commands on the **remote WaSQL host**. Select a command line and press F8 — DaSQL sends it to the server and displays the output locally, including the exit code and a Success/Failure status line.

This is useful for running server-side maintenance commands without needing a separate SSH session.

---

## Editor setup

All installers are run from the DaSQL directory:

```
cd C:\wasql\dasql
python <installer_name>.py
```

---

### Notepad++

**Automatic installer:**

```
python notepad_pp_installer.py
```

The installer will:
- Locate your Notepad++ installation
- Download and install the NppExec plugin if not already present
- Write the DaSQL execution script to the NppExec config directory

**Manual steps required after the installer finishes:**

1. Open Notepad++
2. Go to **Plugins → NppExec → Advanced Options**
3. Under *Menu Items*:
   - Item name: `DaSQL`
   - Associated script: `DaSQL`
   - Click **Add/Modify**, then **OK**
4. Restart Notepad++
5. Go to **Settings → Shortcut Mapper → Plugin commands**
6. Find `DaSQL` in the list, double-click it, and set the shortcut to **F8**

**Usage:**

| Action | How |
|--------|-----|
| Run selected text | Select SQL and press **F8** |
| Run current line | Press **F8** with nothing selected |

---

### Sublime Text

**Automatic installer:**

```
python sublime_installer.py
```

The installer will:
- Copy `custom_exec.py` to the Sublime Text user packages directory
- Copy and configure `DaSQL.sublime-build` with the correct working directory
- Add an **F8** key binding

**Manual steps:**

1. Restart Sublime Text
2. Go to **Tools → Build System → DaSQL** to activate the build system

**Usage:**

| Action | How |
|--------|-----|
| Run selected text | Select SQL and press **F8** |
| Run current line | Press **F8** with nothing selected |
| Run a script file | Open a `.py`, `.php`, etc. file and press **F8** |

---

### Visual Studio Code

**Automatic installer:**

```
python vscode_installer.py
```

The installer will:
- Add three DaSQL tasks to your user-level `tasks.json`
- Add key bindings to `keybindings.json`

**Manual steps:**

1. Restart VS Code

**Usage:**

| Action | Shortcut |
|--------|----------|
| Execute selected text | **F8** |
| Execute current line | **Shift+F8** |
| Execute entire file | **Ctrl+F8** |

You can also run tasks manually via **Ctrl+Shift+P → Run Task → DaSQL: ...**.

---

### JetBrains IDEs

Supported IDEs: IntelliJ IDEA, PyCharm, DataGrip, WebStorm, PhpStorm, GoLand, Rider, CLion, and others.

**Automatic installer:**

```
python jetbrains_installer.py
```

The installer writes a `DaSQL.xml` External Tools configuration to every detected JetBrains IDE config directory.

**Manual steps required after the installer finishes:**

1. Restart the IDE
2. Go to **Settings → Tools → External Tools**
   Confirm the *DaSQL* group contains *Execute Selection* and *Execute File*
3. Go to **Settings → Keymap**
4. Search for `DaSQL` in the keymap search box
5. Double-click **Execute Selection** → **Add Keyboard Shortcut** → press **F8**
6. Double-click **Execute File** → **Add Keyboard Shortcut** → press **Ctrl+F8**

**Usage:**

| Action | Shortcut |
|--------|----------|
| Execute selected text | **F8** |
| Execute entire file | **Ctrl+F8** |

Results appear in the **Run** tool window at the bottom of the IDE.

> **DataGrip tip:** DaSQL complements DataGrip's built-in query runner — use DaSQL when you need to query a database that DataGrip's connection doesn't directly cover, or to run cross-database comparisons.

---

### Vim / Neovim

**Automatic installer:**

```
python vim_installer.py
```

The installer:
- Detects Vim and/or Neovim in your PATH
- For Neovim: appends a Lua config block to `init.lua` if it exists, falls back to `init.vim`, or creates `init.lua` if neither exists
- For Vim: appends a VimL config block to `_vimrc` (Windows) or `.vimrc` (Unix)
- Is safe to re-run — skips if the DaSQL config block is already present

**Manual steps:**

1. Restart Vim / Neovim

**Usage:**

| Mode | Action | Key |
|------|--------|-----|
| Normal | Execute current line | **F8** |
| Visual | Execute selection | **F8** |
| Normal | Execute entire file | **Ctrl+F8** |

Results appear in a `DaSQL-Output` split at the bottom of the screen. Press `Ctrl+W W` to jump between the output and your SQL file.

---

## Troubleshooting

**`DaSQL: dasql.ini not found`**
Copy `dasql.ini.sample` to `dasql.ini` and fill in your `base_url` and `authkey`.

**`DaSQL: ConnectionError trying to connect to ...`**
Check that `base_url` in your ini section is correct and the WaSQL server is reachable.

**`DaSQL: Timeout error`**
The WaSQL server did not respond in time. Check server health or network connectivity.

**`DaSQL: not sure what to do with this`**
The query didn't match any recognised SQL keyword or special prefix. Check for typos at the start of the query, or verify the line doesn't begin with a comment (`--` or `#`) — DaSQL strips leading comment markers automatically, but only one level deep.

**Results appear garbled (encoding issues)**
Set `output_format = dos` in your section — the `dos` format handles encoding most reliably for editor output panels.

**F8 does nothing in the editor**
- Confirm the build system / plugin is active (editor-specific; see setup steps above)
- Check that `dasql.ini` exists and the filename of your open file matches a section name
- Run `python3 dasql.py mysection . "select 1"` directly from the command line to isolate whether the issue is with DaSQL or the editor integration
