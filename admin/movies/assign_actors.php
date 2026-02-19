<?php
// admin/movies/assign_actors.php
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

$movieStmt = $pdo->prepare("SELECT id, title FROM movies WHERE id = :id");
$movieStmt->execute([':id' => $id]);
$movie = $movieStmt->fetch();
if (!$movie) {
    flash_set('danger', 'Movie not found.');
    redirect('/admin/movies/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actorIds  = array_map('intval', $_POST['actor_ids']   ?? []);
    $roleNames = $_POST['role_names'] ?? [];

    $pdo->prepare("DELETE FROM movie_actors WHERE movie_id = :m")->execute([':m' => $id]);

    if (!empty($actorIds)) {
        $ins = $pdo->prepare(
            "INSERT IGNORE INTO movie_actors (movie_id, actor_id, role_name) VALUES (:m, :a, :r)"
        );
        foreach ($actorIds as $i => $aid) {
            if ($aid <= 0) {
                continue;
            }
            $role = trim($roleNames[$i] ?? '');
            $ins->execute([':m' => $id, ':a' => $aid, ':r' => $role ?: null]);
        }
    }
    flash_set('success', 'Actors updated.');
    redirect('/admin/movies/assign_actors.php?id=' . $id);
}

$allActors = $pdo->query("SELECT id, name FROM actors ORDER BY name")->fetchAll();

// Currently assigned with role names
$assignedStmt = $pdo->prepare(
    "SELECT a.id, a.name, ma.role_name
     FROM actors a JOIN movie_actors ma ON ma.actor_id = a.id
     WHERE ma.movie_id = :m ORDER BY a.name"
);
$assignedStmt->execute([':m' => $id]);
$assignedActors = $assignedStmt->fetchAll();
$assignedMap    = array_column($assignedActors, 'role_name', 'id'); // id => role_name

$pageTitle = 'Assign Actors';
require_once __DIR__ . '/../../app/views/partials/header.php';
?>

<div class="d-flex gap-4 align-items-start">
    <?php require_once __DIR__ . '/../../app/views/partials/sidebar_admin.php'; ?>

    <div class="flex-grow-1" style="max-width:680px">
        <h4 class="fw-bold mb-1"><i class="bi bi-person-video3"></i> Assign Actors</h4>
        <p class="text-muted mb-4">Movie: <strong><?= e($movie['title']) ?></strong></p>

        <?php if (empty($allActors)): ?>
            <div class="alert alert-warning">No actors yet. <a href="/admin/actors/index.php">Add actors first.</a></div>
        <?php else: ?>
            <form method="post">
                <p class="text-muted small mb-3">Check an actor and optionally enter their character name.</p>
                <div class="row g-2 mb-4">
                    <?php foreach ($allActors as $a): ?>
                        <?php $checked = array_key_exists((int)$a['id'], $assignedMap); ?>
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
                                    value="<?= e($assignedMap[(int)$a['id']] ?? '') ?>">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
                    <a href="/admin/movies/index.php" class="btn btn-outline-secondary">Back to Movies</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/views/partials/footer.php'; ?>