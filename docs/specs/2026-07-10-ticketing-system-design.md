# Simple Ticketing System — Design

Date: 2026-07-10
Status: Approved

## Goal

A very simple ticketing system with two roles (admin, employee), built with plain
HTML/CSS/PHP so it deploys to XAMPP by copying the folder into `htdocs`. Good
light and dark mode UI.

## Decisions

- **Storage:** SQLite via PDO. Database file auto-created at `data/tickets.db`
  on first page load (schema + seed admin). No import step.
- **Accounts:** Admin creates employee accounts from a Users page. Seeded admin:
  `admin@example.com` / `admin123` (change after first login).
- **Workflow:** Employees create tickets (title, description, priority) and see
  only their own. Admin sees all tickets and updates status:
  open → in_progress → resolved → closed.

## Architecture

Classic multi-page PHP, no framework, no Composer.

```
index.php            redirect to login or dashboard
login.php            login form + handler
logout.php
dashboard.php        role-aware stats + recent tickets
tickets.php          ticket list (admin: all, employee: own) with filters
ticket_new.php       create ticket form
ticket_view.php      ticket detail; admin can change status/priority
users.php            admin only: list/create/deactivate users
includes/db.php      PDO SQLite connection, schema bootstrap, seed admin
includes/auth.php    session + role guards, CSRF helpers
includes/header.php  nav + theme toggle
includes/footer.php
assets/style.css     all styling; light/dark via CSS custom properties
assets/app.js        theme toggle persisted in localStorage
data/tickets.db      SQLite (auto-created); .htaccess denies web access
```

## Data model

- `users`: id, name, email (unique), password_hash, role (admin|employee),
  active (1|0), created_at
- `tickets`: id, user_id → users.id, title, description,
  priority (low|medium|high), status (open|in_progress|resolved|closed),
  created_at, updated_at

## Security

- `password_hash()` / `password_verify()`
- PDO prepared statements everywhere
- `htmlspecialchars()` on all output
- Session auth, server-side role checks on every page
- CSRF token on all mutating forms
- `data/.htaccess` deny-all so the DB isn't downloadable

## UI

- Single stylesheet, CSS variables for theme tokens
- Theme toggle in nav; persisted in localStorage; defaults to system preference
- Dashboard stat cards, color-coded status/priority badges, responsive layout
