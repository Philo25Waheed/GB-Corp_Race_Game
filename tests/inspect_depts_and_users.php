<?php
require_once __DIR__ . '/../db.php';
$pdo = getDBConnection();
$depts = $pdo->query("SELECT id, name_en, name_ar, code, color, car_emoji FROM `departments`")->fetchAll();
echo "CURRENT DEPARTMENTS:\n";
print_r($depts);

$users = $pdo->query("SELECT id, name, email, department_id, role FROM `users`")->fetchAll();
echo "CURRENT USERS:\n";
print_r($users);
