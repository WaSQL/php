# Claude Talk

Two-way audio/visual attention for Claude Code sessions:

- **Claude → you**: a hook speaks, flashes the taskbar, and pops a toast when Claude needs you -
  permission prompt, idle wait, or a finished turn - unless you're already looking at the window.
- **You → Claude**: built-in `/voice` dictation (hold Space to talk) - no setup needed, see below.

No plugins, no modules. One PowerShell script (`speak-notification.ps1`) and two hook entries in
`~/.claude/settings.json`.

Status: **installed and tested** on this machine (Windows, PowerShell 5.1, Windows Terminal).

---

## How it works

Claude Code fires **hooks** at lifecycle events. Two are used here:

| Event | Fires when | What this setup does |
|---|---|---|
| `Notification` | A permission prompt appears, or the session sits idle waiting on you (~60s) | **Full treatment**: taskbar flash + spoken phrase + toast popup - see "Attention channels" below. |
| `Stop` | Every time Claude finishes a turn (also on `/clear`, `/resume`, compaction) | **Chime only**. Fires constantly, so no speech/flash/toast here - just a short sound. |

Both events run the same script, `speak-notification.ps1`, with a `-Mode Notification` or
`-Mode Stop` argument picking the behavior. The `Notification` payload includes a `message` field
describing what Claude is waiting for; the script parses it into a short spoken phrase.

### The focus check (the key bulletproofing decision)

Before doing anything else, the script walks up the process tree from itself to find the console/
terminal window that owns the session (`powershell.exe -> claude -> cmd -> WindowsTerminal`, in this
setup), then compares that window handle against `GetForegroundWindow()`. **If you're already looking
at that window, the hook does nothing at all** - no chime, no flash, no toast, no speech. There's
nothing to grab your attention *for* if you're already there.

This was verified live during setup: the script correctly resolved this exact session's Windows
Terminal window through the process chain and correctly detected it as focused, suppressing the
notification.

If the window can't be resolved (unusual host, permissions issue, etc.), the check fails open - it
always notifies rather than silently going quiet. A missing signal should never look like "nothing to
report."

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
   named mutex (`Global\ClaudeSpeakHook`) so two sessions notifying close together never talk over
   each other; if the mutex can't be acquired within ~2.5s, that notification is skipped rather than
   queued (a "Claude needs you" spoken 10 seconds late, after you've already looked over, is noise).
3. **Toast/balloon** (`System.Windows.Forms.NotifyIcon`) - shows the phrase as text in a popup. Runs
   *last*, after speech, so its ~5s dwell-time sleep never delays the audio. Complements the flash:
   Focus Assist / Do Not Disturb can silently suppress toasts while the flash still works, and a toast
   can be seen from a different virtual desktop where a taskbar flash might go unnoticed.

### Privacy: what actually gets spoken

A Bash permission prompt's `message` can include the full command line - which might have a token,
password, or connection string typed into it. **The script only speaks the tool name for permission
prompts, never the command text**, specifically so a secret never gets read aloud near other people.
Other message types are capped at 60 characters before being spoken.

---

## Files

```
~/.claude/
├── settings.json                          # hooks registered here (merged, not overwritten)
└── hooks/
    ├── speak-notification.ps1             # the script (both modes)
    ├── notification-payloads.log          # real Notification payloads, for tuning phrases
    └── .mute                               # create this file to instantly silence both modes
```

`~/.claude/settings.json` hook entries:

```json
{
  "hooks": {
    "Notification": [
      {
        "hooks": [
          {
            "type": "command",
            "command": "powershell.exe -NoProfile -ExecutionPolicy Bypass -File \"C:\\Users\\slloy\\.claude\\hooks\\speak-notification.ps1\" -Mode Notification 2>/dev/null || true",
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
            "command": "powershell.exe -NoProfile -ExecutionPolicy Bypass -File \"C:\\Users\\slloy\\.claude\\hooks\\speak-notification.ps1\" -Mode Stop 2>/dev/null || true",
            "async": true
          }
        ]
      }
    ]
  }
}
```

Notes on the command line itself:
- **`async: true`** - playback never blocks the session.
- **Backslashes doubled** in the JSON (`C:\\Users\\...`).
- **`2>/dev/null || true`** - a broken hook never interrupts your work.
- **`powershell.exe` (5.1), not `pwsh`** - setting `"shell": "powershell"` resolves to PowerShell 7
  (`pwsh`), which many Windows machines don't have. `powershell.exe` ships with Windows; calling it
  explicitly from the default hook shell sidesteps the dependency.

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
| `CLAUDE_SPEAK_RATE` | SAPI rate, -10 (slow) to 10 (fast) | `1` |
| `CLAUDE_SPEAK_VOLUME` | SAPI volume, 0-100 | `100` |
| `CLAUDE_SPEAK_VOICE` | Substring match against an installed voice name | unset (default voice) |

List installed voices:
```powershell
(New-Object -ComObject SAPI.SpVoice).GetVoices() | ForEach-Object { $_.GetDescription() }
```

**Pronunciation fixes** - `$phrase` always includes `$project` (the current directory name), spoken
verbatim by SAPI otherwise. `Convert-ForSpeech` respells known trouble words right before the
`Speak()` call, speech-only (the toast/log keep the real text) - currently just `wasql` &rarr;
`waskul` (the official logo guide reads "(wäh-skul)" - short flat-A, as in "wack" - but plain
`waskul` came out closest via SAPI in practice; several other spellings were tried and rejected, see
git history). Add more `-replace '(?i)\bWORD\b', 'phonetic'` lines there for any other project name
SAPI mispronounces.

**Tighten the phrasing** - the `switch -Regex` block in the script matches on educated guesses at
Claude Code's actual `message` wording. `notification-payloads.log` beside the script records every
real payload it receives; after a day of use, read that log to see the exact strings your version
emits and tighten the regex.

**Change what triggers a suppressed notification** - currently: focused window = fully suppressed,
unfocused = full treatment, no middle ground. If you want partial escalation (e.g. flash always, but
only speak/toast after a second unanswered notification), that logic isn't built - flag it if wanted.

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

| Symptom | Cause |
|---|---|
| Nothing happens at all | Config not reloaded - open `/hooks` once or restart Claude Code. |
| Works when piped manually, not as a hook | Same as above; the settings watcher only watches directories that had a settings file when the session started. |
| Never fires even when you're away | Check `.mute` doesn't exist; check the focus-detection isn't misfiring (see below). |
| Speech/flash/toast never happens even though you're not looking | The window-owner resolution failed for your terminal host (only Windows Terminal is verified) - it should fail open (always notify), not silently suppress. If it's suppressing when it shouldn't, that's a bug in `Get-AncestorWindowHandle` for your specific terminal - flag it. |
| `pwsh` not found | Something set `"shell": "powershell"` on the hook entry. Remove it; call `powershell.exe` directly in the command instead (already done above). |
| Speech cut off mid-phrase | Another notification fired and took the mutex, or `Speak(...)` was made async - keep flag `0` (synchronous). |
| Total silence, script exits 0 | SAPI and the fallback wav both failed (rare - minimal Windows install), or the mutex timed out and this notification was skipped. Check `notification-payloads.log` to confirm the hook actually ran. |
| Chime/toast on every keystroke-ish action | Speech or toast ended up wired to `Stop` instead of `Notification`. `Stop` should only ever play the plain chime. |
| Two sessions' voices garble together | Should not happen - both modes share the `Global\ClaudeSpeakHook` mutex. If it does, check nothing bypassed the mutex (e.g. a manually-run copy of the script). |

Verify JSON validity after any edit - **a malformed `settings.json` silently disables every setting in
that file**, not just the hooks:
```powershell
Get-Content "$env:USERPROFILE\.claude\settings.json" -Raw | ConvertFrom-Json
```

---

## Known scope / what's NOT verified

- **Terminal host**: the window-owner walk is confirmed correct for Windows Terminal. It should work
  identically for plain `conhost`/`cmd` or the VS Code integrated terminal (both expose a
  `MainWindowHandle` at some point in the ancestor chain), but that specific path hasn't been tested.
  Fails open if it can't resolve - never silently suppresses without cause.
- **macOS/Linux**: not built here. The original design's `say`/`spd-say`/`espeak` fallback shape still
  applies for basic speech, but the flash/toast/focus-check pieces are Windows-specific (Win32/WinForms)
  and would need OS-native equivalents (e.g. `osascript` for macOS notifications, `notify-send` on
  Linux) if ported.
- **Remote/SSH sessions**: the hook runs wherever Claude Code's hook subprocess runs. If that's a
  remote machine, sound/flash/toast happen there, not on your local machine - untested in that
  configuration.
