<?php
require_once __DIR__ . '/includes/auth.php';

$me      = require_login();
$isAdmin = $me['role'] === 'admin';

$id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT * FROM tickets WHERE id = ?');
$stmt->execute([$id]);
$ticket = $stmt->fetch();

// Only the creator or an admin may edit a ticket.
if (!$ticket || (!$isAdmin && (int) $ticket['user_id'] !== (int) $me['id'])) {
    flash_set('That ticket doesn\'t exist or isn\'t yours to edit.', 'error');
    header('Location: tickets.php');
    exit;
}

$errors      = [];
$title       = $ticket['title'];
$description = $ticket['description'];
$priority    = $ticket['priority'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority    = $_POST['priority'] ?? $ticket['priority'];

    if ($title === '') {
        $errors[] = 'Give the ticket a title.';
    } elseif (mb_strlen($title) > 150) {
        $errors[] = 'Keep the title under 150 characters.';
    }
    if ($description === '') {
        $errors[] = 'Describe the issue so it can be worked on.';
    }
    if (!in_array($priority, TICKET_PRIORITIES, true)) {
        $priority = $ticket['priority'];
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'UPDATE tickets SET title = ?, description = ?, priority = ?, updated_at = ? WHERE id = ?'
        );
        $stmt->execute([$title, $description, $priority, now(), $id]);

        flash_set('Ticket ' . ticket_no($id) . ' updated.');
        header('Location: ticket_view.php?id=' . $id);
        exit;
    }
}

$pageTitle = 'Edit ' . ticket_no($id);
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <div class="eyebrow"><a href="tickets.php">Tickets</a> / <a href="ticket_view.php?id=<?= $id ?>"><?= e(ticket_no($id)) ?></a> / Edit</div>
        <h1>Edit ticket</h1>
    </div>
</div>

<div class="card" style="max-width: 640px;">
    <?php foreach ($errors as $error): ?>
    <div class="flash flash-error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" class="form-grid">
        <?= csrf_field() ?>
        <div>
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="<?= e($title) ?>" maxlength="150" required autofocus>
        </div>
        <div>
            <label for="description">Description</label>
            <textarea id="description" name="description" required><?= e($description) ?></textarea>
        </div>
        <div>
            <label for="priority">Priority</label>
            <select id="priority" name="priority">
                <?php foreach (TICKET_PRIORITIES as $p): ?>
                <option value="<?= e($p) ?>" <?= $priority === $p ? 'selected' : '' ?>><?= e(priority_label($p)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-primary">Save changes</button>
            <a class="btn" href="ticket_view.php?id=<?= $id ?>">Cancel</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
