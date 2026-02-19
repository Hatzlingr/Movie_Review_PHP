<?php
// admin/movies/create.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/helpers/functions.php';
require_once __DIR__ . '/../../app/helpers/flash.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

ensure_session();
require_admin();

$errors = [];
$fields = ['title' => '', 'description' => '', 'release_year' => '', 'duration_minutes' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields['title']            = trim($_POST['title']            ?? '');
    $fields['description']      = trim($_POST['description']      ?? '');
    $fields['release_year']     = trim($_POST['release_year']     ?? '');
    $fields['duration_minutes'] = trim($_POST['duration_minutes'] ?? '');

    if ($fields['title'] === '') {
        $errors[] = 'Title is required.';
    }
    if ($fields['release_year'] !== '' && !ctype_digit($fields['release_year'])) {
        $errors[] = 'Release year must be a number.';
    }
    if ($fields['duration_minutes'] !== '' && !ctype_digit($fields['duration_minutes'])) {
        $errors[] = 'Duration must be a number.';
    }

    // Poster upload
    $posterPath = null;
    if (!empty($_FILES['poster']['name'])) {
        $file     = $_FILES['poster'];
        $allowed  = ['image/jpeg', 'image/png', 'image/webp'];
        $maxBytes = 2 * 1024 * 1024;
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mime     = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $allowed, true)) {
            $errors[] = 'Poster must be JPG, PNG, or WebP.';
        } elseif ($file['size'] > $maxBytes) {
            $errors[] = 'Poster must be under 2 MB.';
        } else {
            $ext        = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
            $filename   = bin2hex(random_bytes(12)) . '.' . $ext;
            $uploadDir  = __DIR__ . '/../../public/uploads/posters/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            move_uploaded_file($file['tmp_name'], $uploadDir . $filename);
            $posterPath = 'uploads/posters/' . $filename;
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO movies (title, description, release_year, duration_minutes, poster_path)
             VALUES (:t, :d, :y, :dur, :p)"
        );
        $stmt->execute([
            ':t'   => $fields['title'],
            ':d'   => $fields['description'] ?: null,
            ':y'   => $fields['release_year'] !== '' ? (int)$fields['release_year'] : null,
            ':dur' => $fields['duration_minutes'] !== '' ? (int)$fields['duration_minutes'] : null,
            ':p'   => $posterPath,
        ]);
        $newId = (int) $pdo->lastInsertId();
        flash_set('success', 'Movie created. Now assign genres, actors, and directors.');
        redirect('/admin/movies/assign_genres.php?id=' . $newId);
    }
}

$pageTitle = 'Add Movie';
require_once __DIR__ . '/../../app/views/partials/header.php';
?>

<div class="d-flex gap-4 align-items-start">
    <?php require_once __DIR__ . '/../../app/views/partials/sidebar_admin.php'; ?>

    <div class="flex-grow-1" style="max-width:680px">
        <h4 class="fw-bold mb-4"><i class="bi bi-plus-circle"></i> Add Movie</h4>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger py-2"><?= e($err) ?></div>
        <?php endforeach; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" value="<?= e($fields['title']) ?>" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <textarea name="description" class="form-control" rows="4"><?= e($fields['description']) ?></textarea>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Release Year</label>
                    <input type="number" name="release_year" class="form-control" min="1888" max="2099"
                        value="<?= e($fields['release_year']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Duration (minutes)</label>
                    <input type="number" name="duration_minutes" class="form-control" min="1"
                        value="<?= e($fields['duration_minutes']) ?>">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Poster <small class="text-muted">(JPG/PNG/WebP, max 2 MB)</small></label>
                <input type="file" name="poster" class="form-control" accept="image/jpeg,image/png,image/webp">
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Create Movie</button>
                <a href="/admin/movies/index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/views/partials/footer.php'; ?>