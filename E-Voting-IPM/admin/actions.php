<?php
require_once '../config.php';

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Batch Add Candidates
    if ($action === 'add_candidates_batch') {
        $candidates = $_POST['candidates'] ?? [];
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $stmt = $pdo->prepare("INSERT INTO candidates (name, photo, vision) VALUES (?, ?, ?)");

        foreach ($candidates as $index => $data) {
            $name = $data['name'];
            $vision = $data['vision'];
            $photoPath = 'https://via.placeholder.com/300x300?text=No+Image';

            // Check if file exists for this index
            if (isset($_FILES["photos_$index"]) && $_FILES["photos_$index"]["error"] === UPLOAD_ERR_OK) {
                $file = $_FILES["photos_$index"];
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'candidate_' . time() . '_' . rand(1000, 9999) . '_' . $index . '.' . $ext;
                
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    $photoPath = 'uploads/' . $filename;
                }
            }

            $stmt->execute([$name, $photoPath, $vision]);
        }
        
        // Return JSON for AJAX
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
        exit;
    }

    // Generate Tokens
    if ($action === 'generate_tokens') {
        $count = (int)$_POST['count'];
        $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        for ($i = 0; $i < $count; $i++) {
            $token = '';
            for ($j = 0; $j < 6; $j++) {
                $token .= $charset[rand(0, strlen($charset) - 1)];
            }
            // Insert ignore ensures duplicates are skipped/handled (or handle error)
            $stmt = $pdo->prepare("INSERT IGNORE INTO tokens (code) VALUES (?)");
            $stmt->execute([$token]);
        }
        header("Location: index.php");
        exit;
    }

    // Edit Candidate
    if ($action === 'edit_candidate') {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $vision = $_POST['vision'];

        // Initial SQL
        $sql = "UPDATE candidates SET name = ?, vision = ? WHERE id = ?";
        $params = [$name, $vision, $id];

        // Check if new photo
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['photo'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];

            if ($file['size'] <= $maxSize && in_array($file['type'], $allowedTypes)) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'candidate_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $uploadDir = '../uploads/';
                
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    $photoPath = 'uploads/' . $filename;
                    
                    // Update SQL to include photo
                    $sql = "UPDATE candidates SET name = ?, vision = ?, photo = ? WHERE id = ?";
                    $params = [$name, $vision, $photoPath, $id];
                }
            }
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Return JSON
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'data' => [
                'id' => $id,
                'name' => $name,
                'vision' => $vision,
                'photo' => isset($photoPath) ? $photoPath : null
            ]
        ]);
        exit;
    }

    // Update Settings
    if ($action === 'update_settings') {
        $min = (int)$_POST['min_vote'];
        $max = (int)$_POST['max_vote'];

        $stmt = $pdo->prepare("UPDATE settings SET min_vote = ?, max_vote = ? WHERE id = 1");
        $stmt->execute([$min, $max]);
        header("Location: index.php");
        exit;
    }

    // Resets
    if ($action === 'reset_candidates') {
        $pdo->query("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->query("TRUNCATE TABLE candidates");
        $pdo->query("TRUNCATE TABLE votes");
        $pdo->query("SET FOREIGN_KEY_CHECKS = 1");
        header("Location: index.php");
        exit;
    }

    if ($action === 'reset_tokens') {
        $pdo->query("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->query("TRUNCATE TABLE tokens");
        $pdo->query("TRUNCATE TABLE votes"); // Votes depend on tokens usually
        $pdo->query("SET FOREIGN_KEY_CHECKS = 1");
        header("Location: index.php");
        exit;
    }

    if ($action === 'reset_votes') {
        $pdo->query("TRUNCATE TABLE votes");
        $pdo->query("UPDATE tokens SET is_used = 0");
        header("Location: index.php");
        exit;
    }
}

// Get Request (Delete)
if ($action === 'delete_candidate') {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM candidates WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: index.php");
    exit;
}
