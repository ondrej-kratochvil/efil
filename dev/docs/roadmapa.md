# Roadmapa eFil

## 📅 Aktuální stav

Aplikace je v **produkční verzi** s plně funkčními základními funkcemi:
- Správa filamentů
- Sledování spotřeby
- Multiuser funkce
- Sdílení evidencí
- Knihovna cívek
- E-mail notifikace

---

## 🎯 Plánované vylepšení

### Krátkodobé (1-3 měsíce)

#### 1. Dark mode
- **Priorita**: Střední
- **Popis**: Přidání dark mode s toggle v menu
- **Technické detaily**:
  - CSS proměnné pro barvy
  - `localStorage` pro uložení preference
  - Respektování `prefers-color-scheme` jako výchozí

#### 2. Export dat
- **Priorita**: Vysoká
- **Popis**: Možnost exportovat data do CSV/Excel
- **Funkce**:
  - Export filamentů
  - Export historie spotřeby
  - Export statistik

#### 3. Import dat
- **Priorita**: Vysoká
- **Popis**: Možnost importovat data z CSV/Excel
- **Funkce**:
  - Import filamentů
  - Validace dat
  - Preview před importem

#### 4. Vylepšené statistiky
- **Priorita**: Střední
- **Popis**: Rozšíření statistik o více metrik
- **Funkce**:
  - Graf spotřeby v čase
  - Průměrná spotřeba za den/týden/měsíc
  - Předpověď vyčerpání (pokud je známá průměrná spotřeba)

#### 5. Notifikace o nízkých zásobách
- **Priorita**: Střední
- **Popis**: Upozornění, když filament klesne pod určitou hranici
- **Funkce**:
  - Nastavitelná hranice pro každý filament
  - E-mail notifikace
  - Zobrazení v aplikaci

---

### Střednědobé (3-6 měsíců)

#### 6. API dokumentace
- **Priorita**: Nízká
- **Popis**: OpenAPI/Swagger dokumentace pro API
- **Funkce**:
  - Automaticky generovaná dokumentace
  - Možnost testování API přímo z dokumentace

#### 7. Mobilní aplikace (PWA)
- **Priorita**: Vysoká
- **Popis**: Progressive Web App s offline podporou
- **Funkce**:
  - Service Worker pro offline caching
  - Push notifikace
  - Instalace na domovskou obrazovku

#### 8. Více jazyků (i18n)
- **Priorita**: Střední
- **Popis**: Podpora pro více jazyků (angličtina, němčina)
- **Funkce**:
  - JSON soubory s překlady
- **Struktura**: `/assets/i18n/cs.json`, `/assets/i18n/en.json`

#### 9. Volba jednotek od začátku (locale)
- **Priorita**: Nízká
- **Popis**: Uživatel (např. Američan) si při prvním použití nebo v nastavení zvolí preferované jednotky: měna (Kč / USD / EUR / …) a hmotnost (kg / lb). Aplikace pak zobrazuje a případně ukládá hodnoty v zvolených jednotkách.
- **Možné přístupy**:
  - Ukládat v jedné základní měně (Kč) a jedné hmotnosti (g), zobrazovat podle preference (pouze překlad labelů, nebo konverze podle kurzu).
  - Případně ukládat měnu u položky a zobrazovat v ní.
- **Rozsah**: Nastavení účtu nebo evidence, preference v localStorage, přizpůsobení formulářů a přehledů.

#### 10. Pokročilé filtry
- **Priorita**: Střední
- **Popis**: Rozšíření filtrů o více kritérií
- **Funkce**:
  - Filtrování podle výrobce
  - Filtrování podle umístění
  - Filtrování podle data nákupu
  - Kombinace více filtrů

#### 11. Historie změn
- **Priorita**: Nízká
- **Popis**: Zobrazení historie změn pro každý filament
- **Funkce**:
  - Audit log všech změn
  - Zobrazení kdo a kdy změnil
  - Možnost vrátit změnu

---

### Dlouhodobé (6+ měsíců)

#### 11. QR kódy
- **Priorita**: Střední
- **Popis**: Generování QR kódů pro filamenty
- **Funkce**:
  - QR kód s informacemi o filamentu
  - Tisk štítků
  - Skenování QR kódu pro rychlý přístup

#### 12. Integrace s 3D tiskárnami
- **Priorita**: Nízká
- **Popis**: Automatické sledování spotřeby z tiskáren
- **Funkce**:
  - API pro integraci s OctoPrint, Klipper, atd.
  - Automatické odečítání spotřeby po tisku

#### 13. Pokročilé reporty
- **Priorita**: Nízká
- **Popis**: Generování PDF reportů
- **Funkce**:
  - Měsíční/roční reporty
  - Grafy a statistiky
  - Export do PDF

#### 14. Cloud synchronizace
- **Priorita**: Nízká
- **Popis**: Synchronizace mezi více zařízeními
- **Funkce**:
  - Real-time synchronizace
  - Offline mode s automatickou synchronizací

---

## 🔧 Technické vylepšení

### Refaktoring
- [ ] Rozdělení `app.js` na menší moduly
- [ ] Zavedení build procesu (minifikace, bundling)
- [ ] TypeScript pro lepší type safety (volitelné)

### Testování
- [ ] Rozšíření automatizovaných testů
- [ ] E2E testy (Cypress/Playwright)
- [ ] Performance testy

### Dokumentace
- [ ] Rozšíření API dokumentace
- [ ] Video tutoriály
- [ ] Screenshoty v dokumentaci

---

## 🐛 Známé problémy a omezení

### Aktuální omezení
1. **Bez offline podpory** – Aplikace vyžaduje připojení k internetu
2. **Bez exportu dat** – Data nelze exportovat
3. **Bez importu dat** – Data nelze importovat
4. **Jedna měna a jednotky** – Měna (Kč/CZK) a hmotnost (kg, g) jsou pouze překládané podle jazyka; volba jiných jednotek (USD, lb) je v plánu (viz bod 9. Volba jednotek)

### Známé problémy
- (Žádné kritické problémy v aktuální verzi)

---

## 📊 Metriky úspěchu

### Uživatelské metriky
- Počet aktivních uživatelů
- Počet evidencí
- Počet filamentů na evidenci
- Průměrná spotřeba za měsíc

### Technické metriky
- Čas načtení stránky
- Velikost API odpovědí
- Počet chyb v konzoli
- Uptime serveru

---

## 🤝 Přispívání

Pokud chcete přispět k vývoji:

1. **Fork** repozitáře
2. **Vytvořte branch** pro feature (`git checkout -b feature/nova-funkce`)
3. **Commit** změny (`git commit -m "feat: popis změny"`)
4. **Push** do branch (`git push origin feature/nova-funkce`)
5. **Otevřete Pull Request**

### Coding standards
- Dodržujte `.cursorrules`
- Používej atomické commity
- Před commitem spusťte testy
- Aktualizujte dokumentaci

---

## 📝 Poznámky

- Roadmapa je flexibilní a může se měnit podle potřeb uživatelů
- Priorita se může změnit na základě feedbacku
- Některé funkce mohou být přesunuty nebo zrušeny

---

**Poslední aktualizace**: 2026-02-06
