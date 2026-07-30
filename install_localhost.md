# install_localhost.py — one-shot local WaSQL stack installer

`install_localhost.py` installs and configures a complete local WaSQL
development stack — **Apache + PHP + MySQL** — on **Windows, Linux and macOS**,
then wires it all together for WaSQL and (optionally) builds a starter site.

When it finishes you have:

| | |
|---|---|
| Site | <http://localhost/> |
| Admin | <http://localhost/php/admin.php> — login **admin / admin** |
| Database | `wasql_sample` (user `wasql_dbuser` / `wasql_dbpass`) |
| Config | `config.xml` in the WaSQL directory |

It is the recommended way to install WaSQL and replaces the manual
"Installation" walkthroughs in `README.md`.

On **Windows** it installs into one tidy web server tree — programs in `bin\`,
everything that is data in `data\`:

| | |
|---|---|
| Apache | `C:\webserver\bin\Apache24` |
| PHP | `C:\webserver\bin\php-<version>` |
| MySQL programs | `C:\webserver\bin\mysql-<version>` |
| DocumentRoot | `C:\webserver\data\htdocs` |
| MySQL data directory | `C:\webserver\data\mysql` |

Move the whole tree elsewhere with `--webserver-root D:\webserver`, or override
any single path with `--bin-dir`, `--docroot` and `--mysql-data`. On Linux and
macOS the distro/Homebrew layout is used instead and the DocumentRoot defaults
to the WaSQL checkout.

---

## 1. Quick start

Get the code first (the script lives inside the checkout):

```bash
git clone https://github.com/WaSQL/php.git wasql
cd wasql
```

### Windows

Open **PowerShell or Terminal as Administrator** (required — it installs
services), then:

```powershell
python install_localhost.py
```

### Linux

```bash
python3 install_localhost.py
```

Run it as your normal user — it re-executes itself under `sudo` and will ask for
your password once.

### macOS

```bash
python3 install_localhost.py
```

Also run it as your normal user (**not** `sudo python3 …`): Homebrew refuses to
run as root, so the script re-execs under sudo and drops back to your account
for the `brew` calls.

### Fully hands-off

`--yes` accepts every default and asks nothing at all — good for a fresh VM,
a Dockerfile or a rebuild script:

```bash
python3 install_localhost.py --yes --sample wacss
```

---

## 2. How a run is structured

The script **asks all of its questions up front**, prints the plan, waits for
one confirmation, and then runs unattended to the end. There are no prompts in
the middle, so you can start it and walk away.

Questions asked (each with a default, Enter accepts):

| Question | Default |
|---|---|
| WaSQL directory | the directory holding `install_localhost.py` |
| Web server root *(Windows only)* | `C:\webserver` |
| Apache DocumentRoot | `C:\webserver\data\htdocs` (Windows) / the WaSQL directory |
| MySQL data directory *(Windows only)* | `C:\webserver\data\mysql` |
| Apache port | `80` |
| Install Apache/PHP/MySQL if missing? | `y` |
| WaSQL database name / user / password | `wasql_sample` / `wasql_dbuser` / `wasql_dbpass` |
| MySQL root password | blank (leave whatever is there) |
| Sample site to install | `wacss` |
| Relax MySQL `sql_mode`? | `y` |
| PHP timezone | `America/Denver` |

If `config.xml` already exists and already defines a `localhost` host, the
script **reads the database name/user/password out of it** and uses those as the
defaults, so it configures MySQL to match the config WaSQL will actually load.
In the same spirit, if a previous run already configured a DocumentRoot (there
is a `wasql.conf` next to the Apache config), that path becomes the default so
re-running to repair an install never silently moves the site.

Then, in order, it:

1. Locates any Apache / PHP / MySQL already installed.
2. Installs only what is missing (see §4).
3. Creates `php/temp`, `logs`, `<docroot>/images`, `<docroot>/w_min`, fixes
   permissions, and copies `sample.htaccess` → `<docroot>/.htaccess`.
4. Writes `config.xml` (only if needed — see §6).
5. Configures PHP (extensions, limits, timezone).
6. Writes the Apache config for WaSQL, validates it with `httpd -t`, and uses
   `httpd -S` to name the config file that actually owns `localhost`.
7. Starts MySQL; creates the database, the user and its grants.
8. Starts/restarts Apache.
9. **Verifies through Apache**: fetches a temporary check script that reports
   the PHP version, loaded extensions, the DocumentRoot Apache really used,
   directory writability and a real `mysqli` connection with your configured
   credentials. The check file is deleted afterwards.
10. Runs WaSQL's own setup wizard over HTTP (`/?starttype=wacss`) to create the
    sample site, tables and the `admin` user.
11. **Fetches `/`** and says what it actually renders — the WaSQL setup wizard, a
    WaSQL page, or (a problem) a web server default page. This is checked server
    side on purpose: a browser that saw an Apache welcome page before the
    install will happily keep showing its cached copy, so reload with
    <kbd>Ctrl</kbd>+<kbd>F5</kbd> before believing the browser over this line.
12. Prints a summary with URLs, paths, service restart commands and any
    warnings.

---

## 3. Command-line options

```
-y, --yes              accept every default, ask nothing
--wasql PATH           WaSQL checkout (default: this script's directory)
--webserver-root PATH  Windows: the stack's home; programs in bin\, data in
                       data\ (default C:\webserver)
--bin-dir PATH         Windows: install Apache/PHP/MySQL here
                       (default <webserver-root>\bin)
--mysql-data PATH      Windows: MySQL datadir
                       (default <webserver-root>\data\mysql)
--docroot PATH         Apache DocumentRoot (default:
                       <webserver-root>\data\htdocs on Windows, else the
                       WaSQL dir)
--port N               Apache port (default 80)
--db-name NAME         WaSQL database (default wasql_sample)
--db-user USER         WaSQL db user (default wasql_dbuser)
--db-pass PASS         WaSQL db password (default wasql_dbpass)
--mysql-root-pass PASS set/use this MySQL root password
--sample NAME          wacss | bulma | bootstrap | blank | none
--php-version X.Y      Windows only: PHP branch to fetch, e.g. 8.4
--mysql-version V      Windows only: MySQL series or exact version, e.g. 8.4
                       or 9.6.0 (default 8.4, the LTS line)
--use-choco            Windows only: install Apache/MySQL with Chocolatey
                       instead of plain zips (Chocolatey's paths win)
--timezone TZ          PHP date.timezone
--skip-install         do not install packages, only configure what is here
--repair               re-apply everything to the install that is already
                       here, restart the services, re-verify (implies --yes)
--force-config         overwrite an existing config.xml (a backup is kept)
--strict-sql-mode      leave MySQL sql_mode alone (default: relax it)
--check                only verify an existing install and exit
--dry-run              print what would happen; change nothing
-v, --verbose          show every command and its output
```

Useful combinations:

```bash
# see exactly what it would do, touch nothing (no admin/root needed)
python3 install_localhost.py --dry-run --yes -v

# health check an existing stack (no admin/root needed, changes nothing)
python3 install_localhost.py --check --yes

# something broke - re-apply every setting and restart the services
python3 install_localhost.py --repair

# I already run XAMPP/MAMP/my own stack — just configure it for WaSQL
python3 install_localhost.py --skip-install

# put the whole Windows stack on another drive
python install_localhost.py -y --webserver-root D:\webserver

# separate document root, non-standard port, no starter site
python3 install_localhost.py -y --docroot /var/www/html --port 8080 --sample none
```

`--check` and `--dry-run` are the only modes that do **not** require
administrator/root.

---

## 4. What gets installed, per platform

The script only installs what is missing. An existing Apache, PHP or MySQL
(XAMPP, MAMP, a distro package, your own build) is detected and reused.

### Windows

Everything is a plain zip download, so the install paths are the ones you asked
for rather than a package manager's:

| Component | Source | Installed to |
|---|---|---|
| Visual C++ x64 runtime | `aka.ms/vs/17/release/vc_redist.x64.exe` (skipped if already present) | system |
| Apache | **apachelounge.com** — newest x64 build (what `httpd.apache.org` points Windows users at) | `bin\Apache24` |
| PHP | **windows.php.net** — newest **x64 thread-safe** build (TS is required for `mod_php`) | `bin\php-<version>` |
| MySQL | **cdn.mysql.com** — newest release of the 8.4 LTS line, or `--mysql-version` | `bin\mysql-<version>` |

MySQL gets a generated `my.ini` (`basedir`, `datadir`, port, error log), its data
directory is created with `mysqld --initialize-insecure` (so `root` starts with
no password), and the `MySQL` service is registered against that `my.ini`. An
existing MySQL service or a datadir that already holds system tables is left
strictly alone.

The PHP, Apache and MySQL `bin` directories are appended to the machine `PATH`,
and the DLLs PHP needs (`libssl`, `libcrypto`, `libssh2`, `libsodium`, …) are
pulled in with Apache `LoadFile` directives so extensions work even before a
reboot refreshes `PATH`. Services `Apache2.4` and `MySQL` are registered and set
to start automatically.

`--use-choco` falls back to the older behaviour (Chocolatey `apache-httpd` +
`mysql`, installing Chocolatey itself if needed). Chocolatey hard-codes
`C:\tools\Apache24`, `C:\tools\mysql\current` and its own datadir, so the
`bin\`/`data\` layout above is **not** honoured in that mode. It is also used
automatically if a download source cannot be reached.

### Linux

Detected package manager → packages:

| Manager | Apache | PHP | Database |
|---|---|---|---|
| `apt` | `apache2` | `libapache2-mod-php` + `php-*` | `mysql-server`, else `mariadb-server` |
| `dnf`/`yum` | `httpd` | `php`, `php-fpm` + `php-*` | `mysql-server`, else `community-mysql-server`, else `mariadb-server` |
| `pacman` | `apache` | `php`, `php-apache` | `mariadb` (data dir initialised) |
| `zypper` | `apache2` | `apache2-mod_php8` + `php8-*` | `mariadb` |

* Debian/Ubuntu/Arch/SUSE use **mod_php**; Fedora/RHEL use **PHP-FPM** (the
  distro's own `php.conf` handler), and `php-fpm` is enabled and started.
* Package names that a given distro does not ship are skipped rather than
  aborting the run.
* Where `mod_php` is used, the MPM is switched to **prefork** (mod_php is not
  safe under event/worker).
* **SELinux** (Fedora/RHEL): `httpd_can_network_connect_db` is enabled and the
  WaSQL paths are labelled `httpd_sys_content_t`, with
  `httpd_sys_rw_content_t` on `php/temp`, `logs`, `php/schema`, `w_min` and
  `images`.
* Parent directories of the checkout get `o+x` so Apache can traverse into a
  repo that lives under `/home`.

### macOS

Homebrew `httpd`, `php` and `mysql` (Homebrew itself is installed if absent).
Apache is moved to port 80 and run via `sudo brew services`, using Homebrew
PHP's `libphp.so` as an Apache module with the prefork MPM.

---

## 5. Files the script creates or edits

Everything it generates is wrapped in sentinel markers so a re-run replaces its
own block and never duplicates or disturbs your edits:

```
### BEGIN WaSQL (generated by install_localhost.py) ###
…
### END WaSQL ###
```

Any file it modifies is copied to `<file>.wasql-bak` first (once, so the
original is always the backup).

**Apache** — a `wasql.conf`-style file in the layout the platform expects:

| Platform | File | How it is loaded |
|---|---|---|
| Debian/Ubuntu | `/etc/apache2/sites-available/000-wasql.conf` | `a2ensite`; `000-default` is disabled |
| Fedora/RHEL | `/etc/httpd/conf.d/wasql.conf` | auto-included |
| SUSE | `/etc/apache2/vhosts.d/wasql.conf` | auto-included |
| Arch | `/etc/httpd/conf/extra/wasql.conf` | `Include` added to `httpd.conf` |
| Windows/macOS | `wasql.conf` next to `httpd.conf` | `Include` added to `httpd.conf` |

The generated vhost sets `DocumentRoot`, the `/php/` and `/wfiles/` aliases,
`AllowOverride All`, `Require local`, and WaSQL's rewrite rules: the site root
goes to `/php/index.php?_view=index` first (it is a directory, so the
"real files and directories pass through" rule would skip it), then real
files/directories pass through and everything else becomes
`/php/index.php?_view=$1`. `DirectoryIndex` is **`index.php` only** and
`Options Indexes` is off, so a stray `index.html` in the document root cannot
shadow the WaSQL home page and a document root without `index.php` cannot turn
into a directory listing. `mod_rewrite`, `mod_alias`, `mod_dir` and friends are
uncommented in `httpd.conf` where needed, and `Listen` is pointed at your chosen
port.

Two placement details matter, and both exist because a leftover vhost is the
usual reason `localhost` keeps serving somebody else's welcome page:

* the `Include` goes in **right after the last top-level `LoadModule`** rather
  than at the end of the file. Apache hands a hostname to the *first* matching
  `<VirtualHost>`, so a stock `ServerName localhost` vhost included further down
  can no longer take the site over — and every module we reference is loaded by
  the time our directives are read.
* the main server's `DocumentRoot` is repointed at yours too, so a request that
  never reaches the vhost still lands in the right tree.

After writing the config the installer runs `httpd -S` and names the file that
actually owns `localhost:<port>`, warning you if it is not the WaSQL one.

**PHP** — a drop-in `99-wasql.ini` in PHP's scan directory (Linux/macOS), or
direct edits to `php.ini` on Windows (created from `php.ini-development` if
missing, with `extension_dir` set and the extensions WaSQL needs uncommented).
Settings applied: `memory_limit 512M`, `post_max_size 128M`,
`upload_max_filesize 120M`, `max_execution_time 600`, `max_input_vars 10000`,
`log_errors On`, `display_errors Off`, `date.timezone`.

Required extensions (verified, warned about if missing): `mysqli`, `curl`,
`mbstring`, `simplexml`, `zip`, `json`, `openssl`, `fileinfo`. Recommended and
merely reported: `gd`, `intl`, `exif`, `pdo_mysql`, `soap`, `ldap`, `sqlite3`,
`bcmath`, `sockets`, `zlib`, `iconv`, `gettext`.

**Windows document root** — when the document root is not the WaSQL checkout
(the default on Windows), `php` and `wfiles` are also linked into it as
directory junctions (`mklink /J`), the same way Linux gets symlinks. The Apache
aliases already cover the URLs; the junctions keep document-root-relative file
paths working.

**MySQL** — `--mysql-data` applies to a server this installer creates. An
already-installed MySQL is never relocated; the installer asks it where its
files are (`SELECT @@datadir`) and prints that instead. It then creates the
database (`utf8mb4`), creates the user for
`'user'@'localhost'` and `'user'@'%'`, and grants `ALL PRIVILEGES ON *.* …
WITH GRANT OPTION` (WaSQL's admin manages schemas, so it needs this). If MySQL's
password policy rejects the password, the policy is relaxed on this box and the
grant retried.

Unless `--strict-sql-mode` is passed it also sets
`sql_mode=NO_ENGINE_SUBSTITUTION` and `max_allowed_packet=256M` — via
`SET PERSIST` where supported, otherwise a `99-wasql.cnf`. WaSQL predates
`STRICT_TRANS_TABLES`/`ONLY_FULL_GROUP_BY` and some pages will error under them.

**WaSQL directories** — `php/temp`, `logs`, `<docroot>/images`,
`<docroot>/w_min` (created and made writable by the web server), plus
`<docroot>/.htaccess` copied from `sample.htaccess`. `php/schema` is made
writable because the setup wizard unzips a site template there.

---

## 6. config.xml handling

`config.xml` holds live credentials, so the script is deliberately careful:

* **No `config.xml`** → one is generated with a `<database>` for your settings
  and `<host name="localhost">`. On a non-default port it also adds
  `localhost:PORT` and `127.0.0.1:PORT` host entries, because WaSQL matches on
  `HTTP_HOST`, which includes the port.
* **`config.xml` exists and defines `localhost`** → left completely untouched;
  its db name/user/password become the defaults, so MySQL is set up to match.
* **`config.xml` exists but has no `localhost` host** → a `<database>` and
  `<host>` pair is appended before `</hosts>`; nothing else is changed.
* `--force-config` overwrites it (previous file kept as
  `config.xml.wasql-bak`).

---

## 7. The sample site

`--sample` picks one of the templates in `php/schema/templates/`:

`wacss` (default) · `bulma` · `bootstrap` · `blank` · `none`

WaSQL builds these itself: when `_users` does not exist, `php/index.php` shows
the setup wizard. The installer just drives it over HTTP
(`GET /?starttype=<sample>`), which creates the WaSQL system tables, the sample
pages/templates, and the default **admin / admin** login.

If the database already has a `_users` table, this step is skipped — an existing
site is never overwritten. `--sample none` skips it entirely and leaves you at
the wizard, which you can complete in a browser.

---

## 8. Re-running, repairing, verifying, rolling back

**Re-running is how you repair an install.** Nothing here is
install-once: every generated block is replaced in place, directories and grants
use `IF NOT EXISTS`, an initialised database and datadir are left alone, and a
`config.xml` that already maps `localhost` is never touched. Re-run it after
moving the checkout, changing ports, upgrading PHP, or when something simply
stopped working:

```bash
python install_localhost.py --repair
```

`--repair` implies `--yes`, so it re-applies every setting to what is already on
the box without asking anything: rewrites the Apache and PHP configuration,
re-registers and restarts the Apache/MySQL services, recreates any missing
directory, re-creates the database user and grants, then runs the full
verification. Packages are only downloaded if a component is missing outright —
add `--skip-install` to forbid even that.

What a repair run will *not* do: overwrite your `config.xml` (pass
`--force-config`), replace an existing site (an initialised database is
detected and the wizard skipped), or touch a MySQL datadir that already holds
system tables.

**Verify at any time** (no admin needed, nothing is changed):

```bash
python3 install_localhost.py --check --yes -v
```

It reports the PHP version and SAPI as Apache actually runs it, the DocumentRoot
Apache really used, which config file owns the vhost, missing extensions,
unwritable directories, whether PHP can really connect to the database, and what
`http://localhost/` returns.

**Rolling back:** restore the `*.wasql-bak` files, or delete the block between
the `### BEGIN WaSQL … ### END WaSQL` markers, and restart Apache. On Windows,
programs are plain directories under `bin\` — stop the services
(`net stop Apache2.4`, `net stop MySQL`), unregister them
(`httpd -k uninstall -n Apache2.4`, `mysqld --remove MySQL`) and delete the
directories. Packages installed by Chocolatey / apt / dnf / Homebrew are removed
with those tools — the script does not uninstall anything.

---

## 9. Troubleshooting

**"this installer must run elevated" (Windows)** — start PowerShell or Terminal
with *Run as administrator*; the script cannot elevate itself without losing the
console.

**Apache serves PHP source instead of running it** — the PHP module/handler is
not active. On Windows this means no `php*apache2_4.dll` was found: you have a
**non-thread-safe** PHP. Re-run without `--skip-install` (a TS build is fetched
automatically), or point `--php-version` at a branch that has one.

**404 on every URL / the check step reports 404** — the DocumentRoot Apache is
using is not the one you told the installer. Check `DocumentRoot` in
`httpd.conf`, then re-run with the matching `--docroot`.

**`http://localhost/` shows an Apache welcome page** — first believe the
installer, not the browser: the last step fetches `/` server side and prints
what it got. If that line says a WaSQL page is being served, your browser is
showing a cached copy of whatever used to answer on port 80 — reload with
<kbd>Ctrl</kbd>+<kbd>F5</kbd>. If it says a web server default page is being
served, another vhost is answering first; run with `--verbose` to see the
`httpd -S` dump, and comment out the vhost it names.

**`http://localhost/` shows a "Coming Soon!" page** — that *is* WaSQL: it is the
`wacss` sample site's own index page (`_pages` record `index`). Log into
<http://localhost/php/admin.php> (admin / admin) and edit it.

**403 Forbidden** — on Linux, Apache cannot traverse into the checkout
(permissions on a `/home` path) or SELinux is blocking it. Re-run the installer
as root so it can fix both, and check `/var/log/audit/audit.log` for SELinux
denials. Note the vhost uses `Require local`: only `127.0.0.1`/`::1` may
connect. To reach the site from another machine, change that to
`Require all granted` in the generated conf.

**Port 80 already in use** — on Windows this is usually IIS
(`net stop W3SVC`) or another Apache; on macOS the built-in Apache
(`sudo apachectl stop`). Or just use `--port 8080` — the installer adds the
matching `localhost:8080` host entry to `config.xml` for you.

**PHP cannot reach the database** — check `config.xml` credentials against what
was created, confirm MySQL is running, and remember the grants are created for
both `'user'@'localhost'` and `'user'@'%'`. `--check -v` prints the exact
`mysqli` error.

**Cannot log into MySQL as root** — on Debian/Ubuntu root uses socket auth, so
the script tries `sudo mysql`. If your root password is set, pass
`--mysql-root-pass`. If it still fails, the script prints the three SQL
statements to run by hand and continues.

**Apache config test fails** — the script stops before restarting Apache and
prints the last lines of `httpd -t`. Fix the reported line, or restore
`httpd.conf.wasql-bak`, then re-run.

**Errors after everything installs** — WaSQL's own log is
`logs/wasql_errors.log` in the checkout; Apache's error log is in the usual
place for your platform.

---

## 10. Requirements & notes

* **Python 3.8+**, standard library only — no `pip install` needed.
* Administrator (Windows) or root/sudo (Linux, macOS), except for `--check`
  and `--dry-run`.
* Internet access, unless everything is already installed and you use
  `--skip-install`.
* Intended for **local development only**. It configures a permissive,
  convenience-first stack (`Require local`, relaxed `sql_mode`, `admin/admin`,
  well-known database credentials). Do not point it at a server that is
  reachable from anywhere else.
