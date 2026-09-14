<?php
/**
 * Leaderboard & Weekly Top Department API
 * Computes weekly department winners and overall fleet race rankings
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

$pdo = getDBConnection();

// 1. Fetch All Weeks
$stmtWeeks = $pdo->query("SELECT * FROM `weeks` ORDER BY `week_number` ASC");
$weeks = $stmtWeeks->fetchAll();

// 2. Fetch Departments Overall Standings
$stmtDepts = $pdo->query("SELECT * FROM `departments` ORDER BY `position` DESC, `total_points` DESC");
$departments = $stmtDepts->fetchAll();

// 3. Compute Weekly Standings & Weekly Champion Department
$weeklyReports = [];
foreach ($weeks as $w) {
    $wId = (int)$w['id'];

    $stmtWeekScores = $pdo->prepare("
        SELECT d.id AS department_id, d.name_en, d.name_ar, d.code, d.color, d.secondary_color, d.car_model, d.livery_style, d.racing_num, d.car_emoji,
               COALESCE(ws.score, 0) AS week_score,
               COALESCE(ws.is_weekly_winner, 0) AS is_declared_winner,
               (SELECT COUNT(*) FROM `quiz_attempts` qa WHERE qa.department_id = d.id AND qa.week_id = ?) AS quiz_count,
               (SELECT COUNT(*) FROM `photo_submissions` ps WHERE ps.department_id = d.id AND ps.week_id = ?) AS photo_count
        FROM `departments` d
        LEFT JOIN `weekly_scores` ws ON ws.department_id = d.id AND ws.week_id = ?
        ORDER BY week_score DESC, d.position DESC
    ");
    $stmtWeekScores->execute([$wId, $wId, $wId]);
    $deptScores = $stmtWeekScores->fetchAll();

    $topDept = !empty($deptScores) && $deptScores[0]['week_score'] > 0 ? $deptScores[0] : (!empty($deptScores) ? $deptScores[0] : null);

    $weeklyReports[] = [
        'week_id' => $w['id'],
        'week_number' => $w['week_number'],
        'title_en' => $w['title_en'],
        'title_ar' => $w['title_ar'],
        'challenge_type' => $w['challenge_type'],
        'is_active' => (bool)$w['is_active'],
        'is_completed' => (bool)$w['is_completed'],
        'winner' => $topDept,
        'department_scores' => $deptScores
    ];
}

// 4. Top 3 Departments
$stmtTopDepts = $pdo->query("SELECT * FROM `departments` ORDER BY `total_points` DESC, `position` DESC LIMIT 3");
$topDepts = $stmtTopDepts->fetchAll();

// 5. Top 3 Individual MVP Scorers
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

// PII Protection: Mask employee email addresses on public leaderboard
foreach ($topUsers as &$u) {
    if (!empty($u['email'])) {
        $parts = explode('@', $u['email']);
        $namePart = substr($parts[0], 0, 2);
        $domainPart = $parts[1] ?? 'gbcorp.com';
        $u['email'] = $namePart . '***@' . $domainPart;
    }
}
unset($u);

// 6. Overall Key Stats
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
$totalPhotos = (int)$pdo->query("SELECT COUNT(*) FROM `photo_submissions`")->fetchColumn();
$totalQuizAttempts = (int)$pdo->query("SELECT COUNT(*) FROM `quiz_attempts`")->fetchColumn();
$totalMileage = (int)$pdo->query("SELECT SUM(`position`) FROM `departments`")->fetchColumn() * 100;

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

echo json_encode([
    'success' => true,
    'data' => [
        'overall_standings' => $departments,
        'top_departments' => $topDepts,
        'top_users' => $topUsers,
        'weekly_reports' => $weeklyReports,
        'stats' => [
            'total_users' => $totalUsers,
            'total_photos' => $totalPhotos,
            'total_quiz_attempts' => $totalQuizAttempts,
            'total_mileage' => $totalMileage
        ]
    ]
], JSON_UNESCAPED_UNICODE);
