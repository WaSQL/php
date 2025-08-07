# WaSQL Architecture Documentation

## Overview

WaSQL is a PHP-driven, Rapid Application Development (RAD) platform that deploys pages and applications using a database-driven MVC architecture. Unlike traditional MVC frameworks, all page logic is stored in database records, providing enhanced security and portability.

## Core Architecture Principles

### Database-Driven MVC Pattern
- **All Logic in Database**: Pages, templates, functions, and controllers stored as database records
- **Enhanced Security**: Harder to hack since logic isn't in accessible files
- **Instant Portability**: Entire site can be moved via MySQL dump file
- **Multi-Language Support**: PHP, Node.js, Python, Perl, Ruby, VBScript, Lua, Bash, shell scripts

### The `_pages` Table Structure
The foundation of WaSQL's architecture. Each record represents a page:

| Field | Purpose | Content | Example |
|-------|---------|---------|---------|
| `_id` | Unique identifier | Auto-increment primary key | `1` |
| `name` | Page route/identifier | URL path | `"user/profile"`, `"api/users"` |
| `body` | View content | HTML with `<view:name>` blocks | Multiple view templates |
| `functions` | Model logic | PHP functions for data processing | `pageAddEdit()`, validation |
| `controller` | Request handling | Routing and form processing | `$PASSTHRU` switch statements |
| `js` | Client-side code | JavaScript for the page | Event handlers, AJAX |
| `css` | Styling | Page-specific CSS | Component styles |

## URL Routing System

### $PASSTHRU Array
WaSQL uses the global `$PASSTHRU` array for URL routing:

```
URL: /pagename/action/id/param/extra
Maps to: $PASSTHRU[0], $PASSTHRU[1], $PASSTHRU[2], $PASSTHRU[3]
```

**Controller Example:**
```php
global $PASSTHRU;
switch(strtolower($PASSTHRU[0])){
    case 'edit':
        $id = $PASSTHRU[1]; // Second URL segment
        $record = getDBRecord(['-table' => 'products', '_id' => $id]);
        setView('edit_form');
    break;
    
    case 'api':
        $endpoint = $PASSTHRU[1]; // /pagename/api/users
        handleApiRequest($endpoint);
        return;
    break;
    
    default:
        // Default view loads automatically if no setView() called
        $welcomeMessage = 'Welcome to our application';
    break;
}
```

## MVC Implementation

### Model (Functions Field)
```php
// Stored in _pages.functions field
function getUserById($id) {
    return getDBRecord([
        '-table' => 'users',
        '_id' => $id
    ]);
}

function validateUser($userData) {
    $errors = array();
    if(empty($userData['name'])) {
        $errors[] = 'Name is required';
    }
    if(!isEmail($userData['email'])) {
        $errors[] = 'Valid email is required';
    }
    return $errors;
}

function pageAddEdit($id = 0) {
    $opts = array(
        '-table' => 'users',
        '-fields' => getView('user_fields'),
        'name_options' => array('required' => 1),
        'email_options' => array('required' => 1, 'inputtype' => 'email')
    );
    if($id > 0) {
        $opts['_id'] = $id;
    }
    return addEditDBForm($opts); // Handles add/edit automatically
}
```

### View (Body Field)
```html
<!-- Stored in _pages.body field -->
<view:default>
<div class="user-profile">
    <h1><?=encodeHtml($username);?></h1>
    <?=renderViewIf(isset($userdata), 'user_details', $userdata, 'user');?>
    <?=renderViewIf($isAdmin, 'admin_controls', $adminData, 'data');?>
</div>
</view:default>

<view:user_details>
<div class="user-info">
    <p>Email: <?=encodeHtml($user['email']);?></p>
    <p>Role: <?=encodeHtml($user['role']);?></p>
</div>
</view:user_details>

<view:admin_controls>
<div class="admin-panel">
    <h3>Admin Controls</h3>
    <a href="#" data-nav="/admin/users" data-div="content" onclick="return wacss.nav(this);">
        Manage Users
    </a>
</div>
</view:admin_controls>
```

### Controller (Controller Field)
```php
// Stored in _pages.controller field
global $PASSTHRU, $USER;

switch(strtolower($PASSTHRU[0])){
    case 'update_profile':
        if($_REQUEST['action'] == 'update') {
            $userData = [
                'name' => $_REQUEST['name'],
                'email' => $_REQUEST['email']
            ];
            
            $errors = validateUser($userData);
            if(empty($errors)) {
                $result = editDBRecord([
                    '-table' => 'users',
                    '_id' => $USER['_id'],
                    'name' => $userData['name'],
                    'email' => $userData['email']
                ]);
                
                if(is_numeric($result)) {
                    $success = 'Profile updated successfully';
                    setView('profile_success');
                } else {
                    $error = $result;
                    $formData = $userData;
                    setView('profile_form');
                }
            } else {
                $errors = $errors;
                $formData = $userData;
                setView('profile_form');
            }
        } else {
            setView('profile_form');
        }
    break;
    
    default:
        $username = $USER['name'];
        $userdata = $USER;
        $isAdmin = ($USER['role'] == 'admin');
    break;
}
```

## View System Functions

### Core Rendering Functions
- `setView($viewName)` - Set which view to render
- `setView($viewName, 1)` - Reset and set view (clears previous setView calls)
- `renderView($viewName, $data, $varName)` - Render view with data using specified variable name
- `renderEach($template, $dataArray, $varName)` - Render template for each array item
- `renderViewIf($condition, $viewName, $data, $varName)` - Conditional rendering
- `renderViewIfElse($condition, $trueView, $falseView, $data, $varName)` - If/else rendering
- `getView($viewName)` - Get view content from current page's body field

### Data Flow
Variables set in the controller are automatically available in views **when using setView()**:

```php
// In Controller
$products = getDBRecords(['-table' => 'products']);
$message = 'Welcome to our store';
setView('product_list'); // Makes variables available to product_list view

// In Views - variables are directly available when using setView()
<?=renderEach('product_card', $products, 'product');?>
<?=renderViewIf(isset($message), 'welcome_msg', $message, 'msg');?>

// When using renderView() in the view body, you must pass variables explicitly
<?=renderView('myview', $recs, 'rec');?>
```

## Database Functions

### Core Database Operations
- `getDBRecord($params)` - Get single record
- `getDBRecords($params)` - Get multiple records
- `addDBRecord($params)` - Insert new record
- `editDBRecord($params)` - Update existing record
- `delDBRecord($params)` - Delete record

### Example Usage
```php
// Get state by ID
$state = getDBRecord([
    '-table' => 'states',
    '_id' => 123
]);

// Get all active products
$products = getDBRecords([
    '-table' => 'products',
    'active' => 1,
    '-limit' => 50,
    '-order' => 'name'
]);

// Add new product
$newProductId = addDBRecord([
    '-table' => 'products',
    'name' => 'New Product',
    'price' => 29.99,
    'active' => 1
]);

// Update product
editDBRecord([
    '-table' => 'products',
    '_id' => $newProductId,
    'price' => 24.99
]);
```

## AJAX Navigation System

WaSQL includes a built-in AJAX navigation system using `wacss.nav()`:

### Basic AJAX Links
```html
<a href="#" data-nav="/products/list" data-div="content" onclick="return wacss.nav(this);">
    Load Products
</a>

<div id="content">
    <!-- AJAX response loads here -->
</div>
```

### AJAX Forms
```html
<form method="post" action="/t/1/products/save" data-setprocessing="result" onsubmit="return wacss.ajaxPost(this,'result');">
    <input type="text" name="name" required>
    <input type="number" name="price" step="0.01" required>
    <button type="submit">Save Product</button>
</form>
<div id="result">
    <!-- Form response loads here -->
</div>
```

### Navigation Attributes
- `data-nav="/path/to/endpoint"` - Specifies the URL to call
- `data-div="target_id"` - Specifies which DOM element to update with response
- `onclick="return wacss.nav(this);"` - For links
- `onsubmit="return wacss.nav(this);"` - For forms

## Development Workflow

### PostEdit System
Local development tool that synchronizes database records with local files.

#### Configuration (`postedit/postedit.xml`)
```xml
<host
    name="dev.mysite.com"
    alias="dev"
    apikey="YOUR_API_KEY"
    username="YOUR_USERNAME"
    groupby="name"
    tables="_triggers,_templates,_pages,articles,queries"
/>
```

#### File Structure
```
postedit/
├── posteditfiles/
│   └── {alias}/              # e.g., "dev"
│       └── {table}/          # e.g., "_pages"
│           └── {record_name}/ # e.g., "about"
│               ├── about._pages.body.13.html
│               ├── about._pages.controller.13.php
│               ├── about._pages.css.13.css
│               ├── about._pages.functions.13.php
│               └── about._pages.js.13.js
```

#### File Naming Convention
`{name}.{table}.{field}.{_id}.{extension}`

#### Usage
```bash
# Start PostEdit synchronization
p.bat dev    # Uses PHP
py.bat dev   # Uses Python

# Creates local files and monitors for changes
# Automatically syncs changes back to database
```

### Environment Synchronization
WaSQL provides built-in synchronization between environments:

```
Local Development (PostEdit)
    ↓
Dev Environment (dev.site.com)
    ↓ [Synchronize Feature]
Stage Environment (stage.site.com)
    ↓ [Testing & QA]
Production (www.site.com)
```

Features:
- **Record-level sync**: Push individual changed records
- **Diff comparison**: Visual comparison between environments
- **Selective deployment**: Choose specific changes to promote
- **Conflict detection**: Prevents overwriting concurrent changes

## Page Lifecycle

1. **Request arrives** via Apache/mod_rewrite
2. **Router** looks up page in `_pages` table by name
3. **Controller** code executes (from `controller` field)
4. **Functions** (model) process data as needed (from `functions` field)
5. **View** renders using content from `body` field
6. **CSS/JS** included from respective fields
7. **Response** sent to browser

## Built-in User Management

WaSQL includes comprehensive user management:

### Global $USER Variable
```php
global $USER;
$userName = $USER['name'];
$userRole = $USER['role'];
$userEmail = $USER['email'];
$userId = $USER['_id'];
```

### Authentication Functions
```php
// Check if user is logged in
if(isUser()) {
    // User is authenticated
}
```

### User Management Functions
```php
// Get user by ID
$user = getDBUserById(123);

// Create new user
$newUserId = addDBRecord([
    '-table' => '_users',
    'utype' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'secure_password'
]);

// Update user
$ok = editDBRecordById('_users', $userId, [
    'name' => 'Jane Doe',
    'email' => 'jane@example.com'
]);
```

## Security Features

- **Database-stored logic**: Harder to hack than file-based systems
- **Built-in user management**: Authentication and authorization
- **Input validation**: Comprehensive validation functions
- **SQL injection protection**: Parameterized queries through WaSQL functions
- **Session management**: Secure session handling
- **XSS Protection**: Use `encodeHtml()` for all user output

## Multi-Language Support

WaSQL supports multiple programming languages within the same application using shortcode syntax:

```html
<!-- PHP code (primary) -->
<?php
$users = getDBRecords(['-table' => 'users']);
$message = 'Processing complete';
?>

<!-- Python code (embedded) -->
<?py
import json
user_data = json.loads($_REQUEST['user_data'])
print(f"Processing user: {user_data['name']}")
?>

<!-- Node.js code (embedded) -->
<?js
const userData = JSON.parse($_REQUEST.user_data);
console.log(`Processing user: ${userData.name}`);
?>
```

All languages receive the same `$_REQUEST`, `$_SESSION`, `$_SERVER`, and `$USER` variables.

## File Structure

```
wasql/
├── php/
│   ├── database.php          # Database functions
│   ├── common.php            # View and utility functions
│   ├── user.php              # User management
│   └── wasql.php             # Core framework
├── postedit/
│   ├── postedit.php          # Local development sync tool
│   ├── postedit.xml          # Host configurations
│   └── posteditfiles/        # Synchronized local files
├── wfiles/                   # File storage
├── config.xml               # Configuration
├── p.bat                    # PostEdit launcher (PHP)
├── py.bat                   # PostEdit launcher (Python)
└── .htaccess               # URL routing
```

## Best Practices

### Page Organization
- Use descriptive page names (`user/profile`, `admin/settings`)
- Organize related pages hierarchically
- Separate complex logic into reusable functions

### Development Workflow
1. **Create records** via WaSQL web interface
2. **Sync locally** using PostEdit (`p.bat alias`)
3. **Edit with modern tools** (VS Code, PhpStorm, etc.)
4. **Test in dev environment**
5. **Promote through stages** using synchronization
6. **Deploy to production**

### Variable Management
- Set variables directly in controller: `$var = 'value';`
- Variables are automatically available in views
- Use `renderViewIf()` for conditional content with view blocks

### Security
- Use `encodeHtml()` for all user output
- Validate all inputs in controller logic
- Use WaSQL's built-in user management
- Leverage database-stored logic for enhanced security

### Performance
- Use appropriate database indexes
- Leverage WaSQL's built-in caching
- Minimize database queries in views
- Use `renderEach()` for repeated elements

This architecture provides a unique approach that combines the security and portability of database-driven development with the productivity of modern development tools and AJAX navigation.