<?php
// admin/actors/index.php
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
        $errors[] = 'Actor name is required.';
    } else {
        try {
            $pdo->prepare("INSERT INTO actors (name) VALUES (:n)")->execute([':n' => $name]);
            flash_set('success', 'Actor added.');
        } catch (PDOException $e) {
            $errors[] = 'Actor name already exists.';
        }
        if (empty($errors)) {
            redirect('/admin/actors/index.php');
        }
    }
}

// Delete
if (isset($_GET['delete'])) {
    $delId = (int) $_GET['delete'];
    $pdo->prepare("DELETE FROM actors WHERE id = :id")->execute([':id' => $delId]);
    flash_set('success', 'Actor deleted.');
    redirect('/admin/actors/index.php');
}

$actors = $pdo->query(
    "SELECT a.id, a.name, COUNT(ma.movie_id) AS movie_count
     FROM actors a LEFT JOIN movie_actors ma ON ma.actor_id = a.id
     GROUP BY a.id ORDER BY a.name"
)->fetchAll();

$pageTitle = 'Manage Actors';
require_once __DIR__ . '/../../app/views/partials/header_admin.php';
?>

<h4 class="fw-bold mb-4"><i class="bi bi-person-video3"></i> Actors</h4>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <h6 class="fw-semibold mb-3">Add Actor</h6>
        <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
        <form method="post" class="d-flex gap-2">
            <input type="hidden" name="action" value="create">
            <input type="text" name="name" class="form-control" placeholder="Actor name" required autofocus maxlength="150">
            <button type="submit" class="btn btn-primary btn-sm px-3">Add</button>
        </form>
    </div>
</div>

<?php if (empty($actors)): ?>
    <div class="alert alert-info">No actors yet.</div>
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
            <?php foreach ($actors as $a): ?>
                <tr>
                    <td><?= e($a['name']) ?></td>
                    <td><span class="badge bg-secondary"><?= (int)$a['movie_count'] ?></span></td>
                    <td class="text-end">
                        <a href="?delete=<?= (int)$a['id'] ?>" class="btn btn-sm btn-outline-danger"
                            onclick="return confirm('Delete actor: <?= e(addslashes($a['name'])) ?>?')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../../app/views/partials/footer_admin.php'; ?>