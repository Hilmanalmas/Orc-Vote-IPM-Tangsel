<?php
require_once 'config.php';

// Basic Referer Check to prevent hotlinking/external abuse
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$host = $_SERVER['HTTP_HOST'] ?? '';
if (!empty($referer) && parse_url($referer, PHP_URL_HOST) !== $host) {
    http_response_code(403);
    die(json_encode(['error' => 'Forbidden']));
}

$orgId = $_GET['org_id'] ?? 0;
$stmt = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE admin_id = ?");
$stmt->execute([$orgId]);
$totalVotes = $stmt->fetchColumn();

header('Content-Type: application/json');
echo json_encode(['total_votes' => $totalVotes]);
exit;
