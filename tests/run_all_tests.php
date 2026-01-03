<?php
// tests/run_all_tests.php
// Run all test suites with HTML output

// Check if running in browser (has HTTP_HOST) or CLI
$isBrowser = isset($_SERVER['HTTP_HOST']);

if ($isBrowser) {
    // HTML output for browser
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eFil Test Suite</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        .header p {
            opacity: 0.9;
            font-size: 1.1em;
        }
        .test-section {
            margin: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            background: #fafafa;
        }
        .test-header {
            background: #667eea;
            color: white;
            padding: 15px 20px;
            font-size: 1.2em;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .test-content {
            padding: 20px;
            background: white;
        }
        .test-output {
            font-family: 'Courier New', monospace;
            font-size: 0.95em;
            line-height: 1.6;
            white-space: pre-wrap;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 6px;
            overflow-x: auto;
        }
        .pass {
            color: #4caf50;
            font-weight: bold;
        }
        .fail {
            color: #f44336;
            font-weight: bold;
        }
        .skip {
            color: #ff9800;
            font-weight: bold;
        }
        .summary {
            margin: 20px;
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .summary h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.8em;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .summary-table th,
        .summary-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        .summary-table th {
            background: #f5f5f5;
            font-weight: bold;
            color: #333;
        }
        .summary-table tr:hover {
            background: #f9f9f9;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
        }
        .status-passed {
            background: #4caf50;
            color: white;
        }
        .status-failed {
            background: #f44336;
            color: white;
        }
        .status-skipped {
            background: #ff9800;
            color: white;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-card h3 {
            font-size: 2.5em;
            margin-bottom: 5px;
        }
        .stat-card p {
            opacity: 0.9;
            font-size: 1.1em;
        }
        .icon {
            font-size: 1.2em;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 eFil Test Suite</h1>
            <p>Running all test suites...</p>
        </div>
    <?php
    flush();
    ob_start();
} else {
    // Plain text output for CLI
    echo "========================================\n";
    echo "eFil Test Suite\n";
    echo "========================================\n\n";
}

$tests = [
    'balance_test.php' => 'Balance Calculation Tests',
    'manufacturer_auto_create_test.php' => 'Manufacturer Auto-Create Tests',
    'options_optgroups_test.php' => 'Options Optgroups Tests',
    'spool_management_test.php' => 'Spool Management Tests',
    'form_persistence_test.php' => 'Form Persistence Tests',
    'user_display_id_test.php' => 'User Display ID Tests',
    'consumption_history_test.php' => 'Consumption History Tests',
    'grouping_test.php' => 'Filament Grouping Tests',
    'spool_manufacturer_test.php' => 'Spool-Manufacturer M:N Tests',
    'multiuser_test.php' => 'Multiuser & Sharing Tests'
];

$results = [];
$totalTests = count($tests);
$passedTests = 0;
$failedTests = 0;
$skippedTests = 0;

foreach ($tests as $testFile => $testName) {
    if ($isBrowser) {
        echo '<div class="test-section">';
        echo '<div class="test-header">';
        echo '<span class="icon">▶</span>';
        echo '<span>Running: ' . htmlspecialchars($testName) . '</span>';
        echo '</div>';
        echo '<div class="test-content">';
        echo '<div class="test-output">';
        flush();
    } else {
        echo "\n";
        echo str_repeat("=", 50) . "\n";
        echo "Running: $testName\n";
        echo str_repeat("=", 50) . "\n";
    }

    $testPath = __DIR__ . '/' . $testFile;

    if (!file_exists($testPath)) {
        if ($isBrowser) {
            echo '<span class="skip">[SKIP] Test file not found: ' . htmlspecialchars($testFile) . '</span>';
            echo '</div></div></div>';
        } else {
            echo "[SKIP] Test file not found: $testFile\n";
        }
        $results[$testFile] = ['status' => 'SKIPPED', 'name' => $testName];
        $skippedTests++;
        continue;
    }

    // Capture output
    ob_start();
    $testPassed = true;

    try {
        // Include test file
        include $testPath;

        // Check if test passed (no exit(1) was called)
        $output = ob_get_clean();

        // Process output for HTML
        if ($isBrowser) {
            $output = htmlspecialchars($output);
            $output = preg_replace('/\[PASS\](.*?)\n/', '<span class="pass">[PASS]$1</span><br>', $output);
            $output = preg_replace('/\[FAIL\](.*?)\n/', '<span class="fail">[FAIL]$1</span><br>', $output);
            $output = preg_replace('/\[SKIP\](.*?)\n/', '<span class="skip">[SKIP]$1</span><br>', $output);
            echo $output;
            echo '</div></div></div>';
        } else {
            echo $output;
        }

        // Check for [FAIL] in output
        if (strpos($output, '[FAIL]') !== false || strpos($output, 'Exception:') !== false) {
            $results[$testFile] = ['status' => 'FAILED', 'name' => $testName];
            $failedTests++;
            $testPassed = false;
        } else {
            $results[$testFile] = ['status' => 'PASSED', 'name' => $testName];
            $passedTests++;
        }
    } catch (Exception $e) {
        ob_end_clean();
        $errorMsg = "[FAIL] Exception in test: " . $e->getMessage();
        if ($isBrowser) {
            echo '<span class="fail">' . htmlspecialchars($errorMsg) . '</span>';
            echo '</div></div></div>';
        } else {
            echo $errorMsg . "\n";
        }
        $results[$testFile] = ['status' => 'FAILED', 'name' => $testName];
        $failedTests++;
        $testPassed = false;
    } catch (Error $e) {
        ob_end_clean();
        $errorMsg = "[FAIL] Error in test: " . $e->getMessage();
        if ($isBrowser) {
            echo '<span class="fail">' . htmlspecialchars($errorMsg) . '</span>';
            echo '</div></div></div>';
        } else {
            echo $errorMsg . "\n";
        }
        $results[$testFile] = ['status' => 'FAILED', 'name' => $testName];
        $failedTests++;
        $testPassed = false;
    }

    flush();
}

// Summary
if ($isBrowser) {
    echo '<div class="summary">';
    echo '<h2>📊 Test Summary</h2>';
    echo '<div class="stats">';
    echo '<div class="stat-card"><h3>' . $totalTests . '</h3><p>Total Tests</p></div>';
    echo '<div class="stat-card" style="background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);"><h3>' . $passedTests . '</h3><p>Passed</p></div>';
    echo '<div class="stat-card" style="background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);"><h3>' . $failedTests . '</h3><p>Failed</p></div>';
    if ($skippedTests > 0) {
        echo '<div class="stat-card" style="background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);"><h3>' . $skippedTests . '</h3><p>Skipped</p></div>';
    }
    echo '</div>';
    echo '<table class="summary-table">';
    echo '<thead><tr><th>Test</th><th>Status</th></tr></thead>';
    echo '<tbody>';
    foreach ($results as $testFile => $result) {
        $statusClass = 'status-' . strtolower($result['status']);
        echo '<tr>';
        echo '<td>' . htmlspecialchars($result['name']) . '</td>';
        echo '<td><span class="status-badge ' . $statusClass . '">' . $result['status'] . '</span></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
    echo '</div>';
    echo '</body></html>';
} else {
    echo "\n";
    echo str_repeat("=", 50) . "\n";
    echo "Test Summary\n";
    echo str_repeat("=", 50) . "\n";

    foreach ($results as $testFile => $result) {
        $status = $result['status'] === 'PASSED' ? '✓' : ($result['status'] === 'FAILED' ? '✗' : '○');
        echo "$status {$result['name']}: {$result['status']}\n";
    }

    echo "\n";
    echo "Total: $totalTests tests\n";
    echo "Passed: $passedTests\n";
    echo "Failed: $failedTests\n";
    echo "Skipped: $skippedTests\n";

    if ($failedTests > 0) {
        echo "\n[FAIL] Some tests failed!\n";
        exit(1);
    } else {
        echo "\n[PASS] All tests passed!\n";
        exit(0);
    }
}
