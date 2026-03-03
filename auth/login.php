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

$e_general  = '';
$e_login    = '';
$e_password = '';
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login      = trim($_POST['login'] ?? '');   // untuk ditampilkan kembali di form
    $loginEmail = strtolower($login);            // untuk dicek ke kolom email
    $password   = $_POST['password'] ?? '';

    // Rate limiting: maks 5 percobaan per 10 menit
    $attempts = $_SESSION['login_attempts']     ?? 0;
    $lastTry  = $_SESSION['login_last_attempt'] ?? 0;

    if (time() - $lastTry > 600) {
        $attempts = 0; // reset jika sudah lebih dari 10 menit
    }

    if ($login === '') {
        $e_login = 'Username or email is required.';
    }
    if ($password === '') {
        $e_password = 'Password is required.';
    }

    if (!$e_login && !$e_password && $attempts >= 5) {
        $wait       = 600 - (time() - $lastTry);
        $e_general  = 'Too many failed attempts. Try again in ' . $wait . ' seconds.';
    } elseif (!$e_login && !$e_password) {
        $stmt = $pdo->prepare(
            "SELECT id, username, email, role, password_hash, profile_photo
             FROM users WHERE username = :l1 OR email = :l2 LIMIT 1"
        );
        $stmt->execute([':l1' => $login, ':l2' => $loginEmail]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['login_attempts']     = $attempts + 1;
            $_SESSION['login_last_attempt'] = time();
            $e_general = 'Invalid credentials. Please try again.';
        } else {
            unset($_SESSION['login_attempts'], $_SESSION['login_last_attempt']);
            login_user($user);
            flash_set('success', 'Welcome back, ' . $user['username'] . '!');
            redirect('/index.php');
        }
    }
}

$pageTitle = 'Login';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'ELITISRIPIW') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/public/assets/css/base.css">
    <link rel="stylesheet" href="/public/assets/css/navbar.css">
    <link rel="stylesheet" href="/public/assets/css/hero.css">
    <link rel="stylesheet" href="/public/assets/css/home.css">
    <link rel="stylesheet" href="/public/assets/css/dark-bs.css">
    <link rel="stylesheet" href="/public/assets/css/responsive.css">
    <?= $extraHeadHtml ?? '' ?>
</head>

<body class="<?= e($bodyClass ?? '') ?>">
    <main class="container mt-5 pt-5 pb-5">
        <div class="row justify-content-center">
            <div class="col-md-5">

                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="card-title fw-bold mb-4"><i class="bi bi-box-arrow-in-right"></i> Log In</h4>

                        <?php if ($e_general): ?>
                            <div class="alert alert-danger py-2"><?= e($e_general) ?></div>
                        <?php endif; ?>

                        <?php flash_render(); ?>
                        <form method="post" novalidate>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Username or Email</label>
                                <input type="text" name="login"
                                    class="form-control <?= $e_login ? 'is-invalid' : '' ?>"
                                    value="<?= e($login) ?>" required autofocus>
                                <?php if ($e_login): ?>
                                    <div class="invalid-feedback"><?= e($e_login) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Password</label>
                                <input type="password" name="password"
                                    class="form-control <?= $e_password ? 'is-invalid' : '' ?>"
                                    required>
                                <?php if ($e_password): ?>
                                    <div class="invalid-feedback"><?= e($e_password) ?></div>
                                <?php endif; ?>
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
</body>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>