# WaSQL Quick Reference

## Most Common Functions

### Database Functions

#### Single Record Operations
```php
// Get one record by criteria
$user = getDBRecord([
    '-table' => 'users',
    'id' => 123
]);

// Get one record by ID (shorthand)
$user = getDBRecordById('users', 123);

// Add new record
$newId = addDBRecord([
    '-table' => 'users',
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Update existing record
editDBRecord([
    '-table' => 'users',
    '-where' => 'id=123',
    'name' => 'Jane Doe',
    'email' => 'jane@example.com'
]);

// Update by ID (shorthand)
editDBRecordById('users', 123, [
    'name' => 'Jane Doe',
    'email' => 'jane@example.com'
]);

// Delete record
delDBRecord([
    '-table' => 'users',
    '-where' => 'id=123'
]);

// Delete by ID (shorthand)
delDBRecordById('users', 123);
```

#### Multiple Record Operations
```php
// Get multiple records
$users = getDBRecords([
    '-table' => 'users',
    'active' => 1,
    '-limit' => 20,
    '-order' => 'name'
]);

// Get count of records
$count = getDBCount([
    '-table' => 'users',
    'active' => 1
]);

// Execute raw SQL
$results = executeSQL("SELECT * FROM users WHERE created_date > '2024-01-01'");
```

### View Functions

#### Basic Rendering
```php
// Set which view to render
setView('user_profile');

// Render a view with data
renderView('user_card', $userData, 'data');

// Render template for each item in array
renderEach('product_item', $productArray, 'product');

// Get the contents of a view from the current page body
$content = getView('header_template');
```

#### Conditional Rendering
```php
// Render view only if condition is true
renderViewIf($isAdmin, 'admin_panel', $adminData, 'data');

// If/else rendering
renderViewIfElse($isLoggedIn, 'dashboard', 'login_form', $userData, 'data');

// Switch-style rendering
renderViewSwitch($userRole, [
    'admin' => 'admin_interface',
    'moderator' => 'mod_interface', 
    'user' => 'user_interface',
    'default' => 'guest_interface'
], $userData, 'data');
```

### Data Management (Variables)
```php
// In Controller: Set variables directly (no setValue function)
$username = $user['name'];
$states = getDBRecords(['-table' => 'states', '-order' => 'name']);
$error = 'Invalid login credentials';

// In Views: Use variables directly - they're available from controller
```

### Example: Conditional View Rendering
```html
<!-- Define the view first -->
<view:error_msg>
<div class="alert"><?=encodeHtml($error);?></div>
</view:error_msg>

<!-- Render the view conditionally -->
<?=renderViewIf(isset($error), 'error_msg', $error, 'error');?>

<!-- Multiple renderViewIf can call the same view with different data -->
<?=renderViewIf(isset($warning), 'error_msg', $warning, 'error');?>
<?=renderViewIf(isset($notice), 'error_msg', $notice, 'error');?>
```

### URL Routing
```php
// Always use global $PASSTHRU (not $_REQUEST['passthru'])
global $PASSTHRU;

// URL: /pagename/action/id/param
// Maps to: $PASSTHRU[0], $PASSTHRU[1], $PASSTHRU[2], etc.

switch(strtolower($PASSTHRU[0])){
    case 'edit':
        $id = $PASSTHRU[1];
        $record = getDBRecord(['-table' => 'states', '_id' => $id]);
        setView('edit');
    break;
    case 'list':
        $items = getDBRecords(['-table' => 'states']);
        setView('list');
    break;
    default:
        // Default view loads automatically if no setView() called
        $welcomeMsg = 'Welcome!';
    break;
}
```

### User Functions
```php
// Check if user is logged in
if(isUser()) {
    // User is authenticated  
}

// Access user data (built-in global variable)
global $USER;
$userName = $USER['name'];
$userRole = $USER['role'];
$userEmail = $USER['email'];

// Also available (alternative check)
if(isLoggedIn()) {
    // User is authenticated
}

// Check user permissions
if(hasPermission('admin')) {
    // User has admin rights
}

// Get user by ID
$user = getDBUserById(123);
```

### Form Handling
```php
// Use $_REQUEST for all form data (not $_POST)
$name = $_REQUEST['name'];
$email = $_REQUEST['email'];
$action = $_REQUEST['action'];

// Form processing
if($_REQUEST['action'] == 'save') {
    $result = addDBRecord([
        '-table' => 'contacts',
        'name' => $_REQUEST['name'],
        'email' => $_REQUEST['email']
    ]);
    
    if(is_numeric($result)) {
        $success = 'Record saved successfully';
    } else {
        $error = $result;
    }
}
```

### Utility Functions
```php
// Encode for safe HTML output (always use for user data)
echo encodeHtml($userInput);

// Generate unique ID
$guid = generateGUID();

// Format money
echo formatMoney(1234.56); // $1,234.56

// Check if email is valid
if(isEmail($email)) {
    // Valid email format
}

// Get file contents
$content = getFileContents('path/to/file.txt');
```

## Common Patterns

### Basic Page Structure
```php
// In controller field:
global $PASSTHRU;
switch(strtolower($PASSTHRU[0])){
    case 'save':
        if($_REQUEST['action'] == 'save') {
            // Process form data
            $result = addDBRecord([
                '-table' => 'contacts',
                'name' => $_REQUEST['name'],
                'email' => $_REQUEST['email']
            ]);
            
            if(is_numeric($result)) {
                $success = 'Contact saved successfully';
                setView('success');
            } else {
                $error = 'Failed to save contact';
                setView('error');
            }
        }
    break;
    case 'list':
        // Load data for display
        $contacts = getDBRecords([
            '-table' => 'contacts',
            '-order' => 'name',
            '-limit' => 50
        ]);
        setView('list');
    break;
    default:
        $welcomeMsg = 'Welcome to contacts';
    break;
}
```

```html
<!-- In body field: -->
<view:default>
<?=renderViewIf(isset($welcomeMsg), 'welcome', $welcomeMsg);?>
</view:default>

<view:welcome>
<h1><?=encodeHtml($welcomeMsg);?></h1>
</view:welcome>

<view:success>
<?=renderView('success_msg', $success);?>
</view:success>

<view:success_msg>
<div class="alert alert-success"><?=encodeHtml($success);?></div>
</view:success_msg>

<view:error>
<?=renderView('error_msg', $error);?>
</view:error>

<view:error_msg>
<div class="alert alert-danger"><?=encodeHtml($error);?></div>
</view:error_msg>

<view:list>
<h1>Contacts</h1>
<?=renderEach('contact_row', $contacts, 'contact');?>
</view:list>

<view:contact_row>
<div class="contact">
    <h3><?=encodeHtml($contact['name']);?></h3>
    <p><?=encodeHtml($contact['email']);?></p>
</div>
</view:contact_row>
```

### Authentication Check
```php
// In controller field:
global $USER;
if(!isLoggedIn()) {
    setView('login_required');
    return;
}

// User is logged in, continue with page logic
$currentUser = $USER;
$isAdmin = ($USER['role'] == 'admin');
```

### AJAX Navigation
```html
<!-- Basic AJAX link -->
<a href="#" data-div="content" data-nav="/page/list" onclick="return wacss.nav(this);">
    Load Content
</a>

<!-- AJAX form -->
<form data-div="result" data-nav="/page/process" onsubmit="return wacss.nav(this);">
    <input type="text" name="data">
    <button type="submit">Submit</button>
</form>

<!-- Target containers -->
<div id="content"><!-- AJAX content loads here --></div>
<div id="result"><!-- Form results load here --></div>
```

### Automatic Forms vs Manual Forms
```php
// AUTOMATIC: Using addEditDBForm (recommended)
function pageAddEdit($id = 0) {
    $opts = array(
        '-table' => 'products',
        '-fields' => getView('form_fields'),
        'name_options' => array('required' => 1),
        'price_options' => array('inputtype' => 'number', 'required' => 1)
    );
    if($id > 0) {
        $opts['_id'] = $id;
    }
    return addEditDBForm($opts); // Handles everything automatically
}

// MANUAL: Custom form processing
if($_REQUEST['action'] == 'save_product') {
    $result = addDBRecord([
        '-table' => 'products',
        'name' => $_REQUEST['name'],
        'price' => $_REQUEST['price']
    ]);
    // Handle result manually
}
```

## Critical Syntax Rules

### ✅ Correct WaSQL Syntax
```php
// Variables set directly in controller
$message = 'Hello World';
$users = getDBRecords(['-table' => 'users']);

// Use global $PASSTHRU for routing
global $PASSTHRU;
$action = $PASSTHRU[0];

// Use global $USER for authentication
global $USER;
$userName = $USER['name'];

// Use $_REQUEST for form data
$name = $_REQUEST['name'];

// All inline PHP needs semicolons
<?=encodeHtml($message);?>
<?=renderEach('user_row', $users, 'user');?>

// Conditional rendering with view blocks
<?=renderViewIf(isset($message), 'msg_view', $message);?>

<view:msg_view>
<div><?=encodeHtml($message);?></div>
</view:msg_view>
```

### ❌ Wrong Syntax (Don't Use)
```php
// Wrong - setValue() doesn't exist
setValue('message', 'Hello');

// Wrong - pageValue() is for page fields only
pageValue('message')

// Wrong - deprecated routing
$_REQUEST['passthru'][0]

// Wrong - use $_REQUEST instead
$_POST['name']

// Wrong - missing semicolon
<?=$message?>

// Wrong - PHP if/endif not supported in views
<?php if($condition): ?>
<div>Content</div>
<?php endif; ?>
```

## Quick Tips

1. **Set variables directly in controller**: `$var = 'value';`
2. **Use `$_REQUEST` instead of `$_POST`/`$_GET`**
3. **Use `encodeHtml()` when outputting user data**
4. **Use `global $PASSTHRU;` for URL routing**
5. **Use `global $USER;` for user management** 
6. **All inline PHP must end with semicolon**: `<?=$var;?>`
7. **Use `renderViewIf()` instead of PHP if/endif**
8. **All conditional content goes in `<view:name>` blocks**
9. **Database functions return arrays, not objects**
10. **View functions output directly, don't return values**
11. **Use `addEditDBForm()` for automatic CRUD forms**
12. **Default view loads automatically if no `setView()` called**

## For Complete Documentation
- **AI Patterns**: `ai_patterns.md` - Corrected code patterns 
- **Architecture**: `architecture.md` - Complete technical documentation
- **Claude Instructions**: `claude.md` - AI assistant guidelines
- **PostEdit files**: Check `php/database.php`, `php/common.php`, `php/user.php`
- **Online docs**: https://wasql.com/documentation/