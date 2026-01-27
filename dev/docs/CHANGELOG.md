# eFil - Seznam změn

## Verze 2.0 - Multiuser a rozšířené funkce

### ✅ Implementované funkce

#### 🔐 Správa uživatelů a účtů
- **Zapomenuté heslo** - kompletní flow s JWT tokeny a emailovými notifikacemi
- **Správa účtu** - změna hesla, emailu, smazání účtu
- **API pro správu uživatelů** - přidání, odebrání, změna oprávnění (read/write/manage)
- **Email notifikace** - automatické emaily při:
  - Vytvoření nového účtu
  - Pozvání do evidence
  - Změně oprávnění
  - Odebrání z evidence

#### 🔧 Technické vylepšení
- **History API routování** - navigace zpět/vpřed v prohlížeči funguje správně
- **Rozšířené DB schéma**:
  - Datum čerpání (`consumption_date`)
  - Autor čerpání (`created_by`)
  - JWT secret pro tokeny
  - SMTP konfigurace

#### 🎨 UI vylepšení
- **Větší tlačítko hmotnosti** - snadnější klikání na zápis čerpání
- **Umístění před ID** - lepší přehlednost (např. "Polička A | #1")
- **Odstraněna zelená tečka** - jednodušší UI
- **Odstraněn text "Zůstatek"** - více prostoru pro hmotnost

#### 📊 Data a zobrazení
- **Prázdné filamenty skryty** - automaticky se nezobrazují filamenty s nulovou hmotností
- **Tlačítko Smazat** - v editaci filamentu s potvrzením
- **Demo režim read-only** - demo evidence nelze editovat (kromě admin_efil)
- **Materiály a výrobci pouze z DB** - odstraněny hardcoded seznamy
- **Jednotná barevná paleta** - demo i uživatelské rozhraní používají stejné barvy

### 📋 Zbývající úkoly (plánováno)

#### Vysoká priorita
- [ ] UI pro správu uživatelů v menu
- [ ] Historie čerpání s editací
- [ ] Groupování cívek (více cívek stejné barvy)
- [ ] Přepínání mezi evidencemi

#### Střední priorita
- [ ] Správa typů cívek s vazbami na výrobce (M:N)
- [ ] Představení aplikace na přihlašovací stránce
- [ ] Podrobná nápověda
- [ ] Statistiky eFil pro admina

### 🔒 Bezpečnost
- JWT tokeny pro reset hesla (1 hodina platnost)
- Password setup tokeny (24 hodin platnost)
- SMTP konfigurace v .env
- Demo režim read-only

### 📧 SMTP konfigurace

Přidejte do `.env`:
```env
JWT_SECRET=your-secret-key-change-this-in-production
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=
SMTP_PASSWORD=
SMTP_FROM_EMAIL=noreply@efil.cz
SMTP_FROM_NAME=eFil - Evidence Filamentů
```

### 🗄️ Databázové změny

Spusťte migrační skript pro existující databáze:
```bash
php update_consumption_schema.php
```

Nebo pro nové instalace:
```bash
php init_db.php
```

### 📝 Poznámky pro vývojáře
- Všechny routy jsou nyní v History API
- Email systém používá PHP mail() (pro produkci doporučujeme SMTP službu)
- JWT tokeny jsou signed s tajným klíčem z konfigurace
- Demo evidence má `is_demo = 1` v tabulce inventories
