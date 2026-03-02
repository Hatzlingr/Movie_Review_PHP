<?php
// admin/directors/delete.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/helpers/functions.php';
require_once __DIR__ . '/../../app/helpers/flash.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

ensure_session();
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/directors/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    redirect('/admin/directors/index.php');
}

$stmt = $pdo->prepare("SELECT photo_path FROM directors WHERE id = :id");
$stmt->execute([':id' => $id]);
$director = $stmt->fetch();

if (!$director) {
    flash_set('danger', 'Director not found.');
    redirect('/admin/directors/index.php');
}

if ($director['photo_path'] && file_exists(upload_path($director['photo_path']))) {
    if (!unlink(upload_path($director['photo_path']))) {
        error_log('Failed to delete director photo: ' . upload_path($director['photo_path']));
    }
}

$pdo->prepare("DELETE FROM directors WHERE id = :id")->execute([':id' => $id]);
flash_set('success', 'Director deleted.');
redirect('/admin/directors/index.php');
