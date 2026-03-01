<?php
// app/views/partials/header_admin.php
declare(strict_types=1);
require_once __DIR__ . '/../../helpers/functions.php';
require_once __DIR__ . '/../../helpers/flash.php';
require_once __DIR__ . '/../../helpers/auth.php';
ensure_session();
$_current_user = current_user();

$_admin_links = [
    ['href' => '/admin/dashboard.php',       'icon' => 'bi-speedometer2',  'label' => 'Dashboard'],
    ['href' => '/admin/movies/index.php',    'icon' => 'bi-camera-video',  'label' => 'Movies'],
    ['href' => '/admin/genres/index.php',    'icon' => 'bi-tags',          'label' => 'Genres'],
    ['href' => '/admin/actors/index.php',    'icon' => 'bi-person-video3', 'label' => 'Actors'],
    ['href' => '/admin/directors/index.php', 'icon' => 'bi-megaphone',     'label' => 'Directors'],
    ['href' => '/admin/users/index.php',     'icon' => 'bi-people',        'label' => 'Users'],
];
$_current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title><?= e($pageTitle ?? 'Admin') ?> &mdash; MovieReview</title>
    <!-- SB Admin (Bootstrap 5) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin@7.0.7/dist/css/styles.css" />
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
</head>

<body class="sb-nav-fixed">

    <!-- ===== Top Navbar ===== -->
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <!-- Brand -->
        <a class="navbar-brand ps-3 fw-bold" href="/admin/dashboard.php">
            <i class="bi bi-film me-2"></i>MovieReview
        </a>
        <!-- Sidebar toggle -->
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0 text-white"
            id="sidebarToggle" type="button">
            <i class="bi bi-list fs-4"></i>
        </button>
        <!-- Right nav -->
        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button"
                    id="navbarDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-1"></i>
                    <?= e($_current_user['username'] ?? 'Admin') ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="navbarDropdown">
                    <li>
                        <a class="dropdown-item" href="/index.php">
                            <i class="bi bi-house me-2"></i>Back to Site
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li>
                        <a class="dropdown-item text-danger" href="/auth/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </nav><!-- /sb-topnav -->

    <div id="layoutSidenav">

        <!-- ===== Sidebar ===== -->
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <div class="sb-sidenav-menu-heading">Admin Panel</div>
                        <?php foreach ($_admin_links as $_link):
                            $_active = (rtrim($_current_path, '/') === rtrim($_link['href'], '/'))
                                ? 'active' : '';
                        ?>
                            <a class="nav-link <?= $_active ?>" href="<?= e($_link['href']) ?>">
                                <div class="sb-nav-link-icon">
                                    <i class="bi <?= e($_link['icon']) ?>"></i>
                                </div>
                                <?= e($_link['label']) ?>
                            </a>
                        <?php endforeach; ?>

                        <div class="sb-sidenav-menu-heading">Site</div>
                        <a class="nav-link" href="/index.php">
                            <div class="sb-nav-link-icon"><i class="bi bi-arrow-left-circle"></i></div>
                            Back to Site
                        </a>
                    </div>
                </div>
                <div class="sb-sidenav-footer">
                    <div class="small">Logged in as:</div>
                    <strong><?= e($_current_user['username'] ?? 'Admin') ?></strong>
                </div>
            </nav>
        </div><!-- /layoutSidenav_nav -->

        <!-- ===== Main Content ===== -->
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4 pt-4">

                    <?php flash_render(); ?>