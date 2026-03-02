<?php
// admin/users/index.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/helpers/functions.php';
require_once __DIR__ . '/../../app/helpers/flash.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/helpers/pagination.php';
require_once __DIR__ . '/../../app/config/db.php';

ensure_session();
require_admin();

// Toggle role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_role') {
    $uid = (int) ($_POST['id'] ?? 0);
    if ($uid !== current_user()['id']) {    // Prevent demoting yourself
        $cur = $pdo->prepare("SELECT role FROM users WHERE id = :id");
        $cur->execute([':id' => $uid]);
        $curRole = $cur->fetchColumn();
        $newRole = $curRole === 'admin' ? 'user' : 'admin';
        $pdo->prepare("UPDATE users SET role = :r WHERE id = :id")->execute([':r' => $newRole, ':id' => $uid]);
        flash_set('success', 'User role updated to ' . $newRole . '.');
    } else {
        flash_set('warning', 'You cannot change your own role.');
    }
    redirect('/admin/users/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_user') {
    $uid = (int) ($_POST['id'] ?? 0);
    if ($uid !== current_user()['id']) {    // Prevent deleting yourself
        $pdo->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $uid]);
        flash_set('success', 'User deleted successfully.');
    } else {
        flash_set('warning', 'You cannot delete your own account.');
    }
    redirect('/admin/users/index.php');
}

$perPage    = 25;
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
[$page, $pages, $offset] = calc_pagination($totalUsers, $perPage, (int)($_GET['page'] ?? 1));

$stmt = $pdo->prepare(
    "SELECT u.id, u.username, u.email, u.role, u.created_at,
            COUNT(DISTINCT rv.id) AS review_count,
            COUNT(DISTINCT rt.id) AS rating_count
     FROM users u
     LEFT JOIN reviews rv ON rv.user_id = u.id
     LEFT JOIN ratings  rt ON rt.user_id = u.id
     GROUP BY u.id
     ORDER BY u.created_at DESC
     LIMIT :limit OFFSET :offset"
);
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll();

$pageTitle = 'Manage Users';
require_once __DIR__ . '/../../app/views/partials/header_admin.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-people"></i> Users</h4>
    <span class="badge bg-secondary fs-6"><?= $totalUsers ?> total</span>
</div>

<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Reviews</th>
                <th>Ratings</th>
                <th>Joined</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <?php $isSelf = $u['id'] === current_user()['id']; ?>
                <tr>
                    <td class="text-muted small"><?= (int)$u['id'] ?></td>
                    <td class="fw-semibold">
                        <?= e($u['username']) ?>
                        <?php if ($isSelf): ?>
                            <span class="badge bg-info text-dark ms-1 small">you</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted small"><?= e($u['email']) ?></td>
                    <td>
                        <span class="badge bg-<?= $u['role'] === 'admin' ? 'danger' : 'secondary' ?>">
                            <?= e($u['role']) ?>
                        </span>
                    </td>
                    <td><?= (int)$u['review_count'] ?></td>
                    <td><?= (int)$u['rating_count'] ?></td>
                    <td class="text-muted small"><?= e(substr($u['created_at'], 0, 10)) ?></td>
                    <td class="text-end">
                        <?php if (!$isSelf): ?>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="action" value="toggle_role">
                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-<?= $u['role'] === 'admin' ? 'warning' : 'success' ?>"
                                    onclick="return confirm('Change role of &quot;<?= e(addslashes($u['username'])) ?>&quot; to <?= $u['role'] === 'admin' ? 'user' : 'admin' ?>?')">
                                    <?= $u['role'] === 'admin' ? '<i class="bi bi-arrow-down-circle"></i> Make User' : '<i class="bi bi-arrow-up-circle"></i> Make Admin' ?>
                                </button>
                            </form>
                            <form method="post" class="d-inline ms-1">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Delete user &quot;<?= e(addslashes($u['username'])) ?>&quot;? This will also delete all their reviews, ratings, and watchlist.')">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php render_pagination($page, $pages, '/admin/users/index.php?'); ?>

<?php require_once __DIR__ . '/../../app/views/partials/footer_admin.php'; ?>