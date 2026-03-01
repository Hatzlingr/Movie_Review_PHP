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

$movieId = (int) ($_POST['movie_id'] ?? 0);
$score   = (int) ($_POST['score']    ?? 0);

if ($movieId <= 0 || $score < 1 || $score > 5) {
    flash_set('danger', 'Invalid rating data.');
    redirect($redirect);
}

$userId = current_user()['id'];

$stmt = $pdo->prepare(
    "INSERT INTO ratings (user_id, movie_id, score) VALUES (:u, :m, :s)
     AS new_rt ON DUPLICATE KEY UPDATE score = new_rt.score"
);
$stmt->execute([':u' => $userId, ':m' => $movieId, ':s' => $score]);

flash_set('success', 'Your rating has been saved.');
redirect($redirect);
