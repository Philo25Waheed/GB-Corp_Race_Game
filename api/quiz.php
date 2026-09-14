<?php
/**
 * Quiz & Challenge Progression API (Hardened & Protected)
 * Handles quiz questions retrieval, user answer submission, scoring, and department car advancement
 */

define('IS_API', true);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

$pdo = getDBConnection();
$action = $_GET['action'] ?? ($_POST['action'] ?? '');
if (empty($action) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $action = 'get_questions';
}

function jsonResponse($success, $message = '', $data = null) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 1. Get Questions for the Active Week (or specific week)
if ($action === 'get_questions') {
    $weekId = isset($_GET['week_id']) ? (int)$_GET['week_id'] : null;

    if (!$weekId || $weekId <= 0) {
        $stmtActive = $pdo->query("SELECT `setting_value` FROM `system_settings` WHERE `setting_key` = 'active_week_id'");
        $weekId = (int)($stmtActive->fetchColumn() ?: 1);
    }

    // Security: Do NOT select `correct_option` in client-facing query
    $stmt = $pdo->prepare("
        SELECT id, week_id, category_en, category_ar, 
               question_en, question_ar,
               option_a_en, option_a_ar,
               option_b_en, option_b_ar,
               option_c_en, option_c_ar,
               option_d_en, option_d_ar,
               points
        FROM `questions`
        WHERE `week_id` = ?
        ORDER BY `id` ASC
    ");
    $stmt->execute([$weekId]);
    $questions = $stmt->fetchAll();

    if (empty($questions)) {
        if (function_exists('ensureQuestionsSeeded')) {
            ensureQuestionsSeeded($pdo, $weekId);
        }
        $stmt->execute([$weekId]);
        $questions = $stmt->fetchAll();
    }

    // Fallback: If target week still has no questions, try active week 1 questions
    if (empty($questions) && $weekId !== 1) {
        $stmt->execute([1]);
        $questions = $stmt->fetchAll();
    }


    // Check if logged in user already answered any of these
    $answeredMap = [];
    $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : (int)($_GET['user_id'] ?? 0);
    if ($userId > 0) {
        $stmtAns = $pdo->prepare("SELECT question_id, selected_option, is_correct, points_earned, created_at FROM `quiz_attempts` WHERE user_id = ? AND week_id = ?");
        $stmtAns->execute([$userId, $weekId]);
        $attempts = $stmtAns->fetchAll();
        foreach ($attempts as $att) {
            $answeredMap[$att['question_id']] = $att;
        }
    }

    jsonResponse(true, 'Questions retrieved', [
        'week_id' => $weekId,
        'questions' => $questions,
        'user_attempts' => $answeredMap
    ]);
}

// 2. Start Question (Anti-Cheat: Register question lock on presentation)
if ($action === 'start_question') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    if (empty($input)) $input = $_GET;

    // CSRF Protection
    $token = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!empty($_SESSION['csrf_token']) && !verifyCsrfToken($token)) {
        if (php_sapi_name() !== 'cli') {
            jsonResponse(false, 'انتهت صلاحية جلسة الأمان (CSRF Token Mismatch).');
        }
    }

    $questionId = (int)($input['question_id'] ?? 0);
    // Security: Session Identity with input fallback
    $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : (int)($input['user_id'] ?? 0);

    if (!$questionId || $userId <= 0) {
        jsonResponse(false, 'يرجى تسجيل الدخول أولاً للمشاركة في الكويز | Please sign in first');
    }

    // Rate Limit: Prevent flooding start_question (Max 15 starts per minute)
    $rateStart = checkRateLimit($pdo, 'start_quiz_q', (string)$userId, 15, 60, 60);
    if (!$rateStart['allowed']) {
        jsonResponse(false, $rateStart['message']);
    }

    // Verify user and get department
    $stmtUserCheck = $pdo->prepare("SELECT id, department_id FROM `users` WHERE `id` = ?");
    $stmtUserCheck->execute([$userId]);
    $userRow = $stmtUserCheck->fetch();
    if (!$userRow) {
        jsonResponse(false, 'المستخدم غير مسجل | User not found');
    }
    $departmentId = $userRow['department_id'];

    // Verify question exists
    $stmtQ = $pdo->prepare("SELECT id, week_id FROM `questions` WHERE `id` = ?");
    $stmtQ->execute([$questionId]);
    $qRow = $stmtQ->fetch();
    if (!$qRow) {
        jsonResponse(false, 'السؤال غير موجود | Question not found');
    }
    $weekId = (int)$qRow['week_id'];

    // Check if question already exists in attempts
    $stmtCheck = $pdo->prepare("SELECT id, selected_option FROM `quiz_attempts` WHERE `user_id` = ? AND `question_id` = ?");
    $stmtCheck->execute([$userId, $questionId]);
    $existing = $stmtCheck->fetch();

    if ($existing) {
        if ($existing['selected_option'] === 'PENDING') {
            jsonResponse(true, 'السؤال قيد الإجابة حالياً | Question in progress', [
                'status' => 'PENDING'
            ]);
        } else {
            jsonResponse(false, 'تم الدخول إلى هذا السؤال مسبقاً ولا يمكن إعادته منعاً للغش | Question already attempted', [
                'status' => $existing['selected_option'],
                'already_attempted' => true
            ]);
        }
    }

    // Lock question with PENDING state
    $stmtLock = $pdo->prepare("INSERT INTO `quiz_attempts` (`user_id`, `department_id`, `week_id`, `question_id`, `selected_option`, `is_correct`, `points_earned`) VALUES (?, ?, ?, ?, 'PENDING', 0, 0)");
    $stmtLock->execute([$userId, $departmentId, $weekId, $questionId]);

    jsonResponse(true, 'تم بدء السؤال وتأمين المحاولة | Question started and locked', [
        'status' => 'PENDING',
        'question_id' => $questionId
    ]);
}

// 3. Forfeit Question (Anti-Cheat: Tab switch, window blur, devtools or navigation away)
if ($action === 'forfeit_question') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    if (empty($input)) $input = $_GET;

    $questionId = (int)($input['question_id'] ?? 0);
    // Security: Session Identity with input fallback
    $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : (int)($input['user_id'] ?? 0);
    $reason = htmlspecialchars(strip_tags(trim($input['reason'] ?? 'tab_switch')), ENT_QUOTES, 'UTF-8');

    if (!$questionId || $userId <= 0) {
        jsonResponse(false, 'بيانات غير صالحة لإلغاء السؤال | Invalid forfeit request');
    }

    // Check user & department
    $stmtUserCheck = $pdo->prepare("SELECT id, department_id FROM `users` WHERE `id` = ?");
    $stmtUserCheck->execute([$userId]);
    $userRow = $stmtUserCheck->fetch();
    if (!$userRow) {
        jsonResponse(false, 'المستخدم غير مسجل | User not found');
    }
    $departmentId = $userRow['department_id'];

    $stmtQ = $pdo->prepare("SELECT id, week_id FROM `questions` WHERE `id` = ?");
    $stmtQ->execute([$questionId]);
    $qRow = $stmtQ->fetch();
    $weekId = $qRow ? (int)$qRow['week_id'] : 1;

    // Check existing attempt
    $stmtCheck = $pdo->prepare("SELECT id, selected_option FROM `quiz_attempts` WHERE `user_id` = ? AND `question_id` = ?");
    $stmtCheck->execute([$userId, $questionId]);
    $existing = $stmtCheck->fetch();

    if ($existing) {
        if ($existing['selected_option'] === 'PENDING') {
            $stmtForfeit = $pdo->prepare("UPDATE `quiz_attempts` SET `selected_option` = 'FORFEIT', `is_correct` = 0, `points_earned` = 0 WHERE `id` = ?");
            $stmtForfeit->execute([$existing['id']]);
        }
    } else {
        $stmtInsertForfeit = $pdo->prepare("INSERT INTO `quiz_attempts` (`user_id`, `department_id`, `week_id`, `question_id`, `selected_option`, `is_correct`, `points_earned`) VALUES (?, ?, ?, ?, 'FORFEIT', 0, 0)");
        $stmtInsertForfeit->execute([$userId, $departmentId, $weekId, $questionId]);
    }

    jsonResponse(true, '🚨 تم رصد محاولة مغادرة الصفحة أو فتح أدوات الفحص وإلغاء السؤال فوراً منعاً للغش | Question forfeited due to anti-cheat policy', [
        'status' => 'FORFEIT',
        'question_id' => $questionId
    ]);
}

// 4. Submit Answer (Hardened & Protected)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'submit_answer') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    // CSRF Protection
    $token = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!empty($_SESSION['csrf_token']) && !verifyCsrfToken($token)) {
        if (php_sapi_name() !== 'cli') {
            jsonResponse(false, 'انتهت صلاحية جلسة الأمان (CSRF Token Mismatch). يرجى تحديث الصفحة.');
        }
    }

    $questionId = (int)($input['question_id'] ?? 0);
    $selectedOption = strtoupper(trim($input['selected_option'] ?? ''));
    // Security: Session Identity with input fallback
    $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : (int)($input['user_id'] ?? 0);

    if (!$questionId || !in_array($selectedOption, ['A', 'B', 'C', 'D'])) {
        jsonResponse(false, 'بيانات الإجابة غير مكتملة | Invalid answer submission');
    }

    // Require valid logged-in user in session or input
    if ($userId <= 0) {
        jsonResponse(false, 'يرجى تسجيل الدخول أولاً لحساب النقاط لقسمك | Please sign in first');
    }

    // Verify user exists and get verified department
    $stmtUserCheck = $pdo->prepare("SELECT id, department_id FROM `users` WHERE `id` = ?");
    $stmtUserCheck->execute([$userId]);
    $userRow = $stmtUserCheck->fetch();
    if (!$userRow) {
        jsonResponse(false, 'المتسابق غير مسجل بقاعدة البيانات | User not found');
    }
    $departmentId = $userRow['department_id'];

    // Fetch Question details
    $stmtQ = $pdo->prepare("SELECT * FROM `questions` WHERE `id` = ?");
    $stmtQ->execute([$questionId]);
    $question = $stmtQ->fetch();

    if (!$question) {
        jsonResponse(false, 'السؤال غير موجود | Question not found');
    }

    // Check existing attempt
    $stmtCheck = $pdo->prepare("SELECT id, selected_option FROM `quiz_attempts` WHERE `user_id` = ? AND `question_id` = ?");
    $stmtCheck->execute([$userId, $questionId]);
    $attempt = $stmtCheck->fetch();

    if ($attempt) {
        // If already answered with a definitive choice, do not allow re-answering
        if (in_array($attempt['selected_option'], ['A', 'B', 'C', 'D'])) {
            jsonResponse(false, 'تمت الإجابة على هذا السؤال مسبقاً! | Question already answered');
        }
    }

    $isCorrect = ($selectedOption === $question['correct_option']);
    $pointsEarned = $isCorrect ? (int)$question['points'] : 0;
    $weekId = (int)$question['week_id'];

    // Execute within database transaction
    $pdo->beginTransaction();
    try {
        if ($attempt) {
            // Update existing pending attempt
            $stmtRecord = $pdo->prepare("
                UPDATE `quiz_attempts` 
                SET `selected_option` = ?,
                    `is_correct` = ?,
                    `points_earned` = ?
                WHERE `id` = ?
            ");
            $stmtRecord->execute([$selectedOption, $isCorrect ? 1 : 0, $pointsEarned, $attempt['id']]);
        } else {
            // If start_question was delayed by network latency, insert cleanly right now
            $stmtInsert = $pdo->prepare("
                INSERT INTO `quiz_attempts` (`user_id`, `department_id`, `week_id`, `question_id`, `selected_option`, `is_correct`, `points_earned`)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtInsert->execute([$userId, $departmentId, $weekId, $questionId, $selectedOption, $isCorrect ? 1 : 0, $pointsEarned]);
        }

        // Award Points & Advance Department Car if correct
        if ($isCorrect && $pointsEarned > 0) {
            $steps = max(1, round($pointsEarned / 10));
            
            $stmtRaceLen = $pdo->query("SELECT `setting_value` FROM `system_settings` WHERE `setting_key` = 'race_length'");
            $raceLen = (int)($stmtRaceLen->fetchColumn() ?: 15);

            $stmtDept = $pdo->prepare("
                UPDATE `departments` 
                SET `total_points` = `total_points` + ?,
                    `position` = LEAST(?, `position` + ?)
                WHERE `id` = ?
            ");
            $stmtDept->execute([$pointsEarned, $raceLen, $steps, $departmentId]);

            // Update weekly scores
            $stmtWS = $pdo->prepare("
                INSERT INTO `weekly_scores` (`week_id`, `department_id`, `score`)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE `score` = `score` + VALUES(`score`)
            ");
            $stmtWS->execute([$weekId, $departmentId, $pointsEarned]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonResponse(false, 'حدث خطأ أثناء معالجة الإجابة: ' . $e->getMessage());
    }

    // Fetch updated department state
    $stmtDeptFresh = $pdo->prepare("SELECT * FROM `departments` WHERE `id` = ?");
    $stmtDeptFresh->execute([$departmentId]);
    $freshDept = $stmtDeptFresh->fetch();

    jsonResponse(true, $isCorrect ? 'إجابة صحيحة! أحسنت! حصد قسمك النقاط 🎉 | Correct Answer!' : 'إجابة خاطئة! حظ أوفر في السؤال القادم | Incorrect Answer', [
        'is_correct' => $isCorrect,
        'correct_option' => $question['correct_option'],
        'points_earned' => $pointsEarned,
        'department' => $freshDept
    ]);
}

jsonResponse(false, 'Invalid Action');
