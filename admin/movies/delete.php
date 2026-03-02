<?php
// admin/movies/delete.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/helpers/functions.php';
require_once __DIR__ . '/../../app/helpers/flash.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

ensure_session();
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/movies/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    redirect('/admin/movies/index.php');
}

$stmt = $pdo->prepare("SELECT poster_path, banner_path FROM movies WHERE id = :id");
$stmt->execute([':id' => $id]);
$movie = $stmt->fetch();

if (!$movie) {
    flash_set('danger', 'Movie not found.');
    redirect('/admin/movies/index.php');
}

// Delete poster file
if ($movie['poster_path'] && file_exists(upload_path($movie['poster_path']))) {
    if (!unlink(upload_path($movie['poster_path']))) {
        error_log('Failed to delete poster: ' . upload_path($movie['poster_path']));
    }
}

// Delete banner file
if ($movie['banner_path'] && file_exists(upload_path($movie['banner_path']))) {
    if (!unlink(upload_path($movie['banner_path']))) {
        error_log('Failed to delete banner: ' . upload_path($movie['banner_path']));
    }
}

$del = $pdo->prepare("DELETE FROM movies WHERE id = :id");
$del->execute([':id' => $id]);

flash_set('success', 'Movie deleted.');
redirect('/admin/movies/index.php');
