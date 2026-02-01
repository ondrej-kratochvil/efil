<?php
declare(strict_types=1);

/**
 * Helper pro typy cívek (spool_types) – verzovaná tabulka, soft delete, public, schvalování.
 * Viz dev/docs/SOFT_DELETE_AND_VERSIONING.md a dev/docs/MANUFACTURERS.md (stejný vzor).
 */

/**
 * Sestaví zobrazovací popisek typu cívky z řádku.
 *
 * @param array<string, mixed> $row Řádek z spool_types (weight_grams, color, material, ...)
 * @return string
 */
function spoolTypeRowToLabel(array $row): string
{
    $parts = [];
    if (!empty($row['weight_grams'])) {
        $parts[] = $row['weight_grams'] . ' g';
    }
    if (!empty($row['color'])) {
        $parts[] = $row['color'];
    }
    if (!empty($row['material'])) {
        $parts[] = $row['material'];
    }
    if (!empty($row['outer_diameter_mm'])) {
        $parts[] = $row['outer_diameter_mm'] . ' mm';
    }
    if (!empty($row['width_mm'])) {
        $parts[] = $row['width_mm'] . ' mm';
    }
    if (!empty($row['visual_description'])) {
        $parts[] = $row['visual_description'];
    }
    return $parts !== [] ? implode(', ', $parts) : 'Typ cívky';
}

/**
 * Vrátí aktuální platnou verzi typu cívky (schválenou nebo návrh pro autora).
 *
 * @param PDO $pdo
 * @param int $spoolTypeId Logické id (spool_types.spool_type_id)
 * @param int|null $viewerUserId Id přihlášeného uživatele (pro zobrazení návrhu autorovi)
 * @return array<string, mixed>|null Řádek nebo null
 */
function getSpoolTypeCurrentRow(PDO $pdo, int $spoolTypeId, ?int $viewerUserId = null): ?array
{
    if ($viewerUserId !== null) {
        $stmt = $pdo->prepare("
            SELECT id, spool_type_id, weight_grams, color, material, outer_diameter_mm, width_mm, visual_description, public, approved, created_at, created_by
            FROM spool_types
            WHERE spool_type_id = ? AND approved = 0 AND invalidated_at IS NULL AND created_by = ?
            LIMIT 1
        ");
        $stmt->execute([$spoolTypeId, $viewerUserId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row !== false) {
            return $row;
        }
    }

    $stmt = $pdo->prepare("
        SELECT id, spool_type_id, weight_grams, color, material, outer_diameter_mm, width_mm, visual_description, public, approved, created_at, created_by
        FROM spool_types
        WHERE spool_type_id = ? AND approved = 1 AND invalidated_at IS NULL
        LIMIT 1
    ");
    $stmt->execute([$spoolTypeId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row !== false ? $row : null;
}

/**
 * Vrátí hmotnost (táru) typu cívky v gramech. 0 pokud neexistuje.
 *
 * @param PDO $pdo
 * @param int $spoolTypeId Logické id
 * @param int|null $viewerUserId Pro zobrazení návrhu autorovi
 * @return int
 */
function getSpoolTypeWeight(PDO $pdo, int $spoolTypeId, ?int $viewerUserId = null): int
{
    $row = getSpoolTypeCurrentRow($pdo, $spoolTypeId, $viewerUserId);
    if ($row === null) {
        return 0;
    }
    $w = $row['weight_grams'] ?? null;
    return $w !== null ? (int) $w : 0;
}

/**
 * Seznam typů cívek pro options (dropdown): veřejné (public=1) + vlastní (public=0, created_by=userId).
 * Každý spool_type_id jen jednou; label = z aktuální schválené verze (nebo z návrhu pro autora).
 *
 * @param PDO $pdo
 * @param int $userId Přihlášený uživatel
 * @param bool $includePendingProposals Admin: přidat i záznamy s approved=0 do přehledu (volitelně)
 * @return array<array{id: int, label: string, weight_grams: int|null, ...}> Pole [{ id => spool_type_id, label => string, ... }, ...]
 */
function getSpoolTypesForOptions(PDO $pdo, int $userId, bool $includePendingProposals = false): array
{
    $sql = "
        SELECT st.spool_type_id AS id, st.weight_grams, st.color, st.material, st.outer_diameter_mm, st.width_mm, st.visual_description
        FROM spool_types st
        WHERE st.approved = 1 AND st.invalidated_at IS NULL
          AND (st.public = 1 OR (st.public = 0 AND st.created_by = ?))
        ORDER BY st.weight_grams, st.color, st.material
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $rows = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stId = (int) $row['id'];
        $current = getSpoolTypeCurrentRow($pdo, $stId, $userId);
        if ($current !== null) {
            $label = spoolTypeRowToLabel($current);
            $rows[$stId] = [
                'id' => $stId,
                'label' => $label,
                'weight_grams' => isset($current['weight_grams']) ? (int) $current['weight_grams'] : null,
                'color' => $current['color'] ?? null,
                'material' => $current['material'] ?? null,
                'outer_diameter_mm' => isset($current['outer_diameter_mm']) ? (int) $current['outer_diameter_mm'] : null,
                'width_mm' => isset($current['width_mm']) ? (int) $current['width_mm'] : null,
                'visual_description' => $current['visual_description'] ?? null,
                'public' => (int) ($current['public'] ?? 0),
                'created_by' => isset($current['created_by']) ? (int) $current['created_by'] : null,
            ];
        }
    }
    return array_values($rows);
}

/**
 * Další volné spool_type_id (kořen).
 */
function getNextSpoolTypeId(PDO $pdo): int
{
    $stmt = $pdo->query("SELECT COALESCE(MAX(spool_type_id), 0) + 1 FROM spool_types");
    return (int) $stmt->fetchColumn();
}

/**
 * Je typ cívky použit (alespoň jeden filament nebo spool_manufacturer)?
 */
function isSpoolTypeInUse(PDO $pdo, int $spoolTypeId): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM filaments WHERE spool_type_id = ? LIMIT 1");
    $stmt->execute([$spoolTypeId]);
    if ($stmt->fetchColumn() !== false) {
        return true;
    }
    $stmt = $pdo->prepare("SELECT 1 FROM spool_manufacturer WHERE spool_id = ? LIMIT 1");
    $stmt->execute([$spoolTypeId]);
    return $stmt->fetchColumn() !== false;
}
