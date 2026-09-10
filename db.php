<?php
/**
 * Database Configuration & Auto-Migration System
 * Summer Road Trip - Corporate Team Race
 */

// Direct Access Blocker (Defense in Depth)
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME']) && php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Access Denied: Direct execution not permitted.');
}

// ========================================================================
// SMART ENVIRONMENT AUTO-DETECTION (Localhost vs InfinityFree Live Server)
// ========================================================================
$httpHost = $_SERVER['HTTP_HOST'] ?? '';
$hostOnly = strtolower(explode(':', $httpHost)[0] ?? '');

$isLocalhost = in_array($hostOnly, ['127.0.0.1', 'localhost', '::1', ''])
    || str_starts_with($hostOnly, '192.168.')
    || str_starts_with($hostOnly, '10.')
    || str_starts_with($hostOnly, '172.')
    || (php_sapi_name() === 'cli' && empty(getenv('LIVE_ENV')));

if ($isLocalhost) {
    // 💻 LOCALHOST / XAMPP SETTINGS
    define('DB_HOST', '127.0.0.1');
    define('DB_PORT', '3306');
    define('DB_NAME', 'race_game_db');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
     // 🌐 INFINITYFREE LIVE HOSTING (gb-crop.ct.ws)
    define('DB_HOST', 'sql103.infinityfree.com');
    define('DB_PORT', '3306');
    define('DB_NAME', 'if0_42882264_race_db');   // اسم قاعدتك على InfinityFree
    define('DB_USER', 'if0_42882264');           // حساب الاستضافة
    define('DB_PASS', 'Sx415lrC474');  // كلمة سر حساب الاستضافة (vPanel Password)
}

// Set timezone and session configuration with robust compatibility for Localhost and Live Hosting
if (session_status() === PHP_SESSION_NONE) {
    // Only set secure flag if the actual client connection is genuinely HTTPS
    // (Never guess from reverse-proxy headers which can break http:// sessions on free hosts)
    $isActualHttps = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string)(86400 * 30));

    $cookieParams = [
        'lifetime' => 86400 * 30, // 30-day persistent session so users aren't prematurely logged out
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    if ($isActualHttps) {
        $cookieParams['secure'] = true;
    }

    session_set_cookie_params($cookieParams);
    session_start();
}

// Security & Anti-Cache Headers via PHP
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

date_default_timezone_set('Africa/Cairo');

function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $configsToTry = [
        ['host' => DB_HOST, 'port' => DB_PORT, 'dbname' => DB_NAME, 'user' => DB_USER, 'pass' => DB_PASS],
        ['host' => '127.0.0.1', 'port' => '3306', 'dbname' => 'race_game_db', 'user' => 'root', 'pass' => ''],
        ['host' => 'localhost', 'port' => '3306', 'dbname' => 'race_game_db', 'user' => 'root', 'pass' => '']
    ];

    $lastException = null;

    foreach ($configsToTry as $cfg) {
        try {
            $dsn = "mysql:host=" . $cfg['host'] . ";port=" . $cfg['port'] . ";dbname=" . $cfg['dbname'] . ";charset=utf8mb4";
            $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                PDO::ATTR_TIMEOUT => 3
            ]);

            // Auto-run schema installer/migrator
            initializeDatabaseTables($pdo);
            return $pdo;
        } catch (PDOException $e) {
            $lastException = $e;
            // If database does not exist on local host, try creating it
            if ($cfg['host'] === '127.0.0.1' || $cfg['host'] === 'localhost') {
                try {
                    $dsnInit = "mysql:host=" . $cfg['host'] . ";port=" . $cfg['port'] . ";charset=utf8mb4";
                    $pdoInit = new PDO($dsnInit, $cfg['user'], $cfg['pass'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                    ]);
                    $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `" . $cfg['dbname'] . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

                    $dsn = "mysql:host=" . $cfg['host'] . ";port=" . $cfg['port'] . ";dbname=" . $cfg['dbname'] . ";charset=utf8mb4";
                    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                    ]);
                    initializeDatabaseTables($pdo);
                    return $pdo;
                } catch (PDOException $e2) {
                    $lastException = $e2;
                }
            }
        }
    }

    $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? ($_SERVER['SCRIPT_NAME'] ?? ''));
    $reqUri = str_replace('\\', '/', $_SERVER['REQUEST_URI'] ?? '');
    $isApi = (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
        || (strpos($reqUri, '/api/') !== false)
        || (strpos($scriptPath, '/api/') !== false)
        || (strpos($scriptPath, 'api/') !== false);

    if ($isApi) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'تعذر الاتصال بقاعدة البيانات. | Database Connection Error: ' . ($lastException ? $lastException->getMessage() : 'Unknown error')
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(500);
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>خطأ قاعدة البيانات</title></head><body style="background:#0f172a; color:#f8fafc; font-family:sans-serif; padding:40px; text-align:center;">';
    echo '<div style="max-width:600px; margin:0 auto; background:#1e293b; padding:30px; border-radius:12px; border:1px solid #ef4444; box-shadow:0 10px 25px rgba(0,0,0,0.5);">';
    echo '<h1 style="color:#ef4444; font-size:22px; margin-top:0;">⚠️ تعذر الاتصال بقاعدة البيانات</h1>';
    echo '<div style="background:#0f172a; text-align:left; direction:ltr; padding:15px; border-radius:8px; border:1px solid #334155; font-family:monospace; color:#fca5a5; font-size:13px; overflow-x:auto; word-break:break-all;">';
    echo htmlspecialchars($lastException ? $lastException->getMessage() : 'Unknown Database Error');
    echo '</div>';
    echo '<div style="text-align:right; margin-top:20px; font-size:14px; color:#cbd5e1;">';
    echo '<p><strong>🛠️ خطوات الحل على InfinityFree:</strong></p>';
    echo '<ol style="line-height:1.8;">';
    echo '<li>تأكد من <strong>MySQL Hostname</strong> من لوحة vPanel على اليمين (مثال: <code>sql302.infinityfree.com</code>).</li>';
    echo '<li>تأكد أن اسم القاعدة هو <code>' . htmlspecialchars(DB_NAME) . '</code> وأنك رفعت ملف <code>race_game_db.sql</code> داخل <strong>phpMyAdmin</strong>.</li>';
    echo '<li>تأكد من كلمة مرور الحساب (Account / vPanel Password).</li>';
    echo '</ol>';
    echo '</div>';
    echo '</div></body></html>';
    exit;
}

function initializeDatabaseTables(PDO $pdo) {
    // 1. Departments Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `departments` (
        `id` VARCHAR(32) PRIMARY KEY,
        `name_en` VARCHAR(100) NOT NULL,
        `name_ar` VARCHAR(100) NOT NULL,
        `code` VARCHAR(20) NOT NULL UNIQUE,
        `category` VARCHAR(50) NOT NULL DEFAULT 'bu',
        `password` VARCHAR(50) NOT NULL DEFAULT '1234',
        `color` VARCHAR(20) NOT NULL DEFAULT '#38bdf8',
        `car_emoji` VARCHAR(20) NOT NULL DEFAULT '🏎️',
        `position` INT NOT NULL DEFAULT 0,
        `total_points` INT NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Auto-migrate category column if missing
    try {
        $stmtCheckCat = $pdo->query("SHOW COLUMNS FROM `departments` LIKE 'category'");
        if ($stmtCheckCat->rowCount() === 0) {
            $pdo->exec("ALTER TABLE `departments` ADD COLUMN `category` VARCHAR(50) NOT NULL DEFAULT 'bu' AFTER `code`");
        }
        $cols = $pdo->query("SHOW COLUMNS FROM `departments`")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('secondary_color', $cols)) {
            $pdo->exec("ALTER TABLE `departments` ADD COLUMN `secondary_color` VARCHAR(20) NOT NULL DEFAULT '#d9d8d6' AFTER `color`");
        }
        if (!in_array('car_model', $cols)) {
            $pdo->exec("ALTER TABLE `departments` ADD COLUMN `car_model` VARCHAR(50) NOT NULL DEFAULT 'gt_coupe' AFTER `car_emoji`");
        }
        if (!in_array('livery_style', $cols)) {
            $pdo->exec("ALTER TABLE `departments` ADD COLUMN `livery_style` VARCHAR(50) NOT NULL DEFAULT 'stripes' AFTER `car_model`");
        }
        if (!in_array('racing_num', $cols)) {
            $pdo->exec("ALTER TABLE `departments` ADD COLUMN `racing_num` INT NOT NULL DEFAULT 1 AFTER `livery_style`");
        }
    } catch (Exception $e) {
        // Ignore if already added or error
    }

    // 2. Users Table (Bilingual registration: Name, Email, Department, Password)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(150) NOT NULL,
        `email` VARCHAR(150) NOT NULL UNIQUE,
        `department_id` VARCHAR(32) NOT NULL,
        `password` VARCHAR(255) NULL DEFAULT NULL,
        `role` ENUM('user', 'admin') DEFAULT 'user',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `last_login` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Auto-migrate password and is_admin column if missing
    try {
        $stmtCheckPass = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'password'");
        if ($stmtCheckPass->rowCount() === 0) {
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `password` VARCHAR(255) NULL DEFAULT NULL AFTER `department_id`");
        }
        $stmtCheckAdmin = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'is_admin'");
        if ($stmtCheckAdmin->rowCount() === 0) {
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `is_admin` TINYINT(1) DEFAULT 0 AFTER `role`");
            $pdo->exec("UPDATE `users` SET `is_admin` = 1 WHERE `role` = 'admin'");
        }
    } catch (Exception $e) {
        // Ignore if already added or error
    }

    // 3. Weeks Table (Week 1, Week 2, Week 3 Multi-Challenge Stages)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `weeks` (
        `id` INT PRIMARY KEY,
        `week_number` INT NOT NULL UNIQUE,
        `title_en` VARCHAR(150) NOT NULL,
        `title_ar` VARCHAR(150) NOT NULL,
        `challenge_type` VARCHAR(50) NOT NULL DEFAULT 'multi',
        `description_en` TEXT NULL,
        `description_ar` TEXT NULL,
        `points_reward` INT NOT NULL DEFAULT 50,
        `is_active` TINYINT(1) NOT NULL DEFAULT 0,
        `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    try {
        $pdo->exec("ALTER TABLE `weeks` MODIFY `challenge_type` VARCHAR(50) NOT NULL DEFAULT 'multi'");
    } catch (Exception $e) {}

    // 4. Questions Table for Quiz Weeks
    $pdo->exec("CREATE TABLE IF NOT EXISTS `questions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `week_id` INT NOT NULL,
        `category_en` VARCHAR(100) NOT NULL DEFAULT 'General Knowledge',
        `category_ar` VARCHAR(100) NOT NULL DEFAULT 'معلومات عامة',
        `question_en` TEXT NOT NULL,
        `question_ar` TEXT NOT NULL,
        `option_a_en` VARCHAR(255) NOT NULL,
        `option_a_ar` VARCHAR(255) NOT NULL,
        `option_b_en` VARCHAR(255) NOT NULL,
        `option_b_ar` VARCHAR(255) NOT NULL,
        `option_c_en` VARCHAR(255) NOT NULL,
        `option_c_ar` VARCHAR(255) NOT NULL,
        `option_d_en` VARCHAR(255) NOT NULL,
        `option_d_ar` VARCHAR(255) NOT NULL,
        `correct_option` ENUM('A', 'B', 'C', 'D') NOT NULL,
        `points` INT NOT NULL DEFAULT 10,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`week_id`) REFERENCES `weeks`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 5. Quiz Attempts / User Answers Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `quiz_attempts` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `department_id` VARCHAR(32) NOT NULL,
        `week_id` INT NOT NULL,
        `question_id` INT NOT NULL,
        `selected_option` VARCHAR(16) NOT NULL DEFAULT '',
        `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
        `points_earned` INT NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`week_id`) REFERENCES `weeks`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Migration: Allow status values like 'PENDING', 'FORFEIT', 'TIMEOUT' for anti-cheat
    try {
        $pdo->exec("ALTER TABLE `quiz_attempts` MODIFY `selected_option` VARCHAR(16) NOT NULL DEFAULT ''");
    } catch (Exception $e) {
        // Ignore if already applied
    }

    // 6. Photo Submissions Table (Photo Challenge)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `photo_submissions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `department_id` VARCHAR(32) NOT NULL,
        `week_id` INT NOT NULL,
        `photo_path` VARCHAR(255) NOT NULL,
        `caption` VARCHAR(255) NULL,
        `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
        `points_awarded` INT NOT NULL DEFAULT 15,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`week_id`) REFERENCES `weeks`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 7. Team Activity Submissions & Points
    $pdo->exec("CREATE TABLE IF NOT EXISTS `team_activity_submissions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `department_id` VARCHAR(32) NOT NULL,
        `week_id` INT NOT NULL,
        `activity_name` VARCHAR(150) NOT NULL,
        `notes` TEXT NULL,
        `points_awarded` INT NOT NULL DEFAULT 30,
        `awarded_by` VARCHAR(50) DEFAULT 'Admin',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`week_id`) REFERENCES `weeks`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 8. Weekly Scores & Department Highlights Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `weekly_scores` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `week_id` INT NOT NULL,
        `department_id` VARCHAR(32) NOT NULL,
        `score` INT NOT NULL DEFAULT 0,
        `is_weekly_winner` TINYINT(1) NOT NULL DEFAULT 0,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_week_dept` (`week_id`, `department_id`),
        FOREIGN KEY (`week_id`) REFERENCES `weeks`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 9. System Settings Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `system_settings` (
        `setting_key` VARCHAR(50) PRIMARY KEY,
        `setting_value` TEXT NOT NULL,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 10. Security Rate Limits Table (Brute-Force & Attack Prevention)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `security_rate_limits` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `ip_address` VARCHAR(64) NOT NULL,
        `action_name` VARCHAR(50) NOT NULL,
        `identifier` VARCHAR(150) NOT NULL DEFAULT '',
        `attempts` INT NOT NULL DEFAULT 1,
        `first_attempt_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `last_attempt_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `locked_until` TIMESTAMP NULL DEFAULT NULL,
        INDEX `idx_rate_lookup` (`ip_address`, `action_name`, `identifier`),
        INDEX `idx_lock_lookup` (`locked_until`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Seed default departments if table is empty
    seedDefaultData($pdo);
}

function seedDefaultData(PDO $pdo) {
    // 1. Seed & Synchronize Departments (24 Business Units + 19 Job Families = 43 Dedicated Cars)
    $stmtCheckConfig = $pdo->query("SELECT COUNT(*) FROM `departments` WHERE `racing_num` > 1");
    $isConfigured = ($stmtCheckConfig && $stmtCheckConfig->fetchColumn() >= 40);

    if (!$isConfigured) {
        $depts = [
            // 1. قطاعات الأعمال والوحدات الرئيسية (Business Units - BU)
            ['hr', 'Human Resources', 'قطاع الموارد البشرية', 'BU-HR', 'bu', '1234', '#2b51a4', '#d9d8d6', 'gt_coupe', 'stripes', 1, '🚗'],
            ['it', 'Information Technology', 'قطاع تكنولوجيا المعلومات', 'BU-IT', 'bu', '1234', '#049eda', '#f3f3f3', 'formula', 'velocity', 2, '🏎️'],
            ['finance', 'Finance', 'قطاع الإدارة المالية', 'BU-FIN', 'bu', '1234', '#4062ac', '#dcdcda', 'speedster', 'dual_bars', 3, '🏎️'],
            ['marketing', 'Marketing', 'قطاع التسويق والعلاقات', 'BU-MKT', 'bu', '1234', '#f78c2a', '#2b51a4', 'hypercar', 'arrow', 4, '🏎️'],
            ['operations', 'Operations', 'قطاع العمليات والتشغيل', 'BU-OPS', 'bu', '1234', '#7f8487', '#3bae49', 'aero_fastback', 'side_swoop', 5, '🚗'],
            ['administration', 'Administration', 'الشؤون الإدارية والخدمات العامة', 'BU-ADM', 'bu', '1234', '#8c9093', '#049eda', 'gt_coupe', 'stripes', 6, '🚗'],
            ['procurement', 'Procurement', 'قطاع المشتريات وسلاسل الإمداد', 'BU-PROC', 'bu', '1234', '#119aaa', '#f79740', 'speedster', 'dual_bars', 7, '🏎️'],
            ['legal_loans', 'Legal & Problem Loans', 'الشؤون القانونية والقروض المتعثرة', 'BU-LEG', 'bu', '1234', '#5574b5', '#d9d8d6', 'aero_fastback', 'chevrons', 8, '🚗'],
            ['manufacturing', 'Manufacturing', 'قطاع التصنيع والإنتاج', 'BU-MFG', 'bu', '1234', '#3bae49', '#7f8487', 'hauler_truck', 'heavy_shield', 9, '🚛'],
            ['central_warehousing', 'Central Warehousing', 'المستودعات المركزية', 'BU-WH', 'bu', '1234', '#f79740', '#2b51a4', 'aero_van', 'cargo_bars', 10, '🚐'],
            ['quality_excellence', 'Quality / Business Excellence', 'الجودة والتميز المؤسسي', 'BU-QUAL', 'bu', '1234', '#4eb75b', '#dcdcda', 'hypercar', 'apex_fin', 11, '🏎️'],
            ['digital_transformation', 'Digital Transformation', 'التحول الرقمي والابتكار', 'BU-DIG', 'bu', '1234', '#1da9de', '#f7a454', 'formula', 'cyber_grid', 12, '🏎️'],
            ['data_dept', 'Data', 'إدارة وتحليل البيانات', 'BU-DATA', 'bu', '1234', '#28a4b0', '#eaeef7', 'streamliner', 'telemetry', 13, '⚡'],
            ['projects_bu', 'Projects', 'إدارة المشاريع الاستراتيجية', 'BU-PMO', 'bu', '1234', '#f7a454', '#119aaa', 'hypercar', 'arrow', 14, '🏎️'],
            ['internal_audit', 'Internal Audit', 'المراجعة والتدقيق الداخلي', 'BU-AUD', 'bu', '1234', '#989da0', '#2b51a4', 'aero_fastback', 'radar_ring', 15, '🚗'],
            ['crm_complaints', 'CRM & Complaints', 'علاقات العملاء والشكاوى', 'BU-CRM', 'bu', '1234', '#35b2e2', '#f78c2a', 'gt_coupe', 'side_swoop', 16, '🚗'],
            ['gov_sales', 'Government Sales / Relations', 'المبيعات والعلاقات الحكومية', 'BU-GOV', 'bu', '1234', '#6b85c0', '#e0e0de', 'aero_fastback', 'executive_trim', 17, '🚗'],
            ['planning_performance', 'Planning and Performance Monitoring', 'التخطيط ومتابعة الأداء', 'BU-PLAN', 'bu', '1234', '#40aebb', '#f9ae6a', 'rally_suv', 'vector_speed', 18, '🚙'],
            ['passenger_cars', 'PC (Passenger Cars)', 'قطاع سيارات الركوب (PC)', 'BU-PC', 'bu', '1234', '#049eda', '#2b51a4', 'gt_coupe', 'twin_gt', 19, '🚗'],
            ['cv_ce', 'CV & CE (Commercial & Equipment)', 'السيارات التجارية والمعدات الإنشائية', 'BU-CVCE', 'bu', '1234', '#f78c2a', '#7f8487', 'hauler_truck', 'heavy_shield', 20, '🚛'],
            ['two_three_wheelers', '2&3 Wheelers', 'الدراجات والمركبات الخفيفة (2&3 Wheelers)', 'BU-23W', 'bu', '1234', '#3bae49', '#049eda', 'trike_racer', 'sprint_slash', 21, '🛵'],
            ['tires', 'Tires', 'قطاع الإطارات والخدمات', 'BU-TIRE', 'bu', '1234', '#a5a9ac', '#f78c2a', 'hypercar', 'tread_edge', 22, '🏎️'],
            ['ghabbour_foundation', 'Ghabbour Foundation', 'مؤسسة غبور للتنمية المجتمعية', 'BU-GF', 'bu', '1234', '#62bd6e', '#2b51a4', 'aero_fastback', 'star_beam', 23, '🚗'],
            ['gb_group_companies', 'GB Bus / Itamco / Group Companies', 'جي بي باص / إيتامكو / شركات المجموعة', 'BU-GBC', 'bu', '1234', '#2b51a4', '#1da9de', 'transporter', 'aero_express', 24, '🚌'],

            // 2. العائلات والمجالات الوظيفية الكبرى (Job Families)
            ['jf_hr', 'Human Resources (Job Family)', 'عائلة الموارد البشرية', 'JF-HR', 'job_family', '1234', '#4062ac', '#dcdcda', 'gt_coupe', 'stripes', 25, '🚗'],
            ['jf_it_digital', 'Information Technology/Digital', 'تكنولوجيا المعلومات والحلول الرقمية', 'JF-IT', 'job_family', '1234', '#1da9de', '#f78c2a', 'formula', 'cyber_grid', 26, '🏎️'],
            ['jf_finance_acct', 'Finance and Accounting', 'المالية والمحاسبة', 'JF-FIN', 'job_family', '1234', '#2b51a4', '#f79740', 'speedster', 'dual_bars', 27, '🏎️'],
            ['jf_marketing', 'Marketing (Job Family)', 'التسويق والاتصال المؤسسي', 'JF-MKT', 'job_family', '1234', '#f79740', '#049eda', 'hypercar', 'arrow', 28, '🏎️'],
            ['jf_sales', 'Sales', 'المبيعات وتطوير الأعمال', 'JF-SALES', 'job_family', '1234', '#f78c2a', '#d9d8d6', 'formula', 'velocity', 29, '🏎️'],
            ['jf_engineering', 'Engineering', 'الهندسة والعمليات الفنية', 'JF-ENG', 'job_family', '1234', '#119aaa', '#3bae49', 'aero_fastback', 'gear_mesh', 30, '🚗'],
            ['jf_production_mfg', 'Production / Advanced Manufacturing', 'الإنتاج والتصنيع المتقدم', 'JF-MFG', 'job_family', '1234', '#3bae49', '#f7a454', 'hauler_truck', 'heavy_shield', 31, '🚛'],
            ['jf_quality_assurance', 'Quality Assurance', 'توكيد الجودة والامتثال', 'JF-QA', 'job_family', '1234', '#4eb75b', '#2b51a4', 'hypercar', 'apex_fin', 32, '🏎️'],
            ['jf_logistics_supply', 'Logistics/Supply Chain', 'اللوجستيات وسلاسل الإمداد والتوريد', 'JF-LOG', 'job_family', '1234', '#f7a454', '#7f8487', 'aero_van', 'cargo_bars', 33, '🚐'],
            ['jf_legal', 'Legal (Job Family)', 'الشؤون القانونية والاستشارات', 'JF-LEG', 'job_family', '1234', '#5574b5', '#e4e4e2', 'aero_fastback', 'chevrons', 34, '🚗'],
            ['jf_credit_collections', 'Credit & Collections', 'الائتمان وإدارة التحصيل', 'JF-CRED', 'job_family', '1234', '#7f8487', '#119aaa', 'gt_coupe', 'side_swoop', 35, '🚗'],
            ['jf_customer_service', 'Customer Service / Call Center', 'خدمة العملاء ومركز الاتصال', 'JF-CS', 'job_family', '1234', '#35b2e2', '#d9d8d6', 'speedster', 'wave_glide', 36, '🏎️'],
            ['jf_analytics_data', 'Analytics and Data Science', 'التحليلات وعلم البيانات والذكاء الاصطناعي', 'JF-DATA', 'job_family', '1234', '#28a4b0', '#f9ae6a', 'streamliner', 'telemetry', 37, '⚡'],
            ['jf_corporate_affairs', 'Corporate Affairs', 'الشؤون المؤسسية والعلاقات العامة', 'JF-CORP', 'job_family', '1234', '#6b85c0', '#4eb75b', 'aero_fastback', 'executive_trim', 38, '🚗'],
            ['jf_educational_ops', 'Educational Operations', 'العمليات التعليمية والتدريبية', 'JF-EDU', 'job_family', '1234', '#75c681', '#2b51a4', 'rally_suv', 'vector_speed', 39, '🚙'],
            ['jf_healthcare_hse', 'Healthcare Service Lines / HSE', 'الخدمات الصحية والسلامة والصحة المهنية والبيئة', 'JF-HSE', 'job_family', '1234', '#3bae49', '#fef4ea', 'hse_rapid', 'cross_beacon', 40, '🚑'],
            ['jf_project_program', 'Project and Program Management', 'إدارة المشاريع والبرامج المؤسسية', 'JF-PPM', 'job_family', '1234', '#58b8c4', '#f78c2a', 'hypercar', 'arrow', 41, '🏎️'],
            ['jf_property_delivery', 'Property Management / Construction', 'إدارة الممتلكات والمشاريع الإنشائية', 'JF-PROP', 'job_family', '1234', '#8c9093', '#f79740', 'hauler_truck', 'heavy_shield', 42, '🚛'],
            ['jf_risk_asset', 'Financial Risk / Asset Management', 'إدارة المخاطر المالية وإدارة الأصول', 'JF-RISK', 'job_family', '1234', '#d9d8d6', '#2b51a4', 'speedster', 'dual_bars', 43, '🏎️'],
        ];

        $stmt = $pdo->prepare("
            INSERT INTO `departments` (`id`, `name_en`, `name_ar`, `code`, `category`, `password`, `color`, `secondary_color`, `car_model`, `livery_style`, `racing_num`, `car_emoji`, `position`, `total_points`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)
            ON DUPLICATE KEY UPDATE
                `name_en` = VALUES(`name_en`),
                `name_ar` = VALUES(`name_ar`),
                `code` = VALUES(`code`),
                `category` = VALUES(`category`),
                `color` = VALUES(`color`),
                `secondary_color` = VALUES(`secondary_color`),
                `car_model` = VALUES(`car_model`),
                `livery_style` = VALUES(`livery_style`),
                `racing_num` = VALUES(`racing_num`),
                `car_emoji` = VALUES(`car_emoji`)
        ");
        foreach ($depts as $d) {
            $stmt->execute($d);
        }
    }

    // 2. Seed Default 4 Weeks (Quiz, Photo Challenge, Team Activity, Final Quiz / Challenge)
    $countWeeks = $pdo->query("SELECT COUNT(*) FROM `weeks`")->fetchColumn();
    if ($countWeeks == 0) {
        $weeks = [
            [
                1, 1,
                'Week 1: Knowledge Quiz Sprint',
                'الأسبوع الأول: كويز سباق المعرفة',
                'quiz',
                'Test your company and general knowledge to rev up your department car!',
                'اختبر معلوماتك العامة ومعلومات الشركة لدفع سيارة قسمك نحو خط النهاية!',
                10, 1, 0
            ],
            [
                2, 2,
                'Week 2: Summer Photo Challenge',
                'الأسبوع الثاني: تحدي التصوير الصيفي',
                'photo_challenge',
                'Capture and upload your best summer office or team moments to score miles!',
                'التقط وشارك أجمل صور الصيف مع فريق العمل في قسمك لزيادة نقاط ومسافة سيارتكم!',
                15, 0, 0
            ],
            [
                3, 3,
                'Week 3: Corporate Team Activity',
                'الأسبوع الثالث: تحدي النشاط الجماعي',
                'team_activity',
                'Collaborate together to accomplish this week corporate team mission!',
                'تحدي النشاط الجماعي المشترك لإنجاز مهمة الأسبوع التعاونية وحصد أعلى الأميال!',
                30, 0, 0
            ],
            [
                4, 4,
                'Week 4: The Grand Finale & Bonus Reveal',
                'الأسبوع الرابع: السباق الختامي ومفاجأة البونص',
                'quiz',
                'The final week showdown with the grand secret bonus surprise revealed at the end!',
                'الأسبوع الختامي الحاسم مع إعلان بطل الموسم وكشف مفاجأة البونص الكبرى!',
                20, 0, 0
            ]
        ];

        $stmtWeek = $pdo->prepare("INSERT INTO `weeks` (`id`, `week_number`, `title_en`, `title_ar`, `challenge_type`, `description_en`, `description_ar`, `points_reward`, `is_active`, `is_completed`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($weeks as $w) {
            $stmtWeek->execute($w);
        }
    }

    // 3. Seed Sample Questions for Quiz Weeks (Weeks 1, 2, 3, 4)
    ensureQuestionsSeeded($pdo);

    // 4. Seed System Settings
    $countSettings = $pdo->query("SELECT COUNT(*) FROM `system_settings`")->fetchColumn();
    if ($countSettings == 0) {
        $settings = [
            ['active_week_id', '1'],
            ['race_length', '15'],
            ['gm_pin', '1234'],
            ['admin_password', 'admin123'],
            ['finale_revealed', '0'],
            ['finale_surprise_title_ar', '🎉 مفاجأة البونص الكبرى! 🎉'],
            ['finale_surprise_title_en', '🎉 The Grand Bonus Surprise! 🎉'],
            ['finale_surprise_message_ar', 'ألف مبروك لجميع الأقسام على هذه الرحلة الصيفية الرائعة المليئة بالحماس والطاقة الإيجابية! تهانينا الحارة للقسم البطل المتوج بالمركز الأول ولكل من شارك في صنع هذا الصيف المميز! 🌴☀️🏎️'],
            ['finale_surprise_message_en', 'Huge congratulations to all departments for this unforgettable Summer Road Trip filled with energy, unity and excitement! Special cheers to our crowned champion! 🌴☀️🏎️'],
            ['sound_enabled', '1']
        ];

        $stmtSet = $pdo->prepare("INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
        foreach ($settings as $s) {
            $stmtSet->execute($s);
        }
    }
}

function ensureQuestionsSeeded(PDO $pdo, int $targetWeekId = 0) {
    $allQuestionsData = [
        // Week 1 Questions
        [
            1, 'General Knowledge', 'معلومات عامة',
            'What is the official capital city of Egypt?',
            'ما هي العاصمة الرسمية لجمهورية مصر العربية؟',
            'Alexandria', 'الإسكندرية',
            'Cairo', 'القاهرة',
            'Giza', 'الجيزة',
            'Luxor', 'الأقصر',
            'B', 10
        ],
        [
            1, 'Company & HR', 'الموارد البشرية والشركة',
            'Which department is responsible for talent acquisition and onboarding?',
            'أي قسم في المؤسسة مسؤول عن استقطاب الكفاءات والتوظيف وإدارة المواهب؟',
            'Operations', 'العمليات',
            'Finance', 'الإدارة المالية',
            'Human Resources (HR)', 'الموارد البشرية',
            'IT Support', 'الدعم الفني',
            'C', 10
        ],
        [
            1, 'Technology', 'تكنولوجيا المعلومات',
            'What does the abbreviation "HTML" stand for in web technology?',
            'إلى ماذا يرمز الاختصار "HTML" في تطوير وتصميم صفحات الويب؟',
            'HyperText Markup Language', 'لغة ترميز النص الفائق',
            'HighTech Machine Learning', 'تعلم الآلة عالي التقنية',
            'Hyper Transfer Main Logic', 'منطق النقل الرئيسي الفائق',
            'Home Tool Markup Language', 'لغة أدوات الترميز المنزلية',
            'A', 10
        ],
        [
            1, 'Business & Finance', 'الأعمال والمالية',
            'What does the business acronym "ROI" stand for?',
            'ماذا يعني الاختصار المالي الشهير "ROI" في قياس نجاح المشاريع والاستثمارات؟',
            'Rate of Interest', 'معدل الفائدة',
            'Return on Investment', 'العائد على الاستثمار',
            'Risk of Inflation', 'مخاطر التضخم',
            'Revenue over Income', 'الإيرادات مقارنة بالدخل',
            'B', 10
        ],
        [
            1, 'Operations & Teamwork', 'العمليات والعمل الجماعي',
            'What is the key objective of the Summer Road Trip corporate campaign?',
            'ما هو الهدف الأساسي من حملة ومسابقة Summer Road Trip الصيفية؟',
            'Only working overtime', 'العمل لساعات إضافية فقط',
            'Team bonding, engagement and summer spirit', 'تعزيز روح الفريق والترابط الإيجابي والمرح الصيفي',
            'Buying more cars', 'شراء سيارات جديدة',
            'Individual isolation', 'العمل الفردي المنعزل',
            'B', 10
        ],

        // Week 2 Questions (Technology, Cybersecurity, Leadership)
        [
            2, 'Technology & AI', 'التكنولوجيا والذكاء الاصطناعي',
            'Which programming language is predominantly used for Artificial Intelligence & Data Science?',
            'أي من لغات البرمجة التالية تُستخدم بكثرة وبشكل أساسي في مجالات الذكاء الاصطناعي وعلم البيانات؟',
            'C++', 'سي بلس بلس',
            'Python', 'بايثون',
            'HTML', 'إتش تي إم إل',
            'PHP', 'بي إتش بي',
            'B', 10
        ],
        [
            2, 'Cybersecurity', 'الأمن السيبراني وحماية البيانات',
            'What is the practice of protecting systems, networks, and data from digital attacks called?',
            'ما هو المفهوم الذي يُعبر عن حماية الأنظمة والشبكات والبيانات الرقمية من الهجمات والاختراق؟',
            'Cloud Computing', 'الحوسبة السحابية',
            'Data Mining', 'التنقيب عن البيانات',
            'Cybersecurity', 'الأمن السيبراني',
            'Digital Marketing', 'التسويق الرقمي',
            'C', 10
        ],
        [
            2, 'Project Management', 'إدارة المشاريع',
            'In project management, what is a "Milestone"?',
            'في إدارة وتنفيذ المشاريع، ماذا يعني مصطلح "Milestone"؟',
            'A major critical event or achievement in project timeline', 'حدث رئيسي أو نقطة إنجاز هامة في الجدول الزمني',
            'The financial penalty for project delay', 'الغرامة المالية للتأخير',
            'The project software tool', 'اسم برنامج الإدارة',
            'The project cancellation notice', 'إلغاء المشروع',
            'A', 10
        ],
        [
            2, 'General Science', 'علوم عامة',
            'What is the most abundant chemical element in Earth atmosphere?',
            'ما هو الغاز الكيميائي الأكثر وفرة في الغلاف الجوي لكوكب الأرض؟',
            'Oxygen', 'الأكسجين',
            'Carbon Dioxide', 'ثاني أكسيد الكربون',
            'Nitrogen', 'النيتروجين',
            'Hydrogen', 'الهيدروجين',
            'C', 10
        ],
        [
            2, 'Geography', 'جغرافيا ومعالم',
            'What is the capital city of France?',
            'ما هي عاصمة جمهورية فرنسا؟',
            'Lyon', 'ليون',
            'Marseille', 'مارسيليا',
            'Nice', 'نيس',
            'Paris', 'باريس',
            'D', 10
        ],

        // Week 3 Questions (Quality, Championship & Business Excellence)
        [
            3, 'World Geography', 'جغرافيا العالم',
            'What is the longest river in the world?',
            'ما هو أطول نهر في العالم؟',
            'Amazon River', 'نهر الأمازون',
            'Nile River', 'نهر النيل',
            'Mississippi River', 'نهر المسيسيبي',
            'Yangtze River', 'نهر يانجتسي',
            'B', 10
        ],
        [
            3, 'Astronomy', 'علوم الفضاء',
            'Which planet in our solar system is famously known as the "Red Planet"?',
            'أي كواكب مجموعتنا الشمسية يُعرف باسم "الكوكب الأحمر"؟',
            'Venus', 'الزهرة',
            'Jupiter', 'المشتري',
            'Mars', 'المريخ',
            'Saturn', 'زحل',
            'C', 10
        ],
        [
            3, 'Quality & Standards', 'إدارة الجودة والتميز',
            'Which international standard is globally renowned for Quality Management Systems (QMS)?',
            'ما هي المواصفة والمعيار الدولي الأشهر عالمياً لإدارة وتوكيد الجودة (QMS) في المؤسسات؟',
            'ISO 9001', 'آيزو 9001',
            'ISO 14001', 'آيزو 14001',
            'ISO 27001', 'آيزو 27001',
            'ISO 45001', 'آيزو 45001',
            'A', 10
        ],
        [
            3, 'Organizational Excellence', 'التميز المؤسسي والتآزر',
            'What is the organizational concept of "Synergy"?',
            'في علم الإدارة وثقافة العمل الجماعي، ماذا يعني مفهوم "Synergy" (التآزر)؟',
            'Working without communicating', 'العمل دون تواصل',
            'Collective teamwork producing greater results than individual efforts combined', 'أن التعاون الجماعي ينتج أثراً ونتائج أعظم من مجموع الجهود الفردية',
            'Reducing the work pace', 'تقليل سرعة الإنجاز',
            'Relying solely on external consultants', 'الاعتماد على جهات خارجية فقط',
            'B', 10
        ],
        [
            3, 'General Knowledge', 'معلومات عامة',
            'How many continents are there in the world?',
            'كم عدد قارات العالم المعترف بها جغرافياً؟',
            '5', '5 قارات',
            '6', '6 قارات',
            '7', '7 قارات',
            '8', '8 قارات',
            'C', 10
        ],

        // Week 4 Finale Questions
        [
            4, 'Corporate Culture', 'ثقافة الشركة والتميز',
            'What defines a great team culture during our summer journey?',
            'ما الذي يميز ثقافة الفريق المتميز خلال رحلتنا الصيفية؟',
            'Collaboration, appreciation, and continuous energy', 'التعاون والتقدير المتبادل والطاقة الإيجابية المستمرة',
            'Competition without empathy', 'المنافسة دون تعاطف',
            'Working in strict silos', 'الانعزال التام عن باقي الأقسام',
            'Avoiding challenges', 'تجنب المشاركة في التحديات',
            'A', 20
        ],
        [
            4, 'Innovation', 'الابتكار والريادة',
            'How can every department contribute to overall company excellence?',
            'كيف يساهم كل قسم في تحقيق التميز المؤسسي لشركتنا؟',
            'By driving continuous improvement and creative ideas', 'من خلال التطوير المستمر وتقديم الأفكار الإبداعية',
            'By ignoring feedback', 'بتجاهل آراء الزملاء',
            'By doing minimal effort', 'ببذل الحد الأدنى فقط',
            'By delaying projects', 'بتأخير إنجاز المشروعات',
            'A', 20
        ],
        [
            4, 'Growth Mindset', 'التطوير والتعلم المستمر',
            'What is the key purpose of constructive feedback in professional development?',
            'ما هو الهدف الأساسي من التغذية الراجعة البناءة (Constructive Feedback) في بيئة العمل؟',
            'Continuous personal and team improvement', 'التطوير المستمر للأداء الفردي والجماعي',
            'Finding faults only', 'تصيد الأخطاء فقط',
            'Stopping new initiatives', 'إيقاف المبادرات الجديدة',
            'Reducing productivity', 'تقليل الإنتاجية',
            'A', 20
        ],
        [
            4, 'Operational Efficiency', 'الكفاءة والرشاقة التشغيلية',
            'What primary factor enhances agility and execution speed across teams?',
            'ما هو العامل الأهم لتعزيز الرشاقة وسرعة تنفيذ المهام بين فرق العمل؟',
            'Clear communication and streamlined workflows', 'وضوح قنوات الاتصال وتبسيط إجراءات العمل',
            'Adding excessive bureaucracy', 'زيادة التعقيدات الإدارية',
            'Avoiding technology', 'تجنب استخدام التقنيات الحديثة',
            'Delaying decisions', 'تأجيل القرارات الهامة',
            'A', 20
        ],
        [
            4, 'Championship Mindset', 'عقلية الفوز والبطولة',
            'How do championship teams maintain consistent high performance throughout the race?',
            'كيف تحافظ الفرق البطلة على مستوى أدائها العالي حتى خط نهاية السباق؟',
            'Consistency, discipline, and persistent team dedication', 'الاستمرارية والانضباط والتفاني المشترك بين جميع الأعضاء',
            'Relying on luck alone', 'الاعتماد على الحظ فقط',
            'Stopping effort after first win', 'التوقف عن المحاولة بعد أول فوز',
            'Ignoring team spirit', 'تجاهل الروح المعنوية للفريق',
            'A', 20
        ]
    ];

    $weeksToCheck = ($targetWeekId > 0) ? [$targetWeekId] : [1, 2, 3, 4];
    $stmtCheckWeek = $pdo->prepare("SELECT COUNT(*) FROM `weeks` WHERE `id` = ?");
    $stmtInsertWeek = $pdo->prepare("
        INSERT INTO `weeks` (`id`, `week_number`, `title_en`, `title_ar`, `challenge_type`, `description_en`, `description_ar`, `points_reward`, `is_active`, `is_completed`)
        VALUES (?, ?, ?, ?, 'multi', ?, ?, 50, ?, 0)
        ON DUPLICATE KEY UPDATE `id` = `id`
    ");

    $weekTitles = [
        1 => ['Week 1: Season Kickoff', 'الأسبوع الأول: انطلاقة المنافسة', 'Complete the Kickoff Quiz, upload team photo, and execute the team collaboration mission!', 'أنجز كويز الأسبوع الأول وتحدي التصوير الصيفي والنشاط الجماعي لحصد أعلى النقاط لقسمك!'],
        2 => ['Week 2: Mid-Journey Sprint', 'الأسبوع الثاني: سباق الصدارة', 'Week 2 is live! Answer the knowledge quiz, share creativity photos, and complete the department mission!', 'تحديات الأسبوع الثاني: كويز المعرفة، ومشاركة صور إبداع الفريق، ومهمة القسم التعاونية!'],
        3 => ['Week 3: Championship Finale', 'الأسبوع الثالث: السباق الختامي والتتويج', 'The Grand Finale! Face the ultimate quiz, submit celebration photo, and claim the championship trophy!', 'المرحلة الختامية الكبرى: كويز التتويج، وصورة الاحتفال الجماعية، وحسم درع بطل الموسم!'],
        4 => ['Week 4: The Grand Finale & Bonus Reveal', 'الأسبوع الرابع: السباق الختامي ومفاجأة البونص', 'The final week showdown with the grand secret bonus surprise revealed at the end!', 'الأسبوع الختامي الحاسم مع إعلان بطل الموسم وكشف مفاجأة البونص الكبرى!']
    ];

    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM `questions` WHERE `week_id` = ?");
    $stmtInsert = $pdo->prepare("
        INSERT INTO `questions` (`week_id`, `category_en`, `category_ar`, `question_en`, `question_ar`, `option_a_en`, `option_a_ar`, `option_b_en`, `option_b_ar`, `option_c_en`, `option_c_ar`, `option_d_en`, `option_d_ar`, `correct_option`, `points`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($weeksToCheck as $wId) {
        // Guarantee parent week exists in `weeks` table before adding child questions
        $stmtCheckWeek->execute([$wId]);
        if ($stmtCheckWeek->fetchColumn() == 0) {
            $t = $weekTitles[$wId] ?? ['Week ' . $wId, 'الأسبوع ' . $wId, 'Weekly challenge', 'تحدي الأسبوع'];
            $isActive = ($wId === 1) ? 1 : 0;
            $stmtInsertWeek->execute([$wId, $wId, $t[0], $t[1], $t[2], $t[3], $isActive]);
        }

        $stmtCheck->execute([$wId]);
        $count = (int)$stmtCheck->fetchColumn();
        if ($count === 0) {
            foreach ($allQuestionsData as $q) {
                if ($q[0] === $wId) {
                    $stmtInsert->execute($q);
                }
            }
        }
    }
}

// ========================================================================
// CYBERSECURITY HELPERS: CSRF, RATE LIMITING & INPUT SANITIZATION
// ========================================================================

/**
 * Get or create cryptographic CSRF token for the active session
 */
function getCsrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate incoming CSRF token via timing-safe comparison
 */
function verifyCsrfToken(?string $token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (empty($sessionToken)) {
        return false;
    }
    
    // Check direct parameter
    if (!empty($token) && hash_equals($sessionToken, (string)$token)) {
        return true;
    }

    // Check request header X-CSRF-Token or X-XSRF-Token
    $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_SERVER['HTTP_X_XSRF_TOKEN'] ?? null);
    if (!empty($headerToken) && hash_equals($sessionToken, (string)$headerToken)) {
        return true;
    }

    return false;
}

/**
 * Resolve client IP address safely
 */
function getClientIP(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwarded = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($forwarded[0]);
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
}

/**
 * Check whether an action is currently rate-limited
 * Returns array: ['allowed' => bool, 'remaining' => int, 'locked_until' => ?string, 'message' => string]
 */
function checkRateLimit(PDO $pdo, string $action, string $identifier = '', int $maxAttempts = 5, int $windowSeconds = 600, int $lockoutSeconds = 900): array {
    $ip = getClientIP();
    $now = date('Y-m-d H:i:s');

    try {
        $stmt = $pdo->prepare("
            SELECT attempts, first_attempt_at, last_attempt_at, locked_until 
            FROM `security_rate_limits` 
            WHERE `ip_address` = ? AND `action_name` = ? AND `identifier` = ?
            LIMIT 1
        ");
        $stmt->execute([$ip, $action, $identifier]);
        $row = $stmt->fetch();

        if ($row) {
            // Check if currently locked
            if (!empty($row['locked_until']) && strtotime($row['locked_until']) > time()) {
                $waitSecs = strtotime($row['locked_until']) - time();
                $waitMins = ceil($waitSecs / 60);
                return [
                    'allowed' => false,
                    'remaining' => 0,
                    'locked_until' => $row['locked_until'],
                    'message' => "تم حظر المحاولات مؤقتاً بسبب تكرار الطلبات بشكل مريب. يرجى الانتظار {$waitMins} دقيقة والمحاولة لاحقاً. | Too many attempts. Locked for {$waitMins} min."
                ];
            }

            // Check if outside window (reset)
            if (time() - strtotime($row['first_attempt_at']) > $windowSeconds) {
                $stmtDel = $pdo->prepare("DELETE FROM `security_rate_limits` WHERE `ip_address` = ? AND `action_name` = ? AND `identifier` = ?");
                $stmtDel->execute([$ip, $action, $identifier]);
                return ['allowed' => true, 'remaining' => $maxAttempts, 'locked_until' => null, 'message' => ''];
            }

            $remaining = max(0, $maxAttempts - (int)$row['attempts']);
            if ($remaining === 0) {
                // Trigger lock
                $lockedUntil = date('Y-m-d H:i:s', time() + $lockoutSeconds);
                $stmtLock = $pdo->prepare("UPDATE `security_rate_limits` SET `locked_until` = ? WHERE `ip_address` = ? AND `action_name` = ? AND `identifier` = ?");
                $stmtLock->execute([$lockedUntil, $ip, $action, $identifier]);
                $waitMins = ceil($lockoutSeconds / 60);
                return [
                    'allowed' => false,
                    'remaining' => 0,
                    'locked_until' => $lockedUntil,
                    'message' => "تجاوزت الحد المسموح به من المحاولات. تم قفل المحاولات مؤقتاً لمدة {$waitMins} دقيقة لحماية الحساب. | Too many failed attempts. Locked for {$waitMins} min."
                ];
            }

            return ['allowed' => true, 'remaining' => $remaining, 'locked_until' => null, 'message' => ''];
        }

        return ['allowed' => true, 'remaining' => $maxAttempts, 'locked_until' => null, 'message' => ''];
    } catch (Exception $e) {
        // Fallback open if rate limit table error
        return ['allowed' => true, 'remaining' => $maxAttempts, 'locked_until' => null, 'message' => ''];
    }
}

/**
 * Record a failed attempt towards rate limit
 */
function recordFailedRateAttempt(PDO $pdo, string $action, string $identifier = '', int $maxAttempts = 5, int $windowSeconds = 600, int $lockoutSeconds = 900) {
    $ip = getClientIP();
    $now = date('Y-m-d H:i:s');

    try {
        $stmt = $pdo->prepare("
            SELECT attempts, first_attempt_at, locked_until 
            FROM `security_rate_limits` 
            WHERE `ip_address` = ? AND `action_name` = ? AND `identifier` = ?
            LIMIT 1
        ");
        $stmt->execute([$ip, $action, $identifier]);
        $row = $stmt->fetch();

        if ($row) {
            $newAttempts = (int)$row['attempts'] + 1;
            $lockedUntil = ($newAttempts >= $maxAttempts) ? date('Y-m-d H:i:s', time() + $lockoutSeconds) : null;

            $stmtUpd = $pdo->prepare("
                UPDATE `security_rate_limits` 
                SET `attempts` = ?, `last_attempt_at` = ?, `locked_until` = COALESCE(?, `locked_until`)
                WHERE `ip_address` = ? AND `action_name` = ? AND `identifier` = ?
            ");
            $stmtUpd->execute([$newAttempts, $now, $lockedUntil, $ip, $action, $identifier]);
        } else {
            $stmtIns = $pdo->prepare("
                INSERT INTO `security_rate_limits` (`ip_address`, `action_name`, `identifier`, `attempts`, `first_attempt_at`, `last_attempt_at`)
                VALUES (?, ?, ?, 1, ?, ?)
            ");
            $stmtIns->execute([$ip, $action, $identifier, $now, $now]);
        }
    } catch (Exception $e) {}
}

/**
 * Clear rate limit records upon successful verification
 */
function clearRateLimit(PDO $pdo, string $action, string $identifier = '') {
    $ip = getClientIP();
    try {
        $stmt = $pdo->prepare("DELETE FROM `security_rate_limits` WHERE `ip_address` = ? AND `action_name` = ? AND `identifier` = ?");
        $stmt->execute([$ip, $action, $identifier]);
    } catch (Exception $e) {}
}

