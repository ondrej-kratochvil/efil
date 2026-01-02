<?php
// tests/helpers.php
// Shared helper functions for all tests

if (!function_exists('assertResult')) {
    function assertResult($name, $expected, $actual) {
        // Normalize types for comparison (convert both to same type)
        $normalizedExpected = is_numeric($expected) ? (float)$expected : $expected;
        $normalizedActual = is_numeric($actual) ? (float)$actual : $actual;
        
        // Use == for comparison to handle type coercion, but also check strict if both are same type
        $passed = ($normalizedExpected == $normalizedActual) || ($expected === $actual);
        
        if ($passed) {
            echo "[PASS] $name: Expected " . (is_string($expected) ? "'$expected'" : $expected) . ", Got " . (is_string($actual) ? "'$actual'" : $actual) . "\n";
        } else {
            echo "[FAIL] $name: Expected " . (is_string($expected) ? "'$expected'" : $expected) . ", Got " . (is_string($actual) ? "'$actual'" : $actual) . "\n";
            exit(1);
        }
    }
}

