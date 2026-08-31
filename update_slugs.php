<?php
require 'config.php';
$polls = $pdo->query("SELECT id FROM polls WHERE slug IS NULL")->fetchAll();
foreach ($polls as $p) {
    $slug = bin2hex(random_bytes(8));
    $pdo->prepare("UPDATE polls SET slug = ? WHERE id = ?")->execute([$slug, $p['id']]);
}
echo "Slugs updated.\n";
