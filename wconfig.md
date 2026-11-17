# wconfig.py — command-line manager for `config.xml`

View, search, health-check, add, edit and remove `<database>`, `<host>`, `<allhost>` and `<gitrepo>` tags in WaSQL's `config.xml` without hand-editing XML. Python 3, standard library only.

```
python wconfig.py [options] <command> [args]
python wconfig.py --help        # full command reference
python wconfig.py --version
```

The file managed is `config.xml` — `--config PATH`, else `./config.xml`, else next to the script, else `php/config.xml`. Hand-editing `config.xml` (or `sed`/`cat` on it from a script) is what this tool replaces; `domain_setup.sh` uses it for all of its config edits.

## How it edits (why it is safe to run against the real file)
- **Text-level edits.** Only the tag you change is rewritten; comments, ordering and indentation elsewhere are untouched. Commented-out tags (`<!-- … -->`) are ignored. Add → new tag inserted before `</hosts>`; set → value replaced in place (new attribute appended matching the tag's indentation); unset/rm → the attribute/tag and its whitespace are removed.
- **Validated before saving.** If the result would not be well-formed XML nothing is written.
- **Backup on every write:** `config.xml.bak-YYYYmmddHHMMSS` beside the file. `--no-backup` skips it (scripts that make several edits take one backup themselves).
- **Secrets are masked in output** (attribute names containing pass/pwd/secret/key/token/salt/auth → `KQe****`). `--reveal` shows them. Do not paste revealed output into tickets/chat.

## Commands

| Command | What it does |
|---|---|
| `list databases [--type T]` | `<database>` tags; columns `name,type,host` unless `--fields` |
| `list hosts` | `<host>` tags; columns `name,database,dbname` unless `--fields` |
| `list mysql` | databases whose `dbtype` is `mysql`/`mysqli` (same columns as `list databases`) |
| `list types` / `list gitrepos` | dbtype counts / `<gitrepo>` tags |
| `show host NAME [--resolved]` | every attribute; `--resolved` layers **allhost < sameas(sameas) < sameas < database tag < host**, each line tagged with its source (mirrors `php/config.php`) |
| `show database NAME` | every attribute + which hosts use it |
| `show allhost` / `show gitrepo NAME` | same, for those tags |
| `uses DATABASE` | hosts whose `database=` is that database |
| `search TERM` | case-insensitive match on tag names, attribute names and values |
| `checkhosts [--name HOST]` | does each host name resolve in DNS? |
| `checkdbs [--name DB]` | DNS on `dbhost` + a TCP connect to its port (`dbport`, else the default for the type: mysql 3306, postgres 5432, mssql 1433, oracle 1521, hana 30015, firebird 3050, ctree 6597). File databases (sqlite/duckdb/msaccess/msexcel/mscsv) check that the `dbname` file exists. **Does not log in** — credentials are not verified. Named MSSQL instances (`host\INSTANCE`) with no `dbport` report `dns-only`. |
| `set host\|database\|allhost\|gitrepo NAME ATTR VALUE` | add or change an attribute |
| `unset host\|database\|allhost\|gitrepo NAME ATTR` | remove an attribute |
| `add host NAME [ATTR=VALUE …]` | new `<host>` (self-closing; multi-line if >1 attribute) |
| `add database NAME [ATTR=VALUE …]` | new `<database>` |
| `rm host\|database\|gitrepo NAME` | remove the whole tag (`<allhost>` is refused) |
| `validate` | is `config.xml` well-formed XML? |

For `allhost` there is no name, so the command is `set allhost ATTR VALUE` / `unset allhost ATTR` (the tool inserts the empty name internally).

### Global options
`--format table|csv` (default table; CSV has no footer line, so it pipes cleanly) · `--fields a,b,c` (list: any attribute names; for databases `type`=`dbtype` and `host`=`dbhost`; host fields fall back to the host's database tag) · `--name X` (checks: just one) · `--timeout SECS` (checks, default 3) · `--reveal` · `--resolved` · `--type T` · `--no-backup`.

### Exit codes
`checkhosts`/`checkdbs` exit **1 if anything fails**, 0 if all pass — usable from cron/CI. Errors (unknown name, bad XML) print `ERROR: …` to stderr and exit 1.

## Examples
```
python wconfig.py list databases --format csv
python wconfig.py list hosts --fields name,database,dbname,dbhost
python wconfig.py list databases --type postgres --fields name,host,dbname
python wconfig.py show host localhost2 --resolved
python wconfig.py search spdq
python wconfig.py checkdbs --name spdq_dev --timeout 2
python wconfig.py set database spdq_dev dbport 5433
python wconfig.py add host foo.com database=foo_db access=all stage=0
python wconfig.py add database foo_db dbtype=mysqli dbhost=localhost dbname=foo_db dbuser=foo dbpass=secret
python wconfig.py unset host foo.com access
python wconfig.py rm host foo.com
```

## Gotchas
- **Name matching is case-insensitive**, and a name must be unique: `add` refuses a duplicate. If a host is defined twice by hand, `set`/`unset`/`rm`/`show` act on the **first** one.
- **Only self-closing tags are edited** (`<host … />`). A tag written as `<host …>…</host>` is read, but attribute edits target the opening tag only.
- **`checkdbs` "connect OK" ≠ login OK**, and a failed check on a VPN-only database usually just means the VPN is off.
- **Quote values with special characters** (`'apikey=abc==' `); `&`, `"` and `<` are XML-escaped on write.
- `config.d/` (per-domain config files) is **not** supported — an earlier experiment was removed. The core's `php/config.php` reads only `config.xml`.
- `config.xml` is git-ignored in this repo; its `.bak-*` copies are not.
