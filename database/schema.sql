-- Movie Review DB v2 (MySQL 8+)
-- Improvements:
-- 1) Add updated_at on frequently updated tables
-- 2) Add domain checks for movie release_year and duration_minutes
-- 3) Add composite indexes for common timeline/feed queries

SET NAMES utf8mb4;
SET time_zone = '+00:00';

DROP DATABASE IF EXISTS movie_review_db;
CREATE DATABASE movie_review_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE movie_review_db;

SET FOREIGN_KEY_CHECKS = 0;

-- USERS
DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(30) NOT NULL,
  email VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  profile_photo VARCHAR(255) NULL,
  bio VARCHAR(255) NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- MOVIES
DROP TABLE IF EXISTS movies;
CREATE TABLE movies (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(150) NOT NULL,
  description TEXT NULL,
  release_year SMALLINT UNSIGNED NULL,
  duration_minutes SMALLINT UNSIGNED NULL,
  poster_path VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_movies_release_year CHECK (release_year IS NULL OR release_year BETWEEN 1888 AND 2100),
  CONSTRAINT chk_movies_duration CHECK (duration_minutes IS NULL OR duration_minutes > 0),
  INDEX idx_movies_title (title),
  INDEX idx_movies_year (release_year)
) ENGINE=InnoDB;

-- GENRES + MOVIE_GENRES (M:N)
DROP TABLE IF EXISTS movie_genres;
DROP TABLE IF EXISTS genres;

CREATE TABLE genres (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL,
  UNIQUE KEY uq_genres_name (name)
) ENGINE=InnoDB;

CREATE TABLE movie_genres (
  movie_id BIGINT UNSIGNED NOT NULL,
  genre_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (movie_id, genre_id),
  CONSTRAINT fk_mg_movie FOREIGN KEY (movie_id) REFERENCES movies(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_mg_genre FOREIGN KEY (genre_id) REFERENCES genres(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ACTORS + MOVIE_ACTORS (M:N)
DROP TABLE IF EXISTS movie_actors;
DROP TABLE IF EXISTS actors;

CREATE TABLE actors (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  photo_path VARCHAR(255) NULL,
  UNIQUE KEY uq_actors_name (name)
) ENGINE=InnoDB;

CREATE TABLE movie_actors (
  movie_id BIGINT UNSIGNED NOT NULL,
  actor_id BIGINT UNSIGNED NOT NULL,
  role_name VARCHAR(100) NULL,
  PRIMARY KEY (movie_id, actor_id),
  CONSTRAINT fk_ma_movie FOREIGN KEY (movie_id) REFERENCES movies(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ma_actor FOREIGN KEY (actor_id) REFERENCES actors(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- DIRECTORS + MOVIE_DIRECTORS (M:N)
DROP TABLE IF EXISTS movie_directors;
DROP TABLE IF EXISTS directors;

CREATE TABLE directors (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  UNIQUE KEY uq_directors_name (name)
) ENGINE=InnoDB;

CREATE TABLE movie_directors (
  movie_id BIGINT UNSIGNED NOT NULL,
  director_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (movie_id, director_id),
  CONSTRAINT fk_md_movie FOREIGN KEY (movie_id) REFERENCES movies(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_md_director FOREIGN KEY (director_id) REFERENCES directors(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- RATINGS (1 user 1 rating per movie)
DROP TABLE IF EXISTS ratings;
CREATE TABLE ratings (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  movie_id BIGINT UNSIGNED NOT NULL,
  score TINYINT UNSIGNED NOT NULL, -- 1..5
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_ratings_score CHECK (score BETWEEN 1 AND 5),
  CONSTRAINT fk_ratings_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ratings_movie FOREIGN KEY (movie_id) REFERENCES movies(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uq_ratings_user_movie (user_id, movie_id),
  INDEX idx_ratings_movie (movie_id),
  INDEX idx_ratings_user (user_id),
  INDEX idx_ratings_movie_created_at (movie_id, created_at)
) ENGINE=InnoDB;

-- REVIEWS (1 user 1 review per movie)
DROP TABLE IF EXISTS reviews;
CREATE TABLE reviews (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  movie_id BIGINT UNSIGNED NOT NULL,
  review_text TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_reviews_movie FOREIGN KEY (movie_id) REFERENCES movies(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uq_reviews_user_movie (user_id, movie_id),
  INDEX idx_reviews_movie (movie_id),
  INDEX idx_reviews_user (user_id),
  INDEX idx_reviews_movie_created_at (movie_id, created_at)
) ENGINE=InnoDB;

-- WATCHLISTS (1 user 1 movie 1 row)
DROP TABLE IF EXISTS watchlists;
CREATE TABLE watchlists (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  movie_id BIGINT UNSIGNED NOT NULL,
  status ENUM('plan_to_watch','watching','completed') NOT NULL DEFAULT 'plan_to_watch',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_watch_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_watch_movie FOREIGN KEY (movie_id) REFERENCES movies(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uq_watch_user_movie (user_id, movie_id),
  INDEX idx_watch_user (user_id),
  INDEX idx_watch_movie (movie_id),
  INDEX idx_watch_user_status (user_id, status)
) ENGINE=InnoDB;

-- REVIEW LIKES (toggle like/unlike)
DROP TABLE IF EXISTS review_likes;
CREATE TABLE review_likes (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  review_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_rl_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_rl_review FOREIGN KEY (review_id) REFERENCES reviews(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uq_like_user_review (user_id, review_id),
  INDEX idx_like_review (review_id),
  INDEX idx_like_user (user_id)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
