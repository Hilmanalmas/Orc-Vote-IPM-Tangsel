<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['voter_token']) || !isset($_SESSION['org_id'])) {
    header("Location: index.php");
    exit;
}

$token = $_SESSION['voter_token'];
$orgId = $_SESSION['org_id'];
$selectedCandidates = $_POST['votes'] ?? [];

$settings = getSettings($pdo, $orgId);

// Validation
if (count($selectedCandidates) < $settings['min_vote'] || count($selectedCandidates) > $settings['max_vote']) {
    die("Error: Jumlah pilihan tidak sesuai aturan.");
}

try {
    $pdo->beginTransaction();

    // Double check token usage for THIS organization
    $stmt = $pdo->prepare("SELECT is_used FROM tokens WHERE code = ? AND admin_id = ? FOR UPDATE");
    $stmt->execute([$token, $orgId]);
    $tokenData = $stmt->fetch();

    if (!$tokenData || $tokenData['is_used']) {
        throw new Exception("Token tidak valid atau sudah digunakan.");
    }

    // Insert Votes with admin_id
    $stmt = $pdo->prepare("INSERT INTO votes (admin_id, candidate_id, token_code) VALUES (?, ?, ?)");
    foreach ($selectedCandidates as $candId) {
        $stmt->execute([$orgId, $candId, $token]);
    }

    // Mark Token Used
    $stmt = $pdo->prepare("UPDATE tokens SET is_used = 1 WHERE code = ? AND admin_id = ?");
    $stmt->execute([$token, $orgId]);

    $pdo->commit();

    // Clear Session Token only (keep org_id context or just pass via URL)
    unset($_SESSION['voter_token']);
    // We keep $_SESSION['org_id'] or effectively just use the URL param to get back to the right login context
    // unset($_SESSION['org_id']); 

    // Redirect to Login Page with Org ID
    echo "<script>alert('Terima kasih! Suara berhasil dikirim.'); window.location.href='index.php?org_id=$orgId';</script>";
} catch (Exception $e) {
    $pdo->rollBack();
    die("Terjadi kesalahan: " . $e->getMessage());
}
