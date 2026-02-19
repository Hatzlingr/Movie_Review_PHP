<?php
// admin/movies/assign_genres.php
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

// Handle POST – replace all genre assignments
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedIds = array_map('intval', $_POST['genre_ids'] ?? []);

    $pdo->prepare("DELETE FROM movie_genres WHERE movie_id = :m")->execute([':m' => $id]);

    if (!empty($selectedIds)) {
        $ins = $pdo->prepare("INSERT IGNORE INTO movie_genres (movie_id, genre_id) VALUES (:m, :g)");
        foreach ($selectedIds as $gid) {
            $ins->execute([':m' => $id, ':g' => $gid]);
        }
    }
    flash_set('success', 'Genres updated.');
    redirect('/admin/movies/assign_genres.php?id=' . $id);
}

// All genres
$allGenres = $pdo->query("SELECT id, name FROM genres ORDER BY name")->fetchAll();

// Currently assigned
$assigned = $pdo->prepare("SELECT genre_id FROM movie_genres WHERE movie_id = :m");
$assigned->execute([':m' => $id]);
$assignedIds = $assigned->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Assign Genres';
require_once __DIR__ . '/../../app/views/partials/header.php';
?>

<div class="d-flex gap-4 align-items-start">
    <?php require_once __DIR__ . '/../../app/views/partials/sidebar_admin.php'; ?>

    <div class="flex-grow-1" style="max-width:520px">
        <h4 class="fw-bold mb-1"><i class="bi bi-tags"></i> Assign Genres</h4>
        <p class="text-muted mb-4">Movie: <strong><?= e($movie['title']) ?></strong></p>

        <?php if (empty($allGenres)): ?>
            <div class="alert alert-warning">No genres yet. <a href="/admin/genres/index.php">Add genres first.</a></div>
        <?php else: ?>
            <form method="post">
                <div class="row g-2 mb-4">
                    <?php foreach ($allGenres as $g): ?>
                        <div class="col-6 col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="genre_ids[]"
                                    id="g<?= $g['id'] ?>" value="<?= (int)$g['id'] ?>"
                                    <?= in_array((int)$g['id'], $assignedIds, true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="g<?= $g['id'] ?>"><?= e($g['name']) ?></label>
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