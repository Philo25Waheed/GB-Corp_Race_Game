<?php
require_once __DIR__ . '/../db.php';
$pdo = getDBConnection();
$pdo->exec("UPDATE system_settings SET setting_value = '1' WHERE setting_key = 'active_week_id'");
$pdo->exec("UPDATE weeks SET is_active = IF(id=1, 1, 0)");
echo "Active week set to 1 successfully.\n";
