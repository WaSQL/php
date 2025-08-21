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
        $path = $this->sftpPath($remoteFile);
        if (!@unlink($path)) {
            throw new RuntimeException("Failed to delete remote file: {$remoteFile}");
        }
    }

    public function rename(string $remoteFrom, string $remoteTo, bool $overwrite = true): void
    {
        if (!$overwrite && $this->exists($remoteTo)) {
            throw new RuntimeException("Destination exists: {$remoteTo}");
        }
        $from = $this->sftpPath($remoteFrom);
        $to   = $this->sftpPath($remoteTo);
        if (!@rename($from, $to)) {
            throw new RuntimeException("Failed to rename {$remoteFrom} to {$remoteTo}");
        }
    }

    public function mkdir(string $remotePath, int $mode = 0755, bool $recursive = false): void
    {
        $path = $this->sftpPath($remotePath);
        if ($recursive) {
            $parts = array_filter(explode('/', trim($remotePath, '/')));
            $build = '';
            foreach ($parts as $p) {
                $build .= "/{$p}";
                $dir = $this->sftpPath($build);
                if (!@is_dir($dir)) {
                    if (!@mkdir($dir, $mode)) {
                        throw new RuntimeException("Failed to create directory: {$build}");
                    }
                }
            }
        } else {
            if (!@mkdir($path, $mode)) {
                throw new RuntimeException("Failed to create directory: {$remotePath}");
            }
        }
    }

    public function rmdir(string $remotePath): void
    {
        $path = $this->sftpPath($remotePath);
        if (!@rmdir($path)) {
            throw new RuntimeException("Failed to remove directory: {$remotePath}");
        }
    }

    public function exists(string $remotePath): bool
    {
        $path = $this->sftpPath($remotePath);
        return @file_exists($path);
    }

    public function filesize(string $remotePath): int
    {
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
