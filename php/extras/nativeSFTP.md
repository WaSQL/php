# NativeSFTP – Standalone SFTP Wrapper (phpseclib-free)

> **✅ No longer requires `ext-ssh2`.** The class picks a driver at construction: `ext-ssh2`
> when the extension is loaded (unchanged code path), otherwise the vendored **phpseclib 3**
> in `php/extras/phpseclib3/` — pure PHP, no extension, no root. Every method behaves the
> same either way. Force one with `'driver' => 'ssh2'|'phpseclib'` in the config array, read
> it back with `getDriver()`.
>
> The fallback matters beyond missing extensions: ext-ssh2, libssh (php-curl's `sftp://`) and
> the OpenSSH CLI all verify host key signatures through OpenSSL, so RHEL 9's DEFAULT crypto
> policy makes them refuse `ssh-rsa`-only servers such as Salesforce Marketing Cloud's
> GlobalSCAPE endpoint — with no client-side flag that overrides it. phpseclib connects fine.
> See *SFTP — which extra to use* in `wasql_reference.md`.
>
> Driver differences: `pwd()` uses the SFTP protocol under phpseclib (works on SFTP-only
> servers) but shells out to `pwd` under ssh2; a relative path stays relative to the login
> directory under phpseclib, while ssh2 treats every path as absolute from `/`.
> `php/extras/sftp3.php` remains available as a lighter procedural API over the same library.

`NativeSFTP` is a production-ready, dependency-light SFTP client.  
This doc covers the **compat surface** we expose so agentic tools (and humans) can drive it predictably, without phpseclib.

## Quick Start

```php
$sftp = new NativeSFTP();
$sftp->connect('sftp.example.com', 22, ['username' => 'alice', 'password' => 'secret']);

// Upload (alias)
$sftp->put('/remote/file.txt', "hello world");

// Download to string (alias)
$data = $sftp->get('/remote/file.txt', false);

// Existence & metadata
if ($sftp->file_exists('/remote/file.txt')) {
  $type = $sftp->filetype('/remote/file.txt'); // 'file' | 'directory' | 'link' | ...
  $perms = $sftp->fileperms('/remote/file.txt'); // e.g. 0100644 or null if unknown
}

// Listings
$names = $sftp->nlist('/remote');           // ['a.txt', 'dir', ...]
$detail = $sftp->rawlist('/remote');        // ['a.txt' => ['size'=>..., 'mtime'=>..., ...], ...]

// Mutators
$sftp->chmod(0644, '/remote/file.txt');
$sftp->chown(1000, '/remote/file.txt', 1000); // uid, optional gid

$sftp->disconnect();
```

## Methods

### Aliases (compat)
- `put(string $remoteFile, $data, $mode = null): bool` → uploads (alias of `upload()`)
- `get(string $remoteFile, $localFile = false)` → downloads (alias of `download()`)
- `file_exists(string $path): bool` → alias of `exists()`
- `nlist(string $directory='.', bool $recursive=false): array`
- `rawlist(string $directory='.', bool $recursive=false): array`

**Notes**
- `nlist()` returns **filenames** only; supports recursive expansion.
- `rawlist()` normalizes any metadata your `list()` provides; if not available, it synthesizes entries using `filesize()`, `is_dir()`, and (optionally) remote `stat` via `exec()`.

### Metadata helpers
- `filetype(string $path): ?string` – `'file' | 'directory' | 'link' | ...'` (best-effort)
- `fileperms(string $path): ?int` – POSIX mode (e.g., `0100644`) or `null`
- `fileowner(string $path): ?int` – UID or `null`
- `filegroup(string $path): ?int` – GID or `null`
- `fileatime(string $path): ?int` – epoch seconds or `null`
- `filemtime(string $path): ?int` – epoch seconds or `null`

> These use remote `stat` via `exec()` when available (GNU/BSD compatible).  
> If `exec()` isn’t supported in your build, they return `null` rather than guessing.

### Mutators
- `chmod(int $mode, string $path): bool` – uses remote `chmod` via `exec()`; validates mode and throws on errors.
- `chown(int $uid, string $path, ?int $gid = null): bool` – uses remote `chown` via `exec()`; numeric IDs only.

## Operational Guidance (Agentic-friendly)

- **Idempotence**: Use `file_exists()` before `put()` to avoid accidental overwrites.
- **Selective sync**: Use `rawlist()` to compare `mtime/size` prior to transfers.
- **Permissions**: Always `chmod()` after `put()` if you rely on strict modes.
- **Portability**: `file*` metadata uses `stat` via `exec()` if present. On hosts without `stat/chmod/chown`, methods will throw (mutators) or return `null` (getters). Handle gracefully.
- **Security**: Paths are sanitized and single-quoted for remote commands to avoid injection. Avoid passing user-controlled strings unvalidated.

## Return Shapes

- `nlist('/dir')` → `['a.txt','b','subdir', ...]`
- `rawlist('/dir')` → 
  ```php
  [
    'a.txt' => ['type'=>'file','size'=>123,'mode'=>0100644,'uid'=>1000,'gid'=>1000,'atime'=>1710000000,'mtime'=>1710000100],
    'subdir' => ['type'=>'directory','size'=>null,'mode'=>0040755,'uid'=>1000,'gid'=>1000,'atime'=>null,'mtime'=>1710000200],
  ]
  ```

## Troubleshooting

- **`RuntimeException: Remote exec() is not supported`**  
  Your build doesn’t expose `exec()`. Metadata getters will return `null`, and `chmod/chown` can’t run. Either enable `exec()` or implement protocol-level chmod/chown in `NativeSFTP`.

- **BSD vs GNU `stat`**  
  We auto-try GNU (`stat -c`) then BSD (`stat -f`). If both fail, metadata remains `null`.


## Working Directory (`chdir`)

Set a session-local working directory that is **prepended to all relative remote paths** for uploads, downloads, listings, and metadata:

```php
$sftp->chdir('/var/www');
$sftp->put('index.html', '<h1>Hello</h1>');   // writes to /var/www/index.html
echo $sftp->get('index.html', false);         // reads /var/www/index.html
$names = $sftp->nlist('.');                    // lists /var/www
```

Use `getCwd()` to read the current working directory (or `null` if not set).
