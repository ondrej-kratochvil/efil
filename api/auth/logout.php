<?php
session_start();

// Get session name and cookie params BEFORE any changes
$sessionName = session_name();
$cookieParams = session_get_cookie_params();

// Unset all session variables
$_SESSION = array();

// Delete the session cookie FIRST (before session_destroy)
// Use the exact same parameters as when the cookie was set
if (ini_get("session.use_cookies")) {
    setcookie(
        $sessionName,
        '',
        time() - 3600,
        $cookieParams["path"],
        $cookieParams["domain"],
        $cookieParams["secure"],
        $cookieParams["httponly"]
    );
}

// Destroy the session
session_destroy();

header('Content-Type: application/json');
echo json_encode(['message' => 'Logged out']);
