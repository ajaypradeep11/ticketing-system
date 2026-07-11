<?php
require_once __DIR__ . '/includes/auth.php';

$me = require_login();

$errors      = [];
$title       = '';
$description = '';
$priority    = 'medium';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority    = $_POST['priority'] ?? 'medium';

    if ($title === '') {
        $errors[] = 'Give the ticket a title.';
    } elseif (mb_strlen($title) > 150) {
        $errors[] = 'Keep the title under 150 characters.';
    }
    if ($description === '') {
        $errors[] = 'Describe the issue so it can be worked on.';
    }
    if (!in_array($priority, TICKET_PRIORITIES, true)) {
        $priority = 'medium';
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'INSERT INTO tickets (user_id, title, description, priority, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$me['id'], $title, $description, $priority, 'open', now(), now()]);
        $id = (int) db()->lastInsertId();

        flash_set('Ticket ' . ticket_no($id) . ' created.');
        header('Location: ticket_view.php?id=' . $id);
        exit;
    }
}

$pageTitle = 'New ticket';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <div class="eyebrow">File an issue</div>
        <h1>New ticket</h1>
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
            <input type="text" id="title" name="title" value="<?= e($title) ?>"
                   maxlength="150" placeholder="Short summary of the issue" required autofocus>
        </div>
        <div>
            <label for="description">Description</label>
            <textarea id="description" name="description" required
                      placeholder="What happened? What did you expect? Steps to reproduce help."><?= e($description) ?></textarea>
        </div>
        <div>
            <label for="priority">Priority</label>
            <select id="priority" name="priority">
                <?php foreach (TICKET_PRIORITIES as $p): ?>
                <option value="<?= e($p) ?>" <?= $priority === $p ? 'selected' : '' ?>><?= e(priority_label($p)) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="field-hint">High is for anything blocking work right now.</div>
        </div>
        <div>
            <button type="submit" class="btn btn-primary">Create ticket</button>
            <a class="btn" href="tickets.php">Cancel</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
