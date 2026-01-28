<?php
require_once 'config.php';
$admins = $pdo->query("SELECT id, organization_name FROM admins ORDER BY id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="media/Logo%20Orch-Vote.png">
    <title>Login Voter - Orch-Vote</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="landing-page">
    <header>
        <div class="container nav-wrapper">
            <div class="logo">
                <i class="fas fa-vote-yea"></i>Orch-Vote
            </div>
        </div>
    </header>

    <main class="container auth-container" style="flex-direction: column; justify-content: center; min-height: 70vh;">
        <div class="card" style="max-width: 500px; margin: 0 auto; text-align: center;">
            <i class="fas fa-vote-yea" style="font-size: 3rem; color: #F47F3D;"></i>
            <h2 class="mb-4" style="margin-top: 1rem; color: var(--text-color);">Selamat Datang di Orch-Vote</h2>
            <p class="mb-4" style="color: #6b7280;">Silakan pilih organisasi/nama kegiatan Anda:</p>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach($admins as $admin): ?>
                    <a href="index.php?org_id=<?= $admin['id'] ?>" class="btn" style="background: #f3f4f6; color: #1f2937; text-align: left; padding: 1rem; border: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; text-decoration: none;">
                        <span style="font-weight: 600;"><?= htmlspecialchars($admin['organization_name']) ?></span>
                        <i class="fas fa-chevron-right" style="color: #9ca3af;"></i>
                    </a>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 2rem; font-size: 0.875rem;">
                <a href="admin/login.php" style="color: #f59e0b;">Login Admin</a>
            </div>
        </div>
    </main>
    <?php $basePath = ''; include 'footer.php'; ?>
</body>
</html>
