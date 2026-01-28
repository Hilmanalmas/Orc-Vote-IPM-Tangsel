<?php
require_once '../config.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../media/Logo%20Orch-Vote.png">
    <title>Login Admin - Orch-Vote</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="background-color: var(--background-color); display: flex; align-items: center; justify-content: center; min-height: 100vh;">

    <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <i class="fas fa-vote-yea" style="font-size: 3rem; color: #F47F3D;"></i>
            <h2 style="margin-top: 1rem; color: var(--text-color);">Login Admin</h2>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div style="background: #fee2e2; color: #dc2626; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; text-align: center;">
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <form action="actions.php?action=login" method="POST">
            <div class="input-group">
                <label>Username</label>
                <input type="text" name="username" required autofocus placeholder="admin">
            </div>
            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Masuk</button>
        </form>
        
        <div style="text-align: center; margin-top: 1rem;">
            <a href="../index.php" style="color: var(--secondary-color); text-decoration: none;">&larr; Kembali ke Home</a>
        </div>
    </div>

    <?php $basePath = '../'; include '../footer.php'; ?>
</body>
</html>
