<?php
/**
 * Migrační skript pro opravu schématu consumption_log a filaments tabulek
 * - Přidá sloupec current_weight do filaments (pokud neexistuje)
 * - Opraví názvy sloupců v consumption_log (consumed_weight -> amount_grams, note -> description)
 * - Inicializuje current_weight na základě initial_weight_grams a consumption_log
 */

require_once __DIR__ . '/../../config.php';

echo "=== Migrace schématu consumption_log a filaments ===\n\n";

try {
    // 1. Zkontrolovat a přidat current_weight do filaments
    echo "1. Kontrola sloupce current_weight v tabulce filaments...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM filaments LIKE 'current_weight'");
    if ($stmt->rowCount() == 0) {
        echo "   Přidávám sloupec current_weight...\n";
        $pdo->exec("ALTER TABLE filaments ADD COLUMN current_weight INT NOT NULL DEFAULT 0 AFTER initial_weight_grams");
        echo "   ✓ Sloupec current_weight přidán\n";

        // Inicializovat current_weight na základě initial_weight_grams
        echo "   Inicializuji current_weight na hodnotu initial_weight_grams...\n";
        $pdo->exec("UPDATE filaments SET current_weight = initial_weight_grams");

        // Odečíst čerpání z consumption_log
        echo "   Odečítám čerpání z consumption_log...\n";
        $stmt = $pdo->query("
            UPDATE filaments f
            SET f.current_weight = f.initial_weight_grams + COALESCE((
                SELECT SUM(cl.amount_grams)
                FROM consumption_log cl
                WHERE cl.filament_id = f.id
            ), 0)
        ");
        echo "   ✓ current_weight inicializován\n";
    } else {
        echo "   ✓ Sloupec current_weight již existuje\n";
    }

    // 2. Zkontrolovat a opravit consumption_log sloupce
    echo "\n2. Kontrola sloupců v tabulce consumption_log...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM consumption_log");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Zkontrolovat consumed_weight -> amount_grams
    if (in_array('consumed_weight', $columns) && !in_array('amount_grams', $columns)) {
        echo "   Přejmenovávám consumed_weight -> amount_grams...\n";
        $pdo->exec("ALTER TABLE consumption_log CHANGE COLUMN consumed_weight amount_grams INT NOT NULL");
        echo "   ✓ Sloupec přejmenován\n";
    } else if (!in_array('amount_grams', $columns)) {
        echo "   Přidávám sloupec amount_grams...\n";
        $pdo->exec("ALTER TABLE consumption_log ADD COLUMN amount_grams INT NOT NULL AFTER filament_id");
        // Pokud existuje consumed_weight, zkopírovat hodnoty
        if (in_array('consumed_weight', $columns)) {
            $pdo->exec("UPDATE consumption_log SET amount_grams = consumed_weight");
            $pdo->exec("ALTER TABLE consumption_log DROP COLUMN consumed_weight");
        }
        echo "   ✓ Sloupec amount_grams přidán\n";
    } else {
        echo "   ✓ Sloupec amount_grams již existuje\n";
    }

    // Zkontrolovat note -> description
    if (in_array('note', $columns) && !in_array('description', $columns)) {
        echo "   Přejmenovávám note -> description...\n";
        $pdo->exec("ALTER TABLE consumption_log CHANGE COLUMN note description TEXT");
        echo "   ✓ Sloupec přejmenován\n";
    } else if (!in_array('description', $columns)) {
        echo "   Přidávám sloupec description...\n";
        $pdo->exec("ALTER TABLE consumption_log ADD COLUMN description TEXT AFTER amount_grams");
        // Pokud existuje note, zkopírovat hodnoty
        if (in_array('note', $columns)) {
            $pdo->exec("UPDATE consumption_log SET description = note");
            $pdo->exec("ALTER TABLE consumption_log DROP COLUMN note");
        }
        echo "   ✓ Sloupec description přidán\n";
    } else {
        echo "   ✓ Sloupec description již existuje\n";
    }

    // 3. Rekalkulovat current_weight pro všechny filamenty
    echo "\n3. Rekalkulace current_weight pro všechny filamenty...\n";
    $pdo->exec("
        UPDATE filaments f
        SET f.current_weight = f.initial_weight_grams + COALESCE((
            SELECT SUM(cl.amount_grams)
            FROM consumption_log cl
            WHERE cl.filament_id = f.id
        ), 0)
    ");
    echo "   ✓ current_weight rekalkulován\n";

    echo "\n✅ Migrace úspěšně dokončena!\n";

} catch (PDOException $e) {
    echo "\n❌ Chyba: " . $e->getMessage() . "\n";
    exit(1);
}
