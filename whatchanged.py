#!/usr/bin/env python
"""
whatchanged.py - summarize the local (uncommitted) changes in a git repo.

Looks at everything git considers locally changed - staged, unstaged and
untracked - and prints a readable summary: which files changed, how much,
what kind of change it was, and (optionally) the actual changed lines.

Usage:
    python whatchanged.py                     summary of all local changes
    python whatchanged.py -d                  add per-hunk detail
    python whatchanged.py -d -d               add the actual +/- lines
    python whatchanged.py --staged            only what is staged for commit
    python whatchanged.py --unstaged          only what is not staged yet
    python whatchanged.py --no-untracked      skip untracked files
    python whatchanged.py php/ *.md           limit to these paths
    python whatchanged.py --json              machine readable output
    python whatchanged.py -c                  draft a commit message
    python whatchanged.py -c --conventional   ...in feat(scope): ... style
"""

import argparse
import json
import os
import re
import subprocess
import sys

# ---------------------------------------------------------------------------
# output helpers
# ---------------------------------------------------------------------------

for _s in (sys.stdout, sys.stderr):
    try:
        _s.reconfigure(encoding='utf-8', errors='replace')
    except Exception:
        pass

USE_COLOR = sys.stdout.isatty() and os.environ.get('NO_COLOR') is None


def c(text, code):
    """Wrap text in an ansi color when the terminal supports it."""
    if not USE_COLOR:
        return text
    return '\033[%sm%s\033[0m' % (code, text)


def bold(t):
    return c(t, '1')


def green(t):
    return c(t, '32')


def red(t):
    return c(t, '31')


def cyan(t):
    return c(t, '36')


def grey(t):
    return c(t, '90')


def plain_len(t):
    """Visible length of a possibly colorized string."""
    return len(re.sub(r'\033\[[0-9;]*m', '', t))


def pad(t, width):
    """Left-justify accounting for ansi codes."""
    return t + ' ' * max(0, width - plain_len(t))


def rpad(t, width):
    """Right-justify accounting for ansi codes."""
    return ' ' * max(0, width - plain_len(t)) + t


# ---------------------------------------------------------------------------
# git plumbing
# ---------------------------------------------------------------------------

def git(*args, **kw):
    """Run a git command and return stdout as text ('' on a tolerated failure)."""
    check = kw.pop('check', True)
    proc = subprocess.run(
        ('git',) + args,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        encoding='utf-8',
        errors='replace',
    )
    if proc.returncode != 0:
        if check:
            sys.stderr.write('git %s failed: %s\n' % (' '.join(args), proc.stderr.strip()))
            sys.exit(proc.returncode)
        return ''
    return proc.stdout


def repo_root():
    root = git('rev-parse', '--show-toplevel', check=False).strip()
    if not root:
        sys.stderr.write('not inside a git repository\n')
        sys.exit(1)
    return root


def current_branch():
    b = git('rev-parse', '--abbrev-ref', 'HEAD', check=False).strip()
    return b or '(no commits yet)'


def has_commits():
    return git('rev-parse', '--verify', '-q', 'HEAD', check=False).strip() != ''


# ---------------------------------------------------------------------------
# language-aware "what did this change touch" detection
# ---------------------------------------------------------------------------

SYMBOL_PATTERNS = [
    # php / js functions
    re.compile(r'^\s*(?:(?:public|private|protected|static|final|abstract|async)\s+)*function\s+([A-Za-z_$][\w$]*)'),
    # python
    re.compile(r'^\s*(?:async\s+)?def\s+([A-Za-z_]\w*)'),
    # classes / interfaces / traits
    re.compile(r'^\s*(?:abstract\s+|final\s+)?(?:class|interface|trait|enum)\s+([A-Za-z_]\w*)'),
    # js arrow / assigned functions:  const foo = (a) => {
    re.compile(r'^\s*(?:const|let|var)\s+([A-Za-z_$][\w$]*)\s*=\s*(?:async\s*)?(?:function\b|\([^)]*\)\s*=>)'),
    # sql ddl
    re.compile(r'^\s*(?:CREATE|ALTER|DROP)\s+(?:OR\s+REPLACE\s+)?(?:TABLE|VIEW|INDEX|FUNCTION|PROCEDURE)\s+`?([\w.]+)', re.I),
]

# wasql pages are mirrored to disk one file per page field by postedit
WASQL_FIELD_RE = re.compile(r'\._pages\.(\w+)\.\d+\.\w+$')


def symbols_in(lines):
    """Ordered, de-duplicated symbol names defined in these lines."""
    found = []
    for line in lines:
        for pat in SYMBOL_PATTERNS:
            m = pat.match(line)
            if m:
                name = m.group(1)
                if name not in found:
                    found.append(name)
                break
    return found


def human_size(n):
    if n < 1024:
        return '%dB' % n
    for unit in ('KB', 'MB', 'GB'):
        n /= 1024.0
        if n < 1024 or unit == 'GB':
            return '%.1f%s' % (n, unit)
    return '%.1fGB' % n


def describe_change(entry):
    """One short human sentence about what happened to this file."""
    st = entry['status']
    if st == 'untracked':
        if entry.get('binary'):
            return 'new untracked file (%s)' % human_size(entry.get('bytes', 0))
        return 'new untracked file, %d line%s' % (entry['added'], '' if entry['added'] == 1 else 's')
    if st == 'added':
        return 'new file, %d line%s added' % (entry['added'], '' if entry['added'] == 1 else 's')
    if st == 'deleted':
        return 'file deleted (%d line%s removed)' % (entry['removed'], '' if entry['removed'] == 1 else 's')

    bits = []
    if st == 'renamed':
        bits.append('renamed from %s' % entry['old_path'])
    elif st == 'copied':
        bits.append('copied from %s' % entry['old_path'])

    if entry.get('binary'):
        bits.append('binary content changed')
        return '; '.join(bits)

    hunks = entry.get('hunks', [])
    if hunks:
        bits.append('%d edit%s' % (len(hunks), '' if len(hunks) == 1 else 's'))
    if entry['added'] and entry['removed']:
        bits.append('+%d/-%d lines' % (entry['added'], entry['removed']))
    elif entry['added']:
        bits.append('+%d lines' % entry['added'])
    elif entry['removed']:
        bits.append('-%d lines' % entry['removed'])
    if not bits:
        bits.append('mode/metadata only')

    new_syms = [s for s in entry.get('added_symbols', []) if s not in entry.get('removed_symbols', [])]
    gone_syms = [s for s in entry.get('removed_symbols', []) if s not in entry.get('added_symbols', [])]
    if new_syms:
        bits.append('adds ' + ', '.join(new_syms[:4]) + ('...' if len(new_syms) > 4 else ''))
    if gone_syms:
        bits.append('drops ' + ', '.join(gone_syms[:4]) + ('...' if len(gone_syms) > 4 else ''))

    if not new_syms and not gone_syms:
        uniq = []
        for h in hunks:
            short = context_symbol(h.get('context', ''))
            if short and short not in uniq:
                uniq.append(short)
        if uniq:
            bits.append('near ' + ', '.join(uniq[:3]) + ('...' if len(uniq) > 3 else ''))
    return '; '.join(bits)


def context_symbol(context):
    """The symbol name out of an '@@ ... @@ <context>' tail, or '' for prose.

    git's hunk context is just the nearest preceding line that looked like a
    declaration, so in a .md file it is a sentence - useless in a summary.
    """
    context = (context or '').strip()
    if not context:
        return ''
    names = symbols_in([context])
    if names:
        return names[0]
    # a markdown heading is the one prose context worth keeping
    m = re.match(r'^#+\s+(.{1,40})', context)
    if m:
        return m.group(1).strip().rstrip('#').strip()
    return ''


# ---------------------------------------------------------------------------
# diff parsing
# ---------------------------------------------------------------------------

HUNK_RE = re.compile(r'^@@ -(\d+)(?:,(\d+))? \+(\d+)(?:,(\d+))? @@ ?(.*)$')

STATUS_MAP = {'A': 'added', 'D': 'deleted', 'M': 'modified', 'R': 'renamed',
              'C': 'copied', 'T': 'typechange'}


def new_entry(path):
    return {
        'path': path,
        'old_path': None,
        'status': 'modified',
        'added': 0,
        'removed': 0,
        'binary': False,
        'staged': False,
        'unstaged': False,
        'hunks': [],
        'added_symbols': [],
        'removed_symbols': [],
    }


def parse_diff(diff_text):
    """Parse a unified diff into {path: entry} with per-hunk detail."""
    files = {}
    cur = None
    hunk = None
    header_old = None

    for line in diff_text.split('\n'):
        if line.startswith('diff --git '):
            cur = None
            hunk = None
            header_old = None
            continue
        if line.startswith('--- '):
            p = line[4:]
            header_old = None if p == '/dev/null' else (p[2:] if p[:2] in ('a/', 'b/') else p)
            continue
        if line.startswith('+++ '):
            p = line[4:]
            if p == '/dev/null':
                # deletion: the file identity is the --- side
                path = header_old
            else:
                path = p[2:] if p[:2] in ('a/', 'b/') else p
            if path:
                cur = files.setdefault(path, new_entry(path))
            hunk = None
            continue
        if cur is None:
            continue
        if line.startswith('Binary files') or line.startswith('GIT binary patch'):
            cur['binary'] = True
            continue
        m = HUNK_RE.match(line)
        if m:
            hunk = {
                'old_start': int(m.group(1)),
                'old_count': int(m.group(2) if m.group(2) is not None else 1),
                'new_start': int(m.group(3)),
                'new_count': int(m.group(4) if m.group(4) is not None else 1),
                'context': m.group(5).strip(),
                'added': [],
                'removed': [],
            }
            cur['hunks'].append(hunk)
            continue
        if hunk is None:
            continue
        if line.startswith('+'):
            hunk['added'].append(line[1:])
        elif line.startswith('-'):
            hunk['removed'].append(line[1:])
    return files


def finalize(entry):
    """Roll hunk data up into per-file counts and symbol lists."""
    added_lines, removed_lines = [], []
    for h in entry['hunks']:
        added_lines.extend(h['added'])
        removed_lines.extend(h['removed'])
    if not entry['binary']:
        if not entry['added']:
            entry['added'] = len(added_lines)
        if not entry['removed']:
            entry['removed'] = len(removed_lines)
    if added_lines:
        entry['added_symbols'] = symbols_in(added_lines)
    if removed_lines:
        entry['removed_symbols'] = symbols_in(removed_lines)
    return entry


def _names(z):
    return [p for p in z.split('\0') if p]


def collect_tracked(args, base):
    """Entries for tracked files that differ from HEAD / the index."""
    if args.staged:
        scope = ['--cached']
    elif args.unstaged:
        scope = []
    else:
        scope = [base] if base else ['--cached']

    paths = (['--'] + args.paths) if args.paths else []

    entries = parse_diff(git(*(['diff', '--no-color', '-M', '--unified=3'] + scope + paths)))

    # statuses + rename sources
    ns = _names(git(*(['diff', '--name-status', '-M', '-z'] + scope + paths)))
    i = 0
    while i < len(ns):
        code = ns[i].strip()
        letter = code[0] if code else 'M'
        if letter in ('R', 'C') and i + 2 < len(ns):
            old, new = ns[i + 1], ns[i + 2]
            i += 3
            e = entries.setdefault(new, new_entry(new))
            e['status'] = STATUS_MAP.get(letter, 'modified')
            e['old_path'] = old
            entries.pop(old, None)
        else:
            path = ns[i + 1] if i + 1 < len(ns) else ''
            i += 2
            if not path:
                continue
            e = entries.setdefault(path, new_entry(path))
            e['status'] = STATUS_MAP.get(letter, 'modified')

    # authoritative line counts
    nums = _names(git(*(['diff', '--numstat', '-z', '-M'] + scope + paths)))
    j = 0
    while j < len(nums):
        parts = nums[j].split('\t')
        if len(parts) < 3:
            j += 1
            continue
        add, rem, path = parts[0], parts[1], parts[2]
        if path == '':  # rename record: the next two fields are old, new
            path = nums[j + 2] if j + 2 < len(nums) else ''
            j += 3
        else:
            j += 1
        e = entries.get(path)
        if not e:
            continue
        if add == '-' or rem == '-':
            e['binary'] = True
        else:
            e['added'], e['removed'] = int(add), int(rem)

    # which side of the index each change lives on
    staged = set(_names(git(*(['diff', '--name-only', '--cached', '-z'] + paths))))
    unstaged = set(_names(git(*(['diff', '--name-only', '-z'] + paths))))
    for path, e in entries.items():
        e['staged'] = path in staged or (e['old_path'] in staged if e['old_path'] else False)
        e['unstaged'] = path in unstaged

    return [finalize(e) for e in entries.values()]


def collect_untracked(args):
    """Entries for untracked-but-not-ignored files."""
    paths = (['--'] + args.paths) if args.paths else []
    entries = []
    for path in _names(git(*(['ls-files', '--others', '--exclude-standard', '-z'] + paths))):
        e = new_entry(path)
        e['status'] = 'untracked'
        e['unstaged'] = True
        try:
            e['bytes'] = os.path.getsize(path)
            with open(path, 'rb') as fh:
                raw = fh.read(2000000)
            if b'\0' in raw:
                e['binary'] = True
            else:
                lines = raw.decode('utf-8', 'replace').split('\n')
                if lines and lines[-1] == '':
                    lines.pop()
                e['added'] = len(lines)
                e['added_symbols'] = symbols_in(lines)
                e['preview'] = [l for l in lines[:60] if l.strip()][:5]
        except OSError:
            e['binary'] = True
        entries.append(e)
    return entries


# ---------------------------------------------------------------------------
# reporting
# ---------------------------------------------------------------------------

GROUP_ORDER = ['modified', 'added', 'renamed', 'copied', 'typechange', 'deleted', 'untracked']
GROUP_LABEL = {
    'modified': 'Modified',
    'added': 'Added',
    'renamed': 'Renamed',
    'copied': 'Copied',
    'typechange': 'Type changed',
    'deleted': 'Deleted',
    'untracked': 'Untracked',
}


def where(e):
    if e['status'] == 'untracked':
        return 'untracked'
    if e['staged'] and e['unstaged']:
        return 'partly staged'
    if e['staged']:
        return 'staged'
    return 'not staged'


def bar(added, removed, width=20):
    total = added + removed
    if total == 0:
        return ''
    scaled = min(total, width)
    plus = int(round(scaled * added / float(total))) if added else 0
    minus = max(0, scaled - plus)
    return green('+' * plus) + red('-' * minus)


def report(entries, args):
    scope = 'staged only' if args.staged else ('unstaged only' if args.unstaged else 'all local changes')
    print(bold('Local changes on %s' % current_branch()) + grey('  (%s)' % scope))

    if not entries:
        print(grey('  nothing changed - working tree is clean'))
        return

    tot_add = sum(e['added'] for e in entries)
    tot_rem = sum(e['removed'] for e in entries)
    print(grey('  %d file%s changed, ' % (len(entries), '' if len(entries) == 1 else 's')) +
          green('+%d' % tot_add) + grey(' / ') + red('-%d' % tot_rem))
    print()

    by_group = {}
    for e in entries:
        by_group.setdefault(e['status'], []).append(e)

    width = min(58, max(len(e['path']) for e in entries) + 1)

    for group in GROUP_ORDER:
        group_entries = by_group.get(group)
        if not group_entries:
            continue
        group_entries.sort(key=lambda x: (-(x['added'] + x['removed']), x['path']))
        print(bold(GROUP_LABEL[group]) + grey(' (%d)' % len(group_entries)))
        for e in group_entries:
            label = e['path']
            if len(label) > width:
                label = '...' + label[-(width - 3):]
            counts = rpad(green('+%d' % e['added']), 6) + ' ' + pad(red('-%d' % e['removed']), 6)
            print('  %s %s %s' % (pad(label, width), counts, bar(e['added'], e['removed'])))
            print('      %s' % grey(describe_change(e)))

            field = WASQL_FIELD_RE.search(e['path'])
            if field:
                print('      %s' % grey('wasql page field: %s' % field.group(1)))
            if e['status'] != 'untracked' and not args.staged and not args.unstaged:
                print('      %s' % grey(where(e)))

            if args.detail >= 1:
                for h in e['hunks']:
                    ctx = (' in %s' % h['context']) if h['context'] else ''
                    print('        %s %s' % (
                        cyan('@ line %d' % h['new_start']),
                        grey('+%d/-%d%s' % (len(h['added']), len(h['removed']), ctx))))
                    if args.detail >= 2:
                        shown = 0
                        for l in h['removed'][:args.max_lines]:
                            print('          ' + red('- ' + l.rstrip()[:160]))
                            shown += 1
                        for l in h['added'][:args.max_lines]:
                            print('          ' + green('+ ' + l.rstrip()[:160]))
                            shown += 1
                        extra = len(h['added']) + len(h['removed']) - shown
                        if extra > 0:
                            print('          ' + grey('... %d more changed line%s' % (extra, '' if extra == 1 else 's')))
            if args.detail >= 2 and e.get('preview'):
                for l in e['preview']:
                    print('          ' + grey('| ' + l.rstrip()[:160]))
        print()

    touched = []
    for e in entries:
        for s in e.get('added_symbols', []):
            if s not in e.get('removed_symbols', []) and s not in touched:
                touched.append(s)
    if touched:
        print(bold('New/changed definitions: ') + ', '.join(touched[:20]) +
              ('' if len(touched) <= 20 else grey(' (+%d more)' % (len(touched) - 20))))


# ---------------------------------------------------------------------------
# commit message drafting
# ---------------------------------------------------------------------------

DOC_EXT = ('.md', '.txt', '.rst', '.adoc')
TEST_HINT = ('test', 'spec', '_test.', '.test.')
SUBJECT_MAX = 72


def common_scope(paths):
    """Deepest directory shared by every path, or '' when they diverge."""
    dirs = [os.path.dirname(p.replace('\\', '/')) for p in paths]
    if not dirs or '' in dirs:
        return ''
    parts = [d.split('/') for d in dirs]
    shared = []
    for chunk in zip(*parts):
        if len(set(chunk)) != 1:
            break
        shared.append(chunk[0])
    return '/'.join(shared)


def guess_type(entries):
    """Conventional-commit type, only used with --conventional."""
    paths = [e['path'] for e in entries]
    if all(p.lower().endswith(DOC_EXT) for p in paths):
        return 'docs'
    if all(any(h in p.lower() for h in TEST_HINT) for p in paths):
        return 'test'
    if all(e['status'] in ('added', 'untracked') for e in entries):
        return 'feat'
    if all(e['status'] == 'deleted' for e in entries):
        return 'chore'
    text = ' '.join(l.lower() for e in entries for h in e['hunks'] for l in h['added'])
    if any(w in text for w in ('fix', 'bug', 'broken', 'crash', 'typo')):
        return 'fix'
    return 'feat' if any(e['added_symbols'] for e in entries) else 'chore'


def name_list(names, limit=3):
    """'a, b and c' / 'a, b and 4 others'."""
    if len(names) <= limit:
        if len(names) == 1:
            return names[0]
        return ', '.join(names[:-1]) + ' and ' + names[-1]
    return ', '.join(names[:limit]) + ' and %d others' % (len(names) - limit)


def commit_subject(entries, conventional):
    """One imperative-ish line describing the change set as a whole."""
    added = [e for e in entries if e['status'] in ('added', 'untracked')]
    deleted = [e for e in entries if e['status'] == 'deleted']
    renamed = [e for e in entries if e['status'] in ('renamed', 'copied')]
    modified = [e for e in entries if e['status'] in ('modified', 'typechange')]

    scope = common_scope([e['path'] for e in entries])
    names = [os.path.basename(e['path']) for e in entries]

    if len(entries) == 1 and renamed:
        core = 'moved %s to %s' % (os.path.basename(renamed[0]['old_path']), names[0])
    elif added and not modified and not deleted:
        core = 'added ' + name_list(names)
    elif deleted and not modified and not added:
        core = 'removed ' + name_list(names)
    elif modified and not added and not deleted:
        syms = []
        for e in modified:
            for s in e['added_symbols']:
                if s not in e['removed_symbols'] and s not in syms:
                    syms.append(s)
        if len(modified) == 1 and syms:
            core = 'added %s to %s' % (name_list(syms, 2), names[0])
        else:
            core = 'updated ' + name_list(names)
    else:
        pieces = []
        if modified:
            pieces.append('updated %d file%s' % (len(modified), '' if len(modified) == 1 else 's'))
        if added:
            pieces.append('added %s' % name_list([os.path.basename(e['path']) for e in added], 2))
        if deleted:
            pieces.append('removed %s' % name_list([os.path.basename(e['path']) for e in deleted], 2))
        if renamed:
            pieces.append('renamed %d file%s' % (len(renamed), '' if len(renamed) == 1 else 's'))
        core = ', '.join(pieces)

    if conventional:
        head = guess_type(entries) + ('(%s)' % scope if scope else '') + ': '
        core = head + core
    elif scope and len(entries) > 1 and scope not in core:
        core = '%s: %s' % (scope, core)

    if len(core) > SUBJECT_MAX:
        core = core[:SUBJECT_MAX - 3].rstrip(' ,') + '...'
    return core


def commit_message(entries, conventional):
    """Draft subject + bulleted body for the given entries."""
    lines = [commit_subject(entries, conventional), '']

    by_group = {}
    for e in entries:
        by_group.setdefault(e['status'], []).append(e)

    for group in GROUP_ORDER:
        group_entries = by_group.get(group)
        if not group_entries:
            continue
        group_entries.sort(key=lambda x: (-(x['added'] + x['removed']), x['path']))
        for e in group_entries:
            detail = describe_change(e)
            # the counts are already on the bullet, keep the prose part only
            detail = re.sub(r'^\d+ edits?; ', '', detail)
            detail = re.sub(r'^[+-]\d+(/-\d+)? lines?;? ?', '', detail)
            counts = '+%d/-%d' % (e['added'], e['removed'])
            if e['binary']:
                counts = 'binary'
            bullet = '- %s (%s)' % (e['path'], counts)
            if detail and not detail.startswith(('new file', 'new untracked', 'file deleted')):
                bullet += ': ' + detail
            lines.append(bullet)

    tot_add = sum(e['added'] for e in entries)
    tot_rem = sum(e['removed'] for e in entries)
    lines.append('')
    lines.append('%d file%s changed, +%d/-%d' % (
        len(entries), '' if len(entries) == 1 else 's', tot_add, tot_rem))
    return '\n'.join(lines)


def main():
    p = argparse.ArgumentParser(
        description='Summarize local (uncommitted) git changes.')
    p.add_argument('paths', nargs='*', help='limit to these paths (optional)')
    p.add_argument('-d', '--detail', action='count', default=0,
                   help='-d shows hunks, -dd also shows the changed lines')
    p.add_argument('--staged', action='store_true', help='only changes staged for commit')
    p.add_argument('--unstaged', action='store_true', help='only changes not yet staged')
    p.add_argument('--no-untracked', dest='untracked', action='store_false',
                   default=True, help='skip untracked files')
    p.add_argument('--max-lines', type=int, default=8,
                   help='max +/- lines shown per hunk with -dd (default 8)')
    p.add_argument('--json', action='store_true', help='emit json instead of a report')
    p.add_argument('-c', '--commit', action='store_true',
                   help='print a draft commit message for what would be committed')
    p.add_argument('--conventional', action='store_true',
                   help='with --commit, use conventional-commit style (feat(scope): ...)')
    args = p.parse_args()

    if args.staged and args.unstaged:
        p.error('--staged and --unstaged are mutually exclusive')

    root = repo_root()
    os.chdir(root)

    # a commit message must describe what git will actually record: if anything
    # is staged, that is the commit - otherwise assume `git commit -a` over the
    # tracked edits. untracked files are never part of either.
    if args.commit and not args.staged and not args.unstaged:
        if _names(git(*(['diff', '--name-only', '--cached', '-z'] +
                        ((['--'] + args.paths) if args.paths else [])))):
            args.staged = True
            sys.stderr.write('# describing STAGED changes (use --unstaged to draft for the rest)\n')
        else:
            args.untracked = False
            sys.stderr.write('# nothing staged - describing all tracked edits '
                             '(untracked files excluded)\n')

    base = 'HEAD' if has_commits() else None
    entries = collect_tracked(args, base)
    if args.untracked and not args.staged:
        entries += collect_untracked(args)

    entries.sort(key=lambda e: (GROUP_ORDER.index(e['status']) if e['status'] in GROUP_ORDER else 99,
                                -(e['added'] + e['removed']), e['path']))

    if args.commit:
        if not entries:
            sys.stderr.write('nothing to commit\n')
            return 1
        globals()['USE_COLOR'] = False
        print(commit_message(entries, args.conventional))
        return 0

    if args.json:
        out = []
        for e in entries:
            rec = dict(e)
            if args.detail == 0:
                rec.pop('hunks', None)
            rec['summary'] = describe_change(e)
            rec['location'] = where(e)
            out.append(rec)
        print(json.dumps({
            'branch': current_branch(),
            'root': root,
            'files': len(out),
            'added': sum(e['added'] for e in entries),
            'removed': sum(e['removed'] for e in entries),
            'entries': out,
        }, indent=2))
        return

    report(entries, args)


if __name__ == '__main__':
    sys.exit(main() or 0)
