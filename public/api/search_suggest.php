<?php
// public/api/search_suggest.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/helpers/functions.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/config/db.php';
require_once __DIR__ . '/../../app/repositories/movieRepository.php';

ensure_session();

try {
    $q = trim($_GET['q'] ?? '');

    if (strlen($q) < 2) {
        echo json_encode(['success' => false, 'data' => [], 'message' => 'Query too short']);
        exit;
    }

    $repo = new MovieRepository($pdo);
    $suggestions = $repo->suggestMovies($q, 8);

    // Map results to simplified format for dropdown
    $results = array_map(function ($movie) {
        return [
            'id'        => (int) $movie['id'],
            'title'     => $movie['title'],
            'year'      => $movie['release_year'] ?? null,
            'poster'    => imageUrl($movie['poster_path'] ?? null, 'poster'),
            'rating'    => $movie['avg_rating'] ? (float) $movie['avg_rating'] : null,
        ];
    }, $suggestions);

    echo json_encode([
        'success' => true,
        'data'    => $results,
        'count'   => count($results),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
    ]);
}
?>
