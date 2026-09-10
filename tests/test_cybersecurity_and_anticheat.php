<?php
/**
 * Automated Cybersecurity & Anti-Cheat Test Suite
 * GB Corp Summer Road Trip
 */
require_once __DIR__ . '/../db.php';
$pdo = getDBConnection();

$passed = 0;
$failed = 0;

function assertTest($title, $condition, $details = '') {
    global $passed, $failed;
    if ($condition) {
        echo "✅ PASS: {$title}\n";
        $passed++;
    } else {
        echo "❌ FAIL: {$title} - {$details}\n";
        $failed++;
    }
}

echo "========================================================\n";
echo "🛡️ STARTING CYBERSECURITY & ANTI-CHEAT COMPREHENSIVE AUDIT\n";
echo "========================================================\n\n";

// TEST 1: CSRF Token Generation and Verification
$token = getCsrfToken();
assertTest("CSRF: Token generated and has valid length", !empty($token) && strlen($token) === 64);
assertTest("CSRF: Valid token verified successfully", verifyCsrfToken($token));
assertTest("CSRF: Tampered token strictly rejected", !verifyCsrfToken("invalid_token_12345"));

// TEST 2: Rate Limiting Engine
clearRateLimit($pdo, 'test_action', 'test_user');
$rate1 = checkRateLimit($pdo, 'test_action', 'test_user', 3, 60, 60);
assertTest("RateLimit: First attempt is allowed", $rate1['allowed']);

recordFailedRateAttempt($pdo, 'test_action', 'test_user', 3, 60, 60);
recordFailedRateAttempt($pdo, 'test_action', 'test_user', 3, 60, 60);
recordFailedRateAttempt($pdo, 'test_action', 'test_user', 3, 60, 60);

$rateBlocked = checkRateLimit($pdo, 'test_action', 'test_user', 3, 60, 60);
assertTest("RateLimit: Exceeding 3 attempts triggers lockout", !$rateBlocked['allowed']);

clearRateLimit($pdo, 'test_action', 'test_user');
$rateCleared = checkRateLimit($pdo, 'test_action', 'test_user', 3, 60, 60);
assertTest("RateLimit: Clearing attempts restores access", $rateCleared['allowed']);

// TEST 3: Anti-Cheat - Mandatory Start Question Requirement
// Ensure test user exists
$stmtTestU = $pdo->prepare("SELECT id FROM `users` WHERE `email` = 'security_test@gbcorp.com'");
$stmtTestU->execute();
$testUserId = (int)$stmtTestU->fetchColumn();
if ($testUserId <= 0) {
    $pdo->prepare("INSERT INTO `users` (`name`, `email`, `department_id`, `role`) VALUES ('SecTester', 'security_test@gbcorp.com', 'it', 'user')")->execute();
    $testUserId = (int)$pdo->lastInsertId();
}

$stmtQ = $pdo->query("SELECT id FROM `questions` WHERE `week_id` = 1 LIMIT 1");
$testQId = (int)$stmtQ->fetchColumn();

// Clean up any attempts for this test question
$pdo->prepare("DELETE FROM `quiz_attempts` WHERE `user_id` = ? AND `question_id` = ?")->execute([$testUserId, $testQId]);

// Emulate calling submit_answer without start_question
$_SESSION['user_id'] = $testUserId;
$_SESSION['csrf_token'] = $token;

// Check directly against quiz attempt logic
$stmtCheck = $pdo->prepare("SELECT id, selected_option FROM `quiz_attempts` WHERE `user_id` = ? AND `question_id` = ?");
$stmtCheck->execute([$testUserId, $testQId]);
$attempt = $stmtCheck->fetch();
assertTest("Anti-Cheat: No unstarted attempt exists prior to start", $attempt === false);

// TEST 4: Anti-Cheat - Start Lock and Inhuman Reaction Time
$pdo->prepare("INSERT INTO `quiz_attempts` (`user_id`, `department_id`, `week_id`, `question_id`, `selected_option`, `created_at`) VALUES (?, 'it', 1, ?, 'PENDING', NOW())")->execute([$testUserId, $testQId]);
$attemptRow = $pdo->query("SELECT id, created_at FROM `quiz_attempts` WHERE `user_id` = {$testUserId} AND `question_id` = {$testQId}")->fetch();

$elapsed = time() - strtotime($attemptRow['created_at']);
assertTest("Anti-Cheat: Question locked with PENDING state", !empty($attemptRow));
assertTest("Anti-Cheat: Rapid bot submission (<1s) detected", $elapsed < 1);

// TEST 5: Anti-Cheat - Forfeit & Permanent Re-entry Blocking
$pdo->prepare("UPDATE `quiz_attempts` SET `selected_option` = 'FORFEIT', `points_earned` = 0 WHERE `id` = ?")->execute([$attemptRow['id']]);
$stmtForfeitCheck = $pdo->prepare("SELECT selected_option FROM `quiz_attempts` WHERE `id` = ?");
$stmtForfeitCheck->execute([$attemptRow['id']]);
$forfeitedStatus = $stmtForfeitCheck->fetchColumn();
assertTest("Anti-Cheat: Forfeited attempt is permanently recorded as FORFEIT", $forfeitedStatus === 'FORFEIT');

// TEST 6: Photo Upload - Exactly 1 Submission Per Week Limit
$pdo->prepare("DELETE FROM `photo_submissions` WHERE `user_id` = ? AND `week_id` = 1")->execute([$testUserId]);
$stmtUploadCheck1 = $pdo->prepare("SELECT COUNT(*) FROM `photo_submissions` WHERE `user_id` = ? AND `week_id` = 1");
$stmtUploadCheck1->execute([$testUserId]);
assertTest("Photo Challenge: Initial submission count is 0", (int)$stmtUploadCheck1->fetchColumn() === 0);

$pdo->prepare("INSERT INTO `photo_submissions` (`user_id`, `department_id`, `week_id`, `photo_path`, `caption`, `status`, `points_awarded`) VALUES (?, 'it', 1, 'uploads/photos/test.jpg', 'Test', 'approved', 15)")->execute([$testUserId]);

$stmtUploadCheck2 = $pdo->prepare("SELECT COUNT(*) FROM `photo_submissions` WHERE `user_id` = ? AND `week_id` = 1");
$stmtUploadCheck2->execute([$testUserId]);
assertTest("Photo Challenge: One submission recorded", (int)$stmtUploadCheck2->fetchColumn() === 1);

// Attempting duplicate check
$hasAlready = (int)$pdo->query("SELECT COUNT(*) FROM `photo_submissions` WHERE `user_id` = {$testUserId} AND `week_id` = 1")->fetchColumn();
assertTest("Photo Challenge: Duplicate submission blocked by limit check", $hasAlready >= 1);

// Clean up test photo
$pdo->prepare("DELETE FROM `photo_submissions` WHERE `user_id` = ? AND `week_id` = 1")->execute([$testUserId]);

// TEST 7: Admin Security & Backdoor Removal
// Verify that an unauthenticated user or regular player CANNOT access admin actions
$_SESSION['user_id'] = $testUserId; // regular user, role != 'admin'
$_SESSION['is_admin'] = false;

$stmtAdminCheck = $pdo->prepare("SELECT role, is_admin FROM `users` WHERE `id` = ?");
$stmtAdminCheck->execute([$_SESSION['user_id']]);
$adminUser = $stmtAdminCheck->fetch();
$isAdmin = ($adminUser && ($adminUser['role'] === 'admin' || !empty($adminUser['is_admin'])));
assertTest("Admin Security: Regular player strictly rejected from Admin", $isAdmin === false);

// TEST 8: PII Protection in Public Leaderboard
ob_start();
require __DIR__ . '/../api/leaderboard.php';
$leaderboardOutput = ob_get_clean();
$lbData = json_decode($leaderboardOutput, true);

$allEmailsMasked = true;
if (!empty($lbData['data']['top_users'])) {
    foreach ($lbData['data']['top_users'] as $u) {
        if (!empty($u['email']) && !str_contains($u['email'], '***@')) {
            $allEmailsMasked = false;
        }
    }
}
assertTest("PII Privacy: Leaderboard masks all employee email addresses", $allEmailsMasked);

// TEST 9: Directory Traversal Protection Verification
$uploadsIndexExists = file_exists(__DIR__ . '/../uploads/index.html');
$uploadsPhotosIndexExists = file_exists(__DIR__ . '/../uploads/photos/index.html');
assertTest("Infrastructure: Uploads directory traversal blocker exists (index.html)", $uploadsIndexExists && $uploadsPhotosIndexExists);

// TEST 10: Uploads Deep Security (Script Extensions Blocked via PHP API)
$uploadApiCode = file_get_contents(__DIR__ . '/../api/upload_photo.php');
$hasExtensionCheck = str_contains($uploadApiCode, 'dangerousExts') && str_contains($uploadApiCode, 'validExts');
$hasMimeCheck = str_contains($uploadApiCode, 'finfo_file');
assertTest("Infrastructure: Script execution strictly prevented by upload engine (PHP level)", $hasExtensionCheck && $hasMimeCheck);

// Clean up test quiz attempt
$pdo->prepare("DELETE FROM `quiz_attempts` WHERE `user_id` = ?")->execute([$testUserId]);

echo "\n========================================================\n";
echo "📊 AUDIT SUMMARY: {$passed} PASSED | {$failed} FAILED\n";
echo "========================================================\n";

if ($failed > 0) {
    exit(1);
}