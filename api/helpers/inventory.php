<?php
declare(strict_types=1);

/**
 * Inventory helper: resolve active inventory and manage-access for the current user.
 */

/**
 * Ensures the user has manage access to the inventory (owner, manage role, or admin_efil).
 * Sends 404/403 JSON response and exits on failure.
 *
 * @param PDO $pdo Database connection
 * @param int $inventoryId Inventory ID
 * @param int $userId Current user ID
 * @return array Inventory row (id, owner_id, name, is_admin) for use by caller
 */
function requireInventoryManageAccess(PDO $pdo, int $inventoryId, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT i.id, i.owner_id, i.name, im.role AS member_role
        FROM inventories i
        LEFT JOIN inventory_members im ON im.inventory_id = i.id AND im.user_id = ?
        WHERE i.id = ?
    ");
    $stmt->execute([$userId, $inventoryId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Evidence nenalezena']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = $user && $user['role'] === 'admin_efil';
    $isOwner = (int) $row['owner_id'] === $userId;
    $isManage = $row['member_role'] === 'manage';

    if (!$isOwner && !$isAdmin && !$isManage) {
        http_response_code(403);
        echo json_encode(['error' => 'Nedostatečná oprávnění']);
        exit;
    }

    return [
        'id' => (int) $row['id'],
        'owner_id' => (int) $row['owner_id'],
        'name' => $row['name'],
        'is_admin' => $isAdmin,
    ];
}

/**
 * Returns the active inventory ID for the given user.
 * Uses $_SESSION['inventory_id'] if set; otherwise selects the first available
 * inventory (owned or shared) and optionally stores it in session.
 *
 * @param PDO $pdo Database connection
 * @param int $userId Current user ID
 * @param bool $updateSession If true, set $_SESSION['inventory_id'] when resolving from DB
 * @return int|null Inventory ID or null if user has no inventory
 */
function getInventoryIdForUser(PDO $pdo, int $userId, bool $updateSession = false): ?int
{
    $inventoryId = $_SESSION['inventory_id'] ?? null;
    if ($inventoryId !== null && $inventoryId !== '') {
        return (int) $inventoryId;
    }

    $stmtInv = $pdo->prepare("
        SELECT i.id
        FROM inventories i
        WHERE i.owner_id = ?
        UNION
        SELECT i.id
        FROM inventories i
        JOIN inventory_members im ON i.id = im.inventory_id
        WHERE im.user_id = ?
        LIMIT 1
    ");
    $stmtInv->execute([$userId, $userId]);
    $inv = $stmtInv->fetch(PDO::FETCH_ASSOC);

    if (!$inv) {
        return null;
    }

    $id = (int) $inv['id'];
    if ($updateSession) {
        $_SESSION['inventory_id'] = $id;
    }
    return $id;
}
