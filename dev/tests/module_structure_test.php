<?php
/**
 * Test struktury modulů po rozdělení app.js
 * Ověřuje, že všechny moduly existují a jsou správně importovány
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/helpers.php';

echo "Running Module Structure Tests...\n";
echo "----------------------------------\n";

$basePath = __DIR__ . '/../../assets/js';
$errors = [];

// Kontrola existence modulů
$requiredModules = [
    'colors.js',
    'views/wizard.js',
    'views/auth.js',
    'views/stats.js'
];

foreach ($requiredModules as $module) {
    $path = $basePath . '/' . $module;
    if (!file_exists($path)) {
        $errors[] = "Missing module: $module";
        echo "[FAIL] Module not found: $module\n";
    } else {
        $lines = count(file($path));
        echo "[PASS] Module exists: $module ($lines lines)\n";
    }
}

// Kontrola app.js - měl by být menší než 2402 řádků
$appJsPath = $basePath . '/app.js';
if (file_exists($appJsPath)) {
    $appJsLines = count(file($appJsPath));
    echo "\n[INFO] app.js has $appJsLines lines (was 2402)\n";
    if ($appJsLines < 2402) {
        $saved = 2402 - $appJsLines;
        echo "[PASS] app.js reduced by $saved lines\n";
    } else {
        echo "[WARN] app.js not reduced\n";
    }
}

// Kontrola importů v app.js
$appJsContent = file_get_contents($appJsPath);
$expectedImports = [
    "from './colors.js'",
    "from './views/wizard.js'",
    "from './views/auth.js'",
    "from './views/stats.js'"
];

foreach ($expectedImports as $import) {
    if (strpos($appJsContent, $import) !== false) {
        echo "[PASS] Import found: $import\n";
    } else {
        $errors[] = "Missing import: $import";
        echo "[FAIL] Import not found: $import\n";
    }
}

// Kontrola, že staré funkce byly odstraněny
$removedFunctions = [
    'const colorNames = [',
    'const colorPalette = [',
    'function renderMaterials(v)',
    'function renderColors(v)',
    'function renderDetails(v)',
    'function renderAuth(v)',
    'async function renderStats(v)'
];

echo "\nChecking removed functions...\n";
foreach ($removedFunctions as $func) {
    if (strpos($appJsContent, $func) !== false) {
        $errors[] = "Function still present: $func";
        echo "[FAIL] Function still in app.js: $func\n";
    } else {
        echo "[PASS] Function removed: $func\n";
    }
}

if (empty($errors)) {
    echo "\n[PASS] All module structure tests passed!\n";
    exit(0);
} else {
    echo "\n[FAIL] Module structure tests failed:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    exit(1);
}
