# TicketDesk (Ticketing System) — Project Memory

## About the User
- Pradeep — deploys this manually to a XAMPP server; wants it dead simple, no build tools

## Preferences
- Plain HTML/CSS/PHP only — no frameworks, no Composer, no npm (stated 2026-07-10)
- Deployment = copy folder into `htdocs`, nothing else
- Wanted good light **and** dark mode UI

## Tech Stack
- PHP 8+ (multi-page, shared `includes/`), SQLite via PDO (auto-created at `data/tickets.db` on first load)
- Single stylesheet with CSS custom properties for theming; vanilla JS theme toggle (localStorage, defaults to system)
- No local PHP on this Mac — verified via Docker `php:8.3-cli` built-in server + headless Chrome screenshots

## What's Built (2026-07-10)
- Full app, verified end-to-end (21 curl checks + light/dark/mobile screenshots):
  - Roles: **admin** (all tickets, change status/priority, manage users) vs **employee** (create + view own tickets only)
  - Ticket flow: open → in_progress → resolved → closed; priorities low/medium/high
  - Pages: login, dashboard (stat cards), tickets (filter tabs), ticket_new, ticket_view, users (admin CRUD + activate/deactivate)
  - Security: password_hash, prepared statements, CSRF on all forms, output escaping, `data/.htaccess` deny, session regeneration on login
  - Seeded admin `admin@example.com` / `admin123` — hint shows on login only while it's the sole account
- Design: "ticket stub" identity — mono ticket serials (`TKT-0042`), stub perforation divider with punched notches
- **Accent is BLUE (#1C5DD9 light / #6EA8FF dark)** — user requested "blue shade not green" on 2026-07-10; neutrals also blue-tinted
- Round 2 (2026-07-10, verified end-to-end): ticket **editing** (creator + admin via `ticket_edit.php`), ticket **assignment** (`assigned_to` col with in-place ALTER migration; employees see created OR assigned tickets), **admin self-signup** at `register.php` (name/email/pass/confirm → always creates an admin; user explicitly chose this)
- Round 3 (2026-07-10): **password management** — `account.php` (self change: current+new+confirm; nav user-chip links to it) and `user_password.php` (admin resets anyone's from Users page). Selects normalized (`appearance:none` + themed chevron `--select-arrow`) after user flagged Role combo height mismatch on macOS
- Round 4 (2026-07-10, verified end-to-end via Docker curl suite): **role rework + comments** — employees can NO longer create tickets (`ticket_new.php` = `require_admin()`; all New-ticket links/buttons admin-only). Employees CAN change **status** (status-only form on `ticket_view.php`, `action=update`; priority/assignee ignored for them). New **comments** table (bootstrap in `db.php`) + "Progress & comments" thread on ticket view — anyone who can view the ticket can post (`action=comment`, bumps `updated_at`). Comment CSS in `assets/style.css` (`.comment-list/.comment/...`). README role matrix updated. NOTE for testing: `register.php` self-signup always creates an **admin** — create employees via `users.php` (`action=create`)

## How to Run
- XAMPP: copy folder to `htdocs`, start Apache, open `http://localhost/ticketing-system/` (MySQL not needed)
- Local test on this Mac: `docker run -d -v $(pwd):/app -w /app -p 8899:8899 php:8.3-cli php -S 0.0.0.0:8899` (Docker Desktop installed; daemon usually stopped)
- Reset all data: delete `data/tickets.db`

## Key Files
| File | Role |
|---|---|
| `includes/db.php` | PDO SQLite + schema bootstrap + seed admin |
| `includes/auth.php` | session, role guards, CSRF, view helpers (badges, `ticket_no`) |
| `includes/header.php` | nav, pre-paint theme script |
| `assets/style.css` | all theming — light default, `:root[data-theme="dark"]` overrides |
| `docs/specs/2026-07-10-ticketing-system-design.md` | approved design doc |

## What's Next (Ideas)
- ~~Ticket comments thread~~ done (round 4)
- ~~Ticket assignment to employees~~ done (round 2)
- ~~"Change password" page~~ done (round 3)
- Employee edit rights on title/description are now vestigial (employees can't create tickets, so they're never the creator of new tickets) — could remove or repurpose
