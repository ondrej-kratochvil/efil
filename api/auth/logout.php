<?php
declare(strict_types=1);

session_start();

// Get session name and cookie params BEFORE any changes
$sessionName = session_name();
$cookieParams = session_get_cookie_params();

// Unset all session variables
$_SESSION = array();

// Delete the session cookie FIRST (before session_destroy)
// PHP 7.3+: use options array (third parameter) so samesite is applied; do not mix with legacy positional params
if (ini_get("session.use_cookies")) {
    $options = [
        'expires' => time() - 3600,
        'path' => $cookieParams['path'],
        'domain' => $cookieParams['domain'],
        'secure' => $cookieParams['secure'],
        'httponly' => $cookieParams['httponly'],
        'samesite' => $cookieParams['samesite'] ?? 'Lax',
    ];
    setcookie($sessionName, '', $options);
}

// Destroy the session
session_destroy();

header('Content-Type: application/json');
echo json_encode(['message' => 'Logged out']);
