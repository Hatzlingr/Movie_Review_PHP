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
        try {
            $pdo->prepare("INSERT INTO directors (name) VALUES (:n)")->execute([':n' => $name]);
            flash_set('success', 'Director added.');
        } catch (PDOException $e) {
            $errors[] = 'Director name already exists.';
        }
        if (empty($errors)) {
            redirect('/admin/directors/index.php');
        }
    }
}

// Delete
if (isset($_GET['delete'])) {
    $delId = (int) $_GET['delete'];
    $pdo->prepare("DELETE FROM directors WHERE id = :id")->execute([':id' => $delId]);
    flash_set('success', 'Director deleted.');
    redirect('/admin/directors/index.php');
}

$directors = $pdo->query(
    "SELECT d.id, d.name, COUNT(md.movie_id) AS movie_count
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
        <form method="post" class="d-flex gap-2">
            <input type="hidden" name="action" value="create">
            <input type="text" name="name" class="form-control" placeholder="Director name" required autofocus maxlength="150">
            <button type="submit" class="btn btn-primary btn-sm px-3">Add</button>
        </form>
    </div>
</div>

<?php if (empty($directors)): ?>
    <div class="alert alert-info">No directors yet.</div>
<?php else: ?>
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>Name</th>
                <th>Movies</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($directors as $d): ?>
                <tr>
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