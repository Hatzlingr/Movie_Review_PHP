<?php

declare(strict_types=1);

class MovieRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getMovies(string $q, int $limit, int $offset): array
    {
        $hasSearch = $q !== '';
        $where     = $hasSearch ? 'WHERE m.title LIKE :q' : '';

        $countSql = "SELECT COUNT(*) FROM movies m {$where}";
        $countStmt = $this->pdo->prepare($countSql);
        if ($hasSearch) $countStmt->bindValue(':q', "%{$q}%", PDO::PARAM_STR);
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            "SELECT m.id, m.title, m.release_year, m.poster_path,
                    ROUND(AVG(r.score), 1) AS avg_rating,
                    COUNT(r.id)            AS total_ratings
             FROM movies m
             LEFT JOIN ratings r ON r.movie_id = m.id
             {$where}
             GROUP BY m.id, m.title, m.release_year, m.poster_path
             ORDER BY m.release_year DESC
             LIMIT :lim OFFSET :off"
        );
        if ($hasSearch) $stmt->bindValue(':q', "%{$q}%", PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data'  => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
        ];
    }

    // ============================
    // TAMBAHKAN METHOD INI
    // ============================
    public function getBestMovieByMonth(int $month, ?int $year = null): ?array
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('Month must be between 1 and 12');
        }

        $year = $year ?? (int) date('Y');

        $stmt = $this->pdo->prepare(
            "SELECT m.id, m.title, m.description, m.release_year, m.duration_minutes,
                    m.poster_path, m.banner_path,
                    ROUND(AVG(all_r.score), 1) AS avg_rating,
                    AVG(month_r.score)          AS avg_score,
                    COUNT(month_r.id)           AS rating_count
             FROM movies m
             JOIN ratings month_r ON month_r.movie_id = m.id
                  AND MONTH(month_r.created_at) = :month
                  AND YEAR(month_r.created_at)  = :year
             LEFT JOIN ratings all_r ON all_r.movie_id = m.id
             GROUP BY m.id, m.title, m.description, m.release_year, m.duration_minutes, m.poster_path, m.banner_path
             ORDER BY avg_score DESC, rating_count DESC, m.id ASC
             LIMIT 1"
        );

        $stmt->execute([':month' => $month, ':year' => $year]);
        $movie = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fallback: best movie of all time if no ratings this month
        if (!$movie) {
            $fallback = $this->pdo->query(
                "SELECT m.id, m.title, m.description, m.release_year, m.duration_minutes,
                        m.poster_path, m.banner_path,
                        ROUND(AVG(r.score), 1) AS avg_rating,
                        AVG(r.score)            AS avg_score,
                        COUNT(r.id)             AS rating_count
                 FROM movies m
                 JOIN ratings r ON r.movie_id = m.id
                 GROUP BY m.id, m.title, m.description, m.release_year, m.duration_minutes, m.poster_path, m.banner_path
                 ORDER BY avg_score DESC, rating_count DESC, m.id ASC
                 LIMIT 1"
            );
            $movie = $fallback->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        return $movie ?: null;
    }

    public function getUserWatchlist(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT m.id, m.title, m.release_year, m.poster_path,
                    w.status, w.created_at AS added_at,
                    ROUND(AVG(r.score), 1) AS avg_rating,
                    COUNT(r.id)            AS total_ratings
             FROM watchlists w
             JOIN movies m ON m.id = w.movie_id
             LEFT JOIN ratings r ON r.movie_id = m.id
             WHERE w.user_id = :u
                      GROUP BY m.id, m.title, m.release_year, m.poster_path, w.status, w.created_at
             ORDER BY w.created_at DESC"
        );

        $stmt->execute([':u' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMostLikedReviews(int $limit): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT rv.id, rv.review_text, rv.created_at,
                    m.id AS movie_id, m.title, m.release_year, m.poster_path,
                    u.username,
                    COUNT(rl.id) AS like_count,
                    (
                        SELECT rt2.score
                        FROM ratings rt2
                        WHERE rt2.movie_id = rv.movie_id
                          AND rt2.user_id  = rv.user_id
                        ORDER BY rt2.created_at DESC
                        LIMIT 1
                    ) AS user_score
             FROM reviews rv
             JOIN movies m ON m.id = rv.movie_id
             JOIN users u ON u.id = rv.user_id
             LEFT JOIN review_likes rl ON rl.review_id = rv.id
             GROUP BY rv.id, rv.review_text, rv.created_at,
                      m.id, m.title, m.release_year, m.poster_path, u.username
             ORDER BY like_count DESC, rv.created_at DESC
             LIMIT :lim"
        );

        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
