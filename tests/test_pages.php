<?php
$pages = [
    'Home (/)' => 'http://localhost/RaceGameOld/',
    'Home (index.php)' => 'http://localhost/RaceGameOld/index.php',
    'Sign In' => 'http://localhost/RaceGameOld/login.php',
    'Register' => 'http://localhost/RaceGameOld/register.php',
    'Game (game.php)' => 'http://localhost/RaceGameOld/game.php'
];

foreach ($pages as $name => $url) {
    $html = file_get_contents($url);
    echo "=== $name ===\n";
    if ($name === 'Home (/)' || $name === 'Home (index.php)') {
        echo (strpos($html, 'The school bell is ringing') !== false ? '✅ Slogan OK' : '❌ Slogan missing') . "\n";
        echo (strpos($html, 'جرس المدرسة يدق') !== false ? '✅ Arabic Slogan OK' : '❌ Arabic missing') . "\n";
        echo (strpos($html, 'login.php') !== false ? '✅ Sign In button OK' : '❌ Sign In button missing') . "\n";
        echo (strpos($html, 'register.php') !== false ? '✅ Register button OK' : '❌ Register button missing') . "\n";
    } elseif ($name === 'Sign In') {
        echo (strpos($html, 'form-login') !== false ? '✅ Form Login OK' : '❌ Form missing') . "\n";
        echo (strpos($html, 'btn-lets-start') !== false ? '✅ Lets start modal OK' : '❌ modal missing') . "\n";
    } elseif ($name === 'Register') {
        echo (strpos($html, 'form-register') !== false ? '✅ Form Register OK' : '❌ Form missing') . "\n";
        echo (strpos($html, 'admin_verify_password') !== false ? '✅ Admin verify OK' : '❌ Admin verify missing') . "\n";
    } elseif ($name === 'Game (game.php)') {
        echo (strpos($html, 'track-section') !== false ? '✅ Track Section OK' : '❌ Track missing') . "\n";
    }
}
