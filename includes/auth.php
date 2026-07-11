<?php
/**
 * Session, auth guards, CSRF protection, and small view helpers.
 */

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------- auth ---------- */

function current_user(): ?array
{
    static $user = null;
    static $loaded = false;

    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    if (!$loaded) {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ? AND active = 1');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
        $loaded = true;

        if ($user === null) {
            unset($_SESSION['user_id']);
        }
    }

    return $user;
}

function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        header('Location: login.php');
        exit;
    }
    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        header('Location: dashboard.php');
        exit;
    }
    return $user;
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_check(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Invalid request token. Go back and try again.');
    }
}

/* ---------- flash messages ---------- */

function flash_set(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function flash_get(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/* ---------- view helpers ---------- */

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function ticket_no(int $id): string
{
    return sprintf('TKT-%04d', $id);
}

function format_date(string $datetime): string
{
    return date('M j, Y', strtotime($datetime));
}

function format_datetime(string $datetime): string
{
    return date('M j, Y · g:i a', strtotime($datetime));
}

function status_label(string $status): string
{
    $labels = [
        'open'        => 'Open',
        'in_progress' => 'In progress',
        'resolved'    => 'Resolved',
        'closed'      => 'Closed',
    ];
    return $labels[$status] ?? $status;
}

function status_badge(string $status): string
{
    return '<span class="badge st-' . e($status) . '">' . e(status_label($status)) . '</span>';
}

function priority_label(string $priority): string
{
    return ucfirst($priority);
}

function priority_badge(string $priority): string
{
    return '<span class="badge prio-' . e($priority) . '"><span class="dot"></span>'
        . e(priority_label($priority)) . '</span>';
}

const TICKET_STATUSES  = ['open', 'in_progress', 'resolved', 'closed'];
const TICKET_PRIORITIES = ['low', 'medium', 'high'];
