<?php
// auth/logout.php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/flash.php';
require_once __DIR__ . '/../app/helpers/auth.php';

ensure_session();

if (is_logged_in()) {
    flash_set('success', 'You have been logged out.');
}

logout_user(); // destroys session + redirects to /auth/login.php
