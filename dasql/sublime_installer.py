#!/usr/bin/env python3
"""
Sublime Text Build Files Installer

This script installs custom_exec.py.sample and DaSQL.sublime-build.sample
to the appropriate Sublime Text directories across Linux, macOS, and Windows.

Use:
    Open us a DOS/CMD prompt or Terminal
    Change directory to the place you have DaSQL (ie   cd c:/wasql/dasql)
    python sublime_installer.py

Note: This script will also update the files if you already have them installed.

"""

import os
import sys
import shutil
import platform
import json
import re
from pathlib import Path

def updateKeyBindings(user_packages_dir):
    """Update or create user key bindings to set F8 for build."""
    keybindings_file = user_packages_dir / "Default.sublime-keymap"
    
    # Key binding to add
    f8_binding = {"keys": ["f8"], "command": "build"}
    
    try:
        # Read existing bindings if file exists
        if keybindings_file.exists():
            with open(keybindings_file, 'r', encoding='utf-8') as f:
                content = f.read().strip()
                if content:
                    bindings = json.loads(content)
                else:
                    bindings = []
        else:
            bindings = []
        
        # Check if F8 binding already exists
        f8_exists = any(binding.get("keys") == ["f8"] for binding in bindings)
        
        if not f8_exists:
            bindings.append(f8_binding)
            
            # Write updated bindings
            with open(keybindings_file, 'w', encoding='utf-8') as f:
                json.dump(bindings, f, indent=4)
            
            return True, f"Added F8 build key binding to: {keybindings_file}"
        else:
            return True, "F8 key binding already exists"
            
    except Exception as e:
        return False, f"Failed to update key bindings: {e}"

def getSublimePaths():
    """Get potential Sublime Text installation and user data paths for different OS."""
    system = platform.system().lower()
    
    if system == "windows":
        # Windows paths
        program_files = os.environ.get('PROGRAMFILES', r'C:\Program Files')
        program_files_x86 = os.environ.get('PROGRAMFILES(X86)', r'C:\Program Files (x86)')
        appdata = os.environ.get('APPDATA', '')
        
        install_paths = [
            Path(program_files) / "Sublime Text",
            Path(program_files) / "Sublime Text 3",
            Path(program_files) / "Sublime Text 4",
            Path(program_files_x86) / "Sublime Text",
            Path(program_files_x86) / "Sublime Text 3",
            Path(program_files_x86) / "Sublime Text 4",
        ]
        
        user_paths = [
            Path(appdata) / "Sublime Text" / "Packages" / "User",
            Path(appdata) / "Sublime Text 3" / "Packages" / "User",
            Path(appdata) / "Sublime Text 4" / "Packages" / "User",
        ]
        
    elif system == "darwin":  # macOS
        install_paths = [
            Path("/Applications/Sublime Text.app"),
            Path("/Applications/Sublime Text 3.app"),
            Path("/Applications/Sublime Text 4.app"),
            Path(os.path.expanduser("~/Applications/Sublime Text.app")),
            Path(os.path.expanduser("~/Applications/Sublime Text 3.app")),
            Path(os.path.expanduser("~/Applications/Sublime Text 4.app")),
        ]
        
        user_paths = [
            Path(os.path.expanduser("~/Library/Application Support/Sublime Text/Packages/User")),
            Path(os.path.expanduser("~/Library/Application Support/Sublime Text 3/Packages/User")),
            Path(os.path.expanduser("~/Library/Application Support/Sublime Text 4/Packages/User")),
        ]
        
    else:  # Linux and other Unix-like systems
        install_paths = [
            Path("/opt/sublime_text"),
            Path("/usr/bin/sublime_text"),
            Path("/usr/local/bin/sublime_text"),
            Path(os.path.expanduser("~/sublime_text")),
        ]
        
        user_paths = [
            Path(os.path.expanduser("~/.config/sublime-text/Packages/User")),
            Path(os.path.expanduser("~/.config/sublime-text-3/Packages/User")),
            Path(os.path.expanduser("~/.config/sublime-text-4/Packages/User")),
        ]
    
    return install_paths, user_paths


def findSublimeInstallation():
    """Find Sublime Text installation directory."""
    install_paths, _ = getSublimePaths()
    
    for path in install_paths:
        if path.exists():
            # Additional validation for different OS
            system = platform.system().lower()
            if system == "darwin":
                # Check for the actual executable in the app bundle
                if (path / "Contents" / "MacOS" / "Sublime Text").exists():
                    return path
                # Also check for alternative executable names
                elif (path / "Contents" / "MacOS" / "sublime_text").exists():
                    return path
            elif system == "windows":
                if (path / "sublime_text.exe").exists():
                    return path
            else:  # Linux
                if (path / "sublime_text").exists() or path.name == "sublime_text":
                    return path
    
    return None


def findUserPackagesDir():
    """Find Sublime Text user packages directory."""
    _, user_paths = getSublimePaths()
    
    for path in user_paths:
        if path.exists():
            return path
    
    return None


def createUserPackagesDir():
    """Create user packages directory if it doesn't exist."""
    _, user_paths = getSublimePaths()
    
    # Try to create the most likely path for each OS
    system = platform.system().lower()
    if system == "windows":
        target_path = Path(os.environ.get('APPDATA', '')) / "Sublime Text" / "Packages" / "User"
    elif system == "darwin":
        target_path = Path(os.path.expanduser("~/Library/Application Support/Sublime Text/Packages/User"))
    else:
        target_path = Path(os.path.expanduser("~/.config/sublime-text/Packages/User"))
    
    try:
        target_path.mkdir(parents=True, exist_ok=True)
        return target_path
    except Exception as e:
        print(f"Failed to create user packages directory: {e}")
        return None


def updateWorkingDirInBuildFile(build_file_path, working_dir):
    """Update the working_dir in the Sublime build file."""
    try:
        with open(build_file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Convert paths for JSON compatibility
        if platform.system().lower() == "windows":
            # Use forward slashes in JSON (works in Sublime Text on Windows)
            json_safe_path = str(working_dir).replace('\\', '/')
        else:
            # For Unix-like systems (macOS, Linux), use the path as-is
            json_safe_path = str(working_dir)
        
        # Try to parse as JSON first (if it's a valid JSON file)
        try:
            build_config = json.loads(content)
            build_config['working_dir'] = json_safe_path
            updated_content = json.dumps(build_config, indent=4)
        except json.JSONDecodeError:
            # If not valid JSON, use regex to replace working_dir
            # Match various formats: "working_dir": "path", "working_dir":"path", etc.
            pattern = r'"working_dir"\s*:\s*"[^"]*"'
            replacement = f'"working_dir": "{json_safe_path}"'
            
            if re.search(pattern, content):
                updated_content = re.sub(pattern, replacement, content)
            else:
                # If working_dir doesn't exist, add it
                # Look for the closing brace and add working_dir before it
                if content.strip().endswith('}'):
                    # Remove the closing brace, add working_dir with comma if needed, then add closing brace
                    content_without_closing = content.rstrip().rstrip('}').rstrip()
                    
                    # Check if we need a comma (if there's existing content)
                    if '"' in content_without_closing and content_without_closing.count('"') > 0:
                        # Add comma and new working_dir
                        updated_content = content_without_closing + f',\n    "working_dir": "{json_safe_path}"\n}}'
                    else:
                        # First entry, no comma needed
                        updated_content = f'{{\n    "working_dir": "{json_safe_path}"\n}}'
                else:
                    # Fallback: create a simple JSON structure
                    updated_content = f'{{\n    "working_dir": "{json_safe_path}"\n}}'
        
        with open(build_file_path, 'w', encoding='utf-8') as f:
            f.write(updated_content)
        
        return True, f"Updated working_dir to: {json_safe_path}"
        
    except Exception as e:
        return False, f"Failed to update working_dir: {e}"


def installFiles(source_dir=None):
    """Install the sample files to Sublime Text directories."""
    if source_dir is None:
        source_dir = Path.cwd()
    else:
        source_dir = Path(source_dir)
    
    # Store the working directory (where script was run from)
    working_directory = Path.cwd().resolve()
    
    # Check if source files exist
    custom_exec_sample = source_dir / "custom_exec.py.sample"
    dasql_build_sample = source_dir / "DaSQL.sublime-build.sample"
    
    if not custom_exec_sample.exists():
        return False, f"Source file not found: {custom_exec_sample}"
    
    if not dasql_build_sample.exists():
        return False, f"Source file not found: {dasql_build_sample}"
    
    # Check if Sublime Text is installed
    sublime_install = findSublimeInstallation()
    if not sublime_install:
        return False, "Sublime Text installation not found. Please ensure Sublime Text is installed."
    
    print(f"Found Sublime Text installation at: {sublime_install}")
    
    # Find or create user packages directory
    user_packages_dir = findUserPackagesDir()
    if not user_packages_dir:
        print("User packages directory not found. Attempting to create it...")
        user_packages_dir = createUserPackagesDir()
        if not user_packages_dir:
            return False, "Could not find or create Sublime Text user packages directory."
    
    print(f"Using user packages directory: {user_packages_dir}")
    print(f"Setting working_dir to: {working_directory}")
    
    try:
        # Install custom_exec.py to user packages directory
        custom_exec_dest = user_packages_dir / "custom_exec.py"
        if custom_exec_dest.exists():
            print(f"Replacing existing file: {custom_exec_dest}")
        shutil.copy2(custom_exec_sample, custom_exec_dest)
        print(f"Installed: {custom_exec_dest}")
        
        # Install DaSQL.sublime-build to user packages directory
        dasql_build_dest = user_packages_dir / "DaSQL.sublime-build"
        if dasql_build_dest.exists():
            print(f"Replacing existing file: {dasql_build_dest}")
        shutil.copy2(dasql_build_sample, dasql_build_dest)
        print(f"Installed: {dasql_build_dest}")

        # Update key bindings for F8
        key_success, key_message = updateKeyBindings(user_packages_dir)
        if key_success:
            print(f" {key_message}")
        else:
            print(f"  Warning: {key_message}")
        
        # Update the working_dir in the build file
        success, message = updateWorkingDirInBuildFile(dasql_build_dest, working_directory)
        if success:
            print(f" {message}")
        else:
            print(f"  Warning: {message}")
        
        return True, "Files installed successfully!"
        
    except PermissionError as e:
        return False, f"Permission denied. Try running as administrator/sudo: {e}"
    except Exception as e:
        return False, f"Installation failed: {e}"


def main():
    """Main function."""
    print("Sublime Text Build Files Installer")
    print("=" * 40)
    print(f"Operating System: {platform.system()} {platform.release()}")
    print(f"Python Version: {sys.version}")
    print()
    
    # Allow specifying source directory as command line argument
    source_dir = sys.argv[1] if len(sys.argv) > 1 else None
    
    success, message = installFiles(source_dir)
    
    if success:
        print(" SUCCESS:", message)
        print("\nNext steps:")
        print("1. Restart Sublime Text to load the new build system")
        print("2. Use Tools > Build System > DaSQL to select the new build system")
        print("3. The custom_exec.py file will be automatically used by the build system")
    else:
        print(" FAILED:", message)
        print("\nTroubleshooting tips:")
        print("- Ensure Sublime Text is installed")
        print("- Check that the sample files exist in the current directory")
        if platform.system().lower() != "windows":
            print("- Try running with sudo privileges: sudo python sublime_installer.py")
        else:
            print("- Try running with administrator privileges")
        print("- Verify Sublime Text user directory permissions")
        return 1
    
    return 0


if __name__ == "__main__":
    sys.exit(main())