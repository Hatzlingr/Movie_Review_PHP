<?php
// admin/movies/assign_directors.php
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedIds = array_map('intval', $_POST['director_ids'] ?? []);

    $pdo->prepare("DELETE FROM movie_directors WHERE movie_id = :m")->execute([':m' => $id]);

    if (!empty($selectedIds)) {
        $ins = $pdo->prepare("INSERT IGNORE INTO movie_directors (movie_id, director_id) VALUES (:m, :d)");
        foreach ($selectedIds as $did) {
            if ($did > 0) {
                $ins->execute([':m' => $id, ':d' => $did]);
            }
        }
    }
    flash_set('success', 'Directors updated.');
    redirect('/admin/movies/assign_directors.php?id=' . $id);
}

$allDirectors = $pdo->query("SELECT id, name FROM directors ORDER BY name")->fetchAll();

$assignedStmt = $pdo->prepare("SELECT director_id FROM movie_directors WHERE movie_id = :m");
$assignedStmt->execute([':m' => $id]);
$assignedIds = $assignedStmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Assign Directors';
require_once __DIR__ . '/../../app/views/partials/header.php';
?>

<div class="d-flex gap-4 align-items-start">
    <?php require_once __DIR__ . '/../../app/views/partials/sidebar_admin.php'; ?>

    <div class="flex-grow-1" style="max-width:520px">
        <h4 class="fw-bold mb-1"><i class="bi bi-megaphone"></i> Assign Directors</h4>
        <p class="text-muted mb-4">Movie: <strong><?= e($movie['title']) ?></strong></p>

        <?php if (empty($allDirectors)): ?>
            <div class="alert alert-warning">No directors yet. <a href="/admin/directors/index.php">Add directors first.</a></div>
        <?php else: ?>
            <form method="post">
                <div class="row g-2 mb-4">
                    <?php foreach ($allDirectors as $d): ?>
                        <div class="col-6 col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="director_ids[]"
                                    id="d<?= $d['id'] ?>" value="<?= (int)$d['id'] ?>"
                                    <?= in_array((int)$d['id'], $assignedIds, true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="d<?= $d['id'] ?>"><?= e($d['name']) ?></label>
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