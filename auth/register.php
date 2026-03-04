<?php
// auth/register.php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/db.php';

ensure_session();

const ROLE_USER = 'user';

if (is_logged_in()) {
    redirect('/index.php');
}

$e_username = '';
$e_email    = '';
$e_password = '';
$e_confirm  = '';
$e_general  = '';
$username   = '';
$email      = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';

    // Validate username
    if ($username === '') {
        $e_username = 'Username is required.';
    } elseif (strlen($username) < 3 || strlen($username) > 32) {
        $e_username = 'Username must be 3–32 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9_-]*[a-zA-Z0-9])?$/', $username)) {
        $e_username = 'Only letters, numbers, _ and - allowed. Cannot start or end with a symbol.';
    } elseif (preg_match('/__|--/', $username)) {
        $e_username = 'Cannot contain consecutive underscores or hyphens.';
    }

    // Validate email
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $e_email = 'A valid email address is required.';
    }

    // Validate password
    if (strlen($password) < 8) {
        $e_password = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $e_password = 'Must contain at least one uppercase letter and one number.';
    }

    // Validate confirm
    if ($password !== $confirm) {
        $e_confirm = 'Passwords do not match.';
    }

    // Check uniqueness only if no field errors
    if (!$e_username && !$e_email && !$e_password && !$e_confirm) {
        $check = $pdo->prepare(
            "SELECT username, email FROM users WHERE username = :u OR email = :e LIMIT 1"
        );
        $check->execute([':u' => $username, ':e' => $email]);
        $taken = $check->fetch();
        if ($taken) {
            if ($taken['username'] === $username) $e_username = 'Username is already taken.';
            if ($taken['email']    === $email)    $e_email    = 'Email is already taken.';
        }
    }

    if (!$e_username && !$e_email && !$e_password && !$e_confirm) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins  = $pdo->prepare(
            "INSERT INTO users (username, email, password_hash, role)
             VALUES (:u, :e, :h, :r)"
        );
        try {
            $ins->execute([':u' => $username, ':e' => $email, ':h' => $hash, ':r' => ROLE_USER]);
        } catch (\PDOException $e) {
            error_log('Register INSERT failed: ' . $e->getMessage());
            $e_general = 'Registration failed. Please try again.';
        }

        if (!$e_general) {
            $newId = (int) $pdo->lastInsertId();
            if ($newId === 0) {
                $e_general = 'Registration failed. Please try again.';
            } else {
                flash_set('success', 'Account created! Please log in.');
                redirect('/auth/login.php');
            }
        }
    }
}

$pageTitle = 'Register';
?>

<?php require_once __DIR__ . '/../app/views/partials/header.php'; ?>

<main class="container mt-5 pt-5 pb-5">
        <div class="row justify-content-center">
            <div class="col-md-5">

                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="card-title fw-bold mb-4"><i class="bi bi-person-plus"></i> Create Account</h4>

                        <?php if ($e_general): ?>
                            <div class="alert alert-danger"><?= e($e_general) ?></div>
                        <?php endif; ?>

                        <form method="post" novalidate>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Username</label>
                                <input type="text" name="username"
                                    class="form-control <?= $e_username ? 'is-invalid' : '' ?>"
                                    value="<?= e($username) ?>" required autofocus maxlength="32">
                                <?php if ($e_username): ?>
                                    <div class="invalid-feedback"><?= e($e_username) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" name="email"
                                    class="form-control <?= $e_email ? 'is-invalid' : '' ?>"
                                    value="<?= e($email) ?>" required maxlength="255">
                                <?php if ($e_email): ?>
                                    <div class="invalid-feedback"><?= e($e_email) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Password</label>
                                <input type="password" name="password"
                                    class="form-control <?= $e_password ? 'is-invalid' : '' ?>"
                                    required minlength="8">
                                <?php if ($e_password): ?>
                                    <div class="invalid-feedback"><?= e($e_password) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Confirm Password</label>
                                <input type="password" name="confirm"
                                    class="form-control <?= $e_confirm ? 'is-invalid' : '' ?>"
                                    required>
                                <?php if ($e_confirm): ?>
                                    <div class="invalid-feedback"><?= e($e_confirm) ?></div>
                                <?php endif; ?>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Register</button>
                        </form>

                        <hr>
                        <p class="text-center mb-0">Already have an account?
                            <a href="/auth/login.php">Log in</a>
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>