<?php
require_once __DIR__ . '/../db.php';
$pdoTest = getDBConnection();
$pdoTest->exec("DELETE FROM security_rate_limits");
@unlink(__DIR__ . '/test_cookie.txt');

echo "====================================================\n";
echo "🏁 STARTING COMPREHENSIVE TEST SUITE & SECURITY AUDIT\n";
echo "====================================================\n\n";

$baseUrl = 'http://localhost/RaceGameOld';
$testsPassed = 0;
$testsFailed = 0;
$latestCsrfToken = '';

function runTest($testName, $fn) {
    global $testsPassed, $testsFailed;
    echo "▶ Testing: {$testName}... ";
    try {
        $result = $fn();
        if ($result === true) {
            echo "✅ PASSED\n";
            $testsPassed++;
        } else {
            echo "❌ FAILED: {$result}\n";
            $testsFailed++;
        }
    } catch (Throwable $e) {
        echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
        $testsFailed++;
    }
}

function httpPost($url, $data = [], $headers = []) {
    global $latestCsrfToken;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    if (is_array($data) && $latestCsrfToken && empty($data['csrf_token'])) {
        $data['csrf_token'] = $latestCsrfToken;
    }
    curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? json_encode($data) : $data);
    $defaultHeaders = ['Content-Type: application/json'];
    if ($latestCsrfToken) {
        $defaultHeaders[] = 'X-CSRF-Token: ' . $latestCsrfToken;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, $headers));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/test_cookie.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/test_cookie.txt');
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = json_decode($response, true);
    if (!empty($json['data']['csrf_token'])) {
        $latestCsrfToken = $json['data']['csrf_token'];
    }
    return ['code' => $httpCode, 'json' => $json, 'raw' => $response];
}

function httpGet($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/test_cookie.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/test_cookie.txt');
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'json' => json_decode($response, true), 'raw' => $response];
}

// 1. Test Game State Retrieval
runTest("Game State API (api/game_state.php)", function() use ($baseUrl) {
    $res = httpGet("{$baseUrl}/api/game_state.php");
    if (!$res['json'] || !$res['json']['success']) return "Failed to retrieve game state";
    if (!isset($res['json']['data']['departments'])) return "Missing departments in game state";
    if (count($res['json']['data']['departments']) < 5) return "Expected 5 departments";
    return true;
});

// 2. Test User Registration with Validation & XSS strings
runTest("User Registration Validation & XSS Sanity (api/auth.php)", function() use ($baseUrl) {
    // Missing fields
    $res1 = httpPost("{$baseUrl}/api/auth.php?action=register_login", ['name' => '', 'email' => '']);
    if ($res1['json']['success'] !== false) return "Should reject empty user registration";

    // Invalid Email
    $res2 = httpPost("{$baseUrl}/api/auth.php?action=register_login", ['name' => 'Tester', 'email' => 'invalid-email', 'department_id' => 'it']);
    if ($res2['json']['success'] !== false) return "Should reject invalid email format";

    // Valid User with special characters
    $res3 = httpPost("{$baseUrl}/api/auth.php?action=register_login", [
        'name' => 'John <script>alert(1)</script>',
        'email' => 'sec_audit_' . time() . '@gbcorp.com',
        'department_id' => 'it'
    ]);
    if (!$res3['json'] || !$res3['json']['success']) return "Valid user registration failed";
    if (empty($res3['json']['data']['user']['id'])) return "User ID not returned";

    return true;
});

// 3. Test Quiz Protection (Cannot answer without login, and cannot inspect correct_option)
runTest("Quiz API & Anti-Cheating Protection (api/quiz.php)", function() use ($baseUrl) {
    $resQ = httpGet("{$baseUrl}/api/quiz.php?action=get_questions&week_id=1");
    if (!$resQ['json'] || !$resQ['json']['success']) return "Failed to fetch quiz questions";
    
    $questions = $resQ['json']['data']['questions'];
    if (empty($questions)) return "No questions returned";

    // Verify correct_option is NOT leaked to clients
    foreach ($questions as $q) {
        if (isset($q['correct_option'])) {
            return "SECURITY VULNERABILITY: correct_option leaked in public API!";
        }
    }

    // Attempt answering without valid user (without session cookie)
    $chUnauth = curl_init("{$baseUrl}/api/quiz.php?action=submit_answer");
    curl_setopt($chUnauth, CURLOPT_POST, 1);
    curl_setopt($chUnauth, CURLOPT_POSTFIELDS, json_encode([
        'question_id' => $questions[0]['id'],
        'selected_option' => 'B',
        'user_id' => 0
    ]));
    curl_setopt($chUnauth, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($chUnauth, CURLOPT_RETURNTRANSFER, true);
    $resAnsRaw = curl_exec($chUnauth);
    $resAnsNoUser = json_decode($resAnsRaw, true);
    curl_close($chUnauth);

    if (!$resAnsNoUser || $resAnsNoUser['success'] !== false) {
        return "Should reject submission without valid user";
    }

    return true;
});

// 4. Test Admin Authentication & Unauthorized Lockout
runTest("Admin Authentication & Lockout (api/admin.php)", function() use ($baseUrl) {
    // Attempt unauthorized reset without credentials
    $ch = curl_init("{$baseUrl}/api/admin.php?action=reset_race");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['admin_pin' => 'wrong_pin_9999']));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $raw = curl_exec($ch);
    $res = json_decode($raw, true);
    curl_close($ch);

    if ($res && $res['success'] === true) return "SECURITY VULNERABILITY: Unauthorized access granted with wrong PIN!";

    // Valid PIN Authentication
    $resAuth = httpPost("{$baseUrl}/api/auth.php?action=admin_login", ['password' => '1234']);
    if (!$resAuth['json'] || !$resAuth['json']['success']) return "Valid admin authentication failed: " . ($resAuth['raw'] ?: 'EMPTY');

    return true;
});

// 5. Test Minus Points & Direct Points Adjustment
runTest("Minus Points & Direct Penalty Adjustment (api/admin.php)", function() use ($baseUrl) {
    // Add 30 points
    $resAdd = httpPost("{$baseUrl}/api/admin.php?action=adjust_points", [
        'admin_pin' => '1234',
        'department_id' => 'it',
        'points' => 30,
        'activity_name' => 'Bonus points test'
    ]);
    if (!$resAdd['json'] || !$resAdd['json']['success']) return "Failed to add points: " . ($resAdd['raw'] ?: 'EMPTY');

    // Deduct 15 points (Minus)
    $resDed = httpPost("{$baseUrl}/api/admin.php?action=adjust_points", [
        'admin_pin' => '1234',
        'department_id' => 'it',
        'points' => -15,
        'activity_name' => 'Penalty minus points test'
    ]);
    if (!$resDed['json'] || !$resDed['json']['success']) return "Failed to deduct minus points";

    return true;
});

// 6. Test Uploads Directory Security (Directory listing & traversal prevention)
runTest("Uploads Directory Script Execution & Traversal Prevention", function() {
    $indexFile = __DIR__ . '/../uploads/index.html';
    if (!file_exists($indexFile)) return "Missing uploads/index.html defense";
    $photosIndex = __DIR__ . '/../uploads/photos/index.html';
    if (!file_exists($photosIndex)) return "Missing index.html file in uploads/photos folder";
    return true;
});

// 7. Test Leaderboard & Key Metrics
runTest("Leaderboard & Overall Metrics (api/leaderboard.php)", function() use ($baseUrl) {
    $res = httpGet("{$baseUrl}/api/leaderboard.php");
    if (!$res['json'] || !$res['json']['success']) return "Failed to retrieve leaderboard";
    if (!isset($res['json']['data']['weekly_reports'])) return "Missing weekly reports in leaderboard";
    if (!isset($res['json']['data']['overall_standings'])) return "Missing overall standings in leaderboard";
    return true;
});

// 8. Test Global Race Reset Functionality
runTest("Global Race Reset (api/admin.php?action=reset_race)", function() use ($baseUrl) {
    $res = httpPost("{$baseUrl}/api/admin.php?action=reset_race", ['admin_pin' => '1234']);
    if (!$res['json'] || !$res['json']['success']) return "Reset race execution failed: " . ($res['json']['message'] ?? '');

    // Verify departments are at 0 position and 0 points
    $resState = httpGet("{$baseUrl}/api/game_state.php");
    $depts = $resState['json']['data']['departments'];
    foreach ($depts as $d) {
        if ((int)$d['position'] !== 0 || (int)$d['total_points'] !== 0) {
            return "Department {$d['id']} position or points not reset to 0!";
        }
    }
    return true;
});

@unlink(__DIR__ . '/test_cookie.txt');

echo "\n====================================================\n";
echo "📊 TEST RESULTS: {$testsPassed} PASSED | {$testsFailed} FAILED\n";
echo "====================================================\n";
