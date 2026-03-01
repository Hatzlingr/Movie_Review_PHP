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

$pageTitle = e($movie['title']);
$extraHeadHtml = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/star-rating.js@4.3.0/dist/star-rating.min.css">';
require_once __DIR__ . '/../app/views/partials/header.php';
require_once __DIR__ . '/../app/views/partials/navbar.php';
?>

<main class="container mt-5 pt-5 pb-5">
    <div class="row g-4">

        <!-- ── LEFT: Poster ── -->
        <div class="col-md-3 text-center">
            <?php if ($movie['poster_path'] && file_exists(__DIR__ . '/' . $movie['poster_path'])): ?>
                <img src="/<?= e($movie['poster_path']) ?>"
                    alt="<?= e($movie['title']) ?> poster"
                    class="movie-poster-lg w-100">
            <?php else: ?>
                <div class="movie-poster-lg d-flex align-items-center justify-content-center bg-secondary text-white fs-1 w-100">
                    <i class="bi bi-film"></i>
                </div>
            <?php endif; ?>
        </div>

        <!-- ── RIGHT: Info ── -->
        <div class="col-md-9">
            <h2 class="fw-bold"><?= e($movie['title']) ?></h2>

            <div class="d-flex flex-wrap gap-3 text-muted mb-3">
                <?php if ($movie['release_year']): ?>
                    <span><i class="bi bi-calendar3"></i> <?= e($movie['release_year']) ?></span>
                <?php endif; ?>
                <?php if ($movie['duration_minutes']): ?>
                    <span><i class="bi bi-clock"></i> <?= e($movie['duration_minutes']) ?> min</span>
                <?php endif; ?>
                <?php if ($movie['avg_rating']): ?>
                    <span title="<?= e($movie['avg_rating']) ?>/5" style="color:#f1c40f; font-size:1.1rem; letter-spacing:1px">
                        <?= renderStars((float)$movie['avg_rating']) ?>
                    </span>
                    <span class="score-badge fs-6 px-2"><?= e($movie['avg_rating']) ?>/5</span>
                    <span class="text-muted"><?= (int)$movie['total_ratings'] ?> rating<?= $movie['total_ratings'] != 1 ? 's' : '' ?></span>
                <?php else: ?>
                    <span class="text-muted">No ratings yet</span>
                <?php endif; ?>
            </div>

            <?php if ($movie['description']): ?>
                <p class="mb-3"><?= e($movie['description']) ?></p>
            <?php endif; ?>

            <!-- Genres -->
            <?php if ($genres): ?>
                <div class="mb-2">
                    <strong>Genres:</strong>
                    <?php foreach ($genres as $g): ?>
                        <span class="badge bg-secondary"><?= e($g) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Directors -->
            <?php if ($directors): ?>
                <div class="mb-2">
                    <strong>Director<?= count($directors) > 1 ? 's' : '' ?>:</strong>
                    <?= e(implode(', ', $directors)) ?>
                </div>
            <?php endif; ?>

            <!-- Actors -->
            <?php if ($actors): ?>
                <div class="mb-3">
                    <strong>Cast:</strong>
                    <div class="d-flex flex-wrap gap-2 mt-1">
                        <?php foreach ($actors as $a): ?>
                            <span class="badge bg-light text-dark border">
                                <?= e($a['name']) ?>
                                <?php if ($a['role_name']): ?>
                                    <span class="text-muted fw-normal"> as <?= e($a['role_name']) ?></span>
                                <?php endif; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ── Logged-in actions ── -->
            <?php if ($user): ?>
                <div class="row g-3 mt-1">

                    <!-- Rating form -->
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="card-title"><i class="bi bi-star"></i> Your Rating</h6>
                                <form method="post" action="/action/rating_save.php">
                                    <input type="hidden" name="movie_id" value="<?= $id ?>">
                                    <input type="hidden" name="redirect" value="/public/movie.php?id=<?= $id ?>">
                                    <select name="score" id="ratingSelect" class="star-rating-input" required>
                                        <option value="">-- Pilih --</option>
                                        <?php for ($s = 1; $s <= 5; $s++): ?>
                                            <option value="<?= $s ?>" <?= (int)$myRating === $s ? 'selected' : '' ?>>
                                                <?= $s ?> Star<?= $s > 1 ? 's' : '' ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-warning mt-2">
                                        <?= $myRating !== false ? 'Update Rating' : 'Rate' ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Watchlist form -->
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="card-title"><i class="bi bi-bookmark-star"></i> Watchlist</h6>
                                <form method="post" action="/action/watchlist_save.php">
                                    <input type="hidden" name="movie_id" value="<?= $id ?>">
                                    <input type="hidden" name="redirect" value="/movie.php?id=<?= $id ?>">
                                    <div class="d-flex align-items-center gap-2">
                                        <select name="status" class="form-select form-select-sm" style="width:auto">
                                            <?php foreach (['plan_to_watch' => 'Plan to Watch', 'watching' => 'Watching', 'completed' => 'Completed'] as $val => $label): ?>
                                                <option value="<?= $val ?>" <?= $myWatchlist === $val ? 'selected' : '' ?>>
                                                    <?= $label ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                        <?php if ($myWatchlist): ?>
                                            <a href="/action/watchlist_remove.php?movie_id=<?= $id ?>&redirect=<?= urlencode('/movie.php?id=' . $id) ?>"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Remove from watchlist?')">
                                                Remove
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </div><!-- /row actions -->

                <!-- Review form (upsert) -->
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-body">
                        <h6 class="card-title"><i class="bi bi-chat-left-text"></i> Your Review</h6>
                        <form method="post" action="/action/review_save.php">
                            <input type="hidden" name="movie_id" value="<?= $id ?>">
                            <input type="hidden" name="redirect" value="/movie.php?id=<?= $id ?>">
                            <?php if ($myReview): ?>
                                <input type="hidden" name="review_id" value="<?= (int)$myReview['id'] ?>">
                            <?php endif; ?>
                            <textarea name="review_text" class="form-control mb-2" rows="3"
                                placeholder="Write your review…" required><?= e($myReview['review_text'] ?? '') ?></textarea>
                            <button type="submit" class="btn btn-sm btn-success">
                                <?= $myReview ? 'Update Review' : 'Post Review' ?>
                            </button>
                        </form>
                    </div>
                </div>

            <?php else: ?>
                <div class="alert alert-light border mt-3">
                    <a href="/auth/login.php">Log in</a> to rate, review, or add to your watchlist.
                </div>
            <?php endif; ?>
        </div><!-- /col right -->
    </div><!-- /row -->

    <!-- ── REVIEWS SECTION ── -->
    <hr class="my-5">
    <h4 class="mb-4 fw-bold"><i class="bi bi-chat-square-text"></i> Reviews (<?= count($reviews) ?>)</h4>

    <?php if (empty($reviews)): ?>
        <p class="text-muted">No reviews yet. Be the first!</p>
    <?php endif; ?>

    <?php foreach ($reviews as $rev): ?>
        <div class="card review-card mb-3 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="fw-semibold mb-1">
                        <i class="bi bi-person-circle"></i> <?= e($rev['username']) ?>
                        <small class="text-muted fw-normal ms-2"><?= e($rev['created_at']) ?></small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <!-- Like toggle -->
                        <?php if ($user): ?>
                            <?php $liked = in_array($rev['id'], $myLikedReviews, true); ?>
                            <form method="post" action="/action/review_like_toggle.php" class="d-inline">
                                <input type="hidden" name="review_id" value="<?= (int)$rev['id'] ?>">
                                <input type="hidden" name="redirect" value="/movie.php?id=<?= $id ?>">
                                <button type="submit" class="btn btn-sm btn-outline-<?= $liked ? 'danger' : 'secondary' ?> like-btn <?= $liked ? 'liked' : '' ?>">
                                    <i class="bi bi-heart<?= $liked ? '-fill' : '' ?>"></i>
                                    <?= (int)$rev['like_count'] ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted small">
                                <i class="bi bi-heart"></i> <?= (int)$rev['like_count'] ?>
                            </span>
                        <?php endif; ?>

                        <!-- Delete (owner or admin) -->
                        <?php if ($user && ($user['id'] === (int)($rev['user_id'] ?? 0) || $user['role'] === 'admin')): ?>
                            <a href="/action/review_delete.php?review_id=<?= (int)$rev['id'] ?>&redirect=<?= urlencode('/movie.php?id=' . $id) ?>"
                                class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Delete this review?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <p class="mb-0 mt-1"><?= nl2br(e($rev['review_text'])) ?></p>
            </div>
        </div>
    <?php endforeach; ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/star-rating.js@4.3.0/dist/star-rating.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        new StarRating('#ratingSelect', {
            maxStars: 5,
            tooltip: false,
            labels: ['Terrible', 'Bad', 'Average', 'Good', 'Excellent'],
        });
    });
</script>
<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>