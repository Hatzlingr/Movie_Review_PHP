<?php
// actions/review_like_toggle.php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/db.php';

ensure_session();
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/index.php');
}

$reviewId = (int) ($_POST['review_id'] ?? 0);
$redirect = $_POST['redirect'] ?? '/index.php';

if ($reviewId <= 0) {
    flash_set('danger', 'Invalid review.');
    redirect($redirect);
}

$userId = current_user()['id'];

// Check if already liked
$check = $pdo->prepare(
    "SELECT id FROM review_likes WHERE user_id = :u AND review_id = :r"
);
$check->execute([':u' => $userId, ':r' => $reviewId]);

if ($check->fetch()) {
    // Unlike
    $del = $pdo->prepare(
        "DELETE FROM review_likes WHERE user_id = :u AND review_id = :r"
    );
    $del->execute([':u' => $userId, ':r' => $reviewId]);
} else {
    // Like
    $ins = $pdo->prepare(
        "INSERT IGNORE INTO review_likes (user_id, review_id) VALUES (:u, :r)"
    );
    $ins->execute([':u' => $userId, ':r' => $reviewId]);
}

redirect($redirect);
