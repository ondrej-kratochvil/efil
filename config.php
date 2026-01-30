<?php
declare(strict_types=1);

// Load environment variables if .env exists
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

$host = $_ENV['DB_HOST'] ?? 'localhost';
$db   = $_ENV['DB_NAME'] ?? 'efil_db';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$charset = 'utf8mb4';

// JWT Secret for token generation
$jwtSecret = $_ENV['JWT_SECRET'] ?? 'default-insecure-secret-change-in-production';

// SMTP Configuration
$smtpConfig = [
    'host' => $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com',
    'port' => $_ENV['SMTP_PORT'] ?? 587,
    'username' => $_ENV['SMTP_USERNAME'] ?? '',
    'password' => $_ENV['SMTP_PASSWORD'] ?? '',
    'from_email' => $_ENV['SMTP_FROM_EMAIL'] ?? 'noreply@efil.cz',
    'from_name' => $_ENV['SMTP_FROM_NAME'] ?? 'eFil - Evidence Filamentů'
];

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // In production, log this error instead of showing it
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

/**
 * Get base URL of the application (excluding /api/)
 * This function reliably constructs the base URL regardless of subdirectory installation
 *
 * @return string Base URL path (e.g., '/a/efil-github' or '')
 */
function getBaseUrl() {
    // Get the script path (e.g., '/a/efil-github/api/auth/forgot-password.php')
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';

    // Remove /api/ and everything after it
    // This works because all API scripts are under /api/
    $basePath = preg_replace('#/api/.*$#', '', $scriptPath);

    // Ensure we have a path (even if empty for root install)
    return $basePath ?: '';
}

/**
 * Get full base URL with protocol and host
 *
 * @return string Full base URL (e.g., 'https://example.com/a/efil-github')
 */
function getFullBaseUrl() {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = getBaseUrl();

    return $protocol . '://' . $host . $basePath;
}
