<?php
/**
 * Game State API
 * Retrieves active week, challenge settings, department standings, weekly winner, and surprise status
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

$pdo = getDBConnection();

// Fetch System Settings
$stmtSettings = $pdo->query("SELECT `setting_key`, `setting_value` FROM `system_settings`");
$settingsRaw = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);

$activeWeekId = (int)($settingsRaw['active_week_id'] ?? 1);
$raceLength = (int)($settingsRaw['race_length'] ?? 15);
$finaleRevealed = ($settingsRaw['finale_revealed'] ?? '0') === '1';

// Fetch Active Week
$stmtActiveWeek = $pdo->prepare("SELECT * FROM `weeks` WHERE `id` = ?");
$stmtActiveWeek->execute([$activeWeekId]);
$activeWeek = $stmtActiveWeek->fetch();

if (!$activeWeek) {
    // Fallback to week 1
    $stmtActiveWeek->execute([1]);
    $activeWeek = $stmtActiveWeek->fetch();
}

// Fetch All Weeks
$stmtAllWeeks = $pdo->query("SELECT * FROM `weeks` ORDER BY `week_number` ASC");
$allWeeks = $stmtAllWeeks->fetchAll();

// Fetch Departments with Live Positions & Points
$stmtDepts = $pdo->query("SELECT * FROM `departments` ORDER BY `position` DESC, `total_points` DESC");
$departments = $stmtDepts->fetchAll();

// Calculate Weekly Scores for each week and department
$stmtWeeklyScores = $pdo->query("
    SELECT ws.week_id, ws.department_id, ws.score, ws.is_weekly_winner,
           d.name_en, d.name_ar, d.code, d.color, d.secondary_color, d.car_model, d.livery_style, d.racing_num, d.car_emoji
    FROM `weekly_scores` ws
    JOIN `departments` d ON ws.department_id = d.id
    ORDER BY ws.week_id ASC, ws.score DESC
");
$weeklyScores = $stmtWeeklyScores->fetchAll();

// Determine Weekly Top Department for each completed / active week
$weeklyWinners = [];
foreach ($allWeeks as $w) {
    $wId = (int)$w['id'];
    // Check if there is explicit weekly winner in weekly_scores, or calculate from points
    $stmtTop = $pdo->prepare("
        SELECT d.id, d.name_en, d.name_ar, d.code, d.color, d.secondary_color, d.car_model, d.livery_style, d.racing_num, d.car_emoji, 
               COALESCE(ws.score, 0) AS week_score
        FROM `departments` d
        LEFT JOIN `weekly_scores` ws ON ws.department_id = d.id AND ws.week_id = ?
        ORDER BY week_score DESC, d.position DESC
        LIMIT 1
    ");
    $stmtTop->execute([$wId]);
    $top = $stmtTop->fetch();
    $weeklyWinners[$wId] = [
        'week_number' => $w['week_number'],
        'title_en' => $w['title_en'],
        'title_ar' => $w['title_ar'],
        'challenge_type' => $w['challenge_type'],
        'is_completed' => (bool)$w['is_completed'],
        'top_department' => $top
    ];
}

// Grand Winner (Highest points or position >= raceLength)
$overallLeader = !empty($departments) ? $departments[0] : null;

// Top 3 Departments
$stmtTopDepts = $pdo->query("SELECT * FROM `departments` ORDER BY `total_points` DESC, `position` DESC LIMIT 3");
$topDepartments = $stmtTopDepts->fetchAll();

// Top 3 Individual MVP Scorers
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

// User context if session active
$currentUser = null;
if (!empty($_SESSION['user_id'])) {
    $stmtUser = $pdo->prepare("SELECT u.*, d.name_en AS dept_name_en, d.name_ar AS dept_name_ar, d.code AS dept_code, d.color AS dept_color, d.secondary_color AS dept_secondary_color, d.car_model, d.livery_style, d.racing_num, d.car_emoji, d.position, d.total_points FROM `users` u LEFT JOIN `departments` d ON u.department_id = d.id WHERE u.id = ?");
    $stmtUser->execute([$_SESSION['user_id']]);
    $currentUser = $stmtUser->fetch();
}

// Active week sub-challenge user progress (Quiz, Photo, Team Activity)
$activeWeekProgress = [
    'quiz_completed' => false,
    'quiz_answered_count' => 0,
    'quiz_total_count' => 0,
    'photo_submitted' => false,
    'team_activity_done' => false
];

if ($activeWeek && !empty($_SESSION['user_id'])) {
    $uId = (int)$_SESSION['user_id'];
    $wId = (int)$activeWeek['id'];

    // Quiz total count
    $stmtQTotal = $pdo->prepare("SELECT COUNT(*) FROM `questions` WHERE `week_id` = ?");
    $stmtQTotal->execute([$wId]);
    $qTotal = (int)$stmtQTotal->fetchColumn();
    $activeWeekProgress['quiz_total_count'] = $qTotal;

    // Quiz user attempts
    $stmtQAns = $pdo->prepare("SELECT COUNT(*) FROM `quiz_attempts` WHERE `user_id` = ? AND `week_id` = ? AND `selected_option` NOT IN ('PENDING')");
    $stmtQAns->execute([$uId, $wId]);
    $qAns = (int)$stmtQAns->fetchColumn();
    $activeWeekProgress['quiz_answered_count'] = $qAns;
    $activeWeekProgress['quiz_completed'] = ($qTotal > 0 && $qAns >= $qTotal);

    // Photo submission check
    $stmtPhoto = $pdo->prepare("SELECT COUNT(*) FROM `photo_submissions` WHERE `user_id` = ? AND `week_id` = ?");
    $stmtPhoto->execute([$uId, $wId]);
    $activeWeekProgress['photo_submitted'] = ((int)$stmtPhoto->fetchColumn() > 0);

    // Team activity check
    if ($currentUser && !empty($currentUser['department_id'])) {
        $stmtTeam = $pdo->prepare("SELECT score FROM `weekly_scores` WHERE `department_id` = ? AND `week_id` = ?");
        $stmtTeam->execute([$currentUser['department_id'], $wId]);
        $teamScore = (int)($stmtTeam->fetchColumn() ?: 0);
        $activeWeekProgress['team_activity_done'] = ($teamScore >= 30);
    }
}

echo json_encode([
    'success' => true,
    'data' => [
        'active_week' => $activeWeek,
        'active_week_progress' => $activeWeekProgress,
        'all_weeks' => $allWeeks,
        'departments' => $departments,
        'race_length' => $raceLength,
        'weekly_winners' => $weeklyWinners,
        'overall_leader' => $overallLeader,
        'finale_revealed' => $finaleRevealed,
        'finale_surprise_title_ar' => $settingsRaw['finale_surprise_title_ar'] ?? '',
        'finale_surprise_title_en' => $settingsRaw['finale_surprise_title_en'] ?? '',
        'finale_surprise_message_ar' => $settingsRaw['finale_surprise_message_ar'] ?? '',
        'finale_surprise_message_en' => $settingsRaw['finale_surprise_message_en'] ?? '',
        'user' => $currentUser,
        'top_departments' => $topDepartments,
        'top_users' => $topUsers,
        'is_admin' => !empty($_SESSION['is_admin'])
    ]
], JSON_UNESCAPED_UNICODE);
