<?php
// public/index.php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/db.php';

ensure_session();

$q     = trim($_GET['q'] ?? '');
$page  = max(1, (int) ($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

if ($q !== '') {
    $countStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM movies WHERE title LIKE :q"
    );
    $countStmt->execute([':q' => "%{$q}%"]);
    $total = (int) $countStmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT m.id, m.title, m.release_year, m.poster_path,
                ROUND(AVG(r.score), 1) AS avg_rating,
                COUNT(r.id)            AS total_ratings
         FROM movies m
         LEFT JOIN ratings r ON r.movie_id = m.id
         WHERE m.title LIKE :q
         GROUP BY m.id
         ORDER BY m.release_year DESC
         LIMIT :lim OFFSET :off"
    );
    $stmt->bindValue(':q',   "%{$q}%", PDO::PARAM_STR);
    $stmt->bindValue(':lim', $limit,   PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset,  PDO::PARAM_INT);
    $stmt->execute();
} else {
    $countStmt = $pdo->query("SELECT COUNT(*) FROM movies");
    $total     = (int) $countStmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT m.id, m.title, m.release_year, m.poster_path,
                ROUND(AVG(r.score), 1) AS avg_rating,
                COUNT(r.id)            AS total_ratings
         FROM movies m
         LEFT JOIN ratings r ON r.movie_id = m.id
         GROUP BY m.id
         ORDER BY m.release_year DESC
         LIMIT :lim OFFSET :off"
    );
    $stmt->bindValue(':lim', $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
}

$movies    = $stmt->fetchAll();
$totalPages = (int) ceil($total / $limit);

$pageTitle = $q ? 'Search: ' . $q : 'Home';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">
        <?php if ($q): ?>
            Search results for <em class="text-primary"><?= e($q) ?></em>
            <small class="text-muted fs-6">(<?= $total ?> found)</small>
        <?php else: ?>
            <i class="bi bi-collection-play"></i> All Movies
        <?php endif; ?>
    </h4>
</div>

<?php if (empty($movies)): ?>
    <div class="alert alert-info">No movies found<?= $q ? ' for that search.' : '.' ?></div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
        <?php foreach ($movies as $m): ?>
            <div class="col">
                <a href="/public/movie.php?id=<?= (int)$m['id'] ?>" class="text-decoration-none text-dark">
                    <div class="card h-100 shadow-sm movie-card">
                        <?php if ($m['poster_path'] && file_exists(__DIR__ . '/' . $m['poster_path'])): ?>
                            <img src="/public/<?= e($m['poster_path']) ?>"
                                class="card-img-top"
                                alt="<?= e($m['title']) ?> poster">
                        <?php else: ?>
                            <div class="poster-placeholder">
                                <i class="bi bi-film"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-title fw-semibold mb-1"><?= e($m['title']) ?></h6>
                            <p class="text-muted small mb-2"><?= e($m['release_year'] ?? '—') ?></p>
                            <div class="mt-auto d-flex align-items-center gap-2">
                                <?php if ($m['avg_rating']): ?>
                                    <span class="score-badge">
                                        <i class="bi bi-star-fill"></i>
                                        <?= e($m['avg_rating']) ?>
                                    </span>
                                    <span class="text-muted small"><?= (int)$m['total_ratings'] ?> rating<?= $m['total_ratings'] != 1 ? 's' : '' ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">No ratings yet</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="mt-5" aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link"
                            href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>">
                            <?= $p ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>