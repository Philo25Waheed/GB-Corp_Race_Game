<?php
echo "Testing InfinityFree remote MySQL connection...\n";
try {
    $pdo = new PDO('mysql:host=sql103.infinityfree.com;port=3306;dbname=if0_42882264_race_db;charset=utf8mb4', 'if0_42882264', 'Sx415lrC474', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 6
    ]);
    echo "SUCCESS: Connected to sql103.infinityfree.com!\n";
} catch (Exception $e) {
    echo "FAILURE: " . $e->getMessage() . "\n";
}
