# Installing SCM

**SCM** (Schema Change Manager) is a single-file Python database migration tool.
This guide shows how to install **just SCM** — without cloning the whole WaSQL
repo — and how to set up your computer (Linux, macOS, Windows) so you can run
`scm` from **any** project directory.

For how to *use* scm once it's installed, see [`scm.md`](scm.md) or run `scm learn`.

---

## What you actually need

SCM is deliberately tiny. The only required file is:

| File | Required? | Purpose |
|---|---|---|
| `scm.py` | **Yes** | The entire tool — all logic lives here |
| `scm.bat` | Windows only | Lets you type `scm` instead of `python scm.py` |
| `scm.sh` | Optional (Unix) | Bash wrapper; on Linux/macOS you can skip it and call `scm.py` directly |
| `scm.md`, `scm-help.md` | Optional | Documentation |

### Requirements

- **Python 3.8 or newer** — check with `python3 --version` (or `python --version`).
- **A database driver** for whatever database you connect to (see
  [Database drivers](#database-drivers) below). SQLite needs nothing extra.

SCM has **no other dependencies** — it uses only the Python standard library plus
your database's driver package.

---

## Step 1 — Get the files (SCM only, not the full repo)

Pick whichever method you prefer. All of them grab only the scm files.

### Option A — Download the files directly (simplest, no git)

The files live in the [`WaSQL/php`](https://github.com/WaSQL/php) repo under
`python/`. Download them straight from GitHub:

**Linux / macOS**

```bash
# Choose a permanent home for scm and download into it
mkdir -p ~/.local/scm && cd ~/.local/scm

curl -O https://raw.githubusercontent.com/WaSQL/php/master/python/scm.py
curl -O https://raw.githubusercontent.com/WaSQL/php/master/python/scm.sh   # optional wrapper
chmod +x scm.py scm.sh
```

**Windows (PowerShell)**

```powershell
# Choose a permanent home for scm and download into it
New-Item -ItemType Directory -Force "$HOME\scm" | Out-Null
Set-Location "$HOME\scm"

$base = 'https://raw.githubusercontent.com/WaSQL/php/master/python'
Invoke-WebRequest "$base/scm.py"  -OutFile scm.py
Invoke-WebRequest "$base/scm.bat" -OutFile scm.bat
```

> Keep `scm.py` and its wrapper (`scm.bat` / `scm.sh`) **together in the same
> folder** — the wrapper looks for `scm.py` right beside itself.

### Option B — `git sparse-checkout` (if you want `git pull` updates)

This clones only the `python/` folder instead of the entire repo:

```bash
git clone --filter=blob:none --sparse https://github.com/WaSQL/php.git wasql-scm
cd wasql-scm
git sparse-checkout set python
# scm now lives in ./python — update anytime with: git pull
```

### Option C — Copy from an existing WaSQL checkout

If you already have the WaSQL repo, the files are in its `python/` directory —
just copy `scm.py` (and `scm.bat` on Windows) wherever you like.

---

## Step 2 — Install a database driver

Install the pip package for the database(s) you'll connect to:

| Database         | Install                                      |
|------------------|----------------------------------------------|
| PostgreSQL       | `pip install "psycopg[binary]"`              |
| MySQL / MariaDB  | `pip install mysql-connector-python`         |
| SQL Server       | `pip install pyodbc`  (or `pip install pymssql`) |
| SQLite           | *(built into Python — nothing to install)*   |
| Oracle           | `pip install oracledb`                        |
| SAP HANA         | `pip install hdbcli`                          |
| Snowflake        | `pip install snowflake-connector-python`      |
| FairCom cTree    | `pip install pyodbc` + FairCom ODBC driver    |
| Firebird         | `pip install fdb`                             |

You only need the driver(s) for the databases you actually use.

---

## Step 3 — Put `scm` on your PATH

This is what lets you type `scm` from inside **any** project. SCM is
context-aware: it always reads the `.env`, `migrations/`, and `config.xml` from
your **current working directory**, no matter where `scm.py` itself lives — so a
single install on your PATH serves every repo on your machine.

Assume you downloaded the files to:
- Linux/macOS: `~/.local/scm`
- Windows: `%USERPROFILE%\scm`  (`C:\Users\<you>\scm`)

### Linux

The `scm.py` file has a `#!/usr/bin/env python3` shebang, so the cleanest setup
is to symlink it (as `scm`) into a directory already on your PATH:

```bash
mkdir -p ~/.local/bin
ln -sf ~/.local/scm/scm.py ~/.local/bin/scm
chmod +x ~/.local/scm/scm.py

# Make sure ~/.local/bin is on your PATH (add to ~/.bashrc if not):
echo 'export PATH="$HOME/.local/bin:$PATH"' >> ~/.bashrc
source ~/.bashrc
```

### macOS

Same as Linux. `/usr/local/bin` is already on the PATH on most Macs, so:

```bash
chmod +x ~/.local/scm/scm.py
ln -sf ~/.local/scm/scm.py /usr/local/bin/scm     # may need: sudo ln -sf ...

# On Apple Silicon, Homebrew uses /opt/homebrew/bin instead — either works if it's on PATH.
```

If your shell is **zsh** (the macOS default), add PATH changes to `~/.zshrc`
instead of `~/.bashrc`.

> **Note on the `scm.sh` wrapper:** if you prefer the wrapper over the shebang,
> **rename** it to `scm` in the same folder as `scm.py` and put *that folder* on
> your PATH. Do **not** `ln -s scm.sh /usr/local/bin/scm` — a symlink to the
> wrapper makes it look for `scm.py` in the wrong place. Symlinking `scm.py`
> itself (shown above) is the reliable option.

### Windows

`scm.bat` is picked up by both CMD and PowerShell (`.bat` is on `PATHEXT`), so
you just add the folder containing `scm.bat` **and** `scm.py` to your PATH.

**PowerShell** — persists for your user across sessions:

```powershell
[Environment]::SetEnvironmentVariable(
    'Path',
    [Environment]::GetEnvironmentVariable('Path', 'User') + ";$HOME\scm",
    'User')
```

Then **restart your terminal** and you can run `scm` from anywhere.

Prefer the GUI? Run `sysdm.cpl` → **Advanced** → **Environment Variables** → edit
**Path** under *User variables* → **New** → add `C:\Users\<you>\scm`.

> The wrapper prefers `python3` and falls back to `python`. Make sure Python is
> on your PATH — if `python --version` works in a new terminal, you're set.

---

## Step 4 — Verify

Open a **new** terminal (so the PATH change takes effect) and run:

```bash
scm version      # prints the installed version, e.g. "scm.py 1.29.0"
scm learn        # prints the full quick-start reference
```

If `scm version` prints a version number from any directory, you're done. 🎉

---

## First use in a project

From inside the repo you want to manage:

```bash
# WaSQL project — pull the connection straight from config.xml:
scm env-from-config <dbname>

# Any other project — create ./migrations and a .env stub, then edit .env:
scm init

# Then the everyday loop:
scm new create_users_table
scm status
scm up
```

See [`scm.md`](scm.md) for the complete command reference, `.env` options, file
styles, and safe-migration guidance.

---

## Updating SCM

How you update depends on how you installed. In every case, `scm.py` is the only
file that changes between releases (the wrappers rarely change), and your PATH
setup keeps working — you're just refreshing the file(s) in place. Run
`scm version` before and after to confirm the bump.

### If you used Option A (direct download)

Re-run the same download into the same folder to overwrite the old copy. There's
no separate "update" command — the download *is* the update.

**Linux / macOS**

```bash
cd ~/.local/scm
curl -O https://raw.githubusercontent.com/WaSQL/php/master/python/scm.py
chmod +x scm.py
```

**Windows (PowerShell)**

```powershell
Set-Location "$HOME\scm"
Invoke-WebRequest 'https://raw.githubusercontent.com/WaSQL/php/master/python/scm.py' -OutFile scm.py
```

Your PATH entry / symlink already points at this file, so the update takes effect
immediately — no need to touch PATH again.

### If you used Option B (`git sparse-checkout`) — recommended for easy updates

Just pull:

```bash
cd wasql-scm      # the folder you cloned into
git pull
```

This is the least-effort way to stay current: one command fetches the latest
`scm.py`. Because you tracked only the `python/` folder, the pull stays small.

### If you used Option C (copy from a WaSQL checkout)

Update your WaSQL checkout (`git pull` in that repo), then re-copy `scm.py` (and
`scm.bat`) to wherever you installed them.

> **Tip:** whichever method you use, `scm.py` is self-contained, so updating is
> always just "replace the file." Nothing is cached elsewhere and your `.env`
> files and `migrations/` folders are never touched by an update.

---

## Troubleshooting the install

**`scm: command not found` (Linux/macOS)**
The PATH change hasn't taken effect, or the symlink target is wrong. Open a new
terminal, confirm `~/.local/bin` (or wherever you linked) is in `echo $PATH`, and
that `ls -l $(command -v scm)` points at your `scm.py`.

**`'scm' is not recognized...` (Windows)**
Restart the terminal after editing PATH. Confirm the folder you added actually
contains both `scm.bat` and `scm.py` (`dir %USERPROFILE%\scm`).

**`can't open file '.../scm.py'` when running `scm`**
The wrapper can't find `scm.py` beside itself. This happens if you symlinked
`scm.sh`/`scm.bat` onto your PATH while leaving `scm.py` elsewhere. Keep the
wrapper and `scm.py` in the same folder, or (Linux/macOS) symlink `scm.py`
directly as shown above.

**`No <database> driver found` / import errors**
Install the driver package for your database (see
[Step 2](#step-2--install-a-database-driver)). Make sure you install it into the
same Python that runs scm (`python3 -m pip install ...`).

**Python not found**
Install Python 3.8+ from [python.org](https://www.python.org/downloads/) (on
Windows, tick *"Add Python to PATH"* in the installer). Verify with
`python3 --version` or `python --version`.
