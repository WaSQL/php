# WaSQL Architecture Documentation

Background/onboarding doc — the mental model behind the framework. For day-to-day rules and gotchas use **`CLAUDE.md`** (always-loaded core); for verified per-feature deep-dives use **`wasql_reference.md`**. Where this file and those disagree, **`CLAUDE.md`/`wasql_reference.md` are correct** — they're actively maintained against ~100 production sites; this file is conceptual background and may lag.

## Overview

WaSQL is a PHP-driven, database-driven RAD platform: page logic (routing, model, view, styling, script) lives in `_pages` table records, not files, so an entire site is portable as a MySQL dump.

## The `_pages` Table Structure

| Field | Role | Content |
|---|---|---|
| `_id` | PK | auto-increment |
| `name` | route | URL path segment, e.g. `"user/profile"` |
| `body` | View | HTML with `<view:name>` blocks |
| `functions` | Model | PHP helpers — data access, business logic |
| `controller` | Controller | routes on `$PASSTHRU`, picks a view |
| `js` / `css` | assets | page-specific, auto-injected on render |

MVC pattern, `$PASSTHRU` routing, view/render functions, data-access functions, AJAX nav, and auth — all covered with verified examples in **`CLAUDE.md`** (essentials) and **`wasql_reference.md`** (deep detail). Don't duplicate those here.

## Multi-Language Support

Shortcode islands let a page mix languages beyond PHP within the same field — `<?py ... ?>` (Python), `<?js ... ?>` (Node.js), and others (Perl, Ruby, VBScript, Lua, Bash) are dispatched similarly to the `<?groovy ... ?>` island documented in `wasql_reference.md`'s c-tree section. All languages receive the same `$_REQUEST`/`$_SESSION`/`$_SERVER`/`$USER`. Verify exact language support against a running instance before relying on one you haven't used — this list isn't independently re-verified per language.

## Page Lifecycle

1. Request arrives via Apache/mod_rewrite
2. Router looks up the page in `_pages` by `name` **or** `permalink` (see `wasql_reference.md` → *Page routing*)
3. `controller` runs → picks a view
4. `functions` (model) supply data
5. `body` view renders
6. Page's own `css`/`js` auto-injected
7. Response sent

## Development Workflow — PostEdit

Local files synced to DB records for editing with normal tools. Full mechanics, file-naming convention, config format, and the edit loop → **`postedit.md`** (authoritative — don't duplicate here). The **`?_menu=synchronize`** admin action promotes changes between environments (see `workon.md`).

## File Structure

```
wasql/
├── php/
│   ├── database.php          # Database functions
│   ├── common.php            # View and utility functions
│   ├── user.php              # User management
│   └── wasql.php             # Core framework
├── postedit/
│   ├── postedit.php          # Local development sync tool
│   ├── postedit.xml          # Host configurations
│   └── postEditFiles/        # Synchronized local files (mirror root)
├── wfiles/                   # File storage
├── config.xml                # Configuration
└── workon.php / workon.py    # One-command session startup
```

Best practices, security notes (`encodeHtml` etc.), and variable-scoping rules are all in **`CLAUDE.md`** — see that file rather than this one, which no longer restates them (an earlier version of this section claimed controller variables are "automatically available" inside a view; that's wrong — see `wasql_reference.md`'s isolated-view-scope gotcha — and has been removed here rather than left to mislead).
