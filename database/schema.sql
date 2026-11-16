CREATE DATABASE IF NOT EXISTS voting_system
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE voting_system;

CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin','voter') NOT NULL DEFAULT 'voter',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE elections (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title     VARCHAR(150) NOT NULL,
  start_at  DATETIME NOT NULL,
  end_at    DATETIME NOT NULL,
  status    ENUM('draft','open','closed') NOT NULL DEFAULT 'draft',
  CHECK (end_at > start_at)
) ENGINE=InnoDB;

CREATE TABLE positions (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  election_id INT UNSIGNED NOT NULL,
  title       VARCHAR(100) NOT NULL,
  FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE candidates (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  position_id INT UNSIGNED NOT NULL,
  name        VARCHAR(100) NOT NULL,
  photo       VARCHAR(255) NULL,
  manifesto   TEXT NULL,
  UNIQUE KEY uq_candidate_position (id, position_id),
  FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE votes (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  voter_id     INT UNSIGNED NOT NULL,
  position_id  INT UNSIGNED NOT NULL,
  candidate_id INT UNSIGNED NOT NULL,
  voted_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_one_vote_per_position (voter_id, position_id),
  FOREIGN KEY (voter_id) REFERENCES users(id) ON DELETE RESTRICT,
  FOREIGN KEY (candidate_id, position_id)
    REFERENCES candidates(id, position_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX idx_votes_candidate ON votes(candidate_id);