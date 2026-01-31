# Manuální testy eFil

## 🧪 Přehled

Tento dokument obsahuje scénáře pro manuální testování funkcí, které nelze plně automatizovat nebo vyžadují lidské posouzení.

---

## 1. 🎨 Vizuální kontrola a UX

### Test: Vizuální konzistence
**Cíl**: Ověřit, že UI je konzistentní napříč všemi obrazovkami.

**Kroky**:
1. Projděte všechny obrazovky aplikace
2. Zkontrolujte konzistenci:
   - Barvy a styly tlačítek
   - Velikosti fontů
   - Mezery a padding
   - Zaoblené rohy karet
   - Stíny a efekty

**Očekávaný výsledek**: Všechny obrazovky mají konzistentní vzhled a chování.

**Poznámky**: Věnujte pozornost přechodům mezi různými view (wizard, form, stats).

---

### Test: Responzivita na různých zařízeních
**Cíl**: Ověřit, že aplikace funguje správně na různých velikostech obrazovek.

**Kroky**:
1. Otevřete aplikaci v prohlížeči
2. Změňte velikost okna nebo použijte DevTools pro různé breakpointy:
   - **Mobile (320px)**: Kontrola přetékání, pozice hamburgeru
   - **Tablet (768px)**: Kontrola rozpadu menu a gridů
   - **Desktop (1024px+)**: Kontrola maximální šířky obsahu
3. Zkontrolujte:
   - Čitelnost textu
   - Velikost touch targets (min. 44x44px)
   - Layout gridů a karet

**Očekávaný výsledek**: Aplikace je plně funkční a čitelná na všech velikostech.

**Poznámky**: Věnujte pozornost extrémním velikostem (320px, 4K).

---

### Test: Plynulost animací a transitions
**Cíl**: Ověřit, že animace jsou plynulé a příjemné.

**Kroky**:
1. Projděte aplikaci a pozorujte:
   - Hover efekty na kartách
   - Otevírání/zavírání menu
   - Přechody mezi view
   - Toast notifikace
2. Zkontrolujte:
   - Plynulost (60 FPS)
   - Délku trvání (ne příliš rychlé/pomalé)
   - Absenci "blikání" nebo trhání

**Očekávaný výsledek**: Všechny animace jsou plynulé a příjemné.

**Poznámky**: Testujte na různých zařízeních (včetně slabších).

---

## 2. 🎯 Interakce a navigace

### Test: Intuitivnost navigace
**Cíl**: Ověřit, že navigace je intuitivní pro nového uživatele.

**Kroky**:
1. Otevřete aplikaci jako nový uživatel (nebo použijte demo účet)
2. Zkuste bez čtení dokumentace:
   - Najít a přidat nový filament
   - Zaznamenat spotřebu
   - Zobrazit statistiky
   - Sdílet evidenci
3. Poznamenejte si:
   - Kde jste se zasekli
   - Co bylo nejasné
   - Co bylo intuitivní

**Očekávaný výsledek**: Uživatel dokáže provést základní akce bez pomoci.

**Poznámky**: Ideálně testujte s reálným uživatelem, který aplikaci nezná.

---

### Test: History API (tlačítka Zpět/Vpřed)
**Cíl**: Ověřit, že tlačítka Zpět/Vpřed v prohlížeči fungují správně.

**Kroky**:
1. Projděte aplikaci:
   - Wizard: MAT → BAR → VÝR
   - Otevřete formulář
   - Otevřete statistiky
2. Použijte tlačítko "Zpět" v prohlížeči
3. Použijte tlačítko "Vpřed"
4. Zkontrolujte:
   - URL se mění správně
   - View se aktualizuje správně
   - State je zachován

**Očekávaný výsledek**: Tlačítka Zpět/Vpřed fungují jako očekáváno.

**Poznámky**: Testujte i s přímým zadáním URL do adresního řádku.

---

### Test: Touch interakce (mobilní zařízení)
**Cíl**: Ověřit, že aplikace je použitelná na dotykových zařízeních.

**Kroky**:
1. Otevřete aplikaci na mobilním zařízení (nebo použijte DevTools emulaci)
2. Testujte:
   - Tap na karty a tlačítka
   - Swipe gesta (pokud jsou implementovány)
   - Zoom (mělo by být zakázáno pro inputy)
   - Dlouhý tap (long press)
3. Zkontrolujte:
   - Touch targets jsou dostatečně velké (min. 44x44px)
   - Žádné nechtěné akce při scrollování
   - Formuláře jsou použitelné

**Očekávaný výsledek**: Aplikace je plně použitelná na dotykových zařízeních.

**Poznámky**: Testujte na reálném zařízení, ne jen emulaci.

---

## 3. 📝 Formuláře a validace

### Test: Persistence hodnot formuláře
**Cíl**: Ověřit, že hodnoty formuláře se zachovávají při přepínání mezi módy.

**Kroky**:
1. Otevřete formulář pro přidání filamentu
2. Vyplňte některá pole:
   - Materiál: "PLA"
   - Výrobce: "Prusa Polymers"
   - Umístění: "Regál 1"
3. Přepněte některé selecty do input módu (tlačítko "+")
4. Zavřete formulář a znovu otevřete
5. Zkontrolujte, že hodnoty jsou zachovány

**Očekávaný výsledek**: Hodnoty se zachovávají při přepínání mezi módy a při znovuotevření.

**Poznámky**: Testujte i s různými kombinacemi select/input módu.

---

### Test: Validace formulářů
**Cíl**: Ověřit, že validace funguje správně a zobrazuje užitečné chybové zprávy.

**Kroky**:
1. Otevřete formulář pro přidání filamentu
2. Zkuste uložit bez vyplnění povinných polí:
   - Materiál
   - Barva
   - Hmotnost
3. Zkuste zadat neplatné hodnoty:
   - Záporná hmotnost
   - Prázdný email (v registraci)
   - Neplatný email formát
4. Zkontrolujte:
   - Zobrazí se chybové zprávy
   - Zprávy jsou v češtině
   - Zprávy jsou srozumitelné

**Očekávaný výsledek**: Validace funguje správně a zobrazuje užitečné chyby.

**Poznámky**: Testujte i edge cases (např. velmi dlouhé texty).

---

### Test: Výrobci (výběr, nový výrobce, typy cívek)
**Cíl**: Ověřit práci s výrobci ve formuláři filamentu a u typů cívek (verzované schéma).

**Kroky**:
1. **Výběr ze seznamu**
   - Otevřete formulář pro nový filament
   - V poli Výrobce vyberte existujícího výrobce ze seznamu (nejčastější / ostatní)
   - Uložte a ověřte zobrazení výrobce u filamentu a v editaci
2. **Nový výrobce**
   - U pole Výrobce klikněte na **+**, zadejte nový název (např. „Test výrobce“)
   - Uložte; ověřte, že nový výrobce je v dropdownu a při editaci předvybraný
3. **Typy cívek**
   - Menu → Typy cívek, přidejte/upravte typ cívky
   - V multiselectu Výrobci vyberte jednoho nebo více výrobců, uložte
   - Ověřte zobrazení přiřazených výrobců u typu cívky
4. **Přehled a statistiky**
   - V wizardu (MAT/BAR/VÝR) a v čerpání ověřte, že název výrobce odpovídá

**Očekávaný výsledek**: Výrobce lze vybírat, přidávat (+), zobrazují se správně u filamentů i u typů cívek.

**Poznámky**: Vyžaduje DB po migraci výrobců (`migrate_manufacturers_versioned.php`). Smazat lze jen výrobce nepoužívaného u filamentu/typu cívky.

---

## 4. 🔄 Groupování a zobrazení

### Test: Intuitivnost groupování
**Cíl**: Ověřit, že groupování filamentů je intuitivní.

**Kroky**:
1. Vytvořte několik filamentů se stejným výrobcem, materiálem a barvou
2. Zobrazte detailní seznam (VÝR)
3. Zkontrolujte:
   - Skupina se zobrazí jako jedna položka
   - Celková hmotnost je správná
   - Počet cívek je zobrazen
4. Rozbalte skupinu
5. Zkontrolujte:
   - Jednotlivé filamenty jsou viditelné
   - Tlačítko "Sbalit skupinu" funguje

**Očekávaný výsledek**: Groupování je intuitivní a uživatel rozumí, co se děje.

**Poznámky**: Testujte s různými počty filamentů ve skupině (1, 2, 5, 10+).

---

### Test: Kontrast barev na kartách
**Cíl**: Ověřit, že text je čitelný na barevném pozadí.

**Kroky**:
1. Vytvořte filamenty s různými barvami:
   - Světlé barvy (bílá, žlutá)
   - Tmavé barvy (černá, modrá)
   - Střední barvy (šedá, zelená)
2. Zobrazte karty barev (BAR krok)
3. Zkontrolujte:
   - Text je čitelný na všech barvách
   - Kontrast je dostatečný
   - Bílé barvy mají border pro viditelnost

**Očekávaný výsledek**: Text je čitelný na všech barvách.

**Poznámky**: Testujte i s extrémními barvami (#000000, #FFFFFF).

---

## 5. 🔐 Bezpečnost a oprávnění

### Test: Demo režim (read-only)
**Cíl**: Ověřit, že demo režim funguje správně.

**Kroky**:
1. Přihlaste se jako demo uživatel (`demo@efil.cz` / `demo1234`)
2. Zkuste:
   - Upravit filament
   - Smazat filament
   - Přidat nový filament
   - Zaznamenat spotřebu
3. Zkontrolujte:
   - Zobrazí se chybová zpráva o demo režimu
   - Data se nezmění

**Očekávaný výsledek**: Demo režim je read-only (kromě admin_efil).

**Poznámky**: Testujte i s admin_efil účtem v demo evidenci.

---

### Test: Role-based access control
**Cíl**: Ověřit, že oprávnění fungují správně.

**Kroky**:
1. Vytvořte evidenci a pozvěte uživatele s různými rolemi:
   - `read` - pouze čtení
   - `write` - čtení a zápis
   - `manage` - čtení, zápis a správa uživatelů
2. Přihlaste se jako každý uživatel a zkuste:
   - Zobrazit filamenty (mělo by fungovat pro všechny)
   - Upravit filament (pouze write/manage)
   - Přidat uživatele (pouze manage/owner)
   - Smazat evidenci (pouze owner)
3. Zkontrolujte, že oprávnění jsou respektována

**Očekávaný výsledek**: Oprávnění fungují správně podle rolí.

**Poznámky**: Testujte i edge cases (např. změna role během session).

---

## 6. 📧 E-mail funkce

### Test: Reset hesla flow
**Cíl**: Ověřit, že reset hesla funguje end-to-end.

**Kroky**:
1. Na přihlašovací stránce klikněte na "Zapomenuté heslo"
2. Zadejte email existujícího uživatele
3. Zkontrolujte e-mailovou schránku
4. Klikněte na odkaz v e-mailu
5. Zadejte nové heslo
6. Přihlaste se s novým heslem

**Očekávaný výsledek**: Reset hesla funguje správně.

**Poznámky**: Testujte i s neplatným/expirovaným tokenem.

---

### Test: Pozvánka do evidence
**Cíl**: Ověřit, že pozvánka funguje správně.

**Kroky**:
1. Jako vlastník evidence vygenerujte sdílecí kód
2. Otevřete aplikaci v anonymním okně (nebo použijte jiný účet)
3. Zadejte kód pozvánky
4. Zkontrolujte:
   - Uživatel je přidán do evidence
   - Oprávnění jsou správná
   - E-mail notifikace (pokud je nastaveno)

**Očekávaný výsledek**: Pozvánka funguje správně.

**Poznámky**: Testujte i s neplatným kódem.

---

## 7. 🎯 Performance a plynulost

### Test: Načítání dat
**Cíl**: Ověřit, že aplikace je rychlá a plynulá.

**Kroky**:
1. Otevřete aplikaci s velkým množstvím dat (100+ filamentů)
2. Zkontrolujte:
   - Čas načtení dat
   - Plynulost scrollování
   - Rychlost přepínání mezi view
3. Otevřete DevTools Network tab
4. Zkontrolujte:
   - Velikost API odpovědí
   - Počet requestů
   - Čas načtení

**Očekávaný výsledek**: Aplikace je rychlá i s velkým množstvím dat.

**Poznámky**: Testujte i na pomalejším připojení (throttling v DevTools).

---

### Test: Offline handling
**Cíl**: Ověřit, že aplikace zobrazuje užitečné chyby při offline stavu.

**Kroky**:
1. Otevřete aplikaci
2. V DevTools zapněte "Offline" mode
3. Zkuste provést akci (např. uložit filament)
4. Zkontrolujte:
   - Zobrazí se chybová zpráva
   - Zpráva je srozumitelná
   - Aplikace se nezhroutí

**Očekávaný výsledek**: Aplikace zobrazuje užitečné chyby při offline stavu.

**Poznámky**: Testujte i s částečným připojením (pomalé).

---

## 📋 Checklist pro každý release

Před každým release proveďte:

- [ ] Vizuální kontrola všech obrazovek
- [ ] Test na mobilním zařízení (320px, 768px)
- [ ] Test History API (Zpět/Vpřed)
- [ ] Test formulářů a validace
- [ ] Test groupování
- [ ] Test demo režimu
- [ ] Test reset hesla (pokud je SMTP nastaveno)
- [ ] Test performance s velkým množstvím dat
- [ ] Kontrola konzole pro JavaScript chyby
- [ ] Kontrola Network tabu pro chybné requesty

---

## 🐛 Hlášení problémů

Při nalezení problému zaznamenejte:
1. **Kroky k reprodukci** - Co jste dělali
2. **Očekávané chování** - Co jste očekávali
3. **Aktuální chování** - Co se stalo
4. **Prostředí** - Prohlížeč, OS, velikost obrazovky
5. **Screenshoty** - Pokud je to možné
