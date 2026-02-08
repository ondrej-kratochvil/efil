<?php
declare(strict_types=1);

/**
 * Validace data ve formátu YYYY-MM-DD pro consumption_date.
 * Vrátí normalizovaný řetězec nebo null při neplatném formátu / neexistujícím datu.
 *
 * @return string|null Platné datum 'Y-m-d' nebo null
 */
function validateConsumptionDate(string $value): ?string
{
    $v = trim($value);
    if ($v === '') {
        return null;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
        return null;
    }
    $d = DateTime::createFromFormat('Y-m-d', $v);
    if ($d === false || $d->format('Y-m-d') !== $v) {
        return null;
    }
    return $v;
}
