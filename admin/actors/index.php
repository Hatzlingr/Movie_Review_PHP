<?php
// admin/actors/index.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/helpers/functions.php';
require_once __DIR__ . '/../../app/helpers/flash.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/helpers/pagination.php';
require_once __DIR__ . '/../../app/config/db.php';

ensure_session();
require_admin();

$errors = [];

// Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $errors[] = 'Actor name is required.';
    } elseif (mb_strlen($name) > 100) {
        $errors[] = 'Actor name must be 100 characters or fewer.';
    } else {
        $photoPath = null;
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
                    $photoPath = 'uploads/actors/' . $filename;
                }
            }
        }
        if (empty($errors)) {
            try {
                $pdo->prepare("INSERT INTO actors (name, photo_path) VALUES (:n, :p)")
                    ->execute([':n' => $name, ':p' => $photoPath]);
                flash_set('success', 'Actor added.');
            } catch (PDOException $e) {
                $errors[] = 'Actor name already exists.';
            }
            if (empty($errors)) {
                redirect('/admin/actors/index.php');
            }
        }
    }
}

$perPage     = 20;
$totalActors = (int)$pdo->query("SELECT COUNT(*) FROM actors")->fetchColumn();
[$page, $pages, $offset] = calc_pagination($totalActors, $perPage, (int)($_GET['page'] ?? 1));

$stmt = $pdo->prepare(
    "SELECT a.id, a.name, a.photo_path, COUNT(ma.movie_id) AS movie_count
     FROM actors a LEFT JOIN movie_actors ma ON ma.actor_id = a.id
     GROUP BY a.id ORDER BY a.name
     LIMIT :limit OFFSET :offset"
);
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$actors = $stmt->fetchAll();

$pageTitle = 'Manage Actors';
require_once __DIR__ . '/../../app/views/partials/header_admin.php';
?>

<h4 class="fw-bold mb-4"><i class="bi bi-person-video3"></i> Actors <span class="badge bg-secondary fs-6 ms-2"><?= $totalActors ?></span></h4>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <h6 class="fw-semibold mb-3">Add Actor</h6>
        <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small mb-1">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="Actor name" required autofocus maxlength="100">
                </div>
                <div class="col-md-5">
                    <label class="form-label small mb-1">Photo <small class="text-muted">(JPG/PNG/WebP, max 2 MB)</small></label>
                    <input type="file" name="photo" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Add</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (empty($actors)): ?>
    <div class="alert alert-info">No actors yet.</div>
<?php else: ?>
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>Photo</th>
                <th>Name</th>
                <th>Movies</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($actors as $a): ?>
                <tr>
                    <td>
                        <img src="<?= e(imageUrl($a['photo_path'], 'actor')) ?>"
                            style="width:40px;height:40px;object-fit:cover;border-radius:50%" alt="">
                    </td>
                    <td><?= e($a['name']) ?></td>
                    <td><span class="badge bg-secondary"><?= (int)$a['movie_count'] ?></span></td>
                    <td class="text-end">
                        <a href="/admin/actors/edit.php?id=<?= (int)$a['id'] ?>"
                            class="btn btn-sm btn-outline-secondary me-1">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="post" action="/admin/actors/delete.php" class="d-inline">
                            <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Delete actor: <?= e(addslashes($a['name'])) ?>?')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php render_pagination($page, $pages, '/admin/actors/index.php?'); ?>

<?php require_once __DIR__ . '/../../app/views/partials/footer_admin.php'; ?>