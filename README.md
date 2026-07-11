# TicketDesk

A simple internal ticketing system with two roles — **admin** and **employee** —
built with plain PHP, HTML, and CSS. Data is stored in SQLite, and the database
is created automatically on first load, so there is nothing to install or import.

## Deploy to XAMPP

1. Copy this whole folder into `htdocs` (e.g. `C:\xampp\htdocs\ticketing-system`).
2. Start **Apache** in the XAMPP control panel (MySQL is not needed).
3. Open `http://localhost/ticketing-system/` in a browser.

First login: `admin@example.com` / `admin123` — then create your team's
accounts from the **Users** page and consider replacing the seeded admin.
Admins can also self-register from the **Sign up** link on the login page.

Requirements: PHP 8+ with the `pdo_sqlite` extension (enabled by default in XAMPP).

## Roles

| | Employee | Admin |
|---|---|---|
| Create tickets | ✔ | ✔ |
| View tickets | Own + assigned to them | All |
| Edit title / description | Own tickets | Any ticket |
| Change status / priority / assignee | | ✔ |
| Manage user accounts | | ✔ |
| Self-signup (creates an admin) | — | ✔ via Sign up page |
| Change own password | ✔ Account page | ✔ Account page |
| Reset anyone's password | | ✔ from Users page |

## Ticket flow

Open → In progress → Resolved → Closed. Priorities: low / medium / high.

## Notes

- Light/dark theme toggle in the nav — persisted per browser, defaults to the
  system preference.
- The SQLite file lives at `data/tickets.db`; `data/.htaccess` blocks direct
  web access to it. To reset everything, delete that file.
- Passwords are hashed (`password_hash`), all queries use prepared statements,
  and every form carries a CSRF token.
