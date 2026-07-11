<?php
require_once __DIR__ . '/includes/auth.php';

$me      = require_login();
$isAdmin = $me['role'] === 'admin';

// Ticket counts by status — admins see everything, employees only their own.
$counts = ['open' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0];

if ($isAdmin) {
    $rows = db()->query('SELECT status, COUNT(*) AS n FROM tickets GROUP BY status')->fetchAll();
} else {
    $stmt = db()->prepare(
        'SELECT status, COUNT(*) AS n FROM tickets
         WHERE user_id = ? OR assigned_to = ? GROUP BY status'
    );
    $stmt->execute([$me['id'], $me['id']]);
    $rows = $stmt->fetchAll();
}
foreach ($rows as $row) {
    $counts[$row['status']] = (int) $row['n'];
}
$total = array_sum($counts);

// Recent tickets.
if ($isAdmin) {
    $stmt = db()->prepare(
        'SELECT t.*, u.name AS creator, a.name AS assignee
         FROM tickets t
         JOIN users u ON u.id = t.user_id
         LEFT JOIN users a ON a.id = t.assigned_to
         ORDER BY t.updated_at DESC LIMIT 6'
    );
    $stmt->execute();
} else {
    $stmt = db()->prepare(
        'SELECT t.*, u.name AS creator, a.name AS assignee
         FROM tickets t
         JOIN users u ON u.id = t.user_id
         LEFT JOIN users a ON a.id = t.assigned_to
         WHERE t.user_id = ? OR t.assigned_to = ?
         ORDER BY t.updated_at DESC LIMIT 6'
    );
    $stmt->execute([$me['id'], $me['id']]);
}
$recent = $stmt->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <div class="eyebrow"><?= $isAdmin ? 'All tickets' : 'Your tickets' ?></div>
        <h1>Hi, <?= e($me['name']) ?></h1>
    </div>
    <?php if ($isAdmin): ?>
    <a class="btn btn-primary" href="ticket_new.php">New ticket</a>
    <?php endif; ?>
</div>

<div class="stats">
    <a class="stat-card" href="tickets.php">
        <div class="eyebrow">Total</div>
        <div class="stat-value"><?= $total ?></div>
    </a>
    <a class="stat-card tone-open" href="tickets.php?status=open">
        <div class="eyebrow">Open</div>
        <div class="stat-value"><?= $counts['open'] ?></div>
    </a>
    <a class="stat-card tone-in_progress" href="tickets.php?status=in_progress">
        <div class="eyebrow">In progress</div>
        <div class="stat-value"><?= $counts['in_progress'] ?></div>
    </a>
    <a class="stat-card tone-resolved" href="tickets.php?status=resolved">
        <div class="eyebrow">Resolved</div>
        <div class="stat-value"><?= $counts['resolved'] ?></div>
    </a>
    <a class="stat-card tone-closed" href="tickets.php?status=closed">
        <div class="eyebrow">Closed</div>
        <div class="stat-value"><?= $counts['closed'] ?></div>
    </a>
</div>

<div class="eyebrow" style="margin-bottom: 10px;">Recently updated</div>

<?php if (!$recent): ?>
<div class="card empty">
    <h2>No tickets yet</h2>
    <p><?= $isAdmin ? 'Tickets you create will show up here.' : 'Tickets assigned to you will show up here.' ?></p>
    <?php if ($isAdmin): ?>
    <a class="btn btn-primary" href="ticket_new.php">Create a ticket</a>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Ticket</th>
                <th>Title</th>
                <?php if ($isAdmin): ?><th>Created by</th><?php endif; ?>
                <th>Assigned to</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Updated</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recent as $t): ?>
            <tr>
                <td class="cell-mono"><?= e(ticket_no((int) $t['id'])) ?></td>
                <td><a class="row-title" href="ticket_view.php?id=<?= (int) $t['id'] ?>"><?= e($t['title']) ?></a></td>
                <?php if ($isAdmin): ?><td><?= e($t['creator']) ?></td><?php endif; ?>
                <td><?= $t['assignee'] !== null ? e($t['assignee']) : '<span class="field-hint" style="margin:0;">—</span>' ?></td>
                <td><?= priority_badge($t['priority']) ?></td>
                <td><?= status_badge($t['status']) ?></td>
                <td class="cell-mono"><?= e(format_date($t['updated_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
