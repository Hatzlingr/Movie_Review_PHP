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

/**
 * Render 5 stars (full / half / empty) from a numeric score.
 */
function renderStars(float $score): string
{
    $full  = (int) floor($score);
    $half  = ($score - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - $full - $half;
    $html  = '';
    for ($i = 0; $i < $full; $i++) $html .= '<i class="fa-solid fa-star"></i>';
    if ($half)                       $html .= '<i class="fa-regular fa-star-half-stroke"></i>';
    for ($i = 0; $i < $empty; $i++) $html .= '<i class="fa-regular fa-star"></i>';
    return $html;
}

/**
 * Resolve a poster path to a public URL.
 */
function posterUrl(?string $path): string
{
    if (!$path) return 'https://placehold.co/220x330/2f2543/ffffff?text=No+Poster';
    if (str_starts_with($path, 'http')) return $path;
    return '/public/' . ltrim($path, '/');
}

/**
 * Render a watchlist status badge button.
 */
function watchlistStatusBtn(string $status): string
{
    return match ($status) {
        'watching'  => '<button class="btn-watching w-100">Watching</button>',
        'completed' => '<button class="btn-completed w-100">Completed</button>',
        default     => '<button class="btn-plan w-100">Plan To Watch</button>',
    };
}
