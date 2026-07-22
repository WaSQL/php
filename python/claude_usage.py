#!/usr/bin/env python3
"""
claude_usage.py -- Standalone Claude Code usage dashboard.

Parses your Claude Code transcripts (~/.claude/projects/**/*.jsonl), aggregates
token usage and activity, writes a self-contained HTML dashboard (Chart.js), and
opens it in your default browser.

Portable: works for ANY user on Windows / macOS / Linux. No Claude, no Node, no
pip installs -- just Python 3.8+. Re-run it any time to refresh the dashboard.

    python claude_usage.py                 # parse, write, and open
    python claude_usage.py --no-open       # just write the HTML
    python claude_usage.py --days 7        # only the last 7 days
    python claude_usage.py --dir /path/to/.claude/projects
    python claude_usage.py --out report.html

Note on cost: this tool reports TOKENS and ACTIVITY only, never dollars. On a
subscription / corporate plan you pay a flat fee against a usage cap -- you are
not billed per token -- so a per-token dollar figure would be meaningless.
"""
import argparse, json, os, sys, glob, datetime, collections, webbrowser


# --------------------------------------------------------------------------- #
#  Locate the transcript directory (portable across users / OSes)
# --------------------------------------------------------------------------- #
def find_root(explicit=None):
    if explicit:
        return explicit if os.path.isdir(explicit) else None
    candidates = []
    env = os.environ.get("CLAUDE_CONFIG_DIR")
    if env:
        candidates.append(os.path.join(env, "projects"))
    home = os.path.expanduser("~")
    candidates.append(os.path.join(home, ".claude", "projects"))
    candidates.append(os.path.join(home, ".config", "claude", "projects"))
    for c in candidates:
        if os.path.isdir(c):
            return c
    return None


# --------------------------------------------------------------------------- #
#  Parse
# --------------------------------------------------------------------------- #
def new_counter():
    return collections.Counter()


def parse(root, since=None):
    tot = new_counter()
    by_day = collections.defaultdict(new_counter)
    by_model = collections.defaultdict(new_counter)
    by_project = collections.defaultdict(new_counter)
    by_hour = collections.Counter()
    by_dow = collections.Counter()
    by_effort = collections.Counter()
    tool_counts = collections.Counter()
    sessions = {}
    user_msgs = 0
    assistant_msgs = 0

    files = glob.glob(os.path.join(root, "**", "*.jsonl"), recursive=True)
    for fp in files:
        # first path segment under root is the project folder, even for
        # nested subagent transcripts (root/<project>/<sessionId>/subagents/*.jsonl)
        folder = os.path.relpath(fp, root).split(os.sep)[0]
        try:
            fh = open(fp, "r", encoding="utf-8", errors="replace")
        except OSError:
            continue
        with fh:
            for line in fh:
                line = line.strip()
                if not line:
                    continue
                try:
                    e = json.loads(line)
                except json.JSONDecodeError:
                    continue
                typ = e.get("type")
                ts = e.get("timestamp")

                # optional date filter
                if since and ts and ts[:10] < since:
                    continue

                if typ == "user":
                    user_msgs += 1
                if typ != "assistant":
                    continue

                msg = e.get("message") or {}
                model = msg.get("model", "unknown")
                if model in ("<synthetic>", "unknown"):
                    continue

                u = msg.get("usage", {}) or {}
                inp = u.get("input_tokens", 0)
                out = u.get("output_tokens", 0)
                cr = u.get("cache_read_input_tokens", 0)
                cc = u.get("cache_creation_input_tokens", 0)

                assistant_msgs += 1

                def add(c):
                    c["in"] += inp; c["out"] += out
                    c["cache_read"] += cr; c["cache_create"] += cc
                    c["msgs"] += 1

                add(tot)
                add(by_model[model])
                add(by_project[folder])

                day = "?"
                if ts:
                    try:
                        dt = datetime.datetime.fromisoformat(
                            ts.replace("Z", "+00:00")).astimezone()
                        day = dt.strftime("%Y-%m-%d")
                        by_hour[dt.hour] += 1
                        by_dow[dt.weekday()] += 1
                    except ValueError:
                        pass
                add(by_day[day])

                eff = e.get("effort")
                if eff:
                    by_effort[eff] += 1

                for block in msg.get("content", []) or []:
                    if isinstance(block, dict) and block.get("type") == "tool_use":
                        tool_counts[block.get("name", "?")] += 1

                sid = e.get("sessionId") or e.get("session_id")
                if sid:
                    s = sessions.get(sid)
                    if not s:
                        s = sessions[sid] = {
                            "sid": sid, "proj": folder, "start": ts, "end": ts,
                            "msgs": 0, "tokens": 0, "model": model}
                    s["msgs"] += 1
                    s["tokens"] += inp + out + cr + cc
                    if ts:
                        if not s["start"] or ts < s["start"]:
                            s["start"] = ts
                        if not s["end"] or ts > s["end"]:
                            s["end"] = ts

    return dict(tot=tot, by_day=by_day, by_model=by_model, by_project=by_project,
                by_hour=by_hour, by_dow=by_dow, by_effort=by_effort,
                tools=tool_counts, sessions=sessions,
                user_msgs=user_msgs, assistant_msgs=assistant_msgs,
                files=len(files))


# --------------------------------------------------------------------------- #
#  Shape into the JSON the dashboard consumes  +  derive saving tips
# --------------------------------------------------------------------------- #
def C(c):
    return {"in": c["in"], "out": c["out"], "cache_read": c["cache_read"],
            "cache_create": c["cache_create"], "msgs": c["msgs"]}


def human(n):
    for unit in ("", "K", "M", "B", "T"):
        if abs(n) < 1000:
            return (f"{n:.1f}{unit}".rstrip("0").rstrip(".")
                    if unit else f"{n:.0f}")
        n /= 1000.0
    return f"{n:.1f}P"


def session_duration(s):
    try:
        a = datetime.datetime.fromisoformat(s["start"].replace("Z", "+00:00"))
        b = datetime.datetime.fromisoformat(s["end"].replace("Z", "+00:00"))
        return max(0, int((b - a).total_seconds()))
    except (ValueError, AttributeError, TypeError):
        return 0


def build_tips(agg):
    """Data-driven token-saving insights, most impactful first."""
    tot = agg["tot"]
    turns = max(1, agg["assistant_msgs"])
    nsess = max(1, len(agg["sessions"]))
    cr, cc, inp = tot["cache_read"], tot["cache_create"], tot["in"]
    total_in = cr + cc + inp
    reuse = (cr / total_in * 100) if total_in else 0
    ctx_per_turn = total_in / turns
    turns_per_sess = turns / nsess

    # model split
    opus = sum(v["msgs"] for k, v in agg["by_model"].items() if "opus" in k.lower())
    opus_pct = opus / turns * 100

    # heaviest session
    heavy = max(agg["sessions"].values(), key=lambda s: s["tokens"],
                default=None) if agg["sessions"] else None

    tips = []

    tips.append({
        "kind": "insight",
        "title": "Cached context is your #1 token driver",
        "body": (f"Every turn re-sends the whole conversation as cached input. "
                 f"You've read <b>{human(cr)}</b> cached tokens — about "
                 f"<b>{human(ctx_per_turn)} tokens re-read per turn</b>. "
                 f"The single biggest lever is keeping that context small.")
    })

    if turns_per_sess > 40:
        tips.append({
            "kind": "tip",
            "title": "Your sessions run long — use /clear between tasks",
            "body": (f"You average <b>{turns_per_sess:.0f} turns per session</b>. "
                     f"A long session keeps paying to re-read everything that came "
                     f"before, even after you've moved on. Run <code>/clear</code> "
                     f"when switching to an unrelated task, or <code>/compact</code> "
                     f"to summarise the history instead of carrying it raw. "
                     f"This is usually the biggest real-world saving.")
        })
    else:
        tips.append({
            "kind": "tip",
            "title": "Reset context between unrelated tasks",
            "body": ("Run <code>/clear</code> when you switch to something unrelated, "
                     "or <code>/compact</code> to summarise a long thread. Both cut "
                     "the context that gets re-read on every following turn.")
        })

    if reuse < 60:
        tips.append({
            "kind": "tip",
            "title": f"Cache reuse is {reuse:.0f}% — work in bursts",
            "body": ("The prompt cache expires after a short idle window. Long gaps "
                     "between messages force the context to be re-created (expensive) "
                     "instead of re-read (cheap). Keep iterating in focused bursts to "
                     "ride the warm cache.")
        })
    else:
        tips.append({
            "kind": "insight",
            "title": f"Cache reuse is healthy ({reuse:.0f}%)",
            "body": ("Most of your input is served from cache rather than re-created — "
                     "that's the cheap path. Keeping sessions active keeps it that way.")
        })

    if opus_pct > 70:
        tips.append({
            "kind": "tip",
            "title": f"Opus handles {opus_pct:.0f}% of your turns",
            "body": ("Opus is the heavyweight. For routine edits, file wrangling, and "
                     "simple Q&A, switch to Sonnet or Haiku with <code>/model</code> — "
                     "far fewer tokens per turn — and save Opus for genuinely hard "
                     "reasoning.")
        })

    tips.append({
        "kind": "tip",
        "title": "Point Claude at the right files up front",
        "body": ("Naming the file, function, or path in your prompt saves whole "
                 "tool-loop rounds of searching. Fewer turns = less cumulative "
                 "context re-read on every later turn.")
    })

    if heavy:
        tips.append({
            "kind": "insight",
            "title": "Your heaviest session",
            "body": (f"One session in <b>{heavy['proj']}</b> ran "
                     f"<b>{heavy['msgs']} turns</b> and moved <b>{human(heavy['tokens'])} "
                     f"tokens</b>. Sessions like this are prime candidates for an "
                     f"earlier <code>/compact</code>.")
        })

    tips.append({
        "kind": "tip",
        "title": "Prefer targeted reads over whole files",
        "body": ("Re-reading large files repeatedly bloats the cached context. Ask for "
                 "specific functions or line ranges, and avoid re-dumping files Claude "
                 "already has in the conversation.")
    })

    return tips


def shape(agg):
    days_sorted = sorted(d for d in agg["by_day"] if d != "?")
    sess_list = []
    for s in agg["sessions"].values():
        sess_list.append({
            "sid": s["sid"][:8], "proj": s["proj"], "start": s["start"],
            "dur_s": session_duration(s), "msgs": s["msgs"],
            "tokens": s["tokens"], "model": s["model"]})
    return {
        "generated": datetime.datetime.now().strftime("%Y-%m-%d %H:%M"),
        "range": [days_sorted[0], days_sorted[-1]] if days_sorted else ["?", "?"],
        "totals": C(agg["tot"]),
        "user_msgs": agg["user_msgs"],
        "assistant_msgs": agg["assistant_msgs"],
        "sessions_count": len(agg["sessions"]),
        "active_days": len(days_sorted),
        "files": agg["files"],
        "by_day": {d: C(agg["by_day"][d]) for d in days_sorted},
        "by_model": {m: C(agg["by_model"][m]) for m in agg["by_model"]},
        "by_project": {p: C(agg["by_project"][p]) for p in agg["by_project"]},
        "by_hour": {str(h): agg["by_hour"].get(h, 0) for h in range(24)},
        "by_dow": {str(i): agg["by_dow"].get(i, 0) for i in range(7)},
        "by_effort": dict(agg["by_effort"]),
        "tools": dict(agg["tools"].most_common(25)),
        "top_sessions": sorted(sess_list, key=lambda x: x["tokens"],
                               reverse=True)[:15],
        "tips": build_tips(agg),
    }


# --------------------------------------------------------------------------- #
#  HTML template (self-contained; data + tips injected)
# --------------------------------------------------------------------------- #
PAGE = r"""<!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Claude Usage Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
  :root{
    --page:#0d0d0d; --surface:#1a1a19; --ink:#fff; --ink2:#c3c2b7; --muted:#898781;
    --grid:#2c2c2a; --axis:#383835; --border:rgba(255,255,255,.10);
    --s1:#3987e5; --s2:#d95926; --s3:#199e70; --s4:#c98500; --s5:#d55181;
    --s6:#008300; --s7:#9085e9; --s8:#e66767; --good:#0ca30c;
    --tipbg:rgba(57,135,229,.08);
  }
  html[data-theme="light"]{
    --page:#f9f9f7; --surface:#fcfcfb; --ink:#0b0b0b; --ink2:#52514e; --muted:#898781;
    --grid:#e1e0d9; --axis:#c3c2b7; --border:rgba(11,11,11,.10);
    --s1:#2a78d6; --s2:#eb6834; --s3:#1baf7a; --s4:#eda100; --s5:#e87ba4;
    --s6:#008300; --s7:#4a3aa7; --s8:#e34948; --tipbg:rgba(42,120,214,.06);
  }
  *{box-sizing:border-box}
  body{margin:0;background:var(--page);color:var(--ink);
    font-family:system-ui,-apple-system,"Segoe UI",sans-serif;-webkit-font-smoothing:antialiased}
  .wrap{max-width:1240px;margin:0 auto;padding:28px 22px 60px}
  header{display:flex;align-items:baseline;justify-content:space-between;flex-wrap:wrap;gap:12px}
  h1{font-size:24px;font-weight:650;margin:0;letter-spacing:-.01em}
  .sub{color:var(--muted);font-size:13px;margin-top:4px}
  .toggle{background:var(--surface);color:var(--ink2);border:1px solid var(--border);
    border-radius:8px;padding:7px 13px;font-size:13px;cursor:pointer}
  .toggle:hover{color:var(--ink)}
  .tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(175px,1fr));gap:14px;margin:22px 0}
  .tile{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:16px 18px}
  .tile .lab{color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.05em}
  .tile .val{font-size:28px;font-weight:650;margin-top:6px;letter-spacing:-.01em}
  .tile .note{color:var(--ink2);font-size:12.5px;margin-top:4px}
  .accent{color:var(--s1)}
  .grid{display:grid;grid-template-columns:repeat(12,1fr);gap:16px;margin-top:16px}
  .card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:16px 18px 10px}
  .card h2{font-size:14px;font-weight:600;margin:0 0 2px}
  .card .cs{color:var(--muted);font-size:12px;margin-bottom:10px}
  .col-12{grid-column:span 12}.col-8{grid-column:span 8}.col-7{grid-column:span 7}
  .col-6{grid-column:span 6}.col-5{grid-column:span 5}.col-4{grid-column:span 4}
  .chartbox{position:relative;width:100%}
  table{width:100%;border-collapse:collapse;font-size:12.5px}
  th,td{text-align:left;padding:7px 8px;border-bottom:1px solid var(--border);font-variant-numeric:tabular-nums}
  th{color:var(--muted);font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.04em}
  td.num,th.num{text-align:right}
  .tips{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  .tip{background:var(--tipbg);border:1px solid var(--border);border-left:3px solid var(--s1);
    border-radius:10px;padding:12px 14px}
  .tip.insight{border-left-color:var(--s3)}
  .tip .tt{font-size:13.5px;font-weight:650;margin:0 0 4px}
  .tip .tb{font-size:13px;color:var(--ink2);line-height:1.5;margin:0}
  .tip code{background:var(--surface);border:1px solid var(--border);border-radius:5px;
    padding:1px 5px;font-size:12px}
  .tip .badge{font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);
    float:right;font-weight:600}
  .foot{color:var(--muted);font-size:11.5px;margin-top:26px;line-height:1.6}
  @media(max-width:900px){.col-8,.col-7,.col-6,.col-5,.col-4{grid-column:span 12}.tips{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="wrap">
  <header>
    <div>
      <h1 id="title">Claude Code Usage</h1>
      <div class="sub" id="subtitle"></div>
    </div>
    <button class="toggle" onclick="toggleTheme()">&#9686; Theme</button>
  </header>

  <div class="tiles" id="tiles"></div>

  <div class="grid">
    <div class="card col-8">
      <h2>Turns per day</h2>
      <div class="cs">Assistant responses over time</div>
      <div class="chartbox" style="height:250px"><canvas id="turnsDay"></canvas></div>
    </div>
    <div class="card col-4">
      <h2>Tokens by model</h2>
      <div class="cs">Share of total token volume</div>
      <div class="chartbox" style="height:250px"><canvas id="byModel"></canvas></div>
    </div>

    <div class="card col-12">
      <h2>Tokens per day</h2>
      <div class="cs">Stacked by type &mdash; cache reads (re-read context) are the bulk</div>
      <div class="chartbox" style="height:300px"><canvas id="tokDay"></canvas></div>
    </div>

    <div class="card col-12">
      <h2>How to use fewer tokens</h2>
      <div class="cs">Tips from best practice + insights derived from your own usage</div>
      <div class="tips" id="tips"></div>
    </div>

    <div class="card col-6">
      <h2>Tokens by project</h2>
      <div class="cs">Working directory</div>
      <div class="chartbox" style="height:300px"><canvas id="byProj"></canvas></div>
    </div>
    <div class="card col-6">
      <h2>Most-used tools</h2>
      <div class="cs">Tool calls across all sessions</div>
      <div class="chartbox" style="height:300px"><canvas id="tools"></canvas></div>
    </div>

    <div class="card col-7">
      <h2>Activity by hour of day</h2>
      <div class="cs">When you work (local time)</div>
      <div class="chartbox" style="height:220px"><canvas id="byHour"></canvas></div>
    </div>
    <div class="card col-5">
      <h2>Activity by weekday</h2>
      <div class="cs">Assistant turns</div>
      <div class="chartbox" style="height:220px"><canvas id="byDow"></canvas></div>
    </div>

    <div class="card col-12">
      <h2>Heaviest sessions</h2>
      <div class="cs">Top 15 by token volume &mdash; the best candidates for an earlier /compact</div>
      <table id="sessTable"><thead><tr>
        <th>Session</th><th>Project</th><th>Started</th>
        <th class="num">Duration</th><th class="num">Turns</th><th class="num">Tokens</th>
      </tr></thead><tbody></tbody></table>
    </div>
  </div>

  <div class="foot" id="foot"></div>
</div>

<script>
const DATA = __DATA__;

const cssv = n => getComputedStyle(document.documentElement).getPropertyValue(n).trim();
const compact = n => Intl.NumberFormat('en',{notation:'compact',maximumFractionDigits:1}).format(n);
const durfmt = s => { s=Math.round(s); const h=Math.floor(s/3600),m=Math.floor(s%3600/60);
  return h? h+'h '+m+'m' : m+'m'; };
const DOW = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
const projShort = p => (p.replace(/^C--Users-[^-]+-?/,'~/').replace(/-/g,'/')) || p;
let charts=[];
function ink(){return {ink:cssv('--ink'),ink2:cssv('--ink2'),muted:cssv('--muted'),
  grid:cssv('--grid'),axis:cssv('--axis'),surface:cssv('--surface')};}

function renderHeader(){
  const projs=Object.keys(DATA.by_project);
  if(projs.length===1){
    const name=projShort(projs[0]);
    document.title='Claude Usage — '+name;
    document.getElementById('title').textContent='Claude Code Usage — '+name;
  } else {
    document.getElementById('title').textContent='Claude Code Usage — '+projs.length+' projects';
  }
}

function renderTiles(){
  const t=DATA.totals, days=DATA.active_days||1;
  const totTok=t.in+t.out+t.cache_read+t.cache_create;
  const reuse=(t.cache_read/(t.cache_read+t.in+t.cache_create)*100)||0;
  const tiles=[
    {lab:'Total tokens',val:compact(totTok),note:compact(totTok/days)+' per active day',accent:1},
    {lab:'Generated by Claude',val:compact(t.out),note:'output tokens'},
    {lab:'Assistant turns',val:DATA.assistant_msgs.toLocaleString(),note:DATA.user_msgs.toLocaleString()+' of your messages'},
    {lab:'Sessions',val:DATA.sessions_count.toLocaleString(),note:(DATA.assistant_msgs/DATA.sessions_count).toFixed(0)+' turns each avg'},
    {lab:'Active days',val:days.toString(),note:DATA.range[0]+' → '+DATA.range[1]},
    {lab:'Cache reuse',val:reuse.toFixed(0)+'%',note:'of input served from cache'},
  ];
  document.getElementById('tiles').innerHTML=tiles.map(x=>
    `<div class="tile"><div class="lab">${x.lab}</div>
     <div class="val ${x.accent?'accent':''}">${x.val}</div>
     <div class="note">${x.note}</div></div>`).join('');
  document.getElementById('subtitle').textContent=
    `${DATA.assistant_msgs.toLocaleString()} turns · ${DATA.sessions_count} sessions · ${DATA.range[0]} to ${DATA.range[1]}`;
  document.getElementById('foot').innerHTML=
    `Parsed ${DATA.files} transcript files from your ~/.claude/projects. `+
    `This dashboard reports token volume and activity only &mdash; no dollar figures, `+
    `because a subscription / corporate plan bills a flat fee against a usage cap, not per token. `+
    `Generated ${DATA.generated} by claude_usage.py.`;
}

function renderTips(){
  document.getElementById('tips').innerHTML=DATA.tips.map(t=>
    `<div class="tip ${t.kind}"><p class="tt">${t.title}<span class="badge">${t.kind}</span></p>
     <p class="tb">${t.body}</p></div>`).join('');
}

function baseOpts(extra){
  const c=ink();
  return Object.assign({responsive:true,maintainAspectRatio:false,
    plugins:{legend:{display:false},
      tooltip:{backgroundColor:c.surface,titleColor:c.ink,bodyColor:c.ink2,
        borderColor:cssv('--border'),borderWidth:1,padding:10,cornerRadius:8,titleFont:{weight:'600'}}},
    scales:{x:{grid:{display:false},ticks:{color:c.muted,font:{size:11}},border:{color:c.axis}},
      y:{grid:{color:c.grid},ticks:{color:c.muted,font:{size:11}},border:{display:false}}}},extra||{});
}

function buildAll(){
  charts.forEach(c=>c.destroy()); charts=[];
  const c=ink();
  const days=Object.keys(DATA.by_day);
  const S=['--s1','--s2','--s3','--s4','--s5','--s6','--s7','--s8'].map(cssv);

  charts.push(new Chart(turnsDay,{type:'line',data:{labels:days,datasets:[{
      data:days.map(d=>DATA.by_day[d].msgs),borderColor:S[0],backgroundColor:S[0]+'22',
      fill:true,tension:.25,borderWidth:2,pointRadius:0,pointHoverRadius:5,pointHoverBackgroundColor:S[0]}]},
    options:baseOpts({plugins:{legend:{display:false},tooltip:{callbacks:{label:x=>x.parsed.y+' turns'}}},
      scales:{x:{grid:{display:false},ticks:{color:c.muted,maxTicksLimit:12,font:{size:10}},border:{color:c.axis}},
        y:{grid:{color:c.grid},ticks:{color:c.muted,font:{size:11}},border:{display:false}}}})}));

  const models=Object.keys(DATA.by_model);
  const mtok=m=>{const x=DATA.by_model[m];return x.in+x.out+x.cache_read+x.cache_create;};
  charts.push(new Chart(byModel,{type:'doughnut',data:{labels:models,datasets:[{
      data:models.map(mtok),backgroundColor:models.map((m,i)=>S[i%8]),borderColor:c.surface,borderWidth:2}]},
    options:{responsive:true,maintainAspectRatio:false,cutout:'62%',
      plugins:{legend:{position:'bottom',labels:{color:c.ink2,font:{size:11},boxWidth:12,padding:10}},
        tooltip:{backgroundColor:c.surface,titleColor:c.ink,bodyColor:c.ink2,borderColor:cssv('--border'),
          borderWidth:1,padding:10,cornerRadius:8,callbacks:{label:x=>x.label+' '+compact(x.parsed)}}}}}));

  const stack=[['Input','in',S[0]],['Output','out',S[1]],['Cache read','cache_read',S[2]],['Cache write','cache_create',S[3]]];
  charts.push(new Chart(tokDay,{type:'bar',data:{labels:days,datasets:stack.map(([lab,k,col])=>({
      label:lab,data:days.map(d=>DATA.by_day[d][k]),backgroundColor:col,borderColor:c.surface,borderWidth:1,borderRadius:2,stack:'t'}))},
    options:baseOpts({plugins:{legend:{display:true,position:'top',align:'end',
        labels:{color:c.ink2,font:{size:11},boxWidth:12,padding:12}},
      tooltip:{callbacks:{label:x=>x.dataset.label+' '+compact(x.parsed.y)}}},
      scales:{x:{stacked:true,grid:{display:false},ticks:{color:c.muted,maxTicksLimit:15,font:{size:10}},border:{color:c.axis}},
        y:{stacked:true,grid:{color:c.grid},ticks:{color:c.muted,callback:compact,font:{size:11}},border:{display:false}}}})}));

  const projs=Object.entries(DATA.by_project).map(([k,v])=>[k,v.in+v.out+v.cache_read+v.cache_create])
    .sort((a,b)=>b[1]-a[1]).slice(0,10);
  charts.push(new Chart(byProj,{type:'bar',data:{labels:projs.map(p=>projShort(p[0])),datasets:[{
      data:projs.map(p=>p[1]),backgroundColor:S[0],borderRadius:4}]},
    options:baseOpts({indexAxis:'y',plugins:{legend:{display:false},tooltip:{callbacks:{label:x=>compact(x.parsed.x)+' tokens'}}},
      scales:{x:{grid:{color:c.grid},ticks:{color:c.muted,callback:compact,font:{size:11}},border:{display:false}},
        y:{grid:{display:false},ticks:{color:c.ink2,font:{size:11}},border:{color:c.axis}}}})}));

  const tls=Object.entries(DATA.tools).slice(0,14);
  charts.push(new Chart(window.tools,{type:'bar',data:{labels:tls.map(t=>t[0]),datasets:[{
      data:tls.map(t=>t[1]),backgroundColor:S[0],borderRadius:4}]},
    options:baseOpts({indexAxis:'y',plugins:{legend:{display:false},tooltip:{callbacks:{label:x=>x.parsed.x.toLocaleString()+' calls'}}},
      scales:{x:{grid:{color:c.grid},ticks:{color:c.muted,font:{size:11}},border:{display:false}},
        y:{grid:{display:false},ticks:{color:c.ink2,font:{size:11}},border:{color:c.axis}}}})}));

  const hours=[...Array(24).keys()];
  charts.push(new Chart(byHour,{type:'bar',data:{labels:hours.map(h=>h+':00'),datasets:[{
      data:hours.map(h=>DATA.by_hour[h]||0),backgroundColor:S[0],borderRadius:3}]},
    options:baseOpts({plugins:{legend:{display:false},tooltip:{callbacks:{label:x=>x.parsed.y+' turns'}}},
      scales:{x:{grid:{display:false},ticks:{color:c.muted,maxTicksLimit:12,font:{size:10}},border:{color:c.axis}},
        y:{grid:{color:c.grid},ticks:{color:c.muted,font:{size:11}},border:{display:false}}}})}));

  charts.push(new Chart(byDow,{type:'bar',data:{labels:DOW,datasets:[{
      data:[0,1,2,3,4,5,6].map(i=>DATA.by_dow[i]||0),backgroundColor:S[0],borderRadius:3}]},
    options:baseOpts({plugins:{legend:{display:false},tooltip:{callbacks:{label:x=>x.parsed.y+' turns'}}}})}));
}

function renderTable(){
  document.querySelector('#sessTable tbody').innerHTML=DATA.top_sessions.map(s=>{
    const d=s.start?new Date(s.start):null;
    const when=d?d.toLocaleString('en-US',{month:'short',day:'numeric',hour:'numeric',minute:'2-digit'}):'?';
    return `<tr><td>${s.sid}</td><td>${projShort(s.proj)}</td><td>${when}</td>
      <td class="num">${durfmt(s.dur_s)}</td><td class="num">${s.msgs}</td>
      <td class="num">${compact(s.tokens)}</td></tr>`;}).join('');
}

function toggleTheme(){
  const h=document.documentElement;
  h.setAttribute('data-theme',h.getAttribute('data-theme')==='dark'?'light':'dark');
  buildAll();
}

renderHeader(); renderTiles(); renderTips(); renderTable(); buildAll();
</script>
</body>
</html>"""


# --------------------------------------------------------------------------- #
#  Open in Chrome (falls back to default browser if Chrome isn't found)
# --------------------------------------------------------------------------- #
def find_chrome():
    if sys.platform == "win32":
        candidates = [
            os.path.join(os.environ.get("PROGRAMFILES", r"C:\Program Files"),
                         "Google", "Chrome", "Application", "chrome.exe"),
            os.path.join(os.environ.get("PROGRAMFILES(X86)", r"C:\Program Files (x86)"),
                         "Google", "Chrome", "Application", "chrome.exe"),
            os.path.join(os.environ.get("LOCALAPPDATA", ""),
                         "Google", "Chrome", "Application", "chrome.exe"),
        ]
    elif sys.platform == "darwin":
        candidates = ["/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"]
    else:
        candidates = ["/usr/bin/google-chrome", "/usr/bin/google-chrome-stable",
                      "/usr/bin/chromium", "/usr/bin/chromium-browser"]
    for c in candidates:
        if c and os.path.isfile(c):
            return c
    return None


def open_in_chrome(path):
    url = "file://" + os.path.abspath(path).replace("\\", "/")
    chrome = find_chrome()
    if chrome:
        try:
            import subprocess
            subprocess.Popen([chrome, url])
            print("Opened in Chrome.")
            return
        except OSError as e:
            print(f"Could not launch Chrome ({e}) -- falling back to your default browser.")
    else:
        print("Chrome not found -- opening your default browser instead.")
    webbrowser.open(url)


# --------------------------------------------------------------------------- #
#  Main
# --------------------------------------------------------------------------- #
def main():
    ap = argparse.ArgumentParser(description="Claude Code usage dashboard.")
    ap.add_argument("--dir", help="path to .claude/projects (auto-detected if omitted)")
    ap.add_argument("--out", help="output HTML path (default: next to this script)")
    ap.add_argument("--days", type=int, help="only include the last N days")
    ap.add_argument("--no-open", action="store_true", help="write the file but don't open a browser")
    args = ap.parse_args()

    root = find_root(args.dir)
    if not root:
        sys.exit("Could not find your Claude Code transcripts. Looked for "
                 "~/.claude/projects (and $CLAUDE_CONFIG_DIR). Pass --dir explicitly.")

    since = None
    if args.days:
        since = (datetime.datetime.now() - datetime.timedelta(days=args.days)).strftime("%Y-%m-%d")

    print(f"Reading transcripts from: {root}")
    agg = parse(root, since=since)
    if agg["assistant_msgs"] == 0:
        sys.exit("No assistant messages found in that directory.")

    data = shape(agg)
    out = args.out or os.path.join(os.path.dirname(os.path.abspath(__file__)),
                                   "claude_usage.html")
    with open(out, "w", encoding="utf-8") as f:
        f.write(PAGE.replace("__DATA__", json.dumps(data)))

    t = data["totals"]
    total = t["in"] + t["out"] + t["cache_read"] + t["cache_create"]
    print(f"  {agg['assistant_msgs']:,} assistant turns across "
          f"{data['sessions_count']} sessions, {data['active_days']} active days")
    print(f"  {human(total)} total tokens ({human(t['out'])} generated)")
    print(f"Wrote {out}")

    if not args.no_open:
        open_in_chrome(out)


if __name__ == "__main__":
    main()
