<?php
require_once '../config.php';

$action = $_GET['action'] ?? '';

// Auto-Compress Image Function
function autoCompressImage($sourcePath, $destinationPath, $quality = 75, $maxWidth = 800) {
    if (!function_exists('getimagesize') || !function_exists('imagecreatefromjpeg')) {
        return move_uploaded_file($sourcePath, $destinationPath); // Fallback if GD not installed
    }
    
    $info = getimagesize($sourcePath);
    if (!$info) return move_uploaded_file($sourcePath, $destinationPath);
    
    $mime = $info['mime'];
    $width = $info[0];
    $height = $info[1];
    
    if ($width > $maxWidth) {
        $ratio = $maxWidth / $width;
        $newWidth = $maxWidth;
        $newHeight = $height * $ratio;
    } else {
        $newWidth = $width;
        $newHeight = $height;
    }
    
    $imageResized = imagecreatetruecolor($newWidth, $newHeight);
    
    if ($mime == 'image/png' || $mime == 'image/webp') {
        imagealphablending($imageResized, false);
        imagesavealpha($imageResized, true);
        $transparent = imagecolorallocatealpha($imageResized, 255, 255, 255, 127);
        imagefilledrectangle($imageResized, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    switch ($mime) {
        case 'image/jpeg': $image = imagecreatefromjpeg($sourcePath); break;
        case 'image/png': $image = imagecreatefrompng($sourcePath); break;
        case 'image/webp': $image = imagecreatefromwebp($sourcePath); break;
        default: return move_uploaded_file($sourcePath, $destinationPath);
    }
    
    if (!$image) return move_uploaded_file($sourcePath, $destinationPath);
    
    imagecopyresampled($imageResized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    $success = false;
    switch ($mime) {
        case 'image/jpeg': $success = imagejpeg($imageResized, $destinationPath, $quality); break;
        case 'image/png': 
            $pngQuality = 9; 
            $success = imagepng($imageResized, $destinationPath, $pngQuality); 
            break;
        case 'image/webp': $success = imagewebp($imageResized, $destinationPath, $quality); break;
    }
    
    imagedestroy($image);
    imagedestroy($imageResized);
    
    // Fallback if compression somehow fails
    if (!$success && file_exists($sourcePath)) {
        return move_uploaded_file($sourcePath, $destinationPath);
    }
    return $success;
}

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
            $_SESSION['admin_role'] = $user['role'] ?? 'admin';
            header("Location: index.php");
            exit;
        } else {
            header("Location: login.php?error=Username atau Password salah");
            exit;
        }
    }

    // --- SECURITY CHECK ---
    // All actions below require login
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(403);
        die("Unauthorized");
    }

    // Add New Admin (Requires Master Auth)
    if ($action === 'add_admin') {
        if (($_SESSION['admin_role'] ?? 'admin') !== 'master') {
            die("Unauthorized: Master role required");
        }
        $username = trim($_POST['username']);
        $orgName = trim($_POST['organization_name'] ?? 'Organisasi Baru');
        $password = $_POST['password'];
        $role = $_POST['role'] ?? 'admin'; // Get role from form
        $redirect = $_GET['redirect'] ?? 'index.php';

        // Check if username exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            header("Location: $redirect?error=Username sudah ada");
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash, organization_name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $hash, $orgName, $role]);
        
        // Also create default settings for this new admin
        $newAdminId = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO settings (admin_id, min_vote, max_vote, voting_enabled) VALUES (?, 1, 1, 1)")->execute([$newAdminId]);

        header("Location: $redirect?msg=Admin berhasil ditambahkan");
        exit;
    }

    // Batch Add Candidates
    if ($action === 'add_candidates_batch') {
        // Start output buffering to capture any warnings/notices
        ob_start();

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

            // USE ABSOLUTE PATH to avoid relative path issues in Docker/CLI contexts
            $uploadDir = __DIR__ . '/../uploads/';
            
            // Debug Log
            error_log("Starting batch upload for Admin ID: $adminId. Target Dir: $uploadDir");

            if (!is_dir($uploadDir)) {
                // Try to create with 0777 to be more permissive in dev environments
                if (!@mkdir($uploadDir, 0777, true)) {
                    $error = error_get_last();
                    error_log("Failed to create upload dir: " . ($error['message'] ?? 'Unknown error'));
                    throw new Exception("Gagal membuat folder uploads. Cek permission server (chmod 777 uploads).");
                }
            }

            if (!is_writable($uploadDir)) {
                error_log("Upload dir is not writable: $uploadDir");
                 throw new Exception("Folder uploads tidak dapat ditulis (Permission Denied).");
            }

            $stmt = $pdo->prepare("INSERT INTO candidates (admin_id, name, photo, vision) VALUES (?, ?, ?, ?)");

            foreach ($candidates as $index => $data) {
                $name = $data['name'];
                $vision = $data['vision'];
                $photoPath = 'media/Logo Orch-Vote.png'; // Default

                // Check and Process File
                if (isset($_FILES["photos_$index"])) {
                    $file = $_FILES["photos_$index"];
                    if ($file['error'] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        
                        if (!in_array(strtolower($ext), $allowedExts)) {
                            throw new Exception("Format file tidak valid ($ext) untuk kandidat: " . htmlspecialchars($name));
                        }
                        
                        // Generate unique filename
                        $filename = 'candidate_' . $adminId . '_' . time() . '_' . rand(1000, 9999) . '_' . $index . '.' . $ext;
                        $targetFilePath = $uploadDir . $filename;

                        if (autoCompressImage($file['tmp_name'], $targetFilePath, 70, 800)) {
                            $photoPath = 'uploads/' . $filename;
                            error_log("File uploaded and compressed successfully: $targetFilePath");
                        } else {
                            $error = error_get_last();
                            error_log("autoCompressImage failed for $name. Error: " . ($error['message'] ?? 'Unknown'));
                            throw new Exception("Gagal menyimpan file untuk kandidat: " . htmlspecialchars($name));
                        }
                    } else if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                         // File upload error
                         $uploadErrors = [
                            UPLOAD_ERR_INI_SIZE => 'File too large (php.ini)',
                            UPLOAD_ERR_FORM_SIZE => 'File too large (HTML form)',
                            UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
                            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
                         ];
                         $errorMsg = $uploadErrors[$file['error']] ?? 'Unknown Error';
                         error_log("Upload error for $name: $errorMsg (Code: {$file['error']})");
                         throw new Exception("Upload Error: $errorMsg untuk kandidat: " . htmlspecialchars($name));
                    }
                }

                $stmt->execute([$adminId, $name, $photoPath, $vision]);
            }
            
            // Clean output buffer (remove any warnings) and send JSON
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success']);
            exit;

        } catch (Exception $e) {
            // Clean output buffer to ensure valid JSON error response
            ob_end_clean();
            http_response_code(500); 
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
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
        $theme_color = $_POST['theme_color'] ?? '#00984B';
        $adminId = $_SESSION['admin_id'];
        $orgName = $_POST['organization_name'] ?? '';

        if (!empty($orgName)) {
            $stmtOrg = $pdo->prepare("UPDATE admins SET organization_name = ? WHERE id = ?");
            $stmtOrg->execute([$orgName, $adminId]);
        }

        $logoPathUpdate = "";
        $paramsUpdate = [$min, $max, $theme_color, $adminId];
        $paramsInsert = [$adminId, $min, $max, $theme_color];

        if (isset($_FILES['org_logo']) && $_FILES['org_logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['org_logo'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];

            if ($file['size'] <= $maxSize && in_array($file['type'], $allowedTypes)) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'logo_' . $adminId . '_' . time() . '.' . $ext;
                $uploadDir = __DIR__ . '/../uploads/logos/';
                
                if (!is_dir($uploadDir)) {
                    if (!mkdir($uploadDir, 0777, true)) {
                        die("Error: Tidak dapat membuat direktori uploads. Pastikan folder server memiliki izin tulis (write permission).");
                    }
                }

                if (autoCompressImage($file['tmp_name'], $uploadDir . $filename, 80, 400)) {
                    $logo_path = 'uploads/logos/' . $filename;
                    $logoPathUpdate = ", logo_path = ?";
                    
                    // Modify params for update
                    $paramsUpdate = [$min, $max, $theme_color, $logo_path, $adminId];
                    // Modify params for insert
                    $paramsInsert = [$adminId, $min, $max, $theme_color, $logo_path];
                }
            }
        }

        // Check if settings exist
        $check = $pdo->prepare("SELECT id FROM settings WHERE admin_id = ?");
        $check->execute([$adminId]);
        
        if ($check->rowCount() > 0) {
             $stmt = $pdo->prepare("UPDATE settings SET min_vote = ?, max_vote = ?, theme_color = ? $logoPathUpdate WHERE admin_id = ?");
             $stmt->execute($paramsUpdate);
        } else {
             $insertCols = "admin_id, min_vote, max_vote, theme_color" . ($logoPathUpdate ? ", logo_path" : "");
             $insertVals = "?, ?, ?, ?" . ($logoPathUpdate ? ", ?" : "");
             $stmt = $pdo->prepare("INSERT INTO settings ($insertCols) VALUES ($insertVals)");
             $stmt->execute($paramsInsert);
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

    // Create Poll
    if ($action === 'create_poll') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $successMsg = trim($_POST['success_message'] ?? 'Terima kasih, tanggapan Anda telah berhasil disimpan!');
        $questions = $_POST['questions'] ?? [];
        $adminId = $_SESSION['admin_id'];

        if (empty($title) || empty($questions)) {
            header("Location: manage_polls.php?error=Judul dan minimal 1 pertanyaan harus diisi");
            exit;
        }

        try {
            $pdo->beginTransaction();

            $slug = bin2hex(random_bytes(8));
            $stmt = $pdo->prepare("INSERT INTO polls (admin_id, title, description, success_message, slug) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$adminId, $title, $description, $successMsg, $slug]);
            $pollId = $pdo->lastInsertId();

            $stmtQ = $pdo->prepare("INSERT INTO poll_questions (poll_id, question_text, question_type, is_required, order_num) VALUES (?, ?, ?, ?, ?)");
            $stmtOpt = $pdo->prepare("INSERT INTO poll_options (poll_id, question_id, option_text) VALUES (?, ?, ?)");

            $order = 1;
            foreach ($questions as $q) {
                $qText = trim($q['text'] ?? '');
                $qType = $q['type'] ?? 'short_text';
                $isRequired = isset($q['is_required']) ? 1 : 0;
                
                if (empty($qText)) continue;

                $stmtQ->execute([$pollId, $qText, $qType, $isRequired, $order++]);
                $questionId = $pdo->lastInsertId();

                if ($qType === 'polling' && isset($q['options'])) {
                    foreach ($q['options'] as $opt) {
                        $optText = trim($opt);
                        if (!empty($optText)) {
                            $stmtOpt->execute([$pollId, $questionId, $optText]);
                        }
                    }
                }
            }

            $pdo->commit();
            header("Location: manage_polls?msg=Form Polling berhasil dibuat");
        } catch (Exception $e) {
            $pdo->rollBack();
            header("Location: manage_polls?error=Gagal membuat Form Polling: " . urlencode($e->getMessage()));
        }
        exit;
    }

    // Edit Poll - Temporarily disabled due to complex structure. Will implement a dedicated page later.
    if ($action === 'edit_poll') {
        header("Location: manage_polls?error=Fitur edit form sedang dalam pembaruan.");
        exit;
    }

    // Export CSV
    if ($action === 'export_csv') {
        $pollId = $_GET['id'] ?? 0;
        $adminId = $_SESSION['admin_id'];
        
        // Verify ownership
        $stmt = $pdo->prepare("SELECT title FROM polls WHERE id = ? AND admin_id = ?");
        $stmt->execute([$pollId, $adminId]);
        $poll = $stmt->fetch();
        
        if (!$poll) {
            die("Akses ditolak atau polling tidak ditemukan.");
        }

        // Fetch Questions
        $stmtQ = $pdo->prepare("SELECT id, question_text FROM poll_questions WHERE poll_id = ? ORDER BY order_num ASC, id ASC");
        $stmtQ->execute([$pollId]);
        $questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Answers Grouped by IP & Date (Approximating a user session)
        // Since we don't have a submission ID, we'll group by IP and submitted_at (down to the minute)
        // A better way is just fetching all answers and grouping in PHP.
        $stmtA = $pdo->prepare("
            SELECT a.question_id, a.text_answer, a.ip_address, a.submitted_at, o.option_text
            FROM poll_answers a
            LEFT JOIN poll_options o ON a.option_id = o.id
            WHERE a.poll_id = ?
            ORDER BY a.submitted_at ASC
        ");
        $stmtA->execute([$pollId]);
        $answers = $stmtA->fetchAll(PDO::FETCH_ASSOC);

        // Group by submission "session"
        $submissions = [];
        foreach ($answers as $ans) {
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

        // Output CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Hasil_Polling_' . urlencode($poll['title']) . '_' . date('Ymd_His') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // Header Row
        $headerRow = ['Waktu Submit', 'IP Address'];
        foreach ($questions as $q) {
            $headerRow[] = $q['question_text'];
        }
        fputcsv($output, $headerRow);

        // Data Rows
        foreach ($submissions as $sub) {
            $row = [$sub['waktu'], $sub['ip']];
            foreach ($questions as $q) {
                $row[] = isset($sub['answers'][$q['id']]) ? $sub['answers'][$q['id']] : '';
            }
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }
}

// Delete Admin (Public Access)
if ($action === 'delete_admin') {
    if (($_SESSION['admin_role'] ?? 'admin') !== 'master') {
        die("Unauthorized: Master role required");
    }
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

if ($action === 'toggle_poll') {
    $id = $_GET['id'];
    $state = $_GET['state'];
    $stmt = $pdo->prepare("UPDATE polls SET is_active = ? WHERE id = ? AND admin_id = ?");
    $stmt->execute([$state, $id, $_SESSION['admin_id']]);
    header("Location: manage_polls?msg=Status polling diperbarui");
    exit;
}

if ($action === 'delete_poll') {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM polls WHERE id = ? AND admin_id = ?");
    $stmt->execute([$id, $_SESSION['admin_id']]);
    header("Location: manage_polls?msg=Polling berhasil dihapus");
    exit;
}

if ($action === 'reset_poll_votes') {
    $id = $_GET['id'];
    
    // Verifikasi kepemilikan
    $stmt = $pdo->prepare("SELECT id FROM polls WHERE id = ? AND admin_id = ?");
    $stmt->execute([$id, $_SESSION['admin_id']]);
    if ($stmt->fetch()) {
        $stmtDel = $pdo->prepare("DELETE FROM poll_votes WHERE poll_id = ?");
        $stmtDel->execute([$id]);
        header("Location: manage_polls?msg=Semua suara pada polling berhasil dihapus");
    } else {
        header("Location: manage_polls?error=Polling tidak ditemukan");
    }
    exit;
}
