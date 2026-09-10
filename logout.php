<?php
/**
 * Instant Logout Handler
 * Completely destroys user session & authentication cookies,
 * then immediately redirects directly to the main home page.
 */
require_once __DIR__ . '/db.php';

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

header("Location: index.php");
exit;
