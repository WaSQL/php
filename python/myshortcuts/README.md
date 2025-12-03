# MyShortcuts File-Based Storage

This folder contains your text expansion shortcuts. Each shortcut is stored as a separate file.

## File Naming Convention

To create a shortcut, create a file with the pattern: `{shortcut_name}.shortcut`

Example:
- File: `li.shortcut` → Trigger: `;li>`
- File: `addr.shortcut` → Trigger: `;addr>`
- File: `email.shortcut` → Trigger: `;email>`

## File Contents

The entire content of the file will be used as the expansion text when you trigger the shortcut.

### Single Line Example
**File:** `li.shortcut`
```
<li></li>
```
**Usage:** Type `;li>` and it expands to `<li></li>`

### Multi-Line Example
**File:** `addr.shortcut`
```
123 Main Street
City, State 12345
United States
```
**Usage:** Type `;addr>` and it expands to the full address

## Managing Shortcuts

### Create/Edit a Shortcut
1. Create or edit a `.shortcut` file in this folder
2. Add your expansion text
3. Save the file
4. The shortcut is immediately available

### Delete a Shortcut
- Delete the `.shortcut` file, OR
- Type `;delete:shortcut_name>` (e.g., `;delete:li>`)

### View All Shortcuts
Simply open this folder in your file explorer or text editor to see all available shortcuts.

## Advantages of File-Based Storage

- **Easy to edit:** Open files directly in any text editor
- **Version control:** Track changes with Git
- **Backup friendly:** Just copy the folder
- **Human readable:** No database tools needed
- **Portable:** Works across different systems
