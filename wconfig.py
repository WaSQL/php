#!/usr/bin/env python3
"""
wconfig.py - command-line manager for WaSQL config.xml (view / search / check / add / edit / remove)

Edits are TEXT-LEVEL: comments, ordering, indentation and the rest of the file are left untouched; only the
tag you change is rewritten. Every write makes a timestamped backup (config.xml.bak-YYYYmmddHHMMSS) and refuses
to save if the result would not be well-formed XML. Secrets (pass/secret/key/token...) are masked unless --reveal.

The file managed is config.xml (--config PATH, or ./config.xml, next to this script, or php/config.xml).
"""
import argparse
import concurrent.futures
import csv
import datetime
import html
import os
import re
import shutil
import socket
import sys
import xml.etree.ElementTree as ET

VERSION = '1.1.0'

HELP_EPILOG = """
commands:
  list databases [--type T]        <database> tags; columns name,type,host unless --fields is given
  list hosts                       <host> tags; columns name,database,dbname unless --fields is given
  list mysql                       databases of type mysql/mysqli (same columns as list databases)
  list types | list gitrepos       dbtype counts | <gitrepo> tags
  show host NAME [--resolved]      every attribute of a host; --resolved layers allhost < sameas < database < host
  show database NAME               every attribute of a database + which hosts use it
  show allhost | show gitrepo NAME
  search TERM                      case-insensitive match on tag names, attribute names and values
  uses DATABASE                    hosts that use the database
  checkhosts [--name HOST]         does each <host> name resolve in DNS?
  checkdbs   [--name DB]           does each <database> resolve? DNS on dbhost + TCP connect to its port
                                   (file databases - sqlite/duckdb/excel/access/csv - check the file exists)
                                   It does NOT log in, so credentials are not verified.
                                   Exit code is 1 if anything fails, so it works in scripts/cron.
  set   host|database|allhost|gitrepo NAME ATTR VALUE    add or change an attribute
  unset host|database|allhost|gitrepo NAME ATTR          remove an attribute
  add host NAME [ATTR=VALUE ...]        e.g. add host foo.com database=mydb access=all
  add database NAME [ATTR=VALUE ...]    e.g. add database db1 dbtype=mysqli dbhost=localhost dbname=x dbuser=u dbpass=p
  rm  host|database|gitrepo NAME        remove the whole tag
  validate                         check every config file is well-formed XML

--fields a,b,c picks any attribute names. For databases type=dbtype and host=dbhost;
host fields fall back to the host's database tag.

examples:
  wconfig.py list databases --format csv
  wconfig.py list hosts --fields name,database,dbname,dbhost
  wconfig.py show host localhost2 --resolved
  wconfig.py checkdbs --name spdq_dev
  wconfig.py set database spdq_dev dbport 5433
"""

KINDS = ('database', 'host', 'allhost', 'gitrepo')
TAG_RE = re.compile(r'<(database|host|allhost|gitrepo)\b((?:[^>"\']|"[^"]*"|\'[^\']*\')*?)(/?)>', re.I)
ATTR_RE = re.compile(r'([\w:.\-]+)(\s*=\s*)(?:"([^"]*)"|\'([^\']*)\')')
SECRET_RE = re.compile(r'pass|pwd|secret|key|token|salt|auth', re.I)
MYSQL_TYPES = ('mysql', 'mysqli')
DEFAULT_PORTS = {'mysql': 3306, 'mysqli': 3306, 'postgres': 5432, 'mssql': 1433, 'oracle': 1521,
                 'hana': 30015, 'firebird': 3050, 'ctree': 6597}
FILE_TYPES = ('sqlite', 'duckdb', 'msaccess', 'msexcel', 'mscsv')
FORMAT = 'table'
BACKUP = True


def die(msg):
    sys.stderr.write('ERROR: %s\n' % msg)
    sys.exit(1)


# ---------------------------------------------------------------- file / parsing
def find_config(path):
    if path:
        if not os.path.isfile(path):
            die('not found: %s' % path)
        return os.path.abspath(path)
    here = os.path.dirname(os.path.abspath(__file__))
    for c in (os.path.join(os.getcwd(), 'config.xml'), os.path.join(here, 'config.xml'),
              os.path.join(here, 'php', 'config.xml')):
        if os.path.isfile(c):
            return os.path.abspath(c)
    die('config.xml not found - pass --config PATH')


def read(path):
    with open(path, 'r', encoding='utf-8', newline='') as f:
        return f.read()


def mask_comments(text):
    """Blank out <!-- --> comments (same length, newlines kept) so commented-out tags are ignored."""
    return re.sub(r'<!--.*?-->', lambda m: re.sub(r'[^\r\n]', ' ', m.group(0)), text, flags=re.S)


def parse_elements(path):
    text = read(path)
    masked = mask_comments(text)
    els = []
    for m in TAG_RE.finditer(masked):
        attrs = []  # (name, value, value_start, value_end, attr_start, attr_end)
        base = m.start(2)
        for a in ATTR_RE.finditer(m.group(2)):
            if a.group(3) is not None:
                v, vs, ve = a.group(3), a.start(3), a.end(3)
            else:
                v, vs, ve = a.group(4), a.start(4), a.end(4)
            attrs.append((a.group(1), html.unescape(v), base + vs, base + ve, base + a.start(), base + a.end()))
        name = next((a[1] for a in attrs if a[0].lower() == 'name'), '')
        els.append({'kind': m.group(1).lower(), 'name': name, 'attrs': attrs, 'file': path,
                    'start': m.start(), 'end': m.end(), 'selfclose': m.group(3) == '/',
                    'close_pos': m.end() - (2 if m.group(3) == '/' else 1)})
    return els


def config_files(main):
    return [main]


def load_all(main):
    els = []
    for f in config_files(main):
        els += parse_elements(f)
    return els


def attr_dict(el):
    return {a[0]: a[1] for a in el['attrs']}


def get(els, kind, name):
    return [e for e in els if e['kind'] == kind and (kind == 'allhost' or e['name'].lower() == name.lower())]


def need(els, kind, name):
    hits = get(els, kind, name)
    if not hits:
        die('%s "%s" not found' % (kind, name))
    return hits[0]


# ---------------------------------------------------------------- display
def shown(k, v, reveal):
    if not reveal and SECRET_RE.search(k) and len(v) > 0:
        return v[:3] + '****' if len(v) > 8 else '****'
    return v


def table(rows, headers, count=True):
    """Print rows as an aligned table, or CSV when --format csv (no '(n)' footer then)."""
    rows = [[str(c) for c in r] for r in rows]
    if FORMAT == 'csv':
        w = csv.writer(sys.stdout, lineterminator='\n')
        w.writerow(headers)
        w.writerows(rows)
        return
    w = [max(len(h), *(len(r[i]) for r in rows)) if rows else len(h) for i, h in enumerate(headers)]
    print('  '.join(h.ljust(w[i]) for i, h in enumerate(headers)))
    print('  '.join('-' * w[i] for i in range(len(headers))))
    for r in rows:
        print('  '.join(r[i].ljust(w[i]) for i in range(len(headers))))
    if count:
        print('(%d)' % len(rows))


def print_attrs(d, reveal, src=None):
    if FORMAT == 'csv':
        hdr = ['attribute', 'value'] + (['source'] if src else [])
        table([[k, shown(k, d[k], reveal)] + ([src.get(k, '')] if src else []) for k in d], hdr)
        return
    w = max([len(k) for k in d] or [1])
    for k in d:
        line = '  %s = %s' % (k.ljust(w), shown(k, d[k], reveal))
        if src and src.get(k):
            line += '   [%s]' % src[k]
        print(line)


def field_list(args, default):
    return [f.strip() for f in args.fields.split(',') if f.strip()] if args.fields else default


def cmd_list(els, what, args):
    if what in ('databases', 'mysql'):
        fields = field_list(args, ['name', 'type', 'host'])
        alias = {'type': 'dbtype', 'host': 'dbhost'}
        rows = []
        for e in els:
            if e['kind'] != 'database':
                continue
            d = {k.lower(): v for k, v in attr_dict(e).items()}
            t = d.get('dbtype', '')
            if what == 'mysql' and t.lower() not in MYSQL_TYPES:
                continue
            if args.type and t.lower() != args.type.lower():
                continue
            rows.append([d.get(f.lower(), d.get(alias.get(f.lower(), ''), '')) for f in fields])
        rows.sort(key=lambda r: r[0].lower())
        table(rows, fields)
    elif what == 'hosts':
        fields = field_list(args, ['name', 'database', 'dbname'])
        dbs = {e['name'].lower(): attr_dict(e) for e in els if e['kind'] == 'database'}
        rows = []
        for e in els:
            if e['kind'] != 'host':
                continue
            d = {k.lower(): v for k, v in attr_dict(e).items()}
            dbtag = {k.lower(): v for k, v in dbs.get(d.get('database', '').lower(), {}).items()}
            d['name'] = e['name']
            rows.append([d.get(f.lower(), dbtag.get(f.lower(), '')) for f in fields])
        rows.sort(key=lambda r: r[0].lower())
        table(rows, fields)
    elif what == 'types':
        c = {}
        for e in els:
            if e['kind'] == 'database':
                t = attr_dict(e).get('dbtype', '(none)')
                c[t] = c.get(t, 0) + 1
        table(sorted(([t, n] for t, n in c.items()), key=lambda r: -r[1]), ['dbtype', 'count'])
    elif what == 'gitrepos':
        table([[e['name'], attr_dict(e).get('url', '')] for e in els if e['kind'] == 'gitrepo'], ['name', 'url'])
    else:
        die('list what? databases | hosts | mysql | types | gitrepos')


def resolved(els, name):
    """allhost < sameas(sameas) < sameas < (database tag attrs) < host - mirrors php/config.php."""
    d, src = {}, {}

    def layer(attrs, label):
        for k, v in attrs.items():
            d[k.lower()] = v
            src[k.lower()] = label
    for e in get(els, 'allhost', ''):
        layer(attr_dict(e), 'allhost')
    byname = {e['name'].lower(): e for e in els if e['kind'] == 'host'}
    dbs = {e['name'].lower(): attr_dict(e) for e in els if e['kind'] == 'database'}

    def host_layer(h, label):
        a = attr_dict(h)
        dbn = a.get('database', '').lower()
        if dbn in dbs:
            layer({k: v for k, v in dbs[dbn].items() if k.lower() != 'name'}, 'database:' + dbn)
        layer(a, label)
    h = byname.get(name.lower())
    if not h:
        die('host "%s" not found' % name)
    chain = []
    s1 = attr_dict(h).get('sameas', '').lower()
    if s1 in byname:
        s2 = attr_dict(byname[s1]).get('sameas', '').lower()
        if s2 in byname:
            chain.append((byname[s2], 'sameas:' + s2))
        chain.append((byname[s1], 'sameas:' + s1))
    for x, label in chain:
        host_layer(x, label)
    host_layer(h, 'host')
    return d, src


def cmd_show(els, kind, name, args):
    if kind == 'allhost':
        e = need(els, 'allhost', '')
        if FORMAT != 'csv':
            print('<allhost>')
        print_attrs(attr_dict(e), args.reveal)
        return
    if not name:
        die('show %s NAME' % kind)
    if kind == 'host' and args.resolved:
        d, src = resolved(els, name)
        if FORMAT != 'csv':
            print('<host %s> resolved' % name)
        print_attrs(d, args.reveal, src)
        return
    e = need(els, kind, name)
    if FORMAT != 'csv':
        print('<%s %s>' % (kind, e['name']))
    print_attrs(attr_dict(e), args.reveal)
    if kind == 'database' and FORMAT != 'csv':
        users = cmd_uses(els, name, quiet=True)
        print('used by: %s' % (', '.join(users) if users else '(no hosts)'))


def cmd_uses(els, dbname, quiet=False):
    out = [e['name'] for e in els if e['kind'] == 'host' and attr_dict(e).get('database', '').lower() == dbname.lower()]
    if not quiet:
        table([[h] for h in out], ['host'], count=False)
    return out


def cmd_search(els, term, args):
    t = term.lower()
    rows = []
    for e in els:
        tag_hit = t in e['kind'] or t in e['name'].lower()
        found = False
        for k, v, *_ in e['attrs']:
            if t in k.lower() or t in v.lower():
                rows.append([e['kind'], e['name'], k, shown(k, v, args.reveal)])
                found = True
        if tag_hit and not found:
            rows.append([e['kind'], e['name'], '', ''])
    table(rows, ['tag', 'name', 'attribute', 'value'])


# ---------------------------------------------------------------- checks
def resolve_name(host, timeout):
    """DNS (or literal-IP) lookup with a timeout. Returns (ip, error)."""
    ex = concurrent.futures.ThreadPoolExecutor(max_workers=1)
    fut = ex.submit(socket.getaddrinfo, host, None, 0, socket.SOCK_STREAM)
    try:
        return fut.result(timeout=timeout)[0][4][0], ''
    except concurrent.futures.TimeoutError:
        return '', 'dns timeout'
    except Exception as e:
        return '', str(e.args[-1] if e.args else e)
    finally:
        ex.shutdown(wait=False)


def check_host(e, args):
    ip, err = resolve_name(e['name'], args.timeout)
    return [e['name'], 'ok' if ip else 'FAIL', ip, err]


def check_db(e, args):
    d = {k.lower(): v for k, v in attr_dict(e).items()}
    t = d.get('dbtype', '').lower()
    if t in FILE_TYPES:
        path = d.get('dbname', '')
        ok = bool(path) and os.path.exists(path)
        return [e['name'], t, path, '', '', 'file' if ok else 'FAIL', '' if ok else 'file not found']
    host = d.get('dbhost', '')
    if not host:
        return [e['name'], t, '', '', '', 'FAIL', 'no dbhost attribute']
    inst = '\\' in host
    hostonly = host.split('\\')[0]
    port = d.get('dbport', '')
    if not port and hostonly.count(':') == 1:
        hostonly, port = hostonly.split(':')
    if not port and t in DEFAULT_PORTS and not inst:
        port = str(DEFAULT_PORTS[t])
    ip, err = resolve_name(hostonly, args.timeout)
    if not ip:
        return [e['name'], t, host, port, 'FAIL', 'FAIL', err]
    if not port or not port.isdigit():
        return [e['name'], t, host, port, 'ok', 'dns-only', 'no port to test' + (' (named instance)' if inst else '')]
    try:
        socket.create_connection((ip, int(port)), timeout=args.timeout).close()
        return [e['name'], t, host, port, 'ok', 'ok', ip]
    except Exception as ex:
        return [e['name'], t, host, port, 'ok', 'FAIL', 'tcp %s: %s' % (ip, getattr(ex, 'strerror', None) or str(ex) or type(ex).__name__)]


def cmd_check(els, kind, args):
    targets = [e for e in els if e['kind'] == kind and (not args.name or e['name'].lower() == args.name.lower())]
    if not targets:
        die('%s "%s" not found' % (kind, args.name) if args.name else 'no %s tags found' % kind)
    seen, uniq = set(), []
    for e in targets:  # a name defined twice is checked once
        if e['name'].lower() not in seen:
            seen.add(e['name'].lower())
            uniq.append(e)
    targets = uniq
    fn = check_host if kind == 'host' else check_db
    with concurrent.futures.ThreadPoolExecutor(max_workers=16) as pool:
        rows = list(pool.map(lambda e: fn(e, args), targets))
    rows.sort(key=lambda r: r[0].lower())
    status = 1 if kind == 'host' else 5
    hdr = ['name', 'status', 'ip', 'detail'] if kind == 'host' else ['name', 'type', 'host', 'port', 'dns', 'connect', 'detail']
    table(rows, hdr, count=False)
    bad = sum(1 for r in rows if r[status] == 'FAIL')
    if FORMAT != 'csv':
        print('%d ok, %d failed' % (len(rows) - bad, bad))
    sys.exit(1 if bad else 0)


# ---------------------------------------------------------------- writing
def esc(v):
    return v.replace('&', '&amp;').replace('"', '&quot;').replace('<', '&lt;')


def eol_of(text):
    return '\r\n' if '\r\n' in text else '\n'


def save(path, new_text):
    try:
        ET.fromstring(new_text.encode('utf-8'))
    except ET.ParseError as ex:
        die('edit would make %s invalid XML (%s) - nothing written' % (path, ex))
    bak = '%s.bak-%s' % (path, datetime.datetime.now().strftime('%Y%m%d%H%M%S'))
    if BACKUP:
        shutil.copy2(path, bak)
    with open(path, 'w', encoding='utf-8', newline='') as f:
        f.write(new_text)
    print('saved %s%s' % (path, '  (backup: %s)' % os.path.basename(bak) if BACKUP else ''))


def cmd_set(els, kind, name, attr, value):
    e = need(els, kind, name)
    text = read(e['file'])
    existing = next((a for a in e['attrs'] if a[0].lower() == attr.lower()), None)
    if existing:
        if existing[1] == value:
            print('no change (already %s)' % value)
            return
        text = text[:existing[2]] + esc(value) + text[existing[3]:]
        print('%s %s: %s  %s -> %s' % (kind, e['name'] or '', existing[0], existing[1], value))
    else:
        eol = eol_of(text)
        last = e['attrs'][-1] if e['attrs'] else None
        sep = ' '
        if last:
            before = text[:last[4]]
            ws = re.search(r'[ \t]*$', before).group(0)
            if before[:len(before) - len(ws)].endswith(('\n', '\r')):
                sep = eol + ws  # multi-line tag: match the attribute indentation
            pos = last[5]
        else:
            head = text[:e['close_pos']]
            pos = len(head.rstrip())
        text = text[:pos] + '%s%s="%s"' % (sep, attr, esc(value)) + text[pos:]
        print('%s %s: added %s = %s' % (kind, e['name'] or '', attr, value))
    save(e['file'], text)


def cmd_unset(els, kind, name, attr):
    e = need(els, kind, name)
    a = next((x for x in e['attrs'] if x[0].lower() == attr.lower()), None)
    if not a:
        die('%s "%s" has no attribute "%s"' % (kind, name, attr))
    text = read(e['file'])
    s = a[4]
    while s > 0 and text[s - 1] in ' \t\r\n':
        s -= 1
    text = text[:s] + text[a[5]:]
    print('removed %s from %s %s (was: %s)' % (a[0], kind, e['name'], a[1]))
    save(e['file'], text)


def cmd_add(els, main, kind, name, pairs):
    if kind not in ('host', 'database', 'gitrepo'):
        die('can only add host | database | gitrepo')
    if get(els, kind, name):
        die('%s "%s" already exists' % (kind, name))
    attrs = [('name', name)]
    for p in pairs:
        if '=' not in p:
            die('expected ATTR=VALUE, got "%s"' % p)
        k, v = p.split('=', 1)
        attrs.append((k, v))
    target = main
    text = read(target)
    eol = eol_of(text)
    if len(attrs) <= 2:
        tag = '    <%s %s />' % (kind, ' '.join('%s="%s"' % (k, esc(v)) for k, v in attrs))
    else:
        tag = '    <%s%s%s%s    />' % (kind, eol, eol.join('        %s="%s"' % (k, esc(v)) for k, v in attrs), eol)
    close = text.lower().rfind('</hosts>')
    if close < 0:
        die('no </hosts> found in %s' % target)
    ls = text.rfind('\n', 0, close) + 1
    pre = text[:ls] if not text[ls:close].strip() else text[:close]
    new = pre + tag + eol + text[len(pre):]
    print('added <%s %s> to %s' % (kind, name, os.path.basename(target)))
    save(target, new)


def cmd_rm(els, kind, name):
    if kind == 'allhost':
        die('refusing to remove <allhost>')
    e = need(els, kind, name)
    text = read(e['file'])
    s, en = e['start'], e['end']
    ls = text.rfind('\n', 0, s) + 1
    if not text[ls:s].strip():
        s = ls
    le = text.find('\n', en)
    if le >= 0 and not text[en:le].strip():
        en = le + 1
    text = text[:s] + text[en:]
    print('removed <%s %s> from %s' % (kind, e['name'], os.path.basename(e['file'])))
    save(e['file'], text)


# ---------------------------------------------------------------- main
def main():
    global FORMAT, BACKUP
    ap = argparse.ArgumentParser(prog='wconfig.py', usage='wconfig.py [options] <command> [args]',
                                 description=__doc__, epilog=HELP_EPILOG,
                                 formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument('-V', '--version', action='version', version='wconfig.py %s' % VERSION)
    ap.add_argument('--config', help='path to config.xml (default: ./config.xml, next to this script, or php/config.xml)')
    ap.add_argument('--no-backup', action='store_true', help='set/unset/add/rm: do not write a config.xml.bak-* copy first')
    ap.add_argument('--reveal', action='store_true', help='show secrets unmasked')
    ap.add_argument('--resolved', action='store_true', help='show host with inherited values')
    ap.add_argument('--type', help='list databases: filter by dbtype')
    ap.add_argument('--format', choices=('table', 'csv'), default='table', help='output format (default table)')
    ap.add_argument('--fields', help='list: comma-separated attribute names to output')
    ap.add_argument('--name', help='checkhosts/checkdbs: check just this host/database')
    ap.add_argument('--timeout', type=float, default=3.0, help='checkhosts/checkdbs: seconds per lookup/connect (default 3)')
    ap.add_argument('cmd', nargs='?', help='command (see list below)')
    ap.add_argument('rest', nargs='*', help=argparse.SUPPRESS)
    args = ap.parse_args()
    if not args.cmd or args.cmd.lower() == 'help':
        ap.print_help()
        return
    FORMAT = args.format
    BACKUP = not args.no_backup
    main_path = find_config(args.config)
    els = load_all(main_path)
    c, r = args.cmd.lower(), args.rest

    def arg(i, label):
        if len(r) <= i:
            die('missing %s (see --help)' % label)
        return r[i]
    if c == 'list':
        cmd_list(els, arg(0, 'what').lower(), args)
    elif c == 'show':
        kind = arg(0, 'kind').lower()
        cmd_show(els, kind, r[1] if len(r) > 1 else '', args)
    elif c == 'uses':
        cmd_uses(els, arg(0, 'database'))
    elif c == 'search':
        cmd_search(els, arg(0, 'term'), args)
    elif c == 'checkhosts':
        cmd_check(els, 'host', args)
    elif c == 'checkdbs':
        cmd_check(els, 'database', args)
    elif c == 'set':
        kind = arg(0, 'kind').lower()
        if kind == 'allhost':
            r.insert(1, '')
        cmd_set(els, kind, arg(1, 'name'), arg(2, 'attr'), arg(3, 'value'))
    elif c == 'unset':
        kind = arg(0, 'kind').lower()
        if kind == 'allhost':
            r.insert(1, '')
        cmd_unset(els, kind, arg(1, 'name'), arg(2, 'attr'))
    elif c == 'add':
        cmd_add(els, main_path, arg(0, 'kind').lower(), arg(1, 'name'), r[2:])
    elif c == 'rm':
        cmd_rm(els, arg(0, 'kind').lower(), arg(1, 'name'))
    elif c == 'validate':
        for f in config_files(main_path):
            try:
                ET.parse(f)
                print('ok       %s' % f)
            except ET.ParseError as ex:
                print('INVALID  %s: %s' % (f, ex))
    else:
        die('unknown command "%s" (see --help)' % c)


if __name__ == '__main__':
    main()
