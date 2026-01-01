<?php
require_once __DIR__ . '/../../config.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    
    // Get active inventory: Either owned OR shared
    // For MVP we pick the first one available (Owned preferred, then Shared)
    // In full version, user would "switch" active inventory in session
    
    $sqlInv = "
        SELECT i.id, i.owner_id, i.name, 'owner' as role 
        FROM inventories i 
        WHERE i.owner_id = ?
        UNION
        SELECT i.id, i.owner_id, i.name, ia.permission as role
        FROM inventories i
        JOIN inventory_access ia ON i.id = ia.inventory_id
        WHERE ia.access_code IN (SELECT access_code FROM inventory_access WHERE inventory_id = i.id) 
        -- NOTE: The schema is a bit ambiguous on linking user to access. 
        -- Usually 'inventory_access' links 'inventory_id' and 'user_email' or similar. 
        -- But Blueprint says: 'access_code (unique hash)'.
        -- It implies: User enters code -> We verify code -> We grant access.
        -- BUT: We need to store WHO has access. 
        -- The current schema 'inventory_access' has NO user_id column!
        -- It only has: id, inventory_id, access_code, permission.
        -- This means 'inventory_access' defines the CODE, not the USER relation.
        -- Wait, looking at schema again...
        -- 'inventory_access': id, inventory_id, access_code, permission.
        -- There is NO table linking user_id to inventory_access!
        -- ERROR IN BLUEPRINT/SCHEMA: We need a way to link a User to an Inventory via the Code they used.
        -- FIX: I will add a 'inventory_users' table or similar, OR add 'user_id' to 'inventory_access' (but blueprint says access_code is unique hash for URL, implying one code per share link?).
        -- Let's assume 'inventory_access' is the DEFINITION of the invite.
        -- And we need a table 'user_inventory_map' or we just add 'user_id' to 'inventory_access' to represent 'User X has Access Y'.
        -- BUT Blueprint says 'Pozvaný uživatel se může do evidence dostat zadáním kódu'.
        -- Interpretation: When user enters code, we create a record linking them.
        -- Let's MODIFY schema to add 'user_inventory' table for M:N relation, OR just add 'user_id' to inventory_access and treat it as 'One record per user access'.
        -- If access_code is UNIQUE, then it's a single share link? No, 'unique hash pro URL'.
        -- Let's go with: There is a 'inventory_shares' (definitions) and 'inventory_users' (members).
        -- SINCE I CANNOT CHANGE BLUEPRINT EASILY, I will use a pragmatic approach:
        -- I will add a table 'inventory_members' to link users to inventories.
    ";
    
    // Correction: I will check if I can modify schema slightly to make it work.
    // I will add `inventory_members` table.
    // But first, let's fix the schema in a migration style or just update it since it's dev.
    
    // RETRY LOGIC:
    // 1. Fetch inventories owned by user.
    // 2. Fetch inventories where user is a member (new table needed).
    
    // I need to create `inventory_members` table first.
    // But since I cannot interactive shell easily, I will run a SQL command via PHP or Shell.
    
    // Let's stick to the Plan. I'll update schema first.
    // Actually, looking at `inventory_access` in schema:
    // `access_code VARCHAR(255) UNIQUE NOT NULL`
    // This looks like a "Share Link Definition". 
    // "permission" is what that link grants.
    // So if I have code 'ABC', anyone with 'ABC' gets that permission.
    // So I need a table `inventory_users` (user_id, inventory_id).
    
    // Let's use the `inventory_access` as the "Invitation". 
    // And create `inventory_members` for the actual relation.
    
    // WAIT. If I look at typical PHP simple apps.
    // Maybe `inventory_access` WAS intended to be the member table?
    // "Pozvaný uživatel se může do evidence dostat zadáním kódu"
    // -> He enters code, we find the `inventory_access` row (invitation), verify it, and then INSERT into `inventory_members`.
    
    // OK, I will create `inventory_members` table now via a temp script.

    // Return to list.php logic assuming `inventory_members` exists.
    $sql = "
        SELECT i.id, i.name, 'owner' as role
        FROM inventories i
        WHERE i.owner_id = ?
        UNION
        SELECT i.id, i.name, im.role
        FROM inventories i
        JOIN inventory_members im ON i.id = im.inventory_id
        WHERE im.user_id = ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $userId]);
    $invs = $stmt->fetchAll();
    
    if (count($invs) === 0) { echo json_encode([]); exit; }
    
    // For now, pick the first one
    $invId = $invs[0]['id'];
    
    // ... rest of the code (fetch filaments) ...
    $sqlFil = "
        SELECT 
            f.id, f.material as mat, f.manufacturer as man, f.color_name as color, 
            f.color_hex as hex, f.location as loc, f.price, f.seller, f.purchase_date as date,
            f.spool_type_id as spool_id, COALESCE(sl.weight_grams, 0) as spool_weight,
            (f.initial_weight_grams + COALESCE(SUM(cl.amount_grams), 0)) as g
        FROM filaments f
        LEFT JOIN consumption_log cl ON f.id = cl.filament_id
        LEFT JOIN spool_library sl ON f.spool_type_id = sl.id
        WHERE f.inventory_id = ?
        GROUP BY f.id
        ORDER BY g DESC
    ";
    
    $stmt = $pdo->prepare($sqlFil);
    $stmt->execute([$invId]);
    echo json_encode($stmt->fetchAll());

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
