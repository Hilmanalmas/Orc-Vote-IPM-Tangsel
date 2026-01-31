<?php
require_once '../config.php';

// Auth Check
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch Data
$adminId = $_SESSION['admin_id'];

// Get Current Admin Info
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$adminId]);
$currentAdmin = $stmt->fetch();

$settings = getSettings($pdo, $adminId);

$stmt = $pdo->prepare("SELECT * FROM candidates WHERE admin_id = ? ORDER BY id DESC");
$stmt->execute([$adminId]);
$candidates = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM tokens WHERE admin_id = ?");
$stmt->execute([$adminId]);
$tokensCount = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE admin_id = ?");
$stmt->execute([$adminId]);
$votesCount = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM tokens WHERE admin_id = ? ORDER BY id DESC LIMIT 50");
$stmt->execute([$adminId]);
$tokensList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../media/Logo%20Orch-Vote.png">
    <title>Admin Dashboard - Orch-Vote</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body id="admin-page">
    <header>
        <div class="container nav-wrapper">
            <div class="logo">
                <i class="fas fa-vote-yea"></i>Orch-Vote<span>Admin Dashboard</span>
            </div>
            <nav>
                <ul>
                    <li><a href="../index.php">Login Voter</a></li>
                    <li><a href="../result.php?org_id=<?= $adminId ?>">Live Count</a></li>
                    <li><a href="#" class="active">Admin Dashboard</a></li>
                    <li><a href="logout.php" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <h1 class="mb-4" style="margin-top: 2rem;">
            Admin Dashboard <span style="font-weight: 300; font-size: 0.8em; color: #6b7280;">/ <?= htmlspecialchars($currentAdmin['organization_name']) ?></span>
        </h1>

        <!-- Stats -->
        <div class="dashboard-grid mb-4">
            <div class="stat-card">
                <h3>Total Kandidat</h3>
                <div class="stat-value"><?= count($candidates) ?></div>
            </div>
            <div class="stat-card" style="background-color: var(--accent-color); color: #ffff;">
                <h3>Total Token</h3>
                <div class="stat-value"><?= $tokensCount ?></div>
            </div>
            <div class="stat-card" style="background-color: #F4C400;">
                <h3>Suara Masuk</h3>
                <div class="stat-value"><?= $votesCount ?></div>
            </div>
        </div>

        <!-- Settings Section -->
        <div class="settings-section">
            <h2 class="mb-2"><i class="fas fa-cogs"></i> Pengaturan Voting</h2>
            <form action="actions.php?action=update_settings" method="POST">
                <div class="input-group">
                    <label>Jumlah Kandidat yang HARUS dipilih:</label>
                    <div style="display: flex; gap: 1rem;">
                        <input type="number" name="min_vote" value="<?= $settings['min_vote'] ?>" min="1" placeholder="Min" style="flex:1;">
                        <input type="number" name="max_vote" value="<?= $settings['max_vote'] ?>" min="1" placeholder="Max" style="flex:1;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Simpan Pengaturan</button>
                </div>
            </form>
        </div>

        <!-- Settings Section -->
                <h2 class="mb-2"><i class="fas fa-users"></i> Tambah Kandidat</h2>
                
                <!-- Input Form (Not a real form submission) -->
                <div id="entry-form">
                    <div class="input-group">
                        <label>Nama Kandidat</label>
                        <input type="text" id="entry-name" placeholder="Nama Lengkap">
                    </div>
                    <div class="input-group">
                        <label>Foto Kandidat (Max 2MB)</label>
                        <div id="drop-zone" style="border: 2px dashed #cbd5e1; padding: 2rem; border-radius: 8px; text-align: center; background: #fff; cursor: pointer; transition: all 0.3s ease;">
                            <div id="drop-zone-content">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: #94a3b8; margin-bottom: 0.5rem;"></i>
                                <p style="color: #64748b; margin-bottom: 0.5rem;">Drag & Drop foto di sini atau klik untuk memilih</p>
                            </div>
                            <span style="font-size: 0.875rem; color: #ef4444; display: none;" id="file-error"></span>
                            <input type="file" id="file-input" accept="image/png, image/jpeg, image/jpg" style="display: none;">
                            
                            <!-- Preview in Drop Zone -->
                            <div id="preview-container" style="display: none; margin-top: 1rem;">
                                <img id="image-preview" src="" style="max-width: 100px; max-height: 100px; border-radius: 4px; border: 1px solid #e2e8f0;">
                                <p id="filename" style="font-size: 0.875rem; color: #475569; margin-top: 0.25rem; word-break: break-all;"></p>
                                <button type="button" id="clear-file" style="background: none; border: none; color: #ef4444; font-size: 0.8rem; cursor: pointer; text-decoration: underline;">Ganti Foto</button>
                            </div>
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Visi & Misi</label>
                        <textarea id="entry-vision" rows="3" placeholder="Visi Misi..."></textarea>
                    </div>
                    <button type="button" class="btn btn-secondary" id="add-queue-btn" style="width: 100%; border: 1px dashed #4b5563; color: #4b5563; background: transparent;"><i class="fas fa-plus-circle"></i> Tambah ke Antrian</button>
                </div>

                <!-- Queue Display -->
                <div id="queue-section" style="margin-top: 2rem; display: none; border-top: 1px solid #e5e7eb; padding-top: 1rem;">
                    <h3 class="mb-2" style="font-size: 1rem; color: #4b5563;">Antrian Simpan (<span id="queue-count">0</span>)</h3>
                    <div id="queue-list" style="display: flex; flex-direction: column; gap: 0.5rem; max-height: 300px; overflow-y: auto;">
                        <!-- Items will be injected here -->
                    </div>
                    
                    <button type="button" class="btn btn-primary" id="save-all-btn" style="width: 100%; margin-top: 1rem;"><i class="fas fa-save"></i> Simpan Semua Kandidat</button>
                </div>

                <script>
                    const dropZone = document.getElementById('drop-zone');
                    const fileInput = document.getElementById('file-input');
                    const previewContainer = document.getElementById('preview-container');
                    const imagePreview = document.getElementById('image-preview');
                    const filenameDisplay = document.getElementById('filename');
                    const fileError = document.getElementById('file-error');
                    const clearFileBtn = document.getElementById('clear-file');
                    const dropZoneContent = document.getElementById('drop-zone-content');

                    let currentFile = null;
                    let queue = [];

                    // --- Drag & Drop Handling ---
                    dropZone.addEventListener('click', (e) => {
                        if(e.target !== clearFileBtn) fileInput.click();
                    });

                    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                        dropZone.addEventListener(eventName, preventDefaults, false);
                    });

                    function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }

                    ['dragenter', 'dragover'].forEach(eventName => dropZone.classList.add('highlight'));
                    ['dragleave', 'drop'].forEach(eventName => dropZone.classList.remove('highlight'));

                    dropZone.addEventListener('drop', (e) => handleFiles(e.dataTransfer.files));
                    fileInput.addEventListener('change', (e) => handleFiles(e.target.files));

                    function handleFiles(files) {
                        if (files.length > 0) {
                            validateAndPreview(files[0]);
                        }
                    }

                    function validateAndPreview(file) {
                        if (file.size > 2 * 1024 * 1024) {
                            showError('Ukuran file maks 2MB.'); return;
                        }
                        if (!file.type.startsWith('image/')) {
                            showError('Hanya file gambar.'); return;
                        }
                        
                        hideError();
                        currentFile = file;
                        
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            imagePreview.src = e.target.result;
                            filenameDisplay.textContent = file.name;
                            previewContainer.style.display = 'block';
                            dropZoneContent.style.display = 'none';
                        };
                        reader.readAsDataURL(file);
                    }

                    clearFileBtn.addEventListener('click', resetFile);

                    function resetFile() {
                        currentFile = null;
                        fileInput.value = '';
                        previewContainer.style.display = 'none';
                        dropZoneContent.style.display = 'block';
                        hideError();
                    }

                    function showError(msg) {
                        fileError.textContent = msg;
                        fileError.style.display = 'block';
                        dropZone.style.borderColor = '#ef4444';
                    }
                    function hideError() {
                        fileError.style.display = 'none';
                        dropZone.style.borderColor = '#cbd5e1';
                    }


                    // --- Queue Functionality ---
                    const addQueueBtn = document.getElementById('add-queue-btn');
                    const queueSection = document.getElementById('queue-section');
                    const queueList = document.getElementById('queue-list');
                    const queueCount = document.getElementById('queue-count');
                    const saveAllBtn = document.getElementById('save-all-btn');
                    const nameInput = document.getElementById('entry-name');
                    const visionInput = document.getElementById('entry-vision');

                    addQueueBtn.addEventListener('click', () => {
                        const name = nameInput.value.trim();
                        const vision = visionInput.value.trim();

                        if (!name) { alert('Nama wajib diisi!'); return; }
                        
                        // Add to queue array
                        queue.push({
                            id: Date.now(),
                            name: name,
                            vision: vision,
                            file: currentFile // can be null
                        });

                        renderQueue();
                        resetForm();
                    });

                    function resetForm() {
                        nameInput.value = '';
                        visionInput.value = '';
                        resetFile();
                    }

                    function renderQueue() {
                        queueList.innerHTML = '';
                        queueCount.textContent = queue.length;
                        
                        if (queue.length > 0) {
                            queueSection.style.display = 'block';
                        } else {
                            queueSection.style.display = 'none';
                        }

                        queue.forEach((item, index) => {
                            const div = document.createElement('div');
                            div.className = 'queue-item';
                            div.style.cssText = 'background: #f9fafb; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; display: flex; align-items: center; justify-content: space-between;';
                            
                            const info = document.createElement('div');
                            info.innerHTML = `<strong>${item.name}</strong><br><span style="font-size:0.8rem; color:#6b7280;">${item.file ? item.file.name : 'No Photo'}</span>`;
                            
                            const delBtn = document.createElement('button');
                            delBtn.innerHTML = '<i class="fas fa-times"></i>';
                            delBtn.style.cssText = 'color: #ef4444; background: none; border: none; cursor: pointer;';
                            delBtn.onclick = () => removeFromQueue(index);

                            div.appendChild(info);
                            div.appendChild(delBtn);
                            queueList.appendChild(div);
                        });
                    }

                    function removeFromQueue(index) {
                        queue.splice(index, 1);
                        renderQueue();
                    }

                    // --- Batch Save ---
                    saveAllBtn.addEventListener('click', async () => {
                        if (queue.length === 0) return;
                        
                        saveAllBtn.disabled = true;
                        saveAllBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

                        const formData = new FormData();
                        
                        queue.forEach((item, index) => {
                            formData.append(`candidates[${index}][name]`, item.name);
                            formData.append(`candidates[${index}][vision]`, item.vision);
                            if (item.file) {
                                formData.append(`photos_${index}`, item.file); // Append file with unique key
                            }
                        });

                        try {
                            const response = await fetch('actions.php?action=add_candidates_batch', {
                                method: 'POST',
                                body: formData
                            });

                            if (response.ok) {
                                window.location.reload();
                            } else {
                                const errorText = await response.text();
                                alert('Terjadi kesalahan saat menyimpan: ' + errorText);
                                saveAllBtn.disabled = false;
                                saveAllBtn.innerHTML = '<i class="fas fa-save"></i> Simpan Semua Kandidat';
                            }
                        } catch (error) {
                            console.error(error);
                            alert('Gagal menghubungi server: ' + error.message);
                            saveAllBtn.disabled = false;
                            saveAllBtn.innerHTML = '<i class="fas fa-save"></i> Simpan Semua Kandidat';
                        }
                    });
                </script>
            </div>

            <!-- Token Management -->
            <div class="settings-section">
                <h2 class="mb-2"><i class="fas fa-key"></i> Generator Token</h2>
                <form action="actions.php?action=generate_tokens" method="POST">
                    <div class="input-group">
                        <label>Jumlah Token</label>
                        <input type="number" name="count" value="10" min="1">
                    </div>
                    <button type="submit" class="btn btn-accent"><i class="fas fa-magic"></i> Generate Token</button>
                    <a href="print_tokens.php" class="btn" style="background-color: #4b5563; color: white; display: block; text-align: center; margin-top: 0.5rem; text-decoration: none;"><i class="fas fa-print"></i> Cetak Semua Token</a>
                </form>

                <div style="margin-top: 1.5rem;">
                    <h3>Token Terbaru</h3>
                    <div style="max-height: 150px; overflow-y: auto; padding: 0.5rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px;">
                        <?php foreach ($tokensList as $t): ?>
                            <div style="font-family: monospace; display: flex; justify-content: space-between;">
                                <span><?= $t['code'] ?></span>
                                <span style="color: <?= $t['is_used'] ? 'red' : 'green' ?>"><?= $t['is_used'] ? 'Terpakai' : 'Aktif' ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Candidate List -->
        <h2 class="mb-2">Daftar Kandidat</h2>
        <div class="dashboard-grid mb-4">
            <?php foreach ($candidates as $c): ?>
                <div class="candidate-card" id="card-<?= $c['id'] ?>">
                    <img src="<?= (strpos($c['photo'], 'http') === 0) ? htmlspecialchars($c['photo']) : '../' . htmlspecialchars($c['photo']) ?>" class="candidate-img">
                    <div class="candidate-info">
                        <div class="candidate-name"><?= htmlspecialchars($c['name']) ?></div>
                        <div class="candidate-vision"><?= htmlspecialchars($c['vision']) ?></div>
                        <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                            <button class="btn" style="background-color: #f59e0b; color: white; border: none; flex: 1;" 
                                onclick='openEditModal(<?= json_encode($c) ?>)'>
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="actions.php?action=delete_candidate&id=<?= $c['id'] ?>" class="btn btn-danger" style="flex: 1; text-align: center;" onclick="return confirm('Hapus kandidat ini?')">Hapus</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Danger Zone -->
        <div class="settings-section" style="border: 2px solid #ef4444; background: #fef2f2;">
            <h2 class="mb-2" style="color: #ef4444;">Reset System</h2>
            <form action="actions.php" method="POST" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <button type="submit" formaction="actions.php?action=reset_candidates" class="btn btn-danger" onclick="return confirm('Hapus SEMUA kandidat?')">Hapus Semua Kandidat</button>
                <button type="submit" formaction="actions.php?action=reset_tokens" class="btn btn-danger" onclick="return confirm('Hapus SEMUA token?')">Hapus Semua Token</button>
                <button type="submit" formaction="actions.php?action=reset_votes" class="btn btn-danger" onclick="return confirm('Hapus SEMUA suara?')">Hapus Semua Suara</button>
            </form>
        </div>
    </main>

    <!-- Edit Modal -->
    <div id="edit-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 100; align-items: center; justify-content: center;">
        <div style="background: white; padding: 2rem; border-radius: 8px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto;">
            <h2 class="mb-4">Edit Kandidat</h2>
            <form action="actions.php?action=edit_candidate" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit-id">
                
                <div class="input-group">
                    <label>Nama Kandidat</label>
                    <input type="text" name="name" id="edit-name" required>
                </div>

                <div class="input-group">
                    <label>Foto (Biarkan kosong jika tidak ingin mengubah)</label>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <img id="edit-preview" src="" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                        <input type="file" name="photo" accept="image/*">
                    </div>
                </div>

                <div class="input-group">
                    <label>Visi & Misi</label>
                    <textarea name="vision" id="edit-vision" rows="4"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1rem;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('edit-modal').style.display='none'">Batal</button>
                    <button type="submit" class="btn btn-primary">Save & Exit</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(candidate) {
            document.getElementById('edit-id').value = candidate.id;
            document.getElementById('edit-name').value = candidate.name;
            document.getElementById('edit-vision').value = candidate.vision;
            
            // Handle photo path
            let photoSrc = candidate.photo;
            if (!photoSrc.startsWith('http')) {
                photoSrc = '../' + photoSrc;
            }
            document.getElementById('edit-preview').src = photoSrc;

            document.getElementById('edit-modal').style.display = 'flex';
        }

        // Close modal when clicking outside
        document.getElementById('edit-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });

        // Handle Edit Form Submit via AJAX
        document.querySelector('#edit-modal form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = 'Saving...';

            const formData = new FormData(this);

            try {
                const response = await fetch('actions.php?action=edit_candidate', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.status === 'success') {
                    const data = result.data;
                    const card = document.getElementById('card-' + data.id);
                    
                    if (card) {
                        // Update UI
                        card.querySelector('.candidate-name').textContent = data.name;
                        card.querySelector('.candidate-vision').textContent = data.vision;
                        
                        if (data.photo) {
                            let newSrc = data.photo.startsWith('http') ? data.photo : '../' + data.photo;
                            card.querySelector('.candidate-img').src = newSrc;
                        }

                        // Update the onclick data for next edit
                        // We need to re-construct the candidate object merging old + new
                        // Simplest way is fetching the current image src if not updated, but
                        // the 'openEditModal' expects a raw DB object structure (photo path without ../)
                        // So we should update the button's onclick attribute.
                        
                        // NOTE: updating complex onclick attributes in DOM is tricky.
                        // A better approach is to not pass full object in onclick, but just ID, and fetch data or read from DOM.
                        // BUT, to keep it consistent with current "openEditModal(json)" pattern:
                        
                        const editBtn = card.querySelector('button.btn');
                        // Get current full object from previous attribute
                        // This might be hard to parse back from the attribute string safely.
                        // Alternative: construct new object
                        
                        // We will rely on reloading page IF structure is too complex, BUT user asked NO RELOAD.
                        // So, let's try to update the click handler.
                        
                        // Construct updated candidate object
                        const updatedCandidate = {
                            id: data.id,
                            name: data.name,
                            vision: data.vision,
                            photo: data.photo ? data.photo : (card.querySelector('.candidate-img').src.replace('../', '')) // Approximation
                        };
                         // If we didn't get new photo, we use the old path (we need to know it).
                         // Actually, the simplest way is to RELOAD the candidate data from specific endpoint or just accept that
                         // if we re-open edit without reload, we might have issues if we don't update the OnClick.
                         
                         // Let's grab the OLD onclick string, parse it? No.
                         // Let's just update the DOM elements. If user clicks Edit again, 
                         // we need ensuring the Modal form inputs get the NEW values.
                         // We are populating inputs from 'candidate' arg in openEditModal.
                         // If we don't update the onclick, clicking edit again will show OLD data (Name/Vision).
                         
                         editBtn.setAttribute('onclick', `openEditModal(${JSON.stringify(updatedCandidate).replace(/"/g, "&quot;")})`);
                    }

                    document.getElementById('edit-modal').style.display = 'none';
                    // Optional: Show toast success
                } else {
                    alert('Gagal menyimpan perubahan.');
                }
            } catch (err) {
                console.error(err);
                alert('Terjadi kesalahan koneksi.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    </script>
    <?php $basePath = '../'; include '../footer.php'; ?>
</body>

</html>