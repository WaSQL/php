#! python
"""
ctreesql.py - Interactive SQL prompt for cTree (FairCOM) databases
Usage: python ctreesql.py <db_name>
  <db_name> must match a 'name' attribute in ../config.xml with dbtype="ctree"

Requirements:
    pip install pyodbc xmltodict
"""

import os
import sys
import signal
import csv
import io

try:
    import pyodbc
    import xmltodict
except Exception as err:
    exc_type, exc_obj, exc_tb = sys.exc_info()
    fname = os.path.split(exc_tb.tb_frame.f_code.co_filename)[1]
    print("Import Error: {}. ExceptionType: {}, File: {}, Line: {}".format(
        err, exc_type, fname, exc_tb.tb_lineno))
    sys.exit(3)

# Try to enable readline for history/editing
try:
    import readline
    HIST_FILE = os.path.join(os.path.expanduser("~"), ".ctreesql_history")
    try:
        readline.read_history_file(HIST_FILE)
    except FileNotFoundError:
        pass
    readline.set_history_length(1000)
    HAS_READLINE = True
except ImportError:
    HAS_READLINE = False


def quit_clean():
    """Save readline history then hard-exit, skipping ODBC cleanup.

    pyodbc blocks in close()/finalizer when the server has dropped an idle
    connection.  os._exit() terminates immediately without running GC or
    atexit handlers, so the ODBC driver never gets a chance to hang.
    """
    if HAS_READLINE:
        try:
            readline.write_history_file(HIST_FILE)
        except Exception:
            pass
    os._exit(0)


# ── Config loading ────────────────────────────────────────────────────────────

def load_config(db_name):
    """Parse ../config.xml and return the database entry matching db_name."""
    mypath = os.path.dirname(os.path.realpath(__file__))
    parpath = os.path.abspath(os.path.join(mypath, os.pardir))
    configfile = os.path.join(parpath, "config.xml")

    if not os.path.isfile(configfile):
        print("Error: config.xml not found at {}".format(configfile))
        sys.exit(1)

    with open(configfile) as fd:
        allconfig = xmltodict.parse(fd.read(), attr_prefix='', dict_constructor=dict)

    databases = allconfig.get('hosts', {}).get('database', [])
    # xmltodict may return a single dict if there's only one element
    if isinstance(databases, dict):
        databases = [databases]

    for db in databases:
        if isinstance(db, str):
            continue
        if db.get('name') == db_name:
            return db

    return None


# ── Connection ────────────────────────────────────────────────────────────────

def build_connect_string(params):
    """Build ODBC connection string from config dict."""
    if 'connect' in params:
        return params['connect']
    parts = [
        "Driver={Faircom ODBC Driver}",
        "Host={}".format(params.get('dbhost', 'localhost')),
        "Database={}".format(params.get('dbname', 'liveSQL')),
        "Port={}".format(params.get('dbport', '6597')),
        "charset=UTF-8",
        "UID={}".format(params.get('dbuser', '')),
        "PWD={}".format(params.get('dbpass', '')),
    ]
    return ';'.join(parts)


def connect(params):
    conn_str = build_connect_string(params)
    try:
        conn = pyodbc.connect(conn_str, ansi=True)
        cur = conn.cursor()
        return cur, conn
    except Exception as err:
        print("Connection error: {}".format(err))
        sys.exit(1)


# ── Session state ─────────────────────────────────────────────────────────────

STATE = {
    'output_fmt': 'dos',   # 'dos' (bordered table) or 'csv'
}


# ── Output formatting ─────────────────────────────────────────────────────────

MAX_COL_WIDTH = 80  # truncate display beyond this many characters


def _clean_rows(columns, rows):
    """Normalize raw rows: strip padding, truncate, track numeric columns."""
    numeric = [True] * len(columns)
    str_rows = []
    for row in rows:
        str_row = []
        for i, val in enumerate(row):
            if val is None:
                s = ''
                numeric[i] = False
            elif isinstance(val, (int, float)):
                s = str(val)
            else:
                # Strip CHAR-field padding (cTree pads to declared column width)
                s = str(val).strip()
                numeric[i] = False
            if len(s) > MAX_COL_WIDTH:
                s = s[:MAX_COL_WIDTH - 3] + '...'
            str_row.append(s)
        str_rows.append(str_row)
    return str_rows, numeric


def print_table(columns, rows):
    """Print query results using the current STATE['output_fmt']."""
    if not columns:
        return

    str_rows, numeric = _clean_rows(columns, rows)

    if STATE['output_fmt'] == 'csv':
        buf = io.StringIO()
        writer = csv.writer(buf)
        writer.writerow(columns)
        writer.writerows(str_rows)
        print(buf.getvalue(), end='')
        return

    # DOS bordered table (default)
    widths = [len(str(c)) for c in columns]
    for str_row in str_rows:
        for i, s in enumerate(str_row):
            widths[i] = max(widths[i], len(s))

    sep    = '+' + '+'.join('-' * (w + 2) for w in widths) + '+'
    header = '|' + '|'.join(' {:<{}} '.format(c, widths[i]) for i, c in enumerate(columns)) + '|'

    print(sep)
    print(header)
    print(sep)
    for str_row in str_rows:
        parts = []
        for i, v in enumerate(str_row):
            if numeric[i]:
                parts.append(' {:>{}} '.format(v, widths[i]))
            else:
                parts.append(' {:<{}} '.format(v, widths[i]))
        print('|' + '|'.join(parts) + '|')
    print(sep)


def execute_query(cur, conn, sql):
    """Execute a SQL statement and display results."""
    sql = sql.strip()
    if not sql:
        return

    try:
        cur.execute(sql)

        # DML / DDL — no result set
        if cur.description is None:
            conn.commit()
            count = cur.rowcount
            if count < 0:
                print("OK")
            else:
                print("{} row(s) affected".format(count))
            return

        columns = [d[0] for d in cur.description]
        rows = cur.fetchall()
        print_table(columns, rows)
        count = len(rows)
        print("({} row{})".format(count, '' if count == 1 else 's'))

    except Exception as err:
        print("Error: {}".format(err))


# ── Meta / backslash commands ─────────────────────────────────────────────────

HELP_TEXT = """
Backslash commands:
  \\q, \\quit, exit   Quit ctreesql
  \\dt [schema]       List tables (optional schema filter)
  \\d  [schema.]table Describe table columns
  \\i  <file>         Execute SQL from a file
  \\c                 Show connection info
  \\g                 Execute buffer (no trailing semicolon needed)

  -- Session --
  \\name=<db>         Reconnect to a different database from config.xml
  \\output=dos|csv    Set output format (default: dos)

  -- Server info --
  \\v                 Server version    (fc_get_server_version)
  \\u                 User list         (fc_get_userlist)
  \\db                Database list     (fc_get_dblist)
  \\procs             Built-in proc list(fc_get_fcproclist)
  \\names             cTree entries in config.xml

  -- Stats (numeric values auto-formatted as KB/MB/GB) --
  \\s                 Connection stats  (fc_get_connstats)
  \\m                 Memory stats      (fc_get_memstats)
  \\io                I/O stats         (fc_get_iostats)
  \\ca                Cache stats       (fc_get_cachestats)
  \\lk                Lock stats        (fc_get_lockstats)
  \\tx                Transaction stats (fc_get_transtats)
  \\sq                SQL perf stats    (fc_get_sqlstats)
  \\is                ISAM engine stats (fc_get_isamstats)
  \\rp                Replication stats (fc_get_replstats)

  \\?  or \\h          Show this help

SQL is executed when the statement ends with a semicolon (;).
Use \\g on its own line to execute without a trailing semicolon.
"""


def format_bytes(n):
    """Format a byte count as a human-readable string."""
    if n >= 1024 ** 3:
        return '{:.2f} GB'.format(n / 1024 ** 3)
    elif n >= 1024 ** 2:
        return '{:.1f} MB'.format(n / 1024 ** 2)
    elif n >= 1024:
        return '{:.1f} KB'.format(n / 1024)
    return '{} B'.format(n)


def cmd_stats(cur, conn, proc):
    """Call a two-column fc_get_*stats() procedure and display with human-readable sizes."""
    try:
        cur.execute('call {}()'.format(proc))
        if cur.description is None:
            print("No results returned.")
            return
        rows = cur.fetchall()
        if not rows:
            print("No results returned.")
            return

        enhanced = []
        for row in rows:
            desc = str(row[0]).strip() if row[0] is not None else ''
            val  = row[1]
            try:
                n       = int(val)
                val_str = '{:,}'.format(n)
                # Only interpret as bytes if >= 1 MB; smaller values are likely counts
                pretty  = format_bytes(n) if n >= 1024 * 1024 else ''
            except (TypeError, ValueError):
                val_str = str(val).strip() if val is not None else ''
                pretty  = ''
            enhanced.append((desc, val_str, pretty))

        print_table(['Description', 'Value', 'Size'], enhanced)
    except Exception as err:
        print("Error: {}".format(err))


def cmd_names():
    """List all ctree databases from config.xml."""
    mypath = os.path.dirname(os.path.realpath(__file__))
    parpath = os.path.abspath(os.path.join(mypath, os.pardir))
    configfile = os.path.join(parpath, "config.xml")
    try:
        with open(configfile) as fd:
            allconfig = xmltodict.parse(fd.read(), attr_prefix='', dict_constructor=dict)
        databases = allconfig.get('hosts', {}).get('database', [])
        if isinstance(databases, dict):
            databases = [databases]
        rows = []
        for db in databases:
            if isinstance(db, str):
                continue
            if db.get('dbtype') == 'ctree':
                rows.append((
                    db.get('name', ''),
                    db.get('displayname', ''),
                    db.get('dbhost', ''),
                    db.get('dbuser', ''),
                    db.get('dbport', ''),
                ))
        if not rows:
            print("No ctree databases found in config.xml.")
            return
        print_table(['name', 'displayname', 'dbhost', 'dbuser', 'dbport'], rows)
        print("({} row{})".format(len(rows), '' if len(rows) == 1 else 's'))
    except Exception as err:
        print("Error reading config.xml: {}".format(err))


def cmd_list_tables(cur, conn, schema=None):
    """List tables using the ODBC metadata API."""
    try:
        if schema:
            result = cur.tables(schema=schema, tableType='TABLE')
        else:
            result = cur.tables(tableType='TABLE')
        rows = result.fetchall()
        if not rows:
            print("No tables found.")
            return
        columns = ['schema', 'table', 'type']
        data = [(r[1], r[2], r[3]) for r in rows]
        print_table(columns, data)
        print("({} row{})".format(len(data), '' if len(data) == 1 else 's'))
    except Exception as err:
        print("Error: {}".format(err))


def cmd_describe(cur, conn, table):
    """Describe table columns using the ODBC metadata API."""
    if not table:
        print("Usage: \\d [schema.]<table_name>")
        return
    table = table.strip()
    schema = None
    if '.' in table:
        schema, table = table.split('.', 1)
    try:
        result = cur.columns(table=table, schema=schema)
        rows = result.fetchall()
        if not rows:
            print("Table not found or no columns returned.")
            return
        # rows: TABLE_CAT, TABLE_SCHEM, TABLE_NAME, COLUMN_NAME,
        #       DATA_TYPE, TYPE_NAME, COLUMN_SIZE, BUFFER_LENGTH,
        #       DECIMAL_DIGITS, NUM_PREC_RADIX, NULLABLE, REMARKS,
        #       COLUMN_DEF, ...
        columns = ['column', 'type', 'size', 'nullable', 'default']
        data = []
        for r in rows:
            col_name  = r[3]
            type_name = r[5]
            col_size  = r[6]
            nullable  = 'YES' if r[10] == 1 else 'NO'
            default   = r[12] if r[12] is not None else ''
            data.append((col_name, type_name, col_size, nullable, default))
        print_table(columns, data)
        print("({} column{})".format(len(data), '' if len(data) == 1 else 's'))
    except Exception as err:
        print("Error: {}".format(err))


def cmd_exec_file(cur, conn, filepath):
    filepath = filepath.strip().strip('"').strip("'")
    if not os.path.isfile(filepath):
        print("File not found: {}".format(filepath))
        return
    with open(filepath, 'r', encoding='utf-8') as f:
        sql = f.read()
    # Split on semicolons and run each
    statements = [s.strip() for s in sql.split(';') if s.strip()]
    for stmt in statements:
        print("-- {}".format(stmt[:80]))
        execute_query(cur, conn, stmt)


def handle_meta_command(line, cur, conn, db_params):
    """Handle \\command lines.
    Returns True normally, or a dict {'cur','conn','db_params','prompt_name'}
    when a reconnect has occurred.
    """
    line = line.strip()
    if not line.startswith('\\'):
        return False

    parts = line[1:].split(None, 1)
    cmd = parts[0].lower() if parts else ''
    arg = parts[1] if len(parts) > 1 else ''

    # Support \cmd=value syntax as well as \cmd value
    if '=' in cmd:
        cmd, arg = cmd.split('=', 1)

    # ── Session settings ───────────────────────────────────────────────────────
    if cmd == 'name':
        new_name = arg.strip()
        if not new_name:
            print("Usage: \\name=<db_name>  (see \\names for available connections)")
            return True
        new_params = load_config(new_name)
        if new_params is None:
            print("Error: '{}' not found in config.xml".format(new_name))
            return True
        # Skip closing old connection — it may be stale and close() would hang
        new_cur, new_conn = connect(new_params)
        print("Connected to '{}' ({})".format(new_name, new_params.get('dbhost', '')))
        return {
            'cur':         new_cur,
            'conn':        new_conn,
            'db_params':   new_params,
            'prompt_name': new_params.get('dbname', new_name),
        }
    elif cmd == 'output':
        fmt = arg.strip().lower()
        if fmt not in ('dos', 'csv'):
            print("Usage: \\output=dos|csv  (current: {})".format(STATE['output_fmt']))
            return True
        STATE['output_fmt'] = fmt
        print("Output format set to '{}'.".format(fmt))
        return True
    elif cmd in ('q', 'quit'):
        print("Bye")
        quit_clean()
    elif cmd == 'dt':
        cmd_list_tables(cur, conn, schema=arg.strip() if arg.strip() else None)
    elif cmd == 'd':
        cmd_describe(cur, conn, arg)
    elif cmd == 'i':
        cmd_exec_file(cur, conn, arg)
    elif cmd == 'c':
        print("Database : {}".format(db_params.get('dbname', '')))
        print("Host     : {}".format(db_params.get('dbhost', '')))
        print("Port     : {}".format(db_params.get('dbport', '')))
        print("User     : {}".format(db_params.get('dbuser', '')))
        print("Name     : {}".format(db_params.get('name', '')))
    # ── Stats (Description/Value pairs with byte formatting) ──────────────────
    elif cmd == 's':
        cmd_stats(cur, conn, 'fc_get_connstats')
    elif cmd == 'm':
        cmd_stats(cur, conn, 'fc_get_memstats')
    elif cmd == 'io':
        cmd_stats(cur, conn, 'fc_get_iostats')
    elif cmd == 'ca':
        cmd_stats(cur, conn, 'fc_get_cachestats')
    elif cmd == 'lk':
        cmd_stats(cur, conn, 'fc_get_lockstats')
    elif cmd == 'tx':
        cmd_stats(cur, conn, 'fc_get_transtats')
    elif cmd == 'sq':
        cmd_stats(cur, conn, 'fc_get_sqlstats')
    elif cmd == 'is':
        cmd_stats(cur, conn, 'fc_get_isamstats')
    elif cmd == 'rp':
        cmd_stats(cur, conn, 'fc_get_replstats')
    # ── Admin / info ──────────────────────────────────────────────────────────
    elif cmd == 'names':
        cmd_names()
    elif cmd == 'v':
        execute_query(cur, conn, 'call fc_get_server_version()')
    elif cmd == 'u':
        execute_query(cur, conn, 'call fc_get_userlist()')
    elif cmd == 'db':
        execute_query(cur, conn, 'call fc_get_dblist()')
    elif cmd == 'procs':
        execute_query(cur, conn, 'call fc_get_fcproclist()')
    elif cmd in ('?', 'h'):
        print(HELP_TEXT)
    else:
        print("Unknown command: \\{}  (try \\?)".format(cmd))

    return True


# ── REPL ──────────────────────────────────────────────────────────────────────

def repl(cur, conn, db_params, prompt_name):
    prompt_main = "{}=# ".format(prompt_name)
    prompt_cont = "{}-> ".format(' ' * len(prompt_name))

    buffer = []

    def sigint_handler(sig, frame):
        # Ctrl-C clears the current buffer like psql
        print("\nQuery buffer cleared")
        buffer.clear()

    signal.signal(signal.SIGINT, sigint_handler)

    while True:
        prompt = prompt_main if not buffer else prompt_cont
        try:
            line = input(prompt)
        except EOFError:
            print("\nBye")
            quit_clean()

        stripped = line.strip()

        # Exit shortcuts without backslash
        if not buffer and stripped.lower() in ('exit', 'quit', '\\q'):
            print("Bye")
            quit_clean()

        # Backslash commands (only valid when buffer is empty or the whole line is a meta cmd)
        if stripped.startswith('\\'):
            # flush any pending buffer first
            if buffer:
                pending = ' '.join(buffer)
                print("Warning: discarding pending buffer: {}".format(pending[:60]))
                buffer.clear()
            result = handle_meta_command(stripped, cur, conn, db_params)
            if isinstance(result, dict):
                # Reconnect: swap in new connection and update prompts
                cur         = result['cur']
                conn        = result['conn']
                db_params   = result['db_params']
                prompt_name = result['prompt_name']
                prompt_main = "{}=# ".format(prompt_name)
                prompt_cont = "{}-> ".format(' ' * len(prompt_name))
            continue

        # \\g executes the current buffer without requiring a semicolon
        if stripped == '\\g':
            if buffer:
                execute_query(cur, conn, ' '.join(buffer))
                buffer.clear()
            continue

        # Accumulate lines
        buffer.append(line)

        # Execute if the statement ends with a semicolon (strip trailing whitespace/semicolons)
        combined = ' '.join(buffer).rstrip()
        if combined.endswith(';'):
            sql = combined[:-1]
            if sql.strip():
                execute_query(cur, conn, sql)
            buffer.clear()


# ── Entry point ───────────────────────────────────────────────────────────────

def usage():
    print("Usage: python ctreesql.py <db_name>")
    print()
    print("  <db_name>  Name attribute from ../config.xml (dbtype must be 'ctree')")
    print()
    print("Available cTree databases in config.xml:")
    mypath = os.path.dirname(os.path.realpath(__file__))
    parpath = os.path.abspath(os.path.join(mypath, os.pardir))
    configfile = os.path.join(parpath, "config.xml")
    try:
        with open(configfile) as fd:
            allconfig = xmltodict.parse(fd.read(), attr_prefix='', dict_constructor=dict)
        databases = allconfig.get('hosts', {}).get('database', [])
        if isinstance(databases, dict):
            databases = [databases]
        for db in databases:
            if isinstance(db, str):
                continue
            if db.get('dbtype') == 'ctree':
                disp = db.get('displayname', db.get('name', ''))
                print("  {:40s}  {}".format(db['name'], disp))
    except Exception:
        pass
    sys.exit(1)


def main():
    if len(sys.argv) < 2 or sys.argv[1] in ('-h', '--help'):
        usage()

    db_name = sys.argv[1]

    db_params = load_config(db_name)
    if db_params is None:
        print("Error: '{}' not found in config.xml".format(db_name))
        print()
        usage()

    if db_params.get('dbtype', '') != 'ctree':
        print("Warning: '{}' has dbtype='{}', not 'ctree'. Proceeding anyway.".format(
            db_name, db_params.get('dbtype', '')))

    print("ctreesql - connecting to '{}' ({})".format(
        db_name, db_params.get('displayname', db_params.get('dbhost', ''))))

    cur, conn = connect(db_params)

    prompt_name = db_params.get('dbname', db_name)
    print("Connected. Type \\? for help, \\q to quit.")
    print()

    repl(cur, conn, db_params, prompt_name)


if __name__ == '__main__':
    main()
