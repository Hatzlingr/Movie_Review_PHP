<?php
// admin/directors/index.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/helpers/functions.php';
require_once __DIR__ . '/../../app/helpers/flash.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

ensure_session();
require_admin();

$errors = [];

// Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $errors[] = 'Director name is required.';
    } else {
        $photoPath = null;
        if (!empty($_FILES['photo']['name'])) {
            $file    = $_FILES['photo'];
            $finfo   = new finfo(FILEINFO_MIME_TYPE);
            $mime    = $finfo->file($file['tmp_name']);
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            $extMap  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!in_array($mime, $allowed, true)) {
                $errors[] = 'Photo must be JPG, PNG, or WebP.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Photo must be under 2 MB.';
            } else {
                $filename  = bin2hex(random_bytes(12)) . '.' . $extMap[$mime];
                $uploadDir = __DIR__ . '/../../uploads/directors/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
                move_uploaded_file($file['tmp_name'], $uploadDir . $filename);
                $photoPath = 'uploads/directors/' . $filename;
            }
        }
        if (empty($errors)) {
            try {
                $pdo->prepare("INSERT INTO directors (name, photo_path) VALUES (:n, :p)")
                    ->execute([':n' => $name, ':p' => $photoPath]);
                flash_set('success', 'Director added.');
            } catch (PDOException $e) {
                $errors[] = 'Director name already exists.';
            }
            if (empty($errors)) {
                redirect('/admin/directors/index.php');
            }
        }
    }
}

// Delete
if (isset($_GET['delete'])) {
    $delId    = (int) $_GET['delete'];
    $row      = $pdo->prepare("SELECT photo_path FROM directors WHERE id = :id");
    $row->execute([':id' => $delId]);
    $oldPhoto = $row->fetchColumn();
    if ($oldPhoto && file_exists(__DIR__ . '/../../' . $oldPhoto)) {
        @unlink(__DIR__ . '/../../' . $oldPhoto);
    }
    $pdo->prepare("DELETE FROM directors WHERE id = :id")->execute([':id' => $delId]);
    flash_set('success', 'Director deleted.');
    redirect('/admin/directors/index.php');
}

$directors = $pdo->query(
    "SELECT d.id, d.name, d.photo_path, COUNT(md.movie_id) AS movie_count
     FROM directors d LEFT JOIN movie_directors md ON md.director_id = d.id
     GROUP BY d.id ORDER BY d.name"
)->fetchAll();

$pageTitle = 'Manage Directors';
require_once __DIR__ . '/../../app/views/partials/header_admin.php';
?>

<h4 class="fw-bold mb-4"><i class="bi bi-megaphone"></i> Directors</h4>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <h6 class="fw-semibold mb-3">Add Director</h6>
        <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small mb-1">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="Director name" required autofocus maxlength="100">
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

<?php if (empty($directors)): ?>
    <div class="alert alert-info">No directors yet.</div>
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
            <?php foreach ($directors as $d): ?>
                <tr>
                    <td>
                        <?php if (!empty($d['photo_path']) && file_exists(__DIR__ . '/../../' . $d['photo_path'])): ?>
                            <img src="/<?= e($d['photo_path']) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:50%" alt="">
                        <?php else: ?>
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:50%;background:#e9ecef;color:#adb5bd;font-size:1.3rem;"><i class="bi bi-person-fill"></i></span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($d['name']) ?></td>
                    <td><span class="badge bg-secondary"><?= (int)$d['movie_count'] ?></span></td>
                    <td class="text-end">
                        <a href="?delete=<?= (int)$d['id'] ?>" class="btn btn-sm btn-outline-danger"
                            onclick="return confirm('Delete director: <?= e(addslashes($d['name'])) ?>?')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../../app/views/partials/footer_admin.php'; ?>