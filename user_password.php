<?php
require_once __DIR__ . '/includes/auth.php';

$me = require_admin();

$id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    flash_set('That account doesn\'t exist.', 'error');
    header('Location: users.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $new     = $_POST['new'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (strlen($new) < 6) {
        $errors[] = 'Password needs at least 6 characters.';
    } elseif ($new !== $confirm) {
        $errors[] = 'Passwords don\'t match.';
    }

    if (!$errors) {
        $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $id]);

        flash_set('Password for ' . $user['name'] . ' updated.');
        header('Location: users.php');
        exit;
    }
}

$pageTitle = 'Reset password';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <div class="eyebrow"><a href="users.php">Users</a> / Reset password</div>
        <h1><?= e($user['name']) ?></h1>
    </div>
</div>

<div class="card" style="max-width: 480px;">
    <div class="eyebrow" style="margin-bottom: 12px;">Set a new password for <?= e($user['email']) ?></div>

    <?php foreach ($errors as $error): ?>
    <div class="flash flash-error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" class="form-grid">
        <?= csrf_field() ?>
        <div>
            <label for="new">New password</label>
            <input type="password" id="new" name="new" minlength="6" required autofocus>
            <div class="field-hint">At least 6 characters. Share it with them privately.</div>
        </div>
        <div>
            <label for="confirm">Confirm new password</label>
            <input type="password" id="confirm" name="confirm" minlength="6" required>
        </div>
        <div>
            <button type="submit" class="btn btn-primary">Update password</button>
            <a class="btn" href="users.php">Cancel</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
