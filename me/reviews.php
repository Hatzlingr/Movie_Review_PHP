<?php
// me/reviews.php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/db.php';

ensure_session();
require_login();

$userId = current_user()['id'];

// ── Fetch my reviews ─────────────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT rv.id, rv.review_text, rv.created_at,
            m.id AS movie_id, m.title, m.release_year, m.poster_path,
            COUNT(rl.id) AS like_count
     FROM reviews rv
     JOIN movies m ON m.id = rv.movie_id
     LEFT JOIN review_likes rl ON rl.review_id = rv.id
     WHERE rv.user_id = :u
     GROUP BY rv.id
     ORDER BY rv.created_at DESC"
);
$stmt->execute([':u' => $userId]);
$reviews = $stmt->fetchAll();

// ── Inline edit: POST ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reviewId   = (int) ($_POST['review_id']   ?? 0);
    $reviewText = trim($_POST['review_text']    ?? '');

    if ($reviewId <= 0 || $reviewText === '') {
        flash_set('danger', 'Review text cannot be empty.');
        redirect('/me/reviews.php');
    }

    // Verify ownership
    $own = $pdo->prepare("SELECT id FROM reviews WHERE id = :id AND user_id = :u");
    $own->execute([':id' => $reviewId, ':u' => $userId]);
    if (!$own->fetch()) {
        flash_set('danger', 'Review not found.');
        redirect('/me/reviews.php');
    }

    $upd = $pdo->prepare("UPDATE reviews SET review_text = :t WHERE id = :id");
    $upd->execute([':t' => $reviewText, ':id' => $reviewId]);

    flash_set('success', 'Review updated.');
    redirect('/me/reviews.php');
}

$pageTitle = 'My Reviews';
require_once __DIR__ . '/../app/views/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-chat-left-text"></i> My Reviews</h4>
    <span class="badge bg-secondary fs-6"><?= count($reviews) ?> review<?= count($reviews) !== 1 ? 's' : '' ?></span>
</div>

<?php if (empty($reviews)): ?>
    <div class="alert alert-info">You haven't written any reviews yet. <a href="/public/index.php">Browse movies</a> to get started!</div>
<?php endif; ?>

<?php foreach ($reviews as $rev): ?>
    <div class="card review-card shadow-sm mb-4">
        <div class="card-body">

            <!-- Movie info row -->
            <div class="d-flex align-items-center gap-3 mb-3">
                <?php if ($rev['poster_path'] && file_exists(__DIR__ . '/../public/' . $rev['poster_path'])): ?>
                    <img src="/public/<?= e($rev['poster_path']) ?>"
                        alt="poster"
                        style="width:44px;height:66px;object-fit:cover;border-radius:4px">
                <?php else: ?>
                    <div class="bg-secondary d-flex align-items-center justify-content-center text-white"
                        style="width:44px;height:66px;border-radius:4px;font-size:1.2rem">
                        <i class="bi bi-film"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <a href="/public/movie.php?id=<?= (int)$rev['movie_id'] ?>"
                        class="fw-semibold text-decoration-none">
                        <?= e($rev['title']) ?>
                    </a>
                    <div class="text-muted small">
                        <?= e($rev['release_year'] ?? '') ?>
                        &mdash; posted <?= e(substr($rev['created_at'], 0, 10)) ?>
                        &mdash; <i class="bi bi-heart-fill text-danger"></i> <?= (int)$rev['like_count'] ?> like<?= $rev['like_count'] != 1 ? 's' : '' ?>
                    </div>
                </div>
            </div>

            <!-- Inline edit form -->
            <form method="post">
                <input type="hidden" name="review_id" value="<?= (int)$rev['id'] ?>">
                <textarea name="review_text"
                    class="form-control mb-2"
                    rows="3"
                    required><?= e($rev['review_text']) ?></textarea>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="bi bi-check-lg"></i> Save
                    </button>
                    <a href="/action/review_delete.php?review_id=<?= (int)$rev['id'] ?>&redirect=<?= urlencode('/me/reviews.php') ?>"
                        class="btn btn-sm btn-outline-danger"
                        onclick="return confirm('Delete this review?')">
                        <i class="bi bi-trash"></i> Delete
                    </a>
                    <a href="/public/movie.php?id=<?= (int)$rev['movie_id'] ?>"
                        class="btn btn-sm btn-outline-secondary ms-auto">
                        <i class="bi bi-film"></i> View Movie
                    </a>
                </div>
            </form>

        </div>
    </div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>