<?php
require_once __DIR__ . '/includes/auth.php';

$me      = require_login();
$isAdmin = $me['role'] === 'admin';

$filter = $_GET['status'] ?? '';
if (!in_array($filter, TICKET_STATUSES, true)) {
    $filter = '';
}

// Counts per status for the filter tabs (scoped to role).
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

// Ticket list.
$sql    = 'SELECT t.*, u.name AS creator, a.name AS assignee
           FROM tickets t
           JOIN users u ON u.id = t.user_id
           LEFT JOIN users a ON a.id = t.assigned_to';
$where  = [];
$params = [];

if (!$isAdmin) {
    $where[]  = '(t.user_id = ? OR t.assigned_to = ?)';
    $params[] = $me['id'];
    $params[] = $me['id'];
}
if ($filter !== '') {
    $where[]  = 't.status = ?';
    $params[] = $filter;
}
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY t.updated_at DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

$pageTitle = 'Tickets';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <div class="eyebrow"><?= $isAdmin ? 'All tickets' : 'Your tickets' ?></div>
        <h1>Tickets</h1>
    </div>
    <?php if ($isAdmin): ?>
    <a class="btn btn-primary" href="ticket_new.php">New ticket</a>
    <?php endif; ?>
</div>

<div class="tabs">
    <a class="tab <?= $filter === '' ? 'is-active' : '' ?>" href="tickets.php">
        All <span class="count"><?= $total ?></span>
    </a>
    <?php foreach (TICKET_STATUSES as $status): ?>
    <a class="tab <?= $filter === $status ? 'is-active' : '' ?>" href="tickets.php?status=<?= e($status) ?>">
        <?= e(status_label($status)) ?> <span class="count"><?= $counts[$status] ?></span>
    </a>
    <?php endforeach; ?>
</div>

<?php if (!$tickets): ?>
<div class="card empty">
    <h2>Nothing here</h2>
    <p><?= $filter === '' ? 'No tickets have been filed yet.' : 'No tickets with the status "' . e(status_label($filter)) . '".' ?></p>
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
                <th>Created</th>
                <th>Updated</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tickets as $t): ?>
            <tr>
                <td class="cell-mono"><?= e(ticket_no((int) $t['id'])) ?></td>
                <td><a class="row-title" href="ticket_view.php?id=<?= (int) $t['id'] ?>"><?= e($t['title']) ?></a></td>
                <?php if ($isAdmin): ?><td><?= e($t['creator']) ?></td><?php endif; ?>
                <td><?= $t['assignee'] !== null ? e($t['assignee']) : '<span class="field-hint" style="margin:0;">—</span>' ?></td>
                <td><?= priority_badge($t['priority']) ?></td>
                <td><?= status_badge($t['status']) ?></td>
                <td class="cell-mono"><?= e(format_date($t['created_at'])) ?></td>
                <td class="cell-mono"><?= e(format_date($t['updated_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
