<?php
// Simulate Live Environment
$_SERVER['HTTP_HOST'] = 'gb-crop.ct.ws';
$_SERVER['REQUEST_URI'] = '/api/quiz.php?action=get_questions';
$_SERVER['SCRIPT_FILENAME'] = 'C:/xampp/htdocs/RaceGameOld/api/quiz.php';
$_SERVER['HTTP_ACCEPT'] = '*/*'; // Standard browser fetch header!

// Override with failing credentials
putenv('LIVE_ENV=1');

ob_start();
include __DIR__ . '/../api/quiz.php';
$out = ob_get_clean();

echo "FAILED DB OUTPUT:\n" . $out . "\n";
