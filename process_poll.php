<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /");
    exit;
}

$pollId = $_POST['poll_id'] ?? 0;
$optionId = $_POST['option_id'] ?? 0;

// Get IP Address safely
function getClientIP() {
    if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else if (array_key_exists('HTTP_X_REAL_IP', $_SERVER)) {
        return $_SERVER['HTTP_X_REAL_IP'];
    }
    return $_SERVER['REMOTE_ADDR'];
}

$ipAddress = getClientIP();

if (!$pollId || !$optionId) {
    header("Location: poll?id=" . $pollId . "&error=Pilihan tidak valid");
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Check if Poll is active
    $stmt = $pdo->prepare("SELECT is_active FROM polls WHERE id = ? FOR UPDATE");
    $stmt->execute([$pollId]);
    $poll = $stmt->fetch();

    if (!$poll) {
        throw new Exception("Polling tidak ditemukan.");
    }
    if (!$poll['is_active']) {
        throw new Exception("Polling sudah ditutup.");
    }

    // 2. Check Option validity
    $stmt = $pdo->prepare("SELECT id FROM poll_options WHERE id = ? AND poll_id = ?");
    $stmt->execute([$optionId, $pollId]);
    if (!$stmt->fetch()) {
        throw new Exception("Pilihan tidak valid.");
    }

    // 3. Check Cookie (Limit 1 vote per browser per poll)
    $cookieName = "voted_poll_" . $pollId;
    if (isset($_COOKIE[$cookieName])) {
        throw new Exception("Anda sudah pernah memberikan suara pada polling ini (Berdasarkan sesi perangkat).");
    }

    // 4. Insert Vote (We still record IP for auditing, but don't use it to block)
    $stmt = $pdo->prepare("INSERT INTO poll_votes (poll_id, option_id, ip_address) VALUES (?, ?, ?)");
    $stmt->execute([$pollId, $optionId, $ipAddress]);

    $pdo->commit();

    // 5. Set Cookie to prevent double voting (expires in 1 year)
    setcookie($cookieName, "1", time() + (365 * 24 * 60 * 60), "/");

    // Success redirect
    header("Location: poll?id=" . $pollId . "&msg=Terima kasih, suara Anda telah berhasil disimpan!");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: poll?id=" . $pollId . "&error=" . urlencode($e->getMessage()));
    exit;
}
