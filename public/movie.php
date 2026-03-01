<?php
// public/movie.php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/db.php';

ensure_session();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('/index.php');
}

// ── Fetch movie ──────────────────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT m.*,
            ROUND(AVG(r.score), 1) AS avg_rating,
            COUNT(r.id)            AS total_ratings
     FROM movies m
     LEFT JOIN ratings r ON r.movie_id = m.id
     WHERE m.id = :id
     GROUP BY m.id"
);
$stmt->execute([':id' => $id]);
$movie = $stmt->fetch();
if (!$movie) {
    flash_set('danger', 'Movie not found.');
    redirect('/index.php');
}

// ── Genres ───────────────────────────────────────────────────
$gStmt = $pdo->prepare(
    "SELECT g.name FROM genres g
     JOIN movie_genres mg ON mg.genre_id = g.id
     WHERE mg.movie_id = :id ORDER BY g.name"
);
$gStmt->execute([':id' => $id]);
$genres = $gStmt->fetchAll(PDO::FETCH_COLUMN);

// ── Actors ───────────────────────────────────────────────────
$aStmt = $pdo->prepare(
    "SELECT a.name, a.photo_path, ma.role_name
     FROM actors a
     JOIN movie_actors ma ON ma.actor_id = a.id
     WHERE ma.movie_id = :id ORDER BY a.name"
);
$aStmt->execute([':id' => $id]);
$actors = $aStmt->fetchAll();

// ── Directors ────────────────────────────────────────────────
$dStmt = $pdo->prepare(
    "SELECT d.name FROM directors d
     JOIN movie_directors md ON md.director_id = d.id
     WHERE md.movie_id = :id ORDER BY d.name"
);
$dStmt->execute([':id' => $id]);
$directors = $dStmt->fetchAll(PDO::FETCH_COLUMN);

// ── Reviews (with like counts) ───────────────────────────────
$revStmt = $pdo->prepare(
    "SELECT rv.id, rv.user_id, rv.review_text, rv.created_at,
            u.username, u.profile_photo,
            COUNT(rl.id) AS like_count
     FROM reviews rv
     JOIN users u ON u.id = rv.user_id
     LEFT JOIN review_likes rl ON rl.review_id = rv.id
     WHERE rv.movie_id = :id
     GROUP BY rv.id
     ORDER BY rv.created_at DESC"
);
$revStmt->execute([':id' => $id]);
$reviews = $revStmt->fetchAll();

// ── Current user context ─────────────────────────────────────
$user          = current_user();
$myRating      = null;
$myReview      = null;
$myWatchlist   = null;
$myLikedReviews = [];

if ($user) {
    $uid = $user['id'];

    $rStmt = $pdo->prepare("SELECT score FROM ratings WHERE user_id=:u AND movie_id=:m");
    $rStmt->execute([':u' => $uid, ':m' => $id]);
    $myRating = $rStmt->fetchColumn();

    $rvStmt = $pdo->prepare("SELECT id, review_text FROM reviews WHERE user_id=:u AND movie_id=:m");
    $rvStmt->execute([':u' => $uid, ':m' => $id]);
    $myReview = $rvStmt->fetch() ?: null;

    $wStmt = $pdo->prepare("SELECT status FROM watchlists WHERE user_id=:u AND movie_id=:m");
    $wStmt->execute([':u' => $uid, ':m' => $id]);
    $myWatchlist = $wStmt->fetchColumn() ?: null;

    $lStmt = $pdo->prepare(
        "SELECT review_id FROM review_likes WHERE user_id = :u"
    );
    $lStmt->execute([':u' => $uid]);
    $myLikedReviews = $lStmt->fetchAll(PDO::FETCH_COLUMN);
}

$pageTitle  = e($movie['title']);
$bodyClass  = 'movie-page';
$extraHeadHtml = '<link rel="stylesheet" href="/public/assets/css/movie.css">'
               . '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/star-rating.js@4.3.0/dist/star-rating.min.css">';
require_once __DIR__ . '/../app/views/partials/header.php';
require_once __DIR__ . '/../app/views/partials/navbar.php';

// helper: format duration as "Xh Ym"
$dur = '';
if ($movie['duration_minutes']) {
    $h = (int) floor((int)$movie['duration_minutes'] / 60);
    $m = (int)$movie['duration_minutes'] % 60;
    $dur = ($h ? $h . 'h ' : '') . ($m ? $m . 'm' : '');
}

// helper: resolve banner URL
$bannerUrl = posterUrl($movie['banner_path'] ?? null)
           ?: posterUrl($movie['poster_path'] ?? null);
?>

<main>

<!-- ── FLASH ─────────────────────────────────────────────── -->
<?php foreach (flash_get() as $flash): ?>
    <div class="flash-inner">
        <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
            <?= e($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
<?php endforeach; ?>

<!-- ── HERO BANNER ───────────────────────────────────────── -->
<div class="movie-hero">
    <img src="<?= e($bannerUrl) ?>" alt="<?= e($movie['title']) ?> backdrop"
         onerror="this.style.display='none'">
</div>

<!-- ── MOVIE INFO PANEL ──────────────────────────────────── -->
<div class="movie-info-panel">
    <div class="movie-info-inner">

        <!-- Poster thumbnail -->
        <div class="movie-poster-thumb">
            <?php if ($movie['poster_path']): ?>
                <img src="<?= e(posterUrl($movie['poster_path'])) ?>"
                     alt="<?= e($movie['title']) ?> poster">
            <?php else: ?>
                <div class="poster-placeholder"><i class="fa-solid fa-film"></i></div>
            <?php endif; ?>
        </div>

        <!-- Details -->
        <div class="movie-meta-details">
            <h1 class="movie-title-display">
                <?= e(mb_strtoupper($movie['title'])) ?>
                <?php if ($movie['release_year']): ?>
                    <span style="font-weight:400;font-size:1.1rem;opacity:.7">(<?= e($movie['release_year']) ?>)</span>
                <?php endif; ?>
            </h1>

            <?php if ($movie['release_year'] || $dur): ?>
                <div class="movie-meta-line">
                    <?= e($movie['release_year'] ?? '') ?>
                    <?php if ($movie['release_year'] && $dur): ?>&nbsp;·&nbsp;<?php endif; ?>
                    <?= $dur ? 'Duration : ' . $dur : '' ?>
                </div>
            <?php endif; ?>

            <?php if ($directors): ?>
                <div class="movie-directors-line">
                    <strong>Director<?= count($directors) > 1 ? 's' : '' ?>:</strong>
                    <?= e(implode(', ', $directors)) ?>
                </div>
            <?php endif; ?>

            <?php if ($genres): ?>
                <div class="genres-row">
                    <?php foreach ($genres as $g): ?>
                        <span class="genre-tag"><?= e($g) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($movie['description']): ?>
                <p class="movie-desc-short"><?= e($movie['description']) ?></p>
            <?php endif; ?>

            <!-- Rating row + watchlist button -->
            <div class="rating-row-movie">
                <?php if ($movie['avg_rating']): ?>
                    <div class="avg-rating-display">
                        <span class="avg-score-big"><?= e($movie['avg_rating']) ?></span>
                        <div class="avg-stars-col">
                            <span class="stars-icons"><?= renderStars((float)$movie['avg_rating']) ?></span>
                            <span class="rating-count"><?= (int)$movie['total_ratings'] ?> rating<?= $movie['total_ratings'] != 1 ? 's' : '' ?></span>
                        </div>
                    </div>
                <?php else: ?>
                    <span class="text-muted" style="font-size:.82rem">No ratings yet</span>
                <?php endif; ?>

                <?php if ($user): ?>
                    <?php if ($myWatchlist): ?>
                        <div class="watchlist-form-inline">
                            <form method="post" action="/action/watchlist_save.php" class="d-flex align-items-center gap-2 flex-wrap">
                                <input type="hidden" name="movie_id" value="<?= $id ?>">
                                <input type="hidden" name="redirect" value="/public/movie.php?id=<?= $id ?>">
                                <select name="status" class="watchlist-select-sm">
                                    <?php foreach (['plan_to_watch' => 'Plan to Watch', 'watching' => 'Watching', 'completed' => 'Completed'] as $val => $lbl): ?>
                                        <option value="<?= $val ?>" <?= $myWatchlist === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="watchlist-btn-movie wl-active">
                                    <i class="fa-solid fa-bookmark"></i> Update
                                </button>
                            </form>
                            <a href="/action/watchlist_remove.php?movie_id=<?= $id ?>&redirect=<?= urlencode('/public/movie.php?id=' . $id) ?>"
                               class="watchlist-remove-link"
                               onclick="return confirm('Remove from watchlist?')">
                                <i class="fa-solid fa-trash-can"></i> Remove
                            </a>
                        </div>
                    <?php else: ?>
                        <form method="post" action="/action/watchlist_save.php">
                            <input type="hidden" name="movie_id" value="<?= $id ?>">
                            <input type="hidden" name="redirect" value="/public/movie.php?id=<?= $id ?>">
                            <input type="hidden" name="status" value="plan_to_watch">
                            <button type="submit" class="watchlist-btn-movie">
                                Add To Watch List
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="/auth/login.php" class="watchlist-btn-movie">
                        Add To Watch List
                        <i class="fa-solid fa-plus"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div><!-- /movie-meta-details -->
    </div><!-- /movie-info-inner -->
</div><!-- /movie-info-panel -->

<!-- ── CAST & CREW ───────────────────────────────────────── -->
<?php if ($actors || $directors): ?>
<div class="movie-section">
    <div class="movie-section-title">
        <i class="fa-solid fa-users"></i> Cast &amp; Crew
    </div>
    <div class="cast-scroll-wrap">
        <div class="cast-scroll" id="castScrollRow">

            <!-- Directors first -->
            <?php foreach ($directors as $d): ?>
                <div class="cast-card">
                    <div class="cast-initials">
                        <?= e(mb_strtoupper(mb_substr($d, 0, 2))) ?>
                    </div>
                    <div class="cast-info">
                        <div class="cast-name"><?= e($d) ?></div>
                        <div class="cast-role">Director</div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Actors -->
            <?php foreach ($actors as $a): ?>
                <div class="cast-card">
                    <?php if ($a['photo_path']): ?>
                        <img src="<?= e(posterUrl($a['photo_path'])) ?>" alt="<?= e($a['name']) ?>">
                    <?php else: ?>
                        <div class="cast-initials">
                            <?= e(mb_strtoupper(mb_substr($a['name'], 0, 2))) ?>
                        </div>
                    <?php endif; ?>
                    <div class="cast-info">
                        <div class="cast-name"><?= e($a['name']) ?></div>
                        <?php if ($a['role_name']): ?>
                            <div class="cast-role"><?= e($a['role_name']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        </div><!-- /cast-scroll -->
        <button class="cast-scroll-arrow" onclick="document.getElementById('castScrollRow').scrollBy({left:220,behavior:'smooth'})">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
    </div>
</div>
<?php endif; ?>

<!-- ── REVIEWS LIST ──────────────────────────────────────── -->
<div class="movie-section">
    <div class="movie-section-title">
        <i class="fa-regular fa-comment"></i>
        Reviews
        <span style="font-size:.78rem;font-weight:400;color:var(--text-muted)">(<?= count($reviews) ?>)</span>
    </div>

    <?php if (empty($reviews)): ?>
        <p class="text-muted" style="font-size:.85rem">No reviews yet. Be the first!</p>
    <?php endif; ?>

    <?php foreach ($reviews as $idx => $rev): ?>
        <?php $liked = in_array($rev['id'], $myLikedReviews, true); ?>
        <div class="review-card-movie" style="animation-delay:<?= $idx * 0.07 ?>s">
            <div class="review-header-movie">
                <!-- Reviewer info -->
                <div class="reviewer-row">
                    <div class="reviewer-avatar-circle">
                        <?php if ($rev['profile_photo']): ?>
                            <img src="<?= e(posterUrl($rev['profile_photo'])) ?>" alt="<?= e($rev['username']) ?>">
                        <?php else: ?>
                            <?= e(mb_strtoupper(mb_substr($rev['username'], 0, 1))) ?>
                        <?php endif; ?>
                    </div>
                    <div class="reviewer-meta">
                        <div class="rev-name"><?= e($rev['username']) ?></div>
                        <div class="rev-date"><?= e(date('j/n/Y', strtotime($rev['created_at']))) ?></div>
                    </div>
                </div>
                <!-- Like / Delete actions (top-right) -->
                <div class="review-actions-right">
                    <?php if ($user): ?>
                        <form method="post" action="/action/review_like_toggle.php" style="display:inline">
                            <input type="hidden" name="review_id" value="<?= (int)$rev['id'] ?>">
                            <input type="hidden" name="redirect" value="/public/movie.php?id=<?= $id ?>">
                            <button type="submit" class="like-btn-movie <?= $liked ? 'liked' : '' ?>">
                                <i class="fa-<?= $liked ? 'solid' : 'regular' ?> fa-heart"></i>
                                <?= (int)$rev['like_count'] ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <span class="like-btn-movie" style="cursor:default">
                            <i class="fa-regular fa-heart"></i> <?= (int)$rev['like_count'] ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($user && ($user['id'] === (int)($rev['user_id'] ?? 0) || $user['role'] === 'admin')): ?>
                        <a href="/action/review_delete.php?review_id=<?= (int)$rev['id'] ?>&redirect=<?= urlencode('/public/movie.php?id=' . $id) ?>"
                           class="delete-btn-movie"
                           onclick="return confirm('Delete this review?')">
                            <i class="fa-regular fa-trash-can"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <p class="review-text-body"><?= nl2br(e($rev['review_text'])) ?></p>
        </div>
    <?php endforeach; ?>
</div><!-- /reviews section -->

<!-- ── MY RATING & REVIEW ────────────────────────────────── -->
<div class="my-rating-section">
    <div class="my-rating-inner">
        <?php if ($user): ?>
            <div class="my-rating-label">My Rating</div>
            <div class="my-rating-title-sm"><?= e(mb_strtoupper($movie['title'])) ?> <?php if ($movie['release_year']): ?>(<?= e($movie['release_year']) ?>)<?php endif; ?></div>
            <div class="my-rating-question">What did you think of it?</div>
            <div class="my-rating-hint">Pick a star rating and leave a comment</div>

            <!-- Rating -->
            <div class="star-rating-wrap">
                <form method="post" action="/action/rating_save.php" class="d-flex align-items-center gap-3 flex-wrap">
                    <input type="hidden" name="movie_id" value="<?= $id ?>">
                    <input type="hidden" name="redirect" value="/public/movie.php?id=<?= $id ?>">
                    <span class="star-rating-label">Your rating:</span>
                    <select name="score" id="ratingSelect" class="star-rating-input" required>
                        <option value="">-- Pick --</option>
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                            <option value="<?= $s ?>" <?= (int)$myRating === $s ? 'selected' : '' ?>>
                                <?= $s ?> Star<?= $s > 1 ? 's' : '' ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit" class="submit-review-btn" style="padding:7px 18px">
                        <?= $myRating !== false && $myRating !== null ? 'Update Rating' : 'Rate' ?>
                    </button>
                </form>
            </div>

            <!-- Review textarea -->
            <div class="comment-box-movie">
                <form method="post" action="/action/review_save.php">
                    <input type="hidden" name="movie_id" value="<?= $id ?>">
                    <input type="hidden" name="redirect" value="/public/movie.php?id=<?= $id ?>">
                    <?php if ($myReview): ?>
                        <input type="hidden" name="review_id" value="<?= (int)$myReview['id'] ?>">
                    <?php endif; ?>
                    <textarea name="review_text"
                              placeholder="Leave a comment…"
                              required><?= e($myReview['review_text'] ?? '') ?></textarea>
                    <div class="comment-submit-row">
                        <span style="font-size:.75rem;color:var(--text-muted)">
                            <?= $myReview ? 'Editing your review' : 'Writing a new review' ?>
                        </span>
                        <button type="submit" class="submit-review-btn">
                            <i class="fa-solid fa-paper-plane"></i>
                            <?= $myReview ? 'Update Review' : 'Post Review' ?>
                        </button>
                    </div>
                </form>
            </div>

        <?php else: ?>
            <div class="login-prompt-box">
                <i class="fa-solid fa-star" style="color:var(--mv-star);margin-right:6px"></i>
                <a href="/auth/login.php">Log in</a> to rate, review, or add this movie to your watchlist.
            </div>
        <?php endif; ?>
    </div>
</div><!-- /my-rating-section -->

<script src="https://cdn.jsdelivr.net/npm/star-rating.js@4.3.0/dist/star-rating.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('ratingSelect')) {
        new StarRating('#ratingSelect', {
            maxStars: 5,
            tooltip: false,
            labels: ['Terrible', 'Bad', 'Average', 'Good', 'Excellent'],
        });
    }
});
</script>
<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>