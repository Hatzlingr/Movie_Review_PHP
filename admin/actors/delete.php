<?php
// admin/actors/delete.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/helpers/functions.php';
require_once __DIR__ . '/../../app/helpers/flash.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

ensure_session();
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/actors/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    redirect('/admin/actors/index.php');
}

$stmt = $pdo->prepare("SELECT photo_path FROM actors WHERE id = :id");
$stmt->execute([':id' => $id]);
$actor = $stmt->fetch();

if (!$actor) {
    flash_set('danger', 'Actor not found.');
    redirect('/admin/actors/index.php');
}

if ($actor['photo_path'] && file_exists(upload_path($actor['photo_path']))) {
    if (!unlink(upload_path($actor['photo_path']))) {
        error_log('Failed to delete actor photo: ' . upload_path($actor['photo_path']));
    }
}

$pdo->prepare("DELETE FROM actors WHERE id = :id")->execute([':id' => $id]);
flash_set('success', 'Actor deleted.');
redirect('/admin/actors/index.php');
