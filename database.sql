-- Database for IPM E-Voting
CREATE DATABASE IF NOT EXISTS ipm_voting;
USE ipm_voting;

-- Admin User
CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    organization_name VARCHAR(100) DEFAULT 'Organisasi Utama',
    role ENUM('master', 'admin', 'poll_admin') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default Admin
INSERT INTO admins (username, password_hash, organization_name, role) 
SELECT 'admin', '$2y$10$VkWVFeBPHn9.7867au5VgecX83Qixh9rkipbq8I93K.BK8Hl5ZLyy', 'IPM Tangsel', 'master'
WHERE NOT EXISTS (SELECT * FROM admins);

-- Settings Table (per admin)
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    min_vote INT DEFAULT 1,
    max_vote INT DEFAULT 1,
    voting_enabled BOOLEAN DEFAULT TRUE,
    logo_path VARCHAR(255) DEFAULT NULL,
    theme_color VARCHAR(50) DEFAULT '#00984B',
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

-- Polls Table
CREATE TABLE IF NOT EXISTS `polls` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `success_message` text,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `polls_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Poll Options Table
CREATE TABLE IF NOT EXISTS poll_options (
    id INT PRIMARY KEY AUTO_INCREMENT,
    poll_id INT NOT NULL,
    option_text VARCHAR(255) NOT NULL,
    FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE
);

-- Poll Votes Table
CREATE TABLE IF NOT EXISTS poll_votes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    poll_id INT NOT NULL,
    option_id INT NOT NULL,
    ip_address VARCHAR(45),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    FOREIGN KEY (option_id) REFERENCES poll_options(id) ON DELETE CASCADE
);
