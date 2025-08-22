# `secrets_scanner` — Documentation & Agent Integration Guide

> Version: 1.0 · Maintainer: you · License: MIT (example)

## Overview
`secrets_scanner.py` is a fast, conservative secret-finding tool for local folders. It recursively walks a directory tree, skips binaries/huge files, checks many known patterns (AWS/GitHub/Google/Slack/Stripe/JWT/DB URIs/etc.), and flags high-entropy candidates (base64/hex). By default, it prints **nothing** if no findings are detected.

---

## Key Features
- **Recursive scanning** of any local directory (`--path`).
- **Thorough detection**: curated regexes + high-entropy checks for base64/hex candidates.
- **Noise control**: skip binary files, skip large files (default 5MB), skip common folders (`.git`, `node_modules`, `dist`, `build`, etc.).
- **Allowlists**:
  - Inline: add `# secret-scan: ignore` to suppress a specific line.
  - File: `--ignore-file` with one regex per line.
- **Agent/machine-friendly output** with `--format json`.
- **CI-friendly**: `--ci` returns **exit code 2** on findings, so pipelines can fail fast.
- **Safe defaults** and robust error handling (non-fatal on unreadable files).

---

## Installation
No package install required. Use Python 3.8+.

```bash
# Option A: Run directly
python secrets_scanner.py --path .

# Option B: Make executable
chmod +x secrets_scanner.py
./secrets_scanner.py --path .
```

---

## Command-Line Interface

```bash
python secrets_scanner.py --path <DIR> [--format text|json] [--ci] \
  [--max-bytes BYTES] [--exclude NAMES] [--ignore-file FILE] \
  [--b64-entropy N] [--hex-entropy N] [--follow-symlinks]
```

**Arguments**
- `--path` *(required)*: Directory root to scan.
- `--format` *(optional)*: `text` (default) or `json`.
- `--ci` *(optional)*: Make exit code **2** when any findings are detected.
- `--max-bytes` *(optional)*: Max file size to scan; default `5242880` (5MB).
- `--exclude` *(optional)*: Comma-separated directory **names** to ignore. Defaults include `.git,node_modules,vendor,dist,build,__pycache__,.venv,venv,.idea,.vscode,.next,.terraform,target,.gradle`.
- `--ignore-file` *(optional)*: Path to a regex allowlist file. Lines matching any regex are skipped.
- `--b64-entropy` *(optional)*: Shannon entropy threshold for base64 candidates (default `4.5`).
- `--hex-entropy` *(optional)*: Shannon entropy threshold for hex candidates (default `3.0`).
- `--follow-symlinks` *(optional)*: Follow symlinks during traversal.

**Exit Codes**
- `0` — No findings **or** findings printed but `--ci` not set.
- `2` — Findings detected **and** `--ci` set.
- `1` — Usage/runtime error (e.g., bad path).

---

## Output

### Text format (default)
- **No findings**: prints nothing (blank stdout).
- **Findings**: one per line: `file:line — type | value`

Example:
```
src/util/auth.js:42 — GitHub Personal Token | ghp_************************
app/.env:7 — Stripe Secret Key | sk_live_************************
```

### JSON format (`--format json`)
An array of objects:
```json
[
  {
    "file": "src/util/auth.js",
    "line": 42,
    "type": "GitHub Personal Token",
    "value": "ghp_..."
  }
]
```

#### JSON Schema (informal)
```json
{
  "type": "array",
  "items": {
    "type": "object",
    "properties": {
      "file": {"type": "string"},
      "line": {"type": "integer", "minimum": 1},
      "type": {"type": "string"},
      "value": {"type": "string"}
    },
    "required": ["file", "line", "type", "value"]
  }
}
```

---

## Detection Coverage (selected examples)
- **Cloud/Infra**: AWS Access Key (`AKIA…`), AWS Secret Key, AWS Session Token (`ASIA…`), Azure GUIDs, GCP Service Account key JSON.
- **Generic**: `api_key`/`token`/`secret`, password assignments, Bearer tokens, basic-auth-in-URL.
- **Keys/Tokens**: PEM private keys, SSH public keys, JWTs.
- **Providers**: Google API key (`AIza…`), GitHub tokens (`ghp_`, `gho_`, `ghu_`, `ghs_`), Slack tokens & webhooks, Discord, Stripe keys (`sk_…`, `rk_…`), Twilio, SendGrid, Mailgun, Algolia, Cloudflare, Shopify, Heroku.
- **Telemetry/Webhooks**: Firebase URLs, Sentry DSNs.
- **DB URIs**: Postgres, MySQL, Mongo connection strings.
- **High-Entropy**: 20+ char base64/hex candidates exceeding entropy thresholds.

> ⚠️ Patterns are intentionally conservative to reduce false positives. Some secrets will still be missed; conversely, some matches may be benign. Use allowlists where appropriate.

---

## Allowlisting
- **Inline**: append `# secret-scan: ignore` to a line to suppress a specific match.
- **Regex File**: create a text file where each line is a regex. Pass via `--ignore-file`. Example:
  ```
  ^TEST_\w+_TOKEN$
  ^example-api-key-.*
  ```

---

## Performance & Limits
- Skips files >5MB by default (tune via `--max-bytes`).
- Treats files as binary if they contain NUL bytes or have a high proportion of non-text bytes.
- Traversal prunes common heavy directories; customize via `--exclude`.

---

## Security Notes
- Handle findings as **sensitive** data. Avoid logging `value` to shared systems; prefer secure channels.
- If real secrets are found, rotate the credentials immediately, remove them from history, and add prevention steps (pre-commit hooks, CI checks).

---

## Agentic Integration (LLM/Automation Friendly)

### Deterministic Contract
- **Command**: `python secrets_scanner.py --path <DIR> --format json` (plus `--ci` when you want a failing exit on findings).
- **Stdout**: JSON array of findings **or** empty string if none. No other noise on stdout.
- **Stderr**: operational errors only.
- **Exit codes**: `0` (clean), `2` (findings + `--ci`), `1` (error).

### Suggested Agent Prompts
- **Scan & Triage**
  - *Action*: `python secrets_scanner.py --path . --format json --ci`
  - *Then*: parse JSON; for each finding, propose remediation steps and create issues/PRs.
- **Pre-merge Gate**
  - *Action*: `python secrets_scanner.py --path $CHECKOUT --format json --ci`
  - *Policy*: Block merge on exit code `2`.
- **Targeted Re-scan**
  - *Action*: `python secrets_scanner.py --path ./services/payments --format json`
  - *Then*: only comment on files changed in the current diff.

### Example Agent Playbook (pseudocode)
```yaml
steps:
  - name: scan
    run: python secrets_scanner.py --path . --format json --ci
  - name: parse
    if: steps.scan.exit_code in [0,2]
    run: |
      results = json.load(stdin)
      if not results:
        print("CLEAN")
      else:
        # redact long values
        for r in results:
          r["value"] = r["value"][:8] + "…" if len(r["value"])>12 else r["value"]
        save_artifact(results)
  - name: policy
    if: steps.scan.exit_code == 2
    run: fail("Secrets detected. See artifact.")
```

### JSON Redaction Guidance
Agents should **redact** `value` fields before posting to public logs (e.g., keep first 6–8 chars).

### Idempotence & Retries
- Scans are read-only and idempotent.
- Safe to retry; ensure the working tree is consistent (e.g., after cleanup commits).

---

## CI Examples

**GitHub Actions**
```yaml
- name: Secret scan
  run: python secrets_scanner.py --path . --format json --ci > scan.json || true
- name: Upload artifact
  uses: actions/upload-artifact@v4
  with:
    name: secret-scan
    path: scan.json
- name: Fail if findings
  run: |
    if [ -s scan.json ] && jq 'length>0' scan.json >/dev/null; then
      echo "Secrets found" >&2
      exit 2
    fi
```

**pre-commit**
```yaml
-   repo: local
    hooks:
    -   id: secrets-scanner
        name: secrets-scanner
        entry: python secrets_scanner.py --path . --format json --ci
        language: system
        pass_filenames: false
```

---

## Remediation Checklist (for agents & humans)
1. **Confirm** false positive vs. real secret.
2. If real:
   - Rotate/revoke the credential.
   - Remove from codebase; use env vars or a secret manager.
   - Purge from git history if committed (`git filter-repo` or BFG).
   - Add an allowlist only if it is a **known test fixture**.
3. **Re-scan** to verify clean state.

---

## Roadmap Ideas
- Add `--version` flag and semantic versioning.
- Support `.gitignore`-style allowlists.
- Provider-specific validators (e.g., checksum for Google keys).
- Optional SARIF output for code scanning UIs.

---

## License & Attribution
- Include your chosen license file. Patterns inspired by common public references and provider docs; curated and simplified here.

