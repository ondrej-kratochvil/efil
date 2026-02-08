# Výrobci (manufacturers) – verzování a API

Tabulka výrobců používá **verzování** a **soft delete**. Detailní vzor je popsán v [SOFT_DELETE_AND_VERSIONING.md](SOFT_DELETE_AND_VERSIONING.md).

## Schéma

- **manufacturer_id** – logické ID (společné pro všechny verze téhož výrobce)
- **id** – PK řádku (jedna verze)
- **name**, **public**, **approved**, **created_at**, **created_by**, **invalidated_at**, **invalidated_by**

Reference z `filaments` a `spool_manufacturer` jdou na **manufacturer_id** (logické ID). Cizí klíče na `manufacturers.id` se nepoužívají.

## API endpointy

| Endpoint | Metoda | Popis |
|----------|--------|--------|
| `/api/manufacturers/list.php` | GET | Seznam výrobců pro dropdown (veřejní + vlastní) |
| `/api/manufacturers/create.php` | POST | Vytvoření nového soukromého výrobce (`public=0`) |
| `/api/manufacturers/update.php` | POST | Úprava vlastního výrobce nebo návrh na změnu veřejného |
| `/api/manufacturers/delete.php` | POST | Soft delete (nastaví `invalidated_at`, `invalidated_by`) – jen pokud výrobce není použit |
| `/api/manufacturers/pending.php` | GET | **(Admin)** Seznam čekajících návrhů na změnu |
| `/api/manufacturers/approve.php` | POST | **(Admin)** Schválení návrhu |
| `/api/manufacturers/reject.php` | POST | **(Admin)** Zamítnutí návrhu |

Při soft delete se vždy vyplní **invalidated_by** (ID uživatele, který smazal záznam).

## Migrace existující databáze

Pro databáze se starým schématem (sloupec `filaments.manufacturer` VARCHAR, stará tabulka `manufacturers`):

1. **Záloha databáze**
2. Spuštění migrace:
   ```bash
   php dev/sql/migrate_manufacturers_versioned.php
   ```
3. Skript vytvoří `manufacturers_new`, přenese data, upraví `filaments` a `spool_manufacturer`, odstraní všechny FK odkazující na `manufacturers`, přejmenuje tabulku.
4. Při předchozím selhání migrace (např. kvůli FK) lze skript spustit znovu – detekuje existující `manufacturers_new` a dokončí jen zbývající kroky (resume).

Pro čistou instalaci stačí `dev/sql/init_db.php` (schéma už obsahuje novou strukturu výrobců).

## Helper funkce (api/helpers/manufacturers.php)

- **getManufacturerName($pdo, $manufacturerId, $viewerUserId)** – aktuální název (schválená verze nebo návrh pro autora)
- **getManufacturersForOptions($pdo, $userId, $includePendingProposals)** – seznam pro dropdown
- **manufacturerNameDuplicateExists($pdo, $name, $userId, $excludeManufacturerId)** – kontrola duplicity názvu
- **getNextManufacturerId($pdo)** – další volné logické ID
- **isManufacturerInUse($pdo, $manufacturerId)** – zda je výrobce použit u filamentu nebo typu cívky
