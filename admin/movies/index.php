<?php
// admin/movies/index.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/helpers/functions.php';
require_once __DIR__ . '/../../app/helpers/flash.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

ensure_session();
require_admin();

$movies = $pdo->query(
    "SELECT m.id, m.title, m.release_year, m.duration_minutes, m.poster_path, m.created_at,
            ROUND(AVG(r.score),1) AS avg_rating, COUNT(r.id) AS total_ratings
     FROM movies m
     LEFT JOIN ratings r ON r.movie_id = m.id
     GROUP BY m.id
     ORDER BY m.created_at DESC"
)->fetchAll();

$pageTitle = 'Manage Movies';
require_once __DIR__ . '/../../app/views/partials/header_admin.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-camera-video"></i> Movies</h4>
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
                            <?php if ($m['poster_path'] && file_exists(__DIR__ . '/../../' . $m['poster_path'])): ?>
                                <img src="/<?= e($m['poster_path']) ?>" style="width:44px;height:66px;object-fit:cover;border-radius:4px" alt="">
                            <?php else: ?>
                                <div class="bg-secondary text-white d-flex align-items-center justify-content-center"
                                    style="width:44px;height:66px;border-radius:4px;font-size:1.2rem">
                                    <i class="bi bi-film"></i>
                                </div>
                            <?php endif; ?>
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
                            <div class="btn-group btn-group-sm">
                                <a href="/admin/movies/edit.php?id=<?= (int)$m['id'] ?>" class="btn btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <a href="/admin/movies/delete.php?id=<?= (int)$m['id'] ?>" class="btn btn-outline-danger" title="Delete"
                                    onclick="return confirm('Delete <?= e(addslashes($m['title'])) ?>?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../app/views/partials/footer_admin.php'; ?>