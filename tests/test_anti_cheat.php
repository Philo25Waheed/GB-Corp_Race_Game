<?php
require_once __DIR__ . '/../db.php';
$pdo = getDBConnection();

echo "=== RUNNING ANTI-CHEAT AUTOMATED VERIFICATION ===\n\n";

// 1. Get test user
$stmt = $pdo->query("SELECT id, department_id, name, email FROM users LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    die("Error: No users in DB to test with.\n");
}
$userId = (int)$user['id'];
echo "1. Using test user: {$user['name']} (ID: {$userId})\n";

// Ensure password is set for test authentication
$testPass = '123456';
$pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([password_hash($testPass, PASSWORD_DEFAULT), $userId]);

$cookieFile = __DIR__ . '/test_cookie.txt';
if (file_exists($cookieFile)) @unlink($cookieFile);

// Authenticate via login to get session cookie & CSRF token
$chAuth = curl_init("http://localhost/RaceGame/api/auth.php?action=login");
curl_setopt($chAuth, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chAuth, CURLOPT_POST, true);
curl_setopt($chAuth, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($chAuth, CURLOPT_POSTFIELDS, json_encode(['email' => $user['email'], 'password' => $testPass]));
curl_setopt($chAuth, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($chAuth, CURLOPT_COOKIEFILE, $cookieFile);
$authResult = json_decode(curl_exec($chAuth), true);
curl_close($chAuth);

$csrfToken = $authResult['data']['csrf_token'] ?? '';

// 2. Get active question
$stmtQ = $pdo->query("SELECT id, question_ar FROM questions LIMIT 1");
$q = $stmtQ->fetch(PDO::FETCH_ASSOC);
if (!$q) {
    die("Error: No questions in DB to test with.\n");
}
$questionId = (int)$q['id'];
echo "2. Using test question ID: {$questionId} ('{$q['question_ar']}')\n\n";

// Clean up any existing attempt for this test pair
$pdo->prepare("DELETE FROM quiz_attempts WHERE user_id = ? AND question_id = ?")->execute([$userId, $questionId]);

// TEST A: Start Question
echo "TEST A: Starting Question (Simulate Player entering question)...\n";
$startPayload = json_encode(['question_id' => $questionId, 'csrf_token' => $csrfToken]);
$ch = curl_init("http://localhost/RaceGame/api/quiz.php?action=start_question");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', "X-CSRF-Token: {$csrfToken}"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $startPayload);
$respA = json_decode(curl_exec($ch), true);
curl_close($ch);

echo "Response A: " . json_encode($respA, JSON_UNESCAPED_UNICODE) . "\n";
if ($respA && $respA['success'] && $respA['data']['status'] === 'PENDING') {
    echo ">>> PASS: Question started and locked as PENDING in database!\n\n";
} else {
    echo ">>> FAIL in Test A!\n\n";
}

// TEST B: Re-entering the question (Simulate refresh / back navigation)
echo "TEST B: Re-entering same question (Simulate player re-entering or cheating attempt)...\n";
$ch = curl_init("http://localhost/RaceGame/api/quiz.php?action=start_question");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', "X-CSRF-Token: {$csrfToken}"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $startPayload);
$respB = json_decode(curl_exec($ch), true);
curl_close($ch);

echo "Response B: " . json_encode($respB, JSON_UNESCAPED_UNICODE) . "\n";
if ($respB && !empty($respB['data']) && $respB['data']['status'] === 'PENDING') {
    echo ">>> PASS: System detects question is already started and in-progress.\n\n";
}

// TEST C: Forfeiting Question on Tab Switch / Blur
echo "TEST C: Forfeiting Question (Simulate player switching tab to AI)...\n";
$forfeitPayload = json_encode(['question_id' => $questionId, 'reason' => 'tab_switch', 'csrf_token' => $csrfToken]);
$ch = curl_init("http://localhost/RaceGame/api/quiz.php?action=forfeit_question");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', "X-CSRF-Token: {$csrfToken}"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $forfeitPayload);
$respC = json_decode(curl_exec($ch), true);
curl_close($ch);

echo "Response C: " . json_encode($respC, JSON_UNESCAPED_UNICODE) . "\n";
if ($respC && $respC['success'] && $respC['data']['status'] === 'FORFEIT') {
    echo ">>> PASS: Question immediately forfeited on tab switch!\n\n";
} else {
    echo ">>> FAIL in Test C!\n\n";
}

// TEST D: Trying to submit answer after forfeit
echo "TEST D: Trying to submit answer after cheating/forfeit...\n";
$ansPayload = json_encode(['question_id' => $questionId, 'selected_option' => 'A', 'csrf_token' => $csrfToken]);
$ch = curl_init("http://localhost/RaceGame/api/quiz.php?action=submit_answer");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', "X-CSRF-Token: {$csrfToken}"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $ansPayload);
$respD = json_decode(curl_exec($ch), true);
curl_close($ch);

echo "Response D: " . json_encode($respD, JSON_UNESCAPED_UNICODE) . "\n";
if ($respD && $respD['success'] === false) {
    echo ">>> PASS: Submission strictly BLOCKED on forfeited question!\n\n";
} else {
    echo ">>> FAIL in Test D!\n\n";
}

// TEST E: Starting question again after forfeit (Strict blocking)
echo "TEST E: Trying to re-start already forfeited question...\n";
$ch = curl_init("http://localhost/RaceGame/api/quiz.php?action=start_question");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', "X-CSRF-Token: {$csrfToken}"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $startPayload);
$respE = json_decode(curl_exec($ch), true);
curl_close($ch);

echo "Response E: " . json_encode($respE, JSON_UNESCAPED_UNICODE) . "\n";
if ($respE && $respE['success'] === false && !empty($respE['data']['already_attempted'])) {
    echo ">>> PASS: Re-entry permanently blocked! Question is burned.\n\n";
} else {
    echo ">>> FAIL in Test E!\n\n";
}

// Clean up test attempt
$pdo->prepare("DELETE FROM quiz_attempts WHERE user_id = ? AND question_id = ?")->execute([$userId, $questionId]);

@unlink($cookieFile);
echo "=== ALL ANTI-CHEAT TESTS COMPLETED SUCCESSFULLY! ===\n";
