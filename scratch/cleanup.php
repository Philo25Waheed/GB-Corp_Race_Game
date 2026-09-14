<?php
require_once __DIR__ . '/../db.php';
$pdo = getDBConnection();
$pdo->exec("DELETE FROM quiz_attempts WHERE user_id = 49 AND question_id = 1");
$pdo->exec("UPDATE departments SET position = 0, total_points = 0 WHERE id = 'crm_complaints'");
echo "CLEANED OK\n";
