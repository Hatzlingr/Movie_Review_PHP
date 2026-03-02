<?php
// admin/movies/index.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/helpers/functions.php';
require_once __DIR__ . '/../../app/helpers/flash.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/helpers/pagination.php';
require_once __DIR__ . '/../../app/config/db.php';

ensure_session();
require_admin();

$perPage = 20;
$total   = (int)$pdo->query("SELECT COUNT(*) FROM movies")->fetchColumn();
[$page, $pages, $offset] = calc_pagination($total, $perPage, (int)($_GET['page'] ?? 1));

$stmt = $pdo->prepare(
    "SELECT m.id, m.title, m.release_year, m.duration_minutes, m.poster_path, m.created_at,
            ROUND(AVG(r.score),1) AS avg_rating, COUNT(r.id) AS total_ratings
     FROM movies m
     LEFT JOIN ratings r ON r.movie_id = m.id
     GROUP BY m.id
     ORDER BY m.created_at DESC
     LIMIT :limit OFFSET :offset"
);
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$movies = $stmt->fetchAll();

$pageTitle = 'Manage Movies';
require_once __DIR__ . '/../../app/views/partials/header_admin.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-camera-video"></i> Movies <span class="badge bg-secondary fs-6 ms-2"><?= $total ?></span></h4>
    <a href="/admin/movies/create.php" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Add Movie
    </a>
</div>

<?php if (empty($movies)): ?>
    <div class="alert alert-info">No movies yet.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Poster</th>
                    <th>Title</th>
                    <th>Year</th>
                    <th>Duration</th>
                    <th>Rating</th>
                    <th>Added</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movies as $m): ?>
                    <tr>
                        <td style="width:56px">
                            <img src="<?= e(imageUrl($m['poster_path'], 'poster')) ?>" style="width:44px;height:66px;object-fit:cover;border-radius:4px" alt="">
                        </td>
                        <td class="fw-semibold"><?= e($m['title']) ?></td>
                        <td class="text-muted"><?= e($m['release_year'] ?? '—') ?></td>
                        <td class="text-muted"><?= formatDuration((int)($m['duration_minutes'] ?? 0)) ?: '—' ?></td>
                        <td>
                            <?php if ($m['avg_rating']): ?>
                                <span class="score-badge"><i class="bi bi-star-fill"></i> <?= e($m['avg_rating']) ?></span>
                                <small class="text-muted">(<?= (int)$m['total_ratings'] ?>)</small>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?= e(substr($m['created_at'], 0, 10)) ?></td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="/admin/movies/edit.php?id=<?= (int)$m['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form method="post" action="/admin/movies/delete.php" class="d-inline">
                                    <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"
                                        onclick="return confirm('Delete <?= e(addslashes($m['title'])) ?>?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php render_pagination($page, $pages, '/admin/movies/index.php?'); ?>

<?php require_once __DIR__ . '/../../app/views/partials/footer_admin.php'; ?>