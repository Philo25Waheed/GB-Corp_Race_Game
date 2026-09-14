<?php
require_once __DIR__ . '/../db.php';
$pdo = getDBConnection();

$userStmt = $pdo->query("SELECT id, department_id FROM users LIMIT 1");
$user = $userStmt->fetch();
$userId = (int)$user['id'];
$deptId = $user['department_id'];

$qStmt = $pdo->query("SELECT id, correct_option FROM questions WHERE week_id = 1 ORDER BY id ASC LIMIT 1");
$q = $qStmt->fetch();
$qId = (int)$q['id'];
$correctOpt = $q['correct_option'];

$_SESSION['user_id'] = $userId;
$_POST = [
    'action' => 'submit_answer',
    'question_id' => $qId,
    'selected_option' => $correctOpt,
    'user_id' => $userId,
    'department_id' => $deptId
];
$_SERVER['REQUEST_METHOD'] = 'POST';

include __DIR__ . '/../api/quiz.php';
