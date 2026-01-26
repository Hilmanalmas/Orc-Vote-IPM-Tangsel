<?php
require_once 'config.php';

$totalVotes = $pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();

header('Content-Type: application/json');
echo json_encode(['total_votes' => $totalVotes]);
exit;
