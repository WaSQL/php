#!/usr/bin/env python3
"""
Vim / Neovim DaSQL Configuration Installer

Appends DaSQL key mappings to your Vim or Neovim config file.
F8 executes the current line or visual selection against the matching
DaSQL database; Ctrl+F8 runs the entire file.

Use:
    Open a DOS/CMD prompt or terminal
    Change directory to where DaSQL lives (e.g.  cd c:/wasql/dasql)
    python vim_installer.py

Note: Restart Vim/Neovim after running this script.
"""

import os
import sys
import platform
import shutil
from pathlib import Path


# ---------------------------------------------------------------------------
# Config generators — build literal VimL / Lua strings with the correct paths
# ---------------------------------------------------------------------------

def buildVimLConfig(dasql_dir, python_exe):
    """Return a VimL config block ready to append to vimrc / init.vim."""
    # VimL accepts forward slashes on Windows
    dir_fwd  = Path(dasql_dir).as_posix()
    py_fwd   = Path(python_exe).as_posix() if os.path.isabs(str(python_exe)) else str(python_exe)

    return r"""
" ===== DaSQL Configuration =====
let g:dasql_dir = '""" + dir_fwd + r"""'
let g:dasql_py  = '""" + (Path(dasql_dir) / 'dasql.py').as_posix() + r"""'
let g:dasql_py3 = '""" + py_fwd + r"""'

function! s:DaSQLGetVisual()
    let [ls, cs] = [line("'<"), col("'<")]
    let [le, ce] = [line("'>"), col("'>")]
    let lines = getline(ls, le)
    if empty(lines) | return '' | endif
    if len(lines) == 1
        let lines[0] = lines[0][cs-1 : ce-1]
    else
        let lines[0]  = lines[0][cs-1:]
        let lines[-1] = lines[-1][:ce-1]
    endif
    return join(lines, "\n")
endfunction

function! s:DaSQLRun(query)
    if empty(a:query) | echo 'DaSQL: nothing to run' | return | endif
    let fname   = expand('%:t:r')
    let dirname = expand('%:h:t')
    let result  = system(g:dasql_py3 . ' '
                \ . shellescape(g:dasql_py) . ' '
                \ . shellescape(fname)      . ' '
                \ . shellescape(dirname)    . ' '
                \ . shellescape(a:query))
    let bufname = 'DaSQL-Output'
    let bnr = bufnr(bufname)
    if bnr > 0 && bufwinnr(bnr) > 0
        execute bufwinnr(bnr) . 'wincmd w'
    else
        execute 'botright 15new ' . bufname
        setlocal buftype=nofile bufhidden=wipe noswapfile nobuflisted
    endif
    setlocal modifiable
    silent %delete _
    call setline(1, split(result, "\n"))
    setlocal nomodifiable
    wincmd p
endfunction

nnoremap <silent> <F8>   :call <SID>DaSQLRun(getline('.'))<CR>
vnoremap <silent> <F8>   :<C-u>call <SID>DaSQLRun(<SID>DaSQLGetVisual())<CR>
nnoremap <silent> <C-F8> :call <SID>DaSQLRun(join(getline(1,'$'),"\n"))<CR>
" ===== End DaSQL Configuration =====
"""


def buildLuaConfig(dasql_dir, python_exe):
    """Return a Lua config block ready to append to init.lua."""
    dir_fwd  = Path(dasql_dir).as_posix()
    py_path  = Path(dasql_dir) / 'dasql.py'
    py_fwd   = py_path.as_posix()
    py_exe   = Path(python_exe).as_posix() if os.path.isabs(str(python_exe)) else str(python_exe)

    return """
-- ===== DaSQL Configuration =====
local _dasql_py  = '""" + py_fwd  + """'
local _dasql_py3 = '""" + py_exe  + """'

local function _dasql_run(query)
    if not query or query == '' then
        vim.notify('DaSQL: nothing to run', vim.log.levels.WARN)
        return
    end
    local fname   = vim.fn.expand('%:t:r')
    local dirname = vim.fn.expand('%:h:t')
    local result  = vim.fn.system(
        _dasql_py3
        .. ' ' .. vim.fn.shellescape(_dasql_py)
        .. ' ' .. vim.fn.shellescape(fname)
        .. ' ' .. vim.fn.shellescape(dirname)
        .. ' ' .. vim.fn.shellescape(query)
    )
    local lines   = vim.split(result, '\\n')
    local bufname = 'DaSQL-Output'
    local bnr     = vim.fn.bufnr(bufname)
    if bnr > 0 and vim.fn.bufwinnr(bnr) > 0 then
        vim.cmd(vim.fn.bufwinnr(bnr) .. 'wincmd w')
    else
        vim.cmd('botright 15new ' .. bufname)
        vim.bo.buftype   = 'nofile'
        vim.bo.buflisted = false
        vim.bo.swapfile  = false
        vim.bo.bufhidden = 'wipe'
    end
    vim.bo.modifiable = true
    vim.cmd('silent %delete _')
    vim.fn.setline(1, lines)
    vim.bo.modifiable = false
    vim.cmd('wincmd p')
end

vim.keymap.set('n', '<F8>',
    function() _dasql_run(vim.fn.getline('.')) end,
    { silent = true, desc = 'DaSQL: run current line' })

vim.keymap.set('v', '<F8>',
    function()
        local saved      = vim.fn.getreg('"')
        local saved_type = vim.fn.getregtype('"')
        vim.cmd('normal! y')
        local sel = vim.fn.getreg('"')
        vim.fn.setreg('"', saved, saved_type)
        _dasql_run(sel)
    end,
    { silent = true, desc = 'DaSQL: run selection' })

vim.keymap.set('n', '<C-F8>',
    function() _dasql_run(table.concat(vim.fn.getline(1, vim.fn.line('$')), '\\n')) end,
    { silent = true, desc = 'DaSQL: run entire file' })
-- ===== End DaSQL Configuration =====
"""


# ---------------------------------------------------------------------------
# Path discovery
# ---------------------------------------------------------------------------

def getConfigPaths():
    """Return candidate config file paths keyed by type."""
    system = platform.system().lower()
    home   = Path(os.path.expanduser('~'))

    if system == 'windows':
        local = Path(os.environ.get('LOCALAPPDATA', home / 'AppData' / 'Local'))
        vim_paths  = [home / '_vimrc', home / 'vimfiles' / 'vimrc']
        nvim_viml  = [local / 'nvim' / 'init.vim']
        nvim_lua   = [local / 'nvim' / 'init.lua']
    elif system == 'darwin':
        vim_paths  = [home / '.vimrc', home / '.vim' / 'vimrc']
        nvim_viml  = [home / '.config' / 'nvim' / 'init.vim']
        nvim_lua   = [home / '.config' / 'nvim' / 'init.lua']
    else:
        vim_paths  = [home / '.vimrc', home / '.vim' / 'vimrc']
        nvim_viml  = [home / '.config' / 'nvim' / 'init.vim']
        nvim_lua   = [home / '.config' / 'nvim' / 'init.lua']

    return {'vim': vim_paths, 'nvim_lua': nvim_lua, 'nvim_viml': nvim_viml}


def findPythonExecutable():
    exe = sys.executable
    if exe and os.path.exists(exe):
        return exe
    for name in ['python3', 'python']:
        found = shutil.which(name)
        if found:
            return found
    return 'python3'


def appendConfig(cfg_file, content, label):
    """Append DaSQL block to cfg_file unless it's already there."""
    cfg_file.parent.mkdir(parents=True, exist_ok=True)

    if cfg_file.exists():
        existing = cfg_file.read_text(encoding='utf-8')
        if 'DaSQL Configuration' in existing:
            print(f'  ✓ {label}: config already present in {cfg_file}')
            return True
        mode = 'a'
    else:
        mode = 'w'

    with open(cfg_file, mode, encoding='utf-8') as f:
        if mode == 'a':
            f.write('\n')
        f.write(content)

    verb = 'Updated' if mode == 'a' else 'Created'
    print(f'  ✓ {label}: {verb} {cfg_file}')
    return True


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

def main():
    print('Vim / Neovim DaSQL Installer')
    print('=' * 50)
    print(f'Operating System: {platform.system()} {platform.release()}')
    print(f'Python Version:   {sys.version}')
    print()

    dasql_dir = Path.cwd().resolve()

    if not (dasql_dir / 'dasql.py').exists():
        print(f'WARNING: dasql.py not found in {dasql_dir}')
    else:
        print(f'✓ DaSQL directory: {dasql_dir}')

    python_exe    = findPythonExecutable()
    vim_present   = bool(shutil.which('vim') or shutil.which('gvim'))
    nvim_present  = bool(shutil.which('nvim'))

    print(f'✓ Python: {python_exe}')

    if not vim_present and not nvim_present:
        print('\nERROR: Neither Vim nor Neovim found in PATH.')
        sys.exit(1)

    if vim_present:  print('✓ Vim detected')
    if nvim_present: print('✓ Neovim detected')

    paths      = getConfigPaths()
    viml_block = buildVimLConfig(dasql_dir, python_exe)
    lua_block  = buildLuaConfig(dasql_dir, python_exe)

    print()
    installed = False

    # --- Vim ---
    if vim_present:
        target = next((p for p in paths['vim'] if p.exists()), paths['vim'][0])
        appendConfig(target, viml_block, 'Vim')
        installed = True

    # --- Neovim: prefer init.lua if it exists, else init.vim, else create init.lua ---
    if nvim_present:
        lua_cfg  = paths['nvim_lua'][0]
        viml_cfg = paths['nvim_viml'][0]

        if lua_cfg.exists():
            appendConfig(lua_cfg, lua_block, 'Neovim (Lua)')
        elif viml_cfg.exists():
            appendConfig(viml_cfg, viml_block, 'Neovim (VimL)')
        else:
            appendConfig(lua_cfg, lua_block, 'Neovim (Lua)')
        installed = True

    if not installed:
        print('\nERROR: Could not install configuration.')
        sys.exit(1)

    print()
    print('=' * 50)
    print('✓ Installation complete!')
    print('=' * 50)
    print("""
Key bindings added:
  F8        — execute current line (normal mode)
  F8        — execute selection   (visual mode)
  Ctrl+F8   — execute entire file (normal mode)

Usage:
  1. Restart Vim / Neovim to load the new config
  2. Open a .sql file whose name matches a section in dasql.ini
     (e.g.  ddfa_dev.sql  matches the [ddfa_dev] section)
  3. Place the cursor on a query and press F8
     — or select multiple lines in visual mode and press F8
  4. Results appear in a DaSQL-Output split at the bottom

Tip: press Ctrl+W then W to jump between the output split and your SQL file.
""")


if __name__ == '__main__':
    main()
