<?php
// auth/logout.php
declare(strict_types=1);

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/auth.php';

ensure_session();
logout_user(); // destroys session + redirects to /index.php
