<?php
require_once __DIR__ . '/../db.php';
$pdo = getDBConnection();

echo "--- 1. Testing Database Timezone ---\n";
$tzStmt = $pdo->query("SELECT @@session.time_zone AS session_tz, NOW() AS mysql_now");
$tzRow = $tzStmt->fetch();
echo "PHP date: " . date('Y-m-d H:i:s P') . "\n";
echo "MySQL now: " . $tzRow['mysql_now'] . " (tz: " . $tzRow['session_tz'] . ")\n";

echo "\n--- 2. Testing get_questions API ---\n";
// Pick a test user
$userStmt = $pdo->query("SELECT id, department_id FROM users LIMIT 1");
$user = $userStmt->fetch();
if (!$user) {
    echo "No user found in database!\n";
    exit;
}
$userId = (int)$user['id'];
$deptId = $user['department_id'];
echo "Testing with User ID: $userId, Dept: $deptId\n";

// Clear existing attempts for this user on week 1 question 1 to test clean flow
$qStmt = $pdo->query("SELECT id, correct_option FROM questions WHERE week_id = 1 ORDER BY id ASC LIMIT 1");
$q = $qStmt->fetch();
$qId = (int)$q['id'];
$correctOpt = $q['correct_option'];
echo "Question ID: $qId, Correct Option: $correctOpt\n";

$pdo->prepare("DELETE FROM quiz_attempts WHERE user_id = ? AND question_id = ?")->execute([$userId, $qId]);

// 3. Test start_question
echo "\n--- 3. Testing start_question API ---\n";
$_POST = [
    'action' => 'start_question',
    'question_id' => $qId,
    'user_id' => $userId
];
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SESSION['user_id'] = $userId;

ob_start();
include __DIR__ . '/../api/quiz.php';
$resStart = ob_get_clean();
echo "start_question response: " . $resStart . "\n";

// 4. Test submit_answer
echo "\n--- 4. Testing submit_answer API ---\n";
// Re-open session if needed
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['user_id'] = $userId;
$_POST = [
    'action' => 'submit_answer',
    'question_id' => $qId,
    'selected_option' => $correctOpt,
    'user_id' => $userId,
    'department_id' => $deptId
];
$_SERVER['REQUEST_METHOD'] = 'POST';

ob_start();
include __DIR__ . '/../api/quiz.php';
$resSubmit = ob_get_clean();
echo "submit_answer response: " . $resSubmit . "\n";

// Verify attempt in DB
$attStmt = $pdo->prepare("SELECT * FROM quiz_attempts WHERE user_id = ? AND question_id = ?");
$attStmt->execute([$userId, $qId]);
$attempt = $attStmt->fetch();
echo "\n--- 5. Attempt in DB after submit ---\n";
print_r($attempt);

if ($attempt && $attempt['is_correct'] == 1 && $attempt['selected_option'] == $correctOpt) {
    echo "\n>>> SUCCESS: Quiz Answer Flow is 100% WORKING! <<<\n";
} else {
    echo "\n>>> FAILED: Answer was not registered correctly! <<<\n";
}
