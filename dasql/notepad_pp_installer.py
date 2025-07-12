#!/usr/bin/env python3
"""
Enhanced Notepad++ DaSQL Auto-Installer
Uses the most reliable method for F8 shortcut binding
"""

import os
import sys
import shutil
import winreg
import urllib.request
import zipfile
import tempfile
import json
import platform
import subprocess
import xml.etree.ElementTree as ET
from pathlib import Path
import time

def find_notepad_plus_plus():
    """Find Notepad++ installation directory"""
    possible_paths = []
    
    # Check registry for installation path
    try:
        with winreg.OpenKey(winreg.HKEY_LOCAL_MACHINE, r"SOFTWARE\Notepad++") as key:
            install_dir = winreg.QueryValueEx(key, "")[0]
            if os.path.exists(install_dir):
                possible_paths.append(install_dir)
    except:
        pass
    
    # Check common installation paths
    common_paths = [
        r"C:\Program Files\Notepad++",
        r"C:\Program Files (x86)\Notepad++",
        os.path.expandvars(r"%LOCALAPPDATA%\Notepad++"),
        os.path.expandvars(r"%APPDATA%\Notepad++"),
    ]
    
    for path in common_paths:
        if os.path.exists(path) and os.path.exists(os.path.join(path, "notepad++.exe")):
            possible_paths.append(path)
    
    # Check if notepad++ is in PATH
    npp_in_path = shutil.which("notepad++")
    if npp_in_path:
        possible_paths.append(os.path.dirname(npp_in_path))
    
    # Return first valid path
    for path in possible_paths:
        if os.path.exists(os.path.join(path, "notepad++.exe")):
            return path
    
    return None

def check_nppexec_installed(npp_dir):
    """Check if NppExec plugin is already installed"""
    plugin_paths = [
        os.path.join(npp_dir, "plugins", "NppExec", "NppExec.dll"),
        os.path.join(npp_dir, "plugins", "NppExec.dll"),
    ]
    
    for path in plugin_paths:
        if os.path.exists(path):
            return True
    return False

def get_latest_nppexec_url():
    """Get the latest NppExec release URL from GitHub"""
    try:
        api_url = "https://api.github.com/repos/d0vgan/nppexec/releases/latest"
        with urllib.request.urlopen(api_url) as response:
            data = json.loads(response.read().decode())
        
        is_64bit = platform.machine().endswith('64')
        arch_pattern = 'x64' if is_64bit else 'x86'
        
        for asset in data.get('assets', []):
            name = asset['name']
            if 'dll' in name.lower() and arch_pattern in name.lower() and name.endswith('.zip'):
                return asset['browser_download_url'], data['tag_name']
        
        for asset in data.get('assets', []):
            name = asset['name']
            if 'dll' in name.lower() and name.endswith('.zip'):
                return asset['browser_download_url'], data['tag_name']
                
    except Exception as e:
        print(f"Failed to get latest release info: {e}")
    
    return None, None

def download_nppexec():
    """Download NppExec plugin"""
    url, version = get_latest_nppexec_url()
    
    if not url:
        print("Using fallback NppExec version...")
        url = "https://github.com/d0vgan/nppexec/releases/download/v0.8.6/NppExec_0_8_6_dll_x64.zip"
        version = "v0.8.6"
    
    try:
        print(f"Downloading NppExec plugin {version}...")
        with tempfile.NamedTemporaryFile(delete=False, suffix='.zip') as tmp_file:
            urllib.request.urlretrieve(url, tmp_file.name)
            return tmp_file.name
    except Exception as e:
        print(f"Failed to download NppExec: {e}")
        try:
            url = url.replace('x64', 'x86')
            with tempfile.NamedTemporaryFile(delete=False, suffix='.zip') as tmp_file:
                urllib.request.urlretrieve(url, tmp_file.name)
                return tmp_file.name
        except:
            return None

def install_nppexec(npp_dir, zip_path):
    """Install NppExec plugin to Notepad++"""
    try:
        plugins_dir = os.path.join(npp_dir, "plugins")
        if not os.path.exists(plugins_dir):
            os.makedirs(plugins_dir)
        
        with zipfile.ZipFile(zip_path, 'r') as zip_ref:
            zip_ref.extractall(plugins_dir)
            print("✓ NppExec plugin installed successfully!")
            return True
    except Exception as e:
        print(f"Failed to install NppExec: {e}")
        return False
    finally:
        if os.path.exists(zip_path):
            os.unlink(zip_path)

def find_python_executable():
    """Find Python executable path"""
    python_exe = sys.executable
    if os.path.exists(python_exe):
        return python_exe
    
    python_in_path = shutil.which("python")
    if python_in_path:
        return python_in_path
    
    possible_paths = [
        r"C:\Python311\python.exe",
        r"C:\Python310\python.exe",
        r"C:\Python39\python.exe",
        r"C:\Python38\python.exe",
        r"C:\Program Files\Python311\python.exe",
        r"C:\Program Files\Python310\python.exe",
        r"C:\Program Files\Python39\python.exe",
        r"C:\Program Files\Python38\python.exe",
        r"C:\Program Files (x86)\Python311\python.exe",
        r"C:\Program Files (x86)\Python310\python.exe",
        r"C:\Program Files (x86)\Python39\python.exe",
        r"C:\Program Files (x86)\Python38\python.exe",
    ]
    
    for path in possible_paths:
        if os.path.exists(path):
            return path
    
    return None

def get_nppexec_config_path():
    """Get NppExec configuration directory"""
    appdata_path = os.path.expandvars(r"%APPDATA%\Notepad++\plugins\config\NppExec")
    if not os.path.exists(appdata_path):
        os.makedirs(appdata_path, exist_ok=True)
    return appdata_path

def save_nppexec_script(config_path, script_content):
    """Save NppExec script to config directory"""
    try:
        script_file = os.path.join(config_path, "DaSQL.txt")
        with open(script_file, 'w') as f:
            f.write(script_content)
        print(f"✓ NppExec script saved to: {script_file}")
        return True
    except Exception as e:
        print(f"Could not save script: {e}")
        return False

def configure_nppexec_menu_item(config_path):
    """Configure NppExec menu item for DaSQL script"""
    try:
        # Create nppexec_saved.txt file to store menu items
        saved_file = os.path.join(config_path, "nppexec_saved.txt")
        
        # Read existing content if file exists
        existing_content = ""
        if os.path.exists(saved_file):
            with open(saved_file, 'r') as f:
                existing_content = f.read()
        
        # Check if DaSQL menu item already exists
        if "DaSQL" not in existing_content:
            # Add DaSQL menu item entry
            menu_item = """
[UserMenu]
DaSQL :: DaSQL
"""
            updated_content = existing_content + menu_item
            
            with open(saved_file, 'w') as f:
                f.write(updated_content)
            
            print("✓ DaSQL menu item configured in NppExec")
            return True
        else:
            print("✓ DaSQL menu item already exists")
            return True
            
    except Exception as e:
        print(f"Could not configure NppExec menu item: {e}")
        return False

def generate_nppexec_script(python_exe, dasql_dir):
    """Generate the NppExec script content"""
    python_exe = python_exe.replace('\\', '\\\\')
    dasql_dir = dasql_dir.replace('\\', '\\\\')
    
    script = f"""//--------------------------------------------------
// DaSQL execution script - Press F8 to run
//--------------------------------------------------
set local Python_Exe = {python_exe}
set local DaSQL_Dir = {dasql_dir}
//--------------------------------------------------
SCI_SENDMSG SCI_GETSELECTION
set local sel_start = $(MSG_WPARAM)
set local sel_end = $(MSG_LPARAM)
if $(sel_start) == $(sel_end) GOTO NOSELECTION
GOTO SELECTION
:SELECTION
SCI_SENDMSG SCI_GETSELTEXT 0 @""
set local F = $(MSG_LPARAM)
set sourcefile = $(SYS.TEMP)\\$(NAME_PART).source_tmp
sel_saveto "$(sourcefile)" :a
$(Python_Exe) "$(DaSQL_Dir)\\dasql.py" "$(NAME_PART)" "$(SYS.TEMP)" "$(sourcefile)"
cmd /c del /f /q "$(sourcefile)"
GOTO FINISH
:NOSELECTION
$(Python_Exe) "$(DaSQL_Dir)\\dasql.py" "$(NAME_PART)" "$(SYS.TEMP)" "$(CURRENT_LINESTR)"
GOTO FINISH
:FINISH"""
    
    return script

def close_notepad_plus_plus():
    """Close Notepad++ if it's running"""
    try:
        subprocess.run(['taskkill', '/F', '/IM', 'notepad++.exe'], 
                      capture_output=True, check=False)
        time.sleep(1)
        return True
    except:
        return False

def main():
    print("DaSQL Notepad++ Auto-Installer")
    print("=" * 50)
    
    if sys.platform != "win32":
        print("ERROR: This script requires Windows!")
        sys.exit(1)
    
    # Get current directory
    dasql_dir = os.getcwd()
    
    # Find Python
    python_exe = find_python_executable()
    if not python_exe:
        print("ERROR: Python not found!")
        sys.exit(1)
    
    print(f"✓ Python: {python_exe}")
    
    # Check for dasql.py
    if not os.path.exists(os.path.join(dasql_dir, "dasql.py")):
        print(f"WARNING: dasql.py not found in {dasql_dir}")
    else:
        print(f"✓ DaSQL directory: {dasql_dir}")
    
    # Find Notepad++
    npp_dir = find_notepad_plus_plus()
    if not npp_dir:
        print("ERROR: Notepad++ not found!")
        print("Please install Notepad++ first.")
        sys.exit(1)
    
    print(f"✓ Notepad++: {npp_dir}")
    
    # Close Notepad++ if running
    print("Closing Notepad++ if running...")
    close_notepad_plus_plus()
    
    # Install NppExec if needed
    if not check_nppexec_installed(npp_dir):
        print("Installing NppExec plugin...")
        zip_path = download_nppexec()
        if zip_path and install_nppexec(npp_dir, zip_path):
            print("✓ NppExec installed!")
        else:
            print("ERROR: Failed to install NppExec!")
            sys.exit(1)
    else:
        print("✓ NppExec already installed")
    
    # Generate and save script
    nppexec_script = generate_nppexec_script(python_exe, dasql_dir)
    config_path = get_nppexec_config_path()
    
    if not save_nppexec_script(config_path, nppexec_script):
        print("ERROR: Could not save NppExec script!")
        sys.exit(1)
    
    # Configure menu item
    configure_nppexec_menu_item(config_path)
    
    print("\n" + "=" * 50)
    print("✓ Installation complete!")
    print("=" * 50)
    print("IMPORTANT: FOLLOW THESE TO COMPLETE - This sets F8 as the key to run:")
    print("1. Open Notepad++")
    print("2. Go to Plugins → NppExec → Advanced Options")
    print("3. In the 'Menu Items' section:")
    print("   - Item name: DaSQL")
    print("   - Associated script: DaSQL")
    print("   - Click 'Add/Modify'")
    print("   - Click 'OK'")
    print("4. Restart Notepad++")
    print("5. Go to Settings → Shortcut Mapper → Plugin commands")
    print("6. Find 'DaSQL' in the list")
    print("7. Double-click and set shortcut to F8")
    print("8. It will turn red if there are conficts. If so, look for F8 elsewhere and change to a different key")
    print("9. Now F8 will execute DaSQL queries!")
    print("\nUsage:")
    print("- Select text and press F8 to run selection")
    print("- Place cursor on line and press F8 to run that line")
    print("\nNote: Due to NppExec plugin limitations, the shortcut")
    print("must be configured manually through the GUI.")

if __name__ == "__main__":
    main()