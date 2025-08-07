# WaSQL AI Patterns

## Common Code Patterns for AI Tools

This document provides standardized patterns that AI assistants should suggest when helping users with WaSQL development.

## Page Creation Pattern

### 1. Basic Page Structure
**Controller Field:**
```php
// Authentication check (if needed)
if(!isUser()) {
    setView('login',1);
    return;
}

// Handle URL routing using $PASSTHRU array
// URL: /pagename/action/id/param maps to $PASSTHRU[0], $PASSTHRU[1], etc.
global $PASSTHRU;
switch(strtolower($PASSTHRU[0])){
    case 'addedit':
        $id = $PASSTHRU[1];
        // Load record if editing
        if($id > 0) {
            $record = getDBRecord(['-table' => 'people', '_id' => $id]);
        }
        setView('addedit');
        return;
    break;
    
    case 'list':
        $recs = getDBRecords([
            '-table' => 'people',
            '-order' => 'created_date DESC',
            '-limit' => 20
        ]);
        setView('list');
        return;
    break;
    
    case 'save':
        // Handle form submission
        if($_REQUEST['_id']) {
            // Update existing record
            $result = editDBRecord([
                '-table' => 'people',
                '_id' => $_REQUEST['_id'],
                'name' => $_REQUEST['name'],
                'age' => $_REQUEST['age']
            ]);
        } else {
            // Create new record
            $result = addDBRecord([
                '-table' => 'people',
                'name' => $_REQUEST['name'],
                'age' => $_REQUEST['age']
            ]);
        }
        
        if(is_numeric($result)) {
            $success = 'Record saved successfully';
            setView('success');
        } else {
            $error = $result;
            setView('error');
        }
        return;
    break;
    
    default:
        // If no setView() is called, 'default' view is automatically used
        $welcomeMessage = 'Welcome to our page';
    break;
}
```

**Body Field:**
```html
<view:default>
<div class="container">
    <h1>People Management</h1>
    <?=renderViewIf(isset($welcomeMessage), 'welcome_msg', $welcomeMessage);?>
    
    <!-- AJAX Navigation Links -->
    <div style="margin: 20px 0;">
        <a href="#" data-div="content" data-nav="/people/list" onclick="return wacss.nav(this);" class="button is-info">
            View List
        </a>
        <a href="#" data-div="content" data-nav="/people/addedit/0" onclick="return wacss.nav(this);" class="button is-primary">
            Add New Person
        </a>
    </div>
    
    <!-- Content area for AJAX responses -->
    <div id="content"></div>
</div>
</view:default>

<view:welcome_msg>
<p><?=encodeHtml($welcomeMessage);?></p>
</view:welcome_msg>

<view:list>
<div class="container">
    <h3>People List</h3>
    <div style="margin-bottom: 20px;">
        <a href="#" data-div="content" data-nav="/people/addedit/0" onclick="return wacss.nav(this);" class="button is-primary">
            Add New
        </a>
    </div>
    
    <table class="table is-fullwidth">
        <thead>
            <tr><th>Name</th><th>Age</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?=renderEach('list_row', $recs, 'rec');?>
        </tbody>
    </table>
</div>
</view:list>

<view:list_row>
<tr>
    <td><?=encodeHtml($rec['name']);?></td>
    <td><?=$rec['age'];?></td>
    <td>
        <a href="#" data-div="content" data-nav="/people/addedit/<?=$rec['_id'];?>" 
           onclick="return wacss.nav(this);" class="button is-small is-info">Edit</a>
        <a href="#" data-div="content" data-nav="/people/delete/<?=$rec['_id'];?>" 
           onclick="return wacss.nav(this);" class="button is-small is-danger">Delete</a>
    </td>
</tr>
</view:list_row>

<view:addedit>
<div class="container">
    <?=renderViewIf(isset($record), 'edit_title', 'edit_title');?>
    <?=renderViewIf(!isset($record), 'add_title', 'add_title');?>
    <?=pageAddEdit($id);?>
</div>
</view:addedit>

<view:edit_title>
<h3>Edit Person</h3>
</view:edit_title>

<view:add_title>
<h3>Add New Person</h3>
</view:add_title>

<view:addedit_fields>
<div class="field">
    <label class="label">Name</label>
    <div class="control">
        [name]
    </div>
</div>
<div class="field">
    <label class="label">Age</label>
    <div class="control">
        [age]
    </div>
</div>
</view:addedit_fields>

<view:success>
<div class="container">
    <?=renderView('success_msg', $success);?>
    <a href="#" data-div="content" data-nav="/people/list" onclick="return wacss.nav(this);">
        Back to List
    </a>
</div>
</view:success>

<view:success_msg>
<div class="notification is-success">
    <?=encodeHtml($success);?>
</div>
</view:success_msg>

<view:error>
<div class="container">
    <?=renderView('error_msg', $error);?>
    <a href="#" onclick="history.back();">Go Back</a>
</div>
</view:error>

<view:error_msg>
<div class="notification is-danger">
    Error: <?=encodeHtml($error);?>
</div>
</view:error_msg>
```

**Functions Field:**
```php
function pageAddEdit($id = 0) {
    $opts = array(
        '-table' => 'people',
        '-action' => '/people/save',  // Where form submits
        '-method' => 'post',
        '-fields' => getView('addedit_fields'),
        'name_options' => array(
            'inputtype' => 'text',
            'required' => 1,
            'class' => 'input is-large',
            'placeholder' => 'Enter full name'
        ),
        'age_options' => array(
            'inputtype' => 'number',
            'max' => 120,
            'min' => 1,
            'step' => 1,
            'class' => 'input is-large',
            'required' => 1
        )
    );
    
    if($id > 0) {
        $opts['_id'] = $id;
    }
    
    return addEditDBForm($opts);
}

function validatePersonData($data) {
    $errors = array();
    
    if(empty($data['name'])) {
        $errors[] = 'Name is required';
    }
    
    if(!is_numeric($data['age']) || $data['age'] < 1 || $data['age'] > 120) {
        $errors[] = 'Age must be between 1 and 120';
    }
    
    return $errors;
}
```

## Key WaSQL Patterns for AI Tools

### 1. URL Routing with $PASSTHRU
```php
global $PASSTHRU;
// URL: /pagename/action/id/param
// Maps to: $PASSTHRU[0], $PASSTHRU[1], $PASSTHRU[2], etc.

switch(strtolower($PASSTHRU[0])){
    case 'api':
        // Handle API endpoint
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
        return;
    break;
    
    case 'ajax':
        // Handle AJAX requests
        setView('ajax_response');
        return;
    break;
}
```

### 2. AJAX Navigation Pattern
```html
<!-- Basic AJAX link -->
<a href="#" data-div="target_div" data-nav="/page/action/id" onclick="return wacss.nav(this);">
    Click Me
</a>

<!-- AJAX form submission -->
<form data-div="result_area" data-nav="/page/process" onsubmit="return wacss.nav(this);">
    <input type="text" name="data">
    <button type="submit">Submit</button>
</form>

<!-- Target areas for AJAX content -->
<div id="target_div"><!-- Content loads here --></div>
<div id="result_area"><!-- Form results load here --></div>
```

### 3. View Management
```php
// Set single view (replaces any previous setView calls)
setView('dashboard', 1);

// Add additional views (cumulative)
setView('header');
setView('content');
setView('footer');

// Conditional view rendering in body using variables set in controller
$isAdmin = ($USER['role'] == 'admin');
$hasData = !empty($products);
```

```html
<!-- In body field -->
<?=renderViewIf($isAdmin, 'admin_menu', $USER);?>
<?=renderViewIfElse($hasData, 'data_table', 'no_data_message', $products);?>

<view:admin_menu>
    <div class="admin-panel">Admin: <?=encodeHtml($USER['name']);?></div>
</view:admin_menu>

<view:data_table>
    <?=renderEach('product_row', $products, 'product');?>
</view:data_table>

<view:no_data_message>
    <div class="alert">No data available</div>
</view:no_data_message>
```

### 4. Data Flow Pattern
```php
// In controller: Set variables directly
global $USER; // Built-in user management
$currentUser = $USER;
$products = getDBRecords(['-table' => 'products']);
$message = 'Operation completed successfully';
```

```html
<!-- In body: Use variables directly -->
<h1>Welcome <?=encodeHtml($currentUser['name']);?></h1>
<?=renderEach('product_card', $products, 'product');?>
<?=renderViewIf(isset($message), 'alert_msg', $message);?>

<view:alert_msg>
<div class="alert"><?=encodeHtml($message);?></div>
</view:alert_msg>

<view:product_card>
<div class="card">
    <h3><?=encodeHtml($product['name']);?></h3>
    <p>Price: $<?=$product['price'];?></p>
</div>
</view:product_card>
```

### 5. Form Processing Pattern
```php
// Controller handles both display and processing
global $PASSTHRU;
switch(strtolower($PASSTHRU[0])){
    case 'contact':
        if($_REQUEST['action'] == 'send') {
            // Process form
            $errors = validateContactForm($_REQUEST);
            if(empty($errors)) {
                $result = addDBRecord([
                    '-table' => 'contacts',
                    'name' => $_REQUEST['name'],
                    'email' => $_REQUEST['email'],
                    'message' => $_REQUEST['message']
                ]);
                
                if($result) {
                    $success = 'Message sent successfully';
                    setView('success');
                } else {
                    $error = 'Failed to send message';
                    $form_data = $_REQUEST;
                    setView('contact_form');
                }
            } else {
                $errors = $errors;
                $form_data = $_REQUEST;
                setView('contact_form');
            }
        } else {
            setView('contact_form');
        }
    break;
}
```

### 6. User Management Pattern
```php
// Built-in user management
global $USER;

// Check if user is logged in
if(!isUser()) {
    setView('login_required');
    return;
}

// Get current user info
$currentUser = $USER;
$userName = $USER['name'];
$userRole = $USER['role'];

// Check user permissions
if($USER['role'] == 'admin') {
    $showAdminPanel = true;
}
```

## Best Practices for AI Tools

1. **Always use `global $PASSTHRU;`** for URL routing
2. **Use `encodeHtml()`** when outputting any user data
3. **Set variables directly in controller** - no setValue() function
4. **Use `renderEach()` with variable name** as third parameter for loops
5. **Include error handling** in every database operation
6. **Use AJAX navigation** with `data-nav` and `data-div` for modern UX
7. **Validate all inputs** before database operations
8. **Use `<view:name>` blocks** for all conditional content
9. **Default view is automatic** - only call `setView()` for non-default views
10. **Use `addEditDBForm()`** for automatic CRUD forms
11. **Handle both GET and POST** in the same controller switch statement
12. **Use `$_REQUEST` instead of `$_POST`** for form data
13. **Use `global $USER`** for built-in user management
14. **All inline PHP must end with semicolon** `<?=$variable;?>`
15. **Use `renderViewIf()` instead of PHP if/endif** syntax

## Common Anti-Patterns to Avoid

❌ **Don't use deprecated `$_REQUEST['passthru']`**
```php
// Wrong
switch($_REQUEST['passthru'][0])

// Right  
global $PASSTHRU;
switch(strtolower($PASSTHRU[0]))
```

❌ **Don't use setValue() - it doesn't exist**
```php
// Wrong
setValue('message', 'Hello');

// Right
$message = 'Hello';
```

❌ **Don't use pageValue() for variables**
```php
// Wrong
pageValue('record')['_id']

// Right
$record['_id'] // if $record was set in controller
```

❌ **Don't use PHP if/endif syntax in views**
```html
<!-- Wrong -->
<?php if($condition): ?>
    <div>Content</div>
<?php endif; ?>

<!-- Right -->
<?=renderViewIf($condition, 'content_view', $data);?>

<view:content_view>
<div>Content: <?=$data;?></div>
</view:content_view>
```

❌ **Don't forget semicolons in inline PHP**
```html
<!-- Wrong -->
<?=$variable?>

<!-- Right -->
<?=$variable;?>
```

❌ **Don't use $_POST directly**
```php
// Wrong
$name = $_POST['name'];

// Right
$name = $_REQUEST['name'];
```

## Database-First Development

1. **Create page records** through WaSQL web interface first
2. **Use PostEdit** (`p.bat alias`) to sync to local files
3. **Edit with modern IDEs** (VS Code, PhpStorm)
4. **Changes auto-sync** back to database
5. **Test in browser** immediately

This approach ensures your code works within WaSQL's database-driven architecture while providing modern development experience.