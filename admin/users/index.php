<?php
// admin/users/index.php
declare(strict_types=1);

require_once __DIR__ . '/../../app/helpers/functions.php';
require_once __DIR__ . '/../../app/helpers/flash.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/config/db.php';

ensure_session();
require_admin();

// Toggle role
if (isset($_GET['toggle_role'])) {
    $uid = (int) $_GET['toggle_role'];
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

$users = $pdo->query(
    "SELECT u.id, u.username, u.email, u.role, u.created_at,
            COUNT(DISTINCT rv.id)  AS review_count,
            COUNT(DISTINCT rt.id)  AS rating_count
     FROM users u
     LEFT JOIN reviews rv ON rv.user_id = u.id
     LEFT JOIN ratings  rt ON rt.user_id = u.id
     GROUP BY u.id
     ORDER BY u.created_at DESC"
)->fetchAll();

$pageTitle = 'Manage Users';
require_once __DIR__ . '/../../app/views/partials/header_admin.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-people"></i> Users</h4>
    <span class="badge bg-secondary fs-6"><?= count($users) ?> total</span>
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
                            <a href="?toggle_role=<?= (int)$u['id'] ?>"
                                class="btn btn-sm btn-outline-<?= $u['role'] === 'admin' ? 'warning' : 'success' ?>"
                                onclick="return confirm('Change role of &quot;<?= e(addslashes($u['username'])) ?>&quot; to <?= $u['role'] === 'admin' ? 'user' : 'admin' ?>?')">
                                <?= $u['role'] === 'admin' ? '<i class="bi bi-arrow-down-circle"></i> Make User' : '<i class="bi bi-arrow-up-circle"></i> Make Admin' ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../app/views/partials/footer_admin.php'; ?>