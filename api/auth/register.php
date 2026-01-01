<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Helper to send JSON response
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['email']) || !isset($input['password'])) {
    jsonResponse(['error' => 'Missing email or password'], 400);
}

$email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
$password = $input['password'];

if (!$email) {
    jsonResponse(['error' => 'Invalid email format'], 400);
}

if (strlen($password) < 8) {
    jsonResponse(['error' => 'Password must be at least 8 characters'], 400);
}

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'User already exists'], 409);
    }

    // Hash password
    $hash = password_hash($password, PASSWORD_BCRYPT);

    // Insert user
    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'user')");
    $stmt->execute([$email, $hash]);
    $userId = $pdo->lastInsertId();

    // Create default inventory for the user
    $stmt = $pdo->prepare("INSERT INTO inventories (owner_id, name) VALUES (?, ?)");
    $stmt->execute([$userId, 'Můj sklad']);
    $inventoryId = $pdo->lastInsertId();

    // Grant access to self (optional explicit record, usually ownership implies access, but let's follow schema logic if needed)
    // Blueprint implies ownership via inventory table, but let's stick to minimal for now.

    jsonResponse(['message' => 'User registered successfully', 'user_id' => $userId], 201);

} catch (Exception $e) {
    jsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
