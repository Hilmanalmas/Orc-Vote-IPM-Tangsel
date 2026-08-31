<?php
require_once 'config.php';

try {
    // 1. Modify admins table enum
    echo "Updating admins role enum...\n";
    $pdo->exec("ALTER TABLE admins MODIFY COLUMN role ENUM('master', 'admin', 'poll_admin') DEFAULT 'admin'");
    echo "admins table updated successfully.\n\n";

    // 2. Create polls table
    echo "Creating polls table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS polls (
        id INT PRIMARY KEY AUTO_INCREMENT,
        admin_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
    )");
    echo "polls table created successfully.\n\n";

    // 3. Create poll_options table
    echo "Creating poll_options table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS poll_options (
        id INT PRIMARY KEY AUTO_INCREMENT,
        poll_id INT NOT NULL,
        option_text VARCHAR(255) NOT NULL,
        FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE
    )");
    echo "poll_options table created successfully.\n\n";

    // 4. Create poll_votes table
    echo "Creating poll_votes table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS poll_votes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        poll_id INT NOT NULL,
        option_id INT NOT NULL,
        ip_address VARCHAR(45),
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
        FOREIGN KEY (option_id) REFERENCES poll_options(id) ON DELETE CASCADE
    )");
    echo "poll_votes table created successfully.\n\n";

    echo "Migration completed successfully!\n";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
