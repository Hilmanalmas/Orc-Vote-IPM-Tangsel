<?php
require_once '../config.php';

// Fetch all tokens
$tokens = $pdo->query("SELECT * FROM tokens ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Token - E-Voting IPM Tangsel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f3f4f6;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .header h1 {
            margin: 0;
            font-size: 1.5rem;
            color: #1f2937;
        }

        .btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-back {
            background: #6b7280;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .token-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }

        .token-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 1rem;
            text-align: center;
            position: relative;
        }

        .token-card.used {
            background: #f3f4f6;
            color: #9ca3af;
            text-decoration: line-through;
        }

        .token-code {
            font-family: monospace;
            font-size: 1.25rem;
            font-weight: bold;
            color: #1f2937;
        }

        .token-card.used .token-code {
            color: #9ca3af;
        }

        .token-status {
            font-size: 0.75rem;
            margin-top: 0.5rem;
            color: #059669;
        }

        .token-card.used .token-status {
            color: #dc2626;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .header {
                box-shadow: none;
                padding: 0;
                margin-bottom: 1rem;
                justify-content: center;
            }

            .header .actions {
                display: none;
            }

            .token-grid {
                grid-template-columns: repeat(5, 1fr);
                gap: 0.5rem;
            }

            .token-card {
                border: 1px solid #000;
                break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <a href="index.php" class="btn btn-back actions"><i class="fas fa-arrow-left"></i> Kembali</a>
        <h1>Daftar Token Voting</h1>
        <button onclick="window.print()" class="btn actions"><i class="fas fa-print"></i> Cetak</button>
    </div>

    <div class="token-grid">
        <?php foreach ($tokens as $t): ?>
            <div class="token-card <?= $t['is_used'] ? 'used' : '' ?>">
                <div class="token-code"><?= $t['code'] ?></div>
                <div class="token-status">
                    <?= $t['is_used'] ? 'Terpakai' : 'Aktif' ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>

</html>
