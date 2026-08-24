<?php
// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'ipm_voting');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

// Start Session globally
session_start();

// Helper Functions
function getSettings($pdo, $adminId = null) {
    if ($adminId) {
        $stmt = $pdo->prepare("SELECT * FROM settings WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        $data = $stmt->fetch();
        if ($data) return $data;
    }
    // Fallback or Default
    return ['min_vote' => 1, 'max_vote' => 1, 'voting_enabled' => 1];
}

// CSRF Functions
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    return false;
}
?>
