<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? AND active = 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        header('Location: dashboard.php');
        exit;
    }

    $error = 'That email and password don\'t match an active account.';
}

// Show the seeded credentials only while the admin is the sole account.
$onlySeedAdmin = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn() === 1;

$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>

<div class="login-wrap">
    <div class="login-brand">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 9V6a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v3a3 3 0 0 0 0 6v3a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-3a3 3 0 0 0 0-6Z"/>
            <path d="M14 5.5v2.5M14 11v2M14 16v2.5" stroke-linecap="round"/>
        </svg>
        TicketDesk
    </div>

    <div class="card">
        <div class="eyebrow">Sign in</div>
        <h1 style="font-size: 19px; letter-spacing: -0.01em;">Welcome back</h1>

        <div class="stub-divider"></div>

        <?php if ($error): ?>
        <div class="flash flash-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="form-grid">
            <?= csrf_field() ?>
            <div>
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" required autofocus>
            </div>
            <div>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Log in</button>
        </form>

        <?php if ($onlySeedAdmin): ?>
        <div class="login-note">
            First run — sign in with <code>admin@example.com</code> / <code>admin123</code>,
            then create your team's accounts from the Users page.
        </div>
        <?php endif; ?>

        <div class="login-note">
            New here? <a href="register.php">Sign up as an admin</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
