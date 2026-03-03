<?php
// admin/dashboard.php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/db.php';

ensure_session();
require_admin();

// --- Stat counts ---
$counts = [];
foreach (['movies' => 'movies', 'users' => 'users', 'reviews' => 'reviews', 'ratings' => 'ratings'] as $key => $table) {
    $counts[$key] = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
}

// --- Recent movies ---
$recentMovies = $pdo->query(
    "SELECT id, title, release_year, created_at FROM movies ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

// --- Recent users ---
$recentUsers = $pdo->query(
    "SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

// --- Top rated movies ---
$topRated = $pdo->query(
    "SELECT m.id, m.title, ROUND(AVG(r.score),1) AS avg_score, COUNT(r.id) AS total
     FROM movies m JOIN ratings r ON r.movie_id = m.id
     GROUP BY m.id HAVING total >= 1
     ORDER BY avg_score DESC, total DESC LIMIT 5"
)->fetchAll();

// --- Most reviewed movies ---
$mostReviewed = $pdo->query(
    "SELECT m.id, m.title, COUNT(rv.id) AS total_reviews
     FROM movies m JOIN reviews rv ON rv.movie_id = m.id
     GROUP BY m.id ORDER BY total_reviews DESC LIMIT 5"
)->fetchAll();

// --- Recent reviews ---
$recentReviews = $pdo->query(
    "SELECT rv.id, rv.review_text, rv.created_at,
            u.username, m.id AS movie_id, m.title AS movie_title
     FROM reviews rv
     JOIN users u  ON u.id  = rv.user_id
     JOIN movies m ON m.id  = rv.movie_id
     ORDER BY rv.created_at DESC LIMIT 5"
)->fetchAll();

// --- Rating distribution (1-5) ---
$ratingDist = [];
$distRows = $pdo->query(
    "SELECT score, COUNT(*) AS total FROM ratings GROUP BY score ORDER BY score"
)->fetchAll();
$maxDist = 1;
foreach ($distRows as $row) {
    $ratingDist[(int)$row['score']] = (int)$row['total'];
    if ((int)$row['total'] > $maxDist) $maxDist = (int)$row['total'];
}
for ($s = 1; $s <= 5; $s++) {
    $ratingDist[$s] = $ratingDist[$s] ?? 0;
}

// --- Additional stats ---
$avgRating = $pdo->query(
    "SELECT ROUND(AVG(score), 2) AS avg FROM ratings"
)->fetch()['avg'] ?? 0;

$totalWatchlist = $pdo->query(
    "SELECT COUNT(*) FROM watchlists"
)->fetchColumn();

$adminCount = $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'admin'"
)->fetchColumn();

$userEngagement = $pdo->query(
    "SELECT ROUND(COUNT(rv.id) / COUNT(DISTINCT u.id), 2) AS avg_reviews_per_user
     FROM users u
     LEFT JOIN reviews rv ON rv.user_id = u.id"
)->fetch()['avg_reviews_per_user'] ?? 0;

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../app/views/partials/header_admin.php';
?>

<h4 class="fw-bold mb-4"><i class="bi bi-speedometer2"></i> Dashboard</h4>

<!-- ===== Stat Cards ===== -->
<div class="row g-3 mb-4">
    <?php
    $statCards = [
        ['label' => 'Movies',  'count' => $counts['movies'],  'icon' => 'bi-camera-video',   'color' => 'primary', 'href' => '/admin/movies/index.php'],
        ['label' => 'Users',   'count' => $counts['users'],   'icon' => 'bi-people',          'color' => 'success', 'href' => '/admin/users/index.php'],
        ['label' => 'Reviews', 'count' => $counts['reviews'], 'icon' => 'bi-chat-left-text',  'color' => 'info',    'href' => '#'],
        ['label' => 'Ratings', 'count' => $counts['ratings'], 'icon' => 'bi-star',            'color' => 'warning', 'href' => '#'],
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

<!-- ===== Additional Stats ===== -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-1">Avg Rating</p>
                        <h3 class="fw-bold mb-0"><?= number_format((float)$avgRating, 2) ?></h3>
                        <small class="text-muted">out of 5</small>
                    </div>
                    <i class="bi bi-star-fill fs-2 text-warning opacity-75"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-1">Watchlist Items</p>
                        <h3 class="fw-bold mb-0"><?= (int)$totalWatchlist ?></h3>
                        <small class="text-muted">from all users</small>
                    </div>
                    <i class="bi bi-bookmark-check fs-2 text-primary opacity-75"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-1">Admin Users</p>
                        <h3 class="fw-bold mb-0"><?= (int)$adminCount ?></h3>
                        <small class="text-muted">moderators</small>
                    </div>
                    <i class="bi bi-shield-lock fs-2 text-danger opacity-75"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-1">Avg Reviews/User</p>
                        <h3 class="fw-bold mb-0"><?= number_format((float)$userEngagement, 2) ?></h3>
                        <small class="text-muted">engagement</small>
                    </div>
                    <i class="bi bi-chat-dots fs-2 text-info opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== Row 1: Recent Movies + Recent Users ===== -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header fw-semibold">
                <i class="bi bi-clock-history me-1"></i> Recent Movies
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
                <?php if (empty($recentMovies)): ?>
                    <li class="list-group-item text-muted small">No movies yet.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header fw-semibold">
                <i class="bi bi-person-plus me-1"></i> Recent Users
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($recentUsers as $u): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <?= e($u['username']) ?>
                            <span class="badge bg-<?= $u['role'] === 'admin' ? 'danger' : 'secondary' ?> ms-1"><?= e($u['role']) ?></span>
                        </span>
                        <small class="text-muted"><?= e(substr($u['created_at'], 0, 10)) ?></small>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($recentUsers)): ?>
                    <li class="list-group-item text-muted small">No users yet.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<!-- ===== Row 2: Top Rated + Most Reviewed ===== -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header fw-semibold">
                <i class="bi bi-trophy me-1 text-warning"></i> Top Rated Movies
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($topRated as $i => $m): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <span class="text-muted me-2">#<?= $i + 1 ?></span>
                            <a href="/admin/movies/edit.php?id=<?= (int)$m['id'] ?>" class="text-decoration-none"><?= e($m['title']) ?></a>
                        </span>
                        <span>
                            <span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i> <?= e($m['avg_score']) ?></span>
                            <small class="text-muted ms-1">(<?= (int)$m['total'] ?>)</small>
                        </span>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($topRated)): ?>
                    <li class="list-group-item text-muted small">No ratings yet.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header fw-semibold">
                <i class="bi bi-chat-left-text me-1 text-info"></i> Most Reviewed Movies
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($mostReviewed as $i => $m): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <span class="text-muted me-2">#<?= $i + 1 ?></span>
                            <a href="/admin/movies/edit.php?id=<?= (int)$m['id'] ?>" class="text-decoration-none"><?= e($m['title']) ?></a>
                        </span>
                        <span class="badge bg-info text-dark"><?= (int)$m['total_reviews'] ?> reviews</span>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($mostReviewed)): ?>
                    <li class="list-group-item text-muted small">No reviews yet.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<!-- ===== Row 3: Recent Reviews + Rating Distribution ===== -->
<div class="row g-4 mb-4">
    <div class="col-md-7">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header fw-semibold">
                <i class="bi bi-chat-quote me-1"></i> Recent Reviews
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($recentReviews as $rv): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="fw-semibold"><?= e($rv['username']) ?></span>
                                <span class="text-muted mx-1">on</span>
                                <a href="/admin/movies/edit.php?id=<?= (int)$rv['movie_id'] ?>" class="text-decoration-none"><?= e($rv['movie_title']) ?></a>
                            </div>
                            <small class="text-muted text-nowrap ms-2"><?= e(substr($rv['created_at'], 0, 10)) ?></small>
                        </div>
                        <p class="text-muted small mb-0 mt-1" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%">
                            <?= e($rv['review_text']) ?>
                        </p>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($recentReviews)): ?>
                    <li class="list-group-item text-muted small">No reviews yet.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header fw-semibold">
                <i class="bi bi-bar-chart me-1"></i> Rating Distribution
            </div>
            <div class="card-body">
                <?php if ($counts['ratings'] === 0): ?>
                    <p class="text-muted small mb-0">No ratings yet.</p>
                <?php else: ?>
                    <?php for ($s = 5; $s >= 1; $s--): ?>
                        <?php
                        $count = $ratingDist[$s];
                        $pct   = $maxDist > 0 ? round($count / $maxDist * 100) : 0;
                        $colors = [1 => 'danger', 2 => 'warning', 3 => 'info', 4 => 'primary', 5 => 'success'];
                        ?>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="text-nowrap small fw-semibold" style="width:30px"><?= $s ?> <i class="bi bi-star-fill text-warning small"></i></span>
                            <div class="progress flex-grow-1" style="height:18px">
                                <div class="progress-bar bg-<?= $colors[$s] ?>"
                                    style="width:<?= $pct ?>%"
                                    title="<?= $count ?> ratings">
                                </div>
                            </div>
                            <span class="text-muted small" style="width:30px;text-align:right"><?= $count ?></span>
                        </div>
                    <?php endfor; ?>
                    <p class="text-muted small mb-0 mt-2 text-end">Total: <?= $counts['ratings'] ?> ratings</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../app/views/partials/footer_admin.php'; ?>
