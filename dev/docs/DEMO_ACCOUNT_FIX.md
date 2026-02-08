# Oprava demo účtu - omezení na čtení

## Problém
Demo účet mohl mazat, upravovat a přidávat data, i když by měl být omezen pouze na čtení.

## Příčina
1. **Chybějící `is_demo = 1` při vytváření demo inventáře**: V `init_db.php` se demo inventář vytvářel bez explicitního nastavení `is_demo = 1`, takže měl výchozí hodnotu `FALSE` (0).

2. **Nedostatečná kontrola hodnoty `is_demo`**: MySQL `BOOLEAN` typ je ve skutečnosti alias pro `TINYINT(1)`, takže hodnota může být načtena jako `0`, `1`, `'0'`, `'1'` nebo `NULL`. Původní kontrola `if ($inventory['is_demo'] && !$isAdmin)` nemusela fungovat správně ve všech případech.

## Opravy

### 1. Opraveno `init_db.php`
```php
// Před:
$stmt = $pdo->prepare("INSERT INTO inventories (owner_id, name) VALUES (?, 'Demo Dílna')");

// Po:
$stmt = $pdo->prepare("INSERT INTO inventories (owner_id, name, is_demo) VALUES (?, 'Demo Dílna', 1)");
```

### 2. Robustní kontrola `is_demo` ve všech API souborech
Všechny kontroly `is_demo` byly upraveny na:
```php
// MySQL BOOLEAN is TINYINT(1), so we need to check for 1 or '1'
$isDemo = ($inventory['is_demo'] === 1 || $inventory['is_demo'] === '1' || (bool)$inventory['is_demo']);
if ($isDemo && !$isAdmin) {
    // Blokovat operaci
}
```

### 3. Opravené soubory
- `api/filaments/save.php` - Uložení/úprava filamentu
- `api/filaments/delete.php` - Smazání filamentu
- `api/filaments/consume.php` - Čerpání filamentu
- `api/consumption/update.php` - Úprava záznamu čerpání
- `api/consumption/delete.php` - Smazání záznamu čerpání

### 4. Migrační skript
Vytvořen `fix_demo_inventory.php` pro opravu existujících databází:
```bash
php fix_demo_inventory.php
```
nebo v prohlížeči:
```
http://localhost/fix_demo_inventory.php
```

## Ověření
Po spuštění migračního skriptu by demo účet měl být správně omezen:
- ✅ Nelze mazat filamenty
- ✅ Nelze upravovat filamenty
- ✅ Nelze přidávat nové filamenty
- ✅ Nelze čerpat filamenty
- ✅ Nelze upravovat/smazat záznamy čerpání

## Testování
1. Přihlásit se do demo účtu (demo@efil.cz / demo1234)
2. Otevřít formulář filamentu
3. Zkusit uložit změny → měla by se zobrazit chyba "V demo režimu nelze upravovat data..."
4. Zkusit smazat filament → měla by se zobrazit chyba "V demo režimu nelze mazat"
5. Zkusit přidat nový filament → měla by se zobrazit chyba "V demo režimu nelze upravovat data..."
