<?php
// public/api/search_suggest.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// No session needed — public read-only endpoint
require_once __DIR__ . '/../../app/config/db.php';
require_once __DIR__ . '/../../app/repositories/movieRepository.php';

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $repo = new MovieRepository($pdo);
    echo json_encode($repo->suggestMovies($q, 8));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([]);
}
