<?php
require_once __DIR__ . '/../db.php';
$pdo = getDBConnection();

echo "Testing Admin Access Control...\n";

// 1. Unauthenticated request to admin.php
$htmlUnauth = file_get_contents('http://localhost/RaceGameOld/admin.php');
echo "1. Unauthenticated request: " . (strpos($htmlUnauth, 'هذه اللوحة مخصصة فقط للمستخدمين الذين يملكون حساباً وتم التحقق') !== false ? "PROPERLY BLOCKED" : "FAILED") . "\n";

// 2. Create regular user and admin user
$pdo->exec("DELETE FROM users WHERE email IN ('regular@test.com', 'boss@test.com')");

// Register regular user
$ch = curl_init('http://localhost/RaceGameOld/api/auth.php?action=register');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$cookieReg = tempnam(sys_get_temp_dir(), 'ck_reg_');
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieReg);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'name' => 'Regular Player',
    'email' => 'regular@test.com',
    'department_id' => 'it',
    'password' => '123456'
]));
$resReg = json_decode(curl_exec($ch), true);

// Visit admin.php with regular player cookies
curl_setopt($ch, CURLOPT_URL, 'http://localhost/RaceGameOld/admin.php');
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieReg);
$htmlReg = curl_exec($ch);
echo "2. Regular Player on admin.php: " . (strpos($htmlReg, 'غير مصرح لك بالدخول') !== false ? "PROPERLY RESTRICTED" : "FAILED") . "\n";

// Register verified admin user with key admin123
$cookieAdmin = tempnam(sys_get_temp_dir(), 'ck_adm_');
curl_setopt($ch, CURLOPT_URL, 'http://localhost/RaceGameOld/api/auth.php?action=register');
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieAdmin);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'name' => 'Verified Boss',
    'email' => 'boss@test.com',
    'department_id' => 'it',
    'password' => '123456',
    'is_admin' => true,
    'admin_verify_password' => 'admin123'
]));
$resAdmin = json_decode(curl_exec($ch), true);

// Visit admin.php with admin cookies
curl_setopt($ch, CURLOPT_URL, 'http://localhost/RaceGameOld/admin.php');
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieAdmin);
$htmlAdmin = curl_exec($ch);
echo "3. Verified Admin on admin.php: " . (strpos($htmlAdmin, 'admin-dashboard-app') !== false ? "GRANTED FULL ACCESS" : "FAILED") . "\n";

// Cleanup
$pdo->exec("DELETE FROM users WHERE email IN ('regular@test.com', 'boss@test.com')");
@unlink($cookieReg);
@unlink($cookieAdmin);
echo "4. Test completed and cleaned.\n";
