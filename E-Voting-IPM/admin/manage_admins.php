<?php
require_once '../config.php';

// Auth Check Removed as per request
// if (!isset($_SESSION['admin_id'])) { ... }

// Fetch Admins
$adminsList = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../media/Logo%20Orch-Vote.png">
    <title>Admin Account Manager - Orch-Vote</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body id="admin-page">
    <header>
        <div class="container nav-wrapper">
            <div class="logo">
                <i class="fas fa-vote-yea"></i>Orch-Vote<span>Admin Account Manager</span>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Admin Dashboard</a></li>
                    <li><a href="#" class="active">Admin Account Manager</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <h1 class="mb-4" style="margin-top: 2rem;">Manajemen Akun Admin</h1>

        <?php if (isset($_GET['msg'])): ?>
            <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                <?= htmlspecialchars($_GET['msg']) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div style="background: #fee2e2; color: #dc2626; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <div class="settings-section">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- List Admin -->
                <div>
                    <h3 class="mb-2">Daftar Admin</h3>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($adminsList as $admin): ?>
                            <li style="background: #f9fafb; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
                                <span>
                                    <i class="fas fa-user"></i> <strong><?= htmlspecialchars($admin['username']) ?></strong>
                                    <small style="color: #6b7280;">(<?= htmlspecialchars($admin['organization_name'] ?? '-') ?>)</small>
                                    <?php if ($admin['id'] == $_SESSION['admin_id']): ?>
                                        <span style="font-size: 0.8em; color: green;">(You)</span>
                                    <?php endif; ?>
                                </span>
                                <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                    <a href="actions.php?action=delete_admin&id=<?= $admin['id'] ?>&redirect=manage_admins.php" onclick="return confirm('Hapus admin ini?')" style="color: #ef4444;"><i class="fas fa-trash"></i></a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Add Admin -->
                <div>
                    <h3 class="mb-2">Tambah Admin Baru</h3>
                    <form action="actions.php?action=add_admin&redirect=manage_admins.php" method="POST">
                        <div class="input-group">
                            <label>Username</label>
                            <input type="text" name="username" required autocomplete="off">
                        </div>
                        <div class="input-group">
                            <label>Nama Organisasi (Cabang/Ranting)</label>
                            <input type="text" name="organization_name" placeholder="Contoh: Cabang Pamulang" required>
                        </div>
                        <div class="input-group">
                            <label>Password</label>
                            <input type="password" name="password" required autocomplete="new-password">
                        </div>
                        <button type="submit" class="btn btn-primary">Tambah Admin</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <?php $basePath = '../'; include '../footer.php'; ?>
</body>
</html>
