<?php
require_once __DIR__ . '/includes/auth.php';

$me      = require_login();
$isAdmin = $me['role'] === 'admin';

$id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare(
    'SELECT t.*, u.name AS creator, u.email AS creator_email, a.name AS assignee
     FROM tickets t
     JOIN users u ON u.id = t.user_id
     LEFT JOIN users a ON a.id = t.assigned_to
     WHERE t.id = ?'
);
$stmt->execute([$id]);
$ticket = $stmt->fetch();

// Employees may only open tickets they created or are assigned to.
$mine = $ticket && ((int) $ticket['user_id'] === (int) $me['id']
    || (int) ($ticket['assigned_to'] ?? 0) === (int) $me['id']);
if (!$ticket || (!$isAdmin && !$mine)) {
    flash_set('That ticket doesn\'t exist or isn\'t yours to view.', 'error');
    header('Location: tickets.php');
    exit;
}

$canEdit = $isAdmin || (int) $ticket['user_id'] === (int) $me['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    // Anyone who can view the ticket can leave a progress comment.
    if ($action === 'comment') {
        $body = trim($_POST['body'] ?? '');
        if ($body !== '') {
            $stmt = db()->prepare(
                'INSERT INTO comments (ticket_id, user_id, body, created_at) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$id, $me['id'], $body, now()]);
            db()->prepare('UPDATE tickets SET updated_at = ? WHERE id = ?')->execute([now(), $id]);

            flash_set('Comment added.');
        } else {
            flash_set('Write a comment before posting.', 'error');
        }
        header('Location: ticket_view.php?id=' . $id);
        exit;
    }

    // Employees may change the status; admins also set priority and assignee.
    if ($action === 'update') {
        $status = $_POST['status'] ?? '';

        if ($isAdmin) {
            $priority = $_POST['priority'] ?? '';
            $assigned = (int) ($_POST['assigned_to'] ?? 0);

            $assignedValid = $assigned === 0;
            if (!$assignedValid) {
                $check = db()->prepare('SELECT COUNT(*) FROM users WHERE id = ? AND active = 1');
                $check->execute([$assigned]);
                $assignedValid = (int) $check->fetchColumn() > 0;
            }

            if (in_array($status, TICKET_STATUSES, true) && in_array($priority, TICKET_PRIORITIES, true) && $assignedValid) {
                $stmt = db()->prepare(
                    'UPDATE tickets SET status = ?, priority = ?, assigned_to = ?, updated_at = ? WHERE id = ?'
                );
                $stmt->execute([$status, $priority, $assigned ?: null, now(), $id]);

                flash_set('Ticket ' . ticket_no($id) . ' updated.');
                header('Location: ticket_view.php?id=' . $id);
                exit;
            }
        } elseif (in_array($status, TICKET_STATUSES, true)) {
            $stmt = db()->prepare('UPDATE tickets SET status = ?, updated_at = ? WHERE id = ?');
            $stmt->execute([$status, now(), $id]);

            flash_set('Ticket ' . ticket_no($id) . ' moved to "' . status_label($status) . '".');
            header('Location: ticket_view.php?id=' . $id);
            exit;
        }
    }
}

$assignableUsers = $isAdmin
    ? db()->query('SELECT id, name FROM users WHERE active = 1 ORDER BY name')->fetchAll()
    : [];

$stmt = db()->prepare(
    'SELECT c.*, u.name AS author, u.role AS author_role
     FROM comments c
     JOIN users u ON u.id = c.user_id
     WHERE c.ticket_id = ?
     ORDER BY c.created_at, c.id'
);
$stmt->execute([$id]);
$comments = $stmt->fetchAll();

$pageTitle = ticket_no($id);
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <div class="eyebrow"><a href="tickets.php">Tickets</a> / <?= e(ticket_no($id)) ?></div>
        <h1><?= e($ticket['title']) ?></h1>
    </div>
    <?php if ($canEdit): ?>
    <a class="btn" href="ticket_edit.php?id=<?= $id ?>">Edit ticket</a>
    <?php endif; ?>
</div>

<div class="ticket-layout">
    <div class="card">
        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <span class="ticket-id"><?= e(ticket_no($id)) ?></span>
            <?= status_badge($ticket['status']) ?>
            <?= priority_badge($ticket['priority']) ?>
        </div>

        <div class="stub-divider"></div>

        <div class="eyebrow" style="margin-bottom: 8px;">Description</div>
        <div class="ticket-desc"><?= e($ticket['description']) ?></div>

        <div class="stub-divider"></div>

        <div class="eyebrow" style="margin-bottom: 12px;">Progress &amp; comments</div>

        <?php if (!$comments): ?>
        <p class="field-hint" style="margin: 0 0 16px;">No comments yet. Updates on this ticket will show up here.</p>
        <?php else: ?>
        <div class="comment-list">
            <?php foreach ($comments as $c): ?>
            <div class="comment">
                <div class="comment-head">
                    <span class="comment-author"><?= e($c['author']) ?></span>
                    <span class="role-tag role-<?= e($c['author_role']) ?>"><?= e($c['author_role']) ?></span>
                    <span class="comment-date mono"><?= e(format_datetime($c['created_at'])) ?></span>
                </div>
                <div class="comment-body"><?= e($c['body']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="post" class="form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="comment">
            <div>
                <label for="body">Add a comment</label>
                <textarea id="body" name="body" required
                          placeholder="Share progress, blockers, or questions about this ticket."></textarea>
            </div>
            <div>
                <button type="submit" class="btn btn-primary">Post comment</button>
            </div>
        </form>
    </div>

    <div>
        <div class="card">
            <div class="meta-list">
                <div>
                    <div class="eyebrow">Created by</div>
                    <div class="meta-value"><?= e($ticket['creator']) ?></div>
                    <div class="field-hint"><?= e($ticket['creator_email']) ?></div>
                </div>
                <div>
                    <div class="eyebrow">Assigned to</div>
                    <div class="meta-value"><?= $ticket['assignee'] ? e($ticket['assignee']) : '<span class="field-hint" style="margin:0;">Unassigned</span>' ?></div>
                </div>
                <div>
                    <div class="eyebrow">Created</div>
                    <div class="meta-value mono"><?= e(format_datetime($ticket['created_at'])) ?></div>
                </div>
                <div>
                    <div class="eyebrow">Last updated</div>
                    <div class="meta-value mono"><?= e(format_datetime($ticket['updated_at'])) ?></div>
                </div>
            </div>
        </div>

        <?php if ($isAdmin): ?>
        <div class="card" style="margin-top: 18px;">
            <div class="eyebrow" style="margin-bottom: 12px;">Update ticket</div>
            <form method="post" class="form-grid">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <div>
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <?php foreach (TICKET_STATUSES as $s): ?>
                        <option value="<?= e($s) ?>" <?= $ticket['status'] === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority">
                        <?php foreach (TICKET_PRIORITIES as $p): ?>
                        <option value="<?= e($p) ?>" <?= $ticket['priority'] === $p ? 'selected' : '' ?>><?= e(priority_label($p)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="assigned_to">Assigned to</label>
                    <select id="assigned_to" name="assigned_to">
                        <option value="0">Unassigned</option>
                        <?php foreach ($assignableUsers as $u): ?>
                        <option value="<?= (int) $u['id'] ?>" <?= (int) ($ticket['assigned_to'] ?? 0) === (int) $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Save changes</button>
            </form>
        </div>
        <?php else: ?>
        <div class="card" style="margin-top: 18px;">
            <div class="eyebrow" style="margin-bottom: 12px;">Update status</div>
            <form method="post" class="form-grid">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <div>
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <?php foreach (TICKET_STATUSES as $s): ?>
                        <option value="<?= e($s) ?>" <?= $ticket['status'] === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Save status</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
