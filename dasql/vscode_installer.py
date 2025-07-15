#!/usr/bin/env python3
"""
Visual Studio Code DaSQL Configuration Installer

This script installs DaSQL configuration files to the appropriate Visual Studio Code
directories across Linux, macOS, and Windows.

Use:
    Open a DOS/CMD prompt or terminal
    Change directory to the place you have DaSQL (ie   cd c:/wasql/dasql)
    python vscode_installer.py

Note: This script will also update the files if you already have them installed.

"""

import os
import sys
import shutil
import platform
import json
import re
from pathlib import Path


def getVSCodePaths():
    """Get potential VS Code installation and user data paths for different OS."""
    system = platform.system().lower()
    
    if system == "windows":
        # Windows paths
        program_files = os.environ.get('PROGRAMFILES', r'C:\Program Files')
        program_files_x86 = os.environ.get('PROGRAMFILES(X86)', r'C:\Program Files (x86)')
        appdata = os.environ.get('APPDATA', '')
        localappdata = os.environ.get('LOCALAPPDATA', '')
        
        install_paths = [
            Path(program_files) / "Microsoft VS Code",
            Path(program_files_x86) / "Microsoft VS Code",
            Path(localappdata) / "Programs" / "Microsoft VS Code",
        ]
        
        user_paths = [
            Path(appdata) / "Code" / "User",
            Path(os.path.expanduser("~")) / ".vscode",
        ]
        
    elif system == "darwin":  # macOS
        install_paths = [
            Path("/Applications/Visual Studio Code.app"),
            Path(os.path.expanduser("~/Applications/Visual Studio Code.app")),
        ]
        
        user_paths = [
            Path(os.path.expanduser("~/Library/Application Support/Code/User")),
            Path(os.path.expanduser("~/.vscode")),
        ]
        
    else:  # Linux and other Unix-like systems
        install_paths = [
            Path("/usr/share/code"),
            Path("/opt/visual-studio-code"),
            Path("/usr/bin/code"),
            Path("/snap/code"),
            Path(os.path.expanduser("~/code")),
        ]
        
        user_paths = [
            Path(os.path.expanduser("~/.config/Code/User")),
            Path(os.path.expanduser("~/.vscode")),
        ]
    
    return install_paths, user_paths


def findVSCodeInstallation():
    """Find VS Code installation directory."""
    install_paths, _ = getVSCodePaths()
    
    for path in install_paths:
        if path.exists():
            # Additional validation for different OS
            system = platform.system().lower()
            if system == "darwin":
                # Check for the actual executable in the app bundle
                if (path / "Contents" / "MacOS" / "Electron").exists() or (path / "Contents" / "Resources" / "app" / "bin" / "code").exists():
                    return path
            elif system == "windows":
                if (path / "Code.exe").exists():
                    return path
            else:  # Linux
                # Check for various possible executables
                if (path / "code").exists() or (path / "bin" / "code").exists() or path.name == "code":
                    return path
    
    return None


def findUserConfigDir():
    """Find VS Code user configuration directory."""
    _, user_paths = getVSCodePaths()
    
    for path in user_paths:
        if path.exists():
            return path
    
    return None


def createUserConfigDir():
    """Create user configuration directory if it doesn't exist."""
    _, user_paths = getVSCodePaths()
    
    # Try to create the most likely path for each OS
    system = platform.system().lower()
    if system == "windows":
        target_path = Path(os.environ.get('APPDATA', '')) / "Code" / "User"
    elif system == "darwin":
        target_path = Path(os.path.expanduser("~/Library/Application Support/Code/User"))
    else:
        target_path = Path(os.path.expanduser("~/.config/Code/User"))
    
    try:
        target_path.mkdir(parents=True, exist_ok=True)
        return target_path
    except Exception as e:
        print(f"Failed to create user configuration directory: {e}")
        return None


def createTasksJson(working_dir):
    """Create tasks.json configuration for DaSQL."""
    # Convert Windows paths to use forward slashes for JSON
    if platform.system().lower() == "windows":
        json_safe_path = str(working_dir).replace('\\', '/')
    else:
        json_safe_path = str(working_dir)
    
    tasks_config = {
        "version": "2.0.0",
        "tasks": [
            {
                "label": "DaSQL: Execute Selection",
                "type": "shell",
                "command": "python3",
                "args": [
                    "dasql.py",
                    "${file}",
                    "${fileDirname}",
                    "${selectedText}"
                ],
                "options": {
                    "cwd": json_safe_path
                },
                "group": {
                    "kind": "build",
                    "isDefault": True
                },
                "presentation": {
                    "echo": True,
                    "reveal": "always",
                    "focus": False,
                    "panel": "shared",
                    "showReuseMessage": True,
                    "clear": False
                },
                "problemMatcher": []
            },
            {
                "label": "DaSQL: Execute Current Line",
                "type": "shell",
                "command": "python3",
                "args": [
                    "dasql.py",
                    "${file}",
                    "${fileDirname}",
                    "${lineText}"
                ],
                "options": {
                    "cwd": json_safe_path
                },
                "group": "build",
                "presentation": {
                    "echo": True,
                    "reveal": "always",
                    "focus": False,
                    "panel": "shared",
                    "showReuseMessage": True,
                    "clear": False
                },
                "problemMatcher": []
            },
            {
                "label": "DaSQL: Execute Entire File",
                "type": "shell",
                "command": "python3",
                "args": [
                    "dasql.py",
                    "${file}",
                    "${fileDirname}",
                    "${file}"
                ],
                "options": {
                    "cwd": json_safe_path
                },
                "group": "build",
                "presentation": {
                    "echo": True,
                    "reveal": "always",
                    "focus": False,
                    "panel": "shared",
                    "showReuseMessage": True,
                    "clear": False
                },
                "problemMatcher": []
            }
        ]
    }
    
    return tasks_config


def createKeybindingsJson():
    """Create keybindings.json configuration for DaSQL."""
    keybindings_config = [
        {
            "key": "ctrl+b",
            "command": "workbench.action.tasks.runTask",
            "args": "DaSQL: Execute Selection",
            "when": "editorTextFocus"
        },
        {
            "key": "ctrl+shift+b",
            "command": "workbench.action.tasks.runTask",
            "args": "DaSQL: Execute Current Line",
            "when": "editorTextFocus"
        },
        {
            "key": "ctrl+alt+b",
            "command": "workbench.action.tasks.runTask",
            "args": "DaSQL: Execute Entire File",
            "when": "editorTextFocus"
        }
    ]
    
    return keybindings_config


def updateTasksJson(tasks_file_path, working_dir):
    """Update or create tasks.json with DaSQL configuration."""
    try:
        # Check if tasks.json already exists
        if tasks_file_path.exists():
            with open(tasks_file_path, 'r', encoding='utf-8') as f:
                existing_tasks = json.load(f)
        else:
            existing_tasks = {"version": "2.0.0", "tasks": []}
        
        # Create new DaSQL tasks
        new_tasks_config = createTasksJson(working_dir)
        
        # Remove any existing DaSQL tasks
        existing_tasks["tasks"] = [task for task in existing_tasks.get("tasks", []) 
                                 if not task.get("label", "").startswith("DaSQL:")]
        
        # Add new DaSQL tasks
        existing_tasks["tasks"].extend(new_tasks_config["tasks"])
        
        # Write updated tasks.json
        with open(tasks_file_path, 'w', encoding='utf-8') as f:
            json.dump(existing_tasks, f, indent=4)
        
        return True, f"Updated tasks.json with working directory: {working_dir}"
        
    except Exception as e:
        return False, f"Failed to update tasks.json: {e}"


def updateKeybindingsJson(keybindings_file_path):
    """Update or create keybindings.json with DaSQL key bindings."""
    try:
        # Check if keybindings.json already exists
        if keybindings_file_path.exists():
            with open(keybindings_file_path, 'r', encoding='utf-8') as f:
                existing_keybindings = json.load(f)
        else:
            existing_keybindings = []
        
        # Create new DaSQL keybindings
        new_keybindings = createKeybindingsJson()
        
        # Remove any existing DaSQL keybindings
        existing_keybindings = [kb for kb in existing_keybindings 
                              if not (kb.get("args", "").startswith("DaSQL:") if isinstance(kb.get("args"), str) else False)]
        
        # Add new DaSQL keybindings
        existing_keybindings.extend(new_keybindings)
        
        # Write updated keybindings.json
        with open(keybindings_file_path, 'w', encoding='utf-8') as f:
            json.dump(existing_keybindings, f, indent=4)
        
        return True, "Updated keybindings.json with DaSQL shortcuts"
        
    except Exception as e:
        return False, f"Failed to update keybindings.json: {e}"


def installFiles(source_dir=None):
    """Install the configuration files to VS Code directories."""
    if source_dir is None:
        source_dir = Path.cwd()
    else:
        source_dir = Path(source_dir)
    
    # Store the working directory (where script was run from)
    working_directory = Path.cwd().resolve()
    
    # Check if VS Code is installed
    vscode_install = findVSCodeInstallation()
    if not vscode_install:
        return False, "Visual Studio Code installation not found. Please ensure VS Code is installed."
    
    print(f"Found VS Code installation at: {vscode_install}")
    
    # Find or create user configuration directory
    user_config_dir = findUserConfigDir()
    if not user_config_dir:
        print("User configuration directory not found. Attempting to create it...")
        user_config_dir = createUserConfigDir()
        if not user_config_dir:
            return False, "Could not find or create VS Code user configuration directory."
    
    print(f"Using user configuration directory: {user_config_dir}")
    print(f"Setting working directory to: {working_directory}")
    
    try:
        # Update tasks.json
        tasks_file = user_config_dir / "tasks.json"
        success, message = updateTasksJson(tasks_file, working_directory)
        if success:
            print(f"✅ {message}")
            print(f"Tasks file: {tasks_file}")
        else:
            print(f"⚠️  Warning: {message}")
        
        # Update keybindings.json
        keybindings_file = user_config_dir / "keybindings.json"
        success, message = updateKeybindingsJson(keybindings_file)
        if success:
            print(f"✅ {message}")
            print(f"Keybindings file: {keybindings_file}")
        else:
            print(f"⚠️  Warning: {message}")
        
        return True, "VS Code configuration files installed successfully!"
        
    except PermissionError as e:
        return False, f"Permission denied. Try running as administrator/sudo: {e}"
    except Exception as e:
        return False, f"Installation failed: {e}"


def main():
    """Main function."""
    print("Visual Studio Code DaSQL Configuration Installer")
    print("=" * 50)
    print(f"Operating System: {platform.system()} {platform.release()}")
    print(f"Python Version: {sys.version}")
    print()
    
    # Allow specifying source directory as command line argument
    source_dir = sys.argv[1] if len(sys.argv) > 1 else None
    
    success, message = installFiles(source_dir)
    
    if success:
        print("✅ SUCCESS:", message)
        print("\nNext steps:")
        print("1. Restart VS Code to load the new configuration")
        print("2. Open a SQL file or any file you want to execute with DaSQL")
        print("3. Use the following keyboard shortcuts:")
        print("   - Ctrl+B: Execute selected text")
        print("   - Ctrl+Shift+B: Execute current line")
        print("   - Ctrl+Alt+B: Execute entire file")
        print("4. Or use Ctrl+Shift+P and search for 'DaSQL' to run tasks manually")
        print("\nNote: Make sure python3 and dasql.py are accessible from your system PATH")
    else:
        print("❌ FAILED:", message)
        print("\nTroubleshooting tips:")
        print("- Ensure Visual Studio Code is installed")
        print("- Check VS Code user configuration directory permissions")
        print("- Try running with administrator/sudo privileges")
        print("- Verify that python3 is installed and accessible")
        return 1
    
    return 0


if __name__ == "__main__":
    sys.exit(main())