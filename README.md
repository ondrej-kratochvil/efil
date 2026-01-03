# eFil - Profesionální Evidence Filamentů

Aplikace pro komplexní správu 3D tiskových materiálů (filamentů). Poskytuje uživatelům přesný přehled o stavu zásob na základě reálného čerpání, nikoliv jen odhadů.

## 🚀 Funkce

### Správa dat
- **Správa filamentů** - Evidence materiálů s detailními informacemi (materiál, barva, výrobce, hmotnost)
- **Sledování spotřeby** - Záznam čerpání materiálu s datem a možností vážení nebo manuálního zadání
- **Historie čerpání** - Kompletní přehled všech čerpání s možností editace a mazání
- **Statistiky** - Přehled celkové hmotnosti, hodnoty a spotřeby za různá období
- **Chytré filtry** - Navigace MAT → BAR → VÝR pro snadné vyhledávání
- **Knihovna cívek** - Správa typů cívek s tárou a vazbou na výrobce
- **Groupování cívek** - Automatické seskupování více cívek stejného materiálu a barvy
- **Chytré třídění** - Optgroups pro typy cívek podle vybraného výrobce

### Multiuser funkce
- **Sdílení evidencí** - Sdílení skladu s dalšími uživateli pomocí přístupových kódů nebo emailu
- **Správa uživatelů** - Přidávání uživatelů, změna oprávnění (read/write/manage/owner)
- **Email notifikace** - Automatické notifikace o změnách v evidenci, pozváních, změnách rolí
- **Správa účtu** - Změna hesla, emailu, smazání účtu
- **Zapomenuté heslo** - Obnovení hesla pomocí JWT tokenu v emailovém odkazu
- **Přepínání evidencí** - Snadný přepínač mezi více evidencemi pro uživatele s přístupem k více skladům
- **Demo režim** - Read-only režim pro vyzkoušení aplikace (admin má plná práva)
- **Admin účet** - Speciální účet administrátora s přehledem všech evidencí a statistik systému

### UI/UX vylepšení
- **Routování** - Podpora tlačítek Zpět/Vpřed v prohlížeči pomocí History API
- **Chytré rozbalovací seznamy** - Optgroups s nejčastějšími hodnotami (materiály, výrobci)
- **Automatické vytváření výrobců** - Noví výrobci se automaticky přidají do databáze
- **Vylepšené zobrazení cívek** - Detailní informace o cívkách s možností správy
- **Režim vážení** - Volba mezi "Bez cívky" (netto) a "S cívkou" (brutto)
- **Persistentní formuláře** - Hodnoty se zachovávají při přepínání mezi módy
- **Smazání filamentů** - Možnost smazat filament s potvrzením
- **Skrytí prázdných filamentů** - Automaticky se nezobrazují filamenty s nulovou hmotností
- **Intro na login stránce** - Přehledné představení aplikace s funkcemi a kontaktem
- **Nápověda** - Komplexní nápověda s návody k používání všech funkcí
- **Intuitivní groupování** - Více cívek stejného typu zobrazeno jako jedna položka s možností rozbalení

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
   
   Nebo vytvořte vlastní účet pomocí registrace.

**Poznámka:** Demo účet je read-only. Pro plný přístup si vytvořte vlastní účet.

## 📁 Struktura projektu

```
efil-github/
├── api/                           # Backend API endpointy
│   ├── account/                  # Správa účtu (změna hesla, emailu, smazání)
│   ├── admin/                    # Admin funkce (statistiky eFil)
│   ├── auth/                     # Autentizace (login, register, logout, forgot-password, reset-password)
│   ├── consumption/              # Historie čerpání (list, update, delete, get)
│   ├── dashboard/                # Statistiky skladu
│   ├── data/                     # Data pro selecty (materiály, výrobci, atd.)
│   ├── filaments/               # Správa filamentů (list, save, consume, delete)
│   ├── helpers/                  # Pomocné funkce (JWT, email)
│   ├── inventory/               # Správa evidencí (list, switch, share, join)
│   ├── spools/                  # Knihovna cívek (list, create, update, delete)
│   └── users/                   # Správa uživatelů (list, add, update-role, remove)
├── assets/
│   ├── css/                     # Styly (Tailwind CSS)
│   └── js/                      # Frontend JavaScript (ES6+, History API)
├── database/
│   └── schema.sql               # Kompletní databázové schéma
├── tests/                       # Testovací skripty
├── config.php                   # Konfigurace databáze a aplikace
├── .env.example                 # Příklad konfigurace prostředí
├── init_db.php                  # Inicializační skript
├── update_*.php                 # Migrační skripty
├── index.html                   # Hlavní HTML soubor
├── CHANGELOG.md                 # Seznam změn
├── UPGRADE.md                   # Návod na upgrade
└── README.md                    # Tento soubor
```

## 🗄️ Databázové schéma

Aplikace používá následující hlavní tabulky:

- **users** - Uživatelé systému (role: user, admin_efil)
- **inventories** - Evidence skladů (včetně demo režimu)
- **inventory_access** - Přístupové kódy pro sdílení
- **inventory_members** - Členové sdílených evidencí (role: read, write, manage, owner)
- **filaments** - Filamenty ve skladu
- **consumption_log** - Záznamy spotřeby s datem a autorem
- **spool_library** - Knihovna typů cívek
- **spool_manufacturer** - Vazební tabulka M:N mezi cívkami a výrobci
- **manufacturers** - Výrobci

## 🔐 API Endpointy

### Autentizace
- `POST /api/auth/login.php` - Přihlášení
- `POST /api/auth/register.php` - Registrace
- `GET /api/auth/logout.php` - Odhlášení
- `GET /api/auth/me.php` - Informace o přihlášeném uživateli
- `POST /api/auth/forgot-password.php` - Zapomenuté heslo
- `POST /api/auth/reset-password.php` - Nastavení nového hesla

### Filamenty
- `GET /api/filaments/list.php` - Seznam filamentů
- `POST /api/filaments/save.php` - Uložení/úprava filamentu
- `POST /api/filaments/consume.php` - Záznam spotřeby s datem
- `POST /api/filaments/delete.php` - Smazání filamentu

### Čerpání
- `GET /api/consumption/list.php` - Historie čerpání (pro celý inventář nebo konkrétní filament)
- `GET /api/consumption/get.php` - Detail jednoho záznamu čerpání
- `POST /api/consumption/update.php` - Úprava záznamu čerpání
- `POST /api/consumption/delete.php` - Smazání záznamu čerpání

### Data
- `GET /api/data/options.php` - Možnosti pro selecty (materiály, výrobci, atd.) s optgroups pro nejčastější hodnoty

### Cívky
- `GET /api/spools/list.php` - Seznam typů cívek s vazbami na výrobce
- `POST /api/spools/create.php` - Vytvoření nového typu cívky
- `POST /api/spools/update.php` - Úprava typu cívky
- `POST /api/spools/delete.php` - Smazání typu cívky

### Statistiky
- `GET /api/dashboard/stats.php` - Statistiky skladu
- `GET /api/admin/stats.php` - Celkové statistiky eFil (pouze pro admin_efil)

### Evidence
- `GET /api/inventory/list.php` - Seznam evidencí uživatele
- `POST /api/inventory/switch.php` - Přepnutí mezi evidencemi
- `POST /api/inventory/share.php` - Vygenerování sdílecího kódu
- `POST /api/inventory/join.php` - Připojení k evidenci pomocí kódu

### Uživatelé
- `GET /api/users/list.php` - Seznam uživatelů v evidenci
- `POST /api/users/add.php` - Přidání uživatele do evidence
- `POST /api/users/update-role.php` - Změna role uživatele
- `POST /api/users/remove.php` - Odebrání uživatele z evidence

### Účet
- `POST /api/account/change-password.php` - Změna hesla
- `POST /api/account/change-email.php` - Změna emailu
- `POST /api/account/delete.php` - Smazání účtu

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

