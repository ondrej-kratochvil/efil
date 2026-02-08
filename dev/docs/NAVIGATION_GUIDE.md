# Průvodce navigací v aplikaci eFil

## Postup k editačnímu formuláři filamentu

1. **Homepage** (v přihlášeném stavu)
   - Zobrazí se grid filamentů seskupených podle materiálu

2. **Vybrat materiál** (karta MAT)
   - Kliknout na kartu s materiálem (např. PLA, PETG, ASA)
   - Dojde k přepnutí na kartu **BAR** (barva)
   - Zobrazí se filamenty filtrované podle vybraného materiálu, seskupené podle barvy

3. **Vybrat barvu** (karta BAR)
   - Kliknout na kartu s barvou (např. Galaxy Black, Prusa Orange)
   - Dojde k přepnutí na kartu **VÝR** (výrobce)
   - Zobrazí se filamenty filtrované podle materiálu a barvy, seskupené podle výrobce

4. **Vybrat konkrétní filament** (karta VÝR)
   - Kliknout na název filamentu (nebo na kartu s výrobcem)
   - Zobrazí se **editační formulář** filamentu

**Přidání nového filamentu z wizardu:** Na každé obrazovce (MAT, BAR, VÝR) je za kartami položka **+**. Kliknutím se otevře formulář pro nový filament. Na MAT se nic nepředvyplní, na BAR se předvyplní materiál (pokud je vyfiltrovaný), na VÝR se předvyplní materiál i barva.

5. **Čerpání filamentu**
   - V editačním formuláři kliknout na **hmotnost**
   - Zobrazí se formulář pro čerpání filamentu

## Navigační struktura

```
Homepage (MAT)
  └─> BAR (barva)
      └─> VÝR (výrobce)
          └─> Editační formulář
              └─> Kliknutí na hmotnost → Čerpání
```

## Poznámky

- Navigace probíhá pomocí karet MAT → BAR → VÝR
- Každý krok filtruje filamenty podle předchozího výběru
- Na každé obrazovce je karta **+** pro přidání nového filamentu (s předvyplněním podle aktuálního filtru)
- Editační formulář se otevře kliknutím na konkrétní filament
- Čerpání se otevře kliknutím na hmotnost v editačním formuláři
