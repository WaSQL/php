#!/usr/bin/env python3
"""
JetBrains IDEs DaSQL Configuration Installer

Installs DaSQL as an External Tool in all detected JetBrains IDEs
(IntelliJ IDEA, PyCharm, DataGrip, WebStorm, etc.)

Use:
    Open a DOS/CMD prompt or terminal
    Change directory to where DaSQL lives (e.g.  cd c:/wasql/dasql)
    python jetbrains_installer.py

Note: Restart any open IDE after running this script.
"""

import os
import sys
import platform
import shutil
from pathlib import Path


KNOWN_PRODUCTS = [
    'IntelliJIdea', 'PyCharm', 'DataGrip', 'WebStorm', 'PhpStorm',
    'RubyMine', 'CLion', 'GoLand', 'Rider', 'DataSpell', 'Fleet',
]


def getJetBrainsConfigBase():
    system = platform.system().lower()
    if system == 'windows':
        appdata = os.environ.get('APPDATA', '')
        return Path(appdata) / 'JetBrains'
    elif system == 'darwin':
        return Path(os.path.expanduser('~/Library/Application Support/JetBrains'))
    else:
        return Path(os.path.expanduser('~/.config/JetBrains'))


def findJetBrainsProducts(base_dir):
    if not base_dir.exists():
        return []
    products = []
    for item in sorted(base_dir.iterdir()):
        if item.is_dir() and any(item.name.startswith(p) for p in KNOWN_PRODUCTS):
            products.append(item)
    return products


def findPythonExecutable():
    exe = sys.executable
    if exe and os.path.exists(exe):
        return exe
    for name in ['python3', 'python']:
        found = shutil.which(name)
        if found:
            return found
    return 'python3'


def generateToolsXml(dasql_dir, python_exe):
    # JetBrains accepts forward slashes on all platforms
    dasql_py   = (Path(dasql_dir) / 'dasql.py').as_posix()
    working    = Path(dasql_dir).as_posix()
    python_cmd = Path(python_exe).as_posix() if os.path.isabs(str(python_exe)) else str(python_exe)

    def tool(name, description, param_arg3):
        params = (
            '&quot;' + dasql_py + '&quot;'
            ' &quot;$FileNameWithoutExtension$&quot;'
            ' &quot;$FileDir$&quot;'
            ' ' + param_arg3
        )
        return (
            f'  <tool name="{name}" description="{description}"'
            ' showInMainMenu="true" showInEditor="true" showInProject="false"'
            ' showInSearchPopup="false" disabled="false" useConsole="true"'
            ' showConsoleOnStdOut="false" showConsoleOnStdErr="false"'
            ' synchronizeAfterRun="true">\n'
            '    <exec>\n'
            f'      <option name="COMMAND" value="{python_cmd}" />\n'
            f'      <option name="PARAMETERS" value="{params}" />\n'
            f'      <option name="WORKING_DIRECTORY" value="{working}" />\n'
            '    </exec>\n'
            '  </tool>'
        )

    lines = [
        '<toolSet name="DaSQL">',
        tool(
            'Execute Selection',
            'Run selected SQL with DaSQL (F8)',
            '&quot;$SelectedText$&quot;',
        ),
        tool(
            'Execute File',
            'Run entire file with DaSQL (Ctrl+F8)',
            '&quot;$FilePath$&quot;',
        ),
        '</toolSet>',
    ]
    return '\n'.join(lines) + '\n'


def installForProduct(product_dir, dasql_dir, python_exe):
    tools_dir = product_dir / 'tools'
    tools_dir.mkdir(parents=True, exist_ok=True)
    tools_file = tools_dir / 'DaSQL.xml'
    tools_file.write_text(generateToolsXml(dasql_dir, python_exe), encoding='utf-8')
    return tools_file


def main():
    print('JetBrains IDEs DaSQL Installer')
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

    base_dir = getJetBrainsConfigBase()
    print(f'✓ JetBrains config base: {base_dir}')

    if not base_dir.exists():
        print('\nERROR: JetBrains config directory not found.')
        print('Please ensure at least one JetBrains IDE is installed.')
        sys.exit(1)

    products = findJetBrainsProducts(base_dir)
    if not products:
        print('\nERROR: No JetBrains IDE installations detected.')
        sys.exit(1)

    print(f'\nFound {len(products)} JetBrains IDE(s):')
    installed = []
    for product in products:
        try:
            tools_file = installForProduct(product, dasql_dir, python_exe)
            print(f'  ✓ {product.name}  →  {tools_file}')
            installed.append(product.name)
        except Exception as e:
            print(f'  ✗ {product.name}: {e}')

    if not installed:
        print('\nERROR: Failed to install for any IDE.')
        sys.exit(1)

    print()
    print('=' * 50)
    print('✓ Installation complete!')
    print('=' * 50)
    print("""
Next steps — assign shortcuts in each IDE:

  1. Restart the IDE (required to pick up the new External Tool)
  2. Go to  Settings → Tools → External Tools
     Confirm the "DaSQL" group contains "Execute Selection" and "Execute File"
  3. Go to  Settings → Keymap
  4. In the search box type: DaSQL
  5. Double-click "Execute Selection" → Add Keyboard Shortcut → press F8
  6. Double-click "Execute File"      → Add Keyboard Shortcut → press Ctrl+F8
  7. Click OK and close Settings

Usage:
  F8        — execute selected SQL text
  Ctrl+F8   — execute the entire file
  Results appear in the Run tool window at the bottom.

Tip for DataGrip users: the tool works alongside DataGrip's own query runner —
use DaSQL for cross-database queries that DataGrip's connection doesn't cover.
""")


if __name__ == '__main__':
    main()
