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
 * Resolve any image path to a public URL.
 * $type: 'poster' | 'banner' | 'actor' | 'director' | 'avatar'
 */
function imageUrl(?string $path, string $type = 'poster'): string
{
    if ($path && str_starts_with($path, 'http')) return $path;

    $placeholders = [
        'poster'   => 'https://placehold.co/220x330/2f2543/ffffff?text=No+Poster',
        'banner'   => 'https://placehold.co/1280x720/2f2543/ffffff?text=No+Banner',
        'actor'    => 'https://placehold.co/150x150/2f2543/ffffff?text=No+Photo',
        'director' => 'https://placehold.co/150x150/2f2543/ffffff?text=No+Photo',
        'avatar'   => 'https://placehold.co/80x80/2f2543/ffffff?text=?',
    ];

    if (!$path) return $placeholders[$type] ?? $placeholders['poster'];
    return '/public/' . ltrim($path, '/');
}
/**
 * Format duration in minutes to "Xh Ym" string.
 */
function formatDuration(?int $minutes): string
{
    if (!$minutes || $minutes <= 0) return '';
    $h = (int) floor($minutes / 60);
    $m = $minutes % 60;
    return ($h ? $h . 'h ' : '') . ($m ? $m . 'm' : '');
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
