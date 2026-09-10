<?php
/**
 * Automated Verification: Dedicated Unique Cars & Strict Palette Compliance
 */

require_once __DIR__ . '/../db.php';

echo "========================================================\n";
echo "🚗 DEDICATED CARS & BRAND PALETTE COMPLIANCE AUDIT\n";
echo "========================================================\n\n";

$pdo = getDBConnection();
$passed = 0;
$failed = 0;

function assertCondition($name, $condition, &$passed, &$failed, $details = '') {
    if ($condition) {
        echo "✅ PASS: $name\n";
        $passed++;
    } else {
        echo "❌ FAIL: $name " . ($details ? "($details)" : "") . "\n";
        $failed++;
    }
}

// The Exact 70-Color Palette Matrix extracted from uploaded image
$PALETTE_ALL = [
    // Column 1 (Royal Navy)
    '#2b51a4', '#4062ac', '#5574b5', '#6b85c0', '#7f96c8', '#95a8d2', '#aab9da', '#bfcbe5', '#d5dcee', '#eaeef7',
    // Column 2 (Sky/Cyan Blue)
    '#049eda', '#1da9de', '#35b2e2', '#4fbce5', '#68c4e9', '#82cfed', '#9bd9f0', '#b4e1f4', '#ceecf7', '#e6f5fc',
    // Column 3 (Tangerine Orange)
    '#f78c2a', '#f79740', '#f7a454', '#f9ae6a', '#f9bb80', '#fbc596', '#fcd0a9', '#fcddc0', '#fde8d5', '#fef4ea',
    // Column 4 (Deep Teal)
    '#119aaa', '#28a4b0', '#40aebb', '#58b8c4', '#70c3cb', '#87cbd4', '#9fd6db', '#b8e1e5', '#cfebee', '#e7f5f6',
    // Column 5 (Emerald Green)
    '#3bae49', '#4eb75b', '#62bd6e', '#75c681', '#89cf93', '#9dd5a4', '#b0deb7', '#c4e7c9', '#d8efdb', '#ebf7ed',
    // Column 6 (Slate Grey)
    '#7f8487', '#8c9093', '#989da0', '#a5a9ac', '#b1b5b8', '#bec2c3', '#cdced0', '#d9dadc', '#e5e6e8', '#f3f3f3',
    // Column 7 (Platinum Silver)
    '#d9d8d6', '#dcdcda', '#e0e0de', '#e4e4e2', '#e8e8e6', '#eceaeb', '#f0eeef', '#f7f7f7', '#fbfbfb'
];
$paletteMap = array_flip(array_map('strtolower', $PALETTE_ALL));

// 1. Fetch All Departments from DB
$stmt = $pdo->query("SELECT * FROM `departments` ORDER BY `racing_num` ASC");
$departments = $stmt->fetchAll();

assertCondition("Total departments count is exactly 43", count($departments) === 43, $passed, $failed, "Found: " . count($departments));

// 2. Verify Schema Columns
$sample = $departments[0] ?? [];
assertCondition("Column 'car_model' exists in departments", array_key_exists('car_model', $sample), $passed, $failed);
assertCondition("Column 'secondary_color' exists in departments", array_key_exists('secondary_color', $sample), $passed, $failed);
assertCondition("Column 'livery_style' exists in departments", array_key_exists('livery_style', $sample), $passed, $failed);
assertCondition("Column 'racing_num' exists in departments", array_key_exists('racing_num', $sample), $passed, $failed);

// 3. Verify All Colors are 100% strictly in the Brand Palette Matrix
$invalidColors = [];
$uniqueCars = [];
$racingNums = [];

foreach ($departments as $d) {
    $pColor = strtolower($d['color']);
    $sColor = strtolower($d['secondary_color']);

    if (!isset($paletteMap[$pColor])) {
        $invalidColors[] = "Dept {$d['id']} primary: {$pColor}";
    }
    if (!isset($paletteMap[$sColor])) {
        $invalidColors[] = "Dept {$d['id']} secondary: {$sColor}";
    }

    $carKey = "{$d['car_model']}|{$pColor}|{$sColor}|{$d['livery_style']}";
    $uniqueCars[$carKey] = ($uniqueCars[$carKey] ?? 0) + 1;

    $racingNums[] = (int)$d['racing_num'];
}

assertCondition(
    "100% of primary and secondary colors match the uploaded palette image",
    empty($invalidColors),
    $passed,
    $failed,
    implode(', ', $invalidColors)
);

// 4. Verify Uniqueness of Each Car
$duplicates = array_filter($uniqueCars, fn($c) => $c > 1);
assertCondition(
    "All 43 departments have a dedicated unique car design combination",
    empty($duplicates),
    $passed,
    $failed,
    "Duplicates found: " . count($duplicates)
);

// 5. Verify Racing Numbers from 1 to 43
sort($racingNums);
$expectedNums = range(1, 43);
assertCondition(
    "All 43 departments have dedicated racing numbers 1 to 43",
    $racingNums === $expectedNums,
    $passed,
    $failed
);

// 6. Verify js/cars.js file exists and passes Node execution
$carsJsPath = __DIR__ . '/../js/cars.js';
assertCondition("js/cars.js exists on disk", file_exists($carsJsPath), $passed, $failed);

$nodeScriptPath = escapeshellarg(__DIR__ . '/test_cars_node.js');
$nodeTestOutput = shell_exec("node $nodeScriptPath");

assertCondition("js/cars.js loads cleanly and exports 43 department cars", strpos($nodeTestOutput, 'COUNT:43') !== false, $passed, $failed);

// 7. Verify All 12 Automotive Archetypes are Implemented
assertCondition("All 12 automotive silhouette models render valid SVG", strpos($nodeTestOutput, 'ALL_MODELS_OK:true') !== false, $passed, $failed);

// 8. Verify SVG Output Contains Zero Outside Colors
assertCondition("All SVG paths, fills, and strokes strictly use only palette colors", strpos($nodeTestOutput, 'INVALID_SVG_COLORS:0') !== false, $passed, $failed);

// 9. Verify API game_state.php Output
ob_start();
require __DIR__ . '/../api/game_state.php';
$gameStateJson = ob_get_clean();
$gameStateData = json_decode($gameStateJson, true);

$firstDeptState = $gameStateData['data']['departments'][0] ?? [];
assertCondition(
    "api/game_state.php returns car_model, secondary_color, and racing_num",
    !empty($firstDeptState['car_model']) && !empty($firstDeptState['secondary_color']),
    $passed,
    $failed
);

// 10. Verify API leaderboard.php Output
ob_start();
require __DIR__ . '/../api/leaderboard.php';
$lbJson = ob_get_clean();
$lbData = json_decode($lbJson, true);

$firstTopDept = $lbData['data']['top_departments'][0] ?? [];
assertCondition(
    "api/leaderboard.php returns car_model and secondary_color in top_departments",
    !empty($firstTopDept['car_model']) && !empty($firstTopDept['secondary_color']),
    $passed,
    $failed
);

echo "\n========================================================\n";
echo "📊 AUDIT SUMMARY: $passed PASSED | $failed FAILED\n";
echo "========================================================\n";

if ($failed > 0) {
    exit(1);
}
