<?php
// auth/register.php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/db.php';

ensure_session();

if (is_logged_in()) {
    redirect('/public/index.php');
}

$errors   = [];
$username = '';
$email    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';

    if ($username === '') {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) < 3 || strlen($username) > 32) {
        $errors[] = 'Username must be 3–32 characters.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        // Check uniqueness
        $check = $pdo->prepare(
            "SELECT id FROM users WHERE username = :u OR email = :e LIMIT 1"
        );
        $check->execute([':u' => $username, ':e' => $email]);
        if ($check->fetch()) {
            $errors[] = 'Username or email is already taken.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $ins  = $pdo->prepare(
            "INSERT INTO users (username, email, password_hash, role)
             VALUES (:u, :e, :h, 'user')"
        );
        $ins->execute([':u' => $username, ':e' => $email, ':h' => $hash]);

        $newId = (int) $pdo->lastInsertId();
        $row   = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $row->execute([':id' => $newId]);
        login_user($row->fetch());

        flash_set('success', 'Welcome, ' . $username . '!');
        redirect('/public/index.php');
    }
}

$pageTitle = 'Register';
require_once __DIR__ . '/../app/views/partials/header.php';
require_once __DIR__ . '/../app/views/partials/navbar.php';
?>
<main class="container mt-5 pt-5 pb-5">
    <div class="row justify-content-center">
        <div class="col-md-5">

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h4 class="card-title fw-bold mb-4"><i class="bi bi-person-plus"></i> Create Account</h4>

                    <?php foreach ($errors as $err): ?>
                        <div class="alert alert-danger py-2"><?= e($err) ?></div>
                    <?php endforeach; ?>

                    <form method="post" novalidate>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Username</label>
                            <input type="text" name="username" class="form-control"
                                value="<?= e($username) ?>" required autofocus maxlength="32">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control"
                                value="<?= e($email) ?>" required maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password</label>
                            <input type="password" name="password" class="form-control"
                                required minlength="8">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Confirm Password</label>
                            <input type="password" name="confirm" class="form-control" required>
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