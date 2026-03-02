<?php
// public/search.php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/pagination.php';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/repositories/movieRepository.php';

ensure_session();

$repo    = new MovieRepository($pdo);
$q       = trim($_GET['q']     ?? '');
$genre   = trim($_GET['genre'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset  = ($page - 1) * $perPage;

$result    = $repo->searchMovies($q, $genre, $perPage, $offset);
$movies    = $result['data'];
$total     = $result['total'];
$pages     = $total > 0 ? (int) ceil($total / $perPage) : 1;
$allGenres = $repo->getAllGenres();
$user      = current_user();

$pageTitle     = $q !== '' ? 'Search: ' . $q : 'Search Movies';
$bodyClass     = 'page-search';
$extraHeadHtml = '<link rel="stylesheet" href="/public/assets/css/search.css">';

require_once __DIR__ . '/../app/views/partials/header.php';
require_once __DIR__ . '/../app/views/partials/navbar.php';
?>

<div class="container mt-3"><?php flash_render(); ?></div>

<!-- ── Main content ───────────────────────────────────────────── -->
<main class="container py-4">

    <!-- Page heading -->
    <div class="mb-4">
        <?php if ($q !== '' || $genre !== ''): ?>
            <h2 class="h5 fw-semibold mb-0 search-heading">
                <i class="fa-solid fa-magnifying-glass me-2 heading-icon"></i>
                <?= $q !== '' ? 'Results for <em class="search-em">' . e($q) . '</em>' : '' ?>
                <?= $genre !== '' ? ($q !== '' ? ' &middot; ' : '') . 'Genre: <em class="search-em">' . e($genre) . '</em>' : '' ?>
            </h2>
        <?php else: ?>
            <h2 class="h5 fw-semibold mb-0 search-heading">
                <i class="fa-solid fa-film me-2 heading-icon"></i>All Movies
            </h2>
        <?php endif; ?>
    </div>

    <!-- Genre filter pills -->
    <div class="genre-pills">
        <?php $qPart = $q !== '' ? 'q=' . urlencode($q) . '&' : ''; ?>
        <a href="/public/search.php<?= $q !== '' ? '?q=' . urlencode($q) : '' ?>"
            class="genre-pill <?= $genre === '' ? 'active' : '' ?>">All</a>
        <?php foreach ($allGenres as $g): ?>
            <a href="/public/search.php?<?= $qPart ?>genre=<?= urlencode($g['name']) ?>"
                class="genre-pill <?= $genre === $g['name'] ? 'active' : '' ?>">
                <?= e($g['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Results meta -->
    <p class="results-meta">
        <?php if ($q !== '' || $genre !== ''): ?>
            Found <strong><?= $total ?></strong> movie<?= $total !== 1 ? 's' : '' ?>
            <?= $q !== '' ? ' for <strong>&ldquo;' . e($q) . '&rdquo;</strong>' : '' ?>
            <?= $genre !== '' ? ' in <strong>' . e($genre) . '</strong>' : '' ?>
        <?php else: ?>
            Showing all <strong><?= $total ?></strong> movie<?= $total !== 1 ? 's' : '' ?>
        <?php endif; ?>
    </p>

    <!-- Movie grid -->
    <?php if (empty($movies)): ?>
        <div class="search-empty">
            <i class="fa-regular fa-face-frown"></i>
            <p>No movies found<?= $q !== '' ? ' for &ldquo;' . e($q) . '&rdquo;' : '' ?><?= $genre !== '' ? ' in ' . e($genre) : '' ?>.</p>
            <?php if ($q !== '' || $genre !== ''): ?>
                <a href="/public/search.php" class="mc-btn-detail d-inline-block mt-3 px-4">
                    <i class="fa-solid fa-xmark me-1"></i> Clear filters
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>

        <?php
        $backUrl = e('/public/search.php?' . http_build_query(array_filter(['q' => $q, 'genre' => $genre, 'page' => $page > 1 ? $page : null])));
        ?>
        <div class="movie-grid">
            <?php foreach ($movies as $m): ?>
                <div class="mc-card">
                    <a href="/public/movie.php?id=<?= (int)$m['id'] ?>" class="mc-poster">
                        <img src="<?= e(imageUrl($m['poster_path'], 'poster')) ?>"
                            alt="<?= e($m['title']) ?>"
                            loading="lazy">
                    </a>
                    <div class="mc-body">
                        <p class="mc-title"><?= e($m['title']) ?></p>
                        <p class="mc-year"><?= e($m['release_year'] ?? '—') ?></p>
                        <p class="mc-rating">
                            <?php if ($m['avg_rating']): ?>
                                <i class="fa-solid fa-star"></i>
                                <strong><?= e($m['avg_rating']) ?></strong>
                                <span class="mc-rating-count">(<?= (int)$m['total_ratings'] ?>)</span>
                            <?php else: ?>
                                <span class="mc-no-rate">Not rated</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="mc-actions">
                        <a href="/public/movie.php?id=<?= (int)$m['id'] ?>" class="mc-btn-detail">
                            See Details
                        </a>
                        <?php if ($user): ?>
                            <form method="post" action="/action/watchlist_save.php" class="mc-wl-form">
                                <input type="hidden" name="movie_id" value="<?= (int)$m['id'] ?>">
                                <input type="hidden" name="status" value="plan_to_watch">
                                <input type="hidden" name="redirect" value="<?= $backUrl ?>">
                                <button type="submit" class="mc-btn-wl">
                                    <i class="fa-regular fa-bookmark"></i> Watchlist
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="/auth/login.php" class="mc-btn-wl">
                                <i class="fa-regular fa-bookmark"></i> Watchlist
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php
        $bq      = array_filter(['q' => $q, 'genre' => $genre]);
        $baseUrl = '/public/search.php?' . ($bq ? http_build_query($bq) . '&' : '');
        render_pagination($page, $pages, $baseUrl);
        ?>

    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>