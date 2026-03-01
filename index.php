<?php
// index.php
declare(strict_types=1);

require_once __DIR__ . '/app/config/app.php';
require_once __DIR__ . '/app/helpers/functions.php';
require_once __DIR__ . '/app/helpers/flash.php';
require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/config/db.php';
require_once __DIR__ . '/app/repositories/movieRepository.php';

ensure_session();

$repo = new MovieRepository($pdo);

// ── Hero: best movie this month ──────────────────────────────────────────────
$currentMonth = (int) date('n');
$currentYear  = (int) date('Y');
$heroMovie    = $repo->getBestMovieByMonth($currentMonth, $currentYear);
$monthName    = strtoupper(date('F', mktime(0, 0, 0, $currentMonth, 1)));

// ── Movie list ───────────────────────────────────────────────────────────────
$q            = trim($_GET['q'] ?? '');
$moviesResult = $repo->getMovies($q, 12, 0);
$movies       = $moviesResult['data'];

// ── Watchlist (logged-in only) ───────────────────────────────────────────────
$currentUser = current_user();
$watchlist   = [];
if ($currentUser) {
    $watchlist = $repo->getUserWatchlist($currentUser['id']);
}

// ── Most liked reviews ───────────────────────────────────────────────────────
$reviews = $repo->getMostLikedReviews(5);

// ── Page title & header ──────────────────────────────────────────────────────
$pageTitle = $q ? 'Search: ' . $q : 'ELITISRIPIW';
require_once __DIR__ . '/app/views/partials/header.php';
require_once __DIR__ . '/app/views/partials/navbar.php';
?>



<section class="hero">
    <?php
    $heroBanner = !empty($heroMovie['banner_path'])
        ? '/public/' . ltrim($heroMovie['banner_path'], '/')
        : 'https://image.tmdb.org/t/p/original/gKkl37BQuKTanygYQG1pyYgLVgf.jpg';
    ?>
    <img class="hero-bg" src="<?= e($heroBanner) ?>" alt="Banner">
    <div class="hero-overlay"></div>
    <div class="container">
        <h5 class="text-center hero-title-top">BEST REVIEW ON <?= $monthName ?></h5>

        <?php if ($heroMovie): ?>
            <div class="row justify-content-center align-items-center mt-4 g-3">
                <div class="col-6 col-md-3 text-center text-md-end">
                    <img src="<?= e(posterUrl($heroMovie['poster_path'])) ?>"
                        alt="<?= e($heroMovie['title']) ?>"
                        class="hero-poster">
                </div>
                <div class="col-12 col-md-5 ps-md-4 text-center text-md-start">
                    <h2 class="fw-bold mb-2"><?= e(strtoupper($heroMovie['title'])) ?></h2>
                    <p class="hero-desc mb-3"><?= e($heroMovie['description'] ?? '') ?></p>
                    <div class="d-flex align-items-center justify-content-center justify-content-md-start">
                        <?php if ($heroMovie['avg_rating']): ?>
                            <span class="display-5 fw-bold me-3"><?= e($heroMovie['avg_rating']) ?></span>
                            <div style="color:#f1c40f; font-size:1.5rem; letter-spacing:2px;">
                                <?= renderStars((float)$heroMovie['avg_rating']) ?>
                            </div>
                        <?php else: ?>
                            <span class="text-muted fs-5">No ratings yet</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-3 col-md-1 text-center d-none d-md-block">
                    <h1 class="fw-bold" style="font-size:5rem;">1</h1>
                </div>
            </div>
        <?php else: ?>
            <div class="row justify-content-center align-items-center mt-4">
                <div class="col-md-6 text-center">
                    <p class="text-muted">No ratings recorded this month yet.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ===== MAIN CONTENT ===== -->
<div class="container mt-5 pb-5">
    <?php flash_render(); ?>
    <!-- Movie List -->
    <h5 class="section-header">Movie List<?= $q ? ' — <em>' . e($q) . '</em>' : '' ?></h5>
    <div class="scroll-wrapper mb-5">
        <button class="nav-arrow-list" onclick="scrollSection(this, 1)"><i class="fa-solid fa-chevron-right"></i></button>
        <div class="horizontal-scroll">
            <?php if (empty($movies)): ?>
                <p class="text-muted">No movies found.</p>
            <?php else: ?>
                <?php foreach ($movies as $m): ?>
                    <div class="movie-card">
                        <img src="<?= e(posterUrl($m['poster_path'])) ?>"
                            alt="<?= e($m['title']) ?>">
                        <div class="movie-title"><?= e(strtoupper($m['title'])) ?> (<?= e($m['release_year'] ?? '?') ?>)</div>
                        <div class="movie-rating">
                            <?php if ($m['avg_rating']): ?>
                                <?= renderStars((float)$m['avg_rating']) ?>
                                <span class="ms-1"><?= e($m['avg_rating']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </div>
                        <a href="/public/movie.php?id=<?= (int)$m['id'] ?>" class="btn-card text-center text-decoration-none d-block mb-1">See Details</a>
                        <?php if ($currentUser): ?>
                            <form method="post" action="/action/watchlist_save.php">
                                <input type="hidden" name="movie_id" value="<?= (int)$m['id'] ?>">
                                <input type="hidden" name="status" value="plan_to_watch">
                                <button type="submit" class="btn-card">Add To Watch List</button>
                            </form>
                        <?php else: ?>
                            <a href="/auth/login.php" class="btn-card text-center text-decoration-none d-block">Add To Watch List</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- My Watch List -->
    <?php if ($currentUser): ?>
        <h5 class="section-header">My Watch List</h5>
        <div class="scroll-wrapper mb-5">
            <div class="horizontal-scroll">
                <?php foreach ($watchlist as $w): ?>
                    <div class="movie-card">
                        <img src="<?= e(posterUrl($w['poster_path'])) ?>"
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
                <a href="/index.php" class="add-more-card text-decoration-none">
                    <i class="fa-solid fa-circle-plus" style="font-size:3rem;color:#7a669f;"></i>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Other Reviews -->
    <h5 class="section-header" style="text-transform:lowercase;">other reviews</h5>
    <div class="scroll-wrapper">
        <button class="nav-arrow-list" onclick="scrollSection(this, 1)"><i class="fa-solid fa-chevron-right"></i></button>
        <div class="horizontal-scroll">
            <?php if (empty($reviews)): ?>
                <p class="text-muted">No reviews yet.</p>
            <?php else: ?>
                <?php foreach ($reviews as $rv): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <div class="reviewer-avatar"></div>
                                <div>
                                    <p class="reviewer-name"><?= e($rv['username']) ?></p>
                                    <p class="review-date"><?= e(date('n/j/Y', strtotime($rv['created_at']))) ?></p>
                                </div>
                            </div>
                            <div class="review-score">
                                <?php if ($rv['user_score']): ?>
                                    <?= renderStars((float)$rv['user_score']) ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="review-text"><?= e($rv['review_text']) ?></p>
                        <div class="review-actions">
                            <span><i class="fa-regular fa-thumbs-up me-1"></i><?= (int)$rv['like_count'] ?></span>
                            <span><i class="fa-regular fa-comment me-1"></i><a href="/public/movie.php?id=<?= (int)$rv['movie_id'] ?>" class="text-decoration-none" style="color:var(--text-muted)"><?= e($rv['title']) ?></a></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>
<?php require_once __DIR__ . '/app/views/partials/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function scrollSection(btn, dir) {
        const wrapper = btn.closest('.scroll-wrapper').querySelector('.horizontal-scroll');
        wrapper.scrollBy({
            left: dir * 500,
            behavior: 'smooth'
        });
    }
</script>
</body>

</html>