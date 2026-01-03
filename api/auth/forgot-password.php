<?php
/**
 * Request password reset
 * POST /api/auth/forgot-password.php
 * 
 * Body: { email }
 * 
 * Sends password reset email with JWT token
 */

require_once '../../config.php';
require_once '../helpers/jwt.php';
require_once '../helpers/email.php';

header('Content-Type: application/json');

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');

// Validate input
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Neplatná emailová adresa']);
    exit;
}

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // Always return success to prevent email enumeration
    // but only send email if user exists
    if ($user) {
        // Generate password reset token (1 hour)
        $token = generateJWT([
            'email' => $email,
            'purpose' => 'password_reset'
        ], $jwtSecret, 3600);
        
        // Build reset URL
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
                   "://" . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['REQUEST_URI']));
        $resetUrl = $baseUrl . '/reset-password?token=' . $token;
        
        // Send email
        sendPasswordResetEmail($email, $resetUrl, $smtpConfig);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Pokud účet existuje, byl odeslán email s instrukcemi'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Chyba serveru']);
}
