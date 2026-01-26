# Manuální testy - eFil

Tento dokument obsahuje manuální testovací scénáře pro ověření funkcionality aplikace.

## 📋 Test: Načítání dat při úpravě filamentu

**Cíl:** Ověřit, že při otevření formuláře pro úpravu existujícího filamentu se zobrazí všechny údaje správně, i když byl filament právě přidán.

### Postup:

1. **Přihlášení**
   - Otevřete aplikaci v prohlížeči
   - Přihlaste se pomocí existujícího účtu

2. **Přidání nového filamentu**
   - Klikněte na "Přidat nový filament"
   - Vyplňte formulář:
     - Materiál: `PETG`
     - Výrobce: `Test Manufacturer`
     - Barva: `Černá` (#000000)
     - Hmotnost: `850g`
     - Umístění: `Regál A`
     - Cena: `450.50`
     - Prodejce: `Test Seller`
     - Datum nákupu: `2024-01-15`
   - Klikněte na "Uložit"

3. **Navigace na homepage**
   - Po uložení byste měli být přesměrováni na homepage (wizard)
   - Ověřte, že nový filament je viditelný v seznamu

4. **Otevření formuláře pro úpravu**
   - Proklikejte se k novému filamentu:
     - Klikněte na materiál `PETG`
     - Klikněte na barvu `Černá`
     - V detailním zobrazení klikněte na tlačítko "Upravit" u nového filamentu

5. **Ověření zobrazení údajů**
   - ✅ **Očekávaný výsledek:** Ve formuláři by se měly zobrazit všechny údaje:
     - Materiál: `PETG`
     - Výrobce: `Test Manufacturer`
     - Barva: `Černá` s hex kódem `#000000`
     - Hmotnost: `850g`
     - Umístění: `Regál A`
     - Cena: `450.50`
     - Prodejce: `Test Seller`
     - Datum nákupu: `2024-01-15`
   - ❌ **Chyba:** Pokud se některé údaje nezobrazí nebo jsou prázdné, jedná se o chybu

6. **Test bez přenačtení stránky**
   - **Důležité:** Neobnovujte stránku (F5)
   - Údaje by se měly zobrazit správně i bez přenačtení

### Varianty testu:

#### Varianta A: Nový filament
- Přidejte nový filament
- Okamžitě po uložení proklikejte se k němu a otevřete úpravy
- Ověřte, že všechny údaje jsou zobrazeny

#### Varianta B: Existující filament
- Otevřete úpravy existujícího filamentu (který byl přidán dříve)
- Ověřte, že všechny údaje jsou zobrazeny

#### Varianta C: Více filamentů
- Přidejte několik filamentů za sebou
- Pro každý otevřete úpravy a ověřte správné zobrazení údajů

### Očekávané chování:

- ✅ Všechny údaje se zobrazí správně
- ✅ Formulář je plně vyplněn
- ✅ Uživatel může údaje upravit
- ✅ Uložení úprav funguje správně

### Známé problémy (pokud existují):

- Žádné známé problémy

---

## 📋 Test: Duplicitní zobrazení historie čerpání

**Cíl:** Ověřit, že po uložení úpravy čerpání se blok "Historie čerpání" nezobrazí duplicitně.

### Postup:

1. **Přihlášení a navigace**
   - Přihlaste se do aplikace
   - Proklikejte se k existujícímu filamentu s historií čerpání

2. **Otevření úpravy čerpání**
   - V bloku "Historie čerpání" klikněte na tlačítko "Upravit" u některého záznamu

3. **Úprava a uložení**
   - Upravte hodnoty (např. hmotnost, datum, poznámku)
   - Klikněte na "Uložit"

4. **Ověření zobrazení**
   - ✅ **Očekávaný výsledek:** Blok "Historie čerpání" se zobrazí pouze jednou
   - ❌ **Chyba:** Pokud se blok zobrazí dvakrát nebo vícekrát, jedná se o chybu

---

## 📋 Test: Obecné testy formuláře

### Test 1: Vytvoření nového filamentu
- Otevřete formulář pro přidání nového filamentu
- Vyplňte všechny povinné pole
- Uložte
- Ověřte, že filament se zobrazí v seznamu

### Test 2: Úprava existujícího filamentu
- Otevřete úpravy existujícího filamentu
- Změňte některé hodnoty
- Uložte
- Ověřte, že změny se projevily

### Test 3: Zrušení úprav
- Otevřete úpravy filamentu
- Změňte hodnoty
- Zavřete formulář bez uložení
- Otevřete úpravy znovu
- Ověřte, že původní hodnoty zůstaly zachovány

---

## 📝 Poznámky k testování

- Před testováním se ujistěte, že máte čistou databázi nebo testovací data
- Testujte v různých prohlížečích (Chrome, Firefox, Edge)
- Testujte na různých velikostech obrazovky (desktop, tablet, mobil)
- Při nalezení chyby zaznamenejte:
  - Krok, ve kterém došlo k chybě
  - Očekávané chování
  - Skutečné chování
  - Screenshot (pokud je to možné)
  - Verze prohlížeče a OS
