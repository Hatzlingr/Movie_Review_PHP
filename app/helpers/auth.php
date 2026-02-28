<?php
// app/helpers/auth.php
declare(strict_types=1);

/**
 * Check whether a user is currently logged in.
 */
function is_logged_in(): bool
{
    ensure_session();
    return !empty($_SESSION['user_id']);
}

/**
 * Return the current user's data from the database, or null if not logged in.
 * Fetches fresh from DB every request so role/data changes take effect immediately.
 *
 * @return array{id: int, username: string, email: string, role: string, profile_photo: string|null}|null
 */
function current_user(): ?array
{
    ensure_session();
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $stmt = get_pdo()->prepare(
        "SELECT id, username, email, role, profile_photo
         FROM users WHERE id = ? LIMIT 1"
    );
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();

    // Akun dihapus sementara session masih aktif — paksa logout
    if (!$row) {
        logout_user();
    }

    $cache = [
        'id'            => (int) $row['id'],
        'username'      => $row['username'],
        'email'         => $row['email'],
        'role'          => $row['role'],
        'profile_photo' => $row['profile_photo'] ?? null,
    ];

    return $cache;
}

/**
 * Populate session from a users row fetched via PDO.
 *
 * @param array<string, mixed> $user Row from the users table.
 */
function login_user(array $user): void
{
    ensure_session();
    session_regenerate_id(true);
    $_SESSION['user_id']       = $user['id'];
    $_SESSION['username']      = $user['username'];
    $_SESSION['email']         = $user['email'];
    $_SESSION['role']          = $user['role'];
    $_SESSION['profile_photo'] = $user['profile_photo'] ?? null;
}

/**
 * Destroy the current session and redirect to login.
 */
function logout_user(): never
{
    ensure_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 3600,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
    redirect('/index.php');
}

/**
 * Abort with a redirect to login if the user is not authenticated.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        flash_set('warning', 'You must be logged in to access that page.');
        redirect('/auth/login.php');
    }
}

/**
 * Abort with a redirect if the user is not an admin.
 */
function require_admin(): void
{
    require_login();
    $user = current_user();
    if ($user === null || $user['role'] !== 'admin') {
        flash_set('danger', 'Access denied.');
        redirect('/index.php');
    }
}
