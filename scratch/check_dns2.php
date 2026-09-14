<?php
$prefixes = ['sql100', 'sql101', 'sql102', 'sql103', 'sql105', 'sql108', 'sql200', 'sql201', 'sql205', 'sql300', 'sql301', 'sql302', 'sql303', 'sql305', 'sql308', 'sql310', 'sql312'];
$domains = ['epizy.com', 'byetcluster.com'];

foreach ($domains as $d) {
    foreach ($prefixes as $p) {
        $host = "$p.$d";
        $ip = gethostbyname($host);
        if ($ip !== $host) {
            echo "FOUND: $host => $ip\n";
            break;
        }
    }
}
