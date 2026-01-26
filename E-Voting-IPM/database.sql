-- Database for IPM E-Voting
CREATE DATABASE IF NOT EXISTS ipm_voting;
USE ipm_voting;

-- Settings Table (one row for config)
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    min_vote INT DEFAULT 1,
    max_vote INT DEFAULT 1,
    voting_enabled BOOLEAN DEFAULT TRUE
);

INSERT INTO settings (id, min_vote, max_vote, voting_enabled) 
SELECT 1, 1, 1, TRUE WHERE NOT EXISTS (SELECT * FROM settings);

-- Candidates Table
CREATE TABLE IF NOT EXISTS candidates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    photo VARCHAR(255),
    vision TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tokens Table
CREATE TABLE IF NOT EXISTS tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(10) UNIQUE NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Votes Table
CREATE TABLE IF NOT EXISTS votes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    candidate_id INT NOT NULL,
    token_code VARCHAR(10) NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
    FOREIGN KEY (token_code) REFERENCES tokens(code) ON DELETE CASCADE
);

-- Admin User (Optional, for secure backend access if needed later)
-- For this simple app, we might hardcode admin login or use a simple session check.
