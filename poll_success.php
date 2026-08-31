<?php
require_once 'config.php';

$pollSlug = $_GET['slug'] ?? '';

// Fetch Poll
$stmt = $pdo->prepare("SELECT * FROM polls WHERE slug = ?");
$stmt->execute([$pollSlug]);
$poll = $stmt->fetch();

if (!$poll) {
    die("Polling tidak ditemukan.");
}

// Fetch Admin Settings for Theme
$stmt = $pdo->prepare("SELECT * FROM settings WHERE admin_id = ?");
$stmt->execute([$poll['admin_id']]);
$settings = $stmt->fetch();

if (!$settings) {
    $settings = [
        'theme_color' => '#00984B',
        'logo_path' => 'media/Logo_PD_IPM.png'
    ];
}

$colors = explode(',', $settings['theme_color']);
$primary = $colors[0] ?? '#00984B';
$accent = $colors[1] ?? $primary;
$dark = $colors[2] ?? $primary;

$successMsg = !empty($poll['success_message']) ? $poll['success_message'] : "Terima kasih, suara Anda telah berhasil disimpan!";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berhasil - <?= htmlspecialchars($poll['title']) ?></title>
    <link rel="icon" type="image/png" href="media/Logo%20Orch-Vote.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: <?= htmlspecialchars($primary) ?>;
            --accent-color: <?= htmlspecialchars($accent) ?>;
            --primary-dark: <?= htmlspecialchars($dark) ?>;
        }
        
        .success-container {
            max-width: 600px;
            margin: 2rem auto;
            background: #fff;
            padding: 3rem 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border-top: 5px solid var(--primary-color);
            text-align: center;
        }
    </style>
</head>
<body id="login-page">
    
    <div style="text-align: center; margin-top: 3rem; margin-bottom: 1rem;">
        <?php if ($settings['logo_path']): ?>
            <img src="<?= htmlspecialchars($settings['logo_path']) ?>" alt="Logo" style="height: 100px; width: auto; object-fit: contain;">
        <?php else: ?>
            <i class="fas fa-vote-yea" style="font-size: 4rem; color: var(--primary-color);"></i>
        <?php endif; ?>
    </div>

    <main class="container">
        <div class="success-container">
            <i class="fas fa-check-circle" style="font-size: 5rem; color: var(--primary-color); margin-bottom: 1.5rem;"></i>
            
            <h2 style="color: #1f2937; margin-bottom: 1rem;"><?= htmlspecialchars($poll['title']) ?></h2>
            
            <p style="color: #4b5563; font-size: 1.1rem; line-height: 1.6; margin-bottom: 2rem;">
                <?= nl2br(htmlspecialchars($successMsg)) ?>
            </p>
            
            <a href="poll?slug=<?= urlencode($poll['slug']) ?>" class="btn btn-primary" style="display: inline-block; padding: 0.75rem 2rem; border-radius: 8px;">
                Kembali ke Polling
            </a>
        </div>
    </main>

</body>
</html>
