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

// Fetch Polling Questions and Options
$stmt = $pdo->prepare("SELECT id, question_text FROM poll_questions WHERE poll_id = ? AND question_type = 'polling' ORDER BY order_num ASC");
$stmt->execute([$pollId]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$resultsData = [];
$totalSubmissions = 0;

// Get total submissions by counting unique IP + Date combination for this poll
$stmtTotal = $pdo->prepare("SELECT COUNT(DISTINCT CONCAT(ip_address, DATE_FORMAT(submitted_at, '%Y-%m-%d %H:%i'))) FROM poll_answers WHERE poll_id = ?");
$stmtTotal->execute([$pollId]);
$totalSubmissions = $stmtTotal->fetchColumn();

foreach ($questions as $q) {
    $stmtOpt = $pdo->prepare("
        SELECT o.id, o.option_text, COUNT(a.id) as vote_count 
        FROM poll_options o 
        LEFT JOIN poll_answers a ON o.id = a.option_id 
        WHERE o.question_id = ? 
        GROUP BY o.id
    ");
    $stmtOpt->execute([$q['id']]);
    $resultsData[$q['id']] = [
        'text' => $q['question_text'],
        'options' => $stmtOpt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

// Fetch ALL Questions for the raw data table
$stmtAllQ = $pdo->prepare("SELECT id, question_text FROM poll_questions WHERE poll_id = ? ORDER BY order_num ASC, id ASC");
$stmtAllQ->execute([$pollId]);
$allQuestions = $stmtAllQ->fetchAll(PDO::FETCH_ASSOC);

// Fetch All Answers Grouped by IP & Date for the table
$stmtA = $pdo->prepare("
    SELECT a.question_id, a.text_answer, a.ip_address, a.submitted_at, o.option_text
    FROM poll_answers a
    LEFT JOIN poll_options o ON a.option_id = o.id
    WHERE a.poll_id = ?
    ORDER BY a.submitted_at DESC
");
$stmtA->execute([$pollId]);
$rawAnswers = $stmtA->fetchAll(PDO::FETCH_ASSOC);

$submissions = [];
foreach ($rawAnswers as $ans) {
    // Group by minute to approximate a session
    $key = $ans['ip_address'] . '_' . date('Y-m-d H:i', strtotime($ans['submitted_at']));
    if (!isset($submissions[$key])) {
        $submissions[$key] = [
            'ip' => $ans['ip_address'],
            'waktu' => $ans['submitted_at'],
            'answers' => []
        ];
    }
    $answerText = $ans['option_text'] ? $ans['option_text'] : $ans['text_answer'];
    $submissions[$key]['answers'][$ans['question_id']] = $answerText;
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
                <i class="fas fa-chart-pie"></i>Orch-Vote<span>Hasil Polling</span>
            </div>
            <nav>
                <ul>
                    <li><a href="manage_polls"><i class="fas fa-arrow-left"></i> Kembali ke Manage Polls</a></li>
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
                    <span style="font-size: 1.5rem; font-weight: bold; color: #10b981;"><?= $totalSubmissions ?></span>
                    <span style="color: #6b7280;">Total Responden</span>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <a href="actions.php?action=export_csv&id=<?= $pollId ?>" class="btn btn-primary"><i class="fas fa-file-csv"></i> Download CSV</a>
                    <div>Status: <span style="font-weight: bold; color: <?= $poll['is_active'] ? '#10b981' : '#ef4444' ?>;"><?= $poll['is_active'] ? 'Aktif' : 'Ditutup' ?></span></div>
                </div>
            </div>

            <?php if (empty($resultsData)): ?>
                <div style="text-align: center; color: #6b7280; padding: 2rem;">
                    Tidak ada pertanyaan bertipe pilihan ganda (polling) untuk ditampilkan grafiknya. Silakan download CSV untuk melihat jawaban teks.
                </div>
            <?php else: ?>
                <?php foreach ($resultsData as $qId => $data): ?>
                    <h3 class="mb-4 mt-4" style="border-bottom: 2px solid var(--primary-color); display: inline-block; padding-bottom: 0.5rem;"><?= htmlspecialchars($data['text']) ?></h3>
                    
                    <?php 
                        $qTotalVotes = 0;
                        foreach ($data['options'] as $res) $qTotalVotes += $res['vote_count'];
                    ?>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 4rem;">
                        <!-- Table Results -->
                        <div>
                            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                                <thead>
                                    <tr style="border-bottom: 2px solid #e5e7eb;">
                                        <th style="padding: 0.75rem 0;">Pilihan</th>
                                        <th style="padding: 0.75rem 0;">Jumlah</th>
                                        <th style="padding: 0.75rem 0;">Persentase</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['options'] as $res): ?>
                                        <?php $percentage = $qTotalVotes > 0 ? round(($res['vote_count'] / $qTotalVotes) * 100, 1) : 0; ?>
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
                            <canvas id="pollChart_<?= $qId ?>"></canvas>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <h3 class="mb-4 mt-5" style="border-bottom: 2px solid var(--primary-color); display: inline-block; padding-bottom: 0.5rem;">Tabel Seluruh Tanggapan</h3>
            <div style="background: white; border-radius: 8px; border: 1px solid #e5e7eb; overflow-x: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <table style="width: 100%; border-collapse: collapse; text-align: left; min-width: 800px;">
                    <thead style="background-color: #f9fafb;">
                        <tr>
                            <th style="padding: 1rem; border-bottom: 2px solid #e5e7eb; border-right: 1px solid #e5e7eb; white-space: nowrap;">Waktu Submit</th>
                            <th style="padding: 1rem; border-bottom: 2px solid #e5e7eb; border-right: 1px solid #e5e7eb; white-space: nowrap;">IP Address</th>
                            <?php foreach ($allQuestions as $q): ?>
                                <th style="padding: 1rem; border-bottom: 2px solid #e5e7eb; border-right: 1px solid #e5e7eb; min-width: 150px;"><?= htmlspecialchars($q['question_text']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($submissions)): ?>
                            <tr>
                                <td colspan="<?= count($allQuestions) + 2 ?>" style="text-align: center; padding: 2rem; color: #6b7280;">Belum ada tanggapan masuk.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($submissions as $sub): ?>
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 1rem; border-right: 1px solid #e5e7eb; white-space: nowrap; color: #4b5563; font-size: 0.9rem;"><?= htmlspecialchars($sub['waktu']) ?></td>
                                    <td style="padding: 1rem; border-right: 1px solid #e5e7eb; font-family: monospace; color: #6b7280; font-size: 0.9rem;"><?= htmlspecialchars($sub['ip']) ?></td>
                                    <?php foreach ($allQuestions as $q): ?>
                                        <td style="padding: 1rem; border-right: 1px solid #e5e7eb;">
                                            <?php 
                                                $ansText = isset($sub['answers'][$q['id']]) ? $sub['answers'][$q['id']] : '-';
                                                echo nl2br(htmlspecialchars($ansText));
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
        </div>
    </main>

    <script>
        <?php foreach ($resultsData as $qId => $data): ?>
        (function() {
            const ctx = document.getElementById('pollChart_<?= $qId ?>').getContext('2d');
            const data = {
                labels: [
                    <?php foreach ($data['options'] as $res) echo "'" . addslashes($res['option_text']) . "', "; ?>
                ],
                datasets: [{
                    data: [
                        <?php foreach ($data['options'] as $res) echo $res['vote_count'] . ", "; ?>
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
        })();
        <?php endforeach; ?>
    </script>
</body>
</html>
