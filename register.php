<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$name   = '';
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $name     = trim($_POST['name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($name === '') {
        $errors[] = 'Enter your name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password needs at least 6 characters.';
    } elseif ($password !== $confirm) {
        $errors[] = 'Passwords don\'t match.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ((int) $stmt->fetchColumn() > 0) {
            $errors[] = 'An account with that email already exists. Try logging in.';
        }
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'INSERT INTO users (name, email, password_hash, role, created_at)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), 'admin', now()]);

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) db()->lastInsertId();

        flash_set('Welcome, ' . $name . '! Your admin account is ready.');
        header('Location: dashboard.php');
        exit;
    }
}

$pageTitle = 'Sign up';
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
        <div class="eyebrow">Sign up</div>
        <h1 style="font-size: 19px; letter-spacing: -0.01em;">Create an admin account</h1>

        <div class="stub-divider"></div>

        <?php foreach ($errors as $error): ?>
        <div class="flash flash-error"><?= e($error) ?></div>
        <?php endforeach; ?>

        <form method="post" class="form-grid">
            <?= csrf_field() ?>
            <div>
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="<?= e($name) ?>" required autofocus>
            </div>
            <div>
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" required>
            </div>
            <div>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" minlength="6" required>
                <div class="field-hint">At least 6 characters.</div>
            </div>
            <div>
                <label for="confirm">Confirm password</label>
                <input type="password" id="confirm" name="confirm" minlength="6" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Create account</button>
        </form>

        <div class="login-note">
            Already have an account? <a href="login.php">Log in</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
