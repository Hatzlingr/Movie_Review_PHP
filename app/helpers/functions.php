<?php

/**
 * app/helpers/functions.php
 *
 * Global helper functions used across the entire application.
 *
 * @author  Movie Review App
 * @version 1.0
 */

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
 *
 * Only allows local paths (starting with '/') to prevent open-redirect attacks.
 * Any absolute URL is silently replaced with the site root.
 *
 * @param string $url Target path, e.g. '/index.php' or '/public/movie.php?id=1'.
 */
function redirect(string $url): never
{
    // Block external URLs — only relative paths are allowed.
    if (!str_starts_with($url, '/') || str_starts_with($url, '//')) {
        $url = '/index.php';
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Render 5 stars (full / half / empty) from a numeric score.
 *
 * @param  float  $score  Rating value; automatically clamped to [0, 5].
 * @return string         HTML string of <i> star icons.
 *
 * @example renderStars(3.5) // Returns 3 full + 1 half + 1 empty star
 */
function renderStars(float $score): string
{
    // Clamp to valid range to prevent broken UI or negative loop counts.
    $score = max(0.0, min(5.0, $score));

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
 *
 * Absolute HTTP(S) URLs are returned as-is. DB-stored relative paths
 * (e.g. 'uploads/posters/xxx.jpg') are prefixed with '/public/'.
 * Path-traversal sequences (../ and .\) are stripped for defense-in-depth.
 *
 * @param  string|null $path  Raw path from DB or null.
 * @param  string      $type  Placeholder type: 'poster'|'banner'|'actor'|'director'|'avatar'.
 * @return string             Absolute URL safe for use in an <img src> attribute.
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

    // Strip path-traversal sequences before building the URL.
    $safe = str_replace(['../', '..\\', '..'], '', $path);
    $safe = ltrim($safe, '/');

    return '/public/' . $safe;
}

/**
 * Resolve a DB-stored upload path to an absolute filesystem path.
 *
 * DB stores:  'uploads/posters/xxx.jpg'
 * Returns:    '/absolute/path/to/public/uploads/posters/xxx.jpg'
 *
 * Throws a RuntimeException if the resolved path escapes the public/ folder
 * to prevent path-traversal attacks when the result is used with file functions.
 *
 * @param  string|null $path  Relative path as stored in the database.
 * @return string             Absolute filesystem path inside public/.
 * @throws RuntimeException   If the path resolves outside the allowed directory.
 */
function upload_path(?string $path): string
{
    $base     = realpath(__DIR__ . '/../../public');
    $resolved = realpath($base . '/' . ltrim($path ?? '', '/'));

    // realpath() returns false for non-existent files; fall back to a safe join.
    if ($resolved === false) {
        // File may not exist yet (new upload). Strip traversal manually.
        $safe = str_replace(['../', '..\\', '..'], '', $path ?? '');
        return $base . '/' . ltrim($safe, '/');
    }

    if (!str_starts_with($resolved, $base)) {
        throw new RuntimeException('Invalid upload path: path traversal detected.');
    }

    return $resolved;
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
