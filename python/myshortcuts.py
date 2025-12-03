"""
myshortcuts.py
Simple text-expander using file-based storage and settings from myshortcuts.json.
"""

import time
import threading
from pathlib import Path
import pyperclip
import keyboard
import sys
import json

CONFIG_FILE = Path(__file__).with_name("myshortcuts.json")

# Load settings
def load_config():
    if not CONFIG_FILE.exists():
        print(f"Config file {CONFIG_FILE} not found.")
        sys.exit(1)
    try:
        with open(CONFIG_FILE, "r", encoding="utf-8") as f:
            return json.load(f)
    except json.JSONDecodeError as e:
        print(f"Error parsing config file {CONFIG_FILE}: {e}")
        sys.exit(1)
    except Exception as e:
        print(f"Error reading config file {CONFIG_FILE}: {e}")
        sys.exit(1)

config = load_config()
SHORTCUTS_FOLDER = Path(config.get("shortcuts_folder", "myshortcuts"))
MAX_BUFFER = int(config.get("max_buffer", 200))
SEPARATORS = set(config.get("separators", [" ", "\n", "\t", ".", ",", ";", ":", "!", "?", ")", "]", "}", "\"", "'"]))
DEBUG = config.get("debug", 0)

# Key name to character mapping
KEY_MAP = {
    'space': ' ',
    'enter': '\n',
    'tab': '\t',
    'period': '.',
    'comma': ',',
    ';': ';',
    ':': ':',
    'semicolon': ';',
    'shift+semicolon': ':',
    'shift+1': '!',
    'shift+slash': '?',
    'shift+0': ')',
    'right bracket': ']',
    'shift+right bracket': '}',
    'quote': "'",
    'shift+quote': '"',
    'minus': '-',
    'shift+minus': '_',
    'equals': '=',
    'shift+equals': '+',
    'left bracket': '[',
    'shift+left bracket': '{',
    'backslash': '\\',
    'shift+backslash': '|',
    'slash': '/',
    'shift+grave': '~',
    'grave': '`',
    'shift+2': '@',
    'shift+3': '#',
    'shift+4': '$',
    'shift+5': '%',
    'shift+6': '^',
    'shift+7': '&',
    'shift+8': '*',
    'shift+9': '(',
    'shift+comma': '<',
    'shift+period': '>',
    '<': '<',
    '>': '>',
}

class MyShortcutsStore:
    def __init__(self, shortcuts_folder):
        self.shortcuts_folder = Path(shortcuts_folder)
        self._ensure_folder()

    def _ensure_folder(self):
        if not self.shortcuts_folder.exists():
            self.shortcuts_folder.mkdir(parents=True, exist_ok=True)
            print(f"Shortcuts folder created at {self.shortcuts_folder}")

    def _trigger_to_filename(self, trigger):
        """Convert trigger (e.g., ';li') to filename (e.g., 'li.shortcut')"""
        # Strip leading semicolon if present
        name = trigger.lstrip(';')

        # Security: Prevent path traversal by removing path separators
        # Only allow alphanumeric, dash, underscore, and space
        name = ''.join(c for c in name if c.isalnum() or c in '-_ ')

        if not name:
            raise ValueError(f"Invalid trigger name: {trigger}")

        return f"{name}.shortcut"

    def _filename_to_trigger(self, filename):
        """Convert filename (e.g., 'li.shortcut') to trigger (e.g., ';li')"""
        # Remove .shortcut extension
        name = filename.replace('.shortcut', '')
        return f";{name}"

    def get_expansion(self, trigger):
        try:
            filename = self._trigger_to_filename(trigger)
            filepath = self.shortcuts_folder / filename

            if not filepath.exists():
                return None

            with open(filepath, 'r', encoding='utf-8') as f:
                return f.read()
        except ValueError as e:
            if DEBUG:
                print(f"Invalid trigger name: {e}")
            return None
        except Exception as e:
            if DEBUG:
                print(f"Error reading shortcut file: {e}")
            return None

    def add_or_update(self, trigger, expansion):
        try:
            filename = self._trigger_to_filename(trigger)
            filepath = self.shortcuts_folder / filename

            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(expansion)
            if DEBUG:
                print(f"Saved shortcut to {filepath}")
        except ValueError as e:
            if DEBUG:
                print(f"Invalid trigger name: {e}")
        except Exception as e:
            if DEBUG:
                print(f"Error writing shortcut file: {e}")


    def delete_shortcut(self, trigger):
        try:
            filename = self._trigger_to_filename(trigger)
            filepath = self.shortcuts_folder / filename

            if DEBUG:
                print(f"Attempting to delete: {filepath}")
                print(f"File exists: {filepath.exists()}")

            if not filepath.exists():
                return False

            filepath.unlink()
            if DEBUG:
                print(f"Successfully deleted {filepath}")
            return True
        except ValueError as e:
            if DEBUG:
                print(f"Invalid trigger name: {e}")
            return False
        except Exception as e:
            if DEBUG:
                print(f"Error deleting shortcut: {e}")
            return False

def safe_paste(text):
    try:
        prev_clip = pyperclip.paste()
    except Exception:
        prev_clip = None
    
    try:
        # For very long text, give more time for clipboard operations
        if len(text) > 500:
            time.sleep(0.02)
        
        pyperclip.copy(text)
        
        # Wait longer for large content to be copied
        if len(text) > 500:
            time.sleep(0.05)
        else:
            time.sleep(0.02)
        
        keyboard.send('ctrl+v')
        
        # Wait longer after pasting large content
        if len(text) > 500:
            time.sleep(0.1)
        else:
            time.sleep(0.05)
        
        # Restore previous clipboard content
        if prev_clip is not None:
            time.sleep(0.02)
            pyperclip.copy(prev_clip)
            
    except Exception as e:
        print(f"Paste error: {e}")
        if DEBUG:
            print(f"Text length that failed to paste: {len(text)}")


class Expander:
    def __init__(self, store: MyShortcutsStore):
        self.store = store
        self.buffer = ""
        self.lock = threading.Lock()

    def start(self):
        keyboard.hook(self.on_event)
        print(f"MyShortcuts running. Press Ctrl+C in console to stop.")
        try:
            keyboard.wait()
        except KeyboardInterrupt:
            print("Exiting MyShortcuts.")

    def on_event(self, event):
        if event.event_type != 'down':
            return
            
        key = event.name
        if DEBUG:
            print(f"Key pressed: {key}")
        
        # Skip processing keys when Ctrl is held down (to avoid capturing Ctrl+V, Ctrl+C, etc.)
        if keyboard.is_pressed('ctrl') and key not in ['ctrl']:
            if DEBUG:
                print(f"Skipping key '{key}' because Ctrl is pressed")
            return
        
        with self.lock:
            # Handle regular characters (letters, numbers, and some symbols)
            if len(key) == 1 and (key.isalnum() or key in ';()<>:'):
                self.buffer += key
                if DEBUG:
                    print(f"Buffer: {repr(self.buffer)}")
                # Check if we just added a >
                if key == '>':
                    self._maybe_update_or_expand()
            # Handle mapped keys (punctuation, etc.)
            elif key in KEY_MAP:
                char = KEY_MAP[key]
                self.buffer += char
                if DEBUG:
                    print(f"Buffer: {repr(self.buffer)}")
                if char in SEPARATORS:
                    pass  # Do nothing for regular separators
                elif char == '>':
                    self._maybe_update_or_expand()
            # Handle backspace
            elif key == 'backspace':
                if self.buffer:
                    self.buffer = self.buffer[:-1]
                    if DEBUG:
                        print(f"Buffer after backspace: {repr(self.buffer)}")
            # Handle other special keys that might be separators
            elif key in ['enter', 'space', 'tab']:
                if key == 'enter':
                    self.buffer += '\n'
                elif key == 'space':
                    self.buffer += ' '
                elif key == 'tab':
                    self.buffer += '\t'
                if DEBUG:
                    print(f"Buffer: {repr(self.buffer)}")
                # Do nothing for regular separators

            # Keep buffer size manageable
            if len(self.buffer) > MAX_BUFFER:
                self.buffer = self.buffer[-MAX_BUFFER:]

    def _maybe_update_or_expand(self):
        buf = self.buffer.rstrip()

        if DEBUG:
            print(f"_maybe_update_or_expand called with buffer: {repr(buf)}")

        # Look for the last complete command starting with ;
        # Find all ; positions and check each one
        semicolon_positions = []
        for i, char in enumerate(buf):
            if char == ';':
                semicolon_positions.append(i)

        if not semicolon_positions:
            if DEBUG:
                print("No ; found in buffer")
            return

        # Check from the last ; position
        last_semicolon = semicolon_positions[-1]
        token = buf[last_semicolon:]

        if DEBUG:
            print(f"_maybe_update_or_expand called. Token: {repr(token)}")

        if not token or not token.startswith(';'):
            if DEBUG:
                print("Token doesn't start with ; or is empty")
            return

        # Check for expansion command: ;trigger>
        if token.endswith('>') and '<' not in token and len(token) > 2:
            trigger = token[:-1]  # Remove the >

            if DEBUG:
                print(f"Processing token: {repr(token)}, trigger: {repr(trigger)}")

            # Handle special delete command
            if trigger.startswith(';delete:'):
                if DEBUG:
                    print(f"Handling delete command: {repr(trigger)}")
                self._handle_delete_command(trigger, token)  # Pass both trigger and original token
                return

            if DEBUG:
                print(f"Looking for expansion of trigger: {repr(trigger)}")
            expansion = self.store.get_expansion(trigger)
            if expansion:
                if DEBUG:
                    print(f"Found expansion: {repr(expansion)}")
                chars_to_erase = len(token)

                for _ in range(chars_to_erase):
                    keyboard.send('backspace')
                    time.sleep(0.005)

                safe_paste(expansion)
                self.buffer = ""
            else:
                if DEBUG:
                    print(f"No expansion found for trigger: {repr(trigger)}")
        else:
            if DEBUG:
                print(f"Token doesn't match expansion pattern. Token: {repr(token)}")
    
    def _handle_delete_command(self, trigger, original_token):
        """Handle the ;delete:trigger_name> command to delete a shortcut."""
        if DEBUG:
            print(f"_handle_delete_command called with trigger: {repr(trigger)}, original_token: {repr(original_token)}")
        
        # Extract the trigger name from ;delete:trigger_name
        if ':' not in trigger:
            output = "Invalid delete syntax. Use: ;delete:trigger_name>"
            if DEBUG:
                print("No colon found in delete command")
        else:
            target_trigger = trigger.split(':', 1)[1]
            if DEBUG:
                print(f"Target trigger before prefix check: {repr(target_trigger)}")
            
            if not target_trigger.startswith(';'):
                target_trigger = ';' + target_trigger
                
            if DEBUG:
                print(f"Final target trigger: {repr(target_trigger)}")
            
            # Debug: Check if the shortcut exists before trying to delete
            existing_expansion = self.store.get_expansion(target_trigger)
            if DEBUG:
                print(f"Existing expansion for {target_trigger}: {repr(existing_expansion)}")
            
            deleted = self.store.delete_shortcut(target_trigger)
            if deleted:
                output = f"Successfully deleted shortcut: {target_trigger}"
                if DEBUG:
                    print(f"Successfully deleted: {target_trigger}")
            else:
                output = f"Shortcut not found: {target_trigger}"
                if DEBUG:
                    print(f"Shortcut not found for deletion: {target_trigger}")
        
        # Use the original token length to erase the correct number of characters
        chars_to_erase = len(original_token)
        
        if DEBUG:
            print(f"Erasing {chars_to_erase} characters for command: {repr(original_token)}")
        
        for _ in range(chars_to_erase):
            keyboard.send('backspace')
            time.sleep(0.005)
        
        safe_paste(output)
        self.buffer = ""


def main():
    store = MyShortcutsStore(SHORTCUTS_FOLDER)
    expander = Expander(store)
    expander.start()

if __name__ == "__main__":
    main()