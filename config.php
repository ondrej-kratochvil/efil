<?php

// Load environment variables if .env exists
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
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
