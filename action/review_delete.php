<?php
// actions/review_delete.php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/db.php';

ensure_session();
require_login();

$reviewId = (int) ($_GET['review_id'] ?? 0);
$redirect = $_GET['redirect'] ?? '/index.php';

if ($reviewId <= 0) {
    flash_set('danger', 'Invalid review.');
    redirect($redirect);
}

$user = current_user();
$userId = $user['id'];

// Fetch the review to verify ownership
$stmt = $pdo->prepare("SELECT user_id FROM reviews WHERE id = :id");
$stmt->execute([':id' => $reviewId]);
$review = $stmt->fetch();

if (!$review) {
    flash_set('danger', 'Review not found.');
    redirect($redirect);
}

if ((int)$review['user_id'] !== $userId && $user['role'] !== 'admin') {
    flash_set('danger', 'You are not allowed to delete this review.');
    redirect($redirect);
}

$del = $pdo->prepare("DELETE FROM reviews WHERE id = :id");
$del->execute([':id' => $reviewId]);

flash_set('success', 'Review deleted.');
redirect($redirect);
