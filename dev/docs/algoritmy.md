# Algoritmy eFil

## 📊 Výpočet aktuální hmotnosti filamentu

### Základní vzorec

Aktuální hmotnost se počítá jako:
```
current_weight = initial_weight_grams + SUM(consumption_log.amount_grams)
```

### SQL dotaz

```sql
SELECT
    f.id,
    f.initial_weight_grams,
    (f.initial_weight_grams + COALESCE(SUM(cl.amount_grams), 0)) as current_weight
FROM filaments f
LEFT JOIN consumption_log cl ON f.id = cl.filament_id
WHERE f.inventory_id = ?
GROUP BY f.id
```

### Logika

- **Počáteční hmotnost** (`initial_weight_grams`) je vždy kladná
- **Spotřeba** (`amount_grams`) může být:
  - **Záporná** (`< 0`) - čerpání materiálu
  - **Kladná** (`> 0`) - korekce (přidání materiálu)
- **COALESCE** zajišťuje, že pokud není žádná spotřeba, vrací se `initial_weight_grams`

### Příklad

```
Filament: initial_weight_grams = 1000g
Spotřeba 1: -200g (čerpání)
Spotřeba 2: +50g (korekce)
Aktuální hmotnost: 1000 + (-200) + 50 = 850g
```

---

## 🎯 Groupování filamentů

### Klíč pro groupování

Filamenty se seskupují podle:
```
key = manufacturer + '|' + material + '|' + color_name
```

### Algoritmus

```javascript
// Frontend: assets/js/app.js
const groups = {};
filaments.forEach(f => {
    const key = `${f.man}|${f.mat}|${f.color}`;
    if (!groups[key]) {
        groups[key] = [];
    }
    groups[key].push(f);
});
```

### Zobrazení

- **Skupina s 1 filamentem**: Zobrazí se jako jednotlivá položka
- **Skupina s více filamenty**: Zobrazí se jako jedna položka s:
  - Celkovou hmotností (součet všech filamentů ve skupině)
  - Možností rozbalení (expand/collapse)
  - Seznamem jednotlivých filamentů po rozbalení

### Rozbalení skupin

- `state.expandedGroups` - Set obsahující klíče rozbalených skupin
- Toggle: `state.expandedGroups.has(key)` → add/delete

### Příklad

```
Filamenty:
- Prusa Polymers | PLA | Černá | 500g
- Prusa Polymers | PLA | Černá | 300g
- Prusa Polymers | PLA | Černá | 200g

Skupina: "Prusa Polymers|PLA|Černá"
Celková hmotnost: 1000g
Zobrazení: Jedna položka s možností rozbalení
```

---

## 📋 Optgroups v selectech

### Algoritmus pro materiály a výrobce

Backend (`api/data/options.php`) rozděluje hodnoty do dvou skupin:

1. **Top 5** - Nejčastěji používané hodnoty (podle počtu výskytů v evidenci)
2. **Ostatní** - Zbývající hodnoty seřazené podle abecedy

### SQL dotaz pro top hodnoty

```sql
SELECT material, COUNT(*) as count
FROM filaments
WHERE inventory_id = ? AND material IS NOT NULL AND material != ''
GROUP BY material
ORDER BY count DESC, material ASC
LIMIT 5
```

### Struktura odpovědi

```json
{
    "materials": {
        "top": ["PLA (Standard)", "PETG", "ASA", "TPU 95A (Flexibilní)", "PLA+"],
        "others": ["ABS", "HIPS", "PC", "PVA", ...]
    },
    "manufacturers": {
        "top": ["Prusa Polymers", "Devil Design", "Gembird", ...],
        "others": ["3DXTECH", "Anycubic", "AzureFilm", ...]
    }
}
```

### Frontend renderování

```javascript
// Vytvoření optgroups
if (Array.isArray(materials) && materials.top) {
    // Top skupina
    const topGroup = document.createElement('optgroup');
    topGroup.label = 'Nejčastější';
    materials.top.forEach(m => {
        const option = document.createElement('option');
        option.value = m;
        option.textContent = m;
        topGroup.appendChild(option);
    });
    select.appendChild(topGroup);
    
    // Ostatní skupina
    if (materials.others.length > 0) {
        const othersGroup = document.createElement('optgroup');
        othersGroup.label = 'Ostatní';
        materials.others.forEach(m => {
            // ...
        });
        select.appendChild(othersGroup);
    }
}
```

---

## 💰 Výpočet hodnoty skladu

### Proporcionální hodnota

Hodnota se počítá podle poměru zbývající hmotnosti k počáteční:

```
value = price * (current_weight / initial_weight_grams)
```

### Algoritmus

```php
// api/dashboard/stats.php
foreach ($rows as $row) {
    $w = (int)$row['current_weight'];
    $initW = (int)$row['initial_weight_grams'];
    $price = (float)$row['price'];
    
    if ($w > 0 && $price > 0 && $initW > 0) {
        $ratio = $w / $initW;
        $totalValue += ($price * $ratio);
    }
}
```

### Příklad

```
Filament: initial = 1000g, price = 500 Kč, current = 800g
Ratio = 800 / 1000 = 0.8
Value = 500 * 0.8 = 400 Kč
```

---

## 📈 Průměrná cena za kg (Kč/kg) ve wizardu

### Účel

Na kartách MAT, BAR a VÝR se zobrazuje průměrná cena za kilogram (Kč/kg) – buď pro skupinu (materiál, barva, výrobce), nebo pro jednotlivý filament. Umožňuje rychle porovnat ceny bez otevírání detailu.

### Vzorec

- Do výpočtu se započítávají **pouze filamenty s vyplněnou cenou** (`price > 0`).
- Základ pro přepočet je **původní hmotnost** (`initial_weight_grams`), ne aktuální zůstatek.
- Průměr: `(součet cen) / (součet původních hmotností v kg)` zaokrouhleno na celé Kč.

```
avg_czk_per_kg = ROUND( SUM(price) / (SUM(initial_weight_grams) / 1000) )
```

### Jedna funkce (DRY)

Výpočet je centralizovaný v jedné funkci **`getAvgCzkPerKg(items)`** v `assets/js/utils.js`. Volá se na všech místech:

- **MAT** – `getAvgCzkPerKg(stats[m].items)` pro kartu materiálu
- **BAR** – `getAvgCzkPerKg(info.items)` pro kartu barvy
- **VÝR skupina** – `getAvgCzkPerKg(items)` pro skupinu filamentů
- **VÝR jednotlivý** – `getAvgCzkPerKg([item])` pro jeden filament

Pravidla (jen položky s cenou, použití původní hmotnosti) se tedy mění na jednom místě.

### Příklad

```
Filament A: price = 500 Kč, initial_weight_grams = 1000
Filament B: price = 300 Kč, initial_weight_grams = 500
Filament C: price = 0 (nevyplněno) – nezapočítá se

Součet cen = 800 Kč, součet původních = 1,5 kg
Průměr = 800 / 1,5 = 533,33 → zobrazeno 533 Kč/kg
```

---

## 🔢 Automatické číslování (user_display_id)

### Logika při vytváření

1. Najde se maximum `user_display_id` v evidenci
2. Nový filament dostane `MAX + 1`
3. Pokud není žádný filament, začíná se od `1`

### SQL dotaz

```sql
SELECT COALESCE(MAX(user_display_id), 0) + 1 as next_id
FROM filaments
WHERE inventory_id = ?
```

### Validace při úpravě

- Uživatel může změnit `user_display_id` při editaci
- Systém kontroluje duplicity v rámci evidence
- Pokud je duplicita, vrátí chybu

### Příklad

```
Evidence obsahuje: #1, #2, #3, #5
Nový filament dostane: #6 (ne #4, protože #5 už existuje)
```

---

## 🏭 Automatické vytváření výrobců

### Algoritmus při ukládání filamentu

```php
// api/filaments/save.php
if ($manufacturerName) {
    // 1. Zkusit najít existujícího výrobce
    $stmt = $pdo->prepare("SELECT id FROM manufacturers WHERE name = ?");
    $stmt->execute([$manufacturerName]);
    $manufacturer = $stmt->fetch();
    
    // 2. Pokud neexistuje, vytvořit nového
    if (!$manufacturer) {
        $stmt = $pdo->prepare("INSERT INTO manufacturers (name) VALUES (?)");
        $stmt->execute([$manufacturerName]);
        $manufacturerId = $pdo->lastInsertId();
    }
}
```

### Chování

- **Case-sensitive** - "Prusa Polymers" ≠ "prusa polymers"
- **Automatické** - Uživatel zadá nový výrobce → automaticky se vytvoří
- **Bez duplicit** - UNIQUE constraint na `name`

---

## 📦 Výpočet brutto hmotnosti (s cívkou)

### Vzorec

```
brutto = current_weight + spool_weight
```

### SQL dotaz

```sql
SELECT
    (f.initial_weight_grams + COALESCE(SUM(cl.amount_grams), 0)
     + COALESCE((SELECT st.weight_grams FROM spool_types st
                 WHERE st.spool_type_id = f.spool_type_id AND st.approved = 1 AND st.invalidated_at IS NULL
                 LIMIT 1), 0)) AS brutto
FROM filaments f
LEFT JOIN consumption_log cl ON f.id = cl.filament_id
WHERE f.id = ?
GROUP BY f.id
```

### Režim vážení

Aplikace podporuje dva režimy:

1. **Bez cívky** (`consumeMode = 'used'`):
   - Uživatel zadá čistou hmotnost (netto)
   - `amount_grams = -zadaná_hmotnost`

2. **S cívkou** (`consumeMode = 'weight'`):
   - Uživatel zadá celkovou hmotnost (brutto)
   - `amount_grams = -(zadaná_hmotnost - spool_weight)`

### Příklad

```
Filament: current = 800g, spool = 200g
Brutto = 800 + 200 = 1000g

Režim "S cívkou":
- Uživatel zváží: 600g (brutto)
- Netto = 600 - 200 = 400g
- amount_grams = -400g
- Nová aktuální hmotnost: 800 - 400 = 400g
```

---

## 📊 Statistiky spotřeby (30 dní)

### Algoritmus

```sql
SELECT SUM(cl.amount_grams) as consumed
FROM consumption_log cl
JOIN filaments f ON cl.filament_id = f.id
WHERE f.inventory_id = ?
  AND cl.amount_grams < 0  -- Pouze záporné hodnoty (čerpání)
  AND cl.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
```

### Logika

- Sčítají se pouze **záporné hodnoty** (čerpání)
- Kladné hodnoty (korekce) se ignorují
- Pouze záznamy z posledních 30 dní
- Výsledek se převede na absolutní hodnotu (`ABS()`)

---

## 🔐 JWT Token generování a ověření

### Generování tokenu

```php
// api/helpers/jwt.php
function generateJWT($payload, $secret, $expiresIn = 3600) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    
    $payload['iat'] = time();
    $payload['exp'] = time() + $expiresIn;
    $payload = json_encode($payload);
    
    // Base64URL encoding
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    // HMAC SHA256 signature
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}
```

### Ověření tokenu

```php
function verifyJWT($token, $secret) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    
    // Ověření podpisu
    $signature = hash_hmac('sha256', $parts[0] . "." . $parts[1], $secret, true);
    $signatureCheck = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    if (!hash_equals($parts[2], $signatureCheck)) return null;
    
    // Dekódování payload
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
    
    // Kontrola expirace
    if (isset($payload['exp']) && $payload['exp'] < time()) return null;
    
    return $payload;
}
```

### Použití

- **Reset hesla**: Token obsahuje `email` a `purpose = 'password_reset'`
- **Platnost**: 1 hodina (3600 sekund)
- **Bezpečnost**: `hash_equals()` pro constant-time comparison (ochrana proti timing attacks)

---

## 🔄 Session-based inventory switching

### Algoritmus přepínání

```php
// api/inventory/switch.php
// 1. Ověření, že uživatel má přístup k evidenci
$sql = "
    SELECT i.id, 'owner' as role
    FROM inventories i
    WHERE i.owner_id = ? AND i.id = ?
    UNION
    SELECT i.id, im.role
    FROM inventories i
    JOIN inventory_members im ON i.id = im.inventory_id
    WHERE im.user_id = ? AND i.id = ?
";
// 2. Nastavení session
$_SESSION['inventory_id'] = $inventoryId;
```

### Frontend synchronizace

```javascript
// Po přepnutí načíst nová data
await loadData();
router.push(BASE_PATH + '/wizard/mat');
```

---

## 📧 E-mail notifikace

### Typy e-mailů

1. **Reset hesla** - JWT token v odkazu
2. **Pozvánka do evidence** - Sdílecí kód nebo přímá pozvánka
3. **Změna role** - Notifikace o změně oprávnění

### SMTP konfigurace

- PHPMailer knihovna
- Podpora TLS/SSL
- Konfigurace přes `.env` soubor

### Template systém

- HTML šablony v `api/helpers/email.php`
- Funkce `getEmailTemplate()` pro wrapper
- Funkce `sendEmail()` pro odesílání
