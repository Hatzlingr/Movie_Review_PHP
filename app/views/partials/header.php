<?php
// app/views/partials/header.php
declare(strict_types=1);
require_once __DIR__ . '/../../helpers/functions.php';
require_once __DIR__ . '/../../helpers/flash.php';
require_once __DIR__ . '/../../helpers/auth.php';
ensure_session();
$_current_user = current_user();
$_q = e($_GET['q'] ?? '');
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'MovieReview') ?></title>
    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- App CSS -->
    <link rel="stylesheet" href="/public/assets/css/app.css">
</head>

<body>

    <!-- ===== SITE HEADER ===== -->
    <header class="site-header">
        <div class="site-header__overlay"></div>
        <div class="container position-relative py-4">
            <div class="d-flex flex-column flex-md-row align-items-center gap-3">

                <!-- Brand -->
                <a class="site-header__brand me-md-4 text-decoration-none" href="/public/index.php">
                    <i class="bi bi-film"></i> MovieReview
                </a>

                <!-- Search -->
                <form class="site-header__search flex-grow-1" method="get" action="/public/index.php" role="search">
                    <div class="search-pill">
                        <i class="bi bi-search search-pill__icon"></i>
                        <input
                            class="search-pill__input"
                            type="search"
                            name="q"
                            value="<?= $_q ?>"
                            placeholder="Search movies…"
                            aria-label="Search movies">
                        <button class="search-pill__btn" type="submit">Go</button>
                    </div>
                </form>

                <!-- Nav links -->
                <nav class="site-header__nav d-flex align-items-center gap-2">
                    <?php if ($_current_user): ?>
                        <a class="btn btn-sm btn-outline-light" href="/me/watchlist.php">
                            <i class="bi bi-bookmark-star"></i> Watchlist
                        </a>
                        <a class="btn btn-sm btn-outline-light" href="/me/reviews.php">
                            <i class="bi bi-chat-left-text"></i> My Reviews
                        </a>
                        <?php if ($_current_user['role'] === 'admin'): ?>
                            <a class="btn btn-sm btn-warning" href="/admin/dashboard.php">
                                <i class="bi bi-speedometer2"></i> Admin
                            </a>
                        <?php endif; ?>
                        <a class="btn btn-sm btn-danger" href="/auth/logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    <?php else: ?>
                        <a class="btn btn-sm btn-outline-light" href="/auth/login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                        <a class="btn btn-sm btn-primary" href="/auth/register.php">
                            <i class="bi bi-person-plus"></i> Register
                        </a>
                    <?php endif; ?>
                </nav>

            </div>
        </div>
    </header>
    <!-- ===== END HEADER ===== -->

    <main class="container my-4">
        <?php flash_render(); ?>