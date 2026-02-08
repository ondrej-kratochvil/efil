# Soft delete a verzování

Dokumentace vzoru **soft delete** (měkké smazání) a **verzování** záznamů. Vzor se v projektu používá u výrobců (manufacturers) a je určen k opakovanému použití u dalších entit (materiály, typy cívek, atd.).

---

## 1. Základní pojmy

### Soft delete (měkké smazání)

- Záznam se **nesmaže fyzicky** z databáze (žádné `DELETE`).
- Označí se jako neplatný pomocí sloupců **`invalidated_at`** a **`invalidated_by`**.
- **Platná verze** = `invalidated_at IS NULL`.
- **Smazaný záznam** = poslední verze entity má nastavené `invalidated_at` (a volitelně `invalidated_by`).
- Výhody: zachování historie, integrita odkazů (cizí klíče stále ukazují na existující řádek), možnost auditování (kdo a kdy zneplatnil).

### Verzování

- Jeden **logický záznam** (např. jeden výrobce) může mít v tabulce **více řádků** – každý řádek = jedna verze.
- Společný identifikátor verzí: **kořenové id** (např. `manufacturer_id`) – stejná hodnota u všech verzí téhož záznamu.
- **Aktuální verze** = jediná verze s `invalidated_at IS NULL` pro dané kořenové id (případně rozlišení schválená/návrh – viz níže).
- Každá verze nese: `created_at`, `created_by` (kdo verzi vytvořil). Původní autor entity = `created_by` u první (nejstarší) verze.

---

## 2. Sloupce v tabulce s verzováním a soft delete

Následující sloupce jsou společné pro entity, u kterých používáme tento vzor:

| Sloupec           | Typ         | Význam |
|-------------------|-------------|--------|
| `id`              | PK, auto inc| Id **řádku** (jedné verze). Jednoznačné pro každý řádek. |
| `<entity>_id`     | INT         | **Kořenové id** – společné pro všechny verze téhož logického záznamu. Např. `manufacturer_id`. Nové kořenové id = `COALESCE(MAX(<entity>_id), 0) + 1`. |
| …                 |             | Atributy entity (název, příznaky, atd.). |
| `public`          | TINYINT(1)  | (Volitelně) 0 = soukromý záznam uživatele, 1 = veřejný. |
| `approved`        | TINYINT(1)  | 1 = schválená verze (zobrazuje se uživatelům), 0 = neschválená (návrh – zobrazuje se jen autorovi a adminovi). U entit bez schvalování vždy 1. |
| `created_at`      | DATETIME    | Kdy byla tato verze vytvořena. |
| `created_by`      | INT NOT NULL| Id uživatele, který tuto verzi vytvořil (včetně schválení = admin). FK na users(id). Vždy vyplněno. |
| `invalidated_at`  | DATETIME NULL | Kdy byla verze zneplatněna. NULL = verze je platná. |
| `invalidated_by`  | INT NULL    | Id uživatele, který verzi zneplatnil (smazal / nahradil). FK na users(id). |

- **Platná verze:** `invalidated_at IS NULL`.
- **Smazaný logický záznam:** poslední verze (řádek s daným `<entity>_id`) má `invalidated_at` nastaveno.
- **Původní autor:** první verze v historii (minimální `id` nebo minimální `created_at` pro dané `<entity>_id`) → její `created_by`.

---

## 3. Dotazování

### Aktuální schválená verze (pro běžné zobrazení)

- Pro dané kořenové id `<entity>_id`:
  - platná **a** schválená:  
    `WHERE <entity>_id = ? AND approved = 1 AND invalidated_at IS NULL`
  - Očekáváme **nejvýše jeden** takový řádek na jedno `<entity>_id`.

### Aktuální neschválená verze (návrh)

- Návrh na změnu:  
  `WHERE <entity>_id = ? AND approved = 0 AND invalidated_at IS NULL`
- U jednoho logického záznamu je **nejvýše jeden** takový řádek (jeden čekající návrh).

### Seznam „živých“ logických záznamů (pro dropdown, filtry)

- Běžný uživatel: pouze schválené a platné verze, seskupeno podle `<entity>_id` (každý záznam jednou).
- Administrátor: navíc řádky s `approved = 0 AND invalidated_at IS NULL` (čekající návrhy).

### Poslední schválená verze (pro porovnání s návrhem)

- Pro dané `<entity>_id>`:  
  řádek s `approved = 1 AND invalidated_at IS NULL` (nebo nejnovější takový před invalidací, pokud už byl nahrazen – v typickém případě je právě jeden „current approved“).

### Historie verzí

- Všechny řádky se stejným `<entity>_id>` seřazené např. podle `created_at` nebo `id`.
- Z každého řádku je zřejmé: kdo vytvořil (`created_by`), kdy (`created_at`), zda je platná (`invalidated_at`), kdo zneplatnil (`invalidated_by`).

---

## 4. Pravidla pro použití u dalších entit

1. **Reference z jiných tabulek**  
   Cizí klíče odkazují na **kořenové id** (`manufacturer_id`, `<entity>_id`), ne na `id` řádku. V databázi nelze na ne-unique sloupec dát FK – integrita se hlídá v aplikační vrstvě (při ukládání ověřit, že dané kořenové id existuje a má platnou verzi).

2. **Přidání nové verze**  
   - Úprava vlastního záznamu (soukromý / vlastní): nový řádek se stejným `<entity>_id>`, `approved = 1`, `invalidated_at = NULL`; stará verze dostane `invalidated_at = NOW()`, `invalidated_by = current_user`.
   - Návrh na změnu veřejného záznamu: nový řádek s `approved = 0`; stará schválená verze zůstane beze změny do schválení/zamítnutí.

3. **Schválení návrhu**  
   - Stará schválená verze: nastavit `invalidated_at`, `invalidated_by` (admin).
   - Návrh (řádek s `approved = 0`): nastavit `approved = 1` (příp. upravit název/atributy v jednom kroku). Tím se stane aktuální schválenou verzí.

4. **Zamítnutí návrhu**  
   - Řádek s `approved = 0`: nastavit `invalidated_at`, `invalidated_by`. Návrh přestane být „platnou“ neschválenou verzí.

5. **Soft delete (smazání logického záznamu)**  
   - Povoleno jen pokud na entitu neodkazuje žádný jiný záznam (např. žádný filament, žádná cívka). Jinak mazání zakázat.
   - Pokud je mazání povoleno: aktuální platnou verzi (řádek s `invalidated_at IS NULL`) nastavit `invalidated_at = NOW()`, `invalidated_by = ID uživatele, který mazání provedl`. Žádné mazání řádků – vždy se vyplní invalidated_by, aby bylo jasné, kdo záznam smazal.

6. **Získání nového kořenového id**  
   - Při vytvoření úplně nového logického záznamu:  
     `new_id = COALESCE(MAX(<entity>_id), 0) + 1`  
     (v transakci, ideálně se zámkem, aby nedocházelo ke kolizím při souběhu.)

---

## 5. Příklad: výrobci (manufacturers)

- **Tabulka:** `manufacturers`
- **Kořenové id:** `manufacturer_id` (společné pro všechny verze téhož výrobce).
- **id:** PK řádku (autoincrement).
- **Zobrazení:** Všem uživatelům pouze verze s `approved = 1 AND invalidated_at IS NULL`. Administrátor navíc vidí záznamy s `approved = 0` (návrhy); při úpravě vidí porovnání s poslední schválenou verzí.
- **Autor návrhu** vidí u daného výrobce místo schválené verze svůj návrh (verzi s `approved = 0`).
- **Mazání:** Není dovoleno, pokud je výrobce použit u alespoň jednoho filamentu nebo u vazby spool_manufacturer. Pokud není použit, smazání = nastavení `invalidated_at` u aktuální verze.

---

## 6. Shrnutí

| Situace              | Platná verze              | Zobrazení |
|----------------------|---------------------------|-----------|
| Běžný záznam         | `invalidated_at IS NULL`  | Pouze `approved = 1`. |
| Návrh čekající       | `invalidated_at IS NULL`, `approved = 0` | Autor návrhu + admin; admin vidí srovnání se schválenou. |
| Smazaný záznam       | Poslední verze má `invalidated_at` nastaveno | Do seznamů se nebere, historie zůstává. |

Tímto vzorem získáme **kompletní historii úprav** (kdo, kdy, schváleno/ne, zneplatněno) a **narušení odkazů** z jiných tabulek se předejde tím, že se nikdy neprovádí DELETE na verzované řádky a reference ukazují na stabilní kořenové id.
