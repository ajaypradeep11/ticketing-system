<?php
/**
 * SQLite connection + first-run bootstrap.
 * The database file is created automatically at data/tickets.db,
 * so deploying to XAMPP is just copying this folder into htdocs.
 */

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dir = __DIR__ . '/../data';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $pdo = new PDO('sqlite:' . $dir . '/tickets.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');

        db_bootstrap($pdo);
    }

    return $pdo;
}

function db_bootstrap(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            name          TEXT    NOT NULL,
            email         TEXT    NOT NULL UNIQUE,
            password_hash TEXT    NOT NULL,
            role          TEXT    NOT NULL DEFAULT 'employee'
                          CHECK (role IN ('admin', 'employee')),
            active        INTEGER NOT NULL DEFAULT 1,
            created_at    TEXT    NOT NULL
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tickets (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id     INTEGER NOT NULL REFERENCES users(id),
            title       TEXT    NOT NULL,
            description TEXT    NOT NULL,
            priority    TEXT    NOT NULL DEFAULT 'medium'
                        CHECK (priority IN ('low', 'medium', 'high')),
            status      TEXT    NOT NULL DEFAULT 'open'
                        CHECK (status IN ('open', 'in_progress', 'resolved', 'closed')),
            assigned_to INTEGER REFERENCES users(id),
            created_at  TEXT    NOT NULL,
            updated_at  TEXT    NOT NULL
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS comments (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            ticket_id  INTEGER NOT NULL REFERENCES tickets(id),
            user_id    INTEGER NOT NULL REFERENCES users(id),
            body       TEXT    NOT NULL,
            created_at TEXT    NOT NULL
        )
    ");

    // Databases created before assignment existed get the column added in place.
    $columns = array_column($pdo->query('PRAGMA table_info(tickets)')->fetchAll(), 'name');
    if (!in_array('assigned_to', $columns, true)) {
        $pdo->exec('ALTER TABLE tickets ADD COLUMN assigned_to INTEGER REFERENCES users(id)');
    }

    $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role, created_at)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            'Administrator',
            'admin@example.com',
            password_hash('admin123', PASSWORD_DEFAULT),
            'admin',
            now(),
        ]);
    }
}

function now(): string
{
    return date('Y-m-d H:i:s');
}
