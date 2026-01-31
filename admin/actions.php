<?php
require_once '../config.php';

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Login Action (No Auth Required)
    if ($action === 'login') {
        // ... (existing login logic) ...
        $username = $_POST['username'];
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            header("Location: index.php");
            exit;
        } else {
            header("Location: login.php?error=Username atau Password salah");
            exit;
        }
    }

    // Add New Admin (Public Access)
    if ($action === 'add_admin') {
        $username = trim($_POST['username']);
        $orgName = trim($_POST['organization_name'] ?? 'Organisasi Baru');
        $password = $_POST['password'];
        $redirect = $_GET['redirect'] ?? 'index.php';

        // Check if username exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            header("Location: $redirect?error=Username sudah ada");
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash, organization_name) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hash, $orgName]);
        
        // Also create default settings for this new admin
        $newAdminId = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO settings (admin_id, min_vote, max_vote, voting_enabled) VALUES (?, 1, 1, 1)")->execute([$newAdminId]);

        header("Location: $redirect?msg=Admin berhasil ditambahkan");
        exit;
    }

    // --- SECURITY CHECK ---
    // All actions below require login
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(403);
        die("Unauthorized");
    }

    // add_admin moved up to allow public access

    // Batch Add Candidates
    if ($action === 'add_candidates_batch') {
        try {
            $adminId = $_SESSION['admin_id'];

            // Check if POST is empty but Content-Length > 0 (Likely post_max_size exceeded)
            if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
                throw new Exception("Ukuran file terlalu besar (Melebihi batas server). Kurangi ukuran gambar.");
            }

            $candidates = $_POST['candidates'] ?? [];
            if (empty($candidates)) {
                throw new Exception("Data kandidat kosong.");
            }

            $uploadDir = '../uploads/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    throw new Exception("Gagal membuat folder uploads.");
                }
            }

            $stmt = $pdo->prepare("INSERT INTO candidates (admin_id, name, photo, vision) VALUES (?, ?, ?, ?)");

            foreach ($candidates as $index => $data) {
                $name = $data['name'];
                $vision = $data['vision'];
                $photoPath = 'media/Logo Orch-Vote.png'; // Default if needed, or placeholder

                // Check and Process File
                if (isset($_FILES["photos_$index"])) {
                    $file = $_FILES["photos_$index"];
                    if ($file['error'] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                        if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif'])) {
                            throw new Exception("Format file tidak valid untuk kandidat: " . htmlspecialchars($name));
                        }
                        
                        $filename = 'candidate_' . $adminId . '_' . time() . '_' . rand(1000, 9999) . '_' . $index . '.' . $ext;
                        
                        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                            $photoPath = 'uploads/' . $filename;
                        } else {
                            throw new Exception("Gagal menyimpan file untuk kandidat: " . htmlspecialchars($name));
                        }
                    } else if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                         // File upload error
                         throw new Exception("Upload Error Code " . $file['error'] . " untuk kandidat: " . htmlspecialchars($name));
                    }
                }

                $stmt->execute([$adminId, $name, $photoPath, $vision]);
            }
            
            // Return JSON for AJAX
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success']);
            exit;

        } catch (Exception $e) {
            http_response_code(500); 
            echo $e->getMessage();
            exit;
        }
    }

    // Generate Tokens
    if ($action === 'generate_tokens') {
        $count = (int)$_POST['count'];
        $adminId = $_SESSION['admin_id'];
        $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        $stmt = $pdo->prepare("INSERT IGNORE INTO tokens (admin_id, code) VALUES (?, ?)");

        for ($i = 0; $i < $count; $i++) {
            $token = '';
            for ($j = 0; $j < 6; $j++) {
                $token .= $charset[rand(0, strlen($charset) - 1)];
            }
            $stmt->execute([$adminId, $token]);
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
        $adminId = $_SESSION['admin_id'];

        // Check if settings exist
        $check = $pdo->prepare("SELECT id FROM settings WHERE admin_id = ?");
        $check->execute([$adminId]);
        
        if ($check->rowCount() > 0) {
             $stmt = $pdo->prepare("UPDATE settings SET min_vote = ?, max_vote = ? WHERE admin_id = ?");
             $stmt->execute([$min, $max, $adminId]);
        } else {
             $stmt = $pdo->prepare("INSERT INTO settings (admin_id, min_vote, max_vote) VALUES (?, ?, ?)");
             $stmt->execute([$adminId, $min, $max]);
        }

        header("Location: index.php");
        exit;
    }

    // Resets (Scoped to Admin)
    if ($action === 'reset_candidates') {
        $adminId = $_SESSION['admin_id'];
        $stmt = $pdo->prepare("DELETE FROM candidates WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        header("Location: index.php");
        exit;
    }

    if ($action === 'reset_tokens') {
        $adminId = $_SESSION['admin_id'];
        $stmt = $pdo->prepare("DELETE FROM tokens WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        // Also delete votes for this admin? Yes.
        $stmt = $pdo->prepare("DELETE FROM votes WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        header("Location: index.php");
        exit;
    }

    if ($action === 'reset_votes') {
        $adminId = $_SESSION['admin_id'];
        $stmt = $pdo->prepare("DELETE FROM votes WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        $stmt = $pdo->prepare("UPDATE tokens SET is_used = 0 WHERE admin_id = ?");
        $stmt->execute([$adminId]);
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

// Delete Admin (Public Access)
if ($action === 'delete_admin') {
    $id = $_GET['id'];
    $redirect = $_GET['redirect'] ?? 'index.php';
    
    // Prevent self-deletion if logged in
    if (isset($_SESSION['admin_id']) && $id == $_SESSION['admin_id']) {
        header("Location: $redirect?error=Tidak bisa menghapus akun sendiri");
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: $redirect?msg=Admin dihapus");
    exit;
}

// --- SECURITY CHECK FOR GET REQUESTS ---
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
if ($action === 'delete_candidate') {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM candidates WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: index.php");
    exit;
}
