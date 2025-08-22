<?php
declare(strict_types=1);

/**
 * Native SFTP client using PHP's SSH2 extension (ext-ssh2).
 * - Password or public key auth
 * - Optional server fingerprint verification
 * - List, download, upload, mkdir, rmdir, rename, delete
 *
 * Usage example at bottom.
 * 
 * You need ssh2 installed: 
 *  For windows download the dll
 *      https://github.com/jhanley-com/php-ssh2-windows/blob/master/README.md
 *  then add the following to php.ini
 *      extension=php_ssh2.dll
 *  Linux Debian/Ubuntu
 *      sudo apt-get install php-ssh2
 *  RedHat/CentOS
 *      sudo yum install php-pecl-ssh2
 *  macOS
 *      pecl install ssh2
 * 
 */

final class NativeSFTP
{
    private string $host;
    private int $port;
    private string $username;
    private ?string $password;
    private ?string $pubKeyPath;
    private ?string $privKeyPath;
    private ?string $passphrase;
    private ?string $expectedFingerprintHex; // e.g. "ab:cd:ef:..." or "abcdef..." (case-insensitive)
    private $conn = null;     // SSH2 connection resource
    private $sftp = null;     // SSH2 SFTP resource

    public function __construct(array $cfg)
    {
        if (!extension_loaded('ssh2')) {
            throw new RuntimeException('The ssh2 extension is required. Install via PECL: pecl install ssh2');
        }

        $this->host   = $cfg['host'] ?? 'localhost';
        $this->port   = (int)($cfg['port'] ?? 22);
        $this->username = $cfg['username'] ?? '';
        $this->password = $cfg['password'] ?? null;
        $this->pubKeyPath  = $cfg['pub_key']  ?? null;
        $this->privKeyPath = $cfg['priv_key'] ?? null;
        $this->passphrase  = $cfg['passphrase'] ?? null;
        $this->expectedFingerprintHex = isset($cfg['fingerprint_hex']) ? strtolower(preg_replace('/[^0-9a-f]/i', '', $cfg['fingerprint_hex'])) : null;

        if ($this->username === '') {
            throw new InvalidArgumentException('username is required');
        }
    }

    public function connect(int $timeoutSeconds = 15): void
    {
        $methods = [
            'kex' => null,          // let lib negotiate; override if you need specific KEX
            'hostkey' => null,      // e.g. 'ssh-ed25519' or 'ssh-rsa'
            'client_to_server' => [
                'crypt' => null,    // e.g. ['aes128-ctr','aes256-ctr']
                'comp'  => null,
                'mac'   => null,
            ],
            'server_to_client' => [
                'crypt' => null,
                'comp'  => null,
                'mac'   => null,
            ],
        ];

        $callbacks = [
            'disconnect' => function ($reason, $message, $language) {
                // No-op; could log
            }
        ];

        $this->conn = @ssh2_connect($this->host, $this->port, $methods, $callbacks);
        if (!$this->conn) {
            throw new RuntimeException("Failed to connect to {$this->host}:{$this->port}");
        }

        // Optional: verify server fingerprint (MD5 hex). ext-ssh2 commonly supports MD5/hex flag combo.
        if ($this->expectedFingerprintHex !== null) {
            $fp = @ssh2_fingerprint($this->conn, SSH2_FINGERPRINT_MD5 | SSH2_FINGERPRINT_HEX);
            $fpNorm = strtolower(preg_replace('/[^0-9a-f]/i', '', (string)$fp));
            if ($fpNorm !== $this->expectedFingerprintHex) {
                throw new RuntimeException("Server fingerprint mismatch. Expected {$this->expectedFingerprintHex}, got {$fpNorm}");
            }
        }

        // Authenticate
$ok = false;
if ($this->privKeyPath !== null) {
    $useDirectFiles = is_file($this->privKeyPath) && ($this->pubKeyPath !== null && is_file($this->pubKeyPath));
    if (!$useDirectFiles) {
        // Treat provided values as contents; prepare temp files and auto-derive .pub if needed
        [$priv, $pub, $pass] = self::prepareKeyAuth($this->privKeyPath, $this->pubKeyPath, $this->passphrase);
    } else {
        $priv = $this->privKeyPath;
        $pub  = $this->pubKeyPath;
        $pass = $this->passphrase ?? '';
    }
    $ok = @ssh2_auth_pubkey_file(
        $this->conn,
        $this->username,
        $pub,
        $priv,
        $pass ?? ''
    );
} elseif ($this->password !== null) {
    $ok = @ssh2_auth_password($this->conn, $this->username, $this->password);
} else {
    throw new InvalidArgumentException('Provide either password OR pub_key + priv_key (with optional passphrase).');
}

if (!$ok) {
    throw new RuntimeException('SSH authentication failed.');
}

        if (!$ok) {
            throw new RuntimeException('SSH authentication failed.');
        }

        $this->sftp = @ssh2_sftp($this->conn);
        if (!$this->sftp) {
            throw new RuntimeException('Failed to initialize SFTP subsystem.');
        }

        // Apply a socket timeout to SFTP operations by setting stream context default timeout, if needed.
        stream_context_set_default(['ssh2' => ['timeout' => $timeoutSeconds]]);
    }

    public function list(string $remotePath): array
    {
        $remotePath = $this->applyCwd($remotePath);

        $dir = $this->sftpPath($remotePath);
        $dh = @opendir($dir);
        if (!$dh) {
            throw new RuntimeException("Cannot open directory: {$remotePath}");
        }
        $items = [];
        while (($file = readdir($dh)) !== false) {
            if ($file === '.' || $file === '..') continue;
            $items[] = $file;
        }
        closedir($dh);
        sort($items);
        return $items;
    }

    /**
     * Execute a raw command on the remote server and return stdout+stderr.
     */
    public function exec(string $command, bool $trim = true): string
    {
        if (!is_resource($this->conn)) {
            throw new RuntimeException('Not connected. Call connect() first.');
        }

        $stream = @ssh2_exec($this->conn, $command);
        if (!$stream) {
            throw new RuntimeException("Failed to execute command: {$command}");
        }

        $errorStream = @ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);

        stream_set_blocking($stream, true);
        stream_set_blocking($errorStream, true);

        $stdout = stream_get_contents($stream);
        $stderr = stream_get_contents($errorStream);

        fclose($stream);
        fclose($errorStream);

        $output = $stdout . $stderr;
        return $trim ? trim($output) : $output;
    }

    /**
     * Get the remote working directory (like `pwd`).
     */
    public function pwd(): string
    {
        return $this->exec('pwd');
    }

        /**
     * Resolve an SFTP path to its absolute canonical form (libssh2 sftp_realpath).
     * Works even on SFTP-only servers with no shell access.
     */
    public function realpath(string $path = '.'): string
    {
        if (!is_resource($this->sftp)) {
            throw new RuntimeException('Not connected. Call connect() first.');
        }

        // Prefer the dedicated API if available in your ssh2 build:
        if (function_exists('ssh2_sftp_realpath')) {
            $resolved = @ssh2_sftp_realpath($this->sftp, $path);
            if ($resolved === false || $resolved === null || $resolved === '') {
                throw new RuntimeException("Failed to resolve path: {$path}");
            }
            return $resolved;
        }

        // Fallback: try to infer by opening the directory and reading its meta path.
        // (Some builds don’t expose ssh2_sftp_realpath; this still gives you a usable absolute)
        $meta = sprintf('ssh2.sftp://%s/%s', intval($this->sftp), ltrim($path, '/'));
        $dir = @opendir($meta);
        if ($dir === false) {
            // If '.' fails, try home (“/”) so the caller gets a clue.
            if ($path === '.' && @opendir(sprintf('ssh2.sftp://%s/', intval($this->sftp))) !== false) {
                return '/';
            }
            throw new RuntimeException("Cannot open path: {$path}");
        }
        // Some stream wrappers expose the resolved path in stream_get_meta_data
        $metaData = stream_get_meta_data($dir);
        @closedir($dir);
        // meta_data['uri'] often looks like ssh2.sftp://<id>/abs/path
        if (!empty($metaData['uri'])) {
            $u = parse_url($metaData['uri']);
            if (!empty($u['path'])) {
                return $u['path']; // best-effort absolute path
            }
        }
        // Last resort: return normalized input
        return '/' . ltrim($path, '/');
    }

    /**
     * SFTP equivalent of `pwd` – returns your login directory absolute path.
     */
    public function pwdSftp(): string
    {
        return $this->realpath('.');
    }

        /**
     * Non-throwing directory list: returns ['dirs' => [...], 'files' => [...]].
     * Useful for probing what exists without exceptions.
     */
    public function tryList(string $remotePath = '.', bool $includeHidden = false): array
    {
        if (!is_resource($this->sftp)) {
            throw new RuntimeException('Not connected. Call connect() first.');
        }

        $path = $this->sftpPath($remotePath);
        $dh = @opendir($path);
        if ($dh === false) {
            return ['dirs' => [], 'files' => []]; // unreadable or non-existent
        }

        $dirs  = [];
        $files = [];

        while (($name = readdir($dh)) !== false) {
            if ($name === '.' || $name === '..') continue;
            if (!$includeHidden && substr($name, 0, 1) === '.') continue;

            $fullLogical = rtrim($remotePath, '/') . '/' . $name;
            $fullLogical = ltrim($fullLogical, '/'); // keep relative logical path

            // Prefer is_dir on the wrapper; fall back to opendir probe
            $isDir = @is_dir($this->sftpPath($fullLogical));
            if ($isDir === false) {
                $probe = @opendir($this->sftpPath($fullLogical));
                if ($probe !== false) {
                    $isDir = true;
                    @closedir($probe);
                }
            }

            if ($isDir) {
                $dirs[] = $fullLogical;
            } else {
                $files[] = $fullLogical;
            }
        }
        closedir($dh);

        // Sort for stability
        sort($dirs, SORT_NATURAL | SORT_FLAG_CASE);
        sort($files, SORT_NATURAL | SORT_FLAG_CASE);

        return ['dirs' => $dirs, 'files' => $files];
    }

    /**
     * List only directories under the given path (non-throwing).
     */
    public function listDirs(string $remotePath = '.', bool $includeHidden = false): array
    {
        $res = $this->tryList($remotePath, $includeHidden);
        return $res['dirs'];
    }

    /**
     * Breadth-first discovery of accessible directories starting at $start.
     * Returns a flat list of absolute-like logical paths (relative to start when start = '.').
     *
     * @param string $start         Starting directory ('.' is your SFTP home/jail root)
     * @param int    $maxDepth      How deep to traverse (0 = only start, 1 = start's children, ...)
     * @param int    $maxNodes      Safety cap on directories visited
     * @param bool   $includeHidden Include dot-directories if true
     * @return array                List of directory paths discovered, including $start
     */
    public function discoverDirectories(string $start = '.', int $maxDepth = 2, int $maxNodes = 2000, bool $includeHidden = false): array
    {
        if (!is_resource($this->sftp)) {
            throw new RuntimeException('Not connected. Call connect() first.');
        }

        // Normalize start
        $start = trim($start) === '' ? '.' : $start;
        $normalize = function (string $p): string {
            // Collapse duplicate slashes, remove trailing slash (except root-like '.')
            $p = preg_replace('#/+#', '/', $p);
            if ($p !== '.' && $p !== '/' && substr($p, -1) === '/') {
                $p = substr($p, 0, -1);
            }
            return $p;
        };
        $start = $normalize($start);

        // BFS
        $queue = [[$start, 0]];
        $seen  = [];
        $result = [];

        while (!empty($queue)) {
            [$dir, $depth] = array_shift($queue);

            if (isset($seen[$dir])) continue;
            $seen[$dir] = true;
            $result[] = $dir;

            if (count($seen) >= $maxNodes) {
                // Safety stop
                break;
            }
            if ($depth >= $maxDepth) continue;

            $listing = $this->tryList($dir, $includeHidden);
            foreach ($listing['dirs'] as $child) {
                $child = $normalize($child);
                if (!isset($seen[$child])) {
                    $queue[] = [$child, $depth + 1];
                }
            }
        }

        // Stable order
        $result = array_values(array_unique($result));
        sort($result, SORT_NATURAL | SORT_FLAG_CASE);

        return $result;
    }


    public function download(string $remoteFile, string $localFile, bool $overwrite = true): void
    {
        $remoteFile = $this->applyCwd($remoteFile);

        $src = $this->sftpPath($remoteFile);
        if (!$overwrite && file_exists($localFile)) {
            throw new RuntimeException("Local file exists: {$localFile}");
        }

        $in  = @fopen($src, 'rb');
        if (!$in) {
            throw new RuntimeException("Cannot open remote file for reading: {$remoteFile}");
        }

        $out = @fopen($localFile, 'wb');
        if (!$out) {
            fclose($in);
            throw new RuntimeException("Cannot open local file for writing: {$localFile}");
        }

        $this->streamCopy($in, $out);
        fclose($in);
        fclose($out);
    }

    public function upload(string $localFile, string $remoteFile, bool $overwrite = true): void
    {
        $remoteFile = $this->applyCwd($remoteFile);

        if (!is_file($localFile)) {
            throw new RuntimeException("Local file not found: {$localFile}");
        }

        $dst = $this->sftpPath($remoteFile);
        if (!$overwrite && $this->exists($remoteFile)) {
            throw new RuntimeException("Remote file exists: {$remoteFile}");
        }

        $in  = @fopen($localFile, 'rb');
        if (!$in) {
            throw new RuntimeException("Cannot open local file for reading: {$localFile}");
        }

        $out = @fopen($dst, 'wb');
        if (!$out) {
            fclose($in);
            throw new RuntimeException("Cannot open remote file for writing: {$remoteFile}");
        }

        $this->streamCopy($in, $out);
        fclose($in);
        fclose($out);
    }

    public function delete(string $remoteFile): void
    {
        $remoteFile = $this->applyCwd($remoteFile);

        $path = $this->sftpPath($remoteFile);
        if (!@unlink($path)) {
            throw new RuntimeException("Failed to delete remote file: {$remoteFile}");
        }
    }

    public function rename(string $remoteFrom, string $remoteTo, bool $overwrite = true): void
    {
        $remoteFrom = $this->applyCwd($remoteFrom);
        $remoteTo   = $this->applyCwd($remoteTo);

        if (!is_resource($this->sftp)) {
            throw new RuntimeException('Not connected. Call connect() first.');
        }

        if (!$this->exists($remoteFrom)) {
            throw new RuntimeException("Source does not exist: {$remoteFrom}");
        }

        $parent = rtrim(dirname($remoteTo), '/');
        if ($parent !== '' && $parent !== '.' && $parent !== '/') {
            if (!$this->exists($parent) || !@is_dir($this->sftpPath($parent))) {
                $this->mkdir($parent, 0755, true);
            }
            // Probe writability
            $parentUri = $this->sftpPath($parent);
            $probeName = '/.__probe_' . bin2hex(random_bytes(4));
            $probe = @fopen($parentUri . $probeName, 'wb');
            if ($probe === false) {
                throw new RuntimeException("Destination directory not writable: {$parent}");
            }
            @fclose($probe);
            @unlink($parentUri . $probeName);
        }

        if (!$overwrite && $this->exists($remoteTo)) {
            throw new RuntimeException("Destination exists: {$remoteTo}");
        }

        $fromUri = $this->sftpPath($remoteFrom);
        $toUri   = $this->sftpPath($remoteTo);

        if (@rename($fromUri, $toUri)) {
            return;
        }

        if ($overwrite && $this->exists($remoteTo)) {
            @unlink($toUri);
            if (@rename($fromUri, $toUri)) {
                return;
            }
        }

        $src = @fopen($fromUri, 'rb');
        if ($src === false) {
            throw new RuntimeException("Failed to open source for copy: {$remoteFrom}");
        }
        $dst = @fopen($toUri, 'wb');
        if ($dst === false) {
            @fclose($src);
            throw new RuntimeException("Failed to open destination for copy: {$remoteTo}");
        }
        $bytes = @stream_copy_to_stream($src, $dst);
        @fflush($dst);
        @fclose($dst);
        @fclose($src);

        if ($bytes === false) {
            throw new RuntimeException("Copy fallback failed from {$remoteFrom} to {$remoteTo}");
        }

        $srcSize = null; $dstSize = null;
        try { $srcSize = $this->filesize($remoteFrom); } catch (\Throwable $e) {}
        try { $dstSize = $this->filesize($remoteTo);   } catch (\Throwable $e) {}
        if ($srcSize !== null && $dstSize !== null && $srcSize !== $dstSize) {
            @unlink($toUri);
            throw new RuntimeException("Copy size mismatch ({$srcSize} -> {$dstSize}) during rename fallback.");
        }

        if (!@unlink($fromUri)) {
            @unlink($toUri);
            throw new RuntimeException("Failed to remove source after copy fallback: {$remoteFrom}");
        }
    }

    public function mkdir(string $remotePath, int $mode = 0755, bool $recursive = false): void
    {
        $remotePath = $this->applyCwd($remotePath);
        if (!is_resource($this->sftp)) {
            throw new RuntimeException('Not connected. Call connect() first.');
        }
        $remotePath = '/' . ltrim($remotePath, '/');
        $remotePath = rtrim($remotePath, '/');
        if ($remotePath === '') { $remotePath = '/'; }

        $dirUri = $this->sftpPath($remotePath);
        if (@is_dir($dirUri)) {
            return;
        }

        if (!$recursive) {
            if (!@ssh2_sftp_mkdir($this->sftp, $remotePath, $mode, false)) {
                throw new RuntimeException("Failed to create directory: {$remotePath}");
            }
            return;
        }

        $parts = array_filter(explode('/', trim($remotePath, '/')), 'strlen');
        $build = '';
        foreach ($parts as $p) {
            $build .= '/' . $p;
            $uri = $this->sftpPath($build);
            if (@is_dir($uri)) continue;
            if (!@ssh2_sftp_mkdir($this->sftp, $build, $mode, false)) {
                if (!@ssh2_sftp_mkdir($this->sftp, $build)) {
                    if (!@is_dir($uri)) {
                        throw new RuntimeException("Failed to create directory: {$build}");
                    }
                }
            }
        }
    }

    public function rmdir(string $remotePath): void
    {
        $remotePath = $this->applyCwd($remotePath);

        $path = $this->sftpPath($remotePath);
        if (!@rmdir($path)) {
            throw new RuntimeException("Failed to remove directory: {$remotePath}");
        }
    }

    public function exists(string $remotePath): bool
    {
        $remotePath = $this->applyCwd($remotePath);

        $path = $this->sftpPath($remotePath);
        return @file_exists($path);
    }

    public function filesize(string $remotePath): int
    {
        $remotePath = $this->applyCwd($remotePath);

        $path = $this->sftpPath($remotePath);
        $size = @filesize($path);
        if ($size === false) {
            throw new RuntimeException("Failed to stat file size: {$remotePath}");
        }
        return (int)$size;
    }

    public function disconnect(): void
    {
        // Cleanly close by freeing resources.
        $this->sftp = null;
        if (is_resource($this->conn)) {
            // ext-ssh2 doesn't expose an explicit disconnect; closing stream ends it.
            $this->conn = null;
        }
    }

    private function streamCopy($in, $out, int $bufSize = 1048576): void
    {
        stream_set_read_buffer($in, 0);
        stream_set_write_buffer($out, 0);
        while (!feof($in)) {
            $buf = fread($in, $bufSize);
            if ($buf === false) {
                throw new RuntimeException('Read error during stream copy.');
            }
            $written = fwrite($out, $buf);
            if ($written === false) {
                throw new RuntimeException('Write error during stream copy.');
            }
        }
        fflush($out);
    }

    private function sftpPath(string $path): string
    {
        if (!is_resource($this->sftp)) {
            throw new RuntimeException('Not connected. Call connect() first.');
        }
        // Normalize (allow absolute or relative)
        $normalized = '/' . ltrim($path, '/');
        return sprintf('ssh2.sftp://%s%s', intval($this->sftp), $normalized);
    }
    /**
     * Prepare key files for public key authentication.
     * Accepts private/public key paths OR raw contents and writes temp files if needed.
     * If only private key is provided and unencrypted, derive .pub via ssh-keygen -y.
     * Returns [string $privPath, string $pubPath, ?string $passphrase]
     */
    private static function prepareKeyAuth(string $priv, ?string $pub, ?string $pass): array
    {
        $isPem = function (string $s): bool {
            return strpos($s, '-----BEGIN') !== false;
        };
        $isOpenSshPub = function (string $s): bool {
            $first = trim(strtok($s, "\r\n"));
            return (bool) preg_match('/^ssh-(rsa|ed25519|dss|ecdsa)/i', $first);
        };

        // Private key: path or PEM
        if (is_file($priv)) {
            $privPath = $priv;
            $pubPath  = null;
            if ($pub === null) {
                $candidate = $priv . '.pub';
                if (is_file($candidate)) {
                    $pubPath = $candidate;
                }
            }
        } elseif ($isPem($priv)) {
            $privPath = self::writeTempKey($priv, 'nativesftp_priv_', '.pem', 0600);
            $pubPath = null;
        } else {
            throw new \RuntimeException('Private key must be a file path or PEM contents.');
        }

        // Public key: path or OpenSSH line
        if (!isset($pubPath) || $pubPath === null) {
            if ($pub !== null) {
                if (is_file($pub)) {
                    $pubPath = $pub;
                } elseif ($isOpenSshPub($pub)) {
                    $pubPath = self::writeTempKey($pub, 'nativesftp_pub_', '.pub', 0644);
                }
            }
        }

        // Try to auto-derive .pub with ssh-keygen -y for unencrypted keys
        if (!isset($pubPath) || $pubPath === null) {
            if (!empty($pass)) {
                throw new \RuntimeException('Public key missing and private key is passphrase-protected; cannot derive .pub non-interactively. Provide pub_key path or contents.');
            }
            $bin = trim((string) @shell_exec('command -v ssh-keygen 2>/dev/null'));
            if ($bin === '') {
                $bin = trim((string) @shell_exec('which ssh-keygen 2>/dev/null'));
            }
            if ($bin === '') {
                throw new \RuntimeException('Public key is required and ssh-keygen not found to derive it. Provide pub_key or install ssh-keygen.');
            }
            $cmd = escapeshellcmd($bin) . ' -y -f ' . escapeshellarg($privPath) . ' 2>/dev/null';
            $publine = @shell_exec($cmd);
            if (!is_string($publine) || trim($publine) === '') {
                throw new \RuntimeException('Failed to derive public key using ssh-keygen -y. Provide pub_key contents or file.');
            }
            $pubPath = self::writeTempKey(trim($publine), 'nativesftp_pub_', '.pub', 0644);
        }

        return [$privPath, $pubPath, $pass];
    }

    /**
     * Write temporary key material to a secure file.
     */
    private static function writeTempKey(string $contents, string $prefix, string $suffix, int $chmod): string
    {
        $dir = sys_get_temp_dir();
        $tmp = tempnam($dir, $prefix);
        if ($tmp === false) {
            throw new \RuntimeException('Failed to create temporary file for key material.');
        }
        $target = $tmp . $suffix;
        if (!@rename($tmp, $target)) {
            $target = $tmp;
        }
        if (@file_put_contents($target, $contents) === false) {
            @unlink($target);
            throw new \RuntimeException('Failed to write key material to temp file.');
        }
        @chmod($target, $chmod);
        return $target;
    }

    // ===================== Working Directory Support =====================
    /** @var string|null Current working directory for SFTP operations */
    private ?string $cwd = null;

    /**
     * Change working directory. Verifies it is a directory on the server.
     */
    public function chdir(string $directory): bool
    {
        $directory = $this->normalizePathNonThrow($directory);
        if (!$this->is_dir($directory)) {
            throw new \RuntimeException("chdir() failed: {$directory} is not a directory or not accessible.");
        }
        $this->cwd = rtrim($directory, '/');
        return true;
    }

    /** Get current working directory (null if unset). */
    public function getCwd(): ?string
    {
        return $this->cwd;
    }

    /** Prepend cwd to relative remote paths. */
    private function applyCwd(string $path): string
    {
        if ($this->cwd === null) return $path;
        if ($path !== '' && $path[0] === '/') return $path;
        return rtrim($this->cwd, '/') . '/' . ltrim($path, '/');
    }

    /** Normalize a path without throwing if it doesn't exist yet. */
    private function normalizePathNonThrow(string $path): string
    {
        $path = trim($path);
        if ($path === '') throw new \InvalidArgumentException('Path cannot be empty.');
        try { return $this->realpath($path); } catch (\Throwable $e) { return '/' . ltrim($path, '/'); }
    }

    // ===================== Convenience / Compatibility =====================

    /** put(): alias to upload() */
    public function put(string $remoteFile, $data, $mode = null): bool
    {
        $remoteFile = $this->applyCwd($remoteFile);
        $this->upload(is_string($data) && is_file($data) ? $data : $data, $remoteFile, true);
        return true;
    }

    /** get(): alias to download(); if $localFile === false, return string when supported. */
    public function get(string $remoteFile, $localFile = false)
    {
        $remoteFile = $this->applyCwd($remoteFile);
        if ($localFile === false) {
            // Download to memory
            $tmp = tmpfile();
            if ($tmp === false) {
                throw new \RuntimeException('Failed to allocate temp memory for get().');
            }
            $meta = stream_get_meta_data($tmp);
            $tmpPath = $meta['uri'] ?? null;
            if (!$tmpPath) {
                fclose($tmp);
                throw new \RuntimeException('Temp stream has no path.');
            }
            $this->download($remoteFile, $tmpPath, true);
            $data = stream_get_contents($tmp);
            fclose($tmp);
            if ($data === false) {
                throw new \RuntimeException('Failed reading from temp stream.');
            }
            return $data;
        }
        $this->download($remoteFile, (string)$localFile, true);
        return true;
    }

    /** file_exists(): alias to exists() */
    public function file_exists(string $path): bool
    {
        $path = $this->applyCwd($path);
        return $this->exists($path);
    }

    /** Basic is_dir using stream stat and listDirs() */
    public function is_dir(string $path): bool
    {
        $path = $this->applyCwd($path);
        try {
            $uri = $this->sftpPath($path);
            $st = @stat($uri);
            if (is_array($st) && isset($st['mode'])) {
                return (($st['mode'] & 0170000) === 0040000);
            }
            // fallback using listDirs on parent dir
            $parent = rtrim(dirname($path), '/');
            $name = basename($path);
            if (method_exists($this, 'listDirs')) {
                $dirs = $this->listDirs($parent === '' ? '/' : $parent);
                if (is_array($dirs) && in_array($name, array_map('strval', $dirs), true)) return true;
            }
        } catch (\Throwable $e) {}
        return false;
    }

    public function is_file(string $path): bool
    {
        $path = $this->applyCwd($path);
        try {
            if (!$this->exists($path)) return false;
            $st = @stat($this->sftpPath($path));
            return is_array($st) && isset($st['mode']) && (($st['mode'] & 0170000) === 0100000);
        } catch (\Throwable $e) { return false; }
    }

    /** nlist(): filenames only; supports recursion */
    public function nlist(string $directory='.', bool $recursive=false): array
    {
        $directory = $this->applyCwd($directory);
        $items = $this->list($directory);
        if (!is_array($items)) return [];
        $names = [];
        foreach ($items as $it) {
            if (is_array($it) && isset($it['name'])) $n = (string)$it['name'];
            elseif (is_string($it)) $n = $it;
            else continue;
            if ($n === '' || $n === '.' || $n === '..') continue;
            $names[] = $n;
        }
        if (!$recursive) return array_values($names);
        $all = [];
        foreach ($names as $n) {
            $all[] = $n;
            $full = rtrim($directory, '/') . '/' . $n;
            if ($this->is_dir($full)) {
                foreach ($this->nlist($full, true) as $child) {
                    $all[] = $n . '/' . $child;
                }
            }
        }
        return $all;
    }

    /** rawlist(): detailed listing built from list() + stat() */
    public function rawlist(string $directory='.', bool $recursive=false): array
    {
        $directory = $this->applyCwd($directory);
        $items = $this->list($directory);
        if (!is_array($items)) return [];
        $out = [];
        foreach ($items as $it) {
            $name = is_array($it) && isset($it['name']) ? (string)$it['name'] : (is_string($it) ? $it : null);
            if (!$name || $name === '.' || $name === '..') continue;
            $full = rtrim($directory, '/') . '/' . $name;
            $st = @stat($this->sftpPath($full));
            $mode = is_array($st) && isset($st['mode']) ? $st['mode'] : null;
            $size = is_array($st) && isset($st['size']) ? (int)$st['size'] : null;
            $mtime= is_array($st) && isset($st['mtime'])? (int)$st['mtime']: null;
            $atime= is_array($st) && isset($st['atime'])? (int)$st['atime']: null;
            $uid  = is_array($st) && isset($st['uid'])  ? (int)$st['uid']  : null;
            $gid  = is_array($st) && isset($st['gid'])  ? (int)$st['gid']  : null;
            $type = $mode !== null ? ((($mode & 0170000) == 0040000) ? 'directory' : 'file') : ($this->is_dir($full) ? 'directory' : 'file');
            $out[$name] = ['type'=>$type,'size'=>$size,'mode'=>$mode,'uid'=>$uid,'gid'=>$gid,'atime'=>$atime,'mtime'=>$mtime];
            if ($recursive && $type === 'directory') {
                foreach ($this->rawlist($full, true) as $cn => $cv) {
                    $out[$name.'/'.$cn] = $cv;
                }
            }
        }
        return $out;
    }

    // Metadata helpers
    public function filetype(string $path): ?string
    {
        $path = $this->applyCwd($path);
        $st = @stat($this->sftpPath($path));
        if (is_array($st) && isset($st['mode'])) {
            $t = $st['mode'] & 0170000;
            return $t == 0040000 ? 'directory' : ($t == 0100000 ? 'file' : null);
        }
        return null;
    }
    public function fileperms(string $path): ?int
    {
        $path = $this->applyCwd($path);
        $st = @stat($this->sftpPath($path));
        return is_array($st) && isset($st['mode']) ? (int)$st['mode'] : null;
    }
    public function fileowner(string $path): ?int
    {
        $path = $this->applyCwd($path);
        $st = @stat($this->sftpPath($path));
        return is_array($st) && isset($st['uid']) ? (int)$st['uid'] : null;
    }
    public function filegroup(string $path): ?int
    {
        $path = $this->applyCwd($path);
        $st = @stat($this->sftpPath($path));
        return is_array($st) && isset($st['gid']) ? (int)$st['gid'] : null;
    }
    public function fileatime(string $path): ?int
    {
        $path = $this->applyCwd($path);
        $st = @stat($this->sftpPath($path));
        return is_array($st) && isset($st['atime']) ? (int)$st['atime'] : null;
    }
    public function filemtime(string $path): ?int
    {
        $path = $this->applyCwd($path);
        $st = @stat($this->sftpPath($path));
        return is_array($st) && isset($st['mtime']) ? (int)$st['mtime'] : null;
    }

    // Mutators
    public function chmod(int $mode, string $path): bool
    {
        if ($mode < 0 || $mode > 07777) {
            throw new \InvalidArgumentException('chmod() invalid mode; expected octal 0-07777.');
        }
        $path = $this->applyCwd($path);
        if (!is_resource($this->sftp)) {
            throw new \RuntimeException('Not connected. Call connect() first.');
        }
        if (function_exists('ssh2_sftp_chmod')) {
            if (!@ssh2_sftp_chmod($this->sftp, $path, $mode)) {
                throw new \RuntimeException(sprintf('ssh2_sftp_chmod(%o, %s) failed.', $mode, $path));
            }
            return true;
        }
        $cmd = 'chmod ' . sprintf('%o', $mode) . ' ' . escapeshellarg($path);
        $this->exec($cmd);
        return true;
    }

    public function chown(int $uid, string $path, ?int $gid = null): bool
    {
        if ($uid < 0) throw new \InvalidArgumentException('chown() uid must be >= 0.');
        if ($gid !== null && $gid < 0) throw new \InvalidArgumentException('chown() gid must be >= 0.');
        $path = $this->applyCwd($path);
        $cmd = 'chown ' . $uid . ($gid === null ? '' : ':' . $gid) . ' ' . escapeshellarg($path);
        $this->exec($cmd);
        return true;
    }

}

/* =======================
 * Example usage:
 * =======================
 * 1) Password auth:
 * loadExtras('nativeSFTP');
 * $client = new NativeSFTP([
 *     'host' => 'sftp.example.com',
 *     'port' => 22,
 *     'username' => 'myuser',
 *     'password' => 'mypassword',
 *     // Optional: verify MD5 hex fingerprint (with or without colons)
 *     // 'fingerprint_hex' => 'ab:cd:ef:12:34:56:78:90:ab:cd:ef:12:34:56:78:90'
 * ]);
 *
 * 2) Public key auth:
 * loadExtras('nativeSFTP');
 * $client = new NativeSFTP([
 *     'host' => 'sftp.example.com',
 *     'username' => 'myuser',
 *     'pub_key' => '/path/to/id_ed25519.pub',
 *     'priv_key' => '/path/to/id_ed25519',
 *     'passphrase' => 'optional-passphrase',
 * ]);
 *
 * try {
 *     $client->connect();
 *     // List a directory
 *     $files = $client->list('/incoming');
 *     echo printValue($files);
 *
 *     // Download the first file
 *     if (!empty($files)) {
 *         $remote = '/incoming/' . $files[0];
 *         $client->download($remote, __DIR__ . '/downloads/' . basename($remote));
 *     }
 *
 *     // Upload a file
 *     $client->upload(__DIR__ . '/to_send/report.csv', '/outgoing/report.csv');
 *
 *     // Make dir, rename, delete examples
 *     // $client->mkdir('/outgoing/newdir', 0755, true);
 *     // $client->rename('/outgoing/report.csv', '/outgoing/report-2025-08-20.csv');
 *     // $client->delete('/outgoing/oldfile.txt');
 *
 * } catch (Throwable $e) {
 *     fwrite(STDERR, "SFTP error: " . $e->getMessage() . PHP_EOL);
 * } finally {
 *     if (isset($client)) $client->disconnect();
 * }
 */
