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

$settings = getSettings($pdo, $adminId);

// Fetch Polls
$stmt = $pdo->prepare("SELECT * FROM polls WHERE admin_id = ? ORDER BY created_at DESC");
$stmt->execute([$adminId]);
$polls = $stmt->fetchAll();

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
    <?php
        $colors = explode(',', $settings['theme_color']);
        $primary = $colors[0] ?? '#00984B';
        $accent = $colors[1] ?? $primary;
        $dark = $colors[2] ?? $primary;
    ?>
    <style>
        :root {
            --primary-color: <?= htmlspecialchars($primary) ?>;
            --accent-color: <?= htmlspecialchars($accent) ?>;
            --primary-dark: <?= htmlspecialchars($dark) ?>;
        }
    </style>
</head>
<body id="admin-page">
    <header>
        <div class="container nav-wrapper">
            <div class="logo">
                <?php if ($settings['logo_path'] && $settings['logo_path'] !== 'media/Logo_PD_IPM.png'): ?>
                    <img src="<?= (strpos($settings['logo_path'], 'http') === 0) ? htmlspecialchars($settings['logo_path']) : '../' . htmlspecialchars($settings['logo_path']) ?>" alt="Logo" style="height: 80px; width: auto; margin-right: 10px;">
                <?php else: ?>
                    <i class="fas fa-list"></i>
                <?php endif; ?>
                Orch-Vote<span>Manage Polls</span>
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
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['msg']) ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div style="background: #fee2e2; color: #dc2626; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <!-- Settings Section -->
        <div class="settings-section mb-4">
            <h2 class="mb-2"><i class="fas fa-cogs"></i> Pengaturan Tema & Logo Polling</h2>
            <form action="actions.php?action=update_settings" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="min_vote" value="<?= $settings['min_vote'] ?>">
                <input type="hidden" name="max_vote" value="<?= $settings['max_vote'] ?>">
                
                <div class="input-group">
                    <label>Nama Pembuat / Sapaan:</label>
                    <input type="text" name="organization_name" value="<?= htmlspecialchars($currentAdmin['organization_name']) ?>" required>
                </div>
                
                <div class="input-group">
                    <label>Logo Polling</label>
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                        <img id="logo-preview" src="<?= (strpos($settings['logo_path'], 'http') === 0) ? htmlspecialchars($settings['logo_path']) : '../' . htmlspecialchars($settings['logo_path']) ?>" style="height: 120px; width: auto; max-width: 250px; object-fit: contain; border: 1px solid #ddd; border-radius: 4px; padding: 4px; background: #fff;">
                        <input type="file" name="org_logo" id="org-logo-input" accept="image/*">
                    </div>
                    <p style="font-size: 0.8rem; color: #6b7280;">Upload logo untuk menyesuaikan warna tema secara otomatis.</p>
                </div>

                <div class="input-group">
                    <label>Warna Tema Terdeteksi</label>
                    <div style="display: flex; align-items: center; gap: 1rem;" id="color-previews-container">
                        <?php 
                            $colors = explode(',', $settings['theme_color']);
                            for ($i = 0; $i < 3; $i++) {
                                $c = $colors[$i] ?? $colors[0] ?? '#cccccc';
                                echo "<div class='color-preview-circle' style='width: 40px; height: 40px; border-radius: 50%; background-color: " . htmlspecialchars($c) . "; border: 2px solid #e2e8f0;' title='Warna " . ($i+1) . "'></div>";
                            }
                        ?>
                    </div>
                    <input type="hidden" name="theme_color" id="theme-color-input" value="<?= htmlspecialchars($settings['theme_color']) ?>">
                    <div id="color-hex-text" style="font-family: monospace; color: #4b5563; margin-top: 0.5rem; font-size: 0.85rem;"><?= htmlspecialchars($settings['theme_color']) ?></div>
                </div>

                <button type="submit" class="btn btn-success" style="margin-top: 1rem;"><i class="fas fa-save"></i> Simpan Pengaturan</button>
            </form>
        </div>

        <div class="dashboard-grid mb-4" style="grid-template-columns: 1fr;">
            
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
                            <textarea name="description" rows="2" placeholder="Penjelasan singkat..."></textarea>
                        </div>
                        <div class="input-group">
                            <label>Pesan Sukses (Opsional)</label>
                            <textarea name="success_message" rows="2" placeholder="Terima kasih, suara Anda telah berhasil disimpan!"></textarea>
                            <span style="font-size: 0.8rem; color: #6b7280;">Teks ini akan muncul di halaman terpisah setelah user berhasil melakukan voting.</span>
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
                                            <button onclick='openEditPoll(<?= json_encode($poll) ?>, <?= json_encode($options) ?>)' class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Edit</button>
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
    </script>
    
    <!-- Edit Poll Modal -->
    <div id="edit-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 100; align-items: center; justify-content: center;">
        <div style="background: white; padding: 2rem; border-radius: 8px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto;">
            <h2 class="mb-4">Edit Polling</h2>
            <form action="actions.php?action=edit_poll" method="POST">
                <input type="hidden" name="id" id="edit-poll-id">
                
                <div class="input-group">
                    <label>Judul Polling</label>
                    <input type="text" name="title" id="edit-poll-title" required>
                </div>

                <div class="input-group">
                    <label>Deskripsi (Opsional)</label>
                    <textarea name="description" id="edit-poll-desc" rows="2"></textarea>
                </div>

                <div class="input-group">
                    <label>Pesan Sukses (Opsional)</label>
                    <textarea name="success_message" id="edit-poll-success" rows="2"></textarea>
                </div>

                <div class="input-group">
                    <label>Pilihan Jawaban (Hanya ubah teks)</label>
                    <div id="edit-options-container">
                        <!-- Options will be injected here via JS -->
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1rem;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('edit-modal').style.display='none'">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditPoll(poll, options) {
            document.getElementById('edit-poll-id').value = poll.id;
            document.getElementById('edit-poll-title').value = poll.title;
            document.getElementById('edit-poll-desc').value = poll.description || '';
            document.getElementById('edit-poll-success').value = poll.success_message || '';
            
            const container = document.getElementById('edit-options-container');
            container.innerHTML = '';
            
            options.forEach(opt => {
                const div = document.createElement('div');
                div.style.cssText = 'display: flex; gap: 0.5rem; margin-bottom: 0.5rem;';
                div.innerHTML = `
                    <input type="hidden" name="edit_option_ids[]" value="${opt.id}">
                    <input type="text" name="edit_options[]" value="${opt.option_text}" required style="width: 100%;">
                `;
                container.appendChild(div);
            });

            document.getElementById('edit-modal').style.display = 'flex';
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
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/color-thief/2.3.0/color-thief.umd.js"></script>
    <script>
        const logoInput = document.getElementById('org-logo-input');
        const logoPreview = document.getElementById('logo-preview');
        const previewsContainer = document.getElementById('color-previews-container');
        const colorInput = document.getElementById('theme-color-input');
        const colorHexText = document.getElementById('color-hex-text');
        
        // Helper to convert RGB to HEX
        const rgbToHex = (r, g, b) => '#' + [r, g, b].map(x => {
            const hex = x.toString(16);
            return hex.length === 1 ? '0' + hex : hex;
        }).join('');

        if(logoInput) {
            logoInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        logoPreview.src = event.target.result;
                        
                        // Use ColorThief
                        const img = new Image();
                        img.src = event.target.result;
                        img.onload = function() {
                            try {
                                const colorThief = new ColorThief();
                                // Get up to 3 colors
                                const palette = colorThief.getPalette(img, 3);
                                
                                if (palette && palette.length > 0) {
                                    const hexColors = palette.map(p => rgbToHex(p[0], p[1], p[2]));
                                    // Ensure we always have 3 colors for the UI (fallback to first if fewer)
                                    while(hexColors.length < 3) {
                                        hexColors.push(hexColors[0]);
                                    }
                                    const top3 = hexColors.slice(0, 3);
                                    const joined = top3.join(',');
                                    
                                    // Update Previews
                                    previewsContainer.innerHTML = '';
                                    top3.forEach((hex, i) => {
                                        const div = document.createElement('div');
                                        div.className = 'color-preview-circle';
                                        div.style.cssText = `width: 40px; height: 40px; border-radius: 50%; background-color: ${hex}; border: 2px solid #e2e8f0;`;
                                        div.title = `Warna ${i+1}`;
                                        previewsContainer.appendChild(div);
                                    });
                                    
                                    colorInput.value = joined;
                                    colorHexText.textContent = joined;
                                }
                            } catch (err) {
                                console.log("ColorThief failed or image is white/transparent", err);
                            }
                        }
                    }
                    reader.readAsDataURL(file);
                }
            });
        }
    </script>
    <?php $basePath = '../'; include '../footer.php'; ?>
</body>
</html>
