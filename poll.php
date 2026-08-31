<?php
require_once 'config.php';

$pollSlug = $_GET['slug'] ?? '';

// Fetch Poll
$stmt = $pdo->prepare("SELECT * FROM polls WHERE slug = ?");
$stmt->execute([$pollSlug]);
$poll = $stmt->fetch();

if (!$poll) {
    die("Polling tidak ditemukan.");
}

// Fetch Admin Settings for Theme
$stmt = $pdo->prepare("SELECT * FROM settings WHERE admin_id = ?");
$stmt->execute([$poll['admin_id']]);
$settings = $stmt->fetch();

if (!$settings) {
    // Default settings
    $settings = [
        'theme_color' => '#00984B',
        'logo_path' => 'media/Logo_PD_IPM.png'
    ];
}

$colors = explode(',', $settings['theme_color']);
$primary = $colors[0] ?? '#00984B';
$accent = $colors[1] ?? $primary;
$dark = $colors[2] ?? $primary;

// Fetch Options
$stmt = $pdo->prepare("SELECT * FROM poll_options WHERE poll_id = ?");
$stmt->execute([$poll['id']]);
$options = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($poll['title']) ?> - Polling</title>
    <link rel="icon" type="image/png" href="media/Logo%20Orch-Vote.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: <?= htmlspecialchars($primary) ?>;
            --accent-color: <?= htmlspecialchars($accent) ?>;
            --primary-dark: <?= htmlspecialchars($dark) ?>;
        }
        
        .poll-container {
            max-width: 600px;
            margin: 2rem auto;
            background: #fff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border-top: 5px solid var(--primary-color);
        }

        .poll-option {
            display: block;
            position: relative;
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            transition: all 0.2s ease;
            font-weight: 500;
            color: #4b5563;
        }

        .poll-option:hover {
            border-color: var(--accent-color);
            background-color: rgba(var(--accent-color-rgb, 0,0,0), 0.02); /* Very subtle background */
        }

        .poll-option input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        /* Custom Radio Button Styling */
        .poll-option .checkmark {
            position: absolute;
            top: 50%;
            right: 1.5rem;
            transform: translateY(-50%);
            height: 20px;
            width: 20px;
            background-color: #eee;
            border-radius: 50%;
            border: 2px solid #cbd5e1;
            transition: all 0.2s ease;
        }

        .poll-option:hover input ~ .checkmark {
            background-color: #ccc;
        }

        .poll-option input:checked ~ .checkmark {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: white;
        }

        .poll-option input:checked ~ .checkmark:after {
            display: block;
        }
        
        .poll-option input:checked + .option-text {
            color: var(--primary-dark);
            font-weight: 600;
        }

        /* Update border when checked */
        .poll-option.selected {
            border-color: var(--primary-color);
            background-color: rgba(0, 152, 75, 0.05); /* very light primary */
        }
    </style>
</head>
<body id="login-page"> <!-- Reuse login-page background styles -->
    
    <div style="text-align: center; margin-top: 3rem; margin-bottom: 1rem;">
        <?php if ($settings['logo_path']): ?>
            <img src="<?= htmlspecialchars($settings['logo_path']) ?>" alt="Logo" style="height: 100px; width: auto; object-fit: contain;">
        <?php else: ?>
            <i class="fas fa-vote-yea" style="font-size: 4rem; color: var(--primary-color);"></i>
        <?php endif; ?>
    </div>

    <main class="container">
        <div class="poll-container">
            <?php if (isset($_GET['msg'])): ?>
                <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; text-align: center; font-weight: 500;">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['msg']) ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div style="background: #fee2e2; color: #dc2626; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; text-align: center; font-weight: 500;">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <?php if (!$poll['is_active']): ?>
                <div style="text-align: center; padding: 2rem 0;">
                    <i class="fas fa-lock" style="font-size: 3rem; color: #9ca3af; margin-bottom: 1rem;"></i>
                    <h2 style="color: #4b5563;">Polling Ditutup</h2>
                    <p style="color: #6b7280;">Maaf, polling ini sudah tidak menerima tanggapan lagi.</p>
                </div>
            <?php else: ?>
                <h2 style="text-align: center; color: #1f2937; margin-bottom: 0.5rem;"><?= htmlspecialchars($poll['title']) ?></h2>
                <?php if ($poll['description']): ?>
                    <p style="text-align: center; color: #6b7280; margin-bottom: 2rem;"><?= htmlspecialchars($poll['description']) ?></p>
                <?php else: ?>
                    <div style="margin-bottom: 2rem;"></div>
                <?php endif; ?>

                <form method="POST" action="process_poll.php">
                    <input type="hidden" name="poll_id" value="<?= $poll['id'] ?>">
                    <input type="hidden" name="slug" value="<?= htmlspecialchars($pollSlug) ?>">
                    
                    <div class="options-list">
                        <?php foreach ($options as $opt): ?>
                            <label class="poll-option" onclick="selectOption(this)">
                                <input type="radio" name="option_id" value="<?= $opt['id'] ?>" required>
                                <span class="option-text"><?= htmlspecialchars($opt['option_text']) ?></span>
                                <span class="checkmark"></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem; margin-top: 1.5rem; border-radius: 8px;">
                        Kirim Pilihan
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function selectOption(labelElement) {
            // Remove 'selected' class from all options
            const allOptions = document.querySelectorAll('.poll-option');
            allOptions.forEach(opt => opt.classList.remove('selected'));
            
            // Add 'selected' class to the clicked one
            labelElement.classList.add('selected');
        }
    </script>
</body>
</html>
