<?php
// app/helpers/functions.php
declare(strict_types=1);

/**
 * Start session once (call at top of every entry point).
 */
function ensure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Escape a value for safe HTML output.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * PRG redirect helper.
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}
