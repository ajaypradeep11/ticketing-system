<?php
require_once __DIR__ . '/includes/auth.php';

$me = require_login();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $current = $_POST['current'] ?? '';
    $new     = $_POST['new'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (!password_verify($current, $me['password_hash'])) {
        $errors[] = 'Your current password is incorrect.';
    }
    if (strlen($new) < 6) {
        $errors[] = 'New password needs at least 6 characters.';
    } elseif ($new !== $confirm) {
        $errors[] = 'New passwords don\'t match.';
    }

    if (!$errors) {
        $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $me['id']]);

        flash_set('Password updated.');
        header('Location: account.php');
        exit;
    }
}

$pageTitle = 'Account';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <div class="eyebrow">Your account</div>
        <h1><?= e($me['name']) ?></h1>
    </div>
</div>

<div class="card" style="max-width: 480px;">
    <div class="meta-list" style="margin-bottom: 4px;">
        <div>
            <div class="eyebrow">Email</div>
            <div class="meta-value mono"><?= e($me['email']) ?></div>
        </div>
        <div>
            <div class="eyebrow">Role</div>
            <div class="meta-value"><span class="role-tag role-<?= e($me['role']) ?>"><?= e($me['role']) ?></span></div>
        </div>
    </div>

    <div class="stub-divider"></div>

    <div class="eyebrow" style="margin-bottom: 12px;">Change password</div>

    <?php foreach ($errors as $error): ?>
    <div class="flash flash-error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" class="form-grid">
        <?= csrf_field() ?>
        <div>
            <label for="current">Current password</label>
            <input type="password" id="current" name="current" required autofocus>
        </div>
        <div>
            <label for="new">New password</label>
            <input type="password" id="new" name="new" minlength="6" required>
            <div class="field-hint">At least 6 characters.</div>
        </div>
        <div>
            <label for="confirm">Confirm new password</label>
            <input type="password" id="confirm" name="confirm" minlength="6" required>
        </div>
        <button type="submit" class="btn btn-primary">Update password</button>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
