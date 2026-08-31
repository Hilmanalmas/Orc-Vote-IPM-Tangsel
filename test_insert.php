<?php
require 'config.php';
try {
    $stmt = $pdo->prepare("INSERT INTO polls (admin_id, title, description, success_message) VALUES (?, ?, ?, ?)");
    $stmt->execute([1, 'Test Title', 'Test Desc', 'Test Success']);
    echo "Success! Inserted ID: " . $pdo->lastInsertId() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
