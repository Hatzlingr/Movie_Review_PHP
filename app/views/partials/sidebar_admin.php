<?php
// app/views/partials/sidebar_admin.php
$_admin_links = [
    ['href' => '/admin/dashboard.php',          'icon' => 'bi-speedometer2',  'label' => 'Dashboard'],
    ['href' => '/admin/movies/index.php',        'icon' => 'bi-camera-video',  'label' => 'Movies'],
    ['href' => '/admin/genres/index.php',        'icon' => 'bi-tags',          'label' => 'Genres'],
    ['href' => '/admin/actors/index.php',        'icon' => 'bi-person-video3', 'label' => 'Actors'],
    ['href' => '/admin/directors/index.php',     'icon' => 'bi-megaphone',     'label' => 'Directors'],
    ['href' => '/admin/users/index.php',         'icon' => 'bi-people',        'label' => 'Users'],
];
$_current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<div class="admin-sidebar d-flex flex-column p-3 bg-dark text-white" style="min-width:220px;width:220px;">
    <p class="text-uppercase fw-bold text-secondary small mb-3 px-2">Admin Panel</p>
    <ul class="nav flex-column gap-1">
        <?php foreach ($_admin_links as $link): ?>
            <?php $active = (rtrim($_current_path, '/') === rtrim($link['href'], '/')) ? 'active' : ''; ?>
            <li class="nav-item">
                <a href="<?= e($link['href']) ?>"
                    class="nav-link text-white rounded px-2 py-2 d-flex align-items-center gap-2 <?= $active ?>">
                    <i class="bi <?= e($link['icon']) ?>"></i>
                    <?= e($link['label']) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <hr class="border-secondary">
    <a href="/public/index.php" class="nav-link text-secondary px-2 d-flex align-items-center gap-2">
        <i class="bi bi-arrow-left-circle"></i> Back to Site
    </a>
</div>