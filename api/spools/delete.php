<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing spool ID']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    $spoolId = $data['id'];
    
    // Verify user can delete this spool (must be creator)
    $stmt = $pdo->prepare("SELECT created_by FROM spool_library WHERE id = ?");
    $stmt->execute([$spoolId]);
    $spool = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$spool) {
        http_response_code(404);
        echo json_encode(['error' => 'Typ cívky nenalezen']);
        exit;
    }
    
    if ($spool['created_by'] === null) {
        http_response_code(403);
        echo json_encode(['error' => 'Nelze smazat standardní typ cívky']);
        exit;
    }
    
    if ((int) $spool['created_by'] !== (int) $userId) {
        http_response_code(403);
        echo json_encode(['error' => 'Nemáte oprávnění smazat tento typ cívky']);
        exit;
    }
    
    // Delete spool (cascade will handle spool_manufacturer)
    $stmt = $pdo->prepare("DELETE FROM spool_library WHERE id = ?");
    $stmt->execute([$spoolId]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
