#!/usr/bin/env python3
"""
Emacs DaSQL Configuration Installer

Appends DaSQL key bindings to your Emacs init file.
F8 executes the active region (or the current line if nothing is selected)
against the matching DaSQL database; Ctrl+F8 runs the entire buffer.

Use:
    Open a terminal
    Change directory to where DaSQL lives (e.g.  cd c:/wasql/dasql)
    python emacs_installer.py

Note: Safe to re-run — it skips if the DaSQL block is already present.
      Restart Emacs (or `M-x eval-buffer`) after running it.
"""

import os
import sys
import shutil
import platform
from pathlib import Path


# ---------------------------------------------------------------------------
# Config generator — a literal elisp block with the correct paths baked in
# ---------------------------------------------------------------------------

def buildElispConfig(dasql_dir, python_exe):
    """Return an elisp config block ready to append to the Emacs init file."""
    # Emacs accepts forward slashes on Windows, so normalise everything.
    py_exe  = Path(python_exe).as_posix() if os.path.isabs(str(python_exe)) else str(python_exe)
    script  = (Path(dasql_dir) / 'dasql.py').as_posix()

    return r""";; ===== DaSQL Configuration =====
(defvar dasql-python """ + '"' + py_exe + '"' + r"""
  "Python executable used to run DaSQL.")
(defvar dasql-script """ + '"' + script + '"' + r"""
  "Absolute path to dasql.py.")

(defun dasql--dirname ()
  "Return the last path component of the current file's directory."
  (file-name-nondirectory
   (directory-file-name
    (file-name-directory (or (buffer-file-name) default-directory)))))

(defun dasql-run (query)
  "Send QUERY to DaSQL and show the result in a *DaSQL Output* buffer.
The buffer's file name (with extension) selects the dasql.ini section."
  (if (or (null query) (string-empty-p (string-trim query)))
      (message "DaSQL: nothing to run")
    (let ((fname   (if (buffer-file-name)
                       (file-name-nondirectory (buffer-file-name))
                     (buffer-name)))
          (dirname (dasql--dirname))
          (outbuf  (get-buffer-create "*DaSQL Output*")))
      (with-current-buffer outbuf
        (setq buffer-read-only nil)
        (erase-buffer))
      (call-process dasql-python nil outbuf nil
                    dasql-script fname dirname query)
      (with-current-buffer outbuf
        (goto-char (point-min))
        (setq buffer-read-only t))
      (display-buffer outbuf))))

(defun dasql-run-line-or-region ()
  "Run the active region, or the current line when no region is active."
  (interactive)
  (dasql-run
   (if (use-region-p)
       (buffer-substring-no-properties (region-beginning) (region-end))
     (buffer-substring-no-properties
      (line-beginning-position) (line-end-position)))))

(defun dasql-run-buffer ()
  "Run the entire buffer through DaSQL."
  (interactive)
  (dasql-run (buffer-substring-no-properties (point-min) (point-max))))

(global-set-key (kbd "<f8>")   #'dasql-run-line-or-region)
(global-set-key (kbd "C-<f8>") #'dasql-run-buffer)
;; ===== End DaSQL Configuration =====
"""


# ---------------------------------------------------------------------------
# Path discovery
# ---------------------------------------------------------------------------

def getInitCandidates():
    """Return candidate Emacs init files, most conventional first."""
    home = Path(os.path.expanduser('~'))
    candidates = [
        home / '.emacs.d' / 'init.el',
        home / '.emacs',
        home / '.config' / 'emacs' / 'init.el',
    ]
    if platform.system().lower() == 'windows':
        appdata = os.environ.get('APPDATA')
        if appdata:
            candidates += [
                Path(appdata) / '.emacs.d' / 'init.el',
                Path(appdata) / '.emacs',
            ]
    return candidates


def findPythonExecutable():
    exe = sys.executable
    if exe and os.path.exists(exe):
        return exe
    for name in ['python3', 'python']:
        found = shutil.which(name)
        if found:
            return found
    return 'python3'


def appendConfig(cfg_file, content):
    """Append the DaSQL block to cfg_file unless it's already there."""
    cfg_file.parent.mkdir(parents=True, exist_ok=True)

    if cfg_file.exists():
        existing = cfg_file.read_text(encoding='utf-8')
        if 'DaSQL Configuration' in existing:
            print(f'  ✓ config already present in {cfg_file}')
            return
        mode = 'a'
    else:
        mode = 'w'

    with open(cfg_file, mode, encoding='utf-8') as f:
        if mode == 'a':
            f.write('\n')
        f.write(content)

    print(f"  ✓ {'Updated' if mode == 'a' else 'Created'} {cfg_file}")


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

def main():
    print('Emacs DaSQL Installer')
    print('=' * 50)
    print(f'Operating System: {platform.system()} {platform.release()}')
    print(f'Python Version:   {sys.version}')
    print()

    dasql_dir = Path.cwd().resolve()
    if not (dasql_dir / 'dasql.py').exists():
        print(f'WARNING: dasql.py not found in {dasql_dir}')
    else:
        print(f'✓ DaSQL directory: {dasql_dir}')

    python_exe = findPythonExecutable()
    print(f'✓ Python: {python_exe}')

    if shutil.which('emacs'):
        print('✓ Emacs detected')
    else:
        print('! Emacs not found on PATH — installing config anyway '
              '(common on Windows; the block is harmless if Emacs is present).')

    # Install into an existing init file, else create the conventional one.
    candidates = getInitCandidates()
    target = next((p for p in candidates if p.exists()), candidates[0])

    print()
    appendConfig(target, buildElispConfig(dasql_dir, python_exe))

    print()
    print('=' * 50)
    print('✓ Installation complete!')
    print('=' * 50)
    print("""
Key bindings added:
  F8        — execute region, or current line if nothing is selected
  Ctrl+F8   — execute entire buffer

Usage:
  1. Restart Emacs (or run  M-x eval-buffer  in your init file)
  2. Open a .sql file whose name matches a section in dasql.ini
     (e.g.  ddfa_dev.sql  matches the [ddfa_dev] section)
  3. Put point on a query and press F8 — or select a region and press F8
  4. Results appear in a *DaSQL Output* buffer
""")


if __name__ == '__main__':
    main()
