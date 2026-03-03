<?php
// me/reviews.php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/repositories/movieRepository.php';

$repo = new movieRepository($pdo);

ensure_session();
require_login();

$userId = current_user()['id'];

// ── Fetch my reviews ─────────────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT rv.id, rv.review_text, rv.created_at,
            m.id AS movie_id, m.title, m.release_year, m.poster_path,
            u.username, u.profile_photo,
            COALESCE(rat.score, NULL) AS user_score,
            COUNT(rl.id) AS like_count
     FROM reviews rv
     JOIN movies m ON m.id = rv.movie_id
     JOIN users u ON u.id = rv.user_id
     LEFT JOIN ratings rat ON rat.user_id = rv.user_id AND rat.movie_id = rv.movie_id
     LEFT JOIN review_likes rl ON rl.review_id = rv.id
     WHERE rv.user_id = :u
     GROUP BY rv.id
     ORDER BY rv.created_at DESC"
);
$stmt->execute([':u' => $userId]);
$reviews = $stmt->fetchAll();

?>

<!-- My Reviews -->
    <div class="container py-5">
        <h5 class="section-header" style="text-transform:lowercase;">my reviews</h5>
        <div class="scroll-wrapper">
            <button class="nav-arrow-list" onclick="scrollSection(this, 1)"><i class="fa-solid fa-chevron-right"></i></button>
            <div class="horizontal-scroll">
                <?php if (empty($reviews)): ?>
                    <p class="text-muted">No reviews yet.</p>
                <?php else: ?>
                    <?php foreach ($reviews as $rv): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="reviewer-row">
                                    <div class="reviewer-avatar-circle">
                                        <?php if ($rv['profile_photo']): ?>
                                            <img src="<?= e(imageUrl($rv['profile_photo'], 'avatar')) ?>" alt="<?= e($rv['username']) ?>">
                                        <?php else: ?>
                                            <?= e(mb_strtoupper(mb_substr($rv['username'], 0, 1))) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="reviewer-meta">
                                        <div class="rev-name"><?= e($rv['username']) ?></div>
                                        <div class="rev-date"><?= e(date('n/j/Y', strtotime($rv['created_at']))) ?></div>
                                    </div>
                                </div>
                                <div class="review-score">
                                    <?php if ($rv['user_score']): ?>
                                        <?= renderStars((float)$rv['user_score']) ?>
                                    <?php else: ?>
                                        &mdash;
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="review-text-body"><?= e($rv['review_text']) ?></p>
                            <div class="review-actions">
                                <span><i class="fa-regular fa-thumbs-up me-1"></i><?= (int)$rv['like_count'] ?></span>
                                <span><i class="fa-regular fa-comment me-1"></i><a href="/public/movie.php?id=<?= (int)$rv['movie_id'] ?>"><?= e($rv['title']) ?></a></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>