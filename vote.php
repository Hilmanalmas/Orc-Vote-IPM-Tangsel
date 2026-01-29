<?php
require_once 'config.php';

if (!isset($_SESSION['voter_token']) || !isset($_SESSION['org_id'])) {
    header("Location: index.php");
    exit;
}

$orgId = $_SESSION['org_id'];
$settings = getSettings($pdo, $orgId);

$stmt = $pdo->prepare("SELECT * FROM candidates WHERE admin_id = ? ORDER BY id ASC");
$stmt->execute([$orgId]);
$candidates = $stmt->fetchAll();

// Get Org Name for Display
$stmt = $pdo->prepare("SELECT organization_name FROM admins WHERE id = ?");
$stmt->execute([$orgId]);
$orgName = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="media/Logo%20Orch-Vote.png">
    <title>Voting - Orch-Vote</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body id="vote-page">
    <header>
        <div class="container nav-wrapper">
            <div class="logo">
                <i class="fas fa-vote-yea"></i> Orch-Vote<span><?= htmlspecialchars($orgName) ?></span>
            </div>
            <div style="color: white; font-weight: 500;">
                Halo, Voter
            </div>
        </div>
    </header>

    <main class="container" style="padding-bottom: 5rem;">
        <div class="text-center mb-4" style="margin-top: 2rem;">
            <h1>Pilih Kandidat Anda</h1>
            <p style="color: #6b7280;">Silakan pilih
                <span id="rule-text" style="font-weight: bold; color: #f59e0b;">
                    <?= $settings['min_vote'] == $settings['max_vote'] ? $settings['min_vote'] : $settings['min_vote'] . ' hingga ' . $settings['max_vote'] ?> kandidat
                </span>
            </p>
        </div>

        <form id="vote-form" action="process_vote.php" method="POST">
            <div class="dashboard-grid">
                <?php foreach ($candidates as $c): ?>
                    <div class="candidate-card" id="card-<?= $c['id'] ?>" onclick="toggleSelection(<?= $c['id'] ?>)">
                        <div class="select-overlay"><i class="fas fa-check"></i></div>
                        <img src="<?= htmlspecialchars($c['photo']) ?>" class="candidate-img">
                        <div class="candidate-info">
                            <div class="candidate-name"><?= htmlspecialchars($c['name']) ?></div>
                            <div class="candidate-vision"><?= htmlspecialchars($c['vision']) ?></div>
                        </div>
                        <!-- Hidden Checkbox -->
                        <input type="checkbox" name="votes[]" value="<?= $c['id'] ?>" id="check-<?= $c['id'] ?>" style="display: none;">
                    </div>
                <?php endforeach; ?>
            </div>
        </form>
    </main>

    <!-- Floating Submit Button -->
    <div style="position: fixed; bottom: 0; left: 0; width: 100%; background: white; padding: 1rem; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); display: flex; justify-content: center; z-index: 40;">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            <div style="font-weight: 600;">Terpilih: <span id="selected-count" style="color: #00984B;">0</span></div>
            <button class="btn btn-primary" onclick="submitVote()">Kirim Suara <i class="fas fa-paper-plane" style="margin-left: 0.5rem;"></i></button>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirm-modal" class="modal">
        <div class="modal-content">
            <div style="color: #00984B; font-size: 3rem; margin-bottom: 1rem;">
                <i class="fas fa-question-circle"></i>
            </div>
            <h2>Konfirmasi Pilihan</h2>
            <p class="mb-4">Apakah Anda yakin dengan pilihan Anda? Data yang sudah dikirim tidak dapat diubah.</p>
            <div class="modal-actions">
                <button class="btn btn-danger" onclick="document.getElementById('confirm-modal').classList.remove('active')">Kembali</button>
                <button class="btn btn-primary" onclick="document.getElementById('vote-form').submit()">Ya, Saya Yakin</button>
            </div>
        </div>
    </div>

    <script>
        const minVote = <?= $settings['min_vote'] ?>;
        const maxVote = <?= $settings['max_vote'] ?>;
        let selectedCount = 0;

        function toggleSelection(id) {
            const checkbox = document.getElementById('check-' + id);
            const card = document.getElementById('card-' + id);

            if (!checkbox.checked) {
                if (selectedCount >= maxVote) {
                    alert(`Maksimal memilih ${maxVote} kandidat!`);
                    return;
                }
                checkbox.checked = true;
                card.classList.add('selected');
                selectedCount++;
            } else {
                checkbox.checked = false;
                card.classList.remove('selected');
                selectedCount--;
            }
            document.getElementById('selected-count').innerText = selectedCount;
        }

        function submitVote() {
            if (selectedCount < minVote) {
                alert(`Anda harus memilih minimal ${minVote} kandidat!`);
                return;
            }
            document.getElementById('confirm-modal').classList.add('active');
        }
    </script>
    <?php $basePath = ''; include 'footer.php'; ?>
</body>

</html>