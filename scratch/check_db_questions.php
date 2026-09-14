<?php
require_once __DIR__ . '/../db.php';
$pdo = getDBConnection();
$stmt = $pdo->query("SELECT week_id, COUNT(*) as c FROM questions GROUP BY week_id");
while ($r = $stmt->fetch()) {
    echo "Week " . $r['week_id'] . ": " . $r['c'] . " questions\n";
}

$stmtWeeks = $pdo->query("SELECT id, week_number, challenge_type, is_active FROM weeks");
while ($w = $stmtWeeks->fetch()) {
    echo "Week Row " . $w['id'] . " (Week #" . $w['week_number'] . "): type=" . $w['challenge_type'] . ", active=" . $w['is_active'] . "\n";
}
