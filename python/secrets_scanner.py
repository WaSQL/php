#!/usr/bin/env python3
"""
Recursive secrets scanner for local folders with hierarchical .gitignore support (Git-like).

Goals:
- Walk a local directory tree and scan text files line-by-line.
- Detect many common secret/token formats + high-entropy strings (base64/hex).
- Respect .gitignore files at the root AND in any subdirectory, like `git status`.
- Print a concise list of matches as either newline-delimited text or JSON.
- Print **nothing** (blank stdout) if no findings are detected (default behavior).
- Robust, production-ready CLI with sane defaults and guardrails.

Dependency:
  pip install pathspec

Usage examples:
  # Basic scan (prints nothing if clean)
  python secrets_scanner.py --path .

  # JSON output suitable for tooling
  python secrets_scanner.py --path ./src --format json

  # Fail CI if secrets are found (non-zero exit)
  python secrets_scanner.py --path . --ci

  # Ignore common build/vendor dirs and custom patterns
  python secrets_scanner.py --path . --exclude 'vendor,dist,build' --ignore-file .secretsscanignore

Exit codes:
  0 = no findings
  2 = findings detected (only when --ci is set)
  1 = usage or runtime error

Notes:
- Binary files, huge files, and excluded/ignored paths are skipped.
- You can allowlist specific lines via inline comment:  # secret-scan: ignore
- You can define a regex allowlist file via --ignore-file.
"""
from __future__ import annotations

import argparse
import json
import math
import os
import re
import sys
from dataclasses import dataclass
from pathlib import Path
from typing import Dict, Iterator, List, Optional, Pattern, Tuple
from collections import Counter

# Required for full Git-like .gitignore behavior
try:
    import pathspec  # type: ignore
    from pathspec.patterns import GitWildMatchPattern  # noqa: F401 (ensures backend available)
except Exception:
    print("error: pathspec library required for hierarchical .gitignore support. Install with: pip install pathspec", file=sys.stderr)
    sys.exit(1)

# -----------------------------
# Constants / Defaults
# -----------------------------
DEFAULT_MAX_FILE_BYTES = 5 * 1024 * 1024  # 5 MB
DEFAULT_EXCLUDE_DIRS = {
    ".git", "node_modules", "vendor", "dist", "build", "__pycache__",
    ".venv", "venv", ".idea", ".vscode", ".next", ".terraform", "target", ".gradle"
}

BASE64_CHARS = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/="
HEX_CHARS = "1234567890abcdefABCDEF"

INLINE_IGNORE_MARKER = "secret-scan: ignore"

# -----------------------------
# Entropy helpers
# -----------------------------

def calculate_shannon_entropy(data: str) -> float:
    if not data:
        return 0.0
    freq = Counter(data)
    length = len(data)
    ent = 0.0
    for count in freq.values():
        p = count / length
        ent -= p * math.log(p, 2)
    return ent

# -----------------------------
# Patterns
# -----------------------------
PATTERNS: Dict[str, str] = {
    # Cloud / Infra
    "AWS Access Key": r"\bAKIA[0-9A-Z]{16}\b",
    "AWS Secret Key": r"\b(?:aws)?_?secret(?:_access)?_?key\b\s*[:=]\s*['\"]?[A-Za-z0-9/+=]{40}['\"]?",
    "AWS Session Token": r"\bASIA[0-9A-Z]{16}\b",
    "Azure Subscription/Client ID": r"\b[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}\b",
    "GCP Service Account Key": r"\b" r"\{\s*\"type\"\s*:\s*\"service_account\"" r"",

    # Generic secrets
    "Generic API Key": r"(?i)\b(api[_-]?key|token|secret)\b\s*[:=]\s*['\"]?[A-Za-z0-9_\-]{20,}['\"]?",
    "Password Assignment": r"(?i)\b(password|pwd|pass)\b\s*[:=]\s*['\"]([^'\"\\r\\n]{6,})['\"]",
    "Bearer Token": r"\bBearer\s+[A-Za-z0-9\-._~+\/]+=*\b",
    "Basic Auth in URL": r"\b[a-zA-Z]{3,10}:\/\/[^\s:@\/]+:[^\s@\/]+@[^\s]+",

    # Keys / Tokens
    "Private Key Block": r"-----BEGIN\s+(?:RSA|DSA|EC|OPENSSH|PRIVATE)\s+KEY-----[\s\S]*?-----END\s+(?:RSA|DSA|EC|OPENSSH|PRIVATE)\s+KEY-----",
    "SSH Public Key": r"\b(ssh-rsa|ssh-ed25519|ecdsa-sha2-nistp256|sk-ssh-ed25519@openssh.com|sk-ecdsa-sha2-nistp256@openssh.com)\s+[A-Za-z0-9+/=]+\b",
    "JWT": r"\beyJ[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}\b",

    # Provider-specific
    "Google API Key": r"\bAIza[0-9A-Za-z\-_]{35}\b",
    "GitHub Personal Token": r"\bghp_[0-9A-Za-z]{36}\b",
    "GitHub OAuth Token": r"\bgho_[0-9A-Za-z]{36}\b",
    "GitHub App Token": r"\b(?:ghu|ghs)_[0-9A-Za-z]{36}\b",
    "Slack Token": r"\bxox[baprs]-[A-Za-z0-9\-]{10,}\b",
    "Slack Webhook": r"\bhttps?://hooks\.slack\.com/services/[A-Za-z0-9_\-]+/[A-Za-z0-9_\-]+/[A-Za-z0-9_\-]+\b",
    "Discord Token": r"\b[NM][A-Za-z\d]{23}\.[\w-]{6}\.[\w-]{27}\b",
    "Stripe Secret Key": r"\bsk_(live|test)_[0-9a-zA-Z]{24}\b",
    "Stripe Restricted Key": r"\brk_(live|test)_[0-9a-zA-Z]{24}\b",
    "Twilio API Key": r"\bSK[0-9a-fA-F]{32}\b",
    "SendGrid API Key": r"\bSG\.[0-9A-Za-z\-_]{22}\.[0-9A-Za-z\-_]{43}\b",
    "Mailgun Key": r"\bkey-[0-9A-Za-z]{32}\b",
    "Algolia Key": r"\b(?:ALGOLIA|algolia)_(?:ADMIN|SEARCH)_KEY\b\s*[:=]\s*['\"][0-9a-f]{32}['\"]",
    "Cloudflare Token": r"\b(?:CF|cloudflare)[-_]?(?:API|GLOBAL)?[_-]?KEY\b\s*[:=]\s*['\"][A-Za-z0-9]{20,}['\"]",
    "Shopify Access Token": r"\bshpat_[0-9a-f]{32}\b",
    "Heroku API Key": r"\b[Hh][Ee][Rr][Oo][Kk][Uu].*[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}\b",

    # URLs / Webhooks
    "Firebase URL": r"\b[a-z0-9.-]+\.firebaseio\.com\b",
    "Sentry DSN": r"\bhttps?://[0-9a-f]{32}@[A-Za-z0-9_.-]+/\d+\b",

    # Database connection strings (very generic – kept conservative)
    "Postgres URI": r"\bpostgres(?:ql)?://[^\s]+\b",
    "MySQL URI": r"\bmysql://[^\s]+\b",
    "Mongo URI": r"\bmongodb(?:\+srv)?:\/\/[^\s]+\b",
}

# High-entropy candidate token (word-ish chunk of 20+ base64/hex chars)
HIGH_ENTROPY_CANDIDATE = r"([A-Za-z0-9+/=]{20,}|[A-Fa-f0-9]{32,})"

# -----------------------------
# Data structures
# -----------------------------
@dataclass
class Finding:
    file: str
    line: int
    type: str
    value: str

    def to_dict(self) -> dict:
        return {"file": self.file, "line": self.line, "type": self.type, "value": self.value}

# -----------------------------
# Utilities
# -----------------------------

def is_probably_binary(path: Path, sample_size: int = 8192) -> bool:
    try:
        with path.open("rb") as f:
            chunk = f.read(sample_size)
        if b"\x00" in chunk:
            return True
        # If >30% bytes are non-texty, treat as binary
        text_bytes = bytearray({7, 8, 9, 10, 12, 13, 27} | set(range(0x20, 0x100)))
        nontext = sum(1 for b in chunk if b not in text_bytes)
        return (len(chunk) > 0 and (nontext / len(chunk)) > 0.30)
    except Exception:
        return True  # play it safe

def compile_patterns(patterns: Dict[str, str]) -> Dict[str, Pattern[str]]:
    compiled: Dict[str, Pattern[str]] = {}
    for name, pat in patterns.items():
        try:
            compiled[name] = re.compile(pat)
        except re.error:
            # Skip invalid patterns to avoid breaking the scan
            pass
    return compiled

def load_ignore_regexes(ignore_file: Optional[Path]) -> List[Pattern[str]]:
    regs: List[Pattern[str]] = []
    if not ignore_file:
        return regs
    try:
        with ignore_file.open("r", encoding="utf-8", errors="ignore") as f:
            for line in f:
                s = line.strip()
                if not s or s.startswith("#"):
                    continue
                try:
                    regs.append(re.compile(s))
                except re.error:
                    # Ignore invalid regex lines
                    pass
    except FileNotFoundError:
        pass
    return regs

def line_is_allowlisted(line: str, ignore_regexes: List[Pattern[str]]) -> bool:
    if INLINE_IGNORE_MARKER in line:
        return True
    return any(r.search(line) for r in ignore_regexes)

# -----------------------------
# .gitignore support (hierarchical, Git-like)
# -----------------------------

class GitIgnoreStack:
    """
    Maintains a stack of (base_dir, PathSpec) where each spec's patterns are
    interpreted relative to base_dir (like Git). For a given file/dir path,
    we check each spec using the path relative to its base.
    """
    def __init__(self) -> None:
        self._specs: List[Tuple[Path, pathspec.PathSpec]] = []

    def push_from_dir(self, base_dir: Path) -> None:
        gi = base_dir / ".gitignore"
        if gi.exists():
            try:
                with gi.open("r", encoding="utf-8", errors="ignore") as f:
                    spec = pathspec.PathSpec.from_lines("gitwildmatch", f)
                    self._specs.append((base_dir, spec))
            except Exception:
                # Ignore malformed .gitignore files to remain robust
                return

    def clone(self) -> "GitIgnoreStack":
        clone = GitIgnoreStack()
        clone._specs = list(self._specs)
        return clone

    def matches(self, path: Path) -> bool:
        # A path is ignored if it matches any spec when interpreted relative to the spec's base dir
        for base, spec in self._specs:
            try:
                rel = path.relative_to(base)
            except ValueError:
                # If the path is not under base, skip this spec
                continue
            if spec.match_file(rel.as_posix()):
                return True
        return False

# -----------------------------
# Scanner
# -----------------------------

def scan_file(
    path: Path,
    compiled_patterns: Dict[str, Pattern[str]],
    base64_entropy_threshold: float,
    hex_entropy_threshold: float,
    ignore_regexes: List[Pattern[str]],
) -> Iterator[Finding]:
    try:
        with path.open("r", encoding="utf-8", errors="ignore") as f:
            for idx, line in enumerate(f, start=1):
                if line_is_allowlisted(line, ignore_regexes):
                    continue
                # Pattern matches
                for name, cre in compiled_patterns.items():
                    for m in cre.finditer(line):
                        val = m.group(0)
                        if val:
                            yield Finding(str(path), idx, name, val.strip())
                # High entropy candidates
                for m in re.finditer(HIGH_ENTROPY_CANDIDATE, line):
                    candidate = m.group(1)
                    if not candidate:
                        continue
                    if all(c in BASE64_CHARS for c in candidate) and calculate_shannon_entropy(candidate) > base64_entropy_threshold:
                        yield Finding(str(path), idx, "High Entropy Base64", candidate)
                    elif all(c in HEX_CHARS for c in candidate) and calculate_shannon_entropy(candidate) > hex_entropy_threshold:
                        yield Finding(str(path), idx, "High Entropy Hex", candidate)
    except (UnicodeDecodeError, OSError):
        # Skip unreadable files silently to stay robust
        return

def walk_and_scan(
    root: Path,
    exclude_dirs: set[str],
    max_file_bytes: int,
    compiled_patterns: Dict[str, Pattern[str]],
    base64_entropy_threshold: float,
    hex_entropy_threshold: float,
    ignore_regexes: List[Pattern[str]],
    follow_symlinks: bool,
) -> List[Finding]:
    findings: List[Finding] = []
    # Map each visited directory to its GitIgnoreStack (inherited + local .gitignore)
    dir_specs: Dict[Path, GitIgnoreStack] = {}

    # Initialize root stack (may include root .gitignore)
    root_stack = GitIgnoreStack()
    root_stack.push_from_dir(root)
    dir_specs[root] = root_stack

    for dirpath, dirnames, filenames in os.walk(root, topdown=True, followlinks=follow_symlinks):
        current_dir = Path(dirpath)

        # Inherit parent's stack and apply this directory's .gitignore
        if current_dir == root:
            current_stack = dir_specs[current_dir]
        else:
            parent = current_dir.parent
            parent_stack = dir_specs.get(parent, GitIgnoreStack())
            current_stack = parent_stack.clone()
            current_stack.push_from_dir(current_dir)
            dir_specs[current_dir] = current_stack

        # Prune traversal: remove excluded dirs and those ignored by .gitignore
        pruned_dirnames: List[str] = []
        for d in dirnames:
            dpath = current_dir / d
            # Skip if in static exclude list
            if d in exclude_dirs:
                continue
            # Skip if matched by any .gitignore in the stack
            if current_stack.matches(dpath):
                continue
            pruned_dirnames.append(d)
        dirnames[:] = pruned_dirnames  # mutate in place to control descent

        # Process files
        for fname in filenames:
            fpath = current_dir / fname
            # Skip if ignored by .gitignore rules
            if current_stack.matches(fpath):
                continue
            try:
                if fpath.is_symlink() and not follow_symlinks:
                    continue
                if not fpath.is_file():
                    continue
                if fpath.stat().st_size > max_file_bytes:
                    continue
                if is_probably_binary(fpath):
                    continue
            except OSError:
                continue

            findings.extend(
                scan_file(
                    fpath,
                    compiled_patterns,
                    base64_entropy_threshold,
                    hex_entropy_threshold,
                    ignore_regexes,
                )
            )
    return findings

# -----------------------------
# CLI
# -----------------------------

def parse_args(argv: Optional[List[str]] = None) -> argparse.Namespace:
    p = argparse.ArgumentParser(
        description="Recursively scan a local folder for secrets, tokens, and high-entropy strings (respects hierarchical .gitignore like Git).",
    )
    p.add_argument("--path", required=True, help="Directory to scan (e.g., . or ./src)")
    p.add_argument("--format", choices=["text", "json"], default="text", help="Output format. Default: text")
    p.add_argument("--ci", action="store_true", help="Exit with code 2 if findings are detected")
    p.add_argument("--max-bytes", type=int, default=DEFAULT_MAX_FILE_BYTES, help="Max file size to scan in bytes (default: 5MB)")
    p.add_argument("--exclude", default=",".join(sorted(DEFAULT_EXCLUDE_DIRS)), help="Comma-separated dir names to exclude (exact name match)")
    p.add_argument("--ignore-file", type=str, default=None, help="Path to a regex allowlist file; lines matching any regex will be ignored")
    p.add_argument("--b64-entropy", type=float, default=4.5, help="Threshold for base64 high-entropy detection (default: 4.5)")
    p.add_argument("--hex-entropy", type=float, default=3.0, help="Threshold for hex high-entropy detection (default: 3.0)")
    p.add_argument("--follow-symlinks", action="store_true", help="Follow symlinks during traversal")
    return p.parse_args(argv)

def main(argv: Optional[List[str]] = None) -> int:
    try:
        args = parse_args(argv)
        root = Path(args.path).resolve()
        if not root.exists() or not root.is_dir():
            print(f"error: path not found or not a directory: {root}", file=sys.stderr)
            return 1

        exclude_dirs = {s.strip() for s in args.exclude.split(',') if s.strip()}
        compiled = compile_patterns(PATTERNS)
        ignore_regexes = load_ignore_regexes(Path(args.ignore_file)) if args.ignore_file else []

        findings = walk_and_scan(
            root=root,
            exclude_dirs=exclude_dirs,
            max_file_bytes=args.max_bytes,
            compiled_patterns=compiled,
            base64_entropy_threshold=args.b64_entropy,
            hex_entropy_threshold=args.hex_entropy,
            ignore_regexes=ignore_regexes,
            follow_symlinks=args.follow_symlinks,
        )

        if not findings:
            # Print nothing on stdout by default when clean
            return 0

        # Present findings
        if args.format == "json":
            print(json.dumps([f.to_dict() for f in findings], indent=2))
        else:
            for f in findings:
                # file:line — type | value (value trimmed to avoid massive noise)
                val = f.value
                if len(val) > 120:
                    val = val[:117] + "..."
                print(f"{f.file}:{f.line} — {f.type} | {val}")

        return 2 if args.ci else 0

    except KeyboardInterrupt:
        print("aborted", file=sys.stderr)
        return 1
    except Exception as e:
        print(f"error: {e}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    sys.exit(main())
