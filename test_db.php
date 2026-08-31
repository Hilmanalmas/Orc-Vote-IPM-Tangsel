<?php
require 'config.php';
$stmt = $pdo->query("DESCRIBE polls");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
