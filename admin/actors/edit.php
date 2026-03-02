<?php
// admin/actors/edit.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/helpers/functions.php';
require_once __DIR__ . '/../../app/helpers/flash.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

ensure_session();
require_admin();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('/admin/actors/index.php');
}

$stmt = $pdo->prepare("SELECT * FROM actors WHERE id = :id");
$stmt->execute([':id' => $id]);
$actor = $stmt->fetch();
if (!$actor) {
    flash_set('danger', 'Actor not found.');
    redirect('/admin/actors/index.php');
}

$errors = [];
$name   = $actor['name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $errors[] = 'Actor name is required.';
    } elseif (mb_strlen($name) > 100) {
        $errors[] = 'Actor name must be 100 characters or fewer.';
    } else {
        $currentPhoto = $actor['photo_path'];
        $newPhotoPath = $currentPhoto;

        if (!empty($_FILES['photo']['name'])) {
            $file    = $_FILES['photo'];
            $finfo   = new finfo(FILEINFO_MIME_TYPE);
            $mime    = $finfo->file($file['tmp_name']);
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            $extMap  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Photo upload failed (error code: ' . $file['error'] . ').';
            } elseif (!in_array($mime, $allowed, true)) {
                $errors[] = 'Photo must be JPG, PNG, or WebP.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Photo must be under 2 MB.';
            } else {
                $filename  = bin2hex(random_bytes(12)) . '.' . $extMap[$mime];
                $uploadDir = upload_path('uploads/actors/');
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
                if (!move_uploaded_file($file['tmp_name'], rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $filename)) {
                    $errors[] = 'Failed to save photo file.';
                } else {
                    $newPhotoPath = 'uploads/actors/' . $filename;
                }
            }
        }

        if (empty($errors)) {
            try {
                $pdo->prepare("UPDATE actors SET name = :n, photo_path = :p WHERE id = :id")
                    ->execute([':n' => $name, ':p' => $newPhotoPath, ':id' => $id]);
                if ($newPhotoPath !== $currentPhoto && $currentPhoto && file_exists(upload_path($currentPhoto))) {
                    if (!unlink(upload_path($currentPhoto))) {
                        error_log('Failed to delete old actor photo: ' . upload_path($currentPhoto));
                    }
                }
                flash_set('success', 'Actor updated.');
                redirect('/admin/actors/index.php');
            } catch (PDOException $e) {
                $errors[] = 'Actor name already exists.';
            }
        }
    }
}

$pageTitle = 'Edit Actor';
require_once __DIR__ . '/../../app/views/partials/header_admin.php';
?>

<h4 class="fw-bold mb-4"><i class="bi bi-pencil-square"></i> Edit Actor: <?= e($actor['name']) ?></h4>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-danger py-2"><?= e($err) ?></div>
<?php endforeach; ?>

<form method="post" enctype="multipart/form-data">
    <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="bi bi-info-circle me-2"></i>Actor Info</div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="<?= e($name) ?>" required autofocus maxlength="100">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Photo <small class="text-muted">(JPG/PNG/WebP, max 2 MB)</small></label>
                <?php if ($actor['photo_path']): ?>
                    <div class="mb-2">
                        <img src="<?= e(imageUrl($actor['photo_path'], 'actor')) ?>"
                            style="width:60px;height:60px;object-fit:cover;border-radius:50%" alt="">
                        <small class="text-muted ms-2">Current photo</small>
                    </div>
                <?php endif; ?>
                <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mb-5">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
        <a href="/admin/actors/index.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<?php require_once __DIR__ . '/../../app/views/partials/footer_admin.php'; ?>