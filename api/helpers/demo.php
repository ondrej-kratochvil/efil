<?php
declare(strict_types=1);

/**
 * Demo mode access: block write/update/delete in demo inventory unless user is admin_efil.
 * Centralizes MySQL TINYINT(1) normalization and admin check.
 *
 * @param PDO $pdo Database connection
 * @param int $userId Current user ID
 * @param mixed $isDemoRaw is_demo value from DB (TINYINT(1) may be int or string)
 * @param string|null $errorMessage Custom 403 message (default: generic demo restriction)
 * @return void Exits with 403 JSON if demo mode and user is not admin
 */
function checkDemoModeAccess(PDO $pdo, int $userId, $isDemoRaw, ?string $errorMessage = null): void
{
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = ($user && $user['role'] === 'admin_efil');

    // MySQL TINYINT(1) may be returned as int or string; use int comparison to avoid (bool)'0' quirks
    $isDemo = ((int)($isDemoRaw ?? 0) === 1);

    if ($isDemo && !$isAdmin) {
        http_response_code(403);
        echo json_encode([
            'error' => $errorMessage ?? 'V demo režimu nelze upravovat data. Vytvořte si vlastní účet pro plný přístup.'
        ]);
        exit;
    }
}
