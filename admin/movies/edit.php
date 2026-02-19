<?php
// admin/movies/edit.php
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

$movie = $pdo->prepare("SELECT * FROM movies WHERE id = :id");
$movie->execute([':id' => $id]);
$movie = $movie->fetch();
if (!$movie) {
    flash_set('danger', 'Movie not found.');
    redirect('/admin/movies/index.php');
}

$errors = [];
$fields = [
    'title'            => $movie['title'],
    'description'      => $movie['description'] ?? '',
    'release_year'     => $movie['release_year'] ?? '',
    'duration_minutes' => $movie['duration_minutes'] ?? '',
];

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

    $posterPath = $movie['poster_path'];
    if (!empty($_FILES['poster']['name'])) {
        $file    = $_FILES['poster'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo   = new finfo(FILEINFO_MIME_TYPE);
        $mime    = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $allowed, true)) {
            $errors[] = 'Poster must be JPG, PNG, or WebP.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Poster must be under 2 MB.';
        } else {
            $ext       = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
            $filename  = bin2hex(random_bytes(12)) . '.' . $ext;
            $uploadDir = __DIR__ . '/../../public/uploads/posters/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            move_uploaded_file($file['tmp_name'], $uploadDir . $filename);
            // Delete old poster
            if ($posterPath && file_exists(__DIR__ . '/../../public/' . $posterPath)) {
                @unlink(__DIR__ . '/../../public/' . $posterPath);
            }
            $posterPath = 'uploads/posters/' . $filename;
        }
    }

    if (empty($errors)) {
        $upd = $pdo->prepare(
            "UPDATE movies SET title=:t, description=:d, release_year=:y, duration_minutes=:dur, poster_path=:p
             WHERE id=:id"
        );
        $upd->execute([
            ':t'   => $fields['title'],
            ':d'   => $fields['description'] ?: null,
            ':y'   => $fields['release_year'] !== '' ? (int)$fields['release_year'] : null,
            ':dur' => $fields['duration_minutes'] !== '' ? (int)$fields['duration_minutes'] : null,
            ':p'   => $posterPath,
            ':id'  => $id,
        ]);
        flash_set('success', 'Movie updated.');
        redirect('/admin/movies/index.php');
    }
}

$pageTitle = 'Edit Movie';
require_once __DIR__ . '/../../app/views/partials/header.php';
?>

<div class="d-flex gap-4 align-items-start">
    <?php require_once __DIR__ . '/../../app/views/partials/sidebar_admin.php'; ?>

    <div class="flex-grow-1" style="max-width:680px">
        <h4 class="fw-bold mb-4"><i class="bi bi-pencil-square"></i> Edit Movie</h4>

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
                <?php if ($movie['poster_path'] && file_exists(__DIR__ . '/../../public/' . $movie['poster_path'])): ?>
                    <div class="mb-2">
                        <img src="/public/<?= e($movie['poster_path']) ?>" style="height:90px;border-radius:4px" alt="current poster">
                        <small class="text-muted ms-2">Current poster</small>
                    </div>
                <?php endif; ?>
                <input type="file" name="poster" class="form-control" accept="image/jpeg,image/png,image/webp">
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
                <a href="/admin/movies/assign_genres.php?id=<?= $id ?>" class="btn btn-outline-secondary"><i class="bi bi-tags"></i> Genres</a>
                <a href="/admin/movies/assign_actors.php?id=<?= $id ?>" class="btn btn-outline-secondary"><i class="bi bi-person-video3"></i> Actors</a>
                <a href="/admin/movies/assign_directors.php?id=<?= $id ?>" class="btn btn-outline-secondary"><i class="bi bi-megaphone"></i> Directors</a>
                <a href="/admin/movies/index.php" class="btn btn-outline-secondary ms-auto">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/views/partials/footer.php'; ?>