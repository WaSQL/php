# WaSQL Framework Instructions for Claude

## Overview
This document instructs Claude on how to assist users with WaSQL, a database-driven PHP web development framework. **Always read and reference `architecture.md` for comprehensive technical details.**

## Key Understanding Points

### WaSQL is Different from Traditional MVC
- **All logic is stored in database records**, not files
- Pages are records in the `_pages` table with fields: `name`, `body`, `functions`, `controller`, `js`, `css`
- This provides enhanced security (harder to hack) and instant portability (MySQL dump = entire site)

### Critical Syntax Rules
- **No `setValue()` function** - set variables directly: `$message = 'Hello';`
- **Use `$_REQUEST` not `$_POST`** for form data
- **Use `global $PASSTHRU;` not `$_REQUEST['passthru']`** for URL routing
- **All inline PHP needs semicolons**: `<?=$variable;?>` not `<?=$variable?>`
- **No PHP if/endif in views** - use `renderViewIf()` with `<view:name>` blocks
- **Use `global $USER;`** for built-in user management
- **Variables set in controller are available in views** directly

### Development Workflow
1. **Database First**: New pages/components must be created via WaSQL web interface
2. **PostEdit Sync**: Use `p.bat alias` to download database records as local files
3. **Modern Editing**: Edit with VS Code, PhpStorm, etc. with full IDE support
4. **Auto-Sync**: Changes automatically update database records
5. **Environment Promotion**: Use synchronization feature to promote changes through dev → stage → prod

## When Helping Users

### Code Generation Rules
- **Always use proper WaSQL syntax** from the corrected patterns
- **Set variables in controller**: `$users = getDBRecords(['-table' => 'users']);`
- **Use variables in views**: `<?=renderEach('user_row', $users, 'user');?>`
- **Use `renderViewIf()` for conditionals**: Never `<?php if: ?><?php endif; ?>`
- **All conditional content goes in `<view:name>` blocks**

### Common User Scenarios

#### "How do I create a new page?"
```php
// 1. Create page record via WaSQL web interface
// 2. Run PostEdit to sync: p.bat your-alias
// 3. Edit the generated files in your IDE
// 4. Changes auto-sync back to database
```

#### "How do I display data?"
```php
// In controller field:
$users = getDBRecords(['-table' => 'users', '-limit' => 10]);

// In body field:
<?=renderEach('user_card', $users, 'user');?>

<view:user_card>
<div class="user"><?=encodeHtml($user['name']);?></div>
</view:user_card>
```

#### "How do I handle forms?"

**Option 1: Using addEditDBForm (Recommended - Automatic Processing)**
```php
// In functions field:
function pageAddEdit($id = 0) {
    $opts = array(
        '-table' => 'users',
        '-fields' => getView('form_fields'),
        'name_options' => array('required' => 1),
        'email_options' => array('required' => 1, 'inputtype' => 'email')
    );
    if($id > 0) {
        $opts['_id'] = $id;
    }
    return addEditDBForm($opts); // Automatically handles add/update
}

// In controller field:
global $PASSTHRU;
$id = $PASSTHRU[1] ?? 0;
// No manual save handling needed - addEditDBForm does it automatically

// In body field:
<?=pageAddEdit($id);?>
```

**Option 2: Manual Form Processing**
```php
// In controller field:
if($_REQUEST['action'] == 'save_user') {
    // Manual processing when coding forms manually in view
    $result = addDBRecord([
        '-table' => 'users',
        'name' => $_REQUEST['name'],
        'email' => $_REQUEST['email']
    ]);
    if(is_numeric($result)) {
        $success = 'User saved successfully';
        setView('success');
    } else {
        $error = $result;
        setView('error');
    }
}

// In body field: Manual HTML form
<form method="post">
    <input type="hidden" name="action" value="save_user">
    <input type="text" name="name" required>
    <input type="email" name="email" required>
    <button type="submit">Save</button>
</form>
```

#### "How do I check if user is logged in?"
```php
// In controller field:
global $USER;
if(!isUser()) {
    setView('login_required');
    return;
}

$currentUser = $USER;
$userName = $USER['name'];
```

#### "How do I handle URL routing?"
```php
// In controller field:
global $PASSTHRU;
switch(strtolower($PASSTHRU[0])){
    case 'edit':
        $id = $PASSTHRU[1]; // From URL: /page/edit/123
        $record = getDBRecord(['-table' => 'items', '_id' => $id]);
        setView('edit_form');
    break;
    case 'list':
        $items = getDBRecords(['-table' => 'items']);
        setView('item_list');
    break;
    default:
        // Default view automatically loads if no setView() called
        $welcomeMsg = 'Welcome to our site';
    break;
}
```

#### "How do I use AJAX navigation?"
```html
<!-- In body field: -->
<a href="#" data-div="content" data-nav="/page/list" onclick="return wacss.nav(this);">
    Load List
</a>

<div id="content">
    <!-- AJAX content loads here -->
</div>
```

### Architecture Guidance
- **Reference `architecture.md`** for detailed technical information
- **Explain the database-driven approach** when users seem confused
- **Emphasize the PostEdit workflow** for modern development experience
- **Highlight security benefits** of database-stored logic

### File Structure (PostEdit)
When users ask about local development, explain the PostEdit file naming:
`{name}.{table}.{field}.{_id}.{extension}`

Example: `about._pages.body.13.html`

### Multi-Language Support
WaSQL supports PHP, Python, Node.js, Ruby, Perl, VBScript, Lua, Bash in the same application.
All languages receive the same request/session/server variables.

## What NOT to Do
- Don't suggest `setValue()` - it doesn't exist
- Don't use `pageValue()` for variables - only for page fields
- Don't suggest `$_POST` - use `$_REQUEST`
- Don't use `$_REQUEST['passthru']` - use `global $PASSTHRU;`
- Don't suggest PHP if/endif syntax in views
- Don't forget semicolons in inline PHP
- Don't suggest traditional file-based MVC patterns
- Don't recommend creating separate PHP files for models/controllers
- Don't suggest frameworks like Laravel/Symfony as alternatives
- Don't ignore WaSQL's database-first architecture

## What TO Do
- Always use correct WaSQL syntax from `ai_patterns.md`
- Set variables directly in controller: `$var = 'value';`
- Use `renderViewIf()` and `<view:name>` blocks for conditionals
- Use `global $PASSTHRU;` for URL routing
- Use `global $USER;` for user management
- Suggest using PostEdit for local development
- Recommend proper WaSQL functions and patterns
- Explain the security and portability benefits
- Reference `architecture.md` for detailed information

## Code Review Checklist
When reviewing or generating WaSQL code, verify:

✅ **Variables set in controller**: `$items = getDBRecords(...);`  
✅ **Proper inline PHP syntax**: `<?=$variable;?>`  
✅ **Use $_REQUEST not $_POST**: `$_REQUEST['name']`  
✅ **Global $PASSTHRU for routing**: `global $PASSTHRU;`  
✅ **Global $USER for auth**: `global $USER;`  
✅ **renderViewIf for conditionals**: Never `<?php if: ?>`  
✅ **All conditionals in view blocks**: `<view:name>content</view:name>`  
✅ **encodeHtml for user data**: `<?=encodeHtml($user['name']);?>`  
✅ **Semicolons in inline PHP**: Required for all statements  

## Additional Resources
- `ai_patterns.md` - Corrected code patterns and examples
- `quick_reference.md` - Function reference and common patterns
- `architecture.md` - Complete technical documentation
- `examples.md` - Real-world implementation examples
- `troubleshooting.md` - Common issues and solutions

Remember: WaSQL's database-driven architecture and unique syntax are its strengths. Help users embrace this approach with the correct syntax patterns rather than fighting against it.