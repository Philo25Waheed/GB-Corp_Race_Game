<?php
/**
 * Photo Challenge API - Robust Upload & Gallery Engine
 * GB Corp Summer Road Trip
 */

// Enable clean error handling
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

$pdo = getDBConnection();
$action = $_GET['action'] ?? 'upload';

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

// 1. Upload Photo for Photo Challenge (Hardened & Protected)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'upload' || empty($action))) {
    try {
        // CSRF Protection
        $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (!empty($_SESSION['csrf_token']) && !verifyCsrfToken($token)) {
            if (php_sapi_name() !== 'cli') {
                jsonResponse(false, 'انتهت صلاحية جلسة الأمان (CSRF Token Mismatch).');
            }
        }

        // Security: Strict Authenticated Session Required (No anonymous point farming)
        $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
        if ($userId <= 0) {
            jsonResponse(false, 'يرجى تسجيل الدخول أولاً لرفع صورتك والمشاركة في التحدي | Please sign in first');
        }

        // Rate Limit Photo Uploads (Allows up to 50 uploads per 10 minutes)
        $rateLimit = checkRateLimit($pdo, 'upload_photo', (string)$userId, 50, 600, 900);
        if (!$rateLimit['allowed']) {
            jsonResponse(false, $rateLimit['message']);
        }

        // Verify User and retrieve verified Department
        $stmtUserCheck = $pdo->prepare("SELECT id, name, email, department_id FROM `users` WHERE `id` = ?");
        $stmtUserCheck->execute([$userId]);
        $userRow = $stmtUserCheck->fetch();
        if (!$userRow) {
            jsonResponse(false, 'المستخدم غير مسجل بقاعدة البيانات | User not found');
        }
        $departmentId = $userRow['department_id'];

        $caption = htmlspecialchars(strip_tags(trim($_POST['caption'] ?? '')), ENT_QUOTES, 'UTF-8');
        $weekId = isset($_POST['week_id']) ? (int)$_POST['week_id'] : null;

        // Auto-detect active week if not passed
        if (!$weekId || $weekId <= 0) {
            $stmtActive = $pdo->query("SELECT `setting_value` FROM `system_settings` WHERE `setting_key` = 'active_week_id'");
            $weekId = (int)($stmtActive->fetchColumn() ?: 2);
        }

        // Allow multiple / unlimited photo uploads per user (e.g. Photo Challenge & Team Activity)
        // Rate-limit protected to prevent denial of service

        // Validate File Upload
        if (!isset($_FILES['photo'])) {
            jsonResponse(false, 'لم يتم إرسال أي ملف صورة | No image file was uploaded');
        }

        $file = $_FILES['photo'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE   => 'حجم الصورة أكبر من المسموح به في إعدادات السيرفر (upload_max_filesize)',
                UPLOAD_ERR_FORM_SIZE  => 'حجم الصورة أكبر من الحد المسموح به في النموذج',
                UPLOAD_ERR_PARTIAL    => 'تم رفع جزء من الصورة فقط، يرجى المحاولة مرة أخرى',
                UPLOAD_ERR_NO_FILE    => 'لم يتم اختيار أي ملف للرفع',
                UPLOAD_ERR_NO_TMP_DIR => 'مجلد الملفات المؤقتة غير متوفر على السيرفر',
                UPLOAD_ERR_CANT_WRITE => 'فشل كتابة الصورة على القرص',
                UPLOAD_ERR_EXTENSION  => 'تم إيقاف الرفع بواسطة أحد إضافات PHP'
            ];
            $errMsg = $uploadErrors[$file['error']] ?? 'خطأ أثناء رفع الصورة (كود: ' . $file['error'] . ')';
            jsonResponse(false, $errMsg);
        }

        $maxFileSize = 10 * 1024 * 1024; // 10MB Max
        if ($file['size'] > $maxFileSize) {
            jsonResponse(false, 'حجم الصورة كبير جداً (الحد الأقصى 10 ميجابايت) | File size exceeds 10MB');
        }

        // Anti-RCE & Polyglot Defense: Deep File Verification
        $originalName = strtolower($file['name']);
        
        // Disallow dangerous extensions anywhere in the filename (prevent shell.php.jpg)
        $dangerousExts = ['php', 'phtml', 'phar', 'cgi', 'pl', 'exe', 'sh', 'py', 'asp', 'aspx', 'shtml', 'svg', 'js', 'html', 'htm'];
        foreach ($dangerousExts as $badExt) {
            if (strpos($originalName, '.' . $badExt) !== false) {
                jsonResponse(false, 'نوع الملف غير مسموح به لأسباب أمنية | Security violation: invalid file extension');
            }
        }

        // Detect extension from original file name
        $originalExt = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $validExts = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($originalExt, $validExts)) {
            jsonResponse(false, 'صيغة الصورة غير مدعومة (يرجى رفع صور JPG أو PNG أو WEBP فقط) | Unsupported image format');
        }

        // Structural Image Dimension Verification via GD
        $imageInfo = @getimagesize($file['tmp_name']);
        if (!$imageInfo || empty($imageInfo[0]) || empty($imageInfo[1]) || $imageInfo[0] < 10 || $imageInfo[1] < 10) {
            jsonResponse(false, 'الملف المرفوع ليس صورة صالحة أو تالف | Corrupted image file');
        }

        // Deep MIME inspection using Fileinfo with fallback to GD getimagesize MIME
        $detectedMime = '';
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detectedMime = @finfo_file($finfo, $file['tmp_name']) ?: '';
                @finfo_close($finfo);
            }
        }
        if (empty($detectedMime) && !empty($imageInfo['mime'])) {
            $detectedMime = $imageInfo['mime'];
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($detectedMime, $allowedMimes)) {
            jsonResponse(false, 'محتوى الملف غير صالح أو تم التلاعب بصيغته | Invalid image MIME content');
        }

        // Prepare Target Directory
        $uploadDir = __DIR__ . '/../uploads/photos/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        // Cryptographically Secure Randomized Filename
        $extension = ($originalExt === 'jpeg') ? 'jpg' : $originalExt;
        $fileName = 'photo_w' . $weekId . '_' . bin2hex(random_bytes(16)) . '.' . $extension;
        $targetPath = $uploadDir . $fileName;
        $relativeDbPath = 'uploads/photos/' . $fileName;

        if (!@move_uploaded_file($file['tmp_name'], $targetPath)) {
            if (!@copy($file['tmp_name'], $targetPath)) {
                jsonResponse(false, 'فشل حفظ الصورة على السيرفر | Failed to save file to uploads folder');
            }
        }

        $finalUserId = $userId;

        // Auto-migrate challenge_type column in photo_submissions if missing
        try {
            $stmtCol = $pdo->query("SHOW COLUMNS FROM `photo_submissions` LIKE 'challenge_type'");
            if ($stmtCol && $stmtCol->rowCount() === 0) {
                $pdo->exec("ALTER TABLE `photo_submissions` ADD COLUMN `challenge_type` VARCHAR(50) NOT NULL DEFAULT 'photo_challenge' AFTER `week_id`");
            }
        } catch (Exception $e) {}

        // Determine submission type and points awarded
        $submissionType = trim($_POST['submission_type'] ?? $_POST['challenge_type'] ?? 'photo_challenge');
        $isTeamActivity = ($submissionType === 'team_activity');

        if ($isTeamActivity) {
            $pointsAwarded = 30;
            $steps = 3; // +3 steps (30 miles)
            $challengeType = 'team_activity';
            $successMsg = 'تم رفع صورة النشاط الجماعي بنجاح وحصد 30 نقطة وميل لسيارة إداراتك! 👥🎉 | Team Activity photo uploaded (+30 PTS)!';
        } else {
            $pointsAwarded = 15;
            $steps = 2; // +2 steps (20 miles)
            $challengeType = 'photo_challenge';
            $successMsg = 'تم رفع صورة تحدي التصوير بنجاح وحصد 15 نقطة وميل لسيارة إداراتك! 📷🎉 | Photo Challenge uploaded (+15 PTS)!';
        }

        // Save record into photo_submissions
        try {
            $stmtSub = $pdo->prepare("
                INSERT INTO `photo_submissions` (`user_id`, `department_id`, `week_id`, `challenge_type`, `photo_path`, `caption`, `status`, `points_awarded`)
                VALUES (?, ?, ?, ?, ?, ?, 'approved', ?)
            ");
            $stmtSub->execute([$finalUserId, $departmentId, $weekId, $challengeType, $relativeDbPath, $caption, $pointsAwarded]);
        } catch (Exception $e) {
            // Fallback if challenge_type column could not be added
            $stmtSub = $pdo->prepare("
                INSERT INTO `photo_submissions` (`user_id`, `department_id`, `week_id`, `photo_path`, `caption`, `status`, `points_awarded`)
                VALUES (?, ?, ?, ?, ?, 'approved', ?)
            ");
            $stmtSub->execute([$finalUserId, $departmentId, $weekId, $relativeDbPath, $caption, $pointsAwarded]);
        }
        $submissionId = $pdo->lastInsertId();

        // If team activity, also log into team_activity_submissions for admin synchronization
        if ($isTeamActivity) {
            try {
                $stmtTAS = $pdo->prepare("
                    INSERT INTO `team_activity_submissions` (`department_id`, `week_id`, `activity_name`, `notes`, `points_awarded`, `awarded_by`)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmtTAS->execute([
                    $departmentId,
                    $weekId,
                    'Team Activity (Photo Upload)',
                    $caption ?: 'Team photo submission',
                    $pointsAwarded,
                    $userRow['name'] ?? 'Team Member'
                ]);
            } catch (Exception $ex) {}
        }

        // Award Points & Advance Department Car
        $stmtRaceLen = $pdo->query("SELECT `setting_value` FROM `system_settings` WHERE `setting_key` = 'race_length'");
        $raceLen = (int)($stmtRaceLen->fetchColumn() ?: 15);

        $stmtDept = $pdo->prepare("
            UPDATE `departments` 
            SET `total_points` = `total_points` + ?,
                `position` = LEAST(?, `position` + ?)
            WHERE `id` = ?
        ");
        $stmtDept->execute([$pointsAwarded, $raceLen, $steps, $departmentId]);

        // Update weekly scores
        $stmtWS = $pdo->prepare("
            INSERT INTO `weekly_scores` (`week_id`, `department_id`, `score`)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE `score` = `score` + VALUES(`score`)
        ");
        $stmtWS->execute([$weekId, $departmentId, $pointsAwarded]);

        // Get fresh department stats
        $stmtDeptFresh = $pdo->prepare("SELECT * FROM `departments` WHERE `id` = ?");
        $stmtDeptFresh->execute([$departmentId]);
        $freshDept = $stmtDeptFresh->fetch();

        jsonResponse(true, $successMsg, [
            'submission_id' => $submissionId,
            'photo_url' => $relativeDbPath,
            'caption' => $caption,
            'points_awarded' => $pointsAwarded,
            'challenge_type' => $challengeType,
            'department' => $freshDept
        ]);
    } catch (Throwable $e) {
        jsonResponse(false, 'حدث خطأ أثناء معالجة الصورة: ' . $e->getMessage());
    }
}

// 2. List Photos for Active Week / Department Gallery
if ($_SERVER['REQUEST_METHOD'] === 'GET' || $action === 'list') {
    try {
        $weekId = isset($_GET['week_id']) ? (int)$_GET['week_id'] : null;
        $departmentId = isset($_GET['department_id']) ? trim($_GET['department_id']) : null;

        $sql = "
            SELECT ps.*, u.name AS user_name, u.email AS user_email,
                   d.name_en AS dept_name_en, d.name_ar AS dept_name_ar, d.code AS dept_code, d.color AS dept_color, d.car_emoji
            FROM `photo_submissions` ps
            JOIN `users` u ON ps.user_id = u.id
            JOIN `departments` d ON ps.department_id = d.id
            WHERE 1=1
        ";
        $params = [];

        if ($weekId) {
            $sql .= " AND ps.week_id = ?";
            $params[] = $weekId;
        }
        if ($departmentId) {
            $sql .= " AND ps.department_id = ?";
            $params[] = $departmentId;
        }

        $sql .= " ORDER BY ps.id DESC LIMIT 50";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $photos = $stmt->fetchAll();

        jsonResponse(true, 'Photos list', $photos);
    } catch (Throwable $e) {
        jsonResponse(false, 'Error fetching photos: ' . $e->getMessage());
    }
}

jsonResponse(false, 'Invalid Action');
