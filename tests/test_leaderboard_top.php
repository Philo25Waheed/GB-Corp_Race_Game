<?php
require_once __DIR__ . '/../db.php';
$pdo = getDBConnection();

$stmtTopDepts = $pdo->query("SELECT * FROM `departments` ORDER BY `total_points` DESC, `position` DESC LIMIT 3");
$topDepts = $stmtTopDepts->fetchAll();

$stmtTopUsers = $pdo->query("
    SELECT 
        u.id, 
        u.name, 
        u.email, 
        u.department_id, 
        d.name_ar AS dept_name_ar, 
        d.name_en AS dept_name_en, 
        d.code AS dept_code,
        d.color AS dept_color, 
        d.car_emoji,
        (
            COALESCE((SELECT SUM(qa.points_earned) FROM `quiz_attempts` qa WHERE qa.user_id = u.id), 0) +
            COALESCE((SELECT SUM(ps.points_awarded) FROM `photo_submissions` ps WHERE ps.user_id = u.id AND ps.status = 'approved'), 0)
        ) AS total_score,
        (
            SELECT COUNT(*) FROM `quiz_attempts` qa WHERE qa.user_id = u.id AND qa.is_correct = 1
        ) AS correct_answers_count
    FROM `users` u
    LEFT JOIN `departments` d ON u.department_id = d.id
    ORDER BY total_score DESC, correct_answers_count DESC, u.id ASC
    LIMIT 3
");
$topUsers = $stmtTopUsers->fetchAll();

echo "TOP 3 DEPTS:\n";
foreach ($topDepts as $i => $d) {
    echo ($i+1) . ". {$d['name_ar']} ({$d['name_en']}) - {$d['total_points']} pts\n";
}

echo "\nTOP 3 USERS:\n";
foreach ($topUsers as $i => $u) {
    echo ($i+1) . ". {$u['name']} ({$u['dept_name_ar']}) - {$u['total_score']} pts\n";
}
