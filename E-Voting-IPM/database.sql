-- Database for IPM E-Voting
CREATE DATABASE IF NOT EXISTS ipm_voting;
USE ipm_voting;

-- Admin User
CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    organization_name VARCHAR(100) DEFAULT 'Organisasi Utama',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default Admin
INSERT INTO admins (username, password_hash, organization_name) 
SELECT 'admin', '$2y$10$VkWVFeBPHn9.7867au5VgecX83Qixh9rkipbq8I93K.BK8Hl5ZLyy', 'IPM Tangsel'
WHERE NOT EXISTS (SELECT * FROM admins);

-- Settings Table (per admin)
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    min_vote INT DEFAULT 1,
    max_vote INT DEFAULT 1,
    voting_enabled BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);

-- Candidates Table
CREATE TABLE IF NOT EXISTS candidates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    photo VARCHAR(255),
    vision TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);

-- Tokens Table
CREATE TABLE IF NOT EXISTS tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    code VARCHAR(10) NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    UNIQUE KEY unique_token_per_admin (admin_id, code)
);

-- Votes Table
CREATE TABLE IF NOT EXISTS votes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    candidate_id INT NOT NULL,
    token_code VARCHAR(10) NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE
);
