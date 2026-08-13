# Claude Talk

Two-way audio/visual attention for Claude Code sessions:

- **Claude → you**: a hook speaks, flashes the taskbar, and pops a toast when Claude needs you -
  permission prompt, idle wait, or a finished turn - unless you're already looking at the window.
- **You → Claude**: built-in `/voice` dictation (hold Space to talk) - no setup needed, see below.

No plugins, no modules. One PowerShell script (`speak-notification.ps1`) and two hook entries in
`~/.claude/settings.json`.

**Platform**: Windows, Windows PowerShell 5.1 (no PowerShell 7 needed). See "Known scope" for what
is and isn't verified, and for what a macOS/Linux port would involve.

---

## How it works

Claude Code fires **hooks** at lifecycle events. Two are used here:

| Event | Fires when | What this setup does |
|---|---|---|
| `Notification` | A permission prompt appears, or the session sits idle waiting on you (~60s) | **Full treatment**: taskbar flash + spoken phrase + toast popup - see "Attention channels" below. |
| `Stop` | Every time Claude finishes a turn (also on `/clear`, `/resume`, compaction) | **Chime only**. Fires constantly, so no speech/flash/toast here - just a short sound. |

Both events run the same script with a `-Mode Notification` or `-Mode Stop` argument picking the
behavior. The hook payload arrives as JSON on stdin; in `Notification` mode the script parses it into
a short spoken phrase.

### The payload, and what drives the phrase

The `Notification` payload looks like this:

```json
{"session_id":"…","transcript_path":"…","cwd":"C:\\your\\project","prompt_id":"…",
 "hook_event_name":"Notification","message":"Claude needs your permission",
 "notification_type":"permission_prompt"}
```

**`notification_type` is the signal to branch on, not `message`.** Two values occur in practice:

| `notification_type` | `message` | Spoken as |
|---|---|---|
| `permission_prompt` | `Claude needs your permission` | "Claude needs permission in *project*" |
| `idle_prompt` | `Claude is waiting for your input` | "Claude is waiting on you in *project*" |

*project* is the last segment of the payload's `cwd` (falling back to the current directory), so with
several sessions open you can tell which one wants you. Regex-matching the prose `message` is kept
only as a fallback for payload shapes that omit `notification_type`.

### The focus check (the key bulletproofing decision)

Before doing anything else, the script walks up the process tree from itself to find the console/
terminal window that owns the session, then compares that window handle against
`GetForegroundWindow()`. **If you're already looking at that window, the hook does nothing at all** -
no chime, no flash, no toast, no speech. There's nothing to grab your attention *for* if you're
already there.

The walk takes the first ancestor with a non-zero `MainWindowHandle`. Under Windows Terminal the
chain looks like this (the handle appears only at the last hop):

```
powershell (0) -> claude (0) -> cmd (0) -> WindowsTerminal (0x10A52)   <- the owning window
```

If the window can't be resolved (unusual host, permissions issue, etc.), the check fails open - it
always notifies rather than silently going quiet. A missing signal should never look like "nothing to
report."

Suppressions are recorded in the payload log as `{"_claudetalk":"suppressed","reason":"focused",…}`,
so "is the focus check misfiring?" is answerable after the fact rather than by guesswork.

### Attention channels (Notification mode only)

Three independent channels, each degrading gracefully if the others are unavailable:

1. **Taskbar flash** (`FlashWindowEx`, Win32) - flashes the owning window's taskbar icon until you
   focus it. Deliberately does **not** force the window to the foreground: Windows blocks
   `SetForegroundWindow` from background processes on purpose (foreground-lock protection), and even
   the usual workaround (minimize/restore) risks stealing a keystroke if it fires while you're typing
   elsewhere. Flashing is always permitted and doesn't touch your focus. *(This was a deliberate
   choice among several options - "force to front" and "flash+escalate-if-idle" were also considered
   and rejected as too disruptive for the default.)*
2. **Speech** (SAPI `SpVoice`) - speaks a short phrase. Serialized across concurrent sessions by a
   named mutex so two sessions notifying close together never talk over each other; if the mutex
   can't be acquired within ~2.5s, that notification's audio is skipped rather than queued (a "Claude
   needs you" spoken 10 seconds late, after you've already looked over, is noise). The mutex is
   created as `Global\ClaudeSpeakHook` where permitted, falling back to `Local\ClaudeSpeakHook` -
   `Local` is per-logon-session, which is enough unless you run sessions across RDP logons or
   services. If neither can be created the audio still plays unserialized: garbled beats silent.
3. **Toast/balloon** (`System.Windows.Forms.NotifyIcon`) - shows the phrase as text in a popup. Runs
   *last*, after speech, so its ~5s dwell-time sleep never delays the audio. Complements the flash:
   Focus Assist / Do Not Disturb can silently suppress toasts while the flash still works, and a toast
   can be seen from a different virtual desktop where a taskbar flash might go unnoticed.

A full `Notification` run therefore takes **~9 seconds** end to end (~2s of .NET/CIM startup, ~2s of
speech, 5s of toast dwell). That's expected and costs you nothing - the hook is registered `async`,
so it never blocks the session. A suppressed run exits in ~1-2s.

### Privacy: what actually gets spoken

On some Claude Code versions a Bash permission prompt's `message` can include the full command line -
which might have a token, password, or connection string typed into it. **Both recognized
notification types map to fixed wording, so no payload text is ever read aloud**, specifically so a
secret can never be spoken near other people. Only an unrecognized payload shape falls through to the
`message` itself, and that is truncated to 60 characters.

---

## Install

### 1. Place the script

Put `speak-notification.ps1` at `~/.claude/hooks/speak-notification.ps1` -
i.e. `%USERPROFILE%\.claude\hooks\speak-notification.ps1`:

```powershell
New-Item -ItemType Directory -Force "$env:USERPROFILE\.claude\hooks" | Out-Null
Copy-Item .\speak-notification.ps1 "$env:USERPROFILE\.claude\hooks\" -Force
```

Nothing in the script is machine- or project-specific, so no edits are required to run it.

### 2. Register the two hooks

`~/.claude/settings.json` needs the entries below. **The `-File` path must be absolute** - JSON does
not expand `%USERPROFILE%` or `$HOME`, so substitute your own path (note the **doubled backslashes**,
which JSON requires):

```json
{
  "hooks": {
    "Notification": [
      {
        "hooks": [
          {
            "type": "command",
            "command": "powershell.exe -NoProfile -ExecutionPolicy Bypass -File \"C:\\Users\\YOURNAME\\.claude\\hooks\\speak-notification.ps1\" -Mode Notification 2>/dev/null || true",
            "async": true
          }
        ]
      }
    ],
    "Stop": [
      {
        "hooks": [
          {
            "type": "command",
            "command": "powershell.exe -NoProfile -ExecutionPolicy Bypass -File \"C:\\Users\\YOURNAME\\.claude\\hooks\\speak-notification.ps1\" -Mode Stop 2>/dev/null || true",
            "async": true
          }
        ]
      }
    ]
  }
}
```

To avoid hand-editing (and to merge into an existing `settings.json` rather than overwrite it), run:

```powershell
$settings = Join-Path $env:USERPROFILE '.claude\settings.json'
$script   = Join-Path $env:USERPROFILE '.claude\hooks\speak-notification.ps1'
Copy-Item $settings "$settings.bak" -Force            # keep a backup first

$json = Get-Content $settings -Raw | ConvertFrom-Json
if (-not $json.hooks) { $json | Add-Member hooks (New-Object psobject) -Force }
foreach ($mode in 'Notification', 'Stop') {
    $cmd = 'powershell.exe -NoProfile -ExecutionPolicy Bypass -File "{0}" -Mode {1} 2>/dev/null || true' -f $script, $mode
    $entry = [pscustomobject]@{ hooks = @([pscustomobject]@{ type = 'command'; command = $cmd; async = $true }) }
    $json.hooks | Add-Member $mode @($entry) -Force
}
$json | ConvertTo-Json -Depth 20 | Set-Content $settings -Encoding utf8
Get-Content $settings -Raw | ConvertFrom-Json | Out-Null      # prove it still parses
```

Notes on the command line itself:
- **`async: true`** - playback never blocks the session.
- **`2>/dev/null || true`** - a broken hook never interrupts your work. This is POSIX shell syntax and
  works because Claude Code runs hook commands through a shell that understands it.
- **`powershell.exe` (5.1), not `pwsh`** - setting `"shell": "powershell"` resolves to PowerShell 7
  (`pwsh`), which many Windows machines don't have. `powershell.exe` ships with Windows; calling it
  explicitly from the default hook shell sidesteps the dependency.
- **`-NoProfile`** - keeps startup fast and immune to whatever your profile does.

### 3. Reload

Open `/hooks` once, or restart Claude Code. Config changes are not picked up mid-session.

### 4. Verify

`-Force` skips both the mute file and the focus check, so you can test the channels while looking
right at the terminal:

```powershell
$s = "$env:USERPROFILE\.claude\hooks\speak-notification.ps1"
'{"cwd":"C:\\your\\project","notification_type":"permission_prompt"}' | powershell.exe -NoProfile -ExecutionPolicy Bypass -File $s -Mode Notification -Force
'{}' | powershell.exe -NoProfile -ExecutionPolicy Bypass -File $s -Mode Stop -Force
```

You should get speech + flash + toast from the first, a chime from the second. Then run the same
commands **without** `-Force` while focused on that terminal: both should go silent and log a
`"_claudetalk":"suppressed"` line. That confirms the focus check resolves your terminal host
correctly.

Always pipe something into the script when running it by hand. It reads stdin only when stdin is
redirected, so an unpiped run is harmless - it just falls back to the generic phrase.

---

## Files

```
~/.claude/
├── settings.json                          # hooks registered here (merged, not overwritten)
└── hooks/
    ├── speak-notification.ps1             # the script (both modes)
    ├── notification-payloads.log          # real Notification payloads, for tuning phrases
    └── .mute                              # create this file to instantly silence both modes
```

`notification-payloads.log` is append-only, one JSON object per line, and self-rotates: once it passes
~256 KB the most recent 500 lines are kept. `Stop` payloads are deliberately not logged - that event
fires on every turn and would drown the useful entries.

---

## Customizing

**Mute instantly**, no settings edit or reload:
```powershell
New-Item "$env:USERPROFILE\.claude\hooks\.mute" -ItemType File   # silence
Remove-Item "$env:USERPROFILE\.claude\hooks\.mute"               # re-enable
```

**Tune without editing the script** - environment variables read at hook time:

| Variable | Effect | Default |
|---|---|---|
| `CLAUDE_SPEAK_RATE` | SAPI rate, -10 (slow) to 10 (fast); out-of-range values are clamped | `1` |
| `CLAUDE_SPEAK_VOLUME` | SAPI volume, 0-100 | `100` |
| `CLAUDE_SPEAK_VOICE` | Substring match against an installed voice name | unset (default voice) |

List installed voices (a stock Windows install usually has just David and Zira):
```powershell
(New-Object -ComObject SAPI.SpVoice).GetVoices() | ForEach-Object { $_.GetDescription() }
```

Set them wherever the hook process will inherit them - e.g. permanently for your account:
```powershell
[Environment]::SetEnvironmentVariable('CLAUDE_SPEAK_VOICE', 'Zira', 'User')
```

**Pronunciation fixes** - the spoken phrase always includes the project directory name, which SAPI
otherwise reads verbatim and often mangles. `Convert-ForSpeech` respells known trouble words right
before the `Speak()` call, speech-only (the toast and log keep the real text). Add one line per
project name that comes out wrong:

```powershell
return ($Text -replace '(?i)\bwasql\b', 'waskul' `
              -replace '(?i)\bYOURPROJECT\b', 'phonetic spelling')
```

The shipped example is `wasql` → `waskul` (the official logo guide reads "(wäh-skul)" - short flat-A,
as in "wack" - but plain `waskul` came out closest via SAPI in practice; several other spellings were
tried and rejected). Expect to tune phonetically by ear rather than by dictionary respelling.

**Tighten the phrasing** - if your Claude Code version emits a `notification_type` this script doesn't
know, it falls back to the truncated `message`. Read `notification-payloads.log` to see the exact
values your version emits and add a branch in `Get-Phrase`:

```powershell
$log = "$env:USERPROFILE\.claude\hooks\notification-payloads.log"
Get-Content $log | Where-Object { $_.Trim().StartsWith('{') } |
    ForEach-Object { try { $o = $_ | ConvertFrom-Json; '{0} :: {1}' -f $o.notification_type, $o.message } catch {} } |
    Group-Object | Sort-Object Count -Descending | Format-Table Count, Name -AutoSize
```

**Change what triggers a suppressed notification** - currently: focused window = fully suppressed,
unfocused = full treatment, no middle ground. If you want partial escalation (e.g. flash always, but
only speak/toast after a second unanswered notification), that logic isn't built.

---

## Voice input: `/voice` (built-in, no hook needed)

This is a **separate, built-in Claude Code feature** - not part of the hook setup above, and it has no
interaction with the `Notification`/`Stop` hooks. It answers "can I talk back?" the hook doesn't.

```
/voice          # toggle, keep current mode
/voice hold     # hold-to-talk (default) - hold Space, release to insert transcribed text
/voice tap      # tap Space to start recording, tap again to stop (auto-submits if >= 3 words)
/voice off      # disable
```

Default key is **Space**, held or tapped while the prompt input is focused.

**Requirements**: a Claude.ai account (not API key/Bedrock/Vertex), a working microphone with OS
permission granted. **Works on**: Windows, macOS, Linux, VS Code extension. **Does not work over**:
SSH sessions or WSL1 (native Windows or WSL2 with WSLg is fine). Audio streams to Anthropic's servers
for transcription (not processed locally), but transcription itself is not metered against `/usage`.
Each recording has a 15-second silence timeout / 2-minute hard cap.

Combined with the hook: Claude speaks/flashes/toasts when it needs you; you reply by holding Space
instead of typing. Two independent systems, one loop.

---

## Troubleshooting

Start by finding out whether the problem is the *hook wiring* or the *script*: run the `-Force`
commands from step 4. If those work, the script is fine and the fault is in `settings.json` or the
reload.

| Symptom | Cause |
|---|---|
| Nothing happens at all | Config not reloaded - open `/hooks` once or restart Claude Code. |
| Works with `-Force`, not as a hook | Same as above; also check the absolute `-File` path in `settings.json` is correct and its backslashes are doubled. A wrong path fails silently thanks to `\|\| true`. |
| Never fires even when you're away | Check `.mute` doesn't exist; then check the focus detection (next row). |
| Silent even though you're not looking | Grep `notification-payloads.log` for `_claudetalk`. A `suppressed` line means the window-owner walk resolved to something that *was* foreground - a bug in `Get-AncestorWindowHandle` for your terminal host. No line at all means the hook never ran. |
| Hangs / a `powershell.exe` lingers | An unpiped manual run on an old copy of the script. The current one reads stdin only when `[Console]::IsInputRedirected` is true, precisely because `ReadToEnd()` on an interactive console never sees EOF. |
| Takes ~9 seconds | Expected: ~2s startup + ~2s speech + 5s toast dwell. `async: true` means it never delays you. |
| `pwsh` not found | Something set `"shell": "powershell"` on the hook entry. Remove it; call `powershell.exe` directly in the command instead. |
| Speech cut off mid-phrase | Another notification took the mutex, or `Speak(...)` was made async - keep the flag `0` (synchronous), or the process exits mid-phrase. |
| Total silence, script exits 0 | SAPI, both fallback wavs, and `Console::Beep` all failed (rare), or the mutex timed out and that notification's audio was skipped. Check the log to confirm the hook actually ran. |
| Chime/toast on every turn | Speech or toast ended up wired to `Stop` instead of `Notification`. `Stop` should only ever play the plain chime. |
| Two sessions' voices garble together | Check whether `Global\` mutex creation is being denied and both fell through to unserialized playback - or that nothing bypassed the mutex (e.g. a manually-run copy of the script, or `-Force`). |
| Speech names the wrong project | The phrase uses the payload's `cwd`. If it's absent the script falls back to the hook process's working directory, which may not be your project. |

Verify JSON validity after any edit - **a malformed `settings.json` silently disables every setting in
that file**, not just the hooks:
```powershell
Get-Content "$env:USERPROFILE\.claude\settings.json" -Raw | ConvertFrom-Json
```

---

## Known scope / what's NOT verified

- **Terminal host**: the window-owner walk is confirmed correct for Windows Terminal (the
  `powershell -> claude -> cmd -> WindowsTerminal` chain above was observed live). It should work
  identically for plain `conhost`/`cmd` or the VS Code integrated terminal - both expose a
  `MainWindowHandle` somewhere in the ancestor chain - but those paths haven't been tested. Fails open
  if it can't resolve, so a new host means "notifies too often", never "silently suppresses".
- **Notification types**: `permission_prompt` and `idle_prompt` are the only values observed. Others
  fall back to the truncated `message`, which is safe but generic - see "Tighten the phrasing".
- **macOS/Linux**: not built here. A `say`/`spd-say`/`espeak` fallback covers basic speech, but the
  flash, toast, and focus-check pieces are Windows-specific (Win32/WinForms) and would need OS-native
  equivalents - `osascript`/`terminal-notifier` on macOS, `notify-send` plus an X11/Wayland active-
  window query on Linux.
- **Remote/SSH sessions**: the hook runs wherever Claude Code's hook subprocess runs. If that's a
  remote machine, sound/flash/toast happen there, not on your local machine - untested in that
  configuration.
