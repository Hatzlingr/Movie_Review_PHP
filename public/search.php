<?php
// public/search.php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/auth.php';
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

$pageTitle     = $q !== '' ? 'Search: ' . htmlspecialchars($q, ENT_QUOTES, 'UTF-8') : 'Search Movies';
$bodyClass     = 'page-search';
$extraHeadHtml = <<<'CSS'
<style>
/* Override absolute navbar so it sits in normal flow (no hero behind it) */
.page-search .navbar-custom {
    position: relative;
    background: rgba(22, 18, 36, 0.98);
    border-bottom: 1px solid rgba(255, 255, 255, 0.07);
}
/* Genre pills */
.genre-pills{display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1.5rem;}
.genre-pill{padding:.32rem .9rem;border-radius:999px;font-size:.8rem;font-weight:500;border:1.5px solid rgba(255,255,255,.12);color:#bbb;text-decoration:none;transition:all .14s;background:transparent;}
.genre-pill:hover{border-color:#7d4de0;color:#fff;text-decoration:none;}
.genre-pill.active{background:#6c3fc6;border-color:#6c3fc6;color:#fff;}
/* Results meta */
.results-meta{font-size:.87rem;color:#777;margin-bottom:1.5rem;}
.results-meta strong{color:#bbb;}
/* Movie grid */
.movie-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:1.4rem;margin-bottom:2.5rem;}
@media(max-width:576px){.movie-grid{grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:1rem;}}
.mc-card{background:#131321;border:1px solid rgba(255,255,255,.06);border-radius:10px;overflow:hidden;display:flex;flex-direction:column;transition:transform .2s,box-shadow .2s;}
.mc-card:hover{transform:translateY(-4px);box-shadow:0 14px 32px rgba(0,0,0,.55);}
.mc-poster{aspect-ratio:2/3;overflow:hidden;background:#111;display:block;text-decoration:none;}
.mc-poster img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .3s;}
.mc-card:hover .mc-poster img{transform:scale(1.05);}
.mc-body{padding:.75rem .85rem .6rem;flex:1;display:flex;flex-direction:column;gap:.3rem;}
.mc-title{font-size:.86rem;font-weight:600;color:#eee;line-height:1.3;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;margin:0;}
.mc-year{font-size:.74rem;color:#666;margin:0;}
.mc-rating{font-size:.76rem;color:#f5c518;margin-top:auto;padding-top:.3rem;}
.mc-rating .mc-no-rate{color:#444;}
.mc-actions{padding:0 .85rem .85rem;display:flex;flex-direction:column;gap:.4rem;}
.mc-btn-detail{display:block;text-align:center;background:#6c3fc6;color:#fff;text-decoration:none;border-radius:6px;padding:.45rem;font-size:.8rem;font-weight:500;transition:background .15s;}
.mc-btn-detail:hover{background:#7d4de0;color:#fff;text-decoration:none;}
.mc-btn-wl{display:block;text-align:center;border:1px solid rgba(255,255,255,.13);color:#999;background:transparent;border-radius:6px;padding:.42rem;font-size:.78rem;text-decoration:none;transition:all .15s;cursor:pointer;width:100%;}
.mc-btn-wl:hover{border-color:#7d4de0;color:#fff;background:rgba(109,63,198,.12);text-decoration:none;}
/* Empty state */
.search-empty{text-align:center;padding:4rem 1rem;color:#555;}
.search-empty i{font-size:3.5rem;margin-bottom:1rem;display:block;}
/* Pagination */
.sp-pagination{display:flex;justify-content:center;gap:.45rem;flex-wrap:wrap;}
.sp-pg{min-width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:7px;font-size:.83rem;text-decoration:none;border:1.5px solid rgba(255,255,255,.1);color:#999;transition:all .15s;}
.sp-pg:hover{border-color:#7d4de0;color:#fff;text-decoration:none;}
.sp-pg.sp-pg-on{background:#6c3fc6;border-color:#6c3fc6;color:#fff;}
.sp-pg.sp-pg-off{opacity:.3;pointer-events:none;}
</style>
CSS;

require_once __DIR__ . '/../app/views/partials/header.php';
require_once __DIR__ . '/../app/views/partials/navbar.php';
?>

<?php foreach (flash_get() as $flash): ?>
<div class="container mt-3">
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endforeach; ?>

<!-- ── Main content ───────────────────────────────────────────── -->
<main class="container py-4">

    <!-- Page heading -->
    <div class="mb-4">
        <?php if ($q !== '' || $genre !== ''): ?>
            <h2 class="h5 fw-semibold mb-0" style="color:#ddd;">
                <i class="fa-solid fa-magnifying-glass me-2" style="color:#8866dd;"></i>
                <?= $q !== '' ? 'Results for <em style="color:#a880ff;font-style:normal;">'.e($q).'</em>' : '' ?>
                <?= $genre !== '' ? ($q !== '' ? ' &middot; ' : '').'Genre: <em style="color:#a880ff;font-style:normal;">'.e($genre).'</em>' : '' ?>
            </h2>
        <?php else: ?>
            <h2 class="h5 fw-semibold mb-0" style="color:#ddd;">
                <i class="fa-solid fa-film me-2" style="color:#8866dd;"></i>All Movies
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

        <div class="movie-grid">
            <?php foreach ($movies as $m): ?>
            <div class="mc-card">
                <a href="/public/movie.php?id=<?= (int)$m['id'] ?>" class="mc-poster">
                    <img src="<?= e(imageUrl($m['poster_path'], 'poster')) ?>"
                         alt="<?= e($m['title']) ?>"
                         loading="lazy"
                         onerror="this.src='https://placehold.co/220x330/2f2543/ffffff?text=No+Poster';this.onerror=null;">
                </a>
                <div class="mc-body">
                    <p class="mc-title"><?= e($m['title']) ?></p>
                    <p class="mc-year"><?= e($m['release_year'] ?? '—') ?></p>
                    <p class="mc-rating">
                        <?php if ($m['avg_rating']): ?>
                            <i class="fa-solid fa-star"></i>
                            <strong><?= e($m['avg_rating']) ?></strong>
                            <span style="color:#555;font-size:.7rem;">(<?= (int)$m['total_ratings'] ?>)</span>
                        <?php else: ?>
                            <span class="mc-no-rate">Not rated</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="mc-actions">
                    <a href="/public/movie.php?id=<?= (int)$m['id'] ?>" class="mc-btn-detail">
                        See Details
                    </a>
                    <?php
                    $backUrl = e('/public/search.php?' . http_build_query(array_filter(['q' => $q, 'genre' => $genre, 'page' => $page > 1 ? $page : null])));
                    ?>
                    <?php if ($user): ?>
                        <form method="post" action="/action/watchlist_save.php" style="margin:0;">
                            <input type="hidden" name="movie_id" value="<?= (int)$m['id'] ?>">
                            <input type="hidden" name="status"   value="plan_to_watch">
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
        <?php if ($pages > 1):
            $bq      = array_filter(['q' => $q, 'genre' => $genre]);
            $baseUrl = '/public/search.php?' . ($bq ? http_build_query($bq) . '&' : '');
        ?>
        <nav class="sp-pagination mt-2" aria-label="Pages">
            <a href="<?= $page > 1    ? $baseUrl . 'page=' . ($page - 1) : '#' ?>"
               class="sp-pg <?= $page <= 1    ? 'sp-pg-off' : '' ?>">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <?php for ($p = 1; $p <= $pages; $p++):
                $gap = abs($p - $page);
                if ($p === 1 || $p === $pages || $gap <= 2): ?>
                    <a href="<?= $baseUrl . 'page=' . $p ?>"
                       class="sp-pg <?= $p === $page ? 'sp-pg-on' : '' ?>"><?= $p ?></a>
                <?php elseif ($gap === 3): ?>
                    <span class="sp-pg" style="pointer-events:none;border-color:transparent;">…</span>
                <?php endif;
            endfor; ?>
            <a href="<?= $page < $pages ? $baseUrl . 'page=' . ($page + 1) : '#' ?>"
               class="sp-pg <?= $page >= $pages ? 'sp-pg-off' : '' ?>">
                <i class="fa-solid fa-chevron-right"></i>
            </a>
        </nav>
        <?php endif; ?>

    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>