# Git API Configuration Guide

## Overview

The WaSQL Git management system has been updated to use **Git hosting APIs** (GitHub, GitLab, Bitbucket) for remote operations instead of local git commands. This provides a cloud-first workflow with direct API integration.

## Workflow Changes

### What Uses Local Git (Exceptions)
- **git status** - Shows local file changes
- **File scanning** - Lists modified files in working directory

### What Uses API Calls
- **Pull** - Fetches latest commits from remote via API
- **Push/Commit** - Uploads files directly to remote via API
- **Diff** - Compares local files with remote versions via API
- **Log** - Gets commit history via API
- **Repository Status** - Gets remote repository info via API

## Required Configuration

Add these settings to your `$CONFIG` array in your configuration file:

```php
// Git API Provider: 'github', 'gitlab', or 'bitbucket'
$CONFIG['gitapi_provider'] = 'github';

// Personal Access Token for authentication
// GitHub: Settings → Developer settings → Personal access tokens → Generate new token
// GitLab: Settings → Access Tokens → Add new token
// Bitbucket: Settings → Personal access tokens → Create token
$CONFIG['gitapi_token'] = 'your_personal_access_token_here';

// Repository Owner (username or organization)
$CONFIG['gitapi_owner'] = 'your-username';

// Repository Name
$CONFIG['gitapi_repo'] = 'your-repo-name';

// Default Branch (optional, defaults to 'main')
$CONFIG['gitapi_branch'] = 'main';

// API Base URL (optional, uses defaults if not specified)
// Only needed for self-hosted instances
// GitHub default: https://api.github.com
// GitLab default: https://gitlab.com/api/v4
// Bitbucket default: https://api.bitbucket.org/2.0
// $CONFIG['gitapi_baseurl'] = 'https://your-gitlab-instance.com/api/v4';
```

## Getting API Tokens

### GitHub
1. Go to **Settings** → **Developer settings** → **Personal access tokens** → **Tokens (classic)**
2. Click **Generate new token (classic)**
3. Give it a descriptive name
4. Select scopes: `repo` (Full control of private repositories)
5. Click **Generate token**
6. Copy the token immediately (you won't see it again)

### GitLab
1. Go to **Settings** → **Access Tokens**
2. Enter token name
3. Select expiration date (optional)
4. Select scopes: `api`, `read_repository`, `write_repository`
5. Click **Create personal access token**
6. Copy the token immediately

### Bitbucket
1. Go to **Personal settings** → **Personal access tokens**
2. Click **Create token**
3. Give it a label
4. Select permissions: `repository:write`, `repository:admin`
5. Click **Create**
6. Copy the token

## Configuration Example

```php
<?php
// Example configuration for GitHub
$CONFIG['gitapi_provider'] = 'github';
$CONFIG['gitapi_token'] = 'ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
$CONFIG['gitapi_owner'] = 'mycompany';
$CONFIG['gitapi_repo'] = 'myproject';
$CONFIG['gitapi_branch'] = 'main';
```

## Security Notes

1. **Never commit tokens to repository** - Keep them in separate config files
2. **Use environment variables** - Consider using environment variables for tokens
3. **Rotate tokens regularly** - Generate new tokens periodically
4. **Limit token permissions** - Only grant necessary scopes
5. **Use fine-grained tokens** - GitHub now supports more granular permissions

## Testing Your Configuration

1. Access the Git management page: `/php/admin.php?_menu=git`
2. Click the **Config** tab to see your current configuration
3. Click **Pull** to test API connectivity
4. Click **Status** to see local file changes

## Troubleshooting

### "Authentication token not provided"
- Check that `$CONFIG['gitapi_token']` is set
- Verify the token hasn't expired

### "Repository owner and name required"
- Ensure `$CONFIG['gitapi_owner']` and `$CONFIG['gitapi_repo']` are set
- Check for typos in repository names

### "Failed to get remote file"
- Verify the file exists in the remote repository
- Check that the branch name is correct
- Ensure your token has read permissions

### "Failed to update file"
- Verify your token has write permissions
- Check that you're not trying to push to a protected branch
- For GitHub, ensure the file SHA is correct

## Migration from Local Git

If you're migrating from the old local git command system:

1. **Backup your repository** - Make a complete backup before switching
2. **Ensure local repository is clean** - Commit or stash all changes
3. **Configure API settings** - Add all required $CONFIG settings
4. **Test with pull** - Verify API connectivity
5. **Test with small commit** - Try uploading a single file first

## API Rate Limits

Be aware of API rate limits:

- **GitHub**: 5,000 requests/hour (authenticated)
- **GitLab**: 1,000 requests/hour (authenticated)
- **Bitbucket**: 1,000 requests/hour (authenticated)

The system caches where possible to minimize API calls.

## Files Modified

- **gitapi.php** - New API library with procedural functions
- **git_functions.php** - Updated to use API calls with local git status
- **git_controller.php** - Updated to use API for push/pull/commit operations
- **git_body.htm** - No changes needed

## Support

For issues or questions:
1. Check the git.log file in php/admin/ for detailed error messages
2. Verify API token permissions
3. Test API connectivity with direct API calls
4. Review the gitapi.php function documentation

## Advanced Configuration

### Using Self-Hosted GitLab

```php
$CONFIG['gitapi_provider'] = 'gitlab';
$CONFIG['gitapi_baseurl'] = 'https://gitlab.yourcompany.com/api/v4';
$CONFIG['gitapi_token'] = 'your_token';
$CONFIG['gitapi_owner'] = 'group-name';
$CONFIG['gitapi_repo'] = 'project-name';
```

### Using Bitbucket

```php
$CONFIG['gitapi_provider'] = 'bitbucket';
$CONFIG['gitapi_token'] = 'your_token';
$CONFIG['gitapi_owner'] = 'workspace-name';
$CONFIG['gitapi_repo'] = 'repository-slug';
```

## Benefits of API-Based Workflow

1. **No local git installation required** - Works on any PHP server
2. **Direct remote operations** - Files go straight to remote repository
3. **Immediate visibility** - Changes appear instantly on hosting platform
4. **Better error messages** - API returns detailed error information
5. **Cross-platform compatibility** - No Windows/Linux git command differences
6. **Enhanced security** - Token-based authentication with granular permissions

---

Last Updated: 2024-12-20
Version: 3.0 - API Edition
