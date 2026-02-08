# Tailwind CSS Build Instructions

## Instalace

1. Nainstalujte Node.js (pokud ještě není nainstalováno)
2. V rootu projektu spusťte:
   ```bash
   npm install
   ```

## Build proces

### Vývoj (watch mode)
```bash
npm run watch:css
```

### Produkce (minifikovaný build)
```bash
npm run build:css
```

## Výstup

Build vytvoří soubor `assets/css/tailwind.css`, který obsahuje pouze použité Tailwind třídy z projektu.

## Aktualizace index.html

Po buildu se v `index.html` nahradí:
- `<script src="https://cdn.tailwindcss.com"></script>` → `<link rel="stylesheet" href="assets/css/tailwind.css">`

## Poznámka

Pro produkční nasazení:
1. Spusťte `npm run build:css`
2. Commitněte `assets/css/tailwind.css`
3. CDN script v `index.html` bude nahrazen linkem na builovaný CSS
