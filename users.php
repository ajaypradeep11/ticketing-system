<?php
require_once __DIR__ . '/includes/auth.php';

$me = require_admin();

$errors = [];
$name   = '';
$email  = '';
$role   = 'employee';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name     = trim($_POST['name'] ?? '');
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 'employee';

        if ($name === '') {
            $errors[] = 'Enter the person\'s name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Password needs at least 6 characters.';
        }
        if (!in_array($role, ['admin', 'employee'], true)) {
            $role = 'employee';
        }

        if (!$errors) {
            $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ((int) $stmt->fetchColumn() > 0) {
                $errors[] = 'An account with that email already exists.';
            }
        }

        if (!$errors) {
            $stmt = db()->prepare(
                'INSERT INTO users (name, email, password_hash, role, created_at)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, now()]);

            flash_set('Account for ' . $name . ' created.');
            header('Location: users.php');
            exit;
        }
    }

    if ($action === 'toggle') {
        $targetId = (int) ($_POST['id'] ?? 0);

        if ($targetId === (int) $me['id']) {
            flash_set('You can\'t deactivate your own account.', 'error');
        } else {
            $stmt = db()->prepare('UPDATE users SET active = 1 - active WHERE id = ?');
            $stmt->execute([$targetId]);
            flash_set('Account updated.');
        }
        header('Location: users.php');
        exit;
    }
}

$users = db()->query(
    'SELECT u.*, (SELECT COUNT(*) FROM tickets t WHERE t.user_id = u.id) AS ticket_count
     FROM users u ORDER BY u.created_at'
)->fetchAll();

$pageTitle = 'Users';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <div class="eyebrow">Admin</div>
        <h1>Users</h1>
    </div>
</div>

<div class="card" style="max-width: 640px; margin-bottom: 24px;">
    <div class="eyebrow" style="margin-bottom: 12px;">Add an account</div>

    <?php foreach ($errors as $error): ?>
    <div class="flash flash-error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <div class="form-row">
            <div>
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="<?= e($name) ?>" required>
            </div>
            <div>
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" minlength="6" required>
                <div class="field-hint">At least 6 characters. Share it with them privately.</div>
            </div>
            <div>
                <label for="role">Role</label>
                <select id="role" name="role">
                    <option value="employee" <?= $role === 'employee' ? 'selected' : '' ?>>Employee</option>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
        </div>
        <div>
            <button type="submit" class="btn btn-primary">Create account</button>
        </div>
    </form>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Tickets</th>
                <th>Joined</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><span class="row-title"><?= e($u['name']) ?></span><?= (int) $u['id'] === (int) $me['id'] ? ' <span class="field-hint" style="display:inline;">(you)</span>' : '' ?></td>
                <td class="cell-mono"><?= e($u['email']) ?></td>
                <td><span class="role-tag role-<?= e($u['role']) ?>"><?= e($u['role']) ?></span></td>
                <td class="cell-mono"><?= (int) $u['ticket_count'] ?></td>
                <td class="cell-mono"><?= e(format_date($u['created_at'])) ?></td>
                <td>
                    <?php if ($u['active']): ?>
                    <span class="badge st-resolved">Active</span>
                    <?php else: ?>
                    <span class="badge st-closed">Deactivated</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display: flex; gap: 6px; justify-content: flex-end;">
                        <a class="btn btn-sm" href="user_password.php?id=<?= (int) $u['id'] ?>">Reset password</a>
                        <?php if ((int) $u['id'] !== (int) $me['id']): ?>
                        <form method="post" style="display: inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                            <?php if ($u['active']): ?>
                            <button type="submit" class="btn btn-sm btn-danger-ghost">Deactivate</button>
                            <?php else: ?>
                            <button type="submit" class="btn btn-sm">Reactivate</button>
                            <?php endif; ?>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
