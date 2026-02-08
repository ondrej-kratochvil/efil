<?php
declare(strict_types=1);

/**
 * Price per kg (getAvgCzkPerKg) tests.
 * Ověřuje stejný vzorec jako assets/js/utils.js getAvgCzkPerKg() – průměrná cena za kg z pole položek.
 * Do výpočtu se započítávají jen položky s price > 0, základ je initial_weight_grams.
 */

require_once __DIR__ . '/helpers.php';

echo "Running Price per kg (getAvgCzkPerKg) Tests...\n";
echo "----------------------------------------------\n";

/**
 * Stejná logika jako JS getAvgCzkPerKg() – pro ověření vzorce v PHP testech.
 * @param array<int, array{price?: mixed, initial_weight_grams?: mixed}> $items
 * @return int|null
 */
function getAvgCzkPerKgPhp(array $items): ?int
{
    if (count($items) === 0) {
        return null;
    }
    $withPrice = array_filter($items, function ($i) {
        return (float)($i['price'] ?? 0) > 0;
    });
    if (count($withPrice) === 0) {
        return null;
    }
    $totalPrice = 0.0;
    $totalInitialG = 0;
    foreach ($withPrice as $i) {
        $totalPrice += (float)($i['price'] ?? 0);
        $totalInitialG += (int)($i['initial_weight_grams'] ?? 0);
    }
    if ($totalInitialG <= 0) {
        return null;
    }
    return (int)round($totalPrice / ($totalInitialG / 1000));
}

try {
    // Prázdné pole => null
    $result = getAvgCzkPerKgPhp([]);
    assertResult("Empty array returns null", null, $result);

    // Žádná položka s cenou => null
    $result = getAvgCzkPerKgPhp([
        ['price' => 0, 'initial_weight_grams' => 1000],
        ['price' => null, 'initial_weight_grams' => 500],
    ]);
    assertResult("No items with price returns null", null, $result);

    // Jedna položka: 500 Kč, 1000 g => 500 Kč/kg
    $result = getAvgCzkPerKgPhp([
        ['price' => 500, 'initial_weight_grams' => 1000],
    ]);
    assertResult("Single item 500 Kč / 1 kg", 500, $result);

    // Dvě položky: 500 Kč/1 kg + 300 Kč/0.5 kg => 800/1.5 = 533 Kč/kg
    $result = getAvgCzkPerKgPhp([
        ['price' => 500, 'initial_weight_grams' => 1000],
        ['price' => 300, 'initial_weight_grams' => 500],
    ]);
    assertResult("Two items: 500/1kg + 300/0.5kg => 533 Kč/kg", 533, $result);

    // Položky s cenou 0 se nezapočítávají
    $result = getAvgCzkPerKgPhp([
        ['price' => 500, 'initial_weight_grams' => 1000],
        ['price' => 0, 'initial_weight_grams' => 5000],
        ['price' => 300, 'initial_weight_grams' => 500],
    ]);
    assertResult("Items with price 0 excluded", 533, $result);

    // Nulová původní hmotnost – položka s initial 0 přispívá cenou, ale 0 g do jmenovatele; průměr = (100+400)/1 kg = 500
    $result = getAvgCzkPerKgPhp([
        ['price' => 100, 'initial_weight_grams' => 0],
        ['price' => 400, 'initial_weight_grams' => 1000],
    ]);
    assertResult("One item with 0 initial weight: (100+400)/1 kg = 500", 500, $result);

    // Zaokrouhlení
    $result = getAvgCzkPerKgPhp([
        ['price' => 100, 'initial_weight_grams' => 300], // 333.33 Kč/kg
    ]);
    assertResult("Rounding 333.33 => 333", 333, $result);

    echo "\nAll Tests Passed!\n";
} catch (Throwable $e) {
    echo "\n[FAIL] " . $e->getMessage() . "\n";
    exit(1);
}
