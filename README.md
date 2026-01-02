# eFil - Profesionální Evidence Filamentů

Aplikace pro komplexní správu 3D tiskových materiálů (filamentů). Poskytuje uživatelům přesný přehled o stavu zásob na základě reálného čerpání, nikoliv jen odhadů.

## 🚀 Funkce

- **Správa filamentů** - Evidence materiálů s detailními informacemi (materiál, barva, výrobce, hmotnost)
- **Sledování spotřeby** - Záznam čerpání materiálu s možností vážení nebo manuálního zadání
- **Sdílení evidencí** - Sdílení skladu s dalšími uživateli pomocí přístupových kódů
- **Statistiky** - Přehled celkové hmotnosti, hodnoty a spotřeby
- **Chytré filtry** - Navigace MAT → BAR → VÝR pro snadné vyhledávání
- **Knihovna cívek** - Správa typů cívek s tárou pro přesné vážení
- **Chytré rozbalovací seznamy** - Optgroups s nejčastějšími hodnotami (materiály, výrobci)
- **Automatické vytváření výrobců** - Noví výrobci se automaticky přidají do databáze při ukládání filamentu
- **Vylepšené zobrazení cívek** - Detailní informace o cívkách (barva, materiál, průměr, šířka, hmotnost, popis)
- **Režim vážení** - Volba mezi "Bez cívky" (netto) a "S cívkou" (brutto) s automatickým výpočtem
- **Persistentní formuláře** - Hodnoty formuláře se zachovávají při přepínání mezi select/input módy

## 📋 Požadavky

- **PHP** 8.0 nebo vyšší
- **MySQL/MariaDB** 5.7 nebo vyšší
- **Webový server** (Apache/Nginx) nebo WAMP/XAMPP
- **Rozšíření PHP**: PDO, PDO_MySQL

## 🔧 Instalace

### 1. Naklonování repozitáře

```bash
git clone <repository-url>
cd efil-github
```

### 2. Konfigurace databáze

Vytvořte soubor `.env` v kořenovém adresáři projektu (nebo upravte `config.php` přímo):

```env
DB_HOST=localhost
DB_NAME=efil_db
DB_USER=root
DB_PASS=
```

**Poznámka:** Soubor `.env` je v `.gitignore`, takže nebude commitován do repozitáře.

### 3. Inicializace databáze

Spusťte inicializační skript, který vytvoří databázi, tabulky a naplní je demo daty:

```bash
php init_db.php
```

Nebo v prohlížeči:
```
http://localhost/a/efil-github/init_db.php
```

**Poznámka:** Skript automaticky vytvoří databázi, pokud neexistuje, a smaže existující tabulky před vytvořením nových.

### 4. Aktualizace existující databáze

Pokud již máte databázi s daty a potřebujete aktualizovat schéma bez ztráty dat:

```bash
# Přidání tabulky inventory_members
php update_schema.php

# Aktualizace tabulky spool_library (přidání nových polí)
php update_spool_schema.php
```

**Poznámka:** Pro nové instalace použijte `init_db.php`, který vytvoří kompletní schéma. Migrační skripty jsou určeny pouze pro aktualizaci existujících databází.

## 🎯 Spuštění

1. **Ujistěte se, že běží webový server a MySQL**

2. **Otevřete aplikaci v prohlížeči:**
   ```
   http://localhost/a/efil-github/index.html
   ```

3. **Přihlaste se pomocí demo účtu:**
   - **Email:** `demo@efil.cz`
   - **Heslo:** `demo1234`

## 📁 Struktura projektu

```
efil-github/
├── api/                    # Backend API endpointy
│   ├── auth/              # Autentizace (login, register, logout)
│   ├── dashboard/         # Statistiky
│   ├── data/              # Data pro selecty (materiály, výrobci, atd.)
│   ├── filaments/        # Správa filamentů (list, save, consume)
│   ├── inventory/        # Sdílení evidencí (join, share)
│   └── spools/           # Knihovna cívek
├── assets/
│   ├── css/              # Styly
│   └── js/               # Frontend JavaScript
├── database/
│   └── schema.sql        # Databázové schéma
├── config.php            # Konfigurace databáze
├── init_db.php           # Inicializační skript
├── update_schema.php     # Migrační skript
├── index.html            # Hlavní HTML soubor
└── README.md             # Tento soubor
```

## 🗄️ Databázové schéma

Aplikace používá následující hlavní tabulky:

- **users** - Uživatelé systému
- **inventories** - Evidence skladů
- **inventory_access** - Přístupové kódy pro sdílení
- **inventory_members** - Členové sdílených evidencí
- **filaments** - Filamenty ve skladu
- **consumption_log** - Záznamy spotřeby
- **spool_library** - Knihovna typů cívek
- **manufacturers** - Výrobci

## 🔐 API Endpointy

### Autentizace
- `POST /api/auth/login.php` - Přihlášení
- `POST /api/auth/register.php` - Registrace
- `GET /api/auth/logout.php` - Odhlášení
- `GET /api/auth/me.php` - Informace o přihlášeném uživateli

### Filamenty
- `GET /api/filaments/list.php` - Seznam filamentů
- `POST /api/filaments/save.php` - Uložení/úprava filamentu
- `POST /api/filaments/consume.php` - Záznam spotřeby

### Data
- `GET /api/data/options.php` - Možnosti pro selecty (materiály, výrobci, atd.) s optgroups pro nejčastější hodnoty
- `GET /api/spools/list.php` - Seznam typů cívek
- `POST /api/spools/save.php` - Uložení nového typu cívky
- `GET /api/dashboard/stats.php` - Statistiky skladu

### Sdílení
- `POST /api/inventory/share.php` - Vygenerování sdílecího kódu
- `POST /api/inventory/join.php` - Připojení k evidenci pomocí kódu

## 🎨 Funkce aplikace

### Navigace
Aplikace používá třístupňovou navigaci:
1. **MAT** (Materiál) - Výběr typu materiálu (PLA, PETG, ASA, atd.)
2. **BAR** (Barva) - Výběr barvy
3. **VÝR** (Výrobce/Detail) - Detailní seznam filamentů

### Správa filamentů
- **Přidání** - Klikněte na "Přidat nový filament" v menu nebo na tlačítko, pokud není žádný filament
- **Úprava** - Klikněte na filament v detailním zobrazení
- **Spotřeba** - Klikněte na hmotnost filamentu pro záznam spotřeby
- **Číslování** - Každý filament má `user_display_id` začínající od #1 pro každou evidenci
  - Číslo je uživatelsky nastavitelné ve formuláři
  - Automaticky se navrhne další dostupné číslo (MAX + 1)
  - Systém kontroluje duplicity v rámci evidence
  - Při editaci lze číslo změnit (s kontrolou duplicit)

### Chytré rozbalovací seznamy
- **Optgroups** - Materiály a výrobci jsou rozděleny do skupin:
  - **Nejčastější** - Top 5 nejčastěji používaných hodnot (pokud existují filamenty)
  - **Ostatní** - Zbývající hodnoty seřazené podle abecedy
- **Automatické vytváření** - Nové hodnoty se automaticky přidají do databáze při ukládání
- **Výchozí hodnoty** - Vždy jsou k dispozici výchozí materiály a výrobci

### Správa cívek
- **Detailní charakteristiky** - Barva, materiál, vnější průměr, šířka, hmotnost, popis
- **Zobrazení v seznamu** - Všechny charakteristiky jsou viditelné v rozbalovacím seznamu
- **Vytváření nových** - Možnost přidat nový typ cívky přímo při přidávání filamentu

### Režim vážení
- **Bez cívky** - Zadání čisté hmotnosti filamentu (netto)
- **S cívkou** - Zadání celkové hmotnosti (brutto), automatický výpočet netto = brutto - hmotnost cívky
- **Informační zobrazení** - Zobrazení aktuálního režimu a vypočítané hodnoty

### Sdílení skladu
1. Otevřete "Přehled skladu"
2. Klikněte na "Vygenerovat kód"
3. Sdílejte kód s dalšími uživateli
4. Uživatelé se připojí pomocí "Mám kód pozvánky" na přihlašovací stránce

## 🔒 Bezpečnost

- Hesla jsou hashována pomocí bcrypt
- Session management pro autentizaci
- Ošetření SQL injection pomocí PDO prepared statements
- Kontrola oprávnění pro sdílené evidence (read/write/manage)

## 🛠️ Technický stack

- **Backend:** PHP 8.x s PDO
- **Frontend:** HTML5, Tailwind CSS, Vanilla JavaScript (ES6+)
- **Databáze:** MySQL/MariaDB
- **Komunikace:** AJAX (Fetch API)

## 🧪 Testování

Projekt obsahuje testovací skripty v adresáři `tests/`:

```bash
# Test výpočtu zůstatků
php tests/balance_test.php

# Test automatického vytváření výrobců
php tests/manufacturer_auto_create_test.php

# Test optgroups v options API
php tests/options_optgroups_test.php

# Test správy cívek
php tests/spool_management_test.php

# Test formulářových hodnot
php tests/form_persistence_test.php
```

Všechny testy automaticky vytvářejí testovací data a po dokončení je odstraňují.

## 📝 Poznámky

- Aplikace automaticky vytvoří databázi při prvním spuštění `init_db.php`
- Demo data jsou vytvořena automaticky při inicializaci
- Pro produkční nasazení upravte `config.php` pro lepší error handling
- Noví výrobci se automaticky přidají do tabulky `manufacturers` při ukládání filamentu
- Hodnoty formuláře se automaticky ukládají a obnovují při přepínání mezi select/input módy

## 🐛 Řešení problémů

### Databáze neexistuje
Spusťte `init_db.php` - skript automaticky vytvoří databázi.

### Chyba s indexy (Index column size too large)
Tento problém byl vyřešen v aktuální verzi schématu pomocí prefix indexů a `ROW_FORMAT=DYNAMIC`.

### Data se nenačítají
1. Zkontrolujte, že jste přihlášeni
2. Otevřete konzoli prohlížeče (F12) a zkontrolujte chyby
3. Zkontrolujte, že API endpointy vracejí data (otevřete přímo v prohlížeči)

## 📄 Licence

[Uveďte licenci projektu]

## 👥 Autoři

[Uveďte autory projektu]

