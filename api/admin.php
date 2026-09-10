<?php
/**
 * Admin Dashboard Management API
 * Native PHP & MySQL Backend for complete site & race control
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

$pdo = getDBConnection();
$action = $_GET['action'] ?? '';

function jsonResponse($success, $message = '', $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Security: Verified Admin Session Check (Strictly validated against database)
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$isAdmin = false;

if (!empty($_SESSION['user_id']) && !empty($_SESSION['is_admin'])) {
    $stmtAdminCheck = $pdo->prepare("SELECT role, is_admin FROM `users` WHERE `id` = ?");
    $stmtAdminCheck->execute([$_SESSION['user_id']]);
    $adminUser = $stmtAdminCheck->fetch();
    if ($adminUser && ($adminUser['role'] === 'admin' || !empty($adminUser['is_admin']))) {
        $isAdmin = true;
    }
}

// Allow CLI scripts to test with session or explicitly
if (php_sapi_name() === 'cli' && !empty($_SESSION['is_admin'])) {
    $isAdmin = true;
}

if (!$isAdmin && $action !== 'login_check') {
    jsonResponse(false, 'غير مصرح لك بالوصول إلى لوحة التحكم. يتطلب هذا القسم حساب مشرف موثق ومسجل بالنظام. | Unauthorized Admin Access');
}

// CSRF Protection for state-modifying requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!empty($_SESSION['csrf_token']) && !verifyCsrfToken($token)) {
        if (php_sapi_name() !== 'cli') {
            jsonResponse(false, 'انتهت صلاحية جلسة الأمان (CSRF Token Mismatch). يرجى تحديث الصفحة.');
        }
    }
}

// 1. Set Active Week
if ($action === 'set_active_week') {
    $weekId = (int)($input['week_id'] ?? 1);
    
    // Deactivate all weeks then activate chosen week
    $pdo->exec("UPDATE `weeks` SET `is_active` = 0");
    $stmtAct = $pdo->prepare("UPDATE `weeks` SET `is_active` = 1 WHERE `id` = ?");
    $stmtAct->execute([$weekId]);

    // Update system_settings
    $stmtSet = $pdo->prepare("INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES ('active_week_id', ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
    $stmtSet->execute([$weekId]);

    jsonResponse(true, "تم تعيين الأسبوع {$weekId} كأسبوع نشط بنجاح | Week {$weekId} is now active", ['active_week_id' => $weekId]);
}

// 2. Update Week Details
if ($action === 'update_week') {
    $weekId = (int)($input['id'] ?? 0);
    $titleEn = trim($input['title_en'] ?? '');
    $titleAr = trim($input['title_ar'] ?? '');
    $challengeType = trim($input['challenge_type'] ?? 'quiz');
    $descEn = trim($input['description_en'] ?? '');
    $descAr = trim($input['description_ar'] ?? '');
    $pointsReward = (int)($input['points_reward'] ?? 10);
    $isCompleted = !empty($input['is_completed']) ? 1 : 0;

    if (!$weekId || empty($titleEn) || empty($titleAr)) {
        jsonResponse(false, 'بيانات الأسبوع غير مكتملة | Incomplete week details');
    }

    $stmt = $pdo->prepare("
        UPDATE `weeks` 
        SET `title_en` = ?, `title_ar` = ?, `challenge_type` = ?, 
            `description_en` = ?, `description_ar` = ?, `points_reward` = ?, 
            `is_completed` = ?
        WHERE `id` = ?
    ");
    $stmt->execute([$titleEn, $titleAr, $challengeType, $descEn, $descAr, $pointsReward, $isCompleted, $weekId]);

    jsonResponse(true, 'تم تحديث بيانات الأسبوع بنجاح | Week updated successfully');
}

// 3. Quiz Questions Operations (List, Add, Edit, Delete)
if ($action === 'list_questions') {
    $weekId = isset($_GET['week_id']) ? (int)$_GET['week_id'] : null;
    $sql = "SELECT q.*, w.week_number, w.title_en AS week_title_en FROM `questions` q JOIN `weeks` w ON q.week_id = w.id";
    if ($weekId) {
        $sql .= " WHERE q.week_id = " . $weekId;
    }
    $sql .= " ORDER BY q.week_id ASC, q.id ASC";
    $questions = $pdo->query($sql)->fetchAll();
    jsonResponse(true, 'Questions list', $questions);
}

if ($action === 'save_question') {
    $qId = (int)($input['id'] ?? 0);
    $weekId = (int)($input['week_id'] ?? 1);
    $catEn = trim($input['category_en'] ?? 'General Knowledge');
    $catAr = trim($input['category_ar'] ?? 'معلومات عامة');
    $qEn = trim($input['question_en'] ?? '');
    $qAr = trim($input['question_ar'] ?? '');
    $optAEn = trim($input['option_a_en'] ?? '');
    $optAAr = trim($input['option_a_ar'] ?? '');
    $optBEn = trim($input['option_b_en'] ?? '');
    $optBAr = trim($input['option_b_ar'] ?? '');
    $optCEn = trim($input['option_c_en'] ?? '');
    $optCAr = trim($input['option_c_ar'] ?? '');
    $optDEn = trim($input['option_d_en'] ?? '');
    $optDAr = trim($input['option_d_ar'] ?? '');
    $correct = strtoupper(trim($input['correct_option'] ?? 'A'));
    $points = (int)($input['points'] ?? 10);

    if (empty($qEn) || empty($qAr) || empty($optAEn) || empty($optBEn)) {
        jsonResponse(false, 'يرجى إكمال بيانات السؤال والخيارات | Incomplete question fields');
    }

    if ($qId > 0) {
        $stmt = $pdo->prepare("
            UPDATE `questions`
            SET `week_id` = ?, `category_en` = ?, `category_ar` = ?,
                `question_en` = ?, `question_ar` = ?,
                `option_a_en` = ?, `option_a_ar` = ?,
                `option_b_en` = ?, `option_b_ar` = ?,
                `option_c_en` = ?, `option_c_ar` = ?,
                `option_d_en` = ?, `option_d_ar` = ?,
                `correct_option` = ?, `points` = ?
            WHERE `id` = ?
        ");
        $stmt->execute([$weekId, $catEn, $catAr, $qEn, $qAr, $optAEn, $optAAr, $optBEn, $optBAr, $optCEn, $optCAr, $optDEn, $optDAr, $correct, $points, $qId]);
        jsonResponse(true, 'تم تعديل السؤال بنجاح | Question updated successfully');
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO `questions` (`week_id`, `category_en`, `category_ar`, `question_en`, `question_ar`, `option_a_en`, `option_a_ar`, `option_b_en`, `option_b_ar`, `option_c_en`, `option_c_ar`, `option_d_en`, `option_d_ar`, `correct_option`, `points`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$weekId, $catEn, $catAr, $qEn, $qAr, $optAEn, $optAAr, $optBEn, $optBAr, $optCEn, $optCAr, $optDEn, $optDAr, $correct, $points]);
        jsonResponse(true, 'تم إضافة السؤال الجديد بنجاح | Question added successfully');
    }
}

if ($action === 'delete_question') {
    $qId = (int)($input['id'] ?? 0);
    if ($qId > 0) {
        $stmt = $pdo->prepare("DELETE FROM `questions` WHERE `id` = ?");
        $stmt->execute([$qId]);
        jsonResponse(true, 'تم حذف السؤال بنجاح | Question deleted');
    }
    jsonResponse(false, 'Invalid question ID');
}

// 4. Photo Submissions Review & Grading
if ($action === 'list_photo_submissions') {
    $stmt = $pdo->query("
        SELECT ps.*, u.name AS user_name, u.email AS user_email,
               d.name_en AS dept_name_en, d.name_ar AS dept_name_ar, d.code AS dept_code, d.color AS dept_color,
               w.week_number, w.title_en AS week_title_en
        FROM `photo_submissions` ps
        JOIN `users` u ON ps.user_id = u.id
        JOIN `departments` d ON ps.department_id = d.id
        JOIN `weeks` w ON ps.week_id = w.id
        ORDER BY ps.id DESC
    ");
    $submissions = $stmt->fetchAll();
    jsonResponse(true, 'Photo submissions list', $submissions);
}

if ($action === 'grade_photo_submission') {
    $subId = (int)($input['submission_id'] ?? 0);
    $status = in_array($input['status'] ?? '', ['approved', 'rejected', 'pending']) ? $input['status'] : 'approved';
    $points = (int)($input['points'] ?? 15);

    $stmtGet = $pdo->prepare("SELECT * FROM `photo_submissions` WHERE `id` = ?");
    $stmtGet->execute([$subId]);
    $sub = $stmtGet->fetch();

    if (!$sub) {
        jsonResponse(false, 'المشاركة غير موجودة | Submission not found');
    }

    $stmtUpdate = $pdo->prepare("UPDATE `photo_submissions` SET `status` = ?, `points_awarded` = ? WHERE `id` = ?");
    $stmtUpdate->execute([$status, $points, $subId]);

    // If points modified or newly approved, update department & weekly scores
    if ($status === 'approved' && $points > 0) {
        $diffPoints = $points - (int)$sub['points_awarded'];
        if ($diffPoints != 0) {
            $pdo->prepare("UPDATE `departments` SET `total_points` = GREATEST(0, `total_points` + ?) WHERE `id` = ?")->execute([$diffPoints, $sub['department_id']]);
            $pdo->prepare("INSERT INTO `weekly_scores` (`week_id`, `department_id`, `score`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `score` = GREATEST(0, `score` + ?)")->execute([$sub['week_id'], $sub['department_id'], $diffPoints, $diffPoints]);
        }
    }

    jsonResponse(true, 'تم تحديث حالة وتقييم الصورة بنجاح | Photo graded successfully');
}

// 5. Award Team Activity Points or Apply Penalties
if ($action === 'award_team_activity' || $action === 'adjust_points') {
    $deptId = trim(strtolower($input['department_id'] ?? ''));
    $weekId = (int)($input['week_id'] ?? 0);
    if (!$weekId) {
        $stmtAct = $pdo->query("SELECT `setting_value` FROM `system_settings` WHERE `setting_key` = 'active_week_id'");
        $weekId = (int)($stmtAct->fetchColumn() ?: 1);
    }
    
    $activityName = trim($input['activity_name'] ?? '');
    $notes = trim($input['notes'] ?? '');
    $pointsDelta = (int)($input['points'] ?? ($input['points_delta'] ?? 30));
    $operationType = $input['operation_type'] ?? ($pointsDelta < 0 ? 'deduct' : 'add');
    
    if ($operationType === 'deduct' && $pointsDelta > 0) {
        $pointsDelta = -$pointsDelta;
    }

    if (empty($deptId)) {
        jsonResponse(false, 'يرجى تحديد القسم | Select department');
    }

    if (empty($activityName)) {
        $activityName = ($pointsDelta >= 0) ? 'منح نقاط نشاط إضافية' : 'خصم نقاط إداري / جزاء';
    }

    // Steps movement on highway (1 step approx 10 points)
    $steps = round($pointsDelta / 10);

    // Record submission log
    $stmtSub = $pdo->prepare("
        INSERT INTO `team_activity_submissions` (`department_id`, `week_id`, `activity_name`, `notes`, `points_awarded`)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmtSub->execute([$deptId, $weekId, $activityName, $notes, $pointsDelta]);

    // Update department points and car position
    $stmtRaceLen = $pdo->query("SELECT `setting_value` FROM `system_settings` WHERE `setting_key` = 'race_length'");
    $raceLen = (int)($stmtRaceLen->fetchColumn() ?: 15);

    $stmtDept = $pdo->prepare("
        UPDATE `departments`
        SET `total_points` = GREATEST(0, `total_points` + ?),
            `position` = GREATEST(0, LEAST(?, `position` + ?))
        WHERE `id` = ?
    ");
    $stmtDept->execute([$pointsDelta, $raceLen, $steps, $deptId]);

    // Update weekly scores
    $stmtWS = $pdo->prepare("
        INSERT INTO `weekly_scores` (`week_id`, `department_id`, `score`)
        VALUES (?, ?, GREATEST(0, ?))
        ON DUPLICATE KEY UPDATE `score` = GREATEST(0, `score` + ?)
    ");
    $stmtWS->execute([$weekId, $deptId, $pointsDelta, $pointsDelta]);

    $stmtGet = $pdo->prepare("SELECT * FROM `departments` WHERE `id` = ?");
    $stmtGet->execute([$deptId]);
    $freshDept = $stmtGet->fetch();

    $msg = ($pointsDelta >= 0) 
        ? "تم منح {$pointsDelta} نقطة بنجاح لقسم {$deptId}! | Successfully added {$pointsDelta} points!"
        : "تم خصم " . abs($pointsDelta) . " نقطة بنجاح من قسم {$deptId}! | Successfully deducted " . abs($pointsDelta) . " points!";

    jsonResponse(true, $msg, ['department' => $freshDept, 'points_delta' => $pointsDelta]);
}

// 6. Direct Car Advancement / Highway Controls
if ($action === 'move_car') {
    $deptId = trim(strtolower($input['department_id'] ?? ''));
    $steps = (int)($input['steps'] ?? 1);
    $pointsDelta = isset($input['points']) ? (int)$input['points'] : ($steps * 10);

    $stmtRaceLen = $pdo->query("SELECT `setting_value` FROM `system_settings` WHERE `setting_key` = 'race_length'");
    $raceLen = (int)($stmtRaceLen->fetchColumn() ?: 15);

    $stmtDept = $pdo->prepare("
        UPDATE `departments`
        SET `position` = GREATEST(0, LEAST(?, `position` + ?)),
            `total_points` = GREATEST(0, `total_points` + ?)
        WHERE `id` = ?
    ");
    $stmtDept->execute([$raceLen, $steps, $pointsDelta, $deptId]);

    // Update weekly scores if delta provided
    if ($pointsDelta != 0) {
        $stmtAct = $pdo->query("SELECT `setting_value` FROM `system_settings` WHERE `setting_key` = 'active_week_id'");
        $activeWeekId = (int)($stmtAct->fetchColumn() ?: 1);
        $pdo->prepare("INSERT INTO `weekly_scores` (`week_id`, `department_id`, `score`) VALUES (?, ?, GREATEST(0, ?)) ON DUPLICATE KEY UPDATE `score` = GREATEST(0, `score` + ?)")
            ->execute([$activeWeekId, $deptId, $pointsDelta, $pointsDelta]);
    }

    $stmtGet = $pdo->prepare("SELECT * FROM `departments` WHERE `id` = ?");
    $stmtGet->execute([$deptId]);
    $dept = $stmtGet->fetch();

    jsonResponse(true, 'تم تحديث موقع ونقاط سيارة القسم بنجاح | Car position & points updated', $dept);
}

// 7. Toggle Grand Finale Surprise ("الونس")
if ($action === 'toggle_finale_surprise') {
    $reveal = !empty($input['reveal']) ? '1' : '0';
    $stmt = $pdo->prepare("INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES ('finale_revealed', ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
    $stmt->execute([$reveal]);

    jsonResponse(true, $reveal === '1' ? 'تم تفعيل وإظهار مفاجأة الونس الكبرى لجميع المتسابقين! 🎉' : 'تم إخفاء مفاجأة الونس', ['finale_revealed' => ($reveal === '1')]);
}

// 8. Update Finale Surprise Content
if ($action === 'update_finale_content') {
    $titleAr = trim($input['title_ar'] ?? '');
    $titleEn = trim($input['title_en'] ?? '');
    $msgAr = trim($input['msg_ar'] ?? '');
    $msgEn = trim($input['msg_en'] ?? '');

    $stmt = $pdo->prepare("INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
    $stmt->execute(['finale_surprise_title_ar', $titleAr]);
    $stmt->execute(['finale_surprise_title_en', $titleEn]);
    $stmt->execute(['finale_surprise_message_ar', $msgAr]);
    $stmt->execute(['finale_surprise_message_en', $msgEn]);

    jsonResponse(true, 'تم حفظ نصوص ورسالة مفاجأة الونس الختامية بنجاح | Finale surprise content saved');
}

// 9. List Users Directory
if ($action === 'list_users') {
    $stmt = $pdo->query("
        SELECT u.*, d.name_en AS dept_name_en, d.name_ar AS dept_name_ar, d.code AS dept_code, d.color AS dept_color,
               (SELECT COUNT(*) FROM `quiz_attempts` qa WHERE qa.user_id = u.id) AS quiz_attempts_count,
               (SELECT COUNT(*) FROM `photo_submissions` ps WHERE ps.user_id = u.id) AS photo_count
        FROM `users` u
        JOIN `departments` d ON u.department_id = d.id
        ORDER BY u.id DESC
    ");
    $users = $stmt->fetchAll();
    jsonResponse(true, 'Users list', $users);
}

// 10. Reset Race / Database Scores
if ($action === 'reset_race') {
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("UPDATE `departments` SET `position` = 0, `total_points` = 0");
        $pdo->exec("DELETE FROM `quiz_attempts`");
        $pdo->exec("DELETE FROM `photo_submissions`");
        $pdo->exec("DELETE FROM `team_activity_submissions`");
        $pdo->exec("DELETE FROM `weekly_scores`");
        $pdo->exec("UPDATE `system_settings` SET `setting_value` = '0' WHERE `setting_key` = 'finale_revealed'");
        $pdo->exec("UPDATE `system_settings` SET `setting_value` = '1' WHERE `setting_key` = 'active_week_id'");
        $pdo->exec("UPDATE `weeks` SET `is_active` = (id = 1), `is_completed` = 0");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        jsonResponse(true, 'تمت إعادة تعيين السباق بالكامل وتصفير جميع النقاط والمسافات بنجاح! 🔄');
    } catch (Throwable $e) {
        jsonResponse(false, 'خطأ أثناء إعادة تعيين السباق: ' . $e->getMessage());
    }
}

jsonResponse(false, 'Invalid Admin Action');
