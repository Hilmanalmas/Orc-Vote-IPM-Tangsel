<?php
// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'ipm_voting');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

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
        if ($data) {
            // Provide defaults if null
            $data['logo_path'] = $data['logo_path'] ?? 'media/Logo_PD_IPM.png';
            $data['theme_color'] = $data['theme_color'] ?? '#00984B,#E86729,#00743F';
            return $data;
        }
    }
    // Fallback or Default
    return [
        'min_vote' => 1, 
        'max_vote' => 1, 
        'voting_enabled' => 1,
        'logo_path' => 'media/Logo_PD_IPM.png',
        'theme_color' => '#00984B,#E86729,#00743F'
    ];
}
?>
