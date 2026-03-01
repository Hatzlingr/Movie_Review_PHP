<?php
// actions/rating_save.php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/db.php';

ensure_session();
require_login();

$redirect = $_POST['redirect'] ?? '/index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($redirect);
}

$movieId    = (int) ($_POST['movie_id'] ?? 0);
$score      = (int) ($_POST['score']    ?? 0);
$reviewText = trim($_POST['review_text'] ?? '');

if ($movieId <= 0 || $score < 1 || $score > 5) {
    flash_set('danger', 'Invalid rating data. Please pick a star rating.');
    redirect($redirect);
}

$userId = current_user()['id'];

// Upsert rating
$stmt = $pdo->prepare(
    "INSERT INTO ratings (user_id, movie_id, score) VALUES (:u, :m, :s)
     AS new_rt ON DUPLICATE KEY UPDATE score = new_rt.score"
);
$stmt->execute([':u' => $userId, ':m' => $movieId, ':s' => $score]);

// Upsert review (optional — only if user typed something)
if ($reviewText !== '') {
    $stmt = $pdo->prepare(
        "INSERT INTO reviews (user_id, movie_id, review_text) VALUES (:u, :m, :t)
         AS new_rv ON DUPLICATE KEY UPDATE review_text = new_rv.review_text"
    );
    $stmt->execute([':u' => $userId, ':m' => $movieId, ':t' => $reviewText]);
    flash_set('success', 'Your rating and review have been saved.');
} else {
    flash_set('success', 'Your rating has been saved.');
}

redirect($redirect);
