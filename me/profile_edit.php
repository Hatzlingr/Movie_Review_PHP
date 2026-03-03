<?php
// me/profile_edit.php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/db.php';

ensure_session();
require_login();

$user = current_user();
$uid = $user['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/me/profile.php');
}

$email = trim($_POST['email'] ?? '');
$bio = trim($_POST['bio'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($email === '') {
    flash_set('danger', 'Email cannot be empty.');
    redirect('/me/profile.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash_set('danger', 'Invalid email address.');
    redirect('/me/profile.php');
}

// Check email uniqueness
$chk = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
$chk->execute([':email' => $email, ':id' => $uid]);
if ($chk->fetch()) {
    flash_set('danger', 'Email already in use.');
    redirect('/me/profile.php');
}

$updates = [];
$params = [':id' => $uid];

$updates[] = 'email = :email';
$params[':email'] = $email;

$updates[] = 'bio = :bio';
$params[':bio'] = $bio;

if ($password !== '') {
    if (strlen($password) < 6) {
        flash_set('danger', 'Password must be at least 6 characters.');
        redirect('/me/profile.php');
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $updates[] = 'password_hash = :ph';
    $params[':ph'] = $hash;
}

$sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = :id';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// Update session username/email if needed
$_SESSION['email'] = $email;

flash_set('success', 'Profile updated.');
redirect('/me/profile.php');
?>