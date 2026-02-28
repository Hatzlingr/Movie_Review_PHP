<?php
// auth/login.php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/db.php';

ensure_session();

if (is_logged_in()) {
    redirect('/index.php');
}

$error = '';
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password']   ?? '';

    // Rate limiting: maks 5 percobaan per 10 menit
    $attempts = $_SESSION['login_attempts']     ?? 0;
    $lastTry  = $_SESSION['login_last_attempt'] ?? 0;

    if (time() - $lastTry > 600) {
        $attempts = 0; // reset jika sudah lebih dari 10 menit
    }

    if ($login === '' || $password === '') {
        $error = 'Please enter your username/email and password.';
    } elseif ($attempts >= 5) {
        $wait  = 600 - (time() - $lastTry);
        $error = 'Too many failed attempts. Try again in ' . $wait . ' seconds.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT * FROM users WHERE username = :l OR email = :l LIMIT 1"
        );
        $stmt->execute([':l' => $login]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['login_attempts']     = $attempts + 1;
            $_SESSION['login_last_attempt'] = time();
            $error = 'Invalid credentials. Please try again.';
        } else {
            unset($_SESSION['login_attempts'], $_SESSION['login_last_attempt']);
            login_user($user);
            flash_set('success', 'Welcome back, ' . $user['username'] . '!');
            redirect('/index.php');
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/../app/views/partials/header.php';
require_once __DIR__ . '/../app/views/partials/navbar.php';
?>
<main class="container mt-5 pt-5 pb-5">
    <div class="row justify-content-center">
        <div class="col-md-5">

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h4 class="card-title fw-bold mb-4"><i class="bi bi-box-arrow-in-right"></i> Log In</h4>

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2"><?= e($error) ?></div>
                    <?php endif; ?>

                    <form method="post" novalidate>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Username or Email</label>
                            <input type="text" name="login" class="form-control"
                                value="<?= e($login) ?>" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Log In</button>
                    </form>

                    <hr>
                    <p class="text-center mb-0">Don't have an account?
                        <a href="/auth/register.php">Register</a>
                    </p>
                </div>
            </div>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>