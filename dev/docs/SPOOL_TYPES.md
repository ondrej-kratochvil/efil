# Typy cívek (spool types) – verzování, soft delete, public, schvalování

Typy cívek používají stejný vzor jako výrobci: **verzování**, **soft delete**, **public**, **schvalování**. Viz [SOFT_DELETE_AND_VERSIONING.md](SOFT_DELETE_AND_VERSIONING.md).

## Schéma

- **spool_type_id** – logické ID (společné pro všechny verze téhož typu cívky)
- **id** – PK řádku (jedna verze)
- **weight_grams**, **color**, **material**, **outer_diameter_mm**, **width_mm**, **visual_description**
- **public**, **approved**, **created_at**, **created_by**, **invalidated_at**, **invalidated_by**

Reference z `filaments` a `spool_manufacturer` jdou na **spool_type_id** (logické ID). Cizí klíče na `spool_types.id` se nepoužívají.

## API endpointy

| Endpoint | Metoda | Popis |
|----------|--------|--------|
| `/api/spools/list.php` | GET | Seznam typů cívek pro dropdown (veřejné + vlastní) |
| `/api/spools/create.php` | POST | Vytvoření nového soukromého typu (public=0) |
| `/api/spools/update.php` | POST | Úprava vlastního typu nebo návrh na změnu veřejného |
| `/api/spools/delete.php` | POST | Soft delete (nastaví invalidated_at, invalidated_by) – jen pokud typ není použit |
| `/api/spools/pending.php` | GET | **(Admin)** Seznam čekajících návrhů na změnu |
| `/api/spools/approve.php` | POST | **(Admin)** Schválení návrhu |
| `/api/spools/reject.php` | POST | **(Admin)** Zamítnutí návrhu |
| `/api/spools/save.php` | POST | Find-or-create podle atributů (legacy); přijímá `manufacturer` – výrobce se propisuje z formuláře filamentu |

**Validace:** U create i update jsou **barva** a **materiál** povinné (neprázdné).

Při soft delete se vždy vyplní **invalidated_by** (ID uživatele, který smazal záznam).

## Migrace existující databáze

Pro databáze se starým schématem (tabulka `spool_library`):

1. **Záloha databáze**
2. Spuštění migrace:
   ```bash
   php dev/sql/migrate_spool_types_versioned.php
   ```
3. Skript vytvoří `spool_types`, přenese data (spool_type_id = staré id), odstraní FK od filaments a spool_manufacturer na spool_library, smaže spool_library.

Pro čistou instalaci stačí `dev/sql/init_db.php` (schéma už obsahuje spool_types).

## Helper funkce (api/helpers/spool_types.php)

- **spoolTypeRowToLabel($row)** – zobrazovací popisek z řádku (weight_grams, color, material, …)
- **getSpoolTypeCurrentRow($pdo, $spoolTypeId, $viewerUserId)** – aktuální platná verze (schválená nebo návrh pro autora)
- **getSpoolTypeWeight($pdo, $spoolTypeId, $viewerUserId)** – tára v gramech (pro filamenty)
- **getSpoolTypesForOptions($pdo, $userId, $includePendingProposals)** – seznam pro dropdown
- **getNextSpoolTypeId($pdo)** – další volné logické ID
- **isSpoolTypeInUse($pdo, $spoolTypeId)** – zda je typ použit u filamentu nebo u spool_manufacturer
