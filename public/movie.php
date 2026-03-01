<?php
// public/movie.php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/repositories/movieRepository.php';

ensure_session();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('/index.php');
}

$repo = new MovieRepository($pdo);

// ── Fetch movie ──────────────────────────────────────────────
$movie = $repo->getMovieById($id);
if (!$movie) {
    flash_set('danger', 'Movie not found.');
    redirect('/index.php');
}

// ── Genres, Actors, Directors, Reviews ───────────────────────
$genres    = $repo->getGenresByMovieId($id);
$actors    = $repo->getActorsByMovieId($id);
$directors = $repo->getDirectorsByMovieId($id);
$reviews   = $repo->getReviewsByMovieId($id);

// ── Current user context ─────────────────────────────────────
$user           = current_user();
$myRating       = null;
$myReview       = null;
$myWatchlist    = null;
$myLikedReviews = [];

if ($user) {
    $ctx            = $repo->getUserMovieContext((int)$user['id'], $id);
    $myRating       = $ctx['myRating'];
    $myReview       = $ctx['myReview'];
    $myWatchlist    = $ctx['myWatchlist'];
    $myLikedReviews = $ctx['myLikedReviews'];
}

$pageTitle  = e($movie['title']);
$bodyClass  = 'movie-page';
$extraHeadHtml = '<link rel="stylesheet" href="/public/assets/css/movie.css">';

$dur = formatDuration((int)($movie['duration_minutes'] ?? 0));

require_once __DIR__ . '/../app/views/partials/header.php';
require_once __DIR__ . '/../app/views/partials/navbar.php';
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
        <img class="hero-bg" src="<?= e(imageUrl($movie['banner_path'] ?? null, 'banner')) ?>" alt="<?= e($movie['title']) ?> backdrop"
            onerror="this.src='https://placehold.co/1280x720/2f2543/ffffff?text=No+Banner';this.onerror=null;">
        <div class="hero-overlay"></div>
    </div>

    <!-- ── MOVIE INFO PANEL ──────────────────────────────────── -->
    <div class="movie-info-panel">
        <div class="movie-info-inner">

            <!-- Poster thumbnail -->
            <div class="movie-poster-thumb">
                <?php if ($movie['poster_path']): ?>
                    <img src="<?= e(imageUrl($movie['poster_path'], 'poster')) ?>"
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
                        <span class="movie-year-sub">(<?= e($movie['release_year']) ?>)</span>
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
                        <?= e(implode(', ', array_column($directors, 'name'))) ?>
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
                        <span class="text-muted no-rating-text">No ratings yet</span>
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
                            <?php if ($d['photo_path']): ?>
                                <img src="<?= e(imageUrl($d['photo_path'], 'director')) ?>" alt="<?= e($d['name']) ?>">
                            <?php else: ?>
                                <div class="cast-initials">
                                    <?= e(mb_strtoupper(mb_substr($d['name'], 0, 2))) ?>
                                </div>
                            <?php endif; ?>
                            <div class="cast-info">
                                <div class="cast-name"><?= e($d['name']) ?></div>
                                <div class="cast-role">Director</div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Actors -->
                    <?php foreach ($actors as $a): ?>
                        <div class="cast-card">
                            <?php if ($a['photo_path']): ?>
                                <img src="<?= e(imageUrl($a['photo_path'], 'actor')) ?>" alt="<?= e($a['name']) ?>">
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
            <span class="review-count-sub">(<?= count($reviews) ?>)</span>
        </div>

        <?php if (empty($reviews)): ?>
            <p class="text-muted no-reviews-text">No reviews yet. Be the first!</p>
        <?php endif; ?>

        <?php foreach ($reviews as $idx => $rev): ?>
            <?php $liked = in_array($rev['id'], $myLikedReviews, true); ?>
            <div class="review-card-movie" style="animation-delay:<?= $idx * 0.07 ?>s">
                <div class="review-header-movie">
                    <!-- Reviewer info -->
                    <div class="reviewer-row">
                        <div class="reviewer-avatar-circle">
                            <?php if ($rev['profile_photo']): ?>
                                <img src="<?= e(imageUrl($rev['profile_photo'], 'avatar')) ?>" alt="<?= e($rev['username']) ?>">
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
                            <form method="post" action="/action/review_like_toggle.php" class="form-inline-block">
                                <input type="hidden" name="review_id" value="<?= (int)$rev['id'] ?>">
                                <input type="hidden" name="redirect" value="/public/movie.php?id=<?= $id ?>">
                                <button type="submit" class="like-btn-movie <?= $liked ? 'liked' : '' ?>">
                                    <i class="fa-<?= $liked ? 'solid' : 'regular' ?> fa-heart"></i>
                                    <?= (int)$rev['like_count'] ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="like-btn-movie like-btn-static">
                                <i class="fa-regular fa-heart"></i> <?= (int)$rev['like_count'] ?>
                            </span>
                        <?php endif; ?>

                        <?php if ($user && ((int)$user['id'] === (int)$rev['user_id'] || $user['role'] === 'admin')): ?>
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
                <!-- Unified Rating + Review form -->
                <form method="post" action="/action/rating_save.php">
                    <input type="hidden" name="movie_id" value="<?= $id ?>">
                    <input type="hidden" name="redirect" value="/public/movie.php?id=<?= $id ?>">

                    <!-- Stars (required) -->
                    <div class="star-rating-wrap">
                        <span class="star-rating-label">Your rating:</span>
                        <div class="custom-star-input" data-current="<?= (int)$myRating ?>">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <i class="fa-star <?= (int)$myRating >= $s ? 'fa-solid' : 'fa-regular' ?> star-input-icon"
                                    data-value="<?= $s ?>"></i>
                            <?php endfor; ?>
                            <input type="hidden" name="score" id="scoreInput"
                                value="<?= (int)$myRating ?: '' ?>" required>
                        </div>
                    </div>

                    <!-- Review (optional) -->
                    <div class="comment-box-movie">
                        <textarea name="review_text"
                            placeholder="Leave a comment (optional)…"><?= e($myReview['review_text'] ?? '') ?></textarea>
                        <div class="comment-submit-row">
                            <span class="review-status-hint">
                                <?= ($myRating !== null || $myReview !== null) ? 'Editing your rating &amp; review' : 'Rate this movie' ?>
                            </span>
                            <button type="submit" class="submit-review-btn">
                                <i class="fa-solid fa-paper-plane"></i>
                                <?= ($myRating !== null || $myReview !== null) ? 'Update' : 'Rate' ?>
                            </button>
                        </div>
                    </div>
                </form>

            <?php else: ?>
                <div class="login-prompt-box">
                    <i class="fa-solid fa-star login-star-icon"></i>
                    <a href="/auth/login.php">Log in</a> to rate, review, or add this movie to your watchlist.
                </div>
            <?php endif; ?>
        </div>
    </div><!-- /my-rating-section -->

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const wrap = document.querySelector('.custom-star-input');
            if (!wrap) return;
            const stars = wrap.querySelectorAll('.star-input-icon');
            const input = document.getElementById('scoreInput');

            function highlight(n) {
                stars.forEach(function(s, i) {
                    s.classList.toggle('fa-solid', i < n);
                    s.classList.toggle('fa-regular', i >= n);
                });
            }

            stars.forEach(function(star, idx) {
                star.addEventListener('mouseenter', function() {
                    highlight(idx + 1);
                });
                star.addEventListener('mouseleave', function() {
                    highlight(parseInt(input.value) || 0);
                });
                star.addEventListener('click', function() {
                    input.value = idx + 1;
                    highlight(idx + 1);
                });
            });
        });
    </script>
    <?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>