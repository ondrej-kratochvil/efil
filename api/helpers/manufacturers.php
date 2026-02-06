<?php
declare(strict_types=1);

/**
 * Helper pro výrobce (manufacturers) – verzovaná tabulka, soft delete.
 * Viz dev/docs/SOFT_DELETE_AND_VERSIONING.md
 */

/**
 * Vrátí aktuální název výrobce pro dané manufacturer_id.
 * Pokud má viewerUserId shodný s autorem neschváleného návrhu, vrátí název z návrhu.
 *
 * @param PDO $pdo
 * @param int $manufacturerId Logické id (manufacturers.manufacturer_id)
 * @param int|null $viewerUserId Id přihlášeného uživatele (pro zobrazení návrhu autorovi)
 * @return string|null Název nebo null pokud výrobce neexistuje / nemá platnou verzi
 */
function getManufacturerName(PDO $pdo, int $manufacturerId, ?int $viewerUserId = null): ?string
{
    // Neschválená verze (návrh) – zobrazit jen autorovi návrhu
    if ($viewerUserId !== null) {
        $stmt = $pdo->prepare("
            SELECT name FROM manufacturers
            WHERE manufacturer_id = ? AND approved = 0 AND invalidated_at IS NULL AND created_by = ?
            LIMIT 1
        ");
        $stmt->execute([$manufacturerId, $viewerUserId]);
        $row = $stmt->fetch(PDO::FETCH_COLUMN);
        if ($row !== false) {
            return $row;
        }
    }

    // Schválená platná verze
    $stmt = $pdo->prepare("
        SELECT name FROM manufacturers
        WHERE manufacturer_id = ? AND approved = 1 AND invalidated_at IS NULL
        LIMIT 1
    ");
    $stmt->execute([$manufacturerId]);
    $name = $stmt->fetch(PDO::FETCH_COLUMN);
    return $name !== false ? $name : null;
}

/**
 * Seznam výrobců pro options (dropdown): veřejní (public=1) + vlastní (public=0, created_by=userId).
 * Každý manufacturer_id jen jednou; název = aktuální schválená verze (nebo návrh pro autora).
 *
 * @param PDO $pdo
 * @param int $userId Přihlášený uživatel
 * @param bool $includePendingProposals Admin: přidat i záznamy s approved=0 pro přehled
 * @return array<array{id: int, name: string}> Pole [{ id => manufacturer_id, name => string }, ...]
 */
function getManufacturersForOptions(PDO $pdo, int $userId, bool $includePendingProposals = false): array
{
    $rows = [];

    // Schválené platné verze: public=1 NEBO (public=0 AND created_by=userId); každé manufacturer_id jen jednou
    $sql = "
        SELECT m.manufacturer_id AS id, m.name
        FROM manufacturers m
        WHERE m.approved = 1 AND m.invalidated_at IS NULL
          AND (m.public = 1 OR (m.public = 0 AND m.created_by = ?))
        ORDER BY m.name
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $manId = (int) $row['id'];
        $name = getManufacturerName($pdo, $manId, $userId);
        if ($name !== null) {
            $rows[$manId] = ['id' => $manId, 'name' => $name];
        }
    }

    // Neschválené návrhy – pro autora už máme název v předchozím kroku (getManufacturerName vrátí návrh).
    // Pro admina: přidat záznamy s approved=0 jako samostatné položky do seznamu? Podle specifikace
    // admin vidí „seznam záznamů s approved=0“ zvlášť. Sem dáváme jen to, co jde do dropdownu (options).
    // Takže options = jen schválené + vlastní, s tím že název u „autor návrhu“ je z návrhu. Hotovo výše.

    return array_values($rows);
}

/**
 * Kontrola duplicity: stejný název u public=1 nebo u vlastních (public=0, created_by=userId).
 * Porovnání case-insensitive (LOWER).
 *
 * @param PDO $pdo
 * @param string $name Navrhovaný název
 * @param int $userId Id uživatele (vlastní záznamy)
 * @param int|null $excludeManufacturerId Vynechat toto manufacturer_id (při editaci)
 * @return bool true = duplicita existuje
 */
function manufacturerNameDuplicateExists(PDO $pdo, string $name, int $userId, ?int $excludeManufacturerId = null): bool
{
    $nameNorm = trim($name);
    if ($nameNorm === '') {
        return false;
    }
    $sql = "
        SELECT 1 FROM manufacturers
        WHERE approved = 1 AND invalidated_at IS NULL
          AND LOWER(TRIM(name)) = LOWER(?)
          AND (public = 1 OR (public = 0 AND created_by = ?))
    ";
    $params = [$nameNorm, $userId];
    if ($excludeManufacturerId !== null) {
        $sql .= " AND manufacturer_id != ?";
        $params[] = $excludeManufacturerId;
    }
    $sql .= " LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() !== false;
}

/**
 * Další volné manufacturer_id (kořen).
 */
function getNextManufacturerId(PDO $pdo): int
{
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(manufacturer_id), 0) + 1 FROM manufacturers");
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

/**
 * Je výrobce použit (alespoň jeden filament nebo spool_manufacturer)?
 */
function isManufacturerInUse(PDO $pdo, int $manufacturerId): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM filaments WHERE manufacturer_id = ? LIMIT 1");
    $stmt->execute([$manufacturerId]);
    if ($stmt->fetchColumn() !== false) {
        return true;
    }
    $stmt = $pdo->prepare("SELECT 1 FROM spool_manufacturer WHERE manufacturer_id = ? LIMIT 1");
    $stmt->execute([$manufacturerId]);
    return $stmt->fetchColumn() !== false;
}
