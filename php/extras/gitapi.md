# Git API Library Documentation

A production-ready PHP library providing procedural functions for common Git API operations across GitHub, GitLab, and Bitbucket.

## Table of Contents

- [Overview](#overview)
- [Configuration](#configuration)
- [Getting API Tokens](#getting-api-tokens)
  - [GitHub Personal Access Token](#github-personal-access-token)
  - [GitLab Personal Access Token](#gitlab-personal-access-token)
  - [Bitbucket App Password](#bitbucket-app-password)
- [Finding Repository Owner and Name](#finding-repository-owner-and-name)
  - [GitHub Repository Info](#github-repository-info)
  - [GitLab Repository Info](#gitlab-repository-info)
  - [Bitbucket Repository Info](#bitbucket-repository-info)
- [Configuration Examples](#configuration-examples)
- [Available Functions](#available-functions)
  - [Repository Information](#repository-information)
  - [File Operations](#file-operations)
  - [Branch Operations](#branch-operations)
  - [Tag Operations](#tag-operations)
  - [Commit Operations](#commit-operations)
- [Usage Examples](#usage-examples)
- [Error Handling](#error-handling)

## Overview

The Git API library provides a unified interface for interacting with GitHub, GitLab, and Bitbucket repositories via their REST APIs. All functions use CURL for HTTP requests and return standardized response arrays.

**Supported Providers:**
- GitHub (api.github.com)
- GitLab (gitlab.com/api/v4)
- Bitbucket (api.bitbucket.org/2.0)

## Configuration

Configure the library using the global `$CONFIG` array with the following keys:

| Config Key | Description | Required | Default |
|------------|-------------|----------|---------|
| `gitapi_provider` | Provider name: 'github', 'gitlab', or 'bitbucket' | No | 'github' |
| `gitapi_token` | Personal access token for authentication | **Yes** | None |
| `gitapi_baseurl` | API base URL (for self-hosted instances) | No | Provider default |
| `gitapi_owner` | Repository owner/organization name | **Yes** | None |
| `gitapi_repo` | Repository name | **Yes** | None |
| `gitapi_branch` | Default branch name | No | 'main' |
| `gitapi_ssl_verify` | Verify SSL certificates (set to `false` to disable) | No | `true` |

### SSL Certificate Verification

By default, the library verifies SSL certificates for security. If you're using self-signed certificates or encountering "unable to get local issuer certificate" errors, you can disable SSL verification:

```php
global $CONFIG;
$CONFIG['gitapi_ssl_verify'] = false;  // Disable SSL verification
```

**Security Warning:** Disabling SSL verification makes your connection vulnerable to man-in-the-middle attacks. Only disable this for:
- Development/testing environments with self-signed certificates
- Internal networks where certificate validation is not possible
- Never disable in production with public APIs unless absolutely necessary

**Better Alternative:** Instead of disabling SSL verification, install the proper CA certificates:
- **Windows:** Update your `cacert.pem` file and configure PHP's `curl.cainfo` in `php.ini`
- **Linux:** Update system CA certificates with `update-ca-certificates`
- **Self-signed certs:** Add your certificate to the trusted CA bundle

## Getting API Tokens

### GitHub Personal Access Token

1. **Navigate to Settings**
   - Go to [GitHub.com](https://github.com)
   - Click your profile picture (top-right) → **Settings**

2. **Access Developer Settings**
   - Scroll down to **Developer settings** (bottom of left sidebar)
   - Click **Personal access tokens** → **Tokens (classic)**

3. **Generate New Token**
   - Click **Generate new token** → **Generate new token (classic)**
   - Enter a descriptive note (e.g., "WaSQL API Access")

4. **Select Scopes (Permissions)**
   - For full repository access: check **`repo`** (includes all sub-scopes)
   - For public repositories only: check **`public_repo`**
   - Additional recommended scopes:
     - `read:user` - Read user profile data
     - `user:email` - Access user email addresses

5. **Generate and Copy**
   - Click **Generate token**
   - **Copy the token immediately** (you won't see it again)
   - Store it securely

**Direct Link:** [https://github.com/settings/tokens](https://github.com/settings/tokens)

### GitLab Personal Access Token

1. **Navigate to Settings**
   - Go to [GitLab.com](https://gitlab.com)
   - Click your profile picture (top-right) → **Preferences**

2. **Access Tokens**
   - In the left sidebar, click **Access Tokens**

3. **Create Personal Access Token**
   - Enter a **Token name** (e.g., "WaSQL API")
   - Set an **Expiration date** (optional but recommended)

4. **Select Scopes**
   - `api` - Full API access (recommended for full functionality)
   - Or select specific scopes:
     - `read_api` - Read-only API access
     - `read_repository` - Read repository data
     - `write_repository` - Write repository data

5. **Create and Copy**
   - Click **Create personal access token**
   - **Copy the token immediately**
   - Store it securely

**Direct Link:** [https://gitlab.com/-/profile/personal_access_tokens](https://gitlab.com/-/profile/personal_access_tokens)

### Bitbucket App Password

1. **Navigate to Settings**
   - Go to [Bitbucket.org](https://bitbucket.org)
   - Click your profile picture (bottom-left) → **Personal settings**

2. **Access App Passwords**
   - In the left sidebar under **Access management**, click **App passwords**

3. **Create App Password**
   - Click **Create app password**
   - Enter a **Label** (e.g., "WaSQL API")

4. **Select Permissions**
   - **Repositories:**
     - `Read` - View repository data
     - `Write` - Modify repository files
     - `Admin` - Full repository control
   - **Pull requests:**
     - `Read` and `Write` if needed

5. **Create and Copy**
   - Click **Create**
   - **Copy the password immediately**
   - Store it securely

**Direct Link:** [https://bitbucket.org/account/settings/app-passwords/](https://bitbucket.org/account/settings/app-passwords/)

## Finding Repository Owner and Name

The `gitapi_owner` and `gitapi_repo` configuration values are found in your repository URL. Here's how to identify them for each provider:

### GitHub Repository Info

**Repository URL Format:**
```
https://github.com/{owner}/{repo}
```

**Example URL:**
```
https://github.com/torvalds/linux
```
- **Owner:** `torvalds` (user or organization name)
- **Repo:** `linux` (repository name)

**How to Find:**

1. **From Repository Page:**
   - Navigate to your repository on GitHub
   - Look at the URL in your browser's address bar
   - The format is always: `github.com/{owner}/{repo}`
   - Example: `github.com/microsoft/vscode`
     - Owner: `microsoft`
     - Repo: `vscode`

2. **From Repository Header:**
   - At the top of any repository page, you'll see: `{owner} / {repo}`
   - Click on the owner name to see their profile
   - The repo name is shown immediately after the slash

3. **Personal vs Organization Repositories:**
   - **Personal repository:** Owner is your username
     - Example: `github.com/john-doe/my-project`
     - Owner: `john-doe`
   - **Organization repository:** Owner is the organization name
     - Example: `github.com/my-company/company-website`
     - Owner: `my-company`

**Configuration:**
```php
// For https://github.com/facebook/react
$CONFIG['gitapi_owner'] = 'facebook';
$CONFIG['gitapi_repo'] = 'react';
```

---

### GitLab Repository Info

**Repository URL Format:**
```
https://gitlab.com/{owner}/{repo}
```

**Example URL:**
```
https://gitlab.com/gitlab-org/gitlab
```
- **Owner:** `gitlab-org` (user, group, or organization name)
- **Repo:** `gitlab` (repository/project name)

**How to Find:**

1. **From Project Page:**
   - Navigate to your project on GitLab
   - Look at the URL in your browser's address bar
   - The format is: `gitlab.com/{owner}/{repo}`
   - Example: `gitlab.com/fdroid/fdroidclient`
     - Owner: `fdroid`
     - Repo: `fdroidclient`

2. **From Project Header:**
   - At the top of the project page, you'll see the full path
   - Format: `{owner} / {repo}`
   - For nested groups: `{group} / {subgroup} / {repo}`

3. **For Nested Groups:**
   - GitLab supports nested groups/subgroups
   - Example: `gitlab.com/my-company/frontend/main-app`
     - Owner: `my-company/frontend` (include full path)
     - Repo: `main-app`
   - **Important:** Use URL encoding for the owner in API calls
     - The library handles this automatically with `urlencode()`

4. **From Project Settings:**
   - Go to **Settings** → **General**
   - Under **Project name**, you'll see:
     - **Project name:** The repo name
     - **Project ID:** Numeric ID (alternative to owner/repo)

**Configuration:**
```php
// For https://gitlab.com/inkscape/inkscape
$CONFIG['gitapi_owner'] = 'inkscape';
$CONFIG['gitapi_repo'] = 'inkscape';

// For nested groups: https://gitlab.com/company/team/project
$CONFIG['gitapi_owner'] = 'company/team';
$CONFIG['gitapi_repo'] = 'project';
```

**Self-Hosted GitLab:**
```php
// For https://gitlab.mycompany.com/engineering/backend-api
$CONFIG['gitapi_baseurl'] = 'https://gitlab.mycompany.com/api/v4';
$CONFIG['gitapi_owner'] = 'engineering';
$CONFIG['gitapi_repo'] = 'backend-api';
```

---

### Bitbucket Repository Info

**Repository URL Format:**
```
https://bitbucket.org/{workspace}/{repo}
```

**Example URL:**
```
https://bitbucket.org/atlassian/python-bitbucket
```
- **Owner:** `atlassian` (workspace name)
- **Repo:** `python-bitbucket` (repository slug)

**How to Find:**

1. **From Repository Page:**
   - Navigate to your repository on Bitbucket
   - Look at the URL in your browser's address bar
   - The format is: `bitbucket.org/{workspace}/{repo-slug}`
   - Example: `bitbucket.org/my-team/website-project`
     - Owner: `my-team` (workspace name)
     - Repo: `website-project` (repository slug)

2. **From Repository Header:**
   - At the top of the repository page, you'll see: `{workspace} / {repo}`
   - The workspace is the first part
   - The repository name is the second part

3. **Understanding Workspaces:**
   - **Workspace** is Bitbucket's term for what GitHub calls "owner"
   - A workspace can be:
     - Your personal workspace (same as your username)
     - A team/organization workspace
   - All repositories belong to a workspace

4. **From Repository Settings:**
   - Go to **Repository settings**
   - Look for:
     - **Workspace:** The owner value
     - **Repository slug:** The repo value (URL-friendly name)
     - **Repository name:** Display name (may differ from slug)

5. **Important Note:**
   - Use the **repository slug** (URL name), not the display name
   - The slug is always lowercase with hyphens
   - Example:
     - Display name: "My Website Project"
     - Repository slug: "my-website-project" ← Use this

**Configuration:**
```php
// For https://bitbucket.org/tutorials/tutorials.git.bitbucket.org
$CONFIG['gitapi_owner'] = 'tutorials';  // workspace
$CONFIG['gitapi_repo'] = 'tutorials.git.bitbucket.org';  // repository slug
```

---

## Quick Reference: Finding Your Values

| Provider | Owner Name | Repository Name | Where to Find |
|----------|-----------|-----------------|---------------|
| **GitHub** | User or organization | Repository name | URL: `github.com/{owner}/{repo}` |
| **GitLab** | User, group, or namespace | Project name | URL: `gitlab.com/{owner}/{repo}` |
| **Bitbucket** | Workspace name | Repository slug | URL: `bitbucket.org/{owner}/{repo}` |

**Pro Tip:** The easiest way to find both values is to simply look at your repository's URL in the browser address bar and extract the two parts after the domain.

## Configuration Examples

### GitHub Configuration

```php
global $CONFIG;

$CONFIG['gitapi_provider'] = 'github';
$CONFIG['gitapi_token'] = 'ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
$CONFIG['gitapi_owner'] = 'myusername';
$CONFIG['gitapi_repo'] = 'my-repository';
$CONFIG['gitapi_branch'] = 'main';
```

### GitLab Configuration

```php
global $CONFIG;

$CONFIG['gitapi_provider'] = 'gitlab';
$CONFIG['gitapi_token'] = 'glpat-xxxxxxxxxxxxxxxxxxxx';
$CONFIG['gitapi_owner'] = 'myusername';
$CONFIG['gitapi_repo'] = 'my-repository';
$CONFIG['gitapi_branch'] = 'main';
```

### GitLab Self-Hosted Configuration

```php
global $CONFIG;

$CONFIG['gitapi_provider'] = 'gitlab';
$CONFIG['gitapi_baseurl'] = 'https://gitlab.mycompany.com/api/v4';
$CONFIG['gitapi_token'] = 'glpat-xxxxxxxxxxxxxxxxxxxx';
$CONFIG['gitapi_owner'] = 'myusername';
$CONFIG['gitapi_repo'] = 'my-repository';
$CONFIG['gitapi_branch'] = 'main';
// Disable SSL verification for self-signed certificates (use with caution)
// $CONFIG['gitapi_ssl_verify'] = false;
```

### Bitbucket Configuration

```php
global $CONFIG;

$CONFIG['gitapi_provider'] = 'bitbucket';
$CONFIG['gitapi_token'] = 'ATBBxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
$CONFIG['gitapi_owner'] = 'myworkspace';
$CONFIG['gitapi_repo'] = 'my-repository';
$CONFIG['gitapi_branch'] = 'main';
```

## Available Functions

All functions return an array with the following structure:

```php
array(
    'success' => true/false,      // Operation success status
    'data' => mixed,              // Response data (decoded JSON or raw)
    'error' => string/null,       // Error message if failed
    'status_code' => int,         // HTTP status code
    'headers' => array            // Response headers
)
```

### Repository Information

#### gitapiStatus($params = array())

Get repository status and information.

**Parameters:**
- `owner` - Repository owner (optional, uses config)
- `repo` - Repository name (optional, uses config)
- `provider` - Git provider (optional, uses config)

**Returns:** Repository information including name, description, URL, stars, forks, etc.

**Example:**
```php
$result = gitapiStatus();
if ($result['success']) {
    echo "Repository: " . $result['data']['name'];
    echo "Stars: " . $result['data']['stargazers_count'];
}
```

---

#### gitapiBranches($params = array())

List all branches in repository.

**Parameters:**
- `owner` - Repository owner (optional)
- `repo` - Repository name (optional)
- `provider` - Git provider (optional)
- `per_page` - Results per page (default: 30)
- `page` - Page number (default: 1)

**Returns:** Array of branches with names and commit SHAs.

**Example:**
```php
$result = gitapiBranches();
if ($result['success']) {
    foreach ($result['data'] as $branch) {
        echo $branch['name'] . "\n";
    }
}
```

---

#### gitapiTags($params = array())

List all tags in repository.

**Parameters:**
- Same as `gitapiBranches()`

**Returns:** Array of tags with names and commit SHAs.

---

#### gitapiCommits($params = array())

Get commits for repository or branch.

**Parameters:**
- `owner` - Repository owner (optional)
- `repo` - Repository name (optional)
- `provider` - Git provider (optional)
- `sha` - Branch/commit SHA (optional, default: default branch)
- `per_page` - Results per page (default: 30)
- `page` - Page number (default: 1)

**Returns:** Array of commits with messages, authors, dates, and SHAs.

**Example:**
```php
$result = gitapiCommits(array('sha' => 'develop', 'per_page' => 10));
if ($result['success']) {
    foreach ($result['data'] as $commit) {
        echo $commit['commit']['message'] . "\n";
    }
}
```

---

#### gitapiGetCommit($sha, $params = array())

Get a specific commit by SHA.

**Parameters:**
- `sha` - Commit SHA (required)
- `owner`, `repo`, `provider` - Optional overrides

**Returns:** Detailed commit information including files changed, additions, deletions.

---

#### gitapiCompare($base, $head, $params = array())

Compare two commits or branches.

**Parameters:**
- `base` - Base branch/commit SHA (required)
- `head` - Head branch/commit SHA (required)
- `owner`, `repo`, `provider` - Optional overrides

**Returns:** Comparison data including commits between and files changed.

**Example:**
```php
$result = gitapiCompare('main', 'develop');
if ($result['success']) {
    echo "Commits ahead: " . $result['data']['ahead_by'];
    echo "Commits behind: " . $result['data']['behind_by'];
}
```

---

### File Operations

#### gitapiGetFile($filePath, $params = array())

Get file contents from repository.

**Parameters:**
- `filePath` - Path to file in repository (required)
- `owner`, `repo`, `provider` - Optional overrides
- `branch` - Branch name (default: config branch)
- `ref` - Git reference (commit SHA, branch, tag)

**Returns:** File data including content (base64 encoded for GitHub), SHA, size.

**Example:**
```php
$result = gitapiGetFile('README.md');
if ($result['success']) {
    // GitHub returns base64 encoded content
    $content = base64_decode($result['data']['content']);
    echo $content;
}
```

---

#### gitapiCreateFile($filePath, $content, $message, $params = array())

Create a new file in repository.

**Parameters:**
- `filePath` - Path for new file (required)
- `content` - File content (required)
- `message` - Commit message (required)
- `branch` - Branch name (default: config branch)
- `author_name` - Commit author name (optional)
- `author_email` - Commit author email (optional)

**Returns:** Created file data.

**Example:**
```php
$result = gitapiCreateFile(
    'docs/api.md',
    '# API Documentation',
    'Add API documentation',
    array('branch' => 'develop')
);
if ($result['success']) {
    echo "File created successfully";
}
```

---

#### gitapiUpdateFile($filePath, $content, $message, $params = array())

Update existing file in repository.

**Parameters:**
- `filePath` - Path to file (required)
- `content` - New file content (required)
- `message` - Commit message (required)
- `sha` - Current file SHA (required for GitHub)
- `branch` - Branch name (default: config branch)
- `author_name`, `author_email` - Optional

**Returns:** Updated file data.

**Example:**
```php
// First, get the current file to obtain its SHA (GitHub requirement)
$current = gitapiGetFile('README.md');
$sha = $current['data']['sha'];

// Now update the file
$result = gitapiUpdateFile(
    'README.md',
    '# Updated README\n\nNew content here.',
    'Update README with new information',
    array('sha' => $sha)
);
if ($result['success']) {
    echo "File updated successfully";
}
```

---

#### gitapiDeleteFile($filePath, $message, $params = array())

Delete file from repository.

**Parameters:**
- `filePath` - Path to file (required)
- `message` - Commit message (required)
- `sha` - Current file SHA (required for GitHub)
- `branch` - Branch name (default: config branch)
- `author_name`, `author_email` - Optional

**Returns:** Delete confirmation.

**Example:**
```php
$current = gitapiGetFile('old-file.txt');
$result = gitapiDeleteFile(
    'old-file.txt',
    'Remove obsolete file',
    array('sha' => $current['data']['sha'])
);
```

---

### Branch Operations

#### gitapiCreateBranch($branchName, $params = array())

Create a new branch.

**Parameters:**
- `branchName` - Name for new branch (required)
- `sha` - Commit SHA to branch from (required for GitHub)
- `from` - Source branch name (alternative for GitLab)
- `owner`, `repo`, `provider` - Optional overrides

**Returns:** Created branch data.

**Example:**
```php
// Get the latest commit SHA from main branch
$commits = gitapiCommits(array('sha' => 'main', 'per_page' => 1));
$latestSHA = $commits['data'][0]['sha'];

// Create new branch
$result = gitapiCreateBranch('feature-xyz', array('sha' => $latestSHA));
if ($result['success']) {
    echo "Branch created successfully";
}
```

---

#### gitapiDeleteBranch($branchName, $params = array())

Delete a branch.

**Parameters:**
- `branchName` - Name of branch to delete (required)
- `owner`, `repo`, `provider` - Optional overrides

**Returns:** Delete confirmation.

**Example:**
```php
$result = gitapiDeleteBranch('old-feature');
if ($result['success']) {
    echo "Branch deleted successfully";
}
```

---

#### gitapiMergeBranch($sourceBranch, $targetBranch, $message, $params = array())

Merge a branch.

**Parameters:**
- `sourceBranch` - Source branch to merge from (required)
- `targetBranch` - Target branch to merge into (required)
- `message` - Merge commit message (required)
- `owner`, `repo`, `provider` - Optional overrides

**Returns:** Merge result.

**Note:** GitLab requires merge request creation rather than direct merge.

**Example:**
```php
$result = gitapiMergeBranch(
    'feature-xyz',
    'main',
    'Merge feature XYZ into main'
);
if ($result['success']) {
    echo "Merge successful";
}
```

---

#### gitapiCheckout($branchName, $params = array())

Checkout (switch to) a branch - updates the default branch reference.

**Note:** Git APIs don't have a direct "checkout" concept. This function updates the repository's default branch.

**Parameters:**
- `branchName` - Branch name to checkout (required)
- `owner`, `repo`, `provider` - Optional overrides

---

### Tag Operations

#### gitapiCreateTag($tagName, $sha, $params = array())

Create a new tag.

**Parameters:**
- `tagName` - Name for new tag (required)
- `sha` - Commit SHA to tag (required)
- `message` - Tag message (optional, for annotated tags)
- `owner`, `repo`, `provider` - Optional overrides

**Returns:** Created tag data.

**Example:**
```php
$commits = gitapiCommits(array('per_page' => 1));
$latestSHA = $commits['data'][0]['sha'];

$result = gitapiCreateTag(
    'v1.0.0',
    $latestSHA,
    array('message' => 'Release version 1.0.0')
);
```

---

#### gitapiDeleteTag($tagName, $params = array())

Delete a tag.

**Parameters:**
- `tagName` - Name of tag to delete (required)
- `owner`, `repo`, `provider` - Optional overrides

**Returns:** Delete confirmation.

---

### Commit Operations

#### gitapiCommit($message, $actions, $params = array())

Create a new commit with multiple file changes (GitLab only).

**Parameters:**
- `message` - Commit message (required)
- `actions` - Array of file actions (required), each containing:
  - `action` - 'create', 'update', 'delete', or 'move'
  - `file_path` - Path to file
  - `content` - File content (for create/update)
  - `previous_path` - Previous path (for move)
- `branch` - Branch name (default: config branch)
- `author_name`, `author_email` - Optional

**Returns:** Commit data.

**Note:** GitHub doesn't support multi-file commits via this API. Use individual file operations instead.

**Example (GitLab):**
```php
$actions = array(
    array(
        'action' => 'create',
        'file_path' => 'docs/guide.md',
        'content' => '# User Guide'
    ),
    array(
        'action' => 'update',
        'file_path' => 'README.md',
        'content' => '# Updated README'
    ),
    array(
        'action' => 'delete',
        'file_path' => 'old-file.txt'
    )
);

$result = gitapiCommit('Update documentation', $actions);
```

---

#### gitapiPush($params = array())

Check branch status (commits are pushed automatically via API).

**Note:** Git APIs automatically push commits when created. This function checks branch status.

**Parameters:**
- `branch` - Branch name (default: config branch)
- `owner`, `repo`, `provider` - Optional overrides

---

#### gitapiPull($params = array())

Fetch latest commits for a branch.

**Note:** This is essentially an alias for `gitapiCommits()`.

**Parameters:**
- Same as `gitapiCommits()`

---

## Usage Examples

### Example 1: List Recent Commits

```php
<?php
require_once('gitapi.php');

global $CONFIG;
$CONFIG['gitapi_provider'] = 'github';
$CONFIG['gitapi_token'] = 'your_token_here';
$CONFIG['gitapi_owner'] = 'yourusername';
$CONFIG['gitapi_repo'] = 'your-repo';

$result = gitapiCommits(array('per_page' => 5));

if ($result['success']) {
    echo "Recent commits:\n";
    foreach ($result['data'] as $commit) {
        echo "- " . $commit['commit']['message'] . "\n";
        echo "  by " . $commit['commit']['author']['name'] . "\n";
        echo "  on " . $commit['commit']['author']['date'] . "\n\n";
    }
} else {
    echo "Error: " . $result['error'];
}
?>
```

### Example 2: Create and Update a File

```php
<?php
require_once('gitapi.php');

// Configuration
global $CONFIG;
$CONFIG['gitapi_provider'] = 'github';
$CONFIG['gitapi_token'] = 'your_token_here';
$CONFIG['gitapi_owner'] = 'yourusername';
$CONFIG['gitapi_repo'] = 'your-repo';

// Create a new file
$result = gitapiCreateFile(
    'config/settings.json',
    '{"version": "1.0", "debug": false}',
    'Add initial settings file'
);

if ($result['success']) {
    echo "File created successfully\n";
    $fileSHA = $result['data']['content']['sha'];

    // Update the file
    $updateResult = gitapiUpdateFile(
        'config/settings.json',
        '{"version": "1.1", "debug": true}',
        'Enable debug mode',
        array('sha' => $fileSHA)
    );

    if ($updateResult['success']) {
        echo "File updated successfully\n";
    }
} else {
    echo "Error: " . $result['error'];
}
?>
```

### Example 3: Create Feature Branch Workflow

```php
<?php
require_once('gitapi.php');

// Configuration
global $CONFIG;
$CONFIG['gitapi_provider'] = 'github';
$CONFIG['gitapi_token'] = 'your_token_here';
$CONFIG['gitapi_owner'] = 'yourusername';
$CONFIG['gitapi_repo'] = 'your-repo';

// Get latest commit from main
$commits = gitapiCommits(array('sha' => 'main', 'per_page' => 1));
if (!$commits['success']) {
    die("Error getting commits: " . $commits['error']);
}
$latestSHA = $commits['data'][0]['sha'];

// Create feature branch
$branch = gitapiCreateBranch('feature-new-api', array('sha' => $latestSHA));
if (!$branch['success']) {
    die("Error creating branch: " . $branch['error']);
}
echo "Branch created: feature-new-api\n";

// Create file on the new branch
$file = gitapiCreateFile(
    'api/endpoint.php',
    '<?php echo "New API endpoint"; ?>',
    'Add new API endpoint',
    array('branch' => 'feature-new-api')
);

if ($file['success']) {
    echo "File created on feature branch\n";

    // Merge back to main when ready
    $merge = gitapiMergeBranch(
        'feature-new-api',
        'main',
        'Merge new API endpoint feature'
    );

    if ($merge['success']) {
        echo "Feature merged to main\n";
    }
}
?>
```

### Example 4: Compare Branches

```php
<?php
require_once('gitapi.php');

global $CONFIG;
$CONFIG['gitapi_provider'] = 'gitlab';
$CONFIG['gitapi_token'] = 'your_token_here';
$CONFIG['gitapi_owner'] = 'yourusername';
$CONFIG['gitapi_repo'] = 'your-repo';

$result = gitapiCompare('main', 'develop');

if ($result['success']) {
    echo "Comparison: main vs develop\n";
    echo "Commits ahead: " . $result['data']['ahead_by'] . "\n";
    echo "Commits behind: " . $result['data']['behind_by'] . "\n";
    echo "Files changed: " . count($result['data']['files']) . "\n";

    echo "\nCommits in develop:\n";
    foreach ($result['data']['commits'] as $commit) {
        echo "- " . $commit['commit']['message'] . "\n";
    }
}
?>
```

### Example 5: WaSQL Integration

```php
<?php
// In WaSQL config.xml, add:
// <gitapi_provider>github</gitapi_provider>
// <gitapi_token>your_token_here</gitapi_token>
// <gitapi_owner>yourusername</gitapi_owner>
// <gitapi_repo>your-repo</gitapi_repo>

require_once('/path/to/extras/gitapi.php');

// In your WaSQL page controller:
global $CONFIG;

// Get repository status
$status = gitapiStatus();
if ($status['success']) {
    $repoInfo = $status['data'];
}

// List branches
$branches = gitapiBranches();
if ($branches['success']) {
    $branchList = $branches['data'];
}

// In your WaSQL page body:
?>
<h2>Repository: <?=encodeHtml($repoInfo['name']);?></h2>
<p><?=encodeHtml($repoInfo['description']);?></p>

<h3>Branches</h3>
<?=renderEach('branch_row', $branchList, 'branch');?>

<view:branch_row>
<div class="branch">
    <?=encodeHtml($branch['name']);?>
</div>
</view:branch_row>
```

## Error Handling

All functions return standardized error information:

```php
$result = gitapiGetFile('nonexistent.txt');

if (!$result['success']) {
    echo "Error: " . $result['error'] . "\n";
    echo "Status Code: " . $result['status_code'] . "\n";

    // Common status codes:
    // 401 - Authentication failed (check token)
    // 403 - Forbidden (check permissions/scopes)
    // 404 - Not found (check owner/repo/file path)
    // 422 - Validation failed (check required parameters)
}
```

**Common Errors:**

1. **Authentication token not provided**
   - Solution: Set `$CONFIG['gitapi_token']`

2. **Repository owner and name required**
   - Solution: Set `$CONFIG['gitapi_owner']` and `$CONFIG['gitapi_repo']`

3. **File SHA required for update/delete (GitHub)**
   - Solution: Get file first with `gitapiGetFile()` to obtain SHA

4. **Commit SHA required**
   - Solution: Get commits with `gitapiCommits()` to obtain SHA

5. **401 Unauthorized**
   - Solution: Check token validity and permissions

6. **404 Not Found**
   - Solution: Verify repository owner, name, and file paths

## Best Practices

1. **Always check `success` status** before accessing `data`
2. **Store tokens securely** - never commit them to version control
3. **Use environment variables** or encrypted config for production
4. **Implement rate limiting** - Git APIs have rate limits
5. **Cache responses** when appropriate to reduce API calls
6. **Get file SHA before updating** (required for GitHub)
7. **Test with a test repository** before production use
8. **Handle errors gracefully** with user-friendly messages

## Token Security

Never expose tokens in code:

```php
// BAD - Token in code
$CONFIG['gitapi_token'] = 'ghp_1234567890';

// GOOD - Token from environment
$CONFIG['gitapi_token'] = getenv('GIT_API_TOKEN');

// GOOD - Token from encrypted config
$CONFIG['gitapi_token'] = decryptConfigValue('gitapi_token');
```

## Support

For issues or questions about the Git API library:
- Review function documentation in `gitapi.php`
- Check provider API documentation:
  - [GitHub REST API](https://docs.github.com/en/rest)
  - [GitLab API](https://docs.gitlab.com/ee/api/)
  - [Bitbucket API](https://developer.atlassian.com/cloud/bitbucket/rest/)
