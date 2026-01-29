<?php
require_once 'config.php';

$orgId = $_GET['org_id'] ?? 0;
$stmt = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE admin_id = ?");
$stmt->execute([$orgId]);
$totalVotes = $stmt->fetchColumn();

header('Content-Type: application/json');
echo json_encode(['total_votes' => $totalVotes]);
exit;
