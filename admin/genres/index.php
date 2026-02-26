<?php
// admin/genres/index.php
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
        $errors[] = 'Genre name is required.';
    } else {
        try {
            $pdo->prepare("INSERT INTO genres (name) VALUES (:n)")->execute([':n' => $name]);
            flash_set('success', 'Genre added.');
        } catch (PDOException $e) {
            $errors[] = 'Genre name already exists.';
        }
        if (empty($errors)) {
            redirect('/admin/genres/index.php');
        }
    }
}

// Delete
if (isset($_GET['delete'])) {
    $delId = (int) $_GET['delete'];
    $pdo->prepare("DELETE FROM genres WHERE id = :id")->execute([':id' => $delId]);
    flash_set('success', 'Genre deleted.');
    redirect('/admin/genres/index.php');
}

$genres = $pdo->query(
    "SELECT g.id, g.name, COUNT(mg.movie_id) AS movie_count
     FROM genres g LEFT JOIN movie_genres mg ON mg.genre_id = g.id
     GROUP BY g.id ORDER BY g.name"
)->fetchAll();

$pageTitle = 'Manage Genres';
require_once __DIR__ . '/../../app/views/partials/header_admin.php';
?>

<h4 class="fw-bold mb-4"><i class="bi bi-tags"></i> Genres</h4>

<!-- Add form -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <h6 class="fw-semibold mb-3">Add Genre</h6>
        <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
        <form method="post" class="d-flex gap-2">
            <input type="hidden" name="action" value="create">
            <input type="text" name="name" class="form-control" placeholder="Genre name" required autofocus maxlength="100">
            <button type="submit" class="btn btn-primary btn-sm px-3">Add</button>
        </form>
    </div>
</div>

<!-- List -->
<?php if (empty($genres)): ?>
    <div class="alert alert-info">No genres yet.</div>
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
            <?php foreach ($genres as $g): ?>
                <tr>
                    <td><?= e($g['name']) ?></td>
                    <td><span class="badge bg-secondary"><?= (int)$g['movie_count'] ?></span></td>
                    <td class="text-end">
                        <a href="?delete=<?= (int)$g['id'] ?>" class="btn btn-sm btn-outline-danger"
                            onclick="return confirm('Delete genre: <?= e(addslashes($g['name'])) ?>?')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../../app/views/partials/footer_admin.php'; ?>