<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /");
    exit;
}

$pollId = $_POST['poll_id'] ?? 0;
$answers = $_POST['answers'] ?? []; // Associative array: question_id => answer
$slug = $_POST['slug'] ?? '';

// Get IP Address safely
function getClientIP() {
    if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else if (array_key_exists('HTTP_X_REAL_IP', $_SERVER)) {
        return $_SERVER['HTTP_X_REAL_IP'];
    }
    return $_SERVER['REMOTE_ADDR'];
}

$ipAddress = getClientIP();

if (!$pollId) {
    header("Location: poll?slug=" . urlencode($slug) . "&error=Form tidak valid");
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Check if Poll is active
    $stmt = $pdo->prepare("SELECT is_active FROM polls WHERE id = ? FOR UPDATE");
    $stmt->execute([$pollId]);
    $poll = $stmt->fetch();

    if (!$poll) {
        throw new Exception("Form tidak ditemukan.");
    }
    if (!$poll['is_active']) {
        throw new Exception("Form sudah ditutup.");
    }

    // 2. Validate Questions
    $stmtQ = $pdo->prepare("SELECT id, question_type, is_required FROM poll_questions WHERE poll_id = ?");
    $stmtQ->execute([$pollId]);
    $questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);
    
    $validAnswers = []; // To store processed answers ready for insert
    
    foreach ($questions as $q) {
        $qId = $q['id'];
        $hasAnswer = isset($answers[$qId]) && trim($answers[$qId]) !== '';
        
        if ($q['is_required'] && !$hasAnswer) {
            throw new Exception("Pertanyaan wajib belum diisi.");
        }
        
        if ($hasAnswer) {
            $ansValue = trim($answers[$qId]);
            if ($q['question_type'] === 'polling') {
                // Validate if option_id is valid for this question
                $stmtOpt = $pdo->prepare("SELECT id FROM poll_options WHERE id = ? AND question_id = ?");
                $stmtOpt->execute([$ansValue, $qId]);
                if (!$stmtOpt->fetch()) {
                    throw new Exception("Pilihan jawaban tidak valid.");
                }
                $validAnswers[] = [
                    'question_id' => $qId,
                    'option_id' => $ansValue,
                    'text_answer' => null
                ];
            } else {
                $validAnswers[] = [
                    'question_id' => $qId,
                    'option_id' => null,
                    'text_answer' => $ansValue
                ];
            }
        }
    }

    // 3. Check Cookie (Limit 1 vote per browser per poll)
    $cookieName = "voted_poll_" . $pollId;
    if (isset($_COOKIE[$cookieName])) {
        throw new Exception("Anda sudah pernah mengisi form ini (Berdasarkan sesi perangkat).");
    }

    // 4. Insert Answers
    $stmtIns = $pdo->prepare("INSERT INTO poll_answers (poll_id, question_id, option_id, text_answer, ip_address) VALUES (?, ?, ?, ?, ?)");
    foreach ($validAnswers as $ans) {
        $stmtIns->execute([
            $pollId, 
            $ans['question_id'], 
            $ans['option_id'], 
            $ans['text_answer'], 
            $ipAddress
        ]);
    }

    $pdo->commit();

    // 5. Set Cookie to prevent double voting (expires in 1 year)
    setcookie($cookieName, "1", time() + (365 * 24 * 60 * 60), "/");

    // Success redirect
    header("Location: poll_success?slug=" . urlencode($slug));
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: poll?slug=" . urlencode($slug) . "&error=" . urlencode($e->getMessage()));
    exit;
}
