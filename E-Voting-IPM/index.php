<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = strtoupper(trim($_POST['token']));

    $stmt = $pdo->prepare("SELECT * FROM tokens WHERE code = ?");
    $stmt->execute([$token]);
    $tokenData = $stmt->fetch();

    if ($tokenData) {
        if ($tokenData['is_used']) {
            $error = 'Token sudah digunakan!';
        } else {
            $_SESSION['voter_token'] = $token;
            header("Location: vote.php");
            exit;
        }
    } else {
        $error = 'Token tidak valid!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E-Voting IPM Tangsel</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body id="login-page">
    <div class="auth-container">
        <div class="card">
            <div class="logo" style="justify-content: center; margin-bottom: 2rem; color: var(--primary-color);">
                <i class="fas fa-vote-yea" style="font-size: 2.5rem;"></i>
            </div>
            <h1 class="mb-2">Selamat Datang</h1>
            <p class="mb-4" style="color: #6b7280;">Silakan masukkan token untuk memilih.</p>

            <?php if ($error): ?>
                <div style="color: white; background: #ef4444; padding: 0.5rem; border-radius: 8px; margin-bottom: 1rem;">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="input-group">
                    <input type="text" name="token" placeholder="Masukkan Token" required autocomplete="off" style="text-align: center; letter-spacing: 2px; text-transform: uppercase; font-size: 1.5rem;">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Masuk <i class="fas fa-arrow-right" style="margin-left: 0.5rem;"></i></button>
            </form>
        </div>
    </div>
</body>

</html>