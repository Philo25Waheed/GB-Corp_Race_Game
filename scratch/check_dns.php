<?php
$hosts = [
    'sql103.infinityfree.com',
    'sql101.infinityfree.com',
    'sql102.infinityfree.com',
    'sql200.infinityfree.com',
    'sql201.infinityfree.com',
    'sql300.infinityfree.com',
    'sql301.infinityfree.com',
    'sql302.infinityfree.com',
    'sql303.infinityfree.com',
    'sql305.infinityfree.com',
    'sql310.infinityfree.com',
];

foreach ($hosts as $h) {
    $ip = gethostbyname($h);
    echo "$h => " . ($ip !== $h ? $ip : "NOT FOUND") . "\n";
}
