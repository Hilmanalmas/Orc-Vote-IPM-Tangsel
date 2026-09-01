<?php
require_once 'config.php';

try {
    echo "<pre>";
    $pdo->beginTransaction();

    echo "1. Creating poll_questions table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS poll_questions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        poll_id INT NOT NULL,
        question_text TEXT NOT NULL,
        question_type ENUM('short_text', 'long_text', 'polling') DEFAULT 'polling',
        is_required BOOLEAN DEFAULT TRUE,
        order_num INT DEFAULT 0,
        FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE
    )");
    echo "poll_questions table created.\n\n";

    echo "2. Modifying poll_options table...\n";
    // Check if column exists first
    $stmt = $pdo->prepare("SHOW COLUMNS FROM poll_options LIKE 'question_id'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE poll_options ADD COLUMN question_id INT DEFAULT NULL");
        $pdo->exec("ALTER TABLE poll_options ADD FOREIGN KEY (question_id) REFERENCES poll_questions(id) ON DELETE CASCADE");
        echo "question_id column added to poll_options.\n\n";
    } else {
        echo "question_id column already exists in poll_options.\n\n";
    }

    echo "3. Migrating existing polls to questions...\n";
    $stmt = $pdo->prepare("SELECT id, title FROM polls");
    $stmt->execute();
    $polls = $stmt->fetchAll();

    $stmtInsertQ = $pdo->prepare("INSERT INTO poll_questions (poll_id, question_text, question_type) VALUES (?, ?, 'polling')");
    $stmtUpdateOpt = $pdo->prepare("UPDATE poll_options SET question_id = ? WHERE poll_id = ? AND question_id IS NULL");

    foreach ($polls as $poll) {
        // Check if question already exists to prevent duplicate migration
        $checkQ = $pdo->prepare("SELECT id FROM poll_questions WHERE poll_id = ?");
        $checkQ->execute([$poll['id']]);
        if (!$checkQ->fetch()) {
            $stmtInsertQ->execute([$poll['id'], $poll['title']]);
            $questionId = $pdo->lastInsertId();
            $stmtUpdateOpt->execute([$questionId, $poll['id']]);
            echo "Migrated poll ID " . $poll['id'] . " to question ID " . $questionId . "\n";
        }
    }
    echo "Poll migration to questions completed.\n\n";

    echo "4. Creating poll_answers table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS poll_answers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        poll_id INT NOT NULL,
        question_id INT NOT NULL,
        option_id INT DEFAULT NULL,
        text_answer TEXT,
        ip_address VARCHAR(45),
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES poll_questions(id) ON DELETE CASCADE,
        FOREIGN KEY (option_id) REFERENCES poll_options(id) ON DELETE CASCADE
    )");
    echo "poll_answers table created.\n\n";

    echo "5. Migrating poll_votes to poll_answers...\n";
    // Check if poll_votes exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'poll_votes'");
    $stmt->execute();
    if ($stmt->fetch()) {
        $stmtMigrateVotes = $pdo->prepare("
            INSERT INTO poll_answers (poll_id, question_id, option_id, ip_address, submitted_at)
            SELECT v.poll_id, o.question_id, v.option_id, v.ip_address, v.submitted_at
            FROM poll_votes v
            JOIN poll_options o ON v.option_id = o.id
            WHERE NOT EXISTS (
                SELECT 1 FROM poll_answers a WHERE a.poll_id = v.poll_id AND a.option_id = v.option_id AND a.ip_address = v.ip_address
            )
        ");
        $stmtMigrateVotes->execute();
        $migratedCount = $stmtMigrateVotes->rowCount();
        echo "Migrated $migratedCount votes to poll_answers.\n\n";
    }

    $pdo->commit();
    echo "MIGRATION SUCCESSFUL!</pre>";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Migration failed: " . $e->getMessage() . "\n");
}
