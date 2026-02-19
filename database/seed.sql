USE movie_review_db;

SET FOREIGN_KEY_CHECKS = 0;

-- =========================
-- USERS
-- Password demo:
-- admin: admin123
-- users: user123
-- (hashes are bcrypt demo)
-- =========================
INSERT INTO users (id, username, email, password_hash, role, bio) VALUES
(1, 'admin', 'admin@demo.com', '$2b$10$kaIiedKWQGdPEZERguD3zear/KJhG4N/tYyFUlhsEMIpMW3pWEWm6', 'admin', 'Site admin'),
(2, 'amir',  'amir@demo.com',  '$2b$10$4N6p8H9CIFh1UMMCLxqcE.Ba15GMQ.UAtuNHZEQbcP31h9Fc.bmea', 'user',  'Movie enjoyer'),
(3, 'sinta', 'sinta@demo.com', '$2b$10$4N6p8H9CIFh1UMMCLxqcE.Ba15GMQ.UAtuNHZEQbcP31h9Fc.bmea', 'user',  'Sci-fi & thriller'),
(4, 'budi',  'budi@demo.com',  '$2b$10$4N6p8H9CIFh1UMMCLxqcE.Ba15GMQ.UAtuNHZEQbcP31h9Fc.bmea', 'user',  'Action & drama');

-- =========================
-- GENRES
-- =========================
INSERT INTO genres (id, name) VALUES
(1,'Action'),
(2,'Drama'),
(3,'Comedy'),
(4,'Horror'),
(5,'Sci-Fi'),
(6,'Romance')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- =========================
-- MOVIES
-- =========================
INSERT INTO movies (id, title, description, release_year, duration_minutes, poster_path) VALUES
(1,  'Inception',              'A thief who steals corporate secrets through dream-sharing technology is given a chance to erase his criminal past.', 2010, 148, 'uploads/posters/inception.jpg'),
(2,  'The Matrix',             'A hacker learns about the true nature of reality and his role in the war against its controllers.',              1999, 136, 'uploads/posters/matrix.jpg'),
(3,  'The Dark Knight',        'Batman faces the Joker, a criminal mastermind who wants to plunge Gotham into anarchy.',                         2008, 152, 'uploads/posters/dark_knight.jpg'),
(4,  'Interstellar',           'Explorers travel through a wormhole in space in an attempt to ensure humanity’s survival.',                       2014, 169, 'uploads/posters/interstellar.jpg'),
(5,  'Blade Runner 2049',      'A young blade runner discovers a secret that could plunge society into chaos.',                                 2017, 164, 'uploads/posters/br2049.jpg'),
(6,  'Dune',                   'A noble family becomes embroiled in a war for control over the galaxy’s most valuable asset.',                  2021, 155, 'uploads/posters/dune.jpg'),
(7,  'Spider-Man: No Way Home','Spider-Man seeks help to restore his secret identity, but breaks the multiverse.',                             2021, 148, 'uploads/posters/nwh.jpg'),
(8,  'Avengers: Endgame',      'The Avengers assemble once more to reverse Thanos’ actions and restore balance.',                               2019, 181, 'uploads/posters/endgame.jpg'),
(9,  'Parasite',               'Greed and class discrimination threaten the newly formed symbiotic relationship between two families.',        2019, 132, 'uploads/posters/parasite.jpg'),
(10, 'Get Out',                'A young African-American visits his white girlfriend’s parents for the weekend, where secrets emerge.',         2017, 104, 'uploads/posters/get_out.jpg');

-- =========================
-- DIRECTORS
-- =========================
INSERT INTO directors (id, name) VALUES
(1, 'Christopher Nolan'),
(2, 'Lana Wachowski'),
(3, 'Lilly Wachowski'),
(4, 'Denis Villeneuve'),
(5, 'Jon Watts'),
(6, 'Anthony Russo'),
(7, 'Joe Russo'),
(8, 'Bong Joon-ho'),
(9, 'Jordan Peele')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO movie_directors (movie_id, director_id) VALUES
(1, 1),
(2, 2), (2, 3),
(3, 1),
(4, 1),
(5, 4),
(6, 4),
(7, 5),
(8, 6), (8, 7),
(9, 8),
(10, 9);

-- =========================
-- ACTORS
-- =========================
INSERT INTO actors (id, name, photo_path) VALUES
(1,  'Leonardo DiCaprio',         NULL),
(2,  'Joseph Gordon-Levitt',      NULL),
(3,  'Elliot Page',               NULL),
(4,  'Keanu Reeves',              NULL),
(5,  'Carrie-Anne Moss',          NULL),
(6,  'Laurence Fishburne',        NULL),
(7,  'Christian Bale',            NULL),
(8,  'Heath Ledger',              NULL),
(9,  'Aaron Eckhart',             NULL),
(10, 'Matthew McConaughey',       NULL),
(11, 'Anne Hathaway',             NULL),
(12, 'Ryan Gosling',              NULL),
(13, 'Harrison Ford',             NULL),
(14, 'Timothée Chalamet',         NULL),
(15, 'Zendaya',                   NULL),
(16, 'Tom Holland',               NULL),
(17, 'Benedict Cumberbatch',      NULL),
(18, 'Robert Downey Jr.',         NULL),
(19, 'Chris Evans',               NULL),
(20, 'Scarlett Johansson',        NULL),
(21, 'Song Kang-ho',              NULL),
(22, 'Lee Sun-kyun',              NULL),
(23, 'Cho Yeo-jeong',             NULL),
(24, 'Daniel Kaluuya',            NULL),
(25, 'Allison Williams',          NULL)
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO movie_actors (movie_id, actor_id, role_name) VALUES
(1,  1,  'Cobb'),
(1,  2,  'Arthur'),
(1,  3,  'Ariadne'),

(2,  4,  'Neo'),
(2,  5,  'Trinity'),
(2,  6,  'Morpheus'),

(3,  7,  'Bruce Wayne / Batman'),
(3,  8,  'Joker'),
(3,  9,  'Harvey Dent'),

(4,  10, 'Cooper'),
(4,  11, 'Brand'),

(5,  12, 'K'),
(5,  13, 'Deckard'),

(6,  14, 'Paul Atreides'),
(6,  15, 'Chani'),

(7,  16, 'Peter Parker'),
(7,  17, 'Doctor Strange'),
(7,  15, 'MJ'),

(8,  18, 'Tony Stark / Iron Man'),
(8,  19, 'Steve Rogers / Captain America'),
(8,  20, 'Natasha Romanoff / Black Widow'),

(9,  21, 'Kim Ki-taek'),
(9,  22, 'Park Dong-ik'),
(9,  23, 'Yeon-kyo'),

(10, 24, 'Chris Washington'),
(10, 25, 'Rose Armitage');

-- =========================
-- MOVIE_GENRES
-- =========================
INSERT INTO movie_genres (movie_id, genre_id) VALUES
(1, 5), (1, 2),
(2, 5), (2, 1),
(3, 1), (3, 2),
(4, 5), (4, 2),
(5, 5), (5, 2),
(6, 5), (6, 2),
(7, 1), (7, 5),
(8, 1), (8, 5),
(9, 2), (9, 3),
(10,4), (10,2);

-- =========================
-- RATINGS
-- =========================
INSERT INTO ratings (id, user_id, movie_id, score) VALUES
(1, 2, 1, 4),
(2, 2, 3, 5),
(3, 2, 8, 4),
(4, 3, 2, 5),
(5, 3, 5, 5),
(6, 3, 10, 5),
(7, 4, 7, 5),
(8, 4, 9, 5);

-- =========================
-- REVIEWS
-- =========================
INSERT INTO reviews (id, user_id, movie_id, review_text) VALUES
(1, 2, 1,  'Mind-bending concept with a tight execution. The dream layers are still unmatched.'),
(2, 2, 3,  'Peak superhero movie. Joker feels genuinely chaotic, and the pacing never drags.'),
(3, 3, 2,  'A classic that aged well. The philosophy is fun, and the action still hits.'),
(4, 3, 10, 'Uncomfortable in the best way. Great build-up and payoff.'),
(5, 4, 7,  'Pure fan service but surprisingly emotional. The multiverse chaos is entertaining.'),
(6, 4, 9,  'Sharp satire with brilliant tension. The class commentary lands hard.'),
(7, 3, 5,  'Atmosphere and cinematography are insane. Slow burn, but worth it.'),
(8, 2, 4,  'Ambitious, emotional, and visually stunning. The score is a masterpiece.');

-- =========================
-- WATCHLISTS
-- =========================
INSERT INTO watchlists (id, user_id, movie_id, status) VALUES
(1, 2, 6, 'plan_to_watch'),
(2, 2, 5, 'watching'),
(3, 3, 4, 'completed'),
(4, 3, 9, 'watching'),
(5, 4, 1, 'completed'),
(6, 4, 10,'plan_to_watch');

-- =========================
-- REVIEW LIKES
-- =========================
INSERT INTO review_likes (id, user_id, review_id) VALUES
(1, 3, 1),
(2, 4, 1),
(3, 2, 3),
(4, 2, 6),
(5, 3, 2),
(6, 4, 4);

SET FOREIGN_KEY_CHECKS = 1;
