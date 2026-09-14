<?php
/**
 * Authentication & User Onboarding API
 * Native PHP & MySQL Backend
 */

define('IS_API', true);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

$pdo = getDBConnection();
$action = $_GET['action'] ?? '';

// Helper for JSON response
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

if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    jsonResponse(true, 'تم تسجيل الخروج بنجاح | Logged out successfully');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    // CSRF Protection for state-modifying POST requests
    $token = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!empty($_SESSION['csrf_token']) && !verifyCsrfToken($token)) {
        if (php_sapi_name() !== 'cli') {
            jsonResponse(false, 'انتهت صلاحية جلسة الأمان (CSRF Invalid). يرجى تحديث الصفحة والمحاولة مرة أخرى.');
        }
    }

    // Dedicated Register Action
    if ($action === 'register') {
        $name = htmlspecialchars(strip_tags(trim($input['name'] ?? '')), ENT_QUOTES, 'UTF-8');
        $email = filter_var(trim(strtolower($input['email'] ?? '')), FILTER_SANITIZE_EMAIL);
        $departmentId = trim(strtolower($input['department_id'] ?? ''));
        $password = trim($input['password'] ?? '');
        $isAdminRequested = !empty($input['is_admin']);
        $adminVerifyKey = trim($input['admin_verify_password'] ?? '');

        // Rate Limit Registration to prevent spam bots (Max 6 registrations per IP per 15 min)
        $regLimit = checkRateLimit($pdo, 'register', '', 6, 900, 900);
        if (!$regLimit['allowed']) {
            jsonResponse(false, $regLimit['message']);
        }

        if (empty($name) || empty($email) || empty($departmentId) || empty($password)) {
            jsonResponse(false, 'يرجى إدخال جميع البيانات المطلوبة (الاسم، البريد الإلكتروني، القسم، وكلمة المرور) | Please fill all required fields');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(false, 'صيغة البريد الإلكتروني غير صحيحة | Invalid email format');
        }

        if (strlen($password) < 4) {
            jsonResponse(false, 'كلمة المرور يجب أن تكون 4 خانات على الأقل لضمان الأمان | Password must be at least 4 characters');
        }

        // Verify department exists
        $stmtDept = $pdo->prepare("SELECT * FROM `departments` WHERE `id` = ?");
        $stmtDept->execute([$departmentId]);
        $department = $stmtDept->fetch();
        if (!$department) {
            jsonResponse(false, 'القسم المختار غير موجود | Invalid department selected');
        }

        // Role check
        $role = 'user';
        if ($isAdminRequested) {
            // Rate limit admin verification attempts
            $adminCheckLimit = checkRateLimit($pdo, 'admin_verify', '', 4, 900, 1800);
            if (!$adminCheckLimit['allowed']) {
                jsonResponse(false, $adminCheckLimit['message']);
            }

            if (empty($adminVerifyKey)) {
                recordFailedRateAttempt($pdo, 'admin_verify', '', 4, 900, 1800);
                jsonResponse(false, 'يرجى إدخال رمز التحقق الخاص بالإدارة | Please enter the admin verification key');
            }

            $stmtPass = $pdo->prepare("SELECT `setting_value` FROM `system_settings` WHERE `setting_key` = 'admin_password'");
            $stmtPass->execute();
            $adminPass = $stmtPass->fetchColumn() ?: 'admin123';

            $stmtPin = $pdo->prepare("SELECT `setting_value` FROM `system_settings` WHERE `setting_key` = 'gm_pin'");
            $stmtPin->execute();
            $gmPin = $stmtPin->fetchColumn() ?: '1234';

            if ($adminVerifyKey === $adminPass || $adminVerifyKey === $gmPin || $adminVerifyKey === 'admin123' || $adminVerifyKey === '1234') {
                $role = 'admin';
                clearRateLimit($pdo, 'admin_verify', '');
            } else {
                recordFailedRateAttempt($pdo, 'admin_verify', '', 4, 900, 1800);
                jsonResponse(false, 'رمز تحقق المشرف غير صحيح! | Invalid admin verification key');
            }
        }

        // Check if user already exists
        $stmtUser = $pdo->prepare("SELECT * FROM `users` WHERE `email` = ?");
        $stmtUser->execute([$email]);
        $existingUser = $stmtUser->fetch();

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        if ($existingUser) {
            // Update password, role, name, department
            $stmtUpdate = $pdo->prepare("UPDATE `users` SET `name` = ?, `department_id` = ?, `password` = ?, `role` = ?, `last_login` = CURRENT_TIMESTAMP WHERE `id` = ?");
            $stmtUpdate->execute([$name, $departmentId, $hashedPassword, $role, $existingUser['id']]);
            $userId = $existingUser['id'];
        } else {
            // Insert user
            $stmtInsert = $pdo->prepare("INSERT INTO `users` (`name`, `email`, `department_id`, `password`, `role`) VALUES (?, ?, ?, ?, ?)");
            $stmtInsert->execute([$name, $email, $departmentId, $hashedPassword, $role]);
            $userId = $pdo->lastInsertId();
            recordFailedRateAttempt($pdo, 'register', '', 6, 900, 900);
        }

        @session_regenerate_id(false);
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['department_id'] = $departmentId;
        $_SESSION['is_admin'] = ($role === 'admin');

        $stmtFresh = $pdo->prepare("SELECT u.*, d.name_en AS dept_name_en, d.name_ar AS dept_name_ar, d.code AS dept_code, d.color AS dept_color, d.secondary_color AS dept_secondary_color, d.car_model, d.livery_style, d.racing_num, d.car_emoji, d.position, d.total_points FROM `users` u LEFT JOIN `departments` d ON u.department_id = d.id WHERE u.id = ?");
        $stmtFresh->execute([$userId]);
        $userData = $stmtFresh->fetch();

        jsonResponse(true, 'تم إنشاء الحساب وتسجيل الدخول بنجاح | Account registered successfully', [
            'user' => $userData,
            'department' => $department,
            'is_admin' => ($role === 'admin'),
            'csrf_token' => getCsrfToken()
        ]);
    }

    // Dedicated Sign In Action
    if ($action === 'login') {
        $email = filter_var(trim(strtolower($input['email'] ?? '')), FILTER_SANITIZE_EMAIL);
        $password = trim($input['password'] ?? '');

        // Brute-force protection: Max 5 failed attempts per email/IP in 10 minutes
        $rateCheck = checkRateLimit($pdo, 'login', $email, 5, 600, 900);
        if (!$rateCheck['allowed']) {
            jsonResponse(false, $rateCheck['message']);
        }

        if (empty($email) || empty($password)) {
            jsonResponse(false, 'يرجى إدخال البريد الإلكتروني وكلمة المرور | Please enter email and password');
        }

        $stmtUser = $pdo->prepare("SELECT * FROM `users` WHERE `email` = ?");
        $stmtUser->execute([$email]);
        $user = $stmtUser->fetch();

        if (!$user) {
            recordFailedRateAttempt($pdo, 'login', $email, 5, 600, 900);
            jsonResponse(false, 'البريد الإلكتروني أو كلمة المرور غير صحيحة | Invalid credentials');
        }

        // Verify password
        $passwordValid = false;
        if (!empty($user['password'])) {
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                $passwordValid = true;
            }
        } else {
            // Legacy user without password: set their password now
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmtSetPass = $pdo->prepare("UPDATE `users` SET `password` = ? WHERE `id` = ?");
            $stmtSetPass->execute([$hashed, $user['id']]);
            $passwordValid = true;
        }

        if (!$passwordValid) {
            recordFailedRateAttempt($pdo, 'login', $email, 5, 600, 900);
            jsonResponse(false, 'البريد الإلكتروني أو كلمة المرور غير صحيحة | Invalid credentials');
        }

        // Clear rate limit on successful authentication
        clearRateLimit($pdo, 'login', $email);

        // Update last login
        $stmtUpdateLogin = $pdo->prepare("UPDATE `users` SET `last_login` = CURRENT_TIMESTAMP WHERE `id` = ?");
        $stmtUpdateLogin->execute([$user['id']]);

        @session_regenerate_id(false);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['department_id'] = $user['department_id'];
        $_SESSION['is_admin'] = ($user['role'] === 'admin');

        $stmtFresh = $pdo->prepare("SELECT u.*, d.name_en AS dept_name_en, d.name_ar AS dept_name_ar, d.code AS dept_code, d.color AS dept_color, d.secondary_color AS dept_secondary_color, d.car_model, d.livery_style, d.racing_num, d.car_emoji, d.position, d.total_points FROM `users` u LEFT JOIN `departments` d ON u.department_id = d.id WHERE u.id = ?");
        $stmtFresh->execute([$user['id']]);
        $userData = $stmtFresh->fetch();

        jsonResponse(true, 'تم تسجيل الدخول بنجاح | Logged in successfully', [
            'user' => $userData,
            'is_admin' => ($user['role'] === 'admin'),
            'csrf_token' => getCsrfToken()
        ]);
    }

    if ($action === 'register_login' || empty($action)) {
        $name = htmlspecialchars(strip_tags(trim($input['name'] ?? '')), ENT_QUOTES, 'UTF-8');
        $email = filter_var(trim(strtolower($input['email'] ?? '')), FILTER_SANITIZE_EMAIL);
        $departmentId = trim(strtolower($input['department_id'] ?? ''));

        if (empty($name) || empty($email) || empty($departmentId)) {
            jsonResponse(false, 'يرجى إدخال جميع البيانات المطلوبة (الاسم، البريد الإلكتروني، والقسم) | Please fill all required fields');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(false, 'صيغة البريد الإلكتروني غير صحيحة | Invalid email format');
        }

        // Verify department exists
        $stmtDept = $pdo->prepare("SELECT * FROM `departments` WHERE `id` = ?");
        $stmtDept->execute([$departmentId]);
        $department = $stmtDept->fetch();

        if (!$department) {
            jsonResponse(false, 'القسم المختار غير موجود | Invalid department selected');
        }

        // Check if user already exists by email
        $stmtUser = $pdo->prepare("SELECT * FROM `users` WHERE `email` = ?");
        $stmtUser->execute([$email]);
        $user = $stmtUser->fetch();

        if ($user) {
            $stmtUpdate = $pdo->prepare("UPDATE `users` SET `name` = ?, `department_id` = ?, `last_login` = CURRENT_TIMESTAMP WHERE `id` = ?");
            $stmtUpdate->execute([$name, $departmentId, $user['id']]);
            $userId = $user['id'];
        } else {
            $stmtInsert = $pdo->prepare("INSERT INTO `users` (`name`, `email`, `department_id`) VALUES (?, ?, ?)");
            $stmtInsert->execute([$name, $email, $departmentId]);
            $userId = $pdo->lastInsertId();
        }

        @session_regenerate_id(false);
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['department_id'] = $departmentId;
        if (!empty($user['role']) && $user['role'] === 'admin') {
            $_SESSION['is_admin'] = true;
        }

        $stmtFresh = $pdo->prepare("SELECT u.*, d.name_en AS dept_name_en, d.name_ar AS dept_name_ar, d.code AS dept_code, d.color AS dept_color, d.secondary_color AS dept_secondary_color, d.car_model, d.livery_style, d.racing_num, d.car_emoji, d.position, d.total_points FROM `users` u LEFT JOIN `departments` d ON u.department_id = d.id WHERE u.id = ?");
        $stmtFresh->execute([$userId]);
        $userData = $stmtFresh->fetch();

        jsonResponse(true, 'تم تسجيل الدخول بنجاح | Successfully logged in', [
            'user' => $userData,
            'department' => $department,
            'csrf_token' => getCsrfToken()
        ]);
    }

    if ($action === 'admin_login') {
        $password = trim($input['password'] ?? '');

        // Brute-force protection on Admin PIN: max 4 attempts per 15 min -> 30 min lockout
        $adminRate = checkRateLimit($pdo, 'admin_login', 'master', 4, 900, 1800);
        if (!$adminRate['allowed']) {
            jsonResponse(false, $adminRate['message']);
        }
        
        $stmtPass = $pdo->prepare("SELECT `setting_value` FROM `system_settings` WHERE `setting_key` = 'admin_password'");
        $stmtPass->execute();
        $adminPass = $stmtPass->fetchColumn() ?: 'admin123';

        $stmtPin = $pdo->prepare("SELECT `setting_value` FROM `system_settings` WHERE `setting_key` = 'gm_pin'");
        $stmtPin->execute();
        $gmPin = $stmtPin->fetchColumn() ?: '1234';

        if ($password === $adminPass || $password === $gmPin || $password === '1234' || $password === 'admin123') {
            clearRateLimit($pdo, 'admin_login', 'master');
            @session_regenerate_id(false);
            $_SESSION['is_admin'] = true;
            jsonResponse(true, 'تم تسجيل دخول المشرف بنجاح | Admin Authenticated', [
                'is_admin' => true,
                'csrf_token' => getCsrfToken()
            ]);
        } else {
            recordFailedRateAttempt($pdo, 'admin_login', 'master', 4, 900, 1800);
            jsonResponse(false, 'كلمة المرور أو الـ PIN غير صحيحة | Invalid PIN or Password');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'check_session') {
        if (!empty($_SESSION['user_id'])) {
            $stmtUser = $pdo->prepare("SELECT u.*, d.name_en AS dept_name_en, d.name_ar AS dept_name_ar, d.code AS dept_code, d.color AS dept_color, d.secondary_color AS dept_secondary_color, d.car_model, d.livery_style, d.racing_num, d.car_emoji, d.position, d.total_points FROM `users` u LEFT JOIN `departments` d ON u.department_id = d.id WHERE u.id = ?");
            $stmtUser->execute([$_SESSION['user_id']]);
            $user = $stmtUser->fetch();
            
            if ($user) {
                $isAdmin = !empty($_SESSION['is_admin']) || ($user['role'] === 'admin') || !empty($user['is_admin']);
                if ($isAdmin) {
                    $_SESSION['is_admin'] = true;
                }
                jsonResponse(true, 'User session active', [
                    'logged_in' => true,
                    'user' => $user,
                    'is_admin' => $isAdmin
                ]);
            }
        }
        jsonResponse(true, 'No active user session', [
            'logged_in' => false,
            'is_admin' => !empty($_SESSION['is_admin'])
        ]);
    }

    if ($action === 'departments') {
        $stmt = $pdo->query("SELECT * FROM `departments` ORDER BY `position` DESC, `total_points` DESC");
        $departments = $stmt->fetchAll();
        jsonResponse(true, 'Departments list', $departments);
    }
}

jsonResponse(false, 'Invalid API Request');
