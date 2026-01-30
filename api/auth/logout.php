<?php
declare(strict_types=1);

session_start();

// Get session name and cookie params BEFORE any changes
$sessionName = session_name();
$cookieParams = session_get_cookie_params();

// Unset all session variables
$_SESSION = array();

// Delete the session cookie FIRST (before session_destroy)
// Use the same options as when the cookie was set (path, domain, secure, httponly, samesite)
if (ini_get("session.use_cookies")) {
    $options = [
        'expires' => time() - 3600,
        'path' => $cookieParams['path'],
        'domain' => $cookieParams['domain'],
        'secure' => $cookieParams['secure'],
        'httponly' => $cookieParams['httponly'],
    ];
    if (isset($cookieParams['samesite'])) {
        $options['samesite'] = $cookieParams['samesite'];
    }
    setcookie($sessionName, '', $options);
}

// Destroy the session
session_destroy();

header('Content-Type: application/json');
echo json_encode(['message' => 'Logged out']);
