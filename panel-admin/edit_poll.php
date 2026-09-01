<?php
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login");
    exit;
}

$pollId = $_GET['id'] ?? 0;
$adminId = $_SESSION['admin_id'];
$adminRole = $_SESSION['admin_role'] ?? 'admin';
$settings = getSettings($pdo, $adminId);

// Verify ownership
$stmt = $pdo->prepare("SELECT * FROM polls WHERE id = ? AND admin_id = ?");
$stmt->execute([$pollId, $adminId]);
$poll = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$poll) {
    die("Form tidak ditemukan atau Anda tidak memiliki akses.");
}

// Fetch Questions
$stmtQ = $pdo->prepare("SELECT * FROM poll_questions WHERE poll_id = ? ORDER BY order_num ASC, id ASC");
$stmtQ->execute([$pollId]);
$questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

// Fetch all options
$stmtO = $pdo->prepare("SELECT * FROM poll_options WHERE poll_id = ?");
$stmtO->execute([$pollId]);
$allOptions = $stmtO->fetchAll(PDO::FETCH_ASSOC);

// Group options by question_id
$optionsByQuestion = [];
foreach ($allOptions as $opt) {
    $optionsByQuestion[$opt['question_id']][] = $opt;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Form - Admin Panel</title>
    <link rel="icon" type="image/png" href="../media/Logo%20Orch-Vote.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                    <i class="fas fa-edit"></i>
                <?php endif; ?>
                Orch-Vote<span>Edit Form</span>
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

    <main class="admin-content">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 style="margin: 0;">Edit Form Polling</h2>
                <a href="manage_polls" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div style="background: #fee2e2; color: #dc2626; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; border-left: 4px solid #dc2626;">
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['msg'])): ?>
                <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; border-left: 4px solid #166534;">
                    <?= htmlspecialchars($_GET['msg']) ?>
                </div>
            <?php endif; ?>

            <div style="background: #f9fafb; padding: 1.5rem; border: 1px solid #e5e7eb; border-radius: 8px;">
                <form action="actions.php?action=edit_poll" method="POST" id="edit-poll-form">
                    <input type="hidden" name="id" value="<?= $poll['id'] ?>">

                    <div class="input-group">
                        <label>Judul Form Polling</label>
                        <input type="text" name="title" required value="<?= htmlspecialchars($poll['title']) ?>">
                    </div>
                    <div class="input-group">
                        <label>Deskripsi (Opsional)</label>
                        <textarea name="description" rows="2"><?= htmlspecialchars($poll['description'] ?? '') ?></textarea>
                    </div>
                    <div class="input-group">
                        <label>Pesan Sukses (Opsional)</label>
                        <textarea name="success_message" rows="2"><?= htmlspecialchars($poll['success_message'] ?? '') ?></textarea>
                        <span style="font-size: 0.8rem; color: #6b7280;">Teks ini akan muncul di halaman terpisah setelah user berhasil mengirim tanggapan.</span>
                    </div>

                    <div style="background: #fff3cd; color: #856404; padding: 1rem; border-radius: 6px; margin: 1.5rem 0; border: 1px solid #ffeeba;">
                        <strong><i class="fas fa-exclamation-triangle"></i> Peringatan Penting:</strong> Menghapus pertanyaan atau menghapus pilihan ganda yang *sudah ada* akan mengakibatkan **hilangnya data jawaban responden** yang terkait dengan item tersebut. 
                    </div>

                    <hr style="margin: 2rem 0; border: none; border-top: 1px solid #e5e7eb;">
                    <h4 class="mb-2">Daftar Pertanyaan</h4>
                    
                    <div id="questions-container">
                        <?php foreach ($questions as $q): ?>
                            <?php $qId = $q['id']; ?>
                            <div class="question-block" style="background: white; padding: 1rem; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 1rem; position: relative;">
                                <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                                    <div style="flex: 1;">
                                        <label>Pertanyaan</label>
                                        <input type="text" name="questions[<?= $qId ?>][text]" required value="<?= htmlspecialchars($q['question_text']) ?>">
                                    </div>
                                    <div>
                                        <label>Tipe Pertanyaan</label>
                                        <select name="questions[<?= $qId ?>][type]" class="question-type-select" onchange="toggleOptions(this, '<?= $qId ?>')" style="width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 6px;">
                                            <option value="short_text" <?= $q['question_type'] === 'short_text' ? 'selected' : '' ?>>Teks Singkat</option>
                                            <option value="long_text" <?= $q['question_type'] === 'long_text' ? 'selected' : '' ?>>Teks Panjang (Paragraf)</option>
                                            <option value="polling" <?= $q['question_type'] === 'polling' ? 'selected' : '' ?>>Pilihan Ganda (Polling)</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="options-container-block" id="options-container-<?= $qId ?>" style="margin-bottom: 1rem; <?= $q['question_type'] === 'polling' ? 'display: block;' : 'display: none;' ?>">
                                    <label>Pilihan Jawaban</label>
                                    <div class="options-list">
                                        <?php if ($q['question_type'] === 'polling' && isset($optionsByQuestion[$qId])): ?>
                                            <?php foreach ($optionsByQuestion[$qId] as $opt): ?>
                                                <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                                                    <input type="text" name="questions[<?= $qId ?>][options][<?= $opt['id'] ?>]" required value="<?= htmlspecialchars($opt['option_text']) ?>">
                                                    <button type="button" class="btn btn-danger btn-sm" style="padding: 0 0.5rem;" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <!-- Fallback if it was changed from text to polling during edit -->
                                            <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                                                <input type="text" name="questions[<?= $qId ?>][options][new_opt_0]" placeholder="Pilihan 1" <?= $q['question_type'] === 'polling' ? 'required' : '' ?>>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="addOptionField(this, '<?= $qId ?>')">+ Tambah Pilihan</button>
                                </div>

                                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #eee; padding-top: 1rem;">
                                    <label style="display: flex; align-items: center; gap: 0.5rem; margin: 0; cursor: pointer;">
                                        <input type="checkbox" name="questions[<?= $qId ?>][is_required]" value="1" <?= $q['is_required'] ? 'checked' : '' ?> style="width: auto;"> Wajib Diisi
                                    </label>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeQuestionBlock(this)"><i class="fas fa-trash"></i> Hapus Pertanyaan</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="button" class="btn btn-secondary mb-4" onclick="addQuestionBlock()" style="width: 100%; border-style: dashed;"><i class="fas fa-plus"></i> Tambah Pertanyaan Baru</button>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem;"><i class="fas fa-save"></i> Simpan Perubahan Form</button>
                </form>
            </div>
        </div>
    </main>

    <script>
        let newQuestionCount = 1;
        let newOptionCount = 1;

        function addQuestionBlock() {
            const container = document.getElementById('questions-container');
            const qId = 'new_' + newQuestionCount++;
            
            const div = document.createElement('div');
            div.className = 'question-block';
            div.style.cssText = 'background: white; padding: 1rem; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 1rem; position: relative;';
            div.innerHTML = `
                <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                    <div style="flex: 1;">
                        <label>Pertanyaan</label>
                        <input type="text" name="questions[${qId}][text]" required placeholder="Ketik pertanyaan...">
                    </div>
                    <div>
                        <label>Tipe Pertanyaan</label>
                        <select name="questions[${qId}][type]" class="question-type-select" onchange="toggleOptions(this, '${qId}')" style="width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 6px;">
                            <option value="short_text">Teks Singkat</option>
                            <option value="long_text">Teks Panjang (Paragraf)</option>
                            <option value="polling">Pilihan Ganda (Polling)</option>
                        </select>
                    </div>
                </div>
                
                <div class="options-container-block" id="options-container-${qId}" style="margin-bottom: 1rem; display: none;">
                    <label>Pilihan Jawaban</label>
                    <div class="options-list">
                        <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <input type="text" name="questions[${qId}][options][new_opt_${newOptionCount++}]" placeholder="Pilihan 1">
                            <button type="button" class="btn btn-danger btn-sm" style="padding: 0 0.5rem;" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="addOptionField(this, '${qId}')">+ Tambah Pilihan</button>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #eee; padding-top: 1rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; margin: 0; cursor: pointer;">
                        <input type="checkbox" name="questions[${qId}][is_required]" value="1" checked style="width: auto;"> Wajib Diisi
                    </label>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeQuestionBlock(this)"><i class="fas fa-trash"></i> Hapus Pertanyaan</button>
                </div>
            `;
            container.appendChild(div);
        }

        function toggleOptions(selectElem, qId) {
            const optionsBlock = document.getElementById('options-container-' + qId);
            const inputs = optionsBlock.querySelectorAll('input[type="text"]');
            if (selectElem.value === 'polling') {
                optionsBlock.style.display = 'block';
                inputs.forEach(input => input.setAttribute('required', 'required'));
            } else {
                optionsBlock.style.display = 'none';
                inputs.forEach(input => input.removeAttribute('required'));
            }
        }

        function removeQuestionBlock(btn) {
            if (confirm("Yakin ingin menghapus pertanyaan ini? (Jika sudah disave, jawaban terkait pertanyaan ini akan hilang)")) {
                btn.closest('.question-block').remove();
            }
        }

        function addOptionField(btn, qId) {
            const list = btn.previousElementSibling;
            const count = list.children.length + 1;
            const optId = 'new_opt_' + newOptionCount++;
            const div = document.createElement('div');
            div.style.cssText = 'display: flex; gap: 0.5rem; margin-bottom: 0.5rem;';
            div.innerHTML = `
                <input type="text" name="questions[${qId}][options][${optId}]" required placeholder="Pilihan ${count}">
                <button type="button" class="btn btn-danger btn-sm" style="padding: 0 0.5rem;" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
            `;
            list.appendChild(div);
        }
    </script>
</body>
</html>
