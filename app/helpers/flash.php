<?php
// app/helpers/flash.php
declare(strict_types=1);

/**
 * Store a flash message in the session.
 *
 * @param string $type  Bootstrap alert type: success | danger | warning | info
 * @param string $message
 */
function flash_set(string $type, string $message): void
{
    ensure_session();
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Retrieve and clear all flash messages from the session.
 *
 * @return array<int, array{type: string, message: string}>
 */
function flash_get(): array
{
    ensure_session();
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $messages;
}

/**
 * Render flash messages as Bootstrap alerts (echo directly).
 */
function flash_render(): void
{
    foreach (flash_get() as $flash) {
        $type = htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8');
        $msg  = htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');
        echo <<<HTML
<div class="alert alert-{$type} alert-dismissible fade show" role="alert">
    {$msg}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
HTML;
    }
}
