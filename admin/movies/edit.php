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
    // --- Basic fields ---
    $fields['title']            = trim($_POST['title']            ?? '');
    $fields['description']      = trim($_POST['description']      ?? '');
    $fields['release_year']     = trim($_POST['release_year']     ?? '');
    $fields['duration_minutes'] = trim($_POST['duration_minutes'] ?? '');

    if ($fields['title'] === '') {
        $errors[] = 'Title is required.';
    } elseif (mb_strlen($fields['title']) > 255) {
        $errors[] = 'Title must be 255 characters or fewer.';
    }
    if ($fields['release_year'] !== '' && !ctype_digit($fields['release_year'])) {
        $errors[] = 'Release year must be a number.';
    } elseif ($fields['release_year'] !== '') {
        $year = (int) $fields['release_year'];
        if ($year < 1888 || $year > 2099) {
            $errors[] = 'Release year must be between 1888 and 2099.';
        }
    }
    if ($fields['duration_minutes'] !== '' && !ctype_digit($fields['duration_minutes'])) {
        $errors[] = 'Duration must be a number.';
    } elseif ($fields['duration_minutes'] !== '') {
        $dur = (int) $fields['duration_minutes'];
        if ($dur < 1 || $dur > 9999) {
            $errors[] = 'Duration must be between 1 and 9999 minutes.';
        }
    }

    // --- Poster ---
    $oldPosterPath = $movie['poster_path'];
    $posterPath    = $movie['poster_path'];
    if (!empty($_FILES['poster']['name'])) {
        $file = $_FILES['poster'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Poster upload failed (error code: ' . $file['error'] . ').';
        } else {
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
                $uploadDir = upload_path('uploads/posters/');
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
                if (!move_uploaded_file($file['tmp_name'], rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $filename)) {
                    $errors[] = 'Failed to save poster file.';
                } else {
                    $posterPath = 'uploads/posters/' . $filename;
                }
            }
        }
    }

    // --- Banner ---
    $oldBannerPath = $movie['banner_path'];
    $bannerPath    = $movie['banner_path'];
    if (!empty($_FILES['banner']['name'])) {
        $bfile = $_FILES['banner'];
        if ($bfile['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Banner upload failed (error code: ' . $bfile['error'] . ').';
        } else {
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            $finfo   = new finfo(FILEINFO_MIME_TYPE);
            $bmime   = $finfo->file($bfile['tmp_name']);
            if (!in_array($bmime, $allowed, true)) {
                $errors[] = 'Banner must be JPG, PNG, or WebP.';
            } elseif ($bfile['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Banner must be under 2 MB.';
            } else {
                $bext       = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$bmime];
                $bfilename  = bin2hex(random_bytes(12)) . '.' . $bext;
                $buploadDir = upload_path('uploads/banner/');
                if (!is_dir($buploadDir)) mkdir($buploadDir, 0775, true);
                if (!move_uploaded_file($bfile['tmp_name'], rtrim($buploadDir, '/\\') . DIRECTORY_SEPARATOR . $bfilename)) {
                    $errors[] = 'Failed to save banner file.';
                } else {
                    $bannerPath = 'uploads/banner/' . $bfilename;
                }
            }
        }
    }

    if (empty($errors)) {
        try {
            // Update movie row
            $upd = $pdo->prepare(
                "UPDATE movies SET title=:t, description=:d, release_year=:y, duration_minutes=:dur, poster_path=:p, banner_path=:b
                 WHERE id=:id"
            );
            $upd->execute([
                ':t'   => $fields['title'],
                ':d'   => $fields['description'] ?: null,
                ':y'   => $fields['release_year'] !== '' ? (int)$fields['release_year'] : null,
                ':dur' => $fields['duration_minutes'] !== '' ? (int)$fields['duration_minutes'] : null,
                ':p'   => $posterPath,
                ':b'   => $bannerPath,
                ':id'  => $id,
            ]);

            // Update genres
            $genreIds = array_map('intval', $_POST['genre_ids'] ?? []);
            $pdo->prepare("DELETE FROM movie_genres WHERE movie_id = :m")->execute([':m' => $id]);
            if (!empty($genreIds)) {
                $ins = $pdo->prepare("INSERT IGNORE INTO movie_genres (movie_id, genre_id) VALUES (:m, :g)");
                foreach ($genreIds as $gid) {
                    if ($gid <= 0) continue;
                    $ins->execute([':m' => $id, ':g' => $gid]);
                }
            }

            // Update directors
            $directorIds = array_map('intval', $_POST['director_ids'] ?? []);
            $pdo->prepare("DELETE FROM movie_directors WHERE movie_id = :m")->execute([':m' => $id]);
            if (!empty($directorIds)) {
                $ins = $pdo->prepare("INSERT IGNORE INTO movie_directors (movie_id, director_id) VALUES (:m, :d)");
                foreach ($directorIds as $did) {
                    if ($did > 0) $ins->execute([':m' => $id, ':d' => $did]);
                }
            }

            // Update actors
            $actorIds  = array_map('intval', $_POST['actor_ids'] ?? []);
            $roleNames = $_POST['role_names'] ?? [];
            $pdo->prepare("DELETE FROM movie_actors WHERE movie_id = :m")->execute([':m' => $id]);
            if (!empty($actorIds)) {
                $ins = $pdo->prepare("INSERT IGNORE INTO movie_actors (movie_id, actor_id, role_name) VALUES (:m, :a, :r)");
                foreach ($actorIds as $i => $aid) {
                    if ($aid <= 0) continue;
                    $role = trim($roleNames[$i] ?? '');
                    $ins->execute([':m' => $id, ':a' => $aid, ':r' => $role ?: null]);
                }
            }

            // DB succeeded — now safe to remove replaced images.
            if ($oldPosterPath && $oldPosterPath !== $posterPath) {
                $oldFile = upload_path($oldPosterPath);
                if (file_exists($oldFile) && !unlink($oldFile)) {
                    error_log('Failed to delete old poster: ' . $oldFile);
                }
            }
            if ($oldBannerPath && $oldBannerPath !== $bannerPath) {
                $oldFile = upload_path($oldBannerPath);
                if (file_exists($oldFile) && !unlink($oldFile)) {
                    error_log('Failed to delete old banner: ' . $oldFile);
                }
            }

            flash_set('success', 'Movie updated.');
            redirect('/admin/movies/index.php?id=' . $id);
        } catch (PDOException $e) {
            error_log('Update movie error: ' . $e->getMessage());
            $errors[] = 'Database error — changes were not saved. Please try again.';
        }
    }
}

// --- Data for form ---
$allGenres = $pdo->query("SELECT id, name FROM genres ORDER BY name")->fetchAll();
$assignedG = $pdo->prepare("SELECT genre_id FROM movie_genres WHERE movie_id = :m");
$assignedG->execute([':m' => $id]);
$assignedGenreIds = array_map('intval', $assignedG->fetchAll(PDO::FETCH_COLUMN));

$allDirectors = $pdo->query("SELECT id, name FROM directors ORDER BY name")->fetchAll();
$assignedD = $pdo->prepare("SELECT director_id FROM movie_directors WHERE movie_id = :m");
$assignedD->execute([':m' => $id]);
$assignedDirectorIds = array_map('intval', $assignedD->fetchAll(PDO::FETCH_COLUMN));

$allActors = $pdo->query("SELECT id, name FROM actors ORDER BY name")->fetchAll();
$assignedA = $pdo->prepare(
    "SELECT a.id, ma.role_name FROM actors a JOIN movie_actors ma ON ma.actor_id = a.id WHERE ma.movie_id = :m"
);
$assignedA->execute([':m' => $id]);
$assignedActorMap = array_column($assignedA->fetchAll(), 'role_name', 'id');

$pageTitle = 'Edit Movie';
require_once __DIR__ . '/../../app/views/partials/header_admin.php';
?>

<h4 class="fw-bold mb-4"><i class="bi bi-pencil-square"></i> Edit: <?= e($movie['title']) ?></h4>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-danger py-2"><?= e($err) ?></div>
<?php endforeach; ?>

<form method="post" enctype="multipart/form-data">

    <!-- ===== Basic Info ===== -->
    <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="bi bi-info-circle me-2"></i>Basic Info</div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" value="<?= e($fields['title']) ?>" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <textarea name="description" class="form-control" rows="4"><?= e($fields['description']) ?></textarea>
            </div>
            <div class="row g-3">
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
        </div>
    </div>

    <!-- ===== Images ===== -->
    <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="bi bi-image me-2"></i>Images</div>
        <div class="card-body row g-4">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Poster <small class="text-muted">(JPG/PNG/WebP, max 2 MB)</small></label>
                <?php if ($movie['poster_path']): ?>
                    <div class="mb-2">
                        <img src="<?= e(imageUrl($movie['poster_path'], 'poster')) ?>" style="height:90px;border-radius:4px" alt="current poster">
                        <small class="text-muted ms-2">Current poster</small>
                    </div>
                <?php endif; ?>
                <input type="file" name="poster" class="form-control" accept="image/jpeg,image/png,image/webp">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Banner <small class="text-muted">(JPG/PNG/WebP, max 2 MB)</small></label>
                <?php if ($movie['banner_path']): ?>
                    <div class="mb-2">
                        <img src="<?= e(imageUrl($movie['banner_path'], 'banner')) ?>" style="height:90px;border-radius:4px" alt="current banner">
                        <small class="text-muted ms-2">Current banner</small>
                    </div>
                <?php endif; ?>
                <input type="file" name="banner" class="form-control" accept="image/jpeg,image/png,image/webp">
            </div>
        </div>
    </div>

    <!-- ===== Genres ===== -->
    <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="bi bi-tags me-2"></i>Genres</div>
        <div class="card-body">
            <?php if (empty($allGenres)): ?>
                <p class="text-muted mb-0">No genres yet. <a href="/admin/genres/index.php">Add genres first.</a></p>
            <?php else: ?>
                <div class="row g-2">
                    <?php foreach ($allGenres as $g): ?>
                        <div class="col-6 col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="genre_ids[]"
                                    id="g<?= $g['id'] ?>" value="<?= (int)$g['id'] ?>"
                                    <?= in_array((int)$g['id'], $assignedGenreIds, true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="g<?= $g['id'] ?>"><?= e($g['name']) ?></label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== Directors ===== -->
    <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="bi bi-megaphone me-2"></i>Directors</div>
        <div class="card-body">
            <?php if (empty($allDirectors)): ?>
                <p class="text-muted mb-0">No directors yet. <a href="/admin/directors/index.php">Add directors first.</a></p>
            <?php else: ?>
                <div class="row g-2">
                    <?php foreach ($allDirectors as $d): ?>
                        <div class="col-6 col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="director_ids[]"
                                    id="d<?= $d['id'] ?>" value="<?= (int)$d['id'] ?>"
                                    <?= in_array((int)$d['id'], $assignedDirectorIds, true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="d<?= $d['id'] ?>"><?= e($d['name']) ?></label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== Actors ===== -->
    <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="bi bi-person-video3 me-2"></i>Actors</div>
        <div class="card-body">
            <?php if (empty($allActors)): ?>
                <p class="text-muted mb-0">No actors yet. <a href="/admin/actors/index.php">Add actors first.</a></p>
            <?php else: ?>
                <p class="text-muted small mb-3">Check an actor and optionally enter their character name.</p>
                <div class="row g-2">
                    <?php foreach ($allActors as $a): ?>
                        <?php $checked = array_key_exists((int)$a['id'], $assignedActorMap); ?>
                        <div class="col-md-6">
                            <div class="input-group input-group-sm">
                                <div class="input-group-text">
                                    <input class="form-check-input mt-0" type="checkbox"
                                        name="actor_ids[]" value="<?= (int)$a['id'] ?>"
                                        <?= $checked ? 'checked' : '' ?>>
                                </div>
                                <span class="input-group-text" style="min-width:140px"><?= e($a['name']) ?></span>
                                <input type="text" name="role_names[]" class="form-control"
                                    placeholder="Role / character"
                                    value="<?= e($assignedActorMap[(int)$a['id']] ?? '') ?>">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-flex gap-2 mb-5">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save All Changes</button>
        <a href="/admin/movies/index.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<?php require_once __DIR__ . '/../../app/views/partials/footer_admin.php'; ?>