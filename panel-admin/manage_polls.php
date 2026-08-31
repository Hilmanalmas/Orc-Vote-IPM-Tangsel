<?php
require_once '../config.php';

// Auth Check
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
$adminId = $_SESSION['admin_id'];
$adminRole = $_SESSION['admin_role'] ?? 'admin';

// Get Current Admin Info
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$adminId]);
$currentAdmin = $stmt->fetch();

// Fetch Polls
$stmt = $pdo->prepare("SELECT * FROM polls WHERE admin_id = ? ORDER BY created_at DESC");
$stmt->execute([$adminId]);
$polls = $stmt->fetchAll();

// Get settings for logo/theme
$settings = getSettings($pdo, $adminId);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../media/Logo%20Orch-Vote.png">
    <title>Manage Polls - <?= htmlspecialchars($currentAdmin['organization_name']) ?></title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body id="admin-page">
    <header>
        <div class="container nav-wrapper">
            <div class="logo">
                <i class="fas fa-poll"></i>Orch-Vote<span>Manage Polls</span>
            </div>
            <nav>
                <ul>
                    <?php if ($adminRole !== 'poll_admin'): ?>
                        <li><a href="index.php">Admin Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="manage_polls" class="active"><i class="fas fa-list"></i> Manage Polls</a></li>
                    <?php if ($adminRole === 'master'): ?>
                    <li><a href="manage_admins" style="color: #f59e0b;"><i class="fas fa-users-cog"></i> Manage Admins</a></li>
                    <?php endif; ?>
                    <li><a href="logout" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <h1 class="mb-4" style="margin-top: 2rem;">Manajemen Polling Pendapat</h1>

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
            <div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
                
                <!-- Create Poll -->
                <div style="background: #f9fafb; padding: 1.5rem; border: 1px solid #e5e7eb; border-radius: 8px;">
                    <h3 class="mb-2">Buat Polling Baru</h3>
                    <form action="actions.php?action=create_poll" method="POST">
                        <div class="input-group">
                            <label>Judul Polling (Pertanyaan)</label>
                            <input type="text" name="title" required placeholder="Contoh: Apa pendapat Anda tentang kegiatan ini?">
                        </div>
                        <div class="input-group">
                            <label>Deskripsi (Opsional)</label>
                            <textarea name="description" rows="2" placeholder="Informasi tambahan terkait polling ini..."></textarea>
                        </div>
                        <div class="input-group" id="options-container">
                            <label>Pilihan Jawaban</label>
                            <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                                <input type="text" name="options[]" required placeholder="Pilihan 1">
                            </div>
                            <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                                <input type="text" name="options[]" required placeholder="Pilihan 2">
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm mb-2" onclick="addOptionField()">+ Tambah Pilihan</button>
                        <br>
                        <button type="submit" class="btn btn-primary mt-2">Buat Polling</button>
                    </form>
                </div>
                
                <!-- Polls List -->
                <div>
                    <h3 class="mb-2">Daftar Polling</h3>
                    <?php if (empty($polls)): ?>
                        <p style="color: #6b7280;">Belum ada polling yang dibuat.</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php foreach ($polls as $poll): ?>
                                <?php
                                    // Fetch options
                                    $stmt = $pdo->prepare("SELECT * FROM poll_options WHERE poll_id = ?");
                                    $stmt->execute([$poll['id']]);
                                    $options = $stmt->fetchAll();
                                    
                                    // Fetch vote count
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM poll_votes WHERE poll_id = ?");
                                    $stmt->execute([$poll['id']]);
                                    $totalVotes = $stmt->fetchColumn();
                                ?>
                                <div style="background: #fff; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                        <div>
                                            <h4 style="margin: 0; font-size: 1.25rem; color: #1f2937;"><?= htmlspecialchars($poll['title']) ?></h4>
                                            <?php if ($poll['description']): ?>
                                                <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.9rem;"><?= htmlspecialchars($poll['description']) ?></p>
                                            <?php endif; ?>
                                            <span style="display: inline-block; margin-top: 0.5rem; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem; background: <?= $poll['is_active'] ? '#dcfce7' : '#fee2e2' ?>; color: <?= $poll['is_active'] ? '#166534' : '#dc2626' ?>;">
                                                <?= $poll['is_active'] ? 'Aktif' : 'Ditutup' ?>
                                            </span>
                                            <span style="font-size: 0.8rem; color: #6b7280; margin-left: 0.5rem;">(<?= $totalVotes ?> Suara)</span>
                                        </div>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <button onclick="copyPollLink('<?= $poll['id'] ?>')" class="btn btn-secondary btn-sm" title="Copy Link"><i class="fas fa-link"></i> Copy Link</button>
                                            <a href="poll_results?id=<?= $poll['id'] ?>" class="btn btn-accent btn-sm"><i class="fas fa-chart-bar"></i> Hasil</a>
                                            <a href="actions.php?action=toggle_poll&id=<?= $poll['id'] ?>&state=<?= $poll['is_active'] ? 0 : 1 ?>" class="btn btn-<?= $poll['is_active'] ? 'danger' : 'success' ?> btn-sm">
                                                <?= $poll['is_active'] ? '<i class="fas fa-times-circle"></i> Tutup' : '<i class="fas fa-check-circle"></i> Buka' ?>
                                            </a>
                                            <a href="actions.php?action=delete_poll&id=<?= $poll['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus polling ini? Semua data suara akan hilang.')"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </div>
                                    <div style="background: #f9fafb; padding: 0.5rem; border-radius: 4px;">
                                        <strong>Pilihan:</strong>
                                        <ul style="margin: 0.25rem 0 0 1.5rem; padding: 0; color: #4b5563;">
                                            <?php foreach ($options as $opt): ?>
                                                <li><?= htmlspecialchars($opt['option_text']) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        function addOptionField() {
            const container = document.getElementById('options-container');
            const count = container.children.length; // Includes label
            const div = document.createElement('div');
            div.style.cssText = 'display: flex; gap: 0.5rem; margin-bottom: 0.5rem;';
            div.innerHTML = `
                <input type="text" name="options[]" required placeholder="Pilihan ${count}">
                <button type="button" class="btn btn-danger btn-sm" style="padding: 0 0.5rem;" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
            `;
            container.appendChild(div);
        }

        function copyPollLink(pollId) {
            // Use Javascript's window.location to get the real public domain regardless of reverse proxies
            let basePath = window.location.pathname.replace('/panel-admin/manage_polls.php', '').replace('/panel-admin/manage_polls', '');
            let link = window.location.origin + basePath + '/poll?id=' + pollId;
            
            navigator.clipboard.writeText(link).then(() => {
                alert('Link polling disalin: ' + link);
            }).catch(err => {
                console.error('Failed to copy: ', err);
                alert('Gagal menyalin link.');
            });
        }
    </script>
    
    <?php $basePath = '../'; include '../footer.php'; ?>
</body>
</html>
