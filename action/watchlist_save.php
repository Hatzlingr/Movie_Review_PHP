<?php
// actions/watchlist_save.php
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
$status  = $_POST['status'] ?? '';
$allowed = ['plan_to_watch', 'watching', 'completed'];

if ($movieId <= 0 || !in_array($status, $allowed, true)) {
    flash_set('danger', 'Invalid watchlist data.');
    redirect($redirect);
}

$userId = current_user()['id'];

$stmt = $pdo->prepare(
    "INSERT INTO watchlists (user_id, movie_id, status) VALUES (:u, :m, :s)
     AS new_wl ON DUPLICATE KEY UPDATE status = new_wl.status"
);
$stmt->execute([':u' => $userId, ':m' => $movieId, ':s' => $status]);

flash_set('success', 'Watchlist updated.');
redirect($redirect);
