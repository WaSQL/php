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
        if ($this->privKeyPath && $this->pubKeyPath) {
            $ok = @ssh2_auth_pubkey_file(
                $this->conn,
                $this->username,
                $this->pubKeyPath,
                $this->privKeyPath,
                $this->passphrase ?? ''
            );
        } elseif ($this->password !== null) {
            $ok = @ssh2_auth_password($this->conn, $this->username, $this->password);
        } else {
            throw new InvalidArgumentException('Provide either password OR pub_key + priv_key (with optional passphrase).');
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
        $remotePath = $this->applyCwd($remotePath);

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
        $remotePath = $this->applyCwd($remotePath);

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
        $start = $this->applyCwd($start);

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

        // Ensure destination directory exists
        $parent = rtrim(dirname($remoteTo), '/');
        if ($parent !== '' && $parent !== '.' && $parent !== '/') {
            if (!$this->exists($parent) || !@is_dir($this->sftpPath($parent))) {
                $this->mkdir($parent, 0755, true);
            }
        }

        if (!$overwrite && $this->exists($remoteTo)) {
            throw new RuntimeException("Destination exists: {$remoteTo}");
        }

        $from = $this->sftpPath($remoteFrom);
        $to   = $this->sftpPath($remoteTo);

        if (!@rename($from, $to)) {
            if ($overwrite && $this->exists($remoteTo)) {
                @unlink($this->sftpPath($remoteTo));
                if (@rename($from, $to)) {
                    return;
                }
            }
            throw new RuntimeException("Failed to rename {$remoteFrom} to {$remoteTo}");
        }
    }

    public function mkdir(string $remotePath, int $mode = 0755, bool $recursive = false): void
    {
        $remotePath = $this->applyCwd($remotePath);
        if (!is_resource($this->sftp)) {
            throw new RuntimeException('Not connected. Call connect() first.');
        }
        // Normalize absolute, collapse //, remove trailing slash unless root
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
            if (@is_dir($uri)) {
                continue;
            }
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

    // ===================== CWD support & helpers =====================
    /** @var string|null Working directory for SFTP-relative operations */
    private ?string $cwd = null;

    /**
     * Change SFTP working directory (affects relative remote paths for upload/download/etc).
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

    /** Return the session-local SFTP working directory (or null if not set). */
    public function getCwd(): ?string
    {
        return $this->cwd;
    }

    /** Prepend cwd to relative paths */
    private function applyCwd(string $path): string
    {
        if ($this->cwd === null) return $path;
        if (strlen($path) > 0 && $path[0] === '/') return $path;
        return rtrim($this->cwd, '/') . '/' . ltrim($path, '/');
    }

    /** Normalize without throwing when target does not yet exist. */
    private function normalizePathNonThrow(string $path): string
    {
        $path = trim($path);
        if ($path === '') throw new \InvalidArgumentException('Path cannot be empty.');
        try {
            return $this->realpath($path);
        } catch (\Throwable $e) {
            return '/' . ltrim($path, '/'); // fallback normalized
        }
    }

    // ===================== Aliases & extended API =====================

    /** put(): alias to upload() */
    public function put(string $remoteFile, $data, $mode = null): bool
    {
        $remoteFile = $this->applyCwd($remoteFile);
        return (bool)$this->upload($data, $remoteFile, true);
    }

    /** get(): alias to download(); if $localFile === false, return string when supported. */
    public function get(string $remoteFile, $localFile = false)
    {
        $remoteFile = $this->applyCwd($remoteFile);
        return $this->download($remoteFile, is_string($localFile) ? $localFile : '', is_string($localFile) ? true : true);
    }

    /** file_exists(): alias to exists() */
    public function file_exists(string $path): bool
    {
        $path = $this->applyCwd($path);
        return $this->exists($path);
    }

    /** Basic 'is_dir' using listDirs()/list() */
    public function is_dir(string $path): bool
    {
        $path = $this->applyCwd($path);
        try {
            $parent = rtrim(dirname($path), '/');
            $name = basename($path);
            if (method_exists($this, 'listDirs')) {
                $dirs = $this->listDirs($parent === '' ? '/' : $parent);
                if (is_array($dirs) && in_array($name, array_map('strval', $dirs), true)) return true;
            }
            // Try stream stat
            $meta = @stat($this->sftpPath($path));
            if (is_array($meta)) {
                return ($meta['mode'] & 0170000) == 0040000;
            }
        } catch (\Throwable $e) {}
        return false;
    }

    public function is_file(string $path): bool
    {
        $path = $this->applyCwd($path);
        try {
            if (!$this->exists($path)) return false;
            $meta = @stat($this->sftpPath($path));
            if (is_array($meta)) {
                return ($meta['mode'] & 0170000) == 0100000;
            }
        } catch (\Throwable $e) {}
        return false;
    }

    /** nlist(): filenames only (optionally recursive) */
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

    /** rawlist(): normalize details using list() + stat() when available */
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
            $meta = @stat($this->sftpPath($full));
            $mode = is_array($meta) && isset($meta['mode']) ? $meta['mode'] : null;
            $size = is_array($meta) && isset($meta['size']) ? (int)$meta['size'] : null;
            $mtime= is_array($meta) && isset($meta['mtime'])? (int)$meta['mtime']: null;
            $atime= is_array($meta) && isset($meta['atime'])? (int)$meta['atime']: null;
            $uid  = is_array($meta) && isset($meta['uid'])  ? (int)$meta['uid']  : null;
            $gid  = is_array($meta) && isset($meta['gid'])  ? (int)$meta['gid']  : null;
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

    public function filetype(string $path): ?string
    {
        $path = $this->applyCwd($path);
        $meta = @stat($this->sftpPath($path));
        if (is_array($meta) && isset($meta['mode'])) {
            $t = $meta['mode'] & 0170000;
            return $t == 0040000 ? 'directory' : ($t == 0100000 ? 'file' : null);
        }
        return null;
    }

    public function fileperms(string $path): ?int
    {
        $path = $this->applyCwd($path);
        $meta = @stat($this->sftpPath($path));
        return is_array($meta) && isset($meta['mode']) ? (int)$meta['mode'] : null;
    }

    public function fileowner(string $path): ?int
    {
        $path = $this->applyCwd($path);
        $meta = @stat($this->sftpPath($path));
        return is_array($meta) && isset($meta['uid']) ? (int)$meta['uid'] : null;
    }

    public function filegroup(string $path): ?int
    {
        $path = $this->applyCwd($path);
        $meta = @stat($this->sftpPath($path));
        return is_array($meta) && isset($meta['gid']) ? (int)$meta['gid'] : null;
    }

    public function fileatime(string $path): ?int
    {
        $path = $this->applyCwd($path);
        $meta = @stat($this->sftpPath($path));
        return is_array($meta) && isset($meta['atime']) ? (int)$meta['atime'] : null;
    }

    public function filemtime(string $path): ?int
    {
        $path = $this->applyCwd($path);
        $meta = @stat($this->sftpPath($path));
        return is_array($meta) && isset($meta['mtime']) ? (int)$meta['mtime'] : null;
    }

    public function chmod(int $mode, string $path): bool
    {
        if ($mode < 0 or $mode > 0o7777) {
            throw new \InvalidArgumentException('chmod() invalid mode; expected octal 0-07777.');
        }
        $path = $this->applyCwd($path);
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
