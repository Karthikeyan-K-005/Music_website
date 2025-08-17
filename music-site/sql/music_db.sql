-- sql/music_db.sql
CREATE DATABASE IF NOT EXISTS music_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE music_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Songs table
CREATE TABLE IF NOT EXISTS songs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  artist VARCHAR(255) NOT NULL,
  duration_seconds INT NOT NULL,
  genre ENUM('Melody','Beat','Phonk') NOT NULL,
  audio_url TEXT NOT NULL,
  cover_url TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Favorites table
CREATE TABLE IF NOT EXISTS favorites (
  user_id INT NOT NULL,
  song_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, song_id),
  CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_fav_song FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Admin login: username=admin, password=admin123  (change later)
INSERT INTO users (username, password_hash, role) VALUES
('admin', '$2y$10$2iGv9zYd6mW8tCw9UtiYtO1VYd9k2u2j1cP7m0o1xXgYb8n1Yl0zK', 'admin')
ON DUPLICATE KEY UPDATE username = username;

