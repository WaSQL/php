# MyShortcuts

MyShortcuts is a simple text expander for Windows written in Python. It uses a SQLite database to store shortcuts and expansions.

## Features
- Define your own triggers like `;sig` or `;addr`.
- Expands triggers system-wide when you type them followed by `>` and a separator.
- Add/update shortcuts on-the-fly using `Ctrl+Shift+M` hotkey with `;trigger<message>` syntax.
- **List all shortcuts** using `;list>` command.
- **Delete shortcuts** using `;delete:trigger_name>` command.
- Configurable via `myshortcuts.json`.
- Automatically creates the database if it doesn't exist.

## Requirements
- Python 3.8+
- Install dependencies:
  ```bash
  python -m pip install keyboard pyperclip
  ```
- First run may require **Run as Administrator** so `keyboard` can install hooks.

## Setup
1. Place `myshortcuts.py` and `myshortcuts.json` in the same folder.
2. Adjust settings in `myshortcuts.json` if needed.
3. Run:
   ```bash
   python myshortcuts.py
   ```

## Usage

### Expanding Shortcuts
Type your trigger followed by `>` and any separator (space, enter, punctuation):
- Type `;addr>` + space → expands to your address
- Type `;sig>` + enter → expands to your signature

### Adding/Updating Shortcuts (Live)
Use the syntax `;trigger<message>` then press `Ctrl+Shift+M`:

1. Type: `;addr<123 Main St, MyTown, USA>`
2. Press `Ctrl+Shift+M`
3. The command text disappears and the shortcut is saved

**Examples:**
- `;phone<555-123-4567>` + `Ctrl+Shift+M` → saves phone number
- `;sig<Best regards,\nJohn Smith>` + `Ctrl+Shift+M` → saves multi-line signature

### Managing Shortcuts

#### List All Shortcuts
Type `;list>` followed by any separator (space, enter, etc.) to see all your shortcuts:
- Shows trigger → expansion preview (truncated if long)
- Displays total count of shortcuts
- Includes instructions for deleting shortcuts

**Example:**
```
;list> + space
```
**Output:**
```
=== MyShortcuts List ===
;addr → 123 Main St, MyTown, USA
;linkedin → Hi there, thanks for connecting. \n\nAbout me: \n\nBy day...
;phone → 555-123-4567
;sig → Best regards,\nJohn Smith\nhttps://example.com

Total: 4 shortcuts

To delete a shortcut, use: ;delete:trigger_name>
```

#### Delete Shortcuts
Use `;delete:trigger_name>` to remove a shortcut:
- You don't need to include the `;` in the trigger name
- Provides confirmation when deleted
- Shows error if shortcut doesn't exist

**Examples:**
- `;delete:linkedin>` → deletes the `;linkedin` shortcut
- `;delete:addr>` → deletes the `;addr` shortcut
- `;delete:phone>` → deletes the `;phone` shortcut

### Adding Shortcuts via Database
You can also edit the SQLite database directly:
```sql
INSERT INTO myshortcuts(trigger, expansion) VALUES(';addr','123 Main St, MyTown, USA');
INSERT INTO myshortcuts(trigger, expansion) VALUES(';sig','Best,\nSteve Lloyd\nhttps://wired.wasql.com');
```

## Configuration

Edit `myshortcuts.json` to customize behavior:

```json
{
  "database": "myshortcuts.db",
  "max_buffer": 200,
  "separators": [" ", "\n", "\t", ".", ",", ";", ":", "!", "?", ")", "]", "}", "\"", "'"],
  "update_hotkey": "ctrl+shift+m",
  "debug": 0
}
```

**Settings:**
- `database`: Path to SQLite database file
- `max_buffer`: Maximum characters to keep in typing buffer
- `separators`: Characters that trigger expansion after `>`
- `update_hotkey`: Hotkey combination for adding/updating shortcuts
- `debug`: Set to `1` to enable detailed debug output, `0` for quiet operation

## System Commands

MyShortcuts includes built-in system commands:

| Command | Purpose | Example |
|---------|---------|---------|
| `;list>` | List all shortcuts | `;list>` + space |
| `;delete:trigger>` | Delete a shortcut | `;delete:linkedin>` |

These commands work just like regular shortcuts but perform system operations instead of text expansion.

## Autostart

You can make MyShortcuts start automatically when you log into Windows.

### Using Task Scheduler (recommended)

You can create a scheduled task manually via the Task Scheduler UI:

- Open Task Scheduler.
- Click **Create Task**.
- Name it: `MyShortcuts`.
- Under **Security options**, select **Run only when user is logged on**.
- Set **Trigger**: At log on (of your user).
- Set **Action**: Start a program.
  - Program/script: `python`
  - Add arguments: `C:\path\to\myshortcuts.py`
- Save the task.

### One-liner to create the task from PowerShell (run PowerShell as administrator):

```powershell
$action = New-ScheduledTaskAction -Execute "python.exe" -Argument "C:\path\to\myshortcuts.py"
$trigger = New-ScheduledTaskTrigger -AtLogOn
Register-ScheduledTask -TaskName "MyShortcuts" -Action $action -Trigger $trigger -RunLevel LeastPrivilege -User $env:USERNAME
```

Replace `C:\path\to\myshortcuts.py` with the full path to your script.

### One-liner to create the task from CMD as Administrator:

```cmd
schtasks /Create /TN "MyShortcuts" /TR "python.exe C:\path\to\myshortcuts.py" /SC ONLOGON /RL LIMITED /F /RU %USERNAME%
```

Again, replace `C:\path\to\myshortcuts.py` with your script's full path.

## Notes

- The task is configured to **run only when you are logged on** so it can interact with your desktop (required for keyboard hooks).
- Do **not** run the script as a system service — Windows services run in Session 0 and cannot interact with your keyboard or clipboard.
- On first run, you may need to **Run the script as Administrator** to allow the `keyboard` package to install low-level hooks.
- All triggers must start with `;` to be recognized.
- Use `\n` in messages for line breaks when adding shortcuts via the update hotkey.
- Set `"debug": 1` in the config file to see detailed keystroke and processing information.
- Ctrl+V paste operations are automatically ignored to prevent interference with clipboard shortcuts.