# DaSQL — DOS Access to SQL

DaSQL lets you run SQL queries (and scripts, and shell commands) against **any database your [WaSQL](https://wasql.com) server can reach** — right from inside your text editor or the command line. Select some SQL, press **F8**, and the results print into the editor's output panel. No separate database client, no VPN, no direct database credentials on your machine.

---

## Contents

- [What DaSQL is](#what-dasql-is)
- [How it works](#how-it-works)
- [Requirements](#requirements)
- [Quick start](#quick-start)
- [`dasql.ini` explained](#dasqlini-explained)
  - [The `[global]` section](#the-global-section)
  - [Database sections](#database-sections)
  - [Authentication methods](#authentication-methods)
  - [Output formats](#output-formats)
  - [Default queries](#default-queries)
  - [Shortcuts](#shortcuts)
  - [Generating a section from the WaSQL admin menu](#generating-a-section-from-the-wasql-admin-menu)
- [Running queries](#running-queries)
  - [From the command line](#from-the-command-line)
  - [From an editor](#from-an-editor)
- [Special commands](#special-commands)
- [Running scripts and files](#running-scripts-and-files)
- [`.cli` — run commands on the WaSQL host](#cli--run-commands-on-the-wasql-host)
- [Editor setup](#editor-setup)
  - [Sublime Text](#sublime-text)
  - [Notepad++](#notepad)
  - [Visual Studio Code](#visual-studio-code)
  - [JetBrains IDEs](#jetbrains-ides)
  - [Vim / Neovim](#vim--neovim)
  - [Zed](#zed)
  - [Emacs](#emacs)
  - [Other editors (Geany, gedit, …)](#other-editors-geany-gedit-)
- [Troubleshooting](#troubleshooting)

---

## What DaSQL is

DaSQL is a single, lightweight Python script (`dasql.py`) that acts as a bridge between your editor and WaSQL's SQL engine. When you press F8:

1. The editor hands DaSQL the **filename**, the **directory**, and the **selected text** (or current line).
2. DaSQL reads `dasql.ini`, finds the section that matches the filename, and pulls that database's connection details.
3. It POSTs your query to `{base_url}/php/admin.php` using WaSQL's authenticated SQL prompt.
4. WaSQL runs the query against the target database and returns the results, which DaSQL prints back into your editor's panel.

Because the **filename drives the connection**, you switch databases simply by editing a different `.sql` file. A file named `sales.sql` runs against the `[sales]` section; `reporting.sql` runs against `[reporting]`.

**Why it's useful:**

- Query **any** database WaSQL is connected to — MySQL, PostgreSQL, SQL Server, Oracle, SAP HANA, cTree, SQLite, and more — all from the same editor, with the same keystroke.
- No database drivers or credentials on the client. Auth goes through WaSQL.
- Works identically across Sublime, Notepad++, VS Code, JetBrains IDEs, Vim/Neovim, Zed, and Emacs — plus any editor that can pipe a selection to a command (Geany, gedit, …).
- Doubles as a runner for PHP, Python, Lua, shell commands, JSON pretty-printing, and quick math.

---

## How it works

```
┌────────────┐   filename + selection    ┌───────────┐   HTTP POST    ┌──────────────┐
│  Your      │ ────────────────────────► │  dasql.py │ ─────────────► │  WaSQL server │
│  editor    │        (F8)               │  + .ini   │  /php/admin.php│  (SQL engine) │
│            │ ◄──────────────────────── │           │ ◄───────────── │              │
└────────────┘        results            └───────────┘    results     └──────────────┘
                                                                              │
                                                                    ┌─────────┴─────────┐
                                                                    │ MySQL / Postgres  │
                                                                    │ SQL Server / etc. │
                                                                    └───────────────────┘
```

---

## Requirements

- **Python 3.8+**
- A **WaSQL server** with at least one database configured
- A **WaSQL auth key** (found under the profile menu in the WaSQL admin portal)

Install the Python dependencies:

```
pip install requests chardet markdown
```

---

## Quick start

1. Put the DaSQL folder somewhere permanent, e.g. `C:\wasql\dasql` (Windows) or `~/wasql/dasql` (macOS/Linux).
2. Copy `dasql.ini.sample` to `dasql.ini`.
3. Edit `dasql.ini` — set `base_url` and `authkey` in `[global]`, and add a section for each database.
4. Run the installer for your editor (see [Editor setup](#editor-setup)).
5. Open a `.sql` file named after one of your sections, select a query, and press **F8**.

---

## `dasql.ini` explained

`dasql.ini` is a plain INI file that lives **next to `dasql.py`** (DaSQL always looks for it in its own directory). `[global]` holds defaults that apply to every query; each named section defines one database connection and can override any global value.

> `dasql.ini` is git-ignored — it holds your auth keys, so it never gets committed. Start from `dasql.ini.sample`.

### The `[global]` section

`global` is the only reserved section name. Its keys are loaded first for every query, then overridden by whichever database section matches the file.

```ini
[global]
base_url      = https://your-wasql-server.example.com
authkey       = YOUR_WASQL_AUTH_KEY
output_format = dos
db            =
```

Any key valid in a section is also valid in `[global]` as a default.

### Database sections

One section per database. The **section name must match the `.sql` filename** you'll open (without the extension).

```ini
[sales]
db            = sales_dev
base_url      = https://dev.example.com
authkey       = YOUR_WASQL_AUTH_KEY
output_format = dos
```

Open `sales.sql`, press F8, and the query runs against the `sales_dev` database on `dev.example.com`.

| Key | Meaning |
|-----|---------|
| `db` | The database name/connection as configured **inside WaSQL** (often differs from the section name). |
| `base_url` | The WaSQL server to send the query to. |
| `authkey` (or other auth keys) | How to authenticate — see below. |
| `output_format` | How results are formatted — see below. |
| `query` | An optional default query for this section. |

### Authentication methods

DaSQL supports all six WaSQL authentication methods. Put whichever applies in the section (or in `[global]`):

| Method | Keys required |
|--------|--------------|
| Auth key | `authkey = ...` |
| Temporary auth key | `tauthkey = ...` |
| API key | `apikey = ...` and `username = ...` |
| Username + password | `username = ...` and `password = ...` |
| Email + password | `email = ...` and `password = ...` |
| Phone + password | `phone = ...` and `password = ...` |

Auth key is the usual choice — it's what the WaSQL admin menu generates for you (see below).

### Output formats

Set `output_format` per section or globally:

| Format | Description |
|--------|-------------|
| `dos` | Fixed-width tabular columns — **best for editor panels** and the most reliable with odd encodings. |
| `csv` | Comma-separated values. |
| `json` | JSON array of objects. |
| `xml` | XML. |
| `html` | HTML table. |
| `table` | Markdown-style table. |

### Default queries

A section can carry a query that runs when you press F8 on an otherwise-empty/non-SQL line:

```ini
[slow_queries]
db    = production
query = SELECT pid, query, state FROM pg_stat_activity WHERE state <> 'idle' ORDER BY query_start
```

### Shortcuts

Shortcuts are named, reusable queries. A shortcut is its own INI section named `section:shortcut` (or `global:shortcut`). Type the shortcut's name on a line, press F8, and DaSQL substitutes the stored query. A section-specific shortcut overrides a global one with the same name.

```ini
[global:tables]
query = SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'

[mydb:tables]
query = SELECT relname, n_live_tup FROM pg_stat_user_tables ORDER BY n_live_tup DESC
```

Type `tables` and press F8: while editing `mydb.sql` you get the Postgres stats version; every other file gets the global version.

### Generating a section from the WaSQL admin menu

You don't have to hand-write sections. **WaSQL can generate the `dasql.ini` entry for any database for you:**

1. Log into the WaSQL admin portal.
2. Open the **user menu** (top-right profile/user menu).
3. Choose the **DaSQL** menu option.

WaSQL produces a ready-to-paste `[section]` block for that database — already filled in with the correct `db`, `base_url`, `authkey`, and `output_format`. Copy it into your `dasql.ini`, save a matching `.sql` file (e.g. `sectionname.sql`), and you're connected.

This is the recommended way to add databases: the auth key and `db` value come straight from the server, so there's nothing to get wrong.

---

## Running queries

### From the command line

```
python3 dasql.py <section> <dirname> <query>
```

Common patterns:

```bash
# Run a query against a named section
python3 dasql.py mydb . "select count(*) from orders"

# Run a .sql file (its name must match an ini section)
python3 dasql.py mydb.sql . mydb.sql

# Run a saved shortcut
python3 dasql.py mydb . tables

# Pipe the query in on stdin (handy for scripts and Unix-style editors)
echo "select count(*) from orders" | python3 dasql.py mydb .
```

DaSQL strips a single leading comment marker (`-- ` or `#`) from the query, so lines commented in your `.sql` file still run.

### From an editor

Open a `.sql` file whose name matches a section (e.g. `mydb.sql` → `[mydb]`). **Select** the SQL and press **F8**. With nothing selected, the **current line** runs.

---

## Special commands

These prefixes work on any line, in any file, regardless of the matched section:

| Prefix | What it does | Example |
|--------|-------------|---------|
| `math>` or `calc>` | Evaluate a Python expression | `math> 1024 * 1024 * 512` |
| `cmd>` | Run a local shell command | `cmd> dir C:\data` |
| `C:\path>command` | Run a local command in a directory | `C:\projects>git status` |
| `http...` | Open a URL in your browser | `https://example.com` |
| `{...}` / `[...]` | Pretty-print a JSON string | `{"key":"value"}` |
| `<?php ... ?>` | Execute a PHP snippet | `<?php echo date('Y-m-d'); ?>` |
| `<?py ... ?>` | Execute a Python snippet | `<?py print(2**32) ?>` |
| `<?lua ... ?>` | Execute a Lua snippet | `<?lua print(os.time()) ?>` |

---

## Running scripts and files

If you press F8 on a **script file** (not a `.sql`), DaSQL runs it through the right interpreter, chosen by extension or shebang:

| Extension / shebang | Interpreter |
|--------------------|------------|
| `.php` | `php` |
| `.py` or `#! python` | `python` |
| `.pl` | `perl` |
| `.rb` | `ruby` |
| `.js` | `node` |
| `.lua` | `lua` |
| `.sh` | `bash` |
| `.md` / `.markdown` | Rendered to HTML and opened in the browser |
| `.html` / `.htm` | Opened in the browser |

---

## `.cli` — run commands on the WaSQL host

Files ending in `.cli` send the selected line to the **remote WaSQL server** as a shell command. Select a command, press F8, and DaSQL prints the server-side output, the return code, and a `STATUS: Success`/`Failure` line — handy for server maintenance without opening a separate SSH session.

The `.cli` file's name selects the target the same way `.sql` files do: `sales.cli` runs its commands on the server defined by the `[sales]` section (`dev.example.com`), not on your local machine.

---

## Editor setup

All installers run from the DaSQL directory:

```
cd C:\wasql\dasql
python <installer_name>.py
```

Every integration passes the **filename with its extension** to `dasql.py`, so `.sql` sections, `.cli` files, and script files are all detected correctly.

### Sublime Text

**Automatic installer:**

```
python sublime_installer.py
```

It copies `custom_exec.py` into Sublime's User packages directory, installs and configures `DaSQL.sublime-build` with the correct working directory, and adds an **F8** key binding.

**Manual setup (if you'd rather do it by hand):**

1. **Tools → Build System → New Build System**, replace the contents with `DaSQL.sublime-build.sample`, and save it as `DaSQL.sublime-build`. It should look like:
   ```json
   {
       "target": "execute_selection_exec",
       "cancel": {"kill": true},
       "shell_cmd": "python3 dasql.py \"\\$fname\" \"\\$dirname\" \"\\$selection\"",
       "working_dir": "C:\\wasql\\dasql",
       "word_wrap": false,
       "quiet": true,
       "save_untitled_files": true
   }
   ```
2. Copy `custom_exec.py.sample` to Sublime's User packages folder (`C:\Users\{YOU}\AppData\Roaming\Sublime Text 3\Packages\User`) and rename it `custom_exec.py`.
3. Set the build system: **Tools → Build System → DaSQL**.
4. Copy `dasql.ini.sample` to `dasql.ini` and add a section per database.

**Usage:** select SQL and press **F8**; with nothing selected, the current line runs. Opening a `.py`/`.php`/etc. file and pressing F8 runs it as a script.

### Notepad++

DaSQL runs in Notepad++ through the **NppExec** plugin.

**Automatic installer:**

```
python notepad_pp_installer.py
```

It locates Notepad++, installs the NppExec plugin if missing, and writes the DaSQL execution script (`nppexec_dasql_config.txt`) into the NppExec config directory. The generated script hard-codes your detected Python path and the DaSQL directory, e.g.:

```
set local Python_Exe = C:\Python311\python.exe
set local DaSQL_Dir  = C:\wasql\dasql
```

**Manual steps after the installer:**

1. Open Notepad++.
2. **Plugins → NppExec → Advanced Options**, under *Menu Items*: Item name `DaSQL`, Associated script `DaSQL`, click **Add/Modify**, then **OK**.
3. Restart Notepad++.
4. **Settings → Shortcut Mapper → Plugin commands**, find `DaSQL`, double-click, and assign **F8**.

**Fully manual install (no installer):** install the NppExec plugin (**Plugins → Plugins Admin → NppExec → Install**), turn on **Plugins → NppExec → No Internal Messages**, press **F6**, paste the contents of `notepad_pp_plugin.txt` (adjust `Python_Dir` and `DaSQL_Dir` at the top), and save the script as `DaSQL`.

**Usage:** select SQL and press **F8** (or your mapped key); with nothing selected, the current line runs.

### Visual Studio Code

**Automatic installer:**

```
python vscode_installer.py
```

It adds three DaSQL tasks to your user-level `tasks.json` and the key bindings to `keybindings.json`. Restart VS Code.

**Usage:**

| Action | Shortcut |
|--------|----------|
| Execute selected text | **F8** |
| Execute current line | **Shift+F8** |
| Execute entire file | **Ctrl+F8** |

You can also run them from **Ctrl+Shift+P → Run Task → DaSQL: …**.

### JetBrains IDEs

Works with IntelliJ IDEA, PyCharm, DataGrip, WebStorm, PhpStorm, GoLand, Rider, CLion, and others.

**Automatic installer:**

```
python jetbrains_installer.py
```

It writes a `DaSQL.xml` External Tools configuration into every detected JetBrains config directory.

**Manual steps after the installer:**

1. Restart the IDE.
2. **Settings → Tools → External Tools** — confirm the *DaSQL* group has *Execute Selection* and *Execute File*.
3. **Settings → Keymap**, search `DaSQL`: assign **F8** to *Execute Selection* and **Ctrl+F8** to *Execute File*.

Results appear in the **Run** tool window.

### Vim / Neovim

**Automatic installer:**

```
python vim_installer.py
```

It detects Vim and/or Neovim and appends a DaSQL config block to `init.lua`/`init.vim` (Neovim) or `_vimrc`/`.vimrc` (Vim). It's safe to re-run — it skips if the block is already present. Restart Vim/Neovim.

**Usage:**

| Mode | Action | Key |
|------|--------|-----|
| Normal | Execute current line | **F8** |
| Visual | Execute selection | **F8** |
| Normal | Execute entire file | **Ctrl+F8** |

Output opens in a `DaSQL-Output` split; `Ctrl+W W` jumps between it and your SQL file.

### Zed

**Automatic installer:**

```
python zed_installer.py
```

It adds two DaSQL tasks to Zed's global `tasks.json` and binds them in `keymap.json`, then points them at the current DaSQL directory. Restart Zed.

The tasks pass Zed's `$ZED_FILE` (the full path, *with* extension), `$ZED_DIRNAME`, and `$ZED_SELECTED_TEXT`, so `.sql` sections, `.cli` files, and script files all resolve correctly.

**Usage:**

| Action | Shortcut |
|--------|----------|
| Execute selection | **F8** |
| Execute entire file | **Ctrl+F8** |

Results appear in Zed's terminal panel. You can also run them from the command palette: **task: spawn → DaSQL: …**.

> **Note:** Zed has no "current line" task variable, so select the line (or any text) before pressing **F8**. Zed's Windows build is still preview — on macOS/Linux the config dir is `~/.config/zed`; on Windows the installer targets `%APPDATA%\Zed`.

### Emacs

**Automatic installer:**

```
python emacs_installer.py
```

It appends a DaSQL elisp block to your init file (`~/.emacs.d/init.el`, `~/.emacs`, or `~/.config/emacs/init.el`), with your Python path and `dasql.py` path baked in. It's safe to re-run — it skips if the block is already present. Restart Emacs, or run `M-x eval-buffer` on your init file.

**Usage:**

| Action | Key |
|--------|-----|
| Execute region, or current line if nothing is selected | **F8** |
| Execute entire buffer | **Ctrl+F8** |

Results appear in a `*DaSQL Output*` buffer.

### Other editors (Geany, gedit, …)

`dasql.py` reads the query from **stdin** when it isn't passed as an argument. That means any editor that can pipe the current selection to an external command works — you don't need a dedicated installer. Wire up a custom/external tool that:

1. runs `python3 dasql.py "<filename-with-extension>" "<dir>"` from the DaSQL directory, and
2. pipes the selection to the command's standard input.

The filename (with its extension) still selects the `dasql.ini` section, exactly as with the F8 integrations.

- **Geany** — *Edit → Format → Send Selection to → Set Custom Commands*, or a Build command that pipes the selection.
- **gedit** — enable the built-in **External Tools** plugin, set *Input: Current selection* and *Output: Bottom pane*, and call `dasql.py` with `$GEDIT_CURRENT_DOCUMENT_NAME` and its directory.

Because the filename is passed through, `.cli` files and scripts are detected here too.

---

## Troubleshooting

**`DaSQL: dasql.ini not found`**
Copy `dasql.ini.sample` to `dasql.ini` (in the same folder as `dasql.py`) and set `base_url` and `authkey`.

**`DaSQL: ConnectionError trying to connect to ...`**
Check `base_url` in the matched section and confirm the WaSQL server is reachable.

**`DaSQL: Timeout error`**
The server didn't respond in time — check server health and network.

**`DaSQL: not sure what to do with this`**
The line didn't start with a recognized SQL keyword or special prefix. Check for a typo at the start, or a comment marker deeper than one level (DaSQL strips only one leading `--`/`#`).

**A `.cli` command ran on my computer instead of the server**
The `.cli` file's name must match a section in `dasql.ini`, and your editor integration must pass the filename **with** its `.cli` extension. Re-run your editor's installer if you set it up before this was fixed.

**Results look garbled (encoding issues)**
Set `output_format = dos` — it's the most robust for editor output panels.

**F8 does nothing in the editor**
- Confirm the build system / plugin / task is active (see your editor's setup above).
- Confirm `dasql.ini` exists and your open file's name matches a section.
- Isolate the problem by running it directly: `python3 dasql.py mysection . "select 1"`. If that works, the issue is the editor integration, not DaSQL.
