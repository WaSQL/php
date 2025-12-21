<?php
/**
 * Git API Library
 *
 * Production-ready procedural functions for common Git API operations.
 * Supports GitHub, GitLab, and Bitbucket APIs using CURL.
 *
 * Configuration via $CONFIG global:
 * - gitapi_provider: 'github', 'gitlab', or 'bitbucket' (default: 'github')
 * - gitapi_token: Personal access token for authentication
 * - gitapi_baseurl: API base URL (optional, uses defaults)
 * - gitapi_owner: Repository owner/organization
 * - gitapi_repo: Repository name
 * - gitapi_branch: Default branch (default: 'main')
 * - gitapi_ssl_verify: Verify SSL certificates (default: true, set to false to disable)
 *
 * @author WaSQL Framework
 * @version 1.0.0
 */

// ========================================
// HELPER FUNCTIONS
// ========================================

/**
 * Execute a Git API request using CURL
 *
 * @param string $endpoint API endpoint path
 * @param array $params Request parameters
 *   - method: HTTP method (GET, POST, PUT, DELETE, PATCH)
 *   - data: Request body data (array or JSON string)
 *   - headers: Additional headers (array)
 *   - provider: Override provider ('github', 'gitlab', 'bitbucket')
 * @return array Response array with keys: success, data, error, status_code, headers
 */
function gitapiRequest($endpoint, $params = array()) {
    global $CONFIG;

    // Get provider
    $provider = isset($params['provider']) ? $params['provider'] :
                (isset($CONFIG['gitapi_provider']) ? $CONFIG['gitapi_provider'] : 'github');

    // Get base URL
    $baseUrl = isset($params['baseurl']) ? $params['baseurl'] :
               (isset($CONFIG['gitapi_baseurl']) ? $CONFIG['gitapi_baseurl'] : gitapiGetDefaultBaseUrl($provider));

    // Build full URL
    $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');

    // Get authentication token
    $token = isset($params['token']) ? $params['token'] :
             (isset($CONFIG['gitapi_token']) ? $CONFIG['gitapi_token'] : '');

    if (!strlen($token)) {
        return array(
            'success' => false,
            'error' => 'Authentication token not provided (gitapi_token)',
            'status_code' => 0
        );
    }

    // Initialize CURL
    $ch = curl_init();

    // Set method
    $method = isset($params['method']) ? strtoupper($params['method']) : 'GET';

    // Build headers
    $headers = gitapiGetAuthHeaders($provider, $token);

    // Add custom headers
    if (isset($params['headers']) && is_array($params['headers'])) {
        $headers = array_merge($headers, $params['headers']);
    }

    // Handle request body
    $data = isset($params['data']) ? $params['data'] : null;
    if ($data !== null) {
        if (is_array($data)) {
            $data = json_encode($data);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    // Set CURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);

    // SSL verification (can be disabled via config for self-signed certs)
    $sslVerify = isset($CONFIG['gitapi_ssl_verify']) ? $CONFIG['gitapi_ssl_verify'] : true;
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : 0);

    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'WaSQL-GitAPI/1.0');

    // Execute request
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $error = curl_error($ch);

    curl_close($ch);

    // Parse response
    $responseHeaders = substr($response, 0, $headerSize);
    $responseBody = substr($response, $headerSize);

    // Check for CURL errors
    if ($error) {
        return array(
            'success' => false,
            'error' => 'CURL error: ' . $error,
            'status_code' => $statusCode
        );
    }

    // Decode JSON response
    $responseData = json_decode($responseBody, true);

    // Determine success
    $success = $statusCode >= 200 && $statusCode < 300;

    return array(
        'success' => $success,
        'data' => $responseData !== null ? $responseData : $responseBody,
        'error' => $success ? null : (isset($responseData['message']) ? $responseData['message'] : $responseBody),
        'status_code' => $statusCode,
        'headers' => gitapiParseHeaders($responseHeaders)
    );
}

/**
 * Get default base URL for a provider
 *
 * @param string $provider Provider name ('github', 'gitlab', 'bitbucket')
 * @return string Base URL
 */
function gitapiGetDefaultBaseUrl($provider) {
    $urls = array(
        'github' => 'https://api.github.com',
        'gitlab' => 'https://gitlab.com/api/v4',
        'bitbucket' => 'https://api.bitbucket.org/2.0'
    );
    return isset($urls[$provider]) ? $urls[$provider] : $urls['github'];
}

/**
 * Get authentication headers for a provider
 *
 * @param string $provider Provider name
 * @param string $token Authentication token
 * @return array Headers array
 */
function gitapiGetAuthHeaders($provider, $token) {
    $headers = array('Content-Type: application/json');

    switch ($provider) {
        case 'github':
            $headers[] = 'Authorization: Bearer ' . $token;
            $headers[] = 'Accept: application/vnd.github+json';
            $headers[] = 'X-GitHub-Api-Version: 2022-11-28';
            break;
        case 'gitlab':
            $headers[] = 'PRIVATE-TOKEN: ' . $token;
            break;
        case 'bitbucket':
            $headers[] = 'Authorization: Bearer ' . $token;
            break;
    }

    return $headers;
}

/**
 * Parse HTTP headers string into array
 *
 * @param string $headerString Raw headers
 * @return array Parsed headers
 */
function gitapiParseHeaders($headerString) {
    $headers = array();
    $lines = explode("\r\n", $headerString);
    foreach ($lines as $line) {
        if (strpos($line, ':') !== false) {
            list($key, $value) = explode(':', $line, 2);
            $headers[trim($key)] = trim($value);
        }
    }
    return $headers;
}

/**
 * Build repository path for API endpoints
 *
 * @param array $params Parameters (can override owner and repo)
 * @return string|array Repository path or error array
 */
function gitapiBuildRepoPath($params = array()) {
    global $CONFIG;

    $provider = isset($params['provider']) ? $params['provider'] :
                (isset($CONFIG['gitapi_provider']) ? $CONFIG['gitapi_provider'] : 'github');

    $owner = isset($params['owner']) ? $params['owner'] :
             (isset($CONFIG['gitapi_owner']) ? $CONFIG['gitapi_owner'] : '');

    $repo = isset($params['repo']) ? $params['repo'] :
            (isset($CONFIG['gitapi_repo']) ? $CONFIG['gitapi_repo'] : '');

    if (!strlen($owner) || !strlen($repo)) {
        return array(
            'success' => false,
            'error' => 'Repository owner and name required in config.xml (gitapi_owner, gitapi_repo)'
        );
    }

    switch ($provider) {
        case 'github':
            return "repos/{$owner}/{$repo}";
        case 'gitlab':
            return "projects/" . urlencode("{$owner}/{$repo}");
        case 'bitbucket':
            return "repositories/{$owner}/{$repo}";
        default:
            return "repos/{$owner}/{$repo}";
    }
}

// ========================================
// REPOSITORY INFORMATION FUNCTIONS
// ========================================

/**
 * Get repository status and information
 *
 * @param array $params Optional parameters
 *   - owner: Repository owner (default: $CONFIG['gitapi_owner'])
 *   - repo: Repository name (default: $CONFIG['gitapi_repo'])
 *   - provider: Git provider (default: $CONFIG['gitapi_provider'])
 * @return array Repository information or error
 */
function gitapiStatus($params = array()) {
    $repoPath = gitapiBuildRepoPath($params);
    if (is_array($repoPath) && !$repoPath['success']) {
        return $repoPath;
    }

    return gitapiRequest($repoPath, array_merge($params, array('method' => 'GET')));
}

/**
 * List all branches in repository
 *
 * @param array $params Optional parameters
 *   - owner: Repository owner
 *   - repo: Repository name
 *   - provider: Git provider
 *   - per_page: Results per page (default: 30)
 *   - page: Page number (default: 1)
 * @return array List of branches or error
 */
function gitapiBranches($params = array()) {
    global $CONFIG;

    $repoPath = gitapiBuildRepoPath($params);
    if (is_array($repoPath) && !$repoPath['success']) {
        return $repoPath;
    }

    $provider = isset($params['provider']) ? $params['provider'] :
                (isset($CONFIG['gitapi_provider']) ? $CONFIG['gitapi_provider'] : 'github');

    $endpoint = $provider === 'gitlab' ?
                "{$repoPath}/repository/branches" :
                "{$repoPath}/branches";

    return gitapiRequest($endpoint, array_merge($params, array('method' => 'GET')));
}

/**
 * List all tags in repository
 *
 * @param array $params Optional parameters
 *   - owner: Repository owner
 *   - repo: Repository name
 *   - provider: Git provider
 *   - per_page: Results per page (default: 30)
 *   - page: Page number (default: 1)
 * @return array List of tags or error
 */
function gitapiTags($params = array()) {
    global $CONFIG;

    $repoPath = gitapiBuildRepoPath($params);
    if (is_array($repoPath) && !$repoPath['success']) {
        return $repoPath;
    }

    $provider = isset($params['provider']) ? $params['provider'] :
                (isset($CONFIG['gitapi_provider']) ? $CONFIG['gitapi_provider'] : 'github');

    $endpoint = $provider === 'gitlab' ?
                "{$repoPath}/repository/tags" :
                "{$repoPath}/tags";

    return gitapiRequest($endpoint, array_merge($params, array('method' => 'GET')));
}

/**
 * Get commits for repository or branch
 *
 * @param array $params Optional parameters
 *   - owner: Repository owner
 *   - repo: Repository name
 *   - provider: Git provider
 *   - sha: Branch/commit SHA (default: default branch)
 *   - per_page: Results per page (default: 30)
 *   - page: Page number (default: 1)
 * @return array List of commits or error
 */
function gitapiCommits($params = array()) {
    global $CONFIG;

    $repoPath = gitapiBuildRepoPath($params);
    if (is_array($repoPath) && !$repoPath['success']) {
        return $repoPath;
    }

    $provider = isset($params['provider']) ? $params['provider'] :
                (isset($CONFIG['gitapi_provider']) ? $CONFIG['gitapi_provider'] : 'github');

    $endpoint = $provider === 'gitlab' ?
                "{$repoPath}/repository/commits" :
                "{$repoPath}/commits";

    // Add query parameters
    $queryParams = array();
    if (isset($params['sha'])) {
        $queryParams['sha'] = $params['sha'];
    }
    if (isset($params['per_page'])) {
        $queryParams['per_page'] = $params['per_page'];
    }
    if (isset($params['page'])) {
        $queryParams['page'] = $params['page'];
    }

    if (!empty($queryParams)) {
        $endpoint .= '?' . http_build_query($queryParams);
    }

    return gitapiRequest($endpoint, array_merge($params, array('method' => 'GET')));
}

/**
 * Get a specific commit by SHA
 *
 * @param string $sha Commit SHA
 * @param array $params Optional parameters
 *   - owner: Repository owner
 *   - repo: Repository name
 *   - provider: Git provider
 * @return array Commit information or error
 */
function gitapiGetCommit($sha, $params = array()) {
    global $CONFIG;

    if (!strlen($sha)) {
        return array('success' => false, 'error' => 'Commit SHA required');
    }

    $repoPath = gitapiBuildRepoPath($params);
    if (is_array($repoPath) && !$repoPath['success']) {
        return $repoPath;
    }

    $provider = isset($params['provider']) ? $params['provider'] :
                (isset($CONFIG['gitapi_provider']) ? $CONFIG['gitapi_provider'] : 'github');

    $endpoint = $provider === 'gitlab' ?
                "{$repoPath}/repository/commits/{$sha}" :
                "{$repoPath}/commits/{$sha}";

    return gitapiRequest($endpoint, array_merge($params, array('method' => 'GET')));
}

/**
 * Compare two commits or branches
 *
 * @param string $base Base branch/commit SHA
 * @param string $head Head branch/commit SHA
 * @param array $params Optional parameters
 *   - owner: Repository owner
 *   - repo: Repository name
 *   - provider: Git provider
 * @return array Comparison data or error
 */
function gitapiCompare($base, $head, $params = array()) {
    global $CONFIG;

    if (!strlen($base) || !strlen($head)) {
        return array('success' => false, 'error' => 'Base and head required for comparison');
    }

    $repoPath = gitapiBuildRepoPath($params);
    if (is_array($repoPath) && !$repoPath['success']) {
        return $repoPath;
    }

    $provider = isset($params['provider']) ? $params['provider'] :
                (isset($CONFIG['gitapi_provider']) ? $CONFIG['gitapi_provider'] : 'github');

    $endpoint = $provider === 'gitlab' ?
                "{$repoPath}/repository/compare?from={$base}&to={$head}" :
                "{$repoPath}/compare/{$base}...{$head}";

    return gitapiRequest($endpoint, array_merge($params, array('method' => 'GET')));
}

// ========================================
// FILE OPERATION FUNCTIONS
// ========================================

/**
 * Get file contents from repository
 *
 * @param string $filePath Path to file in repository
 * @param array $params Optional parameters
 *   - owner: Repository owner
 *   - repo: Repository name
 *   - provider: Git provider
 *   - branch: Branch name (default: main)
 *   - ref: Git reference (commit SHA, branch, tag)
 * @return array File data or error
 */
function gitapiGetFile($filePath, $params = array()) {
    global $CONFIG;

    if (!strlen($filePath)) {
        return array('success' => false, 'error' => 'File path required');
    }

    $repoPath = gitapiBuildRepoPath($params);
    if (is_array($repoPath) && !$repoPath['success']) {
        return $repoPath;
    }

    $provider = isset($params['provider']) ? $params['provider'] :
                (isset($CONFIG['gitapi_provider']) ? $CONFIG['gitapi_provider'] : 'github');

    $branch = isset($params['branch']) ? $params['branch'] :
              (isset($CONFIG['gitapi_branch']) ? $CONFIG['gitapi_branch'] : 'main');

    $ref = isset($params['ref']) ? $params['ref'] : $branch;

    $endpoint = $provider === 'gitlab' ?
                "{$repoPath}/repository/files/" . urlencode($filePath) . "?ref={$ref}" :
                "{$repoPath}/contents/" . ltrim($filePath, '/') . "?ref={$ref}";

    return gitapiRequest($endpoint, array_merge($params, array('method' => 'GET')));
}

/**
 * Create a new file in repository
 *
 * @param string $filePath Path for new file
 * @param string $content File content
 * @param string $message Commit message
 * @param array $params Optional parameters
 *   - owner: Repository owner
 *   - repo: Repository name
 *   - provider: Git provider
 *   - branch: Branch name (default: main)
 *   - author_name: Commit author name
 *   - author_email: Commit author email
 * @return array Created file data or error
 */
function gitapiCreateFile($filePath, $content, $message, $params = array()) {
    global $CONFIG, $USER;

    if (!strlen($filePath)) {
        return array('success' => false, 'error' => 'File path required');
    }
    if (!strlen($message)) {
        return array('success' => false, 'error' => 'Commit message required');
    }

    $repoPath = gitapiBuildRepoPath($params);
    if (is_array($repoPath) && !$repoPath['success']) {
        return $repoPath;
    }

    $provider = isset($params['provider']) ? $params['provider'] :
                (isset($CONFIG['gitapi_provider']) ? $CONFIG['gitapi_provider'] : 'github');

    $branch = isset($params['branch']) ? $params['branch'] :
              (isset($CONFIG['gitapi_branch']) ? $CONFIG['gitapi_branch'] : 'main');

    // Build request data
    $data = array();

    if ($provider === 'gitlab') {
        $data['branch'] = $branch;
        $data['content'] = $content;
        $data['commit_message'] = $message;
        if (isset($params['author_name'])) {
            $data['author_name'] = $params['author_name'];
        } elseif (isset($USER['name'])) {
            $data['author_name'] = $USER['name'];
        }
        if (isset($params['author_email'])) {
            $data['author_email'] = $params['author_email'];
        } elseif (isset($USER['email'])) {
            $data['author_email'] = $USER['email'];
        }
        $endpoint = "{$repoPath}/repository/files/" . urlencode($filePath);
    } else {
        // GitHub
        $data['message'] = $message;
        $data['content'] = base64_encode($content);
        $data['branch'] = $branch;
        if (isset($params['author_name']) && isset($params['author_email'])) {
            $data['author'] = array(
                'name' => $params['author_name'],
                'email' => $params['author_email']
            );
        } elseif (isset($USER['name']) && isset($USER['email'])) {
            $data['author'] = array(
                'name' => $USER['name'],
                'email' => $USER['email']
            );
        }
        $endpoint = "{$repoPath}/contents/" . ltrim($filePath, '/');
    }

    return gitapiRequest($endpoint, array_merge($params, array(
        'method' => 'POST',
        'data' => $data
    )));
}

/**
 * Update existing file in repository
 *
 * @param string $filePath Path to file
 * @param string $content New file content
 * @param string $message Commit message
 * @param array $params Required and optional parameters
 *   - sha: Current file SHA (required for GitHub)
 *   - owner: Repository owner
 *   - repo: Repository name
 *   - provider: Git provider
 *   - branch: Branch name (default: main)
 *   - author_name: Commit author name
 *   - author_email: Commit author email
 * @return array Updated file data or error
 */
function gitapiUpdateFile($filePath, $content, $message, $params = array()) {
    global $CONFIG, $USER;

    if (!strlen($filePath)) {
        return array('success' => false, 'error' => 'File path required');
    }
    if (!strlen($message)) {
        return array('success' => false, 'error' => 'Commit message required');
    }

    $repoPath = gitapiBuildRepoPath($params);
    if (is_array($repoPath) && !$repoPath['success']) {
        return $repoPath;
    }

    $provider = isset($params['provider']) ? $params['provider'] :
                (isset($CONFIG['gitapi_provider']) ? $CONFIG['gitapi_provider'] : 'github');

    $branch = isset($params['branch']) ? $params['branch'] :
              (isset($CONFIG['gitapi_branch']) ? $CONFIG['gitapi_branch'] : 'main');

    // Build request data
    $data = array();

    if ($provider === 'gitlab') {
        $data['branch'] = $branch;
        $data['content'] = $content;
        $data['commit_message'] = $message;
        if (isset($params['author_name'])) {
            $data['author_name'] = $params['author_name'];
        } elseif (isset($USER['name'])) {
            $data['author_name'] = $USER['name'];
        }
        if (isset($params['author_email'])) {
            $data['author_email'] = $params['author_email'];
        } elseif (isset($USER['email'])) {
            $data['author_email'] = $USER['email'];
        }
        $endpoint = "{$repoPath}/repository/files/" . urlencode($filePath);
        $method = 'PUT';
    } else {
        // GitHub requires file SHA
        if (!isset($params['sha']) || !strlen($params['sha'])) {
            return array('success' => false, 'error' => 'File SHA required for update (params[sha])');
        }
        $data['message'] = $message;
        $data['content'] = base64_encode($content);
        $data['sha'] = $params['sha'];
        $data['branch'] = $branch;
        if (isset($params['author_name']) && isset($params['author_email'])) {
            $data['author'] = array(
                'name' => $params['author_name'],
                'email' => $params['author_email']
            );
        } elseif (isset($USER['name']) && isset($USER['email'])) {
            $data['author'] = array(
                'name' => $USER['name'],
                'email' => $USER['email']
            );
        }
        $endpoint = "{$repoPath}/contents/" . ltrim($filePath, '/');
        $method = 'PUT';
    }

    return gitapiRequest($endpoint, array_merge($params, array(
        'method' => $method,
        'data' => $data
    )));
}

/**
 * Delete file from repository
 *
 * @param string $filePath Path to file
 * @param string $message Commit message
 * @param array $params Required and optional parameters
 *   - sha: Current file SHA (required for GitHub)
 *   - owner: Repository owner
 *   - repo: Repository name
 *   - provider: Git provider
 *   - branch: Branch name (default: main)
 *   - author_name: Commit author name
 *   - author_email: Commit author email
 * @return array Delete confirmation or error
 */
function gitapiDeleteFile($filePath, $message, $params = array()) {
    global $CONFIG, $USER;

    if (!strlen($filePath)) {
        return array('success' => false, 'error' => 'File path required');
    }
    if (!strlen($message)) {
        return array('success' => false, 'error' => 'Commit message required');
    }

    $repoPath = gitapiBuildRepoPath($params);
    if (is_array($repoPath) && !$repoPath['success']) {
        return $repoPath;
    }

    $provider = isset($params['provider']) ? $params['provider'] :
                (isset($CONFIG['gitapi_provider']) ? $CONFIG['gitapi_provider'] : 'github');

    $branch = isset($params['branch']) ? $params['branch'] :
              (isset($CONFIG['gitapi_branch']) ? $CONFIG['gitapi_branch'] : 'main');

    // Build request data
    $data = array();

    if ($provider === 'gitlab') {
        $data['branch'] = $branch;
        $data['commit_message'] = $message;
        if (isset($params['author_name'])) {
            $data['author_name'] = $params['author_name'];
        } elseif (isset($USER['name'])) {
            $data['author_name'] = $USER['name'];
        }
        if (isset($params['author_email'])) {
            $data['author_email'] = $params['author_email'];
        } elseif (isset($USER['email'])) {
            $data['author_email'] = $USER['email'];
        }
        $endpoint = "{$repoPath}/repository/files/" . urlencode($filePath);
    } else {
        // GitHub requires file SHA
        if (!isset($params['sha']) || !strlen($params['sha'])) {
            return array('success' => false, 'error' => 'File SHA required for delete (params[sha])');
        }
        $data['message'] = $message;
        $data['sha'] = $params['sha'];
        $data['branch'] = $branch;
        if (isset($params['author_name']) && isset($params['author_email'])) {
            $data['author'] = array(
                'name' => $params['author_name'],
                'email' => $params['author_email']
            );
        } elseif (isset($USER['name']) && isset($USER['email'])) {
            $data['author'] = array(
                'name' => $USER['name'],
                'email' => $USER['email']
            );
        }
        $endpoint = "{$repoPath}/contents/" . ltrim($filePath, '/');
    }

    return gitapiRequest($endpoint, array_merge($params, array(
        'method' => 'DELETE',
        'data' => $data
    )));
}

// ========================================
// BRANCH OPERATION FUNCTIONS
// ========================================

/**
 * Create a new branch
 *
 * @param string $branchName Name for new branch
 * @param array $params Required and optional parameters
 *   - sha: Commit SHA to branch from (required)
 *   - from: Source branch name (alternative to sha for GitLab)
 *   - owner: Repository owner
 *   - repo: Repository name
 *   - provider: Git provider
 * @return array Created branch data or error
 */
function gitapiCreateBranch($branchName, $params = array()) {
    global $CONFIG;

    if (!strlen($branchName)) {
        return array('success' => false, 'error' => 'Branch name required');
    }

    $repoPath = gitapiBuildRepoPath($params);
    if (is_array($repoPath) && !$repoPath['success']) {
        return $repoPath;
    }

    $provider = isset($params['provider']) ? $params['provider'] :
                (isset($CONFIG['gitapi_provider']) ? $CONFIG['gitapi_provider'] : 'github');

    if ($provider === 'gitlab') {
        $from = isset($params['from']) ? $params['from'] :
                (isset($params['sha']) ? $params['sha'] :
                (isset($CONFIG['gitapi_branch']) ? $CONFIG['gitapi_branch'] : 'main'));

        $data = array(
            'branch' => $branchName,
            'ref' => $from
        );
        $endpoint = "{$repoPath}/repository/branches";
    } else {
        // GitHub
        if (!isset($params['sha']) || !strlen($params['sha'])) {
            return array('success' => false, 'error' => 'Commit SHA required (params[sha])');
        }
        $data = array(
            'ref' => "refs/heads/{$branchName}",
            'sha' => $params['sha']
        );
        $endpoint = "{$repoPath}/git/refs";
    }

    return gitapiRequest($endpoint, array_merge($params, array(
        'method' => 'POST',
        'data' => $data
    )));
}

/**
 * Delete a branch
 *
 * @param string $branchName Name of branch to delete
 * @param array $params Optional parameters
 *   - owner: Repository owner
 *   - repo: Repository name
 *   - provider: Git provider
 * @return array Delete confirmation or error
 */
function gitapiDeleteBranch($branchName, $params = array()) {
    global $CONFIG;

    if (!strlen($branchName)) {
        return array('success' => false, 'error' => 'Branch name required');
    }

    $repoPath = gitapiBuildRepoPath($params);
    if (is_array($repoPath) && !$repoPath['success']) {
        return $repoPath;
    }

    $provider = isset($params['provider']) ? $params['provider'] :
                (isset($CONFIG['gitapi_provider']) ? $CONFIG['gitapi_provider'] : 'github');

    $endpoint = $provider === 'gitlab' ?
                "{$repoPath}/repository/branches/" . urlencode($branchName) :
                "{$repoPath}/git/refs/heads/{$branchName}";

    return gitapiRequest($endpoint, array_merge($params, array('method' => 'DELETE')));
}

/**
 * Merge a branch
 *
 * @param string $sourceBranch Source branch to merge from
 * @param string $targetBranch Target branch to merge into
 * @param string $message Merge commit message
 * @param array $params Optional parameters
 *   - owner: Repository owner
 *   - repo: Repository name
 *   - provider: Git provider
 *   - commit_message: Alternative to message parameter
 * @return array Merge result or error
 */
function gitapiMergeBranch($sourceBranch, $targetBranch, $message, $params = array()) {
    global $CONFIG;

    if (!strlen($sourceBranch) || !strlen($targetBranch)) {
        return array('success' => false, 'error' => 'Source and target branches required');
    }

    $repoPath = gitapiBuildRepoPath($params);
    if (is_array($repoPath) && !$repoPath['success']) {
        return $repoPath;
    }

    $provider = isset($params['provider']) ? $params['provider'] :
                (isset($CONFIG['gitapi_provider']) ? $CONFIG['gitapi_provider'] : 'github');

    if ($provider === 'gitlab') {
        $data = array(
            'source_branch' => $sourceBranch,
            'target_branch' => $targetBranch
        );
        if (strlen($message)) {
            $data['commit_message'] = $message;
        }
        $endpoint = "{$repoPath}/repository/merge_requests";
        // Note: GitLab requires merge request creation, not direct merge
    } else {
        // GitHub
        $data = array(
            'base' => $targetBranch,
            'head' => $sourceBranch
        );
        if (strlen($message)) {
            $data['commit_message'] = $message;
        }
        $endpoint = "{$repoPath}/merges";
    }

    return gitapiRequest($endpoint, array_merge($params, array(
        'method' => 'POST',
        'data' => $data
    )));
}

/**
 * Checkout (switch to) a branch - Note: API doesn't support checkout,
 * this function updates the default branch reference
 *
 * @param string $branchName Branch name to checkout
 * @param array $params Optional parameters
 *   - owner: Repository owner
 *   - repo: Repository name
 *   - provider: Git provider
 * @return array Result or error
 */
function gitapiCheckout($branchName, $params = array()) {
    global $CONFIG;

    if (!strlen($branchName)) {
        return array('success' => false, 'error' => 'Branch name required');
    }

    // Note: Git APIs don't have a direct "checkout" concept
    // This function updates the default branch for the repository
    $repoPath = gitapiBuildRepoPath($params);
    if (is_array($repoPath) && !$repoPath['success']) {
        return $repoPath;
    }

    $data = array('default_branch' => $branchName);

    return gitapiRequest($repoPath, array_merge($params, array(
        'method' => 'PATCH',
        'data' => $data
    )));
}

// ========================================
// TAG OPERATION FUNCTIONS
// ========================================

/**
 * Create a new tag
 *
 * @param string $tagName Name for new tag
 * @param string $sha Commit SHA to tag
 * @param array $params Optional parameters
 *   - owner: Repository owner
 *   - repo: Repository name
 *   - provider: Git provider
 *   - message: Tag message (for annotated tags)
 *   - tag_message: Alternative to message
 * @return array Created tag data or error
 */
function gitapiCreateTag($tagName, $sha, $params = array()) {
    global $CONFIG;

    if (!strlen($tagName)) {
        return array('success' => false, 'error' => 'Tag name required');
    }
    if (!strlen($sha)) {
        return array('success' => false, 'error' => 'Commit SHA required');
    }

    $repoPath = gitapiBuildRepoPath($params);
    if (is_array($repoPath) && !$repoPath['success']) {
        return $repoPath;
    }

    $provider = isset($params['provider']) ? $params['provider'] :
                (isset($CONFIG['gitapi_provider']) ? $CONFIG['gitapi_provider'] : 'github');

    $message = isset($params['message']) ? $params['message'] :
               (isset($params['tag_message']) ? $params['tag_message'] : '');

    if ($provider === 'gitlab') {
        $data = array(
            'tag_name' => $tagName,
            'ref' => $sha
        );
        if (strlen($message)) {
            $data['message'] = $message;
        }
        $endpoint = "{$repoPath}/repository/tags";
    } else {
        // GitHub - create lightweight tag via refs
        $data = array(
            'ref' => "refs/tags/{$tagName}",
            'sha' => $sha
        );
        $endpoint = "{$repoPath}/git/refs";
    }

    return gitapiRequest($endpoint, array_merge($params, array(
        'method' => 'POST',
        'data' => $data
    )));
}

/**
 * Delete a tag
 *
 * @param string $tagName Name of tag to delete
 * @param array $params Optional parameters
 *   - owner: Repository owner
 *   - repo: Repository name
 *   - provider: Git provider
 * @return array Delete confirmation or error
 */
function gitapiDeleteTag($tagName, $params = array()) {
    global $CONFIG;

    if (!strlen($tagName)) {
        return array('success' => false, 'error' => 'Tag name required');
    }

    $repoPath = gitapiBuildRepoPath($params);
    if (is_array($repoPath) && !$repoPath['success']) {
        return $repoPath;
    }

    $provider = isset($params['provider']) ? $params['provider'] :
                (isset($CONFIG['gitapi_provider']) ? $CONFIG['gitapi_provider'] : 'github');

    $endpoint = $provider === 'gitlab' ?
                "{$repoPath}/repository/tags/" . urlencode($tagName) :
                "{$repoPath}/git/refs/tags/{$tagName}";

    return gitapiRequest($endpoint, array_merge($params, array('method' => 'DELETE')));
}

// ========================================
// COMMIT OPERATION FUNCTIONS
// ========================================

/**
 * Create a new commit with multiple file changes
 *
 * @param string $message Commit message
 * @param array $actions Array of file actions, each containing:
 *   - action: 'create', 'update', 'delete', or 'move'
 *   - file_path: Path to file
 *   - content: File content (for create/update)
 *   - previous_path: Previous path (for move)
 * @param array $params Optional parameters
 *   - owner: Repository owner
 *   - repo: Repository name
 *   - provider: Git provider
 *   - branch: Branch name (default: main)
 *   - author_name: Commit author name
 *   - author_email: Commit author email
 * @return array Commit data or error
 */
function gitapiCommit($message, $actions, $params = array()) {
    global $CONFIG, $USER;

    if (!strlen($message)) {
        return array('success' => false, 'error' => 'Commit message required');
    }
    if (!is_array($actions) || empty($actions)) {
        return array('success' => false, 'error' => 'Actions array required');
    }

    $repoPath = gitapiBuildRepoPath($params);
    if (is_array($repoPath) && !$repoPath['success']) {
        return $repoPath;
    }

    $provider = isset($params['provider']) ? $params['provider'] :
                (isset($CONFIG['gitapi_provider']) ? $CONFIG['gitapi_provider'] : 'github');

    $branch = isset($params['branch']) ? $params['branch'] :
              (isset($CONFIG['gitapi_branch']) ? $CONFIG['gitapi_branch'] : 'main');

    if ($provider === 'gitlab') {
        $data = array(
            'branch' => $branch,
            'commit_message' => $message,
            'actions' => $actions
        );
        if (isset($params['author_name'])) {
            $data['author_name'] = $params['author_name'];
        } elseif (isset($USER['name'])) {
            $data['author_name'] = $USER['name'];
        }
        if (isset($params['author_email'])) {
            $data['author_email'] = $params['author_email'];
        } elseif (isset($USER['email'])) {
            $data['author_email'] = $USER['email'];
        }
        $endpoint = "{$repoPath}/repository/commits";
    } else {
        // GitHub doesn't have a direct multi-file commit API
        // You would need to use the Git Data API with trees
        return array(
            'success' => false,
            'error' => 'Multi-file commits not supported via GitHub API. Use individual file operations or use gitapiCreateFile/gitapiUpdateFile.'
        );
    }

    return gitapiRequest($endpoint, array_merge($params, array(
        'method' => 'POST',
        'data' => $data
    )));
}

/**
 * Push commits (Note: Git APIs don't have direct push - commits are pushed automatically)
 * This function returns status of the branch
 *
 * @param array $params Optional parameters
 *   - owner: Repository owner
 *   - repo: Repository name
 *   - provider: Git provider
 *   - branch: Branch name
 * @return array Branch status or error
 */
function gitapiPush($params = array()) {
    global $CONFIG;

    // Note: Git APIs automatically push commits when created
    // This function checks branch status
    $branch = isset($params['branch']) ? $params['branch'] :
              (isset($CONFIG['gitapi_branch']) ? $CONFIG['gitapi_branch'] : 'main');

    $repoPath = gitapiBuildRepoPath($params);
    if (is_array($repoPath) && !$repoPath['success']) {
        return $repoPath;
    }

    $provider = isset($params['provider']) ? $params['provider'] :
                (isset($CONFIG['gitapi_provider']) ? $CONFIG['gitapi_provider'] : 'github');

    $endpoint = $provider === 'gitlab' ?
                "{$repoPath}/repository/branches/" . urlencode($branch) :
                "{$repoPath}/branches/{$branch}";

    return gitapiRequest($endpoint, array_merge($params, array('method' => 'GET')));
}

/**
 * Check if local repository is clean (no uncommitted changes)
 *
 * @return array Result with 'success', 'is_clean', 'output' keys
 */
function gitapiIsClean() {
    global $CONFIG;

    $path = isset($CONFIG['gitapi_path']) ? $CONFIG['gitapi_path'] : '';
    if (!strlen($path) || !is_dir($path)) {
        return array(
            'success' => false,
            'is_clean' => false,
            'error' => 'Invalid git path'
        );
    }

    $original_dir = getcwd();
    if (!chdir($path)) {
        return array(
            'success' => false,
            'is_clean' => false,
            'error' => 'Could not change to git directory'
        );
    }

    $output = array();
    $exit_code = 0;
    exec('git status --porcelain 2>&1', $output, $exit_code);

    chdir($original_dir);

    $is_clean = ($exit_code === 0 && empty($output));

    return array(
        'success' => $exit_code === 0,
        'is_clean' => $is_clean,
        'output' => implode("\n", $output),
        'error' => $exit_code !== 0 ? implode("\n", $output) : ''
    );
}

/**
 * Pull changes from remote repository
 *
 * This function executes an actual git pull to sync the local repository
 * with the remote. This ensures the Browser UI and git CLI stay in sync.
 * Automatically stashes local changes if needed.
 *
 * @param array $params Optional parameters
 *   - owner: Repository owner
 *   - repo: Repository name
 *   - provider: Git provider
 *   - branch: Branch name
 *   - auto_stash: Automatically stash uncommitted changes (default: true)
 * @return array Result with 'success', 'output', 'error', 'stashed' keys
 */
function gitapiPull($params = array()) {
    global $CONFIG;

    // Get the local path
    $path = isset($CONFIG['gitapi_path']) ? $CONFIG['gitapi_path'] : '';
    if (!strlen($path) || !is_dir($path)) {
        return array(
            'success' => false,
            'error' => 'Invalid git path for pull operation',
            'output' => ''
        );
    }

    // Save current directory
    $original_dir = getcwd();

    // Change to git directory
    if (!chdir($path)) {
        return array(
            'success' => false,
            'error' => 'Could not change to git directory',
            'output' => ''
        );
    }

    // Get branch name
    $branch = isset($params['branch']) ? $params['branch'] :
              (isset($CONFIG['gitapi_branch']) ? $CONFIG['gitapi_branch'] : 'master');

    // Check for auto-stash preference (default true)
    $auto_stash = isset($params['auto_stash']) ? $params['auto_stash'] : true;

    $output = array();
    $stashed = false;

    // Check if there are uncommitted changes
    if ($auto_stash) {
        $status_output = array();
        exec('git status --porcelain 2>&1', $status_output, $status_code);

        if ($status_code === 0 && !empty($status_output)) {
            // There are uncommitted changes - stash them
            $stash_output = array();
            exec('git stash save "Auto-stash before Browser UI pull" 2>&1', $stash_output, $stash_code);
            if ($stash_code === 0) {
                $output[] = "Auto-stashed local changes before pull";
                $stashed = true;
            }
        }
    }

    // Execute git pull
    $pull_output = array();
    $exit_code = 0;
    exec("git pull origin {$branch} 2>&1", $pull_output, $exit_code);

    $output = array_merge($output, $pull_output);

    // If we stashed changes and pull succeeded, try to pop the stash
    if ($stashed && $exit_code === 0) {
        $pop_output = array();
        exec('git stash pop 2>&1', $pop_output, $pop_code);

        if ($pop_code === 0) {
            $output[] = "Successfully restored your local changes";
        } else {
            // Stash pop failed (probably conflicts)
            $output[] = "Warning: Could not auto-restore stashed changes (conflicts detected)";
            $output[] = "Your changes are safely stashed. Run 'git stash list' to see them";
            $output[] = "Run 'git stash pop' manually to restore them";
        }
    }

    // Restore original directory
    chdir($original_dir);

    $output_str = implode("\n", $output);

    return array(
        'success' => $exit_code === 0,
        'output' => $output_str,
        'error' => $exit_code !== 0 ? $output_str : '',
        'data' => $output,
        'stashed' => $stashed
    );
}

?>
