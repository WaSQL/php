"""
myshortcuts.py
Simple text-expander using SQLite and settings from myshortcuts.json,
including hotkey Ctrl+Shift+M to detect add/update commands.
"""

import sqlite3
import time
import threading
from pathlib import Path
import pyperclip
import keyboard
import sys
import json
import re

CONFIG_FILE = Path(__file__).with_name("myshortcuts.json")

# Load settings
def load_config():
    if not CONFIG_FILE.exists():
        print(f"Config file {CONFIG_FILE} not found.")
        sys.exit(1)
    with open(CONFIG_FILE, "r", encoding="utf-8") as f:
        return json.load(f)

config = load_config()
DB_PATH = Path(config.get("database", "myshortcuts.db"))
MAX_BUFFER = int(config.get("max_buffer", 200))
SEPARATORS = set(config.get("separators", [" ", "\n", "\t", ".", ",", ";", ":", "!", "?", ")", "]", "}", "\"", "'"]))
UPDATE_HOTKEY = config.get("update_hotkey", "ctrl+shift+m")
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
    def __init__(self, db_path):
        self.db_path = Path(db_path)
        self._ensure_db()

    def _ensure_db(self):
        if not self.db_path.exists():
            conn = sqlite3.connect(self.db_path)
            conn.execute("""
                CREATE TABLE myshortcuts (
                    trigger TEXT PRIMARY KEY,
                    expansion TEXT NOT NULL
                )
            """)
            conn.commit()
            conn.close()
            print(f"Database created at {self.db_path}")

    def get_expansion(self, trigger):
        conn = sqlite3.connect(self.db_path)
        cur = conn.cursor()
        cur.execute("SELECT expansion FROM myshortcuts WHERE trigger = ?", (trigger,))
        row = cur.fetchone()
        conn.close()
        return row[0] if row else None

    def add_or_update(self, trigger, expansion):
        conn = sqlite3.connect(self.db_path)
        cur = conn.cursor()
        cur.execute("INSERT INTO myshortcuts(trigger, expansion) VALUES(?, ?) ON CONFLICT(trigger) DO UPDATE SET expansion=excluded.expansion", (trigger, expansion))
        conn.commit()
        conn.close()
    
    def list_all_shortcuts(self):
        conn = sqlite3.connect(self.db_path)
        cur = conn.cursor()
        cur.execute("SELECT trigger, expansion FROM myshortcuts ORDER BY trigger")
        rows = cur.fetchall()
        conn.close()
        return rows
    
    def delete_shortcut(self, trigger):
        conn = sqlite3.connect(self.db_path)
        cur = conn.cursor()
        
        if DEBUG:
            # Check what's in the database before deletion
            cur.execute("SELECT trigger, expansion FROM myshortcuts WHERE trigger = ?", (trigger,))
            existing = cur.fetchone()
            print(f"Before deletion - trigger '{trigger}' exists: {existing is not None}")
            if existing:
                print(f"Existing entry: {existing}")
        
        cur.execute("DELETE FROM myshortcuts WHERE trigger = ?", (trigger,))
        deleted = cur.rowcount > 0
        
        if DEBUG:
            print(f"Delete operation result: {deleted}, rowcount: {cur.rowcount}")
        
        conn.commit()
        conn.close()
        return deleted

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

def get_clipboard_with_retry(max_attempts=3, delay=0.1):
    """Get clipboard content with retry logic to handle timing issues."""
    for attempt in range(max_attempts):
        try:
            content = pyperclip.paste()
            if content is not None:
                return content
            time.sleep(delay)
        except Exception as e:
            if attempt == max_attempts - 1:
                print(f"Error reading clipboard after {max_attempts} attempts: {e}")
                return ""
            time.sleep(delay)
    return ""

class Expander:
    def __init__(self, store: MyShortcutsStore):
        self.store = store
        self.buffer = ""
        self.lock = threading.Lock()

    def start(self):
        keyboard.hook(self.on_event)
        keyboard.add_hotkey(UPDATE_HOTKEY, self.handle_update_hotkey)
        print(f"MyShortcuts running. Press Ctrl+C in console to stop.")
        print(f"Update hotkey is '{UPDATE_HOTKEY}'")
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
            
        # Check for update command: ;trigger<message>
        if '<' in token and token.endswith('>'):
            parts = token[1:-1].split('<', 1)  # Remove ; and >, split on first <
            if len(parts) == 2:
                trigger = ';' + parts[0]
                message = parts[1]
                self.store.add_or_update(trigger, message)
                if DEBUG:
                    print(f"Auto-updated trigger '{trigger}' with expansion ({len(message)} chars)")
                return
        
        # Check for expansion command: ;trigger>
        if token.endswith('>') and '<' not in token and len(token) > 2:
            trigger = token[:-1]  # Remove the >
            
            if DEBUG:
                print(f"Processing token: {repr(token)}, trigger: {repr(trigger)}")
            
            # Handle special system commands
            if trigger == ';list':
                if DEBUG:
                    print("Handling list command")
                self._handle_list_command()
                return
            elif trigger.startswith(';delete:'):
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
    
    def _handle_list_command(self):
        """Handle the ;list> command to show all shortcuts."""
        shortcuts = self.store.list_all_shortcuts()
        
        if not shortcuts:
            output = "No shortcuts found in database."
        else:
            output_lines = ["=== MyShortcuts List ==="]
            for trigger, expansion in shortcuts:
                # Truncate long expansions for display
                display_expansion = expansion.replace('\n', '\\n').replace('\r', '\\r')
                if len(display_expansion) > 60:
                    display_expansion = display_expansion[:57] + "..."
                output_lines.append(f"{trigger} → {display_expansion}")
            output_lines.append(f"\nTotal: {len(shortcuts)} shortcuts")
            output_lines.append("\nTo delete a shortcut, use: ;delete:trigger_name>")
            output = "\n".join(output_lines)
        
        # Erase the ;list> command
        chars_to_erase = len(";list>")
        for _ in range(chars_to_erase):
            keyboard.send('backspace')
            time.sleep(0.005)
        
        safe_paste(output)
        self.buffer = ""
        if DEBUG:
            print("Listed all shortcuts")
    
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
                    # Additional debug: List all shortcuts to see what exists
                    all_shortcuts = self.store.list_all_shortcuts()
                    print(f"All shortcuts in database: {all_shortcuts}")
        
        # Use the original token length to erase the correct number of characters
        chars_to_erase = len(original_token)
        
        if DEBUG:
            print(f"Erasing {chars_to_erase} characters for command: {repr(original_token)}")
        
        for _ in range(chars_to_erase):
            keyboard.send('backspace')
            time.sleep(0.005)
        
        safe_paste(output)
        self.buffer = ""

    def handle_update_hotkey(self):
        if DEBUG:
            print(f"Hotkey pressed! Buffer: {repr(self.buffer)}")
        with self.lock:
            # Look for incomplete update commands: ;trigger< or ;trigger<partial text
            pattern = r";([^\s<>]+)<([^>]*?)$"
            match = re.search(pattern, self.buffer)
            
            if not match:
                if DEBUG:
                    print("No incomplete update command found in buffer.")
                return

            trigger = ";" + match.group(1)
            existing_text = match.group(2)
            
            # Get clipboard content with improved error handling and retry logic
            clipboard_content = get_clipboard_with_retry()
            
            if DEBUG:
                print(f"Clipboard content length: {len(clipboard_content)}")
                print(f"First 100 chars of clipboard: {repr(clipboard_content[:100])}")
            
            # Combine existing text with clipboard
            if existing_text:
                message = existing_text + clipboard_content
            else:
                message = clipboard_content
            
            # Replace \n literals with actual newlines
            message = message.replace('\\n', '\n')
            
            # Save to database
            self.store.add_or_update(trigger, message)
            
            if DEBUG:
                print(f"Updated trigger '{trigger}' with clipboard content ({len(message)} chars)")
                print(f"Final message preview: {repr(message[-50:])}")  # Show last 50 chars

            # Remove the incomplete command from input
            start = match.start()
            command_length = len(match.group(0))
            
            # Remove from buffer
            self.buffer = self.buffer[:start] + self.buffer[start + command_length:]

            # Send backspaces to remove the command from the input field
            for _ in range(command_length):
                keyboard.send('backspace')
                time.sleep(0.005)

def main():
    store = MyShortcutsStore(DB_PATH)
    expander = Expander(store)
    expander.start()

if __name__ == "__main__":
    main()