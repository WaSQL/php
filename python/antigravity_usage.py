#!/usr/bin/env python3
"""
antigravity_usage.py -- Standalone Antigravity & Gemini usage dashboard.

Parses your Antigravity and Gemini CLI transcripts and databases (~/.gemini/**),
aggregates token usage (including input, output, prompt cache, and reasoning thoughts),
activity metrics, tool calls, projects, and daily quota limits remaining, writes a
self-contained HTML dashboard (Chart.js), and opens it in your default browser.

Portable: works for ANY user on Windows / macOS / Linux. No Node, no pip installs --
just Python 3.8+. Re-run it any time to refresh the dashboard.

    python antigravity_usage.py                 # parse, write, and open
    python antigravity_usage.py --no-open       # just write the HTML
    python antigravity_usage.py --days 7        # only the last 7 days
    python antigravity_usage.py --req-limit 2000 # custom daily request limit
    python antigravity_usage.py --token-limit 100M # custom daily token limit
    python antigravity_usage.py --dir /path/to/.gemini
    python antigravity_usage.py --out report.html
"""
import argparse, json, os, sys, glob, datetime, collections, webbrowser, sqlite3


# --------------------------------------------------------------------------- #
#  Locate the Antigravity / Gemini data directory (portable across users / OSes)
# --------------------------------------------------------------------------- #
def find_root(explicit=None):
    if explicit:
        return explicit if os.path.isdir(explicit) else None
    candidates = []
    for env_var in (
        "ANTIGRAVITY_APP_DATA_DIR",
        "GEMINI_CONFIG_DIR",
        "GEMINI_DIR",
        "ANTIGRAVITY_DIR",
    ):
        val = os.environ.get(env_var)
        if val:
            candidates.append(val)
            candidates.append(os.path.dirname(val))
    home = os.path.expanduser("~")
    candidates.append(os.path.join(home, ".gemini"))
    candidates.append(os.path.join(home, ".antigravity"))
    candidates.append(os.path.join(home, ".config", "gemini"))
    candidates.append(os.path.join(home, ".config", "antigravity"))
    if sys.platform == "win32":
        appdata = os.environ.get("APPDATA")
        if appdata:
            candidates.append(os.path.join(appdata, "Gemini"))
            candidates.append(os.path.join(appdata, "Antigravity"))
        localappdata = os.environ.get("LOCALAPPDATA")
        if localappdata:
            candidates.append(os.path.join(localappdata, "Gemini"))
            candidates.append(os.path.join(localappdata, "Antigravity"))

    for c in candidates:
        if c and os.path.isdir(c):
            return c
    return None


# --------------------------------------------------------------------------- #
#  Protobuf Decoder for SQLite gen_metadata
# --------------------------------------------------------------------------- #
def decode_protobuf(data):
    if not isinstance(data, (bytes, bytearray)):
        return {}
    fields = {}
    pos = 0
    dlen = len(data)
    while pos < dlen:
        try:
            byte = data[pos]
            field_num = byte >> 3
            wire_type = byte & 7
            pos += 1
            if wire_type == 0:  # varint
                val = 0
                shift = 0
                while True:
                    if pos >= dlen:
                        break
                    b = data[pos]
                    pos += 1
                    val |= (b & 0x7F) << shift
                    shift += 7
                    if not (b & 0x80):
                        break
                fields[field_num] = val
            elif wire_type == 2:  # length delimited
                length = 0
                shift = 0
                while True:
                    if pos >= dlen:
                        break
                    b = data[pos]
                    pos += 1
                    length |= (b & 0x7F) << shift
                    shift += 7
                    if not (b & 0x80):
                        break
                if pos + length > dlen:
                    break
                sub = data[pos : pos + length]
                pos += length
                fields[field_num] = sub
            elif wire_type == 1:  # 64-bit
                pos += 8
            elif wire_type == 5:  # 32-bit
                pos += 4
            else:
                break
        except Exception:
            break
    return fields


def parse_antigravity_db_tokens(db_path):
    tokens_list = []
    try:
        conn = sqlite3.connect(db_path)
        cur = conn.cursor()
        cur.execute("SELECT idx, data FROM gen_metadata ORDER BY idx")
        for idx, data in cur.fetchall():
            f = decode_protobuf(data)
            step_indices = list(f.get(2, b"")) if isinstance(f.get(2), bytes) else []
            inp = 0
            out = 0
            cached = 0
            thoughts = 0
            if 1 in f and isinstance(f[1], bytes):
                inner = decode_protobuf(f[1])
                if 2 in inner and isinstance(inner[2], bytes):
                    sub2 = decode_protobuf(inner[2])
                    inp = sub2.get(2, 0) if isinstance(sub2.get(2), int) else 0
                    out = sub2.get(3, 0) if isinstance(sub2.get(3), int) else 0
                    cached = sub2.get(5, 0) if isinstance(sub2.get(5), int) else 0
                    thoughts = sub2.get(9, 0) if isinstance(sub2.get(9), int) else 0
            tokens_list.append({
                "idx": idx,
                "steps": step_indices,
                "in": inp,
                "out": out,
                "cached": cached,
                "thoughts": thoughts,
            })
        conn.close()
    except Exception:
        pass
    return tokens_list


# --------------------------------------------------------------------------- #
#  Parse
# --------------------------------------------------------------------------- #
def new_counter():
    return collections.Counter()


def format_model_name(raw_name):
    if not raw_name or raw_name in ("<synthetic>", "unknown"):
        return "Gemini 3.7 Flash"
    r = raw_name.lower().strip()
    if "3.7" in r or "gemini-3.7" in r:
        return "Gemini 3.7 Flash"
    if "gemini-3" in r or "3-flash" in r:
        return "Gemini 3.0 Flash"
    if "2.5-pro" in r or "pro" in r:
        return "Gemini 2.5 Pro"
    if "2.5-flash" in r or "flash" in r:
        return "Gemini 2.5 Flash"
    return raw_name


def parse(root, since=None):
    tot = new_counter()
    by_day = collections.defaultdict(new_counter)
    by_day_hours = collections.defaultdict(lambda: collections.defaultdict(int))
    by_model = collections.defaultdict(new_counter)
    by_project = collections.defaultdict(new_counter)
    by_hour = collections.Counter()
    by_dow = collections.Counter()
    tool_counts = collections.Counter()
    sessions = {}
    user_msgs = 0
    assistant_msgs = 0
    parsed_files = 0

    # Project map from projects.json
    project_map = {}
    for pjson_candidate in (
        os.path.join(root, "projects.json"),
        os.path.join(root, "..", "projects.json"),
    ):
        if os.path.isfile(pjson_candidate):
            try:
                with open(pjson_candidate, "r", encoding="utf-8") as f:
                    pj = json.load(f)
                    project_map.update(pj.get("projects", {}))
            except Exception:
                pass

    # Conversation summaries map
    conv_summaries = {}
    for csdb_candidate in (
        os.path.join(root, "antigravity-cli", "conversation_summaries.db"),
        os.path.join(root, "antigravity", "conversation_summaries.db"),
        os.path.join(root, "conversation_summaries.db"),
    ):
        if os.path.isfile(csdb_candidate):
            try:
                conn = sqlite3.connect(csdb_candidate)
                cur = conn.cursor()
                cur.execute(
                    "SELECT conversation_id, title, preview, workspace_uris, last_modified_time FROM conversation_summaries"
                )
                for cid, title, prev, w_uris, lmt in cur.fetchall():
                    pname = title or prev or "Antigravity Session"
                    if w_uris:
                        try:
                            uris = json.loads(w_uris)
                            if uris:
                                u0 = (
                                    uris[0]
                                    .replace("file:///", "")
                                    .replace("file://", "")
                                )
                                pname = os.path.basename(u0) or u0
                        except Exception:
                            pass
                    conv_summaries[cid] = {
                        "title": title or prev or "",
                        "project": pname,
                        "last_modified": lmt,
                    }
                conn.close()
            except Exception:
                pass

    # 1. Parse Antigravity Agent Sessions (conversations + brain transcripts)
    for ag_dir_name in ("antigravity-cli", "antigravity", ""):
        ag_root = os.path.join(root, ag_dir_name) if ag_dir_name else root
        if not os.path.isdir(ag_root):
            continue

        conv_dir = os.path.join(ag_root, "conversations")
        conv_tokens = {}
        if os.path.isdir(conv_dir):
            for db_name in os.listdir(conv_dir):
                if db_name.endswith(".db"):
                    cid = db_name[:-3]
                    db_path = os.path.join(conv_dir, db_name)
                    parsed_files += 1
                    t_list = parse_antigravity_db_tokens(db_path)
                    if t_list:
                        conv_tokens[cid] = t_list

        brain_dir = os.path.join(ag_root, "brain")
        if os.path.isdir(brain_dir):
            for cid in os.listdir(brain_dir):
                tfile = os.path.join(
                    brain_dir, cid, ".system_generated", "logs", "transcript.jsonl"
                )
                if not os.path.isfile(tfile):
                    continue
                parsed_files += 1
                meta = conv_summaries.get(cid, {})
                proj_name = meta.get("project") or "wasql"
                model_name = "Gemini 3.7 Flash"

                tokens_list = conv_tokens.get(cid, [])
                tok_idx = 0
                s_start = None
                s_end = None
                s_msgs = 0
                s_tokens = 0

                try:
                    with open(tfile, "r", encoding="utf-8", errors="replace") as fh:
                        for line in fh:
                            line = line.strip()
                            if not line:
                                continue
                            try:
                                step = json.loads(line)
                            except Exception:
                                continue

                            ts = step.get("created_at")
                            stype = step.get("type")
                            source = step.get("source")

                            if since and ts and ts[:10] < since:
                                continue

                            if ts:
                                if not s_start or ts < s_start:
                                    s_start = ts
                                if not s_end or ts > s_end:
                                    s_end = ts

                            if stype == "USER_INPUT" or source == "USER_EXPLICIT":
                                user_msgs += 1

                            if stype == "PLANNER_RESPONSE" or source == "MODEL":
                                assistant_msgs += 1
                                s_msgs += 1

                                inp = 0
                                out = 0
                                cached = 0
                                thoughts = 0
                                if tok_idx < len(tokens_list):
                                    tinfo = tokens_list[tok_idx]
                                    inp = tinfo["in"]
                                    out = tinfo["out"]
                                    cached = tinfo["cached"]
                                    thoughts = tinfo["thoughts"]
                                    tok_idx += 1
                                elif len(tokens_list) == 0:
                                    clen = len(step.get("content") or "") + len(
                                        step.get("thinking") or ""
                                    )
                                    out = max(10, clen // 4)
                                    inp = 500

                                def add_tok(c):
                                    c["in"] += inp
                                    c["out"] += out
                                    c["cache_read"] += cached
                                    c["thoughts"] += thoughts
                                    c["msgs"] += 1

                                add_tok(tot)
                                add_tok(by_model[model_name])
                                add_tok(by_project[proj_name])

                                s_tokens += inp + out + cached

                                day = "?"
                                if ts:
                                    try:
                                        dt = datetime.datetime.fromisoformat(
                                            ts.replace("Z", "+00:00")
                                        ).astimezone()
                                        day = dt.strftime("%Y-%m-%d")
                                        by_hour[dt.hour] += 1
                                        by_dow[dt.weekday()] += 1
                                        by_day_hours[day][dt.hour] += 1
                                    except Exception:
                                        pass
                                add_tok(by_day[day])

                                for tc in step.get("tool_calls", []) or []:
                                    tname = (
                                        tc.get("name")
                                        or tc.get("toolSummary")
                                        or "tool"
                                    )
                                    tool_counts[tname] += 1

                            if stype in (
                                "RUN_COMMAND",
                                "VIEW_FILE",
                                "WRITE_TO_FILE",
                                "REPLACE_FILE_CONTENT",
                                "SCHEDULE",
                                "GREP_SEARCH",
                                "LIST_DIR",
                            ):
                                tool_counts[stype.lower()] += 1

                    if s_msgs > 0:
                        sessions[cid] = {
                            "sid": cid,
                            "title": meta.get("title") or "Antigravity Session",
                            "proj": proj_name,
                            "start": s_start,
                            "end": s_end,
                            "msgs": s_msgs,
                            "tokens": s_tokens,
                            "model": model_name,
                            "engine": "Antigravity",
                        }
                except Exception:
                    pass

    # 2. Parse Gemini CLI Chat Sessions (tmp/**/chats/*)
    tmp_dir = os.path.join(root, "tmp")
    if os.path.isdir(tmp_dir):
        for root_dir, dirs, files in os.walk(tmp_dir):
            for fname in files:
                fpath = os.path.join(root_dir, fname)
                if not (
                    fname.startswith("session-")
                    and (fname.endswith(".jsonl") or fname.endswith(".json"))
                ):
                    continue
                parsed_files += 1

                rel = os.path.relpath(fpath, tmp_dir).split(os.sep)[0]
                proj_name = project_map.get(rel, rel)
                if len(proj_name) > 30 and (
                    rel.isalnum() or len(rel) == 64
                ):
                    proj_name = rel[:8] + "..."

                sid = fname.replace("session-", "").split(".")[0]
                s_start = None
                s_end = None
                s_msgs = 0
                s_tokens = 0
                s_model = "Gemini 3.7 Flash"

                entries = []
                if fname.endswith(".jsonl"):
                    try:
                        with open(fpath, "r", encoding="utf-8", errors="replace") as fh:
                            for l in fh:
                                if l.strip():
                                    try:
                                        entries.append(json.loads(l.strip()))
                                    except Exception:
                                        pass
                    except Exception:
                        pass
                elif fname.endswith(".json"):
                    try:
                        with open(fpath, "r", encoding="utf-8", errors="replace") as fh:
                            jd = json.load(fh)
                            entries = jd.get("messages", [])
                            if "sessionId" in jd:
                                sid = jd["sessionId"]
                    except Exception:
                        pass

                for e in entries:
                    typ = e.get("type")
                    ts = e.get("timestamp")
                    if since and ts and ts[:10] < since:
                        continue

                    if ts:
                        if not s_start or ts < s_start:
                            s_start = ts
                        if not s_end or ts > s_end:
                            s_end = ts

                    if typ == "user":
                        user_msgs += 1

                    if typ == "gemini":
                        assistant_msgs += 1
                        s_msgs += 1

                        raw_model = e.get("model") or "Gemini 3.7 Flash"
                        model = format_model_name(raw_model)
                        s_model = model

                        tok = e.get("tokens") or {}
                        inp = tok.get("input", 0) if isinstance(tok, dict) else 0
                        out = tok.get("output", 0) if isinstance(tok, dict) else 0
                        cached = tok.get("cached", 0) if isinstance(tok, dict) else 0
                        thoughts = tok.get("thoughts", 0) if isinstance(tok, dict) else 0

                        def add_tok2(c):
                            c["in"] += inp
                            c["out"] += out
                            c["cache_read"] += cached
                            c["thoughts"] += thoughts
                            c["msgs"] += 1

                        add_tok2(tot)
                        add_tok2(by_model[model])
                        add_tok2(by_project[proj_name])

                        s_tokens += inp + out + cached

                        day = "?"
                        if ts:
                            try:
                                dt = datetime.datetime.fromisoformat(
                                    ts.replace("Z", "+00:00")
                                ).astimezone()
                                day = dt.strftime("%Y-%m-%d")
                                by_hour[dt.hour] += 1
                                by_dow[dt.weekday()] += 1
                                by_day_hours[day][dt.hour] += 1
                            except Exception:
                                pass
                        add_tok2(by_day[day])

                        for tc in (
                            e.get("toolCalls", []) or e.get("tool_calls", []) or []
                        ):
                            tname = tc.get("name") or "tool"
                            tool_counts[tname] += 1

                if s_msgs > 0:
                    sessions[sid] = {
                        "sid": sid,
                        "title": f"Chat in {proj_name}",
                        "proj": proj_name,
                        "start": s_start,
                        "end": s_end,
                        "msgs": s_msgs,
                        "tokens": s_tokens,
                        "model": s_model,
                        "engine": "Gemini CLI",
                    }

    return dict(
        tot=tot,
        by_day=by_day,
        by_day_hours=by_day_hours,
        by_model=by_model,
        by_project=by_project,
        by_hour=by_hour,
        by_dow=by_dow,
        tools=tool_counts,
        sessions=sessions,
        user_msgs=user_msgs,
        assistant_msgs=assistant_msgs,
        files=parsed_files,
    )


# --------------------------------------------------------------------------- #
#  Shape into JSON + derive token-saving tips, remaining limits, and terms
# --------------------------------------------------------------------------- #
def C(c):
    return {
        "in": c["in"],
        "out": c["out"],
        "cache_read": c["cache_read"],
        "thoughts": c["thoughts"],
        "msgs": c["msgs"],
    }


def human(n):
    for unit in ("", "K", "M", "B", "T"):
        if abs(n) < 1000:
            return (
                f"{n:.1f}{unit}".rstrip("0").rstrip(".")
                if unit
                else f"{n:.0f}"
            )
        n /= 1000.0
    return f"{n:.1f}P"


def parse_human_num(s, default):
    if not s:
        return default
    try:
        s = str(s).strip().upper()
        mult = 1
        if s.endswith("K"):
            mult = 1000
            s = s[:-1]
        elif s.endswith("M"):
            mult = 1000000
            s = s[:-1]
        elif s.endswith("B"):
            mult = 1000000000
            s = s[:-1]
        return int(float(s) * mult)
    except Exception:
        return default


def session_duration(s):
    try:
        a = datetime.datetime.fromisoformat(s["start"].replace("Z", "+00:00"))
        b = datetime.datetime.fromisoformat(s["end"].replace("Z", "+00:00"))
        return max(0, int((b - a).total_seconds()))
    except (ValueError, AttributeError, TypeError):
        return 0


def build_tips(agg):
    tot = agg["tot"]
    turns = max(1, agg["assistant_msgs"])
    nsess = max(1, len(agg["sessions"]))
    cr, inp = tot["cache_read"], tot["in"]
    total_in = cr + inp
    reuse = (cr / total_in * 100) if total_in else 0
    ctx_per_turn = total_in / turns
    turns_per_sess = turns / nsess

    heavy = (
        max(agg["sessions"].values(), key=lambda s: s["tokens"], default=None)
        if agg["sessions"]
        else None
    )

    tips = []

    tips.append({
        "kind": "insight",
        "title": "Prompt caching is your #1 efficiency driver",
        "body": (
            f"Every agent turn sends the accumulated context. "
            f"You've read <b>{human(cr)}</b> cached tokens — about "
            f"<b>{human(ctx_per_turn)} tokens per turn</b>. "
            f"Gemini's automatic prompt caching significantly lowers turn latency and cost."
        ),
    })

    if turns_per_sess > 30:
        tips.append({
            "kind": "tip",
            "title": "Sessions run long — start fresh sessions for distinct tasks",
            "body": (
                f"You average <b>{turns_per_sess:.0f} turns per session</b>. "
                f"As context accumulates over dozens of turns, each subsequent turn re-processes "
                f"prior context. When switching to an unrelated feature or debugging task, "
                f"start a fresh session to keep context small and fast."
            ),
        })
    else:
        tips.append({
            "kind": "tip",
            "title": "Focused sessions keep turn latency low",
            "body": (
                "Starting a fresh session for each distinct task ensures that earlier, "
                "irrelevant file reads and command outputs are not carried over into new turns."
            ),
        })

    if reuse < 60:
        tips.append({
            "kind": "tip",
            "title": f"Cache reuse is {reuse:.0f}% — work in focused bursts",
            "body": (
                "Prompt caches remain warm during active coding periods. Interacting "
                "in focused bursts keeps the prompt cache hot and avoids cold context rebuilds."
            ),
        })
    else:
        tips.append({
            "kind": "insight",
            "title": f"Cache reuse is healthy ({reuse:.0f}%)",
            "body": (
                "The vast majority of your input context is served directly from the prompt cache, "
                "minimizing token processing and turn response times."
            ),
        })

    tips.append({
        "kind": "tip",
        "title": "Target specific files and line ranges",
        "body": (
            "Specifying precise filenames, functions, or line slices in prompts prevents "
            "the agent from needing broad search sweeps or dumping full directory contents "
            "into the context window."
        ),
    })

    if heavy:
        tips.append({
            "kind": "insight",
            "title": "Heaviest session",
            "body": (
                f"Your heaviest session in <b>{heavy['proj']}</b> ({heavy['engine']}) ran "
                f"<b>{heavy['msgs']} turns</b> and transferred <b>{human(heavy['tokens'])} tokens</b>."
            ),
        })

    tips.append({
        "kind": "tip",
        "title": "Use targeted subagents for isolated research",
        "body": (
            "When performing deep codebase investigations, delegating to subagents keeps "
            "the main conversation context clean and prevents exploration clutter from inflating "
            "every subsequent turn."
        ),
    })

    return tips


def get_terms_legend():
    return [
        {
            "term": "Turn / Assistant Turn",
            "badge": "Activity",
            "desc": "A complete AI interaction cycle. Each turn begins when the model receives your prompt or tool result, plans its actions, optionally executes tools, and generates a response.",
            "note": "More turns per task increase cumulative context re-reading."
        },
        {
            "term": "Tokens",
            "badge": "Volume",
            "desc": "The basic atomic unit of text comprehension used by AI models. In English, 1 token is roughly 4 characters or ~0.75 words.",
            "note": "1,000 tokens ≈ 750 words."
        },
        {
            "term": "Prompt Input Tokens",
            "badge": "Input",
            "desc": "Fresh text sent to the model for the first time — including your current prompt, system instructions, and newly read file contents or terminal outputs.",
            "note": "Subject to initial prompt processing."
        },
        {
            "term": "Prompt Cache Read",
            "badge": "Optimization",
            "desc": "Prior conversation context and system instructions re-read directly from Gemini's server-side cache rather than being computed from scratch.",
            "note": "Dramatic latency and cost reduction (90%+ cheaper than fresh input)."
        },
        {
            "term": "Generated Output Tokens",
            "badge": "Output",
            "desc": "The text, markdown, explanations, and code edits generated by the model in its response.",
            "note": "Output generation is computationally more intensive than input reading."
        },
        {
            "term": "Reasoning Thoughts (CoT)",
            "badge": "Reasoning",
            "desc": "Internal 'Thinking' tokens produced during step-by-step chain-of-thought planning before producing the visible response (e.g. Gemini 3.7 Flash).",
            "note": "Allows deep planning and error-checking without cluttering user output."
        },
        {
            "term": "Cache Reuse %",
            "badge": "Efficiency",
            "desc": "The proportion of total input context served from the warm prompt cache: [Cache Reads ÷ (Cache Reads + Fresh Input)] × 100.",
            "note": "Values above 70% indicate efficient, warm-cache workflow."
        },
        {
            "term": "Session / Trajectory",
            "badge": "Scope",
            "desc": "A continuous conversation thread or task focused on a specific project directory with its own context history and scratchpad.",
            "note": "Starting fresh sessions for distinct tasks prevents context bloat."
        },
        {
            "term": "Tool Calls / Executions",
            "badge": "Tools",
            "desc": "Actions performed by the agent on your local machine (e.g., view_file, run_command, replace_file_content, grep_search, write_to_file, MCP calls).",
            "note": "Targeted file reads reduce unnecessary tool loops."
        },
        {
            "term": "Daily Limit / Quota (RPD)",
            "badge": "Quota",
            "desc": "The maximum number of Requests (turns) Per Day permitted by your account plan or tier before rate-limiting occurs.",
            "note": "Default tier is typically 1,500 RPD."
        },
        {
            "term": "Daily Token Quota (TPD)",
            "badge": "Quota",
            "desc": "The total volume of tokens (input + output + cached) permitted across a 24-hour window.",
            "note": "Tracks cumulative volume against your plan's cap."
        },
        {
            "term": "Burn Rate & Midnight Reset",
            "badge": "Pacing",
            "desc": "Average turns consumed per active hour today. Quota limits reset every 24 hours at midnight.",
            "note": "Helps pace heavy agent workloads across the day."
        }
    ]


def shape(agg, req_limit=1500, token_limit=50000000):
    days_sorted = sorted(d for d in agg["by_day"] if d != "?")
    sess_list = []
    for s in agg["sessions"].values():
        sess_list.append({
            "sid": s["sid"][:8],
            "title": s.get("title", ""),
            "proj": s["proj"],
            "start": s["start"],
            "dur_s": session_duration(s),
            "msgs": s["msgs"],
            "tokens": s["tokens"],
            "model": s["model"],
            "engine": s.get("engine", "Antigravity"),
        })

    # Calculate today's stats & quota limits
    now = datetime.datetime.now()
    today_str = now.strftime("%Y-%m-%d")
    today_data = agg["by_day"].get(today_str, new_counter())
    today_hours = agg.get("by_day_hours", {}).get(today_str, {})

    t_msgs = today_data.get("msgs", 0)
    t_in = today_data.get("in", 0)
    t_out = today_data.get("out", 0)
    t_cached = today_data.get("cache_read", 0)
    t_thoughts = today_data.get("thoughts", 0)
    t_tokens = t_in + t_out + t_cached

    active_hours_today = len(today_hours) or (1 if t_msgs > 0 else 0)
    burn_rate_hr = round(t_msgs / active_hours_today, 1) if active_hours_today else 0
    curr_hour_msgs = today_hours.get(now.hour, 0)

    # Midnight reset countdown
    midnight = (now + datetime.timedelta(days=1)).replace(
        hour=0, minute=0, second=0, microsecond=0
    )
    seconds_until_reset = max(0, int((midnight - now).total_seconds()))
    hours_until_reset = round(seconds_until_reset / 3600.0, 1)

    quota = {
        "today_str": today_str,
        "today_msgs": t_msgs,
        "today_tokens": t_tokens,
        "today_in": t_in,
        "today_out": t_out,
        "today_cached": t_cached,
        "today_thoughts": t_thoughts,
        "active_hours": active_hours_today,
        "burn_rate_hr": burn_rate_hr,
        "curr_hour_msgs": curr_hour_msgs,
        "hours_until_reset": hours_until_reset,
        "req_limit": req_limit,
        "token_limit": token_limit,
        "req_remaining": max(0, req_limit - t_msgs),
        "token_remaining": max(0, token_limit - t_tokens),
        "req_used_pct": round(min(100.0, (t_msgs / req_limit * 100.0)), 1)
        if req_limit
        else 0,
        "token_used_pct": round(min(100.0, (t_tokens / token_limit * 100.0)), 1)
        if token_limit
        else 0,
    }

    return {
        "generated": datetime.datetime.now().strftime("%Y-%m-%d %H:%M"),
        "range": [days_sorted[0], days_sorted[-1]] if days_sorted else ["?", "?"],
        "totals": C(agg["tot"]),
        "user_msgs": agg["user_msgs"],
        "assistant_msgs": agg["assistant_msgs"],
        "sessions_count": len(agg["sessions"]),
        "active_days": len(days_sorted),
        "files": agg["files"],
        "quota": quota,
        "by_day": {d: C(agg["by_day"][d]) for d in days_sorted},
        "by_model": {m: C(agg["by_model"][m]) for m in agg["by_model"]},
        "by_project": {p: C(agg["by_project"][p]) for p in agg["by_project"]},
        "by_hour": {str(h): agg["by_hour"].get(h, 0) for h in range(24)},
        "by_dow": {str(i): agg["by_dow"].get(i, 0) for i in range(7)},
        "tools": dict(agg["tools"].most_common(25)),
        "top_sessions": sorted(
            sess_list, key=lambda x: x["tokens"], reverse=True
        )[:20],
        "tips": build_tips(agg),
        "terms": get_terms_legend(),
    }


# --------------------------------------------------------------------------- #
#  HTML template (self-contained; Chart.js + CSS variables)
# --------------------------------------------------------------------------- #
PAGE = r"""<!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Antigravity & Gemini Usage Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
  :root{
    --page:#0c0d0e; --surface:#16181a; --surface2:#1f2226; --surface3:#282c32;
    --ink:#f4f5f6; --ink2:#c2c7cc; --muted:#858c94;
    --grid:#282c32; --axis:#343a42; --border:rgba(255,255,255,.10);
    --s1:#4285f4; --s2:#ea4335; --s3:#34a853; --s4:#fbbc04; --s5:#a142f4;
    --s6:#24c1e0; --s7:#fa7b17; --s8:#f439a0; --good:#34a853; --warn:#fbbc04; --danger:#ea4335;
    --tipbg:rgba(66,133,244,.08); --quotabg:rgba(66,133,244,.05);
  }
  html[data-theme="light"]{
    --page:#f8f9fa; --surface:#ffffff; --surface2:#f1f3f4; --surface3:#e8eaed;
    --ink:#202124; --ink2:#4d5156; --muted:#70757a;
    --grid:#dadce0; --axis:#bdc1c6; --border:rgba(0,0,0,.10);
    --s1:#1a73e8; --s2:#d93025; --s3:#188038; --s4:#f29900; --s5:#9334e6;
    --s6:#12b5cb; --s7:#e37400; --s8:#e52592; --good:#188038; --warn:#f29900; --danger:#d93025;
    --tipbg:rgba(26,115,232,.06); --quotabg:rgba(26,115,232,.04);
  }
  *{box-sizing:border-box}
  body{margin:0;background:var(--page);color:var(--ink);
    font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;-webkit-font-smoothing:antialiased}
  .wrap{max-width:1280px;margin:0 auto;padding:28px 24px 60px}
  header{display:flex;align-items:baseline;justify-content:space-between;flex-wrap:wrap;gap:12px}
  .brand{display:flex;align-items:center;gap:10px}
  .logo-badge{background:linear-gradient(135deg,#4285f4,#34a853,#ea4335,#fbbc04);
    width:14px;height:14px;border-radius:4px;display:inline-block}
  h1{font-size:24px;font-weight:650;margin:0;letter-spacing:-.01em;display:flex;align-items:center;gap:8px}
  .sub{color:var(--muted);font-size:13px;margin-top:4px}
  .toggle{background:var(--surface);color:var(--ink2);border:1px solid var(--border);
    border-radius:8px;padding:7px 13px;font-size:13px;cursor:pointer;transition:all .15s}
  .toggle:hover{color:var(--ink);background:var(--surface2)}
  
  .tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin:22px 0 16px}
  .tile{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:16px 18px;position:relative;overflow:hidden}
  .tile .lab{color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.05em;font-weight:600}
  .tile .val{font-size:28px;font-weight:650;margin-top:6px;letter-spacing:-.01em}
  .tile .note{color:var(--ink2);font-size:12.5px;margin-top:4px}
  .accent{color:var(--s1)}
  .accent-green{color:var(--s3)}
  .accent-purple{color:var(--s5)}
  .accent-gold{color:var(--s4)}
  .accent-red{color:var(--s2)}
  
  /* Quota & Limits Section */
  .quota-section{background:var(--quotabg);border:1px solid var(--border);border-radius:16px;padding:20px;margin:18px 0}
  .quota-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:16px}
  .quota-title{font-size:16px;font-weight:650;display:flex;align-items:center;gap:8px;margin:0}
  .quota-controls{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
  .preset-btn{background:var(--surface);color:var(--ink2);border:1px solid var(--border);border-radius:6px;padding:4px 9px;font-size:11.5px;cursor:pointer;font-weight:500;transition:all .15s}
  .preset-btn:hover,.preset-btn.active{color:var(--ink);background:var(--surface2);border-color:var(--s1)}
  .quota-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px}
  .qcard{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:16px 18px}
  .qcard .qlab{color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.05em;font-weight:600;display:flex;justify-content:space-between}
  .qcard .qval{font-size:24px;font-weight:700;margin:6px 0 4px;letter-spacing:-.01em}
  .qcard .qsub{font-size:12px;color:var(--ink2);margin-bottom:10px}
  .progress-bg{width:100%;height:8px;background:var(--surface2);border-radius:99px;overflow:hidden;position:relative}
  .progress-bar{height:100%;border-radius:99px;transition:width .4s ease,background-color .3s ease}
  .progress-meta{display:flex;justify-content:space-between;font-size:11.5px;color:var(--muted);margin-top:6px}
  
  .grid{display:grid;grid-template-columns:repeat(12,1fr);gap:16px;margin-top:16px}
  .card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:18px 20px 14px}
  .card h2{font-size:14px;font-weight:650;margin:0 0 2px;letter-spacing:-.01em}
  .card .cs{color:var(--muted);font-size:12px;margin-bottom:12px}
  .col-12{grid-column:span 12}.col-8{grid-column:span 8}.col-7{grid-column:span 7}
  .col-6{grid-column:span 6}.col-5{grid-column:span 5}.col-4{grid-column:span 4}
  .chartbox{position:relative;width:100%}
  table{width:100%;border-collapse:collapse;font-size:12.5px}
  th,td{text-align:left;padding:8px 10px;border-bottom:1px solid var(--border);font-variant-numeric:tabular-nums}
  th{color:var(--muted);font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.04em}
  td.num,th.num{text-align:right}
  .badge-engine{display:inline-block;padding:2px 6px;border-radius:4px;font-size:11px;font-weight:600;background:var(--surface2);color:var(--ink2)}
  .badge-engine.Antigravity{background:rgba(66,133,244,.15);color:var(--s1)}
  .badge-engine.GeminiCLI{background:rgba(52,168,83,.15);color:var(--s3)}
  
  /* Tips */
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

  /* Terms & Legend */
  .legend-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(270px,1fr));gap:12px;margin-top:8px}
  .legend-item{background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:12px 14px;display:flex;flex-direction:column;justify-content:space-between}
  .legend-item .lh{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px}
  .legend-item .ltitle{font-size:13px;font-weight:650;color:var(--ink)}
  .legend-item .lbadge{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;padding:2px 6px;border-radius:4px;background:var(--surface3);color:var(--ink2)}
  .legend-item .ldesc{font-size:12.5px;color:var(--ink2);line-height:1.45;margin:0}
  .legend-item .lnote{font-size:11.5px;color:var(--muted);margin-top:6px;font-style:italic}
  
  .foot{color:var(--muted);font-size:11.5px;margin-top:26px;line-height:1.6}
  @media(max-width:900px){.col-8,.col-7,.col-6,.col-5,.col-4{grid-column:span 12}.tips,.legend-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="wrap">
  <header>
    <div>
      <h1 id="title"><span class="logo-badge"></span> Antigravity &amp; Gemini Usage</h1>
      <div class="sub" id="subtitle"></div>
    </div>
    <button class="toggle" onclick="toggleTheme()">&#9686; Theme</button>
  </header>

  <div class="tiles" id="tiles"></div>

  <!-- Quota & Limit Remaining Section -->
  <div class="quota-section">
    <div class="quota-header">
      <div>
        <h3 class="quota-title">&#9201; Daily Limit &amp; Usage Remaining</h3>
        <div class="sub" id="quotaSubtitle">Real-time daily quota tracker &bull; resets at midnight</div>
      </div>
      <div class="quota-controls">
        <span style="font-size:11.5px;color:var(--muted);margin-right:2px">Plan preset:</span>
        <button class="preset-btn" onclick="setPreset('free', 1500, 20000000)">Free (1.5k / 20M)</button>
        <button class="preset-btn" onclick="setPreset('pro', 5000, 100000000)">Pro (5k / 100M)</button>
        <button class="preset-btn" onclick="setPreset('tier2', 20000, 500000000)">Tier 2 (20k / 500M)</button>
        <button class="preset-btn" onclick="promptCustom()">Custom...</button>
      </div>
    </div>

    <div class="quota-cards" id="quotaCards"></div>
  </div>

  <div class="grid">
    <div class="card col-8">
      <h2>Turns per day</h2>
      <div class="cs">Assistant responses and agent turns over time</div>
      <div class="chartbox" style="height:250px"><canvas id="turnsDay"></canvas></div>
    </div>
    <div class="card col-4">
      <h2>Tokens by model</h2>
      <div class="cs">Share of total token volume</div>
      <div class="chartbox" style="height:250px"><canvas id="byModel"></canvas></div>
    </div>

    <div class="card col-12">
      <h2>Tokens per day</h2>
      <div class="cs">Stacked by type &mdash; cached context, input prompts, reasoning thoughts, and generated outputs</div>
      <div class="chartbox" style="height:300px"><canvas id="tokDay"></canvas></div>
    </div>

    <div class="card col-12">
      <h2>Token efficiency &amp; optimization tips</h2>
      <div class="cs">Insights and best practices derived from your actual usage</div>
      <div class="tips" id="tips"></div>
    </div>

    <div class="card col-6">
      <h2>Tokens by project / workspace</h2>
      <div class="cs">Usage distributed across codebases</div>
      <div class="chartbox" style="height:300px"><canvas id="byProj"></canvas></div>
    </div>
    <div class="card col-6">
      <h2>Most-used tools</h2>
      <div class="cs">Tool executions across all agent sessions</div>
      <div class="chartbox" style="height:300px"><canvas id="tools"></canvas></div>
    </div>

    <div class="card col-7">
      <h2>Activity by hour of day</h2>
      <div class="cs">When you work (local time)</div>
      <div class="chartbox" style="height:220px"><canvas id="byHour"></canvas></div>
    </div>
    <div class="card col-5">
      <h2>Activity by weekday</h2>
      <div class="cs">Assistant turns by day of week</div>
      <div class="chartbox" style="height:220px"><canvas id="byDow"></canvas></div>
    </div>

    <div class="card col-12">
      <h2>Heaviest sessions</h2>
      <div class="cs">Top sessions by token volume across Antigravity and Gemini CLI</div>
      <table id="sessTable"><thead><tr>
        <th>Session</th><th>Engine</th><th>Project / Topic</th><th>Started</th>
        <th class="num">Duration</th><th class="num">Turns</th><th class="num">Tokens</th>
      </tr></thead><tbody></tbody></table>
    </div>

    <!-- Terms & Concepts Legend -->
    <div class="card col-12">
      <h2>&#128218; Terms &amp; Metrics Legend</h2>
      <div class="cs">Clear definitions of terminology, token categories, and usage metrics</div>
      <div class="legend-grid" id="legendGrid"></div>
    </div>
  </div>

  <div class="foot" id="foot"></div>
</div>

<script>
let DATA = __DATA__;

// LocalStorage persistent quota preferences
try {
  const savedReq = localStorage.getItem('ag_quota_req');
  const savedTok = localStorage.getItem('ag_quota_tok');
  if(savedReq) DATA.quota.req_limit = parseInt(savedReq, 10);
  if(savedTok) DATA.quota.token_limit = parseInt(savedTok, 10);
  recalcQuota();
} catch(e){}

function recalcQuota(){
  const q = DATA.quota;
  q.req_remaining = Math.max(0, q.req_limit - q.today_msgs);
  q.token_remaining = Math.max(0, q.token_limit - q.today_tokens);
  q.req_used_pct = q.req_limit ? Math.min(100, Math.round(q.today_msgs / q.req_limit * 1000)/10) : 0;
  q.token_used_pct = q.token_limit ? Math.min(100, Math.round(q.today_tokens / q.token_limit * 1000)/10) : 0;
}

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
    document.title='Antigravity Usage — '+name;
    document.getElementById('title').innerHTML='<span class="logo-badge"></span> Antigravity &amp; Gemini — '+name;
  } else {
    document.getElementById('title').innerHTML='<span class="logo-badge"></span> Antigravity &amp; Gemini Usage ('+projs.length+' projects)';
  }
}

function renderTiles(){
  const t=DATA.totals, days=DATA.active_days||1;
  const totTok=t.in+t.out+t.cache_read;
  const reuse=(t.cache_read/(t.cache_read+t.in)*100)||0;
  const q=DATA.quota;
  const reqLeftPct = Math.max(0, 100 - q.req_used_pct).toFixed(0);

  const tiles=[
    {lab:'Total tokens',val:compact(totTok),note:compact(totTok/days)+' per active day',accent:'accent'},
    {lab:"Today's limit remaining",val:q.req_remaining.toLocaleString()+' turns',note:reqLeftPct+'% quota left today',accent:q.req_used_pct > 80 ? 'accent-red' : 'accent-green'},
    {lab:'Generated output',val:compact(t.out),note:'model response tokens',accent:''},
    {lab:'Reasoning thoughts',val:compact(t.thoughts),note:'Gemini thinking tokens',accent:'accent-purple'},
    {lab:'Assistant turns',val:DATA.assistant_msgs.toLocaleString(),note:DATA.user_msgs.toLocaleString()+' user prompts',accent:''},
    {lab:'Cache reuse',val:reuse.toFixed(0)+'%',note:'context served from cache',accent:'accent-green'},
  ];
  document.getElementById('tiles').innerHTML=tiles.map(x=>
    `<div class="tile"><div class="lab">${x.lab}</div>
     <div class="val ${x.accent}">${x.val}</div>
     <div class="note">${x.note}</div></div>`).join('');
  document.getElementById('subtitle').textContent=
    `${DATA.assistant_msgs.toLocaleString()} turns · ${DATA.sessions_count} sessions · ${DATA.range[0]} to ${DATA.range[1]}`;
  document.getElementById('foot').innerHTML=
    `Parsed ${DATA.files} session files and SQLite databases from ~/.gemini. `+
    `Reports token volume, thinking tokens, and agent activity. `+
    `Generated ${DATA.generated} by antigravity_usage.py.`;
}

function renderQuota(){
  const q = DATA.quota;
  const reqColor = q.req_used_pct > 85 ? 'var(--danger)' : q.req_used_pct > 60 ? 'var(--warn)' : 'var(--good)';
  const tokColor = q.token_used_pct > 85 ? 'var(--danger)' : q.token_used_pct > 60 ? 'var(--warn)' : 'var(--good)';
  
  const cards = [
    {
      lab: 'Daily Turns / Requests Remaining',
      val: q.req_remaining.toLocaleString() + ' <span style="font-size:15px;font-weight:500;color:var(--muted)">/ ' + q.req_limit.toLocaleString() + '</span>',
      sub: `Used <b>${q.today_msgs.toLocaleString()}</b> turns today &bull; <b>${(100 - q.req_used_pct).toFixed(1)}% remaining</b>`,
      pct: q.req_used_pct,
      barColor: reqColor,
      metaLeft: `${q.today_msgs.toLocaleString()} used (${q.req_used_pct}%)`,
      metaRight: `Resets in ~${q.hours_until_reset}h`
    },
    {
      lab: 'Daily Token Quota Remaining',
      val: compact(q.token_remaining) + ' <span style="font-size:15px;font-weight:500;color:var(--muted)">/ ' + compact(q.token_limit) + '</span>',
      sub: `Used <b>${compact(q.today_tokens)}</b> tokens today &bull; <b>${(100 - q.token_used_pct).toFixed(1)}% remaining</b>`,
      pct: q.token_used_pct,
      barColor: tokColor,
      metaLeft: `${compact(q.today_tokens)} used (${q.token_used_pct}%)`,
      metaRight: `${compact(q.today_cached)} cached`
    },
    {
      lab: "Today's Burn Rate & Pacing",
      val: `${q.burn_rate_hr} <span style="font-size:15px;font-weight:500;color:var(--muted)">turns / active hr</span>`,
      sub: `Current hour: <b>${q.curr_hour_msgs} turns</b> &bull; Active hours: <b>${q.active_hours}</b>`,
      pct: Math.min(100, Math.round(q.curr_hour_msgs / 100 * 100)),
      barColor: 'var(--s1)',
      metaLeft: `Pace: ~${Math.round(q.burn_rate_hr * q.hours_until_reset)} turns projected`,
      metaRight: `Status: Normal`
    }
  ];

  document.getElementById('quotaCards').innerHTML = cards.map(c => `
    <div class="qcard">
      <div class="qlab"><span>${c.lab}</span></div>
      <div class="qval">${c.val}</div>
      <div class="qsub">${c.sub}</div>
      <div class="progress-bg">
        <div class="progress-bar" style="width:${c.pct}%;background-color:${c.barColor}"></div>
      </div>
      <div class="progress-meta">
        <span>${c.metaLeft}</span>
        <span>${c.metaRight}</span>
      </div>
    </div>
  `).join('');
}

function setPreset(name, req, tok){
  DATA.quota.req_limit = req;
  DATA.quota.token_limit = tok;
  try {
    localStorage.setItem('ag_quota_req', req);
    localStorage.setItem('ag_quota_tok', tok);
  } catch(e){}
  recalcQuota();
  renderTiles();
  renderQuota();
}

function promptCustom(){
  const curReq = DATA.quota.req_limit;
  const curTok = DATA.quota.token_limit;
  const req = prompt("Enter daily request/turn limit:", curReq);
  if(req === null) return;
  const tok = prompt("Enter daily token limit (e.g. 50M or 50000000):", curTok);
  if(tok === null) return;
  
  let rNum = parseInt(req, 10);
  let tNum = curTok;
  if(tok){
    const s = tok.trim().toUpperCase();
    if(s.endsWith('M')) tNum = parseFloat(s) * 1000000;
    else if(s.endsWith('K')) tNum = parseFloat(s) * 1000;
    else if(s.endsWith('B')) tNum = parseFloat(s) * 1000000000;
    else tNum = parseInt(s, 10);
  }
  if(!isNaN(rNum) && rNum > 0 && !isNaN(tNum) && tNum > 0){
    setPreset('custom', rNum, tNum);
  }
}

function renderTips(){
  document.getElementById('tips').innerHTML=DATA.tips.map(t=>
    `<div class="tip ${t.kind}"><p class="tt">${t.title}<span class="badge">${t.kind}</span></p>
     <p class="tb">${t.body}</p></div>`).join('');
}

function renderLegend(){
  if(!DATA.terms) return;
  document.getElementById('legendGrid').innerHTML = DATA.terms.map(item => `
    <div class="legend-item">
      <div>
        <div class="lh">
          <span class="ltitle">${item.term}</span>
          <span class="lbadge">${item.badge}</span>
        </div>
        <p class="ldesc">${item.desc}</p>
      </div>
      <div class="lnote">&#128161; ${item.note}</div>
    </div>
  `).join('');
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
      fill:true,tension:.25,borderWidth:2,pointRadius:2,pointHoverRadius:5,pointHoverBackgroundColor:S[0]}]},
    options:baseOpts({plugins:{legend:{display:false},tooltip:{callbacks:{label:x=>x.parsed.y+' turns'}}},
      scales:{x:{grid:{display:false},ticks:{color:c.muted,maxTicksLimit:12,font:{size:10}},border:{color:c.axis}},
        y:{grid:{color:c.grid},ticks:{color:c.muted,font:{size:11}},border:{display:false}}}})}));

  const models=Object.keys(DATA.by_model);
  const mtok=m=>{const x=DATA.by_model[m];return x.in+x.out+x.cache_read;};
  charts.push(new Chart(byModel,{type:'doughnut',data:{labels:models,datasets:[{
      data:models.map(mtok),backgroundColor:models.map((m,i)=>S[i%8]),borderColor:c.surface,borderWidth:2}]},
    options:{responsive:true,maintainAspectRatio:false,cutout:'62%',
      plugins:{legend:{position:'bottom',labels:{color:c.ink2,font:{size:11},boxWidth:12,padding:10}},
        tooltip:{backgroundColor:c.surface,titleColor:c.ink,bodyColor:c.ink2,borderColor:cssv('--border'),
          borderWidth:1,padding:10,cornerRadius:8,callbacks:{label:x=>x.label+' '+compact(x.parsed)+' tokens'}}}}}));

  const stack=[
    ['Prompt Input','in',S[0]],
    ['Cache Read','cache_read',S[2]],
    ['Output','out',S[1]],
    ['Thoughts','thoughts',S[4]]
  ];
  charts.push(new Chart(tokDay,{type:'bar',data:{labels:days,datasets:stack.map(([lab,k,col])=>({
      label:lab,data:days.map(d=>DATA.by_day[d][k]),backgroundColor:col,borderColor:c.surface,borderWidth:1,borderRadius:2,stack:'t'}))},
    options:baseOpts({plugins:{legend:{display:true,position:'top',align:'end',
        labels:{color:c.ink2,font:{size:11},boxWidth:12,padding:12}},
      tooltip:{callbacks:{label:x=>x.dataset.label+' '+compact(x.parsed.y)}}},
      scales:{x:{stacked:true,grid:{display:false},ticks:{color:c.muted,maxTicksLimit:15,font:{size:10}},border:{color:c.axis}},
        y:{stacked:true,grid:{color:c.grid},ticks:{color:c.muted,callback:compact,font:{size:11}},border:{display:false}}}})}));

  const projs=Object.entries(DATA.by_project).map(([k,v])=>[k,v.in+v.out+v.cache_read])
    .sort((a,b)=>b[1]-a[1]).slice(0,10);
  charts.push(new Chart(byProj,{type:'bar',data:{labels:projs.map(p=>projShort(p[0])),datasets:[{
      data:projs.map(p=>p[1]),backgroundColor:S[0],borderRadius:4}]},
    options:baseOpts({indexAxis:'y',plugins:{legend:{display:false},tooltip:{callbacks:{label:x=>compact(x.parsed.x)+' tokens'}}},
      scales:{x:{grid:{color:c.grid},ticks:{color:c.muted,callback:compact,font:{size:11}},border:{display:false}},
        y:{grid:{display:false},ticks:{color:c.ink2,font:{size:11}},border:{color:c.axis}}}})}));

  const tls=Object.entries(DATA.tools).slice(0,14);
  charts.push(new Chart(window.tools,{type:'bar',data:{labels:tls.map(t=>t[0]),datasets:[{
      data:tls.map(t=>t[1]),backgroundColor:S[2],borderRadius:4}]},
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
    const engClass = s.engine.replace(/\s+/g,'');
    const label = s.title || projShort(s.proj);
    return `<tr><td><code>${s.sid}</code></td>
      <td><span class="badge-engine ${engClass}">${s.engine}</span></td>
      <td>${label}</td>
      <td>${when}</td>
      <td class="num">${durfmt(s.dur_s)}</td>
      <td class="num">${s.msgs}</td>
      <td class="num">${compact(s.tokens)}</td></tr>`;}).join('');
}

function toggleTheme(){
  const h=document.documentElement;
  h.setAttribute('data-theme',h.getAttribute('data-theme')==='dark'?'light':'dark');
  buildAll();
}

renderHeader(); renderTiles(); renderQuota(); renderTips(); renderTable(); renderLegend(); buildAll();
</script>
</body>
</html>"""


# --------------------------------------------------------------------------- #
#  Browser launcher (Chrome / Default browser fallback)
# --------------------------------------------------------------------------- #
def find_chrome():
    if sys.platform == "win32":
        candidates = [
            os.path.join(
                os.environ.get("PROGRAMFILES", r"C:\Program Files"),
                "Google",
                "Chrome",
                "Application",
                "chrome.exe",
            ),
            os.path.join(
                os.environ.get("PROGRAMFILES(X86)", r"C:\Program Files (x86)"),
                "Google",
                "Chrome",
                "Application",
                "chrome.exe",
            ),
            os.path.join(
                os.environ.get("LOCALAPPDATA", ""),
                "Google",
                "Chrome",
                "Application",
                "chrome.exe",
            ),
        ]
    elif sys.platform == "darwin":
        candidates = ["/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"]
    else:
        candidates = [
            "/usr/bin/google-chrome",
            "/usr/bin/google-chrome-stable",
            "/usr/bin/chromium",
            "/usr/bin/chromium-browser",
        ]
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
            print(f"Could not launch Chrome ({e}) -- falling back to default browser.")
    else:
        print("Chrome not found -- opening default browser instead.")
    webbrowser.open(url)


# --------------------------------------------------------------------------- #
#  Main
# --------------------------------------------------------------------------- #
def main():
    ap = argparse.ArgumentParser(description="Antigravity & Gemini usage dashboard.")
    ap.add_argument(
        "--dir",
        help="path to .gemini directory (auto-detected if omitted)",
    )
    ap.add_argument(
        "--out", help="output HTML path (default: antigravity_usage.html next to this script)"
    )
    ap.add_argument("--days", type=int, help="only include the last N days")
    ap.add_argument(
        "--req-limit",
        "--daily-requests",
        type=int,
        default=1500,
        help="daily request/turn limit (default: 1500)",
    )
    ap.add_argument(
        "--token-limit",
        "--daily-tokens",
        default="50M",
        help="daily token limit (e.g. 50M, 100M, default: 50M)",
    )
    ap.add_argument(
        "--no-open",
        action="store_true",
        help="write the HTML file but do not open a browser",
    )
    args = ap.parse_args()

    root = find_root(args.dir)
    if not root:
        sys.exit(
            "Could not find your Antigravity / Gemini data directory (~/.gemini). "
            "Pass --dir explicitly."
        )

    since = None
    if args.days:
        since = (
            datetime.datetime.now() - datetime.timedelta(days=args.days)
        ).strftime("%Y-%m-%d")

    tok_limit_val = parse_human_num(args.token_limit, 50000000)

    print(f"Reading Antigravity & Gemini data from: {root}")
    agg = parse(root, since=since)
    if agg["assistant_msgs"] == 0:
        sys.exit("No assistant turns or messages found in that directory.")

    data = shape(agg, req_limit=args.req_limit, token_limit=tok_limit_val)
    out = args.out or os.path.join(
        os.path.dirname(os.path.abspath(__file__)), "antigravity_usage.html"
    )
    with open(out, "w", encoding="utf-8") as f:
        f.write(PAGE.replace("__DATA__", json.dumps(data)))

    t = data["totals"]
    total = t["in"] + t["out"] + t["cache_read"]
    q = data["quota"]
    print(
        f"  {agg['assistant_msgs']:,} assistant turns across "
        f"{data['sessions_count']} sessions, {data['active_days']} active days"
    )
    print(
        f"  {human(total)} total tokens ({human(t['out'])} generated output, {human(t['thoughts'])} reasoning thoughts)"
    )
    print(
        f"  Today: {q['today_msgs']:,} / {q['req_limit']:,} turns used ({q['req_remaining']:,} remaining &bull; {100 - q['req_used_pct']:.1f}% left)"
    )
    print(
        f"  Today's tokens: {human(q['today_tokens'])} / {human(q['token_limit'])} ({human(q['token_remaining'])} remaining)"
    )
    print(f"Wrote {out}")

    if not args.no_open:
        open_in_chrome(out)


if __name__ == "__main__":
    main()
