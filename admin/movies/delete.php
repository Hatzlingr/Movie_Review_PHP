<?php
// admin/movies/delete.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/helpers/functions.php';
require_once __DIR__ . '/../../app/helpers/flash.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

ensure_session();
require_admin();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('/admin/movies/index.php');
}

$stmt = $pdo->prepare("SELECT poster_path FROM movies WHERE id = :id");
$stmt->execute([':id' => $id]);
$movie = $stmt->fetch();

if (!$movie) {
    flash_set('danger', 'Movie not found.');
    redirect('/admin/movies/index.php');
}

// Delete poster file
if ($movie['poster_path'] && file_exists(__DIR__ . '/../../public/' . $movie['poster_path'])) {
    @unlink(__DIR__ . '/../../public/' . $movie['poster_path']);
}

$del = $pdo->prepare("DELETE FROM movies WHERE id = :id");
$del->execute([':id' => $id]);

flash_set('success', 'Movie deleted.');
redirect('/admin/movies/index.php');
