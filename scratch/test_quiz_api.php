<?php
$_GET['action'] = 'get_questions';
$_GET['week_id'] = '1';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../api/quiz.php';
$_SERVER['HTTP_ACCEPT'] = 'application/json';

ob_start();
include __DIR__ . '/../api/quiz.php';
$out = ob_get_clean();

echo "QUIZ API OUTPUT:\n" . $out . "\n";
