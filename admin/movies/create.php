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
    if ($fields['description'] === '') {
        $errors[] = 'Description is required.';
    }
    if ($fields['release_year'] === '') {
        $errors[] = 'Release year is required.';
    } elseif (!ctype_digit($fields['release_year'])) {
        $errors[] = 'Release year must be a number.';
    }
    if ($fields['duration_minutes'] === '') {
        $errors[] = 'Duration is required.';
    } elseif (!ctype_digit($fields['duration_minutes'])) {
        $errors[] = 'Duration must be a number.';
    }

    $finfo   = new finfo(FILEINFO_MIME_TYPE);
    $allowed  = ['image/jpeg', 'image/png', 'image/webp'];
    $maxBytes = 2 * 1024 * 1024;
    $extMap   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    // Poster upload
    $posterPath = null;
    if (empty($_FILES['poster']['name'])) {
        $errors[] = 'Poster is required.';
    } else {
        $file = $_FILES['poster'];
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $allowed, true)) {
            $errors[] = 'Poster must be JPG, PNG, or WebP.';
        } elseif ($file['size'] > $maxBytes) {
            $errors[] = 'Poster must be under 2 MB.';
        } else {
            $filename  = bin2hex(random_bytes(12)) . '.' . $extMap[$mime];
            $uploadDir = __DIR__ . '/../../uploads/posters/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
            move_uploaded_file($file['tmp_name'], $uploadDir . $filename);
            $posterPath = 'uploads/posters/' . $filename;
        }
    }

    // Banner upload
    $bannerPath = null;
    if (empty($_FILES['banner']['name'])) {
        $errors[] = 'Banner is required.';
    } else {
        $bfile = $_FILES['banner'];
        $bmime = $finfo->file($bfile['tmp_name']);
        if (!in_array($bmime, $allowed, true)) {
            $errors[] = 'Banner must be JPG, PNG, or WebP.';
        } elseif ($bfile['size'] > $maxBytes) {
            $errors[] = 'Banner must be under 2 MB.';
        } else {
            $bfilename  = bin2hex(random_bytes(12)) . '.' . $extMap[$bmime];
            $buploadDir = __DIR__ . '/../../uploads/banner/';
            if (!is_dir($buploadDir)) mkdir($buploadDir, 0775, true);
            move_uploaded_file($bfile['tmp_name'], $buploadDir . $bfilename);
            $bannerPath = 'uploads/banner/' . $bfilename;
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO movies (title, description, release_year, duration_minutes, poster_path, banner_path)
             VALUES (:t, :d, :y, :dur, :p, :b)"
        );
        $stmt->execute([
            ':t'   => $fields['title'],
            ':d'   => $fields['description'],
            ':y'   => (int)$fields['release_year'],
            ':dur' => (int)$fields['duration_minutes'],
            ':p'   => $posterPath,
            ':b'   => $bannerPath,
        ]);
        $newId = (int) $pdo->lastInsertId();
        flash_set('success', 'Movie created. Now assign genres, actors, and directors.');
        redirect('/admin/movies/edit.php?id=' . $newId);
    }
}

$pageTitle = 'Add Movie';
require_once __DIR__ . '/../../app/views/partials/header_admin.php';
?>

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
        <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
        <textarea name="description" class="form-control" rows="4" required><?= e($fields['description']) ?></textarea>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Release Year <span class="text-danger">*</span></label>
            <input type="number" name="release_year" class="form-control" min="1888" max="2099"
                value="<?= e($fields['release_year']) ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Duration (minutes) <span class="text-danger">*</span></label>
            <input type="number" name="duration_minutes" class="form-control" min="1"
                value="<?= e($fields['duration_minutes']) ?>" required>
        </div>
    </div>
    <div class="mb-4">
        <label class="form-label fw-semibold">Poster <span class="text-danger">*</span> <small class="text-muted">(JPG/PNG/WebP, max 2 MB)</small></label>
        <input type="file" name="poster" class="form-control" accept="image/jpeg,image/png,image/webp" required>
    </div>
    <div class="mb-4">
        <label class="form-label fw-semibold">Banner <span class="text-danger">*</span> <small class="text-muted">(JPG/PNG/WebP, max 2 MB)</small></label>
        <input type="file" name="banner" class="form-control" accept="image/jpeg,image/png,image/webp" required>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Create Movie</button>
        <a href="/admin/movies/index.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<?php require_once __DIR__ . '/../../app/views/partials/footer_admin.php'; ?>