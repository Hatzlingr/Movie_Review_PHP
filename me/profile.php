<?php
// me/profile.php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/db.php';

ensure_session();
require_login();

$currentUser = current_user();
$userId = $currentUser['id'];


// Fetch complete user data including bio
$stmt = $pdo->prepare("
    SELECT id, username, email, profile_photo, bio, role, created_at
    FROM users WHERE id = ? LIMIT 1
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$pageTitle = 'My Profile';
require_once __DIR__ . '/../app/views/partials/header.php';
require_once __DIR__ . '/../app/views/partials/navbar.php';
?>

<main class="container py-5 mt-5">
    <?php flash_render(); ?>

    <!-- Profile hero card -->
    <div class="profile-hero card p-4" style="background: linear-gradient(180deg, rgba(46,23,60,0.95), rgba(33,18,46,0.95)); border-radius:14px; border:1px solid rgba(255,255,255,0.04);">
        <div class="row g-0 align-items-center">
            <div class="col-lg-6 col-md-12">
                <div class="d-flex gap-4 align-items-start">
                    <div class="profile-avatar">
                        <?php if ($user['profile_photo']): ?>
                            <img src="<?= e(imageUrl($user['profile_photo'], 'avatar')) ?>" alt="<?= e($user['username']) ?>"
                                 class="rounded-circle" style="width:96px;height:96px;object-fit:cover;border:4px solid rgba(255,255,255,0.03)">
                        <?php else: ?>
                            <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center" style="width:96px;height:96px;">
                                <i class="fas fa-user fa-2x text-white-50"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2 class="mb-1" style="color:#fff; font-weight:700; font-size:1.25rem"><?= e($user['username']) ?></h2>
                                <div class="text-muted small mb-2" style="color:rgba(255,255,255,0.6)"><i class="fas fa-calendar me-1"></i> Member since <?= date('M d, Y', strtotime($user['created_at'])) ?></div>
                            </div>
                            <div>
                                <a href="/auth/logout.php" class="btn btn-sm" style="background:#d9534f;color:#fff;border-radius:20px;padding:.35rem .9rem">Log Out</a>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2 mt-2">
                            <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'info' ?>"><?= e(ucfirst($user['role'])) ?></span>
                        </div>

                        <p class="mt-3 mb-0" style="color:rgba(255,255,255,0.8);max-width:620px;">
                            <?php if ($user['bio']): ?>
                                <?= e($user['bio']) ?>
                            <?php else: ?>
                                <em style="color:rgba(255,255,255,0.5)">No bio added yet</em>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-12">
                <div style="height:100%;display:flex;align-items:center;justify-content:center;padding-left:28px;padding-right:8px">
                    <form method="post" action="/me/profile_edit.php" style="width:100%;max-width:420px">
                        <div class="mb-3">
                            <label class="form-label text-white-50 small">Email</label>
                            <input type="email" name="email" class="form-control form-control-sm" value="<?= e($user['email']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white-50 small">Bio</label>
                            <textarea name="bio" class="form-control form-control-sm" rows="3"><?= e($user['bio'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white-50 small">New Password</label>
                            <input type="password" name="password" class="form-control form-control-sm" placeholder="Leave blank to keep current">
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-sm" style="background:#6c3fc6;color:#fff;border-radius:20px;padding:.4rem .9rem">Save Change</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</main>

<?php 
if ($user['role'] === 'user') {
    require_once __DIR__ . '/../me/watchlist.php';
    require_once __DIR__ . '/../me/reviews.php';
}
require_once __DIR__ . '/../app/views/partials/footer.php'; 
?>