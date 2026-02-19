<?php
// admin/dashboard.php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/db.php';

ensure_session();
require_admin();

$counts = [];
foreach (['movies' => 'movies', 'users' => 'users', 'reviews' => 'reviews', 'ratings' => 'ratings'] as $key => $table) {
    $counts[$key] = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
}

$recentMovies = $pdo->query(
    "SELECT id, title, release_year, created_at FROM movies ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

$recentUsers = $pdo->query(
    "SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<div class="d-flex gap-4 align-items-start">
    <?php require_once __DIR__ . '/../app/views/partials/sidebar_admin.php'; ?>

    <div class="flex-grow-1">
        <h4 class="fw-bold mb-4"><i class="bi bi-speedometer2"></i> Dashboard</h4>

        <!-- Stat cards -->
        <div class="row g-3 mb-5">
            <?php
            $statCards = [
                ['label' => 'Movies',  'count' => $counts['movies'],  'icon' => 'bi-camera-video', 'color' => 'primary',   'href' => '/admin/movies/index.php'],
                ['label' => 'Users',   'count' => $counts['users'],   'icon' => 'bi-people',        'color' => 'success',   'href' => '/admin/users/index.php'],
                ['label' => 'Reviews', 'count' => $counts['reviews'], 'icon' => 'bi-chat-left-text', 'color' => 'info',      'href' => '#'],
                ['label' => 'Ratings', 'count' => $counts['ratings'], 'icon' => 'bi-star',          'color' => 'warning',   'href' => '#'],
            ];
            foreach ($statCards as $c): ?>
                <div class="col-6 col-md-3">
                    <a href="<?= e($c['href']) ?>" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100 text-center py-3">
                            <div class="card-body">
                                <i class="bi <?= e($c['icon']) ?> fs-2 text-<?= e($c['color']) ?>"></i>
                                <h2 class="fw-bold mb-0 mt-2"><?= $c['count'] ?></h2>
                                <p class="text-muted mb-0"><?= e($c['label']) ?></p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-4">
            <!-- Recent movies -->
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-semibold">
                        <i class="bi bi-camera-video"></i> Recent Movies
                        <a href="/admin/movies/create.php" class="btn btn-sm btn-primary float-end">+ Add</a>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentMovies as $m): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="/admin/movies/edit.php?id=<?= (int)$m['id'] ?>" class="text-decoration-none">
                                    <?= e($m['title']) ?>
                                    <small class="text-muted">(<?= e($m['release_year'] ?? '—') ?>)</small>
                                </a>
                                <small class="text-muted"><?= e(substr($m['created_at'], 0, 10)) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Recent users -->
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-semibold">
                        <i class="bi bi-people"></i> Recent Users
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentUsers as $u): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>
                                    <?= e($u['username']) ?>
                                    <span class="badge bg-<?= $u['role'] === 'admin' ? 'danger' : 'secondary' ?> ms-1">
                                        <?= e($u['role']) ?>
                                    </span>
                                </span>
                                <small class="text-muted"><?= e(substr($u['created_at'], 0, 10)) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div><!-- /flex-grow-1 -->
</div><!-- /d-flex -->

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>