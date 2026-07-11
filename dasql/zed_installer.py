#!/usr/bin/env python3
"""
Zed DaSQL Configuration Installer

Adds DaSQL tasks to Zed's global tasks.json and binds them in keymap.json.
F8 runs the current selection against the matching DaSQL database;
Ctrl+F8 runs the entire file.

Use:
    Open a terminal
    Change directory to where DaSQL lives (e.g.  cd c:/wasql/dasql)
    python zed_installer.py

Note: This script also updates the config if you already have it installed.
      Restart Zed after running it.
"""

import os
import sys
import json
import shutil
import platform
from pathlib import Path


# ---------------------------------------------------------------------------
# Path discovery
# ---------------------------------------------------------------------------

def findPythonExecutable():
    exe = sys.executable
    if exe and os.path.exists(exe):
        return Path(exe).as_posix()
    for name in ['python3', 'python']:
        found = shutil.which(name)
        if found:
            return Path(found).as_posix()
    return 'python3'

def getZedConfigDirs():
    """Return candidate Zed config directories for this OS, best guess first."""
    system = platform.system().lower()
    home   = Path(os.path.expanduser('~'))

    if system == 'windows':
        appdata      = Path(os.environ.get('APPDATA', home / 'AppData' / 'Roaming'))
        localappdata = Path(os.environ.get('LOCALAPPDATA', home / 'AppData' / 'Local'))
        return [appdata / 'Zed', localappdata / 'Zed']

    # macOS and Linux both use ~/.config/zed (Zed follows XDG on macOS too)
    xdg = os.environ.get('XDG_CONFIG_HOME')
    base = Path(xdg) if xdg else (home / '.config')
    return [base / 'zed']


def findOrCreateConfigDir():
    """Return an existing Zed config dir, or create the most likely one."""
    candidates = getZedConfigDirs()
    for path in candidates:
        if path.exists():
            return path, False
    target = candidates[0]
    target.mkdir(parents=True, exist_ok=True)
    return target, True


# ---------------------------------------------------------------------------
# Config generators
# ---------------------------------------------------------------------------

def buildTasks(working_dir, python_exe):
    """Return the DaSQL task objects for Zed's tasks.json (a JSON array)."""
    # Zed accepts forward slashes on every platform
    cwd = Path(working_dir).as_posix()
    return [
        {
            "label": "DaSQL: Execute Selection",
            "command": python_exe,
            # $ZED_FILE is the absolute path WITH extension, so .cli / script
            # detection works. $ZED_SELECTED_TEXT is the current selection.
            "args": ["dasql.py", "$ZED_FILE", "$ZED_DIRNAME", "$ZED_SELECTED_TEXT"],
            "cwd": cwd,
            "use_new_terminal": False,
            "allow_concurrent_runs": True,
            "reveal": "always",
            "hide": "on_success",
        },
        {
            "label": "DaSQL: Execute Entire File",
            "command": python_exe,
            "args": ["dasql.py", "$ZED_FILE", "$ZED_DIRNAME", "$ZED_FILE"],
            "cwd": cwd,
            "use_new_terminal": False,
            "allow_concurrent_runs": True,
            "reveal": "always",
            "hide": "on_success",
        },
    ]


def buildKeymap():
    """Return the DaSQL keymap object for Zed's keymap.json (a JSON array)."""
    return {
        "context": "Editor",
        "bindings": {
            "f8":     ["task::Spawn", {"task_name": "DaSQL: Execute Selection"}],
            "ctrl-f8": ["task::Spawn", {"task_name": "DaSQL: Execute Entire File"}],
        },
    }


# ---------------------------------------------------------------------------
# Helpers for reading Zed's JSONC-ish config (tolerate empty / commented files)
# ---------------------------------------------------------------------------

def loadJsonArray(path):
    """Load a JSON array from path, returning [] if missing or unreadable."""
    if not path.exists():
        return []
    try:
        text = path.read_text(encoding='utf-8').strip()
        if not text:
            return []
        data = json.loads(text)
        return data if isinstance(data, list) else []
    except Exception:
        # A commented-out default file (Zed ships these) isn't valid JSON.
        # Start fresh rather than corrupt it; the old content is replaced.
        return []


def isDasqlBinding(entry):
    """True if a keymap entry binds a DaSQL task (so we can replace it)."""
    if not isinstance(entry, dict):
        return False
    for value in entry.get("bindings", {}).values():
        if (isinstance(value, list) and len(value) >= 2
                and value[0] == "task::Spawn"
                and isinstance(value[1], dict)
                and str(value[1].get("task_name", "")).startswith("DaSQL:")):
            return True
    return False


# ---------------------------------------------------------------------------
# Install
# ---------------------------------------------------------------------------

def updateTasks(tasks_file, working_dir, python_exe):
    existing = loadJsonArray(tasks_file)
    existing = [t for t in existing
                if not str(t.get("label", "")).startswith("DaSQL:")]
    existing.extend(buildTasks(working_dir, python_exe))
    tasks_file.write_text(json.dumps(existing, indent=2) + "\n", encoding='utf-8')


def updateKeymap(keymap_file):
    existing = loadJsonArray(keymap_file)
    existing = [e for e in existing if not isDasqlBinding(e)]
    existing.append(buildKeymap())
    keymap_file.write_text(json.dumps(existing, indent=2) + "\n", encoding='utf-8')


def main():
    print('Zed DaSQL Installer')
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

    config_dir, created = findOrCreateConfigDir()
    print(f'✓ Zed config directory: {config_dir}' + (' (created)' if created else ''))

    tasks_file  = config_dir / 'tasks.json'
    keymap_file = config_dir / 'keymap.json'

    try:
        updateTasks(tasks_file, dasql_dir, python_exe)
        print(f'✓ Tasks:  {tasks_file}')
        updateKeymap(keymap_file)
        print(f'✓ Keymap: {keymap_file}')
    except PermissionError as e:
        print(f'\nERROR: Permission denied writing Zed config: {e}')
        sys.exit(1)
    except Exception as e:
        print(f'\nERROR: Installation failed: {e}')
        sys.exit(1)

    print()
    print('=' * 50)
    print('✓ Installation complete!')
    print('=' * 50)
    print("""
Key bindings added:
  F8        — execute selection
  Ctrl+F8   — execute entire file

Usage:
  1. Restart Zed to load the new tasks and keymap
  2. Open a .sql file whose name matches a section in dasql.ini
     (e.g.  ddfa_dev.sql  matches the [ddfa_dev] section)
  3. Select a query and press F8 — results appear in Zed's terminal panel
  4. Press Ctrl+F8 to run the whole file

Note: Zed has no "current line" task variable, so select the line (or any
      text) before pressing F8. You can also run the tasks from the command
      palette: 'task: spawn' -> 'DaSQL: ...'.
""")


if __name__ == '__main__':
    main()
