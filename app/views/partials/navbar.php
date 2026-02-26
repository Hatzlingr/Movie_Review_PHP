<?php
// app/views/partials/navbar.php
// Requires: $_current_user, $_q (set by header.php)
?>
<!-- ===== NAVBAR ===== -->
<nav class="navbar-custom">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="/public/index.php" class="mb-0 fw-bold text-white text-decoration-none" style="letter-spacing:1px;">ELITISRIPIW</a>
        <div class="d-flex align-items-center gap-3">
            <form method="get" action="/public/index.php" class="mb-0">
                <div class="search-wrapper">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" name="q" class="search-input"
                        placeholder="Search movies…"
                        value="<?= $_q ?>">
                </div>
            </form>
            <?php if ($_current_user): ?>
                <div class="nav-auth d-flex gap-2 align-items-center">
                    <a href="/me/watchlist.php">Watchlist</a>
                    <a href="/me/reviews.php">My Reviews</a>
                    <?php if ($_current_user['role'] === 'admin'): ?>
                        <a href="/admin/dashboard.php">Admin</a>
                    <?php endif; ?>
                    <a href="/auth/logout.php">Logout</a>
                </div>
                <i class="fa-solid fa-circle-user fs-3 text-white"></i>
            <?php else: ?>
                <div class="nav-auth d-flex gap-2">
                    <a href="/auth/login.php">Login</a>
                    <a href="/auth/register.php">Register</a>
                </div>
                <i class="fa-solid fa-circle-user fs-3 text-white"></i>
            <?php endif; ?>
        </div>
    </div>
</nav>
<!-- ===== END NAVBAR ===== -->