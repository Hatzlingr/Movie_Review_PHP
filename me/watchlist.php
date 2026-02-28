<?php
// me/watchlist.php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/db.php';

ensure_session();
require_login();

$userId = current_user()['id'];

// ── Handle inline status update (POST) ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movieId = (int) ($_POST['movie_id'] ?? 0);
    $status  = $_POST['status'] ?? '';
    $allowed = ['plan_to_watch', 'watching', 'completed'];

    if ($movieId > 0 && in_array($status, $allowed, true)) {
        $stmt = $pdo->prepare(
            "UPDATE watchlists SET status = :s
             WHERE user_id = :u AND movie_id = :m"
        );
        $stmt->execute([':s' => $status, ':u' => $userId, ':m' => $movieId]);
        flash_set('success', 'Watchlist status updated.');
    } else {
        flash_set('danger', 'Invalid data.');
    }
    redirect('/me/watchlist.php');
}

// ── Fetch watchlist ──────────────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT w.movie_id, w.status, w.created_at,
            m.title, m.release_year, m.poster_path
     FROM watchlists w
     JOIN movies m ON m.id = w.movie_id
     WHERE w.user_id = :u
     ORDER BY w.created_at DESC"
);
$stmt->execute([':u' => $userId]);
$watchlist = $stmt->fetchAll();

$statusLabels = [
    'plan_to_watch' => ['label' => 'Plan to Watch', 'badge' => 'secondary'],
    'watching'      => ['label' => 'Watching',       'badge' => 'primary'],
    'completed'     => ['label' => 'Completed',      'badge' => 'success'],
];

$pageTitle = 'My Watchlist';
require_once __DIR__ . '/../app/views/partials/header.php';
require_once __DIR__ . '/../app/views/partials/navbar.php';
?>
<main class="container mt-5 pt-5 pb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="bi bi-bookmark-star"></i> My Watchlist</h4>
        <span class="badge bg-secondary fs-6"><?= count($watchlist) ?> title<?= count($watchlist) !== 1 ? 's' : '' ?></span>
    </div>

    <?php if (empty($watchlist)): ?>
        <div class="alert alert-info">Your watchlist is empty. <a href="/public/index.php">Browse movies</a> to add some!</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Poster</th>
                        <th>Title</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th>Added</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($watchlist as $row): ?>
                        <tr>
                            <td style="width:60px">
                                <?php if ($row['poster_path'] && file_exists(__DIR__ . '/../public/' . $row['poster_path'])): ?>
                                    <img src="/public/<?= e($row['poster_path']) ?>"
                                        alt="<?= e($row['title']) ?>"
                                        style="width:48px;height:72px;object-fit:cover;border-radius:4px">
                                <?php else: ?>
                                    <div class="bg-secondary d-flex align-items-center justify-content-center text-white"
                                        style="width:48px;height:72px;border-radius:4px;font-size:1.4rem">
                                        <i class="bi bi-film"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/public/movie.php?id=<?= (int)$row['movie_id'] ?>" class="fw-semibold text-decoration-none">
                                    <?= e($row['title']) ?>
                                </a>
                            </td>
                            <td class="text-muted"><?= e($row['release_year'] ?? '—') ?></td>
                            <td>
                                <form method="post" class="d-flex align-items-center gap-2">
                                    <input type="hidden" name="movie_id" value="<?= (int)$row['movie_id'] ?>">
                                    <select name="status" class="form-select form-select-sm" style="width:auto"
                                        onchange="this.form.submit()">
                                        <?php foreach ($statusLabels as $val => $info): ?>
                                            <option value="<?= $val ?>" <?= $row['status'] === $val ? 'selected' : '' ?>>
                                                <?= $info['label'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td class="text-muted small"><?= e(substr($row['created_at'], 0, 10)) ?></td>
                            <td class="text-end">
                                <a href="/action/watchlist_remove.php?movie_id=<?= (int)$row['movie_id'] ?>&redirect=<?= urlencode('/me/watchlist.php') ?>"
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Remove from watchlist?')">
                                    <i class="bi bi-trash"></i> Remove
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/../app/views/partials/footer.php'; ?>