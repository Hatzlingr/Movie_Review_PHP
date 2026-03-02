<?php
// actions/watchlist_remove.php
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

$movieId  = (int) ($_POST['movie_id'] ?? 0);
$redirect = $_POST['redirect'] ?? '/index.php';

if ($movieId <= 0) {
    flash_set('danger', 'Invalid movie.');
    redirect($redirect);
}

$userId = current_user()['id'];

$stmt = $pdo->prepare(
    "DELETE FROM watchlists WHERE user_id = :u AND movie_id = :m"
);
$stmt->execute([':u' => $userId, ':m' => $movieId]);

flash_set('success', 'Removed from your watchlist.');
redirect($redirect);
