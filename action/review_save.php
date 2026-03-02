<?php
// actions/review_save.php
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

$movieId    = (int) ($_POST['movie_id']    ?? 0);
$reviewText = trim($_POST['review_text']   ?? '');

if ($movieId <= 0 || $reviewText === '') {
    flash_set('danger', 'Review text cannot be empty.');
    redirect($redirect);
}
if (mb_strlen($reviewText) > 5000) {
    flash_set('danger', 'Review is too long (max 5000 characters).');
    redirect($redirect);
}

$userId = current_user()['id'];

$stmt = $pdo->prepare(
    "INSERT INTO reviews (user_id, movie_id, review_text) VALUES (:u, :m, :t)
     AS new_rv ON DUPLICATE KEY UPDATE review_text = new_rv.review_text"
);
$stmt->execute([':u' => $userId, ':m' => $movieId, ':t' => $reviewText]);

flash_set('success', 'Your review has been saved.');
redirect($redirect);
