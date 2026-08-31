<?php
require_once '../config.php';

// Auth Check
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
$adminId = $_SESSION['admin_id'];

$pollId = $_GET['id'] ?? 0;

// Fetch Poll
$stmt = $pdo->prepare("SELECT * FROM polls WHERE id = ? AND admin_id = ?");
$stmt->execute([$pollId, $adminId]);
$poll = $stmt->fetch();

if (!$poll) {
    die("Polling tidak ditemukan atau Anda tidak memiliki akses.");
}

// Fetch Options and Votes
$stmt = $pdo->prepare("
    SELECT o.id, o.option_text, COUNT(v.id) as vote_count 
    FROM poll_options o 
    LEFT JOIN poll_votes v ON o.id = v.option_id 
    WHERE o.poll_id = ? 
    GROUP BY o.id
");
$stmt->execute([$pollId]);
$results = $stmt->fetchAll();

$totalVotes = 0;
foreach ($results as $res) {
    $totalVotes += $res['vote_count'];
}

// Get settings for logo/theme
$settings = getSettings($pdo, $adminId);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../media/Logo%20Orch-Vote.png">
    <title>Hasil Polling - <?= htmlspecialchars($poll['title']) ?></title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body id="admin-page">
    <header>
        <div class="container nav-wrapper">
            <div class="logo">
                <i class="fas fa-chart-pie"></i>Orch-Vote<span>Hasil Polling</span>
            </div>
            <nav>
                <ul>
                    <li><a href="manage_polls.php"><i class="fas fa-arrow-left"></i> Kembali ke Manage Polls</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <div style="margin-top: 2rem; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <h1 class="mb-2"><?= htmlspecialchars($poll['title']) ?></h1>
            <?php if ($poll['description']): ?>
                <p style="color: #6b7280; margin-bottom: 2rem;"><?= htmlspecialchars($poll['description']) ?></p>
            <?php endif; ?>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                <div>
                    <span style="font-size: 1.5rem; font-weight: bold; color: #10b981;"><?= $totalVotes ?></span>
                    <span style="color: #6b7280;">Total Suara</span>
                </div>
                <div>
                    Status: <span style="font-weight: bold; color: <?= $poll['is_active'] ? '#10b981' : '#ef4444' ?>;"><?= $poll['is_active'] ? 'Aktif' : 'Ditutup' ?></span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Table Results -->
                <div>
                    <h3 class="mb-2">Rincian Suara</h3>
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 2px solid #e5e7eb;">
                                <th style="padding: 0.75rem 0;">Pilihan</th>
                                <th style="padding: 0.75rem 0;">Jumlah</th>
                                <th style="padding: 0.75rem 0;">Persentase</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $res): ?>
                                <?php $percentage = $totalVotes > 0 ? round(($res['vote_count'] / $totalVotes) * 100, 1) : 0; ?>
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 0.75rem 0;"><?= htmlspecialchars($res['option_text']) ?></td>
                                    <td style="padding: 0.75rem 0;"><strong><?= $res['vote_count'] ?></strong></td>
                                    <td style="padding: 0.75rem 0; color: #6b7280;"><?= $percentage ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Chart Results -->
                <div>
                    <canvas id="pollChart"></canvas>
                </div>
            </div>
        </div>
    </main>

    <script>
        const ctx = document.getElementById('pollChart').getContext('2d');
        const data = {
            labels: [
                <?php foreach ($results as $res) echo "'" . addslashes($res['option_text']) . "', "; ?>
            ],
            datasets: [{
                data: [
                    <?php foreach ($results as $res) echo $res['vote_count'] . ", "; ?>
                ],
                backgroundColor: [
                    '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#14b8a6'
                ],
                borderWidth: 1
            }]
        };

        new Chart(ctx, {
            type: 'pie',
            data: data,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    </script>
</body>
</html>
