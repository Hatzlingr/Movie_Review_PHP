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
            "SELECT m.id, m.title, m.release_year, m.poster_path, m.duration_minutes,
                    ROUND(AVG(r.score), 1) AS avg_rating,
                    COUNT(r.id)            AS total_ratings
             FROM movies m
             LEFT JOIN ratings r ON r.movie_id = m.id
             {$where}
             GROUP BY m.id, m.title, m.release_year, m.poster_path, m.duration_minutes
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

    /**
     * Get movie suggestions for autocomplete dropdown.
     * Returns limited set of movies matching query.
     */
    public function suggestMovies(string $q, int $limit = 8): array
    {
        if (trim($q) === '') {
            return [];
        }

        $stmt = $this->pdo->prepare(
            "SELECT m.id, m.title, m.release_year, m.poster_path,
                    ROUND(AVG(r.score), 1) AS avg_rating,
                    COUNT(r.id)            AS total_ratings
             FROM movies m
             LEFT JOIN ratings r ON r.movie_id = m.id
             WHERE m.title LIKE :q
             GROUP BY m.id, m.title, m.release_year, m.poster_path
             ORDER BY m.release_year DESC, m.title ASC
             LIMIT :lim"
        );
        $stmt->bindValue(':q', "%{$q}%", PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBestMoviesOfMonth(int $month, ?int $year = null, int $limit = 5): array
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
             LIMIT :lim"
        );

        $stmt->execute([':month' => $month, ':year' => $year, ':lim' => $limit]);
        $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fallback: Jika tidak ada rating bulan ini, ambil film terbaik sepanjang masa
        if (empty($movies)) {
            $fallback = $this->pdo->prepare(
                "SELECT m.id, m.title, m.description, m.release_year, m.duration_minutes,
                        m.poster_path, m.banner_path,
                        ROUND(AVG(r.score), 1) AS avg_rating,
                        AVG(r.score)            AS avg_score,
                        COUNT(r.id)             AS rating_count
                 FROM movies m
                 JOIN ratings r ON r.movie_id = m.id
                 GROUP BY m.id, m.title, m.description, m.release_year, m.duration_minutes, m.poster_path, m.banner_path
                 ORDER BY avg_score DESC, rating_count DESC, m.id ASC
                 LIMIT :lim"
            );
            $fallback->execute([':lim' => $limit]);
            $movies = $fallback->fetchAll(PDO::FETCH_ASSOC);
        }

        return $movies;
    }

    public function getMovieById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT m.*,
                    ROUND(AVG(r.score), 1) AS avg_rating,
                    COUNT(r.id)            AS total_ratings
             FROM movies m
             LEFT JOIN ratings r ON r.movie_id = m.id
             WHERE m.id = :id
             GROUP BY m.id"
        );
        $stmt->execute([':id' => $id]);
        $movie = $stmt->fetch(PDO::FETCH_ASSOC);
        return $movie ?: null;
    }

    public function getGenresByMovieId(int $id): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT g.name FROM genres g
             JOIN movie_genres mg ON mg.genre_id = g.id
             WHERE mg.movie_id = :id ORDER BY g.name"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getActorsByMovieId(int $id): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT a.name, a.photo_path, ma.role_name
             FROM actors a
             JOIN movie_actors ma ON ma.actor_id = a.id
             WHERE ma.movie_id = :id ORDER BY a.name"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDirectorsByMovieId(int $id): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT d.name, d.photo_path FROM directors d
             JOIN movie_directors md ON md.director_id = d.id
             WHERE md.movie_id = :id ORDER BY d.name"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReviewsByMovieId(int $id): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT rv.id, rv.user_id, rv.review_text, rv.created_at,
                    u.username, u.profile_photo,
                    COUNT(rl.id) AS like_count
             FROM reviews rv
             JOIN users u ON u.id = rv.user_id
             LEFT JOIN review_likes rl ON rl.review_id = rv.id
             WHERE rv.movie_id = :id
             GROUP BY rv.id
             ORDER BY rv.created_at DESC"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserMovieContext(int $uid, int $movieId): array
    {
        $rStmt = $this->pdo->prepare("SELECT score FROM ratings WHERE user_id=:u AND movie_id=:m");
        $rStmt->execute([':u' => $uid, ':m' => $movieId]);
        $myRating = $rStmt->fetchColumn();

        $rvStmt = $this->pdo->prepare("SELECT id, review_text FROM reviews WHERE user_id=:u AND movie_id=:m");
        $rvStmt->execute([':u' => $uid, ':m' => $movieId]);
        $myReview = $rvStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $wStmt = $this->pdo->prepare("SELECT status FROM watchlists WHERE user_id=:u AND movie_id=:m");
        $wStmt->execute([':u' => $uid, ':m' => $movieId]);
        $myWatchlist = $wStmt->fetchColumn() ?: null;

        $lStmt = $this->pdo->prepare(
            "SELECT rl.review_id FROM review_likes rl
             JOIN reviews rv ON rv.id = rl.review_id
             WHERE rl.user_id = :u AND rv.movie_id = :m"
        );
        $lStmt->execute([':u' => $uid, ':m' => $movieId]);
        $myLikedReviews = $lStmt->fetchAll(PDO::FETCH_COLUMN);

        return [
            'myRating'       => ($myRating !== false ? $myRating : null),
            'myReview'       => $myReview,
            'myWatchlist'    => $myWatchlist,
            'myLikedReviews' => $myLikedReviews,
        ];
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
                    u.username, u.profile_photo,
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
                      m.id, m.title, m.release_year, m.poster_path, u.username, u.profile_photo
             ORDER BY like_count DESC, rv.created_at DESC
             LIMIT :lim"
        );

        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** All genres ordered alphabetically — used by search page filter pills. */
    public function getAllGenres(): array
    {
        return $this->pdo
            ->query("SELECT id, name FROM genres ORDER BY name ASC")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Paginated search with optional title query AND/OR genre filter.
     * Returns ['data' => [...], 'total' => int].
     */
    public function searchMovies(string $q, string $genre, int $limit, int $offset): array
    {
        $conds  = [];
        $params = [];

        if ($q !== '') {
            $conds[]      = 'm.title LIKE :q';
            $params[':q'] = "%{$q}%";
        }

        $genreJoin = '';
        if ($genre !== '') {
            $genreJoin         = 'JOIN movie_genres mg ON mg.movie_id = m.id
                                  JOIN genres g        ON g.id        = mg.genre_id';
            $conds[]           = 'g.name = :genre';
            $params[':genre']  = $genre;
        }

        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

        $cntStmt = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT m.id) FROM movies m {$genreJoin} {$where}"
        );
        foreach ($params as $k => $v) $cntStmt->bindValue($k, $v, PDO::PARAM_STR);
        $cntStmt->execute();
        $total = (int) $cntStmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            "SELECT m.id, m.title, m.release_year, m.poster_path,
                    ROUND(AVG(r.score), 1) AS avg_rating,
                    COUNT(DISTINCT r.id)    AS total_ratings
             FROM movies m
             {$genreJoin}
             LEFT JOIN ratings r ON r.movie_id = m.id
             {$where}
             GROUP BY m.id, m.title, m.release_year, m.poster_path
             ORDER BY m.release_year DESC, m.id DESC
             LIMIT :lim OFFSET :off"
        );
        foreach ($params as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total];
    }
}

