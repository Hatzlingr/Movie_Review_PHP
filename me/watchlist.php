<?php
// me/watchlist.php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/repositories/movieRepository.php';

ensure_session();
require_login();

$userId = current_user()['id'];

$repo = new MovieRepository($pdo);

// ── Watchlist (logged-in only) ───────────────────────────────────────────────
$currentUser = current_user();
$watchlist   = [];
if ($currentUser) {
    $watchlist = $repo->getUserWatchlist($currentUser['id']);
}

?>

<!-- My Watch List -->
    <div class="container ">
        <?php if ($currentUser): ?>
            <h5 class="section-header">My Watch List</h5>
            <div class="scroll-wrapper">
                <div class="horizontal-scroll">
                    <?php foreach ($watchlist as $w): ?>
                        <div class="movie-card">
                            <img src="<?= e(imageUrl($w['poster_path'], 'poster')) ?>"
                                alt="<?= e($w['title']) ?>">
                            <div class="movie-title"><?= e(strtoupper($w['title'])) ?> (<?= e($w['release_year'] ?? '?') ?>)</div>
                            <div class="movie-rating">
                                <?php if ($w['avg_rating']): ?>
                                    <?= renderStars((float)$w['avg_rating']) ?>
                                    <span class="ms-1"><?= e($w['avg_rating']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </div>
                            <a href="/public/movie.php?id=<?= (int)$w['id'] ?>" class="btn-card text-center text-decoration-none d-block mb-1">See Details</a>
                            <?= watchlistStatusBtn($w['status']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>