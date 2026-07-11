<?php
require_once __DIR__ . '/auth.php';

$me   = current_user();
$page = basename($_SERVER['SCRIPT_NAME']);

function nav_class(string $file, string $page): string
{
    return $file === $page ? 'nav-link is-active' : 'nav-link';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'TicketDesk') ?> · TicketDesk</title>
<script>
/* Apply the saved theme before the stylesheet paints, so there is no flash. */
(function () {
    var theme = null;
    try { theme = localStorage.getItem('theme'); } catch (err) {}
    if (theme !== 'light' && theme !== 'dark') {
        theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    document.documentElement.setAttribute('data-theme', theme);
})();
</script>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<nav class="nav">
    <div class="nav-inner">
        <a class="brand" href="index.php">
            <svg class="brand-mark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 9V6a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v3a3 3 0 0 0 0 6v3a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-3a3 3 0 0 0 0-6Z"/>
                <path d="M14 5.5v2.5M14 11v2M14 16v2.5" stroke-linecap="round"/>
            </svg>
            TicketDesk
        </a>

        <?php if ($me): ?>
        <div class="nav-links">
            <a class="<?= nav_class('dashboard.php', $page) ?>" href="dashboard.php">Dashboard</a>
            <a class="<?= nav_class('tickets.php', $page) ?>" href="tickets.php">Tickets</a>
            <?php if ($me['role'] === 'admin'): ?>
            <a class="<?= nav_class('ticket_new.php', $page) ?>" href="ticket_new.php">New ticket</a>
            <a class="<?= nav_class('users.php', $page) ?>" href="users.php">Users</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="nav-right">
            <button id="theme-toggle" class="theme-toggle" type="button" aria-label="Switch theme">
                <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="4"/>
                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
                </svg>
                <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/>
                </svg>
            </button>

            <?php if ($me): ?>
            <a class="user-chip" href="account.php" title="Account settings">
                <?= e($me['name']) ?>
                <span class="role-tag role-<?= e($me['role']) ?>"><?= e($me['role']) ?></span>
            </a>
            <a class="nav-link" href="logout.php">Log out</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="container">
<?php if ($flash = flash_get()): ?>
    <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>
