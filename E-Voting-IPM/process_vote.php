<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['voter_token'])) {
    header("Location: index.php");
    exit;
}

$token = $_SESSION['voter_token'];
$selectedCandidates = $_POST['votes'] ?? [];

$settings = getSettings($pdo);

// Validation
if (count($selectedCandidates) < $settings['min_vote'] || count($selectedCandidates) > $settings['max_vote']) {
    die("Error: Jumlah pilihan tidak sesuai aturan.");
}

try {
    $pdo->beginTransaction();

    // Double check token usage
    $stmt = $pdo->prepare("SELECT is_used FROM tokens WHERE code = ? FOR UPDATE");
    $stmt->execute([$token]);
    $tokenData = $stmt->fetch();

    if (!$tokenData || $tokenData['is_used']) {
        throw new Exception("Token tidak valid atau sudah digunakan.");
    }

    // Insert Votes
    $stmt = $pdo->prepare("INSERT INTO votes (candidate_id, token_code) VALUES (?, ?)");
    foreach ($selectedCandidates as $candId) {
        $stmt->execute([$candId, $token]);
    }

    // Mark Token Used
    $stmt = $pdo->prepare("UPDATE tokens SET is_used = 1 WHERE code = ?");
    $stmt->execute([$token]);

    $pdo->commit();

    // Clear Session
    unset($_SESSION['voter_token']);

    // Redirect to Success or Result
    echo "<script>alert('Terima kasih! Suara berhasil dikirim.'); window.location.href='index.php';</script>";
} catch (Exception $e) {
    $pdo->rollBack();
    die("Terjadi kesalahan: " . $e->getMessage());
}
