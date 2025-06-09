#!/usr/bin/env python3
"""
Notepad++ NppExec Configuration Generator for DaSQL
Automatically detects Python installation and creates NppExec script
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
from pathlib import Path

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
    import json
    import re
    
    try:
        # Get the latest release info from GitHub API
        api_url = "https://api.github.com/repos/d0vgan/nppexec/releases/latest"
        with urllib.request.urlopen(api_url) as response:
            data = json.loads(response.read().decode())
        
        # Determine architecture
        import platform
        is_64bit = platform.machine().endswith('64')
        arch_pattern = 'x64' if is_64bit else 'x86'
        
        # Find the appropriate download URL
        for asset in data.get('assets', []):
            name = asset['name']
            if 'dll' in name.lower() and arch_pattern in name.lower() and name.endswith('.zip'):
                return asset['browser_download_url'], data['tag_name']
        
        # If no specific architecture found, try to find any dll zip
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
        print("Could not determine latest NppExec version, trying fallback...")
        # Fallback to known working version
        url = "https://github.com/d0vgan/nppexec/releases/download/v0.8.6/NppExec_0_8_6_dll_x64.zip"
        version = "v0.8.6"
    
    try:
        print(f"Downloading NppExec plugin {version}...")
        with tempfile.NamedTemporaryFile(delete=False, suffix='.zip') as tmp_file:
            urllib.request.urlretrieve(url, tmp_file.name)
            return tmp_file.name
    except Exception as e:
        print(f"Failed to download NppExec: {e}")
        # Try alternative URL for x86
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
        
        # Extract the plugin
        with zipfile.ZipFile(zip_path, 'r') as zip_ref:
            # Check if it's the new plugin structure (folder-based)
            namelist = zip_ref.namelist()
            if any("NppExec.dll" in name for name in namelist):
                # Extract to plugins directory
                zip_ref.extractall(plugins_dir)
                print("NppExec plugin installed successfully!")
                return True
    except Exception as e:
        print(f"Failed to install NppExec: {e}")
    finally:
        # Clean up
        if os.path.exists(zip_path):
            os.unlink(zip_path)
    
    return False

def find_python_executable():
    """Find Python executable path"""
    # First, try the current Python interpreter
    python_exe = sys.executable
    if os.path.exists(python_exe):
        return python_exe
    
    # Try common Python locations on Windows
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
    
    # Check if python is in PATH
    python_in_path = shutil.which("python")
    if python_in_path:
        possible_paths.insert(0, python_in_path)
    
    for path in possible_paths:
        if os.path.exists(path):
            return path
    
    return None

def save_nppexec_script(npp_dir, script_content):
    """Save NppExec script to Notepad++ config"""
    try:
        # Try to find NppExec config directory
        config_paths = [
            os.path.join(os.path.expandvars(r"%APPDATA%"), "Notepad++", "plugins", "config", "NppExec"),
            os.path.join(npp_dir, "plugins", "config", "NppExec"),
        ]
        
        for config_path in config_paths:
            if os.path.exists(config_path):
                script_file = os.path.join(config_path, "DaSQL.txt")
                with open(script_file, 'w') as f:
                    f.write(script_content)
                print(f"NppExec script saved to: {script_file}")
                return True
        
        # If config doesn't exist, create it
        config_path = config_paths[0]
        os.makedirs(config_path, exist_ok=True)
        script_file = os.path.join(config_path, "DaSQL.txt")
        with open(script_file, 'w') as f:
            f.write(script_content)
        print(f"NppExec script saved to: {script_file}")
        return True
    except Exception as e:
        print(f"Could not save script automatically: {e}")
        return False

def generate_nppexec_script(python_exe, dasql_dir):
    """Generate the NppExec script content"""
    # Convert paths to use forward slashes for NppExec
    python_exe = python_exe.replace('\\', '\\\\')
    dasql_dir = dasql_dir.replace('\\', '\\\\')
    
    script = f"""//--------------------------------------------------
// Notepad++ script to execute DaSQL commands
//  -- Python detected at: {python_exe}
//  -- DaSQL directory: {dasql_dir}
//  -- Install NppExec Plugin (Plugins->Plugins Admin, search for NppExec, Install)
//  -- Plugins->NppExec->No Internal Messages
//  -- F6, paste the code below in the window. Save as DaSQL. Click OK
//  -- Now you should be able to run queries by using CTRL+F6
//--------------------------------------------------
//----- Automatically generated paths ---------------
//--------------------------------------------------
set local Python_Exe = {python_exe}
set local DaSQL_Dir = {dasql_dir}
//--------------------------------------------------
//----- no need to change anything below -----------
//--------------------------------------------------
SCI_SENDMSG SCI_GETSELTEXT 0 @""
set local F = $(MSG_LPARAM)
if "$(F)" == "" GOTO NOSELECTION
GOTO SELECTION
:SELECTION
set sourcefile = $(SYS.TEMP)\\$(NAME_PART).source_tmp
sel_saveto "$(sourcefile)" :a
$(Python_Exe) $(DaSQL_Dir)\\dasql.py "$(NAME_PART)" "$(SYS.TEMP)" "$(sourcefile)"
cmd /c del /f /q "$(sourcefile)"
GOTO FINISH
:NOSELECTION
$(Python_Exe) $(DaSQL_Dir)\\dasql.py "$(NAME_PART)" "$(SYS.TEMP)" "$(CURRENT_LINESTR)"
GOTO FINISH
:FINISH"""
    
    return script

def main():
    print("DaSQL Notepad++ Integration Setup")
    print("=" * 50)
    
    # Check if running on Windows
    if sys.platform != "win32":
        print("ERROR: This script is designed for Windows only!")
        sys.exit(1)
    
    # Get current directory (where script is run from)
    dasql_dir = os.getcwd()
    
    # Find Python executable
    python_exe = find_python_executable()
    
    if not python_exe:
        print("ERROR: Could not find Python installation!")
        print("Please ensure Python is installed and in your PATH")
        sys.exit(1)
    
    print(f"✓ Python found: {python_exe}")
    
    # Check if dasql.py exists in current directory
    dasql_script = os.path.join(dasql_dir, "dasql.py")
    if not os.path.exists(dasql_script):
        print(f"WARNING: dasql.py not found in {dasql_dir}")
        print("Make sure you're running this script from the DaSQL directory")
    else:
        print(f"✓ DaSQL directory: {dasql_dir}")
    
    # Find Notepad++
    npp_dir = find_notepad_plus_plus()
    if not npp_dir:
        print("\n✗ Notepad++ not found!")
        print("Please install Notepad++ from https://notepad-plus-plus.org/")
    else:
        print(f"✓ Notepad++ found: {npp_dir}")
        
        # Check if NppExec is installed
        if not check_nppexec_installed(npp_dir):
            print("\n✗ NppExec plugin not found")
            
            # Ask user if they want to install it
            response = input("Would you like to install NppExec plugin automatically? (y/n): ")
            if response.lower() == 'y':
                zip_path = download_nppexec()
                if zip_path and install_nppexec(npp_dir, zip_path):
                    print("✓ NppExec plugin installed!")
                    print("NOTE: You may need to restart Notepad++ for the plugin to be recognized.")
                else:
                    print("Failed to install NppExec automatically.")
                    print("Please install it manually via Plugins -> Plugins Admin in Notepad++")
        else:
            print("✓ NppExec plugin is already installed")
    
    # Generate the script
    nppexec_script = generate_nppexec_script(python_exe, dasql_dir)
    
    # Save to file
    output_file = "nppexec_dasql_config.txt"
    with open(output_file, 'w') as f:
        f.write(nppexec_script)
    
    print(f"\n✓ Configuration saved to: {output_file}")
    
    # Try to save directly to NppExec config
    if npp_dir and check_nppexec_installed(npp_dir):
        if save_nppexec_script(npp_dir, nppexec_script):
            print("\n✓ Script automatically saved to NppExec config!")
            print("\nFinal steps:")
            print("1. Open/Restart Notepad++")
            print("2. Go to Plugins -> NppExec -> Execute... (or press F6)")
            print("3. Select 'DaSQL' from the dropdown (if available)")
            print("4. Or paste the script from nppexec_dasql_config.txt")
            print("5. Save as 'DaSQL' and click OK")
            print("6. Use Ctrl+F6 to run DaSQL queries")
        else:
            print("\nManual configuration required:")
            print("1. Open Notepad++")
            print("2. Press F6 to open Execute dialog")
            print(f"3. Copy contents of {output_file} and paste")
            print("4. Save as 'DaSQL' and click OK")
            print("5. Use Ctrl+F6 to run DaSQL queries")
    else:
        print("\nManual steps required:")
        print("1. Install Notepad++ if not already installed")
        print("2. Install NppExec plugin (Plugins -> Plugins Admin)")
        print("3. Press F6 and paste the configuration")
        print("4. Save as 'DaSQL'")
        print("5. Use Ctrl+F6 to run DaSQL queries")

if __name__ == "__main__":
    main()