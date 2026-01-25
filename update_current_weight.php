<?php
/**
 * Migrační skript pro přidání sloupce current_weight do tabulky filaments
 * Tento sloupec je vyžadován některými testy a API soubory
 */

require_once __DIR__ . '/config.php';

echo "=== Přidání sloupce current_weight do tabulky filaments ===\n\n";

try {
    // Zkontrolovat, zda sloupec již existuje
    $stmt = $pdo->query("SHOW COLUMNS FROM filaments LIKE 'current_weight'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Sloupec current_weight již existuje\n";
        exit;
    }

    // Přidat sloupec current_weight
    echo "Přidávám sloupec current_weight...\n";
    $pdo->exec("ALTER TABLE filaments ADD COLUMN current_weight INT NOT NULL DEFAULT 0 AFTER initial_weight_grams");
    echo "✓ Sloupec current_weight přidán\n";

    // Inicializovat current_weight na hodnotu initial_weight_grams
    echo "Inicializuji current_weight na hodnotu initial_weight_grams...\n";
    $pdo->exec("UPDATE filaments SET current_weight = initial_weight_grams");

    // Odečíst čerpání z consumption_log (pokud existuje sloupec amount_grams)
    echo "Aktualizuji current_weight podle consumption_log...\n";
    try {
        $pdo->exec("
            UPDATE filaments f
            SET f.current_weight = f.initial_weight_grams + COALESCE((
                SELECT SUM(cl.amount_grams)
                FROM consumption_log cl
                WHERE cl.filament_id = f.id
            ), 0)
        ");
        echo "✓ current_weight aktualizován podle consumption_log\n";
    } catch (PDOException $e) {
        // Pokud sloupec amount_grams neexistuje, použijeme consumed_weight (pokud existuje)
        echo "  Zkouším použít consumed_weight...\n";
        try {
            $pdo->exec("
                UPDATE filaments f
                SET f.current_weight = f.initial_weight_grams - COALESCE((
                    SELECT SUM(cl.consumed_weight)
                    FROM consumption_log cl
                    WHERE cl.filament_id = f.id
                ), 0)
            ");
            echo "✓ current_weight aktualizován podle consumption_log (consumed_weight)\n";
        } catch (PDOException $e2) {
            echo "  ⚠ Nepodařilo se aktualizovat podle consumption_log: " . $e2->getMessage() . "\n";
            echo "  current_weight je nastaven na initial_weight_grams\n";
        }
    }

    echo "\n✅ Migrace úspěšně dokončena!\n";

} catch (PDOException $e) {
    echo "\n❌ Chyba: " . $e->getMessage() . "\n";
    exit(1);
}

