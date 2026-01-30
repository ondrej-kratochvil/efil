<?php
declare(strict_types=1);

/**
 * Add user to inventory
 * POST /api/users/add.php
 * 
 * Body: { email, role }
 * 
 * If user exists: adds to inventory and sends notification
 * If user doesn't exist: creates account without password and sends setup email
 */

session_start();
require_once '../../config.php';
require_once '../helpers/jwt.php';
require_once '../helpers/email.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Nepřihlášen']);
    exit;
}

$userId = $_SESSION['user_id'];
$inventoryId = $_SESSION['inventory_id'] ?? null;

if (!$inventoryId) {
    http_response_code(400);
    echo json_encode(['error' => 'Žádná aktivní evidence']);
    exit;
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');
$role = $data['role'] ?? 'read';

// Validate input
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Neplatná emailová adresa']);
    exit;
}

if (!in_array($role, ['read', 'write', 'manage'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Neplatná role']);
    exit;
}

try {
    // Check if user has manage permission
    $stmt = $pdo->prepare("
        SELECT role FROM inventory_members 
        WHERE inventory_id = ? AND user_id = ?
    ");
    $stmt->execute([$inventoryId, $userId]);
    $member = $stmt->fetch();
    
    // Check if user is owner
    $stmt = $pdo->prepare("SELECT owner_id, name FROM inventories WHERE id = ?");
    $stmt->execute([$inventoryId]);
    $inventory = $stmt->fetch();
    
    // Verify inventory exists
    if (!$inventory) {
        http_response_code(404);
        echo json_encode(['error' => 'Evidence nenalezena']);
        exit;
    }
    
    $isOwner = ((int) $inventory['owner_id'] === (int) $userId);
    
    // Check if user is admin_efil
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    $isAdmin = ($user && $user['role'] === 'admin_efil');
    
    // Only owner, manage role, or admin can add users
    if (!$isOwner && !$isAdmin && (!$member || $member['role'] !== 'manage')) {
        http_response_code(403);
        echo json_encode(['error' => 'Nedostatečná oprávnění']);
        exit;
    }
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, email, password_hash FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $targetUser = $stmt->fetch();
    
    if ($targetUser) {
        // User exists - check if already in inventory (as member)
        $stmt = $pdo->prepare("SELECT id FROM inventory_members WHERE inventory_id = ? AND user_id = ?");
        $stmt->execute([$inventoryId, $targetUser['id']]);
        
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Uživatel je již v evidenci']);
            exit;
        }
        
        // Check if user is the owner of this inventory
        if ((int) $inventory['owner_id'] === (int) $targetUser['id']) {
            http_response_code(400);
            echo json_encode(['error' => 'Vlastník inventáře nemůže být přidán jako člen']);
            exit;
        }
        
        // Add to inventory and send email in transaction (rollback if email fails)
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO inventory_members (inventory_id, user_id, role) VALUES (?, ?, ?)");
            $stmt->execute([$inventoryId, $targetUser['id'], $role]);

            $loginUrl = getFullBaseUrl();
            $emailSent = sendInventoryInvitationEmail($email, $inventory['name'], $loginUrl, $smtpConfig);
            if (!$emailSent) {
                $pdo->rollBack();
                http_response_code(503);
                echo json_encode([
                    'error' => 'Nepodařilo se odeslat e-mail s pozvánkou. Uživatel nebyl přidán. Zkontrolujte konfiguraci SMTP a zkuste to znovu.'
                ]);
                exit;
            }

            $pdo->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Uživatel přidán',
                'user_existed' => true
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } else {
        // User doesn't exist - create account without password (transaction: rollback if email fails)
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, '', 'user')");
            $stmt->execute([$email]);
            $newUserId = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO inventory_members (inventory_id, user_id, role) VALUES (?, ?, ?)");
            $stmt->execute([$inventoryId, $newUserId, $role]);

            $token = generateJWT(['email' => $email, 'purpose' => 'setup_password'], $jwtSecret, 86400);
            $baseUrl = getFullBaseUrl();
            $setupUrl = $baseUrl . '/reset-password?token=' . $token;

            $emailSent = sendNewAccountEmail($email, $inventory['name'], $setupUrl, $smtpConfig);
            if (!$emailSent) {
                $pdo->rollBack();
                http_response_code(503);
                echo json_encode([
                    'error' => 'Nepodařilo se odeslat e-mail s odkazem pro nastavení hesla. Účet nebyl vytvořen. Zkontrolujte konfiguraci SMTP a zkuste to znovu.'
                ]);
                exit;
            }

            $pdo->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Účet vytvořen a uživatel přidán',
                'user_existed' => false
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Chyba databáze: ' . $e->getMessage()]);
}
