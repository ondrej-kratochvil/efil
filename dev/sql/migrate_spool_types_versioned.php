<?php
declare(strict_types=1);

/**
 * Migrace typů cívek (spool_library) na verzovanou tabulku spool_types:
 * soft delete, public, schvalování (stejný vzor jako manufacturers).
 *
 * - spool_types: id (řádek/verze), spool_type_id (kořen), weight_grams, color, material, ...
 *   public, approved, created_at, created_by, invalidated_at, invalidated_by
 * - filaments.spool_type_id a spool_manufacturer.spool_id zůstávají jako logické id (odpovídají spool_type_id)
 * - Odstraní se FK filaments -> spool_library a spool_manufacturer -> spool_library
 *
 * Spusťte jednou na existující databázi. Před spuštěním záloha DB.
 */

require_once __DIR__ . '/../../config.php';

echo "Migrating spool_library to versioned spool_types...\n";

try {
    // 1. Zjistit, zda už migrace proběhla (tabulka spool_types existuje)
    $stmt = $pdo->query("SHOW TABLES LIKE 'spool_types'");
    if ($stmt && $stmt->rowCount() > 0) {
        echo "Migration already applied (spool_types exists). Skipping.\n";
        exit(0);
    }

    // 2. Zkontrolovat, že spool_library existuje
    $stmt = $pdo->query("SHOW TABLES LIKE 'spool_library'");
    if (!$stmt || $stmt->rowCount() === 0) {
        echo "spool_library not found. Nothing to migrate.\n";
        exit(0);
    }

    $pdo->beginTransaction();

    // 3. Vytvořit novou tabulku spool_types
    $pdo->exec("
        CREATE TABLE spool_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            spool_type_id INT NOT NULL COMMENT 'kořen – společné pro všechny verze',
            weight_grams INT NULL,
            color VARCHAR(50) NULL,
            material VARCHAR(50) NULL,
            outer_diameter_mm INT NULL,
            width_mm INT NULL,
            visual_description TEXT NULL,
            public TINYINT(1) NOT NULL DEFAULT 0,
            approved TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_by INT NOT NULL,
            invalidated_at DATETIME NULL DEFAULT NULL,
            invalidated_by INT NULL DEFAULT NULL,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
            FOREIGN KEY (invalidated_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_spool_type_id (spool_type_id),
            INDEX idx_valid_approved (spool_type_id, approved, invalidated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC
    ");
    echo "Created spool_types.\n";

    // 4. Systémový uživatel a množina platných user ID (created_by musí odkazovat na existujícího uživatele)
    $stmt = $pdo->query("SELECT id FROM users ORDER BY id ASC LIMIT 1");
    $systemUserId = $stmt->fetchColumn();
    if (!$systemUserId) {
        throw new RuntimeException('No users in database. Create at least one user before migration.');
    }
    $systemUserId = (int) $systemUserId;
    $validUserIds = array_flip($pdo->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN));

    // 5. Přesunout data: každý řádek spool_library -> jeden řádek spool_types, spool_type_id = staré id
    $stmtOld = $pdo->query("SELECT id, weight_grams, color, material, outer_diameter_mm, width_mm, visual_description, created_by, created_at FROM spool_library ORDER BY id");
    $stmtIns = $pdo->prepare("
        INSERT INTO spool_types (spool_type_id, weight_grams, color, material, outer_diameter_mm, width_mm, visual_description, public, approved, created_at, created_by, invalidated_at, invalidated_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, COALESCE(?, NOW()), ?, NULL, NULL)
    ");
    while ($row = $stmtOld->fetch(PDO::FETCH_ASSOC)) {
        $oldId = (int) $row['id'];
        $oldCreatedBy = $row['created_by'] !== null ? (int) $row['created_by'] : null;
        $createdBy = ($oldCreatedBy !== null && isset($validUserIds[$oldCreatedBy])) ? $oldCreatedBy : $systemUserId;
        $public = $oldCreatedBy === null ? 1 : 0; // standardní = veřejný, uživatelský = soukromý
        $createdAt = $row['created_at'] ?? null;
        $stmtIns->execute([
            $oldId,
            $row['weight_grams'] !== null ? (int) $row['weight_grams'] : null,
            $row['color'] ?? null,
            $row['material'] ?? null,
            $row['outer_diameter_mm'] !== null ? (int) $row['outer_diameter_mm'] : null,
            $row['width_mm'] !== null ? (int) $row['width_mm'] : null,
            $row['visual_description'] ?? null,
            $public,
            $createdAt,
            $createdBy,
        ]);
    }
    echo "Migrated spool_library rows to spool_types.\n";

    // 6. Odstranit FK od filaments a spool_manufacturer na spool_library
    $fkStmt = $pdo->query("
        SELECT TABLE_NAME, CONSTRAINT_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
          AND REFERENCED_TABLE_NAME = 'spool_library'
    ");
    while ($fkStmt && ($fkRow = $fkStmt->fetch(PDO::FETCH_ASSOC))) {
        $table = $fkRow['TABLE_NAME'];
        $constraint = $fkRow['CONSTRAINT_NAME'];
        $pdo->exec("ALTER TABLE `" . str_replace('`', '``', $table) . "` DROP FOREIGN KEY `" . str_replace('`', '``', $constraint) . "`");
        echo "Dropped FK {$constraint} from {$table}.\n";
    }

    // 7. Smazat starou tabulku (DDL v MySQL implicitne commitne – pred commit() zkontrolovat inTransaction())
    $pdo->exec("DROP TABLE spool_library");
    echo "Dropped spool_library.\n";

    if ($pdo->inTransaction()) {
        $pdo->commit();
    }
    echo "Migration completed successfully.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
