<?php
require_once __DIR__ . '/../db.php';
$pdo = getDBConnection();

echo "Starting Auth Flow Verification...\n";

// 1. Regular register
$ch = curl_init('http://localhost/RaceGameOld/api/auth.php?action=register');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'name' => 'Sara Test',
    'email' => 'sara@test.com',
    'department_id' => 'finance',
    'password' => 'secret123'
]));
$res = json_decode(curl_exec($ch), true);
echo "1. Register user: " . ($res['success'] ? "SUCCESS" : "FAILED: " . ($res['message'] ?? '')) . "\n";

// 2. Admin with wrong key
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'name' => 'Admin Wrong',
    'email' => 'admin_wrong@test.com',
    'department_id' => 'it',
    'password' => 'secret123',
    'is_admin' => true,
    'admin_verify_password' => 'wrongcode'
]));
$resAdminWrong = json_decode(curl_exec($ch), true);
echo "2. Admin with wrong key: " . (!$resAdminWrong['success'] ? "PROPERLY REJECTED (" . $resAdminWrong['message'] . ")" : "UNEXPECTED SUCCESS") . "\n";

// 3. Admin with correct key
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'name' => 'Admin Correct',
    'email' => 'admin_correct@test.com',
    'department_id' => 'it',
    'password' => 'secret123',
    'is_admin' => true,
    'admin_verify_password' => 'admin123'
]));
$resAdminOk = json_decode(curl_exec($ch), true);
echo "3. Admin with correct key: " . ($resAdminOk['success'] && $resAdminOk['data']['is_admin'] ? "SUCCESS WITH ADMIN ROLE" : "FAILED") . "\n";

// 4. Login with registered user
curl_setopt($ch, CURLOPT_URL, 'http://localhost/RaceGameOld/api/auth.php?action=login');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'sara@test.com',
    'password' => 'secret123'
]));
$resLogin = json_decode(curl_exec($ch), true);
echo "4. Login user: " . ($resLogin['success'] ? "SUCCESS (" . $resLogin['data']['user']['name'] . ")" : "FAILED: " . ($resLogin['message'] ?? '')) . "\n";

// 5. Cleanup test data
$pdo->exec("DELETE FROM users WHERE email IN ('sara@test.com', 'admin_wrong@test.com', 'admin_correct@test.com')");
echo "5. Cleanup completed successfully.\n";
